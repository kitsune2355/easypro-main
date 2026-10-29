<?php
//handle_pm_daily.php — งานที่ต้องทำ (pm_daily.php)
//  งานที่ต้องทำส่งเฉพาะตัวเลขสรุป/กลุ่ม (summary) แล้วโหลดรายการเครื่องทีละหน้าเมื่อเปิดกลุ่ม (items)
//  เพื่อไม่ให้หน่วยงานที่มีงานหลายหมื่นรายการต้องโหลดข้อมูลทั้งหมดในครั้งเดียว
@session_start();
include 'config_ctrl/connect.php';
require_once __DIR__ . '/config_ctrl/pm_approve.php';   // งานรอหัวหน้าช่างตรวจรับ (ยังไม่นับว่าทำแล้ว)

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

$user_id = $_SESSION['sess_user_id_es'] ?? '';
$ag_id   = $_SESSION['sess_user_agency'] ?? '';

if (!$user_id) {
    http_response_code(401);
    jsonOut(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบใหม่']);
}
if (!$ag_id) {
    jsonOut(['success' => false, 'error' => 'ไม่พบหน่วยงาน (AG_ID) กรุณาเลือกหน่วยงานก่อน']);
}

$action = $_GET['action'] ?? null;

function pmdQuery($connect, $sql, $types, $params) {
    $stmt = mysqli_prepare($connect, $sql);
    if (!$stmt) jsonOut(['success' => false, 'error' => 'Query preparation failed: ' . mysqli_error($connect)]);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $rows;
}

const PMD_FROM = "FROM pm_plan_events e
            JOIN pm_plans p ON p.id = e.plan_id AND p.status = 1
            LEFT JOIN pm_checksheets c ON e.checksheet_id = c.id
            LEFT JOIN tb_ass_list a ON e.machine_id = a.ass_id
            LEFT JOIN tb_asset_group g ON a.asset_type = g.GroupId
            LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
            LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
            LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id";

/**
 * เงื่อนไขร่วมของ summary/items จาก $_GET
 *  date, date_to (ไม่เกิน 31 วัน), overdue=1 (รวมงานค้างก่อนวันเริ่ม), q (ค้นหา), type (ประเภทเครื่อง),
 *  group (checksheet|location|machine_type|event_date), filter (todo|overdue|done|all)
 *  $withSearch = false => ไม่ใช้ q/type (ใช้หารายการประเภทเครื่องสำหรับตัวกรอง)
 */
function pmdFilters($ag_id, $withSearch = true) {
    $isDate = function ($s) { return is_string($s) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s); };
    $today = date('Y-m-d');
    $date = $isDate($_GET['date'] ?? '') ? $_GET['date'] : $today;
    $dateTo = $isDate($_GET['date_to'] ?? '') ? $_GET['date_to'] : $date;
    $maxTo = date('Y-m-d', strtotime("$date +30 days"));
    if ($dateTo < $date) $dateTo = $date;
    if ($dateTo > $maxTo) $dateTo = $maxTo;

    $where = ["e.ag_id = ?"];
    $types = "s";
    $params = [$ag_id];
    if (($_GET['overdue'] ?? '') === '1') {
        $where[] = "(e.event_date BETWEEN ? AND ? OR (e.event_date < ? AND e.status = 0))";
        $types .= "sss";
        array_push($params, $date, $dateTo, $date);
    } else {
        $where[] = "e.event_date BETWEEN ? AND ?";
        $types .= "ss";
        array_push($params, $date, $dateTo);
    }
    if ($withSearch) {
        $type = trim((string)($_GET['type'] ?? ''));
        if ($type !== '') {
            $where[] = "g.TGroupName = ?";
            $types .= "s";
            $params[] = $type;
        }
        // q = ค้นหาหลัก, q2 = ค้นหาในกลุ่มที่เปิดอยู่ (หน้ารายการเครื่องบนมือถือ) — ใช้ร่วมกันได้
        foreach (['q', 'q2'] as $qk) {
            $q = trim((string)($_GET[$qk] ?? ''));
            if ($q === '') continue;
            // คอลัมน์ต่างตารางมี collation ต่างกัน => แปลงเป็น utf8mb4 ก่อนค้นหา (ไม่สนตัวพิมพ์เล็ก/ใหญ่)
            $where[] = "CONVERT(CONCAT_WS(' ', a.asset_name, a.ass_code, c.name, g.TGroupName, ar.area_name, ac.ac_name, rm.ar_name) USING utf8mb4) LIKE ?";
            $types .= "s";
            $params[] = '%' . $q . '%';
        }
    }

    // กลุ่ม => [นิพจน์คีย์, นิพจน์ชื่อกลุ่ม]
    $groups = [
        'checksheet'   => ["CAST(e.checksheet_id AS CHAR)", "MAX(c.name)"],
        'location'     => ["CONCAT_WS(' / ', NULLIF(ar.area_name, ''), NULLIF(ac.ac_name, ''), NULLIF(rm.ar_name, ''))", null],
        'machine_type' => ["COALESCE(g.TGroupName, '')", null],
        'event_date'   => ["CAST(e.event_date AS CHAR)", null],
    ];
    $group = $_GET['group'] ?? 'checksheet';
    if (!isset($groups[$group])) $group = 'checksheet';

    $filters = [
        'todo'    => "e.status = 0",
        'overdue' => "e.status = 0 AND e.event_date < '$today'",
        'done'    => pmap_done_sql($GLOBALS['connect'], 'e'),
        'waiting' => pmap_wait_sql($GLOBALS['connect'], 'e'),
        'all'     => "1=1",
    ];
    $filter = $_GET['filter'] ?? 'todo';
    if (!isset($filters[$filter])) $filter = 'todo';

    return [
        'date' => $date, 'date_to' => $dateTo, 'today' => $today,
        'where' => implode(' AND ', $where), 'types' => $types, 'params' => $params,
        'group' => $group, 'key' => $groups[$group][0], 'label' => $groups[$group][1] ?: $groups[$group][0],
        'filter_sql' => $filters[$filter],
    ];
}

