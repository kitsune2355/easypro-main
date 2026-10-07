<?php
@session_start();
include "config_ctrl/connect.php";
include "config_ctrl/checksession.php";
date_default_timezone_set('Asia/Bangkok');
  
			
function fn_number_format($value,$decimal,$nullOption) {
	if(($value*1)!=0) {
		return number_format(($value),$decimal);
	}else{
		return $nullOption;
	}
}
function fn_thai_date($time,$formatdate){
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
    $thai_date_return.=	" ".((date("Y",$time)+543))." ".date('H:i:s',$time);
  }else if($formatdate=="4") {
    $thai_date_return =	"".(date("d",$time));
    $thai_date_return.="/".(date("m",$time));
    $thai_date_return.="/".(date("y",$time)+43);
  }else if($formatdate=="5") {
     $thai_date_return="".((date("Y",$time)+543)); 
  }else if($formatdate=="1") {
    $thai_date_return =	"".(date("d",$time));
    $thai_date_return.=" ".($thai_full_month_arr[date("n",$time)]);
    $thai_date_return.=	" ".((date("Y",$time)+543));
  }else{
    $thai_date_return =	"".(date("d",$time));
    $thai_date_return.=" ".($thai_month_arr[date("n",$time)]);
    $thai_date_return.=	" ".((date("Y",$time)+543-2500));
  }
  if($thai_date_return!="1 ม.ค. 2513"){ return $thai_date_return;  }else{ return ""; }
}

$cp_ag_id = $_GET['cp_ag_id'];
$cp_point_number = $_GET['cp_point_number'];
$sql_h="SELECT *  
		FROM tb_agency  
       WHERE ag_id =  '".$cp_ag_id."'
		";    
$query_h = mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
$num_rows_h = mysqli_num_rows($query_h);
$rs_agency = mysqli_fetch_array($query_h);
$c =  $rs2['ag_contract'];

	$CheckImg = 0;
if($cp_ag_id=='11'){
	$CheckImg = 1;
}

$sql_h="SELECT *  
		FROM tb_checkpoint AS TbCp
		LEFT JOIN tb_list_check_detail as TbCde
		ON TbCde.cd_cp_id = TbCp.cp_id
		LEFT JOIN tb_list_check_head as TbCh
		ON TbCh.ch_id = TbCde.cd_ch_id
        WHERE cp_ag_id =  '".$cp_ag_id."'
		AND cp_point_number = '".$cp_point_number."'
		";  
	//echo $sql_h;
$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
$num_rows_h =mysqli_num_rows($query_h);
$rs_checkpoint=mysqli_fetch_array($query_h); 

$sql="SELECT *  
FROM tb_list_check_head AS tbcp 
WHERE  ch_agency     = '".$cp_ag_id."'  
  ";
$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
$num_rows =mysqli_num_rows($query);
if($num_rows>=1){
  while($rs=mysqli_fetch_array($query)) {
	//	echo '<br>เริ่ม'.strtotime($rs['ch_work_time']).' สิ้น'.strtotime($rs['ch_end_time']."+ 1 minute").'<br>';    
		if(time()>=strtotime($rs['ch_work_time']) && time()<=strtotime($rs['ch_end_time']."+ 1 minute")){
				$ch_work_time = $rs['ch_work_time'];
				$ch_end_time = $rs['ch_end_time'];
  				$tmpKey = $rs['ch_id'];
		}
 
  }
}



$sql_cp="SELECT *  
	  FROM tb_list_check_detail AS tbcd 
	  LEFT JOIN tb_checkpoint AS TbCpp
	  ON TbCpp.cp_id = tbcd.cd_cp_id
	  WHERE cd_ch_id = '".$tmpKey."'   
	  AND cp_point_number = '".$cp_point_number."'
	  ";
	//  echo $sql_cp;
$query_cp =mysqli_query($connect,$sql_cp) or die(mysqli_error($connect));
$num_rows_cp =mysqli_num_rows($query_cp);
if($num_rows_cp>=1){
  $rs_cp=mysqli_fetch_array($query_cp);
  	$cp_point_name = $rs_cp['cp_point_name']; 
  	$cp_point_number = $rs_cp['cp_point_number'];   
  	$cd_cp_id = $rs_cp['cd_cp_id']; 
	
}else{
  $rs_cp=mysqli_fetch_array($query_cp);
  	$cp_point_name = $rs_cp['cp_point_name']; 
echo $rs_cp['cp_point_number'].'
		<script language="javascript">
		alert("ขออภัยเวลาปัจุบันไม่อยู่ช่วงตรวจจุด");
		window.close()
		window.parent.location.href="check-error.php?cp_ag_id='.$lp_agency.'&cp_point_number='.$lp_check_point.'";
		// window.parent.location.href="check.php?cp_ag_id='.$lp_agency.'&cp_point_number='.$lp_check_point.'";
		// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
		// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
		</script>
		';
		//exit();
}  

$CheckMon = false;  
if($cp_point_number=='0'||$cp_point_number=='1'){
	$CheckMon = true;
}


?>


<script src="ajax/ajax_framework.js"> </script>
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<html lang="en">
<head>
  <meta charset="utf-8">
  	<meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ระบบจุดตรวจ รปภ.</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <link rel="stylesheet" href="fonts/thsarabunnew.css" />
  <link rel="stylesheet" href="plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css"> 
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
  <!-- Google Font: Source Sans Pro -->
</head>
<body class="hold-transition layout-top-nav layout-navbar-fixed" style="font-family:'SukhumvitSet', sans-serif; "> 
   <!--      <li class="nav-item "> 
            <a href="create.php" class="nav-link" style="color:#00CC99; font-size:14px;"> <strong>สมัครติวเตอร์</strong></a> 
      </li>
		 <li class="nav-item" style='background-color:#000000'>  
		 <a href="login_member.php" class="nav-link" style="color:#00CC99; font-size:14px;"> <strong>เข้าสู่ระบบ</strong></a>
		</li>
		-->
 
