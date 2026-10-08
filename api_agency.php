<?php
@session_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

include("config_ctrl/connect.php");

if (!isset($connect) || !$connect) {
  echo json_encode(['success' => false, 'message' => 'db connect failed'], JSON_UNESCAPED_UNICODE);
  exit;
}

mysqli_set_charset($connect, "utf8");

function jexit($arr){
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

function req($k, $d = ''){
  return isset($_POST[$k]) ? trim($_POST[$k]) : $d;
}

$action = $_REQUEST['action'] ?? 'list';

/**
 * Mapping:
 * ag_job        = รหัสหน่วยงาน
 * ag_format     = format เลขที่เอกสารแจ้งซ่อม
 * ag_contract   = ชื่อหน่วยงาน
 * ag_located    = ที่อยู่
 * ag_latitude   = lat
 * ag_longitude  = lng
 * ag_start_date = วันที่เริ่มต้น
 * ag_end_date   = วันที่สิ้นสุด
 * ag_status     = สถานะ 1/0
 *
 * employers: tb_agency_employer(age_user_id, age_ag_id)
 * permissions: tb_agency_role_permission(ag_id, role, perm_key, can_view)
 */

function parse_employers($raw){
  $raw = trim($raw);
  if ($raw === '') return [];

  if ($raw[0] === '[') {
    $arr = json_decode($raw, true);
    if (is_array($arr)) {
      return array_values(array_unique(array_filter(array_map('trim', $arr))));
    }
    return [];
  }

  return array_values(array_unique(array_filter(array_map('trim', explode(',', $raw)))));
}

function parse_menus($raw){
  $raw = trim($raw);
  if ($raw === '') return [];

  $arr = json_decode($raw, true);
  if (!is_array($arr)) return [];

  return array_values(array_unique(array_filter(array_map('trim', $arr))));
}

function sync_employers($connect, $agId, $employers){
  $stmt = mysqli_prepare($connect, "DELETE FROM tb_agency_employer WHERE age_ag_id = ?");
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $agId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
  }

  if (!$employers || !is_array($employers)) return;

  $stmt = mysqli_prepare($connect, "INSERT INTO tb_agency_employer (age_user_id, age_ag_id, age_ins) VALUES (?, ?, NOW())");
  if (!$stmt) return;

  foreach ($employers as $uid) {
    $uid = trim($uid);
    if ($uid === '') continue;

    mysqli_stmt_bind_param($stmt, "si", $uid, $agId);
    mysqli_stmt_execute($stmt);
  }

  mysqli_stmt_close($stmt);
}

function sync_permissions($connect, $agId, $role, $menus){
  $stmt = mysqli_prepare($connect, "DELETE FROM tb_agency_role_permission WHERE ag_id = ? AND role = ?");
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, "is", $agId, $role);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
  }

  if (!$menus || !is_array($menus)) return;

  $stmt = mysqli_prepare(
    $connect,
    "INSERT INTO tb_agency_role_permission (ag_id, role, perm_key, can_view)
     VALUES (?, ?, ?, 1)"
  );
  if (!$stmt) return;

  foreach ($menus as $permKey) {
    $permKey = trim($permKey);
    if ($permKey === '') continue;

    mysqli_stmt_bind_param($stmt, "iss", $agId, $role, $permKey);
    mysqli_stmt_execute($stmt);
  }

  mysqli_stmt_close($stmt);
}

function agency_exists($connect, $agId){
  $stmt = mysqli_prepare($connect, "SELECT 1 FROM tb_agency WHERE ag_id = ? LIMIT 1");
  if (!$stmt) return false;

  mysqli_stmt_bind_param($stmt, "i", $agId);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $exists = ($res && mysqli_fetch_row($res)) ? true : false;
  mysqli_stmt_close($stmt);

  return $exists;
}

