
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
	height: 520px;
	width: 520px;
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
	width: 60%;
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
</style><!DOCTYPE html>
<html lang="en" >
<head>
  <meta charset="UTF-8">
  <title>งานสอนดอทคอม</title>
  <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.2.0/css/all.css'>
<link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.2.0/css/fontawesome.css'><link rel="stylesheet" href="./style.css">

</head>
<body style="font-family:'SukhumvitSet', sans-serif; ">
<!-- partial:index.partial.html -->

<div class="container">

	<div class="screen">
		<div class="screen__content">
		

	
	<div class="login__submit3" style="text-align:center">
						
    เข้าสู่ระบบ<br>

สำหรับติวเตอร์</div> 	 
	<form id="form1" name="form1" class="login" method="post" action="config_ctrl/checkmember.php" autocomplete="off"> 
					<input type="hidden"class="login__input" name="type" value="1" placeholder="User name">
	
				<div class="login__field" style="margin-top:-100px;">
					<i class="login__icon fas fa-user"></i>
					<input type="text" class="login__input" name="LogInName" placeholder="User name">
				</div>
				<div class="login__field">
					<i class="login__icon fas fa-lock"></i>
					<input type="password" class="login__input" name="LogInPassWord" placeholder="Password">
				</div>
				<a href="ResetPassWord.php" >ลืมรหัสผ่าน</a>
				<button  class="button login__submit" type="submit">
					<span class="button__text">Log In </span>
					<i class="button__icon fas fa-chevron-right"></i>
				</button>	
				
				
			<a href="create.php" class="btn btn-primary" style="font-weight: bold;font-size: 16px; color: #FF0000">	
			<div class="login__submit2"  align="left">
</h3> 

 สมัครติวเตอร์ 	</div>		</a>
 
 
 
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
