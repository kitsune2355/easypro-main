<?php
@session_start();
include "config_ctrl/connect.php";

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

date_default_timezone_set('Asia/Bangkok');

if ($connect->connect_error) {
  http_response_code(500);
  echo json_encode(["ok"=>false,"error"=>"Connection failed"]);
  exit;
}

// ✅ ใช้ ag_id จาก session เป็นหลัก (ตามที่คุณใช้จริง)
$ag_id = 0;
if (!empty($_SESSION['sess_user_agency'])) {
  $ag_id = (int)$_SESSION['sess_user_agency'];
} elseif (!empty($_GET['ag_id'])) { // เผื่อไว้ตอนเทส
  $ag_id = (int)$_GET['ag_id'];
}

if ($ag_id <= 0) {
  http_response_code(400);
  echo json_encode(["ok"=>false,"error"=>"ag_id is required"]);
  exit;
}

/*
  ✅ คืนค่าเป็นรูปแบบเดียวกับ spatialDB เดิม:
  [
    { id, name, floors: [ { id, name, rooms: [ {id, name} ] } ] }
  ]
*/
$sql = "
SELECT
  a.area_id, a.area_name,
  c.ac_id,  c.ac_name,
  r.ar_id,  r.ar_name
FROM tb_area a
LEFT JOIN tb_area_class c
  ON c.ac_area_id = a.area_id
  AND c.ac_status IN (0,1)
LEFT JOIN tb_area_room r
  ON r.ar_area_id = a.area_id
  AND r.ar_ac_id = c.ac_id
  AND r.ar_status IN (0,1)
WHERE a.ag_id = ?
  AND a.area_status IN (0,1)
  AND c.ac_status = 0
ORDER BY a.area_name ASC, c.ac_name ASC, r.ar_name ASC
";

$stmt = $connect->prepare($sql);
$stmt->bind_param("i", $ag_id);
$stmt->execute();
$res = $stmt->get_result();

$areas = [];
$floorIndex = [];

while($row = $res->fetch_assoc()){
  $area_id = (int)$row['area_id'];
  $ac_id   = isset($row['ac_id']) ? (int)$row['ac_id'] : 0;
  $ar_id   = isset($row['ar_id']) ? (int)$row['ar_id'] : 0;

  if(!isset($areas[$area_id])){
    $areas[$area_id] = [
      "id" => $area_id,
      "name" => $row['area_name'],
      "type" => "Area/Building",
      "icon" => "building-2",
      "floors" => []
    ];
  }

  if($ac_id > 0){
    $key = $area_id . "|" . $ac_id;
    if(!isset($floorIndex[$key])){
      $areas[$area_id]["floors"][] = [
        "id" => $ac_id,
        "name" => $row['ac_name'],
        "rooms" => []
      ];
      $floorIndex[$key] = count($areas[$area_id]["floors"]) - 1;
    }

    if($ar_id > 0){
      $idx = $floorIndex[$key];
      $areas[$area_id]["floors"][$idx]["rooms"][] = [
        "id" => $ar_id,
        "name" => $row['ar_name']
      ];
    }
  }
}

$stmt->close();
$connect->close();

echo json_encode([
  "ok" => true,
  "ag_id" => $ag_id,
  "data" => array_values($areas)
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
