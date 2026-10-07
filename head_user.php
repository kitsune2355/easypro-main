<?php   
@session_start();
include "config_ctrl/connect.php";
include "config_ctrl/checksession.php";
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
?>

<!DOCTYPE html>
<!-- This site was created in Webflow. https://www.webflow.com -->
<!-- Last Published: Fri Oct 12 2018 16:54:07 GMT+0000 (UTC) -->
<html data-wf-domain="propel-template.webflow.io" data-wf-page="5b680680f109cf797dd941ac" data-wf-site="5b680680f109cf5a2fd941ab" data-wf-status="1">
<head>
<meta charset="utf-8"/>
<title>2.งานสอนดอลคอม</title>
<meta content="width=device-width, initial-scale=1" name="viewport"/>
<meta content="Webflow" name="generator"/>
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- IonIcons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min.css"> 
  <link rel="stylesheet" href="css/cssindex.css"> 
  <link rel="stylesheet" href="plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="fonts/thsarabunnew.css" />
	<script src="plugins/jquery/jquery-1.12.4.min.js"></script>
	<!-- jQuery 3 --> 
<script src="https://ajax.googleapis.com/ajax/libs/webfont/1.6.26/webfont.js" type="text/javascript"></script>
 
<style>
  body {
  -moz-font-feature-settings: "liga" on;
  -moz-osx-font-smoothing: grayscale;
  -webkit-font-smoothing: antialiased;
  font-feature-settings: "liga" on;
  text-rendering: optimizeLegibility;
 font-family:'SukhumvitSet', sans-serif; 
}
</style>
</head>
<body class="body" >

<div class="navigation wf-section"></div>

<div data-collapse="medium" data-animation="default" data-duration="400" data-easing="ease-out" data-easing2="ease-out" role="banner" class="navigation w-nav">
 
  <div class="navigation-container"><a href="/" aria-current="page" class="logo w-inline-block w--current">
  <!--<img src="https://uploads-ssl.webflow.com/5b680680f109cf5a2fd941ab/5b680f7ddc6fa8394a12fc7f_logo-spoon-white.svg" alt=""/>--> 
            <a id="dropdownSubMenu1" style="margin-left:" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" >
			<img src="https://uploads-ssl.webflow.com/5b680680f109cf5a2fd941ab/5b680f7ddc6fa8394a12fc7f_logo-spoon-white.svg" alt=""/>
			<img src="images.png" width="20" height="20" alt=""/>
			</a> 
            <ul aria-labelledby="dropdownSubMenu1" class="dropdown-menu border-0 " style="left: 0px; right: inherit;">
            <a href="/" class="">  <li>🏠หน้าแรก</li></a></a>
			  <li class="dropdown-divider"></li>
             <a href="/blog"><li>✍🏻บล็อค</a></li></a>
			  <li class="dropdown-divider"></li>
 			<a href="/blog"><li>😯คำถามที่พบบ่อย</li></a>
			  <li class="dropdown-divider"></li>
			 <a href="/blog"><li>📝ลงสอนพิเศษ</li></a>
			  <li class="dropdown-divider"></li>
			 <a href="/blog"><li>🕵🏻‍♂️การค้นหาที่ถูกค้นบ่อย</li></a>
			  <li class="dropdown-divider"></li>
			 <a href="/blog"> <li>☎️ติดต่อ</li></a>
              <!-- End Level two -->
            </ul> 
  <span class="top-bar-dropdown-chevron-down no_selection toggle-dropdown-menu topbar-group"></span>
  </a>
  <div class="col-md-2" style="margin-top:-5px;  padding-left:0px;">
	<div class="dropdown"> 
		 		<span class="caret"></span>
	  
	  <ul class="dropdown-menu" aria-labelledby="dropdownMenu1" style="width:280px; font-size:16px"> 
		<li class=""><a href=""><span class="glyphicon glyphicon-user" aria-hidden="true"></span> Record</a></li> 
	  </ul>
	</div>
</div>
   
    <nav role="navigation" class="nav-menu w-nav-menu">
	<a href="/features" style=" font-family:'SukhumvitSet', sans-serif; " class="nav-link w-nav-link">&nbsp;ลงสอนพิเศษ</a>
	<a href="/premium"  style=" font-family:'SukhumvitSet', sans-serif; "class="nav-link w-nav-link"> &nbsp;การค้นหาที่ถูกค้นบ่อย</a>
	<a href="/pricing" style=" font-family:'SukhumvitSet', sans-serif; " class="nav-link w-nav-link">&nbsp;บล็อค</a>
	<a href="/about"  style=" font-family:'SukhumvitSet', sans-serif; "class="nav-link w-nav-link">&nbsp;คำถามที่พบบ่อย</a>  
	<a href="#" class="navigation-button w-button"  style=" font-family:'SukhumvitSet', sans-serif; background-color:#FF0000; ">&nbsp;หาติวเตอร์</a></nav>
    <div class="menu-button w-nav-button">
      <div class="icon-2 w-icon-nav-menu"></div>
    </div>
  </div>
</div>
<div class="header wf-section">
  <div class="header-content"  style="margin-top:-100px;" >
    <h2 style=" color:#FFFFFF; line-height:50px;" data-w-id="b777ef2d-ac03-cea3-ccc5-52beeee5222a" style="-webkit-transform:translate3d(0, 40PX, 0) scale3d(1, 1, 1) rotateX(-50DEG) rotateY(0) rotateZ(0) skew(0, 0);-moz-transform:translate3d(0, 40PX, 0) scale3d(1, 1, 1) rotateX(-50DEG) rotateY(0) rotateZ(0) skew(0, 0);-ms-transform:translate3d(0, 40PX, 0) scale3d(1, 1, 1) rotateX(-50DEG) rotateY(0) rotateZ(0) skew(0, 0);transform:translate3d(0, 40PX, 0) scale3d(1, 1, 1) rotateX(-50DEG) rotateY(0) rotateZ(0) skew(0, 0);transform-style:preserve-3d;opacity:0">หาติวเตอร์ที่ใช่<br></h2>
    <h2 style=" color:#FFFFFF; line-height:30px; font-size: 16px" data-w-id="b777ef2d-ac03-cea3-ccc5-52beeee5222a" style="-webkit-transform:translate3d(0, 40PX, 0) scale3d(1, 1, 1) rotateX(-50DEG) rotateY(0) rotateZ(0) skew(0, 0);-moz-transform:translate3d(0, 40PX, 0) scale3d(1, 1, 1) rotateX(-50DEG) rotateY(0) rotateZ(0) skew(0, 0);-ms-transform:translate3d(0, 40PX, 0) scale3d(1, 1, 1) rotateX(-50DEG) rotateY(0) rotateZ(0) skew(0, 0);transform:translate3d(0, 40PX, 0) scale3d(1, 1, 1) rotateX(-50DEG) rotateY(0) rotateZ(0) skew(0, 0);transform-style:preserve-3d;opacity:0"> 💡ประสบความสำเร็จเรียนตัวต่อตัวกับติวเตอร์ที่ใช่<br>

พร้อมรับคำแนะนำการศึกษาจากผู้มีประสบการณ์</h2> 

    
    <a href="/contact" class="button w-button" style=" 
 font-family:'SukhumvitSet', sans-serif; background-color:#FF0000 ">สมัครเรียน !</a></div>
</div> 
  
  <!-- /.navbar -->