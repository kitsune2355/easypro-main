<?php
@session_start();
header('Content-Type: application/json; charset=utf-8');

include "config_ctrl/connect.php";
include "config_ctrl/checksession.php";

if (!isset($_SESSION['sess_user_level_es'])) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'message' => 'forbidden'], JSON_UNESCAPED_UNICODE);
  exit();
}

$userId = $_SESSION['sess_user_id_es'] ?? '';
$levelCheck = $_SESSION['sess_user_level_es_check'] ?? '0';

if ($levelCheck == '1') {
  $sql = "
    SELECT a.ag_id, a.ag_job, a.ag_contract
    FROM tb_agency_employer e
    INNER JOIN tb_agency a ON a.ag_id = e.age_ag_id
    WHERE e.age_user_id = '".mysqli_real_escape_string($connect, $userId)."'
      AND a.ag_status = 1
    ORDER BY a.ag_contract ASC
  ";
} else {
  $sql = "
    SELECT ag_id, ag_job, ag_contract
    FROM tb_agency
    WHERE ag_status = 1
    ORDER BY ag_format DESC
  ";
}

$q = mysqli_query($connect, $sql);
if (!$q) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
  exit();
}

$data = [];
while ($r = mysqli_fetch_assoc($q)) {
  $data[] = [
    'ag_id' => (int)$r['ag_id'],
    'code'  => $r['ag_job'],
    'name'  => $r['ag_contract'],
  ];
}

echo json_encode([
  'ok' => true,
  'data' => $data
], JSON_UNESCAPED_UNICODE);