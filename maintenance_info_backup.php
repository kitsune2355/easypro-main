<?php
@session_start();
include "config_ctrl/checksession.php";
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
       ✅ Carousel Styles (SweetAlert)
       ========================= */
    .swal-carousel-container {
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
    .carousel-item {
      display: none;
      width: 100%;
      height: 100%;
      padding: 12px;
      animation: fadeIn 0.25s ease;
    }
    .carousel-item.active {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* wrapper สำหรับ zoom/pan */
    .rr-imgwrap{
      width: 100%;
      height: 100%;
      overflow: hidden;
      border-radius: 14px;
      display:flex;
      align-items:center;
      justify-content:center;
      background: #fff;
      border: 1px solid #e2e8f0;
      position: relative;
    }
    .rr-imgwrap img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      border-radius: 14px;
      background: #fff;
      user-select: none;
      -webkit-user-drag: none;
    }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    /* cursor ตอนลาก */
    .rr-grab{ cursor: grab; }
    .rr-grabbing{ cursor: grabbing; }

    /* ✅ dots */
    .rr-dots{
      position:absolute;
      bottom: 14px;
      left: 50%;
      transform: translateX(-50%);
      display:flex;
      gap: 6px;
      z-index: 25;
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(255,255,255,0.70);
      border: 1px solid rgba(226,232,240,0.9);
      backdrop-filter: blur(8px);
    }
    .rr-dot{
      width: 8px; height: 8px;
      border-radius: 999px;
      cursor: pointer;
      transition: all .15s;
      background: #cbd5e1;
    }
    .rr-dot.active{ background: #0ea5e9; }
    .rr-dot:hover{ transform: scale(1.25); }

    /* =========================
       ✅ Toolbar รวมกลุ่มเดียวกัน
       (fullscreen + prev/next + zoom + fit + rotate + %)
       ========================= */
   .rr-toolbar{
  position:absolute;
  left: 50%;
  bottom: 56px;                 /* อยู่เหนือ dots */
  transform: translateX(-50%);
  z-index: 40;
  display:flex;
  gap: 6px;
  padding: 6px 8px;
  border-radius: 999px;
  background: rgba(255,255,255,0.78);
  border: 1px solid rgba(226,232,240,0.9);
  box-shadow: 0 10px 25px rgba(2, 8, 23, 0.10);
  backdrop-filter: blur(8px);
}

/* ปุ่มเล็กลง */
.rr-toolbtn{
  width: 34px;
  height: 34px;
  border-radius: 12px;
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  color:#334155;
  background: rgba(255,255,255,0.92);
  border: 1px solid rgba(226,232,240,0.95);
  transition: all .15s;
}
.rr-toolbtn:hover{ transform: scale(1.06); background: rgba(255,255,255,0.98); }

.rr-toolsep{
  width:1px;
  background: rgba(226,232,240,0.9);
  margin: 4px 2px;
}

/* label เล็กลง */
.rr-zoomlabel{
  display:flex;
  align-items:center;
  font-size:11px;
  font-weight:800;
  color:#0f172a;
  padding:0 8px;
  min-width: 52px;
  justify-content: center;
}

/* dots ให้ชิดล่างสุดกว่า toolbar */
.rr-dots{
  bottom: 12px;                 /* จากเดิม 14px ก็ได้ */
}

/* ✅ Auto-hide toolbar */
.rr-toolbar{
  opacity: 0;
  pointer-events: none;
  transform: translateX(-50%) translateY(8px);
  transition: opacity .18s ease, transform .18s ease;
}

.rr-toolbar.rr-show{
  opacity: 1;
  pointer-events: auto;
  transform: translateX(-50%) translateY(0);
}

/* (ออปชัน) ให้ dots ก็โผล่/หายพร้อมกันได้ */
.rr-dots.rr-autohide{
  opacity: .85;
  transition: opacity .18s ease;
}
.rr-dots.rr-autohide.rr-hide{
  opacity: .25;
}
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

    <!-- Action Bar -->
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 flex-none">
      <div class="relative w-full md:w-96">
        <i data-lucide="search" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input id="grid-search" type="text" oninput="onSearchChanged()" placeholder="ค้นหา... (ชื่อผู้แจ้ง/อาการ/อาคาร/สถานะ/ครุภัณฑ์)"
          class="w-full bg-white border border-slate-200 rounded-xl pl-11 pr-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-sky-500 outline-none transition-all">
      </div>

      <div class="flex gap-2 text-xs font-bold text-slate-500">
        <div class="bg-white border border-slate-200 rounded-xl px-4 py-2 flex items-center gap-2">
          <span id="row-count" class="text-sky-600 font-bold">0</span> รายการทั้งหมด
        </div>
      </div>
    </div>

    <!-- Grid -->
    <div class="flex-1 overflow-hidden p-1 relative">
      <div id="myGrid" class="ag-theme-alpine w-full h-full"></div>
    </div>
  </main>

<script>
function pad2(n){ return (n < 10 ? '0' : '') + n; }

function formatThaiDate(ymd){
  if(!ymd) return '';
  // รองรับทั้ง "YYYY-MM-DD" หรือ "YYYY-MM-DD HH:mm:ss"
  const part = ymd.toString().trim().split(' ')[0];
  const m = part.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if(!m) return ymd;

  const y = parseInt(m[1],10);
  const mo = parseInt(m[2],10);
  const d = parseInt(m[3],10);

  const thMonths = [
    '', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.',
    'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'
  ];

  const thYear = y + 543;
  return `${d} ${thMonths[mo]} ${thYear}`;
}

function formatThaiTime(hms){
  if(!hms) return '';
  // รองรับ "HH:mm:ss" หรือ "HH:mm"
  const t = hms.toString().trim().slice(0,8);
  const mm = t.match(/^(\d{2}):(\d{2})(?::(\d{2}))?$/);
  if(!mm) return hms;
  return `${mm[1]}:${mm[2]} น.`;
}
  let gridApi;
  const ag_id = <?php echo $sess_user_agency_es ?>;

  const escapeHtml = (s) => (s ?? '').toString()
    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
    .replaceAll('"','&quot;').replaceAll("'","&#039;");

  const getFilterParams = (columnName) => ({
    values: (params) => {
      fetch(`handle_repair_requests.php?action=get_filter_values&column=${columnName}&ag_id=${ag_id}`)
        .then(r => r.json()).then(data => params.success(data))
        .catch(() => params.success([]));
    },
    refreshValuesOnOpen: true,
  });
  // ====== RR Date Input (dd/mm/yyyy) สำหรับ agDateColumnFilter ======
function rrParseDMY(v){
  if(!v) return null;
  const s = String(v).trim();
  // dd/mm/yyyy หรือ dd-mm-yyyy
  const m = s.match(/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/);
  if(!m) return null;

  const dd = parseInt(m[1],10);
  const mm = parseInt(m[2],10);
  const yy = parseInt(m[3],10);

  // กันค่าหลุด ๆ
  if(mm < 1 || mm > 12 || dd < 1 || dd > 31) return null;

  const d = new Date(yy, mm-1, dd);
  // validate จริง (เช่น 31/02)
  if(d.getFullYear() !== yy || d.getMonth() !== (mm-1) || d.getDate() !== dd) return null;

  d.setHours(0,0,0,0);
  return d;
}

function rrFormatDMY(date){
  if(!date) return '';
  const dd = String(date.getDate()).padStart(2,'0');
  const mm = String(date.getMonth()+1).padStart(2,'0');
  const yy = date.getFullYear();
  return `${dd}/${mm}/${yy}`;
}

// Custom date component สำหรับ AG Grid
class RRDateInput {
  init(params){
    this.params = params;
    this.eInput = document.createElement('input');
    this.eInput.type = 'date'; // ✅ มีปฏิทิน
    this.eInput.className = 'ag-input-field-input ag-text-field-input';
    this.eInput.style.height = '32px';
    this.eInput.style.minWidth = '160px';

    this.onChanged = () => this.params.onDateChanged();
    this.eInput.addEventListener('change', this.onChanged);
    this.eInput.addEventListener('input', this.onChanged);
  }

  getGui(){ return this.eInput; }

  // ag-grid ต้องการ Date หรือ null
  getDate(){
    const v = this.eInput.value; // YYYY-MM-DD
    if(!v) return null;
    const d = new Date(v + "T00:00:00");
    d.setHours(0,0,0,0);
    return d;
  }

  setDate(date){
    if(!date){
      this.eInput.value = '';
      return;
    }
    const yyyy = date.getFullYear();
    const mm = String(date.getMonth()+1).padStart(2,'0');
    const dd = String(date.getDate()).padStart(2,'0');
    this.eInput.value = `${yyyy}-${mm}-${dd}`; // ✅ ต้องเป็นแบบนี้เท่านั้น
  }

  destroy(){
    this.eInput.removeEventListener('change', this.onChanged);
    this.eInput.removeEventListener('input', this.onChanged);
  }
} 

const STATUS_META = {
  pending:    { th: 'รอดำเนินการ',  pct: 10,  color: '#f59e0b', bg: 'bg-amber-50',  text: 'text-amber-700', bar: 'bg-amber-500' },
  inprogress: { th: 'กำลังดำเนินการ', pct: 50,  color: '#0ea5e9', bg: 'bg-sky-50',    text: 'text-sky-700',   bar: 'bg-sky-500' },
  completed:  { th: 'เสร็จสิ้น',     pct: 100, color: '#10b981', bg: 'bg-emerald-50', text: 'text-emerald-700', bar: 'bg-emerald-500' },
  feedback:   { th: 'ประเมิน',    pct: 100, color: '#6366f1', bg: 'bg-indigo-50',  text: 'text-indigo-700', bar: 'bg-indigo-500' }, // เปลี่ยนจากม่วงเป็นคราม
  canceled:   { th: 'ยกเลิก',       pct: 0,   color: '#f43f5e', bg: 'bg-rose-50',    text: 'text-rose-700',   bar: 'bg-rose-500' },
};
const normalizeStatus = (v) => {
  const key = (v ?? '').toString().trim().toLowerCase();
  if (key === 'in_progress') return 'inprogress';
  if (key === 'cancelled') return 'canceled';
  return key;
};

const statusToThai = (v) => {
  const k = normalizeStatus(v);
  return STATUS_META[k]?.th || (v ?? '');
};

const statusRenderer = (p) => {
  const k = normalizeStatus(p.value);
  const meta = STATUS_META[k] || { th: 'ไม่ทราบสถานะ', pct: 0, color: '#94a3b8', bg: 'bg-slate-50', text: 'text-slate-600', bar: 'bg-slate-400' };
  const pct = Math.max(0, Math.min(100, Number(meta.pct) || 0));

  return `
    <div class="flex flex-col w-full min-w-[140px] px-1 py-2 group">
      <div class="flex items-end justify-between mb-1.5">
        <span class="text-[12px] font-bold tracking-tight ${meta.text}">
          ${escapeHtml(meta.th)}
        </span>
        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded-md ${meta.bg} ${meta.text} border border-current/10">
          ${pct}%
        </span>
      </div>
      
      <div class="relative h-1.5 w-full bg-slate-100 rounded-full overflow-hidden shadow-inner">
        <div 
          class="h-full rounded-full transition-all duration-700 ease-out ${meta.bar} relative"
          style="width: ${pct}%"
        >
          <div class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
        </div>
      </div>
    </div>
  `;
};

  const columnDefs = [
    {
      headerName: "รูป",
      field: "images",
      width: 80,
      pinned: 'left',
      sortable: false,
      filter: false,
      cellRenderer: function(p){
        var imgs = p.value || [];
        var first = (imgs.length ? imgs[0] : null);

        if(first){
          return ''
            + '<div class="flex items-center h-full cursor-pointer group" onclick=\'openImageCarousel(' + JSON.stringify(imgs) + ')\'>' 
            +   '<img src="' + first + '" class="w-10 h-10 rounded-lg object-cover border border-slate-200 shadow-sm group-hover:scale-105 transition-transform" />'
            + '</div>';
        }

        return ''
          + '<div class="flex items-center h-full">'
          +   '<div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-200 border-dashed flex items-center justify-center text-slate-300">'
          +     '<i data-lucide="image" class="w-4 h-4"></i>'
          +   '</div>'
          + '</div>';
      }
    },// helper: แปลง dd/mm/yyyy หรือ dd-mm-yyyy -> yyyy-mm-dd 

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
  browserDatePicker: true, // ✅ ให้ขึ้นปฏิทิน
  comparator: (filterLocalDateAtMidnight, cellValue) => {
    if(!cellValue) return -1;
    const cell = new Date(cellValue + "T00:00:00");
    if(cell < filterLocalDateAtMidnight) return -1;
    if(cell > filterLocalDateAtMidnight) return 1;
    return 0;
  } 
,

    // ✅ “วันเดียว” ยังใช้ Between ได้: ถ้าใส่ from แล้ว to ว่าง → to = from
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
    return `<div class="leading-tight py-2">
      <div class="font-bold text-slate-900">${escapeHtml(formatThaiDate(d))}</div>
      <div class="text-[11px] text-slate-400 font-semibold">${escapeHtml(formatThaiTime(t))}</div>
    </div>`;
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
        return `<div class="leading-tight py-2">
          <div class="font-bold text-slate-800">${escapeHtml(name)}</div>
          <div class="text-[11px] text-slate-400 font-semibold">ติดต่อ ${escapeHtml(phone)}</div>
        </div>`;
      }
    },
    {
      headerName: "อาคาร",
      field: "building_name",
      width: 170,
      filter: 'agSetColumnFilter',
      filterParams: getFilterParams('building_name')
    },
    {
      headerName: "ชั้น",
      field: "floor_name",
      width: 110,
      filter: 'agSetColumnFilter',
      filterParams: getFilterParams('floor_name')
    },
    {
      headerName: "ห้อง",
      field: "room_name",
      width: 140,
      filter: 'agSetColumnFilter',
      filterParams: getFilterParams('room_name')
    },
    {
      headerName: "อาการเสีย/ปัญหา",
      field: "problem_detail",
      flex: 1,
      filter: 'agTextColumnFilter',
      cellRenderer: (p) => {
        const issue = p.value || '';
        const assetCode = p.data?.asset_code || '';
        const assetName = p.data?.asset_name || '';
        const machine = assetCode ? `<div class="text-[10px] mt-1 text-sky-700 font-bold font-mono bg-sky-50 border border-sky-100 px-2 py-0.5 rounded-md inline-flex">
          ${escapeHtml(assetCode)}${assetName ? ' — ' + escapeHtml(assetName) : ''}
        </div>` : '';
        return `<div class="leading-tight py-2">
          <div class="font-semibold text-slate-800">${escapeHtml(issue)}</div>
          ${machine}
        </div>`;
      }
    },
   {
  headerName: "สถานะ",
  field: "status",
  width: 220,
  filter: "agSetColumnFilter",
  filterParams: {
    ...getFilterParams("status"),
    valueFormatter: (p) => statusToThai(p.value), // ✅ ใน list เป็นไทย
  },
  cellRenderer: statusRenderer // ✅ progress bar
},
    {
      headerName: "ผู้บันทึก",
      field: "created_by",
      width: 140,
      filter: 'agSetColumnFilter',
      filterParams: getFilterParams('created_by'),
      cellRenderer: (p) => `<span class="font-semibold text-slate-700">${escapeHtml(p.value || '')}</span>`
    },
    {
      headerName: "จัดการ",
      width: 110,
      pinned: 'right',
      sortable: false,
      filter: false,
      cellRenderer: (p) => {
        const id = p.data?.id;
        setTimeout(() => lucide.createIcons(), 0);
        return `<div class="flex items-center gap-1 h-full py-2">
          <button onclick="openDetail(${id})" class="p-2 hover:bg-slate-50 text-slate-500 rounded-xl transition-colors" title="ดูรายละเอียด">
            <i data-lucide="file-text" class="w-4 h-4"></i>
          </button>
          <button onclick="openEdit(${id})" class="p-2 hover:bg-sky-50 text-sky-600 rounded-xl transition-colors" title="แก้ไข/รับงาน">
            <i data-lucide="pen" class="w-4 h-4"></i>
          </button>
        </div>`;
      }
    }
  ];

  const gridOptions = {
    columnDefs,
    rowHeight: 65,
    rowModelType: 'serverSide',
    pagination: true,
    paginationPageSize: 10,
    cacheBlockSize: 10,
    paginationPageSizeSelector: [10, 20, 50, 100],
	  components: {
    agDateInput: RRDateInput
  },

    onRowDataUpdated: () => lucide.createIcons(),
    onViewportChanged: () => lucide.createIcons(),
    onPaginationChanged: () => lucide.createIcons(),
    onGridReady: (params) => {
      gridApi = params.api;
      loadGrid();
    }
  };

  function loadGrid(){
    const datasource = {
      getRows: async (params) => {
        const req = params.request;
        const searchTerm = document.getElementById('grid-search').value || '';

        const url = new URL('handle_repair_requests.php', window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/'));
        url.searchParams.set('action', 'get_all');
        url.searchParams.set('ag_id', ag_id);
        url.searchParams.set('startRow', req.startRow);
        url.searchParams.set('endRow', req.endRow);
        url.searchParams.set('search', searchTerm);
        url.searchParams.set('filterModel', JSON.stringify(req.filterModel || {}));
        if (req.sortModel && req.sortModel.length) {
          url.searchParams.set('sortModel', JSON.stringify(req.sortModel));
        }

        try{
          const r = await fetch(url);
          const result = await r.json();

          params.success({
            rowData: result.rows || [],
            rowCount: result.lastRow ?? 0
          });

          document.getElementById('row-count').innerText = (result.lastRow ?? 0);
          setTimeout(() => lucide.createIcons(), 50);
        }catch(e){
          console.error(e);
          params.fail();
        }
      }
    };

    gridApi.setGridOption('serverSideDatasource', datasource);
  }

  function onSearchChanged(){
    if (gridApi) gridApi.refreshServerSide({ purge: true });
  }
  function refreshGrid(){
    if (gridApi) gridApi.refreshServerSide({ purge: true });
  }

  /* ==========================
     ✅ Carousel + Zoom + Fullscreen + Rotate + Dot jump + Prev/Next + Swipe
     ========================== */

  var currentSlide = 0;
  var rrUrls = [];

  var rrZoom = {
    scale: 1,
    min: 1,
    max: 6,
    tx: 0,
    ty: 0,
    rotate: 0,
    dragging: false,
    startX: 0,
    startY: 0,
    baseTX: 0,
    baseTY: 0
  };

  function resetZoomState(){
    rrZoom.scale = 1;
    rrZoom.tx = 0;
    rrZoom.ty = 0;
    rrZoom.rotate = 0;
    rrZoom.dragging = false;
    rrZoom.startX = 0;
    rrZoom.startY = 0;
    rrZoom.baseTX = 0;
    rrZoom.baseTY = 0;
  }

  function clamp(v, min, max){ return Math.max(min, Math.min(max, v)); }
  function getActiveImg(){ return document.getElementById('rr-img-' + currentSlide); }

  function updateZoomLabel(){
    var lb = document.getElementById('rr-zoom-label');
    if(lb) lb.textContent = Math.round(rrZoom.scale * 100) + '%';
  }

  function applyTransformToActive(){
    var img = getActiveImg();
    if(!img) return;
    img.style.transform =
      'translate(' + rrZoom.tx + 'px,' + rrZoom.ty + 'px) ' +
      'scale(' + rrZoom.scale + ') ' +
      'rotate(' + rrZoom.rotate + 'deg)';
  }

  function setZoom(newScale){
    rrZoom.scale = clamp(newScale, rrZoom.min, rrZoom.max);
    if(rrZoom.scale <= 1.001){
      rrZoom.scale = 1;
      rrZoom.tx = 0;
      rrZoom.ty = 0;
    }
    updateZoomLabel();
    applyTransformToActive();
  }

  function fitZoom(){ setZoom(1); }

  function rotateImage(){
    rrZoom.rotate = (rrZoom.rotate + 90) % 360;
    applyTransformToActive();
  }

  function deactivateSlide(i){
    var s = document.getElementById('slide-' + i);
    var d = document.getElementById('dot-' + i);
    if(s) s.classList.remove('active');
    if(d) d.classList.remove('active');
  }

  function activateSlide(i){
    var s = document.getElementById('slide-' + i);
    var d = document.getElementById('dot-' + i);
    if(s) s.classList.add('active');
    if(d) d.classList.add('active');
    applyTransformToActive();
  }

  function goToSlide(index){
    var total = rrUrls.length;
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

  function nextSlide(){
    if(rrUrls.length <= 1) return;
    var total = rrUrls.length;
    goToSlide((currentSlide + 1) % total);
  }
  function prevSlide(){
    if(rrUrls.length <= 1) return;
    var total = rrUrls.length;
    goToSlide((currentSlide - 1 + total) % total);
  }

  function attachPanZoomHandlers(){
    var wraps = document.querySelectorAll('.rr-imgwrap');
    if(!wraps || !wraps.length) return;

    for(var i=0; i<wraps.length; i++){
      (function(wrap){
        // wheel zoom
        wrap.addEventListener('wheel', function(e){
          var sid = parseInt(wrap.getAttribute('data-slide') || '0', 10);
          if(sid !== currentSlide) return;

          e.preventDefault();
          var delta = e.deltaY || 0;
          if(delta > 0) setZoom(rrZoom.scale / 1.15);
          else setZoom(rrZoom.scale * 1.15);
        }, { passive:false });

        // drag pan (desktop)
        wrap.addEventListener('mousedown', function(e){
          var sid = parseInt(wrap.getAttribute('data-slide') || '0', 10);
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

        window.addEventListener('mousemove', function(e){
          if(!rrZoom.dragging) return;
          var dx = e.clientX - rrZoom.startX;
          var dy = e.clientY - rrZoom.startY;
          rrZoom.tx = rrZoom.baseTX + dx;
          rrZoom.ty = rrZoom.baseTY + dy;
          applyTransformToActive();
        });

        window.addEventListener('mouseup', function(){
          if(!rrZoom.dragging) return;
          rrZoom.dragging = false;
          var img = getActiveImg();
          if(img && img.parentElement){
            img.parentElement.classList.remove('rr-grabbing');
            img.parentElement.classList.add('rr-grab');
          }
        });

        /* swipe (mobile) : ถ้า zoom=1 จะเปลี่ยนรูป, ถ้า zoom>1 จะลาก pan */
        var touchStartX = 0, touchStartY = 0, swiping = false;

        wrap.addEventListener('touchstart', function(e){
          var sid = parseInt(wrap.getAttribute('data-slide') || '0', 10);
          if(sid !== currentSlide) return;
          if(!e.touches || !e.touches[0]) return;

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

        wrap.addEventListener('touchmove', function(e){
          var sid = parseInt(wrap.getAttribute('data-slide') || '0', 10);
          if(sid !== currentSlide) return;
          if(!swiping) return;
          if(!e.touches || !e.touches[0]) return;

          var x = e.touches[0].clientX;
          var y = e.touches[0].clientY;

          if(rrZoom.scale > 1 && rrZoom.dragging){
            var dx = x - rrZoom.startX;
            var dy = y - rrZoom.startY;
            rrZoom.tx = rrZoom.baseTX + dx;
            rrZoom.ty = rrZoom.baseTY + dy;
            applyTransformToActive();
          }
        }, {passive:true});

        wrap.addEventListener('touchend', function(e){
          var sid = parseInt(wrap.getAttribute('data-slide') || '0', 10);
          if(sid !== currentSlide) return;

          if(rrZoom.scale > 1){
            rrZoom.dragging = false;
            swiping = false;
            return;
          }

          var endX = (e.changedTouches && e.changedTouches[0]) ? e.changedTouches[0].clientX : touchStartX;
          var endY = (e.changedTouches && e.changedTouches[0]) ? e.changedTouches[0].clientY : touchStartY;
          var dx = endX - touchStartX;
          var dy = endY - touchStartY;

          swiping = false;
          if(Math.abs(dx) < 40 || Math.abs(dx) < Math.abs(dy)) return;

          if(dx < 0) nextSlide();
          else prevSlide();
        }, {passive:true});

      })(wraps[i]);
    }
  }

  function isFullscreen(){
    return !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
  }
  function toggleFullscreen(el){
    if(!isFullscreen()){
      if(el.requestFullscreen) el.requestFullscreen();
      else if(el.webkitRequestFullscreen) el.webkitRequestFullscreen();
      else if(el.mozRequestFullScreen) el.mozRequestFullScreen();
      else if(el.msRequestFullscreen) el.msRequestFullscreen();
    }else{
      if(document.exitFullscreen) document.exitFullscreen();
      else if(document.webkitExitFullscreen) document.webkitExitFullscreen();
      else if(document.mozCancelFullScreen) document.mozCancelFullScreen();
      else if(document.msExitFullscreen) document.msExitFullscreen();
    }
  }
var rrToolbarTimer = null;

function rrShowToolbar(){
  var tb = document.getElementById('rr-toolbar');
  if(tb) tb.classList.add('rr-show');

  // dots (ออปชัน)
  var dots = document.getElementById('rr-dots');
  if(dots){
    dots.classList.add('rr-autohide');
    dots.classList.remove('rr-hide');
  }

  if(rrToolbarTimer) clearTimeout(rrToolbarTimer);
  rrToolbarTimer = setTimeout(function(){
    var tb2 = document.getElementById('rr-toolbar');
    if(tb2) tb2.classList.remove('rr-show');

    var dots2 = document.getElementById('rr-dots');
    if(dots2) dots2.classList.add('rr-hide');
  }, 1800); // เวลาให้หาย (ms) ปรับได้
}

function rrBindAutoHide(containerEl){
  if(!containerEl) return;

  // โผล่ทันทีตอนเปิด
  rrShowToolbar();

  // เมาส์/ทัช/เลื่อน/กดคีย์ => โผล่
  var handler = function(){ rrShowToolbar(); };

  containerEl.addEventListener('mousemove', handler);
  containerEl.addEventListener('mousedown', handler);
  containerEl.addEventListener('wheel', handler, { passive:true });
  containerEl.addEventListener('touchstart', handler, { passive:true });
  containerEl.addEventListener('touchmove', handler, { passive:true });
  window.addEventListener('keydown', handler);

  // เก็บไว้เพื่อถอดตอนปิด
  containerEl._rrAutoHideOff = function(){
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
  function openImageCarousel(images){
    if(!images || images.length === 0){
      Swal.fire("ไม่มีรูป", "รายการนี้ไม่มีรูปแนบ", "info");
      return;
    }

    rrUrls = [];
    for(var i=0; i<images.length; i++){
      if(images[i]) rrUrls.push(images[i]);
    }
    if(rrUrls.length === 0){
      Swal.fire("ไม่มีรูป", "รายการนี้ไม่มีรูปแนบ", "info");
      return;
    }

    currentSlide = 0;
    resetZoomState();

    var html = '<div class="swal-carousel-container" id="rr-carousel">';

    // ✅ Toolbar รวมกลุ่มเดียว + มี prev/next
    html += ''
      + '<div class="rr-toolbar rr-zoombar" id="rr-toolbar">' 
      +   '<button type="button" class="rr-toolbtn rr-zoombar" id="rr-prev" title="รูปก่อนหน้า"><i data-lucide="chevron-left"></i></button>'
      +   '<button type="button" class="rr-toolbtn rr-zoombar" id="rr-next" title="รูปถัดไป"><i data-lucide="chevron-right"></i></button>'
      +   '<div class="rr-toolsep"></div>'
      +   '<button type="button" class="rr-toolbtn rr-zoombar" id="rr-fs-btn" title="เต็มจอ"><i data-lucide="maximize-2"></i></button>'
      +   '<button type="button" class="rr-toolbtn rr-zoombar" id="rr-zoom-out" title="ซูมออก"><i data-lucide="minus"></i></button>'
      +   '<button type="button" class="rr-toolbtn rr-zoombar" id="rr-zoom-in" title="ซูมเข้า"><i data-lucide="plus"></i></button>' 
      +   '<button type="button" class="rr-toolbtn rr-zoombar" id="rr-rotate" title="กลับรูป"><i data-lucide="rotate-cw"></i></button>'
      +   '<div class="rr-zoomlabel" id="rr-zoom-label">100%</div>'
      + '</div>';

    // slides
    for(var idx=0; idx<rrUrls.length; idx++){
      var img = rrUrls[idx];
      html += ''
        + '<div class="carousel-item ' + (idx===0?'active':'') + '" id="slide-' + idx + '">'
        +   '<div class="rr-imgwrap rr-grab" data-slide="'+idx+'">'
        +     '<img id="rr-img-'+idx+'" src="' + img + '"'
        +     ' style="transform: translate(0px,0px) scale(1) rotate(0deg); transform-origin: 50% 50%;"'
        +     ' onerror="this.onerror=null; this.src=\\\'data:image/svg+xml;utf8,<svg xmlns=\\\\\\\'http://www.w3.org/2000/svg\\\\\\\' width=\\\\\\\'900\\\\\\\' height=\\\\\\\'450\\\\\\\'><rect width=\\\\\\\'100%\\\\\\\' height=\\\\\\\'100%\\\\\\\' fill=\\\\\\\'%23f1f5f9\\\\\\\'/><text x=\\\\\\\'50%\\\\\\\' y=\\\\\\\'50%\\\\\\\' text-anchor=\\\\\\\'middle\\\\\\\' fill=\\\\\\\'%2394a3b8\\\\\\\' font-size=\\\\\\\'18\\\\\\\'>Image not found</text></svg>\\\';"'
        +     ' />'
        +   '</div>'
        + '</div>';
    }

    // dots (jump)
    if(rrUrls.length > 1){
      html += '<div class="rr-dots" id="rr-dots">';
      for(var d=0; d<rrUrls.length; d++){
        html += '<div class="rr-dot ' + (d===0?'active':'') + '" id="dot-' + d + '" data-idx="' + d + '"></div>';
      }
      html += '</div>';
    }

    html += '</div>';

    var rrKeyHandler = null;

    Swal.fire({
      title: 'รูปประกอบการแจ้งซ่อม',
      html: html,
      showConfirmButton: false,
      showCloseButton: true,
      width: '900px',
      customClass: { popup: 'rounded-3xl p-4' },
      didOpen: function(){
        if(window.lucide && lucide.createIcons) lucide.createIcons();
		rrBindAutoHide(document.getElementById('rr-carousel'));
        // dot click => jump
        var dotsWrap = document.getElementById('rr-dots');
        if(dotsWrap){
          dotsWrap.onclick = function(e){
            var t = e.target;
            if(!t) return;
            var to = t.getAttribute('data-idx');
            if(to === null) return;
            goToSlide(parseInt(to, 10));
          };
        }

        // buttons
        var fsBtn = document.getElementById('rr-fs-btn');
        var prevBtn = document.getElementById('rr-prev');
        var nextBtn = document.getElementById('rr-next');
        var btnIn = document.getElementById('rr-zoom-in');
        var btnOut = document.getElementById('rr-zoom-out');
        var btnFit = document.getElementById('rr-zoom-fit');
        var btnRotate = document.getElementById('rr-rotate');
        var fsTarget = document.getElementById('rr-carousel');

        if(prevBtn) prevBtn.onclick = function(){ prevSlide(); };
        if(nextBtn) nextBtn.onclick = function(){ nextSlide(); };

        if(btnIn) btnIn.onclick = function(){ setZoom(rrZoom.scale * 1.25); };
        if(btnOut) btnOut.onclick = function(){ setZoom(rrZoom.scale / 1.25); };
        if(btnFit) btnFit.onclick = function(){ fitZoom(); };
        if(btnRotate) btnRotate.onclick = function(){ rotateImage(); };

        // fullscreen icon toggle
        function syncFsIcon(){
          var isFs = isFullscreen();
          if(fsBtn){
            fsBtn.innerHTML = isFs ? '<i data-lucide="minimize-2"></i>' : '<i data-lucide="maximize-2"></i>';
            if(window.lucide && lucide.createIcons) lucide.createIcons();
          }
        }
        if(fsBtn && fsTarget){
          fsBtn.onclick = function(){
            toggleFullscreen(fsTarget);
            setTimeout(syncFsIcon, 60);
          };
          document.addEventListener('fullscreenchange', syncFsIcon);
          syncFsIcon();
        }

        // keyboard: ← → / + - / r / f
        rrKeyHandler = function(e){
          if(e.key === 'ArrowRight') nextSlide();
          if(e.key === 'ArrowLeft')  prevSlide();
          if(e.key === '+' || e.key === '=') setZoom(rrZoom.scale * 1.25);
          if(e.key === '-' || e.key === '_') setZoom(rrZoom.scale / 1.25);
          if(e.key === 'r' || e.key === 'R') rotateImage();
          if(e.key === 'f' || e.key === 'F') if(fsTarget) toggleFullscreen(fsTarget);
        };
        document.addEventListener('keydown', rrKeyHandler);

        attachPanZoomHandlers();
        updateZoomLabel();
        applyTransformToActive();
      },
      willClose: function(){
        resetZoomState();
        rrUrls = [];
        if(rrKeyHandler) document.removeEventListener('keydown', rrKeyHandler);
        rrKeyHandler = null;
		var wrap = document.getElementById('rr-carousel');
		if(wrap && wrap._rrAutoHideOff) wrap._rrAutoHideOff();
      }
    });
  }

  // ปุ่มจัดการ (คุณค่อยผูกไปหน้า accept/close/detail จริง)
  function openDetail(id){
    Swal.fire('Detail', `open detail id = ${id}`, 'info');
  }
  function openEdit(id){
    Swal.fire('Edit/Accept', `open edit/accept id = ${id}`, 'info');
  }

  window.onload = () => {
    const gridDiv = document.querySelector('#myGrid');
    gridApi = agGrid.createGrid(gridDiv, gridOptions);
    lucide.createIcons();
  };
  
</script>
</body>
</html>
