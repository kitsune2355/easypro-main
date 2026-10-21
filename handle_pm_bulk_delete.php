<?php
// ลบแผน PM จำนวนมาก (เช่น นำเข้าผิด) — เฉพาะ Super Admin, เฉพาะหน่วยงานที่เลือกอยู่
//  GET  ?action=batches                 รายการ "รอบการสร้าง" (ผู้สร้าง + นาทีที่สร้าง) ของแผน/เช็คชีต
//  POST ?action=preview  keys[]=...     สรุปสิ่งที่จะถูกลบ (ยังไม่ลบ)
//  POST ?action=delete   keys[]=...     ลบจริง (transaction)
// กติกา: ลบเฉพาะกำหนดการที่ "ยังไม่ได้ทำ" (status 0 และไม่มีใบงาน) — แผนที่มีงานทำแล้วเก็บไว้ (ปิดใช้งาน) เพื่อรักษาประวัติ
//        เช็คชีตลบเมื่อไม่มีแผน/กำหนดการ/ใบงานอื่นใช้อยู่
@session_start();
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');
include 'config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');

function bd_out($a, $code = 200) { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

$me    = (string)($_SESSION['sess_user_id_es'] ?? '');
$level = (string)($_SESSION['sess_user_level_es'] ?? '');
$ag    = trim((string)($_SESSION['sess_user_agency'] ?? ''));
if ($me === '') bd_out(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบใหม่'], 401);
if ($level !== 'super_admin') bd_out(['success' => false, 'error' => 'เฉพาะ Super Admin'], 403);
if ($ag === '' || $ag === '0') bd_out(['success' => false, 'error' => 'กรุณาเลือกหน่วยงานก่อน']);

$action = $_GET['action'] ?? '';

function bd_rows($connect, $sql, $types = '', $params = []) {
    $st = mysqli_prepare($connect, $sql);
    if (!$st) throw new Exception(mysqli_error($connect));
    if ($types !== '') mysqli_stmt_bind_param($st, $types, ...$params);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    mysqli_stmt_close($st);
    return $rows;
}
function bd_ids($arr) { $o = []; foreach ($arr as $v) { $v = (int)$v; if ($v > 0) $o[$v] = $v; } return array_values($o); }
function bd_in($ids) { return $ids ? implode(',', array_map('intval', $ids)) : '0'; }

// คีย์รอบ = ผู้สร้าง|YYYY-mm-dd HH:ii
$BATCH_EXPR_PLAN  = "CONCAT(IFNULL(p.created_by,''), '|', DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i'))";
$BATCH_EXPR_SHEET = "CONCAT(IFNULL(c.created_by,''), '|', DATE_FORMAT(c.created_at, '%Y-%m-%d %H:%i'))";

if ($action === 'batches') {
    $plans = bd_rows($connect, "
        SELECT $BATCH_EXPR_PLAN AS k, COUNT(*) AS plans,
               SUM(p.status = 1) AS active_plans,
               MIN(p.created_at) AS at, p.created_by AS by_user
        FROM pm_plans p WHERE p.ag_id = ? GROUP BY k, p.created_by", 's', [$ag]);
    $ev = bd_rows($connect, "
        SELECT $BATCH_EXPR_PLAN AS k,
               SUM(e.status = 0 AND e.work_record_id IS NULL AND wr.id IS NULL) AS pending,
               SUM(NOT (e.status = 0 AND e.work_record_id IS NULL AND wr.id IS NULL)) AS done
        FROM pm_plans p
        JOIN pm_plan_events e ON e.plan_id = p.id
        LEFT JOIN pm_work_records wr ON wr.plan_event_id = e.id
        WHERE p.ag_id = ? GROUP BY k", 's', [$ag]);
    $sheets = bd_rows($connect, "
        SELECT $BATCH_EXPR_SHEET AS k, COUNT(*) AS sheets, MIN(c.created_at) AS at, c.created_by AS by_user,
               SUBSTRING_INDEX(GROUP_CONCAT(c.name ORDER BY c.id SEPARATOR '||'), '||', 3) AS sample
        FROM pm_checksheets c WHERE c.ag_id = ? GROUP BY k, c.created_by", 's', [$ag]);
    $planSheets = bd_rows($connect, "
        SELECT $BATCH_EXPR_PLAN AS k, COUNT(DISTINCT p.checksheet_id) AS used_sheets,
               SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR '||'), '||', 3) AS sample
        FROM pm_plans p LEFT JOIN pm_checksheets c ON c.id = p.checksheet_id
        WHERE p.ag_id = ? GROUP BY k", 's', [$ag]);

    $map = [];
    $touch = function ($k, $at, $by) use (&$map) {
        if (!isset($map[$k])) $map[$k] = ['key' => $k, 'at' => $at, 'by' => $by, 'plans' => 0, 'active_plans' => 0, 'pending' => 0, 'done' => 0, 'sheets_created' => 0, 'sheets_used' => 0, 'sample' => ''];
        return $k;
    };
    foreach ($plans as $r) { $touch($r['k'], $r['at'], $r['by_user']); $map[$r['k']]['plans'] = (int)$r['plans']; $map[$r['k']]['active_plans'] = (int)$r['active_plans']; }
    foreach ($ev as $r) { if (isset($map[$r['k']])) { $map[$r['k']]['pending'] = (int)$r['pending']; $map[$r['k']]['done'] = (int)$r['done']; } }
    foreach ($planSheets as $r) { if (isset($map[$r['k']])) { $map[$r['k']]['sheets_used'] = (int)$r['used_sheets']; $map[$r['k']]['sample'] = (string)$r['sample']; } }
    foreach ($sheets as $r) {
        $touch($r['k'], $r['at'], $r['by_user']);
        $map[$r['k']]['sheets_created'] = (int)$r['sheets'];
        if ($map[$r['k']]['sample'] === '') $map[$r['k']]['sample'] = (string)$r['sample'];
    }
    $list = array_values($map);
    usort($list, function ($a, $b) { return strcmp($b['at'], $a['at']); });
    foreach ($list as &$b) $b['sample'] = $b['sample'] === '' ? [] : explode('||', $b['sample']);
    unset($b);
    bd_out(['success' => true, 'agency' => (string)($_SESSION['sess_agency_name'] ?? ''), 'batches' => $list]);
}

// ---------- คำนวณสิ่งที่จะลบ จากรอบที่เลือก ----------
function bd_plan($connect, $ag, $keys) {
    global $BATCH_EXPR_PLAN, $BATCH_EXPR_SHEET;
    $keys = array_values(array_unique(array_filter(array_map('strval', (array)$keys), function ($k) { return strpos($k, '|') !== false; })));
    if (!$keys) throw new Exception('ยังไม่ได้เลือกรอบที่จะลบ');
    $ph = implode(',', array_fill(0, count($keys), '?'));
    $types = 's' . str_repeat('s', count($keys));
    $params = array_merge([$ag], $keys);

    $planIds = bd_ids(array_column(bd_rows($connect, "SELECT p.id FROM pm_plans p WHERE p.ag_id = ? AND $BATCH_EXPR_PLAN IN ($ph)", $types, $params), 'id'));
    $sheetIdsCreated = bd_ids(array_column(bd_rows($connect, "SELECT c.id FROM pm_checksheets c WHERE c.ag_id = ? AND $BATCH_EXPR_SHEET IN ($ph)", $types, $params), 'id'));

    $inP = bd_in($planIds);
    // กำหนดการของแผนที่เลือก: ยังไม่ทำ (ลบได้) / ทำแล้วหรือมีใบงาน (เก็บไว้)
    $events = bd_rows($connect, "SELECT e.id, e.plan_id, (e.status = 0 AND e.work_record_id IS NULL AND wr.id IS NULL) AS pending
        FROM pm_plan_events e LEFT JOIN pm_work_records wr ON wr.plan_event_id = e.id
        WHERE e.ag_id = ? AND e.plan_id IN ($inP)", 's', [$ag]);
    $pendingIds = []; $keepPlan = [];
    foreach ($events as $e) {
        if ((int)$e['pending'] === 1) $pendingIds[] = (int)$e['id'];
        else $keepPlan[(int)$e['plan_id']] = true;
    }
    $deletePlans = array_values(array_filter($planIds, function ($id) use ($keepPlan) { return empty($keepPlan[$id]); }));
    $keepPlans = array_values(array_filter($planIds, function ($id) use ($keepPlan) { return !empty($keepPlan[$id]); }));

    // เช็คชีตที่เกี่ยวข้อง = สร้างในรอบนี้ + ใช้โดยแผนที่เลือก
    $sheetIdsUsed = bd_ids(array_column(bd_rows($connect, "SELECT DISTINCT checksheet_id FROM pm_plans WHERE id IN ($inP) AND checksheet_id IS NOT NULL"), 'checksheet_id'));
    $candSheets = bd_ids(array_merge($sheetIdsCreated, $sheetIdsUsed));
    $inC = bd_in($candSheets);
    $inDelP = bd_in($deletePlans);
    $inPend = bd_in($pendingIds);
    // ยังถูกใช้อยู่ = แผนอื่น (ที่ไม่ถูกลบ) / กำหนดการที่เหลือ / ใบงาน
    $usedBy = [];
    foreach (bd_rows($connect, "SELECT DISTINCT checksheet_id AS c FROM pm_plans WHERE checksheet_id IN ($inC) AND id NOT IN ($inDelP)") as $r) $usedBy[(int)$r['c']] = 'plan';
    foreach (bd_rows($connect, "SELECT DISTINCT checksheet_id AS c FROM pm_plan_events WHERE checksheet_id IN ($inC) AND id NOT IN ($inPend) AND plan_id NOT IN ($inDelP)") as $r) $usedBy[(int)$r['c']] = 'event';
    foreach (bd_rows($connect, "SELECT DISTINCT checksheet_id AS c FROM pm_work_records WHERE checksheet_id IN ($inC)") as $r) $usedBy[(int)$r['c']] = 'record';
    $deleteSheets = array_values(array_filter($candSheets, function ($id) use ($usedBy) { return !isset($usedBy[$id]); }));
    $keepSheets = array_values(array_filter($candSheets, function ($id) use ($usedBy) { return isset($usedBy[$id]); }));

    $doneCount = 0; foreach ($events as $e) if ((int)$e['pending'] !== 1) $doneCount++;
    return [
        'plans_total' => count($planIds),
        'delete_plans' => $deletePlans,
        'keep_plans' => $keepPlans,
        'pending_events' => $pendingIds,
        'done_events' => $doneCount,
        'delete_sheets' => $deleteSheets,
        'keep_sheets' => $keepSheets,
    ];
}

function bd_summary($p) {
    return [
        'plans_total' => $p['plans_total'],
        'delete_plans' => count($p['delete_plans']),
        'keep_plans' => count($p['keep_plans']),
        'pending_events' => count($p['pending_events']),
        'done_events' => $p['done_events'],
        'delete_sheets' => count($p['delete_sheets']),
        'keep_sheets' => count($p['keep_sheets']),
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bd_out(['success' => false, 'error' => 'method not allowed'], 405);
$keys = isset($_POST['keys']) ? (array)$_POST['keys'] : [];

if ($action === 'preview') {
    try { bd_out(['success' => true, 'summary' => bd_summary(bd_plan($connect, $ag, $keys))]); }
    catch (Exception $e) { bd_out(['success' => false, 'error' => $e->getMessage()]); }
}

if ($action === 'delete') {
    if (($_POST['confirm'] ?? '') !== 'ลบ') bd_out(['success' => false, 'error' => 'กรุณาพิมพ์คำว่า ลบ เพื่อยืนยัน']);
    try {
        $p = bd_plan($connect, $ag, $keys);
        // ไฟล์แนบของเช็คชีตที่จะลบ (ลบไฟล์จริงหลัง commit)
        $files = [];
        $inS = bd_in($p['delete_sheets']);
        foreach (bd_rows($connect, "SELECT file_path FROM pm_checksheet_files WHERE checksheet_id IN ($inS)") as $r) $files[] = $r['file_path'];
        foreach (bd_rows($connect, "SELECT illustration_path FROM pm_checksheet_items WHERE checksheet_id IN ($inS) AND illustration_path <> ''") as $r) $files[] = $r['illustration_path'];

        mysqli_begin_transaction($connect);
        $agEsc = mysqli_real_escape_string($connect, $ag);
        // ทีละก้อน กัน SQL ยาวเกิน (หลักหมื่นรายการ)
        foreach (array_chunk($p['pending_events'], 2000) as $chunk) {
            $in = bd_in($chunk);
            mysqli_query($connect, "DELETE FROM pm_plan_postpone_history WHERE plan_id IN ($in)");   // ประวัติเลื่อน (คีย์ = id กำหนดการ)
            if (!mysqli_query($connect, "DELETE FROM pm_plan_events WHERE ag_id = '$agEsc' AND status = 0 AND work_record_id IS NULL AND id IN ($in)")) throw new Exception(mysqli_error($connect));
        }
        foreach (array_chunk($p['delete_plans'], 2000) as $chunk) {
            if (!mysqli_query($connect, "DELETE FROM pm_plans WHERE ag_id = '$agEsc' AND id IN (" . bd_in($chunk) . ")")) throw new Exception(mysqli_error($connect));
        }
        foreach (array_chunk($p['keep_plans'], 2000) as $chunk) {   // มีงานทำแล้ว => เก็บแผนไว้แต่ปิดใช้งาน ไม่สร้างกำหนดการใหม่
            if (!mysqli_query($connect, "UPDATE pm_plans SET status = 0, updated_at = NOW() WHERE ag_id = '$agEsc' AND id IN (" . bd_in($chunk) . ")")) throw new Exception(mysqli_error($connect));
        }
        foreach (array_chunk($p['delete_sheets'], 2000) as $chunk) {   // items / files / spares ลบตาม (ON DELETE CASCADE)
            if (!mysqli_query($connect, "DELETE FROM pm_checksheets WHERE ag_id = '$agEsc' AND id IN (" . bd_in($chunk) . ")")) throw new Exception(mysqli_error($connect));
        }
        mysqli_commit($connect);

        foreach (array_unique($files) as $f) {
            $f = (string)$f;
            if ($f !== '' && strpos($f, '..') === false && is_file(__DIR__ . '/' . $f)) @unlink(__DIR__ . '/' . $f);
        }
        bd_out(['success' => true, 'summary' => bd_summary($p)]);
    } catch (Exception $e) {
        @mysqli_rollback($connect);
        bd_out(['success' => false, 'error' => 'ลบไม่สำเร็จ: ' . $e->getMessage()]);
    }
}

bd_out(['success' => false, 'error' => 'unknown action'], 400);
