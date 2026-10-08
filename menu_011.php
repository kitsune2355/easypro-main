<?php 
define("SB_M1","data_11");
define("SB_M2","data_2_1");
define("SB_M3","data_2_1_1");
include "head.php"; 


$cq_id 		= $_GET['cq_id'];
$cq_answer 		= $_GET['cq_answer']; 
 
	$sql="SELECT tbcq.*		
		  FROM tb_category_questions as tbcq
		  WHERE tbcq.cq_id = '".$cq_id."'
		  LIMIT 0 , 1
		  "; 
    $query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
    $num_rows =mysqli_num_rows($query);
    if($num_rows>=1){
        $rs=mysqli_fetch_array($query);
        $readonly1 = ' readonly';
        $text = 'แก้ไขพื้นที่ปฏิบัติงาน';
    }else{
        $text = 'เพิ่มพื้นที่ปฏิบัติงาน';
    }
      
       


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
	var URL 			= 	"menu_011.php";
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
            <h3><strong>ข้อมูลพื้นที่ปฏิบัติงาน  </strong></h3>
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
        
 
    <!-- Main content -->
	<iframe id="iFm_save_target" name="iFm_save_target" src="" style="width:0px;height:0px;border:0"></iframe>
	<form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target">
	<input name="SubmitH" type="hidden" id="SubmitH" value="Submit_14" />  
	<input name="status" type="hidden" id="status" value="<?php echo $status ?>" /> 
	<input name="cq_id" type="hidden" id="cq_id" value="<?php echo $cq_id ?>" /> 



          <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"><strong><?php echo $text ?></strong></h3>
              </div>
              <!-- /.card-header --> 
              <div class="row">
              <div class="card-body">  
              <div class="row">
                    <div class="col-sm-6">
                    <div class="form-group">
                  <label for="exampleInputBorder">เพิ่มพื้นที่ปฏิบัติงาน<code>*</code></label> 
                  <input type="text" name="cq_answer" class="form-control form-control-border"  id="exampleInputBorder" value="<?php echo $rs['cq_answer']?>" placeholder="เพิ่มพื้นที่ปฏิบัติงาน"  <?php if($status=='1'){?>  <?php } ?> required> 
           </div> 


                  
                </div>
                  </div>  

				  <div style="float:left">
				  <button type="submit" class="btn btn-success " style="text-align:left; margin-top:10px; margin-left:10px;">
						<span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span> บันทึกข้อมูล </button>  
				   <!--<button onclick="fn_cancel_doc()"  class="btn btn-warning"><span class="glyphicon glyphicon-save"></span> ยกเลิก </button>  -->
					</div></div>
				  
				  
				  </div>
      <!-- /.container-fluid -->
      <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">

          </div></div></div></div>
        

            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><strong>ข้อมูลพื้นที่ปฏิบัติงาน</strong></h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>รายชื่อพื้นที่ปฏิบัติงาน</th>
                    <th>ดำเนินการ</th>
                  </tr>
                  </thead>
                  <tbody>
				  <?php
				  $sql_h="SELECT *  
					 	FROM tb_category_questions 
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
                    <td onclick="fn_gotourl('menu_011.php?cq_id=<?php echo $rs['cq_id']?>&status=1&img_icon=glyphicon-inbox')">
                    <?php echo $rs['cq_answer']?>   
                    <td> 
					<a href="menu_011.php?cq_id=<?php echo $rs['cq_id']?>&status=1"  role="button" data-toggle="tooltip" data-placement="top" title="ดูข้อมูล" class="btn btn-xs  btn-info ">แก้ไขคำถาม<a>
					<a target="_blank" onclick="fn_delete('<?php echo $rs['cq_id']?>')" role="button" data-toggle="tooltip" data-placement="top" title="ดูข้อมูล" class="btn btn-xs  btn-danger "><li class="fas fa-trash"></li><a>
					
					</td> 
                  </tr>
			   <?php }
			   
			   }?>
                  </tbody>
                  <tfoot>
                  <tr>
                    <th>รายชื่อพื้นที่ปฏิบัติงาน</th>
                    <th>ดำเนินการ</th>
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