<!--  <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
    <div class="container">
      <a href="index.html" class="navbar-brand">
       <strong style="font-size:14px;">แฮปปี้แลนด์</strong>      </a>

  
 
        
		 
        
 
      </ul>
	   
    </div>
  </nav>-->
  </a>
  
  
  <!-- /.navbar --><style>
.container23 {
	display: flex;
	align-items: center;
	justify-content: center;
	min-height: 100vh;
}
</style>

<div class="container2">
  <link rel="stylesheet" href="css2/main.min.css">
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header"> 
      <div class="container"> 
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0"> <strong></strong></h1>
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </div>
    <!-- /.content-header --> 
    <iframe  id="iFm_save_target" name="iFm_save_target" src="" style="width:0px;height:0px;border:0"></iframe>
    <form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target">
      <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_18" />
      <input name="status" type="hidden" id="status" value="" />
      <input type="hidden" name="lp_agency" value="<?php echo $rs_agency['ag_id'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อผู้ใช้งาน" >   
      <input type="hidden" name="ag_token" value="<?php echo $rs_agency['ag_token'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อผู้ใช้งาน" >   
      <input type="hidden" name="lp_check_point" value="<?php echo $cd_cp_id ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อผู้ใช้งาน" >  
      <input type="hidden" id="lp_latitude" name="lp_latitude" value="<?php echo $rs['latitude'] ?>" class="form-control form-control-border"  placeholder="ชื่อผู้ใช้งาน" >  
      <input type="hidden" id="lp_longitude" name="lp_longitude" value="<?php echo $rs['latitude'] ?>" class="form-control form-control-border" placeholder="ชื่อผู้ใช้งาน" > 
      <input type="hidden" id="lp_cp_point_name" name="lp_cp_point_name" value="<?php echo $cp_point_name ?>" class="form-control form-control-border" placeholder="ชื่อผู้ใช้งาน" > 
      <input type="hidden" id="lp_ch_id" name="lp_ch_id" value="<?php echo $tmpKey ?>" class="form-control form-control-border" placeholder="ชื่อผู้ใช้งาน" >
      <input type="hidden" id="lp_number" name="lp_number" value="<?php echo $cp_point_number ?>" class="form-control form-control-border" placeholder="ชื่อผู้ใช้งาน" >
      <input type="hidden" id="ag_job" name="ag_job" value="หน่วยงาน <?php echo $rs_agency['ag_job']; ?>" class="form-control form-control-border" placeholder="ชื่อผู้ใช้งาน" >
	  
 
      <input type="hidden" id="lp_link_cp_ag_id" name="lp_link_cp_ag_id" value="<?php echo $cp_ag_id; ?>" class="form-control form-control-border" placeholder="ชื่อผู้ใช้งาน" >
      <input type="hidden" id="lp_link_cp_point_number" name="lp_link_cp_point_number" value="<?php echo $cp_point_number; ?>" class="form-control form-control-border" placeholder="ชื่อผู้ใช้งาน" >
      <input type="hidden" id="lp_time" name="lp_time" value="รอบวันที่ <?php echo fn_thai_date(date('Y-m-d'),'4')?> เวลา <?php echo $ch_work_time.' - '.$ch_end_time ?> น." class="form-control form-control-border" placeholder="ชื่อผู้ใช้งาน" >
	  
	  
	  
      <!-- Main content --> 
	        <div class="container">
	        
		
		<div class="row">  
          <div class="col-md-12 col-lg-12 col-md-12 col-xs-12">
            <!-- Widget: user widget style 1 -->
            <div class="card card-widget widget-user" >
              <!-- Add the bg color to the header using any of the bg-* classes -->
              <div class="widget-user-header bg-" style="background-color:#804040; color:#FFFFFF"><br />
                <h3  ><strong>
				ยินดีต้อนรับเข้าระบบบันทึกจุดตรวจ 
				<!--<input type="file" accept="image/*" capture="camera" />-->
</strong></h5>
              </div>
              <div class="widget-user-image"  > 
                <img  class="elevation-2 img-circle" src="logo1.png"  style="margin-top:25px;" > 
              </div>
               <div class="card-footer ">
                <ul class="nav flex-column">
                  <li class="nav-item"> <br>

				  <blockquote style="text-align:center; margin-top:10px;" >
                  <strong>หน่วยงาน <?php echo $rs_agency['ag_job']; ?><br>
				  จุด <?php if($cp_point_number=='0'){?> กะเช้า<?php }elseif($cp_point_number=='1'){ ?>กะดึก<?php }else{ ?>ที่ <?php echo ($cp_point_number-1).' '.$cp_point_name; ?><?php } ?> 
				  <br>รอบวันที่ <?php echo fn_thai_date(date('Y-m-d'),'4')?><br>				  เวลา <?php echo $ch_work_time.' - '.$ch_end_time ?> น.</strong> 
				  <input type="hidden" id="ls_check_head" name="ls_check_head" value="<?php echo $ch_work_time.' - '.$ch_end_time ?>" class="form-control form-control-border" placeholder="ชื่อผู้ใช้งาน" >
                </blockquote> 
                  </li>
			     </ul>
			  </div>
				  </h2>  
			 <div class="card-header ui-sortable-handle dark-mode" style="margin-top:1px;" >
                <h3 class="card-title">
                   
                </h3>  
					<div id="camera1_box" style="display: block;"  align="center">
	<input type=button value="กดเปิดกล้องเพื่อถ่ายรูปบุคคลยืนยันการตรวจ" id="camera" class="btn btn-block btn-warning btn-sm" style="color:#FFFFFF; " onClick="configure()">
	</div>
                     
					<div id="camera2_box" style="display:none;">
	<input type=button value="ถ่ายรูป" style="color:#FFFFFF"  id="camera2" class="btn btn-block btn-warning  btn-sm"   onClick="take_snapshot()">  
	</div>
				  </label>   
              </div> 
			  <div align="center">
	<div id="my_camera"  style="display:none; "></div>
	<div id="camera3"  style="display:none; ">
    <div id="results"></div>
	<input type="hidden" id="results2" name="lp_picture" value=""    class="btn  btn-warning btn-sm"  >  <br>
