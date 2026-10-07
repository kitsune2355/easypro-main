<?php
// แสดงข้อผิดพลาด PHP เพื่อการตรวจสอบ
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config_ctrl/connect.php'; 

if (mysqli_connect_errno()) {
    http_response_code(500); 
    echo json_encode(array('error' => 'Database connection failed: ' . mysqli_connect_error()), JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type"); 

date_default_timezone_set('Asia/Bangkok'); 

$action = isset($_GET['action']) ? $_GET['action'] : null; 
$response = array('success' => false, 'data' => array());

if (!$action) {
    http_response_code(400);
    echo json_encode(array('error' => 'ไม่ระบุการดำเนินการ (action)'), JSON_UNESCAPED_UNICODE);
    exit;
}

function ensureUploadDir($base_path) {
    if (is_dir($base_path)) {
        return is_writable($base_path);
    }
    return @mkdir($base_path, 0777, true);
}

function uploadMachineImages($id, $asset_id, $connect) {
    $year_dir  = date('Y');
    $month_dir = date('m');
    $base_path = "AttFile/" . $year_dir . "/" . $month_dir . "/";

    $file_names = array('', '', '');

    // ดึงรูปเก่ามาเก็บไว้เป็นค่าเริ่มต้นก่อน
    if ($id) {
        $stmt_old = $connect->prepare("SELECT fileUpload1, fileUpload2, fileUpload3 FROM tb_ass_list WHERE ass_id = ?");
        $stmt_old->bind_param("i", $id);
        $stmt_old->execute();
        $res_old = $stmt_old->get_result();
        if ($res_old && $old = $res_old->fetch_assoc()) {
            $file_names[0] = isset($old['fileUpload1']) ? $old['fileUpload1'] : '';
            $file_names[1] = isset($old['fileUpload2']) ? $old['fileUpload2'] : '';
            $file_names[2] = isset($old['fileUpload3']) ? $old['fileUpload3'] : '';
        }
        $stmt_old->close();
    }

    $has_status = false;
    for ($i = 1; $i <= 3; $i++) {
        if (isset($_POST["img_status_{$i}"])) { $has_status = true; break; }
    }

    if ($has_status) {
        for ($i = 1; $i <= 3; $i++) {
            $idx = $i - 1;
            $status = isset($_POST["img_status_{$i}"]) ? $_POST["img_status_{$i}"] : 'keep';

            if ($status === 'delete') {
                if (!empty($file_names[$idx]) && file_exists($file_names[$idx])) {
                    @unlink($file_names[$idx]);
                }
                $file_names[$idx] = '';
                continue;
            }

            if ($status === 'upload') {
                // เช็คว่าไฟล์ถูกส่งมาและสมบูรณ์
                if (isset($_FILES["file_upload_{$i}"]) && $_FILES["file_upload_{$i}"]['error'] === 0) {
                    $tmp_name = $_FILES["file_upload_{$i}"]['tmp_name'];
                    $orig_name = $_FILES["file_upload_{$i}"]['name'];
                    
                    $extension = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
                    // กรณี Frontend ส่ง Blob มาไม่มีนามสกุล อนุโลมให้เป็น jpg
                    if ($extension === '') {
                        $extension = 'jpg'; 
                    }

                    if (in_array($extension, array('jpg','jpeg','png','gif'))) {
                        if (!empty($file_names[$idx]) && file_exists($file_names[$idx])) {
                            @unlink($file_names[$idx]);
                        }

                        if (ensureUploadDir($base_path)) {
                            $clean_id = preg_replace('/[^A-Za-z0-9\-]/', '', $asset_id);
                            $new_name = "img_" . $clean_id . "_" . time() . "_" . $i . "." . $extension;
                            $full_path = $base_path . $new_name;

                            if (move_uploaded_file($tmp_name, $full_path)) {
                                $file_names[$idx] = $full_path;
                            }
                        }
                    }
                }
            }
        }
        return $file_names;
    }

    return $file_names;
}

// ----------------------------------------------------------------
// ✅ ดึงข้อมูลและประวัติการซ่อม (GET HISTORY)
// ----------------------------------------------------------------
if ($action === 'get_history') {
    if (!isset($_GET['id'])) {
        echo json_encode(array('success' => false, 'error' => 'ไม่พบรหัสอ้างอิงเครื่องจักร'));
        exit;
    }

    $id = intval($_GET['id']);
    
    $sql_machine = "SELECT a.*, ar.area_name, ac.ac_name, rm.ar_name
                    FROM tb_ass_list a
                    LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
                    LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
                    LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
                    WHERE a.ass_id = ?";
                    
    $stmt_machine = $connect->prepare($sql_machine);
    $stmt_machine->bind_param("i", $id);
    $stmt_machine->execute();
    $result_machine = $stmt_machine->get_result();
    $machine_data = $result_machine->fetch_assoc();
    $stmt_machine->close();

    if (!$machine_data) {
        echo json_encode(array('success' => false, 'error' => 'ไม่พบข้อมูลเครื่องจักรในระบบ'));
        exit;
    }

    $page     = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = isset($_GET['per_page']) ? max(1, min(100, intval($_GET['per_page']))) : 15;
    $offset   = ($page - 1) * $per_page;

    $stmt_count = $connect->prepare("SELECT COUNT(*) AS cnt FROM repair_requests WHERE machine_id = ?");
    $stmt_count->bind_param("i", $id);
    $stmt_count->execute();
    $count_res = $stmt_count->get_result()->fetch_assoc();
    $total_history = (int)(isset($count_res['cnt']) ? $count_res['cnt'] : 0);
    $stmt_count->close();

    $sql_history = "SELECT * FROM repair_requests WHERE machine_id = ? ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?";
    $stmt_history = $connect->prepare($sql_history);
    $stmt_history->bind_param("iii", $id, $per_page, $offset);
    $stmt_history->execute();
    $result_history = $stmt_history->get_result();
    
    $history_data = array();
    while ($row = $result_history->fetch_assoc()) {
        $history_data[] = $row;
    }
    $stmt_history->close();

    echo json_encode(array(
        'success' => true, 
        'data' => array(
            'machine' => $machine_data,
            'history' => $history_data,
            'history_meta' => array(
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total_history,
                'has_more' => ($offset + count($history_data)) < $total_history
            )
        )
    ));
    exit;
}

// ----------------------------------------------------------------
// ✅ ดึงข้อมูลเครื่องจักร + ประวัติการแจ้งซ่อม สำหรับหน้า Scan QR (Public View)
// ----------------------------------------------------------------
if ($action === 'get_public_history') {
    $asset_code = isset($_GET['asset_id']) ? trim($_GET['asset_id']) : '';
    $ag_id      = isset($_GET['ag_id']) ? trim($_GET['ag_id']) : '';

    if ($asset_code === '') {
        echo json_encode(array('success' => false, 'error' => 'ไม่พบรหัสครุภัณฑ์ (Asset ID) ในลิงก์'), JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql_machine = "SELECT a.*, g.TGroupName, ar.area_name, ac.ac_name, rm.ar_name, ag.ag_contract,
                    a.asset_rp_area_id, a.asset_rp_ac_id, a.asset_rp_ar_id
                    FROM tb_ass_list a
                    LEFT JOIN tb_asset_group g ON a.asset_type = g.GroupId
                    LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
                    LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
                    LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
                    LEFT JOIN tb_agency ag ON a.ass_ag_id = ag.ag_id
                    WHERE a.ass_code = ?";
    $params = array($asset_code);
    $types  = "s";

    if ($ag_id !== '') {
        $sql_machine .= " AND a.ass_ag_id = ?";
        $params[] = $ag_id;
        $types   .= "s";
    }

    $sql_machine .= " LIMIT 1";

    $stmt_machine = $connect->prepare($sql_machine);
    
    // PHP 5.4 bind_param workaround แบบ Dynamic
    $bind_params = array($types);
    foreach ($params as $key => $value) {
        $bind_params[] = &$params[$key];
    }
    call_user_func_array(array($stmt_machine, 'bind_param'), $bind_params);
    
    $stmt_machine->execute();
    $row = $stmt_machine->get_result()->fetch_assoc();
    $stmt_machine->close();

    if (!$row) {
        echo json_encode(array('success' => false, 'error' => 'ไม่พบข้อมูลเครื่องจักรนี้ในระบบ'), JSON_UNESCAPED_UNICODE);
        exit;
    }

    $images = array();
    for ($i = 1; $i <= 3; $i++) {
        if (!empty($row["fileUpload{$i}"])) $images[] = $row["fileUpload{$i}"];
    }

    $machine_id = $row['ass_id'];

    $machine_data = array(
        'id'           => $machine_id,
        'images'       => $images,
        'image1'       => isset($row['fileUpload1']) ? $row['fileUpload1'] : '',
        'image2'       => isset($row['fileUpload2']) ? $row['fileUpload2'] : '',
        'image3'       => isset($row['fileUpload3']) ? $row['fileUpload3'] : '',
        'asset_id'     => $row['ass_code'],
        'machine_name' => $row['asset_name'],
        'type_name'    => isset($row['TGroupName']) ? $row['TGroupName'] : '-',
        'serial'       => $row['asset_sn'],
        'brand'        => $row['brn_id'],
        'model'        => $row['asset_model'],
        'warranty'     => $row['asset_warranty'],
        'company'      => $row['asset_company'],
        'remark'       => $row['asset_remark'],
        'status'       => $row['asset_status'],
        'location'     => isset($row['area_name']) ? $row['area_name'] : 'ไม่ระบุ',
        'floor'        => isset($row['ac_name']) ? $row['ac_name'] : '-',
        'room'         => isset($row['ar_name']) ? $row['ar_name'] : '-',
        'area_id'      => $row['asset_rp_area_id'],
        'ac_id'        => $row['asset_rp_ac_id'],
        'ar_id'        => $row['asset_rp_ar_id'],
        'ag_contract'  => isset($row['ag_contract']) ? $row['ag_contract'] : '-'
    );

    $page     = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = isset($_GET['per_page']) ? max(1, min(100, intval($_GET['per_page']))) : 15;
    $offset   = ($page - 1) * $per_page;

    $total_history = 0;
    if ($stmt_count = $connect->prepare("SELECT COUNT(*) AS cnt FROM repair_requests WHERE machine_id = ?")) {
        $stmt_count->bind_param("i", $machine_id);
        $stmt_count->execute();
        $count_res = $stmt_count->get_result()->fetch_assoc();
        $total_history = (int)(isset($count_res['cnt']) ? $count_res['cnt'] : 0);
        $stmt_count->close();
    }

    $history_data = array();
    $sql_history = "SELECT * FROM repair_requests WHERE machine_id = ? ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?";
    if ($stmt_history = $connect->prepare($sql_history)) {
        $stmt_history->bind_param("iii", $machine_id, $per_page, $offset);
        $stmt_history->execute();
        $result_history = $stmt_history->get_result();
        while ($h = $result_history->fetch_assoc()) $history_data[] = $h;
        $stmt_history->close();
    }

    echo json_encode(array(
        'success' => true,
        'data' => array(
            'machine' => $machine_data,
            'history' => $history_data,
            'history_meta' => array(
                'page' => $page,
                'per_page' => $per_page,
                'total' => $total_history,
                'has_more' => ($offset + count($history_data)) < $total_history
            )
        )
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

// ----------------------------------------------------------------
// ✅ 1. ดึงข้อมูลแบบละเอียดตาม ID (GET BY ID)
// ----------------------------------------------------------------
if ($action === 'get_by_id') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        $sql_asset = "SELECT a.*, g.TGroupName, ar.area_name, ac.ac_name, rm.ar_name,
                             a.asset_rp_area_id, a.asset_rp_ac_id, a.asset_rp_ar_id
                      FROM tb_ass_list a
                      LEFT JOIN tb_asset_group g ON a.asset_type = g.GroupId
                      LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
                      LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
                      LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
                      WHERE a.ass_id = ?";
        
        if ($stmt_asset = $connect->prepare($sql_asset)) {
            $stmt_asset->bind_param("i", $id);
            $stmt_asset->execute();
            $result_asset = $stmt_asset->get_result();

            if ($result_asset->num_rows > 0) {
                $row = $result_asset->fetch_assoc();
                
                $images = array();
                for ($i = 1; $i <= 3; $i++) {
                    $key = "fileUpload" . $i;
                    if (!empty($row[$key])) $images[] = $row[$key];
                }

                $image1 = !empty($row['fileUpload1']) ? $row['fileUpload1'] : '';
                $image2 = !empty($row['fileUpload2']) ? $row['fileUpload2'] : '';
                $image3 = !empty($row['fileUpload3']) ? $row['fileUpload3'] : '';

                $formatted_data = array(
                    'id'           => $row['ass_id'],
                    'images'       => $images,
                    'image1'       => $image1,
                    'image2'       => $image2,
                    'image3'       => $image3,
                    'asset_id'     => $row['ass_code'],
                    'machine_name' => $row['asset_name'],
                    'asset_type'   => $row['asset_type'],
                    'type_name'    => $row['TGroupName'],
                    'serial'       => $row['asset_sn'],
                    'brand'        => $row['brn_id'],
                    'model'        => $row['asset_model'],
                    'warranty'     => $row['asset_warranty'],
                    'company'      => $row['asset_company'],
                    'remark'       => $row['asset_remark'],
                    'status'       => $row['asset_status'],
                    'location'     => isset($row['area_name']) ? $row['area_name'] : 'ไม่ระบุ',
                    'floor'        => isset($row['ac_name']) ? $row['ac_name'] : '-',
                    'room'         => isset($row['ar_name']) ? $row['ar_name'] : '-',
                    'area_id'      => $row['asset_rp_area_id'],
                    'ac_id'        => $row['asset_rp_ac_id'],
                    'ar_id'        => $row['asset_rp_ar_id']
                );

                $history_data = array();
                $sql_history = "SELECT id, rp_format, status, problem_detail, completed_solution 
                                FROM repair_requests WHERE machine_id = ? ORDER BY id DESC";
                if ($stmt_history = $connect->prepare($sql_history)) {
                    $stmt_history->bind_param("i", $id);
                    $stmt_history->execute();
                    $result_history = $stmt_history->get_result();
                    while ($h = $result_history->fetch_assoc()) $history_data[] = $h;
                    $stmt_history->close();
                }
                $formatted_data['history'] = $history_data;

                $response['success'] = true;
                $response['data'] = $formatted_data;
            }
            $stmt_asset->close();
        }
    }
}

// ----------------------------------------------------------------
// ✅ 2. ดึงข้อมูลทั้งหมด (GET ALL)
// ----------------------------------------------------------------
elseif ($action === 'get_all') {
    $ag_id = isset($_GET['ag_id']) ? $_GET['ag_id'] : null;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    $isExport = isset($_GET['export']) && $_GET['export'] === 'true';

    $startRow = intval(isset($_GET['startRow']) ? $_GET['startRow'] : 0);
    $endRow   = intval(isset($_GET['endRow']) ? $_GET['endRow'] : 0);
    $limit    = $endRow > $startRow ? $endRow - $startRow : 0;

    $sortModel = json_decode(isset($_GET['sortModel']) ? $_GET['sortModel'] : '[]', true);
    $filterModel = json_decode(isset($_GET['filterModel']) ? $_GET['filterModel'] : '[]', true);

    try {
        $where = " WHERE a.ass_ag_id = ? ";
        $params = array($ag_id);
        $types  = "s";

        if (!empty($search)) {
            $where .= " AND (a.ass_code LIKE ? OR a.asset_name LIKE ? OR a.asset_sn LIKE ?) ";
            $searchParam = "%{$search}%";
            array_push($params, $searchParam, $searchParam, $searchParam);
            $types .= "sss";
        }

        if (!empty($filterModel)) {
            $filterMapping = array(
                'asset_id'     => 'a.ass_code',
                'machine_name' => 'a.asset_name',
                'type_name'    => 'g.TGroupName',
                'status'       => 'a.asset_status',
                'location'     => 'ar.area_name',
                'floor'        => 'ac.ac_name',
                'room'         => 'rm.ar_name',
                'asset_ins'    => 'a.asset_ins' 
            );

            foreach ($filterModel as $key => $info) {
                if (isset($filterMapping[$key])) {
                    $dbCol = $filterMapping[$key];
                    if ($info['filterType'] === 'set') {
                        $values = $info['values'];
                        if (!empty($values)) {
                            $placeholders = implode(',', array_fill(0, count($values), '?'));
                            $where .= " AND $dbCol IN ($placeholders) ";
                            foreach ($values as $v) {
                                $params[] = $v;
                                $types .= "s";
                            }
                        }
                    } 
                    elseif ($info['filterType'] === 'text') {
                        $where .= " AND $dbCol LIKE ? ";
                        $params[] = "%" . $info['filter'] . "%";
                        $types .= "s";
                    }
                    elseif ($info['filterType'] === 'dateRange') {
                        $dateFrom = isset($info['dateFrom']) ? $info['dateFrom'] : '';
                        $dateTo = isset($info['dateTo']) ? $info['dateTo'] : '';
                        
                        if (!empty($dateFrom) && !empty($dateTo)) {
                            $where .= " AND DATE($dbCol) BETWEEN ? AND ? ";
                            $params[] = $dateFrom;
                            $params[] = $dateTo;
                            $types .= "ss";
                        } 
                        elseif (!empty($dateFrom)) {
                            $where .= " AND DATE($dbCol) >= ? ";
                            $params[] = $dateFrom;
                            $types .= "s";
                        } 
                        elseif (!empty($dateTo)) {
                            $where .= " AND DATE($dbCol) <= ? ";
                            $params[] = $dateTo;
                            $types .= "s";
                        }
                    }
                }
            }
        }

        $orderBy = " ORDER BY a.ass_id DESC ";
        if (!empty($sortModel)) {
            $col = $sortModel[0]['colId'];
            $dir = strtoupper($sortModel[0]['sort']) === 'ASC' ? 'ASC' : 'DESC';
            $allowedSort = array(
                'asset_id' => 'a.ass_code',
                'machine_name' => 'a.asset_name',
                'status' => 'a.asset_status',
                'type_name' => 'g.TGroupName',
                'asset_ins' => 'a.asset_ins' 
            );
            if (isset($allowedSort[$col])) {
                $orderBy = " ORDER BY {$allowedSort[$col]} {$dir} ";
            }
        }

        $stmtCount = $connect->prepare("SELECT COUNT(*) AS total FROM tb_ass_list a 
            LEFT JOIN tb_asset_group g ON a.asset_type = g.GroupId
            LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
            LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
            LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
            $where");
            
        $bind_count_params = array($types);
        foreach ($params as $key => $value) {
            $bind_count_params[] = &$params[$key];
        }
        call_user_func_array(array($stmtCount, 'bind_param'), $bind_count_params);
        
        $stmtCount->execute();
        $total = $stmtCount->get_result()->fetch_assoc()['total'];

        $dataSql = "
            SELECT a.*, g.TGroupName, ar.area_name, ac.ac_name, rm.ar_name, ag.ag_contract
            FROM tb_ass_list a
            LEFT JOIN tb_asset_group g ON a.asset_type = g.GroupId
            LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
            LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
            LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
            LEFT JOIN tb_agency ag ON a.ass_ag_id = ag.ag_id
            $where
            $orderBy
        ";

        if (!$isExport) {
            $dataSql .= " LIMIT ?, ? ";
            $paramsData = array_merge($params, array($startRow, $limit));
            $typesData  = $types . "ii";
        } else {
            $paramsData = $params;
            $typesData  = $types;
        }

        $stmtData = $connect->prepare($dataSql);
        
        $bind_data_params = array($typesData);
        foreach ($paramsData as $key => $value) {
            $bind_data_params[] = &$paramsData[$key];
        }
        call_user_func_array(array($stmtData, 'bind_param'), $bind_data_params);
        
        $stmtData->execute();
        $res = $stmtData->get_result();

        $rows = array();
        while ($row = $res->fetch_assoc()) {
            $images = array();
            for ($i = 1; $i <= 3; $i++) {
                if (!empty($row["fileUpload{$i}"])) {
                    $images[] = $row["fileUpload{$i}"];
                }
            }

            $rows[] = array(
                'id' => $row['ass_id'],
                'images' => $images,
                'asset_id' => $row['ass_code'],
                'type_name' => $row['TGroupName'],
                'machine_name' => $row['asset_name'],
                'serial' => $row['asset_sn'],
                'status' => $row['asset_status'],
                'location' => isset($row['area_name']) ? $row['area_name'] : 'ไม่ระบุอาคาร',
                'floor' => isset($row['ac_name']) ? $row['ac_name'] : '-',
                'room' => isset($row['ar_name']) ? $row['ar_name'] : '-',
                'remark' => $row['asset_remark'],
                'brand' => isset($row['brn_id']) ? $row['brn_id'] : '-',  
                'model' => isset($row['asset_model']) ? $row['asset_model'] : '-', 
                'warranty' => isset($row['asset_warranty']) ? $row['asset_warranty'] : '-',
                'company' => isset($row['asset_company']) ? $row['asset_company'] : '-',
                'asset_ins' => isset($row['asset_ins']) ? $row['asset_ins'] : '-' ,
                'ag_contract' => $row['ag_contract']
            );
        }

        echo json_encode(array(
            'rows' => $rows,
            'lastRow' => $total
        ));
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array('error' => $e->getMessage()));
        exit;
    }
}
// ----------------------------------------------------------------
// ✅ 3. บันทึก หรือ แก้ไขข้อมูล (ADD & UPDATE)
// ----------------------------------------------------------------
elseif ($action === 'save') {
    $id             = isset($_POST['id']) ? intval($_POST['id']) : null;
    $asset_id       = isset($_POST['asset_id']) ? $_POST['asset_id'] : '';
    $machine_name   = isset($_POST['machine_name']) ? $_POST['machine_name'] : '';
    $machine_type   = isset($_POST['machine_type']) ? $_POST['machine_type'] : '';
    $serial         = isset($_POST['serial']) ? $_POST['serial'] : '';
    $brand          = (!empty($_POST['brand']) && $_POST['brand'] !== 'undefined') ? $_POST['brand'] : 0;
    $model          = isset($_POST['model']) ? $_POST['model'] : '';
    $warranty_end   = !empty($_POST['warranty']) ? $_POST['warranty'] : null;
    $vendor         = isset($_POST['vendor']) ? $_POST['vendor'] : '';
    $remark         = isset($_POST['remark']) ? $_POST['remark'] : '';
    $status         = isset($_POST['status']) ? $_POST['status'] : '';
    $ag_id          = isset($_POST['ag_id']) ? $_POST['ag_id'] : '';
    $area_id        = !empty($_POST['area_id']) ? intval($_POST['area_id']) : null;
    $ac_id          = !empty($_POST['ac_id']) ? intval($_POST['ac_id']) : null;
    $ar_id          = !empty($_POST['ar_id']) ? intval($_POST['ar_id']) : null;
    $current_user   = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'System'; 
    $dep_id         = isset($_POST['dep_id']) ? intval($_POST['dep_id']) : 0;
    
    $now            = date('Y-m-d H:i:s');
    $asset_state    = 1; 
    $asset_ins      = $now;
    $warranty_start = $now;

    if ($id) {
        $check_sql = "SELECT ass_id FROM tb_ass_list WHERE ass_code = ? AND ass_id != ?";
        $stmt_check = $connect->prepare($check_sql);
        $stmt_check->bind_param("si", $asset_id, $id);
    } else {
        $check_sql = "SELECT ass_id FROM tb_ass_list WHERE ass_code = ?";
        $stmt_check = $connect->prepare($check_sql);
        $stmt_check->bind_param("s", $asset_id);
    }

    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    
    if ($result_check->num_rows > 0) {
        echo json_encode(array(
            'success' => false, 
            'error' => 'รหัสครุภัณฑ์ "'.$asset_id.'" มีอยู่ในระบบแล้ว โปรดใช้รหัสอื่น'
        ));
        $stmt_check->close();
        exit;
    }
    $stmt_check->close();

    $uploadedFiles = uploadMachineImages($id, $asset_id, $connect);

    if ($id) {
        $sql = "UPDATE tb_ass_list SET 
                ass_code = ?, asset_name = ?, asset_type = ?, asset_sn = ?, 
                brn_id = ?, asset_model = ?, asset_warranty = ?, asset_company = ?, 
                asset_remark = ?, asset_status = ?, asset_rp_area_id = ?, 
                asset_rp_ac_id = ?, asset_rp_ar_id = ?,
                fileUpload1 = ?, fileUpload2 = ?, fileUpload3 = ? , 
                updated_at = ?, updated_by = ?
                WHERE ass_id = ?";

        $stmt = $connect->prepare($sql);

        if (!$stmt) {
            echo json_encode(array('success' => false, 'error' => 'SQL Prepare Error (Update): ' . $connect->error));
            exit;
        }

        $stmt->bind_param("ssssssssssiiisssssi", 
            $asset_id, $machine_name, $machine_type, $serial, $brand, 
            $model, $warranty_end, $vendor, $remark, $status, 
            $area_id, $ac_id, $ar_id,
            $uploadedFiles[0], $uploadedFiles[1], $uploadedFiles[2], 
            $now, $current_user, $id
        );
    } else {
        $sql = "INSERT INTO tb_ass_list (
                    ass_code, asset_type, asset_name, asset_sn, asset_model, 
                    asset_rp_area_id, asset_rp_ac_id, asset_rp_ar_id, asset_warranty, asset_company, 
                    dep_id, brn_id, warranty_startdate, fileUpload1, fileUpload2, 
                    fileUpload3, asset_remark, asset_state, asset_ins, created_by, updated_at, updated_by, ass_ag_id, 
                    asset_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $connect->prepare($sql);
        if (!$stmt) {
            echo json_encode(array('success' => false, 'error' => 'SQL Prepare Error: ' . $connect->error));
            exit;
        }

        $types = "sssssiiisssissssssssssis";
        
        $stmt->bind_param($types, 
            $asset_id,      
            $machine_type,  
            $machine_name,  
            $serial,        
            $model,         
            $area_id,       
            $ac_id,         
            $ar_id,         
            $warranty_end,  
            $vendor,        
            $dep_id,        
            $brand,         
            $warranty_start,
            $uploadedFiles[0], 
            $uploadedFiles[1], 
            $uploadedFiles[2], 
            $remark,        
            $asset_state,   
            $asset_ins,     
            $current_user,
            $now,           
            $current_user,  
            $ag_id,         
            $status         
        );
    }

    if ($stmt->execute()) {
        echo json_encode(array('success' => true, 'message' => 'บันทึกข้อมูลเรียบร้อยแล้ว'));
        exit;
    } else {
        echo json_encode(array('success' => false, 'error' => 'Database Error: ' . $stmt->error));
        exit;
    }
} 
elseif ($action === 'get_filter_values') {
    $column = isset($_GET['column']) ? $_GET['column'] : '';
    $ag_id = isset($_GET['ag_id']) ? $_GET['ag_id'] : '';

    $columnMap = array(
        'asset_id'     => 'a.ass_code',
        'type_name'    => 'g.TGroupName',
        'machine_name' => 'a.asset_name',
        'location'     => 'ar.area_name',
        'floor'        => 'ac.ac_name',
        'room'         => 'rm.ar_name',
        'status'       => 'a.asset_status'
    );

    if (!isset($columnMap[$column])) {
        echo json_encode(array());
        exit;
    }

    $dbCol = $columnMap[$column];

    $sql = "SELECT DISTINCT $dbCol as value 
            FROM tb_ass_list a
            LEFT JOIN tb_asset_group g ON a.asset_type = g.GroupId
            LEFT JOIN tb_area ar ON a.asset_rp_area_id = ar.area_id
            LEFT JOIN tb_area_class ac ON a.asset_rp_ac_id = ac.ac_id
            LEFT JOIN tb_area_room rm ON a.asset_rp_ar_id = rm.ar_id
            WHERE a.ass_ag_id = ? AND $dbCol IS NOT NULL AND $dbCol != ''
            ORDER BY $dbCol ASC";

    $stmt = $connect->prepare($sql);
    $stmt->bind_param("s", $ag_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $values = array();
    while ($row = $res->fetch_assoc()) {
        $values[] = $row['value'];
    }
    echo json_encode($values);
    exit;
}

// ==========================================
// ส่วนสำหรับการลบข้อมูล (Delete)
// ==========================================
if ($action === 'delete') {
    $deleted_ids_json = isset($_POST['deleted_ids']) ? $_POST['deleted_ids'] : '[]';
    $deleted_ids = json_decode($deleted_ids_json, true);

    if (!empty($deleted_ids) && is_array($deleted_ids)) {
        $placeholders = implode(',', array_fill(0, count($deleted_ids), '?'));
        $types = str_repeat('i', count($deleted_ids));
        
        $sql = "DELETE FROM tb_ass_list WHERE ass_id IN ($placeholders)"; 
        
        $stmt = $connect->prepare($sql);
        if ($stmt) {
            // การ Bind param แบบ Dynamic สำหรับ PHP 5.4 
            $bind_delete_params = array($types);
            foreach ($deleted_ids as $key => $value) {
                $bind_delete_params[] = &$deleted_ids[$key];
            }
            call_user_func_array(array($stmt, 'bind_param'), $bind_delete_params);
            
            if ($stmt->execute()) {
                echo json_encode(array('success' => true));
            } else {
                echo json_encode(array('success' => false, 'error' => 'Database Error: ' . $connect->error));
            }
            $stmt->close();
        } else {
            echo json_encode(array('success' => false, 'error' => 'SQL Prepare failed: ' . $connect->error));
        }
    } else {
        echo json_encode(array('success' => false, 'error' => 'ไม่มีรายการที่ต้องลบ'));
    }
    exit;
}

if (isset($connect)) mysqli_close($connect);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>