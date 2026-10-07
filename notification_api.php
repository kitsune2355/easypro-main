<?php
@session_start();
ini_set('display_errors', 0);

include "config_ctrl/connect.php";
include "send_push_notification.php"; // เรียกใช้ไฟล์ฟังก์ชัน Push Notification

header('Content-Type: application/json; charset=utf-8');

function formatDateThai($dateStr, $showTime = true) {
    // 1. ตรวจสอบค่าว่างหรือวันที่ 0000
    if (empty($dateStr) || $dateStr === '0000-00-00' || $dateStr === '0000-00-00 00:00:00') {
        return '-';
    }

    // 2. แปลงเป็น Timestamp
    $timestamp = strtotime($dateStr);
    if (!$timestamp) {
        return $dateStr; // ถ้าแปลงไม่ได้ ให้คืนค่าเดิมกลับไป
    }

    // 3. Array เดือนภาษาไทยแบบย่อ
    $thai_months = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];

    // 4. ประกอบร่าง วัน, เดือน(ไทย), ปี(พ.ศ.)
    $day = date('j', $timestamp); // วันที่แบบไม่มี 0 นำหน้า
    $month = $thai_months[(int)date('n', $timestamp)]; // เดือน
    $year = date('Y', $timestamp) + 543; // ปี พ.ศ.

    $datePart = "{$day} {$month} {$year}";

    // 5. คืนค่าตามเงื่อนไขการแสดงเวลา
    if (!$showTime) {
        return $datePart;
    }

    $timePart = date('H:i', $timestamp);
    return "{$datePart}, {$timePart} น.";
}

// ใช้ ID ของ User ที่ล็อกอินอยู่ (ปรับชื่อ Session ให้ตรงกับระบบคุณ)
$user_id = $_SESSION['sess_user_id_es'] ?? ''; 

