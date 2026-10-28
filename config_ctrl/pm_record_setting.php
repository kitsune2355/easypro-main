<?php
// ตั้งค่าการบันทึกผล PM (ต่อหน่วยงาน) — ตั้งค่าที่ ตั้งค่าข้อมูล → แผน PM → การบันทึกผล PM (settings_pm_record.php)
//  sign_required : 1 = บังคับลายมือชื่อผู้ตรวจสอบ (ค่าเดิม) / 0 = ไม่บังคับ
//  date_mode     : 'today' = วันที่ทำ PM คือวันที่บันทึก (ค่าเดิม) / 'pick' = เลือกวันที่ได้ภายในรอบของแผน

if (!function_exists('pmrs_ensure')) {

    function pmrs_ensure($connect) {
        static $done = false;
        if ($done) return;
        $done = true;
        mysqli_query($connect, "CREATE TABLE IF NOT EXISTS tb_pm_record_setting (
            ag_id INT NOT NULL PRIMARY KEY,
            sign_required TINYINT(1) NOT NULL DEFAULT 1,
            date_mode VARCHAR(10) NOT NULL DEFAULT 'today',
            created_at DATETIME NULL,
            created_by VARCHAR(50) NULL,
            updated_at DATETIME NULL,
            updated_by VARCHAR(50) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    function pmrs_get($connect, $ag) {
        static $cache = [];
        $ag = (int)$ag;
        $def = ['sign_required' => 1, 'date_mode' => 'today'];
        if ($ag <= 0) return $def;
        if (isset($cache[$ag])) return $cache[$ag];
        pmrs_ensure($connect);
        $st = mysqli_prepare($connect, "SELECT sign_required, date_mode FROM tb_pm_record_setting WHERE ag_id = ? LIMIT 1");
        if ($st) {
            mysqli_stmt_bind_param($st, 'i', $ag);
            mysqli_stmt_execute($st);
            mysqli_stmt_bind_result($st, $sr, $dm);
            if (mysqli_stmt_fetch($st)) $def = ['sign_required' => (int)$sr ? 1 : 0, 'date_mode' => $dm === 'pick' ? 'pick' : 'today'];
            mysqli_stmt_close($st);
        }
        return $cache[$ag] = $def;
    }

    // ช่วงวันที่ทำ PM ที่เลือกได้ของกำหนดการหนึ่ง (รอบของแผน)
    //  ตั้งแต่ต้นเดือนของกำหนดการ ถึงสิ้นเดือนก่อนเดือนของรอบถัดไป
    //  เช่น ทุก 3 เดือน ก.ย. 69 → ธ.ค. 69 = 1 ก.ย. 69 – 30 พ.ย. 69 · รอบถัดไปอยู่เดือนเดียวกัน (รายสัปดาห์) = ถึงวันก่อนรอบถัดไป
    //  ไม่ทับรอบก่อนหน้า · ไม่เกินวันนี้ · รอบสุดท้ายถึงวันสิ้นสุดสัญญา · ยังไม่ถึงรอบ (ทำล่วงหน้า) = วันนี้เท่านั้น
    function pmrs_window($connect, $event_id, $contract_end = '') {
        $event_id = (int)$event_id;
        $res = mysqli_query($connect, "SELECT plan_id, event_date FROM pm_plan_events WHERE id = $event_id LIMIT 1");
        $ev = $res ? mysqli_fetch_assoc($res) : null;
        if (!$ev || !$ev['event_date']) return null;
        $plan = (int)$ev['plan_id'];
        $date = $ev['event_date'];
        $d = mysqli_real_escape_string($connect, $date);
        $res = mysqli_query($connect, "SELECT MAX(CASE WHEN event_date < '$d' THEN event_date END) AS prev_d, MIN(CASE WHEN event_date > '$d' THEN event_date END) AS next_d
                                       FROM pm_plan_events WHERE plan_id = $plan");
        $nb = $res ? mysqli_fetch_assoc($res) : [];
        $prev = isset($nb['prev_d']) ? $nb['prev_d'] : null;
        $next = isset($nb['next_d']) ? $nb['next_d'] : null;

        $min = date('Y-m-01', strtotime($date));
        if ($prev && $prev >= $min) $min = date('Y-m-d', strtotime($prev . ' +1 day'));
        if ($next) {
            $max = substr($next, 0, 7) !== substr($date, 0, 7)
                ? date('Y-m-d', strtotime(date('Y-m-01', strtotime($next)) . ' -1 day'))
                : date('Y-m-d', strtotime($next . ' -1 day'));
        } else {
            $max = ($contract_end && preg_match('/^\d{4}-\d{2}-\d{2}$/', $contract_end) && $contract_end >= $date) ? $contract_end : date('Y-m-t', strtotime($date));
        }
        $today = date('Y-m-d');
        if ($max > $today) $max = $today;
        $early = $min > $today;
        if ($early) $min = $max = $today;
        return ['plan_date' => $date, 'min' => $min, 'max' => $max, 'early' => $early];
    }

    // ค่าที่หน้าบันทึกผลใช้: การตั้งค่า + ช่วงวันที่ (เฉพาะโหมดเลือกวันที่)
    function pmrs_client($connect, $ag, $event_id = 0, $contract_end = '') {
        $s = pmrs_get($connect, $ag);
        $s['today'] = date('Y-m-d');
        $s['window'] = ($s['date_mode'] === 'pick' && $event_id) ? pmrs_window($connect, $event_id, $contract_end) : null;
        return $s;
    }

    // วันที่ทำ PM ที่จะบันทึก — โหมดวันนี้: วันนี้เสมอ · โหมดเลือก: ต้องอยู่ในรอบของแผน (ไม่ส่งมา = วันนี้ ถ้าอยู่ในรอบ)
    function pmrs_actual_date($connect, $ag, $event_id, $date, $contract_end = '') {
        $today = date('Y-m-d');
        $s = pmrs_get($connect, $ag);
        if ($s['date_mode'] !== 'pick') return $today;
        $w = pmrs_window($connect, $event_id, $contract_end);
        if (!$w) return $today;
        $date = trim((string)$date);
        if ($date === '') $date = $today < $w['min'] ? $w['min'] : ($today > $w['max'] ? $w['max'] : $today);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !checkdate((int)substr($date, 5, 2), (int)substr($date, 8, 2), (int)substr($date, 0, 4))) {
            throw new Exception('วันที่ทำ PM ไม่ถูกต้อง');
        }
        if ($date < $w['min'] || $date > $w['max']) {
            throw new Exception('วันที่ทำ PM ต้องอยู่ระหว่าง ' . pmrs_th($w['min']) . ' ถึง ' . pmrs_th($w['max']));
        }
        return $date;
    }

    function pmrs_th($ymd) {
        $m = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        $t = strtotime($ymd);
        return $t ? (int)date('j', $t) . ' ' . $m[(int)date('n', $t)] . ' ' . substr((string)((int)date('Y', $t) + 543), -2) : $ymd;
    }
}
