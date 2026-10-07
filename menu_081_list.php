 
 <?php 
define("SB_M1","data_1");
define("SB_M2","data_2_1");
define("SB_M3","data_2_1_1");
include("config_ctrl/connect.php"); 
$process				= $_POST['process']; 
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
	$year_period			= $_POST['year_period'];
	$month_period 			= $_POST['month_period_search_m2'];
	$ag_id 		= $_POST['t3_ag_id'];
	$t3_cotton 			= $_POST['t3_cotton'];
	$t3_degree			= $_POST['t3_degree'];
	$t3_position		= $_POST['t3_position'];
	$user_id			= $_POST['user_id']; 
	$date_end			= $_POST['date_end']; 
	 if(strlen($month_period)=='1'){ $month_period = '0'.$month_period; }else{ $month_period = $month_period; }

//	echo  $month_period;
	if( ($month_period*1) >= (date('m')*1)){
		$MaxDate 		= date("d",strtotime($year_period."-".$month_period."-".date('d')));
	}else{
		$MaxDate 		= date("t",strtotime($year_period."-".$month_period."-".date('d')));
	}
// echo date('m');
	include "config_ctrl/DetechGuest.php";
	include "config_ctrl/connect.php";
	include "config_ctrl/checksession.php"; 
	
$cp_ag_id = '1';
$cp_point_number = '3';
$sql_h="SELECT *  
		FROM tb_agency  
       WHERE ag_id =  '".$cp_ag_id."'
		";    
$query_h = mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
$num_rows_h = mysqli_num_rows($query_h);
$rs_agency = mysqli_fetch_array($query_h);
$c =  $rs2['ag_contract'];



$sql_h="SELECT *  
		FROM tb_checkpoint AS TbCp
		LEFT JOIN tb_list_check_detail as TbCde
		ON TbCde.cd_cp_id = TbCp.cp_id
		LEFT JOIN tb_list_check_head as TbCh
		ON TbCh.ch_id = TbCde.cd_ch_id
        WHERE cp_ag_id =  '".$cp_ag_id."'
		AND cp_point_number = '".$cp_point_number."'
		";  
	//echo $sql_h;
$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
$num_rows_h =mysqli_num_rows($query_h);
$rs_checkpoint=mysqli_fetch_array($query_h); 

$sql="SELECT *  
FROM tb_list_check_head AS tbcp 
WHERE  ch_agency     = '".$cp_ag_id."'  
  ";
  //echo $sql;
$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
$num_rows =mysqli_num_rows($query);
if($num_rows>=1){
  while($rs=mysqli_fetch_array($query)) {
	//	echo '<br>เริ่ม'.strtotime($rs['ch_work_time']).' สิ้น'.strtotime($rs['ch_end_time']."+ 1 minute").'<br>';    
		if(time()>=strtotime($rs['ch_work_time']) && time()<=strtotime($rs['ch_end_time']."+ 1 minute")){
				$ch_work_time = $rs['ch_work_time'];
				$ch_end_time = $rs['ch_end_time'];
  				$tmpKey = $rs['ch_id'];
		}
 
  }
}



$sql_cp="SELECT *  
	  FROM tb_list_check_detail AS tbcd 
	  LEFT JOIN tb_checkpoint AS TbCpp
	  ON TbCpp.cp_id = tbcd.cd_cp_id
	  WHERE cd_ch_id = '".$tmpKey."'   
	  AND cp_point_number = '".$cp_point_number."'
	  ";
	//  echo $sql_cp;
$query_cp =mysqli_query($connect,$sql_cp) or die(mysqli_error($connect));
$num_rows_cp =mysqli_num_rows($query_cp);
if($num_rows_cp>=1){
  $rs_cp=mysqli_fetch_array($query_cp);
  	$cp_point_name = $rs_cp['cp_point_name']; 
  	$cp_point_number = $rs_cp['cd_number'];   
  	$cd_cp_id = $rs_cp['cd_cp_id'];   
}else{
  $rs_cp=mysqli_fetch_array($query_cp);
  	$cp_point_name = $rs_cp['cp_point_name']; 
echo $rs_cp['cp_point_number'].'
		<script language="javascript">
		alert("ไม่อยู่ช่วงตรวจจุด");
	//	window.close()
	//	 window.parent.location.href="check.php?cp_ag_id='.$lp_agency.'&cp_point_number='.$lp_check_point.'";
		// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
		// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
		</script>
		';
		//exit();
}  

