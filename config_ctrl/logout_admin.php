<?php
@session_start();

unset($_SESSION['sess_user_id']);
unset($_SESSION['sess_user_code']);
unset($_SESSION['sess_user_password']);
unset($_SESSION['sess_user_name']);
unset($_SESSION['sess_user_level']);
unset($_SESSION['sess_user_dep_id']);
unset($_SESSION['sess_user_email']);
unset($_SESSION['sess_tmp_usr_id']);
unset($_SESSION['sess_server_id']);
unset($_SESSION['sess_business_title']);
unset($_SESSION['sess_mem_expire']);

session_destroy();
?>
<script language="javascript">
	window.location.href="../index_admin.php"
</script>
