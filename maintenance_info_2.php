<?php
@session_start();
include "config_ctrl/checksession.php";
$ag_id = (int)($sess_user_agency_es ?? 0); // ✅ กันว่างแล้ว JS พัง
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Repair Requests - List</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/ag-grid-enterprise/dist/ag-grid-enterprise.min.js"></script> 
  <style>
    :root{
      --color-bg-light:#F0F4F8;
      --color-primary:#006B9F;
      --color-secondary:#04ADFF;

      --ag-accent-color:#006B9F;
      --ag-header-background-color:#f8fafc;
      --ag-row-hover-color:#f1f5f9;
      --ag-selected-row-color:#e2e8f0;
      --ag-font-family:'Kanit', sans-serif;
      --ag-font-size:13px;
    }
    body{ font-family:'Kanit',sans-serif; background:var(--color-bg-light); height:100vh; overflow:hidden; }
    .btn-gradient{ background:linear-gradient(to right,#006B9F,#04ADFF); color:#fff; transition:all .3s ease; }
    .btn-gradient:hover{ box-shadow:0 10px 20px -5px rgba(0,107,159,.4); transform:translateY(-1px); }
    .ag-theme-alpine{ --ag-border-color:#e2e8f0; --ag-border-radius:12px; width:100%; height:100%; }
    .ag-theme-alpine .ag-cell{ display:flex; align-items:center; }

    /* =========================
       ✅ Carousel (SweetAlert)
       ========================= */
    .swal-carousel-container{
      position: relative;
      width: 100%;
      height: 52vh;
      min-height: 360px;
      max-height: 680px;
      overflow: hidden;
      border-radius: 1.5rem;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
    }
    .carousel-item{
      display:none;
      width:100%;
      height:100%;
      padding:12px;
      animation: fadeIn .25s ease;
    }
    .carousel-item.active{
      display:flex;
      align-items:center;
      justify-content:center;
    }
    .rr-imgwrap{
      width:100%;
      height:100%;
      overflow:hidden;
      border-radius:14px;
      display:flex;
      align-items:center;
      justify-content:center;
      background:#fff;
      border:1px solid #e2e8f0;
      position:relative;
    }
    .rr-imgwrap img{
      max-width:100%;
      max-height:100%;
      object-fit:contain;
      border-radius:14px;
      background:#fff;
      user-select:none;
      -webkit-user-drag:none;
    }
    @keyframes fadeIn{ from{opacity:0;} to{opacity:1;} }

    .rr-grab{ cursor:grab; }
    .rr-grabbing{ cursor:grabbing; }

    .rr-dots{
      position:absolute;
      bottom:12px;
      left:50%;
      transform:translateX(-50%);
      display:flex;
      gap:6px;
      z-index:25;
      padding:6px 10px;
      border-radius:999px;
      background: rgba(255,255,255,.70);
      border:1px solid rgba(226,232,240,.9);
      backdrop-filter: blur(8px);
    }
    .rr-dot{
      width:8px; height:8px;
      border-radius:999px;
      cursor:pointer;
      transition:all .15s;
      background:#cbd5e1;
    }
    .rr-dot.active{ background:#0ea5e9; }
    .rr-dot:hover{ transform:scale(1.25); }

    .rr-toolbar{
      position:absolute;
      left:50%;
      bottom:56px;
      transform:translateX(-50%);
      z-index:40;
      display:flex;
      gap:6px;
      padding:6px 8px;
      border-radius:999px;
      background: rgba(255,255,255,.78);
      border:1px solid rgba(226,232,240,.9);
      box-shadow:0 10px 25px rgba(2,8,23,.10);
      backdrop-filter: blur(8px);
    }
    .rr-toolbtn{
      width:34px; height:34px;
      border-radius:12px;
      display:flex; align-items:center; justify-content:center;
      cursor:pointer;
      color:#334155;
      background: rgba(255,255,255,.92);
      border:1px solid rgba(226,232,240,.95);
      transition:all .15s;
    }
    .rr-toolbtn:hover{ transform:scale(1.06); background: rgba(255,255,255,.98); }
    .rr-toolsep{ width:1px; background: rgba(226,232,240,.9); margin:4px 2px; }
    .rr-zoomlabel{
      display:flex; align-items:center; justify-content:center;
      font-size:11px; font-weight:800;
      color:#0f172a;
      padding:0 8px;
      min-width:52px;
    }

    /* auto-hide */
    .rr-toolbar{
      opacity:0; pointer-events:none;
      transform:translateX(-50%) translateY(8px);
      transition:opacity .18s ease, transform .18s ease;
    }
    .rr-toolbar.rr-show{
      opacity:1; pointer-events:auto;
      transform:translateX(-50%) translateY(0);
    }
    .rr-dots.rr-autohide{ opacity:.85; transition:opacity .18s ease; }
    .rr-dots.rr-autohide.rr-hide{ opacity:.25; }

    /* =========================
       ✅ Drawer Tabs
       ========================= */
    .ud_tab{
      flex:1;
      display:flex; align-items:center; justify-content:center; gap:.5rem;
      padding:.55rem .6rem;
      border-radius:1rem;
      font-size:12px;
      font-weight:800;
      color:#64748b;
      transition:.2s;
    }
	.ud_panel{
  min-height: 0;
}
    .ud_tab:hover{ background:#fff; color:#0f172a; }
    .ud_tab.active{
      background:#fff;
      color:#006B9F;
      box-shadow:0 8px 18px rgba(2,132,199,.12);
      border:1px solid rgba(0,107,159,.15);
    }
	.swal2-container{ z-index:20000!important; }
	.swal2-popup{ z-index:20001!important; }
	#ud_drawer{ overscroll-behavior: contain; }
#ud_body_scroll{ scrollbar-width: thin; }
#ud_body_scroll::-webkit-scrollbar{ width: 10px; }
#ud_body_scroll::-webkit-scrollbar-thumb{
  background:#cbd5e1;
  border-radius:999px;
  border:3px solid #fff;
}
#ud_body_scroll::-webkit-scrollbar-track{ background: transparent; }

.urg-pill{
  display:flex;
  align-items:center;
  gap:.5rem;
  justify-content:center;
  padding:.6rem .75rem;
  border-radius:1rem;
  border:1px solid #e2e8f0;
  background:#f8fafc;
  font-weight:900;
  font-size:12px;
  color:#334155;
  transition:all .18s ease;
}
.urg-pill:hover{
  transform:translateY(-1px);
  border-color: rgba(0,107,159,.35);
  box-shadow: 0 10px 22px rgba(2, 132, 199, 0.10);
  background:#fff;
}
.urg-pill.urg-active{
  background: rgba(0, 107, 159, 0.08);
  border-color: rgba(0,107,159,.35);
  box-shadow: 0 12px 26px rgba(0, 107, 159, 0.12);
  color:#0f172a;
}
.urg-dot{ width:10px; height:10px; border-radius:999px; }
.urg-low{ background:#94a3b8; }
.urg-med{ background:#0ea5e9; }
.urg-high{ background:#f59e0b; }
.urg-crit{ background:#ef4444; }


  </style>
</head>

<body class="flex flex-col antialiased text-slate-700">

  <!-- Header -->
  <nav class="flex-none px-3 py-4 flex items-center justify-between border-b border-slate-200 bg-white z-30">
    <div class="flex items-center gap-3">
      <div class="p-2.5 bg-[--color-primary] rounded-xl shadow-lg text-white">
        <i data-lucide="wrench" class="w-6 h-6"></i>
      </div>
      <div>
        <h1 class="text-xl font-bold text-slate-900 leading-none">Repair Requests</h1>
        <p class="text-sm text-slate-500 mt-1">รายการแจ้งซ่อมทั้งหมด</p>
      </div>
    </div>

    <div class="flex items-center gap-2 md:gap-3">
      <button onClick="refreshGrid()" class="flex items-center gap-2 btn-gradient px-3 md:px-5 py-2.5 rounded-xl text-sm font-medium shadow-md">
        <i data-lucide="refresh-ccw" class="w-4 h-4"></i>
        <span class="hidden md:inline">รีเฟรช</span>
      </button>
    </div>
  </nav>

  <!-- Main -->
  <main class="flex-1 flex flex-col p-4 gap-4 overflow-hidden">
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 flex-none">
      <div class="relative w-full md:w-96">
        <i data-lucide="search" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input id="grid-search" type="text" oninput="onSearchChanged()"
               placeholder="ค้นหา... (ชื่อผู้แจ้ง/อาการ/อาคาร/สถานะ/ครุภัณฑ์)"
               class="w-full bg-white border border-slate-200 rounded-xl pl-11 pr-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-sky-500 outline-none transition-all">
      </div>

      <div class="flex gap-2 text-xs font-bold text-slate-500">
        <div class="bg-white border border-slate-200 rounded-xl px-4 py-2 flex items-center gap-2">
          <span id="row-count" class="text-sky-600 font-bold">0</span> รายการทั้งหมด
        </div>
      </div>
    </div>

    <div class="flex-1 overflow-hidden p-1 relative">
      <div id="myGrid" class="ag-theme-alpine w-full h-full"></div>
    </div>
  </main>

  <script>
    /* =========================
       ✅ Global / Utils
       ========================= */
    const AG_ID = <?php echo $ag_id; ?>;
    let gridApi;

    const safeIcons = () => window.lucide?.createIcons?.();

    const escapeHtml = (s) => (s ?? '').toString()
      .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
      .replaceAll('"','&quot;').replaceAll("'","&#039;");

    function formatThaiDate(ymd){
      if(!ymd) return '';
      const part = ymd.toString().trim().split(' ')[0];
      const m = part.match(/^(\d{4})-(\d{2})-(\d{2})$/);
      if(!m) return ymd;

      const y = parseInt(m[1],10);
      const mo = parseInt(m[2],10);
      const d = parseInt(m[3],10);

      const thMonths = ['', 'ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
      return `${d} ${thMonths[mo]} ${y + 543}`;
    }

    function formatThaiTime(hms){
      if(!hms) return '';
      const t = hms.toString().trim().slice(0,8);
      const mm = t.match(/^(\d{2}):(\d{2})(?::(\d{2}))?$/);
      if(!mm) return hms;
      return `${mm[1]}:${mm[2]} น.`;
    }

    const getFilterParams = (columnName) => ({
      values: (params) => {
        fetch(`handle_repair_requests.php?action=get_filter_values&column=${columnName}&ag_id=${AG_ID}`)
          .then(r => r.json()).then(data => params.success(data))
          .catch(() => params.success([]));
      },
      refreshValuesOnOpen: true,
    });

    /* =========================
       ✅ AG Grid Date Component
       ========================= */
    class RRDateInput{
      init(params){
        this.params = params;
        this.eInput = document.createElement('input');
        this.eInput.type = 'date';
        this.eInput.className = 'ag-input-field-input ag-text-field-input';
        this.eInput.style.height = '32px';
        this.eInput.style.minWidth = '160px';

        this.onChanged = () => this.params.onDateChanged();
        this.eInput.addEventListener('change', this.onChanged);
        this.eInput.addEventListener('input', this.onChanged);
      }
      getGui(){ return this.eInput; }
      getDate(){
        const v = this.eInput.value;
        if(!v) return null;
        const d = new Date(v + "T00:00:00");
        d.setHours(0,0,0,0);
        return d;
      }
      setDate(date){
        if(!date){ this.eInput.value = ''; return; }
        const yyyy = date.getFullYear();
        const mm = String(date.getMonth()+1).padStart(2,'0');
        const dd = String(date.getDate()).padStart(2,'0');
        this.eInput.value = `${yyyy}-${mm}-${dd}`;
      }
      destroy(){
        this.eInput.removeEventListener('change', this.onChanged);
        this.eInput.removeEventListener('input', this.onChanged);
      }
    }

    /* =========================
       ✅ Status Renderer
       ========================= */
    const STATUS_META = {
      pending:    { th:'รอดำเนินการ',   pct:10,  bg:'bg-amber-50',  text:'text-amber-700',  bar:'bg-amber-500' },
      inprogress: { th:'กำลังดำเนินการ', pct:50,  bg:'bg-sky-50',    text:'text-sky-700',    bar:'bg-sky-500' },
      completed:  { th:'เสร็จสิ้น',      pct:100, bg:'bg-emerald-50', text:'text-emerald-700', bar:'bg-emerald-500' },
      feedback:   { th:'ประเมิน',       pct:100, bg:'bg-indigo-50',  text:'text-indigo-700',  bar:'bg-indigo-500' },
      canceled:   { th:'ยกเลิก',        pct:0,   bg:'bg-rose-50',    text:'text-rose-700',    bar:'bg-rose-500' },
    };

    const normalizeStatus = (v) => {
      const key = (v ?? '').toString().trim().toLowerCase();
      if(key === 'in_progress') return 'inprogress';
      if(key === 'cancelled') return 'canceled';
      return key;
    };

    const statusToThai = (v) => STATUS_META[normalizeStatus(v)]?.th || (v ?? '');

    const statusRenderer = (p) => {
      const k = normalizeStatus(p.value);
      const meta = STATUS_META[k] || { th:'ไม่ทราบสถานะ', pct:0, bg:'bg-slate-50', text:'text-slate-600', bar:'bg-slate-400' };
      const pct = Math.max(0, Math.min(100, Number(meta.pct) || 0));

      return `
        <div class="flex flex-col w-full min-w-[140px] px-1 py-2 group">
          <div class="flex items-end justify-between mb-1.5">
            <span class="text-[12px] font-bold tracking-tight ${meta.text}">${escapeHtml(meta.th)}</span>
            <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-md ${meta.bg} ${meta.text} border border-current/10">${pct}%</span>
          </div>
          <div class="relative h-1.5 w-full bg-slate-100 rounded-full overflow-hidden shadow-inner">
            <div class="h-full rounded-full transition-all duration-700 ease-out ${meta.bar}" style="width:${pct}%"></div>
          </div>
        </div>
      `;
    };

    /* =========================
       ✅ ColumnDefs
       ========================= */
    const columnDefs = [
      {
        headerName: "รูป",
        field: "images",
        width: 80,
        pinned: 'left',
        sortable: false,
        filter: false,
        cellRenderer: (p) => {
          const imgs = p.value || [];
          const first = imgs.length ? imgs[0] : null;

          if(first){
            return `
              <div class="flex items-center h-full cursor-pointer group" onclick='openImageCarousel(${JSON.stringify(imgs)})'>
                <img src="${first}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 shadow-sm group-hover:scale-105 transition-transform" />
              </div>
            `;
          }

          return `
            <div class="flex items-center h-full">
              <div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-200 border-dashed flex items-center justify-center text-slate-300">
                <i data-lucide="image" class="w-4 h-4"></i>
              </div>
            </div>
          `;
        }
      },
      {
        headerName: "วันที่/เวลา",
        field: "report_date",
        colId: "report_date",
        width: 170,
        pinned: "left",
        sort: "desc",
        filter: "agDateColumnFilter",
        filterParams: {
          filterOptions: ["inRange"],
          defaultOption: "inRange",
          maxNumConditions: 1,
          numAlwaysVisibleConditions: 1,
          suppressAndOrCondition: true,
          buttons: ["reset", "apply"],
          closeOnApply: true,
          browserDatePicker: true,
          comparator: (filterLocalDateAtMidnight, cellValue) => {
            if(!cellValue) return -1;
            const cell = new Date(cellValue + "T00:00:00");
            if(cell < filterLocalDateAtMidnight) return -1;
            if(cell > filterLocalDateAtMidnight) return 1;
            return 0;
          },
          onFilterChanged: (params) => {
            const api = params.api;
            const model = api.getFilterModel() || {};
            const m = model.report_date;
            if(!m) return;

            if(m.type === "inRange" && m.dateFrom && !m.dateTo){
              m.dateTo = m.dateFrom;
              api.setFilterModel(model);
            }
            if(m.type === "inRange" && m.dateFrom && m.dateTo && m.dateTo < m.dateFrom){
              m.dateTo = m.dateFrom;
              api.setFilterModel(model);
            }
          }
        },
        cellRenderer: (p) => {
          const d = p.data?.report_date || '';
          const t = p.data?.report_time || '';
          return `
            <div class="leading-tight py-2">
              <div class="font-bold text-slate-900">${escapeHtml(formatThaiDate(d))}</div>
              <div class="text-[11px] text-slate-400 font-semibold">${escapeHtml(formatThaiTime(t))}</div>
            </div>
          `;
        }
      },
      {
        headerName: "ผู้แจ้ง",
        field: "name",
        width: 220,
        filter: 'agTextColumnFilter',
        cellRenderer: (p) => {
          const name = p.data?.name || '-';
          const phone = p.data?.phone || '-';
          return `
            <div class="leading-tight py-2">
              <div class="font-bold text-slate-800">${escapeHtml(name)}</div>
              <div class="text-[11px] text-slate-400 font-semibold">ติดต่อ ${escapeHtml(phone)}</div>
            </div>
          `;
        }
      },
      { headerName:"อาคาร", field:"building_name", width:170, filter:'agSetColumnFilter', filterParams:getFilterParams('building_name') },
      { headerName:"ชั้น",  field:"floor_name",    width:110, filter:'agSetColumnFilter', filterParams:getFilterParams('floor_name') },
      { headerName:"ห้อง",  field:"room_name",     width:140, filter:'agSetColumnFilter', filterParams:getFilterParams('room_name') },
      {
        headerName: "อาการเสีย/ปัญหา",
        field: "problem_detail",
        flex: 1,
        filter: 'agTextColumnFilter',
        cellRenderer: (p) => {
          const issue = p.value || '';
          const assetCode = p.data?.asset_code || '';
          const assetName = p.data?.asset_name || '';
          const machine = assetCode ? `
            <div class="text-[10px] mt-1 text-sky-700 font-bold font-mono bg-sky-50 border border-sky-100 px-2 py-0.5 rounded-md inline-flex">
              ${escapeHtml(assetCode)}${assetName ? ' — ' + escapeHtml(assetName) : ''}
            </div>` : '';
          return `
            <div class="leading-tight py-2">
              <div class="font-semibold text-slate-800">${escapeHtml(issue)}</div>
              ${machine}
            </div>
          `;
        }
      },
      {
        headerName:"สถานะ",
        field:"status",
        width:220,
        filter:"agSetColumnFilter",
        filterParams: { ...getFilterParams("status"), valueFormatter:(p)=>statusToThai(p.value) },
        cellRenderer: statusRenderer
      },
      {
        headerName:"ผู้บันทึก",
        field:"created_by",
        width:140,
        filter:'agSetColumnFilter',
        filterParams:getFilterParams('created_by'),
        cellRenderer:(p)=> `<span class="font-semibold text-slate-700">${escapeHtml(p.value || '')}</span>`
      },
      {
        headerName:"จัดการ",
        field:"actions",
        width:120,
        pinned:"right",
        cellRenderer:(params)=>{
          const wrap = document.createElement('div');
          wrap.className = "flex items-center justify-center gap-2 h-full";

          const mkBtn = (cls, icon, handler) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = cls;
            b.innerHTML = `<i data-lucide="${icon}" class="w-4 h-4"></i>`;
            b.addEventListener('click', handler);
            return b;
          };

          const btnEdit = mkBtn(
            "w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center",
            "panel-right-open",
            ()=> openDrawer('edit', params.data)
          );

          const btnAccept = mkBtn(
            "w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-emerald-600 flex items-center justify-center",
            "user-check",
            ()=> openDrawer('accept', params.data)
          );

          const btnClose = mkBtn(
            "w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-rose-600 flex items-center justify-center",
            "check-circle-2",
            ()=> openDrawer('close', params.data)
          );

          wrap.append(btnEdit, btnAccept, btnClose);
          setTimeout(safeIcons, 0);
          return wrap;
        }
      }
    ];

    /* =========================
       ✅ Grid Options
       ========================= */
    const gridOptions = {
      columnDefs,
      rowHeight: 65,
      rowModelType: 'serverSide',

      pagination: true,
      paginationPageSize: 10,
      cacheBlockSize: 10,
      paginationPageSizeSelector: [10, 20, 50, 100],

      components: { agDateInput: RRDateInput },

      onRowDataUpdated: safeIcons,
      onViewportChanged: safeIcons,
      onPaginationChanged: safeIcons,

      onGridReady: (params) => {
        gridApi = params.api;
        loadGrid();
      }
    };

    function loadGrid(){
      const datasource = {
        getRows: async (params) => {
          const req = params.request;
          const searchTerm = document.getElementById('grid-search')?.value || '';

          const url = new URL('handle_repair_requests.php', window.location.href);
          url.searchParams.set('action', 'get_all');
          url.searchParams.set('ag_id', AG_ID);
          url.searchParams.set('startRow', req.startRow);
          url.searchParams.set('endRow', req.endRow);
          url.searchParams.set('search', searchTerm);
          url.searchParams.set('filterModel', JSON.stringify(req.filterModel || {}));
          if(req.sortModel?.length) url.searchParams.set('sortModel', JSON.stringify(req.sortModel));

          try{
            const r = await fetch(url);
            const result = await r.json();

            params.success({
              rowData: result.rows || [],
              rowCount: result.lastRow ?? 0
            });

            document.getElementById('row-count').innerText = (result.lastRow ?? 0);
            setTimeout(safeIcons, 50);
          }catch(e){
            console.error(e);
            params.fail();
          }
        }
      };

      gridApi.setGridOption('serverSideDatasource', datasource);
    }

    function onSearchChanged(){
      gridApi?.refreshServerSide({ purge:true });
    }
    function refreshGrid(){
      gridApi?.refreshServerSide({ purge:true });
    }

    /* =========================
       ✅ Carousel (เดิมของคุณ)
       ========================= */
    let currentSlide = 0;
    let rrUrls = [];
    let rrToolbarTimer = null;

    const rrZoom = {
      scale:1, min:1, max:6,
      tx:0, ty:0, rotate:0,
      dragging:false, startX:0, startY:0, baseTX:0, baseTY:0
    };

    function clamp(v, min, max){ return Math.max(min, Math.min(max, v)); }
    function getActiveImg(){ return document.getElementById('rr-img-' + currentSlide); }

    function resetZoomState(){
      rrZoom.scale=1; rrZoom.tx=0; rrZoom.ty=0; rrZoom.rotate=0;
      rrZoom.dragging=false; rrZoom.startX=0; rrZoom.startY=0; rrZoom.baseTX=0; rrZoom.baseTY=0;
    }
    function updateZoomLabel(){
      const lb = document.getElementById('rr-zoom-label');
      if(lb) lb.textContent = Math.round(rrZoom.scale * 100) + '%';
    }
    function applyTransformToActive(){
      const img = getActiveImg();
      if(!img) return;
      img.style.transform = `translate(${rrZoom.tx}px,${rrZoom.ty}px) scale(${rrZoom.scale}) rotate(${rrZoom.rotate}deg)`;
    }
    function setZoom(newScale){
      rrZoom.scale = clamp(newScale, rrZoom.min, rrZoom.max);
      if(rrZoom.scale <= 1.001){ rrZoom.scale=1; rrZoom.tx=0; rrZoom.ty=0; }
      updateZoomLabel(); applyTransformToActive();
    }
    function rotateImage(){ rrZoom.rotate = (rrZoom.rotate + 90) % 360; applyTransformToActive(); }

    function deactivateSlide(i){
      document.getElementById('slide-'+i)?.classList.remove('active');
      document.getElementById('dot-'+i)?.classList.remove('active');
    }
    function activateSlide(i){
      document.getElementById('slide-'+i)?.classList.add('active');
      document.getElementById('dot-'+i)?.classList.add('active');
      applyTransformToActive();
    }
    function goToSlide(index){
      const total = rrUrls.length;
      if(total <= 0) return;

      if(index < 0) index = 0;
      if(index >= total) index = total - 1;
      if(index === currentSlide) return;

      deactivateSlide(currentSlide);
      currentSlide = index;
      activateSlide(currentSlide);

      resetZoomState();
      updateZoomLabel();
      applyTransformToActive();
    }
    function nextSlide(){ if(rrUrls.length>1) goToSlide((currentSlide+1) % rrUrls.length); }
    function prevSlide(){ if(rrUrls.length>1) goToSlide((currentSlide-1+rrUrls.length) % rrUrls.length); }

    function isFullscreen(){
      return !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
    }
    function toggleFullscreen(el){
      if(!isFullscreen()){
        el.requestFullscreen?.() || el.webkitRequestFullscreen?.() || el.mozRequestFullScreen?.() || el.msRequestFullscreen?.();
      }else{
        document.exitFullscreen?.() || document.webkitExitFullscreen?.() || document.mozCancelFullScreen?.() || document.msExitFullscreen?.();
      }
    }

    function rrShowToolbar(){
      document.getElementById('rr-toolbar')?.classList.add('rr-show');

      const dots = document.getElementById('rr-dots');
      if(dots){ dots.classList.add('rr-autohide'); dots.classList.remove('rr-hide'); }

      if(rrToolbarTimer) clearTimeout(rrToolbarTimer);
      rrToolbarTimer = setTimeout(()=>{
        document.getElementById('rr-toolbar')?.classList.remove('rr-show');
        document.getElementById('rr-dots')?.classList.add('rr-hide');
      }, 1800);
    }

    function rrBindAutoHide(containerEl){
      if(!containerEl) return;
      rrShowToolbar();

      const handler = () => rrShowToolbar();
      containerEl.addEventListener('mousemove', handler);
      containerEl.addEventListener('mousedown', handler);
      containerEl.addEventListener('wheel', handler, { passive:true });
      containerEl.addEventListener('touchstart', handler, { passive:true });
      containerEl.addEventListener('touchmove', handler, { passive:true });
      window.addEventListener('keydown', handler);

      containerEl._rrAutoHideOff = () => {
        containerEl.removeEventListener('mousemove', handler);
        containerEl.removeEventListener('mousedown', handler);
        containerEl.removeEventListener('wheel', handler);
        containerEl.removeEventListener('touchstart', handler);
        containerEl.removeEventListener('touchmove', handler);
        window.removeEventListener('keydown', handler);
        if(rrToolbarTimer) clearTimeout(rrToolbarTimer);
        rrToolbarTimer = null;
      };
    }

    function attachPanZoomHandlers(){
      const wraps = document.querySelectorAll('.rr-imgwrap');
      if(!wraps?.length) return;

      wraps.forEach((wrap)=>{
        wrap.addEventListener('wheel', (e)=>{
          const sid = parseInt(wrap.getAttribute('data-slide') || '0', 10);
          if(sid !== currentSlide) return;

          e.preventDefault();
          const delta = e.deltaY || 0;
          if(delta > 0) setZoom(rrZoom.scale / 1.15);
          else setZoom(rrZoom.scale * 1.15);
        }, { passive:false });

        wrap.addEventListener('mousedown', (e)=>{
          const sid = parseInt(wrap.getAttribute('data-slide') || '0', 10);
          if(sid !== currentSlide) return;
          if(rrZoom.scale <= 1) return;

          rrZoom.dragging = true;
          rrZoom.startX = e.clientX;
          rrZoom.startY = e.clientY;
          rrZoom.baseTX = rrZoom.tx;
          rrZoom.baseTY = rrZoom.ty;

          wrap.classList.add('rr-grabbing');
          wrap.classList.remove('rr-grab');
        });

        window.addEventListener('mousemove', (e)=>{
          if(!rrZoom.dragging) return;
          rrZoom.tx = rrZoom.baseTX + (e.clientX - rrZoom.startX);
          rrZoom.ty = rrZoom.baseTY + (e.clientY - rrZoom.startY);
          applyTransformToActive();
        });

        window.addEventListener('mouseup', ()=>{
          if(!rrZoom.dragging) return;
          rrZoom.dragging = false;

          const img = getActiveImg();
          if(img?.parentElement){
            img.parentElement.classList.remove('rr-grabbing');
            img.parentElement.classList.add('rr-grab');
          }
        });

        // touch swipe
        let touchStartX=0, touchStartY=0, swiping=false;

        wrap.addEventListener('touchstart', (e)=>{
          const sid = parseInt(wrap.getAttribute('data-slide') || '0', 10);
          if(sid !== currentSlide) return;
          if(!e.touches?.[0]) return;

          touchStartX = e.touches[0].clientX;
          touchStartY = e.touches[0].clientY;
          swiping = true;

          if(rrZoom.scale > 1){
            rrZoom.dragging = true;
            rrZoom.startX = touchStartX;
            rrZoom.startY = touchStartY;
            rrZoom.baseTX = rrZoom.tx;
            rrZoom.baseTY = rrZoom.ty;
          }
        }, {passive:true});

        wrap.addEventListener('touchmove', (e)=>{
          const sid = parseInt(wrap.getAttribute('data-slide') || '0', 10);
          if(sid !== currentSlide) return;
          if(!swiping || !e.touches?.[0]) return;

          const x = e.touches[0].clientX;
          const y = e.touches[0].clientY;

          if(rrZoom.scale > 1 && rrZoom.dragging){
            rrZoom.tx = rrZoom.baseTX + (x - rrZoom.startX);
            rrZoom.ty = rrZoom.baseTY + (y - rrZoom.startY);
            applyTransformToActive();
          }
        }, {passive:true});

        wrap.addEventListener('touchend', (e)=>{
          const sid = parseInt(wrap.getAttribute('data-slide') || '0', 10);
          if(sid !== currentSlide) return;

          if(rrZoom.scale > 1){
            rrZoom.dragging = false;
            swiping = false;
            return;
          }

          const endX = e.changedTouches?.[0]?.clientX ?? touchStartX;
          const endY = e.changedTouches?.[0]?.clientY ?? touchStartY;
          const dx = endX - touchStartX;
          const dy = endY - touchStartY;

          swiping = false;
          if(Math.abs(dx) < 40 || Math.abs(dx) < Math.abs(dy)) return;
          if(dx < 0) nextSlide();
          else prevSlide();
        }, {passive:true});
      });
    }

    function openImageCarousel(images){
      if(!images?.length){
        Swal.fire("ไม่มีรูป", "รายการนี้ไม่มีรูปแนบ", "info");
        return;
      }

      rrUrls = images.filter(Boolean);
      if(!rrUrls.length){
        Swal.fire("ไม่มีรูป", "รายการนี้ไม่มีรูปแนบ", "info");
        return;
      }

      currentSlide = 0;
      resetZoomState();

      let html = `<div class="swal-carousel-container" id="rr-carousel">`;

      html += `
        <div class="rr-toolbar" id="rr-toolbar">
          <button type="button" class="rr-toolbtn" id="rr-prev" title="รูปก่อนหน้า"><i data-lucide="chevron-left"></i></button>
          <button type="button" class="rr-toolbtn" id="rr-next" title="รูปถัดไป"><i data-lucide="chevron-right"></i></button>
          <div class="rr-toolsep"></div>
          <button type="button" class="rr-toolbtn" id="rr-fs-btn" title="เต็มจอ"><i data-lucide="maximize-2"></i></button>
          <button type="button" class="rr-toolbtn" id="rr-zoom-out" title="ซูมออก"><i data-lucide="minus"></i></button>
          <button type="button" class="rr-toolbtn" id="rr-zoom-in" title="ซูมเข้า"><i data-lucide="plus"></i></button>
          <button type="button" class="rr-toolbtn" id="rr-rotate" title="กลับรูป"><i data-lucide="rotate-cw"></i></button>
          <div class="rr-zoomlabel" id="rr-zoom-label">100%</div>
        </div>
      `;

      rrUrls.forEach((img, idx)=>{
        html += `
          <div class="carousel-item ${idx===0?'active':''}" id="slide-${idx}">
            <div class="rr-imgwrap rr-grab" data-slide="${idx}">
              <img id="rr-img-${idx}" src="${img}"
                   style="transform: translate(0px,0px) scale(1) rotate(0deg); transform-origin: 50% 50%;"
                   onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;900&quot; height=&quot;450&quot;><rect width=&quot;100%&quot; height=&quot;100%&quot; fill=&quot;%23f1f5f9&quot;/><text x=&quot;50%&quot; y=&quot;50%&quot; text-anchor=&quot;middle&quot; fill=&quot;%2394a3b8&quot; font-size=&quot;18&quot;>Image not found</text></svg>';" />
            </div>
          </div>
        `;
      });

      if(rrUrls.length > 1){
        html += `<div class="rr-dots" id="rr-dots">`;
        rrUrls.forEach((_, d)=>{
          html += `<div class="rr-dot ${d===0?'active':''}" id="dot-${d}" data-idx="${d}"></div>`;
        });
        html += `</div>`;
      }

      html += `</div>`;

      let rrKeyHandler = null;

      Swal.fire({
        title: 'รูปประกอบการแจ้งซ่อม',
        html,
        showConfirmButton:false,
        showCloseButton:true,
        width:'1280px',
        customClass:{ popup:'rounded-3xl p-4' },
        didOpen: () => {
          safeIcons();
          rrBindAutoHide(document.getElementById('rr-carousel'));

          document.getElementById('rr-dots')?.addEventListener('click', (e)=>{
            const to = e.target?.getAttribute?.('data-idx');
            if(to === null) return;
            goToSlide(parseInt(to, 10));
          });

          const fsBtn = document.getElementById('rr-fs-btn');
          const fsTarget = document.getElementById('rr-carousel');

          document.getElementById('rr-prev')?.addEventListener('click', prevSlide);
          document.getElementById('rr-next')?.addEventListener('click', nextSlide);
          document.getElementById('rr-zoom-in')?.addEventListener('click', ()=> setZoom(rrZoom.scale * 1.25));
          document.getElementById('rr-zoom-out')?.addEventListener('click', ()=> setZoom(rrZoom.scale / 1.25));
          document.getElementById('rr-rotate')?.addEventListener('click', rotateImage);

          const syncFsIcon = ()=>{
            const isFs = isFullscreen();
            if(fsBtn){
              fsBtn.innerHTML = isFs ? '<i data-lucide="minimize-2"></i>' : '<i data-lucide="maximize-2"></i>';
              safeIcons();
            }
          };

          fsBtn?.addEventListener('click', ()=>{ toggleFullscreen(fsTarget); setTimeout(syncFsIcon, 60); });
          document.addEventListener('fullscreenchange', syncFsIcon);
          syncFsIcon();

          rrKeyHandler = (e)=>{
            if(e.key==='ArrowRight') nextSlide();
            if(e.key==='ArrowLeft') prevSlide();
            if(e.key==='+' || e.key==='=') setZoom(rrZoom.scale * 1.25);
            if(e.key==='-' || e.key==='_') setZoom(rrZoom.scale / 1.25);
            if(e.key==='r' || e.key==='R') rotateImage();
            if(e.key==='f' || e.key==='F') toggleFullscreen(fsTarget);
          };
          document.addEventListener('keydown', rrKeyHandler);

          attachPanZoomHandlers();
          updateZoomLabel();
          applyTransformToActive();
        },
        willClose: () => {
          resetZoomState();
          rrUrls = [];
          if(rrKeyHandler) document.removeEventListener('keydown', rrKeyHandler);
          rrKeyHandler = null;

          const wrap = document.getElementById('rr-carousel');
          if(wrap?._rrAutoHideOff) wrap._rrAutoHideOff();
        }
      });
    }

    /* =========================
       ✅ Drawer
       ========================= */
    let UD = { mode:'edit', row:null, closeFiles:[] };
    const MAX_CLOSE_FILES = 5;

    function openDrawer(mode, rowData){
      UD.mode = mode || 'edit';
      UD.row = rowData || null;

      document.getElementById('ud_backdrop')?.classList.remove('hidden');
      document.getElementById('ud_drawer')?.classList.remove('translate-x-full');

      document.getElementById('ud_title').innerText = 'รายละเอียดงานซ่อม';
      document.getElementById('ud_subtitle').innerText = `ID: ${rowData?.id || rowData?.rp_id || '-'}`;

      fillEditTab(rowData);
      switchDrawerTab(UD.mode);

      safeIcons();
      document.documentElement.style.overflow = 'hidden';
    }

    function closeDrawer(){
      document.getElementById('ud_drawer')?.classList.add('translate-x-full');
      setTimeout(()=> document.getElementById('ud_backdrop')?.classList.add('hidden'), 250);
      document.documentElement.style.overflow = '';
    }

    document.getElementById('ud_backdrop')?.addEventListener('click', closeDrawer);

    function switchDrawerTab(tab){
      UD.mode = tab;

      document.querySelectorAll('.ud_tab').forEach(b=>{
        b.classList.toggle('active', b.dataset.tab === tab);
      });

      document.querySelectorAll('.ud_panel').forEach(p=>{
        p.classList.toggle('hidden', p.dataset.panel !== tab);
      });

      safeIcons();
    }
	
	function renderEditImages(imgs){
	  const wrap = document.getElementById('ed_preview');
	  const count = document.getElementById('ed_img_count');
	  if(!wrap || !count) return;
	
	  const list = (imgs || []).filter(Boolean).slice(0, 5);
	  count.innerText = `${list.length}/5`;
	
	  if(!list.length){
		wrap.innerHTML = `
		  <div class="w-full py-6 flex items-center justify-center text-slate-400 text-[11px] font-semibold">
			ไม่มีรูปแนบในการแจ้งซ่อม
		  </div>
		`;
		return;
	  }
	
	  wrap.innerHTML = list.map((src, idx)=>`
		<button type="button"
		  class="relative w-20 h-20 rounded-xl overflow-hidden border border-slate-200 bg-white shrink-0 group"
		  onclick='openImageCarousel(${JSON.stringify(list)})'
		  title="ดูรูป">
		  <img src="${src}" class="w-full h-full object-cover group-hover:scale-105 transition-transform"
			   onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;200&quot; height=&quot;200&quot;><rect width=&quot;100%&quot; height=&quot;100%&quot; fill=&quot;%23f1f5f9&quot;/><text x=&quot;50%&quot; y=&quot;50%&quot; text-anchor=&quot;middle&quot; fill=&quot;%2394a3b8&quot; font-size=&quot;12&quot;>Image not found</text></svg>';">
		  <span class="absolute bottom-1 right-1 px-1.5 py-0.5 rounded-md bg-white/90 border border-slate-200 text-[10px] font-extrabold text-slate-600">
			${idx+1}
		  </span>
		</button>
	  `).join('');
	}
	
	
	function fillEditTab(row){
		  const setVal = (id, v) => {
			const el = document.getElementById(id);
			if (el) el.value = (v ?? '');
		  };
		
		  // ฟิลด์พื้นฐาน
		  setVal('ed_report_date', row?.report_date);
		  setVal('ed_report_time', row?.report_time);
		  setVal('ed_name', row?.name);
		  setVal('ed_phone', row?.phone);
		
		  // ✅ ต้อง set building ก่อน (AssetSelector ใช้ building)
		  setVal('ed_building', row?.building);
		  setVal('ed_floor', row?.floor);
		  setVal('ed_room', row?.room);
		
		  setVal('ed_problem', row?.problem_detail);
		  setVal('ed_urgency', row?.urgency || 'medium');
		
		  // ✅ รูป
		  setEditImagesFromRow(row);
		
		  // ✅ ==========================
		  // ✅ สำคัญ: โหลด Asset จาก machine_id
		  // ✅ ==========================
		   // ✅ ===== จุดสำคัญ: ดึง machine_id แล้วให้ AssetSelector โหลด =====
  const machineId = row?.machine_id || row?.asset_ass_id || row?.ass_id || '';
  console.log('[asset] building=', document.getElementById('ed_building')?.value, 'machineId=', machineId);

		
		  if (machineId) {
			// ✅ เก็บ id ไว้ (แนะนำให้ใช้ hidden ตัวนี้เป็นหลัก)
			setVal('ed_asset_id', machineId);
		
			// ถ้าคุณ "ตั้งใจ" จะใช้ ed_asset เก็บ machine_id ด้วย ก็ set ไปด้วย
			setVal('ed_asset', machineId);
		
			// ✅ ให้ไปดึงรายละเอียดแล้ว render card
			AssetSelector.init({ ass_id: machineId });   // หรือ AssetSelector.loadOne(machineId)
		  } else {
			AssetSelector.clear();
		  }
		
		  // ✅ โชว์ location badges (ค่อยทำใน setTimeout ก็ได้)
		  setTimeout(() => {
			const b = row?.building_name || '';
			const f = row?.floor_name || '';
			const r = row?.room_name || '';
		
			if (b || f || r) {
			  document.getElementById('loc_placeholder_edit')?.classList.add('hidden');
			  document.getElementById('loc_selected_edit')?.classList.remove('hidden');
			  document.getElementById('disp_building_edit').innerText = b || '-';
			  document.getElementById('disp_floor_edit').innerText    = f || '-';
			  document.getElementById('disp_room_edit').innerText     = r || '-';
			}
		  }, 0);
		}



    function initCloseUploader(){
      const input = document.getElementById('cl_images');
      const preview = document.getElementById('cl_preview');
      const count = document.getElementById('cl_img_count');
      const err = document.getElementById('cl_img_err');
      if(!input || !preview || !count) return;

      const render = ()=>{
        preview.innerHTML = '';
        count.innerText = `${UD.closeFiles.length}/${MAX_CLOSE_FILES}`;
        err?.classList.toggle('hidden', UD.closeFiles.length <= MAX_CLOSE_FILES);

        UD.closeFiles.slice(0, MAX_CLOSE_FILES).forEach((f, idx)=>{
          const url = URL.createObjectURL(f);
          const wrap = document.createElement('div');
          wrap.className = "relative w-20 h-20 rounded-xl overflow-hidden border border-slate-200 bg-white shrink-0";
          wrap.innerHTML = `
            <img src="${url}" class="w-full h-full object-cover">
            <button type="button" data-idx="${idx}"
              class="absolute top-1 right-1 w-6 h-6 rounded-full bg-rose-500 text-white text-xs font-extrabold hover:bg-rose-600">×</button>
          `;
          wrap.querySelector('button').addEventListener('click', (e)=>{
            const i = Number(e.currentTarget.dataset.idx);
            UD.closeFiles.splice(i,1);
            render();
          });
          preview.appendChild(wrap);
        });
      };

      input.addEventListener('change', ()=>{
        const incoming = Array.from(input.files || []).filter(f=>f.type.startsWith('image/'));
        const merged = [...UD.closeFiles, ...incoming];

        UD.closeFiles = merged.slice(0, MAX_CLOSE_FILES);
        if(merged.length > MAX_CLOSE_FILES) err?.classList.remove('hidden'); else err?.classList.add('hidden');

        input.value = '';
        render();
      });

      const box = preview.closest('.rounded-2xl');
      box?.addEventListener('dragover', (e)=> e.preventDefault());
      box?.addEventListener('drop', (e)=>{
        e.preventDefault();
        const files = Array.from(e.dataTransfer?.files || []).filter(f=>f.type.startsWith('image/'));
        UD.closeFiles = [...UD.closeFiles, ...files].slice(0, MAX_CLOSE_FILES);
        render();
      });

      render();
    }
	
	// ✅ รูปในแท็บแก้ไข (รูปแจ้งซ่อม)
let ED = { keepUrls: [], newFiles: [] };
const MAX_EDIT_FILES = 5;

function initEditUploader(){
  const input = document.getElementById('ed_images');
  const preview = document.getElementById('ed_preview');
  const count = document.getElementById('ed_img_count');
  const err = document.getElementById('ed_img_err');
  if(!input || !preview || !count) return;

  const total = ()=> (ED.keepUrls.length + ED.newFiles.length);

  const render = ()=>{
    const n = total();
    count.innerText = `${n}/${MAX_EDIT_FILES}`;
    err?.classList.toggle('hidden', n <= MAX_EDIT_FILES);

    const items = [
      ...ED.keepUrls.map(u=>({ type:'url', value:u })),
      ...ED.newFiles.map(f=>({ type:'file', value:f }))
    ];

    if(!items.length){
      preview.innerHTML = `
        <div class="w-full py-6 flex items-center justify-center text-slate-400 text-[11px] font-semibold">
          ไม่มีรูปแนบ
        </div>`;
      return;
    }

    preview.innerHTML = items.map((it, idx)=>{
      const src = (it.type==='url') ? it.value : URL.createObjectURL(it.value);
      return `
        <div class="relative w-20 h-20 rounded-xl overflow-hidden border border-slate-200 bg-white shrink-0 group">
          <button type="button" onclick='openImageCarousel(${JSON.stringify(
  items.map(x=> x.type === "url" ? x.value : URL.createObjectURL(x.value))
)})'
            class="w-full h-full">
            <img src="${src}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
          </button>

          <button type="button" data-idx="${idx}"
            class="absolute top-1 right-1 w-6 h-6 rounded-full bg-rose-500 text-white text-xs font-extrabold hover:bg-rose-600">×</button>
        </div>
      `;
    }).join('');

    // bind remove
    preview.querySelectorAll('button[data-idx]').forEach(btn=>{
      btn.addEventListener('click', (e)=>{
        const i = Number(e.currentTarget.dataset.idx);
        // แยก index ระหว่าง keepUrls กับ newFiles
        if(i < ED.keepUrls.length) ED.keepUrls.splice(i,1);
        else ED.newFiles.splice(i - ED.keepUrls.length, 1);
        render();
      });
    });
  };

  input.addEventListener('change', ()=>{
    const incoming = Array.from(input.files || []).filter(f=>f.type.startsWith('image/'));
    input.value = '';

    const canAdd = MAX_EDIT_FILES - total();
    if(canAdd <= 0){ err?.classList.remove('hidden'); return; }

    ED.newFiles.push(...incoming.slice(0, canAdd));
    render();
  });
  
  const box = preview.closest('.rounded-2xl');
	if(box){
	  box.addEventListener('dragover', e=>{
		e.preventDefault();
		box.classList.add('ring-2','ring-sky-400');
	  });
	
	  box.addEventListener('dragleave', ()=>{
		box.classList.remove('ring-2','ring-sky-400');
	  });
	
	  box.addEventListener('drop', e=>{
		e.preventDefault();
		box.classList.remove('ring-2','ring-sky-400');
	
		const files = Array.from(e.dataTransfer.files)
		  .filter(f=>f.type.startsWith('image/'));
	
		const canAdd = MAX_EDIT_FILES - total();
		ED.newFiles.push(...files.slice(0, canAdd));
		render();
	  });
	}

  render();
}

// ✅ เรียกตอนเปิด Drawer edit เพื่อโหลดรูปเดิม
function setEditImagesFromRow(row){
  ED.keepUrls = (row?.images || []).filter(Boolean).slice(0, MAX_EDIT_FILES);
  ED.newFiles = [];
  initEditUploader();
}


    function addPartRow(name='', qty='1'){
      const wrap = document.getElementById('parts_wrap');
      if(!wrap) return;

      const row = document.createElement('div');
      row.className = "grid grid-cols-12 gap-2 items-center";
      row.innerHTML = `
        <input class="col-span-8 px-3 py-2 rounded-xl border border-slate-200 bg-white text-[12px] font-semibold"
          placeholder="ชื่ออะไหล่/วัสดุ..." value="${escapeHtml(name)}">
        <input type="number" min="0" class="col-span-3 px-3 py-2 rounded-xl border border-slate-200 bg-white text-[12px] font-semibold"
          placeholder="จำนวน" value="${escapeHtml(qty)}">
        <button type="button" class="col-span-1 h-10 rounded-xl border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100">
          <i data-lucide="trash-2" class="w-4 h-4 mx-auto"></i>
        </button>
      `;
      row.querySelector('button').addEventListener('click', ()=> row.remove());
      wrap.appendChild(row);
      safeIcons();
    }

    async function saveDrawer(){
      if(!UD.row) return;

      if(UD.mode === 'edit'){
		  const fd = new FormData();
		  fd.append('id', UD.row.id);
		  fd.append('report_date', document.getElementById('ed_report_date')?.value || '');
		  fd.append('report_time', document.getElementById('ed_report_time')?.value || '');
		  fd.append('name', document.getElementById('ed_name')?.value || '');
		  fd.append('phone', document.getElementById('ed_phone')?.value || '');
		  fd.append('building', document.getElementById('ed_building')?.value || '');
		  fd.append('floor', document.getElementById('ed_floor')?.value || '');
		  fd.append('room', document.getElementById('ed_room')?.value || '');
		  fd.append('problem_detail', document.getElementById('ed_problem')?.value || '');
		  fd.append('urgency', document.getElementById('ed_urgency')?.value || 'medium');
		
		  // ✅ รูป: keep + new (รวม <= 5)
		  fd.append('keep_urls', JSON.stringify(ED.keepUrls || []));
		  (ED.newFiles || []).forEach(f=> fd.append('new_images[]', f));
		
		  // TODO: fetch ไป API จริงของคุณ
		  // await fetch('handle_repair_requests.php?action=update', { method:'POST', body:fd });
		
		  Swal.fire({icon:'success', title:'บันทึกแก้ไขแล้ว', timer:1200, showConfirmButton:false});
		  closeDrawer();
		  return;
		}

      if(UD.mode === 'accept'){
        const payload = {
          id: UD.row.id,
          action_date: document.getElementById('ac_date')?.value || '',
          action_time: document.getElementById('ac_time')?.value || '',
          technician: document.getElementById('ac_tech')?.value || '',
          note: document.getElementById('ac_note')?.value || '',
        };
        // TODO: fetch จริง
        Swal.fire({icon:'success', title:'รับงานแล้ว', timer:1200, showConfirmButton:false});
        closeDrawer();
        return;
      }

      if(UD.mode === 'close'){
        const parts = [];
        document.querySelectorAll('#parts_wrap > div').forEach(row=>{
          const inputs = row.querySelectorAll('input');
          const n = inputs?.[0]?.value?.trim() || '';
          const q = inputs?.[1]?.value?.trim() || '0';
          if(n) parts.push({ name:n, qty:q });
        });

        const fd = new FormData();
        fd.append('id', UD.row.id);
        fd.append('finish_date', document.getElementById('cl_date')?.value || '');
        fd.append('finish_time', document.getElementById('cl_time')?.value || '');
        fd.append('service_type', document.getElementById('cl_service_type')?.value || '');
        fd.append('job_type', document.getElementById('cl_job_type')?.value || '');
        fd.append('note', document.getElementById('cl_note')?.value || '');
        fd.append('parts_json', JSON.stringify(parts));
        UD.closeFiles.forEach(f=> fd.append('close_images[]', f));

        // TODO: fetch จริง
        Swal.fire({icon:'success', title:'ปิดงานเรียบร้อย', timer:1200, showConfirmButton:false});
        closeDrawer();
        return;
      }
    }

    /* =========================
       ✅ Boot
       ========================= */
    document.addEventListener('DOMContentLoaded', ()=>{
      const gridDiv = document.querySelector('#myGrid');
      gridApi = agGrid.createGrid(gridDiv, gridOptions);
      initCloseUploader();
      safeIcons();
    });

    document.addEventListener('keydown', (e)=>{
      if(e.key === 'Escape'){
        const dr = document.getElementById('ud_drawer');
        if(dr && !dr.classList.contains('translate-x-full')) closeDrawer();
      }
    });
	
	function initUrgencyUI(){
  const sel = document.getElementById('ed_urgency');
  const wrap = document.getElementById('urg_pills');
  const badge = document.getElementById('urg_badge');
  if(!sel || !wrap) return;

  const meta = {
    low:      { text:'ต่ำ',        cls:'bg-slate-50 text-slate-600 border-slate-200' },
    medium:   { text:'ปานกลาง',    cls:'bg-sky-50 text-sky-700 border-sky-200' },
    high:     { text:'สูง',        cls:'bg-amber-50 text-amber-700 border-amber-200' },
    critical: { text:'เร่งด่วนมาก', cls:'bg-rose-50 text-rose-700 border-rose-200' },
  };

  const apply = (v)=>{
    sel.value = v;
    wrap.querySelectorAll('.urg-pill').forEach(b=>{
      b.classList.toggle('urg-active', b.dataset.value === v);
    });
    if(badge){
      const m = meta[v] || meta.medium;
      badge.className = `text-[10px] font-extrabold px-2.5 py-1 rounded-full border ${m.cls}`;
      badge.textContent = m.text;
    }
  };

  wrap.addEventListener('click', (e)=>{
    const btn = e.target.closest('.urg-pill');
    if(!btn) return;
    apply(btn.dataset.value);
  });

  // init จากค่าเดิมใน select
  apply(sel.value || 'medium');
}

// ✅ เรียกหลังเปิด drawer ด้วยก็ได้
document.addEventListener('DOMContentLoaded', initUrgencyUI);
	
	// เริ่มเลือกห้อง<br>
(function(){
  const LS_PRIMARY = '#006B9F';

  window.showSoftAlert = window.showSoftAlert || function(message, type='info'){
    const old = document.getElementById('soft_alert_top');
    if(old) old.remove();

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

    if(window.lucide?.createIcons) window.lucide.createIcons();

    requestAnimationFrame(() => {
      toast.style.transition = 'all .3s cubic-bezier(.2,.9,.2,1)';
      toast.style.opacity = '1';
      toast.style.transform = 'translate(-50%, 0)';
    });

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translate(-50%, -12px)';
      setTimeout(() => toast.remove(), 300);
    }, 2200);
  };

  window.LocationSelector = {
    spatialDB: [],
    currentStep: 1,
    selection: { bld: null, flr: null, rm: null },
    target: 'edit',
    focus: 'building',
    _loaded: false,
    _bound: false,

    init(){
      this.injectStyles();
      this.injectModal();
      this.bindEvents();
      this.safeIcons();
    },

    safeIcons(){
      const run = ()=> window.lucide?.createIcons?.();
      run(); setTimeout(run, 60);
    },

    escapeHtml(str){
      return String(str ?? '').replace(/[&<>"']/g, m => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'
      }[m]));
    },

    async openModal(target='edit', focus='building'){
      this.target = target || 'edit';
      this.focus  = focus  || 'building';

      if(!document.getElementById('spatial_modal')){
        this.injectStyles();
        this.injectModal();
        this.bindEvents();
      }

      const m = document.getElementById('spatial_modal');
      if(!m) return;

      // show
      m.classList.remove('hidden');
      requestAnimationFrame(()=> m.classList.remove('opacity-0'));

      // loading
      const vb = document.getElementById('view_building');
      if(vb){
        vb.innerHTML = `
          <div class="col-span-full flex flex-col items-center justify-center py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2" style="border-color:${LS_PRIMARY}"></div>
            <p class="mt-4 text-slate-400 font-semibold">กำลังโหลดข้อมูล...</p>
          </div>`;
      }

      await this.loadSpatialDB();
      this.syncSelectionFromInputs();

      // route by focus
      if(this.focus === 'room'){
        if(!this.selection.bld){
          showSoftAlert('กรุณาเลือกอาคารก่อน', 'info');
          this.renderBuildings();
          return;
        }
        if(!this.selection.flr){
          showSoftAlert('กรุณาเลือกชั้นก่อน', 'info');
          this.renderFloors();
          return;
        }
        this.renderRooms();
        return;
      }

      if(this.focus === 'floor'){
        if(!this.selection.bld){
          showSoftAlert('กรุณาเลือกอาคารก่อน', 'info');
          this.renderBuildings();
          return;
        }
        this.renderFloors();
        return;
      }

      this.renderBuildings();
    },

    closeModal(){
      const m = document.getElementById('spatial_modal');
      if(!m) return;
      m.classList.add('opacity-0');
      setTimeout(()=> m.classList.add('hidden'), 220);
    },

    async loadSpatialDB(){
      if(this._loaded) return;

      try{
        const res = await fetch('get_building_all.php', { cache:'no-store' });
        const json = await res.json();

        // normalize to: [{id,name,floors:[{id,name,rooms:[{id,name}]}]}]
        let raw = [];

        if(json?.ok && Array.isArray(json.data)){
          raw = json.data;
        }else if(Array.isArray(json?.building)){
          raw = json.building;
        }else if(Array.isArray(json?.data)){
          raw = json.data;
        }

        this.spatialDB = (raw || []).map(b => ({
          id: b.area_id ?? b.id,
          name: b.area_name ?? b.name,
          floors: (b.floor || b.floors || []).map(f => ({
            id: f.ac_id ?? f.id,
            name: f.ac_name ?? f.name,
            rooms: (f.room || f.rooms || []).map(r => ({
              id: r.ar_id ?? r.id,
              name: r.ar_name ?? r.name
            }))
          }))
        }));

        this._loaded = true;
      }catch(err){
        console.error('Load Error:', err);
        this.spatialDB = [];
      }
    },
	
	getTargetMap(target='edit'){
  // รองรับหลาย target ในอนาคตได้
  if(target === 'edit'){
    return {
      inBld:'ed_building', inFlr:'ed_floor', inRm:'ed_room',
      dB:'disp_building_edit', dF:'disp_floor_edit', dR:'disp_room_edit',
      ph:'loc_placeholder_edit', sel:'loc_selected_edit'
    };
  }
  // fallback
  return {
    inBld:'ed_building', inFlr:'ed_floor', inRm:'ed_room',
    dB:'disp_building_edit', dF:'disp_floor_edit', dR:'disp_room_edit',
    ph:'loc_placeholder_edit', sel:'loc_selected_edit'
  };
},

refreshTriggerUI(target='edit'){
  const map = this.getTargetMap(target);
  const b = document.getElementById(map.inBld)?.value || '';
  const f = document.getElementById(map.inFlr)?.value || '';
  const r = document.getElementById(map.inRm)?.value || '';

  // ถ้ามีเลือกอาคารแล้วให้โชว์ selected
  if(b || f || r){
    document.getElementById(map.ph)?.classList.add('hidden');
    document.getElementById(map.sel)?.classList.remove('hidden');
  }else{
    document.getElementById(map.ph)?.classList.remove('hidden');
    document.getElementById(map.sel)?.classList.add('hidden');
  }

  this.safeIcons?.();
},

clearRoom(target='edit'){
  const map = this.getTargetMap(target);

  // เคลียร์ state ใน module
  this.selection.rm = null;

  // เคลียร์ hidden input + ข้อความ
  const inRm = document.getElementById(map.inRm);
  if(inRm) inRm.value = '';
  const dispRm = document.getElementById(map.dR);
  if(dispRm) dispRm.innerText = '-';

  // update status ใน modal (ถ้า modal เปิดอยู่)
  this.updateStatusText?.();
  this.setConfirmEnabled?.(!!this.selection.bld, 'Confirm Selection');

  this.safeIcons?.();
},

clearFloor(target='edit'){
  const map = this.getTargetMap(target);

  // เคลียร์ state ใน module
  this.selection.flr = null;
  this.selection.rm  = null;

  // เคลียร์ hidden input + ข้อความ (ชั้น + ห้อง)
  const inFlr = document.getElementById(map.inFlr);
  const inRm  = document.getElementById(map.inRm);
  if(inFlr) inFlr.value = '';
  if(inRm)  inRm.value = '';

  const dispFlr = document.getElementById(map.dF);
  const dispRm  = document.getElementById(map.dR);
  if(dispFlr) dispFlr.innerText = '-';
  if(dispRm)  dispRm.innerText  = '-';

  // update status ใน modal (ถ้า modal เปิดอยู่)
  this.updateStatusText?.();
  this.setConfirmEnabled?.(!!this.selection.bld, 'Confirm Selection');

  this.safeIcons?.();
},


    syncSelectionFromInputs(){
      // ตอนนี้ใช้ target='edit' เป็นหลัก
      const map = (this.target === 'edit') ? {
        inB: 'ed_building',
        inF: 'ed_floor',
        inR: 'ed_room'
      } : {
        inB: 'in_building',
        inF: 'in_floor',
        inR: 'in_room'
      };

      const bId = document.getElementById(map.inB)?.value || '';
      const fId = document.getElementById(map.inF)?.value || '';
      const rId = document.getElementById(map.inR)?.value || '';

      this.selection.bld = null;
      this.selection.flr = null;
      this.selection.rm  = null;

      if(bId){
        const b = this.spatialDB.find(x => String(x.id) === String(bId));
        if(b){
          this.selection.bld = b;
          if(fId){
            const f = (b.floors||[]).find(x => String(x.id) === String(fId));
            if(f){
              this.selection.flr = f;
              if(rId){
                const r = (f.rooms||[]).find(x => String(x.id) === String(rId));
                if(r) this.selection.rm = { id:r.id, name:r.name };
              }
            }
          }
        }
      }

      this.updateStatusText();
    },

    updateModalUI(title, progress, showBack){
      const t = document.getElementById('modal_title');
      const step = document.getElementById('step_label');
      const bar = document.getElementById('step_bar');
      const back = document.getElementById('btn_back');

      if(t) t.innerText = title || 'เลือกตำแหน่ง';
      if(step) step.innerText = `Step ${this.currentStep}`;
      if(bar) bar.style.width = `${progress || 33}%`;
      if(back) back.classList.toggle('hidden', !showBack);

      this.updateStatusText();
      this.setConfirmEnabled(false, 'Confirm Selection');
    },

    updateStatusText(){
      const set = (id, val, active) => {
        const el = document.getElementById(id);
        if(!el) return;
        el.innerText = val || '...';
        el.classList.toggle('opacity-50', !active);
        el.classList.toggle('text-slate-800', !!active);
      };

      set('stat_bld', this.selection.bld?.name, this.selection.bld);
      set('stat_flr', this.selection.flr?.name, this.selection.flr);
      set('stat_rm',  this.selection.rm?.name,  this.selection.rm);
    },

    setConfirmEnabled(enabled, label){
      const btn = document.getElementById('btn_confirm');
      if(!btn) return;
      btn.disabled = !enabled;
      if(label) btn.innerText = label;
    },

    switchView(viewId){
      const ids = ['view_building','view_floor','view_room'];
      ids.forEach(id=>{
        const el = document.getElementById(id);
        if(!el) return;
        if(id === viewId){
          el.classList.remove('hidden');
          el.classList.add('animate-fade-up');
          el.scrollTop = 0;
        }else{
          el.classList.add('hidden');
          el.classList.remove('animate-fade-up');
        }
      });
    },

    // ====== Renderers ======
    renderBuildings(){
      this.currentStep = 1;
      this.updateModalUI('เลือกสาขา / อาคาร', 33, false);

      const c = document.getElementById('view_building');
      if(!c) return;

      c.innerHTML = (this.spatialDB || []).map(b=>{
        const floorsCount = (b.floors || []).length;
        const active = this.selection.bld && String(this.selection.bld.id) === String(b.id);

        return `
          <div class="building-card ${active?'active':''}"
               data-id="${this.escapeHtml(b.id)}"
               onclick="LocationSelector.selectBuilding('${this.escapeHtml(b.id)}')">
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

      // ถ้ามีค่าเดิม ให้คง active
      this._applyActive('view_building', this.selection.bld?.id);
    },

    selectBuilding(id){
      const b = this.spatialDB.find(x => String(x.id) === String(id));
      if(!b) return;

      this.selection.bld = b;
      this.selection.flr = null;
      this.selection.rm  = null;
      this.updateStatusText();

      this._applyActive('view_building', id);

      const floors = b.floors || [];
      if(floors.length === 0){
        this.setConfirmEnabled(true, 'ยืนยันการเลือกอาคาร');
        showSoftAlert('อาคารนี้ไม่มีข้อมูลชั้น สามารถยืนยันได้ทันที', 'success');
      }else{
        this.renderFloors();
      }
    },

    renderFloors(){
      this.currentStep = 2;
      this.updateModalUI(`เลือกชั้น - ${this.selection.bld?.name || ''}`, 66, true);

      const c = document.getElementById('view_floor');
      if(!c) return;

      const floors = this.selection.bld?.floors || [];
      c.innerHTML = floors.map(f=>{
        const roomsCount = (f.rooms || []).length;
        const active = this.selection.flr && String(this.selection.flr.id) === String(f.id);

        return `
          <div class="floor-card ${active?'active':''}"
               data-id="${this.escapeHtml(f.id)}"
               onclick="LocationSelector.selectFloor('${this.escapeHtml(f.id)}')">
            <div class="flex items-start justify-between">
              <div class="floor-icon"><i data-lucide="layers" class="w-5 h-5"></i></div>
              <span class="room-badge">
                <i data-lucide="door-open" class="w-4 h-4"></i>
                ${roomsCount} ห้อง
              </span>
            </div>
            <div class="mt-4">
              <div class="floor-no">${this.escapeHtml(f.name)}</div>
            </div>
            <div class="mt-4 text-[10px] font-bold text-slate-400 uppercase tracking-widest">แตะเพื่อเลือกชั้น</div>
          </div>
        `;
      }).join('');

      this.switchView('view_floor');
      this.safeIcons();
      this._applyActive('view_floor', this.selection.flr?.id);
    },

    selectFloor(id){
      const f = (this.selection.bld?.floors || []).find(x => String(x.id) === String(id));
      if(!f) return;

      this.selection.flr = f;
      this.selection.rm  = null;
      this.updateStatusText();

      this._applyActive('view_floor', id);

      const rooms = f.rooms || [];
      if(rooms.length === 0){
        this.setConfirmEnabled(true, 'ยืนยันการเลือกชั้น');
        showSoftAlert('ชั้นนี้ไม่มีข้อมูลห้อง สามารถยืนยันได้ทันที', 'success');
      }else{
        this.renderRooms();
      }
    },

    renderRooms(){
      this.currentStep = 3;
      this.updateModalUI(`เลือกห้อง - ${this.selection.flr?.name || ''}`, 100, true);

      const c = document.getElementById('view_room');
      if(!c) return;

      const rooms = this.selection.flr?.rooms || [];
      c.innerHTML = rooms.map(r=>{
        const active = this.selection.rm && String(this.selection.rm.id) === String(r.id);
        return `
          <div class="room-card ${active?'active':''}"
               data-id="${this.escapeHtml(r.id)}"
               onclick="LocationSelector.selectRoom('${this.escapeHtml(r.id)}','${this.escapeHtml(r.name)}')">
            <div class="rm-icon"><i data-lucide="door-open" class="w-5 h-5"></i></div>
            <div class="rm-title">${this.escapeHtml(r.name)}</div>
            <div class="rm-sub">แตะเพื่อเลือกห้อง</div>
          </div>
        `;
      }).join('');

      this.switchView('view_room');
      this.safeIcons();
      this._applyActive('view_room', this.selection.rm?.id);

      // เลือกห้องแล้วค่อย confirm
      if(this.selection.rm) this.setConfirmEnabled(true, 'ยืนยันตำแหน่งนี้');
    },

    selectRoom(id, name){
      this.selection.rm = { id, name };
      this.updateStatusText();

      this._applyActive('view_room', id);

      this.setConfirmEnabled(true, 'ยืนยันตำแหน่งนี้');
    },

    _applyActive(viewId, activeId){
      const c = document.getElementById(viewId);
      if(!c) return;
      [...c.children].forEach(el=>{
        const id = el.getAttribute('data-id');
        el.classList.toggle('active', activeId && String(id) === String(activeId));
      });
    },

    filterList(query){
      const q = (query || '').toLowerCase().trim();
      const viewId = (this.currentStep === 1) ? 'view_building'
                  : (this.currentStep === 2) ? 'view_floor'
                  : 'view_room';

      const c = document.getElementById(viewId);
      if(!c) return;
      [...c.children].forEach(item=>{
        item.style.display = item.innerText.toLowerCase().includes(q) ? '' : 'none';
      });
    },

    confirmSelection(){
      const isEdit = (this.target === 'edit');

      const map = isEdit ? {
        inB:'ed_building', inF:'ed_floor', inR:'ed_room',
        dB:'disp_building_edit', dF:'disp_floor_edit', dR:'disp_room_edit',
        ph:'loc_placeholder_edit', sel:'loc_selected_edit'
      } : {
        inB:'in_building', inF:'in_floor', inR:'in_room',
        dB:'disp_building', dF:'disp_floor', dR:'disp_room',
        ph:'loc_placeholder', sel:'loc_selected'
      };

      if(this.selection.bld){
        const inB = document.getElementById(map.inB);
        if(inB) inB.value = this.selection.bld.id;
        const dB = document.getElementById(map.dB);
        if(dB) dB.innerText = this.selection.bld.name;
      }

      if(this.selection.flr){
        const inF = document.getElementById(map.inF);
        if(inF) inF.value = this.selection.flr.id;
        const dF = document.getElementById(map.dF);
        if(dF) dF.innerText = this.selection.flr.name;
      }else{
        document.getElementById(map.inF)?.setAttribute('value','');
        const dF = document.getElementById(map.dF);
        if(dF) dF.innerText = '-';
      }

      if(this.selection.rm){
        const inR = document.getElementById(map.inR);
        if(inR) inR.value = this.selection.rm.id;
        const dR = document.getElementById(map.dR);
        if(dR) dR.innerText = this.selection.rm.name;
      }else{
        document.getElementById(map.inR)?.setAttribute('value','');
        const dR = document.getElementById(map.dR);
        if(dR) dR.innerText = '-';
      }

      // toggle placeholder/selected
      document.getElementById(map.ph)?.classList.add('hidden');
      document.getElementById(map.sel)?.classList.remove('hidden');

      this.closeModal();
    },

    bindEvents(){
      if(this._bound) return;
      this._bound = true;

      const m = document.getElementById('spatial_modal');
      if(!m) return;

      document.getElementById('btn_confirm')?.addEventListener('click', ()=> this.confirmSelection());

      document.getElementById('btn_back')?.addEventListener('click', ()=>{
        if(this.currentStep === 3) this.renderFloors();
        else if(this.currentStep === 2) this.renderBuildings();
      });

      document.getElementById('btn_close_modal')?.addEventListener('click', ()=> this.closeModal());
      document.getElementById('modal_backdrop')?.addEventListener('click', ()=> this.closeModal());

      document.getElementById('spatial_search')?.addEventListener('input', (e)=>{
        this.filterList(e.target.value);
      });
    },

    injectStyles(){
      if(document.getElementById('ls-styles-modern')) return;

      const s = document.createElement('style');
      s.id = 'ls-styles-modern';
      s.textContent = `
        :root{ --primary:${LS_PRIMARY}; }
        .text-primary{ color: var(--primary); }

        .modal-blur{
          backdrop-filter: blur(12px) saturate(180%);
          background-color: rgba(15, 23, 42, .40);
        }

        .btn-gradient{
          background: linear-gradient(to right, ${LS_PRIMARY}, #04ADFF);
          color: #fff;
          transition: all .25s ease;
        }
        .btn-gradient:hover{
          box-shadow: 0 10px 20px -5px rgba(0,107,159,.35);
          transform: translateY(-1px);
        }

        .step-indicator{
          height:6px;border-radius:999px;background:#f1f5f9;width:128px;position:relative;overflow:hidden;
        }
        .step-progress{
          position:absolute;left:0;top:0;height:100%;background:var(--primary);transition:width .4s ease;width:33%;
        }

        @keyframes fadeUp { from{opacity:0; transform:translateY(10px);} to{opacity:1; transform:translateY(0);} }
        .animate-fade-up{ animation: fadeUp .35s ease forwards; }

        /* Building */
        .building-card{
          border:1px solid #e2e8f0;border-radius:1.75rem;background:#fff;padding:18px;cursor:pointer;
          transition:all .25s ease;min-height:140px;
          box-shadow:0 12px 22px rgba(2,132,199,.06);
        }
        .building-card:hover{ transform:translateY(-2px); border-color:rgba(0,107,159,.35); box-shadow:0 18px 30px rgba(0,107,159,.10); }
        .building-card .bld-icon{
          width:44px;height:44px;border-radius:14px;display:flex;align-items:center;justify-content:center;
          background:rgba(0,107,159,.08);color:var(--primary);border:1px solid rgba(0,107,159,.15);
        }
        .building-card .bld-title{ font-size:15px;font-weight:900;color:#0f172a;line-height:1.25;white-space:normal;word-break:break-word; }
        .building-card .bld-sub{ font-size:12px;font-weight:700;color:#64748b; }
        .building-card .bld-badge{
          display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;
          background:#f1f5f9;border:1px solid #e2e8f0;font-size:11px;font-weight:800;color:#475569;white-space:nowrap;
        }
        .building-card.active{
          border-color:var(--primary);
          box-shadow:0 18px 35px rgba(0,107,159,.18);
          outline:2px solid rgba(0,107,159,.10);
        }

        /* Floor */
        .floor-card{
          border:1px solid #e2e8f0;border-radius:1.75rem;background:#fff;padding:18px;cursor:pointer;
          transition:all .25s ease;min-height:140px;
          box-shadow:0 12px 22px rgba(2,132,199,.06);
        }
        .floor-card:hover{ transform:translateY(-2px); border-color:rgba(0,107,159,.35); box-shadow:0 18px 30px rgba(0,107,159,.10); }
        .floor-card .floor-icon{
          width:44px;height:44px;border-radius:14px;display:flex;align-items:center;justify-content:center;
          background:rgba(0,107,159,.08);color:var(--primary);border:1px solid rgba(0,107,159,.15);
        }
        .floor-card .floor-no{ font-size:32px;font-weight:900;line-height:1;color:#0f172a; }
        .floor-card .room-badge{
          display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:999px;
          background:#f1f5f9;border:1px solid #e2e8f0;font-size:11px;font-weight:800;color:#475569;white-space:nowrap;
        }
        .floor-card.active{
          border-color:var(--primary);
          box-shadow:0 18px 35px rgba(0,107,159,.18);
          outline:2px solid rgba(0,107,159,.10);
        }

        /* Room */
        .room-card{
          border:1px solid #e2e8f0;border-radius:1.75rem;background:#fff;padding:18px;cursor:pointer;
          transition:all .25s ease;min-height:180px;
          display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;
          box-shadow:0 12px 22px rgba(2,132,199,.06);
        }
        .room-card:hover{ transform:translateY(-2px); border-color:rgba(0,107,159,.35); box-shadow:0 18px 30px rgba(0,107,159,.10); }
        .room-card .rm-icon{
          width:46px;height:46px;border-radius:16px;display:flex;align-items:center;justify-content:center;
          background:rgba(0,107,159,.08);color:var(--primary);border:1px solid rgba(0,107,159,.15);
          margin:0 auto 10px auto;
        }
        .room-card .rm-title{ font-size:14px;font-weight:900;color:#0f172a;line-height:1.25;white-space:normal;word-break:break-word; }
        .room-card .rm-sub{ margin-top:6px;font-size:11px;font-weight:800;color:#64748b; }
        .room-card.active{
          border-color:var(--primary);
          background:rgba(0,107,159,.04);
          box-shadow:0 18px 35px rgba(0,107,159,.18);
          outline:2px solid rgba(0,107,159,.10);
        }
      `;
      document.head.appendChild(s);
    },

    injectModal(){
      if(document.getElementById('spatial_modal')) return;

      const html = `
        <div id="spatial_modal"
             class="modal-blur fixed inset-0 z-[10000] flex items-center justify-center hidden opacity-0 transition-all duration-300">
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

              <button id="btn_close_modal"
                      class="w-12 h-12 rounded-full border border-slate-100 flex items-center justify-center text-slate-400 hover:rotate-90 hover:bg-slate-50 transition-all duration-300">
                <i data-lucide="x" class="w-6 h-6"></i>
              </button>
            </div>

            <div class="px-8 mb-6 shrink-0">
              <div class="relative">
                <i data-lucide="search" class="absolute left-5 top-4 w-5 h-5 text-slate-400"></i>
                <input type="text" id="spatial_search"
                       class="w-full bg-slate-50 rounded-2xl py-4 pl-14 pr-6 text-sm font-medium border-none outline-none focus:ring-2 focus:ring-primary/20"
                       placeholder="ค้นหา...">
              </div>
            </div>

            <div class="flex-1 overflow-hidden relative px-8 pb-8">
              <div id="view_building" class="h-full overflow-y-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pb-20"></div>
              <div id="view_floor" class="hidden h-full overflow-y-auto grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 pb-20"></div>
              <div id="view_room" class="hidden h-full overflow-y-auto grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 pb-20"></div>
            </div>

            <div class="absolute bottom-0 left-0 w-full h-24 bg-white/90 backdrop-blur-md border-t border-slate-100 px-8 flex items-center justify-between z-10">
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

              <button id="btn_confirm" disabled
                      class="btn-gradient px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest disabled:opacity-50">
                Confirm Selection
              </button>
            </div>

          </div>
        </div>
      `;
      document.body.insertAdjacentHTML('beforeend', html);

      // หลัง inject ค่อย bind
      this.bindEvents();
      this.safeIcons();
    },
  };

  document.addEventListener('DOMContentLoaded', ()=> LocationSelector.init());
})();

// ทรัพย์สิน<br>
const AssetSelector = {
  apiEndpoint: 'get_assets.php',
  typeId: 0,

  getBuilding() {
    return (document.getElementById('ed_building')?.value || '').trim();
  },

  // ✅ รับได้ทั้ง row object หรือ {ass_id:..}
  init(existingData) {
    const building = this.getBuilding();
    if (!building) return this.clear();

    const assId =
      String(existingData?.machine_id ?? '').trim() ||
      String(existingData?.asset_ass_id ?? '').trim() ||   // จาก SQL ของคุณ
      String(existingData?.ass_id ?? '').trim() ||
      String(document.getElementById('ed_asset_id')?.value ?? '').trim() ||
      String(document.getElementById('ed_asset')?.value ?? '').trim();

    if (!assId) return this.clear();

    // ✅ sync machine_id ลง input ตามที่คุณต้องการ
    const edAsset = document.getElementById('ed_asset');
    if (edAsset) edAsset.value = assId;
    const edAssetId = document.getElementById('ed_asset_id');
    if (edAssetId) edAssetId.value = assId;

    this.loadOne(assId);
  },

  loadOne(assId) {
    const building = this.getBuilding();
    if (!building || !assId) return this.clear();

    const url = `${this.apiEndpoint}?action=one&ass_id=${encodeURIComponent(assId)}&building=${encodeURIComponent(building)}`;

    fetch(url)
      .then(r => r.json())
      .then(json => {
        if (json?.ok && json?.data) this.renderSelectedCard(json.data);
        else this.clear();
      })
      .catch(() => this.clear());
  },

  search(keyword) {
    const dropdown = document.getElementById('asset_dropdown');
    const loading  = document.getElementById('asset_loading');
    const building = this.getBuilding();

    if (!dropdown || !loading) return;

    if (!building) {
      dropdown.innerHTML = `<div class="p-3 text-center text-xs text-rose-500 font-bold">กรุณาเลือกอาคารก่อน</div>`;
      dropdown.classList.remove('hidden');
      return;
    }

    if (!keyword || keyword.length < 2) {
      dropdown.classList.add('hidden');
      return;
    }

    loading.classList.remove('hidden');

    const url = `${this.apiEndpoint}?action=search&q=${encodeURIComponent(keyword)}&building=${encodeURIComponent(building)}&type=${encodeURIComponent(this.typeId)}&limit=10`;

    fetch(url)
      .then(r => r.json())
      .then(json => this.renderList(json?.data || []))
      .catch(console.error)
      .finally(() => loading.classList.add('hidden'));
  },

  renderList(items) {
    const dropdown = document.getElementById('asset_dropdown');
    if (!dropdown) return;

    dropdown.innerHTML = '';

    if (!items.length) {
      dropdown.innerHTML = `<div class="p-3 text-center text-xs text-slate-400">ไม่พบข้อมูล</div>`;
      dropdown.classList.remove('hidden');
      return;
    }

    items.forEach(item => {
      const imgHtml = item.fileUpload1_url
	  ? `<img src="${item.fileUpload1_url}" class="w-8 h-8 rounded object-cover border border-slate-200">`
	  : `<div class="w-8 h-8 rounded bg-slate-100 flex items-center justify-center text-slate-400">
		   <i data-lucide="box" class="w-3.5 h-3.5"></i>
		 </div>`;

      const el = document.createElement('div');
      el.className = 'p-2.5 hover:bg-sky-50 cursor-pointer flex items-center gap-3 transition-colors border-b border-slate-50 last:border-0';
      el.onclick = () => this.select(item);

      el.innerHTML = `
        ${imgHtml}
        <div class="flex-1 min-w-0">
          <div class="flex justify-between items-center gap-2">
            <p class="text-xs font-bold text-slate-700 truncate">${item.asset_name || '-'}</p>
            <span class="text-[10px] bg-slate-100 text-slate-500 px-1 rounded shrink-0">${item.ass_code || '-'}</span>
          </div>
          <p class="text-[10px] text-slate-400 truncate">SN: ${item.asset_sn || '-'}</p>
        </div>
      `;
      dropdown.appendChild(el);
    });

    dropdown.classList.remove('hidden');
    window.lucide?.createIcons?.();
  },

  select(item) {
    this.renderSelectedCard(item);
    document.getElementById('asset_dropdown')?.classList.add('hidden');
  },

  renderSelectedCard(item) {
    // ✅ sync id ลง 2 ช่อง
    document.getElementById('ed_asset') && (document.getElementById('ed_asset').value = item.ass_id || '');
    document.getElementById('ed_asset_id') && (document.getElementById('ed_asset_id').value = item.ass_id || '');
    document.getElementById('ed_asset_name_submit') && (document.getElementById('ed_asset_name_submit').value = item.asset_name || '');

    // ✅ UI card
    document.getElementById('sel_asset_name').innerText  = item.asset_name || '-';
    document.getElementById('sel_asset_code').innerText  = item.ass_code || 'NO CODE';
    document.getElementById('sel_asset_sn').innerText    = 'SN: ' + (item.asset_sn || '-');
    document.getElementById('sel_asset_model').innerText = 'Model: ' + (item.asset_model || '-');

    const imgEl  = document.getElementById('sel_asset_img');
	const iconEl = document.getElementById('sel_asset_icon');
	
	if (imgEl && iconEl) {
	  const url = item.fileUpload1_url || '';
	  if (url) {
		imgEl.src = url;              // ✅ ใช้ URL ที่ API ส่งมา
		imgEl.classList.remove('hidden');
		iconEl.classList.add('hidden');
	  } else {
		imgEl.classList.add('hidden');
		iconEl.classList.remove('hidden');
	  }
	}

    document.getElementById('asset_search_wrapper')?.classList.add('hidden');
    document.getElementById('asset_selected_card')?.classList.remove('hidden');

    window.lucide?.createIcons?.();
  },

  clear() {
    document.getElementById('ed_asset') && (document.getElementById('ed_asset').value = '');
    document.getElementById('ed_asset_id') && (document.getElementById('ed_asset_id').value = '');
    document.getElementById('ed_asset_name_submit') && (document.getElementById('ed_asset_name_submit').value = '');
    document.getElementById('asset_search_input') && (document.getElementById('asset_search_input').value = '');

    document.getElementById('asset_selected_card')?.classList.add('hidden');
    document.getElementById('asset_search_wrapper')?.classList.remove('hidden');

    setTimeout(() => document.getElementById('asset_search_input')?.focus?.(), 100);
  }
};

document.addEventListener('click', (e) => {
  const wrap = document.getElementById('asset_search_wrapper');
  if (wrap && !wrap.contains(e.target)) {
    document.getElementById('asset_dropdown')?.classList.add('hidden');
  }
});


  </script>
 
  <!-- =========================
       ✅ Drawer MUST be inside body
       ========================= -->
  <div id="ud_backdrop" class="fixed inset-0 z-[9998] hidden">
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px]"></div>
  </div>

<aside id="ud_drawer"
  class="fixed top-0 right-0
         h-[100dvh] max-h-[100dvh]
         w-full md:w-[92vw] lg:w-[78vw] xl:w-[70vw] 2xl:w-[64vw]
         max-w-[1400px]
         z-[9999] translate-x-full transition-transform duration-300 ease-out
         bg-white border-l border-slate-200 shadow-2xl flex flex-col">

  <!-- Header -->
  <div class="px-6 py-4 border-b border-slate-100 flex items-start justify-between">
    <div class="min-w-0">
      <div class="flex items-center gap-2">
        <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-700 flex items-center justify-center">
          <i data-lucide="clipboard-list" class="w-5 h-5"></i>
        </div>
        <div class="min-w-0">
          <div class="text-sm font-extrabold text-slate-800 leading-tight" id="ud_title">รายละเอียดงานซ่อม</div>
          <div class="text-[11px] font-bold text-slate-400" id="ud_subtitle">ID: -</div>
        </div>
      </div>
    </div>

    <button type="button" onClick="closeDrawer()"
      class="w-10 h-10 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 hover:rotate-90 transition">
      <i data-lucide="x" class="w-5 h-5 mx-auto"></i>
    </button>
  </div>

  <!-- Tabs -->
  <div class="px-6 pt-4">
    <div class="flex items-center gap-2 p-1 rounded-2xl bg-slate-50 border border-slate-100">
      <button type="button" class="ud_tab active" data-tab="edit" onClick="switchDrawerTab('edit')">
        <i data-lucide="pencil" class="w-4 h-4"></i> แก้ไขงาน
      </button>
      <button type="button" class="ud_tab" data-tab="accept" onClick="switchDrawerTab('accept')">
        <i data-lucide="user-check" class="w-4 h-4"></i> รับงาน
      </button>
      <button type="button" class="ud_tab" data-tab="close" onClick="switchDrawerTab('close')">
        <i data-lucide="check-circle-2" class="w-4 h-4"></i> ปิดงาน
      </button>
    </div>
  </div>

  <!-- Body -->
<div id="ud_body_scroll" class="flex-1 min-h-0 overflow-y-auto bg-slate-50/50">

<section class="ud_panel px-6 py-5 max-w-5xl mx-auto space-y-4" data-panel="edit">

  <!-- CARD 1: ข้อมูลการรับแจ้ง -->
  <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
	  <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-100">
      <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
        <i data-lucide="file-text" class="w-4 h-4"></i>
      </div>
      <h3 class="text-sm font-bold text-slate-800">ข้อมูลการรับแจ้ง</h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
      <div>
        <label class="text-[11px] font-extrabold text-slate-500">วันที่แจ้ง</label>
        <input id="ed_report_date" type="date"
          class="w-full mt-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none">
      </div>

      <div>
        <label class="text-[11px] font-extrabold text-slate-500">เวลาที่แจ้ง</label>
        <input id="ed_report_time" type="time"
          class="w-full mt-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none">
      </div>

      <div>
        <label class="text-[11px] font-extrabold text-slate-500">ผู้แจ้ง</label>
        <input id="ed_name" type="text"
          class="w-full mt-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none"
          placeholder="ชื่อ-นามสกุล">
      </div>

      <div>
        <label class="text-[11px] font-extrabold text-slate-500">เบอร์โทร</label>
        <input id="ed_phone" type="tel"
          class="w-full mt-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none"
          placeholder="0xx-xxxxxxx">
      </div>
    </div>
  </div>

  <!-- CARD 2: ตำแหน่งและรายละเอียดงาน -->
 <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
  <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-100">
      <div class="w-8 h-8 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center">
        <i data-lucide="map-pin" class="w-4 h-4"></i>
      </div>
      <h3 class="text-sm font-bold text-slate-800">ตำแหน่งและรายละเอียดงาน</h3>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

      <!-- LEFT -->
      <div class="lg:col-span-8 space-y-4 min-w-0">
        <!-- Location -->
        <div id="loc_trigger_edit"
          class="w-full bg-white border border-slate-200 p-4 rounded-3xl transition-all">

          <div id="loc_placeholder_edit">
            <div class="flex items-center gap-3 cursor-pointer"
              onclick="LocationSelector.openModal('edit','building')">
              <div class="w-11 h-11 rounded-2xl bg-sky-100 flex items-center justify-center text-sky-600 shrink-0">
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

          <div id="loc_selected_edit" class="hidden">
            <div class="flex items-center justify-between gap-3">
              <div class="flex flex-wrap items-center gap-2 min-w-0">

                <!-- อาคาร -->
                <span role="button"
                  onclick="event.stopPropagation(); LocationSelector.openModal('edit','building');"
                  class="group relative cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-2xl
                         bg-sky-50 border border-sky-100 text-slate-800 font-extrabold text-[12px] min-w-0
                         hover:bg-sky-100/60 hover:border-sky-200 transition">
                  <i data-lucide="building-2" class="w-4 h-4 text-sky-700 shrink-0"></i>
                  <span class="text-[10px] font-black text-slate-400 uppercase">อาคาร</span>
                  <span id="disp_building_edit" class="truncate max-w-[260px]">-</span>
                </span>

                <!-- ชั้น -->
                <span role="button"
                  onclick="event.stopPropagation(); LocationSelector.openModal('edit','floor');"
                  class="group relative cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-2xl
                         bg-white border border-slate-200 text-slate-700 font-extrabold text-[12px]
                         hover:bg-slate-50 hover:border-slate-300 transition pr-9">
                  <i data-lucide="layers" class="w-4 h-4 text-sky-700"></i>
                  <span class="text-[10px] font-black text-slate-400 uppercase">ชั้น</span>
                  <span id="disp_floor_edit">-</span>
                  <button type="button"
                    onclick="event.stopPropagation(); LocationSelector.clearFloor?.('edit');"
                    class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full
                           bg-slate-100 border border-slate-200 text-slate-500
                           opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto
                           hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition"
                    title="ลบชั้น">
                    <i data-lucide="x" class="w-3.5 h-3.5 mx-auto"></i>
                  </button>
                </span>

                <!-- ห้อง -->
                <span role="button"
                  onclick="event.stopPropagation(); LocationSelector.openModal('edit','room');"
                  class="group relative cursor-pointer inline-flex items-center gap-2 px-3 py-2 rounded-2xl
                         bg-white border border-slate-200 text-slate-700 font-extrabold text-[12px]
                         hover:bg-slate-50 hover:border-slate-300 transition pr-9">
                  <i data-lucide="door-open" class="w-4 h-4 text-sky-700"></i>
                  <span class="text-[10px] font-black text-slate-400 uppercase">ห้อง</span>
                  <span id="disp_room_edit">-</span>
                  <button type="button"
                    onclick="event.stopPropagation(); LocationSelector.clearRoom?.('edit');"
                    class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 rounded-full
                           bg-slate-100 border border-slate-200 text-slate-500
                           opacity-0 pointer-events-none group-hover:opacity-100 group-hover:pointer-events-auto
                           hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition"
                    title="ลบห้อง">
                    <i data-lucide="x" class="w-3.5 h-3.5 mx-auto"></i>
                  </button>
                </span>
              </div>

              <span class="text-[10px] font-extrabold text-emerald-600 bg-emerald-50 border border-emerald-100 rounded-full px-3 py-1 inline-flex items-center gap-1.5 shrink-0">
                <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Selected
              </span>
            </div>
          </div>

          <input type="hidden" id="ed_building" />
          <input type="hidden" id="ed_floor" />
          <input type="hidden" id="ed_room" />
        </div>

        <!-- Asset -->
        <div class="relative z-20 space-y-2">
  
  <label class="text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
    ทรัพย์สิน/เครื่องจักร (Asset)
  </label>

  <input type="text" id="ed_asset" name="ed_asset" value=""> <input type="hidden" id="ed_asset_name_submit" name="asset_name" value="">

  <div id="asset_search_wrapper" class="relative group">
    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
      <i data-lucide="search" class="w-4 h-4 text-slate-400 group-focus-within:text-sky-500 transition-colors"></i>
    </div>
    
    <input type="text" id="asset_search_input"
      autocomplete="off"
      class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-slate-200 bg-slate-50 
             text-sm font-semibold text-slate-700 placeholder:text-slate-400 placeholder:font-normal
             focus:bg-white focus:border-sky-400 focus:ring-4 focus:ring-sky-50 outline-none transition-all shadow-sm"
      placeholder="พิมพ์ค้นหาเพื่อเปลี่ยนอุปกรณ์ (ชื่อ, รหัส, S/N)..."
      onkeyup="AssetSelector.search(this.value)">

    <div id="asset_loading" class="hidden absolute inset-y-0 right-3 flex items-center">
      <svg class="animate-spin h-4 w-4 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
      </svg>
    </div>

    <div id="asset_dropdown" 
         class="hidden absolute top-full left-0 right-0 mt-2 bg-white rounded-xl border border-slate-100 shadow-xl max-h-60 overflow-y-auto divide-y divide-slate-50 z-50">
    </div>
  </div>

  <div id="asset_selected_card" class="hidden relative bg-white border border-sky-200 rounded-xl p-3 shadow-[0_4px_12px_-4px_rgba(14,165,233,0.15)] group transition-all hover:border-sky-300">
    
    <div class="flex items-start gap-3">
      <div class="w-12 h-12 rounded-lg bg-slate-50 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center relative">
         <img id="sel_asset_img" src="" class="w-full h-full object-cover hidden">
         <i id="sel_asset_icon" data-lucide="package" class="w-5 h-5 text-slate-400"></i>
      </div>

      <div class="flex-1 min-w-0 pt-0.5">
        <div class="flex items-center gap-2 mb-1">
           <span id="sel_asset_code" class="text-[10px] font-black bg-sky-50 text-sky-700 border border-sky-100 px-1.5 py-0.5 rounded uppercase tracking-wider">
             CODE
           </span>
           <span class="text-[10px] text-slate-400 font-mono truncate" id="sel_asset_sn">SN: -</span>
        </div>
        <h4 id="sel_asset_name" class="text-sm font-bold text-slate-800 leading-tight truncate">Asset Name</h4>
        <p id="sel_asset_model" class="text-[11px] text-slate-500 mt-0.5 truncate">Model: -</p>
      </div>

      <div class="flex flex-col gap-1">
        <button type="button" onclick="AssetSelector.clear()" title="เปลี่ยนอุปกรณ์"
          class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-50 text-slate-400 hover:bg-rose-50 hover:text-rose-500 border border-transparent hover:border-rose-100 transition">
          <i data-lucide="arrow-left-right" class="w-4 h-4"></i>
        </button>
      </div>
    </div>
    
    <div class="absolute -top-1.5 -right-1.5 bg-sky-500 text-white rounded-full p-0.5 border-2 border-white shadow-sm">
        <i data-lucide="check" class="w-2.5 h-2.5"></i>
    </div>
  </div>

</div>
 
        <!-- Problem -->
        <div>
          <label class="text-[11px] font-extrabold text-slate-500">อาการเสีย/ปัญหาที่พบ</label>
          <textarea id="ed_problem" rows="4"
            class="w-full mt-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none"
            placeholder="รายละเอียดปัญหา..."></textarea>
        </div>
      </div>

      <!-- RIGHT (✅ sticky + อยู่ในกรอบ) -->
      <div class="lg:col-span-4 space-y-4 min-w-0 min-h-0 lg:sticky lg:top-6 self-start flex flex-col">

        <!-- Urgency (✅ ไม่ใช้ h-full แล้ว / เลื่อนในตัวเอง) -->
        <div class="rounded-3xl border border-slate-200 bg-white p-4
                    max-h-[calc(100vh-220px)] overflow-y-auto">
          <div class="flex items-center justify-between">
            <label class="text-[11px] font-extrabold text-slate-500">ความเร่งด่วน</label>
            <span id="urg_badge"
              class="text-[10px] font-extrabold px-2.5 py-1 rounded-full border bg-slate-50 text-slate-600 border-slate-200">
              ปานกลาง
            </span>
          </div>

          <select id="ed_urgency" class="hidden">
            <option value="low">ต่ำ</option>
            <option value="medium" selected>ปานกลาง</option>
            <option value="high">สูง</option>
            <option value="critical">เร่งด่วนมาก</option>
          </select>

          <div class="mt-3 grid grid-cols-2 gap-2" id="urg_pills">
            <button type="button" class="urg-pill" data-value="low">
              <span class="urg-dot urg-low"></span> ต่ำ
            </button>
            <button type="button" class="urg-pill urg-active" data-value="medium">
              <span class="urg-dot urg-med"></span> ปานกลาง
            </button>
            <button type="button" class="urg-pill" data-value="high">
              <span class="urg-dot urg-high"></span> สูง
            </button>
            <button type="button" class="urg-pill" data-value="critical">
              <span class="urg-dot urg-crit"></span> เร่งด่วนมาก
            </button>
          </div>

          <div class="mt-3 text-[10px] font-semibold text-slate-400">
            * คลิกเลือกเพื่อกำหนดระดับ (มีสี + hover)
          </div>
        </div>

        <!-- Images (กันหลุด) -->
        <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-3 w-full overflow-hidden">
          <div class="flex items-center justify-between">
            <div class="text-[11px] font-extrabold text-slate-700 flex items-center gap-2">
              <i data-lucide="images" class="w-4 h-4 text-sky-700"></i>
              รูปที่แจ้งซ่อม (สูงสุด 5 รูป)
            </div>
            <span class="text-[11px] font-extrabold text-slate-400" id="ed_img_count">0/5</span>
          </div>

          <div class="mt-2 flex items-center gap-2 min-w-0">
            <input id="ed_images" type="file" class="hidden" multiple accept="image/*">
            <button type="button" onclick="document.getElementById('ed_images').click()"
              class="px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-[11px] font-extrabold hover:bg-slate-50">
              + เพิ่มรูป
            </button>
            <div class="text-[11px] text-slate-400 font-semibold truncate">
              คลิกที่รูปเพื่อดู / กด X เพื่อลบ
            </div>
          </div>

          <div id="ed_preview" class="mt-3 flex gap-2 overflow-x-auto max-w-full min-w-0 pr-1"></div>

          <p id="ed_img_err" class="hidden mt-2 text-[11px] text-rose-600 font-bold">
            รวมรูปได้สูงสุด 5 รูปเท่านั้น
          </p>
        </div>

      </div>
    </div>
  </div>

</section>
 

      <!-- TAB: ACCEPT -->
      <section class="ud_panel hidden" data-panel="accept">
        <div class="rounded-2xl border border-slate-100 bg-white p-4 space-y-4">
          <div class="text-xs font-extrabold text-slate-700 flex items-center gap-2">
            <i data-lucide="calendar-check" class="w-4 h-4 text-sky-700"></i>
            รับงาน (กำหนดเข้าดำเนินการ)
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-[11px] font-extrabold text-slate-500">วันที่เข้าดำเนินการ</label>
              <input id="ac_date" type="date" class="w-full mt-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none">
            </div>
            <div>
              <label class="text-[11px] font-extrabold text-slate-500">เวลาเข้าดำเนินการ</label>
              <input id="ac_time" type="time" class="w-full mt-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none">
            </div>
          </div>

          <div>
            <label class="text-[11px] font-extrabold text-slate-500">เลือกช่างเข้าดำเนินการ</label>
            <select id="ac_tech" class="w-full mt-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 outline-none">
              <option value="">-- เลือกช่าง --</option>
              <option value="ช่างส่วนกลาง">ช่างส่วนกลาง</option>
              <option value="ช่าง A">ช่าง A</option>
              <option value="ช่าง B">ช่าง B</option>
            </select>
            <p class="mt-2 text-[11px] text-slate-400 font-semibold">* ของจริงให้โหลดจาก DB เป็น list ช่าง</p>
          </div>

          <div>
            <label class="text-[11px] font-extrabold text-slate-500">บันทึกเพิ่มเติม (รับงาน)</label>
            <textarea id="ac_note" rows="3"
              class="w-full mt-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none"
              placeholder="เช่น นัดหมาย, ต้องเตรียมอุปกรณ์..."></textarea>
          </div>
        </div>
      </section>

      <!-- TAB: CLOSE -->
      <section class="ud_panel hidden" data-panel="close">
        <div class="rounded-2xl border border-slate-100 bg-white p-4 space-y-4">
          <div class="text-xs font-extrabold text-slate-700 flex items-center gap-2">
            <i data-lucide="badge-check" class="w-4 h-4 text-sky-700"></i>
            ปิดงาน (ข้อมูลการปิดเคส)
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-[11px] font-extrabold text-slate-500">วันที่เสร็จ</label>
              <input id="cl_date" type="date" class="w-full mt-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none">
            </div>
            <div>
              <label class="text-[11px] font-extrabold text-slate-500">เวลาเสร็จ</label>
              <input id="cl_time" type="time" class="w-full mt-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-[11px] font-extrabold text-slate-500">ชนิดของการบริการ</label>
              <select id="cl_service_type" class="w-full mt-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 outline-none">
                <option value="">-- เลือก --</option>
                <option value="ซ่อมบำรุง">ซ่อมบำรุง</option>
                <option value="ติดตั้ง">ติดตั้ง</option>
                <option value="ตรวจเช็ค">ตรวจเช็ค</option>
              </select>
            </div>
            <div>
              <label class="text-[11px] font-extrabold text-slate-500">ประเภทงาน</label>
              <select id="cl_job_type" class="w-full mt-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 outline-none">
                <option value="">-- เลือก --</option>
                <option value="ไฟฟ้า">ไฟฟ้า</option>
                <option value="ประปา">ประปา</option>
                <option value="แอร์">แอร์</option>
                <option value="ทั่วไป">ทั่วไป</option>
              </select>
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between">
              <label class="text-[11px] font-extrabold text-slate-500">รูปภาพปิดงาน (สูงสุด 5 รูป)</label>
              <span class="text-[11px] font-extrabold text-slate-400" id="cl_img_count">0/5</span>
            </div>

            <div class="mt-2 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-3">
              <input id="cl_images" type="file" class="hidden" multiple accept="image/*">
              <div class="flex items-center gap-2">
                <button type="button" onClick="document.getElementById('cl_images').click()"
                  class="px-3 py-2 rounded-xl bg-white border border-slate-200 text-slate-700 text-[11px] font-extrabold hover:bg-slate-50">
                  + เพิ่มรูป
                </button>
                <div class="text-[11px] text-slate-400 font-semibold">ลากมาวางก็ได้</div>
              </div>
              <div id="cl_preview" class="mt-3 flex gap-2 overflow-x-auto"></div>
              <p id="cl_img_err" class="hidden mt-2 text-[11px] text-rose-600 font-bold">เลือกได้สูงสุด 5 รูปเท่านั้น</p>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-3">
            <div class="flex items-center justify-between">
              <div class="text-[11px] font-extrabold text-slate-700">รายการอะไหล่/วัสดุสิ้นเปลือง</div>
              <button type="button" onClick="addPartRow()"
                class="text-[11px] font-extrabold text-sky-700 hover:underline inline-flex items-center gap-1">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> เพิ่มรายการ
              </button>
            </div>
            <div id="parts_wrap" class="mt-3 space-y-2"></div>
          </div>

          <div>
            <label class="text-[11px] font-extrabold text-slate-500">บันทึกการดำเนินการเสร็จ</label>
            <textarea id="cl_note" rows="4"
              class="w-full mt-1 px-3 py-2 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white outline-none"
              placeholder="เช่น เปลี่ยนอะไหล่..., แก้ไขจุดรั่ว..., ทดสอบแล้วใช้งานได้ปกติ"></textarea>
          </div>
        </div>
      </section>
    </div>
	</div>
    <!-- Footer -->
    <div class="px-6 py-4 border-t border-slate-100 bg-white flex items-center justify-between">
      <button type="button" onClick="closeDrawer()"
        class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-[12px] font-extrabold hover:bg-slate-50">ปิด</button>

     <button type="button"
		  onClick="saveDrawer()"
		  class="btn-gradient px-4 py-2 rounded-2xl font-bold shadow-xl shadow-sky-100 transition-all transform active:scale-95 flex items-center gap-2">
		  <i data-lucide="check" class="w-4 h-4"></i>
		  บันทึกข้อมูล
		</button>

    </div>
  </aside>

</body>
</html>