?> 
<?php 
			  $sql="SELECT *
					  FROM tb_list_check_head 
					  WHERE ch_agency = '".$ag_id."' 
					  ";  
					//  echo $sql;
				$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
				$num_rows =mysqli_num_rows($query);
				if($num_rows>=1){ 
					$i=0;
					while($rs=mysqli_fetch_array($query)){   
						$i++;
						$ArrWorkTime[$rs['ch_id']]	=  $rs['ch_work_time'];  
					}
				}  
			  $sql="SELECT *
					FROM tb_list_check_detail AS TbLcad
					LEFT JOIN tb_checkpoint AS TbCp
					ON TbCp.cp_id = TbLcad.cd_cp_id
					WHERE cd_ag_id = '".$ag_id."'
					";  
					 // echo $sql;
				$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
				$num_rows =mysqli_num_rows($query);
				if($num_rows>=1){
					$i=0;
					while($rs=mysqli_fetch_array($query)){  
						$i++;
						$ArrWorkTimeDetail[$rs['cd_ch_id']]	=  $rs['cp_point_number'].$rs['cp_point_name']; 
					}
				} 
				$sql="SELECT COUNT( cd_id ) as sum , cd_ch_id
					  FROM tb_list_check_detail AS TbLcad
					  LEFT JOIN tb_checkpoint AS TbCp
					  ON TbCp.cp_id = TbLcad.cd_cp_id
					  WHERE cd_ag_id = '".$ag_id."'
					  GROUP BY cd_ch_id
					  ";  
					//  echo $sql;
				$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
				$num_rows =mysqli_num_rows($query);
				if($num_rows>=1){
					$i=1;
					while($rs=mysqli_fetch_array($query)){  
						$ArrAgencySum[$rs['cd_ch_id']]	 =  $rs['sum']; 
					}
				}  
				
				$sql="SELECT DATE_FORMAT(`lp_date`, '%d') AS Day
				      ,lp_check_point 
					  ,lp_ch_id
					  ,lp_number
					  FROM tb_list_point AS TbLcad 
					  WHERE lp_agency  = '".$ag_id."'
					  AND DATE_FORMAT(`lp_date`, '%Y-%m') = '".$year_period.'-'.$month_period."'  
					  ";  
					//  echo $sql;
				$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
				$num_rows =mysqli_num_rows($query);
				if($num_rows>=1){ 
					while($rs=mysqli_fetch_array($query)){  
					//	echo $rs['Day'].'/'.$rs['lp_check_point'].'/'.$rs['lp_ch_id'].'/'.$rs['lp_number'].'<br />';
						$ArrCheckPoint[$rs['Day']][$rs['lp_ch_id']][$rs['lp_check_point']][$rs['lp_number']] 	 =  $rs['lp_check_point'];  
					}
				} 
				//echo print_r($ArrCheckPoint);
			  ?>
      <!--    <table id="example" style="font-size:10px; font-weight:bold" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <td rowspan="2">วันที่</th>
				  	<?php  
					  $sql="SELECT *
						  FROM tb_list_check_head 
						  WHERE ch_agency = '".$ag_id."' 
						  ";  
						 // echo $sql;
					$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
					$num_rows =mysqli_num_rows($query);
					if($num_rows>=1){ 
						$i=0;
						while($rs=mysqli_fetch_array($query)){  
					
					//foreach($ArrWorkTime as $ArrId => $WorkTimeName) {   ?>
                   	<td colspan="<?php echo $ArrAgencySum[$rs['ch_id']]; ?>"><?php echo $rs['ch_work_time']; ?></td>  
					
				   <?php // } 
				   		}
					}
				   ?>
                  </tr>   
					  <tr>
					    <?php  
					  $sql="SELECT *
						  FROM tb_list_check_head 
						  WHERE ch_agency = '9999' 
						  ";  
						  echo $sql;
					$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
					$num_rows =mysqli_num_rows($query);
					if($num_rows>=1){ 
						$i=0;
						while($rs=mysqli_fetch_array($query)){   
							  $sql_sb="SELECT *
								  FROM tb_list_check_detail as TbCd
								  LEFT JOIN tb_checkpoint AS TbCp
								  ON TbCp.cp_id = TbCd.cd_cp_id
								  WHERE cd_ch_id = '".$rs['ch_id']."' 
								  ";  
								 // echo $sql_sb;
							$query_sb =mysqli_query($connect,$sql_sb) or die(mysqli_error($connect));
							$num_rows_sb =mysqli_num_rows($query_sb);
							if($num_rows_sb>=1){ 
								$i=0;
								while($rs_sb=mysqli_fetch_array($query_sb)){   
					  ?>
							<td align="center"> <?php if($rs_sb['cp_point_number']=='0'){?>จุด <?php }elseif($rs_sb['cp_point_number']=='1'){ ?>จุด<?php }else{ ?> <?php echo 'จุด '.($rs_sb['cp_point_number']-1)?>  <?php } ?><?php echo  '<br>'.$rs_sb['cp_point_name']; ?> <?php // echo $rs['ch_id'] ?> <?php // echo $rs_sb['cd_cp_id'] ?></td> 
						<?php }
							}
						}
					}
					?>
					  </tr>  
                  </thead>
                  <tbody>
				   <?php   
								for($MinDate=$MaxDate; $MinDate>=1; $MinDate--){ ?>
                  <tr onmouseover="msOverListColor(this,'#CCCCCC')" onmouseout="msOutListColor(this,'')" >
				  	<td align="center"><?php echo $MinDate*1?></td>
				   <?php  
					  $sql="SELECT *
						  FROM tb_list_check_head 
						  WHERE ch_agency = '999' 
						  ";  
						 // echo $sql;
					$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
					$num_rows =mysqli_num_rows($query);
					if($num_rows>=1){ 
						$i=0;
						while($rs=mysqli_fetch_array($query)){   
							  $sql_sb="SELECT *
								  FROM tb_list_check_detail as TbCd
								  LEFT JOIN tb_checkpoint AS TbCp
								  ON TbCp.cp_id = TbCd.cd_cp_id
								  WHERE cd_ch_id = '".$rs['ch_id']."' 
								  ";  
								//  echo $sql_sb;
							$query_sb =mysqli_query($connect,$sql_sb) or die(mysqli_error($connect));
							$num_rows_sb =mysqli_num_rows($query_sb);
							if($num_rows_sb>=1){  
								while($rs_sb=mysqli_fetch_array($query_sb)){   
								  ?>
								
								<td align="center">   
								<?php //echo $MinDate.'/'.$rs_sb['cd_ch_id'].'/'.$rs_sb['cd_cp_id'].'/'.$rs_sb['cd_number']?>
									<?php if($ArrCheckPoint[$MinDate][$rs_sb['cd_ch_id']][$rs_sb['cd_cp_id']][$rs_sb['cd_number']]!=''){   ?> 
									<a href="#" onclick="fn_select_qut('6506001' ,'03' ,'2023' ,'คุณณรงค์เดช&nbsp;&nbsp;อิงคนานุวัฒน์' ,'1187' ,'2023-03-20 11:42:41')">
						  <i style="font-size:18px; color: #FF0000" class="fa fa-fw fa-check-square-o"></i>		</a>								
									<?php } ?>
					  			</td>
								<?php }
								}
							}
						}
								?> 
                  </tr> 
				  <?php }
				   ?>
                  </tbody>
              <tfoot>
                  <tr>
                    <th>&nbsp;</th>
                    <th>ชื่อหน่วยงาน</th>
                    <th>&nbsp;</th>
                    <th>ชื่องาน</th>
                    <th>รายละเอียดหน่วยงาน</th> 
                    <th>พื้นที่ปฏิบัติงาน</th> 
                  </tr>
                  </tfoot> 
