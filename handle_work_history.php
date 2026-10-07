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
  echo json_encode(array('success' => true), JSON_UNESCAPED_UNICODE);
  exit;
}

@session_start();
date_default_timezone_set('Asia/Bangkok');

include 'config_ctrl/connect.php';
if (mysqli_connect_errno()) {
  http_response_code(500);
  echo json_encode(array('success' => false, 'error' => 'DB connect failed: ' . mysqli_connect_error()), JSON_UNESCAPED_UNICODE);
  exit;
}
mysqli_set_charset($connect, "utf8mb4");

// ============================================================
//  Helper: เขียนแทน operator ?? (PHP 7.0+) ให้ใช้ได้กับ PHP 5.4
//  gv($array, $key, $default) จะ isset() ให้ก่อนเสมอ จึงไม่มี Notice
// ============================================================
function gv($arr, $key, $default = null) {
  if (is_array($arr) && isset($arr[$key])) {
    return $arr[$key];
  }
  return $default;
}

$action = gv($_GET, 'action', '');
if (!$action) {
  http_response_code(400);
  echo json_encode(array('success' => false, 'error' => 'Missing action'), JSON_UNESCAPED_UNICODE);
  exit;
}

function build_base_url() {
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  $scheme = $https ? 'https://' : 'http://';
  $host = gv($_SERVER, 'HTTP_HOST', '');
  return $scheme . $host;
}

function is_absolute_url($url) {
  return (bool)preg_match('/^https?:\/\//i', (string)$url);
}

/**
 * แทนที่ spread operator ...$params (PHP 5.6+) ด้วยรูปแบบ call_user_func_array
 * ที่ใช้ได้ตั้งแต่ PHP 5.3 ขึ้นไป (ต้องส่งค่าพารามิเตอร์เป็น "reference")
 */
function bind_params_array($stmt, $types, array $params) {
  if ($types === '') {
    return true;
  }
  $args = array();
  $args[] = $types;
  for ($i = 0; $i < count($params); $i++) {
    $args[] = &$params[$i];
  }
  return call_user_func_array(array($stmt, 'bind_param'), $args);
}

/**
 * แทนที่ array_column() (PHP 5.5+) ด้วย loop ธรรมดา ใช้ได้ตั้งแต่ PHP 4
 */
function my_array_column($array, $columnKey) {
  $result = array();
  foreach ($array as $row) {
    if (is_array($row) && isset($row[$columnKey])) {
      $result[] = $row[$columnKey];
    }
  }
  return $result;
}

/**
 * ฟังก์ชันนี้แปลงค่าจาก DB ให้ตรงกับ key ที่ front-end ต้องการ
 */
function map_status_to_frontend($dbStatus) {
  $s = strtolower(trim((string)$dbStatus));
  $map = array(
    'pending'     => 'pending',
    'inprogress'  => 'in_progress', // รองรับค่าจาก DB ที่เป็น inprogress
    'in_progress' => 'in_progress', // รองรับกรณีเผื่อบันทึกมาแบบมีขีดล่าง
    'feedback'    => 'feedback',
    'completed'   => 'completed',
    'cancel'      => 'cencel', 
    'canceled'    => 'cencel',  // เพิ่มเผื่อกรณี DB ใช้ canceled
  );
  return gv($map, $s, 'pending');
}

/* ============================================================
 *  ส่วนสำคัญ: "ตารางอ้างอิงรวม" (Reference Lookup)
 *  ไม่ใช้ LEFT JOIN ระหว่างตารางหลักกับตารางย่อย
 *  แต่รวมตารางย่อยทั้งหมด (tb_area, tb_area_class, tb_area_room,
 *  tb_ass_list, tb_repair_group, tb_repair_system) เป็น "ตารางเดียว"
 *  ด้วย UNION ALL ก่อน แล้วฝั่ง PHP จะ "map ค่าใน array" เอาเอง
 *  แทนการ join ใน SQL
 * ============================================================ */
