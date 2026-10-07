<?php
// =====================================================================
//  handle_meter_info.php  (ปรับปรุงใหม่)
//  - ใช้ Prepared Statement ทุกจุดที่รับค่าจากผู้ใช้ (กัน SQL Injection)
//  - ชื่อตาราง/คอลัมน์มาจาก whitelist เท่านั้น (meter_table/meter_prefix)
//  - ปิด error leak บน production, ไม่ส่ง debug กลับหน้าเว็บ
//  - แยกแต่ละ action เป็นฟังก์ชัน + รวมสูตรคำนวณ usage ไว้ที่เดียว
// =====================================================================

// --- Production-safe error handling: ไม่โชว์ error ออกหน้าจอ แต่ log ไว้ ---
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

include 'config_ctrl/connect.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

date_default_timezone_set('Asia/Bangkok');

if (mysqli_connect_errno()) {
    error_log('DB connect failed: ' . mysqli_connect_error());
    send_json(['success' => false, 'message' => 'เชื่อมต่อฐานข้อมูลไม่สำเร็จ'], 500);
}

// =====================================================================
//  Helpers
// =====================================================================

/** ส่ง JSON กลับแล้วจบการทำงาน (จุดออกเดียวของทุก action) */
function send_json($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** ส่ง error มาตรฐาน */
function send_error($message, $code = 400, $extra = []) {
    send_json(array_merge(['success' => false, 'message' => $message], $extra), $code);
}

/** ประเภทมิเตอร์แบบ simple (อ่านค่าเดียว): น้ำ/ไฟ/แก๊ส */
function meter_is_simple($tab) {
    return in_array($tab, ['wt', 'et', 'gt'], true);
}
/** whitelist ชื่อตารางตามประเภท — เพิ่มประเภทใหม่แก้ที่นี่ที่เดียว */
function meter_table($tab) {
    $map = ['wt' => 'tb_toum_detail_wt', 'et' => 'tb_toum_detail_et', 'gt' => 'tb_toum_detail_gt'];
    return $map[$tab] ?? null;
}
/** whitelist prefix คอลัมน์ตามประเภท */
function meter_prefix($tab) {
    $map = ['wt' => 'toumdt_wt', 'et' => 'toumdt_et', 'gt' => 'toumdt_gt'];
    return $map[$tab] ?? null;
}

/**
 * คำนวณปริมาณการใช้ (usage) รองรับมิเตอร์วนรอบ (rollover)
 * ใช้ร่วมกันทั้งตอนดึงประวัติและตอนเช็คแจ้งเตือน (ลดโค้ดซ้ำ)
 */
function calc_usage($curr, $prev, $is_roll, $max) {
    if ($is_roll == 1 && $curr < $prev) {
        return ($max - $prev) + $curr;
    }
    $u = $curr - $prev;
    return $u < 0 ? 0 : $u;
}

/** SELECT ด้วย prepared statement -> คืน array ของ row (หรือ [] ถ้าไม่มี/พลาด) */
function db_select($connect, $sql, $params = []) {
    $stmt = $connect->prepare($sql);
    if (!$stmt) { error_log('prepare failed: ' . $connect->error . ' | ' . $sql); return []; }
    if (!empty($params)) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $rows = [];
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        if ($res) { while ($r = $res->fetch_assoc()) $rows[] = $r; }
    }
    $stmt->close();
    return $rows;
}

/** INSERT/UPDATE ด้วย prepared statement -> คืน ['ok'=>bool, 'insert_id'=>int, 'error'=>string] */
function db_exec($connect, $sql, $params = []) {
    $stmt = $connect->prepare($sql);
    if (!$stmt) {
        error_log('prepare failed: ' . $connect->error . ' | ' . $sql);
        return ['ok' => false, 'insert_id' => 0, 'error' => 'prepare: ' . $connect->error];
    }
    if (!empty($params)) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $ok = $stmt->execute();
    $err = $ok ? '' : $stmt->error;
    if (!$ok) error_log('execute failed: ' . $stmt->error . ' | ' . $sql);
    $insert_id = $connect->insert_id;
    $stmt->close();
    return ['ok' => $ok, 'insert_id' => $insert_id, 'error' => $err];
}

