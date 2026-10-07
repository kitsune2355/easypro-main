 
<?php 
define("SB_M1","data_132");
include "head.php"; 

$status 	= $_GET['status'];
$rp_id		= $_GET['rp_id']; 
 
	$sql="SELECT *		
		  FROM tb_repair
		  WHERE rp_id = '".$rp_id."'
		  LIMIT 0 , 1
		  "; 
		 // echo $sql;
	$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
	$num_rows =mysqli_num_rows($query);
	if($num_rows>=1){
		$rs=mysqli_fetch_array($query);
		$readonly1 = ' readonly';
		$text = 'รายละเอียดแจ้งซ่อม';
	}else{
		$text = 'รายละเอียดแจ้งซ่อม';
	}

?>
<script src="ajax/ajax_framework.js"> </script>
<script language="javascript">
function fn_show1 () {
   	$('#modal-default').modal('show'); 
 }
function fn_show25 () {
alert('fdg');
   $('#modal-lg').modal('show'); 
   //$('#modal-lg').modal('show'); 
 }
 
 function fn_show222() { 
		Swal.fire({
		  title: "ต้องการปิดงานใช่หรือไม่", 
		  icon: "warning",
		  showCancelButton: true,
		  cancelButtonText: "ไม่",      
		  confirmButtonColor: "#3085d6",
		  cancelButtonColor: "#d33",
		  confirmButtonText: "ใช่"
		}).then((result) => {
		  if (result.isConfirmed) {
		//	window.location.href="fn_save_center.php?user_id="+rsmp_id+"&SubmitH=Submit_1_1"; 
			 $('#modal-lg').modal('show'); 
			then(function() {
			
	 		 location.assign("menu_02.php") 
		 
 		 });
			
			
		  }
		});
	}
	
	function fn_show2() { 
		Swal.fire({
		  title: "ต้องการปิดงานใช่หรือไม่", 
		  icon: "warning",
		  showCancelButton: true,
		  cancelButtonText: "ไม่",      
		  confirmButtonColor: "#3085d6",
		  cancelButtonColor: "#d33",
		  confirmButtonText: "ใช่"
		}).then((result) => {
		  if (result.isConfirmed) {
			
			 $('#modal-lg').modal('show'); 
			
			
 	  		}else{
			
			fn_alet();
			}
		});
	}
	
 	function fn_show233() { 
		Swal.fire({
		  title: "ต้องการปิดงานใช่หรือไม่", 
		  icon: "warning",
		  showCancelButton: true,
		  cancelButtonText: "ไม่",      
		  confirmButtonColor: "#3085d6",
		  cancelButtonColor: "#d33",
		  confirmButtonText: "ใช่"
		}).then((result) => {
		  if (result.isConfirmed) {
		//	window.location.href="fn_save_center.php?user_id="+rsmp_id+"&SubmitH=Submit_1_1";
			
			
   $('#modal-lg').modal('show'); 
			 
			
		  }
		});
	}
	
 
function fn_alet() {
 
		Swal.fire({
		  position: "top-end",
		  icon: "success",
		  title: "บันทึกข้อมูลเรียบร้อย",
		  showConfirmButton: false,
		  
		  timer: 2000 
		  
		}).then(function() {
  
     location.assign("menu_14.php") 
 });

		} 
	

	
</script>

