<?php
@session_start();
header('Content-Type: application/json; charset=utf-8');

include "config_ctrl/connect.php";
include "config_ctrl/checksession.php";
 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'message' => 'method_not_allowed']);
  exit();
}

$ag_id = isset($_POST['ag_id']) ? (int)$_POST['ag_id'] : 0;
if ($ag_id <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'message' => 'invalid_ag_id']);
  exit();
}

$sql = "SELECT ag_id, ag_contract, ag_job, ag_start_date, ag_end_date
        FROM tb_agency
        WHERE ag_id = $ag_id AND ag_status = 1
        LIMIT 1";
$q = mysqli_query($connect, $sql);
if (!$q) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => mysqli_error($connect)]);
  exit();
}

$r = mysqli_fetch_assoc($q);
if (!$r) {
  http_response_code(404);
  echo json_encode(['ok' => false, 'message' => 'agency_not_found']);
  exit();
}

// ???? session
$_SESSION['sess_user_agency']   		= (int)$r['ag_id'];
$_SESSION['sess_agency_code'] 			= $r['ag_job'];
$_SESSION['sess_agency_name'] 			= $r['ag_contract'];
$_SESSION['sess_ag_start_date'] 		= $r['ag_start_date'];
$_SESSION['sess_ag_end_date'] 	 		= $r['ag_end_date'];

echo json_encode([
  'ok' => true,
  'message' => 'saved',
  'selected' => [
    'ag_id' => (int)$r['ag_id'],
    'code' => $r['ag_job'],
    'name' => $r['ag_contract'],
  ]
], JSON_UNESCAPED_UNICODE);
