<?php
error_reporting(E_ALL ^ E_NOTICE);
include "checksession.php";
@session_start();
if($sess_user_id==""){ 

?>
	<script language="javascript"> 
		window.location.href="login.php";
	</script>
<?php
}
?>