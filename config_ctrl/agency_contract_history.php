<?php
// ประวัติสัญญาของหน่วยงาน — 1 แถว = 1 รอบสัญญา (ต่อสัญญากี่ครั้ง ช่วงไหนบ้าง)
//  (ต่างจาก config_ctrl/agency_contract.php ซึ่งตรวจสัญญาหมดตอนเข้าสู่ระบบ)
//  ใช้กับ agency.php / api_agency.php
//  tb_agency.ag_start_date / ag_end_date = รอบสัญญาล่าสุดเสมอ (ซิงก์ให้อัตโนมัติ) — ส่วนอื่นของระบบ
//  (บล็อกการเข้าระบบเมื่อหมดสัญญา, ป้ายสัญญาหมดในแท็บ Monitor ฯลฯ) จึงทำงานเหมือนเดิม
//  สร้างตารางให้เองครั้งแรก + ย้ายวันเริ่ม–สิ้นสุดเดิมของทุกหน่วยงานเป็น "รอบที่ 1" (sql/2026-11-24_agency_contract_history.sql)
//  PHP 7.1+

if (!function_exists('ach_ensure')) {

    function ach_ensure($connect) {
        static $done = false;
        if ($done) return;
        $done = true;
        $rs = mysqli_query($connect, "SHOW TABLES LIKE 'tb_agency_contract'");
        $exists = $rs && mysqli_num_rows($rs) > 0;
        if ($exists) return;
        mysqli_query($connect, "CREATE TABLE IF NOT EXISTS tb_agency_contract (
            ac_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ag_id INT NOT NULL,
            start_date DATE NOT NULL COMMENT 'วันเริ่มสัญญา',
            end_date DATE NOT NULL COMMENT 'วันสิ้นสุดสัญญา',
            contract_no VARCHAR(100) NULL COMMENT 'เลขที่สัญญา',
            note VARCHAR(500) NULL COMMENT 'หมายเหตุ',
            created_at DATETIME NULL,
            created_by VARCHAR(50) NULL,
            updated_at DATETIME NULL,
            updated_by VARCHAR(50) NULL,
            KEY idx_ag_start (ag_id, start_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // ข้อมูลเดิม (ทำครั้งเดียวตอนสร้างตาราง): หน่วยงานที่มีวันเริ่ม/สิ้นสุด => รอบที่ 1
        mysqli_query($connect, "INSERT INTO tb_agency_contract (ag_id, start_date, end_date, note, created_at, created_by, updated_at, updated_by)
            SELECT a.ag_id, COALESCE(a.ag_start_date, a.ag_end_date), COALESCE(a.ag_end_date, a.ag_start_date),
                   'ย้ายจากวันเริ่ม–สิ้นสุดเดิมของหน่วยงาน', NOW(), 'system', NOW(), 'system'
            FROM tb_agency a
            WHERE (a.ag_start_date IS NOT NULL OR a.ag_end_date IS NOT NULL)
              AND NOT EXISTS (SELECT 1 FROM tb_agency_contract c WHERE c.ag_id = a.ag_id)");
    }

    // รอบสัญญาทั้งหมดของหน่วยงาน (เก่า -> ใหม่) + ลำดับรอบ
    function ach_list($connect, $ag) {
        ach_ensure($connect);
        $rows = [];
        $st = mysqli_prepare($connect, "SELECT ac_id, start_date, end_date, contract_no, note, created_at, created_by, updated_at, updated_by
            FROM tb_agency_contract WHERE ag_id = ? ORDER BY start_date, ac_id");
        mysqli_stmt_bind_param($st, 'i', $ag);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        $i = 0;
        while ($r = mysqli_fetch_assoc($rs)) { $r['ac_id'] = (int)$r['ac_id']; $r['round'] = ++$i; $rows[] = $r; }
        mysqli_stmt_close($st);
        return $rows;
    }

    // รอบล่าสุด (วันสิ้นสุดมากสุด) จาก ach_list() หรือ null
    function ach_latest($rows) {
        $best = null;
        foreach ($rows as $r) if (!$best || $r['end_date'] > $best['end_date'] || ($r['end_date'] === $best['end_date'] && $r['start_date'] > $best['start_date'])) $best = $r;
        return $best;
    }

    // หน่วยงานที่มีวันเริ่ม/สิ้นสุดแต่ยังไม่มีประวัติสัญญา (เพิ่งสร้าง / เพิ่งใส่วันครั้งแรก) => รอบที่ 1
    function ach_ensure_first_round($connect, $ag, $by) {
        $now = date('Y-m-d H:i:s');
        $st = mysqli_prepare($connect, "INSERT INTO tb_agency_contract (ag_id, start_date, end_date, created_at, created_by, updated_at, updated_by)
            SELECT a.ag_id, COALESCE(a.ag_start_date, a.ag_end_date), COALESCE(a.ag_end_date, a.ag_start_date), ?, ?, ?, ?
            FROM tb_agency a
            WHERE a.ag_id = ? AND (a.ag_start_date IS NOT NULL OR a.ag_end_date IS NOT NULL)
              AND NOT EXISTS (SELECT 1 FROM tb_agency_contract c WHERE c.ag_id = a.ag_id)");
        mysqli_stmt_bind_param($st, 'ssssi', $now, $by, $now, $by, $ag);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
    }

    // จำนวนรอบสัญญาของทุกหน่วยงาน [ag_id => n]
    function ach_counts($connect) {
        ach_ensure($connect);
        $out = [];
        $rs = mysqli_query($connect, "SELECT ag_id, COUNT(*) n FROM tb_agency_contract GROUP BY ag_id");
        if ($rs) while ($r = mysqli_fetch_assoc($rs)) $out[(int)$r['ag_id']] = (int)$r['n'];
        return $out;
    }

    // ตั้ง tb_agency.ag_start_date / ag_end_date = รอบสัญญาล่าสุด (วันสิ้นสุดมากสุด) — ไม่มีรอบเลย => คงค่าเดิม
    function ach_sync($connect, $ag) {
        $st = mysqli_prepare($connect, "SELECT start_date, end_date FROM tb_agency_contract WHERE ag_id = ? ORDER BY end_date DESC, start_date DESC, ac_id DESC LIMIT 1");
        mysqli_stmt_bind_param($st, 'i', $ag);
        mysqli_stmt_execute($st);
        mysqli_stmt_bind_result($st, $s, $e);
        $has = mysqli_stmt_fetch($st);
        mysqli_stmt_close($st);
        if (!$has) return;
        $st = mysqli_prepare($connect, "UPDATE tb_agency SET ag_start_date = ?, ag_end_date = ? WHERE ag_id = ?");
        mysqli_stmt_bind_param($st, 'ssi', $s, $e, $ag);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
    }

    // รอบที่ช่วงวันทับกับช่วงที่ระบุ (ไม่นับรอบ $exceptId) — คืนแถวแรกที่ทับ หรือ null
    function ach_overlap($connect, $ag, $start, $end, $exceptId = 0) {
        $st = mysqli_prepare($connect, "SELECT ac_id, start_date, end_date FROM tb_agency_contract
            WHERE ag_id = ? AND ac_id <> ? AND start_date <= ? AND end_date >= ? ORDER BY start_date LIMIT 1");
        mysqli_stmt_bind_param($st, 'iiss', $ag, $exceptId, $end, $start);
        mysqli_stmt_execute($st);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        mysqli_stmt_close($st);
        return $row ?: null;
    }
}