<strong>รูปถ่าย</strong>
	</div>
	</div>
	<!--<button type=button class="btn btn-block btn-danger btn-sm" onClick="getLocation()">กดเพื่อระบุตำแหน่ง</button>-->

<!--<p id="demo"></p>-->

<style>
div.fileinputs {
	   position: relative;
	   width:100%;
	   height:40px;
	 }
	 div.fakefile {
	   position: absolute;
	   top: 0px;
	   left: 0px;
	   z-index: 1; 
	   width: auto;
	   border-radius: 5px;
	   padding:0px;
	   height:100%;
	   width:100%; 
	   background-color: #FFCC00;
	   color: #000000;
	 }

	 input.file {
	   position: relative;
	   text-align: right;
	   -moz-opacity: 0;
	   filter: alpha(opacity: 0);
	   opacity: 0;
	   z-index: 2;
	 }
	 .wrapper boik{
  display: inline-flex;
  background: #F9F9F9;
  height: 65px;
  width: 100%;
  height:auto;
  align-items: center;
  justify-content: space-evenly;
  border-radius: 5px;
  padding: 2px 2px; 
  box-shadow: 5px 5px 30px rgba(0,0,0,0.2);
</style>
				
				<?php 
				
				for($i=0; $i<20; $i++){
				
				if($rs_checkpoint['cp_nameanswer'.$i]!=''){?>
				 <?php 
					
				?> 
				
				<style>
				.wrapper<?php echo $i ?>{
  display: inline-flex;
  background: #F9F9F9;
  height: 65px;
  width: 100%;
  align-items: center;
  justify-content: space-evenly;
  border-radius: 5px;
  padding: 2px 2px; 
  box-shadow: 5px 5px 30px rgba(0,0,0,0.2);
}
.wrapper<?php echo $i ?> .option<?php echo $i ?>{
  background: #fff;
  height: 100%;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-evenly;
  margin: 0 10px;
  border-radius: 5px;
  cursor: pointer;
  padding: 0 10px;
  border: 2px solid lightgrey;
  transition: all 0.3s ease;
}
.wrapper<?php echo $i ?> .option<?php echo $i ?> .dot<?php echo $i ?>{
  height: 20px;
  width: 20px;
  background: #d9d9d9;
  border-radius: 50%;
  position: relative;
}
.wrapper<?php echo $i ?> .option-1<?php echo $i ?> .dot<?php echo $i ?>::before{
  position: absolute;
  content: "";
  top: 4px;
  left: 4px;
  width: 12px;
  height: 12px;
  background: #00CC00;
  border-radius: 50%;
  opacity: 0;
  transform: scale(1.5);
  transition: all 0.3s ease;
}
.wrapper<?php echo $i ?> .option-2<?php echo $i ?> .dot<?php echo $i ?>::before{
  position: absolute;
  content: "";
  top: 4px;
  left: 4px;
  width: 12px;
  height: 12px;
  background: #FF3300;
  border-radius: 50%;
  opacity: 0;
  transform: scale(1.5);
  transition: all 0.3s ease;
}
input[type="radio"]{
  display: none;
}
#option-1<?php echo $i ?>:checked:checked ~ .option-1<?php echo $i ?>{
  border-color: #006600;
  background: #009900;
}
#option-2<?php echo $i ?>:checked:checked ~ .option-2<?php echo $i ?>{
  border-color: #CC0000;
  background: #FF0000;
}
#option-1<?php echo $i ?>:checked:checked ~ .option-1<?php echo $i ?> .dot,
#option-2<?php echo $i ?>:checked:checked ~ .option-2<?php echo $i ?> .dot{
background-color:#33FF00;
}
#option-1<?php echo $i ?>:checked:checked ~ .option-1<?php echo $i ?> .dot<?php echo $i ?>::before,
#option-2<?php echo $i ?>:checked:checked ~ .option-2<?php echo $i ?> .dot<?php echo $i ?>::before{
  opacity: 1;
  transform: scale(1);
}
.wrapper<?php echo $i ?> .option<?php echo $i ?> span{
  font-size: 15px;
  color: #808080;
}
#option-1<?php echo $i ?>:checked:checked ~ .option-1<?php echo $i ?> span,
#option-2<?php echo $i ?>:checked:checked ~ .option-2<?php echo $i ?> span{
  color: #fff;
}
				</style>
				
                	  <div class="card-header ui-sortable-handle dark-mode" style="margin-top:10px;" >
					  	
				<input type="hidden" name="lp_answer_picture_tmpFoder[<?php echo $i ?>]" value="<?php echo $tmpFolderFILE; ?>" >
		<input type="hidden" name="lp_question1[<?php echo $i ?>]" value="<?php echo $rs_checkpoint['cp_nameanswer'.$i];?>" id="sizeWeight<?php echo $i ?>" checked="checked" />
                <h3 class="card-title" style="color:#FFFFFF">
                 
                  <div style="font-size:30px;  font-weight:bold;"> <i class="fas fa-exclamation-triangle mr-1" style="color:#FFFF00; font-size:30px; font-weight:bold; "></i> <?php echo $rs_checkpoint['cp_nameanswer'.$i];?></div>
                </h3><br> 
				  <div class="wrapper<?php echo $i ?>">
 <input type="radio" name="lp_answer[<?php echo $i ?>]" onClick="fn_checkVa(<?php echo $i ?>,'1')" id="option-1<?php echo $i ?>" value="1" checked>
 <input type="radio" name="lp_answer[<?php echo $i ?>]" onClick="fn_checkVa(<?php echo $i ?>,'2')" id="option-2<?php echo $i ?>" value="2"> 
   
 <label for="option-1<?php echo $i ?>" class="option<?php echo $i ?> option-1<?php echo $i ?>">
     <div class="dot<?php echo $i ?>"></div>
      <span>ปกติ</span>
      </label>
   <label for="option-2<?php echo $i ?>" class="option<?php echo $i ?> option-2<?php echo $i ?>">
     <div class="dot<?php echo $i ?>"></div>
      <span>ไม่ปกติ</span>
   </label>      

   </div>    
                
				
				<br> 
 
