<?php
// handle_repair_hold.php — พักงานซ่อม / กลับมาดำเนินการ (ใช้กับหน้าข้อมูลแจ้งซ่อม: รายละเอียดงานซ่อม > การดำเนินการ)
//  get           ?rp_id=        รอบที่กำลังพัก (พร้อมอะไหล่) + ประวัติการพักงานทั้งหมด
//  search_parts  ?q=            ค้นหาอะไหล่จากทุกคลังของหน่วยงาน (คงเหลือ / จองแล้ว / ใช้ได้)
//  hold          POST JSON      { rp_id, reason, eta, note, detail{}, parts[{ps_id, qty}] }
//  resume        POST JSON      { rp_id } => คืนรายการอะไหล่ที่เข้าแล้ว ให้หน้าใบงานเพิ่มลงส่วนปิดงาน (ตัดสต๊อกตอนบันทึกตามเดิม)
//  เงื่อนไขของแต่ละการทำงานอธิบายไว้ใน config_ctrl/repair_hold.php
@session_start();
include 'config_ctrl/connect.php';
require_once 'config_ctrl/repair_hold.php';

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Bangkok');

function out($arr) { echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
function fail($msg, $code = 200) { http_response_code($code); out(['success' => false, 'error' => $msg]); }

if (empty($_SESSION['sess_user_id_es'])) fail('กรุณาเข้าสู่ระบบใหม่', 401);
$AG = (string)(int)($_SESSION['sess_user_agency'] ?? 0);
$ME = (string)$_SESSION['sess_user_id_es'];
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];

rh_ensure($connect);
$REASONS = rh_reasons();

// งานซ่อมของหน่วยงานที่ล็อกอินอยู่เท่านั้น
function job_or_fail($connect, $rp_id, $AG) {
    $r = rh_rows($connect, "SELECT id, rp_format, status, ag_id, received_by FROM repair_requests WHERE id = ?", 'i', [(int)$rp_id]);
    if (!$r || (string)$r[0]['ag_id'] !== $AG) fail('ไม่พบงานซ่อมนี้ในหน่วยงานของคุณ');
    return $r[0];
}

