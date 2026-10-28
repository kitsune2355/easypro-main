<?php
// ตัวเลขบนแท็บหน้า PM (pm.php) — นับแบบเดียวกับค่าเริ่มต้นของแต่ละแท็บ
//  todo     : งานที่ต้องทำ = ยังไม่ทำ (status 0) กำหนดวันนี้หรือเลยกำหนด ของแผนที่เปิดใช้ (handle_pm_daily.php ช่วงวันนี้ + รวมงานค้าง)
//  evaluate : รอประเมิน = เสร็จแล้ว (ไม่รอตรวจรับ) ยังไม่ประเมิน ช่วงต้นเดือนก่อน – วันนี้ (ช่วงเริ่มต้นของแท็บประเมิน PM)
//  approve  : รอตรวจรับ ทุกวันที่
//  handle_pm_badges.php ส่งค่าเดียวกันให้หน้าเว็บโหลดใหม่หลังบันทึก/ตรวจรับ/ประเมิน
require_once __DIR__ . '/pm_approve.php';

if (!function_exists('pmtb_counts')) {
    function pmtb_counts($connect, $ag) {
        $agEsc = mysqli_real_escape_string($connect, (string)$ag);
        $today = date('Y-m-d');
        $evFrom = date('Y-m-01', strtotime('first day of last month'));
        $one = function ($sql) use ($connect) {
            try { $r = mysqli_query($connect, $sql); return $r ? (int)mysqli_fetch_row($r)[0] : 0; }
            catch (Exception $e) { return 0; }
        };
        return [
            'todo' => $one("SELECT COUNT(*) FROM pm_plan_events e JOIN pm_plans p ON p.id = e.plan_id AND p.status = 1
                            WHERE e.ag_id = '$agEsc' AND e.status = 0 AND e.event_date <= '$today'"),
            'evaluate' => $one("SELECT COUNT(*) FROM pm_plan_events e WHERE e.ag_id = '$agEsc' AND " . pmap_done_sql($connect, 'e') . "
                            AND e.work_record_id IS NOT NULL AND e.evaluation_status = 0 AND e.event_date BETWEEN '$evFrom' AND '$today'"),
            'approve' => pmap_pending_count($connect, $ag),
        ];
    }

    // ป้ายตัวเลขบนแท็บ (ซ่อนเมื่อเป็น 0) — id = pm-badge-{key}
    function pmtb_badge($key, $n, $color) {
        return '<span id="pm-badge-' . $key . '" data-badge="' . $key . '" class="' . ($n ? '' : 'hidden ') . 'pm-tab-badge ' . $color . '">'
            . ($n > 99 ? '99+' : (int)$n) . '</span>';
    }
}
