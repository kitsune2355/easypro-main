<?php
// สิทธิ์จัดการสต็อก (รับเข้า / ปรับยอด / ขอซื้อ) — ผู้ใช้ที่ "ตำแหน่ง" (tb_user.user_department -> tb_department2) เป็น "ช่าง" จัดการไม่ได้ ดูได้อย่างเดียว
//  super_admin จัดการได้เสมอ · ใช้ร่วมกัน: stock.php, handle_stock.php, handle_stock_pr.php
//  PHP 7.1+

if (!function_exists('stock_can_manage')) {

    // ตำแหน่งของผู้ใช้เป็น "ช่าง" หรือไม่
    function stock_is_technician($connect, $userId) {
        static $cache = [];
        $userId = (string)$userId;
        if ($userId === '') return false;
        if (array_key_exists($userId, $cache)) return $cache[$userId];
        $dep = '';
        $st = mysqli_prepare($connect, "SELECT d.dep_name FROM tb_user u INNER JOIN tb_department2 d ON d.dep_id = u.user_department WHERE u.user_id = ? LIMIT 1");
        if ($st) {
            mysqli_stmt_bind_param($st, 's', $userId);
            mysqli_stmt_execute($st);
            mysqli_stmt_bind_result($st, $name);
            if (mysqli_stmt_fetch($st)) $dep = trim((string)$name);
            mysqli_stmt_close($st);
        }
        return $cache[$userId] = ($dep === 'ช่าง');
    }

    // ผู้ใช้ที่ล็อกอินอยู่ (session) จัดการสต็อกได้หรือไม่
    function stock_can_manage($connect) {
        if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
        $me = (string)($_SESSION['sess_user_id_es'] ?? '');
        if ($me === '') return false;
        if ((string)($_SESSION['sess_user_level_es'] ?? '') === 'super_admin') return true;
        return !stock_is_technician($connect, $me);
    }
}
