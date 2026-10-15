<?php
// API รายงานส่งมอบ PM (แท็บ "รายงาน PM" ในโหมดจัดการแผน)
//  ?action=options            ตัวเลือกตัวกรอง (กลุ่มเครื่อง / เช็คชีต)
//  ?action=preview&from&to... ตัวเลขสรุป + รายชื่อเครื่อง (ไม่รวมรายการย่อย)
//  ?action=data&from&to...    ข้อมูลทั้งหมดสำหรับ Export Excel
//  ?action=history&from&to... PM History (1 แถวต่อใบงาน)
@session_start();
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');
include 'config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');
require_once __DIR__ . '/config_ctrl/pm_report_data.php';

function out($a, $code = 200) { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

$ag = (string)($_SESSION['sess_user_agency'] ?? '');
if ($ag === '' || empty($_SESSION['sess_user_id_es'])) out(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบใหม่'], 401);
if (!pmr_can_manage($connect)) out(['success' => false, 'error' => 'ไม่มีสิทธิ์ใช้งานรายงาน PM'], 403);

ini_set('memory_limit', '512M');
set_time_limit(300);

$action = $_REQUEST['action'] ?? '';
try {
    if ($action === 'options') out(['success' => true] + pmr_options($connect, $ag));

    $f = pmr_filters($_REQUEST);
    if ($action === 'history') out(['success' => true, 'agency' => pmr_agency($connect, $ag), 'filters' => $f, 'rows' => pmr_history($connect, $ag, $f)]);
    $r = pmr_build($connect, $ag, $f);

    if ($action === 'preview') {
        $hist = pmr_history($connect, $ag, $f);
        foreach ($r['machines'] as &$m) unset($m['events']);
        unset($m);
        out(['success' => true, 'agency' => $r['agency'], 'filters' => $f, 'kpi' => $r['kpi'],
             'by_type' => $r['by_type'], 'by_checksheet' => $r['by_checksheet'], 'machines' => $r['machines'],
             'counts' => ['fails' => count($r['fails']), 'spares' => count($r['spares']), 'evaluations' => count($r['evaluations']), 'postpones' => count($r['postpones'])],
             'history_count' => count($hist), 'history_sample' => array_slice($hist, 0, 6)]);
    }

    if ($action === 'data') {
        $events = [];
        foreach ($r['machines'] as &$m) {
            foreach ($m['events'] as $e) {
                $events[] = ['code' => $e['ass_code'], 'name' => $e['asset_name'], 'type' => $e['type_name'], 'location' => $e['location'],
                    'checksheet' => $e['cs_name'], 'doc_no' => $e['doc_no'], 'freq' => $e['freq'], 'plan_date' => $e['event_date'],
                    'actual_date' => $e['actual_date'], 'state' => pmr_state_label($e['state']), 'inspector' => $e['inspector_name'],
                    'pass' => $e['pass'], 'fail' => $e['fail'], 'na' => $e['na'], 'score' => $e['average_score'] !== null ? (float)$e['average_score'] : null,
                    'remarks' => $e['remarks']];
            }
            unset($m['events']);
        }
        unset($m);
        $r['events'] = $events;
        $r['success'] = true;
        out($r);
    }
    out(['success' => false, 'error' => 'unknown action'], 400);
} catch (Throwable $e) {
    out(['success' => false, 'error' => $e->getMessage()], 500);
}
