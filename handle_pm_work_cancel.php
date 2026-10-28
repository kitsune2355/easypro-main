<?php
// ยกเลิกผลบันทึก PM แล้วส่งกลับให้ทำใหม่ — Admin / Super Admin เท่านั้น
//  POST ?action=cancel  event_id, reason   ลบใบงานเดิม (ผลตรวจ รูป ลายเซ็น ผลประเมิน) คืนสต็อกอะไหล่ ตั้งกำหนดการกลับเป็น "ยังไม่ทำ"
//                                           เก็บ log (ใคร เมื่อไร เหตุผล เลขที่เอกสาร) — บันทึกใหม่จะใช้เลขที่เอกสารเดิม + แก้ไขครั้งที่ N
//  GET  ?action=info    event_id           ข้อมูลก่อนยกเลิก (ประเมินแล้วหรือยัง / อะไหล่ที่จะคืน)
@session_start();
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');
include 'config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');
require_once __DIR__ . '/config_ctrl/pm_work_cancel.php';
require_once __DIR__ . '/config_ctrl/pm_approve.php';   // ล้างสถานะตรวจรับตอนยกเลิก

function wc_out($a, $code = 200) { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

$me    = (string)($_SESSION['sess_user_id_es'] ?? '');
$level = (string)($_SESSION['sess_user_level_es'] ?? '');
$ag    = trim((string)($_SESSION['sess_user_agency'] ?? ''));
if ($me === '') wc_out(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบใหม่'], 401);
if (!in_array($level, ['admin', 'super_admin'], true)) wc_out(['success' => false, 'error' => 'เฉพาะผู้ดูแลระบบ (Admin / Super Admin)'], 403);

$action = $_GET['action'] ?? '';
function wc_one($connect, $sql) { $r = mysqli_query($connect, $sql); return $r ? mysqli_fetch_assoc($r) : null; }
function wc_all($connect, $sql) { $o = []; $r = mysqli_query($connect, $sql); if ($r) while ($x = mysqli_fetch_assoc($r)) $o[] = $x; return $o; }
$agEsc = mysqli_real_escape_string($connect, $ag);

// ---------- Super Admin: ประวัติการยกเลิก/แก้ไข (มอนิเตอร์) ----------
//  GET ?action=logs   q (เลขที่เอกสาร/เครื่อง/เหตุผล/ผู้ยกเลิก), status (redo = รอทำใหม่ / done = ทำใหม่แล้ว)
//  GET ?action=search q                     ค้นใบงานที่บันทึกแล้ว เพื่อรีเซ็ตให้ทำใหม่
if ($action === 'logs' || $action === 'search') {
    if ($level !== 'super_admin') wc_out(['success' => false, 'error' => 'เฉพาะ Super Admin'], 403);
    $q = trim((string)($_GET['q'] ?? ''));
    $qEsc = mysqli_real_escape_string($connect, str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q));
    $MACHINE_JOIN = "LEFT JOIN tb_ass_list m ON m.ass_id = e.machine_id LEFT JOIN pm_checksheets c ON c.id = e.checksheet_id";

    if ($action === 'search') {
        if (mb_strlen($q, 'UTF-8') < 2) wc_out(['success' => true, 'rows' => []]);
        $rows = wc_all($connect, "SELECT e.id AS event_id, e.event_date, e.evaluation_status, wr.doc_no, wr.actual_date, wr.inspector_name,
                COALESCE(wr.machine_code, m.ass_code) AS machine_code, COALESCE(wr.machine_name, m.asset_name) AS machine_name, COALESCE(wr.checksheet_name, c.name) AS checksheet
            FROM pm_work_records wr JOIN pm_plan_events e ON e.id = wr.plan_event_id $MACHINE_JOIN
            WHERE e.ag_id = '$agEsc' AND (wr.doc_no LIKE '%$qEsc%' OR wr.machine_code LIKE '%$qEsc%' OR wr.machine_name LIKE '%$qEsc%' OR m.ass_code LIKE '%$qEsc%' OR m.asset_name LIKE '%$qEsc%')
            ORDER BY wr.actual_date DESC, wr.id DESC LIMIT 30");
        wc_out(['success' => true, 'rows' => $rows]);
    }

    if (!pmwc_has_table($connect)) wc_out(['success' => true, 'rows' => [], 'stats' => ['total' => 0, 'redo' => 0, 'done' => 0]]);
    $where = "l.ag_id = '$agEsc'";
    if ($q !== '') $where .= " AND (l.doc_no LIKE '%$qEsc%' OR l.reason LIKE '%$qEsc%' OR l.created_by LIKE '%$qEsc%' OR m.ass_code LIKE '%$qEsc%' OR m.asset_name LIKE '%$qEsc%' OR c.name LIKE '%$qEsc%')";
    // สถานะปัจจุบันของงาน: ยังมีใบงานใหม่หรือยัง (ใบงานใหม่ = บันทึกหลังการยกเลิกครั้งนั้น)
    $sql = "SELECT l.id, l.plan_event_id, l.doc_no, l.revision, l.reason, l.snapshot_json, l.created_at, l.created_by,
                (SELECT u.user_name FROM tb_user u WHERE u.user_id = l.created_by LIMIT 1) AS created_by_name,
                e.event_date, e.status AS ev_status, m.ass_code AS machine_code, m.asset_name AS machine_name, c.name AS checksheet,
                wr.id AS new_wr_id, wr.actual_date AS new_actual_date, wr.created_at AS new_created_at, wr.inspector_name AS new_inspector,
                (SELECT MAX(l2.id) FROM pm_work_record_cancel_log l2 WHERE l2.plan_event_id = l.plan_event_id) AS last_log_id
            FROM pm_work_record_cancel_log l
            LEFT JOIN pm_plan_events e ON e.id = l.plan_event_id
            $MACHINE_JOIN
            LEFT JOIN pm_work_records wr ON wr.plan_event_id = l.plan_event_id
            WHERE $where ORDER BY l.id DESC LIMIT 1000";
    $rows = wc_all($connect, $sql);
    $stats = ['total' => 0, 'redo' => 0, 'done' => 0, 'deleted' => 0];
    $out = [];
    $status = $_GET['status'] ?? '';
    foreach ($rows as $r) {
        // แถวล่าสุดของงานนั้นบอกสถานะปัจจุบัน — แถวเก่ากว่า = ถูกแทนด้วยการยกเลิกครั้งถัดไป
        $isLast = (int)$r['last_log_id'] === (int)$r['id'];
        if ($r['event_date'] === null) $st = 'deleted';                         // กำหนดการถูกลบไปแล้ว
        elseif (!$isLast) $st = 'superseded';
        elseif ($r['new_wr_id']) $st = 'done';
        else $st = 'redo';
        $stats['total']++;
        if (isset($stats[$st])) $stats[$st]++;
        if ($status !== '' && $status !== $st) continue;
        $snap = json_decode((string)$r['snapshot_json'], true);
        $r['state'] = $st;
        $r['snapshot'] = is_array($snap) ? $snap : null;
        unset($r['snapshot_json'], $r['last_log_id']);
        $out[] = $r;
    }
    wc_out(['success' => true, 'rows' => $out, 'stats' => $stats, 'today' => date('Y-m-d')]);
}

$event_id = (int)($_POST['event_id'] ?? $_GET['event_id'] ?? 0);
if ($event_id <= 0) wc_out(['success' => false, 'error' => 'ไม่พบรหัสงาน PM']);
$ev = wc_one($connect, "SELECT e.id, e.ag_id, e.plan_id, e.status, e.evaluation_status, e.work_record_id FROM pm_plan_events e WHERE e.id = $event_id AND e.ag_id = '$agEsc'");
if (!$ev) wc_out(['success' => false, 'error' => 'ไม่พบงาน PM นี้ในหน่วยงานที่เลือก']);
$wr = wc_one($connect, "SELECT * FROM pm_work_records WHERE plan_event_id = $event_id LIMIT 1");
if (!$wr) wc_out(['success' => false, 'error' => 'งานนี้ยังไม่มีผลบันทึก ไม่ต้องยกเลิก']);
$wrId = (int)$wr['id'];

$spares = [];
$r = mysqli_query($connect, "SELECT rpd_product_id AS pd, wh_id AS wh, SUM(rpd_qty) AS qty, MAX(rpd_details) AS name
    FROM pm_work_record_actual_spares WHERE work_record_id = $wrId AND rpd_product_id > 0 AND wh_id > 0 GROUP BY rpd_product_id, wh_id");
if ($r) while ($row = mysqli_fetch_assoc($r)) $spares[] = $row;
$evaluated = (int)$ev['evaluation_status'] === 1 || wc_one($connect, "SELECT 1 AS x FROM pm_work_evaluations WHERE work_record_id = $wrId LIMIT 1");

if ($action === 'info') {
    wc_out(['success' => true, 'doc_no' => $wr['doc_no'], 'actual_date' => $wr['actual_date'], 'evaluated' => (bool)$evaluated,
        'spares' => array_map(function ($s) { return ['name' => $s['name'], 'qty' => (float)$s['qty']]; }, $spares),
        'revision' => pmwc_revision($connect, $event_id)]);
}

if ($action === 'cancel') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') wc_out(['success' => false, 'error' => 'method not allowed'], 405);
    $reason = trim((string)($_POST['reason'] ?? ''));
    if (mb_strlen($reason, 'UTF-8') < 5) wc_out(['success' => false, 'error' => 'กรุณาระบุเหตุผลอย่างน้อย 5 ตัวอักษร']);

    $res = pmwc_cancel($connect, $event_id, $ag, $me, $reason);
    if (!$res['success']) wc_out($res);
    wc_out(['success' => true, 'doc_no' => $res['doc_no'], 'revision' => $res['revision'], 'spares_returned' => $res['spares_returned']]);
}

wc_out(['success' => false, 'error' => 'unknown action'], 400);