function validate_date_range($startDate, $endDate){
  if ($startDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
    jexit(['success' => false, 'message' => 'รูปแบบวันที่เริ่มต้นไม่ถูกต้อง']);
  }

  if ($endDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    jexit(['success' => false, 'message' => 'รูปแบบวันที่สิ้นสุดไม่ถูกต้อง']);
  }

  if ($startDate !== '' && $endDate !== '' && $startDate > $endDate) {
    jexit(['success' => false, 'message' => 'วันที่เริ่มต้นต้องไม่มากกว่าวันที่สิ้นสุด']);
  }
}

/* =========================================================
 * GET PERMISSIONS
 * ========================================================= */
if ($action === 'get_permissions') {
  $ag_id = (int)($_GET['ag_id'] ?? 0);
  $role = trim($_GET['role'] ?? 'employer');

  if ($ag_id <= 0) {
    jexit(['success' => false, 'message' => 'invalid ag_id']);
  }

  $stmt = mysqli_prepare(
    $connect,
    "SELECT perm_key AS sm_key, can_view
     FROM tb_agency_role_permission
     WHERE ag_id = ? AND role = ?
     ORDER BY arp_id ASC"
  );
  if (!$stmt) jexit(['success' => false, 'message' => 'prepare failed']);

  mysqli_stmt_bind_param($stmt, "is", $ag_id, $role);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);

  $rows = [];
  while ($r = mysqli_fetch_assoc($res)) {
    $rows[] = [
      'sm_key' => $r['sm_key'],
      'can_view' => (int)$r['can_view']
    ];
  }
  mysqli_stmt_close($stmt);

  jexit(['success' => true, 'data' => $rows]);
}

/* =========================================================
 * LIST
 * ========================================================= */
if ($action === 'list') {
  $sql = "SELECT ag_id, ag_job, ag_format, ag_contract, ag_located,
                 ag_latitude, ag_longitude, ag_start_date, ag_end_date, ag_status
          FROM tb_agency
          ORDER BY ag_id DESC";
  $res = mysqli_query($connect, $sql);
  if (!$res) jexit(['success' => false, 'message' => 'query failed']);

  $rows = [];

  while ($r = mysqli_fetch_assoc($res)) {
    $agId = (int)$r['ag_id'];

    $emps = [];
    $stmt = mysqli_prepare(
      $connect,
      "SELECT e.age_user_id, u.user_name, u.user_fname
       FROM tb_agency_employer e
       LEFT JOIN tb_user u ON u.user_id = e.age_user_id
       WHERE e.age_ag_id = ?
       ORDER BY e.age_id DESC"
    );

    if ($stmt) {
      mysqli_stmt_bind_param($stmt, "i", $agId);
      mysqli_stmt_execute($stmt);
      $emRes = mysqli_stmt_get_result($stmt);

      while ($e = mysqli_fetch_assoc($emRes)) {
        $name = trim(($e['user_name'] ?? '') . ' ' . ($e['user_fname'] ?? ''));
        $emps[] = [
          'user_id' => $e['age_user_id'],
          'name' => ($name !== '' ? $name : $e['age_user_id']),
        ];
      }

      mysqli_stmt_close($stmt);
    }
	
	  $perms = [];
    $stmt = mysqli_prepare(
      $connect,
      "SELECT perm_key
       FROM tb_agency_role_permission
       WHERE ag_id = ? AND role = ?
       AND can_view = 1
       ORDER BY arp_id ASC"
    );

    if ($stmt) {
      $role = 'employer';
      mysqli_stmt_bind_param($stmt, "is", $agId, $role);
      mysqli_stmt_execute($stmt);
      $permRes = mysqli_stmt_get_result($stmt);

      while ($p = mysqli_fetch_assoc($permRes)) {
        $perms[] = $p['perm_key'];
      }

      mysqli_stmt_close($stmt);
    }

    $rows[] = [
      'ag_id' => $agId,
      'ag_job' => $r['ag_job'],
      'ag_format' => $r['ag_format'],
      'ag_contract' => $r['ag_contract'],
      'ag_located' => $r['ag_located'],
      'ag_latitude' => $r['ag_latitude'],
      'ag_longitude' => $r['ag_longitude'],
      'ag_start_date' => $r['ag_start_date'],
      'ag_end_date' => $r['ag_end_date'],
      'ag_status' => (int)$r['ag_status'],
      'employers' => $emps,
      'permissions' => $perms
    ];
  }

  jexit(['success' => true, 'data' => $rows]);
}

