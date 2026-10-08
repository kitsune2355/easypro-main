<?php
// save_repair_api2.php — บันทึกแจ้งซ่อม (ต่อจาก save_repair_api1.php) + วิดีโอ 1 คลิป (name="video") ใช้กับ maintenance_request.php
@session_start();
include __DIR__ . "/config_ctrl/connect.php";

/**
 * ✅ ป้องกัน Warning/Notice ไปปน JSON (แนะนำให้ปิดใน production)
 * ถ้าจะ debug ให้เปิดเองตามต้องการ
 */
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

date_default_timezone_set('Asia/Bangkok');
/**
 * เรียกส่งอีเมลแบบ Background
 * หน้าแจ้งซ่อมไม่ต้องรอ SMTP
 */
function triggerRepairEmailAsync($repairId)
{
    $repairId = (int) $repairId;

    if ($repairId <= 0) {
        return false;
    }

    $host = 'happylandgroup.biz';
    $path = '/es/send_repair_email.php';

    $postData = http_build_query([
        'repair_id' => $repairId
    ]);

    $errorNumber = 0;
    $errorMessage = '';

    $socket = @fsockopen(
        'ssl://' . $host,
        443,
        $errorNumber,
        $errorMessage,
        3
    );

    if (!$socket) {
        error_log(
            'triggerRepairEmailAsync connection failed. ' .
            'Repair ID: ' . $repairId .
            ' | Error: ' . $errorMessage .
            ' (' . $errorNumber . ')'
        );

        return false;
    }

    stream_set_timeout($socket, 3);

    $request =
        "POST {$path} HTTP/1.1\r\n" .
        "Host: {$host}\r\n" .
        "Content-Type: application/x-www-form-urlencoded; charset=UTF-8\r\n" .
        "Content-Length: " . strlen($postData) . "\r\n" .
        "Connection: Close\r\n" .
        "\r\n" .
        $postData;

    $written = @fwrite(
        $socket,
        $request
    );

    @fflush($socket);
    @fclose($socket);

    if ($written === false || $written <= 0) {
        error_log(
            'triggerRepairEmailAsync write failed. ' .
            'Repair ID: ' . $repairId
        );

        return false;
    }

    return true;
}

// -------------------------
// ✅ CORS Preflight
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['ok' => 1], JSON_UNESCAPED_UNICODE);
    exit();
}

// -------------------------
// ✅ ตรวจ DB
// -------------------------
if (!isset($connect) || $connect->connect_errno) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'DB connection failed'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// -------------------------
// ✅ helper
// -------------------------
function pick_post(array $keys, $default = null) {
    foreach ($keys as $k) {
        if (isset($_POST[$k])) {
            $v = $_POST[$k];
            if (is_string($v)) $v = trim($v);
            if ($v !== '' && $v !== null) return $v;
        }
    }
    return $default;
}

function respond_and_exit(mysqli $connect, int $code, array $payload) {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    $connect->close();
    exit();
}

// -------------------------
// ✅ include ฟังก์ชันเสริม (ใช้ path ให้ตรงกับ /public_html/es/...)
// -------------------------
$pushFile = __DIR__ . '/../API_es/send_push_notification.php';
$fmtFile  = __DIR__ . '/../API_es/gen_agency_format.php';

if (!file_exists($pushFile) || !file_exists($fmtFile)) {
    respond_and_exit($connect, 500, [
        'status' => 'error',
        'message' => 'Missing required include files',
        'debug' => [
            'send_push_notification.php' => $pushFile,
            'gen_agency_format.php' => $fmtFile
        ]
    ]);
}

require_once $pushFile;
require_once $fmtFile;

// -------------------------
// ✅ รับ request
// -------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond_and_exit($connect, 405, [
        'status' => 'error',
        'message' => 'Invalid request method. Only POST is allowed.'
    ]);
}

// -------------------------
// ✅ รับค่าจากฟอร์ม (รองรับ snake_case และ camelCase)
// -------------------------
$reportDate    = pick_post(['report_date', 'reportDate']);
$reportTime    = pick_post(['report_time', 'reportTime']);
$name          = pick_post(['name']);
$phone         = pick_post(['phone'], '');
$building      = pick_post(['building']);
$floor         = pick_post(['floor'], '');
$room          = pick_post(['room'], '');
$problemDetail = pick_post(['problem_detail', 'problemDetail']);
$status        = pick_post(['status'], 'pending');
$urgency       = pick_post(['urgency_input', 'urgency', 'urgencyInput'], '');

