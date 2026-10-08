<?php
@session_start();
include "config_ctrl/checksession.php";
include "config_ctrl/connect.php";

// pm_monthly_report_v2.php
// รายงาน PM รายเดือนแบบตาราง 1-31 วัน
// จุดสำคัญ: ใช้วันที่ตามแผนจาก pm_plan_events.event_date เป็นตำแหน่งคอลัมน์วัน
// ไม่ใช้ actual_date เป็นคอลัมน์วัน เพราะ actual_date คือวันที่กดบันทึกจริง อาจซ้ำกันหลายแผน

date_default_timezone_set('Asia/Bangkok');

$plan_event_id = 0;
if (isset($_GET['plan_event_id'])) {
    $plan_event_id = intval($_GET['plan_event_id']);
} elseif (isset($_GET['event_id'])) {
    $plan_event_id = intval($_GET['event_id']);
} elseif (isset($_GET['plan_id'])) {
    // ในระบบเดิม pm_worksheet.php ส่ง plan_id แต่ค่าจริงคือ pm_plan_events.id
    $plan_event_id = intval($_GET['plan_id']);
}

$work_record_id = isset($_GET['work_record_id']) ? intval($_GET['work_record_id']) : 0;
$month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$auto_print = isset($_GET['auto']) && $_GET['auto'] == '1';

$session_ag_id = isset($sess_user_agency_es) ? trim((string)$sess_user_agency_es) : '';

function h($value) {
    return htmlspecialchars((string)($value === null ? '' : $value), ENT_QUOTES, 'UTF-8');
}

function dbv($connect, $value) {
    return mysqli_real_escape_string($connect, (string)$value);
}

function thaiMonthName($month) {
    $months = array(
        1 => 'มกราคม',
        2 => 'กุมภาพันธ์',
        3 => 'มีนาคม',
        4 => 'เมษายน',
        5 => 'พฤษภาคม',
        6 => 'มิถุนายน',
        7 => 'กรกฎาคม',
        8 => 'สิงหาคม',
        9 => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม'
    );
    return isset($months[(int)$month]) ? $months[(int)$month] : '-';
}

function shortThaiDate($date) {
    if (!$date || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }

    $ts = strtotime($date);
    if (!$ts) return '-';

    return date('j/m/', $ts) . (date('Y', $ts) + 543);
}

function isExpectedValue($value) {
    $value = trim((string)$value);
    return $value !== '' && $value !== '-';
}

function isActualValue($value) {
    $value = trim((string)$value);
    return $value !== '' && $value !== '-';
}

function markByStatus($status) {
    $status = trim((string)$status);

    if ($status === 'Pass') return '/';
    if ($status === 'Fail') return 'X';
    if ($status === 'N/A') return '-';

    return $status;
}

function cellValueFromItem($item) {
    $actual = isset($item['actual_value']) ? trim((string)$item['actual_value']) : '';
    $unit = isset($item['unit']) ? trim((string)$item['unit']) : '';
    $expected = isset($item['expected_value']) ? trim((string)$item['expected_value']) : '';
    $value_name = isset($item['value_name']) ? trim((string)$item['value_name']) : '';
    $status = isset($item['result_status']) ? trim((string)$item['result_status']) : '';

    // ถ้ามีค่าที่วัดได้จริง และรายการนั้นเป็นรายการที่เกี่ยวกับการวัดค่า ให้แสดงค่าที่วัดได้จริง
    if (isActualValue($actual) && (isExpectedValue($expected) || $value_name !== '')) {
        return $actual . ($unit !== '' ? ' ' . $unit : '');
    }

    return markByStatus($status);
}

function cellClassFromItem($item) {
    $status = isset($item['result_status']) ? trim((string)$item['result_status']) : '';
    if ($status === 'Fail') return 'cell-fail';
    if ($status === 'N/A') return 'cell-na';
    return '';
}

function getStandardText($row) {
    $expected = isset($row['expected_value']) ? trim((string)$row['expected_value']) : '';
    $unit = isset($row['unit']) ? trim((string)$row['unit']) : '';
    $standard = isset($row['standard_text']) ? trim((string)$row['standard_text']) : '';

    if (isExpectedValue($expected)) {
        return $expected . ($unit !== '' ? ' ' . $unit : '');
    }

    return $standard !== '' ? $standard : '-';
}

