<?php 
define("SB_M1","process_m_10");
define("SB_M2","process_10");
include("config_ctrl/connect.php"); 
$process				= $_GET['process']; 
//$chk_ctrl_add 			= fn_detect_permis_pocess($sess_user_id,$process,'ctrl_add');
//$chk_ctrl_edit 			= fn_detect_permis_pocess($sess_user_id,$process,'ctrl_edit');
//$chk_ctrl_view			= fn_detect_permis_pocess($sess_user_id,$process,'ctrl_view');
//$chk_ctrl_print			= fn_detect_permis_pocess($sess_user_id,$process,'ctrl_print');
//$chk_ctrl_del			= fn_detect_permis_pocess($sess_user_id,$process,'ctrl_del');
$tmp_process = '9';  
function fn_number_format($value,$decimal,$nullOption) {
	if(($value*1)!=0) {
		return number_format(($value),$decimal);
	}else{
		return $nullOption;
	}
}
	$eng_full_month_arr=array(
		"0"=>"",
		"1"=>"January",
		"2"=>"February",
		"3"=>"March",
		"4"=>"April",
		"5"=>"May",
		"6"=>"June",	
		"7"=>"July",
		"8"=>"August",
		"9"=>"September",
		"10"=>"October",
		"11"=>"November",
		"12"=>"December"
	);
	function fn_thai_date($time,$formatdate){
		if(($time=="0000-00-00")||($time=="")) {
			return '';
		}
		$thai_day_arr=array("อาทิตย์","จันทร์","อังคาร","พุธ","พฤหัสบดี","ศุกร์","เสาร์");
		$thai_full_month_arr=array(
			"0"=>"",
			"1"=>"มกราคม",
			"2"=>"กุมภาพันธุ์",
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
			$thai_date_return =	"".(date("m",$time)*1);
			$thai_date_return.="/".(date("d",$time));
			$thai_date_return.="/".(date("Y",$time));
		}else if($formatdate=="5") {
			 $thai_date_return="".((date("Y",$time)+543)); 
		}else{
			$thai_date_return =	"".(date("d",$time)*1);
			$thai_date_return.=" ".($thai_month_arr[date("n",$time)]);
			$thai_date_return.=	" ".((date("Y",$time)+543-2500));
		}
		if($thai_date_return!="1 ม.ค. 2513") { return $thai_date_return;  }else{ return ""; }
	} 
?> 

<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>AdminLTE 3 | Top Navigation</title>

<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">

<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">

<link rel="stylesheet" href="dist/css/adminlte.min.css?v=3.2.0">
<script nonce="347afc03-5661-4eed-b08e-c9ea4ea3d9eb">(function(w,d){!function(dg,dh,di,dj){dg[di]=dg[di]||{};dg[di].executed=[];dg.zaraz={deferred:[],listeners:[]};dg.zaraz.q=[];dg.zaraz._f=function(dk){return async function(){var dl=Array.prototype.slice.call(arguments);dg.zaraz.q.push({m:dk,a:dl})}};for(const dm of["track","set","debug"])dg.zaraz[dm]=dg.zaraz._f(dm);dg.zaraz.init=()=>{var dn=dh.getElementsByTagName(dj)[0],dp=dh.createElement(dj),dq=dh.getElementsByTagName("title")[0];dq&&(dg[di].t=dh.getElementsByTagName("title")[0].text);dg[di].x=Math.random();dg[di].w=dg.screen.width;dg[di].h=dg.screen.height;dg[di].j=dg.innerHeight;dg[di].e=dg.innerWidth;dg[di].l=dg.location.href;dg[di].r=dh.referrer;dg[di].k=dg.screen.colorDepth;dg[di].n=dh.characterSet;dg[di].o=(new Date).getTimezoneOffset();if(dg.dataLayer)for(const du of Object.entries(Object.entries(dataLayer).reduce(((dv,dw)=>({...dv[1],...dw[1]})),{})))zaraz.set(du[0],du[1],{scope:"page"});dg[di].q=[];for(;dg.zaraz.q.length;){const dx=dg.zaraz.q.shift();dg[di].q.push(dx)}dp.defer=!0;for(const dy of[localStorage,sessionStorage])Object.keys(dy||{}).filter((dA=>dA.startsWith("_zaraz_"))).forEach((dz=>{try{dg[di]["z_"+dz.slice(7)]=JSON.parse(dy.getItem(dz))}catch{dg[di]["z_"+dz.slice(7)]=dy.getItem(dz)}}));dp.referrerPolicy="origin";dp.src="/cdn-cgi/zaraz/s.js?z="+btoa(encodeURIComponent(JSON.stringify(dg[di])));dn.parentNode.insertBefore(dp,dn)};["complete","interactive"].includes(dh.readyState)?zaraz.init():dg.addEventListener("DOMContentLoaded",zaraz.init)}(w,d,"zarazData","script");})(window,document);</script></head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">

<nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
<div class="container">
<a href="index3.html" class="navbar-brand">
<img src="dist/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
<span class="brand-text font-weight-light">AdminLTE 3</span>
</a>
<button class="navbar-toggler order-1" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse order-3" id="navbarCollapse">

<ul class="navbar-nav">
<li class="nav-item">
<a href="index3.html" class="nav-link">Home</a>
</li>
<li class="nav-item">
<a href="#" class="nav-link">Contact</a>
</li>
<li class="nav-item dropdown">
<a id="dropdownSubMenu1" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle">Dropdown</a>
<ul aria-labelledby="dropdownSubMenu1" class="dropdown-menu border-0 shadow">
<li><a href="#" class="dropdown-item">Some action </a></li>
<li><a href="#" class="dropdown-item">Some other action</a></li>
<li class="dropdown-divider"></li>

<li class="dropdown-submenu dropdown-hover">
<a id="dropdownSubMenu2" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="dropdown-item dropdown-toggle">Hover for action</a>
<ul aria-labelledby="dropdownSubMenu2" class="dropdown-menu border-0 shadow">
<li>
<a tabindex="-1" href="#" class="dropdown-item">level 2</a>
</li>

<li class="dropdown-submenu">
<a id="dropdownSubMenu3" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="dropdown-item dropdown-toggle">level 2</a>
<ul aria-labelledby="dropdownSubMenu3" class="dropdown-menu border-0 shadow">
<li><a href="#" class="dropdown-item">3rd level</a></li>
<li><a href="#" class="dropdown-item">3rd level</a></li>
</ul>
</li>

<li><a href="#" class="dropdown-item">level 2</a></li>
<li><a href="#" class="dropdown-item">level 2</a></li>
</ul>
</li>

</ul>
</li>
</ul>

<form class="form-inline ml-0 ml-md-3">
<div class="input-group input-group-sm">
<input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
<div class="input-group-append">
<button class="btn btn-navbar" type="submit">
<i class="fas fa-search"></i>
</button>
</div>
</div>
</form>
</div>

<ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">

<li class="nav-item dropdown">
<a class="nav-link" data-toggle="dropdown" href="#">
<i class="fas fa-comments"></i>
<span class="badge badge-danger navbar-badge">3</span>
</a>
<div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
<a href="#" class="dropdown-item">

<div class="media">
<img src="dist/img/user1-128x128.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">
<div class="media-body">
<h3 class="dropdown-item-title">
Brad Diesel
<span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
</h3>
<p class="text-sm">Call me whenever you can...</p>
<p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
</div>
</div>

</a>
<div class="dropdown-divider"></div>
<a href="#" class="dropdown-item">

<div class="media">
<img src="dist/img/user8-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
<div class="media-body">
<h3 class="dropdown-item-title">
John Pierce
<span class="float-right text-sm text-muted"><i class="fas fa-star"></i></span>
</h3>
<p class="text-sm">I got your message bro</p>
<p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
</div>
</div>

</a>
<div class="dropdown-divider"></div>
<a href="#" class="dropdown-item">

