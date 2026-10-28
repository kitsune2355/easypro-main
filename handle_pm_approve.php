<?php
// handle_pm_approve.php — ตรวจรับงาน PM โดยหัวหน้าช่าง (config_ctrl/pm_approve.php)
//  ผู้ตรวจรับ: กำหนดใน ตั้งค่าข้อมูล → แผน PM → ผู้ตรวจรับ PM (pm_approvers) · super_admin ตรวจรับได้เสมอ
//  GET  ?action=setting_list | me | groups (status, from, to, cs) | items (date+cs หรือ ids) | get_one (event_id)
//  POST ?action=setting_save {user_ids}   — admin / super_admin
//  POST ?action=approve {event_ids, comment, signature | signature_path}   ตรวจรับ (ทีละไม่เกิน 100 ใบ) — ลายมือชื่อผู้ตรวจรับบังคับ
//       ชุดแรกส่งรูป (data URL) ชุดต่อไปส่ง signature_path ที่ได้กลับมา (ไฟล์เดียวกัน)
//  POST ?action=return  {event_id, reason}     ส่งกลับแก้ไข = ยกเลิกผลบันทึก ช่างบันทึกใหม่ (ใช้เลขที่เอกสารเดิม)
@session_start();
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');
include 'config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');
require_once __DIR__ . '/config_ctrl/pm_record_setting.php';
require_once __DIR__ . '/config_ctrl/pm_work_cancel.php';
require_once __DIR__ . '/config_ctrl/pm_approve.php';

function ap_out($a, $code = 200) { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }
function ap_fail($msg, $code = 200) { ap_out(['success' => false, 'error' => $msg], $code); }
function ap_all($connect, $sql) { $o = []; $r = mysqli_query($connect, $sql); if ($r) while ($x = mysqli_fetch_assoc($r)) $o[] = $x; return $o; }

$ME    = (string)($_SESSION['sess_user_id_es'] ?? '');
$LEVEL = (string)($_SESSION['sess_user_level_es'] ?? '');
$AG    = (int)($_SESSION['sess_user_agency'] ?? 0);
if ($ME === '') ap_fail('กรุณาเข้าสู่ระบบใหม่', 401);
if ($AG <= 0) ap_fail('ไม่พบหน่วยงานของผู้ใช้');
$agEsc = mysqli_real_escape_string($connect, (string)$AG);
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];

pmrs_ensure($connect);
pmap_ensure($connect);
$NO_PERM = 'ใช้ได้เฉพาะ super admin และผู้ตรวจรับ PM ของหน่วยงาน (กำหนดได้ที่ ตั้งค่าข้อมูล → แผน PM → ผู้ตรวจรับ PM)';

function ap_my_name($connect, $me) {
    $meEsc = mysqli_real_escape_string($connect, $me);
    $r = mysqli_query($connect, "SELECT TRIM(CONCAT(user_name, ' ', user_fname)) AS n FROM tb_user WHERE user_id = '$meEsc' LIMIT 1");
    $row = $r ? mysqli_fetch_assoc($r) : null;
    return $row && $row['n'] !== '' ? $row['n'] : $me;
}

