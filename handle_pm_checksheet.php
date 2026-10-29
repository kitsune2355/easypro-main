<?php
//handle_pm_checksheet.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config_ctrl/connect.php';
include_once 'config_ctrl/pm_cs_section.php';   // หมวดของจุดตรวจ 

if (mysqli_connect_errno()) {
    http_response_code(500); 
    echo json_encode(['success' => false, 'error' => 'Database connection failed'], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type"); 

date_default_timezone_set('Asia/Bangkok'); 

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : null); 
$response = ['success' => false, 'data' => []];

if (!$action) {
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุการดำเนินการ (action)'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- Helper Function: สร้าง Folder และ Upload File ---
function uploadFile($file, $base_folder) {
    $date_path = date('Y/m/d');
    $target_dir = $base_folder . '/' . $date_path . '/';
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = uniqid() . '_' . time() . '.' . $ext;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['name' => $file['name'], 'path' => $target_file];
    }
    return false;
}

// --- Helper Function: แปลง Base64 เป็นรูปภาพ ---
// --- Helper Function: แปลง Base64 เป็นรูปภาพ ---
function saveBase64Image($base64_string, $base_folder) {
    // เปลี่ยนจาก (\w+) เป็น ([a-zA-Z0-9\-\+]+) เพื่อรองรับ MIME Type ได้หลากหลายขึ้น
    if (empty($base64_string) || !preg_match('/^data:image\/([a-zA-Z0-9\-\+]+);base64,/', $base64_string, $type)) {
        return null;
    }
    
    $date_path = date('Y/m/d');
    $target_dir = $base_folder . '/' . $date_path . '/';
    
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $data = substr($base64_string, strpos($base64_string, ',') + 1);
    $data = base64_decode($data);
    
    $extension = strtolower($type[1]); 
    // ตัวเสริมความชัวร์: หากเป็น jpeg ให้ใช้ .jpg เพื่อความเป็นระเบียบ
    if ($extension === 'jpeg') {
        $extension = 'jpg';
    }

    $new_filename = uniqid('ill_') . '_' . time() . '.' . $extension;
    $target_file = $target_dir . $new_filename;

    if (file_put_contents($target_file, $data)) {
        return $target_file;
    }
    return null;
}

// Map ตัวอักษรจาก Dropdown เป็น ID
$check_type_map = [
    'ผ่าน / ไม่ผ่าน' => 1,
    'ผ่าน / ไม่ผ่าน / ไม่เกี่ยวข้อง' => 2
];
$photo_req_map = [
    'บังคับ' => 1,
    'ไม่บังคับ' => 2,
    'บังคับเฉพาะกรณีที่ไม่ผ่าน' => 3
];

if ($action === 'save') {
    try {
        // 1. รับค่าตัวแปรหลักจาก POST (กรุณาเช็คชื่อฟิลด์ให้ตรงกับที่คุณส่งมาจาก Frontend)
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $name = isset($_POST['name']) ? mysqli_real_escape_string($connect, $_POST['name']) : '';
        $dept = isset($_POST['ass_type_id']) ? intval($_POST['ass_type_id']) : 0;
        $effective_date_val = isset($_POST['effective_date']) && $_POST['effective_date'] != '' ? "'" . mysqli_real_escape_string($connect, $_POST['effective_date']) . "'" : "NULL";
        $doc_no = isset($_POST['doc_no']) ? mysqli_real_escape_string($connect, $_POST['doc_no']) : '';
        $rev_no = isset($_POST['rev_no']) ? mysqli_real_escape_string($connect, $_POST['rev_no']) : '';
        $estimated_time = isset($_POST['estimated_time']) ? intval($_POST['estimated_time']) : 0;
        $ag_id = isset($_POST['ag_id']) ? intval($_POST['ag_id']) : 0;
        $user_id = isset($_POST['user_id']) ? mysqli_real_escape_string($connect, $_POST['user_id']) : NULL;

        $checksheet_id = 0; // ตัวแปรสำหรับเก็บ ID ของ Checksheet ที่จะใช้บันทึกตารางลูก

        if ($id > 0) {
            // ==========================================
            // โหมด UPDATE (แก้ไขข้อมูลเดิม)
            // ==========================================
            $checksheet_id = $id;

            // อัปเดตข้อมูลในตารางหลัก
            $sql_update = "UPDATE pm_checksheets SET 
                name = '$name', 
                ass_type_id = $dept, 
                effective_date = $effective_date_val, 
                doc_no = '$doc_no', 
                rev_no = '$rev_no', 
                estimated_time = $estimated_time, 
                ag_id = $ag_id,
                updated_by = '$user_id'
                WHERE id = $id AND ag_id = $ag_id";
            if (!mysqli_query($connect, $sql_update)) {
                throw new Exception('Main update error: ' . mysqli_error($connect));
            }

            // ลบ Items และ Spares เก่าของ ID นี้ทิ้งก่อน เพื่อรอรับของใหม่ที่ส่งมา (ป้องกันข้อมูลซ้ำซ้อน)
            mysqli_query($connect, "DELETE FROM pm_checksheet_spares WHERE checksheet_id=$id");
            mysqli_query($connect, "DELETE FROM pm_checksheet_items WHERE checksheet_id=$id");
            
            // จัดการไฟล์แนบเดิม (เช็คว่าผู้ใช้กากบาทลบไฟล์ไหนทิ้งไปบ้าง)
            $existing_files = isset($_POST['existing_files']) ? $_POST['existing_files'] : [];
            if (!empty($existing_files)) {
                $keep_ids = implode(',', array_map('intval', $existing_files));
                
                // ลบไฟล์ทางกายภาพ (ไฟล์ที่ไม่ได้ส่ง ID กลับมาแปลว่าถูกลบ)
                $res_del = mysqli_query($connect, "SELECT file_path FROM pm_checksheet_files WHERE checksheet_id=$id AND id NOT IN ($keep_ids)");
                while($row_del = mysqli_fetch_assoc($res_del)) {
                   // if(file_exists($row_del['file_path'])) { @unlink($row_del['file_path']); }
                }
                // ลบข้อมูลไฟล์ออกจาก Database
                mysqli_query($connect, "DELETE FROM pm_checksheet_files WHERE checksheet_id=$id AND id NOT IN ($keep_ids)");
            } else {
                // กรณีลบไฟล์เก่าทิ้งทั้งหมด ไม่มีส่ง ID กลับมาเลย
                $res_del = mysqli_query($connect, "SELECT file_path FROM pm_checksheet_files WHERE checksheet_id=$id");
                while($row_del = mysqli_fetch_assoc($res_del)) {
                   // if(file_exists($row_del['file_path'])) { @unlink($row_del['file_path']); }
                }
                mysqli_query($connect, "DELETE FROM pm_checksheet_files WHERE checksheet_id=$id");
            }

        } else {
            // ==========================================
            // โหมด INSERT (สร้างเช็คชีตใหม่ หรือ Copy)
            // ==========================================
            $sql_insert = "INSERT INTO pm_checksheets 
                (name, ass_type_id, effective_date, doc_no, rev_no, estimated_time, ag_id, created_by, updated_by) 
                VALUES 
                ('$name', $dept, $effective_date_val, '$doc_no', '$rev_no', $estimated_time, $ag_id, '$user_id', '$user_id')";
                         
            if (mysqli_query($connect, $sql_insert)) {
                $checksheet_id = mysqli_insert_id($connect); 
                
                // --- ส่วนที่เพิ่ม: ดึงไฟล์เดิมมาคัดลอก (กรณี Copy) ---
                $existing_files = isset($_POST['existing_files']) ? $_POST['existing_files'] : [];
                if (!empty($existing_files)) {
                    $keep_ids = implode(',', array_map('intval', $existing_files));
                    $res_copy = mysqli_query($connect, "SELECT * FROM pm_checksheet_files WHERE id IN ($keep_ids)");
                    
                    while ($row_file = mysqli_fetch_assoc($res_copy)) {
                        $old_path = $row_file['file_path'];
                        if (file_exists($old_path)) {
                            // สร้างชื่อไฟล์ใหม่เพื่อแยกขาดจากไฟล์ต้นฉบับ
                            $ext = pathinfo($old_path, PATHINFO_EXTENSION);
                            $new_filename = uniqid('copy_') . '_' . time() . '.' . $ext;
                            $new_path = dirname($old_path) . '/' . $new_filename;
                            
                            // ทำสำเนาไฟล์บน Server
                            if (copy($old_path, $new_path)) {
                                $file_type = mysqli_real_escape_string($connect, $row_file['file_type']);
                                $file_name = mysqli_real_escape_string($connect, $row_file['file_name']);
                                $new_path_db = mysqli_real_escape_string($connect, $new_path);
                                
                                $sql_insert_file = "INSERT INTO pm_checksheet_files (checksheet_id, file_path, file_type, file_name) 
                                                    VALUES ($checksheet_id, '$new_path_db', '$file_type', '$file_name')";
                                mysqli_query($connect, $sql_insert_file);
                            }
                        }
                    }
                }
            } else {
                throw new Exception('Main insert error: ' . mysqli_error($connect));
            }
        }

        // ==========================================
        // อัปโหลดไฟล์ใหม่ (จะใช้ $checksheet_id เสมอ ไม่ว่าสร้างใหม่หรือแก้ไข)
        // ==========================================
        
        // 1. จัดการไฟล์ภาพจุดตรวจสอบ (checksheet_images)
        if (isset($_FILES['checksheet_images']) && !empty($_FILES['checksheet_images']['name'][0])) {
            foreach ($_FILES['checksheet_images']['name'] as $key => $filename) {
                if ($_FILES['checksheet_images']['error'][$key] == 0) {
                    $tmp_name = $_FILES['checksheet_images']['tmp_name'][$key];
                    // แก้ไข: รับค่าเป็น Array แล้วดึงเฉพาะ ['path'] มาใช้
                    $upload_res = uploadFile(['name' => $filename, 'tmp_name' => $tmp_name], 'uploads/images');
                    if ($upload_res) {
                        $file_path_db = mysqli_real_escape_string($connect, $upload_res['path']);
                        $file_name_db = mysqli_real_escape_string($connect, $filename);
                        
                        // แก้ไข: เปลี่ยนจาก original_name เป็น file_name
                        $sql_file = "INSERT INTO pm_checksheet_files (checksheet_id, file_path, file_type, file_name) 
                                     VALUES ($checksheet_id, '$file_path_db', 'image', '$file_name_db')";
                        mysqli_query($connect, $sql_file);
                    }
                }
            }
        }

        // 2. จัดการไฟล์คู่มือ / WI (work_instructions)
        if (isset($_FILES['work_instructions']) && !empty($_FILES['work_instructions']['name'][0])) {
            foreach ($_FILES['work_instructions']['name'] as $key => $filename) {
                if ($_FILES['work_instructions']['error'][$key] == 0) {
                    $tmp_name = $_FILES['work_instructions']['tmp_name'][$key];
                    
                    // ตรวจสอบว่า uploadFile คืนค่ากลับมาจริงๆ
                    $upload_res = uploadFile(['name' => $filename, 'tmp_name' => $tmp_name], 'uploads/files');
                    
                    if ($upload_res) {
                        $file_path_db = mysqli_real_escape_string($connect, $upload_res['path']);
                        $file_name_db = mysqli_real_escape_string($connect, $filename);
                        
                        // แนะนำ: ใส่ if check เพื่อดูว่า query สำเร็จไหม
                        $sql_file = "INSERT INTO pm_checksheet_files (checksheet_id, file_path, file_type, file_name) 
                                    VALUES ($checksheet_id, '$file_path_db', 'document', '$file_name_db')";
                        
                        if (!mysqli_query($connect, $sql_file)) {
                            // ถ้าพังตรงนี้ จะกระโดดไปที่ catch และแจ้ง Error กลับไปที่หน้าจอ
                            throw new Exception("ไม่สามารถบันทึกข้อมูลไฟล์ลงฐานข้อมูลได้: " . mysqli_error($connect));
                        }
                    } else {
                        throw new Exception("ไม่สามารถย้ายไฟล์ $filename ไปยังโฟลเดอร์ปลายทางได้ (เช็ค Permission)");
                    }
                } else {
                    // เช็ค Error Code ของ PHP Upload (เช่นไฟล์ใหญ่เกินไป)
                    $upload_error_code = $_FILES['work_instructions']['error'][$key];
                    throw new Exception("เกิดข้อผิดพลาดในการอัปโหลดไฟล์ (Code: $upload_error_code)");
                }
            }
        }

        // ==========================================
        // บันทึกข้อมูลตารางลูก (Spares และ Items)
        // ==========================================
        
        // --- ส่วนที่ 1: Spares ---
        // รับค่า key ให้ตรงกับที่ FormData ส่งมา (spare_parts)
        if (isset($_POST['spare_parts'])) {
            $spares = is_string($_POST['spare_parts']) ? json_decode($_POST['spare_parts'], true) : $_POST['spare_parts'];
            if (is_array($spares)) {
                foreach ($spares as $spare) {
                    // ข้ามหากแถวนั้นไม่มีการกรอกชื่ออะไหล่
                    if (empty($spare[0])) continue; 

                    // ข้อมูลจาก Handsontable จะมาเป็น Index Array [0 => ชื่อ, 1 => จำนวน]
                    $part_name = mysqli_real_escape_string($connect, $spare[0]); 
                    $qty = intval($spare[1] ?? 0); 
                    
                    // บันทึกลงตาราง pm_checksheet_spares
                    $sql_spare = "INSERT INTO pm_checksheet_spares (checksheet_id, part_name, quantity) 
                                  VALUES ($checksheet_id, '$part_name', $qty)";
                    
                    // หมายเหตุ: หาก DB ของคุณใช้ column ชื่อ 'spare_name' ให้เปลี่ยน 'part_name' กลับเป็น 'spare_name'
                    if (!mysqli_query($connect, $sql_spare)) {
                        throw new Exception('Spares insert error: ' . mysqli_error($connect));
                    }
                }
            }
        }

        // --- ส่วนที่ 2: Items ---
        // รับค่า key ให้ตรงกับที่ FormData ส่งมา (checksheet_items)
        if (!empty($_POST['checksheet_items'])) {
            // เช็คว่าข้อมูลมาเป็น JSON String หรือ Array
            $items = is_string($_POST['checksheet_items']) ? json_decode($_POST['checksheet_items'], true) : $_POST['checksheet_items'];
            
            if (is_array($items)) {
                $sort_order = 1;
                foreach ($items as $item) {
                    // ข้ามถ้าไม่ได้กรอกจุดตรวจสอบ (Handsontable มักจะมีแถวว่างเผื่อไว้)
                    if (empty($item[0])) continue;

                    $check_point     = mysqli_real_escape_string($connect, $item[0]);
                    $standard_text   = mysqli_real_escape_string($connect, $item[1] ?? '');
                    $method_text     = mysqli_real_escape_string($connect, $item[2] ?? '');
                    $action_abnormal = mysqli_real_escape_string($connect, $item[3] ?? '');
                    
                    // แปลง Dropdown เป็น ID (คุณต้องมี Array $check_type_map เตรียมไว้ก่อนหน้า)
                    $check_type_id     = isset($check_type_map[$item[4]]) ? $check_type_map[$item[4]] : 1;
                    $photo_required_id = isset($photo_req_map[$item[6]]) ? $photo_req_map[$item[6]] : 2;
                    
                    // จัดการภาพประกอบ (Illustration) ในตาราง
                    $illustration_path = '';
                    if (!empty($item[5]) && strpos($item[5], 'data:image') === 0) {
                        // รูปอัปโหลดใหม่ (Base64)
                        $saved_path = saveBase64Image($item[5], 'uploads/illustrations');
                        if ($saved_path) $illustration_path = $saved_path;
                    } else if (!empty($item[5])) {
                        $old_ill_path = $item[5];
                        // $id == 0 หมายถึงเป็นการสร้างใหม่ / Copy
                        if ($id == 0 && file_exists($old_ill_path)) {
                            $ext = pathinfo($old_ill_path, PATHINFO_EXTENSION);
                            $new_filename = uniqid('ill_copy_') . '_' . time() . '.' . ($ext ? $ext : 'jpg');
                            $new_path = dirname($old_ill_path) . '/' . $new_filename;
                            
                            // คัดลอกไฟล์รูปประกอบให้เป็นไฟล์ใหม่
                            if (copy($old_ill_path, $new_path)) {
                                $illustration_path = mysqli_real_escape_string($connect, $new_path);
                            } else {
                                $illustration_path = mysqli_real_escape_string($connect, $old_ill_path); // Fallback กลับไปใช้ path เดิมถ้า copy พลาด
                            }
                        } else {
                            // กรณีโหมด UPDATE ปกติ ให้ใช้ Path เดิมไปเลย
                            $illustration_path = mysqli_real_escape_string($connect, $old_ill_path);
                        }
                    }

                    $value_name     = mysqli_real_escape_string($connect, $item[7] ?? '');
                    $unit           = mysqli_real_escape_string($connect, $item[8] ?? '');
                    $expected_value = mysqli_real_escape_string($connect, $item[9] ?? '');
                    // หมวด (ช่องที่ 11 ต่อท้าย — ลำดับช่อง 0–9 เดิมไม่เปลี่ยน) · ยังไม่รัน SQL => ไม่บันทึกหมวด
                    $sec = pmcs_has_section($connect);
                    $section_name = $sec ? mysqli_real_escape_string($connect, pmcs_section_value($item[10] ?? '')) : '';

                    $sql_item = "INSERT INTO pm_checksheet_items 
                                 (checksheet_id, " . ($sec ? "section_name, " : "") . "check_point, standard_text, method_text, action_abnormal, check_type_id, illustration_path, photo_required_id, value_name, unit, expected_value, sort_order) 
                                 VALUES 
                                 ($checksheet_id, " . ($sec ? ($section_name === '' ? "NULL, " : "'$section_name', ") : "") . "'$check_point', '$standard_text', '$method_text', '$action_abnormal', $check_type_id, '$illustration_path', $photo_required_id, '$value_name', '$unit', '$expected_value', $sort_order)";
                    
                    if (!mysqli_query($connect, $sql_item)) {
                        throw new Exception('เกิดข้อผิดพลาดในการบันทึกจุดตรวจสอบ: ' . mysqli_error($connect));
                    }
                    $sort_order++;
                }
            }
        }

        // ==========================================
        // --- ส่วนที่ 3: Files (ไฟล์แนบอ้างอิง) ---
        // ==========================================
        // เช็คชื่อตัวแปรที่รับมาให้ตรงกับ Frontend ('checksheet_files' หรือ 'files')
        if (isset($_FILES['checksheet_files']) && !empty($_FILES['checksheet_files']['name'][0])) {
            foreach ($_FILES['checksheet_files']['name'] as $key => $filename) {
                if ($_FILES['checksheet_files']['error'][$key] == 0) {
                    $tmp_name = $_FILES['checksheet_files']['tmp_name'][$key];
                    
                    // ตรวจสอบประเภทไฟล์จาก file_types[] ที่ส่งมาพร้อมกัน (ถ้ามี)
                    $type = (isset($_POST['file_types'][$key])) ? $_POST['file_types'][$key] : 'document';
                    $folder = ($type === 'image') ? 'uploads/images' : 'uploads/files';

                    $upload_res = uploadFile(['name' => $filename, 'tmp_name' => $tmp_name], $folder);
                    if ($upload_res) {
                        $file_path_db = mysqli_real_escape_string($connect, $upload_res['path']);
                        $file_name_db = mysqli_real_escape_string($connect, $filename);
                        $sql_file = "INSERT INTO pm_checksheet_files (checksheet_id, file_path, file_type, file_name) 
                                    VALUES ($checksheet_id, '$file_path_db', '$type', '$file_name_db')";
                        mysqli_query($connect, $sql_file);
                    }
                }
            }
        }

        // หากทำงานมาถึงตรงนี้โดยไม่มี Error (Throw) แปลว่าสำเร็จ
        $response = ['success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว'];

    } catch (Exception $e) {
        $response = ['success' => false, 'error' => $e->getMessage()];
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

elseif ($action === 'get_all') {
    // 1. รับพารามิเตอร์ Pagination
    $ag_id = isset($_GET['ag_id']) ? intval($_GET['ag_id']) : 0;
    $startRow = isset($_GET['startRow']) ? intval($_GET['startRow']) : 0;
    $endRow = isset($_GET['endRow']) ? intval($_GET['endRow']) : 10;
    $limit = $endRow - $startRow;
    
    // 2. รับ Filter และ Sort Model จาก AG Grid
    $filterModel = isset($_GET['filterModel']) ? json_decode($_GET['filterModel'], true) : [];
    $sortModel = isset($_GET['sortModel']) ? json_decode($_GET['sortModel'], true) : [];
    
    // 3. สร้างเงื่อนไข WHERE สำหรับการ Filter
    $where_clauses = ["1=1"];
    
    if ($ag_id > 0) { 
        // ถ้ามีการส่ง ag_id มา ให้กรองตาม ag_id ด้วย
        $where_clauses[] = "c.ag_id = $ag_id";
    }

    if (!empty($filterModel)) {
        foreach ($filterModel as $column => $details) {
            $filterValue = mysqli_real_escape_string($connect, $details['filter']);
            // แมพชื่อฟิลด์จาก Grid ให้ตรงกับคอลัมน์ใน DB
            if ($column === 'name') $where_clauses[] = "c.name LIKE '%$filterValue%'";
            if ($column === 'docNo') $where_clauses[] = "c.doc_no LIKE '%$filterValue%'";
            if ($column === 'revNo') $where_clauses[] = "c.rev_no LIKE '%$filterValue%'";
            if ($column === 'category') $where_clauses[] = "t.TGroupName LIKE '%$filterValue%'";
        }
    }
    $where_sql = implode(" AND ", $where_clauses);

    // 4. สร้างส่วนการ Order By (Sorting)
    $order_by = "c.id DESC"; // Default
    if (!empty($sortModel)) {
        $sort_parts = [];
        foreach ($sortModel as $sort) {
            $colId = $sort['colId'];
            $sort_dir = ($sort['sort'] === 'asc') ? 'ASC' : 'DESC';
            
            // แมพ colId เป็นชื่อฟิลด์จริงใน DB
            if ($colId === 'name') $sort_parts[] = "c.name $sort_dir";
            elseif ($colId === 'docNo') $sort_parts[] = "c.doc_no $sort_dir";
            elseif ($colId === 'category') $sort_parts[] = "category_name $sort_dir";
            elseif ($colId === 'effectiveDate') $sort_parts[] = "c.effective_date $sort_dir";
        }
        if (!empty($sort_parts)) $order_by = implode(", ", $sort_parts);
    }

    // 5. นับจำนวนแถวทั้งหมด (แบบที่กรองแล้ว) เพื่อทำ Pagination
    $sql_count = "SELECT COUNT(*) as total FROM pm_checksheets c 
                  LEFT JOIN tb_asset_group t ON c.ass_type_id = t.GroupId 
                  WHERE $where_sql";
    $res_count = mysqli_query($connect, $sql_count);
    $total_rows = mysqli_fetch_assoc($res_count)['total'];

    // 6. ดึงข้อมูลจริงตาม LIMIT และ OFFSET
    $sql = "SELECT c.*, t.TGroupName as category_name,
            (SELECT COUNT(*) FROM pm_checksheet_items WHERE checksheet_id = c.id) as item_count,
            (SELECT COUNT(*) FROM pm_checksheet_spares WHERE checksheet_id = c.id) as spare_count
            FROM pm_checksheets c 
            LEFT JOIN tb_asset_group t ON c.ass_type_id = t.GroupId
            WHERE $where_sql
            ORDER BY $order_by
            LIMIT $limit OFFSET $startRow"; 
            
    $result = mysqli_query($connect, $sql);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'docNo' => $row['doc_no'],
            'revNo' => $row['rev_no'],
            'effectiveDate' => $row['effective_date'],
            'category' => $row['category_name'] ?? 'ไม่ระบุ',
            'itemCount' => (int)$row['item_count'],
            'estimatedTime' => (int)$row['estimated_time'],
            'sparePartsCount' => (int)$row['spare_count']
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $data,
        'totalCount' => (int)$total_rows
    ]);
    exit;
}

// รายการเช็คชีตของแท็บ "สร้างเช็คชีต" (pm_checksheet.php): ค้นหา/กรองประเภท/แบ่งหน้าที่เซิร์ฟเวอร์ + จำนวนแผนที่ใช้
//  (get_all ด้านบนยังใช้โดย pm_dashboard/pm_postpone/pm_plan/pm_feedback — ห้ามเปลี่ยนรูปแบบผลลัพธ์)
elseif ($action === 'list') {
    @session_start();
    $ag_id = intval($_SESSION['sess_user_agency'] ?? 0);
    session_write_close();
    if ($ag_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบหน่วยงาน กรุณาเข้าสู่ระบบใหม่'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $startRow = max(0, intval($_GET['startRow'] ?? 0));
    $limit    = min(200, max(1, intval($_GET['endRow'] ?? 50) - $startRow));
    $q        = trim($_GET['q'] ?? '');
    $type_id  = intval($_GET['type_id'] ?? 0);

    $where = "c.ag_id = ?";
    $types = 'i';
    $args  = [$ag_id];
    if ($q !== '') {
        $where .= " AND (c.name LIKE ? OR c.doc_no LIKE ?)";
        $like = '%' . $q . '%';
        $types .= 'ss';
        array_push($args, $like, $like);
    }
    if ($type_id > 0) {
        $where .= " AND c.ass_type_id = ?";
        $types .= 'i';
        $args[] = $type_id;
    }

    // เรียงลำดับตามคอลัมน์ที่อนุญาตเท่านั้น
    $sort_map = ['name' => 'c.name', 'category' => 'category_name', 'effectiveDate' => 'c.effective_date', 'updatedAt' => 'c.updated_at', 'itemCount' => 'item_count'];
    $order_by = 'c.updated_at DESC, c.id DESC';
    $sortModel = json_decode($_GET['sortModel'] ?? '[]', true);
    if (is_array($sortModel) && !empty($sortModel[0]['colId']) && isset($sort_map[$sortModel[0]['colId']])) {
        $order_by = $sort_map[$sortModel[0]['colId']] . (($sortModel[0]['sort'] ?? '') === 'asc' ? ' ASC' : ' DESC') . ', c.id DESC';
    }

    $stmt = mysqli_prepare($connect, "SELECT COUNT(*) FROM pm_checksheets c WHERE $where");
    mysqli_stmt_bind_param($stmt, $types, ...$args);
    mysqli_stmt_execute($stmt);
    $total = (int)mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];
    mysqli_stmt_close($stmt);

    $sql = "SELECT c.id, c.name, c.doc_no, c.rev_no, c.effective_date, c.estimated_time, c.ass_type_id, c.updated_at,
                   t.TGroupName AS category_name,
                   (SELECT COUNT(*) FROM pm_checksheet_items WHERE checksheet_id = c.id) AS item_count,
                   (SELECT COUNT(*) FROM pm_checksheet_spares WHERE checksheet_id = c.id) AS spare_count,
                   (SELECT COUNT(*) FROM pm_checksheet_files WHERE checksheet_id = c.id) AS file_count,
                   (SELECT COUNT(*) FROM pm_checksheet_files WHERE checksheet_id = c.id AND file_type = 'image') AS image_count
            FROM pm_checksheets c
            LEFT JOIN tb_asset_group t ON c.ass_type_id = t.GroupId
            WHERE $where
            ORDER BY $order_by
            LIMIT $limit OFFSET $startRow";
    $stmt = mysqli_prepare($connect, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$args);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $data[(int)$row['id']] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'docNo' => $row['doc_no'],
            'revNo' => $row['rev_no'],
            'effectiveDate' => $row['effective_date'],
            'typeId' => (int)$row['ass_type_id'],
            'category' => $row['category_name'],
            'itemCount' => (int)$row['item_count'],
            'sparePartsCount' => (int)$row['spare_count'],
            'fileCount' => (int)$row['file_count'],
            'imageCount' => (int)$row['image_count'],
            'docCount' => (int)$row['file_count'] - (int)$row['image_count'],
            'estimatedTime' => (int)$row['estimated_time'],
            'updatedAt' => $row['updated_at'],
            'planCount' => 0
        ];
    }
    mysqli_stmt_close($stmt);

    // จำนวนแผนที่ใช้เช็คชีต (เฉพาะแถวในหน้านี้ — query เดียว)
    if ($data) {
        $ids = array_map('strval', array_keys($data));
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = mysqli_prepare($connect, "SELECT checksheet_id, COUNT(*) AS n FROM pm_plans WHERE ag_id = ? AND checksheet_id IN ($ph) GROUP BY checksheet_id");
        $ag_str = (string)$ag_id;
        mysqli_stmt_bind_param($stmt, str_repeat('s', count($ids) + 1), $ag_str, ...$ids);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            if (isset($data[(int)$row['checksheet_id']])) $data[(int)$row['checksheet_id']]['planCount'] = (int)$row['n'];
        }
        mysqli_stmt_close($stmt);
    }

    echo json_encode(['success' => true, 'data' => array_values($data), 'totalCount' => $total], JSON_UNESCAPED_UNICODE);
    exit;
}

