<?php
@session_start();
include "config_ctrl/checksession.php";
include "config_ctrl/connect.php";

date_default_timezone_set('Asia/Bangkok');

// รับ plan_id ที่ส่งมาจาก pm_plan.php
$plan_id = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0; 
$work_record_id = isset($_GET['work_record_id']) ? intval($_GET['work_record_id']) : 0;

$date_from = isset($_GET['date_from']) ? trim((string)$_GET['date_from']) : '';
$date_to   = isset($_GET['date_to']) ? trim((string)$_GET['date_to']) : '';
$filter_type = isset($_GET['filter_type']) ? trim((string)$_GET['filter_type']) : 'daily';

// --- ค่ากำหนด หน้ากระดาษ Rows & Cols ---
$rows_per_page = isset($_GET['rows']) ? intval($_GET['rows']) : 18;
if ($rows_per_page < 5) $rows_per_page = 5;

$cols_per_page = isset($_GET['cols']) ? intval($_GET['cols']) : 15;
if ($cols_per_page < 5) $cols_per_page = 5;
if ($cols_per_page > 31) $cols_per_page = 31; // จำกัดไม่เกิน 31 วันต่อหน้า

// --- รูปแบบการแสดงผล ---
// 'event' = เฉพาะวันที่มีงาน, 'full_month' = แสดงทุกวัน
$view_mode = isset($_GET['view_mode']) ? trim((string)$_GET['view_mode']) : 'event';

$show_pending = isset($_GET['show_pending']) && $_GET['show_pending'] == '1';
$show_values_in_cell = isset($_GET['values']) && $_GET['values'] == '1';

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

function actualValueText($item) {
    $actual = trim((string)($item['actual_value'] ?? ''));
    $unit = trim((string)($item['unit'] ?? ''));

    if ($actual === '' || $actual === '-') return '';
    return $actual . ($unit !== '' ? ' ' . $unit : '');
}

function buildCellFromItems($items, $show_values_in_cell) {
    if (empty($items)) return ['text' => '', 'class' => ''];

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
        if ($valueText !== '') $values[] = $valueText;
    }

    if ($hasFail) {
        $mark = 'X';
        $class = 'text-[#b00000] font-bold';
    } elseif ($hasPass) {
        $mark = '/';
        $class = 'font-bold';
    } elseif ($hasNA) {
        $mark = '-';
        $class = 'text-gray-700';
    } else {
        $mark = '';
        $class = '';
    }

    $values = array_values(array_unique($values));

    if ($show_values_in_cell && !empty($values)) {
        return ['text' => implode(', ', $values), 'class' => trim($class . ' text-[6.5px] whitespace-normal')];
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
            if (($item['result_status'] ?? '') === 'Fail') $hasFail = true;
            $valueText = actualValueText($item);
            if ($valueText !== '') $dayValues[] = $valueText;
        }

        $dayValues = array_values(array_unique($dayValues));
        if (!empty($dayValues)) $notes[] = $label . ': ' . implode(', ', $dayValues);
        if ($hasFail) $notes[] = $label . ': ผิดปกติ';
    }
    return implode(' | ', array_values(array_unique($notes)));
}

function normalizeReportKey($value) {
    $value = trim((string)$value);
    $value = preg_replace('/\s+/u', ' ', $value);
    if ($value === '') return 'empty_' . md5($value);
    if (function_exists('mb_strtolower')) return md5(mb_strtolower($value, 'UTF-8'));
    return md5(strtolower($value));
}

function mergeStandardText($old, $new) {
    $old = trim((string)$old);
    $new = trim((string)$new);
    if ($old === '') return $new;
    if ($new === '') return $old;
    if ($old === $new) return $old;
    $parts = array_filter(array_map('trim', explode(' / ', $old)));
    if (!in_array($new, $parts, true)) $parts[] = $new;
    return implode(' / ', $parts);
}

