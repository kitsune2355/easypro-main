<?php
// ลบแผน PM จำนวนมาก (เช่น นำเข้าผิด) — เฉพาะ Super Admin, เฉพาะหน่วยงานที่เลือกอยู่
//  GET  ?action=batches                                   รายการ "รอบการสร้าง" (ผู้สร้าง + นาทีที่สร้าง) ของแผน/เช็คชีต
//  POST ?action=types    keys[]                           ประเภทเครื่องจักรในรอบที่เลือก (พร้อมจำนวน)
//  POST ?action=preview  keys[] types[] mode              สรุปสิ่งที่จะถูกลบ (ยังไม่ลบ)
//  POST ?action=delete   keys[] types[] mode confirm=ลบ    ลบจริง (transaction)
//   types[] = id ประเภทเครื่องจักร ('none' = ไม่ระบุประเภท) — ไม่ส่ง = ทุกประเภท
//   mode = pending ลบเฉพาะงานที่ยังไม่ทำ (ค่าเริ่มต้น) / done เฉพาะงานที่ทำแล้ว / all ทั้งหมด
// กติกา:
//  - งานที่ทำแล้ว = มีใบงาน (pm_work_records) หรือสถานะไม่ใช่ 0 — ลบแล้วลบใบงาน ผลตรวจ รูป ลายเซ็น ผลประเมิน และ "คืนสต็อก" อะไหล่ที่ตัดไป
//  - แผนที่ไม่เหลืองานเลย => ลบแผน · ลบเฉพาะงานที่ยังไม่ทำแล้วเหลืองานที่ทำแล้ว => ปิดใช้งานแผน (ไม่สร้างกำหนดการใหม่)
//  - เช็คชีต "ไม่ลบ" — ถ้าไม่มีแผน/กำหนดการ/ใบงานอื่นใช้อยู่แล้ว จะล้างประเภทเครื่องจักร (ass_type_id = NULL)
//    เพื่อไม่ให้ถูกจับคู่กับประเภทเดิมตอนนำเข้าใหม่ (นำเข้าจับคู่เช็คชีตด้วย ประเภท + ชื่อ) — จุดตรวจ/ไฟล์แนบยังอยู่ครบ
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

// ---------- ตัวช่วย: รอบที่เลือก / ประเภทที่เลือก ----------
function bd_keys($raw) {
    $keys = array_values(array_unique(array_filter(array_map('strval', (array)$raw), function ($k) { return strpos($k, '|') !== false; })));
    if (!$keys) throw new Exception('ยังไม่ได้เลือกรอบที่จะลบ');
    return $keys;
}
// เงื่อนไขประเภทเครื่องจักร: คอลัมน์ $col (เช่น p.mac_type_id) — [] = ทุกประเภท
function bd_type_sql($col, $types) {
    if (!$types) return '';
    $ids = []; $none = false;
    foreach ($types as $t) { if ($t === 'none') $none = true; elseif ((int)$t > 0) $ids[] = (int)$t; }
    $parts = [];
    if ($ids) $parts[] = "$col IN (" . implode(',', $ids) . ")";
    if ($none) $parts[] = "($col IS NULL OR $col = '' OR $col = '0')";
    return $parts ? ' AND (' . implode(' OR ', $parts) . ')' : ' AND 1 = 0';
}
// งานที่ "ยังไม่ทำ" = สถานะ 0 และยังไม่มีใบงานเลย (รวมใบงานที่บันทึกค้างไว้)
$PENDING_SQL = "(e.status = 0 AND e.work_record_id IS NULL AND wr.id IS NULL)";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') bd_out(['success' => false, 'error' => 'method not allowed'], 405);

