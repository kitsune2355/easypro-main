<?php
// Monitor ความเคลื่อนไหวของแต่ละหน่วยงาน — Super Admin เท่านั้น (แท็บ Monitor ใน agency.php)
//  GET ?action=summary&days=7|30|90        สรุปทุกหน่วยงานในช่วงเวลา
//  GET ?action=detail&ag=ID&days=...       รายวัน + ความเคลื่อนไหวล่าสุด + ผู้ใช้ที่เข้าใช้งาน ของหน่วยงานเดียว
// ไม่แก้ไขข้อมูลใด ๆ (อ่านอย่างเดียว)
@session_start();
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');
include 'config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');

// วิธีเข้าสู่ระบบ (tl_type: password | qr) — คอลัมน์เพิ่มโดย config_ctrl/login_log.php ตอนล็อกอินครั้งแรก · หน้านี้อ่านอย่างเดียว ไม่เพิ่มคอลัมน์เอง
$tlq = @mysqli_query($connect, "SHOW COLUMNS FROM tb_time_login LIKE 'tl_type'");
$TLT = ($tlq && mysqli_num_rows($tlq) > 0) ? 'tl_type' : 'NULL';

function am_out($a, $code = 200) { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }
if (empty($_SESSION['sess_user_id_es'])) am_out(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบใหม่'], 401);
if (($_SESSION['sess_user_level_es'] ?? '') !== 'super_admin') am_out(['success' => false, 'error' => 'เฉพาะ Super Admin'], 403);

$action = $_GET['action'] ?? 'summary';
// ช่วงเวลา: days=7|30|90 (ถึงวันนี้) หรือกำหนดเอง from=YYYY-MM-DD&to=YYYY-MM-DD (ดูย้อนหลังได้ ไม่เกิน 1 ปี, to ไม่เกินวันนี้)
$days = (int)($_GET['days'] ?? 30);
if (!in_array($days, [7, 30, 90], true)) $days = 30;
$today = date('Y-m-d');
$from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
$to = $today;
$isYmd = function ($d) { return is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && checkdate((int)substr($d, 5, 2), (int)substr($d, 8, 2), (int)substr($d, 0, 4)); };
if ($isYmd($_GET['from'] ?? '') && $isYmd($_GET['to'] ?? '')) {
    $from = $_GET['from'];
    $to = min($_GET['to'], $today);
    if ($from > $to) am_out(['success' => false, 'error' => 'วันที่เริ่มต้องไม่เกินวันที่สิ้นสุด (และไม่เกินวันนี้)']);
    if ((strtotime($to) - strtotime($from)) / 86400 > 365) am_out(['success' => false, 'error' => 'เลือกช่วงได้ไม่เกิน 1 ปี']);
    $days = (int)round((strtotime($to) - strtotime($from)) / 86400) + 1;
}
$fromTs = $from . ' 00:00:00';
$toTs = $to . ' 23:59:59';

function am_rows($connect, $sql) {
    $o = [];
    $r = mysqli_query($connect, $sql);
    if ($r) while ($x = mysqli_fetch_assoc($r)) $o[] = $x;
    return $o;
}
// ผลแบบจัดกลุ่มตามหน่วยงาน => [ag_id => row]
function am_by_ag($connect, $sql) {
    $o = [];
    foreach (am_rows($connect, $sql) as $r) $o[(string)$r['ag']] = $r;
    return $o;
}
// แปลงข้อความเป็น utf8mb4 collation เดียวกัน (ตารางเก่า/ใหม่ใช้ collation ต่างกัน => UNION แล้ว error "Illegal mix of collations")
function am_t($expr) { return "CONVERT($expr USING utf8mb4) COLLATE utf8mb4_unicode_ci"; }
function am_max() { $m = null; foreach (func_get_args() as $v) if ($v && $v !== '0000-00-00 00:00:00' && ($m === null || $v > $m)) $m = $v; return $m; }

// ผู้ใช้ของแต่ละหน่วยงาน: หน่วยงานหลักของผู้ใช้ + หน่วยงานที่ได้รับมอบหมาย (ช่างหลายหน่วยงาน)
$USER_AG = "(SELECT user_id, CAST(user_agency AS CHAR) AS ag FROM tb_user WHERE user_agency > 0
             UNION SELECT age_user_id, CAST(age_ag_id AS CHAR) FROM tb_agency_employer)";
