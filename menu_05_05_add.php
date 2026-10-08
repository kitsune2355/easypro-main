<?php 
define("SB_M1","data_5");
include "head.php"; 

$status 		= $_GET['status'];
$cpb_id 		= $_GET['cpb_id']; 
 
	$sql="SELECT tbUs.*		
		  FROM tb_checkproblem as tbUs
		  WHERE tbUs.cpb_id = '".$cpb_id."'
		  LIMIT 0 , 1
		  "; 

	$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
	$num_rows =mysqli_num_rows($query);
	if($num_rows>=1){
		$rs=mysqli_fetch_array($query);
		$readonly1 = ' readonly';
		$text = 'เพิ่มคำถาม';
	}else{
		$text = 'เพิ่มคำถาม';
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
              <li class="breadcrumb-item"><a href="menu_05.php">รายการคำถาม</a></li>
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
	<input name="SubmitH" type="hidden" id="SubmitH" value="Submit_5" />  
	<input name="status" type="hidden" id="status" value="<?php echo $status ?>" /> 
	<input name="cpb_id" type="hidden" id="cpb_id" value="<?php echo $rs['cpb_id'] ?>" /> 

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"><strong><?php echo $text; ?></strong></h3>
              </div>
              <!-- /.card-header -->  
              
        <div class="row">
              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">เพิ่มคำถามใหม่<code>*</code></label>
                  <input  type="text" name="cpb_nameanswer" class="form-control form-control-border" value="<?php echo $rs['cpb_nameanswer']?>" id="exampleInputBorder" placeholder="เพิ่มคำถามใหม่" <?php if($status=='1'){?>  <?php } ?> required>
                  <div class="col-sm-6">
                  </div> 
                  </div>           
				  <div class="form-group">
                  <label for="exampleSelectBorder">กลุ่มประเภทของคำถาม<code>*</code></label>
                  <select name="cpb_groupanswer" id="cpb_groupanswer" class="custom-select form-control-border icon-menu" id="exampleSelectBorder" required>
                    <option value="">เลือกกลุ่มประเภทของคำถาม</option>
                    <option value="หมู่บ้าน" <?php if($rs['cpb_groupanswer']=='หมู่บ้าน'){?> selected="selected"<?php } ?>>หมู่บ้าน</strong></option>
                    <option value="คอนโด" <?php if($rs['cpb_groupanswer']=='คอนโด'){?> selected="selected"<?php } ?>>คอนโด</option>
                    <option value="ห้างสรรพสินค้า" <?php if($rs['cpb_groupanswer']=='ห้างสรรพสินค้า'){?> selected="selected"<?php } ?>>ห้างสรรพสินค้า</option>
                    <option value="อาคารสำนักงาน" <?php if($rs['cpb_groupanswer']=='อาคารสำนักงาน'){?> selected="selected"<?php } ?>>อาคารสำนักงาน</option>
                    <option value="สถานศึกษา" <?php if($rs['cpb_groupanswer']=='สถานศึกษา'){?> selected="selected"<?php } ?>>สถานศึกษา</option>
                  </select>
                </div>
                  

				  <div style="float:left">
				  <button type="submit" class="btn btn-success " style="text-align:left; margin-top:10px; margin-left:10px;">
						<span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span> บันทึกข้อมูล </button>  
				   <!--<button onclick="fn_cancel_doc()"  class="btn btn-warning"><span class="glyphicon glyphicon-save"></span> ยกเลิก </button>  -->
					</div></div>
				  
				  
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