if (empty($user_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

// รับข้อมูลจาก JS (รองรับทั้งการส่งแบบ GET และ POST JSON)
$data = json_decode(file_get_contents("php://input"), true);
$action = $_GET['action'] ?? ($data['action'] ?? '');

switch ($action) {

    // ----------------------------------------------------
    // [1] ดึงข้อมูลแจ้งเตือนไปแสดงบนเว็บ
    // ----------------------------------------------------
    case 'get':
        // =========================================================
        // 🚀 ส่วนที่ 1: ระบบจำลอง Cron Job สร้างแจ้งเตือน PM
        // =========================================================
        $cron_log_file = __DIR__ . '/last_pm_alert_run.txt'; 
        $today = date('Y-m-d');
        
        $last_run = file_exists($cron_log_file) ? trim(file_get_contents($cron_log_file)) : '';
        if ($last_run !== $today) { 
            
            // 🌟 1. ตรวจสอบแจ้งเตือนล่วงหน้า (Advance Alert)
            // เพิ่มเงื่อนไข p.alert_value > 0 เพื่อไม่ให้ซ้ำซ้อนกับของวันนี้
            $sql_check_advance = "
                SELECT 
                    e.id AS event_id,
                    e.event_date,
                    p.id AS plan_id,
                    m.asset_name AS machine_name,
                    p.alert_value
                FROM pm_plan_events e
                JOIN pm_plans p ON e.plan_id = p.id
                LEFT JOIN tb_ass_list m ON p.machine_id = m.ass_id
                WHERE e.status = 0 
                  AND p.alert_value > 0 
                  AND DATE_SUB(e.event_date, INTERVAL p.alert_value DAY) = CURDATE()
            ";
            
            $res_advance = mysqli_query($connect, $sql_check_advance);
            if ($res_advance && mysqli_num_rows($res_advance) > 0) {
                $sql_insert = "INSERT INTO notifications (type, related_id, rp_format, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP())";
                $stmt_insert = mysqli_prepare($connect, $sql_insert);
                
                while ($row = mysqli_fetch_assoc($res_advance)) {
                    $event_id = $row['event_id'];
                    $event_date_th = formatDateThai($row['event_date'], false);
                    $advance_days = $row['alert_value']; 
                    $machine_name = $row['machine_name'] ?: '';
                    
                    $type = 'pm_advance_alert';
                    $related_id = $event_id;
                    $rp_format = "แจ้งเตือน PM ล่วงหน้า $advance_days วัน $machine_name (กำหนดทำ $event_date_th)";
                    
                    $check_dup_sql = "SELECT id FROM notifications WHERE type = '$type' AND related_id = '$related_id' LIMIT 1";
                    $dup_result = mysqli_query($connect, $check_dup_sql);
                    
                    if (mysqli_num_rows($dup_result) == 0) {
                        mysqli_stmt_bind_param($stmt_insert, "sis", $type, $related_id, $rp_format);
                        mysqli_stmt_execute($stmt_insert);
                    }
                }
                if ($stmt_insert) mysqli_stmt_close($stmt_insert);
            }

            // 🌟 2. ตรวจสอบแจ้งเตือนสำหรับ "วันนี้" (Today Alert) - แบบ Group รวมเป็น 1 แจ้งเตือน
            $sql_check_today = "
                SELECT 
                    MIN(e.id) AS related_event_id,
                    COUNT(e.id) AS total_tasks,
                    GROUP_CONCAT(m.asset_name SEPARATOR ', ') AS machine_names
                FROM pm_plan_events e
                JOIN pm_plans p ON e.plan_id = p.id
                LEFT JOIN tb_ass_list m ON p.machine_id = m.ass_id
                WHERE e.status = 0 
                  AND DATE(e.event_date) = CURDATE()
                GROUP BY p.ag_id
            ";
            
            $res_today = mysqli_query($connect, $sql_check_today);
            if ($res_today && mysqli_num_rows($res_today) > 0) {
                $sql_insert = "INSERT INTO notifications (type, related_id, rp_format, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP())";
                $stmt_insert = mysqli_prepare($connect, $sql_insert);
                
                while ($row = mysqli_fetch_assoc($res_today)) {
                    $related_id = $row['related_event_id'];
                    $total_tasks = $row['total_tasks'];
                    $machines_str = $row['machine_names'] ?: 'ไม่ระบุชื่อเครื่อง';
                    
                    $type = 'pm_today_alert';
                    $formatted_list = "- " . str_replace(', ', "\n- ", $machines_str);
                    $rp_format = "ต้องทำ PM วันนี้ $total_tasks รายการ:\n" . $formatted_list;
                    $check_dup_sql = "SELECT id FROM notifications WHERE type = '$type' AND related_id = '$related_id' LIMIT 1";
                    $dup_result = mysqli_query($connect, $check_dup_sql);
                    
                    if (mysqli_num_rows($dup_result) == 0) {
                        mysqli_stmt_bind_param($stmt_insert, "sis", $type, $related_id, $rp_format);
                        mysqli_stmt_execute($stmt_insert);
                    }
                }
                if ($stmt_insert) mysqli_stmt_close($stmt_insert);
            }
            
            file_put_contents($cron_log_file, $today);
            
        }
        // =========================================================
        // 🏁 สิ้นสุดระบบจำลอง Cron Job
        // =========================================================
        
        $user_id = $_SESSION['sess_user_id_es'] ?? '';
        $user_agency = $_SESSION['sess_user_agency'] ?? ''; 
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10; 
        $offset = ($page - 1) * $limit;

        // ---------------------------------------------------------
        // 1. นับจำนวน unread (🌟 อัปเดตเพิ่ม LEFT JOIN ของ PM)
        // ---------------------------------------------------------
        $sql_count = "SELECT COUNT(n.id) as total_unread
                      FROM notifications n 
                      LEFT JOIN repair_requests rep ON n.related_id = rep.id AND n.type IN ('repair_request', 'repair_completed')
                      LEFT JOIN tb_meter tm ON n.related_id = tm.mt_id AND n.type = 'meter_exceeded'
                      LEFT JOIN tb_repair_product trp ON n.related_id = trp.pd_id AND n.type = 'low_stock'
                      LEFT JOIN pm_plan_events pe ON n.related_id = pe.id AND n.type IN ('pm_advance_alert', 'pm_today_alert')
                      LEFT JOIN pm_plans pmp ON pe.plan_id = pmp.id
                      LEFT JOIN notification_reads r ON n.id = r.notification_id AND r.user_id = ?
                      WHERE (rep.ag_id = ? OR tm.mr_ag_id = ? OR trp.ag_id = ? OR pmp.ag_id = ?) 
                      AND (rep.created_by != ? OR rep.created_by IS NULL) 
                      AND r.read_at IS NULL";
        
        $stmt_count = $connect->prepare($sql_count);
        // 🌟 แก้ไข bind_param เป็น 6 ตัว ("ssssss")
        $stmt_count->bind_param("ssssss", $user_id, $user_agency, $user_agency, $user_agency, $user_agency, $user_id);
        $stmt_count->execute();
        $result_count = $stmt_count->get_result();
        $row_count = $result_count->fetch_assoc();
        $total_unread = $row_count['total_unread'] ?? 0;
        $stmt_count->close();

        // ---------------------------------------------------------
        // 2. คิวรี่ดึงข้อมูลมาแสดงผล (🌟 อัปเดตเพิ่ม LEFT JOIN ของ PM)
        // ---------------------------------------------------------
        $sql = "SELECT n.id AS notif_id, n.type, n.related_id, n.rp_format, n.created_at, 
                       IF(r.read_at IS NOT NULL, 1, 0) AS is_read, rep.problem_detail, rep.urgency,
                       ta.area_name AS building_name,
                       tac.ac_name AS floor_name,
                       tar.ar_name AS room_name,
                       tm.mt_name
                FROM notifications n
                LEFT JOIN repair_requests rep ON n.related_id = rep.id AND n.type IN ('repair_request', 'repair_completed')
                LEFT JOIN tb_meter tm ON n.related_id = tm.mt_id AND n.type = 'meter_exceeded'
                LEFT JOIN tb_repair_product trp ON n.related_id = trp.pd_id AND n.type = 'low_stock'
                LEFT JOIN pm_plan_events pe ON n.related_id = pe.id AND n.type IN ('pm_advance_alert', 'pm_today_alert')
                LEFT JOIN pm_plans pmp ON pe.plan_id = pmp.id
                LEFT JOIN tb_area ta ON rep.building = ta.area_id
                LEFT JOIN tb_area_class tac ON rep.floor = tac.ac_id
                LEFT JOIN tb_area_room tar ON rep.room = tar.ar_id
                LEFT JOIN notification_reads r ON n.id = r.notification_id AND r.user_id = ?
                WHERE (rep.ag_id = ? OR tm.mr_ag_id = ? OR trp.ag_id = ? OR pmp.ag_id = ?) 
                AND (rep.created_by != ? OR rep.created_by IS NULL)
                ORDER BY n.created_at DESC 
                LIMIT ?, ?";

        $stmt = $connect->prepare($sql);
        // 🌟 แก้ไข bind_param เป็น 8 ตัว ("ssssssii")
        $stmt->bind_param("ssssssii", $user_id, $user_agency, $user_agency, $user_agency, $user_agency, $user_id, $offset, $limit); 
        $stmt->execute();
        $result = $stmt->get_result();

        $notifications = [];

        while ($row = $result->fetch_assoc()) {
            $isRead = (bool)$row['is_read'];
            
            $title_text = 'แจ้งเตือนใหม่';
            $detail_text = '-';
            $link_url = '#'; 

            $urgency_badge = ''; // สำหรับแสดงบนเว็บ
            $urgency_plain = ''; // สำหรับแจ้งเตือนภายนอก

            if (!empty($row['urgency'])) {
                switch (strtolower($row['urgency'])) {
                    case 'critical': 
                        $urgency_badge = ' <span class="inline-flex items-center px-2 py-0.5 mr-1 text-[10px] font-medium text-white bg-red-500 rounded-full">ด่วนมาก</span>'; 
                        $urgency_plain = '[🔴 ด่วนมาก] ';
                        break;
                    case 'high':     
                        $urgency_badge = ' <span class="inline-flex items-center px-2 py-0.5 mr-1 text-[10px] font-medium text-white bg-orange-500 rounded-full">ด่วน</span>'; 
                        $urgency_plain = '[🟠 ด่วน] ';
                        break;
                    case 'medium':   
                        $urgency_badge = ' <span class="inline-flex items-center px-2 py-0.5 mr-1 text-[10px] font-medium text-amber-800 bg-yellow-300 rounded-full">ปานกลาง</span>'; 
                        $urgency_plain = '[🟡 ปานกลาง] ';
                        break;
                    case 'low':      
                        $urgency_badge = ' <span class="inline-flex items-center px-2 py-0.5 mr-1 text-[10px] font-medium text-white bg-green-500 rounded-full">ทั่วไป</span>'; 
                        $urgency_plain = '[🟢 ทั่วไป] ';
                        break;
                }
            }
            
            // แยกประเภทข้อความ
            if ($row['type'] === 'repair_request') {
                $title_text = '🔧 มีการแจ้งซ่อมใหม่';
                $title_display = $urgency_badge . $row['rp_format'];
                $link_url = "maintenance_info.php?id=" . $row['related_id'];
            } elseif ($row['type'] === 'repair_completed') {
                $title_text = '✅ ซ่อมเสร็จแล้ว';
                $title_display = '✅ ' . $row['rp_format'] . " ซ่อมเสร็จแล้ว";
                $link_url = "maintenance_info.php?id=" . $row['related_id'];
            } elseif ($row['type'] === 'meter_exceeded') {
                $title_text = '🚨 ค่ามิเตอร์เกินกำหนด';
                $link_url = "meter_info.php?" . $row['rp_format'];
                $title_display = $title_text;
            } elseif ($row['type'] === 'low_stock') {
                $title_text = '⚠️ สต็อกสินค้าเหลือน้อย';
                $title_display = $title_text;
                $link_url = "stock.php?pd_id=" . $row['related_id']; 
            } elseif ($row['type'] === 'pm_advance_alert') { 
                $title_text = '📅 กำหนดการบำรุงรักษา (PM)';
                $title_display = $title_text;
                $link_url = "pm.php"; 
            } elseif ($row['type'] === 'pm_today_alert') { 
                $title_text = '🚀 แจ้งเตือน PM';
                $title_display = $title_text;
                $link_url = "pm.php"; 
            }
            
            // จัดการ Detail Text
            if ($row['type'] === 'meter_exceeded') {
                parse_str($row['rp_format'], $params);
                $month_name = '';
                $year_th = '';
                if (isset($params['month']) && isset($params['year'])) {
                    $thai_months = [
                        1 => "มกราคม", 2 => "กุมภาพันธ์", 3 => "มีนาคม", 4 => "เมษายน",
                        5 => "พฤษภาคม", 6 => "มิถุนายน", 7 => "กรกฎาคม", 8 => "สิงหาคม",
                        9 => "กันยายน", 10 => "ตุลาคม", 11 => "พฤศจิกายน", 12 => "ธันวาคม"
                    ];
                    $month_num = (int)$params['month'];
                    $month_name = $thai_months[$month_num] ?? '';
                    $year_th = (int)$params['year'] + 543;
                }
                $mt_name = $row['mt_name'] ?? 'ไม่ทราบชื่อ';
                $detail_text = "พบการใช้งานผิดปกติ มิเตอร์: {$mt_name}";
                if ($month_name !== '') {
                    $detail_text .= " (ประจำเดือน {$month_name} {$year_th}) กรุณาตรวจสอบ";
                } else {
                    $detail_text .= " กรุณาตรวจสอบ";
                }
            } elseif ($row['type'] === 'low_stock') { 
                $detail_text = $row['rp_format'] ?: 'มีสินค้าจำนวนเหลือน้อยกว่าหรือเท่ากับจุดสั่งซื้อ';
            } elseif ($row['type'] === 'pm_advance_alert') {
                $detail_text = $row['rp_format'] ?: 'มีกำหนดการทำ PM เร็วๆ นี้';
            } elseif ($row['type'] === 'pm_today_alert') {
                $detail_text = $row['rp_format'] ?: 'มีกำหนดการทำ PM ภายในวันนี้ กรุณาตรวจสอบ';
            } else {
                $displayBuilding = $row['building_name'] ?? '';
                $displayFloor = $row['floor_name'] ?? '';
                $displayRoom = $row['room_name'] ?? '';
                $location_info = trim($displayBuilding . ' ' . $displayFloor . ' ' . $displayRoom);

                $detail_base = $row['problem_detail'] ?: '-';
                $detail_text = ($location_info !== '') ? $detail_base . ' ' . $location_info : $detail_base;
            }

            $created_date = date('Y-m-d', strtotime($row['created_at']));
            $today_date = date('Y-m-d');
            
            if ($created_date == $today_date) {
                $date_group = "วันนี้";
            } else {
                $date_group = formatDateThai($created_date, false);
            }

            $notifications[] = [
                'id'         => $row['notif_id'],
                'type'       => $row['type'],       
                'related_id' => $row['related_id'], 
                'title'      => $title_display,
                'title_plain'=> strip_tags($urgency_plain),
                'detail'     => $detail_text, 
                'urgency'    => $row['urgency'] ?? '',
                'time'       => date('H:i', strtotime($row['created_at'])) . ' น.',
                'date_group' => $date_group,
                'isRead'     => $isRead,
                'url'        => $link_url 
            ];
        }

        echo json_encode([
            'status' => 'success', 
            'data' => $notifications, 
            'unread_count' => $total_unread 
        ]);
        break;

    // ----------------------------------------------------
    // [2] บันทึกว่าผู้ใช้อ่านแจ้งเตือนแล้ว
    // ----------------------------------------------------
    case 'read':
        $notification_id = $data['notification_id'] ?? '';
        
        if ($notification_id === 'all') {
            $sql = "INSERT IGNORE INTO notification_reads (user_id, notification_id, read_at) 
                    SELECT ?, id, NOW() FROM notifications";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
        } elseif (!empty($notification_id)) {
            $sql = "INSERT IGNORE INTO notification_reads (user_id, notification_id, read_at) VALUES (?, ?, NOW())";
            $stmt = $connect->prepare($sql);
            $stmt->bind_param("ss", $user_id, $notification_id);
            $stmt->execute();
        }

        echo json_encode(['status' => 'success']);
        break;

    // ----------------------------------------------------
    // [3] เรียกใช้งานส่งแจ้งเตือนไปที่แอป (Push Notification)
    // ----------------------------------------------------
    case 'send_push':
        // รับตัวแปร createdBy (ID คนแจ้งซ่อม) มาจากฝั่ง JavaScript แทนการรับ tokens ตรงๆ
        $createdBy = $data['createdBy'] ?? ''; 
        $title = $data['title'] ?? 'แจ้งเตือนระบบ';
        $body = $data['body'] ?? '';
        $extraData = $data['extraData'] ?? [];

        if (empty($createdBy)) {
            echo json_encode(['status' => 'error', 'message' => 'Creator ID is missing']);
            exit;
        }

        // 1) หา user_agency ของผู้แจ้ง
        $user_agency = null;
        $sql_get_agency = "SELECT user_agency FROM tb_user WHERE user_id = ?";
        if ($stmt_agency = $connect->prepare($sql_get_agency)) {
            $stmt_agency->bind_param('s', $createdBy);
            $stmt_agency->execute();
            $result_agency = $stmt_agency->get_result();
            if ($row_agency = $result_agency->fetch_assoc()) {
                $user_agency = $row_agency['user_agency'] ?? null;
            }
            $stmt_agency->close();
        }
         
        // 2) หา admin ที่อยู่ agency เดียวกัน (และไม่ใช่ตัวผู้แจ้งเอง)
        $admin_user_ids = [];
        if (!empty($user_agency)) {
            $sql_admins = "SELECT user_id FROM tb_user
                           WHERE user_level = 'admin' AND user_id != ? AND user_agency = ?";
            if ($stmt_admins = $connect->prepare($sql_admins)) {
                $stmt_admins->bind_param('ss', $createdBy, $user_agency);
                $stmt_admins->execute();
                $result_admins = $stmt_admins->get_result();
                while ($row_admin = $result_admins->fetch_assoc()) {
                    $admin_user_ids[] = $row_admin['user_id'];
                }
                $stmt_admins->close();
            }
        }

        // 3) หา token ของ admin กลุ่มนั้น
        $expo_tokens_to_send = [];
        if (!empty($admin_user_ids)) {
            $in = str_repeat('?,', count($admin_user_ids) - 1) . '?';
            $sql_tokens = "SELECT token FROM expo_tokens WHERE user_id IN ($in)";
            if ($stmt_tokens = $connect->prepare($sql_tokens)) {
                $types = str_repeat('s', count($admin_user_ids));
                $stmt_tokens->bind_param($types, ...$admin_user_ids);
                $stmt_tokens->execute();
                $result_tokens = $stmt_tokens->get_result();
                while ($row_token = $result_tokens->fetch_assoc()) {
                    if (!empty($row_token['token'])) {
                        $expo_tokens_to_send[] = $row_token['token'];
                    }
                }
                $stmt_tokens->close();
            }
        }

        // เช็คว่ามี Token ที่พร้อมจะส่งหรือไม่
        if (empty($expo_tokens_to_send) || !function_exists('send_push_notification')) {
            echo json_encode(['status' => 'error', 'message' => 'No admin tokens found or function error']);
            exit;
        }

        // เรียกฟังก์ชันส่ง Push Notification
        $push_result = send_push_notification($expo_tokens_to_send, $title, $body, $extraData);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Push sent to agency admins successfully', 
            'expo_result' => json_decode($push_result)
        ]);
        break;

    // ----------------------------------------------------
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
?>