/* =========================================================
 * CREATE
 * ========================================================= */
if ($action === 'create') {
  $ag_job        = req('ag_job');
  $ag_format     = req('ag_format');
  $ag_contract   = req('ag_contract');
  $ag_located    = req('ag_located');
  $ag_latitude   = req('ag_latitude');
  $ag_longitude  = req('ag_longitude');
  $ag_start_date = req('ag_start_date');
  $ag_end_date   = req('ag_end_date');
  $ag_status     = (int)req('ag_status', '1');

  $employers = parse_employers(req('employers', ''));
  $menus     = parse_menus(req('menus', '[]'));
  $role      = 'employer';

  if ($ag_job === '' || $ag_contract === '') {
    jexit(['success' => false, 'message' => 'กรุณากรอก รหัสหน่วยงาน และ ชื่อหน่วยงาน']);
  }

  validate_date_range($ag_start_date, $ag_end_date);

  $stmt = mysqli_prepare($connect, "SELECT 1 FROM tb_agency WHERE ag_job = ? LIMIT 1");
  if (!$stmt) jexit(['success' => false, 'message' => 'prepare dup-check failed']);

  mysqli_stmt_bind_param($stmt, "s", $ag_job);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $dup = ($res && mysqli_fetch_row($res)) ? true : false;
  mysqli_stmt_close($stmt);

  if ($dup) {
    jexit(['success' => false, 'message' => 'รหัสหน่วยงานนี้ถูกใช้แล้ว']);
  }

  $ag_details = '';
  $ag_area = '';
  $ag_token = '';
  $ag_username = '';
  $ag_op = '';
  $ag_user_id = '';
  $ag_cpb_groupanswer = 0;

  $sql = "INSERT INTO tb_agency
          (ag_job, ag_format, ag_contract, ag_details, ag_area, ag_located,
           ag_token, ag_longitude, ag_latitude, ag_username, ag_op, ag_user_id,
           ag_start_date, ag_end_date, ag_cpb_groupanswer, ag_status, ag_ins)
          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, ?, NOW())";

  $stmt = mysqli_prepare($connect, $sql);
  if (!$stmt) jexit(['success' => false, 'message' => 'prepare failed']);

  mysqli_stmt_bind_param(
    $stmt,
    "ssssssssssssssii",
    $ag_job,
    $ag_format,
    $ag_contract,
    $ag_details,
    $ag_area,
    $ag_located,
    $ag_token,
    $ag_longitude,
    $ag_latitude,
    $ag_username,
    $ag_op,
    $ag_user_id,
    $ag_start_date,
    $ag_end_date,
    $ag_cpb_groupanswer,
    $ag_status
  );

  $ok = mysqli_stmt_execute($stmt);
  $newId = mysqli_insert_id($connect);
  mysqli_stmt_close($stmt);

  if (!$ok) {
    jexit(['success' => false, 'message' => 'insert failed']);
  }

  sync_employers($connect, (int)$newId, $employers);
  sync_permissions($connect, (int)$newId, $role, $menus);

  jexit([
    'success' => true,
    'message' => 'create ok',
    'ag_id' => (int)$newId
  ]);
}

/* =========================================================
 * UPDATE
 * ========================================================= */
