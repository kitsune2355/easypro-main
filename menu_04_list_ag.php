<?php 
define("SB_M1","data_4");
define("SB_M2","data_2_1");
define("SB_M3","data_2_1_1");
include "head.php"; 

?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h3><strong>ข้อมูลบัตร/จุดตรวจ</strong></h3>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item active"><strong>ข้อมูลบัตร/จุดตรวจ</strong></li>
              <li class="breadcrumb-item"><a href="menu_02_02_add.php">เพิ่มหน่วยงานใหม่<a></li>
			   
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
                <h3 class="card-title"><strong>รายชื่อหน่วยงาน</strong></h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>ชื่อหน่วยงาน</th>
                    <th>ชื่องาน</th>
                    <th>รายละเอียดหน่วยงาน</th> 
                    <th>พื้นที่ปฏิบัติงาน</th>
                    <th>ดำเนินการ</th>
                  </tr>
                  </thead>
                  <tbody>
				  <?php
				  $sql_h="SELECT *  
					 	FROM tb_agency  
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
                    <td onclick="fn_gotourl('menu_04.php?ag_id=<?php echo $rs['ag_id']?>&img_icon=glyphicon-inbox')">
						<?php echo $rs['ag_contract']?>
					</td>
                    <td onclick="fn_gotourl('menu_04.php?ag_id=<?php echo $rs['ag_id']?>&img_icon=glyphicon-inbox')">
						<?php echo $rs['ag_job']?>
					</td>
                    <td onclick="fn_gotourl('menu_04.php?ag_id=<?php echo $rs['ag_id']?>&img_icon=glyphicon-inbox')">
					<?php echo $rs['ag_details']?>
					</td> 
                    <td onclick="fn_gotourl('menu_04.php?ag_id=<?php echo $rs['ag_id']?>&img_icon=glyphicon-inbox')">
					<?php echo $rs['ag_area']?>
					</td> 
                    <td> 
					<a href="menu_04.php?ag_id=<?php echo $rs['ag_id']?>"  role="button" data-toggle="tooltip" data-placement="top" title="เลือก" class="btn btn-xs  btn-info "><i class="fa fa-fw fa-mouse-pointer"></i>เลือก<a>
					 
					</td> 
                  </tr>
			   <?php }
			   
			   }?>
                  </tbody>
                  <tfoot>
                  <tr>
                    <th>ชื่อหน่วยงาน</th>
                    <th>ชื่องาน</th>
                    <th>รายละเอียดหน่วยงาน</th> 
                    <th>พื้นที่ปฏิบัติงาน</th>
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