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

function jexit($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
function req($key, $default=''){ return isset($_POST[$key]) ? trim($_POST[$key]) : $default; }
function hash_pass_triple_md5($p){ return md5(md5(md5($p))); }
function is_valid_email($email){ return $email==='' ? true : (bool)filter_var($email, FILTER_VALIDATE_EMAIL); }

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'list';

/**
 * FIXED RULES:
 * - user_level = สิทธิ์การใช้งาน (Role)
 * - user_status_login = สถานะ (1=Active, 0=Inactive)
 * - user_open ไม่ใช้
 * - user_agency ฟิกเป็น '0'
 */

// -------------------------------
// ACTION: list (ตารางผู้ใช้) => เฉพาะ super_admin
// -------------------------------
if ($action === 'list') {
 $sql = "SELECT 
          u.id,
          u.user_id,
          u.user_name,
          u.user_fname,
          u.user_level,
          u.user_email,
          u.user_status_login,
          u.user_department,
          GROUP_CONCAT(DISTINCT a.ag_contract ORDER BY a.ag_contract SEPARATOR ' | ') AS agency_names
        FROM tb_user u
        LEFT JOIN tb_agency_employer ae ON ae.age_user_id = u.user_id
        LEFT JOIN tb_agency a ON a.ag_id = ae.age_ag_id
        WHERE u.user_level = 'super_admin'
           OR (u.user_level = 'employer' AND (FIND_IN_SET('1', u.user_department) > 0 OR FIND_IN_SET('3', u.user_department) > 0))
        GROUP BY u.id, u.user_id, u.user_name, u.user_fname, u.user_level, u.user_email, u.user_status_login, u.user_department
        ORDER BY u.id DESC";

  $res = mysqli_query($connect, $sql);
  if (!$res) jexit(['success'=>false,'message'=>'query failed']);

  $rows = [];
  while($r = mysqli_fetch_assoc($res)){
    $rows[] = [
	  'id' => (int)$r['id'],
	  'user_id' => $r['user_id'],
	  'user_name' => $r['user_name'],
	  'user_fname' => $r['user_fname'],
	  'user_level' => $r['user_level'],
	  'user_email' => $r['user_email'],
	  'user_status_login' => (int)$r['user_status_login'],
	  'user_department' => $r['user_department'],
	  'agency_names' => $r['agency_names'] ? explode(' | ', $r['agency_names']) : [],
	];
  }
  jexit(['success'=>true,'data'=>$rows]);
}

// -------------------------------
// ACTION: list_super_admin (select)
// -------------------------------
if ($action === 'list_super_admin') {
  $sql = "SELECT id, user_id, user_name, user_fname, user_level, user_email, user_status_login, user_department
          FROM tb_user
          WHERE user_level = 'super_admin'
             OR (user_level = 'employer' AND (FIND_IN_SET('1', user_department) > 0 OR FIND_IN_SET('3', user_department) > 0))
          ORDER BY id DESC";

  $res = mysqli_query($connect, $sql);
  if (!$res) jexit(['success'=>false,'message'=>'query failed']);

  $rows = [];
  while($r = mysqli_fetch_assoc($res)){
    $rows[] = [
      'id' => (int)$r['id'],
      'user_id' => $r['user_id'],
      'user_name' => $r['user_name'],
      'user_fname' => $r['user_fname'],
      'user_level' => $r['user_level'],
      'user_email' => $r['user_email'],
      'user_status_login' => (int)$r['user_status_login'],
      'user_department' => $r['user_department'],
    ];
  }
  jexit(['success'=>true,'data'=>$rows]);
}
// -------------------------------
// ACTION: create
// -------------------------------
if ($action === 'create') {
  $user_id    = req('user_id');
  $pass_plain = req('user_password');
  $user_name  = req('user_name');
  $user_fname = req('user_fname');
  $user_email = req('user_email');
  $user_level = req('user_level', 'super_admin'); // Role
	$user_department = req('user_department', '');
  $user_status_login = (int)req('user_status_login', '1'); // Status
  $user_agency = '0';

  if ($user_id==='' || $pass_plain==='' || $user_name==='') {
    jexit(['success'=>false,'message'=>'กรุณากรอก Username, Password, ชื่อจริง']);
  }
  if (!is_valid_email($user_email)) jexit(['success'=>false,'message'=>'รูปแบบอีเมลไม่ถูกต้อง']);

  // username ซ้ำ
  $stmt = mysqli_prepare($connect, "SELECT 1 FROM tb_user WHERE user_id=? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "s", $user_id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $dup = ($res && mysqli_fetch_row($res)) ? true : false;
  mysqli_stmt_close($stmt);
  if ($dup) jexit(['success'=>false,'message'=>'Username นี้ถูกใช้แล้ว']);

  $hashed = hash_pass_triple_md5($pass_plain);
  if ($user_level === 'employer' && !in_array($user_department, ['1', '3'], true)) {
	  jexit(['success'=>false,'message'=>'กรุณาเลือกประเภท Employer ให้ถูกต้อง']);
	}
	
	if ($user_level !== 'employer') {
	  $user_department = '';
	}

  $sql = "INSERT INTO tb_user
        (user_id, user_password, user_name, user_fname, user_email, user_department, user_level, user_status_login, user_agency)
        VALUES (?,?,?,?,?,?,?,?,?)";
$stmt = mysqli_prepare($connect, $sql);
if (!$stmt) jexit(['success'=>false,'message'=>'prepare failed']);

mysqli_stmt_bind_param(
  $stmt,
  "sssssssis",
  $user_id,
  $hashed,
  $user_name,
  $user_fname,
  $user_email,
  $user_department,
  $user_level,
  $user_status_login,
  $user_agency
);

  $ok = mysqli_stmt_execute($stmt);
  $newId = mysqli_insert_id($connect);
  mysqli_stmt_close($stmt);

  if(!$ok) jexit(['success'=>false,'message'=>'insert failed']);
  jexit(['success'=>true,'message'=>'create ok','id'=>(int)$newId]);
}

// -------------------------------
// ACTION: update
// -------------------------------
if ($action === 'update') {
  $id         = (int)req('id','0');
  $user_id    = req('user_id');
  $pass_plain = req('user_password'); // ว่าง=ไม่เปลี่ยน
  $user_name  = req('user_name');
  $user_fname = req('user_fname');
  $user_email = req('user_email');
  $user_level = req('user_level','super_admin'); // Role
	$user_department = req('user_department', '');
  $user_status_login = (int)req('user_status_login','1'); // Status
  $user_agency = '0';
  if ($user_level === 'employer' && !in_array($user_department, ['1', '3'], true)) {
	  jexit(['success'=>false,'message'=>'กรุณาเลือกประเภท Employer ให้ถูกต้อง']);
	}
	
	if ($user_level !== 'employer') {
	  $user_department = '';
	}
  if ($id<=0) jexit(['success'=>false,'message'=>'invalid id']);
  if ($user_id==='' || $user_name==='') jexit(['success'=>false,'message'=>'กรุณากรอก Username และ ชื่อจริง']);
  if (!is_valid_email($user_email)) jexit(['success'=>false,'message'=>'รูปแบบอีเมลไม่ถูกต้อง']);
 
  // username ซ้ำกับคนอื่น
  $stmt = mysqli_prepare($connect, "SELECT 1 FROM tb_user WHERE user_id=? AND id<>? LIMIT 1");
  mysqli_stmt_bind_param($stmt, "si", $user_id, $id);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $dup = ($res && mysqli_fetch_row($res)) ? true : false;
  mysqli_stmt_close($stmt);
  if ($dup) jexit(['success'=>false,'message'=>'Username นี้ถูกใช้แล้ว']);

  if ($pass_plain !== '') {
    $hashed = hash_pass_triple_md5($pass_plain);
     $sql = "UPDATE tb_user
				SET user_id=?, user_password=?, user_name=?, user_fname=?, user_email=?, user_department=?, user_level=?, user_status_login=?, user_agency=?
				WHERE id=?";
    $stmt = mysqli_prepare($connect, $sql);
    if(!$stmt) jexit(['success'=>false,'message'=>'prepare failed']);
    mysqli_stmt_bind_param($stmt, "ssssssisi", $user_id, $hashed, $user_name, $user_fname, $user_email, $user_level, $user_status_login, $user_agency, $id);
  } else {
    $sql = "UPDATE tb_user
			SET user_id=?, user_password=?, user_name=?, user_fname=?, user_email=?, user_department=?, user_level=?, user_status_login=?, user_agency=?
			WHERE id=?";
    $stmt = mysqli_prepare($connect, $sql);
    if(!$stmt) jexit(['success'=>false,'message'=>'prepare failed']);
	mysqli_stmt_bind_param(
	  $stmt,
	  "sssssssisi",
	  $user_id,
	  $hashed,
	  $user_name,
	  $user_fname,
	  $user_email,
	  $user_department,
	  $user_level,
	  $user_status_login,
	  $user_agency,
	  $id
	);
  }

  $ok = mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);

  if(!$ok) jexit(['success'=>false,'message'=>'update failed']);
  jexit(['success'=>true,'message'=>'update ok']);
}

// -------------------------------
// ACTION: delete
// -------------------------------
if ($action === 'delete') {
  $id = (int)req('id','0');
  if ($id<=0) jexit(['success'=>false,'message'=>'invalid id']);

  $stmt = mysqli_prepare($connect, "DELETE FROM tb_user WHERE id=? LIMIT 1");
  if(!$stmt) jexit(['success'=>false,'message'=>'prepare failed']);
  mysqli_stmt_bind_param($stmt, "i", $id);
  $ok = mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);

  if(!$ok) jexit(['success'=>false,'message'=>'delete failed']);
  jexit(['success'=>true,'message'=>'delete ok']);
}

jexit(['success'=>false,'message'=>'unknown action']);