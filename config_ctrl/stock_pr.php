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
            approver_id VARCHAR(50) NULL COMMENT 'ผู้อนุมัติ (tb_user.user_id)',
            approver_name VARCHAR(150) NULL COMMENT 'ชื่อผู้อนุมัติ ณ วันที่บันทึก',
            status TINYINT NOT NULL DEFAULT 1 COMMENT '1 = ใช้งาน, 0 = ยกเลิก',
            cancel_reason VARCHAR(500) NULL COMMENT 'เหตุผลที่ยกเลิก',
            cancelled_at DATETIME NULL,
            cancelled_by VARCHAR(50) NULL,
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
        // ตารางที่สร้างก่อนมีเหตุผลการยกเลิก => เพิ่มคอลัมน์
        $cols = [];
        $rs = mysqli_query($connect, "SHOW COLUMNS FROM tb_stock_pr");
        if ($rs) { while ($c = mysqli_fetch_assoc($rs)) $cols[$c['Field']] = true; mysqli_free_result($rs); }
        if ($cols && !isset($cols['cancel_reason'])) mysqli_query($connect, "ALTER TABLE tb_stock_pr ADD COLUMN cancel_reason VARCHAR(500) NULL COMMENT 'เหตุผลที่ยกเลิก' AFTER status, ADD COLUMN cancelled_at DATETIME NULL AFTER cancel_reason, ADD COLUMN cancelled_by VARCHAR(50) NULL AFTER cancelled_at");
        // ผู้อนุมัติของใบ (จาก settings_pr_approvers.php): NULL = ใบเก่าก่อนมีช่องนี้, '' = ไม่ระบุ
        if ($cols && !isset($cols['approver_id'])) mysqli_query($connect, "ALTER TABLE tb_stock_pr ADD COLUMN approver_id VARCHAR(50) NULL COMMENT 'ผู้อนุมัติ (tb_user.user_id)' AFTER req_name, ADD COLUMN approver_name VARCHAR(150) NULL COMMENT 'ชื่อผู้อนุมัติ ณ วันที่บันทึก' AFTER approver_id");
        // ลายเซ็นในใบพิมพ์ (stock_pr_print.php): รูป PNG แบบ data URL — เก็บในฐานข้อมูล ไม่เปิดอ่านจากโฟลเดอร์อัปโหลดได้
        if ($cols && !isset($cols['req_sign'])) {
            try {
                @mysqli_query($connect, "ALTER TABLE tb_stock_pr
                    ADD COLUMN req_sign MEDIUMTEXT NULL COMMENT 'ลายเซ็นผู้ขอซื้อ', ADD COLUMN req_signed_at DATETIME NULL, ADD COLUMN req_signed_by VARCHAR(50) NULL,
                    ADD COLUMN ap_sign MEDIUMTEXT NULL COMMENT 'ลายเซ็นผู้อนุมัติ', ADD COLUMN ap_signed_at DATETIME NULL, ADD COLUMN ap_signed_by VARCHAR(50) NULL");
            } catch (Exception $e) {
                // ไม่มีสิทธิ์ ALTER => ใช้งานแบบเดิม (เซ็นบนกระดาษ) — รัน sql/2026-11-24_stock_pr_sign.sql
            }
        }
    }

    // มีคอลัมน์ลายเซ็นแล้วหรือยัง (เรียกหลัง spr_ensure)
    function spr_has_sign($connect) {
        static $has = null;
        if ($has === null) {
            $r = mysqli_query($connect, "SHOW COLUMNS FROM tb_stock_pr LIKE 'ap_signed_by'");
            $has = $r && mysqli_num_rows($r) > 0;
        }
        return $has;
    }

    // ชื่อเต็มของผู้ใช้
    function spr_user_name($connect, $uid) {
        $name = '';
        $st = mysqli_prepare($connect, "SELECT TRIM(CONCAT(COALESCE(user_name, ''), ' ', COALESCE(user_fname, ''))) FROM tb_user WHERE user_id = ? LIMIT 1");
        mysqli_stmt_bind_param($st, 's', $uid);
        mysqli_stmt_execute($st);
        mysqli_stmt_bind_result($st, $name);
        mysqli_stmt_fetch($st);
        mysqli_stmt_close($st);
        return (string)$name !== '' ? (string)$name : (string)$uid;
    }

    // ชื่อในใบเป็น ชื่อ-นามสกุล: ใบเก็บแค่ชื่อ (บัญชีตอนนั้นยังไม่มีนามสกุล) แล้วเติมนามสกุลในบัญชีภายหลัง => ใช้ชื่อเต็มของบัญชี
    function spr_full_name($connect, $name, $uid) {
        $name = trim(preg_replace('/\s+/u', ' ', (string)$name));
        if ($name === '' || (string)$uid === '' || spr_has_surname($name)) return $name;
        $full = spr_user_name($connect, $uid);
        return strpos($full, $name . ' ') === 0 ? trim(preg_replace('/\s+/u', ' ', $full)) : $name;
    }
    // มีทั้งชื่อและนามสกุล (เว้นวรรคคั่น)
    function spr_has_surname($name) { return (bool)preg_match('/\S\s+\S/u', trim((string)$name)); }

    // สถานะของใบ: cancelled = ยกเลิก · approved = ผู้อนุมัติลงนามแล้ว · pending = รออนุมัติ
    function spr_state($row) {
        if ((int)$row['status'] !== 1) return 'cancelled';
        return trim((string)($row['ap_signed_by'] ?? '')) !== '' ? 'approved' : 'pending';
    }
    // เงื่อนไข SQL ของสถานะ (ตาราง h)
    function spr_state_sql($connect, $state) {
        if ($state === 'cancelled') return 'h.status <> 1';
        $signed = spr_has_sign($connect) ? "COALESCE(h.ap_signed_by, '') <> ''" : '0 = 1';
        return $state === 'approved' ? "(h.status = 1 AND $signed)" : "(h.status = 1 AND NOT ($signed))";
    }

    // ช่องลงนามที่ผู้ใช้ที่ล็อกอินเซ็นได้ ['req' => bool, 'ap' => bool] (ไม่ดูว่าเซ็นไปแล้วหรือยัง)
    //  ผู้ขอซื้อ : ผู้สร้างใบ / ผู้ดูแลระบบ (ตำแหน่งช่างไม่ได้ — เหมือนสิทธิ์แก้ไขใบ)
    //  ผู้อนุมัติ: คนที่ระบุในใบ · ใบไม่ระบุ = คนใดก็ได้ในรายชื่อผู้อนุมัติของหน่วยงาน · super_admin ได้เสมอ (ชื่อในใบเปลี่ยนเป็นผู้เซ็น)
    //  ต้อง include stock_perm.php และ stock_pr_approver.php ก่อน
    function spr_sign_rights($connect, $ag, $pr, $me, $level) {
        $out = ['req' => false, 'ap' => false];
        if ((int)$pr['status'] !== 1 || (string)$me === '' || !spr_has_sign($connect)) return $out;
        $out['req'] = stock_can_manage($connect) && (in_array($level, ['admin', 'super_admin'], true) || (string)$pr['created_by'] === (string)$me);
        $apId = trim((string)($pr['approver_id'] ?? ''));
        $out['ap'] = $level === 'super_admin' || (spra_is_approver($connect, $ag, $me) && ($apId === '' || $apId === (string)$me));
        return $out;
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
