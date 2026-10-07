<?php 
define("SB_M1","data_13");
define("SB_M2","data_2_1");
define("SB_M3","data_2_1_1");
include "head.php";  
?>

<script type="text/javascript" src="ajax/ajax_framework.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js"></script>
<script type="text/javascript" src="src/jquery.qrcode.js"></script>
<script type="text/javascript" src="src/qrcode.js"></script>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h3><strong>รายการข้อมูลหน่วยงาน</strong></h3>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item active"><strong>รายการข้อมูลหน่วยงาน</strong></li>
              <li class="breadcrumb-item"><a href="menu_14_add.php"><strong>เพิ่มรายการข้อมูลหน่วยงาน</strong><a> 
			   
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
        

            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><strong>ข้อมูลหน่วยงาน</strong></h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped" style="font-size:12px;">
                  <thead>
                  <tr>
                    <th width="64">เลขที่เอกสาร</th>
                    <th width="50">วันที่แจ้ง</th>
                    <th width="142">ชื่อผู้แจ้ง</th>
                    <th width="300">เรื่องที่แจ้ง</th> 
                    <th width="80">สถานะ</th> 
                    <th width="10">ดำเนินการ</th> 
                  </tr>
                  </thead>
                  <tbody>
				  <?php
				  $sql_h="SELECT *  
					 	FROM tb_repair AS TbAs 
						WHERE rp_status IN ('1','2','3')
						ORDER BY rp_id DESC
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
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">
						<?php echo $rs['rp_format']?>
					</td>
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">
					<?php echo $rs['rp_date']?>
					</td>
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">
						<?php echo $rs['rp_name']?>
					</td>
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">
					<?php echo $rs['rp_subject']?>
					</td>    
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">
					<?php if($rs['rp_status']=='1'){?>
					<button type="button" class="btn btn-block btn-info btn-xs">รอรับเรื่อง</button>
					<?php }elseif($rs['rp_status']=='2'){?>
					<button type="button" class="btn btn-block btn-warning btn-xs">ดำเนินการ</button>
					<?php }elseif($rs['rp_status']=='3'){?>
					<button type="button" class="btn btn-block btn-success btn-xs">ดำเนินการเรียบร้อย</button>
					<?php } ?>
					</td>      
                    <td>  
					<a href="menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1"  role="button" data-toggle="tooltip" data-placement="top" title="แก้ไขข้อมูล" class="btn btn-xs  btn-info "><li class="fas fa-edit"></li><a> 
				   <?php if($sess_user_level=='admin'||$sess_user_level=='general'){ ?>
					<a target="_blank"  onclick="fn_delete('<?php echo $rs['user_id']?>')" role="button" data-toggle="tooltip" data-placement="top" title="ลบข้อมูล" class="btn btn-xs  btn-danger "><li class="fas fa-trash"></li> <a>
						<?php 
					 } ?> 
					</td>   
					
				  </tr>
					
                
				
			   <?php
					$iCountPg++;
					 } 
			   }?>
                  </tbody>
                  <tfoot>
                  <tr>
                    <th>เลขที่เอกสาร</th>
                    <th>วันที่แจ้ง</th>
                    <th>ชื่อผู้แจ้ง</th>
                    <th>เรื่องที่แจ้ง</th> 
                    <th>สถานะ</th> 
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
  
 <?php include "footer.php"; ?>
 <!-- DataTables  & Plugins -->
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
  $(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": true,
      "ordering": false,
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
		Swal.fire({
		  title: "ต้องการลบข้อมูลใช่หรือไม่", 
		  icon: "warning",
		  showCancelButton: true,
		  cancelButtonText: "ไม่",      
		  confirmButtonColor: "#3085d6",
		  cancelButtonColor: "#d33",
		  confirmButtonText: "ใช่"
		}).then((result) => {
		  if (result.isConfirmed) {
		//	window.location.href="fn_save_center.php?user_id="+rsmp_id+"&SubmitH=Submit_1_1";
			
			var URL = "fn_save_center.php" ;
			var data = 'user_id='+rsmp_id+'&SubmitH=Submit_1_1';
			ajaxLoad('POST', URL, data, div_target,'');
			
			Swal.fire({
			  title: "Deleted!",
			  text: "Your file has been deleted.",
			  icon: "success",
		  showConfirmButton: false,
		  
		  timer: 2000 
			}).then(function() {
  
     location.assign("menu_03.php") 
 });
			
			
		  }
		});
	}
	
	function fn_delete2(rsmp_id) { 
		if (confirm('ต้องการลบข้อมูลใช่หรือไม่')) {
			window.location.href="fn_save_center.php?user_id="+rsmp_id+"&SubmitH=Submit_1_1";
		}
	}
	function fn_show_add_new_process(actionH,user_id,process_id,permis_id,div_target) {

			var URL = "user_permission_process_edit_fn.php" ;
			var data = '&user_id='+user_id+'&div_target='+div_target;
			ajaxLoad('post', URL, data, div_target,'');
		
	}
	
	function fn_update_process() {
		document.frm_update_data.SubmitH.value="update";
		document.frm_update_data.submit();
	}
</script>

<iframe id="iFm_save_target" name="iFm_save_target" src="" style="width:0px;height:0px;border:0"></iframe>
	<form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="#">
		 <input name="SubmitH" type="hidden" id="SubmitH" value="" />
		 <input name="Action" type="hidden" id="Action" value="add" /> 
<div class="modal fade" id="staticBackdrop" style="width:100%;"  data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog" role="document" >
    <div class="modal-content" style="width:700px; margin-left:-200px;">
      <div class="modal-header" >
        <h5 class="modal-title" id="staticBackdropLabel"><strong>กำหนดสิทธิ์ผู้ใช้งาน</strong> </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
       <div id="div_target">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
        <button type="button" class="btn btn-primary" onclick="fn_update_process()" >บันทึกข้อมูล</button>
      </div>
    </div>
  </div>
</div>

</form>