<?php 
define("SB_M1","data_index");
define("SB_M2","data_2_1");
define("SB_M3","data_2_1_1");
include "head.php"; 
$rp_area_id = $_GET['rp_area_id'];
$Day_start = $_GET['Day_start'];
$Day_end = $_GET['Day_end'];
if($Day_start!=''){ echo $Day_start; }else{ $Day_start = date('Y-01-01'); }
if($Day_end!=''){ echo $Day_end; }else{ $Day_end =  date('Y-12-31'); }

 function fn_thai_date3($time,$formatdate){
		  if(($time=="0000-00-00")||($time=="")) {
			return '';
		  }
		  $thai_day_arr=array("อาทิตย์","จันทร์","อังคาร","พุธ","พฤหัสบดี","ศุกร์","เสาร์");
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
		  $thai_month_arr=array(
			"0"=>"",
			"1"=>"ม.ค.",
			"2"=>"ก.พ.",
			"3"=>"มี.ค.",
			"4"=>"เม.ย.",
			"5"=>"พ.ค.",
			"6"=>"มิ.ย.",	
			"7"=>"ก.ค.",
			"8"=>"ส.ค.",
			"9"=>"ก.ย.",
			"10"=>"ต.ค.",
			"11"=>"พ.ย.",
			"12"=>"ธ.ค."					
		  );
		  $time = strtotime($time);
		  if($formatdate=="2") {
			$thai_date_return =	'<span class="underlineStlye">&nbsp;&nbsp;'.(date("d",$time)*1).'&nbsp;&nbsp;</span>';
			$thai_date_return.=' เดือน '.'<span class="underlineStlye">&nbsp;&nbsp;'.($thai_full_month_arr[date("n",$time)]).'&nbsp;&nbsp;</span>';
			$thai_date_return.=	' พ.ศ. '.'<span class="underlineStlye">&nbsp;&nbsp;'.((date("Y",$time)+543)).'&nbsp;&nbsp;</span>';
		  }else if($formatdate=="3") {
			$thai_date_return =	"".(date("d",$time)*1);
			$thai_date_return.=" ".($thai_month_arr[date("n",$time)]);
			$thai_date_return.=	" ".((date("Y",$time)+543));
		  }else if($formatdate=="4") {
			$thai_date_return =	"".(date("d",$time)*1);
			$thai_date_return.=" ".($thai_month_arr[date("n",$time)]);
				$thai_date_return.=	" ".((date("Y",$time)+543))." ".date('H:i:s',$time);
		  }else if($formatdate=="5") {
			 $thai_date_return="".((date("Y",$time)+543)); 
		  }else if($formatdate=="1") {
			$thai_date_return =	"".(date("d",$time));
			$thai_date_return.=" ".($thai_full_month_arr[date("n",$time)]);
			$thai_date_return.=	" ".((date("Y",$time)+543));
		  }else if($formatdate=="6") {
			$thai_date_return =" ".($thai_month_arr[date("n",$time)]);
			$thai_date_return.=	" ".((date("y",$time)+43));
		  }else{
			$thai_date_return =	"".(date("d",$time));
			$thai_date_return.=" ".($thai_month_arr[date("n",$time)]);
			$thai_date_return.=	" ".((date("Y",$time)+543-2500));
		  }
		  if($thai_date_return!="1 ม.ค. 2513"){ return $thai_date_return;  }else{ return ""; }
		 
		}
		
	if($rp_area_id!=''){
		$Tmp = 	"AND rp_area_id = '".$rp_area_id."'";
		$Tmp1 = 	"AND area_id = '".$rp_area_id."'";
	}
		
	 $tb_list = "SELECT *
					FROM tb_area 
					WHERE area_status = '0' 
					".$Tmp1."
					";  
	$res_dep=mysqli_query($connect,$tb_list) or die(mysqli_error($connect));  
	while($arr = mysqli_fetch_array($res_dep)){	 
		$area_name =  $arr['area_name'];
	}
	
	$tb_list = "SELECT COUNT(rp_date) AS CountPrb 
				,DATE_FORMAT(`rp_date`, '%Y-%m') AS rp_date 
				FROM tb_repair 
				WHERE rp_status IN ('1','2','3')
			  	AND DATE_FORMAT(`rp_date`, '%Y-%m-%d') BETWEEN  '".$Day_start."' AND '".$Day_end."' 
				".$Tmp."
				GROUP BY DATE_FORMAT(`rp_date`, '%Y-%m')
				";   
	$res_dep=mysqli_query($connect,$tb_list) or die(mysqli_error($connect));  
	while($arr = mysqli_fetch_array($res_dep)){	 
		 $ArrCountJob[$arr['rp_date']] = $arr['CountPrb'];
	}    
	
	
	$tb_list = "SELECT COUNT(rp_date) AS CountPrb 
				,DATE_FORMAT(`rp_date`, '%Y-%m') AS rp_date 
				,rp_area_id
				FROM tb_repair 
				WHERE rp_status IN ('1','2','3')
			  	AND DATE_FORMAT(`rp_date`, '%Y-%m-%d') BETWEEN  '".$Day_start."' AND '".$Day_end."' 
				".$Tmp."
				GROUP BY DATE_FORMAT(`rp_date`, '%Y-%m')
				,rp_area_id
				";  
	//	echo 		$tb_list;
	$res_dep=mysqli_query($connect,$tb_list) or die(mysqli_error($connect));  
	while($arr = mysqli_fetch_array($res_dep)){	 
		 $ArrCountJobArea[$arr['rp_date']][$arr['rp_area_id']] = $arr['CountPrb'];
	}   
	
	
	
	$tb_list = "SELECT COUNT(rp_date) AS CountPrb 
				,DATE_FORMAT(`rp_date`, '%Y-%m') AS rp_date 
				,rp_rpg_id
				FROM tb_repair 
				WHERE rp_status IN ('1','2','3')
			  	AND DATE_FORMAT(`rp_date`, '%Y-%m-%d') BETWEEN  '".$Day_start."' AND '".$Day_end."' 
				".$Tmp."
				GROUP BY DATE_FORMAT(`rp_date`, '%Y-%m')
				,rp_rpg_id
				";  
	// 	echo 		$tb_list;
	$res_dep=mysqli_query($connect,$tb_list) or die(mysqli_error($connect));  
	while($arr = mysqli_fetch_array($res_dep)){	 
		 $ArrCountJobRpRpg[$arr['rp_date']][$arr['rp_rpg_id']] = $arr['CountPrb'];
	}   
	
	
	
	$tb_list = "SELECT COUNT(rp_date) AS CountPrb 
				,DATE_FORMAT(`rp_date`, '%Y-%m') AS rp_date 
				,rp_rps_id
				FROM tb_repair 
				WHERE rp_status IN ('1','2','3')
			  	AND DATE_FORMAT(`rp_date`, '%Y-%m-%d') BETWEEN  '".$Day_start."' AND '".$Day_end."' 
				".$Tmp."
				GROUP BY DATE_FORMAT(`rp_date`, '%Y-%m')
				,rp_rps_id
				";  
 //	echo 		$tb_list;
	$res_dep=mysqli_query($connect,$tb_list) or die(mysqli_error($connect));  
	while($arr = mysqli_fetch_array($res_dep)){	 
		 $ArrCountJobRpRps[$arr['rp_date']][$arr['rp_rps_id']] = $arr['CountPrb'];
	}   
	
 ?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js"></script>
