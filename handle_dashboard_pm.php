<?php
// ข้อมูลกราฟ PM บนหน้า Dashboard (dashboard.php → ส่วน PM Monitoring)
//  ?startDate=YYYY-MM-DD&endDate=YYYY-MM-DD[&area_id=]
//  ช่วงข้อมูลอิงวันที่ตามแผน (pm_plan_events.event_date) — หน่วยงานจาก session
@session_start();
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');
include 'config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');
require_once __DIR__ . '/config_ctrl/pm_approve.php';

function out($a, $code = 200) { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

$ag = (string)($_SESSION['sess_user_agency'] ?? '');
if ($ag === '' || empty($_SESSION['sess_user_id_es'])) out(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบใหม่'], 401);

$d = function ($v, $def) { $v = trim((string)$v); return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : $def; };
$from = $d($_GET['startDate'] ?? '', date('Y-m-01'));
$to   = $d($_GET['endDate'] ?? '', date('Y-m-t'));
if ($to < $from) { $t = $from; $from = $to; $to = $t; }
$area = trim((string)($_GET['area_id'] ?? ''));

$where = " WHERE e.ag_id = ? AND e.event_date BETWEEN ? AND ? ";
$types = 'sss';
$params = [$ag, $from, $to];
if ($area !== '') { $where .= " AND a.asset_rp_area_id = ? "; $types .= 's'; $params[] = $area; }
$JOIN = " FROM pm_plan_events e LEFT JOIN tb_ass_list a ON a.ass_id = e.machine_id ";

// สถานะของรอบแผน: ทำตามแผน / ทำล่าช้า / รอหัวหน้าช่างตรวจรับ / ค้าง (เลยกำหนด) / ยังไม่ถึงกำหนด
$DONE = pmap_done_sql($connect, 'e');
$STATES = "COUNT(*) AS planned,
    SUM($DONE AND COALESCE(DATE(e.completed_at), e.event_date) <= e.event_date) AS ontime,
    SUM($DONE AND COALESCE(DATE(e.completed_at), e.event_date) > e.event_date) AS late,
    SUM(" . pmap_wait_sql($connect, 'e') . ") AS waiting,
    SUM(e.status <> 1 AND e.event_date < CURDATE()) AS overdue,
    SUM(e.status <> 1 AND e.event_date >= CURDATE()) AS upcoming";

function q($connect, $sql, $types, $params) {
    $st = $connect->prepare($sql);
    if (!$st) throw new Exception($connect->error);
    $st->bind_param($types, ...$params);
    $st->execute();
    $r = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $r;
}
$int = function ($r) { foreach (['planned', 'ontime', 'late', 'waiting', 'overdue', 'upcoming', 'machines', 'n'] as $k) if (isset($r[$k])) $r[$k] = (int)$r[$k]; return $r; };

try {
    $rows = array_map($int, q($connect, "SELECT $STATES $JOIN $where", $types, $params));

    // รวม
    $k = ['planned' => 0, 'ontime' => 0, 'late' => 0, 'waiting' => 0, 'overdue' => 0, 'upcoming' => 0];
    foreach ($rows as $r) foreach ($k as $key => $_) $k[$key] += (int)$r[$key];
    $k['done'] = $k['ontime'] + $k['late'];
    $k['rate'] = $k['planned'] ? round($k['done'] * 100 / $k['planned'], 1) : 0;
    $k['ontime_rate'] = $k['done'] ? round($k['ontime'] * 100 / $k['done'], 1) : 0;

    // ตามประเภทเครื่อง
    $byType = array_map($int, q($connect, "SELECT COALESCE(g.TGroupName, 'ไม่ระบุประเภท') AS name, COUNT(DISTINCT e.machine_id) AS machines, $STATES
        $JOIN LEFT JOIN tb_asset_group g ON g.GroupId = a.asset_type $where GROUP BY name ORDER BY planned DESC", $types, $params));

    out(['success' => true, 'from' => $from, 'to' => $to, 'kpi' => $k, 'by_type' => $byType]);
} catch (Throwable $e) {
    out(['success' => false, 'error' => $e->getMessage()], 500);
}