// ✅ created_by: POST มาก่อน ถ้าไม่มีใช้ session sess_user_id_es
$createdBy = pick_post(['created_by', 'createdBy'], $_SESSION['sess_user_id_es'] ?? '');

$ag_id      = $_SESSION['sess_user_agency'] ?? '';
$machine_id = pick_post(['asset_id'], null); // เผื่อฝั่งฟอร์มใช้ asset_id

// -------------------------
// ✅ Validate required
// -------------------------
if (empty($createdBy)) {
    respond_and_exit($connect, 401, [
        'status' => 'error',
        'message' => 'Missing created_by (not logged in or not provided).'
    ]);
}

if (empty($reportDate) || empty($reportTime) || empty($name) || empty($problemDetail) || empty($building)) {
    respond_and_exit($connect, 400, [
        'status' => 'error',
        'message' => 'Required fields are missing. Need: report_date/report_time/name/problem_detail/building/created_by'
    ]);
}

// -------------------------
// ✅ Upload Images - ใช้ชุดเดียวเท่านั้น
// ✅ หมายเหตุ: หน้าบ้านแปลง/ย่อเป็น JPG แล้ว จึงไม่ใช้ getimagesize()/resize ซ้ำใน API
// -------------------------
$uploaded_image_filenames = [];

// ✅ โฟลเดอร์จริงที่เก็บไฟล์บน server (อยู่ใต้ /public_html/es/API_es/uploads/)
$base_upload_dir = __DIR__ . "/../API_es/uploads/";

$current_year  = date('Y');
$current_month = date('m');

$target_dir_full_path = rtrim($base_upload_dir, "/") . "/" . $current_year . "/" . $current_month . "/";
$db_image_path        = $current_year . "/" . $current_month;

if (!is_dir($target_dir_full_path)) {
    if (!mkdir($target_dir_full_path, 0777, true)) {
        respond_and_exit($connect, 500, [
            'status' => 'error',
            'message' => 'ไม่สามารถสร้างโฟลเดอร์อัปโหลดได้',
            'debug' => [
                'target_dir' => $target_dir_full_path,
                'base_upload_dir' => $base_upload_dir
            ]
        ]);
    }
    @chmod($target_dir_full_path, 0777);
}

// ✅ รองรับ name="images[]" หรือ name="image[]"
$files = null;
if (isset($_FILES['images'])) {
    $files = $_FILES['images'];
} elseif (isset($_FILES['image'])) {
    $files = $_FILES['image'];
}

if ($files && isset($files['name']) && is_array($files['name'])) {

    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $file_count = count($files['name']);

    // ✅ กันส่งเกิน 5 ไฟล์
    if ($file_count > 5) {
        $file_count = 5;
    }

    for ($i = 0; $i < $file_count; $i++) {

        if (!isset($files['error'][$i])) {
            continue;
        }

        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            respond_and_exit($connect, 400, [
                'status' => 'error',
                'message' => 'อัปโหลดรูปไม่สำเร็จ Code: ' . $files['error'][$i] . ' : ' . $files['name'][$i]
            ]);
        }

        if ((int)$files['size'][$i] <= 0) {
            respond_and_exit($connect, 400, [
                'status' => 'error',
                'message' => 'ไฟล์รูปว่างหรืออัปโหลดไม่สมบูรณ์: ' . $files['name'][$i]
            ]);
        }

        // ✅ รับได้สูงสุด 20MB ต่อรูป เผื่อกรณีหน้าบ้านยังไม่บีบอัด
        if ((int)$files['size'][$i] > (20 * 1024 * 1024)) {
            respond_and_exit($connect, 400, [
                'status' => 'error',
                'message' => 'รูปใหญ่เกิน 20MB: ' . $files['name'][$i]
            ]);
        }

        $tmp_name = $files['tmp_name'][$i];

        if (!is_uploaded_file($tmp_name)) {
            respond_and_exit($connect, 400, [
                'status' => 'error',
                'message' => 'ไม่พบไฟล์อัปโหลดชั่วคราว: ' . $files['name'][$i]
            ]);
        }

        $originalName = basename($files['name'][$i]);
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // ✅ ถ้าไม่มีนามสกุล ให้ตั้งเป็น jpg
        if ($ext === '') {
            $ext = 'jpg';
        }

        if (!in_array($ext, $allowed_ext, true)) {
            respond_and_exit($connect, 400, [
                'status' => 'error',
                'message' => 'รองรับเฉพาะ JPG / JPEG / PNG / GIF / WEBP: ' . $originalName
            ]);
        }

        // ✅ ตั้งชื่อใหม่เอง ป้องกันชื่อซ้ำ/ชื่อแปลกจากมือถือ
        $file_name = uniqid('img_', true) . '.' . $ext;
        $target_file = $target_dir_full_path . $file_name;

        if (!move_uploaded_file($tmp_name, $target_file)) {
            respond_and_exit($connect, 500, [
                'status' => 'error',
                'message' => 'บันทึกรูปไม่สำเร็จ กรุณาตรวจสอบ permission โฟลเดอร์ uploads',
                'debug' => [
                    'target_dir' => $target_dir_full_path,
                    'is_dir' => is_dir($target_dir_full_path),
                    'is_writable' => is_writable($target_dir_full_path),
                    'tmp_name' => $tmp_name,
                    'file_name' => $file_name,
                    'target_file' => $target_file,
                    'last_error' => error_get_last()
                ]
            ]);
        }

        @chmod($target_file, 0644);

        // ✅ เก็บลง DB เป็น "YYYY/MM/filename"
        $uploaded_image_filenames[] = $db_image_path . "/" . $file_name;
    }
}

