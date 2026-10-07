<?php
include 'config_ctrl/connect.php'; 
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Bangkok'); 

$response = ['success' => false, 'data' => [], 'total' => 0, 'error' => null];

if (mysqli_connect_errno()) {
    $response['error'] = 'Database connection failed';
    echo json_encode($response); exit;
}

$action = $_GET['action'] ?? null; 

if ($action === 'get_all') {
    $ag_id = $_GET['ag_id'] ?? '';
    
    // รับค่าสำหรับ Pagination
    $start = isset($_GET['startRow']) ? (int)$_GET['startRow'] : 0;
    $limit = isset($_GET['endRow']) ? (int)$_GET['endRow'] - $start : 50;

    // แผนงานของวันนี้และแผนในอนาคต
    $where = "WHERE p.status = 0 AND DATE(p.event_date) >= CURDATE()";

    // จัดการตัวกรองเพิ่มเติม (ถ้ามี)
    if ($ag_id != '') $where .= " AND m.asset_type = '" . mysqli_real_escape_string($connect, $ag_id) . "'";
    if (!empty($_GET['machine'])) $where .= " AND m.asset_name LIKE '%" . mysqli_real_escape_string($connect, $_GET['machine']) . "%'";
    if (!empty($_GET['checksheet'])) $where .= " AND c.name LIKE '%" . mysqli_real_escape_string($connect, $_GET['checksheet']) . "%'";
    if (!empty($_GET['type'])) $where .= " AND g.TGroupName LIKE '%" . mysqli_real_escape_string($connect, $_GET['type']) . "%'";
    if (!empty($_GET['location'])) $where .= " AND a.area_name LIKE '%" . mysqli_real_escape_string($connect, $_GET['location']) . "%'";

    // 1. นับจำนวนทั้งหมดสำหรับแสดง Pagination
    $countSql = "SELECT COUNT(*) as total 
                 FROM pm_plan_events p 
                 LEFT JOIN tb_ass_list m ON p.machine_id = m.ass_id 
                 LEFT JOIN tb_asset_group g ON m.asset_type = g.GroupId
                 LEFT JOIN tb_area a ON m.asset_rp_area_id = a.area_id
                 LEFT JOIN pm_checksheets c ON p.checksheet_id = c.id
                 $where";
    $totalRes = $connect->query($countSql);
    $response['total'] = $totalRes ? $totalRes->fetch_assoc()['total'] : 0;

    // 2. ดึงข้อมูลพร้อม Subquery นับประวัติการเลื่อน และจัดเรียงตามวันที่ใกล้ถึงก่อน
    $sql = "SELECT 
                p.id, 
                p.event_date, 
                p.status,
                m.ass_code AS qr_code,
                m.asset_name AS machine_name,
                g.TGroupName AS machine_type,
                a.area_name AS location_name,
                c.name AS checksheet_name,
                pl.frequency AS plan_frequency,
                (SELECT COUNT(*) FROM pm_plan_postpone_history WHERE plan_id = p.id) as postpone_count
            FROM pm_plan_events p
            LEFT JOIN tb_ass_list m ON p.machine_id = m.ass_id
            LEFT JOIN tb_asset_group g ON m.asset_type = g.GroupId
            LEFT JOIN tb_area a ON m.asset_rp_area_id = a.area_id
            LEFT JOIN pm_checksheets c ON p.checksheet_id = c.id
            LEFT JOIN pm_plans pl ON pl.checksheet_id = c.id 
            $where
            GROUP BY p.id 
            ORDER BY p.event_date ASC, p.id ASC 
            LIMIT $start, $limit";

    $result = $connect->query($sql);
    if ($result) {
        $events = [];
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
    } else {
        $response['error'] = $connect->error;
    }
}

echo json_encode($response);