elseif ($action === 'get_by_id') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        $response = ['success' => false, 'error' => 'ไม่ระบุ ID หรือ ID ไม่ถูกต้อง'];
    } else {
        // ใช้ Prepared Statement เพื่อความปลอดภัยและป้องกันข้อผิดพลาด
        
        // 1. ดึงข้อมูลหลัก
        $stmt_main = mysqli_prepare($connect, "SELECT * FROM pm_checksheets WHERE id = ?");
        mysqli_stmt_bind_param($stmt_main, "i", $id);
        mysqli_stmt_execute($stmt_main);
        $res_main = mysqli_stmt_get_result($stmt_main);
        $main = mysqli_fetch_assoc($res_main);
        mysqli_stmt_close($stmt_main);

        if (!$main) {
            $response = ['success' => false, 'error' => 'ไม่พบข้อมูลที่ระบุ'];
        } else {
            // เตรียม Array ปลายทาง
            $data = [
                'main' => $main,
                'spares' => [],
                'items' => [],
                'images' => [],
                'instructions' => []
            ];

            // 2. ดึงข้อมูลอะไหล่
            $stmt_spares = mysqli_prepare($connect, "SELECT * FROM pm_checksheet_spares WHERE checksheet_id = ?");
            mysqli_stmt_bind_param($stmt_spares, "i", $id);
            mysqli_stmt_execute($stmt_spares);
            $res_spares = mysqli_stmt_get_result($stmt_spares);
            while($row = mysqli_fetch_assoc($res_spares)) { 
                $data['spares'][] = $row; 
            }
            mysqli_stmt_close($stmt_spares);

            // 3. ดึงข้อมูลจุดตรวจสอบ
            $stmt_items = mysqli_prepare($connect, "SELECT * FROM pm_checksheet_items WHERE checksheet_id = ? ORDER BY sort_order ASC");
            mysqli_stmt_bind_param($stmt_items, "i", $id);
            mysqli_stmt_execute($stmt_items);
            $res_items = mysqli_stmt_get_result($stmt_items);
            while($row = mysqli_fetch_assoc($res_items)) { 
                $data['items'][] = $row; 
            }
            mysqli_stmt_close($stmt_items);

            // 4. ดึงข้อมูลไฟล์แนบ
            $stmt_files = mysqli_prepare($connect, "SELECT * FROM pm_checksheet_files WHERE checksheet_id = ?");
            mysqli_stmt_bind_param($stmt_files, "i", $id);
            mysqli_stmt_execute($stmt_files);
            $res_files = mysqli_stmt_get_result($stmt_files);
            while($row = mysqli_fetch_assoc($res_files)) {
                if ($row['file_type'] === 'image') {
                    $data['images'][] = $row;
                } else {
                    $data['instructions'][] = $row;
                }
            }
            mysqli_stmt_close($stmt_files);

            $response = [
                'success' => true, 
                'data' => $data
            ];
        }
    }
}