<script type="text/javascript" src="src/jquery.qrcode.js"></script>
<script type="text/javascript" src="src/qrcode.js"></script>
<script type="text/javascript" src="ajax/ajax_framework.js"></script>
<script>
function fn_show1 () {
   	$('#modal-default').modal('show'); 
 }
function fn_alert () {
   	$('#modal-success').modal('show'); 
 }
function fn_alert1(alert_msg,alert_class) {
 	document.getElementById('bts_save_state1').innerHTML = alert_msg;
 	$('#modal-'+alert_class+'').modal('show'); 
}
function fn_alert2(alert_msg,alert_class) {
 	document.getElementById('bts_save_state2').innerHTML = alert_msg;
 	$('#modal-'+alert_class+'').modal('show'); 
}
function fn_alert3(alert_msg,alert_class) {
 	document.getElementById('bts_save_state3').innerHTML = alert_msg;
 	$('#modal-'+alert_class+'').modal('show'); 
}
function fn_alert4(alert_msg,alert_class) {
 	document.getElementById('bts_save_state1').innerHTML = alert_msg;
 	$('#modal-'+alert_class+'').modal('show'); 
	
}
function fn_click(){ 
	fn_show();	
}   
function fn_list_Process10(){   
 
	var year_period 		=	document.getElementById('year_period').value; 
	var month_period_search_m2 		=	document.getElementById('month_period_search_m2').value;  
	var t3_ag_id 		=	document.getElementById('t3_ag_id').value;  
	//var t3_degree 		=	document.getElementById('t3_degree').value;  
	//var t3_cotton 		=	document.getElementById('t3_cotton').value; 
	var URL 			= 	"menu_08_list.php";
	var data 	 		= 	'year_period='+year_period+
							'&month_period_search_m2='+month_period_search_m2+ 
							'&t3_ag_id='+t3_ag_id+  
							'&t3_cotton=';   
		ajaxLoad('post', URL, data, 'div_details','');	 
} 
</script>

