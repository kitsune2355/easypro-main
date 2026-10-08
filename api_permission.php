<?php
@session_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

include("config_ctrl/connect.php");
if (!isset($connect) || !$connect) {
  echo json_encode(['success'=>false,'message'=>'db connect failed'], JSON_UNESCAPED_UNICODE);
  exit;
}
mysqli_set_charset($connect, "utf8");

function jexit($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }
function req($k,$d=''){ return isset($_REQUEST[$k]) ? trim($_REQUEST[$k]) : $d; }

$action = req('action','list_agency');

/** เมนูทั้งหมด (ให้เห็นทั้งเมนูไปเลย) */
function all_menus(){
  return [
    ['key'=>'repair_request', 'label'=>'แจ้งซ่อม'],
    ['key'=>'repair_data',    'label'=>'ข้อมูลแจ้งซ่อม'],
    ['key'=>'meter',          'label'=>'ข้อมูลมิเตอร์'],
    ['key'=>'machine',        'label'=>'ข้อมูลอุปกรณ์เครื่องจักร'],
    ['key'=>'stock',          'label'=>'จัดการสต็อก'],
  ];
}

/** role ที่อนุญาตให้ตั้งค่า */
function allowed_roles(){
  return ['super_admin','employer'];
}

/** -----------------------
 * LIST AGENCY
 * ----------------------*/
if ($action === 'list_agency') {
  $sql = "SELECT ag_id, ag_job, ag_contract, ag_status
          FROM tb_agency
          ORDER BY ag_id DESC";
  $res = mysqli_query($connect, $sql);
  if(!$res) jexit(['success'=>false,'message'=>'query failed']);

  $rows = [];
  while($r = mysqli_fetch_assoc($res)){
    $rows[] = [
      'ag_id' => (int)$r['ag_id'],
      'ag_job' => $r['ag_job'],
      'ag_contract' => $r['ag_contract'],
      'ag_status' => (int)$r['ag_status'],
    ];
  }
  jexit(['success'=>true,'data'=>$rows]);
}

/** -----------------------
 * GET PERMISSIONS
 * ----------------------*/
if ($action === 'get') {
  $ag_id = (int)req('ag_id','0');
  $role  = req('role','');

  if ($ag_id <= 0) jexit(['success'=>false,'message'=>'invalid ag_id']);
  if (!in_array($role, allowed_roles(), true)) jexit(['success'=>false,'message'=>'invalid role']);

  $menus = all_menus();
  $map = [];
  foreach($menus as $m){ $map[$m['key']] = 0; } // default ปิดหมด

  $stmt = mysqli_prepare($connect,
    "SELECT perm_key, can_view
     FROM tb_agency_role_permission
     WHERE ag_id = ? AND role = ?"
  );
  if(!$stmt) jexit(['success'=>false,'message'=>'prepare failed']);
  mysqli_stmt_bind_param($stmt, "is", $ag_id, $role);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  while($r = mysqli_fetch_assoc($res)){
    $k = $r['perm_key'];
    if (array_key_exists($k, $map)) $map[$k] = (int)$r['can_view'];
  }
  mysqli_stmt_close($stmt);

  $out = [];
  foreach($menus as $m){
    $out[] = [
      'perm_key' => $m['key'],
      'label' => $m['label'],
      'can_view' => (int)$map[$m['key']]
    ];
  }

  jexit(['success'=>true,'data'=>$out]);
}

/** -----------------------
 * SAVE PERMISSIONS
 * body: ag_id, role, items(JSON)
 * items: [{perm_key:"repair_request", can_view:1}, ...]
 * ----------------------*/
if ($action === 'save') {
  $ag_id = (int)req('ag_id','0');
  $role  = req('role','');
  $itemsRaw = req('items','');

  if ($ag_id <= 0) jexit(['success'=>false,'message'=>'invalid ag_id']);
  if (!in_array($role, allowed_roles(), true)) jexit(['success'=>false,'message'=>'invalid role']);

  $items = json_decode($itemsRaw, true);
  if (!is_array($items)) jexit(['success'=>false,'message'=>'items must be json array']);

  // whitelist keys
  $allowedKeys = [];
  foreach(all_menus() as $m) $allowedKeys[$m['key']] = true;

  // normalize: build map perm_key => can_view
  $toSave = [];
  foreach($items as $it){
    $k = isset($it['perm_key']) ? trim($it['perm_key']) : '';
    if ($k === '' || !isset($allowedKeys[$k])) continue;
    $v = isset($it['can_view']) ? (int)$it['can_view'] : 0;
    $toSave[$k] = ($v ? 1 : 0);
  }

  // ensure “ครบทุกเมนู” (ถ้าไม่ได้ส่งมา ถือว่า 0)
  foreach($allowedKeys as $k => $_){
    if (!isset($toSave[$k])) $toSave[$k] = 0;
  }

  // upsert
  $stmt = mysqli_prepare($connect,
    "INSERT INTO tb_agency_role_permission (ag_id, role, perm_key, can_view)
     VALUES (?,?,?,?)
     ON DUPLICATE KEY UPDATE can_view=VALUES(can_view), updated_at=NOW()"
  );
  if(!$stmt) jexit(['success'=>false,'message'=>'prepare failed']);

  foreach($toSave as $k => $v){
    mysqli_stmt_bind_param($stmt, "issi", $ag_id, $role, $k, $v);
    mysqli_stmt_execute($stmt);
  }
  mysqli_stmt_close($stmt);

  jexit(['success'=>true,'message'=>'save ok']);
}

jexit(['success'=>false,'message'=>'unknown action']);