elseif ($action === 'delete') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$id) {
        $response = ['success' => false, 'error' => 'ไม่ระบุ ID'];
    } else {
        // (Optional) ลบไฟล์จริงๆ ออกจาก Server ก่อนลบข้อมูลใน DB
        $res_files = mysqli_query($connect, "SELECT file_path FROM pm_checksheet_files WHERE checksheet_id = $id");
        while($row = mysqli_fetch_assoc($res_files)) {
            if (file_exists($row['file_path'])) { @unlink($row['file_path']); }
        }
        $res_ill = mysqli_query($connect, "SELECT illustration_path FROM pm_checksheet_items WHERE checksheet_id = $id AND illustration_path IS NOT NULL AND illustration_path != ''");
        while($row = mysqli_fetch_assoc($res_ill)) {
            if (file_exists($row['illustration_path'])) { @unlink($row['illustration_path']); }
        }

        // ลบข้อมูลหลัก
        $sql = "DELETE FROM pm_checksheets WHERE id = $id";
        if (mysqli_query($connect, $sql)) {
            $response = ['success' => true, 'message' => 'ลบข้อมูลเรียบร้อยแล้ว'];
        } else {
            $response = ['success' => false, 'error' => mysqli_error($connect)];
        }
    }
}

// ด้านล่างปล่อยไว้เหมือนเดิม
if (isset($connect)) mysqli_close($connect);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>