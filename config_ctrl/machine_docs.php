<?php
// เอกสารแนบของเครื่องจักร (tb_ass_document) — ใช้ใน handle_machine_docs.php / handle_machine_info.php
//  ไฟล์เก็บที่ uploads/machine_docs/<ag_id>/<ชื่อสุ่ม>.<นามสกุล> · เปิด/ดาวน์โหลดผ่าน handle_machine_docs.php เท่านั้น

if (!function_exists('md_ensure')) {

    define('MD_DIR', dirname(__DIR__) . '/uploads/machine_docs');
    define('MD_MAX_FILES', 10);                  // ต่อเครื่อง
    define('MD_MAX_BYTES', 20 * 1024 * 1024);    // ต่อไฟล์

    // ชนิดไฟล์ที่รับ => Content-Type ตอนเปิด
    function md_types() {
        return [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'txt'  => 'text/plain; charset=utf-8',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt'  => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];
    }

    function md_exists($connect) {
        try {
            $r = @mysqli_query($connect, "SHOW TABLES LIKE 'tb_ass_document'");
            return $r && mysqli_num_rows($r) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    // สร้างตารางครั้งแรก (ไม่ต้องรัน SQL เอง)
    function md_ensure($connect) {
        static $ok = null;
        if ($ok !== null) return $ok;
        $ok = md_exists($connect);
        if ($ok) return $ok;
        try {
            $ok = (bool)@mysqli_query($connect, "CREATE TABLE IF NOT EXISTS tb_ass_document (
                md_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                ass_id INT NOT NULL COMMENT 'tb_ass_list.ass_id',
                ag_id INT NOT NULL,
                file_name VARCHAR(255) NOT NULL COMMENT 'ชื่อไฟล์เดิมที่ผู้ใช้แนบ',
                file_path VARCHAR(255) NOT NULL COMMENT 'ต่อจาก uploads/machine_docs/',
                file_ext VARCHAR(10) NOT NULL,
                file_size INT NOT NULL DEFAULT 0,
                created_at DATETIME NULL,
                created_by VARCHAR(50) NULL,
                updated_at DATETIME NULL,
                updated_by VARCHAR(50) NULL,
                KEY idx_ass (ass_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Exception $e) {
            $ok = false;
        }
        return $ok;
    }

    // เนื้อไฟล์ตรงกับนามสกุลหรือไม่ (ตรวจส่วนหัวไฟล์)
    function md_content_ok($path, $ext) {
        $h = (string)@file_get_contents($path, false, null, 0, 1024);
        if ($ext === 'pdf') return strpos($h, '%PDF') !== false;
        if ($ext === 'png') return strncmp($h, "\x89PNG", 4) === 0;
        if ($ext === 'jpg' || $ext === 'jpeg') return strncmp($h, "\xFF\xD8\xFF", 3) === 0;
        if ($ext === 'docx' || $ext === 'xlsx' || $ext === 'pptx') return strncmp($h, "PK", 2) === 0 || strncmp($h, "\xD0\xCF\x11\xE0", 4) === 0;   // แบบหลัง = ไฟล์ใส่รหัสผ่าน
        if ($ext === 'doc' || $ext === 'xls' || $ext === 'ppt') return strncmp($h, "\xD0\xCF\x11\xE0", 4) === 0;
        if ($ext === 'txt') return strpos($h, "\0") === false;
        return false;
    }

    // โฟลเดอร์เก็บไฟล์ของหน่วยงาน (ปิดการเปิดไฟล์ตรง ๆ ด้วย .htaccess)
    function md_dir($ag) {
        $dir = MD_DIR . '/' . (int)$ag;
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) return false;
        if (!is_file(MD_DIR . '/.htaccess')) {
            @file_put_contents(MD_DIR . '/.htaccess', "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Order deny,allow\n  Deny from all\n</IfModule>\n");
        }
        return is_writable($dir) ? $dir : false;
    }

    function md_unlink($rel) {
        if ($rel !== '' && strpos($rel, '..') === false && is_file(MD_DIR . '/' . $rel)) @unlink(MD_DIR . '/' . $rel);
    }

    // ลบเครื่องจักร => ลบเอกสารของเครื่องนั้นด้วย
    function md_delete_machines($connect, $ids) {
        $ids = array_filter(array_map('intval', (array)$ids));
        if (!$ids || !md_exists($connect)) return;
        $in = implode(',', $ids);
        $r = mysqli_query($connect, "SELECT file_path FROM tb_ass_document WHERE ass_id IN ($in)");
        $paths = [];
        while ($r && $row = mysqli_fetch_assoc($r)) $paths[] = $row['file_path'];
        if (mysqli_query($connect, "DELETE FROM tb_ass_document WHERE ass_id IN ($in)")) foreach ($paths as $p) md_unlink($p);
    }
}
