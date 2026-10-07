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

if ($action === 'get_department') {
    // ดึงเฉพาะแผนกที่สถานะใช้งานอยู่ (dep_status = 0)
    $sql = "SELECT dep_id, dep_name FROM tb_department2 WHERE dep_status = 0 ORDER BY dep_name ASC";
    $result = mysqli_query($connect, $sql);
    
    if ($result) {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = [
                'dep_id' => $row['dep_id'],
                'dep_name' => $row['dep_name']
            ];
        }
        $response['success'] = true;
        $response['data'] = $data;
    } else {
        $response['error'] = 'Database query error: ' . mysqli_error($connect);
    }
}

// ----------------------------------------------------------------
// ✅ 1. ดึงข้อมูลทั้งหมด (GET ALL)
// ----------------------------------------------------------------
if ($action === 'get_all') {
    $ag_id = isset($_GET['ag_id']) ? mysqli_real_escape_string($connect, $_GET['ag_id']) : '';
    $search = isset($_GET['search']) ? mysqli_real_escape_string($connect, trim($_GET['search'])) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    // สร้างเงื่อนไข WHERE พื้นฐาน
    $where_clause = "WHERE u.user_agency = '$ag_id'";
    
    // ถ้ามีการค้นหา
    if (!empty($search)) {
        $where_clause .= " AND (u.user_id LIKE '%$search%' 
                             OR u.user_name LIKE '%$search%' 
                             OR u.user_fname LIKE '%$search%' 
                             OR u.user_tel LIKE '%$search%' 
                             OR d.dep_name LIKE '%$search%')";
    }

    // นับจำนวนรวมทั้งหมด (สำหรับทำ Pagination ใน Frontend)
    $sql_count = "SELECT COUNT(*) as total 
                  FROM tb_user u 
                  LEFT JOIN tb_department2 d ON u.user_department = d.dep_id AND d.dep_status = 0 
                  $where_clause";
    $result_count = mysqli_query($connect, $sql_count);
    $total_rows = $result_count ? mysqli_fetch_assoc($result_count)['total'] : 0;

    // ดึงข้อมูลจริงพร้อม Limit & Offset
    $sql = "SELECT u.id, u.user_id, u.user_code, u.user_name, u.user_fname, 
                   u.user_level, u.user_tel, u.user_open, d.dep_name 
            FROM tb_user u 
            LEFT JOIN tb_department2 d ON u.user_department = d.dep_id AND d.dep_status = 0
            $where_clause
            ORDER BY u.user_id DESC";

    if ($limit > 0) {
        $sql .= " LIMIT $limit OFFSET $offset";
    }
            
    $result = mysqli_query($connect, $sql);
    
    if ($result) {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['status'] = ($row['user_open'] == 0) ? 'Active' : 'Inactive';
            $data[] = $row;
        }
        $response['success'] = true;
        $response['total'] = $total_rows; // ส่งกลับจำนวนทั้งหมด
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
            $stmt_update = mysqli_prepare($connect, "UPDATE tb_user SET user_id=?, user_name=?, user_fname=?, user_level=?, user_department=?, user_tel=?, user_open=? WHERE id=?");
            $stmt_insert = mysqli_prepare($connect, "INSERT INTO tb_user (user_id, user_name, user_fname, user_level, user_department, user_tel, user_open, user_agency, user_password, user_status_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");

            foreach ($changes as $row) {
                $db_id      = intval($row['id'] ?? 0);
                $user_id_at = $row['user_id'] ?? ''; 
                $user_name  = $row['user_name'] ?? '';
                $user_fname = $row['user_fname'] ?? '';
                $user_level = $row['user_level'] ?? 'employer';
                $user_tel   = $row['user_tel'] ?? '';
                $user_open  = (isset($row['status']) && $row['status'] === 'Inactive') ? 1 : 0;
                $user_dep   = !empty($row['user_department']) ? intval($row['user_department']) : null;

                if ($db_id > 0) {
                    mysqli_stmt_bind_param($stmt_update, "ssssisii", $user_id_at, $user_name, $user_fname, $user_level, $user_dep, $user_tel, $user_open, $db_id);
                    mysqli_stmt_execute($stmt_update);
                } else {
                    $default_password = md5(md5(md5('4123'))); 
                    mysqli_stmt_bind_param($stmt_insert, "ssssisiss", $user_id_at, $user_name, $user_fname, $user_level, $user_dep, $user_tel, $user_open, $ag_id, $default_password);
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
    // รับค่า id ของแถวนั้นๆ (เพื่อไม่ให้เช็คเจอกับตัวเองตอนแก้ไข)
    $db_id = isset($_GET['db_id']) ? intval($_GET['db_id']) : 0;

    if ($check_val !== '') {
        $sql = "SELECT COUNT(*) as count FROM tb_user WHERE user_id = ? AND id != ?";
        
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

// ----------------------------------------------------------------
// ✅ 4. เปลี่ยนรหัสผ่าน (CHANGE PASSWORD)
// ----------------------------------------------------------------
elseif ($action === 'change_password') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id']) ? intval($input['id']) : 0;
    $new_password = isset($input['password']) ? trim($input['password']) : '';

    if ($id > 0 && !empty($new_password)) {
        // เข้ารหัสผ่านด้วยรูปแบบเดิมของระบบ
        $hashed_password = md5(md5(md5($new_password)));
        
        $stmt = mysqli_prepare($connect, "UPDATE tb_user SET user_password=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "si", $hashed_password, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $response['success'] = true;
            $response['message'] = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
        } else {
            $response['error'] = 'ไม่สามารถอัปเดตรหัสผ่านได้: ' . mysqli_error($connect);
        }
        mysqli_stmt_close($stmt);
    } else {
        $response['error'] = 'ข้อมูลไม่ครบถ้วน หรือไม่มีการระบุรหัสผ่านใหม่';
    }
}

// ปิดการเชื่อมต่อฐานข้อมูล
if (isset($connect)) mysqli_close($connect);

// ส่งผลลัพธ์ออกไป
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>