<?php
// แสดงข้อผิดพลาด PHP เพื่อการตรวจสอบ
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config_ctrl/connect.php'; 

if (mysqli_connect_errno()) {
    http_response_code(500); 
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type"); 

date_default_timezone_set('Asia/Bangkok'); 

$action = isset($_GET['action']) ? $_GET['action'] : null; 
$response = ['success' => false, 'data' => []];

if (!$action) {
    http_response_code(400);
    echo json_encode(['error' => 'ไม่ระบุการดำเนินการ (action)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ----------------------------------------------------------------
// ✅ 1. ดึงข้อมูลทั้งหมด (GET ALL)
// ----------------------------------------------------------------
if ($action === 'get_all') {
    if (isset($_GET['tab']) && isset($_GET['ag_id'])) {
        $tab = mysqli_real_escape_string($connect, $_GET['tab']);
        $ag_id = mysqli_real_escape_string($connect, $_GET['ag_id']);
        
        $month = isset($_GET['month']) && $_GET['month'] !== '' ? mysqli_real_escape_string($connect, $_GET['month']) : date('n');
        $year  = isset($_GET['year']) && $_GET['year'] !== '' ? mysqli_real_escape_string($connect, $_GET['year']) : date('Y');

        $sql_meter = "
            SELECT 
                m.mt_id, m.mt_name, m.mt_type, m.mt_max_val, m.mt_limit_percent,
                a.area_name, c.ac_name, r.ar_name
            FROM tb_meter m
            LEFT JOIN tb_area a ON m.mt_rp_area_id = a.area_id
            LEFT JOIN tb_area_class c ON m.mt_rp_ac_id = c.ac_id
            LEFT JOIN tb_area_room r ON m.mt_rp_ar_id = r.ar_id
            WHERE m.mt_type = '$tab' 
            AND m.mt_status = '0' 
            AND m.mr_ag_id = '$ag_id'
        ";

        $result_meter = $connect->query($sql_meter);
        $meters = [];

        if ($result_meter) {
            while ($row_m = $result_meter->fetch_assoc()) {
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

                if ($tab === 'wt' || $tab === 'et') {
                    $table_name = ($tab === 'wt') ? 'tb_toum_detail_wt' : 'tb_toum_detail_et';
                    $prefix = ($tab === 'wt') ? 'toumdt_wt' : 'toumdt_et';
                    $max_meter_val = $dynamic_max_val; 
                    
                    // ==========================================
                    // ดึงประวัติเดือนที่เลือก
                    // ==========================================
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
                        FROM {$table_name} td
                        LEFT JOIN tb_meter_round mr ON td.{$prefix}_round_id = mr.round_id
                        LEFT JOIN tb_user u ON td.{$prefix}_user_ins = u.user_id
                        WHERE td.{$prefix}_toum_id = '$meter_id' 
                        AND td.{$prefix}_ag_id = '$ag_id'
                    ";

                    if (!empty($month) && !empty($year)) {
                        $sql_his .= " AND MONTH(td.{$prefix}_time) = '$month' AND YEAR(td.{$prefix}_time) = '$year'";
                    }
                    $sql_his .= " ORDER BY DATE(td.{$prefix}_time) ASC, td.{$prefix}_round_id ASC";
                    
                    $res_his = $connect->query($sql_his);
                    $temp_history = [];
                    $prev_val = 0;
                    $prev_usage = null; // 🎯 ตัวแปรเก็บ Usage ของรอบที่แล้วเพื่อนำมาคำนวณ %
                    $latest_curr = 0;
                    $latest_usage = 0;

                    if (!empty($month) && !empty($year)) {
                        $sql_prev_val = "
                            SELECT td.{$prefix}_mt as val 
                            FROM {$table_name} td 
                            WHERE td.{$prefix}_toum_id = '$meter_id' 
                            AND td.{$prefix}_ag_id = '$ag_id' 
                            AND (YEAR(td.{$prefix}_time) < '$year' OR (YEAR(td.{$prefix}_time) = '$year' AND MONTH(td.{$prefix}_time) < '$month'))
                            ORDER BY DATE(td.{$prefix}_time) DESC, td.{$prefix}_round_id DESC LIMIT 1
                        ";
                        $res_prev_val = $connect->query($sql_prev_val);
                        if ($res_prev_val && $row_prev = $res_prev_val->fetch_assoc()) {
                            $prev_val = floatval($row_prev['val']);
                        }
                    }

                    if ($res_his) {
                        while ($row_h = $res_his->fetch_assoc()) {
                            $curr_val = floatval($row_h['val']);
                            $is_roll = isset($row_h['is_rollover']) ? (int)$row_h['is_rollover'] : 0;
                            
                            $date_parts = explode('-', $row_h['log_date']);
                            $formatted_date = $date_parts[2].'/'.$date_parts[1].'/'.$date_parts[0];
                            $time_display = substr($row_h['round_time'], 0, 5);
                            
                            if ($is_roll == 1 && $curr_val < $prev_val) {
                                $usage = ($max_meter_val - $prev_val) + $curr_val;
                            } else {
                                $usage = $curr_val - $prev_val;
                                if ($usage < 0) $usage = 0;
                            }

                            // 🎯 คำนวณเปอร์เซ็นต์ส่วนต่าง (% Diff)
                            $percent_diff = 0;
                            $is_exceeded = false;
                            
                            if ($prev_usage !== null && $prev_usage > 0) {
                                $percent_diff = (($usage - $prev_usage) / $prev_usage) * 100;
                                // ตรวจสอบว่าเกิน 20% หรือไม่
                                if ($percent_diff > $limit_percent) {
                                    $is_exceeded = true;
                                }
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
                                'percent_diff' => $prev_usage !== null ? round($percent_diff, 2) : null, // ส่งค่า % ไปให้ Frontend
                                'is_exceeded' => $is_exceeded, // ส่งสถานะว่าเกินหรือไม่
                                'note' => $row_h['note'],
                                'is_rollover' => $is_roll,
                                'images' => $row_h['img_path'] ? [$row_h['img_path']] : [],
                                'recorder' => $row_h['recorder_name']
                            ];
                            
                            $latest_curr = $curr_val;
                            $latest_usage = $usage;
                            $prev_val = $curr_val;
                            $prev_usage = $usage; // 🎯 เก็บ Usage ปัจจุบันไว้ใช้เป็นฐานคำนวณในรอบถัดไป
                        }
                    }
                    $meter_obj['history'] = array_reverse(array_values($temp_history));
                    $meter_obj['lastRead'] = number_format($latest_curr, 2);
                    $meter_obj['usage'] = number_format($latest_usage, 2);

                } elseif ($tab === 'tou') {
                    $max_tou_val = $dynamic_max_val; 
                    
                    // ==========================================
                    // ดึงประวัติเดือนที่เลือก
                    // ==========================================
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
                        WHERE td.toudt_tou_id = '$meter_id'
                        AND td.toudt_ag_id = '$ag_id'
                    ";
                    
                    if (!empty($month) && !empty($year)) {
                        $sql_tou .= " AND MONTH(td.toudt_time) = '$month' AND YEAR(td.toudt_time) = '$year'";
                    }
                    $sql_tou .= " ORDER BY DATE(td.toudt_time) ASC, td.toudt_round_id ASC";

                    $res_tou = $connect->query($sql_tou);
                    $temp_history = [];
                    $prev_data = [];
                    $prev_total_unit = null; // 🎯 ตัวแปรเก็บ Total Unit ของรอบที่แล้วเพื่อหา % Diff
                    $latest_total_val = 0;
                    $latest_total_unit = 0;
                    
                    if (!empty($month) && !empty($year)) {
                        $sql_prev_tou = "
                            SELECT toudt_011, toudt_012 
                            FROM tb_tou_detail 
                            WHERE toudt_tou_id = '$meter_id' 
                            AND toudt_ag_id = '$ag_id' 
                            AND (YEAR(toudt_time) < '$year' OR (YEAR(toudt_time) = '$year' AND MONTH(toudt_time) < '$month'))
                            ORDER BY DATE(toudt_time) DESC, toudt_round_id DESC LIMIT 1
                        ";
                        $res_prev_tou = $connect->query($sql_prev_tou);
                        if ($res_prev_tou && $row_prev = $res_prev_tou->fetch_assoc()) {
                            $prev_data = [
                                'toudt_011' => floatval($row_prev['toudt_011']), 
                                'toudt_012' => floatval($row_prev['toudt_012'])
                            ];
                        }
                    }

                    if ($res_tou) {
                        while ($row_t = $res_tou->fetch_assoc()) {
                            $date_parts = explode('-', $row_t['log_date']);
                            $formatted_date = $date_parts[2].'/'.$date_parts[1].'/'.$date_parts[0];
                            $time_display = substr($row_t['round_time'], 0, 5);

                            $val_010 = floatval($row_t['toudt_010']);
                            $val_011 = floatval($row_t['toudt_011']);
                            $val_012 = floatval($row_t['toudt_012']);
                            $is_roll = isset($row_t['toudt_is_rollover']) ? (int)$row_t['toudt_is_rollover'] : 0;

                            $prev_011 = isset($prev_data['toudt_011']) ? $prev_data['toudt_011'] : 0;
                            $prev_012 = isset($prev_data['toudt_012']) ? $prev_data['toudt_012'] : 0;

                            $unit_011 = $val_011 - $prev_011;
                            $unit_012 = $val_012 - $prev_012;
                            
                            if ($is_roll == 1) {
                                if ($val_011 < $prev_011) $unit_011 = ($max_tou_val - $prev_011) + $val_011;
                                if ($val_012 < $prev_012) $unit_012 = ($max_tou_val - $prev_012) + $val_012;
                            }

                            $unit_011 = $unit_011 < 0 ? 0 : $unit_011;
                            $unit_012 = $unit_012 < 0 ? 0 : $unit_012;
                            $unit_total = $unit_011 + $unit_012;

                            // 🎯 คำนวณเปอร์เซ็นต์ส่วนต่างของ TOU
                            $percent_diff = 0;
                            $is_exceeded = false;
                            
                            if ($prev_total_unit !== null && $prev_total_unit > 0) {
                                $percent_diff = (($unit_total - $prev_total_unit) / $prev_total_unit) * 100;
                                if ($percent_diff > $limit_percent) {
                                    $is_exceeded = true;
                                }
                            }

                            if (!isset($temp_history[$formatted_date])) {
                                $temp_history[$formatted_date] = ['date' => $formatted_date, 'round' => []];
                            }

                            $temp_history[$formatted_date]['round'][] = [
                                'round_id' => intval($row_t['toudt_round_id']),
                                'time' => $time_display,
                                'total_val' => $val_010,
                                'total_unit' => $unit_total,
                                'percent_diff' => $prev_total_unit !== null ? round($percent_diff, 2) : null, // ส่ง % กลับไป
                                'is_exceeded' => $is_exceeded, // ส่งสถานะสีแดงกลับไป
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
                            $prev_total_unit = $unit_total; // 🎯 เก็บ Total unit ไปใช้เทียบรอบถัดไป
                        }
                    }
                    $meter_obj['history'] = array_reverse(array_values($temp_history));
                    $meter_obj['lastRead'] = number_format($latest_total_val, 2);
                    $meter_obj['usage'] = number_format($latest_total_unit, 2);
                }
                $meters[] = $meter_obj;
            }
            $response = array_values($meters);
        }
    }
}

// ----------------------------------------------------------------
// ✅ 2. บันทึก หรือ แก้ไขข้อมูล (ADD & UPDATE)
// ----------------------------------------------------------------
elseif ($action === 'save') {
    $ag_id = mysqli_real_escape_string($connect, $_POST['ag_id']);
    $mt_id = mysqli_real_escape_string($connect, $_POST['mt_id']);
    $tab = mysqli_real_escape_string($connect, $_POST['tab']);
    $round_id = mysqli_real_escape_string($connect, $_POST['round_id']);
    $log_date = mysqli_real_escape_string($connect, $_POST['date']); 
    $note = mysqli_real_escape_string($connect, $_POST['note']);
    $user_id = mysqli_real_escape_string($connect, $_POST['user_ins']);
    
    // รับค่า is_rollover (สมมติถ้าไม่ได้ส่งมาหรือไม่ได้ติ๊ก ให้เป็น 0)
    $is_rollover = (isset($_POST['is_rollover']) && $_POST['is_rollover'] === '1') ? 1 : 0;

    // --- 🛠 1. จัดเตรียมค่าที่ผู้ใช้กรอกเข้ามาเพื่อนำไปตรวจสอบ ---
    $values_to_check = [];
    if ($tab === 'wt' || $tab === 'et') {
        $curr_val = isset($_POST['curr']) ? (float)$_POST['curr'] : 0;
        $values_to_check['ค่ามิเตอร์'] = $curr_val;
    } elseif ($tab === 'tou') {
        // สำหรับ TOU ดึงค่าหลักๆ มาตรวจสอบว่าเกินหน้าปัดหรือไม่
        $values_to_check['Total 010'] = isset($_POST['total_val']) ? (float)$_POST['total_val'] : 0;
        $values_to_check['On Peak 011'] = isset($_POST['on_val']) ? (float)$_POST['on_val'] : 0;
        $values_to_check['Off Peak 012'] = isset($_POST['off_val']) ? (float)$_POST['off_val'] : 0;
    }

    // --- 🛠 2. ดึงค่า max_val จากฐานข้อมูลและทำการตรวจสอบ ---
    $sql_check_max = "SELECT mt_max_val FROM tb_meter WHERE mt_id = '$mt_id'"; // แก้ไขเป็น $mt_id
    $res_check_max = $connect->query($sql_check_max);
    
    if ($res_check_max && $row_max = $res_check_max->fetch_assoc()) {
        // ดึงค่า max_val ออกมา ถ้าไม่มีหรือเป็น 0 ให้ใช้ค่า default เป็น 1000000
        $max_val = (!empty($row_max['mt_max_val']) && $row_max['mt_max_val'] > 0) ? floatval($row_max['mt_max_val']) : 1000000;
        
        // วนลูปเช็คค่าที่กรอกมาว่ามีช่องไหนเกิน max_val หรือไม่
        foreach ($values_to_check as $label => $val) {
            if ($val > $max_val) {
                echo json_encode([
                    'success' => false, 
                    'message' => "ข้อมูล $label ที่กรอก ($val) เกินค่าสูงสุดหน้าปัด ($max_val)"
                ], JSON_UNESCAPED_UNICODE);
                exit; // จบการทำงาน ไม่บันทึกข้อมูล
            }
        }
    }
    
    // ตรวจสอบเบื้องต้นว่ามีข้อมูลเดิมในรอบ/วันนั้นหรือไม่
    if ($tab === 'wt' || $tab === 'et') {
        $table = ($tab === 'wt') ? 'tb_toum_detail_wt' : 'tb_toum_detail_et';
        $prefix = ($tab === 'wt') ? 'toumdt_wt' : 'toumdt_et';
        $check_sql = "SELECT * FROM $table WHERE {$prefix}_toum_id = '$mt_id' AND {$prefix}_round_id = '$round_id' AND DATE({$prefix}_time) = '$log_date'";
    } else {
        $table = 'tb_tou_detail';
        $check_sql = "SELECT * FROM $table WHERE toudt_tou_id = '$mt_id' AND toudt_round_id = '$round_id' AND DATE(toudt_time) = '$log_date'";
    }
    
    $check_res = $connect->query($check_sql);
    $is_insert = ($check_res->num_rows === 0);
    $old_data = (!$is_insert) ? $check_res->fetch_assoc() : null;

    // --- 🛠 ปรับปรุงส่วนการตรวจสอบข้อมูลย้อนหลัง (Validation) ---
    $is_edit_mode = (isset($_POST['mode']) && $_POST['mode'] === 'edit');

    if (!$is_edit_mode) {
        if ($tab === 'wt' || $tab === 'et') {
            $curr_val = (float)$_POST['curr'];
            $sql_prev = "SELECT {$prefix}_mt FROM $table WHERE {$prefix}_toum_id = '$mt_id' AND {$prefix}_ag_id = '$ag_id' 
                        AND (DATE({$prefix}_time) < '$log_date' OR (DATE({$prefix}_time) = '$log_date' AND {$prefix}_round_id < '$round_id'))
                        ORDER BY {$prefix}_time DESC, {$prefix}_round_id DESC LIMIT 1";
            $res_prev = $connect->query($sql_prev);
            
            if ($res_prev && $row_prev = $res_prev->fetch_assoc()) {
                $prev_val = (float)$row_prev["{$prefix}_mt"];
                
                if ($is_rollover && $curr_val >= $prev_val) {
                    echo json_encode(['success' => false, 'message' => 'คุณเลือก "มิเตอร์วนรอบ" แต่ค่าปัจจุบันไม่ได้น้อยกว่ารอบก่อนหน้า'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (!$is_rollover && $curr_val < $prev_val) {
                    echo json_encode(['success' => false, 'message' => 'ค่ามิเตอร์น้อยกว่ารอบก่อนหน้า ('. $prev_val .') หากมิเตอร์วนรอบกรุณาติ๊กช่องยืนยัน'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
        } 
        elseif ($tab === 'tou') {
            $v010 = (float)$_POST['total_val']; 
            $v011 = (float)$_POST['on_val'];    
            $v012 = (float)$_POST['off_val'];   
            $v031 = (float)$_POST['on_peak_demand'];  
            $v032 = (float)$_POST['off_peak_demand']; 
            $v071 = (float)$_POST['on_reactive'];     
            $v072 = (float)$_POST['off_reactive'];    

            $prev_sql = "SELECT toudt_010, toudt_011, toudt_012, toudt_031, toudt_032, toudt_071, toudt_072 
                        FROM tb_tou_detail 
                        WHERE toudt_tou_id = '$mt_id' AND toudt_ag_id = '$ag_id' 
                        AND toudt_time < '$log_date " . date('H:i:s') . "' ";
            
            if (!$is_insert) { 
                $prev_sql .= " AND toudt_id <> '".$old_data['toudt_id']."' "; 
            }
            $prev_sql .= " ORDER BY toudt_time DESC LIMIT 1";
            
            $res_prev = $connect->query($prev_sql);
            if ($res_prev && $p = $res_prev->fetch_assoc()) {
                $checks = [
                    'Total 010' => [$v010, (float)$p['toudt_010']],
                    'On Peak 011' => [$v011, (float)$p['toudt_011']],
                    'Off Peak 012' => [$v012, (float)$p['toudt_012']],
                    'On Peak 031' => [$v031, (float)$p['toudt_031']],
                    'Off Peak 032' => [$v032, (float)$p['toudt_032']],
                    'On Peak 071' => [$v071, (float)$p['toudt_071']],
                    'Off Peak 072' => [$v072, (float)$p['toudt_072']]
                ];

                foreach ($checks as $label => $values) {
                    if (!$is_rollover && $values[0] < $values[1]) {
                        echo json_encode([
                            'success' => false, 
                            'message' => "ค่า $label ต่ำกว่าข้อมูลก่อนหน้า [ $values[1] ] หากมิเตอร์วนรอบกรุณาติ๊กช่องยืนยัน"
                        ], JSON_UNESCAPED_UNICODE);
                        exit;
                    }
                }
            }
        }
    }

    // --- จัดการรูปภาพ ---
    $img_path = null; 
    if (isset($_FILES['meter_photo']) && $_FILES['meter_photo']['error'] == 0) {
        $year_folder = date('Y', strtotime($log_date));
        $month_folder = date('m', strtotime($log_date));
        $upload_dir = "upload/{$ag_id}/{$mt_id}/{$year_folder}/{$month_folder}/";
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
        $file_ext = pathinfo($_FILES['meter_photo']['name'], PATHINFO_EXTENSION);
        $file_name = "img-" . date('Ymd-His') . "." . $file_ext;
        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['meter_photo']['tmp_name'], $target_file)) { $img_path = $target_file; }
    }

    // --- ดำเนินการ INSERT หรือ UPDATE ---
    if ($tab === 'wt' || $tab === 'et') {
        $val = mysqli_real_escape_string($connect, $_POST['curr']);
        if (!$is_insert) {
            $final_img = ($img_path !== null) ? $img_path : $old_data["{$prefix}_img"];
            $row_id = $old_data["{$prefix}_id"];
            // เพิ่มการอัปเดตสถานะ is_rollover ลงไป
            $sql = "UPDATE $table SET {$prefix}_mt = '$val', {$prefix}_rm = '$note', {$prefix}_is_rollover = '$is_rollover', {$prefix}_img = '$final_img', {$prefix}_user_upd = '$user_id', {$prefix}_upd = NOW() WHERE {$prefix}_id = '$row_id'";
        } else {
            $save_img = ($img_path === null) ? "" : $img_path;
            // เพิ่มสถานะ is_rollover ลงไปตอน Insert
            $sql = "INSERT INTO $table ({$prefix}_toum_id, {$prefix}_ag_id, {$prefix}_round_id, {$prefix}_time, {$prefix}_mt, {$prefix}_rm, {$prefix}_is_rollover, {$prefix}_img, {$prefix}_user_ins, {$prefix}_ins, {$prefix}_user_upd, {$prefix}_upd) 
                    VALUES ('$mt_id', '$ag_id', '$round_id', '$log_date " . date('H:i:s') . "', '$val', '$note', '$is_rollover', '$save_img', '$user_id', NOW(), '$user_id', NOW())";
        }
    } elseif ($tab === 'tou') {
        $v010 = (isset($_POST['total_val']) && $_POST['total_val'] !== '') ? mysqli_real_escape_string($connect, $_POST['total_val']) : '0';
        $v011 = (isset($_POST['on_val']) && $_POST['on_val'] !== '') ? mysqli_real_escape_string($connect, $_POST['on_val']) : '0';
        $v012 = (isset($_POST['off_val']) && $_POST['off_val'] !== '') ? mysqli_real_escape_string($connect, $_POST['off_val']) : '0';
        $v031 = (isset($_POST['on_peak_demand']) && $_POST['on_peak_demand'] !== '') ? mysqli_real_escape_string($connect, $_POST['on_peak_demand']) : '0';
        $v032 = (isset($_POST['off_peak_demand']) && $_POST['off_peak_demand'] !== '') ? mysqli_real_escape_string($connect, $_POST['off_peak_demand']) : '0';
        $v071 = (isset($_POST['on_reactive']) && $_POST['on_reactive'] !== '') ? mysqli_real_escape_string($connect, $_POST['on_reactive']) : '0';
        $v072 = (isset($_POST['off_reactive']) && $_POST['off_reactive'] !== '') ? mysqli_real_escape_string($connect, $_POST['off_reactive']) : '0';

        if (!$is_insert) {
            $final_img = ($img_path !== null) ? $img_path : $old_data['toudt_img'];
            $row_id = $old_data['toudt_id'];

            $sql = "UPDATE tb_tou_detail SET 
                        toudt_010='$v010', 
                        toudt_011='$v011', 
                        toudt_012='$v012', 
                        toudt_031='$v031', 
                        toudt_032='$v032', 
                        toudt_071='$v071', 
                        toudt_072='$v072', 
                        toudt_note='$note', 
                        toudt_is_rollover='$is_rollover',
                        toudt_img='$final_img', 
                        toudt_user_upd='$user_id', 
                        toudt_upd=NOW() 
                    WHERE toudt_id='$row_id'";
        } else {
            $save_img = ($img_path === null) ? "" : $img_path;

            $sql = "INSERT INTO tb_tou_detail (
                        toudt_tou_id, toudt_ag_id, toudt_round_id, toudt_time, 
                        toudt_010, toudt_011, toudt_012, 
                        toudt_031, toudt_032, toudt_071, toudt_072, 
                        toudt_note, toudt_is_rollover, toudt_img, toudt_user_ins, toudt_ins, toudt_user_upd, toudt_upd
                    ) 
                    VALUES (
                        '$mt_id', '$ag_id', '$round_id', '$log_date " . date('H:i:s') . "', 
                        '$v010', '$v011', '$v012', 
                        '$v031', '$v032', '$v071', '$v072', 
                        '$note', '$is_rollover', '$save_img', '$user_id', NOW(), '$user_id', NOW()
                    )";
        }
    }

    if (isset($sql) && $connect->query($sql)) {
        $response = ['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว'];

        // =======================================================================
        // 📍 ระบบแจ้งเตือน (ตรวจสอบว่าปริมาณ "การใช้งาน (Usage)" เกิน % ที่กำหนดหรือไม่)
        // =======================================================================
        $is_exceeded = false;
        $limit_percent = 20.00; 
        $max_meter_val = 1000000;
        $percent_diff = 0; // ตัวแปรเก็บไว้เช็ก Debug
        
        $sql_mt = "SELECT mt_max_val, mt_limit_percent FROM tb_meter WHERE mt_id = '$mt_id'";
        $res_mt = $connect->query($sql_mt);
        if ($res_mt && $row_mt = $res_mt->fetch_assoc()) {
            if (!empty($row_mt['mt_limit_percent'])) $limit_percent = (float)$row_mt['mt_limit_percent'];
            if (!empty($row_mt['mt_max_val']) && (float)$row_mt['mt_max_val'] > 0) $max_meter_val = (float)$row_mt['mt_max_val'];
        }

        if ($tab === 'wt' || $tab === 'et') {
            $prefix = ($tab === 'wt') ? 'toumdt_wt' : 'toumdt_et';
            $table_noti = ($tab === 'wt') ? 'tb_toum_detail_wt' : 'tb_toum_detail_et';
            $curr_val = isset($_POST['curr']) ? (float)$_POST['curr'] : 0;

            $sql_prevs = "SELECT {$prefix}_mt as val, {$prefix}_is_rollover as is_roll 
                          FROM $table_noti 
                          WHERE {$prefix}_toum_id = '$mt_id' AND {$prefix}_ag_id = '$ag_id' 
                          AND (DATE({$prefix}_time) < '$log_date' OR (DATE({$prefix}_time) = '$log_date' AND {$prefix}_round_id < '$round_id'))
                          ORDER BY {$prefix}_time DESC, {$prefix}_round_id DESC LIMIT 2";
            
            $res_prevs = $connect->query($sql_prevs);
            $history = [];
            if($res_prevs) {
                while ($row_p = $res_prevs->fetch_assoc()) {
                    $history[] = $row_p; 
                }
            }

            if (count($history) > 0) {
                $val_prev1 = (float)$history[0]['val'];
                
                if ($is_rollover == 1 && $curr_val < $val_prev1) {
                    $curr_usage = ($max_meter_val - $val_prev1) + $curr_val;
                } else {
                    $curr_usage = max(0, $curr_val - $val_prev1);
                }

                if (count($history) == 2) {
                    $val_prev2 = (float)$history[1]['val'];
                    $roll_prev1 = (int)$history[0]['is_roll'];

                    if ($roll_prev1 == 1 && $val_prev1 < $val_prev2) {
                        $prev_usage = ($max_meter_val - $val_prev2) + $val_prev1;
                    } else {
                        $prev_usage = max(0, $val_prev1 - $val_prev2);
                    }

                    if ($prev_usage > 0) {
                        $percent_diff = (($curr_usage - $prev_usage) / $prev_usage) * 100;
                        if ($percent_diff > $limit_percent) {
                            $is_exceeded = true;
                        }
                    }
                }
            }
            
        } elseif ($tab === 'tou') {
            $curr_011 = isset($_POST['on_val']) ? (float)$_POST['on_val'] : 0;
            $curr_012 = isset($_POST['off_val']) ? (float)$_POST['off_val'] : 0;

            $sql_prevs = "SELECT toudt_011, toudt_012, toudt_is_rollover as is_roll 
                          FROM tb_tou_detail 
                          WHERE toudt_tou_id = '$mt_id' AND toudt_ag_id = '$ag_id'
                          AND (DATE(toudt_time) < '$log_date' OR (DATE(toudt_time) = '$log_date' AND toudt_round_id < '$round_id'))
                          ORDER BY toudt_time DESC, toudt_round_id DESC LIMIT 2";
            
            $res_prevs = $connect->query($sql_prevs);
            $history = [];
            if($res_prevs) {
                while ($row_p = $res_prevs->fetch_assoc()) {
                    $history[] = $row_p; 
                }
            }

            if (count($history) > 0) {
                $prev1_011 = (float)$history[0]['toudt_011'];
                $prev1_012 = (float)$history[0]['toudt_012'];

                $curr_unit_011 = ($is_rollover == 1 && $curr_011 < $prev1_011) ? ($max_meter_val - $prev1_011) + $curr_011 : max(0, $curr_011 - $prev1_011);
                $curr_unit_012 = ($is_rollover == 1 && $curr_012 < $prev1_012) ? ($max_meter_val - $prev1_012) + $curr_012 : max(0, $curr_012 - $prev1_012);
                $curr_total_unit = $curr_unit_011 + $curr_unit_012;

                if (count($history) == 2) {
                    $prev2_011 = (float)$history[1]['toudt_011'];
                    $prev2_012 = (float)$history[1]['toudt_012'];
                    $roll_prev1 = (int)$history[0]['is_roll'];

                    $prev_unit_011 = ($roll_prev1 == 1 && $prev1_011 < $prev2_011) ? ($max_meter_val - $prev2_011) + $prev1_011 : max(0, $prev1_011 - $prev2_011);
                    $prev_unit_012 = ($roll_prev1 == 1 && $prev1_012 < $prev2_012) ? ($max_meter_val - $prev2_012) + $prev1_012 : max(0, $prev1_012 - $prev2_012);
                    $prev_total_unit = $prev_unit_011 + $prev_unit_012;

                    if ($prev_total_unit > 0) {
                        $percent_diff = (($curr_total_unit - $prev_total_unit) / $prev_total_unit) * 100;
                        if ($percent_diff > $limit_percent) {
                            $is_exceeded = true;
                        }
                    }
                }
            }
        }

        // 4. บันทึกข้อมูลการแจ้งเตือน
        if ($is_exceeded === true) {
            $date_parts = explode('-', $log_date);
            $issue_year = $date_parts[0];
            $issue_month = (int)$date_parts[1]; 
            
            $noti_type = 'meter_exceeded';
            $rp_format = "tab={$tab}&month={$issue_month}&year={$issue_year}";
            
            $sql_noti = "INSERT INTO `notifications` (`type`, `related_id`, `rp_format`, `created_at`) 
                         VALUES ('$noti_type', '$mt_id', '$rp_format', current_timestamp())";
            
            if (!$connect->query($sql_noti)) {
                $response['noti_error'] = "SQL Error: " . $connect->error; // เช็ก Error ว่า Query ผิดพลาดตรงไหน
            } else {
                $response['noti_success'] = "Inserted successfully!";
            }
        } 
        
        // 🛠 แนบ Debug ไปตอน Save เพื่อให้คุณเห็นค่าเบื้องหลัง
        $response['debug'] = [
            'history_count' => isset($history) ? count($history) : 0,
            'percent_diff' => $percent_diff,
            'limit_percent' => $limit_percent,
            'is_exceeded' => $is_exceeded
        ];
        // =======================================================================

    } else {
        $response = ['success' => false, 'message' => 'Error: ' . $connect->error];
    }
}

// ----------------------------------------------------------------
// ✅ 3. ตรวจสอบข้อมูลซ้ำ (CHECK DUPLICATE)
// ----------------------------------------------------------------
if ($action === 'check_duplicate') {
    $meter_id = mysqli_real_escape_string($connect, $_POST['meter_id']);
    $round_id = mysqli_real_escape_string($connect, $_POST['round_id']);
    $date     = mysqli_real_escape_string($connect, $_POST['date']);
    $type     = strtolower(mysqli_real_escape_string($connect, $_POST['type']));

    $sql = ""; 
    if ($type === 'wt') {
        $sql = "SELECT toumdt_wt_id as id FROM tb_toum_detail_wt WHERE toumdt_wt_toum_id = '$meter_id' AND toumdt_wt_round_id = '$round_id' AND DATE(toumdt_wt_time) = '$date'";
    } elseif ($type === 'et') {
        $sql = "SELECT toumdt_et_id as id FROM tb_toum_detail_et WHERE toumdt_et_toum_id = '$meter_id' AND toumdt_et_round_id = '$round_id' AND DATE(toumdt_et_time) = '$date'";
    } else {
        $sql = "SELECT toudt_id as id FROM tb_tou_detail WHERE toudt_tou_id = '$meter_id' AND toudt_round_id = '$round_id' AND DATE(toudt_time) = '$date'";
    }

    if ($sql !== "") {
        $result = $connect->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode(['exists' => true, 'id' => $row['id']]);
        } else {
            echo json_encode(['exists' => false]);
        }
    } else {
        echo json_encode(['exists' => false, 'error' => 'Invalid type']);
    }
    exit;
}

if (isset($connect)) mysqli_close($connect);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>