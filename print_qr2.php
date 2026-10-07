<?php 
define("SB_M1","data_7");
include "config_ctrl/DetechGuest.php";
include "config_ctrl/checksession.php";
include "config_ctrl/connect.php";
date_default_timezone_set('Asia/Bangkok'); 

$cp_id			= $_GET['cp_id'];  

$i=1;
$sql="SELECT *  
FROM tb_checkpoint AS tbcp
  LEFT JOIN tb_agency As tbag  
  ON tbcp.cp_ag_id = tbag.ag_id
WHERE  cp_id     = '".$cp_id."' 
  ";
  echo $sql;
$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
$num_rows =mysqli_num_rows($query);
if($num_rows>=1){
  while($rs=mysqli_fetch_array($query)) {
		$ag_name			      = $rs['ag_name']; 
		$cp_point_name			= $rs['cp_point_name']; 
 
  }
}
<img class="elevation-2 img-circle" src="logo1.png"   > 

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Untitled Document</title>
</head>

<body>
<table width="100%" border="0">
  <tr>
    <td> <?php echo $cp_point_name?> <td>
  </tr>
</table>
<table width="100%" border="0">
  <tr>
    <td>&nbsp;</td>
  </tr>
</table>
<table width="100%" border="0">
  <tr>
    <td>&nbsp;</td>

  </tr>
</table>
<table width="100%" border="0">
  <tr>
    <td>&nbsp;</td>
  </tr>
</table>
</body>
</html>
