<?php
	include "config_ctrl/connect.php"; 
	$SelectValue = $_GET["SelectValue"];
	echo "- เลือกรอบเวลาเดินตรวจจุด -@@@ ###";
	echo "- แสดงทั้งหมด -@@@ ###";
	if($SelectValue!="") {
		$sql="SELECT ch_id, CONCAT(ch_work_time, ' - ', ch_end_time) AS FullName
			  FROM tb_list_check_head     
			  WHERE ch_agency = '".$SelectValue."' 
			  ORDER BY ch_id ASC
			  "; 
		$query=mysqli_query($connect,$sql)  or die(mysqli_error($connect));
		$num_rows=mysqli_num_rows($query);
		if ($num_rows>=1){
			while ($result = mysqli_fetch_array($query)){
				echo"$result[FullName]@@@$result[ch_id]###";
			}
		}
	} 
?>
