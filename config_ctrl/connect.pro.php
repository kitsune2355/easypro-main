<?php
	error_reporting(E_ALL ^ E_NOTICE);
//	include "config.inc.php"; 
	$SERVER = $_SERVER['HTTP_HOST'];
	$SERVER22 = strpos('x'.$SERVER,'localhost');
	if($server_id!="") { $dbname =	$server_id; }
	if($sess_server_id!="") { $dbname =	$sess_server_id; }

	if(($SERVER22*1)>0) {
		$user_mysql 	= 'root';
		$password_mysql = '16259';
		 
	}else{
//		$user_mysql 	= strtoupper($dbname);
		$user_mysql 	= 'happylandc_wha';
		$password_mysql = 'z7B53hxPs4cGtn8hpayk';
	}
	$dbname = "happylandc_wha";
	if($dbname=="") {
		?>
		<script language="javascript">
			//window.location.href='index.php';
		</script>
		<?php
	}
	$host_mysql	= "localhost";
//	echo $dbname." <<<<<<<<<<<<<<<<<<<<<<<<<<< <br />";
	$connect=mysqli_connect("$host_mysql","$user_mysql","$password_mysql",$dbname) or die("SERVER CONNECTION ERROR"); 
	
	//die(mysqli_error($connect));
//	$db=mysql_select_db($dbname) or die("Wrong Database Selection.");
	$charset = "SET character_set_results=UTF8";
	mysqli_query($connect,$charset) or die(mysqli_error($connect));
	$charset = "SET character_set_client='UTF8'";
	mysqli_query($connect,$charset) or die(mysqli_error($connect));
	$charset = "SET character_set_connection='UTF8'";
	mysqli_query($connect,$charset) or die(mysqli_error($connect));
	$iChkConnection = true;
?>