<?php
// เอกสารแนบของเครื่องจักร: list / upload / delete / file (เปิดหรือดาวน์โหลด)
//  ต้องล็อกอิน และเข้าถึงได้เฉพาะเครื่องของหน่วยงานที่ล็อกอินอยู่
@session_start();
ini_set('display_errors', 0);
include 'config_ctrl/connect.php';
require_once __DIR__ . '/config_ctrl/machine_docs.php';
date_default_timezone_set('Asia/Bangkok');

function md_out($a, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($a, JSON_UNESCAPED_UNICODE);
    exit;
}
function md_fail($msg, $code = 200) { md_out(['success' => false, 'message' => $msg], $code); }

$ME = (string)($_SESSION['sess_user_id_es'] ?? '');
$AG = (int)($_SESSION['sess_user_agency'] ?? 0);
if ($ME === '' || $AG <= 0) md_fail('กรุณาเข้าสู่ระบบใหม่', 403);
session_write_close();

$action = (string)($_GET['action'] ?? '');
if (!md_ensure($connect)) md_fail('ฐานข้อมูลยังไม่พร้อมเก็บเอกสาร กรุณาแจ้งผู้ดูแลระบบ', 500);

// เครื่องต้องเป็นของหน่วยงานที่ล็อกอินอยู่
function md_own_machine($connect, $ass, $ag) {
    $st = mysqli_prepare($connect, "SELECT ass_id FROM tb_ass_list WHERE ass_id = ? AND ass_ag_id = ?");
    mysqli_stmt_bind_param($st, 'ii', $ass, $ag);
    mysqli_stmt_execute($st);
    return (bool)mysqli_fetch_assoc(mysqli_stmt_get_result($st));
}

function md_list($connect, $ass, $ag) {
    $st = mysqli_prepare($connect, "SELECT md_id, file_name, file_ext, file_size, created_at FROM tb_ass_document WHERE ass_id = ? AND ag_id = ? ORDER BY md_id");
    mysqli_stmt_bind_param($st, 'ii', $ass, $ag);
    mysqli_stmt_execute($st);
    $rs = mysqli_stmt_get_result($st);
    $rows = [];
    while ($r = mysqli_fetch_assoc($rs)) {
        $r['md_id'] = (int)$r['md_id'];
        $r['file_size'] = (int)$r['file_size'];
        $rows[] = $r;
    }
    return $rows;
}

function md_doc($connect, $id, $ag) {
    $st = mysqli_prepare($connect, "SELECT d.* FROM tb_ass_document d JOIN tb_ass_list a ON a.ass_id = d.ass_id WHERE d.md_id = ? AND d.ag_id = ? AND a.ass_ag_id = ?");
    mysqli_stmt_bind_param($st, 'iii', $id, $ag, $ag);
    mysqli_stmt_execute($st);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($st));
}

