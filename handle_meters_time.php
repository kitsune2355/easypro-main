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

// ----------------------------------------------------------------
// ✅ 1. ดึงข้อมูลทั้งหมด (GET ALL)
// ----------------------------------------------------------------
if ($action === 'get_all') {
    $ag_id = isset($_GET['ag_id']) ? mysqli_real_escape_string($connect, $_GET['ag_id']) : '';
    $search = isset($_GET['search']) ? mysqli_real_escape_string($connect, trim($_GET['search'])) : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    $whereConditions = ["round_ag_id = '$ag_id'"];

    // ถ้ามีการค้นหา
    if ($search !== '') {
        $whereConditions[] = "(round_name LIKE '%$search%' OR round_time LIKE '%$search%')";
    }

    $whereSql = implode(' AND ', $whereConditions);

    // ดึงจำนวนทั้งหมด (สำหรับการทำ Pagination)
    $countQuery = "SELECT COUNT(*) as total FROM tb_meter_round WHERE $whereSql";
    $countResult = mysqli_query($connect, $countQuery);
    $totalRows = 0;
    if ($countResult) {
        $totalRows = mysqli_fetch_assoc($countResult)['total'];
    }

    // ดึงข้อมูลตาม Limit / Offset
    $query = "SELECT round_id as id, round_name, round_time 
              FROM tb_meter_round 
              WHERE $whereSql 
              ORDER BY round_id DESC 
              LIMIT $limit OFFSET $offset";
              
    $result = mysqli_query($connect, $query);

    if ($result) {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $data;
        $response['total'] = $totalRows;
    } else {
        $response['error'] = mysqli_error($connect);
    }
}

// ----------------------------------------------------------------
// ✅ 2. บันทึก, แก้ไข, หรือลบข้อมูล (SAVE & DELETE)
// ----------------------------------------------------------------
elseif ($action === 'save') {
    // รับข้อมูล JSON จาก Axios
    $json = file_get_contents('php://input');
    $input = json_decode($json, true);
    
    $ag_id = isset($input['ag_id']) ? mysqli_real_escape_string($connect, $input['ag_id']) : '';
    
    // 1. รับค่า user_id จากที่ส่งมา
    $user_id = isset($input['user_id']) ? mysqli_real_escape_string($connect, $input['user_id']) : '';
    $data = isset($input['data']) ? $input['data'] : [];

    // 2. ดึงเวลาปัจจุบัน
    $now = date('Y-m-d H:i:s');

    mysqli_begin_transaction($connect);
    try {
        foreach ($data as $row) {
            // (ส่วนของการลบข้อมูล ถ้ามี ปล่อยไว้เหมือนเดิม)

            if (isset($row['id'])) {
                $id = (int)$row['id'];
                $round_name = mysqli_real_escape_string($connect, $row['round_name'] ?? '');
                $round_time = mysqli_real_escape_string($connect, $row['round_time'] ?? '');

                // ถ้าเป็นแถวใหม่ (ID = 0 หรือติดลบจากหน้าบ้าน) ให้ Insert (ใส่ทั้ง created และ updated)
                if ($id <= 0) {
                    $insertQuery = "INSERT INTO tb_meter_round 
                                    (round_name, round_time, round_ag_id, created_at, created_by, updated_at, updated_by) 
                                    VALUES 
                                    ('$round_name', '$round_time', '$ag_id', '$now', '$user_id', '$now', '$user_id')";
                    if (!mysqli_query($connect, $insertQuery)) {
                        throw new Exception("Error Insert: " . mysqli_error($connect));
                    }
                } 
                // ถ้าเป็นแถวเดิม ให้ Update (อัปเดตเฉพาะค่า updated)
                else {
                    $updateQuery = "UPDATE tb_meter_round 
                                    SET round_name = '$round_name', 
                                        round_time = '$round_time',
                                        updated_at = '$now',
                                        updated_by = '$user_id'
                                    WHERE round_id = $id AND round_ag_id = '$ag_id'";
                    if (!mysqli_query($connect, $updateQuery)) {
                        throw new Exception("Error Update: " . mysqli_error($connect));
                    }
                }
            }
        }

        mysqli_commit($connect);
        $response['success'] = true;

    } catch (Exception $e) {
        mysqli_rollback($connect);
        $response['success'] = false;
        $response['error'] = $e->getMessage();
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>