<?php
// แสดงข้อผิดพลาด PHP เพื่อการตรวจสอบ
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

@session_start();
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

// =========================================
// หน้าตั้งค่า "คลังสินค้า / ตึก" (settings_warehouses.php) — ใช้หน่วยงานจาก session เท่านั้น
// =========================================
elseif ($action === 'list_setting') {
    $ag_id = (int)($_SESSION['sess_user_agency'] ?? 0);
    $search = trim($_GET['search'] ?? '');
    $limit = max(1, min(100000, (int)($_GET['limit'] ?? 20)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $like = '%' . $search . '%';

    $stmt = $connect->prepare("SELECT COUNT(*) c FROM tb_wh_stock WHERE ag_id = ? AND wh_name LIKE ?");
    $stmt->bind_param("is", $ag_id, $like);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    $stmt = $connect->prepare("SELECT w.id, w.wh_name, w.status,
                                      (SELECT COUNT(*) FROM product_stocks ps WHERE ps.wh_id = w.id) AS total_items
                               FROM tb_wh_stock w
                               WHERE w.ag_id = ? AND w.wh_name LIKE ?
                               ORDER BY w.id ASC
                               LIMIT ? OFFSET ?");
    $stmt->bind_param("isii", $ag_id, $like, $limit, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $row['id'] = (int)$row['id'];
        $row['total_items'] = (int)$row['total_items'];
        $row['status'] = (string)($row['status'] ?? '0');
        $data[] = $row;
    }
    $stmt->close();
    echo json_encode(['success' => true, 'data' => $data, 'total' => $total], JSON_UNESCAPED_UNICODE);
    exit;
}

elseif ($action === 'save_setting') {
    $ag_id = (int)($_SESSION['sess_user_agency'] ?? 0);
    $user_id = (string)($_SESSION['sess_user_id_es'] ?? '');
    $rows = $input['data'] ?? [];
    if ($ag_id <= 0) { echo json_encode(['success' => false, 'error' => 'ไม่พบหน่วยงานของผู้ใช้'], JSON_UNESCAPED_UNICODE); exit; }
    if (!is_array($rows) || !count($rows)) { echo json_encode(['success' => false, 'error' => 'ไม่มีข้อมูลที่ต้องบันทึก'], JSON_UNESCAPED_UNICODE); exit; }

    // ชื่อซ้ำกันเองในชุดที่ส่งมา
    $seen = [];
    foreach ($rows as $r) {
        $name = trim((string)($r['wh_name'] ?? ''));
        if ($name === '') { echo json_encode(['success' => false, 'error' => 'กรุณาระบุชื่อคลังสินค้า / ตึก ให้ครบทุกแถว'], JSON_UNESCAPED_UNICODE); exit; }
        $k = mb_strtolower($name);
        if (isset($seen[$k])) { echo json_encode(['success' => false, 'error' => "ชื่อ \"$name\" ซ้ำกันในรายการที่บันทึก"], JSON_UNESCAPED_UNICODE); exit; }
        $seen[$k] = true;
    }

    mysqli_begin_transaction($connect);
    try {
        $dup = $connect->prepare("SELECT id FROM tb_wh_stock WHERE ag_id = ? AND wh_name = ? AND id <> ? LIMIT 1");
        $ins = $connect->prepare("INSERT INTO tb_wh_stock (ag_id, wh_name, status, created_by) VALUES (?, ?, ?, ?)");
        $upd = $connect->prepare("UPDATE tb_wh_stock SET wh_name = ?, status = ?, updated_by = ? WHERE id = ? AND ag_id = ?");
        foreach ($rows as $r) {
            $id = (int)($r['id'] ?? 0);
            $name = trim((string)$r['wh_name']);
            $status = ((string)($r['status'] ?? '0')) === '1' ? '1' : '0';
            $dup->bind_param("isi", $ag_id, $name, $id);
            $dup->execute();
            if ($dup->get_result()->fetch_assoc()) throw new Exception("ชื่อคลัง \"$name\" มีอยู่แล้วในหน่วยงานนี้ กรุณาใช้ชื่ออื่น");
            if ($id > 0) {
                $upd->bind_param("sssii", $name, $status, $user_id, $id, $ag_id);
                if (!$upd->execute()) throw new Exception('แก้ไขไม่สำเร็จ: ' . $upd->error);
            } else {
                $ins->bind_param("isss", $ag_id, $name, $status, $user_id);
                if (!$ins->execute()) throw new Exception('เพิ่มไม่สำเร็จ: ' . $ins->error);
            }
        }
        mysqli_commit($connect);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        mysqli_rollback($connect);
        echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
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