<?php
// โลโก้บริษัทลูกค้า (ต่อหน่วยงาน) — แสดงในใบพิมพ์ทุกใบ คู่กับ Logo_โปร.png
//  ตั้งค่าที่ ตั้งค่าข้อมูล → ตั้งค่าข้อมูลพื้นฐาน → โลโก้หน่วยงาน (settings_agency_logo.php)
//  ไฟล์เก็บที่ uploads/agency_logo/ (path จาก root ของระบบ)
//  ตั้งค่าต่อหน่วยงาน: logo_pos = 'right' (ต่อจากโลโก้ PROACTIVE) | 'left' (ก่อนโลโก้ PROACTIVE)
//                     logo_size = ขนาด % เทียบขนาดมาตรฐานของแต่ละใบพิมพ์ (AGL_SIZE_MIN–AGL_SIZE_MAX, ปกติ 100)

if (!defined('AGL_SIZE_MIN')) define('AGL_SIZE_MIN', 50);
if (!defined('AGL_SIZE_MAX')) define('AGL_SIZE_MAX', 250);

if (!function_exists('agl_ensure')) {

    function agl_ensure($connect) {
        static $done = false;
        if ($done) return;
        $done = true;
        mysqli_query($connect, "CREATE TABLE IF NOT EXISTS tb_agency_logo (
            ag_id INT NOT NULL PRIMARY KEY,
            file_path VARCHAR(255) NOT NULL,
            logo_pos VARCHAR(10) NOT NULL DEFAULT 'right',
            logo_size SMALLINT NOT NULL DEFAULT 100,
            created_at DATETIME NULL,
            created_by VARCHAR(50) NULL,
            updated_at DATETIME NULL,
            updated_by VARCHAR(50) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // ตารางเดิม (ก่อนมีการตั้งตำแหน่ง/ขนาด) => เพิ่มคอลัมน์
        $cols = [];
        $rs = mysqli_query($connect, "SHOW COLUMNS FROM tb_agency_logo");
        if ($rs) { while ($c = mysqli_fetch_assoc($rs)) $cols[$c['Field']] = true; mysqli_free_result($rs); }
        if ($cols && !isset($cols['logo_pos'])) mysqli_query($connect, "ALTER TABLE tb_agency_logo ADD COLUMN logo_pos VARCHAR(10) NOT NULL DEFAULT 'right' AFTER file_path");
        if ($cols && !isset($cols['logo_size'])) mysqli_query($connect, "ALTER TABLE tb_agency_logo ADD COLUMN logo_size SMALLINT NOT NULL DEFAULT 100 AFTER logo_pos");
    }

    // ค่าที่ถูกต้องของตำแหน่ง / ขนาด
    function agl_norm_pos($pos) { return $pos === 'left' ? 'left' : 'right'; }
    function agl_norm_size($size) { return max(AGL_SIZE_MIN, min(AGL_SIZE_MAX, (int)$size ?: 100)); }

    // ['path' => 'uploads/agency_logo/ag_21_...png' หรือ '' ถ้ายังไม่ได้ตั้ง/ไฟล์หาย, 'pos' => 'right'|'left', 'size' => 100]
    function agl_info($connect, $ag) {
        static $cache = [];
        $ag = (int)$ag;
        $info = ['path' => '', 'pos' => 'right', 'size' => 100];
        if ($ag <= 0) return $info;
        if (array_key_exists($ag, $cache)) return $cache[$ag];
        agl_ensure($connect);
        $st = mysqli_prepare($connect, "SELECT file_path, logo_pos, logo_size FROM tb_agency_logo WHERE ag_id = ? LIMIT 1");
        if ($st) {
            mysqli_stmt_bind_param($st, 'i', $ag);
            mysqli_stmt_execute($st);
            mysqli_stmt_bind_result($st, $fp, $pos, $size);
            if (mysqli_stmt_fetch($st)) $info = ['path' => (string)$fp, 'pos' => agl_norm_pos((string)$pos), 'size' => agl_norm_size($size)];
            mysqli_stmt_close($st);
        }
        if ($info['path'] !== '' && !is_file(dirname(__DIR__) . '/' . $info['path'])) $info['path'] = '';
        return $cache[$ag] = $info;
    }

    // path ของโลโก้หน่วยงาน หรือ '' ถ้ายังไม่ได้ตั้ง/ไฟล์หาย
    function agl_path($connect, $ag) {
        $i = agl_info($connect, $ag);
        return $i['path'];
    }

    // แท็ก <img> ของโลโก้หน่วยงาน (ว่างถ้ายังไม่ได้ตั้ง) — $attrs เช่น 'class="..." style="..."'
    function agl_img($connect, $ag, $attrs = '') {
        $p = agl_path($connect, $ag);
        if ($p === '') return '';
        $v = @filemtime(dirname(__DIR__) . '/' . $p);
        return '<img src="' . htmlspecialchars($p . '?v=' . $v, ENT_QUOTES, 'UTF-8') . '" alt="logo" ' . $attrs . '>';
    }

    // โลโก้ PROACTIVE + โลโก้หน่วยงาน เรียงตามตำแหน่งที่ตั้งไว้ และย่อ/ขยายตามขนาดที่ตั้งไว้
    //  $proHtml  = แท็ก <img> ของ Logo_โปร.png ของใบพิมพ์นั้น
    //  $baseH / $baseMaxW = สูง / กว้างสูงสุด (px) ของโลโก้หน่วยงานที่ขนาด 100%
    //  ยังไม่ได้ตั้งโลโก้ => คืนเฉพาะ $proHtml
    function agl_logos($connect, $ag, $proHtml, $baseH, $baseMaxW, $gap = 8, $attrs = 'class="agl-logo"') {
        $i = agl_info($connect, $ag);
        if ($i['path'] === '') return $proHtml;
        $k = $i['size'] / 100;
        $style = 'style="height:' . round($baseH * $k) . 'px;width:auto;max-width:' . round($baseMaxW * $k) . 'px;object-fit:contain;margin:0;flex-shrink:0;"';
        $ag = agl_img($connect, $ag, $attrs . ' ' . $style);
        $html = $i['pos'] === 'left' ? $ag . $proHtml : $proHtml . $ag;
        return '<span class="agl-logos" style="display:inline-flex;align-items:center;gap:' . (int)$gap . 'px;flex-shrink:0;">' . $html . '</span>';
    }
}
