<?php
//handle_pm_worksheet.php
@session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config_ctrl/connect.php'; 

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

if (!$action) {
    echo json_encode(['success' => false, 'error' => 'ไม่ระบุการดำเนินการ (action)'], JSON_UNESCAPED_UNICODE);
    exit;
}

function pmEscapeLike($value) {
    return str_replace(
        ['\\', '%', '_'],
        ['\\\\', '\\%', '\\_'],
        (string)$value
    );
}

function generatePmWorkDocNo($connect, $base_doc_no, $ag_id = '') {
    $base_doc_no = trim((string)$base_doc_no);

    if ($base_doc_no === '') {
        return null;
    }

    // ปี พ.ศ. 2 หลัก + เดือนปัจจุบัน เช่น 6906
    $year_th_short = substr((string)(date('Y') + 543), -2);
    $month = date('m');
    $period = $year_th_short . $month;

    // รูปแบบ: PM-AC-6906-0001
    $prefix = $base_doc_no . '-' . $period . '-';

    // กันเลขชนกรณีมีคนกดบันทึกพร้อมกัน
    $lock_name = 'pm_doc_no_' . md5($ag_id . '|' . $prefix);
    $got_lock = 0;

    $stmt_lock = $connect->prepare("SELECT GET_LOCK(?, 10)");

    if (!$stmt_lock) {
        throw new Exception('Prepare GET_LOCK failed: ' . $connect->error);
    }

    $stmt_lock->bind_param('s', $lock_name);
    $stmt_lock->execute();
    $stmt_lock->bind_result($got_lock);
    $stmt_lock->fetch();
    $stmt_lock->close();

    if ((int)$got_lock !== 1) {
        throw new Exception('ไม่สามารถล็อกการรันเลขเอกสารได้ กรุณาลองใหม่อีกครั้ง');
    }

    try {
        $like_pattern = pmEscapeLike($prefix) . '%';

        if ($ag_id !== '') {
            $sql_latest = "
                SELECT doc_no
                FROM pm_work_records
                WHERE ag_id = ?
                  AND doc_no LIKE ? ESCAPE '\\\\'
                ORDER BY doc_no DESC
                LIMIT 1
            ";

            $stmt = $connect->prepare($sql_latest);

            if (!$stmt) {
                throw new Exception('Prepare latest doc_no failed: ' . $connect->error);
            }

            $stmt->bind_param('ss', $ag_id, $like_pattern);

        } else {
            $sql_latest = "
                SELECT doc_no
                FROM pm_work_records
                WHERE doc_no LIKE ? ESCAPE '\\\\'
                ORDER BY doc_no DESC
                LIMIT 1
            ";

            $stmt = $connect->prepare($sql_latest);

            if (!$stmt) {
                throw new Exception('Prepare latest doc_no failed: ' . $connect->error);
            }

            $stmt->bind_param('s', $like_pattern);
        }

        $stmt->execute();
        $stmt->bind_result($latest_doc_no);
        $stmt->fetch();
        $stmt->close();

        $next_number = 1;

        if (!empty($latest_doc_no) && strpos($latest_doc_no, $prefix) === 0) {
            $latest_running = substr($latest_doc_no, strlen($prefix));

            if (is_numeric($latest_running)) {
                $next_number = intval($latest_running) + 1;
            }
        }

        $running = str_pad($next_number, 4, '0', STR_PAD_LEFT);

        return $prefix . $running;

    } finally {
        $stmt_unlock = $connect->prepare("SELECT RELEASE_LOCK(?)");

        if ($stmt_unlock) {
            $stmt_unlock->bind_param('s', $lock_name);
            $stmt_unlock->execute();
            $stmt_unlock->close();
        }
    }
}


function saveBase64ImageToPath($base64_string, $base_folder, $prefix = 'img') {
    if (
        empty($base64_string) ||
        !preg_match('/^data:image\/([a-zA-Z0-9\-\+]+);base64,/', $base64_string, $type)
    ) {
        return null;
    }

    $date_path = date('Y/m/d');
    $target_dir = rtrim($base_folder, '/') . '/' . $date_path . '/';

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $data = substr($base64_string, strpos($base64_string, ',') + 1);
    $data = base64_decode($data);

    if ($data === false) {
        return null;
    }

    $extension = strtolower($type[1]);
    if ($extension === 'jpeg') {
        $extension = 'jpg';
    }

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($extension, $allowed_extensions)) {
        return null;
    }

    $new_filename = uniqid($prefix . '_', true) . '_' . time() . '.' . $extension;
    $target_file = $target_dir . $new_filename;

    if (file_put_contents($target_file, $data)) {
        return $target_file;
    }

    return null;
}

function dbEsc($connect, $value) {
    if ($value === null) {
        return '';
    }
    return mysqli_real_escape_string($connect, (string)$value);
}

// --- Helper Function: แปลง Base64 เป็นรูปภาพผลการตรวจ ---
function saveResultImage($base64_string, $base_folder) {
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
    if ($extension === 'jpeg') { $extension = 'jpg'; }

    $new_filename = uniqid('res_') . '_' . time() . '.' . $extension;
    $target_file = $target_dir . $new_filename;

    if (file_put_contents($target_file, $data)) {
        return $new_filename;
    }
    return null;
}