// =====================================================================
//  Action 1: ดึงข้อมูลทั้งหมด (get_all)  -> คืน JSON array ตรงๆ
// =====================================================================
function action_get_all($connect) {
    $tab   = $_GET['tab']   ?? '';
    $ag_id = $_GET['ag_id'] ?? '';

    if ($tab === '' || $ag_id === '') {
        send_json([]); // frontend คาดหวัง array เสมอ
    }
    if (!meter_is_simple($tab) && $tab !== 'tou') {
        send_json([]); // ประเภทไม่รู้จัก
    }

    $month = (isset($_GET['month']) && $_GET['month'] !== '') ? $_GET['month'] : date('n');
    $year  = (isset($_GET['year'])  && $_GET['year']  !== '') ? $_GET['year']  : date('Y');

    $sql_meter = "
        SELECT
            m.mt_id, m.mt_name, m.mt_type, m.mt_max_val, m.mt_limit_percent,
            a.area_name, c.ac_name, r.ar_name
        FROM tb_meter m
        LEFT JOIN tb_area a ON m.mt_rp_area_id = a.area_id
        LEFT JOIN tb_area_class c ON m.mt_rp_ac_id = c.ac_id
        LEFT JOIN tb_area_room r ON m.mt_rp_ar_id = r.ar_id
        WHERE m.mt_type = ? AND m.mt_status = '0' AND m.mr_ag_id = ?
    ";
    $meter_rows = db_select($connect, $sql_meter, [$tab, $ag_id]);

    $meters = [];
    foreach ($meter_rows as $row_m) {
        $meter_id = $row_m['mt_id'];
        $parts = array_filter([$row_m['area_name'], $row_m['ac_name'], $row_m['ar_name']]);
        $location = implode(' - ', $parts);

        $dynamic_max_val = (!empty($row_m['mt_max_val']) && $row_m['mt_max_val'] > 0) ? (int)$row_m['mt_max_val'] : 1000000;
        $limit_percent = (isset($row_m['mt_limit_percent']) && is_numeric($row_m['mt_limit_percent'])) ? (float)$row_m['mt_limit_percent'] : 20.00;

        $meter_obj = [
            'id' => $meter_id,
            'name' => $row_m['mt_name'],
            'location' => $location,
            'brand' => '-',
            'size' => '-',
            'number' => $meter_id,
            'type' => strtoupper($tab),
            'history' => [],
            'mt_max_val' => $dynamic_max_val,
            'limit_percent' => $limit_percent
        ];

        if (meter_is_simple($tab)) {
            $meter_obj = build_simple_history($connect, $meter_obj, $tab, $meter_id, $ag_id, $month, $year, $dynamic_max_val, $limit_percent);
        } elseif ($tab === 'tou') {
            $meter_obj = build_tou_history($connect, $meter_obj, $meter_id, $ag_id, $month, $year, $dynamic_max_val, $limit_percent);
        }
        $meters[] = $meter_obj;
    }

    send_json(array_values($meters));
}

