


  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest" defer></script>

  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
 <style>
    html, body { height: 100%; }
    body {
      background: linear-gradient(135deg, #f0f4f8 0%, #e2e8f0 100%);
      color: #334155;
      margin: 0;
      padding: 0;
      overflow: hidden;
    }
    @media (max-width: 1024px) {
      body { overflow: auto; }
    }

    /* ✅ Fullscreen form */
    #repair_form{
      position: fixed;
      inset: 0;
      width: 100vw;
      height: 100vh;
    }

    .glass-panel {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.5);
      box-shadow: 0 20px 50px -12px rgba(0, 0, 0, 0.1);
    }

    .input-modern {
      width: 100%;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 0.75rem;
      padding: 0.5rem 0.85rem;
      font-size: 0.875rem;
      transition: all 0.2s;
    }
    .input-modern:focus {
      outline: none;
      border-color: #006B9F;
      background: #fff;
      box-shadow: 0 0 0 3px rgba(0, 107, 159, 0.1);
    }

    .label-head {
      font-size: 0.75rem;
      color: #64748b;
      font-weight: 600;
      margin-bottom: 0.35rem;
      display: block;
    }

    .btn-gradient {
      background: linear-gradient(to right, #006B9F, #04ADFF);
      color: white;
      transition: all 0.3s ease;
    }
    .btn-gradient:hover {
      box-shadow: 0 10px 20px -5px rgba(0, 107, 159, 0.4);
      transform: translateY(-1px);
    }

    /* Modal blur overlay */
    .modal-blur {
      backdrop-filter: blur(12px) saturate(180%);
      background-color: rgba(15, 23, 42, 0.4);
    }

    .location-trigger {
      width: 100%;
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 1.5rem;
      padding: 1rem;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      cursor: pointer;
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }
    .location-trigger:hover {
      border-color: rgba(0, 107, 159, 0.5);
      box-shadow: 0 10px 25px rgba(0, 107, 159, 0.05);
    }

    .location-card {
      display: flex;
      flex-direction: column;
      padding: 1.25rem;
      background: #fff;
      border: 1px solid #f1f5f9;
      border-radius: 1.75rem;
      transition: all 0.3s;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }
    .location-card:hover {
      box-shadow: 0 18px 35px rgba(0, 107, 159, 0.10);
      border-color: rgba(0, 107, 159, 0.30);
      transform: translateY(-2px);
    }
    .location-card.active {
      border-color: #006B9F;
      background: rgba(0, 107, 159, 0.05);
      box-shadow: 0 10px 20px rgba(0, 107, 159, 0.10);
      outline: 2px solid rgba(0, 107, 159, 0.10);
      outline-offset: 0px;
    }

    .step-indicator {
      height: 6px;
      border-radius: 9999px;
      background: #f1f5f9;
      overflow: hidden;
      position: relative;
      width: 128px;
    }
    .step-progress {
      position: absolute;
      inset: 0 auto 0 0;
      height: 100%;
      background: #006B9F;
      transition: width 0.5s ease-out;
      width: 33%;
    }

    .animate-fade-up { animation: fadeUp 0.4s ease-out forwards; }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .scrollbar-hide::-webkit-scrollbar { display: none; }

    .shake { animation: shake 0.35s ease-in-out; }
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-6px); }
      75% { transform: translateX(6px); }
    }
	 

