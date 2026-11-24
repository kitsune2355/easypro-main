<?php
// ตั้งค่าผู้อนุมัติใบขอซื้อ (PR) — settings_pr_approvers.php · เฉพาะ admin / super_admin ของหน่วยงานที่ล็อกอินอยู่
//  GET ?action=setting_list · POST ?action=setting_save {user_ids}
@session_start();
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');
include 'config_ctrl/connect.php';
mysqli_set_charset($connect, 'utf8mb4');
require_once __DIR__ . '/config_ctrl/stock_pr_approver.php';

function spra_out($a, $code = 200) { http_response_code($code); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }
function spra_fail($msg, $code = 200) { spra_out(['success' => false, 'error' => $msg], $code); }

$ME    = (string)($_SESSION['sess_user_id_es'] ?? '');
$LEVEL = (string)($_SESSION['sess_user_level_es'] ?? '');
$AG    = (int)($_SESSION['sess_user_agency'] ?? 0);
if ($ME === '') spra_fail('กรุณาเข้าสู่ระบบใหม่', 401);
if (!in_array($LEVEL, ['super_admin', 'admin'], true)) spra_fail('เฉพาะผู้ดูแลระบบ', 403);
if ($AG <= 0) spra_fail('ไม่พบหน่วยงานของผู้ใช้');
if (!spra_ensure($connect)) spra_fail('สร้างตารางผู้อนุมัติไม่สำเร็จ กรุณาแจ้งผู้ดูแลระบบ');

// ผู้ใช้ของหน่วยงาน + ผู้ที่ถูกตั้งไว้แล้ว (ย้ายหน่วยงานไปก็ยังเห็นเพื่อเอาออกได้)
function spra_users($connect, $ag) {
    $rows = [];
    $rs = mysqli_query($connect, "SELECT u.user_id, TRIM(CONCAT(u.user_name, ' ', u.user_fname)) AS name, COALESCE(d.dep_name, '') AS user_position, u.user_level,
                (ap.id IS NOT NULL) AS is_approver
            FROM tb_user u
            LEFT JOIN tb_department2 d ON d.dep_id = u.user_department AND d.dep_status = 0   -- ตำแหน่ง: แหล่งเดียวกับหน้าตั้งค่าผู้ใช้ (settings_user.php)
            LEFT JOIN tb_stock_pr_approver ap ON ap.ag_id = $ag AND ap.user_id = u.user_id
            WHERE u.user_agency = '$ag' OR u.user_id IN (SELECT age_user_id FROM tb_agency_employer WHERE age_ag_id = $ag) OR ap.id IS NOT NULL
            ORDER BY is_approver DESC, u.user_name ASC");
    if (!$rs) throw new Exception(mysqli_error($connect));
    while ($r = mysqli_fetch_assoc($rs)) { $r['is_approver'] = (int)$r['is_approver'] === 1; $rows[] = $r; }
    return $rows;
}

$action = $_GET['action'] ?? '';
try {
    if ($action === 'setting_list') spra_out(['success' => true, 'data' => spra_users($connect, $AG)]);

    if ($action === 'setting_save') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') spra_fail('method ไม่ถูกต้อง', 405);
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || !isset($input['user_ids']) || !is_array($input['user_ids'])) spra_fail('ข้อมูลไม่ถูกต้อง', 400);   // กันล้างรายชื่อทั้งหมดโดยไม่ตั้งใจ
        $want = array_values(array_unique(array_filter(array_map('strval', $input['user_ids']), 'strlen')));

        $old = [];
        $known = [];
        foreach (spra_users($connect, $AG) as $u) {
            $known[] = (string)$u['user_id'];
            if ($u['is_approver']) $old[] = (string)$u['user_id'];
        }
        $unknown = array_diff($want, $known);
        if ($unknown) spra_fail('มีผู้ใช้ที่ไม่ได้อยู่ในหน่วยงานนี้: ' . implode(', ', $unknown));

        $now = date('Y-m-d H:i:s');
        mysqli_begin_transaction($connect);
        try {
            $del = mysqli_prepare($connect, "DELETE FROM tb_stock_pr_approver WHERE ag_id = ? AND user_id = ?");
            foreach (array_diff($old, $want) as $uid) {
                mysqli_stmt_bind_param($del, 'is', $AG, $uid);
                if (!mysqli_stmt_execute($del)) throw new Exception(mysqli_stmt_error($del));
            }
            $ins = mysqli_prepare($connect, "INSERT IGNORE INTO tb_stock_pr_approver (ag_id, user_id, created_at, created_by, updated_at, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
            foreach (array_diff($want, $old) as $uid) {
                mysqli_stmt_bind_param($ins, 'isssss', $AG, $uid, $now, $ME, $now, $ME);
                if (!mysqli_stmt_execute($ins)) throw new Exception(mysqli_stmt_error($ins));
            }
            mysqli_commit($connect);
        } catch (Exception $e) {
            mysqli_rollback($connect);
            throw $e;
        }
        spra_out(['success' => true, 'count' => count($want)]);
    }
    spra_fail('ไม่รู้จักคำสั่ง', 400);
} catch (Exception $e) {
    error_log('stock_pr_approver: ' . $e->getMessage());
    spra_fail('ทำรายการไม่สำเร็จ กรุณาลองใหม่');
}
