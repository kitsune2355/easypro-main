<?php
@session_start();
header('Content-Type: application/json; charset=utf-8');

/*
  handle_job_urgency.php
  ใช้แค่เปิด/ปิดหัวข้อ "ระดับความเร่งด่วน (Urgency Level)"

  GET  handle_job_urgency.php?action=get_setting&ag_id=xxx
  POST handle_job_urgency.php?action=save
       body JSON: { ag_id, user_id, urgency_enabled }
*/

include "config_ctrl/connect.php";

function json_out($arr) {
    echo json_encode($arr);
    exit;
}

function get_db_conn() {
    global $conn, $con, $connect, $mysqli, $link, $db;

    if (isset($conn) && $conn instanceof mysqli) return $conn;
    if (isset($con) && $con instanceof mysqli) return $con;
    if (isset($connect) && $connect instanceof mysqli) return $connect;
    if (isset($mysqli) && $mysqli instanceof mysqli) return $mysqli;
    if (isset($link) && $link instanceof mysqli) return $link;
    if (isset($db) && $db instanceof mysqli) return $db;

    json_out(array(
        'success' => false,
        'error' => 'ไม่พบตัวแปรเชื่อมต่อฐานข้อมูลใน config_ctrl/connect.php'
    ));
}

function get_request_ag_id() {
    global $sess_user_agency_es;

    if (isset($_GET['ag_id']) && trim($_GET['ag_id']) !== '') return trim($_GET['ag_id']);
    if (isset($_POST['ag_id']) && trim($_POST['ag_id']) !== '') return trim($_POST['ag_id']);
    if (isset($sess_user_agency_es) && trim($sess_user_agency_es) !== '') return trim($sess_user_agency_es);

    return '';
}

$mysqli = get_db_conn();
@$mysqli->set_charset('utf8');

$action = isset($_GET['action']) ? $_GET['action'] : '';
if ($action == '' && isset($_POST['action'])) $action = $_POST['action'];

if ($action === 'get_setting') {
    $ag_id = get_request_ag_id();

    // ถ้าไม่ส่ง ag_id มา ให้เปิดไว้ก่อน เพื่อไม่กระทบหน้าแจ้งซ่อม
    if ($ag_id === '') {
        json_out(array(
            'success' => true,
            'data' => array(
                'ag_id' => '',
                'urgency_enabled' => 1
            )
        ));
    }

    $sql = "SELECT urgency_enabled FROM tb_job_urgency_setting WHERE ag_id = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) json_out(array('success' => false, 'error' => $mysqli->error));

    $stmt->bind_param('s', $ag_id);
    if (!$stmt->execute()) json_out(array('success' => false, 'error' => $stmt->error));

    $stmt->bind_result($urgency_enabled);
    if ($stmt->fetch()) {
        json_out(array(
            'success' => true,
            'data' => array(
                'ag_id' => $ag_id,
                'urgency_enabled' => (int)$urgency_enabled
            )
        ));
    }

    // ยังไม่เคยตั้งค่า = เปิดไว้ก่อน
    json_out(array(
        'success' => true,
        'data' => array(
            'ag_id' => $ag_id,
            'urgency_enabled' => 1
        )
    ));
}

if ($action === 'save') {
    // save ต้องผ่าน session
    include "config_ctrl/checksession.php";

    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (!is_array($body)) $body = array();

    $ag_id = isset($body['ag_id']) ? trim($body['ag_id']) : get_request_ag_id();
    $user_id = isset($body['user_id']) ? trim($body['user_id']) : '';
    $enabled = isset($body['urgency_enabled']) ? (int)$body['urgency_enabled'] : 1;
    $enabled = ($enabled === 1) ? 1 : 0;

    if ($ag_id === '') {
        json_out(array('success' => false, 'error' => 'ไม่พบ ag_id'));
    }

    $sql = "INSERT INTO tb_job_urgency_setting
              (ag_id, urgency_enabled, updated_by, created_at, updated_at)
            VALUES
              (?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
              urgency_enabled = VALUES(urgency_enabled),
              updated_by = VALUES(updated_by),
              updated_at = NOW()";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) json_out(array('success' => false, 'error' => $mysqli->error));

    $stmt->bind_param('sis', $ag_id, $enabled, $user_id);
    if (!$stmt->execute()) {
        json_out(array('success' => false, 'error' => $stmt->error));
    }

    json_out(array(
        'success' => true,
        'message' => 'บันทึกข้อมูลเรียบร้อย',
        'data' => array(
            'ag_id' => $ag_id,
            'urgency_enabled' => $enabled
        )
    ));
}

json_out(array('success' => false, 'error' => 'action ไม่ถูกต้อง'));
?>
