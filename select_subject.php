<script src="ajax/ajax_framework.js"> </script>
<?php  
define("SB_M1","datauser_2");  
error_reporting(E_ALL ^ E_NOTICE);
include "config_ctrl/checksession.php";
@session_start(); 
if($_SESSION['sess_member_id_ngansorn']==""){  
?>
<script language="javascript"> 
		window.location.href="login_member.php";
	</script>
<?php
exit();
} 

include "head_user.php";  
$sj_id = $_GET['sj_id'];
$sql="SELECT count(*) AS sum , ss_sj_id
	FROM `tb_select_subject` 
	GROUP BY ss_sj_id
	";   
//echo $sql;
$query=mysqli_query($connect,$sql) or die(mysqli_error($connect));
$num_rows = mysqli_num_rows($query); 
$query=mysqli_query($connect,$sql) or die(mysqli_error($connect));
$num_rows = mysqli_num_rows($query); 
if($num_rows>=1){  
	while($rs=mysqli_fetch_array($query)){  
	$ArrDataSum[$rs['ss_sj_id']] = $rs['sum'];
	}
}
$sql="SELECT  ss_sj_id
	FROM `tb_select_subject` 
	WHERE  ss_member_id = '".$_SESSION['sess_member_id_ngansorn']."' 
	AND ss_sj_id = '".$sj_id."' 
	";     
$query=mysqli_query($connect,$sql) or die(mysqli_error($connect));
$num_rows = mysqli_num_rows($query); 
$query=mysqli_query($connect,$sql) or die(mysqli_error($connect));
$num_rows = mysqli_num_rows($query); 
if($num_rows>=1){  
	while($rs=mysqli_fetch_array($query)){  
		$DataCheck = $rs['ss_sj_id'];
	}
}
?>
<!--<link rel="stylesheet" href="css2/main.min.css">-->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container">
    <div class="card">
      <div class="card-body"> <img src="33561.jpg" width="100%" /> </div>
    </div>
    <div class="row mb-2">
      <div class="col-sm-12">
        <h1 class="m-3" style="text-align: center;"> <strong>สมัครเป็นติวเตอร์หางานสอนพิเศษกับเราได้แล้ว</strong><br />
          <a href="create.php" class="btn btn-success">สมัครเลย!</a></h1>
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row -->
  </div>
  <!-- /.container-fluid -->
