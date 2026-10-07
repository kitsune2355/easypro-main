<?php 
define("SB_M1","data_10");
define("SB_M2","data_2_1");
define("SB_M3","data_2_1_1");
include "head.php"; 
$ag_id = $_GET['ag_id'];


$Day_start 			= $_GET['Day_start'];
$Day_end 			= $_GET['Day_end'];
$ag_id 				= $_GET['t3_ag_id'];
$SubmitH 			= $_GET['SubmitH']; 
$ch_id 				= $_GET['ch_id']; 
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
 
	var year_period 		=	document.getElementById('year_period').value; 
	var month_period_search_m2 		=	document.getElementById('month_period_search_m2').value;  
	var t3_ag_id 		=	document.getElementById('t3_ag_id').value;  
	//var t3_degree 		=	document.getElementById('t3_degree').value;  
	//var t3_cotton 		=	document.getElementById('t3_cotton').value; 
	var URL 			= 	"menu_08_list.php";
	var data 	 		= 	'year_period='+year_period+
							'&month_period_search_m2='+month_period_search_m2+ 
							'&t3_ag_id='+t3_ag_id+  
							'&t3_cotton=';   
		ajaxLoad('post', URL, data, 'div_details','');	 
} 
</script>
<div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h3><strong>รายงานสถานการณ์ปกติ</strong></h3>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
           <!--   <li class="breadcrumb-item active"><strong>รายการบัตร-จุดตรวจ</strong></li>
              <li class="breadcrumb-item"><a href="menu_04_04_add.php">เพิ่มรบัตร-จุดตรวจใหม่<a></li>-->
			   
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>
<div class="container-fluid">
        <div class="row">
    </div>    

            <div class="card">
			<div class="card-body">
            <div class="row"> 
			
          <div class="col-12">
        <div class="row">
					<strong><br />ค้นหา</strong> 
                    <div class="col-sm-2">
				  <div class="form-group"> <strong>เริ่มตั้งแต่</strong> 
				  
  <form id="studentFormd" class="form-horizontal" method="GET" enctype="multipart/form-data" action="#">
    <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_2" />
				  <input id="Day_start" name="Day_start" type="date" class="form-control" value="<?php if($Day_start!=''){ echo $Day_start; }else{ echo date('Y-m-d'); }?>"  onchange="fn_list_Process10()"/>
                <!-- <select name="year_period" id="year_period" class="form-control select2" style="font-size:14px; font-weight:normal; padding-top:0px;" onchange="fn_list_Process10()"  > 
					<option value="" > เลือกปี</option> 
					 <?php
					  if($year_period!="undefined"){
							 $year_period = date('Y');
						  }else{
						   	 $year_period = $year_period;
						  }
					 for($i=(date('Y'));$i>(date('Y')-5);$i--) {
					  ?>
					  <option value="<?php echo  $i?>" <?php  if($i==$year_period) echo 'selected';?>  ><?php echo $i+543?></option>
					  <?php
					 }?>
				</select> -->
                  
                </div>
                  </div>
                  <div class="col-sm-2">
				  <div class="form-group"> 
				  <strong>สิ้นสุด</strong>
				  <input id="Day_end" name="Day_end" type="date" class="form-control" value="<?php echo date('Y-m-d')?>" onchange="fn_list_Process10()" />
                <!-- <select name="month_period_search_m2" id="month_period_search_m2" class="form-control select2" style=" font-size:14px; font-weight:normal; padding-top:0px;"  onchange="fn_list_Process10()"  > 
						<option value="" > เลือกเดือน</option> 
						  <?php
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
						  
		if($month_period!='undefined'){ $month_period = date('m'); }else{  if(strlen($month_period)=='1'){ $month_period = '0'.$month_period; }else{ $month_period = $month_period; } } 
						//  echo $month_period_search;
						  for($i=01;$i<=12;$i++) { 
						  	
						   ?>
						   <option value="<?php if($i<=9){?>0<?php } ?><?php echo $i?>" <?php if($i==$month_period) echo 'selected'; ?>  ><?php echo $thai_full_month_arr[$i]?></option>
						   <?php
						  }?>
					</select>-->
                </div>
                  </div>
				  
                  <div class="col-sm-2">
				  <div class="form-group"> 
				  <strong>หน่วยงาน</strong>
					<select id="t3_ag_id" name="t3_ag_id"  class="form-control select2 "  data-select2-id="1" tabindex="-1" aria-hidden="true" onChange="ListAmphur(this.value,'cus_addr05','cus_addr04')" > 
						<?php
						$i=1;
						if($sess_user_level=='employer'){
							$TmpUserIdAg2  = "WHERE tb_agency.ag_id in (".$sess_Ag_mul.")";
						}  
						$sql="SELECT *
							  FROM tb_agency
							  ".$TmpUserIdAg2."
							  ";
							  echo $sql;
						$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
						$num_rows =mysqli_num_rows($query);
						if($num_rows>=1){
							while($rs_sb=mysqli_fetch_array($query)) {
					  ?>
						<option value="<?php echo $rs_sb['ag_id']; ?>" <?php if($ag_id==$rs_sb['ag_id']){?> selected="selected"<?php } ?>><?php echo '<strong>-'.$rs_sb['ag_job'].'</strong> '.$rs_sb['ag_name']; ?></option>								 
						<?php 
							}					
						}
						?>
					</select>
					      </div>
                  </div>
				  <div class="col-sm-2">
				  <div class="form-group"> 
				  <strong>รอบเวลาเดินตรวจจุด</strong> 
					 <select name="ch_id" id="cus_addr05" class="form-control select2"  style=" font-size:14px; font-weight:normal; height:50px; padding-top:0px;" >
            <option value="">- เลือกรอบเวลาเดินตรวจจุด -</option>
			<option value="">- แสดงทั้งหมด -</option>
            <?php
				if($ch_id!=''){
					$sql="SELECT ch_id, CONCAT(ch_work_time, ' - ', ch_end_time) AS FullName
						  FROM tb_list_check_head     
						  WHERE ch_agency = '".$ag_id."' 
						  ORDER BY ch_id ASC
					  "; 
					$query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
					$num_rows =mysqli_num_rows($query);
					if ($num_rows>=1){ 
						while($rstmp=mysqli_fetch_array($query)) { ?>
		
					<option value="<?php echo $rstmp['ch_id'] ?>" <?php if($rstmp['ch_id']==$ch_id){?> selected="selected"<?php } ?>><?php echo $rstmp['FullName'] ?></option>
		
		<?php 
						}
					}
				} 
				?>
            </select>
			
					      </div>
                  </div> 
                  <div class="col-sm-2">
				  <div class="form-group"> <br />

				  <input id="end" name="end" type="hidden" class="form-control" value="emp"  />
      <input type="submit" class="btn btn-info" value="ค้นหา">
                  </div> 
    </div>   
  </form>  
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      </div>
      </div>
 
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
        

            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><strong>หน่วยงาน<?php echo $ag_contract?></strong></h3> 
              </div>

              <div class="card">
              <div class="card-header">
                <h3 class="card-title"><strong>เวลา<?php echo $ag_contract?></strong></h3> 
              </div>

              <!-- /.card-header -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr> 
                    <th>วันที</th>
                    <th>รอบเวลา</th>
                    <th>จุดตรวจ</th>
                    <th>สิ่งผิดปกติ</th>
                    <th>รูปภาพ</th>
                    <th>วันและเวลาบันทึก</th>
                  </tr>
                  </thead>
                  <tbody>
				  <?php
				  if($ch_id!='') $TmpCh = "AND lp_ch_id ='".$ch_id."'"; 
						
				  $sql_h="SELECT *
                  FROM (
                  (
                  
                  SELECT lp_ins_time, lp_id, lp_date,lp_number, lp_agency, lp_check_point, lp_ch_id, lp_question1, lp_answer1,lp_answer_picture1 AS lp_attachment
                  FROM `tb_list_point`
                  WHERE lp_answer1 = '1'
                    )";
				  
                  for($i=2; $i<=20; $i++){
					 $sql_h.=" UNION ALL ( 
							  SELECT lp_ins_time, lp_id, lp_date,lp_number, lp_agency, lp_check_point, lp_ch_id, lp_question".$i.", lp_answer".$i.",lp_answer_picture".$i." AS lp_attachment
							  FROM `tb_list_point`
							  WHERE lp_answer".$i." = '1'
							  )";  
					 }
                 $sql_h.=" 
                  ) AS Tb_NewWt
                  LEFT JOIN tb_agency AS tbag ON Tb_NewWt.lp_agency = tbag.ag_id
                  LEFT JOIN tb_list_check_head AS tbch ON Tb_NewWt.lp_ch_id = tbch.ch_id
                  LEFT JOIN tb_checkpoint AS tbcp ON Tb_NewWt.lp_check_point = tbcp.cp_id 
				 WHERE lp_agency  = '".$ag_id."'
						  AND DATE_FORMAT(`lp_date`, '%Y-%m-%d') BETWEEN  '".$Day_start."' AND '".$Day_end."'
						  ".$TmpCh."
						  ORDER BY  Tb_NewWt.lp_ins_time DESC 
          ";
		  
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
				   
                    <td onclick="fn_gotourl('menu_06_06_add.php?ch_id=<?php echo $rs['ch_id']?>&status=1&img_icon=glyphicon-inbox')">
						<?php echo fn_thai_date($rs['lp_date'],'3')?> 
					</td>
                    <td onclick="fn_gotourl('menu_06_06_add.php?ch_id=<?php echo $rs['ch_id']?>&status=1&img_icon=glyphicon-inbox')">
						<?php echo $rs['ch_work_time']?> - <?php echo $rs['ch_end_time']?>
					</td>
            <td onclick="fn_gotourl('menu_06_06_add.php?ch_id=<?php echo $rs['ch_id']?>&status=1&img_icon=glyphicon-inbox')"> 
						 <?php if($rs['lp_number']=='0'){?>จุด 
						 <?php }elseif($rs['lp_number']=='1'){ ?>จุด
						 <?php }else{ ?> 
						 <?php echo 'จุด '.($rs['lp_number']-1)?>  <?php } ?>
						 <?php echo  $rs['cp_point_name']; ?>
					</td>
            <td onclick="fn_gotourl('menu_06_06_add.php?ch_id=<?php echo $rs['ch_id']?>&status=1&img_icon=glyphicon-inbox')">
						<?php echo $rs['lp_question1']?>
						
					</td>
			
                    <td> 
					
						<?php 
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
						}
							?>	
                     
					</td> 
            <td onclick="fn_gotourl('menu_06_06_add.php?ch_id=<?php echo $rs['ch_id']?>&status=1&img_icon=glyphicon-inbox')">
					<?php echo fn_thai_date($rs['lp_ins_time'],'4')?>
					</td>
                  </tr>
			   <?php }
			   
			   }?>
                  </tbody>
                  <tfoot>
                  <tr> 
                  <th>วันที่</th>
                    <th>รอบเวลา</th>
                    <th>จุดตรวจ</th>
                    <th>สิ่งผิดปกติ</th>
                    <th>รูปภาพ</th>
                    <th>วันและเวลาบันทึก</th>
                  </tr>
                  </tfoot>
                </table>
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

      
       
       
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  
 <?php include "footer.php"; ?>
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
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": true,
      "buttons": ["copy",  "excel",  "print", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": false,
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

