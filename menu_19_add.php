<?php 
define("SB_M1","data_13");
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
		$text = ' ข้อมูลหน่วย';
	}else{
		$text = 'ข้อมูลหน่วย';
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
  
     location.assign("menu_14.php") 
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
            <li class="breadcrumb-item"><a href="menu_03.php">ข้อมูลหน่วย</a></li>
            <li class="breadcrumb-item active"><?php echo $text; ?></li>
          </ol>
        </div>
      </div>
    </div>
    <!-- /.container-fluid -->
  </section>
  <!-- Main content -->
  <section class="content">
  <iframe id="iFm_save_target" name="iFm_save_target" src="" style="width:0px;height:0px;border:0"></iframe>
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
      <h3 class="card-title"><strong><?php echo $text; ?></strong> </h3>
    </div>
    <!-- /.card-header -->
    <div class="card-body" style="font-size:14px;">
    
    <br />
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
        
            
            
          </select>
        </div>
      </div>
    </div>
    
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          <label for="exampleInputBorder">ชื่อหน่วยงาน<code>*</code></label>
          <input  type="text" name="rp_name" class="form-control form-control-border" value="<?php echo $rs['rp_name']?>" id="rp_name" placeholder="ชื่อ" required>
        </div>
      </div>
      
      <div class="col-sm-4">
        <div class="form-group">
          
          
        </div>
      </div>
      <div class="col-sm-2">
        <div class="form-group">
          
          <div class="input-group">
            
          
          </div>
        </div>
        
        
      </div>
      <div class="col-sm-2">
        <div class="form-group">
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-4">
        <div class="form-group">
          
         
            
            
							  
          </select>
        </div>
      </div>
    </div>
    <!-- <div style="background-color:#F7F7F7; padding:3px;"> <i class="fas fa-book mr-1"></i> <strong>ที่อยู่</strong> </div>
              <br />-->
    <div class="row">
      <div class="col-sm-5">
        <div class="form-group">
          <label for="exampleInputBorder">ที่อยู่<code>*</code></label>
          <textarea rows="3" id="comment" class="form-control " style="color:#000000; margin-top:10px;" name="rp_subject" placeholder="กรุณากรอกรายละเอียด" required=""><?php echo $rs['rp_subject']?></textarea>
        </div>
      </div>
    </div>
    
          
       
          </div>
        </div>
      </div>
    </div>
    <?php if($rp_id!=''){?>
    <div style="background-color:#F7F7F7; padding:3px;"> <i class="fas fa-book mr-1"></i> <strong>รายละเอียดงานซ่อม</strong> </div>
    <br />
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
    <div class="row">
      <div class="col-sm-12">
        <div class="form-group">
          <label for="exampleInputBorder">ผู้ดำเนินการ/ช่าง<code>*</code></label>
          <select  name="rp_user_id_rep" id="rp_user_id_rep" class="form-control select2" style="width: 100%;">
            <option value=""><strong>เลือก</strong></option>
            <?php
							$sql="SELECT *
							FROM tb_user
							LEFT JOIN tb_department2 AS TbDpm2
							ON tb_user.user_department = TbDpm2.dep_id
							WHERE user_status_login = '1'
							AND user_department = '2'
							"; 
						  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
						  $num_rows =mysqli_num_rows($query);
						  if ($num_rows>=1){ 
							while($rstmp=mysqli_fetch_array($query)) {
							  ?>
            <option value="<?php echo $rstmp['user_id'] ?>" <?php if($rstmp['dep_id']==$rs['dep_id']){?>selected="selected"<?php } ?>><strong><?php echo $rstmp['user_name'].' '.$rstmp['user_fname'] ?></strong></option>
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
          <label for="exampleInputBorder">วันที่นัดหมาย<code>*</code></label>
          <div class="input-group">
            <div class="input-group-prepend"> <span class="input-group-text"> <i class="far fa-calendar-alt"></i> </span> </div>
            <input type="date" name="rp_date_rep" value="<?php echo $rs['rp_date_rep']?>" class="form-control float-right" id="reservation">
          </div>
        </div>
      </div>
      <div class="col-sm-3">
        <label for="exampleInputBorder">เวลานัดหมาย<code>*</code></label>
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
    <div style="background-color:#F7F7F7; padding:3px;"> <i class="fas fa-book mr-1"></i> <strong>งานซ่อม/ค่าบริการเพิ่มเติม</strong> </div>
    <br />
    <table style="margin-top:-10px;" id="example22" width="100%">
      <thead>
        <tr style="font-size:16px; border-bottom: solid #CCCCCC 2px; ">
          <th width="40%" class="text-center"><strong>หัวข้อ</strong></th>
          <th width="40%" class="text-center"><strong>รายละเอียด</strong></th>
          <th width="14%" class="text-center"><span style="font-size:14px;"><strong>ค่าใช้จ่าย</strong></span></th>
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
          <th width="40%" class="text-center"><strong><input style="width:100%;" class="form-control" type="text"  name="rpd_details_head[]" id="rpd_details_head[]" value="<?php echo $rstmp['rpd_details_head']?>"/></strong></th>
          <th width="40%" class="text-center"><strong><input style="width:100%;" class="form-control" type="text" name="rpd_details[]" id="rpd_details[]" value="<?php echo $rstmp['rpd_details']?>" /></strong></th>
          <th width="14%" class="text-center"><span style="font-size:14px;"><strong><input style="width:100%;" class="form-control" type="text" name="rpd_details_money[]" id="rpd_details_money[]" value="<?php echo $rstmp['rpd_details_money']?>" /></strong></span></th> 
			   <td align="center">&nbsp;&nbsp;&nbsp;<a href="javascript:fn_del_row_item('<?php echo $i ?>')" style="width:100%; padding:10px;" class="btn btn-xs btn-danger ">ลบ</a></td></td>
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
          <td width="40%"><strong>หัวข้อ</strong>
            <input  type="text" name="rpd_details_head_va" class="form-control" value="<?php echo $rs['asset_name']?>" id="rpd_details_head_va" placeholder="หัวข้อ" >
          </td>
          <td width="40%"><strong>รายละเอียด</strong>
            <input  type="text" name="rpd_details_va" class="form-control" value="<?php echo $rs['asset_name']?>" id="rpd_details_va" placeholder="รายละเอียด" >
          </td>
          <td width="14%"><strong>ค่าใช้จ่าย</strong>
            <input  type="text" name="rpd_details_money_va" class="form-control " value="<?php echo $rs['asset_name']?>" id="rpd_details_money_va" placeholder="ค่าใช้จ่าย" >
          </td>
          <td width="6%">&nbsp;<br />
            <a onclick="fn_add_item()" class="btn btn-default btn-xs" style="  padding:10px;">
            <li class="fa fa-plus"></li>
            เพิ่มรายการ </a> </td>
        </tr>
      </tbody>
    </table>
    <?php } ?>
  </form>
  <div style="float:left">
    <?php if($rs['rp_status']==''){?>
    <button id="btnFetch"  class="btn btn-success " style="text-align:left; margin-top:10px; margin-left:10px;"> <span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span> บันทึกข้อมูล </button>
    <?php }elseif($rs['rp_status']=='1'){ ?>
    <button id="btnFetch"  class="btn btn-success " style="text-align:left; margin-top:10px; margin-left:10px;"> <span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span> ดำเนินการซ่อม </button>
    <?php }elseif($rs['rp_status']=='2'){ ?>
    <button id="btnFetch"  class="btn btn-success " style="text-align:left; margin-top:10px; margin-left:10px;"> <span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span> บันทึกข้อมูลดำเนินการซ่อม </button>
    <button type="button" class="btn btn-danger" style="text-align:left; margin-top:10px; margin-left:10px;" data-toggle="modal" data-target="#modal-lg">ปิดงาน</button>
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
<script language="javascript">

$(function () {
  bsCustomFileInput.init();
});

 function fn_add_item() { 
		var rpd_details_head 		=	document.getElementById('rpd_details_head_va').value;
		var rpd_details 			=	document.getElementById('rpd_details_va').value;  
		var rpd_details_money 		=	document.getElementById('rpd_details_money_va').value;  
		//var user_id_arr =   user_id.split("|");
		if(rpd_details_head=="") { alert('กรุณากรอกหัวข้อ'); return } 
		//if(rpd_details=="") { return; } 
		//if(rpd_details_money=="") { return; } 
		//var tmp1 = un_id.split("/");

		var tmp_row=document.getElementById('example22').rows.length;
		var x=document.getElementById('example22').insertRow((tmp_row*1));
		var c1=x.insertCell(0);
		var c2=x.insertCell(1);
		var c3=x.insertCell(2); 
		var c4=x.insertCell(3); 
		var rdChk = '';
		tmp_row = ((tmp_row*1)-1);
		if(((tmp_row*1)-1)=="1") { rdChk = ' checked'; }
		for (var i=0;i<=((tmp_row*1)+1); i++) {
		c1.innerHTML='<td><input style="width:100%;" class="form-control" type="text"  name="rpd_details_head[]" id="rpd_details_head[]" value="'+rpd_details_head+'"/></td>';
		c2.innerHTML='<td><input style="width:100%;" class="form-control" type="text" name="rpd_details[]" id="rpd_details[]" value="'+rpd_details+'" /></td>';   
		c3.innerHTML='<td><input style="width:100%;" class="form-control" type="text" name="rpd_details_money[]" id="rpd_details_money[]" value="'+rpd_details_money+'" /></td>';  
		c4.innerHTML='<td align="center">&nbsp;&nbsp;&nbsp;<a href="javascript:fn_del_row_item('+i+')" style="width:100%; padding:10px;" class="btn btn-xs btn-danger ">ลบ</a></td>';
		}
		var table 	= document.getElementById('example22');
		var rows 	= table.getElementsByTagName("tr")[((tmp_row*1)+1)];  
		 document.getElementById('rpd_details_head_va').value='';
		 document.getElementById('rpd_details_va').value='';  
		 document.getElementById('rpd_details_money_va').value='';  
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
	format: 'DD/MM/YYYY hh:mm A'
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
</script>
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
<?php include "footer.php"; ?>


  <iframe id="iFm_save_target3" name="iFm_save_target3" src="" style="width:800px;height:200px;border:0"></iframe>
  <form id="frm_update_data3" name="frm_update_data3" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target3">
    <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_24" />
    <input name="rp_id" type="hidden" id="rp_id" value="<?php echo $rp_id ?>" />
    <input name="status" type="hidden" id="status" value="<?php echo $status ?>" />
<div class="modal fade" id="modal-lg">
  <div class="modal-dialog modal-lg">
    <div class="modal-content ">
      <div class="modal-header bg-danger">
        <h4 class="modal-title">ปิดงานแจ้งซ่อม ?</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
      </div>
      <div class="modal-body">
	  
	   <div class="row">
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
    </div>
	a
      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
    </div>
  </div>
</div>
</form>
