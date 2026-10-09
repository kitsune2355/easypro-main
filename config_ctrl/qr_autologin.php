<?php
error_reporting(E_ALL ^ E_NOTICE);
@session_start();

// 1. รับค่า Username จาก QR Code (ถ้าไม่มีให้ default หรือแจ้งเตือน)
$u = isset($_GET['u']) ? trim($_GET['u']) : '';

if (empty($u)) {
    die("QR Code ไม่ถูกต้อง หรือไม่มีการระบุหน่วยงาน");
}

// connect DB
include "connect.php";
include_once "agency_contract.php";   // ตรวจหน่วยงานหมดสัญญา

$user_login = mysqli_real_escape_string($connect, $u);

// กำหนดตัวแปรระบบ
$db_short_name = '82';

// เลือก server ตาม host
$SERVER_HOST = $_SERVER['HTTP_HOST'];
if ($SERVER_HOST == "localhost") {
    $server_id = 'db_job_'.$db_short_name;
} else {
    $server_id = 'happylandc_wha';
}

$sql = "
    SELECT tbUsr.*
    FROM tb_user AS tbUsr
    WHERE tbUsr.user_id = '$user_login'
";

$query    = mysqli_query($connect, $sql) or die(mysqli_error($connect));
$num_rows = mysqli_num_rows($query);

// หากพบ User
if ($num_rows >= 1) {
    $result = mysqli_fetch_array($query);

    // Set Session ทั้งหมดให้เหมือนการล็อกอินปกติ (ข้อมูล password จะถูกดึงจากตาราง tb_user อัตโนมัติ)
    $_SESSION['sess_id_es']              = session_id(); 
    $_SESSION['sess_member_last_name']   = $result["member_last_name"];  
    $_SESSION['sess_user_id_es']         = $result["user_id"]; 
    $_SESSION['sess_user_name_es']       = $result["user_name"];
    $_SESSION['sess_user_fname_es']      = $result["user_fname"];
    $_SESSION['sess_user_password_es']   = $result["user_password"]; // <== ใช้ข้อมูลจากตารางจริงที่นี่
    $_SESSION['sess_user_level_es']      = $result["user_level"];
    $_SESSION['sess_user_email_es']      = $result["user_email"];
    $_SESSION['sess_user_dep_id_es']     = $result["user_dep_id"];
    $_SESSION['sess_user_agency']        = $result["user_agency"];
    $_SESSION['sess_status_login_es']    = $result["user_status_login"];
    $_SESSION['sess_user_position_es']   = $result["user_position"];
    $_SESSION['sess_position_level_es']  = $result["user_position_level"]; 
    $_SESSION['sess_SYSTEM_CTRL']        = $db_short_name;
    $_SESSION['sess_server_id']          = $server_id;
    $_SESSION['sess_tmp_usr_id']         = date('His');
    $_SESSION['sess_SERVER_HOST']        = $SERVER_HOST;
    $_SESSION['session_time']            = time(); 

    // บันทึก Log การเข้าใช้งาน
    $sql_log = "
        INSERT INTO tb_time_login 
        SET `tl_name` = '".$result["user_id"]."', 
            `tl_time` = NOW()
    ";
    mysqli_query($connect, $sql_log);

    // ดึงข้อมูล Agency พื้นฐาน (ตาม Logic ปกติ)
    $ag_id = (int)$result["user_agency"];
    if ($ag_id > 0) {
        $sql_ag = "SELECT ag_id, ag_contract, ag_job, ag_start_date, ag_end_date 
                   FROM tb_agency WHERE ag_id = {$ag_id} AND ag_status = 1 LIMIT 1";
        $q_ag = mysqli_query($connect, $sql_ag);
        if ($r_ag = mysqli_fetch_assoc($q_ag)) {
            // หน่วยงานหมดสัญญา => เข้าสู่ระบบด้วย QR ไม่ได้
            if (agc_is_expired($r_ag['ag_end_date'])) {
                agc_block(agc_message([['name' => $r_ag['ag_contract'], 'end' => $r_ag['ag_end_date']]]));
            }
            $_SESSION['sess_user_agency'] = (int)$r_ag['ag_id'];
            $_SESSION['sess_agency_code'] = $r_ag['ag_job'];
            $_SESSION['sess_agency_name'] = $r_ag['ag_contract'];
            $_SESSION['sess_ag_start_date'] = $r_ag['ag_start_date'];
            $_SESSION['sess_ag_end_date'] = $r_ag['ag_end_date'];
            $_SESSION['is_qr_user'] = true;
        }
    }
    $_SESSION['needAgencyPopupES'] = 0;

    // ล็อกอินสำเร็จ -> ส่งไปหน้าเมนูแจ้งซ่อมโดยตรง
    header('Location: ../main2.php#maintenance_request.php');
    exit();

} else {
    // กรณีที่หา user นี้ไม่พบ
    $_SESSION['login_error'] = 'ไม่พบสิทธิ์การใช้งานของบัญชีผู้แจ้งซ่อม';
    header('Location: ../login.php');
    exit();
}
?>