<?php 

define("SB_M1","data_index");
include "head.php"; 
 
?>
 <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="plugins/sweetalert2/sweetalert2.min.css">
  <!-- Toastr -->
  <link rel="stylesheet" href="plugins/toastr/toastr.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
 <style>
				   
.button {
  animation: wiggle 2s linear infinite;
}

/* Keyframes */
@keyframes wiggle {
  0%, 7% {
    transform: rotateZ(0);
  }
  15% {
    transform: rotateZ(-15deg);
  }
  20% {
    transform: rotateZ(10deg);
  }
  25% {
    transform: rotateZ(-10deg);
  }
  30% {
    transform: rotateZ(6deg);
  }
  35% {
    transform: rotateZ(-4deg);
  }
  40%, 100% {
    transform: rotateZ(0);
  }
}

body {
  background: #000;
}

.button {
  position: absolute;
  background-color:#FFFFFF;
  margin-left: 5px;
  
  
  transform-origin: 50% 5em; 
}
				  </style>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1><i class="nav-icon fas fa-home"></i> <strong>Home</strong></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">หน้าหลัก</a></li><!--
              <li class="breadcrumb-item active">DataTables</li>-->
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
		<div class="col-12 col-sm-12">
            <div class="card card-primary card-tabs">
              <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="custom-tabs-two-tab" role="tablist">
                  <li class="pt-2 px-3"><h3 class="card-title"><strong>Main Home</strong></h3></li>
                  <li class="nav-item" style=" <?php echo $MenuAdmin3; ?> ">
                    <a class="nav-link active" id="custom-tabs-two-home-tab" data-toggle="pill" href="#custom-tabs-two-home" role="tab" aria-controls="custom-tabs-two-home" aria-selected="true"><strong>จองงานใหม่</strong> <?php echo $MenuAdmin4; ?></a> 
                  </li>  
                  <li class="nav-item" style=" <?php echo $MenuAdmin1; ?> ">
                    <a class="nav-link " id="custom-tabs-two-profile-tab" data-toggle="pill" href="#custom-tabs-two-profile" role="tab" aria-controls="custom-tabs-two-profile" aria-selected="false"><strong>รายชื่อติวเตอร์ใหม่</strong> <?php echo $MenuAdmin2; ?></a>
                  </li> 
                  </li>
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content" id="custom-tabs-two-tabContent">
                  <div class="tab-pane fade active show" id="custom-tabs-two-home" role="tabpanel" aria-labelledby="custom-tabs-two-home-tab">
                     
              <!-- /.card --> 
            </div>
          </div>
        </div>
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
 
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
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
      "buttons": ["copy",  "excel",    "print", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
     
	
   $("#example3").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": true,
      "buttons": ["copy",  "excel",    "print", "colvis"]
    }).buttons().container().appendTo('#example3_wrapper .col-md-6:eq(0)');
   
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
	function fn_gotourl2(mLink) {  
		var tmpurl = mLink+'&emp=';
		var windowFeatures = "scrollbars=yes,top=0,left=50,resizable=yes,width="+(screen.width-550)+",height="+(screen.height-300); 
		MyWindow=window.open(tmpurl,'MyWindow2',windowFeatures);	
	}
	function fn_gotourl(mLink) { 
	
		var tmpurl = mLink+'&emp=';
		var windowFeatures = "scrollbars=yes,top=0,left=50,resizable=yes,width="+(screen.width-550)+",height="+(screen.height-300); 
		MyWindow=window.open(tmpurl,'MyWindow2',windowFeatures);	
	}
	function fn_check_mb(member_id,ss_status) { 
		if (confirm('ยืนยันอนุมัติ ใช่หรือไม่ ')) {
			window.location.href="fn_save_center.php?member_id="+member_id+"&member_status="+ss_status+"&SubmitH=Submit_7";
		}
	}
	function fn_check_mb2(member_id,ss_status) { 
		if (confirm('ยืนยันไม่อนุมัติ ใช่หรือไม่ ')) {
			window.location.href="fn_save_center.php?member_id="+member_id+"&member_status="+ss_status+"&SubmitH=Submit_7";
		}
	}
	function fn_delete(rsmp_id) { 
		if (confirm('ต้องการลบข้อมูลใช่หรือไม่')) {
			window.location.href="fn_save_center.php?user_id="+rsmp_id+"&SubmitH=Submit_1_1";
		}
	}
	function fn_check_ss(sj_id,ss_id,ss_status) {  
		if (confirm('ยืนยันอนุมัติ ใช่หรือไม่')) {
			window.location.href="fn_save_center.php?sj_id="+sj_id+"&ss_status="+ss_status+"&ss_id="+ss_id+"&SubmitH=Submit_6"; 
		}
	}
	function fn_check_ss2(sj_id,ss_id,ss_status) { 
		if (confirm('ยืนยันไม่อนุมัติ ใช่หรือไม่')) {
			window.location.href="fn_save_center.php?sj_id="+sj_id+"&ss_status="+ss_status+"&ss_id="+ss_id+"&SubmitH=Submit_6"; 
		}
	}
	
	
 </script>