<?php
// รหัสผ่านผู้ใช้ (ตรวจ session ทุกครั้ง)
//  POST ?action=change_my_password  {current, password}  เปลี่ยนรหัสผ่านของตัวเอง (ต้องกรอกรหัสผ่านเดิม)
//  POST ?action=reset_password      {id}                  คืนค่ารหัสผ่านเป็นค่าตั้งต้น 4123 — admin / super_admin
//       admin คืนค่าได้เฉพาะผู้ใช้ในหน่วยงานเดียวกันและไม่ใช่ super_admin
//  รหัสผ่านเก็บแบบเดิมของระบบ: md5(md5(md5(pass))) — ให้ตรงกับ config_ctrl/checkmember.php
@session_start();
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');
include 'config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');

define('ACC_DEFAULT_PASSWORD', '4123');

function acc_out($a, $code = 200) { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }
function acc_hash($p) { return md5(md5(md5($p))); }

$me    = (string)($_SESSION['sess_user_id_es'] ?? '');
$level = (string)($_SESSION['sess_user_level_es'] ?? '');
$myAg  = (string)($_SESSION['sess_user_agency'] ?? '');
if ($me === '') acc_out(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบใหม่'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') acc_out(['success' => false, 'error' => 'method not allowed'], 405);

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = [];
$action = $_GET['action'] ?? '';

if ($action === 'change_my_password') {
    $current = (string)($input['current'] ?? '');
    $new     = trim((string)($input['password'] ?? ''));
    if ($current === '' || $new === '') acc_out(['success' => false, 'error' => 'กรุณากรอกข้อมูลให้ครบ']);
    if (mb_strlen($new) < 4) acc_out(['success' => false, 'error' => 'รหัสผ่านใหม่ต้องมีอย่างน้อย 4 ตัวอักษร']);
    if ($new === $current) acc_out(['success' => false, 'error' => 'รหัสผ่านใหม่ต้องไม่ซ้ำกับรหัสผ่านเดิม']);

    $st = $connect->prepare("SELECT id, user_password FROM tb_user WHERE user_id = ? LIMIT 1");
    $st->bind_param('s', $me);
    $st->execute();
    $u = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$u) acc_out(['success' => false, 'error' => 'ไม่พบผู้ใช้งาน'], 404);
    if (!hash_equals((string)$u['user_password'], acc_hash($current))) acc_out(['success' => false, 'error' => 'รหัสผ่านเดิมไม่ถูกต้อง']);

    $hash = acc_hash($new);
    $id = (int)$u['id'];
    $st = $connect->prepare("UPDATE tb_user SET user_password = ? WHERE id = ?");
    $st->bind_param('si', $hash, $id);
    $ok = $st->execute();
    $st->close();
    if (!$ok) acc_out(['success' => false, 'error' => 'บันทึกไม่สำเร็จ'], 500);
    $_SESSION['sess_user_password_es'] = $hash;
    acc_out(['success' => true, 'message' => 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว']);
}

if ($action === 'reset_password') {
    if (!in_array($level, ['admin', 'super_admin'], true)) acc_out(['success' => false, 'error' => 'เฉพาะผู้ดูแลระบบ'], 403);
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) acc_out(['success' => false, 'error' => 'ไม่ระบุผู้ใช้']);

    $st = $connect->prepare("SELECT id, user_id, user_level, user_agency FROM tb_user WHERE id = ? LIMIT 1");
    $st->bind_param('i', $id);
    $st->execute();
    $u = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$u) acc_out(['success' => false, 'error' => 'ไม่พบผู้ใช้งาน'], 404);
    if ($level !== 'super_admin') {
        if ((string)$u['user_agency'] !== $myAg) acc_out(['success' => false, 'error' => 'คืนค่าได้เฉพาะผู้ใช้ในหน่วยงานของคุณ'], 403);
        if ($u['user_level'] === 'super_admin') acc_out(['success' => false, 'error' => 'ไม่มีสิทธิ์คืนค่ารหัสผ่านของ Super Admin'], 403);
    }

    $hash = acc_hash(ACC_DEFAULT_PASSWORD);
    $st = $connect->prepare("UPDATE tb_user SET user_password = ? WHERE id = ?");
    $st->bind_param('si', $hash, $id);
    $ok = $st->execute();
    $st->close();
    if (!$ok) acc_out(['success' => false, 'error' => 'บันทึกไม่สำเร็จ'], 500);
    acc_out(['success' => true, 'message' => 'คืนค่ารหัสผ่านของ ' . $u['user_id'] . ' เป็น ' . ACC_DEFAULT_PASSWORD . ' แล้ว']);
}

acc_out(['success' => false, 'error' => 'unknown action'], 400);
