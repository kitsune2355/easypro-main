<?php
//handle_pm_import.php — นำเข้า Yearly PM Schedule (Excel): สร้างเช็คชีต แล้วให้แอดมินกรอกแผน PM ในตาราง
@session_start();
include 'config_ctrl/connect.php';
require_once 'pm_plan_functions.php';

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Bangkok');
ini_set('memory_limit', '512M');
set_time_limit(600);

function jsonOut($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (mysqli_connect_errno()) {
    http_response_code(500);
    jsonOut(['success' => false, 'error' => 'Database connection failed']);
}

// --- ตรวจสิทธิ์: เฉพาะผู้ดูแลระบบ ---
$user_id    = $_SESSION['sess_user_id_es'] ?? '';
$user_level = $_SESSION['sess_user_level_es'] ?? '';
$ag_id      = $_SESSION['sess_user_agency'] ?? '';
$contract_start = $_SESSION['sess_ag_start_date'] ?? '';
$contract_end   = $_SESSION['sess_ag_end_date'] ?? '';

if (!$user_id) {
    http_response_code(401);
    jsonOut(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบใหม่']);
}
if (!in_array($user_level, ['admin', 'super_admin'], true)) {
    http_response_code(403);
    jsonOut(['success' => false, 'error' => 'เมนูนี้สำหรับผู้ดูแลระบบเท่านั้น']);
}
if (!$ag_id) {
    jsonOut(['success' => false, 'error' => 'ไม่พบหน่วยงาน (AG_ID) กรุณาเลือกหน่วยงานก่อน']);
}

$action = $_GET['action'] ?? null;
$input  = json_decode(file_get_contents('php://input'), true) ?: [];

// ตัวอักษรใน Excel (หมายเหตุท้ายชีต) => คำอธิบาย (แสดงอ้างอิงในตารางกรอกแผน)
$LETTER_LABEL = ['D' => 'Daily', 'W' => 'Weekly', 'M' => 'Monthly', 'Q' => 'Quarterly', 'H' => 'Half Year', 'Y' => 'Yearly', 'R' => 'Reading'];

// จุดตรวจ default เมื่อไฟล์ Excel ไม่มีจุดตรวจ
$DEFAULT_ITEM = [
    'check_point'       => 'ตรวจสอบอุปกรณ์',
    'standard_text'     => 'ตรวจสอบอุปกรณ์',
    'method_text'       => 'ตรวจสอบอุปกรณ์',
    'action_abnormal'   => '',
    'check_type_id'     => 1, // ผ่าน / ไม่ผ่าน
    'photo_required_id' => 2, // ไม่บังคับ
];

$DAY_OPTIONS = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.', 'อา.'];

function fetchAll($connect, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($connect, $sql);
    if (!$stmt) throw new Exception('Query preparation failed: ' . mysqli_error($connect));
    if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($res)) $rows[] = $row;
    mysqli_stmt_close($stmt);
    return $rows;
}

// freq_value => ['desc', 'multi' (dropdown_multi_select), 'max_alert' (alert_before_for_repeat_config)]
function getFreqOptions($connect) {
    $opts = [];
    $sql = "SELECT freq_value, description, dropdown_multi_select, alert_before_for_repeat_config FROM pm_freq_options ORDER BY id ASC";
    foreach (fetchAll($connect, $sql) as $r) {
        $opts[(string)$r['freq_value']] = [
            'desc' => $r['description'],
            'multi' => (string)$r['dropdown_multi_select'],
            'max_alert' => intval($r['alert_before_for_repeat_config'])
        ];
    }
    return $opts;
}

// ข้อจำกัดไฟล์แนบ (เหมือนหน้าสร้างเช็คชีต)
const MAX_IMAGES = 5;
const MAX_DOCS = 5;
const MAX_DOC_BYTES = 5 * 1024 * 1024;

// อ่านไฟล์จาก $_FILES[$field][] => [{tmp_name, name, size}]
function collectFiles($field) {
    if (empty($_FILES[$field]['name'])) return [];
    $f = $_FILES[$field];
    $out = [];
    foreach ((array)$f['name'] as $i => $n) {
        $err = ((array)$f['error'])[$i];
        if ($err === UPLOAD_ERR_NO_FILE) continue;
        if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) throw new Exception("ไฟล์ \"$n\" มีขนาดใหญ่เกินที่เซิร์ฟเวอร์รองรับ");
        if ($err !== UPLOAD_ERR_OK) throw new Exception("อัปโหลดไฟล์ \"$n\" ไม่สำเร็จ (error $err)");
        $out[] = ['tmp_name' => ((array)$f['tmp_name'])[$i], 'name' => $n, 'size' => ((array)$f['size'])[$i]];
    }
    return $out;
}