try {
    if ($action === 'list') {
        $ass = (int)($_GET['ass_id'] ?? 0);
        if (!md_own_machine($connect, $ass, $AG)) md_fail('ไม่พบเครื่องจักรในหน่วยงานนี้', 404);
        md_out(['success' => true, 'data' => md_list($connect, $ass, $AG)]);
    }

    if ($action === 'upload') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') md_fail('method ไม่ถูกต้อง', 405);
        $mb = (int)(MD_MAX_BYTES / 1048576);
        if (empty($_POST) && empty($_FILES)) md_fail('ไฟล์ใหญ่เกินที่เซิร์ฟเวอร์รับได้');   // เกิน post_max_size
        $ass = (int)($_POST['ass_id'] ?? 0);
        if (!md_own_machine($connect, $ass, $AG)) md_fail('ไม่พบเครื่องจักรในหน่วยงานนี้', 404);

        $f = $_FILES['file'] ?? null;
        if (!$f || is_array($f['error'])) md_fail('ไม่พบไฟล์');
        if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > MD_MAX_BYTES) md_fail("ไฟล์ใหญ่เกินกำหนด (ไม่เกิน $mb MB หรือตามที่เซิร์ฟเวอร์รับได้)");
        if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] <= 0) md_fail('อัปโหลดไม่สำเร็จ หรือไฟล์ว่าง');

        // ชื่อไฟล์เดิม (ตัด path เอง — basename() มีปัญหากับชื่อภาษาไทยในบาง locale)
        $name = preg_replace('~^.*[\\\\/]~s', '', (string)$f['name']);
        $name = trim((string)preg_replace('/[\x00-\x1F\x7F]/', '', $name));
        $dot = strrpos($name, '.');
        $ext = $dot === false ? '' : strtolower(substr($name, $dot + 1));
        if (!isset(md_types()[$ext])) md_fail('ชนิดไฟล์นี้แนบไม่ได้ (รับ PDF, Word, Excel, PowerPoint, รูป JPG/PNG, TXT)');
        if (!md_content_ok($f['tmp_name'], $ext)) md_fail('เนื้อไฟล์ไม่ตรงกับนามสกุล .' . $ext);
        if (preg_match('/^(.{1,200})/us', substr($name, 0, $dot), $m)) $name = $m[1] . '.' . $ext;
        else $name = 'document.' . $ext;

        if (count(md_list($connect, $ass, $AG)) >= MD_MAX_FILES) md_fail('แนบได้ไม่เกิน ' . MD_MAX_FILES . ' ไฟล์ต่อเครื่อง');
        $dir = md_dir($AG);
        if (!$dir) md_fail('เซิร์ฟเวอร์เขียนโฟลเดอร์เก็บเอกสารไม่ได้', 500);
        $file = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $file)) md_fail('บันทึกไฟล์ไม่สำเร็จ', 500);

        $rel = $AG . '/' . $file;
        $size = (int)$f['size'];
        $ok = false;
        try {
            $st = mysqli_prepare($connect, "INSERT INTO tb_ass_document (ass_id, ag_id, file_name, file_path, file_ext, file_size, created_at, created_by, updated_at, updated_by) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, NOW(), ?)");
            mysqli_stmt_bind_param($st, 'iisssiss', $ass, $AG, $name, $rel, $ext, $size, $ME, $ME);
            $ok = mysqli_stmt_execute($st);
        } catch (Exception $e) { $ok = false; }
        if (!$ok) { md_unlink($rel); md_fail('บันทึกข้อมูลเอกสารไม่สำเร็จ', 500); }
        md_out(['success' => true, 'data' => md_list($connect, $ass, $AG)]);
    }

    if ($action === 'delete') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') md_fail('method ไม่ถูกต้อง', 405);
        $doc = md_doc($connect, (int)($_POST['md_id'] ?? 0), $AG);
        if (!$doc) md_fail('ไม่พบเอกสาร', 404);
        $id = (int)$doc['md_id'];
        $st = mysqli_prepare($connect, "DELETE FROM tb_ass_document WHERE md_id = ?");
        mysqli_stmt_bind_param($st, 'i', $id);
        if (!mysqli_stmt_execute($st)) md_fail('ลบไม่สำเร็จ', 500);
        md_unlink($doc['file_path']);
        md_out(['success' => true]);
    }

    if ($action === 'file') {
        $doc = md_doc($connect, (int)($_GET['id'] ?? 0), $AG);
        $path = $doc ? MD_DIR . '/' . $doc['file_path'] : '';
        if (!$doc || strpos($doc['file_path'], '..') !== false || !is_file($path)) md_fail('ไม่พบไฟล์', 404);
        $types = md_types();
        $inline = in_array($doc['file_ext'], ['pdf', 'jpg', 'jpeg', 'png', 'txt'], true) && empty($_GET['dl']);
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: ' . ($types[$doc['file_ext']] ?? 'application/octet-stream'));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="document.' . $doc['file_ext'] . '"; filename*=UTF-8\'\'' . rawurlencode($doc['file_name']));
        readfile($path);
        exit;
    }
    md_fail('ไม่รู้จักคำสั่ง', 400);
} catch (Exception $e) {
    md_fail('เกิดข้อผิดพลาดในระบบ', 500);
}
