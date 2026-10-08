<?php 
define("SB_M1","data_1");
include "head.php"; 

$status 		= $_GET['status'];
$rpg_id 		= $_GET['rpg_id']; 
 
	$sql="SELECT tbUs.*		
		  FROM tb_repair_product as tbUs
		  WHERE tbUs.rpd_id = '".$rpg_id."'
		  LIMIT 0 , 1
		  "; 
	//  echo $sql;
	$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
	$num_rows =mysqli_num_rows($query);
	if($num_rows>=1){
		$rs=mysqli_fetch_array($query);
		$readonly1 = ' readonly';
		$text = 'แก้ไขข้อมูลสินค้า';
	}else{
		$text = 'รายการข้อมูลสินค้า';
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
  
     location.assign("menu_23.php") 
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
              <li class="breadcrumb-item"><a href="menu_15.php">รายการข้อมูลสินค้า</a></li>
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
	<input name="SubmitH" type="hidden" id="SubmitH" value="Submit_30" />  
	<input name="status" type="hidden" id="status" value="<?php echo $status ?>" /> 
	<input name="GroupId" type="hidden" id="GroupId" value="<?php echo $rpg_id ?>" /> 

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
				  <div class="row"> 
					  <div class="col-sm-4">
					  <label for="exampleInputBorder">ประเภทระบบ<code>*</code></label>
						<div class="form-group">
						  <select  name="rp_rps_id" id="rp_rps_id" class="form-control select2" style="width: 100%;" required>
							<option value=""><strong>เลือกประเภทระบบ</strong></option>
							<?php
								  $sql="SELECT *
										FROM tb_repair_system 
										WHERE rps_status = '0'
								  "; 
								  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
								  $num_rows =mysqli_num_rows($query);
								  if ($num_rows>=1){ 
									while($rstmp=mysqli_fetch_array($query)) {
									  ?>
								<option value="<?php echo $rstmp['rps_id'] ?>" <?php if($rstmp['rps_id']==$rs['rp_rps_id']){?>selected="selected"<?php } ?>><?php echo $rstmp['rps_name']; ?></option>
							<?php 
									}
								  }
									  ?>
						  </select>
						</div>
					  </div>
					</div>
                <div class="form-group">
                  <label for="exampleInputBorder">ชื่ออะไหล่ / วัสดุสิ้นเปลือง<code>*</code></label>
                  <input  type="text" name="rpd_details_head" class="form-control form-control-border" value="<?php echo $rs['rpd_details_head']?>" id="exampleInputBorder" placeholder="ประเภทช่องทางการรับแจ้งงาน"  required>
                </div>  
                <div class="form-group">
                  <label for="exampleInputBorder">รายละเอียด<code>*</code></label>
                  <input  type="text" name="rpd_details" class="form-control form-control-border" value="<?php echo $rs['rpd_details']?>" id="exampleInputBorder" placeholder="ประเภทช่องทางการรับแจ้งงาน"  required>
                </div>  
                <div class="form-group">
                  <label for="exampleInputBorder">ยี่ห้อ / รุ่น / ชนิด<code>*</code></label>
                  <input  type="text" name="rpd_brand" class="form-control form-control-border" value="<?php echo $rs['rpd_brand']?>" id="exampleInputBorder" placeholder="ประเภทช่องทางการรับแจ้งงาน"  required>
                </div>  
                <div class="form-group">
                  <label for="exampleInputBorder">ราคา / หน่วย<code>*</code></label>
                  <input  type="text" name="rpd_price" class="form-control form-control-border" value="<?php echo $rs['rpd_price']?>" id="exampleInputBorder" placeholder="ประเภทช่องทางการรับแจ้งงาน"  required>
                </div>   
				<div class="form-group">
                  <label for="exampleInputBorder">สถานะ<code>*</code></label>
                  
                  <select name="rpd_satatus" id="rpd_satatus" class="custom-select form-control-border icon-menu" required="">
                    <option value="">เลือกสถานะ</option>
                    <option value="0" <?php if($rs['rpd_satatus']==''||$rs['rpd_satatus']=='0'){?>selected="selected"<?php } ?>>ใช้งาน</option>
                    <option value="1" <?php if($rs['rpd_satatus']=='1'){?>selected="selected"<?php } ?>>ยกเลิก</option>
                  </select>
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