/** สร้างประวัติสำหรับมิเตอร์ simple (wt/et/gt) */
function build_simple_history($connect, $meter_obj, $tab, $meter_id, $ag_id, $month, $year, $max_meter_val, $limit_percent) {
    $table  = meter_table($tab);
    $prefix = meter_prefix($tab);

    $sql_his = "
        SELECT
            DATE(td.{$prefix}_time) as log_date,
            td.{$prefix}_mt as val,
            td.{$prefix}_rm as note,
            td.{$prefix}_is_rollover as is_rollover,
            td.{$prefix}_round_id as round_id,
            td.{$prefix}_img as img_path,
            u.user_name as recorder_name,
            mr.round_time
        FROM {$table} td
        LEFT JOIN tb_meter_round mr ON td.{$prefix}_round_id = mr.round_id
        LEFT JOIN tb_user u ON td.{$prefix}_user_ins = u.user_id
        WHERE td.{$prefix}_toum_id = ? AND td.{$prefix}_ag_id = ?
          AND MONTH(td.{$prefix}_time) = ? AND YEAR(td.{$prefix}_time) = ?
        ORDER BY DATE(td.{$prefix}_time) ASC, td.{$prefix}_round_id ASC
    ";
    $his_rows = db_select($connect, $sql_his, [$meter_id, $ag_id, $month, $year]);

    // ค่าหน้าปัดสุดท้ายของเดือนก่อนหน้า (ใช้เป็นฐานคำนวณ usage แถวแรก)
    $prev_val = 0;
    $sql_prev = "
        SELECT td.{$prefix}_mt as val
        FROM {$table} td
        WHERE td.{$prefix}_toum_id = ? AND td.{$prefix}_ag_id = ?
          AND (YEAR(td.{$prefix}_time) < ? OR (YEAR(td.{$prefix}_time) = ? AND MONTH(td.{$prefix}_time) < ?))
        ORDER BY DATE(td.{$prefix}_time) DESC, td.{$prefix}_round_id DESC LIMIT 1
    ";
    $prev_rows = db_select($connect, $sql_prev, [$meter_id, $ag_id, $year, $year, $month]);
    if (!empty($prev_rows)) $prev_val = floatval($prev_rows[0]['val']);

    $temp_history = [];
    $prev_usage = null;
    $latest_curr = 0;
    $latest_usage = 0;

    foreach ($his_rows as $row_h) {
        $curr_val = floatval($row_h['val']);
        $is_roll = isset($row_h['is_rollover']) ? (int)$row_h['is_rollover'] : 0;

        $date_parts = explode('-', $row_h['log_date']);
        $formatted_date = $date_parts[2] . '/' . $date_parts[1] . '/' . $date_parts[0];
        $time_display = substr($row_h['round_time'], 0, 5);

        $usage = calc_usage($curr_val, $prev_val, $is_roll, $max_meter_val);

        $percent_diff = 0;
        $is_exceeded = false;
        if ($prev_usage !== null && $prev_usage > 0) {
            $percent_diff = (($usage - $prev_usage) / $prev_usage) * 100;
            if ($percent_diff > $limit_percent) $is_exceeded = true;
        }

        if (!isset($temp_history[$formatted_date])) {
            $temp_history[$formatted_date] = ['date' => $formatted_date, 'round' => []];
        }
        $temp_history[$formatted_date]['round'][] = [
            'round_id' => intval($row_h['round_id']),
            'time' => $time_display,
            'prev' => $prev_val,
            'curr' => $curr_val,
            'usage' => $usage,
            'percent_diff' => $prev_usage !== null ? round($percent_diff, 2) : null,
            'is_exceeded' => $is_exceeded,
            'note' => $row_h['note'],
            'is_rollover' => $is_roll,
            'images' => $row_h['img_path'] ? [$row_h['img_path']] : [],
            'recorder' => $row_h['recorder_name']
        ];

        $latest_curr = $curr_val;
        $latest_usage = $usage;
        $prev_val = $curr_val;
        $prev_usage = $usage;
    }

    $meter_obj['history'] = array_reverse(array_values($temp_history));
    $meter_obj['lastRead'] = number_format($latest_curr, 2);
    $meter_obj['usage'] = number_format($latest_usage, 2);
    return $meter_obj;
}

