<?php
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
// ✅ 1. ดึงข้อมูลทั้งหมด (GET ALL) - เปลี่ยนเป็น tb_repair_group
// ----------------------------------------------------------------
if ($action === 'get_all') {
    $ag_id = isset($_GET['ag_id']) ? mysqli_real_escape_string($connect, $_GET['ag_id']) : '';
    $search = isset($_GET['search']) ? mysqli_real_escape_string($connect, trim($_GET['search'])) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    $where_clause = "WHERE rpg_ag_id = '$ag_id'";
    
    if (!empty($search)) {
        $where_clause .= " AND (rpg_name LIKE '%$search%')";
    }

    $sql_count = "SELECT COUNT(*) as total FROM tb_repair_group $where_clause";
    $result_count = mysqli_query($connect, $sql_count);
    $total_rows = $result_count ? mysqli_fetch_assoc($result_count)['total'] : 0;

    $sql = "SELECT rpg_id, rpg_name, rpg_status 
            FROM tb_repair_group 
            $where_clause
            ORDER BY rpg_id DESC";

    if ($limit > 0) {
        $sql .= " LIMIT $limit OFFSET $offset";
    }
            
    $result = mysqli_query($connect, $sql);
    
    if ($result) {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                'id' => $row['rpg_id'],
                'rpg_name' => $row['rpg_name'],
                'status' => $row['rpg_status']
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
    $changes = isset($input['data']) ? $input['data'] : [];
    $ag_id = isset($input['ag_id']) ? $input['ag_id'] : '';
    $user_sin = isset($input['user_sin']) ? $input['user_sin'] : '';
    
    if (!empty($changes) && is_array($changes)) {
        mysqli_begin_transaction($connect);
        try {
            // Update & Insert สำหรับ tb_repair_group
            $stmt_update = mysqli_prepare($connect, "UPDATE tb_repair_group SET rpg_name=?, rpg_status=?, rpg_user_upd=?, rpg_upd=? WHERE rpg_id=?");
            $stmt_insert = mysqli_prepare($connect, "INSERT INTO tb_repair_group (rpg_name, rpg_status, rpg_ag_id, rpg_user_ins, rpg_ins, rpg_user_upd, rpg_upd) VALUES (?, ?, ?, ?, ?, ?, ?)");

            $current_time = date('Y-m-d H:i:s');

            foreach ($changes as $row) {
                $db_id      = intval($row['id'] ?? 0);
                $rpg_name   = $row['rpg_name'] ?? '';
                $rpg_status = $row['status'] ?? '0'; // 0=Active, 1=Inactive
                
                if ($db_id > 0) {
                    mysqli_stmt_bind_param($stmt_update, "ssssi", $rpg_name, $rpg_status, $user_sin, $current_time, $db_id);
                    mysqli_stmt_execute($stmt_update);
                } else {
                    mysqli_stmt_bind_param($stmt_insert, "sssssss", $rpg_name, $rpg_status, $ag_id, $user_sin, $current_time, $user_sin, $current_time);
                    mysqli_stmt_execute($stmt_insert);
                }
            }
            
            mysqli_commit($connect);
            $response['success'] = true;
        } catch (Exception $e) {
            mysqli_rollback($connect);
            $response['error'] = $e->getMessage();
        }
    }
}

// ----------------------------------------------------------------
// ✅ 3. ตรวจสอบชื่อซ้ำ (Optional - ปรับตาม rpg_name)
// ----------------------------------------------------------------
elseif ($action === 'check_duplicate') {
    $check_val = isset($_GET['value']) ? trim($_GET['value']) : '';
    $db_id = isset($_GET['db_id']) ? intval($_GET['db_id']) : 0;

    $sql = "SELECT COUNT(*) as count FROM tb_repair_group WHERE rpg_name = ? AND rpg_id != ?";
    $stmt = mysqli_prepare($connect, $sql);
    mysqli_stmt_bind_param($stmt, "si", $check_val, $db_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $response['success'] = true;
    $response['is_duplicate'] = ($row['count'] > 0);
}

mysqli_close($connect);
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>