// เก็บไฟล์แบบเดียวกับ uploadFile() ใน handle_pm_checksheet.php: <folder>/Y/m/d/<uniqid>_<time>.<ext>
function storeUpload($tmp, $originalName, $baseFolder) {
    $dir = $baseFolder . '/' . date('Y/m/d') . '/';
    if (!is_dir($dir) && !mkdir($dir, 0777, true)) return false;
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $target = $dir . uniqid() . '_' . time() . ($ext !== '' ? '.' . $ext : '');
    return move_uploaded_file($tmp, $target) ? $target : false;
}

// ตัวอักษร => จำนวนวัน (ใช้เมื่อในไฟล์มีกำหนดเพียงครั้งเดียว คำนวณระยะห่างไม่ได้)
$LETTER_DAYS = ['D' => 1, 'W' => 7, 'M' => 30, 'Q' => 90, 'H' => 180, 'Y' => 365];
// จำนวนเดือน => freq_value แบบรายเดือนใน calculateNextPMDate (ใช้เมื่อไม่มีตัวเลือก "ทุก N วัน")
$MONTH_FREQ = [1 => '-2', 2 => '-4', 3 => '-5', 4 => '-6', 5 => '-7', 6 => '-8', 12 => '-10', 24 => '-11', 36 => '-12', 48 => '-13', 60 => '-14'];

// สัปดาห์ที่ k (1-4) ของเดือน => วันจันทร์ที่ k ของเดือนนั้น
function weekMonday($year, $month, $k) {
    $first = new DateTime(sprintf('%04d-%02d-01', $year, $month));
    $offset = (8 - (int)$first->format('N')) % 7;   // จำนวนวันถึงวันจันทร์แรก
    $first->modify('+' . ($offset + ($k - 1) * 7) . ' days');
    return $first->format('Y-m-d');
}

/**
 * เสนอความถี่และวันที่เริ่มจากตาราง Excel (ช่องสัปดาห์ 0-47, เดือนละ 4 ช่อง)
 * - ระยะห่าง 4 ช่อง = 1 เดือน = 30 วัน, ต่ำกว่า 4 ช่อง = สัปดาห์ละ 7 วัน
 * - วันที่เริ่ม = วันจันทร์ของสัปดาห์แรกที่มีกำหนด (ภายในช่วงสัญญา)
 */