if ($plan_event_id <= 0 && $work_record_id <= 0) {
    die('ไม่พบรหัสแผนงานหรือรหัสประวัติ PM');
}

/* =========================================================
   1) ดึงข้อมูลตั้งต้นจาก plan_event หรือ work_record
========================================================= */
$base = null;

if ($work_record_id > 0) {
    $sql_base = "
        SELECT
            e.id AS event_id,
            e.ag_id AS event_ag_id,
            e.plan_id AS event_plan_id,
            e.machine_id AS event_machine_id,
            e.mac_type_id AS event_mac_type_id,
            e.checksheet_id AS event_checksheet_id,
            e.event_date,
            e.status AS event_status,
            e.work_record_id AS event_work_record_id,

            r.*,

            c.name AS master_checksheet_name,
            c.doc_no AS master_doc_no,
            c.rev_no AS master_rev_no,

            a.asset_name AS master_machine_name,
            a.ass_code AS master_machine_code,
            a.asset_sn AS master_machine_sn,
            g.TGroupName AS master_machine_type,
            CONCAT_WS(' / ', ar.area_name, ac.ac_name, rm.ar_name) AS master_location
        FROM pm_work_records r
        LEFT JOIN pm_plan_events e ON e.id = r.plan_event_id
        LEFT JOIN pm_checksheets c ON c.id = r.checksheet_id
        LEFT JOIN tb_ass_list a ON a.ass_id = r.machine_id
        LEFT JOIN tb_asset_group g ON g.GroupId = r.mac_type_id
        LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
        LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
        LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
        WHERE r.id = " . $work_record_id . "
        LIMIT 1
    ";
} else {
    $sql_base = "
        SELECT
            e.id AS event_id,
            e.ag_id AS event_ag_id,
            e.plan_id AS event_plan_id,
            e.machine_id AS event_machine_id,
            e.mac_type_id AS event_mac_type_id,
            e.checksheet_id AS event_checksheet_id,
            e.event_date,
            e.status AS event_status,
            e.work_record_id AS event_work_record_id,

            r.*,

            c.name AS master_checksheet_name,
            c.doc_no AS master_doc_no,
            c.rev_no AS master_rev_no,

            a.asset_name AS master_machine_name,
            a.ass_code AS master_machine_code,
            a.asset_sn AS master_machine_sn,
            g.TGroupName AS master_machine_type,
            CONCAT_WS(' / ', ar.area_name, ac.ac_name, rm.ar_name) AS master_location
        FROM pm_plan_events e
        LEFT JOIN pm_work_records r ON (r.id = e.work_record_id OR r.plan_event_id = e.id)
        LEFT JOIN pm_checksheets c ON c.id = e.checksheet_id
        LEFT JOIN tb_ass_list a ON a.ass_id = e.machine_id
        LEFT JOIN tb_asset_group g ON g.GroupId = e.mac_type_id
        LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
        LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
        LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
        WHERE e.id = " . $plan_event_id . "
        LIMIT 1
    ";
}

$res_base = mysqli_query($connect, $sql_base);
if (!$res_base) {
    die('SQL Error Base: ' . mysqli_error($connect));
}

$base = mysqli_fetch_assoc($res_base);
if (!$base) {
    die('ไม่พบข้อมูลตั้งต้นสำหรับออกรายงาน');
}

$base_ag_id = trim((string)(!empty($base['event_ag_id']) ? $base['event_ag_id'] : $base['ag_id']));
$base_plan_id = intval(!empty($base['event_plan_id']) ? $base['event_plan_id'] : $base['plan_id']);
$base_machine_id = trim((string)(!empty($base['event_machine_id']) ? $base['event_machine_id'] : $base['machine_id']));
$base_mac_type_id = trim((string)(!empty($base['event_mac_type_id']) ? $base['event_mac_type_id'] : $base['mac_type_id']));
$base_checksheet_id = trim((string)(!empty($base['event_checksheet_id']) ? $base['event_checksheet_id'] : $base['checksheet_id']));

// กันข้ามหน่วยงาน ถ้ามี session ag_id
if ($session_ag_id !== '' && $base_ag_id !== '' && $session_ag_id !== $base_ag_id) {
    die('ไม่สามารถออกรายงานข้ามหน่วยงานได้');
}

