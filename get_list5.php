<?php
	include "config_ctrl/connect.php"; 
	$SelectValue = $_GET["SelectValue"];
	echo "- เลือก -@@@0###"; 
	if($SelectValue!="") {
		$sql="SELECT *
			 ,CONCAT(rpd_details_head,'-',rpd_brand) AS  detail
			 ,CONCAT(rpd_id,'|',rpd_details_head,'|',rpd_details,'|',rpd_brand,'|',rpd_price) AS  id
			  FROM tb_repair_product     
			  WHERE rp_rps_id = '".$SelectValue."' 
			  AND rpd_satatus = '0'
			  ORDER BY rpd_details_head ASC
			  ";  
		$query=mysqli_query($connect,$sql)  or die(mysqli_error($connect));
		$num_rows=mysqli_num_rows($query);
		if ($num_rows>=1){
			while ($result = mysqli_fetch_array($query)){
				echo"$result[detail]@@@$result[id]###";
			}
		}
	} 
?>
