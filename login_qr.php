<?php
error_reporting(E_ALL ^ E_NOTICE);

// 1. รับค่า Username ของหน่วยงานจาก URL เช่น login_qr.php?u=user_bam_1
$u = isset($_GET['u']) ? trim($_GET['u']) : '';

// 2. เตรียม URL สำหรับฝังใน QR Code
$target_autologin_url = "https://happylandgroup.biz/es/config_ctrl/qr_autologin.php";
if (!empty($u)) {
    $target_autologin_url .= "?u=" . urlencode($u);
}

// 3. เรียกใช้งาน QR Code API
// ecc=H = ระดับแก้ไขข้อผิดพลาดสูงสุด (30%) เพื่อให้ QR ยังสแกนได้แม้มีโลโก้บังตรงกลาง
$qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=220x220&ecc=H&data=" . urlencode($target_autologin_url);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Login - แจ้งซ่อม EasyPro</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.15.4/css/all.css'>
    <link rel="icon" type="image/png" href="../es/logo - easypro2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
<body class="font-prompt min-h-screen relative selection:bg-easy-primary selection:text-white flex items-center justify-center">

    <div class="fixed inset-0 z-0" style="
        background: linear-gradient(rgba(0, 50, 75, 0.85), rgba(0, 25, 40, 0.95)), 
                    url('../es/bg_easypro1.png');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
    ">
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 32px 32px;"></div>
    </div>

    <div class="relative z-10 w-full max-w-[420px] p-6 animate__animated animate__zoomIn">
        
        <div class="bg-white rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.4)] p-8 text-center relative overflow-hidden">
            <div class="absolute -top-16 -right-16 w-32 h-32 bg-easy-primary/5 rounded-full"></div>
            
            <div class="relative z-10">
                <!--<img src="../es/images.png" alt="EasyPro" onerror="this.style.display='none';" class="h-14 mx-auto mb-4"/>-->
				<img src="../es/pro.png" alt="EasyPro" onerror="this.style.display='none';" class="h-14 mx-auto mb-4"/>
 
                <h2 class="text-2xl font-bold text-gray-900 mb-1">ระบบแจ้งซ่อมออนไลน์</h2>
                
                <?php if (empty($u)): ?>
                    <p class="text-red-500 text-sm mb-6 mt-2 font-medium">
                        <i class="fas fa-exclamation-triangle mr-1"></i> ไม่สามารถสร้าง QR Code ได้
                    </p>
                    <div class="bg-red-50 text-red-700 text-sm rounded-xl p-4 text-left border border-red-100 mb-6">
                        <p class="font-semibold mb-1">สาเหตุ:</p>
                        <p class="text-xs text-red-600 leading-relaxed">
                            ไม่มีการระบุชื่อบัญชีผู้ใช้ใน URL กรุณาเรียกใช้งานหน้านี้โดยต่อท้ายด้วยรหัสผู้ใช้ เช่น:
                        </p>
                        <code class="block bg-white p-2 rounded border border-red-200 text-xs font-mono mt-2 break-all text-red-800">
                            login_qr.php?u=username
                        </code>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-sm mb-6">สแกน QR Code เพื่อเข้าสู่ระบบแจ้งซ่อมทันที</p>

                    <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-2xl p-4 flex justify-center items-center mb-6 mx-auto w-fit">
                        <div class="relative w-[220px] h-[220px]">
                            <img src="<?php echo $qr_image_url; ?>" 
                                 alt="QR Code สำหรับแจ้งซ่อม" 
                                 class="rounded-xl shadow-sm mix-blend-multiply w-full h-full">
                            <!-- โลโก้ตรงกลาง QR Code -->
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white rounded-full p-1.5 shadow-md flex items-center justify-center">
                                <img src="../es/logo - easypro2.png" 
                                     alt="Logo" 
                                     onerror="this.parentElement.style.display='none';"
                                     class="w-10 h-10 object-contain rounded-full">
                            </div>
                        </div>
                    </div>

                    <div class="bg-blue-50 text-easy-primary text-sm rounded-xl p-3 flex items-center justify-center space-x-2">
                        <i class="fas fa-mobile-alt"></i>
                        <span>สแกนด้วยกล้องมือถือเพื่อเข้าสู่ระบบทันที</span>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>

        <div class="mt-8 flex flex-col items-center">
                    <a target="_blank" href="https://www.proactivemanagement.co.th/">
                        <div class="bg-white/10 hover:bg-white/20 transition-colors backdrop-blur-sm border border-white/10 rounded-xl px-4 py-2.5 flex items-center space-x-3 shadow-lg cursor-pointer">
                            <!-- โลโก้บริษัทผู้สร้างระบบ -->
                            <img src="pro.png" alt="Proactive Management" onerror="this.style.display='none';" class="h-8 object-contain bg-white rounded-md p-1"/>
                            <span class="text-sm font-semibold text-white tracking-wide">PROACTIVE MANAGEMENT</span>
                        </div>
                    
                        <p class="text-xs text-white/40 mt-3 font-light text-center">
                            &copy; 2026 PROACTIVE MANAGEMENT CO,.LTD.
                        </p>
                    </a>
                </div>
        
    </div>

</body>
</html>