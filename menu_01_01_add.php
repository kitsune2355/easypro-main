<?php 
define("SB_M1","data_1");
include "head.php"; 

$status 		= $_GET['status'];
$user_id 		= $_GET['user_id']; 
 
	$sql="SELECT tbUs.*		
		  FROM tb_user as tbUs
		  WHERE tbUs.user_id = '".$user_id."'
		  LIMIT 0 , 1
		  "; 
		 // echo $sql;
	$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
	$num_rows =mysqli_num_rows($query);
	if($num_rows>=1){
		$rs=mysqli_fetch_array($query);
		$readonly1 = ' readonly';
		$text = 'แก้ไขข้อมูลผู้ใช้งานระบบ';
	}else{
		$text = 'เพิ่มข้อมูลผู้ใช้งานระบบ';
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
  
     location.assign("menu_01.php") 
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
            <li class="breadcrumb-item"><a href="menu_01.php">รายการผู้ใช้งานระบบ</a></li>
            <li class="breadcrumb-item active"><?php echo $text; ?></li>
          </ol>
        </div>
      </div>
    </div>
    <!-- /.container-fluid -->
  </section>
  <!-- Main content -->
  <iframe id="iFm_save_target" name="iFm_save_target" src="" style="width:0px;height:0px;border:0"></iframe>
  <form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target" onsubmit="myFunction()">
    <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_1" />
    <input name="status" type="hidden" id="status" value="<?php echo $status ?>" />
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
        <label for="exampleInputBorder">ชื่อเข้าใช้งาน<code>*</code></label>
        <input  type="text" name="user_id" class="form-control form-control-border" value="<?php echo $rs['user_id']?>" id="exampleInputBorder" placeholder="ชื่อเข้าใช้งาน" <?php if($status=='1'){?> readonly <?php } ?> required>
      </div>
      <?php if($status!='1'){?>
      <div class="form-group">
        <label for="exampleInputBorder">รหัสผ่าน<code>*</code></label>
        <input type="text" name="user_password" class="form-control form-control-border" value="<?php echo $rs['user_id']?>" id="exampleInputBorder" placeholder="รหัสผ่าน" required>
      </div>
      <?php } ?>
      <div class="row">
        <div class="col-sm-6">
          <div class="form-group">
            <label for="exampleInputBorder">ชื่อ<code>*</code></label>
            <input type="text" name="user_name"  class="form-control form-control-border" value="<?php echo $rs['user_name']?>" id="exampleInputBorder" placeholder="ชื่อ" required>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="form-group">
            <label for="exampleInputBorder">สกุล</label>
            <input type="text" name="user_fname"  class="form-control form-control-border" value="<?php echo $rs['user_fname']?>" id="exampleInputBorder" placeholder="สกุล" >
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-6">
          <div class="form-group">
            <label for="exampleSelectBorder">ตำแหน่ง <code>*</code></label>
            <select  name="dep_id" id="dep_id" class="form-control select2" style="width: 100%;" required>
              <option value=""><strong>เลือก</strong></option>
				 <?php
					$sql="SELECT *
					FROM  tb_department2
					where dep_status = '0'
					"; 
				  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
				  $num_rows =mysqli_num_rows($query);
				  if ($num_rows>=1){ 
					while($rstmp=mysqli_fetch_array($query)) {
					  ?>
              <option value="<?php echo $rstmp['dep_id'] ?>" <?php if($rstmp['dep_id']==$rs['user_department']){?>selected="selected"<?php } ?>><strong><?php echo $rstmp['dep_name'] ?></strong></option>
              <?php 
								}
							  }
								  ?>
            </select>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-6">
          <div class="form-group">
            <label for="exampleSelectBorder">ระดับผู้ใช้งาน <code>*</code></label>
            <select name="user_level" id="user_level" class="custom-select form-control-border icon-menu" id="exampleSelectBorder" required>
            <option value="">เลือกสถานะ</option>
            <option value="admin" <?php if($rs['user_level']=='admin'){?> selected="selected"<?php } ?>>ผู้ดูแลระบบ</strong></option>
            <option value="employer" <?php if($rs['user_level']=='employer'){?> selected="selected"<?php } ?>>ผู้ว่าจ้าง</option>
            <option value="general" <?php if($rs['user_level']=='general'){?> selected="selected"<?php } ?>>ผู้ใช้งานทั่วไป</option>
            </select>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="form-group">
            <label for="exampleSelectBorder">สถานะ <code>*</code></label>
            <select name="user_status_login" id="user_status_login" class="custom-select form-control-border icon-menu" id="exampleSelectBorder" required>
            <option value="">เลือกสถานะ</option>
            <option value="1" <?php if($rs['user_status_login']=='1'){?> selected="selected"<?php } ?>>เปิดการใช้งาน</strong></option>
            <option value="0" <?php if($rs['user_status_login']=='0'){?> selected="selected"<?php } ?>>ปิดการใช้งาน</option>
            </select>
          </div>
        </div>
      </div>
      <div style="float:left">
        <button id="btnFetch"  class="btn btn-success " style="text-align:left; margin-top:10px; margin-left:10px;"> <span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span> บันทึกข้อมูล </button>
        <!--<button onclick="fn_cancel_doc()"  class="btn btn-warning"><span class="glyphicon glyphicon-save"></span> ยกเลิก </button>  -->
      </div>
    </div>
  </form>
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
