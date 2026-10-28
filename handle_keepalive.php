<?php
// ต่ออายุการใช้งานขณะเปิดหน้าค้างไว้ (main.php / main2.php เรียกทุก 5 นาที และตอนกลับมาที่แท็บ)
//  session หาย (เซิร์ฟเวอร์ลบ / หมดเวลา) แต่ยังมี token จดจำการเข้าสู่ระบบ => สร้าง session คืน — ไม่ต้องล็อกอินใหม่
//  => {success, logged_in}
@session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
include 'config_ctrl/connect.php';
require_once __DIR__ . '/config_ctrl/remember_login.php';

if (empty($_SESSION['sess_user_id_es']) && !empty($_COOKIE[RM_COOKIE])) rm_restore($connect);
$in = !empty($_SESSION['sess_user_id_es']);
if ($in) {
    $_SESSION['session_time'] = time();   // เปิดหน้าอยู่ = ยังใช้งาน (checksession นับเวลาจากค่านี้)
    rm_sync($connect);
}
echo json_encode(['success' => true, 'logged_in' => $in]);
