<?php
//handle_pm_daily.php — รายการงาน PM ประจำวัน: วันนี้ต้องทำ PM เครื่องไหนบ้าง (ใช้กับ pm_daily.php)
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

// งานของวันที่เลือก (+ งานค้างจากวันก่อนหน้า ถ้า overdue=1)
if ($action === 'list') {
    $date = $_GET['date'] ?? '';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');
    $withOverdue = ($_GET['overdue'] ?? '') === '1';

    $where = $withOverdue
        ? "(e.event_date = ? OR (e.event_date < ? AND e.status = 0))"
        : "e.event_date = ?";

    $sql = "SELECT
                e.id, e.event_date, e.status, e.completed_at, e.work_record_id, e.checksheet_id,
                c.name AS checksheet_name,
                a.ass_code AS machine_code, a.asset_name AS machine_name,
                g.TGroupName AS machine_type,
                CONCAT_WS(' / ', NULLIF(ar.area_name, ''), NULLIF(ac.ac_name, ''), NULLIF(rm.ar_name, '')) AS location,
                r.inspector_name
            FROM pm_plan_events e
            JOIN pm_plans p ON p.id = e.plan_id AND p.status = 1
            LEFT JOIN pm_checksheets c ON e.checksheet_id = c.id
            LEFT JOIN tb_ass_list a ON e.machine_id = a.ass_id
            LEFT JOIN tb_asset_group g ON a.asset_type = g.GroupId
            LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
            LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
            LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
            LEFT JOIN pm_work_records r ON r.id = e.work_record_id
            WHERE e.ag_id = ? AND $where
            ORDER BY c.name, e.event_date, a.ass_code, e.id";

    $stmt = mysqli_prepare($connect, $sql);
    if (!$stmt) jsonOut(['success' => false, 'error' => 'Query preparation failed: ' . mysqli_error($connect)]);
    if ($withOverdue) mysqli_stmt_bind_param($stmt, "sss", $ag_id, $date, $date);
    else mysqli_stmt_bind_param($stmt, "ss", $ag_id, $date);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $rows = [];
    while ($r = mysqli_fetch_assoc($res)) {
        $rows[] = [
            'id'             => (int)$r['id'],
            'event_date'     => $r['event_date'],
            'status'         => (int)$r['status'],
            'has_record'     => (int)$r['work_record_id'] > 0,
            'completed_at'   => $r['completed_at'],
            'inspector'      => $r['inspector_name'],
            'checksheet_id'  => (int)$r['checksheet_id'],
            'checksheet'     => $r['checksheet_name'] ?? '',
            'machine_code'   => $r['machine_code'] ?? '',
            'machine_name'   => $r['machine_name'] ?? '',
            'machine_type'   => $r['machine_type'] ?? '',
            'location'       => $r['location'] ?? '',
        ];
    }
    mysqli_stmt_close($stmt);

    jsonOut(['success' => true, 'date' => $date, 'today' => date('Y-m-d'), 'data' => $rows]);
}

jsonOut(['success' => false, 'error' => 'ไม่ระบุการดำเนินการ (action)']);