// แหล่งข้อมูลการจดมิเตอร์ (น้ำ / ไฟ / แก๊ส / TOU)
$METER_SRC = [
    ['tb_toum_detail_wt', 'toumdt_wt_ag_id', 'toumdt_wt_ins', 'toumdt_wt_user_ins', 'toumdt_wt_toum_id', 'น้ำประปา'],
    ['tb_toum_detail_et', 'toumdt_et_ag_id', 'toumdt_et_ins', 'toumdt_et_user_ins', 'toumdt_et_toum_id', 'ไฟฟ้า'],
    ['tb_toum_detail_gt', 'toumdt_gt_ag_id', 'toumdt_gt_ins', 'toumdt_gt_user_ins', 'toumdt_gt_toum_id', 'แก๊ส'],
    ['tb_tou_detail', 'toudt_ag_id', 'toudt_ins', 'toudt_user_ins', 'toudt_tou_id', 'ไฟฟ้า TOU'],
];
// ข้อมูลตั้งค่า (สร้าง / แก้ไข): ตาราง, คอลัมน์หน่วยงาน (หรือ join), สร้าง, แก้ไข, ชื่อรายการ, ป้าย, join, ผู้ทำ
$SETTING_SRC = [
    ['pm_checksheets', 't.ag_id', 't.created_at', 't.updated_at', 't.name', 'เช็คชีต', '', 'COALESCE(t.updated_by, t.created_by)'],
    ['pm_plans', 't.ag_id', 't.created_at', 't.updated_at', "CONCAT('แผน PM #', t.id)", 'แผน PM', '', 't.created_by'],
    ['tb_meter', 't.mr_ag_id', 't.mt_ins', 't.mt_upd', 't.mt_name', 'มิเตอร์', '', 'COALESCE(t.mt_user_id_upd, t.mt_user_ins)'],
    ['tb_area', 't.ag_id', 't.area_ins', 'NULL', 't.area_name', 'อาคาร', '', 't.area_user_ins'],
    ['tb_area_class', 'a.ag_id', 't.ac_ins', 't.ac_upd', 't.ac_name', 'ชั้น', 'JOIN tb_area a ON a.area_id = t.ac_area_id', 't.ac_user_ins'],
    ['tb_area_room', 'a.ag_id', 't.ar_ins', 't.ar_upd', 't.ar_name', 'ห้อง', 'JOIN tb_area a ON a.area_id = t.ar_area_id', 't.ar_user_ins'],
];