/** สร้างประวัติสำหรับมิเตอร์ TOU */
function build_tou_history($connect, $meter_obj, $meter_id, $ag_id, $month, $year, $max_tou_val, $limit_percent) {
    $sql_tou = "
        SELECT
            DATE(td.toudt_time) as log_date,
            mr.round_time,
            u.user_name as recorder_name,
            td.toudt_img as img_path,
            td.*
        FROM tb_tou_detail td
        LEFT JOIN tb_meter_round mr ON td.toudt_round_id = mr.round_id
        LEFT JOIN tb_user u ON td.toudt_user_ins = u.user_id
        WHERE td.toudt_tou_id = ? AND td.toudt_ag_id = ?
          AND MONTH(td.toudt_time) = ? AND YEAR(td.toudt_time) = ?
        ORDER BY DATE(td.toudt_time) ASC, td.toudt_round_id ASC
    ";
    $tou_rows = db_select($connect, $sql_tou, [$meter_id, $ag_id, $month, $year]);

    $prev_data = ['toudt_011' => 0, 'toudt_012' => 0];
    $sql_prev = "
        SELECT toudt_011, toudt_012
        FROM tb_tou_detail
        WHERE toudt_tou_id = ? AND toudt_ag_id = ?
          AND (YEAR(toudt_time) < ? OR (YEAR(toudt_time) = ? AND MONTH(toudt_time) < ?))
        ORDER BY DATE(toudt_time) DESC, toudt_round_id DESC LIMIT 1
    ";
    $prev_rows = db_select($connect, $sql_prev, [$meter_id, $ag_id, $year, $year, $month]);
    if (!empty($prev_rows)) {
        $prev_data = [
            'toudt_011' => floatval($prev_rows[0]['toudt_011']),
            'toudt_012' => floatval($prev_rows[0]['toudt_012'])
        ];
    }

    $temp_history = [];
    $prev_total_unit = null;
    $latest_total_val = 0;
    $latest_total_unit = 0;

    foreach ($tou_rows as $row_t) {
        $date_parts = explode('-', $row_t['log_date']);
        $formatted_date = $date_parts[2] . '/' . $date_parts[1] . '/' . $date_parts[0];
        $time_display = substr($row_t['round_time'], 0, 5);

        $val_010 = floatval($row_t['toudt_010']);
        $val_011 = floatval($row_t['toudt_011']);
        $val_012 = floatval($row_t['toudt_012']);
        $is_roll = isset($row_t['toudt_is_rollover']) ? (int)$row_t['toudt_is_rollover'] : 0;

        $prev_011 = $prev_data['toudt_011'] ?? 0;
        $prev_012 = $prev_data['toudt_012'] ?? 0;

        $unit_011 = calc_usage($val_011, $prev_011, $is_roll, $max_tou_val);
        $unit_012 = calc_usage($val_012, $prev_012, $is_roll, $max_tou_val);
        $unit_total = $unit_011 + $unit_012;

        $percent_diff = 0;
        $is_exceeded = false;
        if ($prev_total_unit !== null && $prev_total_unit > 0) {
            $percent_diff = (($unit_total - $prev_total_unit) / $prev_total_unit) * 100;
            if ($percent_diff > $limit_percent) $is_exceeded = true;
        }

        if (!isset($temp_history[$formatted_date])) {
            $temp_history[$formatted_date] = ['date' => $formatted_date, 'round' => []];
        }
        $temp_history[$formatted_date]['round'][] = [
            'round_id' => intval($row_t['toudt_round_id']),
            'time' => $time_display,
            'total_val' => $val_010,
            'total_unit' => $unit_total,
            'percent_diff' => $prev_total_unit !== null ? round($percent_diff, 2) : null,
            'is_exceeded' => $is_exceeded,
            'on_val' => $val_011,
            'on_unit' => $unit_011,
            'off_val' => $val_012,
            'off_unit' => $unit_012,
            'on_peak_demand' => floatval($row_t['toudt_031']),
            'off_peak_demand' => floatval($row_t['toudt_032']),
            'on_reactive' => floatval($row_t['toudt_071']),
            'off_reactive' => floatval($row_t['toudt_072']),
            'note' => $row_t['toudt_note'],
            'is_rollover' => $is_roll,
            'images' => $row_t['toudt_img'] ? [$row_t['toudt_img']] : [],
            'recorder' => $row_t['recorder_name']
        ];

        $latest_total_val = $val_010;
        $latest_total_unit = $unit_total;
        $prev_data = ['toudt_011' => $val_011, 'toudt_012' => $val_012];
        $prev_total_unit = $unit_total;
    }

    $meter_obj['history'] = array_reverse(array_values($temp_history));
    $meter_obj['lastRead'] = number_format($latest_total_val, 2);
    $meter_obj['usage'] = number_format($latest_total_unit, 2);
    return $meter_obj;
}

