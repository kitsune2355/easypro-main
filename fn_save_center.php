<?php 
ob_start();
ini_set('memory_limit', '8M');
ini_set('max_execution_time', 300);
@session_start();
include "config_ctrl/connect.php";
include "config_ctrl/checksession.php"; 
date_default_timezone_set('Asia/Bangkok'); 

$SubmitH	= $_POST['SubmitH'];   
$SubmitH2	= $_GET['SubmitH'];   

 
 
if($SubmitH=="Submit_1_1") { 

	$user_id				= 	$_POST['user_id']; 
	
	$sql="DELETE FROM tb_user
		WHERE  `user_id`			= 	'".$user_id."' 
	 "; 
	include "inc_logging.php"; 
	$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect)); 
}
if($SubmitH=="Submit_1") { 
    
	$user_id				= 	$_POST['user_id'];
	$user_password			=	$_POST['user_password'];
	$user_passwordH			=	$_POST['user_passwordH'];
	$user_affiliation		= 	$_POST['user_affiliation'];
	$user_position_level_2	= 	$_POST['user_position_level_2'];
	$user_sex 				= 	$_POST['user_sex']; 
	$user_name				= 	$_POST['user_name'];
	$user_fname				= 	$_POST['user_fname'];
	$user_nickname			= 	$_POST['user_nickname'];
	$user_position			= 	$_POST['user_position'];
	$user_department		= 	$_POST['user_department']; 
	$user_id_card			= 	$_POST['user_id_card']; 
	$user_position_level	= 	$_POST['user_position_level'];
	$user_level				= 	$_POST['user_level'];
	$user_status_login		= 	$_POST['user_status_login']; 
	$status	     			= 	$_POST['status']; 
	$user_email				= 	$_POST['user_email'];  
	$status					= 	$_POST['status'];   
	$dep_id					= 	$_POST['dep_id'];   
	
	if(($user_password!='')&&($user_passwordH!='')){
		if($user_password!=$user_passwordH){
		echo'
			<script language="javascript">
			// window.parent.fn_alert2(\'รหัสผ่านไม่ตรงกัน\',\'warning2\');
			 //var Dist = setTimeout(\'window.parent.location.href="setting_user_admin_v1.php"\', 3000);
			</script>
			';
		exit();
		}
	$user_password = md5(md5(md5($user_password)));
	$user_passwordG = ",`user_password`			= 	'".$user_password."'";
			
	} 
	$user_password = md5(md5(md5($user_password)));
	 
	if($status=='3'){
		$sql="UPDATE tb_user SET  
			`user_password`			= 	'".$user_password."'
			WHERE  `user_id`		= 	'".$user_id."'
			 "; 
		include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("แก้ไขรหัสผ่านเรียบร้อย");
			 window.parent.location.href="menu_01.php";
			 //window.parent.fn_alert1(\'แก้ไขข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			 //var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';	
			exit();
	}
	 
	
	 
	if($status=='1'){
		$sql="UPDATE tb_user SET 
			`user_email`				= 	'".$user_email."' 
			,`user_name`				= 	'".$user_name."'
			,`user_fname`				= 	'".$user_fname."' 
			,`user_position`			= 	'".$user_position."' 
			,`user_position_level`		= 	'".$user_position_level."'  
			,`user_status_login`		= 	'".$user_status_login."'
			,`user_level`				= 	'".$user_level."'
			,`user_department`			= 	'".$dep_id."'
			WHERE  `user_id`			= 	'".$user_id."'
			 "; 
		include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		
		$user_shop_arr	=	$_POST['ag_id'];
		$sql="DELETE FROM tb_agency_employer
			WHERE  `age_user_id`			= 	'".$user_id."'
		 "; 
		  include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		foreach ($user_shop_arr as &$id) {
			$sql="
				INSERT INTO tb_agency_employer SET
				 `age_user_id`		= '".$user_id."'
				,`age_ag_id`	= '".$id."'
				,`age_ins`	= now()
				 "; 
			include "inc_logging.php"; 
			$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		}
		 
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
	//	alert("แก้ไขข้อมูลเรียบร้อยแล้ว");
		//	 window.parent.location.href="menu_01.php";
			 //window.parent.fn_alert1(\'แก้ไขข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			 //var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';	
	}else{
		
		$sql_ph12="SELECT tb_user.user_id
			FROM tb_user as tb_user
			WHERE tb_user.user_id = '".$user_id."'
			LIMIT 0 , 1
			"; 
			echo $sql_ph12;
		$query_ph12 =mysqli_query($connect,$sql_ph12) or die(mysqli_error($connect));
		$num_rows_ph12 =mysqli_num_rows($query_ph12);
			if($num_rows_ph12>=1){
			echo'
			<script language="javascript">
			alert("รหัสพนักงานนี้ถูกใช้งานไปแล้ว");
			// window.parent.fn_alert2(\'รหัสพนักงานนี้ถูกใช้งานไปแล้ว\',\'warning2\');
			 //var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
			exit();
			}
		
		$sql="INSERT INTO tb_user SET 
			 `user_id`					= 	'".$user_id."'
			,`user_password`			= 	'".$user_password."' 
			,`user_name`				= 	'".$user_name."'
			,`user_fname`				= 	'".$user_fname."'  
			,`user_level`				= 	'".$user_level."'
			,`user_status_login`		= 	'".$user_status_login."'
			,`user_department`			= 	'".$dep_id."'
			 "; 
			include "inc_logging.php"; 
	$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		
		//$user_shop_arr	=	$_POST['ag_id'];
//		foreach ($user_shop_arr as &$id) {
//			$sql="
//				INSERT INTO tb_agency_employer SET
//				 `age_user_id`		= '".$user_id."'
//				,`age_ag_id`	= '".$id."'
//				,`age_ins`	= now()
//				 ";
//			$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
//		}
		echo'
			<script language="javascript">
	//	alert("บันทึกข้อมูลเรียบร้อย");
		 //	 window.parent.location.href="menu_01.php";
			 window.parent.fn_alet();
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1700);
			</script>
			';
	}  
}


if($SubmitH=="Submit_2_1") { 
 
	$GroupId				= 	$_POST['GroupId']; 
	$sql="DELETE FROM tb_asset_group
		WHERE  `GroupId`			= 	'".$GroupId."' 
	 "; 
			include "inc_logging.php"; 
	$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
	
	
}

if($SubmitH=="Submit_2") { 
  

	

	$TGroupName			= 	$_POST['TGroupName'];
	$GroupId				= 	$_POST['GroupId']; 
	$status				= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_asset_group SET 
			`TGroupName`				= 	'".$TGroupName."' 
			WHERE GroupId					= 	'".$GroupId."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_asset_group SET 
			`TGroupName`				= 	'".$TGroupName."'  
			,`group_user_ins`			= 	'".$sess_user_id."' 
			,`group_ins`				= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}




