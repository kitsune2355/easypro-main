<?php 
define("SB_M1","data_index");
define("SB_M2","data_2_1");
define("SB_M3","data_2_1_1");
include "head.php"; 
$ag_id = $_GET['ag_id'];


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
	var date_end 		=	document.getElementById('date_end').value;  
	
	//var t3_degree 		=	document.getElementById('t3_degree').value;  
	//var t3_cotton 		=	document.getElementById('t3_cotton').value; 
	var URL 			= 	"menu_081_list.php";
	var data 	 		= 	'year_period='+year_period+
							'&month_period_search_m2='+month_period_search_m2+ 
							'&date_end='+date_end+ 
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
            <h3><strong>รายงานเดินตรวจจุด</strong></h3>
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
          <div class="col-12">
        

            <div class="card">
			<div class="card-body">
            <div class="row"> 
					ค้นหา 
                    <div class="col-sm-3">
				  <div class="form-group"> 
                 <select name="year_period" id="year_period" class="form-control select2" style="font-size:14px; font-weight:normal; padding-top:0px;" onchange="fn_list_Process10()"  > 
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
				</select> 
                  
                </div>
                  </div>
                  <div class="col-sm-3">
				  <div class="form-group"> 
                 <select name="month_period_search_m2" id="month_period_search_m2" class="form-control select2" style=" font-size:14px; font-weight:normal; padding-top:0px;"  onchange="fn_list_Process10()"  > 
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
					</select>
                </div>
                  </div>
				  
                  <div class="col-sm-3">
				  <div class="form-group"> 
					<select id="t3_ag_id" name="t3_ag_id"  class="form-control select2" onchange="fn_list_Process10()" > 
						<?php
						$i=1;
						if($sess_user_level=='op'){
							$TmpUserIdAg2  = "WHERE tb_agency.ag_user_id = '".$sess_user_id."'";
						}  
						$sql="SELECT *
							  FROM tb_agency
							  ".$TmpUserIdAg2."
							  ";
						$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
						$num_rows =mysqli_num_rows($query);
						if($num_rows>=1){
							while($rs_sb=mysqli_fetch_array($query)) {
					  ?>
						<option value="<?php echo $rs_sb['ag_id']; ?>" <?php if($t3_ag_id==$rs_sb['ag_id']){?> selected="selected"<?php } ?>><?php echo '<strong>-'.$rs_sb['ag_job'].'</strong> '.$rs_sb['ag_name']; ?></option>								 
						<?php 
							}					
						}
						?>
					</select>
					      
                </div>
                  </div>
				  
                  <div class="col-sm-2">
				  <div class="form-group"> 
						  <select onchange="fn_list_Process10()" id="date_end" name="date_end" class="form-control select2" style="width: 100%;" >
						<option value="">--&nbsp;สินสุด&nbsp;--</option>
					 <?php  
					 	for($i=1;$i<=31;$i++) {  
					?>
					<option value="<?php echo $i ?>" <?php if((date('d'))==$i){?>selected="selected"<?php } ?>><?php echo $i ?> &nbsp;  </option>
					<?php   
						}
					?>
					</select></div>
                  </div>
                  </div> 
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      </div> 

    
	
    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            
          <div class="card">
              <div class="card-header">
                <h3 class="card-title"><strong>ข้อมูลเดินตรวจจุด</strong></h3>
              </div>
			  <div id="div_details"></div>
          </div>  
            
          </div>
          <!-- /.col -->
          
        </div>
        <!-- /.row -->
        
      </div>
      </div>
       
       
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
fn_list_Process10();
  $(function () {
    $("#example1").DataTable({
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

