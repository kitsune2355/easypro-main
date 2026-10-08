 <?php 
define("SB_M1","process_m_10");
define("SB_M2","process_10");
include("config_ctrl/connect.php"); 
$date				= $_POST['date']; 
//$chk_ctrl_add 			= fn_detect_permis_pocess($sess_user_id,$process,'ctrl_add');
//$chk_ctrl_edit 			= fn_detect_permis_pocess($sess_user_id,$process,'ctrl_edit');
//$chk_ctrl_view			= fn_detect_permis_pocess($sess_user_id,$process,'ctrl_view');
//$chk_ctrl_print			= fn_detect_permis_pocess($sess_user_id,$process,'ctrl_print');
//$chk_ctrl_del			= fn_detect_permis_pocess($sess_user_id,$process,'ctrl_del');
$tmp_process = '9';  
function fn_number_format($value,$decimal,$nullOption) {
	if(($value*1)!=0) {
		return number_format(($value),$decimal);
	}else{
		return $nullOption;
	}
}
	$eng_full_month_arr=array(
		"0"=>"",
		"1"=>"January",
		"2"=>"February",
		"3"=>"March",
		"4"=>"April",
		"5"=>"May",
		"6"=>"June",	
		"7"=>"July",
		"8"=>"August",
		"9"=>"September",
		"10"=>"October",
		"11"=>"November",
		"12"=>"December"
	);
	function fn_thai_date($time,$formatdate){
		if(($time=="0000-00-00")||($time=="")) {
			return '';
		}
		$thai_day_arr=array("อาทิตย์","จันทร์","อังคาร","พุธ","พฤหัสบดี","ศุกร์","เสาร์");
		$thai_full_month_arr=array(
			"0"=>"",
			"1"=>"มกราคม",
			"2"=>"กุมภาพันธุ์",
			"3"=>"มีนาคม",
			"4"=>"เมษายน",
			"5"=>"พฤษภาคม",
			"6"=>"มิถุนายน",	
			"7"=>"กรกฏาคม",
			"8"=>"สิงหาคม",
			"9"=>"กันยายน",
			"10"=>"ตุลาคม",
			"11"=>"พฤศจิกายน",
			"12"=>"ธันวาคม"
		);
		$thai_month_arr=array(
			"0"=>"",
			"1"=>"ม.ค.",
			"2"=>"ก.พ.",
			"3"=>"มี.ค.",
			"4"=>"เม.ย.",
			"5"=>"พ.ค.",
			"6"=>"มิ.ย.",	
			"7"=>"ก.ค.",
			"8"=>"ส.ค.",
			"9"=>"ก.ย.",
			"10"=>"ต.ค.",
			"11"=>"พ.ย.",
			"12"=>"ธ.ค."					
		);
		$time = strtotime($time);
		if($formatdate=="2") {
			$thai_date_return =	'<span class="underlineStlye">&nbsp;&nbsp;'.(date("d",$time)*1).'&nbsp;&nbsp;</span>';
			$thai_date_return.=' เดือน '.'<span class="underlineStlye">&nbsp;&nbsp;'.($thai_full_month_arr[date("n",$time)]).'&nbsp;&nbsp;</span>';
			$thai_date_return.=	' พ.ศ. '.'<span class="underlineStlye">&nbsp;&nbsp;'.((date("Y",$time)+543)).'&nbsp;&nbsp;</span>';
		}else if($formatdate=="3") {
			$thai_date_return =	"".(date("d",$time)*1);
			$thai_date_return.=" ".($thai_month_arr[date("n",$time)]);
			$thai_date_return.=	" ".((date("Y",$time)+543))." ".date('H:i:s',$time);
		}else if($formatdate=="4") {
			$thai_date_return =	"".(date("m",$time)*1);
			$thai_date_return.="/".(date("d",$time));
			$thai_date_return.="/".(date("Y",$time));
		}else{
			$thai_date_return =	"".(date("d",$time)*1);
			$thai_date_return.=" ".($thai_month_arr[date("n",$time)]);
			$thai_date_return.=	" ".((date("Y",$time)+543-2500));
		}
		if($thai_date_return!="1 ม.ค. 2513") { return $thai_date_return;  }else{ return ""; }
	}
	$ag_id			= $_POST['ag_id'];
	$ag_name 		= $_POST['ag_name'];
	$class_id	 	= $_POST['class_id'];
	$class_name     = $_POST['class_name'];  
	
	include "config_ctrl/DetechGuest.php";
	include "config_ctrl/connect.php";
	include "config_ctrl/checksession.php"; 
?> 
<style>
	.example2 {
		border: 1px solid black;
	}
	.example2 th {
		border: 1px solid;
		border-color: #CCCCCC;
		font-size:16px;
		height: 30px;
	}
	.example2 td {
		border: 1px solid;
		border-color: #CCCCCC;
	}
</style>  
<input type="hidden" id="ag_id" name="ag_id"  value="<?php echo $ag_id ?>">  
<input type="hidden" id="class_id" name="class_id"  value="<?php echo $class_id ?>">  
<div style="ale">
	<table  border="1" class="example2" id="example2229"> 
	<tr>
		<td colspan="2" align="center" style="font-size:16px"><strong>หน่วยงาน<?php echo $ag_name ?> ชั้น <?php echo $class_name; ?></strong> </td>
	  </tr>
	    <?php
		$sql_sb_t="SELECT *   
				 FROM tb_class_room AS TbLcad
				 WHERE room_agc_id =  '".$ag_id."'
				 AND room_class_id =  '".$class_id."'
				 AND room_status	=  '1'
				 "; 
		$query_sb_t =mysqli_query($connect,$sql_sb_t)  or die(mysqli_error($connect));
		$num_rows_sb_t =mysqli_num_rows($query_sb_t);
		if($num_rows_sb_t>=1){
			$o=0;
			while($rs_sb_t=mysqli_fetch_array($query_sb_t)) {  
		?> 
	  <tr>
		<td width="80%" align="center"><input type="hidden" style="width:100%" id="room_id[]" name="room_id[]"  value="<?php echo $rs_sb_t['room_id']; ?>">&nbsp;&nbsp;<strong><?php echo $rs_sb_t['room_name']; ?></strong></td>
		<td width="20%" align="center">
		<select name="room_air[]" id="room_air[]" class="form-control select2" style=" width:150px;  font-size:14px; font-weight:normal; height:28px; padding-top:0px;">
		  <option value="">- เลือกสถานะแอร์ -</option> 
		  <option value="1" <?php if($rs_sb_t['room_air']=='1'){?> selected="selected" <?php } ?>>- มี -</option> 
		  <option value="0">- ไม่มี -</option> 
	   
		</select>
		</td>
	  </tr>
	  <?php }
	  }
	  ?>
	</table>

  
