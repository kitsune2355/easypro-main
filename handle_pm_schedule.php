<?php
//handle_pm_schedule.php — ตารางแผน PM รายปี: ดู/แก้แผน PM ที่บันทึกแล้ว (เฉพาะผู้ดูแลระบบ) ใช้กับ pm_schedule.php
@session_start();
include 'config_ctrl/connect.php';
require_once 'pm_plan_functions.php';

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Bangkok');
set_time_limit(600);

function jsonOut($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (mysqli_connect_errno()) {
    http_response_code(500);
    jsonOut(['success' => false, 'error' => 'Database connection failed']);
}

// --- ตรวจสิทธิ์: เฉพาะผู้ดูแลระบบ (เหมือน handle_pm_import.php) ---
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

function fetchRows($connect, $sql, $types = '', $params = []) {
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

// ช่องสัปดาห์ในตาราง (0-47) เดือนละ 4 ช่อง: วันที่ 1-7 = สัปดาห์ 1, 8-14 = 2, 15-21 = 3, 22 ขึ้นไป = 4 (เหมือนตารางนำเข้า)
function weekIndex($ymd) {
    $month = (int)substr($ymd, 5, 2);
    $day = (int)substr($ymd, 8, 2);
    return ($month - 1) * 4 + min(4, (int)ceil($day / 7)) - 1;
}

try {
    // แผน PM ที่ใช้งานอยู่ + กำหนดการรายสัปดาห์ของปีที่เลือก
    if ($action === 'get_schedule') {
        $year = intval($_GET['year'] ?? 0) ?: (int)date('Y');
        $today = date('Y-m-d');

        $plans = fetchRows($connect, "
            SELECT p.id, p.frequency, p.alert_value, p.days_config, p.start_date, p.next_date,
                   a.ass_code, a.asset_name, g.TGroupName, c.name AS checksheet,
                   CONCAT_WS(' ', ar.area_name, ac.ac_name, rm.ar_name) AS location
            FROM pm_plans p
            LEFT JOIN tb_ass_list a ON p.machine_id = a.ass_id
            LEFT JOIN tb_asset_group g ON a.asset_type = g.GroupId
            LEFT JOIN pm_checksheets c ON p.checksheet_id = c.id
            LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
            LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
            LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
            WHERE p.ag_id = ? AND p.status = 1
            ORDER BY c.name, a.ass_code", "s", [$ag_id]);

        // กำหนดการของปีที่เลือก => {plan_id: {week: {n, done, overdue}}}
        $weeks = [];
        $events = fetchRows($connect, "
            SELECT e.plan_id, e.event_date, e.status
            FROM pm_plan_events e
            JOIN pm_plans p ON p.id = e.plan_id
            WHERE p.ag_id = ? AND p.status = 1 AND e.event_date BETWEEN ? AND ?",
            "sss", [$ag_id, "$year-01-01", "$year-12-31"]);
        foreach ($events as $e) {
            $k = weekIndex($e['event_date']);
            $w = &$weeks[$e['plan_id']][$k];
            if (!$w) $w = ['n' => 0, 'done' => 0, 'overdue' => 0];
            $w['n']++;
            if ((int)$e['status'] === 1) $w['done']++;
            elseif ($e['event_date'] < $today) $w['overdue']++;
            unset($w);
        }

        // รอบถัดไปที่ยังไม่ดำเนินการ (ตั้งแต่วันนี้)
        $nextPending = [];
        foreach (fetchRows($connect, "
            SELECT e.plan_id, MIN(e.event_date) AS d
            FROM pm_plan_events e
            JOIN pm_plans p ON p.id = e.plan_id
            WHERE p.ag_id = ? AND p.status = 1 AND e.status = 0 AND e.event_date >= ?
            GROUP BY e.plan_id", "ss", [$ag_id, $today]) as $r) {
            $nextPending[$r['plan_id']] = $r['d'];
        }

        $rows = [];
        foreach ($plans as $p) {
            $days = json_decode($p['days_config'] ?? '', true);
            $rows[] = [
                'plan_id' => (int)$p['id'],
                'checksheet' => $p['checksheet'] ?? '',
                'code' => $p['ass_code'] ?? '',
                'machine' => $p['asset_name'] ?? '',
                'group' => $p['TGroupName'] ?? '',
                'location' => trim($p['location'] ?? ''),
                'freq' => (string)$p['frequency'],
                'alert' => (string)$p['alert_value'],
                'days' => is_array($days) ? array_values($days) : [],
                'start_date' => $p['start_date'],
                'next_date' => $nextPending[$p['id']] ?? $p['next_date'],
                'weeks' => (object)($weeks[$p['id']] ?? [])
            ];
        }
        jsonOut(['success' => true, 'data' => ['year' => $year, 'today' => $today, 'rows' => $rows,
                 'contract' => ['start' => $contract_start, 'end' => $contract_end]]]);
    }

    // แก้แผนหลายรายการ: อัปเดตแผน แล้วสร้างกำหนดการที่ยังไม่ดำเนินการใหม่ตั้งแต่ "รอบถัดไป" จนสิ้นสุดสัญญา
    // (กำหนดการที่ทำแล้ว และงานค้างก่อนวันรอบถัดไป คงไว้เหมือนเดิม)
    if ($action === 'update_plans') {
        $items = $input['plans'] ?? [];
        if (!$items) throw new Exception('ไม่มีแผนที่จะบันทึก');
        if (!$contract_end) throw new Exception('ไม่พบวันสิ้นสุดสัญญาของหน่วยงาน');

        $freqOptions = getFreqOptions($connect);
        $alertValues = array_column(fetchRows($connect, "SELECT alert_value FROM pm_alert_options"), 'alert_value');
        $plans = [];
        foreach (fetchRows($connect, "SELECT id, machine_id, mac_type_id, checksheet_id FROM pm_plans WHERE ag_id = ? AND status = 1", "s", [$ag_id]) as $p) {
            $plans[(int)$p['id']] = $p;
        }

        // ตรวจสอบทุกแถวก่อน — ถ้ามีแถวผิด จะไม่บันทึกเลย
        $errors = [];
        foreach ($items as $i => $it) {
            $planId = intval($it['plan_id'] ?? 0);
            $days = array_values(array_unique(array_map('strval', $it['days'] ?? [])));
            if (!isset($plans[$planId])) $err = 'ไม่พบแผน PM นี้ในหน่วยงาน';
            else $err = validatePmPlanFields((string)($it['freq'] ?? ''), $it['alert'] ?? '', $days, (string)($it['next_date'] ?? ''),
                                             $freqOptions, $alertValues, $contract_start, $contract_end);
            if ($err) $errors[] = ['index' => $i, 'row' => $it['row'] ?? $planId, 'error' => $err];
        }
        if ($errors) jsonOut(['success' => false, 'error' => 'ข้อมูลไม่ถูกต้อง ' . count($errors) . ' แถว', 'errors' => $errors]);

        $holidays = getHolidays($connect, $ag_id);
        $today = date('Y-m-d');
        mysqli_begin_transaction($connect);
        try {
            $stmtPlan = mysqli_prepare($connect, "UPDATE pm_plans SET frequency = ?, alert_value = ?, days_config = ? WHERE id = ? AND ag_id = ?");
            $stmtDel  = mysqli_prepare($connect, "DELETE FROM pm_plan_events WHERE plan_id = ? AND status = 0 AND event_date >= ?");
            $stmtEv   = mysqli_prepare($connect, "INSERT INTO pm_plan_events (ag_id, plan_id, machine_id, mac_type_id, checksheet_id, event_date, status) VALUES (?, ?, ?, ?, ?, ?, 0)");
            $stmtNext = mysqli_prepare($connect, "UPDATE pm_plans SET next_date = COALESCE((SELECT MIN(event_date) FROM pm_plan_events WHERE plan_id = ? AND status = 0 AND event_date >= ?), ?) WHERE id = ?");
            $countPlans = 0;
            $countEvents = 0;

            foreach ($items as $it) {
                $planId = intval($it['plan_id']);
                $p = $plans[$planId];
                $freq = (string)$it['freq'];
                $alert = (string)$it['alert'];
                $days = array_values(array_unique(array_map('strval', $it['days'] ?? [])));
                if ($freqOptions[$freq]['multi'] === '5') $days = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.'];
                $daysJson = json_encode($days, JSON_UNESCAPED_UNICODE);
                $from = (string)$it['next_date'];

                mysqli_stmt_bind_param($stmtPlan, "sssis", $freq, $alert, $daysJson, $planId, $ag_id);
                if (!mysqli_stmt_execute($stmtPlan)) throw new Exception('อัปเดตแผนไม่สำเร็จ: ' . mysqli_error($connect));

                mysqli_stmt_bind_param($stmtDel, "is", $planId, $from);
                if (!mysqli_stmt_execute($stmtDel)) throw new Exception('ล้างกำหนดการเดิมไม่สำเร็จ: ' . mysqli_error($connect));

                // กำหนดการใหม่ตั้งแต่รอบถัดไปจนสิ้นสุดสัญญา (เหมือน action=update ใน handle_pm_plan.php)
                $cur = $from;
                while ($cur && $cur <= $contract_end) {
                    if (!$contract_start || $cur >= $contract_start) {
                        mysqli_stmt_bind_param($stmtEv, "sissss", $ag_id, $planId, $p['machine_id'], $p['mac_type_id'], $p['checksheet_id'], $cur);
                        if (!mysqli_stmt_execute($stmtEv)) throw new Exception('สร้างกำหนดการไม่สำเร็จ: ' . mysqli_error($connect));
                        $countEvents++;
                    }
                    $nx = calculateNextPMDate($cur, $freq, $days, $holidays);
                    if (!$nx || $nx <= $cur) break;
                    $cur = $nx;
                }

                mysqli_stmt_bind_param($stmtNext, "issi", $planId, $today, $from, $planId);
                if (!mysqli_stmt_execute($stmtNext)) throw new Exception('อัปเดตรอบถัดไปไม่สำเร็จ: ' . mysqli_error($connect));
                $countPlans++;
            }

            mysqli_commit($connect);
            jsonOut(['success' => true, 'data' => ['plans_updated' => $countPlans, 'events_created' => $countEvents]]);
        } catch (Exception $e) {
            mysqli_rollback($connect);
            throw $e;
        }
    }

    jsonOut(['success' => false, 'error' => 'ไม่ระบุการดำเนินการ (action)']);
} catch (Exception $e) {
    jsonOut(['success' => false, 'error' => $e->getMessage()]);
}
