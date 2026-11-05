<?php
// สูตรปริมาณการใช้มิเตอร์ — ใช้ร่วมกันทุกหน้า (handle_meter_info.php, meter_info_print.php, get_all_dashboard.php)
//  โหมดในคอลัมน์ *_is_rollover
//   0 = ปกติ            : เลขที่อ่านได้ - เลขครั้งก่อน
//   1 = เปลี่ยนมิเตอร์   : เลขที่อ่านได้ - เลขเริ่มต้นของมิเตอร์ใหม่ (ข้อมูลเก่า start = NULL => นับจาก 0 เหมือนเดิม)
//   2 = หน้าปัดวนรอบ    : ช่องที่เลขลดลง = (ค่าสูงสุดหน้าปัด - เลขครั้งก่อน) + เลขที่อ่านได้ · ช่องที่ไม่ลด = ลบกันตามปกติ
//  ผลลัพธ์ติดลบ = 0

if (!function_exists('meter_usage')) {

    function meter_usage($curr, $prev, $mode, $max, $start = 0) {
        $mode = (int)$mode;
        if ($mode === 1) {
            $u = $curr - (float)$start;
        } elseif ($mode === 2 && $curr < $prev && $max > 0 && $prev <= $max) {
            $u = ($max - $prev) + $curr;
        } elseif ($mode === 2 && $curr < $prev) {
            $u = $curr;   // ค่าสูงสุดหน้าปัดตั้งไว้ต่ำกว่าเลขครั้งก่อน => คำนวณส่วนที่เหลือของรอบไม่ได้
        } else {
            $u = $curr - $prev;
        }
        return $u < 0 ? 0.0 : $u;
    }

    // เพิ่มคอลัมน์เลขเริ่มต้นของมิเตอร์ใหม่ครั้งแรก (ไม่ต้องรัน SQL เอง — sql/2026-11-05_meter_start_val.sql) · true = มีคอลัมน์ครบ
    function meter_start_ensure($connect) {
        static $ready = null;
        if ($ready !== null) return $ready;
        $cols = [
            'tb_toum_detail_wt' => ['toumdt_wt_start_val'],
            'tb_toum_detail_et' => ['toumdt_et_start_val'],
            'tb_toum_detail_gt' => ['toumdt_gt_start_val'],
            'tb_tou_detail'     => ['toudt_start_on', 'toudt_start_off'],
        ];
        $ready = true;
        try {
            foreach ($cols as $table => $list) {
                foreach ($list as $col) {
                    $r = @mysqli_query($connect, "SHOW COLUMNS FROM $table LIKE '$col'");
                    if ($r && mysqli_num_rows($r) > 0) continue;
                    if (!@mysqli_query($connect, "ALTER TABLE $table ADD COLUMN $col DOUBLE NULL DEFAULT NULL")) $ready = false;
                }
            }
        } catch (Exception $e) {   // PHP 8 mysqli โยน exception
            $ready = false;
        }
        return $ready;
    }

    // ชื่อคอลัมน์สำหรับ SELECT — ยังไม่มีคอลัมน์ (ไม่มีสิทธิ์ ALTER) => NULL ให้หน้าเว็บทำงานต่อได้
    function meter_start_sel($connect, $col) {
        return meter_start_ensure($connect) ? $col : 'NULL';
    }
}
