<?php 
define("SB_M1","data_14");
define("SB_M2","data_2_1");
define("SB_M3","data_2_1_1");
include "head.php";  

$month_period 	= $_GET['month_period'];
$year_period 	= $_GET['year_period'];
$tou_id 		= $_GET['tou_id'];
 
$sql_h="SELECT *  
		FROM tb_tou  
	  	WHERE tou_id = '".$tou_id."'
		";    
$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
$num_rows_h =mysqli_num_rows($query_h); 
if($num_rows_h>=1){    
	$rs_tou=mysqli_fetch_array($query_h);
}

?>

<script type="text/javascript" src="ajax/ajax_framework.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js"></script>
<script type="text/javascript" src="src/jquery.qrcode.js"></script>
<script type="text/javascript" src="src/qrcode.js"></script>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h3><strong>รายการบันทึกมิเตอร์ไฟ TOU <?php echo $rs_tou['tou_name']?></strong></h3>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item active"><strong>รายการบันทึกมิเตอร์ไฟ TOU <?php echo $rs_tou['tou_name']?></strong></li>
              <li class="breadcrumb-item"><a href="menu_26_add.php?tou_id=<?php echo $tou_id; ?>"><strong>บันทึกมิเตอร์ไฟ TOU <?php echo $rs_tou['tou_name']?></strong><a> 
			   
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
	 
      <div class="container-fluid">
	   <div class="row">
          <div class="col-12">
        
