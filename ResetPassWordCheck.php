<?php 
include "config_ctrl/connect.php";
?>
    <link rel="stylesheet" href="fonts/thsarabunnew.css" />
<style>
@import url('https://fonts.googleapis.com/css?family=Raleway:400,700');

* {
	box-sizing: border-box;
	margin: 0;
	padding: 0;	 
}

body {
	background: linear-gradient(90deg, #C7C5F4, #776BCC);		
}

.container {
	display: flex;
	align-items: center;
	justify-content: center;
	min-height: 100vh;
}

.screen {		
	background: linear-gradient(90deg, #5D54A4, #7C78B8);		
	position: relative;	
	height: 600px;
	width: 560px;	
	box-shadow: 0px 0px 24px #5C5696;
}

.screen__content {
	z-index: 1;
	position: relative;	
	height: 100%;
}

.screen__background {		
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 0;
	-webkit-clip-path: inset(0 0 0 0);
	clip-path: inset(0 0 0 0);	
}

.screen__background__shape {
	transform: rotate(45deg);
	position: absolute;
}

.screen__background__shape1 {
	height: 550px;
	width: 800px;
	background: #FFF;	
	top: -50px;
	right: 120px;	
	border-radius: 0 72px 0 0;
}

.screen__background__shape2 {
	height: 220px;
	width: 220px;
	background: #6C63AC;	
	top: -172px;
	right: 0;	
	border-radius: 32px;
}

.screen__background__shape3 {
	height: 540px;
	width: 190px;
	background: linear-gradient(270deg, #5D54A4, #6A679E);
	top: -24px;
	right: 0;	
	border-radius: 32px;
}

.screen__background__shape4 {
	height: 400px;
	width: 200px;
	background: #7E7BB9;	
	top: 420px;
	right: 50px;	
	border-radius: 60px;
}

.login {
	width: 100%;
	padding: 30px;
	padding-top: 156px;
}

.login__field {
	padding: 20px 0px;	
	position: relative;	
}

.login__icon {
	position: absolute;
	top: 30px;
	color: #7875B5;
}

.login__input {
	border: none;
	border-bottom: 2px solid #D1D1D4;
	background: none;
	padding: 10px;
	padding-left: 24px;
	font-weight: 700;
	width: 75%;
	transition: .2s;
}

.login__input:active,
.login__input:focus,
.login__input:hover {
	outline: none;
	border-bottom-color: #6A679E;
}

.login__submit {
	background: #fff;
	font-size: 14px;
	margin-top: 25px;
	padding: 16px 20px;
	border-radius: 26px;
	border: 1px solid #D4D3E8;
	text-transform: uppercase;
	font-weight: 700;
	display: flex;
	align-items: center;
	width: 100%;
	color: #4C489D;
	box-shadow: 0px 2px 2px #5C5696;
	cursor: pointer;
	transition: .2s;
}

.login__submit:active,
.login__submit:focus,
.login__submit:hover {
	border-color: #6A679E;
	outline: none;
}


.login__submit2 {
	background: #fff;
	font-size: 14px;
	margin-top: 30px;
	padding: 16px 20px;
	border-radius: 26px;
	border: 1px solid #D4D3E8; 
	font-weight: 700;
	text-align:left;
	align-items: center;
	width: 100%;
	color: #FF0000;
	box-shadow: 0px 2px 2px #5C5696;
	cursor: pointer;
	transition: .2s;
}

.login__submit2:active,
.login__submit2:focus,
.login__submit2:hover {
	border-color: #6A679E;
	outline: none;
}



.login__submit3 {
	background: #fff;
	font-size: 14px;
	margin-top: 60px;
	padding: 16px 20px;
	border-radius: 26px;
	border: 1px solid #D4D3E8;
	text-transform: uppercase;
	font-weight: 700;
	font-size:18px;
	align-items: center;
	width: 70%;
	color: #4C489D;
	box-shadow: 0px 2px 2px #5C5696;
	cursor: pointer;
	transition: .2s;
	text-align:center;
}

.login__submit3:active,
.login__submit3:focus,
.login__submit3:hover {
	border-color: #6A679E;
	outline: none;
}
.button__icon {
	font-size: 24px;
	margin-left: auto;
	color: #7875B5;
}

.social-login {	
	position: absolute;
	height: 140px;
	width: 160px;
	text-align: center;
	bottom: 0px;
	right: 0px;
	color: #fff;
}

.social-icons {
	display: flex;
	align-items: center;
	justify-content: center;
}

.social-login__icon {
	padding: 20px 10px;
	color: #fff;
	text-decoration: none;	
	text-shadow: 0px 0px 8px #7875B5;
}

.social-login__icon:hover {
	transform: scale(1.5);	
}
</style>
<?php
$member_id  = $_GET['member_id'];
$sql="SELECT member_username, member_id
	  FROM tb_member 
	  WHERE member_id =  ".$member_id."
	  ORDER BY member_id DESC 
	  LIMIT 0 , 1
	  ";
$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
$num_rows =mysqli_num_rows($query);
if($num_rows>=1){
	$rs=mysqli_fetch_array($query);
	$member_id = $rs['member_id'];
}  
?>
<!DOCTYPE html>
<html lang="en" >
<head>
  <meta charset="UTF-8">
  <title>CodePen - Css / html Login form </title>
  <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.2.0/css/all.css'>
<link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.2.0/css/fontawesome.css'><link rel="stylesheet" href="./style.css">

</head>
<body style="font-family:'SukhumvitSet', sans-serif; ">
<!-- partial:index.partial.html -->

<div class="container">

	<div class="screen">
		<div class="screen__content">
		

	
	<div class="login__submit3" style="text-align:center">
						
    <span style="color:#FF0000">ลืมรหัสผ่านใหม่</span><br>
	กรุณากรอกรหัสบัตรประชานชน 13 หลัก <br>เพื่อตรวจสอบข้อมูล

</div> 	 

<iframe id="iFm_save_target" name="iFm_save_target" src="" style="width:800px;height:200px;border:0"></iframe>
	<form id="frm_update_data" name="frm_update_data" class="login" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target"> 
					<input type="hidden"class="login__input" name="SubmitH" value="Submit_10" placeholder="User name"> 
					<input type="text"class="login__input" name="member_id" value="<?php echo $member_id; ?>" placeholder="User name"> 
			<div class="login__field" style="margin-top:-150px;">
					<strong>ชื่อผู้ใช้งานสำหรับล็อกอินเข้าสู่ระบบ</strong>
					<input type="text"  ame="member_password3333" class="login__input "  id="exampleInputBorder" style="font-family:'SukhumvitSet', sans-serif; background-color:#CCCCCC  " placeholder="* รหัสผ่านใหม่ *" value="<?php echo $rs['member_username'] ?>"  readonly="">
				</div> 
				<div class="login__field" style="margin-top:-20px;">
				<strong style="font-size:12px">รหัสผ่านใหม่</strong>
					<input type="text"  name="member_password" value="" class="login__input "  id="exampleInputBorder" style="font-family:'SukhumvitSet', sans-serif; " placeholder="* รหัสผ่านใหม่ *" required>
				</div> 
				<div class="login__field" style="margin-top:-20px;">
				<strong style="font-size:12px">ยืนยันรหัสผ่านใหม่</strong>
					<input type="text"  name="member_username_confirm" value="" class="login__input "  id="exampleInputBorder" style="font-family:'SukhumvitSet', sans-serif; width:300px; " placeholder="* ยืนยันรหัสผ่านใหม่ *" required>
				</div>  
				<input  class="button login__submit" value="เปลี่ยนรหัสผ่านใหม่" type="submit"> 
			</form>
			 
			<div class="social-login"><br />

				
				<div class="social-icons">  
				</div>
			</div>
		</div>
		<div class="screen__background">
			<span class="screen__background__shape screen__background__shape4"></span>
			<span class="screen__background__shape screen__background__shape3"></span>		
			<span class="screen__background__shape screen__background__shape2"></span>
			<span class="screen__background__shape screen__background__shape1"></span>
		</div>	
			
	</div>
</div>
<!-- partial -->
  
</body>
</html>

</script>
<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Select2 -->
<script src="plugins/select2/js/select2.full.min.js"></script>
<!-- Bootstrap4 Duallistbox -->
<script src="plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js"></script>
<!-- InputMask -->
<script src="plugins/moment/moment.min.js"></script>
<script src="plugins/inputmask/jquery.inputmask.min.js"></script>
<!-- date-range-picker -->
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<!-- bootstrap color picker -->
<script src="plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Bootstrap Switch -->
<script src="plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<!-- BS-Stepper -->
<script src="plugins/bs-stepper/js/bs-stepper.min.js"></script>
<!-- dropzonejs -->
<script src="plugins/dropzone/min/dropzone.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>
<!-- AdminLTE for demo purposes -->
<!-- Page specific script -->
<script src="plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2').select2()

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })

    //Datemask dd/mm/yyyy
    $('#datemask').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' })
    //Datemask2 mm/dd/yyyy
    $('#datemask2').inputmask('mm/dd/yyyy', { 'placeholder': 'mm/dd/yyyy' })
    //Money Euro
    $('[data-mask]').inputmask()

    //Date picker
    $('#reservationdate').datetimepicker({
        format: 'L'
    });

    //Date and time picker
    $('#reservationdatetime').datetimepicker({ icons: { time: 'far fa-clock' } });

    //Date range picker
    $('#reservation').daterangepicker()
    //Date range picker with time picker
    $('#reservationtime').daterangepicker({
      timePicker: true,
      timePickerIncrement: 30,
      locale: {
        format: 'MM/DD/YYYY hh:mm A'
      }
    })
    //Date range as a button
    $('#daterange-btn').daterangepicker(
      {
        ranges   : {
          'Today'       : [moment(), moment()],
          'Yesterday'   : [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
          'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
          'Last 30 Days': [moment().subtract(29, 'days'), moment()],
          'This Month'  : [moment().startOf('month'), moment().endOf('month')],
          'Last Month'  : [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        startDate: moment().subtract(29, 'days'),
        endDate  : moment()
      },
      function (start, end) {
        $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'))
      }
    )

    //Timepicker
    $('#timepicker').datetimepicker({
      format: 'LT'
    })

    //Bootstrap Duallistbox
    $('.duallistbox').bootstrapDualListbox()

    //Colorpicker
    $('.my-colorpicker1').colorpicker()
    //color picker with addon
    $('.my-colorpicker2').colorpicker()

    $('.my-colorpicker2').on('colorpickerChange', function(event) {
      $('.my-colorpicker2 .fa-square').css('color', event.color.toString());
    })

    $("input[data-bootstrap-switch]").each(function(){
      $(this).bootstrapSwitch('state', $(this).prop('checked'));
    })

  })
    function ListAmphur(SelectValue,nameaddr,nameaddr2)
	{
		SelectValue = SelectValue.substr(0,2);
		var URL = "get_list.php?SelectValue=" + SelectValue + "&emp=" ;
		ajaxLoad('get', URL, '', nameaddr,'');
		
		var URL = "get_list2.php?SelectValue=xx&emp=" ;
		ajaxLoad('get', URL, '', nameaddr2,'');
	}
	function ListDistrict(SelectValue,nameaddr)
	{
		SelectValue = SelectValue.substr(0,4);
		var URL = "get_list2.php?SelectValue=" + SelectValue + "&emp=" ;
		ajaxLoad('get', URL, '', nameaddr,'');
	} 
	 $(function () {
  bsCustomFileInput.init();
});
</script>
