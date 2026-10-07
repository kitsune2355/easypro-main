<?php
// ปิดการแสดง error ออกหน้าจอเมื่อขึ้น Production
ini_set('display_errors', 0); 
error_reporting(E_ALL);

include 'config_ctrl/connect.php'; 

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type"); 

if (mysqli_connect_errno()) {
    http_response_code(500); 
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()], JSON_UNESCAPED_UNICODE);
    exit;
}

date_default_timezone_set('Asia/Bangkok'); 

$action = isset($_GET['action']) ? $_GET['action'] : null; 
$response = ['success' => false, 'data' => []];

if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุการดำเนินการ (action)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ----------------------------------------------------------------
// ✅ 1. ดึงข้อมูลทั้งหมด (GET ALL) พร้อม Search และ Pagination
// ----------------------------------------------------------------
if ($action === 'get_all') {
    $ag_id = isset($_GET['ag_id']) ? mysqli_real_escape_string($connect, $_GET['ag_id']) : '';
    $search = isset($_GET['search']) ? mysqli_real_escape_string($connect, trim($_GET['search'])) : '';
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    if (empty($ag_id)) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบรหัสหน่วยงาน (ag_id)'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ----------------------------------------------------------------
    // ⭐ AUTO SYNC: ดึงประเภทงานจาก tb_repair_system มาเพิ่มใน SLA อัตโนมัติ
    // กรองเฉพาะสถานะ 0 หรือ Active
    // ----------------------------------------------------------------
    $syncSql = "INSERT INTO job_sla_settings (ag_id, rps_id, created_at)
                SELECT rps_ag_id, rps_id, NOW()
                FROM tb_repair_system
                WHERE rps_ag_id = '$ag_id' AND rps_status IN ('0', 'Active')
                AND rps_id NOT IN (
                    SELECT rps_id FROM job_sla_settings WHERE ag_id = '$ag_id' AND rps_id IS NOT NULL
                )";
    mysqli_query($connect, $syncSql);


    // 1. สร้างเงื่อนไข WHERE (ใส่ Alias ให้ชัดเจน j = job_sla_settings, r = tb_repair_system)
    $whereConditions = [
        "j.ag_id = '$ag_id'",
        "r.rps_status IN ('0', 'Active')" // ซ่อนประเภทงานที่ถูกลบหรือ Inactive
    ];

    // ถ้ามีการพิมพ์ค้นหา
    if ($search !== '') {
        $whereConditions[] = "(j.sla_step1 LIKE '%$search%' OR j.sla_step2 LIKE '%$search%' OR r.rps_name LIKE '%$search%')";
    }

    $whereSql = implode(' AND ', $whereConditions);

    // 2. ดึงจำนวนข้อมูลทั้งหมด (Total) แบบ JOIN
    $countSql = "SELECT COUNT(j.id) as total 
                 FROM job_sla_settings j
                 INNER JOIN tb_repair_system r ON j.rps_id = r.rps_id
                 WHERE $whereSql";
                 
    $countResult = mysqli_query($connect, $countSql);
    $totalRows = 0;
    if ($countResult) {
        $countRow = mysqli_fetch_assoc($countResult);
        $totalRows = (int)$countRow['total'];
    }

    // 3. ดึงข้อมูลตาม Limit และ Offset แบบ JOIN
    $sql = "SELECT j.*, r.rps_name, r.rps_code 
            FROM job_sla_settings j
            INNER JOIN tb_repair_system r ON j.rps_id = r.rps_id
            WHERE $whereSql 
            ORDER BY r.rps_id ASC 
            LIMIT $limit OFFSET $offset";
            
    $result = mysqli_query($connect, $sql);
    
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['id'] = (int)$row['id'];
            $row['sla_step1_hours'] = (float)$row['sla_step1_hours'];
            $row['sla_step2_hours'] = (float)$row['sla_step2_hours'];
            $data[] = $row;
        }
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $data,
        'total' => $totalRows,
        'limit' => $limit,
        'offset' => $offset
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
// ----------------------------------------------------------------
// ✅ 2. บันทึก/อัปเดตข้อมูล (SAVE - INSERT/UPDATE)
// ----------------------------------------------------------------
if ($action === 'save') {
    // รับค่า JSON Payload จาก Axios POST
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    $ag_id = isset($input['ag_id']) ? mysqli_real_escape_string($connect, $input['ag_id']) : '';
    $user_id = isset($input['user_id']) ? mysqli_real_escape_string($connect, $input['user_id']) : '';
    $items = isset($input['items']) ? $input['items'] : [];

    if (empty($ag_id)) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบรหัสหน่วยงาน']);
        exit;
    }

    $current_time = date('Y-m-d H:i:s');

    // ใช้ Transaction เผื่อเกิด Error ข้อมูลจะได้ไม่บันทึกไปครึ่งๆ กลางๆ
    mysqli_begin_transaction($connect);

    try {
        foreach ($items as $item) {
            $id = isset($item['id']) ? (int)$item['id'] : 0;
            $sla_step1 = mysqli_real_escape_string($connect, $item['sla_step1']);
            $sla_step2 = mysqli_real_escape_string($connect, $item['sla_step2']);
            $sla_step1_hours = isset($item['sla_step1_hours']) ? (float)$item['sla_step1_hours'] : 0;
            $sla_step2_hours = isset($item['sla_step2_hours']) ? (float)$item['sla_step2_hours'] : 0;

            if ($id <= 0) {
                // เป็นข้อมูลใหม่ (ID น้อยกว่าหรือเท่ากับ 0 ที่เราจำลองไว้ในหน้าบ้าน) -> INSERT
                $sql = "INSERT INTO job_sla_settings 
                        (ag_id, sla_step1, sla_step2, sla_step1_hours, sla_step2_hours, created_by, created_at) 
                        VALUES 
                        ('$ag_id', '$sla_step1', '$sla_step2', $sla_step1_hours, $sla_step2_hours, '$user_id', '$current_time')";
                mysqli_query($connect, $sql);
            } else {
                // เป็นข้อมูลเดิม -> UPDATE
                $sql = "UPDATE job_sla_settings SET 
                            sla_step1 = '$sla_step1',
                            sla_step2 = '$sla_step2',
                            sla_step1_hours = $sla_step1_hours,
                            sla_step2_hours = $sla_step2_hours,
                            updated_by = '$user_id',
                            updated_at = '$current_time'
                        WHERE id = $id AND ag_id = '$ag_id'";
                mysqli_query($connect, $sql);
            }
        }

        mysqli_commit($connect);
        echo json_encode(['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);
    } catch (Exception $e) {
        mysqli_rollback($connect);
        echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// ----------------------------------------------------------------
// ✅ 3. ลบข้อมูล (DELETE)
// ----------------------------------------------------------------
if ($action === 'delete') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    
    $ids = isset($input['ids']) ? $input['ids'] : [];
    $ag_id = isset($input['ag_id']) ? mysqli_real_escape_string($connect, $input['ag_id']) : '';

    if (!empty($ids) && !empty($ag_id)) {
        // กรองเอาเฉพาะตัวเลขเพื่อความปลอดภัย
        $safeIds = array_map('intval', $ids);
        $idList = implode(',', $safeIds);

        // ป้องกันไม่ให้ส่ง ID = 0 (ข้อมูลชั่วคราว) มาลบในฐานข้อมูล
        $sql = "DELETE FROM job_sla_settings WHERE id IN ($idList) AND id > 0 AND ag_id = '$ag_id'";
        
        if (mysqli_query($connect, $sql)) {
            echo json_encode(['success' => true, 'message' => 'ลบข้อมูลสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'error' => mysqli_error($connect)]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ไม่มีข้อมูลที่ต้องการลบ']);
    }
    exit;
}

// หากส่ง action มาผิด
echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>