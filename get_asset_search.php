<?php
@session_start();
header('Content-Type: application/json; charset=utf-8');
require_once "config_ctrl/connect.php";
mysqli_set_charset($connect, "utf8");

function jexit($ok, $extra = []) {
  echo json_encode(array_merge(["ok"=>$ok], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

$ag_id = (int)($_SESSION['sess_user_agency'] ?? 0);
if ($ag_id <= 0) jexit(false, ["data"=>[]]);

$action  = trim($_GET['action'] ?? 'search');

$q        = trim($_GET['q'] ?? "");
$building = (int)($_GET['building'] ?? 0);
//$floor    = (int)($_GET['floor'] ?? 0);
//$room     = (int)($_GET['room'] ?? 0);

$type_id  = (int)($_GET['type'] ?? 0); // ✅ 0=all, -1=ungrouped, >0=GroupId
$limit    = min(max((int)($_GET['limit'] ?? 10),1),50);
$offset   = max((int)($_GET['offset'] ?? 0),0);

if ($building <= 0) {
  if ($action === 'types') {
    jexit(true, [
      "data" => [
        ["id" => 0,  "label" => "ทั้งหมด"],
        ["id" => -1, "label" => "Asset"]
      ]
    ]);
  }
  jexit(true, ["data"=>[]]);
}

/* =========================================================
   ✅ ACTION: types  -> คืนรายการ type สำหรับสร้างปุ่ม filter (ID)
========================================================= */
if ($action === 'types') {

  // ดึง group ที่มีการใช้งานจริงใน building นี้
  $sql = "
    SELECT DISTINCT g.GroupId, g.TGroupName
    FROM tb_ass_list a
    INNER JOIN tb_asset_group g ON g.GroupId = a.asset_type
    WHERE a.ass_ag_id = ?
      AND a.asset_rp_area_id = ?
      AND g.TGroupName IS NOT NULL
      AND g.TGroupName <> ''
    ORDER BY g.TGroupName
  ";

  $stmt = mysqli_prepare($connect, $sql);
  mysqli_stmt_bind_param($stmt, "ii", $ag_id, $building);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);

  $data = [];
  // ปุ่มมาตรฐาน
  $data[] = ["id" => 0,  "label" => "ทั้งหมด"];
  $data[] = ["id" => -1, "label" => "Asset"]; // ungrouped

  while($row = mysqli_fetch_assoc($res)){
    $gid = (int)$row['GroupId'];
    $lbl = (string)$row['TGroupName'];
    if ($gid > 0) {
      $data[] = ["id" => $gid, "label" => $lbl];
    }
  }

  jexit(true, ["data"=>$data]);
}

/* =========================================================
   ✅ ACTION: search + รองรับ type filter แบบ ID
========================================================= */

$sql = "
SELECT
  a.ass_id,
  a.ass_code,
  a.asset_name,
  a.asset_state,
  a.dep_id,
  a.asset_status,
  a.asset_company,

  b.area_name,
  f.ac_name,
  r.ar_name,

  a.asset_type,
  g.TGroupName
FROM tb_ass_list a
LEFT JOIN tb_area b ON b.area_id = a.asset_rp_area_id
LEFT JOIN tb_area_class f ON f.ac_id = a.asset_rp_ac_id
LEFT JOIN tb_area_room r ON r.ar_id = a.asset_rp_ar_id
LEFT JOIN tb_asset_group g ON g.GroupId = a.asset_type
WHERE a.ass_ag_id = ?
  AND a.asset_rp_area_id = ?
";

$params = [$ag_id, $building];
$types  = "ii";

// ✅ filter ตาม type_id
// 0  = all (ไม่กรอง)
// -1 = ungrouped (asset_type null/0)
// >0 = groupid
if ($type_id !== 0) {
  if ($type_id === -1) {
    $sql .= " AND (a.asset_type IS NULL OR a.asset_type = 0) ";
  } else {
    $sql .= " AND a.asset_type = ? ";
    $types .= "i";
    $params[] = $type_id;
  }
}

if ($q !== "") {
  $sql .= "
    AND (
      a.ass_code LIKE ?
      OR a.asset_name LIKE ?
      OR b.area_name LIKE ?
      OR f.ac_name LIKE ?
      OR r.ar_name LIKE ?
    )
  ";
  $like = "%$q%";
  $types .= "sssss";
  array_push($params, $like, $like, $like, $like, $like);
}

$sql .= " ORDER BY a.ass_id DESC LIMIT ? OFFSET ? ";
$types .= "ii";
array_push($params, $limit, $offset);

$stmt = mysqli_prepare($connect, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$data = [];
while($row = mysqli_fetch_assoc($res)){
  $loc = [];
  if($row['area_name']) $loc[] = $row['area_name'];
  if($row['ac_name'])   $loc[] = $row['ac_name'];
  if($row['ar_name'])   $loc[] = $row['ar_name'];

  $data[] = [
    "id"        => (int)$row['ass_id'],
    "code"      => $row['ass_code'],
    "name"      => $row['asset_name'],
    "type_id"   => (int)($row['asset_type'] ?? 0),   // ✅ ส่งกลับไว้ด้วย
    "type_name" => $row['TGroupName'],               // ✅ เอาไว้โชว์ badge
    "location"  => implode(" • ", $loc),
    "status"    => $row['asset_status'],
    "owner"     => $row['asset_company']
  ];
}

jexit(true, ["data"=>$data, "count"=>count($data)]);