$base_date = '';
if (!empty($base['event_date'])) {
    $base_date = $base['event_date'];
} elseif (!empty($base['plan_date'])) {
    $base_date = $base['plan_date'];
} elseif (!empty($base['actual_date'])) {
    $base_date = $base['actual_date'];
} else {
    $base_date = date('Y-m-d');
}

if ($month <= 0) {
    $month = intval(date('m', strtotime($base_date)));
}

if ($year <= 0) {
    $year = intval(date('Y', strtotime($base_date)));
}

// รองรับกรณีส่งปี พ.ศ. มา เช่น 2569
if ($year > 2400) {
    $year = $year - 543;
}

$thai_year = $year + 543;
$start_date = sprintf('%04d-%02d-01', $year, $month);
$end_date = date('Y-m-t', strtotime($start_date));
$days_in_month = intval(date('t', strtotime($start_date)));
$today = date('Y-m-d');

/* =========================================================
   2) ดึงแผนทั้งเดือนเป็นแกนหลัก
   หมายเหตุ: ใช้ pm_plan_events.event_date เป็นคอลัมน์วัน
========================================================= */
$events = array();
$event_by_day = array();
$work_record_ids = array();

$sql_events = "
    SELECT
        e.id AS event_id,
        e.ag_id,
        e.plan_id,
        e.machine_id,
        e.mac_type_id,
        e.checksheet_id,
        e.event_date,
        e.status AS event_status,
        e.work_record_id AS event_work_record_id,
        e.status_feedback,
        e.completed_at,

        r.id AS record_id,
        r.doc_no,
        r.rev_no,
        r.checksheet_name,
        r.machine_code,
        r.machine_name,
        r.machine_sn,
        r.machine_type,
        r.location,
        r.plan_date,
        r.actual_date,
        r.inspector_name,
        r.inspector_signature_path,
        r.remarks,
        r.created_at AS record_created_at
    FROM pm_plan_events e
    LEFT JOIN pm_work_records r ON (r.id = e.work_record_id OR r.plan_event_id = e.id)
    WHERE e.ag_id = '" . dbv($connect, $base_ag_id) . "'
      AND e.plan_id = " . intval($base_plan_id) . "
      AND IFNULL(e.machine_id, '') = '" . dbv($connect, $base_machine_id) . "'
      AND IFNULL(e.checksheet_id, '') = '" . dbv($connect, $base_checksheet_id) . "'
      AND e.event_date BETWEEN '" . dbv($connect, $start_date) . "' AND '" . dbv($connect, $end_date) . "'
    GROUP BY e.id
    ORDER BY e.event_date ASC, e.id ASC
";

$res_events = mysqli_query($connect, $sql_events);
if (!$res_events) {
    die('SQL Error Events: ' . mysqli_error($connect));
}

while ($ev = mysqli_fetch_assoc($res_events)) {
    $events[] = $ev;
    $day = intval(date('j', strtotime($ev['event_date'])));

    // หากวันเดียวกันมีหลาย event ให้เลือกตัวที่บันทึกแล้วก่อน
    if (!isset($event_by_day[$day])) {
        $event_by_day[$day] = $ev;
    } else {
        $old = $event_by_day[$day];
        if (empty($old['record_id']) && !empty($ev['record_id'])) {
            $event_by_day[$day] = $ev;
        }
    }

    if (!empty($ev['record_id'])) {
        $work_record_ids[] = intval($ev['record_id']);
    }
}

$work_record_ids = array_values(array_unique($work_record_ids));

/* =========================================================
   3) สร้างหัวรายงานจาก record ล่าสุด ถ้ามี ไม่งั้นใช้ master จาก base
========================================================= */
$latest_record = null;
for ($i = count($events) - 1; $i >= 0; $i--) {
    if (!empty($events[$i]['record_id'])) {
        $latest_record = $events[$i];
        break;
    }
}

$header = $latest_record ? $latest_record : $base;