function fetch_ref_lookup($connect) {
  // ยืนยันแล้ว: tb_repair_group ใช้ rpg_id/rpg_name, tb_repair_system ใช้ rps_id/rps_name/rps_code
  $sql = "
    SELECT 'building' AS ref_type,
           area_id AS ref_id,
           CONVERT(area_name USING utf8mb4) COLLATE utf8mb4_general_ci AS ref_name,
           NULL AS ref_code
    FROM tb_area
    WHERE area_status = 0

    UNION ALL

    SELECT 'floor',
           ac_id,
           CONVERT(ac_name USING utf8mb4) COLLATE utf8mb4_general_ci,
           NULL
    FROM tb_area_class
    WHERE ac_status = 0

    UNION ALL

    SELECT 'room',
           ar_id,
           CONVERT(ar_name USING utf8mb4) COLLATE utf8mb4_general_ci,
           NULL
    FROM tb_area_room
    WHERE ar_status = 0

    UNION ALL

    SELECT 'asset',
           ass_id,
           CONVERT(asset_name USING utf8mb4) COLLATE utf8mb4_general_ci,
           CONVERT(ass_code USING utf8mb4) COLLATE utf8mb4_general_ci
    FROM tb_ass_list

    UNION ALL

    SELECT 'group',
           rpg_id,
           CONVERT(rpg_name USING utf8mb4) COLLATE utf8mb4_general_ci,
           NULL
    FROM tb_repair_group

    UNION ALL

    SELECT 'system',
           rps_id,
           CONVERT(rps_name USING utf8mb4) COLLATE utf8mb4_general_ci,
           CONVERT(rps_code USING utf8mb4) COLLATE utf8mb4_general_ci
    FROM tb_repair_system
  ";

  $result = $connect->query($sql);
  if (!$result) {
    throw new Exception('Query vw_ref_lookup failed: ' . $connect->error);
  }

  $lookup = array(
    'building' => array(),
    'floor'    => array(),
    'room'     => array(),
    'asset'    => array(),
    'group'    => array(),
    'system'   => array(),
  );

  while ($row = $result->fetch_assoc()) {
    $type = $row['ref_type'];
    $lookup[$type][(string)$row['ref_id']] = array(
      'name' => $row['ref_name'],
      'code' => $row['ref_code'],
    );
  }

  return $lookup;
}

/**
 * ดึงรายชื่อช่างที่รับผิดชอบงานซ่อม แบบ "หลายคน 1 งาน"
 * จาก repair_request_responsible โดยไม่ join กับ repair_requests
 * (query แยกทีเดียวทั้งชุด แล้วจับกลุ่มด้วย repair_request_id ใน PHP)
 */
function fetch_responsibles_map($connect, array $repairIds) {
  $map = array();
  if (empty($repairIds)) return $map;

  $placeholders = implode(',', array_fill(0, count($repairIds), '?'));
  $types = str_repeat('s', count($repairIds));

  $sql = "
    SELECT repair_request_id, user_id, user_name, user_fname, user_department, user_tel
    FROM repair_request_responsible
    WHERE repair_request_id IN ($placeholders)
    ORDER BY id ASC
  ";
  $stmt = $connect->prepare($sql);
  if (!$stmt) throw new Exception('Prepare responsible failed: ' . $connect->error);
  bind_params_array($stmt, $types, $repairIds);
  $stmt->execute();
  $res = $stmt->get_result();

  while ($row = $res->fetch_assoc()) {
    $rid = (string)$row['repair_request_id'];
    if (!isset($map[$rid])) $map[$rid] = array();
    $map[$rid][] = array(
      'user_id'         => (string)$row['user_id'],
      'user_name'       => $row['user_name'],
      'user_fname'      => $row['user_fname'],
      'user_department' => $row['user_department'],
      'user_tel'        => $row['user_tel'],
    );
  }
  $stmt->close();
  return $map;
}

/**
 * ดึงรูปภาพ (แจ้งซ่อม / ปิดงาน) แบบชุดเดียวสำหรับหลาย repair_request_id
 */
