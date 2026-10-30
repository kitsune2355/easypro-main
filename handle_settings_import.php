<?php
// handle_settings_import.php — นำเข้าข้อมูลหน้าตั้งค่าจาก Excel + Template (js/settings_import.js)
//  ?entity=<ชุดข้อมูล>&action=schema | export | preview | commit
//  schema  : ช่อง + ค่าที่ใช้ได้ (Template)        export : ข้อมูลปัจจุบันในรูปแบบเดียวกับ Template (ส่งออก → แก้ → นำเข้ากลับ)
//  preview : ตรวจทุกแถว ไม่บันทึก                  commit : ตรวจซ้ำแล้วบันทึกทั้งชุดในทรานแซกชันเดียว (ผิดแถวเดียว = ไม่บันทึกเลย)
//  ชุดข้อมูลแต่ละหน้า: config_ctrl/settings_import_entities.php
//  สิทธิ์: admin / super_admin (เหมือนเมนูตั้งค่าข้อมูล) · หน่วยงาน = หน่วยงานที่เลือกอยู่ใน session (ไม่รับจากหน้าเว็บ)
@session_start();
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
ob_start();
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        while (ob_get_level()) ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'เกิดข้อผิดพลาดในระบบ: ' . $e['message']], JSON_UNESCAPED_UNICODE);
    }
});
function sxi_out($data)
{
    while (ob_get_level()) ob_end_clean();
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$level = $_SESSION['sess_user_level_es'] ?? '';
if (empty($_SESSION['sess_user_id_es']) || !in_array($level, ['admin', 'super_admin'], true)) sxi_out(['success' => false, 'error' => 'ไม่มีสิทธิ์นำเข้าข้อมูลหน้าตั้งค่า (กรุณาเข้าสู่ระบบใหม่)']);
$AG = (string)($_SESSION['sess_user_agency'] ?? '');
$UID = (string)$_SESSION['sess_user_id_es'];
if ($AG === '' || $AG === '0') sxi_out(['success' => false, 'error' => 'กรุณาเลือกหน่วยงานก่อนนำเข้าข้อมูล']);

include 'config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');
require_once 'config_ctrl/settings_import_entities.php';

$entities = sxi_entities();
$entity = $_GET['entity'] ?? '';
$action = $_GET['action'] ?? '';
if (!isset($entities[$entity])) sxi_out(['success' => false, 'error' => 'ไม่รู้จักชุดข้อมูล: ' . $entity]);
$E = $entities[$entity];
$ctx = ['c' => $connect, 'ag' => $AG, 'uid' => $UID, 'level' => $level];

// ---------- ค่าที่ใช้ได้ของช่องแบบรายการ: ชื่อรายการ => [ชื่อแสดง => id] (+ _idx = ดัชนีตัวพิมพ์เล็กสำหรับเทียบ) ----------
function sxi_lookups($E, $ctx)
{
    $lk = isset($E['lookups']) ? call_user_func($E['lookups'], $ctx) : [];
    $idx = [];
    foreach ($lk as $name => $map) if (is_array($map) && $name[0] !== '_') foreach ($map as $disp => $id) $idx[$name][sxi_key($disp)] = $id;
    $lk['_idx'] = $idx;
    return $lk;
}
function sxi_key($v)
{
    $v = preg_replace('/\s+/u', ' ', trim((string)$v));
    return function_exists('mb_strtolower') ? mb_strtolower($v, 'UTF-8') : strtolower($v);
}

// วันที่จาก Excel: 31/12/2026, 2026-12-31, 31-12-2569 (พ.ศ.), เลขวันที่ของ Excel (46000) => Y-m-d หรือ null
function sxi_date($v)
{
    $v = trim((string)$v);
    if ($v === '') return '';
    if (preg_match('/^\d{4,5}(\.\d+)?$/', $v) && (float)$v > 20000 && (float)$v < 80000) return gmdate('Y-m-d', (int)round(((float)$v - 25569) * 86400));
    if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})/', $v, $m)) { $y = (int)$m[1]; $mo = (int)$m[2]; $d = (int)$m[3]; }
    elseif (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2,4})/', $v, $m)) { $d = (int)$m[1]; $mo = (int)$m[2]; $y = (int)$m[3]; if ($y < 100) $y += 2000; }
    else return null;
    if ($y > 2400) $y -= 543;   // พ.ศ.
    return checkdate($mo, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : null;
}

