<?php 
	error_reporting(E_ALL ^ E_NOTICE);
	@session_start();
	
	if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loginUrl = "https://happylandgroup.biz/es/login.php";

/* ??????? session ????????????? login */
if (
    empty($_SESSION['sess_user_id_es']) ||
    empty($_SESSION['sess_user_level_es'])
) {
    session_unset();
    session_destroy();

    header("Location: " . $loginUrl);
    exit();
}

/* ??????????? session_time ???????? */
if (empty($_SESSION['session_time'])) {
    $_SESSION['session_time'] = time();
}

/* ??????????? 1 ??????? */
$overtime = time() - (int)$_SESSION['session_time'];

/* หน่วยงานหมดสัญญาระหว่างที่ยังล็อกอินอยู่ => ออกจากระบบพร้อมข้อความ (super_admin ใช้งานต่อได้) */
if ($_SESSION['sess_user_level_es'] !== 'super_admin' && !empty($_SESSION['sess_ag_end_date'])) {
    require_once __DIR__ . '/agency_contract.php';
    if (agc_is_expired($_SESSION['sess_ag_end_date'])) {
        agc_block(agc_message([['name' => (string)($_SESSION['sess_agency_name'] ?? ''), 'end' => $_SESSION['sess_ag_end_date']]]), $loginUrl);
    }
}

if ($overtime > 3600) {
    session_unset();
    session_destroy();

    header("Location: " . $loginUrl);
    exit();
}

/* ??????? session */
$_SESSION['session_time'] = time();
	
	$sess_member_id			= $_SESSION['sess_member_id'];
	$sess_id				= $_SESSION['sess_id_es'];
	$sess_user_id			= $_SESSION['sess_user_id_es'];
	$sess_user_code			= $_SESSION['sess_user_code'];
	$sess_member_status		= $_SESSION['sess_member_status'];
	$sess_user_password		= $_SESSION['sess_user_password'];
	$sess_user_name			= $_SESSION['sess_user_name_es'];
	$sess_user_fname		= $_SESSION['sess_user_fname_es']; 
	$sess_user_position		= $_SESSION['sess_user_position_es'];
	$sess_user_level		= $_SESSION['sess_user_level_es'];
	$sess_user_dep_id		= $_SESSION['sess_user_dep_id'];
	$sess_user_email		= $_SESSION['sess_user_email_es'];
	$sess_tmp_usr_id		= $_SESSION['sess_tmp_usr_id_es'];
	$sess_server_id			= $_SESSION['sess_server_id_es'];
	$sess_business_title	= $_SESSION['sess_business_title'];
	$sess_SYSTEM_CTRL		= $_SESSION['sess_SYSTEM_CTRL'];
	$sess_chk_cashire		= $_SESSION['sess_chk_cashire'];		
	$sess_chk_tax		 	= $_SESSION['sess_chk_tax'];			
	$sess_chk_it			= $_SESSION['sess_chk_it'];			
	$sess_chk_finance		= $_SESSION['sess_chk_finance'];		
	$sess_mem_expire		= $_SESSION['sess_mem_expire'];
	$actual_link 			= 'http://'.$_SERVER['HTTP_HOST'];
	$sess_user_shop 		= $_SESSION['sess_user_shop'];	
	$sess_status_login		= $_SESSION['sess_status_login'];
	$sess_buttoncontorno	= $_SESSION['sess_buttoncontorno'];
	$sess_agency_name		= $_SESSION['sess_agency_name'];
	$sess_agency_code		= $_SESSION['sess_agency_code'];
	$sess_user_agency		= $_SESSION['sess_user_agency'];
	 
	
	$sess_username	   	    = $_SESSION['sess_username'];
	$sess_userfname	   	    = $_SESSION['sess_user_fname'];
	$sess_position_level    = $_SESSION['sess_position_level'];
	$needAgencyPopupES    	= $_SESSION['needAgencyPopupES']; 
	$sess_Ag_mul			= $_SESSION['sess_Ag_mul'];
	$sess_user_agency_es    = $_SESSION['sess_user_agency'];	
	
	
	$sess_ag_start_date    	= $_SESSION['sess_ag_start_date'];	
	$sess_ag_end_date    	= $_SESSION['sess_ag_end_date'];	
	
	 
	 
	 
?>