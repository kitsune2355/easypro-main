<?php 
define("SB_M1","data_1");
include "head.php"; 

$status 		= $_GET['status'];
$GroupId 		= $_GET['GroupId']; 
 
	$sql="SELECT tbUs.*		
		  FROM tb_asset_group as tbUs
		  WHERE tbUs.GroupId = '".$GroupId."'
		  LIMIT 0 , 1
		  "; 
		 // echo $sql;
	$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
	$num_rows =mysqli_num_rows($query);
	if($num_rows>=1){
		$rs=mysqli_fetch_array($query);
		$readonly1 = ' readonly';
		$text = 'แก้ไขข้อมูลประเภทอุปกรณ์เครื่องจักร';
	}else{
		$text = 'รายการประเภทอุปกรณ์เครื่องจักร';
	}

?>
<script src="ajax/ajax_framework.js"> </script>
<script language="javascript">
function fn_show1 () {
   	$('#modal-default').modal('show'); 
 }
function fn_alet() {
 
		Swal.fire({
		  position: "top-end",
		  icon: "success",
		  title: "บันทึกข้อมูลเรียบร้อย",
		  showConfirmButton: false,
		  
		  timer: 2000 
		  
		}).then(function() {
  
     location.assign("menu_02.php") 
 });

		} 
		 
</script>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h4><strong><?php echo $text; ?></strong></h4>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="menu_02.php">รายการประเภทอุปกรณ์เครื่องจักร</a></li>
              <li class="breadcrumb-item active"><?php echo $text; ?></li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
	<iframe id="iFm_save_target" name="iFm_save_target" src="" style="width:0px;height:0px;border:0"></iframe>
	<form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target" onsubmit="myFunction()">
	<input name="SubmitH" type="hidden" id="SubmitH" value="Submit_2" />  
	<input name="status" type="hidden" id="status" value="<?php echo $status ?>" /> 
	<input name="GroupId" type="hidden" id="GroupId" value="<?php echo $GroupId ?>" /> 

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"><strong><?php echo $text; ?></strong></h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body"> 
                <div class="form-group">
                  <label for="exampleInputBorder">ชื่อประเภทอุปกรณ์เครื่องจักร<code>*</code></label>
                  <input  type="text" name="TGroupName" class="form-control form-control-border" value="<?php echo $rs['TGroupName']?>" id="exampleInputBorder" placeholder="ชื่อประเภทอุปกรณ์เครื่องจักร"  required>
                </div>   
				  <div style="float:left">
				  <button id="btnFetch"  class="btn btn-success " style="text-align:left; margin-top:10px; margin-left:10px;">
						<span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span> บันทึกข้อมูล </button>  
				   <!--<button onclick="fn_cancel_doc()"  class="btn btn-warning"><span class="glyphicon glyphicon-save"></span> ยกเลิก </button>  -->
					</div>
				  
				  </div></form>
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
<script> 
function myFunction() {
 $("#btnFetch").prop("disabled", true);
      // add spinner to button
      $("#btnFetch").html(
        `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังบันทึกข้อมูล...`
      ); 
 
	}
function myFunction2() {
	  
//	document.getElementById("btnFetch").click(); 
}
$(document).ready(function() {
	

    $(".btnFetch").click(function() {
		 
      // disable button
//	  document.getElementById("frm_update_data").submit();
      $(this).prop("disabled", true);
      // add spinner to button
      $(this).html(
        `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังบันทึกข้อมูล...`
      );
    });
}); 
</script>