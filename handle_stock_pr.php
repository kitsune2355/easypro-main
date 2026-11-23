<?php
// ใบขออนุมัติจัดซื้อ (PR) — API ของเมนู "ใบขอซื้อ (PR)" ใน stock.php (หน่วยงานจาก session เท่านั้น)
//  GET  ?action=defaults                       ค่าเริ่มต้นของใบใหม่ (เลขที่ถัดไป, ชื่อผู้ขอ)
//  GET  ?action=products                       สินค้าในสต็อก (ช่องค้นหาเพิ่มรายการในฟอร์ม)
//  GET  ?action=list&q=&filter=(filterModel JSON)&limit=&offset=   รายการใบขอซื้อ (ตาราง ag-Grid แบบ server-side, กรองที่หัวคอลัมน์)
//  GET  ?action=get&id=                        ใบขอซื้อ + รายการ (ใช้ตอนแก้ไข)
//  POST ?action=save   (JSON)                  บันทึกใบใหม่ => ออกเลขที่
//  POST ?action=update (JSON, pr_id)           แก้ไขใบ (เลขที่เดิม) — ผู้สร้าง / admin / super_admin, เฉพาะใบที่ยังไม่ยกเลิก
//  POST ?action=cancel (id, reason)            ยกเลิกใบ (ต้องระบุเหตุผล) — ผู้สร้าง / admin / super_admin
//  รายการในใบ: สินค้าในสต็อก { pd_id, qty, note } หรือสินค้านอกสต็อก { pd_id: 0, name, code, unit, qty, note }
//  ตำแหน่ง "ช่าง" ดูและพิมพ์ได้ แต่ บันทึก / แก้ไข / ยกเลิก ไม่ได้ (config_ctrl/stock_perm.php)
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
$canManage = stock_can_manage($connect);

// สิทธิ์แก้ไข/ยกเลิกใบ
function spr_can_change($row, $canManage, $isAdmin, $me) {
    return $canManage && (int)$row['status'] === 1 && ($isAdmin || (string)$row['created_by'] === $me);
}

