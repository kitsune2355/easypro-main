/**
 * Location Selector Module
 * UI ของ #spatial_modal คงเดิม 100% พร้อมระบบจัดการไอคอน Lucide
 */

const LocationSelector = {
    spatialDB: [],
    currentStep: 1,
    selection: { bld: null, flr: null, rm: null },

    init: function() {
        this.injectStyles();
        this.injectModal();
        this.bindEvents();
        this.safeIcons();
    },

    // ✅ ฟังก์ชันสำหรับเปิด Modal (ใช้ชื่อนี้ใน onclick)
    openModal: async function() {
        const m = document.getElementById('spatial_modal');
        if (!m) return;
        
        m.classList.remove('hidden');
        setTimeout(() => m.classList.remove('opacity-0'), 10);
        
        // แสดง Loading ระหว่างโหลดข้อมูล
        document.getElementById('view_building').innerHTML = `
            <div class="col-span-full flex flex-col items-center justify-center py-20">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                <p class="mt-4 text-slate-400">กำลังโหลดข้อมูล...</p>
            </div>`;
        
        await this.loadSpatialDB();
        this.renderBuildings();
    },

    loadSpatialDB: async function() {
        try {
            const res = await fetch('get_building_all.php', { cache: 'no-store' });
            const json = await res.json();
            if (json.ok && Array.isArray(json.data)) {
                this.spatialDB = json.data;
            } else if (Array.isArray(json.building)) {
                this.spatialDB = json.building.map(b => ({
                    id: b.area_id, 
                    name: b.area_name,
                    floors: (b.floor || []).map(f => ({
                        id: f.ac_id, 
                        name: f.ac_name,
                        rooms: (f.room || []).map(r => ({ id: r.ar_id, name: r.ar_name }))
                    }))
                }));
            }
        } catch (err) { 
            console.error('Load Error:', err); 
        }
    },

    renderBuildings: function() {
        this.currentStep = 1;
        this.updateModalUI('เลือกสาขา / อาคาร', 33, false);
        const container = document.getElementById('view_building');
        
        container.innerHTML = (this.spatialDB || []).map(b => {
            const floorsCount = (b.floors || []).length;
            return `
              <div onclick="LocationSelector.selectBuilding('${b.id}', this)" class="building-card group">
                <div class="flex items-start justify-between">
                  <div class="bld-icon"><i data-lucide="building-2" class="w-5 h-5"></i></div>
                  <span class="bld-badge">
                    <i data-lucide="layers" class="w-4 h-4"></i>
                    <span>${floorsCount} ชั้น</span>
                  </span>
                </div>
                <div class="mt-4">
                  <div class="bld-title">${this.escapeHtml(b.name)}</div>
                  <div class="mt-1 bld-sub">แตะเพื่อเลือกสาขา/อาคาร</div>
                </div>
              </div>
            `;
        }).join('');

        this.switchView('view_building');
        this.safeIcons();
    },

    selectBuilding: function(id) {
        this.selection.bld = this.spatialDB.find(b => String(b.id) === String(id));
        this.selection.flr = null;
        this.selection.rm = null;
        this.updateStatusText();
        
        const floors = this.selection.bld?.floors || [];
        if (floors.length === 0) {
            this.setConfirmEnabled(true, "ยืนยันการเลือกอาคาร");
            if (typeof window.showSoftAlert === 'function') {
              window.showSoftAlert("อาคารนี้ไม่มีข้อมูลชั้น สามารถยืนยันได้ทันที", "success");
          }
        } else {
            this.setConfirmEnabled(false, "Confirm Selection");
            this.renderFloors();
        }
    },

    renderFloors: function() {
        this.currentStep = 2;
        this.updateModalUI(`เลือกชั้น - ${this.selection.bld.name}`, 66, true);
        const container = document.getElementById('view_floor');
        container.innerHTML = (this.selection.bld.floors || []).map(f => `
            <div onclick="LocationSelector.selectFloor('${f.id}')" class="floor-card group">
                <div class="flex items-start justify-between">
                    <div class="floor-icon"><i data-lucide="layers" class="w-5 h-5"></i></div>
                    <span class="room-badge"><i data-lucide="door-open" class="w-4 h-4"></i>${(f.rooms || []).length} ห้อง</span>
                </div>
                <div class="mt-4">
                    <div class="floor-no">${this.escapeHtml(f.name)}</div>
                </div>
                <div class="mt-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">แตะเพื่อเลือกชั้น</div>
            </div>
        `).join('');
        this.switchView('view_floor');
        this.safeIcons();
    },

    selectFloor: function(id) {
        this.selection.flr = this.selection.bld.floors.find(f => String(f.id) === String(id));
        this.selection.rm = null;
        this.updateStatusText();
        const rooms = this.selection.flr?.rooms || [];
        if (rooms.length === 0) {
            this.setConfirmEnabled(true, "ยืนยันการเลือกชั้น");
            if (typeof window.showSoftAlert === 'function') {
                window.showSoftAlert("ชั้นนี้ไม่มีข้อมูลห้อง สามารถยืนยันได้ทันที", "success");
            }
        } else {
            this.setConfirmEnabled(false, "Confirm Selection");
            this.renderRooms();
        }
    },

    renderRooms: function() {
        this.currentStep = 3;
        this.updateModalUI(`เลือกห้อง - ${this.selection.flr.name}`, 100, true);
        const container = document.getElementById('view_room');
        container.innerHTML = (this.selection.flr.rooms || []).map(r => `
            <div class="room-card" onclick="LocationSelector.selectRoom('${r.id}', '${this.escapeHtml(r.name)}')">
                <div class="rm-icon"><i data-lucide="door-open" class="w-5 h-5"></i></div>
                <div class="rm-title">${this.escapeHtml(r.name)}</div>
                <div class="rm-sub">แตะเพื่อเลือกห้อง</div>
            </div>
        `).join('');
        this.switchView('view_room');
        this.safeIcons();
    },

    selectRoom: function(id, name) {
        this.selection.rm = { id, name };
        this.updateStatusText();
        this.setConfirmEnabled(true, "ยืนยันตำแหน่งนี้");
    },

    closeModal: function() {
        const m = document.getElementById('spatial_modal');
        if (!m) return;
        m.classList.add('opacity-0');
        setTimeout(() => m.classList.add('hidden'), 300);
    },

    updateModalUI: function(title, progress, showBack) {
        document.getElementById('modal_title').innerText = title;
        document.getElementById('step_label').innerText = `Step ${this.currentStep}`;
        document.getElementById('step_bar').style.width = `${progress}%`;
        document.getElementById('btn_back').classList.toggle('hidden', !showBack);
    },

    switchView: function(viewId) {
        ['view_building', 'view_floor', 'view_room'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                if (id === viewId) {
                    el.classList.remove('hidden');
                    el.classList.add('animate-fade-up');
                } else {
                    el.classList.add('hidden');
                }
            }
        });
    },

    updateStatusText: function() {
        const setStat = (id, val, active) => {
            const el = document.getElementById(id);
            if (!el) return;
            el.innerText = val || '...';
            el.classList.toggle('text-primary', !!active);
            el.classList.toggle('opacity-50', !active);
        };
        setStat('stat_bld', this.selection.bld?.name, this.selection.bld);
        setStat('stat_flr', this.selection.flr?.name, this.selection.flr);
        setStat('stat_rm', this.selection.rm?.name, this.selection.rm);
    },

    setConfirmEnabled: function(enabled, label) {
        const btn = document.getElementById('btn_confirm');
        if (!btn) return;
        btn.disabled = !enabled;
        if (label) btn.innerText = label;
    },

    bindEvents: function() {
        const confirmBtn = document.getElementById('btn_confirm');
        if (confirmBtn) confirmBtn.onclick = () => this.confirmSelection();

        const backBtn = document.getElementById('btn_back');
        if (backBtn) backBtn.onclick = () => {
            if (this.currentStep === 3) this.renderFloors();
            else if (this.currentStep === 2) this.renderBuildings();
        };

        const closeBtn = document.getElementById('btn_close_modal');
        if (closeBtn) closeBtn.onclick = () => this.closeModal();

        const backdrop = document.getElementById('modal_backdrop');
        if (backdrop) backdrop.onclick = () => this.closeModal();

        const search = document.getElementById('spatial_search');
        if (search) search.oninput = (e) => this.filterList(e.target.value);
    },

    filterList: function(query) {
        const q = (query || "").toLowerCase().trim();
        const views = ['view_building', 'view_floor', 'view_room'];
        const activeViewId = views[this.currentStep - 1];
        const container = document.getElementById(activeViewId);
        if (!container) return;
        
        const items = container.children;
        for (let item of items) {
            item.style.display = item.innerText.toLowerCase().includes(q) ? '' : 'none';
        }
    },

    confirmSelection: function() {
        if (this.selection.bld) {
            const inBld = document.getElementById('in_building');
            if (inBld) inBld.value = this.selection.bld.id;
            
            const dispBld = document.getElementById('disp_building')?.querySelector('span');
            if (dispBld) dispBld.innerText = this.selection.bld.name;
        }
        if (this.selection.flr) {
            const inFlr = document.getElementById('in_floor');
            if (inFlr) inFlr.value = this.selection.flr.id;
            
            const dispFlr = document.getElementById('disp_floor');
            if (dispFlr) dispFlr.innerText = this.selection.flr.name;
        }
        if (this.selection.rm) {
            const inRm = document.getElementById('in_room');
            if (inRm) inRm.value = this.selection.rm.id;
            
            const dispRm = document.getElementById('disp_room');
            if (dispRm) dispRm.innerText = this.selection.rm.name;
        }
        
        document.getElementById('loc_placeholder')?.classList.add('hidden');
        document.getElementById('loc_selected')?.classList.remove('hidden');
        
        this.closeModal();
    },

    safeIcons: function() {
        const trigger = () => { if (window.lucide) window.lucide.createIcons(); };
        trigger();
        setTimeout(trigger, 100);
    },

    escapeHtml: (str) => String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])),

    injectStyles: function() {
        if (document.getElementById('ls-styles')) return;
        const s = document.createElement('style');
        s.id = 'ls-styles';
        s.innerHTML = `
            :root { --primary: #006B9F; }
            .modal-blur { backdrop-filter: blur(12px) saturate(180%); background-color: rgba(15, 23, 42, 0.4); }
            .btn-gradient { background: linear-gradient(to right, #006B9F, #04ADFF); color: white; border: none; }
            .step-indicator { height: 6px; border-radius: 999px; background: #f1f5f9; width: 120px; position: relative; overflow: hidden; }
            .step-progress { position: absolute; left: 0; top: 0; height: 100%; background: var(--primary); transition: width 0.4s ease; }
            .building-card { background: white; border: 2px solid #f1f5f9; border-radius: 2rem; padding: 1.5rem; cursor: pointer; transition: all 0.3s; }
            .building-card:hover { border-color: var(--primary); transform: translateY(-4px); box-shadow: 0 10px 20px -5px rgba(0,107,159,0.2); }
            .bld-icon { width: 48px; height: 48px; border-radius: 1rem; background: #f8fafc; display: flex; align-items: center; justify-content: center; color: var(--primary); }
            .bld-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; background: #f1f5f9; color: #64748b; }
            .bld-title { font-weight: 700; color: #1e293b; font-size: 1.1rem; }
            .bld-sub { font-size: 12px; color: #94a3b8; }
            .floor-card { background: white; border: 1.5px solid #f1f5f9; border-radius: 1.75rem; padding: 1.25rem; cursor: pointer; transition: all 0.2s; }
            .floor-card:hover { border-color: var(--primary); background: #f0f9ff; }
            @keyframes fade-up { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
            .animate-fade-up { animation: fade-up 0.4s ease forwards; }
            
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
  flex-direction: column;
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

        `;
        document.head.appendChild(s);
    },

    injectModal: function() {
        if (document.getElementById('spatial_modal')) return;
        const html = `
        <div id="spatial_modal" class="modal-blur fixed inset-0 z-[60] flex items-center justify-center hidden opacity-0 transition-all duration-300">
          <div id="modal_backdrop" class="absolute inset-0 bg-slate-900/40"></div>
          <div class="modal-panel w-[92vw] max-w-6xl h-[88vh] bg-white rounded-[3rem] shadow-2xl flex flex-col overflow-hidden relative z-10">
            <div class="p-8 pb-4 flex items-center justify-between shrink-0">
              <div class="flex items-center gap-4">
                <button id="btn_back" class="hidden w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center hover:bg-slate-100 transition-all">
                  <i data-lucide="arrow-left" class="w-5 h-5 text-slate-600"></i>
                </button>
                <div>
                  <h2 class="text-2xl font-bold text-slate-800 tracking-tight" id="modal_title">เลือกอาคาร</h2>
                  <div class="flex items-center gap-2 mt-1.5">
                    <span class="text-[10px] font-bold text-primary uppercase tracking-wider" id="step_label">Step 1</span>
                    <div class="step-indicator"><div id="step_bar" class="step-progress"></div></div>
                  </div>
                </div>
              </div>
              <button id="btn_close_modal" class="w-12 h-12 rounded-full border border-slate-100 flex items-center justify-center text-slate-400 hover:rotate-90 hover:bg-slate-50 transition-all duration-300">
                <i data-lucide="x" class="w-6 h-6"></i>
              </button>
            </div>
            <div class="px-8 mb-6 shrink-0">
              <div class="relative">
                <i data-lucide="search" class="absolute left-5 top-4 w-5 h-5 text-slate-400"></i>
                <input type="text" id="spatial_search" class="w-full bg-slate-50 rounded-2xl py-4 pl-14 pr-6 text-sm font-medium border-none outline-none focus:ring-2 focus:ring-primary/20" placeholder="ค้นหา...">
              </div>
            </div>
            <div class="flex-1 overflow-hidden relative px-8 pb-8">
              <div id="view_building" class="h-full overflow-y-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pb-20"></div>
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
              <button id="btn_confirm" disabled class="btn-gradient px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest disabled:opacity-50">
                Confirm Selection
              </button>
            </div>
          </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', html);
    }
};

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
  
  // เรียกใช้ safeIcons เพื่อแสดงไอคอน Lucide
  if (typeof LocationSelector !== 'undefined' && LocationSelector.safeIcons) {
      LocationSelector.safeIcons();
  } else if (window.lucide) {
      window.lucide.createIcons();
  }

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

document.addEventListener('DOMContentLoaded', () => LocationSelector.init());