const LocationSelector = {
  spatialDB: [],
  currentStep: 1,
  selection: { bld: null, flr: null, rm: null },
  target: 'main',
  focus: 'building',

  init(){
    this.injectStyles();
    this.injectModal();
    this.bindEvents();
    this.safeIcons();
  },

  /* ================= OPEN MODAL ================= */
  openModal: async function(target='main', focus='building'){
    this.target = target;
    this.focus  = focus;

    const m = document.getElementById('spatial_modal');
    if(!m) return;

    m.classList.remove('hidden');
    setTimeout(()=>m.classList.remove('opacity-0'),10);

    document.getElementById('view_building').innerHTML = `
      <div class="col-span-full flex flex-col items-center justify-center py-20">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        <p class="mt-4 text-slate-400">กำลังโหลดข้อมูล...</p>
      </div>`;

    await this.loadSpatialDB();

    if(focus==='room' && this.selection.bld && this.selection.flr){
      this.renderRooms();
    }else if(focus==='floor' && this.selection.bld){
      this.renderFloors();
    }else{
      this.renderBuildings();
    }
  },

  /* ================= RESET LOGIC ================= */
  resetFrom(level){
    if(level==='building'){
      this.selection.flr = null;
      this.selection.rm  = null;
    }else if(level==='floor'){
      this.selection.rm = null;
    }
    this.updateStatusText();
    this.setConfirmEnabled(false,'Confirm Selection');
  },

  /* ================= LOAD DATA ================= */
  async loadSpatialDB(){
    const res = await fetch('get_building_all.php',{cache:'no-store'});
    const json = await res.json();

    this.spatialDB = (json.data || json.building || []).map(b=>({
      id: b.area_id ?? b.id,
      name: b.area_name ?? b.name,
      floors:(b.floor||b.floors||[]).map(f=>({
        id:f.ac_id ?? f.id,
        name:f.ac_name ?? f.name,
        rooms:(f.room||f.rooms||[]).map(r=>({
          id:r.ar_id ?? r.id,
          name:r.ar_name ?? r.name
        }))
      }))
    }));
  },

  /* ================= RENDER ================= */
  renderBuildings(){
    this.currentStep = 1;
    this.updateModalUI('เลือกอาคาร',33,false);

    const c = document.getElementById('view_building');
    c.innerHTML = this.spatialDB.map(b=>`
      <div class="building-card" onclick="LocationSelector.selectBuilding('${b.id}')">
        <div class="bld-title">${this.escapeHtml(b.name)}</div>
        <div class="bld-sub">${(b.floors||[]).length} ชั้น</div>
      </div>
    `).join('');

    this.switchView('view_building');
    this.safeIcons();
  },

  selectBuilding(id){
    this.selection.bld = this.spatialDB.find(b=>String(b.id)===String(id));
    this.selection.flr = null;
    this.selection.rm  = null;
    this.updateStatusText();

    if(!(this.selection.bld?.floors||[]).length){
      this.setConfirmEnabled(true,'ยืนยันอาคาร');
    }else{
      this.renderFloors();
    }
  },

  renderFloors(){
    this.currentStep = 2;
    this.updateModalUI(`เลือกชั้น - ${this.selection.bld.name}`,66,true);

    const c = document.getElementById('view_floor');
    c.innerHTML = this.selection.bld.floors.map(f=>`
      <div class="floor-card" onclick="LocationSelector.selectFloor('${f.id}')">
        <div class="floor-no">${this.escapeHtml(f.name)}</div>
      </div>
    `).join('');

    this.switchView('view_floor');
    this.safeIcons();
  },

  selectFloor(id){
    this.selection.flr = this.selection.bld.floors.find(f=>String(f.id)===String(id));
    this.selection.rm  = null;
    this.updateStatusText();

    if(!(this.selection.flr?.rooms||[]).length){
      this.setConfirmEnabled(true,'ยืนยันชั้น');
    }else{
      this.renderRooms();
    }
  },

  renderRooms(){
    this.currentStep = 3;
    this.updateModalUI(`เลือกห้อง - ${this.selection.flr.name}`,100,true);

    const c = document.getElementById('view_room');
    c.innerHTML = this.selection.flr.rooms.map(r=>`
      <div class="room-card" onclick="LocationSelector.selectRoom('${r.id}','${this.escapeHtml(r.name)}')">
        <div class="rm-title">${this.escapeHtml(r.name)}</div>
      </div>
    `).join('');

    this.switchView('view_room');
    this.safeIcons();
  },

  selectRoom(id,name){
    this.selection.rm = {id,name};
    this.updateStatusText();
    this.setConfirmEnabled(true,'ยืนยันตำแหน่งนี้');
  },

  /* ================= CONFIRM ================= */
  confirmSelection(){
    const map = (this.target==='edit') ? {
      inBld:'ed_building', inFlr:'ed_floor', inRm:'ed_room',
      dB:'disp_building_edit', dF:'disp_floor_edit', dR:'disp_room_edit',
      ph:'loc_placeholder_edit', sel:'loc_selected_edit'
    } : {};

    if(this.selection.bld){
      document.getElementById(map.inBld).value = this.selection.bld.id;
      document.getElementById(map.dB).innerText = this.selection.bld.name;
    }
    if(this.selection.flr){
      document.getElementById(map.inFlr).value = this.selection.flr.id;
      document.getElementById(map.dF).innerText = this.selection.flr.name;
    }else document.getElementById(map.dF).innerText='-';

    if(this.selection.rm){
      document.getElementById(map.inRm).value = this.selection.rm.id;
      document.getElementById(map.dR).innerText = this.selection.rm.name;
    }else document.getElementById(map.dR).innerText='-';

    document.getElementById(map.ph)?.classList.add('hidden');
    document.getElementById(map.sel)?.classList.remove('hidden');

    this.closeModal();
  },

  /* ================= UTILS ================= */
  updateStatusText(){
    const s=(id,v)=>{const e=document.getElementById(id); if(e)e.innerText=v||'...';};
    s('stat_bld',this.selection.bld?.name);
    s('stat_flr',this.selection.flr?.name);
    s('stat_rm', this.selection.rm?.name);
  },

  setConfirmEnabled(en,label){
    const b=document.getElementById('btn_confirm');
    if(b){b.disabled=!en; if(label)b.innerText=label;}
  },

  closeModal(){
    const m=document.getElementById('spatial_modal');
    m.classList.add('opacity-0');
    setTimeout(()=>m.classList.add('hidden'),300);
  },

  switchView(id){
    ['view_building','view_floor','view_room'].forEach(v=>{
      document.getElementById(v)?.classList.toggle('hidden',v!==id);
    });
  },

 bindEvents(){
  const btnConfirm = document.getElementById('btn_confirm');
  const btnBack    = document.getElementById('btn_back');
  const btnClose   = document.getElementById('btn_close_modal');

  // ✅ กัน null (modal ยังไม่ถูกสร้าง / id ไม่ตรง)
  if(!btnConfirm || !btnBack || !btnClose){
    console.warn('[LocationSelector] modal buttons not found (skip bind)');
    return;
  }

  // ✅ กัน bind ซ้ำ
  if(btnConfirm.dataset.bound === '1') return;
  btnConfirm.dataset.bound = '1';

  btnConfirm.addEventListener('click', () => this.confirmSelection());
  btnBack.addEventListener('click', () => {
    if(this.currentStep === 3) this.renderFloors();
    else this.renderBuildings();
  });
  btnClose.addEventListener('click', () => this.closeModal());
},

injectStyles(){
  if(document.getElementById('ls_styles')) return;

  const s = document.createElement('style');
  s.id = 'ls_styles';
  s.textContent = `
    :root{ --ls-primary: #006B9F; }

    #spatial_modal{ font-family: 'Kanit', sans-serif; }
    .ls-card-grid{
      display:grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap:12px;
    }
    @media (max-width: 900px){
      .ls-card-grid{ grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 520px){
      .ls-card-grid{ grid-template-columns: repeat(1, minmax(0, 1fr)); }
    }

    .building-card,.floor-card,.room-card{
      border:1px solid #e2e8f0;
      background:#fff;
      border-radius:18px;
      padding:14px;
      cursor:pointer;
      transition:.15s;
      box-shadow: 0 6px 20px rgba(2,8,23,.04);
      user-select:none;
    }
    .building-card:hover,.floor-card:hover,.room-card:hover{
      transform: translateY(-1px);
      border-color: rgba(0,107,159,.35);
      box-shadow: 0 14px 30px rgba(2,8,23,.10);
    }
    .bld-title{ font-weight:900; color:#0f172a; font-size:13px; line-height:1.2; }
    .bld-sub{ margin-top:6px; font-size:11px; font-weight:800; color:#64748b; }

    .floor-no{ font-weight:900; color:#0f172a; font-size:14px; text-align:center; }
    .rm-title{ font-weight:900; color:#0f172a; font-size:12px; line-height:1.2; }

    .ls-pill{
      display:inline-flex; align-items:center; gap:6px;
      padding:6px 10px; border-radius:999px;
      background:#f1f5f9; border:1px solid #e2e8f0;
      font-size:11px; font-weight:900; color:#0f172a;
    }
    .ls-progress{
      width:100%; height:8px; border-radius:999px; overflow:hidden;
      background:#e2e8f0;
    }
    .ls-progress > div{
      height:100%;
      background: linear-gradient(to right, #006B9F, #04ADFF);
      width:33%;
      transition: width .2s ease;
    }
  `;
  document.head.appendChild(s);
},

injectModal(){
  if(document.getElementById('spatial_modal')) return;

  const wrap = document.createElement('div');
  wrap.id = 'spatial_modal';
  wrap.className = 'hidden opacity-0 fixed inset-0 z-[10000] transition-opacity duration-200';

  wrap.innerHTML = `
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]"></div>

    <div class="relative mx-auto mt-6 w-[980px] max-w-[96vw]">
      <div class="bg-white rounded-3xl border border-slate-200 shadow-2xl overflow-hidden">

        <!-- Header -->
        <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="text-sm font-extrabold text-slate-800" id="ls_title">เลือกตำแหน่ง</div>
            <div class="mt-2 ls-progress"><div id="ls_progress_bar"></div></div>

            <div class="mt-3 flex flex-wrap gap-2">
              <span class="ls-pill">อาคาร: <span id="stat_bld">...</span></span>
              <span class="ls-pill">ชั้น: <span id="stat_flr">...</span></span>
              <span class="ls-pill">ห้อง: <span id="stat_rm">...</span></span>
            </div>
          </div>

          <button id="btn_close_modal" type="button"
            class="w-10 h-10 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 hover:rotate-90 transition flex items-center justify-center">
            ✕
          </button>
        </div>

        <!-- Body -->
        <div class="p-5">
          <div id="view_building" class="ls-card-grid"></div>
          <div id="view_floor" class="ls-card-grid hidden"></div>
          <div id="view_room" class="ls-card-grid hidden"></div>
        </div>

        <!-- Footer -->
        <div class="px-5 py-4 border-t border-slate-100 bg-white flex items-center justify-between">
          <button id="btn_back" type="button"
            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-[12px] font-extrabold hover:bg-slate-50">
            ย้อนกลับ
          </button>

          <button id="btn_confirm" type="button" disabled
            class="px-5 py-2 rounded-2xl text-white text-[12px] font-extrabold shadow-sm disabled:opacity-50"
            style="background: var(--ls-primary);">
            Confirm Selection
          </button>
        </div>

      </div>
    </div>
  `;

  document.body.appendChild(wrap);

  // ✅ คลิกพื้นหลังเพื่อปิด
  wrap.addEventListener('click', (e) => {
    if(e.target === wrap) this.closeModal();
    // หรือคลิกที่ backdrop
    if(e.target?.classList?.contains('bg-slate-900/40')) this.closeModal();
  });

  // ✅ สร้างแล้วค่อย bind
  this.bindEvents();
},


  safeIcons(){ window.lucide?.createIcons?.(); },
  escapeHtml:s=>String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])),

  injectStyles(){ /* ใช้ของคุณเดิม */ },
  injectModal(){ /* ใช้ของคุณเดิม */ }
};
document.addEventListener('DOMContentLoaded',()=>LocationSelector.init());