if ($action === 'summary') {
    $agencies = am_rows($connect, "SELECT ag_id, ag_job, ag_contract, ag_status, ag_start_date, ag_end_date FROM tb_agency ORDER BY ag_contract");
    $users = am_by_ag($connect, "SELECT ag, COUNT(DISTINCT user_id) AS n FROM $USER_AG ua GROUP BY ag");
    $login = am_by_ag($connect, "SELECT ua.ag, SUM(l.tl_time BETWEEN '$fromTs' AND '$toTs') AS n, COUNT(DISTINCT CASE WHEN l.tl_time BETWEEN '$fromTs' AND '$toTs' THEN l.tl_name END) AS users, MAX(l.tl_time) AS last
        FROM tb_time_login l JOIN $USER_AG ua ON ua.user_id = l.tl_name GROUP BY ua.ag");
    $rep = am_by_ag($connect, "SELECT ag_id AS ag, SUM(created_at BETWEEN '$fromTs' AND '$toTs') AS new_n, SUM(completed_date BETWEEN '$from' AND '$to') AS closed_n,
            SUM(status IN ('pending', 'inprogress')) AS backlog, MAX(GREATEST(COALESCE(created_at, 0), COALESCE(updated_at, 0))) AS last
        FROM repair_requests GROUP BY ag_id");
    $pm = am_by_ag($connect, "SELECT ag_id AS ag, SUM(completed_at BETWEEN '$fromTs' AND '$toTs') AS done_n, SUM(status = 0 AND event_date < '$today') AS overdue,
            SUM(event_date BETWEEN '$from' AND '$to') AS due_n, MAX(completed_at) AS last
        FROM pm_plan_events GROUP BY ag_id");
    $meterParts = [];
    foreach ($METER_SRC as $m) $meterParts[] = "SELECT $m[1] AS ag, $m[2] AS t FROM $m[0]";
    $meter = am_by_ag($connect, "SELECT ag, SUM(t BETWEEN '$fromTs' AND '$toTs') AS n, MAX(t) AS last FROM (" . implode(' UNION ALL ', $meterParts) . ") x GROUP BY ag");
    $stock = am_by_ag($connect, "SELECT ag_id AS ag, SUM(created_at BETWEEN '$fromTs' AND '$toTs') AS n, MAX(created_at) AS last FROM product_stock_transactions GROUP BY ag_id");
    $mach = am_by_ag($connect, "SELECT ass_ag_id AS ag, COUNT(*) AS total, SUM(asset_ins BETWEEN '$fromTs' AND '$toTs') AS added,
            SUM(updated_at BETWEEN '$fromTs' AND '$toTs' AND (asset_ins IS NULL OR asset_ins < '$fromTs')) AS updated, MAX(GREATEST(COALESCE(asset_ins, 0), COALESCE(updated_at, 0))) AS last
        FROM tb_ass_list GROUP BY ass_ag_id");
    $setParts = [];
    foreach ($SETTING_SRC as $st) $setParts[] = "SELECT $st[1] AS ag, GREATEST(COALESCE($st[2], 0), COALESCE($st[3], 0)) AS t FROM $st[0] t $st[6]";
    $set = am_by_ag($connect, "SELECT ag, SUM(t BETWEEN '$fromTs' AND '$toTs') AS n, MAX(t) AS last FROM (" . implode(' UNION ALL ', $setParts) . ") x GROUP BY ag");

    // แนวโน้มรายวันของแต่ละหน่วยงาน (sparkline): เข้าใช้งาน + แจ้งซ่อม + ปิดงาน + PM + มิเตอร์ + สต็อก
    $sp = [];
    $spAdd = function ($rows) use (&$sp) { foreach ($rows as $r) { $k = (string)$r['ag']; if (!isset($sp[$k])) $sp[$k] = []; $sp[$k][$r['d']] = (isset($sp[$k][$r['d']]) ? $sp[$k][$r['d']] : 0) + (int)$r['n']; } };
    $spAdd(am_rows($connect, "SELECT ua.ag, DATE(l.tl_time) AS d, COUNT(*) AS n FROM tb_time_login l JOIN $USER_AG ua ON ua.user_id = l.tl_name WHERE l.tl_time BETWEEN '$fromTs' AND '$toTs' GROUP BY ua.ag, d"));
    $spAdd(am_rows($connect, "SELECT ag_id AS ag, DATE(created_at) AS d, COUNT(*) AS n FROM repair_requests WHERE created_at BETWEEN '$fromTs' AND '$toTs' GROUP BY ag_id, d"));
    $spAdd(am_rows($connect, "SELECT ag_id AS ag, completed_date AS d, COUNT(*) AS n FROM repair_requests WHERE completed_date BETWEEN '$from' AND '$to' GROUP BY ag_id, d"));
    $spAdd(am_rows($connect, "SELECT ag_id AS ag, DATE(completed_at) AS d, COUNT(*) AS n FROM pm_plan_events WHERE completed_at BETWEEN '$fromTs' AND '$toTs' GROUP BY ag_id, d"));
    $spAdd(am_rows($connect, "SELECT ag, DATE(t) AS d, COUNT(*) AS n FROM (" . implode(' UNION ALL ', $meterParts) . ") x WHERE t BETWEEN '$fromTs' AND '$toTs' GROUP BY ag, d"));
    $spAdd(am_rows($connect, "SELECT ag_id AS ag, DATE(created_at) AS d, COUNT(*) AS n FROM product_stock_transactions WHERE created_at BETWEEN '$fromTs' AND '$toTs' GROUP BY ag_id, d"));
    $dayList = [];
    for ($t = strtotime($from); $t <= strtotime($to); $t += 86400) $dayList[] = date('Y-m-d', $t);

    $out = [];
    $now = time();
    foreach ($agencies as $a) {
        $k = (string)$a['ag_id'];
        $g = function ($arr, $f) use ($k) { return isset($arr[$k][$f]) ? $arr[$k][$f] : null; };
        $last = am_max($g($login, 'last'), $g($rep, 'last'), $g($pm, 'last'), $g($meter, 'last'), $g($stock, 'last'), $g($mach, 'last'), $g($set, 'last'));
        $idleDays = $last ? max(0, (int)floor(($now - strtotime($last)) / 86400)) : null;
        $state = $last === null ? 'never' : ($idleDays <= 3 ? 'active' : ($idleDays <= 14 ? 'quiet' : 'idle'));
        $expired = !empty($a['ag_end_date']) && $a['ag_end_date'] !== '0000-00-00' && $a['ag_end_date'] < $today;
        $out[] = [
            'ag_id' => (int)$a['ag_id'], 'code' => $a['ag_job'], 'name' => $a['ag_contract'], 'enabled' => (int)$a['ag_status'] === 1,
            'start' => $a['ag_start_date'], 'end' => $a['ag_end_date'], 'expired' => $expired,
            'last' => $last, 'idle_days' => $idleDays, 'state' => $state,
            'users' => (int)$g($users, 'n'),
            'login' => ['n' => (int)$g($login, 'n'), 'users' => (int)$g($login, 'users'), 'last' => $g($login, 'last')],
            'repair' => ['new' => (int)$g($rep, 'new_n'), 'closed' => (int)$g($rep, 'closed_n'), 'backlog' => (int)$g($rep, 'backlog'), 'last' => $g($rep, 'last')],
            'pm' => ['done' => (int)$g($pm, 'done_n'), 'due' => (int)$g($pm, 'due_n'), 'overdue' => (int)$g($pm, 'overdue'), 'last' => $g($pm, 'last')],
            'meter' => ['n' => (int)$g($meter, 'n'), 'last' => $g($meter, 'last')],
            'stock' => ['n' => (int)$g($stock, 'n'), 'last' => $g($stock, 'last')],
            'machine' => ['total' => (int)$g($mach, 'total'), 'added' => (int)$g($mach, 'added'), 'updated' => (int)$g($mach, 'updated'), 'last' => $g($mach, 'last')],
            'setting' => ['n' => (int)$g($set, 'n'), 'last' => $g($set, 'last')],
            'spark' => array_map(function ($d) use ($sp, $k) { return isset($sp[$k][$d]) ? $sp[$k][$d] : 0; }, $dayList),
        ];
    }
    am_out(['success' => true, 'days' => $days, 'from' => $from, 'to' => $to, 'today' => $today, 'agencies' => $out]);
}

if ($action === 'detail') {
    $ag = (int)($_GET['ag'] ?? 0);
    $a = am_rows($connect, "SELECT ag_id, ag_job, ag_contract FROM tb_agency WHERE ag_id = $ag");
    if (!$a) am_out(['success' => false, 'error' => 'ไม่พบหน่วยงาน']);
    $agS = "'" . mysqli_real_escape_string($connect, (string)$ag) . "'";

    // ---------- รายวัน ----------
    $series = ['login' => [], 'repair_new' => [], 'repair_closed' => [], 'pm_done' => [], 'meter' => [], 'stock' => [], 'machine' => [], 'setting' => []];
    $put = function ($key, $rows) use (&$series) { foreach ($rows as $r) $series[$key][$r['d']] = (int)$r['n']; };
    $put('login', am_rows($connect, "SELECT DATE(l.tl_time) AS d, COUNT(*) AS n FROM tb_time_login l JOIN $USER_AG ua ON ua.user_id = l.tl_name AND ua.ag = $agS WHERE l.tl_time BETWEEN '$fromTs' AND '$toTs' GROUP BY d"));
    $put('repair_new', am_rows($connect, "SELECT DATE(created_at) AS d, COUNT(*) AS n FROM repair_requests WHERE ag_id = $ag AND created_at BETWEEN '$fromTs' AND '$toTs' GROUP BY d"));
    $put('repair_closed', am_rows($connect, "SELECT completed_date AS d, COUNT(*) AS n FROM repair_requests WHERE ag_id = $ag AND completed_date BETWEEN '$from' AND '$to' GROUP BY d"));
    $put('pm_done', am_rows($connect, "SELECT DATE(completed_at) AS d, COUNT(*) AS n FROM pm_plan_events WHERE ag_id = $agS AND completed_at BETWEEN '$fromTs' AND '$toTs' GROUP BY d"));
    $mp = [];
    foreach ($METER_SRC as $m) $mp[] = "SELECT $m[2] AS t FROM $m[0] WHERE $m[1] = $agS AND $m[2] BETWEEN '$fromTs' AND '$toTs'";
    $put('meter', am_rows($connect, "SELECT DATE(t) AS d, COUNT(*) AS n FROM (" . implode(' UNION ALL ', $mp) . ") x GROUP BY d"));
    $put('stock', am_rows($connect, "SELECT DATE(created_at) AS d, COUNT(*) AS n FROM product_stock_transactions WHERE ag_id = $agS AND created_at BETWEEN '$fromTs' AND '$toTs' GROUP BY d"));
    $put('machine', am_rows($connect, "SELECT DATE(GREATEST(COALESCE(asset_ins, 0), COALESCE(updated_at, 0))) AS d, COUNT(*) AS n FROM tb_ass_list
        WHERE ass_ag_id = $agS AND GREATEST(COALESCE(asset_ins, 0), COALESCE(updated_at, 0)) BETWEEN '$fromTs' AND '$toTs' GROUP BY d"));
    $sp = [];
    foreach ($SETTING_SRC as $st) $sp[] = "SELECT GREATEST(COALESCE($st[2], 0), COALESCE($st[3], 0)) AS t FROM $st[0] t $st[6] WHERE $st[1] = $agS";
    $put('setting', am_rows($connect, "SELECT DATE(t) AS d, COUNT(*) AS n FROM (" . implode(' UNION ALL ', $sp) . ") x WHERE t BETWEEN '$fromTs' AND '$toTs' GROUP BY d"));
    $daysList = [];
    for ($t = strtotime($from); $t <= strtotime($to); $t += 86400) $daysList[] = date('Y-m-d', $t);
    $daily = [];
    foreach ($series as $k => $m) { $daily[$k] = []; foreach ($daysList as $d) $daily[$k][] = isset($m[$d]) ? $m[$d] : 0; }

    // ---------- ความเคลื่อนไหวล่าสุด (รวมทุกประเภท) ----------
    $L = 12;
    $feed = [];
    $add = function ($type, $rows) use (&$feed) { foreach ($rows as $r) { $r['type'] = $type; $feed[] = $r; } };
    $add('login', am_rows($connect, "SELECT l.tl_time AS t, l.tl_name AS user, IF(" . ($TLT === 'NULL' ? 'NULL' : 'l.tl_type') . " = 'qr', 'เข้าสู่ระบบด้วย QR', 'เข้าสู่ระบบ') AS text FROM tb_time_login l JOIN $USER_AG ua ON ua.user_id = l.tl_name AND ua.ag = $agS ORDER BY l.tl_time DESC LIMIT $L"));
    $add('repair', am_rows($connect, "SELECT created_at AS t, created_by AS user, CONCAT('แจ้งซ่อม ', IFNULL(rp_format, ''), ' — ', LEFT(IFNULL(problem_detail, ''), 80)) AS text FROM repair_requests WHERE ag_id = $ag ORDER BY created_at DESC LIMIT $L"));
    $add('repair_done', am_rows($connect, "SELECT CONCAT(completed_date, ' ', IFNULL(completed_time, '00:00:00')) AS t, completed_by AS user, CONCAT('ปิดงานซ่อม ', IFNULL(rp_format, '')) AS text FROM repair_requests WHERE ag_id = $ag AND completed_date IS NOT NULL ORDER BY completed_date DESC, completed_time DESC LIMIT $L"));
    $add('pm', am_rows($connect, "SELECT wr.created_at AS t, wr.created_by AS user, CONCAT('บันทึก PM ', IFNULL(wr.doc_no, ''), ' — ', IFNULL(wr.machine_name, '')) AS text FROM pm_work_records wr WHERE wr.ag_id = $agS ORDER BY wr.created_at DESC LIMIT $L"));
    $mf = [];
    foreach ($METER_SRC as $m) $mf[] = "(SELECT d.$m[2] AS t, " . am_t("d.$m[3]") . " AS user, " . am_t("CONCAT('จดมิเตอร์$m[5] ', IFNULL(mt.mt_name, ''))") . " AS text FROM $m[0] d LEFT JOIN tb_meter mt ON mt.mt_id = d.$m[4] WHERE d.$m[1] = $agS ORDER BY d.$m[2] DESC LIMIT $L)";
    $add('meter', am_rows($connect, implode(' UNION ALL ', $mf)));
    $add('stock', am_rows($connect, "SELECT created_at AS t, user_id AS user, CONCAT(CASE trans_type WHEN 'IN' THEN 'รับเข้า' WHEN 'OUT' THEN 'เบิกจ่าย' ELSE 'ปรับยอด' END, ' ', IFNULL(snap_pd_name, ''), ' ', ABS(qty_change), ' ', IFNULL(snap_unit, '')) AS text FROM product_stock_transactions WHERE ag_id = $agS ORDER BY created_at DESC LIMIT $L"));
    $add('machine', am_rows($connect, "SELECT GREATEST(COALESCE(asset_ins, 0), COALESCE(updated_at, 0)) AS t, COALESCE(updated_by, created_by) AS user,
            CONCAT(IF(updated_at IS NOT NULL AND updated_at > asset_ins, 'แก้ไขเครื่องจักร ', 'เพิ่มเครื่องจักร '), IFNULL(ass_code, ''), ' ', IFNULL(asset_name, '')) AS text
        FROM tb_ass_list WHERE ass_ag_id = $agS ORDER BY t DESC LIMIT $L"));
    $sf = [];
    foreach ($SETTING_SRC as $st) $sf[] = "(SELECT GREATEST(COALESCE($st[2], 0), COALESCE($st[3], 0)) AS t, " . am_t($st[7]) . " AS user, " . am_t("CONCAT('ตั้งค่า$st[5]: ', IFNULL($st[4], ''))") . " AS text FROM $st[0] t $st[6] WHERE $st[1] = $agS ORDER BY t DESC LIMIT $L)";
    $add('setting', am_rows($connect, implode(' UNION ALL ', $sf)));
    $feed = array_values(array_filter($feed, function ($f) { return !empty($f['t']) && $f['t'] !== '0000-00-00 00:00:00' && substr($f['t'], 0, 4) !== '0000'; }));
    usort($feed, function ($x, $y) { return strcmp($y['t'], $x['t']); });
    // รวมรายการประเภทเดียวกัน คนเดียวกัน นาทีเดียวกัน (เช่น นำเข้า/แก้ไขทีละหลายร้อยรายการ) => "… และอีก N รายการ"
    $merged = [];
    foreach ($feed as $f) {
        $k = $f['type'] . '|' . $f['user'] . '|' . substr($f['t'], 0, 16);
        $last = count($merged) - 1;
        if ($f['type'] !== 'login' && $last >= 0 && $merged[$last]['_k'] === $k) { $merged[$last]['more']++; continue; }
        $f['_k'] = $k; $f['more'] = 0; $merged[] = $f;
    }
    foreach ($merged as &$f) { unset($f['_k']); } unset($f);
    $feed = array_slice($merged, 0, 60);
    // ชื่อผู้ใช้ (ชื่อ นามสกุล)
    $ids = array_values(array_unique(array_filter(array_map(function ($f) { return (string)$f['user']; }, $feed))));
    $names = [];
    if ($ids) {
        $in = implode(',', array_map(function ($u) use ($connect) { return "'" . mysqli_real_escape_string($connect, $u) . "'"; }, $ids));
        foreach (am_rows($connect, "SELECT user_id, TRIM(CONCAT(IFNULL(user_name, ''), ' ', IFNULL(user_fname, ''))) AS nm FROM tb_user WHERE user_id IN ($in)") as $u) $names[$u['user_id']] = $u['nm'];
    }
    foreach ($feed as &$f) $f['user_name'] = isset($names[(string)$f['user']]) && $names[(string)$f['user']] !== '' ? $names[(string)$f['user']] : (string)$f['user'];
    unset($f);

    // ---------- ผู้ใช้ของหน่วยงาน + การเข้าใช้งาน ----------
    $users = am_rows($connect, "SELECT u.user_id, TRIM(CONCAT(IFNULL(u.user_name, ''), ' ', IFNULL(u.user_fname, ''))) AS name, u.user_level, u.user_position,
            SUM(l.tl_time BETWEEN '$fromTs' AND '$toTs') AS n, MAX(l.tl_time) AS last
        FROM (SELECT DISTINCT user_id FROM $USER_AG ua WHERE ua.ag = $agS) x
        JOIN tb_user u ON u.user_id = x.user_id
        LEFT JOIN tb_time_login l ON l.tl_name = u.user_id
        GROUP BY u.user_id, u.user_name, u.user_fname, u.user_level, u.user_position
        ORDER BY n DESC, last DESC LIMIT 200");
    foreach ($users as &$u) $u['n'] = (int)$u['n'];
    unset($u);

    am_out(['success' => true, 'agency' => $a[0], 'days' => $daysList, 'daily' => $daily, 'feed' => $feed, 'users' => $users]);
}

// ---------- ประวัติเข้าสู่ระบบของผู้ใช้ 1 คน (กดชื่อในรายการผู้ใช้งาน) ----------
//  GET ?action=user_logins&ag=ID&user=USER_ID&days=...   => days: [{d, times: ['09:25:10', ...]}]
if ($action === 'user_logins') {
    $ag = (int)($_GET['ag'] ?? 0);
    $user = (string)($_GET['user'] ?? '');
    $agS = "'" . mysqli_real_escape_string($connect, (string)$ag) . "'";
    $uS = "'" . mysqli_real_escape_string($connect, $user) . "'";
    // ต้องเป็นผู้ใช้ของหน่วยงานนี้
    if (!am_rows($connect, "SELECT 1 FROM $USER_AG ua WHERE ua.user_id = $uS AND ua.ag = $agS LIMIT 1")) am_out(['success' => false, 'error' => 'ไม่พบผู้ใช้ในหน่วยงานนี้']);
    $rows = am_rows($connect, "SELECT tl_time AS t, $TLT AS k FROM tb_time_login WHERE tl_name = $uS AND tl_time BETWEEN '$fromTs' AND '$toTs' ORDER BY tl_time DESC LIMIT 1000");
    $byDay = [];
    $byType = []; $cnt = ['qr' => 0, 'password' => 0];
    foreach ($rows as $r) {
        $d = substr($r['t'], 0, 10); $byDay[$d][] = substr($r['t'], 11, 8); $byType[$d][] = (string)$r['k'];
        if (isset($cnt[$r['k']])) $cnt[$r['k']]++;
    }
    $out = [];
    foreach ($byDay as $d => $times) $out[] = ['d' => $d, 'times' => $times, 'types' => $byType[$d]];   // types: qr | password | '' (ก่อนเริ่มบันทึกวิธีเข้า)
    $last = am_rows($connect, "SELECT MAX(tl_time) AS t FROM tb_time_login WHERE tl_name = $uS");
    am_out(['success' => true, 'days' => $out, 'total' => count($rows), 'by_type' => $cnt, 'last_ever' => $last ? $last[0]['t'] : null]);
}

// ---------- รายการข้อมูลจริงของแต่ละประเภท (กดตัวเลขเพื่อดู) ----------
//  GET ?action=list&ag=ID&days=...&type=login|repair|pm|meter|stock|data   => rows: [{t, user, title, sub, status}]
if ($action === 'list') {
    $ag = (int)($_GET['ag'] ?? 0);
    $agS = "'" . mysqli_real_escape_string($connect, (string)$ag) . "'";
    $type = $_GET['type'] ?? '';
    $LIM = !empty($_GET['export']) ? 20000 : 300;   // ส่งออก Excel => ทั้งหมด (สูงสุด 20,000)
    $rows = [];
    if ($type === 'login') {
        $rows = am_rows($connect, "SELECT l.tl_time AS t, l.tl_name AS user, 'เข้าสู่ระบบ' AS title, '' AS sub, " . ($TLT === 'NULL' ? "''" : "CASE l.tl_type WHEN 'qr' THEN 'QR' WHEN 'password' THEN 'รหัสผ่าน' ELSE '' END") . " AS status
            FROM tb_time_login l JOIN $USER_AG ua ON ua.user_id = l.tl_name AND ua.ag = $agS WHERE l.tl_time BETWEEN '$fromTs' AND '$toTs' ORDER BY l.tl_time DESC LIMIT $LIM");
    } elseif ($type === 'repair') {   // แจ้งในช่วงนี้ หรือปิดงานในช่วงนี้
        $rows = am_rows($connect, "SELECT r.created_at AS t, r.created_by AS user, CONCAT(IFNULL(r.rp_format, ''), ' — ', IFNULL(r.problem_detail, '')) AS title,
                TRIM(CONCAT(IFNULL(a.area_name, ''), ' ', IFNULL(c.ac_name, ''), ' ', IFNULL(rm.ar_name, ''))) AS sub, r.status,
                IF(r.completed_date IS NOT NULL, CONCAT(r.completed_date, ' ', IFNULL(r.completed_time, '')), NULL) AS done_at
            FROM repair_requests r
            LEFT JOIN tb_area a ON a.area_id = r.building LEFT JOIN tb_area_class c ON c.ac_id = r.floor LEFT JOIN tb_area_room rm ON rm.ar_id = r.room
            WHERE r.ag_id = $ag AND (r.created_at BETWEEN '$fromTs' AND '$toTs' OR r.completed_date BETWEEN '$from' AND '$to') ORDER BY r.created_at DESC LIMIT $LIM");
    } elseif ($type === 'pm') {
        $rows = am_rows($connect, "SELECT wr.created_at AS t, wr.created_by AS user, CONCAT(IFNULL(wr.doc_no, ''), ' — ', IFNULL(wr.machine_code, ''), ' ', IFNULL(wr.machine_name, '')) AS title,
                CONCAT(IFNULL(wr.checksheet_name, ''), ' · กำหนด ', IFNULL(wr.plan_date, '-')) AS sub, IF(e.evaluation_status = 1, 'evaluated', 'done') AS status
            FROM pm_work_records wr LEFT JOIN pm_plan_events e ON e.id = wr.plan_event_id
            WHERE wr.ag_id = $agS AND wr.created_at BETWEEN '$fromTs' AND '$toTs' ORDER BY wr.created_at DESC LIMIT $LIM");
    } elseif ($type === 'meter') {
        $parts = [];
        foreach ($METER_SRC as $m) $parts[] = "(SELECT d.$m[2] AS t, " . am_t("d.$m[3]") . " AS user, " . am_t("CONCAT('$m[5] · ', IFNULL(mt.mt_name, '-'))") . " AS title, '' AS sub, '' AS status
            FROM $m[0] d LEFT JOIN tb_meter mt ON mt.mt_id = d.$m[4] WHERE d.$m[1] = $agS AND d.$m[2] BETWEEN '$fromTs' AND '$toTs')";
        $rows = am_rows($connect, "SELECT * FROM (" . implode(' UNION ALL ', $parts) . ") x ORDER BY t DESC LIMIT $LIM");
    } elseif ($type === 'stock') {
        $rows = am_rows($connect, "SELECT created_at AS t, user_id AS user, CONCAT(IFNULL(snap_pd_name, ''), ' ', ABS(qty_change), ' ', IFNULL(snap_unit, '')) AS title,
                CONCAT(IFNULL(doc_no, ''), IF(snap_wh_name IS NULL, '', CONCAT(' · ', snap_wh_name))) AS sub, trans_type AS status
            FROM product_stock_transactions WHERE ag_id = $agS AND created_at BETWEEN '$fromTs' AND '$toTs' ORDER BY created_at DESC LIMIT $LIM");
    } elseif ($type === 'data') {   // เครื่องจักร + ข้อมูลตั้งค่า ที่เพิ่ม/แก้ไขในช่วงนี้
        $parts = ["(SELECT GREATEST(COALESCE(asset_ins, 0), COALESCE(updated_at, 0)) AS t, " . am_t("COALESCE(updated_by, created_by)") . " AS user,
                " . am_t("CONCAT(IFNULL(ass_code, ''), ' ', IFNULL(asset_name, ''))") . " AS title, " . am_t("'เครื่องจักร'") . " AS sub,
                " . am_t("IF(updated_at IS NOT NULL AND updated_at > asset_ins, 'edit', 'add')") . " AS status
            FROM tb_ass_list WHERE ass_ag_id = $agS AND GREATEST(COALESCE(asset_ins, 0), COALESCE(updated_at, 0)) BETWEEN '$fromTs' AND '$toTs')"];
        foreach ($SETTING_SRC as $st) $parts[] = "(SELECT GREATEST(COALESCE($st[2], 0), COALESCE($st[3], 0)) AS t, " . am_t($st[7]) . " AS user, " . am_t("IFNULL($st[4], '')") . " AS title, " . am_t("'$st[5]'") . " AS sub,
                " . am_t("IF($st[3] IS NOT NULL AND $st[3] > $st[2], 'edit', 'add')") . " AS status
            FROM $st[0] t $st[6] WHERE $st[1] = $agS AND GREATEST(COALESCE($st[2], 0), COALESCE($st[3], 0)) BETWEEN '$fromTs' AND '$toTs')";
        $rows = am_rows($connect, "SELECT * FROM (" . implode(' UNION ALL ', $parts) . ") x ORDER BY t DESC LIMIT $LIM");
    } else {
        am_out(['success' => false, 'error' => 'ไม่รู้จักประเภทข้อมูล']);
    }
    $ids = array_values(array_unique(array_filter(array_map(function ($r) { return (string)$r['user']; }, $rows))));
    $names = [];
    if ($ids) {
        $in = implode(',', array_map(function ($u) use ($connect) { return "'" . mysqli_real_escape_string($connect, $u) . "'"; }, $ids));
        foreach (am_rows($connect, "SELECT user_id, TRIM(CONCAT(IFNULL(user_name, ''), ' ', IFNULL(user_fname, ''))) AS nm FROM tb_user WHERE user_id IN ($in)") as $u) $names[$u['user_id']] = $u['nm'];
    }
    foreach ($rows as &$r) $r['user_name'] = isset($names[(string)$r['user']]) && $names[(string)$r['user']] !== '' ? $names[(string)$r['user']] : (string)$r['user'];
    unset($r);
    am_out(['success' => true, 'rows' => $rows, 'limit' => $LIM]);
}

am_out(['success' => false, 'error' => 'unknown action'], 400);
