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
$month_period 	= $_GET['month_period'];
$year_period 	= $_GET['year_period'];
$toum_id 		= $_GET['toum_id'];

   
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
</script>   
<style>
 .textAlignVer{    
		display:block; 
		filter: flipv fliph;
   		-webkit-transform: rotate(-90deg); 
    	-moz-transform: rotate(-90deg); 
    	transform: rotate(-90deg);  
    	width:10px;
    	white-space:nowrap;
    	font-size:12px;
		margin-top:32px;
		margin-left: auto;
		margin-right:auto; 
		text-align:center;
		 z-index: 99;
		 bottom:0px;
		
	} 
</style>


<img src="Logo_โปร.png" alt="ระบบ EasyPro" width="147" height="35">
<table width="100%" class="table table-bordered  thsarabunnew24b" style="border-collapse:collapse; font-size:12px;"border="1"> 
    <tr>
      <td style="background-color:#666666; color:#FFFFFF;"><div align="center" class="style4">ELECTRICAL SYSTEM DAILY RECORD																												</div></td> 
    </tr> 
    <tr>
      <td style="background-color: #CCCCCC; color: #000000;"><div align="center" class="style4">บันทึกการตรวจสอบระบบไฟฟ้าหลักของอาคารประจำวัน																														
																												</div></td> 
    </tr> 
</table>
<table width="100%" class="table table-bordered  thsarabunnew24b" style="border-collapse:collapse; font-size:10px;"border="1"> 
    <tr>
      <td width="58%"> PROJECT TITLE : </td> 
      <td width="42%">OF MONTH :</td>
    </tr> 
    <tr>
      <td rowspan="2" valign="top"> ADDRESS  : </td> 
      <td>BUILDING :</td>
    </tr>
    <tr>
      <td>EQUIPMENT No. :</td>
    </tr> 
</table>