function fetch_images_map($connect, array $repairIds, $table, $dateCol) {
  $map = array();
  if (empty($repairIds)) return $map;

  $placeholders = implode(',', array_fill(0, count($repairIds), '?'));
  $types = str_repeat('i', count($repairIds));
  $uploadBaseUrl = build_base_url() . '/API_es/uploads/';

  $sql = "
    SELECT repair_request_id, path, file_name
    FROM $table
    WHERE repair_request_id IN ($placeholders)
    ORDER BY $dateCol ASC
  ";
  $stmt = $connect->prepare($sql);
  if (!$stmt) throw new Exception("Prepare $table failed: " . $connect->error);
  bind_params_array($stmt, $types, $repairIds);
  $stmt->execute();
  $res = $stmt->get_result();

  while ($row = $res->fetch_assoc()) {
    $rid = (string)$row['repair_request_id'];
    $p  = !empty($row['path']) ? trim(str_replace('\\', '/', (string)$row['path'])) : '';
    $fn = !empty($row['file_name']) ? trim((string)$row['file_name']) : '';
    if ($p === '' && $fn === '') continue;

    $rel = $fn !== '' ? (trim($p, '/') !== '' ? trim($p, '/') . '/' . $fn : $fn) : ltrim($p, '/');
    if ($rel === '') continue;

    $url = is_absolute_url($rel) ? $rel : (rtrim($uploadBaseUrl, '/') . '/' . ltrim($rel, '/'));
    if (!isset($map[$rid])) $map[$rid] = array();
    $map[$rid][] = $url;
  }
  $stmt->close();
  return $map;
}

/**
 * ดึงอะไหล่/สต็อกที่เบิกใช้ สำหรับหลาย repair_request_id
 */
function fetch_stock_map($connect, array $repairIds) {
  $map = array();
  if (empty($repairIds)) return $map;

  $placeholders = implode(',', array_fill(0, count($repairIds), '?'));
  $types = str_repeat('i', count($repairIds));

  $sql = "
    SELECT rpd_rp_id, pd_gen_code, rpd_details, rpd_qty, pd_unit
    FROM tb_repair_detail
    WHERE rpd_rp_id IN ($placeholders)
    ORDER BY rpd_id ASC
  ";
  $stmt = $connect->prepare($sql);
  if (!$stmt) throw new Exception('Prepare tb_repair_detail failed: ' . $connect->error);
  bind_params_array($stmt, $types, $repairIds);
  $stmt->execute();
  $res = $stmt->get_result();

  while ($row = $res->fetch_assoc()) {
    $rid = (string)$row['rpd_rp_id'];
    if (!isset($map[$rid])) $map[$rid] = array();
    $map[$rid][] = array(
      'itemCode' => $row['pd_gen_code'],
      'itemName' => $row['rpd_details'],
      'qty'      => (float)$row['rpd_qty'],
      'unit'     => $row['pd_unit'],
    );
  }
  $stmt->close();
  return $map;
}

/* ============================================================
 * GET TECHNICIANS (สำหรับ dropdown เลือกช่าง)
 * URL: handle_work_history.php?action=get_technicians&ag_id=...
 * ============================================================ */
