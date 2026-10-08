<?php
// แสดงข้อผิดพลาด PHP เพื่อการตรวจสอบ (ปิดเมื่อขึ้น Production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config_ctrl/connect.php'; 

if (mysqli_connect_errno()) {
    http_response_code(500); 
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type"); 

date_default_timezone_set('Asia/Bangkok'); 

$action = isset($_GET['action']) ? $_GET['action'] : null; 
$response = ['success' => false, 'data' => []];

if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุการดำเนินการ (action)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// รับข้อมูล JSON payload กรณีเป็น POST
$request_body = file_get_contents('php://input');
$post_data = json_decode($request_body, true);
if (!$post_data) {
    $post_data = $_POST; 
}

if ($action === 'get_all') {
    ob_clean(); 
    
    $ag_id = isset($_GET['ag_id']) ? $_GET['ag_id'] : '';
    $machine_filter = isset($_GET['machine']) ? $_GET['machine'] : '';
    $checksheet_filter = isset($_GET['checksheet']) ? $_GET['checksheet'] : '';
    $type_filter = isset($_GET['type']) ? $_GET['type'] : '';      
    $location_filter = isset($_GET['location']) ? $_GET['location'] : ''; 
    
    // รับพารามิเตอร์วันที่จาก FullCalendar
    $start_date = isset($_GET['start']) ? $_GET['start'] : '';
    $end_date = isset($_GET['end']) ? $_GET['end'] : '';

    $sql = "SELECT 
                p.id, 
                p.event_date, 
                p.status,
                p.completed_at,
                m.asset_name AS machine_name,
                g.TGroupName AS machine_type,
                a.area_name AS location_name,
                c.name AS checksheet_name
            FROM pm_plan_events p
            LEFT JOIN tb_ass_list m ON p.machine_id = m.ass_id
            LEFT JOIN tb_asset_group g ON m.asset_type = g.GroupId
            LEFT JOIN tb_area a ON m.asset_rp_area_id = a.area_id
            LEFT JOIN pm_checksheets c ON p.checksheet_id = c.id
            WHERE 1=1";

    $params = [];
    $types = "";

    // --- ส่วนการกรองช่วงวันที่ (Performance Boost) ---
    if (!empty($start_date) && !empty($end_date)) {
        $sql .= " AND p.event_date >= ? AND p.event_date <= ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
    }

    // --- ส่วนการกรองข้อมูลอื่นๆ (Filtering) ---
    if (!empty($ag_id)) {
        $sql .= " AND p.ag_id = ?";
        $params[] = $ag_id;
        $types .= "s";
    }

    if (!empty($machine_filter)) {
        $sql .= " AND m.asset_name LIKE ?"; 
        $params[] = "%$machine_filter%";
        $types .= "s";
    }

    if (!empty($checksheet_filter)) {
        $sql .= " AND c.name LIKE ?";
        $params[] = "%$checksheet_filter%";
        $types .= "s";
    }

    if (!empty($type_filter)) {
        $sql .= " AND g.TGroupName LIKE ?"; 
        $params[] = "%$type_filter%";
        $types .= "s";
    }

    if (!empty($location_filter)) {
        $sql .= " AND a.area_name LIKE ?"; 
        $params[] = "%$location_filter%";
        $types .= "s";
    }

    $stmt = $connect->prepare($sql);
    
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $events = [];
            while ($row = $result->fetch_assoc()) {
                $events[] = [
                    'id' => $row['id'],
                    'title' => ($row['checksheet_name']) .' '. ($row['machine_name']) .' '. ($row['location_name']),
                    'start' => $row['event_date'],
                    'extendedProps' => [
                        'machine' => $row['machine_name'],
                        'type' => $row['machine_type'],      // ส่งออกไปหน้าบ้าน
                        'location' => $row['location_name'], // ส่งออกไปหน้าบ้าน
                        'checksheet' => $row['checksheet_name'],
                        'status' => $row['status'],
                        'completed_at' => $row['completed_at']
                    ]
                ];
            }
            echo json_encode(['success' => true, 'data' => $events], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => $connect->error]);
    }
    exit; // สำคัญ: ต้องหยุดการทำงานเพื่อไม่ให้โค้ดส่วนอื่นพ่นอะไรออกมาต่อ
}