<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
  
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h3><strong>หน้าหลัก</strong></h3>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <!--   <li class="breadcrumb-item active"><strong>รายการบัตร-จุดตรวจ</strong></li>
              <li class="breadcrumb-item"><a href="menu_04_04_add.php">เพิ่มรบัตร-จุดตรวจใหม่<a></li>-->
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
				  <!--<div class="col-sm-2">
					<div class="form-group">
					  <label for="exampleInputBorder">ค้นหา<code>*</code></label>
					  <input type="text" name="sr" class="form-control"   value="<?php echo $sr; ?>" id="sr" placeholder="เลขที่เอกสาร/ชื่อผู้แจ้ง">
					</div>
				  </div>-->
                    <div class="col-sm-2">
				    <div class="form-group">  
					  <label for="exampleInputBorder">เริ่มตั้งแต่<code>*</code></label>
					 <input id="Day_start" name="Day_start" type="date" class="form-control" value="<?php if($Day_start!=''){ echo $Day_start; }else{ echo date('Y-01-01'); }?>"  />
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
				  <input id="Day_end" name="Day_end" type="date" class="form-control" value="<?php echo date('Y-12-31')?>" />
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
					  <select  name="rp_area_id" id="rp_area_id" class="form-control select2" style="width: 100%;" <!--onChange="ListAmphur(this.value,'cus_old_addr02','cus_old_addr03')"--> >
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
				  
				 <!-- <div class="col-sm-2">
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
				  </div>-->
				  
				  <div class="col-sm-2">
					<div class="form-group">
					  <label for="exampleInputBorder"> <br /> </label><br />
				  <input id="end" name="end" type="hidden" class="form-control" value="emp"  />
     			  <input class="btn btn-info" type="submit" value="ค้นหา">
  </form>  
					 </div>
				  </div>
				</div>
			</div>
		</div> 
    </div>
	
    <!-- /.container-fluid -->
	<div class="container-fluid">
	    
  <style>
	#container {
		height: 400px;
	}
	
	.highcharts-figure,
	.highcharts-data-table table {
		min-width: 310px;
		max-width: 800px;
		margin: 1em auto;
	}
	
	.highcharts-data-table table {
		font-family: Verdana, sans-serif;
		border-collapse: collapse;
		border: 1px solid #ebebeb;
		margin: 10px auto;
		text-align: center;
		width: 100%;
		max-width: 500px;
	}
	
	.highcharts-data-table caption {
		padding: 1em 0;
		font-size: 1.2em;
		color: #555;
	}
	
	.highcharts-data-table th {
		font-weight: 600;
		padding: 0.5em;
	}
	
	.highcharts-data-table td,
	.highcharts-data-table th,
	.highcharts-data-table caption {
		padding: 0.5em;
	}
	
	.highcharts-data-table thead tr,
	.highcharts-data-table tr:nth-child(even) {
		background: #f8f8f8;
	}
	
	.highcharts-data-table tr:hover {
		background: #f1f7ff;
	}

	</style>
  <!-- Main content -->
  <section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-6">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><strong>จำนวนงานทั้งหมดในแต่ละเดือน</strong></h3>
          </div>
          <div class="card-body">
            <script src="https://code.highcharts.com/highcharts.js"></script>
            <script src="https://code.highcharts.com/highcharts-3d.js"></script>
            <script src="https://code.highcharts.com/modules/exporting.js"></script>
            <script src="https://code.highcharts.com/modules/export-data.js"></script>
            <script src="https://code.highcharts.com/modules/accessibility.js"></script> 
              <div id="container3"></div> 
             <!-- <div id="sliders">
                <table>
                  <tr>
                    <td><label for="alpha">ความสูง</label></td>
                    <td><input id="alpha" type="range" min="0" max="45" value="15"/>
                      <span id="alpha-value" class="value"></span></td>
                  </tr>
                  <tr>
                    <td><label for="beta">หมนุ ซ้าย ขวา</label></td>
                    <td><input id="beta" type="range" min="-45" max="45" value="15"/>
                      <span id="beta-value" class="value"></span></td>
                  </tr>
                  <tr>
                    <td><label for="depth">ความลึก</label></td>
                    <td><input id="depth" type="range" min="20" max="100" value="50"/>
                      <span id="depth-value" class="value"></span></td>
                  </tr>
                </table>
              </div> -->
          </div>
        </div>
        </div>
		
		<div class="col-6">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><strong>จำนวนงานทั้งหมดในแต่ละเดือนแยกตามอาคาร</strong></h3>
          </div>
          <div class="card-body"> 
		   <script src="https://code.highcharts.com/highcharts.js"></script>
            <script src="https://code.highcharts.com/highcharts-3d.js"></script>
            <script src="https://code.highcharts.com/modules/exporting.js"></script>
            <script src="https://code.highcharts.com/modules/export-data.js"></script>
            <script src="https://code.highcharts.com/modules/accessibility.js"></script> 
              <div id="container4"></div> 
             <!-- <div id="container2"></div> 
              <div id="sliders2">
                <table>
                  <tr>
                    <td><label for="alpha2">ความสูง</label></td>
                    <td><input id="alpha" type="range" min="0" max="45" value="15"/>
                      <span id="alpha2-value2" class="value"></span></td>
                  </tr>
                  <tr>
                    <td><label for="beta2">หมนุ ซ้าย ขวา</label></td>
                    <td><input id="beta" type="range" min="-45" max="45" value="15"/>
                      <span id="beta2-value2" class="value"></span></td>
                  </tr>
                  <tr>
                    <td><label for="depth2">ความลึก</label></td>
                    <td><input id="depth" type="range" min="20" max="100" value="50"/>
                      <span id="depth2-value2" class="value"></span></td>
                  </tr>
                </table>
              </div> -->
          </div>
        </div>
		
		
		
      </div>
      <!-- /.col -->
    </div>
    <!-- /.row --> 
	
	<div class="row">
      <div class="col-6">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><strong>ประเภทแจ้งซ่อมแยกตามชนิดของการบริการ</strong></h3>
          </div>
          <div class="card-body">
           <div id="container5"></div> 
              
          </div>
        </div>
        </div>
			<div class="col-6">
			<div class="card">
			  <div class="card-header">
				<h3 class="card-title"><strong>ประเภทแจ้งซ่อมแยกตามประเภทงาน</strong></h3>
			  </div>
			  <div class="card-body">
			   <div id="container6" style=" height:;"></div> 
				  
			  </div>
			</div>
			</div>
			</div> 
        </div> 
     </div>
		
  </div>
