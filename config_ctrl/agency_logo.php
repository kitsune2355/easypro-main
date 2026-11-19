<?php
// โลโก้บริษัทลูกค้า (ต่อหน่วยงาน) — แสดงในใบพิมพ์ทุกใบ คู่กับ Logo_โปร.png
//  ตั้งค่าที่ ตั้งค่าข้อมูล → ตั้งค่าข้อมูลพื้นฐาน → โลโก้หน่วยงาน (settings_agency_logo.php)
//  ไฟล์เก็บที่ uploads/agency_logo/ (path จาก root ของระบบ)
//  ตั้งค่าต่อหน่วยงาน: logo_pos = 'right' (ต่อจากโลโก้ PROACTIVE) | 'left' (ก่อนโลโก้ PROACTIVE)
//                     logo_size = ขนาด % เทียบขนาดมาตรฐานของแต่ละใบพิมพ์ (AGL_SIZE_MIN–AGL_SIZE_MAX, ปกติ 100)
//                     logo_align = แนวตั้งของโลโก้ทั้งสอง 'top' | 'center' (ปกติ) | 'bottom'
//                     logo_gap = ระยะห่างระหว่างโลโก้ (px, 0–AGL_GAP_MAX, ปกติ 10)

if (!defined('AGL_SIZE_MIN')) define('AGL_SIZE_MIN', 50);
if (!defined('AGL_SIZE_MAX')) define('AGL_SIZE_MAX', 250);
if (!defined('AGL_GAP_MAX')) define('AGL_GAP_MAX', 60);

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
            logo_align VARCHAR(10) NOT NULL DEFAULT 'center',
            logo_gap SMALLINT NOT NULL DEFAULT 10,
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
        if ($cols && !isset($cols['logo_align'])) mysqli_query($connect, "ALTER TABLE tb_agency_logo ADD COLUMN logo_align VARCHAR(10) NOT NULL DEFAULT 'center' AFTER logo_size");
        if ($cols && !isset($cols['logo_gap'])) mysqli_query($connect, "ALTER TABLE tb_agency_logo ADD COLUMN logo_gap SMALLINT NOT NULL DEFAULT 10 AFTER logo_align");
    }

    // ค่าที่ถูกต้องของตำแหน่ง / ขนาด
    function agl_norm_pos($pos) { return $pos === 'left' ? 'left' : 'right'; }
    function agl_norm_size($size) { return max(AGL_SIZE_MIN, min(AGL_SIZE_MAX, (int)$size ?: 100)); }
    function agl_norm_align($a) { return in_array($a, ['top', 'bottom'], true) ? $a : 'center'; }
    function agl_norm_gap($g) { return ($g === null || $g === '') ? 10 : max(0, min(AGL_GAP_MAX, (int)$g)); }

    // ['path' => 'uploads/agency_logo/ag_21_...png' หรือ '' ถ้ายังไม่ได้ตั้ง/ไฟล์หาย, 'pos' => 'right'|'left', 'size' => 100, 'align' => 'center', 'gap' => 10]
    function agl_info($connect, $ag) {
        static $cache = [];
        $ag = (int)$ag;
        $info = ['path' => '', 'pos' => 'right', 'size' => 100, 'align' => 'center', 'gap' => 10];
        if ($ag <= 0) return $info;
        if (array_key_exists($ag, $cache)) return $cache[$ag];
        agl_ensure($connect);
        $st = mysqli_prepare($connect, "SELECT file_path, logo_pos, logo_size, logo_align, logo_gap FROM tb_agency_logo WHERE ag_id = ? LIMIT 1");
        if ($st) {
            mysqli_stmt_bind_param($st, 'i', $ag);
            mysqli_stmt_execute($st);
            mysqli_stmt_bind_result($st, $fp, $pos, $size, $align, $gap);
            if (mysqli_stmt_fetch($st)) $info = ['path' => (string)$fp, 'pos' => agl_norm_pos((string)$pos), 'size' => agl_norm_size($size), 'align' => agl_norm_align((string)$align), 'gap' => agl_norm_gap($gap)];
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

    // โลโก้ PROACTIVE + โลโก้หน่วยงาน เรียงตามตำแหน่ง / ขนาด / แนวตั้ง / ระยะห่างที่ตั้งไว้
    //  $proHtml  = แท็ก <img> ของ Logo_โปร.png ของใบพิมพ์นั้น
    //  $baseH / $baseMaxW = สูง / กว้างสูงสุด (px) ของโลโก้หน่วยงานที่ขนาด 100%
    //  ยังไม่ได้ตั้งโลโก้ => คืนเฉพาะ $proHtml
    function agl_logos($connect, $ag, $proHtml, $baseH, $baseMaxW, $attrs = 'class="agl-logo"') {
        $i = agl_info($connect, $ag);
        if ($i['path'] === '') return $proHtml;
        $k = $i['size'] / 100;
        $style = 'style="height:' . round($baseH * $k) . 'px;width:auto;max-width:' . round($baseMaxW * $k) . 'px;object-fit:contain;margin:0;flex-shrink:0;"';
        $ag = agl_img($connect, $ag, $attrs . ' ' . $style);
        $html = $i['pos'] === 'left' ? $ag . $proHtml : $proHtml . $ag;
        $align = ['top' => 'flex-start', 'center' => 'center', 'bottom' => 'flex-end'][$i['align']];
        // ชิดบน/ชิดล่าง => ข้อความหัวรายงานที่อยู่แถวเดียวกัน (กล่อง flex ที่ครอบโลโก้) จัดแนวตามด้วย
        $css = '';
        static $cssDone = false;
        if ($i['align'] !== 'center' && !$cssDone) {
            $cssDone = true;
            $css = '<style>:has(> .agl-logos.agl-al-top),:has(> .rp-logos > .agl-logos.agl-al-top){align-items:flex-start!important}'
                 . ':has(> .agl-logos.agl-al-bottom),:has(> .rp-logos > .agl-logos.agl-al-bottom){align-items:flex-end!important}</style>';
        }
        return $css . '<span class="agl-logos agl-al-' . $i['align'] . '" style="display:inline-flex;align-items:' . $align . ';gap:' . $i['gap'] . 'px;flex-shrink:0;">' . $html . '</span>';
    }
}
