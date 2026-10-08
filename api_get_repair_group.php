<?php

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once "config_ctrl/checksession.php";
require_once "config_ctrl/connect.php";

mysqli_set_charset($connect, "utf8");

// ✅ เพิ่ม ag_id จาก session
$ag_id = $_SESSION['sess_user_agency'] ?? '';
$ag_id = trim((string)$ag_id);

if ($ag_id === '') {
  echo json_encode(['success'=>false,'message'=>'ไม่พบค่า sess_user_agency ใน Session'], JSON_UNESCAPED_UNICODE);
  exit;
}

// ✅ เพิ่มเงื่อนไข rpg_ag_id = $ag_id
$sql = "SELECT rpg_id, rpg_name
        FROM tb_repair_group
        WHERE rpg_status = 0
          AND rpg_ag_id = ?
        ORDER BY rpg_name ASC";

$stmt = mysqli_prepare($connect, $sql);
if (!$stmt) {
  echo json_encode(['success'=>false,'message'=>'Prepare failed: '.mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
  exit;
}

mysqli_stmt_bind_param($stmt, "s", $ag_id);

if (!mysqli_stmt_execute($stmt)) {
  echo json_encode(['success'=>false,'message'=>'Execute failed: '.mysqli_stmt_error($stmt)], JSON_UNESCAPED_UNICODE);
  exit;
}

$result = mysqli_stmt_get_result($stmt);
if (!$result) {
  echo json_encode(['success'=>false,'message'=>'Get result failed: '.mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
  exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
  $data[] = ['id'=>(int)$row['rpg_id'], 'name'=>$row['rpg_name']];
}

mysqli_stmt_close($stmt);

echo json_encode(['success'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE);
