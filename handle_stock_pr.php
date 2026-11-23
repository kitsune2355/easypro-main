<?php
// ใบขออนุมัติจัดซื้อ (PR) — API ของเมนู "ใบขอซื้อ (PR)" ใน stock.php (หน่วยงานจาก session เท่านั้น)
//  GET  ?action=defaults            ค่าเริ่มต้นของใบใหม่ (เลขที่ถัดไป, ผู้ขอ, ค่าจากใบล่าสุด)
//  GET  ?action=list&q=&limit=&offset=   รายการใบขอซื้อ (ตาราง ag-Grid แบบ server-side)
//  GET  ?action=get&id=             ใบขอซื้อ + รายการ
//  POST ?action=save   (JSON)       บันทึกใบใหม่ => ออกเลขที่
//  POST ?action=cancel (id)         ยกเลิกใบ — ผู้สร้าง / admin / super_admin
@session_start();
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');
include 'config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');
require_once __DIR__ . '/config_ctrl/stock_pr.php';
require_once __DIR__ . '/config_ctrl/stock_perm.php';

function spr_out($a, $code = 200) { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }
function spr_str($v, $max) { $v = trim((string)$v); return function_exists('mb_substr') ? mb_substr($v, 0, $max, 'UTF-8') : substr($v, 0, $max); }
function spr_date($v) { $v = trim((string)$v); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : null; }