<div class="card">
			<div class="card-body">
            <div class="row">
					<strong> ค้นหา</strong> 
                    <div class="col-sm-2">
				  <div class="form-group"> 
				  
  <form id="studentFormd" class="form-horizontal" method="GET" enctype="multipart/form-data" action="#"> 
    <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_2" /> 
    <input name="tou_id" type="hidden" id="tou_id" value="<?php echo $tou_id?>" /> 
           <select name="year_period" id="year_period" class="form-control select2" style="font-size:14px; font-weight:normal; padding-top:0px;" onchange="fn_list_Process10()"  > 
					<option value="" > เลือกปี</option> 
					 <?php
					  if($year_period!="undefined"){
							 $year_period = date('Y');
						  }else{
						   	 $year_period = $year_period;
						  }
					 for($i=(date('Y'));$i>(date('Y')-5);$i--) {
					  ?>
					  <option value="<?php echo  $i?>" <?php  if($i==$year_period) echo 'selected';?>  ><?php echo $i+543?></option>
					  <?php
					 }?>
				</select> 
                  
                </div>
                  </div>
                  <div class="col-sm-2">
				  <div class="form-group">  
              <select name="month_period" id="month_period" class="form-control select2" style=" font-size:14px; font-weight:normal; padding-top:0px;"  onchange="fn_list_Process10()"  > 
						<option value="" > เลือกเดือน</option> 
						  <?php
						  $thai_full_month_arr=array(
							"0"=>"",
							"1"=>"มกราคม",
							"2"=>"กุมภาพันธ์",
							"3"=>"มีนาคม",
							"4"=>"เมษายน",
							"5"=>"พฤษภาคม",
							"6"=>"มิถุนายน",	
							"7"=>"กรกฏาคม",
							"8"=>"สิงหาคม",
							"9"=>"กันยายน",
							"10"=>"ตุลาคม",
							"11"=>"พฤศจิกายน",
							"12"=>"ธันวาคม"
						); 
						  
		if($month_period==''){ $month_period = date('m'); }else{  if(strlen($month_period)=='1'){ $month_period = '0'.$month_period; }else{ $month_period = $month_period; } } 
						//  echo $month_period_search;
						  for($i=01;$i<=12;$i++) { 
						  	
						   ?>
						   <option value="<?php if($i<=9){?>0<?php } ?><?php echo $i?>" <?php if($i==$month_period) echo 'selected'; ?>  ><?php echo $thai_full_month_arr[$i]?></option>
						   <?php
						  }?>
					</select> 
                </div>
                  </div>
				   
                  
                  <div class="col-sm-3">
				  <div class="form-group"> 
 
      <input type="submit" class="btn btn-info" value="ค้นหา">
  <div class="btn btn-info " style="text-align:left; " onclick="fn_preview_doc('<?php echo $month_period?>','<?php echo $year_period?>','<?php echo $tou_id?>')"><li class="fas fa-print"></li> พิมพ์ </div>
                  </div> 
    </div>   
  </form>  
    </div>    
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      </div>
	  
        <div class="row">
          <div class="col-12">
        

            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><strong>รายการบันทึกมิเตอร์ไฟ TOU <?php echo $rs_tou['tou_name']?></strong></h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
			  <style>
			  table th{
			  
			  text-align:center;
			  vertical-align:top;}
			  </style>
                <table id="example1" class="table table-bordered table-striped" style="font-size:12px;">
                  <thead>
                  <tr>
                    <th colspan="8"><strong>พลังงานไฟฟ้า</strong></th>
                    <th colspan="2"> <strong>ค่าความต้องการไฟฟ้าสูงสุด</strong></th>
                    <th colspan="2"><strong style="font-size:10px;">ค่าความต้องการกำลังฟ้ารีแอ็คทีฟ</strong></th>
                    <th>Record By </th>
                  </tr>
                  <tr>
                    <td align="center" width="80" valign="top" rowspan="2"><strong>Date<br /><br />วันที่</strong></td>
                    <td align="center" width="69" valign="top" rowspan="2"><strong>Time<br /><br />เวลา</strong></td>
                    <th colspan="2" valign="top"><strong>Total (kW/h) 010</strong></th>
                    <th colspan="2" valign="top"><strong>On Peak (kW/h) 011</strong></th>
                    <th colspan="2" valign="top"><strong>Off Peak (kW/h) 012</strong></th>
                    <td width="98" rowspan="2" align="center" valign="top"><strong>On Peak (kW/h)<br /> <br />031</strong></td>
                    <td width="101" rowspan="2" align="center" valign="top"><strong>Off Peak (kW/h)<br /><br />032</strong></td>
                    <td width="96" rowspan="2" align="center" valign="top"><strong>On Peak (kW/h)<br /><br />071</strong></td>
                    <td width="114" rowspan="2" align="center" valign="top"><strong>Off Peak (kW/h) <br />
                    <br />072</strong></td>
                    <td width="375" rowspan="2" align="center" valign="top"><strong>ผู้บันทึก</strong></td>
                  </tr>
                  <tr>
                    <th width="80" valign="top">010</th>
                    <th width="56" valign="top">(Units)</th> 
                    <th width="80" valign="top">011</th> 
                    <th width="59" valign="top">(Units)</th> 
                    <th width="80" valign="top">012</th>
                    <th width="36" valign="top">(Units)</th>
                    </tr>
                  </thead>
                  <tbody>
				  <?php
				  $sql_h="SELECT *
				  		,DATE_FORMAT(toudt_time, '%Y-%m-%d') AS Day
				  		,DATE_FORMAT(toudt_time, '%H:%h') AS time
				  		,user_name 
					 	FROM tb_tou_detail AS TbAs
						LEFT JOIN tb_user 
						ON TbAs.toudt_user_ins = user_id
						WHERE DATE_FORMAT(`toudt_time`, '%Y%m') = ".$year_period.$month_period."
						AND toudt_tou_id = ".$tou_id."
						ORDER BY toudt_time ASC
					  	"; 
					//	echo $sql_h;   
				$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
				$num_rows_h =mysqli_num_rows($query_h);
				$mPage = $_GET['mPageH'];
				$page_size=10;
				$Total_Page=ceil($num_rows_h/$page_size);
				if($mPage=="") {
					$page=1;
					$start_rec=0;
				}else{
					$page=$mPage;
					$start_rec=$page_size*($page-1);
					if($start_rec<=0) $start_rec=0;
				}
				$sql.=" 
					  LIMIT $start_rec,$page_size ";  
				$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
				$num_rows_h =mysqli_num_rows($query_h);
				$tmpbody='
				<div class="btn-group">
						';
						for($i=1;$i<=$Total_Page;$i++) {
							if($i==$page){
								$tmpbody.="<a class=\"btn btn-default\" href=\"\">$i</a>";
							}else{
								if($i==1||$i==$Total_Page){
									$tmpbody.="<a class=\"btn btn-success\" href=\"javascript:fn_pageSubmit('$i')\">$i</a>"; }
								elseif(($i<=$page+3)&&($i>=$page-3)) {
									$tmpbody.="<a class=\"btn btn-success\" href=\"javascript:fn_pageSubmit('$i')\">$i</a>"; }
								elseif(($i<=$page+6)&&($i>=$page-6)) {
									$tmpbody.=""; }
							}
						}
						$tmpbody.='
				</div>
				';
				if($num_rows_h>=1){   
					$iCountPg = $start_rec+1;
					while($rs=mysqli_fetch_array($query_h)) {  
				  ?>
                  <tr onmouseover="msOverListColor(this,'#CCCCCC')" onmouseout="msOutListColor(this,'')" >
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">
						<?php echo $rs['Day']?>					</td>
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">
					<?php echo $rs['time']?>					</td>
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">
						<?php echo $rs['toudt_010']?>					</td>
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">kW/h</td>    
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toudt_011']?></td>      
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">kW/h</td>  
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toudt_012']?></td>     
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">kW/h</td>  
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toudt_031']?></td>    
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toudt_032']?></td>   
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toudt_071']?></td>     
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['toudt_072']?></td>     
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['user_name']?></td>    
                  </tr>
					
                
				
			   <?php
					$iCountPg++;
					 } 
			   }?>
                  </tbody>
                  <tfoot>
                  <tr>
                    <th>เลขที่เอกสาร</th>
                    <th>วันที่แจ้งซ่อม</th>
                    <th>ชื่อผู้แจ้ง</th>
                    <th>เรื่องที่แจ้ง</th> 
                    <th>สถานะ</th> 
                    <th>ดำเนินการ</th> 
                    <th>&nbsp;</th>
                    <th>&nbsp;</th>
                    <th>&nbsp;</th>
                    <th>&nbsp;</th>
                    <th>&nbsp;</th>
                    <th>&nbsp;</th>
                    <th>&nbsp;</th>
                  </tr>
                  </tfoot>
                </table>
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
 <!-- DataTables  & Plugins -->
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
  $(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": true,
      "ordering": false,
      "buttons": ["copy",  "excel",  "print", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": false,
      "info": true,
      "autoWidth": false,
      "responsive": true,
    });
  }); 
	function msOverListColor(el,tarcolor) {
		el.bgColor = tarcolor;
		el.style.cursor = 'pointer';
	}
	function msOutListColor(el,tarcolor) {
		el.bgColor = tarcolor;
		if (typeof fn_view_approve_list === "function") {
			fn_view_approve_list('','');
		}
	}
	function msOverList(el,imgtar) {
		el.style.backgroundImage="url("+imgtar+")";
		el.style.cursor = 'pointer';
	}
	function msOutList(el,imgtar) {
		el.style.backgroundImage = "url("+imgtar+")";
	}
	function fn_gotourl(mLink) {
		window.location.href=mLink;
	}
	
	function fn_delete(rsmp_id) { 
		Swal.fire({
		  title: "ต้องการลบข้อมูลใช่หรือไม่", 
		  icon: "warning",
		  showCancelButton: true,
		  cancelButtonText: "ไม่",      
		  confirmButtonColor: "#3085d6",
		  cancelButtonColor: "#d33",
		  confirmButtonText: "ใช่"
		}).then((result) => {
		  if (result.isConfirmed) {
		//	window.location.href="fn_save_center.php?user_id="+rsmp_id+"&SubmitH=Submit_1_1";
			
			var URL = "fn_save_center.php" ;
			var data = 'user_id='+rsmp_id+'&SubmitH=Submit_1_1';
			ajaxLoad('POST', URL, data, div_target,'');
			
			Swal.fire({
			  title: "Deleted!",
			  text: "Your file has been deleted.",
			  icon: "success",
		  showConfirmButton: false,
		  
		  timer: 2000 
			}).then(function() {
  
     location.assign("menu_03.php") 
 });
			
			
		  }
		});
	}
	
	function fn_delete2(rsmp_id) { 
		if (confirm('ต้องการลบข้อมูลใช่หรือไม่')) {
			window.location.href="fn_save_center.php?user_id="+rsmp_id+"&SubmitH=Submit_1_1";
		}
	}
	function fn_show_add_new_process(actionH,user_id,process_id,permis_id,div_target) {

			var URL = "user_permission_process_edit_fn.php" ;
			var data = '&user_id='+user_id+'&div_target='+div_target;
			ajaxLoad('post', URL, data, div_target,'');
		
	}
	
	function fn_update_process() {
		document.frm_update_data.SubmitH.value="update";
		document.frm_update_data.submit();
	}
	
	function fn_preview_doc(month_period,year_period,toum_id) {
//			fn_auto_save_list_tb('tmpid/0002','');
		var Dist = setTimeout("fn_preview_doc_action('"+month_period+"','"+year_period+"','"+toum_id+"')", 500);
	}
	function fn_preview_doc_action(month_period,year_period,toum_id) {
		var process = '107';
		var CTRLTYPE = '';
		tmpurl = 'menu_26_print.php?month_period='+month_period+'&year_period='+year_period+'&toum_id='+toum_id+'&emp=';
		var windowFeatures = "scrollbars=yes,top=0,left=0,resizable=yes,width="+(screen.width-10)+",height="+(screen.height-90); 
		MyWindow=window.open(tmpurl,'MyWindow2',windowFeatures);	
	}
	
</script>

<iframe id="iFm_save_target" name="iFm_save_target" src="" style="width:0px;height:0px;border:0"></iframe>
	<form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="#">
		 <input name="SubmitH" type="hidden" id="SubmitH" value="" />
		 <input name="Action" type="hidden" id="Action" value="add" /> 
<div class="modal fade" id="staticBackdrop" style="width:100%;"  data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog" role="document" >
    <div class="modal-content" style="width:700px; margin-left:-200px;">
      <div class="modal-header" >
        <h5 class="modal-title" id="staticBackdropLabel"><strong>กำหนดสิทธิ์ผู้ใช้งาน</strong> </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
       <div id="div_target">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
        <button type="button" class="btn btn-primary" onclick="fn_update_process()" >บันทึกข้อมูล</button>
      </div>
    </div>
  </div>
</div>

</form>