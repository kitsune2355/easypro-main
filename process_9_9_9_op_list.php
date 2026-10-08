 <?php 
define("SB_M1","data_1");
define("SB_M2","data_2_1");
define("SB_M3","data_2_1_1");
include("config_ctrl/connect.php"); 
$process				= $_GET['process']; 
//$chk_ctrl_add 		= fn_detect_permis_pocess($sess_user_id,$process,'ctrl_add');
//$chk_ctrl_edit 		= fn_detect_permis_pocess($sess_user_id,$process,'ctrl_edit');
//$chk_ctrl_view		= fn_detect_permis_pocess($sess_user_id,$process,'ctrl_view');
//$chk_ctrl_print		= fn_detect_permis_pocess($sess_user_id,$process,'ctrl_print');
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
	$trn_cus_id			= $_POST['trn_cus_id'];
	$trn_type 			= $_POST['trn_type'];
	$trn_date_to 		= $_POST['trn_date_to'];
	$t3_cotton 			= $_POST['t3_cotton'];
	$t3_degree			= $_POST['t3_degree'];
	$t3_position		= $_POST['t3_position'];
	$user_id			= $_POST['user_id'];
	
	include "config_ctrl/DetechGuest.php";
	include "config_ctrl/connect.php";
	include "config_ctrl/checksession.php"; 
?> 

<div class="fc fc-unthemed fc-ltr" style="margin-top:-22px;">
  <div class="fc-view-container" style="">
	<div class="fc-view fc-month-view fc-basic-view" ><br>
		<!--<div class="box-body  table-responsive"> <a href="javascript:btn6()" style="font-size:12px; font-weight:bold; padding:5px;" class="btn btn-xs btn-success pull-left">เพิ่มชื่อหน่วยงาน</a><br />-->

		<table class="table table-bordered table-hover " width="100%" border="2" style="  border-color: #999999; margin-top:12px; font-size:14px">
		  <thead>
			<tr bordercolor="#333333" bgcolor="#EFEFEF"> 
			  <th align="center" style=" border-color: #999999"><span >ชื่อหน่วยงาน</span><span></span></th> 
			  <th width="1053" align="center" style=" border-color: #999999">รายชื่อชั้นและรายชื่อห้อง</th> 
			</tr>
		  </thead>
		  <tbody>
		  <?php
		  	if($t3_position!="") {
				$tmpSQt3_position = " AND pst_id = '".$t3_position."'";
			}
		  	if($trn_cus_id!="") {
				$tmpSQLcus = " AND tb_PH.trn_cus_id = '".$trn_cus_id."'";
			}
		  	if($t3_cotton!="") {
				$tmpSQLtype1 = " AND TbPcAr.process_cotton	 = '".$t3_cotton."'";
			}
		  	if($t3_degree!="") {
				$tmpSQLtype2 = " AND TbPcAr.process_degree	 = '".$t3_degree."'";
			}
		  	if($trn_date_to!="" && $trn_date_form!="") {
			//	$tmpSQLdate = " AND DATE_FORMAT( tbDet.sm_date, '%Y-%m-%d' ) BETWEEN  '".$trn_date_form."' AND '".$trn_date_to."'";
			}else{
				//	$tmpSQLdate = " AND DATE_FORMAT( tbDet.sm_date, '%Y-%m-%d' ) BETWEEN  '".date('Y-m-01')."' AND '".date('Y-m-d')."'"; 	
			} 
			if(strlen($search_month)==1) $search_month = "0".$search_month;
		//	
