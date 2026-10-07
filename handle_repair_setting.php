<?php
@session_start();
header('Content-Type: application/json; charset=utf-8');

/*
  handle_repair_setting.php
  API กลางสำหรับ "ตั้งค่าอื่น ๆ" ของระบบแจ้งซ่อม

  GET  handle_repair_setting.php?action=get_all&ag_id=xxx
  GET  handle_repair_setting.php?action=get_setting&ag_id=xxx&key=show_urgency_level
  POST handle_repair_setting.php?action=save
       body JSON: { ag_id, user_id, settings: { show_urgency_level: "1" } }
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

function get_default_settings() {
    return array(
        // 1 = แสดงหัวข้อระดับความเร่งด่วน, 0 = ซ่อนหัวข้อระดับความเร่งด่วน
        'show_urgency_level' => '1'
    );
}

function allow_setting_key($key) {
    $defaults = get_default_settings();
    return isset($defaults[$key]);
}

$mysqli = get_db_conn();
@$mysqli->set_charset('utf8');

$action = isset($_GET['action']) ? $_GET['action'] : '';
if ($action == '' && isset($_POST['action'])) $action = $_POST['action'];

if ($action === 'get_all') {
    $ag_id = get_request_ag_id();
    $settings = get_default_settings();

    // ถ้าไม่มี ag_id ให้คืนค่า default ก่อน เพื่อไม่ให้หน้าใช้งานพัง
    if ($ag_id === '') {
        json_out(array(
            'success' => true,
            'data' => array(
                'ag_id' => '',
                'settings' => $settings
            )
        ));
    }

    $sql = "SELECT setting_key, setting_value FROM tb_repair_setting WHERE ag_id = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) json_out(array('success' => false, 'error' => $mysqli->error));

    $stmt->bind_param('s', $ag_id);
    if (!$stmt->execute()) json_out(array('success' => false, 'error' => $stmt->error));

    $stmt->bind_result($setting_key, $setting_value);
    while ($stmt->fetch()) {
        if (allow_setting_key($setting_key)) {
            $settings[$setting_key] = (string)$setting_value;
        }
    }

    json_out(array(
        'success' => true,
        'data' => array(
            'ag_id' => $ag_id,
            'settings' => $settings
        )
    ));
}

if ($action === 'get_setting') {
    $ag_id = get_request_ag_id();
    $key = isset($_GET['key']) ? trim($_GET['key']) : '';

    if (!allow_setting_key($key)) {
        json_out(array('success' => false, 'error' => 'setting_key ไม่ถูกต้อง'));
    }

    $defaults = get_default_settings();
    $value = $defaults[$key];

    if ($ag_id !== '') {
        $sql = "SELECT setting_value FROM tb_repair_setting WHERE ag_id = ? AND setting_key = ? LIMIT 1";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) json_out(array('success' => false, 'error' => $mysqli->error));

        $stmt->bind_param('ss', $ag_id, $key);
        if (!$stmt->execute()) json_out(array('success' => false, 'error' => $stmt->error));

        $stmt->bind_result($db_value);
        if ($stmt->fetch()) {
            $value = (string)$db_value;
        }
    }

    json_out(array(
        'success' => true,
        'data' => array(
            'ag_id' => $ag_id,
            'setting_key' => $key,
            'setting_value' => $value
        )
    ));
}

if ($action === 'save') {
    include "config_ctrl/checksession.php";

    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (!is_array($body)) $body = array();

    $ag_id = isset($body['ag_id']) ? trim($body['ag_id']) : get_request_ag_id();
    $user_id = isset($body['user_id']) ? trim($body['user_id']) : '';
    $settings = isset($body['settings']) && is_array($body['settings']) ? $body['settings'] : array();

    if ($ag_id === '') {
        json_out(array('success' => false, 'error' => 'ไม่พบ ag_id'));
    }

    if (count($settings) === 0) {
        json_out(array('success' => false, 'error' => 'ไม่มีข้อมูล setting ที่ต้องบันทึก'));
    }

    $sql = "INSERT INTO tb_repair_setting
              (ag_id, setting_key, setting_value, updated_by, created_at, updated_at)
            VALUES
              (?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
              setting_value = VALUES(setting_value),
              updated_by = VALUES(updated_by),
              updated_at = NOW()";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) json_out(array('success' => false, 'error' => $mysqli->error));

    foreach ($settings as $key => $value) {
        $key = trim($key);
        if (!allow_setting_key($key)) continue;

        // ตอนนี้เป็น toggle: รับเฉพาะ 1/0
        $value = ((string)$value === '1') ? '1' : '0';

        $stmt->bind_param('ssss', $ag_id, $key, $value, $user_id);
        if (!$stmt->execute()) {
            json_out(array('success' => false, 'error' => $stmt->error));
        }
    }

    json_out(array(
        'success' => true,
        'message' => 'บันทึกข้อมูลเรียบร้อย'
    ));
}

json_out(array('success' => false, 'error' => 'action ไม่ถูกต้อง'));
?>