$doc_no = !empty($header['doc_no']) ? $header['doc_no'] : (!empty($base['master_doc_no']) ? $base['master_doc_no'] : '-');
$rev_no = !empty($header['rev_no']) ? $header['rev_no'] : (!empty($base['master_rev_no']) ? $base['master_rev_no'] : '-');
$checksheet_name = !empty($header['checksheet_name']) ? $header['checksheet_name'] : (!empty($base['master_checksheet_name']) ? $base['master_checksheet_name'] : '-');
$machine_code = !empty($header['machine_code']) ? $header['machine_code'] : (!empty($base['master_machine_code']) ? $base['master_machine_code'] : '-');
$machine_name = !empty($header['machine_name']) ? $header['machine_name'] : (!empty($base['master_machine_name']) ? $base['master_machine_name'] : '-');
$machine_sn = !empty($header['machine_sn']) ? $header['machine_sn'] : (!empty($base['master_machine_sn']) ? $base['master_machine_sn'] : '-');
$machine_type = !empty($header['machine_type']) ? $header['machine_type'] : (!empty($base['master_machine_type']) ? $base['master_machine_type'] : '-');
$location = !empty($header['location']) ? $header['location'] : (!empty($base['master_location']) ? $base['master_location'] : '-');

/* =========================================================
   4) ดึงรายการตรวจสอบจาก master ก่อน ถ้าไม่มีค่อยใช้ snapshot
========================================================= */
$row_defs = array();

if ($base_checksheet_id !== '') {
    $sql_master_items = "
        SELECT
            id AS checksheet_item_id,
            sort_order,
            check_point,
            standard_text,
            value_name,
            unit,
            expected_value
        FROM pm_checksheet_items
        WHERE checksheet_id = '" . dbv($connect, $base_checksheet_id) . "'
        ORDER BY sort_order ASC, id ASC
    ";

    $res_master_items = mysqli_query($connect, $sql_master_items);
    if ($res_master_items) {
        while ($m = mysqli_fetch_assoc($res_master_items)) {
            $sort = intval($m['sort_order']);
            if ($sort <= 0) $sort = count($row_defs) + 1;

            $row_defs[$sort] = array(
                'sort_order' => $sort,
                'checksheet_item_id' => $m['checksheet_item_id'],
                'check_point' => $m['check_point'],
                'standard_text' => $m['standard_text'],
                'value_name' => $m['value_name'],
                'unit' => $m['unit'],
                'expected_value' => $m['expected_value']
            );
        }
    }
}

/* =========================================================
   5) ดึงผลตรวจจาก work_record_items ทั้งเดือน
========================================================= */
$item_by_record_sort = array();
$all_snapshot_items = array();

if (!empty($work_record_ids)) {
    $ids_sql = implode(',', array_map('intval', $work_record_ids));

    $sql_items = "
        SELECT *
        FROM pm_work_record_items
        WHERE work_record_id IN ($ids_sql)
        ORDER BY sort_order ASC, id ASC
    ";

    $res_items = mysqli_query($connect, $sql_items);
    if (!$res_items) {
        die('SQL Error Items: ' . mysqli_error($connect));
    }

    while ($it = mysqli_fetch_assoc($res_items)) {
        $rid = intval($it['work_record_id']);
        $sort = intval($it['sort_order']);
        if ($sort <= 0) $sort = 9999;

        if (!isset($item_by_record_sort[$rid])) {
            $item_by_record_sort[$rid] = array();
        }

        $item_by_record_sort[$rid][$sort] = $it;
        $all_snapshot_items[] = $it;

        // ถ้า master ไม่มี sort นี้ ให้เติมจาก snapshot
        if (!isset($row_defs[$sort])) {
            $row_defs[$sort] = array(
                'sort_order' => $sort,
                'checksheet_item_id' => $it['checksheet_item_id'],
                'check_point' => $it['check_point'],
                'standard_text' => $it['standard_text'],
                'value_name' => $it['value_name'],
                'unit' => $it['unit'],
                'expected_value' => $it['expected_value']
            );
        }
    }
}

ksort($row_defs);

$completed_count = 0;
$pending_count = 0;
foreach ($events as $ev) {
    if (!empty($ev['record_id']) || intval($ev['event_status']) === 1) {
        $completed_count++;
    } else {
        $pending_count++;
    }
}

$signature_path = '';
$inspector_name = '-';
$report_remarks = '';

