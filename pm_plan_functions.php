<?php
//pm_plan_functions.php — ฟังก์ชันคำนวณแผน PM ที่ใช้ร่วมกัน (handle_pm_plan.php, handle_pm_import.php, handle_pm_schedule.php)

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

// freq_value => ['desc', 'multi' (dropdown_multi_select), 'max_alert' (alert_before_for_repeat_config)]
function getFreqOptions($connect) {
    $opts = [];
    $res = mysqli_query($connect, "SELECT freq_value, description, dropdown_multi_select, alert_before_for_repeat_config FROM pm_freq_options ORDER BY id ASC");
    if (!$res) throw new Exception('Query failed: ' . mysqli_error($connect));
    while ($r = mysqli_fetch_assoc($res)) {
        $opts[(string)$r['freq_value']] = [
            'desc' => $r['description'],
            'multi' => (string)$r['dropdown_multi_select'],
            'max_alert' => intval($r['alert_before_for_repeat_config'])
        ];
    }
    return $opts;
}

/**
 * ตรวจค่าแผน PM: ความถี่ / แจ้งเตือน / ระบุวัน / วันที่เริ่มคำนวณ — คืนข้อความผิดพลาด หรือ '' ถ้าถูกต้อง
 * ใช้ร่วมกัน: บันทึกแผนจากตารางนำเข้า Excel (handle_pm_import.php) และแก้แผนในตารางแผน PM (handle_pm_schedule.php)
 * $days = วันที่ระบุ (array ของ string), $alertValues = alert_value ทั้งหมดใน pm_alert_options
 */
function validatePmPlanFields($freq, $alert, $days, $start, $freqOptions, $alertValues, $contractStart, $contractEnd) {
    $dayOptions = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.', 'อา.'];
    if (!isset($freqOptions[$freq])) return 'กรุณาเลือกความถี่';
    if (!in_array((string)$alert, array_map('strval', $alertValues), true)) return 'กรุณาเลือกการแจ้งเตือน';
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !strtotime($start)) return 'วันที่เริ่มไม่ถูกต้อง';
    if (($contractStart && $start < $contractStart) || ($contractEnd && $start > $contractEnd)) return 'วันที่เริ่มอยู่นอกช่วงสัญญา';

    $multi = $freqOptions[$freq]['multi'];
    $av = intval($alert);
    if (!($av === 0 || $av === -1 || $av <= $freqOptions[$freq]['max_alert'])) return 'การแจ้งเตือนนี้ใช้กับความถี่ที่เลือกไม่ได้';
    if ($multi === '2' && count($days) !== 2) return 'ต้องระบุวันให้ครบ 2 วัน';
    if (($multi === '6' || $multi === '31') && !$days) return 'ต้องระบุวันอย่างน้อย 1 วัน';
    if (in_array($multi, ['2', '5', '6'], true) && array_diff($days, $dayOptions)) return 'วันในสัปดาห์ต้องเป็น จ. อ. พ. พฤ. ศ. ส. อา.';
    if ($multi === '31' && array_filter($days, function ($d) { return !ctype_digit($d) || $d < 1 || $d > 31; })) return 'วันที่ในเดือนต้องเป็น 1-31';
    if (!in_array($multi, ['2', '5', '6', '31'], true) && $days) return 'ความถี่นี้ไม่ต้องระบุวัน';
    return '';
}