/* ✅ Building card UI (ให้ธีมเดียวกับ floor-card) */
.building-card{
  border: 1px solid #e2e8f0;
  border-radius: 1.75rem;
  background: #fff;
  padding: 18px;
  cursor: pointer;
  transition: all .25s ease;
  min-height: 140px;
  box-shadow: 0 12px 22px rgba(2, 132, 199, 0.06);
}
.building-card:hover{
  transform: translateY(-2px);
  border-color: rgba(0,107,159,.35);
  box-shadow: 0 18px 30px rgba(0, 107, 159, 0.10);
}
.building-card .bld-icon{
  width: 44px; height: 44px;
  border-radius: 14px;
  display:flex; align-items:center; justify-content:center;
  background: rgba(0, 107, 159, 0.08);
  color: #006B9F;
  border: 1px solid rgba(0, 107, 159, 0.15);
}
.building-card .bld-title{
  font-size: 15px;
  font-weight: 900;
  color: #0f172a;
  line-height: 1.25;
  /* ✅ แสดงชื่อเต็ม ไม่ตัด ... */
  white-space: normal;
  overflow: visible;
  word-break: break-word;
}
.building-card .bld-sub{
  font-size: 12px;
  font-weight: 700;
  color:#64748b;
}
.building-card .bld-badge{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding: 6px 10px;
  border-radius: 999px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  font-size: 11px;
  font-weight: 800;
  color:#475569;
  white-space: nowrap;
}
.building-card.active{
  border-color: #006B9F;
  box-shadow: 0 18px 35px rgba(0, 107, 159, 0.18);
  outline: 2px solid rgba(0, 107, 159, 0.10);
}




/* ✅ Floor card UI */
.floor-card{
  border: 1px solid #e2e8f0;
  border-radius: 1.75rem;
  background: #fff;
  padding: 18px;
  cursor: pointer;
  transition: all .25s ease;
  min-height: 140px;
  box-shadow: 0 12px 22px rgba(2, 132, 199, 0.06);
}
.floor-card:hover{
  transform: translateY(-2px);
  border-color: rgba(0,107,159,.35);
  box-shadow: 0 18px 30px rgba(0, 107, 159, 0.10);
}
.floor-card .floor-icon{
  width: 44px; height: 44px;
  border-radius: 14px;
  display:flex; align-items:center; justify-content:center;
  background: rgba(0, 107, 159, 0.08);
  color: #006B9F;
  border: 1px solid rgba(0, 107, 159, 0.15);
}
.floor-card .floor-no{
  font-size: 32px;
  font-weight: 800;
  line-height: 1;
  color: #0f172a;
}
.floor-card .floor-name{
  font-size: 13px;
  font-weight: 800;
  color: #334155;
}
.floor-card .room-badge{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding: 6px 10px;
  border-radius: 999px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  font-size: 11px;
  font-weight: 800;
  color:#475569;
}
.floor-card.active{
  border-color: #006B9F;
  box-shadow: 0 18px 35px rgba(0, 107, 159, 0.18);
  outline: 2px solid rgba(0, 107, 159, 0.10);
}

/* ✅ Room card UI (ให้ธีมเดียวกับ floor card) */
.room-card{
  border: 1px solid #e2e8f0;
  border-radius: 1.75rem;
  background: #fff;
  padding: 18px;
  cursor: pointer;
  transition: all .25s ease;
  min-height: 180px;
  display:flex;
  align-items:center;
  justify-content:center;
  text-align:center;
  box-shadow: 0 12px 22px rgba(2, 132, 199, 0.06);
}
.room-card:hover{
  transform: translateY(-2px);
  border-color: rgba(0,107,159,.35);
  box-shadow: 0 18px 30px rgba(0, 107, 159, 0.10);
}
.room-card .rm-icon{
  width: 46px; height: 46px;
  border-radius: 16px;
  display:flex; align-items:center; justify-content:center;
  background: rgba(0, 107, 159, 0.08);
  color: #006B9F;
  border: 1px solid rgba(0, 107, 159, 0.15);
  margin: 0 auto 10px auto;
}
.room-card .rm-title{
  font-size: 14px;
  font-weight: 900;
  color:#0f172a;
  line-height: 1.25;
  white-space: normal;      /* ✅ ชื่อเต็ม */
  word-break: break-word;
}
.room-card .rm-sub{
  margin-top: 6px;
  font-size: 11px;
  font-weight: 800;
  color:#64748b;
}
.room-card.active{
  border-color: #006B9F;
  background: rgba(0, 107, 159, 0.04);
  box-shadow: 0 18px 35px rgba(0, 107, 159, 0.18);
  outline: 2px solid rgba(0, 107, 159, 0.10);
}


	
  </style>
