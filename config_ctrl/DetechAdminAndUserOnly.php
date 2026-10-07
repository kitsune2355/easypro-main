<?php
	include "checksession.php";
	if(($sess_user_level!="admin")&&($sess_user_level!="user"){ 
	?>
		<script language="javascript">
			alert("ขอภัยหน้านี้สำหรับเจ้าหน้าที่ผู้ดูและระบบเท่านั้น");
			window.location.href="index.php";
		</script>
	<?
	}
?>