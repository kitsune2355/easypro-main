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
    $rps_id = $_GET['rps_id'] ?? null; // ID หมวดหมู่
    $ag_id_req = $_GET['ag_id'] ?? '';

    // 1. ค้นหาตามชื่อก่อน
    $stmt = $connect->prepare("SELECT pd_id, pd_gen_code FROM tb_repair_product WHERE pd_details_head = ? AND ag_id = ? LIMIT 1");
    $stmt->bind_param("ss", $pd_name, $ag_id_req);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        // กรณีเจอสินค้าเดิม
        echo json_encode([
            'exists' => true,
            'pd_id' => $row['pd_id'],
            'code' => $row['pd_gen_code'],
            'message' => 'พบสินค้าเดิมในระบบ'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // กรณีไม่เจอ ให้ Generate รหัสใหม่ (เรียกใช้ Function เดิมที่มีอยู่)
        if ($rps_id) {
            $new_code = generateProductCode($connect, $rps_id, $ag_id_req); // ใช้ function ในไฟล์ handle_stock.php
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
        $wh_ids = $input['wh_ids'] ?? []; // รับ array ของ id คลัง [1, 2, ...]
        $ag_id = $input['ag_id'] ?? null;
        
        // ข้อมูลสต็อกพื้นฐาน
        $qty     = $input['qty'] ?? 0;
        $qty_all = $input['qty_all'] ?? $qty;
        $qty_min = $input['qty_min'] ?? 0;
        $ps_status = $input['ps_status'] ?? '1';

        // --- 1. ส่วนของการสร้างสินค้าใหม่ (กรณีไม่มี pd_id) ---
        if (!$pd_id) {
            if (!$pd_rps_id) {
                throw new Exception('ไม่พบข้อมูลประเภทสินค้า');
            }

            $pd_gen_code = generateProductCode($connect, $pd_rps_id, $ag_id);
            
            $sql_pd = "INSERT INTO tb_repair_product (
                pd_rps_id, pd_gen_code, pd_details_head, pd_details, 
                pd_brand, pd_model, pd_price, pd_unit, 
                pd_status, pd_user_ins, pd_user_upd, ag_id, pd_ins, pd_upd
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $stmt_pd = $connect->prepare($sql_pd);
            
            $pd_details_head = $input['pd_details_head'] ?? null;
            $pd_details      = $input['pd_details'] ?? null;
            $pd_brand        = $input['pd_brand'] ?? null;
            $pd_model        = $input['pd_model'] ?? null;
            $pd_price        = $input['pd_price'] ?? 0;
            $pd_unit         = $input['pd_unit'] ?? null;
            $pd_status       = '0';
            $pd_user_ins     = $input['user_id'] ?? 'system';
            $pd_user_upd     = $input['user_id'] ?? 'system';
            $ag_id           = $input['ag_id'] ?? null;

            $stmt_pd->bind_param("sssssdssssss", 
                $pd_rps_id, $pd_gen_code, $pd_details_head, $pd_details,
                $pd_brand, $pd_model, $pd_price, $pd_unit, 
                $pd_status, $pd_user_ins, $pd_user_upd, $ag_id
            );

            if (!$stmt_pd->execute()) {
                throw new Exception('ไม่สามารถบันทึกข้อมูลสินค้า: ' . $stmt_pd->error);
            }
            
            $pd_id = $connect->insert_id; // นำ ID ที่สร้างใหม่ไปใช้ต่อในการบันทึกสต็อก
        }

        // --- 2. ส่วนของการจัดการสต็อก (รองรับหลายคลัง) ---
        if (!empty($wh_ids) && is_array($wh_ids)) {
            $sql_stock = "INSERT INTO product_stocks (pd_id, wh_id, qty, qty_all, qty_min, ps_status) 
                          VALUES (?, ?, ?, ?, ?, ?) 
                          ON DUPLICATE KEY UPDATE 
                            qty = VALUES(qty), 
                            qty_all = VALUES(qty_all), 
                            qty_min = VALUES(qty_min),
                            ps_status = VALUES(ps_status)";
            
            $stmt_st = $connect->prepare($sql_stock);
            
            foreach ($wh_ids as $wh_id) {
                $stmt_st->bind_param("iiddds", $pd_id, $wh_id, $qty, $qty_all, $qty_min, $ps_status);
                if (!$stmt_st->execute()) {
                    throw new Exception('ไม่สามารถบันทึกสต็อกสำหรับคลัง ID ' . $wh_id . ': ' . $stmt_st->error);
                }
            }
        } elseif ($input['wh_id'] ?? null) { 
            $wh_id = $input['wh_id'];
            $sql_stock = "INSERT INTO product_stocks (pd_id, wh_id, qty, qty_all, qty_min, ps_status) 
                        VALUES (?, ?, ?, ?, ?, '0') 
                        ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty), qty_all = VALUES(qty_all), qty_min = VALUES(qty_min)";
            $stmt_st = $connect->prepare($sql_stock);
            $stmt_st->bind_param("iiddd", $pd_id, $wh_id, $qty, $qty_all, $qty_min);
            $stmt_st->execute();
        }

        mysqli_commit($connect);
        echo json_encode([
            'success' => true, 
            'message' => 'บันทึกข้อมูลสินค้าและคลังสินค้าเรียบร้อยแล้ว',
            'pd_id' => $pd_id,
            'affected_warehouses' => count($wh_ids)
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        mysqli_rollback($connect);
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
    
} elseif ($action === 'get_all') {
    $ag_id = $_GET['ag_id'] ?? '';
    $wh_id = $_GET['wh_id'] ?? null;
    $search = $_GET['search'] ?? null;

    // --- ส่วนที่เพิ่มสำหรับ ag-Grid SSRM ---
    $startRow = isset($_GET['startRow']) ? (int)$_GET['startRow'] : 0;
    $endRow = isset($_GET['endRow']) ? (int)$_GET['endRow'] : 100;
    $limit = $endRow - $startRow; // คำนวณ Limit
    
    // รับ JSON String และแปลงเป็น Array
    $sortModel = isset($_GET['sortModel']) ? json_decode($_GET['sortModel'], true) : [];
    $filterModel = isset($_GET['filterModel']) ? json_decode($_GET['filterModel'], true) : [];

    try {
        // 1. สร้างเงื่อนไข WHERE พื้นฐาน
        $whereSql = " WHERE p.ag_id = ? ";
        $params = [$ag_id];
        $types = "s";

        // เงื่อนไขจาก Warehouse
        if (!empty($wh_id) && $wh_id !== 'all') {
            $whereSql .= " AND ps.wh_id = ? ";
            $params[] = $wh_id;
            $types .= "s";
        }

        // เงื่อนไขจาก Search Box
        if (!empty($search)) {
            $whereSql .= " AND (p.pd_gen_code LIKE ? OR p.pd_details_head LIKE ?)";
            $searchTerm = "%$search%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }

        // เงื่อนไขจาก Filter ของ ag-Grid (Column Filters)
        if (!empty($filterModel)) {
            foreach ($filterModel as $colId => $filter) {
                
                // 1. จัดการกรณี Set Filter (เช่น คลังสินค้า wh_name)
                if ($filter['filterType'] === 'set') {
                    $setValues = $filter['values']; // จะเป็น Array เช่น ['คลังยา', 'คลังพัสดุ']
                    if (!empty($setValues)) {
                        // สร้างเครื่องหมาย ? ตามจำนวนค่าที่เลือก
                        $placeholders = implode(',', array_fill(0, count($setValues), '?'));
                        
                        // แปลงชื่อ field ในกรณีที่ colId เป็น wh_name ให้ตรงกับ Table Alias ใน SQL
                        $columnName = ($colId === 'wh_name') ? 'w.wh_name' : $colId;
                        
                        $whereSql .= " AND $columnName IN ($placeholders) ";
                        
                        foreach ($setValues as $val) {
                            $params[] = $val;
                            $types .= "s";
                        }
                    }
                }
                
                // 2. จัดการกรณี Text Filter (เดิมที่คุณมี)
                elseif ($filter['filterType'] === 'text') {
                    $columnName = ($colId === 'wh_name') ? 'w.wh_name' : $colId;
                    $whereSql .= " AND $columnName LIKE ? ";
                    $params[] = "%" . $filter['filter'] . "%";
                    $types .= "s";
                }
            }
        }

        // 2. Query เพื่อนับจำนวนรายการทั้งหมด (Total Count) โดยใช้ WHERE เดียวกัน
        $sqlCount = "SELECT COUNT(*) as total_count 
                     FROM tb_repair_product p
                     INNER JOIN product_stocks ps ON p.pd_id = ps.pd_id
                     INNER JOIN tb_wh_stock w ON ps.wh_id = w.id
                     $whereSql";
        
        $stmtCount = $connect->prepare($sqlCount);
        $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $totalRows = $stmtCount->get_result()->fetch_assoc()['total_count'];

        // 3. จัดการเรื่อง Sorting
        $orderSql = " ORDER BY p.pd_id DESC"; // Default Sort
        if (!empty($sortModel)) {
            $sort = $sortModel[0]; // ag-Grid ส่งมาเป็น array
            $col = $sort['colId'];
            $dir = ($sort['sort'] === 'asc') ? 'ASC' : 'DESC';
            // ป้องกัน SQL Injection สำหรับ Column Name (ควรตรวจสอบชื่อ Column ที่อนุญาต)
            $orderSql = " ORDER BY $col $dir";
        }

        // 4. Query เพื่อดึงข้อมูลจริง พร้อม LIMIT และ OFFSET
        $sql = "SELECT 
                    p.*, ps.qty as pd_qty, ps.qty_all as pd_qty_all, 
                    ps.qty_min as pd_qty_min, ps.wh_id, ps.id as ps_id, 
                    ps.ps_status, w.wh_name 
                FROM tb_repair_product p
                INNER JOIN product_stocks ps ON p.pd_id = ps.pd_id
                INNER JOIN tb_wh_stock w ON ps.wh_id = w.id
                $whereSql $orderSql LIMIT ? OFFSET ?";
        
        // เพิ่ม Parameter สำหรับ LIMIT และ OFFSET
        $params[] = $limit;
        $params[] = $startRow;
        $types .= "ii";

        $stmt = $connect->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        // 5. ส่ง JSON กลับไป (ต้องมี data และ total)
        echo json_encode([
            'success' => true, 
            'data' => $data, 
            'total' => (int)$totalRows // ตัวนี้สำคัญมากสำหรับ Grid และ Badge
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
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

        $sql_get_info = "SELECT pd_id, wh_id, qty FROM product_stocks WHERE id = ?";
        $sql_update = "UPDATE product_stocks SET qty = qty + ? WHERE id = ?";
        $sql_log = "INSERT INTO product_stock_transactions (pd_id, wh_id, trans_type, qty_change, qty_balance, doc_no, trans_date, details, user_id) VALUES (?, ?, 'IN', ?, ?, ?, ?, ?, ?)";

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
                
                $stmt_upd->bind_param("ii", $qty_in, $ps_id);
                if (!$stmt_upd->execute()) {
                    throw new Exception("Error Update Stock ID: $ps_id");
                }

                $new_balance = $current_qty + $qty_in;
                $stmt_log->bind_param("iiiissss", $pd_id, $wh_id, $qty_in, $new_balance, $doc_no, $trans_date, $remark, $user_id);
                
                if (!$stmt_log->execute()) {
                    throw new Exception("Error Insert Log Stock ID: $ps_id -> " . $stmt_log->error);
                }
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

        $sql_get_info = "SELECT pd_id, wh_id, qty FROM product_stocks WHERE id = ?";
        $sql_update = "UPDATE product_stocks SET qty = ? WHERE id = ?";
        // สังเกตว่า doc_no ใส่เป็น NULL ใน SQL เลย ไม่ต้อง bind
        $sql_log = "INSERT INTO product_stock_transactions (pd_id, wh_id, trans_type, qty_change, qty_balance, doc_no, trans_date, details, user_id) VALUES (?, ?, 'ADJUST', ?, ?, ?, ?, ?, ?)";

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

                $diff = $new_qty - $old_qty; 

                // 2. อัปเดตยอดใหม่
                $stmt_upd->bind_param("ii", $new_qty, $ps_id);
                if (!$stmt_upd->execute()) {
                    throw new Exception("Error Update Stock ID: $ps_id");
                }

                // 3. บันทึกประวัติ (Log) - แก้ไขตรงนี้
                // แก้ไข Type string ให้ตรงกับจำนวนตัวแปร (7 ตัว: i, i, i, i, s, s, s)
                $stmt_log->bind_param("iiiissss", $pd_id, $wh_id, $diff, $new_qty, $doc_no, $trans_date, $remark, $user_id);
                
                if (!$stmt_log->execute()) {
                    throw new Exception("Error Insert Log Stock ID: $ps_id -> " . $stmt_log->error);
                }
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
    $sql = "SELECT t.*, p.pd_gen_code, p.pd_details_head, p.pd_unit, w.wh_name 
            FROM product_stock_transactions t
            LEFT JOIN tb_repair_product p ON t.pd_id = p.pd_id
            LEFT JOIN tb_wh_stock w ON t.wh_id = w.id
            WHERE t.trans_type = ? AND p.ag_id = ?";

    // รายการพารามิเตอร์สำหรับ bind_param
    $params = [$type, $ag_id];
    $types = "ss";

    // 2. เพิ่มเงื่อนไขการค้นหาด้วย Keyword (เลขที่เอกสาร, รหัสสินค้า, ชื่อสินค้า, หมายเหตุ)
    if (!empty($search)) {
        $sql .= " AND (t.doc_no LIKE ? OR p.pd_gen_code LIKE ? OR p.pd_details_head LIKE ? OR t.details LIKE ?)";
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
}

if (isset($connect)) mysqli_close($connect);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);