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
	$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
	$num_rows =mysqli_num_rows($query);
	if($num_rows>=1){
		$rs=mysqli_fetch_array($query);
		$readonly1 = ' readonly';
		$text = 'แก้ไขรหัสผ่าน';
	}else{
		$text = 'เพิ่มข้อมูลผู้ใช้งานระบบ';
	}

?>
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
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
	<iframe id="iFm_save_target" name="iFm_save_target" src="" style="width:0px;height:0px;border:0"></iframe>
	<form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target">
	<input name="SubmitH" type="hidden" id="SubmitH" value="Submit_1" />  
	<input name="status" type="hidden" id="status" value="3" /> 

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
                  <label for="exampleInputBorder">ชื่อผู้ใช้งาน<code>*</code></label>
                  <input  type="text" name="user_id" class="form-control form-control-border" value="<?php echo $rs['user_id']?>" id="exampleInputBorder" placeholder="ชื่อผู้ใช้งาน" <?php if($status=='1'){?> readonly <?php } ?> required readonly>
                </div> 
                <div class="form-group">
                  <label for="exampleInputBorder">รหัสผ่านใหม่<code>*</code></label>
                  <input type="text" name="user_password" class="form-control form-control-border" value="" id="exampleInputBorder" placeholder="รหัสผ่านใหม่" required>
                </div> 
                <div class="form-group">
                  <label for="exampleInputBorder">ยืนยันรหัสผ่านใหม่<code>*</code></label>
                  <input type="text" name="user_password" class="form-control form-control-border" value="" id="exampleInputBorder" placeholder="ยืนยันรหัสผ่านใหม่" required>
                </div> 
				<!--<div class="row">
                    <div class="col-sm-6">
                    <div class="form-group">
					  <label for="exampleInputBorder">ชื่อ<code>*</code></label>
					  <input type="text" name="user_name"  class="form-control form-control-border" value="<?php echo $rs['user_name']?>" id="exampleInputBorder" placeholder="ชื่อ" required>
					</div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
					  <label for="exampleInputBorder">สกุล<code>*</code></label>
					  <input type="text" name="user_fname"  class="form-control form-control-border" value="<?php echo $rs['user_fname']?>" id="exampleInputBorder" placeholder="สกุล" required>
					</div>
                  </div>
                 
                  </div>
				  	<div class="row">
                    <div class="col-sm-6">
				  <div class="form-group">
                  <label for="exampleSelectBorder">สถานะ <code>*</code></label>
                  <select name="user_status_login" id="user_status_login" class="custom-select form-control-border icon-menu" id="exampleSelectBorder" required>
                    <option value="">เลือกสถานะ</option>
                    <option value="1" <?php if($rs['user_status_login']=='1'){?> selected="selected"<?php } ?>>เปิดการใช้งาน</strong></option>
                    <option value="0" <?php if($rs['user_status_login']=='4'){?> selected="selected"<?php } ?>>ปิดการใช้งาน</option>
                  </select>
                </div>
                  </div> 
                  </div> -->
				  <div style="float:left">
				  <button type="submit" class="btn btn-success " style="text-align:left; margin-top:10px; margin-left:10px;">
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