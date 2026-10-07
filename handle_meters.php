<?php
// แสดงข้อผิดพลาด PHP เพื่อการตรวจสอบ (ปิดเมื่อขึ้น Production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config_ctrl/connect.php'; 

if (mysqli_connect_errno()) {
    http_response_code(500); 
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . mysqli_connect_error()], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type"); 

date_default_timezone_set('Asia/Bangkok'); 

$action = isset($_GET['action']) ? $_GET['action'] : null; 
$response = ['success' => false, 'data' => []];

if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุการดำเนินการ (action)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// แม็ปประเภทมิเตอร์: โค้ด (ในฐานข้อมูล) <-> ชื่อไทย (บนหน้าบ้าน)
// เพิ่มประเภทใหม่ในอนาคตให้เติมที่ map ตรงนี้ที่เดียว
$METER_TYPE_MAP = [
    'wt'  => 'มิเตอร์น้ำ',
    'et'  => 'มิเตอร์ไฟ',
    'tou' => 'มิเตอร์TOU',
    'gt'  => 'มิเตอร์แก๊ส',
];

// ฟังก์ชันแปลงค่า Type มิเตอร์ให้ตรงกับฐานข้อมูล/หน้าบ้าน
function getMeterTypeText($type) {
    global $METER_TYPE_MAP;
    return $METER_TYPE_MAP[$type] ?? '';
}

function getMeterTypeValue($text) {
    global $METER_TYPE_MAP;
    $code = array_search($text, $METER_TYPE_MAP, true);
    return $code !== false ? $code : '';
}

// ----------------------------------------------------------------
// ✅ 1. ดึงข้อมูลทั้งหมด (GET ALL)
// ----------------------------------------------------------------
if ($action === 'get_all') {
    $ag_id = isset($_GET['ag_id']) ? mysqli_real_escape_string($connect, $_GET['ag_id']) : '';
    $search = isset($_GET['search']) ? mysqli_real_escape_string($connect, trim($_GET['search'])) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    $where_clause = "WHERE m.mr_ag_id = '$ag_id'";
    
    // ถ้ามีการค้นหา
    if (!empty($search)) {
        $where_clause .= " AND (m.mt_name LIKE '%$search%' OR a.area_name LIKE '%$search%')";
    }

    // นับจำนวนรวมทั้งหมด
    $sql_count = "SELECT COUNT(*) as total FROM tb_meter m 
                  LEFT JOIN tb_area a ON m.mt_rp_area_id = a.area_id AND a.ag_id = m.mr_ag_id AND a.area_status = 0
                  $where_clause";
    $result_count = mysqli_query($connect, $sql_count);
    $total_rows = $result_count ? mysqli_fetch_assoc($result_count)['total'] : 0;

    // ดึงข้อมูลจริงพร้อม JOIN
    $sql = "SELECT m.mt_id, m.mt_type, m.mt_name, m.mt_max_val, m.mt_limit_percent, m.mt_rp_area_id, m.mt_rp_ac_id, m.mt_rp_ar_id, m.mt_status,
                   a.area_name, ac.ac_name, ar.ar_name 
            FROM tb_meter m
            LEFT JOIN tb_area a ON m.mt_rp_area_id = a.area_id AND a.ag_id = m.mr_ag_id AND a.area_status = 0
            LEFT JOIN tb_area_class ac ON m.mt_rp_ac_id = ac.ac_id AND ac.ac_status = 0
            LEFT JOIN tb_area_room ar ON m.mt_rp_ar_id = ar.ar_id AND ar.ar_status = 0
            $where_clause
            ORDER BY m.mt_id DESC";

    if ($limit > 0) {
        $sql .= " LIMIT $limit OFFSET $offset";
    }
            
    $result = mysqli_query($connect, $sql);
    
    if ($result) {
        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            
            // สร้าง text สถานที่แบบเต็มส่งให้หน้าบ้านโดยตรง
            $locParts = [];
            if (!empty($row['area_name'])) $locParts[] = $row['area_name'];
            if (!empty($row['ac_name'])) $locParts[] = $row['ac_name'];
            if (!empty($row['ar_name'])) $locParts[] = $row['ar_name'];
            $full_location = implode(' ', $locParts);

            $data[] = [
                'id' => $row['mt_id'],
                'mt_type' => getMeterTypeText($row['mt_type']),
                'mt_name' => $row['mt_name'],
                // ⭐ 2. ดึงค่าจาก DB ส่งให้ Handsontable (ถ้าไม่มีให้ตั้ง default เป็น 1000000)
                'max_val' => (!empty($row['mt_max_val']) && $row['mt_max_val'] > 0) ? (int)$row['mt_max_val'] : 1000000,
                'limit_percent' => $row['mt_limit_percent'],
                'area_id' => $row['mt_rp_area_id'],
                'area_name' => $row['area_name'] ?? '',
                'ac_id' => $row['mt_rp_ac_id'],
                'ac_name' => $row['ac_name'] ?? '',
                'ar_id' => $row['mt_rp_ar_id'],
                'ar_name' => $row['ar_name'] ?? '',
                'full_location' => $full_location, 
                'status' => $row['mt_status']
            ];
        }
        $response['success'] = true;
        $response['total'] = $total_rows;
        $response['data'] = $data;
    } else {
        $response['error'] = 'Database query error: ' . mysqli_error($connect);
    }
}