</table>  -->
		  <p>
		    <!--<a href="management_user_print.php?search_cost_id=<?php echo $search_cost_id ?>&search_month=<?php echo $search_month ?>&search_year=<?php echo $search_year ?>" role="button" data-toggle="tooltip" data-placement="top" target="_blank" title="ปริ้นรายการนัดหมาย" class="btn btn-xs btn-success "><i class="fa fa-print" ></i>ปริ้นรายการนัดหมาย</a>-->
		    </div>
		        </div>
	          </div>
          </div>
</p>

<div class="row">  
          <div class="col-md-12 col-lg-12 col-md-12 col-xs-12">
            <!-- Widget: user widget style 1 -->
            <div class="card card-widget widget-user" >
              <!-- Add the bg color to the header using any of the bg-* classes -->
              <div class="widget-user-header bg-" style="background-color:#804040; color:#FFFFFF; height:150px;"><br />
                <h3  ><strong> หน่วยงาน <?php echo $rs_agency['ag_job']; ?><br>
				<!--  จุด <?php if($cp_point_number=='0'){?> กะเช้า<?php }elseif($cp_point_number=='1'){ ?>กะดึก<?php }else{ ?>ที่ <?php echo ($cp_point_number-1).' '.$cp_point_name; ?><?php } ?> -->
				 รอบวันที่ <?php echo fn_thai_date(date('Y-m-d'),'4')?><br>				  เวลา <?php echo $ch_work_time.' - '.$ch_end_time ?> น. 
				<!--<input type="file" accept="image/*" capture="camera" />--><br />

