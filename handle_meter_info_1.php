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
                m.mt_id, m.mt_name, m.mt_type,
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

                $meter_obj = [
                    'id' => $meter_id,
                    'name' => $row_m['mt_name'],
                    'location' => $location,
                    'brand' => '-',
                    'size' => '-',
                    'number' => $meter_id,
                    'type' => strtoupper($tab),
                    'history' => []
                ];

                if ($tab === 'wt' || $tab === 'et') {
                    $table_name = ($tab === 'wt') ? 'tb_toum_detail_wt' : 'tb_toum_detail_et';
                    $prefix = ($tab === 'wt') ? 'toumdt_wt' : 'toumdt_et';
                    
                    $sql_his = "
                        SELECT 
                            DATE(td.{$prefix}_time) as log_date,
                            td.{$prefix}_mt as val,
                            td.{$prefix}_rm as note,
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
                    $latest_curr = 0;
                    $latest_prev = 0;

                    // 1. ดึงค่ารอบสุดท้ายก่อนถึงเดือน/ปี ที่เลือก เพื่อใช้เป็นฐานลบของรอบแรกในเดือนนี้
                    $prev_val = 0;
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
                            $date_parts = explode('-', $row_h['log_date']);
                            $formatted_date = $date_parts[2].'/'.$date_parts[1].'/'.$date_parts[0];
                            $time_display = substr($row_h['round_time'], 0, 5);
                            $usage = $curr_val - $prev_val;

                            if (!isset($temp_history[$formatted_date])) {
                                $temp_history[$formatted_date] = ['date' => $formatted_date, 'round' => []];
                            }

                            $temp_history[$formatted_date]['round'][] = [
                                'round_id' => intval($row_h['round_id']),
                                'time' => $time_display,
                                'prev' => $prev_val,
                                'curr' => $curr_val,
                                'usage' => $usage,
                                'note' => $row_h['note'],
                                'images' => $row_h['img_path'] ? [$row_h['img_path']] : [],
                                'recorder' => $row_h['recorder_name']
                            ];
                            $latest_prev = $prev_val;
                            $latest_curr = $curr_val;
                            $prev_val = $curr_val;
                        }
                    }
                    $meter_obj['history'] = array_reverse(array_values($temp_history));
                    $meter_obj['lastRead'] = number_format($latest_curr, 2);
                    $meter_obj['usage'] = number_format($latest_curr - $latest_prev, 2);

                } elseif ($tab === 'tou') {
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
                    $latest_total_val = 0;
                    $latest_total_unit = 0;
                    // 1. ดึงค่า TOU ของรอบล่าสุดก่อนถึงเดือน/ปี ที่ค้นหา (เพิ่มโค้ดส่วนนี้ก่อนเข้าลูป)
                    $prev_data = [];
                    if (!empty($month) && !empty($year)) {
                        // สมมติว่าในระบบมีตัวแปร $meter_id และ $ag_id อยู่แล้ว
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

                    // 2. ลูปแสดงผลข้อมูล TOU
                    if ($res_tou) {
                        while ($row_t = $res_tou->fetch_assoc()) {
                            $date_parts = explode('-', $row_t['log_date']);
                            $formatted_date = $date_parts[2].'/'.$date_parts[1].'/'.$date_parts[0];
                            $time_display = substr($row_t['round_time'], 0, 5);

                            $val_010 = floatval($row_t['toudt_010']);
                            $val_011 = floatval($row_t['toudt_011']);
                            $val_012 = floatval($row_t['toudt_012']);

                            // ถ้ามีค่าจากเดือนที่แล้ว (หรือรอบก่อนหน้า) ให้ดึงมาใช้ ถ้าไม่มีให้เป็น 0
                            $prev_011 = isset($prev_data['toudt_011']) ? $prev_data['toudt_011'] : 0;
                            $prev_012 = isset($prev_data['toudt_012']) ? $prev_data['toudt_012'] : 0;

                            // แก้ไข: เอา !empty($prev_data) ออก เพื่อให้ระบบคำนวณจากค่า $prev เสมอ
                            $unit_011 = $val_011 - $prev_011;
                            $unit_012 = $val_012 - $prev_012;
                            
                            // ป้องกันกรณีค่าติดลบ (ถ้าเกิดกรณีมิเตอร์วนรอบหรือมีการรีเซ็ต)
                            $unit_011 = $unit_011 < 0 ? 0 : $unit_011;
                            $unit_012 = $unit_012 < 0 ? 0 : $unit_012;

                            $unit_total = $unit_011 + $unit_012;

                            if (!isset($temp_history[$formatted_date])) {
                                $temp_history[$formatted_date] = ['date' => $formatted_date, 'round' => []];
                            }

                            $temp_history[$formatted_date]['round'][] = [
                                'round_id' => intval($row_t['toudt_round_id']),
                                'time' => $time_display,
                                'total_val' => $val_010,
                                'total_unit' => $unit_total,
                                'on_val' => $val_011,
                                'on_unit' => $unit_011,
                                'off_val' => $val_012,
                                'off_unit' => $unit_012,
                                'on_peak_demand' => floatval($row_t['toudt_031']),
                                'off_peak_demand' => floatval($row_t['toudt_032']),
                                'on_reactive' => floatval($row_t['toudt_071']),
                                'off_reactive' => floatval($row_t['toudt_072']),
                                'note' => $row_t['toudt_note'],
                                'images' => $row_t['toudt_img'] ? [$row_t['toudt_img']] : [],
                                'recorder' => $row_t['recorder_name']
                            ];

                            $latest_total_val = $val_010;
                            $latest_total_unit = $unit_total;
                            
                            // เก็บค่าของรอบนี้ไว้เป็น "ค่าของรอบก่อนหน้า" สำหรับการวนลูปแถวถัดไป
                            $prev_data = ['toudt_011' => $val_011, 'toudt_012' => $val_012];
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

    // --- 🛠 ปรับปรุงส่วนการตรวจสอบ (Validation) ตามเงื่อนไข ---
    // หากเป็นการเพิ่มข้อมูล (Insert) หรือบันทึกทับรอบเดิม -> ตรวจสอบค่า
    // หากเป็นการกดแก้ไข (ผ่านหน้า Edit ที่ UI ส่ง flag มา) -> ข้ามการตรวจสอบ
    $is_edit_mode = (isset($_POST['mode']) && $_POST['mode'] === 'edit');

    if (!$is_edit_mode) {
        if ($tab === 'wt' || $tab === 'et') {
            $curr_val = (float)$_POST['curr'];
            $sql_prev = "SELECT {$prefix}_mt FROM $table WHERE {$prefix}_toum_id = '$mt_id' AND {$prefix}_ag_id = '$ag_id' 
                        AND (DATE({$prefix}_time) < '$log_date' OR (DATE({$prefix}_time) = '$log_date' AND {$prefix}_round_id < '$round_id'))
                        ORDER BY {$prefix}_time DESC, {$prefix}_round_id DESC LIMIT 1";
            $res_prev = $connect->query($sql_prev);
            if ($res_prev && $row_prev = $res_prev->fetch_assoc()) {
                if ($curr_val < (float)$row_prev["{$prefix}_mt"]) {
                    echo json_encode(['success' => false, 'message' => 'ค่ามิเตอร์ต้องไม่น้อยกว่ารอบก่อนหน้า (' . $row_prev["{$prefix}_mt"] . ')'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
            }
        } 
        elseif ($tab === 'tou') {
            // รับค่าจาก $_POST
            $v010 = (float)$_POST['total_val']; // Energy Total
            $v011 = (float)$_POST['on_val'];    // Energy On Peak
            $v012 = (float)$_POST['off_val'];   // Energy Off Peak
            
            // รับค่าเพิ่มเติมตามที่ต้องการตรวจสอบ
            $v031 = (float)$_POST['on_peak_demand'];  // Max Demand On Peak
            $v032 = (float)$_POST['off_peak_demand']; // Max Demand Off Peak
            $v071 = (float)$_POST['on_reactive'];     // Reactive Energy On Peak
            $v072 = (float)$_POST['off_reactive'];    // Reactive Energy Off Peak

            // ดึงข้อมูลก่อนหน้าเพื่อเปรียบเทียบ
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
                // สร้าง Array รายการที่ต้องการตรวจสอบ (ชื่อแสดงใน Error => [ค่าปัจจุบัน, ค่าเก่า])
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
                    if ($values[0] < $values[1]) {
                        echo json_encode([
                            'success' => false, 
                            'message' => "ค่า $label ต่ำกว่าข้อมูลก่อนหน้า [ $values[1] ]"
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
            $sql = "UPDATE $table SET {$prefix}_mt = '$val', {$prefix}_rm = '$note', {$prefix}_img = '$final_img', {$prefix}_user_upd = '$user_id', {$prefix}_upd = NOW() WHERE {$prefix}_id = '$row_id'";
        } else {
            $save_img = ($img_path === null) ? "" : $img_path;
            $sql = "INSERT INTO $table ({$prefix}_toum_id, {$prefix}_ag_id, {$prefix}_round_id, {$prefix}_time, {$prefix}_mt, {$prefix}_rm, {$prefix}_img, {$prefix}_user_ins, {$prefix}_ins, {$prefix}_user_upd, {$prefix}_upd) 
                    VALUES ('$mt_id', '$ag_id', '$round_id', '$log_date " . date('H:i:s') . "', '$val', '$note', '$save_img', '$user_id', NOW(), '$user_id', NOW())";
        }
    } elseif ($tab === 'tou') {
        // 1. รับค่าและจัดการค่าว่างสำหรับฟิลด์ตัวเลข
        $v010 = (isset($_POST['total_val']) && $_POST['total_val'] !== '') ? mysqli_real_escape_string($connect, $_POST['total_val']) : '0';
        $v011 = (isset($_POST['on_val']) && $_POST['on_val'] !== '') ? mysqli_real_escape_string($connect, $_POST['on_val']) : '0';
        $v012 = (isset($_POST['off_val']) && $_POST['off_val'] !== '') ? mysqli_real_escape_string($connect, $_POST['off_val']) : '0';
        $v031 = (isset($_POST['on_peak_demand']) && $_POST['on_peak_demand'] !== '') ? mysqli_real_escape_string($connect, $_POST['on_peak_demand']) : '0';
        $v032 = (isset($_POST['off_peak_demand']) && $_POST['off_peak_demand'] !== '') ? mysqli_real_escape_string($connect, $_POST['off_peak_demand']) : '0';
        $v071 = (isset($_POST['on_reactive']) && $_POST['on_reactive'] !== '') ? mysqli_real_escape_string($connect, $_POST['on_reactive']) : '0';
        $v072 = (isset($_POST['off_reactive']) && $_POST['off_reactive'] !== '') ? mysqli_real_escape_string($connect, $_POST['off_reactive']) : '0';

        if (!$is_insert) {
            // 2. SQL สำหรับ UPDATE
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
                        toudt_img='$final_img', 
                        toudt_user_upd='$user_id', 
                        toudt_upd=NOW() 
                    WHERE toudt_id='$row_id'";
        } else {
            // 3. SQL สำหรับ INSERT
            $save_img = ($img_path === null) ? "" : $img_path;

            $sql = "INSERT INTO tb_tou_detail (
                        toudt_tou_id, toudt_ag_id, toudt_round_id, toudt_time, 
                        toudt_010, toudt_011, toudt_012, 
                        toudt_031, toudt_032, toudt_071, toudt_072, 
                        toudt_note, toudt_img, toudt_user_ins, toudt_ins, toudt_user_upd, toudt_upd
                    ) 
                    VALUES (
                        '$mt_id', '$ag_id', '$round_id', '$log_date " . date('H:i:s') . "', 
                        '$v010', '$v011', '$v012', 
                        '$v031', '$v032', '$v071', '$v072', 
                        '$note', '$save_img', '$user_id', NOW(), '$user_id', NOW()
                    )";
        }
    }

    if (isset($sql) && $connect->query($sql)) {
        $response = ['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว'];
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