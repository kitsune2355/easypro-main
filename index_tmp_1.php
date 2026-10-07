<?php 
@session_start();
include("config_ctrl/connect.php"); 
$sql="SELECT count(*) AS sum , ss_sj_id
	FROM `tb_select_subject` 
	WHERE ss_status in ('1','2')
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
function fn_number_format($value,$decimal,$nullOption) {
	if(($value*1)!=0) {
		return number_format(($value),$decimal);
	}else{
		return $nullOption;
	}
}
?>
    <link rel="stylesheet" href="fonts/thsarabunnew.css" />
<style>
@import url('https://fonts.googleapis.com/css?family=Raleway:400,700');

* {
	box-sizing: border-box;
	margin: 0;
	padding: 0;	 
}

body { 
}

.container2 { 
	display: flex;
	align-items: center;
	justify-content: center;
	min-height: 100vh;
	min-width:10vh;
  border-radius: 1em;
  overflow: hidden;  
 
}

.screen {		
	background: linear-gradient(90deg, #5D54A4, #7C78B8);		
	position: relative;	
	height: 600px;
	width: 560px;	
	box-shadow: 0px 0px 24px #5C5696;
}

.screen__content {
	z-index: 1;
	position: relative;	
	height: 100%;
}

.screen__background {		
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 0;
	-webkit-clip-path: inset(0 0 0 0);
	clip-path: inset(0 0 0 0);	
}

.screen__background__shape {
	transform: rotate(45deg);
	position: absolute;
}

.screen__background__shape1 {
	height: 520px;
	width: 520px;
	background: #FFF;	
	top: -50px;
	right: 120px;	
	border-radius: 0 72px 0 0;
}

.screen__background__shape2 {
	height: 220px;
	width: 220px;
	background: #6C63AC;	
	top: -172px;
	right: 0;	
	border-radius: 32px;
}

.screen__background__shape3 {
	height: 540px;
	width: 190px;
	background: linear-gradient(270deg, #5D54A4, #6A679E);
	top: -24px;
	right: 0;	
	border-radius: 32px;
}

.screen__background__shape4 {
	height: 400px;
	width: 200px;
	background: #7E7BB9;	
	top: 420px;
	right: 50px;	
	border-radius: 60px;
}

.login {
	width: 100%;
	padding: 30px;
	padding-top: 156px;
}

.login__field {
	padding: 20px 0px;	
	position: relative;	
}

.login__icon {
	position: absolute;
	top: 30px;
	color: #7875B5;
}

.login__input {
	border: none;
	border-bottom: 2px solid #D1D1D4;
	background: none;
	padding: 10px;
	padding-left: 24px;
	font-weight: 700;
	width: 75%;
	transition: .2s;
}

.login__input:active,
.login__input:focus,
.login__input:hover {
	outline: none;
	border-bottom-color: #6A679E;
}

.login__submit {
	background: #fff;
	font-size: 14px;
	margin-top: 25px;
	padding: 16px 20px;
	border-radius: 26px;
	border: 1px solid #D4D3E8;
	text-transform: uppercase;
	font-weight: 700;
	display: flex;
	align-items: center;
	width: 100%;
	color: #4C489D;
	box-shadow: 0px 2px 2px #5C5696;
	cursor: pointer;
	transition: .2s;
}

.login__submit:active,
.login__submit:focus,
.login__submit:hover {
	border-color: #6A679E;
	outline: none;
}


.login__submit2 {
	background: #fff;
	font-size: 14px;
	margin-top: 30px;
	padding: 16px 20px;
	border-radius: 26px;
	border: 1px solid #D4D3E8; 
	font-weight: 700;
	text-align:left;
	align-items: center;
	width: 100%;
	color: #FF0000;
	box-shadow: 0px 2px 2px #5C5696;
	cursor: pointer;
	transition: .2s;
}

.login__submit2:active,
.login__submit2:focus,
.login__submit2:hover {
	border-color: #6A679E;
	outline: none;
}



.login__submit3 {
	background: #fff;
	font-size: 14px;
	margin-top: 60px;
	padding: 16px 20px;
	border-radius: 26px;
	border: 1px solid #D4D3E8;
	text-transform: uppercase;
	font-weight: 700;
	font-size:18px;
	align-items: center;
	width: 60%;
	color: #4C489D;
	box-shadow: 0px 2px 2px #5C5696;
	cursor: pointer;
	transition: .2s;
	text-align:center;
}

.login__submit3:active,
.login__submit3:focus,
.login__submit3:hover {
	border-color: #6A679E;
	outline: none;
}
.button__icon {
	font-size: 24px;
	margin-left: auto;
	color: #7875B5;
}

.social-login {	
	position: absolute;
	height: 140px;
	width: 160px;
	text-align: center;
	bottom: 0px;
	right: 0px;
	color: #fff;
}

.social-icons {
	display: flex;
	align-items: center;
	justify-content: center;
}

.social-login__icon {
	padding: 20px 10px;
	color: #fff;
	text-decoration: none;	
	text-shadow: 0px 0px 8px #7875B5;
}

.social-login__icon:hover {
	transform: scale(1.5);	
} 
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
		</style><!DOCTYPE html>
<html lang="en" >
<head>
  <meta charset="UTF-8">
  <title>งานสอนดอทคอม</title>
  <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.2.0/css/all.css'>
<link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.2.0/css/fontawesome.css'><link rel="stylesheet" href="./style.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css"> 

</head>
<body style="font-family:'SukhumvitSet', sans-serif; ">
<!-- partial:index.partial.html -->
<br>

<div class="container2">


<table class="table1" width=""  border="0" align="center" cellpadding="0" cellspacing="0" >
  <tbody>
  <tr>
  <td>
  	 <table  width="100%" border="0">
  <tr>
    <td width="25%" height="71"> <strong style="font-size:20px;">&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#FFFFFF">sdsdsd</span>งานสอนดอทคอม</strong>  </td>
    <td width="14%">&nbsp;</td>
    <td width="61%" align="right">  
	<?php	if($_SESSION['sess_member_id_ngansorn']==""){  ?> 
            <a href="create.php" style="color:#00CC99; font-size:14px;"> <strong>สมัครติวเตอร์</strong></a>  &nbsp;&nbsp;&nbsp;&nbsp; 
		 <a href="login_member.php"   style="color:#00CC99; font-size:14px;"> <strong>เข้าสู่ระบบ</strong></a> 
		<a href="admin.php"   style="color:#00CC99; font-size:14px;">
		<img src="2669.png"/> 
      </button> 
	  <?php }else{ ?>  
            <a href="index.php"  style="color:#00CC99; font-size:14px;"> <strong><i class="fas fa-HOME"></i> หน้าหลัก</strong></li></a>
			
			 
            <a href="select_subject_user.php"  style="color:#00CC99; font-size:14px;"> <strong>ข้อมูลจองงานสอน</strong></a>
		 
			 
			   <a href="config_ctrl/logout.php" style="color:#00CC99; font-size:14px;" ><strong>ออกจากระบบ</strong></a>
	 
			
		<?php } ?> 
	&nbsp;&nbsp;
	</td>
  </tr> 
</table>
  </td>
  </tr>
    <tr>
      <td><table  width="" border="0" align="center" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td> 
                <img src="tutorjob_e.jpg" width="100%" /> </td>
            </tr>
            <tr>
              <td>&nbsp;</td>
            </tr>
          </tbody>
        </table>
        <table width="" border="0" align="center" cellpadding="0" cellspacing="0">
          <tbody>
            <tr>
              <td width="213" height="53" valign="bottom" class="font_2"><strong><span style="color:#FFFFFF">sdsdsd</span>งานสอนทั้งหมด</strong> </td>
              <td width="655" valign="bottom">
            </tr>
            <tr>
              <td colspan="3"><img src="images/space10.png" width="1" height="10"></td>
            </tr>
          </tbody>
        </table>
        <table style=" max-width: 100%; max-height:100%; margin-left:auto; margin-right:auto;"  class="table2"width="" border="1" align="center" cellpadding="0" cellspacing="0">
          <thead>
            <tr style="background-color: #000000; font-weight:500;  color:#FFFFFF;">
              <!--<th width="132" align="center" >เลขที่เอกสารใบสมัคร</th>   -->
              <!--<th width="58" align="center" > <span>
							 ลำดับ</span>							</label>  </th>  -->
              <td width="66" height="19" align="center" ><strong>ลำดับ</strong></td>
              <td width="263" align="center" ><strong>วิชา</strong></td>
              <td width="302" align="center" ><strong>สถานที่</strong></td>
              <td width="60" align="center" ><strong>คนจอง</strong></td>
              <td width="194" align="center" ><strong>สถานะ</strong></td>
              <td width="187"   align="center" ><span><strong>รายละเอียด</strong></span></td>
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
						WHERE sj_status not in ('5')
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
							  	if($ArrDataSum[$rs['sj_id']]==''){
									  $bg = 'style="background-color: #EAEA00; text-align:center;"';  
								$status = '<i class="fa fa-bell"></i>&nbsp;&nbsp;&nbsp;<strong>ว่าง(จองด่วน)</strong><img src="new_icon.gif" />'; 
								 }else{
								$bg = 'style="background-color: #AEFF5E; text-align:center;"';
								 $status = '<i class="fa fa-bell"></i>&nbsp;<strong>ต้องการงาน</strong>';   
								 }
							  }elseif($rs['sj_status']=='3'){ 
								if($rs['sj_coler']=='') $bg = 'style="background-color: #FF8CC6; text-align:center;"'; else  $bg = 'style="background-color: '.$rs['sj_coler'].'; text-align:center;"'; 
								 $status = '<i class="fa fa-bell"></i>&nbsp;<strong>ว่างเฉพาะติวเตอร์ (ญ)</strong> <img src="new_icon.gif" />'; 
							  }elseif($rs['sj_status']=='4'){  
								if($rs['sj_coler']=='') $bg = 'style="background-color: #FFA448; text-align:center;"'; else  $bg = 'style="background-color: '.$rs['sj_coler'].'; text-align:center;"';  
								 $status = '<i class="fa fa-bell"></i>&nbsp;<strong>จองด่วน</strong><img src="new_icon.gif" />'; 
							  }elseif($rs['sj_status']=='5'){ 
								$bg = 'style="background-color: #999999; text-align:center;"';
								 $status = '<i class="fa fa-bell"></i>&nbsp;&nbsp;<strong>Deal</strong>'; 
							  }elseif($rs['sj_status']=='6'){ 
								$bg = 'style="background-color: #22B7FF; text-align:center;"';
								 $status = '<i class="fa fa-bell"></i>&nbsp;&nbsp;<strong>ว่างเฉพาะติวเตอร์ (ช)</strong><img src="new_icon.gif" />'; 
							  }	
								$x++; 
								?>
            <tr  height="35" <?php echo $bg ?>  >
              <td width="66" align="center"><strong><?php echo $x; ?> </strong></td>
              <td width="263" style=" font-size:12px;"><strong><?php echo $rs['sj_subject']; ?></strong> </td>
              <td width="302" style=" font-size:12px;"><strong><?php echo $rs['sj_location']; ?> </strong></td>
              <td width="60" style=" text-align:center;  font-size:12px;"><button type="button" style="font-weight: bold;background-color: #FF9900;color: white;" class="btn btn-xs"><?php echo fn_number_format($ArrDataSum[$rs['sj_id']],'0','0') ?></button></td>
              <td width="194" style=" font-size:12px;"><?php echo $status; ?>              </td>
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
		div.absolute {
  position: fixed; 
  bottom:0;
  top:50.2%;
  right:1%; 
}
		</style>
  
</div>
<!-- partial -->
  <div class="absolute thsarabunnew14n" >
  	<a href="#">
      <img src="orange-439383_960_720.png" width="29" height="35"  class="to-ttop" /></a></div>
 </div>
</body>
</html>
<!-- Main Footer -->
<?php 
include "footer_user.php";  ?>