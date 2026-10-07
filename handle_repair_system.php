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

if ($action === 'get_all') {
    $ag_id = $_GET['ag_id'] ?? ''; 

    $sql = "SELECT * FROM tb_repair_system WHERE rps_status = '0' AND rps_ag_id = $ag_id ORDER BY rps_id DESC";
    $result = mysqli_query($connect, $sql);

    if ($result) {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        $response['success'] = true;
        $response['data'] = $data;
        $response['message'] = 'ดึงข้อมูลสำเร็จ';
    } else {
        http_response_code(500);
        $response['message'] = 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . mysqli_error($connect);
    }
}

if (isset($connect)) mysqli_close($connect);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);