
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php
header("Content-Type: application/vnd.ms-excel");
header('Content-Disposition: attachment; filename="excel.xls"');  
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
$rp_area_id 		= $_GET['rp_area_id'];
$rp_ac_id 			= $_GET['rp_ac_id']; 
$rp_ar_id 			= $_GET['rp_ar_id']; 
$status 			= $_GET['status'];  
$sr= $_GET['sr'];  
$mPage			= $_GET['mPage'];   
  
			  ?>
     
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
</script>   

<table id="example1s" class="table table-bordered table-striped" border="1" style="font-size:16px;">
                  <thead>
				  <tr>
                    <th colspan="24">ข้อมูล ตั้งแต่ <?php echo $Day_start.' ถึง '.$Day_end;?></th>
                    </tr>
                  <tr>
                    <th width="64">เลขที่เอกสาร</th>
                    <th width="64">วันที่แจ้งซ่อม</th>
                    <th width="64">เวลาแจ้งซ่อม</th>
                    <th width="64">วันที่เข้าซ่อม</th> 
                    <th width="90">ช่องทางแจ้งงาน</th>
                    <th width="150">ชนิดของการบริการ</th>
                    <th width="150">ประเภทงาน</th>
                    <th width="64">ชื่อผู้แจ้ง</th>
                    <th width="64">เบอร์โทร</th>
                    <th width="100">อาคาร/สถานที่/แผนก</th>
                    <th width="50">ชั้น</th>
                    <th width="50">ห้อง</th>
                    <th width="200">รายละเอียดของปัญหา</th>
                    <th width="120">ผู้ดำเนินการ/ช่าง</th>
                    <th width="200">รายละเอียดการปฏิบัติงาน</th>
                    <th width="120">ค่าบริการงานซ่อม</th>
                    <th width="110">ผลการปฏิบัติ</th>
                    <th>วันที่แล้วเสร็จ</th> 
                    <th>เวลาที่แล้วเสร็จ</th> 
                    <th>หมายเหตุุ</th>   
                    <th>การประเมินผลการให้บริการ</th>  
                    <th>ข้อแนะนำเพื่อปรับปรุง</th>   
                    <th width="80">สถานะ</th> 
                    <th width="80">ดำเนินการ</th> 
                  </tr>
                  </thead>
                  <tbody>
				  <?php
				  
					if($rp_area_id!=''){
						$Tmp = 	"AND rp_area_id = '".$rp_area_id."'";
					}
					if($rp_ac_id!=''){
						$Tmp1 = 	"AND rp_ac_id = '".$rp_ac_id."'";
					}
					if($rp_ar_id!=''){
						$Tmp2 = 	"AND rp_ar_id = '".$rp_ar_id."'";
					} 
					if($rp_ar_id!=''){
						$Tmp3 = 	"AND rp_ar_id = '".$rp_ar_id."'";
					} 
					if($status!=''){
						$Tmp4 = 	"AND rp_status = '".$status."'";
					} 
					if($sr!="") {
						$tmpSQLemp = "AND (
									 (rp_format LIKE '%".$sr."%'
									  OR rp_name LIKE '%".$sr."%' 
									  ) 
										)";
					}   
				  
				  
				  $sql_h="SELECT *  
					 	FROM tb_repair AS TbAs 
					  LEFT JOIN tb_area AS tb_area
					  ON tb_area.area_id = TbAs.rp_area_id
					  LEFT JOIN tb_area_class AS tb_area_class
					  ON tb_area_class.ac_id = TbAs.rp_ac_id
					  LEFT JOIN tb_area_room AS tb_area_room
					  ON tb_area_room.ar_id = TbAs.rp_ar_id 
					  LEFT JOIN tb_channel AS tb_channel
					  ON tb_channel.rpcn_id = TbAs.rp_rpcn_id 
					  LEFT JOIN tb_repair_group AS tb_repair_group
					  ON tb_repair_group.rpg_id = TbAs.rp_rpg_id 
					  LEFT JOIN tb_repair_system AS tb_repair_system
					  ON tb_repair_system.rps_id = TbAs.rp_rps_id 
					  LEFT JOIN tb_user AS tb_user
					  ON tb_user.user_id = TbAs.rp_user_id_rep 
					  
						WHERE rp_status IN ('1','2','3')
						  AND DATE_FORMAT(`rp_date`, '%Y-%m-%d') BETWEEN  '".$Day_start."' AND '".$Day_end."'
						".$Tmp."
						".$Tmp1."
						".$Tmp2."
						".$Tmp3."
						".$Tmp4."
						".$tmpSQLemp."
						ORDER BY rp_id DESC
					  	";  
				//	echo   $sql_h;
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
                  <tr onMouseOver="msOverListColor(this,'#CCCCCC')" onMouseOut="msOutListColor(this,'')" > 
                    <td><?php echo $rs['rp_format']; ?></td>
                    <td><?php echo $rs['rp_date']; ?></td>
                    <td><?php echo $rs['rp_time']; ?></td>
                    <td><?php echo $rs['rp_date_active']; ?></td>
                    <td><?php echo $rs['rpcn_name']; ?></td> 
                    <td><?php echo $rs['rpg_name']; ?></td>
                    <td><?php echo $rs['rps_name']; ?></td>
                    <td><?php echo $rs['rp_name']; ?></td>
                    <td><?php echo $rs['rp_phone']; ?></td>
                    <td><?php echo $rs['area_name']; ?></td>
                    <td><?php echo $rs['ac_name']; ?></td>
                    <td><?php echo $rs['ar_name']; ?></td>
                    <td><?php echo $rs['rp_subject']; ?></td>
                    <td><?php echo $rs['user_name'].' '.$rs['user_fname']; ?></td>
                    <td><?php echo $rs['rp_subject3']; ?></td>
                    <td><?php echo $rs['rp_file_close']; ?></td>
                    <td> <?php if($rs['rp_jr']==1){ echo 'แล้วเสร็จ'; }?><?php if($rs['rp_jr']==2){ echo 'ต้องเข้าดำเนินการต่อ'; }?></td>
                    <td><?php echo $rs['rp_date_rep']; ?></td>
                    <td><?php echo $rs['rp_time_rep']; ?></td>
                    <td><?php echo $rs['rp_note_close']; ?></td> 
                    <td><?php if($rs['cb']=='1') echo 'ปรับปรุง'; elseif($rs['cb']=='2') echo 'พอใช้'; elseif($rs['cb']=='3') echo 'ดี'; elseif($rs['cb']=='4') echo 'ดีมาก'; ?>
					</td>  
                    <td><?php echo $rs['rp_note_close']; ?></td>  
                    <td>
					<?php if($rs['rp_status']=='1'){?>กำลังดำเนินการ 
					<?php }elseif($rs['rp_status']=='3'){?> ดำเนินการเรียบร้อย 
					<?php } ?>
					</td>  
                    <td><?php echo $rs['rp_file_close']; ?></td>   
				  </tr>
					
                
				
			   <?php
					$iCountPg++;
					 } 
			   }?>
                  </tbody>
                  
                </table>
   
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