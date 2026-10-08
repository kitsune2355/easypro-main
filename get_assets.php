<?php
@session_start();
header('Content-Type: application/json; charset=utf-8');
require_once "config_ctrl/connect.php";
mysqli_set_charset($connect, "utf8mb4");

/* =========================
   ✅ JSON exit helper
========================= */
function jexit($ok, $extra = []) {
  echo json_encode(array_merge(["ok"=>$ok], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}

/* =========================
   ✅ helper bind param แบบ reference (PHP 7.4)
========================= */
function bind_params($stmt, $types, &$params){
  $bind = [];
  $bind[] = $types;
  foreach($params as $k => $v){
    $bind[] = &$params[$k];
  }
  return call_user_func_array([$stmt, 'bind_param'], $bind);
}

/* =========================
   ✅ URL helpers (สำคัญ)
========================= */
function base_url(){
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  $proto = $https ? 'https://' : 'http://';
  $host  = $_SERVER['HTTP_HOST'] ?? 'localhost';

  // ให้ base เป็นโฟลเดอร์ที่ไฟล์นี้อยู่ เช่น https://happylandgroup.biz/es/
  $dir = trim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
  return $proto . $host . ($dir !== '' ? "/{$dir}/" : "/");
}

// ✅ encode แบบ "คง / ไว้" (ไม่ให้กลายเป็น %2F)
function encode_path_keep_slash($path){
  $path = str_replace('\\', '/', $path);
  $path = preg_replace('#/+#', '/', $path);
  $path = trim($path, '/');
  if ($path === '') return '';

  $parts = explode('/', $path);
  $parts = array_map('rawurlencode', $parts);
  return implode('/', $parts);
}

function asset_file_url($filename){
  $filename = trim((string)$filename);
  if($filename === '' || $filename === '0') return null;

  // เผื่อ DB เก็บ AttFile%2F... มาแล้ว
  $filename = urldecode($filename);

  // ถ้าเป็น URL เต็มอยู่แล้ว
  if(preg_match('/^https?:\/\//i', $filename)) return $filename;

  $filename = ltrim($filename, "/\\");
  $filename = str_replace('\\', '/', $filename);

  return rtrim(base_url(), '/') . '/' . encode_path_keep_slash($filename);
}

function map_asset_row($row){
  if(!$row) return $row;
  $row['fileUpload1_url'] = asset_file_url($row['fileUpload1'] ?? '');
  return $row;
}

/* =========================
   ✅ INPUT
========================= */
$ag_id = (int)($_SESSION['sess_user_agency'] ?? 0);
if ($ag_id <= 0) jexit(false, ["message"=>"no ag_id", "data"=>[]]);

$action   = trim($_GET['action'] ?? 'search');
$q        = trim($_GET['q'] ?? "");
$building = (int)($_GET['building'] ?? 0); // area_id
$type_id  = (int)($_GET['type'] ?? 0);
$limit    = min(max((int)($_GET['limit'] ?? 10), 1), 50);
$offset   = max((int)($_GET['offset'] ?? 0), 0);
$ass_id   = (int)($_GET['ass_id'] ?? 0);

/* =========================
   ✅ debug (เช็คว่าไฟล์นี้ถูกใช้งานจริง)
   /get_assets.php?action=debug
========================= */
if ($action === 'debug') {
  jexit(true, [
    "file" => __FILE__,
    "ver"  => "get_assets.php unified 2026-01-29",
    "ag_id"=> $ag_id
  ]);
}

/* =========================
   ✅ action=one
   - ลองแบบกรอง building ก่อน
   - ถ้าไม่เจอ -> fallback ไม่กรอง building (เพื่อ Drawer แสดงได้แน่นอน)
========================= */
if ($action === 'one') {
  if ($ass_id <= 0) jexit(true, ["data"=>null]);

  // 1) try with building (ถ้าส่งมา)
  if ($building > 0) {
    $sql = "
      SELECT
        a.ass_id,
        a.ass_code,
        a.asset_name,
        a.asset_sn,
        a.asset_model,
        a.fileUpload1,
        a.asset_type,
        g.TGroupName,
        a.asset_status,
        a.asset_company,
        a.asset_rp_area_id
      FROM tb_ass_list a
      LEFT JOIN tb_asset_group g ON g.GroupId = a.asset_type
      WHERE a.ass_ag_id = ?
        AND a.asset_rp_area_id = ?
        AND a.ass_id = ?
      LIMIT 1
    ";

    $stmt = mysqli_prepare($connect, $sql);
    if(!$stmt) jexit(false, ["message"=>"prepare failed", "error"=>mysqli_error($connect)]);

    $params = [$ag_id, $building, $ass_id];
    bind_params($stmt, "iii", $params);

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);

    if($row){
      $row = map_asset_row($row);
      jexit(true, ["data" => $row, "match" => "with_building"]);
    }
  }

  // 2) fallback without building
  $sql2 = "
    SELECT
      a.ass_id,
      a.ass_code,
      a.asset_name,
      a.asset_sn,
      a.asset_model,
      a.fileUpload1,
      a.asset_type,
      g.TGroupName,
      a.asset_status,
      a.asset_company,
      a.asset_rp_area_id
    FROM tb_ass_list a
    LEFT JOIN tb_asset_group g ON g.GroupId = a.asset_type
    WHERE a.ass_ag_id = ?
      AND a.ass_id = ?
    LIMIT 1
  ";

  $stmt2 = mysqli_prepare($connect, $sql2);
  if(!$stmt2) jexit(false, ["message"=>"prepare failed", "error"=>mysqli_error($connect)]);

  $params2 = [$ag_id, $ass_id];
  bind_params($stmt2, "ii", $params2);

  mysqli_stmt_execute($stmt2);
  $res2 = mysqli_stmt_get_result($stmt2);
  $row2 = mysqli_fetch_assoc($res2);

  $row2 = $row2 ? map_asset_row($row2) : null;
  jexit(true, ["data" => $row2, "match" => "fallback_no_building"]);
}

/* =========================
   ✅ action=types
========================= */
if ($action === 'types') {
  if ($building <= 0) jexit(true, ["data"=>[]]);

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
  if(!$stmt) jexit(false, ["message"=>"prepare failed", "error"=>mysqli_error($connect)]);

  $params = [$ag_id, $building];
  bind_params($stmt, "ii", $params);

  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);

  $data = [
    ["id" => 0,  "label" => "ทั้งหมด"],
    ["id" => -1, "label" => "Asset"],
  ];

  while($row = mysqli_fetch_assoc($res)){
    $gid = (int)$row['GroupId'];
    $lbl = (string)$row['TGroupName'];
    if ($gid > 0) $data[] = ["id" => $gid, "label" => $lbl];
  }

  jexit(true, ["data"=>$data]);
}

