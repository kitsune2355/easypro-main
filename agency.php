<?php
@session_start();
$amIsSuper = (($_SESSION['sess_user_level_es'] ?? '') === 'super_admin');   // แท็บ Monitor เฉพาะ Super Admin (API ตรวจซ้ำ)
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EasyPro - Management Portal</title>

  <!-- 1. Assets & Libraries -->
  <link rel="icon" type="image/png" href="../es/logo - easypro2.png">
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@0.400.0/dist/umd/lucide.min.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
          integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <!-- 2. Tailwind Configuration -->
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { prompt: ['Prompt', 'sans-serif'] },
          colors: {
            'easy-primary': '#005e8e',
            'easy-secondary': '#003d5c',
            'easy-accent': '#0284c7',
            'easy-bg': '#f8fafc',
          }
        }
      }
    }
  </script>

  <!-- 3. Custom Styles -->
  <style>
    html { -webkit-tap-highlight-color: transparent; }
    body { font-family: 'Prompt', sans-serif; font-size: 13px; }

    .bg-overlay {
      background: linear-gradient(rgba(0, 50, 75, 0.75), rgba(0, 25, 40, 0.85)),
      url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&q=80&w=2000');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
    }

    .app-card {
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0,0,0,0.05);
      border: 1px solid #f1f5f9;
    }

    .input-clean {
      width: 100%;
      padding: 0.5rem 0.75rem;
      font-size: 0.75rem;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
      background-color: #f8fafc;
      color: #334155;
      transition: all 0.2s ease;
      outline: none;
    }
    .input-clean:hover { border-color: #cbd5e1; background-color: #ffffff; }
    .input-clean:focus { border-color: #0284c7; background-color: #ffffff; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); }
    .input-clean::placeholder { color: #94a3b8; }
    .input-clean:disabled, .input-clean[readonly] { background-color: #f1f5f9; color: #64748b; cursor: not-allowed; }

    /* ===== Monitor หน่วยงาน (รายการแถวกะทัดรัด) ===== */
    .am-seg { display: inline-flex; background: #f1f5f9; border-radius: 9px; padding: 3px; gap: 2px; }
    .am-seg button { height: 28px; padding: 0 12px; border-radius: 7px; font-size: 12px; font-weight: 600; color: #64748b; }
    .am-seg button.on { background: #fff; color: #005e8e; box-shadow: 0 1px 2px rgba(15, 23, 42, .1); }
    .am-filter { display: inline-flex; align-items: center; gap: 6px; height: 28px; padding: 0 10px; border-radius: 999px; font-size: 12px; font-weight: 600; color: #475569; }
    .am-filter:hover { background: #f1f5f9; }
    .am-filter.on { background: #0f172a; color: #fff; }
    .am-filter b { font-weight: 700; }
    .am-dot { width: 8px; height: 8px; border-radius: 999px; display: inline-block; flex: none; }
    /* ตาราง: หน่วยงาน | สถานะ | ล่าสุด | แนวโน้ม | ตัวชี้วัด 6 คอลัมน์ | › */
    .am-grid { display: grid; grid-template-columns: minmax(220px, 1fr) 118px 96px 104px repeat(6, 78px) 20px; align-items: center; column-gap: 8px; }
    .am-head { position: sticky; top: 0; z-index: 2; background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 9px 18px; font-size: 11px; font-weight: 600; color: #64748b; }
    .am-head .h { display: flex; align-items: center; gap: 4px; cursor: pointer; user-select: none; white-space: nowrap; }
    .am-head .h.num { justify-content: center; }
    .am-head .h:hover { color: #0f172a; }
    .am-head .h.on { color: #005e8e; }
    .am-head .h .ar { font-size: 9px; opacity: .8; }
    .am-r { padding: 0 18px; min-height: 58px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background .12s; }
    .am-r:hover { background: #f8fafc; }
    .am-r:last-child { border-bottom: 0; }
    .am-name { font-size: 13.5px; font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .am-meta { font-size: 11px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px; }
    .am-state { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 500; color: #475569; white-space: nowrap; }
    .am-ago { font-size: 12px; color: #475569; white-space: nowrap; }
    .am-ago.old { color: #94a3b8; }
    .am-n { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 40px; border-radius: 8px; font-variant-numeric: tabular-nums; }
    .am-n b { font-size: 14px; font-weight: 600; color: #1e293b; line-height: 1.2; }
    .am-n .x { font-size: 10px; color: #94a3b8; line-height: 1.2; white-space: nowrap; }
    .am-n .x.red { color: #dc2626; font-weight: 600; }
    .am-n.link:hover { background: #e0f2fe; }
    .am-n.link:hover b { color: #005e8e; }
    .am-n.zero b { color: #cbd5e1; font-weight: 500; }
    @media (max-width: 1180px) { .am-grid { grid-template-columns: minmax(200px, 1fr) 110px 88px repeat(6, 70px) 16px; } .am-col-spark { display: none !important; } }
    /* มือถือ / จอแคบ: การ์ด 2 ส่วน (ชื่อ+สถานะ / ตัวชี้วัด 3×2) */
    @media (max-width: 860px) {
      .am-head { display: none; }
      .am-grid.am-r { grid-template-columns: minmax(0, 1fr) auto; row-gap: 10px; padding: 12px 16px; }
      .am-r .am-col-ago, .am-r .am-col-arrow { display: none; }
      .am-r .am-col-metrics { grid-column: 1 / -1; display: grid !important; grid-template-columns: repeat(3, 1fr); gap: 6px; }
      .am-r .am-col-metrics .am-n { background: #f8fafc; height: 46px; }
      .am-r .am-col-metrics .am-n .lbl { display: block !important; }
    }
    .am-col-metrics { display: contents; }

    .am-tab { height: 34px; padding: 0 12px; border-radius: 8px; font-size: 12.5px; font-weight: 600; color: #64748b; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; }
    .am-tab:hover { background: #f1f5f9; color: #0f172a; }
    .am-tab.on { background: #005e8e; color: #fff; }
    .am-tab .n { font-size: 11px; padding: 0 6px; border-radius: 999px; background: rgba(15, 23, 42, .07); }
    .am-tab.on .n { background: rgba(255, 255, 255, .2); }
    .am-list td { padding: 9px 10px; border-bottom: 1px solid #f1f5f9; font-size: 12.5px; vertical-align: top; }
    .am-list th { padding: 8px 10px; font-size: 11px; font-weight: 700; color: #64748b; text-align: left; background: #f8fafc; position: sticky; top: 0; z-index: 1; }
    /* หน้ารายละเอียด: หัวข้อส่วน / ความเคลื่อนไหวล่าสุด / ประวัติล็อกอิน / scrollbar บาง */
    .am-sec { font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 8px; flex: none; }
    .am-day { position: sticky; top: 0; z-index: 1; background: #fff; padding: 6px 0 4px; margin-top: 4px; font-size: 11px; font-weight: 700; color: #64748b; border-bottom: 1px solid #f1f5f9; }
    .am-fi { width: 24px; height: 24px; border-radius: 7px; background: #f1f5f9; color: #64748b; display: flex; align-items: center; justify-content: center; flex: none; }
    /* แผงประวัติเข้าสู่ระบบ: หน้าต่างแยกทางขวาของหน้าต่างรายละเอียด (ปฏิทินรายเดือน มีเวลาในช่องวัน) */
    .am-user.on { background: #f0f9ff; }
    .am-user.on .am-caret { color: #0284c7; }
    #am-up { position: fixed; z-index: 2; background: #fff; border-radius: 16px; box-shadow: 0 12px 40px rgba(15, 23, 42, .2); display: flex; flex-direction: column; overflow: hidden; text-align: left; opacity: 0; transform: translateX(-8px); transition: opacity .18s, transform .18s; }
    #am-up.show { opacity: 1; transform: none; }
<<<<<<< HEAD
    /* ปุ่มปิดแผง: แบบเดียวกับปุ่มปิดของ SweetAlert2 */
    .am-up-x { position: absolute; top: 0; right: 0; z-index: 2; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; padding: 0; border: none; border-radius: 5px; background: transparent; color: #ccc; font-family: monospace; font-size: 40px; line-height: 1; cursor: pointer; transition: color .1s; }
    .am-up-x:hover { color: #f27474; }
=======
>>>>>>> 50333a3abac16661c1be685b2248b4ad0342428c
    .am-upc { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px; }
    .am-upc .w { font-size: 10.5px; color: #94a3b8; text-align: center; padding-bottom: 2px; }
    .am-ud { min-height: 62px; border-radius: 8px; padding: 4px 6px; border: 1px solid #f1f5f9; font-variant-numeric: tabular-nums; text-align: left; display: flex; flex-direction: column; }
    .am-ud .hd { display: flex; align-items: center; justify-content: space-between; gap: 2px; }
    .am-ud .n { font-size: 11px; font-weight: 600; color: #cbd5e1; line-height: 18px; }
    .am-ud .c { font-size: 10px; font-weight: 700; color: #0369a1; background: #e0f2fe; border-radius: 999px; min-width: 18px; height: 16px; padding: 0 4px; display: inline-flex; align-items: center; justify-content: center; }
    .am-ud .f { margin-top: 5px; font-size: 12px; font-weight: 700; color: #0f172a; line-height: 1.2; }
    .am-ud .l { font-size: 10px; color: #94a3b8; line-height: 1.3; white-space: nowrap; }
    .am-tc { display: inline-flex; align-items: center; gap: 3px; padding: 3px 8px; border-radius: 6px; background: #fff; border: 1px solid #e2e8f0; font-size: 12px; font-weight: 600; color: #0f172a; font-variant-numeric: tabular-nums; }
    .am-tc i { font-style: normal; font-size: 10.5px; font-weight: 600; color: #0369a1; }
    .am-ud.has { border-color: #e0f2fe; background: #f8fcff; cursor: pointer; }
    .am-ud.has .n { color: #0369a1; }
    .am-ud.has:hover { border-color: #7dd3fc; }
    .am-ud.sel { border-color: #0284c7; box-shadow: inset 0 0 0 1px #0284c7; }
    .am-ud.out { border-color: transparent; }
    .am-ud.out .n { color: #e2e8f0; }
    .am-ud.today .n { color: #fff; background: #334155; border-radius: 999px; padding: 0 5px; }
    .am-scroll { scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
    .am-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
    .am-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
    .am-scroll::-webkit-scrollbar-track { background: transparent; }
    .am-st { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; white-space: nowrap; }

    .admin-row-card { transition: all 0.2s ease; }
    .admin-row-card:hover { border-color: #bae6fd; background-color: #f0f9ff; }

    .tab-btn { transition: all 0.2s ease; position: relative; }
    .tab-btn.active { background: #005e8e; color: white; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); border-color: transparent; }
    .tab-btn:not(.active) { color: #cbd5e1; background: transparent; border: 1px solid rgba(255,255,255,0.1); }
    .tab-btn:not(.active):hover { color: white; background: rgba(255, 255, 255, 0.05); }

    .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    .leaflet-container { z-index: 10; font-family: 'Prompt', sans-serif; }
    .swal2-popup { font-family: 'Prompt', sans-serif !important; }
  </style>
</head>

<body class="bg-overlay p-4 md:p-6 text-slate-700 min-h-screen flex flex-col lg:h-screen lg:overflow-hidden relative">

<!-- ========================================== -->
<!-- Modal: Map Picker -->
<!-- ========================================== -->
<div id="map-modal" class="fixed inset-0 z-[60] bg-slate-900/60 backdrop-blur-sm hidden flex-col items-center justify-center p-4 opacity-0 transition-all duration-300">
  <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl flex flex-col overflow-hidden transform scale-95 transition-all duration-300" id="map-modal-content">

    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50 shrink-0">
      <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
        <i data-lucide="map" class="w-4 h-4 text-easy-primary"></i> เลือกพิกัดหน่วยงาน
      </h3>
      <div class="flex items-center gap-3">
        <button onClick="toggleMapFullscreen()" class="text-slate-400 hover:text-easy-primary transition-colors" title="เต็มจอ">
          <i data-lucide="maximize" id="fullscreen-icon" class="w-4 h-4"></i>
        </button>
        <button onClick="closeMapModal()" class="text-slate-400 hover:text-red-500 transition-colors" title="ปิดหน้าต่าง">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
    </div>

    <div class="p-2 bg-slate-100 text-[11px] text-slate-600 flex items-center gap-2 border-b border-slate-200 shrink-0">
      <i data-lucide="info" class="w-3.5 h-3.5 text-blue-500"></i>
      สามารถคลิกบนแผนที่เพื่อปักหมุดตำแหน่งที่ต้องการ ระบบจะดึงที่อยู่ให้อัตโนมัติ
    </div>

    <div class="p-3 bg-white border-b border-slate-200 flex gap-2 items-center shrink-0">
      <div class="relative flex-1">
        <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input type="text" id="map-search-input" onKeyDown="if(event.key === 'Enter') searchLocationOnMap()"
               placeholder="พิมพ์ชื่อสถานที่, ที่อยู่ หรือ พิกัด (Lat, Lng) แล้วกด Enter..." class="input-clean pl-9">
      </div>
      <button type="button" id="btn-map-search" onClick="searchLocationOnMap()"
              class="bg-slate-800 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-slate-700 transition-colors flex items-center gap-1.5 shrink-0 h-[34px]">
        <i data-lucide="search" class="w-3.5 h-3.5"></i> ค้นหา
      </button>
    </div>

    <div id="map-view" class="w-full h-[350px] sm:h-[400px] bg-slate-200 transition-all duration-300"></div>

    <div class="p-4 bg-white flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center shrink-0">
      <div class="flex-1 w-full text-xs">
        <div class="font-bold text-slate-700 mb-1">ที่อยู่ที่เลือก:</div>
        <div id="map-address-preview" class="text-slate-500 min-h-[1.5rem] line-clamp-2">ยังไม่ได้เลือกตำแหน่ง...</div>
      </div>
      <div class="flex gap-2 w-full sm:w-auto shrink-0">
        <button onClick="closeMapModal()"
                class="flex-1 sm:flex-none px-4 py-2 border border-slate-200 text-slate-600 rounded-lg text-xs font-bold hover:bg-slate-50 transition-colors">
          ยกเลิก
        </button>
        <button onClick="confirmMapLocation()"
                class="flex-1 sm:flex-none px-4 py-2 bg-easy-primary text-white rounded-lg text-xs font-bold hover:bg-easy-secondary transition-colors flex items-center justify-center gap-2">
          <i data-lucide="check" class="w-3.5 h-3.5"></i> ยืนยันพิกัด
        </button>
      </div>
    </div>

  </div>
</div>

<!-- ========================================== -->
<!-- Top Header & Global Navigation -->
<!-- ========================================== -->
<div class="flex flex-col gap-4 mb-4 shrink-0">
  <div class="flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center text-easy-primary shadow-sm shrink-0">
        <i data-lucide="layout-grid" class="w-5 h-5"></i>
      </div>
      <div>
        <h1 class="text-base sm:text-lg font-bold text-white tracking-tight leading-none drop-shadow-sm">ตั้งค่าหน่วยงานใหม่</h1>
        <p class="text-[10px] text-slate-300 font-medium mt-1 uppercase tracking-wider">EasyPro Portal</p>
      </div>
    </div>
    <a href="main.php" class="flex items-center gap-2 bg-slate-900/40 hover:bg-slate-800/80 backdrop-blur-md px-3 sm:px-4 py-2 rounded-lg border border-white/10 shadow-sm transition-all shrink-0 group">
      <i data-lucide="arrow-left" class="w-4 h-4 text-slate-300 group-hover:-translate-x-1 group-hover:text-white transition-all"></i>
      <span class="text-xs font-bold text-white">กลับหน้าหลัก</span>
    </a>
  </div>

  <div class="flex gap-2 w-full max-w-xl">
    <button onClick="switchTab('agency')" id="tab-agency-btn" class="tab-btn active flex-1 py-2 px-2 sm:px-4 rounded-lg text-xs font-bold flex items-center justify-center gap-2">
      <i data-lucide="building-2" class="w-3.5 h-3.5"></i> จัดการหน่วยงาน
    </button>
    <button onClick="switchTab('users')" id="tab-users-btn" class="tab-btn flex-1 py-2 px-2 sm:px-4 rounded-lg text-xs font-bold flex items-center justify-center gap-2">
      <i data-lucide="users" class="w-3.5 h-3.5"></i> ข้อมูลผู้ใช้
    </button>
    <?php if ($amIsSuper) { ?>
    <button onClick="switchTab('monitor')" id="tab-monitor-btn" class="tab-btn flex-1 py-2 px-2 sm:px-4 rounded-lg text-xs font-bold flex items-center justify-center gap-2">
      <i data-lucide="activity" class="w-3.5 h-3.5"></i> Monitor
    </button>
    <?php } ?>
  </div>
</div>

<!-- ========================================== -->
<!-- Main Content Container -->
<!-- ========================================== -->
<div class="flex-1 flex flex-col relative w-full lg:min-h-0">

  <!-- Tab 1: Agency Management -->
  <div id="view-agency" class="flex flex-col lg:flex-row gap-4 flex-1 lg:min-h-0 w-full transition-opacity duration-300">

    <!-- Left: Agency Table -->
    <div class="w-full lg:w-8/12 xl:w-9/12 flex flex-col h-[500px] lg:h-full">
      <div class="app-card overflow-hidden flex flex-col h-full border border-slate-200">

        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white">
          <div class="flex items-center gap-2 shrink-0">
            <h2 class="text-sm font-bold text-slate-800">รายการหน่วยงาน</h2>
            <span class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded text-[10px] font-bold border border-slate-200" id="stat-agencies">0</span>
          </div>
          <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
              <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
              <input type="text" id="agency-search" oninput="renderAgencyTable()" placeholder="ค้นหารหัส, format, ชื่อ, ที่อยู่..." class="input-clean pl-9">
            </div>
            <button onClick="resetForm(); document.getElementById('agency-code').focus()"
                    class="bg-slate-800 text-white px-4 py-2 sm:py-1.5 rounded-lg text-[11px] font-bold hover:bg-slate-700 transition-colors flex items-center justify-center gap-2 shrink-0">
              <i data-lucide="plus-square" class="w-3.5 h-3.5"></i> <span>เพิ่มหน่วยงาน</span>
            </button>
          </div>
        </div>

        <div class="flex-1 overflow-auto custom-scrollbar bg-white">
          <table class="w-full text-left border-collapse min-w-[820px]">
            <thead class="bg-slate-50 sticky top-0 z-20 border-b border-slate-200">
             <tr class="text-slate-500 text-[10px] uppercase tracking-wider font-bold">
			  <th class="px-5 py-3 w-28">รหัส</th>
			  <th class="px-5 py-3">ชื่อหน่วยงาน & ที่ตั้ง</th>
			  <th class="px-5 py-3">สิทธิ์การใช้งานระบบ</th>
			  <th class="px-5 py-3 text-center">ผู้ดูแล</th>
			  <th class="px-5 py-3">รายชื่อผู้ดูแล</th>
			  <th class="px-5 py-3 text-center">จัดการ</th>
			</tr>
            </thead>
            <tbody id="agency-table-body" class="divide-y divide-slate-100 text-xs text-slate-600"></tbody>
          </table>
        </div>

      </div>
    </div>

    <!-- Right: Agency Form -->
    <div class="w-full lg:w-4/12 xl:w-3/12 flex flex-col shrink-0">
      <div class="app-card p-5 flex flex-col lg:h-full border-t-4 border-easy-primary">

        <div class="mb-4 pb-3 border-b border-slate-100 shrink-0">
          <h2 id="form-title" class="text-sm font-bold text-slate-800 flex items-center gap-2">
            <i data-lucide="plus-square" class="w-4 h-4 text-easy-primary"></i> <span>เพิ่มหน่วยงานใหม่</span>
          </h2>
        </div>

        <form id="agencyForm" class="flex flex-col flex-1 lg:min-h-0" onSubmit="handleFormSubmit(event)">
          <input type="hidden" id="edit-id" value="">

          <div class="custom-scrollbar overflow-y-auto pr-2 -mr-2 space-y-5 flex-1 pb-4">

            <div>
              <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">ข้อมูลหน่วยงาน</label>
              <div class="space-y-3">

                <!-- ag_job = รหัสหน่วยงาน -->
                <div>
                  <label class="block text-[11px] font-medium text-slate-600 mb-1">รหัสหน่วยงาน <span class="text-red-500">*</span></label>
                  <input type="text" id="agency-code" required class="input-clean" placeholder="เช่น AG-001">
                </div>

				<div class="grid grid-cols-2 gap-2">
				  <div>
					<label class="block text-[11px] font-medium text-slate-600 mb-1">วันที่เริ่มต้นหน่วยงาน</label>
					<input type="date" id="agency-start-date" class="input-clean">
				  </div>
				  <div>
					<label class="block text-[11px] font-medium text-slate-600 mb-1">วันที่สิ้นสุดหน่วยงาน</label>
					<input type="date" id="agency-end-date" class="input-clean">
				  </div>
				</div>
                <!-- ag_contract = ชื่อหน่วยงาน -->
                <div>
                  <label class="block text-[11px] font-medium text-slate-600 mb-1">ชื่อหน่วยงาน <span class="text-red-500">*</span></label>
                  <input type="text" id="agency-name" required placeholder="ระบุชื่อฝ่าย/แผนก" class="input-clean">
                </div>

                <!-- ag_format = format เลขที่เอกสารแจ้งซ่อม -->
                <div>
                  <label class="block text-[11px] font-medium text-slate-600 mb-1">เลขที่ Format เลขที่เอกสารแจ้งซ่อม</label>
                  <input type="text" id="agency-doc-format" class="input-clean" placeholder="เช่น EP-RP-{YYYY}-{####}">
                </div>
                <!-- ag_status = เปิด/ปิด -->
                <div>
                  <label class="block text-[11px] font-medium text-slate-600 mb-1">สถานะหน่วยงาน</label>
                  <select id="agency-status" class="input-clean cursor-pointer">
                    <option value="1">เปิดใช้งาน</option>
                    <option value="0">ปิดใช้งาน</option>
                  </select>
                </div>

              </div>
            </div>

            <div class="pt-3 border-t border-slate-50">
              <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">ข้อมูลที่ตั้ง (พิกัด)</label>
              <div class="space-y-3">
                <div>
                  <label class="block text-[11px] font-medium text-slate-600 mb-1">ที่อยู่ (ดึงอัตโนมัติจากแผนที่)</label>
                  <textarea id="agency-address" rows="2" class="input-clean resize-none" placeholder="รายละเอียดที่อยู่..."></textarea>
                </div>

                <div class="flex items-end">
                  <button type="button" onClick="openMapModal()"
                          class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold py-2 px-2 rounded-md border border-slate-200 transition-colors flex items-center justify-center gap-1.5 h-[34px]">
                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-red-500"></i> เปิดแผนที่
                  </button>
                </div>

                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">ละติจูด (Lat)</label>
                    <input type="text" id="agency-lat" class="input-clean text-[10px] bg-slate-50" readonly placeholder="--">
                  </div>
                  <div>
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">ลองติจูด (Lng)</label>
                    <input type="text" id="agency-lng" class="input-clean text-[10px] bg-slate-50" readonly placeholder="--">
                  </div>
                </div>
              </div>
            </div>

            <!-- Employers (ค้นหาได้ + select) -->
            <div class="pt-3 border-t border-slate-50">
              <div class="flex items-center justify-between mb-2">
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">ผู้ดูแลระบบ</label>
                <button type="button" onClick="addAdminRow('')" class="text-easy-primary text-[10px] font-bold hover:underline flex items-center gap-1">
                  <i data-lucide="plus" class="w-3 h-3"></i> เพิ่มผู้ดูแล
                </button>
              </div>
              <div id="admin-inputs-container" class="space-y-2"></div>
            </div>
			<style>
			.toggle-switch {
  appearance:none;
  width:36px;
  height:20px;
  background:#e2e8f0;
  border-radius:999px;
  position:relative;
  cursor:pointer;
  transition:all .2s;
}

.toggle-switch:checked{
  background:#0284c7;
}

.toggle-switch::before{
  content:"";
  position:absolute;
  width:16px;
  height:16px;
  background:white;
  border-radius:50%;
  top:2px;
  left:2px;
  transition:all .2s;
}

.toggle-switch:checked::before{
  left:18px;
}
			</style>
			<!-- ⭐ สิทธิ์การใช้งาน -->
				<div class="pt-4 border-t border-slate-100">
				
				<div class="flex items-center justify-between mb-3">
				
				<label class="text-[11px] font-bold text-slate-500">
				สิทธิ์การใช้งานระบบ
				</label>
				
				<label class="flex items-center gap-2 text-xs cursor-pointer">
				
				<input type="checkbox"
				id="perm-select-all"
				class="w-4 h-4">
				
				<span class="font-semibold text-slate-600">
				เลือกทั้งหมด
				</span>
				
				</label>
				
				</div>
				
				<div id="permission-container"
				class="space-y-3"></div>
				
				</div>          </div>

          <div class="pt-4 mt-2 shrink-0">
            <button type="submit" id="submit-btn"
                    class="w-full bg-easy-primary hover:bg-easy-secondary text-white text-xs font-bold py-2.5 rounded-lg transition-colors shadow-sm flex items-center justify-center gap-2">
              <i data-lucide="save" class="w-4 h-4"></i> บันทึกข้อมูล
            </button>
          </div>

        </form>

      </div>
    </div>
  </div>

  <!-- Tab 2: User Management -->
  <div id="view-users" class="hidden flex-col lg:flex-row gap-4 flex-1 lg:min-h-0 w-full opacity-0 transition-opacity duration-300">

    <div class="w-full lg:w-8/12 xl:w-9/12 flex flex-col h-[500px] lg:h-full">
      <div class="app-card overflow-hidden flex flex-col h-full border border-slate-200">

        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white">
          <div class="flex items-center gap-2 shrink-0">
            <h2 class="text-sm font-bold text-slate-800">รายชื่อผู้ใช้งานระบบ</h2>
            <span class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded text-[10px] font-bold border border-slate-200" id="stat-users">0</span>
          </div>
          <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
              <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
              <input type="text" id="user-search" oninput="renderUserTable()" placeholder="ค้นหาชื่อ, Username..." class="input-clean pl-9">
            </div>
            <button onClick="resetUserForm(); document.getElementById('user-username').focus()"
                    class="bg-slate-800 text-white px-4 py-2 sm:py-1.5 rounded-lg text-[11px] font-bold hover:bg-slate-700 transition-colors flex items-center justify-center gap-2 shrink-0">
              <i data-lucide="user-plus" class="w-3.5 h-3.5"></i> <span>เพิ่มผู้ใช้</span>
            </button>
          </div>
        </div>

        <div class="flex-1 overflow-auto custom-scrollbar bg-white">
          <table class="w-full text-left border-collapse min-w-[1020px]">
            <thead class="bg-slate-50 sticky top-0 z-20 border-b border-slate-200">
              <tr class="text-slate-500 text-[10px] uppercase tracking-wider font-bold">
			  <th class="px-5 py-3">ผู้ใช้งาน (Name)</th>
			  <th class="px-5 py-3">Username</th>
			  <th class="px-5 py-3">email</th>
			  <th class="px-5 py-3">Role</th>
			  <th class="px-5 py-3">หน่วยงานที่ดูแล</th>
			  <th class="px-5 py-3">Status</th>
			  <th class="px-5 py-3 text-center w-20">จัดการ</th>
			</tr>
            </thead>
            <tbody id="user-table-body" class="divide-y divide-slate-100 text-xs text-slate-600"></tbody>
          </table>
        </div>

      </div>
    </div>

    <div class="w-full lg:w-4/12 xl:w-3/12 flex flex-col shrink-0">
      <div class="app-card p-5 flex flex-col lg:h-full border-t-4 border-slate-800">
        <div class="mb-4 pb-3 border-b border-slate-100 shrink-0">
          <h2 id="user-form-title" class="text-sm font-bold text-slate-800 flex items-center gap-2">
            <i data-lucide="user-plus" class="w-4 h-4 text-slate-800"></i> <span>เพิ่มผู้ใช้งานใหม่</span>
          </h2>
        </div>

        <form id="userForm" class="flex flex-col flex-1 lg:min-h-0" onSubmit="handleUserSubmit(event)">
          <input type="hidden" id="edit-user-id" value="">
          <div class="custom-scrollbar overflow-y-auto pr-2 -mr-2 space-y-5 flex-1 pb-4">

            <div>
              <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">บัญชีระบบ</label>
              <div class="space-y-3">
                <div>
                  <label class="block text-[11px] font-medium text-slate-600 mb-1">Username <span class="text-red-500">*</span></label>
                  <input type="text" id="user-username" required class="input-clean" autocomplete="off">
                </div>
                <div>
                  <label class="block text-[11px] font-medium text-slate-600 mb-1">Password <span class="text-red-500">*</span></label>
                  <div class="relative">
                    <input type="password" id="user-password" required class="input-clean pr-8" autocomplete="new-password">
                    <button type="button" onClick="togglePasswordVisibility('user-password')" class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none">
                      <i data-lucide="eye" id="user-password-icon" class="w-3.5 h-3.5"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">ข้อมูลบุคคล</label>
              <div class="space-y-3">
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">ชื่อจริง <span class="text-red-500">*</span></label>
                    <input type="text" id="user-fname" required class="input-clean">
                  </div>
                  <div>
                    <label class="block text-[11px] font-medium text-slate-600 mb-1">นามสกุล <span class="text-red-500">*</span></label>
                    <input type="text" id="user-lname" required class="input-clean">
                  </div>
                </div>

                <div>
                  <label class="block text-[11px] font-medium text-slate-600 mb-1">อีเมล</label>
                  <input type="email" id="user-email" class="input-clean" placeholder="example@company.com" autocomplete="off">
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">สิทธิ์การใช้งาน</label>
                <select id="user-role" required class="input-clean cursor-pointer" onChange="toggleUserDepartment()">
                  <option value="super_admin">Super_Admin</option>
                  <option value="employer">Employer(ผู้ว่าจ้าง)</option>
                </select>
              </div>
              <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">สถานะ</label>
                <select id="user-status" required class="input-clean cursor-pointer">
                  <option value="1">Active (ใช้งาน)</option>
                  <option value="0">Inactive (ระงับ)</option>
                </select>
              </div>
            </div>

			<div id="user-department-wrap" class="hidden">
			  <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">ประเภท Employer</label>
			  <select id="user-department" class="input-clean cursor-pointer">
				<option value="">-- กรุณาเลือกประเภท --</option>
				<option value="1">1 = ผู้ว่าจ้าง</option>
				<option value="3">3 = เจ้าหน้าที่ธุรการ</option>
			  </select>
			  <p class="text-[10px] text-slate-400 mt-1">
				เมื่อเลือกสิทธิ์เป็น Employer ต้องเลือกประเภทเพื่อบันทึกลง user_department
			  </p>
			</div>
          </div>

          <div class="pt-4 mt-2 shrink-0">
            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold py-2.5 rounded-lg transition-colors shadow-sm flex items-center justify-center gap-2">
              <i data-lucide="save" class="w-4 h-4"></i> บันทึกข้อมูล
            </button>
          </div>
        </form>

      </div>
    </div>

  </div>

  <?php if ($amIsSuper) { ?>
  <!-- Tab 3: Monitor ความเคลื่อนไหวของแต่ละหน่วยงาน (Super Admin) — handle_agency_monitor.php -->
  <div id="view-monitor" class="hidden flex-col gap-4 flex-1 lg:min-h-0 w-full opacity-0 transition-opacity duration-300">
    <div class="app-card border border-slate-200 flex flex-col flex-1 lg:min-h-0 overflow-hidden">
      <div class="px-5 py-3.5 border-b border-slate-100 flex flex-wrap items-center gap-3">
        <div class="mr-auto">
          <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2"><i data-lucide="activity" class="w-4 h-4 text-easy-primary"></i> Monitor หน่วยงาน</h2>
          <p class="text-[11px] text-slate-400 mt-0.5" id="am-updated">กำลังโหลด...</p>
        </div>
        <div class="relative w-full sm:w-auto">
          <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
          <input id="am-q" type="search" placeholder="ค้นหาหน่วยงาน" class="input-clean !pl-9 !py-1.5 w-full sm:w-52">
        </div>
        <div class="am-seg" id="am-days"><button type="button" data-days="7">7 วัน</button><button type="button" data-days="30" class="on">30 วัน</button><button type="button" data-days="90">90 วัน</button></div>
        <button type="button" id="am-export" class="h-8 px-3 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 text-xs font-bold flex items-center gap-1.5 hover:bg-emerald-100" title="ส่งออกสรุปทุกหน่วยงาน (ตามตัวกรอง)"><i data-lucide="file-spreadsheet" class="w-3.5 h-3.5"></i> Export Excel</button>
        <button type="button" id="am-reload" class="w-8 h-8 rounded-lg border border-slate-200 text-slate-500 flex items-center justify-center hover:bg-slate-50 hover:text-easy-primary" title="โหลดใหม่"><i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i></button>
      </div>
      <div class="px-4 py-2 border-b border-slate-100 flex flex-wrap items-center gap-1 bg-slate-50/70">
        <div id="am-filters" class="flex flex-wrap items-center gap-1"></div>
        <select id="am-sort" class="ml-auto text-[12px] font-semibold text-slate-600 bg-transparent border-0 focus:ring-0 cursor-pointer">
          <option value="last">เรียงตาม: เคลื่อนไหวล่าสุด</option>
          <option value="total">เรียงตาม: เคลื่อนไหวมากสุด</option>
          <option value="idle">เรียงตาม: เงียบนานสุด</option>
          <option value="name">เรียงตาม: ชื่อหน่วยงาน</option>
          <option value="login">เรียงตาม: เข้าใช้งานมากสุด</option>
          <option value="repair">เรียงตาม: แจ้งซ่อมมากสุด</option>
          <option value="pm">เรียงตาม: PM มากสุด</option>
        </select>
      </div>
      <div class="flex-1 overflow-auto lg:min-h-0" style="min-height: 320px">
        <div id="am-list"></div>
      </div>
    </div>
  </div>
  <?php } ?>
</div>

<!-- ========================================== -->
<!-- Application Scripts (ONE BLOCK ONLY) -->
<!-- ========================================== -->
<script>

function toggleUserDepartment() {
  const role = document.getElementById('user-role').value;
  const wrap = document.getElementById('user-department-wrap');
  const department = document.getElementById('user-department');

  if (role === 'employer') {
    wrap.classList.remove('hidden');
    department.setAttribute('required', 'required');
  } else {
    wrap.classList.add('hidden');
    department.value = '';
    department.removeAttribute('required');
  }
}

const SYSTEM_MENU = [

{
group:"Repair System",
menus:[
{key:"repair_request",name:"แจ้งซ่อม",icon:"wrench"},
{key:"repair_data",name:"ข้อมูลแจ้งซ่อม",icon:"clipboard-list"},
{key:"work_history",name:"ประวัติงานช่าง",icon:"hard-hat"}
]
},

{
group:"Asset System",
menus:[
{key:"meter_data",name:"ข้อมูลมิเตอร์",icon:"activity"},
{key:"machine_data",name:"ข้อมูลเครื่องจักร",icon:"cpu"}
]
},

{
group:"Stock System",
menus:[
{key:"stock_manage",name:"จัดการสต็อก",icon:"package"}
]
},

{
group:"PM System",
menus:[
{key:"pm_plan",name:"วางแผน PM",icon:"notebook-pen"}
]
}

];

function renderPermissions(data = []) {
  const safeData = Array.isArray(data) ? data : [];
  const container = document.getElementById("permission-container");

  container.innerHTML = SYSTEM_MENU.map(group => {
    const menus = group.menus.map(menu => {
      const perm = safeData.find(x => x.sm_key === menu.key);
      const checked = perm && perm.can_view == 1 ? "checked" : "";

      return `
        <div class="flex items-center justify-between p-3 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 transition">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center">
              <i data-lucide="${menu.icon}" class="w-4 h-4 text-slate-600"></i>
            </div>
            <div>
              <div class="text-xs font-semibold text-slate-700">${menu.name}</div>
            </div>
          </div>

          <input type="checkbox" class="toggle-switch perm-check" value="${menu.key}" ${checked}>
        </div>
      `;
    }).join("");

    return `
      <div class="border rounded-lg p-3 bg-slate-50">
        <div class="text-[11px] font-bold text-slate-500 mb-2">${group.group}</div>
        <div class="space-y-2">${menus}</div>
      </div>
    `;
  }).join("");

  lucide.createIcons();
}

document.addEventListener("change",(e)=>{

if(e.target.id==="perm-select-all"){

const checked = e.target.checked;

document
.querySelectorAll(".perm-check")
.forEach(el=>el.checked = checked);

}

});

 async function loadPermissions(ag_id){

const res = await fetch(
`api_agency.php?action=get_permissions&ag_id=${ag_id}`
);

const json = await res.json();

if(json.success){

renderPermissions(json.data);

}

}
 
  /** ==========================================
   * 0) API URL
   * ========================================== */
  const USER_API_URL   = "api_user.php";
  const AGENCY_API_URL = "api_agency.php";
  const USERS_TABLE_ONLY_SUPER_ADMIN = false; // ถ้าจะ filter เฉพาะ super_admin ในตาราง users ให้เปลี่ยนเป็น true

  /** ==========================================
   * 1) State
   * ========================================== */
  let agencies = [];
  let allUsers = [];
  let adminMasterList = [];

  // Leaflet
  let map = null;
  let mapMarker = null;
  let tempLoc = { lat: '', lng: '', address: '' };
  let isMapFullscreen = false;

  /** ==========================================
   * 2) Helpers
   * ========================================== */
  function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, (m) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[m]));
  }

  function stringToColor(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) hash = str.charCodeAt(i) + ((hash << 5) - hash);
    return `hsl(${Math.abs(hash) % 360}, 65%, 45%)`;
  }

  function refreshIcons() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
  }

  const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.onmouseenter = Swal.stopTimer;
      toast.onmouseleave = Swal.resumeTimer;
    }
  });

  function showToast(msg, type = 'success') {
    Toast.fire({ icon: type === 'error' ? 'error' : 'success', title: msg });
  }

  async function apiGet(url) {
    const res = await fetch(url);
    return res.json();
  }
  async function apiPost(url, data) {
    const res = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded; charset=utf-8" },
      body: new URLSearchParams(data).toString()
    });
    return res.json();
  }

  /** ==========================================
   * 3) Tab Switch
   * ========================================== */
  function switchTab(tabName) {
    ['agency', 'users', 'monitor'].forEach(function (name) {
      const view = document.getElementById('view-' + name);
      const btn = document.getElementById('tab-' + name + '-btn');
      if (!view) return;
      if (name === tabName) {
        view.classList.remove('hidden');
        view.classList.add('flex');
        setTimeout(() => view.classList.remove('opacity-0'), 20);
        if (btn) btn.classList.add('active');
      } else {
        view.classList.add('hidden', 'opacity-0');
        view.classList.remove('flex');
        if (btn) btn.classList.remove('active');
      }
    });
    if (tabName === 'monitor' && window.amOpen) window.amOpen();
  }

  /** ==========================================
   * 4) Load Data
   * ========================================== */
  async function fetchUsers() {
    const json = await apiGet(`${USER_API_URL}?action=list`);
    if (!json.success) throw new Error(json.message || "load users failed");

    let data = json.data || [];
    if (USERS_TABLE_ONLY_SUPER_ADMIN) data = data.filter(u => u.user_level === 'super_admin');

    allUsers = data.map(u => {
      const first = (u.user_name || "").trim();
      const last  = (u.user_fname || "").trim();
      const avatar = ((first.charAt(0) || "") + (last.charAt(0) || "")).toUpperCase() || "U";
      return {
		  id: u.id,
		  username: u.user_id,
		  fname: first,
		  lname: last,
		  email: u.user_email || '',
		  role: u.user_level,
		  department: u.user_department || '',
		  agency_names: Array.isArray(u.agency_names) ? u.agency_names : [],
		  status_login: u.user_status_login,
		  status: (u.user_status_login == 1 ? "Active" : "Inactive"),
		  avatar
		};
    });
  }
  
  function getPermissionLabel(key) {
	  const map = {
		repair_request: 'แจ้งซ่อม',
		repair_data: 'ข้อมูลแจ้งซ่อม',
		meter_data: 'ข้อมูลมิเตอร์',
		machine_data: 'ข้อมูลเครื่องจักร',
		stock_manage: 'จัดการสต็อก',
		pm_plan: 'วางแผน PM'
	  };
	
	  return map[key] || key;
	}

  async function fetchSuperAdmins() {
    const json = await apiGet(`${USER_API_URL}?action=list_super_admin`);
    if (!json.success) throw new Error(json.message || "load super_admin failed");

    adminMasterList = (json.data || []).map(u => ({
      user_id: u.user_id,
      name: `${(u.user_name || '').trim()} ${(u.user_fname || '').trim()}`.trim() || u.user_id,
      role: u.user_level
    }));
  }

  async function fetchAgencies() {
    const json = await apiGet(`${AGENCY_API_URL}?action=list`);
    if (!json.success) throw new Error(json.message || "load agencies failed");

    agencies = (json.data || []).map(a => ({
	  id: String(a.ag_id),
	  code: a.ag_job || '',
	  doc_format: a.ag_format || '',
	  name: a.ag_contract || '',
	  address: a.ag_located || '',
	  lat: a.ag_latitude || '',
	  lng: a.ag_longitude || '',
	  start_date: a.ag_start_date || '',
	  end_date: a.ag_end_date || '',
	  status: Number(a.ag_status ?? 0),
	  employers: a.employers || [],
	  permissions: a.permissions || []
	}));
  }

  window.addEventListener('DOMContentLoaded', async () => {
    try {
      await fetchUsers();
      await fetchSuperAdmins();
      await fetchAgencies();
 // ⭐ render permission เปล่าก่อน
    renderPermissions([]);
      renderUserTable();
      renderAgencyTable();
      addAdminRow('');
    toggleUserDepartment();

      setTimeout(refreshIcons, 100);
    } catch (err) {
      console.error(err);
      Swal.fire({ icon: 'error', title: 'โหลดข้อมูลไม่สำเร็จ', text: err.message || 'เกิดข้อผิดพลาด', confirmButtonColor: '#005e8e' });
    }
  });

  /** ==========================================
   * 5) Leaflet Map
   * ========================================== */
  function initLeafletMap() {
    if (map) return;
    map = L.map('map-view').setView([13.7563, 100.5018], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);

    map.on('click', function(e) {
      updateLocationData(e.latlng.lat, e.latlng.lng);
    });
  }

  async function updateLocationData(lat, lng) {
    lat = parseFloat(lat).toFixed(6);
    lng = parseFloat(lng).toFixed(6);

    if (mapMarker) mapMarker.setLatLng([lat, lng]);
    else mapMarker = L.marker([lat, lng]).addTo(map);

    map.setView([lat, lng], 15);
    tempLoc.lat = lat;
    tempLoc.lng = lng;
    document.getElementById('map-address-preview').innerText = "กำลังค้นหาข้อมูลที่อยู่...";

    try {
      const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=th`);
      const data = await res.json();
      if (data && data.display_name) {
        tempLoc.address = data.display_name;
        document.getElementById('map-address-preview').innerText = tempLoc.address;
      } else {
        tempLoc.address = '';
        document.getElementById('map-address-preview').innerText = `ละติจูด: ${lat}, ลองติจูด: ${lng} (ไม่พบรายละเอียดที่อยู่)`;
      }
    } catch (e) {
      document.getElementById('map-address-preview').innerText = `ละติจูด: ${lat}, ลองติจูด: ${lng}`;
    }
  }

  async function searchLocationOnMap() {
    const query = document.getElementById('map-search-input').value.trim();
    if (!query) return;

    const searchBtn = document.getElementById('btn-map-search');
    const originalText = searchBtn.innerHTML;
    searchBtn.innerHTML = `<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> <span>กำลังหา...</span>`;
    searchBtn.disabled = true;
    refreshIcons();

    const coordRegex = /^[-+]?([1-8]?\d(\.\d+)?|90(\.0+)?)[,\s]+[-+]?(180(\.0+)?|((1[0-7]\d)|([1-9]?\d))(\.\d+)?)$/;

    try {
      if (coordRegex.test(query)) {
        const parts = query.split(/[,\s]+/).filter(Boolean);
        await updateLocationData(parts[0], parts[1]);
      } else {
        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&accept-language=th`);
        const data = await res.json();
        if (data && data.length > 0) {
          await updateLocationData(data[0].lat, data[0].lon);
        } else {
          Swal.fire({ icon: 'warning', title: 'ไม่พบสถานที่', text: 'ไม่พบสถานที่ที่ค้นหา กรุณาลองใช้คำอื่น', confirmButtonColor: '#005e8e' });
          document.getElementById('map-address-preview').innerText = "ไม่พบสถานที่ กรุณาลองคลิกบนแผนที่เอง";
        }
      }
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'ข้อผิดพลาด', text: 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์แผนที่', confirmButtonColor: '#005e8e' });
    }

    searchBtn.innerHTML = originalText;
    searchBtn.disabled = false;
    refreshIcons();
  }

  function openMapModal() {
    const modal = document.getElementById('map-modal');
    const content = document.getElementById('map-modal-content');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    setTimeout(() => {
      modal.classList.remove('opacity-0');
      content.classList.remove('scale-95');
      initLeafletMap();

      setTimeout(() => {
        if (map) map.invalidateSize();

        const currentLat = document.getElementById('agency-lat').value;
        const currentLng = document.getElementById('agency-lng').value;

        if (currentLat && currentLng) {
          const l = [parseFloat(currentLat), parseFloat(currentLng)];
          map.setView(l, 15);
          if (!mapMarker) mapMarker = L.marker(l).addTo(map);
          else mapMarker.setLatLng(l);

          tempLoc = {
            lat: currentLat,
            lng: currentLng,
            address: document.getElementById('agency-address').value || ''
          };

          document.getElementById('map-address-preview').innerText =
            tempLoc.address || `ละติจูด: ${currentLat}, ลองติจูด: ${currentLng}`;
        }
      }, 300);
    }, 50);
  }

  function closeMapModal() {
    if (isMapFullscreen) toggleMapFullscreen();
    const modal = document.getElementById('map-modal');
    const content = document.getElementById('map-modal-content');

    modal.classList.add('opacity-0');
    content.classList.add('scale-95');
    setTimeout(() => { modal.classList.add('hidden'); modal.classList.remove('flex'); }, 300);
  }

  function toggleMapFullscreen() {
    const modal = document.getElementById('map-modal');
    const content = document.getElementById('map-modal-content');
    const mapView = document.getElementById('map-view');
    const icon = document.getElementById('fullscreen-icon');

    isMapFullscreen = !isMapFullscreen;
    if (isMapFullscreen) {
      modal.classList.remove('p-4');
      content.classList.remove('max-w-3xl', 'rounded-xl');
      content.classList.add('h-full', 'max-w-full', 'rounded-none');
      mapView.classList.remove('h-[350px]', 'sm:h-[400px]');
      mapView.classList.add('flex-1');
      icon.setAttribute('data-lucide', 'minimize');
    } else {
      modal.classList.add('p-4');
      content.classList.remove('h-full', 'max-w-full', 'rounded-none');
      content.classList.add('max-w-3xl', 'rounded-xl');
      mapView.classList.remove('flex-1');
      mapView.classList.add('h-[350px]', 'sm:h-[400px]');
      icon.setAttribute('data-lucide', 'maximize');
    }
    refreshIcons();
    setTimeout(() => { if (map) map.invalidateSize(); }, 350);
  }

  function confirmMapLocation() {
    if (!tempLoc.lat) {
      Swal.fire({ icon: 'warning', title: 'แจ้งเตือน', text: 'กรุณาคลิกเลือกตำแหน่งบนแผนที่ก่อน', confirmButtonColor: '#005e8e' });
      return;
    }
    document.getElementById('agency-lat').value = tempLoc.lat;
    document.getElementById('agency-lng').value = tempLoc.lng;
    if (tempLoc.address) document.getElementById('agency-address').value = tempLoc.address;
    closeMapModal();
  }

  /** ==========================================
   * 6) Agency UI
   * ========================================== */
  function renderAgencyTable() {
    const tbody = document.getElementById('agency-table-body');
    const searchTerm = (document.getElementById('agency-search')?.value || '').toLowerCase();

    const filtered = agencies.filter(a =>
      (a.code || '').toLowerCase().includes(searchTerm) ||
      (a.doc_format || '').toLowerCase().includes(searchTerm) ||
      (a.name || '').toLowerCase().includes(searchTerm) ||
      (a.address || '').toLowerCase().includes(searchTerm)
    );

    tbody.innerHTML = filtered.map(item => {
      const emps = Array.isArray(item.employers) ? item.employers : [];
      const empsNorm = emps.map(e => typeof e === 'string' ? ({ user_id: e, name: e }) : e);

      const badge = item.status == 1
        ? '<span class="ml-2 text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">เปิด</span>'
        : '<span class="ml-2 text-[10px] font-bold px-2 py-0.5 rounded bg-slate-50 text-slate-600 border border-slate-200">ปิด</span>';
		
	  const perms = Array.isArray(item.permissions) ? item.permissions : [];
      const permBadges = perms.length > 0
        ? perms.map(p => `
            <span class="inline-flex items-center px-2 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-medium">
              ${escapeHtml(getPermissionLabel(p))}
            </span>
          `).join('')
        : `<span class="text-slate-400 text-[10px]">ไม่มีสิทธิ์</span>`;
		
			
      return `
        <tr class="hover:bg-slate-50 transition-colors">
          <td class="px-5 py-3 font-mono text-slate-500 text-[11px]">${escapeHtml(item.code)}</td>
          <td class="px-5 py-3">
			  <div class="font-bold text-slate-800 flex items-center gap-2">
				<span>${escapeHtml(item.name)}</span>
				${badge}
			  </div>
			
			  ${item.doc_format ? `
				<div class="text-[11px] text-slate-500 mt-1">
				  <span class="font-mono">${escapeHtml(item.doc_format)}</span>
				</div>` : ''}
			
			  ${(item.start_date || item.end_date) ? `
				  <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
					<i data-lucide="calendar-range" class="w-3.5 h-3.5"></i>
					<span>
					  ${item.start_date ? escapeHtml(formatThaiDate(item.start_date)) : '-'}
					  ถึง
					  ${item.end_date ? escapeHtml(formatThaiDate(item.end_date)) : '-'}
					</span>
				  </div>` : ''}
			
			  ${item.address ? `
				<div class="text-[11px] text-slate-500 mt-1 flex items-start gap-1 max-w-[320px]">
				  <i data-lucide="map-pin" class="w-3.5 h-3.5 shrink-0 mt-0.5"></i>
				  <span class="line-clamp-2" title="${escapeHtml(item.address)}">${escapeHtml(item.address)}</span>
				</div>` : ''}
			</td>
			<td class="px-5 py-3">
			  <div class="flex flex-wrap gap-1.5 max-w-[260px]">
				${permBadges}
			  </div>
			</td>
          <td class="px-5 py-3 text-center">
            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold">${empsNorm.length}</span>
          </td>
          <td class="px-5 py-3">
            <div class="flex -space-x-1.5">
              ${empsNorm.length > 0 ? empsNorm.slice(0, 4).map(adm => `
                <div class="w-7 h-7 rounded-full border-2 border-white flex items-center justify-center text-[9px] font-bold shadow-sm text-white"
                     style="background-color:${stringToColor(adm.name || adm.user_id)}"
                     title="${escapeHtml((adm.name||adm.user_id) + ' (' + (adm.user_id||'') + ')')}">
                  ${(adm.name || adm.user_id || 'U').substring(0,2).toUpperCase()}
                </div>
              `).join('') : '<span class="text-slate-400 text-[10px]">ไม่มีผู้ดูแล</span>'}
              ${empsNorm.length > 4 ? `<div class="w-7 h-7 rounded-full border-2 border-white bg-slate-100 text-slate-500 flex items-center justify-center text-[9px] font-bold shadow-sm">+${empsNorm.length - 4}</div>` : ''}
            </div>
          </td>
          <td class="px-5 py-3 text-center">
            <div class="flex items-center justify-center gap-1.5">
              <button onclick="editAgency('${item.id}')" class="p-1.5 text-slate-400 hover:text-easy-primary hover:bg-blue-50 rounded transition-colors" title="Edit">
                <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
              </button>
              <button onclick="deleteAgency('${item.id}')" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors" title="Delete">
                <i data-lucide="trash" class="w-3.5 h-3.5"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    document.getElementById('stat-agencies').innerText = filtered.length;
    refreshIcons();
  }

  function addAdminRow(initialUserId = '', append = false) {
    const container = document.getElementById('admin-inputs-container');
    const div = document.createElement('div');
    div.className = 'admin-row-card p-2 rounded-lg border border-slate-200 bg-slate-50 space-y-2';

    div.innerHTML = `
      <div class="flex items-center gap-2">
        <div class="flex-1 relative">
          <i data-lucide="search" class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
          <input type="text" class="input-clean pl-9 admin-search" placeholder="ค้นหาชื่อผู้ดูแล...">
        </div>
        <button type="button"
          onclick="this.closest('.admin-row-card').remove(); refreshIcons();"
          class="text-slate-400 hover:text-red-500 p-2 rounded hover:bg-white transition-colors shrink-0">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>

      <select class="input-clean admin-select">
        <option value="">-- เลือกผู้ใช้ --</option>
        ${adminMasterList.map(u => `<option value="${u.user_id}" ${String(initialUserId)===String(u.user_id)?'selected':''}>${escapeHtml(u.name)} (${escapeHtml(u.user_id)})</option>`).join('')}
      </select>
    `;

    if (append) container.appendChild(div); else container.prepend(div);

    const searchInput = div.querySelector('.admin-search');
    const select = div.querySelector('.admin-select');

    searchInput.addEventListener('input', () => {
      const q = searchInput.value.trim().toLowerCase();
      const opts = adminMasterList.filter(u =>
        (u.name || '').toLowerCase().includes(q) || (u.user_id || '').toLowerCase().includes(q)
      );
      const cur = select.value;

      select.innerHTML = `<option value="">-- เลือกผู้ใช้ --</option>` +
        opts.map(u => `<option value="${u.user_id}" ${u.user_id===cur?'selected':''}>${escapeHtml(u.name)} (${escapeHtml(u.user_id)})</option>`).join('');
    });

    refreshIcons();
  }

  async function handleFormSubmit(e) {
  e.preventDefault();

  const editId        = document.getElementById('edit-id').value;
  const ag_job        = document.getElementById('agency-code').value.trim();
  const ag_format     = document.getElementById('agency-doc-format').value.trim();
  const ag_contract   = document.getElementById('agency-name').value.trim();
  const ag_located    = document.getElementById('agency-address').value.trim();
  const ag_latitude   = document.getElementById('agency-lat').value.trim();
  const ag_longitude  = document.getElementById('agency-lng').value.trim();
  const ag_status     = document.getElementById('agency-status').value;
  const ag_start_date = document.getElementById('agency-start-date').value;
  const ag_end_date   = document.getElementById('agency-end-date').value;

  const employerIds = Array.from(document.querySelectorAll('.admin-select'))
    .map(s => s.value)
    .filter(Boolean);

  if (!ag_job || !ag_contract) {
    showToast('กรุณากรอก รหัสหน่วยงาน และ ชื่อหน่วยงาน', 'error');
    return;
  }

  if (ag_start_date && ag_end_date && ag_start_date > ag_end_date) {
    showToast('วันที่เริ่มต้นต้องไม่มากกว่าวันที่สิ้นสุด', 'error');
    return;
  }

  try {
    const payload = {
      action: editId ? 'update' : 'create',
      ag_id: editId || '',
      ag_job,
      ag_format,
      ag_contract,
      ag_located,
      ag_latitude,
      ag_longitude,
      ag_status,
      ag_start_date,
      ag_end_date,
      employers: JSON.stringify(employerIds),
      menus: JSON.stringify(getPermissions())
    };

    const json = await apiPost(AGENCY_API_URL, payload);
    if (!json.success) throw new Error(json.message || 'save failed');

    showToast(editId ? 'อัปเดตหน่วยงานสำเร็จ' : 'เพิ่มหน่วยงานสำเร็จ');

    await fetchAgencies();
    renderAgencyTable();
    resetForm();
  } catch (err) {
    Swal.fire({
      icon:'error',
      title:'บันทึกไม่สำเร็จ',
      text: err.message || 'เกิดข้อผิดพลาด',
      confirmButtonColor:'#005e8e'
    });
  }
}

function formatThaiDate(dateStr) {
  if (!dateStr) return '-';

  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;

  return new Intl.DateTimeFormat('th-TH', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  }).format(d);
}

function getPermissions(){
return Array.from(
document.querySelectorAll(".perm-check:checked")
).map(el=>el.value);
}
  async function editAgency(id) {
		  const a = agencies.find(x => String(x.id) === String(id));
		  if (!a) return;
		
		  document.getElementById('edit-id').value = a.id;
		  document.getElementById('agency-code').value = a.code || '';
		  document.getElementById('agency-doc-format').value = a.doc_format || '';
		  document.getElementById('agency-name').value = a.name || '';
		  document.getElementById('agency-address').value = a.address || '';
		  document.getElementById('agency-lat').value = a.lat || '';
		  document.getElementById('agency-lng').value = a.lng || '';
		  document.getElementById('agency-start-date').value = a.start_date || '';
		  document.getElementById('agency-end-date').value = a.end_date || '';
		  document.getElementById('agency-status').value = String(a.status ?? 1);
		
		  await loadPermissions(id);
		
		  document.getElementById('form-title').innerHTML =
			`<i data-lucide="edit-3" class="w-4 h-4 text-orange-500"></i> <span>แก้ไขหน่วยงาน</span>`;
		
		  const container = document.getElementById('admin-inputs-container');
		  container.innerHTML = '';
		
		  const emps = Array.isArray(a.employers) ? a.employers : [];
		  const ids = emps.map(e => (typeof e === 'string' ? e : (e.user_id || ''))).filter(Boolean);
		
		  if (ids.length) ids.forEach(uid => addAdminRow(uid, true));
		  else addAdminRow('', true);
		
		  refreshIcons();
		  if (window.innerWidth < 1024) {
			document.getElementById('agencyForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
		  }
		}

  function deleteAgency(id) {
    Swal.fire({
      title: 'ยืนยันการลบ?',
      text: "คุณต้องการลบข้อมูลหน่วยงานนี้ใช่หรือไม่?",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#94a3b8',
      confirmButtonText: 'ใช่, ลบเลย!',
      cancelButtonText: 'ยกเลิก'
    }).then(async (result) => {
      if (!result.isConfirmed) return;
      try {
        const json = await apiPost(AGENCY_API_URL, { action: 'delete', ag_id: id });
        if (!json.success) throw new Error(json.message || 'delete failed');

        showToast('ลบข้อมูลเรียบร้อย', 'success');
        await fetchAgencies();
        renderAgencyTable();
      } catch (err) {
        Swal.fire({ icon:'error', title:'ลบไม่สำเร็จ', text: err.message || 'เกิดข้อผิดพลาด', confirmButtonColor:'#005e8e' });
      }
    });
  }

  function resetForm() {
    document.getElementById('agencyForm').reset();
    document.getElementById('edit-id').value = '';
	 renderPermissions([]); // ⭐ clear permission
    document.getElementById('form-title').innerHTML =
      `<i data-lucide="plus-square" class="w-4 h-4 text-easy-primary"></i> <span>เพิ่มหน่วยงานใหม่</span>`;

    document.getElementById('admin-inputs-container').innerHTML = '';
    addAdminRow('');

    tempLoc = { lat: '', lng: '', address: '' };
    if (mapMarker && map) { map.removeLayer(mapMarker); mapMarker = null; }
    refreshIcons();
  }

  /** ==========================================
   * 7) Users UI + CRUD
   * ========================================== */
  function getRoleBadgeClass(role) {
    switch (role) {
      case 'super_admin': return 'bg-purple-50 text-purple-700 border-purple-200';
      case 'employer': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
      default: return 'bg-slate-50 text-slate-600 border-slate-200';
    }
  }

  function renderUserTable() {
  const tbody = document.getElementById('user-table-body');
  const searchTerm = (document.getElementById('user-search')?.value || '').toLowerCase();

  const filtered = allUsers.filter(u =>
    (u.username || '').toLowerCase().includes(searchTerm) ||
    (u.fname || '').toLowerCase().includes(searchTerm) ||
    (u.lname || '').toLowerCase().includes(searchTerm) ||
    (u.email || '').toLowerCase().includes(searchTerm)
  );

  tbody.innerHTML = filtered.map(user => {
    const agenciesHtml = (user.agency_names && user.agency_names.length)
      ? user.agency_names.map(name => `
          <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-medium border bg-blue-50 text-blue-700 border-blue-200">
            ${escapeHtml(name)}
          </span>
        `).join('')
      : '<span class="text-[10px] text-slate-400">-</span>';

    return `
      <tr class="hover:bg-slate-50 transition-colors">
        <td class="px-5 py-3">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 font-bold text-[10px] shrink-0 border border-slate-300">${escapeHtml(user.avatar)}</div>
            <div>
              <div class="font-bold text-slate-800 leading-tight">${escapeHtml(user.fname)} ${escapeHtml(user.lname)}</div>
              <div class="text-[10px] text-slate-400">ID: ${user.id}</div>
            </div>
          </div>
        </td>
        <td class="px-5 py-3 font-mono text-slate-500 text-[11px]">${escapeHtml(user.username)}</td>
        <td class="px-5 py-3 text-slate-500 text-xs">${escapeHtml(user.email || '-')}</td>
        <td class="px-5 py-3">
          <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold border ${getRoleBadgeClass(user.role)}">${escapeHtml(user.role || '-')}</span>
        </td>
        <td class="px-5 py-3">
          <div class="flex flex-wrap gap-1.5 max-w-[220px]">
            ${agenciesHtml}
          </div>
        </td>
        <td class="px-5 py-3">
          <div class="flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full ${user.status_login == 1 ? 'bg-emerald-500' : 'bg-slate-300'}"></span>
            <span class="text-[10px] ${user.status_login == 1 ? 'text-emerald-600' : 'text-slate-500'} font-medium">${escapeHtml(user.status)}</span>
          </div>
        </td>
        <td class="px-5 py-3 text-center">
          <div class="flex items-center justify-center gap-1.5">
            <button onclick="editUser(${user.id})" class="p-1.5 text-slate-400 hover:text-slate-800 hover:bg-slate-100 rounded transition-colors" title="Edit"><i data-lucide="edit-2" class="w-3.5 h-3.5"></i></button>
            <button onclick="deleteUser(${user.id})" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded transition-colors" title="Delete"><i data-lucide="trash" class="w-3.5 h-3.5"></i></button>
          </div>
        </td>
      </tr>
    `;
  }).join('');

  document.getElementById('stat-users').innerText = filtered.length;
  refreshIcons();
}

  async function handleUserSubmit(e) {
    e.preventDefault();

    const editId   = document.getElementById('edit-user-id').value;
    const username = document.getElementById('user-username').value.trim();
    const pass     = document.getElementById('user-password').value;
    const fname    = document.getElementById('user-fname').value.trim(); // -> user_name
    const lname    = document.getElementById('user-lname').value.trim(); // -> user_fname
    const email    = (document.getElementById('user-email')?.value || '').trim();
    const role     = document.getElementById('user-role').value;         // -> user_level
    const status   = document.getElementById('user-status').value;       // -> user_status_login
	const userDepartment = document.getElementById('user-department').value;
	
	if (role === 'employer' && !userDepartment) {
	  showToast('กรุณาเลือกประเภท Employer', 'error');
	  return;
	}

    if (!username || !fname) { showToast('กรุณากรอก Username และ ชื่อจริง', 'error'); return; }

    try {
      if (editId) {
        const json = await apiPost(USER_API_URL, {
          action: 'update',
          id: editId,
          user_id: username,
          user_password: pass, // ว่างได้
          user_name: fname,
          user_fname: lname,
          user_email: email,
          user_level: role,
		  user_department: role === 'employer' ? userDepartment : '',
          user_status_login: status
        });
        if (!json.success) throw new Error(json.message || 'update failed');
        showToast('อัปเดตข้อมูลผู้ใช้สำเร็จ');
      } else {
        if (!pass) { showToast('กรุณากรอก Password', 'error'); return; }
        const json = await apiPost(USER_API_URL, {
          action: 'create',
          user_id: username,
          user_password: pass,
          user_name: fname,
          user_fname: lname,
          user_email: email,
          user_level: role,
		  user_department: role === 'employer' ? userDepartment : '',
          user_status_login: status
        });
        if (!json.success) throw new Error(json.message || 'create failed');
        showToast('เพิ่มผู้ใช้งานใหม่เรียบร้อย');
      }

      await fetchUsers();
      await fetchSuperAdmins(); // เผื่อ user ใหม่เป็น super_admin จะไปโผล่ในผู้ดูแลหน่วยงาน
      renderUserTable();
      resetUserForm();
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'บันทึกไม่สำเร็จ', text: err.message || 'เกิดข้อผิดพลาด', confirmButtonColor: '#005e8e' });
    }
  }

  function editUser(id) {
  const u = allUsers.find(item => item.id == id);
  if(!u) return;

  document.getElementById('edit-user-id').value = u.id;
  document.getElementById('user-username').value = u.username;
  document.getElementById('user-fname').value = u.fname;
  document.getElementById('user-lname').value = u.lname;
  document.getElementById('user-email').value = u.email || '';
  document.getElementById('user-role').value = u.role || 'super_admin';
  toggleUserDepartment();
  document.getElementById('user-department').value = u.department || '';
  document.getElementById('user-status').value = (u.status_login == 1 ? '1' : '0');

  document.getElementById('user-form-title').innerHTML =
    `<i data-lucide="edit-3" class="w-4 h-4 text-orange-500"></i> <span>แก้ไขผู้ใช้งาน</span>`;

  const passInput = document.getElementById('user-password');
  passInput.value = '';
  passInput.removeAttribute('required');
  passInput.placeholder = 'ปล่อยว่างหากไม่เปลี่ยนรหัส';

  refreshIcons();
  if(window.innerWidth < 1024) document.getElementById('userForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

 function resetUserForm() {
	  document.getElementById('userForm').reset();
	  document.getElementById('edit-user-id').value = '';
	  document.getElementById('user-form-title').innerHTML =
		`<i data-lucide="user-plus" class="w-4 h-4 text-slate-800"></i> <span>เพิ่มผู้ใช้งานใหม่</span>`;
	
	  const passInput = document.getElementById('user-password');
	  passInput.setAttribute('required', 'required');
	  passInput.placeholder = '';
	  passInput.type = 'password';
	
	  const passIcon = document.getElementById('user-password-icon');
	  if(passIcon) passIcon.setAttribute('data-lucide', 'eye');
	
	  document.getElementById('user-department').value = '';
	  toggleUserDepartment();
	
	  refreshIcons();
	}

  function deleteUser(id) {
    Swal.fire({
      title: 'ยืนยันการลบ?',
      text: "คุณต้องการลบข้อมูลผู้ใช้งานนี้ใช่หรือไม่?",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#94a3b8',
      confirmButtonText: 'ใช่, ลบเลย!',
      cancelButtonText: 'ยกเลิก'
    }).then(async (result) => {
      if (!result.isConfirmed) return;
      try {
        const json = await apiPost(USER_API_URL, { action: 'delete', id: id });
        if (!json.success) throw new Error(json.message || 'delete failed');

        showToast('ลบข้อมูลเรียบร้อย', 'success');
        await fetchUsers();
        await fetchSuperAdmins();
        renderUserTable();
      } catch (err) {
        console.error(err);
        Swal.fire({ icon: 'error', title: 'ลบไม่สำเร็จ', text: err.message || 'เกิดข้อผิดพลาด', confirmButtonColor: '#005e8e' });
      }
    });
  }

  function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + '-icon');
    if (!input || !icon) return;

    if (input.type === 'password') {
      input.type = 'text';
      icon.setAttribute('data-lucide', 'eye-off');
    } else {
      input.type = 'password';
      icon.setAttribute('data-lucide', 'eye');
    }
    refreshIcons();
  }
</script>

<?php if ($amIsSuper) { ?>
<script>
/** ==========================================
 * Monitor ความเคลื่อนไหวของหน่วยงาน (Super Admin) — handle_agency_monitor.php
 *  รายการแถวกะทัดรัด: สถานะ · แนวโน้ม · ยอดรวม · ตัวเลขหลัก (กดเพื่อดูข้อมูลจริง)
 * ========================================== */
(function () {
  const $ = id => document.getElementById(id);
  const esc = v => String(v == null ? '' : v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const nf = n => Number(n || 0).toLocaleString('th-TH');
  const ico = (name, cls) => `<i data-lucide="${name}" class="${cls || 'w-3.5 h-3.5'}"></i>`;
  const thDT = s => { if (!s) return '-'; const d = new Date(String(s).replace(' ', 'T')); return isNaN(d) ? s : d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' }) + ' ' + d.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }); };
  const thD = s => { if (!s) return '-'; const d = new Date(String(s).slice(0, 10) + 'T00:00:00'); return isNaN(d) ? s : d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' }); };
  function ago(s) {
    if (!s) return 'ไม่มีข้อมูล';
    const ms = Date.now() - new Date(String(s).replace(' ', 'T')).getTime();
    if (isNaN(ms)) return s;
    const m = Math.max(0, Math.floor(ms / 60000));
    if (m < 60) return m <= 1 ? 'เมื่อสักครู่' : m + ' นาทีก่อน';
    const h = Math.floor(m / 60); if (h < 24) return h + ' ชม.ก่อน';
    const d = Math.floor(h / 24); if (d < 60) return d + ' วันก่อน';
    return Math.floor(d / 30) + ' เดือนก่อน';
  }
  const STATE = { active: ['ใช้งานปกติ', '#22c55e'], quiet: ['เงียบ', '#f59e0b'], idle: ['ไม่เคลื่อนไหว', '#ef4444'], never: ['ไม่มีข้อมูล', '#cbd5e1'] };
  // ตัวเลขหลักที่กดดูข้อมูลได้: key, ไอคอน, ป้าย, ค่า
  const METRICS = [
    ['login', 'log-in', 'เข้าใช้งาน', a => a.login.n],
    ['repair', 'wrench', 'แจ้งซ่อม', a => a.repair.new],
    ['pm', 'clipboard-check', 'PM', a => a.pm.done],
    ['meter', 'gauge', 'มิเตอร์', a => a.meter.n],
    ['stock', 'package', 'สต็อก', a => a.stock.n],
    ['data', 'database', 'ข้อมูล/ตั้งค่า', a => a.machine.added + a.machine.updated + a.setting.n]
  ];
  let DATA = [], days = 30, filter = '', sortKey = 'last', loaded = false, loading = false;
  const total = a => a.spark.reduce((x, y) => x + y, 0);

  function spark(vals, color) {
    const w = 120, h = 28, n = vals.length, max = Math.max(1, ...vals);
    if (!vals.some(v => v)) return `<svg width="${w}" height="${h}"><line x1="0" y1="${h - 2}" x2="${w}" y2="${h - 2}" stroke="#e2e8f0" stroke-width="1.5" stroke-dasharray="3 4"/></svg>`;
    const pts = vals.map((v, i) => [(n === 1 ? w / 2 : i * w / (n - 1)), h - 2 - (v / max) * (h - 6)]);
    const line = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
    return `<svg width="${w}" height="${h}" class="overflow-visible"><path d="${line} L${w} ${h} L0 ${h} Z" fill="${color}" fill-opacity=".1"/><path d="${line}" fill="none" stroke="${color}" stroke-width="1.6" stroke-linejoin="round"/></svg>`;
  }

  function renderFilters() {
    const c = { '': DATA.length, active: 0, quiet: 0, idle: 0, expired: 0 };
    DATA.forEach(a => { c[a.state === 'never' ? 'idle' : a.state]++; if (a.expired) c.expired++; });
    const f = (k, label, color) => `<button type="button" class="am-filter ${filter === k ? 'on' : ''}" data-filter="${k}">${color ? `<span class="am-dot" style="background:${color}"></span>` : ''}${label} <b>${c[k]}</b></button>`;
    $('am-filters').innerHTML = f('', 'ทั้งหมด') + f('active', 'ใช้งานปกติ', '#10b981') + f('quiet', 'เงียบ', '#f59e0b') + f('idle', 'ไม่เคลื่อนไหว', '#ef4444') + (c.expired ? f('expired', 'สัญญาหมด', '#64748b') : '');
  }

  // ตัวเลข 1 ช่อง (กดเพื่อดูข้อมูลจริง) — 0 = สีจาง ไม่ต้องกด
  function cell(a, key, val, sub, subRed) {
    const lbl = (METRICS.find(m => m[0] === key) || [])[2] || '';
    if (!val && !subRed) return `<div class="am-n zero" title="${lbl}: 0"><span class="lbl hidden text-[10px] text-slate-400">${lbl}</span><b>0</b></div>`;
    return `<div class="am-n link" data-ag="${a.ag_id}" data-tab="${key}" title="ดู${lbl}"><span class="lbl hidden text-[10px] text-slate-400">${lbl}</span><b>${nf(val)}</b>${sub ? `<span class="x ${subRed ? 'red' : ''}">${sub}</span>` : ''}</div>`;
  }

  function row(a) {
    const st = STATE[a.state];
    const idleDays = a.last ? (Date.now() - new Date(String(a.last).replace(' ', 'T'))) / 86400000 : 999;
    return `<div class="am-grid am-r" data-ag="${a.ag_id}" data-tab="overview">
      <div class="min-w-0">
        <div class="am-name" title="${esc(a.name)}">${esc(a.name)}</div>
        <div class="am-meta">${esc(a.code || '-')} · ผู้ใช้ ${nf(a.users)} คน${a.expired ? ' · สัญญาหมด ' + esc(thD(a.end)) : ''}${a.enabled ? '' : ' · ปิดใช้งาน'}</div>
      </div>
      <div><span class="am-state"><span class="am-dot" style="background:${st[1]}"></span>${st[0]}</span></div>
      <div class="am-col-ago"><div class="am-ago ${idleDays > 14 ? 'old' : ''}" title="${esc(thDT(a.last))}">${esc(ago(a.last))}</div></div>
      <div class="am-col-spark">${spark(a.spark, '#38bdf8')}</div>
      <div class="am-col-metrics">
        ${cell(a, 'login', a.login.n, a.login.n ? nf(a.login.users) + '/' + nf(a.users) + ' คน' : '')}
        ${cell(a, 'repair', a.repair.new, a.repair.backlog ? 'ค้าง ' + nf(a.repair.backlog) : '')}
        ${cell(a, 'pm', a.pm.done, a.pm.overdue ? 'เลย ' + nf(a.pm.overdue) : '', !!a.pm.overdue)}
        ${cell(a, 'meter', a.meter.n)}
        ${cell(a, 'stock', a.stock.n)}
        ${cell(a, 'data', a.machine.added + a.machine.updated + a.setting.n)}
      </div>
      <div class="am-col-arrow text-slate-300">${ico('chevron-right', 'w-4 h-4')}</div>
    </div>`;
  }

  function headHtml() {
    const h = (key, label, num, cls) => `<div class="h ${num ? 'num' : ''} ${sortKey === key ? 'on' : ''} ${cls || ''}" data-sort="${key}">${label}${sortKey === key ? '<span class="ar">▼</span>' : ''}</div>`;
    return `<div class="am-grid am-head">${h('name', 'หน่วยงาน')}${h('state', 'สถานะ')}${h('last', 'ล่าสุด')}<div class="h am-col-spark" style="cursor:default">แนวโน้ม ${days} วัน</div>
      ${h('login', 'เข้าใช้งาน', 1)}${h('repair', 'แจ้งซ่อม', 1)}${h('pm', 'PM', 1)}${h('meter', 'มิเตอร์', 1)}${h('stock', 'สต็อก', 1)}${h('data', 'ข้อมูล', 1)}<div></div></div>`;
  }

  function currentRows() {
    const q = $('am-q').value.trim().toLowerCase();
    const rows = DATA.filter(a => (!filter || (filter === 'expired' ? a.expired : filter === 'idle' ? (a.state === 'idle' || a.state === 'never') : a.state === filter))
      && (!q || (a.name || '').toLowerCase().includes(q) || (a.code || '').toLowerCase().includes(q)));
    const num = f => (x, y) => f(y) - f(x);
    const ORD = { active: 0, quiet: 1, idle: 2, never: 3 };
    const by = {
      last: (x, y) => (y.last || '').localeCompare(x.last || ''), total: num(total),
      idle: (x, y) => (x.last || '').localeCompare(y.last || ''), name: (x, y) => (x.name || '').localeCompare(y.name || '', 'th'),
      state: (x, y) => ORD[x.state] - ORD[y.state], login: num(a => a.login.n), repair: num(a => a.repair.new), pm: num(a => a.pm.done),
      meter: num(a => a.meter.n), stock: num(a => a.stock.n), data: num(a => a.machine.added + a.machine.updated + a.setting.n)
    }[sortKey] || ((x, y) => (y.last || '').localeCompare(x.last || ''));
    rows.sort((x, y) => by(x, y) || (x.name || '').localeCompare(y.name || '', 'th'));
    return rows;
  }

  function render() {
    renderFilters();
    const rows = currentRows();
    $('am-list').innerHTML = (rows.length ? headHtml() + rows.map(row).join('')
      : `<div class="py-16 text-center text-slate-400 text-sm">ไม่พบหน่วยงานตามเงื่อนไข</div>`);
    if (window.lucide) lucide.createIcons();
  }

  async function load() {
    if (loading) return;
    loading = true;
    $('am-list').innerHTML = Array.from({ length: 6 }, () => '<div class="am-r flex items-center gap-4 !cursor-default animate-pulse"><div class="h-4 bg-slate-100 rounded w-56"></div><div class="h-4 bg-slate-100 rounded w-20"></div><div class="h-4 bg-slate-100 rounded flex-1"></div></div>').join('');
    try {
      const r = await (await fetch('handle_agency_monitor.php?action=summary&days=' + days)).json();
      if (!r.success) throw new Error(r.error || 'โหลดไม่สำเร็จ');
      DATA = r.agencies; loaded = true;
      $('am-updated').textContent = 'ความเคลื่อนไหว ' + days + ' วันล่าสุดของทุกหน่วยงาน · อัปเดต ' + new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) + ' น.';
      render();
    } catch (e) {
      $('am-list').innerHTML = `<div class="py-16 text-center text-red-500 text-sm">${esc(e.message)}</div>`;
    } finally { loading = false; }
  }
  window.amOpen = () => { if (!loaded) load(); };

  // ---------- รายละเอียดหน่วยงาน (แท็บ: ภาพรวม + ข้อมูลจริงแต่ละประเภท) ----------
  const SERIES = [
    ['login', 'เข้าใช้งาน', '#94a3b8'], ['repair_new', 'แจ้งซ่อมใหม่', '#f59e0b'], ['repair_closed', 'ปิดงานซ่อม', '#10b981'], ['pm_done', 'PM ทำแล้ว', '#0ea5e9'],
    ['meter', 'จดมิเตอร์', '#8b5cf6'], ['stock', 'สต็อก', '#ec4899'], ['machine', 'เครื่องจักร', '#14b8a6'], ['setting', 'ตั้งค่า', '#cbd5e1']
  ];
  const FEED_ICON = { login: 'log-in', repair: 'wrench', repair_done: 'check-circle-2', pm: 'clipboard-check', meter: 'gauge', stock: 'package', machine: 'cpu', setting: 'settings' };
  const ST_LABEL = {
    pending: ['รอดำเนินการ', '#fffbeb', '#b45309'], inprogress: ['กำลังดำเนินการ', '#f0f9ff', '#0369a1'], completed: ['เสร็จสิ้น', '#ecfdf5', '#047857'],
    feedback: ['ประเมินแล้ว', '#f5f3ff', '#6d28d9'], cancel: ['ยกเลิก', '#fef2f2', '#b91c1c'], cencel: ['ยกเลิก', '#fef2f2', '#b91c1c'],
    done: ['ทำแล้ว', '#ecfdf5', '#047857'], evaluated: ['ประเมินแล้ว', '#f5f3ff', '#6d28d9'],
    IN: ['รับเข้า', '#ecfdf5', '#047857'], OUT: ['เบิกจ่าย', '#fffbeb', '#b45309'], ADJUST: ['ปรับยอด', '#f1f5f9', '#475569'],
    add: ['เพิ่ม', '#ecfdf5', '#047857'], edit: ['แก้ไข', '#f0f9ff', '#0369a1']
  };
  const stBadge = s => { const x = ST_LABEL[s]; return x ? `<span class="am-st" style="background:${x[1]};color:${x[2]}">${x[0]}</span>` : ''; };
  let detailChart = null, cur = null, listCache = {};

  function tabCounts(a) {
    return { login: a.login.n, repair: a.repair.new, pm: a.pm.done, meter: a.meter.n, stock: a.stock.n, data: a.machine.added + a.machine.updated + a.setting.n };
  }

  // ประวัติเข้าสู่ระบบของผู้ใช้ (ช่วงที่เลือก) จัดกลุ่มตามวัน
  // ป้ายวัน: วันนี้ / เมื่อวาน / "พ. 21 ต.ค. 69"
  function dayLabel(ymdStr) {
    const d = new Date(ymdStr + 'T00:00:00'), t = new Date(); t.setHours(0, 0, 0, 0);
    const diff = Math.round((t - d) / 86400000);
    if (diff === 0) return 'วันนี้';
    if (diff === 1) return 'เมื่อวาน';
    return d.toLocaleDateString('th-TH', { weekday: 'short', day: 'numeric', month: 'short', year: '2-digit' });
  }

  // แผงประวัติเข้าสู่ระบบ: หน้าต่างแยกทางขวาของหน้าต่างรายละเอียด (ไม่ทับกัน) · ปฏิทินทีละเดือน เวลาเข้าแสดงในช่องวัน
  const UP_W = 460, UP_GAP = 12;
  let upUser = null;

  // ความกว้างหน้าต่างรายละเอียด: ใช้ขนาดเดียวกับตอนเปิดแผงไว้ตั้งแต่แรก จะได้ไม่ย่อขยายตอนเปิด/ปิดแผง
  function detailWidth() {
    const vw = document.documentElement.clientWidth, side = 16;
    const split = Math.min(1056, vw - side * 2 - Math.min(UP_W, vw - side * 2) - UP_GAP);
    return split >= 640 ? split : Math.min(1056, vw - side * 2);
  }
  function fitDetail() {
    const pop = Swal.getPopup(); if (!pop) return;
    if (upUser) { placeUserPanel(); return; }
    pop.style.width = detailWidth() + 'px'; pop.style.transform = '';
  }

  function placeUserPanel() {
    const pn = $('am-up'), pop = Swal.getPopup(); if (!pn || !pop) return;
    const vw = document.documentElement.clientWidth, vh = window.innerHeight, side = 16;
    const w = Math.min(UP_W, vw - side * 2);
    const popW = Math.min(1056, vw - side * 2 - w - UP_GAP);
    pn.style.width = w + 'px';
    if (popW >= 640) {
      // จอกว้างพอ: เลื่อนหน้าต่างรายละเอียดไปซ้าย วางแผงชิดขวา
      pop.style.width = popW + 'px';
      pop.style.transform = `translateX(${-(w + UP_GAP) / 2}px)`;
      const r = pop.getBoundingClientRect();
      pn.style.left = ((vw - (popW + UP_GAP + w)) / 2 + popW + UP_GAP) + 'px';
      pn.style.top = r.top + 'px'; pn.style.height = r.height + 'px';
    } else {
      // จอแคบ: แผงซ้อนด้านขวาเต็มความสูง
      pop.style.width = detailWidth() + 'px'; pop.style.transform = '';
      pn.style.left = (vw - w - 8) + 'px'; pn.style.top = '8px'; pn.style.height = (vh - 16) + 'px';
    }
  }

  function closeUserPanel() {
    upUser = null;
    const pn = $('am-up'); if (pn) pn.remove();
    fitDetail();
    document.querySelectorAll('#am-users .am-user.on').forEach(x => x.classList.remove('on'));
  }

  async function toggleUserLog(btn) {
    const user = btn.dataset.user;
    if (upUser === user) { closeUserPanel(); return; }
    document.querySelectorAll('#am-users .am-user.on').forEach(x => x.classList.remove('on'));
    btn.classList.add('on'); upUser = user;
    let pn = $('am-up');
    if (!pn) {
      pn = document.createElement('div'); pn.id = 'am-up';
      Swal.getContainer().appendChild(pn);
    }
    const name = btn.querySelector('.am-uname').textContent;
    pn.innerHTML = `<div class="px-5 pt-4 pb-3 border-b border-slate-100 flex-none">
<<<<<<< HEAD
        <button type="button" data-x class="am-up-x" title="ปิด" aria-label="ปิด">×</button>
        <div class="min-w-0 pr-10"><div class="text-[11px] text-slate-400">ประวัติเข้าสู่ระบบ · ${days} วันล่าสุด</div><div class="text-[15px] font-bold text-slate-800 truncate">${esc(name)}</div></div>
=======
        <div class="flex items-start gap-2"><div class="min-w-0 flex-1"><div class="text-[11px] text-slate-400">ประวัติเข้าสู่ระบบ · ${days} วันล่าสุด</div><div class="text-[15px] font-bold text-slate-800 truncate">${esc(name)}</div></div>
          <button type="button" data-x class="w-8 h-8 -mr-2 rounded-lg hover:bg-slate-100 text-slate-400 flex items-center justify-center flex-none" title="ปิด">${ico('x', 'w-4 h-4')}</button></div>
>>>>>>> 50333a3abac16661c1be685b2248b4ad0342428c
        <div data-sum class="text-[12px] text-slate-500 mt-0.5"></div></div>
      <div data-body class="am-scroll flex-1 min-h-0 overflow-y-auto px-4 py-3"><div class="text-xs text-slate-400 py-6 text-center">กำลังโหลด...</div></div>`;
    pn.onclick = e => { if (e.target.closest('[data-x]')) closeUserPanel(); };
    if (window.lucide) lucide.createIcons();
    placeUserPanel();
    requestAnimationFrame(() => pn.classList.add('show'));
    const body = pn.querySelector('[data-body]');
    try {
      const r = await (await fetch(`handle_agency_monitor.php?action=user_logins&ag=${cur.ag_id}&user=${encodeURIComponent(user)}&days=${days}`)).json();
      if (!r.success) throw new Error(r.error || 'โหลดไม่สำเร็จ');
      if (upUser !== user) return;   // ระหว่างโหลดผู้ใช้กดคนอื่นแล้ว
      pn.querySelector('[data-sum]').textContent = r.days.length
        ? `รวม ${nf(r.total)} ครั้ง ใน ${nf(r.days.length)} วัน${r.total >= 1000 ? ' (แสดง 1,000 ครั้งล่าสุด)' : ''}`
        : `ไม่ได้เข้าสู่ระบบในช่วงนี้${r.last_ever ? ' · เข้าล่าสุด ' + thDT(r.last_ever) : ' · ยังไม่เคยเข้าสู่ระบบ'}`;
      if (!r.days.length) { body.innerHTML = '<div class="text-xs text-slate-400 py-6 text-center">ไม่มีข้อมูลการเข้าสู่ระบบ</div>'; return; }
      const byDay = {};
      r.days.forEach(d => { byDay[d.d] = d.times.map(t => t.slice(0, 5)).sort(); });   // เช้า → เย็น
      const pad = n => String(n).padStart(2, '0');
      const ymdOf = dt => dt.getFullYear() + '-' + pad(dt.getMonth() + 1) + '-' + pad(dt.getDate());
      const today = new Date(); today.setHours(0, 0, 0, 0);
      const from = new Date(today); from.setDate(from.getDate() - (days - 1));
      const months = [];   // เก่า → ใหม่
      for (let m = new Date(from.getFullYear(), from.getMonth(), 1); m <= today; m.setMonth(m.getMonth() + 1)) months.push(new Date(m));
      const latest = r.days[0].d;
      let mi = months.findIndex(m => ymdOf(m).slice(0, 7) === latest.slice(0, 7)); if (mi < 0) mi = months.length - 1;
      let sel = latest;
      // เวลาซ้ำนาทีเดียวกันรวมเป็น ×N
      const grouped = t => { const o = []; t.forEach(x => { const g = o[o.length - 1]; if (g && g.t === x) g.n++; else o.push({ t: x, n: 1 }); }); return o; };
      const render = () => {
        const m = months[mi], y = m.getFullYear(), mo = m.getMonth(), last = new Date(y, mo + 1, 0).getDate();
        let cells = '', cnt = 0;
        for (let i = 0; i < new Date(y, mo, 1).getDay(); i++) cells += '<span></span>';
        for (let d = 1; d <= last; d++) {
          const dt = new Date(y, mo, d), key = ymdOf(dt), t = byDay[key] || [], inRange = dt >= from && dt <= today;
          cnt += t.length;
          const cls = 'am-ud' + (t.length ? ' has' : '') + (!inRange ? ' out' : '') + (key === sel ? ' sel' : '') + (key === ymdOf(today) ? ' today' : '');
          // ช่องวัน: เวลาเข้าครั้งแรก (ตัวหนา) + "ถึง" เวลาล่าสุด · จำนวนครั้งมุมขวาบน
          const times = t.length ? `<span class="f">${t[0]}</span>${t[t.length - 1] !== t[0] ? `<span class="l">ถึง ${t[t.length - 1]}</span>` : ''}` : '';
          cells += `<div class="${cls}" ${t.length ? `data-d="${key}" title="${esc(dayLabel(key) + ' · ' + t.length + ' ครั้ง: ' + t.join(', '))}"` : ''}><div class="hd"><span class="n">${d}</span>${t.length ? `<span class="c">${t.length}</span>` : ''}</div>${times}</div>`;
        }
        const st = byDay[sel] || [];
        body.innerHTML = `<div class="flex items-center justify-between mb-2">
            <button type="button" data-mv="-1" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:hover:bg-transparent" ${mi === 0 ? 'disabled' : ''} title="เดือนก่อน">${ico('chevron-left', 'w-4 h-4')}</button>
            <div class="text-[13px] font-bold text-slate-700">${m.toLocaleDateString('th-TH', { month: 'long', year: 'numeric' })} <span class="font-normal text-slate-400 text-[11.5px]">· ${cnt ? nf(cnt) + ' ครั้ง' : 'ไม่ได้เข้า'}</span></div>
            <button type="button" data-mv="1" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 disabled:opacity-30 disabled:hover:bg-transparent" ${mi === months.length - 1 ? 'disabled' : ''} title="เดือนถัดไป">${ico('chevron-right', 'w-4 h-4')}</button></div>
          <div class="am-upc">${['อา', 'จ', 'อ', 'พ', 'พฤ', 'ศ', 'ส'].map(w => `<span class="w">${w}</span>`).join('')}${cells}</div>
          ${st.length ? `<div class="mt-3 rounded-lg bg-slate-50 px-3 py-2.5">
              <div class="flex items-baseline justify-between gap-2 text-[12px]"><b class="text-slate-700">${esc(dayLabel(sel))}</b><span class="text-slate-400">${st.length} ครั้ง${st.length > 1 ? ` · ${st[0]} – ${st[st.length - 1]}` : ''}</span></div>
              <div class="flex flex-wrap gap-1.5 mt-2">${grouped(st).map(g => `<span class="am-tc">${g.t}${g.n > 1 ? `<i>×${g.n}</i>` : ''}</span>`).join('')}</div></div>` : ''}`;
        if (window.lucide) lucide.createIcons();
      };
      body.onclick = e => {
        const mv = e.target.closest('[data-mv]:not([disabled])');
        if (mv) { mi += +mv.dataset.mv; render(); return; }
        const c = e.target.closest('.am-ud[data-d]');
        if (c) { sel = c.dataset.d; render(); }
      };
      render();
    } catch (e) { if (upUser === user) body.innerHTML = `<div class="text-xs text-red-500 py-6 text-center">${esc(e.message)}</div>`; }
  }

  async function showTab(tab) {
    const a = cur; if (!a) return;
    closeUserPanel();
    document.querySelectorAll('#am-tabs .am-tab').forEach(b => b.classList.toggle('on', b.dataset.tab === tab));
    const body = $('am-tabbody');
    if (detailChart) { detailChart.destroy(); detailChart = null; }
    if (tab === 'overview') {
      // ความสูงคงที่: กราฟสูงตายตัว ส่วน "ล่าสุด" และ "ผู้ใช้งาน" ยืดเต็มที่เหลือแล้วเลื่อนในกล่องของตัวเอง
      body.innerHTML = `<div class="grid grid-cols-1 lg:grid-cols-5 gap-5 lg:h-full lg:min-h-0">
          <div class="lg:col-span-3 flex flex-col gap-4 lg:min-h-0">
            <div class="flex-none"><div class="am-sec">ความเคลื่อนไหวรายวัน</div><div style="height: 190px"><canvas id="am-chart"></canvas></div></div>
            <div class="flex flex-col lg:flex-1 lg:min-h-0"><div class="am-sec">ความเคลื่อนไหวล่าสุด</div><div id="am-feed" class="am-scroll lg:flex-1 lg:min-h-0 overflow-y-auto pr-2" style="max-height: 420px"><div class="text-xs text-slate-400 py-4">กำลังโหลด...</div></div></div>
          </div>
          <div class="lg:col-span-2 flex flex-col lg:min-h-0 lg:border-l lg:border-slate-100 lg:pl-5">
            <div class="am-sec">ผู้ใช้งาน <span class="font-normal text-slate-400 text-[11px]">· กดชื่อเพื่อเปิดปฏิทินเข้าสู่ระบบ</span></div>
            <div id="am-users" class="am-scroll lg:flex-1 lg:min-h-0 overflow-y-auto pr-2" style="max-height: 520px"></div>
          </div>
        </div>`;
      try {
        const r = listCache.overview || (listCache.overview = await (await fetch(`handle_agency_monitor.php?action=detail&ag=${a.ag_id}&days=${days}`)).json());
        if (!r.success) throw new Error(r.error || 'โหลดไม่สำเร็จ');
        if (cur !== a || !$('am-chart')) return;
        const labels = r.days.map(d => new Date(d + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'short' }));
        detailChart = new Chart($('am-chart'), {
          type: 'bar',
          data: { labels, datasets: SERIES.map(([k, label, color]) => ({ label, data: r.daily[k], backgroundColor: color, borderRadius: 2, stack: 's', maxBarThickness: 18, hidden: k === 'setting' || k === 'machine' })) },
          options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
            scales: { x: { stacked: true, grid: { display: false }, ticks: { font: { family: 'Prompt', size: 10 }, color: '#94a3b8', maxRotation: 0, autoSkip: true } },
                      y: { stacked: true, beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { precision: 0, font: { family: 'Prompt', size: 10 }, color: '#94a3b8' } } },
            plugins: { legend: { position: 'bottom', labels: { font: { family: 'Prompt', size: 11 }, boxWidth: 7, boxHeight: 7, usePointStyle: true, pointStyle: 'circle', padding: 12 } }, tooltip: { filter: i => i.raw > 0 } } }
        });
        // จัดกลุ่มตามวัน (หัววันติดด้านบนขณะเลื่อน) · เวลาอยู่คอลัมน์ซ้าย ไม่ต้องอ่านวันที่ซ้ำทุกบรรทัด
        let lastDay = '';
        $('am-feed').innerHTML = r.feed.length ? r.feed.slice(0, 40).map(f => {
          const d = String(f.t).slice(0, 10), head = d !== lastDay ? `<div class="am-day">${esc(dayLabel(d))}</div>` : '';
          lastDay = d;
          return head + `<div class="flex items-start gap-3 py-1.5">
            <span class="w-10 flex-none text-[11.5px] text-slate-400 tabular-nums pt-[3px]">${esc(String(f.t).slice(11, 16))}</span>
            <span class="am-fi">${ico(FEED_ICON[f.type] || 'settings', 'w-3.5 h-3.5')}</span>
            <div class="min-w-0 flex-1"><div class="text-[12.5px] text-slate-700 leading-snug break-words">${esc(f.text)}${f.more ? ` <span class="text-[11px] text-slate-400">และอีก ${f.more} รายการ</span>` : ''}</div>
            ${f.user_name ? `<div class="text-[11px] text-slate-400 mt-0.5">${esc(f.user_name)}</div>` : ''}</div></div>`;
        }).join('') : '<div class="text-xs text-slate-400 py-4">ยังไม่มีความเคลื่อนไหว</div>';
        const maxN = Math.max(1, ...r.users.map(u => u.n));
        $('am-users').innerHTML = r.users.length ? r.users.map(u => `<div class="border-b border-slate-100 last:border-0">
            <button type="button" class="am-user w-full text-left py-2 px-1.5 -mx-1.5 rounded-lg hover:bg-slate-50" data-user="${esc(u.user_id)}" title="กดเพื่อดูวัน เวลาที่เข้าสู่ระบบ">
              <div class="flex items-baseline justify-between gap-2"><span class="text-[12.5px] font-semibold text-slate-700 truncate flex items-center gap-1"><span class="am-caret text-slate-300 transition-transform">${ico('chevron-right', 'w-3.5 h-3.5')}</span><span class="am-uname truncate">${esc(u.name || u.user_id)}</span></span><span class="text-[12.5px] font-bold ${u.n ? 'text-slate-800' : 'text-slate-300'}">${nf(u.n)}</span></div>
              <div class="flex items-center gap-2 mt-1 pl-[18px]"><div class="flex-1 h-1 rounded-full bg-slate-100 overflow-hidden"><div class="h-full rounded-full bg-sky-500" style="width:${Math.round(u.n * 100 / maxN)}%"></div></div><span class="text-[10.5px] text-slate-400 whitespace-nowrap">${u.last ? esc(ago(u.last)) : 'ไม่เคยเข้า'}</span></div>
            </button>
          </div>`).join('') : '<div class="text-xs text-slate-400 py-4">ไม่มีผู้ใช้ในหน่วยงานนี้</div>';
        $('am-users').addEventListener('click', e => { const b = e.target.closest('.am-user'); if (b) toggleUserLog(b); });
        if (window.lucide) lucide.createIcons();
      } catch (e) { body.innerHTML = `<div class="py-10 text-center text-red-500 text-sm">${esc(e.message)}</div>`; }
      return;
    }
    body.innerHTML = '<div class="py-10 text-center text-slate-400 text-sm">กำลังโหลด...</div>';
    try {
      const r = listCache[tab] || (listCache[tab] = await (await fetch(`handle_agency_monitor.php?action=list&type=${tab}&ag=${a.ag_id}&days=${days}`)).json());
      if (!r.success) throw new Error(r.error || 'โหลดไม่สำเร็จ');
      if (cur !== a) return;
      const showSt = ['repair', 'pm', 'stock', 'data'].includes(tab);
      body.innerHTML = r.rows.length ? `<div class="flex flex-col h-full min-h-0"><div class="flex items-center justify-between mb-2 flex-none"><div class="text-[12px] text-slate-500">${nf(r.rows.length)} รายการ${r.rows.length >= r.limit ? ' (แสดง ' + r.limit + ' รายการล่าสุด)' : ''} · ${days} วันล่าสุด</div>
          <input type="search" id="am-lq" placeholder="ค้นหาในรายการ" class="input-clean !py-1 !text-[12px] w-48"></div>
        <div class="am-scroll border border-slate-200 rounded-lg overflow-auto flex-1 min-h-0"><table class="am-list w-full"><thead><tr><th class="w-[130px]">วันเวลา</th><th>รายการ</th>${showSt ? '<th class="w-[110px]">สถานะ</th>' : ''}<th class="w-[170px]">โดย</th></tr></thead><tbody>
        ${r.rows.map(x => `<tr data-s="${esc((x.title + ' ' + x.sub + ' ' + x.user_name).toLowerCase())}"><td class="whitespace-nowrap text-slate-500">${esc(thDT(x.t))}</td>
          <td><div class="text-slate-800 break-words">${esc(x.title)}</div>${x.sub ? `<div class="text-[11px] text-slate-400">${esc(x.sub)}</div>` : ''}${x.done_at ? `<div class="text-[11px] text-emerald-600">ปิดงาน ${esc(thDT(x.done_at))}</div>` : ''}</td>
          ${showSt ? `<td>${stBadge(x.status)}</td>` : ''}<td class="text-slate-600">${esc(x.user_name || '-')}</td></tr>`).join('')}</tbody></table></div></div>`
        : '<div class="py-12 text-center text-slate-400 text-sm">ไม่มีข้อมูลในช่วงนี้</div>';
      const lq = $('am-lq');
      if (lq) lq.addEventListener('input', () => { const q = lq.value.trim().toLowerCase(); body.querySelectorAll('tbody tr').forEach(tr => tr.classList.toggle('hidden', !!q && !tr.dataset.s.includes(q))); });
    } catch (e) { body.innerHTML = `<div class="py-10 text-center text-red-500 text-sm">${esc(e.message)}</div>`; }
  }

  // ---------- Export Excel (ExcelJS โหลดเมื่อกดครั้งแรก) ----------
  let excelReady = null;
  function loadExcel() {
    if (window.ExcelJS) return Promise.resolve();
    return excelReady || (excelReady = new Promise((ok, fail) => {
      const sc = document.createElement('script');
      sc.src = 'https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js';
      sc.onload = ok; sc.onerror = () => { excelReady = null; fail(new Error('โหลดตัวสร้างไฟล์ Excel ไม่สำเร็จ')); };
      document.head.appendChild(sc);
    }));
  }
  // sheets: [{ name, title, columns: [[หัวคอลัมน์, กว้าง]], rows: [[...]] }]
  async function saveXlsx(filename, sheets) {
    await loadExcel();
    const wb = new ExcelJS.Workbook();
    wb.creator = 'EasyPro';
    sheets.forEach(sh => {
      const ws = wb.addWorksheet(sh.name.replace(/[\\/?*\[\]:]/g, '-').slice(0, 31));
      let r0 = 1;
      if (sh.title) {
        ws.mergeCells(1, 1, 1, sh.columns.length);
        Object.assign(ws.getCell(1, 1), { value: sh.title });
        ws.getCell(1, 1).font = { bold: true, size: 13 };
        r0 = 3;
      }
      const hr = ws.getRow(r0);
      sh.columns.forEach((c, i) => {
        const cell = hr.getCell(i + 1);
        cell.value = c[0];
        cell.font = { bold: true, color: { argb: 'FFFFFFFF' } };
        cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF005E8E' } };
        cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
        ws.getColumn(i + 1).width = c[1] || 14;
      });
      hr.height = 22;
      sh.rows.forEach(row => ws.addRow(row));
      ws.views = [{ state: 'frozen', ySplit: r0 }];
      if (sh.rows.length) ws.autoFilter = { from: { row: r0, column: 1 }, to: { row: r0 + sh.rows.length, column: sh.columns.length } };
    });
    const buf = await wb.xlsx.writeBuffer();
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob([buf], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }));
    link.download = filename;
    document.body.appendChild(link); link.click(); link.remove();
    setTimeout(() => URL.revokeObjectURL(link.href), 5000);
  }
  const ymd = () => new Date().toISOString().slice(0, 10);
  const dtx = s => s ? String(s).slice(0, 16) : '';   // วันเวลาในไฟล์: YYYY-MM-DD HH:mm (เรียง/กรองใน Excel ได้)
  const stName = st => (STATE[st] || ['-'])[0];

  // สรุปทุกหน่วยงาน (ตามตัวกรอง/ค้นหา/เรียงที่แสดงอยู่)
  async function exportSummary() {
    const rows = currentRows();
    if (!rows.length) { Swal.fire({ icon: 'info', title: 'ไม่มีข้อมูลให้ส่งออก', confirmButtonColor: '#005e8e' }); return; }
    const btn = $('am-export'); btn.disabled = true;
    try {
      await saveXlsx(`monitor-agencies-${days}d-${ymd()}.xlsx`, [{
        name: 'สรุปหน่วยงาน', title: `Monitor หน่วยงาน — ความเคลื่อนไหว ${days} วันล่าสุด (ข้อมูล ณ ${new Date().toLocaleString('th-TH')})`,
        columns: [['หน่วยงาน', 36], ['รหัส', 16], ['สถานะ', 14], ['เคลื่อนไหวล่าสุด', 18], ['ความเคลื่อนไหวรวม', 12], ['เริ่มสัญญา', 12], ['สิ้นสุดสัญญา', 12], ['สัญญาหมด', 10],
          ['ผู้ใช้ทั้งหมด', 10], ['เข้าใช้งาน (ครั้ง)', 12], ['ผู้ใช้ที่เข้า', 10], ['แจ้งซ่อมใหม่', 11], ['ปิดงานซ่อม', 11], ['งานซ่อมค้าง', 11],
          ['PM ทำแล้ว', 10], ['PM ถึงกำหนด', 11], ['PM เลยกำหนดสะสม', 13], ['จดมิเตอร์', 10], ['สต็อก', 9], ['เครื่องจักร เพิ่ม', 12], ['เครื่องจักร แก้ไข', 12], ['เครื่องจักรทั้งหมด', 12], ['ตั้งค่า', 9]],
        rows: rows.map(a => [a.name, a.code, stName(a.state), dtx(a.last), total(a), a.start || '', a.end || '', a.expired ? 'หมดแล้ว' : '',
          a.users, a.login.n, a.login.users, a.repair.new, a.repair.closed, a.repair.backlog, a.pm.done, a.pm.due, a.pm.overdue,
          a.meter.n, a.stock.n, a.machine.added, a.machine.updated, a.machine.total, a.setting.n])
      }]);
    } catch (e) { Swal.fire({ icon: 'error', title: 'ส่งออกไม่สำเร็จ', text: e.message, confirmButtonColor: '#005e8e' }); }
    finally { btn.disabled = false; }
  }

  // ข้อมูลทั้งหมดของหน่วยงาน: สรุป + ทุกประเภท + ผู้ใช้งาน + ประวัติล็อกอิน
  async function exportAgency(a) {
    const btn = $('am-export-ag'); btn.disabled = true;
    const label = btn.innerHTML; btn.innerHTML = 'กำลังเตรียมไฟล์...';
    try {
      const get = async q => { const r = await (await fetch('handle_agency_monitor.php?' + q)).json(); if (!r.success) throw new Error(r.error || 'โหลดไม่สำเร็จ'); return r; };
      const base = `ag=${a.ag_id}&days=${days}`;
      const [detail, login, repair, pm, meter, stock, data] = await Promise.all([
        get('action=detail&' + base), get('action=list&type=login&export=1&' + base), get('action=list&type=repair&export=1&' + base), get('action=list&type=pm&export=1&' + base),
        get('action=list&type=meter&export=1&' + base), get('action=list&type=stock&export=1&' + base), get('action=list&type=data&export=1&' + base)
      ]);
      const stTxt = s => (ST_LABEL[s] || [s || ''])[0];
      const listSheet = (name, r, withStatus, extra) => ({
        name, columns: [['วันเวลา', 18], ['รายการ', 60], ['รายละเอียด', 40]].concat(withStatus ? [['สถานะ', 14]] : []).concat(extra ? extra.cols : []).concat([['โดย', 26], ['Username', 18]]),
        rows: r.rows.map(x => [dtx(x.t), x.title, x.sub || ''].concat(withStatus ? [stTxt(x.status)] : []).concat(extra ? extra.val(x) : []).concat([x.user_name || '', x.user || '']))
      });
      const lastLogin = {};
      detail.users.forEach(u => { lastLogin[u.user_id] = u.last; });
      await saveXlsx(`monitor-${(a.code || a.ag_id)}-${days}d-${ymd()}.xlsx`, [
        { name: 'สรุป', title: `${a.name} (${a.code || '-'}) — ${days} วันล่าสุด`, columns: [['หัวข้อ', 30], ['ค่า', 26]], rows: [
          ['สถานะ', stName(a.state)], ['เคลื่อนไหวล่าสุด', dtx(a.last)], ['สัญญา', (a.start || '-') + ' ถึง ' + (a.end || '-') + (a.expired ? ' (หมดแล้ว)' : '')],
          ['ผู้ใช้ทั้งหมด (คน)', a.users], ['เข้าใช้งาน (ครั้ง)', a.login.n], ['ผู้ใช้ที่เข้าใช้งาน (คน)', a.login.users],
          ['แจ้งซ่อมใหม่', a.repair.new], ['ปิดงานซ่อม', a.repair.closed], ['งานซ่อมค้าง', a.repair.backlog],
          ['PM ทำแล้ว', a.pm.done], ['PM ถึงกำหนดในช่วงนี้', a.pm.due], ['PM เลยกำหนดสะสม', a.pm.overdue],
          ['จดมิเตอร์', a.meter.n], ['สต็อก (รายการ)', a.stock.n], ['เครื่องจักร เพิ่ม / แก้ไข', a.machine.added + ' / ' + a.machine.updated], ['เครื่องจักรทั้งหมด', a.machine.total], ['ตั้งค่า (สร้าง/แก้ไข)', a.setting.n]] },
        { name: 'รายวัน', columns: [['วันที่', 12]].concat(SERIES.map(sr => [sr[1], 12])), rows: detail.days.map((d, i) => [d].concat(SERIES.map(sr => detail.daily[sr[0]][i]))) },
        { name: 'ผู้ใช้งาน', columns: [['ชื่อ', 30], ['Username', 20], ['ระดับ', 12], ['ตำแหน่ง', 18], ['เข้าใช้งานในช่วงนี้ (ครั้ง)', 14], ['เข้าล่าสุด', 18]],
          rows: detail.users.map(u => [u.name || '', u.user_id, u.user_level || '', u.user_position || '', u.n, dtx(u.last)]) },
        { name: 'ประวัติล็อกอิน', columns: [['วันที่', 12], ['เวลา', 10], ['ชื่อ', 30], ['Username', 20]],
          rows: login.rows.map(x => [String(x.t).slice(0, 10), String(x.t).slice(11, 19), x.user_name || '', x.user || '']) },
        listSheet('แจ้งซ่อม', repair, true, { cols: [['ปิดงานเมื่อ', 18]], val: x => [dtx(x.done_at)] }),
        listSheet('PM', pm, true), listSheet('มิเตอร์', meter, false), listSheet('สต็อก', stock, true), listSheet('ข้อมูล-ตั้งค่า', data, true)
      ]);
    } catch (e) { alert('ส่งออกไม่สำเร็จ: ' + e.message); }   // ไม่ใช้ Swal เพราะจะปิดหน้ารายละเอียด
    finally { btn.disabled = false; btn.innerHTML = label; if (window.lucide) lucide.createIcons(); }
  }

  function openDetail(agId, tab) {
    const a = DATA.find(x => x.ag_id === agId); if (!a) return;
    cur = a; listCache = {};
    const st = STATE[a.state], cnt = tabCounts(a);
    const tabs = [['overview', 'ภาพรวม', 'layout-dashboard', null]].concat(METRICS.map(m => [m[0], m[2], m[1], cnt[m[0]]]));
    Swal.fire({
      width: '66rem', showConfirmButton: false, showCloseButton: true, padding: 0, customClass: { popup: 'rounded-2xl overflow-hidden' },
      html: `<div class="text-left">
        <div class="px-6 pt-5 pb-3">
          <div class="flex items-center gap-2 pr-8"><span class="am-dot" style="background:${st[1]}"></span><h3 class="text-[17px] font-bold text-slate-800 truncate">${esc(a.name)}</h3><span class="text-[12px] font-semibold" style="color:${st[1]}">${st[0]}</span>
            <button type="button" id="am-export-ag" class="ml-auto h-8 px-3 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 text-xs font-bold flex items-center gap-1.5 hover:bg-emerald-100 flex-none" title="ส่งออกข้อมูลทั้งหมดของหน่วยงานนี้">${ico('file-spreadsheet', 'w-3.5 h-3.5')} Export Excel</button></div>
          <div class="text-[12px] text-slate-500 mt-0.5 pl-4">${esc(a.code || '')} · สัญญา ${esc(thD(a.start))} – ${esc(thD(a.end))}${a.expired ? ' (หมดแล้ว)' : ''} · เคลื่อนไหวล่าสุด ${esc(thDT(a.last))}</div>
          <div id="am-tabs" class="flex gap-1 mt-4 overflow-x-auto pb-1">${tabs.map(t => `<button type="button" class="am-tab" data-tab="${t[0]}">${ico(t[2], 'w-3.5 h-3.5')}${t[1]}${t[3] != null ? `<span class="n">${nf(t[3])}</span>` : ''}</button>`).join('')}</div>
        </div>
        <div id="am-tabbody" class="px-6 pb-5 pt-4 border-t border-slate-100 overflow-y-auto lg:overflow-hidden" style="height: min(580px, calc(100vh - 230px))"></div>
      </div>`,
      didOpen: () => {
        fitDetail();
        window.addEventListener('resize', fitDetail);
        if (window.lucide) lucide.createIcons();
        $('am-tabs').addEventListener('click', e => { const b = e.target.closest('[data-tab]'); if (b) showTab(b.dataset.tab); });
        $('am-export-ag').addEventListener('click', () => exportAgency(a));
        showTab(tab || 'overview');
      },
      willClose: () => { window.removeEventListener('resize', fitDetail); closeUserPanel(); if (detailChart) { detailChart.destroy(); detailChart = null; } cur = null; }
    });
  }

  $('am-days').addEventListener('click', e => {
    const b = e.target.closest('[data-days]'); if (!b) return;
    days = +b.dataset.days;
    document.querySelectorAll('#am-days button').forEach(x => x.classList.toggle('on', x === b));
    load();
  });
  $('am-filters').addEventListener('click', e => { const b = e.target.closest('[data-filter]'); if (b) { filter = b.dataset.filter; render(); } });
  $('am-q').addEventListener('input', render);
  $('am-sort').addEventListener('change', e => { sortKey = e.target.value; render(); });
  $('am-reload').addEventListener('click', load);
  $('am-export').addEventListener('click', exportSummary);
  // กดตัวเลข => เปิดรายละเอียดที่แท็บข้อมูลนั้น · กดแถว => ภาพรวม
  $('am-list').addEventListener('click', e => {
    const h = e.target.closest('.am-head [data-sort]');
    if (h) { sortKey = h.dataset.sort; const sel = $('am-sort'); if ([...sel.options].some(o => o.value === sortKey)) sel.value = sortKey; render(); return; }
    const el = e.target.closest('[data-ag][data-tab]'); if (el) openDetail(+el.dataset.ag, el.dataset.tab);
  });
})();
</script>
<?php } ?>
</body>
</html>