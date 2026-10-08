<?php
	include "config_ctrl/connect.php"; 
	$SelectValue = $_GET["SelectValue"];
	echo "- เลือกตำบล -@@@ ###";
	if($SelectValue!="") {
		$sql="SELECT *
			  FROM tb_addr     
			  WHERE Type = '3'
			  AND Code LIKE ('".$SelectValue."%')
			  ORDER BY Name ASC
			  ";
		$query=mysqli_query($connect,$sql)  or die(mysqli_error($connect));
		$num_rows=mysqli_num_rows($query);
		if ($num_rows>=1){
			while ($result = mysqli_fetch_array($query)){
				echo"$result[Name]@@@$result[Code]###";
			}
		}
	}
	mysql_close();
?>
