<?php 
define("SB_M1","data_6");
include "head.php"; 

$status 		= $_GET['status'];
$ch_id 		= $_GET['ch_id']; 
 
	$sql="SELECT tbUs.*		
		  FROM tb_list_check_head as tbUs
		  WHERE tbUs.ch_id = '".$ch_id."'
		  LIMIT 0 , 1
		  "; 
      echo $sql;
	$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
	$num_rows =mysqli_num_rows($query);
	if($num_rows>=1){
		$rs=mysqli_fetch_array($query);
		$readonly1 = ' readonly';
		$text = 'เพิ่มรายชื่อ';
	}else{
		$text = 'เพิ่มรายชื่อ';
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
              <li class="breadcrumb-item"><a href="menu_06.php">ตรวจสอบราบชื่อ</a></li>
              <li class="breadcrumb-item active"><?php echo $text; ?></li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
	<iframe id="iFm_save_target" name="iFm_save_target" src="" style="width:800px;height:200px;border:0"></iframe>
	<form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target">
	<input name="SubmitH" type="hidden" id="SubmitH" value="Submit_6" />  
	<input name="status" type="hidden" id="status" value="<?php echo $status ?>" /> 
	<input name="ch_id" type="hidden" id="ch_id" value="<?php echo $rs['ch_id'] ?>" /> 

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
                <label for="exampleInputBorder">หน่วยงาน<code>*</code></label>
                  <input  type="text" name="ch_agency" class="form-control form-control-border" value="<?php echo $rs['ch_agency']?>" id="exampleInputBorder" placeholder="เพิ่มหน่วยงานใหม่" <?php if($status=='1'){?>  <?php } ?> required>
                  </div> </div> </div> 
                  
            <div class="row">
              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">เวลาทำงาน<code>*</code></label>
                  <input  type="text" name="ch_work_time" class="form-control form-control-border" value="<?php echo $rs['ch_work_time']?>" id="exampleInputBorder" placeholder="เพิ่มเวลาทำงานใหม่" <?php if($status=='1'){?>  <?php } ?> required>
                  </div> </div> </div> 
                   
                  

                  
              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">ลำดับ<code>*</code></label>
                  <input  type="text" name="ch_number" class="form-control form-control-border" value="<?php echo $rs['ch_number']?>" id="exampleInputBorder" placeholder="เพิ่มลำดับใหม่" <?php if($status=='1'){?>  <?php } ?> required>
                 
                  </div> 
                  </div> 
                  

                  <div class="row">
              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">ชื่อผู้บันทึก<code>*</code></label>
                  <input  type="text" name="ch_recorder_name" class="form-control form-control-border" value="<?php echo $rs['ch_recorder_name']?>" id="exampleInputBorder" placeholder="เพิ่มชื่อผู้บันทึกใหม่" <?php if($status=='1'){?>  <?php } ?> required>
                
                  </div>
                  </div> 
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