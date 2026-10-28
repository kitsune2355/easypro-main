<?php
// ตั้งค่าการบันทึกผล PM (ต่อหน่วยงานที่ล็อกอินอยู่) — settings_pm_record.php
//  GET  ?action=get
//  POST ?action=save  (sign_required=0|1, date_mode=today|pick, approve_required=0|1) — admin / super_admin
@session_start();
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');
include 'config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');
require_once __DIR__ . '/config_ctrl/pm_record_setting.php';
require_once __DIR__ . '/config_ctrl/pm_approve.php';

function pmrs_out($a, $code = 200) { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

$me    = (string)($_SESSION['sess_user_id_es'] ?? '');
$level = (string)($_SESSION['sess_user_level_es'] ?? '');
$ag    = (int)($_SESSION['sess_user_agency'] ?? 0);
if ($me === '' || $ag <= 0) pmrs_out(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบใหม่'], 401);

$action = $_GET['action'] ?? '';
$canEdit = in_array($level, ['admin', 'super_admin'], true);

if ($action === 'get') {
    pmrs_ensure($connect);
    pmrs_out(['success' => true, 'setting' => pmrs_get($connect, $ag), 'can_edit' => $canEdit,
        'pending_approval' => pmap_pending_count($connect, $ag), 'approver_count' => pmap_approver_count($connect, $ag)]);
}

if (!$canEdit) pmrs_out(['success' => false, 'error' => 'เฉพาะผู้ดูแลระบบ'], 403);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') pmrs_out(['success' => false, 'error' => 'method not allowed'], 405);

if ($action === 'save') {
    pmrs_ensure($connect);
    $approve = !empty($_POST['approve_required']) && $_POST['approve_required'] !== '0' ? 1 : 0;
    if ($approve) pmap_ensure($connect);
    $sign = !empty($_POST['sign_required']) && $_POST['sign_required'] !== '0' ? 1 : 0;
    $mode = ($_POST['date_mode'] ?? '') === 'pick' ? 'pick' : 'today';
    $now = date('Y-m-d H:i:s');
    $st = mysqli_prepare($connect, "INSERT INTO tb_pm_record_setting (ag_id, sign_required, date_mode, approve_required, created_at, created_by, updated_at, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE sign_required = VALUES(sign_required), date_mode = VALUES(date_mode), approve_required = VALUES(approve_required), updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)");
    mysqli_stmt_bind_param($st, 'iisissss', $ag, $sign, $mode, $approve, $now, $me, $now, $me);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    if (!$ok) pmrs_out(['success' => false, 'error' => 'บันทึกไม่สำเร็จ'], 500);
    pmrs_out(['success' => true, 'setting' => ['sign_required' => $sign, 'date_mode' => $mode, 'approve_required' => $approve]]);
}

pmrs_out(['success' => false, 'error' => 'unknown action'], 400);
