<?php 
ini_set('memory_limit', '64M');
ini_set('max_execution_time', 5000);
include "config_ctrl/connect.php";
define("SB_M1","data_7");
define("SB_M2","data_2_1");
define("SB_M3","data_2_1_1"); 
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
    $thai_date_return.=	" ".((date("Y",$time)+543));
  }else if($formatdate=="4") {
    $thai_date_return =	"".(date("d",$time)*1);
    $thai_date_return.=" ".($thai_month_arr[date("n",$time)]);
		$thai_date_return.=	" ".((date("Y",$time)+543))." ".date('H:i:s',$time);
  }else if($formatdate=="5") {
     $thai_date_return="".((date("Y",$time)+543)); 
  }else if($formatdate=="1") {
    $thai_date_return =	"".(date("d",$time));
    $thai_date_return.=" ".($thai_full_month_arr[date("n",$time)]);
    $thai_date_return.=	" ".((date("Y",$time)+543));
  }else{
    $thai_date_return =	"".(date("d",$time));
    $thai_date_return.=" ".($thai_month_arr[date("n",$time)]);
    $thai_date_return.=	" ".((date("Y",$time)+543-2500));
  }
  if($thai_date_return!="1 ม.ค. 2513"){ return $thai_date_return;  }else{ return ""; }
}
$Day_start 			= $_GET['Day_start'];
$Day_end 			= $_GET['Day_end'];
$ag_id 				= $_GET['t3_ag_id'];
$SubmitH 			= $_GET['SubmitH']; 
$ch_id 				= $_GET['ch_id']; 
$mPage			= $_GET['mPage'];   
 
               function fn_show_detail($id){
			include "config_ctrl/connect.php";
				$AttAns=array("","ปกติ","<span style='color:#FF0000'>ไม่ปกติ</span>");
				  $sql_h="SELECT *
						  FROM (
						  ( 
						  SELECT lp_picture ,lp_question1 as lp_question, lp_answer1 as lp_answer ,lp_answer_picture1 AS lp_attachment ,lp_answer_picture1_1 AS lp_attachment1 ,lp_answer_picture1_2 AS lp_attachment2 ,lp_answer_detail1 as lp_detail
						  FROM `tb_list_point`
						  WHERE lp_question1 != ''
						  AND lp_id = '".$id."'
						  )";
					for($i=2; $i<=20; $i++){
						 $sql_h.=" UNION ALL (
						  SELECT lp_picture, lp_question".$i." as lp_question, lp_answer".$i." as lp_answer ,lp_answer_picture".$i." AS lp_attachment,lp_answer_picture".$i."_1 AS lp_attachment1,lp_answer_picture".$i."_2 AS lp_attachment2 ,lp_answer_detail".$i." as lp_detail
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
								echo '<strong>'.$rs['lp_question'].'</strong> <br />คำตอบ : '.$AttAns[$rs['lp_answer']].'<br />';
								//echo $rs['lp_detail'];
								if($rs['lp_detail']!='') echo 'หมายเหตุ : <span style=\'color:#FF0000\'>'.$rs['lp_detail'].'</span><br />';
								$dataB = $rs['lp_attachment']; 
								if($dataB!=''){
										$txt = '<br />';
										$txt = $dataB;
										$handle = fopen($txt, "r");
										if ($handle) {
										
											while (!feof($handle)) {
												$line = fgets($handle);
											   
											}
										 echo  ' <a href="https://develophpg.net/timeatt/s.php?P='.$dataB.'"><img    src="'.$line.'" ></a><br />';
										 //fn_number_format((filesize($dataB) / 1024),0,0);
											fclose($handle);
										} else {
											echo "Error opening file.\n";   
										}
								echo '<br />';
										
								}
								$dataB = $rs['lp_attachment1']; 
								if($dataB!=''){
										$txt = '<br />';
										$txt = $dataB;
										$handle = fopen($txt, "r");
										if ($handle) {
										
											while (!feof($handle)) {
												$line = fgets($handle);
											   
											}
										 echo  ' <a href="https://develophpg.net/timeatt/s.php?P='.$dataB.'"><img    src="'.$line.'" ></a><br />';
										 //fn_number_format((filesize($dataB) / 1024),0,0);
											fclose($handle);
										} else {
											echo "Error opening file.\n";   
										}
								echo '<br />';
										
								}
								$dataB = $rs['lp_attachment2']; 
								if($dataB!=''){
										$txt = '<br />';
										$txt = $dataB;
										$handle = fopen($txt, "r");
										if ($handle) {
										
											while (!feof($handle)) {
												$line = fgets($handle);
											   
											}
										 echo  ' <a href="https://develophpg.net/timeatt/s.php?P='.$dataB.'"><img    src="'.$line.'" ></a><br />';
										 //fn_number_format((filesize($dataB) / 1024),0,0);
											fclose($handle);
										} else {
											echo "Error opening file.\n";   
										}
								echo '<br />';
										
								}
							}
							echo '<hr>';
						}
					}else{
							
							 $sql_h="SELECT *
									  FROM (
									  ( 
									  SELECT lp_picture 
									  FROM `tb_list_point`
									  WHERE lp_picture != ''
									  AND lp_id = '".$id."'
									  )";
									 $sql_h.=" ) AS Tb_NewWt 
								  "; 
								//  echo $sql_h;
								$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
								$num_rows_h =mysqli_num_rows($query_h); 
								if($num_rows_h>=1){   
									while($rs=mysqli_fetch_array($query_h)) {     
											if($rs['lp_picture']!=''){  
												$dataB = $rs['lp_picture']; 
											//	echo $dataB;
												if($dataB!=''){
														$txt = $dataB; 
														 echo  ' <img src="'.$txt.'" >';
															fclose($handle); 
												echo '<br />';
														
												}
											} 
									}
								} 
							}
			} 
	
	
			    
				//echo print_r($ArrCheckPoint);
			  ?>
    <link rel="stylesheet" href="fonts/thsarabunnew.css" />
 <link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js"></script>
