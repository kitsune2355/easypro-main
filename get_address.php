<?php @session_start();
header("Content-type:text/html; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
$find = trim($_GET['find']);
$fvalue = trim($_GET['fvalue']);

if(($find != null)&&($find != '')&&($fvalue != null)&&($fvalue != '')){
 	
include "config_ctrl/connect.php";
 
	
	$fvalue = explode(",",$fvalue);
	$fvalue = $fvalue[0];
	
	if ( $find == 'a' ){
		//$result = $juice->fncSelect("AMPHUR_ID,AMPHUR_NAME","amphur","WHERE PROVINCE_ID ='".$fvalue."' AND AMPHUR_NAME NOT LIKE '*%' ORDER BY AMPHUR_NAME ASC");
		
		$sql_h="SELECT AMPHUR_ID,AMPHUR_NAME
				FROM `amphur`
				WHERE PROVINCE_ID ='".$fvalue."' AND AMPHUR_NAME NOT LIKE '*%' ORDER BY AMPHUR_NAME ASC     
				";    
		$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
		$num_rows_h =mysqli_num_rows($query_h); 
		if($num_rows_h>=1){    
			while($rs=mysqli_fetch_array($query_h)) {  
		if ( count($num_rows_h)>0 ) {
			$temp = '<option value="">:::::&nbsp;เลือกอำเภอ&nbsp;:::::</option>';
			foreach($result as $read){
				if($read['AMPHUR_ID']==$svalue){
					$temp .= '<option value="'.$rs['AMPHUR_ID'].','.$rs['AMPHUR_NAME'].'" selected>'.$rs['AMPHUR_NAME'].'</option>';	
				}else{
					$temp .= '<option value="'.$rs['AMPHUR_ID'].','.$rs['AMPHUR_NAME'].'">'.$rs['AMPHUR_NAME'].'</option>';
				}
			}
		} else {
			$temp = '<option value="">:::&nbsp;ไม่มีข้อมูล&nbsp;:::</option>';	
		}
	}else if($find == 't'){
		$result = $juice->fncSelect("DISTRICT_CODE,DISTRICT_NAME","district","WHERE AMPHUR_ID ='".$fvalue."' AND DISTRICT_NAME NOT LIKE '*%' ORDER BY DISTRICT_NAME ASC");
		if ( count($result)>0 ) {
			$temp = '<option value="" selected="selected">:::::&nbsp;เลือกตำบล&nbsp;:::::</option>';
			foreach($result as $read){
				if($read['DISTRICT_CODE']==$svalue){
					$temp .= '<option value="'.$read['DISTRICT_CODE'].','.$read['DISTRICT_NAME'].'" selected>'.$read['DISTRICT_NAME'].'</option>';
				}else{
					$temp .= '<option value="'.$read['DISTRICT_CODE'].','.$read['DISTRICT_NAME'].'">'.$read['DISTRICT_NAME'].'</option>';
				}
			}
		} else {
			$temp = '<option value="" selected="selected">:::&nbsp;ไม่มีข้อมูล&nbsp;:::</option>';	
		}
	} else if($find == 'z') {
		$result = $juice->fncSelectAssoc("ZIPCODE","zipcode","WHERE DISTRICT_CODE ='".$fvalue."' ");
		$temp = $result['ZIPCODE'];
	}

} else {
	if ( $find != 'z' ) 
	{
		$temp = '<option value="">:::::&nbsp;เลือก&nbsp;:::::</option>';	
	}
}

echo $temp;
?>
