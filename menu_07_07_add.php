<?php 
define("SB_M1","data_7");
include "head.php"; 

$ag_id			= $_GET['ag_id']; 
$cp_id		  = $_GET['cp_id']; 
$ch_id	  	= $_GET['ch_id']; 
$status	  	= $_GET['status']; 

$i=1;
$sql="SELECT * 
  FROM tb_list_point  as Tblp
  LEFT JOIN tb_agency AS tbAg
  ON Tblp.lp_agency = tbAg.ag_id 
  LEFT JOIN tb_checkpoint AS tbcp 
  ON Tblp.lp_check_point = tbcp.cp_id 
  LEFT JOIN tb_list_check_head AS tbch
  ON Tblp.lp_ch_id = tbch.ch_id 
  WHERE lp_id     = '".$lp_id."'  
  ";

$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
$num_rows =mysqli_num_rows($query);
if($num_rows>=1){
  while($rs=mysqli_fetch_array($query)) {
		$ag_name			= $rs['ag_name']; 
		$class_name			= $rs['class_name']; 
		$room_name			= $rs['room_name']; 
		$ag_id    			= $rs['ag_id']; 
    $lp_check_point    			= $rs['lp_check_point']; 
    $cp_point_number	    			= $rs['cp_point_number']; 
    $cp_point_name    			= $rs['cp_point_name']; 
    $lp_question1    			= $rs['lp_question1']; 
    $lp_question2    			= $rs['lp_question2']; 
    $lp_question3    			= $rs['lp_question3'];
    $lp_answer1    			= $rs['lp_answer1']; 
    $lp_answer2    			= $rs['lp_answer2']; 
    $lp_answer3    			= $rs['lp_answer3'];
    $lp_ins_time    			= $rs['lp_ins_time'];
    $lp_more_details    			= $rs['lp_more_details'];  
    $lp_attachment1    			= $rs['lp_attachment1'];
    $lp_attachment2    			= $rs['lp_attachment2'];
    $lp_attachment3    			= $rs['lp_attachment3'];
    $lp_picture    		    	= $rs['lp_picture'];
    $lp_recorder_name    		    	= $rs['lp_recorder_name'];
  }
}