<script type="text/javascript" src="src/jquery.qrcode.js"></script>
<script type="text/javascript" src="src/qrcode.js"></script>
<script type="text/javascript" src="ajax/ajax_framework.js"></script>
<script>
function fn_show1 () {
   	$('#modal-default').modal('show'); 
 }
function fn_alert () {
   	$('#modal-success').modal('show'); 
 }
function fn_alert1(alert_msg,alert_class) {
 	document.getElementById('bts_save_state1').innerHTML = alert_msg;
 	$('#modal-'+alert_class+'').modal('show'); 
}
function fn_alert2(alert_msg,alert_class) {
 	document.getElementById('bts_save_state2').innerHTML = alert_msg;
 	$('#modal-'+alert_class+'').modal('show'); 
}
function fn_alert3(alert_msg,alert_class) {
 	document.getElementById('bts_save_state3').innerHTML = alert_msg;
 	$('#modal-'+alert_class+'').modal('show'); 
}
function fn_alert4(alert_msg,alert_class) {
 	document.getElementById('bts_save_state1').innerHTML = alert_msg;
 	$('#modal-'+alert_class+'').modal('show'); 
	
}
function fn_click(){ 
	fn_show();	
}   
function fn_list_Process10(){    
	var Day_end 		=	document.getElementById('Day_end').value; 
	var Day_start 		=	document.getElementById('Day_start').value;  
	var t3_ag_id 		=	document.getElementById('t3_ag_id').value;  
	//var t3_degree 		=	document.getElementById('t3_degree').value;  
	//var t3_cotton 		=	document.getElementById('t3_cotton').value; 
	//var URL 			= 	"menu_07_list.php";
	var data 	 		= 	'Day_start='+Day_start+
							'&Day_end='+Day_end+ 
							'&t3_ag_id='+t3_ag_id+  
							'&t3_cotton=';   
		ajaxLoad('post', URL, data, 'div_details','');	 
} 
</script>   <table  id="example1" border="1" style="font-size:12px; border-collapse:collapse;"  class="table table-bordered  thsarabunnew24b">
                  <thead style="background-color:#FFDCB9">
				   <tr> 
                    <th width="80" colspan="4"><strong>รายงานข้อมูลดิบ</strong></th> 
                  </tr>
                  <tr> 
                    <th width="80"><strong>ลำดับจุดที่</strong></th>
                    <th width="180"><strong>ชื่อจุด</strong></th>
                    <th width="369"><strong>คำถาม</strong></th>
                    <th width="110"><strong>วันและเวลาบันทึก</strong></th>
                  </tr>
                  </thead>
                  <tbody>
				  <?php 
			include "config_ctrl/connect.php";
				  if($ch_id!='') $TmpCh = "AND lp_ch_id ='".$ch_id."'"; 
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
						  ".$TmpCh."
						  ORDER BY   lp_date ASC  ,   ch_work_time ASC  ,  lp_number ASC
					  	";    
			 	//echo $sql_h;
				$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
				$num_rows_h =mysqli_num_rows($query_h); 
			 	//echo $num_rows_h;
				if($num_rows_h>=1){    
								$Topic1=1; 
					while($rs=mysqli_fetch_array($query_h)) {    
				  if($rs['ch_work_time']!=$tmpAg){ 
								?>
								 <tr <?php echo $tmpBg; ?>   > 
								  <td height="30" colspan="4"  class="" style=" background-color: #999999; color: #000000"><strong><?php  echo $Topic1.'. วันที่ '.fn_thai_date($rs['lp_date'],'3').' รอบ '.$rs['ch_work_time'].' - '.$rs['ch_end_time'].' ผู้ตรวจ : '.$rs['ls_security_name'].$rs['lp_more_details']; ?></strong>									 </td>    
								</tr>
								<?php
								$Topic1++; 
								$Topic2=1;
								$tmpAg=$rs['ch_work_time'];
								}
								?>
                  <tr onmouseover="msOverListColor(this,'#CCCCCC')" onmouseout="msOutListColor(this,'')" >
           

                    <td valign="top" align="center">
						 <?php if($rs['lp_number']=='0'){?><?php echo  '0'; ?> 
						 <?php }elseif($rs['lp_number']=='1'){ ?><?php echo  '0'; ?> 
						 <?php }else{ ?> 
						 <?php echo ''.($rs['lp_number']-1)?>  <?php } ?>
						 <?php //echo  $rs['cp_point_name']; ?>       </td>
                    <td valign="top">
						<?php echo $rs['cp_point_name']?>          </td> 
                    <td valign="top" >
					<?php
					
								if($rs['lp_more_details']!=''){  echo 'บันทึกเพิ่มเติม : '.$rs['lp_more_details'].' '; }  echo fn_show_detail($rs['lp_id']); ?>					 </td>
                    <td valign="top"> 
						<?php echo fn_thai_date($rs['lp_ins_time'],'4')?>			       </td> 
                  </tr>
			   <?php }
			   
			   }?>
                  </tbody>
                  <tfoot>
                  </tfoot>
                </table> 
			  <div id="div_details"></div>
        </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
   
 <!-- DataTables  & Plugins -->
 <script src="plugins/select2/js/select2.full.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="plugins/jszip/jszip.min.js"></script>