// ตัวเลขสรุปตามสถานะ + รายการกลุ่ม (ยังไม่มีรายการเครื่อง)
if ($action === 'summary') {
    $f = pmdFilters($ag_id);
    $today = $f['today'];
    $from = PMD_FROM;

    $DONE = pmap_done_sql($connect, 'e');
    $c = pmdQuery($connect, "SELECT COUNT(*) AS total, SUM($DONE) AS done, SUM(" . pmap_wait_sql($connect, 'e') . ") AS waiting,
                SUM(e.status = 0 AND e.event_date < '$today') AS overdue, SUM(e.status = 0 AND e.event_date >= '$today') AS pending
            $from WHERE {$f['where']}", $f['types'], $f['params'])[0];
    $counts = ['all' => (int)$c['total'], 'done' => (int)$c['done'], 'waiting' => (int)$c['waiting'], 'overdue' => (int)$c['overdue'], 'pending' => (int)$c['pending']];
    $counts['todo'] = $counts['overdue'] + $counts['pending'];

    // n = จำนวนตามชิปสถานะที่เลือก, total/done = ความคืบหน้าของทั้งกลุ่ม; กลุ่มที่มีงานค้างขึ้นก่อน
    $groups = pmdQuery($connect, "SELECT {$f['key']} AS k, {$f['label']} AS label, COUNT(*) AS total, SUM($DONE) AS done,
                SUM(e.status = 0 AND e.event_date < '$today') AS overdue, SUM({$f['filter_sql']}) AS n
            $from WHERE {$f['where']}
            GROUP BY k HAVING n > 0
            ORDER BY SUM(e.status = 0 AND e.event_date < '$today') > 0 DESC, " . ($f['group'] === 'event_date' ? "k" : "label") . "
            LIMIT 500", $f['types'], $f['params']);

    // บันทึกพร้อมกัน: เช็คชีตเดียวกัน วันเดียวกัน ยังไม่ทำ ตั้งแต่ 2 เครื่อง (แสดงไม่เกิน 3 วันแรกต่อกลุ่ม)
    $batch = [];
    if ($f['group'] === 'checksheet') {
        $sql = "SELECT e.checksheet_id AS k, e.event_date AS d, COUNT(*) AS n
                $from WHERE {$f['where']} AND e.status = 0
                GROUP BY e.checksheet_id, e.event_date HAVING n > 1 ORDER BY e.event_date";
        foreach (pmdQuery($connect, $sql, $f['types'], $f['params']) as $b) {
            if (count($batch[$b['k']] ?? []) < 3) $batch[$b['k']][] = ['date' => $b['d'], 'n' => (int)$b['n']];
        }
    }

    // ประเภทเครื่องของงานในช่วงนี้ (สำหรับตัวกรอง ไม่ขึ้นกับคำค้นหา/ประเภทที่เลือก)
    $fa = pmdFilters($ag_id, false);
    $typeList = array_column(pmdQuery($connect, "SELECT DISTINCT g.TGroupName AS t $from
            WHERE {$fa['where']} AND g.TGroupName IS NOT NULL ORDER BY t", $fa['types'], $fa['params']), 't');

    jsonOut([
        'success' => true, 'date' => $f['date'], 'date_to' => $f['date_to'], 'today' => $today,
        'counts' => $counts, 'types' => $typeList,
        'groups' => array_map(function ($g) use ($batch) {
            return ['key' => (string)$g['k'], 'label' => (string)($g['label'] ?? ''), 'n' => (int)$g['n'],
                    'total' => (int)$g['total'], 'done' => (int)$g['done'], 'overdue' => (int)$g['overdue'],
                    'batch' => $batch[$g['k']] ?? []];
        }, $groups),
    ]);
}

// รายการเครื่องในกลุ่มเดียว ทีละหน้า (key, offset, limit)
if ($action === 'items') {
    $f = pmdFilters($ag_id);
    $offset = max(0, intval($_GET['offset'] ?? 0));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 30)));
    $from = PMD_FROM;
    $rows = pmdQuery($connect, "SELECT e.id, e.event_date, e.status, " . pmap_col_sql($connect, 'e') . " AS approval_status, e.work_record_id, e.checksheet_id,
                c.name AS checksheet_name, a.ass_code AS machine_code, a.asset_name AS machine_name, g.TGroupName AS machine_type,
                CONCAT_WS(' / ', NULLIF(ar.area_name, ''), NULLIF(ac.ac_name, ''), NULLIF(rm.ar_name, '')) AS location,
                r.inspector_name
            $from
            LEFT JOIN pm_work_records r ON r.id = e.work_record_id
            WHERE {$f['where']} AND {$f['filter_sql']} AND {$f['key']} = ?
            ORDER BY e.event_date, a.ass_code, e.id
            LIMIT $limit OFFSET $offset", $f['types'] . "s", array_merge($f['params'], [(string)($_GET['key'] ?? '')]));

    jsonOut(['success' => true, 'data' => array_map(function ($r) {
        return [
            'id' => (int)$r['id'], 'event_date' => $r['event_date'], 'status' => (int)$r['status'],
            'waiting' => (int)$r['status'] === 1 && $r['approval_status'] !== null && (int)$r['approval_status'] === 0,
            'has_record' => (int)$r['work_record_id'] > 0, 'inspector' => $r['inspector_name'],
            'checksheet_id' => (int)$r['checksheet_id'], 'checksheet' => $r['checksheet_name'] ?? '',
            'machine_code' => $r['machine_code'] ?? '', 'machine_name' => $r['machine_name'] ?? '',
            'machine_type' => $r['machine_type'] ?? '', 'location' => $r['location'] ?? '',
        ];
    }, $rows)]);
}

jsonOut(['success' => false, 'error' => 'ไม่ระบุการดำเนินการ (action)']);
