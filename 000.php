 
<?php  


$expirationDays = 30; 

$expireDate = strtotime("-$expirationDays days");
echo $expireDate;
echo date('Y-m-d',$expireDate);
//echo 'dsfsadsadsa';
?>