<script src="plugins/pdfmake/pdfmake.min.js"></script>
<script src="plugins/pdfmake/vfs_fonts.js"></script>
<script src="plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<script>
//fn_list_Process10();
 
<?php if($ch_id==''){ ?>
setTimeout(ListAmphur('<?php echo $ag_id; ?>','cus_addr05'),1000); 
<?php } ?>		 
function ListAmphur(SelectValue,nameaddr)
					{   
						var URL = "get_list.php?SelectValue=" + SelectValue + "&emp=" ;
						ajaxLoad('get', URL, '', nameaddr,''); 
					}
<?php if($SubmitH==''){ ?>

			document.getElementById("studentFormd").submit();
			<?php }else{ ?>
			//  setTimeout(ListAmphur('<?php echo $ag_id; ?>','cus_addr05'),1000); 
			<?php } ?>
  $(function () {
    $("#example12").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": true,
      "buttons": ["copy",  "excel",  "print", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
    });
  }); 
	function msOverListColor(el,tarcolor) {
		el.bgColor = tarcolor;
		el.style.cursor = 'pointer';
	}
	function msOutListColor(el,tarcolor) {
		el.bgColor = tarcolor;
		if (typeof fn_view_approve_list === "function") {
			fn_view_approve_list('','');
		}
	}
	function msOverList(el,imgtar) {
		el.style.backgroundImage="url("+imgtar+")";
		el.style.cursor = 'pointer';
	}
	function msOutList(el,imgtar) {
		el.style.backgroundImage = "url("+imgtar+")";
	}
	function fn_gotourl(mLink) {
		window.location.href=mLink;
	}
	function fn_delete(rsmp_id) { 
		if (confirm('ต้องการลบข้อมูลใช่หรือไม่')) {
			window.location.href="fn_save_center.php?user_id="+rsmp_id+"&SubmitH=Submit_1_1";
		}
	}
	 
</script>