<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6" >
          <h4><strong><?php echo $text; ?></strong></h4>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="menu_03.php">รายการแจ้งซ่อม</a></li>
            <li class="breadcrumb-item active"><?php echo $text; ?></li>
          </ol>
        </div>
      </div>
    </div>
    <!-- /.container-fluid -->
  </section>
  <!-- Main content -->
  <section class="content">
  <iframe id="iFm_save_target" name="iFm_save_target" src="" style="width0px;height:0px;border:0"></iframe>
  <form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target" onsubmit="myFunction()">
    <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_20" />
    <input name="rp_id" type="hidden" id="rp_id" value="<?php echo $rp_id ?>" />
    <input name="status" type="hidden" id="status" value="<?php echo $status ?>" />
    <div class="container-fluid" style="margin-top:-40px;">
    <div class="row">
    <div class="col-12">
    <div class="card card-primary">
    <div class="card-header">
      <h3 class="card-title"><strong><?php echo $text; ?></strong> 
	  
	  <?php if($rs['rp_status']=='1'){ ?>  >
	    <?php if($sess_user_level=='admin'||$sess_user_level=='general'){ ?>
    <button type="button" class="btn btn-danger" style="text-align:left; margin-left:10px;" id="turn_off" data-toggle="modal" data-target="#modal-lg">ปิดงาน</button>
	<?php } ?>
    <?php }elseif(($rs['rp_status']=='1')||($rs['rp_status']=='3')){ ?><a href="print.php?rp_id=<?php echo $rs['rp_id']?>" target="_blank" class="btn btn-success  btn-xs " style="text-align:right; "> <li class="fas fa-print" aria-hidden="true"></li> พิมพ์เอกสาร  </a><?php } ?></h3>
    </div>
    <!-- /.card-header -->
    <div class="card-body" style="font-size:14px;">
    <div style="background-color:#F7F7F7; padding:3px;"> <i class="fas fa-book mr-1"></i> <strong>ข้อมูลการแจ้งซ่อม</strong> </div>
    <br />
	 <div class="row"> 
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">วันที่แจ้งซ่อม<code>*</code></label>
          <div class="input-group">
            <div class="input-group-prepend"> <span class="input-group-text"> <i class="far fa-calendar-alt"></i> </span> </div>
            <input  type="date" name="rp_date" class="form-control" value="<?php if($rs['rp_date']!=''){ echo $rs['rp_date']; }else{ echo date('Y-m-d'); }?><?php // echo $rs['rp_time']?>" id="rp_date" placeholder="ชื่อผู้แจ้ง" required>
          </div>
        </div>
      </div>
      <div class="col-sm-4">
        <label for="exampleInputBorder">เวลาแจ้งซ่อม<code></code></label>
        <div class="form-group">
          <input type="time" name="rp_time" class="form-control form-control-border" value="<?php if($rs['rp_time']!=''){ echo $rs['rp_time']; }else{ echo date("H:i"); }?>" id="rp_time" placeholder="รายละเอียด"  >
        </div>
      </div> 
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">วันที่เข้าซ่อม<code></code></label>
          <div class="input-group">
            <div class="input-group-prepend"> <span class="input-group-text"> <i class="far fa-calendar-alt"></i> </span> </div>
            <input  type="date" name="rp_date_active" class="form-control" value="<?php  echo $rs['rp_date_active']?>" id="rp_date" placeholder="ชื่อผู้แจ้ง" >
          </div>
        </div>
      </div>
      </div> 
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">ช่องทางแจ้งงาน<code>*</code></label>
          <select  name="rp_rpcn_id" id="rp_rpcn_id" class="form-control select2" style="width: 100%;" >
            <option value=""><strong>เลือกช่องทางแจ้งงาน</strong></option>
            <?php
							$sql="SELECT *
								FROM tb_channel 
								WHERE rpcn_status = '0'
							"; 
						  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
						  $num_rows =mysqli_num_rows($query);
						  if ($num_rows>=1){ 
							while($rstmp=mysqli_fetch_array($query)) {
							  ?>
            <option value="<?php echo $rstmp['rpcn_id'] ?>" <?php if($rstmp['rpcn_id']==$rs['rp_rpcn_id']){?>selected="selected"<?php } ?>><?php echo $rstmp['rpcn_name']; ?></option>
            <?php 
							}
						  }
							  ?>
          </select>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">ชนิดของการบริการ<code>*</code></label>
          <select  name="rp_rpg_id" id="rp_rpg_id" class="form-control select2" style="width: 100%;" required>
            <option value=""><strong>เลือกชนิดของการบริการ</strong></option>
            <?php
							$sql="SELECT *
								FROM tb_repair_group 
								WHERE rpg_status = '0'
							"; 
						  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
						  $num_rows =mysqli_num_rows($query);
						  if ($num_rows>=1){ 
							while($rstmp=mysqli_fetch_array($query)) {
							  ?>
            <option value="<?php echo $rstmp['rpg_id'] ?>" <?php if($rstmp['rpg_id']==$rs['rp_rpg_id']){?>selected="selected"<?php } ?>><?php echo $rstmp['rpg_name']; ?></option>
            <?php 
							}
						  }
							  ?>
          </select>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">ประเภทงาน<code>*</code></label>
          <select  name="rp_rps_id" id="rp_rps_id" class="form-control select2" style="width: 100%;" onChange="ListAmphur2(this.value,'rpd_details_head_va')" required>
            <option value=""><strong>เลือกประเภทงาน</strong></option>
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
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">ชื่อผู้แจ้ง<code>*</code></label>
          <input  type="text" name="rp_name" class="form-control form-control-border" value="<?php echo $rs['rp_name']?>" id="rp_name" placeholder="ชื่อผู้แจ้ง" required>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">เบอร์โทร</label>
          <input  type="text" name="rp_phone" class="form-control form-control-border" value="<?php echo $rs['rp_phone']?>" id="rp_phone" placeholder="เบอร์โทร" >
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
          <label for="exampleInputBorder">ชั้น<code>*</code></label> <?php echo $rs['rp_ac_id']; ?>
          <select  name="rp_ac_id" id="cus_old_addr02" class="form-control select2"  onChange="ListDistrict(this.value,'cus_old_addr03')" style="width: 100%;" required>
            <option value=""><strong>เลือก</strong></option>
            <?php 
					if($rs['rp_ac_id']!='0'){
							$sql="SELECT *
							FROM tb_area_class  
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
          <label for="exampleInputBorder">ห้อง<code></code></label>
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
    </div>
    <!-- <div style="background-color:#F7F7F7; padding:3px;"> <i class="fas fa-book mr-1"></i> <strong>รายละเอียดงานซ่อม</strong> </div>
              <br />-->
	<!--<div class="row">
      <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">รายการอุปกรณ์เครื่องจักร<code>*</code></label>
		   <select  name="rp_ass_id" id="rp_ass_id" class="form-control select2" style="width: 100%;">
            <option value=""><strong>เลือก</strong></option>
            <?php  
							$sql="SELECT *
							FROM tb_ass_list  
							"; 
						  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
						  $num_rows =mysqli_num_rows($query);
						  if ($num_rows>=1){ 
							while($rstmp=mysqli_fetch_array($query)) {
							  ?>
            <option value="<?php echo $rstmp['ass_id'] ?>" <?php if($rstmp['ass_id']==$rs['rp_ass_id']){?>selected="selected"<?php } ?>><?php echo $rstmp['ass_code']; ?> - <?php echo $rstmp['asset_name']; ?></option>
            <?php 
							}
						  } 
							  ?>
          </select> 
        </div>
      </div>
    </div>-->
    <div class="row">
      <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">รายละเอียดของปัญหา<code>*</code></label>
          <textarea rows="3" id="comment" class="form-control " style="color:#000000; margin-top:10px;" name="rp_subject" placeholder="กรุณากรอกรายละเอียดของงาน" required=""><?php echo $rs['rp_subject']?></textarea>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">แนบรูปภาพประกอบ</code></label>
          <br />
          <?php if($rs['rp_file1']!=""){ ?>
		  <img src="<?php echo $rs['rp_file1'];?>" />
          <a class="glyphicon glyphicon-paperclip" aria-hidden="true" href="<?php echo $rs['rp_file1']; ?>" style="padding:5px; color:#0066CC" target="_blank">แนบไฟล์</a>
          <?php } ?>
          <div class="custom-file">
            <input type="file" name="file1" class="custom-file-input" id="customFile2">
            <label class="custom-file-label" for="customFile2">เลือกไฟล์</label>
          </div>
        </div>
      </div>
    </div> 
	<?php if($sess_user_level=='admin'||$sess_user_level=='general'||$rp_id!=''){ ?>
    <div style="background-color:#F7F7F7; padding:3px;"> <i class="fas fa-book mr-1"></i> <strong>รายละเอียดงานซ่อม</strong> </div>
    <br />
	 <div class="row">
      <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">ผู้ดำเนินการ/ช่าง<code>*</code></label>
          <select  name="rp_user_id_rep" id="rp_user_id_rep" class="form-control select2" style="width: 100%;" required>
            <option value=""><strong>เลือก</strong></option>
            <?php
							$sql="SELECT *
							FROM tb_user
							LEFT JOIN tb_department2 AS TbDpm2
							ON tb_user.user_department = TbDpm2.dep_id
							WHERE user_status_login = '1'
							AND user_department in ('2','5')
							"; 
						  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
						  $num_rows =mysqli_num_rows($query);
						  if ($num_rows>=1){ 
							while($rstmp=mysqli_fetch_array($query)) {
							  ?>
            <option value="<?php echo $rstmp['user_id'] ?>" <?php if($rstmp['user_id']==$rs['rp_user_id_rep']){?>selected="selected"<?php } ?>><strong><?php echo $rstmp['user_name'].' '.$rstmp['user_fname'] ?></strong></option>
            <?php 
							}
						  }
							  ?>
          </select>
        </div>
      </div>
    </div>
	 <!--  <div class="row">
      <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">ปัญหาที่พบ<code>*</code></label>
          <textarea rows="3" id="comment" class="form-control " style="color:#000000; margin-top:10px;" name="rp_subject2" placeholder="กรุณากรอกปัญหาที่พบ" required=""><?php echo $rs['rp_subject2']?></textarea>
        </div>
      </div>
    </div>-->
	 <div class="row">
      <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">รายละเอียดการปฏิบัติงาน<code>*</code></label>
          <textarea rows="3" id="comment" class="form-control " style="color:#000000; margin-top:10px;" name="rp_subject3" placeholder="รายละเอียดการปฏิบัติงาน" ><?php echo $rs['rp_subject3']?></textarea>
        </div>
      </div>
    </div>
	<div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">แนบรูปภาพประกอบ</label>
          <br>
          <?php if($rs['rp_close_file']!=""){ ?>
		  <img src="<?php echo $rs['rp_close_file'];?>" /> 
          <a class="glyphicon glyphicon-paperclip" aria-hidden="true" href="<?php echo $rs['rp_close_file']; ?>" style="padding:5px; color:#0066CC" target="_blank">แนบไฟล์</a>
          <?php } ?>
                     <div class="custom-file">
            <input type="file" name="rp_file_close" class="custom-file-input" id="customFile3">
            <label class="custom-file-label" for="customFile3">เลือกไฟล์</label>
          </div>
        </div>
      </div>
    </div>
    <!--<table>
		     <tr> 
		  <?php
			$sql="SELECT *
			FROM tb_repair_group 
			WHERE rpg_status = '0'
			"; 
		  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
		  $num_rows =mysqli_num_rows($query);
		  if ($num_rows>=1){ 
			while($rstmp=mysqli_fetch_array($query)) {
			  ?>
			  	<td width="10%"> 
				<div class="custom-control custom-radio">
					<input type="radio" name="rpg_id" id="color_option_a1<?php echo $rstmp['rpg_id']?>" value="<?php echo $rstmp['rpg_id']?>" class="custom-control-input" autocomplete="off" >  
					<label for="color_option_a1<?php echo $rstmp['rpg_id']?>" class="custom-control-label"><strong><?php echo $rstmp['rpg_name']?> </strong></label>
					</div> 
			  	</td>
			 <?php } 
			 }?>		 	 
			  </tr>
			 </table><br />-->
			 <!--
			  <div style="background-color:#F7F7F7; padding:3px;"> <i class="fas fa-book mr-1"></i> <strong>งานซ่อม/ค่าบริการเพิ่มเติม</strong> </div>
    <br />
    <table style="margin-top:-10px; "  border="1" id="example22" width="100%">
      <thead>
        <tr style="font-size:16px;">
          <th width="20%" class="text-center"><strong>ชื่ออะไหล่ / วัสดุสิ้นเปลือง</strong></th>
          <th width="20%" class="text-center"><strong>รายละเอียด</strong></th>
          <th width="18%" class="text-center"><span style="font-size:14px;"><strong>ยี่ห้อ / รุ่น / ชนิด</strong></span></th>
          <th width="5%" class="text-center"><span style="font-size:14px;"><strong>จำนวน</strong></span></th>
          <th width="15%" class="text-center"><span style="font-size:14px;"><strong>ราคา / หน่วย</strong></span></th>
          <th width="5%" class="text-center"><span style="font-size:14px;"><strong>รวมเงิน</strong></span></th> 
          <th width="6%" class="text-center">&nbsp;</th>
        </tr>
      </thead>
      <tbody>
	    <?php
			$sql="SELECT *
			FROM tb_repair_detail 
			WHERE rpd_rp_id = '".$rp_id."' 
			";  
		  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
		  $num_rows =mysqli_num_rows($query);
		  if ($num_rows>=1){ 
		  $i=0;
			while($rstmp=mysqli_fetch_array($query)) {
		  $i++;
			  ?>
         <tr style="font-size:16px; border-bottom: solid #CCCCCC 2px; ">
          <th width="20%" class="text-center"> 
		  <strong><?php echo $rstmp['rpd_details_head']?> 
		  <input style="width:100%;" class="form-control" type="hidden"  name="rpd_details_head[]" id="rpd_details_head[]" value="<?php echo $rstmp['rpd_details_head']?>"/>
		  <input style="width:100%;" class="form-control" type="hidden"  name="rpd_details_head_va_id2[]" id="rpd_details_head_va_id2[]" value="<?php echo $rstmp['rpd_details_head_va_id']?>"/></strong>
		  </th>
          <th width="20%" class="text-center"><strong><?php echo $rstmp['rpd_details']?><input style="width:100%;" class="form-control" type="hidden" name="rpd_details[]" id="rpd_details[]" value="<?php echo $rstmp['rpd_details']?>" /></strong></th>
          <th width="18%" class="text-center"><?php echo $rstmp['rpd_brand']?><input style="width:100%;" class="form-control" type="hidden" name="rpd_brand[]" id="rpd_brand[]" value="<?php echo $rstmp['rpd_brand']?>" /></strong></span></th> 
		  <th width="5%" class="text-center"><?php echo $rstmp['rpd_qty']?><input style="width:100%;" class="form-control" type="hidden" name="rpd_qty[]" id="rpd_qty[]" value="<?php echo $rstmp['rpd_qty']?>" /></strong></span></th> 
		  <th width="15%" class="text-center"><?php echo $rstmp['rpd_price']?><input style="width:100%;" class="form-control" type="hidden" name="rpd_price[]" id="rpd_price[]" value="<?php echo $rstmp['rpd_price']?>" /></strong></span></th> 
		  <th width="5%" class="text-center"><?php echo $rstmp['rpd_sum_money']?><input style="width:100%;" class="form-control" type="hidden" name="rpd_sum_money[]" id="rpd_sum_money[]" value="<?php echo $rstmp['rpd_sum_money']?>" /></strong></span></th> 
			   <td align="center">&nbsp;&nbsp;&nbsp;<a href="javascript:fn_del_row_item('<?php echo $i ?>')" style="width:100%;  " class="btn btn-xs btn-danger ">ลบ</a></td></td>
        </tr>
            <?php 
			}
		  }
			  ?>
      </tbody>
    </table>
    <table style="margin-top:30px; border: #CCCCCC" border="0" width="100%">
      <tbody>
        <tr>
          <td width="33%"><strong>ชื่ออะไหล่ / วัสดุสิ้นเปลือง
		  
              <input type="hidden" name="rpd_details_head_va_id" class="form-control" value="<?php echo $rs['asset_name']?>" id="rpd_details_head_va_id" placeholder="รายละเอียด" />
              <input type="hidden" name="rpd_details_head_va_name" class="form-control" value="<?php echo $rs['asset_name']?>" id="rpd_details_head_va_name" placeholder="รายละเอียด" />
		  <select  name="rpd_details_head_va" id="rpd_details_head_va" class="form-control select2" style="width: 100%;" onchange="fn_product_list(this.value)" >
            <option value="0"><strong>เลือก</strong></option> 
            
          </select> 
          </strong></td>
          <td width="28%"><strong>รายละเอียด
              <input  type="text" name="rpd_details_va" class="form-control" value="<?php echo $rs['asset_name']?>" id="rpd_details_va" placeholder="รายละเอียด" readonly />
          </strong></td>
          <td width="39%"><strong>ยี่ห้อ / รุ่น / ชนิด
              <input  type="text" name="rpd_brand_va" class="form-control" value="<?php echo $rs['asset_name']?>" id="rpd_brand_va" placeholder="ยี่ห้อ / รุ่น / ชนิด" readonly />
          </strong></td>
        </tr>
        <tr>
          <td><strong>จำนวน
              <input  type="number" name="rpd_qty_va" class="form-control" onkeyup="fn_sum()" value="<?php echo $rs['asset_name']?>" id="rpd_qty_va" placeholder="จำนวน" />
          </strong></td>
          <td><strong>ราคา / หน่วย
              <input  type="number" name="rpd_price_va" class="form-control" onkeyup="fn_sum()" value="<?php echo $rs['asset_name']?>" id="rpd_price_va" placeholder="ราคา / หน่วย" readonly/>
          </strong></td>
          <td><strong>รวมเงิน
              <input  type="number" name="rpd_sum_money_va" class="form-control" value="<?php echo $rs['asset_name']?>" id="rpd_sum_money_va" placeholder="รวมเงิน" readonly="" />
          </strong></td>
        </tr>
        <tr>
          <td></td>
          <td>
            <a onclick="fn_add_item()" class="btn btn-default btn-xs" style="  padding:10px;">
            <li class="fa fa-plus"></li>
            เพิ่มรายการ </a> </td>
          <td>&nbsp;</td>
        </tr> 
      </tbody>
    </table> -->
   
	<?php } ?>
   <br />
<br />
          <?php if($rs['rp_status']=="3"){ ?>


	  <div class="row">
      <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">ผลการปฏิบัติ<code>*</code></label>
          <select  name="rp_jr" id="rp_jr" class="form-control select2"  onchange="fn_bx(this.value)" required>
            <option value=""><strong>เลือก</strong></option>  
            <option value="1" <?php if($rs['rp_jr']==1){?>selected="selected"<?php } ?>><strong>แล้วเสร็จ</strong></option> 
            <option value="2" <?php if($rs['rp_jr']==2){?>selected="selected"<?php } ?>><strong>ต้องเข้าดำเนินการต่อ</strong></option> 
          </select>
        </div>
      </div> 
    </div>
	  <?php if($rs['rp_jr']==1)  $Text = "display:block;"; else  $Text = "display:none;"; ?>
	<?php if($rs['rp_jr']==2)  $Text2 = "display:block;"; else  $Text2 = "display:none;"; ?>
	
	<div id='b1' style=" <?php echo $Text; ?>  background-color:#C4FFC4; padding:5px;">
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">วันที่แล้วเสร็จ<code>*</code></label>
          <div class="input-group">
            <div class="input-group-prepend"> <span class="input-group-text"> <i class="far fa-calendar-alt"></i> </span> </div>
            <input type="date" name="rp_date_rep" value="<?php echo $rs['rp_date_rep']?>" class="form-control float-right" id="reservation">
          </div>
        </div>
      </div>
      <div class="col-sm-3">
        <label for="exampleInputBorder">เวลาแล้วเสร็จ<code>*</code></label>
        <div class="form-group">
          <input  type="time" name="rp_time_rep" class="form-control form-control-border" value="<?php echo $rs['rp_time_rep']?>" id="exampleInputBorder" placeholder="รายละเอียด" >
        </div>
      </div>
      <div class="col-sm-5">
        <label for="exampleInputBorder">หมายเหตุ<code>*</code></label>
        <div class="form-group">
          <input  type="text" name="rp_note" class="form-control form-control-border" value="<?php echo $rs['rp_note']?>" id="exampleInputBorder" placeholder="หมายเหตุ" >
        </div>
      </div>
    </div>
	</div> 
	<div id='b2'  style=" <?php echo $Text2; ?> background-color: #FFFFB7; padding:5px;">
    <div class="row">
      <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">สาเหตุที่ไม่สามารถดำเนินการแล้วเสร็จ<code>*</code></label>
		  <select  name="rp_c_id" id="rp_c_id" class="form-control " style="width: 100%;">
            <option value=""><strong>เลือก</strong></option>
            <?php  
							$sql="SELECT *
							FROM tb_repair_cause 
							WHERE rpc_status = '0'
							"; 
						  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
						  $num_rows =mysqli_num_rows($query);
						  if ($num_rows>=1){ 
							while($rstmp=mysqli_fetch_array($query)) {
							  ?>
            <option value="<?php echo $rstmp['rpc_id'] ?>" <?php if($rstmp['rpc_id']==$rs['rp_c_id']){?>selected="selected"<?php } ?>><?php echo $rstmp['rpc_name']; ?></option>
            <?php 
							} 
					}
							  ?>
          </select>
        </div>
      </div>  
    </div>
	</div>

    <div class="row">
		 <div class="col-sm-12">
        <div class="form-group">
		
			  <div style="background-color:#F7F7F7; padding:3px;"> <i class="fas fa-book mr-1"></i> <strong>การประเมินผลการให้บริการ<code>*</code></strong> </div> 
<br />

    <!--      <label >1.ความรวดเร็วในการให้บริการ<code>*</code></label>-->
		  
 <div class="group"> 
  <input class="inputtr" type="radio" name="cb" id="cb111" value="4" <?php if($rs['cb']=='4'){?>checked="checked"<?php } ?>  required/>
  <label class="labelTr" for="cb111">ดีมาก</label>
  <input class="inputtr"  type="radio" name="cb" id="cb212" value="3" <?php if($rs['cb']=='3'){?>checked="checked"<?php } ?> required/>
  <label class="labelTr" for="cb212">ดี</label>
  <input class="inputtr"  type="radio" name="cb" id="cb313" value="2" <?php if($rs['cb']=='2'){?>checked="checked"<?php } ?> required/>
  <label class="labelTr" for="cb313">	พอใช้</label>
  <input class="inputtr"  type="radio" name="cb" id="cb414" value="1" <?php if($rs['cb']=='1'){?>checked="checked"<?php } ?> required/>
  <label class="labelTr" for="cb414">	ปรับปรุง</label>
</div>
<hr>  







       <!--   <label >2.ความสุภาพของพนักงานผู้ให้บริการ<code>*</code></label>
		  
 <div class="group"> 
  <input class="inputtr" type="radio" name="cb2" id="cb121" value="4" <?php if($rs['cb2']=='4'){?>checked="checked"<?php } ?>  required/>
  <label class="labelTr" for="cb121">พอใจมาก</label>
  <input class="inputtr"  type="radio" name="cb2" id="cb222" value="3" <?php if($rs['cb2']=='3'){?>checked="checked"<?php } ?>  required />
  <label class="labelTr" for="cb222">พอใจ</label>
  <input class="inputtr"  type="radio" name="cb2" id="cb323" value="2" <?php if($rs['cb2']=='2'){?>checked="checked"<?php } ?> required/>
  <label class="labelTr" for="cb323">	ปานกลาง</label>
  <input class="inputtr"  type="radio" name="cb2" id="cb224" value="1" <?php if($rs['cb2']=='1'){?>checked="checked"<?php } ?> required/>
  <label class="labelTr" for="cb224">	ต่ำกว่าความคาดหวัง</label>
</div> 
<hr>

          <label >3.การอธิบายข้อมูลเบื้องต้นให้ลูกค้าทราบ<code>*</code></label>
		  
 <div class="group"> 
  <input class="inputtr" type="radio" name="cb3" id="cb131" value="4" <?php if($rs['cb3']=='4'){?>checked="checked"<?php } ?> required/>
  <label class="labelTr" for="cb131">พอใจมาก</label>
  <input class="inputtr"  type="radio" name="cb3" id="cb232" value="3" <?php if($rs['cb3']=='3'){?>checked="checked"<?php } ?> required/>
  <label class="labelTr" for="cb232">พอใจ</label>
  <input class="inputtr"  type="radio" name="cb3" id="cb333" value="2" <?php if($rs['cb3']=='2'){?>checked="checked"<?php } ?> required/>
  <label class="labelTr" for="cb333">	ปานกลาง</label>
  <input class="inputtr"  type="radio" name="cb3" id="cb334" value="1" <?php if($rs['cb3']=='1'){?>checked="checked"<?php } ?> required/>
  <label class="labelTr" for="cb334">	ต่ำกว่าความคาดหวัง</label>
</div> 
<hr>
          <label >4.การเสนอความช่วยเหลือต่างๆ<code>*</code></label>
		  
 <div class="group"> 
  <input class="inputtr" type="radio" name="cb4" id="cb141" value="4" <?php if($rs['cb4']=='4'){?>checked="checked"<?php } ?> required/>
  <label class="labelTr" for="cb141">พอใจมาก</label>
  <input class="inputtr"  type="radio" name="cb4" id="cb242" value="3" <?php if($rs['cb4']=='3'){?>checked="checked"<?php } ?> required/>
  <label class="labelTr" for="cb242">พอใจ</label>
  <input class="inputtr"  type="radio" name="cb4" id="cb343" value="2" <?php if($rs['cb4']=='2'){?>checked="checked"<?php } ?> required/>
  <label class="labelTr" for="cb343">	ปานกลาง</label>
  <input class="inputtr"  type="radio" name="cb4" id="cb444" value="1" <?php if($rs['cb4']=='1'){?>checked="checked"<?php } ?> required/>
  <label class="labelTr" for="cb444">	ต่ำกว่าความคาดหวัง</label>
</div> 
<hr> -->

        </div>
      </div>
      <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">ข้อแนะนำเพื่อปรับปรุง<code>*</code></label>
          <textarea rows="3" id="rp_note_close" class="form-control " style="color:#000000; margin-top:10px;" name="rp_note_close" placeholder="กรุณากรอกรายละเอียด" required=""><?php echo $rs['rp_note_close']?></textarea>
        </div>
      </div> 
    </div>
	
   <?php } ?>
  </form>
  <div style="float:left"> 
  <?php if($sess_user_level=='admin'||$sess_user_level=='general'||$sess_user_level=='employer'){ ?>
          <?php if($rs['rp_status']!="3"){ ?>
    <button id="btnFetch"  class="btn btn-success " style="text-align:left; margin-top:10px; margin-left:10px;"> <span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span> บันทึกข้อมูล </button>
  <?php } ?>
  <?php } ?>
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

<script language="javascript">

$(function () {


  $('.select2').select2() 
  bsCustomFileInput.init();
});
 
 function fn_bx(value){   
		if(value==1){
			document.getElementById("b1").style.display="block";
			document.getElementById("b2").style.display="none";
		}else if(value==2){
			document.getElementById("b2").style.display="block";
			document.getElementById("b1").style.display="none";
		}
		
 }   
  
 function fn_sum(){ 
		var rpd_price_va 	=	document.getElementById('rpd_price_va').value;
		var rpd_qty_va 		=	document.getElementById('rpd_qty_va').value;
		document.getElementById('rpd_sum_money_va').value=rpd_qty_va*rpd_price_va;
 }
 
 function fn_sum2(i){ 
		var rpd_price 		=	document.getElementById('rpd_price'+i).value;
		var rpd_qty 		=	document.getElementById('rpd_qty'+i).value;
		document.getElementById('rpd_sum_money'+i).value=rpd_qty*rpd_price;
 }
  
 function fn_product_list(Kvalue){  
 	 var Kvalue3 = Kvalue;
 	 var Kvalue2 = Kvalue3.split("|"); 
	 document.getElementById('rpd_details_head_va_id').value = Kvalue2[0];
	 document.getElementById('rpd_details_head_va_name').value = Kvalue2[1];
	 document.getElementById('rpd_details_va').value = Kvalue2[2];
	 document.getElementById('rpd_brand_va').value = Kvalue2[3];
	 document.getElementById('rpd_price_va').value = Kvalue2[4]; 
	 document.getElementById('rpd_qty_va').focus(); 
 }
 
 function fn_add_item() { 
 
		var rpd_details_head_va_id 		=	document.getElementById('rpd_details_head_va_id').value;
		var rpd_details_head 			=	document.getElementById('rpd_details_head_va').value;
		var rpd_details_head_va_name	=	document.getElementById('rpd_details_head_va_name').value;
		var rpd_details 				=	document.getElementById('rpd_details_va').value;  
		var rpd_brand		 			=	document.getElementById('rpd_brand_va').value;  
		var rpd_price		 			=	document.getElementById('rpd_price_va').value;  
		var rpd_qty			 			=	document.getElementById('rpd_qty_va').value;  
		var rpd_sum_money 				=	document.getElementById('rpd_sum_money_va').value;   
		//var user_id_arr =   user_id.split("|");
		 if(rpd_details_head_va_id=="") { alert('กรุณากรอกข้อมูล ชื่ออะไหล่ / วัสดุสิ้นเปลือง'); return; } 
		 if(rpd_qty=="") { alert('กรุณากรอกจำนวน'); return; } 
		//if(rpd_details=="") { return; } 
		//if(rpd_details_money=="") { return; } 
		//var tmp1 = un_id.split("/");

		var tmp_row=document.getElementById('example22').rows.length;
		var x=document.getElementById('example22').insertRow((tmp_row*1));
		var c1=x.insertCell(0);
		var c2=x.insertCell(1);
		var c3=x.insertCell(2); 
		var c4=x.insertCell(3); 
		var c5=x.insertCell(4); 
		var c6=x.insertCell(5); 
		var c7=x.insertCell(6); 
		var rdChk = ''; 
		tmp_row = ((tmp_row*1)-1);
		if(((tmp_row*1)-1)=="1") { rdChk = ' checked'; }
		for (var i=0;i<=((tmp_row*1)+1); i++) {
		c1.innerHTML='<td>'+rpd_details_head_va_name+'<input style="width:100%;" class="form-control" type="hidden"  name="rpd_details_head[]" id="rpd_details_head[]" value="'+rpd_details_head_va_name+'"/><input style="width:100%;" class="form-control" type="hidden"  name="rpd_details_head_va_id2[]" id="rpd_details_head_va_id2[]" value="'+rpd_details_head_va_id+'"/></td>';
		c2.innerHTML='<td>'+rpd_details+'<input style="width:100%;" class="form-control" type="hidden" name="rpd_details[]" id="rpd_details[]" value="'+rpd_details+'" /></td>';   
		c3.innerHTML='<td>'+rpd_brand+'<input style="width:100%;" class="form-control" type="hidden" name="rpd_brand[]" id="rpd_brand[]" value="'+rpd_brand+'" /></td>';  
		c4.innerHTML='<td>'+rpd_qty+'<input style="width:100%;" class="form-control" type="hidden" name="rpd_qty[]" onkeyup="fn_sum2('+i+')" id="rpd_qty'+i+'" value="'+rpd_qty+'" /></td>';  
		c5.innerHTML='<td>'+rpd_price+'<input style="width:100%;" class="form-control" type="hidden" name="rpd_price[]" onkeyup="fn_sum2('+i+')" id="rpd_price'+i+'" value="'+rpd_price+'" /></td>';  
		c6.innerHTML='<td>'+rpd_sum_money+'<input style="width:100%;" class="form-control" type="hidden" name="rpd_sum_money[]" id="rpd_sum_money'+i+'" value="'+rpd_sum_money+'" /></td>';  
		c7.innerHTML='<td align="center">&nbsp;&nbsp;&nbsp;<a href="javascript:fn_del_row_item('+i+')" style="width:100%; padding:10px;" class="btn btn-xs btn-danger ">ลบ</a></td>';
		}
		var table 	= document.getElementById('example22');
		var rows 	= table.getElementsByTagName("tr")[((tmp_row*1)+1)];  
		 document.getElementById('rpd_details_head_va_id').value='';
		 document.getElementById('rpd_details_head_va').value='0';
		 document.getElementById('rpd_details_va').value='';  
		 document.getElementById('rpd_brand_va').value='';  
		 document.getElementById('rpd_price_va').value='';  
		 document.getElementById('rpd_qty_va').value='';  
		 document.getElementById('rpd_sum_money_va').value='';   
	}
	
	
		function addCommas(nStr) {
			  nStr = parseFloat(nStr);
			  nStr = nStr.toFixed(2);
			  nStr += '';
			  x = nStr.split('.');
			  x1 = x[0];
			  x2 = x.length > 1 ? '.' + x[1] : '';
			  var rgx = /(\d+)(\d{3})/;
			  while (rgx.test(x1)) {
			   x1 = x1.replace(rgx, '$1' + ',' + '$2');
			  }
			  return x1 + x2;
			 }
			 function set_num_format(EnumNumber){
			  //EnumNumber = EnumNumber.replace(',','');
			  //EnumNumber = EnumNumber.replace(',','');
			  if(isNaN(EnumNumber)) {
			//   var tmpVal = EnumNumber.length;
			//   EnumNumber = EnumNumber.substring(0,(EnumNumber.length-1));
			   return '';
			  }
			  if(EnumNumber!="0") {
			   var tmpNum = EnumNumber;
				tmpNum = parseFloat(tmpNum);
				tmpNum = tmpNum.toFixed(2);
			   EnumNumber = addCommas(tmpNum);
			   return EnumNumber;
			  }else{
			   return '';
			  }
		}
		function fn_sum_total() { 
		   var sum_product_tmp_arr  	 	= document.getElementsByName("sum_product_tmp[]"); 
		   var tmp_row=document.getElementById('example2').rows.length;
		   if(sum_product_tmp_arr=="") { return; }
		   var sum_total = 0; 
		   var sum_totalH = 0; 
		   for(i=0;i<((tmp_row*1)-1);i++){
				var sum_product_tmp   = sum_product_tmp_arr[i].value;  
				sum_product_tmp    = sum_product_tmp.replace(/[$,]+/g,'');  
				sum_total   = (sum_product_tmp*1);
				sum_totalH = sum_totalH + sum_total;
		   }
		   document.getElementById("sum_total_product").value = set_num_format(sum_totalH);
		   //document.getElementById("div_tb5_sum_price_cost").innerHTML = set_num_format(sum_price_cost);
		 
		}
	function fn_del_row_item(del_row){
		var table = document.getElementById("example22");
		var rows  = table.getElementsByTagName("tr")[del_row];
		rows.style.display = 'none'; 
		var rpd_details_head  = document.getElementsByName("rpd_details_head[]"); 
		rpd_details_head[(del_row-1)].value=''; 
		var rpd_details  = document.getElementsByName("rpd_details[]"); 
		rpd_details[(del_row-1)].value=''; 
		var rpd_details_money  = document.getElementsByName("rpd_details_money[]"); 
		rpd_details_money[(del_row-1)].value=''; 
	}   

//Date picker
$('#reservationdate').datetimepicker({
	format: 'L'
});

//Date and time picker
$('#reservationdatetime').datetimepicker({ icons: { time: 'far fa-clock' } });

//Date range picker
$('#reservation').daterangepicker()
//Date range picker with time picker
$('#reservationtime').daterangepicker({
  timePicker: true,
  timePickerIncrement: 30,
  locale: {
	format: 'MM/DD/YYYY hh:mm A'
  }
})
//Date range as a button
$('#daterange-btn').daterangepicker(
  {
	ranges   : {
	  'Today'       : [moment(), moment()],
	  'Yesterday'   : [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
	  'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
	  'Last 30 Days': [moment().subtract(29, 'days'), moment()],
	  'This Month'  : [moment().startOf('month'), moment().endOf('month')],
	  'Last Month'  : [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
	},
	startDate: moment().subtract(29, 'days'),
	endDate  : moment()
  },
  function (start, end) {
	$('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'))
  }
)

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

    $('#reservation').daterangepicker();
	
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
</script>   
<script src="plugins/select2/js/select2.full.min.js"></script>
<script src="plugins/jquery/jquery.min.js"></script>
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
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<script src="plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>

<style>
.inputtr {
  position: absolute;
  background:#00FF00;
  opacity: 0;
  z-index: -1;
}
.group {
  display: flex;
  align-items: center; 
}
/* Styles */
.labelTr {
  display: flex;
  align-items: center;
  margin-left: 1rem;
  margin-right: 1rem;
  padding: 0.3rem 0.3rem 0.3rem 0.3rem;
  position: relative;
  cursor: pointer;
    height: 50%;
	font-size:12px;
  transition: all 0.25s cubic-bezier(0.25, 0.25, 0.5, 1.9);
  &::before {
    content: "";
    position: absolute;
    left: 0;
    width: 1.5rem;
    height: 1.5rem;
    background: transparent;
	background-color:#009900;
    border: 2px solid #00CC00;
    border-radius: 0.25rem;
    z-index: -1;
    transition: all 0.25s cubic-bezier(0.25, 0.25, 0.5, 1.9);
    input[type="radio"] + & {
      border-radius: 2rem;
    }
  }
}
/* Checked */
.inputtr:checked + .labelTr {
  padding-left: 1em;
  color:#FFFFFF;
	background-color:#009900;
    height: 50%;
  &::before {
    width: 100%;
    height: 50%;
    background: #009900;
	background-color:#009900;
    border: 0;
    box-shadow: 0 0 0.5rem rgba(0, 0, 0, 0.5);
  }
}
</style>
  <iframe id="iFm_save_target3" name="iFm_save_target3" src="" style="width:0px;height:0px;border:0"></iframe>
  <form id="frm_update_data3" name="frm_update_data3" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target3">
    <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_24" />
    <input name="rp_id" type="hidden" id="rp_id" value="<?php echo $rp_id ?>" />
    <input name="status" type="hidden" id="status" value="<?php echo $status ?>" />
<div class="modal fade" id="modal-lg"  tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content ">
      <div class="modal-header bg-danger">
        <h4 class="modal-title">ปิดงานแจ้งซ่อม ?</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
      </div>
      <div class="modal-body">
	   <div style="background-color:#F7F7F7; padding:3px;"> <i class="fas fa-book mr-1"></i> <strong>ผลการปฏิบัติงาน</strong> </div><br />
   
	  <div class="row">
      <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">ผลการปฏิบัติ<code>*</code></label>
          <select  name="rp_jr" id="rp_jr" class="form-control select2"  onchange="fn_bx(this.value)" required>
            <option value=""><strong>เลือก</strong></option>  
            <option value="1" <?php if($rs['rp_jr']==1){?>selected="selected"<?php } ?>><strong>แล้วเสร็จ</strong></option> 
            <option value="2" <?php if($rs['rp_jr']==2){?>selected="selected"<?php } ?>><strong>ต้องเข้าดำเนินการต่อ</strong></option> 
          </select>
        </div>
      </div> 
    </div>
	  <?php if($rs['rp_jr']==1)  $Text = "display:block;"; else  $Text = "display:none;"; ?>
	<?php if($rs['rp_jr']==2)  $Text2 = "display:block;"; else  $Text2 = "display:none;"; ?>
	
	<div id='b1' style=" <?php echo $Text; ?>  background-color:#C4FFC4; padding:5px;">
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">วันที่แล้วเสร็จ<code>*</code></label>
          <div class="input-group">
            <div class="input-group-prepend"> <span class="input-group-text"> <i class="far fa-calendar-alt"></i> </span> </div>
            <input type="date" name="rp_date_rep" value="<?php echo $rs['rp_date_rep']?>" class="form-control float-right" id="reservation">
          </div>
        </div>
      </div>
      <div class="col-sm-3">
        <label for="exampleInputBorder">เวลาแล้วเสร็จ<code>*</code></label>
        <div class="form-group">
          <input  type="time" name="rp_time_rep" class="form-control form-control-border" value="<?php echo $rs['rp_time_rep']?>" id="exampleInputBorder" placeholder="รายละเอียด" >
        </div>
      </div>
      <div class="col-sm-5">
        <label for="exampleInputBorder">หมายเหตุ<code>*</code></label>
        <div class="form-group">
          <input  type="text" name="rp_note" class="form-control form-control-border" value="<?php echo $rs['rp_note']?>" id="exampleInputBorder" placeholder="หมายเหตุ" >
        </div>
      </div>
    </div>
	</div> 
	<div id='b2'  style=" <?php echo $Text2; ?> background-color: #FFFFB7; padding:5px;">
    <div class="row">
      <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">สาเหตุที่ไม่สามารถดำเนินการแล้วเสร็จ<code>*</code></label>
		  <select  name="rp_c_id" id="rp_c_id" class="form-control " style="width: 100%;">
            <option value=""><strong>เลือก</strong></option>
            <?php  
							$sql="SELECT *
							FROM tb_repair_cause 
							WHERE rpc_status = '0'
							"; 
						  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
						  $num_rows =mysqli_num_rows($query);
						  if ($num_rows>=1){ 
							while($rstmp=mysqli_fetch_array($query)) {
							  ?>
            <option value="<?php echo $rstmp['rpc_id'] ?>" <?php if($rstmp['rpc_id']==$rs['rp_c_id']){?>selected="selected"<?php } ?>><?php echo $rstmp['rpc_name']; ?></option>
            <?php 
							} 
					}
							  ?>
          </select>
        </div>
      </div>  
    </div>
	</div>
	    <div class="row">
		 <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">การประเมินผลการให้บริการ<code>*</code></label><br />
 

       <!--   <label >1.ความรวดเร็วในการให้บริการ<code>*</code></label>-->
		  
 <div class="group"> 
  <input class="inputtr" type="radio" name="cb" id="cb111" value="4" />
  <label class="labelTr" for="cb111">ดีมาก</label>
  <input class="inputtr"  type="radio" name="cb" id="cb212" value="3" />
  <label class="labelTr" for="cb212">ดี</label>
  <input class="inputtr"  type="radio" name="cb" id="cb313" value="2" />
  <label class="labelTr" for="cb313">	พอใช้</label>
  <input class="inputtr"  type="radio" name="cb" id="cb414" value="1" />
  <label class="labelTr" for="cb414">	ปรับปรุง</label>
</div>
<hr> 

        <!--  <label >2.ความสุภาพของพนักงานผู้ให้บริการ<code>*</code></label>
		  
 <div class="group"> 
  <input class="inputtr" type="radio" name="cb2" id="cb121" value="4" required/>
  <label class="labelTr" for="cb121">พอใจมาก</label>
  <input class="inputtr"  type="radio" name="cb2" id="cb222" value="3"required />
  <label class="labelTr" for="cb222">พอใจ</label>
  <input class="inputtr"  type="radio" name="cb2" id="cb323" value="2" required/>
  <label class="labelTr" for="cb323">	ปานกลาง</label>
  <input class="inputtr"  type="radio" name="cb2" id="cb224" value="1" required/>
  <label class="labelTr" for="cb224">	ต่ำกว่าความคาดหวัง</label>
</div> 
<hr>

          <label >3.การอธิบายข้อมูลเบื้องต้นให้ลูกค้าทราบ<code>*</code></label>
		  
 <div class="group"> 
  <input class="inputtr" type="radio" name="cb3" id="cb131" value="4" required/>
  <label class="labelTr" for="cb131">พอใจมาก</label>
  <input class="inputtr"  type="radio" name="cb3" id="cb232" value="3" required/>
  <label class="labelTr" for="cb232">พอใจ</label>
  <input class="inputtr"  type="radio" name="cb3" id="cb333" value="2" required/>
  <label class="labelTr" for="cb333">	ปานกลาง</label>
  <input class="inputtr"  type="radio" name="cb3" id="cb334" value="1" required/>
  <label class="labelTr" for="cb334">	ต่ำกว่าความคาดหวัง</label>
</div> 
<hr>
          <label >4.การเสนอความช่วยเหลือต่างๆ<code>*</code></label>
		  
 <div class="group"> 
  <input class="inputtr" type="radio" name="cb4" id="cb141" value="4" required/>
  <label class="labelTr" for="cb141">พอใจมาก</label>
  <input class="inputtr"  type="radio" name="cb4" id="cb242" value="3" required/>
  <label class="labelTr" for="cb242">พอใจ</label>
  <input class="inputtr"  type="radio" name="cb4" id="cb343" value="2" required/>
  <label class="labelTr" for="cb343">	ปานกลาง</label>
  <input class="inputtr"  type="radio" name="cb4" id="cb444" value="1" required/>
  <label class="labelTr" for="cb444">	ต่ำกว่าความคาดหวัง</label>
</div> 
<hr>--><!--
		  <table width="100%" border="1">
		  <tr>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		  </tr>
		  <tr>
			<td>&nbsp;</td>
			<td>พอใจมาก</td>
			<td>พอใจ</td>
			<td>ปานกลาง</td>
			<td>ต่ำกว่าความคาดหวัง</td>
		  </tr>
		  <tr>
			<td>1.ความรวดเร็วในการให้บริการ</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
		  </tr>
		  <tr>
		    <td>2.ความสุภาพของพนักงานผู้ให้บริการ</td>
		    <td>&nbsp;</td>
		    <td>&nbsp;</td>
		    <td>&nbsp;</td>
		    <td>&nbsp;</td>
		    </tr>
		  <tr>
		    <td>3.การอธิบายข้อมูลเบื้องต้นให้ลูกค้าทราบ</td>
		    <td>&nbsp;</td>
		    <td>&nbsp;</td>
		    <td>&nbsp;</td>
		    <td>&nbsp;</td>
		    </tr>
		  <tr>
		    <td>4.การเสนอความช่วยเหลือต่างๆ</td>
		    <td>&nbsp;</td>
		    <td>&nbsp;</td>
		    <td>&nbsp;</td>
		    <td>&nbsp;</td>
		    </tr>
		</table>-->

        </div>
      </div>
      <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">ข้อแนะนำเพื่อปรับปรุง<code>*</code></label>
          <textarea rows="3" id="rp_note_close" class="form-control " style="color:#000000; margin-top:10px;" name="rp_note_close" placeholder="กรุณากรอกรายละเอียด" ></textarea>
        </div>
      </div> 
    </div>
	<!--<div class="row">
      <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">แนบรูปภาพประกอบ</label>
          <br>
                     <div class="custom-file">
            <input type="file" name="rp_file_close" class="custom-file-input" id="customFile3">
            <label class="custom-file-label" for="customFile3">เลือกไฟล์</label>
          </div>
        </div>
      </div>
    </div>-->
	   <!--<div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">วันที่ปิดงาน<code>*</code></label>
          <div class="input-group">
            <div class="input-group-prepend"> <span class="input-group-text"> <i class="far fa-calendar-alt"></i> </span> </div>
            <input type="date" name="rp_close_date" value="<?php echo date('Y-m-d')?>" class="form-control float-right" id="reservation">
          </div>
        </div>
      </div>
      <div class="col-sm-3">
        <label for="exampleInputBorder">เวลา<code>*</code></label>
        <div class="form-group">
          <input  type="time" name="rp_close_time" class="form-control form-control-border" value="<?php echo  date('h:i') ?>" id="exampleInputBorder" placeholder="รายละเอียด" >
        </div>
      </div>
      </div>
	  
	  <div class="row">
      <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">รายละเอียด<code>*</code></label>
          <textarea rows="3" id="rp_note_close" class="form-control " style="color:#000000; margin-top:10px;" name="rp_note_close" placeholder="กรุณากรอกรายละเอียด" required=""></textarea>
        </div>
      </div>
    </div>
	<div class="row">
      <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">แนบรูปภาพประกอบ</label>
          <br>
                     <div class="custom-file">
            <input type="file" name="rp_file_close" class="custom-file-input" id="customFile3">
            <label class="custom-file-label" for="customFile3">เลือกไฟล์</label>
          </div>
        </div>
      </div>
    </div>-->
	
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">ยกเลิก</button>
        <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
      </div>
    </div>
  </div>
</div>
</form>

 
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>