/* =========================
   ✅ action=search (default)
========================= */
if ($building <= 0) jexit(true, ["data"=>[], "count"=>0]);

$sql = "
  SELECT
    a.ass_id,
    a.ass_code,
    a.asset_name,
    a.asset_sn,
    a.asset_model,
    a.fileUpload1,
    a.asset_type,
    g.TGroupName,
    a.asset_status,
    a.asset_company,
    a.asset_rp_area_id
  FROM tb_ass_list a
  LEFT JOIN tb_asset_group g ON g.GroupId = a.asset_type
  WHERE a.ass_ag_id = ?
    AND a.asset_rp_area_id = ?
";

$params = [$ag_id, $building];
$types  = "ii";

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
  $sql .= " AND (a.ass_code LIKE ? OR a.asset_name LIKE ? OR a.asset_sn LIKE ?) ";
  $like = "%{$q}%";
  $types .= "sss";
  $params[] = $like; $params[] = $like; $params[] = $like;
}

$sql .= " ORDER BY a.ass_id DESC LIMIT ? OFFSET ? ";
$types .= "ii";
$params[] = $limit;
$params[] = $offset;

$stmt = mysqli_prepare($connect, $sql);
if(!$stmt) jexit(false, ["message"=>"prepare failed", "error"=>mysqli_error($connect)]);

bind_params($stmt, $types, $params);

mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$data = [];
while($row = mysqli_fetch_assoc($res)){
  $data[] = map_asset_row($row);
}

jexit(true, ["data"=>$data, "count"=>count($data)]);