// =====================================================================
//  Action 2: บันทึก/แก้ไข (save)
// =====================================================================
function action_save($connect) {
    $ag_id     = $_POST['ag_id']     ?? '';
    $mt_id     = $_POST['mt_id']     ?? '';
    $tab       = $_POST['tab']       ?? '';
    $round_id  = $_POST['round_id']  ?? '';
    $log_date  = $_POST['date']      ?? '';
    $note      = $_POST['note']      ?? '';
    $user_id   = $_POST['user_ins']  ?? '';
    $is_rollover = (isset($_POST['is_rollover']) && $_POST['is_rollover'] === '1') ? 1 : 0;

    if ($mt_id === '' || $tab === '' || $round_id === '' || $log_date === '') {
        send_error('ข้อมูลไม่ครบถ้วน', 400);
    }
    if (!meter_is_simple($tab) && $tab !== 'tou') {
        send_error('ประเภทมิเตอร์ไม่ถูกต้อง', 400);
    }
    $log_datetime = $log_date . ' ' . date('H:i:s');

    // --- 1) ตรวจค่าที่กรอกว่าเกินค่าสูงสุดหน้าปัดหรือไม่ ---
    $values_to_check = [];
    if (meter_is_simple($tab)) {
        $values_to_check['ค่ามิเตอร์'] = isset($_POST['curr']) ? (float)$_POST['curr'] : 0;
    } else { // tou
        $values_to_check['Total 010']   = isset($_POST['total_val']) ? (float)$_POST['total_val'] : 0;
        $values_to_check['On Peak 011']  = isset($_POST['on_val'])    ? (float)$_POST['on_val']    : 0;
        $values_to_check['Off Peak 012'] = isset($_POST['off_val'])   ? (float)$_POST['off_val']   : 0;
    }

    $max_val = 1000000;
    $mt_rows = db_select($connect, "SELECT mt_max_val, mt_limit_percent FROM tb_meter WHERE mt_id = ?", [$mt_id]);
    if (!empty($mt_rows) && !empty($mt_rows[0]['mt_max_val']) && $mt_rows[0]['mt_max_val'] > 0) {
        $max_val = floatval($mt_rows[0]['mt_max_val']);
    }
    foreach ($values_to_check as $label => $val) {
        if ($val > $max_val) {
            send_error("ข้อมูล $label ที่กรอก ($val) เกินค่าสูงสุดหน้าปัด ($max_val)", 200);
        }
    }

    // --- 2) หาว่ามีข้อมูลรอบ/วันนั้นอยู่แล้วหรือยัง (insert หรือ update) ---
    if (meter_is_simple($tab)) {
        $table  = meter_table($tab);
        $prefix = meter_prefix($tab);
        $check_sql = "SELECT * FROM {$table} WHERE {$prefix}_toum_id = ? AND {$prefix}_round_id = ? AND DATE({$prefix}_time) = ?";
    } else {
        $table = 'tb_tou_detail';
        $check_sql = "SELECT * FROM {$table} WHERE toudt_tou_id = ? AND toudt_round_id = ? AND DATE(toudt_time) = ?";
    }
    $exist_rows = db_select($connect, $check_sql, [$mt_id, $round_id, $log_date]);
    $is_insert = (count($exist_rows) === 0);
    $old_data  = $is_insert ? null : $exist_rows[0];

    // --- 3) ตรวจสอบค่าย้อนหลัง (เฉพาะโหมดเพิ่มใหม่) ---
    $is_edit_mode = (isset($_POST['mode']) && $_POST['mode'] === 'edit');
    if (!$is_edit_mode) {
        if (meter_is_simple($tab)) {
            validate_simple_rollback($connect, $tab, $mt_id, $ag_id, $log_date, $round_id, $is_rollover);
        } else {
            validate_tou_rollback($connect, $mt_id, $ag_id, $log_datetime, $is_rollover, $is_insert, $old_data);
        }
    }

    // --- 4) จัดการรูปภาพ (มี whitelist นามสกุลกันอัปโหลดไฟล์อันตราย) ---
    $img_path = handle_photo_upload($ag_id, $mt_id, $log_date);

    // --- 5) INSERT / UPDATE ---
    if (meter_is_simple($tab)) {
        $result = save_simple($connect, $tab, $mt_id, $ag_id, $round_id, $log_datetime, $note, $user_id, $is_rollover, $img_path, $is_insert, $old_data);
    } else {
        $result = save_tou($connect, $mt_id, $ag_id, $round_id, $log_datetime, $note, $user_id, $is_rollover, $img_path, $is_insert, $old_data);
    }

    if (!$result['ok']) {
        // TODO: ลบ debug_error ออกหลังแก้ปัญหาเสร็จ
        send_error('ไม่สามารถบันทึกข้อมูลได้', 500, ['debug_error' => $result['error'] ?? '']);
    }

    $response = ['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว'];

    // --- 6) ระบบแจ้งเตือน: usage เกิน % ที่กำหนดหรือไม่ ---
    check_and_notify($connect, $tab, $mt_id, $ag_id, $log_date, $round_id, $is_rollover);

    send_json($response);
}

/** ตรวจค่ามิเตอร์ simple เทียบรอบก่อนหน้า (ถ้าผิดเงื่อนไข rollover จะ exit ทันที) */
function validate_simple_rollback($connect, $tab, $mt_id, $ag_id, $log_date, $round_id, $is_rollover) {
    $table  = meter_table($tab);
    $prefix = meter_prefix($tab);
    $curr_val = (float)($_POST['curr'] ?? 0);

    $sql = "SELECT {$prefix}_mt as val FROM {$table}
            WHERE {$prefix}_toum_id = ? AND {$prefix}_ag_id = ?
              AND (DATE({$prefix}_time) < ? OR (DATE({$prefix}_time) = ? AND {$prefix}_round_id < ?))
            ORDER BY {$prefix}_time DESC, {$prefix}_round_id DESC LIMIT 1";
    $rows = db_select($connect, $sql, [$mt_id, $ag_id, $log_date, $log_date, $round_id]);
    if (empty($rows)) return;

    $prev_val = (float)$rows[0]['val'];
    if ($is_rollover && $curr_val >= $prev_val) {
        send_error('คุณเลือก "มิเตอร์วนรอบ" แต่ค่าปัจจุบันไม่ได้น้อยกว่ารอบก่อนหน้า', 200);
    }
    if (!$is_rollover && $curr_val < $prev_val) {
        send_error('ค่ามิเตอร์น้อยกว่ารอบก่อนหน้า (' . $prev_val . ') หากมิเตอร์วนรอบกรุณาติ๊กช่องยืนยัน', 200);
    }
}