// -------------------------
// ✅ Upload Video (สูงสุด 1 คลิป, name="video")
// หน้าบ้านตัดให้ไม่เกิน 30 วินาทีและบีบอัด ≈ ไม่เกิน 10MB แล้ว — API รับได้ถึง 50MB เผื่อเบราว์เซอร์ที่บีบอัดไม่ได้
// เก็บโฟลเดอร์เดียวกับรูป: API_es/uploads/YYYY/MM/vid_xxx.ext และลง repair_requests.video_url เป็น "YYYY/MM/filename"
// -------------------------
$video_url = null;

if (isset($_FILES['video']) && !is_array($_FILES['video']['name']) && $_FILES['video']['error'] !== UPLOAD_ERR_NO_FILE) {
    $vf = $_FILES['video'];

    if ($vf['error'] === UPLOAD_ERR_INI_SIZE || $vf['error'] === UPLOAD_ERR_FORM_SIZE) {
        respond_and_exit($connect, 413, [
            'status' => 'error',
            'message' => 'วิดีโอใหญ่เกินที่เซิร์ฟเวอร์รับได้ กรุณาตัดวิดีโอให้สั้นลง'
        ]);
    }

    if ($vf['error'] !== UPLOAD_ERR_OK) {
        respond_and_exit($connect, 400, [
            'status' => 'error',
            'message' => 'อัปโหลดวิดีโอไม่สำเร็จ Code: ' . $vf['error']
        ]);
    }

    if ((int)$vf['size'] <= 0) {
        respond_and_exit($connect, 400, [
            'status' => 'error',
            'message' => 'ไฟล์วิดีโอว่างหรืออัปโหลดไม่สมบูรณ์'
        ]);
    }

    if ((int)$vf['size'] > (50 * 1024 * 1024)) {
        respond_and_exit($connect, 400, [
            'status' => 'error',
            'message' => 'วิดีโอใหญ่เกิน 50MB'
        ]);
    }

    if (!is_uploaded_file($vf['tmp_name'])) {
        respond_and_exit($connect, 400, [
            'status' => 'error',
            'message' => 'ไม่พบไฟล์วิดีโอชั่วคราว'
        ]);
    }

    $allowed_video_ext = ['webm', 'mp4', 'mov', 'm4v', '3gp', 'mkv'];
    $video_ext = strtolower(pathinfo(basename($vf['name']), PATHINFO_EXTENSION));

    // ✅ ไม่มีนามสกุล: เดาจากชนิดไฟล์ที่เบราว์เซอร์ส่งมา
    if ($video_ext === '') {
        $video_ext = (stripos((string)$vf['type'], 'mp4') !== false) ? 'mp4' : 'webm';
    }

    if (!in_array($video_ext, $allowed_video_ext, true)) {
        respond_and_exit($connect, 400, [
            'status' => 'error',
            'message' => 'รองรับเฉพาะวิดีโอ WEBM / MP4 / MOV: ' . basename($vf['name'])
        ]);
    }

    // ✅ ตรวจชนิดไฟล์จริง (กันไฟล์อื่นที่เปลี่ยนนามสกุลมา)
    $video_mime = function_exists('mime_content_type') ? (string)@mime_content_type($vf['tmp_name']) : '';
    if ($video_mime !== '' && strpos($video_mime, 'video/') !== 0 && $video_mime !== 'audio/webm' && $video_mime !== 'application/octet-stream') {
        respond_and_exit($connect, 400, [
            'status' => 'error',
            'message' => 'ไฟล์ที่เลือกไม่ใช่วิดีโอ'
        ]);
    }

    $video_file_name = uniqid('vid_', true) . '.' . $video_ext;
    $video_target = $target_dir_full_path . $video_file_name;

    if (!move_uploaded_file($vf['tmp_name'], $video_target)) {
        respond_and_exit($connect, 500, [
            'status' => 'error',
            'message' => 'บันทึกวิดีโอไม่สำเร็จ กรุณาตรวจสอบ permission โฟลเดอร์ uploads',
            'debug' => [
                'target_dir' => $target_dir_full_path,
                'is_dir' => is_dir($target_dir_full_path),
                'is_writable' => is_writable($target_dir_full_path),
                'last_error' => error_get_last()
            ]
        ]);
    }

    @chmod($video_target, 0644);
    $video_url = $db_image_path . "/" . $video_file_name;
}

