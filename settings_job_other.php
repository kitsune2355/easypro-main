<?php
@session_start();
include "config_ctrl/checksession.php";
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ตั้งค่าอื่น ๆ</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Kanit', 'sans-serif'] },
          colors: {
            primary: '#006b9f',      
            primaryDark: '#004a6f',  
            softBlue: '#eef8ff',     
            surface: '#f8fafc',
          }
        }
      }
    }
  </script>

  <style>
    body { font-family: 'Kanit', sans-serif; background-color: #f8fafc; }
    
    /* Custom Scrollbar สำหรับ Desktop Content */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* ซ่อน Scrollbar สำหรับเมนูแนวนอนบนมือถือ แต่ยังเลื่อนได้ */
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    /* Toggle Switch */
    .switch-track {
      width: 44px;
      height: 24px;
      border-radius: 999px;
      background: #e2e8f0;
      padding: 2px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      display: flex;
      align-items: center;
    }
    .switch-dot {
      width: 20px;
      height: 20px;
      border-radius: 999px;
      background: #fff;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
    }
    .switch-track.on { background: #006b9f; }
    .switch-track.on .switch-dot { transform: translateX(20px); }

    /* Animation Panels */
    .setting-panel { display: none; animation: fadeIn 0.3s ease-in-out; }
    .setting-panel.active { display: flex; flex-direction: column; }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(5px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>

<body class="h-screen overflow-hidden flex flex-col md:flex-row text-slate-700 bg-slate-50">

  <div class="md:hidden bg-white border-b border-slate-200 shrink-0 w-full z-10 shadow-sm">
    <div class="flex items-center px-4 py-3 border-b border-slate-100">
      <h1 class="text-lg font-semibold text-slate-800">ตั้งค่าเพิ่มเติม</h1>
    </div>
    <nav id="mobile-nav" class="flex p-2 gap-2 overflow-x-auto hide-scrollbar" aria-label="Mobile Tabs">
      </nav>
  </div>

  <aside id="desktop-sidebar" class="hidden md:flex flex-col w-64 lg:w-72 bg-white border-r border-slate-200 shrink-0 h-full z-10 shadow-sm">
    <div class="p-5 border-b border-slate-100">
      <h1 class="text-xl font-semibold text-slate-800">ตั้งค่าเพิ่มเติม</h1>
      <p class="text-xs text-slate-500 mt-1">จัดการการตั้งค่าภายในระบบแจ้งซ่อม</p>
    </div>
    <div class="p-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wide border-b border-slate-50 mb-2">
      หมวดหมู่การตั้งค่า
    </div>
    <nav id="desktop-nav" class="flex-1 overflow-y-auto px-3 space-y-1">
      </nav>
  </aside>

  <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 w-full bg-slate-50" id="content-panels">
      </main>

  <script>
    const AG_ID = '<?php echo isset($sess_user_agency_es) ? addslashes($sess_user_agency_es) : ""; ?>';
    const USER_ID = '<?php echo isset($sess_user_id) ? addslashes($sess_user_id) : ""; ?>';

    // โครงสร้างเมนู
    const menuConfig = [
      {
        id: 'repair-settings',
        navIcon: 'wrench',
        navLabel: 'ตั้งค่าหน้าแจ้งซ่อม',
        headerTitle: 'ตั้งค่าหน้าแจ้งซ่อม (Repair Form)',
        headerDesc: 'กำหนดการแสดงผลฟิลด์ต่างๆ ภายในฟอร์มแจ้งซ่อม',
        items: [
          {
            key: 'show_urgency_level',
            icon: 'zap',
            title: 'แสดงหัวข้อระดับความเร่งด่วน',
            subtitle: 'Urgency Level',
            desc: 'เปิดเพื่อแสดงตัวเลือก “ต่ำ / ปานกลาง / สูง / เร่งด่วนมาก”<br>หากปิด ระบบจะซ่อนหัวข้อนี้และใช้ค่า <b>medium</b> เป็นค่าเริ่มต้น',
            defaultVal: '1'
          }
        ]
      },
      {
        id: 'email-notification-settings',
        navIcon: 'mail',
        navLabel: 'ตั้งค่าอีเมลแจ้งเตือนแจ้งซ่อม',
        type: 'iframe',
        url: 'settings_email_notify.php',
        headerTitle: 'รายชื่ออีเมลรับแจ้งเตือน',
        headerDesc: 'จัดการรายชื่อผู้ที่ต้องการให้ระบบส่งอีเมลแจ้งเตือนเมื่อมีการแจ้งซ่อม'
      },
      {
        id: 'email-evaluation-settings',
        navIcon: 'star',
        navLabel: 'ตั้งค่าอีเมลแจ้งเตือนประเมินงานซ่อม',
        type: 'iframe',
        url: 'settings_email_notify_eval.php',
        headerTitle: 'รายชื่ออีเมลรับแจ้งเตือนประเมินงานซ่อม',
        headerDesc: 'จัดการรายชื่อผู้ที่ต้องการให้ระบบส่งอีเมลแจ้งเตือนเมื่อมีการประเมินงานซ่อม'
      }
    ];

    let settingsState = {};

    function initUI() {
      const desktopNav = document.getElementById('desktop-nav');
      const mobileNav = document.getElementById('mobile-nav');
      const panelContainer = document.getElementById('content-panels');
      
      desktopNav.innerHTML = '';
      mobileNav.innerHTML = '';
      panelContainer.innerHTML = '';

      menuConfig.forEach((menu, index) => {
        const isActive = index === 0;

        // 1. สร้างปุ่มสำหรับ Desktop Sidebar
        const desktopBtn = document.createElement('button');
        desktopBtn.className = `w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors desktop-menu-btn ${isActive ? 'bg-softBlue text-primary' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'}`;
        desktopBtn.setAttribute('data-target', menu.id);
        desktopBtn.onclick = () => switchPanel(menu.id);
        desktopBtn.innerHTML = `
          <i data-lucide="${menu.navIcon}" class="w-4 h-4 shrink-0"></i>
          <span class="truncate">${menu.navLabel}</span>
        `;
        desktopNav.appendChild(desktopBtn);

        // 2. สร้างปุ่มสำหรับ Mobile Horizontal Nav (รูปแบบเมนูแคปซูล/Pills)
        const mobileBtn = document.createElement('button');
        mobileBtn.className = `whitespace-nowrap flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-full transition-colors mobile-menu-btn ${isActive ? 'bg-primary text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`;
        mobileBtn.setAttribute('data-target', menu.id);
        mobileBtn.onclick = () => switchPanel(menu.id);
        mobileBtn.innerHTML = `
          <i data-lucide="${menu.navIcon}" class="w-4 h-4 shrink-0"></i>
          <span>${menu.navLabel}</span>
        `;
        mobileNav.appendChild(mobileBtn);

        // 3. สร้าง Content Panel
        const panel = document.createElement('section');
        panel.id = `panel-${menu.id}`;
        
        if (menu.type === 'iframe') {
          panel.className = `setting-panel flex-1 max-w-8xl w-full mx-auto hidden h-full ${isActive ? 'active' : ''}`;
          panel.innerHTML = `
            <div class="mb-4 shrink-0">
              <h2 class="text-lg md:text-xl font-semibold text-slate-800">${menu.headerTitle}</h2>
              <p class="text-xs md:text-sm text-slate-500 mt-1">${menu.headerDesc}</p>
            </div>
            <div class="flex-1 bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm relative min-h-[500px]">
              <iframe src="${menu.url}" class="absolute inset-0 w-full h-full border-0"></iframe>
            </div>
          `;
        } else {
          panel.className = `setting-panel max-w-8xl w-full mx-auto hidden ${isActive ? 'active' : ''}`;
          let itemsHTML = '';
          menu.items.forEach(item => {
            if(settingsState[item.key] === undefined) {
               settingsState[item.key] = item.defaultVal;
            }

            itemsHTML += `
              <div class="bg-white border border-slate-200 rounded-xl p-4 md:p-5 shadow-sm mb-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <div class="flex gap-4 items-start sm:items-center">
                    <div class="w-10 h-10 rounded-full bg-softBlue border border-blue-100 flex items-center justify-center shrink-0 text-primary">
                      <i data-lucide="${item.icon}" class="w-5 h-5"></i>
                    </div>
                    <div>
                      <div class="flex items-center gap-2 mb-0.5">
                        <h3 class="text-[15px] font-semibold text-slate-800">${item.title}</h3>
                        <span id="badge-${item.key}" class="text-[10px] px-2 py-0.5 rounded-full font-medium shrink-0"></span>
                      </div>
                      <p class="text-xs text-slate-500 leading-relaxed">${item.desc}</p>
                    </div>
                  </div>
                  <div class="flex justify-end sm:block mt-2 sm:mt-0 shrink-0">
                    <button type="button" id="toggle-${item.key}" onclick="toggleSetting('${item.key}')" class="switch-track">
                      <div class="switch-dot"></div>
                    </button>
                  </div>
                </div>
              </div>
            `;
          });

          panel.innerHTML = `
            <div class="mb-6 shrink-0 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
              <div>
                <h2 class="text-lg md:text-xl font-semibold text-slate-800">${menu.headerTitle}</h2>
                <p class="text-xs md:text-sm text-slate-500 mt-1">${menu.headerDesc}</p>
              </div>
              <button type="button" onclick="saveSettings()"
                class="w-full md:w-auto h-10 px-5 rounded-lg bg-primary text-white text-sm font-medium shadow-sm hover:bg-primaryDark transition-colors flex items-center justify-center gap-2 shrink-0">
                <i data-lucide="save" class="w-4 h-4"></i>
                บันทึกการตั้งค่า
              </button>
            </div>
            <div class="grid grid-cols-1 gap-4">
              ${itemsHTML}
            </div>
          `;
        }
        panelContainer.appendChild(panel);
      });

      if (window.lucide) lucide.createIcons();
      updateAllSwitchesUI();
    }

    function switchPanel(panelId) {
      // ซ่อน Panel ทั้งหมดและแสดงตัวที่เลือก
      document.querySelectorAll('.setting-panel').forEach(p => p.classList.remove('active'));
      document.getElementById(`panel-${panelId}`).classList.add('active');

      // อัปเดตสถานะปุ่มเมนูบน Desktop Sidebar
      document.querySelectorAll('.desktop-menu-btn').forEach(btn => {
        if (btn.getAttribute('data-target') === panelId) {
          btn.className = 'w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors desktop-menu-btn bg-softBlue text-primary';
        } else {
          btn.className = 'w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors desktop-menu-btn text-slate-600 hover:bg-slate-50 hover:text-slate-900';
        }
      });

      // อัปเดตสถานะปุ่มเมนูบน Mobile Horizontal Nav
      document.querySelectorAll('.mobile-menu-btn').forEach(btn => {
        if (btn.getAttribute('data-target') === panelId) {
          btn.className = 'whitespace-nowrap flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-full transition-colors mobile-menu-btn bg-primary text-white shadow-sm';
          // เลื่อนให้ปุ่มที่ถูกเลือกมาอยู่ตรงกลาง (ถ้ามีปุ่มเยอะ)
          btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        } else {
          btn.className = 'whitespace-nowrap flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-full transition-colors mobile-menu-btn bg-slate-100 text-slate-600 hover:bg-slate-200';
        }
      });
    }

    function toggleSetting(key) {
      settingsState[key] = settingsState[key] === '1' ? '0' : '1';
      updateSwitchUI(key);
    }

    function updateSwitchUI(key) {
      const toggle = document.getElementById(`toggle-${key}`);
      const badge = document.getElementById(`badge-${key}`);
      if (!toggle) return;
      
      const isOn = settingsState[key] === '1';
      if (isOn) toggle.classList.add('on');
      else toggle.classList.remove('on');

      if (badge) {
        if (isOn) {
          badge.className = 'text-[10px] px-2 py-0.5 rounded-full font-medium bg-blue-50 text-blue-600 border border-blue-100';
          badge.innerText = 'เปิดใช้งาน';
        } else {
          badge.className = 'text-[10px] px-2 py-0.5 rounded-full font-medium bg-slate-100 text-slate-500 border border-slate-200';
          badge.innerText = 'ปิดใช้งาน';
        }
      }
    }

    function updateAllSwitchesUI() {
      Object.keys(settingsState).forEach(key => updateSwitchUI(key));
    }

    async function loadSettings() {
      try {
        const res = await fetch(`handle_repair_setting.php?action=get_all&ag_id=${encodeURIComponent(AG_ID)}`, { cache: 'no-store' });
        const json = await res.json();

        if (json.success && json.data && json.data.settings) {
          settingsState = { ...settingsState, ...json.data.settings };
          Object.keys(settingsState).forEach(k => {
             settingsState[k] = String(settingsState[k] === '1' ? '1' : '0');
          });
          updateAllSwitchesUI();
        }
      } catch (e) {
        console.error('โหลดข้อมูลตั้งค่าล้มเหลว', e.message);
      }
    }

    async function saveSettings() {
      try {
        Swal.fire({
          title: 'กำลังบันทึก...',
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        const res = await fetch('handle_repair_setting.php?action=save', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            ag_id: AG_ID,
            user_id: USER_ID,
            settings: settingsState
          })
        });

        const json = await res.json();
        Swal.close();

        if (json.success) {
          Swal.fire({ 
            icon: 'success', 
            title: 'บันทึกสำเร็จ', 
            text: 'อัปเดตการตั้งค่าเรียบร้อยแล้ว',
            timer: 1500, 
            showConfirmButton: false,
            customClass: { popup: 'rounded-2xl' }
          });
        } else {
          Swal.fire('เกิดข้อผิดพลาด', json.error || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (e) {
        Swal.close();
        Swal.fire('เชื่อมต่อ API ไม่ได้', e.message, 'error');
      }
    }
    
    initUI();
    loadSettings();
  </script>
</body>
</html>