/** ตรวจค่ามิเตอร์ TOU เทียบข้อมูลก่อนหน้า */
function validate_tou_rollback($connect, $mt_id, $ag_id, $log_datetime, $is_rollover, $is_insert, $old_data) {
    $v = [
        'Total 010'   => (float)($_POST['total_val'] ?? 0),
        'On Peak 011' => (float)($_POST['on_val'] ?? 0),
        'Off Peak 012'=> (float)($_POST['off_val'] ?? 0),
        'On Peak 031' => (float)($_POST['on_peak_demand'] ?? 0),
        'Off Peak 032'=> (float)($_POST['off_peak_demand'] ?? 0),
        'On Peak 071' => (float)($_POST['on_reactive'] ?? 0),
        'Off Peak 072'=> (float)($_POST['off_reactive'] ?? 0),
    ];

    $sql = "SELECT toudt_010, toudt_011, toudt_012, toudt_031, toudt_032, toudt_071, toudt_072
            FROM tb_tou_detail
            WHERE toudt_tou_id = ? AND toudt_ag_id = ? AND toudt_time < ?";
    $params = [$mt_id, $ag_id, $log_datetime];
    if (!$is_insert) { $sql .= " AND toudt_id <> ?"; $params[] = $old_data['toudt_id']; }
    $sql .= " ORDER BY toudt_time DESC LIMIT 1";

    $rows = db_select($connect, $sql, $params);
    if (empty($rows)) return;
    $p = $rows[0];

    $map = [
        'Total 010' => 'toudt_010', 'On Peak 011' => 'toudt_011', 'Off Peak 012' => 'toudt_012',
        'On Peak 031' => 'toudt_031', 'Off Peak 032' => 'toudt_032',
        'On Peak 071' => 'toudt_071', 'Off Peak 072' => 'toudt_072',
    ];
    foreach ($map as $label => $col) {
        $prev = (float)$p[$col];
        if (!$is_rollover && $v[$label] < $prev) {
            send_error("ค่า $label ต่ำกว่าข้อมูลก่อนหน้า [ $prev ] หากมิเตอร์วนรอบกรุณาติ๊กช่องยืนยัน", 200);
        }
    }
}

