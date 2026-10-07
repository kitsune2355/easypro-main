<?php
// แสดงข้อผิดพลาด PHP เพื่อการตรวจสอบ
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config_ctrl/connect.php'; 

if (mysqli_connect_errno()) {
    http_response_code(500); 
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type"); 

date_default_timezone_set('Asia/Bangkok'); 

$action = isset($_GET['action']) ? $_GET['action'] : null; 
$response = ['success' => false, 'message' => ''];

// รับข้อมูลจาก JSON Body (สำหรับ POST/PUT)
$input = json_decode(file_get_contents('php://input'), true);

if (!$action) {
    http_response_code(400);
    echo json_encode(['error' => 'ไม่ระบุการดำเนินการ (action)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- การดำเนินการ CRUD ---

if ($action === 'add') {
    $ag_id = $input['ag_id'] ?? null;
    $wh_name = trim($input['wh_name'] ?? ''); 
    $user_id = $input['user_id'] ?? null;
    $status = $input['status'] ?? '0'; // รับค่า status (ถ้าไม่ส่งมาให้ค่าเริ่มต้นเป็น '0')

    if (empty($wh_name)) {
        $response['message'] = "กรุณาระบุชื่อคลังสินค้า";
    } else {
        $check_stmt = $connect->prepare("SELECT id FROM tb_wh_stock WHERE ag_id = ? AND wh_name = ?");
        $check_stmt->bind_param("is", $ag_id, $wh_name);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $response['success'] = false;
            $response['message'] = "ชื่อคลัง '$wh_name' มีอยู่แล้วในหน่วยงานนี้ กรุณาใช้ชื่ออื่น";
        } else {
            // เพิ่มฟิลด์ status ในคำสั่ง INSERT
            $stmt = $connect->prepare("INSERT INTO tb_wh_stock (ag_id, wh_name, status, created_by) VALUES (?, ?, ?, ?)");
            // เปลี่ยนเป็น isss (integer, string, string, string)
            $stmt->bind_param("isss", $ag_id, $wh_name, $status, $user_id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "เพิ่มข้อมูลสำเร็จ";
            } else {
                $response['message'] = "เกิดข้อผิดพลาดในการบันทึก: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

elseif ($action === 'get_all') {
    $ag_id = $_GET['ag_id'] ?? '';
    $sql = "SELECT w.*, 
            (SELECT COUNT(*) FROM product_stocks ps WHERE ps.wh_id = w.id) as total_items
            FROM tb_wh_stock w 
            WHERE w.ag_id = ?";
            
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("i", $ag_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $response['success'] = true;
    $response['data'] = $data;
    echo json_encode($response);
    exit;
}

elseif ($action === 'get_all_setting') {
    $ag_id = $_GET['ag_id'] ?? '';
    $pd_id = $_GET['pd_id'] ?? ''; 

    try {
        // เพิ่ม w.status เข้าไปใน SELECT ด้วย
        // และเพิ่มเงื่อนไข w.status = '0' หากคุณต้องการให้ดึงไปใช้งานเฉพาะคลังที่เปิดใช้อยู่
        $sql = "SELECT w.id, w.wh_name, w.status 
                FROM tb_wh_stock w 
                WHERE w.ag_id = ? AND w.status = '0'";
        
        $params = [$ag_id];
        $types = "i";

        if (!empty($pd_id)) {
            $sql .= " AND NOT EXISTS (
                        SELECT 1 FROM product_stocks ps 
                        WHERE ps.wh_id = w.id AND ps.pd_id = ?
                    )";
            $params[] = $pd_id;
            $types .= "i";
        }

        $stmt = $connect->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $response['success'] = true;
        $response['data'] = $data;
        echo json_encode($response, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

elseif ($action === 'update') {
    $id = $input['id'] ?? null;
    $ag_id = $input['ag_id'] ?? null;
    $wh_name = trim($input['wh_name'] ?? ''); 
    $user_id = $input['user_id'] ?? null;
    $status = $input['status'] ?? '0'; // รับค่า status มาอัปเดต

    if (empty($wh_name)) {
        $response['message'] = "กรุณาระบุชื่อคลังสินค้า";
    } else {
        $check_stmt = $connect->prepare("SELECT id FROM tb_wh_stock WHERE ag_id = ? AND wh_name = ? AND id != ?");
        $check_stmt->bind_param("isi", $ag_id, $wh_name, $id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $response['success'] = false;
            $response['message'] = "ชื่อคลัง '$wh_name' มีอยู่แล้วในหน่วยงานนี้ กรุณาใช้ชื่ออื่น";
        } else {
            // เพิ่ม status เข้าไปในการ UPDATE
            $stmt = $connect->prepare("UPDATE tb_wh_stock SET ag_id = ?, wh_name = ?, status = ?, updated_by = ? WHERE id = ?");
            // เปลี่ยนเป็น isssi (integer, string, string, string, integer)
            $stmt->bind_param("isssi", $ag_id, $wh_name, $status, $user_id, $id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "แก้ไขข้อมูลสำเร็จ";
            } else {
                $response['success'] = false;
                $response['message'] = "เกิดข้อผิดพลาดในการแก้ไข: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

elseif ($action === 'delete') {
    $id = $input['id'] ?? $_GET['id'] ?? null; 

    if (!$id) {
        $response['success'] = false;
        $response['message'] = "ไม่ระบุ ID ที่ต้องการลบ";
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $connect->prepare("DELETE FROM tb_wh_stock WHERE id = ?");
    $stmt->bind_param("s", $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = "ลบข้อมูลสำเร็จ";
        } else {
            $response['success'] = false;
            $response['message'] = "ไม่พบข้อมูลที่ต้องการลบ (ID: $id)";
        }
    } else {
        $response['success'] = false;
        $response['message'] = "เกิดข้อผิดพลาดในการลบ: " . $stmt->error;
    }
    $stmt->close();
}

if (isset($connect)) mysqli_close($connect);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);