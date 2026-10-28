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

    // ยกเลิกผลบันทึกของกำหนดการ แล้วตั้งกลับเป็น "ยังไม่ทำ" — ใช้ทั้งผู้ดูแล (handle_pm_work_cancel.php) และหัวหน้าช่างส่งกลับแก้ไข (handle_pm_approve.php)
    //  ลบใบงานเดิม (ผลตรวจ รูป ลายเซ็น ผลประเมิน) คืนสต็อกอะไหล่ เก็บ log + สรุปผลเดิม · ไม่ตรวจสิทธิ์ (ผู้เรียกตรวจเอง) · อย่าเรียกระหว่าง transaction
    //  คืนค่า ['success' => true, 'doc_no', 'revision', 'spares_returned'] หรือ ['success' => false, 'error']
    function pmwc_cancel($connect, $event_id, $ag, $me, $reason) {
        $event_id = (int)$event_id;
        $agEsc = mysqli_real_escape_string($connect, (string)$ag);
        $r = mysqli_query($connect, "SELECT e.id, e.plan_id, e.evaluation_status FROM pm_plan_events e WHERE e.id = $event_id AND e.ag_id = '$agEsc'");
        $ev = $r ? mysqli_fetch_assoc($r) : null;
        if (!$ev) return ['success' => false, 'error' => 'ไม่พบงาน PM นี้ในหน่วยงานที่เลือก'];
        $r = mysqli_query($connect, "SELECT * FROM pm_work_records WHERE plan_event_id = $event_id LIMIT 1");
        $wr = $r ? mysqli_fetch_assoc($r) : null;
        if (!$wr) return ['success' => false, 'error' => 'งานนี้ยังไม่มีผลบันทึก'];
        $wrId = (int)$wr['id'];
        $spares = [];
        $r = mysqli_query($connect, "SELECT rpd_product_id AS pd, wh_id AS wh, SUM(rpd_qty) AS qty, MAX(rpd_details) AS name
            FROM pm_work_record_actual_spares WHERE work_record_id = $wrId AND rpd_product_id > 0 AND wh_id > 0 GROUP BY rpd_product_id, wh_id");
        if ($r) while ($row = mysqli_fetch_assoc($r)) $spares[] = $row;
        $r = mysqli_query($connect, "SELECT 1 FROM pm_work_evaluations WHERE work_record_id = $wrId LIMIT 1");
        $evaluated = (int)$ev['evaluation_status'] === 1 || ($r && mysqli_num_rows($r) > 0);
        // มีขั้นตรวจรับ => ล้างสถานะตรวจรับด้วย (บันทึกใหม่แล้วค่อยรอตรวจรับใหม่)
        $apSet = function_exists('pmap_has_col') && pmap_has_col($connect) ? ', approval_status = NULL, approval_id = NULL, approved_at = NULL' : '';

        // สรุปผลเดิมเก็บไว้ใน log (ดูย้อนหลังได้ว่าเดิมบันทึกอะไรไว้)
        $items = [];
        $r = mysqli_query($connect, "SELECT check_point, result_status, actual_value, unit FROM pm_work_record_items WHERE work_record_id = $wrId ORDER BY sort_order, id");
        if ($r) while ($row = mysqli_fetch_assoc($r)) $items[] = $row;
        $insp = [];
        $r = mysqli_query($connect, "SELECT inspector_name_snapshot FROM pm_work_record_inspectors WHERE work_record_id = $wrId");
        if ($r) while ($row = mysqli_fetch_assoc($r)) $insp[] = $row['inspector_name_snapshot'];
        $snapshot = json_encode([
            'actual_date' => $wr['actual_date'], 'inspectors' => $insp ?: [$wr['inspector_name']], 'remarks' => $wr['remarks'],
            'created_by' => $wr['created_by'], 'created_at' => $wr['created_at'], 'items' => $items,
            'spares' => $spares, 'evaluated' => (bool)$evaluated,
        ], JSON_UNESCAPED_UNICODE);

        // ไฟล์ของใบงานเดิม (ลบหลัง commit)
        $files = [];
        $q = function ($sql) use ($connect, &$files) { $r = mysqli_query($connect, $sql); if ($r) while ($row = mysqli_fetch_row($r)) if ($row[0] !== null && $row[0] !== '') $files[] = $row[0]; };
        $q("SELECT result_photo_path FROM pm_work_record_items WHERE work_record_id = $wrId");
        $q("SELECT inspector_signature_path FROM pm_work_records WHERE id = $wrId");
        $q("SELECT evaluator_signature_path FROM pm_work_evaluations WHERE work_record_id = $wrId");
        $q("SELECT s.file_path FROM pm_work_record_files_snapshot s WHERE s.work_record_id = $wrId
            AND NOT EXISTS (SELECT 1 FROM pm_checksheet_files cf WHERE cf.file_path = s.file_path)
            AND NOT EXISTS (SELECT 1 FROM pm_work_record_files_snapshot o WHERE o.file_path = s.file_path AND o.work_record_id <> $wrId)");

        pmwc_ensure($connect);   // ก่อนเริ่ม transaction
        $revision = pmwc_revision($connect, $event_id) + 1;
        $now = date('Y-m-d H:i:s');
        mysqli_begin_transaction($connect);
        try {
            // คืนสต็อกอะไหล่ที่ใบงานเดิมตัดไป
            if ($spares) {
                $st = mysqli_prepare($connect, "UPDATE product_stocks SET qty = qty + ?, updated_at = NOW() WHERE pd_id = ? AND wh_id = ?");
                foreach ($spares as $s) {
                    $qty = (float)$s['qty']; $pd = (int)$s['pd']; $wh = (int)$s['wh'];
                    mysqli_stmt_bind_param($st, 'dii', $qty, $pd, $wh);
                    if (!mysqli_stmt_execute($st)) throw new Exception(mysqli_error($connect));
                }
                mysqli_stmt_close($st);
            }
            $st = mysqli_prepare($connect, "INSERT INTO pm_work_record_cancel_log (ag_id, plan_event_id, plan_id, work_record_id, doc_no, revision, reason, snapshot_json, created_at, created_by, updated_at, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $planId = (int)$ev['plan_id']; $doc = (string)$wr['doc_no'];
            mysqli_stmt_bind_param($st, 'siiisissssss', $ag, $event_id, $planId, $wrId, $doc, $revision, $reason, $snapshot, $now, $me, $now, $me);
            if (!mysqli_stmt_execute($st)) throw new Exception(mysqli_error($connect));
            mysqli_stmt_close($st);
            // ลบใบงานเดิม — ผลตรวจ ผู้ตรวจ อะไหล่ ไฟล์ snapshot ผลประเมิน ลบตาม (ON DELETE CASCADE)
            if (!mysqli_query($connect, "DELETE FROM pm_work_records WHERE id = $wrId")) throw new Exception(mysqli_error($connect));
            // กำหนดการกลับเป็น "ยังไม่ทำ" วันเดิม
            if (!mysqli_query($connect, "UPDATE pm_plan_events SET status = 0, status_feedback = 0, work_record_id = NULL, completed_at = NULL,
                    evaluation_status = 0, evaluation_id = NULL, evaluated_at = NULL" . $apSet . ", updated_at = NOW() WHERE id = $event_id")) throw new Exception(mysqli_error($connect));
            mysqli_commit($connect);
        } catch (Exception $e) {
            mysqli_rollback($connect);
            return ['success' => false, 'error' => 'ยกเลิกไม่สำเร็จ: ' . $e->getMessage()];
        }


        pmwc_unlink_unused($connect, $files, dirname(__DIR__));
        return ['success' => true, 'doc_no' => $wr['doc_no'], 'revision' => $revision, 'spares_returned' => count($spares)];
    }
}