</div>
<!-- /.content-header -->
<iframe id="iFm_save_target" name="iFm_save_target" src="" style="width:0px;height:0px;border:0"></iframe>
<form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target">
<input name="SubmitH" type="hidden" id="SubmitH" value="Submit_3" />
<input name="status" type="hidden" id="status" value="<?php echo $status ?>" />
<input type="hidden" name="member_id" value="<?php echo $rs['member_id'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อผู้ใช้งาน" required>
<!-- Main content -->
<div class="content">
  <div class="container">
    <div class="row">
      <div class="col-md-12"  >
        <div class="fc fc-unthemed fc-ltr">
          <div class="fc-view-container" style="">
            <div class="b-table">
              <div class="table-wrapper has-mobile-cards" >
                <table class=" table table-bordered table-hover " id="mytable_1" style="background-color: #FFFFFF;   border-collapse:collapse; font-size: 12px; border-radius:3px; ">
                  <thead>
                    <tr style="background-color: #000000; font-weight:500;  color:#FFFFFF;">
                      <!--<th width="132" align="center" >เลขที่เอกสารใบสมัคร</th>   -->
                      <!--<th width="58" align="center" > <span>
										 ลำดับ</span>							</label>  </th>  -->
                      <td width="66" height="19" align="center" ><strong>รหัสงาน</strong></td>
                      <td width="263" align="center" ><strong>วิชา</strong></td>
                      <td width="390" align="center" ><strong>สถานที่</strong></td>
                      <td width="390" align="center" ><strong>วันเรียน</strong></td>
                      <td width="149" align="center" ><strong>สอนครั้งละ</strong></td>
                      <td width="149" align="center" ><strong>ค่าแนะนำ</strong></td>
                      <td width="60" align="center" ><strong>คนจอง</strong></td>
                      <td width="149" align="center" ><strong>สถานะ</strong></td>
					  
					  
                      <!--<th width="10%" align="center" > <span>แก้ไข</span></th> -->
                    </tr>
                  </thead>
                  <tbody>
                    <?php
					$rsmp_title = array("","นาย", "นาง", "นางสาว");
					$rsmp_position  = array("","หัวหน้าแม่บ้าน", "แม่บ้าน", "สแปร์", "ล้างจาน (สจ๊วต)");
					if(strlen($search_month)==1) $search_month = "0".$search_month; 
					$sql="SELECT *
						FROM `tb_subject` 
						WHERE sj_id = '".$sj_id."'
						ORDER BY sj_id DESC
						";   
					//echo $sql;
					$query=mysqli_query($connect,$sql) or die(mysqli_error($connect));
					$num_rows = mysqli_num_rows($query); 
					$query=mysqli_query($connect,$sql) or die(mysqli_error($connect));
					$num_rows = mysqli_num_rows($query); 
								if($num_rows>=1){ 
									$x=0;
									while($rs=mysqli_fetch_array($query)){ 
									  if($rs['sj_status']=='1'){ 
											if($rs['sj_coler']==''){ $bg = 'style="background-color: #EAEA00; text-align:center;"'; }else{  $bg = 'style="background-color: '.$rs['sj_coler'].'; text-align:center;"'; }
											$status = '<i class="fa fa-bell"></i>&nbsp;&nbsp;&nbsp;<strong>ว่าง(จองด่วน)</strong><img src="new_icon.gif" />'; 
										  }elseif($rs['sj_status']=='2'){ 
											$bg = 'style="background-color: #AEFF5E; text-align:center;"';
											 $status = '<i class="fa fa-bell"></i>&nbsp;<strong>ต้องการงาน</strong>';  
										  }elseif($rs['sj_status']=='3'){ 
											if($rs['sj_coler']=='') $bg = 'style="background-color: #FF8CC6; text-align:center;"'; else  $bg = 'style="background-color: '.$rs['sj_coler'].'; text-align:center;"'; 
											 $status = '<i class="fa fa-bell"></i>&nbsp;<strong>ว่างเฉพาะติวเตอร์ (ญ)</strong> '; 
										  }elseif($rs['sj_status']=='4'){ 
										  
											if($rs['sj_coler']=='') $bg = 'style="background-color: #FFA448; text-align:center;"'; else  $bg = 'style="background-color: '.$rs['sj_coler'].'; text-align:center;"';  
											 $status = '<i class="fa fa-bell"></i>&nbsp;<strong>จองด่วน</strong>'; 
										  }elseif($rs['sj_status']=='5'){ 
											$bg = 'style="background-color: #999999; text-align:center;"';
											 $status = '<i class="fa fa-bell"></i>&nbsp;&nbsp;<strong>Deal</strong>'; 
										  }elseif($rs['sj_status']=='6'){ 
											$bg = 'style="background-color: #22B7FF; text-align:center;"';
											 $status = '<i class="fa fa-bell"></i>&nbsp;&nbsp;<strong>ว่างเฉพาะติวเตอร์ (ช)</strong>'; 
										  }	
										$x++; 
										?>
										
									 
                    <tr  height="35" <?php echo $bg ?>  >
                      <td width="66" align="center"><strong><?php echo $rs['sj_key']; ?> </strong></td>
                      <td width="263" style=" font-size:12px;"><strong><?php echo $rs['sj_subject']; ?></strong> </td>
                      <td width="390" style=" font-size:12px;"><strong><?php echo $rs['sj_location']; ?> </strong></td>
                      <td width="390" style=" font-size:12px;"><strong><?php echo $rs['sj_date_subject']; ?> </strong></td>
                      <td width="390" style=" font-size:12px;"><strong><?php echo $rs['sj_teach_money']; ?> </strong></td>
                      <td width="390" style=" font-size:12px;"><strong><?php echo $rs['sj_referral']; ?> </strong></td>
                      <td width="60" style=" text-align:center;  font-size:12px;"><button type="button" style="font-weight: bold;background-color: #FF9900;color: white;" class="btn btn-xs"><?php echo fn_number_format($ArrDataSum[$rs['sj_id']],'0','0') ?></button></td>
                      <td width="149" style=" font-size:12px;"><?php echo $status; ?> </td>
                    </tr>
                    <?php      
										}  
									}
									?>
                  </tbody>
                </table>
                <br />
                <?php if($DataCheck==''){?>
                <div style="text-align: center;"> <a href="fn_save_center.php?member_id=<?php echo $_SESSION['sess_member_first_name_ngansorn']; ?>&sj_id=<?php echo $sj_id ?>&SubmitH=Submit_4" class="btn btn-success">จองเลย!</a> </div>
                <?php }else{?>
                <div style="text-align: center;">
				<a href="index.php" class="btn btn-success  "><strong>< กลับ</strong> </a>
				 <a href="#" class="btn btn-warning "><strong>จองเรียบร้อยแล้ว !</strong></a>  
				 </div>
                <?php } ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
  <!-- Control sidebar content goes here -->
