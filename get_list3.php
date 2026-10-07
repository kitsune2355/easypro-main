<?php
	include "config_ctrl/connect.php"; 
	$SelectValue = $_GET["SelectValue"];
	echo "- เลือกชั้น -@@@ ###"; 
	if($SelectValue!="") {
		$sql="SELECT *
			  FROM tb_area_class     
			  WHERE ac_area_id = '".$SelectValue."' 
			  ORDER BY ac_id ASC
			  "; 
		$query=mysqli_query($connect,$sql)  or die(mysqli_error($connect));
		$num_rows=mysqli_num_rows($query);
		if ($num_rows>=1){
			while ($result = mysqli_fetch_array($query)){
				echo"$result[ac_name]@@@$result[ac_id]###";
			}
		}
	} 
?>
