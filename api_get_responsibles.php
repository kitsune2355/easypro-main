<?php 

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once "config_ctrl/checksession.php";
require_once "config_ctrl/connect.php";

mysqli_set_charset($connect, "utf8");

$ag_id = $_SESSION['sess_user_agency'] ?? '';
$ag_id = trim((string)$ag_id);

if ($ag_id === '') {
  echo json_encode(['success'=>false,'message'=>'Missing sess_user_agency in session'], JSON_UNESCAPED_UNICODE);
  exit;
}

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

$sql = "
  SELECT user_id, user_code, user_fname, user_name, user_position
  FROM tb_user
  WHERE user_department IN ('1','2')
    AND user_agency = ?
";

$params = [$ag_id];
$types  = "s";

if ($q !== '') {
  $sql .= " AND (
      user_code LIKE ?
      OR user_fname LIKE ?
      OR user_name LIKE ?
      OR CONCAT(user_fname,' ',user_name) LIKE ?
      OR user_position LIKE ?
    )";
  $like = "%{$q}%";
  array_push($params, $like, $like, $like, $like, $like);
  $types .= "sssss";
}

$sql .= " ORDER BY user_ord ASC, user_fname ASC, user_name ASC LIMIT 200";

$stmt = mysqli_prepare($connect, $sql);
if (!$stmt) {
  echo json_encode(['success'=>false,'message'=>'Prepare failed: '.mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
  exit;
}

// bind_param ต้องส่ง by reference
$bind = [];
$bind[] = $types;
foreach ($params as $k => $v) {
  $bind[] = &$params[$k];
}
call_user_func_array([$stmt, 'bind_param'], $bind);

if (!mysqli_stmt_execute($stmt)) {
  echo json_encode(['success'=>false,'message'=>'Execute failed: '.mysqli_stmt_error($stmt)], JSON_UNESCAPED_UNICODE);
  exit;
}

$res = mysqli_stmt_get_result($stmt);
$data = [];

while ($row = mysqli_fetch_assoc($res)) {
  $full  = trim($row['user_name'].' '.$row['user_fname']);
  $label = $full;
 // if ($row['user_code'] !== '') $label .= " ({$row['user_code']})";
//  if ($row['user_position'] !== '') $label .= " - {$row['user_position']}";

  $data[] = [
    'id'       => $row['user_id'],
    'code'     => $row['user_code'],
    'name'     => $full,
    'position' => $row['user_position'],
    'label'    => $label,
  ];
}

echo json_encode(['success'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE);
