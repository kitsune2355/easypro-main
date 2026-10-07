<?php
set_time_limit(120); // อนุญาตให้ Script รันได้สูงสุด 120 วินาที (2 นาที)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config_ctrl/connect.php'; 

if (mysqli_connect_errno()) {
    http_response_code(500); 
    echo json_encode(['success' => false, 'error' => 'Database connection failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type"); 

date_default_timezone_set('Asia/Bangkok'); 

/**
 * ฟังก์ชันคำนวณวันทำ PM รอบถัดไป (รองรับวันหยุด และ ล็อกวันที่สำหรับรายเดือนแบบแม่นยำ)
 */
function calculateNextPMDate($baseDate, $freq, $daysConfig = [], $holidays = [], $defaultAction = 'none') {
    $current = new DateTime($baseDate);
    $freq = intval($freq);

    if ($freq > 0) {
        // --- 1. การบวกแบบนับจำนวนวันปกติ ---
        $current->modify("+$freq days");
    } else {
        switch ($freq) {
            // --- 2. กรณีรายวัน / วันทำงาน / ระบุวันในสัปดาห์ ---
            case -9: // ทุกวันทำงาน
                do { $current->modify('+1 day'); } while ($current->format('N') >= 6); 
                break;
                
            case -1: // 2 ครั้งต่อสัปดาห์
            case -3: // ระบุวันในสัปดาห์
                if (empty($daysConfig)) return $baseDate;
                $dayMap = ['จ.' => 1, 'อ.' => 2, 'พ.' => 3, 'พฤ.' => 4, 'ศ.' => 5, 'ส.' => 6, 'อา.' => 7];
                $targetDays = array_map(function($d) use ($dayMap) { 
                    return is_numeric($d) ? intval($d) : ($dayMap[$d] ?? 0); 
                }, $daysConfig);
                sort($targetDays);

                for ($i = 1; $i <= 7; $i++) {
                    $temp = clone $current;
                    $temp->modify("+$i day");
                    if (in_array((int)$temp->format('N'), $targetDays)) {
                        $current = $temp;
                        break;
                    }
                }
                break;
                
            // --- 3. กรณีระบุวันที่ของทุกเดือน (ล็อกวันที่) ---
            case -2: case -4: case -5: case -6: case -7: case -8:
            case -10: case -11: case -12: case -13: case -14:
                
                $monthMap = [
                    -2 => 1, -4 => 2, -5 => 3, -6 => 4, -7 => 5, -8 => 6, 
                    -10 => 12, -11 => 24, -12 => 36, -13 => 48, -14 => 60
                ];
                $monthsToAdd = $monthMap[$freq];
                
                $targetDays = [];
                if (!empty($daysConfig)) {
                    foreach ($daysConfig as $d) {
                        if (is_numeric($d)) $targetDays[] = (int)$d;
                    }
                }
                if (empty($targetDays)) {
                    $targetDays[] = (int)$current->format('d');
                }
                sort($targetDays);
                
                $currY = (int)$current->format('Y');
                $currM = (int)$current->format('m');
                $currD = (int)$current->format('d');
                
                $nextDay = null;
                $addMonth = true;
                
                foreach ($targetDays as $d) {
                    if ($d > $currD) {
                        $nextDay = $d;
                        $addMonth = false;
                        break;
                    }
                }
                
                if ($addMonth) {
                    $currM += $monthsToAdd;
                    while ($currM > 12) {
                        $currM -= 12;
                        $currY++;
                    }
                    $nextDay = $targetDays[0];
                }
                
                $maxDays = cal_days_in_month(CAL_GREGORIAN, $currM, $currY);
                $actualDay = min($nextDay, $maxDays);
                
                $current->setDate($currY, $currM, $actualDay);
                break;
                
            default: return $baseDate;
        }
    }

    // --- 🌟 4. Logic ตรวจสอบวันหยุด (แก้ไขการอ่านค่าวัน จ.-อา.) 🌟 ---
    if (!empty($holidays) || $defaultAction !== 'none') {
        $getHolidayAction = function($date) use ($holidays) {
            $date_Ymd = $date->format('Y-m-d');
            $date_md  = $date->format('m-d');
            $dayOfWeek = (int)$date->format('N'); // 1=จันทร์, ..., 7=อาทิตย์
            
            // Map สำหรับแปลงภาษาไทยเป็นตัวเลขวันในสัปดาห์
            $thaiDayMap = [
                'จ.' => 1, 'จันทร์' => 1,
                'อ.' => 2, 'อังคาร' => 2,
                'พ.' => 3, 'พุธ' => 3,
                'พฤ.' => 4, 'พฤหัส' => 4, 'พฤหัสบดี' => 4,
                'ศ.' => 5, 'ศุกร์' => 5,
                'ส.' => 6, 'เสาร์' => 6,
                'อา.' => 7, 'อาทิตย์' => 7
            ];

            foreach ($holidays as $hol) {
                // ประเภทเกิดครั้งเดียว (เช่น 2026-04-13)
                if ($hol['type'] === 'once' && $date_Ymd === $hol['date']) {
                    return $hol['handle'];
                }
                
                // ประเภททุกปี (เช่น 04-13)
                if ($hol['type'] === 'yearly' && $date_md === date('m-d', strtotime($hol['date']))) {
                    return $hol['handle'];
                }
                
                // ประเภทประจำสัปดาห์ (เช่น จ., อ., พ.)
                if ($hol['type'] === 'daily') {
                    $holDateVal = trim($hol['date']);
                    $targetHolDay = 0;
                    
                    if (isset($thaiDayMap[$holDateVal])) {
                        // ถ้าเป็นภาษาไทย ให้ใช้ตัวเลขจาก Map
                        $targetHolDay = $thaiDayMap[$holDateVal];
                    } elseif (is_numeric($holDateVal)) {
                        // ถ้าบันทึกเป็นตัวเลข 1-7 ตรงๆ
                        $targetHolDay = (int)$holDateVal;
                    } else {
                        // ถ้าเป็นภาษาอังกฤษ เช่น 'Monday'
                        $targetHolDay = (int)date('N', strtotime($holDateVal));
                    }

                    // ถ้าระบบคำนวณได้ว่าวันนี้ตรงกับวันหยุดประจำสัปดาห์ที่ตั้งไว้
                    if ($targetHolDay === $dayOfWeek) {
                        return $hol['handle'];
                    }
                }
            }
            return 'none'; 
        };

        // หาว่าวันที่คำนวณมา ดันเป็นวันหยุดหรือไม่
        $action = $getHolidayAction($current);

        if ($action !== 'none') {
            switch ($action) {
                case 'next_working_day': 
                    // เลื่อนไปข้างหน้าทีละวัน จนกว่าจะไม่ใช่วันหยุด
                    while ($getHolidayAction($current) !== 'none') {
                        $current->modify('+1 day');
                    }
                    break;
                case 'prev_working_day': 
                    // ถอยหลังทีละวัน จนกว่าจะไม่ใช่วันหยุด
                    while ($getHolidayAction($current) !== 'none') {
                        $current->modify('-1 day');
                    }
                    break;
                case 'skip': 
                    // ข้ามไปคำนวณรอบถัดไปเลย
                    return calculateNextPMDate($current->format('Y-m-d'), $freq, $daysConfig, $holidays, $defaultAction);
            }
        }
    }

    return $current->format('Y-m-d');
}

function getHolidays($connect, $ag_id) {
    if (!$ag_id) return [];
    
    $holidays = [];
    $sql = "SELECT date, type, handle FROM pm_holidays WHERE ag_id = ? AND status = 1";
    $stmt = mysqli_prepare($connect, $sql);
    mysqli_stmt_bind_param($stmt, "s", $ag_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $holidays[] = $row;
    }
    mysqli_stmt_close($stmt);
    return $holidays;
}

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : null); 
$response = ['success' => false, 'data' => []];

if (!$action) {
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุการดำเนินการ (action)'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'save') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $contract_start = $input['contract_start'] ?? null;
    $contract_end = $input['contract_end'] ?? null;
    $ag_id = $input['ag_id'] ?? null;
    $mac_type = $input['mac_type'] ?? null;
    $user_id = $input['user_id'] ?? null;

    if (!$contract_start || !$contract_end || empty($input['machines'])) {
        echo json_encode(['success' => false, 'error' => 'ข้อมูลสัญญาหรือเครื่องจักรไม่ครบถ้วน'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $holidays = getHolidays($connect, $ag_id);

    mysqli_begin_transaction($connect);
    try {
        // เตรียม Statement ล่วงหน้าเพื่อประสิทธิภาพ
        $sql_plan = "INSERT INTO pm_plans (ag_id, mac_type_id, machine_id, checksheet_id, frequency, alert_value, days_config, status, start_date, next_date, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, NOW())";
        $stmt_plan = mysqli_prepare($connect, $sql_plan);

        $sql_ev = "INSERT INTO pm_plan_events (ag_id, plan_id, machine_id, mac_type_id, checksheet_id, event_date, status) VALUES (?, ?, ?, ?, ?, ?, 0)";
        $stmt_ev = mysqli_prepare($connect, $sql_ev);

        foreach ($input['machines'] as $machine) {
            $machine_id = $machine['id']; 

            foreach ($input['checksheets'] as $index => $cs) {
                $chk_id = $cs['id'];
                $freq = $cs['freqValue'] ?? '';
                $daysArray = $cs['days'] ?? [];
                $days_config_json = json_encode($daysArray, JSON_UNESCAPED_UNICODE);
                $alertValue = $cs['alertValue'] ?? '';
                
                $date_key = "startDate_" . $index;
                $start_date = !empty($machine[$date_key]) ? $machine[$date_key] : null;

                if (!$start_date) continue;

                // 1. บันทึกแผนหลักลง pm_plans
                $next_date = calculateNextPMDate($start_date, $freq, $daysArray, $holidays);
                mysqli_stmt_bind_param($stmt_plan, "ssssssssss", $ag_id, $mac_type, $machine_id, $chk_id, $freq, $alertValue, $days_config_json, $start_date, $next_date, $user_id);
                
                if (!mysqli_stmt_execute($stmt_plan)) {
                    throw new Exception("ไม่สามารถบันทึกแผนได้: " . mysqli_error($connect));
                }
                $plan_id = mysqli_insert_id($connect);

                // 2. สร้างรายการ Event ลงใน pm_plan_events ตลอดอายุสัญญา
                $current_event_date = $start_date;
                while ($current_event_date && $current_event_date <= $contract_end) {
                    // บันทึกเฉพาะวันที่อยู่ภายในช่วงสัญญา
                    if ($current_event_date >= $contract_start) {
                        mysqli_stmt_bind_param($stmt_ev, "sissss", $ag_id, $plan_id, $machine_id, $mac_type, $chk_id, $current_event_date);
                        if (!mysqli_stmt_execute($stmt_ev)) {
                            throw new Exception("ไม่สามารถบันทึก Event ได้: " . mysqli_error($connect));
                        }
                    }

                    // คำนวณวันถัดไป
                    $next_ev = calculateNextPMDate($current_event_date, $freq, $daysArray, $holidays);
                    
                    // ป้องกัน Loop ไม่สิ้นสุด
                    if (!$next_ev || $next_ev <= $current_event_date) break; 
                    
                    $current_event_date = $next_ev;
                }
            }
        }
        
        mysqli_stmt_close($stmt_plan);
        mysqli_stmt_close($stmt_ev);
        mysqli_commit($connect);
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        mysqli_rollback($connect);
        echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

elseif ($action === 'update') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $plan_id = $input['id'] ?? null;
    $ag_id = $input['ag_id'] ?? null;
    $next_date_ui = $input['nextDate'] ?? null; 
    $contract_end = $input['contract_end'] ?? null;
    $freq = $input['freq'] ?? null;
    $daysArray = $input['days'] ?? [];
    $alert_value = $input['alert'] ?? '';

    if (!$plan_id || !$next_date_ui || !$contract_end) {
        echo json_encode(['success' => false, 'error' => 'ข้อมูลสำหรับการอัปเดตไม่ครบถ้วน'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $holidays = getHolidays($connect, $ag_id);

    mysqli_begin_transaction($connect);
    try {
        // 1. ดึงข้อมูล Metadata จากแผนหลักมาเตรียมไว้
        $machine_id = '';
        $mac_type_id = '';
        $checksheet_id = '';
        
        $sql_get_plan = "SELECT machine_id, mac_type_id, checksheet_id FROM pm_plans WHERE id = ?";
        $stmt_get_plan = mysqli_prepare($connect, $sql_get_plan);
        mysqli_stmt_bind_param($stmt_get_plan, "i", $plan_id);
        mysqli_stmt_execute($stmt_get_plan);
        mysqli_stmt_bind_result($stmt_get_plan, $machine_id, $mac_type_id, $checksheet_id);
        
        if (!mysqli_stmt_fetch($stmt_get_plan)) {
            throw new Exception("ไม่พบแผนที่ต้องการอัปเดต");
        }
        mysqli_stmt_close($stmt_get_plan);

        // 2. อัปเดตข้อมูลใน pm_plans
        $next_next_date = calculateNextPMDate($next_date_ui, $freq, $daysArray, $holidays);
        $days_json = json_encode($daysArray, JSON_UNESCAPED_UNICODE);
        
        $sql_update = "UPDATE pm_plans SET next_date = ?, frequency = ?, alert_value = ?, days_config = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($connect, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "ssssi", $next_date_ui, $freq, $alert_value, $days_json, $plan_id);
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("ไม่สามารถอัปเดตแผนได้: " . mysqli_error($connect));
        }
        mysqli_stmt_close($stmt_update);

        // 3. ล้าง Event เดิมที่ยัง "รอดำเนินการ" (Status 0)
        $sql_del = "DELETE FROM pm_plan_events WHERE plan_id = ? AND status = 0";
        $stmt_del = mysqli_prepare($connect, $sql_del);
        mysqli_stmt_bind_param($stmt_del, "i", $plan_id);
        mysqli_stmt_execute($stmt_del);
        mysqli_stmt_close($stmt_del);

        // 4. สร้าง Event ใหม่เริ่มจาก next_date_ui จนถึงสิ้นสุดสัญญา
        $sql_ev = "INSERT INTO pm_plan_events (ag_id, plan_id, machine_id, mac_type_id, checksheet_id, event_date, status) VALUES (?, ?, ?, ?, ?, ?, 0)";
        $stmt_ev = mysqli_prepare($connect, $sql_ev);
        
        $current_ev = $next_date_ui;
        while ($current_ev && $current_ev <= $contract_end) {
            mysqli_stmt_bind_param($stmt_ev, "sissss", $ag_id, $plan_id, $machine_id, $mac_type_id, $checksheet_id, $current_ev);
            if (!mysqli_stmt_execute($stmt_ev)) {
                 throw new Exception("ไม่สามารถสร้าง Event ใหม่ได้: " . mysqli_error($connect));
            }
            
            $next_ev = calculateNextPMDate($current_ev, $freq, $daysArray, $holidays);
            // ป้องกัน Loop ไม่สิ้นสุด
            if (!$next_ev || $next_ev <= $current_ev) break;
            
            $current_ev = $next_ev;
        }
        mysqli_stmt_close($stmt_ev);

        // --- 🌟 ส่วนที่ต้องเพิ่ม: อัปเดต next_date ให้เป็นปัจจุบันที่สุด 🌟 ---
        $today = date('Y-m-d');
        $sql_next = "SELECT MIN(event_date) as real_next FROM pm_plan_events 
                     WHERE plan_id = ? AND event_date >= ? AND status = 0";
        $stmt_next = mysqli_prepare($connect, $sql_next);
        mysqli_stmt_bind_param($stmt_next, "is", $plan_id, $today);
        mysqli_stmt_execute($stmt_next);
        $res_next = mysqli_stmt_get_result($stmt_next);
        $row_next = mysqli_fetch_assoc($res_next);
        
        if ($row_next['real_next']) {
            $update_plan_final = "UPDATE pm_plans SET next_date = ? WHERE id = ?";
            $stmt_up_final = mysqli_prepare($connect, $update_plan_final);
            mysqli_stmt_bind_param($stmt_up_final, "si", $row_next['real_next'], $plan_id);
            mysqli_stmt_execute($stmt_up_final);
        }

        mysqli_commit($connect);
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        mysqli_rollback($connect);
        echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

elseif ($action === 'get_all') {
    $ag_id = $_GET['ag_id'] ?? $_POST['ag_id'] ?? '';
    
    // เปลี่ยนจาก ass_type เป็น tb_asset_group
    // และเปลี่ยน m.ass_type_name เป็น m.TGroupName
    $sql = "SELECT p.*, 
                   c.name as checksheetName, 
                   m.TGroupName as macTypeName, 
                   a.asset_name as equipmentName,
                   a.asset_sn,
                   f.description as freqDesc,
                   al.description as alertDesc
            FROM pm_plans p
            LEFT JOIN pm_checksheets c ON p.checksheet_id = c.id
            LEFT JOIN tb_asset_group m ON p.mac_type_id = m.GroupId
            LEFT JOIN tb_ass_list a ON p.machine_id = a.ass_id
            LEFT JOIN pm_freq_options f ON p.frequency = f.freq_value
            LEFT JOIN pm_alert_options al ON p.alert_value = al.alert_value
            WHERE p.ag_id = ?
            ORDER BY p.created_at DESC";
            
    $stmt = mysqli_prepare($connect, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $ag_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // แปลง JSON string ของวันในสัปดาห์กลับเป็น Array เพื่อให้หน้าบ้านใช้ได้ง่าย
            $row['days_config'] = json_decode($row['days_config'], true);
            $data[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'error' => 'Query failed: ' . mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

elseif ($action === 'get_by_id') {
    $plan_id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$plan_id) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบรหัสแผน PM'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = "SELECT * FROM pm_plans WHERE id = ?";
    $stmt = mysqli_prepare($connect, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $plan_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $row['days_config'] = json_decode($row['days_config'], true);
            echo json_encode(['success' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลแผน'], JSON_UNESCAPED_UNICODE);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'error' => 'Query failed: ' . mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

elseif ($action === 'delete') {
    $plan_id = $_GET['id'] ?? $_POST['id'] ?? null;
    if (!$plan_id) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบรหัสแผน PM'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = "DELETE FROM pm_plans WHERE id = ?";
    $stmt = mysqli_prepare($connect, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $plan_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'ลบแผน PM สำเร็จ'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => 'Delete failed: ' . mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
        }
        mysqli_stmt_close($stmt);
    }
    exit;
}

elseif ($action === 'get_options') {
    $response_data = [
        'alerts' => [],
        'freqs' => []
    ];

    // 1. ดึงข้อมูลตัวเลือกการแจ้งเตือน (Alert Options)
    $sql_alert = "SELECT alert_value AS value, description AS `desc` FROM pm_alert_options ORDER BY id ASC";
    $query_alert = mysqli_query($connect, $sql_alert); 
    
    if ($query_alert) {
        while ($row = mysqli_fetch_assoc($query_alert)) {
            // บังคับให้ value เป็น string ตามโครงสร้างเดิมที่หน้าบ้านต้องการ
            $row['value'] = (string)$row['value'];
            $response_data['alerts'][] = $row;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Query alerts failed: ' . mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. ดึงข้อมูลตัวเลือกความถี่ (Frequency Options)
    $sql_freq = "SELECT freq_value AS value, dropdown_multi_select AS dropdownmultiselect, alert_before_for_repeat_config AS alertbeforeforrepeatconfig, description AS `desc` FROM pm_freq_options ORDER BY id ASC";
    $query_freq = mysqli_query($connect, $sql_freq); 
    
    if ($query_freq) {
        while ($row = mysqli_fetch_assoc($query_freq)) {
            // บังคับชนิดข้อมูลเป็น string ให้เหมือน Hardcode เดิม
            $row['value'] = (string)$row['value'];
            $row['dropdownmultiselect'] = (string)$row['dropdownmultiselect'];
            $row['alertbeforeforrepeatconfig'] = (string)$row['alertbeforeforrepeatconfig'];
            $response_data['freqs'][] = $row;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Query freqs failed: ' . mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ส่งข้อมูลกลับเป็น JSON
    echo json_encode(['success' => true, 'data' => $response_data], JSON_UNESCAPED_UNICODE);
    exit;
}
elseif ($action === 'get_machines_by_group') {
    $mac_type = isset($_GET['macType']) ? $_GET['macType'] : (isset($_POST['macType']) ? $_POST['macType'] : '');
    $ag_id = isset($_GET['AG_ID']) ? $_GET['AG_ID'] : (isset($_POST['AG_ID']) ? $_POST['AG_ID'] : '');

    if (empty($ag_id)) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบ (AG_ID)'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // สร้างคำสั่ง SQL โดยใช้ LEFT JOIN ดึงข้อมูลสถานที่
    $sql = "SELECT 
            a.ass_id,
            a.ass_code,
            a.asset_name, 
            ar.area_name, 
            ac.ac_name, 
            rm.ar_name,
            ag.TGroupName  
        FROM tb_ass_list a
        LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
        LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
        LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
        LEFT JOIN tb_asset_group ag ON a.asset_type = ag.GroupId
        WHERE a.asset_type = ? AND a.ass_ag_id = ?";

    $stmt = mysqli_prepare($connect, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $mac_type, $ag_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $machines = [];
        while ($row = mysqli_fetch_assoc($result)) {
            // นำสถานที่มาต่อกัน (ถ้ามีข้อมูล)
            $locationParts = [];
            if (!empty($row['area_name'])) $locationParts[] = $row['area_name'];
            if (!empty($row['ac_name'])) $locationParts[] = $row['ac_name'];
            if (!empty($row['ar_name'])) $locationParts[] = $row['ar_name'];
            $location = implode(' / ', $locationParts);

            $machines[] = [
                'id' => $row['ass_id'], 
                'qrCode' => $row['ass_code'],
                'groupName' => $row['TGroupName'],
                'name' => $row['asset_name'],
                'location' => empty($location) ? 'ไม่ระบุสถานที่' : $location
            ];
        }

        echo json_encode(['success' => true, 'data' => $machines], JSON_UNESCAPED_UNICODE);
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'error' => 'Query Failed: ' . mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
elseif ($action === 'update_status') {
    $input = json_decode(file_get_contents('php://input'), true);
    $plan_id = $input['id'] ?? null;
    $status = isset($input['status']) ? $input['status'] : null;

    if ($plan_id === null || $status === null) {
        echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ครบถ้วน'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = "UPDATE pm_plans SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($connect, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $status, $plan_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'อัปเดตสถานะสำเร็จ'], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'error' => 'ไม่สามารถอัปเดตสถานะได้: ' . mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'error' => 'Query preparation failed'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
elseif ($action === 'regenerate_calendar') {
    $input = json_decode(file_get_contents('php://input'), true);
    $ag_id = $input['ag_id'] ?? null;
    $contract_end = $input['contract_end'] ?? null;

    if (!$ag_id || !$contract_end) {
        echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ครบถ้วน (ต้องการ ag_id และ contract_end)'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $holidays = getHolidays($connect, $ag_id);

    mysqli_begin_transaction($connect);
    try {
        // 1. ดึงแผน PM ทั้งหมดของเอเจนซี่นั้นที่ยังทำงานอยู่ (status = 1)
        $sql = "SELECT * FROM pm_plans WHERE ag_id = ? AND status = 1";
        $stmt = mysqli_prepare($connect, $sql);
        mysqli_stmt_bind_param($stmt, "s", $ag_id);
        mysqli_stmt_execute($stmt);
        $plans = mysqli_stmt_get_result($stmt);

        // เตรียม statement สำหรับ Insert event ใหม่
        $sql_ev = "INSERT INTO pm_plan_events (ag_id, plan_id, machine_id, mac_type_id, checksheet_id, event_date, status) VALUES (?, ?, ?, ?, ?, ?, 0)";
        $stmt_ev = mysqli_prepare($connect, $sql_ev);

        while ($plan = mysqli_fetch_assoc($plans)) {
            $plan_id = $plan['id'];
            $freq = $plan['frequency'];
            $days_config = !empty($plan['days_config']) ? json_decode($plan['days_config'], true) : [];
            
            // 2. หาวันที่สร้าง Event ล่าสุดของแผนนี้ (ถ้าไม่มีให้เริ่มจาก start_date)
            $sql_last_event = "SELECT MAX(event_date) as last_date FROM pm_plan_events WHERE plan_id = ?";
            $stmt_last = mysqli_prepare($connect, $sql_last_event);
            mysqli_stmt_bind_param($stmt_last, "i", $plan_id);
            mysqli_stmt_execute($stmt_last);
            $res_last = mysqli_stmt_get_result($stmt_last);
            $row_last = mysqli_fetch_assoc($res_last);
            
            // ตั้งต้นวันที่สำหรับการคำนวณลูป
            $current_date = $row_last['last_date'] ? $row_last['last_date'] : $plan['start_date'];

            // 3. คำนวณวันถัดไป
            $next_date = calculateNextPMDate($current_date, $freq, $days_config, $holidays, 'none');
            
            // 4. ลูปสร้าง event ใหม่ไปเรื่อยๆ จนกว่าจะเกินวันที่สิ้นสุดสัญญา (contract_end)
            while (strtotime($next_date) <= strtotime($contract_end)) {
                // ตรวจสอบก่อนว่าเคยสร้างของวันนี้ไปหรือยัง (กันข้อมูลซ้ำ)
                $check_sql = "SELECT id FROM pm_plan_events WHERE plan_id = ? AND event_date = ?";
                $check_stmt = mysqli_prepare($connect, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "is", $plan_id, $next_date);
                mysqli_stmt_execute($check_stmt);
                $check_res = mysqli_stmt_get_result($check_stmt);
                
                if (mysqli_num_rows($check_res) == 0) {
                     mysqli_stmt_bind_param($stmt_ev, "siiiis", $ag_id, $plan_id, $plan['machine_id'], $plan['mac_type_id'], $plan['checksheet_id'], $next_date);
                     mysqli_stmt_execute($stmt_ev);
                }
                
                $current_date = $next_date;
                $next_date = calculateNextPMDate($current_date, $freq, $days_config, $holidays, 'none');

                // 🌟 ป้องกัน Infinite Loop: ถ้าวันที่ถัดไปไม่อัปเดตไปข้างหน้า หรือคำนวณไม่ได้ ให้หยุดทันที
                if (!$next_date || strtotime($next_date) <= strtotime($current_date)) {
                    break; 
                }
            }
            
            // 5. อัปเดต next_date ในตาราง pm_plans หลัก ให้ชี้ไปยัง Event ถัดไปจากวันปัจจุบัน
            $today = date('Y-m-d');
            $sql_next = "SELECT MIN(event_date) as real_next FROM pm_plan_events WHERE plan_id = ? AND event_date >= ? AND status = 0";
            $stmt_next = mysqli_prepare($connect, $sql_next);
            mysqli_stmt_bind_param($stmt_next, "is", $plan_id, $today);
            mysqli_stmt_execute($stmt_next);
            $res_next = mysqli_stmt_get_result($stmt_next);
            $row_next = mysqli_fetch_assoc($res_next);
            
            if ($row_next['real_next']) {
                $update_plan = "UPDATE pm_plans SET next_date = ? WHERE id = ?";
                $stmt_up = mysqli_prepare($connect, $update_plan);
                mysqli_stmt_bind_param($stmt_up, "si", $row_next['real_next'], $plan_id);
                mysqli_stmt_execute($stmt_up);
            }
        }
        
        mysqli_commit($connect);
        echo json_encode(['success' => true, 'message' => 'สร้างกำหนดการ PM ล่วงหน้าเรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        mysqli_rollback($connect);
        echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ด้านล่างปล่อยไว้เหมือนเดิม
if (isset($connect)) mysqli_close($connect);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>