function mergeDayItems($existingItems, $newItem) {
    if (!is_array($existingItems)) $existingItems = [];
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
$actual_plan_id = $plan_id;

if ($plan_id > 0) {
    $sql = "SELECT * FROM pm_plan_events WHERE plan_id = ? LIMIT 1";
    $stmt = $connect->prepare($sql);
    if (!$stmt) die('Prepare base event failed: ' . $connect->error);
    $stmt->bind_param('i', $plan_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $base_event = $res->fetch_assoc();
    $stmt->close();

    if (!$base_event) die('ไม่พบข้อมูลแผนงาน PM');

    if (!empty($base_event['work_record_id'])) {
        $wrid = intval($base_event['work_record_id']);
        $sql = "SELECT * FROM pm_work_records WHERE id = ? LIMIT 1";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('i', $wrid);
        $stmt->execute();
        $base_record = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if ($work_record_id > 0) {
    $sql = "SELECT * FROM pm_work_records WHERE id = ? LIMIT 1";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param('i', $work_record_id);
    $stmt->execute();
    $base_record = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$base_record) die('ไม่พบข้อมูลประวัติ PM');

    $eventId = intval($base_record['plan_event_id']);
    if ($eventId > 0) {
        $sql = "SELECT * FROM pm_plan_events WHERE id = ? LIMIT 1";
        $stmt = $connect->prepare($sql);
        $stmt->bind_param('i', $eventId);
        $stmt->execute();
        $base_event = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $actual_plan_id = intval($base_event['plan_id'] ?? 0);
    }
}

$ag_id = $base_event['ag_id'] ?? ($base_record['ag_id'] ?? $session_ag_id);
$machine_id = $base_event['machine_id'] ?? ($base_record['machine_id'] ?? '');
$checksheet_id = intval($base_event['checksheet_id'] ?? ($base_record['checksheet_id'] ?? 0));

/* =========================================================
   เงื่อนไขช่วงวันที่
========================================================= */
$has_range_filter = validDateYmd($date_from) && validDateYmd($date_to);
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
}

/* =========================================================
   2) ดึง Event ตาม plan_id และช่วงวันที่
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
    WHERE e.plan_id = ?
";

if ($has_range_filter) {
    $sql_events .= " AND e.event_date BETWEEN ? AND ? ";
}
$sql_events .= " ORDER BY e.event_date ASC, e.id ASC ";

$stmt = $connect->prepare($sql_events);
if (!$stmt) die('Prepare events failed: ' . $connect->error);

if ($has_range_filter) {
    $stmt->bind_param('iss', $actual_plan_id, $start_date, $end_date);
} else {
    $stmt->bind_param('i', $actual_plan_id);
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

/* =========================================================
   ดึงวันที่ทั้งหมดที่มีแผน PM ก่อนการเช็ค empty($events)
========================================================= */
$sql_available = "SELECT DISTINCT DATE(event_date) as event_date FROM pm_plan_events WHERE plan_id = ?";
$stmt_av = $connect->prepare($sql_available);
$stmt_av->bind_param('i', $actual_plan_id);
$stmt_av->execute();
$res_av = $stmt_av->get_result();
$available_dates = [];
while ($row = $res_av->fetch_assoc()) {
    $available_dates[] = $row['event_date'];
}
$stmt_av->close();
// เปลี่ยนเป็น JSON เพื่อส่งให้ JS
$available_dates_json = json_encode($available_dates);

/* =========================================================
   ตรวจสอบและจัดการกรณีไม่พบข้อมูล (พร้อมแจ้งช่วงวันที่ที่มี)
========================================================= */
if (empty($events)) {
    $suggestion_html = "ไม่พบแผน PM ตามเงื่อนไขวันที่เลือก";
    $has_data = false;
    $min_date = '';
    $max_date = '';

    if (!empty($available_dates)) {
        $has_data = true;
        sort($available_dates); // เรียงจากเก่าไปใหม่
        $min_date = $available_dates[0];
        $max_date = end($available_dates);
        
        $display_min = date('d/m/Y', strtotime($min_date));
        $display_max = date('d/m/Y', strtotime($max_date));
        
        $suggestion_html .= "<br><br><span style='font-size: 14px; color: #666;'>เครื่องจักรนี้มีแผน PM อยู่ในช่วงวันที่</span><br><b style='color: #0284c7; font-size: 16px;'>{$display_min} ถึง {$display_max}</b>";
    } else {
        $suggestion_html .= "<br><br><span style='color: #dc2626; font-size: 14px;'>ยังไม่มีการสร้างแผน PM สำหรับเครื่องจักรนี้ในระบบ</span>";
    }

    echo '
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body style="background-color: #f3f4f6;">
        <script>
            Swal.fire({
                icon: "info",
                title: "ไม่พบข้อมูลในช่วงที่เลือก",
                html: "' . $suggestion_html . '",
                showCancelButton: true,
                confirmButtonText: "' . ($has_data ? 'ไปยังช่วงที่มีข้อมูล' : 'รับทราบ') . '",
                confirmButtonColor: "#0284c7",
                cancelButtonText: "ย้อนกลับ",
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed && ' . ($has_data ? 'true' : 'false') . ') {
                    // Redirect เปลี่ยน Date Filter อัตโนมัติ
                    const url = new URL(window.location.href);
                    url.searchParams.set("date_from", "' . $min_date . '");
                    url.searchParams.set("date_to", "' . $max_date . '");
                    url.searchParams.set("filter_type", "daily");
                    
                    // ล้างพารามิเตอร์เก่าๆ ทิ้ง
                    url.searchParams.delete("month");
                    url.searchParams.delete("year");
                    
                    window.location.href = url.toString();
                } else {
                    window.history.back();
                }
            });
        </script>
    </body>
    </html>';
    exit;
}

$work_record_ids = array_values(array_unique($work_record_ids));

/* =========================================================
   3) สร้างข้อมูลส่วนหัวคอลัมน์ (วันที่) ตาม View Mode
========================================================= */
$planned_days = [];

if ($view_mode === 'full_month') {
    // โหมด "แสดงทุกวันในเดือน/ช่วง"
    $iter_start = $start_date;
    $iter_end = $end_date;
    
    // ถ้าไม่ได้ส่ง date_from, date_to มา ให้คำนวณจาก event ที่มี
    if (!$iter_start || !$iter_end) {
        $min_date = $events[0]['event_date'];
        $max_date = $events[count($events)-1]['event_date'];
        $iter_start = date('Y-m-01', strtotime($min_date));
        $iter_end = date('Y-m-t', strtotime($max_date));
    }
    
    $current_ts = strtotime($iter_start);
    $end_ts = strtotime($iter_end);
    
    while ($current_ts <= $end_ts) {
        $date_str = date('Y-m-d', $current_ts);
        $planned_days[] = [
            'key' => $date_str,
            'date' => $date_str,
            'label' => $has_range_filter ? date('j/m', $current_ts) : date('j/m/', $current_ts) . substr((string)(date('Y', $current_ts) + 543), -2),
            'short_label' => $has_range_filter ? date('j', $current_ts) : date('j/m', $current_ts),
            'weekday' => thaiDayShort($date_str),
            'is_weekend' => in_array((int)date('w', $current_ts), [0, 6], true)
        ];
        $current_ts = strtotime('+1 day', $current_ts);
    }
} else {
    // โหมด "เฉพาะวันที่มีงาน" (event)
    foreach ($events_by_day_key as $dayKey => $eventList) {
        $date = $eventList[0]['event_date'];
        $ts = strtotime($date);

        $planned_days[] = [
            'key' => $dayKey,
            'date' => $date,
            'label' => $has_range_filter ? date('j/m', $ts) : date('j/m/', $ts) . substr((string)(date('Y', $ts) + 543), -2),
            'short_label' => $has_range_filter ? date('j', $ts) : date('j/m', $ts),
            'weekday' => thaiDayShort($date),
            'is_weekend' => in_array((int)date('w', $ts), [0, 6], true)
        ];
    }
    usort($planned_days, function($a, $b) {
        return strcmp($a['date'], $b['date']);
    });
}

// แบ่งคอลัมน์ตาม cols_per_page
$day_chunks = array_chunk($planned_days, $cols_per_page);

/* =========================================================
   4) ข้อมูล Header
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

if (($header['checksheet_name'] === '-' || $header['machine_name'] === '-') && $checksheet_id > 0) {
    $event_id_for_master = intval($base_event['id'] ?? ($events[0]['id'] ?? 0));
    $sql_master = "
        SELECT c.name AS checksheet_name, c.doc_no, c.rev_no, a.asset_name AS machine_name,
               a.ass_code AS machine_code, a.asset_sn AS machine_sn, g.TGroupName AS machine_type,
               CONCAT_WS(' / ', ar.area_name, ac.ac_name, rm.ar_name) AS location
        FROM pm_plan_events e
        LEFT JOIN pm_checksheets c ON e.checksheet_id = c.id
        LEFT JOIN tb_ass_list a ON e.machine_id = a.ass_id
        LEFT JOIN tb_asset_group g ON a.asset_type = g.GroupId
        LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
        LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
        LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
        WHERE e.id = ? LIMIT 1
    ";
    $stmt = $connect->prepare($sql_master);
    if ($stmt) {
        $stmt->bind_param('i', $event_id_for_master);
        $stmt->execute();
        $master = $stmt->get_result()->fetch_assoc();
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
   5) ดึงหัวข้อจากฐานข้อมูลจริง
========================================================= */
$report_rows = [];
$sql_master_items = "SELECT id, check_point, standard_text, expected_value, unit, sort_order FROM pm_checksheet_items WHERE checksheet_id = ? ORDER BY sort_order ASC, id ASC";
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

        $standard = '';
        if (hasExpectedValue($item['expected_value'] ?? '')) {
            $standard = trim((string)$item['expected_value']) . (!empty($item['unit']) ? ' ' . $item['unit'] : '');
        } elseif (!empty($item['standard_text'])) {
            $standard = trim((string)$item['standard_text']);
        }

        if (!isset($report_rows[$rowKey])) {
            $report_rows[$rowKey] = [
                'key' => $rowKey, 'checksheet_item_id' => $itemId, 'sort_order' => intval($item['sort_order']),
                'check_point' => $checkPoint !== '' ? $checkPoint : '-', 'standard' => $standard,
                'item_ids' => [$itemId], 'duplicate_count' => 1, 'days' => []
            ];
        } else {
            $report_rows[$rowKey]['standard'] = mergeStandardText($report_rows[$rowKey]['standard'], $standard);
            $report_rows[$rowKey]['item_ids'][] = $itemId;
            $report_rows[$rowKey]['duplicate_count']++;
            if (intval($item['sort_order']) < intval($report_rows[$rowKey]['sort_order'])) {
                $report_rows[$rowKey]['sort_order'] = intval($item['sort_order']);
            }
        }
    }
    $stmt->close();
}