</div>
<!-- /.container-fluid -->
</section>
<!-- /.content -->
</div>
<!-- /.content-wrapper -->
<?php include "footer.php"; ?>
<!-- DataTables  & Plugins -->
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
fn_list_Process10();
  $(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": true,
      "buttons": ["copy",  "excel",  "print", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": true,
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
		if (confirm('ต้องการลบข้อมูลใช่หรือไม่')) {
			window.location.href="fn_save_center.php?user_id="+rsmp_id+"&SubmitH=Submit_1_1";
		}
	}
</script>
<script>




	// Set up the chart
//const chart3 = new Highcharts.Chart({
//    chart: {
//        renderTo: 'container2',
//        type: 'column',
//        options3d: {
//            enabled: true,
//            alpha: 15,
//            beta: 15,
//            depth: 50,
//            viewDistance: 25
//        }
//    },
//    xAxis: {
//        categories: [
//			 <?php  
//			$tb_list = "SELECT *
//						FROM tb_repair_system 
//						WHERE rps_status = '0'
//						";  
//			$res_dep=mysqli_query($connect,$tb_list) or die(mysqli_error($connect));  
//				while($arr = mysqli_fetch_array($res_dep)){	
//					echo "['".$arr['rps_name']."'],";
//				} 
//		  ?> 
//			]
//    },
//    yAxis: {
//        title: {
//            enabled: false
//        }
//    },
//    tooltip: {
//        headerFormat: '<b>{point.key}</b><br>',
//        pointFormat: 'จำนวนงาน: {point.y}'
//    },
//    title: {
//        text: 'กราฟแสดงผล เดือน กันยายน 2567',
//        align: 'left'
//    },
//    subtitle: {
//        text: 'Source: ' +
//            '<a href="https://ofv.no/registreringsstatistikk"' +
//            'target="_blank">OFV</a>',
//        align: 'left'
//    },
//    legend: {
//        enabled: false
//    },
//    plotOptions: {
//        column: {
//            depth: 25
//        }
//    },
//    series: [{
//        data: [10, 9, 8, 7, 6, 5, 4, 3],
//        colorByPoint: true
//    }]
//});

function showValues2() {
    document.getElementById('alpha2-value2').innerHTML = chart3.options.chart.options3d.alpha;
    document.getElementById('beta2-value2').innerHTML = chart3.options.chart.options3d.beta;
    document.getElementById('depth2-value2').innerHTML = chart3.options.chart.options3d.depth;
}

// Activate the sliders
document.querySelectorAll('#sliders2 input').forEach(input => input.addEventListener('input', e => {
    chart3.options.chart.options3d[e.target.id] = parseFloat(e.target.value);
    showValues2();
    chart3.redraw(false);
}));

//showValues2();

//////จบกราฟแรก/////////
//const chart = new Highcharts.Chart({
//    chart: {
//        renderTo: 'container',
//        type: 'column',
//        options3d: {
//            enabled: true,
//            alpha: 15,
//            beta: 15,
//            depth: 50,
//            viewDistance: 25
//        }
//    },
//    xAxis: {
//        categories: [
//			 <?php  
//			$tb_list = "SELECT *
//						FROM tb_repair_system 
//						WHERE rps_status = '0'
//						";  
//			$res_dep=mysqli_query($connect,$tb_list) or die(mysqli_error($connect));  
//				while($arr = mysqli_fetch_array($res_dep)){	
//					echo "['".$arr['rps_name']."'],";
//				} 
//		  ?> 
//			]
//    },
//    yAxis: {
//        title: {
//            enabled: false
//        }
//    },
//    tooltip: {
//        headerFormat: '<b>{point.key}</b><br>',
//        pointFormat: 'จำนวนงาน: {point.y}'
//    },
//    title: {
//        text: 'กราฟแสดงผล เดือน กันยายน  2567',
//        align: 'left'
//    },
//    subtitle: {
//        text: 'Source: ' +
//            '<a href="https://ofv.no/registreringsstatistikk"' +
//            'target="_blank">OFV</a>',
//        align: 'left'
//    },
//    legend: {
//        enabled: false
//    },
//    plotOptions: {
//        column: {
//            depth: 25
//        }
//    },
//    series: [{
//        data: [10, 9, 8, 7, 6, 5, 4, 3],
//        colorByPoint: true
//    }]
//});

function showValues() {
    document.getElementById('alpha-value').innerHTML = chart.options.chart.options3d.alpha;
    document.getElementById('beta-value').innerHTML = chart.options.chart.options3d.beta;
    document.getElementById('depth-value').innerHTML = chart.options.chart.options3d.depth;
}

// Activate the sliders
document.querySelectorAll('#sliders input').forEach(input => input.addEventListener('input', e => {
    chart.options.chart.options3d[e.target.id] = parseFloat(e.target.value);
    showValues();
    chart.redraw(false);
}));
 
 
 // Data retrieved https://en.wikipedia.org/wiki/List_of_cities_by_average_temperature
 


 
Highcharts.chart('container3', {
    chart: {
        type: 'line'
    },
    title: {
        text: 'กราฟแสดงจำนวนงานในแต่ละเดือน'
    },
    subtitle: {
        text: ''
    },
    xAxis: {
        categories: [
		<?php 
		
		
		// สร้าง DateTime object สำหรับวันที่เริ่มต้นและสิ้นสุด
$startDate = new DateTime($Day_start);
$endDate = new DateTime($Day_end);

// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
$interval = $startDate->diff($endDate);

// คำนวณจำนวนเดือนที่ต้องวนลูป
$total_months = ($interval->y * 12) + $interval->m;

// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด

for ($i = 0; $i <= $total_months; $i++) {
    // แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
     $tmpMon = $startDate->format('Y-m');
	 ?>
	 
	<?php 
	 echo "'".fn_thai_date3($tmpMon,'6')."',";
    
    // เพิ่มเดือนใน DateTime object
    $startDate->modify('+1 month');
}
		?>
      //      'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',  'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.',
     //       'พ.ย.', 'ธ.ค.' 
        ]
    },
    yAxis: {
        title: {
            text: 'จำนวนงาน'
        }
    },
    plotOptions: {
        line: {
            dataLabels: {
                enabled: true
            },
            enableMouseTracking: false
        }
    },
    series: [{
        name: 'จำนวนงานในแต่ละเดือน',
        data: [
		<?php  
		// สร้าง DateTime object สำหรับวันที่เริ่มต้นและสิ้นสุด
$startDate = new DateTime($Day_start);
$endDate = new DateTime($Day_end);

// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
$interval = $startDate->diff($endDate);

// คำนวณจำนวนเดือนที่ต้องวนลูป
$total_months = ($interval->y * 12) + $interval->m;

// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด

for ($i = 0; $i <= $total_months; $i++) {
    // แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
     $tmpMon = $startDate->format('Y-m');
	 ?>
	 
	<?php 
	if($ArrCountJob[$tmpMon]==' ') echo  '0,';
	if($ArrCountJob[$tmpMon]!='') echo  $ArrCountJob[$tmpMon].','; else echo '0,';
	
	// echo "'".fn_thai_date3($tmpMon,'6')."',";
    
    // เพิ่มเดือนใน DateTime object
    $startDate->modify('+1 month');
}
		?>
		
        //    160.0, 170.0, 152, 165, 157, 120, 244, 255, 146, 0,
        //    0, 0
        ]
    } ]
});
 
//
//
//
//Highcharts.chart('container44', {
//    chart: {
//        type: 'bar'
//    },
//    title: {
//        text: 'กราฟแสดงจำนวนงานตามอาคาร',
//        align: 'left'
//    },
//    subtitle: {
//        text: '',
//        align: 'left'
//    },
//    xAxis: {
//        categories: [
//		<?php
//			$startDate = new DateTime($Day_start);
//			$endDate = new DateTime($Day_end);
//		// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
//			$interval = $startDate->diff($endDate);
//			
//			// คำนวณจำนวนเดือนที่ต้องวนลูป
//			$total_months = ($interval->y * 12) + $interval->m;
//			
//			// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด
//			
//			for ($i = 0; $i <= $total_months; $i++) {
//				// แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
//				 $tmpMon = $startDate->format('Y-m');
//				//  echo fn_thai_date3($tmpMon,'6').',';
//				
//				echo "'".fn_thai_date3($tmpMon,'6')."',";
//				
//				// เพิ่มเดือนใน DateTime object
//				$startDate->modify('+1 month');
//			}
//			?>
//		],
//        title: {
//            text: null
//        },
//        gridLineWidth: 1,
//        lineWidth: 0
//    },
//    yAxis: {
//        min: 0,
//        title: {
//            text: '',
//            align: 'high'
//        },
//        labels: {
//            overflow: 'justify'
//        },
//        gridLineWidth: 0
//    },
//    tooltip: {
//        valueSuffix: ' งาน'
//    },
//    plotOptions: {
//        bar: {
//            borderRadius: '50%',
//            dataLabels: {
//                enabled: true
//            },
//            groupPadding: 0.1
//        }
//    },
//    legend: {
//        layout: 'vertical',
//        align: 'right',
//        verticalAlign: 'right',
//        x: -80,
//        y: -10,
//        floating: true,
//        borderWidth: 1,
//        backgroundColor:
//            Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF',
//        shadow: true
//    },
//    credits: {
//        enabled: false
//    },
//    series: [
//	
//	<?php  
//	 $tb_list = "SELECT *
//					FROM tb_area 
//					WHERE area_status = '0' 
//					";  
//		$res_dep=mysqli_query($connect,$tb_list) or die(mysqli_error($connect));  
//			while($arr = mysqli_fetch_array($res_dep)){	
//				echo '{';
//					echo "name:'".$arr['area_name']."',";
//					echo 'data: [';
//								// สร้าง DateTime object สำหรับวันที่เริ่มต้นและสิ้นสุด
//								$startDate = new DateTime($Day_start);
//								$endDate = new DateTime($Day_end);
//								
//								// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
//								$interval = $startDate->diff($endDate);
//								
//								// คำนวณจำนวนเดือนที่ต้องวนลูป
//								$total_months = ($interval->y * 12) + $interval->m;
//								
//								// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด
//								
//								for ($i = 0; $i <= $total_months; $i++) {
//									// แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
//									 $tmpMon = $startDate->format('Y-m');
//									 
//									if($ArrCountJobArea[$tmpMon][$arr['area_id']]==' ') echo  '0,';
//									if($ArrCountJobArea[$tmpMon][$arr['area_id']]!='') echo $ArrCountJobArea[$tmpMon][$arr['area_id']].','; else echo '0,';
//									
//									// echo "'".fn_thai_date3($tmpMon,'6')."',";
//									
//									// เพิ่มเดือนใน DateTime object
//									$startDate->modify('+1 month');
//								}
//					echo ']';
//				echo '},';
//			} 
//	?>
//	
//	//{
////        name: 'Year 1990',
////        data: [632, 727, 3202, 721]
////    }, {
////        name: 'Year 2000',
////        data: [814, 841, 3714, 726]
////    }, {
////        name: 'Year 2021',
////        data: [1393, 1031, 4695, 745]
////    }
//	
//	]
//});
//
//
//
//
//Highcharts.chart('container55', {
//    chart: {
//        type: 'bar'
//    },
//    title: {
//        text: 'กราฟแสดงจำนวนงานตามชนิดการให้บริการ',
//        align: 'left'
//    },
//    subtitle: {
//        text: '',
//        align: 'left'
//    },
//    xAxis: {
//        categories: [
//		<?php
//			$startDate = new DateTime($Day_start);
//			$endDate = new DateTime($Day_end);
//		// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
//			$interval = $startDate->diff($endDate);
//			
//			// คำนวณจำนวนเดือนที่ต้องวนลูป
//			$total_months = ($interval->y * 12) + $interval->m;
//			
//			// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด
//			
//			for ($i = 0; $i <= $total_months; $i++) {
//				// แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
//				 $tmpMon = $startDate->format('Y-m');
//				//  echo fn_thai_date3($tmpMon,'6').',';
//				
//				echo "'".fn_thai_date3($tmpMon,'6')."',";
//				
//				// เพิ่มเดือนใน DateTime object
//				$startDate->modify('+1 month');
//			}
//			?>
//		],
//        title: {
//            text: null
//        },
//        gridLineWidth: 1,
//        lineWidth: 0
//    },
//    yAxis: {
//        min: 0,
//        title: {
//            text: '',
//            align: 'high'
//        },
//        labels: {
//            overflow: 'justify'
//        },
//        gridLineWidth: 0
//    },
//    tooltip: {
//        valueSuffix: ' งาน'
//    },
//    plotOptions: {
//        bar: {
//            borderRadius: '50%',
//            dataLabels: {
//                enabled: true
//            },
//            groupPadding: 0.1
//        }
//    },
//    legend: {
//        layout: 'vertical',
//        align: 'right',
//        verticalAlign: 'top',
//        x: -80,
//        y: -10,
//        floating: true,
//        borderWidth: 1,
//        backgroundColor:
//            Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF',
//        shadow: true
//    },
//    credits: {
//        enabled: false
//    },
//    series: [
//	
//	<?php  
//	 $tb_list = "SELECT *
//				FROM tb_repair_group 
//				WHERE rpg_status = '0'
//				";  
//		$res_dep=mysqli_query($connect,$tb_list) or die(mysqli_error($connect));  
//			while($arr = mysqli_fetch_array($res_dep)){	
//				echo '{';
//					echo "name:'".$arr['rpg_name']."',";
//					echo 'data: [';
//								// สร้าง DateTime object สำหรับวันที่เริ่มต้นและสิ้นสุด
//								$startDate = new DateTime($Day_start);
//								$endDate = new DateTime($Day_end);
//								
//								// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
//								$interval = $startDate->diff($endDate);
//								
//								// คำนวณจำนวนเดือนที่ต้องวนลูป
//								$total_months = ($interval->y * 12) + $interval->m;
//								
//								// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด
//								
//								for ($i = 0; $i <= $total_months; $i++) {
//									// แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
//									 $tmpMon = $startDate->format('Y-m');
//									 
//									if($ArrCountJobRpRpg[$tmpMon][$arr['rpg_id']]==' ') echo  '0,';
//									if($ArrCountJobRpRpg[$tmpMon][$arr['rpg_id']]!='') echo $ArrCountJobRpRpg[$tmpMon][$arr['rpg_id']].','; else echo '0,';
//									
//									// echo "'".fn_thai_date3($tmpMon,'6')."',";
//									
//									// เพิ่มเดือนใน DateTime object
//									$startDate->modify('+1 month');
//								}
//					echo ']';
//				echo '},';
//			} 
//	?>
//	
//	//{
////        name: 'Year 1990',
////        data: [632, 727, 3202, 721]
////    }, {
////        name: 'Year 2000',
////        data: [814, 841, 3714, 726]
////    }, {
////        name: 'Year 2021',
////        data: [1393, 1031, 4695, 745]
////    }
//	
//	]
//});
//
//
//
//
//Highcharts.chart('container66', {
//    chart: {
//        type: 'bar'  ,
//		top:1000
//
//
//    },
//    title: {
//        text: 'กราฟแสดงจำนวนงานตามประเภทงาน<br>',
//        align: 'left'
//    },
//    subtitle: {
//        text: '',
//        align: 'left'
//    },
//    xAxis: {
//        categories: [
//		<?php
//			$startDate = new DateTime($Day_start);
//			$endDate = new DateTime($Day_end);
//		// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
//			$interval = $startDate->diff($endDate);
//			
//			// คำนวณจำนวนเดือนที่ต้องวนลูป
//			$total_months = ($interval->y * 12) + $interval->m;
//			
//			// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด
//			
//			for ($i = 0; $i <= $total_months; $i++) {
//				// แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
//				 $tmpMon = $startDate->format('Y-m');
//				//  echo fn_thai_date3($tmpMon,'6').',';
//				
//				echo "'".fn_thai_date3($tmpMon,'6')."',";
//				
//				// เพิ่มเดือนใน DateTime object
//				$startDate->modify('+1 month');
//			}
//			?>
//		],
//        title: {
//            text: null
//        },
//        gridLineWidth: 1,
//        lineWidth: 0
//    },
//    yAxis: {
//        min: 0,
//        title: {
//            text: '',
//            align: 'high'
//        },
//        labels: {
//            overflow: 'justify'
//        },
//        gridLineWidth: 0
//    },
//    tooltip: {
//        valueSuffix: ' งาน'
//    },
//    plotOptions: {
//        bar: {
//            borderRadius: '50%',
//            dataLabels: {
//                enabled: true
//            },
//            groupPadding: 0.1
//        }
//    },
//    legend: {
//       
//        align: 'right',
//        verticalAlign: 'top',
//        x: -90,
//        y: 280, 
//        floating: true,
//        borderWidth:1,
//        backgroundColor:
//            Highcharts.defaultOptions.legend.backgroundColor || '#FFFFFF',
//        shadow: true
//    },
//    credits: {
//        enabled: false
//    },
//    series: [
//	
//	<?php  
//	 $tb_list = "SELECT *
//				FROM tb_repair_system 
//				WHERE rps_status = '0'
//				";  
//		$res_dep=mysqli_query($connect,$tb_list) or die(mysqli_error($connect));  
//			while($arr = mysqli_fetch_array($res_dep)){	
//				echo '{';
//					echo "name:'".$arr['rps_name']."',";
//					echo 'data: [';
//								// สร้าง DateTime object สำหรับวันที่เริ่มต้นและสิ้นสุด
//								$startDate = new DateTime($Day_start);
//								$endDate = new DateTime($Day_end);
//								
//								// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
//								$interval = $startDate->diff($endDate);
//								
//								// คำนวณจำนวนเดือนที่ต้องวนลูป
//								$total_months = ($interval->y * 12) + $interval->m;
//								
//								// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด
//								
//								for ($i = 0; $i <= $total_months+3; $i++) {
//									// แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
//									 $tmpMon = $startDate->format('Y-m');
//									 
//									if($ArrCountJobRpRps[$tmpMon][$arr['rps_id']]==' ') echo  '0,';
//									if($ArrCountJobRpRps[$tmpMon][$arr['rps_id']]!='') echo $ArrCountJobRpRps[$tmpMon][$arr['rps_id']].','; else echo '0,';
//									
//									// echo "'".fn_thai_date3($tmpMon,'6')."',";
//									
//									// เพิ่มเดือนใน DateTime object
//									$startDate->modify('+1 month');
//								}
//					echo ']';
//				echo '},';
//			} 
//			 
//			
//			
//	?>
//	
// 
//
////{
////        name: 'Year 2000',
////        data: [814, 841, 3714, 726]
////    }, {
////        name: 'Year 2021',
////        data: [1393, 1031, 4695, 745]
////    }
//	
//	]
//});




Highcharts.chart('container4', {
    chart: {
        type: 'line'
    },
    title: {
        text: 'กราฟแสดงจำนวนงานในแต่ละเดือน'
    },
    subtitle: {
        text: ''
    },
    xAxis: {
        categories: [
		<?php 
		
		
		// สร้าง DateTime object สำหรับวันที่เริ่มต้นและสิ้นสุด
$startDate = new DateTime($Day_start);
$endDate = new DateTime($Day_end);

// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
$interval = $startDate->diff($endDate);

// คำนวณจำนวนเดือนที่ต้องวนลูป
$total_months = ($interval->y * 12) + $interval->m;

// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด

for ($i = 0; $i <= $total_months; $i++) {
    // แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
     $tmpMon = $startDate->format('Y-m');
	 ?>
	 
	<?php 
	 echo "'".fn_thai_date3($tmpMon,'6')."',";
    
    // เพิ่มเดือนใน DateTime object
    $startDate->modify('+1 month');
}
		?>
      //'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',  'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.',
     //'พ.ย.', 'ธ.ค.' 
        ]
    },
    yAxis: {
        title: {
            text: 'จำนวนงาน'
        }
    },
    plotOptions: {
        line: {
            dataLabels: {
                enabled: true
            },
            enableMouseTracking: false
        }
    },
	
    series: [ 
		
	<?php  
	 $tb_list = "SELECT *
					FROM tb_area 
					WHERE area_status = '0' 
					";  
		$res_dep=mysqli_query($connect,$tb_list) or die(mysqli_error($connect));  
			while($arr = mysqli_fetch_array($res_dep)){	
				echo '{';
					echo "name:'".$arr['area_name']."',";
					echo 'data: [';
								// สร้าง DateTime object สำหรับวันที่เริ่มต้นและสิ้นสุด
								$startDate = new DateTime($Day_start);
								$endDate = new DateTime($Day_end);
								
								// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
								$interval = $startDate->diff($endDate);
								
								// คำนวณจำนวนเดือนที่ต้องวนลูป
								$total_months = ($interval->y * 12) + $interval->m;
								
								// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด
								
								for ($i = 0; $i <= $total_months; $i++) {
									// แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
									 $tmpMon = $startDate->format('Y-m');
									 
									if($ArrCountJobArea[$tmpMon][$arr['area_id']]==' ') echo  '0,';
									if($ArrCountJobArea[$tmpMon][$arr['area_id']]!='') echo $ArrCountJobArea[$tmpMon][$arr['area_id']].','; else echo '0,';
									
									// echo "'".fn_thai_date3($tmpMon,'6')."',";
									
									// เพิ่มเดือนใน DateTime object
									$startDate->modify('+1 month');
								}
					echo ']';
				echo '},';
			} 
	?>
	
		
        //    160.0, 170.0, 152, 165, 157, 120, 244, 255, 146, 0,
        //    0, 0
      
     ]
});
 
 
 

//Highcharts.chart('container5', {
//    chart: {
//        type: 'line'
//    },
//    title: {
//        text: 'กราฟแสดงจำนวนงานในแต่ละเดือน'
//    },
//    subtitle: {
//        text: ''
//    },
//    xAxis: {
//        categories: [
//		<?php 
//		
//		
//		// สร้าง DateTime object สำหรับวันที่เริ่มต้นและสิ้นสุด
//$startDate = new DateTime($Day_start);
//$endDate = new DateTime($Day_end);
//
//// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
//$interval = $startDate->diff($endDate);
//
//// คำนวณจำนวนเดือนที่ต้องวนลูป
//$total_months = ($interval->y * 12) + $interval->m;
//
//// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด
//
//for ($i = 0; $i <= $total_months; $i++) {
//    // แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
//     $tmpMon = $startDate->format('Y-m');
//	 ?>
//	 
//	<?php 
//	 echo "'".fn_thai_date3($tmpMon,'6')."',";
//    
//    // เพิ่มเดือนใน DateTime object
//    $startDate->modify('+1 month');
//}
//		?>
//      //      'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',  'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.',
//     //       'พ.ย.', 'ธ.ค.' 
//        ]
//    },
//    yAxis: {
//        title: {
//            text: 'จำนวนงาน'
//        }
//    },
//    plotOptions: {
//        line: {
//            dataLabels: {
//                enabled: true
//            },
//            enableMouseTracking: false
//        }
//    },
//	
//    series: [ 
//		
//		<?php  
//	 $tb_list = "SELECT *
//				FROM tb_repair_group 
//				WHERE rpg_status = '0'
//				";  
//		$res_dep=mysqli_query($connect,$tb_list) or die(mysqli_error($connect));  
//			while($arr = mysqli_fetch_array($res_dep)){	
//				echo '{';
//					echo "name:'".$arr['rpg_name']."',";
//					echo 'data: [';
//								// สร้าง DateTime object สำหรับวันที่เริ่มต้นและสิ้นสุด
//								$startDate = new DateTime($Day_start);
//								$endDate = new DateTime($Day_end);
//								
//								// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
//								$interval = $startDate->diff($endDate);
//								
//								// คำนวณจำนวนเดือนที่ต้องวนลูป
//								$total_months = ($interval->y * 12) + $interval->m;
//								
//								// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด
//								
//								for ($i = 0; $i <= $total_months; $i++) {
//									// แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
//									 $tmpMon = $startDate->format('Y-m');
//									 
//									if($ArrCountJobRpRpg[$tmpMon][$arr['rpg_id']]==' ') echo  '0,';
//									if($ArrCountJobRpRpg[$tmpMon][$arr['rpg_id']]!='') echo $ArrCountJobRpRpg[$tmpMon][$arr['rpg_id']].','; else echo '0,';
//									
//									// echo "'".fn_thai_date3($tmpMon,'6')."',";
//									
//									// เพิ่มเดือนใน DateTime object
//									$startDate->modify('+1 month');
//								}
//					echo ']';
//				echo '},';
//			} 
//	?>
//	
//		
//        //    160.0, 170.0, 152, 165, 157, 120, 244, 255, 146, 0,
//        //    0, 0
//      
//     ]
//});
 
 Highcharts.chart('container5', {
    chart: {
        type: 'column'
    },
    title: {
        text: 'กราฟแสดงจำนวนงานแยกตามชนิดของการบริการ',
        align: 'center'
    },
    subtitle: {
        text:
            '',
        align: 'left'
    },
    xAxis: {
        categories: [
		
		<?php 
		
		
		// สร้าง DateTime object สำหรับวันที่เริ่มต้นและสิ้นสุด
		$startDate = new DateTime($Day_start);
		$endDate = new DateTime($Day_end);
		
		// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
		$interval = $startDate->diff($endDate);
		
		// คำนวณจำนวนเดือนที่ต้องวนลูป
		$total_months = ($interval->y * 12) + $interval->m;
		
		// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด
		
		for ($i = 0; $i <= $total_months; $i++) {
			// แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
			 $tmpMon = $startDate->format('Y-m');
			 ?>
			 
			<?php 
			 echo "'".fn_thai_date3($tmpMon,'6')."',";
			
			// เพิ่มเดือนใน DateTime object
			$startDate->modify('+1 month');
		}
		?>
		
		],
        crosshair: true,
        accessibility: {
            description: 'Countries'
        }
    },
    yAxis: {
        min: 0,
        title: {
            text: 'จำนวนงาน'
        }
    },
    tooltip: {
        valueSuffix: ' งาน'
    },
    plotOptions: {
        column: {
            pointPadding: 0.2,
            borderWidth: 0
        }
    },
    series: [
       <?php  
	 $tb_list = "SELECT *
				FROM tb_repair_group 
				WHERE rpg_status = '0'
				";  
		$res_dep=mysqli_query($connect,$tb_list) or die(mysqli_error($connect));  
			while($arr = mysqli_fetch_array($res_dep)){	
				echo '{';
					echo "name:'".$arr['rpg_name']."',";
					echo 'data: [';
								// สร้าง DateTime object สำหรับวันที่เริ่มต้นและสิ้นสุด
								$startDate = new DateTime($Day_start);
								$endDate = new DateTime($Day_end);
								
								// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
								$interval = $startDate->diff($endDate);
								
								// คำนวณจำนวนเดือนที่ต้องวนลูป
								$total_months = ($interval->y * 12) + $interval->m;
								
								// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด
								
								for ($i = 0; $i <= $total_months; $i++) {
									// แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
									 $tmpMon = $startDate->format('Y-m');
									 
									if($ArrCountJobRpRpg[$tmpMon][$arr['rpg_id']]==' ') echo  '0,';
									if($ArrCountJobRpRpg[$tmpMon][$arr['rpg_id']]!='') echo $ArrCountJobRpRpg[$tmpMon][$arr['rpg_id']].','; else echo '0,';
									
									// echo "'".fn_thai_date3($tmpMon,'6')."',";
									
									// เพิ่มเดือนใน DateTime object
									$startDate->modify('+1 month');
								}
					echo ']';
				echo '},';
			} 
	?>
    ]
});


 Highcharts.chart('container4', {
    chart: {
        type: 'column'
    },
    title: {
        text: 'กราฟแสดงจำนวนงานแยกตามอาคาร',
        align: 'center'
    },
    subtitle: {
        text: '',
        align: 'left'
    },
    xAxis: {
        categories: [
		
		<?php 
		
		
		// สร้าง DateTime object สำหรับวันที่เริ่มต้นและสิ้นสุด
		$startDate = new DateTime($Day_start);
		$endDate = new DateTime($Day_end);
		
		// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
		$interval = $startDate->diff($endDate);
		
		// คำนวณจำนวนเดือนที่ต้องวนลูป
		$total_months = ($interval->y * 12) + $interval->m;
		
		// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด
		
		for ($i = 0; $i <= $total_months; $i++) {
			// แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
			 $tmpMon = $startDate->format('Y-m');
			 ?>
			 
			<?php 
			 echo "'".fn_thai_date3($tmpMon,'6')."',";
			
			// เพิ่มเดือนใน DateTime object
			$startDate->modify('+1 month');
		}
		?>
		
		],
        crosshair: true,
        accessibility: {
            description: 'Countries'
        }
    },
    yAxis: {
        min: 0,
        title: {
            text: 'จำนวนงาน'
        }
    },
    tooltip: {
        valueSuffix: ' งาน'
    },
    plotOptions: {
        column: {
            pointPadding: 0.2,
            borderWidth: 0
        }
    },
    series: [
       <?php  
	 $tb_list = "SELECT *
					FROM tb_area 
					WHERE area_status = '0' 
					";  
		$res_dep=mysqli_query($connect,$tb_list) or die(mysqli_error($connect));  
			while($arr = mysqli_fetch_array($res_dep)){	
				echo '{';
					echo "name:'".$arr['area_name']."',";
					echo 'data: [';
								// สร้าง DateTime object สำหรับวันที่เริ่มต้นและสิ้นสุด
								$startDate = new DateTime($Day_start);
								$endDate = new DateTime($Day_end);
								
								// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
								$interval = $startDate->diff($endDate);
								
								// คำนวณจำนวนเดือนที่ต้องวนลูป
								$total_months = ($interval->y * 12) + $interval->m;
								
								// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด
								
								for ($i = 0; $i <= $total_months; $i++) {
									// แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
									 $tmpMon = $startDate->format('Y-m');
									 
									if($ArrCountJobArea[$tmpMon][$arr['area_id']]==' ') echo  '0,';
									if($ArrCountJobArea[$tmpMon][$arr['area_id']]!='') echo $ArrCountJobArea[$tmpMon][$arr['area_id']].','; else echo '0,';
									
									// echo "'".fn_thai_date3($tmpMon,'6')."',";
									
									// เพิ่มเดือนใน DateTime object
									$startDate->modify('+1 month');
								}
					echo ']';
				echo '},';
			} 
	?>
    ]
});



 Highcharts.chart('container6', {
    chart: {
        type: 'column'
    },
    title: {
        text: 'กราฟแสดงจำนวนงานแยกตามประเภทงาน',
        align: 'center'
    },
    subtitle: {
        text: '',
        align: 'left'
    },
    xAxis: {
        categories: [
		
		<?php 
		
		
		// สร้าง DateTime object สำหรับวันที่เริ่มต้นและสิ้นสุด
		$startDate = new DateTime($Day_start);
		$endDate = new DateTime($Day_end);
		
		// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
		$interval = $startDate->diff($endDate);
		
		// คำนวณจำนวนเดือนที่ต้องวนลูป
		$total_months = ($interval->y * 12) + $interval->m;
		
		// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด
		
		for ($i = 0; $i <= $total_months; $i++) {
			// แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
			 $tmpMon = $startDate->format('Y-m');
			 ?>
			 
			<?php 
			 echo "'".fn_thai_date3($tmpMon,'6')."',";
			
			// เพิ่มเดือนใน DateTime object
			$startDate->modify('+1 month');
		}
		?>
		
		],
        crosshair: true,
        accessibility: {
            description: 'Countries'
        }
    },
    yAxis: {
        min: 0,
        title: {
            text: 'จำนวนงาน'
        }
    },
    tooltip: {
        valueSuffix: ' งาน'
    },
    plotOptions: {
        column: {
            pointPadding: 0.2,
            borderWidth: 0
        }
    },
    series: [
      <?php  
	 $tb_list = "SELECT *
				FROM tb_repair_system 
				WHERE rps_status = '0'
				";  
		$res_dep=mysqli_query($connect,$tb_list) or die(mysqli_error($connect));  
			while($arr = mysqli_fetch_array($res_dep)){	
				echo '{';
					echo "name:'".$arr['rps_name']."',";
					echo 'data: [';
								// สร้าง DateTime object สำหรับวันที่เริ่มต้นและสิ้นสุด
								$startDate = new DateTime($Day_start);
								$endDate = new DateTime($Day_end);
								
								// หาความแตกต่างระหว่างวันที่เริ่มต้นและสิ้นสุด
								$interval = $startDate->diff($endDate);
								
								// คำนวณจำนวนเดือนที่ต้องวนลูป
								$total_months = ($interval->y * 12) + $interval->m;
								
								// ใช้ for loop เพื่อแสดงเดือนจากวันที่เริ่มต้นจนถึงวันที่สิ้นสุด
								
								for ($i = 0; $i <= $total_months; $i++) {
									// แสดงเดือนปัจจุบันในรูปแบบ ปี-เดือน (YYYY-MM)
									 $tmpMon = $startDate->format('Y-m');
									 
									if($ArrCountJobRpRps[$tmpMon][$arr['rps_id']]==' ') echo  '0,';
									if($ArrCountJobRpRps[$tmpMon][$arr['rps_id']]!='') echo $ArrCountJobRpRps[$tmpMon][$arr['rps_id']].','; else echo '0,';
									
									// echo "'".fn_thai_date3($tmpMon,'6')."',";
									
									// เพิ่มเดือนใน DateTime object
									$startDate->modify('+1 month');
								}
					echo ']';
				echo '},';
			} 
	?>
    ]
});
	</script>
<?php

//echo print_r($ArrCountJobRpRps);
?>