function suggestPlan($cells, $year, $freqOptions, $contractStart, $contractEnd) {
    global $LETTER_DAYS, $MONTH_FREQ;
    $idxs = array_keys($cells);
    sort($idxs);
    $out = ['freq' => '', 'start_date' => '', 'note' => []];
    if (!$idxs || !$year) return $out;

    // ระยะห่างที่พบบ่อยที่สุด (ถ้าเท่ากัน ใช้ค่าน้อยกว่า)
    $gaps = [];
    for ($i = 1; $i < count($idxs); $i++) $gaps[] = $idxs[$i] - $idxs[$i - 1];
    $days = 0;
    $months = 0;
    if ($gaps) {
        $cnt = array_count_values($gaps);
        ksort($cnt);
        arsort($cnt);
        reset($cnt);
        $gap = key($cnt);
        if (count($cnt) > 1) $out['note'][] = 'ระยะห่างในไฟล์ไม่สม่ำเสมอ (' . implode('/', array_unique($gaps)) . ' สัปดาห์)';
        if ($gap % 4 === 0) { $months = $gap / 4; $days = $months * 30; }
        else $days = $gap * 7;
    } else {
        // มีครั้งเดียว: ใช้ตัวอักษร
        $letter = $cells[$idxs[0]];
        if (isset($LETTER_DAYS[$letter])) {
            $days = $LETTER_DAYS[$letter];
            $months = ['M' => 1, 'Q' => 3, 'H' => 6, 'Y' => 12][$letter] ?? 0;
        }
        $out['note'][] = 'มีกำหนดครั้งเดียวในไฟล์ ใช้ความถี่ตามตัวอักษร ' . $letter;
    }

    if ($days > 0) {
        if (isset($freqOptions[(string)$days])) $out['freq'] = $freqOptions[(string)$days]['desc'];
        elseif ($months && isset($MONTH_FREQ[$months]) && isset($freqOptions[$MONTH_FREQ[$months]])) $out['freq'] = $freqOptions[$MONTH_FREQ[$months]]['desc'];
        else $out['note'][] = "ไม่มีความถี่ \"ทุก $days วัน\" ในระบบ";
    }

    // วันที่เริ่ม: วันจันทร์ของสัปดาห์แรกที่อยู่ในช่วงสัญญา
    foreach ($idxs as $idx) {
        $d = weekMonday($year, intdiv($idx, 4) + 1, $idx % 4 + 1);
        if ($contractStart && $d < $contractStart) continue;
        if ($contractEnd && $d > $contractEnd) break;
        $out['start_date'] = $d;
        break;
    }
    if (!$out['start_date']) $out['note'][] = 'กำหนดในไฟล์อยู่นอกช่วงสัญญา';

    $out['note'] = implode(' · ', $out['note']);
    return $out;
}

function normCode($s) {
    return strtoupper(preg_replace('/\s+/u', ' ', trim((string)$s)));
}

/**
 * จับคู่แถวใน Excel กับเครื่องจักร และหาเช็คชีตที่ต้องใช้ (ใช้ทั้ง preview และ create_sheets)
 * rows: [{row, name, code, location, letters: ['M','Q',...]}]
 * เช็คชีต 1 ชุดต่อ (ประเภทเครื่อง + Name)
 */
