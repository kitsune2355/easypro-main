<?php
include 'config_ctrl/connect.php'; 
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Bangkok'); 

$response = ['success' => false, 'data' => [], 'total' => 0, 'error' => null];

// --- ส่วนที่ต้องเพิ่ม: จัดการกับ Request แบบ POST ที่ส่ง JSON มา ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่า JSON payload ที่ส่งมาจาก Frontend
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['updates']) && is_array($input['updates'])) {
        // เริ่ม Transaction เพื่อป้องกันข้อมูลบันทึกไม่ครบ
        $connect->begin_transaction();
        
        try {
            foreach ($input['updates'] as $update) {
                $plan_id = (int)$update['plan_id'];
                $old_date = mysqli_real_escape_string($connect, $update['old_date']);
                $new_date = mysqli_real_escape_string($connect, $update['new_date']);
                $reason = mysqli_real_escape_string($connect, $update['reason']);
                $user_id = mysqli_real_escape_string($connect, $update['user_id']);

                // 1. อัปเดตวันที่ใหม่ในตาราง pm_plan_events
                $updateSql = "UPDATE pm_plan_events SET event_date = '$new_date' WHERE id = $plan_id";
                if (!$connect->query($updateSql)) {
                    throw new Exception("Error updating plan: " . $connect->error);
                }

                // 2. บันทึกประวัติการเลื่อนลงในตาราง pm_plan_postpone_history
                $insertSql = "INSERT INTO pm_plan_postpone_history (plan_id, old_date, new_date, reason, created_by) 
                                VALUES ($plan_id, '$old_date', '$new_date', '$reason', '$user_id')";
                if (!$connect->query($insertSql)) {
                    throw new Exception("Error inserting history: " . $connect->error);
                }
            }
            
            // ถ้าทำงานครบทุกรายการให้ Commit ข้อมูลลงฐานข้อมูลจริง
            $connect->commit();
            $response['success'] = true;
            $response['message'] = "บันทึกข้อมูลเรียบร้อยแล้ว";
            
        } catch (Exception $e) {
            // หากเกิดข้อผิดพลาดให้ Rollback ข้อมูลกลับทั้งหมด
            $connect->rollback();
            $response['error'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['error'] = 'รูปแบบข้อมูลไม่ถูกต้อง (Missing updates array)';
    }

    // ส่ง JSON กลับไปที่ Frontend และหยุดการทำงานของ PHP ทันที
    echo json_encode($response);
    exit;
}
// -----------------------------------------------------------------

$action = $_GET['action'] ?? null; 

if ($action === 'get_history') {
    $plan_id = isset($_GET['plan_id']) ? (int)$_GET['plan_id'] : 0;
    
    if ($plan_id > 0) {
        $historySql = "SELECT old_date, new_date, reason FROM pm_plan_postpone_history WHERE plan_id = $plan_id ORDER BY id ASC";
        $historyResult = $connect->query($historySql);
        
        $historyData = [];
        if ($historyResult) {
            while ($row = $historyResult->fetch_assoc()) {
                $historyData[] = $row;
            }
        }
        
        $response['success'] = true;
        $response['data'] = $historyData;
    } else {
        $response['error'] = 'Invalid plan ID';
    }
    
    echo json_encode($response);
    exit;
}

if ($action === 'get_all') {
    $ag_id = $_GET['ag_id'] ?? '';
    $start = isset($_GET['startRow']) ? (int)$_GET['startRow'] : 0;
    $limit = isset($_GET['endRow']) ? (int)$_GET['endRow'] - $start : 50;
    
    // รับค่า Filter ทั้งหมดที่ส่งมาจาก JS
    $month = (isset($_GET['month']) && $_GET['month'] !== '') ? (int)$_GET['month'] : null;
    $year = (isset($_GET['year']) && $_GET['year'] !== '') ? (int)$_GET['year'] : null;
    $machine = isset($_GET['machine']) ? mysqli_real_escape_string($connect, $_GET['machine']) : '';
    $checksheet = isset($_GET['checksheet']) ? mysqli_real_escape_string($connect, $_GET['checksheet']) : '';
    $type = isset($_GET['type']) ? mysqli_real_escape_string($connect, $_GET['type']) : '';
    $location = isset($_GET['location']) ? mysqli_real_escape_string($connect, $_GET['location']) : '';

    $where = "WHERE p.status = 0";

    // จัดการเงื่อนไข Filter ของเดือนและปี
    if ($month !== null) {
        $where .= " AND MONTH(p.event_date) = $month";
    }
    if ($year !== null) {
        $where .= " AND YEAR(p.event_date) = $year";
    }

    // --- ส่วนที่ต้องเพิ่ม: จัดการเงื่อนไข Filter ตัวอื่นๆ ---
    if ($machine !== '') {
        $where .= " AND m.asset_name LIKE '%$machine%'";
    }
    if ($checksheet !== '') {
        $where .= " AND c.name LIKE '%$checksheet%'";
    }
    if ($type !== '') {
        $where .= " AND g.TGroupName LIKE '%$type%'";
    }
    if ($location !== '') {
        $where .= " AND a.area_name LIKE '%$location%'";
    }
    // ---------------------------------------------------

    // 1. นับจำนวนทั้งหมดสำหรับ Pagination
    $countSql = "SELECT COUNT(*) as total 
                 FROM pm_plan_events p 
                 LEFT JOIN tb_ass_list m ON p.machine_id = m.ass_id 
                 LEFT JOIN tb_asset_group g ON m.asset_type = g.GroupId
                 LEFT JOIN tb_area a ON m.asset_rp_area_id = a.area_id
                 LEFT JOIN pm_checksheets c ON p.checksheet_id = c.id
                 $where";
                 
    $totalRes = $connect->query($countSql);
    $response['total'] = $totalRes->fetch_assoc()['total'];

    // 2. ดึงข้อมูลพร้อม Subquery นับจำนวนครั้งที่เคยเลื่อน (postpone_count)
    $sql = "SELECT 
                p.id, p.event_date, p.status,
                m.ass_code AS qr_code, m.asset_name AS machine_name,
                g.TGroupName AS machine_type, a.area_name AS location_name,
                c.name AS checksheet_name, pl.frequency AS plan_frequency,
                (SELECT COUNT(*) FROM pm_plan_postpone_history WHERE plan_id = p.id) as postpone_count
            FROM pm_plan_events p
            LEFT JOIN tb_ass_list m ON p.machine_id = m.ass_id
            LEFT JOIN tb_asset_group g ON m.asset_type = g.GroupId
            LEFT JOIN tb_area a ON m.asset_rp_area_id = a.area_id
            LEFT JOIN pm_checksheets c ON p.checksheet_id = c.id
            LEFT JOIN pm_plans pl ON pl.checksheet_id = c.id 
            $where
            LIMIT $start, $limit";

    $result = $connect->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $events[] = [
                'id' => $row['id'],
                'start' => $row['event_date'],
                'postpone_count' => (int)$row['postpone_count'],
                'extendedProps' => [
                    'qr_code' => $row['qr_code'],
                    'machine' => $row['machine_name'],
                    'type' => $row['machine_type'],      
                    'location' => $row['location_name'], 
                    'checksheet' => $row['checksheet_name'],
                    'frequency' => (int)$row['plan_frequency'] 
                ]
            ];
        }
        $response['success'] = true;
        $response['data'] = $events;
    }
}
echo json_encode($response);