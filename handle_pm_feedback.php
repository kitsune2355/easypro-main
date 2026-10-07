<?php
//handle_pm_feedback.php
@session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config_ctrl/connect.php'; 

// ตรวจสอบการเชื่อมต่อ DB
if (mysqli_connect_errno()) {
    http_response_code(500); 
    echo json_encode(['success' => false, 'error' => 'Database connection failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type"); 

date_default_timezone_set('Asia/Bangkok'); 

// ดึงชื่อผู้ใช้งานจาก Session (ปรับชื่อตัวแปร Session ตามระบบจริงของคุณ เช่น $_SESSION['UName'])
$now = date('Y-m-d H:i:s');

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : null); 

if (!$action) {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    if (isset($input['action'])) { $action = $input['action']; }
}

if (!$action) {
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุ action'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'get_eval_topics') {
    $ag_id = isset($_POST['ag_id']) ? intval($_POST['ag_id']) : (isset($_GET['ag_id']) ? intval($_GET['ag_id']) : 0);
    $sql = "SELECT topic_title FROM pm_feedback WHERE status = '1' AND ag_id = $ag_id ORDER BY order_no ASC";
    $result = mysqli_query($connect, $sql);
    
    $topics = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $topics[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $topics], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Action: get_all ---
if ($action === 'get_all') {
    $ag_id = isset($_GET['ag_id']) ? intval($_GET['ag_id']) : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    $searchParam = "%{$search}%";
    $stmtData = $connect->prepare("SELECT id, ag_id, topic_title, order_no, status FROM pm_feedback WHERE ag_id = ? AND topic_title LIKE ? ORDER BY order_no ASC");
    $stmtData->bind_param("is", $ag_id, $searchParam);
    $stmtData->execute();
    $result = $stmtData->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['status'] = ($row['status'] == 1) ? '0' : '1';
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Action: save (เพิ่ม created_by / updated_by) ---
if ($action === 'save') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    $ag_id = isset($input['ag_id']) ? intval($input['ag_id']) : 0;
    $data = isset($input['data']) ? $input['data'] : [];
    $current_user = isset($input['user_sin']) ? $input['user_sin'] : '';

    mysqli_begin_transaction($connect);

    try {
        foreach ($data as $row) {
            $id = intval($row['id']);
            $topic_title = isset($row['topic_title']) ? trim($row['topic_title']) : '';
            $order_no = isset($row['order_no']) ? intval($row['order_no']) : 0;
            $status = (isset($row['status']) && $row['status'] === '0') ? 1 : 0;

            if ($id == 0) {
                // INSERT: เก็บทั้ง created และ updated
                $stmt = $connect->prepare("INSERT INTO pm_feedback (ag_id, topic_title, order_no, status, created_by, created_at, updated_by, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isiiisss", $ag_id, $topic_title, $order_no, $status, $current_user, $now, $current_user, $now);
                $stmt->execute();
            } else {
                // UPDATE: เก็บเฉพาะ updated
                $stmt = $connect->prepare("UPDATE pm_feedback SET topic_title = ?, order_no = ?, status = ?, updated_by = ?, updated_at = ? WHERE id = ?");
                $stmt->bind_param("siissi", $topic_title, $order_no, $status, $current_user, $now, $id);
                $stmt->execute();
            }
        }

        mysqli_commit($connect);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        mysqli_rollback($connect);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}