function analyzeRows($connect, $ag_id, $rows) {
    global $LETTER_LABEL;
    if (empty($rows)) throw new Exception('ไม่พบข้อมูลเครื่องจักรในไฟล์');

    $machines = [];
    $dupCodes = [];
    $sql = "SELECT a.ass_id, a.ass_code, a.asset_name, a.asset_type, g.TGroupName
            FROM tb_ass_list a
            LEFT JOIN tb_asset_group g ON a.asset_type = g.GroupId
            WHERE a.ass_ag_id = ?";
    foreach (fetchAll($connect, $sql, "s", [$ag_id]) as $m) {
        $k = normCode($m['ass_code']);
        if ($k === '') continue;
        if (isset($machines[$k])) { $dupCodes[$k] = true; continue; }
        $machines[$k] = $m;
    }

    $existingSheets = [];
    foreach (fetchAll($connect, "SELECT id, name, ass_type_id FROM pm_checksheets WHERE ag_id = ?", "s", [$ag_id]) as $c) {
        $existingSheets[$c['ass_type_id'] . '|' . $c['name']] = (int)$c['id'];
    }

    $sheets = [];
    $pairs = [];   // เครื่อง × เช็คชีต
    $result = [];
    $seenCodes = [];
    $stats = ['rows' => 0, 'matched' => 0, 'not_found' => 0, 'no_schedule' => 0, 'duplicate' => 0];

    foreach ($rows as $r) {
        $stats['rows']++;
        $code = normCode($r['code'] ?? '');
        $name = trim((string)($r['name'] ?? ''));
        $out = ['row' => $r['row'] ?? null, 'name' => $name, 'code' => $r['code'] ?? '', 'location' => $r['location'] ?? '',
                'machine' => '', 'group' => '', 'status' => 'ok', 'message' => '', 'sheets' => []];

        if (isset($seenCodes[$code])) {
            $out['status'] = 'duplicate';
            $out['message'] = 'เลขครุภัณฑ์ซ้ำกับแถว ' . $seenCodes[$code];
            $stats['duplicate']++;
            $result[] = $out;
            continue;
        }
        $seenCodes[$code] = $r['row'] ?? '?';

        if (!isset($machines[$code])) {
            $out['status'] = 'not_found';
            $out['message'] = 'ไม่พบเลขครุภัณฑ์นี้ในระบบ';
            $stats['not_found']++;
            $result[] = $out;
            continue;
        }
        $m = $machines[$code];
        $out['machine'] = $m['asset_name'];
        $out['group'] = $m['TGroupName'] ?? '';
        if (isset($dupCodes[$code])) $out['message'] = 'เลขครุภัณฑ์นี้มีมากกว่า 1 เครื่องในระบบ (ใช้เครื่องแรก)';

        // cells: {ช่องสัปดาห์ 0-47: ตัวอักษร}
        $cells = [];
        $counts = [];
        foreach (($r['cells'] ?? []) as $idx => $l) {
            $idx = intval($idx);
            $l = strtoupper(trim((string)$l));
            if ($idx < 0 || $idx > 47 || $l === '') continue;
            $cells[$idx] = $l;
            if ($l !== '*') $counts[$l] = ($counts[$l] ?? 0) + 1;
        }
        if (!$counts) {
            $out['status'] = 'no_schedule';
            $out['message'] = trim($out['message'] . ' ไม่มีกำหนดการในแถวนี้');
            $stats['no_schedule']++;
            $result[] = $out;
            continue;
        }
        $stats['matched']++;

        // เช็คชีตเดียวต่อ Name (M/Q/Y ของเครื่องเดียวกันใช้เช็คชีตเดียวกัน ต่างกันแค่ความถี่ในแผน)
        $key = $m['asset_type'] . '|' . $name;
        if (!isset($sheets[$key])) {
            $sheets[$key] = ['key' => $key, 'name' => $name, 'ass_type_id' => $m['asset_type'],
                             'group_name' => $m['TGroupName'] ?? '', 'letters' => [],
                             'existing_id' => $existingSheets[$key] ?? null, 'machines' => 0];
        }
        $sheets[$key]['machines']++;
        $sheets[$key]['letters'] = array_values(array_unique(array_merge($sheets[$key]['letters'], array_keys($counts))));
        $out['sheets'][] = $name;
        $pairs[] = ['machine' => $m, 'location' => $r['location'] ?? '', 'sheet_key' => $key, 'cells' => $cells,
                    'letters' => implode(', ', array_map(function ($l) use ($LETTER_LABEL) { return $l . (isset($LETTER_LABEL[$l]) ? ' (' . $LETTER_LABEL[$l] . ')' : ''); }, array_keys($counts)))];
        $result[] = $out;
    }

    $stats['sheets_new'] = count(array_filter($sheets, function ($s) { return !$s['existing_id']; }));
    $stats['sheets_reused'] = count($sheets) - $stats['sheets_new'];
    $stats['pairs'] = count($pairs);

    return ['rows' => $result, 'sheets' => $sheets, 'pairs' => $pairs, 'stats' => $stats];
}

