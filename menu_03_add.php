<?php 
define("SB_M1","data_1");
include "head.php"; 

$status 		= $_GET['status'];
$ass_id 		= $_GET['ass_id']; 
 
	$sql="SELECT *		
		  FROM tb_ass_list
		  WHERE ass_id = '".$ass_id."'
		  LIMIT 0 , 1
		  "; 
		 // echo $sql;
	$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
	$num_rows =mysqli_num_rows($query);
	if($num_rows>=1){
		$rs=mysqli_fetch_array($query);
		$readonly1 = ' readonly';
		$text = 'แก้ไขข้อมูลอุปกรณ์เครื่องจักร';
	}else{
		$text = 'เพิ่มข้อมูลอุปกรณ์เครื่องจักร';
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
  
     location.assign("menu_03.php") 
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
            <li class="breadcrumb-item"><a href="menu_03.php">รายการอุปกรณ์เครื่องจักร</a></li>
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
    <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_3333" /> 
    <input name="ass_id" type="hidden" id="ass_id" value="<?php echo $ass_id ?>" />
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
      <div class="row">
        <div class="col-sm-12">
          <div class="form-group">
            <label for="exampleInputBorder">เลขครุภัณฑ์<code>*</code></label>
            <input  type="text" name="ass_code" class="form-control form-control-border" value="<?php echo $rs['ass_code']?>" id="exampleInputBorder" placeholder="เลขครุภัณฑ์" required>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-12">
          <div class="form-group">
            <label for="exampleInputBorder">ประเภทเครื่องจักร / อุปกรณ์<code>*</code></label>
            <select  name="asset_type" id="asset_type" class="form-control select2" style="width: 100%;" required>
              <option value=""><strong>เลือก</strong></option>
              <?php
							$sql="SELECT *
							FROM  tb_asset_group 
							"; 
						  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
						  $num_rows =mysqli_num_rows($query);
						  if ($num_rows>=1){ 
							while($rstmp=mysqli_fetch_array($query)) {
							  ?>
              <option value="<?php echo $rstmp['GroupId'] ?>" <?php if($rstmp['GroupId']==$rs['asset_type']){?>selected="selected"<?php } ?>><strong><?php echo $rstmp['TGroupName'] ?></strong></option>
              <?php 
							}
						  }
							  ?>
            </select>
          </div>
        </div>
      </div>
	  
      <div class="row">
        <div class="col-sm-12">
          <div class="form-group">
            <label for="exampleInputBorder">ชื่อเครื่องจักร / อุปกรณ์<code>*</code></label>
            <input  type="text" name="asset_name" class="form-control form-control-border" value="<?php echo $rs['asset_name']?>" id="exampleInputBorder" placeholder="ชื่อเครื่องจักร / อุปกรณ์" required>
          </div>
        </div>
        </div> 
	  
      <div class="row">
        <div class="col-sm-12">
          <div class="form-group">
            <label for="exampleInputBorder">ซีเรียล </label>
            <input  type="text" name="asset_sn" class="form-control form-control-border" value="<?php echo $rs['asset_sn']?>" id="exampleInputBorder" placeholder="ซีเรียล">
          </div>
        </div>
        </div> 
      <div class="row">
        <div class="col-sm-6"> 
          <div class="form-group">
            <label for="exampleInputBorder">ยี่ห้อ<code></code></label>
            <input  type="text" name="brn_id" class="form-control form-control-border" value="<?php echo $rs['brn_id']?>" id="exampleInputBorder" placeholder="ยี่ห้อ">
			
          </div>
        </div>
        <div class="col-sm-6"> 
          <div class="form-group">
            <label for="exampleInputBorder">รุ่น<code></code></label>
            <input  type="text" name="asset_model" class="form-control form-control-border" value="<?php echo $rs['asset_model']?>" id="exampleInputBorder" placeholder="รุ่น">
          </div>
        </div>
      </div>
	  <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">อาคาร/สถานที่/แผนก<code>*</code></label>
          <select  name="rp_area_id" id="rp_area_id" class="form-control select2" style="width: 100%;" onChange="ListAmphur(this.value,'cus_old_addr02','cus_old_addr03')" required>
            <option value=""><strong>เลือก</strong></option>
            <?php
							$sql="SELECT *
							FROM tb_area 
							WHERE area_status = '0'
							"; 
						  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
						  $num_rows =mysqli_num_rows($query);
						  if ($num_rows>=1){ 
							while($rstmp=mysqli_fetch_array($query)) {
							  ?>
            <option value="<?php echo $rstmp['area_id'] ?>" <?php if($rstmp['area_id']==$rs['rp_area_id']){?>selected="selected"<?php } ?>><?php echo $rstmp['area_name']; ?></option>
            <?php 
							}
						  }
							  ?>
          </select>
        </div>
      </div>
	  
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">ชั้น<code>*</code></label><?php echo $rs['rp_ac_id']; ?>
          <select  name="rp_ac_id" id="cus_old_addr02" class="form-control select2"  onChange="ListDistrict(this.value,'cus_old_addr03')" style="width: 100%;">
            <option value=""><strong>เลือก</strong></option>
            <?php 
					if($rs['rp_ac_id']!='0'){
							$sql="SELECT *
							FROM tb_area_class 
							WHERE ac_area_id = '".$rs['rp_ac_id']."'
							"; 
							//echo $sql;
						  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
						  $num_rows =mysqli_num_rows($query);
						  if ($num_rows>=1){ 
							while($rstmp=mysqli_fetch_array($query)) {
							  ?>
            <option value="<?php echo $rstmp['ac_id'] ?>" <?php if($rstmp['ac_id']==$rs['rp_ac_id']){?>selected="selected"<?php } ?>><?php echo $rstmp['ac_name']; ?></option>
            <?php 
							}
						  }
					}
							  ?>
          </select>
        </div>
      </div>
	  
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">ห้อง<code>*</code></label>
          <select  name="rp_ar_id" id="cus_old_addr03" class="form-control select2" style="width: 100%;">
            <option value=""><strong>เลือก</strong></option>
            <?php 
					if($rs['rp_ar_id']!='0'){
							$sql="SELECT *
							FROM tb_area_room 
							WHERE ar_area_id = '".$rs['rp_area_id']."'
							AND ar_ac_id = '".$rs['rp_ac_id']."'
							"; 
						  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
						  $num_rows =mysqli_num_rows($query);
						  if ($num_rows>=1){ 
							while($rstmp=mysqli_fetch_array($query)) {
							  ?>
            <option value="<?php echo $rstmp['ar_id'] ?>" <?php if($rstmp['ar_id']==$rs['rp_ar_id']){?>selected="selected"<?php } ?>><?php echo $rstmp['ar_name']; ?></option>
            <?php 
							}
						  }
					}
							  ?>
          </select>
        </div>
      </div>
    </div><!--
      <div class="row">
        <div class="col-sm-12">
          <div class="form-group">
            <label for="exampleInputBorder">สถานที่ตั้งเครื่องจักร/อุปกรณ์<code>*</code></label>
            <input  type="text" name="asset_location" class="form-control form-control-border" value="<?php echo $rs['asset_location']?>" id="exampleInputBorder" placeholder="สถานที่ตั้งเครื่องจักร/อุปกรณ์" required>
          </div>
        </div>
        </div>  --> 
      <div class="row">
        <div class="col-sm-6"> 
          <div class="form-group">
            <label for="exampleInputBorder">วันหมดรับประกัน<code></code></label>
            <input  type="date" name="asset_warranty" class="form-control form-control-border" value="<?php echo $rs['asset_warranty']?>" id="exampleInputBorder" placeholder="ยี่ห้อ">
			
          </div>
        </div>
        <div class="col-sm-6"> 
          <div class="form-group">
            <label for="exampleInputBorder">บริษัทที่รับผิดชอบดูแลอุปปกรณ์<code></code></label>
            <input  type="text" name="asset_company" class="form-control form-control-border" value="<?php echo $rs['asset_company']?>" id="exampleInputBorder" placeholder="รุ่น">
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-sm-12">
          <div class="form-group">
            <label for="exampleInputBorder">ผู้เก็บรักษา<code>*</code></label>
            <input  type="text" name="asset_name" class="form-control form-control-border" value="<?php echo $rs['asset_name']?>" id="exampleInputBorder" placeholder="ผู้เก็บรักษา" required>
          </div>
        </div>
       </div>  
      <div class="row">
        <div class="col-sm-12">
          <div class="form-group">
            <label for="exampleInputBorder">แผนก<code>*</code></label> 
			 <select  name="dep_id" id="dep_id" class="form-control select2" style="width: 100%;" required>
              <option value=""><strong>เลือก</strong></option>
              <?php
							$sql="SELECT *
							FROM  tb_department
							where dep_status = '0'
							"; 
						  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
						  $num_rows =mysqli_num_rows($query);
						  if ($num_rows>=1){ 
							while($rstmp=mysqli_fetch_array($query)) {
							  ?>
              <option value="<?php echo $rstmp['dep_id'] ?>" <?php if($rstmp['dep_id']==$rs['dep_id']){?>selected="selected"<?php } ?>><strong><?php echo $rstmp['dep_name'] ?></strong></option>
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
            <label for="exampleSelectBorder">สถานะ <code>*</code></label>
            <select name="asset_state" id="asset_state" class="custom-select form-control-border icon-menu" required>
            <option value="">เลือกสถานะ</option>
            <option value="0" <?php if($rs['asset_state']=='0'){?> selected="selected"<?php } ?>>ปกติ</strong></option> 
            <option value="1" <?php if($rs['asset_state']=='1'){?> selected="selected"<?php } ?>>อยู่ระหว่างการซ่อม</option> 
            <option value="2" <?php if($rs['asset_state']=='2'){?> selected="selected"<?php } ?>>ยกเลิกการใช้งาน</option>
            </select>
          </div>
        </div>
      </div>
      <div style="float:left">
        <button id="btnFetch"  class="btn btn-success " style="text-align:left; margin-top:10px; margin-left:10px;"> <span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span> บันทึกข้อมูล </button>
        <!--<button onclick="fn_cancel_doc()"  class="btn btn-warning"><span class="glyphicon glyphicon-save"></span> ยกเลิก </button>  -->
      </div>
    </div>
	
	
	
	
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
<script src="plugins/select2/js/select2.full.min.js"></script>
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


	function ListAmphur(SelectValue,nameaddr)
	{
		//SelectValue = SelectValue.substr(0,2);
		var URL = "get_list3.php?SelectValue=" + SelectValue + "&emp=" ;
		ajaxLoad('get', URL, '', nameaddr,'');
		
		//var URL = "get_list2.php?SelectValue=xx&emp=" ;
//		ajaxLoad('get', URL, '', nameaddr2,'');
	}
function ListDistrict(SelectValue,nameaddr)
	{
		//SelectValue = SelectValue.substr(0,4);
		var URL = "get_list4.php?SelectValue=" + SelectValue + "&emp=" ;
		ajaxLoad('get', URL, '', nameaddr,'');
	} 
	function ListAmphur2(SelectValue,nameaddr)
	{
		//SelectValue = SelectValue.substr(0,2);
		var URL = "get_list5.php?SelectValue=" + SelectValue + "&emp=" ;
		ajaxLoad('get', URL, '', nameaddr,'');
		
		//var URL = "get_list2.php?SelectValue=xx&emp=" ;
//		ajaxLoad('get', URL, '', nameaddr2,'');
	}

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