if ($latest_record) {
    $signature_path = !empty($latest_record['inspector_signature_path']) ? $latest_record['inspector_signature_path'] : '';
    $inspector_name = !empty($latest_record['inspector_name']) ? $latest_record['inspector_name'] : '-';
    $report_remarks = !empty($latest_record['remarks']) ? $latest_record['remarks'] : '';
}

$total_rows_target = 14;
$empty_rows = max(0, $total_rows_target - count($row_defs));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>PM Monthly Report - <?php echo h($machine_name); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #e5e7eb;
            font-family: Tahoma, Arial, sans-serif;
            color: #000;
        }

        .screen-toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #0f172a;
            color: #fff;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .screen-toolbar-title {
            font-size: 14px;
            font-weight: bold;
            line-height: 1.5;
        }

        .screen-toolbar-actions {
            display: flex;
            gap: 8px;
            flex: 0 0 auto;
        }

        .screen-toolbar button {
            border: 0;
            border-radius: 6px;
            padding: 8px 14px;
            cursor: pointer;
            font-size: 13px;
            color: #fff;
        }

        .btn-print { background: #0284c7; }
        .btn-close { background: #334155; }

        .page-wrap { padding: 14px; }

        .report-page {
            width: 297mm;
            min-height: 210mm;
            background: #fff;
            margin: 0 auto;
            padding: 7mm;
            border: 1px solid #111;
        }

        .title-main {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            line-height: 1.5;
        }

        .title-sub {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            line-height: 1.5;
            margin-bottom: 6px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr 1fr;
            gap: 4px;
            margin-bottom: 5px;
            font-size: 8px;
        }

        .info-box {
            border: 1px solid #111;
            min-height: 18px;
            padding: 3px 5px;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .summary-line {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            margin: 4px 0 5px;
            font-size: 9px;
        }

        .month-title {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
        }

        .summary-badge {
            display: inline-block;
            border: 1px solid #111;
            padding: 2px 6px;
            font-size: 8px;
            background: #f8fafc;
            margin-left: 3px;
        }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td {
            border: 1px solid #111;
            padding: 1px 2px;
            font-size: 7px;
            line-height: 1.18;
            vertical-align: middle;
        }

        th {
            background: #e2edf7;
            text-align: center;
            font-weight: bold;
        }

        .col-no { width: 8mm; text-align: center; }
        .col-task { width: 47mm; }
        .col-standard { width: 24mm; text-align: center; }
        .col-day { width: 5.4mm; text-align: center; }
        .col-remark { width: 30mm; }

        .section-row td {
            background: #b7b7b7;
            font-weight: bold;
            height: 15px;
        }

        .day-head {
            font-size: 7px;
            padding: 1px;
        }

        .cell-day {
            height: 15px;
            text-align: center;
            font-size: 7px;
            overflow: hidden;
            word-break: break-word;
            white-space: normal;
        }

        .day-weekend { background: #9ca3af !important; }
        .day-out { background: #5b5b5b !important; color: #fff; }
        .cell-pending { background: #fff7ed; }
        .cell-fail { color: #b00000; font-weight: bold; }
        .cell-na { color: #555; }

        .legend {
            border-left: 1px solid #111;
            border-right: 1px solid #111;
            border-bottom: 1px solid #111;
            background: #e5e7eb;
            text-align: center;
            font-size: 7px;
            padding: 3px 4px;
        }

        .remark-box {
            border-left: 1px solid #111;
            border-right: 1px solid #111;
            border-bottom: 1px solid #111;
            min-height: 28px;
            padding: 4px;
            font-size: 8px;
        }

        .sign-table { margin-top: 6px; }
        .sign-table td {
            height: 42px;
            text-align: center;
            vertical-align: bottom;
            font-size: 8px;
        }

        .sign-line {
            border-bottom: 1px dotted #000;
            height: 16px;
            margin: 0 20px 2px;
        }

        .signature-img {
            display: block;
            max-width: 120px;
            max-height: 24px;
            object-fit: contain;
            margin: 0 auto 1px;
        }

        .footer {
            margin-top: 3px;
            text-align: right;
            font-size: 7px;
        }

        @media print {
            body { background: #fff; }
            .screen-toolbar { display: none !important; }
            .page-wrap { padding: 0; }
            .report-page {
                width: 100%;
                min-height: auto;
                border: 0;
                padding: 0;
                margin: 0;
            }
            @page { size: A4 landscape; margin: 6mm; }
        }
    </style>
</head>
<body>

<div class="screen-toolbar">
    <div class="screen-toolbar-title">
        รายงาน PM รายเดือน : <?php echo h($machine_name); ?> / <?php echo h(thaiMonthName($month)); ?> <?php echo h($thai_year); ?>
    </div>
    <div class="screen-toolbar-actions">
        <button type="button" class="btn-print" onClick="window.print()">พิมพ์ / Save PDF</button>
        <button type="button" class="btn-close" onClick="window.close()">ปิดหน้าต่าง</button>
    </div>
</div>

<div class="page-wrap">
    <div class="report-page">
        <div class="title-main">
            แบบฟอร์มตรวจเช็ค <?php echo h($checksheet_name); ?> ประจำปี <?php echo h($thai_year); ?>
        </div>
        <div class="title-sub">
            <?php echo h($location); ?>
        </div>

        <div class="info-grid">
            <div class="info-box"><strong>เครื่องจักร/อุปกรณ์:</strong> <?php echo h($machine_name); ?></div>
            <div class="info-box"><strong>รหัสเครื่อง:</strong> <?php echo h($machine_code); ?></div>
            <div class="info-box"><strong>ประเภท:</strong> <?php echo h($machine_type); ?></div>
            <div class="info-box"><strong>เลขที่เอกสาร:</strong> <?php echo h($doc_no); ?></div>
        </div>

        <div class="info-grid">
            <div class="info-box"><strong>สถานที่:</strong> <?php echo h($location); ?></div>
            <div class="info-box"><strong>Serial No.:</strong> <?php echo h($machine_sn); ?></div>
            <div class="info-box"><strong>Revision:</strong> <?php echo h($rev_no); ?></div>
            <div class="info-box"><strong>แผนทั้งหมด:</strong> <?php echo count($events); ?> / ทำแล้ว <?php echo $completed_count; ?> / รอ <?php echo $pending_count; ?></div>
        </div>

        <div class="summary-line">
            <div class="month-title">
                ประจำเดือน <?php echo h(thaiMonthName($month)); ?> <?php echo h($thai_year); ?>
            </div>
            <div>
                <span class="summary-badge">/ = ปกติ</span>
                <span class="summary-badge">X = ผิดปกติ</span>
                <span class="summary-badge">- = N/A</span>
                <span class="summary-badge" style="background:#fff7ed;">สีครีม = มีแผนยังไม่บันทึก</span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2" class="col-no">ลำดับ</th>
                    <th rowspan="2" class="col-task">รายการตรวจสอบ</th>
                    <th rowspan="2" class="col-standard">มาตรฐาน</th>
                    <th colspan="31">วันที่ประจำเดือน</th>
                    <th rowspan="2" class="col-remark">หมายเหตุ</th>
                </tr>
                <tr>
                    <?php for ($d = 1; $d <= 31; $d++): ?>
                        <?php
                            $head_class = '';
                            if ($d > $days_in_month) {
                                $head_class = 'day-out';
                            } else {
                                $date_check = sprintf('%04d-%02d-%02d', $year, $month, $d);
                                $week_day = intval(date('w', strtotime($date_check)));
                                if ($week_day === 0 || $week_day === 6) {
                                    $head_class = 'day-weekend';
                                }
                            }
                        ?>
                        <th class="col-day day-head <?php echo h($head_class); ?>"><?php echo $d; ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <tr class="section-row">
                    <td></td>
                    <td colspan="34">รายการตรวจสอบ / PM CHECKLIST</td>
                </tr>

                <?php $row_no = 1; ?>
                <?php foreach ($row_defs as $sort => $row): ?>
                    <tr>
                        <td class="col-no"><?php echo $row_no++; ?></td>
                        <td class="col-task"><?php echo h($row['check_point']); ?></td>
                        <td class="col-standard"><?php echo h(getStandardText($row)); ?></td>

                        <?php
                            $row_remark = array();
                        ?>

                        <?php for ($d = 1; $d <= 31; $d++): ?>
                            <?php
                                $cell_text = '';
                                $cell_class = '';

                                if ($d > $days_in_month) {
                                    $cell_class .= ' day-out';
                                } else {
                                    $date_check = sprintf('%04d-%02d-%02d', $year, $month, $d);
                                    $week_day = intval(date('w', strtotime($date_check)));
                                    if ($week_day === 0 || $week_day === 6) {
                                        $cell_class .= ' day-weekend';
                                    }
                                }

                                if (isset($event_by_day[$d])) {
                                    $ev = $event_by_day[$d];
                                    $rid = !empty($ev['record_id']) ? intval($ev['record_id']) : 0;

                                    if ($rid > 0 && isset($item_by_record_sort[$rid]) && isset($item_by_record_sort[$rid][$sort])) {
                                        $it = $item_by_record_sort[$rid][$sort];
                                        $cell_text = cellValueFromItem($it);
                                        $cell_class = cellClassFromItem($it); // ถ้ามีข้อมูลจริง ไม่ใช้สี weekend ทับ เพื่อให้อ่านชัด

                                        if (trim((string)$it['result_status']) === 'Fail') {
                                            $row_remark[] = 'วันที่ ' . $d . ' ผิดปกติ';
                                        }
                                    } else {
                                        // มีแผน แต่ยังไม่มีผลบันทึก
                                        $cell_class = 'cell-pending';
                                        $cell_text = '';
                                    }
                                }
                            ?>
                            <td class="cell-day <?php echo h($cell_class); ?>"><?php echo h($cell_text); ?></td>
                        <?php endfor; ?>

                        <td class="col-remark"><?php echo h(implode(', ', array_unique($row_remark))); ?></td>
                    </tr>
                <?php endforeach; ?>

                <?php for ($e = 0; $e < $empty_rows; $e++): ?>
                    <tr>
                        <td class="col-no">&nbsp;</td>
                        <td class="col-task">&nbsp;</td>
                        <td class="col-standard">&nbsp;</td>
                        <?php for ($d = 1; $d <= 31; $d++): ?>
                            <?php
                                $cell_class = '';
                                if ($d > $days_in_month) {
                                    $cell_class = 'day-out';
                                } else {
                                    $date_check = sprintf('%04d-%02d-%02d', $year, $month, $d);
                                    $week_day = intval(date('w', strtotime($date_check)));
                                    if ($week_day === 0 || $week_day === 6) {
                                        $cell_class = 'day-weekend';
                                    }
                                }
                            ?>
                            <td class="cell-day <?php echo h($cell_class); ?>">&nbsp;</td>
                        <?php endfor; ?>
                        <td class="col-remark">&nbsp;</td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="legend">
            หมายเหตุ: รายงานนี้ใช้วันที่จากแผนงาน <strong>pm_plan_events.event_date</strong> ในการลงช่องวันที่ 1-31 และใช้ผลตรวจจาก <strong>pm_work_record_items</strong> เมื่อมีการบันทึกแล้ว
        </div>

        <div class="remark-box">
            <strong>หมายเหตุเพิ่มเติม:</strong>
            <?php echo nl2br(h($report_remarks)); ?>
        </div>

        <table class="sign-table">
            <tr>
                <td style="width:33.33%;">
                    <?php if (!empty($signature_path)): ?>
                        <img src="<?php echo h($signature_path); ?>" class="signature-img">
                    <?php else: ?>
                        <div class="sign-line"></div>
                    <?php endif; ?>
                    <div>ผู้ตรวจสอบ / ผู้ดำเนินการ</div>
                    <div><?php echo h($inspector_name); ?></div>
                </td>
                <td style="width:33.33%;">
                    <div class="sign-line"></div>
                    <div>หัวหน้างาน / ผู้ตรวจสอบ</div>
                    <div>วันที่ ........../........../..........</div>
                </td>
                <td style="width:33.33%;">
                    <div class="sign-line"></div>
                    <div>ผู้จัดการอาคาร / ผู้อนุมัติ</div>
                    <div>วันที่ ........../........../..........</div>
                </td>
            </tr>
        </table>

        <div class="footer">
            FM-PM-MONTHLY Rev.00 | Generated: <?php echo date('d/m/') . (date('Y') + 543); ?>
        </div>
    </div>
</div>

<script>
    const AUTO_PRINT = <?php echo $auto_print ? 'true' : 'false'; ?>;
    if (AUTO_PRINT) {
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 500);
        });
    }
</script>
</body>
</html>