try {
    if ($action === 'get_options') {
        $freqs = [];
        foreach (getFreqOptions($connect) as $v => $o) {
            $freqs[] = ['value' => (string)$v, 'desc' => $o['desc'], 'dropdownmultiselect' => $o['multi'], 'alertbeforeforrepeatconfig' => $o['max_alert']];
        }
        $alerts = fetchAll($connect, "SELECT alert_value AS value, description AS `desc` FROM pm_alert_options ORDER BY id ASC");

        // ค่าเริ่มต้น: แจ้งเตือน 8 โมงเช้าของวันนั้น (notification_api.php แจ้งทุกงานที่ event_date = วันนี้)
        $defaultAlert = '';
        foreach ($alerts as $al) {
            if (preg_match('/(^|\D)0?8[:.]00|8\s*โมง|วันนั้น|วันเดียวกัน|วันที่ทำ/u', $al['desc'])) { $defaultAlert = $al['value']; break; }
        }
        if ($defaultAlert === '') {
            foreach ($alerts as $al) if ((string)$al['value'] === '0') { $defaultAlert = '0'; break; }
        }

        jsonOut(['success' => true, 'data' => ['freqs' => $freqs, 'alerts' => $alerts, 'default_alert' => (string)$defaultAlert,
                 'contract' => ['start' => $contract_start, 'end' => $contract_end],
                 'agency' => (fetchAll($connect, "SELECT ag_contract FROM tb_agency WHERE ag_id = ?", "s", [$ag_id])[0]['ag_contract'] ?? '')]]);
    }

    // รายการเครื่องจักรของหน่วยงาน (ใส่ใน Template ให้กรอกตารางปีต่อได้เลย)
    if ($action === 'machines') {
        $sql = "SELECT a.ass_code, a.asset_name, g.TGroupName,
                       CONCAT_WS(' ', ar.area_name, ac.ac_name, rm.ar_name) AS location
                FROM tb_ass_list a
                LEFT JOIN tb_asset_group g ON a.asset_type = g.GroupId
                LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
                LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
                LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
                WHERE a.ass_ag_id = ? AND a.ass_code IS NOT NULL AND a.ass_code <> ''
                ORDER BY g.TGroupName, a.ass_code";
        jsonOut(['success' => true, 'data' => fetchAll($connect, $sql, "s", [$ag_id])]);
    }

    // ขั้นที่ 1: ตรวจสอบไฟล์ (ยังไม่บันทึกอะไร)
    if ($action === 'preview') {
        $a = analyzeRows($connect, $ag_id, $input['rows'] ?? []);
        jsonOut(['success' => true, 'data' => ['rows' => $a['rows'], 'sheets' => array_values($a['sheets']), 'stats' => $a['stats']]]);
    }

    // ขั้นที่ 2: สร้างเช็คชีตที่ยังไม่มี แล้วส่งรายการ เครื่อง × เช็คชีต กลับไปให้กรอกแผน
    if ($action === 'create_sheets') {
        $a = analyzeRows($connect, $ag_id, $input['rows'] ?? []);
        $sheetIds = [];
        $createdIds = [];   // key => id ของเช็คชีตที่สร้างใหม่ (ใช้อัปโหลดไฟล์แนบต่อ)

        mysqli_begin_transaction($connect);
        try {
            $stmtSheet = mysqli_prepare($connect, "INSERT INTO pm_checksheets (name, ass_type_id, effective_date, doc_no, rev_no, estimated_time, ag_id, created_by, updated_by) VALUES (?, ?, NULL, '', '', 0, ?, ?, ?)");
            $stmtItem = mysqli_prepare($connect, "INSERT INTO pm_checksheet_items (checksheet_id, check_point, standard_text, method_text, action_abnormal, check_type_id, illustration_path, photo_required_id, value_name, unit, expected_value, sort_order) VALUES (?, ?, ?, ?, ?, ?, '', ?, '', '', '', 1)");
            foreach ($a['sheets'] as $key => $s) {
                if ($s['existing_id']) { $sheetIds[$key] = $s['existing_id']; continue; }
                mysqli_stmt_bind_param($stmtSheet, "sisss", $s['name'], $s['ass_type_id'], $ag_id, $user_id, $user_id);
                if (!mysqli_stmt_execute($stmtSheet)) throw new Exception('สร้างเช็คชีตไม่สำเร็จ: ' . mysqli_error($connect));
                $sid = mysqli_insert_id($connect);
                $sheetIds[$key] = $sid;
                $createdIds[$key] = $sid;

                mysqli_stmt_bind_param($stmtItem, "issssii", $sid, $DEFAULT_ITEM['check_point'], $DEFAULT_ITEM['standard_text'],
                    $DEFAULT_ITEM['method_text'], $DEFAULT_ITEM['action_abnormal'], $DEFAULT_ITEM['check_type_id'], $DEFAULT_ITEM['photo_required_id']);
                if (!mysqli_stmt_execute($stmtItem)) throw new Exception('สร้างจุดตรวจไม่สำเร็จ: ' . mysqli_error($connect));
            }
            mysqli_commit($connect);
        } catch (Exception $e) {
            mysqli_rollback($connect);
            throw $e;
        }

        // แผนที่ใช้งานอยู่แล้ว => ไม่ต้องแสดงในตาราง
        $existingPlans = [];
        foreach (fetchAll($connect, "SELECT machine_id, checksheet_id FROM pm_plans WHERE ag_id = ? AND status = 1", "s", [$ag_id]) as $p) {
            $existingPlans[$p['machine_id'] . '|' . $p['checksheet_id']] = true;
        }

        // ค่าเริ่มต้นจาก Excel: ความถี่ตามระยะห่าง + วันที่เริ่ม (วันจันทร์) — แอดมินตรวจและแก้ไขในตารางได้
        $year = intval($input['year'] ?? 0);
        $freqOptions = getFreqOptions($connect);

        $planRows = [];
        $skipped = 0;
        foreach ($a['pairs'] as $p) {
            $sid = $sheetIds[$p['sheet_key']];
            if (isset($existingPlans[$p['machine']['ass_id'] . '|' . $sid])) { $skipped++; continue; }
            $sg = suggestPlan($p['cells'], $year, $freqOptions, $contract_start, $contract_end);
            $planRows[] = [
                'machine_id' => $p['machine']['ass_id'], 'code' => $p['machine']['ass_code'], 'machine' => $p['machine']['asset_name'],
                'group' => $p['machine']['TGroupName'] ?? '', 'location' => $p['location'],
                'checksheet_id' => $sid, 'checksheet' => $a['sheets'][$p['sheet_key']]['name'], 'excel_freq' => $p['letters'],
                'freq' => $sg['freq'], 'start_date' => $sg['start_date'], 'note' => $sg['note'],
                'cells' => (object)$p['cells']   // ตารางสัปดาห์จาก Excel สำหรับแสดงในหน้าตรวจสอบ
            ];
        }
        jsonOut(['success' => true, 'data' => ['sheets_created' => count($createdIds), 'created_ids' => (object)$createdIds,
                 'sheets_reused' => $a['stats']['sheets_reused'], 'plans_skipped' => $skipped, 'plan_rows' => $planRows]]);
    }

    // แนบไฟล์ให้เช็คชีต (ส่งทีละเช็คชีต): images[] = ภาพจุดตรวจสอบ, docs[] = คำแนะนำในการทำงาน
    if ($action === 'upload_attachments') {
        $sid = intval($_POST['checksheet_id'] ?? 0);
        $own = fetchAll($connect, "SELECT name FROM pm_checksheets WHERE id = ? AND ag_id = ?", "is", [$sid, $ag_id]);
        if (!$own) throw new Exception('ไม่พบเช็คชีต');
        $label = $own[0]['name'];

        $have = ['image' => 0, 'document' => 0];
        foreach (fetchAll($connect, "SELECT file_type, COUNT(*) AS n FROM pm_checksheet_files WHERE checksheet_id = ? GROUP BY file_type", "i", [$sid]) as $r) {
            $have[$r['file_type']] = intval($r['n']);
        }

        // ตรวจทั้งหมดก่อนเก็บไฟล์
        $images = collectFiles('images');
        $docs = collectFiles('docs');
        if (count($images) + $have['image'] > MAX_IMAGES) throw new Exception("$label: ภาพจุดตรวจสอบได้สูงสุด " . MAX_IMAGES . " รูป");
        if (count($docs) + $have['document'] > MAX_DOCS) throw new Exception("$label: คำแนะนำในการทำงานได้สูงสุด " . MAX_DOCS . " ไฟล์");
        foreach ($images as $f) {
            if (@getimagesize($f['tmp_name']) === false) throw new Exception("$label: ไฟล์ \"{$f['name']}\" ไม่ใช่รูปภาพ");
        }
        foreach ($docs as $f) {
            if ($f['size'] > MAX_DOC_BYTES) throw new Exception("$label: ไฟล์ \"{$f['name']}\" มีขนาดใหญ่กว่า 5MB");
            if (preg_match('/\.(php\d?|phtml|phar|exe|bat|cmd|sh|js|html?)$/i', $f['name'])) throw new Exception("$label: ไม่อนุญาตไฟล์ \"{$f['name']}\"");
        }

        // ภาพจุดตรวจสอบ => uploads/images (image), คำแนะนำ => uploads/files (document) เหมือน handle_pm_checksheet.php
        $todo = array_merge(
            array_map(function ($f) { return $f + ['type' => 'image', 'folder' => 'uploads/images']; }, $images),
            array_map(function ($f) { return $f + ['type' => 'document', 'folder' => 'uploads/files']; }, $docs)
        );
        $saved = [];
        mysqli_begin_transaction($connect);
        try {
            $stmtFile = mysqli_prepare($connect, "INSERT INTO pm_checksheet_files (checksheet_id, file_path, file_type, file_name) VALUES (?, ?, ?, ?)");
            foreach ($todo as $f) {
                $path = storeUpload($f['tmp_name'], $f['name'], $f['folder']);
                if (!$path) throw new Exception('อัปโหลดไฟล์ไม่สำเร็จ: ' . $f['name']);
                $saved[] = $path;
                mysqli_stmt_bind_param($stmtFile, "isss", $sid, $path, $f['type'], $f['name']);
                if (!mysqli_stmt_execute($stmtFile)) throw new Exception('บันทึกไฟล์แนบไม่สำเร็จ: ' . mysqli_error($connect));
            }
            mysqli_commit($connect);
        } catch (Exception $e) {
            mysqli_rollback($connect);
            foreach ($saved as $p) @unlink($p);
            throw $e;
        }
        jsonOut(['success' => true, 'data' => ['images' => count($images), 'docs' => count($docs)]]);
    }

    // ขั้นที่ 3: บันทึกแผน PM จากตาราง (คำนวณกำหนดการด้วย calculateNextPMDate เหมือนหน้าสร้างแผน PM)
    if ($action === 'save_plans') {
        $items = $input['plans'] ?? [];
        if (!$items) throw new Exception('ไม่มีแผนที่จะบันทึก');
        if (!$contract_end) throw new Exception('ไม่พบวันสิ้นสุดสัญญาของหน่วยงาน');

        $freqOptions = getFreqOptions($connect);
        $alertValues = array_column(fetchAll($connect, "SELECT alert_value FROM pm_alert_options"), 'alert_value');
        $machineTypes = [];
        foreach (fetchAll($connect, "SELECT ass_id, asset_type FROM tb_ass_list WHERE ass_ag_id = ?", "s", [$ag_id]) as $m) {
            $machineTypes[$m['ass_id']] = $m['asset_type'];
        }
        $sheetIdsOfAg = array_flip(array_column(fetchAll($connect, "SELECT id FROM pm_checksheets WHERE ag_id = ?", "s", [$ag_id]), 'id'));

        // ตรวจสอบทุกแถวก่อน — ถ้ามีแถวผิด จะไม่บันทึกเลย
        $errors = [];
        foreach ($items as $i => $it) {
            $rowNo = $it['row'] ?? ($i + 1);
            $freq = (string)($it['freq'] ?? '');
            $days = array_values(array_unique(array_map('strval', $it['days'] ?? [])));
            $start = (string)($it['start_date'] ?? '');
            $err = '';
            if (!isset($machineTypes[$it['machine_id'] ?? ''])) $err = 'ไม่พบเครื่องจักรในหน่วยงานนี้';
            elseif (!isset($sheetIdsOfAg[$it['checksheet_id'] ?? ''])) $err = 'ไม่พบเช็คชีต';
            elseif (!isset($freqOptions[$freq])) $err = 'กรุณาเลือกความถี่';
            elseif (!in_array((string)($it['alert'] ?? ''), array_map('strval', $alertValues), true)) $err = 'กรุณาเลือกการแจ้งเตือน';
            elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !strtotime($start)) $err = 'วันที่เริ่มไม่ถูกต้อง';
            elseif (($contract_start && $start < $contract_start) || $start > $contract_end) $err = 'วันที่เริ่มอยู่นอกช่วงสัญญา';
            else {
                $multi = $freqOptions[$freq]['multi'];
                $max = $freqOptions[$freq]['max_alert'];
                $av = intval($it['alert']);
                if (!($av === 0 || $av === -1 || $av <= $max)) $err = 'การแจ้งเตือนนี้ใช้กับความถี่ที่เลือกไม่ได้';
                elseif ($multi === '2' && count($days) !== 2) $err = 'ต้องระบุวันให้ครบ 2 วัน';
                elseif (($multi === '6' || $multi === '31') && !$days) $err = 'ต้องระบุวันอย่างน้อย 1 วัน';
                elseif (in_array($multi, ['2', '5', '6'], true) && array_diff($days, $GLOBALS['DAY_OPTIONS'])) $err = 'วันในสัปดาห์ต้องเป็น จ. อ. พ. พฤ. ศ. ส. อา.';
                elseif ($multi === '31' && array_filter($days, function ($d) { return !ctype_digit($d) || $d < 1 || $d > 31; })) $err = 'วันที่ในเดือนต้องเป็น 1-31';
                elseif (!in_array($multi, ['2', '5', '6', '31'], true) && $days) $err = 'ความถี่นี้ไม่ต้องระบุวัน';
            }
            if ($err) $errors[] = ['index' => $i, 'row' => $rowNo, 'error' => $err];
        }
        if ($errors) jsonOut(['success' => false, 'error' => 'ข้อมูลไม่ถูกต้อง ' . count($errors) . ' แถว', 'errors' => $errors]);

        $holidays = getHolidays($connect, $ag_id);
        mysqli_begin_transaction($connect);
        try {
            $stmtPlan = mysqli_prepare($connect, "INSERT INTO pm_plans (ag_id, mac_type_id, machine_id, checksheet_id, frequency, alert_value, days_config, status, start_date, next_date, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, NOW())");
            $stmtEv = mysqli_prepare($connect, "INSERT INTO pm_plan_events (ag_id, plan_id, machine_id, mac_type_id, checksheet_id, event_date, status) VALUES (?, ?, ?, ?, ?, ?, 0)");
            $countPlans = 0;
            $countEvents = 0;

            foreach ($items as $it) {
                $machineId = (string)$it['machine_id'];
                $macType = (string)$machineTypes[$machineId];
                $chkId = (string)$it['checksheet_id'];
                $freq = (string)$it['freq'];
                $alert = (string)$it['alert'];
                $days = array_values(array_unique(array_map('strval', $it['days'] ?? [])));
                if ($freqOptions[$freq]['multi'] === '5') $days = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.'];
                $daysJson = json_encode($days, JSON_UNESCAPED_UNICODE);
                $start = $it['start_date'];

                $next = calculateNextPMDate($start, $freq, $days, $holidays);
                mysqli_stmt_bind_param($stmtPlan, "ssssssssss", $ag_id, $macType, $machineId, $chkId, $freq, $alert, $daysJson, $start, $next, $user_id);
                if (!mysqli_stmt_execute($stmtPlan)) throw new Exception('สร้างแผนไม่สำเร็จ: ' . mysqli_error($connect));
                $planId = mysqli_insert_id($connect);
                $countPlans++;

                // กำหนดการตลอดสัญญา (เหมือน action=save ใน handle_pm_plan.php)
                $cur = $start;
                while ($cur && $cur <= $contract_end) {
                    if (!$contract_start || $cur >= $contract_start) {
                        mysqli_stmt_bind_param($stmtEv, "sissss", $ag_id, $planId, $machineId, $macType, $chkId, $cur);
                        if (!mysqli_stmt_execute($stmtEv)) throw new Exception('สร้างกำหนดการไม่สำเร็จ: ' . mysqli_error($connect));
                        $countEvents++;
                    }
                    $nx = calculateNextPMDate($cur, $freq, $days, $holidays);
                    if (!$nx || $nx <= $cur) break;
                    $cur = $nx;
                }
            }

            mysqli_commit($connect);
            jsonOut(['success' => true, 'data' => ['plans_created' => $countPlans, 'events_created' => $countEvents]]);
        } catch (Exception $e) {
            mysqli_rollback($connect);
            throw $e;
        }
    }

    jsonOut(['success' => false, 'error' => 'ไม่ระบุการดำเนินการ (action)']);
} catch (Exception $e) {
    jsonOut(['success' => false, 'error' => $e->getMessage()]);
}