// =========================================
// บันทึกผลการปฏิบัติงาน 1 ใบ (ใช้ทั้งบันทึกทีละใบ และบันทึกพร้อมกันหลายเครื่อง)
//  - ผู้เรียกเปิด/ปิด transaction เอง, ผิดพลาด => throw Exception
//  - $photos = [checksheet_item_id => base64], $signature_path = ไฟล์ลายเซ็นที่บันทึกไว้แล้ว (ถ้ามี)
// =========================================
function pmws_save_event($connect, $event_id, $inspector_name, $inspector_users, $inspector_signature, $remarks, $created_by, $submitted_items, $parts, $photos, $signature_path = null) {
    $event_id = intval($event_id);
    // 1. กันบันทึกซ้ำ
    $sql_duplicate = "
        SELECT id
        FROM pm_work_records
        WHERE plan_event_id = $event_id
        LIMIT 1
    ";
    $res_duplicate = mysqli_query($connect, $sql_duplicate);

    if (!$res_duplicate) {
        throw new Exception('SQL Error (Check Duplicate): ' . mysqli_error($connect));
    }

    if (mysqli_num_rows($res_duplicate) > 0) {
        throw new Exception('แผนงานนี้ถูกบันทึกผลการปฏิบัติงานแล้ว');
    }

    // 2. ดึง snapshot หัวใบงานจาก master
    $sql_info = "
        SELECT 
            e.id AS plan_event_id,
            e.ag_id,
            e.plan_id,
            e.machine_id,
            e.mac_type_id,
            e.checksheet_id,
            e.event_date AS plan_date,

            c.name AS checksheet_name,
            c.doc_no,
            c.rev_no,

            a.asset_name AS machine_name,
            a.ass_code AS machine_code,
            a.asset_sn AS machine_sn,

            g.TGroupName AS machine_type,

            CONCAT_WS(' / ', ar.area_name, ac.ac_name, rm.ar_name) AS location

        FROM pm_plan_events e
        LEFT JOIN pm_checksheets c ON e.checksheet_id = c.id
        LEFT JOIN tb_ass_list a ON e.machine_id = a.ass_id
        LEFT JOIN tb_asset_group g ON a.asset_type = g.GroupId
        LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
        LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
        LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
        WHERE e.id = $event_id
        LIMIT 1
    ";

    $res_info = mysqli_query($connect, $sql_info);

    if (!$res_info) {
        throw new Exception('SQL Error (Info Snapshot): ' . mysqli_error($connect));
    }

    $info = mysqli_fetch_assoc($res_info);

    if (!$info) {
        throw new Exception('ไม่พบข้อมูลแผนงาน');
    }

    $checksheet_id = intval($info['checksheet_id']);

    if ($checksheet_id <= 0) {
        throw new Exception('ไม่พบรหัสเช็คชีตของแผนงานนี้');
    }

    // 3. บันทึกลายเซ็นผู้ตรวจสอบ
    // บันทึกหลายใบพร้อมกัน: ใช้ไฟล์ลายเซ็นที่บันทึกไว้แล้วร่วมกัน
    $inspector_signature_path = $signature_path ? $signature_path : saveBase64ImageToPath(
        $inspector_signature,
        'uploads/pm_work_records/signatures',
        'inspector_sign'
    );

    if (!$inspector_signature_path) {
        throw new Exception('ไม่สามารถบันทึกลายเซ็นผู้ตรวจสอบได้');
    }

    // 4. insert pm_work_records
    $ag_id = dbEsc($connect, $info['ag_id']);
    $plan_id_real = intval($info['plan_id']);
    $machine_id = dbEsc($connect, $info['machine_id']);
    $mac_type_id = dbEsc($connect, $info['mac_type_id']);

    $checksheet_name_raw = isset($info['checksheet_name']) ? trim((string)$info['checksheet_name']) : '';

		$generated_doc_no = generatePmWorkDocNo(
			$connect,
			$checksheet_name_raw,
			(string)$info['ag_id']
		);
		
		if ($generated_doc_no === null || $generated_doc_no === '') {
			throw new Exception('ไม่สามารถสร้างเลขที่เอกสาร PM ได้ เนื่องจากไม่พบชื่อเช็คชีต');
		}
		
		$base_doc_no_raw = isset($info['doc_no']) ? trim((string)$info['doc_no']) : '';
		$checksheet_name_raw = isset($info['checksheet_name']) ? trim((string)$info['checksheet_name']) : '';
		
		$generated_doc_no = generatePmWorkDocNo(
			$connect,
			$base_doc_no_raw,
			(string)$info['ag_id']
		);
		
		if ($generated_doc_no === null || $generated_doc_no === '') {
			throw new Exception('ไม่สามารถสร้างเลขที่เอกสาร PM ได้ เนื่องจากไม่พบ doc_no จากตาราง pm_checksheets');
		}
		
		$doc_no = dbEsc($connect, $generated_doc_no);
		$rev_no = dbEsc($connect, $info['rev_no']);
		$checksheet_name = dbEsc($connect, $checksheet_name_raw);

    $machine_code = dbEsc($connect, $info['machine_code']);
    $machine_name = dbEsc($connect, $info['machine_name']);
    $machine_sn = dbEsc($connect, $info['machine_sn']);
    $machine_type = dbEsc($connect, $info['machine_type']);
    $location = dbEsc($connect, $info['location']);

    $plan_date = dbEsc($connect, $info['plan_date']);
    $actual_date = date('Y-m-d');

    $inspector_name_db = dbEsc($connect, $inspector_name);
    $inspector_signature_path_db = dbEsc($connect, $inspector_signature_path);
    $remarks_db = dbEsc($connect, $remarks);
    $created_by_db = dbEsc($connect, $created_by);

    $sql_insert_record = "
        INSERT INTO pm_work_records (
            ag_id,
            plan_event_id,
            plan_id,
            checksheet_id,
            machine_id,
            mac_type_id,

            doc_no,
            rev_no,
            checksheet_name,

            machine_code,
            machine_name,
            machine_sn,
            machine_type,
            location,

            plan_date,
            actual_date,

            inspector_name,
            inspector_signature_path,
            remarks,

            status,
            created_by,
            created_at
        ) VALUES (
            '$ag_id',
            $event_id,
            $plan_id_real,
            $checksheet_id,
            '$machine_id',
            '$mac_type_id',

            '$doc_no',
            '$rev_no',
            '$checksheet_name',

            '$machine_code',
            '$machine_name',
            '$machine_sn',
            '$machine_type',
            '$location',

            " . ($plan_date !== '' ? "'$plan_date'" : "NULL") . ",
            '$actual_date',

            '$inspector_name_db',
            '$inspector_signature_path_db',
            '$remarks_db',

            1,
            '$created_by_db',
            NOW()
        )
    ";

    if (!mysqli_query($connect, $sql_insert_record)) {
        throw new Exception('SQL Error (Insert Work Record): ' . mysqli_error($connect));
    }

    $work_record_id = mysqli_insert_id($connect);

    if ($work_record_id <= 0) {
        throw new Exception('ไม่สามารถสร้างเลขที่บันทึกผลการปฏิบัติงานได้');
    }

    // 4.1 บันทึกผู้ตรวจสอบหลายคนลงตารางลูก
    $sql_insert_inspector = "
        INSERT INTO pm_work_record_inspectors (
            work_record_id,
            plan_event_id,
            user_id,
            user_pk_id,
            inspector_name_snapshot,
            created_at
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ";

    $stmt_inspector = $connect->prepare($sql_insert_inspector);

    if (!$stmt_inspector) {
        throw new Exception('Prepare insert pm_work_record_inspectors failed: ' . $connect->error);
    }

    foreach ($inspector_users as $inspector) {
        $inspector_user_id = isset($inspector['user_id']) ? trim((string)$inspector['user_id']) : '';
        $inspector_user_pk_id = isset($inspector['user_pk_id']) ? intval($inspector['user_pk_id']) : 0;
        $inspector_name_snapshot = isset($inspector['name']) ? trim((string)$inspector['name']) : '';

        if ($inspector_user_id === '') {
            $stmt_inspector->close();
            throw new Exception('พบผู้ตรวจสอบที่ไม่มี user_id');
        }

        $stmt_inspector->bind_param(
            'iisis',
            $work_record_id,
            $event_id,
            $inspector_user_id,
            $inspector_user_pk_id,
            $inspector_name_snapshot
        );

        if (!$stmt_inspector->execute()) {
            $err = $stmt_inspector->error;
            $stmt_inspector->close();
            throw new Exception('Insert pm_work_record_inspectors failed: ' . $err);
        }
    }

    $stmt_inspector->close();

    // 5. บันทึกอะไหล่/วัสดุที่ใช้จริง + ตัด stock ให้ติดลบได้
    if (!empty($parts)) {
        $sql_insert_part = "
            INSERT INTO pm_work_record_actual_spares (
                work_record_id,
                plan_event_id,
                rpd_product_id,
                wh_id,
                pd_gen_code,
                rpd_details_head,
                rpd_details,
                rpd_brand,
                rpd_qty,
                rpd_price,
                pd_unit,
                rpd_sum_money,
                created_by,
                created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ";

        $stmt_part = $connect->prepare($sql_insert_part);

        if (!$stmt_part) {
            throw new Exception('Prepare insert pm_work_record_actual_spares failed: ' . $connect->error);
        }

        $sql_stock = "
            UPDATE product_stocks
            SET qty = qty - ?,
                updated_at = NOW()
            WHERE pd_id = ?
              AND wh_id = ?
        ";

        $stmt_stock = $connect->prepare($sql_stock);

        if (!$stmt_stock) {
            $stmt_part->close();
            throw new Exception('Prepare update product_stocks failed: ' . $connect->error);
        }

        foreach ($parts as $p) {
            $rpd_product_id = intval($p['rpd_product_id'] ?? 0);
            $wh_id = intval($p['wh_id'] ?? 0);

            $pd_gen_code = (string)($p['pd_gen_code'] ?? '');
            $rpd_details_head = (string)($p['rpd_details_head'] ?? '');
            $rpd_details = (string)($p['rpd_details'] ?? '');
            $rpd_brand = (string)($p['rpd_brand'] ?? '');

            $rpd_qty = floatval($p['rpd_qty'] ?? 0);
            $rpd_price = floatval($p['rpd_price'] ?? 0);
            $pd_unit = (string)($p['pd_unit'] ?? '');
            $rpd_sum_money = floatval($p['rpd_sum_money'] ?? ($rpd_qty * $rpd_price));

            if ($rpd_qty <= 0) {
                continue;
            }

            if ($rpd_product_id <= 0 || $wh_id <= 0) {
                $stmt_part->close();
                $stmt_stock->close();
                throw new Exception('ข้อมูลอะไหล่/วัสดุที่ใช้จริงไม่ครบถ้วน');
            }

            if ($rpd_details_head === '') {
                $stmt_part->close();
                $stmt_stock->close();
                throw new Exception('ไม่พบชื่ออะไหล่/วัสดุที่ใช้จริง');
            }

            $stmt_part->bind_param(
                'iiiissssddsds',
                $work_record_id,
                $event_id,
                $rpd_product_id,
                $wh_id,
                $pd_gen_code,
                $rpd_details_head,
                $rpd_details,
                $rpd_brand,
                $rpd_qty,
                $rpd_price,
                $pd_unit,
                $rpd_sum_money,
                $created_by_db
            );

            if (!$stmt_part->execute()) {
                $err = $stmt_part->error;
                $stmt_part->close();
                $stmt_stock->close();
                throw new Exception('Insert pm_work_record_actual_spares failed: ' . $err);
            }

            $stmt_stock->bind_param('dii', $rpd_qty, $rpd_product_id, $wh_id);

            if (!$stmt_stock->execute()) {
                $err = $stmt_stock->error;
                $stmt_part->close();
                $stmt_stock->close();
                throw new Exception('Update product_stocks failed: ' . $err);
            }

            if ($stmt_stock->affected_rows <= 0) {
                $stmt_part->close();
                $stmt_stock->close();
                throw new Exception('ไม่พบสินค้าในสต๊อก: ' . $rpd_details_head);
            }
        }

        $stmt_part->close();
        $stmt_stock->close();
    }

    // 6. map ผลตรวจจาก frontend
    $submitted_map = [];

    foreach ($submitted_items as $submitted) {
        if (!isset($submitted['item_id'])) {
            continue;
        }

        $item_id = intval($submitted['item_id']);

        if ($item_id <= 0) {
            continue;
        }

        $photo_base64 = isset($photos[$item_id]) ? trim((string)$photos[$item_id]) : '';
        $photo_base64 = preg_replace('/[\x00-\x1F\x7F]/', '', $photo_base64);

        $submitted_map[$item_id] = [
            'status' => isset($submitted['status']) ? trim($submitted['status']) : '',
            'value' => isset($submitted['value']) ? trim($submitted['value']) : '',
            'photo_base64' => $photo_base64
        ];
    }

    if (count($submitted_map) === 0) {
        throw new Exception('ข้อมูลผลการตรวจสอบไม่ถูกต้อง');
    }

    // 7. ดึง master items เพื่อ snapshot
    $sql_master_items = "
        SELECT 
            id,
            checksheet_id,
            check_point,
            standard_text,
            method_text,
            action_abnormal,
            check_type_id,
            illustration_path,
            photo_required_id,
            value_name,
            unit,
            expected_value,
            sort_order
        FROM pm_checksheet_items
        WHERE checksheet_id = $checksheet_id
        ORDER BY sort_order ASC, id ASC
    ";

    $res_master_items = mysqli_query($connect, $sql_master_items);

    if (!$res_master_items) {
        throw new Exception('SQL Error (Master Items): ' . mysqli_error($connect));
    }

    $master_item_count = 0;

    while ($item = mysqli_fetch_assoc($res_master_items)) {
        $master_item_count++;

        $checksheet_item_id = intval($item['id']);

        if (!isset($submitted_map[$checksheet_item_id])) {
            throw new Exception('รายการตรวจสอบไม่ครบ กรุณาตรวจสอบข้อมูลอีกครั้ง');
        }

        $submitted = $submitted_map[$checksheet_item_id];

        $result_status = $submitted['status'];
        $actual_value = $submitted['value'];
        $photo_base64 = $submitted['photo_base64'];

        $check_type_id = intval($item['check_type_id']);
        $photo_required_id = intval($item['photo_required_id']);

        if ($check_type_id === 1 && !in_array($result_status, ['Pass', 'Fail'])) {
            throw new Exception('ผลการตรวจไม่ถูกต้องในรายการ: ' . $item['check_point']);
        }

        if ($check_type_id === 2 && !in_array($result_status, ['Pass', 'Fail', 'N/A'])) {
            throw new Exception('ผลการตรวจไม่ถูกต้องในรายการ: ' . $item['check_point']);
        }

        $has_measurement = trim((string)$item['expected_value']) !== '' 
               && trim((string)$item['expected_value']) !== '-';

        if ($has_measurement && $actual_value === '') {
            throw new Exception('กรุณากรอกค่า: ' . $item['value_name'] . ' ในรายการ ' . $item['check_point']);
        }

        if ($actual_value === '') {
            $actual_value = '-';
        }

        $need_photo = false;

        if ($photo_required_id === 1) {
            $need_photo = true;
        }

        if ($photo_required_id === 3 && $result_status === 'Fail') {
            $need_photo = true;
        }

        if ($need_photo && $photo_base64 === '') {
            throw new Exception('กรุณาแนบรูปภาพหลักฐานในรายการ: ' . $item['check_point']);
        }

        $result_photo_path = null;

        if ($photo_base64 !== '') {
            $result_photo_path = saveBase64ImageToPath(
                $photo_base64,
                'uploads/pm_work_records/result_photos',
                'result'
            );

            if (!$result_photo_path) {
                throw new Exception('ไม่สามารถบันทึกรูปภาพหลักฐานในรายการ: ' . $item['check_point']);
            }
        }

        $sort_order = intval($item['sort_order']);

        $check_point = dbEsc($connect, $item['check_point']);
        $standard_text = dbEsc($connect, $item['standard_text']);
        $method_text = dbEsc($connect, $item['method_text']);
        $action_abnormal = dbEsc($connect, $item['action_abnormal']);
        $illustration_path = dbEsc($connect, $item['illustration_path']);
        $value_name = dbEsc($connect, $item['value_name']);
        $unit = dbEsc($connect, $item['unit']);
        $expected_value = dbEsc($connect, $item['expected_value']);

        $result_status_db = dbEsc($connect, $result_status);
        $actual_value_db = dbEsc($connect, $actual_value);
        $result_photo_path_db = dbEsc($connect, $result_photo_path);

        $sql_insert_item = "
            INSERT INTO pm_work_record_items (
                work_record_id,
                plan_event_id,
                checksheet_item_id,

                sort_order,

                check_point,
                standard_text,
                method_text,
                action_abnormal,

                check_type_id,
                photo_required_id,
                illustration_path,

                value_name,
                unit,
                expected_value,

                result_status,
                actual_value,
                result_photo_path,

                created_at
            ) VALUES (
                $work_record_id,
                $event_id,
                $checksheet_item_id,

                $sort_order,

                '$check_point',
                '$standard_text',
                '$method_text',
                '$action_abnormal',

                $check_type_id,
                $photo_required_id,
                '$illustration_path',

                '$value_name',
                '$unit',
                '$expected_value',

                '$result_status_db',
                '$actual_value_db',
                " . ($result_photo_path_db !== '' ? "'$result_photo_path_db'" : "NULL") . ",

                NOW()
            )
        ";

        if (!mysqli_query($connect, $sql_insert_item)) {
            throw new Exception('SQL Error (Insert Work Item): ' . mysqli_error($connect));
        }
    }

    if ($master_item_count === 0) {
        throw new Exception('ไม่พบรายการจุดตรวจสอบของเช็คชีตนี้');
    }

    // 8. snapshot อะไหล่ที่ต้องเตรียม
    $sql_spares = "
        SELECT id, part_name, quantity
        FROM pm_checksheet_spares
        WHERE checksheet_id = $checksheet_id
        ORDER BY id ASC
    ";

    $res_spares = mysqli_query($connect, $sql_spares);

    if (!$res_spares) {
        throw new Exception('SQL Error (Spares Snapshot): ' . mysqli_error($connect));
    }

    while ($spare = mysqli_fetch_assoc($res_spares)) {
        $checksheet_spare_id = intval($spare['id']);
        $part_name = dbEsc($connect, $spare['part_name']);
        $quantity = floatval($spare['quantity']);

        $sql_insert_spare_snapshot = "
            INSERT INTO pm_work_record_spares_snapshot (
                work_record_id,
                plan_event_id,
                checksheet_spare_id,
                part_name,
                quantity,
                created_at
            ) VALUES (
                $work_record_id,
                $event_id,
                $checksheet_spare_id,
                '$part_name',
                $quantity,
                NOW()
            )
        ";

        if (!mysqli_query($connect, $sql_insert_spare_snapshot)) {
            throw new Exception('SQL Error (Insert Spare Snapshot): ' . mysqli_error($connect));
        }
    }

    // 9. snapshot ไฟล์แนบ
    $sql_files = "
        SELECT id, file_type, file_name, file_path, uploaded_at
        FROM pm_checksheet_files
        WHERE checksheet_id = $checksheet_id
        ORDER BY id ASC
    ";

    $res_files = mysqli_query($connect, $sql_files);

    if (!$res_files) {
        throw new Exception('SQL Error (Files Snapshot): ' . mysqli_error($connect));
    }

    while ($file = mysqli_fetch_assoc($res_files)) {
        $checksheet_file_id = intval($file['id']);
        $file_type = dbEsc($connect, $file['file_type']);
        $file_name = dbEsc($connect, $file['file_name']);
        $file_path = dbEsc($connect, $file['file_path']);
        $uploaded_at = dbEsc($connect, $file['uploaded_at']);

        $sql_insert_file_snapshot = "
            INSERT INTO pm_work_record_files_snapshot (
                work_record_id,
                plan_event_id,
                checksheet_file_id,
                file_type,
                file_name,
                file_path,
                uploaded_at,
                created_at
            ) VALUES (
                $work_record_id,
                $event_id,
                $checksheet_file_id,
                '$file_type',
                '$file_name',
                '$file_path',
                " . ($uploaded_at !== '' ? "'$uploaded_at'" : "NULL") . ",
                NOW()
            )
        ";

        if (!mysqli_query($connect, $sql_insert_file_snapshot)) {
            throw new Exception('SQL Error (Insert File Snapshot): ' . mysqli_error($connect));
        }
    }

    // 10. update pm_plan_events
    $sql_update_event = "
        UPDATE pm_plan_events
        SET 
            status = 1,
            status_feedback = 1,
            work_record_id = $work_record_id,
            completed_at = NOW(),
            updated_at = NOW()
        WHERE id = $event_id
    ";

    if (!mysqli_query($connect, $sql_update_event)) {
        throw new Exception('SQL Error (Update Plan Event): ' . mysqli_error($connect));
    }

    return $work_record_id;
}

// =========================================
// ACTION: โหลดข้อมูลแผนและจุดตรวจสอบมาแสดง
// =========================================
if ($action === 'get_data') {
    $event_id = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0;
    
    // 1. ดึงข้อมูลส่วนหัว
    $sql_info = "SELECT e.id, e.checksheet_id, e.event_date AS plan_date, 
                        c.name AS checksheet_name, c.doc_no, c.rev_no,
                        a.asset_name AS machine_name, 
                        a.ass_code AS machine_code, 
                        a.asset_sn AS machine_sn, 
                        g.TGroupName AS machine_type, 
                        CONCAT_WS(' / ', ar.area_name, ac.ac_name, rm.ar_name) AS location 
                 FROM pm_plan_events e 
                 LEFT JOIN pm_checksheets c ON e.checksheet_id = c.id
                 LEFT JOIN tb_ass_list a ON e.machine_id = a.ass_id 
                 LEFT JOIN tb_asset_group g ON a.asset_type = g.GroupId
                 LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
                 LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
                 LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
                 WHERE e.id = $event_id";
                 
    $res_info = mysqli_query($connect, $sql_info);
    
    if (!$res_info) {
        echo json_encode(['success' => false, 'error' => 'SQL Error (Info): ' . mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $info = mysqli_fetch_assoc($res_info);
    
    if (!$info) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลแผนงาน หรือ แผนงานนี้ถูกลบไปแล้ว'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $checksheet_id = (int)$info['checksheet_id'];

    // 2. ดึงข้อมูลรายการอะไหล่ที่ต้องใช้
    $spares = [];
    $sql_spares = "SELECT id, checksheet_id, part_name, quantity 
                   FROM pm_checksheet_spares 
                   WHERE checksheet_id = $checksheet_id";
    $res_spares = mysqli_query($connect, $sql_spares);
    if ($res_spares) {
        while ($row = mysqli_fetch_assoc($res_spares)) {
            $spares[] = $row;
        }
    }

    // 3. ดึงข้อมูลรายการจุดตรวจสอบตาม checksheet_id
    $sql_items = "SELECT id, check_point, standard_text, method_text, 
                         action_abnormal AS action_text, 
                         check_type_id, illustration_path AS reference_image, photo_required_id, 
                         value_name AS measurement_name, unit, expected_value, sort_order
                  FROM pm_checksheet_items 
                  WHERE checksheet_id = $checksheet_id 
                  ORDER BY sort_order ASC, id ASC";
                  
    $res_items = mysqli_query($connect, $sql_items);
    
    if (!$res_items) {
        echo json_encode(['success' => false, 'error' => 'SQL Error (Items): ' . mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $items = [];
    while ($row = mysqli_fetch_assoc($res_items)) {
        $items[] = $row;
    }

    // 4. ดึงไฟล์แนบของ checksheet
    $files = [];
    $sql_files = "SELECT id, checksheet_id, file_type, file_name, file_path, uploaded_at
                  FROM pm_checksheet_files
                  WHERE checksheet_id = $checksheet_id
                  ORDER BY id ASC";
    $res_files = mysqli_query($connect, $sql_files);

    if (!$res_files) {
        echo json_encode(['success' => false, 'error' => 'SQL Error (Files): ' . mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    while ($row = mysqli_fetch_assoc($res_files)) {
        $files[] = $row;
    }

    echo json_encode([
        'success' => true,
        'info' => $info,
        'spares' => $spares,
        'items' => $items,
        'files' => $files
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// =========================================
// ACTION: บันทึกข้อมูลผลการปฏิบัติงาน (ส่วนผู้ตรวจสอบ)
// =========================================
if ($action === 'save') {
    $event_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;

    $inspector_name = isset($_POST['inspector_name']) ? trim($_POST['inspector_name']) : '';
    $inspector_users_json = isset($_POST['inspector_users_json']) ? trim($_POST['inspector_users_json']) : '';
    $remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';
    $inspector_signature = isset($_POST['inspector_signature']) ? trim($_POST['inspector_signature']) : '';

    $items_json = isset($_POST['items']) ? $_POST['items'] : '';
    $parts_json = isset($_POST['parts']) ? $_POST['parts'] : '[]';

    $created_by = isset($_SESSION['sess_user_id']) ? $_SESSION['sess_user_id'] : (isset($_SESSION['sess_user_id_es']) ? $_SESSION['sess_user_id_es'] : '');

    if ($event_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบรหัสแผนงาน'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($inspector_name === '') {
        echo json_encode(['success' => false, 'error' => 'กรุณาเลือกชื่อผู้ตรวจสอบ'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ผู้ตรวจสอบรองรับหลายคน จึงรับเป็น JSON array จาก frontend
    $inspector_users_json = preg_replace('/^\xEF\xBB\xBF/', '', $inspector_users_json);
    $inspector_users_json = preg_replace('/[\x00-\x1F\x7F]/', '', $inspector_users_json);
    $inspector_users = json_decode($inspector_users_json, true);

    if (!is_array($inspector_users) || count($inspector_users) === 0) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูล user_id ของผู้ตรวจสอบ'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($inspector_signature === '') {
        echo json_encode(['success' => false, 'error' => 'กรุณาลงลายมือชื่อผู้ตรวจสอบ'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // items ต้องเป็น JSON เฉพาะข้อมูลผลตรวจ ห้ามใส่ base64 รูปภาพไว้ใน JSON
    $items_json = isset($_POST['items']) ? $_POST['items'] : '';
    $items_json = preg_replace('/^\xEF\xBB\xBF/', '', $items_json);
    $items_json = preg_replace('/[\x00-\x1F\x7F]/', '', $items_json);

    $submitted_items = json_decode($items_json, true);

    if (!is_array($submitted_items) || count($submitted_items) === 0) {
        echo json_encode([
            'success' => false,
            'error' => 'ไม่พบข้อมูลผลการตรวจสอบ',
            'debug' => [
                'post_keys' => array_keys($_POST),
                'items_exists' => isset($_POST['items']),
                'items_length' => strlen($items_json),
                'items_raw_start' => mb_substr($items_json, 0, 300),
                'json_error' => json_last_error_msg()
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $parts_json = isset($_POST['parts']) ? $_POST['parts'] : '[]';
    $parts_json = preg_replace('/^\xEF\xBB\xBF/', '', $parts_json);
    $parts_json = preg_replace('/[\x00-\x1F\x7F]/', '', $parts_json);

    $parts = json_decode($parts_json, true);
    if (!is_array($parts)) {
        $parts = [];
    }
    mysqli_begin_transaction($connect);

    try {
        // รูปผลตรวจรายข้อ (photo_{item_id})
        $photos = [];
        foreach ($submitted_items as $si) {
            $iid = isset($si['item_id']) ? intval($si['item_id']) : 0;
            if ($iid > 0 && isset($_POST['photo_' . $iid])) $photos[$iid] = $_POST['photo_' . $iid];
        }

        $work_record_id = pmws_save_event($connect, $event_id, $inspector_name, $inspector_users, $inspector_signature, $remarks, $created_by, $submitted_items, $parts, $photos);

        mysqli_commit($connect);

        echo json_encode([
            'success' => true,
            'message' => 'บันทึกผลการปฏิบัติงานสำเร็จ',
            'work_record_id' => $work_record_id,
            'plan_event_id' => $event_id
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Exception $e) {
        mysqli_rollback($connect);

        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// =========================================
// ACTION: ข้อมูลสำหรับบันทึกพร้อมกัน — เช็คชีต + เครื่องที่ยังไม่ได้ทำ (เช็คชีตเดียวกัน วันเดียวกัน)
// =========================================
if ($action === 'get_batch_data') {
    $checksheet_id = isset($_GET['checksheet_id']) ? intval($_GET['checksheet_id']) : 0;
    $date = isset($_GET['date']) ? $_GET['date'] : '';
    $ag_id = isset($_SESSION['sess_user_agency']) ? (string)$_SESSION['sess_user_agency'] : '';

    if ($checksheet_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $ag_id === '') {
        echo json_encode(['success' => false, 'error' => 'ข้อมูลไม่ครบ (เช็คชีต / วันที่ / หน่วยงาน)'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $connect->prepare("SELECT id, name, doc_no, rev_no FROM pm_checksheets WHERE id = ?");
    $stmt->bind_param('i', $checksheet_id);
    $stmt->execute();
    $checksheet = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$checksheet) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบเช็คชีต'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $items = [];
    $res = mysqli_query($connect, "SELECT id, check_point, standard_text, method_text, action_abnormal AS action_text, check_type_id,
                                          illustration_path AS reference_image, photo_required_id, value_name AS measurement_name, unit, expected_value, sort_order
                                   FROM pm_checksheet_items WHERE checksheet_id = $checksheet_id ORDER BY sort_order ASC, id ASC");
    while ($res && ($row = mysqli_fetch_assoc($res))) $items[] = $row;

    $spares = [];
    $res = mysqli_query($connect, "SELECT part_name, quantity FROM pm_checksheet_spares WHERE checksheet_id = $checksheet_id ORDER BY id ASC");
    while ($res && ($row = mysqli_fetch_assoc($res))) $spares[] = $row;

    $events = [];
    $stmt = $connect->prepare("
        SELECT e.id, e.event_date, a.asset_name AS machine_name, a.ass_code AS machine_code, a.asset_sn AS machine_sn,
               CONCAT_WS(' / ', ar.area_name, ac.ac_name, rm.ar_name) AS location
        FROM pm_plan_events e
        LEFT JOIN tb_ass_list a ON e.machine_id = a.ass_id
        LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
        LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
        LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
        WHERE e.ag_id = ? AND e.checksheet_id = ? AND e.event_date = ? AND (e.status IS NULL OR e.status <> 1) AND e.work_record_id IS NULL
        ORDER BY location ASC, a.ass_code ASC, e.id ASC
    ");
    $cs = (string)$checksheet_id;
    $stmt->bind_param('sss', $ag_id, $cs, $date);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $events[] = $row;
    $stmt->close();

    echo json_encode(['success' => true, 'checksheet' => $checksheet, 'items' => $items, 'spares' => $spares, 'events' => $events, 'date' => $date], JSON_UNESCAPED_UNICODE);
    exit;
}

// =========================================
// ACTION: บันทึกพร้อมกันหลายเครื่อง (เช็คชีตเดียวกัน) — หน้าเว็บส่งมาเป็นชุด ๆ ละไม่กี่สิบเครื่อง
//  ผลตรวจเหมือนกันทุกเครื่อง (ปกติ / ไม่เกี่ยวข้อง เท่านั้น — เครื่องผิดปกติให้ทำทีละใบ)
//  ค่าที่วัดได้ / รูปที่บังคับ ส่งมาแยกรายเครื่อง; แต่ละเครื่องแยก transaction และรายงานผลรายเครื่อง
// =========================================
if ($action === 'save_batch') {
    @set_time_limit(0);
    $ag_id = isset($_SESSION['sess_user_agency']) ? (string)$_SESSION['sess_user_agency'] : '';
    $checksheet_id = isset($_POST['checksheet_id']) ? intval($_POST['checksheet_id']) : 0;
    $clean = function ($v) { $v = preg_replace('/^\xEF\xBB\xBF/', '', (string)$v); return preg_replace('/[\x00-\x1F\x7F]/', '', $v); };

    $event_ids = json_decode($clean($_POST['event_ids'] ?? '[]'), true);
    $inspector_name = trim($_POST['inspector_name'] ?? '');
    $inspector_users = json_decode($clean($_POST['inspector_users_json'] ?? ''), true);
    $remarks = trim($_POST['remarks'] ?? '');
    $inspector_signature = trim($_POST['inspector_signature'] ?? '');
    $signature_path = trim($_POST['signature_path'] ?? '');
    $common_items = json_decode($clean($_POST['items'] ?? ''), true);
    $per_event = json_decode($clean($_POST['per_event'] ?? '{}'), true);
    $created_by = isset($_SESSION['sess_user_id']) ? $_SESSION['sess_user_id'] : (isset($_SESSION['sess_user_id_es']) ? $_SESSION['sess_user_id_es'] : '');

    $fail = function ($msg) { echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE); exit; };
    if ($ag_id === '') $fail('ไม่พบหน่วยงานของผู้ใช้');
    if ($checksheet_id <= 0) $fail('ไม่พบรหัสเช็คชีต');
    if (!is_array($event_ids) || !count($event_ids)) $fail('ไม่ได้เลือกเครื่อง');
    if (count($event_ids) > 100) $fail('ส่งได้ครั้งละไม่เกิน 100 เครื่อง');
    if ($inspector_name === '') $fail('กรุณาเลือกชื่อผู้ตรวจสอบ');
    if (!is_array($inspector_users) || !count($inspector_users)) $fail('ไม่พบข้อมูล user_id ของผู้ตรวจสอบ');
    if (!is_array($common_items) || !count($common_items)) $fail('ไม่พบข้อมูลผลการตรวจสอบ');
    if (!is_array($per_event)) $per_event = [];

    // บันทึกพร้อมกันได้เฉพาะผล ปกติ / ไม่เกี่ยวข้อง
    foreach ($common_items as $ci) {
        if (!in_array(($ci['status'] ?? ''), ['Pass', 'N/A'], true)) $fail('บันทึกพร้อมกันได้เฉพาะผล "ปกติ" หรือ "ไม่เกี่ยวข้อง" — เครื่องที่ผิดปกติให้บันทึกทีละใบ');
    }

    // ลายเซ็น: ชุดแรกส่งรูปมา => บันทึกไฟล์ครั้งเดียว ชุดต่อไปส่ง path กลับมาใช้ร่วมกัน (ต้องอยู่ในโฟลเดอร์ลายเซ็นเท่านั้น)
    if ($signature_path !== '') {
        $base = realpath('uploads/pm_work_records/signatures');
        $real = realpath($signature_path);
        if (!$base || !$real || strpos($real, $base) !== 0 || !is_file($real)) $fail('ไฟล์ลายเซ็นไม่ถูกต้อง กรุณาเซ็นใหม่');
    } else {
        if ($inspector_signature === '') $fail('กรุณาลงลายมือชื่อผู้ตรวจสอบ');
        $signature_path = saveBase64ImageToPath($clean($inspector_signature), 'uploads/pm_work_records/signatures', 'inspector_sign');
        if (!$signature_path) $fail('ไม่สามารถบันทึกลายเซ็นผู้ตรวจสอบได้');
    }

    $results = [];
    foreach ($event_ids as $eid) {
        $eid = intval($eid);
        if ($eid <= 0) continue;
        try {
            // งานต้องเป็นของหน่วยงานนี้ และเช็คชีตเดียวกับที่เลือก
            $stmt = $connect->prepare("SELECT id FROM pm_plan_events WHERE id = ? AND ag_id = ? AND checksheet_id = ?");
            $cs = (string)$checksheet_id;
            $stmt->bind_param('iss', $eid, $ag_id, $cs);
            $stmt->execute();
            $ok = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$ok) throw new Exception('ไม่พบงานนี้ในหน่วยงาน หรือเป็นคนละเช็คชีต');

            $mine = isset($per_event[(string)$eid]) && is_array($per_event[(string)$eid]) ? $per_event[(string)$eid] : [];
            $values = isset($mine['values']) && is_array($mine['values']) ? $mine['values'] : [];
            $submitted = [];
            $photos = [];
            foreach ($common_items as $ci) {
                $iid = intval($ci['item_id'] ?? 0);
                if ($iid <= 0) continue;
                $submitted[] = [
                    'item_id' => $iid,
                    'status' => (string)$ci['status'],
                    'value' => isset($values[(string)$iid]) ? trim((string)$values[(string)$iid]) : trim((string)($ci['value'] ?? '')),
                ];
                $pk = 'photo_' . $eid . '_' . $iid;
                if (isset($_POST[$pk]) && $_POST[$pk] !== '') $photos[$iid] = $clean($_POST[$pk]);
            }

            mysqli_begin_transaction($connect);
            $wid = pmws_save_event($connect, $eid, $inspector_name, $inspector_users, '', $remarks, $created_by, $submitted, [], $photos, $signature_path);
            mysqli_commit($connect);
            $results[] = ['event_id' => $eid, 'success' => true, 'work_record_id' => $wid];
        } catch (Exception $e) {
            @mysqli_rollback($connect);
            $results[] = ['event_id' => $eid, 'success' => false, 'error' => $e->getMessage()];
        }
    }

    echo json_encode(['success' => true, 'results' => $results, 'signature_path' => $signature_path], JSON_UNESCAPED_UNICODE);
    exit;
}
// =========================================
// ACTION: บันทึกข้อมูลการประเมิน (ส่วนผู้ประเมิน)
// =========================================
if ($action === 'save_evaluation') {
}

// =========================================
// ACTION: ดึงประวัติ PM ที่บันทึกแล้ว
// =========================================
if ($action === 'get_history') {
    $plan_event_id  = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0;
    $work_record_id = isset($_GET['work_record_id']) ? intval($_GET['work_record_id']) : 0;

    $ag_id = '';

    if (isset($_GET['ag_id'])) {
        $ag_id = trim($_GET['ag_id']);
    } elseif (isset($_SESSION['sess_user_agency_es'])) {
        $ag_id = trim($_SESSION['sess_user_agency_es']);
    }

    if ($plan_event_id <= 0 && $work_record_id <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'ไม่พบรหัสแผนงานหรือรหัสประวัติ PM'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $where = "1=1";

    if ($work_record_id > 0) {
        $where .= " AND id = " . $work_record_id;
    } else {
        $where .= " AND plan_event_id = " . $plan_event_id;
    }

    if ($ag_id !== '') {
        $ag_id_db = mysqli_real_escape_string($connect, $ag_id);
        $where .= " AND ag_id = '$ag_id_db'";
    }

    // 1. ดึงหัวประวัติ PM
    $sql_record = "
        SELECT *
        FROM pm_work_records
        WHERE $where
        LIMIT 1
    ";

    $res_record = mysqli_query($connect, $sql_record);

    if (!$res_record) {
        echo json_encode([
            'success' => false,
            'error' => 'SQL Error (History Record): ' . mysqli_error($connect)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $record = mysqli_fetch_assoc($res_record);

    if (!$record) {
        echo json_encode([
            'success' => false,
            'error' => 'ไม่พบประวัติ PM ที่บันทึกแล้ว'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $real_work_record_id = intval($record['id']);

    // 2. ดึงผู้ตรวจสอบหลายคน
    $inspectors = [];
    $sql_inspectors = "
        SELECT *
        FROM pm_work_record_inspectors
        WHERE work_record_id = $real_work_record_id
        ORDER BY id ASC
    ";

    $res_inspectors = mysqli_query($connect, $sql_inspectors);

    if ($res_inspectors) {
        while ($row = mysqli_fetch_assoc($res_inspectors)) {
            $inspectors[] = $row;
        }
    }

    // 3. ดึงรายการผลตรวจ
    $items = [];
    $sql_items = "
        SELECT *
        FROM pm_work_record_items
        WHERE work_record_id = $real_work_record_id
        ORDER BY sort_order ASC, id ASC
    ";

    $res_items = mysqli_query($connect, $sql_items);

    if (!$res_items) {
        echo json_encode([
            'success' => false,
            'error' => 'SQL Error (History Items): ' . mysqli_error($connect)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    while ($row = mysqli_fetch_assoc($res_items)) {
        $items[] = $row;
    }

    // 4. ดึงอะไหล่/วัสดุที่ใช้จริง
    $actual_spares = [];
    $sql_actual_spares = "
        SELECT *
        FROM pm_work_record_actual_spares
        WHERE work_record_id = $real_work_record_id
        ORDER BY id ASC
    ";

    $res_actual_spares = mysqli_query($connect, $sql_actual_spares);

    if ($res_actual_spares) {
        while ($row = mysqli_fetch_assoc($res_actual_spares)) {
            $actual_spares[] = $row;
        }
    }

    // 5. ดึงอะไหล่ที่ต้องเตรียม Snapshot
    $spares_snapshot = [];
    $sql_spares_snapshot = "
        SELECT *
        FROM pm_work_record_spares_snapshot
        WHERE work_record_id = $real_work_record_id
        ORDER BY id ASC
    ";

    $res_spares_snapshot = mysqli_query($connect, $sql_spares_snapshot);

    if ($res_spares_snapshot) {
        while ($row = mysqli_fetch_assoc($res_spares_snapshot)) {
            $spares_snapshot[] = $row;
        }
    }

    // 6. ดึงไฟล์แนบ Snapshot
    $files_snapshot = [];
    $sql_files_snapshot = "
        SELECT *
        FROM pm_work_record_files_snapshot
        WHERE work_record_id = $real_work_record_id
        ORDER BY id ASC
    ";

    $res_files_snapshot = mysqli_query($connect, $sql_files_snapshot);

    if ($res_files_snapshot) {
        while ($row = mysqli_fetch_assoc($res_files_snapshot)) {
            $files_snapshot[] = $row;
        }
    }

    echo json_encode([
        'success' => true,
        'record' => $record,
        'inspectors' => $inspectors,
        'items' => $items,
        'actual_spares' => $actual_spares,
        'spares_snapshot' => $spares_snapshot,
        'files_snapshot' => $files_snapshot
    ], JSON_UNESCAPED_UNICODE);

    exit;
}


// =========================================
// ACTION: ดึงข้อมูลผู้ใช้งานสำหรับ Multi-selection (tb_user)
// =========================================
if ($action === 'get_users') {
    $ag_id = isset($_GET['ag_id']) ? mysqli_real_escape_string($connect, $_GET['ag_id']) : '';
    
    if (empty($ag_id)) {
        echo json_encode(['success' => false, 'error' => 'ไม่พบข้อมูลหน่วยงาน'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql_users = "SELECT id, user_id, user_name, user_fname, user_department, 
                         user_level, user_position, user_position_level, user_agency, user_tel 
                  FROM tb_user 
                  WHERE user_agency = '$ag_id' AND user_department IN (2) 
                  ORDER BY user_fname ASC";
                  
    $res_users = mysqli_query($connect, $sql_users);
    $users = [];
    
    if ($res_users) {
        while($row = mysqli_fetch_assoc($res_users)){
            $users[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $users], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'error' => 'ดึงข้อมูลผู้ใช้ไม่สำเร็จ: ' . mysqli_error($connect)], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
