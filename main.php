<?php 

@session_start();

include "config_ctrl/connect.php";
include "config_ctrl/checksession.php";
ini_set('memory_limit', '300M');
ini_set('max_execution_time', 5000);
date_default_timezone_set('Asia/Bangkok');
 

if (isset($_SESSION['is_qr_user']) && $_SESSION['is_qr_user'] === true) {
    header("Location: main2.php#maintenance_request.php");
    exit();
}

/* =========================
   ดึง permission ตาม agency + role
========================= */
$rolePerms = array();

     $agId = (int)$sess_user_agency; 

    $sqlPerm = "SELECT perm_key
                FROM tb_agency_role_permission
                WHERE ag_id = ".$agId." 
                  AND can_view = 1"; 
    $rsPerm = mysqli_query($connect, $sqlPerm);

    if ($rsPerm) {
        while ($rowPerm = mysqli_fetch_assoc($rsPerm)) {
            $rolePerms[] = $rowPerm['perm_key'];
        }
    }  
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <script>
        // แถบเมนูด้านล่าง: มือถือ (<768px) และ iPad/แท็บเล็ตจอสัมผัส (≤1366px) — ตั้งก่อนแสดงผลกันหน้ากระพริบ
        (function () {
            var q = window.matchMedia('(max-width: 767px), (pointer: coarse) and (max-width: 1366px)');
            var set = function () { document.documentElement.classList.toggle('bn-mode', q.matches); };
            set();
            if (q.addEventListener) q.addEventListener('change', set); else if (q.addListener) q.addListener(set);
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyPro</title>
    <script>
        // ขนาดตัวอักษรที่บันทึกไว้ในเครื่อง (ตั้งได้ที่เมนู การแสดงผล) — ใช้ก่อนวาดหน้า
        window.FONT_SCALE_KEY = 'easypro_font_scale';
        window.applyFontScale = function (z) {
            z = Math.min(1.5, Math.max(0.8, parseFloat(z) || 1));
            const h = document.documentElement;
            h.style.setProperty('--fs', z);
            h.classList.toggle('fs-scaled', z !== 1);
            try { localStorage.setItem(window.FONT_SCALE_KEY, String(z)); } catch (e) {}
            return z;
        };
        (function () { let z = 1; try { z = parseFloat(localStorage.getItem(window.FONT_SCALE_KEY)) || 1; } catch (e) {} if (z !== 1) window.applyFontScale(z); })();
    </script>
    <link rel="icon" type="image/png" href="../es/logo - easypro2.png">
    <!-- <link rel="icon" type="image/png" href="../es/Pro_EP1.jpg"> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
    <script src="js/tour-guide.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
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
                        'easy-secondary': ' #004a6f ',
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Prompt', sans-serif;
            font-size: 14px;
        }
        .bg-overlay {
            background: linear-gradient(rgba(0, 74, 111, 0.6), rgba(0, 42, 63, 0.7)), 
                        url('../es/bg_easypro1.png');
            background-size: cover;
            background-position: center;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .glass-card-loading {
            background: rgba(255, 255, 255, 1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .sidebar-transition {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .sidebar-expanded { width: 240px; }
        .sidebar-collapsed { width: 70px; }

        /* ===== ขนาดตัวอักษรทั้งโปรแกรม (เมนู การแสดงผล) — --fs = ตัวคูณ (1 = ปกติ) =====
           หน้าใน iframe: ขยายด้วย transform แล้วลดขนาดกรอบลงตามสัดส่วน => หน้าข้างในเห็นพื้นที่เล็กลง จัดหน้าใหม่ได้เอง (เหมือนซูมเบราว์เซอร์)
           ไม่ใช้ zoom กับ iframe เพราะ Chrome คำนวณตำแหน่งคลิกในกรอบที่ zoom ผิด
           เมนูด้านซ้าย/แถบด้านบน: zoom ที่ลูกของ sidebar (sidebar เองสูงเต็มจอ ถ้า zoom จะยาวเกินจอ) และขยายความกว้าง sidebar ตาม */
        html.fs-scaled .sidebar-expanded { width: calc(240px * var(--fs)); }
        html.fs-scaled .sidebar-collapsed { width: calc(70px * var(--fs)); }
        html.fs-scaled #sidebar > :not(#toggle-btn), html.fs-scaled main > header { zoom: var(--fs); }
        html.fs-scaled #content-iframe { transform-origin: 0 0; transform: scale(var(--fs)); width: calc(100% / var(--fs)) !important; height: calc(100% / var(--fs)) !important; }
        /* หน้าใน iframe ขอแสดงเต็มหน้าจอชั่วคราว ทับ navbar/sidebar (เช่น ภาพขยายในคู่มือ) — ดู setFrameMax()
           กล่องกระจกที่ครอบ iframe ใช้ backdrop-filter ซึ่งทำให้ position:fixed ไม่อิงหน้าจอ จึงปิดไว้ระหว่างนั้น */
        html.frame-max #content-iframe { position: fixed !important; inset: 0 !important; width: 100vw !important; height: 100vh !important; height: 100dvh !important; z-index: 2147483000; border-radius: 0; }
        html.frame-max.fs-scaled #content-iframe { width: calc(100vw / var(--fs)) !important; height: calc(100dvh / var(--fs)) !important; }
        html.frame-max :has(> #content-iframe) { backdrop-filter: none !important; -webkit-backdrop-filter: none !important; }

        /* มือถือ/แท็บเล็ต: 100vh ของ Safari รวมแถบเครื่องมือด้วย ทำให้ส่วนล่างโดนบัง
           จึงใช้ 100dvh (ความสูงที่มองเห็นจริง) ให้กรอบหลักพอดีจอ แล้วให้ iframe กินพื้นที่ที่เหลือทั้งหมด
           หน้าในเลื่อนอยู่ภายใน iframe ชั้นเดียว (ไม่มีการเลื่อนซ้อนกับหน้าหลัก) */
        @media (max-width: 1024px) {
            body {
                min-height: 0 !important;
                height: 100vh;
                height: 100dvh;
            }

            main,
            #sidebar {
                height: 100vh !important;
                height: 100dvh !important;
            }

            .flex-1.px-3.pb-3 {
                min-height: 0;
            }
        }

        @media (max-width: 766px) {
            .sidebar-mobile-hidden {
                transform: translateX(-100%);
            }
            .sidebar-mobile-hidden #mobile-logout-btn {
                display: none !important;
            }
            .sidebar-mobile-visible {
                transform: translateX(0);
                width: 280px !important;
                height: 100dvh !important; 
                display: flex;
                flex-direction: column;
            }
            .sidebar-mobile-visible #mobile-logout-btn {
                display: block !important;
            }
            #toggle-btn { display: none !important; }
            #mobile-close-btn { display: none; }
            .sidebar-mobile-visible #mobile-close-btn { display: block; }
            #sidebar {
                overflow-y: auto;
                height: 100%;
            }
        }
        
        @media (min-width: 769px) {
            #mobile-close-btn { display: none !important; }
        }

        .material-symbols-rounded {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            font-size: 22px;
        }
        
        #content-iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* ===== มือถือ: แถบเมนูด้านล่าง (bottom navigation) ===== */
        /* ใช้กับ มือถือ (<768px) และ iPad/แท็บเล็ตจอสัมผัส (≤1366px) — html.bn-mode ตั้งโดยสคริปต์ใน <head> */
        #bottom-nav { display: none; }
        html.bn-mode #sidebar, html.bn-mode #sidebar-backdrop, html.bn-mode #toggle-btn { display: none !important; }
        html.bn-mode #mobile-toggle { display: none !important; }    /* ใช้แถบล่างแทนปุ่ม ☰ */
        html.bn-mode #app-footer { display: none !important; }
        html.bn-mode #bottom-nav {
            /* จอกว้าง (แท็บเล็ต) ช่องกว้างไม่เกิน 120px จัดกึ่งกลาง — มือถือช่องหดตามจอ */
            display: grid; grid-auto-flow: column; grid-auto-columns: minmax(0, 120px); justify-content: center;
            align-items: end; flex-shrink: 0; position: relative; z-index: 20;
            background: rgba(255, 255, 255, .97); -webkit-backdrop-filter: blur(12px); backdrop-filter: blur(12px);
            border-top: 1px solid rgba(15, 23, 42, .08);
            box-shadow: 0 -6px 20px rgba(15, 23, 42, .12);
            padding: 4px 4px calc(4px + env(safe-area-inset-bottom));
        }
        html.bn-mode #bottom-nav.kb-open { display: none; }          /* คีย์บอร์ดเปิด => ซ่อน ไม่บังช่องกรอก */
        html.bn-mode .content-wrap-m { padding-bottom: 8px !important; }
        @media (min-width: 768px) {
            html.bn-mode .bn-item { font-size: 12px; }
            html.bn-mode #more-sheet .ms-panel { max-width: 600px; margin: 0 auto; }
        }
        .bn-item {
            display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 2px;
            min-height: 52px; padding: 4px 2px; border-radius: 12px; color: #64748b;
            font-size: 10.5px; font-weight: 500; line-height: 1.2; position: relative;
            -webkit-tap-highlight-color: transparent; transition: color .15s;
        }
        .bn-item .bn-ico { width: 44px; height: 28px; border-radius: 999px; display: flex; align-items: center; justify-content: center; transition: background .2s; }
        .bn-item .bn-ico svg { width: 21px; height: 21px; }
        .bn-item.active { color: #005a87; font-weight: 600; }
        .bn-item.active .bn-ico { background: #e0f2fe; }
        .bn-item:active .bn-ico { transform: scale(.92); }
        .bn-label { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .bn-main .bn-ico {
            width: 52px; height: 52px; margin-top: -24px; color: #fff;
            background: linear-gradient(135deg, #0ea5e9, #005a87);
            box-shadow: 0 6px 16px rgba(0, 90, 135, .4); border: 4px solid #fff;
        }
        .bn-main .bn-ico svg { width: 24px; height: 24px; }
        .bn-main.active .bn-ico { background: linear-gradient(135deg, #0284c7, #004a6f); }
        /* แผ่นเมนูเพิ่มเติม (bottom sheet) */
        /* สแกน QR เครื่องจักร (ปุ่มในแถบล่าง) */
        #qr-scan { position: fixed; inset: 0; z-index: 80; background: #0f172a; display: none; flex-direction: column; }
        #qr-scan.open { display: flex; }
        #qr-reader { flex: 1; min-height: 0; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        #qr-reader video { width: 100% !important; height: 100% !important; object-fit: cover; }
        #more-sheet { position: fixed; inset: 0; z-index: 70; visibility: hidden; }
        #more-sheet.open { visibility: visible; }
        #more-sheet .ms-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, .45); opacity: 0; transition: opacity .25s; }
        #more-sheet.open .ms-backdrop { opacity: 1; }
        #more-sheet .ms-panel {
            position: absolute; left: 0; right: 0; bottom: 0; max-height: 85dvh; overflow-y: auto;
            background: #fff; border-radius: 22px 22px 0 0; transform: translateY(100%);
            transition: transform .28s cubic-bezier(.2, .8, .2, 1);
            padding: 8px 16px calc(16px + env(safe-area-inset-bottom));
        }
        #more-sheet.open .ms-panel { transform: translateY(0); }
        .ms-tile { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 10px 4px; border-radius: 14px; color: #334155; font-size: 11.5px; text-align: center; line-height: 1.25; }
        .ms-tile .ms-ico { width: 46px; height: 46px; border-radius: 14px; background: #f1f5f9; color: #005a87; display: flex; align-items: center; justify-content: center; }
        .ms-tile .ms-ico svg { width: 22px; height: 22px; }
        .ms-tile.active .ms-ico { background: #005a87; color: #fff; }
        .ms-tile:active { background: #f8fafc; }
    </style>
</head>
<body class="bg-overlay flex min-h-screen overflow-hidden">

<?php
$needAgencyPopup = 0;
if (isset($_SESSION['sess_user_level_es']) && ($_SESSION['sess_user_level_es'] == 'super_admin' || $_SESSION['sess_user_level_es_check'] == '1')) {
    if($sess_user_agency == '0'){
        $needAgencyPopup = 1;
    }
} 
?>

<?php if (isset($_SESSION['sess_user_level_es']) && ($_SESSION['sess_user_level_es'] == 'super_admin' || $_SESSION['sess_user_level_es_check'] == '1')) { ?>
<div id="agencyModal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-0 md:p-4 backdrop-blur-md bg-black/40 transition-all duration-300">
  
  <div class="relative w-full h-full md:h-auto md:min-h-[min(520px,calc(100dvh-3rem))] md:max-h-[min(860px,calc(100dvh-3rem))] md:max-w-[800px] bg-white rounded-none md:rounded-[24px] shadow-[0_20px_50px_rgba(0,0,0,0.15)] border-0 md:border border-gray-100 overflow-hidden flex flex-col animate-in fade-in zoom-in duration-300">
    
    <div class="flex items-center justify-between px-6 py-4 md:px-8 md:py-6 bg-gradient-to-r from-easy-primary to-easy-primary/80 text-white shrink-0">
      <div>
        <h2 class="text-xl font-bold tracking-tight">เลือกหน่วยงาน</h2>
        <p class="text-white/70 text-xs font-light mt-0.5">Select your company to proceed</p>
      </div>
      <button id="btnAddAgency" class="group flex items-center gap-2 bg-white/10 hover:bg-white/20 border border-white/20 px-4 py-2 rounded-full transition-all text-sm font-medium">
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span class="hidden sm:inline">เพิ่มหน่วยงานใหม่</span>
        <span class="sm:hidden">เพิ่ม</span>
      </button>
    </div>

    <div class="flex-1 min-h-0 flex flex-col p-4 md:px-8 md:py-6 overflow-hidden">
      
      <!-- ช่องค้นหา (กรองรายการในตารางด้านล่างทันทีที่พิมพ์) -->
      <div class="relative mb-4 shrink-0">
        <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
        <input id="fSearch" type="search" autocomplete="off" aria-label="ค้นหาหน่วยงาน" class="w-full bg-gray-50 border-2 border-gray-100 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:bg-white focus:border-easy-primary focus:ring-4 focus:ring-easy-primary/10 outline-none transition-all placeholder:text-gray-400" placeholder="ค้นหารหัสหรือชื่อหน่วยงาน...">
      </div>

      <div class="flex-1 bg-gray-50/50 rounded-2xl border border-gray-100 overflow-hidden shadow-inner flex flex-col min-h-0">
        <div class="grid grid-cols-[80px_1fr] md:grid-cols-[120px_1fr] bg-white border-b px-4 md:px-6 py-4 text-[11px] uppercase tracking-widest font-black text-gray-400 shrink-0">
          <div>Code</div>
          <div>Company Name</div>
        </div>
        
        <div class="flex-1 overflow-y-auto bg-white"> 
          <table class="w-full text-left border-collapse">
            <tbody id="agencyTbody" class="divide-y divide-gray-100">
              <tr>
                <td class="px-4 md:px-6 py-4 text-sm text-gray-400" colspan="2">กำลังโหลดข้อมูล...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <div class="flex items-center justify-between pt-3 pb-1 md:py-3 text-sm shrink-0">
        <div class="text-gray-500 text-xs md:text-sm">
          แสดง <span id="startRange">1</span> - <span id="endRange">10</span> จาก <span id="totalItems">0</span>
        </div>
        <div class="flex gap-2">
          <button id="prevPage" class="p-2 rounded-lg hover:bg-gray-100 disabled:opacity-30 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
          </button>
          <div id="pageNumbers" class="flex gap-1"></div>
          <button id="nextPage" class="p-2 rounded-lg hover:bg-gray-100 disabled:opacity-30 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
          </button>
        </div>
      </div>

      <div class="flex flex-col md:flex-row items-center justify-between mt-3 md:mt-4 gap-4 shrink-0">
        <div class="hidden md:flex items-center gap-2 text-amber-600 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-100">
          <i data-lucide="triangle-alert" class="w-4 h-4"></i>
          <span class="text-[11px] font-medium">โปรดเลือกหน่วยงานเพื่อเริ่มใช้งานระบบ</span>
        </div>
        
        <div class="flex gap-3 w-full md:w-auto">
          <button id="btnSelectAgency" 
                  disabled
                  class="flex-1 md:flex-none bg-easy-primary text-white px-10 py-3 rounded-xl font-bold shadow-lg shadow-easy-primary/25 disabled:opacity-40 disabled:shadow-none transition-all active:scale-95">
            ยืนยัน
          </button>
        </div>
      </div>

    </div>
  </div>
  
</div>
<?php } ?>

<div id="sidebar-backdrop" class="fixed inset-0 bg-black/50 z-20 hidden md:hidden transition-opacity duration-300 opacity-0"></div>

<aside id="sidebar" class="sidebar-transition sidebar-expanded fixed md:relative h-screen bg-easy-secondary/95 backdrop-blur-md text-white flex flex-col shadow-2xl z-30 sidebar-mobile-hidden md:sidebar-expanded">
    
    <button id="toggle-btn" class="hidden md:flex absolute -right-3 top-8 bg-easy-primary text-white w-6 h-6 rounded-full items-center justify-center border-2 border-white hover:bg-white hover:text-easy-primary shadow-lg transition-all z-50">
        <span id="toggle-icon" class="material-symbols-rounded !text-[14px]">chevron_left</span>
    </button>

    <div class="px-4 py-3 flex items-center gap-3 border-b border-white/10 cursor-pointer">
        <div class="bg-white rounded-lg flex items-center justify-center shadow-md overflow-hidden w-10 h-10 shrink-0" onClick="window.location.href='main.php'">
            <!-- <img src="../es/Pro_EP1.jpg" alt="logo" class="w-full h-full object-contain" /> -->
             <img src="../es/logo - easypro2.png" alt="logo" class="w-full h-full object-contain" />
        </div>
        <span id="logo-text" class="sidebar-text font-bold text-lg tracking-tight whitespace-nowrap overflow-hidden transition-all duration-300" onClick="window.location.href='main.php'">
            EasyPro
        </span>
        <button id="mobile-close-btn" class="ml-auto p-1 hover:bg-white/10 rounded-full transition-colors">
            <span class="material-symbols-rounded">close</span>
        </button>
    </div>

    <nav id="menu-container" class="sidebar-nav flex-1 mt-4 px-2.5 space-y-1 overflow-y-auto overflow-x-hidden">
    </nav>

    <div id="mobile-logout-btn" class="p-3 border-t border-white/10 mt-auto">
        <button id="logout-btn" onClick="window.location.href='config_ctrl/logout.php'" class="w-full flex items-center gap-3 p-2.5 rounded-lg text-red-300 hover:bg-red-500/70 hover:text-red-100 transition-all text-sm group">
            <span class="material-symbols-rounded min-w-[24px] text-center">logout</span>
            <span class="sidebar-text logout-label whitespace-nowrap transition-all duration-300">ออกจากระบบ</span>
        </button>
    </div>
</aside>

<main class="flex-1 flex flex-col h-screen overflow-hidden relative">
    <header class="flex items-center justify-between p-3 md:p-4 md:px-6 text-white shrink-0 relative z-10">
      <div class="flex items-center gap-2 md:gap-4 min-w-0">
          <button id="mobile-toggle" class="md:hidden p-2 hover:bg-white/10 rounded-lg shrink-0">
              <span class="material-symbols-rounded">menu</span>
          </button>
          <h3 id="page-title" class="text-base md:text-lg font-medium opacity-90 truncate transition-opacity duration-300">
              หน้าหลัก
          </h3>
      </div>

      <div class="flex items-center justify-end">
      <div class="flex items-center gap-1.5 md:gap-2 bg-black/20 p-1 pr-2 md:pr-3 rounded-full backdrop-blur-sm border border-white/10 max-w-[60%] md:max-w-none">
          
          <div id="agency-selector-trigger" 
              class="flex items-center gap-1.5 bg-white/10 px-2 md:px-3 py-1 rounded-full shrink min-w-0 lg:shrink-0
              <?php echo (isset($_SESSION['sess_user_level_es']) && $_SESSION['sess_user_level_es'] == 'super_admin') ? 'cursor-pointer hover:bg-white/20 transition-colors' : ''; ?>">
              <span class="material-symbols-rounded text-[18px] md:text-sm shrink-0">corporate_fare</span> 
              <p class="text-[11px] md:text-sm font-medium truncate max-w-[70px] md:max-w-[150px] lg:max-w-none">
                  <?php echo isset($sess_agency_name) ? $sess_agency_name : 'หน่วยงาน'; ?>
              </p>
          </div>

          <!-- เมนูผู้ใช้: เปลี่ยนรหัสผ่าน / ออกจากระบบ -->
          <div class="relative shrink-0" id="user-menu-wrapper">
              <button type="button" id="user-menu-btn" aria-haspopup="menu" aria-expanded="false" title="บัญชีผู้ใช้"
                  class="flex items-center gap-1.5 rounded-full px-1 py-0.5 hover:bg-white/15 transition-colors cursor-pointer">
                  <span class="material-symbols-rounded text-easy-primary bg-white rounded-full p-0.5 shrink-0" style="font-size: 18px;">
                      person
                  </span>
                  <span class="text-[11px] md:text-sm font-medium truncate max-w-[60px] md:max-w-[120px]">
                      <?php echo isset($sess_user_name) ? htmlspecialchars($sess_user_name) : 'ผู้ใช้งาน'; ?>
                  </span>
                  <span class="material-symbols-rounded text-[16px] opacity-80 hidden md:inline">expand_more</span>
              </button>
              <div id="user-menu" role="menu" class="hidden absolute right-0 top-full mt-2 w-56 bg-white text-slate-700 rounded-xl shadow-xl border border-slate-100 py-1.5 z-[80]">
                  <div class="px-3 py-2 border-b border-slate-100 mb-1">
                      <p class="text-sm font-semibold text-slate-800 truncate"><?php echo isset($sess_user_name) ? htmlspecialchars($sess_user_name) : 'ผู้ใช้งาน'; ?></p>
                      <p class="text-xs text-slate-400 truncate"><?php echo htmlspecialchars((string)($_SESSION['sess_user_id_es'] ?? '')); ?></p>
                  </div>
                  <button type="button" role="menuitem" id="menu-change-password" class="w-full flex items-center gap-2.5 px-3 py-2 text-sm hover:bg-sky-50 hover:text-sky-700 text-left">
                      <span class="material-symbols-rounded text-[18px]">lock_reset</span> เปลี่ยนรหัสผ่าน
                  </button>
                  <button type="button" role="menuitem" onclick="window.location.href='config_ctrl/logout.php'" class="w-full flex items-center gap-2.5 px-3 py-2 text-sm text-red-500 hover:bg-red-50 text-left">
                      <span class="material-symbols-rounded text-[18px]">logout</span> ออกจากระบบ
                  </button>
              </div>
          </div>
      </div>

        <div class="relative" id="notification-wrapper">
            <button type="button" id="notification-btn" class="relative flex items-center justify-center p-1 md:p-1.5 rounded-full hover:bg-white/20 transition-colors shrink-0 cursor-pointer" title="การแจ้งเตือน">
                <span class="material-symbols-rounded text-[18px] md:text-[20px]">notifications</span>
                
                <span id="notification-badge" class="absolute -top-1 -right-1 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-red-500 rounded-full border-2 border-transparent shadow-sm hidden">
                    0
                </span>
            </button>

            <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-72 md:w-80 bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden z-50">
                <div class="px-4 py-2.5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-800">การแจ้งเตือน</h3>
                </div>
                
                <div id="notification-list" class="max-h-[500px] overflow-y-auto">
                    </div>
                
                <a href="#notifications.php" onClick="navigate('notifications.php'); document.getElementById('notification-dropdown').classList.add('hidden');" class="block text-center px-4 py-2.5 text-xs font-medium text-blue-600 hover:bg-gray-50 transition-colors border-t border-gray-100 bg-white">
                    ดูการแจ้งเตือนทั้งหมด
                </a>
            </div>
        </div>
      </div>
  </header>

    <div class="flex-1 px-3 pb-3 md:px-5 md:pb-5 overflow-hidden content-wrap-m">
        <div class="glass-card-loading rounded-2xl h-full shadow-2xl overflow-hidden relative ิเ">
            <div id="iframe-loading" class="absolute inset-0 flex items-center justify-center bg-white/60 z-10 hidden">
                <div class="flex flex-col items-center gap-3">
                    <div class="animate-spin rounded-full h-10 w-10 border-4 border-easy-primary border-t-transparent"></div>
                    <p class="text-easy-secondary font-medium animate-pulse">กำลังโหลด...</p>
                </div>
            </div>
            <iframe id="content-iframe" name="mainFrame" class="w-full h-full" allowfullscreen></iframe>
        </div>
    </div>

    <div id="app-footer" class="flex justify-end items-center px-6 py-2 bg-easy-secondary/10 shrink-0">
        <a target="_blank" href="https://www.proactivemanagement.co.th/">
            <p class="text-[10px] md:text-xs font-medium text-white/80">PROACTIVE MANAGEMENT CO,.LTD</p>
        </a>
    </div>

    <!-- มือถือ: แถบเมนูด้านล่าง (สร้างจาก menuItems ตามสิทธิ์ ใน renderBottomNav) -->
    <nav id="bottom-nav" aria-label="เมนูหลัก"></nav>
</main>

<!-- มือถือ: เมนูทั้งหมด (เปิดจากปุ่ม "เมนู" ในแถบล่าง) -->
<!-- สแกน QR เครื่องจักรของระบบ (ป้าย QR จาก machine_info_print.php) => หน้าประวัติเครื่อง แจ้งซ่อม / ทำ PM -->
<div id="qr-scan" role="dialog" aria-label="สแกน QR เครื่องจักร">
    <div class="flex items-center gap-3 px-4 pt-[calc(12px+env(safe-area-inset-top))] pb-3 text-white">
        <button type="button" id="qr-close" class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center" aria-label="ปิด"><i data-lucide="x" class="w-5 h-5"></i></button>
        <div class="min-w-0"><div class="font-semibold">สแกน QR เครื่องจักร</div><div class="text-xs text-white/60">ส่องกล้องไปที่ป้าย QR บนเครื่องจักร</div></div>
    </div>
    <div id="qr-reader"></div>
    <div id="qr-msg" class="px-4 pt-3 pb-[calc(16px+env(safe-area-inset-bottom))] text-center text-sm text-white/80 min-h-[56px]"></div>
</div>

<div id="more-sheet" aria-hidden="true">
    <div class="ms-backdrop" data-close-sheet></div>
    <div class="ms-panel" role="dialog" aria-label="เมนูทั้งหมด">
        <div class="w-10 h-1.5 rounded-full bg-slate-200 mx-auto mb-3"></div>
        <div class="flex items-center gap-3 pb-3 mb-2 border-b border-slate-100">
            <span class="material-symbols-rounded text-easy-primary bg-sky-50 rounded-full p-2" style="font-size:24px">person</span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-800 truncate"><?php echo isset($sess_user_name) ? htmlspecialchars($sess_user_name) : 'ผู้ใช้งาน'; ?></p>
                <p class="text-xs text-slate-400 truncate"><?php echo htmlspecialchars(isset($sess_agency_name) ? (string)$sess_agency_name : ''); ?></p>
            </div>
            <button type="button" class="p-2 rounded-full text-slate-400 hover:bg-slate-100" data-close-sheet aria-label="ปิด"><span class="material-symbols-rounded">close</span></button>
        </div>
        <div id="more-grid" class="grid grid-cols-4 gap-1"></div>
        <div class="grid grid-cols-2 gap-2 mt-3 pt-3 border-t border-slate-100">
            <button type="button" id="ms-change-password" class="flex items-center justify-center gap-2 h-11 rounded-xl bg-slate-100 text-slate-700 text-sm font-medium"><span class="material-symbols-rounded text-[19px]">lock_reset</span>เปลี่ยนรหัสผ่าน</button>
            <button type="button" onclick="window.location.href='config_ctrl/logout.php'" class="flex items-center justify-center gap-2 h-11 rounded-xl bg-red-50 text-red-600 text-sm font-medium"><span class="material-symbols-rounded text-[19px]">logout</span>ออกจากระบบ</button>
        </div>
    </div>
</div>

<script>
const rolePerms = <?php echo json_encode($rolePerms); ?>;
const currentUserRole = '<?php echo $_SESSION['sess_user_level_es']; ?>';
const allMenuItems = [
    { icon: 'layout-dashboard', label: 'หน้าหลัก', href: 'dashboard.php', perm: null },
    { icon: 'wrench', label: 'แจ้งซ่อม', href: 'maintenance_request.php', perm: 'repair_request' },
    { icon: 'clipboard-list', label: 'ข้อมูลแจ้งซ่อม', href: 'maintenance_info.php', perm: 'repair_data' },
    { icon: 'hard-hat', label: 'ประวัติงานช่าง', href: 'work_history.php', perm: 'work_history' },
    { icon: 'gauge', label: 'ข้อมูลมิเตอร์', href: 'meter_info.php', perm: 'meter_data' },
    { icon: 'drill', label: 'ข้อมูลอุปกรณ์เครื่องจักร', href: 'machine_info.php', perm: 'machine_data' },
    { icon: 'package', label: 'จัดการสต็อก', href: 'stock.php', perm: 'stock_manage' },
    { icon: 'notebook-pen', label: 'วางแผน PM', href: 'pm.php', perm: 'pm_plan' },
    { icon: 'type', label: 'การแสดงผล', href: 'display_settings.php', perm: null },
    { icon: 'book-open-check', label: 'คู่มือการใช้งาน', href: 'tutorial.php', perm: null },
    { icon: 'settings', label: 'ตั้งค่าข้อมูล', href: 'settings.php', roles: ['admin', 'super_admin'] },
];

const menuItems = allMenuItems.filter(function(item) {
    if (item.roles) {
        return item.roles.indexOf(currentUserRole) !== -1;
    }

    if (item.perm === null) {
        return true;
    }

    return rolePerms.indexOf(item.perm) !== -1;
});

const sidebar = document.getElementById('sidebar');
const iframe = document.getElementById('content-iframe');
// หน้าใน iframe ขอแสดงเต็มหน้าจอชั่วคราว (เช่น ภาพขยายในคู่มือ) — โหลดหน้าใหม่เมื่อไรก็คืนสภาพเดิม กันค้าง
window.setFrameMax = function (on) { document.documentElement.classList.toggle('frame-max', !!on); };
iframe.addEventListener('load', () => window.setFrameMax(false));
const loader = document.getElementById('iframe-loading');
const backdrop = document.getElementById('sidebar-backdrop');
const toggleBtn = document.getElementById('toggle-btn');
const toggleIcon = document.getElementById('toggle-icon');
const mobileToggle = document.getElementById('mobile-toggle');
const mobileCloseBtn = document.getElementById('mobile-close-btn');
const pageTitle = document.getElementById('page-title');
const logoutBtn = document.getElementById('logout-btn');
const btnAddAgency = document.getElementById('btnAddAgency');

function initSidebarState() {
    if (window.innerWidth > 768) {
        const savedState = localStorage.getItem('easypro_sidebar_state');
        if (savedState === 'collapsed') {
            sidebar.classList.remove('sidebar-expanded');
            sidebar.classList.add('sidebar-collapsed');
            toggleIcon.textContent = 'chevron_right';
        } else {
            sidebar.classList.add('sidebar-expanded');
            sidebar.classList.remove('sidebar-collapsed');
            toggleIcon.textContent = 'chevron_left';
        }
    }
}

initSidebarState();

if (btnAddAgency) {
    btnAddAgency.addEventListener('click', function(e) {
        e.preventDefault();
        window.location.href = 'agency.php';
    });
}

function renderMenu(activeHref) {
    const container = document.getElementById('menu-container');
    const isDesktop = window.innerWidth > 768;
    const isCollapsed = sidebar.classList.contains('sidebar-collapsed');
    const logoText = document.getElementById('logo-text');
    const logoutLabel = document.querySelector('.logout-label');
    const mobileLogoutBtn = document.getElementById('mobile-logout-btn');
    
    const shouldCollapse = isDesktop && isCollapsed;
    
    if (shouldCollapse) {
        logoText.classList.add('hidden');
        logoutLabel.classList.add('hidden');
        logoutBtn.classList.add('justify-center');
        logoutBtn.classList.remove('justify-start');
    } else {
        logoText.classList.remove('hidden');
        logoutLabel.classList.remove('hidden');
        logoutBtn.classList.remove('justify-center');
        logoutBtn.classList.add('justify-start');
        mobileLogoutBtn.classList.remove('hidden');
    }

    if (!menuItems.length) {
        container.innerHTML = '<div class="text-white/70 text-sm px-3 py-3">ไม่พบเมนูที่มีสิทธิ์ใช้งาน</div>';
        return;
    }

    container.innerHTML = menuItems.map(function(item) {
        const isActive = item.href === activeHref;
        return '' +
        '<a href="#' + item.href + '" ' +
        'onclick="navigate(\'' + item.href + '\')" ' +
        'class="menu-link flex items-center gap-3 p-2.5 rounded-lg transition-all group ' + 
        (shouldCollapse ? 'justify-center' : 'justify-start') + ' ' + 
        (isActive ? 'bg-white/15 text-white shadow-sm ring-1 ring-white/20' : 'text-white/70 hover:bg-white/10 hover:text-white') + '">' +
            '<i data-lucide="' + item.icon + '" class="w-5 h-5 transition-all ' + 
            (shouldCollapse ? '' : 'min-w-[24px]') + ' ' + 
            (isActive ? 'text-white' : 'text-white/70 group-hover:text-white') + '"></i>' +
            '<span class="sidebar-text whitespace-nowrap transition-all duration-300 text-sm font-medium ' + 
            (shouldCollapse ? 'hidden' : 'block') + '">' + 
            item.label +
            '</span>' +
        '</a>';
    }).join('');

    renderBottomNav(activeHref);

    if (window.lucide) {
        lucide.createIcons();
    }
}

// ===== มือถือ: แถบเมนูด้านล่าง =====
// ช่องหลัก 2 ช่อง (เลือกตามลำดับความสำคัญจากเมนูที่มีสิทธิ์) + ปุ่มแจ้งซ่อมตรงกลาง + สแกน QR + "เมนู" (ที่เหลือทั้งหมด) — 5 ช่องสมมาตร
const BN_MAIN = 'maintenance_request.php';
const BN_PRIORITY = ['dashboard.php', 'maintenance_info.php', 'pm.php', 'work_history.php', 'meter_info.php', 'machine_info.php', 'stock.php'];
const BN_SHORT = { 'maintenance_info.php': 'งานซ่อม', 'pm.php': 'PM', 'work_history.php': 'งานช่าง', 'meter_info.php': 'มิเตอร์', 'machine_info.php': 'เครื่องจักร', 'stock.php': 'สต็อก' };

function bnEsc(s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }

function bottomNavSlots() {
    const allowed = function (href) { return menuItems.some(function (m) { return m.href === href; }); };
    const item = function (href) { return menuItems.find(function (m) { return m.href === href; }); };
    const side = BN_PRIORITY.filter(allowed).slice(0, 2).map(item);
    const main = allowed(BN_MAIN) ? item(BN_MAIN) : null;
    const inBar = side.map(function (m) { return m.href; }).concat(main ? [main.href] : []);
    const rest = menuItems.filter(function (m) { return inBar.indexOf(m.href) === -1; });
    return { side: side, main: main, rest: rest };
}

function renderBottomNav(activeHref) {
    const nav = document.getElementById('bottom-nav');
    if (!nav) return;
    const s = bottomNavSlots();
    const btn = function (m, extra) {
        return '<a href="#' + m.href + '" onclick="navigate(\'' + m.href + '\'); return false;" class="bn-item ' + (extra || '') + (m.href === activeHref ? ' active' : '') + '"' + (m.href === activeHref ? ' aria-current="page"' : '') + '>' +
            '<span class="bn-ico"><i data-lucide="' + m.icon + '"></i></span><span class="bn-label">' + bnEsc(BN_SHORT[m.href] || m.label) + '</span></a>';
    };
    const cells = s.side.map(function (m) { return btn(m); });
    // ปุ่มแจ้งซ่อมไว้ตรงกลาง (หลังช่องที่ 2)
    if (s.main) cells.splice(Math.min(2, cells.length), 0, btn(s.main, 'bn-main'));
    // ทางลัดสแกน QR เครื่องจักร (ทุกคน) — ก่อนปุ่ม "เมนู"
    cells.push('<button type="button" id="bn-scan" class="bn-item"><span class="bn-ico"><i data-lucide="scan-line"></i></span><span class="bn-label">สแกน</span></button>');
    const restActive = s.rest.some(function (m) { return m.href === activeHref; });
    cells.push('<button type="button" id="bn-more" class="bn-item' + (restActive ? ' active' : '') + '" aria-haspopup="dialog">' +
        '<span class="bn-ico"><i data-lucide="layout-grid"></i></span><span class="bn-label">เมนู</span></button>');
    nav.innerHTML = cells.join('');
    document.getElementById('bn-more').addEventListener('click', openMoreSheet);
    document.getElementById('bn-scan').addEventListener('click', openQrScan);

    document.getElementById('more-grid').innerHTML = s.rest.map(function (m) {
        return '<a href="#' + m.href + '" onclick="closeMoreSheet(); navigate(\'' + m.href + '\'); return false;" class="ms-tile' + (m.href === activeHref ? ' active' : '') + '">' +
            '<span class="ms-ico"><i data-lucide="' + m.icon + '"></i></span><span>' + bnEsc(m.label) + '</span></a>';
    }).join('');
}

// ===== สแกน QR เครื่องจักร =====
//  ป้าย QR ของระบบ = .../machine_history_view.php?asset_id=รหัสเครื่อง&ag_id=หน่วยงาน => เปิดหน้านั้นบนเว็บนี้ (ไม่ตามโดเมนใน QR)
//  กล้องใช้ได้เฉพาะ https (หรือ localhost) · โหลดไลบรารีเมื่อกดครั้งแรก
let qrScanner = null;
function qrMsg(html) { document.getElementById('qr-msg').innerHTML = html; }
function loadQrLib() {
    if (window.Html5Qrcode) return Promise.resolve();
    return new Promise(function (res, rej) {
        const s = document.createElement('script');
        s.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
        s.onload = res; s.onerror = rej;
        document.head.appendChild(s);
    });
}
function machineUrlFromQr(text) {
    let u;
    try { u = new URL(String(text).trim(), window.location.href); } catch (e) { return null; }
    if (!/machine_history_view\.php$/i.test(u.pathname)) return null;
    const code = u.searchParams.get('asset_id') || u.searchParams.get('id');
    if (!code) return null;
    const p = new URLSearchParams({ asset_id: code });
    if (u.searchParams.get('ag_id')) p.set('ag_id', u.searchParams.get('ag_id'));
    return 'machine_history_view.php?' + p.toString();
}
async function openQrScan() {
    const box = document.getElementById('qr-scan');
    box.classList.add('open');
    if (window.lucide) lucide.createIcons();
    qrMsg('กำลังเปิดกล้อง...');
    if (!window.isSecureContext) { qrMsg('เปิดกล้องไม่ได้ — ต้องเข้าเว็บผ่าน https'); return; }
    try { await loadQrLib(); } catch (e) { qrMsg('โหลดตัวสแกนไม่สำเร็จ ตรวจสอบอินเทอร์เน็ตแล้วลองใหม่'); return; }
    let done = false;
    qrScanner = new Html5Qrcode('qr-reader');
    try {
        await qrScanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: function (w, h) { const s = Math.floor(Math.min(w, h) * 0.7); return { width: s, height: s }; } },
            function (text) {
                if (done) return;
                const url = machineUrlFromQr(text);
                if (!url) { qrMsg('<span class="text-amber-300">QR นี้ไม่ใช่ป้ายเครื่องจักรของระบบ</span>'); return; }
                done = true;
                qrMsg('<span class="text-emerald-400">✓</span> พบเครื่องจักร กำลังเปิด...');
                closeQrScan().then(function () { window.location.href = url; });
            });
        qrMsg('ส่องกล้องไปที่ป้าย QR บนเครื่องจักร');
    } catch (e) {
        qrMsg('เปิดกล้องไม่ได้ — อนุญาตให้เว็บใช้กล้องในการตั้งค่าเบราว์เซอร์แล้วลองใหม่');
    }
}
async function closeQrScan() {
    document.getElementById('qr-scan').classList.remove('open');
    if (qrScanner) {
        try { if (qrScanner.isScanning) await qrScanner.stop(); qrScanner.clear(); } catch (e) {}
        qrScanner = null;
    }
}
document.getElementById('qr-close').addEventListener('click', closeQrScan);

function openMoreSheet() {
    const sh = document.getElementById('more-sheet');
    sh.classList.add('open'); sh.setAttribute('aria-hidden', 'false');
}
function closeMoreSheet() {
    const sh = document.getElementById('more-sheet');
    sh.classList.remove('open'); sh.setAttribute('aria-hidden', 'true');
}
window.closeMoreSheet = closeMoreSheet;
document.querySelectorAll('[data-close-sheet]').forEach(function (el) { el.addEventListener('click', closeMoreSheet); });
document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { closeMoreSheet(); closeQrScan(); } });
document.getElementById('ms-change-password').addEventListener('click', function () {
    closeMoreSheet();
    const b = document.getElementById('menu-change-password');
    if (b) b.click();
});

// คีย์บอร์ดบนมือถือเปิด (ความสูงจอที่มองเห็นลดลงมาก) => ซ่อนแถบล่าง ไม่ให้ดันขึ้นมาบังช่องกรอก
(function () {
    const vv = window.visualViewport;
    if (!vv) return;
    const base = { h: window.innerHeight };
    const check = function () {
        if (!document.documentElement.classList.contains('bn-mode')) return;
        if (vv.height > base.h) base.h = vv.height;
        document.getElementById('bottom-nav').classList.toggle('kb-open', vv.height < base.h * 0.75);
    };
    vv.addEventListener('resize', check);
    window.addEventListener('orientationchange', function () { setTimeout(function () { base.h = window.innerHeight; check(); }, 400); });
})();

// ===== ต่ออายุการใช้งานขณะเปิดหน้าค้างไว้ (handle_keepalive.php) =====
//  ทุก 5 นาทีขณะแท็บเปิดอยู่ + ทันทีที่กลับมาที่แท็บ (มือถือสลับแอป) — session หายจะสร้างคืนจาก token 30 วัน
(function () {
    let last = Date.now();
    const ping = () => {
        last = Date.now();
        fetch('handle_keepalive.php', { cache: 'no-store', credentials: 'same-origin' }).then(r => r.json()).then(r => {
            if (r && r.success && !r.logged_in) window.location.href = 'login.php';   // token หมดอายุ / ถูกยกเลิก
        }).catch(() => {});
    };
    setInterval(() => { if (document.visibilityState === 'visible') ping(); }, 5 * 60 * 1000);
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible' && Date.now() - last > 60 * 1000) ping(); });
})();

window.navigate = function(href, queryString) {
    if (!href) return;
    if (href !== 'pm.php') {
        localStorage.removeItem('activePMTab');
    }

    window.location.hash = href;
    const selectedMenu = menuItems.find(function(m) { return m.href === href; });

    if (selectedMenu && pageTitle) {
        pageTitle.style.opacity = '0';
        setTimeout(function() {
            pageTitle.textContent = selectedMenu.label;
            pageTitle.style.opacity = '0.9';
        }, 150);
    }

    // ✅ ส่งต่อ query string (เช่น ?asset_code=...&ag_id=...) ที่ติดมากับ main.php
    // ไปยังหน้าที่โหลดใน iframe ด้วย ไม่เช่นนั้นหน้าปลายทาง (เช่น maintenance_request.php)
    // จะไม่เห็นค่าที่ใช้สำหรับเลือกเครื่องจักร/สถานที่ให้อัตโนมัติ
    const targetSrc = href + (queryString || '');
    // เทียบชื่อไฟล์ตรงตัว (เดิมใช้ indexOf => display_settings.php ถูกนับว่าเป็น settings.php แล้วไม่เปลี่ยนหน้า)
    const curFile = (iframe.src.split('?')[0].split('#')[0].split('/').pop()) || '';
    if (curFile !== href || (queryString && iframe.src.indexOf(queryString) === -1)) {
        loader.classList.remove('hidden');
        iframe.src = targetSrc;
    }
    
    if (window.innerWidth <= 768) closeMobileSidebar();
    renderMenu(href);
};

if (toggleBtn) {
    toggleBtn.addEventListener('click', function() {
        if (sidebar.classList.contains('sidebar-expanded')) {
            sidebar.classList.remove('sidebar-expanded');
            sidebar.classList.add('sidebar-collapsed');
            toggleIcon.textContent = 'chevron_right';
            localStorage.setItem('easypro_sidebar_state', 'collapsed');
        } else {
            sidebar.classList.remove('sidebar-collapsed');
            sidebar.classList.add('sidebar-expanded');
            toggleIcon.textContent = 'chevron_left';
            localStorage.setItem('easypro_sidebar_state', 'expanded');
        }
        renderMenu((iframe.src.split('/').pop()) || (menuItems[0] ? menuItems[0].href : ''));
    });
}

function openMobileSidebar() {
    sidebar.classList.remove('sidebar-mobile-hidden');
    sidebar.classList.add('sidebar-mobile-visible');
    backdrop.classList.remove('hidden');
    setTimeout(function() {
        backdrop.classList.add('opacity-100');
    }, 10);
    document.body.style.overflow = 'hidden';
    renderMenu((iframe.src.split('/').pop()) || (menuItems[0] ? menuItems[0].href : ''));
}

function closeMobileSidebar() {
    sidebar.classList.remove('sidebar-mobile-visible');
    sidebar.classList.add('sidebar-mobile-hidden');
    backdrop.classList.remove('opacity-100');
    setTimeout(function() {
        backdrop.classList.add('hidden');
        document.body.style.overflow = '';
    }, 300);
}

if (mobileToggle) mobileToggle.addEventListener('click', openMobileSidebar);
if (mobileCloseBtn) mobileCloseBtn.addEventListener('click', closeMobileSidebar);
if (backdrop) backdrop.addEventListener('click', closeMobileSidebar);

window.addEventListener('resize', function() {
    const currentPath = (iframe.src.split('/').pop()) || (menuItems[0] ? menuItems[0].href : '');
    
    if (window.innerWidth > 768) {
        backdrop.classList.add('hidden');
        sidebar.classList.remove('sidebar-mobile-hidden', 'sidebar-mobile-visible');
        document.body.style.overflow = '';
        
        if (!sidebar.classList.contains('sidebar-collapsed') && !sidebar.classList.contains('sidebar-expanded')) {
            const savedState = localStorage.getItem('easypro_sidebar_state');
            if(savedState === 'collapsed'){
                sidebar.classList.add('sidebar-collapsed');
                toggleIcon.textContent = 'chevron_right';
            } else {
                sidebar.classList.add('sidebar-expanded');
                toggleIcon.textContent = 'chevron_left';
            }
        }
    } else {
        if (!sidebar.classList.contains('sidebar-mobile-visible')) {
            sidebar.classList.add('sidebar-mobile-hidden');
        }
    }
    renderMenu(currentPath);
});

iframe.onload = function() {
    loader.classList.add('hidden');
};

window.onload = function() {
    const hash = window.location.hash.replace('#', '');
    const defaultHref = menuItems.length ? menuItems[0].href : '';
    // ✅ ส่ง query string ของ main.php (เช่น asset_code, ag_id, area_id ที่ส่งมาจาก
    // machine_history_view.php ตอนกดปุ่ม "แจ้งซ่อม") ต่อไปยัง iframe ปลายทางด้วย
    navigate(hash || defaultHref, window.location.search);
};

const agencyTrigger = document.getElementById('agency-selector-trigger');
const isSuperAdmin = <?php echo (isset($_SESSION['sess_user_level_es']) && ($_SESSION['sess_user_level_es'] == 'super_admin' || $_SESSION['sess_user_level_es_check'] == '1')) ? 'true' : 'false'; ?>;

let agencyModalInited = false;

function showAgencyModal() {
    const modal = document.getElementById('agencyModal');
    if (!modal) return;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function hideAgencyModal() {
    const modal = document.getElementById('agencyModal');
    if (!modal) return;

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
}

async function initAgencyModalOnce() {
    const modal = document.getElementById('agencyModal');
    if (!modal || agencyModalInited) return;
    agencyModalInited = true;

    const tbody = document.getElementById('agencyTbody');
    const fSearch = document.getElementById('fSearch');   // ช่องเดียว: ค้นทั้งรหัสและชื่อหน่วยงาน
    const btnSelect = document.getElementById('btnSelectAgency');

    const prevPageBtn = document.getElementById('prevPage');
    const nextPageBtn = document.getElementById('nextPage');
    const pageNumbers = document.getElementById('pageNumbers');
    const startRange = document.getElementById('startRange');
    const endRange = document.getElementById('endRange');
    const totalItems = document.getElementById('totalItems');

    let allData = [];
    let filteredData = [];
    let currentPage = 1;
    const rowsPerPage = 10;
    let selectedId = null;

    function norm(s){ return (s || '').toString().toLowerCase().trim(); }

    function escapeHtml(str){
        return String(str || '')
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;')
            .replace(/'/g,'&#039;');
    }

    function applyFilter() {
        const q = norm(fSearch.value);

        filteredData = allData.filter(function(x) {
            return q === '' || norm(x.code).indexOf(q) !== -1 || norm(x.name).indexOf(q) !== -1;
        });

        currentPage = 1;
        selectedId = null;
        btnSelect.disabled = true;
        renderTable();
    }

    async function selectAgency(idOverride) {
        const agId = idOverride || selectedId;
        if (!agId) return;

        const fd = new FormData();
        fd.append('ag_id', String(agId));

        try {
            const res = await fetch('get_set_agency.php', {
                method: 'POST',
                body: fd,
                credentials: 'same-origin'
            });
            const json = await res.json();
            if (!json.ok) throw new Error(json.message || 'save_failed');

            hideAgencyModal();
            location.reload();
        } catch (err) {
            alert('บันทึกหน่วยงานไม่สำเร็จ: ' + err.message);
        }
    }

    function renderTable() {
        const totalPages = Math.ceil(filteredData.length / rowsPerPage) || 1;
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        const items = filteredData.slice(start, end);

        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="2" class="px-6 py-4 text-sm text-gray-400">ไม่พบข้อมูลหน่วยงาน</td></tr>';
        } else {
            tbody.innerHTML = items.map(function(item) {
                return '' +
                '<tr class="agency-row hover:bg-easy-primary/5 cursor-pointer transition-colors group border-b border-gray-50 ' + 
                (selectedId === item.ag_id ? 'bg-easy-primary/10' : '') + '" data-id="' + item.ag_id + '">' +
                    '<td class="px-6 py-[13.5px] text-sm font-semibold text-gray-700">' + escapeHtml(item.code) + '</td>' +
                    '<td class="px-6 py-[13.5px] text-sm text-gray-600 group-hover:text-easy-primary">' + escapeHtml(item.name) + '</td>' +
                '</tr>';
            }).join('');
        }

        const rows = tbody.querySelectorAll('.agency-row');
        for (let i = 0; i < rows.length; i++) {
            rows[i].addEventListener('click', function() {
                selectedId = parseInt(this.getAttribute('data-id'), 10);
                btnSelect.disabled = false;
                renderTable();
            });

            rows[i].addEventListener('dblclick', function() {
                selectedId = parseInt(this.getAttribute('data-id'), 10);
                btnSelect.disabled = false;
                selectAgency(selectedId);
            });
        }

        totalItems.textContent = filteredData.length;
        startRange.textContent = filteredData.length ? (start + 1) : 0;
        endRange.textContent = Math.min(end, filteredData.length);

        prevPageBtn.disabled = currentPage === 1;
        nextPageBtn.disabled = currentPage === totalPages;

        pageNumbers.innerHTML = '';
        const maxShow = 5;
        let s = Math.max(1, currentPage - 2);
        let e = Math.min(totalPages, s + maxShow - 1);
        s = Math.max(1, e - maxShow + 1);

        for (let p = s; p <= e; p++) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'px-3 py-1 rounded-lg text-sm ' + (p === currentPage ? 'bg-easy-primary text-white' : 'hover:bg-gray-100 text-gray-700');
            btn.textContent = p;
            btn.addEventListener('click', function() {
                currentPage = p;
                renderTable();
            });
            pageNumbers.appendChild(btn);
        }
    }

    try {
        const res = await fetch('get_agency_list.php', { credentials: 'same-origin' });
        const json = await res.json();
        if (!json.ok) throw new Error(json.message || 'load_failed');

        allData = (json.data || []).map(function(x) {
            return {
                ag_id: parseInt(x.ag_id, 10),
                code: String(x.code || ''),
                name: String(x.name || '')
            };
        });

        filteredData = allData;
        renderTable();
    } catch (err) {
        alert('โหลดรายการหน่วยงานไม่สำเร็จ: ' + err.message);
        return;
    }

    fSearch.addEventListener('input', applyFilter);

    prevPageBtn.addEventListener('click', function() {
        if (currentPage > 1) {
            currentPage--;
            renderTable();
        }
    });

    nextPageBtn.addEventListener('click', function() {
        const totalPages = Math.ceil(filteredData.length / rowsPerPage) || 1;
        if (currentPage < totalPages) {
            currentPage++;
            renderTable();
        }
    });

    btnSelect.addEventListener('click', function() {
        selectAgency();
    });
}

if (isSuperAdmin && agencyTrigger) {
    agencyTrigger.addEventListener('click', async function() {
        await initAgencyModalOnce();
        showAgencyModal();
    });
}

const needAgencyPopup = <?php echo ($needAgencyPopup == 1) ? 'true' : 'false'; ?>;
if (isSuperAdmin && needAgencyPopup) {
    (async function() {
        await initAgencyModalOnce();
        showAgencyModal();
    })();
}

</script>
<script>
    // --- ระบบจัดการ Notification (Dynamic + Infinite Scroll) ---

let notifications = [];
let currentPage = 1;      // หน้าปัจจุบันที่โหลดมาแล้ว
let isLoading = false;     // สถานะกำลังโหลดข้อมูล (ป้องกันการส่งซ้ำ)
let hasMore = true;        // เช็คว่ายังมีข้อมูลให้โหลดต่ออีกไหม
let globalUnreadCount = 0;
let notifiedIds = new Set();
let isFirstLoad = true;
let mainLastDateGroup = '';

// ฟังก์ชันสำหรับนำข้อมูลจาก array notifications มาแสดงผลบน Dropdown
function renderNotifications(data, isAppend) {
    const container = document.getElementById('notification-list');
    if (!container) return;

    const oldLoader = container.querySelector('.loading-more-indicator');
    if (oldLoader) {
        oldLoader.remove();
    }

    if (!data || !Array.isArray(data)) {
        data = [];
    }

    if (!isAppend) {
        container.innerHTML = '';
        mainLastDateGroup = ''; // รีเซ็ตค่ากลุ่มวันที่ใหม่เมื่อเปิดดูหรือโหลดใหม่ครั้งแรก
    }

    if (data.length === 0 && !isAppend) {
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center py-8 text-gray-400">
                <span class="material-symbols-rounded text-4xl mb-1 opacity-50">notifications_off</span>
                <p class="text-sm">ไม่มีการแจ้งเตือน</p>
            </div>
        `;
        return;
    }

    data.forEach(item => {
        // เพิ่มเงื่อนไขเช็คการเปลี่ยนกลุ่มวันที่
        if (item.date_group !== mainLastDateGroup) {
            const groupHeader = document.createElement('div');
            // ตกแต่งให้เข้ากับรูปแบบดรอปดาวน์แจ้งเตือนขนาดเล็ก
            groupHeader.className = 'px-4 py-1 mt-2 mb-1 text-xs font-bold text-gray-500 bg-gray-50 border-y border-gray-100 sticky top-0 z-10';
            groupHeader.innerText = item.date_group || 'ไม่ทราบวันที่'; // ป้องกันกรณี API ไม่ส่ง date_group มา
            container.appendChild(groupHeader);
            mainLastDateGroup = item.date_group; // อัปเดตกลุ่มวันที่ล่าสุดที่แสดงผล
        }

        const notifItem = document.createElement('div');
        notifItem.className = `p-4 border-b border-gray-50 cursor-pointer transition-colors hover:bg-gray-50 flex items-start gap-3 ${!item.isRead ? 'bg-blue-50/30' : ''}`;
        
        // ใส่โครงสร้าง HTML ของไอเทมแจ้งเตือนเดิมของคุณ
        notifItem.innerHTML = `
            <div class="mt-1 flex-shrink-0">
                ${!item.isRead ? 
                    '<span class="flex h-2 w-2 relative"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span></span>' 
                    : 
                    '<span class="h-2 w-2 rounded-full bg-gray-200 block"></span>'
                }
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">${item.title}</p>
                <p class="text-xs text-gray-500 truncate mt-0.5 whitespace-pre-line">${item.detail}</p>
                <span class="text-[10px] text-gray-400 block mt-1">${item.time}</span>
            </div>
        `;

        // ใส่ Event Listener สำหรับการคลิก (โค้ดส่วนเดิมของคุณ)
        notifItem.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const notifDropdown = document.getElementById('notification-dropdown');
            if (notifDropdown) {
                notifDropdown.classList.add('hidden');
            }

            switch(item.type) {
                case 'repair_request':
                case 'repair_completed':
                case 'repair_hold':            // พักงานซ่อม
                case 'repair_parts_arrived':   // อะไหล่ของงานที่พักเข้าคลังแล้ว
                    sessionStorage.setItem('_edit_id', item.related_id);
                    navigate('maintenance_info.php');
                    break;

                case 'meter_exceeded':
                    if (item.url && item.url !== '#') {
                        navigate(item.url); 
                    } else {
                        navigate('meter_info.php');
                    }
                    break;
                    
                case 'low_stock':
                    window.parent.navigate('stock.php');
                    break;
                
                case 'pm_advance_alert':
                case 'pm_today_alert':
                    navigate('pm.php'); 
                    break;
                        
                default:
                    navigate('dashboard.php'); 
                    break;
            }

            if (typeof markAsRead === 'function' && !item.isRead) {
                markAsRead(item.id);
            }
        });

        container.appendChild(notifItem);
    });

    if (typeof isLoading !== 'undefined' && isLoading && typeof currentPage !== 'undefined' && currentPage > 1) {
        const loadMoreDiv = document.createElement('div');
        // เพิ่มคลาส loading-more-indicator เพื่อให้ด้านบนหาเจอและลบออกได้
        loadMoreDiv.className = 'loading-more-indicator p-2 text-center text-xs text-gray-400 bg-gray-50 animate-pulse';
        loadMoreDiv.innerText = 'กำลังโหลดข้อมูลเพิ่มเติม...';
        container.appendChild(loadMoreDiv);
    }

    const badge = document.getElementById('notification-badge');
    if (badge) {
        if (typeof globalUnreadCount !== 'undefined' && globalUnreadCount > 0) {
            badge.innerText = globalUnreadCount > 99 ? '99+' : globalUnreadCount;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden'); 
        }
    }
}

// 1. ดึงข้อมูลแจ้งเตือน (ปรับปรุงให้รองรับหน้าถัดไปแบบ Lazy Load)
async function fetchNotifications(isAppend = false) {
    if (isLoading) return; 
    if (isAppend && !hasMore) return; 

    isLoading = true;

    if (!isAppend) {
        currentPage = 1;
        hasMore = true;
        notifications = []; 
    }

    if (isAppend && currentPage > 1) {
        renderNotifications([], true); 
    }

    try {
        const response = await fetch(`notification_api.php?action=get&page=${currentPage}`);
        const json = await response.json();
        
        if (json.status === 'success') {
            if (typeof globalUnreadCount !== 'undefined') {
                globalUnreadCount = json.unread_count;
            }
            
            // Notification OS Logic...
            if ("Notification" in window && Notification.permission === "granted") {
                json.data.forEach(item => {
                    if (!item.isRead && typeof notifiedIds !== 'undefined' && !notifiedIds.has(item.id)) {
                        notifiedIds.add(item.id); 
                        
                        // ตรวจสอบว่าเป็นกลุ่มวันที่ "วันนี้" หรือไม่
                        const isToday = (item.date_group === 'วันนี้');
                        
                        // เงื่อนไข: ทำงานเมื่อไม่ใช่การโหลดครั้งแรก "หรือ" เป็นรายการของวันนี้ (ทุก Type จะเข้าเงื่อนไขนี้)
                        if ((typeof isFirstLoad !== 'undefined' && !isFirstLoad) || isToday) {
                            const osNotif = new Notification(item.title_plain, {
                                body: item.detail,
                                icon: "../es/logo - easypro2.png", 
                                requireInteraction: false 
                            });
                            
                            // จัดการ Event เมื่อผู้ใช้คลิกที่ OS Notification ภายนอก
                            osNotif.onclick = function(event) {
                                event.preventDefault();
                                window.focus(); // ดึงหน้าต่างเบราว์เซอร์ขึ้นมาด้านหน้า
                                
                                // เช็ค Type เพื่อนำทางไปยังหน้าจอที่ถูกต้อง
                                if(['repair_request', 'repair_completed', 'repair_hold', 'repair_parts_arrived'].includes(item.type)){
                                    sessionStorage.setItem('_edit_id', item.related_id);
                                    if(typeof navigate === 'function') navigate('maintenance_info.php');
                                } else if (item.type === 'meter_exceeded') {
                                    if (item.url && item.url !== '#') {
                                        if(typeof navigate === 'function') navigate(item.url);
                                    } else {
                                        if(typeof navigate === 'function') navigate('meter_info.php');
                                    }
                                } else if (item.type === 'low_stock') {
                                    if(typeof navigate === 'function') navigate('stock.php');
                                } else if (item.type === 'pm_advance_alert' || item.type === 'pm_today_alert') {
                                    if(typeof navigate === 'function') navigate('pm.php');
                                } else {
                                    if(typeof navigate === 'function') navigate('dashboard.php');
                                }
                            };
                        }
                    }
                });
            }
            
            if (typeof isFirstLoad !== 'undefined') isFirstLoad = false;
            
            let newData = [];
            if (isAppend) {
                const existingIds = new Set(notifications.map(n => n.id));
                newData = json.data.filter(n => !existingIds.has(n.id));
                notifications = [...notifications, ...newData];
            } else {
                notifications = json.data;
                newData = json.data;
            }

            if (json.data.length < 10) {
                hasMore = false;
            } else {
                currentPage++; 
            }

            isLoading = false; 

            if (isAppend) {
                renderNotifications(newData, true); 
            } else {
                renderNotifications(notifications, false);
            }
        }
    } catch (error) {
        console.error('Error fetching notifications:', error);
        isLoading = false;
    }
}

// 2. บันทึกการอ่าน
async function markAsRead(notificationId) {
    try {
        await fetch('notification_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'read', 
                notification_id: notificationId 
            })
        });
        fetchNotifications(false); // โหลดหน้า 1 ใหม่เพื่ออัปเดตสถานะสีตัวหนังสือ
    } catch (error) {
        console.error('Error updating read status:', error);
    }
}

let notifInterval;
const POLLING_INTERVAL = 60000; // 60 วินาทีดึงอัปเดตใหม่ครั้งหนึ่ง

function startPolling() {
    if (!notifInterval) {
        fetchNotifications(false); // ดึงครั้งแรกทันที
        notifInterval = setInterval(function() {
            const notifDropdown = document.getElementById('notification-dropdown');
            const notifContainer = document.getElementById('notification-list');
            
            // 💡 เคล็ดลับเพิ่มมิติใช้งาน: จะรีเฟรชหน้า 1 อัตโนมัติเฉพาะตอนปิดเมนูอยู่ หรือตอนที่ผู้ใช้เปิดแต่อยู่บนสุด (scrollTop == 0) เท่านั้น 
            // เพื่อไม่ให้รบกวนจังหวะผู้ใช้กำลังไล่ Scroll อ่านประวัติเก่าด้านล่าง
            if (!notifDropdown || notifDropdown.classList.contains('hidden') || (notifContainer && notifContainer.scrollTop === 0)) {
                fetchNotifications(false);
            }
        }, POLLING_INTERVAL);
    }
}

function stopPolling() {
    if (notifInterval) {
        clearInterval(notifInterval);
        notifInterval = null;
    }
}

// ตั้งค่า Event Listeners เมื่อโหลดหน้าเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    if ("Notification" in window) {
        Notification.requestPermission();
    }
    startPolling();

    // ดึง Element ของกล่องแจ้งเตือนมาผูก Event Scroll สำหรับ Infinite Scroll
    const notifContainer = document.getElementById('notification-list');
    if (notifContainer) {
        notifContainer.addEventListener('scroll', function() {
            // เช็คว่า Scroll ลงมาจนเกือบถึงขอบล่าง (ห่างจากขอบล่างน้อยกว่าหรือเท่ากับ 15px)
            const isNearBottom = notifContainer.scrollTop + notifContainer.clientHeight >= notifContainer.scrollHeight - 15;
            
            if (isNearBottom) {
                fetchNotifications(true); // สั่งดึงข้อมูลของหน้าถัดไปมาต่อท้าย
            }
        });
    }

    document.addEventListener("visibilitychange", function() {
        if (document.hidden) {
            stopPolling(); 
        } else {
            fetchNotifications(false);
            startPolling();
        }
    });

    const markAllReadBtn = document.getElementById('mark-all-read-btn');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            markAsRead('all');
        });
    }

    const notifBtn = document.getElementById('notification-btn');
    const notifDropdown = document.getElementById('notification-dropdown');
    const notifWrapper = document.getElementById('notification-wrapper');

    if (notifBtn) {
        notifBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('hidden');
            // ทุกครั้งที่เปิด Dropdown ให้ทำการโหลดหน้าแรกมาแสดงผลใหม่แบบล่าสุดเสมอ
            if(!notifDropdown.classList.contains('hidden')){
                fetchNotifications(false);
            }
        });
    }

    document.addEventListener('click', function(e) {
        if (notifWrapper && !notifWrapper.contains(e.target)) {
            notifDropdown.classList.add('hidden');
        }
    });

    window.addEventListener('blur', function() {
        if (notifDropdown && !notifDropdown.classList.contains('hidden')) {
            notifDropdown.classList.add('hidden');
        }
    });
});

</script>
<script>
    const IDLE_TIMEOUT = 3600000; // 3600 วินาที (1 ชั่วโมง)

    // บันทึกเวลาที่มีการขยับเมาส์ พิมพ์ หรือคลิกล่าสุด
    function updateActivity() {
        localStorage.setItem('last_activity', Date.now());
    }

    // ดักจับการเคลื่อนไหว (หน้าเว็บหลัก)
    ['mousemove', 'click', 'keypress', 'scroll'].forEach(evt => 
        window.addEventListener(evt, updateActivity)
    );

    // เช็คเวลาแบบเงียบๆ ทุก 1 วินาที
    setInterval(() => {
        let lastActivity = localStorage.getItem('last_activity') || Date.now();
        
        // ถ้าเวลาปัจจุบัน ลบ เวลาที่ขยับล่าสุด มีค่าเกิน 1 ชั่วโมง
        if (Date.now() - lastActivity >= IDLE_TIMEOUT) {
            localStorage.removeItem('last_activity'); // เคลียร์ค่าทิ้ง
            
            // แสดง Alert
            Swal.fire({
                icon: 'warning', title: 'เซสชันหมดอายุ!', text: 'กรุณาเข้าสู่ระบบใหม่อีกครั้ง',
                confirmButtonText: 'ตกลง', allowOutsideClick: false, allowEscapeKey: false
            }).then(() => { window.top.location.href = "https://happylandgroup.biz/es/login.php"; });

            setTimeout(() => { window.top.location.href = "https://happylandgroup.biz/es/login.php"; }, 5000);
        }
    }, 1000);
</script>

<script>
// ===== เมนูผู้ใช้ (มุมขวาบน): เปลี่ยนรหัสผ่าน / ออกจากระบบ =====
(function () {
    const btn = document.getElementById('user-menu-btn');
    const menu = document.getElementById('user-menu');
    if (!btn || !menu) return;
    const close = () => { menu.classList.add('hidden'); btn.setAttribute('aria-expanded', 'false'); };
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = menu.classList.toggle('hidden') === false;
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', (e) => { if (!menu.contains(e.target)) close(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });

    const field = (id, label, ph) => `
        <div class="text-left">
            <label for="${id}" class="text-xs text-slate-500 font-semibold mb-1 block">${label}</label>
            <div class="relative">
                <input id="${id}" type="password" autocomplete="${id === 'cp-current' ? 'current-password' : 'new-password'}" placeholder="${ph}"
                    class="w-full pl-3 pr-10 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                <button type="button" data-eye="${id}" tabindex="-1" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-sky-600" aria-label="แสดงรหัสผ่าน">
                    <span class="material-symbols-rounded text-[18px]">visibility</span>
                </button>
            </div>
        </div>`;

    document.getElementById('menu-change-password').addEventListener('click', async () => {
        close();
        const r = await Swal.fire({
            title: 'เปลี่ยนรหัสผ่าน',
            html: `<div class="flex flex-col gap-3">${field('cp-current', 'รหัสผ่านเดิม', 'กรอกรหัสผ่านปัจจุบัน')}${field('cp-new1', 'รหัสผ่านใหม่', 'อย่างน้อย 4 ตัวอักษร')}${field('cp-new2', 'ยืนยันรหัสผ่านใหม่', 'กรอกรหัสผ่านใหม่อีกครั้ง')}</div>`,
            focusConfirm: false, showCancelButton: true,
            confirmButtonText: 'บันทึกรหัสผ่าน', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#006B9F',
            showLoaderOnConfirm: true, allowOutsideClick: () => !Swal.isLoading(),
            didOpen: (pop) => {
                pop.querySelector('#cp-current').focus();
                pop.querySelectorAll('[data-eye]').forEach((b) => b.addEventListener('click', () => {
                    const inp = pop.querySelector('#' + b.dataset.eye);
                    inp.type = inp.type === 'password' ? 'text' : 'password';
                    b.querySelector('span').textContent = inp.type === 'password' ? 'visibility' : 'visibility_off';
                }));
            },
            preConfirm: async () => {
                const cur = document.getElementById('cp-current').value;
                const p1 = document.getElementById('cp-new1').value;
                const p2 = document.getElementById('cp-new2').value;
                if (!cur || !p1 || !p2) return Swal.showValidationMessage('กรุณากรอกให้ครบทุกช่อง');
                if (p1.length < 4) return Swal.showValidationMessage('รหัสผ่านใหม่ต้องมีอย่างน้อย 4 ตัวอักษร');
                if (p1 !== p2) return Swal.showValidationMessage('รหัสผ่านใหม่ทั้งสองช่องไม่ตรงกัน');
                if (p1 === cur) return Swal.showValidationMessage('รหัสผ่านใหม่ต้องไม่ซ้ำกับรหัสผ่านเดิม');
                try {
                    const res = await fetch('handle_account.php?action=change_my_password', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ current: cur, password: p1 })
                    });
                    const j = await res.json();
                    if (!j.success) return Swal.showValidationMessage(j.error || 'เปลี่ยนรหัสผ่านไม่สำเร็จ');
                    return j;
                } catch (e) { return Swal.showValidationMessage('เชื่อมต่อเซิร์ฟเวอร์ไม่ได้'); }
            }
        });
        if (r.isConfirmed) Swal.fire({ icon: 'success', title: 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว', text: 'ครั้งหน้าใช้รหัสผ่านใหม่เข้าสู่ระบบ', timer: 2200, showConfirmButton: false });
    });
})();
</script>

</body>
</html>