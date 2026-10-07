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
    $ag_id = isset($_GET['ag_id']) ? mysqli_real_escape_string($connect, $_GET['ag_id']) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($connect, trim($_GET['date_from'])) : '';
    $date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($connect, trim($_GET['date_to'])) : '';
    $filter_name = isset($_GET['filter_name']) ? mysqli_real_escape_string($connect, trim($_GET['filter_name'])) : '';
    $filter_type = isset($_GET['filter_type']) ? mysqli_real_escape_string($connect, trim($_GET['filter_type'])) : '';
    $filter_handle = isset($_GET['filter_handle']) ? mysqli_real_escape_string($connect, trim($_GET['filter_handle'])) : '';
    $filter_status = isset($_GET['filter_status']) ? mysqli_real_escape_string($connect, trim($_GET['filter_status'])) : '';

    $where_sql = "1=1";
    if ($ag_id !== '') {
        $where_sql .= " AND ag_id = '$ag_id'";
    }
    
    // กรองแบบพิมพ์ข้อความค้นหา (Text Filter)
    if ($date_from !== '' && $date_to !== '') {
        $where_sql .= " AND date BETWEEN '$date_from' AND '$date_to'";
    } elseif ($date_from !== '') {
        $where_sql .= " AND date = '$date_from'";
    }
    if ($filter_name !== '') { $where_sql .= " AND name LIKE '%$filter_name%'"; }

    // กรองแบบเลือกหลายรายการ (Set Filter)
    if ($filter_type !== '') { 
        $types = explode(',', $filter_type);
        $types_str = implode("','", $types);
        $where_sql .= " AND type IN ('$types_str')"; 
    }
    if ($filter_handle !== '') { 
        $handles = explode(',', $filter_handle);
        $handles_str = implode("','", $handles);
        $where_sql .= " AND handle IN ('$handles_str')"; 
    }
    if ($filter_status !== '') { 
        // แปลงค่า boolean (true/false) กลับเป็นตัวเลข 1, 0 สำหรับฐานข้อมูล
        $statuses = explode(',', $filter_status);
        $status_ints = [];
        foreach($statuses as $s) {
            if ($s === 'true' || $s === '1') $status_ints[] = 1;
            if ($s === 'false' || $s === '0') $status_ints[] = 0;
        }
        if (count($status_ints) > 0) {
            $statuses_str = implode(",", $status_ints);
            $where_sql .= " AND status IN ($statuses_str)";
        }
    }

    $count_sql = "SELECT COUNT(*) as total FROM pm_holidays WHERE $where_sql";
    $count_result = mysqli_query($connect, $count_sql);
    $total_rows = 0;
    if ($count_result && $row_count = mysqli_fetch_assoc($count_result)) {
        $total_rows = intval($row_count['total']);
    }
    
    $sql = "SELECT * FROM pm_holidays WHERE $where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $result = mysqli_query($connect, $sql);
    
    $holidays = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $holidays[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'date' => $row['date'],
                'name' => $row['name'],
                'handle' => $row['handle'],
                'status' => (bool)$row['status']
            ];
        }
        $response['success'] = true;
        $response['data'] = $holidays;
        $response['total_rows'] = $total_rows;
    } else {
        $response['error'] = mysqli_error($connect);
    }
}

elseif ($action === 'get_by_id') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id > 0) {
        $sql = "SELECT * FROM pm_holidays WHERE id = $id LIMIT 1";
        $result = mysqli_query($connect, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $response['success'] = true;
            $response['data'] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'date' => $row['date'],
                'name' => $row['name'],
                'handle' => $row['handle'],
                'status' => (bool)$row['status'] // เปลี่ยนเป็น status
            ];
        } else {
            $response['error'] = 'ไม่พบข้อมูล';
        }
    } else {
        $response['error'] = 'ระบุ ID ไม่ถูกต้อง';
    }
}

elseif ($action === 'save') {
    $id = isset($post_data['id']) && $post_data['id'] !== '' ? intval($post_data['id']) : null;
    $ag_id = isset($post_data['ag_id']) ? mysqli_real_escape_string($connect, $post_data['ag_id']) : '';
    $type = isset($post_data['type']) ? mysqli_real_escape_string($connect, $post_data['type']) : '';
    $date = isset($post_data['date']) ? mysqli_real_escape_string($connect, $post_data['date']) : '';
    $name = isset($post_data['name']) ? mysqli_real_escape_string($connect, $post_data['name']) : '';
    $handle = isset($post_data['handle']) ? mysqli_real_escape_string($connect, $post_data['handle']) : '';
    $status = isset($post_data['status']) && $post_data['status'] ? 1 : 0;
    $user_id = isset($post_data['user_id']) ? mysqli_real_escape_string($connect, $post_data['user_id']) : '';

    if ($id) {
        $sql = "UPDATE pm_holidays SET 
                    type = '$type', 
                    date = '$date', 
                    name = '$name', 
                    handle = '$handle', 
                    status = $status,
                    updated_by = '$user_id'
                WHERE id = $id";
        
        if (mysqli_query($connect, $sql)) {
            $response['success'] = true;
            $response['message'] = 'อัปเดตข้อมูลสำเร็จ';
            $response['data'] = ['id' => $id];
        } else {
            $response['error'] = mysqli_error($connect);
        }
    } else {
        $sql = "INSERT INTO pm_holidays (ag_id, type, date, name, handle, status, created_by, updated_by) 
                VALUES ('$ag_id', '$type', '$date', '$name', '$handle', $status, '$user_id', '$user_id')";
        
        if (mysqli_query($connect, $sql)) {
            $response['success'] = true;
            $response['message'] = 'บันทึกข้อมูลสำเร็จ';
            $response['data'] = ['id' => mysqli_insert_id($connect)];
        } else {
            $response['error'] = mysqli_error($connect);
        }
    }
}

elseif ($action === 'delete') {
    $id = isset($post_data['id']) ? intval($post_data['id']) : 0;
    
    if ($id > 0) {
        $sql = "DELETE FROM pm_holidays WHERE id = $id";
        if (mysqli_query($connect, $sql)) {
            $response['success'] = true;
            $response['message'] = 'ลบข้อมูลสำเร็จ';
        } else {
            $response['error'] = mysqli_error($connect);
        }
    } else {
        $response['error'] = 'รหัสอ้างอิงไม่ถูกต้อง';
    }
}

// เพิ่มส่วนนี้ก่อนการปิดการเชื่อมต่อฐานข้อมูลใน handle_pm_holiday.php
elseif ($action === 'get_calendar_events') {
    $ag_id = isset($_GET['ag_id']) ? mysqli_real_escape_string($connect, $_GET['ag_id']) : '';
    
    // ดึงเฉพาะวันหยุดที่สถานะเป็น Active (status = 1)
    $where_sql = "status = 1";
    if ($ag_id !== '') {
        $where_sql .= " AND ag_id = '$ag_id'";
    }

    $sql = "SELECT id, name, type, date FROM pm_holidays WHERE $where_sql";
    $result = mysqli_query($connect, $sql);
    
    $events = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = [
                'id' => (string)$row['id'],
                'name' => $row['name'],
                'type' => $row['type'],
                'date' => $row['date']
            ];
        }
        $response['success'] = true;
        $response['data'] = $events;
    } else {
        $response['error'] = mysqli_error($connect);
    }
}

// ปิดการเชื่อมต่อฐานข้อมูล
if (isset($connect)) mysqli_close($connect);

// ส่งผลลัพธ์ออกไป
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>