/** อัปโหลดรูป -> คืน path หรือ null (กันไฟล์ที่ไม่ใช่รูป) */
function handle_photo_upload($ag_id, $mt_id, $log_date) {
    if (!isset($_FILES['meter_photo']) || $_FILES['meter_photo']['error'] != 0) return null;

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $file_ext = strtolower(pathinfo($_FILES['meter_photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed, true)) {
        send_error('อนุญาตเฉพาะไฟล์รูปภาพ (jpg, png, gif, webp)', 200);
    }

    $year_folder = date('Y', strtotime($log_date));
    $month_folder = date('m', strtotime($log_date));
    $upload_dir = "upload/{$ag_id}/{$mt_id}/{$year_folder}/{$month_folder}/";
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

    $file_name = 'img-' . date('Ymd-His') . '.' . $file_ext;
    $target_file = $upload_dir . $file_name;
    if (move_uploaded_file($_FILES['meter_photo']['tmp_name'], $target_file)) {
        return $target_file;
    }
    return null;
}

/** บันทึกมิเตอร์ simple */
function save_simple($connect, $tab, $mt_id, $ag_id, $round_id, $log_datetime, $note, $user_id, $is_rollover, $img_path, $is_insert, $old_data) {
    $table  = meter_table($tab);
    $prefix = meter_prefix($tab);
    $val = $_POST['curr'] ?? '0';

    if (!$is_insert) {
        $final_img = ($img_path !== null) ? $img_path : $old_data["{$prefix}_img"];
        $row_id = $old_data["{$prefix}_id"];
        $sql = "UPDATE {$table} SET
                    {$prefix}_mt = ?, {$prefix}_rm = ?, {$prefix}_is_rollover = ?,
                    {$prefix}_img = ?, {$prefix}_user_upd = ?, {$prefix}_upd = NOW()
                WHERE {$prefix}_id = ?";
        return db_exec($connect, $sql, [$val, $note, $is_rollover, $final_img, $user_id, $row_id]);
    }
    $save_img = ($img_path === null) ? '' : $img_path;
    $sql = "INSERT INTO {$table}
                ({$prefix}_toum_id, {$prefix}_ag_id, {$prefix}_round_id, {$prefix}_time, {$prefix}_mt,
                 {$prefix}_rm, {$prefix}_is_rollover, {$prefix}_img, {$prefix}_user_ins, {$prefix}_ins,
                 {$prefix}_user_upd, {$prefix}_upd)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW())";
    return db_exec($connect, $sql, [$mt_id, $ag_id, $round_id, $log_datetime, $val, $note, $is_rollover, $save_img, $user_id, $user_id]);
}

/** บันทึกมิเตอร์ TOU */
function save_tou($connect, $mt_id, $ag_id, $round_id, $log_datetime, $note, $user_id, $is_rollover, $img_path, $is_insert, $old_data) {
    $g = function ($k) { return (isset($_POST[$k]) && $_POST[$k] !== '') ? $_POST[$k] : '0'; };
    $v010 = $g('total_val'); $v011 = $g('on_val'); $v012 = $g('off_val');
    $v031 = $g('on_peak_demand'); $v032 = $g('off_peak_demand');
    $v071 = $g('on_reactive'); $v072 = $g('off_reactive');

    if (!$is_insert) {
        $final_img = ($img_path !== null) ? $img_path : $old_data['toudt_img'];
        $row_id = $old_data['toudt_id'];
        $sql = "UPDATE tb_tou_detail SET
                    toudt_010=?, toudt_011=?, toudt_012=?, toudt_031=?, toudt_032=?, toudt_071=?, toudt_072=?,
                    toudt_note=?, toudt_is_rollover=?, toudt_img=?, toudt_user_upd=?, toudt_upd=NOW()
                WHERE toudt_id=?";
        return db_exec($connect, $sql, [$v010, $v011, $v012, $v031, $v032, $v071, $v072, $note, $is_rollover, $final_img, $user_id, $row_id]);
    }
    $save_img = ($img_path === null) ? '' : $img_path;
    $sql = "INSERT INTO tb_tou_detail
                (toudt_tou_id, toudt_ag_id, toudt_round_id, toudt_time,
                 toudt_010, toudt_011, toudt_012, toudt_031, toudt_032, toudt_071, toudt_072,
                 toudt_note, toudt_is_rollover, toudt_img, toudt_user_ins, toudt_ins, toudt_user_upd, toudt_upd)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW())";
    return db_exec($connect, $sql, [$mt_id, $ag_id, $round_id, $log_datetime, $v010, $v011, $v012, $v031, $v032, $v071, $v072, $note, $is_rollover, $save_img, $user_id, $user_id]);
}