?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h4><strong><?php echo $text; ?>แก้ไขข้อมูล</strong></h4> 
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li  class="breadcrumb-item active"><strong>แก้ไขข้อมูล<strong></li>
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
	<input name="SubmitH" type="hidden" id="SubmitH" value="Submit_13" />  
	<input name="status" type="hidden" id="status" value="<?php echo $status ?>" /> 
	<input name="lp_id" type="hidden" id="lp_id" value="<?php echo $lp_id ?>" /> 

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"><strong><?php echo $text; ?></strong></h3>
              </div>
              <!-- /.card-header -->  
              <?php echo ''.$rs['ag_id'];?>
              <div class="row">
              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">ตารางเดิน<code>*</code></label> 
              <select id="ch_work_time" name="ch_work_time"  class="form-control select2" onchange="fn_add_item_1()" style="width:100%" >
                <option value="">เลือกหน่วย</option> 
                <?php
                $i=1;
                $sql="SELECT *
                    FROM tb_list_check_head
                    WHERE ch_agency = '".$ag_id."'
                    ";
                    echo $sql;
                $query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
                $num_rows =mysqli_num_rows($query);
                if($num_rows>=1){
                  while($rs_sb=mysqli_fetch_array($query)) {
                ?>
                <option value="<?php echo $rs_sb['lp_id']; ?>"<?php if($rs['lp_id']==$rs_sb['lp_id']){?> selected="selected" <?php } ?>><?php echo $rs_sb['ch_work_time']; ?> </option>								 
                <?php 
                  }					
                }
                ?>
              </select>
              </div></div>

              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">ชื่อจุด<code>*</code></label>
                   <select id="cp_point_name" name="cp_point_name"  class="form-control select2" onchange="fn_add_item_1()" style="width:100%" >
                <option value="">เลือกชื่อจุด</option> 
                <?php
                $i=1;
                $sql="SELECT *
                    FROM tb_checkpoint
                    WHERE cp_ag_id = '".$ag_id."'
                    ";
                    echo $sql;
                $query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
                $num_rows =mysqli_num_rows($query);
                if($num_rows>=1){
                  while($rs_sb=mysqli_fetch_array($query)) {
                ?>
                <option value="<?php echo $rs_sb['cp_id']; ?>"<?php if($lp_check_point==$rs_sb['cp_id']){?> selected="selected" <?php } ?>><?php echo $rs_sb['cp_point_name']; ?> </option>								 
                <?php 
                  }					
                }
                ?>
              </select>
              </div></div></div>

              <div class="row">
              <div class="card-body">    
              <div class="form-group">
                  <label for="exampleSelectBorder">หมายเลขจุด <code>*</code></label>
                  <select name="cp_point_number" id="cp_point_number" class="custom-select form-control-border icon-menu" required="">
                  <option value="">เลือกหมายเลขจุด</option>
                   <option value="0" <?php if($cp_point_number=='0'){?> selected="selected"<?php } ?>>กะเช้า</strong></option>
                   <option value="1" <?php if($cp_point_number=='1'){?> selected="selected"<?php } ?>>กะดึก</strong></option>
                   <option value="2" <?php if($cp_point_number=='2'){?> selected="selected"<?php } ?>>1</strong></option>
                   <option value="3" <?php if($cp_point_number=='3'){?> selected="selected"<?php } ?>>2</option>
                   <option value="4" <?php if($cp_point_number=='4'){?> selected="selected"<?php } ?>>3</strong></option>
                   <option value="5" <?php if($cp_point_number=='5'){?> selected="selected"<?php } ?>>4</option>
                   <option value="6" <?php if($cp_point_number=='6'){?> selected="selected"<?php } ?>>5</strong></option>
                   <option value="7" <?php if($cp_point_number=='7'){?> selected="selected"<?php } ?>>6</option>
                   <option value="8" <?php if($cp_point_number=='8'){?> selected="selected"<?php } ?>>7</strong></option>
                   <option value="9" <?php if($cp_point_number=='9'){?> selected="selected"<?php } ?>>8</option>
                   <option value="10" <?php if($cp_point_number=='10'){?> selected="selected"<?php } ?>>9</strong></option>
                   <option value="11" <?php if($cp_point_number=='11'){?> selected="selected"<?php } ?>>10</option>
                   <option value="12" <?php if($cp_point_number=='12'){?> selected="selected"<?php } ?>>11</strong></option>
                   <option value="13" <?php if($cp_point_number=='13'){?> selected="selected"<?php } ?>>12</option>
                   <option value="14" <?php if($cp_point_number=='14'){?> selected="selected"<?php } ?>>13</strong></option>
                   <option value="15" <?php if($cp_point_number=='15'){?> selected="selected"<?php } ?>>14</option>
                   <option value="16" <?php if($cp_point_number=='16'){?> selected="selected"<?php } ?>>15</strong></option>
                   <option value="17" <?php if($cp_point_number=='17'){?> selected="selected"<?php } ?>>16</option>
                   <option value="18" <?php if($cp_point_number=='18'){?> selected="selected"<?php } ?>>17</strong></option>
                   <option value="19" <?php if($cp_point_number=='19'){?> selected="selected"<?php } ?>>18</option>
                   <option value="20" <?php if($cp_point_number=='20'){?> selected="selected"<?php } ?>>19</strong></option>
                   <option value="21" <?php if($cp_point_number=='21'){?> selected="selected"<?php } ?>>20</option>
                  </select>
                </div></div></div>


                <div class="row">
                <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">คำถามที่ 1<code>*</code></label>
                <input readonly type="text" name="lp_question1" class="form-control form-control-border" value="<?php echo $lp_question1?>" id="exampleInputBorder" placeholder="คำถามที่ 1" <?php if($status=='1'){?>  <?php } ?> required>
              </div></div>

                    
              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">คำถามที่ 2<code>*</code></label>
                <input readonly type="text" name="lp_question2" class="form-control form-control-border" value="<?php echo $lp_question2?>" id="exampleInputBorder" placeholder="คำถามที่ 2" <?php if($status=='1'){?>  <?php } ?> required>
              </div></div>

              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">คำถามที่ 3<code>*</code></label>
                <input readonly type="text" name="lp_question3" class="form-control form-control-border" value="<?php echo $lp_question3?>" id="exampleInputBorder" placeholder="คำถามที่ 3" <?php if($status=='1'){?>  <?php } ?> required>
              </div></div></div>

              <div class="row">
              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">เหตุการณ์ปกติ หรือ ไม่<code>*<?php // echo $lp_attachment1; ?></code></label>
                   <select id="lp_answer1" name="lp_answer1"  class="form-control select2" onchange="fn_add_item_1()" style="width:100%" >
                <option value="">เลือกคำถาม</option> 
                
                <option value="1" <?php if($lp_answer1=='1'){?> selected="selected"<?php } ?>>เหตุการณ์ปกติ</strong></option>
                    <option value="2" <?php if($lp_answer1=='2'){?> selected="selected"<?php } ?>>ไม่ปกติ</option> 
              </select>
              </div></div>

              
              <div class="card-body"> 
                <div class="form-group">
                <label for= "exampleInputBorder">เหตุการณ์ปกติ หรือ ไม่<code>*</code></label>
                   <select  id="lp_answer2" name="lp_answer2"  class="form-control select2" onchange="fn_add_item_1()" style="width:100%" >
                <option value="">เลือกคำถาม</option> 
                <option value="1" <?php if($lp_answer2=='1'){?> selected="selected"<?php } ?>>เหตุการณ์ปกติ</strong></option>
                    <option value="2" <?php if($lp_answer2=='2'){?> selected="selected"<?php } ?>>ไม่ปกติ</option> 
                    <img src="<?php echo $lp_attachment2?>" alt="Girl in a jacket" width="500" height="600">
              </select>
              </div></div>

              
              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">เหตุการณ์ปกติ หรือ ไม่<code>*</code></label>
                   <select id="lp_answer3" name="lp_answer3"  class="form-control select2" onchange="fn_add_item_1()" style="width:100%" >
                <option value="">เลือกคำถาม</option> 
                <option value="1" <?php if($lp_answer3=='1'){?> selected="selected"<?php } ?>>เหตุการณ์ปกติ</strong></option>
                  <option value="2" <?php if($lp_answer3=='2'){?> selected="selected"<?php } ?>>ไม่ปกติ</option> 
                  <img src="<?php echo $lp_attachment3?>" alt="Girl in a jacket" width="500" height="600">
              </select>
              </div></div></div>

              <div class="row">  
              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">รูปภาพเเหตุการณ์ ที่1  : <code>*</code></label>
                </div>
                <a target="_blank" href="<?php echo $lp_attachment1; ?>"><img src="<?php echo $lp_attachment1; ?>" alt="Girl in a jacket" width="200" height="200"></a>
                  </div>  
              

                
              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">รูปภาพเเหตุการณ์ ที่2  : <code>*</code></label>
                </div>
                <a target="_blank" href="<?php echo $lp_attachment2; ?>"><img src="<?php echo $lp_attachment2; ?>" alt="Girl in a jacket" width="200" height="200"></a>
                  </div>
              

                
              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">รูปภาพเเหตุการณ์ ที่3  : <code>*</code></label>
                </div>
                <a target="_blank" href="<?php echo $lp_attachment3; ?>"><img src="<?php echo $lp_attachment3; ?>" alt="Girl in a jacket" width="200" height="200"></a>
                  </div> </div> 
              
              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">หมายเหตุ<code>*</code></label>
                <textarea  type="text" name="lp_more_details" class="form-control" value="" id="exampleInputBorder" placeholder="วันที่" <?php if($status=='1'){?>  <?php } ?> ><?php echo $lp_more_details?></textarea>
              </div></div>
                  
              <div class="row">  
              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">วันที่ และ เวลา<code>*</code></label>
                  <input readonly type="text" name="lp_ins_time" class="form-control select2" value="<?php echo $lp_ins_time?>" id="exampleInputBorder" placeholder="วันที่" <?php if($status=='1'){?>  <?php } ?> required>
                 
                  </div> </div> </div> 
                  
                  

                  <div class="row"> 
              <div class="card-body"> 
                <div class="form-group">
                <label for="exampleInputBorder">ชื่อผู้บันทึก<code>*</code></label>
                  <input  type="text" name="lp_recorder_name" class="form-control form-control-border" value="<?php echo $lp_recorder_name?>" id="exampleInputBorder" placeholder="ชื่อผู้บันทึก" <?php if($status=='1'){?>  <?php } ?> required>
                
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