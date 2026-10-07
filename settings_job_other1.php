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
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Prompt', 'sans-serif'] },
          colors: {
            primary: '#006B9F',
            primaryDark: '#004a6f',
            softBlue: '#eef8ff'
          }
        }
      }
    }
  </script>

  <style>
    body { font-family:'Prompt', sans-serif; background:#f8fafc; }
    .glass-card {
      background: rgba(255,255,255,.92);
      border: 1px solid #e2e8f0;
      box-shadow: 0 18px 45px rgba(15,23,42,.06);
    }
    .side-item {
      display:flex;
      align-items:center;
      gap:.75rem;
      width:100%;
      padding:.85rem .9rem;
      border-radius:1rem;
      font-size:.82rem;
      font-weight:800;
      color:#64748b;
      transition:.2s;
      text-align:left;
    }
    .side-item:hover { background:#f1f5f9; color:#006B9F; }
    .side-item.active {
      background:#eef8ff;
      color:#006B9F;
      border:1px solid rgba(0,107,159,.16);
      box-shadow:0 10px 18px rgba(0,107,159,.08);
    }
    .switch-track {
      width:68px;
      height:38px;
      border-radius:999px;
      background:#cbd5e1;
      padding:4px;
      cursor:pointer;
      transition:.25s;
      border:1px solid rgba(15,23,42,.06);
    }
    .switch-dot {
      width:30px;
      height:30px;
      border-radius:999px;
      background:#fff;
      box-shadow:0 4px 10px rgba(15,23,42,.20);
      transition:.25s;
    }
    .switch-track.on { background:#006B9F; }
    .switch-track.on .switch-dot { transform:translateX(30px); }
    .setting-panel { display:none; }
    .setting-panel.active { display:block; }
  </style>
</head>

<body class="h-screen overflow-hidden">
  <div class="h-full flex flex-col">

    <!-- TOP BAR -->
    <header class="h-16 shrink-0 bg-white border-b border-slate-200 px-4 md:px-6 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-2xl bg-primary text-white flex items-center justify-center shadow-lg shadow-sky-100">
          <i data-lucide="sliders-horizontal" class="w-5 h-5"></i>
        </div>
        <div>
          <h1 class="text-lg md:text-xl font-extrabold text-slate-900 leading-tight">ตั้งค่าอื่น ๆ</h1>
          <p class="text-xs text-slate-500">Other Settings / ตั้งค่าระบบแจ้งซ่อม</p>
        </div>
      </div>

      <button id="btn-save" type="button"
        class="h-10 px-4 md:px-5 rounded-xl bg-primary text-white text-xs md:text-sm font-extrabold shadow hover:bg-primaryDark transition flex items-center justify-center gap-2">
        <i data-lucide="save" class="w-4 h-4"></i>
        บันทึกข้อมูล
      </button>
    </header>

    <div class="flex-1 min-h-0 grid grid-cols-12">

      <!-- SIDEBAR -->
      <aside class="col-span-12 md:col-span-3 xl:col-span-2 bg-white border-r border-slate-200 p-3 md:p-4 overflow-y-auto">
        <div class="mb-4 hidden md:block">
          <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 mb-2">Setting Menu</p>
          <p class="text-xs text-slate-500 leading-relaxed">เปิด/ปิดหัวข้อที่ต้องการให้แสดงในหน้าแจ้งซ่อม</p>
        </div>

        <nav class="flex md:flex-col gap-2 overflow-x-auto md:overflow-visible pb-2 md:pb-0">
          <button type="button" class="side-item active shrink-0" data-panel="panel-repair-form">
            <span class="w-9 h-9 rounded-xl bg-sky-50 text-primary flex items-center justify-center shrink-0">
              <i data-lucide="zap" class="w-4 h-4"></i>
            </span>
            <span class="whitespace-nowrap md:whitespace-normal">แสดงหัวข้อระดับความเร่งด่วน</span>
          </button>
        </nav>
      </aside>

      <!-- CONTENT -->
      <main class="col-span-12 md:col-span-9 xl:col-span-10 overflow-y-auto p-4 md:p-6">

        <!-- PANEL: REPAIR FORM -->
        <section id="panel-repair-form" class="setting-panel active">
          <div class="mb-5">
            <div class="inline-flex items-center gap-2 text-[11px] font-extrabold text-primary bg-sky-50 border border-sky-100 rounded-full px-3 py-1 mb-3">
              <i data-lucide="wrench" class="w-3.5 h-3.5"></i>
              Repair Form Settings
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900">ตั้งค่าหน้าแจ้งซ่อม</h2>
            <p class="text-sm text-slate-500 mt-1">กำหนดว่าจะให้หน้าแจ้งซ่อมแสดงหัวข้อใดบ้าง</p>
          </div>

          <div class="grid grid-cols-1 gap-4 max-w-4xl">
            <article class="glass-card rounded-3xl p-5 md:p-6">
              <div class="flex items-start justify-between gap-5">
                <div class="flex-1 min-w-0">
                  <div id="badge-show-urgency" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-100 mb-4">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    เปิดใช้งาน
                  </div>

                  <div class="flex items-center gap-3 mb-2">
                    <div class="w-11 h-11 rounded-2xl bg-sky-50 text-primary flex items-center justify-center shrink-0">
                      <i data-lucide="zap" class="w-5 h-5"></i>
                    </div>
                    <div>
                      <h3 class="text-lg font-extrabold text-slate-900 leading-tight">แสดงหัวข้อระดับความเร่งด่วน</h3>
                      <p class="text-xs text-slate-400 font-semibold">Urgency Level</p>
                    </div>
                  </div>

                  <p class="text-sm text-slate-500 leading-relaxed mt-3">
                    ถ้าเปิด ระบบจะแสดงปุ่ม “ต่ำ / ปานกลาง / สูง / เร่งด่วนมาก” ในหน้าแจ้งซ่อม<br>
                    ถ้าปิด ระบบจะซ่อนหัวข้อนี้ และยังส่งค่าเริ่มต้นเป็น <b>medium</b> เพื่อป้องกันฐานข้อมูล error
                  </p>
                </div>

                <button id="toggle-show-urgency" type="button" class="switch-track on shrink-0" aria-label="เปิด/ปิดหัวข้อระดับความเร่งด่วน">
                  <div class="switch-dot"></div>
                </button>
              </div>
            </article>
          </div>
        </section>
      </main>
    </div>
  </div>

  <script>
    var AG_ID = '<?php echo isset($sess_user_agency_es) ? addslashes($sess_user_agency_es) : ""; ?>';
    var USER_ID = '<?php echo isset($sess_user_id) ? addslashes($sess_user_id) : ""; ?>';

    var settingsState = {
      show_urgency_level: '1'
    };

    function safeIcons() {
      if (window.lucide && typeof window.lucide.createIcons === 'function') {
        lucide.createIcons();
      }
    }

    function setBadge(badgeId, enabled) {
      var badge = document.getElementById(badgeId);
      if (!badge) return;

      if (enabled) {
        badge.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-100 mb-4';
        badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-emerald-500"></span> เปิดใช้งาน';
      } else {
        badge.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-extrabold bg-slate-100 text-slate-600 border border-slate-200 mb-4';
        badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-slate-400"></span> ปิดใช้งาน';
      }
    }

    function renderState() {
      var enabled = settingsState.show_urgency_level === '1';
      var toggle = document.getElementById('toggle-show-urgency');

      if (toggle) {
        if (enabled) toggle.classList.add('on');
        else toggle.classList.remove('on');
      }

      setBadge('badge-show-urgency', enabled);
    }

    async function loadSettings() {
      try {
        var res = await fetch('handle_repair_setting.php?action=get_all&ag_id=' + encodeURIComponent(AG_ID), {
          cache: 'no-store'
        });
        var text = await res.text();
        var json = JSON.parse(text);

        if (json.success && json.data && json.data.settings) {
          settingsState.show_urgency_level = String(json.data.settings.show_urgency_level || '1') === '1' ? '1' : '0';
          renderState();
        } else {
          Swal.fire('เกิดข้อผิดพลาด', json.error || 'โหลดข้อมูลไม่สำเร็จ', 'error');
        }
      } catch (e) {
        Swal.fire('เชื่อมต่อ API ไม่ได้', e.message, 'error');
      }
    }

    async function saveSettings() {
      try {
        Swal.fire({
          title: 'กำลังบันทึก...',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: function(){ Swal.showLoading(); }
        });

        var res = await fetch('handle_repair_setting.php?action=save', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            ag_id: AG_ID,
            user_id: USER_ID,
            settings: settingsState
          })
        });

        var text = await res.text();
        var json = JSON.parse(text);
        Swal.close();

        if (json.success) {
          Swal.fire({ icon:'success', title:'บันทึกสำเร็จ', timer:1300, showConfirmButton:false });
        } else {
          Swal.fire('เกิดข้อผิดพลาด', json.error || 'บันทึกไม่สำเร็จ', 'error');
        }
      } catch (e) {
        Swal.close();
        Swal.fire('เชื่อมต่อ API ไม่ได้', e.message, 'error');
      }
    }

    document.querySelectorAll('.side-item[data-panel]').forEach(function(btn){
      btn.addEventListener('click', function(){
        var target = btn.getAttribute('data-panel');
        document.querySelectorAll('.side-item[data-panel]').forEach(function(x){ x.classList.remove('active'); });
        document.querySelectorAll('.setting-panel').forEach(function(x){ x.classList.remove('active'); });
        btn.classList.add('active');
        var panel = document.getElementById(target);
        if (panel) panel.classList.add('active');
        safeIcons();
      });
    });

    document.getElementById('toggle-show-urgency').addEventListener('click', function(){
      settingsState.show_urgency_level = settingsState.show_urgency_level === '1' ? '0' : '1';
      renderState();
    });

    document.getElementById('btn-save').addEventListener('click', saveSettings);

    safeIcons();
    renderState();
    loadSettings();
  </script>
</body>
</html>