<!-- =========================
  ✅ LOCATION (LEFT PANEL)
========================= -->
<div class="space-y-4 pt-4 border-t border-slate-100">
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-2 text-primary">
      <i data-lucide="map-pin" class="w-4 h-4"></i>
      <h2 class="text-sm font-bold uppercase tracking-tight">สถานที่</h2>
    </div>
    <span id="loc_status" class="text-[10px] text-slate-400 font-bold uppercase hidden">Selected</span>
  </div>

  <p id="loc_error" class="hidden text-[11px] text-red-500 font-semibold">
    กรุณาเลือกสถานที่ (อาคาร/ชั้น/ห้อง) ก่อนกดยืนยัน
  </p>

  <div id="loc_trigger" onClick="openSpatialModal()" class="location-trigger">

    <!-- Placeholder -->
    <div id="loc_placeholder">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-2xl bg-primary/10 flex items-center justify-center text-primary shrink-0">
          <i data-lucide="map-pin" class="w-5 h-5"></i>
        </div>

        <div class="flex-1 min-w-0">
          <p class="text-xs font-extrabold text-slate-700">ระบุตำแหน่งที่แจ้งซ่อม</p>
          <p class="text-[10px] text-slate-400 font-semibold">กดเพื่อเลือก อาคาร / สาขา → ชั้น → ห้อง</p>
        </div>

        <div class="text-slate-300">
          <i data-lucide="chevron-right" class="w-5 h-5"></i>
        </div>
      </div>
    </div>

    <!-- Selected -->
    <div id="loc_selected" class="hidden">
      <div class="flex flex-col gap-2">
        <div class="flex items-center justify-between">
          <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1.5">
            <i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400"></i>
            Building
          </span>

          <span class="text-[10px] font-extrabold text-emerald-600 bg-emerald-50 border border-emerald-100 rounded-full px-2.5 py-1 inline-flex items-center gap-1.5">
            <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i>
            Selected
          </span>
        </div>

        <!-- ✅ ตรงนี้มีไอคอน + ชื่ออาคาร (ใส่ span ข้างในเพื่อ set innerText ได้) -->
        <span id="disp_building" class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
          <i data-lucide="map-pin" class="w-4 h-4 text-primary"></i>
          <span class="min-w-0 break-words"></span>
        </span>

        <div class="flex flex-wrap gap-2 mt-1">
          <span class="px-2 py-1 bg-blue-50 text-primary text-[10px] font-extrabold rounded-lg border border-blue-100 inline-flex items-center gap-1.5">
            <i data-lucide="layers" class="w-3.5 h-3.5"></i>
            <span id="disp_floor"></span>
          </span>

          <span id="disp_room"
            class="px-2 py-1 bg-slate-50 text-slate-600 text-[10px] font-extrabold rounded-lg border border-slate-200 inline-flex items-center gap-1.5">
            <i data-lucide="door-open" class="w-3.5 h-3.5"></i>
            ห้อง -
          </span>
        </div>
      </div>
    </div>

    <!-- Hidden inputs -->
    <input type="hidden" name="building" id="in_building" required>
    <input type="hidden" name="floor" id="in_floor" required>
    <input type="hidden" name="room" id="in_room" required>
  </div>
</div>


<!-- =========================
  ✅ LOCATION MODAL