if ($action === 'get_technicians') {
  $ag_id = gv($_GET, 'ag_id', null);
  if (!$ag_id) {
    echo json_encode(array('success' => false, 'error' => 'Missing ag_id'), JSON_UNESCAPED_UNICODE);
    exit;
  }

  $sql = "
    SELECT r.user_id, r.user_name, r.user_fname, r.user_department
    FROM repair_request_responsible r
    INNER JOIN (
      SELECT user_id, MAX(id) AS max_id
      FROM repair_request_responsible
      GROUP BY user_id
    ) latest ON latest.user_id = r.user_id AND latest.max_id = r.id
    INNER JOIN tb_user u ON r.user_id = u.user_id
    WHERE u.user_agency = ?
    ORDER BY r.user_fname ASC
  ";
  $stmt = $connect->prepare($sql);
  if (!$stmt) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => $connect->error), JSON_UNESCAPED_UNICODE);
    exit;
  }
  $stmt->bind_param("s", $ag_id);
  $stmt->execute();
  $result = $stmt->get_result();

  $technicians = array(
    array('id' => 'all', 'name' => 'ช่างทั้งหมด', 'role' => 'รวมทุกทีม'),
  );
  while ($row = $result->fetch_assoc()) {
    $technicians[] = array(
      'id'   => (string)$row['user_id'],
      'name' => trim($row['user_name'] . ' ' . $row['user_fname']),
      'role' => '', 
    );
  }
  $stmt->close();

  echo json_encode(array('success' => true, 'rows' => $technicians), JSON_UNESCAPED_UNICODE);
  exit;
}

/* ============================================================
 *  GET JOBS (list หลัก) — มี search / filter / sort / pagination
 *  ไม่ LEFT JOIN กับตารางย่อย ใช้ vw_ref_lookup (UNION ALL) + map ใน PHP แทน
 *  URL: handle_work_history.php?action=get_jobs&ag_id=...&startRow=&endRow=&search=&sortModel=&filterModel=&technicianId=
 * ============================================================ */
