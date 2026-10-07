<script src="ajax/ajax_framework.js"> </script>
<?php  
include "config_ctrl/checksession.php";
define("SB_M1","datauser_2");
include "head_user.php";  
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
?> 
<!-- Content Wrapper. Contains page content -->
<style>
		 .table1 { 
  border-radius: 1em;
  overflow: hidden; 
  box-shadow: rgba(6, 24, 44, 0.4) 0px 0px 0px 2px, rgba(6, 24, 44, 0.65) 0px 4px 6px -1px, rgba(255, 255, 255, 0.08) 0px 1px 0px inset;
}
		 .table2 {
  border-collapse: collapse;
  border-radius: 1em;
  overflow: hidden;
}
		</style>
<iframe id="iFm_save_target" name="iFm_save_target" src="" style="width:0px;height:0px;border:0"></iframe>
<form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target">
<input name="SubmitH" type="hidden" id="SubmitH" value="Submit_3" />
<input name="status" type="hidden" id="status" value="<?php echo $status ?>" />
<input type="hidden" name="member_id" value="<?php echo $rs['member_id'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อผู้ใช้งาน" required>
 <br />

<div style=" max-width: 100%; max-height:100%">


<table  class="table1" border="0" align="center" cellpadding="0" cellspacing="0" >
  <tbody>
    <tr>
      <td><table width="" border="0" align="center" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td> 
                <img src="33561.jpg" width="100%" /> </td>
            </tr>
            <tr>
              <td>&nbsp;</td>
            </tr>
          </tbody>
        </table>
        <table width="" border="0" align="center" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td width="213" valign="top" class="font_2"><strong>งานสอนทั้งหมด</strong> </td>
              <td width="655" valign="bottom">
            </tr>
            <tr>
              <td colspan="3"><img src="images/space10.png" width="1" height="10"></td>
            </tr>
          </tbody>
        </table>
        <table  style=" max-width: 100%; max-height:100%; margin-left:20px;"  class="table2"width="" border="1" align="center" cellpadding="0" cellspacing="0">
          <thead>
            <tr style="background-color: #000000; font-weight:500;  color:#FFFFFF;">
              <!--<th width="132" align="center" >เลขที่เอกสารใบสมัคร</th>   -->
              <!--<th width="58" align="center" > <span>
							 ลำดับ</span>							</label>  </th>  -->
              <td width="66" height="19" align="center" ><strong>ลำดับ</strong></td>
              <td width="263" align="center" ><strong>วิชา</strong></td>
              <td width="390" align="center" ><strong>สถานที่</strong></td>
              <td width="60" align="center" ><strong>คนจอง</strong></td>
              <td width="149" align="center" ><strong>สถานะ</strong></td>
              <td width="113"   align="center" ><span><strong>รายละเอียด</strong></span></td>
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
						ORDER BY sj_id DESC
						";   
					//echo $sql;
					$query=mysqli_query($connect,$sql) or die(mysqli_error($connect));
					$num_rows = mysqli_num_rows($query); 
					$query=mysqli_query($connect,$sql) or die(mysqli_error($connect));
					$num_rows = mysqli_num_rows($query); 
						if($num_rows>=1){ 
						 
							$x=0; $bg='';  $status='';
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
              <td width="66" align="center"><strong><?php echo $x; ?> </strong></td>
              <td width="263" style=" font-size:12px;"><strong><?php echo $rs['sj_subject']; ?></strong> </td>
              <td width="390" style=" font-size:12px;"><strong><?php echo $rs['sj_location']; ?> </strong></td>
              <td width="60" style=" text-align:center;  font-size:12px;"><button type="button" style="font-weight: bold;background-color: #FF9900;color: white;" class="btn btn-xs"><?php echo fn_number_format($ArrDataSum[$rs['sj_id']],'0','0') ?></button></td>
              <td width="149" style=" font-size:12px;"><?php echo $status; ?>              </td>
              <td style=" text-align: center; ">
			  <?php 
			  if($ArrDataSum[$rs['sj_id']]!=''){ 
							 echo '<a href="select_subject.php?sj_id='.$rs['sj_id'].'"><button type="button" style="font-weight: bold;background-color: #FF8000;color: white; border:#000000 solid 1px;" class="btn btn-xs">ดูรายละเอียด</button> </a>';
							   }else{ 
							echo '<a href="select_subject.php?sj_id='.$rs['sj_id'].'"><button type="button" style="font-weight: bold;background-color: #FF8000;color: white; border:#000000 solid 1px;" class="btn btn-xs">ดูรายละเอียด</button> </a>';
						   }  
			  ?>
			  
              </td>
            </tr>
            <?php     
							$i++;    
								}  
							}
							?>
            <tr style="background-color:#000000">
              <td data-label="รหัสงาน" style=" font-size:12px;">&nbsp;</td>
              <td data-label="วิชา" style=" font-size:12px;">&nbsp;</td>
              <td data-label="สถานที่" style=" font-size:12px;">&nbsp;</td>
              <td data-label="วันสอน" style=" font-size:12px;">&nbsp;</td>
              <td data-label="คนจอง" style=" text-align:center;  font-size:12px;">&nbsp;</td>
              <td data-label="สถานะ" style=" font-size:12px;">&nbsp;</td>
            </tr>
          </tbody>
        </table>
        <br /></td>
      </td>
      </td>
</table> 
<style>
.to-ttop{
background-image: url(orange-439383_960_720.png);
width:29px;
height:35px; 
}
.to-ttop:hover{
background-image: url(orange-42395_960_720.png);
width:29px;
height:35px; 
}
</style>

<a  id="back-to-top" href="#" class="back-to-top" role="button" aria-label="Scroll to top" >
      <img src="orange-439383_960_720.png" width="29" height="35"  class="to-ttop" />
</a>
<!-- Main Footer -->
<?php 
include "footer_user.php";  ?>
</div>
</div>
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




