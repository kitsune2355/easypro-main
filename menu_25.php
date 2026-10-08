<?php 
define("SB_M1","data_14");
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
          <h3><strong>รายการบันทึกมิเตอร์ น้ำ / ไฟ</strong></h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item active"><strong>รายการบันทึกมิเตอร์  น้ำ / ไฟ</strong></li>
          </ol>
        </div>
      </div>
    </div>
    <!-- /.container-fluid -->
  </section>
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card card-primary card-outline">
        <div class="card-header">
          <h3 class="card-title"> <i class="fas fa-edit"></i><strong>บันทึกมิเตอร์</strong> </h3>
        </div>
        <div class="card-body" style="background: #F9F9F9"> 
            
          <ul class="nav nav-tabs" id="custom-content-above-tab" role="tablist"> 
            <li class="nav-item"> <a class="nav-link active" id="custom-content-above-profile-tab" data-toggle="pill" href="#custom-content-above-profile" role="tab" aria-controls="custom-content-above-profile" aria-selected="false"><strong>มิเตอร์ TOU</strong></a> </li>
            <li class="nav-item"> <a class="nav-link" id="custom-content-above-messages-tab" data-toggle="pill" href="#custom-content-above-messages" role="tab" aria-controls="custom-content-above-messages" aria-selected="false"><strong>มิเตอร์ MDB
			
			</strong></a> </li>
            <li class="nav-item"> <a class="nav-link" id="custom-content-above-settings-tab" data-toggle="pill" href="#custom-content-above-settings" role="tab" aria-controls="custom-content-above-settings" aria-selected="false"><strong>มิเตอร์ ไฟเมน</strong></a> </li>
            <li class="nav-item"> <a class="nav-link" id="custom-content-above-settings-tab1" data-toggle="pill" href="#custom-content-above-settings1" role="tab" aria-controls="custom-content-above-settings1" aria-selected="false"><strong>มิเตอร์ ไฟ</strong></a> </li>
            <li class="nav-item"> <a class="nav-link" id="custom-content-above-settings-tab2" data-toggle="pill" href="#custom-content-above-settings2" role="tab" aria-controls="custom-content-above-settings2" aria-selected="false"><strong>มิเตอร์ น้ำ</strong></a> </li>
          </ul> 
          <div class="tab-content" id="custom-content-above-tabContent"> 
            <div class="tab-pane fade show active" id="custom-content-above-profile" role="tabpanel" aria-labelledby="custom-content-above-profile-tab"> 
			<div class="row">
			  <?php
			  $sql_h="SELECT *  
					FROM tb_meter  
					WHERE mt_type = 'tou'
					";    
			$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
			$num_rows_h =mysqli_num_rows($query_h); 
			if($num_rows_h>=1){   
				$iCountPg = $start_rec+1;
				while($rs=mysqli_fetch_array($query_h)) {  
			  ?>
			<div class="col-md-3 col-sm-6 col-12"> <a style="color:#000000" href="menu_26_add.php?mt_id=<?php echo $rs['mt_id']?>">
			  <div class="info-box"> <span class="info-box-icon bg-info"><i class="far fa-file"></i></span>
				<div class="info-box-content"> <span class="info-box-text"><strong>บันทึกมิเตอร์ TOU<br />
				  <?php echo $rs['mt_name']?>	</strong></span> 
				 </div>
			  </div>
			</div>
			</a>  
			<?php
				}
			}
			?>
			</div>
			<hr>
			
			</div>
            <div class="tab-pane fade" id="custom-content-above-messages" role="tabpanel" aria-labelledby="custom-content-above-messages-tab">
			 <div class="row">
			 	<?php
			  $sql_h="SELECT *  
					FROM tb_meter  
					WHERE mt_type = 'mdb'
					";    
			$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
			$num_rows_h =mysqli_num_rows($query_h); 
			if($num_rows_h>=1){   
				$iCountPg = $start_rec+1;
				while($rs=mysqli_fetch_array($query_h)) {  
			  ?>
			<div class="col-md-3 col-sm-6 col-12"> <a style="color:#000000" href="menu_28_add.php?toum_id=<?php echo $rs['mt_id']?>">
			  <div class="info-box"> <span class="info-box-icon bg-success"><i class="far fa-file"></i></span>
				<div class="info-box-content"> <span class="info-box-text"><strong>บันทึกมิเตอร์ MDB<br />
				  <?php echo $rs['mt_name']?>	</strong></span> 
				 </div>
			  </div>
			</div>
			</a>  
			<?php
				}
			}
			?>
			</div>
			</div>
            <div class="tab-pane fade" id="custom-content-above-settings" role="tabpanel" aria-labelledby="custom-content-above-settings-tab"> 
			 <div class="row">
			 	<?php
			  $sql_h="SELECT *  
					FROM tb_meter  
					WHERE mt_type = 'etm'
					";    
			$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
			$num_rows_h =mysqli_num_rows($query_h); 
			if($num_rows_h>=1){   
				$iCountPg = $start_rec+1;
				while($rs=mysqli_fetch_array($query_h)) {  
			  ?>
			<div class="col-md-3 col-sm-6 col-12"> <a style="color:#000000" href="menu_30_add.php?toum_id=<?php echo $rs['mt_id']?>">
			  <div class="info-box"> <span class="info-box-icon bg-success"><i class="far fa-file"></i></span>
				<div class="info-box-content"> <span class="info-box-text"><strong>บันทึกมิเตอร์ ไฟฟ้าเมน<br />
				  <?php echo $rs['mt_name']?>	</strong></span> 
				 </div>
			  </div>
			</div>
			</a>  
			<?php
				}
			}
			?>
			</div> </div>
            <div class="tab-pane fade" id="custom-content-above-settings1" role="tabpanel" aria-labelledby="custom-content-above-settings-tab1">
			<div class="row">
			 	<?php
			  $sql_h="SELECT *  
					FROM tb_meter  
					WHERE mt_type = 'et'
					";    
			$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
			$num_rows_h =mysqli_num_rows($query_h); 
			if($num_rows_h>=1){   
				$iCountPg = $start_rec+1;
				while($rs=mysqli_fetch_array($query_h)) {  
			  ?>
			<div class="col-md-3 col-sm-6 col-12"> <a style="color:#000000" href="menu_31_add.php?toum_id=<?php echo $rs['mt_id']?>">
			  <div class="info-box"> <span class="info-box-icon bg-success"><i class="far fa-file"></i></span>
				<div class="info-box-content"> <span class="info-box-text"><strong>บันทึกมิเตอร์ ไฟฟ้า<br />
				  <?php echo $rs['mt_name']?>	</strong></span> 
				 </div>
			  </div>
			</div>
			</a>  
			<?php
				}
			}
			?>
			</div>
			</div>
            <div class="tab-pane fade" id="custom-content-above-settings2" role="tabpanel" aria-labelledby="custom-content-above-settings-tab2"> 
			<div class="row">
			 	<?php
			  $sql_h="SELECT *  
					FROM tb_meter  
					WHERE mt_type = 'wt'
					";    
			$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
			$num_rows_h =mysqli_num_rows($query_h); 
			if($num_rows_h>=1){   
				$iCountPg = $start_rec+1;
				while($rs=mysqli_fetch_array($query_h)) {  
			  ?>
			<div class="col-md-3 col-sm-6 col-12"> <a style="color:#000000" href="menu_32_add.php?toum_id=<?php echo $rs['mt_id']?>">
			  <div class="info-box"> <span class="info-box-icon bg-success"><i class="far fa-file"></i></span>
				<div class="info-box-content"> <span class="info-box-text"><strong>บันทึกมิเตอร์ น้ำ<br />
				  <?php echo $rs['mt_name']?>	</strong></span> 
				 </div>
			  </div>
			</div>
			</a>  
			<?php
				}
			}
			?>
			</div>
			</div>
          </div>
        </div>
      </div>
    
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
