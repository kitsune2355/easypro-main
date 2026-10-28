<?php
// ตัวเลขบนแท็บหน้า PM (config_ctrl/pm_tab_badges.php) — pm.php เรียกหลังบันทึกผล / ตรวจรับ / ประเมิน
@session_start();
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');
include 'config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');
require_once __DIR__ . '/config_ctrl/pm_tab_badges.php';

$ag = (int)($_SESSION['sess_user_agency'] ?? 0);
if (empty($_SESSION['sess_user_id_es']) || $ag <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบใหม่'], JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode(['success' => true] + pmtb_counts($connect, $ag), JSON_UNESCAPED_UNICODE);