//			$sql_sb="SELECT tbPer1.process_id 
//					 FROM tb_permission_process_v2 AS tbPer1
//					 WHERE tbPer1.user_id LIKE '%".$user_id."%' 
//					 ";
//			$query_sb =mysqli_query($connect,$sql_sb)  or die(mysqli_error($connect));
//			$num_rows_sb =mysqli_num_rows($query_sb);
//			if($num_rows_sb>=1){
//				while($rs_sb=mysqli_fetch_array($query_sb)){ 
//					if($proId=="") {
//						$proId = "'".$rs_sb['process_id']."'";
//					}else{
//						$proId.= ",'".$rs_sb['process_id']."'";
//					}
//				}
//			} 
//			
//			if($user_id!=''){
//				  $temSQLprc = "AND process_id in (".$proId.")"; 
//			}
		  
		    $sql="SELECT  *
				  FROM tb_agency AS TbPcAr   
				  WHERE  ag_status = '1' 
				  ".$tmpSQLtype1."
				  ".$tmpSQLtype2."
				  ".$tmpSQt3_position."
				  ".$temSQLprc."
				  /*ORDER BY  TbPcAr.process_cotton ASC ,TbPcAr.process_degree ASC*/
				  ";  
			$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
			$num_rows =mysqli_num_rows($query);
			if($num_rows>=1){
				$i=1;
				while($rs=mysqli_fetch_array($query)){  
				  ?>
					<tr> 
					  <td width="332" class="" style=" border-color: #999999"><strong> <?php echo $rs['ag_job'];  ?>&nbsp;&nbsp;<?php // echo $rs['ag_name'];  ?></strong></td>
					  <td style=" border-color: #999999">	
					  
					  <table width="100%" cellpadding="0" cellspacing="0" border="1" style=" font-size:12px; border-color: #999999; border:hidden;" >
					  <a onclick="fn_select_qut_1('<?php echo $rs['ag_id']; ?>','<?php echo $rs['ag_job']; ?>')"  href="#" class="btn btn-xs btn-success"><i class="fa fa-plus-circle"></i> เพิ่มตารางเวร</a>
					  
							<?php
								$sql_sb="SELECT *   
									 FROM tb_list_check_head AS TbLcad
									 WHERE ch_agency =  '".$rs['ag_id']."' 
									 ";
							$query_sb =mysqli_query($connect,$sql_sb)  or die(mysqli_error($connect));
							$num_rows_sb =mysqli_num_rows($query_sb);
							if($num_rows_sb>=1){
								$cc=0;
								while($rs_sb=mysqli_fetch_array($query_sb)) { 
								$cc++;
										$class_name = $rs_sb['ch_work_time'].' - '.$rs_sb['ch_end_time']; 
										$tmpImg = '<i class="fa fa-fw fa-user"></i>'; 
									$isapce = '';
									for($x=1;$x<$rs_sb['approve_level'];$x++) {
										$isapce.= '&nbsp;';
										$isapce.= '&nbsp;';
										$isapce.= '&nbsp;';
									} 
								?>
								<tr>
								  <td width="200" bgcolor="#E9E9E9" style="border-bottom-color: #000000; border-bottom-style: dotted;">
								  
								  	<?php echo $isapce?>&nbsp;&nbsp;<strong> &rsaquo;</strong> <strong><?php echo $cc; ?>.</strong> <?=$class_name?><br />
 <!--<a href="javascript:fn_select_qut_3('<?php echo $rs['ag_id']; ?>','<?php echo $rs['ag_name']; ?>','<?php echo $rs_sb['class_id']; ?>','<?php echo $rs_sb['class_name']; ?>')" style="font-size:12px; font-weight:bold; padding:5px;" class="btn btn-xs btn-default pull-left"><strong>กำหนดแอร์</strong></a>--> </td>
								  <td width="956" bgcolor="#E9E9E9" style="border-bottom-color: #000000; border-bottom-style: dotted;"> 
								  <a href="javascript:fn_select_qut_2('<?php echo $rs['ag_id']; ?>','<?php echo $rs['ag_job']; ?>','<?php echo $rs_sb['ch_id']; ?>','<?php echo $rs_sb['ch_work_time']; ?>')" style="font-size:12px; font-weight:bold; padding:5px;" class="btn btn-xs btn-success pull-left">เพิ่มจุด</a><?php echo $rs_sb['dep_name']?>	<br /><br /> 
									  <?php
									$sql_sb_t="SELECT *   
											 FROM tb_list_check_detail AS TbLcad
											 LEFT JOIN tb_checkpoint AS TbCp
											 ON TbCp.cp_id = TbLcad.cd_cp_id
											 WHERE cd_ag_id =  '".$rs['ag_id']."'
											 AND cd_ch_id =  '".$rs_sb['ch_id']."'
											 AND cd_status	=  '1'
											 "; 
											// echo $sql_sb_t;
									$query_sb_t =mysqli_query($connect,$sql_sb_t)  or die(mysqli_error($connect));
									$num_rows_sb_t =mysqli_num_rows($query_sb_t);
									if($num_rows_sb_t>=1){
										$o=0;
										while($rs_sb_t=mysqli_fetch_array($query_sb_t)) {  
									?> 
						<a class="btn btn-app" style=" font-size:14px; width:-100px; height:1px; padding: px;"> <span onclick="fn_close(<?php echo $rs_sb_t['cd_id']; ?>)" class="badge bg-red"><i class="fa fa-fw fa-close"></i></span>ลำดับที่  <?php echo $rs_sb_t['cd_number'] ?> &nbsp;&nbsp;จุด<?php if($rs_sb_t['cp_point_number']=='0'){?> กะเช้า<?php }elseif($rs_sb_t['cp_point_number']=='1'){ ?>กะดึก<?php }else{ ?>ที่ <?php echo ($rs_sb_t['cp_point_number']-1);?> ชื่อ <?php echo $rs_sb_t['cp_point_name'] ?>   <?php } ?></a>  <br />
									<?php 
										}
									} 
									?>
								  
								  				 </td>  
								  <td width="104" bgcolor="#E9E9E9" style="border-bottom-color: #000000; border-bottom-style: dotted;"> 
								  <!--<a href="javascript:fn_del_permisdfsfdsf('<?php echo $rs_sb['ch_id']; ?>','<?php echo $rs['ag_id']; ?>')" style="font-size:12px; font-weight:bold; padding:5px;" class="btn btn-xs btn-danger pull-right">ลบ</a>-->
								  &nbsp;</td>
								</tr>
								<?php
								}
							}else{
							?>
							<tr>
							  <td>-&nbsp;</td>
							  <td>&nbsp;</td> 
							</tr>
							<?php
							}
							?>
					    </table>
					  
					  
					  
					  
					  </td>    
					</tr>
				  <?php 
				  $i++;
				}
				?> 
		  <?php
			}else{
		  ?>
		  <tr>
			  <td colspan="7" class="" align="center"> <span>ไม่มีข้อมูล</span> </td>
		    </tr>
		  <?php } ?>
		  </tbody>
		</table> 
		<!--<a href="management_user_print.php?search_cost_id=<?php echo $search_cost_id ?>&search_month=<?php echo $search_month ?>&search_year=<?php echo $search_year ?>" role="button" data-toggle="tooltip" data-placement="top" target="_blank" title="ปริ้นรายการนัดหมาย" class="btn btn-xs btn-success "><i class="fa fa-print" ></i>ปริ้นรายการนัดหมาย</a>-->
		</div>
    </div>
  </div>
</div>