// รายละเอียดงาน PM 1 รายการ (popup ในปฏิทิน): ข้อมูลเครื่อง/เช็คชีต + สิ่งที่ช่างต้องเตรียม + ประวัติเลื่อน/ผลการทำงาน
if ($action === 'get_event_detail') {
    $event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $one = function ($sql, $types = '', ...$params) use ($connect) {
        $stmt = $connect->prepare($sql);
        if ($types !== '') $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    };

    $ev = $one("SELECT e.id, e.plan_id, e.event_date, e.status, e.completed_at, e.checksheet_id,
                       m.ass_code, m.asset_name, m.asset_model, m.asset_sn,
                       g.TGroupName AS machine_type, a.area_name AS location_name,
                       c.name AS checksheet_name, c.doc_no, c.rev_no, c.estimated_time,
                       f.description AS freq_desc
                FROM pm_plan_events e
                LEFT JOIN tb_ass_list m ON e.machine_id = m.ass_id
                LEFT JOIN tb_asset_group g ON m.asset_type = g.GroupId
                LEFT JOIN tb_area a ON m.asset_rp_area_id = a.area_id
                LEFT JOIN pm_checksheets c ON e.checksheet_id = c.id
                LEFT JOIN pm_plans pl ON e.plan_id = pl.id
                LEFT JOIN pm_freq_options f ON f.freq_value = pl.frequency
                WHERE e.id = ?", 'i', $event_id);
    if (!$ev) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบงาน PM นี้'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $ev = $ev[0];
    $cs = (int)$ev['checksheet_id'];

    // จุดตรวจ: 1 = บังคับถ่ายรูป, 3 = ถ่ายรูปเมื่อผิดปกติ
    $items = $one("SELECT COUNT(*) AS total, COALESCE(SUM(photo_required_id = 1), 0) AS photo_always,
                          COALESCE(SUM(photo_required_id = 3), 0) AS photo_abnormal
                   FROM pm_checksheet_items WHERE checksheet_id = ?", 'i', $cs)[0];
    $spares = $one("SELECT part_name, quantity FROM pm_checksheet_spares WHERE checksheet_id = ? ORDER BY id", 'i', $cs);
    // คู่มือ/ไฟล์แนบของเช็คชีต (file_path เป็น path จาก root ของระบบ เช่น uploads/files/...) — เอกสารก่อน แล้วค่อยรูป
    $files = $one("SELECT file_type, file_name, file_path FROM pm_checksheet_files
                   WHERE checksheet_id = ? ORDER BY file_type = 'image', id", 'i', $cs);

    // ทำครั้งล่าสุดของแผนเดียวกัน (ก่อนรอบนี้)
    $last = $one("SELECT MAX(COALESCE(DATE(completed_at), event_date)) AS d FROM pm_plan_events
                  WHERE plan_id = ? AND status = 1 AND id <> ? AND event_date <= ?", 'iis', (int)$ev['plan_id'], $event_id, $ev['event_date'])[0]['d'];

    // ประวัติการเลื่อน (plan_id ในตารางนี้ = id ของ pm_plan_events)
    $postpones = $one("SELECT old_date, new_date, reason FROM pm_plan_postpone_history WHERE plan_id = ? ORDER BY id DESC", 'i', $event_id);

    // ทำแล้ว: วันที่ทำจริง ผู้ตรวจ ผลประเมิน
    $work = null;
    if ((int)$ev['status'] === 1) {
        $wr = $one("SELECT actual_date, remarks FROM pm_work_records WHERE plan_event_id = ? ORDER BY id DESC LIMIT 1", 'i', $event_id);
        $insp = $one("SELECT inspector_name_snapshot AS name FROM pm_work_record_inspectors WHERE plan_event_id = ? ORDER BY id", 'i', $event_id);
        $evl = $one("SELECT evaluator_name, average_score, eval_date FROM pm_work_evaluations WHERE plan_event_id = ? ORDER BY id DESC LIMIT 1", 'i', $event_id);
        $work = [
            'actual_date' => $wr[0]['actual_date'] ?? null,
            'remarks' => $wr[0]['remarks'] ?? '',
            'inspectors' => array_values(array_filter(array_column($insp, 'name'))),
            'evaluation' => $evl[0] ?? null
        ];
    }

    echo json_encode(['success' => true, 'data' => [
        'event' => $ev,
        'items' => array_map('intval', $items),
        'spares' => $spares,
        'files' => $files,
        'last_done' => $last,
        'postpones' => $postpones,
        'work' => $work,
        'today' => date('Y-m-d')
    ]], JSON_UNESCAPED_UNICODE);
    exit;
}

// ปิดการเชื่อมต่อฐานข้อมูล
if (isset($connect)) mysqli_close($connect);

// ส่งผลลัพธ์ออกไป
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>