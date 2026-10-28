<?php
// ตั้งค่าการบันทึกผล PM (ต่อหน่วยงาน) — ตั้งค่าที่ ตั้งค่าข้อมูล → แผน PM → การบันทึกผล PM (settings_pm_record.php)
//  sign_required : 1 = บังคับลายมือชื่อผู้ตรวจสอบ (ค่าเดิม) / 0 = ไม่บังคับ
//  date_mode     : 'today' = วันที่ทำ PM คือวันที่บันทึก (ค่าเดิม) / 'pick' = เลือกวันที่ได้ภายในช่วงสัญญา/แผนของหน่วยงาน

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

    // ช่วงวันที่ทำ PM ที่เลือกได้ของกำหนดการหนึ่ง = ช่วงสัญญา/แผนของหน่วยงาน (tb_agency.ag_start_date – ag_end_date) และไม่เกินวันนี้
    //  เช่น สัญญา 1 มิ.ย. 69 – 31 พ.ค. 70 วันนี้ 28 ต.ค. 69 => เลือกได้ 1 มิ.ย. 69 – 28 ต.ค. 69 ทุกใบงาน
    //  ไม่มีวันเริ่ม/สิ้นสุดสัญญา => ใช้กำหนดการแรก/สุดท้ายของแผน · ยังไม่ถึงวันเริ่มสัญญา = วันนี้เท่านั้น
    //  ($contract_end ใช้เมื่อหาข้อมูลหน่วยงานไม่ได้)
    function pmrs_window($connect, $event_id, $contract_end = '') {
        $event_id = (int)$event_id;
        $res = mysqli_query($connect, "SELECT e.plan_id, e.event_date, ag.ag_start_date, ag.ag_end_date
                                       FROM pm_plan_events e LEFT JOIN tb_agency ag ON ag.ag_id = e.ag_id
                                       WHERE e.id = $event_id LIMIT 1");
        $ev = $res ? mysqli_fetch_assoc($res) : null;
        if (!$ev || !$ev['event_date']) return null;
        $isDate = function ($d) { return $d && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && $d !== '0000-00-00'; };

        $min = $isDate($ev['ag_start_date']) ? $ev['ag_start_date'] : '';
        $max = $isDate($ev['ag_end_date']) ? $ev['ag_end_date'] : ($isDate($contract_end) ? $contract_end : '');
        if ($min === '' || $max === '') {
            $plan = (int)$ev['plan_id'];
            $r = mysqli_query($connect, "SELECT MIN(event_date) AS f, MAX(event_date) AS l FROM pm_plan_events WHERE plan_id = $plan");
            $fl = $r ? mysqli_fetch_assoc($r) : [];
            if ($min === '') $min = date('Y-m-01', strtotime(!empty($fl['f']) ? $fl['f'] : $ev['event_date']));
            if ($max === '') $max = date('Y-m-t', strtotime(!empty($fl['l']) ? $fl['l'] : $ev['event_date']));
        }
        $today = date('Y-m-d');
        if ($max > $today) $max = $today;
        $early = $min > $max;
        if ($early) $min = $max = $today;
        return ['plan_date' => $ev['event_date'], 'min' => $min, 'max' => $max, 'early' => $early];
    }

    // ค่าที่หน้าบันทึกผลใช้: การตั้งค่า + ช่วงวันที่ (เฉพาะโหมดเลือกวันที่)
    function pmrs_client($connect, $ag, $event_id = 0, $contract_end = '') {
        $s = pmrs_get($connect, $ag);
        $s['today'] = date('Y-m-d');
        $s['window'] = ($s['date_mode'] === 'pick' && $event_id) ? pmrs_window($connect, $event_id, $contract_end) : null;
        return $s;
    }

    // วันที่ทำ PM ที่จะบันทึก — โหมดวันนี้: วันนี้เสมอ · โหมดเลือก: ต้องอยู่ในช่วงสัญญา/แผน (ไม่ส่งมา = วันนี้ ถ้าอยู่ในรอบ)
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
