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
<input type="hidden" id="cd_ag_id" name="cd_ag_id"  value="<?php echo $ag_id ?>">  
<input type="hidden" id="cd_cp_id" name="cd_cp_id"  value="<?php echo $class_id ?>">  

	<table width="100%" border="1" class="example2" id="example2229"> 
	<tr>
		<td colspan="3" align="center" style="font-size:16px"><strong>หน่วยงาน<?php echo $ag_name ?> <br />ตารางเวร เวลา <?php echo $class_name; ?></strong> </td>
	  </tr>
	  <tr>
	    <td align="center" >ลำดับการเดิน</td>
	    <td align="center" >จุดตรวจ</td>
      </tr>
	  <tr>
		<td width="50%" > 		
		<select name="cd_number" id="cd_number" class="custom-select form-control-border icon-menu"  required>
                    <option value="">เลือกหมายเลขเดิน</option>
                    
                    <option value="1" <?php if($rs['cp_point_number']=='1'){?> selected="selected"<?php } ?>>1</strong></option>
                    <option value="2" <?php if($rs['cp_point_number']=='2'){?> selected="selected"<?php } ?>>2</strong></option>
                    <option value="3" <?php if($rs['cp_point_number']=='3'){?> selected="selected"<?php } ?>>3</option>
                    <option value="4" <?php if($rs['cp_point_number']=='4'){?> selected="selected"<?php } ?>>4</strong></option>
                    <option value="5" <?php if($rs['cp_point_number']=='5'){?> selected="selected"<?php } ?>>5</option>
                    <option value="6" <?php if($rs['cp_point_number']=='6'){?> selected="selected"<?php } ?>>6</strong></option>
                    <option value="7" <?php if($rs['cp_point_number']=='7'){?> selected="selected"<?php } ?>>7</option>
                    <option value="8" <?php if($rs['cp_point_number']=='8'){?> selected="selected"<?php } ?>>8</strong></option>
                    <option value="9" <?php if($rs['cp_point_number']=='9'){?> selected="selected"<?php } ?>>9</option>
                    <option value="10" <?php if($rs['cp_point_number']=='10'){?> selected="selected"<?php } ?>>10</strong></option>
                    <option value="11" <?php if($rs['cp_point_number']=='11'){?> selected="selected"<?php } ?>>11</option>
                    <option value="12" <?php if($rs['cp_point_number']=='12'){?> selected="selected"<?php } ?>>12</strong></option>
                    <option value="13" <?php if($rs['cp_point_number']=='13'){?> selected="selected"<?php } ?>>13</option>
                    <option value="14" <?php if($rs['cp_point_number']=='14'){?> selected="selected"<?php } ?>>14</strong></option>
                    <option value="15" <?php if($rs['cp_point_number']=='15'){?> selected="selected"<?php } ?>>15</option>
                    <option value="16" <?php if($rs['cp_point_number']=='16'){?> selected="selected"<?php } ?>>16</strong></option>
                    <option value="17" <?php if($rs['cp_point_number']=='17'){?> selected="selected"<?php } ?>>17</option>
                    <option value="18" <?php if($rs['cp_point_number']=='18'){?> selected="selected"<?php } ?>>18</strong></option>
                    <option value="19" <?php if($rs['cp_point_number']=='19'){?> selected="selected"<?php } ?>>19</option>
                    <option value="20" <?php if($rs['cp_point_number']=='20'){?> selected="selected"<?php } ?>>20</strong></option> 
                    <option value="21" <?php if($rs['cp_point_number']=='21'){?> selected="selected"<?php } ?>>21</strong></option> 
                    <option value="22" <?php if($rs['cp_point_number']=='22'){?> selected="selected"<?php } ?>>22</strong></option> 
                    <option value="23" <?php if($rs['cp_point_number']=='23'){?> selected="selected"<?php } ?>>23</strong></option> 
                    <option value="24" <?php if($rs['cp_point_number']=='24'){?> selected="selected"<?php } ?>>24</strong></option> 
                    <option value="25" <?php if($rs['cp_point_number']=='25'){?> selected="selected"<?php } ?>>25</strong></option> 
                    <option value="26" <?php if($rs['cp_point_number']=='26'){?> selected="selected"<?php } ?>>26</strong></option> 
                    <option value="27" <?php if($rs['cp_point_number']=='27'){?> selected="selected"<?php } ?>>27</strong></option> 
                    <option value="28" <?php if($rs['cp_point_number']=='28'){?> selected="selected"<?php } ?>>28</strong></option> 
                    <option value="29" <?php if($rs['cp_point_number']=='29'){?> selected="selected"<?php } ?>>29</strong></option> 
                    <option value="30" <?php if($rs['cp_point_number']=='30'){?> selected="selected"<?php } ?>>30</strong></option> 
          </select>
		</td>
		<td width="50%" ><select  name="class_name[]" id="class_name[]" class="form-control"   style="width: 100%;" >
          <option ><strong>เลือกจุดตรวจ</strong></option>
          <?php
				$sql="SELECT *
				FROM  tb_checkpoint
				WHERE  cp_ag_id = '".$ag_id."'
				"; 
			  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
			  $num_rows =mysqli_num_rows($query);
			  if ($num_rows>=1){ 
				while($rstmp3=mysqli_fetch_array($query)) {
				  ?>
          <option value="<?php echo $rstmp3['cp_id'] ?>" <?php if($rstmp3['cp_id']==$rs['cp_id']){?> selected="selected"<?php } ?>>
		  
		  
		  &nbsp;&nbsp;จุด<?php if($rstmp3['cp_point_number']=='0'){?> กะเช้า<?php }elseif($rstmp3['cp_point_number']=='1'){ ?>กะดึก<?php }else{ ?>ที่ <?php echo ($rstmp3['cp_point_number']-1);?> ชื่อ <?php echo $rstmp3['cp_point_name'] ?>   <?php } ?></option>
          <?php }
			  }
			  ?>
        </select></td>
	  </tr>
	</table>

  
