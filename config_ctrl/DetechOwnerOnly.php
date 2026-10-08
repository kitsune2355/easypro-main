<?php
	include "checksession.php";
	$user_id = $_GET['user_id'];
	if($sess_user_id=="") {
	?>
		<script language="javascript">
			window.location.href="index.php";
		</script>
	<?php
	}
	if($user_id=="") {
	?>
		<script language="javascript">
			//window.history.back();
		</script>
	<?php
	}
	if($sess_user_id!=$user_id){ 
	?>
		<script language="javascript">
			//alert("คุณไม่สามารถเข้าถึงข้อมูลของ USER อื่นได้ กรุณาติดต่อ ผู้ดูแลระบบ");
			//window.history.back();
		</script>
	<?php
	}
?>