if($SubmitH=="Submit_3333") { 
  

	

	$ass_code				= 	$_POST['ass_code'];
	$asset_type				= 	$_POST['asset_type'];
	$asset_name				= 	$_POST['asset_name'];
	$asset_sn				= 	$_POST['asset_sn'];
	$brn_id					= 	$_POST['brn_id'];
	$asset_model			= 	$_POST['asset_model'];
	$asset_location			= 	$_POST['asset_location']; 
	$dep_id					= 	$_POST['dep_id'];
	$asset_state			= 	$_POST['asset_state'];
	$status					= 	$_POST['status'];
	$ass_id					= 	$_POST['ass_id'];
	$asset_name				= 	$_POST['asset_name'];
	
 
	if($status=='1'){
		$sql="UPDATE tb_ass_list SET 
			`ass_code`					= 	'".$ass_code."'
			,`asset_type`				= 	'".$asset_type."' 
			,`asset_name`				= 	'".$asset_name."' 
			,`asset_sn`					= 	'".$asset_sn."'
			,`brn_id`					= 	'".$brn_id."'
			,`asset_model`				= 	'".$asset_model."'
			,`asset_location`			= 	'".$asset_location."'
			,`dep_id`					= 	'".$dep_id."'
			,`asset_state`				= 	'".$asset_state."'
			,`asset_name`				= 	'".$asset_name."'
			WHERE ass_id					= 	'".$ass_id."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_ass_list SET 
			`ass_code`					= 	'".$ass_code."'
			,`asset_type`				= 	'".$asset_type."' 
			,`asset_name`				= 	'".$asset_name."' 
			,`asset_sn`					= 	'".$asset_sn."'
			,`brn_id`					= 	'".$brn_id."'
			,`asset_model`				= 	'".$asset_model."'
			,`asset_location`			= 	'".$asset_location."'
			,`dep_id`					= 	'".$dep_id."'
			,`asset_name`				= 	'".$asset_name."'
			,`asset_state`				= 	'".$asset_state."' 
			,`asset_ins`					= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}


if($SubmitH=="Submit_3") { 
  

	

	$ag_contract			= 	$_POST['ag_contract'];
	$ag_details				= 	$_POST['ag_details'];
	$ag_area				= 	$_POST['ag_area'];
	$ag_username			= 	$_POST['ag_username'];
	$ag_op					= 	$_POST['ag_op'];
	$ag_status				= 	$_POST['ag_status'];
	$ag_job					= 	$_POST['ag_job'];
	$cpb_groupanswer		= 	$_POST['cpb_groupanswer'];

 
	if($status=='1'){
		$sql="UPDATE tb_agency SET 
			`ag_contract`				= 	'".$ag_contract."'
			,`ag_details`				= 	'".$ag_details."' 
			,`ag_located`				= 	'".$ag_located."' 
			,`ag_area`					= 	'".$ag_area."'
			,`ag_username`				= 	'".$ag_username."'
			,`ag_op`					= 	'".$ag_op."'
			,`ag_status`				= 	'".$ag_status."'
			,`ag_job`					= 	'".$ag_job."'
			,`cpb_groupanswer`			= 	'".$cpb_groupanswer."'
			,`ag_ins`					= 	now()
			WHERE ag_id					= 	'".$ag_id."'
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_01.php";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_agency SET 
			`ag_contract`				= 	'".$ag_contract."'
			,`ag_details`				= 	'".$ag_details."' 
			,`ag_located`				= 	'".$ag_located."' 
			,`ag_area`					= 	'".$ag_area."'
			,`ag_username`				= 	'".$ag_username."'
			,`ag_op`					= 	'".$ag_op."'
			,`ag_status`				= 	'".$ag_status."'
			,`ag_job`					= 	'".$ag_job."'
			,`cpb_groupanswer`			= 	'".$cpb_groupanswer."'
			,`ag_ins`					= 	now()
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_01.php";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}

if($SubmitH=="Submit_4") { 
  

	

	$cp_card_type			= 	$_POST['cp_card_type'];
	$cp_point_name			= 	$_POST['cp_point_name'];
	$cp_point_number		= 	$_POST['cp_point_number'];
	$cp_ag_id				= 	$_POST['cp_ag_id']; 
	$cp_id					= 	$_POST['cp_id']; 
	$cbp_id					= 	$_POST['cbp_id']; 
	$cp_nameanswer1			= 	$_POST['cp_nameanswer1'];
	$cp_answerlevel1		= 	$_POST['cp_answerlevel1'];
	$cp_nameanswer2			= 	$_POST['cp_nameanswer2'];
	$cp_answerlevel2		= 	$_POST['cp_answerlevel2'];
	$cp_nameanswer3			= 	$_POST['cp_nameanswer3'];
	$cp_answerlevel3		= 	$_POST['cp_answerlevel3'];
	$cp_nameanswer4			= 	$_POST['cp_nameanswer4'];
	$cp_answerlevel4		= 	$_POST['cp_answerlevel4'];
	$cp_nameanswer5			= 	$_POST['cp_nameanswer5'];
	$cp_answerlevel5		= 	$_POST['cp_answerlevel5'];
	$cp_nameanswer6			= 	$_POST['cp_nameanswer6'];
	$cp_answerlevel6		= 	$_POST['cp_answerlevel6'];
	$cp_nameanswer7			= 	$_POST['cp_nameanswer7'];
	$cp_answerlevel7		= 	$_POST['cp_answerlevel7'];
	$cp_nameanswer8			= 	$_POST['cp_nameanswer8'];
	$cp_answerlevel8		= 	$_POST['cp_answerlevel8'];
	$cp_nameanswer9			= 	$_POST['cp_nameanswer9'];
	$cp_answerlevel9		= 	$_POST['cp_answerlevel9'];
	$cp_nameanswer10		= 	$_POST['cp_nameanswer10'];
	$cp_answerlevel10		= 	$_POST['cp_answerlevel10'];
	$cp_nameanswer11		= 	$_POST['cp_nameanswer11'];
	$cp_answerleve11		= 	$_POST['cp_answerleve11'];
	$cp_nameanswer12		= 	$_POST['cp_nameanswer12'];
	$cp_answerlevel12		= 	$_POST['cp_answerlevel12'];
	$cp_nameanswer13		= 	$_POST['cp_nameanswer13'];
	$cp_answerlevel13		= 	$_POST['cp_answerlevel13'];
	$cp_nameanswer14		= 	$_POST['cp_nameanswer14'];
	$cp_answerlevel14		= 	$_POST['cp_answerlevel14'];
	$cp_nameanswer15		= 	$_POST['cp_nameanswer15'];
	$cp_answerlevel15		= 	$_POST['cp_answerlevel15'];
	$cp_nameanswer16		= 	$_POST['cp_nameanswer16'];
	$cp_answerlevel16		= 	$_POST['cp_answerlevel16'];
	$cp_nameanswer17		= 	$_POST['cp_nameanswer17'];
	$cp_answerlevel17		= 	$_POST['cp_answerlevel17'];
	$cp_nameanswer18		= 	$_POST['cp_nameanswer18'];
	$cp_answerlevel18		= 	$_POST['cp_answerlevel18'];
	$cp_nameanswer19		= 	$_POST['cp_nameanswer19'];
	$cp_answerlevel19		= 	$_POST['cp_answerlevel19'];
	$cp_nameanswer20		= 	$_POST['cp_nameanswer20'];
	$cp_answerlevel20		= 	$_POST['cp_answerlevel20'];
	$status					= 	$_POST['status'];


	
 
	if($status=='1'){
		$sql="UPDATE tb_checkpoint SET 
			`cp_point_number`			= 	'".$cp_point_number."'
			,`cp_point_name`			= 	'".$cp_point_name."' 
			,`cp_point_number`			= 	'".$cp_point_number."' 
			,`cp_card_type`				= 	'".$cp_card_type."' 
			,`cp_nameanswer1`			= 	'".$cp_nameanswer1."' 
			,`cp_answerlevel1`			= 	'".$cp_answerlevel1."' 
			,`cp_nameanswer2`			= 	'".$cp_nameanswer2."' 
			,`cp_answerlevel2`			= 	'".$cp_answerlevel2."' 
			,`cp_nameanswer3`			= 	'".$cp_nameanswer3."' 
			,`cp_answerlevel3`			= 	'".$cp_answerlevel3."' 
			,`cp_nameanswer4`			= 	'".$cp_nameanswer4."' 
			,`cp_answerlevel4`			= 	'".$cp_answerlevel4."' 
			,`cp_nameanswer5`			= 	'".$cp_nameanswer5."' 
			,`cp_answerlevel5`			= 	'".$cp_answerlevel5."' 
			,`cp_nameanswer6`			= 	'".$cp_nameanswer6."' 
			,`cp_answerlevel6`			= 	'".$cp_answerlevel6."' 
			,`cp_nameanswer7`			= 	'".$cp_nameanswer7."' 
			,`cp_answerlevel7`			= 	'".$cp_answerlevel7."' 
			,`cp_nameanswer8`			= 	'".$cp_nameanswer8."' 
			,`cp_answerlevel8`			= 	'".$cp_answerlevel8."' 
			,`cp_nameanswer9`			= 	'".$cp_nameanswer9."' 
			,`cp_answerlevel9`			= 	'".$cp_answerlevel9."' 
			,`cp_nameanswer10`			= 	'".$cp_nameanswer10."' 
			,`cp_answerlevel10`			= 	'".$cp_answerlevel10."' 
			,`cp_nameanswer11`			= 	'".$cp_nameanswer11."' 
			,`cp_answerlevel11`			= 	'".$cp_answerlevel11."' 
			,`cp_nameanswer12`			= 	'".$cp_nameanswer12."' 
			,`cp_answerlevel12`			= 	'".$cp_answerlevel12."' 
			,`cp_nameanswer13`			= 	'".$cp_nameanswer13."' 
			,`cp_answerlevel13`			= 	'".$cp_answerlevel13."' 
			,`cp_nameanswer14`			= 	'".$cp_nameanswer14."' 
			,`cp_answerlevel14`			= 	'".$cp_answerlevel14."' 
			,`cp_nameanswer15`			= 	'".$cp_nameanswer15."' 
			,`cp_answerlevel15`			= 	'".$cp_answerlevel15."' 
			,`cp_nameanswer16`			= 	'".$cp_nameanswer16."' 
			,`cp_answerlevel16`			= 	'".$cp_answerlevel16."' 
			,`cp_nameanswer17`			= 	'".$cp_nameanswer17."' 
			,`cp_answerlevel17`			= 	'".$cp_answerlevel17."' 
			,`cp_nameanswer18`			= 	'".$cp_nameanswer18."' 
			,`cp_answerlevel18`			= 	'".$cp_answerlevel18."' 
			,`cp_nameanswer19`			= 	'".$cp_nameanswer19."' 
			,`cp_answerlevel19`			= 	'".$cp_answerlevel19."' 
			,`cp_nameanswer20`			= 	'".$cp_nameanswer20."' 
			,`cp_answerlevel20`			= 	'".$cp_answerlevel20."' 
			,`cp_time`					= 	now()
			WHERE cp_id					= 	'".$cp_id."'
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			window.parent.location.href="menu_04.php?ag_id='.$cp_ag_id.'";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_checkpoint SET 
			`cp_ag_id`					= 	'".$cp_ag_id."'
			,`cp_point_name`			= 	'".$cp_point_name."'  
			,`cp_point_number`			= 	'".$cp_point_number."'  
			,`cp_card_type`				= 	'".$cp_card_type."'
			,`cp_nameanswer1`			= 	'".$cp_nameanswer1."' 
			,`cp_answerlevel1`			= 	'".$cp_answerlevel1."' 
			,`cp_nameanswer2`			= 	'".$cp_nameanswer2."' 
			,`cp_answerlevel2`			= 	'".$cp_answerlevel2."' 
			,`cp_nameanswer3`			= 	'".$cp_nameanswer3."' 
			,`cp_answerlevel3`			= 	'".$cp_answerlevel3."'
			,`cp_nameanswer4`			= 	'".$cp_nameanswer4."' 
			,`cp_answerlevel4`			= 	'".$cp_answerlevel4."' 
			,`cp_nameanswer5`			= 	'".$cp_nameanswer5."' 
			,`cp_answerlevel5`			= 	'".$cp_answerlevel5."' 
			,`cp_nameanswer6`			= 	'".$cp_nameanswer6."' 
			,`cp_answerlevel6`			= 	'".$cp_answerlevel6."' 
			,`cp_nameanswer7`			= 	'".$cp_nameanswer7."' 
			,`cp_answerlevel7`			= 	'".$cp_answerlevel7."' 
			,`cp_nameanswer8`			= 	'".$cp_nameanswer8."' 
			,`cp_answerlevel8`			= 	'".$cp_answerlevel8."' 
			,`cp_nameanswer9`			= 	'".$cp_nameanswer9."' 
			,`cp_answerlevel9`			= 	'".$cp_answerlevel9."' 
			,`cp_nameanswer10`			= 	'".$cp_nameanswer10."' 
			,`cp_answerlevel10`			= 	'".$cp_answerlevel10."' 
			,`cp_nameanswer11`			= 	'".$cp_nameanswer11."' 
			,`cp_answerlevel11`			= 	'".$cp_answerlevel11."' 
			,`cp_nameanswer12`			= 	'".$cp_nameanswer12."' 
			,`cp_answerlevel12`			= 	'".$cp_answerlevel12."' 
			,`cp_nameanswer13`			= 	'".$cp_nameanswer13."' 
			,`cp_answerlevel13`			= 	'".$cp_answerlevel13."' 
			,`cp_nameanswer14`			= 	'".$cp_nameanswer14."' 
			,`cp_answerlevel14`			= 	'".$cp_answerlevel14."' 
			,`cp_nameanswer15`			= 	'".$cp_nameanswer15."' 
			,`cp_answerlevel15`			= 	'".$cp_answerlevel15."' 
			,`cp_nameanswer16`			= 	'".$cp_nameanswer16."' 
			,`cp_answerlevel16`			= 	'".$cp_answerlevel16."' 
			,`cp_nameanswer17`			= 	'".$cp_nameanswer17."' 
			,`cp_answerlevel17`			= 	'".$cp_answerlevel17."' 
			,`cp_nameanswer18`			= 	'".$cp_nameanswer18."' 
			,`cp_answerlevel18`			= 	'".$cp_answerlevel18."' 
			,`cp_nameanswer19`			= 	'".$cp_nameanswer19."' 
			,`cp_answerlevel19`			= 	'".$cp_answerlevel19."' 
			,`cp_nameanswer20`			= 	'".$cp_nameanswer20."' 
			,`cp_answerlevel20`			= 	'".$cp_answerlevel20."'     
			,`cp_time`					= 	now()
			"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_04.php?ag_id='.$cp_ag_id.'";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}

if($SubmitH=="Submit_5") { 
	$cpb_nameanswer			= 	$_POST['cpb_nameanswer'];
	$cpb_groupanswer		= 	$_POST['cpb_groupanswer'];
	$cpb_id					= 	$_POST['cpb_id'];
	$status		= 	$_POST['status'];

	
 
	if($status=='1'){
		$sql="UPDATE tb_checkproblem SET 
			`cpb_nameanswer`			= 	'".$cpb_nameanswer."' 
			,`cpb_groupanswer`			= 	'".$cpb_groupanswer."' 
			,`cpb_time`					= 	now()
			WHERE cpb_id					= 	'".$cpb_id."'
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_05.php";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_checkproblem SET 
			`cpb_nameanswer`			= 	'".$cpb_nameanswer."' 
			,`cpb_groupanswer`			= 	'".$cpb_groupanswer."'  
			,`cpb_time`					= 	now()
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_05.php?cp_ag_id='.$cp_ag_id.'";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}

if($SubmitH=="Submit_6") { 
	$ch_agency				= 	$_POST['ch_agency'];
	$ch_work_time			= 	$_POST['ch_work_time'];
	$ch_number				= 	$_POST['ch_number'];
	$cpb_groupanswer		= 	$_POST['cpb_groupanswer'];
	$lc_id					= 	$_POST['lc_id'];

	
 
	if($status=='1'){
		$sql="UPDATE tb_list_check_head SET  
			`ch_agency`					= 	'".$ch_agency."' 
			,`ch_work_time`				= 	'".$ch_work_time."'
			,`ch_number`				= 	'".$ch_number."' 
			,`ch_recorder_name`			= 	'".$ch_recorder_name."'  
			,`ch_time`					= 	now()
			WHERE ch_id					= 	'".$ch_id."'
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_06.php";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_list_check_head SET 
			`ch_agency`					= 	'".$ch_agency."' 
			,`ch_work_time`				= 	'".$ch_work_time."'
			,`ch_number`				= 	'".$ch_number."'
			,`ch_recorder_name`			= 	'".$ch_recorder_name."'
			,`ch_time`					= 	now()
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
 
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_06.php?cp_ag_id='.$cp_ag_id.'";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}



if($SubmitH=="Submit_7") { 
	$ch_agency				= 	$_POST['ch_agency'];
	$ch_work_time			= 	$_POST['ch_work_time'];
	$ch_number				= 	$_POST['ch_number'];
	$cpb_groupanswer		= 	$_POST['cpb_groupanswer'];
	$lc_id					= 	$_POST['lc_id'];

	
 
	if($status=='1'){
		$sql="UPDATE tb_list_check_head SET  
			`ch_agency`					= 	'".$ch_agency."' 
			,`ch_work_time`				= 	'".$ch_work_time."'
			,`ch_number`				= 	'".$ch_number."' 
			,`ch_recorder_name`			= 	'".$ch_recorder_name."'  
			,`ch_time`					= 	now()
			WHERE ch_id					= 	'".$ch_id."'
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_06.php";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_list_check_head SET 
			`ch_agency`					= 	'".$ch_agency."' 
			,`ch_work_time`				= 	'".$ch_work_time."'
			,`ch_number`				= 	'".$ch_number."'
			,`ch_recorder_name`			= 	'".$ch_recorder_name."'
			,`ch_time`					= 	now()
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
 
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_06.php?cp_ag_id='.$cp_ag_id.'";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}



if($SubmitH=="Submit_8") { 
			ini_set('memory_limit', '64M');
			ini_set('max_execution_time', 5000);
			
	$lp_picture				= 	$_POST['lp_picture'];
	$lp_agency				= 	$_POST['lp_agency'];
	$lp_check_point			= 	$_POST['lp_check_point'];
	$lp_latitude			= 	$_POST['lp_latitude'];
	$lp_longitude			= 	$_POST['lp_longitude'];  
	$lp_answer_arr			= 	$_POST['lp_answer']; 
	$lp_question_arr		= 	$_POST['lp_question1'];  
	$lp_answer_detail_arr	= 	$_POST['lp_answer_detail']; 
	$lp_answer_picture_arr	= 	$_POST['lp_answer_picture']; 
	$file1					= 	$_POST['file1']; 
	$file2					= 	$_POST['file2']; 
	$file3					= 	$_POST['file3']; 
	$lp_more_details		= 	$_POST['lp_more_details']; 
	$lp_ch_id				= 	$_POST['lp_ch_id']; 
	$lp_number				= 	$_POST['lp_number']; 
	$lp_number1				= 	$_POST['lp_number']; 
	$ag_job					= 	$_POST['ag_job'];
	$lp_time				= 	$_POST['lp_time']; 
	$ag_token				= 	$_POST['ag_token']; 
	$lp_cp_point_name				= 	$_POST['lp_cp_point_name']; 
	$lp_answer_picture_tmpFoder_arr			= 	$_POST['lp_answer_picture_tmpFoder']; 
	$lp_link_cp_ag_id					= 	$_POST['lp_link_cp_ag_id']; 
	$lp_link_cp_point_number 			= 	$_POST['lp_link_cp_point_number']; 
	
	if($lp_number1=='0'){ $lp_number1 = $lp_cp_point_name;  }elseif($lp_number1=='1'){ $lp_number1 = $lp_cp_point_name;  }else{  $lp_number1 = ($lp_number1-1).' '.$lp_cp_point_name;  }  
	echo 'sdffdsf';
	echo print_r($lp_answer_picture_arr);
	//sexit();
	//echo  $file1;
	//$i=1;
//	foreach($lp_answer_picture_arr as &$lp_answer_picture) { 
//		if($lp_answer_picture!=''){  
//			$sql="INSERT INTO tb_list_check_head SET 
//				  `ch_agency`		= 	'".$asp_shop_id."'
//				 ,`ch_work_time`	= 	'".$class_name."'    
//				 ,`ch_end_time`		= 	'".$class_name2_arr[$i]."'    
//				 ,`ch_time`			= 	NOW()
//				 ";
//			echo $sql.'<br />';
//		//	$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect)); 
//		}
//		$i++;
//	}
	
//	$ag = $lp_agency;
//	$filename=date("ymdHis").$ag.$i.'.txt';
//	$tmpFolder='AttFile2/'.$ag;
//	if(!file_exists($tmpFolder)) mkdir($tmpFolder, 0777);
//	$tmpFolderMY='AttFile2/'.$ag.'/AttFile'.date("y-m");
//	if(!file_exists($tmpFolderMY)) mkdir($tmpFolderMY, 0777);
//	$tmpFolderMYD='AttFile2/'.$ag.'/AttFile'.date("y-m").'/'.date("y-m-d");
//	if(!file_exists($tmpFolderMYD)) mkdir($tmpFolderMYD, 0777);
//			$myfile = fopen($tmpFolderFILE, "a") or die("Unable to open file!");
//			fwrite($myfile, ''); 
	
	$ag = $lp_agency;
	$filename=date("ymdHis").$ag.$i.'.txt';
	$tmpFolder='A/'.$ag;
	if(!file_exists($tmpFolder)) mkdir($tmpFolder, 0777);
	$tmpFolderMY='A/'.$ag.'/'.date("ym");
	if(!file_exists($tmpFolderMY)) mkdir($tmpFolderMY, 0777);
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
	if(!file_exists($tmpFolderMYD)) mkdir($tmpFolderMYD, 0777);
	
	if($lp_answer_picture_arr[1]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-1.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file1!");
		$Text = $lp_answer_picture_arr[1];
		fwrite($myfile, $Text); 
		$lp_answer_picture_arr[1] = $tmpFolderFILE;
	}
	
	if($lp_answer_picture_arr[2]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-2.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file2!");
		$Text = $lp_answer_picture_arr[2];
		fwrite($myfile, $Text); 
		$lp_answer_picture_arr[2] = $tmpFolderFILE;
	}
	
	if($lp_answer_picture_arr[3]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-3.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file3!");
		$Text = $lp_answer_picture_arr[3];
		fwrite($myfile, $Text); 
		$lp_answer_picture_arr[3] = $tmpFolderFILE;
	}
	
	if($lp_answer_picture_arr[4]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-4.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file4!");
		$Text = $lp_answer_picture_arr[4];
		fwrite($myfile, $Text); 
		$lp_answer_picture_arr[4] = $tmpFolderFILE;
	}
	
	if($lp_answer_picture_arr[5]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-5.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file4!");
		$Text = $lp_answer_picture_arr[5];
		fwrite($myfile, $Text); 
		$lp_answer_picture_arr[5] = $tmpFolderFILE;
	}
	//$i=1;
//	foreach($lp_answer_picture_arr as &$lp_answer_picture) { 
//		if($lp_answer_picture!=""){  
//			$ag = $lp_agency;
//			$filename=date("ymdHis").$ag.$i.'.txt';
//			$tmpFolder='AttFile2/'.$ag;
//			if(!file_exists($tmpFolder)) mkdir($tmpFolder, 0777);
//			$tmpFolderMY='AttFile2/'.$ag.'/AttFile'.date("y-m");
//			if(!file_exists($tmpFolderMY)) mkdir($tmpFolderMY, 0777);
//			$tmpFolderMYD='AttFile2/'.$ag.'/AttFile'.date("y-m").'/'.date("y-m-d");
//			if(!file_exists($tmpFolderMYD)) mkdir($tmpFolderMYD, 0777);
//			$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
//			
//			$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file!");
//			$Text = $lp_answer_picture;
//			fwrite($myfile, $Text); 
//			$lp_answer_picture_arr[$i] = $tmpFolderFILE;
//		} 
//		$i++;
//	}
	 
	 
	 
	 
	if($status=='1'){
		$sql="UPDATE tb_list_point SET  
			`ch_agency`					= 	'".$ch_agency."' 
			,`ch_work_time`				= 	'".$ch_work_time."'
			,`ch_number`				= 	'".$ch_number."' 
			,`ch_recorder_name`			= 	'".$ch_recorder_name."'  
			,`ch_time`					= 	now()
			WHERE ch_id					= 	'".$ch_id."'
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_06.php";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{ 	
		
		$sql="INSERT INTO tb_list_point SET 
			`lp_agency`					= 	'".$lp_agency."' 
			,`lp_check_point`			= 	'".$lp_check_point."'
			,`lp_date`					= 	now()  
			,`lp_ch_id`					= 	'".$lp_ch_id."' 
			,`lp_picture`				= 	'".$lp_picture."' 
			,`lp_cp_point_name`			= 	'".$lp_cp_point_name."'  
			,`lp_question1`				= 	'".$lp_question_arr[1]."'
			,`lp_answer1`				= 	'".$lp_answer_arr[1]."' 
			,`lp_answer_picture1`		= 	'".$lp_answer_picture_arr[1]."'
			,`lp_answer_detail1`		= 	'".$lp_answer_detail_arr[1]."' 
			,`lp_question2`				= 	'".$lp_question_arr[2]."'
			,`lp_answer2`				= 	'".$lp_answer_arr[2]."' 
			,`lp_answer_picture2`		= 	'".$lp_answer_picture_arr[2]."'
			,`lp_answer_detail2`		= 	'".$lp_answer_detail_arr[2]."'
			,`lp_question3`				= 	'".$lp_question_arr[3]."'
			,`lp_answer3`				= 	'".$lp_answer_arr[3]."'
			,`lp_answer_picture3`		= 	'".$lp_answer_picture_arr[3]."'
			,`lp_answer_detail3`		= 	'".$lp_answer_detail_arr[3]."'
			,`lp_question4`				= 	'".$lp_question_arr[4]."'
			,`lp_question5`				= 	'".$lp_question_arr[5]."' 
			,`lp_question6`				= 	'".$lp_question_arr[6]."'
			,`lp_question7`				= 	'".$lp_question_arr[7]."' 
			,`lp_question8`				= 	'".$lp_question_arr[8]."'
			,`lp_question9`				= 	'".$lp_question_arr[9]."' 
			,`lp_question10`			= 	'".$lp_question_arr[10]."'
			,`lp_question11`			= 	'".$lp_question_arr[11]."' 
			,`lp_question12`			= 	'".$lp_question_arr[12]."'
			,`lp_question13`			= 	'".$lp_question_arr[13]."' 
			,`lp_question14`			= 	'".$lp_question_arr[14]."'
			,`lp_question15`			= 	'".$lp_question_arr[15]."' 
			,`lp_question16`			= 	'".$lp_question_arr[16]."'
			,`lp_question17`			= 	'".$lp_question_arr[17]."' 
			,`lp_question18`			= 	'".$lp_question_arr[18]."'
			,`lp_question19`			= 	'".$lp_question_arr[19]."' 
			,`lp_question20`			= 	'".$lp_question_arr[20]."'
			,`lp_answer4`				= 	'".$lp_answer_arr[4]."' 
			,`lp_answer5`				= 	'".$lp_answer_arr[5]."'
			,`lp_answer6`				= 	'".$lp_answer_arr[6]."' 
			,`lp_answer7`				= 	'".$lp_answer_arr[7]."'
			,`lp_answer8`				= 	'".$lp_answer_arr[8]."' 
			,`lp_answer9`				= 	'".$lp_answer_arr[9]."'
			,`lp_answer10`				= 	'".$lp_answer_arr[10]."' 
			,`lp_answer11`				= 	'".$lp_answer_arr[11]."'
			,`lp_answer12`				= 	'".$lp_answer_arr[12]."' 
			,`lp_answer13`				= 	'".$lp_answer_arr[13]."'
			,`lp_answer14`				= 	'".$lp_answer_arr[14]."' 
			,`lp_answer15`				= 	'".$lp_answer_arr[15]."'
			,`lp_answer16`				= 	'".$lp_answer_arr[16]."' 
			,`lp_answer17`				= 	'".$lp_answer_arr[17]."'
			,`lp_answer18`				= 	'".$lp_answer_arr[18]."' 
			,`lp_answer19`				= 	'".$lp_answer_arr[19]."'
			,`lp_answer20`				= 	'".$lp_answer_arr[20]."'

			,`lp_answer_picture4`		= 	'".$lp_answer_picture_arr[4]."' 
			,`lp_answer_picture5`		= 	'".$lp_answer_picture_arr[5]."'
			,`lp_answer_picture6`		= 	'".$lp_answer_picture_arr[6]."' 
			,`lp_answer_picture7`		= 	'".$lp_answer_picture_arr[7]."'
			,`lp_answer_picture8`		= 	'".$lp_answer_picture_arr[8]."' 
			,`lp_answer_picture9`		= 	'".$lp_answer_picture_arr[9]."'
			,`lp_answer_picture10`		= 	'".$lp_answer_picture_arr[10]."' 
			,`lp_answer_picture11`		= 	'".$lp_answer_picture_arr[11]."'
			,`lp_answer_picture12`		= 	'".$lp_answer_picture_arr[12]."' 
			,`lp_answer_picture13`		= 	'".$lp_answer_picture_arr[13]."'
			,`lp_answer_picture14`		= 	'".$lp_answer_picture_arr[14]."' 
			,`lp_answer_picture15`		= 	'".$lp_answer_picture_arr[15]."'
			,`lp_answer_picture16`		= 	'".$lp_answer_picture_arr[16]."' 
			,`lp_answer_picture17`		= 	'".$lp_answer_picture_arr[17]."'
			,`lp_answer_picture18`		= 	'".$lp_answer_picture_arr[18]."' 
			,`lp_answer_picture19`		= 	'".$lp_answer_picture_arr[19]."'
			,`lp_answer_picture20`		= 	'".$lp_answer_picture_arr[20]."'

			,`lp_answer_detail4`		= 	'".$lp_answer_detail_arr[4]."' 
			,`lp_answer_detail5`		= 	'".$lp_answer_detail_arr[5]."'
			,`lp_answer_detail6`		= 	'".$lp_answer_detail_arr[6]."' 
			,`lp_answer_detail7`		= 	'".$lp_answer_detail_arr[7]."'
			,`lp_answer_detail8`		= 	'".$lp_answer_detail_arr[8]."' 
			,`lp_answer_detail9`		= 	'".$lp_answer_detail_arr[9]."'
			,`lp_answer_detail10`		= 	'".$lp_answer_detail_arr[10]."' 
			,`lp_answer_detail11`		= 	'".$lp_answer_detail_arr[11]."'
			,`lp_answer_detail12`		= 	'".$lp_answer_detail_arr[12]."' 
			,`lp_answer_detail13`		= 	'".$lp_answer_detail_arr[13]."'
			,`lp_answer_detail14`		= 	'".$lp_answer_detail_arr[14]."' 
			,`lp_answer_detail15`		= 	'".$lp_answer_detail_arr[15]."'
			,`lp_answer_detail16`		= 	'".$lp_answer_detail_arr[16]."' 
			,`lp_answer_detail17`		= 	'".$lp_answer_detail_arr[17]."'
			,`lp_answer_detail18`		= 	'".$lp_answer_detail_arr[18]."' 
			,`lp_answer_detail19`		= 	'".$lp_answer_detail_arr[19]."'
			,`lp_answer_detail20`		= 	'".$lp_answer_detail_arr[20]."'

			,`lp_longitude`				= 	'".$lp_longitude."' 
			,`lp_latitude`				= 	'".$lp_latitude."' 
			,`lp_more_details`			= 	'".$lp_more_details."' 
			,`lp_number`				= 	'".$lp_number."' 
			
			".$avd_type1_file."
			".$avd_type2_file."
			".$avd_type3_file."
			,`lp_ins_time`				= 	now() 
			"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
 
	
				
			$AttAns=array("","ปกติ","ไม่ปกติ");
			ini_set('display_errors', 1);
			ini_set('display_startup_errors', 1);
			error_reporting(E_ALL);
			date_default_timezone_set("Asia/Bangkok");
			$sToken      = $ag_token; 
			$tmp = " \n";
			
			$v=1;
			foreach($lp_question_arr as &$lp_question) { 
				if($lp_question!=""){  
					if($lp_question!=''){ $tmp.= $v.'.'.$lp_question; $tmp.= " \n"; }
					if($lp_answer_arr[$v]!=''){ $tmp.='เหตุการณ์ : '.$AttAns[$lp_answer_arr[$v]]; $tmp.= " \n"; } 
					if($lp_answer_detail_arr[$v]!=''){ $tmp.='เหตุผล : '.$lp_answer_detail_arr[$v]; $tmp.= " \n"; } 
					if($lp_answer_picture_arr[$v]!=''){ $tmp.='ภาพประกอบ : '.'https://develophpg.net/timeatt/s.php?P='.$lp_answer_picture_arr[$v]; $tmp.= " \n"; } 
				}
			$v++;
			} 
			if($lp_more_details!=''){ $tmp.= 'บันทึกเพิ่มเติม : '.$lp_more_details; $tmp.= " \n"; } 
			//$imageFile = new CURLFILE($avd_file11); 
			$sMessage = $ag_job; 
			$sMessage.= " \n";  
			$sMessage.= "จุดที่ ".$lp_number1.' '.$lp_time;
			//if($lp_picture!=''){ $sMessage.= " \n";  $sMessage.= "ภาพบุคคลบันทึก : https://develophpg.net/timeatt/showpic.php?dataB=".$lp_picture; }
			$sMessage.= $tmp;
		
			$eToken	=  "bkyMijaVzSnpzWYULW1izKyjI459kgqO1Sex8hysfZL";
			
			$chOne = curl_init(); 
			curl_setopt( $chOne, CURLOPT_URL, "https://notify-api.line.me/api/notify"); 
			curl_setopt( $chOne, CURLOPT_SSL_VERIFYHOST, 0); 
			curl_setopt( $chOne, CURLOPT_SSL_VERIFYPEER, 0); 
			curl_setopt( $chOne, CURLOPT_POST, 1); 
			curl_setopt( $chOne, CURLOPT_POSTFIELDS, "message=".$sMessage); 
			$headers = array( 'Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer '.$sToken.'', );
			curl_setopt($chOne, CURLOPT_HTTPHEADER, $headers); 
			curl_setopt( $chOne, CURLOPT_RETURNTRANSFER, 1); 
			$result = curl_exec( $chOne ); 
		
			//Result error 
			if(curl_error($chOne)) 
			{ 
				echo 'error:' . curl_error($chOne); 
			} 
			else { 
				$result_ = json_decode($result, true); 
				echo "status : ".$result_['status']; echo "message : ". $result_['message'];
			} 
			curl_close( $chOne );    
			
				$chOne = curl_init(); 
			curl_setopt( $chOne, CURLOPT_URL, "https://notify-api.line.me/api/notify"); 
			curl_setopt( $chOne, CURLOPT_SSL_VERIFYHOST, 0); 
			curl_setopt( $chOne, CURLOPT_SSL_VERIFYPEER, 0); 
			curl_setopt( $chOne, CURLOPT_POST, 1); 
			curl_setopt( $chOne, CURLOPT_POSTFIELDS, "message=".$sMessage); 
			$headers = array( 'Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer '.$eToken.'', );
			curl_setopt($chOne, CURLOPT_HTTPHEADER, $headers); 
			curl_setopt( $chOne, CURLOPT_RETURNTRANSFER, 1); 
			$result = curl_exec( $chOne ); 
		
			//Result error 
			if(curl_error($chOne)) 
			{ 
				echo 'error:' . curl_error($chOne); 
			} 
			else { 
				$result_ = json_decode($result, true); 
				echo "status : ".$result_['status']; echo "message : ". $result_['message'];
			} 
			curl_close( $chOne );  
		  
		  echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="checkcomplete.php?cp_ag_id='.$lp_link_cp_ag_id.'&cp_point_number='.$lp_link_cp_point_number.'";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
				

	}
}

if($SubmitH=="Submit_9"){
	$asp_shop_id			= 	$_POST['process_id'];
	$class_name_arr		= 	$_POST['class_name']; 
	$class_name2_arr		= 	$_POST['class_name2']; 
	
//	$x=0;
//	foreach($asp_name_arr as &$asp_name) { 
//		if($asp_name!=''){  
//				 $sql="SELECT asp_name
//					  FROM tb_agency_sparepart
//					  WHERE asp_name 	= '".$asp_name."'
//					  AND asp_shop_id	= '".$asp_shop_id."' 
//					  AND asp_unit_id	= '".$asp_unit_id."' 
//					  LIMIT 0 , 1
//					  ";
//				$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
//				$num_rows =mysqli_num_rows($query);
//				if($num_rows>=1){
//					$rs=mysqli_fetch_array($query); 
//					?>
//					<script>
//							 alert('ระบบมีข้อมูล<?php echo $rs['asp_name']; ?> อยู่แล้ว กรุณาตรวจสอบใหม่อีกครั้ง'); 
//					 </script>
//					<?php 
//					exit();
//				}     
//		}
//		$x++;
//	}    
//	
	$i=0;
	foreach($class_name_arr as &$class_name) { 
		if($class_name!=''){  
			$sql="INSERT INTO tb_list_check_head SET 
				  `ch_agency`		= 	'".$asp_shop_id."'
				 ,`ch_work_time`	= 	'".$class_name."'    
				 ,`ch_end_time`		= 	'".$class_name2_arr[$i]."'    
				 ,`ch_time`			= 	NOW()
				 ";
			echo $sql.'<br />';
			$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect)); 
		}
		$i++;
	}     
	echo'
		<script language="javascript"> 
		  window.parent.fn_list_Process10();  
		  alert("บันทึกเรียบร้อย");
		</script>
		';
		
	echo'
		<script language="javascript"> 
		  window.parent.fn_hide_update1();  
		</script>
		';
}

if($SubmitH=="Submit_10"){
	$cd_ag_id			= 	$_POST['cd_ag_id'];
	$cd_cp_id			= 	$_POST['cd_cp_id'];
	$class_name_arr		= 	$_POST['class_name']; 
	$cd_number			= 	$_POST['cd_number']; 
	
	echo 'sdfdsfsfd';
	echo  print_r($class_name_arr);
//	$x=0;
//	foreach($asp_name_arr as &$asp_name) { 
//		if($asp_name!=''){  
//				 $sql="SELECT asp_name
//					  FROM tb_agency_sparepart
//					  WHERE asp_name 	= '".$asp_name."'
//					  AND asp_shop_id	= '".$asp_shop_id."' 
//					  AND asp_unit_id	= '".$asp_unit_id."' 
//					  LIMIT 0 , 1
//					  ";
//				$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
//				$num_rows =mysqli_num_rows($query);
//				if($num_rows>=1){
//					$rs=mysqli_fetch_array($query); 
//					?>
//					<script>
//							 alert('ระบบมีข้อมูล<?php echo $rs['asp_name']; ?> อยู่แล้ว กรุณาตรวจสอบใหม่อีกครั้ง'); 
//					 </script>
//					<?php 
//					exit();
//				}     
//		}
//		$x++;
//	}    
//	
	$i=0;
	foreach($class_name_arr as &$class_name) { 
		if($class_name!=''){  
			$sql="INSERT INTO tb_list_check_detail SET 
				  `cd_ag_id`		= 	'".$cd_ag_id."'
				 ,`cd_cp_id`		= 	'".$class_name."'    
				 ,`cd_ch_id`		= 	'".$cd_cp_id."'    
				 ,`cd_number`		= 	'".$cd_number."'    
				 ,`cd_status`		= 	'1'     
				 ,`cd_ins`			= 	NOW()
				 ";
			echo $sql.'<br />';
			$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect)); 
		}
		$i++;
	}     
	echo'
		<script language="javascript"> 
		  window.parent.fn_list_Process10();  
		  alert("บันทึกเรียบร้อย");
		</script>
		';
		
	echo'
		<script language="javascript"> 
		  window.parent.fn_hide_update2();  
		</script>
		';
}
if($SubmitH=="Submit_11") { 
	$lp_check_point				= 	$_POST['lp_check_point'];
	$ch_work_time			= 	$_POST['ch_work_time'];
	$ch_number				= 	$_POST['ch_number'];
	$cpb_groupanswer		= 	$_POST['cpb_groupanswer'];
	$lc_id					= 	$_POST['lc_id'];

	
 
	if($status=='1'){
		$sql="UPDATE tb_list_point SET  
			`lp_check_point`			= 	'".$lp_check_point."' 
			,`ch_work_time`				= 	'".$ch_work_time."'
			,`ch_number`				= 	'".$ch_number."' 
			,`ch_recorder_name`			= 	'".$ch_recorder_name."'  
			,`ch_time`					= 	now()
			WHERE ch_id					= 	'".$ch_id."'
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_06.php";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_list_check_head SET 
			`lp_check_point`				= 	'".$lp_check_point."' 
			,`ch_work_time`				= 	'".$ch_work_time."'
			,`ch_number`				= 	'".$ch_number."'
			,`ch_recorder_name`			= 	'".$ch_recorder_name."'
			,`ch_time`					= 	now()
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
 
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_06.php?cp_ag_id='.$cp_ag_id.'";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}

if($SubmitH=="Submit_12") { 
	$lp_check_point				= 	$_POST['lp_check_point'];
	$ch_work_time			= 	$_POST['ch_work_time'];
	$ch_number				= 	$_POST['ch_number'];
	$cpb_groupanswer		= 	$_POST['cpb_groupanswer'];
	$lc_id					= 	$_POST['lc_id'];

	
 
	if($status=='1'){
		$sql="UPDATE tb_list_point SET  
			`lp_check_point`			= 	'".$lp_check_point."' 
			,`ch_work_time`				= 	'".$ch_work_time."'
			,`ch_number`				= 	'".$ch_number."' 
			,`ch_recorder_name`			= 	'".$ch_recorder_name."'  
			,`ch_time`					= 	now()
			WHERE ch_id					= 	'".$ch_id."'
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_06.php";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_list_check_head SET 
			`lp_check_point`				= 	'".$lp_check_point."' 
			,`ch_work_time`				= 	'".$ch_work_time."'
			,`ch_number`				= 	'".$ch_number."'
			,`ch_recorder_name`			= 	'".$ch_recorder_name."'
			,`ch_time`					= 	now()
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
 
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_06.php?cp_ag_id='.$cp_ag_id.'";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}

if($SubmitH=="Submit_13") { 
	$lp_check_point		= 	$_POST['lp_check_point'];
	$lp_date			= 	$_POST['lp_date'];
	$lp_ch_id			= 	$_POST['lp_ch_id'];
	$lp_picture			= 	$_POST['lp_picture'];
	$lp_question1		= 	$_POST['lp_question1'];
	$lp_answer1			= 	$_POST['lp_answer1'];
	$lp_question2		= 	$_POST['lp_question2'];
	$lp_answer2			= 	$_POST['lp_answer2'];
	$lp_question3		= 	$_POST['lp_question3'];
	$lp_answer3			= 	$_POST['lp_answer3'];
	$lp_longitude		= 	$_POST['lp_longitude'];
	$lp_latitude		= 	$_POST['lp_latitude'];
	$lp_more_details	= 	$_POST['lp_more_details'];
	$lp_number			= 	$_POST['lp_number'];
	$lp_recorder_name	= 	$_POST['lp_recorder_name'];
	$status				= 	$_POST['status'];
	$lp_id				= 	$_POST['lp_id'];
	$asp_unit_id				= 	$_POST['asp_unit_id'];
	$ch_work_time				= 	$_POST['ch_work_time'];
	$cp_point_name				= 	$_POST['cp_point_name'];
	$cp_point_number				= 	$_POST['cp_point_number'];

	
 
	if($status=='1'){
		$sql="UPDATE tb_list_point SET 
			`lp_agency`					= 	'".$lp_agency."' 
			,`lp_check_point`			= 	'".$lp_check_point."' 
			,`lp_ch_id`					= 	'".$lp_ch_id."' 
			,`lp_picture`				= 	'".$lp_picture."' 
			,`lp_question1`				= 	'".$lp_question_arr[1]."'
			,`lp_answer1`				= 	'".$lp_answer_arr[1]."' 
			,`lp_question2`				= 	'".$lp_question_arr[2]."'
			,`lp_answer2`				= 	'".$lp_answer_arr[2]."' 
			,`lp_question3`				= 	'".$lp_question_arr[3]."'
			,`lp_answer3`				= 	'".$lp_answer_arr[3]."' 
			,`lp_longitude`				= 	'".$lp_longitude."' 
			,`lp_latitude`				= 	'".$lp_latitude."' 
			,`lp_more_details`			= 	'".$lp_more_details."' 
			,`lp_number`				= 	'".$lp_number."' 
			,`lp_recorder_name`			= 	'".$lp_recorder_name."'  
			
			".$avd_type1_file."
			".$avd_type2_file."
			".$avd_type3_file."
			WHERE lp_id 				= 	'".$lp_id."'
			";  
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_07.php";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}
}
if($SubmitH=="Submit_14") { 
	$cq_answer				= 	$_POST['cq_answer'];
	$cq_id					= 	$_POST['cq_id'];

	
 
	if($status=='1'){
		$sql="UPDATE tb_category_questions SET  
			`cq_answer`			= 	'".$cq_answer."' 
			WHERE cq_id					= 	'".$cq_id."'
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_011.php";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_category_questions SET 
			`cq_answer`				= 	'".$cq_answer."' 
			";
			echo $sql;
			exit();
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
 
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_011.php?cq_id='.$cq_id.'";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}
if($SubmitH=="Submit_15") { 
	$permis_id				= 	$_POST['permis_id']; 

	
 		$sql="DELETE FROM tb_list_check_detail
		  WHERE `cd_id`					= 	'".$permis_id."'
		 "; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		
		echo'
			<script language="javascript">
			alert("ลบข้อมูลเรียบร้อย");
			// window.parent.location.href="menu_011.php";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	 
}

if($SubmitH=="Submit_16") { 
	$permis_id				= 	$_POST['permis_id']; 

 		$sql="DELETE FROM tb_list_check_head
		  WHERE `tb_list_check_head`					= 	'".$permis_id."'
		 "; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
	
 		$sql="DELETE FROM tb_list_check_detail
		  WHERE `cd_id`					= 	'".$permis_id."'
		 "; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		
		echo'
			<script language="javascript">
			alert("ลบข้อมูลเรียบร้อย");
			// window.parent.location.href="menu_011.php";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	 
}

if($SubmitH=="Submit_17") { 
	$sn_id				= 	$_POST['sn_id'];
	$sn_name			= 	$_POST['sn_name'];
	$sn_lastname		= 	$_POST['sn_lastname'];
	$sn_agency			= 	$_POST['sn_agency'];
	$sn_shift			= 	$_POST['sn_shift'];
	$sn_prefix			= 	$_POST['sn_prefix'];
	$sn_prefix_to		= 	$_POST['sn_prefix_to'];
	$status				= 	$_POST['status'];

	
 
	if($status=='1'){
		$sql="UPDATE tb_security_name SET  
			`sn_name`					= 	'".$sn_name."' 
			,`sn_lastname`				= 	'".$sn_lastname."'
			,`sn_agency`				= 	'".$sn_agency."' 
			,`sn_shift`					= 	'".$sn_shift."'
			,`sn_prefix`				= 	'".$sn_prefix."'   
			,`sn_prefix_to`				= 	'".$sn_prefix_to."'    
			WHERE sn_id					= 	'".$sn_id."'
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_012.php";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_security_name SET 
			`sn_name`					= 	'".$sn_name."' 
			,`sn_lastname`				= 	'".$sn_lastname."'
			,`sn_agency`				= 	'".$sn_agency."'
			,`sn_shift`					= 	'".$sn_shift."'
			,`sn_prefix`				= 	'".$sn_prefix."'
			,`sn_prefix_to`				= 	'".$sn_prefix_to."'   
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
 
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_012.php?sn_id='.$sn_id.'";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}


if($SubmitH=="Submit_18") { 
			ini_set('memory_limit', '64M');
			ini_set('max_execution_time', 5000);
			
	$lp_picture				= 	$_POST['lp_picture'];
	$lp_agency				= 	$_POST['lp_agency'];
	$lp_check_point			= 	$_POST['lp_check_point'];
	$lp_latitude			= 	$_POST['lp_latitude'];
	$lp_longitude			= 	$_POST['lp_longitude'];  
	$lp_answer_arr			= 	$_POST['lp_answer']; 
	$lp_question_arr		= 	$_POST['lp_question1'];  
	$lp_answer_detail_arr	= 	$_POST['lp_answer_detail']; 
	$lp_answer_picture_arr	= 	$_POST['lp_answer_picture']; 
	$lp_answer_picture_1_arr	= 	$_POST['lp_answer_picture_1']; 
	$lp_answer_picture_2_arr	= 	$_POST['lp_answer_picture_2']; 
	$file1					= 	$_POST['file1']; 
	$file2					= 	$_POST['file2']; 
	$file3					= 	$_POST['file3']; 
	$lp_more_details		= 	$_POST['lp_more_details']; 
	$lp_ch_id				= 	$_POST['lp_ch_id']; 
	$lp_number				= 	$_POST['lp_number']; 
	$lp_number1				= 	$_POST['lp_number']; 
	$ag_job					= 	$_POST['ag_job'];
	$lp_time				= 	$_POST['lp_time']; 
	$ag_token				= 	$_POST['ag_token']; 
	$lp_cp_point_name				= 	$_POST['lp_cp_point_name']; 
	$lp_answer_picture_tmpFoder_arr			= 	$_POST['lp_answer_picture_tmpFoder']; 
	$lp_link_cp_ag_id					= 	$_POST['lp_link_cp_ag_id']; 
	$lp_link_cp_point_number 			= 	$_POST['lp_link_cp_point_number']; 
	$ls_check_head 			= 	$_POST['ls_check_head']; 
	$ls_user_security 			= 	$_POST['ls_user_security']; 
	$ls_user_security_arr 			= 	explode("|",$ls_user_security); 
	$ls_security_id					= 	$ls_user_security_arr[0]; 
	$ls_security_name				= 	$ls_user_security_arr[1]; 
	if($lp_number1=='0'){ $lp_number1 = $lp_cp_point_name;  }elseif($lp_number1=='1'){ $lp_number1 = $lp_cp_point_name;  }else{  $lp_number1 = ($lp_number1-1).' '.$lp_cp_point_name;  }  
	echo 'sdffdsf';
	echo print_r($lp_answer_picture_arr);
	//sexit();
	//echo  $file1;
	//$i=1;
//	foreach($lp_answer_picture_arr as &$lp_answer_picture) { 
//		if($lp_answer_picture!=''){  
//			$sql="INSERT INTO tb_list_check_head SET 
//				  `ch_agency`		= 	'".$asp_shop_id."'
//				 ,`ch_work_time`	= 	'".$class_name."'    
//				 ,`ch_end_time`		= 	'".$class_name2_arr[$i]."'    
//				 ,`ch_time`			= 	NOW()
//				 ";
//			echo $sql.'<br />';
//		//	$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect)); 
//		}
//		$i++;
//	}
	
//	$ag = $lp_agency;
//	$filename=date("ymdHis").$ag.$i.'.txt';
//	$tmpFolder='AttFile2/'.$ag;
//	if(!file_exists($tmpFolder)) mkdir($tmpFolder, 0777);
//	$tmpFolderMY='AttFile2/'.$ag.'/AttFile'.date("y-m");
//	if(!file_exists($tmpFolderMY)) mkdir($tmpFolderMY, 0777);
//	$tmpFolderMYD='AttFile2/'.$ag.'/AttFile'.date("y-m").'/'.date("y-m-d");
//	if(!file_exists($tmpFolderMYD)) mkdir($tmpFolderMYD, 0777);
//			$myfile = fopen($tmpFolderFILE, "a") or die("Unable to open file!");
//			fwrite($myfile, ''); 
	
	$ag = $lp_agency;
	$filename=date("ymdHis").$ag.$i.'.txt';
	$tmpFolder='A/'.$ag;
	if(!file_exists($tmpFolder)) mkdir($tmpFolder, 0777);
	$tmpFolderMY='A/'.$ag.'/'.date("ym");
	if(!file_exists($tmpFolderMY)) mkdir($tmpFolderMY, 0777);
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
	if(!file_exists($tmpFolderMYD)) mkdir($tmpFolderMYD, 0777);
	
	if($lp_answer_picture_arr[1]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-1.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file1!");
		$Text = $lp_answer_picture_arr[1];
		fwrite($myfile, $Text); 
		$lp_answer_picture_arr[1] = $tmpFolderFILE;
	}
	
	if($lp_answer_picture_1_arr[1]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-11.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file1!");
		$Text = $lp_answer_picture_1_arr[1];
		fwrite($myfile, $Text); 
		$lp_answer_picture_1_arr[1] = $tmpFolderFILE;
	}
	
	if($lp_answer_picture_2_arr[1]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-12.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file1!");
		$Text = $lp_answer_picture_2_arr[1];
		fwrite($myfile, $Text); 
		$lp_answer_picture_2_arr[1] = $tmpFolderFILE;
	}
	
	
	if($lp_answer_picture_arr[2]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-2.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file2!");
		$Text = $lp_answer_picture_arr[2];
		fwrite($myfile, $Text); 
		$lp_answer_picture_arr[2] = $tmpFolderFILE;
	}
	
	if($lp_answer_picture_1_arr[2]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-21.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file1!");
		$Text = $lp_answer_picture_1_arr[2];
		fwrite($myfile, $Text); 
		$lp_answer_picture_1_arr[2] = $tmpFolderFILE;
	}
	
	if($lp_answer_picture_2_arr[2]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-22.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file1!");
		$Text = $lp_answer_picture_2_arr[2];
		fwrite($myfile, $Text); 
		$lp_answer_picture_2_arr[2] = $tmpFolderFILE;
	}
	
	if($lp_answer_picture_arr[3]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-3.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file3!");
		$Text = $lp_answer_picture_arr[3];
		fwrite($myfile, $Text); 
		$lp_answer_picture_arr[3] = $tmpFolderFILE;
	}
	
	if($lp_answer_picture_1_arr[3]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-31.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file1!");
		$Text = $lp_answer_picture_1_arr[3];
		fwrite($myfile, $Text); 
		$lp_answer_picture_1_arr[3] = $tmpFolderFILE;
	}
	
	if($lp_answer_picture_2_arr[3]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-32.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file1!");
		$Text = $lp_answer_picture_2_arr[3];
		fwrite($myfile, $Text); 
		$lp_answer_picture_2_arr[3] = $tmpFolderFILE;
	}
	
	
	
	if($lp_answer_picture_arr[4]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-4.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file3!");
		$Text = $lp_answer_picture_arr[4];
		fwrite($myfile, $Text); 
		$lp_answer_picture_arr[4] = $tmpFolderFILE;
	}
	
	if($lp_answer_picture_1_arr[4]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-41.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file1!");
		$Text = $lp_answer_picture_1_arr[4];
		fwrite($myfile, $Text); 
		$lp_answer_picture_1_arr[4] = $tmpFolderFILE;
	}
	
	if($lp_answer_picture_2_arr[4]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-42.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file1!");
		$Text = $lp_answer_picture_2_arr[4];
		fwrite($myfile, $Text); 
		$lp_answer_picture_2_arr[4] = $tmpFolderFILE;
	}
	
	
	
	
	if($lp_answer_picture_arr[5]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-5.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file3!");
		$Text = $lp_answer_picture_arr[5];
		fwrite($myfile, $Text); 
		$lp_answer_picture_arr[5] = $tmpFolderFILE;
	}
	
	if($lp_answer_picture_1_arr[5]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-51.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file1!");
		$Text = $lp_answer_picture_1_arr[5];
		fwrite($myfile, $Text); 
		$lp_answer_picture_1_arr[5] = $tmpFolderFILE;
	}
	
	if($lp_answer_picture_2_arr[5]!='') { 
		$ag = $lp_agency;
		$filename=date("ymdHis").$ag.'-52.txt'; 
		$tmpFolderMYD='A/'.$ag.'/'.date("ym").'/'.date("d");
		$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
		
		$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file1!");
		$Text = $lp_answer_picture_2_arr[5];
		fwrite($myfile, $Text); 
		$lp_answer_picture_2_arr[5] = $tmpFolderFILE;
	}
	//$i=1;
//	foreach($lp_answer_picture_arr as &$lp_answer_picture) { 
//		if($lp_answer_picture!=""){  
//			$ag = $lp_agency;
//			$filename=date("ymdHis").$ag.$i.'.txt';
//			$tmpFolder='AttFile2/'.$ag;
//			if(!file_exists($tmpFolder)) mkdir($tmpFolder, 0777);
//			$tmpFolderMY='AttFile2/'.$ag.'/AttFile'.date("y-m");
//			if(!file_exists($tmpFolderMY)) mkdir($tmpFolderMY, 0777);
//			$tmpFolderMYD='AttFile2/'.$ag.'/AttFile'.date("y-m").'/'.date("y-m-d");
//			if(!file_exists($tmpFolderMYD)) mkdir($tmpFolderMYD, 0777);
//			$tmpFolderFILE=$tmpFolderMYD.'/'.$filename;
//			
//			$myfile = fopen($tmpFolderFILE, "w") or die("Unable to open file!");
//			$Text = $lp_answer_picture;
//			fwrite($myfile, $Text); 
//			$lp_answer_picture_arr[$i] = $tmpFolderFILE;
//		} 
//		$i++;
//	}
	 
	 
	 
	 
	if($status=='1'){
		$sql="UPDATE tb_list_point SET  
			`ch_agency`					= 	'".$ch_agency."' 
			,`ch_work_time`				= 	'".$ch_work_time."'
			,`ch_number`				= 	'".$ch_number."' 
			,`ch_recorder_name`			= 	'".$ch_recorder_name."'  
			,`ch_time`					= 	now()
			WHERE ch_id					= 	'".$ch_id."'
			";
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="menu_06.php";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{ 	
		
		 
		
		$sql="INSERT INTO tb_list_point SET 
			`lp_agency`					= 	'".$lp_agency."' 
			,`lp_check_point`			= 	'".$lp_check_point."'
			,`lp_date`					= 	now()
			,`ls_check_head`			= 	'".$ls_check_head."'   
			,`ls_security_id`			= 	'".$ls_security_id."'   
			,`ls_security_name`			= 	'".$ls_security_name."'   
			,`lp_ch_id`					= 	'".$lp_ch_id."' 
			,`lp_picture`				= 	'".$lp_picture."' 
			,`lp_cp_point_name`			= 	'".$lp_cp_point_name."'  
			,`lp_question1`				= 	'".$lp_question_arr[1]."'
			,`lp_answer1`				= 	'".$lp_answer_arr[1]."' 
			,`lp_answer_picture1`		= 	'".$lp_answer_picture_arr[1]."'
			,`lp_answer_detail1`		= 	'".$lp_answer_detail_arr[1]."' 
			,`lp_question2`				= 	'".$lp_question_arr[2]."'
			,`lp_answer2`				= 	'".$lp_answer_arr[2]."' 
			,`lp_answer_picture2`		= 	'".$lp_answer_picture_arr[2]."'
			,`lp_answer_detail2`		= 	'".$lp_answer_detail_arr[2]."'
			,`lp_question3`				= 	'".$lp_question_arr[3]."'
			,`lp_answer3`				= 	'".$lp_answer_arr[3]."'
			,`lp_answer_picture3`		= 	'".$lp_answer_picture_arr[3]."'
			,`lp_answer_detail3`		= 	'".$lp_answer_detail_arr[3]."'
			,`lp_question4`				= 	'".$lp_question_arr[4]."'
			,`lp_question5`				= 	'".$lp_question_arr[5]."' 
			,`lp_question6`				= 	'".$lp_question_arr[6]."'
			,`lp_question7`				= 	'".$lp_question_arr[7]."' 
			,`lp_question8`				= 	'".$lp_question_arr[8]."'
			,`lp_question9`				= 	'".$lp_question_arr[9]."' 
			,`lp_question10`			= 	'".$lp_question_arr[10]."'
			,`lp_question11`			= 	'".$lp_question_arr[11]."' 
			,`lp_question12`			= 	'".$lp_question_arr[12]."'
			,`lp_question13`			= 	'".$lp_question_arr[13]."' 
			,`lp_question14`			= 	'".$lp_question_arr[14]."'
			,`lp_question15`			= 	'".$lp_question_arr[15]."' 
			,`lp_question16`			= 	'".$lp_question_arr[16]."'
			,`lp_question17`			= 	'".$lp_question_arr[17]."' 
			,`lp_question18`			= 	'".$lp_question_arr[18]."'
			,`lp_question19`			= 	'".$lp_question_arr[19]."' 
			,`lp_question20`			= 	'".$lp_question_arr[20]."'
			,`lp_answer4`				= 	'".$lp_answer_arr[4]."' 
			,`lp_answer5`				= 	'".$lp_answer_arr[5]."'
			,`lp_answer6`				= 	'".$lp_answer_arr[6]."' 
			,`lp_answer7`				= 	'".$lp_answer_arr[7]."'
			,`lp_answer8`				= 	'".$lp_answer_arr[8]."' 
			,`lp_answer9`				= 	'".$lp_answer_arr[9]."'
			,`lp_answer10`				= 	'".$lp_answer_arr[10]."' 
			,`lp_answer11`				= 	'".$lp_answer_arr[11]."'
			,`lp_answer12`				= 	'".$lp_answer_arr[12]."' 
			,`lp_answer13`				= 	'".$lp_answer_arr[13]."'
			,`lp_answer14`				= 	'".$lp_answer_arr[14]."' 
			,`lp_answer15`				= 	'".$lp_answer_arr[15]."'
			,`lp_answer16`				= 	'".$lp_answer_arr[16]."' 
			,`lp_answer17`				= 	'".$lp_answer_arr[17]."'
			,`lp_answer18`				= 	'".$lp_answer_arr[18]."' 
			,`lp_answer19`				= 	'".$lp_answer_arr[19]."'
			,`lp_answer20`				= 	'".$lp_answer_arr[20]."'

			,`lp_answer_picture4`		= 	'".$lp_answer_picture_arr[4]."' 
			,`lp_answer_picture5`		= 	'".$lp_answer_picture_arr[5]."'
			,`lp_answer_picture6`		= 	'".$lp_answer_picture_arr[6]."' 
			,`lp_answer_picture7`		= 	'".$lp_answer_picture_arr[7]."'
			,`lp_answer_picture8`		= 	'".$lp_answer_picture_arr[8]."' 
			,`lp_answer_picture9`		= 	'".$lp_answer_picture_arr[9]."'
			,`lp_answer_picture10`		= 	'".$lp_answer_picture_arr[10]."' 
			,`lp_answer_picture11`		= 	'".$lp_answer_picture_arr[11]."'
			,`lp_answer_picture12`		= 	'".$lp_answer_picture_arr[12]."' 
			,`lp_answer_picture13`		= 	'".$lp_answer_picture_arr[13]."'
			,`lp_answer_picture14`		= 	'".$lp_answer_picture_arr[14]."' 
			,`lp_answer_picture15`		= 	'".$lp_answer_picture_arr[15]."'
			,`lp_answer_picture16`		= 	'".$lp_answer_picture_arr[16]."' 
			,`lp_answer_picture17`		= 	'".$lp_answer_picture_arr[17]."'
			,`lp_answer_picture18`		= 	'".$lp_answer_picture_arr[18]."' 
			,`lp_answer_picture19`		= 	'".$lp_answer_picture_arr[19]."'
			,`lp_answer_picture20`		= 	'".$lp_answer_picture_arr[20]."'

			,`lp_answer_detail4`		= 	'".$lp_answer_detail_arr[4]."' 
			,`lp_answer_detail5`		= 	'".$lp_answer_detail_arr[5]."'
			,`lp_answer_detail6`		= 	'".$lp_answer_detail_arr[6]."' 
			,`lp_answer_detail7`		= 	'".$lp_answer_detail_arr[7]."'
			,`lp_answer_detail8`		= 	'".$lp_answer_detail_arr[8]."' 
			,`lp_answer_detail9`		= 	'".$lp_answer_detail_arr[9]."'
			,`lp_answer_detail10`		= 	'".$lp_answer_detail_arr[10]."' 
			,`lp_answer_detail11`		= 	'".$lp_answer_detail_arr[11]."'
			,`lp_answer_detail12`		= 	'".$lp_answer_detail_arr[12]."' 
			,`lp_answer_detail13`		= 	'".$lp_answer_detail_arr[13]."'
			,`lp_answer_detail14`		= 	'".$lp_answer_detail_arr[14]."' 
			,`lp_answer_detail15`		= 	'".$lp_answer_detail_arr[15]."'
			,`lp_answer_detail16`		= 	'".$lp_answer_detail_arr[16]."' 
			,`lp_answer_detail17`		= 	'".$lp_answer_detail_arr[17]."'
			,`lp_answer_detail18`		= 	'".$lp_answer_detail_arr[18]."' 
			,`lp_answer_detail19`		= 	'".$lp_answer_detail_arr[19]."'
			,`lp_answer_detail20`		= 	'".$lp_answer_detail_arr[20]."'
			
			
			,`lp_answer_picture1_1`		= 	'".$lp_answer_picture_1_arr[1]."'  
			,`lp_answer_picture2_1`		= 	'".$lp_answer_picture_1_arr[2]."'  
			,`lp_answer_picture3_1`		= 	'".$lp_answer_picture_1_arr[3]."'  
			,`lp_answer_picture4_1`		= 	'".$lp_answer_picture_1_arr[4]."'  
			,`lp_answer_picture5_1`		= 	'".$lp_answer_picture_1_arr[5]."'  
			,`lp_answer_picture6_1`		= 	'".$lp_answer_picture_1_arr[6]."'  
			,`lp_answer_picture7_1`		= 	'".$lp_answer_picture_1_arr[7]."'  
			,`lp_answer_picture8_1`		= 	'".$lp_answer_picture_1_arr[8]."'  
			,`lp_answer_picture9_1`		= 	'".$lp_answer_picture_1_arr[9]."'  
			,`lp_answer_picture10_1`	= 	'".$lp_answer_picture_1_arr[10]."'  

			
			,`lp_answer_picture1_2`		= 	'".$lp_answer_picture_2_arr[1]."'  
			,`lp_answer_picture2_2`		= 	'".$lp_answer_picture_2_arr[2]."'  
			,`lp_answer_picture3_2`		= 	'".$lp_answer_picture_2_arr[3]."'  
			,`lp_answer_picture4_2`		= 	'".$lp_answer_picture_2_arr[4]."'  
			,`lp_answer_picture5_2`		= 	'".$lp_answer_picture_2_arr[5]."'  
			,`lp_answer_picture6_2`		= 	'".$lp_answer_picture_2_arr[6]."'  
			,`lp_answer_picture7_2`		= 	'".$lp_answer_picture_2_arr[7]."'  
			,`lp_answer_picture8_2`		= 	'".$lp_answer_picture_2_arr[8]."'  
			,`lp_answer_picture9_2`		= 	'".$lp_answer_picture_2_arr[9]."'  
			,`lp_answer_picture10_2`	= 	'".$lp_answer_picture_2_arr[10]."'  


			,`lp_longitude`				= 	'".$lp_longitude."' 
			,`lp_latitude`				= 	'".$lp_latitude."' 
			,`lp_more_details`			= 	'".$lp_more_details."' 
			,`lp_number`				= 	'".$lp_number."' 
			
			".$avd_type1_file."
			".$avd_type2_file."
			".$avd_type3_file."
			,`lp_ins_time`				= 	now() 
			"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
 
	
				
			$AttAns=array("","ปกติ","ไม่ปกติ");
			ini_set('display_errors', 1);
			ini_set('display_startup_errors', 1);
			error_reporting(E_ALL);
			date_default_timezone_set("Asia/Bangkok");
			$sToken      = $ag_token; 
			$tmp = " \n";
			
			$v=1;
			foreach($lp_question_arr as &$lp_question) { 
				if($lp_question!=""){  
					if($lp_question!=''){ $tmp.= $v.'.'.$lp_question; $tmp.= " \n"; }
					if($lp_answer_arr[$v]!=''){ $tmp.='เหตุการณ์ : '.$AttAns[$lp_answer_arr[$v]]; $tmp.= " \n"; } 
					if($lp_answer_detail_arr[$v]!=''){ $tmp.='เหตุผล : '.$lp_answer_detail_arr[$v]; $tmp.= " \n"; } 
					if($lp_answer_picture_1_arr[$v]!=''){ $tmp.='ภาพประกอบ : '.'https://develophpg.net/timeatt/s.php?P='.$lp_answer_picture_1_arr[$v]; $tmp.= " \n"; } 
					if($lp_answer_picture_2_arr[$v]!=''){ $tmp.='ภาพประกอบ : '.'https://develophpg.net/timeatt/s.php?P='.$lp_answer_picture_2_arr[$v]; $tmp.= " \n"; } 
					if($lp_answer_picture_arr[$v]!=''){ $tmp.='ภาพประกอบ : '.'https://develophpg.net/timeatt/s.php?P='.$lp_answer_picture_arr[$v]; $tmp.= " \n"; } 
				}
			$v++;
			} 
			if($ls_security_name!=''){ $tmp.= 'ชื่อผู้ตรวจ : '.$ls_security_name; $tmp.= " \n"; } 
			if($lp_more_details!=''){ $tmp.= 'บันทึกเพิ่มเติม : '.$lp_more_details; $tmp.= " \n"; } 
			//$imageFile = new CURLFILE($avd_file11); 
			$sMessage = $ag_job; 
			$sMessage.= " \n";  
			$sMessage.= "จุดที่ ".$lp_number1.' '.$lp_time;
			//if($lp_picture!=''){ $sMessage.= " \n";  $sMessage.= "ภาพบุคคลบันทึก : https://develophpg.net/timeatt/showpic.php?dataB=".$lp_picture; }
			$sMessage.= $tmp;
		
			
			$chOne = curl_init(); 
			curl_setopt( $chOne, CURLOPT_URL, "https://notify-api.line.me/api/notify"); 
			curl_setopt( $chOne, CURLOPT_SSL_VERIFYHOST, 0); 
			curl_setopt( $chOne, CURLOPT_SSL_VERIFYPEER, 0); 
			curl_setopt( $chOne, CURLOPT_POST, 1); 
			curl_setopt( $chOne, CURLOPT_POSTFIELDS, "message=".$sMessage); 
			$headers = array( 'Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer '.$sToken.'', );
			curl_setopt($chOne, CURLOPT_HTTPHEADER, $headers); 
			curl_setopt( $chOne, CURLOPT_RETURNTRANSFER, 1); 
			$result = curl_exec( $chOne ); 
		
			//Result error 
			if(curl_error($chOne)) 
			{ 
				echo 'error:' . curl_error($chOne); 
			} 
			else { 
				$result_ = json_decode($result, true); 
				echo "status : ".$result_['status']; echo "message : ". $result_['message'];
			} 
			curl_close( $chOne );    
			
			//$eToken	=  "wGyqsZ6Gy3SwD6ls9Hd5PXrfmBUJADei71lmUaEJhLV   ";
			
				$chOne = curl_init(); 
			curl_setopt( $chOne, CURLOPT_URL, "https://notify-api.line.me/api/notify"); 
			curl_setopt( $chOne, CURLOPT_SSL_VERIFYHOST, 0); 
			curl_setopt( $chOne, CURLOPT_SSL_VERIFYPEER, 0); 
			curl_setopt( $chOne, CURLOPT_POST, 1); 
			curl_setopt( $chOne, CURLOPT_POSTFIELDS, "message=".$sMessage); 
			$headers = array( 'Content-type: application/x-www-form-urlencoded', 'Authorization: Bearer '.$eToken.'', );
			curl_setopt($chOne, CURLOPT_HTTPHEADER, $headers); 
			curl_setopt( $chOne, CURLOPT_RETURNTRANSFER, 1); 
			$result = curl_exec( $chOne ); 
		
			//Result error 
			if(curl_error($chOne)) 
			{ 
				echo 'error:' . curl_error($chOne); 
			} 
			else { 
				$result_ = json_decode($result, true); 
				echo "status : ".$result_['status']; echo "message : ". $result_['message'];
			} 
			curl_close( $chOne );  
		  
		  echo'
			<script language="javascript">
			alert("บันทึกข้อมูลเรียบร้อย");
			 window.parent.location.href="checkcomplete.php?cp_ag_id='.$lp_link_cp_ag_id.'&cp_point_number='.$lp_link_cp_point_number.'";
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
				

	}
}


if($SubmitH=="Submit_19_1") { 

	$permis_id				= 	$_POST['user_id']; 
	
	$sql="DELETE FROM tb_permission_process_approve
		WHERE  `permis_id`			= 	'".$permis_id."' 
	 "; 
	include "inc_logging.php"; 
	$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect)); 
}

if($SubmitH=="Submit_19") {
	
	$process_id				= 	$_POST['process_id'];
	$user_id_arr			= 	$_POST['user_id'];  
	$approve_level_arr 		= 	$_POST['approve_level'];    
	$i=0;
	//echo 'dsdsddss'.$approve_level_arr;
	foreach ($approve_level_arr as &$approve_level) { 
		if($approve_level!=''){  
			$sql="INSERT INTO tb_permission_process_approve SET 
				   `user_id`		= '".$user_id_arr[$i]."' 
				 ,`process_id`		= '".$process_id."'  
				 ,`approve_level`	= '".$approve_level."' 
				 ";
			echo $sql.'<br />';
			include "inc_logging.php"; 
			$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect)); 
		}
		$i++;
	}    
//	exit();
	echo'
		<script language="javascript"> 
			 window.parent.fn_alet();
		//  window.parent.fn_list_Process10();  
		</script>
		';
		
	echo'
		<script language="javascript"> 
	//	  window.parent.fn_hide_update1();  
		</script>
		';
}




if($SubmitH=="Submit_20") { 
  
		
	function fn_check_ref_rsm5($format_ref){
		include "config_ctrl/connect.php";
		$Year = (date('y')+43);
		$sql="SELECT rp_format AS ref
				FROM  tb_repair
				WHERE rp_format LIKE '".$format_ref.$Year."%'
				ORDER BY rp_format DESC
				LIMIT 0 , 1
			";   
		  $query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
		  $num_rows =mysqli_num_rows($query);
		   $rs_sb=mysqli_fetch_array($query);
		   if($num_rows>=1){ 
		   $process_ref = $rs_sb['ref'];  
		   $arr = explode("-",$process_ref); 
		   $arr[1] = ($arr[1]*1)+1;
		   $chkLen   = strlen($arr[1]);
		   if($chkLen=="2") { $arr[1] = "000".$arr[1]; }
		   if($chkLen=="3") { $arr[1] = "00".$arr[1]; }
		   if($chkLen=="4") { $arr[1] = "0".$arr[1]; }
		   $process_ref = $arr[0]."-".$arr[1]; 
		   return  $process_ref;
		  }else{
		   $process_ref = $format_ref.$Year."0001"; 
			return  $process_ref;
		  } 
	} 
		
			
	$rp_format								=   	fn_check_ref_rsm5('RP-'); 
	$rp_name								= 		$_POST['rp_name'];
	$rp_rps_id								= 		$_POST['rp_rps_id'];
	$rpg_id									= 		$_POST['rpg_id'];
	$rp_area_id								= 		$_POST['rp_area_id'];
	$rp_ac_id								= 		$_POST['rp_ac_id'];
	$rp_ar_id								= 		$_POST['rp_ar_id'];
	$rp_phone								= 		$_POST['rp_phone'];
	$rp_date								= 		$_POST['rp_date'];
	$rp_time								= 		$_POST['rp_time'];
	$rp_subject								= 		$_POST['rp_subject'];
	$rp_subject2							= 		$_POST['rp_subject2'];
	$rp_subject3							= 		$_POST['rp_subject3'];
	$rp_details								= 		$_POST['rp_details'];
	$rp_user_id_rep							= 		$_POST['rp_user_id_rep']; 
	$file1									= 		$_POST['rp_file1'];
	$file2									= 		$_POST['rp_file2'];
	$file3									= 		$_POST['rp_file3'];
	$file4									= 		$_POST['rp_file4'];
	$file5									= 		$_POST['rp_file5'];
	$rp_note								= 		$_POST['rp_note']; 
	$rp_date_active							= 		$_POST['rp_date_active']; 
	
	$rpd_details_head_va_id2_arr			= 		$_POST['rpd_details_head_va_id2'];
	$rpd_details_head_arr					= 		$_POST['rpd_details_head'];
	$rpd_details_arr						= 		$_POST['rpd_details'];
	$rpd_brand_arr							= 		$_POST['rpd_brand']; 
	$rpd_qty_arr							= 		$_POST['rpd_qty']; 
	$rpd_price_arr							= 		$_POST['rpd_price']; 
	$rpd_sum_money_arr						= 		$_POST['rpd_sum_money']; 
	$status									= 		$_POST['status'];  
 	$file1									= 		$_POST['file1'];
 	$rp_id									= 		$_POST['rp_id'];
 	$rp_c_id								= 		$_POST['rp_c_id'];
 	$rp_rpg_id								= 		$_POST['rp_rpg_id'];
 	$rp_date_rep							= 		$_POST['rp_date_rep'];
 	$rp_time_rep							= 		$_POST['rp_time_rep'];
 	$rp_note								= 		$_POST['rp_note'];
 	$rp_c_id								= 		$_POST['rp_c_id'];
 	$rp_jr									= 		$_POST['rp_jr'];
 	$rp_rpcn_id								= 		$_POST['rp_rpcn_id'];
 	$rp_date_active							= 		$_POST['rp_date_active'];
 	$rp_ass_id								= 		$_POST['rp_ass_id'];
	  
	$rp_file_close							= 		$_POST['rp_file_close'];
	 
	  
	if($_FILES['rp_file_close']['name']!=""){  
		if(($_FILES['rp_file_close']['size'])>0) {
			$filenameArr  = explode(".", $_FILES['rp_file_close']['name']);
			$fileCountArr = count($filenameArr);
			$filename=date("ymdHis")."-01.".$filenameArr[($fileCountArr-1)];
			$tmpname=$_FILES['rp_file_close']['tmp_name'];
			$tmpFolder='AttFile/AttFile'.date("y-m").'/';
			if(!file_exists($tmpFolder)) mkdir($tmpFolder, 0777);
			move_uploaded_file($tmpname,"$tmpFolder$filename");
			$upfile1_adr="$tmpFolder$filename";
			$avd_type_file2 = ",`rp_close_file`			= 	'".$upfile1_adr."'";
		}
	} 
	  
	  
	if($_FILES['file1']['name']!=""){  
		if(($_FILES['file1']['size'])>0) {
			$filenameArr  = explode(".", $_FILES['file1']['name']);
			$fileCountArr = count($filenameArr);
			$filename=date("ymdHis")."-02.".$filenameArr[($fileCountArr-1)];
			$tmpname=$_FILES['file1']['tmp_name'];
			$tmpFolder='AttFile/AttFile'.date("y-m").'/';
			if(!file_exists($tmpFolder)) mkdir($tmpFolder, 0777);
			move_uploaded_file($tmpname,"$tmpFolder$filename");
			$upfile1_adr="$tmpFolder$filename";
			$avd_type_file1 = ",`rp_file1`			= 	'".$upfile1_adr."'";
		}
	} 
 	
	echo '---'.$avd_type_file1;
  
	if($status=='1'){
		
		$sql="UPDATE tb_repair SET  
			`rp_date_rep`				= 	'' 
			,`rp_time_rep`				= 	'' 
			,`rp_note`					= 	'' 
			,`rp_c_id`					= 	'' 
			,`rp_jr`					= 	''  
			,`rp_user_id_upd`			= 	'".$sess_user_id."'
			,`rp_date_upd`				= 	now() 
			WHERE rp_id					= 	'".$rp_id."'
			";
			echo $sql;
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
	
		$sql="UPDATE tb_repair SET  
			`rp_rpg_id`					= 	'".$rp_rpg_id."'
			,`rp_rps_id`				= 	'".$rp_rps_id."' 
			,`rp_date_active`			= 	'".$rp_date_active."' 
			,`rp_rpcn_id`				= 	'".$rp_rpcn_id."'  
			,`rp_name`					= 	'".$rp_name."' 
			,`rp_phone`					= 	'".$rp_phone."' 
			,`rp_date`					= 	'".$rp_date."' 
			,`rp_time`					= 	'".$rp_time."' 
			,`rp_area_id`				= 	'".$rp_area_id."' 
			,`rp_ac_id`					= 	'".$rp_ac_id."'
			,`rp_ar_id`					= 	'".$rp_ar_id."'
			,`rp_subject`				= 	'".$rp_subject."'
			,`rp_subject2`				= 	'".$rp_subject2."' 
			,`rp_subject3`				= 	'".$rp_subject3."' 
			,`rp_user_id_rep`			= 	'".$rp_user_id_rep."' 
			,`rp_date_rep`				= 	'".$rp_date_rep."' 
			,`rp_time_rep`				= 	'".$rp_time_rep."' 
			,`rp_note`					= 	'".$rp_note."' 
			,`rp_c_id`					= 	'".$rp_c_id."' 
			,`rp_jr`					= 	'".$rp_jr."'  
			,`rp_jr`					= 	'".$rp_jr."'  
			,`rp_user_id_upd`			= 	'".$sess_user_id."'
			,`rp_date_upd`				= 	now() 
			".$avd_type_file1." 
			".$avd_type_file2."
			WHERE rp_id					= 	'".$rp_id."'
			";
			echo $sql;
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		
		$sql="DELETE FROM tb_repair_detail
 			  WHERE  `rpd_rp_id`			= 	'".$rp_id."' 
			 "; 
		include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect)); 
		
		$i=0;
		foreach ($rpd_details_head_arr as &$rpd_details_head) {
			if(($rpd_details_head!='')){  
				$sql="INSERT INTO tb_repair_detail SET 
					 `rpd_rp_id`				= 	'".$rp_id."'
					,`rpd_details_head_va_id`	= 	'".$rpd_details_head_va_id2_arr[$i]."'
					,`rpd_details_head`			= 	'".$rpd_details_head."'
					,`rpd_details`				= 	'".$rpd_details_arr[$i]."'
					,`rpd_brand`				= 	'".$rpd_brand_arr[$i]."'
					,`rpd_qty`					= 	'".$rpd_qty_arr[$i]."'
					,`rpd_price`				= 	'".$rpd_price_arr[$i]."'
					,`rpd_sum_money`			= 	'".$rpd_sum_money_arr[$i]."'   
					";
					include "inc_logging.php"; 
				$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
				
				 
				
			}
			$i++;
		}
?>
			<script language="javascript">
	 //	if (confirm("Poista?") == true) {
 				window.parent.fn_show2();		
   	//	 } else { 
	//		 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
	//	  }
			</script>
			
		 <?php
 		 
	}else{
	
	 
	
		$sql="INSERT INTO tb_repair SET 
			 `rp_format`				= 	'".$rp_format."'
			,`rp_rpcn_id`				= 	'".$rp_rpcn_id."'  
			,`rp_rpg_id`				= 	'".$rp_rpg_id."'
			,`rp_rps_id`				= 	'".$rp_rps_id."' 
			,`rp_name`					= 	'".$rp_name."' 
			,`rp_phone`					= 	'".$rp_phone."' 
			,`rp_date`					= 	'".$rp_date."' 
			,`rp_date_active`			= 	'".$rp_date_active."' 
			,`rp_time`					= 	'".$rp_time."' 
			,`rp_area_id`				= 	'".$rp_area_id."' 
			,`rp_ac_id`					= 	'".$rp_ac_id."'
			,`rp_ar_id`					= 	'".$rp_ar_id."'
			,`rp_subject`				= 	'".$rp_subject."'
			,`rp_subject2`				= 	'".$rp_subject2."' 
			,`rp_subject3`				= 	'".$rp_subject3."' 
			,`rp_user_id_rep`			= 	'".$rp_user_id_rep."' 
			,`rp_date_rep`				= 	'".$rp_date_rep."' 
			,`rp_time_rep`				= 	'".$rp_time_rep."' 
			,`rp_note`					= 	'".$rp_note."' 
			,`rp_c_id`					= 	'".$rp_c_id."' 
			,`rp_jr`					= 	'".$rp_jr."'  
			,`rp_status`				= 	'1' 
			,`rp_user_id_ins`			= 	'".$sess_user_id."'
			,`rp_date_ins`				= 	now()
			".$avd_type_file1." 
			".$avd_type_file2."
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		
		$sql="SELECT rp_id
			  FROM tb_repair 
			  ORDER BY rp_id DESC 
			  LIMIT 0 , 1
			  ";
			include "inc_logging.php"; 
		$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
		$num_rows =mysqli_num_rows($query);
		if($num_rows>=1){
			$rs=mysqli_fetch_array($query);
			$rp_id = $rs['rp_id'];
		} 
		
		$i=0;
		foreach ($rpd_details_head_arr as &$rpd_details_head) {
			if(($rpd_details_head!='')||($rpd_details_arr[$i]!='')||($rpd_sum_money_arr[$i]!='')){  
				$sql="INSERT INTO tb_repair_detail SET 
					 `rpd_rp_id`				= 	'".$rp_id."'
					,`rpd_details_head_va_id`	= 	'".$rpd_details_head_va_id2_arr[$i]."'
					,`rpd_details_head`			= 	'".$rpd_details_head."'
					,`rpd_details`				= 	'".$rpd_details_arr[$i]."'
					,`rpd_brand`				= 	'".$rpd_brand_arr[$i]."'
					,`rpd_qty`					= 	'".$rpd_qty_arr[$i]."'
					,`rpd_price`				= 	'".$rpd_price_arr[$i]."'
					,`rpd_sum_money`			= 	'".$rpd_sum_money_arr[$i]."'   
					";
				$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
			}
			$i++;
		} 
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}



if($SubmitH=="Submit_21") { 
  

	

	$TGroupName			= 	$_POST['TGroupName'];
	$GroupId			= 	$_POST['GroupId']; 
	$rpg_status			= 	$_POST['rpg_status']; 
	$status				= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_repair_group SET 
			`rpg_name`				= 	'".$TGroupName."' 
			,`rpg_status`			= 	'".$rpg_status."' 
			,`rpg_user_upd`			= 	'".$sess_user_id."' 
			,`rpg_upd`				= 	now()
			WHERE rpg_id					= 	'".$GroupId."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_repair_group SET 
			`rpg_name`				= 	'".$TGroupName."'  
			,`rpg_status`			= 	'".$rpg_status."' 
			,`rpg_user_ins`			= 	'".$sess_user_id."' 
			,`rpg_ins`				= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}



if($SubmitH=="Submit_22") { 
  

	

	$TGroupName			= 	$_POST['TGroupName'];
	$GroupId				= 	$_POST['GroupId']; 
	$status				= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_area SET 
			`area_name`				= 	'".$TGroupName."' 
			WHERE area_id					= 	'".$GroupId."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_area SET 
			`area_name`				= 	'".$TGroupName."'  
			,`area_user_ins`			= 	'".$sess_user_id."' 
			,`area_ins`				= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}





if($SubmitH=="Submit_24") { 
  
	
	
 
	$rp_close_date							= 		$_POST['rp_close_date'];  
	$rp_close_time							= 		$_POST['rp_close_time'];  
	$rp_note_close							= 		$_POST['rp_note_close'];   
 	$rp_file_close							= 		$_POST['rp_file_close'];
 	$rp_id									= 		$_POST['rp_id'];
	 
	  
	if($_FILES['rp_file_close']['name']!=""){  
		if(($_FILES['rp_file_close']['size'])>0) {
			$filenameArr  = explode(".", $_FILES['rp_file_close']['name']);
			$fileCountArr = count($filenameArr);
			$filename=date("ymdHis")."-01.".$filenameArr[($fileCountArr-1)];
			$tmpname=$_FILES['rp_file_close']['tmp_name'];
			$tmpFolder='AttFile/AttFile'.date("y-m").'/';
			if(!file_exists($tmpFolder)) mkdir($tmpFolder, 0777);
			move_uploaded_file($tmpname,"$tmpFolder$filename");
			$upfile1_adr="$tmpFolder$filename";
			$avd_type_file1 = ",`rp_close_file`			= 	'".$upfile1_adr."'";
		}
	} 
 	  
			
   	$rp_date_rep			= 	$_POST['rp_date_rep'];
   	$rp_time_rep			= 	$_POST['rp_time_rep'];
   	$rp_note			= 	$_POST['rp_note'];
   	$rp_c_id			= 	$_POST['rp_c_id'];
   	$rp_jr			= 	$_POST['rp_jr']; 
   	$cb			= 	$_POST['cb'];
	$cb2		= 	$_POST['cb2']; 
	$cb3		= 	$_POST['cb3']; 
	$cb4		= 	$_POST['cb4']; 
   
	$sql="UPDATE tb_repair SET   
		`rp_close_note`				= 	'".$rp_note_close."'  
		,`rp_date_rep`				= 	'".$rp_date_rep."' 
		,`rp_time_rep`				= 	'".$rp_time_rep."' 
		,`rp_note`					= 	'".$rp_note."' 
		,`rp_c_id`					= 	'".$rp_c_id."' 
		,`rp_jr`					= 	'".$rp_jr."'  
		,`cb`						= 	'".$cb."' 
		,`cb2`						= 	'".$cb2."' 
		,`cb3`						= 	'".$cb3."' 
		,`cb4`						= 	'".$cb4."'  
		,`rp_close_ins`				= 	NOW() 
		,`rp_status`				= 	'3'  
		,`rp_close_user_id`			= 	'".$sess_user_id."' 
		".$avd_type_file1." 
		WHERE rp_id				= 	'".$rp_id."'
		";
		echo $sql;
		include "inc_logging.php"; 
	$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect)); 
	echo'
		<script language="javascript">
		 window.parent.fn_alet();
		// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
		// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
		</script>
		';
 
}


if($SubmitH=="Submit_25") { 
  

	

	$ac_id				= 	$_POST['ac_id'];
	$ac_name			= 	$_POST['ac_name'];
	$GroupId			= 	$_POST['GroupId']; 
	$ac_status			= 	$_POST['ac_status']; 
	$status				= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_area_class SET 
			 `ac_name`					= 	'".$ac_name."' 
			,`ac_upd_ins`			= 	'".$sess_user_id."' 
			,`ac_status`			= 	'".$ac_status."' 
			,`ac_upd`					= 	now()
			WHERE ac_id					= 	'".$ac_id."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_area_class SET 
			`ac_area_id`				= 	'".$GroupId."'  
			,`ac_name`					= 	'".$ac_name."' 
			,`ac_status`				= 	'".$ac_status."' 
			,`ac_user_ins`				= 	'".$sess_user_id."'  
			,`ac_ins`					= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}


if($SubmitH=="Submit_26") { 
  

	

	$GroupId			= 	$_POST['GroupId'];
	$ar_ac_id			= 	$_POST['ar_ac_id'];
	$ar_name			= 	$_POST['ar_name']; 
	$ar_ac_id			= 	$_POST['ar_ac_id'];
	$ar_id				= 	$_POST['ar_id'];
	$status				= 	$_POST['status']; 
	$ar_status			= 	$_POST['ar_status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_area_room SET 
			`ar_name`					= 	'".$ar_name."' 
			,`ar_status`				= 	'".$ar_status."' 
			,`ar_upd_ins`				= 	'".$sess_user_id."' 
			,`ar_upd`					= 	now()
			WHERE ar_id					= 	'".$ar_id."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		
		$sql="INSERT INTO tb_area_room SET 
			`ar_area_id`				= 	'".$GroupId."'  
			,`ar_ac_id`					= 	'".$ar_ac_id."' 
			,`ar_name`					= 	'".$ar_name."' 
			,`ar_user_ins`				= 	'".$sess_user_id."'  
			,`ar_ins`					= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}



if($SubmitH=="Submit_27") { 
  

	

	$TGroupName			= 	$_POST['TGroupName'];
	$GroupId			= 	$_POST['GroupId']; 
	$rpg_status			= 	$_POST['rpg_status']; 
	$status				= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_repair_system SET 
			`rps_name`				= 	'".$TGroupName."' 
			,`rps_status`			= 	'".$rpg_status."' 
			,`rps_user_upd`			= 	'".$sess_user_id."' 
			,`rps_upd`				= 	now()
			WHERE rps_id					= 	'".$GroupId."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_repair_system SET 
			`rps_name`				= 	'".$TGroupName."'  
			,`rps_status`			= 	'".$rpg_status."' 
			,`rps_user_ins`			= 	'".$sess_user_id."' 
			,`rps_ins`				= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}


if($SubmitH=="Submit_28") { 
  

	

	$TGroupName			= 	$_POST['TGroupName'];
	$GroupId			= 	$_POST['GroupId']; 
	$rpg_status			= 	$_POST['rpg_status']; 
	$status				= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_repair_cause SET 
			`rpc_name`				= 	'".$TGroupName."' 
			,`rpc_status`			= 	'".$rpg_status."' 
			,`rpc_user_upd`			= 	'".$sess_user_id."' 
			,`rpc_upd`				= 	now()
			WHERE rpc_id					= 	'".$GroupId."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_repair_cause SET 
			`rpc_name`				= 	'".$TGroupName."'  
			,`rpc_status`			= 	'".$rpg_status."' 
			,`rpc_user_ins`			= 	'".$sess_user_id."' 
			,`rpc_ins`				= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}

if($SubmitH=="Submit_29") { 
  

	

	$TGroupName			= 	$_POST['TGroupName'];
	$GroupId			= 	$_POST['GroupId']; 
	$rpg_status			= 	$_POST['rpg_status']; 
	$status				= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_channel SET 
			`rpcn_name`				= 	'".$TGroupName."' 
			,`rpcn_status`			= 	'".$rpg_status."' 
			,`rpcn_user_upd`		= 	'".$sess_user_id."' 
			,`rpcn_upd`				= 	now()
			WHERE rpcn_id			= 	'".$GroupId."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_channel SET 
			`rpcn_name`				= 	'".$TGroupName."'  
			,`rpcn_status`			= 	'".$rpg_status."' 
			,`rpcn_user_ins`		= 	'".$sess_user_id."' 
			,`rpcn_ins`				= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}

if($SubmitH=="Submit_30") { 
  

	

	$GroupId			= 	$_POST['GroupId'];
	$rp_rps_id			= 	$_POST['rp_rps_id'];
	$rpd_details_head	= 	$_POST['rpd_details_head']; 
	$rpd_details		= 	$_POST['rpd_details']; 
	$rpd_brand			= 	$_POST['rpd_brand']; 
	$rpd_price			= 	$_POST['rpd_price']; 
	$rpd_satatus		= 	$_POST['rpd_satatus']; 
	$status				= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_repair_product SET 
			`rp_rps_id`				= 	'".$rp_rps_id."' 
			,`rpd_details_head`		= 	'".$rpd_details_head."' 
			,`rpd_details`			= 	'".$rpd_details."' 
			,`rpd_brand`			= 	'".$rpd_brand."' 
			,`rpd_price`			= 	'".$rpd_price."' 
			,`rpd_satatus`			= 	'".$rpd_satatus."' 
			,`rpd_user_upd`			= 	'".$sess_user_id."' 
			,`rpd_upd`				= 	now()
			WHERE rpd_id			= 	'".$GroupId."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_repair_product SET 
			`rp_rps_id`				= 	'".$rp_rps_id."' 
			,`rpd_details_head`		= 	'".$rpd_details_head."' 
			,`rpd_details`			= 	'".$rpd_details."' 
			,`rpd_brand`			= 	'".$rpd_brand."' 
			,`rpd_price`			= 	'".$rpd_price."' 
			,`rpd_satatus`			= 	'".$rpd_satatus."' 
			,`rpd_user_ins`			= 	'".$sess_user_id."' 
			,`rpd_ins`				= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}


if($SubmitH=="Submit_31") {  

	$GroupId			= 	$_POST['GroupId'];
	$TGroupName			= 	$_POST['TGroupName']; 
	$rpd_satatus		= 	$_POST['rpd_satatus']; 
	$status				= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_tou SET 
			`tou_name`				= 	'".$TGroupName."' 
			,`tou_status`			= 	'".$rpd_satatus."'  
			,`tou_user_id_upd`		= 	'".$sess_user_id."' 
			,`tou_upd`				= 	now()
			WHERE tou_id			= 	'".$GroupId."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_tou SET 
			`tou_name`				= 	'".$TGroupName."' 
			,`tou_status`			= 	'".$rpd_satatus."'   
			,`tou_user_ins`			= 	'".$sess_user_id."' 
			,`tou_ins`				= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}


if($SubmitH=="Submit_32") { 
  

	
	$toudt_id		= 	$_POST['toudt_id']; 
	$toudt_tou_id	= 	$_POST['toudt_tou_id'];
	$toudt_time		= 	$_POST['toudt_time']; 
	$toudt_010		= 	$_POST['toudt_010']; 
	$toudt_011		= 	$_POST['toudt_011']; 
	$toudt_012		= 	$_POST['toudt_012']; 
	$toudt_031		= 	$_POST['toudt_031']; 
	$toudt_032		= 	$_POST['toudt_032']; 
	$toudt_071		= 	$_POST['toudt_071']; 
	$toudt_072		= 	$_POST['toudt_072'];  
	$status			= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_tou_detail SET 
			`toudt_time`			= 	'".$toudt_time."'    
			,`toudt_tou_id`			= 	'".$toudt_tou_id."' 
			,`toudt_010`			= 	'".$toudt_010."' 
			,`toudt_011`			= 	'".$toudt_011."' 
			,`toudt_012`			= 	'".$toudt_012."' 
			,`toudt_031`			= 	'".$toudt_031."' 
			,`toudt_032`			= 	'".$toudt_032."' 
			,`toudt_071`			= 	'".$toudt_071."' 
			,`toudt_072`			= 	'".$toudt_072."' 
			,`toudt_user_upd`		= 	'".$sess_user_id."' 
			,`toudt_upd`			= 	now()
			WHERE toudt_id			= 	'".$toudt_id."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		
		$sql="SELECT *
			  FROM tb_tou_detail
			  WHERE DATE_FORMAT(`toudt_time`, '%Y%m%d')  = DATE_FORMAT('".date('Y-m-d',strtotime($toudt_time))."', '%Y%m%d')
			  ".$tmpSQL."
			  ";
			  
		$query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
		$num_rows =mysqli_num_rows($query);
		if ($num_rows>=1){	
			?>
			<script language="javascript">
			 	alert("ไม่สามารถ บันทึกข้อมูลได้ เนื่องจาก มีข้อมูลวันที่ดังกล่าว");
				window.parent.myFunction3('');
			//	window.history.back();
			</script>
			<?
			exit();
		} 
		$sql="INSERT INTO tb_tou_detail SET 
			`toudt_time`			= 	'".$toudt_time."'   
			,`toudt_tou_id`			= 	'".$toudt_tou_id."' 
			,`toudt_010`			= 	'".$toudt_010."' 
			,`toudt_011`			= 	'".$toudt_011."' 
			,`toudt_012`			= 	'".$toudt_012."' 
			,`toudt_031`			= 	'".$toudt_031."' 
			,`toudt_032`			= 	'".$toudt_032."' 
			,`toudt_071`			= 	'".$toudt_071."' 
			,`toudt_072`			= 	'".$toudt_072."' 
			,`toudt_user_ins`			= 	'".$sess_user_id."' 
			,`toudt_ins`				= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}



if($SubmitH=="Submit_33") {  

	$GroupId			= 	$_POST['GroupId'];
	$TGroupName			= 	$_POST['TGroupName']; 
	$rpd_satatus		= 	$_POST['rpd_satatus']; 
	$status				= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_tou_mdb SET 
			`toum_name`				= 	'".$TGroupName."' 
			,`toum_status`			= 	'".$rpd_satatus."'  
			,`toum_user_id_upd`		= 	'".$sess_user_id."' 
			,`toum_upd`				= 	now()
			WHERE toum_id			= 	'".$GroupId."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_tou_mdb SET 
			`toum_name`				= 	'".$TGroupName."' 
			,`toum_status`			= 	'".$rpd_satatus."'   
			,`toum_user_ins`		= 	'".$sess_user_id."' 
			,`toum_ins`				= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}


if($SubmitH=="Submit_34") { 
  

	
	$toumdt_id			= 	$_POST['toumdt_id']; 
	$toumdt_toum_id		= 	$_POST['toumdt_toum_id'];
	$toumdt_time		= 	$_POST['toumdt_time']; 
	$toumdt_rs_400		= 	$_POST['toumdt_rs_400']; 
	$toumdt_st_400		= 	$_POST['toumdt_st_400']; 
	$toumdt_tr_400		= 	$_POST['toumdt_tr_400']; 
	$toumdt_rn_230		= 	$_POST['toumdt_rn_230']; 
	$toumdt_sn_230		= 	$_POST['toumdt_sn_230']; 
	$toumdt_tn_230		= 	$_POST['toumdt_tn_230']; 
	$toumdt_r			= 	$_POST['toumdt_r'];  
	$toumdt_s			= 	$_POST['toumdt_s'];  
	$toumdt_t			= 	$_POST['toumdt_t'];  
	$toumdt_kw			= 	$_POST['toumdt_kw'];  
	$toumdt_pf			= 	$_POST['toumdt_pf'];  
	$toumdt_at			= 	$_POST['toumdt_at'];  
	$toumdt_off			= 	$_POST['toumdt_off'];  
	$toumdt_rm			= 	$_POST['toumdt_rm'];   
	$status				= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_toum_detail SET 
			`toumdt_time`			= 	'".$toumdt_time."'    
			,`toumdt_toum_id`		= 	'".$toumdt_toum_id."' 
			,`toumdt_rs_400`		= 	'".$toumdt_rs_400."' 
			,`toumdt_st_400`		= 	'".$toumdt_st_400."' 
			,`toumdt_tr_400`		= 	'".$toumdt_tr_400."' 
			,`toumdt_rn_230`		= 	'".$toumdt_rn_230."' 
			,`toumdt_sn_230`		= 	'".$toumdt_sn_230."' 
			,`toumdt_tn_230`		= 	'".$toumdt_tn_230."' 
			,`toumdt_r`				= 	'".$toumdt_r."' 
			,`toumdt_s`				= 	'".$toumdt_s."' 
			,`toumdt_t`				= 	'".$toumdt_t."' 
			,`toumdt_kw`			= 	'".$toumdt_kw."' 
			,`toumdt_pf`			= 	'".$toumdt_pf."' 
			,`toumdt_at`			= 	'".$toumdt_at."' 
			,`toumdt_off`			= 	'".$toumdt_off."' 
			,`toumdt_rm`			= 	'".$toumdt_rm."'  
			,`toumdt_user_upd`		= 	'".$sess_user_id."' 
			,`toumdt_upd`			= 	now()
			WHERE toumdt_id			= 	'".$toumdt_id."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		
		$sql="SELECT *
			  FROM tb_toum_detail
			  WHERE DATE_FORMAT(`toumdt_time`, '%Y%m%d')  = DATE_FORMAT('".date('Y-m-d',strtotime($toumdt_time))."', '%Y%m%d')
			  AND toumdt_toum_id =  '".$toumdt_toum_id."'
			  ".$tmpSQL."
			  ";
			  
		$query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
		$num_rows =mysqli_num_rows($query);
		if ($num_rows>=1){	
			?>
			<script language="javascript">
			 	alert("ไม่สามารถ บันทึกข้อมูลได้ เนื่องจาก มีข้อมูลวันที่ดังกล่าว");
				window.parent.myFunction3('');
			//	window.history.back();
			</script>
			<?
			exit();
		} 
		$sql="INSERT INTO tb_toum_detail SET 
			`toumdt_time`			= 	'".$toumdt_time."'    
			,`toumdt_toum_id`		= 	'".$toumdt_toum_id."' 
			,`toumdt_rs_400`		= 	'".$toumdt_rs_400."' 
			,`toumdt_st_400`		= 	'".$toumdt_st_400."' 
			,`toumdt_tr_400`		= 	'".$toumdt_tr_400."' 
			,`toumdt_rn_230`		= 	'".$toumdt_rn_230."' 
			,`toumdt_sn_230`		= 	'".$toumdt_sn_230."' 
			,`toumdt_tn_230`		= 	'".$toumdt_tn_230."' 
			,`toumdt_r`				= 	'".$toumdt_r."' 
			,`toumdt_s`				= 	'".$toumdt_s."' 
			,`toumdt_t`				= 	'".$toumdt_t."' 
			,`toumdt_kw`			= 	'".$toumdt_kw."' 
			,`toumdt_pf`			= 	'".$toumdt_pf."' 
			,`toumdt_at`			= 	'".$toumdt_at."' 
			,`toumdt_off`			= 	'".$toumdt_off."' 
			,`toumdt_rm`			= 	'".$toumdt_rm."'   
			,`toumdt_user_ins`		= 	'".$sess_user_id."' 
			,`toumdt_ins`			= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}
}



if($SubmitH=="Submit_35") {  

	$GroupId			= 	$_POST['GroupId'];
	$TGroupName			= 	$_POST['TGroupName']; 
	$rpd_satatus		= 	$_POST['rpd_satatus']; 
	$mt_type			= 	$_POST['mt_type']; 
	$status				= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_meter SET 
			`mt_name`			= 	'".$TGroupName."' 
			,`mt_type`			= 	'".$mt_type."'  
			,`mt_status`		= 	'".$rpd_satatus."'  
			,`mt_upd`			= 	now()
			,`mt_user_id_upd`	= 	'".$sess_user_id."' 
			WHERE mt_id			= 	'".$GroupId."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		$sql="INSERT INTO tb_meter SET 
			`mt_name`			= 	'".$TGroupName."' 
			,`mt_type`			= 	'".$mt_type."'  
			,`mt_status`		= 	'".$rpd_satatus."'  
			,`mt_ins`			= 	now()
			,`mt_user_ins`		= 	'".$sess_user_id."'  
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';

	}
}




if($SubmitH=="Submit_36") { 
  

	
	$toumdt_id			= 	$_POST['toumdt_id']; 
	$toumdt_toum_id		= 	$_POST['toumdt_toum_id'];
	$toumdt_time		= 	$_POST['toumdt_time']; 
	$toumdt_rs_400		= 	$_POST['toumdt_rs_400']; 
	$toumdt_st_400		= 	$_POST['toumdt_st_400']; 
	$toumdt_tr_400		= 	$_POST['toumdt_tr_400']; 
	$toumdt_rn_230		= 	$_POST['toumdt_rn_230']; 
	$toumdt_sn_230		= 	$_POST['toumdt_sn_230']; 
	$toumdt_tn_230		= 	$_POST['toumdt_tn_230']; 
	$toumdt_r			= 	$_POST['toumdt_r'];  
	$toumdt_s			= 	$_POST['toumdt_s'];  
	$toumdt_t			= 	$_POST['toumdt_t'];  
	$toumdt_kw			= 	$_POST['toumdt_kw'];  
	$toumdt_pf			= 	$_POST['toumdt_pf'];  
	$toumdt_at			= 	$_POST['toumdt_at'];  
	$toumdt_off			= 	$_POST['toumdt_off'];  
	$toumdt_rm			= 	$_POST['toumdt_rm'];   
	$status				= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_toum_detail SET 
			`toumdt_time`			= 	'".$toumdt_time."'    
			,`toumdt_toum_id`		= 	'".$toumdt_toum_id."' 
			,`toumdt_rs_400`		= 	'".$toumdt_rs_400."' 
			,`toumdt_st_400`		= 	'".$toumdt_st_400."' 
			,`toumdt_tr_400`		= 	'".$toumdt_tr_400."' 
			,`toumdt_rn_230`		= 	'".$toumdt_rn_230."' 
			,`toumdt_sn_230`		= 	'".$toumdt_sn_230."' 
			,`toumdt_tn_230`		= 	'".$toumdt_tn_230."' 
			,`toumdt_r`				= 	'".$toumdt_r."' 
			,`toumdt_s`				= 	'".$toumdt_s."' 
			,`toumdt_t`				= 	'".$toumdt_t."' 
			,`toumdt_kw`			= 	'".$toumdt_kw."' 
			,`toumdt_pf`			= 	'".$toumdt_pf."' 
			,`toumdt_at`			= 	'".$toumdt_at."' 
			,`toumdt_off`			= 	'".$toumdt_off."' 
			,`toumdt_rm`			= 	'".$toumdt_rm."'  
			,`toumdt_user_upd`		= 	'".$sess_user_id."' 
			,`toumdt_upd`			= 	now()
			WHERE toumdt_id			= 	'".$toumdt_id."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		
		$sql="SELECT *
			  FROM tb_toum_detail
			  WHERE DATE_FORMAT(`toumdt_time`, '%Y%m%d')  = DATE_FORMAT('".date('Y-m-d',strtotime($toumdt_time))."', '%Y%m%d')
			  AND toumdt_toum_id =  '".$toumdt_toum_id."'
			  ".$tmpSQL."
			  ";
			  
		$query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
		$num_rows =mysqli_num_rows($query);
		if ($num_rows>=1){	
			?>
			<script language="javascript">
			 	alert("ไม่สามารถ บันทึกข้อมูลได้ เนื่องจาก มีข้อมูลวันที่ดังกล่าว");
				window.parent.myFunction3('');
			//	window.history.back();
			</script>
			<?
			exit();
		} 
		$sql="INSERT INTO tb_toum_detail SET 
			`toumdt_time`			= 	'".$toumdt_time."'    
			,`toumdt_toum_id`		= 	'".$toumdt_toum_id."' 
			,`toumdt_rs_400`		= 	'".$toumdt_rs_400."' 
			,`toumdt_st_400`		= 	'".$toumdt_st_400."' 
			,`toumdt_tr_400`		= 	'".$toumdt_tr_400."' 
			,`toumdt_rn_230`		= 	'".$toumdt_rn_230."' 
			,`toumdt_sn_230`		= 	'".$toumdt_sn_230."' 
			,`toumdt_tn_230`		= 	'".$toumdt_tn_230."' 
			,`toumdt_r`				= 	'".$toumdt_r."' 
			,`toumdt_s`				= 	'".$toumdt_s."' 
			,`toumdt_t`				= 	'".$toumdt_t."' 
			,`toumdt_kw`			= 	'".$toumdt_kw."' 
			,`toumdt_pf`			= 	'".$toumdt_pf."' 
			,`toumdt_at`			= 	'".$toumdt_at."' 
			,`toumdt_off`			= 	'".$toumdt_off."' 
			,`toumdt_rm`			= 	'".$toumdt_rm."'   
			,`toumdt_user_ins`		= 	'".$sess_user_id."' 
			,`toumdt_ins`			= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}
}



if($SubmitH=="Submit_37") { 
  

	
	$toumdt_etm_id			= 	$_POST['toumdt_etm_id']; 
	$toumdt_etm_time		= 	$_POST['toumdt_etm_time'];
	$toumdt_etm_toum_id		= 	$_POST['toumdt_etm_toum_id']; 
	$toumdt_etm_mt			= 	$_POST['toumdt_etm_mt']; 
	$toumdt_etm_rm			= 	$_POST['toumdt_etm_rm'];  
	$status					= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_toum_detail_etm SET 
			`toumdt_etm_time`			= 	'".$toumdt_etm_time."'    
			,`toumdt_etm_toum_id`		= 	'".$toumdt_etm_toum_id."' 
			,`toumdt_etm_mt`			= 	'".$toumdt_etm_mt."' 
			,`toumdt_etm_rm`			= 	'".$toumdt_etm_rm."'  
			,`toumdt_etm_user_upd`		= 	'".$sess_user_id."' 
			,`toumdt_etm_upd`			= 	now()
			WHERE toumdt_etm_id			= 	'".$toumdt_id."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		
		$sql="SELECT *
			  FROM tb_toum_detail_etm
			  WHERE DATE_FORMAT(`toumdt_etm_time`, '%Y%m%d')  = DATE_FORMAT('".date('Y-m-d',strtotime($toumdt_etm_time))."', '%Y%m%d')
			  AND toumdt_etm_toum_id =  '".$toumdt_etm_toum_id."'
			  ".$tmpSQL."
			  ";
			  
		$query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
		$num_rows =mysqli_num_rows($query);
		if ($num_rows>=1){	
			?>
			<script language="javascript">
			 	alert("ไม่สามารถ บันทึกข้อมูลได้ เนื่องจาก มีข้อมูลวันที่ดังกล่าว");
				window.parent.myFunction3('');
			//	window.history.back();
			</script>
			<?
			exit();
		} 
		$sql="INSERT INTO tb_toum_detail_etm SET 
			`toumdt_etm_time`			= 	'".$toumdt_etm_time."'    
			,`toumdt_etm_toum_id`		= 	'".$toumdt_etm_toum_id."' 
			,`toumdt_etm_mt`			= 	'".$toumdt_etm_mt."' 
			,`toumdt_etm_rm`			= 	'".$toumdt_etm_rm."'  
			,`toumdt_etm_user_ins`		= 	'".$sess_user_id."' 
			,`toumdt_etm_ins`			= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}
}



if($SubmitH=="Submit_38") { 
  

	
	$toumdt_et_id			= 	$_POST['toumdt_et_id']; 
	$toumdt_et_time		= 	$_POST['toumdt_et_time'];
	$toumdt_et_toum_id		= 	$_POST['toumdt_et_toum_id']; 
	$toumdt_et_mt			= 	$_POST['toumdt_et_mt']; 
	$toumdt_et_rm			= 	$_POST['toumdt_et_rm'];  
	$status					= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_toum_detail_et SET 
			`toumdt_et_time`			= 	'".$toumdt_et_time."'    
			,`toumdt_et_toum_id`		= 	'".$toumdt_et_toum_id."' 
			,`toumdt_et_mt`			= 	'".$toumdt_et_mt."' 
			,`toumdt_et_rm`			= 	'".$toumdt_et_rm."'  
			,`toumdt_et_user_upd`		= 	'".$sess_user_id."' 
			,`toumdt_et_upd`			= 	now()
			WHERE toumdt_et_id			= 	'".$toumdt_id."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		
		$sql="SELECT *
			  FROM tb_toum_detail_et
			  WHERE DATE_FORMAT(`toumdt_et_time`, '%Y%m%d')  = DATE_FORMAT('".date('Y-m-d',strtotime($toumdt_et_time))."', '%Y%m%d')
			  AND toumdt_et_toum_id =  '".$toumdt_et_toum_id."'
			  ".$tmpSQL."
			  ";
			  
		$query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
		$num_rows =mysqli_num_rows($query);
		if ($num_rows>=1){	
			?>
			<script language="javascript">
			 	alert("ไม่สามารถ บันทึกข้อมูลได้ เนื่องจาก มีข้อมูลวันที่ดังกล่าว");
				window.parent.myFunction3('');
			//	window.history.back();
			</script>
			<?
			exit();
		} 
		$sql="INSERT INTO tb_toum_detail_et SET 
			`toumdt_et_time`			= 	'".$toumdt_et_time."'    
			,`toumdt_et_toum_id`		= 	'".$toumdt_et_toum_id."' 
			,`toumdt_et_mt`			= 	'".$toumdt_et_mt."' 
			,`toumdt_et_rm`			= 	'".$toumdt_et_rm."'  
			,`toumdt_et_user_ins`		= 	'".$sess_user_id."' 
			,`toumdt_et_ins`			= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}
}



if($SubmitH=="Submit_39") { 
  

	
	$toumdt_wt_id			= 	$_POST['toumdt_wt_id']; 
	$toumdt_wt_time			= 	$_POST['toumdt_wt_time'];
	$toumdt_wt_toum_id		= 	$_POST['toumdt_wt_toum_id']; 
	$toumdt_wt_mt			= 	$_POST['toumdt_wt_mt']; 
	$toumdt_wt_rm			= 	$_POST['toumdt_wt_rm'];  
	$status					= 	$_POST['status']; 
 
	if($status=='1'){
		$sql="UPDATE tb_toum_detail_wt SET 
			`toumdt_wt_time`			= 	'".$toumdt_wt_time."'    
			,`toumdt_wt_toum_id`		= 	'".$toumdt_wt_toum_id."' 
			,`toumdt_wt_mt`			= 	'".$toumdt_wt_mt."' 
			,`toumdt_wt_rm`			= 	'".$toumdt_wt_rm."'  
			,`toumdt_wt_user_upd`		= 	'".$sess_user_id."' 
			,`toumdt_wt_upd`			= 	now()
			WHERE toumdt_wt_id			= 	'".$toumdt_id."'
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}else{
		
		$sql="SELECT *
			  FROM tb_toum_detail_wt
			  WHERE DATE_FORMAT(`toumdt_wt_time`, '%Y%m%d')  = DATE_FORMAT('".date('Y-m-d',strtotime($toumdt_wt_time))."', '%Y%m%d')
			  AND toumdt_wt_toum_id =  '".$toumdt_wt_toum_id."' 
			  ".$tmpSQL."
			  ";
			  
		$query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
		$num_rows =mysqli_num_rows($query);
		if ($num_rows>=1){	
			?>
			<script language="javascript">
			 	alert("ไม่สามารถ บันทึกข้อมูลได้ เนื่องจาก มีข้อมูลวันที่ดังกล่าว");
				window.parent.myFunction3('');
			//	window.history.back();
			</script>
			<?
			exit();
		} 
		$sql="INSERT INTO tb_toum_detail_wt SET 
			`toumdt_wt_time`			= 	'".$toumdt_wt_time."'    
			,`toumdt_wt_toum_id`		= 	'".$toumdt_wt_toum_id."' 
			,`toumdt_wt_mt`			= 	'".$toumdt_wt_mt."' 
			,`toumdt_wt_rm`			= 	'".$toumdt_wt_rm."'  
			,`toumdt_wt_user_ins`		= 	'".$sess_user_id."' 
			,`toumdt_wt_ins`			= 	now()
			";
			include "inc_logging.php"; 
		$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
		echo'
			<script language="javascript">
			 window.parent.fn_alet();
			// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
			// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
			</script>
			';
	}
}


?>

