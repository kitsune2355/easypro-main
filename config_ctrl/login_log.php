<?php
// บันทึกการเข้าสู่ระบบ (tb_time_login) พร้อมวิธีเข้า — ใช้ใน checkmember.php (รหัสผ่าน) และ qr_autologin.php (สแกน QR)
//  tl_type: 'password' | 'qr' · ข้อมูลก่อนเพิ่มคอลัมน์ = NULL (ไม่ทราบวิธีเข้า)
//  หน้า Monitor หน่วยงาน (agency.php / handle_agency_monitor.php) ใช้แยกการเข้าด้วย QR

if (!function_exists('tl_ensure')) {

    // เพิ่มคอลัมน์ tl_type ครั้งแรก (ไม่ต้องรัน SQL เอง) · คืนค่า true เมื่อมีคอลัมน์ให้ใช้
    function tl_ensure($connect) {
        static $has = null;
        if ($has !== null) return $has;
        try {
            $r = @mysqli_query($connect, "SHOW COLUMNS FROM tb_time_login LIKE 'tl_type'");
            $has = $r && mysqli_num_rows($r) > 0;
            if (!$has) $has = (bool)@mysqli_query($connect, "ALTER TABLE tb_time_login ADD COLUMN tl_type VARCHAR(10) NULL DEFAULT NULL");
        } catch (Exception $e) {   // PHP 8 mysqli โยน exception
            $has = false;
        }
        return $has;
    }

    // เพิ่มคอลัมน์ไม่ได้ (เช่น ไม่มีสิทธิ์ ALTER) => บันทึกแบบเดิม ไม่ให้การเข้าสู่ระบบล้ม
    function tl_log($connect, $user_id, $type) {
        $u = mysqli_real_escape_string($connect, (string)$user_id);
        $t = $type === 'qr' ? 'qr' : 'password';
        if (tl_ensure($connect)) return mysqli_query($connect, "INSERT INTO tb_time_login SET tl_name = '$u', tl_time = NOW(), tl_type = '$t'");
        return mysqli_query($connect, "INSERT INTO tb_time_login SET tl_name = '$u', tl_time = NOW()");
    }
}