/* =========================================================
   6) เติมผลตรวจจาก work_record_items
========================================================= */
if (!empty($work_record_ids)) {
    $ids_sql = implode(',', array_map('intval', $work_record_ids));
    $sql_items = "SELECT * FROM pm_work_record_items WHERE work_record_id IN ($ids_sql) ORDER BY sort_order ASC, id ASC";
    $res_items = mysqli_query($connect, $sql_items);
    if ($res_items) {
        while ($item = mysqli_fetch_assoc($res_items)) {
            $wrid = intval($item['work_record_id']);
            if (!isset($record_by_id[$wrid])) continue;
            $event_date = $record_by_id[$wrid]['event_date'] ?? '';
            if (!$event_date) continue;

            $dayKey = date('Y-m-d', strtotime($event_date));
            $checkPoint = trim((string)($item['check_point'] ?? ''));
            $key = 'cp_' . normalizeReportKey($checkPoint);

            if (!isset($report_rows[$key])) {
                $standard = '';
                if (hasExpectedValue($item['expected_value'] ?? '')) {
                    $standard = trim((string)$item['expected_value']) . (!empty($item['unit']) ? ' ' . $item['unit'] : '');
                } elseif (!empty($item['standard_text'])) {
                    $standard = trim((string)$item['standard_text']);
                }

                $report_rows[$key] = [
                    'key' => $key, 'checksheet_item_id' => intval($item['checksheet_item_id'] ?? 0),
                    'sort_order' => intval($item['sort_order'] ?? 9999), 'check_point' => $checkPoint !== '' ? $checkPoint : '-',
                    'standard' => $standard, 'item_ids' => [], 'duplicate_count' => 1, 'days' => []
                ];
            }

            if (!isset($report_rows[$key]['days'][$dayKey])) $report_rows[$key]['days'][$dayKey] = ['items' => []];
            $report_rows[$key]['days'][$dayKey]['items'] = mergeDayItems($report_rows[$key]['days'][$dayKey]['items'], $item);
        }
    }
}