</strong></h5>
              </div> 
               <div class="card-footer ">
                <ul class="nav flex-column">
                  <li class="nav-item">  
	<?php 
	$sql="SELECT *
		  FROM tb_list_check_head 
						  WHERE ch_id = '".$tmpKey."' 
		  ";  
		//  echo $sql;
	$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
	$num_rows =mysqli_num_rows($query);
	if($num_rows>=1){ 
		$i=0;
		while($rs=mysqli_fetch_array($query)){  
	?>	
		 
              <div class="card-footer p-0">
                <ul class="nav flex-column" style="background-color:#FFFFFF">
				<?php 
					 $sql_sb="SELECT *
						  FROM tb_list_check_detail as TbCd
						  LEFT JOIN tb_checkpoint AS TbCp
						  ON TbCp.cp_id = TbCd.cd_cp_id
						  WHERE cd_ch_id = '".$rs['ch_id']."' 
						  ";  
						 // echo $sql_sb;
					$query_sb =mysqli_query($connect,$sql_sb) or die(mysqli_error($connect));
					$num_rows_sb =mysqli_num_rows($query_sb);
						$TmpColor = '';
					if($num_rows_sb>=1){ 
						$i=0;
						while($rs_sb=mysqli_fetch_array($query_sb)){   
			  		?>
					<?php if($ArrCheckPoint[$date_end][$rs_sb['cd_ch_id']][$rs_sb['cd_cp_id']][$rs_sb['cd_number']]!=''){ $TmpColor = 'style="background-color: #DFFFDF"'; }else{ 
						$TmpColor = ''; } ?>
                  <li class="nav-item" <?php echo $TmpColor; ?>>
				  
                    <a href="" class="nav-link">
                     <strong> <?php if($rs_sb['cp_point_number']=='0'){?>จุด <?php }elseif($rs_sb['cp_point_number']=='1'){ ?>จุด<?php }else{ ?> <?php echo 'จุด '.($rs_sb['cp_point_number']-1)?>  <?php } ?><?php echo  ' '.$rs_sb['cp_point_name']; ?> </strong>
					  <span class="float-right badge "><?php if($ArrCheckPoint[$date_end][$rs_sb['cd_ch_id']][$rs_sb['cd_cp_id']][$rs_sb['cd_number']]!=''){   ?> 
									 
						  <i style="font-size:18px; color: #00CC00" class="fa fa-fw fa-check-square-o"></i>		 							
									<?php } ?></span>
                    </a>
                  </li> 
				  <?php }
				  }
				  ?>
                </ul>
              </div>
</div>
<?php // } 
		}
	}
   ?> 
         <!-- <table width="200" border="1">
         
		  <?php  
					  $sql="SELECT *
						  FROM tb_list_check_head 
						  WHERE ch_id = '".$tmpKey."' 
						  ";  
						 // echo $sql;
					$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
					$num_rows =mysqli_num_rows($query);
					if($num_rows>=1){ 
						$i=0;
						while($rs=mysqli_fetch_array($query)){  
					
					//foreach($ArrWorkTime as $ArrId => $WorkTimeName) {   ?>
					 <tr>
                   	<td width="71" ><?php echo $rs['ch_work_time']; ?></td>   
				  </tr>
				   <tr>
				  		<?php 
							 $sql_sb="SELECT *
								  FROM tb_list_check_detail as TbCd
								  LEFT JOIN tb_checkpoint AS TbCp
								  ON TbCp.cp_id = TbCd.cd_cp_id
								  WHERE cd_ch_id = '".$rs['ch_id']."' 
								  ";  
								 // echo $sql_sb;
							$query_sb =mysqli_query($connect,$sql_sb) or die(mysqli_error($connect));
							$num_rows_sb =mysqli_num_rows($query_sb);
							if($num_rows_sb>=1){ 
								$i=0;
								while($rs_sb=mysqli_fetch_array($query_sb)){   
					  ?>
				   <tr>
							<td align="center"> <?php if($rs_sb['cp_point_number']=='0'){?>จุด <?php }elseif($rs_sb['cp_point_number']=='1'){ ?>จุด<?php }else{ ?> <?php echo 'จุด '.($rs_sb['cp_point_number']-1)?>  <?php } ?><?php echo  '<br>'.$rs_sb['cp_point_name']; ?> <?php // echo $rs['ch_id'] ?> <?php // echo $rs_sb['cd_cp_id'] ?><?php if($ArrCheckPoint[$date_end][$rs_sb['cd_ch_id']][$rs_sb['cd_cp_id']][$rs_sb['cd_number']]!=''){   ?> 
									<a href="#" onclick="fn_select_qut('6506001' ,'03' ,'2023' ,'คุณณรงค์เดช&nbsp;&nbsp;อิงคนานุวัฒน์' ,'1187' ,'2023-03-20 11:42:41')">
						  <i style="font-size:18px; color: #FF0000" class="fa fa-fw fa-check-square-o"></i>		</a>								
									<?php } ?></td> 
						
						</tr>
						<?php }
							}
						?> 
						</tr>
				   <?php // } 
				   		}
					}
				   ?> 
          </table>-->
