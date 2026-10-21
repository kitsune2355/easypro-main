<?php
// ยกเลิกผลบันทึก PM แล้วส่งกลับให้ทำใหม่ — ตาราง log + ตัวช่วยที่ใช้ร่วมกัน
//  handle_pm_work_cancel.php (ยกเลิก), handle_pm_worksheet.php (บันทึกใหม่ใช้เลขที่เอกสารเดิม / แสดงครั้งที่แก้ไข), pm_worksheet_sheet.php (พิมพ์)

if (!function_exists('pmwc_ensure')) {

    // สร้างตาราง (อย่าเรียกระหว่าง transaction — CREATE TABLE จะ commit อัตโนมัติ)
    function pmwc_ensure($connect) {
        mysqli_query($connect, "CREATE TABLE IF NOT EXISTS pm_work_record_cancel_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ag_id VARCHAR(50) NOT NULL,
            plan_event_id INT NOT NULL,
            plan_id INT NULL,
            work_record_id INT NULL,
            doc_no VARCHAR(100) NULL,
            revision INT NOT NULL DEFAULT 1,
            reason TEXT NOT NULL,
            snapshot_json MEDIUMTEXT NULL,
            created_at DATETIME NOT NULL,
            created_by VARCHAR(50) NULL,
            updated_at DATETIME NULL,
            updated_by VARCHAR(50) NULL,
            KEY idx_event (plan_event_id),
            KEY idx_ag (ag_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    function pmwc_has_table($connect) {
        static $has = null;
        if ($has === null) {
            $r = mysqli_query($connect, "SHOW TABLES LIKE 'pm_work_record_cancel_log'");
            $has = $r && mysqli_num_rows($r) > 0;
        }
        return $has;
    }

    // ประวัติการยกเลิกของกำหนดการ (ใหม่สุดก่อน)
    function pmwc_logs($connect, $event_id) {
        $event_id = (int)$event_id;
        if ($event_id <= 0 || !pmwc_has_table($connect)) return [];
        $rows = [];
        $r = mysqli_query($connect, "SELECT id, doc_no, revision, reason, created_at, created_by,
                (SELECT u.user_name FROM tb_user u WHERE u.user_id = l.created_by LIMIT 1) AS created_by_name
            FROM pm_work_record_cancel_log l WHERE plan_event_id = $event_id ORDER BY id DESC");
        if ($r) while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
        return $rows;
    }

    // ครั้งที่แก้ไขของใบงานปัจจุบัน (= จำนวนครั้งที่ถูกยกเลิก)
    function pmwc_revision($connect, $event_id) {
        $event_id = (int)$event_id;
        if ($event_id <= 0 || !pmwc_has_table($connect)) return 0;
        $r = mysqli_query($connect, "SELECT COUNT(*) AS n FROM pm_work_record_cancel_log WHERE plan_event_id = $event_id");
        return $r ? (int)mysqli_fetch_assoc($r)['n'] : 0;
    }

    // ลบไฟล์ของใบงานที่ลบไปแล้ว (เรียกหลัง commit) — ลบเฉพาะไฟล์ใต้ uploads/ ที่ไม่มีแถวอื่นอ้างถึงอยู่
    //  (ข้อมูลตัวอย่าง/สำเนาบางชุดใช้ไฟล์เดียวกันหลายใบงาน)
    function pmwc_unlink_unused($connect, $files, $root) {
        $refs = [
            ['pm_work_record_items', 'result_photo_path'], ['pm_work_records', 'inspector_signature_path'],
            ['pm_work_evaluations', 'evaluator_signature_path'], ['pm_work_record_files_snapshot', 'file_path'],
            ['pm_checksheet_files', 'file_path'], ['pm_checksheet_items', 'illustration_path'],
        ];
        $n = 0;
        foreach (array_unique(array_map('strval', $files)) as $f) {
            if ($f === '' || strpos($f, 'uploads/') !== 0 || strpos($f, '..') !== false || !is_file($root . '/' . $f)) continue;
            $esc = mysqli_real_escape_string($connect, $f);
            $used = false;
            foreach ($refs as $rc) {
                $r = mysqli_query($connect, "SELECT 1 FROM {$rc[0]} WHERE {$rc[1]} = '$esc' LIMIT 1");
                if ($r && mysqli_num_rows($r) > 0) { $used = true; break; }
            }
            if (!$used && @unlink($root . '/' . $f)) $n++;
        }
        return $n;
    }

    // บันทึกใหม่หลังยกเลิก => ใช้เลขที่เอกสารเดิม (ถ้าไม่มีใบงานอื่นใช้เลขนั้นอยู่)
    function pmwc_reuse_doc_no($connect, $event_id, $ag_id) {
        $event_id = (int)$event_id;
        if ($event_id <= 0 || !pmwc_has_table($connect)) return null;
        $r = mysqli_query($connect, "SELECT doc_no FROM pm_work_record_cancel_log WHERE plan_event_id = $event_id AND doc_no IS NOT NULL AND doc_no <> '' ORDER BY id DESC LIMIT 1");
        $row = $r ? mysqli_fetch_assoc($r) : null;
        if (!$row) return null;
        $doc = mysqli_real_escape_string($connect, $row['doc_no']);
        $agEsc = mysqli_real_escape_string($connect, (string)$ag_id);
        $dup = mysqli_query($connect, "SELECT 1 FROM pm_work_records WHERE doc_no = '$doc' AND ag_id = '$agEsc' LIMIT 1");
        if ($dup && mysqli_num_rows($dup) > 0) return null;
        return $row['doc_no'];
    }
}
