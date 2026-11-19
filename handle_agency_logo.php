<?php
// โลโก้บริษัทลูกค้า (ต่อหน่วยงานที่ล็อกอินอยู่) — settings_agency_logo.php
//  GET  ?action=get
//  POST ?action=upload  (multipart: logo)  — admin / super_admin
//  POST ?action=delete                      — admin / super_admin
//  POST ?action=settings (pos=left|right, size=50–250) — admin / super_admin
@session_start();
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');
include 'config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');
require_once __DIR__ . '/config_ctrl/agency_logo.php';

function agl_out($a, $code = 200) { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

$me    = (string)($_SESSION['sess_user_id_es'] ?? '');
$level = (string)($_SESSION['sess_user_level_es'] ?? '');
$ag    = (int)($_SESSION['sess_user_agency'] ?? 0);
if ($me === '' || $ag <= 0) agl_out(['success' => false, 'error' => 'กรุณาเข้าสู่ระบบใหม่'], 401);

$action = $_GET['action'] ?? '';
$root = __DIR__;
$dirRel = 'uploads/agency_logo';

function agl_current($connect, $ag) {
    $i = agl_info($connect, $ag);
    $p = $i['path'];
    if ($p === '') return null;
    $abs = __DIR__ . '/' . $p;
    $size = @getimagesize($abs);
    return ['url' => $p . '?v=' . @filemtime($abs), 'pos' => $i['pos'], 'size' => $i['size'], 'width' => $size ? $size[0] : null, 'height' => $size ? $size[1] : null, 'bytes' => @filesize($abs)];
}

if ($action === 'get') {
    agl_out(['success' => true, 'logo' => agl_current($connect, $ag), 'size_min' => AGL_SIZE_MIN, 'size_max' => AGL_SIZE_MAX, 'can_edit' => in_array($level, ['admin', 'super_admin'], true)]);
}

if (!in_array($level, ['admin', 'super_admin'], true)) agl_out(['success' => false, 'error' => 'เฉพาะผู้ดูแลระบบ'], 403);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') agl_out(['success' => false, 'error' => 'method not allowed'], 405);
agl_ensure($connect);

if ($action === 'upload') {
    if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) agl_out(['success' => false, 'error' => 'ไม่พบไฟล์ หรืออัปโหลดไม่สำเร็จ']);
    $f = $_FILES['logo'];
    if ($f['size'] > 2 * 1024 * 1024) agl_out(['success' => false, 'error' => 'ไฟล์ใหญ่เกิน 2 MB']);
    // ตรวจจากเนื้อไฟล์จริง (ไม่เชื่อนามสกุล) — รับเฉพาะ PNG / JPG / WEBP / GIF (ไม่รับ SVG เพื่อความปลอดภัย)
    $info = @getimagesize($f['tmp_name']);
    $exts = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif'];
    if (defined('IMAGETYPE_WEBP')) $exts[IMAGETYPE_WEBP] = 'webp';
    if (!$info || !isset($exts[$info[2]])) agl_out(['success' => false, 'error' => 'รองรับเฉพาะไฟล์รูป PNG, JPG, WEBP หรือ GIF']);
    $ext = $exts[$info[2]];

    if (!is_dir($root . '/' . $dirRel) && !@mkdir($root . '/' . $dirRel, 0755, true)) agl_out(['success' => false, 'error' => 'สร้างโฟลเดอร์เก็บไฟล์ไม่ได้'], 500);
    $rel = $dirRel . '/ag_' . $ag . '_' . time() . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $root . '/' . $rel)) agl_out(['success' => false, 'error' => 'บันทึกไฟล์ไม่สำเร็จ'], 500);

    $old = agl_path($connect, $ag);
    $now = date('Y-m-d H:i:s');
    $st = mysqli_prepare($connect, "INSERT INTO tb_agency_logo (ag_id, file_path, created_at, created_by, updated_at, updated_by) VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), updated_at = VALUES(updated_at), updated_by = VALUES(updated_by)");
    mysqli_stmt_bind_param($st, 'isssss', $ag, $rel, $now, $me, $now, $me);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    if (!$ok) { @unlink($root . '/' . $rel); agl_out(['success' => false, 'error' => 'บันทึกข้อมูลไม่สำเร็จ'], 500); }
    if ($old !== '' && $old !== $rel && strpos($old, $dirRel . '/') === 0) @unlink($root . '/' . $old);
    $abs = $root . '/' . $rel;
    // ตำแหน่ง/ขนาดเดิมคงไว้ (ON DUPLICATE KEY ไม่แตะคอลัมน์ตั้งค่า)
    $st = mysqli_prepare($connect, "SELECT logo_pos, logo_size FROM tb_agency_logo WHERE ag_id = ?");
    mysqli_stmt_bind_param($st, 'i', $ag);
    mysqli_stmt_execute($st);
    mysqli_stmt_bind_result($st, $pos, $size);
    if (!mysqli_stmt_fetch($st)) { $pos = 'right'; $size = 100; }
    mysqli_stmt_close($st);
    agl_out(['success' => true, 'logo' => ['url' => $rel . '?v=' . filemtime($abs), 'pos' => agl_norm_pos((string)$pos), 'size' => agl_norm_size($size), 'width' => $info[0], 'height' => $info[1], 'bytes' => filesize($abs)]]);
}

if ($action === 'delete') {
    $old = agl_path($connect, $ag);
    $st = mysqli_prepare($connect, "DELETE FROM tb_agency_logo WHERE ag_id = ?");
    mysqli_stmt_bind_param($st, 'i', $ag);
    mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    if ($old !== '' && strpos($old, $dirRel . '/') === 0) @unlink($root . '/' . $old);
    agl_out(['success' => true]);
}

if ($action === 'settings') {
    $pos  = agl_norm_pos((string)($_POST['pos'] ?? 'right'));
    $size = agl_norm_size($_POST['size'] ?? 100);
    $now  = date('Y-m-d H:i:s');
    $st = mysqli_prepare($connect, "UPDATE tb_agency_logo SET logo_pos = ?, logo_size = ?, updated_at = ?, updated_by = ? WHERE ag_id = ?");
    mysqli_stmt_bind_param($st, 'sissi', $pos, $size, $now, $me, $ag);
    $ok = mysqli_stmt_execute($st);
    $n = mysqli_stmt_affected_rows($st);
    mysqli_stmt_close($st);
    if (!$ok) agl_out(['success' => false, 'error' => 'บันทึกข้อมูลไม่สำเร็จ'], 500);
    if ($n === 0 && agl_path($connect, $ag) === '') agl_out(['success' => false, 'error' => 'กรุณาอัปโหลดโลโก้ก่อน']);
    agl_out(['success' => true, 'pos' => $pos, 'size' => $size]);
}

agl_out(['success' => false, 'error' => 'unknown action'], 400);