/** เช็ค usage เทียบรอบก่อน ถ้าเกิน limit ให้บันทึกการแจ้งเตือน */
function check_and_notify($connect, $tab, $mt_id, $ag_id, $log_date, $round_id, $is_rollover) {
    $limit_percent = 20.00;
    $max_meter_val = 1000000;
    $mt_rows = db_select($connect, "SELECT mt_max_val, mt_limit_percent FROM tb_meter WHERE mt_id = ?", [$mt_id]);
    if (!empty($mt_rows)) {
        if (!empty($mt_rows[0]['mt_limit_percent'])) $limit_percent = (float)$mt_rows[0]['mt_limit_percent'];
        if (!empty($mt_rows[0]['mt_max_val']) && (float)$mt_rows[0]['mt_max_val'] > 0) $max_meter_val = (float)$mt_rows[0]['mt_max_val'];
    }

    $is_exceeded = false;

    if (meter_is_simple($tab)) {
        $table  = meter_table($tab);
        $prefix = meter_prefix($tab);
        $curr_val = (float)($_POST['curr'] ?? 0);

        $sql = "SELECT {$prefix}_mt as val, {$prefix}_is_rollover as is_roll
                FROM {$table}
                WHERE {$prefix}_toum_id = ? AND {$prefix}_ag_id = ?
                  AND (DATE({$prefix}_time) < ? OR (DATE({$prefix}_time) = ? AND {$prefix}_round_id < ?))
                ORDER BY {$prefix}_time DESC, {$prefix}_round_id DESC LIMIT 2";
        $history = db_select($connect, $sql, [$mt_id, $ag_id, $log_date, $log_date, $round_id]);

        if (count($history) >= 1) {
            $val_prev1 = (float)$history[0]['val'];
            $curr_usage = calc_usage($curr_val, $val_prev1, $is_rollover, $max_meter_val);

            if (count($history) === 2) {
                $val_prev2 = (float)$history[1]['val'];
                $roll_prev1 = (int)$history[0]['is_roll'];
                $prev_usage = calc_usage($val_prev1, $val_prev2, $roll_prev1, $max_meter_val);
                if ($prev_usage > 0) {
                    $percent_diff = (($curr_usage - $prev_usage) / $prev_usage) * 100;
                    if ($percent_diff > $limit_percent) $is_exceeded = true;
                }
            }
        }
    } else { // tou
        $curr_011 = (float)($_POST['on_val'] ?? 0);
        $curr_012 = (float)($_POST['off_val'] ?? 0);

        $sql = "SELECT toudt_011, toudt_012, toudt_is_rollover as is_roll
                FROM tb_tou_detail
                WHERE toudt_tou_id = ? AND toudt_ag_id = ?
                  AND (DATE(toudt_time) < ? OR (DATE(toudt_time) = ? AND toudt_round_id < ?))
                ORDER BY toudt_time DESC, toudt_round_id DESC LIMIT 2";
        $history = db_select($connect, $sql, [$mt_id, $ag_id, $log_date, $log_date, $round_id]);

        if (count($history) >= 1) {
            $prev1_011 = (float)$history[0]['toudt_011'];
            $prev1_012 = (float)$history[0]['toudt_012'];
            $curr_total_unit = calc_usage($curr_011, $prev1_011, $is_rollover, $max_meter_val)
                             + calc_usage($curr_012, $prev1_012, $is_rollover, $max_meter_val);

            if (count($history) === 2) {
                $prev2_011 = (float)$history[1]['toudt_011'];
                $prev2_012 = (float)$history[1]['toudt_012'];
                $roll_prev1 = (int)$history[0]['is_roll'];
                $prev_total_unit = calc_usage($prev1_011, $prev2_011, $roll_prev1, $max_meter_val)
                                 + calc_usage($prev1_012, $prev2_012, $roll_prev1, $max_meter_val);
                if ($prev_total_unit > 0) {
                    $percent_diff = (($curr_total_unit - $prev_total_unit) / $prev_total_unit) * 100;
                    if ($percent_diff > $limit_percent) $is_exceeded = true;
                }
            }
        }
    }

    if ($is_exceeded) {
        $date_parts = explode('-', $log_date);
        $issue_year = $date_parts[0];
        $issue_month = (int)$date_parts[1];
        $rp_format = "tab={$tab}&month={$issue_month}&year={$issue_year}";
        db_exec(
            $connect,
            "INSERT INTO `notifications` (`type`, `related_id`, `rp_format`, `created_at`) VALUES ('meter_exceeded', ?, ?, current_timestamp())",
            [$mt_id, $rp_format]
        );
    }
}

// =====================================================================
//  Action 3: ตรวจสอบข้อมูลซ้ำ (check_duplicate)
// =====================================================================
function action_check_duplicate($connect) {
    $meter_id = $_POST['meter_id'] ?? '';
    $round_id = $_POST['round_id'] ?? '';
    $date     = $_POST['date'] ?? '';
    $type     = strtolower($_POST['type'] ?? '');

    if (meter_is_simple($type)) {
        $table  = meter_table($type);
        $prefix = meter_prefix($type);
        $sql = "SELECT {$prefix}_id as id FROM {$table} WHERE {$prefix}_toum_id = ? AND {$prefix}_round_id = ? AND DATE({$prefix}_time) = ?";
    } else {
        $sql = "SELECT toudt_id as id FROM tb_tou_detail WHERE toudt_tou_id = ? AND toudt_round_id = ? AND DATE(toudt_time) = ?";
    }

    $rows = db_select($connect, $sql, [$meter_id, $round_id, $date]);
    if (!empty($rows)) {
        send_json(['exists' => true, 'id' => $rows[0]['id']]);
    }
    send_json(['exists' => false]);
}

// =====================================================================
//  Router
// =====================================================================
$action = $_GET['action'] ?? null;
switch ($action) {
    case 'get_all':         action_get_all($connect); break;
    case 'save':            action_save($connect); break;
    case 'check_duplicate': action_check_duplicate($connect); break;
    default:                send_error('ไม่ระบุการดำเนินการ (action)', 400);
}
