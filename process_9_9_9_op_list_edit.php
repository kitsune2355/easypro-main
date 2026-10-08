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
			"2"=>"กุมภาพันธ์",
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
	$trn_cus_id		= $_POST['trn_cus_id'];
	$trn_type 		= $_POST['trn_type'];
	$brand_name 	= $_POST['brand_name'];
	$process_id     = $_POST['process_id']; 
	$process_degree	= $_POST['process_degree']; 
	$dep_name		= $_POST['dep_name']; 
	$pst_name		= $_POST['pst_name']; 
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
<input type="hidden" id="process_id" name="process_id"  value="<?php echo $process_id ?>">  
	<table width="100%" border="1" class="example2" id="example2228"> 
	<tr>
		<td colspan="2" align="center" style="font-size:16px"><strong><?php echo $process_degree ?></strong></td>
	  </tr>
	  <tr>
	    <td colspan="2" align="center" ><strong>กำหนดช่วงเวลาการเดิน</strong></td>
      </tr>
	  <tr>
	    <td  align="center"><strong>เริ่มต้น</strong></td>
	    <td  align="center"><strong>สิ้นสุด</strong></td>
      </tr>
	  <tr>
		<td width="50%" >
<input type="time" style="width:100%" id="class_name[]"  class="form-control form-control-border" name="class_name[]" placeholder="09.00" value="" onkeyup="fn_add_item_1()"></td> 
			<td width="50%"  align="center"> 
			  <input type="time" style="width:100%" class="form-control form-control-border" id="class_name2[]" name="class_name2[]" placeholder="10.00" value="" onkeyup="fn_add_item_1()" />
			 </td>
	  </tr>
	</table>

  