// ตรวจ + แปลงค่า 1 แถว => ['v' => ค่าที่บันทึกได้, 'errors' => [], 'bad' => [ช่องที่ผิด]]
function sxi_validate($E, $row, $lk)
{
    $v = []; $errors = []; $bad = [];
    foreach ($E['fields'] as $f) {
        $k = $f['k'];
        $raw = trim(preg_replace('/\s+/u', ' ', (string)($row[$k] ?? '')));
        $type = $f['type'] ?? 'text';
        if ($raw === '') {
            if (!empty($f['required'])) { $errors[] = 'ไม่ได้กรอก ' . $f['label']; $bad[] = $k; }
            $v[$k] = ($type === 'lookup' || $type === 'choice') ? null : '';   // null = ไม่ระบุ (เพิ่มใหม่ใช้ค่าเริ่มต้น / แก้ไขไม่เปลี่ยน)
            continue;
        }
        if ($type === 'lookup') {
            $map = $lk['_idx'][$f['lookup'] ?? $k] ?? [];
            $id = $map[sxi_key($raw)] ?? null;
            if ($id === null) { $errors[] = 'ไม่พบ' . $f['label'] . ' "' . $raw . '" ในระบบ'; $bad[] = $k; }
            $v[$k] = $id;
        } elseif ($type === 'choice') {
            $hit = null;
            foreach ($f['choices'] as $val => $names) foreach ((array)$names as $n) if (sxi_key($n) === sxi_key($raw)) $hit = $val;
            if ($hit === null) { $errors[] = $f['label'] . ' ต้องเป็น: ' . implode(' / ', array_map(function ($n) { return is_array($n) ? $n[0] : $n; }, $f['choices'])); $bad[] = $k; }
            $v[$k] = $hit;
        } elseif ($type === 'date') {
            $d = sxi_date($raw);
            if ($d === null) { $errors[] = $f['label'] . ' ไม่ใช่วันที่ (เช่น 31/12/2026)'; $bad[] = $k; }
            $v[$k] = $d;
        } elseif ($type === 'number') {
            $n = str_replace(',', '', $raw);
            if (!is_numeric($n)) { $errors[] = $f['label'] . ' ต้องเป็นตัวเลข'; $bad[] = $k; }
            $v[$k] = is_numeric($n) ? $n + 0 : 0;
        } else {
            $max = $f['max'] ?? 255;
            if ((function_exists('mb_strlen') ? mb_strlen($raw, 'UTF-8') : strlen($raw)) > $max) { $errors[] = $f['label'] . ' ยาวเกิน ' . $max . ' ตัวอักษร'; $bad[] = $k; }
            $v[$k] = $raw;
        }
    }
    if (empty($errors) && isset($E['check'])) {   // ตรวจเฉพาะชุดข้อมูล (เช่น ห้องต้องอยู่ในชั้นของอาคารนั้น)
        foreach ((array)call_user_func_array($E['check'], [&$v, $lk]) as $k => $msg) { $errors[] = $msg; if (!is_int($k)) $bad[] = $k; }   // ตรวจเพิ่ม + เติมค่า (เช่น id ของอาคาร/ชั้น/ห้อง)
    }
    return ['v' => $v, 'errors' => $errors, 'bad' => array_values(array_unique($bad))];
}

// ตรวจทุกแถว: ค่า + รหัสซ้ำในไฟล์ + มีอยู่แล้วในระบบ
function sxi_check_rows($E, $ctx, $rows)
{
    $lk = sxi_lookups($E, $ctx);
    $existing = call_user_func($E['existing'], $ctx);   // sxi_key(รหัส) => id
    $seen = []; $out = [];
    foreach ($rows as $i => $row) {
        $r = sxi_validate($E, is_array($row) ? $row : [], $lk);
        $key = sxi_key(isset($E['keyOf']) ? call_user_func($E['keyOf'], $r['v']) : ($r['v'][$E['key']] ?? ''));
        if ($key !== '' && isset($seen[$key])) { $r['errors'][] = 'ซ้ำกับแถวที่ ' . ($seen[$key] + 1) . ' ในไฟล์'; $r['bad'][] = $E['key']; }
        elseif ($key !== '') $seen[$key] = $i;
        $r['id'] = $key !== '' && isset($existing[$key]) ? $existing[$key] : null;
        $r['status'] = $r['errors'] ? 'error' : ($r['id'] ? 'update' : 'new');
        $out[] = $r;
    }
    return [$out, $lk];
}