$image_urls_json = json_encode($uploaded_image_filenames, JSON_UNESCAPED_UNICODE);

// -------------------------
// ✅ Insert Repair Request
// -------------------------
$rp_format = generate_rp_format($connect, $ag_id);

$sql_repair_request = "INSERT INTO repair_requests (
    report_date, report_time,
    name, phone, building, floor, room, problem_detail, urgency,
    image_url, video_url, created_by, status, ag_id, rp_format, machine_id, created_at
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt_repair = $connect->prepare($sql_repair_request);
if (!$stmt_repair) {
    respond_and_exit($connect, 500, [
        'status' => 'error',
        'message' => 'Error: Could not prepare repair request statement: ' . $connect->error
    ]);
}

$stmt_repair->bind_param(
    'ssssssssssssssss',
    $reportDate,
    $reportTime,
    $name,
    $phone,
    $building,
    $floor,
    $room,
    $problemDetail,
    $urgency,
    $image_urls_json,
    $video_url,
    $createdBy,
    $status,
    $ag_id,
    $rp_format,
    $machine_id
);

if (!$stmt_repair->execute()) {
    $err = $stmt_repair->error;
    $stmt_repair->close();
    respond_and_exit($connect, 500, [
        'status' => 'error',
        'message' => 'Error: Could not execute repair request query: ' . $err
    ]);
}

$last_inserted_repair_id = $connect->insert_id;
$stmt_repair->close();

$emailTriggered = triggerRepairEmailAsync(
    $last_inserted_repair_id
);

// -------------------------
// ✅ Insert repair_images
// -------------------------
if (!empty($uploaded_image_filenames)) {
    $sql_insert_image = "INSERT INTO repair_images (repair_request_id, path, file_name, created_by, created_at)
                         VALUES (?, ?, ?, ?, NOW())";

    $stmt_image = $connect->prepare($sql_insert_image);
    if ($stmt_image) {
        foreach ($uploaded_image_filenames as $full_image_path) {
            $just_file_name = basename($full_image_path);
            $stmt_image->bind_param(
                'isss',
                $last_inserted_repair_id,
                $db_image_path,
                $just_file_name,
                $createdBy
            );
            $stmt_image->execute();
        }
        $stmt_image->close();
    }
}

// -------------------------
// ✅ Notifications + Push
// -------------------------
$displayBuilding = '';
$displayFloor = '';
$displayRoom = '';

$sql_get_location_names = "
    SELECT
        ta.area_name AS building_name,
        tac.ac_name AS floor_name,
        tar.ar_name AS room_name
    FROM
        repair_requests rr
    LEFT JOIN
        tb_area ta ON rr.building = ta.area_id
    LEFT JOIN
        tb_area_class tac ON rr.floor = tac.ac_id
    LEFT JOIN
        tb_area_room tar ON rr.room = tar.ar_id
    WHERE
        rr.id = ? LIMIT 1";

