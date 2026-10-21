<?php
// โลโก้บริษัทลูกค้า (ต่อหน่วยงาน) — แสดงในใบพิมพ์ทุกใบ ต่อจาก Logo_โปร.png (ทางขวา)
//  ตั้งค่าที่ ตั้งค่าข้อมูล → ตั้งค่าข้อมูลพื้นฐาน → โลโก้หน่วยงาน (settings_agency_logo.php)
//  ไฟล์เก็บที่ uploads/agency_logo/ (path จาก root ของระบบ)

if (!function_exists('agl_ensure')) {

    function agl_ensure($connect) {
        static $done = false;
        if ($done) return;
        $done = true;
        mysqli_query($connect, "CREATE TABLE IF NOT EXISTS tb_agency_logo (
            ag_id INT NOT NULL PRIMARY KEY,
            file_path VARCHAR(255) NOT NULL,
            created_at DATETIME NULL,
            created_by VARCHAR(50) NULL,
            updated_at DATETIME NULL,
            updated_by VARCHAR(50) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // path ของโลโก้หน่วยงาน (เช่น uploads/agency_logo/ag_21_1760000000.png) หรือ '' ถ้ายังไม่ได้ตั้ง/ไฟล์หาย
    function agl_path($connect, $ag) {
        static $cache = [];
        $ag = (int)$ag;
        if ($ag <= 0) return '';
        if (array_key_exists($ag, $cache)) return $cache[$ag];
        agl_ensure($connect);
        $path = '';
        $st = mysqli_prepare($connect, "SELECT file_path FROM tb_agency_logo WHERE ag_id = ? LIMIT 1");
        if ($st) {
            mysqli_stmt_bind_param($st, 'i', $ag);
            mysqli_stmt_execute($st);
            mysqli_stmt_bind_result($st, $fp);
            if (mysqli_stmt_fetch($st)) $path = (string)$fp;
            mysqli_stmt_close($st);
        }
        $root = dirname(__DIR__);
        if ($path !== '' && !is_file($root . '/' . $path)) $path = '';
        return $cache[$ag] = $path;
    }

    // แท็ก <img> ของโลโก้หน่วยงาน (ว่างถ้ายังไม่ได้ตั้ง) — $attrs เช่น 'class="..." style="..."'
    function agl_img($connect, $ag, $attrs = '') {
        $p = agl_path($connect, $ag);
        if ($p === '') return '';
        $v = @filemtime(dirname(__DIR__) . '/' . $p);
        return '<img src="' . htmlspecialchars($p . '?v=' . $v, ENT_QUOTES, 'UTF-8') . '" alt="logo" ' . $attrs . '>';
    }
}
