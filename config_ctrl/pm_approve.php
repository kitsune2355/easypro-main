<?php
// ตรวจรับงาน PM โดยหัวหน้าช่าง (เปิด/ปิดต่อหน่วยงานที่ ตั้งค่าข้อมูล → แผน PM → การบันทึกผล PM)
//  pm_plan_events.approval_status : NULL = ไม่ต้องตรวจรับ (หน่วยงานไม่เปิด / งานก่อนเปิดใช้) · 0 = รอตรวจรับ · 1 = ตรวจรับแล้ว
//  status ยังเป็น 1 เมื่อช่างบันทึกผล (มีใบงานแล้ว) — "เสร็จ" = status 1 และไม่ได้รอตรวจรับ (pmap_done_sql)
//  ส่งกลับแก้ไข = ยกเลิกผลบันทึก (pmwc_cancel) => ช่างบันทึกใหม่ (ใช้เลขที่เอกสารเดิม)
//  pm_approvers       : ผู้ตรวจรับของหน่วยงาน (super_admin ตรวจรับได้เสมอ)
//  pm_work_approvals  : ประวัติ ตรวจรับ (พร้อมลายมือชื่อผู้ตรวจรับ signature_path) / ส่งกลับ

if (!function_exists('pmap_ensure')) {

    // สร้างตาราง/คอลัมน์ (อย่าเรียกระหว่าง transaction — DDL จะ commit อัตโนมัติ)
    function pmap_ensure($connect) {
        static $done = false;
        if ($done) return;
        $done = true;
        try {
            mysqli_query($connect, "CREATE TABLE IF NOT EXISTS pm_approvers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ag_id INT NOT NULL,
                user_id VARCHAR(50) NOT NULL,
                created_at DATETIME NULL,
                created_by VARCHAR(50) NULL,
                updated_at DATETIME NULL,
                updated_by VARCHAR(50) NULL,
                UNIQUE KEY uq_ag_user (ag_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            mysqli_query($connect, "CREATE TABLE IF NOT EXISTS pm_work_approvals (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ag_id VARCHAR(50) NOT NULL,
                plan_event_id INT NOT NULL,
                work_record_id INT NULL,
                doc_no VARCHAR(100) NULL,
                action VARCHAR(10) NOT NULL,
                comment TEXT NULL,
                approver_name VARCHAR(255) NULL,
                signature_path VARCHAR(500) NULL,
                created_at DATETIME NULL,
                created_by VARCHAR(50) NULL,
                updated_at DATETIME NULL,
                updated_by VARCHAR(50) NULL,
                KEY idx_event (plan_event_id),
                KEY idx_ag (ag_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            // ตารางที่สร้างก่อนมีลายมือชื่อผู้ตรวจรับ
            $r = mysqli_query($connect, "SHOW COLUMNS FROM pm_work_approvals LIKE 'signature_path'");
            if ($r && !mysqli_num_rows($r)) @mysqli_query($connect, "ALTER TABLE pm_work_approvals ADD COLUMN signature_path VARCHAR(500) NULL AFTER approver_name");
            if (!pmap_has_col($connect)) {
                $ok = @mysqli_query($connect, "ALTER TABLE pm_plan_events
                    ADD COLUMN approval_status TINYINT(1) NULL DEFAULT NULL,
                    ADD COLUMN approval_id INT NULL DEFAULT NULL,
                    ADD COLUMN approved_at DATETIME NULL DEFAULT NULL");
                if ($ok) pmap_has_col($connect, true);
            }
        } catch (Exception $e) {
            // ไม่มีสิทธิ์ ALTER/CREATE => ใช้งานแบบเดิม (ไม่มีขั้นตรวจรับ) — รัน sql/2026-10-28_pm_approve.sql แทน
        }
    }

    // มีคอลัมน์ approval_status แล้วหรือยัง ($refresh = ตรวจใหม่หลัง ALTER)
    function pmap_has_col($connect, $refresh = false) {
        static $has = null;
        if ($has === null || $refresh) {
            try {
                $r = mysqli_query($connect, "SHOW COLUMNS FROM pm_plan_events LIKE 'approval_status'");
                $has = $r && mysqli_num_rows($r) > 0;
            } catch (Exception $e) { $has = false; }
        }
        return $has;
    }

    // เงื่อนไข SQL: งาน "เสร็จ" (บันทึกผลแล้วและไม่ได้รอตรวจรับ) / งาน "รอตรวจรับ" — $a = alias ของ pm_plan_events
    function pmap_done_sql($connect, $a = 'e') {
        $p = $a !== '' ? $a . '.' : '';
        return pmap_has_col($connect) ? "({$p}status = 1 AND IFNULL({$p}approval_status, 1) = 1)" : "({$p}status = 1)";
    }
    function pmap_wait_sql($connect, $a = 'e') {
        $p = $a !== '' ? $a . '.' : '';
        return pmap_has_col($connect) ? "({$p}status = 1 AND {$p}approval_status = 0)" : "(0 = 1)";
    }
    // คอลัมน์สำหรับ SELECT (ไม่มีคอลัมน์ => NULL)
    function pmap_col_sql($connect, $a = 'e') {
        return pmap_has_col($connect) ? "$a.approval_status" : "NULL";
    }

    function pmap_is_approver($connect, $ag, $uid) {
        if (($_SESSION['sess_user_level_es'] ?? '') === 'super_admin') return true;
        $ag = (int)$ag;
        $uid = mysqli_real_escape_string($connect, (string)$uid);
        try {
            $r = mysqli_query($connect, "SELECT 1 FROM pm_approvers WHERE ag_id = $ag AND user_id = '$uid' LIMIT 1");
            return $r && mysqli_num_rows($r) > 0;
        } catch (Exception $e) { return false; }
    }

    // ลายมือชื่อผู้ตรวจรับ: data URL => ไฟล์ใน uploads/pm_work_records/approve_signatures/ปี/เดือน/วัน (คืน path หรือ null)
    function pmap_save_signature($b64) {
        if (!preg_match('#^data:image/(png|jpe?g|webp);base64,#', (string)$b64, $m)) return null;
        $data = base64_decode(substr($b64, strpos($b64, ',') + 1));
        if ($data === false || strlen($data) < 50) return null;
        $dir = 'uploads/pm_work_records/approve_signatures/' . date('Y/m/d') . '/';
        if (!is_dir(dirname(__DIR__) . '/' . $dir)) @mkdir(dirname(__DIR__) . '/' . $dir, 0777, true);
        $file = $dir . uniqid('approve_sign_', true) . '_' . time() . '.' . ($m[1] === 'jpeg' ? 'jpg' : $m[1]);
        return file_put_contents(dirname(__DIR__) . '/' . $file, $data) ? $file : null;
    }

    // path ลายเซ็นที่ส่งกลับมาใช้ซ้ำ (ตรวจรับหลายชุด) ต้องอยู่ในโฟลเดอร์ลายเซ็นผู้ตรวจรับเท่านั้น
    function pmap_valid_signature_path($path) {
        $base = realpath(dirname(__DIR__) . '/uploads/pm_work_records/approve_signatures');
        $real = realpath(dirname(__DIR__) . '/' . $path);
        return $base && $real && strpos($real, $base) === 0 && is_file($real);
    }

    function pmap_approver_count($connect, $ag) {
        $ag = (int)$ag;
        try {
            $r = mysqli_query($connect, "SELECT COUNT(*) AS n FROM pm_approvers WHERE ag_id = $ag");
            return $r ? (int)mysqli_fetch_assoc($r)['n'] : 0;
        } catch (Exception $e) { return 0; }
    }

    // ผู้ตรวจรับล่าสุดของกลุ่มกำหนดการ (รายงาน PM รายเดือน) => ['approver_name', 'signature_path', 'created_at', 'date_th'] หรือ null
    function pmap_latest_approval($connect, $eventIds) {
        $ids = array_values(array_filter(array_map('intval', (array)$eventIds)));
        if (!$ids || !pmap_has_col($connect)) return null;
        try {
            $r = mysqli_query($connect, "SELECT a.approver_name, a.signature_path, a.created_at FROM pm_plan_events e
                JOIN pm_work_approvals a ON a.id = e.approval_id
                WHERE e.id IN (" . implode(',', $ids) . ") AND e.approval_status = 1 ORDER BY a.created_at DESC, a.id DESC LIMIT 1");
            $row = $r ? mysqli_fetch_assoc($r) : null;
        } catch (Exception $e) { return null; }
        if (!$row) return null;
        $t = strtotime($row['created_at']);
        $row['date_th'] = $t ? date('d/m/', $t) . (date('Y', $t) + 543) : '';
        return $row;
    }

    // ผู้ประเมินล่าสุดที่มีลายเซ็น ของกลุ่มกำหนดการ (ช่อง ทวนสอบ / ผู้อนุมัติ ในใบพิมพ์และรายงาน PM)
    //  => ['evaluator_name', 'evaluator_signature_path', 'eval_date', 'date_th'] หรือ null
    function pmap_latest_evaluation($connect, $eventIds) {
        $ids = array_values(array_filter(array_map('intval', (array)$eventIds)));
        if (!$ids) return null;
        try {
            $r = mysqli_query($connect, "SELECT ev.evaluator_name, ev.evaluator_signature_path, ev.eval_date FROM pm_plan_events e
                JOIN pm_work_evaluations ev ON ev.id = e.evaluation_id
                WHERE e.id IN (" . implode(',', $ids) . ") AND e.evaluation_status = 1 AND IFNULL(ev.evaluator_signature_path, '') <> ''
                ORDER BY ev.eval_date DESC, ev.id DESC LIMIT 1");
            $row = $r ? mysqli_fetch_assoc($r) : null;
        } catch (Exception $e) { return null; }
        if (!$row) return null;
        $t = strtotime($row['eval_date']);
        $row['date_th'] = $t ? date('d/m/', $t) . (date('Y', $t) + 543) : '';
        return $row;
    }

    // จำนวนงานที่รอตรวจรับของหน่วยงาน
    function pmap_pending_count($connect, $ag) {
        if (!pmap_has_col($connect)) return 0;
        $agEsc = mysqli_real_escape_string($connect, (string)$ag);
        $r = mysqli_query($connect, "SELECT COUNT(*) AS n FROM pm_plan_events WHERE ag_id = '$agEsc' AND status = 1 AND approval_status = 0");
        return $r ? (int)mysqli_fetch_assoc($r)['n'] : 0;
    }
}