// รายการอะไหล่ของรอบพักงาน พร้อมชื่อ/คลัง/ข้อมูลที่หน้าใบงานใช้เพิ่มลงส่วนปิดงาน
function hold_parts($connect, $hold_id) {
    return array_map(function ($p) {
        return [
            'id' => (int)$p['id'], 'ps_id' => (int)$p['ps_id'], 'pd_id' => (int)$p['pd_id'], 'wh_id' => (int)$p['wh_id'], 'qty' => (int)$p['qty'],
            'arrived_at' => $p['arrived_at'], 'name' => (string)$p['pd_details_head'], 'code' => (string)$p['pd_gen_code'],
            'detail' => (string)$p['pd_details'], 'brand' => (string)$p['pd_brand'], 'model' => (string)$p['pd_model'],
            'price' => (float)$p['pd_price'], 'unit' => (string)$p['pd_unit'], 'wh_name' => (string)$p['wh_name'],
        ];
    }, rh_rows($connect, "SELECT p.*, pr.pd_details_head, pr.pd_gen_code, pr.pd_details, pr.pd_brand, pr.pd_model, pr.pd_price, pr.pd_unit, w.wh_name
        FROM repair_hold_parts p
        LEFT JOIN tb_repair_product pr ON pr.pd_id = p.pd_id
        LEFT JOIN tb_wh_stock w ON w.id = p.wh_id
        WHERE p.hold_id = ? ORDER BY p.id", 'i', [(int)$hold_id]));
}

function hold_out($connect, $h) {
    $names = rh_rows($connect, "SELECT user_id, COALESCE(NULLIF(user_name, ''), NULLIF(user_fname, ''), user_id) AS n FROM tb_user WHERE user_id IN (?, ?)",
        'ss', [(string)$h['started_by'], (string)($h['ended_by'] ?? '')]);
    $nm = []; foreach ($names as $x) $nm[$x['user_id']] = $x['n'];
    return [
        'id' => (int)$h['id'], 'reason' => $h['reason'], 'reason_label' => rh_reasons()[$h['reason']] ?? $h['reason'],
        'detail' => json_decode((string)$h['detail_json'], true) ?: new stdClass(), 'eta' => $h['eta'], 'note' => (string)$h['note'],
        'started_at' => $h['started_at'], 'started_by' => $nm[$h['started_by']] ?? $h['started_by'],
        'ended_at' => $h['ended_at'], 'ended_by' => $h['ended_by'] ? ($nm[$h['ended_by']] ?? $h['ended_by']) : null, 'end_type' => $h['end_type'],
        'parts' => $h['reason'] === 'parts' ? hold_parts($connect, $h['id']) : [],
    ];
}

try {
    if ($action === 'get') {
        $job = job_or_fail($connect, $_GET['rp_id'] ?? 0, $AG);
        $all = rh_rows($connect, "SELECT * FROM repair_holds WHERE rp_id = ? ORDER BY id DESC", 'i', [(int)$job['id']]);
        $active = null; $history = [];
        foreach ($all as $h) { $o = hold_out($connect, $h); if ($h['ended_at'] === null && !$active) $active = $o; else $history[] = $o; }
        out(['success' => true, 'status' => $job['status'], 'active' => $active, 'history' => $history, 'reasons' => $REASONS]);
    }

    if ($action === 'search_parts') {
        $q = trim((string)($_GET['q'] ?? ''));
        if ($q === '') out(['success' => true, 'data' => []]);
        $like = '%' . $q . '%';
        $rows = rh_rows($connect, "SELECT ps.id AS ps_id, p.pd_id, ps.wh_id, w.wh_name, p.pd_details_head AS name, p.pd_gen_code AS code,
                p.pd_model AS model, p.pd_unit AS unit, ps.qty
            FROM product_stocks ps
            JOIN tb_repair_product p ON p.pd_id = ps.pd_id
            JOIN tb_wh_stock w ON w.id = ps.wh_id
            WHERE p.ag_id = ? AND w.ag_id = ? AND ps.ps_status = '0' AND w.status = '0'
              AND (p.pd_details_head LIKE ? OR p.pd_gen_code LIKE ? OR p.pd_model LIKE ? OR p.pd_details LIKE ?)
            ORDER BY p.pd_details_head, w.wh_name LIMIT 40", 'ssssss', [$AG, $AG, $like, $like, $like, $like]);
        foreach ($rows as &$r) {
            $r['qty'] = (int)$r['qty'];
            $r['reserved'] = rh_reserved($connect, $r['pd_id'], $r['wh_id']);
            $r['avail'] = max(0, $r['qty'] - $r['reserved']);
        }
        unset($r);
        // คลังที่มีของใช้ได้อยู่บนสุด
        usort($rows, function ($a, $b) { return $b['avail'] <=> $a['avail'] ?: strcmp($a['name'], $b['name']); });
        out(['success' => true, 'data' => $rows]);
    }

    if ($action === 'hold') {
        $job = job_or_fail($connect, $input['rp_id'] ?? 0, $AG);
        if (strtolower($job['status']) !== 'inprogress') fail('พักงานได้เฉพาะงานที่รับงานแล้วและยังไม่ปิดงาน');
        if (rh_active($connect, $job['id'])) fail('งานนี้กำลังพักงานอยู่แล้ว');
        $reason = (string)($input['reason'] ?? '');
        if (!isset($REASONS[$reason])) fail('กรุณาเลือกเหตุผลที่พักงาน');
        $eta = trim((string)($input['eta'] ?? ''));
        if ($eta !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $eta)) fail('วันที่คาดว่าจะกลับมาทำไม่ถูกต้อง');
        if (in_array($reason, ['parts', 'supplier'], true) && $eta === '') fail('กรุณาระบุวันที่คาดว่าจะกลับมาทำ');
        if ($eta !== '' && $eta < date('Y-m-d')) fail('วันที่คาดว่าจะกลับมาทำต้องไม่ใช่วันที่ผ่านมาแล้ว');
        $note = trim((string)($input['note'] ?? ''));

        // รายละเอียดตามเหตุผล (เก็บเฉพาะช่องที่รู้จัก)
        $d = is_array($input['detail'] ?? null) ? $input['detail'] : [];
        $keep = ['parts' => ['pr'], 'supplier' => ['company', 'contact', 'po', 'cost'], 'access' => ['person', 'appt'], 'other' => ['why']][$reason];
        $detail = [];
        foreach ($keep as $k) { $v = trim((string)($d[$k] ?? '')); if ($v !== '') $detail[$k] = mb_substr($v, 0, 255); }
        if ($reason === 'supplier' && empty($detail['company'])) fail('กรุณาระบุบริษัทผู้รับเหมา/ซัพ');
        if ($reason === 'other' && empty($detail['why'])) fail('กรุณาระบุเหตุผล');
        if (isset($detail['cost']) && !is_numeric($detail['cost'])) fail('ค่าใช้จ่ายต้องเป็นตัวเลข');
        if (isset($detail['appt']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $detail['appt'])) fail('วันนัดไม่ถูกต้อง');

        // อะไหล่ที่รอ: ต้องเป็นสินค้าในคลังของหน่วยงานนี้
        $parts = [];
        if ($reason === 'parts') {
            foreach ((array)($input['parts'] ?? []) as $p) {
                $ps = (int)($p['ps_id'] ?? 0); $qty = (int)($p['qty'] ?? 0);
                if ($ps <= 0) continue;
                if ($qty < 1) fail('จำนวนอะไหล่ต้องอย่างน้อย 1');
                if (isset($parts[$ps])) fail('มีรายการอะไหล่ซ้ำ');
                $r = rh_rows($connect, "SELECT ps.pd_id, ps.wh_id, ps.qty FROM product_stocks ps JOIN tb_wh_stock w ON w.id = ps.wh_id
                    WHERE ps.id = ? AND w.ag_id = ?", 'is', [$ps, $AG]);
                if (!$r) fail('ไม่พบอะไหล่ที่เลือกในคลังของหน่วยงานนี้');
                $parts[$ps] = ['pd_id' => (int)$r[0]['pd_id'], 'wh_id' => (int)$r[0]['wh_id'], 'stock' => (int)$r[0]['qty'], 'qty' => $qty];
            }
            if (!$parts) fail('ค้นหาแล้วเลือกอะไหล่ที่รออย่างน้อย 1 รายการ');
        }

        $connect->begin_transaction();
        $hid = rh_exec($connect, "INSERT INTO repair_holds (rp_id, ag_id, reason, detail_json, eta, note, started_at, started_by) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)",
            'issssss', [(int)$job['id'], $AG, $reason, json_encode($detail, JSON_UNESCAPED_UNICODE), $eta === '' ? null : $eta, $note, $ME]);
        foreach ($parts as $ps => $p) {
            // ของในคลังพออยู่แล้ว => จองให้ทันที (นับเป็นเข้าแล้ว)
            $ok = $p['stock'] - rh_reserved($connect, $p['pd_id'], $p['wh_id']) >= $p['qty'];
            rh_exec($connect, "INSERT INTO repair_hold_parts (hold_id, rp_id, ps_id, pd_id, wh_id, qty, arrived_at) VALUES (?, ?, ?, ?, ?, ?, " . ($ok ? 'NOW()' : 'NULL') . ")",
                'iiiiii', [$hid, (int)$job['id'], $ps, $p['pd_id'], $p['wh_id'], $p['qty']]);
        }
        rh_notify($connect, 'repair_hold', $job['id'], "{$job['rp_format']} พักงาน: {$REASONS[$reason]}" . ($eta ? ' (คาดว่ากลับมาทำ ' . date('d/m/', strtotime($eta)) . ((int)substr($eta, 0, 4) + 543) . ')' : ''));
        $connect->commit();
        $h = rh_rows($connect, "SELECT * FROM repair_holds WHERE id = ?", 'i', [$hid])[0];
        out(['success' => true, 'active' => hold_out($connect, $h)]);
    }

    if ($action === 'resume') {
        $job = job_or_fail($connect, $input['rp_id'] ?? 0, $AG);
        $h = rh_active($connect, $job['id']);
        if (!$h) fail('งานนี้ไม่ได้พักงานอยู่');
        $out = hold_out($connect, $h);
        rh_exec($connect, "UPDATE repair_holds SET ended_at = NOW(), ended_by = ?, end_type = 'resume' WHERE id = ?", 'si', [$ME, (int)$h['id']]);
        $got = array_values(array_filter($out['parts'], function ($p) { return $p['arrived_at'] !== null; }));
        $miss = array_values(array_filter($out['parts'], function ($p) { return $p['arrived_at'] === null; }));
        out(['success' => true, 'arrived' => $got, 'missing' => $miss]);
    }

    fail('ไม่รู้จักคำสั่งนี้');
} catch (Throwable $e) {
    try { $connect->rollback(); } catch (Throwable $e2) {}
    fail('เกิดข้อผิดพลาด: ' . $e->getMessage(), 500);
}