if ($stmt_location = $connect->prepare($sql_get_location_names)) {
    $stmt_location->bind_param('i', $last_inserted_repair_id);
    $stmt_location->execute();
    $result_location = $stmt_location->get_result();
    if ($row_location = $result_location->fetch_assoc()) {
        $displayBuilding = $row_location['building_name'] ?? '';
        $displayFloor = $row_location['floor_name'] ?? '';
        $displayRoom = $row_location['room_name'] ?? '';
    }
    $stmt_location->close();
}

$notification_type = 'repair_request';

$sql_notify = "INSERT INTO notifications (type, related_id, rp_format, created_at)
               VALUES (?, ?, ?, NOW())";

$new_notification_id = null;

if ($stmt_notify = $connect->prepare($sql_notify)) {

    $stmt_notify->bind_param('sis', $notification_type, $last_inserted_repair_id, $rp_format);
    if ($stmt_notify->execute()) {
        $new_notification_id = $connect->insert_id;

        // 1) user_agency ของผู้แจ้ง
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

        // 2) admin ที่ agency เดียวกัน
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

        // 3) token ของ admin
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
                    if (!empty($row_token['token'])) $expo_tokens_to_send[] = $row_token['token'];
                }
                $stmt_tokens->close();
            }
        }

        // 4) push
        if (!empty($expo_tokens_to_send) && function_exists('send_push_notification')) {

            $urgency_badge = '';
            switch (strtolower($urgency)) {
                case 'critical':
                    $urgency_badge = ' [🔴 เร่งด่วนมาก]';
                    break;
                case 'high':
                    $urgency_badge = ' [🟠 สูง]';
                    break;
                case 'medium':
                    $urgency_badge = ' [🟡 ปานกลาง]';
                    break;
                case 'low':
                    $urgency_badge = ' [🟢 ต่ำ]';
                    break;
            }

            send_push_notification(
                $expo_tokens_to_send,
                '📢 ' . $rp_format . $urgency_badge,
                '📝 ' . $problemDetail . "\n" .
                '📍 ' . trim($displayBuilding . ' ' . $displayFloor . ' ' . $displayRoom),
                [
                    'screen' => 'RepairDetailScreen',
                    'params' => [
                        'repairId' => $last_inserted_repair_id,
                        'notificationId' => $new_notification_id,
                        'urgency' => strtolower($urgency)
                    ]
                ]
            );
        }
    }
    $stmt_notify->close();
}