if ($action === 'get_jobs') {

  $ag_id  = gv($_GET, 'ag_id', null);
  $search = trim(gv($_GET, 'search', ''));
  $technicianId = trim(gv($_GET, 'technicianId', 'all'));

  // startRow/endRow ไม่ได้ถูกส่งมาจาก front-end แล้ว (ใช้ startDate/endDate กรองแทน)
  // ถ้ามีการส่งมา (เผื่อ caller เก่า) ก็ยังใช้ได้ตามปกติ ไม่งั้นใช้ค่า default ที่ปลอดภัย
  $startRow = intval(gv($_GET, 'startRow', 0));
  $endRow   = intval(gv($_GET, 'endRow', 2000)); // เพดานความปลอดภัยเมื่อไม่ได้ระบุ endRow
  $limit    = max(1, $endRow - $startRow);

  $startDate = trim(gv($_GET, 'startDate', ''));
  $endDate   = trim(gv($_GET, 'endDate', ''));

  $sortModel   = json_decode(gv($_GET, 'sortModel', '[]'), true);
  $filterModel = json_decode(gv($_GET, 'filterModel', '{}'), true);
  if (!is_array($sortModel))   $sortModel = array();
  if (!is_array($filterModel)) $filterModel = array();

  // ------- WHERE (เฉพาะ repair_requests เอง ไม่แตะตารางย่อย) -------
  $whereSql = " WHERE r.ag_id = ? ";
  $params   = array($ag_id);
  $types    = "s";
  
  if ($startDate !== '' && $endDate !== '') {
    $whereSql .= " AND COALESCE(r.process_date, r.report_date) <= ? 
                   AND (r.completed_date IS NULL OR r.completed_date >= ?) ";
    $params[] = $endDate;
    $params[] = $startDate;
    $types   .= "ss";
  } elseif ($startDate !== '') {
    $whereSql .= " AND (r.completed_date IS NULL OR r.completed_date >= ?) ";
    $params[] = $startDate;
    $types   .= "s";
  } elseif ($endDate !== '') {
    $whereSql .= " AND COALESCE(r.process_date, r.report_date) <= ? ";
    $params[] = $endDate;
    $types   .= "s";
  }

  if ($search !== '') {
    $whereSql .= " AND (
      r.rp_format LIKE ?
      OR r.name LIKE ?
      OR r.problem_detail LIKE ?
      OR r.phone LIKE ?
      OR r.completed_solution LIKE ?
    ) ";
    $s = "%{$search}%";
    $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
    $types .= "sssss";
  }

  // ------- filter: status -------
  $statusValues = gv(gv($filterModel, 'status', array()), 'values', array());
  if (!empty($statusValues)) {
    $placeholders = implode(',', array_fill(0, count($statusValues), '?'));
    $whereSql .= " AND r.status IN ($placeholders) ";
    foreach ($statusValues as $v) { $params[] = $v; $types .= "s"; }
  }

  // ------- filter: urgency (priority) -------
  $urgencyValues = gv(gv($filterModel, 'urgency', array()), 'values', array());
  if (!empty($urgencyValues)) {
    $placeholders = implode(',', array_fill(0, count($urgencyValues), '?'));
    $whereSql .= " AND r.urgency IN ($placeholders) ";
    foreach ($urgencyValues as $v) { $params[] = $v; $types .= "s"; }
  }

  // ------- filter: วันที่แจ้งซ่อม (report_date) -------
  if (isset($filterModel['report_date'])) {
    $info = $filterModel['report_date'];
    $dateFrom = substr(gv($info, 'dateFrom', ''), 0, 10);
    $dateTo   = substr(gv($info, 'dateTo', ''), 0, 10);
    if ($dateFrom && $dateTo) {
      $whereSql .= " AND r.report_date BETWEEN ? AND ? ";
      $params[] = $dateFrom; $params[] = $dateTo; $types .= "ss";
    } elseif ($dateFrom) {
      $whereSql .= " AND r.report_date >= ? "; $params[] = $dateFrom; $types .= "s";
    } elseif ($dateTo) {
      $whereSql .= " AND r.report_date <= ? "; $params[] = $dateTo; $types .= "s";
    }
  }

  // ------- filter: ช่าง (technicianId) -------
  // ช่างอยู่คนละตาราง (repair_request_responsible) จึงกรองด้วย sub-select
  // (ไม่ใช่ LEFT JOIN แต่เป็น WHERE ... IN (SELECT ...))
  if ($technicianId && $technicianId !== 'all') {
    $whereSql .= " AND r.id IN (
      SELECT repair_request_id FROM repair_request_responsible WHERE user_id = ?
    ) ";
    $params[] = $technicianId;
    $types   .= "s";
  }

  // ------- ORDER BY -------
  $orderSql = " ORDER BY r.report_date DESC, r.id DESC ";
  if (!empty($sortModel)) {
    $allowedSort = array(
      'reportDate' => 'r.report_date',
      'status'     => 'r.status',
      'urgency'    => 'r.urgency',
      'id'         => 'r.id',
    );
    $first = gv($sortModel, 0, array());
    $colId = gv($first, 'colId', '');
    $dir   = strtoupper(gv($first, 'sort', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
    if (isset($allowedSort[$colId])) {
      $orderSql = " ORDER BY " . $allowedSort[$colId] . " $dir ";
    }
  }

  try {
    // ------- นับจำนวนทั้งหมด (สำหรับ pagination) -------
    $sqlCount = "SELECT COUNT(*) AS total FROM repair_requests r $whereSql";
    $stmtCount = $connect->prepare($sqlCount);
    if (!$stmtCount) throw new Exception('Prepare count failed: ' . $connect->error);
    bind_params_array($stmtCount, $types, $params);
    $stmtCount->execute();
    $countRow = $stmtCount->get_result()->fetch_assoc();
    $total = (int)gv($countRow, 'total', 0);
    $stmtCount->close();

    // ------- ดึงข้อมูลหลักจาก repair_requests อย่างเดียว (ไม่ join) -------
    $sql = "
      SELECT
        r.id, r.rp_format, r.report_date, r.report_time,
        r.name, r.phone, r.building, r.floor, r.room,
        r.problem_detail, r.urgency, r.status, r.machine_id,
        r.received_date, r.process_date, r.process_time,
        r.completed_date, r.completed_time, r.completed_solution,
        r.service_type, r.job_type
      FROM repair_requests r
      $whereSql
      $orderSql
      LIMIT ? OFFSET ?
    ";
    $stmt = $connect->prepare($sql);
    if (!$stmt) throw new Exception('Prepare get_jobs failed: ' . $connect->error);

    $execTypes  = $types . "ii";
    $execParams = $params;
    $execParams[] = $limit;
    $execParams[] = $startRow;
    bind_params_array($stmt, $execTypes, $execParams);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $repairIds = array();
    foreach ($rows as $r) {
      $repairIds[] = (string)$r['id'];
    }

    // ------- ดึงข้อมูลเสริมทั้งหมดแบบแยก query (ไม่ join) แล้ว map ใน PHP -------
    $lookup        = fetch_ref_lookup($connect);
    $responsibles  = fetch_responsibles_map($connect, $repairIds);
    $beforeImages  = fetch_images_map($connect, $repairIds, 'repair_images', 'created_at');
    $afterImages   = fetch_images_map($connect, $repairIds, 'repair_completed_images', 'uploaded_at');
    $stockUsed     = fetch_stock_map($connect, $repairIds);

    $jobs = array();
    foreach ($rows as $r) {
      $rid = (string)$r['id'];

      $buildingKey = (string)$r['building'];
      $floorKey    = (string)$r['floor'];
      $roomKey     = (string)$r['room'];
      $assetKey    = (string)$r['machine_id'];
      $groupKey    = (string)$r['service_type'];
      $systemKey   = (string)$r['job_type'];

      $buildingName = gv(gv($lookup['building'], $buildingKey, array()), 'name', '');
      $floorName    = gv(gv($lookup['floor'], $floorKey, array()), 'name', '');
      $roomName     = gv(gv($lookup['room'], $roomKey, array()), 'name', '');
      $assetName    = gv(gv($lookup['asset'], $assetKey, array()), 'name', '');
      $groupName    = gv(gv($lookup['group'], $groupKey, array()), 'name', $r['service_type']);
      $systemName   = gv(gv($lookup['system'], $systemKey, array()), 'name', $r['job_type']);

      $locationParts = array_filter(array($buildingName, $floorName, $roomName));
      $techList = gv($responsibles, $rid, array());
      $primaryTech = gv($techList, 0, null);

      // 1. จัดการข้อมูลเวลาเริ่มต้นและสิ้นสุด
      $workStart = $r['process_date']
                      ? trim($r['process_date'] . 'T' . $r['process_time'])
                      : trim($r['report_date'] . 'T' . $r['report_time']);
                      
      // สำคัญ: ต้องมีทั้ง completed_date และ completed_time ครบ ไม่งั้นจะได้สตริงแบบ
      // "2026-07-16T" (ไม่มีเวลา) ซึ่ง JS new Date(...) จะ parse เป็น Invalid Date
      // แล้วทำให้ front-end กรองงานนี้ทิ้งไปเงียบๆ ตอนเทียบช่วงวันที่ (workEnd >= vStart)
      $workEnd   = ($r['completed_date'] && $r['completed_time'])
                      ? trim($r['completed_date'] . 'T' . $r['completed_time'])
                      : null;

      // 2. คำนวณระยะเวลาทำงาน (Duration)
      $workDuration = '-';
      if ($workStart && $workEnd) {
          $start_ts = strtotime($workStart);
          $end_ts = strtotime($workEnd);
          
          if ($start_ts && $end_ts && $end_ts >= $start_ts) {
              $diff_seconds = $end_ts - $start_ts;
              $hours = floor($diff_seconds / 3600);
              $minutes = floor(($diff_seconds % 3600) / 60);
              
              if ($hours > 0 && $minutes > 0) {
                  $workDuration = "{$hours} ชั่วโมง {$minutes} นาที";
              } elseif ($hours > 0) {
                  $workDuration = "{$hours} ชั่วโมง";
              } elseif ($minutes > 0) {
                  $workDuration = "{$minutes} นาที";
              } else {
                  $workDuration = "น้อยกว่า 1 นาที";
              }
          }
      }

      $jobs[] = array(
        'id'             => $r['rp_format'] ? $r['rp_format'] : $rid,
        'technicianId'   => $primaryTech ? gv($primaryTech, 'user_id', null) : null,
        'technicians'    => $techList, // งานเดียวมีได้หลายช่าง
        'title'          => mb_substr((string)$r['problem_detail'], 0, 60),
        'type'           => $systemName,   // จาก tb_repair_system (ประเภทงาน)
        'serviceGroup'   => $groupName,    // จาก tb_repair_group (ชนิดการบริการ)
        'asset'          => $assetName,    // จาก tb_ass_list (เครื่องจักร)
        'priority'       => $r['urgency'],
        'status'         => map_status_to_frontend($r['status']),
        'reportDate'     => trim($r['report_date'] . 'T' . $r['report_time']),
        'workStart'      => $workStart,
        'workEnd'        => $workEnd,
        'workDuration'   => $workDuration, // เพิ่มฟิลด์นี้เข้าไปใหม่
        'reporter'       => $r['name'],
        'phone'          => $r['phone'],
        'location'       => implode(' / ', $locationParts),
        'issueDetail'    => $r['problem_detail'],
        'beforeImages'   => gv($beforeImages, $rid, array()),
        'solution'       => $r['completed_solution'],
        'afterImages'    => gv($afterImages, $rid, array()),
        'stockUsed'      => gv($stockUsed, $rid, array()),
        'rating'         => null, // TODO: ถ้ามีตาราง feedback/rating ให้เพิ่ม fetch_ + map ตรงนี้
      );
    }

    echo json_encode(array(
      'success' => true,
      'rows'    => $jobs,
      'total'   => $total,
    ), JSON_UNESCAPED_UNICODE);
    exit;

  } catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => $e->getMessage()), JSON_UNESCAPED_UNICODE);
    exit;
  }
}