try {
    if ($action === 'schema' || $action === 'export') {
        $lk = sxi_lookups($E, $ctx);
        $fields = array_map(function ($f) {
            $o = ['k' => $f['k'], 'label' => $f['label'], 'required' => !empty($f['required']), 'hint' => $f['hint'] ?? '', 'example' => $f['example'] ?? '', 'kw' => $f['kw'] ?? []];
            if (($f['type'] ?? '') === 'choice') $o['hint'] = trim($o['hint'] . ' (' . implode(' / ', array_map(function ($n) { return is_array($n) ? $n[0] : $n; }, $f['choices'])) . ')');
            return $o;
        }, $E['fields']);
        // รายการค่าที่ใช้ได้ (ชื่อแสดง) สำหรับชีต "ค่าที่ใช้ได้" ของ Template
        $lists = [];
        foreach ($E['fields'] as $f) {
            if (($f['type'] ?? '') === 'lookup') $lists[$f['k']] = array_map('strval', array_keys($lk[$f['lookup'] ?? $f['k']] ?? []));
            if (($f['type'] ?? '') === 'choice') $lists[$f['k']] = array_map(function ($n) { return is_array($n) ? $n[0] : $n; }, array_values($f['choices']));
        }
        if (isset($E['names'])) foreach (call_user_func($E['names'], $ctx, $lk) as $k => $names) $lists[$k] = $names;   // รายการเพิ่มเติม (เช่น อาคาร / ชั้น / ห้อง ทั้งเส้นทาง)
        $out = ['success' => true, 'title' => $E['title'], 'sheet' => $E['sheet'], 'file' => $E['file'], 'keyHint' => $E['keyHint'] ?? '', 'fields' => $fields, 'lookups' => $lists];
        if ($action === 'export') $out['rows'] = call_user_func($E['export'], $ctx);
        sxi_out($out);
    }

    $in = json_decode(file_get_contents('php://input'), true);
    $rows = is_array($in['rows'] ?? null) ? array_values($in['rows']) : [];
    $mode = ($in['mode'] ?? 'update') === 'skip' ? 'skip' : 'update';
    if (count($rows) > 5000) sxi_out(['success' => false, 'error' => 'ไฟล์มีมากกว่า 5,000 แถว — แบ่งไฟล์แล้วนำเข้าทีละส่วน']);

    if ($action === 'preview') {
        list($res) = sxi_check_rows($E, $ctx, $rows);
        sxi_out(['success' => true, 'rows' => array_map(function ($r) { return ['status' => $r['status'], 'errors' => $r['errors'], 'bad' => $r['bad']]; }, $res)]);
    }

    if ($action === 'commit') {
        if (!$rows) sxi_out(['success' => false, 'error' => 'ไม่มีข้อมูลให้นำเข้า']);
        list($res, $lk) = sxi_check_rows($E, $ctx, $rows);
        $bad = array_filter($res, function ($r) { return $r['status'] === 'error'; });
        if ($bad) sxi_out(['success' => false, 'error' => 'มี ' . count($bad) . ' แถวผิดพลาด — ตรวจสอบในหน้าต่างนำเข้าแล้วลองใหม่']);
        $ins = 0; $upd = 0; $skip = 0;
        mysqli_begin_transaction($connect);
        try {
            foreach ($res as $r) {
                if ($r['id']) {
                    if ($mode === 'skip') { $skip++; continue; }
                    call_user_func($E['update'], $ctx, $r['id'], $r['v'], $lk);
                    $upd++;
                } else {
                    call_user_func($E['insert'], $ctx, $r['v'], $lk);
                    $ins++;
                }
            }
            mysqli_commit($connect);
        } catch (Exception $e) {
            mysqli_rollback($connect);
            throw $e;
        }
        if (isset($E['after'])) call_user_func($E['after'], $ctx);   // งานหลังบันทึก (นอกทรานแซกชัน)
        sxi_out(['success' => true, 'inserted' => $ins, 'updated' => $upd, 'skipped' => $skip]);
    }
    sxi_out(['success' => false, 'error' => 'ไม่รู้จักคำสั่ง: ' . $action]);
} catch (Exception $e) {
    sxi_out(['success' => false, 'error' => $e->getMessage()]);
}
