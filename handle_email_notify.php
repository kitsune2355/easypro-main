<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config_ctrl/connect.php'; 

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Bangkok'); 

$action = isset($_GET['action']) ? $_GET['action'] : null; 
$response = ['success' => false, 'data' => [], 'error' => ''];

if (!$action) {
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุ action']);
    exit;
}

// ----------------------------------------------------------------
// 1. ดึงข้อมูลอีเมลทั้งหมดตาม ag_id
// ----------------------------------------------------------------
if ($action === 'get') {
    $ag_id = isset($_GET['ag_id']) ? $_GET['ag_id'] : '';
    
    $sql = "SELECT * FROM tb_email_notify WHERE ag_id = ? ORDER BY id DESC";
    $stmt = mysqli_prepare($connect, $sql);
    mysqli_stmt_bind_param($stmt, "s", $ag_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // แปลงสถานะ 1, 0 ให้เป็นข้อความสำหรับ Handsontable
        $row['status'] = ($row['status'] == 1) ? 'ใช้งาน' : 'ระงับ';
        $data[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    
    $response['success'] = true;
    $response['data'] = $data;
    echo json_encode($response);
    exit;
}

// ----------------------------------------------------------------
// 2. บันทึก / อัปเดต / ลบ ข้อมูล (Bulk Save)
// ----------------------------------------------------------------
elseif ($action === 'save') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $ag_id = isset($input['ag_id']) ? $input['ag_id'] : '';
    $user_id = isset($input['user_id']) ? $input['user_id'] : '';
    $data_to_save = isset($input['data']) ? $input['data'] : [];
    $deleted_ids = isset($input['deleted_ids']) ? $input['deleted_ids'] : [];
    
    if (empty($ag_id)) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูล ag_id']);
        exit;
    }

    mysqli_begin_transaction($connect);
    try {
        // 2.1 ลบข้อมูลแถวที่ถูก Delete ทิ้งจาก UI
        if (!empty($deleted_ids)) {
            $placeholders = implode(',', array_fill(0, count($deleted_ids), '?'));
            $sql_del = "DELETE FROM tb_email_notify WHERE id IN ($placeholders) AND ag_id = ?";
            $stmt_del = mysqli_prepare($connect, $sql_del);
            
            // สร้าง types parameter (i = integer, s = string)
            $types = str_repeat('i', count($deleted_ids)) . 's';
            $params = $deleted_ids;
            $params[] = $ag_id;
            
            mysqli_stmt_bind_param($stmt_del, $types, ...$params);
            mysqli_stmt_execute($stmt_del);
            mysqli_stmt_close($stmt_del);
        }

        // 2.2 อัปเดต หรือ เพิ่มข้อมูลใหม่
        $sql_insert = "INSERT INTO tb_email_notify (ag_id, name, position, department, division, email, phone, status, created_at, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
        $stmt_insert = mysqli_prepare($connect, $sql_insert);

        $sql_update = "UPDATE tb_email_notify SET name=?, position=?, department=?, division=?, email=?, phone=?, status=?, updated_at=NOW(), updated_by=? WHERE id=? AND ag_id=?";
        $stmt_update = mysqli_prepare($connect, $sql_update);

        foreach ($data_to_save as $row) {
            $id = isset($row['id']) ? intval($row['id']) : -1;
            $name = $row['name'] ?? '';
            $position = $row['position'] ?? '';
            $department = $row['department'] ?? '';
            $division = $row['division'] ?? '';
            $email = $row['email'] ?? '';
            $phone = $row['phone'] ?? '';
            $status = (isset($row['status']) && $row['status'] === 'ใช้งาน') ? 1 : 0;

            // ถ้าไม่มีทั้งชื่อและอีเมล ให้ข้ามไป
            if (trim($name) === '' && trim($email) === '') continue;

            if ($id < 0) {
                // INSERT แถวใหม่
                mysqli_stmt_bind_param($stmt_insert, "sssssssis", $ag_id, $name, $position, $department, $division, $email, $phone, $status, $user_id);
                mysqli_stmt_execute($stmt_insert);
            } else {
                // UPDATE แถวที่มีอยู่แล้ว
                mysqli_stmt_bind_param($stmt_update, "ssssssissi", $name, $position, $department, $division, $email, $phone, $status, $user_id, $id, $ag_id);
                mysqli_stmt_execute($stmt_update);
            }
        }
        
        mysqli_stmt_close($stmt_insert);
        mysqli_stmt_close($stmt_update);
        
        mysqli_commit($connect);
        $response['success'] = true;

    } catch (Exception $e) {
        mysqli_rollback($connect);
        $response['error'] = 'Transaction failed: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}
?>