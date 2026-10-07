<link rel="stylesheet" href="fonts/thsarabunnew.css" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js"></script>
<script type="text/javascript" src="src/jquery.qrcode.js"></script>
<script type="text/javascript" src="src/qrcode.js"></script>
<?php 
define("SB_M1","data_7");
include "config_ctrl/DetechGuest.php";
include "config_ctrl/checksession.php";
include "config_ctrl/connect.php";
date_default_timezone_set('Asia/Bangkok'); 

$cp_ag_id			= $_GET['cp_ag_id'];  
$cp_point_number	= $_GET['cp_point_number'];   
$cp_id				= $_GET['cp_id'];   
 


$i=1;
$sql="SELECT *  
FROM tb_checkpoint AS tbcp
  LEFT JOIN tb_agency As tbag  
  ON tbcp.cp_ag_id = tbag.ag_id
WHERE  cp_id     = '".$cp_id."' 
  ";
$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
$num_rows =mysqli_num_rows($query);
if($num_rows>=1){
  $rs=mysqli_fetch_array($query);
		$ag_name			      = $rs['ag_name']; 
		$ag_id			      = $rs['ag_id']; 
		$cp_point_name			= $rs['cp_point_name']; 
    $ag_job			        = $rs['ag_job']; 
    $cp_point_number		 = $rs['cp_point_number']; 
  
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  	<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Untitled Document</title>
</head>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<body class="thsarabunnew24b">

<center><table  width="54%" border="1" style="border-collapse:collapse"></center>
                          <tr> 
                                <td><center><div id="qrcodeCanvas" style="margin-top:10px;"></div></center> 
                                <center><span style="font-size: 8px;"><b>**สแกนคิวอาร์โค้ดเพื่อบันทึกการตรวจจุด**</b></span></center></td>
                              
                                <td> <center><img class="elevation-2 img-circle" src="logo1.png" width="50" height="50"></center> 
                              <center><span style="font-size: 12px;"><b>หน่วยงาน  : </b><?php echo $ag_job?></center></span>
                               <center><span style="font-size: 30px;"><b>จุดที่ : </b>
							   
							    <?php if($cp_point_number=='0'){?> กะเช้า<?php }elseif($cp_point_number=='1'){ ?>กะดึก<?php }else{ ?> <?php echo ($cp_point_number-1)?>  <?php } ?>
							   <?php // echo $cp_point_number?></center></span>
                               <center><span style="font-size: 14px;"><b>ชื่อจุด : </b><?php echo $cp_point_name?></center></span></td>
                              </tr>  
                        </table>
<script> 
	//jQuery('#qrcode').qrcode("this plugin is great"); 
	jQuery('#qrcodeCanvas<?php  echo $iCountPg ?>').qrcode({
		text	: "https://develophpg.net/timeatt/check.php?cp_ag_id=<?php echo $cp_ag_id ?>&cp_point_number=<?php echo $cp_point_number?>" 
	});	 

	jQuery('#qrcodeCanvas3').qrcode({
		text	: "https://develophpg.net/timeatt/check.php?cp_ag_id=<?php echo $cp_ag_id ?>&cp_point_number=<?php  echo $cp_point_number?>" 
	});	 
//window.print();

</script>
