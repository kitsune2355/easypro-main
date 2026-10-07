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
}else{
  $rs_cp=mysqli_fetch_array($query_cp);
  	$cp_point_name = $rs_cp['cp_point_name']; 
echo $rs_cp['cp_point_number'].'
		<script language="javascript">
		alert("ไม่อยู่ช่วงตรวจจุด");
	//	window.close()
	//	 window.parent.location.href="check.php?cp_ag_id='.$lp_agency.'&cp_point_number='.$lp_check_point.'";
		// window.parent.fn_alert1(\'บันทึกข้อมูลเรียบร้อยแล้ว\',\'success-alert\');
		// var Dist = setTimeout(\'window.parent.location.href="setting_customer_list.php"\', 1500);
		</script>
		';
		//exit();
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
  <title>แฮปปี้แลนด์</title>
  
  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->

  <!-- Google Font -->
  <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="bower_components/Ionicons/css/ionicons.min.css">
  <link rel="stylesheet" href="fonts/thsarabunnew.css" />
  <link rel="stylesheet" href="plugins/select2/css/select2.min.css">
  <link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css"> 
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js" type="text/javascript"></script>
<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>
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
      <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_8" />
      <input name="status" type="hidden" id="status" value="" />
      <input type="hidden" name="lp_agency" value="<?php echo $rs_agency['ag_id'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อผู้ใช้งาน" >   
      <input type="hidden" name="ag_token" value="<?php echo $rs_agency['ag_token'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อผู้ใช้งาน" >   
      <input type="hidden" name="lp_check_point" value="<?php echo $rs_checkpoint['cd_cp_id'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อผู้ใช้งาน" >  
      <input type="hidden" id="lp_latitude" name="lp_latitude" value="<?php echo $rs['latitude'] ?>" class="form-control form-control-border"  placeholder="ชื่อผู้ใช้งาน" >  
      <input type="hidden" id="lp_longitude" name="lp_longitude" value="<?php echo $rs['latitude'] ?>" class="form-control form-control-border" placeholder="ชื่อผู้ใช้งาน" > 
      <input type="hidden" id="lp_ch_id" name="lp_ch_id" value="<?php echo $tmpKey ?>" class="form-control form-control-border" placeholder="ชื่อผู้ใช้งาน" >
      <input type="hidden" id="lp_number" name="lp_number" value="<?php echo $rs_checkpoint['cd_number'] ?>" class="form-control form-control-border" placeholder="ชื่อผู้ใช้งาน" >
      <input type="hidden" id="ag_job" name="ag_job" value="หน่วยงาน <?php echo $rs_agency['ag_job']; ?>" class="form-control form-control-border" placeholder="ชื่อผู้ใช้งาน" >
      <input type="hidden" id="lp_time" name="lp_time" value="รอบวันที่ <?php echo fn_thai_date(date('Y-m-d'),'4')?> เวลา <?php echo $ch_work_time.' - '.$ch_end_time ?> น." class="form-control form-control-border" placeholder="ชื่อผู้ใช้งาน" >
	  
	  
	  
      <!-- Main content --> 
	        <div class="container">
	        
		
		<div class="row">  
          <div class="col-md-12 col-lg-12 col-md-12 col-xs-12">
            <!-- Widget: user widget style 1 -->
            <div class="card card-widget widget-user" >
              <!-- Add the bg color to the header using any of the bg-* classes -->
              <div class="widget-user-header bg-" style="background-color:#804040; color:#FFFFFF; height: auto"><br />
                <h5  ><strong><strong>หน่วยงาน <?php echo $rs_agency['ag_job']; ?><br>
				 <!-- จุด <?php if($cp_point_number=='0'){?> กะเช้า<?php }elseif($cp_point_number=='1'){ ?>กะดึก<?php }else{ ?>ที่ <?php echo ($cp_point_number-1).' '.$cp_point_name; ?><?php } ?> -->
				  <br>รอบวันที่ <?php echo fn_thai_date(date('Y-m-d'),'4')?> <br>เวลา <?php echo $ch_work_time.' - '.$ch_end_time ?> น.</strong> 
				<!--<input type="file" accept="image/*" capture="camera" />-->
</strong></h5>
              </div>
              <div class="widget-user-image"  > 
                <img  class="elevation-2 img-circle" src="logo1.png"  style="margin-top:55px;" > 
              </div>
               <div class="card-footer " style="margin-top:-55px;">
                <ul class="nav flex-column">
                  <li class="nav-item"> <br>

				 <!-- <blockquote style="text-align:center; margin-top:10px;" >
                  <strong>หน่วยงาน <?php echo $rs_agency['ag_job']; ?><br>
				  จุด <?php if($cp_point_number=='0'){?> กะเช้า<?php }elseif($cp_point_number=='1'){ ?>กะดึก<?php }else{ ?>ที่ <?php echo ($cp_point_number-1).' '.$cp_point_name; ?><?php } ?> 
				  <br>รอบวันที่ <?php echo fn_thai_date(date('Y-m-d'),'4')?> เวลา <?php echo $ch_work_time.' - '.$ch_end_time ?> น.</strong> 
                </blockquote> -->
                  </li>
			     </ul>
			  </div>
				  </h2>  
			    <div class="card-footer ">
                <ul class="nav flex-column">
                  <li class="nav-item">  
	<?php 
	$date_end = date('d');
	$sql="SELECT DATE_FORMAT(`lp_date`, '%d') AS Day
	  ,lp_check_point 
	  ,lp_ch_id
	  ,lp_number
	  FROM tb_list_point AS TbLcad 
	  WHERE lp_agency  = '".$cp_ag_id."'
	  AND DATE_FORMAT(`lp_date`, '%Y-%m') = '".date('Y').'-'.date('m')."'  
	  ";  
	//  echo $sql;
$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
$num_rows =mysqli_num_rows($query);
if($num_rows>=1){ 
	while($rs=mysqli_fetch_array($query)){  
	//	echo $rs['Day'].'/'.$rs['lp_check_point'].'/'.$rs['lp_ch_id'].'/'.$rs['lp_number'].'<br />';
		$ArrCheckPoint[$rs['Day']][$rs['lp_ch_id']][$rs['lp_check_point']][$rs['lp_number']] 	 =  $rs['lp_check_point'];  
	}
} 
	$sql="SELECT *
		  FROM tb_list_check_head 
						  WHERE ch_id = '".$tmpKey."' 
		  ";  
		//  echo $sql;
	$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
	$num_rows =mysqli_num_rows($query);
	if($num_rows>=1){ 
		$i=0;
		while($rs=mysqli_fetch_array($query)){  
	?>	
		 
              <div class="card-footer p-0">
                <ul class="nav flex-column" style="background-color:#FFFFFF">
				<?php 
					 $sql_sb="SELECT *
						  FROM tb_list_check_detail as TbCd
						  LEFT JOIN tb_checkpoint AS TbCp
						  ON TbCp.cp_id = TbCd.cd_cp_id
						  WHERE cd_ch_id = '".$rs['ch_id']."' 
						  ";  
						 // echo $sql_sb;
					$query_sb =mysqli_query($connect,$sql_sb) or die(mysqli_error($connect));
					$num_rows_sb =mysqli_num_rows($query_sb);
						$TmpColor = '';
					if($num_rows_sb>=1){ 
						$i=0;
						while($rs_sb=mysqli_fetch_array($query_sb)){   
			  		?>
					<?php if($ArrCheckPoint[$date_end][$rs_sb['cd_ch_id']][$rs_sb['cd_cp_id']][$rs_sb['cd_number']]!=''){ $TmpColor = 'style="background-color: #00CC00"'; }else{ 
						$TmpColor = 'style="background-color: #FF9900"'; } ?>
                  <li class="nav-item"  <?php echo $TmpColor; ?>>
				  
                    <a style="color:#FFFFFF" href=""   class="nav-link">
                     <strong> <?php if($rs_sb['cp_point_number']=='0'){?>จุด <?php }elseif($rs_sb['cp_point_number']=='1'){ ?>จุด<?php }else{ ?> <?php echo 'จุด '.($rs_sb['cp_point_number']-1)?>  <?php } ?><?php echo  ' '.$rs_sb['cp_point_name']; ?> </strong>
					  <span class="float-right badge "><?php if($ArrCheckPoint[$date_end][$rs_sb['cd_ch_id']][$rs_sb['cd_cp_id']][$rs_sb['cd_number']]!=''){   ?> 
									 
						  <i style="font-size:18px; color: #00CC00" class="fa fa-fw fa-check-square-o"></i>		ตรวจจุดเรียบร้อย 							
									<?php }else{ ?>
									รอ. . .. 
									<?php } ?>
									</span>
                    </a>
                  </li> 
				  <?php }
				  }
				  ?>
                </ul>
              </div>
				  <?php }
				  }
				  ?>
				  
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