========================= -->
<div id="spatial_modal" class="modal-blur fixed inset-0 z-50 flex items-center justify-center hidden opacity-0 transition-all duration-300">
  <div id="modal_backdrop" class="absolute inset-0"></div>

  <div class="modal-panel w-[92vw] max-w-6xl h-[88vh] bg-white rounded-[3rem] shadow-2xl flex flex-col overflow-hidden relative z-10">

    <div class="p-8 pb-4 flex items-center justify-between shrink-0">
      <div class="flex items-center gap-4">
        <button onClick="goBackStep()" id="btn_back"
          class="hidden w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center hover:bg-slate-100 transition-all">
          <i data-lucide="arrow-left" class="w-5 h-5 text-slate-600"></i>
        </button>

        <div>
          <h2 class="text-2xl font-bold text-slate-800 tracking-tight" id="modal_title">เลือกอาคาร</h2>
          <div class="flex items-center gap-2 mt-1.5">
            <span class="text-[10px] font-bold text-primary uppercase tracking-wider" id="step_label">Step 1</span>
            <div class="step-indicator">
              <div id="step_bar" class="step-progress"></div>
            </div>
          </div>
        </div>
      </div>

      <button onClick="closeSpatialModal()"
        class="w-12 h-12 rounded-full border border-slate-100 flex items-center justify-center text-slate-400 hover:rotate-90 hover:bg-slate-50 transition-all duration-300">
        <i data-lucide="x" class="w-6 h-6"></i>
      </button>
    </div>

    <div class="px-8 mb-6 shrink-0">
      <div class="relative">
        <i data-lucide="search" class="absolute left-5 top-4 w-5 h-5 text-slate-400"></i>
        <input type="text" id="spatial_search" onKeyUp="filterSpatialList()"
          class="w-full bg-slate-50 rounded-2xl py-4 pl-14 pr-6 text-sm font-medium border-none focus:ring-2 focus:ring-primary/20 transition-all outline-none"
          placeholder="ค้นหา...">
      </div>
    </div>

    <div class="flex-1 overflow-hidden relative px-8 pb-8">
      <div id="view_building" class="h-full overflow-y-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pb-20 animate-fade-up"></div>
      <div id="view_floor" class="hidden h-full overflow-y-auto grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 pb-20"></div>
      <div id="view_room" class="hidden h-full overflow-y-auto grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 pb-20"></div>
    </div>

    <div class="modal-footer absolute bottom-0 left-0 w-full h-24 bg-white/90 backdrop-blur-md border-t border-slate-100 px-8 flex items-center justify-between z-10">
      <div class="flex flex-col">
        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1">Current Selection</span>
        <div class="flex items-center gap-2 text-sm font-bold text-slate-800">
          <span id="stat_bld" class="opacity-50">...</span>
          <i data-lucide="chevron-right" class="w-3 h-3 text-slate-300"></i>
          <span id="stat_flr" class="opacity-50">...</span>
          <i data-lucide="chevron-right" class="w-3 h-3 text-slate-300"></i>
          <span id="stat_rm" class="opacity-50">...</span>
        </div>
      </div>

      <button id="btn_confirm" disabled onClick="confirmSelection()"
        class="btn-gradient px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-primary/20">
        Confirm Selection
      </button>
    </div>

  </div>
</div>


<!-- =========================
  ✅ CSS (เฉพาะสถานที่)