if ($action === 'types') {
    try {
        $keys = bd_keys($_POST['keys'] ?? []);
        $ph = implode(',', array_fill(0, count($keys), '?'));
        $tp = 's' . str_repeat('s', count($keys));
        $pr = array_merge([$ag], $keys);
        $map = [];
        $row = function ($id) use (&$map) {
            $k = ($id === null || $id === '' || (string)$id === '0') ? 'none' : (string)(int)$id;
            if (!isset($map[$k])) $map[$k] = ['id' => $k, 'name' => '', 'plans' => 0, 'pending' => 0, 'done' => 0, 'sheets' => 0];
            return $k;
        };
        foreach (bd_rows($connect, "SELECT p.mac_type_id AS t, COUNT(DISTINCT p.id) AS plans,
                    SUM(e.id IS NOT NULL AND $PENDING_SQL) AS pending, SUM(e.id IS NOT NULL AND NOT $PENDING_SQL) AS done
                FROM pm_plans p
                LEFT JOIN pm_plan_events e ON e.plan_id = p.id
                LEFT JOIN pm_work_records wr ON wr.plan_event_id = e.id
                WHERE p.ag_id = ? AND $BATCH_EXPR_PLAN IN ($ph) GROUP BY p.mac_type_id", $tp, $pr) as $r) {
            $k = $row($r['t']); $map[$k]['plans'] += (int)$r['plans']; $map[$k]['pending'] += (int)$r['pending']; $map[$k]['done'] += (int)$r['done'];
        }
        foreach (bd_rows($connect, "SELECT c.ass_type_id AS t, COUNT(*) AS n FROM pm_checksheets c
                WHERE c.ag_id = ? AND $BATCH_EXPR_SHEET IN ($ph) GROUP BY c.ass_type_id", $tp, $pr) as $r) {
            $k = $row($r['t']); $map[$k]['sheets'] += (int)$r['n'];
        }
        $ids = array_filter(array_keys($map), function ($k) { return $k !== 'none'; });
        if ($ids) foreach (bd_rows($connect, "SELECT GroupId, TGroupName FROM tb_asset_group WHERE GroupId IN (" . bd_in($ids) . ")") as $g) {
            if (isset($map[(string)(int)$g['GroupId']])) $map[(string)(int)$g['GroupId']]['name'] = (string)$g['TGroupName'];
        }
        foreach ($map as &$m) if ($m['name'] === '') $m['name'] = $m['id'] === 'none' ? 'ไม่ระบุประเภท' : 'ประเภท #' . $m['id'];
        unset($m);
        $list = array_values($map);
        usort($list, function ($a, $b) { return ($b['pending'] + $b['done']) - ($a['pending'] + $a['done']) ?: strcmp($a['name'], $b['name']); });
        bd_out(['success' => true, 'types' => $list]);
    } catch (Exception $e) { bd_out(['success' => false, 'error' => $e->getMessage()]); }
}

// ---------- คำนวณสิ่งที่จะลบ ----------
function bd_plan($connect, $ag, $keys, $types, $mode) {
    global $BATCH_EXPR_PLAN, $BATCH_EXPR_SHEET, $PENDING_SQL;
    $keys = bd_keys($keys);
    $types = array_values(array_unique(array_map('strval', (array)$types)));
    if (!in_array($mode, ['pending', 'done', 'all'], true)) $mode = 'pending';
    $ph = implode(',', array_fill(0, count($keys), '?'));
    $tp = 's' . str_repeat('s', count($keys));
    $pr = array_merge([$ag], $keys);

    $planIds = bd_ids(array_column(bd_rows($connect, "SELECT p.id FROM pm_plans p WHERE p.ag_id = ? AND $BATCH_EXPR_PLAN IN ($ph)" . bd_type_sql('p.mac_type_id', $types), $tp, $pr), 'id'));
    $sheetIdsCreated = bd_ids(array_column(bd_rows($connect, "SELECT c.id FROM pm_checksheets c WHERE c.ag_id = ? AND $BATCH_EXPR_SHEET IN ($ph)" . bd_type_sql('c.ass_type_id', $types), $tp, $pr), 'id'));

    $inP = bd_in($planIds);
    $events = bd_rows($connect, "SELECT e.id, e.plan_id, $PENDING_SQL AS pending, wr.id AS wr_id, e.work_record_id
        FROM pm_plan_events e LEFT JOIN pm_work_records wr ON wr.plan_event_id = e.id
        WHERE e.plan_id IN ($inP)");
    $delEvents = []; $delRecords = []; $remain = []; $pendingDel = 0; $doneDel = 0; $doneKeep = 0;
    foreach ($events as $e) {
        $pending = (int)$e['pending'] === 1;
        $del = $mode === 'all' || ($mode === 'pending' && $pending) || ($mode === 'done' && !$pending);
        if ($del) {
            $delEvents[] = (int)$e['id'];
            if ($pending) $pendingDel++; else $doneDel++;
            if ($e['wr_id']) $delRecords[(int)$e['wr_id']] = (int)$e['wr_id'];
            if ($e['work_record_id']) $delRecords[(int)$e['work_record_id']] = (int)$e['work_record_id'];
        } else {
            $remain[(int)$e['plan_id']] = ($remain[(int)$e['plan_id']] ?? 0) + 1;
            if (!$pending) $doneKeep++;
        }
    }
    $delRecords = array_values($delRecords);
    // แผน: ไม่เหลืองาน => ลบ · ลบเฉพาะงานที่ยังไม่ทำแล้วเหลืองานที่ทำแล้ว => ปิดใช้งาน · อื่น ๆ คงไว้
    $deletePlans = []; $deactivatePlans = [];
    foreach ($planIds as $id) {
        if (empty($remain[$id])) $deletePlans[] = $id;
        elseif ($mode === 'pending') $deactivatePlans[] = $id;
    }

    // เช็คชีตที่เกี่ยวข้อง = สร้างในรอบนี้ + ใช้โดยแผนที่เลือก — ล้างประเภทเมื่อไม่มีใครใช้แล้ว
    $sheetIdsUsed = bd_ids(array_column(bd_rows($connect, "SELECT DISTINCT checksheet_id FROM pm_plans WHERE id IN ($inP) AND checksheet_id IS NOT NULL"), 'checksheet_id'));
    $candSheets = bd_ids(array_merge($sheetIdsCreated, $sheetIdsUsed));
    $inC = bd_in($candSheets); $inDelP = bd_in($deletePlans); $inDelE = bd_in($delEvents); $inDelR = bd_in($delRecords);
    $usedBy = [];
    foreach (bd_rows($connect, "SELECT DISTINCT checksheet_id AS c FROM pm_plans WHERE checksheet_id IN ($inC) AND id NOT IN ($inDelP)") as $r) $usedBy[(int)$r['c']] = 1;
    foreach (bd_rows($connect, "SELECT DISTINCT checksheet_id AS c FROM pm_plan_events WHERE checksheet_id IN ($inC) AND id NOT IN ($inDelE) AND plan_id NOT IN ($inDelP)") as $r) $usedBy[(int)$r['c']] = 1;
    foreach (bd_rows($connect, "SELECT DISTINCT checksheet_id AS c FROM pm_work_records WHERE checksheet_id IN ($inC) AND id NOT IN ($inDelR)") as $r) $usedBy[(int)$r['c']] = 1;
    $typed = [];
    foreach (bd_rows($connect, "SELECT id FROM pm_checksheets WHERE id IN ($inC) AND ass_type_id IS NOT NULL AND ass_type_id <> 0") as $r) $typed[(int)$r['id']] = 1;
    $clearSheets = array_values(array_filter($candSheets, function ($id) use ($usedBy, $typed) { return !isset($usedBy[$id]) && isset($typed[$id]); }));
    $keepSheets = array_values(array_filter($candSheets, function ($id) use ($usedBy) { return isset($usedBy[$id]); }));

    // อะไหล่ที่ตัดสต็อกไปแล้ว (คืนเมื่อลบใบงาน)
    $spares = $delRecords ? bd_rows($connect, "SELECT rpd_product_id AS pd, wh_id AS wh, SUM(rpd_qty) AS qty
        FROM pm_work_record_actual_spares WHERE work_record_id IN ($inDelR) AND rpd_product_id > 0 AND wh_id > 0 GROUP BY rpd_product_id, wh_id") : [];

    return [
        'mode' => $mode,
        'plans_total' => count($planIds),
        'delete_plans' => $deletePlans,
        'deactivate_plans' => $deactivatePlans,
        'delete_events' => $delEvents,
        'pending_del' => $pendingDel,
        'done_del' => $doneDel,
        'done_keep' => $doneKeep,
        'delete_records' => $delRecords,
        'spares' => $spares,
        'clear_sheets' => $clearSheets,
        'keep_sheets' => $keepSheets,
    ];
}

function bd_summary($p) {
    $qty = 0; foreach ($p['spares'] as $s) $qty += (float)$s['qty'];
    return [
        'mode' => $p['mode'],
        'plans_total' => $p['plans_total'],
        'delete_plans' => count($p['delete_plans']),
        'deactivate_plans' => count($p['deactivate_plans']),
        'pending_del' => $p['pending_del'],
        'done_del' => $p['done_del'],
        'done_keep' => $p['done_keep'],
        'records_del' => count($p['delete_records']),
        'spare_items' => count($p['spares']),
        'spare_qty' => $qty,
        'clear_sheets' => count($p['clear_sheets']),
        'keep_sheets' => count($p['keep_sheets']),
    ];
}

$keys = $_POST['keys'] ?? [];
$types = $_POST['types'] ?? [];
$mode = (string)($_POST['mode'] ?? 'pending');

if ($action === 'preview') {
    try { bd_out(['success' => true, 'summary' => bd_summary(bd_plan($connect, $ag, $keys, $types, $mode))]); }
    catch (Exception $e) { bd_out(['success' => false, 'error' => $e->getMessage()]); }
}

if ($action === 'delete') {
    if (($_POST['confirm'] ?? '') !== 'ลบ') bd_out(['success' => false, 'error' => 'กรุณาพิมพ์คำว่า ลบ เพื่อยืนยัน']);
    try {
        $p = bd_plan($connect, $ag, $keys, $types, $mode);

        // ไฟล์ของใบงานที่จะลบ (ลบไฟล์จริงหลัง commit): รูปผลตรวจ ลายเซ็นผู้ตรวจ/ผู้ประเมิน สำเนาไฟล์แนบของใบงาน
        $files = [];
        foreach (array_chunk($p['delete_records'], 2000) as $chunk) {
            $in = bd_in($chunk);
            foreach (bd_rows($connect, "SELECT result_photo_path AS f FROM pm_work_record_items WHERE work_record_id IN ($in) AND result_photo_path <> ''") as $r) $files[] = $r['f'];
            foreach (bd_rows($connect, "SELECT inspector_signature_path AS f FROM pm_work_records WHERE id IN ($in) AND inspector_signature_path <> ''") as $r) $files[] = $r['f'];
            foreach (bd_rows($connect, "SELECT evaluator_signature_path AS f FROM pm_work_evaluations WHERE work_record_id IN ($in) AND evaluator_signature_path <> ''") as $r) $files[] = $r['f'];
            foreach (bd_rows($connect, "SELECT s.file_path AS f FROM pm_work_record_files_snapshot s
                    WHERE s.work_record_id IN ($in) AND s.file_path <> ''
                      AND NOT EXISTS (SELECT 1 FROM pm_checksheet_files cf WHERE cf.file_path = s.file_path)
                      AND NOT EXISTS (SELECT 1 FROM pm_work_record_files_snapshot o WHERE o.file_path = s.file_path AND o.work_record_id NOT IN ($in))") as $r) $files[] = $r['f'];
        }

        mysqli_begin_transaction($connect);
        $agEsc = mysqli_real_escape_string($connect, $ag);
        $meEsc = mysqli_real_escape_string($connect, $me);
        // คืนสต็อกอะไหล่ที่ใบงานตัดไป
        if ($p['spares']) {
            $st = mysqli_prepare($connect, "UPDATE product_stocks SET qty = qty + ?, updated_at = NOW() WHERE pd_id = ? AND wh_id = ?");
            foreach ($p['spares'] as $s) {
                $q = (float)$s['qty']; $pd = (int)$s['pd']; $wh = (int)$s['wh'];
                mysqli_stmt_bind_param($st, 'dii', $q, $pd, $wh);
                if (!mysqli_stmt_execute($st)) throw new Exception(mysqli_error($connect));
            }
            mysqli_stmt_close($st);
        }
        // ทีละก้อน กัน SQL ยาวเกิน (หลักหมื่นรายการ)
        foreach (array_chunk($p['delete_records'], 2000) as $chunk) {   // ผลตรวจ/ผู้ตรวจ/อะไหล่/ผลประเมิน ลบตาม (ON DELETE CASCADE)
            if (!mysqli_query($connect, "DELETE FROM pm_work_records WHERE id IN (" . bd_in($chunk) . ")")) throw new Exception(mysqli_error($connect));
        }
        foreach (array_chunk($p['delete_events'], 2000) as $chunk) {
            $in = bd_in($chunk);
            mysqli_query($connect, "DELETE FROM pm_plan_postpone_history WHERE plan_id IN ($in)");   // ประวัติเลื่อน (คีย์ = id กำหนดการ)
            if (!mysqli_query($connect, "DELETE FROM pm_plan_events WHERE ag_id = '$agEsc' AND id IN ($in)")) throw new Exception(mysqli_error($connect));
        }
        foreach (array_chunk($p['delete_plans'], 2000) as $chunk) {
            if (!mysqli_query($connect, "DELETE FROM pm_plans WHERE ag_id = '$agEsc' AND id IN (" . bd_in($chunk) . ")")) throw new Exception(mysqli_error($connect));
        }
        foreach (array_chunk($p['deactivate_plans'], 2000) as $chunk) {   // เหลือแต่งานที่ทำแล้ว => ปิดใช้งาน ไม่สร้างกำหนดการใหม่
            if (!mysqli_query($connect, "UPDATE pm_plans SET status = 0, updated_at = NOW() WHERE ag_id = '$agEsc' AND id IN (" . bd_in($chunk) . ")")) throw new Exception(mysqli_error($connect));
        }
        foreach (array_chunk($p['clear_sheets'], 2000) as $chunk) {   // ไม่ลบเช็คชีต — ล้างประเภทเครื่องจักรเท่านั้น
            if (!mysqli_query($connect, "UPDATE pm_checksheets SET ass_type_id = NULL, updated_at = NOW(), updated_by = '$meEsc' WHERE ag_id = '$agEsc' AND id IN (" . bd_in($chunk) . ")")) throw new Exception(mysqli_error($connect));
        }
        mysqli_commit($connect);

        foreach (array_unique($files) as $f) {
            $f = (string)$f;
            if ($f !== '' && strpos($f, 'uploads/') === 0 && strpos($f, '..') === false && is_file(__DIR__ . '/' . $f)) @unlink(__DIR__ . '/' . $f);
        }
        bd_out(['success' => true, 'summary' => bd_summary($p)]);
    } catch (Exception $e) {
        @mysqli_rollback($connect);
        bd_out(['success' => false, 'error' => 'ลบไม่สำเร็จ: ' . $e->getMessage()]);
    }
}

bd_out(['success' => false, 'error' => 'unknown action'], 400);