<div class="form-group"  id="box_dis<?php echo $i ?>" style="display:none"><br>

<div class="fileinputs mb-3"> 
	
										 <input  type="hidden" id="box_ck<?php echo $i ?>" > 
										 <input style="width:100%; height:100%" type="file" class="custom-file-input" id="uploadImageAttDis1<?php echo $i ?>" accept="image/*" capture="camera" name="lp_answer_picture_11[<?php echo $i ?>]" onChange="loadImageFile21<?php echo $i ?>();" > 
                                           <div class="fakefile">
                                             <div class="" style="margin-top:10px;" align="center"><i class="fa fa-fw fa-camera"></i>&nbsp;&nbsp;&nbsp;<strong>ถ่ายภาพเหตุการณ์ไม่ปกติ ภาพที่ 1</strong></div>
                                           </div><span id="showtext310<?php echo $i ?>"> </span> 
                                         </div>
										  <div class="row" >
											<div class="col-sm-2"></div>
											<div class="col-sm-6" id="img234<?php echo $i ?>" style="display:none; width:50%; height:50%;"> 
												<img id="blah234<?php echo $i ?>" class="img-fluid" src="#" alt="" /> 
									   <!--   <textarea class="form-control" rows="3" id="blah12<?php echo $i ?>"    name="lp_answer_picture[<?php echo $i ?>]"placeholder="กรอกข้อมูลที่ต้องการบันทึกเหตุการณ์เพิ่มเติม"></textarea>-->
											  <input type="hidden" id="blah1234<?php echo $i ?>"  name="lp_answer_picture_1[<?php echo $i ?>]" value="" required>
											 <!-- <div id="blah12<?php echo $i ?>"></div>-->
											</div> 
											</div> 
							 
											 
											
<div class="fileinputs mb-3"> 
	<input type="hidden" id="box_ck2<?php echo $i ?>" >
										 <input style="width:100%; height:100%" type="file" class="custom-file-input" id="uploadImageAttDis12<?php echo $i ?>" accept="image/*" capture="camera" name="lp_answer_picture_22[<?php echo $i ?>]" onChange="loadImageFile212<?php echo $i ?>();" > 
										
                                           <div class="fakefile">
                                             <div class="" style="margin-top:10px;" align="center"><i class="fa fa-fw fa-camera"></i>&nbsp;&nbsp;&nbsp;<strong>ถ่ายภาพเหตุการณ์ไม่ปกติ ภาพที่ 2</strong></div>
                                           </div><span id="showtext310<?php echo $i ?>"> </span> 
                                         </div>
										  <div class="row" >
											<div class="col-sm-2"></div>
											<div class="col-sm-6" id="img2345<?php echo $i ?>" style="display:none; width:50%; height:50%;"> 
												<img id="blah2345<?php echo $i ?>" class="img-fluid" src="#" alt="" /> 
									   <!--   <textarea class="form-control" rows="3" id="blah12<?php echo $i ?>"    name="lp_answer_picture[<?php echo $i ?>]"placeholder="กรอกข้อมูลที่ต้องการบันทึกเหตุการณ์เพิ่มเติม"></textarea>-->
											  <input type="hidden" id="blah12345<?php echo $i ?>"  name="lp_answer_picture_2[<?php echo $i ?>]" value="" required>
											 <!-- <div id="blah12<?php echo $i ?>"></div>-->
											</div> 
											</div> 
							 
										
<div class="input-group mb-3"  style=" margin-top:10px;">
                  <div class="input-group-prepend">
                    <span class="input-group-text" style="background-color:#FFFFFF"><label style="color:#FF8000"><i class="far fa-bell"></i> <br>ระบุบเหตุผล</label></span>
                  </div>
				  
                        <textarea class="form-control is-warning" name="lp_answer_detail[<?php echo $i ?>]" id="inputWarning"rows="3" placeholder="Enter ..."></textarea>
                </div> 
                      </div> <br>
<br>

					  <div class="fileinputs mb-3"> 
										 <input style="width:100%; height:100%" type="file" class="custom-file-input" id="uploadImage<?php echo $i ?>" accept="image/*" capture="camera" name="lp_answer_picture[<?php echo $i ?>]" onChange="loadImageFile<?php echo $i ?>();" >
                                           <div  class="fakefile">
                                             <div class="" style="margin-top:10px;" align="center"><i  class="fa fa-fw fa-camera"></i>&nbsp;&nbsp;&nbsp;<strong>ถ่ายภาพ <?php // echo $CheckImg?></strong></div>
                                           </div><span id="showtext10"></span> 
                                         </div> 
					 <div class="row" >
					<div class="col-sm-2"></div>
                    <div class="col-sm-6" id="img2<?php echo $i ?>" style="display:none; width:50%; height:50%;"> 
                		<img id="blah2<?php echo $i ?>" class="img-fluid" src="#" alt="" /> 
               <!--   <textarea class="form-control" rows="3" id="blah12<?php echo $i ?>"    name="lp_answer_picture[<?php echo $i ?>]"placeholder="กรอกข้อมูลที่ต้องการบันทึกเหตุการณ์เพิ่มเติม"></textarea>-->
                      <input type="hidden" id="blah12<?php echo $i ?>"  name="lp_answer_picture[<?php echo $i ?>]" value="" required>
					 <!-- <div id="blah12<?php echo $i ?>"></div>-->
                    </div> 
					</div>
              </div>
				<?php } } ?>
                <!-- /.d-flex -->
                <!-- CSS -->
    <style>
    #my_camera{
        width: 80%;
        height: 100%;
        border: 1px solid black;
    }
	</style>

	<!-- -->
	
 

