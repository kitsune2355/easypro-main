<?php
// แสดงข้อผิดพลาด PHP เพื่อการตรวจสอบ
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config_ctrl/connect.php'; 

if (mysqli_connect_errno()) {
    http_response_code(500); 
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type"); 

date_default_timezone_set('Asia/Bangkok'); 

$action = isset($_GET['action']) ? $_GET['action'] : null; 
$response = ['success' => false, 'message' => ''];

// รับข้อมูลจาก JSON Body (สำหรับ POST/PUT)
$input = json_decode(file_get_contents('php://input'), true);

if (!$action) {
    http_response_code(400);
    echo json_encode(['error' => 'ไม่ระบุการดำเนินการ (action)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// เพิ่มพารามิเตอร์ $qty และ $qty_min รับเข้ามาตรงๆ เลย
function checkAndNotifyLowStock($connect, $pd_id, $wh_id, $qty, $qty_min) {
    // 1. แปลงค่าให้ชัวร์ว่าเป็นตัวเลข
    $qty = (float)$qty;
    $qty_min = (float)$qty_min;
    
    // 2. เช็คเงื่อนไขทันที โดยไม่ต้องไป SELECT qty ใหม่
    if ($qty <= $qty_min && $qty_min > 0) {
        
        // 3. ไปดึงชื่อสินค้า พร้อมกับชื่อคลังสินค้า (จาก tb_wh_stock)
        $sql = "SELECT 
                    (SELECT wh_name FROM tb_wh_stock WHERE id = ?) AS wh_name,
                    pd_gen_code, 
                    pd_details_head 
                FROM tb_repair_product 
                WHERE pd_id = ?";
        
        $stmt = $connect->prepare($sql);
        $stmt->bind_param("ii", $wh_id, $pd_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $pd_name = $row['pd_details_head'];
            $wh_name = !empty($row['wh_name']) ? $row['wh_name'] : 'ไม่ระบุคลัง';
            $type = 'low_stock';
            
            // 4. ป้องกันแจ้งเตือนซ้ำของวันนี้
            // หมายเหตุ: หากต้องการให้แยกแจ้งเตือนรายคลัง (สินค้า A หมดที่คลัง 1 และคลัง 2 ให้แจ้ง 2 รอบ) 
            // อาจจะต้องเอา wh_id ไปต่อท้าย type เช่น $type = 'low_stock_' . $wh_id;
            $check_sql = "SELECT id FROM notifications WHERE related_id = ? AND type = ? AND DATE(created_at) = CURDATE()";
            $chk_stmt = $connect->prepare($check_sql);
            $chk_stmt->bind_param("is", $pd_id, $type);
            $chk_stmt->execute();
            
            if ($chk_stmt->get_result()->num_rows === 0) {
                // 5. บันทึกลงตาราง notifications (เพิ่มชื่อคลังเข้าไปในข้อความ)
                $rp_format = "{$pd_name} (รหัส {$row['pd_gen_code']} | คลัง: {$wh_name}) คงเหลือ $qty";
                $insert_sql = "INSERT INTO notifications (type, related_id, rp_format, created_at) VALUES (?, ?, ?, NOW())";
                $ins_stmt = $connect->prepare($insert_sql);
                $ins_stmt->bind_param("sis", $type, $pd_id, $rp_format);
                
                if($ins_stmt->execute()) {
                    // echo "บันทึกแจ้งเตือนสำเร็จ!";
                }
            }
        }
    }
}

function generatePONumber($connect, $type = 'IN', $ag_id) {
    // 1. กำหนด Prefix ตามประเภท: IN -> PO, ADJUST -> AD
    $prefix_code = ($type === 'IN') ? "RC" : "AJ";
    $prefix = $prefix_code . "-" . date('ym') . "-";

    // 2. ค้นหาเลขที่เอกสารล่าสุดที่ขึ้นต้นด้วย Prefix นี้ "และเป็นของหน่วยงานนี้"
    // ต้อง JOIN กับ tb_repair_product เพื่อเช็ค ag_id
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(t.doc_no, '-', -1) AS UNSIGNED)) as max_num 
            FROM product_stock_transactions t
            INNER JOIN tb_repair_product p ON t.pd_id = p.pd_id
            WHERE t.doc_no LIKE ? AND p.ag_id = ?";
    
    $search_prefix = $prefix . "%";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ss", $search_prefix, $ag_id); // Bind ag_id เพิ่ม
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // 3. กำหนดลำดับถัดไป
    $next_num = ($row['max_num'] ?? 0) + 1;

    // 4. คืนค่าในรูปแบบ PO-YYMM-XXXX หรือ AD-YYMM-XXXX (เลข 4 หลัก)
    return $prefix . str_pad($next_num, 4, '0', STR_PAD_LEFT);
}


// เช็คว่ารหัสนี้มีอยู่ใน DB แล้วหรือไม่ (ของหน่วยงานนี้)
function isCodeTaken($connect, $code, $ag_id) {
    $stmt = $connect->prepare("SELECT 1 FROM tb_repair_product WHERE pd_gen_code = ? AND ag_id = ? LIMIT 1");
    $stmt->bind_param("ss", $code, $ag_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function generateProductCode($connect, $rps_id, $ag_id) { // เพิ่ม $ag_id
    // 1. ดึง rps_code มาเป็น prefix (เช่น AIR, ELE)
    $sql_prefix = "SELECT rps_code FROM tb_repair_system WHERE rps_id = ?";
    $stmt_p = $connect->prepare($sql_prefix);
    $stmt_p->bind_param("i", $rps_id);
    $stmt_p->execute();
    $res_p = $stmt_p->get_result();
    $row_p = $res_p->fetch_assoc();
    $prefix = (!empty($row_p['rps_code'])) ? $row_p['rps_code'] : 'PD';

    // 2. หาตัวเลขที่สูงที่สุดของสินค้าที่มี Prefix นี้ "และต้องเป็นของหน่วยงาน (ag_id) นี้เท่านั้น"
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(pd_gen_code, '-', -1) AS UNSIGNED)) as max_num 
            FROM tb_repair_product 
            WHERE pd_gen_code LIKE ? AND ag_id = ?"; // เพิ่มเงื่อนไข ag_id
    
    $search_prefix = $prefix . "-%";
    $stmt = $connect->prepare($sql);
    $stmt->bind_param("ss", $search_prefix, $ag_id); // bind ag_id เพิ่มเข้าไป
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $next_num = ($row['max_num'] ?? 0) + 1;

    // 3. กำหนดจำนวนหลัก (Padding)
    if ($next_num <= 999) {
        $pad_length = 4; 
    } else {
        $pad_length = strlen((string)$next_num);
    }

    return $prefix . "-" . str_pad($next_num, $pad_length, '0', STR_PAD_LEFT);
}

// --- การดำเนินการ CRUD ---

if ($action === 'check_info') {
    $pd_name = $_GET['name'] ?? '';
    $rps_id = $_GET['rps_id'] ?? null; 
    $ag_id_req = $_GET['ag_id'] ?? '';

    // 1. ค้นหาตามชื่อสินค้า โดย JOIN กับประเภทงานเพื่อเอาชื่อ rps_name มาแสดงด้วย
    $sql_check = "SELECT p.*, s.rps_name 
              FROM tb_repair_product p
              LEFT JOIN tb_repair_system s ON p.pd_rps_id = s.rps_id
              WHERE TRIM(p.pd_details_head) = TRIM(?) AND p.ag_id = ? 
              LIMIT 1";
                  
    $stmt = $connect->prepare($sql_check);
    $stmt->bind_param("ss", $pd_name, $ag_id_req);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // กรณีเจอสินค้าเดิม: ส่งข้อมูลทุกอย่างกลับไป
        echo json_encode([
            'exists' => true,
            'pd_id'  => $row['pd_id'],
            'code'   => $row['pd_gen_code'],
            'message' => 'พบสินค้าชื่อนี้อยู่ในระบบแล้ว',
            'data'   => [
                'pd_rps_id'       => $row['pd_rps_id'],
                'rps_name'        => $row['rps_name'],        // ประเภทงาน
                'pd_gen_code'     => $row['pd_gen_code'],     // รหัสสินค้า
                'pd_details_head' => $row['pd_details_head'], // ชื่อสินค้า
                'pd_model'        => $row['pd_model'],        // รุ่น/ชนิด
                'pd_price'        => $row['pd_price'],        // ราคา/หน่วย
                'pd_unit'         => $row['pd_unit'],         // หน่วย
                'pd_details'      => $row['pd_details'],      // รายละเอียดสินค้า
                'pd_brand'        => $row['pd_brand']         // ยี่ห้อ
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // กรณีไม่เจอ: ให้ Generate รหัสใหม่
        if ($rps_id) {
            $new_code = generateProductCode($connect, $rps_id, $ag_id_req);
            echo json_encode([
                'exists' => false,
                'code' => $new_code,
                'message' => 'ไม่พบชื่อนี้ ระบบจะสร้างรหัสสินค้าใหม่'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'กรุณาระบุหมวดหมู่เพื่อสร้างรหัส']);
        }
    }
    exit;
}

if ($action === 'save_product') {
    mysqli_begin_transaction($connect);
    try {
        $pd_id = $input['pd_id'] ?? null;
        $pd_rps_id = $input['pd_rps_id'] ?? null;
        $ag_id = $input['ag_id'] ?? null;
        $user_id = $input['user_id'] ?? 'system';
        
        // ดึงข้อมูลจาก input และจัดการค่าว่างให้เป็น null หรือค่าที่เหมาะสม
        $pd_details_head = !empty($input['pd_details_head']) ? $input['pd_details_head'] : null;
        $pd_details      = $input['pd_details'] ?? null;
        $pd_brand        = $input['pd_brand'] ?? null;
        $pd_model        = !empty($input['pd_model']) ? $input['pd_model'] : "-"; // ป้องกันเป็น 0 หรือว่าง
        $pd_price        = (float)($input['pd_price'] ?? 0);
        $pd_unit         = $input['pd_unit'] ?? null;

        // ==========================================
        // เพิ่มใหม่: ถ้าหน้าจอส่งมาเป็นของใหม่ (ไม่มี pd_id) ให้เช็คจากชื่อสินค้าก่อน
        // ==========================================
        if (empty($pd_id) && !empty($pd_details_head)) {
            $stmt_check = $connect->prepare("SELECT pd_id FROM tb_repair_product WHERE TRIM(pd_details_head) = TRIM(?) AND ag_id = ? LIMIT 1");
            $stmt_check->bind_param("ss", $pd_details_head, $ag_id);
            $stmt_check->execute();
            $res_check = $stmt_check->get_result();
            if ($row_check = $res_check->fetch_assoc()) {
                // ถ้าเจอชื่อสินค้าเดิมในระบบ ให้ดึง pd_id เก่ามาใช้เลย!
                $pd_id = $row_check['pd_id']; 
            }
            $stmt_check->close();
        }

        if ($pd_id) {
            // --- กรณี UPDATE ข้อมูลเดิม --- [เพิ่มส่วนนี้เพื่อประสิทธิภาพ]
            $sql_pd = "UPDATE tb_repair_product SET 
                pd_rps_id = ?, pd_details_head = ?, pd_details = ?, 
                pd_brand = ?, pd_model = ?, pd_price = ?, pd_unit = ?, 
                pd_user_upd = ?, pd_upd = NOW()
                WHERE pd_id = ? AND ag_id = ?";
            
            $stmt_pd = $connect->prepare($sql_pd);
            $stmt_pd->bind_param("issssdssis", 
                $pd_rps_id, $pd_details_head, $pd_details, 
                $pd_brand, $pd_model, $pd_price, $pd_unit, 
                $user_id, $pd_id, $ag_id);
        } else {
            // --- กรณี INSERT ข้อมูลใหม่ ---
            if (!$pd_rps_id) throw new Exception('ไม่พบข้อมูลประเภทสินค้า');

            // 1. ลองใช้รหัสที่ frontend preview มาก่อน (เร็วกว่า ไม่ต้อง query MAX ซ้ำ และ
            //    frontend มีโค้ดกันชนกันระหว่างแถวในตารางเดียวกันอยู่แล้ว)
            $client_code = !empty($input['pd_gen_code']) ? trim($input['pd_gen_code']) : null;

            if ($client_code && !isCodeTaken($connect, $client_code, $ag_id)) {
                $pd_gen_code = $client_code;
            } else {
                // 2. ไม่มีรหัสส่งมา หรือรหัสที่ส่งมาชนกับของจริงใน DB (เช่นมีคนอื่นบันทึกไปก่อนแล้ว)
                //    -> ให้ backend คำนวณใหม่ และเช็คซ้ำวนซ้อนอีกชั้นเผื่อบังเอิญชนอีก
                $pd_gen_code = generateProductCode($connect, $pd_rps_id, $ag_id);
                $attempts = 0;
                while (isCodeTaken($connect, $pd_gen_code, $ag_id) && $attempts < 5) {
                    $pd_gen_code = generateProductCode($connect, $pd_rps_id, $ag_id);
                    $attempts++;
                }
            }

            $sql_pd = "INSERT INTO tb_repair_product (
                pd_rps_id, pd_gen_code, pd_details_head, pd_details, 
                pd_brand, pd_model, pd_price, pd_unit, 
                pd_status, pd_user_ins, pd_user_upd, ag_id, pd_ins, pd_upd
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, '0', ?, ?, ?, NOW(), NOW())";

            $stmt_pd = $connect->prepare($sql_pd);
            $stmt_pd->bind_param("isssssdssss",
                $pd_rps_id, $pd_gen_code, $pd_details_head, $pd_details, 
                $pd_brand, $pd_model, $pd_price, $pd_unit, 
                $user_id, $user_id, $ag_id);
        }

        if (!$stmt_pd->execute()) {
            throw new Exception('ไม่สามารถบันทึกข้อมูลสินค้า: ' . $stmt_pd->error);
        }

        // กรณีเป็นรายการใหม่ ให้ดึง ID ที่เพิ่ง Insert
        if (!$pd_id) $pd_id = $connect->insert_id; 

        // --- จัดการสต็อก (ON DUPLICATE KEY UPDATE) ---
        $wh_ids    = $input['wh_ids'] ?? [];
        $qty       = $input['qty'] ?? 0;
        $qty_all   = $input['qty_all'] ?? $qty;
        $qty_min   = $input['qty_min'] ?? 0;
        $ps_status = $input['ps_status'] ?? '1';

        if (!empty($wh_ids) && is_array($wh_ids)) {
            // ใช้ SQL โค้ดเดิมของคุณที่ทำงานได้ถูกต้องในการอัปเดตครบทุกฟิลด์
            $sql_stock = "INSERT INTO product_stocks (pd_id, wh_id, qty, qty_all, qty_min, ps_status) 
                          VALUES (?, ?, ?, ?, ?, ?) 
                          ON DUPLICATE KEY UPDATE 
                            qty = VALUES(qty), 
                            qty_all = VALUES(qty_all), 
                            qty_min = VALUES(qty_min),
                            ps_status = VALUES(ps_status)";
            
            $stmt_st = $connect->prepare($sql_stock);
            
            if ($stmt_st) {
                foreach ($wh_ids as $wh_id) {
                    $stmt_st->bind_param("iiddds", $pd_id, $wh_id, $qty, $qty_all, $qty_min, $ps_status);
                    if (!$stmt_st->execute()) {
                        throw new Exception('ไม่สามารถบันทึกสต็อกสำหรับคลัง ID ' . $wh_id . ': ' . $stmt_st->error);
                    }
                    checkAndNotifyLowStock($connect, $pd_id, $wh_id, $qty, $qty_min);
                }
                $stmt_st->close();
            } else {
                throw new Exception('Prepare statement failed: ' . $connect->error);
            }
        }

        mysqli_commit($connect);
        echo json_encode(['success' => true, 'pd_id' => $pd_id], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        mysqli_rollback($connect);
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
    
} elseif ($action === 'get_all') {
    $ag_id = $_GET['ag_id'] ?? '';
    $wh_id = $_GET['wh_id'] ?? null;
    $search = $_GET['search'] ?? null;

    // 🌟 แก้ไข: รับค่า limit และ offset ตรงๆ จาก URL ตามที่ Frontend (Axios) ส่งมา
    $limit  = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    $sortModel   = isset($_GET['sortModel']) ? json_decode($_GET['sortModel'], true) : [];
    $filterModel = isset($_GET['filterModel']) ? json_decode($_GET['filterModel'], true) : [];

    try {
        // 1. เตรียมตัวแปรสำหรับ Dynamic WHERE
        $cond = ["p.ag_id = ?"];
        $params = [$ag_id];
        $types = "s";

        // เงื่อนไขคลังสินค้า
        if (!empty($wh_id) && $wh_id !== 'all') {
            $cond[] = "ps.wh_id = ?";
            $params[] = $wh_id;
            $types .= "s";
        }

        // เงื่อนไข Search Box
        if (!empty($search)) {
            $cond[] = "(p.pd_gen_code LIKE ? OR p.pd_details_head LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }

        // เงื่อนไข Filter จากตาราง
        if (!empty($filterModel)) {
            foreach ($filterModel as $colId => $filter) {
                $columnName = ($colId === 'wh_name') ? 'w.wh_name' : $colId;
                if (isset($filter['filter'])) {
                    $cond[] = "$columnName LIKE ?";
                    $params[] = "%" . $filter['filter'] . "%";
                    $types .= "s";
                }
            }
        }

        $whereSql = " WHERE " . implode(" AND ", $cond);

        // 2. นับจำนวนแถวทั้งหมด (Total Rows)
        $sqlCount = "SELECT COUNT(*) as total 
                     FROM tb_repair_product p
                     LEFT JOIN product_stocks ps ON p.pd_id = ps.pd_id
                     LEFT JOIN tb_wh_stock w ON ps.wh_id = w.id
                     $whereSql";
        
        $stmtCount = $connect->prepare($sqlCount);
        $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $totalRows = $stmtCount->get_result()->fetch_assoc()['total'];

        // 3. จัดการ Sorting
        $orderSql = " ORDER BY p.pd_id DESC"; 
        if (!empty($sortModel)) {
            $col = $sortModel[0]['colId'];
            $dir = (strtolower($sortModel[0]['sort']) === 'asc') ? 'ASC' : 'DESC';
            $allowed = ['pd_gen_code', 'pd_details_head', 'wh_name', 'pd_qty'];
            if (in_array($col, $allowed)) {
                $orderSql = " ORDER BY $col $dir";
            }
        }

        // 4. ดึงข้อมูลจริง (ใส่ LIMIT และ OFFSET)
        $sql = "SELECT 
                    p.*, ps.qty as pd_qty, ps.qty_all as pd_qty_all, 
                    ps.qty_min as pd_qty_min, ps.wh_id, ps.id as ps_id, 
                    ps.ps_status, w.wh_name 
                FROM tb_repair_product p
                LEFT JOIN product_stocks ps ON p.pd_id = ps.pd_id
                LEFT JOIN tb_wh_stock w ON ps.wh_id = w.id
                $whereSql $orderSql LIMIT ? OFFSET ?";
        
        // ผสมพารามิเตอร์: ข้อมูล WHERE + Limit + Offset
        $finalParams = array_merge($params, [$limit, $offset]);
        $finalTypes = $types . "ii"; // ii = integer สำหรับ Limit และ Offset

        $stmt = $connect->prepare($sql);
        $stmt->bind_param($finalTypes, ...$finalParams);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        // 5. Response กลับไปที่ตาราง
        echo json_encode([
            'success' => true, 
            'data'    => $data, 
            'total'   => (int)$totalRows 
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;

} elseif ($action === 'get_product_detail') {
    $pd_id = $_GET['pd_id'];
    $wh_id = $_GET['wh_id'] ?? null;
    
    $sql = "SELECT p.*, 
                   ps.qty as pd_qty, 
                   ps.qty_all as pd_qty_all, 
                   ps.qty_min as pd_qty_min,
                   (SELECT GROUP_CONCAT(wh_id) FROM product_stocks WHERE pd_id = p.pd_id) as wh_list 
            FROM tb_repair_product p 
            LEFT JOIN product_stocks ps ON p.pd_id = ps.pd_id AND ps.wh_id = ? 
            WHERE p.pd_id = ? 
            LIMIT 1";
            
    $stmt = $connect->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ii", $wh_id, $pd_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        if ($res) {
            $res['wh_ids'] = $res['wh_list'] ? explode(',', $res['wh_list']) : [];
            $res['pd_qty'] = $res['pd_qty'] ?? 0;
            $res['pd_qty_all'] = $res['pd_qty_all'] ?? 0;
            $res['pd_qty_min'] = $res['pd_qty_min'] ?? 0;

            echo json_encode(['success' => true, 'data' => $res], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลสินค้า']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'SQL Error: ' . $connect->error]);
    }
    exit;

} elseif ($action === 'update') {
    mysqli_begin_transaction($connect);
    try {
        $pd_id = $input['pd_id'] ?? null;
        $target_wh_id = $input['wh_id'] ?? null; // ID คลังที่เลือกเปิดขึ้นมาแก้ไข (ส่วนที่ 2)

        if (!$pd_id) {
            throw new Exception('ไม่พบรหัสสินค้า (pd_id) ที่ต้องการแก้ไข');
        }

        // --- ส่วนที่ 1: อัปเดตข้อมูลพื้นฐาน (มีผลกับทุกข้อมูลสินค้า) ---
        $pd_rps_id       = $input['pd_rps_id'] ?? null;
        $pd_details_head = $input['pd_details_head'] ?? null;
        $pd_details      = $input['pd_details'] ?? null;
        $pd_model        = $input['pd_model'] ?? null;
        $pd_price        = $input['pd_price'] ?? 0;
        $pd_unit         = $input['pd_unit'] ?? null;
        $pd_user_upd     = $input['pd_user_upd'] ?? 'system';
        $pd_upd_now      = date('Y-m-d H:i:s');

        // หมายเหตุ: เอา pd_brand และ pd_status ออกตามที่ UI แสดงล่าสุด
        $sql_pd = "UPDATE tb_repair_product SET 
                    pd_rps_id = ?, pd_details_head = ?, pd_details = ?, 
                    pd_model = ?, pd_price = ?, pd_unit = ?, 
                    pd_user_upd = ?, pd_upd = ?
                WHERE pd_id = ?";

        $stmt_pd = $connect->prepare($sql_pd);
        $stmt_pd->bind_param("ssssdsssi", 
            $pd_rps_id, $pd_details_head, $pd_details, 
            $pd_model, $pd_price, $pd_unit, 
            $pd_user_upd, $pd_upd_now, $pd_id
        );

        if (!$stmt_pd->execute()) {
            throw new Exception('ไม่สามารถแก้ไขข้อมูลสินค้าพื้นฐาน: ' . $stmt_pd->error);
        }

        // --- ส่วนที่ 2: การจัดการคลังสินค้าและจำนวนสต็อก ---
        $wh_ids_new = $input['wh_ids'] ?? []; // รายการคลังทั้งหมดที่เลือกเก็บไว้
        $qty        = $input['qty'] ?? 0;
        $qty_all    = $input['qty_all'] ?? 0;
        $qty_min    = $input['qty_min'] ?? 0;
        $ps_status    = $input['ps_status'];

        // 2.1 ดึงรายการคลังสินค้าเดิมที่มีอยู่ในฐานข้อมูล
        $sql_old_wh = "SELECT wh_id FROM product_stocks WHERE pd_id = ?";
        $stmt_old = $connect->prepare($sql_old_wh);
        $stmt_old->bind_param("i", $pd_id);
        $stmt_old->execute();
        $res_old = $stmt_old->get_result();
        $wh_ids_old = [];
        while ($row = $res_old->fetch_assoc()) {
            $wh_ids_old[] = (int)$row['wh_id'];
        }

        // 2.2 คลังที่ต้อง "ลบออก"
        $wh_to_delete = array_diff($wh_ids_old, $wh_ids_new);
        if (!empty($wh_to_delete)) {
            $placeholders = implode(',', array_fill(0, count($wh_to_delete), '?'));
            $sql_del = "DELETE FROM product_stocks WHERE pd_id = ? AND wh_id IN ($placeholders)";
            $stmt_del = $connect->prepare($sql_del);
            $types = 'i' . str_repeat('i', count($wh_to_delete));
            $stmt_del->bind_param($types, $pd_id, ...$wh_to_delete);
            $stmt_del->execute();
        }

        // 2.3 คลังที่ต้อง "เพิ่มใหม่" 
        // (ถ้าเพิ่มใหม่ จะใช้ค่า qty, qty_all, qty_min ที่กรอกมาเป็นค่าเริ่มต้น)
        $wh_to_add = array_diff($wh_ids_new, $wh_ids_old);
        if (!empty($wh_to_add)) {
            $target_pd_ids = [$pd_id, $other_pd_id];
            $sql_ins = "INSERT INTO product_stocks (pd_id, wh_id, qty, qty_all, qty_min, ps_status) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_ins = $connect->prepare($sql_ins);
            foreach ($wh_to_add as $wh_id) {
                $stmt_ins->bind_param("iiiiis", $pd_id, $wh_id, $qty, $qty_all, $qty_min, $ps_status);
                $stmt_ins->execute();
                checkAndNotifyLowStock($connect, $pd_id, $target_wh_id, $qty, $qty_min);
            }
        }

        // 2.4 อัปเดตข้อมูลสต็อก (เจาะจงคลังที่กำลังแก้ไข)
        // ตรวจสอบว่า target_wh_id ยังอยู่ในลิสต์ที่เลือก และมีค่าส่งมา
        if ($target_wh_id && in_array($target_wh_id, $wh_ids_new)) {
            $sql_upd_st = "UPDATE product_stocks 
                           SET qty = ?, qty_all = ?, qty_min = ? 
                           WHERE pd_id = ? AND wh_id = ?";
            $stmt_upd_st = $connect->prepare($sql_upd_st);
            // d=double/decimal, i=integer
            $stmt_upd_st->bind_param("iiiii", $qty, $qty_all, $qty_min, $pd_id, $target_wh_id);
            $stmt_upd_st->execute();
            checkAndNotifyLowStock($connect, $pd_id, $target_wh_id, $qty, $qty_min);
        }

        mysqli_commit($connect);
        $response['success'] = true;
        $response['message'] = 'บันทึกการแก้ไขเรียบร้อยแล้ว';

    } catch (Exception $e) {
        mysqli_rollback($connect);
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

} elseif ($action === 'update_status') {
    // รับข้อมูล ps_id (ID ของแถวใน product_stocks) และสถานะใหม่
    $ps_id      = $input['ps_id'] ?? null; 
    $new_status = $input['ps_status'] ?? '0'; 

    if (!$ps_id) {
        $response['message'] = 'ไม่พบรหัสสต็อก (ps_id) ที่ต้องการอัปเดต';
    } else {
        // อัปเดตเฉพาะ ps_status ในตาราง product_stocks
        $sql = "UPDATE product_stocks SET ps_status = ? WHERE id = ?";
        $stmt = $connect->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("si", $new_status, $ps_id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'อัปเดตสถานะคลังสินค้าเรียบร้อยแล้ว';
                $response['ps_id']   = $ps_id;
                $response['new_status'] = $new_status;
            } else {
                $response['message'] = 'เกิดข้อผิดพลาดในการอัปเดต: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = 'Prepare statement failed: ' . $connect->error;
        }
    }

} elseif ($action === 'stock_receive') {
    mysqli_begin_transaction($connect);
    try {
        $items = $input['items'] ?? []; 
        $doc_no = $input['doc_no'] ?? '';
        $ag_id = $input['ag_id'] ?? null;
        if (empty($doc_no)) {
            $doc_no = generatePONumber($connect, 'IN', $ag_id);
        }
        // ----------------------------------------------

        $trans_date = $input['date'] ?? date('Y-m-d');
        $remark = $input['remark'] ?? '';
        $user_id = $input['user_id'] ?? 'system';

        if (empty($items)) {
            throw new Exception('ไม่พบรายการสินค้าที่ต้องการรับเข้า');
        }

        // ดึงข้อมูลหลัก (Master Data) ของสินค้า เพื่อใช้ทำ Snapshot
        $sql_get_info = "
            SELECT ps.pd_id, ps.wh_id, ps.qty, 
                   p.pd_details_head, p.pd_gen_code, p.pd_price, p.pd_unit, 
                   p.pd_rps_id, p.pd_model, w.wh_name 
            FROM product_stocks ps
            LEFT JOIN tb_repair_product p ON ps.pd_id = p.pd_id
            LEFT JOIN tb_wh_stock w ON ps.wh_id = w.id
            WHERE ps.id = ?
        ";
        $sql_update = "UPDATE product_stocks SET qty = qty + ? WHERE id = ?";
        
        // เพิ่มฟิลด์สำหรับรับค่า Snapshot ใน INSERT
        $sql_log = "INSERT INTO product_stock_transactions 
                    (pd_id, ag_id, wh_id, trans_type, qty_change, qty_balance, doc_no, trans_date, details, user_id, 
                     snap_pd_name, snap_pd_code, snap_job_type, snap_model_type, snap_price, snap_unit, snap_wh_name) 
                    VALUES (?, ?, ?, 'IN', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt_get = $connect->prepare($sql_get_info);
        $stmt_upd = $connect->prepare($sql_update);
        $stmt_log = $connect->prepare($sql_log);

        foreach ($items as $item) {
            $ps_id = $item['ps_id'];
            $qty_in = (int)$item['qty'];

            if ($qty_in <= 0) continue;

            $stmt_get->bind_param("i", $ps_id);
            $stmt_get->execute();
            $res = $stmt_get->get_result();
            if ($row = $res->fetch_assoc()) {
                $pd_id = $row['pd_id'];
                $wh_id = $row['wh_id'];
                $current_qty = $row['qty'];
                
                // --- ค่า Snapshot ที่ดึงมา ณ เวลาที่มีการทำรายการ ---
                $snap_pd_name    = $row['pd_details_head'];
                $snap_pd_code    = $row['pd_gen_code'];
                $snap_job_type   = $row['pd_rps_id']; // ประเภทงาน (หมวดหมู่)
                $snap_model_type = $row['pd_model'];  // รุ่น/ชนิด
                $snap_price      = $row['pd_price'] ?? 0;
                $snap_unit       = $row['pd_unit'];
                $snap_wh_name    = $row['wh_name'];
                
                $stmt_upd->bind_param("ii", $qty_in, $ps_id);
                if (!$stmt_upd->execute()) {
                    throw new Exception("Error Update Stock ID: $ps_id");
                }

                $new_balance = $current_qty + $qty_in;
                
                $stmt_log->bind_param("isiiissssssssdss", 
                    $pd_id, $ag_id, $wh_id, $qty_in, $new_balance, $doc_no, $trans_date, $remark, $user_id,
                    $snap_pd_name, $snap_pd_code, $snap_job_type, $snap_model_type, $snap_price, $snap_unit, $snap_wh_name
                );
                
                if (!$stmt_log->execute()) {
                    throw new Exception("Error Insert Log Stock ID: $ps_id -> " . $stmt_log->error);
                }
                checkAndNotifyLowStock($connect, $pd_id, $wh_id, $qty, $qty_min);
            }
        }

        mysqli_commit($connect);
        echo json_encode([
            'success' => true, 
            'message' => 'บันทึกรับเข้าสินค้าเรียบร้อยแล้ว',
            'doc_no' => $doc_no 
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        mysqli_rollback($connect);
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;

} elseif ($action === 'stock_adjust') {
    mysqli_begin_transaction($connect);
    try {
        $items = $input['items'] ?? []; 
        $doc_no = $input['doc_no'] ?? '';
        $ag_id = $input['ag_id'] ?? null;
        if (empty($doc_no)) {
            $doc_no = generatePONumber($connect, 'ADJUST', $ag_id);
        }
        $trans_date = $input['date'] ?? date('Y-m-d');
        $remark = $input['remark'] ?? '';
        $user_id = $input['user_id'] ?? 'system';

        if (empty($items)) {
            throw new Exception('ไม่พบรายการสินค้าที่ต้องการปรับยอด');
        }

        // ดึงข้อมูลหลัก (Master Data) ของสินค้า เพื่อใช้ทำ Snapshot
        $sql_get_info = "
            SELECT ps.pd_id, ps.wh_id, ps.qty, 
                   p.pd_details_head, p.pd_gen_code, p.pd_price, p.pd_unit, 
                   p.pd_rps_id, p.pd_model, w.wh_name 
            FROM product_stocks ps
            LEFT JOIN tb_repair_product p ON ps.pd_id = p.pd_id
            LEFT JOIN tb_wh_stock w ON ps.wh_id = w.id
            WHERE ps.id = ?
        ";
        $sql_update = "UPDATE product_stocks SET qty = ? WHERE id = ?";
        
        // เพิ่มฟิลด์ ag_id, snap_job_type, snap_model_type ใน INSERT
        $sql_log = "INSERT INTO product_stock_transactions 
                    (pd_id, ag_id, wh_id, trans_type, qty_change, qty_balance, doc_no, trans_date, details, user_id,
                     snap_pd_name, snap_pd_code, snap_job_type, snap_model_type, snap_price, snap_unit, snap_wh_name) 
                    VALUES (?, ?, ?, 'ADJUST', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt_get = $connect->prepare($sql_get_info);
        $stmt_upd = $connect->prepare($sql_update);
        $stmt_log = $connect->prepare($sql_log);

        foreach ($items as $item) {
            $ps_id = $item['ps_id'];
            $new_qty = (int)$item['qty']; 

            $stmt_get->bind_param("i", $ps_id);
            $stmt_get->execute();
            $res = $stmt_get->get_result();
            if ($row = $res->fetch_assoc()) {
                $pd_id = $row['pd_id'];
                $wh_id = $row['wh_id'];
                $old_qty = (int)$row['qty'];

                // --- ค่า Snapshot ที่ดึงมา ณ เวลาที่มีการทำรายการ ---
                $snap_pd_name    = $row['pd_details_head'];
                $snap_pd_code    = $row['pd_gen_code'];
                $snap_job_type   = $row['pd_rps_id']; // ประเภทงาน (หมวดหมู่)
                $snap_model_type = $row['pd_model'];  // รุ่น/ชนิด
                $snap_price      = $row['pd_price'] ?? 0;
                $snap_unit       = $row['pd_unit'];
                $snap_wh_name    = $row['wh_name'];

                $diff = $new_qty - $old_qty; 

                // 2. อัปเดตยอดใหม่
                $stmt_upd->bind_param("ii", $new_qty, $ps_id);
                if (!$stmt_upd->execute()) {
                    throw new Exception("Error Update Stock ID: $ps_id");
                }

                // 3. บันทึกประวัติ (Log) พร้อม Snapshot และ ag_id
                // Types (16 ตัว): isiiisssssssssds
                $stmt_log->bind_param("isiiissssssssdss", 
                    $pd_id, $ag_id, $wh_id, $diff, $new_qty, $doc_no, $trans_date, $remark, $user_id,
                    $snap_pd_name, $snap_pd_code, $snap_job_type, $snap_model_type, $snap_price, $snap_unit, $snap_wh_name
                );
                
                if (!$stmt_log->execute()) {
                    throw new Exception("Error Insert Log Stock ID: $ps_id -> " . $stmt_log->error);
                }
                checkAndNotifyLowStock($connect, $pd_id, $wh_id, $qty, $qty_min);
            }
        }

        mysqli_commit($connect);
        echo json_encode(['success' => true, 'message' => 'ปรับปรุงยอดสต็อกเรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        mysqli_rollback($connect);
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;

// --- ดึงรายงาน (Report) ---
} elseif ($action === 'get_report') {
    $type       = $_GET['type'] ?? 'IN'; 
    $ag_id      = $_GET['ag_id'] ?? '';
    $search     = $_GET['search'] ?? '';      // สำหรับค้นหา เลขที่เอกสาร, รหัสสินค้า, ชื่อสินค้า
    $start_date = $_GET['start_date'] ?? '';  // วันที่เริ่มต้น (YYYY-MM-DD)
    $end_date   = $_GET['end_date'] ?? '';    // วันที่สิ้นสุด (YYYY-MM-DD)
    
    // 1. สร้าง Base SQL
    // ใช้ฟังก์ชัน COALESCE เพื่อดึงข้อมูล Snapshot ก่อน ถ้าข้อมูลเก่ายังไม่มี Snapshot ให้ดึงจากข้อมูลปัจจุบันแทน
    $sql = "SELECT t.*, 
                   COALESCE(t.snap_pd_code, p.pd_gen_code) AS pd_gen_code, 
                   COALESCE(t.snap_pd_name, p.pd_details_head) AS pd_details_head, 
                   COALESCE(t.snap_unit, p.pd_unit) AS pd_unit, 
                   COALESCE(t.snap_wh_name, w.wh_name) AS wh_name 
            FROM product_stock_transactions t
            LEFT JOIN tb_repair_product p ON t.pd_id = p.pd_id
            LEFT JOIN tb_wh_stock w ON t.wh_id = w.id
            WHERE t.trans_type = ? AND p.ag_id = ?";

    // รายการพารามิเตอร์สำหรับ bind_param
    $params = [$type, $ag_id];
    $types = "ss";

    // 2. เพิ่มเงื่อนไขการค้นหาด้วย Keyword
    // รองรับการค้นหาจากทั้งข้อมูล Snapshot และ Master Data
    if (!empty($search)) {
        $sql .= " AND (t.doc_no LIKE ? 
                  OR COALESCE(t.snap_pd_code, p.pd_gen_code) LIKE ? 
                  OR COALESCE(t.snap_pd_name, p.pd_details_head) LIKE ? 
                  OR t.details LIKE ?)";
        $searchParam = "%$search%";
        array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
        $types .= "ssss";
    }

    // 3. เพิ่มเงื่อนไขช่วงวันที่
    if (!empty($start_date) && !empty($end_date)) {
        $sql .= " AND t.trans_date BETWEEN ? AND ?";
        array_push($params, $start_date, $end_date);
        $types .= "ss";
    } elseif (!empty($start_date)) {
        $sql .= " AND t.trans_date >= ?";
        array_push($params, $start_date);
        $types .= "ss";
    }

    $sql .= " ORDER BY t.trans_date DESC, t.id DESC";

    $stmt = $connect->prepare($sql);
    
    // ทำ Dynamic Binding
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $groupedData = [];

    while($row = $result->fetch_assoc()) {
        $groupKey = (!empty($row['doc_no'])) 
                    ? $row['doc_no'] 
                    : $row['trans_date'] . "_" . md5($row['details']);

        if (!isset($groupedData[$groupKey])) {
            $groupedData[$groupKey] = [
                "trans_type" => $row['trans_type'],
                "doc_no"     => $row['doc_no'],
                "trans_date" => $row['trans_date'],
                "details"    => $row['details'],
                "user_id"    => $row['user_id'],
                "items"      => []
            ];
        }

        $groupedData[$groupKey]['items'][] = [
            "id"              => $row['id'],
            "pd_id"           => $row['pd_id'],
            "wh_id"           => $row['wh_id'],
            "qty_change"      => $row['qty_change'],
            "qty_balance"     => $row['qty_balance'],
            "details"         => $row['details'],
            "user_id"         => $row['user_id'],
            "created_at"      => $row['created_at'],
            // คืนค่าที่มาจาก COALESCE ทำให้ได้ Snapshot (ถ้ามี)
            "pd_gen_code"     => $row['pd_gen_code'],
            "pd_details_head" => $row['pd_details_head'],
            "pd_unit"         => $row['pd_unit'],
            "wh_name"         => $row['wh_name']
        ];
    }
    
    $finalData = array_values($groupedData);

    echo json_encode([
        'success' => true, 
        'data'    => $finalData, 
        'total'   => count($finalData) 
    ], JSON_UNESCAPED_UNICODE);
    exit;

} elseif ($action === 'get_next_po') {
    try {
        $ag_id = $_GET['ag_id'] ?? '';
        $type = $_GET['type'] ?? 'IN';
        if (empty($ag_id)) {
             throw new Exception('กรุณาระบุหน่วยงาน (ag_id)');
        }
        $next_po = generatePONumber($connect, $type, $ag_id);
        echo json_encode(['success' => true, 'next_po' => $next_po]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;

} elseif ($action === 'delete_product') {
    mysqli_begin_transaction($connect);
    try {
        // รับค่า Array ของ ID ที่ต้องการลบ และรหัสหน่วยงาน
        $pd_ids = $input['pd_ids'] ?? [];
        $ag_id = $input['ag_id'] ?? null;

        if (empty($pd_ids)) {
            throw new Exception('ไม่พบรายการที่ต้องการลบ');
        }

        // 1. เตรียมจำนวนตัวแปร (?) ให้เท่ากับจำนวน ID ที่ส่งมา
        $placeholders = implode(',', array_fill(0, count($pd_ids), '?'));
        $types = str_repeat('i', count($pd_ids)); // กำหนด type เป็น integer สำหรับ pd_id

        // 2. ลบข้อมูลใน product_stocks ก่อน (เพื่อป้องกันปัญหากับ Foreign Key)
        $sql_del_stock = "DELETE FROM product_stocks WHERE pd_id IN ($placeholders)";
        $stmt_del_stock = $connect->prepare($sql_del_stock);
        $stmt_del_stock->bind_param($types, ...$pd_ids);
        $stmt_del_stock->execute();

        // 3. ลบข้อมูลหลักใน tb_repair_product (เช็ค ag_id ด้วยเพื่อความปลอดภัย)
        $sql_del_pd = "DELETE FROM tb_repair_product WHERE pd_id IN ($placeholders) AND ag_id = ?";
        
        // นำ ag_id ต่อท้ายเข้าไปใน Array สำหรับการ bind_param
        $params = $pd_ids;
        $params[] = $ag_id; 
        $types_pd = $types . 's'; // เพิ่ม s สำหรับ ag_id (string)

        $stmt_del_pd = $connect->prepare($sql_del_pd);
        $stmt_del_pd->bind_param($types_pd, ...$params);
        
        if (!$stmt_del_pd->execute()) {
            throw new Exception('ไม่สามารถลบข้อมูลสินค้าได้: ' . $stmt_del_pd->error);
        }

        mysqli_commit($connect);
        
        echo json_encode(['success' => true, 'message' => 'ลบข้อมูลเรียบร้อยแล้ว'], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        mysqli_rollback($connect);
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

if (isset($connect)) mysqli_close($connect);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);