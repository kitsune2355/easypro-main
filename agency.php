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

  <div class="flex gap-2 w-full max-w-md">
    <button onClick="switchTab('agency')" id="tab-agency-btn" class="tab-btn active flex-1 py-2 px-2 sm:px-4 rounded-lg text-xs font-bold flex items-center justify-center gap-2">
      <i data-lucide="building-2" class="w-3.5 h-3.5"></i> จัดการหน่วยงาน
    </button>
    <button onClick="switchTab('users')" id="tab-users-btn" class="tab-btn flex-1 py-2 px-2 sm:px-4 rounded-lg text-xs font-bold flex items-center justify-center gap-2">
      <i data-lucide="users" class="w-3.5 h-3.5"></i> ข้อมูลผู้ใช้
    </button>
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
    const agencyView = document.getElementById('view-agency');
    const usersView = document.getElementById('view-users');
    const agencyBtn = document.getElementById('tab-agency-btn');
    const usersBtn = document.getElementById('tab-users-btn');

    if (tabName === 'agency') {
      usersView.classList.add('hidden', 'opacity-0');
      usersView.classList.remove('flex');

      agencyView.classList.remove('hidden');
      agencyView.classList.add('flex');
      setTimeout(() => agencyView.classList.remove('opacity-0'), 20);

      agencyBtn.classList.add('active');
      usersBtn.classList.remove('active');
    } else {
      agencyView.classList.add('hidden', 'opacity-0');
      agencyView.classList.remove('flex');

      usersView.classList.remove('hidden');
      usersView.classList.add('flex');
      setTimeout(() => usersView.classList.remove('opacity-0'), 20);

      usersBtn.classList.add('active');
      agencyBtn.classList.remove('active');
    }
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

</body>
</html>