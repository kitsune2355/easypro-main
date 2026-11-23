<?php
// ใบขออนุมัติจัดซื้อ (PR) ของระบบจัดการสต็อก — เมนู "ใบขอซื้อ (PR)" ใน stock.php / handle_stock_pr.php / stock_pr_print.php
//  ตาราง: tb_stock_pr (หัวเอกสาร) + tb_stock_pr_item (รายการ) — สร้างให้เองครั้งแรก (sql/2026-11-23_stock_pr.sql)
//  เลขที่เอกสาร: PR-YYMM-NNNN (YY = ปี พ.ศ. 2 หลัก, MM = เดือน) นับใหม่ทุกเดือน แยกตามหน่วยงาน
//  PHP 7.1+

if (!function_exists('spr_ensure')) {

    function spr_ensure($connect) {
        static $done = false;
        if ($done) return;
        $done = true;
        mysqli_query($connect, "CREATE TABLE IF NOT EXISTS tb_stock_pr (
            pr_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ag_id INT NOT NULL,
            pr_no VARCHAR(20) NOT NULL COMMENT 'เลขที่ PR-YYMM-NNNN',
            pr_date DATE NOT NULL COMMENT 'วันที่เอกสาร',
            deliver_date DATE NULL COMMENT 'จัดส่งภายในวันที่',
            remark TEXT NULL COMMENT 'หมายเหตุท้ายเอกสาร',
            req_name VARCHAR(150) NULL COMMENT 'ผู้ขอซื้อ/ขอจ้าง',
            status TINYINT NOT NULL DEFAULT 1 COMMENT '1 = ใช้งาน, 0 = ยกเลิก',
            created_at DATETIME NULL,
            created_by VARCHAR(50) NULL,
            updated_at DATETIME NULL,
            updated_by VARCHAR(50) NULL,
            UNIQUE KEY uq_ag_prno (ag_id, pr_no),
            KEY idx_ag_date (ag_id, pr_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        mysqli_query($connect, "CREATE TABLE IF NOT EXISTS tb_stock_pr_item (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            pr_id INT NOT NULL,
            line_no INT NOT NULL,
            pd_id INT NULL COMMENT 'tb_repair_product.pd_id',
            pd_code VARCHAR(100) NULL COMMENT 'รหัสสินค้า ณ วันที่ออกเอกสาร',
            pd_name TEXT NULL COMMENT 'รายการ ณ วันที่ออกเอกสาร',
            pd_unit VARCHAR(100) NULL,
            qty DECIMAL(12,2) NOT NULL DEFAULT 0,
            note VARCHAR(255) NULL COMMENT 'หมายเหตุรายการ',
            created_at DATETIME NULL,
            created_by VARCHAR(50) NULL,
            updated_at DATETIME NULL,
            updated_by VARCHAR(50) NULL,
            KEY idx_pr (pr_id, line_no)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // คำนำหน้าเลขที่ของวันที่เอกสาร เช่น 2026-11-10 => PR-6911-
    function spr_prefix($date) {
        $t = strtotime($date) ?: time();
        return 'PR-' . substr((string)((int)date('Y', $t) + 543), -2) . date('m', $t) . '-';
    }

    // เลขที่ถัดไปของหน่วยงาน (ไม่จองเลข — ใช้แสดงตัวอย่าง; ตอนบันทึกใช้ spr_insert_no)
    function spr_next_no($connect, $ag, $date) {
        $prefix = spr_prefix($date);
        $like = $prefix . '%';
        $n = 0;
        $st = mysqli_prepare($connect, "SELECT MAX(CAST(SUBSTRING(pr_no, ?) AS UNSIGNED)) FROM tb_stock_pr WHERE ag_id = ? AND pr_no LIKE ?");
        $pos = strlen($prefix) + 1;
        mysqli_stmt_bind_param($st, 'iis', $pos, $ag, $like);
        mysqli_stmt_execute($st);
        mysqli_stmt_bind_result($st, $max);
        if (mysqli_stmt_fetch($st)) $n = (int)$max;
        mysqli_stmt_close($st);
        return $prefix . str_pad((string)($n + 1), 4, '0', STR_PAD_LEFT);
    }

    // ข้อความวันที่ไทย เช่น 10 พฤศจิกายน 2569
    function spr_thai_date($date) {
        if (!$date || $date === '0000-00-00') return '';
        $t = strtotime($date);
        if (!$t) return '';
        $m = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        return (int)date('j', $t) . ' ' . $m[(int)date('n', $t)] . ' ' . ((int)date('Y', $t) + 543);
    }
}
