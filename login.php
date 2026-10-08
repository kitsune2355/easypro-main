<?php
@session_start();
$login_error = '';

if (!empty($_SESSION['login_error'])) {
    $login_error = $_SESSION['login_error'];
    unset($_SESSION['login_error']); // ให้แสดงครั้งเดียว
}
?>
<!DOCTYPE html>
<html lang="th" class="h-full overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, shrink-to-fit=no">
    <title>EasyPro - Building Management System</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/style.css"> 
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.15.4/css/all.css'>
    <link rel="icon" type="image/png" href="../es/logo - easypro2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        prompt: ['Prompt', 'sans-serif'],
                    },
                    colors: {
                        'easy-primary': '#006b9f',
                        'easy-secondary': '#004a6f',
                    }
                }
            }
        }
    </script>
</head>
<body class="font-prompt min-h-[100dvh] h-full relative selection:bg-easy-primary selection:text-white overflow-x-hidden w-full touch-manipulation">

    <div class="fixed inset-0 z-0 w-full h-full overflow-hidden" style="
        background: linear-gradient(rgba(0, 50, 75, 0.75), rgba(0, 25, 40, 0.85)), 
                    url('../es/bg_easypro1.png');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
    ">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 32px 32px;"></div>
    </div>

    <div class="relative z-10 min-h-[100dvh] w-full flex flex-col items-center justify-center p-4 sm:p-6 xl:p-12 overflow-x-hidden">
        <div class="w-full max-w-full xl:max-w-6xl grid grid-cols-1 xl:grid-cols-2 gap-6 xl:gap-20 items-center justify-center">
            
            <div class="hidden xl:block text-white animate__animated animate__fadeInLeft">
                <div class="flex flex-row items-center space-x-6">
                    <div class="inline-flex items-center justify-center w-32 h-32 bg-white backdrop-blur-md rounded-2xl shadow-lg border border-white/20 mb-8 overflow-hidden">
                        <img src="../es/logo - easypro2.png" alt="EasyPro" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" class="w-full h-full object-contain p-2"/>
                        <i class="fas fa-building text-easy-primary text-3xl hidden"></i>
                    </div>
                    <h1 class="text-8xl font-bold leading-tight mb-4">
                        EasyPro <br> 
                    </h1>
                </div>
                
                <p class="text-2xl md:text-3xl font-bold mb-3 max-w-2xl">
                    ระบบจัดการงานบำรุงรักษาอาคาร (CMMS)
                </p>
                <p class="text-base md:text-lg text-blue-100 mb-10 max-w-2xl opacity-90">
                    ควบคุม ติดตาม และวิเคราะห์ข้อมูลในที่เดียว
                </p>

                <div class="space-y-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-12 h-12 bg-white/10 rounded-full flex items-center justify-center backdrop-blur-sm border border-white/10 text-blue-300">
                            <i class="fas fa-tools text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-white">จัดการงานซ่อมบำรุง</h3>
                            <p class="text-sm text-blue-200">ลดเวลา Downtime และวางแผน PM ได้อย่างแม่นยำ</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-12 h-12 bg-white/10 rounded-full flex items-center justify-center backdrop-blur-sm border border-white/10 text-blue-300">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-white">รายงานผลแบบ Real time</h3>
                            <p class="text-sm text-blue-200">สรุปข้อมูลเป็น Dashboard ดูง่าย ตัดสินใจได้ทันที</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-12 h-12 bg-white/10 rounded-full flex items-center justify-center backdrop-blur-sm border border-white/10 text-blue-300">
                            <i class="fas fa-shield-alt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-white">ระบบความปลอดภัยสูง</h3>
                            <p class="text-sm text-blue-200">จัดเก็บข้อมูลบน Cloud อย่างปลอดภัย พร้อมระบบสำรองข้อมูล</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full max-w-[420px] sm:max-w-[460px] mx-auto animate__animated animate__fadeInRight flex flex-col justify-center overflow-hidden">
                
                <div class="text-center mb-4 xl:hidden">
                    <img src="../es/logo - easypro2.png" alt="EasyPro" onerror="this.style.display='none';" class="h-14 mx-auto mb-2 bg-white rounded-xl p-2 shadow-sm"/>
                    <h2 class="text-xl font-bold text-white">EasyPro CMMS</h2>
                </div>

                <div class="bg-white rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] p-5 sm:p-10 relative overflow-hidden max-w-full">
                    <div class="absolute -top-16 -right-16 w-32 h-32 bg-easy-primary/5 rounded-full hidden sm:block"></div>
                    
                    <div class="mb-5 sm:mb-8 text-center relative z-10">
                        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-1.5">เข้าสู่ระบบ</h2>
                        <p class="text-gray-500 text-xs sm:text-sm">กรุณากรอกข้อมูลเพื่อเข้าใช้งานระบบ</p>
                    </div>

                    <form id="form1" name="form1" method="post" action="config_ctrl/checkmember.php" autocomplete="off" class="space-y-4 sm:space-y-6 relative z-10">
                        
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5">ชื่อผู้ใช้งาน</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fas fa-user text-gray-400 group-focus-within:text-easy-primary transition-colors"></i>
                                </div>
                                <input type="text" name="LogInName" id="LogInName"
                                       class="w-full pl-11 pr-4 py-2.5 sm:py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm sm:text-base text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-easy-primary/20 focus:border-easy-primary focus:bg-white transition-all"
                                       placeholder="ระบุชื่อผู้ใช้งาน" required autofocus>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5">รหัสผ่าน</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i class="fas fa-lock text-gray-400 group-focus-within:text-easy-primary transition-colors"></i>
                                </div>
                                <input type="password" name="LogInPassWord" id="password"
                                    class="w-full pl-11 pr-12 py-2.5 sm:py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm sm:text-base text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-easy-primary/20 focus:border-easy-primary focus:bg-white transition-all"
                                    placeholder="ระบุรหัสผ่าน" required>
                                
                                <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-easy-primary focus:outline-none transition-colors">
                                    <i class="fas fa-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="pt-1">
                            <button type="submit" class="w-full bg-easy-primary hover:bg-easy-secondary text-white py-3 sm:py-4 rounded-xl font-semibold text-base sm:text-lg shadow-[0_8px_20px_-6px_rgba(0,107,159,0.5)] transform hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center group">
                                <span>เข้าสู่ระบบ</span>
                                <i class="fas fa-arrow-right ml-2 opacity-80 group-hover:translate-x-1 transition-transform"></i>
                            </button>
                        </div>

                    </form>
                </div>
                
                <div class="mt-6 sm:mt-10 flex flex-col items-center">
                    <a target="_blank" href="https://www.proactivemanagement.co.th/">
                        <div class="bg-white/10 hover:bg-white/20 transition-colors backdrop-blur-sm border border-white/10 rounded-xl px-4 py-2 flex items-center space-x-3 shadow-lg cursor-pointer">
                            <img src="pro.png" alt="Proactive Management" onerror="this.style.display='none';" class="h-7 object-contain bg-white rounded-md p-1"/>
                            <span class="text-xs sm:text-sm font-semibold text-white tracking-wide">PROACTIVE MANAGEMENT</span>
                        </div>
                    
                        <p class="text-[10px] sm:text-xs text-white/40 mt-2.5 font-light text-center">
                            &copy; 2026 PROACTIVE MANAGEMENT CO,.LTD.
                        </p>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script>
        $('form').on('submit', function() {
            const btn = $(this).find('button[type="submit"]');
            btn.prop('disabled', true).addClass('opacity-80 cursor-not-allowed');
            btn.find('span').text('กำลังตรวจสอบข้อมูล...');
            btn.find('i').attr('class', 'fas fa-spinner fa-spin ml-2');
        });

        $('#togglePassword').on('click', function() {
            const passwordField = $('#password');
            const eyeIcon = $('#eyeIcon');
            
            const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
            passwordField.attr('type', type);
            
            eyeIcon.toggleClass('fa-eye fa-eye-slash');
        });

		const loginInput = document.getElementById('LogInName');
		
		window.addEventListener('load', function () {
			if (loginInput) {
				loginInput.focus();
			}
		});
		
		$('#LogInName').on('keydown', function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                $('#password').focus();
            }
        });
		
		const loginError = <?php echo json_encode($login_error); ?>;
		if (loginError) {
			Swal.fire({
				icon: 'error',
				title: 'เข้าสู่ระบบไม่สำเร็จ',
				text: loginError,
				confirmButtonText: 'ลองใหม่',
				confirmButtonColor: '#006b9f',
				timer: 4000,
				timerProgressBar: true
			}).then(() => {
				if (loginInput) {
					loginInput.focus();
				}
			});
		}
    </script>
</body>
</html>