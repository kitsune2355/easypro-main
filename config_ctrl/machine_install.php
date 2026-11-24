<?php
// วันที่ติดตั้งเครื่องจักร (tb_ass_list.asset_install_date) — ใช้ใน handle_machine_info.php

if (!function_exists('mi_ensure')) {

    // เพิ่มคอลัมน์ครั้งแรก (ไม่ต้องรัน SQL เอง) · คืนค่า true เมื่อมีคอลัมน์ให้ใช้
    function mi_ensure($connect) {
        static $has = null;
        if ($has !== null) return $has;
        try {
            $r = @mysqli_query($connect, "SHOW COLUMNS FROM tb_ass_list LIKE 'asset_install_date'");
            $has = $r && mysqli_num_rows($r) > 0;
            if (!$has) $has = (bool)@mysqli_query($connect, "ALTER TABLE tb_ass_list ADD COLUMN asset_install_date DATE NULL DEFAULT NULL COMMENT 'วันที่ติดตั้งเครื่อง'");
        } catch (Exception $e) {   // PHP 8 mysqli โยน exception
            $has = false;
        }
        return $has;
    }

    // วันที่จากฟอร์ม: YYYY-MM-DD ที่มีจริง · ว่าง = null · รูปแบบผิด = false
    function mi_date($v) {
        $v = trim((string)$v);
        if ($v === '') return null;
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) || !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return false;
        return $v;
    }

    function mi_value($row) {
        $v = isset($row['asset_install_date']) ? $row['asset_install_date'] : '';
        return $v === '0000-00-00' ? '' : (string)$v;
    }
}
