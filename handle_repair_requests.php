<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
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

$action = $_GET['action'] ?? '';
if (!$action) {
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => 'Missing action'], JSON_UNESCAPED_UNICODE);
  exit;
}

function bind_params($stmt, $types, $params) {
  if (!$stmt) return false;
  if ($types && !empty($params)) {
    $stmt->bind_param($types, ...$params);
  }
  return true;
}

function build_base_url(){
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  $scheme = $https ? 'https://' : 'http://';
  $host = $_SERVER['HTTP_HOST'] ?? '';
  return $scheme . $host;
}

function is_absolute_url($url){
  return (bool)preg_match('/^https?:\/\//i', (string)$url);
}

/**
 * helper หยิบค่า POST รองรับหลายชื่อ key (snake/camel) แต่จะไม่คืนค่า '' / null
 */
function pick_post(array $keys, $default = null) {
  foreach ($keys as $k) {
    if (isset($_POST[$k])) {
      $v = $_POST[$k];
      if (is_string($v)) $v = trim($v);
      if ($v !== '' && $v !== null) return $v;
    }
  }
  return $default;
}

/**
 * เวอร์ชันที่ "ยอมให้ค่าว่างได้"
 * ถ้า key มีใน $_POST จะคืนค่า (อาจเป็น '') ถ้าไม่มี key เลย -> null
 */
function pick_post_allow_empty(array $keys) {
  foreach ($keys as $k) {
    if (array_key_exists($k, $_POST)) {
      $v = $_POST[$k];
      if (is_string($v)) $v = trim($v);
      return $v; // อาจเป็น '' ได้
    }
  }
  return null;
}

/**
 * แปลง string ว่าง ให้กลายเป็น NULL (ใช้กับ field ที่ NULL ได้)
 */
function normalize_nullable(&$v) {
  if ($v === null) return;
  if (is_string($v) && trim($v) === '') {
    $v = null;
  }
}

function pick_post_array(array $keys) {
  foreach ($keys as $k) {
    if (isset($_POST[$k])) {
      $v = $_POST[$k];
      if (is_array($v)) return $v;
      if ($v === '' || $v === null) return [];
      return [$v];
    }
  }
  return [];
}

