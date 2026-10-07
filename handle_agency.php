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

// รับข้อมูลจาก php://input (รองรับ JSON Payload จาก fetch API)
$input = json_decode(file_get_contents('php://input'), true);

// ----------------------------------------------------------------
// ✅ 1. ดึงข้อมูลทั้งหมด (GET ALL) รองรับ Search และ Pagination
// ----------------------------------------------------------------
if ($action === 'get_all') {
    $ag_id = isset($_GET['ag_id']) ? intval($_GET['ag_id']) : 1; 
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 0;
    $area_id_filter = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0; // ฟิลเตอร์เฉพาะอาคาร

    // 1. สร้างเงื่อนไข (WHERE conditions) พื้นฐาน
    $where_clause = "WHERE a.ag_id = ?";
    $params = [$ag_id];
    $types = "i";

    // หากต้องการดูข้อมูลเฉพาะอาคาร
    if ($area_id_filter > 0) {
        $where_clause .= " AND a.area_id = ?";
        $params[] = $area_id_filter;
        $types .= "i";
    }

    // หากมีการค้นหา
    if ($search !== '') {
        $where_clause .= " AND (c.ac_name LIKE ? OR r.ar_name LIKE ? OR a.area_name LIKE ?)";
        $search_param = "%{$search}%";
        array_push($params, $search_param, $search_param, $search_param);
        $types .= "sss";
    }

    // 2. Query นับจำนวนข้อมูลทั้งหมด (Total Records) สำหรับใช้ใน Pagination
    $count_sql = "
        SELECT COUNT(*) AS total 
        FROM tb_area a
        LEFT JOIN tb_area_class c ON a.area_id = c.ac_area_id
        LEFT JOIN tb_area_room r ON c.ac_id = r.ar_ac_id
        $where_clause
    ";
    $count_stmt = mysqli_prepare($connect, $count_sql);
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
    mysqli_stmt_execute($count_stmt);
    $total_row = mysqli_fetch_assoc(mysqli_stmt_get_result($count_stmt));
    $total_records = $total_row['total'] ?? 0;

    // 3. Query ดึงข้อมูลหลัก
    $sql = "
        SELECT 
            a.area_id, a.area_name, a.area_status, 
            c.ac_id, c.ac_name AS floor, c.ac_status AS floor_status, 
            r.ar_id, r.ar_name AS room_name, r.ar_status AS status
        FROM tb_area a
        LEFT JOIN tb_area_class c ON a.area_id = c.ac_area_id
        LEFT JOIN tb_area_room r ON c.ac_id = r.ar_ac_id
        $where_clause
        ORDER BY a.area_id DESC, c.ac_id ASC, r.ar_id ASC
    ";

    // 4. กรณีที่มีการส่ง page และ limit มาให้ต่อท้ายด้วย LIMIT/OFFSET
    if ($page > 0 && $limit > 0) {
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        array_push($params, $limit, $offset);
        $types .= "ii";
    }

    // Execute คำสั่ง SQL หลัก
    $stmt = mysqli_prepare($connect, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // 5. จัดรูปแบบข้อมูลเตรียมส่งกลับ (จัดกลุ่มห้องให้อยู่ภายใต้ชั้น)
    $db_data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $area_id = $row['area_id'];
        
        if (!isset($db_data[$area_id])) {
            $db_data[$area_id] = [
                'id' => $area_id,
                'name' => $row['area_name'],
                'status' => $row['area_status'],
                'floors' => [] 
            ];
        }
        
        if (!empty($row['floor'])) {
            $ac_id = $row['ac_id']; // ดึง ID ชั้น
            
            // ใช้ ac_id เป็น Key ในการจัดกลุ่ม เพื่อความแม่นยำ
            if (!isset($db_data[$area_id]['floors'][$ac_id])) {
                $db_data[$area_id]['floors'][$ac_id] = [
                    'floor_id' => $ac_id,      // เพิ่ม ID ชั้น
                    'floor' => $row['floor'],
                    'floor_status' => $row['floor_status'],
                    'rooms' => []
                ];
            }
            
            if (!empty($row['room_name'])) {
                $db_data[$area_id]['floors'][$ac_id]['rooms'][] = [
                    'room_id' => $row['ar_id'], // เพิ่ม ID ห้อง
                    'room_name' => $row['room_name'],
                    'status' => $row['status']
                ];
            }
        }
    }

    // แปลง floors จาก Associative Array เป็น Indexed Array
    foreach ($db_data as $key => $area) {
        $db_data[$key]['floors'] = array_values($area['floors']);
    }
    
    // 6. ส่ง Response คืนให้ Frontend
    $response['success'] = true;
    $response['data'] = $db_data;
    
    // แนบข้อมูล Pagination ไปให้ Frontend ใช้ต่อด้วย
    $response['pagination'] = [
        'total_records' => $total_records,
        'total_pages' => $limit > 0 ? ceil($total_records / $limit) : 1,
        'current_page' => $page > 0 ? $page : 1,
        'limit' => $limit > 0 ? $limit : $total_records
    ];
}