<script>
function fn_checkVa(value,type){
		if(value==1){
			if(type==2){
				document.getElementById('box_dis1').style.display='block';
				document.getElementById("box_ck1").value='1';
				document.getElementById("box_ck21").value='1'; 
			}else{
				document.getElementById('box_dis1').style.display='none';
				document.getElementById("box_ck1").value='0';
				document.getElementById("box_ck21").value='0'; 
			}
		}else if(value==2){
			if(type==2){
				document.getElementById('box_dis2').style.display='block';
				document.getElementById("box_ck2").value='1'; 
				 document.getElementById("lp_answer_picture2").required = true;
				 document.getElementById("blah122").required = true; 
			}else{
				document.getElementById('box_dis2').style.display='none';
				document.getElementById("box_ck2").value='0';
				 document.getElementById("lp_answer_picture2").required = false;
				 document.getElementById("blah122").required = false; 
			}
		}else if(value==3){
			if(type==2){
				document.getElementById('box_dis3').style.display='block';
				document.getElementById("box_ck3").value='1';
				 document.getElementById("lp_answer_picture3").required = true;
			}else{
				document.getElementById('box_dis3').style.display='none';
				document.getElementById("box_ck3").value='0';
				 document.getElementById("lp_answer_picture3").required = false;
			}
		}else if(value==4){
			if(type==2){
				document.getElementById('box_dis4').style.display='block';
				document.getElementById("box_ck4").value='1';
				 document.getElementById("lp_answer_picture4").required = true;
			}else{
				document.getElementById('box_dis4').style.display='none';
				document.getElementById("box_ck4").value='0';
				 document.getElementById("lp_answer_picture4").required = false;
			}
		}else if(value==5){
			if(type==2){
				document.getElementById('box_dis5').style.display='block';
				document.getElementById("box_ck5").value='1';
				 document.getElementById("lp_answer_picture5").required = true;
			}else{
				document.getElementById('box_dis5').style.display='none';
				document.getElementById("box_ck5").value='0';
				 document.getElementById("lp_answer_picture5").required = false;
			}
		}else if(value==6){
			if(type==2){
				document.getElementById('box_dis6').style.display='block';
				document.getElementById("box_ck6").value='1';
				 document.getElementById("lp_answer_picture6").required = true;
			}else{
				document.getElementById('box_dis6').style.display='none';
				document.getElementById("box_ck6").value='0';
				 document.getElementById("lp_answer_picture6").required = false;
			}
		}else if(value==7){
			if(type==2){
				document.getElementById('box_dis7').style.display='block';
				document.getElementById("box_ck7").value='1';
				 document.getElementById("lp_answer_picture7").required = true;
			}else{
				document.getElementById('box_dis7').style.display='none';
				document.getElementById("box_ck7").value='0';
				 document.getElementById("lp_answer_picture7").required = false;
			}
		}else if(value==8){
			if(type==2){
				document.getElementById('box_dis8').style.display='block';
				document.getElementById("box_ck8").value='1';
				 document.getElementById("lp_answer_picture8").required = true;
			}else{
				document.getElementById('box_dis8').style.display='none';
				document.getElementById("box_ck8").value='0';
				 document.getElementById("lp_answer_picture8").required = false;
			}
		}else if(value==9){
			if(type==2){
				document.getElementById('box_dis9').style.display='block';
				document.getElementById("box_ck9").value='1';
				 document.getElementById("lp_answer_picture9").required = true;
			}else{
				document.getElementById('box_dis9').style.display='none';
				document.getElementById("box_ck9").value='0';
				 document.getElementById("lp_answer_picture9").required = false;
			}
		}else if(value==10){
			if(type==2){
				document.getElementById('box_dis10').style.display='block';
				document.getElementById("box_ck10").value='1';
				 document.getElementById("lp_answer_picture10").required = true;
			}else{
				document.getElementById('box_dis10').style.display='none';
				document.getElementById("box_ck10").value='0';
				 document.getElementById("lp_answer_picture10").required = false;
			}
		}
}
function myMap() {
var mapProp= {
  center:new google.maps.LatLng(51.508742,-0.120850),
  zoom:5,
};
var map = new google.maps.Map(document.getElementById("googleMap"),mapProp);
}
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_KEY&callback=myMap"></script>



<script>
var x = document.getElementById("demo");
getLocation();
function getLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(showPosition);
  } else { 
    x.innerHTML = "Geolocation is not supported by this browser.";
  }
}

