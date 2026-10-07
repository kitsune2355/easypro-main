<?php
@session_start();
include "config_ctrl/checksession.php";
include "config_ctrl/connect.php";

date_default_timezone_set('Asia/Bangkok');

$plan_id = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0; // pm_plan_events.id
$work_record_id = isset($_GET['work_record_id']) ? intval($_GET['work_record_id']) : 0;

$month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$year = isset($_GET['year']) ? intval($_GET['year']) : 0;

// ถ้ามีการส่งช่วงวันที่มา จะใช้ช่วงวันที่นี้แทนเดือน/ปี
$date_from = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : '';
$date_to   = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : '';

// จำนวนวันที่ต่อ 1 หน้า และจำนวนหัวข้อต่อ 1 หน้า
$cols_per_page = isset($_GET['cols']) ? intval($_GET['cols']) : 14;
$rows_per_page = isset($_GET['rows']) ? intval($_GET['rows']) : 18;

$show_pending = isset($_GET['show_pending']) && $_GET['show_pending'] == '1';
$show_values_in_cell = isset($_GET['values']) && $_GET['values'] == '1';

if ($cols_per_page < 6) $cols_per_page = 6;
if ($cols_per_page > 18) $cols_per_page = 18;

if ($rows_per_page < 8) $rows_per_page = 8;
if ($rows_per_page > 28) $rows_per_page = 28;

$session_ag_id = isset($sess_user_agency_es) ? trim((string)$sess_user_agency_es) : '';

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function thaiMonthName($month) {
    $months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    return $months[(int)$month] ?? '-';
}

function thaiDayShort($date) {
    $days = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    $ts = strtotime($date);
    if (!$ts) return '';
    return $days[(int)date('w', $ts)] ?? '';
}

function validDateYmd($date) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
    $ts = strtotime($date);
    return $ts !== false;
}

function hasExpectedValue($value) {
    $value = trim((string)$value);
    return $value !== '' && $value !== '-';
}

function recordMark($status) {
    $status = trim((string)$status);
    if ($status === 'Pass') return '/';
    if ($status === 'Fail') return 'X';
    if ($status === 'N/A') return '-';
    return $status !== '' ? $status : '';
}

function actualValueText($item) {
    $actual = trim((string)($item['actual_value'] ?? ''));
    $unit = trim((string)($item['unit'] ?? ''));

    if ($actual === '' || $actual === '-') {
        return '';
    }

    return $actual . ($unit !== '' ? ' ' . $unit : '');
}

function buildCellFromItems($items, $show_values_in_cell) {
    if (empty($items)) {
        return ['text' => '', 'class' => ''];
    }

    $hasFail = false;
    $hasPass = false;
    $hasNA = false;
    $values = [];

    foreach ($items as $item) {
        $status = trim((string)($item['result_status'] ?? ''));
        if ($status === 'Fail') $hasFail = true;
        if ($status === 'Pass') $hasPass = true;
        if ($status === 'N/A') $hasNA = true;

        $valueText = actualValueText($item);
        if ($valueText !== '') {
            $values[] = $valueText;
        }
    }

    if ($hasFail) {
        $mark = 'X';
        $class = 'cell-fail';
    } elseif ($hasPass) {
        $mark = '/';
        $class = 'cell-pass';
    } elseif ($hasNA) {
        $mark = '-';
        $class = 'cell-na';
    } else {
        $mark = '';
        $class = '';
    }

    $values = array_values(array_unique($values));

    if ($show_values_in_cell && !empty($values)) {
        return ['text' => implode(', ', $values), 'class' => trim($class . ' cell-value')];
    }

    return ['text' => $mark, 'class' => $class];
}

function buildRemarkForDays($row, $days) {
    $notes = [];

    foreach ($days as $dayInfo) {
        $dayKey = $dayInfo['key'];
        $label = $dayInfo['short_label'];
        $items = $row['days'][$dayKey]['items'] ?? [];
        if (empty($items)) continue;

        $dayValues = [];
        $hasFail = false;

        foreach ($items as $item) {
            if (($item['result_status'] ?? '') === 'Fail') {
                $hasFail = true;
            }

            $valueText = actualValueText($item);
            if ($valueText !== '') {
                $dayValues[] = $valueText;
            }
        }

        $dayValues = array_values(array_unique($dayValues));
        if (!empty($dayValues)) {
            $notes[] = $label . ': ' . implode(', ', $dayValues);
        }

        if ($hasFail) {
            $notes[] = $label . ': ผิดปกติ';
        }
    }

    return implode(' | ', array_values(array_unique($notes)));
}


