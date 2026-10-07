<?php
error_reporting(E_ALL ^ E_NOTICE);
@session_start();

$user_login    = isset($_POST['LogInName'])      ? trim($_POST['LogInName'])      : '';
$pass_login    = isset($_POST['LogInPassWord'])  ? trim($_POST['LogInPassWord'])  : '';
$LoginTypeH    = isset($_POST['LoginTypeH'])     ? $_POST['LoginTypeH']          : '';
$server_id     = isset($_POST['server_id'])      ? $_POST['server_id']           : '';
$server_id_tmp = $server_id;
$db_short_name = '82';

// ถ้าไม่กรอก user/pass เลย ให้เด้งกลับไปหน้า login
if ($user_login === '' || $pass_login === '') {
    $_SESSION['login_error'] = 'กรุณากรอกชื่อผู้ใช้งาน และรหัสผ่าน';
    header('Location: ../index.php');
    exit();
}
 
    $server_id = 'happylandc_wha';
 

// connect DB
include "connect.php";

// เข้ารหัสรหัสผ่านแบบเดิม
$pass_login_md5 = md5(md5(md5($pass_login)));

// ดึง user
$sql = "
    SELECT tbUsr.*
    FROM tb_user AS tbUsr
    WHERE tbUsr.user_id = '$user_login'
      AND tbUsr.user_password = '$pass_login_md5'
";
// echo $sql; // ถ้าจะ debug ค่อยเปิด

$query    = mysqli_query($connect, $sql) or die(mysqli_error($connect));
$num_rows = mysqli_num_rows($query);

