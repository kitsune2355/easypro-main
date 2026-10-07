<?php  
include "config_ctrl/DetechGuest.php"; 
@session_start();
include "config_ctrl/connect.php";
include "config_ctrl/checksession.php";
			ini_set('memory_limit', '64M');
			ini_set('max_execution_time', 5000);
date_default_timezone_set('Asia/Bangkok');
if($_SESSION['session_time']!=""){
	$overtime=time()-$_SESSION['session_time'];
	if($overtime>3600){
	 echo' <script language="javascript">
	 		alert("กรุณาล็อกอินเข้าสู่ระบบใหม่");
				window.location.href="config_ctrl/logout.php"
			</script>';
	}else{
	$_SESSION['session_time']=time();
	}
} 
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
    $thai_date_return =	"".(date("d",$time)*1);
    $thai_date_return.=" ".($thai_month_arr[date("n",$time)]);
		$thai_date_return.=	" ".((date("y",$time)+43));
  }else{
    $thai_date_return =	"".(date("d",$time));
    $thai_date_return.=" ".($thai_month_arr[date("n",$time)]);
    $thai_date_return.=	" ".((date("Y",$time)+543-2500));
  }
  if($thai_date_return!="1 ม.ค. 2513"){ return $thai_date_return;  }else{ return ""; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>EasyPro</title>
<!-- Google Font: Source Sans Pro -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<!-- Font Awesome Icons -->
<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<!-- IonIcons -->
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
<!-- Theme style -->
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/daterangepicker/daterangepicker.css">
<link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
<link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="fonts/thsarabunnew.css" />
<link rel="stylesheet" href="plugins/select2/css/select2.min.css">
<link rel="stylesheet" href="plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
<link rel="icon" href="favicon (1).ico" type="image/x-icon">
<link rel="shortcut icon" href="favicon (1).ico" type="image/x-icon">
<!-- tailwind -->
<script src="https://cdn.tailwindcss.com"></script>
<!-- lucide icon -->
<script src="https://unpkg.com/lucide@latest"></script>
<!-- Bootstrap4 Duallistbox -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<!--
`body` tag options:

  Apply one or more of the following classes to to the body tag
  to get the desired effect

  * sidebar-collapse
  * sidebar-mini
-->
<body class="hold-transition sidebar-mini" style="font-family:'SukhumvitSet', sans-serif; ">
<div class="wrapper">
<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <!-- Left navbar links -->
  <ul class="navbar-nav">
    <li class="nav-item"> <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a> </li>
    <?php if($sess_user_level=='admin'){ ?>
    <li class="nav-item d-none d-sm-inline-block"> <a href="menu_08.php" class="nav-link">Home</a> </li>
    <li class="nav-item d-none d-sm-inline-block"> <a href="menu_08.php" class="nav-link">หน้าหลัก</a> </li>
    <?php } ?>
  </ul>
  <!-- Right navbar links -->
  <ul class="navbar-nav ml-auto">
    <!-- Messages Dropdown Menu -->
	<b style="margin-top:8px;">ระบบบำรุงรักษาอาคาร Easy PRO</b>
    <li class="nav-item dropdown"> <a class="nav-link" data-toggle="dropdown" href="#"> <i class="fas fa-users"></i> <?php echo $sess_user_name;	?> </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
      <!--  <a href="#" class="dropdown-item">
           Message Start 
            <div class="media"> 
              <div class="media-body">
                <h3 class="dropdown-item-title">
                  เปลี่ยนรหัสผ่าน
                  <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                </h3> 
              </div>
            </div>
           Message End 
          </a>  -->
      <a href="config_ctrl/logout.php" class="dropdown-item">
      <!-- Message Start -->
      <div class="media">
        <div class="media-body">
          <h3 class="dropdown-item-title"> ออกจากระบบ <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span> </h3>
        </div>
      </div>
      <!-- Message End -->
      </a>
    <li class="nav-item"> <a class="nav-link" data-widget="fullscreen" href="#" role="button"> <i class="fas fa-expand-arrows-alt"></i> </a> </li>
  </ul>
</nav>
<!-- /.navbar -->
<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <!-- Brand Logo -->
  <!-- Sidebar -->
  <div class="sidebar">
    <!-- Sidebar user panel (optional) -->
    <a href="menu_08.php" class="brand-link"> <img  src="Easy_Prologo_1.bmp" width="250" style="margin-left:-12px;" alt="AdminLTE Logo"> <br>
    <div style="text-align:center"><span class="brand-text font-weight-light">
      <ion-icon name="body-outline"></ion-icon>
      <strong> </strong> </span></div>
    </a>
    <!-- Sidebar Menu -->
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
        <li class="nav-item" >
          <?php if($sess_user_level=='admin'||$sess_user_level=='general'){ ?>
          <a href="menu_08.php" class="nav-link <?php echo  SB_M1 == "data_index" ? "active":"";?>"> <i class="nav-icon fas fa-home"></i>
          <p> หน้าหลัก
            <!--  <span class="right badge badge-danger">New</span>-->
          </p>
          </a> </li>
        <?php } ?> 
       
        <li class="nav-item"> <a href="menu_14_add.php" class="nav-link <?php echo  SB_M1 == "data_132" ? "active":"";?>"> <i class="nav-icon fas fa-th"></i>
          <p style="font-size:14px;"> แจ้งซ่อม 
          </p>
          </a> </li>
        <li class="nav-item"> <a href="menu_14.php" class="nav-link <?php echo  SB_M1 == "data_13" ? "active":"";?>"> <i class="nav-icon fas fa-th"></i>
          <p style="font-size:14px;"> ข้อมูลแจ้งซ่อม 
          </p>
          </a> </li>
            <!--  <li class="nav-item"> <a href="menu_25.php" class="nav-link <?php echo  SB_M1 == "data_14" ? "active":"";?>"> <i class="nav-icon fas fa-th"></i>
          <p style="font-size:14px;"> ข้อมูลมิเตอร์ น้ำ/ไฟ 
          </p>
          </a> </li>
		   <li class="nav-item"> <a href="menu_03.php" class="nav-link <?php echo  SB_M1 == "data_7" ? "active":"";?>"> <i class="nav-icon fas fa-th"></i>
          <p style="font-size:14px;"> ข้อมูลอุปกรณ์เครื่องจักร 
          </p>
          </a> </li>
     <li class="nav-item">
            <a href="menu_09.php" style="font-size: 13px;" class="nav-link <?php echo  SB_M1 == "data_9" ? "active":"";?>">
              <i class="nav-icon fas fa-th"></i>
              <p>
              รายงานสถานการณ์ผิดปกติ
             <span class="right badge badge-danger">New</span> 
              </p>
            </a>
          </li> 

          <li class="nav-item">
            <a href="menu_010.php" style="font-size: 13px;" class="nav-link <?php echo  SB_M1 == "data_10" ? "active":"";?>">
              <i class="nav-icon fas fa-th"></i>
              <p>
                รายงานสถานการณ์ปกติ
               <span class="right badge badge-danger">New</span>
              </p>
            </a>
          </li> -->
        <?php if($sess_user_level=='admin2'||$sess_user_level=='general2'){ ?>
        <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
        <li class="nav-item"> <a href="menu_02.php" class="nav-link <?php echo  SB_M1 == "data_2" ? "active":"";?>"> <i class="nav-icon fas fa-th"></i>
          <p> ข้อมูลหน่วยงาน
            <!--  <span class="right badge badge-danger">New</span>-->
          </p>
          </a> </li>
        <li class="nav-item"> <a href="menu_04_list_ag.php" class="nav-link <?php echo  SB_M1 == "data_4" ? "active":"";?>"> <i class="nav-icon fas fa-th"></i>
          <p> ข้อมูลบัตร/จุดตรวจ
            <!--  <span class="right badge badge-danger">New</span>-->
          </p>
          </a> </li>
        <li class="nav-item"> <a href="process_9_9_9_op.php" class="nav-link <?php echo  SB_M1 == "data_6" ? "active":"";?>"> <i class="nav-icon fas fa-th"></i>
          <p> ข้อมูลตารางเดิน
            <!--  <span class="right badge badge-danger">New</span>-->
          </p>
          </a> </li>
        <li class="nav-item"> <a href="menu_012.php" class="nav-link <?php echo  SB_M1 == "data_12" ? "active":"";?>"> <i class="nav-icon fas fa-th"></i>
          <p> ข้อมูลพนักงานประจำหน่วยงาน
            <!--  <span class="right badge badge-danger">New</span>-->
          </p>
          </a> </li>
        <li class="nav-item"> <a href="menu_011.php" class="nav-link <?php echo  SB_M1 == "data_11" ? "active":"";?>"> <i class="nav-icon fas fa-th"></i>
          <p> ข้อมูลพื้นที่ปฏิบัติงาน
            <!--  <span class="right badge badge-danger">New</span>-->
          </p>
          </a> </li>
        <li class="nav-item"> <a href="menu_05.php" class="nav-link <?php echo  SB_M1 == "data_5" ? "active":"";?>"> <i class="nav-icon fas fa-th"></i>
          <p> ข้อมูลคำถาม
            <!--  <span class="right badge badge-danger">New</span>-->
          </p>
          </a> </li>
        <?php } ?> 
        <?php if($sess_user_level=='admin'||$sess_user_level=='general'){ ?>
        <li class="nav-item"> <a href="setting.php" class="nav-link <?php echo  SB_M1 == "data_1" ? "active":"";?>"> <i class="nav-icon fas fa-gears"></i>
          <p> ตั้งค่าข้อมูล
            <!--  <span class="right badge badge-danger">New</span>-->
          </p>
          </a> </li> 
        <?php } ?> 
      </ul>
    </nav>
    <!-- /.sidebar-menu -->
  </div>
  <!-- /.sidebar -->
</aside>
