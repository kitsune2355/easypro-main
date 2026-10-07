<?php 
define("SB_M1","data_12");
include "head.php"; 

$sn_shift 		= $_GET['sn_shift'];
$sn_id 		    = $_GET['sn_id']; 
$sn_agency 		= $_GET['sn_agency'];
$status 		= $_GET['status'];
 
	$sql="SELECT tbsn.*		
		  FROM tb_security_name as tbsn
		  WHERE tbsn.sn_id = '".$sn_id."'
		  LIMIT 0 , 1
		  "; 
	$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
	$num_rows =mysqli_num_rows($query);
	if($num_rows>=1){
		$rs=mysqli_fetch_array($query);
		$readonly1 = ' readonly';
		$text = 'แก้ไขข้อมูลพนักงาน';
	}else{
		$text = 'เพิ่มรายชื่อพนักงาน';
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
              <li class="breadcrumb-item"><a href="menu_012.php">รายชื่อพนักงานรปภ.</a></li>
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
	<input name="SubmitH" type="hidden" id="SubmitH" value="Submit_17" />  
	<input name="status" type="hidden" id="status" value="<?php echo $status ?>" /> 
	<input name="sn_id" type="hidden" id="sn_id" value="<?php echo $rs['sn_id'] ?>" /> 

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
                    <div class="row">
                    <div class="col-sm-2">
				  <div class="form-group">
                  <label for="exampleSelectBorder">ตำแหน่ง <code>*</code></label>
                  <select name="sn_prefix_to" id="sn_prefix_to" class="custom-select form-control-border icon-menu" id="exampleSelectBorder" required>
                    <option value="">เลือกตำแหน่ง</option>
                    <option value="หัวหน้าชุด" <?php if($rs['sn_prefix_to']=='หัวหน้าชุด'){?> selected="selected"<?php } ?>>หัวหน้าชุด</strong></option>
                    <option value="รปภ." <?php if($rs['sn_prefix_to']=='รปภ.'){?> selected="selected"<?php } ?>>รปภ.</option> 
                  </select>
                </div>
				</div>
                    <div class="col-sm-2">
				  <div class="form-group">
                  <label for="exampleSelectBorder">คำนำหน้าชื่อ <code>*</code></label>
                  <select name="sn_prefix" id="sn_prefix" class="custom-select form-control-border icon-menu" id="exampleSelectBorder" required>
                    <option value="">เลือกคำนำหน้า</option>
                    <option value="นาย" <?php if($rs['sn_prefix']=='นาย'){?> selected="selected"<?php } ?>>นาย</strong></option>
                    <option value="นาง" <?php if($rs['sn_prefix']=='นาง'){?> selected="selected"<?php } ?>>นาง</option>
                    <option value="นางสาว" <?php if($rs['sn_prefix']=='นางสาว'){?> selected="selected"<?php } ?>>นางสาว</option>
                  </select>
                </div></div>               
                    <div class="col-sm-4">
                    <div class="form-group">
                <label for="exampleInputBorder">ชื่อจริง<code>*</code></label>
                  <input  type="text" name="sn_name" class="form-control form-control-border" value="<?php echo $rs['sn_name']?>" id="exampleInputBorder" placeholder="ชื่อจริง" <?php if($status=='1'){?>  <?php } ?> required>
                  </div></div>

                  <div class="col-sm-4"> 
                <div class="form-group">
                  <label for="exampleInputBorder">นามสกุล<code>*</code></label>
                  <input  type="text" name="sn_lastname" class="form-control form-control-border" value="<?php echo $rs['sn_lastname']?>" id="exampleInputBorder" placeholder="นามสกุล" <?php if($status=='1'){?>  <?php } ?> required>
                </div></div>	
                </div>	  
               <!--   <div class="row">
                    <div class="col-sm-6">
                    <div class="form-group">
					  <label for="exampleInputBorder">ละติจูด <code>*</code></label>
					  <input type="text" name="ag_longitude"  class="form-control form-control-border" value="<?php echo $rs['ag_username']?>" id="ag_longitude" placeholder="ละติจูด" >
					</div>
                    </div>
                    <div class="col-sm-6">
                      <div class="form-group">
					  <label for="exampleInputBorder">ลองจิจูด<code>*</code></label>
					  <input type="text" name="ag_latitude"  class="form-control form-control-border" value="<?php echo $rs['ag_op']?>" id="ag_latitude" placeholder="ลองจิจูด" >
					</div>
                  </div>
                  </div> --> 
                  <div class="row">
                  <div class="col-sm-6"> 
                <div class="form-group">
                <label for="exampleInputBorder">หน่วยงาน<code>*</code></label>
                   <select id="sn_agency" name="sn_agency"  class="form-control select2" onchange="fn_add_item_1()" style="width:100%" >
                <option value="">เลือกหน่วยงาน</option> 
                <?php
                $i=1;
                $sql="SELECT *
                FROM   tb_agency
                ";  
                    echo $sql;
                $query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
                $num_rows =mysqli_num_rows($query);
                if($num_rows>=1){
                  while($rs_sb=mysqli_fetch_array($query)) {
                ?>
                <option value="<?php echo $rs_sb['ag_id']; ?>"<?php if($rs['sn_agency']==$rs_sb['ag_id']){?> selected="selected" <?php } ?>><?php echo $rs_sb['ag_contract']; ?> </option>								 
                <?php 
                  }					
                }
                ?>
              </select></div> </div> 

                    <div class="col-sm-6">
				  <div class="form-group">
                  <label for="exampleSelectBorder">กะที่เข้าเวร <code>*</code></label>
                  <select name="sn_shift" id="sn_shift" class="custom-select form-control-border icon-menu" id="exampleSelectBorder" required>
                    <option value="">เลือกกะ</option>
                    <option value="กะเช้า" <?php if($rs['sn_shift']=='กะเช้า'){?> selected="selected"<?php } ?>>กะเช้า</strong></option>
                    <option value="กะดึก" <?php if($rs['sn_shift']=='กะดึก'){?> selected="selected"<?php } ?>>กะดึก</option>
                  </select>
                </div></div>
                  
              


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