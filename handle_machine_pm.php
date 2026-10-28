<?php
// รอบ PM ของเครื่องจักรที่สแกน QR (machine_history_view.php) — ปุ่ม "ทำ PM" เฉพาะ ช่าง (ตำแหน่งขึ้นต้นด้วย "ช่าง") / super admin
//  GET ?asset_code=รหัสเครื่อง&ag_id=หน่วยงานบน QR
//  => can: แสดงปุ่มได้ไหม · due: รอบที่ถึงกำหนดแล้ว (วันนี้ + เลยกำหนด ยังไม่ทำ) · next: รอบถัดไป
//  GET ?action=history&asset_code=&ag_id=  ประวัติแผน PM ของเครื่อง (สาธารณะ — ดูด้านล่าง)
//  ทำ PM ได้เฉพาะตามรอบแผน (กำหนดการของแผนที่เปิดใช้) — ไม่มีรอบถึงกำหนด = ไม่มีปุ่ม
@session_start();
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');
include 'config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');

function mp_out($a) { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

// =========================================
// ประวัติแผน PM ของเครื่อง (แท็บ "แผน PM" — ดูได้ทุกคนเหมือนประวัติแจ้งซ่อม)
//  GET ?action=history&asset_code=&ag_id=&page=&per_page=
//  => upcoming: รอบถัดไป (ไม่เกิน 3) · items: รอบที่ถึงกำหนดแล้ว ใหม่สุดก่อน · stats: ทำแล้ว / ทั้งหมด
// =========================================
if (($_GET['action'] ?? '') === 'history') {
    require_once __DIR__ . '/config_ctrl/pm_approve.php';
    $code = trim((string)($_GET['asset_code'] ?? ''));
    $qrAg = trim((string)($_GET['ag_id'] ?? ''));
    if ($code === '') mp_out(['success' => false, 'error' => 'ไม่พบรหัสเครื่องจักร']);
    $codeEsc = mysqli_real_escape_string($connect, $code);
    $agWhere = $qrAg !== '' ? " AND a.ass_ag_id = '" . mysqli_real_escape_string($connect, $qrAg) . "'" : '';
    $r = mysqli_query($connect, "SELECT a.ass_id, a.ass_ag_id FROM tb_ass_list a WHERE a.ass_code = '$codeEsc' $agWhere LIMIT 1");
    $m = $r ? mysqli_fetch_assoc($r) : null;
    if (!$m) mp_out(['success' => false, 'error' => 'ไม่พบเครื่องจักร']);

    $mid = mysqli_real_escape_string($connect, (string)$m['ass_id']);
    $agEsc = mysqli_real_escape_string($connect, (string)$m['ass_ag_id']);
    $today = date('Y-m-d');
    $per = min(50, max(5, (int)($_GET['per_page'] ?? 15)));
    $page = max(1, (int)($_GET['page'] ?? 1));
    $off = ($page - 1) * $per;
    $ap = pmap_has_col($connect);
    $base = "FROM pm_plan_events e LEFT JOIN pm_checksheets c ON c.id = e.checksheet_id WHERE e.ag_id = '$agEsc' AND e.machine_id = '$mid'";

    $st = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(*) AS total, SUM(e.status = 1) AS done,
            SUM(e.status = 0 AND e.event_date < '$today') AS missed, SUM(" . pmap_wait_sql($connect, 'e') . ") AS waiting
        $base AND e.event_date <= '$today'"));
    $upcoming = [];
    $r = mysqli_query($connect, "SELECT e.event_date, c.name AS checksheet $base AND e.status = 0 AND e.event_date > '$today' ORDER BY e.event_date, e.id LIMIT 3");
    if ($r) while ($x = mysqli_fetch_assoc($r)) $upcoming[] = $x;
    // รอบที่ถึงกำหนดแล้วยังไม่ทำ (วันนี้ + เลยกำหนด) ของแผนที่เปิดใช้ — แท็บ งาน PM ตามรอบแผน (คนทั่วไปดูอย่างเดียว)
    $due = [];
    $r = mysqli_query($connect, "SELECT e.event_date, c.name AS checksheet FROM pm_plan_events e JOIN pm_plans p ON p.id = e.plan_id AND p.status = 1
        LEFT JOIN pm_checksheets c ON c.id = e.checksheet_id WHERE e.ag_id = '$agEsc' AND e.machine_id = '$mid' AND e.status = 0 AND e.event_date <= '$today' ORDER BY e.event_date, e.id LIMIT 20");
    if ($r) while ($x = mysqli_fetch_assoc($r)) $due[] = ['event_date' => $x['event_date'], 'checksheet' => $x['checksheet'], 'overdue' => $x['event_date'] < $today];

    $items = [];
    $r = mysqli_query($connect, "SELECT e.id, e.event_date, e.status, " . ($ap ? 'e.approval_status, e.approved_at, ap.approver_name' : 'NULL AS approval_status, NULL AS approved_at, NULL AS approver_name') . ",
            c.name AS checksheet, wr.doc_no, wr.actual_date, wr.inspector_name
        FROM pm_plan_events e
        LEFT JOIN pm_checksheets c ON c.id = e.checksheet_id
        LEFT JOIN pm_work_records wr ON wr.id = e.work_record_id
        " . ($ap ? 'LEFT JOIN pm_work_approvals ap ON ap.id = e.approval_id' : '') . "
        WHERE e.ag_id = '$agEsc' AND e.machine_id = '$mid' AND e.event_date <= '$today'
        ORDER BY e.event_date DESC, e.id DESC LIMIT " . ($per + 1) . " OFFSET $off");
    if ($r) while ($x = mysqli_fetch_assoc($r)) {
        // done = ทำแล้ว · approved = ตรวจรับแล้ว · waiting = รอตรวจรับ · today = ถึงกำหนดวันนี้ · missed = เลยกำหนด ยังไม่ทำ
        if ((int)$x['status'] === 1) $state = $x['approval_status'] === null ? 'done' : ((int)$x['approval_status'] === 1 ? 'approved' : 'waiting');
        else $state = $x['event_date'] < $today ? 'missed' : 'today';
        $items[] = ['event_id' => (int)$x['id'], 'event_date' => $x['event_date'], 'checksheet' => (string)$x['checksheet'], 'state' => $state,
            'doc_no' => $x['doc_no'], 'actual_date' => $x['actual_date'], 'inspector' => $x['inspector_name'],
            'approver' => $state === 'approved' ? $x['approver_name'] : null, 'approved_at' => $state === 'approved' ? $x['approved_at'] : null];
    }
    $more = count($items) > $per;
    if ($more) array_pop($items);
    mp_out(['success' => true, 'today' => $today, 'logged_in' => !empty($_SESSION['sess_user_id_es']),
        'stats' => ['total' => (int)$st['total'], 'done' => (int)$st['done'], 'missed' => (int)$st['missed'], 'waiting' => (int)$st['waiting']],
        'upcoming' => $upcoming, 'due' => $due, 'items' => $items, 'has_more' => $more]);
}

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