<div class="media">
<img src="dist/img/user3-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
<div class="media-body">
<h3 class="dropdown-item-title">
Nora Silvester
<span class="float-right text-sm text-warning"><i class="fas fa-star"></i></span>
</h3>
<p class="text-sm">The subject goes here</p>
<p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
</div>
</div>

</a>
<div class="dropdown-divider"></div>
<a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
</div>
</li>

<li class="nav-item dropdown">
<a class="nav-link" data-toggle="dropdown" href="#">
<i class="far fa-bell"></i>
<span class="badge badge-warning navbar-badge">15</span>
</a>
<div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
<span class="dropdown-header">15 Notifications</span>
<div class="dropdown-divider"></div>
<a href="#" class="dropdown-item">
<i class="fas fa-envelope mr-2"></i> 4 new messages
<span class="float-right text-muted text-sm">3 mins</span>
</a>
<div class="dropdown-divider"></div>
<a href="#" class="dropdown-item">
<i class="fas fa-users mr-2"></i> 8 friend requests
<span class="float-right text-muted text-sm">12 hours</span>
</a>
<div class="dropdown-divider"></div>
<a href="#" class="dropdown-item">
<i class="fas fa-file mr-2"></i> 3 new reports
<span class="float-right text-muted text-sm">2 days</span>
</a>
<div class="dropdown-divider"></div>
<a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
</div>
</li>
<li class="nav-item">
<a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
<i class="fas fa-th-large"></i>
</a>
</li>
</ul>
</div>
</nav>


<div class="content-wrapper">

<div class="content-header">
<div class="container">
<div class="row mb-2">
<div class="col-sm-6">
<h1 class="m-0"> Top Navigation <small>Example 3.0</small></h1>
</div>
<div class="col-sm-6">
<ol class="breadcrumb float-sm-right">
<li class="breadcrumb-item"><a href="#">Home</a></li>
<li class="breadcrumb-item"><a href="#">Layout</a></li>
<li class="breadcrumb-item active">Top Navigation</li>
</ol>
</div>
</div>
</div>
</div>


<div class="content">
<div class="container">
<div class="row">
<div class="col-lg-6">
<div class="card">
<div class="card-body">
<h5 class="card-title">Card title</h5>
<p class="card-text">
Some quick example text to build on the card title and make up the bulk of the card's
content.
</p>
<a href="#" class="card-link">Card link</a>
<a href="#" class="card-link">Another link</a>
</div>
</div>
<div class="card card-primary card-outline">
<div class="card-body">
<h5 class="card-title">Card title</h5>
<p class="card-text">
Some quick example text to build on the card title and make up the bulk of the card's
content.
</p>
<a href="#" class="card-link">Card link</a>
<a href="#" class="card-link">Another link</a>
</div>
</div>
</div>

<div class="col-lg-6">
<div class="card">
<div class="card-header">
<h5 class="card-title m-0">Featured</h5>
</div>
<div class="card-body">
<h6 class="card-title">Special title treatment</h6>
<p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
<a href="#" class="btn btn-primary">Go somewhere</a>
</div>
</div>
<div class="card card-primary card-outline">
<div class="card-header">
<h5 class="card-title m-0">Featured</h5>
</div>
<div class="card-body">
<h6 class="card-title">Special title treatment</h6>
<p class="card-text">With supporting text below as a natural lead-in to additional content.</p>
<a href="#" class="btn btn-primary">Go somewhere</a>
</div>
</div>
</div>

</div>

</div>
</div>

</div>


<aside class="control-sidebar control-sidebar-dark">

</aside>


<footer class="main-footer">

<div class="float-right d-none d-sm-inline">
Anything you want
</div>

<strong>Copyright &copy; 2014-2021 <a href="https://adminlte.io">AdminLTE.io</a>.</strong> All rights reserved.
</footer>
</div>



<script src="plugins/jquery/jquery.min.js"></script>

<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="dist/js/adminlte.min.js?v=3.2.0"></script>
 
</body>
</html>