$report_rows = array_values($report_rows);
usort($report_rows, function($a, $b) {
    if ($a['sort_order'] === $b['sort_order']) return intval($a['checksheet_item_id']) <=> intval($b['checksheet_item_id']);
    return $a['sort_order'] <=> $b['sort_order'];
});

foreach ($report_rows as $idx => &$r) { $r['_row_no'] = $idx + 1; } unset($r);
$row_chunks = array_chunk($report_rows, $rows_per_page);
if (empty($row_chunks)) $row_chunks = [[]];

$inspectors = [];
if (!empty($work_record_ids)) {
    $firstWrId = intval($work_record_ids[0]);
    $res_inspectors = mysqli_query($connect, "SELECT inspector_name_snapshot FROM pm_work_record_inspectors WHERE work_record_id = $firstWrId ORDER BY id ASC");
    if ($res_inspectors) {
        while ($row = mysqli_fetch_assoc($res_inspectors)) {
            if (!empty($row['inspector_name_snapshot'])) $inspectors[] = $row['inspector_name_snapshot'];
        }
    }
}
if (empty($inspectors) && !empty($events[0]['wr_inspector_name'])) $inspectors[] = $events[0]['wr_inspector_name'];
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
    <title>PM Due Dates Report - <?php echo h($header['machine_name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <style>
        @media print {
            html, body { 
                background: white !important; 
                padding: 0 !important; 
                margin: 0 !important;
                height: auto !important; 
                overflow: visible !important;
                display: block !important;
                zoom: 95%; 
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page { size: A4 landscape; margin: 6mm; }
        }
    </style>
</head>

<body class="m-0 bg-gray-200 font-[Tahoma,Arial,sans-serif] text-black print:bg-white">

<div class="sticky top-0 z-[99] bg-slate-900 text-white px-4 py-2 flex justify-between items-center print:hidden">
    <div class="text-sm font-bold truncate pr-2">
        PM Due Dates Report : <?php echo h($header['machine_name']); ?>
    </div>
    <div class="flex-shrink-0">
        <button type="button" class="border-0 rounded-md px-2 md:px-3.5 py-1.5 md:py-2 cursor-pointer text-[12px] md:text-[13px] ml-1 bg-sky-600 text-white hover:bg-sky-700" onClick="window.print()">พิมพ์</button>
        <button type="button" class="border-0 rounded-md px-2 md:px-3.5 py-1.5 md:py-2 cursor-pointer text-[12px] md:text-[13px] ml-1 bg-slate-700 text-white hover:bg-slate-600" onClick="window.close()">ปิด</button>
    </div>
</div>

<div class="bg-white px-4 py-4 border-b border-gray-200 print:hidden mb-4">
    <div class="flex flex-col md:flex-row flex-wrap items-start md:items-end gap-4 md:gap-3">
        
        <div class="w-full md:w-auto px-2 md:border-r border-slate-200 md:pr-4">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">รูปแบบแสดงผล</label>
            <select id="viewMode" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 block w-full md:w-60 p-2 outline-none">
                <option value="event" <?php echo $view_mode == 'event' ? 'selected' : ''; ?>>ตามรอบงาน (เฉพาะวันทำ)</option>
                <option value="full_month" <?php echo $view_mode == 'full_month' ? 'selected' : ''; ?>>ตามรอบเดือน (ทุกวัน)</option>
            </select>
        </div>

        <div class="w-full md:w-auto px-2">
            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">ช่วงเวลา</label>
            <select id="filterType" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 block w-full md:w-32 p-2 outline-none">
                <option value="daily" <?php echo $filter_type == 'daily' ? 'selected' : ''; ?>>รายวัน</option>
                <option value="monthly" <?php echo $filter_type == 'monthly' ? 'selected' : ''; ?>>รายเดือน</option>
                <option value="yearly" <?php echo $filter_type == 'yearly' ? 'selected' : ''; ?>>รายปี</option>
            </select>
        </div>

        <div id="filterInputs" class="w-full md:w-auto px-2 flex flex-col sm:flex-row items-start sm:items-center gap-2"></div>

        <div class="w-full md:w-auto px-2 md:border-l border-slate-200 md:pl-4 flex gap-3">
            <div class="w-1/2 md:w-auto">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">แถว/หน้า</label>
                <input type="number" id="rowsInput" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 block w-full md:w-20 p-2 outline-none" value="<?php echo $rows_per_page; ?>" min="5" max="50">
            </div>
            <div class="w-1/2 md:w-auto">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">คอลัมน์/หน้า</label>
                <input type="number" id="colsInput" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 block w-full md:w-20 p-2 outline-none" value="<?php echo $cols_per_page; ?>" min="5" max="31">
            </div>
        </div>

        <div class="w-full md:w-auto px-2 md:ml-auto mt-2 md:mt-0">
            <button type="button" onclick="applyFilter()" class="w-full md:w-auto bg-sky-600 text-white text-sm rounded-lg px-6 py-2 font-bold hover:bg-sky-700 focus:ring-2 focus:ring-sky-500 outline-none">
                แสดงผล
            </button>
        </div>
    </div>
</div>

<div class="w-full overflow-x-auto p-2 sm:p-3.5 print:overflow-visible print:p-0">
    <div class="min-w-[297mm]">
        <?php foreach ($day_chunks as $day_chunk_index => $chunk_days): ?>
            <?php foreach ($row_chunks as $row_chunk_index => $chunk_rows): ?>
                <?php $page_counter++; ?>
                <?php $empty_rows = max(0, $rows_per_page - count($chunk_rows)); ?>
                <div class="w-[297mm] bg-white mx-auto mb-3 p-[6mm] border border-gray-300 shadow-sm [page-break-after:always] last:page-break-after-auto print:w-full print:m-0 print:border-0 print:p-0 print:shadow-none flex flex-col justify-between">
            <div>
                <div class="text-center font-bold text-[13px] leading-snug mb-1.5">
                    แบบรายงานผลการบำรุงรักษาเชิงป้องกันรายเดือน
                </div>
                <div class="text-center font-bold text-[11px] leading-tight mb-1.5">
                    <?php echo h($header['checksheet_name']); ?> <?php echo h($period_label); ?>
                </div>

                <div class="grid grid-cols-[1.2fr_1fr_1fr_0.75fr] gap-[3px] mb-1">
                    <div class="border border-black px-1.5 py-1 min-h-[18px] text-[7.5px] leading-tight"><strong>เครื่องจักร/อุปกรณ์:</strong> <?php echo h($header['machine_name']); ?></div>
                    <div class="border border-black px-1.5 py-1 min-h-[18px] text-[7.5px] leading-tight"><strong>รหัสเครื่อง:</strong> <?php echo h($header['machine_code']); ?></div>
                    <div class="border border-black px-1.5 py-1 min-h-[18px] text-[7.5px] leading-tight"><strong>ประเภท:</strong> <?php echo h($header['machine_type']); ?></div>
                    <div class="border border-black px-1.5 py-1 min-h-[18px] text-[7.5px] leading-tight"><strong>เลขที่เอกสาร:</strong> <?php echo h($header['doc_no']); ?></div>
                </div>

                <div class="grid grid-cols-[1.2fr_1fr_1fr_0.75fr] gap-[3px] mb-1">
                    <div class="border border-black px-1.5 py-1 min-h-[18px] text-[7.5px] leading-tight col-span-2"><strong>สถานที่:</strong> <?php echo h($header['location']); ?></div>
                    <div class="border border-black px-1.5 py-1 min-h-[18px] text-[7.5px] leading-tight"><strong>Serial No.:</strong> <?php echo h($header['machine_sn']); ?></div>
                    <div class="border border-black px-1.5 py-1 min-h-[18px] text-[7.5px] leading-tight"><strong>Rev:</strong> <?php echo h($header['rev_no']); ?></div>
                </div>

                <div class="flex justify-between items-center text-[8px] my-1 mb-1.5">
                    <div>แสดงวันที่ต้องดำเนินการ PM ตามเงื่อนไข | รวมหัวข้อซ้ำตามชื่อ</div>
                    <div>หน้า <?php echo $page_counter; ?> / <?php echo $total_pages; ?></div>
                </div>

                <table class="w-full border-collapse table-fixed">
                    <thead>
                        <tr>
                            <th rowspan="2" class="border border-black p-0.5 text-[7px] leading-tight align-middle bg-blue-100 font-bold text-center w-[8mm]">ลำดับ</th>
                            <th rowspan="2" class="border border-black p-0.5 text-[7px] leading-tight align-middle bg-blue-100 font-bold text-center w-[62mm]">รายการตรวจสอบ</th>
                            <th rowspan="2" class="border border-black p-0.5 text-[7px] leading-tight align-middle bg-blue-100 font-bold text-center w-[34mm]">มาตรฐาน / ค่าที่ต้องการ</th>
                            <th colspan="<?php echo count($chunk_days); ?>" class="border border-black p-0.5 text-[7px] leading-tight align-middle bg-blue-100 font-bold text-center">วันที่ต้องดำเนินการ PM</th>
                            <th rowspan="2" class="border border-black p-0.5 text-[7px] leading-tight align-middle bg-blue-100 font-bold text-center w-[55mm]">หมายเหตุ</th>
                        </tr>
                        <tr>
                            <?php foreach ($chunk_days as $dayInfo): ?>
                                <th class="text-center border border-black p-0 align-middle h-[60px] bg-blue-100 overflow-hidden <?php echo $dayInfo['is_weekend'] ? '!bg-gray-400' : ''; ?>">
                                    <div class="inline-block transform -rotate-[90deg] whitespace-nowrap">
                                        <div class="font-bold text-[7.5px] leading-tight"><?php echo h($dayInfo['label']); ?></div>
                                    </div>
                                    <div class="pt-2 text-[6px] font-normal leading-tight"><?php echo h($dayInfo['weekday']); ?></div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <tr class="bg-[#bfbfbf] font-bold">
                            <td class="border border-black p-0.5 text-[7px] leading-tight align-middle"></td>
                            <td colspan="<?php echo 3 + count($chunk_days); ?>" class="border border-black p-0.5 text-[7px] leading-tight align-middle">รายการตรวจสอบ / PM CHECK ITEMS</td>
                        </tr>

                        <?php foreach ($chunk_rows as $row): ?>
                            <tr>
                                <td class="border border-black p-0.5 text-[7px] leading-tight align-middle text-center"><?php echo h($row['_row_no']); ?></td>
                                <td class="border border-black p-0.5 text-[7px] leading-tight align-middle"><?php echo h($row['check_point']); ?></td>
                                <td class="border border-black p-0.5 text-[7px] leading-tight align-middle text-center"><?php echo h($row['standard']); ?></td>

                                <?php foreach ($chunk_days as $dayInfo): ?>
                                    <?php
                                        $dayKey = $dayInfo['key'];
                                        $itemsForDay = $row['days'][$dayKey]['items'] ?? [];
                                        $cell = buildCellFromItems($itemsForDay, $show_values_in_cell);

                                        if ($cell['text'] === '' && $show_pending && isset($events_by_day_key[$dayKey])) {
                                            $cell['text'] = 'รอ';
                                            $cell['class'] = 'text-amber-700 bg-orange-50 text-[6px]';
                                        }
                                    ?>
                                    <td class="border border-black p-[1px] h-[15px] text-center text-[8px] overflow-hidden whitespace-nowrap align-middle <?php echo $dayInfo['is_weekend'] ? '!bg-gray-400 ' : ''; ?><?php echo h($cell['class']); ?>">
                                        <?php echo h($cell['text']); ?>
                                    </td>
                                <?php endforeach; ?>

                                <td class="border border-black p-0.5 text-[6.5px] leading-tight align-middle break-words">
                                    <?php echo h(buildRemarkForDays($row, $chunk_days)); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php for ($i = 0; $i < $empty_rows; $i++): ?>
                            <tr>
                                <td class="border border-black p-0.5 text-[7px] leading-tight align-middle text-center">&nbsp;</td>
                                <td class="border border-black p-0.5 text-[7px] leading-tight align-middle">&nbsp;</td>
                                <td class="border border-black p-0.5 text-[7px] leading-tight align-middle text-center">&nbsp;</td>
                                <?php foreach ($chunk_days as $dayInfo): ?>
                                    <td class="border border-black p-[1px] h-[15px] align-middle <?php echo $dayInfo['is_weekend'] ? '!bg-gray-400' : ''; ?>">&nbsp;</td>
                                <?php endforeach; ?>
                                <td class="border border-black p-0.5 text-[7px] leading-tight align-middle">&nbsp;</td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <div>
                <div class="border-x border-b border-black text-center text-[7px] p-[3px] bg-slate-50">
                    / = ปกติหรือดำเนินการแล้ว &nbsp;&nbsp;&nbsp;
                    X = ผิดปกติ &nbsp;&nbsp;&nbsp;
                    - = ไม่เกี่ยวข้อง / ไม่มีข้อมูล
                    <?php if ($show_pending): ?> &nbsp;&nbsp;&nbsp; รอ = มีแผนแต่ยังไม่บันทึกผล <?php endif; ?>
                </div>

                <div class="border-x border-b border-black min-h-[24px] p-1 text-[7px]">
                    <strong>หมายเหตุเพิ่มเติม:</strong> <?php echo nl2br(h($header['remarks'])); ?>
                </div>

                <table class="mt-10 w-full border-collapse table-fixed">
                    <tr>
                        <td class="h-[35px] text-center align-bottom text-[7px] w-[33.33%]">
                            <?php if ($page_counter === $total_pages && !empty($header['signature_path'])): ?>
                                <img src="<?php echo h($header['signature_path']); ?>" class="max-h-[20px] max-w-[110px] object-contain block mx-auto mb-[1px]">
                            <?php else: ?>
                                <div class="border-b border-dotted border-black h-[13px] mx-[20px] mb-[2px]"></div>
                            <?php endif; ?>
                            <div>ผู้ดำเนินการ / ผู้ตรวจสอบ</div>
                            <div><?php echo h($inspector_text); ?></div>
                        </td>
                        <td class="h-[35px] text-center align-bottom text-[7px] w-[33.33%]">
                            <div class="border-b border-dotted border-black h-[13px] mx-[20px] mb-[2px]"></div>
                            <div>หัวหน้างาน / ผู้ทวนสอบ</div>
                            <div>วันที่ ........../........../..........</div>
                        </td>
                        <td class="h-[35px] text-center align-bottom text-[7px] w-[33.33%]">
                            <div class="border-b border-dotted border-black h-[13px] mx-[20px] mb-[2px]"></div>
                            <div>ผู้จัดการอาคาร / ผู้อนุมัติ</div>
                            <div>วันที่ ........../........../..........</div>
                        </td>
                    </tr>
                </table>

                <div class="flex justify-between items-center text-[6.5px] mt-[2px]">
                    <div>PM-DUE-DATES Rev.04</div>
                    <div>
                        แผนทั้งหมด <?php echo $total_plan_count; ?> รายการ |
                        บันทึกแล้ว <?php echo $done_count; ?> รายการ |
                        รอดำเนินการ <?php echo $pending_count; ?> รายการ |
                        หัวข้อทั้งหมด <?php echo count($report_rows); ?> ข้อ
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endforeach; ?>
</div>

<script>
    $('#filterType').on('change', function() {
        const type = $(this).val();
        let html = '';
        const style = 'bg-white border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 block w-full sm:w-auto p-2 outline-none';
        
        const urlParams = new URLSearchParams(window.location.search);
        const dFrom = urlParams.get('date_from');
        const dTo = urlParams.get('date_to');

        if (type === 'daily') {
            const defaultStart = dFrom ? dFrom : moment().subtract(7, 'days').format('YYYY-MM-DD');
            const defaultEnd = dTo ? dTo : moment().format('YYYY-MM-DD');
            html = '<input type="date" id="dateStart" class="' + style + '" value="' + defaultStart + '">' +
                   '<span class="text-slate-400 px-1">-</span>' +
                   '<input type="date" id="dateEnd" class="' + style + '" value="' + defaultEnd + '">';
        } else if (type === 'monthly') {
            const defaultStart = dFrom ? moment(dFrom).format('YYYY-MM') : moment().format('YYYY-MM');
            const defaultEnd = dTo ? moment(dTo).format('YYYY-MM') : moment().format('YYYY-MM');
            html = '<input type="month" id="monthStart" class="' + style + '" value="' + defaultStart + '">' +
                   '<span class="text-slate-400 px-1">-</span>' +
                   '<input type="month" id="monthEnd" class="' + style + '" value="' + defaultEnd + '">';
        } else if (type === 'yearly') {
            const curY = new Date().getFullYear();
            const defaultStart = dFrom ? moment(dFrom).format('YYYY') : curY;
            const defaultEnd = dTo ? moment(dTo).format('YYYY') : curY;
            
            let optsStart = '', optsEnd = '';
            for(let i = curY + 2; i >= curY - 5; i--) {
                optsStart += '<option value="' + i + '" ' + (i == defaultStart ? 'selected' : '') + '>' + i + '</option>';
                optsEnd += '<option value="' + i + '" ' + (i == defaultEnd ? 'selected' : '') + '>' + i + '</option>';
            }
            html = '<select id="yearStart" class="' + style + '">' + optsStart + '</select>' +
                   '<span class="text-slate-400 px-1">-</span>' +
                   '<select id="yearEnd" class="' + style + '">' + optsEnd + '</select>';
        }
        $('#filterInputs').html(html);
    }).trigger('change');
    
        // 1. นำโค้ดโหลดข้อมูลของคุณมาใส่ไว้ในฟังก์ชัน (เพื่อให้เรียกใช้ง่ายขึ้น)
        function applyFilter() {
            const type = $('#filterType').val(); // หาก ID เลือกประเภทคือ filterType (ปรับให้ตรงกับโค้ดจริงของคุณ)
            let dFrom = '', dTo = '';
            
            if (type === 'daily') {
                dFrom = $('#dateStart').val();
                dTo = $('#dateEnd').val();
            } else if (type === 'monthly') {
                dFrom = $('#monthStart').val() + '-01';
                dTo = moment($('#monthEnd').val(), 'YYYY-MM').endOf('month').format('YYYY-MM-DD');
            } else if (type === 'yearly') {
                dFrom = $('#yearStart').val() + '-01-01';
                dTo = $('#yearEnd').val() + '-12-31';
            }
            
            const viewMode = $('#viewMode').val();
            const rows = $('#rowsInput').val();
            const cols = $('#colsInput').val();
            
            let url = new URL(window.location.href);
            url.searchParams.set('date_from', dFrom);
            url.searchParams.set('date_to', dTo);
            url.searchParams.set('view_mode', viewMode);
            url.searchParams.set('rows', rows);
            url.searchParams.set('cols', cols);
            url.searchParams.set('filter_type', type);
            
            // ล้างพารามิเตอร์เก่าๆ ทิ้ง
            url.searchParams.delete('month');
            url.searchParams.delete('year');
            
            window.location.href = url.toString();
        }

        // 2. ผูกปุ่มกด Enter เข้ากับ Input fields ที่ต้องการ
        // ระบุ ID ของช่องกรอกข้อมูลทั้งหมดที่อยากให้กด Enter ได้
        const inputFields = '#rowsInput, #colsInput, #dateStart, #dateEnd, #monthStart, #monthEnd, #yearStart, #yearEnd';
        
        $(inputFields).on('keypress', function(e) {
            // ตรวจสอบว่าปุ่มที่กดคือ Enter (รหัส 13) หรือไม่
            if (e.which === 13) {
                e.preventDefault(); // ป้องกันไม่ให้ฟอร์มโหลดหน้าเว็บใหม่แบบผิดวิธี
                applyFilter();     // เรียกใช้งานฟังก์ชันรีเฟรชค่า
            }
        });

        // 3. (ทางเลือก) หากมีปุ่ม "ค้นหา" หรือ "แสดงผล" ก็สามารถจับ Event Click มาที่ฟังก์ชันเดียวกันได้
        $('#btnSubmit').on('click', function(e) {
            e.preventDefault();
            applyFilter();
        });

    const AUTO_PRINT = new URLSearchParams(window.location.search).get('auto') === '1';
    if (AUTO_PRINT) {
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 500);
        });
    }
</script>

</body>
</html>