// ---------- กรณีเจอ user (ล็อกอินสำเร็จ) ----------
if ($num_rows >= 1) {

    $result = mysqli_fetch_array($query) or die(mysqli_error($connect));

    // set session เดิมของระบบ
    $_SESSION['sess_id_es']              = session_id(); 
    $_SESSION['sess_member_last_name']   = $result["member_last_name"];  
    $_SESSION['sess_user_id_es']         = $result["user_id"]; 
    $_SESSION['sess_user_name_es']       = $result["user_name"];
    $_SESSION['sess_user_fname_es']      = $result["user_fname"];
    $_SESSION['sess_user_password_es']   = $result["user_password"];
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

    $ag_id = $result["user_agency"];

    // log เวลาเข้าใช้งาน
    $sql_log = "
        INSERT INTO tb_time_login 
        SET `tl_name` = '".$result["user_id"]."', 
            `tl_time` = NOW()
    ";
    $res_m = mysqli_query($connect, $sql_log) or die(mysqli_error($connect));

    // ดึงสิทธิ์ agency employer (โค้ดเดิม ไม่ได้ใช้ $Tem_Ag ต่อ แต่ผมคงไว้ให้)
    $Tem_Ag = '';
    $sql_emp = "
        SELECT * 
        FROM tb_agency_employer
        WHERE age_user_id = '".$result["user_id"]."'
    ";
    $query_emp = mysqli_query($connect, $sql_emp) or die(mysqli_error($connect));
    $num_rows_emp = mysqli_num_rows($query_emp);
    if ($num_rows_emp >= 1) {
        while ($rs_data = mysqli_fetch_array($query_emp)) {
            if ($Tem_Ag == "") {
                $Tem_Ag = "'".$rs_data['age_ag_id']."'";
            } else {
                $Tem_Ag .= ",'".$rs_data['age_ag_id']."'";
            }
        }
    }

    // ---------- เช็คสิทธิ์ & สถานะ login ----------

 if ($_SESSION['sess_status_login_es'] == '1') {

        // super_admin
        if ($_SESSION['sess_user_level_es'] == 'super_admin') {
            $_SESSION['needAgencyPopupES'] = 0;
            header('Location: ../main.php');
            exit();
        }

        // employer
        if ($_SESSION['sess_user_level_es'] == 'employer') {

    // ถ้ามีหน่วยงานที่เลือกไว้แล้ว ให้เข้าใช้งานได้เลย
   			 if (isset($_SESSION['sess_user_agency']) && $_SESSION['sess_user_agency'] != '0') {
			 		$sql_multi_ag = "
							SELECT a.ag_id, a.ag_job, a.ag_contract, ag_start_date, ag_end_date
							FROM tb_agency a 
							WHERE a.ag_id = '".$_SESSION['sess_user_agency']."'
							  AND a.ag_status = 1
							ORDER BY a.ag_contract ASC
						";
						$q_multi_ag = mysqli_query($connect, $sql_multi_ag);
					
						if (!$q_multi_ag) {
							$_SESSION['login_error'] = 'เกิดข้อผิดพลาดในการโหลดหน่วยงาน';
							header('Location: ../login.php');
							exit();
						}
					
						while ($r = mysqli_fetch_assoc($q_multi_ag)) {
							$agencyList[] = [
								'ag_id' => (int)$r['ag_id'],
								'ag_job' => $r['ag_job'],
								'ag_contract' => $r['ag_contract'],
								'ag_start_date' => $r['ag_start_date'],
								'ag_end_date' => $r['ag_end_date']
							];
						}
					
						if (count($agencyList) <= 0) {
							$_SESSION['login_error'] = 'ไม่พบหน่วยงานที่เปิดใช้งานสำหรับผู้ใช้นี้';
							header('Location: ../login.php');
							exit();
						}
					
						$_SESSION['sess_user_agencies'] = $agencyList;
					
						if (count($agencyList) === 1) {
							$_SESSION['sess_user_agency'] 	= (int)$agencyList[0]['ag_id'];
							$_SESSION['sess_agency_code'] 	= $agencyList[0]['ag_job'];
							$_SESSION['sess_agency_name'] 	= $agencyList[0]['ag_contract'];
							$_SESSION['sess_ag_start_date'] = $agencyList[0]['ag_start_date'];
							$_SESSION['sess_ag_end_date'] 	= $agencyList[0]['ag_end_date'];
							$_SESSION['needAgencyPopupES'] 	= 0;
						} else {
							$_SESSION['sess_user_agency'] = 0;
							$_SESSION['sess_agency_code'] = '';
							$_SESSION['sess_agency_name'] = '';
							$_SESSION['needAgencyPopupES'] = 1;
						}
					
					header('Location: ../main.php');
					exit();
				}
			
				$agencyList = []; 
				$sql_multi_ag = "
					SELECT a.ag_id, a.ag_job, a.ag_contract, ag_start_date, ag_end_date
					FROM tb_agency_employer e
					INNER JOIN tb_agency a ON a.ag_id = e.age_ag_id
					WHERE e.age_user_id = '".mysqli_real_escape_string($connect, $result["user_id"])."'
					  AND a.ag_status = 1
					ORDER BY a.ag_contract ASC
				";
				$q_multi_ag = mysqli_query($connect, $sql_multi_ag);
			
				if (!$q_multi_ag) {
					$_SESSION['login_error'] = 'เกิดข้อผิดพลาดในการโหลดหน่วยงาน';
					header('Location: ../login.php');
					exit();
				}
			
				while ($r = mysqli_fetch_assoc($q_multi_ag)) {
					$agencyList[] = [
						'ag_id' => (int)$r['ag_id'],
						'ag_job' => $r['ag_job'],
						'ag_contract' => $r['ag_contract'],
						'ag_start_date' => $r['ag_start_date'],
						'ag_end_date' => $r['ag_end_date']
					];
				}
			
				if (count($agencyList) <= 0) {
					$_SESSION['login_error'] = 'ไม่พบหน่วยงานที่เปิดใช้งานสำหรับผู้ใช้นี้';
					header('Location: ../login.php');
					exit();
				}
			
				$_SESSION['sess_user_agencies'] = $agencyList;
			
				if (count($agencyList) === 1) {
					$_SESSION['sess_user_agency'] = (int)$agencyList[0]['ag_id'];
					$_SESSION['sess_agency_code'] = $agencyList[0]['ag_job'];
					$_SESSION['sess_agency_name'] = $agencyList[0]['ag_contract'];
					$_SESSION['sess_ag_start_date'] 		= $agencyList[0]['ag_start_date'];
					$_SESSION['sess_ag_end_date'] 		= $agencyList[0]['ag_end_date'];
					$_SESSION['needAgencyPopupES'] = 0;
				} else {
					$_SESSION['sess_user_agency'] = 0;
					$_SESSION['sess_agency_code'] = '';
					$_SESSION['sess_agency_name'] = '';
					$_SESSION['needAgencyPopupES'] = 1;
					$_SESSION['sess_user_level_es_check'] = 1;
				}
				 
			
				header('Location: ../main.php');
				exit();
			}

        // admin / general
        if (
            $_SESSION['sess_user_level_es'] == 'admin' ||
            $_SESSION['sess_user_level_es'] == 'general'
        ) {
            $ag_id = (int)$result["user_agency"];

            if ($ag_id <= 0) {
                $_SESSION['login_error'] = 'ไม่พบหน่วยงานของผู้ใช้งาน';
                header('Location: ../login.php');
                exit();
            }

            $sql_ag = "
                SELECT ag_id, ag_contract, ag_job, ag_start_date, ag_end_date
                FROM tb_agency
                WHERE ag_id = {$ag_id}
                  AND ag_status = 1
                LIMIT 1
            ";
            $q_ag = mysqli_query($connect, $sql_ag);

            if (!$q_ag) {
                $_SESSION['login_error'] = 'เกิดข้อผิดพลาดในการโหลดข้อมูลหน่วยงาน';
                header('Location: ../login.php');
                exit();
            }

            $r_ag = mysqli_fetch_assoc($q_ag);
            if (!$r_ag) {
                $_SESSION['login_error'] = 'ไม่พบข้อมูลหน่วยงาน หรือหน่วยงานถูกปิดใช้งาน';
                header('Location: ../login.php');
                exit();
            }

            $_SESSION['sess_user_agency'] 	= (int)$r_ag['ag_id'];
            $_SESSION['sess_agency_code'] 	= $r_ag['ag_job'];
            $_SESSION['sess_agency_name'] 	= $r_ag['ag_contract'];
			$_SESSION['sess_ag_start_date'] 	  	= $r_ag['ag_start_date'];
			$_SESSION['sess_ag_end_date'] 	 	= $r_ag['ag_end_date'];
            $_SESSION['needAgencyPopupES'] 	= 0;

            header('Location: ../main.php');
            exit();
        }

        header('Location: ../config_ctrl/logout.php');
        exit();
    }

}
// ---------- กรณีไม่เจอ user ----------
else {
    $_SESSION['login_error'] = 'ชื่อผู้ใช้งาน หรือรหัสผ่านไม่ถูกต้อง';
    header('Location: ../login.php');
    exit();
}
?>