// -------------------------
// ✅ Email Notifications
// -------------------------
//
//// 1. นำเข้าไลบรารี PHPMailer v5 (ปรับพาธให้ตรงกับตำแหน่งโฟลเดอร์ของคุณ)
//require_once __DIR__ . '/PHPMailer_v5.0.2/class.phpmailer.php';
//require_once __DIR__ . '/PHPMailer_v5.0.2/class.smtp.php';
//
//// 2. ดึงข้อมูลอีเมลจาก tb_email_notify ตามหน่วยงาน (ag_id) และสถานะเปิดใช้งาน (status = 1)
//$emails_to_send = [];
//$sql_get_emails = "SELECT name, email FROM tb_email_notify WHERE ag_id = ? AND status = 1 AND email != ''";
//
//if ($stmt_emails = $connect->prepare($sql_get_emails)) {
//    $stmt_emails->bind_param('s', $ag_id);
//    $stmt_emails->execute();
//    $result_emails = $stmt_emails->get_result();
//    while ($row_email = $result_emails->fetch_assoc()) {
//        if (filter_var($row_email['email'], FILTER_VALIDATE_EMAIL)) {
//            $emails_to_send[] = $row_email;
//        }
//    }
//    $stmt_emails->close();
//}
//
//// 3. หากมีอีเมลที่ต้องส่ง ให้ตั้งค่าและส่งอีเมล
//if (!empty($emails_to_send)) {
//    
//    // แปลงความเร่งด่วนเป็นภาษาไทย
//    $urgency_text = 'ไม่ระบุ';
//    switch (strtolower($urgency)) {
//        case 'critical': $urgency_text = '🔴 เร่งด่วนมาก'; break;
//        case 'high':     $urgency_text = '🟠 สูง'; break;
//        case 'medium':   $urgency_text = '🟡 ปานกลาง'; break;
//        case 'low':      $urgency_text = '🟢 ต่ำ'; break;
//    }
//
//    $location_text = trim($displayBuilding . ' ' . $displayFloor . ' ' . $displayRoom);
//    
//    // ตั้งค่าลิงก์สำหรับกดดูรายละเอียดจากอีเมล
//    $system_link = "https://happylandgroup.biz/es/main.php#maintenance_info.php?id=" . $last_inserted_repair_id;
//
//    foreach ($emails_to_send as $recipient) {
//        
//        // เตรียมเนื้อหาอีเมลตามรูปแบบ
//        $body = "
//        <div style='font-family: Kanit, Sarabun, Arial, sans-serif; line-height: 1.8; color: #334155; max-width: 650px; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px;'>
//            <h2 style='color: #0f172a; border-bottom: 2px solid #3b82f6; padding-bottom: 10px;'>แจ้งเตือนการแจ้งซ่อมใหม่</h2>
//            <p>เรียน <b>" . htmlspecialchars($recipient['name'], ENT_QUOTES, 'UTF-8') . "</b>,</p>
//            <p>ระบบได้รับการแจ้งซ่อมใหม่จากหน่วยงานของคุณ โดยมีรายละเอียดดังนี้:</p>
//            <p>เลขที่เอกสาร <b>" . htmlspecialchars($rp_format, ENT_QUOTES, 'UTF-8') . "</b></p>
//            <p>ผู้แจ้ง <b>" . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</b></p>
//            <p>เบอร์ติดต่อ <b>" . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . "</b></p>
//            <p>รายละเอียดปัญหา <b>" . htmlspecialchars($problemDetail, ENT_QUOTES, 'UTF-8') . "</b></p>
//            <p>สถานที่ <b>" . htmlspecialchars($location_text, ENT_QUOTES, 'UTF-8') . "</b></p>
//            <p>ระดับความเร่งด่วน <b>" . htmlspecialchars($urgency_text, ENT_QUOTES, 'UTF-8') . "</b></p>
//            <p>กรุณาตรวจสอบและดำเนินการตามขั้นตอนของระบบ</p>
//            <div style='margin: 30px 0; text-align: center;'>
//                <a href='" . htmlspecialchars($system_link, ENT_QUOTES, 'UTF-8') . "' style='display: inline-block; background-color: #f1f5f9; color: #0f172a; padding: 12px 20px; text-decoration: none; border-radius: 8px; font-weight: bold; border: 1px solid #cbd5e1;'>ดูรายละเอียดการแจ้งซ่อม</a>
//            </div>
//            <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
//            <p style='font-size: 13px;'>ระบบรับแจ้งซ่อมบำรุง (Maintenance System)</p>
//        </div>";
//
//        // 4. การตั้งค่า PHPMailer v5.0.2
//        $mail = new PHPMailer(true); // ใส่ true เพื่อให้จับ Error ผ่าน Exception ได้
//        try {
//            $mail->CharSet    = "utf-8";
//            $mail->IsSMTP();
//            $mail->SMTPAuth   = true;
//            $mail->Host       = "smtp.zoho.com";
//            $mail->Port       = 587;
//            $mail->SMTPSecure = "tls";
//            
//            $mail->Username   = "no-reply@proactivemanagement.co.th";
//            $mail->Password   = "***"; 
//
//            $mail->SetFrom("no-reply@proactivemanagement.co.th", "Maintenance System");
//            $mail->AddAddress($recipient['email'], $recipient['name']);
//            
//            $mail->Subject = "แจ้งเตือนงานซ่อมใหม่: " . $rp_format;
//            $mail->MsgHTML($body);
//            
//            $mail->Send();
//        } catch (phpmailerException $e) {
//            // ดัก Error ของ PHPMailer (ถ้าต้องการตรวจสอบ ให้ลอง echo $e->errorMessage(); ดูครับ)
//        } catch (Exception $e) {
//            // ดัก Error ทั่วไป
//        }
//    }
//}

// -------------------------
// ✅ Success response
// -------------------------
$connect->close();
echo json_encode([
    'status' => 'success',
    'message' => 'Repair request submitted successfully.',
    'image_urls' => $uploaded_image_filenames,
    'repair_id' => $last_inserted_repair_id,
    'inserted_id' => $last_inserted_repair_id,
    'rp_format' => $rp_format,
    'created_by' => $createdBy
], JSON_UNESCAPED_UNICODE);