// คงเหลือ / จุดสั่งซื้อรวมทุกคลัง (เฉพาะคลังที่เปิดใช้งาน) ของสินค้าที่ระบุ — [pd_id => [stock, min, wh[]]]
function spr_stock_info($connect, $ag, $ids) {
    $out = [];
    if (!$ids) return $out;
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $st = mysqli_prepare($connect, "SELECT ps.pd_id, ps.qty, ps.qty_min, w.wh_name FROM product_stocks ps
            INNER JOIN tb_repair_product p ON p.pd_id = ps.pd_id AND p.ag_id = ?
            INNER JOIN tb_wh_stock w ON w.id = ps.wh_id
            WHERE ps.ps_status = '0' AND ps.pd_id IN ($ph)");
    $bind = array_merge([$ag], $ids);
    mysqli_stmt_bind_param($st, 'i' . str_repeat('i', count($ids)), ...$bind);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    while ($r = mysqli_fetch_assoc($rs)) {
        $id = (int)$r['pd_id'];
        if (!isset($out[$id])) $out[$id] = ['stock' => 0, 'min' => 0, 'wh' => []];
        $out[$id]['stock'] += (int)$r['qty'];
        $out[$id]['min'] += (int)$r['qty_min'];
        if (!in_array($r['wh_name'], $out[$id]['wh'], true)) $out[$id]['wh'][] = (string)$r['wh_name'];
    }
    mysqli_stmt_close($st);
    return $out;
}

// เงื่อนไข SQL ของตัวกรองหัวคอลัมน์ 1 เงื่อนไข (เฉพาะคอลัมน์ที่อนุญาต) — เติมพารามิเตอร์ใน $types / $params
function spr_filter_sql($col, $c, &$types, &$params) {
    $textCols = ['pr_no' => 'h.pr_no', 'req_name' => 'h.req_name', 'remark' => 'h.remark'];
    $dateCols = ['pr_date' => 'h.pr_date', 'deliver_date' => 'h.deliver_date'];
    $ft = (string)($c['filterType'] ?? '');
    $type = (string)($c['type'] ?? '');
    if ($ft === 'text' && (isset($textCols[$col]) || $col === 'items')) {
        if ($type === 'blank' || $type === 'notBlank') {
            if ($col === 'items') return '';
            return $type === 'blank' ? "COALESCE({$textCols[$col]}, '') = ''" : "COALESCE({$textCols[$col]}, '') <> ''";
        }
        $v = trim((string)($c['filter'] ?? ''));
        if ($v === '') return '';
        $map = ['contains' => ['LIKE', "%$v%"], 'notContains' => ['NOT LIKE', "%$v%"], 'equals' => ['=', $v], 'notEqual' => ['<>', $v], 'startsWith' => ['LIKE', "$v%"], 'endsWith' => ['LIKE', "%$v"]];
        if (!isset($map[$type])) $type = 'contains';
        list($op, $val) = $map[$type];
        if ($col === 'items') {   // รายการสินค้า: ค้นรหัส / ชื่อสินค้าในใบ
            $neg = ($type === 'notContains' || $type === 'notEqual');
            $op2 = $neg ? ($type === 'notEqual' ? '=' : 'LIKE') : $op;
            $types .= 'ss'; $params[] = $val; $params[] = $val;
            return ($neg ? 'NOT ' : '') . "EXISTS (SELECT 1 FROM tb_stock_pr_item fi WHERE fi.pr_id = h.pr_id AND (fi.pd_code $op2 ? OR fi.pd_name $op2 ?))";
        }
        $types .= 's'; $params[] = $val;
        return "{$textCols[$col]} $op ?";
    }
    if ($ft === 'date' && isset($dateCols[$col])) {
        $f = $dateCols[$col];
        if ($type === 'blank') return "$f IS NULL";
        if ($type === 'notBlank') return "$f IS NOT NULL";
        $d1 = spr_date(substr((string)($c['dateFrom'] ?? ''), 0, 10));
        $d2 = spr_date(substr((string)($c['dateTo'] ?? ''), 0, 10));
        if (!$d1) return '';
        $ops = ['equals' => '=', 'notEqual' => '<>', 'lessThan' => '<', 'greaterThan' => '>'];
        if ($type === 'inRange' && $d2) { $types .= 'ss'; $params[] = $d1; $params[] = $d2; return "$f BETWEEN ? AND ?"; }
        $types .= 's'; $params[] = $d1;
        return "$f " . ($ops[$type] ?? '=') . " ?";
    }
    if ($ft === 'set' && $col === 'status') {
        $vals = array_values(array_intersect(array_map('strval', (array)($c['values'] ?? [])), ['0', '1']));
        if (!$vals) return '1 = 0';   // ไม่ได้เลือกสถานะใดเลย
        return 'h.status IN (' . implode(',', array_map('intval', $vals)) . ')';
    }
    return '';
}

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

// ---------- สินค้าในสต็อก (ช่องค้นหาเพิ่มรายการ) ----------
if ($action === 'products') {
    $st = mysqli_prepare($connect, "SELECT p.pd_id, p.pd_gen_code, p.pd_details_head, p.pd_details, p.pd_unit
            FROM tb_repair_product p
            WHERE p.ag_id = ? AND p.pd_status = 0 AND EXISTS (SELECT 1 FROM product_stocks ps WHERE ps.pd_id = p.pd_id AND ps.ps_status = '0')
            ORDER BY p.pd_gen_code");
    mysqli_stmt_bind_param($st, 'i', $ag);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $items = [];
    while ($r = mysqli_fetch_assoc($rs)) {
        $items[(int)$r['pd_id']] = ['pd_id' => (int)$r['pd_id'], 'code' => (string)$r['pd_gen_code'], 'name' => trim((string)$r['pd_details_head']),
            'detail' => trim((string)$r['pd_details']), 'unit' => (string)$r['pd_unit']];
    }
    mysqli_stmt_close($st);
    foreach (spr_stock_info($connect, $ag, array_keys($items)) as $id => $s) $items[$id] += $s;
    spr_out(['success' => true, 'items' => array_values($items)]);
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
    // ตัวกรองที่หัวคอลัมน์ของตาราง (ag-Grid filterModel)
    $fm = json_decode((string)($_GET['filter'] ?? ''), true);
    if (is_array($fm)) {
        foreach ($fm as $col => $f) {
            if (!is_array($f)) continue;
            $conds = isset($f['conditions']) && is_array($f['conditions']) ? $f['conditions'] : [$f];
            $join = (isset($f['operator']) && strtoupper($f['operator']) === 'OR') ? ' OR ' : ' AND ';
            $parts = [];
            foreach ($conds as $c) {
                $sql = spr_filter_sql($col, $c, $types, $params);
                if ($sql !== '') $parts[] = $sql;
            }
            if ($parts) $where .= ' AND (' . implode($join, $parts) . ')';
        }
    }

    // เรียงตามหัวคอลัมน์ (ag-Grid) — เฉพาะคอลัมน์ที่อนุญาต · ค่าเริ่มต้น: วันที่ขอซื้อล่าสุดก่อน
    $sortCols = ['pr_no' => 'h.pr_no', 'pr_date' => 'h.pr_date', 'req_name' => 'h.req_name', 'deliver_date' => 'h.deliver_date', 'status' => 'h.status', 'remark' => 'h.remark'];
    $order = [];
    foreach ((array)json_decode((string)($_GET['sort'] ?? ''), true) as $so) {
        $cid = is_array($so) ? (string)($so['colId'] ?? '') : '';
        if (isset($sortCols[$cid])) $order[] = $sortCols[$cid] . (strtolower((string)($so['sort'] ?? '')) === 'desc' ? ' DESC' : ' ASC');
    }
    $order = $order ? implode(', ', $order) . ', h.pr_id DESC' : 'h.pr_date DESC, h.pr_id DESC';
    $st = mysqli_prepare($connect, "SELECT COUNT(*) FROM tb_stock_pr h $where");
    mysqli_stmt_bind_param($st, $types, ...$params);
    mysqli_stmt_execute($st);
    mysqli_stmt_bind_result($st, $total);
    mysqli_stmt_fetch($st);
    mysqli_stmt_close($st);
    $sql = "SELECT h.pr_id, h.pr_no, h.pr_date, h.deliver_date, h.req_name, h.remark, h.status, h.created_by, h.created_at,
                   h.cancel_reason, h.cancelled_at, h.cancelled_by,
                   (SELECT COUNT(*) FROM tb_stock_pr_item i WHERE i.pr_id = h.pr_id) AS n_items
            FROM tb_stock_pr h $where ORDER BY $order LIMIT $per OFFSET $off";
    $st = mysqli_prepare($connect, $sql);
    mysqli_stmt_bind_param($st, $types, ...$params);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $rows = [];
    while ($r = mysqli_fetch_assoc($rs)) {
        $r['pr_id'] = (int)$r['pr_id'];
        $r['status'] = (int)$r['status'];
        $r['n_items'] = (int)$r['n_items'];
        $r['can_edit'] = $r['can_cancel'] = spr_can_change($r, $canManage, $isAdmin, $me);
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
        $st = mysqli_prepare($connect, "SELECT pr_id, pd_id, pd_code, pd_name, pd_unit, qty, note FROM tb_stock_pr_item WHERE pr_id IN ($ph) ORDER BY pr_id, line_no");
        mysqli_stmt_bind_param($st, str_repeat('i', count($ids)), ...$ids);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        while ($it = mysqli_fetch_assoc($rs)) {
            $k = $idx[(int)$it['pr_id']];
            unset($it['pr_id']);
            $it['qty'] = (float)$it['qty'];
            $it['pd_id'] = $it['pd_id'] === null ? null : (int)$it['pd_id'];
            $rows[$k]['items'][] = $it;
        }
        mysqli_stmt_close($st);
    }
    spr_out(['success' => true, 'rows' => $rows, 'total' => (int)$total]);
}

// ---------- ใบเดียว (ใช้ตอนแก้ไข) ----------
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
    while ($r = mysqli_fetch_assoc($rs)) {
        $r['qty'] = (float)$r['qty'];
        $r['pd_id'] = $r['pd_id'] === null ? null : (int)$r['pd_id'];
        $items[] = $r;
    }
    mysqli_stmt_close($st);
    $info = spr_stock_info($connect, $ag, array_values(array_filter(array_map(function ($i) { return (int)$i['pd_id']; }, $items))));
    foreach ($items as &$i) if ($i['pd_id'] && isset($info[$i['pd_id']])) $i += $info[$i['pd_id']];
    unset($i);
    $h['can_edit'] = spr_can_change($h, $canManage, $isAdmin, $me);
    spr_out(['success' => true, 'pr' => $h, 'items' => $items]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') spr_out(['success' => false, 'error' => 'method not allowed'], 405);
if (!$canManage) spr_out(['success' => false, 'error' => 'ไม่มีสิทธิ์: ตำแหน่งช่างสร้าง แก้ไข หรือยกเลิกใบขอซื้อไม่ได้'], 403);

// หัวเอกสาร + รายการจาก JSON — สินค้าในสต็อกเก็บชื่อ/รหัส/หน่วยจากฐานข้อมูล ณ วันที่บันทึก, สินค้านอกสต็อกใช้ค่าที่กรอก
function spr_parse_input($connect, $ag) {
    $in = json_decode(file_get_contents('php://input'), true);
    if (!is_array($in)) spr_out(['success' => false, 'error' => 'ข้อมูลไม่ถูกต้อง'], 400);
    $head = [
        'pr_date' => spr_date($in['pr_date'] ?? '') ?: date('Y-m-d'),
        'deliver_date' => spr_date($in['deliver_date'] ?? ''),
        'remark' => spr_str($in['remark'] ?? '', 2000),
        'req_name' => spr_str($in['req_name'] ?? '', 150),
    ];
    if ($head['req_name'] === '') spr_out(['success' => false, 'error' => 'กรุณากรอกชื่อผู้ขอซื้อ']);
    $lines = [];
    foreach ((array)($in['items'] ?? []) as $it) {
        $pid = (int)($it['pd_id'] ?? 0);
        $qty = round((float)($it['qty'] ?? 0), 2);
        $note = spr_str($it['note'] ?? '', 255);
        if ($pid > 0) {
            $lines[] = ['pd_id' => $pid, 'qty' => $qty, 'note' => $note];
        } else {
            $name = spr_str($it['name'] ?? '', 500);
            if ($name === '') spr_out(['success' => false, 'error' => 'กรุณากรอกชื่อรายการนอกสต็อกให้ครบ']);
            $lines[] = ['pd_id' => null, 'qty' => $qty, 'note' => $note, 'name' => $name, 'code' => spr_str($it['code'] ?? '', 100), 'unit' => spr_str($it['unit'] ?? '', 100)];
        }
    }
    if (!$lines) spr_out(['success' => false, 'error' => 'กรุณาเพิ่มรายการอย่างน้อย 1 รายการ']);
    foreach ($lines as $l) if (!($l['qty'] > 0)) spr_out(['success' => false, 'error' => 'กรุณาระบุจำนวนมากกว่า 0 ทุกรายการ']);
    if (count($lines) > 200) spr_out(['success' => false, 'error' => 'เพิ่มได้ไม่เกิน 200 รายการต่อใบ']);

    $ids = array_values(array_unique(array_filter(array_map(function ($l) { return (int)$l['pd_id']; }, $lines))));
    if ($ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $st = mysqli_prepare($connect, "SELECT pd_id, pd_gen_code, pd_details_head, pd_details, pd_unit FROM tb_repair_product WHERE ag_id = ? AND pd_id IN ($ph)");
        $bind = array_merge([$ag], $ids);
        mysqli_stmt_bind_param($st, 'i' . str_repeat('i', count($ids)), ...$bind);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        $prod = [];
        while ($r = mysqli_fetch_assoc($rs)) $prod[(int)$r['pd_id']] = $r;
        mysqli_stmt_close($st);
        foreach ($lines as &$l) {
            if (!$l['pd_id']) continue;
            if (!isset($prod[$l['pd_id']])) spr_out(['success' => false, 'error' => 'พบสินค้าที่ไม่ใช่ของหน่วยงานนี้ กรุณาโหลดหน้าใหม่']);
            $p = $prod[$l['pd_id']];
            $name = trim((string)$p['pd_details_head']);
            $detail = trim((string)$p['pd_details']);
            if ($detail !== '' && $detail !== $name) $name .= ' ' . $detail;
            $l['name'] = $name;
            $l['code'] = (string)$p['pd_gen_code'];
            $l['unit'] = (string)$p['pd_unit'];
        }
        unset($l);
    }
    return [$head, $lines];
}

function spr_insert_lines($connect, $prId, $lines, $now, $me) {
    $st = mysqli_prepare($connect, "INSERT INTO tb_stock_pr_item (pr_id, line_no, pd_id, pd_code, pd_name, pd_unit, qty, note, created_at, created_by, updated_at, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $lineNo = 0;
    foreach ($lines as $l) {
        $lineNo++;
        $pid = $l['pd_id'] ?: null;
        mysqli_stmt_bind_param($st, 'iiisssdsssss', $prId, $lineNo, $pid, $l['code'], $l['name'], $l['unit'], $l['qty'], $l['note'], $now, $me, $now, $me);
        if (!mysqli_stmt_execute($st)) { mysqli_stmt_close($st); return false; }
    }
    mysqli_stmt_close($st);
    return true;
}

// ใบเดิม (ตรวจสิทธิ์แก้ไข/ยกเลิก)
function spr_load_for_change($connect, $ag, $id, $canManage, $isAdmin, $me) {
    $st = mysqli_prepare($connect, "SELECT pr_id, pr_no, created_by, status FROM tb_stock_pr WHERE pr_id = ? AND ag_id = ? LIMIT 1");
    mysqli_stmt_bind_param($st, 'ii', $id, $ag);
    mysqli_stmt_execute($st);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
    mysqli_stmt_close($st);
    if (!$row) spr_out(['success' => false, 'error' => 'ไม่พบใบขอซื้อ'], 404);
    if ((int)$row['status'] !== 1) spr_out(['success' => false, 'error' => 'ใบนี้ถูกยกเลิกแล้ว']);
    if (!spr_can_change($row, $canManage, $isAdmin, $me)) spr_out(['success' => false, 'error' => 'แก้ไข/ยกเลิกได้เฉพาะผู้สร้างใบ หรือผู้ดูแลระบบ'], 403);
    return $row;
}

// ---------- บันทึกใบใหม่ ----------
if ($action === 'save') {
    list($head, $lines) = spr_parse_input($connect, $ag);
    $now = date('Y-m-d H:i:s');
    // ออกเลขที่: ชนกับคนอื่นที่บันทึกพร้อมกัน (UNIQUE ag_id+pr_no) => ลองเลขถัดไป
    $prId = 0;
    $prNo = '';
    for ($try = 0; $try < 5 && !$prId; $try++) {
        mysqli_begin_transaction($connect);
        $prNo = spr_next_no($connect, $ag, $head['pr_date']);
        $st = mysqli_prepare($connect, "INSERT INTO tb_stock_pr (ag_id, pr_no, pr_date, deliver_date, remark, req_name, status, created_at, created_by, updated_at, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($st, 'isssssssss', $ag, $prNo, $head['pr_date'], $head['deliver_date'], $head['remark'], $head['req_name'], $now, $me, $now, $me);
        $ok = @mysqli_stmt_execute($st);
        $err = mysqli_stmt_errno($st);
        $newId = (int)mysqli_insert_id($connect);
        mysqli_stmt_close($st);
        if (!$ok) {
            mysqli_rollback($connect);
            if ($err === 1062) continue;   // เลขซ้ำ
            spr_out(['success' => false, 'error' => 'บันทึกไม่สำเร็จ'], 500);
        }
        if (!spr_insert_lines($connect, $newId, $lines, $now, $me)) { mysqli_rollback($connect); spr_out(['success' => false, 'error' => 'บันทึกรายการไม่สำเร็จ'], 500); }
        mysqli_commit($connect);
        $prId = $newId;
    }
    if (!$prId) spr_out(['success' => false, 'error' => 'ออกเลขที่เอกสารไม่สำเร็จ กรุณาลองใหม่'], 409);
    spr_out(['success' => true, 'pr_id' => $prId, 'pr_no' => $prNo]);
}

// ---------- แก้ไขใบ (เลขที่เดิม) ----------
if ($action === 'update') {
    $id = (int)($_GET['id'] ?? 0);
    $row = spr_load_for_change($connect, $ag, $id, $canManage, $isAdmin, $me);
    list($head, $lines) = spr_parse_input($connect, $ag);
    $now = date('Y-m-d H:i:s');
    mysqli_begin_transaction($connect);
    $st = mysqli_prepare($connect, "UPDATE tb_stock_pr SET pr_date = ?, deliver_date = ?, remark = ?, req_name = ?, updated_at = ?, updated_by = ? WHERE pr_id = ? AND ag_id = ? AND status = 1");
    mysqli_stmt_bind_param($st, 'ssssssii', $head['pr_date'], $head['deliver_date'], $head['remark'], $head['req_name'], $now, $me, $id, $ag);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    if ($ok) {
        $st = mysqli_prepare($connect, "DELETE FROM tb_stock_pr_item WHERE pr_id = ?");
        mysqli_stmt_bind_param($st, 'i', $id);
        $ok = mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
    }
    if ($ok) $ok = spr_insert_lines($connect, $id, $lines, $now, $me);
    if (!$ok) { mysqli_rollback($connect); spr_out(['success' => false, 'error' => 'บันทึกการแก้ไขไม่สำเร็จ'], 500); }
    mysqli_commit($connect);
    spr_out(['success' => true, 'pr_id' => $id, 'pr_no' => $row['pr_no']]);
}

// ---------- ยกเลิก (ต้องระบุเหตุผล) ----------
if ($action === 'cancel') {
    $id = (int)($_POST['id'] ?? 0);
    $reason = spr_str($_POST['reason'] ?? '', 500);
    if ($reason === '') spr_out(['success' => false, 'error' => 'กรุณาระบุเหตุผลที่ยกเลิก']);
    spr_load_for_change($connect, $ag, $id, $canManage, $isAdmin, $me);
    $now = date('Y-m-d H:i:s');
    $st = mysqli_prepare($connect, "UPDATE tb_stock_pr SET status = 0, cancel_reason = ?, cancelled_at = ?, cancelled_by = ?, updated_at = ?, updated_by = ? WHERE pr_id = ? AND ag_id = ?");
    mysqli_stmt_bind_param($st, 'sssssii', $reason, $now, $me, $now, $me, $id, $ag);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    spr_out(['success' => true]);
}

spr_out(['success' => false, 'error' => 'unknown action'], 400);
