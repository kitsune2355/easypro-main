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
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    // เปลี่ยนไปใช้ tb_repair_system
    $where_clause = "WHERE rps_ag_id = '$ag_id'";
    
    // ถ้ามีการค้นหา
    if (!empty($search)) {
        $where_clause .= " AND (rps_code LIKE '%$search%' 
                             OR rps_name LIKE '%$search%')";
    }

    // นับจำนวนรวมทั้งหมด
    $sql_count = "SELECT COUNT(*) as total FROM tb_repair_system $where_clause";
    $result_count = mysqli_query($connect, $sql_count);
    $total_rows = $result_count ? mysqli_fetch_assoc($result_count)['total'] : 0;

    // ดึงข้อมูลจริง
    $sql = "SELECT rps_id, rps_code, rps_name, rps_status 
            FROM tb_repair_system 
            $where_clause
            ORDER BY rps_id DESC";

    if ($limit > 0) {
        $sql .= " LIMIT $limit OFFSET $offset";
    }
            
    $result = mysqli_query($connect, $sql);
    
    if ($result) {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                'id' => $row['rps_id'],
                'job_type_id' => $row['rps_code'],    // Map ให้ตรงกับ Frontend
                'job_type_name' => $row['rps_name'],  // Map ให้ตรงกับ Frontend
                'status' => $row['rps_status']        // ส่งค่าสถานะออกไป
            ];
        }
        $response['success'] = true;
        $response['total'] = $total_rows;
        $response['data'] = $data;
    } else {
        $response['error'] = 'Database query error: ' . mysqli_error($connect);
    }
}

// ----------------------------------------------------------------
// ✅ 2. บันทึก หรือ แก้ไขข้อมูล (ADD & UPDATE)
// ----------------------------------------------------------------
elseif ($action === 'save') {
    $input = json_decode(file_get_contents('php://input'), true);
    $changes = isset($input['data']) ? $input['data'] : (is_array($input) ? $input : []);
    $ag_id = isset($input['ag_id']) ? $input['ag_id'] : '';
    $user_sin = isset($input['user_sin']) ? $input['user_sin'] : '';
    
    if (!empty($changes) && is_array($changes)) {
        mysqli_begin_transaction($connect);
        try {
            // Prepared Statements สำหรับ tb_repair_system
            $stmt_update = mysqli_prepare($connect, "UPDATE tb_repair_system SET rps_name=?, rps_code=?, rps_status=?, rps_user_upd=?, rps_upd=? WHERE rps_id=?");
            
            // ปรับ INSERT ให้ครบตามที่กำหนด
            $stmt_insert = mysqli_prepare($connect, "INSERT INTO tb_repair_system (rps_name, rps_code, rps_status, rps_ag_id, rps_user_ins, rps_ins, rps_user_upd, rps_upd) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            $current_time = date('Y-m-d H:i:s');

            foreach ($changes as $row) {
                $db_id          = intval($row['id'] ?? 0);
                $rps_code       = $row['job_type_id'] ?? ''; 
                $rps_name       = $row['job_type_name'] ?? '';
                $rps_status     = $row['status'] ?? 'Active'; // รับค่า Active/Inactive
                
                if ($db_id > 0) {
                    // UPDATE
                    mysqli_stmt_bind_param($stmt_update, "sssssi", $rps_name, $rps_code, $rps_status, $user_sin, $current_time, $db_id);
                    mysqli_stmt_execute($stmt_update);
                } else {
                    // INSERT
                    mysqli_stmt_bind_param($stmt_insert, "ssssssss", $rps_name, $rps_code, $rps_status, $ag_id, $user_sin, $current_time, $user_sin, $current_time);
                    mysqli_stmt_execute($stmt_insert);
                }
            }
            
            mysqli_stmt_close($stmt_update);
            mysqli_stmt_close($stmt_insert);
            
            mysqli_commit($connect);
            $response['success'] = true;
            $response['message'] = 'บันทึกข้อมูลเรียบร้อยแล้ว';
            
        } catch (Exception $e) {
            mysqli_rollback($connect);
            $response['error'] = 'Transaction failed: ' . $e->getMessage();
        }
    } else {
        $response['error'] = 'ไม่มีข้อมูลให้บันทึก หรือรูปแบบข้อมูลไม่ถูกต้อง';
    }
}

// ----------------------------------------------------------------
// ✅ 3. ตรวจสอบข้อมูลซ้ำ (CHECK DUPLICATE)
// ----------------------------------------------------------------
elseif ($action === 'check_duplicate') {
    $check_val = isset($_GET['value']) ? trim($_GET['value']) : '';
    $db_id = isset($_GET['db_id']) ? intval($_GET['db_id']) : 0;

    if ($check_val !== '') {
        // เช็คที่ rps_code
        $sql = "SELECT COUNT(*) as count FROM tb_repair_system WHERE rps_code = ? AND rps_id != ?";
        
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, "si", $check_val, $db_id);
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        $response['success'] = true;
        $response['is_duplicate'] = ($row['count'] > 0);
        
        mysqli_stmt_close($stmt);
    } else {
        $response['error'] = 'Missing value parameter';
    }
}

if (isset($connect)) mysqli_close($connect);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>