$me    = (string)($_SESSION['sess_user_id_es'] ?? '');
$level = (string)($_SESSION['sess_user_level_es'] ?? '');
$ag    = (int)($_SESSION['sess_user_agency'] ?? 0);
if ($me === '' || $ag <= 0) spr_out(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบใหม่'], 401);
spr_ensure($connect);

$action = $_GET['action'] ?? '';
$isAdmin = in_array($level, ['admin', 'super_admin'], true);
$canManage = stock_can_manage($connect);   // ตำแหน่ง "ช่าง" => ดูและพิมพ์ได้ แต่สร้าง/ยกเลิกไม่ได้

// ---------- ค่าเริ่มต้นของใบใหม่ ----------
if ($action === 'defaults') {
    $today = date('Y-m-d');
    $d = ['pr_no' => spr_next_no($connect, $ag, $today), 'pr_date' => $today, 'req_name' => ''];
    $st = mysqli_prepare($connect, "SELECT user_name, user_fname FROM tb_user WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($st, 's', $me);
    mysqli_stmt_execute($st);
    mysqli_stmt_bind_result($st, $fn, $ln);
    if (mysqli_stmt_fetch($st)) $d['req_name'] = trim($fn . ' ' . $ln);
    mysqli_stmt_close($st);
    // ชื่อผู้ขอที่ผู้ใช้เคยกรอกในใบล่าสุด
    $st = mysqli_prepare($connect, "SELECT req_name FROM tb_stock_pr WHERE ag_id = ? AND created_by = ? ORDER BY pr_id DESC LIMIT 1");
    mysqli_stmt_bind_param($st, 'is', $ag, $me);
    mysqli_stmt_execute($st);
    mysqli_stmt_bind_result($st, $rn);
    if (mysqli_stmt_fetch($st) && trim((string)$rn) !== '') $d['req_name'] = (string)$rn;
    mysqli_stmt_close($st);
    spr_out(['success' => true, 'defaults' => $d]);
}

// ---------- รายการใบขอซื้อ ----------
if ($action === 'list') {
    $q = trim((string)($_GET['q'] ?? ''));
    $per = max(1, min(200, (int)($_GET['limit'] ?? 20)));
    $off = max(0, (int)($_GET['offset'] ?? 0));
    $where = "WHERE h.ag_id = ?";
    $types = 'i';
    $params = [$ag];
    if ($q !== '') {
        $where .= " AND (h.pr_no LIKE ? OR h.req_name LIKE ? OR h.remark LIKE ? OR EXISTS (SELECT 1 FROM tb_stock_pr_item i2 WHERE i2.pr_id = h.pr_id AND (i2.pd_code LIKE ? OR i2.pd_name LIKE ?)))";
        $like = '%' . $q . '%';
        $types .= 'sssss';
        array_push($params, $like, $like, $like, $like, $like);
    }
    $st = mysqli_prepare($connect, "SELECT COUNT(*) FROM tb_stock_pr h $where");
    mysqli_stmt_bind_param($st, $types, ...$params);
    mysqli_stmt_execute($st);
    mysqli_stmt_bind_result($st, $total);
    mysqli_stmt_fetch($st);
    mysqli_stmt_close($st);
    $sql = "SELECT h.pr_id, h.pr_no, h.pr_date, h.deliver_date, h.req_name, h.remark, h.status, h.created_by, h.created_at,
                   (SELECT COUNT(*) FROM tb_stock_pr_item i WHERE i.pr_id = h.pr_id) AS n_items,
                   (SELECT GROUP_CONCAT(i.pd_name ORDER BY i.line_no SEPARATOR ' · ') FROM tb_stock_pr_item i WHERE i.pr_id = h.pr_id) AS item_names
            FROM tb_stock_pr h $where ORDER BY h.pr_date DESC, h.pr_id DESC LIMIT $per OFFSET $off";
    $st = mysqli_prepare($connect, $sql);
    mysqli_stmt_bind_param($st, $types, ...$params);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $rows = [];
    while ($r = mysqli_fetch_assoc($rs)) {
        $r['pr_id'] = (int)$r['pr_id'];
        $r['status'] = (int)$r['status'];
        $r['n_items'] = (int)$r['n_items'];
        $names = (string)$r['item_names'];
        $r['item_names'] = function_exists('mb_substr') && mb_strlen($names, 'UTF-8') > 140 ? mb_substr($names, 0, 140, 'UTF-8') . '…' : $names;
        $r['can_cancel'] = $canManage && $r['status'] === 1 && ($isAdmin || $r['created_by'] === $me);
        $r['items'] = [];
        $rows[] = $r;
    }
    mysqli_stmt_close($st);
    // รายการสินค้าของใบในหน้านี้ (แสดงแบบกางดูได้ เหมือนรายงานรับเข้า/ปรับยอด)
    if ($rows) {
        $idx = [];
        foreach ($rows as $k => $r) $idx[$r['pr_id']] = $k;
        $ids = array_keys($idx);
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $st = mysqli_prepare($connect, "SELECT pr_id, pd_code, pd_name, pd_unit, qty, note FROM tb_stock_pr_item WHERE pr_id IN ($ph) ORDER BY pr_id, line_no");
        mysqli_stmt_bind_param($st, str_repeat('i', count($ids)), ...$ids);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        while ($it = mysqli_fetch_assoc($rs)) {
            $k = $idx[(int)$it['pr_id']];
            unset($it['pr_id']);
            $it['qty'] = (float)$it['qty'];
            $rows[$k]['items'][] = $it;
        }
        mysqli_stmt_close($st);
    }
    spr_out(['success' => true, 'rows' => $rows, 'total' => (int)$total]);
}

// ---------- ใบเดียว ----------
if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $st = mysqli_prepare($connect, "SELECT * FROM tb_stock_pr WHERE pr_id = ? AND ag_id = ? LIMIT 1");
    mysqli_stmt_bind_param($st, 'ii', $id, $ag);
    mysqli_stmt_execute($st);
    $h = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    if (!$h) spr_out(['success' => false, 'error' => 'ไม่พบใบขอซื้อ'], 404);
    $st = mysqli_prepare($connect, "SELECT line_no, pd_id, pd_code, pd_name, pd_unit, qty, note FROM tb_stock_pr_item WHERE pr_id = ? ORDER BY line_no");
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $items = [];
    while ($r = mysqli_fetch_assoc($rs)) { $r['qty'] = (float)$r['qty']; $items[] = $r; }
    mysqli_stmt_close($st);
    spr_out(['success' => true, 'pr' => $h, 'items' => $items]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') spr_out(['success' => false, 'error' => 'method not allowed'], 405);
if (!$canManage) spr_out(['success' => false, 'error' => 'ไม่มีสิทธิ์: ตำแหน่งช่างสร้างหรือยกเลิกใบขอซื้อไม่ได้'], 403);

// ---------- บันทึกใบใหม่ ----------
if ($action === 'save') {
    $in = json_decode(file_get_contents('php://input'), true);
    if (!is_array($in)) spr_out(['success' => false, 'error' => 'ข้อมูลไม่ถูกต้อง'], 400);
    $prDate = spr_date($in['pr_date'] ?? '') ?: date('Y-m-d');
    $deliver = spr_date($in['deliver_date'] ?? '');
    $remark = spr_str($in['remark'] ?? '', 2000);
    $reqName = spr_str($in['req_name'] ?? '', 150);
    if ($reqName === '') spr_out(['success' => false, 'error' => 'กรุณากรอกชื่อผู้ขอซื้อ']);

    // รายการ: ต้องเป็นสินค้าของหน่วยงานนี้ — ชื่อ/รหัส/หน่วยเก็บจากฐานข้อมูล ณ วันที่ออกเอกสาร
    $lines = [];
    foreach ((array)($in['items'] ?? []) as $it) {
        $pid = (int)($it['pd_id'] ?? 0);
        $qty = round((float)($it['qty'] ?? 0), 2);
        if ($pid <= 0 || $qty <= 0) continue;
        $lines[] = ['pd_id' => $pid, 'qty' => $qty, 'note' => spr_str($it['note'] ?? '', 255)];
    }
    if (!$lines) spr_out(['success' => false, 'error' => 'กรุณาเลือกสินค้าอย่างน้อย 1 รายการ และระบุจำนวนมากกว่า 0']);
    if (count($lines) > 200) spr_out(['success' => false, 'error' => 'เลือกได้ไม่เกิน 200 รายการต่อใบ']);
    $ids = array_values(array_unique(array_map(function ($l) { return $l['pd_id']; }, $lines)));
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $st = mysqli_prepare($connect, "SELECT pd_id, pd_gen_code, pd_details_head, pd_details, pd_unit FROM tb_repair_product WHERE ag_id = ? AND pd_id IN ($ph)");
    $bind = array_merge([$ag], $ids);
    mysqli_stmt_bind_param($st, 'i' . str_repeat('i', count($ids)), ...$bind);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $prod = [];
    while ($r = mysqli_fetch_assoc($rs)) $prod[(int)$r['pd_id']] = $r;
    mysqli_stmt_close($st);
    foreach ($lines as $l) if (!isset($prod[$l['pd_id']])) spr_out(['success' => false, 'error' => 'พบสินค้าที่ไม่ใช่ของหน่วยงานนี้ กรุณาโหลดหน้าใหม่']);

    $now = date('Y-m-d H:i:s');
    // ออกเลขที่: ชนกับคนอื่นที่บันทึกพร้อมกัน (UNIQUE ag_id+pr_no) => ลองเลขถัดไป
    $prId = 0;
    $prNo = '';
    for ($try = 0; $try < 5 && !$prId; $try++) {
        mysqli_begin_transaction($connect);
        $prNo = spr_next_no($connect, $ag, $prDate);
        $st = mysqli_prepare($connect, "INSERT INTO tb_stock_pr (ag_id, pr_no, pr_date, deliver_date, remark, req_name, status, created_at, created_by, updated_at, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($st, 'isssssssss', $ag, $prNo, $prDate, $deliver, $remark, $reqName, $now, $me, $now, $me);
        $ok = @mysqli_stmt_execute($st);
        $err = mysqli_stmt_errno($st);
        $newId = (int)mysqli_insert_id($connect);
        mysqli_stmt_close($st);
        if (!$ok) {
            mysqli_rollback($connect);
            if ($err === 1062) continue;   // เลขซ้ำ
            spr_out(['success' => false, 'error' => 'บันทึกไม่สำเร็จ'], 500);
        }
        $st = mysqli_prepare($connect, "INSERT INTO tb_stock_pr_item (pr_id, line_no, pd_id, pd_code, pd_name, pd_unit, qty, note, created_at, created_by, updated_at, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $lineNo = 0;
        $okItems = true;
        foreach ($lines as $l) {
            $p = $prod[$l['pd_id']];
            $lineNo++;
            $name = trim((string)$p['pd_details_head']);
            $detail = trim((string)$p['pd_details']);
            if ($detail !== '' && $detail !== $name) $name .= ' ' . $detail;
            $code = (string)$p['pd_gen_code'];
            $unit = (string)$p['pd_unit'];
            mysqli_stmt_bind_param($st, 'iiisssdsssss', $newId, $lineNo, $l['pd_id'], $code, $name, $unit, $l['qty'], $l['note'], $now, $me, $now, $me);
            if (!mysqli_stmt_execute($st)) { $okItems = false; break; }
        }
        mysqli_stmt_close($st);
        if (!$okItems) { mysqli_rollback($connect); spr_out(['success' => false, 'error' => 'บันทึกรายการไม่สำเร็จ'], 500); }
        mysqli_commit($connect);
        $prId = $newId;
    }
    if (!$prId) spr_out(['success' => false, 'error' => 'ออกเลขที่เอกสารไม่สำเร็จ กรุณาลองใหม่'], 409);
    spr_out(['success' => true, 'pr_id' => $prId, 'pr_no' => $prNo]);
}

// ---------- ยกเลิก ----------
if ($action === 'cancel') {
    $id = (int)($_POST['id'] ?? 0);
    $st = mysqli_prepare($connect, "SELECT created_by, status FROM tb_stock_pr WHERE pr_id = ? AND ag_id = ? LIMIT 1");
    mysqli_stmt_bind_param($st, 'ii', $id, $ag);
    mysqli_stmt_execute($st);
    mysqli_stmt_bind_result($st, $by, $status);
    $found = mysqli_stmt_fetch($st);
    mysqli_stmt_close($st);
    if (!$found) spr_out(['success' => false, 'error' => 'ไม่พบใบขอซื้อ'], 404);
    if (!$isAdmin && $by !== $me) spr_out(['success' => false, 'error' => 'ยกเลิกได้เฉพาะผู้สร้างใบ หรือผู้ดูแลระบบ'], 403);
    $now = date('Y-m-d H:i:s');
    $st = mysqli_prepare($connect, "UPDATE tb_stock_pr SET status = 0, updated_at = ?, updated_by = ? WHERE pr_id = ? AND ag_id = ?");
    mysqli_stmt_bind_param($st, 'ssii', $now, $me, $id, $ag);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    spr_out(['success' => true]);
}

spr_out(['success' => false, 'error' => 'unknown action'], 400);
