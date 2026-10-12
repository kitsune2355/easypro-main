<?php
//handle_pm_postpone.php — เลื่อนแผน PM: รายการงานที่ยังไม่ทำ (แบ่งหน้า) / ตัวเลือกตัวกรอง / ประวัติการเลื่อน / บันทึกการเลื่อน (ใช้กับ pm_postpone.php)
@session_start();
include 'config_ctrl/connect.php';

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Bangkok');

function jsonOut($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (mysqli_connect_errno()) {
    http_response_code(500);
    jsonOut(['success' => false, 'error' => 'Database connection failed']);
}

// ใช้หน่วยงาน/ผู้ใช้จาก session เท่านั้น (ไม่เชื่อค่าจาก URL)
$user_id = $_SESSION['sess_user_id_es'] ?? '';
$ag_id   = (string)($_SESSION['sess_user_agency'] ?? '');
$contract_start = $_SESSION['sess_ag_start_date'] ?? '';
$contract_end   = $_SESSION['sess_ag_end_date'] ?? '';

if (!$user_id) {
    http_response_code(401);
    jsonOut(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบใหม่']);
}
if ($ag_id === '') {
    jsonOut(['success' => false, 'error' => 'ไม่พบหน่วยงาน (AG_ID) กรุณาเลือกหน่วยงานก่อน']);
}

// รัน prepared statement แล้วคืนแถวทั้งหมด
function ppQuery($connect, $sql, $types = '', $params = []) {
    $stmt = $connect->prepare($sql);
    if (!$stmt) throw new Exception('Query prepare failed: ' . $connect->error);
    if ($types !== '') $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) throw new Exception('Query failed: ' . $stmt->error);
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

// ความถี่ => จำนวนวันโดยประมาณ (ใช้กับกฎ "งานเลยกำหนดที่ถี่กว่า 30 วัน ห้ามเลื่อน")
function ppFreqDays($freq) {
    $f = (int)$freq;
    if ($f > 0) return $f;
    $map = [-9 => 1, -1 => 3, -3 => 7, -2 => 30, -4 => 60, -5 => 90, -6 => 120, -7 => 150, -8 => 180,
            -10 => 365, -11 => 730, -12 => 1095, -13 => 1460, -14 => 1825];
    return $map[$f] ?? 0;
}

// วันหยุดที่ตรงกับวันที่ (คืนชื่อวันหยุด หรือ '') — อาทิตย์ห้ามเลื่อนเสมอ (เหมือนปฏิทินในตาราง)
function ppHolidayName($date, $holidays) {
    $d = new DateTime($date);
    if ((int)$d->format('N') === 7) return 'วันอาทิตย์';
    $thaiDayMap = ['จ.' => 1, 'อ.' => 2, 'พ.' => 3, 'พฤ.' => 4, 'ศ.' => 5, 'ส.' => 6, 'อา.' => 7];
    foreach ($holidays as $h) {
        $hd = trim($h['date']);
        if ($h['type'] === 'once' && $hd === $d->format('Y-m-d')) return $h['name'];
        if ($h['type'] === 'yearly' && strlen($hd) >= 10 && substr($hd, 5, 5) === $d->format('m-d')) return $h['name'];
        if ($h['type'] === 'daily') {
            $n = $thaiDayMap[$hd] ?? (is_numeric($hd) ? (int)$hd : 0);
            if ($n === (int)$d->format('N')) return $h['name'];
        }
    }
    return '';
}

function ppHolidays($connect, $ag_id) {
    return ppQuery($connect, "SELECT type, date, name FROM pm_holidays WHERE ag_id = ? AND status = 1", 's', [$ag_id]);
}

$isDate = function ($s) { return is_string($s) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) && strtotime($s) !== false; };

// ==================== บันทึกการเลื่อน (POST JSON { updates: [{plan_id, new_date, reason}] }) ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $updates = $input['updates'] ?? null;
    if (!is_array($updates) || !$updates) jsonOut(['success' => false, 'error' => 'รูปแบบข้อมูลไม่ถูกต้อง (Missing updates array)']);
    if (count($updates) > 500) jsonOut(['success' => false, 'error' => 'บันทึกได้ครั้งละไม่เกิน 500 รายการ']);

    $ids = array_values(array_unique(array_map(function ($u) { return (int)($u['plan_id'] ?? 0); }, $updates)));
    $ids = array_filter($ids);
    if (!$ids) jsonOut(['success' => false, 'error' => 'ไม่พบรายการที่จะเลื่อน']);

    try {
        // วันที่เดิม/ความถี่อ่านจากฐานข้อมูล (เฉพาะงานของหน่วยงานนี้ที่ยังไม่ทำ)
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $rows = ppQuery($connect,
            "SELECT p.id, p.event_date, pl.frequency FROM pm_plan_events p LEFT JOIN pm_plans pl ON pl.id = p.plan_id
             WHERE p.ag_id = ? AND p.status = 0 AND p.id IN ($ph)",
            's' . str_repeat('i', count($ids)), array_merge([$ag_id], $ids));
        $events = [];
        foreach ($rows as $r) $events[(int)$r['id']] = $r;
        $holidays = ppHolidays($connect, $ag_id);
    } catch (Exception $e) {
        jsonOut(['success' => false, 'error' => $e->getMessage()]);
    }

    $today = date('Y-m-d');
    $errors = [];
    $valid = [];
    foreach ($updates as $u) {
        $id = (int)($u['plan_id'] ?? 0);
        $newDate = (string)($u['new_date'] ?? '');
        $reason = trim((string)($u['reason'] ?? ''));
        if (!isset($events[$id])) { $errors[] = ['plan_id' => $id, 'error' => 'ไม่พบงาน หรือทำเสร็จ/ถูกลบไปแล้ว']; continue; }
        $ev = $events[$id];
        $err = '';
        if (!$isDate($newDate)) $err = 'วันที่ใหม่ไม่ถูกต้อง';
        elseif ($reason === '') $err = 'ต้องระบุเหตุผล';
        elseif ($newDate < $today) $err = 'ห้ามเลื่อนเป็นวันในอดีต';
        elseif ($newDate === $ev['event_date']) $err = 'วันที่ใหม่ตรงกับวันที่เดิม';
        elseif ($contract_end && $newDate > $contract_end) $err = 'เกินวันสิ้นสุดสัญญา (' . $contract_end . ')';
        elseif ($contract_start && $newDate < $contract_start) $err = 'ก่อนวันเริ่มสัญญา (' . $contract_start . ')';
        elseif (($hol = ppHolidayName($newDate, $holidays)) !== '') $err = 'ตรงกับวันหยุด: ' . $hol;
        elseif ($ev['event_date'] < $today && ppFreqDays($ev['frequency']) < 30) $err = 'งานเลยกำหนดที่มีความถี่น้อยกว่า 30 วัน ไม่อนุญาตให้เลื่อน';
        if ($err) { $errors[] = ['plan_id' => $id, 'error' => $err]; continue; }
        $valid[] = [$id, $ev['event_date'], $newDate, mb_substr($reason, 0, 1000)];
    }
    // มีรายการผิด => ไม่บันทึกเลยสักรายการ (ให้แก้ให้ครบก่อน)
    if ($errors) jsonOut(['success' => false, 'error' => 'มีรายการที่ไม่ผ่านการตรวจสอบ ' . count($errors) . ' รายการ', 'errors' => $errors]);

    $connect->begin_transaction();
    try {
        $up = $connect->prepare("UPDATE pm_plan_events SET event_date = ? WHERE id = ? AND ag_id = ? AND status = 0");
        $ins = $connect->prepare("INSERT INTO pm_plan_postpone_history (plan_id, old_date, new_date, reason, created_by) VALUES (?, ?, ?, ?, ?)");
        if (!$up || !$ins) throw new Exception($connect->error);
        foreach ($valid as [$id, $oldDate, $newDate, $reason]) {
            $up->bind_param('sis', $newDate, $id, $ag_id);
            if (!$up->execute()) throw new Exception('Error updating plan: ' . $up->error);
            $ins->bind_param('issss', $id, $oldDate, $newDate, $reason, $user_id);
            if (!$ins->execute()) throw new Exception('Error inserting history: ' . $ins->error);
        }
        $connect->commit();
        jsonOut(['success' => true, 'saved' => count($valid), 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว']);
    } catch (Exception $e) {
        $connect->rollback();
        jsonOut(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

$action = $_GET['action'] ?? null;

// ==================== ประวัติการเลื่อนของงาน 1 รายการ ====================
if ($action === 'get_history') {
    $plan_id = (int)($_GET['plan_id'] ?? 0);
    if ($plan_id <= 0) jsonOut(['success' => false, 'data' => [], 'error' => 'Invalid plan ID']);
    try {
        // plan_id ในตารางประวัติ = id ของ pm_plan_events — ตรวจว่าเป็นงานของหน่วยงานนี้
        $data = ppQuery($connect,
            "SELECT h.old_date, h.new_date, h.reason, h.created_by, h.created_at
             FROM pm_plan_postpone_history h JOIN pm_plan_events p ON p.id = h.plan_id AND p.ag_id = ?
             WHERE h.plan_id = ? ORDER BY h.id ASC",
            'si', [$ag_id, $plan_id]);
        jsonOut(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        jsonOut(['success' => false, 'data' => [], 'error' => $e->getMessage()]);
    }
}

// ==================== ตัวเลือกตัวกรอง (เฉพาะที่มีในแผนของหน่วยงาน) ====================
if ($action === 'options') {
    try {
        $base = "FROM pm_plans pl JOIN tb_ass_list m ON m.ass_id = pl.machine_id";
        $pick = function ($sql) use ($connect, $ag_id) {
            return array_values(array_filter(array_column(ppQuery($connect, $sql, 's', [$ag_id]), 'v'), 'strlen'));
        };
        jsonOut(['success' => true,
            'checksheets' => $pick("SELECT DISTINCT c.name AS v FROM pm_plans pl JOIN pm_checksheets c ON c.id = pl.checksheet_id WHERE pl.ag_id = ? ORDER BY v LIMIT 500"),
            'types'       => $pick("SELECT DISTINCT g.TGroupName AS v $base JOIN tb_asset_group g ON g.GroupId = m.asset_type WHERE pl.ag_id = ? ORDER BY v LIMIT 500"),
            'locations'   => $pick("SELECT DISTINCT a.area_name AS v $base JOIN tb_area a ON a.area_id = m.asset_rp_area_id WHERE pl.ag_id = ? ORDER BY v LIMIT 500"),
        ]);
    } catch (Exception $e) {
        jsonOut(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ==================== รายการงานที่ยังไม่ทำ (แบ่งหน้าฝั่ง server) ====================
// list = รูปแบบใหม่ (แถวแบบแบน) / get_all = รูปแบบเดิม (extendedProps) เผื่อมีที่อื่นเรียก
if ($action === 'list' || $action === 'get_all') {
    $month = (int)($_GET['month'] ?? 0);
    $year  = (int)($_GET['year'] ?? 0);
    if ($action === 'get_all') {
        $start = max(0, (int)($_GET['startRow'] ?? 0));
        $size  = (int)($_GET['endRow'] ?? 50) - $start;
    } else {
        $size  = (int)($_GET['size'] ?? 50);
        $start = max(0, ((int)($_GET['page'] ?? 1) - 1)) * max(1, $size);
    }
    $size = min(100, max(1, $size));

    $where = ["p.ag_id = ?", "p.status = 0"];
    $types = 's';
    $params = [$ag_id];
    $needJoin = false;

    // เดือน/ปี => ช่วงวันที่ (ใช้ index event_date ได้ ต่างจาก MONTH()/YEAR())
    if ($year >= 2000 && $year <= 2200) {
        $from = $month >= 1 && $month <= 12 ? sprintf('%04d-%02d-01', $year, $month) : sprintf('%04d-01-01', $year);
        $to = date('Y-m-d', strtotime($from . ($month >= 1 && $month <= 12 ? ' +1 month' : ' +1 year')));
        $where[] = "p.event_date >= ? AND p.event_date < ?";
        $types .= 'ss';
        array_push($params, $from, $to);
    } elseif ($month >= 1 && $month <= 12) {
        $where[] = "MONTH(p.event_date) = ?";
        $types .= 'i';
        $params[] = $month;
    }
    if (($_GET['overdue'] ?? '') === '1') {
        $where[] = "p.event_date < ?";
        $types .= 's';
        $params[] = date('Y-m-d');
    }
    // ค้นหา: ชื่อ/รหัสเครื่อง หรือ ชื่อเช็คชีต  + ตัวกรองแต่ละช่อง (machine เดิมยังรองรับ)
    $q = trim((string)($_GET['q'] ?? ''));
    if ($q !== '') {
        $where[] = "(m.asset_name LIKE ? OR m.ass_code LIKE ? OR c.name LIKE ?)";
        $types .= 'sss';
        array_push($params, "%$q%", "%$q%", "%$q%");
        $needJoin = true;
    }
    foreach (['machine' => 'm.asset_name', 'checksheet' => 'c.name', 'type' => 'g.TGroupName', 'location' => 'a.area_name'] as $key => $col) {
        $v = trim((string)($_GET[$key] ?? ''));
        if ($v === '') continue;
        $where[] = "$col LIKE ?";
        $types .= 's';
        $params[] = "%$v%";
        $needJoin = true;
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);
    $joins = "LEFT JOIN tb_ass_list m ON p.machine_id = m.ass_id
              LEFT JOIN tb_asset_group g ON m.asset_type = g.GroupId
              LEFT JOIN tb_area a ON m.asset_rp_area_id = a.area_id
              LEFT JOIN pm_checksheets c ON p.checksheet_id = c.id";

    try {
        // นับจำนวน: ไม่มีตัวกรองข้อความ => นับจาก pm_plan_events อย่างเดียว ไม่ต้อง join
        $total = (int)ppQuery($connect, "SELECT COUNT(*) AS n FROM pm_plan_events p " . ($needJoin ? $joins : '') . " $whereSql", $types, $params)[0]['n'];

        // แบ่งหน้าก่อนใน subquery แล้วค่อย join รายละเอียด + นับประวัติเลื่อน เฉพาะแถวในหน้านั้น
        $rows = $total === 0 ? [] : ppQuery($connect,
            "SELECT p.id, p.event_date, m.ass_code, m.asset_name, g.TGroupName AS type_name, a.area_name,
                    c.name AS cs_name, pl.frequency,
                    (SELECT COUNT(*) FROM pm_plan_postpone_history h WHERE h.plan_id = p.id) AS pc
             FROM (SELECT p.id FROM pm_plan_events p " . ($needJoin ? $joins : '') . " $whereSql
                   ORDER BY p.event_date, p.id LIMIT ?, ?) pg
             JOIN pm_plan_events p ON p.id = pg.id
             $joins
             LEFT JOIN pm_plans pl ON pl.id = p.plan_id
             ORDER BY p.event_date, p.id",
            $types . 'ii', array_merge($params, [$start, $size]));
    } catch (Exception $e) {
        jsonOut(['success' => false, 'data' => [], 'total' => 0, 'error' => $e->getMessage()]);
    }

    if ($action === 'get_all') {
        $events = array_map(function ($r) {
            return ['id' => $r['id'], 'start' => $r['event_date'], 'postpone_count' => (int)$r['pc'],
                'extendedProps' => ['qr_code' => $r['ass_code'], 'machine' => $r['asset_name'], 'type' => $r['type_name'],
                    'location' => $r['area_name'], 'checksheet' => $r['cs_name'], 'frequency' => (int)$r['frequency']]];
        }, $rows);
        jsonOut(['success' => true, 'data' => $events, 'total' => $total, 'error' => null]);
    }

    // คำอธิบายความถี่ (ตารางเล็ก ~60 แถว)
    $freqDesc = [];
    if ($rows) {
        $fr = $connect->query("SELECT freq_value, description FROM pm_freq_options");
        while ($fr && ($f = $fr->fetch_assoc())) $freqDesc[(string)$f['freq_value']] = trim(preg_replace('/\s+/', ' ', $f['description']));
    }
    $out = array_map(function ($r) use ($freqDesc) {
        $f = (string)$r['frequency'];
        return [(int)$r['id'], $r['event_date'], $r['ass_code'], $r['asset_name'], $r['type_name'], $r['area_name'],
            $r['cs_name'], (int)$f, $freqDesc[$f] ?? ($f !== '' ? "ทุก $f วัน" : ''), (int)$r['pc'], ppFreqDays($f)];
    }, $rows);

    $resp = ['success' => true, 'total' => $total, 'page' => intdiv($start, $size) + 1, 'size' => $size,
        // คอลัมน์ของแต่ละแถว (ส่งเป็น array เพื่อลดขนาด)
        'cols' => ['id', 'd', 'code', 'machine', 'type', 'loc', 'cs', 'freq', 'freq_label', 'pc', 'freq_days'],
        'rows' => $out];
    // เปิดแท็บครั้งแรก: แนบวันหยุด + วันสัญญา สำหรับตรวจวันที่ฝั่งหน้าจอ (ไม่ต้องยิงแยก)
    if (($_GET['meta'] ?? '') === '1') {
        try { $resp['holidays'] = ppHolidays($connect, $ag_id); } catch (Exception $e) { $resp['holidays'] = []; }
        $resp['contract'] = ['start' => $contract_start, 'end' => $contract_end];
        $resp['today'] = date('Y-m-d');
    }
    jsonOut($resp);
}

http_response_code(400);
jsonOut(['success' => false, 'error' => 'ไม่ระบุการดำเนินการ (action)']);