if ($action === 'update') {
  $ag_id         = (int)req('ag_id', req('id', '0'));
  $ag_job        = req('ag_job');
  $ag_format     = req('ag_format');
  $ag_contract   = req('ag_contract');
  $ag_located    = req('ag_located');
  $ag_latitude   = req('ag_latitude');
  $ag_longitude  = req('ag_longitude');
  $ag_start_date = req('ag_start_date');
  $ag_end_date   = req('ag_end_date');
  $ag_status     = (int)req('ag_status', '1');

  $ag_start_date = ($ag_start_date === '') ? null : $ag_start_date;
  $ag_end_date   = ($ag_end_date === '') ? null : $ag_end_date;

  $employers = parse_employers(req('employers', ''));
  $menus     = parse_menus(req('menus', '[]'));
  $role      = 'employer';

  if ($ag_id <= 0) {
    jexit([
      'success' => false,
      'message' => 'invalid ag_id (ไม่ได้ส่ง ag_id มา หรือค่าเป็น 0)',
      'debug' => ['ag_id' => $ag_id, 'post_keys' => array_keys($_POST)]
    ]);
  }

  if (!agency_exists($connect, $ag_id)) {
    jexit(['success' => false, 'message' => 'ไม่พบหน่วยงานที่ต้องการแก้ไข']);
  }

  if ($ag_job === '' || $ag_contract === '') {
    jexit(['success' => false, 'message' => 'กรุณากรอก รหัสหน่วยงาน และ ชื่อหน่วยงาน']);
  }

  validate_date_range($ag_start_date, $ag_end_date);

  $stmt = mysqli_prepare($connect, "SELECT 1 FROM tb_agency WHERE ag_job = ? AND ag_id <> ? LIMIT 1");
  if (!$stmt) jexit(['success' => false, 'message' => 'prepare dup-check failed']);

  mysqli_stmt_bind_param($stmt, "si", $ag_job, $ag_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $dup = ($res && mysqli_fetch_row($res)) ? true : false;
  mysqli_stmt_close($stmt);

  if ($dup) {
    jexit(['success' => false, 'message' => 'รหัสหน่วยงานนี้ถูกใช้แล้ว']);
  }

  $sql = "UPDATE tb_agency
          SET ag_job = ?, ag_format = ?, ag_contract = ?, ag_located = ?,
              ag_latitude = ?, ag_longitude = ?, ag_start_date = ?, ag_end_date = ?, ag_status = ?
          WHERE ag_id = ?";

  $stmt = mysqli_prepare($connect, $sql);
  if (!$stmt) jexit(['success' => false, 'message' => 'prepare failed']);

  mysqli_stmt_bind_param(
    $stmt,
    "ssssssssii",
    $ag_job,
    $ag_format,
    $ag_contract,
    $ag_located,
    $ag_latitude,
    $ag_longitude,
    $ag_start_date,
    $ag_end_date,
    $ag_status,
    $ag_id
  );

  $ok = mysqli_stmt_execute($stmt);

  if (!$ok) {
    jexit([
      'success' => false,
      'message' => 'update failed',
      'sql_error' => mysqli_stmt_error($stmt)
    ]);
  }

  mysqli_stmt_close($stmt);

  sync_employers($connect, $ag_id, $employers);
  sync_permissions($connect, $ag_id, $role, $menus);

  jexit(['success' => true, 'message' => 'update ok']);
}

/* =========================================================
 * DELETE
 * ========================================================= */
if ($action === 'delete') {
  $ag_id = (int)req('ag_id', '0');
  if ($ag_id <= 0) {
    jexit(['success' => false, 'message' => 'invalid ag_id']);
  }

  $stmt = mysqli_prepare($connect, "DELETE FROM tb_agency_employer WHERE age_ag_id = ?");
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $ag_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
  }

  $stmt = mysqli_prepare($connect, "DELETE FROM tb_agency_role_permission WHERE ag_id = ?");
  if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $ag_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
  }

  $stmt = mysqli_prepare($connect, "DELETE FROM tb_agency WHERE ag_id = ? LIMIT 1");
  if (!$stmt) jexit(['success' => false, 'message' => 'prepare failed']);

  mysqli_stmt_bind_param($stmt, "i", $ag_id);
  $ok = mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);

  if (!$ok) {
    jexit(['success' => false, 'message' => 'delete failed']);
  }

  jexit(['success' => true, 'message' => 'delete ok']);
}

jexit(['success' => false, 'message' => 'unknown action']);
?>