<?php
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

if ($action === 'regenerate_calendar') {
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