// ----------------------------------------------------------------
// ✅ 2. บันทึก หรือ แก้ไขข้อมูล (ADD & UPDATE) รวมถึง DELETE ด้วย
// ----------------------------------------------------------------
elseif ($action === 'save') {
    $input = json_decode(file_get_contents('php://input'), true);
    $changes = isset($input['data']) ? $input['data'] : [];
    $deleted_ids = isset($input['deleted_ids']) ? $input['deleted_ids'] : [];
    $ag_id = isset($input['ag_id']) ? $input['ag_id'] : '';
    $user_id = isset($input['user_id']) ? $input['user_id'] : '';
    $now = date('Y-m-d H:i:s');

    if ((!empty($changes) && is_array($changes)) || (!empty($deleted_ids) && is_array($deleted_ids))) {
        mysqli_begin_transaction($connect);
        try {
            
            // 1. จัดการลบข้อมูล
            if (!empty($deleted_ids)) {
                $ids_str = implode(',', array_map('intval', $deleted_ids));
                $sql_del = "DELETE FROM tb_meter WHERE mt_id IN ($ids_str)";
                if (!mysqli_query($connect, $sql_del)) {
                    throw new Exception("Delete Error: " . mysqli_error($connect));
                }
            }

            // 2. จัดการเพิ่มหรือแก้ไขข้อมูล
            if (!empty($changes)) {
                // เตรียม Statement สำหรับ Update และ Insert
                $sql_update = "UPDATE tb_meter SET mt_name=?, mt_type=?, mt_rp_area_id=?, mt_rp_ac_id=?, mt_rp_ar_id=?, mt_max_val=?, mt_limit_percent=?, mt_status=?, mt_user_id_upd=?, mt_upd=NOW() WHERE mt_id=?";
                $stmt_update = mysqli_prepare($connect, $sql_update);
                if (!$stmt_update) {
                    throw new Exception("Prepare Update Error: " . mysqli_error($connect));
                }

                $sql_insert = "INSERT INTO tb_meter (mt_name, mt_type, mt_rp_area_id, mt_rp_ac_id, mt_rp_ar_id, mr_ag_id, mt_max_val, mt_limit_percent, mt_status, mt_user_ins, mt_user_id_upd, mt_ins, mt_upd) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt_insert = mysqli_prepare($connect, $sql_insert);
                if (!$stmt_insert) {
                    throw new Exception("Prepare Insert Error: " . mysqli_error($connect));
                }

                foreach ($changes as $row) {
                    $mt_type = getMeterTypeValue($row['mt_type']);
                    $mt_name = isset($row['mt_name']) ? $row['mt_name'] : '';
                    
                    // ⭐ รับค่า max_val และจัดการกรณีเป็นค่าว่างหรือมีลูกน้ำ
                    $max_val_raw = isset($row['max_val']) ? $row['max_val'] : '';
                    $max_val = ($max_val_raw !== '') ? (int)str_replace(',', '', $max_val_raw) : 1000000;
                    $limit_percent = isset($row['limit_percent']) && $row['limit_percent'] !== '' ? (float)$row['limit_percent'] : 20.00;
                    
                    $area_id = !empty($row['area_id']) ? intval($row['area_id']) : 0;
                    $ac_id   = !empty($row['ac_id']) ? intval($row['ac_id']) : 0;
                    $ar_id   = !empty($row['ar_id']) ? intval($row['ar_id']) : 0;
                    
                    // ตรวจสอบสถานะ
                    $status = (isset($row['status']) && ($row['status'] === 'Active' || (string)$row['status'] === '0')) ? 0 : 1;
                    $db_id = isset($row['id']) ? intval($row['id']) : 0;

                    if ($db_id > 0) {
                        // อัปเดตข้อมูล: ตรวจสอบให้แน่ใจว่ามี 10 ตัวแปร
                        // "ssiiiiidii" คือ 10 ตัวอักษร
                        mysqli_stmt_bind_param($stmt_update, "ssiiiiidii", 
                            $mt_name, $mt_type, $area_id, $ac_id, $ar_id, 
                            $max_val, $limit_percent, $status, $user_id, $db_id
                        );
                        if (!mysqli_stmt_execute($stmt_update)) {
                            throw new Exception("Update Error: " . mysqli_stmt_error($stmt_update)); 
                        }
                    } else {
                        // เพิ่มข้อมูล: ตรวจสอบให้แน่ใจว่ามี 10 ตัวแปร
                        mysqli_stmt_bind_param($stmt_insert, "ssiiisidiii", 
                            $mt_name, $mt_type, $area_id, $ac_id, $ar_id, 
                            $ag_id, $max_val, $limit_percent, $status, $user_id, $user_id
                        );
                        if (!mysqli_stmt_execute($stmt_insert)) {
                            throw new Exception("Insert Error: " . mysqli_stmt_error($stmt_insert)); 
                        }
                    }
                }
                mysqli_stmt_close($stmt_update);
                mysqli_stmt_close($stmt_insert);
            }

            mysqli_commit($connect);
            $response['success'] = true;
            $response['message'] = 'บันทึกข้อมูลเรียบร้อยแล้ว';
            
        } catch (Exception $e) {
            mysqli_rollback($connect);
            $response['success'] = false;
            $response['error'] = 'Transaction failed: ' . $e->getMessage();
        }
    } else {
        $response['success'] = false;
        $response['error'] = 'ไม่มีข้อมูลให้บันทึก';
    }
}

// ----------------------------------------------------------------
// ✅ 3. ลบข้อมูล (DELETE)
// ----------------------------------------------------------------
elseif ($action === 'delete') {
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = isset($input['ids']) ? $input['ids'] : [];

    if (!empty($ids) && is_array($ids)) {
        $ids_str = implode(',', array_map('intval', $ids));
        $sql = "DELETE FROM tb_meter WHERE mt_id IN ($ids_str)";
        
        if (mysqli_query($connect, $sql)) {
            $response['success'] = true;
            $response['message'] = 'ลบข้อมูลเรียบร้อยแล้ว';
        } else {
            $response['error'] = 'Delete failed: ' . mysqli_error($connect);
        }
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>