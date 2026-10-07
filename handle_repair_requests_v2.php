<?php
// handle_repair_requests.php (API ONLY) PHP 7.4 - SSR for ag-Grid v31

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Max-Age: 86400");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
  exit;
}

@session_start();
date_default_timezone_set('Asia/Bangkok');

include 'config_ctrl/connect.php';

if (mysqli_connect_errno()) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'DB connect failed: ' . mysqli_connect_error()], JSON_UNESCAPED_UNICODE);
  exit;
}
mysqli_set_charset($connect, "utf8mb4");

function build_base_url(){
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  $scheme = $https ? 'https://' : 'http://';
  $host = $_SERVER['HTTP_HOST'] ?? '';
  return $scheme . $host;
}

function json_out($arr, $code = 200){
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

function read_json_body(){
  $raw = file_get_contents("php://input");
  if(!$raw) return [];
  $j = json_decode($raw, true);
  return is_array($j) ? $j : [];
}

function is_absolute_url($s){
  return is_string($s) && (strpos($s, 'http://') === 0 || strpos($s, 'https://') === 0);
}

function sql_escape_like($s){
  // escape % _
  $s = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $s);
  return $s;
}

// ---------- Read action ----------
$body = read_json_body();
if (!$body && !empty($_POST) && is_array($_POST)) {
  $body = $_POST; // fallback รองรับ form-data/x-www-form-urlencoded
}

$action = '';
if (!empty($_GET['action'])) $action = trim($_GET['action']);
if ($action === '' && !empty($_POST['action'])) $action = trim((string)$_POST['action']);
if ($action === '' && !empty($body['action'])) $action = trim((string)$body['action']);

if ($action === '') {
  json_out(['success' => false, 'error' => 'Missing action'], 400);
}