<table width="100%" id="example1" border="1" style="font-size:9px; border-collapse:collapse;"  class="table table-bordered  thsarabunnew24b">
                  <thead> 
                  <tr onmouseover="msOverListColor(this,'#CCCCCC')" onmouseout="msOutListColor(this,'')" >
                    <th rowspan="2">Date</th>
                    <th rowspan="2">Time</th>
                    <th colspan="9">แผงจ่ายไฟฟ้าหลัก / MDB (From TR NO.______________) </th>
                    <th rowspan="2">กิโลวัตต์</th>
                    <th rowspan="2"><div class="textAlignVer" style="font-size:7px;">power factor </div></th>
                    <th colspan="2" rowspan="2">ATS</th>
                    <th rowspan="2">อุณหภูมิ</th>
                    <th rowspan="2">ผู้บันทึก</th>
                  </tr>
                  <tr onmouseover="msOverListColor(this,'#CCCCCC')" onmouseout="msOutListColor(this,'')" >
                    <th colspan="6">แรงดัน / Voltage </th>
                    <th colspan="3">กระแสไฟฟ้า/Amp</th>
                    </tr>
                  <tr onmouseover="msOverListColor(this,'#CCCCCC')" onmouseout="msOutListColor(this,'')" >
                    <th>วันที่</th>
                    <th>เวลา</th>
                    <th>R - S<br />
                    400 V </th>
                    <th>S - T<br />
                    400 V </th>
                    <th>T - R<br />
                    400 V </th>
                    <th>R - N<br />
                    230 V </th>
                    <th>S - N <br />
                    230 V </th>
                    <th>T - N<br />
                    230 V </th>
                    <th>R<br />
                    Amp.</th>
                    <th>S<br />
                    Amp.</th>
                    <th>T<br />
                      Amp.</th>
                    <th>kW</th>
                    <th>PF<br />
                      &gt;0.85</th>
                    <th>Auto</th>
                    <th>Off</th>
                    <th>Temp Rm. </th>
                    <th>Record by </th>
                  </tr>
                  </thead>
                  <tbody>
				  <?php
				  $sql_h="SELECT *
				  		,DATE_FORMAT(toumdt_time, '%Y-%m-%d') AS Day
				  		,DATE_FORMAT(toumdt_time, '%H:%h') AS time
				  		,user_name 
					 	FROM tb_toum_detail AS TbAs
						LEFT JOIN tb_user 
						ON TbAs.toumdt_user_ins = user_id
						WHERE DATE_FORMAT(`toumdt_time`, '%Y%m') = '".$year_period.$month_period."'
						AND toumdt_toum_id = '".$toum_id."'
						ORDER BY toumdt_time ASC
					  	"; 
					//	echo $sql_h;   
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
				 	$iCountRow	= 1;
					$iCountPg = $start_rec+1;
					while($rs=mysqli_fetch_array($query_h)) {  
				  ?>
                  <tr onmouseover="msOverListColor(this,'#CCCCCC')" onmouseout="msOutListColor(this,'')" >
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')">
						<?php echo $rs['Day'].$iCountRow?>					</td>
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')">
					<?php echo $rs['time']?>					</td>
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')">
						<?php echo $rs['toumdt_rs_400']?>					</td>
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toumdt_st_400']?></td>    
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toumdt_tr_400']?></td>      
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toumdt_rn_230']?></td>  
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toumdt_sn_230']?></td>     
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toumdt_tn_230']?></td>  
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toumdt_r']?></td>    
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toumdt_s']?></td>   
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toumdt_t']?></td>     
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toumdt_kw']?></td>     
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toumdt_pf']?></td>    
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toumdt_at']?></td>
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toumdt_off']?></td>
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toumdt_rm']?></td>
                    <td ondblclick="fn_gotourl('menu_28.php?toumdt_id=<?php echo $rs['toumdt_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['user_name']?></td>
                  </tr> 
				   <?php
						$iCountPg++;
						$iCountRow++;
						 } 
				   }
				   if($iCountRow<=31){
					for($i=$iCountRow;$i<=31;$i++){
					?>
					<tr height="10">
					  <td nowrap="nowrap" class="brdrfashion"><?php echo $i; ?></td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					  <td nowrap="nowrap" class="brdrfashion">&nbsp;</td>
					</tr>
					<?
					}
				}
				?> 
					<tr>
					  <td colspan="17" nowrap="nowrap" class="brdrfashion"> ***สถานะ On = เปิด Off  = ปิด  N = ปกติ  AB = ผิดปกติ  บันทึกค่าแรงดันเป็น PSI/Bar บันทึกค่าอุณหภูมิ C° /  F° *** 
&nbsp;</td>
				    </tr>
					
                  </tbody> 
			</table>
			<table width="100%" border="1" style="font-size:12px; margin-top:10px; border-collapse:collapse;" >
			  <tr>
				<td height="60">&nbsp;</td>
			  </tr>
			</table>


			  <table border="1" width="100%" style="font-size:12px; margin-top:10px; border-collapse:collapse;"> 
                <tr height="24">
                  <td colspan="2" height="24">Approved By Site Engineer / Supervisor</td>
                  <td colspan="2">Approved By Customer</td>
                  <td colspan="5">Symbol for Shift &amp; Day</td>
                </tr>
                <tr height="24">
                  <td width="70" height="24" align="left"> Signature : </td>
                  <td width="413">&nbsp;</td>
                  <td width="95" align="left"> Signature : </td>
                  <td width="448">&nbsp;</td>
                  <td width="263" align="left"> M = Morning : 07:00 - 16:00 </td>
                  <td width="227" align="left"> D = Day : 08:00 - 17:00 </td>
                </tr>
                <tr height="24">
                  <td height="24" align="left"> Date : </td>
                  <td>&nbsp;</td>
                  <td align="left"> Date : </td>
                  <td>&nbsp;</td>
                  <td align="left"> A = Afternoon : 14:00 - 23:00 </td>
                  <td align="left"> N = Night : 23:00 - 08:00 </td>
                </tr>
              </table>
			  <p>&nbsp;</p>
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