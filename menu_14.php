<?php 
define("SB_M1","data_13");
define("SB_M2","data_2_1");
define("SB_M3","data_2_1_1");
include "head.php";  

$rp_area_id = $_GET['rp_area_id'];
$rp_ac_id 	= $_GET['rp_ac_id'];
$rp_ar_id 	= $_GET['rp_ar_id'];
$sr 		= $_GET['sr'];
$Day_start 	= $_GET['Day_start'];
$Day_end 	= $_GET['Day_end']; 
$status 	= $_GET['status']; 
if($Day_start!=''){ echo $Day_start; }else{ $Day_start = date('Y-m-01'); }
if($Day_end!=''){ echo $Day_end; }else{ $Day_end =  date('Y-m-d'); }
 
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
            <h3><strong>รายการข้อมูลแจ้งซ่อม</strong></h3>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item active"><strong>รายการข้อมูลแจ้งซ่อม</strong></li>
              <li class="breadcrumb-item"><a href="menu_14_add.php"><strong>เพิ่มรายการข้อมูลแจ้งซ่อม</strong><a> 
			   
            </ol>
          </div>
		  
        </div>
		<form id="studentFormd" class="form-horizontal" method="GET" enctype="multipart/form-data" action="#"> 
      <div class="container-fluid">
	   <div class="row">
          <div class="col-12"> 
            <div class="card">
              <div class="card-header">
				<div class="row">
				  <div class="col-sm-2">
					<div class="form-group">
					  <label for="exampleInputBorder">ค้นหา<code>*</code></label>
					  <input type="text" name="sr" class="form-control"   value="<?php echo $sr; ?>" id="sr" placeholder="เลขที่เอกสาร/ชื่อผู้แจ้ง">
					</div>
				  </div>
                    <div class="col-sm-2">
				    <div class="form-group">  
					  <label for="exampleInputBorder">เริ่มตั้งแต่<code>*</code></label>
					 <input id="Day_start" name="Day_start" type="date" class="form-control" value="<?php if($Day_start!=''){ echo $Day_start; }else{ echo date('Y-m-01'); }?>"  />
                <!-- <select name="year_period" id="year_period" class="form-control select2" style="font-size:14px; font-weight:normal; padding-top:0px;" onchange="fn_list_Process10()"  > 
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
				</select> -->
                  
                </div>
                  </div>
                  <div class="col-sm-2">
				  <div class="form-group"> 
					  <label for="exampleInputBorder">สิ้นสุด<code>*</code></label>
				  <strong></strong>
				  <input id="Day_end" name="Day_end" type="date" class="form-control" value="<?php echo date('Y-m-d')?>" />
                <!-- <select name="month_period_search_m2" id="month_period_search_m2" class="form-control select2" style=" font-size:14px; font-weight:normal; padding-top:0px;"  onchange="fn_list_Process10()"  > 
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
						  
		if($month_period!='undefined'){ $month_period = date('m'); }else{  if(strlen($month_period)=='1'){ $month_period = '0'.$month_period; }else{ $month_period = $month_period; } } 
						//  echo $month_period_search;
						  for($i=01;$i<=12;$i++) { 
						  	
						   ?>
						   <option value="<?php if($i<=9){?>0<?php } ?><?php echo $i?>" <?php if($i==$month_period) echo 'selected'; ?>  ><?php echo $thai_full_month_arr[$i]?></option>
						   <?php
						  }?>
					</select>-->
                </div>
                  </div>
				  <div class="col-sm-2">
					<div class="form-group">
					  <label for="exampleInputBorder">อาคาร/สถานที่/แผนก<code>*</code></label>
					  <select  name="rp_area_id" id="rp_area_id" class="form-control select2" style="width: 100%;" onChange="ListAmphur(this.value,'cus_old_addr02','cus_old_addr03')" >
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
						<option value="<?php echo $rstmp['area_id'] ?>" <?php if($rstmp['area_id']==$rp_area_id){?>selected="selected"<?php } ?>><?php echo $rstmp['area_name']; ?></option>
						<?php 
										}
									  }
										  ?>
					  </select>
					</div>
				  </div>
				  
				  <div class="col-sm-2">
					<div class="form-group">
					  <label for="exampleInputBorder">ชั้น<code>*</code></label><?php echo $rs['rp_ac_id']; ?>
					  <select  name="rp_ac_id" id="cus_old_addr02" class="form-control select2"  onChange="ListDistrict(this.value,'cus_old_addr03')" style="width: 100%;">
						<option value=""><strong>เลือก</strong></option>
						<?php  
										$sql="SELECT *
										FROM tb_area_class 
										WHERE  ac_id = '".$rp_area_id."'
										"; 
										//echo $sql;
									  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
									  $num_rows =mysqli_num_rows($query);
									  if ($num_rows>=1){ 
										while($rstmp=mysqli_fetch_array($query)) {
										  ?>
						<option value="<?php echo $rstmp['ac_id'] ?>" <?php if($rstmp['ac_id']==$rp_ac_id){?>selected="selected"<?php } ?>><?php echo $rstmp['ac_name']; ?></option>
						<?php 
										}
									  } 
										  ?>
					  </select>
					</div>
				  </div>
				  
				  <div class="col-sm-2">
					<div class="form-group">
					  <label for="exampleInputBorder">ห้อง<code>*</code></label>
					  <select  name="rp_ar_id" id="cus_old_addr03" class="form-control select2" style="width: 100%;">
						<option value=""><strong>เลือก</strong></option>
						<?php  
										$sql="SELECT *
										FROM tb_area_room 
										WHERE ar_area_id = '".$rp_area_id."'
										AND ar_ac_id = '".$rp_ac_id."'
										"; 
									  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
									  $num_rows =mysqli_num_rows($query);
									  if ($num_rows>=1){ 
										while($rstmp=mysqli_fetch_array($query)) {
										  ?>
						<option value="<?php echo $rstmp['ar_id'] ?>" <?php if($rstmp['ar_id']==$rp_ar_id){?>selected="selected"<?php } ?>><?php echo $rstmp['ar_name']; ?></option>
						<?php 
										}
									  } 
										  ?>
					  </select>
					</div>
				  </div>
				  </div>
				  
				  <div class="row">
				  <div class="col-sm-2">
					<div class="form-group">
					  <label for="exampleInputBorder"> สถานะเอกสาร </label><br />
					    <select name="status" id="status" class="form-control select2" style="font-size:14px; font-weight:normal; padding-top:0px;"> 
						 <option value="" > เลือกสถานะเอกสาร</option> 
						 <option value="1" <?php if($status==1){?> selected="selected"<?php } ?>> กำลังดำเนินการ</option> 
						 <option value="3" <?php if($status==3){?> selected="selected"<?php } ?>> ดำเนินการเรียบร้อย</option> 
						  
						</select>
					 </div>
				  </div>
				  
				  <div class="col-sm-2">
					<div class="form-group">
					  <label for="exampleInputBorder"> <br /> </label><br />
				  <input id="end" name="end" type="hidden" class="form-control" value="emp"  />
     			  <input type="submit" class="btn btn-info" value="ค้นหา">
  </form>  
					 </div>
				  </div>
				</div>
			</div>
		</div> 
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
	 
	 
        <div class="row">
          <div class="col-12">
        

            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><strong>ข้อมูลแจ้งซ่อม</strong></h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
			  <div class="dt-buttons btn-group flex-wrap">      
			  <!--<a target="_blank" href="menu_07_print.php?Day_start=<? echo $Day_start; ?>&Day_end=<? echo $Day_end; ?>&rp_area_id=<? echo $rp_area_id; ?>&rp_ac_id=<? echo $rp_ac_id; ?>&rp_ar_id=<? echo $rp_ar_id; ?>&status=<? echo $status; ?>&end=emp#"><button class="btn btn-secondary buttons-print" tabindex="0" aria-controls="example1" type="button"><span>Print</span></button> </a>-->
			    <a target="_blank" href="menu_07_excel.php?Day_start=<? echo $Day_start; ?>&Day_end=<? echo $Day_end; ?>&rp_area_id=<? echo $rp_area_id; ?>&rp_ac_id=<? echo $rp_ac_id; ?>&rp_ar_id=<? echo $rp_ar_id; ?>&status=<? echo $status; ?>&end=emp#"><button class="btn btn-secondary buttons-print" tabindex="0" aria-controls="example1" type="button"><span>Excel</span></button> </a>
			 </div>
                <table id="example1s" class="table table-bordered table-striped" style="font-size:12px;">
                  <thead>
                  <tr>
                    <th width="70">เลขที่เอกสาร</th>
                    <th width="78">วันที่แจ้งซ่อม</th>
                    <th width="110">ชื่อผู้แจ้ง</th>
                    <th width="167">เรื่องที่แจ้ง</th> 
                    <th width="213">สถานที่</th>  
                    <th width="135">ชนิดของการบริการ</th>
                    <th width="166">ประเภทงาน</th>
                    <th width="100">สถานะ</th> 
                    <th width="100">ดำเนินการ</th> 
                  </tr>
                  </thead>
                  <tbody>
				  <?php
				  
					if($rp_area_id!=''){
						$Tmp = 	"AND rp_area_id = '".$rp_area_id."'";
					}
					if($rp_ac_id!=''){
						$Tmp1 = 	"AND rp_ac_id = '".$rp_ac_id."'";
					}
					if($rp_ar_id!=''){
						$Tmp2 = 	"AND rp_ar_id = '".$rp_ar_id."'";
					} 
					if($rp_ar_id!=''){
						$Tmp3 = 	"AND rp_ar_id = '".$rp_ar_id."'";
					} 
					if($status!=''){
						$Tmp4 = 	"AND rp_status = '".$status."'";
					} 
					if($sr!="") {
						$tmpSQLemp = "AND (
									 (rp_format LIKE '%".$sr."%'
									  OR rp_name LIKE '%".$sr."%' 
									  ) 
										)";
					}   
				  
				  
				  $sql_h="SELECT *  
					 	FROM tb_repair AS TbAs 
					  LEFT JOIN tb_area AS tb_area
					  ON tb_area.area_id = TbAs.rp_area_id
					  LEFT JOIN tb_area_class AS tb_area_class
					  ON tb_area_class.ac_id = TbAs.rp_ac_id
					  LEFT JOIN tb_area_room AS tb_area_room
					  ON tb_area_room.ar_id = TbAs.rp_ar_id 
					  LEFT JOIN tb_channel AS tb_channel
					  ON tb_channel.rpcn_id = TbAs.rp_rpcn_id 
					  LEFT JOIN tb_repair_group AS tb_repair_group
					  ON tb_repair_group.rpg_id = TbAs.rp_rpg_id 
					  LEFT JOIN tb_repair_system AS tb_repair_system
					  ON tb_repair_system.rps_id = TbAs.rp_rps_id 
					  LEFT JOIN tb_user AS tb_user
					  ON tb_user.user_id = TbAs.rp_user_id_rep 
						WHERE rp_status IN ('1','2','3')
						  AND DATE_FORMAT(`rp_date`, '%Y-%m-%d') BETWEEN  '".$Day_start."' AND '".$Day_end."'
						".$Tmp."
						".$Tmp1."
						".$Tmp2."
						".$Tmp3."
						".$Tmp4."
						".$tmpSQLemp."
						ORDER BY rp_id DESC
					  	";  
				//	echo   $sql_h;
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
						<?php echo $rs['rp_format']?>					</td>
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">
					<?php echo $rs['rp_date']?>					</td>
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">
						<?php echo $rs['rp_name']?>					</td>
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">
					<?php echo $rs['rp_subject']?>					</td>    
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">
					<?php echo $rs['area_name']?> <?php echo $rs['ac_name']?> <?php echo $rs['ar_name']?>					</td>    
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['rpg_name']; ?></td>
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')"><?php echo $rs['rps_name']; ?></td>
                    <td ondblclick="fn_gotourl('menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1&img_icon=glyphicon-inbox')">
					<?php if($rs['rp_status']=='1'){?> 
					<button type="button" class="btn btn-block btn-warning btn-xs">กำลังดำเนินการ</button>
					<?php }elseif($rs['rp_status']=='3'){?>
					<button type="button" class="btn btn-block btn-success btn-xs">ดำเนินการเรียบร้อย</button>
					<?php } ?>					</td>      
                    <td>  
					<a href="print_ver2.php?rp_id=<?php echo $rs['rp_id']?>" target="_blank" class="btn btn-success  btn-xs " style="text-align:right; "> <li class="fas fa-print" aria-hidden="true"></li>  </a>
				   <?php if($sess_user_level=='admin'||$sess_user_level=='general'){ ?>
					<a href="menu_14_add.php?rp_id=<?php echo $rs['rp_id']?>&status=1"  role="button" data-toggle="tooltip" data-placement="top" title="แก้ไขข้อมูล" class="btn btn-xs  btn-info "><li class="fas fa-edit"></li><a> 
					<a target="_blank"  onclick="fn_delete('<?php echo $rs['user_id']?>')" role="button" data-toggle="tooltip" data-placement="top" title="ลบข้อมูล" class="btn btn-xs  btn-danger "><li class="fas fa-trash"></li> <a>
						<?php 
					 } ?>					</td>   
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
                    <th>สถานที่</th> 
                    <th>&nbsp;</th>
                    <th>&nbsp;</th>
                    <th>สถานะ</th> 
                    <th>ดำเนินการ</th> 
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

<div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Modal Title</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        Content inside the modal
      </div>
    </div>
  </div>
</div>
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