// ---------- SSR Endpoint ----------
if ($action === 'get_all_ssr') {

  $ag_id = isset($body['ag_id']) ? (int)$body['ag_id'] : 0;
  if ($ag_id <= 0) json_out(['success' => false, 'error' => 'Invalid ag_id'], 400);

  $startRow = isset($body['startRow']) ? (int)$body['startRow'] : 0;
  $endRow   = isset($body['endRow']) ? (int)$body['endRow'] : 50;
  if ($startRow < 0) $startRow = 0;
  if ($endRow <= $startRow) $endRow = $startRow + 50;

  $limit  = $endRow - $startRow;
  if ($limit > 200) $limit = 200; // กันยิงหนัก (ปรับได้)
  $offset = $startRow;

  $q = isset($body['q']) ? trim((string)$body['q']) : '';
  $sortModel = isset($body['sortModel']) && is_array($body['sortModel']) ? $body['sortModel'] : [];
  $filterModel = isset($body['filterModel']) && is_array($body['filterModel']) ? $body['filterModel'] : [];

  // ✅ URL uploads จริง (แก้ path ให้ตรงระบบคุณ)
  // เดิมคุณมี: /es/API_es/uploads/
  $uploadBaseUrl = build_base_url() . '/API_es/uploads/';

  // ---------- Allowed sort fields mapping ----------
  $sortMap = [
    'report_date'   => 'r.report_date',
    'report_time'   => 'r.report_time',
    'name'          => 'r.name',
    'phone'         => 'r.phone',
    'building_name' => 'ta.area_name',
    'floor_name'    => 'tac.ac_name',
    'room_name'     => 'tar.ar_name',
    'problem_detail'=> 'r.problem_detail',
    'status'        => 'r.status',
    'created_by'    => 'r.created_by',
    'created_at'    => 'r.created_at',
    'asset_code'    => 'a.ass_code',
    'asset_name'    => 'a.asset_name',
    'machine_id'    => 'r.machine_id'
  ];

  // ---------- WHERE ----------
  $where = [];
  $params = [];
  $types = '';

  $where[] = 'r.ag_id = ?';
  $params[] = $ag_id;
  $types .= 'i';

  // Global search (q)
  if ($q !== '') {
    $qq = '%' . sql_escape_like($q) . '%';
    $where[] = "("
      . "r.name LIKE ? ESCAPE '\\\\' OR "
      . "r.phone LIKE ? ESCAPE '\\\\' OR "
      . "ta.area_name LIKE ? ESCAPE '\\\\' OR "
      . "tac.ac_name LIKE ? ESCAPE '\\\\' OR "
      . "tar.ar_name LIKE ? ESCAPE '\\\\' OR "
      . "r.problem_detail LIKE ? ESCAPE '\\\\' OR "
      . "r.status LIKE ? ESCAPE '\\\\' OR "
      . "a.ass_code LIKE ? ESCAPE '\\\\' OR "
      . "a.asset_name LIKE ? ESCAPE '\\\\' "
      . ")";
    for($i=0;$i<9;$i++){ $params[] = $qq; $types .= 's'; }
  }

  // Column filters (basic)
  foreach($filterModel as $colId => $f){
    if(!is_array($f)) continue;

    // แปลง colId ให้ตรง field ที่เรารองรับ
    $field = isset($sortMap[$colId]) ? $sortMap[$colId] : '';

    // บางคอลัมน์ใน grid ไม่มี field ตรง ๆ (เช่น "ผู้แจ้ง" ใช้ valueGetter)
    // ถ้าต้องการ filter ชื่อ/เบอร์ ให้ใช้ colId = name/phone ใน grid จะชัวร์
    if($field === '' && $colId === 'created_by') $field = 'r.created_by';
    if($field === '' && $colId === 'status') $field = 'r.status';
    if($field === '' && $colId === 'problem_detail') $field = 'r.problem_detail';
    if($field === '' && $colId === 'report_date') $field = 'r.report_date';

    if($field === '') continue;

    $filterType = isset($f['filterType']) ? $f['filterType'] : '';

    // Text filter
    if($filterType === 'text'){
      $type = isset($f['type']) ? $f['type'] : 'contains';
      $val  = isset($f['filter']) ? (string)$f['filter'] : '';

      if($val === '') continue;

      if($type === 'equals'){
        $where[] = "$field = ?";
        $params[] = $val;
        $types .= 's';
      }else{
        $like = '%' . sql_escape_like($val) . '%';
        $where[] = "$field LIKE ? ESCAPE '\\\\'";
        $params[] = $like;
        $types .= 's';
      }
    }

    // Date filter (expects YYYY-MM-DD)
    if($filterType === 'date'){
      $type = isset($f['type']) ? $f['type'] : 'equals';
      $dateFrom = isset($f['dateFrom']) ? (string)$f['dateFrom'] : '';

      if($dateFrom === '') continue;

      if($type === 'equals'){
        $where[] = "$field = ?";
        $params[] = $dateFrom;
        $types .= 's';
      }elseif($type === 'lessThan'){
        $where[] = "$field < ?";
        $params[] = $dateFrom;
        $types .= 's';
      }elseif($type === 'greaterThan'){
        $where[] = "$field > ?";
        $params[] = $dateFrom;
        $types .= 's';
      }elseif($type === 'inRange'){
        $dateTo = isset($f['dateTo']) ? (string)$f['dateTo'] : '';
        if($dateTo !== ''){
          $where[] = "($field BETWEEN ? AND ?)";
          $params[] = $dateFrom; $types .= 's';
          $params[] = $dateTo;   $types .= 's';
        }
      }
    }
  }

  $whereSql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

  // ---------- ORDER BY ----------
  $orderSql = "ORDER BY r.created_at DESC";
  if(!empty($sortModel)){
    $orders = [];
    foreach($sortModel as $s){
      if(!is_array($s)) continue;
      $colId = isset($s['colId']) ? (string)$s['colId'] : '';
      $sort  = isset($s['sort']) ? strtolower((string)$s['sort']) : 'desc';
      if(!isset($sortMap[$colId])) continue;
      $dir = ($sort === 'asc') ? 'ASC' : 'DESC';
      $orders[] = $sortMap[$colId] . ' ' . $dir;
    }
    if(count($orders)) $orderSql = 'ORDER BY ' . implode(', ', $orders);
  }

  // ---------- COUNT (total) ----------
  $sqlCount = "
    SELECT COUNT(DISTINCT r.id) AS cnt
    FROM repair_requests r
    LEFT JOIN tb_ass_list a ON a.ass_id = r.machine_id
    LEFT JOIN tb_area ta        ON r.building = ta.area_id
    LEFT JOIN tb_area_class tac ON r.floor    = tac.ac_id
    LEFT JOIN tb_area_room tar  ON r.room     = tar.ar_id
    $whereSql
  ";

  $stmtC = mysqli_prepare($connect, $sqlCount);
  if(!$stmtC) json_out(['success'=>false,'error'=>'Prepare count failed: '.mysqli_error($connect)], 500);

  if($types !== ''){
    mysqli_stmt_bind_param($stmtC, $types, ...$params);
  }
  if(!mysqli_stmt_execute($stmtC)) json_out(['success'=>false,'error'=>'Execute count failed: '.mysqli_stmt_error($stmtC)], 500);

  $resC = mysqli_stmt_get_result($stmtC);
  $total = 0;
  if($resC){
    $rC = mysqli_fetch_assoc($resC);
    $total = isset($rC['cnt']) ? (int)$rC['cnt'] : 0;
  }
  mysqli_stmt_close($stmtC);

  // ---------- DATA (page) ----------
  $sqlData = "
    SELECT
      r.id,
      r.report_date, r.report_time,
      r.name, r.phone,
      r.building, r.floor, r.room,
      r.problem_detail,
      r.created_by,
      r.status,
      r.ag_id,
      r.rp_format,
      r.machine_id,
      r.created_at,

      a.ass_id   AS asset_ass_id,
      a.ass_code AS asset_code,
      a.asset_name,
      a.asset_sn,
      a.asset_model,

      ta.area_name AS building_name,
      tac.ac_name  AS floor_name,
      tar.ar_name  AS room_name
    FROM repair_requests r
    LEFT JOIN tb_ass_list a ON a.ass_id = r.machine_id
    LEFT JOIN tb_area ta        ON r.building = ta.area_id
    LEFT JOIN tb_area_class tac ON r.floor    = tac.ac_id
    LEFT JOIN tb_area_room tar  ON r.room     = tar.ar_id
    $whereSql
    GROUP BY r.id
    $orderSql
    LIMIT ?
    OFFSET ?
  ";

  $stmt = mysqli_prepare($connect, $sqlData);
  if(!$stmt) json_out(['success'=>false,'error'=>'Prepare data failed: '.mysqli_error($connect)], 500);

  // bind params + limit/offset
  $types2 = $types . 'ii';
  $params2 = $params;
  $params2[] = $limit;
  $params2[] = $offset;

  mysqli_stmt_bind_param($stmt, $types2, ...$params2);

  if(!mysqli_stmt_execute($stmt)) json_out(['success'=>false,'error'=>'Execute data failed: '.mysqli_stmt_error($stmt)], 500);

  $res = mysqli_stmt_get_result($stmt);
  if(!$res) json_out(['success'=>false,'error'=>'Get result failed'], 500);

  $rows = [];
  $ids = [];
  while($row = mysqli_fetch_assoc($res)){
    if(empty($row['building_name'])) $row['building_name'] = '-';
    if(empty($row['floor_name']))    $row['floor_name'] = '-';
    if(empty($row['room_name']))     $row['room_name'] = '-';

    $row['images'] = []; // เติมทีหลัง
    $rows[] = $row;
    $ids[] = (int)$row['id'];
  }
  mysqli_stmt_close($stmt);

  // ---------- Images (2nd query) ----------
  if(count($ids)){
    $in = implode(',', array_fill(0, count($ids), '?'));
    $typesIn = str_repeat('i', count($ids));

    $sqlImg = "
      SELECT repair_request_id, path, file_name, created_at
      FROM repair_images
      WHERE repair_request_id IN ($in)
      ORDER BY created_at ASC
    ";

    $stmtI = mysqli_prepare($connect, $sqlImg);
    if($stmtI){
      mysqli_stmt_bind_param($stmtI, $typesIn, ...$ids);
      if(mysqli_stmt_execute($stmtI)){
        $resI = mysqli_stmt_get_result($stmtI);
        $imgMap = []; // id => [url,url...]

        while($ri = mysqli_fetch_assoc($resI)){
          $rid = (int)$ri['repair_request_id'];
          $p = '';
          if(!empty($ri['path'])) $p = trim(str_replace('\\','/', (string)$ri['path']));
          $fn = !empty($ri['file_name']) ? trim((string)$ri['file_name']) : '';

          if($p === '' && $fn === '') continue;

          if($fn !== ''){
            $p = trim($p, '/');
            $rel = ($p !== '') ? ($p . '/' . $fn) : $fn;
          }else{
            $rel = ltrim($p, '/');
          }

          if($rel === '') continue;

          if(is_absolute_url($rel)){
            $url = $rel;
          }else{
            $url = rtrim($uploadBaseUrl, '/') . '/' . ltrim($rel, '/');
          }

          if(!isset($imgMap[$rid])) $imgMap[$rid] = [];
          $imgMap[$rid][] = $url;
        }
      }
      mysqli_stmt_close($stmtI);

      // merge into rows
      for($i=0;$i<count($rows);$i++){
        $rid = (int)$rows[$i]['id'];
        $rows[$i]['images'] = isset($imgMap[$rid]) ? $imgMap[$rid] : [];
      }
    }
  }

  json_out([
    'success' => true,
    'total'   => $total,
    'data'    => $rows
  ]);
}

// fallback
json_out(['success' => false, 'error' => 'Unknown action'], 400);
