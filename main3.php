<?php 
@session_start();
include "config_ctrl/connect.php";
include "config_ctrl/checksession.php";
ini_set('memory_limit', '300M');
ini_set('max_execution_time', 5000);
date_default_timezone_set('Asia/Bangkok');
if($_SESSION['session_time']!=""){
    $overtime=time()-$_SESSION['session_time'];
    if($overtime>3600){
        echo' <script language="javascript">
        alert("กรุณาล็อกอินเข้าสู่ระบบใหม่");
        window.location.href="config_ctrl/logout.php"
        </script>';
    }else{
        $_SESSION['session_time']=time();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyPro - Dashboard</title>
    <link rel="icon" type="image/png" href="../es/logo - easypro2.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
    <script src="js/tour-guide.js" defer></script>
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
                        'easy-secondary': '#004a6f',
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
                        url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&q=80&w=2000');
            background-size: cover;
            background-position: center;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .sidebar-transition {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .sidebar-expanded { width: 240px; }
        .sidebar-collapsed { width: 70px; }

        @media (max-width: 1024px) {
            /* เปลี่ยนจากล็อกความสูง 100vh เป็นขั้นต่ำแทน */
            body {
                height: auto !important;
                min-height: 100vh;
                overflow-y: auto !important;
            }

            main {
                height: auto !important;
                min-height: 100vh;
                overflow: visible !important; /* ให้เห็น Footer ที่อยู่ล่างสุด */
            }

            /* ปรับความสูงของพื้นที่ iframe ให้แน่นอนเพื่อให้ footer ไม่ถูกเบียด */
            .flex-1.px-3.pb-3 {
                height: 80vh !important; 
                flex: none !important;
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
    </style>
</head>
<body class="bg-overlay flex min-h-screen overflow-hidden"> 
<?php
$needAgencyPopup = 0;
if (isset($_SESSION['sess_user_level_es']) && ($_SESSION['sess_user_level_es'] == 'super_admin' || $_SESSION['sess_user_level_es_check']== '1')) {
	if($sess_user_agency_es=='0'){
      $needAgencyPopup = 1;
    }
} 
?>

<?php if (isset($_SESSION['sess_user_level_es']) && ($_SESSION['sess_user_level_es'] == 'super_admin' || $_SESSION['sess_user_level_es_check']== '1')) { ?>
<div id="agencyModal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-0 md:p-4 backdrop-blur-md bg-black/40 transition-all duration-300">
  
  <div class="relative w-full h-full md:h-auto md:max-w-[800px] bg-white rounded-none md:rounded-[24px] shadow-[0_20px_50px_rgba(0,0,0,0.15)] border-0 md:border border-gray-100 overflow-hidden flex flex-col animate-in fade-in zoom-in duration-300">
    
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

    <div class="flex-1 flex flex-col p-4 md:p-8 overflow-hidden">
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 md:mb-6 shrink-0">
        <div class="relative group">
          <label class="text-[10px] uppercase font-bold text-gray-400 mb-1 ml-1 block">Company Code</label>
          <input id="fCode" class="w-full bg-gray-50 border-2 border-gray-100 rounded-xl px-4 py-3 text-sm focus:bg-white focus:border-easy-primary focus:ring-4 focus:ring-easy-primary/10 outline-none transition-all placeholder:text-gray-300" placeholder="e.g. AG001">
        </div>
        <div class="relative group">
          <label class="text-[10px] uppercase font-bold text-gray-400 mb-1 ml-1 block">Company Name</label>
          <input id="fName" class="w-full bg-gray-50 border-2 border-gray-100 rounded-xl px-4 py-3 text-sm focus:bg-white focus:border-easy-primary focus:ring-4 focus:ring-easy-primary/10 outline-none transition-all placeholder:text-gray-300" placeholder="e.g. Global Logistics Co.">
        </div>
      </div>

      <div class="flex-1 bg-gray-50/50 rounded-2xl border border-gray-100 overflow-hidden shadow-inner flex flex-col min-h-0">
        <div class="grid grid-cols-[80px_1fr] md:grid-cols-[120px_1fr] bg-white border-b px-4 md:px-6 py-4 text-[11px] uppercase tracking-widest font-black text-gray-400 shrink-0">
          <div>Code</div>
          <div>Company Name</div>
        </div>
        
        <div class="flex-1 overflow-y-auto bg-white"> 
          <table class="w-full text-left border-collapse">
            <tbody id="agencyTbody" class="divide-y divide-gray-100">
              <tr class="hover:bg-easy-primary/5 cursor-pointer transition-colors group">
                <td class="px-4 md:px-6 py-4 text-sm font-semibold text-gray-700">AG001</td>
                <td class="px-4 md:px-6 py-4 text-sm text-gray-600 group-hover:text-easy-primary transition-colors">Digital Solution Group</td>
              </tr>
              <tr class="hover:bg-easy-primary/5 cursor-pointer transition-colors group">
                <td class="px-4 md:px-6 py-4 text-sm font-semibold text-gray-700">AG002</td>
                <td class="px-4 md:px-6 py-4 text-sm text-gray-600 group-hover:text-easy-primary transition-colors">Test Company</td>
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

      <div class="flex flex-col md:flex-row items-center justify-between mt-4 md:mt-8 gap-4 shrink-0">
        <div class="hidden md:flex items-center gap-2 text-amber-600 bg-amber-50 px-3 py-1.5 rounded-lg border border-amber-100">
          <i data-lucide="triangle-alert" class="w-4 h-4"></i>
          <span class="text-[11px] font-medium">โปรดเลือกหน่วยงานเพื่อเริ่มใช้งานระบบ</span>
        </div>
        
        <div class="flex gap-3 w-full md:w-auto">
          <!--<button class="flex-1 md:flex-none px-6 py-3 rounded-xl border border-gray-200 text-gray-500 font-bold hover:bg-gray-50 transition-all">ยกเลิก</button>-->
          <button id="btnSelectAgency" 
                  disabled
                  class="flex-1 md:flex-none bg-easy-primary hover:bg-easy-primary-dark text-white px-10 py-3 rounded-xl font-bold shadow-lg shadow-easy-primary/25 disabled:opacity-40 disabled:shadow-none transition-all active:scale-95">
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
            <img src="../es/logo_easypro.png" alt="logo" class="w-full h-full object-contain" />
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

      <div class="flex items-center gap-1.5 md:gap-2 bg-black/20 p-1 pr-2 md:pr-3 rounded-full backdrop-blur-sm border border-white/10 max-w-[60%] md:max-w-none">
          
          <div id="agency-selector-trigger" 
              class="flex items-center gap-1.5 bg-white/10 px-2 md:px-3 py-1 rounded-full shrink min-w-0
              <?php echo ($_SESSION['sess_user_level_es'] == 'super_admin') ? 'cursor-pointer hover:bg-white/20 transition-colors' : ''; ?>">
              <span class="material-symbols-rounded text-[18px] md:text-sm shrink-0">corporate_fare</span> 
              <p class="text-[11px] md:text-sm font-medium truncate max-w-[70px] md:max-w-[150px]">
                  <?php echo $sess_agency_name ?? 'หน่วยงาน'; ?>
              </p>
          </div>

          <div class="flex items-center gap-1.5 shrink-0">
              <span class="material-symbols-rounded text-easy-primary bg-white rounded-full p-0.5 shrink-0" style="font-size: 18px; md:font-size: 20px;">
                  person
              </span>
              <p class="text-[11px] md:text-sm font-medium truncate max-w-[60px] md:max-w-[120px]">
                  <?php echo $sess_user_name ?? 'ผู้ใช้งาน'; ?>
              </p>
          </div>
      </div>
  </header>

    <div class="flex-1 px-3 pb-3 md:px-5 md:pb-5 overflow-hidden">
        <div class="glass-card rounded-2xl h-full shadow-2xl overflow-hidden relative">
            <div id="iframe-loading" class="absolute inset-0 flex items-center justify-center bg-white/60 z-10 hidden">
                <div class="flex flex-col items-center gap-3">
                    <div class="animate-spin rounded-full h-10 w-10 border-4 border-easy-primary border-t-transparent"></div>
                    <p class="text-easy-secondary font-medium animate-pulse">กำลังโหลด...</p>
                </div>
            </div>
            <iframe id="content-iframe" name="mainFrame" class="w-full h-full" allowfullscreen></iframe>
        </div>
    </div>

    <div class="flex justify-end items-center px-6 py-2 bg-easy-secondary/10 shrink-0">
        <a target="_blank" href="https://www.proactivemanagement.co.th/">
            <p class="text-[10px] md:text-xs font-medium text-white/80">PROACTIVE MANAGEMENT CO,.LTD</p>
        </a>
    </div>
</main>

<script>
const menuItems = [
    { icon: 'layout-dashboard', label: 'หน้าหลัก', href: 'dashboard.php' },
    { icon: 'wrench', label: 'แจ้งซ่อม', href: 'maintenance_request.php' },
    { icon: 'clipboard-list', label: 'ข้อมูลแจ้งซ่อม', href: 'maintenance_info.php' },
    { icon: 'gauge', label: 'ข้อมูลมิเตอร์', href: 'meter_info.php' },
    { icon: 'drill', label: 'ข้อมูลอุปกรณ์เครื่องจักร', href: 'machine_info.php' },
    { icon: 'package', label: 'จัดการสต็อก', href: 'stock.php' },
    { icon: 'notebook-pen', label: 'วางแผน PM', href: 'pm.php' },
    { icon: 'settings', label: 'ตั้งค่าข้อมูล', href: 'settings.php' }
];

const sidebar = document.getElementById('sidebar');
const iframe = document.getElementById('content-iframe');
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
    // ทำงานเฉพาะ Desktop
    if (window.innerWidth > 768) {
        const savedState = localStorage.getItem('easypro_sidebar_state');
        if (savedState === 'collapsed') {
            sidebar.classList.remove('sidebar-expanded');
            sidebar.classList.add('sidebar-collapsed');
            toggleIcon.textContent = 'chevron_right';
        } else {
            // Default หรือ Expanded
            sidebar.classList.add('sidebar-expanded');
            sidebar.classList.remove('sidebar-collapsed');
            toggleIcon.textContent = 'chevron_left';
        }
    }
}

initSidebarState();

if (btnAddAgency) {
    btnAddAgency.addEventListener('click', function(e) {
        e.preventDefault(); // ป้องกันการทำงานเดิม (ถ้ามี)
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
    
    // ยุบตัวหนังสือเฉพาะเมื่ออยู่บน Desktop และสถานะคือ Collapsed
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

      container.innerHTML = menuItems.map(item => {
      const isActive = item.href === activeHref;
      return `
          <a href="#${item.href}" 
            onclick="navigate('${item.href}')"
            class="menu-link flex items-center gap-3 p-2.5 rounded-lg transition-all group ${shouldCollapse ? 'justify-center' : 'justify-start'} ${isActive ? 'bg-white/15 text-white shadow-sm ring-1 ring-white/20' : 'text-white/70 hover:bg-white/10 hover:text-white'}">
              <i data-lucide="${item.icon}" class="w-5 h-5 transition-all ${shouldCollapse ? '' : 'min-w-[24px]'} ${isActive ? 'text-white' : 'text-white/70 group-hover:text-white'}"></i>
              
              <span class="sidebar-text whitespace-nowrap transition-all duration-300 text-sm font-medium ${shouldCollapse ? 'hidden' : 'block'}">
                  ${item.label}
              </span>
          </a>
      `;
  }).join('');

  if (window.lucide) {
      lucide.createIcons();
  }
}

window.navigate = (href) => {
    window.location.hash = href;
    const selectedMenu = menuItems.find(m => m.href === href);
    if (selectedMenu && pageTitle) {
        pageTitle.style.opacity = '0';
        setTimeout(() => {
            pageTitle.textContent = selectedMenu.label;
            pageTitle.style.opacity = '0.9';
        }, 150);
    }

    if (iframe.src.indexOf(href) === -1) {
        loader.classList.remove('hidden');
        iframe.src = href;
    }
    
    if (window.innerWidth <= 768) closeMobileSidebar();
    renderMenu(href);
};

toggleBtn.addEventListener('click', () => {
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
    renderMenu(iframe.src.split('/').pop() || menuItems[0].href);
});

function openMobileSidebar() {
    sidebar.classList.remove('sidebar-mobile-hidden');
    sidebar.classList.add('sidebar-mobile-visible');
    backdrop.classList.remove('hidden');
    setTimeout(() => backdrop.classList.add('opacity-100'), 10);
    document.body.style.overflow = 'hidden';
    // บังคับ Render เมนูใหม่เพื่อให้ตัวหนังสือกลับมาแสดง (ใน Mobile ไม่ควรมีสถานะย่อ)
    renderMenu(iframe.src.split('/').pop() || menuItems[0].href);
}

function closeMobileSidebar() {
    sidebar.classList.remove('sidebar-mobile-visible');
    sidebar.classList.add('sidebar-mobile-hidden');
    backdrop.classList.remove('opacity-100');
    setTimeout(() => {
        backdrop.classList.add('hidden');
        document.body.style.overflow = '';
    }, 300);
}

mobileToggle.addEventListener('click', openMobileSidebar);
mobileCloseBtn.addEventListener('click', closeMobileSidebar);
backdrop.addEventListener('click', closeMobileSidebar);

window.addEventListener('resize', () => {
    const currentPath = iframe.src.split('/').pop() || menuItems[0].href;
    
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

iframe.onload = () => loader.classList.add('hidden');
window.onload = () => {
    const hash = window.location.hash.replace('#', '');
    navigate(hash || menuItems[0].href);
};  

const agencyTrigger = document.getElementById('agency-selector-trigger');
const isSuperAdmin = <?php echo (isset($_SESSION['sess_user_level_es']) && ($_SESSION['sess_user_level_es'] == 'super_admin' || $_SESSION['sess_user_level_es_check']== '1')) ? 'true' : 'false'; ?>;

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
  const fCode = document.getElementById('fCode');
  const fName = document.getElementById('fName');
  const btnSelect = document.getElementById('btnSelectAgency');

  const prevPageBtn = document.getElementById('prevPage');
  const nextPageBtn = document.getElementById('nextPage');
  const pageNumbers = document.getElementById('pageNumbers');
  const startRange = document.getElementById('startRange');
  const endRange = document.getElementById('endRange');
  const totalItems = document.getElementById('totalItems');

  // ปุ่ม Cancel (ของคุณไม่มี id) -> ปิดด้วยการจับปุ่มตัวแรกที่มีคำว่า Cancel
  const cancelBtn = modal.querySelector('button:not([id])');
  if (cancelBtn) cancelBtn.addEventListener('click', hideAgencyModal);

  let allData = [];
  let filteredData = [];
  let currentPage = 1;
  const rowsPerPage = 10;
  let selectedId = null;   // ✅ ตัวนี้ต้องอยู่ก่อน selectAgency และทุก handler

  function norm(s){ return (s||'').toString().toLowerCase().trim(); }
  function escapeHtml(str){
    return String(str ?? '')
      .replaceAll('&','&amp;').replaceAll('<','&lt;')
      .replaceAll('>','&gt;').replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function applyFilter() {
    const c = norm(fCode.value);
    const n = norm(fName.value);

    filteredData = allData.filter(x =>
      (c === '' || norm(x.code).includes(c)) &&
      (n === '' || norm(x.name).includes(n))
    );

    currentPage = 1;
    selectedId = null;
    btnSelect.disabled = true;
    renderTable();
  }

  // ✅ ย้ายมาไว้ "ใน" initAgencyModalOnce และ "หลัง" let selectedId = null;
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

    tbody.innerHTML = items.map(item => `
      <tr class="agency-row hover:bg-easy-primary/5 cursor-pointer transition-colors group border-b border-gray-50 ${selectedId===item.ag_id ? 'bg-easy-primary/10' : ''}"
          data-id="${item.ag_id}">
        <td class="px-6 py-[13.5px] text-sm font-semibold text-gray-700">${escapeHtml(item.code)}</td>
        <td class="px-6 py-[13.5px] text-sm text-gray-600 group-hover:text-easy-primary">${escapeHtml(item.name)}</td>
      </tr>
    `).join('');

    tbody.querySelectorAll('.agency-row').forEach(tr => {
      const id = parseInt(tr.dataset.id, 10);

      // คลิกครั้งเดียว = เลือก & ไฮไลต์
      tr.addEventListener('click', () => {
        selectedId = id;
        btnSelect.disabled = false;
        renderTable(); // ให้กลับมาไฮไลต์แถวที่เลือกเหมือนเดิม
      });

      // ดับเบิ้ลคลิก = เลือก + บันทึกทันที
      tr.addEventListener('dblclick', () => {
        selectedId = id;
        btnSelect.disabled = false;
        selectAgency(id);  // ✅ ส่ง id เข้าไปชัดเจน
      });
    });

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
      btn.addEventListener('click', () => { currentPage = p; renderTable(); });
      pageNumbers.appendChild(btn);
    }
  }

  // โหลด API list ครั้งเดียว
  try {
    const res = await fetch('get_agency_list.php', { credentials: 'same-origin' });
    const json = await res.json();
    if (!json.ok) throw new Error(json.message || 'load_failed');

    allData = (json.data || []).map(x => ({
      ag_id: parseInt(x.ag_id, 10),
      code: String(x.code ?? ''),
      name: String(x.name ?? '')
    }));

    filteredData = allData;
    renderTable();
  } catch (err) {
    alert('โหลดรายการหน่วยงานไม่สำเร็จ: ' + err.message);
    return;
  }

  fCode.addEventListener('input', applyFilter);
  fName.addEventListener('input', applyFilter);

  prevPageBtn.addEventListener('click', () => { 
    if (currentPage > 1) {
      currentPage--; 
      renderTable(); 
    }
  });
  nextPageBtn.addEventListener('click', () => { 
    currentPage++; 
    renderTable(); 
  });

  // ปุ่มเลือก = ใช้ selectedId ปัจจุบัน
  btnSelect.addEventListener('click', () => selectAgency());
}

async function selectAgency() {
	if (!selectedId) return;
	
	const fd = new FormData();
	fd.append('ag_id', String(selectedId));
	
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

// ===== Bind click เฉพาะ Superadmin =====
if (isSuperAdmin && agencyTrigger) {
  agencyTrigger.addEventListener('click', async () => {
    await initAgencyModalOnce();
    showAgencyModal();
  });
}

// ===== ถ้าเข้าใหม่แล้วยังไม่เลือกหน่วยงาน (needAgencyPopup==1) ให้เด้งอัตโนมัติ =====
const needAgencyPopup = <?php echo ($needAgencyPopup==1) ? 'true' : 'false'; ?>;
if (isSuperAdmin && needAgencyPopup) {
  (async () => {
    await initAgencyModalOnce();
    showAgencyModal();
  })();
}


// เรียกใช้ Tour หลังจากทุกอย่างพร้อม
//window.addEventListener('load', () => {
//    const isSuperAdmin = <?php echo (isset($_SESSION['sess_user_level_es']) && ($_SESSION['sess_user_level_es'] == 'super_admin' || $_SESSION['sess_user_level_es_check'] == '1')) ? 'true' : 'false'; ?>;
//    const needAgency = <?php echo ($needAgencyPopup==1) ? 'true' : 'false'; ?>;
//
//    // 1. ถ้าเป็น Super Admin และต้องเลือกหน่วยงาน
//    if (isSuperAdmin && needAgency) {
//        // เช็คว่าเคยดู Tour เลือกหน่วยงานหรือยัง
//        if (!localStorage.getItem('hasSeenAgencyTour')) {
//            setTimeout(() => {
//                if (typeof window.startTour === 'function') window.startTour('agency');
//            }, 1000);
//        }
//    } 
//    // 2. หน้าหลัก (Main Tour)
//    else {
//        // เช็คว่าเคยดู Tour หน้าหลักหรือยัง
//        if (!localStorage.getItem('hasSeenMainTour')) {
//            setTimeout(() => {
//                if (typeof window.startTour === 'function') window.startTour('main');
//            }, 1000);
//        }
//    }
//});

</script>

</body>
</html>