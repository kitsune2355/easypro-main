<?php
@session_start();
// ตั้งค่าการบันทึกผล PM — ลายมือชื่อผู้ตรวจสอบ (บังคับ/ไม่บังคับ) และวันที่ทำ PM (วันนี้ / เลือกวันที่ภายในช่วงสัญญา/แผน)
//  รูปแบบหน้าเดียวกับ settings_job_other.php
include "config_ctrl/checksession.php";
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>การบันทึกผล PM</title>

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

    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    /* Toggle Switch (แบบเดียวกับ settings_job_other.php) */
    .switch-track { width: 44px; height: 24px; border-radius: 999px; background: #e2e8f0; padding: 2px; cursor: pointer; transition: background-color 0.3s ease; display: flex; align-items: center; }
    .switch-dot { width: 20px; height: 20px; border-radius: 999px; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1); }
    .switch-track.on { background: #006b9f; }
    .switch-track.on .switch-dot { transform: translateX(20px); }
    .switch-track:disabled { cursor: not-allowed; opacity: .6; }

    /* ตัวเลือกวันที่ทำ PM */
    .mode-opt { border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 14px; cursor: pointer; display: flex; gap: 12px; align-items: flex-start; transition: border-color .2s, background-color .2s; text-align: left; width: 100%; }
    .mode-opt:hover { border-color: #bfdbfe; }
    .mode-opt.on { border-color: #006b9f; background: #eef8ff; }
    .mode-opt .dot { width: 18px; height: 18px; border-radius: 999px; border: 2px solid #cbd5e1; flex: none; margin-top: 2px; display: flex; align-items: center; justify-content: center; }
    .mode-opt.on .dot { border-color: #006b9f; }
    .mode-opt.on .dot::after { content: ''; width: 8px; height: 8px; border-radius: 999px; background: #006b9f; }
    .mode-opt:disabled { cursor: not-allowed; opacity: .7; }

    .setting-panel { display: none; animation: fadeIn 0.3s ease-in-out; }
    .setting-panel.active { display: flex; flex-direction: column; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>

<body class="h-screen overflow-hidden flex flex-col md:flex-row text-slate-700 bg-slate-50">

  <div class="md:hidden bg-white border-b border-slate-200 shrink-0 w-full z-10 shadow-sm">
    <div class="flex items-center px-4 py-3 border-b border-slate-100">
      <h1 class="text-lg font-semibold text-slate-800">ตั้งค่าแผน PM</h1>
    </div>
    <nav id="mobile-nav" class="flex p-2 gap-2 overflow-x-auto hide-scrollbar" aria-label="Mobile Tabs"></nav>
  </div>

  <aside id="desktop-sidebar" class="hidden md:flex flex-col w-64 lg:w-72 bg-white border-r border-slate-200 shrink-0 h-full z-10 shadow-sm">
    <div class="p-5 border-b border-slate-100">
      <h1 class="text-xl font-semibold text-slate-800">ตั้งค่าแผน PM</h1>
      <p class="text-xs text-slate-500 mt-1">จัดการการตั้งค่าการบันทึกผลใบงาน PM</p>
    </div>
    <div class="p-3 text-[10px] font-semibold text-slate-400 uppercase tracking-wide border-b border-slate-50 mb-2">
      หมวดหมู่การตั้งค่า
    </div>
    <nav id="desktop-nav" class="flex-1 overflow-y-auto px-3 space-y-1"></nav>
  </aside>

  <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8 w-full bg-slate-50" id="content-panels">
    <!-- การบันทึกผล PM -->
    <section id="panel-pm-record" class="setting-panel active max-w-8xl w-full mx-auto">
      <div class="mb-6 shrink-0 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
        <div>
          <h2 class="text-lg md:text-xl font-semibold text-slate-800">การบันทึกผล PM (PM Worksheet)</h2>
          <p class="text-xs md:text-sm text-slate-500 mt-1">กำหนดการลงชื่อและวันที่ทำ PM ตอนบันทึกผลใบงาน ทั้งทีละเครื่อง และบันทึกพร้อมกันหลายเครื่อง</p>
        </div>
        <button type="button" id="btn-save"
          class="w-full md:w-auto h-10 px-5 rounded-lg bg-primary text-white text-sm font-medium shadow-sm hover:bg-primaryDark transition-colors flex items-center justify-center gap-2 shrink-0 disabled:opacity-50 disabled:cursor-not-allowed">
          <i data-lucide="save" class="w-4 h-4"></i>
          บันทึกการตั้งค่า
        </button>
      </div>

      <p id="no-perm" class="hidden mb-4 text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg p-2.5 flex items-center gap-1.5"><i data-lucide="lock" class="w-3.5 h-3.5"></i> เปลี่ยนการตั้งค่าได้เฉพาะผู้ดูแลระบบ</p>

      <div class="grid grid-cols-1 gap-4">
        <!-- ลายมือชื่อผู้ตรวจสอบ -->
        <div class="bg-white border border-slate-200 rounded-xl p-4 md:p-5 shadow-sm">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex gap-4 items-start sm:items-center">
              <div class="w-10 h-10 rounded-full bg-softBlue border border-blue-100 flex items-center justify-center shrink-0 text-primary">
                <i data-lucide="signature" class="w-5 h-5"></i>
              </div>
              <div>
                <div class="flex items-center gap-2 mb-0.5">
                  <h3 class="text-[15px] font-semibold text-slate-800">บังคับลายมือชื่อผู้ตรวจสอบ</h3>
                  <span id="badge-sign" class="text-[10px] px-2 py-0.5 rounded-full font-medium shrink-0"></span>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed">เปิดเพื่อบังคับให้เซ็นชื่อผู้ตรวจสอบก่อนบันทึกผลทุกครั้ง<br>หากปิด บันทึกผลได้โดยไม่ต้องเซ็น (ถ้าเซ็นไว้ ลายเซ็นยังแสดงในใบงานตามปกติ)</p>
              </div>
            </div>
            <div class="flex justify-end sm:block mt-2 sm:mt-0 shrink-0">
              <button type="button" id="toggle-sign" class="switch-track" aria-label="บังคับลายมือชื่อผู้ตรวจสอบ"><div class="switch-dot"></div></button>
            </div>
          </div>
        </div>

        <!-- วันที่ทำ PM -->
        <div class="bg-white border border-slate-200 rounded-xl p-4 md:p-5 shadow-sm">
          <div class="flex gap-4 items-start">
            <div class="w-10 h-10 rounded-full bg-softBlue border border-blue-100 flex items-center justify-center shrink-0 text-primary">
              <i data-lucide="calendar-check" class="w-5 h-5"></i>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-0.5">
                <h3 class="text-[15px] font-semibold text-slate-800">วันที่ทำ PM</h3>
                <span id="badge-date" class="text-[10px] px-2 py-0.5 rounded-full font-medium shrink-0"></span>
              </div>
              <p class="text-xs text-slate-500 leading-relaxed">เลือกว่าวันที่ทำ PM ในใบงานจะใช้วันที่บันทึก หรือให้ผู้บันทึกเลือกวันที่เอง</p>

              <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mt-4">
                <button type="button" class="mode-opt" data-mode="today">
                  <span class="dot"></span>
                  <span>
                    <span class="block text-sm font-medium text-slate-800">ใช้วันที่ปัจจุบัน</span>
                    <span class="block text-xs text-slate-500 mt-0.5">วันที่ทำ PM คือวันที่กดบันทึกผล (แบบเดิม)</span>
                  </span>
                </button>
                <button type="button" class="mode-opt" data-mode="pick">
                  <span class="dot"></span>
                  <span>
                    <span class="block text-sm font-medium text-slate-800">เลือกวันที่เอง</span>
                    <span class="block text-xs text-slate-500 mt-0.5 leading-relaxed">มีช่องเลือกวันที่ตอนบันทึกผล เลือกได้เฉพาะ<b class="text-slate-700 font-medium">ในช่วงสัญญา/แผนของหน่วยงาน</b> และไม่เกินวันนี้</span>
                  </span>
                </button>
              </div>

              <div class="mt-3 text-xs text-slate-500 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2.5 leading-relaxed flex gap-2">
                <i data-lucide="info" class="w-4 h-4 shrink-0 text-slate-400 mt-px"></i>
                <div>
                  <b class="text-slate-600 font-medium">ตัวอย่าง</b> สัญญา 1 มิ.ย. 69 – 31 พ.ค. 70 วันนี้ 28 ต.ค. 69 → ใบงานทุกรอบเลือกได้ <b class="text-slate-700 font-medium">1 มิ.ย. 69 – 28 ต.ค. 69</b><br>
                  เลือกล่วงหน้าเกินวันนี้ไม่ได้ และไม่เกินวันสิ้นสุดสัญญา
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script>
    // เมนูด้านซ้าย / แคปซูลบนมือถือ (โครงเดียวกับ settings_job_other.php — เพิ่มหมวดได้ในอนาคต)
    const menuConfig = [
      { id: 'pm-record', navIcon: 'clipboard-check', navLabel: 'การบันทึกผล PM' }
    ];

    let state = { sign_required: 1, date_mode: 'today' };
    let canEdit = false;
    const $ = (id) => document.getElementById(id);

    function initNav() {
      menuConfig.forEach((menu, index) => {
        const isActive = index === 0;
        const d = document.createElement('button');
        d.className = `w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors desktop-menu-btn ${isActive ? 'bg-softBlue text-primary' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'}`;
        d.innerHTML = `<i data-lucide="${menu.navIcon}" class="w-4 h-4 shrink-0"></i><span class="truncate">${menu.navLabel}</span>`;
        $('desktop-nav').appendChild(d);

        const m = document.createElement('button');
        m.className = `whitespace-nowrap flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-full transition-colors mobile-menu-btn ${isActive ? 'bg-primary text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'}`;
        m.innerHTML = `<i data-lucide="${menu.navIcon}" class="w-4 h-4 shrink-0"></i><span>${menu.navLabel}</span>`;
        $('mobile-nav').appendChild(m);
      });
    }

    function badge(el, on, onText, offText) {
      el.className = 'text-[10px] px-2 py-0.5 rounded-full font-medium shrink-0 ' + (on ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-slate-100 text-slate-500 border border-slate-200');
      el.innerText = on ? onText : offText;
    }

    function render() {
      const sign = !!+state.sign_required;
      $('toggle-sign').classList.toggle('on', sign);
      badge($('badge-sign'), sign, 'เปิดใช้งาน', 'ปิดใช้งาน');
      document.querySelectorAll('.mode-opt').forEach(b => b.classList.toggle('on', b.dataset.mode === state.date_mode));
      badge($('badge-date'), state.date_mode === 'pick', 'เลือกวันที่เอง', 'วันที่ปัจจุบัน');
    }

    async function loadSettings() {
      try {
        const r = await (await fetch('handle_pm_record_setting.php?action=get', { cache: 'no-store' })).json();
        if (!r.success) throw new Error(r.error || 'โหลดข้อมูลไม่สำเร็จ');
        state = r.setting;
        canEdit = !!r.can_edit;
        $('toggle-sign').disabled = !canEdit;
        $('btn-save').disabled = !canEdit;
        document.querySelectorAll('.mode-opt').forEach(b => b.disabled = !canEdit);
        $('no-perm').classList.toggle('hidden', canEdit);
        render();
      } catch (e) {
        Swal.fire('เกิดข้อผิดพลาด', e.message, 'error');
      }
    }

    async function saveSettings() {
      if (!canEdit) return;
      try {
        Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const fd = new FormData();
        fd.append('sign_required', +state.sign_required ? '1' : '0');
        fd.append('date_mode', state.date_mode);
        const r = await (await fetch('handle_pm_record_setting.php?action=save', { method: 'POST', body: fd })).json();
        Swal.close();
        if (!r.success) { Swal.fire('เกิดข้อผิดพลาด', r.error || 'บันทึกไม่สำเร็จ', 'error'); return; }
        state = r.setting; render();
        Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', text: 'อัปเดตการตั้งค่าเรียบร้อยแล้ว', timer: 1500, showConfirmButton: false, customClass: { popup: 'rounded-2xl' } });
      } catch (e) {
        Swal.close();
        Swal.fire('เชื่อมต่อ API ไม่ได้', e.message, 'error');
      }
    }

    $('toggle-sign').addEventListener('click', () => { state.sign_required = +state.sign_required ? 0 : 1; render(); });
    document.querySelectorAll('.mode-opt').forEach(b => b.addEventListener('click', () => { state.date_mode = b.dataset.mode; render(); }));
    $('btn-save').addEventListener('click', saveSettings);

    initNav();
    if (window.lucide) lucide.createIcons();
    loadSettings();
  </script>
</body>
</html>