// =========================================
// ตั้งค่าผู้ตรวจรับ (settings_pm_approvers.php) — ผู้ดูแลระบบ
// =========================================
if ($action === 'setting_list') {
    if (!in_array($LEVEL, ['super_admin', 'admin'], true)) ap_fail('เฉพาะผู้ดูแลระบบ', 403);
    $rows = ap_all($connect, "SELECT u.user_id, TRIM(CONCAT(u.user_name, ' ', u.user_fname)) AS name, u.user_position, u.user_level,
                (ap.id IS NOT NULL) AS is_approver
            FROM tb_user u
            LEFT JOIN pm_approvers ap ON ap.ag_id = $AG AND ap.user_id = u.user_id
            WHERE u.user_agency = '$agEsc' OR u.user_id IN (SELECT age_user_id FROM tb_agency_employer WHERE age_ag_id = $AG) OR ap.id IS NOT NULL
            ORDER BY is_approver DESC, u.user_name ASC");
    foreach ($rows as &$r) $r['is_approver'] = (int)$r['is_approver'] === 1;
    unset($r);
    ap_out(['success' => true, 'data' => $rows, 'approve_required' => pmrs_get($connect, $AG)['approve_required']]);
}

if ($action === 'setting_save') {
    if (!in_array($LEVEL, ['super_admin', 'admin'], true)) ap_fail('เฉพาะผู้ดูแลระบบ', 403);
    $ids = array_values(array_unique(array_filter(array_map('strval', (array)($input['user_ids'] ?? [])), 'strlen')));
    $now = date('Y-m-d H:i:s');
    mysqli_begin_transaction($connect);
    try {
        if (!mysqli_query($connect, "DELETE FROM pm_approvers WHERE ag_id = $AG")) throw new Exception(mysqli_error($connect));
        $st = mysqli_prepare($connect, "INSERT INTO pm_approvers (ag_id, user_id, created_at, created_by, updated_at, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($ids as $uid) {
            mysqli_stmt_bind_param($st, 'isssss', $AG, $uid, $now, $ME, $now, $ME);
            if (!mysqli_stmt_execute($st)) throw new Exception(mysqli_stmt_error($st));
        }
        mysqli_stmt_close($st);
        mysqli_commit($connect);
        ap_out(['success' => true, 'count' => count($ids)]);
    } catch (Exception $e) {
        mysqli_rollback($connect);
        ap_fail('บันทึกไม่สำเร็จ: ' . $e->getMessage());
    }
}

// =========================================
// ผู้ใช้ปัจจุบัน: เป็นผู้ตรวจรับหรือไม่ + สถานะการเปิดใช้
// =========================================
if ($action === 'me') {
    ap_out(['success' => true, 'is_approver' => pmap_is_approver($connect, $AG, $ME), 'name' => ap_my_name($connect, $ME),
        'required' => pmrs_get($connect, $AG)['approve_required'], 'approver_count' => pmap_approver_count($connect, $AG),
        'pending' => pmap_pending_count($connect, $AG), 'ready' => pmap_has_col($connect)]);
}

// ผลตรวจรับของใบงาน (แสดงในใบงานโหมดประวัติ)
if ($action === 'get_one') {
    $id = (int)($_GET['event_id'] ?? 0);
    if (!pmap_has_col($connect)) ap_out(['success' => true, 'approval_status' => null]);
    $ev = ap_all($connect, "SELECT id, status, approval_status, approved_at FROM pm_plan_events WHERE id = $id AND ag_id = '$agEsc'");
    if (!$ev) ap_fail('ไม่พบใบงาน');
    $ev = $ev[0];
    $hist = ap_all($connect, "SELECT a.action, a.comment, a.approver_name, a.signature_path, a.created_at, a.doc_no FROM pm_work_approvals a WHERE a.plan_event_id = $id ORDER BY a.id DESC LIMIT 20");
    ap_out(['success' => true, 'status' => (int)$ev['status'], 'approval_status' => $ev['approval_status'] === null ? null : (int)$ev['approval_status'],
        'approved_at' => $ev['approved_at'], 'history' => $hist, 'can_approve' => pmap_is_approver($connect, $AG, $ME)]);
}

if (!pmap_is_approver($connect, $AG, $ME)) ap_fail($NO_PERM, 403);
if (!pmap_has_col($connect)) ap_fail('ฐานข้อมูลยังไม่รองรับขั้นตรวจรับ (ไม่มีคอลัมน์ approval_status) — ให้ผู้ดูแลรัน sql/2026-10-28_pm_approve.sql');

// =========================================
// แท็บตรวจรับ PM: กลุ่ม วัน × เช็คชีต
//  status: pending = รอตรวจรับ (ทุกวันที่) · done = ตรวจรับแล้ว (ตามช่วงวันที่) · all
// =========================================
if ($action === 'groups') {
    $status = $_GET['status'] ?? 'pending';
    $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : date('Y-m-01');
    $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : date('Y-m-t');
    $cs = (int)($_GET['cs'] ?? 0);
    $where = "e.ag_id = '$agEsc' AND e.status = 1 AND e.work_record_id IS NOT NULL";
    if ($status === 'pending') $where .= " AND e.approval_status = 0";
    else {
        $where .= " AND e.approval_status IN (0, 1) AND e.event_date BETWEEN '$from' AND '$to'";
        if ($status === 'done') $where .= " AND e.approval_status = 1";
    }
    if ($cs > 0) $where .= " AND e.checksheet_id = $cs";
    $rows = ap_all($connect, "SELECT e.event_date AS d, e.checksheet_id AS cs_id, MAX(c.name) AS cs, COUNT(*) AS t, SUM(e.approval_status = 1) AS ok
            FROM pm_plan_events e LEFT JOIN pm_checksheets c ON c.id = e.checksheet_id
            WHERE $where GROUP BY e.event_date, e.checksheet_id ORDER BY e.event_date DESC, cs ASC LIMIT 500");
    foreach ($rows as &$r) { $r['t'] = (int)$r['t']; $r['ok'] = (int)$r['ok']; }
    unset($r);
    $cs_list = ap_all($connect, "SELECT DISTINCT c.id, c.name FROM pm_plan_events e JOIN pm_checksheets c ON c.id = e.checksheet_id
            WHERE e.ag_id = '$agEsc' AND e.status = 1 AND e.approval_status IN (0, 1) ORDER BY c.name");
    ap_out(['success' => true, 'data' => $rows, 'checksheets' => $cs_list, 'pending' => pmap_pending_count($connect, $AG)]);
}

// ใบงานในกลุ่ม (หรือตามรายการ id)
if ($action === 'items') {
    $where = "e.ag_id = '$agEsc' AND e.status = 1 AND e.approval_status IN (0, 1)";
    $ids = array_values(array_filter(array_map('intval', explode(',', (string)($_GET['ids'] ?? '')))));
    if ($ids) $where .= " AND e.id IN (" . implode(',', $ids) . ")";
    else {
        $d = $_GET['date'] ?? '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) ap_fail('ไม่ระบุวันที่');
        $where .= " AND e.event_date = '$d' AND e.checksheet_id = " . (int)($_GET['cs'] ?? 0);
    }
    if (($_GET['pending'] ?? '') === '1') $where .= " AND e.approval_status = 0";
    $rows = ap_all($connect, "SELECT e.id, e.event_date, e.approval_status, e.approved_at, e.completed_at, wr.doc_no, wr.machine_code, wr.machine_name, wr.location,
                wr.actual_date, wr.inspector_name, wr.checksheet_name, a.approver_name, a.comment AS approve_comment, a.signature_path AS approve_signature
            FROM pm_plan_events e
            JOIN pm_work_records wr ON wr.id = e.work_record_id
            LEFT JOIN pm_work_approvals a ON a.id = e.approval_id
            WHERE $where ORDER BY e.approval_status ASC, wr.machine_code ASC, e.id ASC LIMIT 1000");
    ap_out(['success' => true, 'data' => $rows]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') ap_fail('method not allowed', 405);

// =========================================
// ตรวจรับ (รวมหลายใบ — หน้าเว็บส่งเป็นชุดละไม่เกิน 100)
// =========================================
if ($action === 'approve') {
    @set_time_limit(0);
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)($input['event_ids'] ?? [])))));
    if (!$ids) ap_fail('ไม่ได้เลือกใบงาน');
    if (count($ids) > 100) ap_fail('ส่งได้ครั้งละไม่เกิน 100 ใบ');
    $comment = trim((string)($input['comment'] ?? ''));
    $sig = trim((string)($input['signature_path'] ?? ''));
    if ($sig !== '') { if (!pmap_valid_signature_path($sig)) ap_fail('ไฟล์ลายเซ็นไม่ถูกต้อง กรุณาเซ็นใหม่'); }
    else { $sig = pmap_save_signature((string)($input['signature'] ?? '')); if (!$sig) ap_fail('กรุณาลงลายมือชื่อผู้ตรวจรับ'); }
    $name = ap_my_name($connect, $ME);
    $now = date('Y-m-d H:i:s');
    $ins = mysqli_prepare($connect, "INSERT INTO pm_work_approvals (ag_id, plan_event_id, work_record_id, doc_no, action, comment, approver_name, signature_path, created_at, created_by, updated_at, updated_by)
        VALUES (?, ?, ?, ?, 'approve', ?, ?, ?, ?, ?, ?, ?)");
    $agS = (string)$AG;
    $results = [];
    foreach ($ids as $eid) {
        mysqli_begin_transaction($connect);
        try {
            $r = mysqli_query($connect, "SELECT e.work_record_id, e.approval_status, wr.doc_no FROM pm_plan_events e
                    LEFT JOIN pm_work_records wr ON wr.id = e.work_record_id
                    WHERE e.id = $eid AND e.ag_id = '$agEsc' AND e.status = 1 FOR UPDATE");
            $row = $r ? mysqli_fetch_assoc($r) : null;
            if (!$row || !$row['work_record_id']) throw new Exception('ใบงานยังไม่ได้บันทึกผล หรือไม่อยู่ในหน่วยงานนี้');
            if ($row['approval_status'] === null || (int)$row['approval_status'] !== 0) throw new Exception('ใบงานนี้ไม่ได้รอตรวจรับ (ตรวจรับแล้ว หรือถูกส่งกลับ)');
            $wr = (int)$row['work_record_id']; $doc = (string)$row['doc_no'];
            mysqli_stmt_bind_param($ins, 'siissssssss', $agS, $eid, $wr, $doc, $comment, $name, $sig, $now, $ME, $now, $ME);
            if (!mysqli_stmt_execute($ins)) throw new Exception(mysqli_stmt_error($ins));
            $apId = mysqli_insert_id($connect);
            if (!mysqli_query($connect, "UPDATE pm_plan_events SET approval_status = 1, approval_id = $apId, approved_at = '$now', updated_at = NOW() WHERE id = $eid")) throw new Exception(mysqli_error($connect));
            mysqli_commit($connect);
            $results[] = ['event_id' => $eid, 'success' => true];
        } catch (Exception $e) {
            mysqli_rollback($connect);
            $results[] = ['event_id' => $eid, 'success' => false, 'error' => $e->getMessage()];
        }
    }
    ap_out(['success' => true, 'results' => $results, 'signature_path' => $sig]);
}

// =========================================
// ส่งกลับแก้ไข — ยกเลิกผลบันทึก (คืนอะไหล่ ลบรูป/ลายเซ็น) งานกลับเป็น "ยังไม่ทำ" ช่างเห็นเหตุผลตอนเปิดบันทึกใหม่
// =========================================
if ($action === 'return') {
    $eid = (int)($input['event_id'] ?? 0);
    $reason = trim((string)($input['reason'] ?? ''));
    if ($eid <= 0) ap_fail('ไม่พบรหัสงาน PM');
    if (mb_strlen($reason, 'UTF-8') < 5) ap_fail('กรุณาระบุสิ่งที่ต้องแก้ไขอย่างน้อย 5 ตัวอักษร');
    $row = ap_all($connect, "SELECT e.approval_status, e.work_record_id, wr.doc_no FROM pm_plan_events e LEFT JOIN pm_work_records wr ON wr.id = e.work_record_id
            WHERE e.id = $eid AND e.ag_id = '$agEsc' AND e.status = 1");
    if (!$row || !$row[0]['work_record_id']) ap_fail('ใบงานยังไม่ได้บันทึกผล หรือไม่อยู่ในหน่วยงานนี้');
    if ($row[0]['approval_status'] === null || (int)$row[0]['approval_status'] !== 0) ap_fail('ส่งกลับได้เฉพาะใบงานที่รอตรวจรับ');
    $wrId = (int)$row[0]['work_record_id']; $doc = (string)$row[0]['doc_no'];
    $name = ap_my_name($connect, $ME);

    $res = pmwc_cancel($connect, $eid, (string)$AG, $ME, 'ส่งกลับแก้ไข (ผู้ตรวจรับ): ' . $reason);
    if (!$res['success']) ap_out($res);

    $now = date('Y-m-d H:i:s');
    $st = mysqli_prepare($connect, "INSERT INTO pm_work_approvals (ag_id, plan_event_id, work_record_id, doc_no, action, comment, approver_name, created_at, created_by, updated_at, updated_by)
        VALUES (?, ?, ?, ?, 'return', ?, ?, ?, ?, ?, ?)");
    $agS = (string)$AG;
    mysqli_stmt_bind_param($st, 'siisssssss', $agS, $eid, $wrId, $doc, $reason, $name, $now, $ME, $now, $ME);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    ap_out(['success' => true, 'doc_no' => $res['doc_no'], 'revision' => $res['revision'], 'spares_returned' => $res['spares_returned']]);
}

ap_fail('ไม่รู้จักคำสั่ง (action)', 400);