========================= -->
<style>
  .modal-blur {
    backdrop-filter: blur(12px) saturate(180%);
    background-color: rgba(15, 23, 42, 0.4);
  }

  .location-trigger {
    width: 100%;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 1.5rem;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
  }
  .location-trigger:hover {
    border-color: rgba(0, 107, 159, 0.5);
    box-shadow: 0 10px 25px rgba(0, 107, 159, 0.05);
  }

  .step-indicator {
    height: 6px;
    border-radius: 9999px;
    background: #f1f5f9;
    overflow: hidden;
    position: relative;
    width: 128px;
  }
  .step-progress {
    position: absolute;
    inset: 0 auto 0 0;
    height: 100%;
    background: #006B9F;
    transition: width 0.5s ease-out;
    width: 33%;
  }

  .animate-fade-up { animation: fadeUp 0.4s ease-out forwards; }
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .shake { animation: shake 0.35s ease-in-out; }
  @keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-6px); }
    75% { transform: translateX(6px); }
  }

  /* building/floor/room cards (ตามของคุณ) */
  .building-card,.floor-card,.room-card{
    border: 1px solid #e2e8f0;
    border-radius: 1.75rem;
    background: #fff;
    padding: 18px;
    cursor: pointer;
    transition: all .25s ease;
    box-shadow: 0 12px 22px rgba(2, 132, 199, 0.06);
  }
  .building-card{ min-height: 140px; }
  .floor-card{ min-height: 140px; }
  .room-card{ min-height: 180px; display:flex; align-items:center; justify-content:center; text-align:center; }

  .building-card:hover,.floor-card:hover,.room-card:hover{
    transform: translateY(-2px);
    border-color: rgba(0,107,159,.35);
    box-shadow: 0 18px 30px rgba(0, 107, 159, 0.10);
  }
  .building-card.active,.floor-card.active,.room-card.active{
    border-color: #006B9F;
    box-shadow: 0 18px 35px rgba(0, 107, 159, 0.18);
    outline: 2px solid rgba(0, 107, 159, 0.10);
  }

  .building-card .bld-icon,.floor-card .floor-icon,.room-card .rm-icon{
    width: 44px; height: 44px;
    border-radius: 14px;
    display:flex; align-items:center; justify-content:center;
    background: rgba(0, 107, 159, 0.08);
    color: #006B9F;
    border: 1px solid rgba(0, 107, 159, 0.15);
  }
  .room-card .rm-icon{ width:46px; height:46px; border-radius:16px; margin: 0 auto 10px auto; }

  .building-card .bld-title{ font-size:15px; font-weight:900; color:#0f172a; line-height:1.25; white-space:normal; word-break:break-word; }
  .building-card .bld-sub{ font-size:12px; font-weight:700; color:#64748b; }
  .building-card .bld-badge,.floor-card .room-badge{
    display:inline-flex; align-items:center; gap:6px;
    padding: 6px 10px; border-radius:999px;
    background:#f1f5f9; border:1px solid #e2e8f0;
    font-size:11px; font-weight:800; color:#475569;
    white-space:nowrap;
  }

  .floor-card .floor-no{ font-size:32px; font-weight:800; line-height:1; color:#0f172a; }
  .floor-card .floor-name{ font-size:13px; font-weight:800; color:#334155; }

  .room-card .rm-title{ font-size:14px; font-weight:900; color:#0f172a; line-height:1.25; white-space:normal; word-break:break-word; }
  .room-card .rm-sub{ margin-top:6px; font-size:11px; font-weight:800; color:#64748b; }
</style>


<!-- =========================
  ✅ JS (เฉพาะสถานที่)
========================= -->
<script>
/* ✅ lucide safe init */
function safeIcons() {
  if (window.lucide && typeof window.lucide.createIcons === 'function') {
    window.lucide.createIcons();
  }
}

/* ✅ toast (บน, สีเหลือง, slide down) */
function showSoftAlert(message, type = 'info') {
  const old = document.getElementById('soft_alert_top');
  if (old) old.remove();

  const toast = document.createElement('div');
  toast.id = 'soft_alert_top';
  toast.className = `
    fixed top-6 left-1/2 -translate-x-1/2 z-[9999]
    px-4 py-3 rounded-2xl shadow-lg
    text-sm font-bold flex items-center gap-2
    border bg-amber-50 text-amber-800 border-amber-200
  `;
  toast.innerHTML = `
    <span class="w-8 h-8 rounded-xl bg-white border border-amber-200
                 flex items-center justify-center text-amber-700 shrink-0">
      <i data-lucide="alert-triangle" class="w-4 h-4"></i>
    </span>
    <span class="leading-snug">${message}</span>
  `;

  toast.style.opacity = '0';
  toast.style.transform = 'translate(-50%, -16px)';
  document.body.appendChild(toast);
  safeIcons();

  requestAnimationFrame(() => {
    toast.style.transition = 'all 0.3s cubic-bezier(.2,.9,.2,1)';
    toast.style.opacity = '1';
    toast.style.transform = 'translate(-50%, 0)';
  });

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translate(-50%, -12px)';
    setTimeout(() => toast.remove(), 300);
  }, 2500);
}

/* =========================
   ✅ DATA + STATE
========================= */
let spatialDB = [];
let currentStep = 1;
let selection = { bld: null, flr: null, rm: null };

/* ✅ load data */
async function loadSpatialDB(){
  try{
    const res = await fetch('get_building_all.php', { cache: 'no-store' });
    const json = await res.json();

    if (json.ok && Array.isArray(json.data)) {
      spatialDB = json.data;
    } else if (Array.isArray(json.building)) {
      spatialDB = json.building.map(b => ({
        id: b.area_id,
        name: b.area_name,
        floors: (b.floor || []).map(f => ({
          id: f.ac_id,
          name: f.ac_name,
          rooms: (f.room || []).map(r => ({ id: r.ar_id, name: r.ar_name }))
        }))
      }));
    } else {
      throw new Error('รูปแบบ JSON ไม่ถูกต้อง');
    }
  }catch(err){
    console.error('loadSpatialDB error:', err);
    spatialDB = [];
  }
}

/* =========================
   ✅ MODAL OPEN/CLOSE
========================= */
async function openSpatialModal() {
  const m = document.getElementById('spatial_modal');
  m.classList.remove('hidden');
  requestAnimationFrame(() => m.classList.remove('opacity-0'));

  document.getElementById('spatial_search').value = '';
  const container = document.getElementById('view_building');
  container.innerHTML = `<div class="col-span-full text-center text-slate-400 font-bold py-10">กำลังโหลดข้อมูล...</div>`;

  await loadSpatialDB();

  if (!spatialDB.length) {
    container.innerHTML = `<div class="col-span-full text-center text-rose-500 font-bold py-10">ไม่พบข้อมูลอาคาร</div>`;
    return;
  }

  renderBuildings();
}

function closeSpatialModal() {
  const m = document.getElementById('spatial_modal');
  m.classList.add('opacity-0');
  setTimeout(() => m.classList.add('hidden'), 300);
}

/* =========================
   ✅ UI HELPERS
========================= */
function updateModalUI(title, progressPercent, showBack) {
  document.getElementById('modal_title').innerText = title;
  document.getElementById('step_label').innerText = `Step ${currentStep}`;
  document.getElementById('step_bar').style.width = `${progressPercent}%`;

  const btnBack = document.getElementById('btn_back');
  if (showBack) btnBack.classList.remove('hidden');
  else btnBack.classList.add('hidden');

  if (currentStep < 3) document.getElementById('btn_confirm').disabled = true;
}

function switchView(viewId) {
  ['view_building', 'view_floor', 'view_room'].forEach(id => {
    const el = document.getElementById(id);
    if (id === viewId) {
      el.classList.remove('hidden');
      el.classList.add('animate-fade-up');
      setTimeout(() => el.classList.remove('animate-fade-up'), 450);
    } else {
      el.classList.add('hidden');
      el.classList.remove('animate-fade-up');
    }
  });
}

function escapeHtml(str) {
  return String(str)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function setConfirmEnabled(enabled, label) {
  const btn = document.getElementById('btn_confirm');
  if (!btn) return;
  btn.disabled = !enabled;
  if (label) btn.textContent = label;
}

/* =========================
   ✅ RENDER + SELECT
========================= */
function renderBuildings() {
  currentStep = 1;
  updateModalUI('เลือกสาขา / อาคาร', 33, false);

  const container = document.getElementById('view_building');
  container.innerHTML = (spatialDB || []).map(b => {
    const floorsCount = (b.floors || []).length;
    return `
      <div onclick="selectBuilding('${b.id}', this)" class="building-card group">
        <div class="flex items-start justify-between">
          <div class="bld-icon"><i data-lucide="building-2" class="w-5 h-5"></i></div>
          <span class="bld-badge"><i data-lucide="layers" class="w-4 h-4"></i>${floorsCount} ชั้น</span>
        </div>
        <div class="mt-4">
          <div class="bld-title">${escapeHtml(b.name)}</div>
          <div class="mt-1 bld-sub">แตะเพื่อเลือกสาขา/อาคาร</div>
        </div>
      </div>
    `;
  }).join('');

  switchView('view_building');
  safeIcons();
  filterSpatialList();
}

function selectBuilding(id, el) {
  document.querySelectorAll('#view_building .building-card').forEach(x => x.classList.remove('active'));
  if (el) el.classList.add('active');

  selection.bld = spatialDB.find(b => String(b.id) === String(id));
  selection.flr = null;
  selection.rm  = null;

  updateStatusText();

  const floors = (selection.bld?.floors || []);
  if (floors.length === 0) {
    // ไม่มีชั้น -> confirm อาคารได้เลย
    currentStep = 1;
    updateModalUI(`เลือกสาขา / อาคาร`, 33, false);
    switchView('view_building');

    setConfirmEnabled(true, "Confirm Building");
    showSoftAlert("อาคารนี้ไม่มีข้อมูลชั้น สามารถยืนยันได้ทันที");
    return;
  }

  setConfirmEnabled(false, "Confirm Selection");
  setTimeout(renderFloors, 120);
}

function renderFloors() {
  currentStep = 2;
  updateModalUI(`เลือกชั้น (${selection.bld.name})`, 66, true);

  const container = document.getElementById('view_floor');
  container.innerHTML = (selection.bld.floors || []).map(f => {
    const roomCount = (f.rooms || []).length;
    return `
      <div onclick="selectFloor('${f.id}', this)" class="floor-card group">
        <div class="flex items-start justify-between">
          <div class="floor-icon"><i data-lucide="layers" class="w-5 h-5"></i></div>
          <span class="room-badge"><i data-lucide="door-open" class="w-4 h-4"></i>${roomCount} ห้อง</span>
        </div>
        <div class="mt-4">
          <div class="floor-no">${escapeHtml(f.id)}</div>
          <div class="mt-1 floor-name">${escapeHtml(f.name)}</div>
        </div>
        <div class="mt-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">แตะเพื่อเลือกชั้น</div>
      </div>
    `;
  }).join('');

  switchView('view_floor');
  safeIcons();
  filterSpatialList();
}

function selectFloor(id, el) {
  document.querySelectorAll('#view_floor .floor-card').forEach(x => x.classList.remove('active'));
  if (el) el.classList.add('active');

  selection.flr = (selection.bld?.floors || []).find(f => String(f.id) === String(id));
  selection.rm  = null;

  updateStatusText();

  const rooms = (selection.flr?.rooms || []);
  if (rooms.length === 0) {
    // ไม่มีห้อง -> confirm ชั้นได้เลย
    currentStep = 2;
    updateModalUI(`เลือกชั้น (${selection.bld.name})`, 66, true);
    switchView('view_floor');

    setConfirmEnabled(true, "Confirm Floor");
    showSoftAlert("ชั้นนี้ไม่มีข้อมูลห้อง สามารถยืนยันได้ทันที");
    return;
  }

  setConfirmEnabled(false, "Confirm Selection");
  setTimeout(renderRooms, 120);
}

function renderRooms() {
  currentStep = 3;
  updateModalUI(`เลือกห้อง (${selection.flr.name})`, 100, true);

  const container = document.getElementById('view_room');
  container.innerHTML = (selection.flr.rooms || []).map(r => `
    <div class="room-card" onclick="selectRoom(${r.id}, '${escapeHtml(r.name)}', this)">
      <div>
        <div class="rm-icon"><i data-lucide="door-open" class="w-5 h-5"></i></div>
        <div class="rm-title">${escapeHtml(r.name)}</div>
        <div class="rm-sub">แตะเพื่อเลือกห้อง</div>
      </div>
    </div>
  `).join('');

  switchView('view_room');
  safeIcons();
  filterSpatialList();
}

function selectRoom(id, name, el) {
  document.querySelectorAll('#view_room .room-card').forEach(x => x.classList.remove('active'));
  if (el) el.classList.add('active');

  selection.rm = { id, name };
  updateStatusText();
  setConfirmEnabled(true, "Confirm Selection");
}

/* =========================
   ✅ CONFIRM + BACK
========================= */
function confirmSelection() {
  if (!selection.bld) return;

  const floorName = selection.flr ? selection.flr.name : "-";
  const roomName  = selection.rm  ? selection.rm.name  : "-";

  document.getElementById('loc_placeholder').classList.add('hidden');
  document.getElementById('loc_selected').classList.remove('hidden');
  document.getElementById('loc_status').classList.remove('hidden');

  // ✅ set building name (span ด้านใน)
  const bEl = document.getElementById('disp_building');
  if (bEl) {
    const nameHolder = bEl.querySelector('span');
    if (nameHolder) nameHolder.innerText = selection.bld.name;
  }

  document.getElementById('disp_floor').innerText = floorName;
  document.getElementById('disp_room').innerText  = roomName;

  document.getElementById('in_building').value = selection.bld.id;
  document.getElementById('in_floor').value    = selection.flr ? selection.flr.id : "";
  document.getElementById('in_room').value     = selection.rm  ? selection.rm.id  : "";

  hideLocationError();
  closeSpatialModal();
}

function goBackStep() {
  if (currentStep === 3) renderFloors();
  else if (currentStep === 2) renderBuildings();
}

function updateStatusText() {
  const b = document.getElementById('stat_bld');
  const f = document.getElementById('stat_flr');
  const r = document.getElementById('stat_rm');

  b.innerText = selection.bld ? selection.bld.name : '...';
  b.className = selection.bld ? 'text-primary' : 'opacity-50';

  f.innerText = selection.flr ? selection.flr.name : '...';
  f.className = selection.flr ? 'text-primary' : 'opacity-50';

  r.innerText = selection.rm ? selection.rm.name : '...';
  r.className = selection.rm ? 'text-primary' : 'opacity-50';
}

function filterSpatialList() {
  const q = document.getElementById('spatial_search').value.toLowerCase().trim();
  const currentView = currentStep === 1 ? 'view_building' : (currentStep === 2 ? 'view_floor' : 'view_room');
  const items = document.getElementById(currentView).children;

  for (let item of items) {
    const text = item.innerText.toLowerCase();
    item.classList.toggle('hidden', !text.includes(q));
  }
}

/* =========================
   ✅ REQUIRED VALIDATION (submit)
========================= */
function initLocationRequired() {
  const form = document.getElementById('repair_form');
  const locError = document.getElementById('loc_error');
  const locTrigger = document.getElementById('loc_trigger');
  if (!form || !locError || !locTrigger) return;

  window.showLocationError = function () {
    locError.classList.remove('hidden');
    locTrigger.classList.add('shake');
    setTimeout(() => locTrigger.classList.remove('shake'), 400);
  }
  window.hideLocationError = function () {
    locError.classList.add('hidden');
  }

  form.addEventListener('submit', function (e) {
    const b = document.getElementById('in_building').value.trim();
    const f = document.getElementById('in_floor').value.trim();
    const r = document.getElementById('in_room').value.trim();

    const bObj = spatialDB.find(x => String(x.id) === String(b));
    const bHasFloors = (bObj?.floors || []).length > 0;

    let needFloor = bHasFloors;
    let needRoom  = false;

    if (needFloor) {
      const fObj = (bObj?.floors || []).find(x => String(x.id) === String(f));
      needRoom = ((fObj?.rooms || []).length > 0);
    }

    if (!b || (needFloor && !f) || (needRoom && !r)) {
      e.preventDefault();
      showLocationError();
      openSpatialModal();
    }
  });

  document.addEventListener('keydown', function (e) {
    const m = document.getElementById('spatial_modal');
    const isOpen = m && !m.classList.contains('hidden');
    if (isOpen && e.key === 'Escape') closeSpatialModal();
  });

  document.getElementById('modal_backdrop')?.addEventListener('click', closeSpatialModal);
}

/* ✅ init (เรียกหลัง DOM loaded) */
document.addEventListener('DOMContentLoaded', () => {
  safeIcons();
  initLocationRequired();
});
</script>
