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

// ปิดการเชื่อมต่อฐานข้อมูล
if (isset($connect)) mysqli_close($connect);

// ส่งผลลัพธ์ออกไป
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>