</aside>
<!-- /.control-sidebar -->
<!-- Main Footer -->
<?php 
include "footer_user.php";  ?>
</div>
<!-- ./wrapper -->
<!-- REQUIRED SCRIPTS -->
</body>
</html>
<script>
function fn_add_item_1(tmp_row_new) {
	var tmp_row=document.getElementById('example222').rows.length;
	var tem_v=document.getElementsByName('p5d_pt_id[]');
	var tem_v_total=document.getElementsByName('p5d_qty[]');
	 
	if(tem_v[tmp_row-2].value==""||tem_v_total[tmp_row-2].value=="") { return; }
	var x=document.getElementById('example222').insertRow((tmp_row*1));
	var c1=x.insertCell(0);
	var c2=x.insertCell(1);
	var c3=x.insertCell(2);
	if(((tmp_row*1)-1)=="1") { rdChk = ' checked'; }
	for (var i=0;i<=((tmp_row*1)+1); i++) {
		c1.innerHTML='<td align="center"><select id="p5d_pt_id[]" name="p5d_pt_id[]" onchange="fn_add_item_1()" class="form-control select2" style="width:100%; text-align:center;" ><option value="">--&nbsp;เลือกตำแหน่ง&nbsp;--</option><option value=1>ผู้จัดการนิติ</option><option value=2>ผู้จัดการอาคาร</option><option value=3>ผู้จัดการหมู่บ้าน</option><option value=4>ช่างเทคนิค</option><option value=5>เจ้าหน้าที่ธุรการ</option><option value=6>หัวหน้าช่างเทคนิค</option><option value=7>ผู้จัดการนิติ</option><option value=8>IT Support</option><option value=9>วิศวกร</option><option value=10>เจ้าหน้าที่ความปลอดภัย</option><option value=11>วิศวกร</option><option value=12>เจ้าหน้าที่บัญชี</option><option value=13>เจ้าหน้าที่ประสานงานอาคาร และบริการลูกค้า</option></select></td>';
		c2.innerHTML='<td><input  onkeypress="return handleEnter(this, event)" type="text" name="p5d_qty[]" id="p5d_qty[]" placeholder="จำนวน" class="form-control" style="width:100%; height:34px; text-align:center;"  onkeyup="fn_add_item_1();"/></td>';
		c3.innerHTML='<td width="87"  align="center"><a href="javascript:fn_del_row_item_1('+(i-1)+')" style="width:100%; height:100%;" class="btn btn-danger pull-right">ลบ</a></td>'; 
	}
	var table 	= document.getElementById('example222');
	var rows 	= table.getElementsByTagName("tr")[((tmp_row*1)-1)];
	var v_total_2H = document.getElementById("v_total_2H_1_1").value;
	v_total_2H = (v_total_2H*1) + 1;
	document.getElementById("v_total_2H_1_1").value = v_total_2H;
}

function fn_del_row_item_1(del_row) { 
	
	var table = document.getElementById("example222");
	var tem_v=document.getElementsByName('p5d_pt_id[]');
  	var v_total_arr		   = document.getElementsByName("p5d_qty[]");
	
	v_total_arr[(del_row-1)].value='';
	console.log(v_total_arr[(del_row-1)].value);
	if(tem_v[(del_row-1)].value=="") { return; }
	
	var rows  = table.getElementsByTagName("tr")[del_row]; 
	rows.style.display = 'none';
	//setTimeout("fn_call_tb9_sum_1()", 100);   
} 
</script>
<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Select2 -->
<script src="plugins/select2/js/select2.full.min.js"></script>
<!-- Bootstrap4 Duallistbox -->
<script src="plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js"></script>
<!-- InputMask -->
<script src="plugins/moment/moment.min.js"></script>
<script src="plugins/inputmask/jquery.inputmask.min.js"></script>
<!-- date-range-picker -->
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<!-- bootstrap color picker -->
<script src="plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Bootstrap Switch -->
<script src="plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<!-- BS-Stepper -->
<script src="plugins/bs-stepper/js/bs-stepper.min.js"></script>
<!-- dropzonejs -->
<script src="plugins/dropzone/min/dropzone.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>
<!-- AdminLTE for demo purposes -->
<!-- Page specific script -->
<script src="plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2').select2()

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })

    //Datemask dd/mm/yyyy
    $('#datemask').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' })
    //Datemask2 mm/dd/yyyy
    $('#datemask2').inputmask('mm/dd/yyyy', { 'placeholder': 'mm/dd/yyyy' })
    //Money Euro
    $('[data-mask]').inputmask()

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

    //Timepicker
    $('#timepicker').datetimepicker({
      format: 'LT'
    })

    //Bootstrap Duallistbox
    $('.duallistbox').bootstrapDualListbox()

    //Colorpicker
    $('.my-colorpicker1').colorpicker()
    //color picker with addon
    $('.my-colorpicker2').colorpicker()

    $('.my-colorpicker2').on('colorpickerChange', function(event) {
      $('.my-colorpicker2 .fa-square').css('color', event.color.toString());
    })

    $("input[data-bootstrap-switch]").each(function(){
      $(this).bootstrapSwitch('state', $(this).prop('checked'));
    })

  })
    function ListAmphur(SelectValue,nameaddr,nameaddr2)
	{
		SelectValue = SelectValue.substr(0,2);
		var URL = "get_list.php?SelectValue=" + SelectValue + "&emp=" ;
		ajaxLoad('get', URL, '', nameaddr,'');
		
		var URL = "get_list2.php?SelectValue=xx&emp=" ;
		ajaxLoad('get', URL, '', nameaddr2,'');
	}
	function ListDistrict(SelectValue,nameaddr)
	{
		SelectValue = SelectValue.substr(0,4);
		var URL = "get_list2.php?SelectValue=" + SelectValue + "&emp=" ;
		ajaxLoad('get', URL, '', nameaddr,'');
	} 
	 $(function () {
  bsCustomFileInput.init();
});
</script>