function normalizeReportKey($value) {
    $value = trim((string)$value);
    $value = preg_replace('/\s+/u', ' ', $value);

    if ($value === '') {
        return 'empty_' . md5($value);
    }

    if (function_exists('mb_strtolower')) {
        return md5(mb_strtolower($value, 'UTF-8'));
    }

    return md5(strtolower($value));
}

function mergeStandardText($old, $new) {
    $old = trim((string)$old);
    $new = trim((string)$new);

    if ($old === '') return $new;
    if ($new === '') return $old;
    if ($old === $new) return $old;

    $parts = array_filter(array_map('trim', explode(' / ', $old)));
    if (!in_array($new, $parts, true)) {
        $parts[] = $new;
    }

    return implode(' / ', $parts);
}

function mergeDayItems($existingItems, $newItem) {
    if (!is_array($existingItems)) {
        $existingItems = [];
    }
    $existingItems[] = $newItem;
    return $existingItems;
}

if ($plan_id <= 0 && $work_record_id <= 0) {
    die('ไม่พบรหัสแผนงานหรือรหัสประวัติ PM');
}

/* =========================================================
   1) หา Base Event / Base Work Record
========================================================= */
$base_event = null;
$base_record = null;

if ($plan_id > 0) {
    $sql = "SELECT * FROM pm_plan_events WHERE id = ? LIMIT 1";
    $stmt = $connect->prepare($sql);
    if (!$stmt) die('Prepare base event failed: ' . $connect->error);
    $stmt->bind_param('i', $plan_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $base_event = $res->fetch_assoc();
    $stmt->close();

    if (!$base_event) {
        die('ไม่พบข้อมูลแผนงาน PM');
    }

    if (!empty($base_event['work_record_id'])) {
        $wrid = intval($base_event['work_record_id']);
        $sql = "SELECT * FROM pm_work_records WHERE id = ? LIMIT 1";
        $stmt = $connect->prepare($sql);
        if (!$stmt) die('Prepare base record failed: ' . $connect->error);
        $stmt->bind_param('i', $wrid);
        $stmt->execute();
        $res = $stmt->get_result();
        $base_record = $res->fetch_assoc();
        $stmt->close();
    }
}

if ($work_record_id > 0) {
    $sql = "SELECT * FROM pm_work_records WHERE id = ? LIMIT 1";
    $stmt = $connect->prepare($sql);
    if (!$stmt) die('Prepare base work record failed: ' . $connect->error);
    $stmt->bind_param('i', $work_record_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $base_record = $res->fetch_assoc();
    $stmt->close();

    if (!$base_record) {
        die('ไม่พบข้อมูลประวัติ PM');
    }

    $eventId = intval($base_record['plan_event_id']);
    if ($eventId > 0) {
        $sql = "SELECT * FROM pm_plan_events WHERE id = ? LIMIT 1";
        $stmt = $connect->prepare($sql);
        if (!$stmt) die('Prepare event from record failed: ' . $connect->error);
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $res = $stmt->get_result();
        $base_event = $res->fetch_assoc();
        $stmt->close();
    }
}

if (!$base_event && !$base_record) {
    die('ไม่พบข้อมูลสำหรับสร้างรายงาน');
}

$ag_id = $base_event['ag_id'] ?? ($base_record['ag_id'] ?? $session_ag_id);
$machine_id = $base_event['machine_id'] ?? ($base_record['machine_id'] ?? '');
$checksheet_id = intval($base_event['checksheet_id'] ?? ($base_record['checksheet_id'] ?? 0));
$base_date = $base_event['event_date'] ?? ($base_record['plan_date'] ?? ($base_record['actual_date'] ?? date('Y-m-d')));

/* =========================================================
   เงื่อนไขช่วงวันที่
   - ถ้าส่ง date_from/date_to    => แสดงเฉพาะช่วงวันที่นั้น
   - ถ้าส่ง month/year          => แสดงเฉพาะเดือน/ปีนั้น
   - ถ้าไม่ส่งวันที่ใด ๆ เลย   => แสดงทุกวันที่ต้องทำ PM ทั้งหมด
========================================================= */
$has_range_filter = validDateYmd($date_from) && validDateYmd($date_to);
$has_month_year_filter = ($month > 0 && $year > 0);
$has_date_filter = $has_range_filter || $has_month_year_filter;

$start_date = null;
$end_date = null;
$period_label = 'ทุกวันที่ต้องดำเนินการ PM';

if ($has_range_filter) {
    $start_date = $date_from;
    $end_date = $date_to;

    if (strtotime($start_date) > strtotime($end_date)) {
        $tmp = $start_date;
        $start_date = $end_date;
        $end_date = $tmp;
    }

    $period_label = 'ช่วงวันที่ ' . date('d/m/', strtotime($start_date)) . (date('Y', strtotime($start_date)) + 543) . ' - ' . date('d/m/', strtotime($end_date)) . (date('Y', strtotime($end_date)) + 543);

} elseif ($has_month_year_filter) {
    if ($year > 2400) {
        $year -= 543;
    }

    $thai_year = $year + 543;
    $start_date = sprintf('%04d-%02d-01', $year, $month);
    $end_date = date('Y-m-t', strtotime($start_date));
    $period_label = 'ประจำเดือน ' . thaiMonthName($month) . ' ' . $thai_year;
}

/* =========================================================
   2) ดึง Event เฉพาะช่วงวันที่นั้น ๆ โดยใช้ event_date เป็นหลัก
========================================================= */
$events = [];
$events_by_day_key = [];
$work_record_ids = [];
$record_by_id = [];

$sql_events = "
    SELECT
        e.*,
        wr.id AS wr_id,
        wr.doc_no AS wr_doc_no,
        wr.rev_no AS wr_rev_no,
        wr.checksheet_name AS wr_checksheet_name,
        wr.machine_code AS wr_machine_code,
        wr.machine_name AS wr_machine_name,
        wr.machine_sn AS wr_machine_sn,
        wr.machine_type AS wr_machine_type,
        wr.location AS wr_location,
        wr.actual_date AS wr_actual_date,
        wr.inspector_name AS wr_inspector_name,
        wr.inspector_signature_path AS wr_signature_path,
        wr.remarks AS wr_remarks
    FROM pm_plan_events e
    LEFT JOIN pm_work_records wr ON wr.plan_event_id = e.id
    WHERE e.ag_id = ?
      AND e.machine_id = ?
      AND e.checksheet_id = ?
";

if ($has_date_filter) {
    $sql_events .= " AND e.event_date BETWEEN ? AND ? ";
}

$sql_events .= " ORDER BY e.event_date ASC, e.id ASC ";

$stmt = $connect->prepare($sql_events);
if (!$stmt) die('Prepare events failed: ' . $connect->error);

if ($has_date_filter) {
    $stmt->bind_param('ssiss', $ag_id, $machine_id, $checksheet_id, $start_date, $end_date);
} else {
    $stmt->bind_param('ssi', $ag_id, $machine_id, $checksheet_id);
}

$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $events[] = $row;
    $dayKey = date('Y-m-d', strtotime($row['event_date']));
    $events_by_day_key[$dayKey][] = $row;

    if (!empty($row['wr_id'])) {
        $wrid = intval($row['wr_id']);
        $work_record_ids[] = $wrid;
        $record_by_id[$wrid] = $row;
    }
}
$stmt->close();

if (empty($events)) {
    die('ไม่พบแผน PM ตามเงื่อนไขที่เลือก');
}

$work_record_ids = array_values(array_unique($work_record_ids));

/* =========================================================
   3) สร้างวันที่ต้องทำ PM เฉพาะวันที่มี event จริง
========================================================= */
$planned_days = [];
foreach ($events_by_day_key as $dayKey => $eventList) {
    $date = $eventList[0]['event_date'];
    $ts = strtotime($date);

    $planned_days[] = [
        'key' => $dayKey,
        'date' => $date,
        // ถ้าไม่ได้กรองเดือน/ปี ให้ใส่ปี 2 หลักด้วย เพื่อไม่ให้วันที่คนละเดือน/ปีดูชนกัน
        'label' => $has_date_filter ? date('j/m', $ts) : date('j/m/', $ts) . substr((string)(date('Y', $ts) + 543), -2),
        'short_label' => $has_date_filter ? date('j', $ts) : date('j/m', $ts),
        'weekday' => thaiDayShort($date),
        'is_weekend' => in_array((int)date('w', $ts), [0, 6], true)
    ];
}

usort($planned_days, function($a, $b) {
    return strcmp($a['date'], $b['date']);
});

$day_chunks = array_chunk($planned_days, $cols_per_page);

/* =========================================================
   4) ข้อมูล Header จาก Record ถ้ามี ไม่งั้นดึง master จาก event
========================================================= */
$header = [
    'doc_no' => $base_record['doc_no'] ?? ($events[0]['wr_doc_no'] ?? '-'),
    'rev_no' => $base_record['rev_no'] ?? ($events[0]['wr_rev_no'] ?? '-'),
    'checksheet_name' => $base_record['checksheet_name'] ?? ($events[0]['wr_checksheet_name'] ?? '-'),
    'machine_code' => $base_record['machine_code'] ?? ($events[0]['wr_machine_code'] ?? '-'),
    'machine_name' => $base_record['machine_name'] ?? ($events[0]['wr_machine_name'] ?? '-'),
    'machine_sn' => $base_record['machine_sn'] ?? ($events[0]['wr_machine_sn'] ?? '-'),
    'machine_type' => $base_record['machine_type'] ?? ($events[0]['wr_machine_type'] ?? '-'),
    'location' => $base_record['location'] ?? ($events[0]['wr_location'] ?? '-'),
    'signature_path' => $base_record['inspector_signature_path'] ?? ($events[0]['wr_signature_path'] ?? ''),
    'remarks' => $base_record['remarks'] ?? ($events[0]['wr_remarks'] ?? '')
];

/* ถ้ายังไม่มี header จาก work_records ให้ดึงจาก master */
if (($header['checksheet_name'] === '-' || $header['machine_name'] === '-') && $checksheet_id > 0) {
    $event_id_for_master = intval($base_event['id'] ?? ($events[0]['id'] ?? 0));
    $sql_master = "
        SELECT
            c.name AS checksheet_name,
            c.doc_no,
            c.rev_no,
            a.asset_name AS machine_name,
            a.ass_code AS machine_code,
            a.asset_sn AS machine_sn,
            g.TGroupName AS machine_type,
            CONCAT_WS(' / ', ar.area_name, ac.ac_name, rm.ar_name) AS location
        FROM pm_plan_events e
        LEFT JOIN pm_checksheets c ON e.checksheet_id = c.id
        LEFT JOIN tb_ass_list a ON e.machine_id = a.ass_id
        LEFT JOIN tb_asset_group g ON a.asset_type = g.GroupId
        LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
        LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
        LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
        WHERE e.id = ?
        LIMIT 1
    ";
    $stmt = $connect->prepare($sql_master);
    if ($stmt) {
        $stmt->bind_param('i', $event_id_for_master);
        $stmt->execute();
        $res = $stmt->get_result();
        $master = $res->fetch_assoc();
        $stmt->close();
        if ($master) {
            $header['checksheet_name'] = $header['checksheet_name'] !== '-' ? $header['checksheet_name'] : ($master['checksheet_name'] ?? '-');
            $header['doc_no'] = $header['doc_no'] !== '-' ? $header['doc_no'] : ($master['doc_no'] ?? '-');
            $header['rev_no'] = $header['rev_no'] !== '-' ? $header['rev_no'] : ($master['rev_no'] ?? '-');
            $header['machine_name'] = $header['machine_name'] !== '-' ? $header['machine_name'] : ($master['machine_name'] ?? '-');
            $header['machine_code'] = $header['machine_code'] !== '-' ? $header['machine_code'] : ($master['machine_code'] ?? '-');
            $header['machine_sn'] = $header['machine_sn'] !== '-' ? $header['machine_sn'] : ($master['machine_sn'] ?? '-');
            $header['machine_type'] = $header['machine_type'] !== '-' ? $header['machine_type'] : ($master['machine_type'] ?? '-');
            $header['location'] = $header['location'] !== '-' ? $header['location'] : ($master['location'] ?? '-');
        }
    }
}

/* =========================================================
   5) ดึงหัวข้อจากฐานข้อมูลจริง: pm_checksheet_items
   - รวมชื่อหัวข้อซ้ำด้วย check_point
   - ในฐานข้อมูลมีชื่อหัวข้อไม่ซ้ำกี่ข้อ ก็แสดงครบตามนั้น
   - ถ้าชื่อซ้ำ เช่น จุดตรวจที่ 1 หลายแถว จะออกแค่ 1 หัวข้อ แล้วรวมผลไปติ๊กในแถวเดียว
========================================================= */
$report_rows = [];
$master_key_by_item_id = [];

$sql_master_items = "
    SELECT id, check_point, standard_text, expected_value, unit, sort_order
    FROM pm_checksheet_items
    WHERE checksheet_id = ?
    ORDER BY sort_order ASC, id ASC
";
$stmt = $connect->prepare($sql_master_items);
if ($stmt) {
    $stmt->bind_param('i', $checksheet_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($item = $res->fetch_assoc()) {
        $itemId = intval($item['id']);
        if ($itemId <= 0) continue;

        $checkPoint = trim((string)($item['check_point'] ?? ''));
        $rowKey = 'cp_' . normalizeReportKey($checkPoint);
        $master_key_by_item_id[$itemId] = $rowKey;

        $standard = '';
        if (hasExpectedValue($item['expected_value'] ?? '')) {
            $standard = trim((string)$item['expected_value']) . (!empty($item['unit']) ? ' ' . $item['unit'] : '');
        } elseif (!empty($item['standard_text'])) {
            $standard = trim((string)$item['standard_text']);
        }

        if (!isset($report_rows[$rowKey])) {
            $report_rows[$rowKey] = [
                'key' => $rowKey,
                'checksheet_item_id' => $itemId,
                'sort_order' => intval($item['sort_order']),
                'check_point' => $checkPoint !== '' ? $checkPoint : '-',
                'standard' => $standard,
                'item_ids' => [$itemId],
                'duplicate_count' => 1,
                'days' => []
            ];
        } else {
            $report_rows[$rowKey]['standard'] = mergeStandardText($report_rows[$rowKey]['standard'], $standard);
            $report_rows[$rowKey]['item_ids'][] = $itemId;
            $report_rows[$rowKey]['duplicate_count']++;

            // ใช้ sort_order ที่น้อยที่สุดเป็นตำแหน่งแสดงผล
            if (intval($item['sort_order']) < intval($report_rows[$rowKey]['sort_order'])) {
                $report_rows[$rowKey]['sort_order'] = intval($item['sort_order']);
            }
        }
    }
    $stmt->close();
}

/* =========================================================
   6) เติมผลตรวจจาก work_record_items ลงในวันที่ของ event_date
   - จับด้วย check_point เป็นหลัก เพื่อลดหัวข้อซ้ำ
   - ถ้าวันเดียวกัน/หัวข้อเดียวกันมีหลายรายการ จะสรุปในช่องเดียว
     Fail มาก่อน Pass, Pass มาก่อน N/A
========================================================= */
if (!empty($work_record_ids)) {
    $ids_sql = implode(',', array_map('intval', $work_record_ids));

    $sql_items = "
        SELECT *
        FROM pm_work_record_items
        WHERE work_record_id IN ($ids_sql)
        ORDER BY sort_order ASC, id ASC
    ";

    $res_items = mysqli_query($connect, $sql_items);
    if (!$res_items) die('SQL Error Items: ' . mysqli_error($connect));

    while ($item = mysqli_fetch_assoc($res_items)) {
        $wrid = intval($item['work_record_id']);
        if (!isset($record_by_id[$wrid])) continue;

        $event_date = $record_by_id[$wrid]['event_date'] ?? '';
        if (!$event_date) continue;

        $dayKey = date('Y-m-d', strtotime($event_date));
        if (!$dayKey) continue;

        $checkPoint = trim((string)($item['check_point'] ?? ''));
        $key = 'cp_' . normalizeReportKey($checkPoint);

        // fallback เผื่อรายการ snapshot ไม่อยู่ใน master หรือ master ว่าง
        if (!isset($report_rows[$key])) {
            $standard = '';
            if (hasExpectedValue($item['expected_value'] ?? '')) {
                $standard = trim((string)$item['expected_value']) . (!empty($item['unit']) ? ' ' . $item['unit'] : '');
            } elseif (!empty($item['standard_text'])) {
                $standard = trim((string)$item['standard_text']);
            }

            $report_rows[$key] = [
                'key' => $key,
                'checksheet_item_id' => intval($item['checksheet_item_id'] ?? 0),
                'sort_order' => intval($item['sort_order'] ?? 9999),
                'check_point' => $checkPoint !== '' ? $checkPoint : '-',
                'standard' => $standard,
                'item_ids' => [],
                'duplicate_count' => 1,
                'days' => []
            ];
        }

        if (!isset($report_rows[$key]['days'][$dayKey])) {
            $report_rows[$key]['days'][$dayKey] = ['items' => []];
        }

        $report_rows[$key]['days'][$dayKey]['items'] = mergeDayItems(
            $report_rows[$key]['days'][$dayKey]['items'],
            $item
        );
    }
}

$report_rows = array_values($report_rows);
usort($report_rows, function($a, $b) {
    if ($a['sort_order'] === $b['sort_order']) {
        return intval($a['checksheet_item_id']) <=> intval($b['checksheet_item_id']);
    }
    return $a['sort_order'] <=> $b['sort_order'];
});

foreach ($report_rows as $idx => &$r) {
    $r['_row_no'] = $idx + 1;
}
unset($r);

$row_chunks = array_chunk($report_rows, $rows_per_page);
if (empty($row_chunks)) {
    $row_chunks = [[]];
}

/* ผู้ตรวจสอบ */
$inspectors = [];
if (!empty($work_record_ids)) {
    $firstWrId = intval($work_record_ids[0]);
    $sql_inspectors = "
        SELECT inspector_name_snapshot
        FROM pm_work_record_inspectors
        WHERE work_record_id = $firstWrId
        ORDER BY id ASC
    ";
    $res_inspectors = mysqli_query($connect, $sql_inspectors);
    if ($res_inspectors) {
        while ($row = mysqli_fetch_assoc($res_inspectors)) {
            if (!empty($row['inspector_name_snapshot'])) $inspectors[] = $row['inspector_name_snapshot'];
        }
    }
}
if (empty($inspectors) && !empty($events[0]['wr_inspector_name'])) {
    $inspectors[] = $events[0]['wr_inspector_name'];
}
$inspector_text = !empty($inspectors) ? implode(', ', array_unique($inspectors)) : '';

$total_plan_count = count($events);
$done_count = count($work_record_ids);
$pending_count = max(0, $total_plan_count - $done_count);
$total_pages = count($day_chunks) * count($row_chunks);
$page_counter = 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>PM Due Dates Report V5 - <?php echo h($header['machine_name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #e5e7eb;
            font-family: Tahoma, Arial, sans-serif;
            color: #000;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 99;
            background: #0f172a;
            color: #fff;
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .toolbar-title { font-size: 14px; font-weight: bold; }
        .toolbar button {
            border: 0;
            border-radius: 6px;
            padding: 8px 14px;
            cursor: pointer;
            font-size: 13px;
            margin-left: 6px;
        }
        .btn-print { background: #0284c7; color: #fff; }
        .btn-close { background: #334155; color: #fff; }

        .page-wrap { padding: 14px; }

        .report-page {
            width: 297mm;
            min-height: 210mm;
            background: #fff;
            margin: 0 auto 12px;
            padding: 6mm;
            border: 1px solid #111;
            page-break-after: always;
        }

        .report-page:last-child { page-break-after: auto; }

        .report-title {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            line-height: 1.45;
            margin-bottom: 5px;
        }

        .report-subtitle {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            line-height: 1.3;
            margin-bottom: 6px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr 0.75fr;
            gap: 3px;
            margin-bottom: 4px;
        }

        .info-box {
            border: 1px solid #111;
            padding: 3px 5px;
            min-height: 18px;
            font-size: 7.5px;
            line-height: 1.25;
        }

        .info-box strong { font-weight: bold; }

        .meta-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8px;
            margin: 3px 0 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #111;
            padding: 2px;
            font-size: 7px;
            line-height: 1.2;
            vertical-align: middle;
        }

        th {
            background: #dbeafe;
            font-weight: bold;
            text-align: center;
        }

        .section-row td {
            background: #bfbfbf;
            font-weight: bold;
        }

        .col-no { width: 8mm; text-align: center; }
        .col-task { width: 62mm; }
        .col-standard { width: 34mm; text-align: center; }
        .col-day { width: 12mm; text-align: center; }
        .col-remark { width: 55mm; }

        .day-head {
            text-align: center;
            font-size: 7px;
            line-height: 1.1;
            padding: 1px;
            height: 20px;
            white-space: nowrap;
        }

        .day-main { font-weight: bold; font-size: 8px; }
        .day-sub { font-size: 6px; font-weight: normal; }
        .weekend { background: #9ca3af !important; }

        .result-cell {
            height: 15px;
            text-align: center;
            font-size: 8px;
            overflow: hidden;
            white-space: nowrap;
            padding: 1px;
        }

        .cell-pass { font-weight: bold; }
        .cell-fail { color: #b00000; font-weight: bold; }
        .cell-na { color: #374151; }
        .cell-value { font-size: 6.5px; white-space: normal; }
        .cell-pending { color: #b45309; background: #fff7ed; font-size: 6px; }

        .remark-cell {
            font-size: 6.5px;
            line-height: 1.25;
            word-break: break-word;
        }

        .legend {
            border-left: 1px solid #111;
            border-right: 1px solid #111;
            border-bottom: 1px solid #111;
            text-align: center;
            font-size: 7px;
            padding: 3px;
            background: #f8fafc;
        }

        .extra-remark {
            border-left: 1px solid #111;
            border-right: 1px solid #111;
            border-bottom: 1px solid #111;
            min-height: 24px;
            padding: 4px;
            font-size: 7px;
        }

        .sign-table { margin-top: 6px; }
        .sign-table td {
            height: 35px;
            text-align: center;
            vertical-align: bottom;
            font-size: 7px;
        }

        .sign-line {
            border-bottom: 1px dotted #000;
            height: 13px;
            margin: 0 20px 2px;
        }

        .signature-img {
            max-height: 20px;
            max-width: 110px;
            object-fit: contain;
            display: block;
            margin: 0 auto 1px;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 6.5px;
            margin-top: 2px;
        }

        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page-wrap { padding: 0; }
            .report-page {
                width: 100%;
                min-height: auto;
                margin: 0;
                border: 0;
                padding: 0;
                page-break-after: always;
            }
            .report-page:last-child { page-break-after: auto; }
            @page { size: A4 landscape; margin: 6mm; }
        }
    </style>
</head>

<body>

<div class="toolbar">
    <div class="toolbar-title">
        PM Due Dates Report V5 : <?php echo h($header['machine_name']); ?> / <?php echo h($period_label); ?>
    </div>
    <div>
        <button type="button" class="btn-print" onClick="window.print()">พิมพ์ / Save PDF</button>
        <button type="button" class="btn-close" onClick="window.close()">ปิด</button>
    </div>
</div>

<div class="page-wrap">
<?php foreach ($day_chunks as $day_chunk_index => $chunk_days): ?>
    <?php foreach ($row_chunks as $row_chunk_index => $chunk_rows): ?>
        <?php $page_counter++; ?>
        <?php $empty_rows = max(0, $rows_per_page - count($chunk_rows)); ?>
        <div class="report-page">
            <div class="report-title">
                แบบรายงานผลการบำรุงรักษาเชิงป้องกันรายเดือน
            </div>
            <div class="report-subtitle">
                <?php echo h($header['checksheet_name']); ?> <?php echo h($period_label); ?>
            </div>

            <div class="info-grid">
                <div class="info-box"><strong>เครื่องจักร/อุปกรณ์:</strong> <?php echo h($header['machine_name']); ?></div>
                <div class="info-box"><strong>รหัสเครื่อง:</strong> <?php echo h($header['machine_code']); ?></div>
                <div class="info-box"><strong>ประเภท:</strong> <?php echo h($header['machine_type']); ?></div>
                <div class="info-box"><strong>เลขที่เอกสาร:</strong> <?php echo h($header['doc_no']); ?></div>
            </div>

            <div class="info-grid">
                <div class="info-box" style="grid-column: span 2;"><strong>สถานที่:</strong> <?php echo h($header['location']); ?></div>
                <div class="info-box"><strong>Serial No.:</strong> <?php echo h($header['machine_sn']); ?></div>
                <div class="info-box"><strong>Rev:</strong> <?php echo h($header['rev_no']); ?></div>
            </div>

            <div class="meta-line">
                <div>แสดงวันที่ต้องดำเนินการ PM ตามเงื่อนไข | ถ้าไม่ส่งเดือน/ปีจะแสดงทั้งหมด | รวมหัวข้อซ้ำตามชื่อ</div>
                <div>หน้า <?php echo $page_counter; ?> / <?php echo $total_pages; ?></div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th rowspan="2" class="col-no">ลำดับ</th>
                        <th rowspan="2" class="col-task">รายการตรวจสอบ</th>
                        <th rowspan="2" class="col-standard">มาตรฐาน / ค่าที่ต้องการ</th>
                        <th colspan="<?php echo count($chunk_days); ?>">วันที่ต้องดำเนินการ PM</th>
                        <th rowspan="2" class="col-remark">หมายเหตุ</th>
                    </tr>
                    <tr>
                        <?php foreach ($chunk_days as $dayInfo): ?>
                            <th class="col-day day-head <?php echo $dayInfo['is_weekend'] ? 'weekend' : ''; ?>">
                                <div class="day-main"><?php echo h($dayInfo['label']); ?></div>
                                <div class="day-sub"><?php echo h($dayInfo['weekday']); ?></div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>

                <tbody>
                    <tr class="section-row">
                        <td></td>
                        <td colspan="<?php echo 3 + count($chunk_days); ?>">รายการตรวจสอบ / PM CHECK ITEMS</td>
                    </tr>

                    <?php foreach ($chunk_rows as $row): ?>
                        <tr>
                            <td class="col-no"><?php echo h($row['_row_no']); ?></td>
                            <td class="col-task"><?php echo h($row['check_point']); ?></td>
                            <td class="col-standard"><?php echo h($row['standard']); ?></td>

                            <?php foreach ($chunk_days as $dayInfo): ?>
                                <?php
                                    $dayKey = $dayInfo['key'];
                                    $itemsForDay = $row['days'][$dayKey]['items'] ?? [];
                                    $cell = buildCellFromItems($itemsForDay, $show_values_in_cell);

                                    if ($cell['text'] === '' && $show_pending && isset($events_by_day_key[$dayKey])) {
                                        $cell['text'] = 'รอ';
                                        $cell['class'] = 'cell-pending';
                                    }
                                ?>
                                <td class="result-cell <?php echo h(($dayInfo['is_weekend'] ? 'weekend ' : '') . $cell['class']); ?>">
                                    <?php echo h($cell['text']); ?>
                                </td>
                            <?php endforeach; ?>

                            <td class="col-remark remark-cell">
                                <?php echo h(buildRemarkForDays($row, $chunk_days)); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php for ($i = 0; $i < $empty_rows; $i++): ?>
                        <tr>
                            <td class="col-no">&nbsp;</td>
                            <td class="col-task">&nbsp;</td>
                            <td class="col-standard">&nbsp;</td>
                            <?php foreach ($chunk_days as $dayInfo): ?>
                                <td class="result-cell <?php echo $dayInfo['is_weekend'] ? 'weekend' : ''; ?>">&nbsp;</td>
                            <?php endforeach; ?>
                            <td class="col-remark">&nbsp;</td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>

            <div class="legend">
                / = ปกติหรือดำเนินการแล้ว &nbsp;&nbsp;&nbsp;
                X = ผิดปกติ &nbsp;&nbsp;&nbsp;
                - = ไม่เกี่ยวข้อง / ไม่มีข้อมูล
                <?php if ($show_pending): ?> &nbsp;&nbsp;&nbsp; รอ = มีแผนแต่ยังไม่บันทึกผล <?php endif; ?>
            </div>

            <div class="extra-remark">
                <strong>หมายเหตุเพิ่มเติม:</strong> <?php echo nl2br(h($header['remarks'])); ?>
            </div>

            <table class="sign-table">
                <tr>
                    <td style="width:33.33%;">
                        <?php if ($page_counter === $total_pages && !empty($header['signature_path'])): ?>
                            <img src="<?php echo h($header['signature_path']); ?>" class="signature-img">
                        <?php else: ?>
                            <div class="sign-line"></div>
                        <?php endif; ?>
                        <div>ผู้ดำเนินการ / ผู้ตรวจสอบ</div>
                        <div><?php echo h($inspector_text); ?></div>
                    </td>
                    <td style="width:33.33%;">
                        <div class="sign-line"></div>
                        <div>หัวหน้างาน / ผู้ทวนสอบ</div>
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
                <div>PM-DUE-DATES Rev.04</div>
                <div>
                    แผนทั้งหมด <?php echo $total_plan_count; ?> รายการ |
                    บันทึกแล้ว <?php echo $done_count; ?> รายการ |
                    รอดำเนินการ <?php echo $pending_count; ?> รายการ |
                    หัวข้อทั้งหมด <?php echo count($report_rows); ?> ข้อ
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endforeach; ?>
</div>

<script>
    const AUTO_PRINT = new URLSearchParams(window.location.search).get('auto') === '1';
    if (AUTO_PRINT) {
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 500);
        });
    }
</script>

</body>
</html>