function showPosition(position) {
  x.innerHTML = "Latitude: " + position.coords.latitude + 
  "<br>Longitude: " + position.coords.longitude;
  document.getElementById("lp_latitude").value=position.coords.latitude;
  document.getElementById("lp_longitude").value=position.coords.longitude;
}
</script>
	<!-- Script -->
 <script src="http://code.jquery.com/jquery-latest.js"></script>
 
 <!-- <div class="card-header ui-sortable-handle dark-mode" style="margin-top:1px; color:#FFFFFF;" >
                <strong>เพิ่มรูปภาพเหตุการณ์ปัจุบัน</strong>
                  <ul class="nav nav-pills ml-auto">
                    <div class="custom-file"> 
                      <label class="custom-file-label" for="customFile">กดเพิ่อบันทึกรูปภาพที่ 1</label>
                      <input id="uploadImage" type="file" name="file1" onChange="loadImageFile();"  accept="image/*" capture="camera" class="custom-file-input"  /> 
                    </div>
                    <div id="img1" style="display:none;"> 
                		<img id="blah1" style="width:100%; height:100%";  src="#" alt="" />
                      <input type="hidden" id="blah11"  name="lp_attachment100" value="">
                    </div> 
                    <div class="custom-file">
                      <input type="file" accept="image/*" capture="camera" class="custom-file-input" name="file2" onChange="readURL2(this);" id="customFile">
                      <label class="custom-file-label" for="customFile">กดเพิ่อบันทึกรูปภาพที่ 2</label>
                    </div>
                    <div id="img2" style="display:none;"> 
                		<img id="blah2" style="width:100%; height:100%";  src="#" alt="" /> 
                      <input type="hidden" id="blah12"  name="lp_attachment200" value="">
                    </div>
					<div class="custom-file">
                      <input type="file" accept="image/*" capture="camera" name="file3" class="custom-file-input" onChange="readURL3(this);" id="customFile">
                      <label class="custom-file-label" for="customFile">กดเพิ่อบันทึกรูปภาพที่ 3</label>
                    </div>
                    <div id="img3" style="display:none;"> 
                		<img id="blah3" style="width:100%; height:100%";  src="#" alt="" /> 
                      <input type="hidden" id="blah13"  name="lp_attachment300" value="">
                    </div>
                  </ul> 
              </div> -->
			  <?php if($CheckMon==true){?>
			 <div class="card-header ui-sortable-handle dark-mode" style="margin-top:1px;" >
                <h3 class="card-title">
                   
                </h3>  
					<div id="camera1_box" style="display: block;"  align="center">  
					<select style="background-color: #FF9900;color: #FFFFFF; text-align:center" id="ls_user_security"  name="ls_user_security"  class="form-control "  > 
					<option value="" >- กรุณาเลือกชื่อพนักงาน -</option>
						<?php 
						$sql="SELECT *  
							  FROM tb_security_name As Tbsn 
							  LEFT JOIN tb_agency AS Tbag
							  ON Tbsn.sn_agency = Tbag.ag_id
							  WHERE sn_agency = '".$cp_ag_id."'
							  ";
						$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
						$num_rows =mysqli_num_rows($query);
						if($num_rows>=1){
							while($rs=mysqli_fetch_array($query)) {
					  ?>
							<option value="<?php echo $rs['sn_id']?>|<?php echo $rs['sn_prefix_to']?> <?php echo $rs['sn_prefix']?><?php echo $rs['sn_name']?> <?php echo $rs['sn_lastname']?>"><?php echo $rs['sn_prefix_to']?> <?php echo $rs['sn_prefix']?><?php echo $rs['sn_name']?> <?php echo $rs['sn_lastname']?></option>								 
						<?php 
							}					
						}
						?>
					<option value="0|พนักงานสแปร์" >- พนักงานสแปร์ -</option>
					</select>
					  </div>
			  </div> 
			  <?php } ?>
  <div class="card-header ui-sortable-handle dark-mode" style="margin-top:1px; color:#FFFFFF;" >
                
                <h3 class="card-title">
                  <i class="fas fa-chart-pie mr-1"></i> <strong>บันทึกเพิ่มเติม</strong>
                </h3> 
                <br> 
                  <textarea class="form-control" rows="3" name="lp_more_details" placeholder="กรอกข้อมูลที่ต้องการบันทึกเหตุการณ์เพิ่มเติม"></textarea>
                   
              </div>
	<script type="text/javascript" src="camera/webcamjs/webcam.min.js"></script>

	<!-- Code to handle taking the snapshot and displaying it locally -->
	<script language="JavaScript">
		
		// Configure a few settings and attach camera
		function configure(){
			 
	document.getElementById('my_camera').style.display='block';
	document.getElementById('camera1_box').style.display='none';
	document.getElementById('camera2_box').style.display='block'; 
 	document.getElementById('camera').value = 'ถ่ายใหม่';
	
			Webcam.set({
				width: 250,
				height: 340,
				image_format: 'jpeg',
				jpeg_quality: 190
			});
			Webcam.attach( '#my_camera' );
		}
		// A button for taking snaps
		

		// preload shutter audio clip
		var shutter = new Audio();
		shutter.autoplay = false;
		shutter.src = navigator.userAgent.match(/Firefox/) ? 'shutter.ogg' : 'shutter.mp3';

		function take_snapshot() {
			
	document.getElementById('my_camera').style.display='block';
	document.getElementById('camera1_box').style.display='block';
	document.getElementById('camera2_box').style.display='none';
	document.getElementById('camera3').style.display='block';
			// play sound effect
			//shutter.play();

			// take snapshot and get image data
			Webcam.snap( function(data_uri) {
				// display results in page
				document.getElementById('results2').value = data_uri;
				document.getElementById('results').innerHTML = 
					'<img id="imageprev" src="'+data_uri+'"/>';
			} );

		//	Webcam.reset();
		}

		function saveSnap(){
			// Get base64 value from <img id='imageprev'> source
			var base64image =  document.getElementById("imageprev").src;

			 Webcam.upload( base64image, 'upload.php', function(code, text) {
				 console.log('Save successfully');
				 //console.log(text);
            });

		}
	</script>
                  <div style=" text-align:center"><br>

				  
					          <button id="btnFetch"  type="submit" class="btn btn-success " style="text-align:left; padding:10px;"> <span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span>  ยืนยัน การตรวจจุด</button>
							   
							  
			  </div><br>

</div> 
 
 <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css">