// ----------------------------------------------------------------
// ✅ 2. เพิ่มอาคารใหม่ (ADD BUILDING)
// ----------------------------------------------------------------
elseif ($action === 'add_building') {
    $ag_id = isset($input['ag_id']) ? intval($input['ag_id']) : 1;
    $area_name = $input['area_name'] ?? '';
    $user_id = $input['user_id'] ?? 1; // ควรดึงจาก $_SESSION['user_id']
    $date_now = date('Y-m-d H:i:s');

    if (empty($area_name)) {
        echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ครบถ้วน'], JSON_UNESCAPED_UNICODE); exit;
    }

    $stmt = mysqli_prepare($connect, "INSERT INTO tb_area (area_name, ag_id, area_status, area_user_ins, area_ins) VALUES (?, ?, 1, ?, ?)");
    mysqli_stmt_bind_param($stmt, "siis", $area_name, $ag_id, $user_id, $date_now);
    
    if (mysqli_stmt_execute($stmt)) {
        $response['success'] = true;
        $response['area_id'] = mysqli_insert_id($connect);
    } else {
        $response['error'] = mysqli_error($connect);
    }
}

// ----------------------------------------------------------------
// ✅ 3. บันทึกชั้น และ ห้อง (SAVE ROOMS)
// ----------------------------------------------------------------
elseif ($action === 'save') {
    $area_id = isset($input['area_id']) ? intval($input['area_id']) : null;
    $rooms = $input['rooms'] ?? [];
    $user_id = isset($input['user_id']) ? (string)$input['user_id'] : 'admin'; // รับค่าเป็น String
    $date_now = date('Y-m-d H:i:s');

    if (!$area_id) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบรหัสอาคาร'], JSON_UNESCAPED_UNICODE); exit;
    }

    mysqli_begin_transaction($connect);
    try {
        // อัปเดต Binding 'ac_upd_ins' และ 'ar_upd_ins' ให้เป็น 's' (String) เผื่อเป็น "admin"
        $stmt_upd_floor = mysqli_prepare($connect, "UPDATE tb_area_class SET ac_name = ?, ac_status = ?, ac_upd = NOW(), ac_upd_ins = ? WHERE ac_id = ?");
        $stmt_ins_floor = mysqli_prepare($connect, "INSERT INTO tb_area_class (ac_area_id, ac_name, ac_status, ac_user_ins, ac_ins) VALUES (?, ?, ?, ?, ?)");
        
        $stmt_upd_room = mysqli_prepare($connect, "UPDATE tb_area_room SET ar_name = ?, ar_status = ?, ar_upd = NOW(), ar_upd_ins = ? WHERE ar_id = ?");
        $stmt_ins_room = mysqli_prepare($connect, "INSERT INTO tb_area_room (ar_area_id, ar_ac_id, ar_name, ar_status, ar_user_ins, ar_ins, ar_upd, ar_upd_ins) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), ?)");

        $floor_cache = []; 
        $last_ac_id = null; // เก็บ ID ชั้นของแถวบนสุด (เผื่อแถวล่างไม่ได้ส่งข้อมูลชั้นมา)

        foreach ($rooms as $room) {
            $floor_id = !empty($room['floor_id']) ? intval($room['floor_id']) : null;
            $floor_name = isset($room['floor']) && $room['floor'] !== null ? trim($room['floor']) : '';
            $floor_status = isset($room['floor_status']) && $room['floor_status'] !== null ? intval($room['floor_status']) : 0;
            
            $room_id = !empty($room['room_id']) ? intval($room['room_id']) : null;
            $room_name = isset($room['room_name']) && $room['room_name'] !== null ? trim($room['room_name']) : '';
            $status = isset($room['status']) && $room['status'] !== null ? intval($room['status']) : 0;

            $ac_id = null;

            // -----------------------------------------
            // 1. ตรวจสอบ/จัดการข้อมูลชั้น (Floor)
            // -----------------------------------------
            if ($floor_id) {
                $ac_id = $floor_id; // มี ID ชั้นส่งมา ให้ใช้เลย
                $last_ac_id = $ac_id;
                
                // อัปเดตชั้น เฉพาะเมื่อมี "ชื่อชั้น" ส่งมาด้วย (แถวแรกของ Merge cell)
                if ($floor_name !== '') {
                    mysqli_stmt_bind_param($stmt_upd_floor, "sisi", $floor_name, $floor_status, $user_id, $floor_id);
                    mysqli_stmt_execute($stmt_upd_floor);
                }
            } else {
                if ($floor_name !== '') {
                    // หากไม่มี floor_id แต่มีชื่อ ให้หาหรือเพิ่มใหม่
                    if (isset($floor_cache[$floor_name])) {
                        $ac_id = $floor_cache[$floor_name];
                    } else {
                        $stmt_find_f = mysqli_prepare($connect, "SELECT ac_id FROM tb_area_class WHERE ac_area_id = ? AND ac_name = ?");
                        mysqli_stmt_bind_param($stmt_find_f, "is", $area_id, $floor_name);
                        mysqli_stmt_execute($stmt_find_f);
                        $res_f = mysqli_stmt_get_result($stmt_find_f);
                        
                        if ($row_f = mysqli_fetch_assoc($res_f)) {
                            $ac_id = $row_f['ac_id'];
                            mysqli_stmt_bind_param($stmt_upd_floor, "sisi", $floor_name, $floor_status, $user_id, $ac_id);
                            mysqli_stmt_execute($stmt_upd_floor);
                        } else {
                            mysqli_stmt_bind_param($stmt_ins_floor, "isiss", $area_id, $floor_name, $floor_status, $user_id, $date_now);
                            mysqli_stmt_execute($stmt_ins_floor);
                            $ac_id = mysqli_insert_id($connect);
                        }
                        $floor_cache[$floor_name] = $ac_id;
                    }
                    $last_ac_id = $ac_id;
                } else {
                    // ถ้าไม่มีทั้ง ID และ ชื่อชั้น ให้ดึงของแถวก่อนหน้ามาใช้
                    $ac_id = $last_ac_id;
                }
            }

            // ถ้าหาชั้นไม่เจอ หรือไม่มีชื่อห้อง ข้ามห้องนี้ไป
            if (!$ac_id || empty($room_name)) continue;

            // -----------------------------------------
            // 2. จัดการข้อมูลห้อง (Room)
            // -----------------------------------------
            if ($room_id) {
                // ✅ อัปเดตห้องจาก room_id (สถานะก็จะถูกอัปเดตตรงนี้)
                mysqli_stmt_bind_param($stmt_upd_room, "sisi", $room_name, $status, $user_id, $room_id);
                mysqli_stmt_execute($stmt_upd_room);
            } else {
                // หากไม่มี room_id ให้ค้นหาหรือ Insert
                $stmt_find_r = mysqli_prepare($connect, "SELECT ar_id FROM tb_area_room WHERE ar_ac_id = ? AND ar_name = ?");
                mysqli_stmt_bind_param($stmt_find_r, "is", $ac_id, $room_name);
                mysqli_stmt_execute($stmt_find_r);
                $res_r = mysqli_stmt_get_result($stmt_find_r);
                
                if ($row_r = mysqli_fetch_assoc($res_r)) {
                    $ar_id = $row_r['ar_id'];
                    mysqli_stmt_bind_param($stmt_upd_room, "sisi", $room_name, $status, $user_id, $ar_id);
                    mysqli_stmt_execute($stmt_upd_room);
                } else {
                    mysqli_stmt_bind_param($stmt_ins_room, "iisiss", $area_id, $ac_id, $room_name, $status, $user_id, $user_id);
                    mysqli_stmt_execute($stmt_ins_room);
                }
            }
        }
        
        mysqli_commit($connect); 
        $response['success'] = true;

    } catch (Exception $e) {
        mysqli_rollback($connect); 
        $response['success'] = false;
        $response['error'] = 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage();
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;

// --- ส่วน Action: update_building ใน handle_agency.php ---
} elseif ($action === 'update_building') {
    $area_id = isset($input['area_id']) ? intval($input['area_id']) : null;
    $area_name = $input['area_name'] ?? null;
    $area_status = isset($input['area_status']) ? intval($input['area_status']) : null;
    $user_id = $input['user_id'] ?? 1;

    if (!$area_id) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบรหัสอาคาร']); exit;
    }

    $fields = []; $params = []; $types = "";
    if ($area_name !== null) { 
        $fields[] = "area_name = ?"; 
        $params[] = $area_name; 
        $types .= "s"; 
    }
    if ($area_status !== null) { 
        $fields[] = "area_status = ?"; 
        $params[] = $area_status; 
        $types .= "i"; 
    }
    
    // ใช้คอลัมน์ area_user_ins แทน area_upd_ins ตามโครงสร้างที่คุณมี
    $fields[] = "area_user_ins = ?"; 
    $params[] = $user_id; 
    $types .= "i"; 

    $params[] = $area_id; 
    $types .= "i"; 

    // สร้าง SQL อัปเดต (ตัด area_upd ออกเพราะไม่มีคอลัมน์นี้)
    $sql = "UPDATE tb_area SET " . implode(", ", $fields) . " WHERE area_id = ?";
    $stmt = mysqli_prepare($connect, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        $response['success'] = mysqli_stmt_execute($stmt);
        if (!$response['success']) { 
            $response['error'] = "Execute Error: " . mysqli_stmt_error($stmt); 
        }
    } else {
        $response['success'] = false;
        $response['error'] = "SQL Error: " . mysqli_error($connect);
    }
    echo json_encode($response);
    exit;
}

if (isset($connect)) mysqli_close($connect);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>