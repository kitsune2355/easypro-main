<?php
// handle_stock_maintenance.php (PHP 5.4+)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

date_default_timezone_set('Asia/Bangkok');

include 'config_ctrl/connect.php';

if (!isset($connect) || !$connect || mysqli_connect_errno()) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'Database connection failed: ' . mysqli_connect_error()
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_set_charset($connect, "utf8");

// ===== HELPERS =====
function read_json_body() {
    $raw = file_get_contents('php://input');
    if (!$raw) return array();
    $data = json_decode($raw, true);
    return is_array($data) ? $data : array();
}

function json_fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(array('success' => false, 'message' => $msg), JSON_UNESCAPED_UNICODE);
    exit;
}

// bind_param แบบ dynamic (PHP 5.4 friendly)
function bind_dynamic($stmt, $types, $params) {
    if ($types === '' || empty($params)) return;

    $bind = array();
    $bind[] = $types;

    for ($i = 0; $i < count($params); $i++) {
        $k = 'p' . $i;
        $$k = $params[$i];
        $bind[] = &$$k;
    }

    call_user_func_array(array($stmt, 'bind_param'), $bind);
}

// ===== INPUTS =====
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$body   = read_json_body();

if ($action === '') {
    json_fail('ไม่ระบุการดำเนินการ (action)');
}

// ----------------------------------------------------
// 1) get_wh_list : ดึงรายการคลังจาก tb_wh_stock ตาม ag_id
// GET: ?action=get_wh_list&ag_id=2
// ----------------------------------------------------
if ($action === 'get_wh_list') {

    $ag_id = isset($_GET['ag_id']) ? (int)$_GET['ag_id'] : 0;
    if ($ag_id <= 0) {
        echo json_encode(array('success'=>false,'message'=>'missing ag_id'), JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $connect->prepare("SELECT id, wh_name FROM tb_wh_stock WHERE ag_id = ? ORDER BY id DESC");
    if (!$stmt) {
        echo json_encode(array('success'=>false,'message'=>'prepare failed: '.$connect->error), JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt->bind_param("i", $ag_id);

    if (!$stmt->execute()) {
        echo json_encode(array('success'=>false,'message'=>'execute failed: '.$stmt->error), JSON_UNESCAPED_UNICODE);
        $stmt->close();
        exit;
    }

    $stmt->store_result();
    $stmt->bind_result($id, $wh_name);

    $data = array();
    while ($stmt->fetch()) {
        $data[] = array(
            'id' => $id,
            'wh_name' => $wh_name
        );
    }

    $stmt->close();

    echo json_encode(array('success'=>true,'data'=>$data), JSON_UNESCAPED_UNICODE);
    exit;
}

// ----------------------------------------------------
// 2) get_all : ดึงรายการสินค้า + stock ตาม ag_id
// optional: wh_id, q, job_type
// GET: ?action=get_all&ag_id=2&wh_id=1&q=หลอดไฟ&job_type=3
// ----------------------------------------------------
elseif ($action === 'get_all') {

    $ag_id = isset($_GET['ag_id']) ? (int)$_GET['ag_id'] : 0;
    $wh_id = isset($_GET['wh_id']) ? (int)$_GET['wh_id'] : 0;
    $q     = isset($_GET['q']) ? trim($_GET['q']) : '';

    if ($ag_id <= 0) {
        echo json_encode(array('success' => false, 'message' => 'missing ag_id'), JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = "SELECT 
                p.*,
                ps.qty      AS pd_qty,
                ps.qty_all  AS pd_qty_all,
                ps.qty_min  AS pd_qty_min,
                ps.wh_id,
                ps.id       AS ps_id,
                ps.ps_status,
                w.wh_name
            FROM tb_repair_product p
            INNER JOIN product_stocks ps ON p.pd_id = ps.pd_id
            INNER JOIN tb_wh_stock w ON ps.wh_id = w.id
            WHERE p.ag_id = ? ";

    $types  = "i";
    $params = array($ag_id);

    if ($wh_id > 0) {
        $sql .= " AND ps.wh_id = ?";
        $types .= "i";
        $params[] = $wh_id;
    }

    if ($q !== '') {
        $sql .= " AND (
                    p.pd_id LIKE ?
                    OR p.pd_gen_code LIKE ?
                    OR p.pd_model LIKE ?
                    OR p.pd_details LIKE ?
                    OR p.pd_details_head LIKE ?
                 )";

        $like = "%".$q."%";
        $types .= "sssss";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= " ORDER BY p.pd_id DESC";

    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        echo json_encode(array('success' => false, 'message' => 'Prepare failed: ' . $connect->error), JSON_UNESCAPED_UNICODE);
        exit;
    }

    bind_dynamic($stmt, $types, $params);

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        echo json_encode(array('success' => false, 'message' => 'Execute failed: ' . $err), JSON_UNESCAPED_UNICODE);
        exit;
    }

    $rs = $stmt->get_result();
    if (!$rs) {
        $stmt->close();
        echo json_encode(array('success' => false, 'message' => 'get_result() not available (mysqlnd required)'), JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = array();
    while ($row = $rs->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();

    echo json_encode(array('success' => true, 'data' => $data), JSON_UNESCAPED_UNICODE);
    exit;
}

else {
    json_fail('action ไม่ถูกต้อง');
}