<div style="margin:3em;"> 
</div>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js"></script>
<script>
<?php if($CheckImg==0){?>
$(document).ready(function() {
    $("#btnFetch").click(function() {
			
			<?php  
			for($i=0; $i<20; $i++){
				if($rs_checkpoint['cp_nameanswer'.$i]!=''){
			?> 
			if(document.getElementById("box_ck<?php echo $i?>").value=='1'){
				if(document.getElementById("blah1234<?php echo $i?>").value==''){ 
				
				alert('กรุณาบันทึกรูปเหตุการณ์ไม่ปกติคำถามที่ 1');
				return  false;
				} 
			}else{
				if(document.getElementById("blah12<?php echo $i?>").value == ''){
					alert('กรุณาบันทึกรูปเหตุการณ์ที่ <?php echo $i?>');
					return  false;
				}
			}
			<?php 
				}
			}
			?>
			  <?php if($CheckMon==true){?>
			  if(document.getElementById("results2").value == ''){
					alert('กรุณากดเปิดกล้องเพื่อถ่ายรูปบุคคลยืนยันการตรวจ');
					return  false;
				}
				 if(document.getElementById("ls_user_security").value == ''){
					alert('กรุณาเลือกชื่อพนักงาน');
					return  false;
				}
			  <?php } ?>
			document.getElementById("frm_update_data").submit();
<?php } ?>	 
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
 
                        <!--<input type="checkbox" id="checkboxDanger2" required >
                        <label for="checkboxDanger2">
                        </label>
                      </div>  
                         <span class="  badge bg-info"> ยอมรับข้อตกลง</span> -->
                        
                    <br />

                  </li> 
                </ul>
          </div>
              </div>
              </div>
              </div>
              </div>
            </div>
            <!-- /.widget-user -->
          </div>
          <!-- /.col --> 
            
		
<style>

	</style>
	  
     <!-- <div style=" text-align:center">
        <button type="submit" class="btn btn-success " style="text-align:left; margin-top:10px; margin-left:10px;"> <span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span> ยืนยัน </button>
      </div>-->
    </form>
    <!-- /.row -->
	    </div>
    <!-- /.container-fluid -->
</div>
<!-- /.content --> 
<!-- /.content-wrapper -->
<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
  <!-- Control sidebar content goes here -->
</aside>
<!-- /.control-sidebar -->
<!-- Main Footer -->
<!--<a id="back-to-top" href="#" class="btn btn-primary back-to-top" role="button" aria-label="Scroll to top">
      <i class="fas fa-chevron-up"></i>
    </a>-->
﻿<!-- <footer class="main-footer">
    <div class="float-right d-none d-sm-block">
      <b>Version</b> 1.0
    </div>
    <strong>Copyright &copy; 2023 <a href="#"></a></strong> 
  </footer>-->

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>
<!-- AdminLTE for demo purposes -->
<!--<script src="dist/js/demo.js"></script>-->
<!-- Page specific script -->

</body>
</html>
</div>
<!-- ./wrapper -->
<!-- REQUIRED SCRIPTS -->
</body></html><script>
function fn_approve_member(member_id) { 
	 window.location.href="menu_01.php?member_id"+member_id+"&SubmitH=Submit_5";
}
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

<script src="plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
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



<html>
<head>
<meta content="text/html; charset=UTF-8" http-equiv="Content-Type" />
<title>Image preview example</title>
<script type="text/javascript">
  <?php 
		 for($i=1; $i<21; $i++){
	?> 
	
oFReader<?php echo $i ?> = new FileReader(), rFilter = /^(?:image\/bmp|image\/cis\-cod|image\/gif|image\/ief|image\/jpeg|image\/jpeg|image\/jpeg|image\/pipeg|image\/png|image\/svg\+xml|image\/tiff|image\/x\-cmu\-raster|image\/x\-cmx|image\/x\-icon|image\/x\-portable\-anymap|image\/x\-portable\-bitmap|image\/x\-portable\-graymap|image\/x\-portable\-pixmap|image\/x\-rgb|image\/x\-xbitmap|image\/x\-xpixmap|image\/x\-xwindowdump)$/i;

oFReader<?php echo $i ?>.onload = function (oFREvent<?php echo $i ?>) {

  var img<?php echo $i ?>=new Image();
  img<?php echo $i ?>.onload=function(){
  var dile<?php echo $i ?>
   //   document.getElementById("originalImg").src=img.src;
      var canvas<?php echo $i ?>=document.createElement("canvas");
      var ctx<?php echo $i ?>=canvas<?php echo $i ?>.getContext("2d");
	  console.log(img<?php echo $i ?>.width);
	  if(img<?php echo $i ?>.width>1500){
      canvas<?php echo $i ?>.width=img<?php echo $i ?>.width/15;
      canvas<?php echo $i ?>.height=img<?php echo $i ?>.height/15;
	  }else{
      canvas<?php echo $i ?>.width=img<?php echo $i ?>.width/6;
      canvas<?php echo $i ?>.height=img<?php echo $i ?>.height/6;
	  } 
      ctx<?php echo $i ?>.drawImage(img<?php echo $i ?>,0,0,img<?php echo $i ?>.width,img<?php echo $i ?>.height,0,0,canvas<?php echo $i ?>.width,canvas<?php echo $i ?>.height);
      document.getElementById("blah2<?php echo $i ?>").src = canvas<?php echo $i ?>.toDataURL();
	  console.log(canvas<?php echo $i ?>.toDataURL());
	  document.getElementById("blah12<?php echo $i ?>").value=canvas<?php echo $i ?>.toDataURL();
  }
		document.getElementById('img2<?php echo $i ?>').style.display='block';
  img<?php echo $i ?>.src=oFREvent<?php echo $i ?>.target.result;
};

function loadImageFile<?php echo $i ?>() {
  if (document.getElementById("uploadImage<?php echo $i ?>").files.length === 0) { return; }
  var oFile<?php echo $i ?> = document.getElementById("uploadImage<?php echo $i ?>").files[0];
  if (!rFilter.test(oFile<?php echo $i ?>.type)) { alert("You must select a valid image file!"); return; }
  oFReader<?php echo $i ?>.readAsDataURL(oFile<?php echo $i ?>);
}
<?php } ?>
</script>
<script type="text/javascript">
  <?php 
		 for($i=1; $i<21; $i++){
	?> 
	
oFReader3<?php echo $i ?> = new FileReader(), rFilter = /^(?:image\/bmp|image\/cis\-cod|image\/gif|image\/ief|image\/jpeg|image\/jpeg|image\/jpeg|image\/pipeg|image\/png|image\/svg\+xml|image\/tiff|image\/x\-cmu\-raster|image\/x\-cmx|image\/x\-icon|image\/x\-portable\-anymap|image\/x\-portable\-bitmap|image\/x\-portable\-graymap|image\/x\-portable\-pixmap|image\/x\-rgb|image\/x\-xbitmap|image\/x\-xpixmap|image\/x\-xwindowdump)$/i;

oFReader3<?php echo $i ?>.onload = function (oFREvent3<?php echo $i ?>) {

  var img<?php echo $i ?>=new Image();
  img<?php echo $i ?>.onload=function(){
  var dile<?php echo $i ?>
   //   document.getElementById("originalImg").src=img.src;
      var canvas<?php echo $i ?>=document.createElement("canvas");
      var ctx<?php echo $i ?>=canvas<?php echo $i ?>.getContext("2d");
	  console.log(img<?php echo $i ?>.width);
	  if(img<?php echo $i ?>.width>1500){
      canvas<?php echo $i ?>.width=img<?php echo $i ?>.width/15;
      canvas<?php echo $i ?>.height=img<?php echo $i ?>.height/15;
	  }else{
      canvas<?php echo $i ?>.width=img<?php echo $i ?>.width/6;
      canvas<?php echo $i ?>.height=img<?php echo $i ?>.height/6;
	  } 
      ctx<?php echo $i ?>.drawImage(img<?php echo $i ?>,0,0,img<?php echo $i ?>.width,img<?php echo $i ?>.height,0,0,canvas<?php echo $i ?>.width,canvas<?php echo $i ?>.height);
      document.getElementById("blah234<?php echo $i ?>").src = canvas<?php echo $i ?>.toDataURL();
	  console.log(canvas<?php echo $i ?>.toDataURL());
	  document.getElementById("blah1234<?php echo $i ?>").value=canvas<?php echo $i ?>.toDataURL();
  }
		document.getElementById('img234<?php echo $i ?>').style.display='block';
  img<?php echo $i ?>.src=oFREvent3<?php echo $i ?>.target.result;
};

function loadImageFile21<?php echo $i ?>() {
  if (document.getElementById("uploadImageAttDis1<?php echo $i ?>").files.length === 0) { return; }
  var oFile<?php echo $i ?> = document.getElementById("uploadImageAttDis1<?php echo $i ?>").files[0];
  if (!rFilter.test(oFile<?php echo $i ?>.type)) { alert("You must select a valid image file!"); return; }
  oFReader3<?php echo $i ?>.readAsDataURL(oFile<?php echo $i ?>);
}
<?php } ?>


  <?php 
		 for($i=1; $i<21; $i++){
	?> 
	
oFReader34<?php echo $i ?> = new FileReader(), rFilter = /^(?:image\/bmp|image\/cis\-cod|image\/gif|image\/ief|image\/jpeg|image\/jpeg|image\/jpeg|image\/pipeg|image\/png|image\/svg\+xml|image\/tiff|image\/x\-cmu\-raster|image\/x\-cmx|image\/x\-icon|image\/x\-portable\-anymap|image\/x\-portable\-bitmap|image\/x\-portable\-graymap|image\/x\-portable\-pixmap|image\/x\-rgb|image\/x\-xbitmap|image\/x\-xpixmap|image\/x\-xwindowdump)$/i;

oFReader34<?php echo $i ?>.onload = function (oFREvent34<?php echo $i ?>) {

  var img<?php echo $i ?>=new Image();
  img<?php echo $i ?>.onload=function(){
  var dile<?php echo $i ?>
   //   document.getElementById("originalImg").src=img.src;
      var canvas<?php echo $i ?>=document.createElement("canvas");
      var ctx<?php echo $i ?>=canvas<?php echo $i ?>.getContext("2d");
	  console.log(img<?php echo $i ?>.width);
	  if(img<?php echo $i ?>.width>1500){
      canvas<?php echo $i ?>.width=img<?php echo $i ?>.width/15;
      canvas<?php echo $i ?>.height=img<?php echo $i ?>.height/15;
	  }else{
      canvas<?php echo $i ?>.width=img<?php echo $i ?>.width/6;
      canvas<?php echo $i ?>.height=img<?php echo $i ?>.height/6;
	  } 
      ctx<?php echo $i ?>.drawImage(img<?php echo $i ?>,0,0,img<?php echo $i ?>.width,img<?php echo $i ?>.height,0,0,canvas<?php echo $i ?>.width,canvas<?php echo $i ?>.height);
      document.getElementById("blah2345<?php echo $i ?>").src = canvas<?php echo $i ?>.toDataURL();
	  console.log(canvas<?php echo $i ?>.toDataURL());
	  document.getElementById("blah12345<?php echo $i ?>").value=canvas<?php echo $i ?>.toDataURL();
  }
		document.getElementById('img2345<?php echo $i ?>').style.display='block';
  img<?php echo $i ?>.src=oFREvent34<?php echo $i ?>.target.result;
};

function loadImageFile212<?php echo $i ?>() {
  if (document.getElementById("uploadImageAttDis12<?php echo $i ?>").files.length === 0) { return; }
  var oFile<?php echo $i ?> = document.getElementById("uploadImageAttDis12<?php echo $i ?>").files[0];
  if (!rFilter.test(oFile<?php echo $i ?>.type)) { alert("You must select a valid image file!"); return; }
  oFReader34<?php echo $i ?>.readAsDataURL(oFile<?php echo $i ?>);
}
<?php } ?>
</script>
  
											
<script type="text/javascript">
 //   
//   function readURL<?php echo $i ?>(input) {
//	 console.log(input.files[0]);
//	 if (input.files && input.files[0]) {
//	   var reader = new FileReader();
//
//	   reader.onload = function(e) {
//		 $('#blah<?php echo $i ?>').attr('src', e.target.result);
//		 $('#blah1<?php echo $i ?>').attr('value', e.target.result);
//	   }
//		document.getElementById('img<?php echo $i ?>').style.display='block';
//	   reader.readAsDataURL(input.files[0]);
//	 }
//   }
//    
//   function readURL2<?php echo $i ?>(input) {
//	 console.log(input.files[0]);
//	 if (input.files && input.files[0]) {
//	   var reader = new FileReader();
//
//	   reader.onload = function(e) {
//		 $('#blah2<?php echo $i ?>').attr('src', e.target.result);
//		 $('#blah12<?php echo $i ?>').attr('value', e.target.result);
//	   }
//		document.getElementById('img2<?php echo $i ?>').style.display='block';
//	   reader.readAsDataURL(input.files[0]);
//	 }
//   }  
 
 </script>    
 <!--<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>

<body  >
  <form name="uploadForm">
    <table>
      <tbody>
        <tr>
          <td><img id="originalImg"/></td>
          <td><img id="uploadPreview"/><input  id="uploadImage55" type="text" name="myPhoto" onChange="loadImageFile();" /></td>
          <td><input id="uploadImage" accept="image/*" capture="camera" type="file" name="myPhoto" onChange="loadImageFile();" /></td>
        </tr>
      </tbody>
    </table>
  </form>
</body>
</html>-->