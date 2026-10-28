<?php
// รอบ PM ของเครื่องจักรที่สแกน QR (machine_history_view.php) — ปุ่ม "ทำ PM" เฉพาะ ช่าง (ตำแหน่งขึ้นต้นด้วย "ช่าง") / super admin
//  GET ?asset_code=รหัสเครื่อง&ag_id=หน่วยงานบน QR
//  => can: แสดงปุ่มได้ไหม · due: รอบที่ถึงกำหนดแล้ว (วันนี้ + เลยกำหนด ยังไม่ทำ) · next: รอบถัดไป
//  ทำ PM ได้เฉพาะตามรอบแผน (กำหนดการของแผนที่เปิดใช้) — ไม่มีรอบถึงกำหนด = ไม่มีปุ่ม
@session_start();
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');
include 'config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');

function mp_out($a) { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

$me    = (string)($_SESSION['sess_user_id_es'] ?? '');
$level = (string)($_SESSION['sess_user_level_es'] ?? '');
$ag    = (string)($_SESSION['sess_user_agency'] ?? '');
if ($me === '') mp_out(['success' => true, 'can' => false, 'reason' => 'login']);   // ไม่ได้ล็อกอิน (คนทั่วไปสแกนแจ้งซ่อม)

// ตำแหน่งอ่านจากฐานข้อมูล (แก้ตำแหน่งแล้วมีผลทันที) — เกณฑ์เดียวกับ pm.php
$meEsc = mysqli_real_escape_string($connect, $me);
$r = mysqli_query($connect, "SELECT user_position FROM tb_user WHERE user_id = '$meEsc' LIMIT 1");
$pos = ($r && ($row = mysqli_fetch_assoc($r))) ? trim((string)$row['user_position']) : '';
if ($level !== 'super_admin' && mb_strpos($pos, 'ช่าง') !== 0) mp_out(['success' => true, 'can' => false, 'reason' => 'role']);

$code = trim((string)($_GET['asset_code'] ?? ''));
$qrAg = trim((string)($_GET['ag_id'] ?? ''));
if ($code === '') mp_out(['success' => false, 'error' => 'ไม่พบรหัสเครื่องจักร']);
$codeEsc = mysqli_real_escape_string($connect, $code);
$agWhere = $qrAg !== '' ? " AND a.ass_ag_id = '" . mysqli_real_escape_string($connect, $qrAg) . "'" : '';
$r = mysqli_query($connect, "SELECT a.ass_id, a.ass_ag_id FROM tb_ass_list a WHERE a.ass_code = '$codeEsc' $agWhere LIMIT 1");
$m = $r ? mysqli_fetch_assoc($r) : null;
if (!$m) mp_out(['success' => true, 'can' => false, 'reason' => 'machine']);

// เครื่องของหน่วยงานอื่น => ต้องเข้าสู่ระบบ/เปลี่ยนเป็นหน่วยงานนั้นก่อน (ใบงานบันทึกตามหน่วยงานที่ล็อกอิน)
if ((string)$m['ass_ag_id'] !== $ag) {
    $r = mysqli_query($connect, "SELECT ag_contract FROM tb_agency WHERE ag_id = '" . mysqli_real_escape_string($connect, (string)$m['ass_ag_id']) . "'");
    $agName = ($r && ($x = mysqli_fetch_assoc($r))) ? $x['ag_contract'] : '';
    mp_out(['success' => true, 'can' => false, 'reason' => 'agency', 'agency_name' => $agName]);
}

$mid = mysqli_real_escape_string($connect, (string)$m['ass_id']);
$agEsc = mysqli_real_escape_string($connect, $ag);
$today = date('Y-m-d');
$base = "FROM pm_plan_events e
         JOIN pm_plans p ON p.id = e.plan_id AND p.status = 1
         LEFT JOIN pm_checksheets c ON c.id = e.checksheet_id
         WHERE e.ag_id = '$agEsc' AND e.machine_id = '$mid' AND e.status = 0";
$due = [];
$r = mysqli_query($connect, "SELECT e.id, e.event_date, c.name AS checksheet $base AND e.event_date <= '$today' ORDER BY e.event_date, e.id LIMIT 20");
if ($r) while ($x = mysqli_fetch_assoc($r)) {
    $due[] = ['event_id' => (int)$x['id'], 'event_date' => $x['event_date'], 'checksheet' => (string)$x['checksheet'], 'overdue' => $x['event_date'] < $today];
}
$r = mysqli_query($connect, "SELECT e.event_date, c.name AS checksheet $base AND e.event_date > '$today' ORDER BY e.event_date, e.id LIMIT 1");
$next = ($r && ($x = mysqli_fetch_assoc($r))) ? ['event_date' => $x['event_date'], 'checksheet' => (string)$x['checksheet']] : null;

mp_out(['success' => true, 'can' => true, 'today' => $today, 'due' => $due, 'next' => $next]);
