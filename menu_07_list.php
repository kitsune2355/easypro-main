 
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
	$Day_end 			= $_POST['Day_end'];
	$Day_start 			= $_POST['Day_start'];
	$ag_id 		= $_POST['t3_ag_id'];
	$t3_cotton 			= $_POST['t3_cotton'];
	$t3_degree			= $_POST['t3_degree'];
	$t3_position		= $_POST['t3_position'];
	$user_id			= $_POST['user_id']; 
	 if(strlen($month_period)=='1'){ $month_period = '0'.$month_period; }else{ $month_period = $month_period; }

	//echo  $month_period;
	if( ($month_period*1) >= (date('m')*1)){
		$MaxDate 		= date("d",strtotime($year_period."-".$month_period."-".date('d')));
	}else{
		$MaxDate 		= date("t",strtotime($year_period."-".$month_period."-".date('d')));
	}
//echo date('m');
	include "config_ctrl/DetechGuest.php";
	include "config_ctrl/connect.php";
	include "config_ctrl/checksession.php";  
	 
	function fn_show_detail($id){
	include "config_ctrl/connect.php";
		$AttAns=array("","ปกติ","<span style='color:#FF0000'>ไม่ปกติ</span>");
		  $sql_h="SELECT *
				  FROM (
				  ( 
				  SELECT lp_question1 as lp_question, lp_answer1 as lp_answer ,lp_answer_picture1 AS lp_attachment ,lp_answer_detail1 as lp_detail
				  FROM `tb_list_point`
				  WHERE lp_question1 != ''
				  AND lp_id = '".$id."'
				  )";
				   
			for($i=2; $i<=20; $i++){
				 $sql_h.=" UNION ALL (
				  SELECT lp_question".$i." as lp_question, lp_answer".$i." as lp_answer ,lp_answer_picture".$i." AS lp_attachment ,lp_answer_detail".$i." as lp_detail
				  FROM `tb_list_point`
				  WHERE lp_question".$i." != ''
				  AND lp_id = '".$id."'
				  )";  
			}
				 $sql_h.=" ) AS Tb_NewWt 
			  "; 
			 // echo $sql_h;
			$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
			$num_rows_h =mysqli_num_rows($query_h); 
			if($num_rows_h>=1){   
				while($rs=mysqli_fetch_array($query_h)) {  
					if($rs['lp_question']!=''){ 
			  
				  		echo $rs['lp_question'].' / '.$AttAns[$rs['lp_answer']];
						$dataB = $rs['lp_attachment']; 
						$txt = $dataB;
						$handle = fopen($txt, "r");
						if ($handle) {
						
							while (!feof($handle)) {
								$line = fgets($handle);
							   
							}
						 echo  '<img    src="'.$line.'" >';
							fclose($handle);
						} else {
							echo "Error opening file.\n";   
						}

					}
				}
			}
	} 
	
	
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
						//echo $rs['Day'].'/'.$rs['lp_check_point'].'/'.$rs['lp_ch_id'].'/'.$rs['lp_number'].'<br />';
						$ArrCheckPoint[$rs['Day']][$rs['lp_ch_id']][$rs['lp_check_point']][$rs['lp_number']] 	 =  $rs['lp_check_point'];  
					}
				} 
				//echo print_r($ArrCheckPoint);
			  ?>
         <table id="example1" class="table table-bordered ">
                  <thead>
                  <tr>
                    <th>วันที่</th>
                    <th>ตารางเดิน</th>
                    <th>ลำดับจุดที่</th>
                    <th>ชื่อจุด</th>
                    <th>รูปถ่าย</th>
                    <th>วันและเวลาบันทึก</th>
                  </tr>
                  </thead>
                  <tbody>
				  <?php 
				  $sql_h="SELECT * 
						  FROM tb_list_point  as Tblp
						  LEFT JOIN tb_agency AS tbAg
						  ON Tblp.lp_agency = tbAg.ag_id 
						  LEFT JOIN tb_checkpoint AS tbcp 
						  ON Tblp.lp_check_point = tbcp.cp_id 
						  LEFT JOIN tb_list_check_head AS tbch
						  ON Tblp.lp_ch_id = tbch.ch_id 
						  WHERE lp_agency  = '".$ag_id."'
						  AND DATE_FORMAT(`lp_date`, '%Y-%m-%d') BETWEEN  '".$Day_start."' AND '".$Day_end."'
						  ORDER BY  lp_ins_time DESC 
					  	";    
						echo $sql_h;
				$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
				$num_rows_h =mysqli_num_rows($query_h);
				$mPage = $_GET['mPageH'];
				$page_size=10;
				$Total_Page=ceil($num_rows_h/$page_size);
				if($mPage=="") {
					$page=1;
					$start_rec=0;
				}else{
					$page=$mPage;
					$start_rec=$page_size*($page-1);
					if($start_rec<=0) $start_rec=0;
				}
				$sql.=" 
					  LIMIT $start_rec,$page_size ";  
				$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
				$num_rows_h =mysqli_num_rows($query_h);
				$tmpbody='
				<div class="btn-group">
						';
						for($i=1;$i<=$Total_Page;$i++) {
							if($i==$page){
								$tmpbody.="<a class=\"btn btn-default\" href=\"\">$i</a>";
							}else{
								if($i==1||$i==$Total_Page){
									$tmpbody.="<a class=\"btn btn-success\" href=\"javascript:fn_pageSubmit('$i')\">$i</a>"; }
								elseif(($i<=$page+3)&&($i>=$page-3)) {
									$tmpbody.="<a class=\"btn btn-success\" href=\"javascript:fn_pageSubmit('$i')\">$i</a>"; }
								elseif(($i<=$page+6)&&($i>=$page-6)) {
									$tmpbody.=""; }
							}
						}
						$tmpbody.='
				</div>
				';
				if($num_rows_h>=1){   
					$iCountPg = $start_rec+1;
					while($rs=mysqli_fetch_array($query_h)) {  
				  ?>
                  <tr onmouseover="msOverListColor(this,'#CCCCCC')" onmouseout="msOutListColor(this,'')" >
            <td onclick="fn_gotourl('menu_07_07_add.php?lp_id=<?php echo $rs['lp_id']?>&status=1&img_icon=glyphicon-inbox')">
						<?php echo $rs['lp_date']?>          </td>
                    <td onclick="fn_gotourl('menu_07_07_add.php?lp_id=<?php echo $rs['lp_id']?>&status=1&img_icon=glyphicon-inbox')">
						<?php echo $rs['ch_work_time']?> - <?php echo $rs['ch_end_time']?>          </td>

                    <td onclick="fn_gotourl('menu_07_07_add.php?lp_id=<?php echo $rs['lp_id']?>&status=1&img_icon=glyphicon-inbox')">
						 <?php if($rs['lp_number']=='0'){?><?php echo  $rs['cp_point_name']; ?> 
						 <?php }elseif($rs['lp_number']=='1'){ ?><?php echo  $rs['cp_point_name']; ?> 
						 <?php }else{ ?> 
						 <?php echo ''.($rs['lp_number']-1)?>  <?php } ?>
						 <?php //echo  $rs['cp_point_name']; ?>       </td>
                    <td onclick="fn_gotourl('menu_07_07_add.php?lp_id=<?php echo $rs['lp_id']?>&status=1&img_icon=glyphicon-inbox')">
						<?php echo $rs['cp_point_name']?>          </td> 
                    <td onclick="fn_gotourl('menu_07_07_add.php?lp_id=<?php echo $rs['lp_id']?>&status=1&img_icon=glyphicon-inbox')">
					<?php  echo fn_show_detail($rs['lp_id'])?>  
					
					 </td>
                    <td> 
						<?php echo $rs['lp_ins_time']?>
                    
				<!--	<a href="menu_07_07_add.php?lp_id=<?php echo $rs['lp_id']?>&status=1"  role="button" data-toggle="tooltip" data-placement="top" title="ดูข้อมูล" class="btn btn-xs  btn-info ">แก้ไขข้อมูล<a>
					<a target="_blank" onclick="fn_delete('<?php echo $rs['lp_id']?>')" role="button" data-toggle="tooltip" data-placement="top" title="ดูข้อมูล" class="btn btn-xs  btn-danger "><li class="fas fa-trash"></li><a><br>
          -->        </td> 
                  </tr>
			   <?php }
			   
			   }?>
                  </tbody>
                  <tfoot>
                  <tr>
                    <th>ตารางเดิน</th>
                    <th>ลำดับจุดที่</th>
                    <th>ชื่อจุด</th>
                    <th>วันที่</th>
                    <th>&nbsp;</th>
                    <th>วันและเวลาบันทึก</th>
                  </tr>
                  </tfoot>
                </table>
		</div>
    </div>
  </div>
</div>