/* ============================================================
 *  GET FILTER VALUES (สำหรับ dropdown filter บน UI)
 *  URL: handle_work_history.php?action=get_filter_values&column=status&ag_id=...
 * ============================================================ */
if ($action === 'get_filter_values') {
  $column = gv($_GET, 'column', '');
  $ag_id  = gv($_GET, 'ag_id', null);

  if (!$column || !$ag_id) {
    echo json_encode(array('success' => true, 'rows' => array()), JSON_UNESCAPED_UNICODE);
    exit;
  }

  // คอลัมน์ตรงจาก repair_requests เอง ไม่ต้องพึ่งตารางย่อย
  $map = array(
    'status'  => 'status',
    'urgency' => 'urgency',
  );

  if (isset($map[$column])) {
    $dbCol = $map[$column];
    $sql = "SELECT DISTINCT $dbCol AS value FROM repair_requests WHERE ag_id = ? AND $dbCol IS NOT NULL AND $dbCol != '' ORDER BY $dbCol ASC";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $ag_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $values = array();
    while ($row = $res->fetch_assoc()) $values[] = $row['value'];
    $stmt->close();
    echo json_encode(array('success' => true, 'rows' => $values), JSON_UNESCAPED_UNICODE);
    exit;
  }

  // คอลัมน์ที่มาจาก vw_ref_lookup (group / system / building ฯลฯ) -> ดึงจาก lookup แล้ว unique เอา
  $refTypeMap = array(
    'building_name' => 'building',
    'floor_name'    => 'floor',
    'room_name'     => 'room',
    'group_name'    => 'group',
    'system_name'   => 'system',
  );

  if (isset($refTypeMap[$column])) {
    $lookup = fetch_ref_lookup($connect);
    $type = $refTypeMap[$column];
    $names = array_values(array_unique(my_array_column($lookup[$type], 'name')));
    sort($names);
    echo json_encode(array('success' => true, 'rows' => $names), JSON_UNESCAPED_UNICODE);
    exit;
  }

  echo json_encode(array('success' => true, 'rows' => array()), JSON_UNESCAPED_UNICODE);
  exit;
}

// ------------------------------
// default
// ------------------------------
echo json_encode(array('success' => false, 'error' => 'Unknown action'), JSON_UNESCAPED_UNICODE);