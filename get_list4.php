<?php
	include "config_ctrl/connect.php"; 
	$SelectValue = $_GET["SelectValue"];
	echo "- เลือกห้อง -@@@ ###"; 
	if($SelectValue!="") {
		$sql="SELECT *
			  FROM tb_area_room     
			  WHERE ar_ac_id = '".$SelectValue."' 
			  ORDER BY ar_id ASC
			  "; 
		$query=mysqli_query($connect,$sql)  or die(mysqli_error($connect));
		$num_rows=mysqli_num_rows($query);
		if ($num_rows>=1){
			while ($result = mysqli_fetch_array($query)){
				echo"$result[ar_name]@@@$result[ar_id]###";
			}
		}
	} 
?>