function save_repair_responsibles($connect, $repair_request_id, array $user_ids, $assigned_by) {
  $repair_request_id = intval($repair_request_id);

  $clean_ids = [];
  foreach ($user_ids as $uid) {
    $uid = trim((string)$uid);
    if ($uid !== '') {
      $clean_ids[] = $uid;
    }
  }
  $clean_ids = array_values(array_unique($clean_ids));

  // ลบของเดิมก่อน
  $stmtDel = $connect->prepare("DELETE FROM repair_request_responsible WHERE repair_request_id = ?");
  if (!$stmtDel) {
    throw new Exception('Prepare delete responsible failed: ' . $connect->error);
  }
  $stmtDel->bind_param("i", $repair_request_id);
  if (!$stmtDel->execute()) {
    $err = $stmtDel->error;
    $stmtDel->close();
    throw new Exception('Delete responsible failed: ' . $err);
  }
  $stmtDel->close();

  if (empty($clean_ids)) {
    return 0;
  }

  // รองรับทั้งกรณี option ส่งมาเป็น user_id และ user_name เช่น user-1
  $stmtUser = $connect->prepare("
    SELECT
      user_id,
      user_name,
      user_fname,
      user_department,
      user_tel
    FROM tb_user
    WHERE CAST(user_id AS CHAR) = ?
       OR user_name = ?
    LIMIT 1
  ");
  if (!$stmtUser) {
    throw new Exception('Prepare select user failed: ' . $connect->error);
  }

  $stmtIns = $connect->prepare("
    INSERT INTO repair_request_responsible (
      repair_request_id,
      user_id,
      user_name,
      user_fname,
      user_department,
      user_tel,
      assigned_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?)
  ");
  if (!$stmtIns) {
    throw new Exception('Prepare insert responsible failed: ' . $connect->error);
  }

  $saved = 0;

  foreach ($clean_ids as $uid) {
    $stmtUser->bind_param("ss", $uid, $uid);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result();
    $user = $resUser->fetch_assoc();

    if ($user) {
      $user_id = (string)$user['user_id'];
      $user_name = $user['user_name'] ?? '';
      $user_fname = $user['user_fname'] ?? '';
      $user_department = $user['user_department'] ?? '';
      $user_tel = $user['user_tel'] ?? '';
    } else {
      // fallback: ถ้าไม่เจอใน tb_user ก็ยังบันทึกค่าที่เลือกไว้
      $user_id = $uid;
      $user_name = $uid;
      $user_fname = '';
      $user_department = '';
      $user_tel = '';
    }

    $assigned_by_value = (string)$assigned_by;

    $stmtIns->bind_param(
      "issssss",
      $repair_request_id,
      $user_id,
      $user_name,
      $user_fname,
      $user_department,
      $user_tel,
      $assigned_by_value
    );

    if (!$stmtIns->execute()) {
      $err = $stmtIns->error;
      $stmtUser->close();
      $stmtIns->close();
      throw new Exception('Insert responsible failed: ' . $err);
    }

    $saved++;
  }

  $stmtUser->close();
  $stmtIns->close();

  return $saved;
}

/* ============================================
 *  GET ONE (สำหรับเปิด Drawer / แก้ไข)
 *  URL: handle_repair_requests.php?action=get_one&ag_id=...&id=...
 * ============================================ */ 
if ($action === 'get_one') {

  $ag_id = $_GET['ag_id'] ?? null;
  $id    = intval($_GET['id'] ?? 0);

  if (!$ag_id || $id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing ag_id or id'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $sql = "
    SELECT
      r.id,
      r.report_date, 
      r.report_time,
      r.name, 
      r.phone,
      r.building, 
      r.floor, 
      r.room,
      r.problem_detail,
      r.urgency,

      r.status,
      r.ag_id,
      r.rp_format,
      r.machine_id,
      r.image_url,
      r.has_feedback,

      -- ส่วนรับงาน / ดำเนินการ
      r.received_date,
      r.received_by,
      r.process_date,
      r.process_time,

      -- ส่วนปิดงาน
      r.completed_by,
      r.completed_date,
      r.completed_time,
      r.completed_solution,
      r.service_type,
      r.job_type,

      r.created_by,
      r.created_at,

      a.ass_id   AS asset_ass_id,
      a.ass_code AS asset_code,
      a.asset_name,
      a.asset_sn,
      a.asset_model,

      ta.area_name AS building_name,
      tac.ac_name  AS floor_name,
      tar.ar_name  AS room_name,

      u.user_id,
      u.user_code,
      u.user_fname,
      u.user_name,
      u.user_department,
      u.user_position,
      u.user_tel,
      u.user_email
    FROM repair_requests r
    LEFT JOIN tb_user u      ON u.user_id = r.created_by
    LEFT JOIN tb_ass_list a  ON a.ass_id = r.machine_id
    LEFT JOIN tb_area ta        ON r.building = ta.area_id
    LEFT JOIN tb_area_class tac ON r.floor    = tac.ac_id
    LEFT JOIN tb_area_room tar  ON r.room     = tar.ar_id
    WHERE r.ag_id = ?
      AND r.id = ?
    LIMIT 1
  ";

  $stmt = $connect->prepare($sql);
  if(!$stmt){
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $connect->error], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $stmt->bind_param("si", $ag_id, $id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if(!$row){
    echo json_encode(['success' => true, 'row' => null], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ชื่อคนสร้างให้อ่านง่าย
  $row['created_by_name'] = $row['user_name'] ?: ($row['user_fname'] ?? $row['created_by']);

  $uploadBaseUrl = build_base_url() . '/API_es/uploads/';

  // ===== รูปแจ้งซ่อม (repair_images) =====
  $images = [];
  $sqlImg = "SELECT path, file_name, created_at
             FROM repair_images
             WHERE repair_request_id = ?
             ORDER BY created_at ASC";
  $stmtI = $connect->prepare($sqlImg);
  if ($stmtI) {
    $stmtI->bind_param("i", $id);
    $stmtI->execute();
    $resI = $stmtI->get_result();

    while ($ri = $resI->fetch_assoc()) {
      $p  = !empty($ri['path']) ? trim(str_replace('\\','/', (string)$ri['path'])) : '';
      $fn = !empty($ri['file_name']) ? trim((string)$ri['file_name']) : '';

      if ($p === '' && $fn === '') continue;

      if ($fn !== '') {
        $p = trim($p, '/');
        $rel = ($p !== '') ? ($p . '/' . $fn) : $fn;
      } else {
        $rel = ltrim($p, '/');
      }

      if ($rel === '') continue;

      $url = is_absolute_url($rel)
        ? $rel
        : (rtrim($uploadBaseUrl, '/') . '/' . ltrim($rel, '/'));

      $images[] = $url;
    }
    $stmtI->close();
  }
  $row['images'] = $images;

  // ===== รูปปิดจ๊อป (repair_completed_images) =====
  $completedImages = [];
  $sqlImg2 = "SELECT path, file_name, uploaded_at
              FROM repair_completed_images
              WHERE repair_request_id = ?
              ORDER BY uploaded_at ASC";
  $stmtC = $connect->prepare($sqlImg2);
  if ($stmtC) {
    $stmtC->bind_param("i", $id);
    $stmtC->execute();
    $resC = $stmtC->get_result();

    while ($rc = $resC->fetch_assoc()) {
      $p  = !empty($rc['path']) ? trim(str_replace('\\','/', (string)$rc['path'])) : '';
      $fn = !empty($rc['file_name']) ? trim((string)$rc['file_name']) : '';

      if ($p === '' && $fn === '') continue;

      if ($fn !== '') {
        $p = trim($p, '/');
        $rel = ($p !== '') ? ($p . '/' . $fn) : $fn;
      } else {
        $rel = ltrim($p, '/');
      }

      if ($rel === '') continue;

      $url = is_absolute_url($rel)
        ? $rel
        : (rtrim($uploadBaseUrl, '/') . '/' . ltrim($rel, '/'));

      $completedImages[] = $url;
    }
    $stmtC->close();
  }
  $row['completed_images'] = $completedImages;

  // ===== อะไหล่ / รายการซ่อม จาก tb_repair_detail =====
  $parts = [];
  $sqlParts = "
    SELECT
      rpd_id,
      rpd_rp_id,
      rpd_product_id,
      wh_id,
      pd_gen_code,
	  wh_name,
      rpd_details_head,
      rpd_details,
      rpd_brand,
      rpd_qty,
      rpd_price,
      pd_unit,
      rpd_sum_money
    FROM tb_repair_detail
    LEFT JOIN tb_wh_stock AS ws
    ON ws.id = wh_id     -- ✅ join ตามที่ต้องการ
    WHERE rpd_rp_id = ?
    ORDER BY rpd_id ASC
  ";

  $stmtP = $connect->prepare($sqlParts);
  if ($stmtP) {
    $stmtP->bind_param("i", $id);
    $stmtP->execute();
    $resP = $stmtP->get_result();

    while ($rp = $resP->fetch_assoc()) {
      $parts[] = [
        'rpd_id'          => (int)$rp['rpd_id'],
        'rpd_rp_id'       => (int)$rp['rpd_rp_id'],
        'rpd_product_id'  => (int)$rp['rpd_product_id'],
        'wh_id'           => (int)$rp['wh_id'],
      'wh'          => $rp['wh_name'],   // ✅ เพิ่มชื่อคลัง
        'pd_gen_code'     => $rp['pd_gen_code'],
        'rpd_details_head'=> $rp['rpd_details_head'],
        'rpd_details'     => $rp['rpd_details'],
        'rpd_brand'       => $rp['rpd_brand'],
        'rpd_qty'         => (float)$rp['rpd_qty'],
        'rpd_price'       => (float)$rp['rpd_price'],
        'pd_unit'         => $rp['pd_unit'],
        'rpd_sum_money'   => (float)$rp['rpd_sum_money'],
      ];
    }
    $stmtP->close();
  }
  $row['parts'] = $parts;
  
  $responsibles = [];
	$responsibleUserIds = [];
	
	$sqlResp = "
	  SELECT
		user_id,
		user_name,
		user_fname,
		user_department,
		user_tel
	  FROM repair_request_responsible
	  WHERE repair_request_id = ?
	  ORDER BY id ASC
	";
	
	$stmtResp = $connect->prepare($sqlResp);
	if ($stmtResp) {
	  $stmtResp->bind_param("i", $id);
	  $stmtResp->execute();
	  $resResp = $stmtResp->get_result();
	
	  while ($rr = $resResp->fetch_assoc()) {
		$responsibles[] = [
		  'user_id' => (string)$rr['user_id'],
		  'user_name' => $rr['user_name'],
		  'user_fname' => $rr['user_fname'],
		  'user_department' => $rr['user_department'],
		  'user_tel' => $rr['user_tel']
		];
	
		$responsibleUserIds[] = (string)$rr['user_id'];
	  }
	
	  $stmtResp->close();
	}
	
	// fallback: ถ้าตารางใหม่ยังไม่มีข้อมูล แต่ repair_requests.received_by มีค่า
	if (empty($responsibleUserIds) && !empty($row['received_by'])) {
	  $responsibleUserIds[] = (string)$row['received_by'];
	}
	
	$row['responsibles'] = $responsibles;
	$row['responsible_user_ids'] = $responsibleUserIds;

  echo json_encode(['success' => true, 'row' => $row], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ============================================
 *  GET FILTER VALUES
 * ============================================ */
if ($action === 'get_filter_values') {
  $column = $_GET['column'] ?? '';
  $ag_id  = isset($_GET['ag_id']) ? (int)$_GET['ag_id'] : 0;

  if (!$column || !$ag_id) {
    echo json_encode([]);
    exit;
  }

  $values = [];
  $sql    = '';
  $types  = 'i';
  $params = [$ag_id];

  // 👉 1) building_name: ดึงจาก tb_area (ใช้ ag_id จาก tb_area)
  if ($column === 'building_name') {
    $sql = "
      SELECT DISTINCT ta.area_name AS value
      FROM tb_area ta
      WHERE ta.ag_id = ?
        AND ta.area_status = 0
      ORDER BY ta.area_name ASC
    ";
  }
  // 👉 2) floor_name: ดึงจาก tb_area_class + join tb_area เพื่อเช็ค ag_id
  else if ($column === 'floor_name') {
    $sql = "
      SELECT DISTINCT ac.ac_name AS value
      FROM tb_area_class ac
      INNER JOIN tb_area ta ON ta.area_id = ac.ac_area_id
      WHERE ta.ag_id = ?
        AND ac.ac_status = 0
      ORDER BY ac.ac_name ASC
    ";
  }
  // 👉 3) room_name: ดึงจาก tb_area_room + join tb_area เพื่อเช็ค ag_id
  else if ($column === 'room_name') {
    $sql = "
      SELECT DISTINCT ar.ar_name AS value
      FROM tb_area_room ar
      INNER JOIN tb_area ta ON ta.area_id = ar.ar_area_id
      WHERE ta.ag_id = ?
        AND ar.ar_status = 0
      ORDER BY ar.ar_name ASC
    ";
  }
  // 👉 4) คอลัมน์อื่น ๆ (status, created_by, rp_format ฯลฯ) ใช้จาก repair_requests ตามเดิม
  else {
    $column = $_GET['column'] ?? '';
	  $ag_id  = isset($_GET['ag_id']) ? (int)$_GET['ag_id'] : 0;
	
	  if (!$column || !$ag_id) {
		echo json_encode([]);
		exit;
	  }
	
	  $values = [];
	  $sql    = '';
	  $types  = 'i';
	  $params = [$ag_id];
	
	  // ====== [เคสพิเศษ] created_by ======
	  if ($column === 'created_by') {
		$sql = "
		  SELECT DISTINCT
			r.created_by AS value,
			TRIM(
			  CONCAT(
				COALESCE(u.user_fname, ''),
				' ',
				COALESCE(u.user_name, '')
			  )
			) AS label
		  FROM repair_requests r
		  LEFT JOIN tb_user u ON u.user_id = r.created_by
		  WHERE r.ag_id = ?
			AND r.created_by IS NOT NULL
			AND r.created_by != ''
		  ORDER BY label ASC
		";
	
		$stmt = $connect->prepare($sql);
		if (!$stmt) {
		  echo json_encode([]);
		  exit;
		}
		$stmt->bind_param($types, ...$params);
		$stmt->execute();
		$res = $stmt->get_result();
	
		while ($row = $res->fetch_assoc()) {
		  if ($row['value'] === null || $row['value'] === '') continue;
		  $values[] = [
			'value' => $row['value'],          // ใช้ filter
			'label' => $row['label'] ?: $row['value'], // เอาไว้โชว์ชื่อ
		  ];
		}
	
		echo json_encode($values, JSON_UNESCAPED_UNICODE);
		exit;
	  }
	
	  // ====== เคสคอลัมน์อื่น ใช้ map เดิม ======
	  $map = [
		'status'     => 'r.status',
		'created_by' => 'r.created_by', // ตรงนี้ยังอยู่ได้ เผื่อใช้ที่อื่น
		'rp_format'  => 'r.rp_format',
		// ...
	  ];
	
	  if (!isset($map[$column])) {
		echo json_encode([]);
		exit;
	  }
	
	  $dbCol = $map[$column];
	
	  $sql = "
		SELECT DISTINCT $dbCol AS value
		FROM repair_requests r
		LEFT JOIN tb_user u      ON u.user_id = r.created_by
		LEFT JOIN tb_ass_list a  ON a.ass_id = r.machine_id
		LEFT JOIN tb_area ta        ON r.building = ta.area_id
		LEFT JOIN tb_area_class tac ON r.floor    = tac.ac_id
		LEFT JOIN tb_area_room tar  ON r.room     = tar.ar_id
		WHERE r.ag_id = ?
		  AND $dbCol IS NOT NULL AND $dbCol != ''
		ORDER BY $dbCol ASC
	  ";
	
	  $stmt = $connect->prepare($sql);
	  if (!$stmt) {
		echo json_encode([]);
		exit;
	  }
	  $stmt->bind_param("i", $ag_id);
	  $stmt->execute();
	  $res = $stmt->get_result();
	
	  while ($row = $res->fetch_assoc()) {
		if ($row['value'] === null || $row['value'] === '') continue;
		$values[] = $row['value'];
	  }
	
	  echo json_encode($values, JSON_UNESCAPED_UNICODE);
	  exit;
  }

  $stmt = $connect->prepare($sql);
  if (!$stmt) {
    echo json_encode([]);
    exit;
  }

  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();

  while ($row = $res->fetch_assoc()) {
    if ($row['value'] === null || $row['value'] === '') continue;
    $values[] = $row['value'];
  }

  echo json_encode($values, JSON_UNESCAPED_UNICODE);
  exit;
}
/* ============================================
 *  GET ALL (server-side row model)
 * ============================================ */
if ($action === 'get_all') {

  $ag_id   = $_GET['ag_id'] ?? null;
  $search  = trim($_GET['search'] ?? '');

  $startRow = intval($_GET['startRow'] ?? 0);
  $endRow   = intval($_GET['endRow'] ?? 20);
  $limit    = max(1, $endRow - $startRow);

  $sortModel   = json_decode($_GET['sortModel'] ?? '[]', true);
  $filterModel = json_decode($_GET['filterModel'] ?? '{}', true);

  // ------- WHERE -------
  $whereSql = " WHERE r.ag_id = ?";
  $params   = [$ag_id];
  $types    = "s";

  $hasStatusFilter = (is_array($filterModel) && isset($filterModel['status']) && !empty($filterModel['status']));
  if (!$hasStatusFilter) {
    // default เฉพาะเดือน 2026-02
   $whereSql .= " AND LOWER(TRIM(r.status)) IN ('pending','inprogress','completed','feedback','cancel')
              AND DATE_FORMAT(r.report_date, '%Y-%m') > '2025-12' ";
  }

  // ---------- Global search ----------
  if ($search !== '') {
    $searchDigits = preg_replace('/\D+/', '', $search);

    $whereSql .= " AND (
      r.id LIKE ?
      OR r.name LIKE ?
      OR r.problem_detail LIKE ?
      OR r.status LIKE ?
      OR r.created_by LIKE ?
      OR a.ass_code LIKE ?
      OR a.asset_name LIKE ?
      OR ta.area_name LIKE ?
      OR tac.ac_name LIKE ?
      OR tar.ar_name LIKE ?
      OR r.phone LIKE ?
      OR u.user_fname LIKE ?
      OR u.user_name LIKE ?
      OR u.user_department LIKE ?
    ";

    $s = "%{$search}%";
    array_push($params, $s,$s,$s,$s,$s,$s,$s,$s,$s,$s,$s,$s,$s,$s);
    $types .= str_repeat("s", 14);

    if ($searchDigits !== '') {
      $whereSql .= " OR REPLACE(REPLACE(REPLACE(REPLACE(r.phone,'-',''),' ',''),'(',''),')','') LIKE ? ";
      $params[] = "%{$searchDigits}%";
      $types   .= "s";
    }

    $whereSql .= " ) ";
  }

  // ---------- Column filters ----------
  $filterMap = [
    'status'        => 'r.status',
    'created_by'    => 'r.created_by',
    'building_name' => 'ta.area_name',
    'floor_name'    => 'tac.ac_name',
    'room_name'     => 'tar.ar_name',
    'report_date'   => 'r.report_date',
    'rp_format'     => 'r.rp_format',
    'asset_code'    => 'a.ass_code',
    'asset_name'    => 'a.asset_name',
    'name'          => 'r.name',
    'problem_detail'=> 'r.problem_detail',
    'phone'         => 'r.phone',
  ];

  if (is_array($filterModel) && !empty($filterModel)) {
    foreach ($filterModel as $field => $info) {

      // 🟢 กรณีพิเศษ: filter วันที่ (report_date)
      if ($field === 'report_date' && ($info['filterType'] ?? '') === 'date') {

        $col = 'r.report_date';

        $type     = $info['type']     ?? 'inRange';
        $dateFrom = $info['dateFrom'] ?? '';
        $dateTo   = $info['dateTo']   ?? '';

        if ($dateFrom) $dateFrom = substr($dateFrom, 0, 10);
        if ($dateTo)   $dateTo   = substr($dateTo, 0, 10);

        if ($type === 'inRange') {
          if ($dateFrom && $dateTo) {
            $whereSql .= " AND $col BETWEEN ? AND ? ";
            $params[] = $dateFrom;
            $params[] = $dateTo;
            $types   .= "ss";
          } elseif ($dateFrom) {
            $whereSql .= " AND $col >= ? ";
            $params[] = $dateFrom;
            $types   .= "s";
          } elseif ($dateTo) {
            $whereSql .= " AND $col <= ? ";
            $params[] = $dateTo;
            $types   .= "s";
          }
        } elseif ($type === 'equals' && $dateFrom) {
          $whereSql .= " AND $col = ? ";
          $params[] = $dateFrom;
          $types   .= "s";
        }

        // 🔴 เพิ่มเลขที่เอกสาร (doc) เข้ามา
        $doc = trim($info['doc'] ?? '');
        if ($doc !== '') {
          $whereSql .= " AND r.rp_format LIKE ? ";
          $params[] = "%{$doc}%";
          $types   .= "s";
        }

        // 🟠 เพิ่มความเร่งด่วน (urgency)
        $urg = trim($info['urgency'] ?? '');
        if ($urg !== '') {
          $whereSql .= " AND r.urgency = ? ";
          $params[] = $urg;
          $types   .= "s";
        }

        continue; // ข้ามไม่ให้เข้า logic ปกติด้านล่าง
      }

      // ---------- filter ปกติ ----------
      if (!isset($filterMap[$field])) continue;
      $col = $filterMap[$field];

      // set filter
      if (($info['filterType'] ?? '') === 'set') {
        $values = $info['values'] ?? [];
        if (!empty($values)) {
          $placeholders = implode(',', array_fill(0, count($values), '?'));
          $whereSql .= " AND $col IN ($placeholders) ";
          foreach ($values as $v) { $params[] = $v; $types .= "s"; }
        }
      }

      // text filter
      if (($info['filterType'] ?? '') === 'text') {
        $val = trim($info['filter'] ?? '');
        if ($val !== '') {
          if ($field === 'name') {
            $whereSql .= " AND (r.name LIKE ? OR r.phone LIKE ?) ";
            $s = "%{$val}%";
            $params[] = $s;
            $params[] = $s;
            $types .= "ss";

            $digits = preg_replace('/\D+/', '', $val);
            if ($digits !== '') {
              $whereSql .= " OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(r.phone,'-',''),' ',''),'(',''),')',''),'+','') LIKE ? ";
              $params[] = "%{$digits}%";
              $types .= "s";
            }

            $whereSql .= " ";
          } else {
            $whereSql .= " AND $col LIKE ? ";
            $params[] = "%{$val}%";
            $types   .= "s";
          }
        }
      }

      // date filter สำหรับ column อื่น ๆ ถ้ามี
      if (($info['filterType'] ?? '') === 'date') {
        $type = $info['type'] ?? 'equals';
        $dateFrom = $info['dateFrom'] ?? '';
        $dateTo   = $info['dateTo'] ?? '';

        if ($dateFrom) $dateFrom = substr($dateFrom, 0, 10);
        if ($dateTo)   $dateTo   = substr($dateTo, 0, 10);

        if ($type === 'inRange') {
          if ($dateFrom && $dateTo) {
            $whereSql .= " AND $col BETWEEN ? AND ? ";
            $params[] = $dateFrom;
            $params[] = $dateTo;
            $types   .= "ss";
          } elseif ($dateFrom) {
            $whereSql .= " AND $col >= ? ";
            $params[] = $dateFrom;
            $types   .= "s";
          } elseif ($dateTo) {
            $whereSql .= " AND $col <= ? ";
            $params[] = $dateTo;
            $types   .= "s";
          }
        } else if ($type === 'equals') {
          if ($dateFrom) {
            $whereSql .= " AND $col = ? ";
            $params[] = $dateFrom;
            $types   .= "s";
          }
        } else if ($type === 'lessThan') {
          if ($dateFrom) {
            $whereSql .= " AND $col < ? ";
            $params[] = $dateFrom;
            $types   .= "s";
          }
        } else if ($type === 'greaterThan') {
          if ($dateFrom) {
            $whereSql .= " AND $col > ? ";
            $params[] = $dateFrom;
            $types   .= "s";
          }
        }
      }

    } // end foreach filterModel
  }

  // ------- ORDER BY -------
  $orderSql = " ORDER BY r.rp_format DESC, r.id DESC ";
  if (!empty($sortModel)) {
    $colId = $sortModel[0]['colId'] ?? '';
    $dir   = strtoupper($sortModel[0]['sort'] ?? 'DESC');
    $dir   = ($dir === 'ASC') ? 'ASC' : 'DESC';

    $allowedSort = [
      'id'            => 'r.id',
      'rp_format'     => 'r.rp_format',
      'report_date'   => 'r.report_date',
      'report_time'   => 'r.report_time',
      'status'        => 'r.status',
      'created_by'    => 'r.created_by',
      'building_name' => 'ta.area_name',
      'floor_name'    => 'tac.ac_name',
      'room_name'     => 'tar.ar_name',
      'name'          => 'r.name',
      'problem_detail'=> 'r.problem_detail',
      'phone'         => 'r.phone',
    ];
    if (isset($allowedSort[$colId])) {
      if ($colId === 'report_date') {
        $orderSql = " ORDER BY r.report_date {$dir}, r.rp_format {$dir}, r.id {$dir} ";
      } else {
        $orderSql = " ORDER BY {$allowedSort[$colId]} {$dir}, r.id {$dir} ";
      }
    }
  }

  // ------- COUNT -------
  $countSql = "
    SELECT COUNT(DISTINCT r.id) AS total
    FROM repair_requests r
    LEFT JOIN tb_user u         ON u.user_id = r.created_by
    LEFT JOIN tb_ass_list a     ON a.ass_id = r.machine_id
    LEFT JOIN tb_area ta        ON r.building = ta.area_id
    LEFT JOIN tb_area_class tac ON r.floor    = tac.ac_id
    LEFT JOIN tb_area_room tar  ON r.room     = tar.ar_id
    $whereSql
  ";

  $stmtCount = $connect->prepare($countSql);
  if(!$stmtCount){
    http_response_code(500);
    echo json_encode([
      'success' => false,
      'error'   => 'Prepare COUNT failed: ' . $connect->error,
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  bind_params($stmtCount, $types, $params);
  $stmtCount->execute();
  $total = intval(($stmtCount->get_result()->fetch_assoc()['total'] ?? 0));
  $stmtCount->close();

  // ------- DATA (เอา field ให้ใกล้กับ get_one) -------
  $dataSql = "
    SELECT
      r.id,
      r.report_date,
      r.report_time,
      r.name,
      r.phone,
      r.building,
      r.floor,
      r.room,
      r.problem_detail,
      r.urgency,

      r.status,
      r.ag_id,
      r.rp_format,
      r.machine_id,
      r.image_url,
      r.has_feedback,

      -- ส่วนรับงาน / ดำเนินการ
      r.received_date,
      r.received_by,
      r.process_date,
      r.process_time,

      -- ส่วนปิดงาน
      r.completed_by,
      r.completed_date,
      r.completed_time,
      r.completed_solution,
      r.service_type,
      r.job_type,
		rg.rpg_name AS service_type_name,
		rs.rps_name AS job_type_name,
      GROUP_CONCAT(
        DISTINCT COALESCE(NULLIF(rrr.user_name, ''), NULLIF(rrr.user_fname, ''), rrr.user_id)
        ORDER BY rrr.id ASC
        SEPARATOR '
'
      ) AS responsible_names,
      r.created_by,
      r.created_at,

      a.ass_id   AS asset_ass_id,
      a.ass_code AS asset_code,
      a.asset_name,
      a.asset_sn,
      a.asset_model,

      ta.area_name AS building_name,
      tac.ac_name  AS floor_name,
      tar.ar_name  AS room_name,

      u.user_id,
      u.user_code,
      u.user_fname,
      u.user_name,
      u.user_department,
      u.user_position,
      u.user_tel,
      u.user_email
    FROM repair_requests r
    LEFT JOIN tb_user u         ON u.user_id = r.created_by
    LEFT JOIN tb_ass_list a     ON a.ass_id = r.machine_id
    LEFT JOIN tb_area ta        ON r.building = ta.area_id
    LEFT JOIN tb_area_class tac ON r.floor    = tac.ac_id
    LEFT JOIN tb_area_room tar  ON r.room     = tar.ar_id 
	LEFT JOIN tb_repair_group rg
		   ON rg.rpg_id = r.service_type 
	LEFT JOIN tb_repair_system rs
		   ON rs.rps_id = r.job_type
    LEFT JOIN repair_request_responsible rrr
           ON rrr.repair_request_id = r.id
    $whereSql
    GROUP BY r.id
    $orderSql
    LIMIT ?
    OFFSET ?
  ";

  $paramsData = array_merge($params, [$limit, $startRow]);
  $typesData  = $types . "ii";

  $stmt = $connect->prepare($dataSql);
  if (!$stmt) {
    http_response_code(500);
    echo json_encode([
      'success' => false,
      'error'   => 'Prepare DATA failed: ' . $connect->error,
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  bind_params($stmt, $typesData, $paramsData);
  $stmt->execute();
  $res = $stmt->get_result();

  $rows = [];
  while ($row = $res->fetch_assoc()) {

    $created_by_name = $row['user_name'] ?: ($row['user_fname'] ?? $row['created_by']);

    $rows[] = [
      'id'              => (int)$row['id'],
      'report_date'     => $row['report_date'],
      'report_time'     => $row['report_time'],
      'reported_at'     => trim(($row['report_date'] ?? '') . ' ' . ($row['report_time'] ?? '')),
      'name'            => $row['name'],
      'phone'           => $row['phone'],
      'problem_detail'  => $row['problem_detail'],
      'urgency'         => $row['urgency'],

      'status'          => $row['status'],
      'ag_id'           => $row['ag_id'],
      'rp_format'       => $row['rp_format'],
      'machine_id'      => $row['machine_id'],
      'image_url'       => $row['image_url'],
      'has_feedback'    => (int)$row['has_feedback'],

      'received_date'   => $row['received_date'],
      'received_by'     => $row['received_by'],
      'process_date'    => $row['process_date'],
      'process_time'    => $row['process_time'],

      'completed_by'    => $row['completed_by'],
      'completed_date'  => $row['completed_date'],
      'completed_time'  => $row['completed_time'],
      'completed_solution' => $row['completed_solution'],
      'service_type'    => $row['service_type'],
      'job_type'        => $row['job_type'],
      'service_type_name'        => $row['service_type_name'],
      'job_type_name'        => $row['job_type_name'],
      'responsible_names'    => $row['responsible_names'] ?? '',
 


      'created_by'      => $row['created_by'],
      'created_by_name' => $created_by_name,
      'created_at'      => $row['created_at'],

      'building'        => $row['building'],
      'floor'           => $row['floor'],
      'room'            => $row['room'],

      'asset_code'      => $row['asset_code'],
      'asset_name'      => $row['asset_name'],
      'asset_sn'        => $row['asset_sn'],
      'asset_model'     => $row['asset_model'],

      'building_name'   => $row['building_name'] ?? 'ไม่ระบุ',
      'floor_name'      => $row['floor_name'] ?? '-',
      'room_name'       => $row['room_name'] ?? '-',

      // array ไว้ใส่ทีหลังจาก batch-query
      'images'           => [],
      'completed_images' => [],
      'parts'            => [],
    ];
  }
  $stmt->close();

  // ====== BATCH: รูป Before / After + parts ======
  $uploadBaseUrl = build_base_url() . '/API_es/uploads/';

  $ids = array_map(function($r){ return (int)$r['id']; }, $rows);

  if (count($ids)) {
    $in      = implode(',', array_fill(0, count($ids), '?'));
    $typesIn = str_repeat('i', count($ids));

    // ----- รูปแจ้งซ่อม (repair_images) -----
    $sqlImg = "
      SELECT repair_request_id, path, file_name, created_at
      FROM repair_images
      WHERE repair_request_id IN ($in)
      ORDER BY created_at ASC
    ";
    $stmtI = mysqli_prepare($connect, $sqlImg);
    if ($stmtI) {
      mysqli_stmt_bind_param($stmtI, $typesIn, ...$ids);
      if (mysqli_stmt_execute($stmtI)) {
        $resI   = mysqli_stmt_get_result($stmtI);
        $imgMap = [];

        while ($ri = mysqli_fetch_assoc($resI)) {
          $rid = (int)$ri['repair_request_id'];

          $p  = !empty($ri['path']) ? trim(str_replace('\\','/', (string)$ri['path'])) : '';
          $fn = !empty($ri['file_name']) ? trim((string)$ri['file_name']) : '';

          if ($p === '' && $fn === '') continue;

          if ($fn !== '') {
            $p   = trim($p, '/');
            $rel = ($p !== '') ? ($p . '/' . $fn) : $fn;
          } else {
            $rel = ltrim($p, '/');
          }
          if ($rel === '') continue;

          $url = is_absolute_url($rel)
            ? $rel
            : (rtrim($uploadBaseUrl, '/') . '/' . ltrim($rel, '/'));

          if (!isset($imgMap[$rid])) $imgMap[$rid] = [];
          $imgMap[$rid][] = $url;
        }

        foreach ($rows as &$r) {
          $rid = (int)$r['id'];
          $r['images'] = $imgMap[$rid] ?? [];
        }
        unset($r);
      }
      mysqli_stmt_close($stmtI);
    }

    // ----- รูปปิดจ๊อป (repair_completed_images) -----
    $sqlImg2 = "
      SELECT repair_request_id, path, file_name, uploaded_at
      FROM repair_completed_images
      WHERE repair_request_id IN ($in)
      ORDER BY uploaded_at ASC
    ";
    $stmtC = mysqli_prepare($connect, $sqlImg2);
    if ($stmtC) {
      mysqli_stmt_bind_param($stmtC, $typesIn, ...$ids);
      if (mysqli_stmt_execute($stmtC)) {
        $resC    = mysqli_stmt_get_result($stmtC);
        $cMap    = [];

        while ($rc = mysqli_fetch_assoc($resC)) {
          $rid = (int)$rc['repair_request_id'];

          $p  = !empty($rc['path']) ? trim(str_replace('\\','/', (string)$rc['path'])) : '';
          $fn = !empty($rc['file_name']) ? trim((string)$rc['file_name']) : '';

          if ($p === '' && $fn === '') continue;

          if ($fn !== '') {
            $p   = trim($p, '/');
            $rel = ($p !== '') ? ($p . '/' . $fn) : $fn;
          } else {
            $rel = ltrim($p, '/');
          }
          if ($rel === '') continue;

          $url = is_absolute_url($rel)
            ? $rel
            : (rtrim($uploadBaseUrl, '/') . '/' . ltrim($rel, '/'));

          if (!isset($cMap[$rid])) $cMap[$rid] = [];
          $cMap[$rid][] = $url;
        }

        foreach ($rows as &$r) {
          $rid = (int)$r['id'];
          $r['completed_images'] = $cMap[$rid] ?? [];
        }
        unset($r);
      }
      mysqli_stmt_close($stmtC);
    }

    // ----- parts / อะไหล่ (tb_repair_detail) -----
    $sqlParts = "
      SELECT
        rpd_id,
        rpd_rp_id,
        rpd_product_id,
        wh_id,
        pd_gen_code,
        ws.wh_name,
        rpd_details_head,
        rpd_details,
        rpd_brand,
        rpd_qty,
        rpd_price,
        pd_unit,
        rpd_sum_money
      FROM tb_repair_detail
      LEFT JOIN tb_wh_stock AS ws ON ws.id = wh_id
      WHERE rpd_rp_id IN ($in)
      ORDER BY rpd_id ASC
    ";
    $stmtP = mysqli_prepare($connect, $sqlParts);
    if ($stmtP) {
      mysqli_stmt_bind_param($stmtP, $typesIn, ...$ids);
      if (mysqli_stmt_execute($stmtP)) {
        $resP    = mysqli_stmt_get_result($stmtP);
        $partsMap = [];

        while ($rp = mysqli_fetch_assoc($resP)) {
          $rid = (int)$rp['rpd_rp_id'];

          if (!isset($partsMap[$rid])) $partsMap[$rid] = [];

          $partsMap[$rid][] = [
            'rpd_id'          => (int)$rp['rpd_id'],
            'rpd_rp_id'       => (int)$rp['rpd_rp_id'],
            'rpd_product_id'  => (int)$rp['rpd_product_id'],
            'wh_id'           => (int)$rp['wh_id'],
            'wh'              => $rp['wh_name'],
            'pd_gen_code'     => $rp['pd_gen_code'],
            'rpd_details_head'=> $rp['rpd_details_head'],
            'rpd_details'     => $rp['rpd_details'],
            'rpd_brand'       => $rp['rpd_brand'],
            'rpd_qty'         => (float)$rp['rpd_qty'],
            'rpd_price'       => (float)$rp['rpd_price'],
            'pd_unit'         => $rp['pd_unit'],
            'rpd_sum_money'   => (float)$rp['rpd_sum_money'],
          ];
        }

        foreach ($rows as &$r) {
          $rid = (int)$r['id'];
          $r['parts'] = $partsMap[$rid] ?? [];
        }
        unset($r);
      }
      mysqli_stmt_close($stmtP);
    }
  }

  echo json_encode([
    'rows'    => $rows,
    'lastRow' => $total
  ], JSON_UNESCAPED_UNICODE);
  exit;
}
/* ============================================
 *  CANCEL (ยกเลิกใบงาน)
 *  URL: handle_repair_requests.php?action=cancel
 *  Method: POST (FormData: id)
 * ============================================ */
if ($action === 'cancel') {

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $repairId  = (int) pick_post(['id', 'repair_id'], 0);
  $updatedBy = pick_post(['updated_by', 'updatedBy'], $_SESSION['sess_user_id_es'] ?? '');

  if ($repairId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid repair id'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if (empty($updatedBy)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Missing updated_by / session'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $connect->begin_transaction();

  try {
    // lock แถวก่อนอัปเดต กันการกดซ้ำพร้อมกัน
    $sqlGet = "SELECT id, rp_format, status FROM repair_requests WHERE id = ? FOR UPDATE";
    $stmtGet = $connect->prepare($sqlGet);
    if (!$stmtGet) {
      throw new Exception('Prepare get repair_requests failed: ' . $connect->error);
    }
    $stmtGet->bind_param('i', $repairId);
    $stmtGet->execute();
    $rowReq = $stmtGet->get_result()->fetch_assoc();
    $stmtGet->close();

    if (!$rowReq) {
      throw new Exception('Repair request not found');
    }

    if (in_array(strtolower((string)$rowReq['status']), ['cancel', 'canceled', 'cancelled'], true)) {
      $connect->commit();
      echo json_encode([
        'success'   => true,
        'message'   => 'ใบงานนี้ถูกยกเลิกอยู่แล้ว',
        'repair_id' => $repairId,
        'rp_format' => $rowReq['rp_format'],
        'status'    => 'cancel'
      ], JSON_UNESCAPED_UNICODE);
      exit;
    }

    $newStatus = 'cancel';

    $sqlCancel = "UPDATE repair_requests
                  SET status = ?,
                      updated_at = NOW(),
                      updated_by = ?
                  WHERE id = ?";
    $stmtCancel = $connect->prepare($sqlCancel);
    if (!$stmtCancel) {
      throw new Exception('Prepare cancel repair_requests failed: ' . $connect->error);
    }
    $stmtCancel->bind_param('ssi', $newStatus, $updatedBy, $repairId);

    if (!$stmtCancel->execute()) {
      $err = $stmtCancel->error;
      $stmtCancel->close();
      throw new Exception('Execute cancel repair_requests failed: ' . $err);
    }
    $stmtCancel->close();

    $connect->commit();

    echo json_encode([
      'success'   => true,
      'message'   => 'ยกเลิกใบงานเรียบร้อย',
      'repair_id' => $repairId,
      'rp_format' => $rowReq['rp_format'],
      'status'    => $newStatus
    ], JSON_UNESCAPED_UNICODE);
    exit;

  } catch (Exception $e) {
    $connect->rollback();
    http_response_code(500);
    echo json_encode([
      'success' => false,
      'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

/* ============================================
 *  UPDATE (โหมดเดียว: แก้ไข + รับงาน + ปิดงาน + รูป + อะไหล่)
 *  URL: handle_repair_requests.php?action=update
 *  Method: POST (FormData)
 * ============================================ */
if ($action === 'update') {

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $repairId  = (int) pick_post(['id', 'repair_id'], 0);
  $updatedBy = pick_post(['updated_by', 'updatedBy'], $_SESSION['sess_user_id_es'] ?? '');

  if ($repairId <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid repair id'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if (empty($updatedBy)) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Missing updated_by / session'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $connect->begin_transaction();

  try {
    // 1) ดึงข้อมูลเดิม (lock แถว)
    $sqlGet = "SELECT * FROM repair_requests WHERE id = ? FOR UPDATE";
    $stmtGet = $connect->prepare($sqlGet);
    if (!$stmtGet) {
      throw new Exception('Prepare get repair_requests failed: ' . $connect->error);
    }
    $stmtGet->bind_param('i', $repairId);
    $stmtGet->execute();
    $resGet = $stmtGet->get_result();
    $rowReq = $resGet->fetch_assoc();
    $stmtGet->close();

    if (!$rowReq) {
      throw new Exception('Repair request not found');
    }

    // ---------- 2) ค่าเดิมจาก DB ----------
    $report_date    = $rowReq['report_date'];
    $report_time    = $rowReq['report_time'];
    $name           = $rowReq['name'];
    $phone          = $rowReq['phone'];
    $building       = $rowReq['building'];
    $floor          = $rowReq['floor'];
    $room           = $rowReq['room'];
    $problem_detail = $rowReq['problem_detail'];
    $urgency        = $rowReq['urgency'];
    $image_url      = $rowReq['image_url'];
    $status_old     = $rowReq['status'];

    $received_date  = $rowReq['received_date'];
    $received_by    = $rowReq['received_by'];
    $process_date   = $rowReq['process_date'];
    $process_time   = $rowReq['process_time'];

    $completed_date     = $rowReq['completed_date'];
    $completed_time     = $rowReq['completed_time'];
    $completed_by       = $rowReq['completed_by'];
    $completed_solution = $rowReq['completed_solution'];
    $service_type       = $rowReq['service_type'];
    $job_type           = $rowReq['job_type'];

    $ag_id        = $rowReq['ag_id'];
    $rp_format    = $rowReq['rp_format'];
    $has_feedback = $rowReq['has_feedback'];
    $machine_id   = $rowReq['machine_id'];

    // ---------- 3) ส่วน header ----------
	// report_date
		$tmp = pick_post_allow_empty(['report_date','reportDate']);
		if ($tmp !== null) {
			// ถ้าอยากให้ '' กลายเป็น NULL ก็ใส่ normalize_nullable($tmp); ได้
			$report_date = $tmp;
		}
		
		// report_time
		$tmp = pick_post_allow_empty(['report_time','reportTime']);
		if ($tmp !== null) {
			$report_time = $tmp;
		}
		
		// ผู้แจ้ง (Requester)
		$tmp = pick_post_allow_empty(['name']);
		if ($tmp !== null) {
			$name = $tmp;  // อนุญาตให้เป็น ''
		}
		
		// เบอร์โทร
		$tmp = pick_post_allow_empty(['phone']);
		if ($tmp !== null) {
			$phone = $tmp;
		}
		
		// อาคาร
		$tmp = pick_post_allow_empty(['building']);
		if ($tmp !== null) {
			$building = $tmp;
		}
		
		// ชั้น
		$tmp = pick_post_allow_empty(['floor']);
		if ($tmp !== null) {
			$floor = $tmp;
		}
		
		// ห้อง
		$tmp = pick_post_allow_empty(['room']);
		if ($tmp !== null) {
			$room = $tmp;
		}
		
		// อาการ
		$tmp = pick_post_allow_empty(['problem_detail','problemDetail']);
		if ($tmp !== null) {
			$problem_detail = $tmp;
		}
		
		// ความเร่งด่วน
		$tmp = pick_post_allow_empty(['urgency','urgency_input','urgencyInput']);
		if ($tmp !== null) {
			$urgency = $tmp;
		}
	// เปลี่ยน asset / feedback (ถ้าส่งมา)
	
	// machine_id (asset) - ถ้าส่ง key มา ให้เคารพค่าที่ส่งแม้จะเป็น ''
	$tmp = pick_post_allow_empty(['machine_id','ed_asset_id']);
	if ($tmp !== null) {
		// ถ้าอยากให้ '' -> NULL
		normalize_nullable($tmp); 
		$machine_id = $tmp;
	}
	
	// has_feedback
	$tmp = pick_post_allow_empty(['has_feedback']);
	if ($tmp !== null) {
		// แล้วแต่ออกแบบ:
		// - ถ้าใช้งานเป็น 'Y'/'N' ก็อาจไม่ normalize
		// - ถ้าอยากให้ '' = ไม่มีค่า -> NULL ก็ normalize ได้
		normalize_nullable($tmp); 
		$has_feedback = $tmp;
	}

    // ---------- 4) ส่วนรับงาน (Assign / Accept) ใช้ pick_post_allow_empty ----------
    $tmp = pick_post_allow_empty(['received_date','action_date','ac_date']);
	if ($tmp !== null) {
	  $received_date = $tmp;
	  normalize_nullable($received_date);  // '' -> NULL
	}
	
	$tmp = pick_post_allow_empty(['process_date','action_date','ac_date']);
	if ($tmp !== null) {
	  $process_date = $tmp;
	  normalize_nullable($process_date);
	}
	
	$tmp = pick_post_allow_empty(['process_time','action_time','ac_time']);
	if ($tmp !== null) {
	  $process_time = $tmp;
	  normalize_nullable($process_time);
	}
	
	$tmp = pick_post_allow_empty(['received_by','technician','ac_tech']);
	if ($tmp !== null) {
	  $received_by = $tmp;
	  normalize_nullable($received_by);
	}
	
	$responsible_user_ids = pick_post_array([
	  'responsible_user_ids',
	  'responsible_user_ids[]'
	]);
	
	// รองรับ user_id แบบตัวเลขและแบบข้อความ เช่น user-1
	$responsible_user_ids = array_values(array_unique(array_filter(array_map(function($v) {
	  return trim((string)$v);
	}, $responsible_user_ids))));
	
	// ถ้ามีการเลือกหลายคน ให้ใช้คนแรกเก็บใน repair_requests.received_by
	// ห้าม intval() เพราะค่าอาจเป็น user-1
	if (!empty($responsible_user_ids)) {
	  $received_by = $responsible_user_ids[0];
	  normalize_nullable($received_by);
	}

    // ---------- 5) ส่วนปิดงาน (Close) ----------
   $completedByInput = pick_post_allow_empty(['completed_by','completedBy']);
	normalize_nullable($completedByInput);
	
	$tmp = pick_post_allow_empty(['finish_date','completed_date','cl_date']);
	if ($tmp !== null) {
	  $completed_date = $tmp;
	  normalize_nullable($completed_date);
	}
	
	$tmp = pick_post_allow_empty(['finish_time','completed_time','cl_time']);
	if ($tmp !== null) {
	  $completed_time = $tmp;
	  normalize_nullable($completed_time);
	}
	
	$tmp = pick_post_allow_empty(['service_type','serviceType','cl_service_type']);
	if ($tmp !== null) {
	  $service_type = $tmp;
	  normalize_nullable($service_type);
	}
	
	$tmp = pick_post_allow_empty(['job_type','jobType','cl_job_type']);
	if ($tmp !== null) {
	  $job_type = $tmp;
	  normalize_nullable($job_type);
	}
	
	// บันทึกผลการดำเนินงาน (อนุญาตให้ค่าว่างได้)
	$note = pick_post_allow_empty(['cl_note','note','completed_solution','completedSolution']);
	if ($note !== null) {
	  // เก็บตามที่ส่งมาเลย จะเป็น string ปกติ หรือ '' ก็ได้
	  $completed_solution = $note;
	}
	
	$completed_by = $completedByInput ?: ($completed_by ?: $updatedBy);
	normalize_nullable($completed_by);

    // ---------- 6) รูปแจ้งซ่อม (repair_images) ----------
    $doUpdateHeaderImages = array_key_exists('keep_urls', $_POST) || isset($_FILES['new_images']);

    if ($doUpdateHeaderImages) {
      $base_upload_dir = __DIR__ . "/../API_es/uploads/";
      $current_year  = date('Y');
      $current_month = date('m');
      $target_dir_full_path = rtrim($base_upload_dir, "/") . "/" . $current_year . "/" . $current_month . "/";
      $db_image_path        = $current_year . "/" . $current_month;

      if (!is_dir($target_dir_full_path)) {
        if (!mkdir($target_dir_full_path, 0755, true)) {
          throw new Exception('Failed to create upload directory');
        }
      }

      $keepUrlsJson = $_POST['keep_urls'] ?? '[]';
      $keepUrls = json_decode($keepUrlsJson, true);
      if (!is_array($keepUrls)) $keepUrls = [];

      $allRelPaths = [];

      // 6.1 แปลง URL เป็น path
      foreach ($keepUrls as $url) {
        if (!is_string($url) || $url === '') continue;
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) continue;

        $marker = '/API_es/uploads/';
        $pos = strpos($path, $marker);
        if ($pos !== false) {
          $rel = substr($path, $pos + strlen($marker));
        } else {
          $rel = ltrim($path, '/');
        }
        $rel = ltrim($rel, '/');
        if ($rel === '') continue;
        $allRelPaths[] = $rel;
      }

      // 6.2 upload new_images[]
      $newFiles = $_FILES['new_images'] ?? null;
      if ($newFiles && isset($newFiles['name']) && is_array($newFiles['name'])) {
        $allowed_types = ["jpg", "png", "jpeg", "gif", "webp"];
        $file_count = count($newFiles['name']);
        if ($file_count > 5) $file_count = 5;

        for ($i = 0; $i < $file_count; $i++) {
          if (!isset($newFiles['error'][$i])) continue;
          if ($newFiles['error'][$i] === UPLOAD_ERR_NO_FILE) continue;

          if ($newFiles['error'][$i] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $newFiles['name'][$i] . ' Code: ' . $newFiles['error'][$i]);
          }

          if ((int)$newFiles['size'][$i] >= 5000000) {
            throw new Exception('Image file too large (max 5MB): ' . $newFiles['name'][$i]);
          }

          $originalName = basename($newFiles['name'][$i]);
          $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
          if (!in_array($ext, $allowed_types, true)) {
            throw new Exception('Only JPG/JPEG/PNG/GIF/WEBP allowed: ' . $originalName);
          }

          $file_name   = uniqid('img_', true) . '_' . $originalName;
          $target_file = $target_dir_full_path . $file_name;

          if (!move_uploaded_file($newFiles['tmp_name'][$i], $target_file)) {
            throw new Exception('Error uploading image: ' . $originalName . ' - Check permissions.');
          }

          $rel = $db_image_path . "/" . $file_name;
          $allRelPaths[] = $rel;
        }
      }

      // 6.3 sync repair_images
      $sqlDelImg = "DELETE FROM repair_images WHERE repair_request_id = ?";
      if ($stmtDel = $connect->prepare($sqlDelImg)) {
        $stmtDel->bind_param('i', $repairId);
        $stmtDel->execute();
        $stmtDel->close();
      }

      if (!empty($allRelPaths)) {
        $sqlInsImg = "INSERT INTO repair_images (repair_request_id, path, file_name, created_by, created_at)
                      VALUES (?, ?, ?, ?, NOW())";
        $stmtInsImg = $connect->prepare($sqlInsImg);
        if (!$stmtInsImg) {
          throw new Exception('Prepare insert repair_images failed: ' . $connect->error);
        }

        foreach ($allRelPaths as $rel) {
          $rel = ltrim($rel, '/');
          if ($rel === '') continue;
          $path = '';
          $file_name = basename($rel);
          $dir = dirname($rel);
          if ($dir !== '.' && $dir !== '') {
            $path = $dir;
          }

          $stmtInsImg->bind_param(
            'isss',
            $repairId,
            $path,
            $file_name,
            $updatedBy
          );
          if (!$stmtInsImg->execute()) {
            $err = $stmtInsImg->error;
            $stmtInsImg->close();
            throw new Exception('Insert repair_images failed: ' . $err);
          }
        }

        $stmtInsImg->close();
      }

      $image_url = json_encode($allRelPaths, JSON_UNESCAPED_UNICODE);
    }

    // ---------- 7) รูปปิดงาน (repair_completed_images) ----------
// อนุญาตลบ/คงรูปเดิมแบบเดียวกับรูปแจ้งซ่อม
$doUpdateCompletedImages = array_key_exists('completed_keep_urls', $_POST) || isset($_FILES['close_images']);

$completed_uploads = [];

if ($doUpdateCompletedImages) {
    $base_upload_dir2 = __DIR__ . "/../API_es/uploads/";
    $current_year2  = date('Y');
    $current_month2 = date('m');
    $target_dir_full_path2 = rtrim($base_upload_dir2, "/") . "/" . $current_year2 . "/" . $current_month2 . "/";
    $db_image_path2        = $current_year2 . "/" . $current_month2;

    if (!is_dir($target_dir_full_path2)) {
        if (!mkdir($target_dir_full_path2, 0755, true)) {
            throw new Exception('Failed to create upload directory for completed images');
        }
    }

    // 7.1 URL ที่ยังต้องการเก็บไว้จาก frontend (completed_keep_urls เป็น JSON array ของ URL)
    $keepCompletedJson = $_POST['completed_keep_urls'] ?? '[]';
    $keepCompleted = json_decode($keepCompletedJson, true);
    if (!is_array($keepCompleted)) $keepCompleted = [];

    $allCompletedRelPaths = [];

    foreach ($keepCompleted as $url) {
        if (!is_string($url) || $url === '') continue;
        $path = parse_url($url, PHP_URL_PATH);
        if (!$path) continue;

        $marker = '/API_es/uploads/';
        $pos = strpos($path, $marker);
        if ($pos !== false) {
            $rel = substr($path, $pos + strlen($marker));
        } else {
            $rel = ltrim($path, '/');
        }
        $rel = ltrim($rel, '/');
        if ($rel === '') continue;

        $allCompletedRelPaths[] = $rel;
    }

    // 7.2 upload ไฟล์ใหม่ close_images[]
    $completedFiles = $_FILES['close_images'] ?? null;

    if ($completedFiles && isset($completedFiles['name']) && is_array($completedFiles['name'])) {
        $allowed_types = ["jpg", "png", "jpeg", "gif", "webp"];
        $file_count = count($completedFiles['name']);
        if ($file_count > 10) $file_count = 10;

        for ($i = 0; $i < $file_count; $i++) {
            if (!isset($completedFiles['error'][$i])) continue;
            if ($completedFiles['error'][$i] === UPLOAD_ERR_NO_FILE) continue;

            if ($completedFiles['error'][$i] !== UPLOAD_ERR_OK) {
                throw new Exception('File upload error (completed images): ' . $completedFiles['name'][$i] . ' Code: ' . $completedFiles['error'][$i]);
            }

            if ((int)$completedFiles['size'][$i] >= 5000000) {
                throw new Exception('Completed image file too large (max 5MB): ' . $completedFiles['name'][$i]);
            }

            $originalName = basename($completedFiles['name'][$i]);
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed_types, true)) {
                throw new Exception('Only JPG/JPEG/PNG/GIF/WEBP allowed (completed): ' . $originalName);
            }

            $file_name   = uniqid('done_', true) . '_' . $originalName;
            $target_file = $target_dir_full_path2 . $file_name;

            if (!move_uploaded_file($completedFiles['tmp_name'][$i], $target_file)) {
                throw new Exception('Error uploading completed image: ' . $originalName . ' - Check permissions.');
            }

            $rel = $db_image_path2 . "/" . $file_name;
            $allCompletedRelPaths[] = $rel;

            $completed_uploads[] = [
                'path'      => $db_image_path2,
                'file_name' => $file_name
            ];
        }
    }

    // 7.3 sync ตาราง repair_completed_images (ลบของเก่าออกก่อน แล้วใส่ของใหม่/ที่เหลือ)
    $sqlDelDone = "DELETE FROM repair_completed_images WHERE repair_request_id = ?";
    if ($stmtDelDone = $connect->prepare($sqlDelDone)) {
        $stmtDelDone->bind_param('i', $repairId);
        $stmtDelDone->execute();
        $stmtDelDone->close();
    }

    if (!empty($allCompletedRelPaths)) {
        $sqlDoneImg = "INSERT INTO repair_completed_images
                       (repair_request_id, rp_format, path, file_name, uploaded_by, uploaded_at)
                       VALUES (?, ?, ?, ?, ?, NOW())";

        $stmtDoneImg = $connect->prepare($sqlDoneImg);
        if (!$stmtDoneImg) {
            throw new Exception('Prepare insert repair_completed_images failed: ' . $connect->error);
        }

        foreach ($allCompletedRelPaths as $rel) {
            $rel = ltrim($rel, '/');
            if ($rel === '') continue;

            $dir = dirname($rel);
            $file_name2 = basename($rel);
            $path2 = ($dir !== '.' && $dir !== '') ? $dir : '';

            $stmtDoneImg->bind_param(
                'issss',
                $repairId,
                $rp_format,
                $path2,
                $file_name2,
                $completed_by
            );

            if (!$stmtDoneImg->execute()) {
                $err = $stmtDoneImg->error;
                $stmtDoneImg->close();
                throw new Exception('Insert repair_completed_images failed: ' . $err);
            }
        }

        $stmtDoneImg->close();
    }
}

// ---------- 8) parts + tb_repair_detail + ตัดสต๊อก ----------
// เข้ามาตลอด ไม่ว่าจะส่ง parts_json มาไหม
$partsJson = pick_post_allow_empty(['parts_json','partsJson']);
$parts = [];

if ($partsJson !== null && trim($partsJson) !== '') {
    $tmp = json_decode($partsJson, true);
    if (is_array($tmp)) {
        $parts = $tmp;
    }
}

// 8.1 คืนสต๊อกของรายการเดิมก่อน (ถ้ามี)
$sqlOldDetail = "SELECT rpd_product_id, wh_id, rpd_qty
                 FROM tb_repair_detail
                 WHERE rpd_rp_id = ?";
if ($stmtOld = $connect->prepare($sqlOldDetail)) {
    $stmtOld->bind_param('i', $repairId);
    $stmtOld->execute();
    $resOld = $stmtOld->get_result();
    $oldRows = $resOld->fetch_all(MYSQLI_ASSOC);
    $stmtOld->close();

    if (!empty($oldRows)) {
        $sqlReturnStock = "UPDATE product_stocks
                           SET qty = qty + ?,
                               updated_at = NOW()
                           WHERE pd_id = ? AND wh_id = ?";
        $stmtReturn = $connect->prepare($sqlReturnStock);
        if (!$stmtReturn) {
            throw new Exception('Prepare return stock failed: ' . $connect->error);
        }

        foreach ($oldRows as $orow) {
            $old_pd  = (int)($orow['rpd_product_id'] ?? 0);
            $old_wh  = (int)($orow['wh_id'] ?? 0);
            $old_qty = (float)($orow['rpd_qty'] ?? 0);
            if ($old_pd > 0 && $old_wh > 0 && $old_qty > 0) {
                $stmtReturn->bind_param('dii', $old_qty, $old_pd, $old_wh);
                if (!$stmtReturn->execute()) {
                    $err = $stmtReturn->error;
                    $stmtReturn->close();
                    throw new Exception('Return stock failed: ' . $err);
                }
            }
        }

        $stmtReturn->close();
    }
}

// 8.2 ลบ detail เดิมของใบนี้ออกให้หมดก่อน
$sqlDelDetail = "DELETE FROM tb_repair_detail WHERE rpd_rp_id = ?";
if ($stmtDel = $connect->prepare($sqlDelDetail)) {
    $stmtDel->bind_param('i', $repairId);
    $stmtDel->execute();
    $stmtDel->close();
}

// 8.3 ถ้า parts ใหม่ไม่ว่าง ค่อย insert + ตัดสต๊อกใหม่
if (!empty($parts)) {
    $sqlInsDetail = "INSERT INTO tb_repair_detail
      (rpd_rp_id, rpd_product_id, wh_id, pd_gen_code,
       rpd_details_head, rpd_details, rpd_brand,
       rpd_qty, rpd_price, pd_unit, rpd_sum_money)
      VALUES (?,?,?,?,?,?,?,?,?,?,?)";

    $stmtDet = $connect->prepare($sqlInsDetail);
    if (!$stmtDet) {
      throw new Exception('Prepare insert tb_repair_detail failed: ' . $connect->error);
    }

    $sqlStock = "UPDATE product_stocks
                 SET qty = GREATEST(qty - ?, 0),
                     updated_at = NOW()
                 WHERE pd_id = ? AND wh_id = ?";
    $stmtStock = $connect->prepare($sqlStock);
    if (!$stmtStock) {
      $stmtDet->close();
      throw new Exception('Prepare update product_stocks failed: ' . $connect->error);
    }

    foreach ($parts as $p) {
      $rpd_product_id   = (int)($p['rpd_product_id'] ?? 0);
      $wh_id            = (int)($p['wh_id'] ?? 0);

      $pd_gen_code      = (string)($p['pd_gen_code'] ?? '');
      $rpd_details_head = (string)($p['rpd_details_head'] ?? '');
      $rpd_details      = (string)($p['rpd_details'] ?? '');
      $rpd_brand        = (string)($p['rpd_brand'] ?? '');
      $rpd_qty          = (float)($p['rpd_qty'] ?? 0);
      $rpd_price        = (float)($p['rpd_price'] ?? 0);
      $pd_unit          = (string)($p['pd_unit'] ?? '');
      $rpd_sum_money    = (float)($p['rpd_sum_money'] ?? ($rpd_qty * $rpd_price));

      if ($rpd_qty <= 0) continue;

      $stmtDet->bind_param(
        'iiissssddsd',
        $repairId,
        $rpd_product_id,
        $wh_id,
        $pd_gen_code,
        $rpd_details_head,
        $rpd_details,
        $rpd_brand,
        $rpd_qty,
        $rpd_price,
        $pd_unit,
        $rpd_sum_money
      );

      if (!$stmtDet->execute()) {
        $err = $stmtDet->error;
        $stmtDet->close();
        $stmtStock->close();
        throw new Exception('Insert tb_repair_detail failed: ' . $err);
      }

      if ($wh_id > 0 && $rpd_product_id > 0) {
        $stmtStock->bind_param('dii', $rpd_qty, $rpd_product_id, $wh_id);
        if (!$stmtStock->execute()) {
          $err = $stmtStock->error;
          $stmtDet->close();
          $stmtStock->close();
          throw new Exception('Update product_stocks failed: ' . $err);
        }
      }
    }

    $stmtDet->close();
    $stmtStock->close();
}

 
	normalize_nullable($received_date);
	normalize_nullable($process_date);
	normalize_nullable($process_time);
	normalize_nullable($received_by);
	
	normalize_nullable($completed_date);
	normalize_nullable($completed_time);
	normalize_nullable($service_type);
	normalize_nullable($job_type);
	normalize_nullable($completed_by);

// ---------- 9) คำนวณสถานะ ----------

// helper สั้น ๆ
$val = function($x) {
    return trim((string)$x);
};

// รับงาน: ต้องกรอกครบทั้ง 4 ช่อง ถึงจะถือว่า "รับงานแล้ว"
$hasReceive = (
    $val($received_date) !== '' &&
    $val($received_by)   !== '' &&
    $val($process_date)  !== '' &&
    $val($process_time)  !== ''
);

// ปิดงาน: ต้องกรอกครบทุกช่อง ถึงจะถือว่า "ปิดงานแล้ว"
// ถ้าอยากให้ note (completed_solution) ว่างได้ ให้เอา && $val($completed_solution) !== '' ออก
$hasCompleted = (
    $hasReceive && // ปิดงานได้ต้องเคยรับงานก่อน
    $val($completed_date)     !== '' &&
    $val($completed_time)     !== '' &&
    $val($completed_by)       !== '' &&
    $val($completed_solution) !== '' && // ถ้าไม่อยากบังคับ note เอาออก
    $val($service_type)       !== '' &&
    $val($job_type)           !== ''
);

// ตัดสินสถานะจากค่าปัจจุบัน "ล้วน ๆ" ไม่สน status เก่า
if ($hasCompleted) {
    // ทุกช่องรับงาน + ปิดงานครบ
    $status = 'completed';
} elseif ($hasReceive) {
    // มีข้อมูลรับงาน (4 ช่องครบ) แต่ปิดงานยังไม่ครบ
    $status = 'inprogress';
} else {
    // ช่องรับงานไม่ครบสักชุด → ถือว่ายังไม่รับงาน
    $status = 'pending';
}

    // ---------- 10) update repair_requests ----------
    $sqlUpdate = "UPDATE repair_requests
                  SET report_date       = ?,
                      report_time       = ?,
                      name              = ?,
                      phone             = ?,
                      building          = ?,
                      floor             = ?,
                      room              = ?,
                      problem_detail    = ?,
                      urgency           = ?,
                      image_url         = ?,
                      status            = ?,
                      received_date     = ?,
                      received_by       = ?,
                      process_date      = ?,
                      process_time      = ?,
                      completed_by      = ?,
                      completed_date    = ?,
                      completed_time    = ?,
                      completed_solution= ?,
                      service_type      = ?,
                      job_type          = ?,
                      ag_id             = ?,
                      rp_format         = ?,
                      has_feedback      = ?,
                      machine_id        = ?,
                      updated_at        = NOW(),
                      updated_by        = ?
                  WHERE id = ?";

    $stmtUpd = $connect->prepare($sqlUpdate);
    if (!$stmtUpd) {
      throw new Exception('Prepare update repair_requests failed: ' . $connect->error);
    }

    // 26 string-ish + 1 int
    $types = str_repeat('s', 26) . 'i';
    $stmtUpd->bind_param(
      $types,
      $report_date,
      $report_time,
      $name,
      $phone,
      $building,
      $floor,
      $room,
      $problem_detail,
      $urgency,
      $image_url,
      $status,
      $received_date,
      $received_by,
      $process_date,
      $process_time,
      $completed_by,
      $completed_date,
      $completed_time,
      $completed_solution,
      $service_type,
      $job_type,
      $ag_id,
      $rp_format,
      $has_feedback,
      $machine_id,
      $updatedBy,
      $repairId
    );

    if (!$stmtUpd->execute()) {
      $err = $stmtUpd->error;
      $stmtUpd->close();
      throw new Exception('Execute update repair_requests failed: ' . $err);
    }
    $stmtUpd->close();
	
// ---------- บันทึกผู้รับผิดชอบหลายคน ----------
$responsible_saved = save_repair_responsibles(
  $connect,
  $repairId,
  $responsible_user_ids,
  $updatedBy
);
    // ---------- 11) commit ----------
    $connect->commit();

    echo json_encode([
      'success'         => true,
      'message'         => 'บันทึกเรียบร้อย',
      'repair_id'       => $repairId,
      'rp_format'       => $rp_format,
      'status'          => $status,
      'responsible_user_ids' => $responsible_user_ids,
      'responsible_saved' => $responsible_saved,
      'completed_imgs'  => $completed_uploads
    ], JSON_UNESCAPED_UNICODE);
    exit;

  } catch (Exception $e) {
    $connect->rollback();
    http_response_code(500);
    echo json_encode([
      'success' => false,
      'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

// ------------------------------
// default
// ------------------------------
echo json_encode(['success' => false, 'error' => 'Unknown action'], JSON_UNESCAPED_UNICODE);
