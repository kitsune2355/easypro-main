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
  <script src="js/img-carousel.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  
  <!-- สำหรับ Export Excel + รูปภาพ -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
  <style>
  .select2-container {
	  width: 100% !important;
	}
	
	.select2-container--default .select2-selection--multiple {
	  min-height: 44px;
	  border: 1px solid #cbd5e1;
	  border-radius: 12px;
	  padding: 6px 8px;
	  background: #fff;
	}
	
	.select2-container--default.select2-container--focus .select2-selection--multiple {
	  border-color: #93c5fd;
	  box-shadow: 0 0 0 3px rgba(14, 165, 233, .15);
	}
	
	.select2-container--default .select2-selection--multiple .select2-selection__choice {
	  background: #dbeafe;
	  border: 1px solid #bfdbfe;
	  color: #1e40af;
	  border-radius: 999px;
	  padding: 3px 9px;
	  font-size: 12px;
	  font-weight: 700;
	}
	
	.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
	  color: #64748b;
	  border-right: none;
	  margin-right: 5px;
	}
	
	.select2-dropdown {
	  border: 1px solid #cbd5e1;
	  border-radius: 12px;
	  overflow: hidden;
	  z-index: 20050 !important;
	}
	
	.select2-results__option {
	  padding: 10px 12px;
	  font-size: 13px;
	  font-weight: 600;
	}
	
	.select2-results__option--highlighted[aria-selected] {
	  background: #eff6ff !important;
	  color: #0f172a !important;
	}


/* Select2: ให้ฟอนต์และ placeholder เหมือน input อื่น */
.select2-container,
.select2-container *,
.select2-dropdown,
.select2-dropdown * {
  font-family: 'Kanit', sans-serif !important;
}

.select2-container--default .select2-selection--multiple {
  display: flex;
  align-items: center;
}

.select2-container--default .select2-search--inline .select2-search__field {
  font-family: 'Kanit', sans-serif !important;
  font-size: 13px !important;
  font-weight: 600 !important;
  color: #334155 !important;
  margin-top: 0 !important;
  height: 28px !important;
  line-height: 28px !important;
}

.select2-container--default .select2-search--inline .select2-search__field::placeholder {
  font-family: 'Kanit', sans-serif !important;
  font-size: 13px !important;
  font-weight: 700 !important;
  color: #64748b !important;
  opacity: 1 !important;
}

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
	/* SweetAlert ต้องอยู่เหนือ Evaluation Modal ที่ใช้ z-index 22000 */
	body > .swal2-container {
	  z-index: 99999 !important;
	}
	
	body > .swal2-container .swal2-popup {
	  z-index: 100000 !important;
	}
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
	padding:.35rem .6rem;
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


  
    /* =========================
       ✅ Upload Dropzone (เหมือนตัวอย่างในรูป)
       ========================= */
    .up-card{ border:1px solid #e2e8f0; background:#fff; border-radius:16px; box-shadow:0 1px 2px rgba(15,23,42,.06); }
    .up-head{ display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 16px 10px; }
    .up-title{ display:flex; align-items:center; gap:8px; font-size:12px; font-weight:800; color:#0f172a; }
    .up-count{ font-size:11px; font-weight:800; color:#94a3b8; }
    .up-zone{
      margin:0 16px 12px;
      border:2px dashed #cbd5e1;
      border-radius:18px;
      background:#fff;
      min-height:90px;
      display:flex;
      align-items:stretch;
      overflow:hidden;
      cursor:pointer;
      transition:.15s;
    }
    .up-zone:hover{ border-color:#94a3b8; background:#f8fafc; }
    .up-left{
      width:92px;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      gap:6px;
      border-right:1px dashed #e2e8f0;
      background:rgba(241,245,249,.7);
    }
    .up-left .cam{
      width:34px; height:34px;
      display:flex; align-items:center; justify-content:center;
      border:1px solid #e2e8f0; border-radius:14px; background:#fff;
      color:#64748b;
    }
    .up-left .txt{ font-size:10px; font-weight:900; color:#64748b; line-height:1.1; text-align:center; }
    .up-mid{
      flex:1;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      padding:12px 14px;
      gap:10px;
      color:#94a3b8;
      font-size:12px;
      font-weight:700;
      text-align:center;
    }
    .up-mid.has-items{
      align-items:flex-start;
      justify-content:flex-start;
      text-align:left;
    }
    .up-hint{ width:100%; }
    .up-thumbs{
      width:100%;
      display:flex;
      flex-wrap:wrap;
      gap:8px;
      align-items:flex-start;
      justify-content:flex-start;
    }
    .up-thumb{
      width:64px;
      height:64px;
      border-radius:14px;
      overflow:hidden;
      border:1px solid #e2e8f0;
      background:#fff;
      position:relative;
      flex:0 0 auto;
    }
    .up-thumb img{ width:100%; height:100%; object-fit:cover; display:block; }
    .up-x{
      position:absolute;
      top:4px; right:4px;
      width:20px; height:20px;
      border-radius:999px;
      background:#ef4444;
      color:#fff;
      font-weight:900;
      font-size:12px;
      line-height:20px;
      text-align:center;
      box-shadow:0 6px 16px rgba(239,68,68,.25);
    }
    .up-x:hover{ background:#dc2626; }
    .up-err{
      width:100%;
      font-size:10px;
      font-weight:800;
      color:#e11d48;
    }

    .up-help{
      padding:0 16px 14px;
      font-size:10px;
      font-weight:700;
      color:#94a3b8;
    }
    .up-preview{ padding:0 16px 14px; }

.ag-checkbox-input{
  -webkit-appearance: none;
  appearance: none;
  width: 18px;
  height: 18px;
  border-radius: 9999px;
  border: 2px solid #cbd5e1;   /* สีขอบ */
  background: #fff;
  display: inline-grid;
  place-content: center;
  cursor: pointer;
  outline: none;
}

/* ติ๊ก */
.ag-checkbox-input::after{
  content: "";
  width: 6px;
  height: 10px;
  border-right: 2px solid #fff;
  border-bottom: 2px solid #fff;
  transform: rotate(45deg) scale(0);
  transition: transform 120ms ease;
}

/* ตอนติ๊ก */
.ag-checkbox-input:checked{
  background: #0ea5e9;   /* ฟ้า */
  border-color: #0ea5e9;
}
.ag-checkbox-input:checked::after{
  transform: rotate(45deg) scale(1);
}

/* ตอน hover / focus */
.ag-checkbox-input:hover{ border-color:#94a3b8; }
.ag-checkbox-input:focus{ box-shadow: 0 0 0 3px rgba(14,165,233,.25); }

  </style>
</head>

<body class="flex flex-col antialiased text-slate-700">

  <!-- Header -->
  <nav class="flex-none px-3 py-4 flex items-center justify-between border-b border-slate-200 bg-white z-30">
    <div class="flex items-center gap-3">
      <div class="p-2.5 bg-[--color-primary] rounded-xl shadow-lg text-white">
        <i data-lucide="clipboard-list" class="w-6 h-6"></i>
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
	const createdByNameMap = {}; 
    const getFilterParams = (columnName) => {
	
	  if (columnName === 'status') { 
	  return {
		values: (params) => {
		  fetch(`handle_repair_requests.php?action=get_filter_values&column=status&ag_id=${AG_ID}`)
			.then(r => r.json())
			.then(list => {
			  const statusOrder = ['pending', 'inprogress', 'completed', 'feedback', 'cancel'];
			  const seen = new Set();
	
			  const normalized = (Array.isArray(list) ? list : [])
				.map(v => normalizeStatus(v))
				.filter(v => {
				  if (!v) return false;
				  if (seen.has(v)) return false;
				  seen.add(v);
				  return true;
				})
				.sort((a, b) => statusOrder.indexOf(a) - statusOrder.indexOf(b));
	
			  params.success(normalized);
			})
			.catch(() => params.success([]));
		},
		refreshValuesOnOpen: true,
		valueFormatter: (p) => statusToThai(p.value),
	  };
	}

	  // เคส created_by
	  if (columnName === 'created_by') {
		return {
		  values: (params) => {
			fetch(`handle_repair_requests.php?action=get_filter_values&column=created_by&ag_id=${AG_ID}`)
			  .then(r => r.json())
			  .then(list => {
				// list = [{value, label}, ...]
				if (Array.isArray(list)) {
				  list.forEach(item => {
					createdByNameMap[item.value] = item.label;
				  });
				  // ส่งเฉพาะ value (id) ให้ filter ใช้
				  params.success(list.map(item => item.value));
				} else {
				  params.success([]);
				}
			  })
			  .catch(() => params.success([]));
		  },
		  // ให้ dropdown แสดงชื่อแทน id
		  valueFormatter: (p) => createdByNameMap[p.value] || p.value,
		  refreshValuesOnOpen: true,
		};
	  }
	
	  // เคสอื่น ๆ เดิม
	  return {
		values: (params) => {
		  fetch(`handle_repair_requests.php?action=get_filter_values&column=${columnName}&ag_id=${AG_ID}`)
			.then(r => r.json())
			.then(data => params.success(data))
			.catch(() => params.success([]));
		},
		refreshValuesOnOpen: true,
	  };
	};

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
	  feedback:   { th:'ประเมินแล้ว',   pct:100, bg:'bg-emerald-50', text:'text-emerald-700', bar:'bg-emerald-500'},
      cancel:     { th:'ยกเลิก',        pct:0,   bg:'bg-rose-50',    text:'text-rose-700',    bar:'bg-rose-500' },
    };

    const normalizeStatus = (v) => {
      const key = (v ?? '').toString().trim().toLowerCase();
      if(key === 'in_progress') return 'inprogress';
      if(key === 'canceled' || key === 'cancelled') return 'cancel';
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
          </div>
          <div class="relative h-1.5 w-full bg-slate-100 rounded-full overflow-hidden shadow-inner">
            <div class="h-full rounded-full transition-all duration-700 ease-out ${meta.bar}" style="width:${pct}%"></div>
          </div>
        </div>
      `;
    };
	
	
	
	function DocDateUrgencyFilter() {}

DocDateUrgencyFilter.prototype.init = function(params) {
  this.params = params;
  this.value = {
    dateFrom: '',
    dateTo: '',
    doc: '',
    urgency: ''
  };

  const eGui = document.createElement('div');
  eGui.className = 'p-2 text-[11px] text-slate-700 space-y-2';

  eGui.innerHTML = `
    <div class="space-y-1">
      <div class="font-semibold text-[11px] text-slate-600">ช่วงวันที่</div>
      <input type="date" data-role="dateFrom"
        class="ag-input-field-input w-full border border-slate-200 rounded mb-1 px-2 py-1 text-[11px]" />
      <input type="date" data-role="dateTo"
        class="ag-input-field-input w-full border border-slate-200 rounded px-2 py-1 text-[11px]" />
    </div>

    <div class="space-y-1">
      <div class="font-semibold text-[11px] text-slate-600">เลขที่เอกสาร</div>
      <input type="text" data-role="doc"
        placeholder="เช่น RP2-6902-0005"
        class="ag-input-field-input w-full border border-slate-200 rounded px-2 py-1 text-[11px]" />
    </div>

    <div class="space-y-1">
      <div class="font-semibold text-[11px] text-slate-600">ความเร่งด่วน</div>
      <select data-role="urgency"
        class="ag-input-field-input w-full border border-slate-200 rounded px-2 py-1 text-[11px] bg-white">
        <option value="">ทั้งหมด</option>
        <option value="low">ต่ำ</option>
        <option value="medium">ปานกลาง</option>
        <option value="high">สูง</option>
        <option value="critical">เร่งด่วนมาก</option>
      </select>
    </div>
  `;

  this.eGui = eGui;

  const dateFromInput = eGui.querySelector('[data-role="dateFrom"]');
  const dateToInput   = eGui.querySelector('[data-role="dateTo"]');
  const docInput      = eGui.querySelector('[data-role="doc"]');
  const urgSelect     = eGui.querySelector('[data-role="urgency"]');

  const onChanged = () => {
    this.value.dateFrom = dateFromInput.value || '';
    this.value.dateTo   = dateToInput.value   || '';
    this.value.doc      = docInput.value.trim();
    this.value.urgency  = urgSelect.value;

    this.params.filterChangedCallback();
  };

  dateFromInput.addEventListener('change', onChanged);
  dateToInput.addEventListener('change', onChanged);
  docInput.addEventListener('input', onChanged);
  urgSelect.addEventListener('change', onChanged);
};

DocDateUrgencyFilter.prototype.getGui = function() {
  return this.eGui;
};

DocDateUrgencyFilter.prototype.isFilterActive = function() {
  const v = this.value;
  return !!(v.dateFrom || v.dateTo || v.doc || v.urgency);
};

// 🟢 ตรงนี้สำคัญ: model ที่จะถูกส่งไป PHP ผ่าน filterModel
DocDateUrgencyFilter.prototype.getModel = function() {
  if (!this.isFilterActive()) return null;

  return {
    filterType: 'date',
    type: 'inRange',              // ให้ PHP รู้ว่าเป็นช่วงวันที่
    dateFrom: this.value.dateFrom,
    dateTo:   this.value.dateTo,
    doc:      this.value.doc,     // เพิ่มฟิลด์พิเศษ
    urgency:  this.value.urgency  // เพิ่มฟิลด์พิเศษ
  };
};

DocDateUrgencyFilter.prototype.setModel = function(model) {
  if (!model) {
    this.value = { dateFrom: '', dateTo: '', doc: '', urgency: '' };
  } else {
    this.value.dateFrom = model.dateFrom || '';
    this.value.dateTo   = model.dateTo   || '';
    this.value.doc      = model.doc      || '';
    this.value.urgency  = model.urgency  || '';
  }

  if (this.eGui) {
    const dateFromInput = this.eGui.querySelector('[data-role="dateFrom"]');
    const dateToInput   = this.eGui.querySelector('[data-role="dateTo"]');
    const docInput      = this.eGui.querySelector('[data-role="doc"]');
    const urgSelect     = this.eGui.querySelector('[data-role="urgency"]');

    if (dateFromInput) dateFromInput.value = this.value.dateFrom;
    if (dateToInput) dateToInput.value = this.value.dateTo;
    if (docInput) docInput.value = this.value.doc;
    if (urgSelect) urgSelect.value = this.value.urgency;
  }
};

DocDateUrgencyFilter.prototype.doesFilterPass = function(params) {
  // ฝั่ง client-side row model จะใช้; แต่คุณใช้ server-side เป็นหลัก
  // จะเขียนให้ทำงานหรือปล่อย return true ก็ได้
  return true;
};

DocDateUrgencyFilter.prototype.afterGuiAttached = function() {
  const input = this.eGui.querySelector('[data-role="dateFrom"]');
  if (input) input.focus();
};

DocDateUrgencyFilter.prototype.destroy = function() {};
DocDateUrgencyFilter.prototype.onNewRowsLoaded = function() {};
	
	
function urgencyMeta(code) {
  const c = String(code ?? '').trim();
  switch (c) {
    case 'low':
      return { label: 'ต่ำ',        dotClass: 'bg-emerald-500' };
    case 'medium':
      return { label: 'ปานกลาง',   dotClass: 'bg-amber-500' };
    case 'high':
      return { label: 'สูง',        dotClass: 'bg-orange-500' };
    case 'critical':
      return { label: 'เร่งด่วนมาก', dotClass: 'bg-rose-500' };
    default:
      return { label: 'ไม่ระบุ',    dotClass: 'bg-slate-300' };
  }
} 
const statusText = {
  pending: "รอดำเนินการ",
  inprogress: "กำลังดำเนินการ",
  completed: "เสร็จสิ้น",
  feedback: "ประเมินแล้ว",
  cancel: "ยกเลิก",
  canceled: "ยกเลิก",
  cancelled: "ยกเลิก"
};

const excelExportColumns = [
  // 1) เลขที่เอกสาร
  {
    headerName: 'เลขที่เอกสาร',
    field: 'ex_doc_no',
    hide: true,
    valueGetter: p => p.data?.rp_format || ''
  },

  // 2) สถานะ (ไทย)
  {
    headerName: 'สถานะ',
    field: 'ex_status',
    hide: true,
    valueGetter: p => statusText[p.data?.status] || p.data?.status || ''
  },

  // 3) ความเร่งด่วน
  {
    headerName: 'ความเร่งด่วน',
    field: 'ex_urgency',
    hide: true,
    valueGetter: p => urgencyMeta(p.data?.urgency).label
  },

  // 4) วันที่แจ้ง
  {
    headerName: 'วันที่แจ้ง',
    field: 'ex_report_date',
    hide: true,
    valueGetter: p => formatThaiDate(p.data?.report_date || '')
  },

  // 5) เวลาที่แจ้ง
  {
    headerName: 'เวลาที่แจ้ง',
    field: 'ex_report_time',
    hide: true,
    valueGetter: p => formatThaiTime(p.data?.report_time || '')
  },

  // 6) วันที่เริ่มซ่อม (process_date)
  {
    headerName: 'วันที่เริ่มซ่อม',
    field: 'ex_process_date',
    hide: true,
    valueGetter: p => formatThaiDate(p.data?.process_date || '')
  },

  // 7) เวลาเริ่มซ่อม (process_time)
  {
    headerName: 'เวลาเริ่มซ่อม',
    field: 'ex_process_time',
    hide: true,
    valueGetter: p => formatThaiTime(p.data?.process_time || '')
  },

  // 8) วันที่ซ่อมเสร็จ (completed_date)
  {
    headerName: 'วันที่ซ่อมเสร็จ',
    field: 'ex_completed_date',
    hide: true,
    valueGetter: p => formatThaiDate(p.data?.completed_date || '')
  },

  // 9) เวลาซ่อมเสร็จ (completed_time)
  {
    headerName: 'เวลาซ่อมเสร็จ',
    field: 'ex_completed_time',
    hide: true,
    valueGetter: p => formatThaiTime(p.data?.completed_time || '')
  },

  // 10) ผู้แจ้ง
  {
    headerName: 'ผู้แจ้ง',
    field: 'ex_reporter',
    hide: true,
    valueGetter: p => p.data?.name || ''
  },

  // 11) เบอร์
  {
    headerName: 'เบอร์',
    field: 'ex_phone',
    hide: true,
    valueGetter: p => p.data?.phone || ''
  },

  // 12) อาคาร
  {
    headerName: "อาคาร",
    field: "ex_building",
    hide: true,
    valueGetter: p => p.data?.building_name || ''
  },

  // 13) ชั้น
  {
    headerName: "ชั้น",
    field: "ex_floor",
    hide: true,
    valueGetter: p => p.data?.floor_name || ''
  },

  // 14) ห้อง
  {
    headerName: "ห้อง",
    field: "ex_room",
    hide: true,
    valueGetter: p => p.data?.room_name || ''
  },

  // 15) ชนิดบริการ
  {
    headerName: 'ชนิดบริการ',
    field: 'ex_service_type',
    hide: true,
    valueGetter: p => p.data?.service_type_name || ''
  },

  // 16) ประเภทงาน
  {
    headerName: 'ประเภทงาน',
    field: 'ex_job_type',
    hide: true,
    valueGetter: p => p.data?.job_type_name || ''
  },

  // 17) รายละเอียดปัญหา
  {
    headerName: 'รายละเอียดปัญหา',
    field: 'ex_problem_detail',
    hide: true,
    valueGetter: p => p.data?.problem_detail || ''
  },

  // 18) รายละเอียดการปฏิบัติงาน
  {
    headerName: 'รายละเอียดการปฏิบัติงาน',
    field: 'ex_completed_solution',
    hide: true,
    valueGetter: p => p.data?.completed_solution || ''
  },

  // 19) ผู้รับแจ้ง (Admin)
  {
    headerName: 'ผู้รับแจ้ง (Admin)',
    field: 'ex_admin',
    hide: true,
    valueGetter: p =>
      p.data?.admin_name ||
      p.data?.created_by_name ||
      p.data?.created_by ||
      ''
  },

  // 20) ช่างผู้ดำเนินงาน
  {
    headerName: 'ช่างผู้ดำเนินงาน',
    field: 'ex_technician',
    hide: true,
    valueGetter: p =>
      p.data?.responsible_names ||
      p.data?.tech_name ||
      p.data?.completed_by ||
      ''
  },

  // 21) รหัสอุปกรณ์
  {
    headerName: "รหัสอุปกรณ์",
    field: "ex_asset_code",
    hide: true,
    valueGetter: p => p.data?.asset_code || ''
  },

  // 22) ชื่ออุปกรณ์
  {
    headerName: "ชื่ออุปกรณ์",
    field: "ex_asset_name",
    hide: true,
    valueGetter: p => p.data?.asset_name || ''
  },

  // 23) รุ่นอุปกรณ์
  {
    headerName: "รุ่น",
    field: "ex_asset_model",
    hide: true,
    valueGetter: p => p.data?.asset_model || ''
  },

  // 24) Serial No
  {
    headerName: "Serial No",
    field: "ex_asset_sn",
    hide: true,
    valueGetter: p => p.data?.asset_sn || ''
  },

  // 25) สรุปอะไหล่
  {
    headerName: 'สรุปอะไหล่',
    field: 'ex_parts_summary',
    hide: true,
    valueGetter: p => {
      const parts = p.data?.parts || [];
      if (!parts.length) return '';
      return parts.map(pt =>
        `${pt.wh || ''} | ${pt.pd_gen_code || ''} | ${pt.rpd_details_head || ''} | ` +
        `${pt.rpd_qty} ${pt.pd_unit || ''} | ${pt.rpd_price}`
      ).join('\n');
    }
  },

  // 26) รวมเงินอะไหล่ทั้งหมด
  {
    headerName: 'รวมเงินอะไหล่',
    field: 'ex_parts_total',
    hide: true,
    valueGetter: p =>
      (p.data?.parts || []).reduce(
        (sum, pt) => sum + (Number(pt.rpd_sum_money) || 0),
        0
      )
  },
 
];

// แปลง URL รูป → base64 สำหรับใส่ใน ExcelJS
async function getBase64FromUrl(url) {
  try {
    const resp = await fetch(url, { mode: 'cors' });
    const blob = await resp.blob();

    return await new Promise((resolve) => {
      const reader = new FileReader();
      reader.onloadend = () => resolve(reader.result);
      reader.readAsDataURL(blob);
    });
  } catch (e) {
    console.error('getBase64FromUrl error:', e);
    return null;
  }
}


function getCurrentSortModelForExport() {
  if (!gridApi) return [];

  // รองรับ AG Grid รุ่นใหม่ที่ใช้ column state

  if (typeof gridApi.getColumnState === 'function') {
    return gridApi.getColumnState()
      .filter(col => col.sort)
      .sort((a, b) => {
        const ai = a.sortIndex ?? 0;
        const bi = b.sortIndex ?? 0;
        return ai - bi;
      })
      .map(col => ({
        colId: col.colId,
        sort: col.sort
      }));
  }

  // เผื่อ AG Grid รุ่นเก่า
  if (typeof gridApi.getSortModel === 'function') {
    return gridApi.getSortModel();
  }

  return [];
}

async function fetchAllFilteredRowsForExport() {
  if (!gridApi) return [];

  const searchTerm = document.getElementById('grid-search')?.value || '';
  const filterModel = gridApi.getFilterModel ? gridApi.getFilterModel() : {};
  const sortModel = getCurrentSortModelForExport();

  // จำนวนทั้งหมดหลังกรอง จากตัวเลขที่ API ส่งมาให้หน้า grid
  let totalRows = parseInt(
    (document.getElementById('row-count')?.innerText || '0').replace(/,/g, ''),
    10
  );

  if (!totalRows || totalRows < 1) {
    if (typeof gridApi.paginationGetRowCount === 'function') {
      totalRows = gridApi.paginationGetRowCount();
    }
  }

  // กันกรณี row-count ยังไม่มา
  if (!totalRows || totalRows < 1) {
    totalRows = 100000;
  }

  const url = new URL('handle_repair_requests.php', window.location.href);
  url.searchParams.set('action', 'get_all');
  url.searchParams.set('ag_id', AG_ID);
  url.searchParams.set('startRow', 0);
  url.searchParams.set('endRow', totalRows);
  url.searchParams.set('search', searchTerm);
  url.searchParams.set('filterModel', JSON.stringify(filterModel || {}));

  if (sortModel && sortModel.length) {
    url.searchParams.set('sortModel', JSON.stringify(sortModel));
  }

  const res = await fetch(url.toString(), {
    method: 'GET',
    cache: 'no-store'
  });

  const result = await res.json();

  if (!result || !Array.isArray(result.rows)) {
    throw new Error(result?.message || result?.error || 'ไม่สามารถดึงข้อมูล Export ได้');
  }

  return result.rows;
}

async function exportToExcelFull() {
  if (!gridApi) return;

  Swal.fire({
    title: "กำลังสร้างไฟล์ Excel...",
    text: "กำลังดึงข้อมูลทั้งหมดตามเงื่อนไขที่กรอง",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  try {
    const workbook = new ExcelJS.Workbook();
    const sheet = workbook.addWorksheet("Repair Report");

    // 1) คอลัมน์หลักมาจาก excelExportColumns เดิม
    const cols = excelExportColumns.map(col => ({
      header: col.headerName,
      key: col.field,
      width: 28
    }));

    // 2) เพิ่มคอลัมน์รูปแจ้งซ่อม / รูปปิดงาน
    for (let i = 1; i <= 5; i++) {
      cols.push({
        header: `รูปแจ้งซ่อม ${i}`,
        key: `before_img_${i}`,
        width: 20
      });
    }

    for (let i = 1; i <= 5; i++) {
      cols.push({
        header: `รูปปิดงาน ${i}`,
        key: `after_img_${i}`,
        width: 20
      });
    }

    sheet.columns = cols;

    // ✅ จุดสำคัญ: ดึงข้อมูลทั้งหมดตาม filter/search/sort จาก API
    const rows = await fetchAllFilteredRowsForExport();

    if (!rows.length) {
      Swal.close();
      Swal.fire("ไม่พบข้อมูล", "ไม่มีข้อมูลสำหรับ Export ตามเงื่อนไขที่กรอง", "warning");
      return;
    }

    let rowIndex = 2;

    for (const data of rows) {
      const rowData = {};

      excelExportColumns.forEach(col => {
        const val = col.valueGetter
          ? col.valueGetter({ data })
          : (data[col.field] ?? "");
        rowData[col.field] = val;
      });

      for (let i = 1; i <= 5; i++) {
        rowData[`before_img_${i}`] = "";
        rowData[`after_img_${i}`]  = "";
      }

      const row = sheet.addRow(rowData);
      row.height = 120;
      row.alignment = { vertical: "middle", wrapText: true };

      const excelRowIndex = rowIndex;

      // รูปแจ้งซ่อม
      if (Array.isArray(data.images)) {
        for (let i = 0; i < Math.min(data.images.length, 5); i++) {
          const url = data.images[i];
          if (!url) continue;

          const base64 = await getBase64FromUrl(url);
          if (!base64) continue;

          const imgId = workbook.addImage({
            base64,
            extension: "png"
          });

          const colKey = `before_img_${i + 1}`;
          const colIndex = sheet.getColumn(colKey).number - 1;

          sheet.addImage(imgId, {
            tl: { col: colIndex + 0.15, row: excelRowIndex - 0.8 },
            ext: { width: 90, height: 90 }
          });
        }
      }

      // รูปปิดงาน
      if (Array.isArray(data.completed_images)) {
        for (let i = 0; i < Math.min(data.completed_images.length, 5); i++) {
          const url = data.completed_images[i];
          if (!url) continue;

          const base64 = await getBase64FromUrl(url);
          if (!base64) continue;

          const imgId = workbook.addImage({
            base64,
            extension: "png"
          });

          const colKey = `after_img_${i + 1}`;
          const colIndex = sheet.getColumn(colKey).number - 1;

          sheet.addImage(imgId, {
            tl: { col: colIndex + 0.15, row: excelRowIndex - 0.8 },
            ext: { width: 90, height: 90 }
          });
        }
      }

      rowIndex++;
    }

    const header = sheet.getRow(1);
    header.font = { bold: true, color: { argb: "FFFFFFFF" } };
    header.fill = {
      type: "pattern",
      pattern: "solid",
      fgColor: { argb: "006B9F" }
    };
    header.alignment = { horizontal: "center", vertical: "middle" };
    header.height = 25;

    const buffer = await workbook.xlsx.writeBuffer();

    saveAs(
      new Blob([buffer]),
      `Repair_Report_${new Date().toISOString().slice(0, 10)}.xlsx`
    );

    Swal.fire("สำเร็จ", `ดาวน์โหลดไฟล์เรียบร้อยแล้ว (${rows.length} รายการ)`, "success");

  } catch (err) {
    console.error(err);
    Swal.fire("ผิดพลาด", err.message || "ไม่สามารถสร้างไฟล์ Excel ได้", "error");
  }
}

// ===============================
// 2) คอลัมน์ที่ใช้แสดงบนหน้าจอ (UI จริง)
// ===============================
const uiColumns = [
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
              <div class="flex items-center h-full cursor-pointer group" onclick='ImageCarousel.open(${JSON.stringify(imgs)})'>
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
  width: 150,
  pinned: "left",
  filter: DocDateUrgencyFilter,   // 🆕 ใช้ custom filter
  // ไม่มี filterParams พิเศษละ
  cellRenderer: (p) => {
    const d   = p.data?.report_date || '';
    const t   = p.data?.report_time || '';
    const doc = p.data?.rp_format   || '';
    const urg = p.data?.urgency     || '';

    const { label: urgLabel, dotClass } = urgencyMeta(urg);

    return `
      <div class="flex flex-col justify-center py-2 leading-snug">
        <!-- บรรทัดบน: เอกสาร + เวลา -->
        <div class="flex items-center justify-between gap-2">
          <div class="text-[11px] font-semibold text-slate-900 truncate">
            ${doc ? escapeHtml(doc) : '-'}
          </div>
          <div class="text-[10px] text-slate-400 font-medium whitespace-nowrap">
            ${escapeHtml(formatThaiTime(t))}
          </div>
        </div>

        <!-- บรรทัดล่าง: วันที่ + จุดสีความเร่งด่วน -->
        <div class="mt-0.5 flex items-center justify-between gap-2">
          <div class="text-[10px] text-slate-500 font-medium">
            ${escapeHtml(formatThaiDate(d))}
          </div>
          <div class="flex items-center gap-1 text-[10px] text-slate-500">
            <span class="inline-block w-1.5 h-1.5 rounded-full ${dotClass}"></span>
            <span class="truncate">${escapeHtml(urgLabel)}</span>
          </div>
        </div>
      </div>
    `;
  }
},
      {
        headerName: "ผู้แจ้ง",
        field: "name",
        width: 180,
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
      { headerName:"อาคาร", field:"building_name", width:150, filter:'agSetColumnFilter', filterParams:getFilterParams('building_name') },
      { headerName:"ชั้น",  field:"floor_name",    width:90, filter:'agSetColumnFilter', filterParams:getFilterParams('floor_name') },
      { headerName:"ห้อง",  field:"room_name",     width:100, filter:'agSetColumnFilter', filterParams:getFilterParams('room_name') },
      {
        headerName: "อาการเสีย/ปัญหา",
  width: 400,
        field: "problem_detail",
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
        width:100,
        filter:"agSetColumnFilter",
        filterParams: { ...getFilterParams("status"), valueFormatter:(p)=>statusToThai(p.value) },
        cellRenderer: statusRenderer
      },
      {
        headerName:"ช่างผู้ดำเนินงาน",
        field:"responsible_names",
        width:220,
        filter:'agTextColumnFilter',
        cellRenderer: (p) => `
          <div class="text-[12px] font-bold text-slate-700 leading-snug whitespace-pre-line">
            ${escapeHtml(p.value || '-')}
          </div>
        `
      },
      {
        headerName:"ผู้บันทึก",
        flex: 1,
        field: "created_by", 
  filter: 'agSetColumnFilter',
  filterParams: getFilterParams('created_by'),
  valueFormatter: (p) => createdByNameMap[p.value] || p.value,
      },
      {
  headerName:"จัดการ",
  field:"actions",
  width:195,
  pinned:"right",
  cellRenderer:(params)=>{
    const wrap = document.createElement('div');
    wrap.className = "flex items-center justify-center gap-2 h-full";

    // mkBtn ใหม่: มี title เพิ่มเข้ามา
    const mkBtn = (cls, icon, title, handler) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = cls;
      b.innerHTML = `<i data-lucide="${icon}" class="w-4 h-4"></i>`;
      if (title) b.title = title;
      if (typeof handler === 'function') {
        b.addEventListener('click', handler);
      }
      return b;
    };

    const row = params.data || {};

    const btnEdit = mkBtn(
      "w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center",
      "panel-right-open",
      "แก้ไขใบงาน",
      () => openDrawer('edit', row)
    );

    const btnPrint = mkBtn(
      "w-9 h-9 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-indigo-600 flex items-center justify-center",
      "printer",
      "พิมพ์ใบงาน",
      () => {
        const rpId = row.id || row.rp_id;   // ใช้ id จริงจากแถว
        if (!rpId) return;

        const url = 'print_ver2.php?rp_id=' + encodeURIComponent(rpId);
        const width  = 1000;
        const height = 700;
        const left = (window.screen.width  - width)  / 2;
        const top  = (window.screen.height - height) / 2;

        window.open(
          url,
          '_blank',
          `width=${width},height=${height},top=${top},left=${left},scrollbars=yes,resizable=yes`
        );
      }
    );

    const currentStatus = normalizeStatus(row.status);

/*
 * ประเมินแล้ว เมื่อ:
 * 1. repair_requests.has_feedback = 1
 * 2. รองรับข้อมูลเก่าที่สถานะเป็น feedback
 * 3. รองรับกรณี API ส่ง evaluation_status กลับมาด้วย
 */
const hasEvaluated =
  Number(row.has_feedback || 0) === 1 ||
  currentStatus === 'feedback' ||
  String(row.evaluation_status || '').toLowerCase() === 'completed';

const evalButtonClass = hasEvaluated
  ? `relative w-9 h-9 rounded-xl
     border border-emerald-600
     bg-emerald-600 text-white
     hover:bg-emerald-700 hover:border-emerald-700
     shadow-sm shadow-emerald-200
     ring-2 ring-emerald-100
     flex items-center justify-center
     transition-all duration-200`
  : `relative w-9 h-9 rounded-xl
     border border-sky-200
     bg-sky-50 text-sky-700
     hover:bg-sky-100 hover:border-sky-300
     flex items-center justify-center
     transition-all duration-200`;

const btnEval = mkBtn(
  evalButtonClass,
  hasEvaluated ? "badge-check" : "mail-check",
  hasEvaluated
    ? "ประเมินแล้ว • คลิกเพื่อดูผลหรือส่งประเมินรอบใหม่"
    : "ตรวจสอบ / ส่งแบบประเมิน",
  () => {
    if (window.RepairEvaluationCenter?.open) {
      window.RepairEvaluationCenter.open(row);
    } else {
      Swal.fire(
        'ผิดพลาด',
        'ไม่พบโมดูลส่งแบบประเมิน กรุณารีเฟรชหน้า',
        'error'
      );
    }
  }
);

/* จุดสถานะมุมขวาบนแบบ Enterprise */
if (hasEvaluated) {
  btnEval.insertAdjacentHTML(
    'beforeend',
    `
      <span
        class="absolute -top-1 -right-1
               w-3.5 h-3.5 rounded-full
               bg-emerald-400 border-2 border-white
               shadow-sm"
        title="ประเมินแล้ว">
      </span>
    `
  );

  btnEval.setAttribute('aria-label', 'ประเมินแล้ว');
}
    const canCancel = !['cancel', 'completed', 'feedback'].includes(currentStatus);

    wrap.append(btnEdit, btnPrint, btnEval);

    if (canCancel) {
      const btnCancel = mkBtn(
        "w-9 h-9 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center",
        "x-circle",
        "ยกเลิกใบงาน",
        () => cancelRepair(row)
      );
      wrap.append(btnCancel);
    }

    setTimeout(safeIcons, 0); // ให้ lucide สร้าง icon ใหม่
    return wrap;
  }
}
    ];

async function cancelRepair(row) {
  const rpId = row?.id || row?.rp_id;
  const rpNo = row?.rp_format || (rpId ? `#${rpId}` : '-');

  if (!rpId) {
    Swal.fire('ผิดพลาด', 'ไม่พบรหัสใบงานที่ต้องการยกเลิก', 'error');
    return;
  }

  const currentStatus = normalizeStatus(row?.status);
  if (['cancel', 'completed', 'feedback'].includes(currentStatus)) {
    Swal.fire('ไม่สามารถยกเลิกได้', 'ใบงานนี้ถูกปิด/ประเมิน/ยกเลิกแล้ว', 'warning');
    return;
  }

  const confirm = await Swal.fire({
    icon: 'warning',
    title: 'ยืนยันยกเลิกใบงาน?',
    html: `ต้องการยกเลิกใบงาน <b>${escapeHtml(rpNo)}</b> ใช่ไหม?<br><span class="text-xs text-slate-500">ระบบจะอัปเดตสถานะเป็น <b>cancel</b></span>`,
    showCancelButton: true,
    confirmButtonText: 'ยืนยันยกเลิก',
    cancelButtonText: 'ไม่ยกเลิก',
    reverseButtons: true,
    customClass: {
      confirmButton: 'px-5 py-2 rounded-xl bg-rose-600 text-white font-semibold text-sm hover:bg-rose-700',
      cancelButton: 'px-5 py-2 rounded-xl bg-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-300',
    }
  });

  if (!confirm.isConfirmed) return;

  try {
    Swal.fire({
      title: 'กำลังยกเลิกใบงาน...',
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => Swal.showLoading()
    });

    const fd = new FormData();
    fd.append('id', rpId);

    const url = new URL('handle_repair_requests.php', window.location.href);
    url.searchParams.set('action', 'cancel');
    url.searchParams.set('ag_id', AG_ID);

    const res = await fetch(url, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    });

    const json = await res.json();
    if (!json?.success) {
      throw new Error(json?.message || json?.error || 'ยกเลิกใบงานไม่สำเร็จ');
    }

    Swal.close();
    Swal.fire({
      icon: 'success',
      title: 'ยกเลิกใบงานแล้ว',
      text: 'อัปเดตสถานะเป็น cancel เรียบร้อย',
      timer: 1400,
      showConfirmButton: false
    });

    closeDrawer?.();
    refreshGrid();
  } catch (err) {
    console.error(err);
    Swal.close();
    Swal.fire('ผิดพลาด', err?.message || 'กรุณาลองใหม่', 'error');
  }
}

async function openConclusionPopup(row) {
  const rpNo = row.rp_format || `#${row.id}`;
  const currentScore  = row.conclusion_score  || '';
  const currentResult = row.conclusion_result || '';

  const popupHtml = `
    <div class="space-y-5 text-sm text-slate-700">

      <!-- Header card -->
      <div class="bg-slate-50 border border-slate-100 rounded-2xl px-4 py-3 flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-sky-500 text-white flex items-center justify-center shadow-sm">
          <i data-lucide="check-circle-2" class="w-5 h-5"></i>
        </div>
        <div class="min-w-0">
          <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
            Service Report
          </p>
          <p class="text-sm font-bold text-slate-800 truncate">${rpNo}</p>
        </div>
      </div>

      <!-- Body: 2 columns -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <!-- ซ้าย: ประเมินผลงาน -->
        <div class="bg-slate-50 rounded-2xl border border-slate-100 px-4 py-3">
          <p class="text-xs font-semibold text-slate-500 mb-2 flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
            ประเมินผลงาน
          </p>
          <div class="space-y-1.5 text-sm">
            <label class="flex items-center gap-2 cursor-pointer hover:text-sky-700">
              <input type="radio" name="sw_score" value="4"
                     class="h-4 w-4 text-sky-600 border-slate-300">
              <span>ดีมาก</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer hover:text-sky-700">
              <input type="radio" name="sw_score" value="3"
                     class="h-4 w-4 text-sky-600 border-slate-300">
              <span>ดี</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer hover:text-sky-700">
              <input type="radio" name="sw_score" value="2"
                     class="h-4 w-4 text-sky-600 border-slate-300">
              <span>พอใช้</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer hover:text-sky-700">
              <input type="radio" name="sw_score" value="1"
                     class="h-4 w-4 text-sky-600 border-slate-300">
              <span>ปรับปรุง</span>
            </label>
          </div>
        </div>

        <!-- ขวา: สรุปผลการทำงาน -->
        <div class="bg-slate-50 rounded-2xl border border-slate-100 px-4 py-3">
          <p class="text-xs font-semibold text-slate-500 mb-2 flex items-center gap-2">
            <span class="w-1.5 h-1.5 rounded-full bg-sky-500 inline-block"></span>
            สรุปผลการทำงาน
          </p>
          <div class="space-y-1.5 text-sm">
            <label class="flex items-center gap-2 cursor-pointer hover:text-sky-700">
              <input type="radio" name="sw_result" value="completed"
                     class="h-4 w-4 text-sky-600 border-slate-300">
              <span>Completed – เสร็จสมบูรณ์</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer hover:text-sky-700">
              <input type="radio" name="sw_result" value="followup"
                     class="h-4 w-4 text-sky-600 border-slate-300">
              <span>To Follow Up – ต้องดำเนินการต่อ</span>
            </label>
          </div>
        </div>

      </div>

    </div>
  `;

  const { value: formValues } = await Swal.fire({
    title: '',                 // เราใช้ header card แทน
    html: popupHtml,
    width: 640,
    showCancelButton: true,
    confirmButtonText: 'บันทึก',
    cancelButtonText: 'ยกเลิก',
    focusConfirm: false,
    customClass: {
      popup: 'rounded-3xl !pt-6',
      confirmButton: 'px-5 py-2 rounded-xl bg-sky-600 text-white font-semibold text-sm hover:bg-sky-700 focus:ring-2 focus:ring-sky-300',
      cancelButton: 'px-5 py-2 rounded-xl bg-slate-200 text-slate-700 font-semibold text-sm hover:bg-slate-300',
    },
    didOpen: () => {
      // set ค่าเดิมถ้ามี
      if (currentScore) {
        const s = document.querySelector(`input[name="sw_score"][value="${currentScore}"]`);
        if (s) s.checked = true;
      }
      if (currentResult) {
        const r = document.querySelector(`input[name="sw_result"][value="${currentResult}"]`);
        if (r) r.checked = true;
      }
      // ให้ lucide สร้าง icon ใน popup
      if (window.lucide && typeof lucide.createIcons === 'function') {
        lucide.createIcons();
      }
    },
    preConfirm: () => {
      const scoreEl  = document.querySelector('input[name="sw_score"]:checked');
      const resultEl = document.querySelector('input[name="sw_result"]:checked');
      return {
        score:  scoreEl  ? scoreEl.value  : '',
        result: resultEl ? resultEl.value : ''
      };
    }
  });

  if (!formValues) return; // กดยกเลิก

  // === บันทึกลง backend (ปรับ URL / ชื่อ field ตามระบบคุณ) ===
  try {
    const fd = new FormData();
    fd.append('id', row.id);
    fd.append('conclusion_score',  formValues.score);
    fd.append('conclusion_result', formValues.result);


    const res  = await fetch('maintenance_info.php?action=save_conclusion', {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    });
    const json = await res.json();

    if (!json.success) throw new Error(json.error || 'save failed');

    Swal.fire('บันทึกแล้ว', 'อัปเดตผลการประเมินเรียบร้อย', 'success');
    if (window.gridApi) {
      gridApi.refreshServerSide();   // ให้ grid โหลดค่าใหม่
    }
  } catch (err) {
    console.error(err);
    Swal.fire('ผิดพลาด', err.message, 'error');
  }
}
const columnDefs = [
  ...excelExportColumns,   // ซ่อน แต่ใช้ตอน Excel export
  ...uiColumns             // แสดงบนหน้าจอ
];

    /* =========================
       ✅ Grid Options
       ========================= */
    const gridOptions = {
      columnDefs,
  defaultColDef: {
    resizable: true,
    sortable: true,
    filter: true,
    flex: 0,
  },

  // ให้ Excel export ใช้หัวตาม excelExportColumns เท่านั้น
  defaultExcelExportParams: {
    fileName: 'repair_requests.xlsx',
    allColumns: false,
    columnKeys: excelExportColumns.map(c => c.field)
  },
  
  // 🔴 ตรงนี้คือ key ที่จะเปลี่ยนเมนูคลิกขวา
  getContextMenuItems: (params) => {
    const defaultItems = params.defaultItems ? params.defaultItems.slice() : [];
    const exportIndex = defaultItems.indexOf('export');

    if (exportIndex > -1) {
      // แทนเมนู export เดิม ด้วยเมนูของเราเอง
      defaultItems[exportIndex] = {
        name: 'Export',
        subMenu: [
       //   {
//            name: 'CSV Export',
//            action: () => params.api.exportDataAsCsv()
//          },
          {
            name: 'Excel Export',
            action: () => exportToExcelFull()   // ✅ เรียกฟังก์ชันที่คุณเขียน
          }
        ]
      };
    } else {
      // ถ้าไม่มี 'export' อยู่แล้ว ก็เพิ่มเมนูใหม่เข้าไปเฉย ๆ
      defaultItems.push('separator');
      defaultItems.push({
        name: 'Excel Export',
        action: () => exportToExcelFull()
      });
    }

    return defaultItems;
  },

	  components: {
		DocDateUrgencyFilter: DocDateUrgencyFilter
	  },
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

	  // ✅ เริ่ม auto refresh แบบปลอดภัย
	  startAutoRefreshRepairList();
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
	
	// โหลดรูปปิดงานจาก row.completed_images เข้า CL แล้วให้ uploader render
	function setCloseImagesFromRow(row){
	  CL.keepUrls = (row?.completed_images || []).filter(Boolean).slice(0, MAX_CLOSE_FILES);
	  CL.newFiles = [];
	  initCloseUploader();
	}
	
	
function setActionStep(step) {
  const step1 = document.getElementById('step-accept');
  const step2 = document.getElementById('step-close');
  const helperBox = document.getElementById('actions-helper');
  if (!step1 || !step2) return;

  if (step === 1) {
    step1.classList.remove('opacity-40', 'pointer-events-none');
    step2.classList.add('hidden', 'opacity-40', 'pointer-events-none');

    if (helperBox) {
      helperBox.classList.remove('hidden');
    }
  }

  if (step === 2) {
    step2.classList.remove('hidden', 'opacity-40', 'pointer-events-none');

    if (helperBox) {
      helperBox.classList.add('hidden');
    }

    step2.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

// เช็คว่า Step 1 กรอกครบหรือยัง
function checkStep1Completed() {
  const acDate = (document.getElementById('ac_date')?.value || '').trim();
  const acTime = (document.getElementById('ac_time')?.value || '').trim();
  const acTechCount = getSelectedMultiValues('ac_tech').length;

  const done = acDate && acTime && acTechCount > 0;

  if (done) {
    setActionStep(2);
  }
}

// ผูก event กับฟิลด์ใน Step 1 (ให้เช็คทุกครั้งที่กรอก/เปลี่ยนค่า)
function initActionStepWatcher() {
  ['ac_date', 'ac_time', 'ac_tech'].forEach(id => {
    const el = document.getElementById(id);
    if (!el || el.dataset.stepWatcher === '1') return; // กันผูกซ้ำหลายรอบ

    el.dataset.stepWatcher = '1';
    el.addEventListener('change', checkStep1Completed);
    el.addEventListener('blur', checkStep1Completed);
  });
}

// เรียกตอนโหลดหน้าให้ผูก event ไว้ก่อน
document.addEventListener('DOMContentLoaded', initActionStepWatcher);
	
	
	
 	    /* =========================
       ✅ Drawer
       ========================= */
	   
	   
	    
let UD = { mode:'edit', row:null };  // ไม่ต้องมี closeFiles แล้ว

// รูปปิดงาน
const MAX_CLOSE_FILES = 5;
let CL = { keepUrls: [], newFiles: [] };

    // 👉 เพิ่มฟังก์ชันนี้เข้ามาตรงนี้
   function fillFormFromRow(row){
  if(!row) return;
		
		  const setVal = (id, v) => {
			const el = document.getElementById(id);
			if(el) el.value = v ?? '';
		  };
		
		  // ========= 1. แท็บ “รายละเอียดใบงาน” =========
		  fillEditTab(row);   // ใช้ของเดิมที่มีอยู่แล้ว
		
		  // ========= 2. แท็บ “การดำเนินการ – รับงาน / จ่ายงาน” =========
			let acDate = '';
			let acTime = '';
			
			// 1) ใช้ process_date ถ้ามี
			if (row.process_date) {
			  acDate = row.process_date;
			}
			
			// 2) ใช้ process_time ถ้ามีและไม่ใช่ 00:00:00
			if (row.process_time && row.process_time !== '00:00:00') {
			  acTime = row.process_time.slice(0, 5); // HH:MM
			}
			
			// 3) ถ้า process_date ยังว่าง ลองดึงจาก received_date
			if (!acDate && row.received_date) {
			  const [d, t] = row.received_date.split(' ');
			  acDate = d || '';
			  if (!acTime && t && t !== '00:00:00') {
				acTime = t.slice(0, 5);
			  }
			}
			
			// 4) เซ็ตลงฟอร์ม
			setVal('ac_date', acDate);
			setVal('ac_time', acTime);
			const responsibleIds = Array.isArray(row.responsible_user_ids) && row.responsible_user_ids.length
			  ? row.responsible_user_ids
			  : (row.received_by ? [row.received_by] : []);
			
			setMultiSelected('ac_tech', responsibleIds);
			setVal('ac_note', '');
		
		  // ========= 3. แท็บ “ปิดงาน / รายงานผล” =========
		  setVal('cl_date', row.completed_date || '');               // "2026-02-04"
		  setVal('cl_time', (row.completed_time || '').slice(0,5));  // "17:14" จาก "17:14:00"
		  setVal('cl_service_type', row.service_type || '');
		  setVal('cl_job_type', row.job_type || '');
		  setVal('cl_note', row.completed_solution || '');
		
		  // ========= 4. รูป “ปิดงาน” จาก completed_images =========
		   setCloseImagesFromRow(row);
		
		  // ========= 5. parts (อะไหล่/วัสดุสิ้นเปลือง) =========
		  const wrap = document.getElementById('parts_wrap');
		  if(wrap){
			wrap.innerHTML = '';  // เคลียร์ของเก่า
			(row.parts || []).forEach(p => {
			  const item = {
				ps_id: 0,
				pd_id: p.rpd_product_id || 0,
				wh_id: p.wh_id || 0,
				 wh:      p.wh || '',                    // ชื่อคลังจาก JSON
				name: p.rpd_details_head || '',
				code: p.pd_gen_code || '',
				detail: p.rpd_details || '',
				brand: p.rpd_brand || '',
				wh:      p.wh || '',  
				price: Number(p.rpd_price || 0),
				stock: 0,
				stockMax: 0,
				unit: p.pd_unit || ''
			  };
			  addPartRow(item, String(p.rpd_qty || '1'));
			});
			updatePartsTotal();  
		  }
		  
		  
		    const hasCompleted =
    !!(row.completed_date || row.completed_time || row.completed_solution);

  if (hasCompleted) {
    setActionStep(2);
  } else {
    // งานที่ยังไม่ปิด -> เน้น Step 1 ก่อน
    setActionStep(1);
  }

  // ผูก event watcher ให้ Step 1 (เผื่อเปิด drawer ครั้งแรก)
  initActionStepWatcher();

  // เผื่อกรณีมีค่า ac_date/ac_time/ac_tech อยู่แล้ว
  // เช่น กรอกไว้ครึ่งหนึ่ง / ดึงมาจาก process_date
  checkStep1Completed();
}
	
	
    async function openDrawer(mode, rowData){
      UD.mode = mode || 'edit';

      // ✅ ดึงรายละเอียดเต็มจาก API (รวมรูป/สถานที่/ทรัพย์สิน) เพื่อให้ Drawer แสดงครบ
      let fullRow = rowData || null;
      try{
        const id = rowData?.id ?? rowData?.rp_id ?? '';
        if(id){
          const url = new URL('handle_repair_requests.php', window.location.href);
          url.searchParams.set('action','get_one');
          url.searchParams.set('ag_id', AG_ID);
          url.searchParams.set('id', id);
          const r = await fetch(url);
          const j = await r.json();
          if((j?.success && j?.row) || (j?.ok && j?.data)) fullRow = (j.row || j.data);
        }
      }catch(e){
        console.warn('get_one failed, fallback rowData', e);
      }

      const row = fullRow || rowData || null;
      UD.row = row;

      document.getElementById('ud_backdrop')?.classList.remove('hidden');
      document.getElementById('ud_drawer')?.classList.remove('translate-x-full');

      document.getElementById('ud_title').innerText = 'รายละเอียดงานซ่อม';
      document.getElementById('ud_subtitle').innerText = `เลขที่เอกสาร: ${row?.rp_format || '-'}`;

      fillFormFromRow(row);
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
        wrap.innerHTML = '';
        const hint = document.getElementById('ed_hint');
        const mid  = document.getElementById('ed_mid') || document.querySelector('#ed_dropzone .up-mid');
        hint?.classList.remove('hidden');
        mid?.classList.remove('has-items');
        return;
      }
      const hint = document.getElementById('ed_hint');
      const mid  = document.getElementById('ed_mid') || document.querySelector('#ed_dropzone .up-mid');
      hint?.classList.add('hidden');
      mid?.classList.add('has-items');
	
	  wrap.innerHTML = list.map((src, idx)=>`
		<button type="button"
		  class="up-thumb group"
		  onclick='ImageCarousel.open(${JSON.stringify(list)})'
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
		  setVal('ed_building', row?.building_id || row?.area_id || row?.building || '');
		setVal('ed_floor',    row?.floor_id    || row?.ac_id   || row?.floor    || '');
		setVal('ed_room',     row?.room_id     || row?.ar_id   || row?.room     || '');
		
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
  const input   = document.getElementById('cl_images');
  const preview = document.getElementById('cl_preview');
  const count   = document.getElementById('cl_img_count');
  const err     = document.getElementById('cl_img_err');
  const hint    = document.getElementById('cl_hint');
  const mid     = document.getElementById('cl_mid') || document.querySelector('#cl_dropzone .up-mid');
  if(!input || !preview || !count) return;

  const total = () => (CL.keepUrls.length + CL.newFiles.length);

  const render = () => {
    const n = total();
    count.textContent = `${n}/${MAX_CLOSE_FILES}`;
    err?.classList.toggle('hidden', n <= MAX_CLOSE_FILES);

    const items = [
      ...CL.keepUrls.map(u => ({ type:'url',  value:u })),
      ...CL.newFiles.map(f => ({ type:'file', value:f })),
    ];

    if(!items.length){
      preview.innerHTML = '';
      hint?.classList.remove('hidden');
      mid?.classList.remove('has-items');
      return;
    }

    hint?.classList.add('hidden');
    mid?.classList.add('has-items');

    const carouselUrls = items.map(it =>
      it.type === 'url' ? it.value : URL.createObjectURL(it.value)
    );

    preview.innerHTML = items.map((it, idx) => {
      const src = carouselUrls[idx];
      return `
        <div class="up-thumb group">
          <button type="button"
            class="w-full h-full"
            onclick='ImageCarousel.open(${JSON.stringify(carouselUrls)})'>
            <img src="${src}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
          </button>
          <button type="button" data-idx="${idx}" class="up-x">×</button>
        </div>
      `;
    }).join('');

    // ปุ่มลบ
    preview.querySelectorAll('button[data-idx]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const i = Number(e.currentTarget.dataset.idx);
        if(i < CL.keepUrls.length) CL.keepUrls.splice(i, 1);
        else CL.newFiles.splice(i - CL.keepUrls.length, 1);
        render();
      });
    });
  };
  
    // 🔧 ป้องกันการ init ซ้ำ (วางตรงนี้เท่านั้น)
  if (input.dataset.inited === '1') {
    render();      // แค่อัปเดตหน้าตาตาม CL ปัจจุบัน
    return;
  }
  input.dataset.inited = '1';

  // เลือกไฟล์จาก input
  input.addEventListener('change', () => {
    const incoming = Array.from(input.files || [])
      .filter(f => f.type.startsWith('image/'));
    input.value = '';

    const canAdd = MAX_CLOSE_FILES - total();
    if(canAdd <= 0){
      err?.classList.remove('hidden');
      return;
    }

    CL.newFiles.push(...incoming.slice(0, canAdd));
    render();
  });

  // กรอบ dropzone + คลิก
  const box =
    document.getElementById('cl_dropzone') ||
    preview.closest('.rounded-2xl') ||
    preview.closest('.rounded-xl')  ||
    preview.parentElement;

  if(box){
    // คลิกพื้นที่ว่าง → เปิดเลือกไฟล์
    box.addEventListener('click', (e) => {
      if(e.target.closest('button')) return;
      input.click();
    });

    box.addEventListener('dragover', (e) => {
      e.preventDefault();
      box.classList.add('ring-2','ring-rose-300');
    });

    box.addEventListener('dragleave', () => {
      box.classList.remove('ring-2','ring-rose-300');
    });

    box.addEventListener('drop', (e) => {
      e.preventDefault();
      box.classList.remove('ring-2','ring-rose-300');

      const files = Array.from(e.dataTransfer?.files || [])
        .filter(f => f.type.startsWith('image/'));

      const canAdd = MAX_CLOSE_FILES - total();
      CL.newFiles.push(...files.slice(0, canAdd));
      render();
    });
  }

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
  const hint = document.getElementById('ed_hint');
  const mid  = document.getElementById('ed_mid') || document.querySelector('#ed_dropzone .up-mid');
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
      preview.innerHTML = '';
      hint?.classList.remove('hidden');
      mid?.classList.remove('has-items');
      return;
    }
    hint?.classList.add('hidden');
    mid?.classList.add('has-items');

    preview.innerHTML = items.map((it, idx)=>{
      const src = (it.type==='url') ? it.value : URL.createObjectURL(it.value);
      return `
        <div class="up-thumb group">
          <button type="button" onclick='ImageCarousel.open(${JSON.stringify(
  items.map(x=> x.type === "url" ? x.value : URL.createObjectURL(x.value))
)})'
            class="w-full h-full">
            <img src="${src}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
          </button>

          <button type="button" data-idx="${idx}"
            class="up-x">×</button>
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

	 // 🔧 ป้องกันการ init ซ้ำ
  if (input.dataset.inited === '1') {
    render();
    return;
  }
  input.dataset.inited = '1';
  
  input.addEventListener('change', ()=>{
    const incoming = Array.from(input.files || []).filter(f=>f.type.startsWith('image/'));
    input.value = '';

    const canAdd = MAX_EDIT_FILES - total();
    if(canAdd <= 0){ err?.classList.remove('hidden'); return; }

    ED.newFiles.push(...incoming.slice(0, canAdd));
    render();
  });
  
  const box =
    document.getElementById('ed_dropzone') ||
    preview.closest('.rounded-2xl') ||
    preview.closest('.rounded-xl')  ||
    preview.parentElement;
	if(box){
      // ✅ คลิกในกรอบเพื่อเลือกไฟล์ (ยกเว้นคลิกปุ่ม/รูป)
      box.addEventListener('click', (e)=>{
        if(e.target.closest('button')) return;
        input.click();
      });

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


   // ✅ รวมค่าวัสดุ/อะไหล่ทั้งหมด
function updatePartsTotal(){
  const wrap = document.getElementById('parts_wrap');
  const totalEl = document.getElementById('parts_total');
  if(!wrap || !totalEl) return;

  let total = 0;
  wrap.querySelectorAll('#parts_wrap > div').forEach(row => {
    const sum = parseFloat(row.dataset.sum || '0') || 0;
    total += sum;
  });

  totalEl.textContent = total.toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}
function addPartRow(item, qty = '1') {
  const wrap = document.getElementById('parts_wrap');
  if (!wrap || !item) return;

  const row = document.createElement('div');
  row.className = "grid grid-cols-12 gap-2 items-center";

  // ✅ เก็บข้อมูลทั้งหมดสำหรับส่งไป API
  row.dataset.productId   = String(item.pd_id || '');
  row.dataset.stockId     = String(item.ps_id || '');
  row.dataset.whId        = String(item.wh_id || '');
  row.dataset.whName      = String(item.wh_name || item.wh || '');
  row.dataset.model       = String(item.model || '');
  row.dataset.genCode     = String(item.code || '');
  row.dataset.detailsHead = String(item.name || '');
  row.dataset.details     = String(item.detail || '');
  row.dataset.brand       = String(item.brand || '');
  row.dataset.price       = String(item.price || 0);
  row.dataset.unit        = String(item.unit || '');

  row.innerHTML = `
    <div class="col-span-7">
      <div class="text-[11px] font-bold text-slate-800">
        ${escapeHtml(item.name || '')}
      </div>
      <div class="text-[10px] text-slate-500">
        <span class="font-semibold">รหัส:</span> ${escapeHtml(item.code || '')}
        ${item.brand ? ` | <span class="font-semibold">ยี่ห้อ:</span> ${escapeHtml(item.brand)}` : ``}
        ${(item.wh_name || item.wh) ?
          ` | <span class="font-semibold">คลัง:</span> ${escapeHtml(item.wh_name || item.wh || '')}`
          : ``}
      </div>
      <div class="text-[10px] text-slate-500 truncate">
        ${escapeHtml(item.detail || '')}
      </div>
      ${item.model ? `
      <div class="text-[10px] text-slate-500">
        <span class="font-semibold">รุ่น/ชนิด:</span> ${escapeHtml(item.model || '')}
      </div>` : ``}
      <div class="text-[10px] text-emerald-700 font-semibold">
        ราคา/หน่วย:
        ${Number(item.price || 0).toLocaleString(undefined, {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        })}
        (${escapeHtml(item.unit || '')})
      </div>
    </div>

    <div class="col-span-3">
      <input type="number" min="0" step="1" data-role="qty"
        class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-[12px] font-semibold text-right"
        value="${escapeHtml(qty)}" />
      <div class="mt-1 text-[10px] text-right text-slate-500">
        รวม: <span data-role="sum">0.00</span>
      </div>
    </div>

    <div class="col-span-2 flex justify-end">
      <button type="button"
        class="h-9 px-2 rounded-xl border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100">
        <i data-lucide="trash-2" class="w-4 h-4"></i>
      </button>
    </div>
  `;

  const qtyInput = row.querySelector('input[data-role="qty"]');
  const sumEl    = row.querySelector('[data-role="sum"]');

  function recalcRow() {
    const qtyVal = parseFloat(qtyInput.value || '0') || 0;
    const price  = parseFloat(row.dataset.price || '0') || 0;
    const sum    = qtyVal * price;

    sumEl.textContent = sum.toLocaleString(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });

    row.dataset.qty = String(qtyVal);
    row.dataset.sum = String(sum);

    updatePartsTotal();
  }

  qtyInput.addEventListener('input', recalcRow);
  row.querySelector('button').addEventListener('click', () => {
    row.remove();
    updatePartsTotal();
  });

  wrap.appendChild(row);
  recalcRow();
  safeIcons();
}
   async function saveDrawer() {
  if (!UD || !UD.row) return;

  const fd = new FormData();

  // ========== 1) id หลัก ==========
  fd.append('id', UD.row.id);

  // helper เอาค่าจาก input ถ้ามี
  const getVal = (id) => {
    const el = document.getElementById(id);
    return el ? (el.value || '') : '';
  };

  // ========== 2) ฟิลด์ส่วนหัวใบแจ้ง (edit) ==========
  fd.append('report_date',    getVal('ed_report_date'));
  fd.append('report_time',    getVal('ed_report_time'));
  fd.append('name',           getVal('ed_name'));
  fd.append('phone',          getVal('ed_phone'));
  fd.append('building',       getVal('ed_building'));
  fd.append('floor',          getVal('ed_floor'));
  fd.append('room',           getVal('ed_room'));
  fd.append('problem_detail', getVal('ed_problem'));
  fd.append('urgency',        getVal('ed_urgency'));
  fd.append('machine_id', document.getElementById('ed_asset_id')?.value || '');

  // ========== 3) ฟิลด์รับงาน (accept) ==========
  const acDate = getVal('ac_date');
  const acTime = getVal('ac_time');
  const acTechIds = getSelectedMultiValues('ac_tech');
	const acTech = acTechIds[0] || '';
  const acNote = getVal('ac_note');

  // map ไปชื่อฟิลด์ที่ PHP รองรับ
  fd.append('received_date', acDate);   // วันที่รับงาน
  fd.append('process_date',  acDate);   // สมมติ process_date = วันเดียวกับรับงาน
  fd.append('process_time',  acTime);
  fd.append('received_by',   acTech);
  acTechIds.forEach(userId => {
	  fd.append('responsible_user_ids[]', userId);
	});
  if (acNote) {
    fd.append('note', acNote); // note ตอนรับงาน
  }

  // ========== 4) ฟิลด์ปิดงาน (close) ==========
  const clDate        = getVal('cl_date');
  const clTime        = getVal('cl_time');
  const clServiceType = getVal('cl_service_type');
  const clJobType     = getVal('cl_job_type');
  const clNote        = getVal('cl_note');

  fd.append('finish_date',  clDate);
  fd.append('finish_time',  clTime);
  fd.append('service_type', clServiceType);
  fd.append('job_type',     clJobType);

  // ส่ง cl_note ไปให้ PHP เสมอ ไม่ว่าจะว่างหรือไม่
  fd.append('cl_note', clNote);

  // ถ้า clNote มีข้อความ ก็ set ไปที่ note ด้วย
  if (clNote) {
    fd.set('note', clNote);
  }

  // ========== 5) รูป "แจ้งซ่อม" (header images) ==========
  if (typeof ED !== 'undefined') {
    fd.append('keep_urls', JSON.stringify(ED.keepUrls || []));
    (ED.newFiles || []).forEach(f => fd.append('new_images[]', f));
  }

  // ========== 6) รูป "ปิดงาน" (completed images) ==========
  if (typeof CL !== 'undefined') {
    fd.append('completed_keep_urls', JSON.stringify(CL.keepUrls || []));
    (CL.newFiles || []).forEach(f => fd.append('close_images[]', f));
  }

  // ========== 7) อะไหล่ (parts) + รวมเงิน ==========
 const parts = [];
	document.querySelectorAll('#parts_wrap > div').forEach(row => {
	  const qty   = parseFloat(row.dataset.qty || '0') || 0;
	  if (qty <= 0) return;
	
	  const price = parseFloat(row.dataset.price || '0') || 0;
	  const sum   = qty * price;
	
	  const brand = row.dataset.brand || '';
	  const model = row.dataset.model || '';
	
	  // ✅ รวมยี่ห้อ + รุ่น ให้กลายเป็นค่าเดียวเก็บใน rpd_brand
	  const brandFull = [brand, model].filter(x => x && x.trim() !== '').join(' / ');
	
	  parts.push({
		rpd_product_id: Number(row.dataset.productId || 0),
		wh_id:          Number(row.dataset.whId || 0),
	
		wh:               row.dataset.wh          || '',
		pd_gen_code:      row.dataset.genCode     || '',
		rpd_details_head: row.dataset.detailsHead || '',
		rpd_details:      row.dataset.details     || '',
	
		rpd_brand:        brandFull,   // 🟢 ใช้อันนี้ตัวเดียวก็พอแล้ว
	
		// ถ้าอยากส่งแยกไปด้วยก็ได้ ไม่ผิด (แต่หลังบ้านไม่ใช้ก็ลบได้)
		// rpd_model:        row.dataset.model || '',
	
		rpd_qty:       qty,
		rpd_price:     price,
		pd_unit:       row.dataset.unit || '',
		rpd_sum_money: sum
	  });
	});
	
	if (parts.length > 0) {
	  fd.append('parts_json', JSON.stringify(parts));
	}

  // ========== 8) ยิง API ตัวเดียว (action=update) + SweetAlert loading ==========
  // เปิด popup "กำลังบันทึก..."
  Swal.fire({
    title: 'กำลังบันทึกข้อมูล...',
    text: 'โปรดรอสักครู่',
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  try {
    const url = new URL('handle_repair_requests.php', window.location.href);
    url.searchParams.set('action', 'update');
    url.searchParams.set('ag_id', AG_ID);

    const r = await fetch(url, { method: 'POST', body: fd });

    const j = await r.json();

    if (!j?.success) {
      throw new Error(j?.message || j?.error || 'บันทึกไม่สำเร็จ');
    }

    // ปิด popup loading ก่อน
    Swal.close();

    // แจ้ง success
    Swal.fire({
      icon: 'success',
      title: 'บันทึกเรียบร้อย',
      timer: 1200,
      showConfirmButton: false
    });

    closeDrawer();
    refreshGrid();
    return;
  } catch (err) {
    console.error(err);
    // ปิด popup loading ถ้ายังเปิดอยู่
    Swal.close();
    Swal.fire({
      icon: 'error',
      title: 'บันทึกไม่สำเร็จ',
      text: (err?.message || 'กรุณาลองใหม่')
    });
    return;
  }
}

    /* =========================
       ✅ Boot
       ========================= */
	   async function fillSelect(selectId, apiUrl, placeholderText, toOption) {
  const sel = document.getElementById(selectId);
  if (!sel) return;

  // ถ้าเป็น Select2 อยู่ ให้ destroy ก่อนเติม option ใหม่
  if (window.jQuery && $('#' + selectId).data('select2')) {
    $('#' + selectId).select2('destroy');
  }

  sel.innerHTML = '';

  if (placeholderText) {
    const ph = document.createElement('option');
    ph.value = '';
    ph.textContent = placeholderText || '-- เลือก --';
    sel.appendChild(ph);
  }

  try {
    const r = await fetch(apiUrl, { method: 'GET' });
    const j = await r.json();
    if (!j || !j.success || !Array.isArray(j.data)) {
      throw new Error(j?.message || 'โหลดข้อมูลไม่สำเร็จ');
    }

    const seen = new Set();

    j.data.forEach(item => {
      const opt = toOption(item);
      if (!opt) return;

      const value = String(opt.value ?? '').trim();
      const text = String(opt.text ?? '').trim();

      if (!value || seen.has(value)) return;
      seen.add(value);

      const o = document.createElement('option');
      o.value = value;
      o.textContent = text || value;
      sel.appendChild(o);
    });
  } catch (err) {
    console.error(selectId, err);
  }
}

async function loadActionDropdowns() {
  // 1) ช่าง / ผู้รับผิดชอบ หลายคน
  await fillSelect(
    'ac_tech',
    'api_get_responsibles.php',
    '',
    (u) => ({
      value: String(u.id ?? u.user_id ?? '').trim(),
      text: String(u.label || u.user_fname || u.name || u.user_name || u.id || '').trim()
    })
  );

  document.querySelector('#ac_tech option[value=""]')?.remove();
  initResponsibleSelect2();

  // 2) ชนิดของการบริการ
  await fillSelect(
    'cl_service_type',
    'api_get_repair_group.php',
    '-- เลือก --',
    (x) => ({ value: x.id, text: x.name })
  );

  // 3) ประเภทงาน
  await fillSelect(
    'cl_job_type',
    'api_get_repair_system.php',
    '-- เลือก --',
    (x) => ({ value: x.id, text: x.name })
  );
}

    document.addEventListener('DOMContentLoaded', () => {
        const gridDiv = document.querySelector('#myGrid');
        gridApi = agGrid.createGrid(gridDiv, gridOptions);
        initCloseUploader();
        safeIcons();
        AssetSelector.updateRestoreUI?.();
        loadActionDropdowns();

        // ตัวแปรสำหรับเก็บ ID ที่ต้องการเปิด
        let targetId = null;

        // --- 1. เช็คจาก Dashboard Notification (Session Storage) ก่อน ---
        const pendingEditId = sessionStorage.getItem('_edit_id');
        if (pendingEditId) {
            targetId = pendingEditId;
            sessionStorage.removeItem('_edit_id'); // เคลียร์ค่าทิ้ง
        } 
        // --- 2. ถ้าไม่มีใน Session Storage ให้เช็คจาก URL ---
        else {
            try {
                // ดึง Hash (เผื่อโหลดผ่าน AJAX หรือเป็นหน้าหลัก)
                let hashString = window.location.hash;

                // ถ้าอยู่ใน Iframe ให้ไปดึง Hash จากหน้าต่างหลัก (Parent)
                if (!hashString && window !== window.parent) {
                    hashString = window.parent.location.hash;
                }

                // แกะค่า id ออกจาก Hash (เช่น #maintenance_info.php?id=100217)
                if (hashString && hashString.includes('?')) {
                    const hashParams = new URLSearchParams(hashString.split('?')[1]);
                    targetId = hashParams.get('id');
                } 
                // เผื่อกรณีเข้าผ่าน Query String ปกติ (?id=100217)
                else {
                    const urlParams = new URLSearchParams(window.location.search);
                    targetId = urlParams.get('id');
                }
            } catch (e) {
                console.warn("ไม่สามารถอ่าน URL parameters ได้:", e);
            }
        }

        // --- 3. ถ้าเจอ ID (ไม่ว่าจะมาจาก Session หรือ URL) สั่งเปิด Drawer ---
        if (targetId) {
            setTimeout(() => {
                if (typeof openDrawer === 'function') {
                    openDrawer('edit', { id: targetId });
                }
            }, 500); 
        }

        // --- 4. รับ filter จาก Dashboard (ช่วงวันที่ + สถานะ) แล้ว set ให้ grid ---
        applyDashboardFilters();
    });

    // ✅ อ่านค่า query string ทั้งจาก URL ปกติ และจาก Hash (เผื่อโหลดผ่าน Iframe)
    function getDashboardFilterParam(key) {
        try {
            let hashString = window.location.hash;
            if (!hashString && window !== window.parent) {
                hashString = window.parent.location.hash;
            }
            if (hashString && hashString.includes('?')) {
                const hashParams = new URLSearchParams(hashString.split('?')[1]);
                if (hashParams.has(key)) return hashParams.get(key);
            }
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(key);
        } catch (e) {
            console.warn("ไม่สามารถอ่าน URL parameters ได้:", e);
            return null;
        }
    }

    // ✅ ตั้งค่า filter ของ grid (ช่วงวันที่ที่คอลัมน์ "วันที่/เวลา" + สถานะ) ตามที่ส่งมาจาก Dashboard
    function applyDashboardFilters(retry) {
        const status   = getDashboardFilterParam('status');
        const dateFrom = getDashboardFilterParam('dateFrom');
        const dateTo   = getDashboardFilterParam('dateTo');

        if (!status && !dateFrom && !dateTo) return;

        if (!gridApi) {
            // grid อาจยังสร้างไม่เสร็จ ลองใหม่อีกครั้งใน 150ms (สูงสุด ~3 วิ)
            if ((retry || 0) < 20) setTimeout(() => applyDashboardFilters((retry || 0) + 1), 150);
            return;
        }

        const model = {};

        if (dateFrom || dateTo) {
            model.report_date = {
                filterType: 'date',
                type: 'inRange',
                dateFrom: dateFrom || '',
                dateTo: dateTo || ''
            };
        }

        if (status) {
            model.status = {
                filterType: 'set',
                values: [status]
            };
        }

        gridApi.setFilterModel(model);
    }

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

  // ✅ เก็บของเดิมไว้ย้อนกลับ
  _backupItem: null,

  restoreBackup(){
    if(this._backupItem){
      this.renderSelectedCard(this._backupItem);
      this._backupItem = null;
      this.updateRestoreUI();
    }
  },

  updateRestoreUI(){
    const btn = document.getElementById('asset_restore_btn');
    if(!btn) return;
    btn.classList.toggle('hidden', !this._backupItem);
  },
  
 getBuilding() {
	  const v = (document.getElementById('ed_building')?.value || '').trim();
	  const id = parseInt(v, 10);
	  return (Number.isFinite(id) && id > 0) ? String(id) : '';
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
	applyThumb(url){
  const clean = (v)=> (v ?? '').toString().trim();
  const isBad = (u)=> !u || u === '0' || ['null','undefined'].includes(u.toLowerCase());

  const showIcon = ()=>{
    const img  = document.getElementById('sel_asset_img');
    const icon = document.getElementById('sel_asset_icon');
    if(!img || !icon) return;

    img.classList.add('hidden');
    img.removeAttribute('src');

    icon.classList.remove('hidden');

    // ถ้ายังเป็น <i> (ยังไม่ถูก lucide แปลง) ให้แปลงทันที
    if(icon.tagName.toLowerCase() === 'i'){
      window.lucide?.createIcons?.();
    }
  };

  const showImg = ()=>{
    const img  = document.getElementById('sel_asset_img');
    const icon = document.getElementById('sel_asset_icon');
    if(!img || !icon) return;

    icon.classList.add('hidden');
    img.classList.remove('hidden');
  };

  const u = clean(url);

  // ✅ เริ่มด้วยไอคอนเสมอ (กันโหลดช้า/รูปพัง)
  showIcon();
  if(isBad(u)) return;

  const img = document.getElementById('sel_asset_img');
  if(!img) return;

  img.onload = ()=>{
    showImg();
    img.onload = null;
    img.onerror = null;
  };

  img.onerror = ()=>{
    showIcon();
    img.onload = null;
    img.onerror = null;
  };

  img.src = u;
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
	  ? `<img src="${item.fileUpload1_url}" class="w-8 h-8 rounded object-cover border border-slate-200"
			  onerror="this.outerHTML='<div class=&quot;w-8 h-8 rounded bg-slate-100 flex items-center justify-center text-slate-400&quot;><i data-lucide=&quot;cpu&quot; class=&quot;w-3.5 h-3.5&quot;></i></div>'; window.lucide?.createIcons?.();">`
	  : `<div class="w-8 h-8 rounded bg-slate-100 flex items-center justify-center text-slate-400">
		   <i data-lucide="cpu" class="w-3.5 h-3.5"></i>
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
  document.getElementById('ed_asset') && (document.getElementById('ed_asset').value = item.ass_id || '');
  document.getElementById('ed_asset_id') && (document.getElementById('ed_asset_id').value = item.ass_id || '');
  document.getElementById('ed_asset_name_submit') && (document.getElementById('ed_asset_name_submit').value = item.asset_name || '');

  document.getElementById('sel_asset_name').innerText  = item.asset_name || '-';
  document.getElementById('sel_asset_code').innerText  = item.ass_code || 'NO CODE';
  document.getElementById('sel_asset_sn').innerText    = 'SN: ' + (item.asset_sn || '-');
  document.getElementById('sel_asset_model').innerText = 'Model: ' + (item.asset_model || '-');

  // ✅ สำคัญ: ใช้ตัวนี้จบ
  this.applyThumb(item.fileUpload1_url || '');

  document.getElementById('asset_search_wrapper')?.classList.add('hidden');
  document.getElementById('asset_selected_card')?.classList.remove('hidden');

      this._lastSelected = item;

    document.getElementById('asset_search_wrapper')?.classList.add('hidden');
    document.getElementById('asset_selected_card')?.classList.remove('hidden');

    window.lucide?.createIcons?.();
},
  clear() {
    // ✅ ถ้ามีการ์ดที่เลือกอยู่ ให้ backup ไว้ก่อน
    const currentId = (document.getElementById('ed_asset_id')?.value || '').trim();
    if(currentId){
      // พยายาม backup จาก lastSelected ก่อน (แม่นสุด)
      if(this._lastSelected && String(this._lastSelected.ass_id) === String(currentId)){
        this._backupItem = this._lastSelected;
      }else{
        // fallback: backup จากข้อมูลที่โชว์อยู่ (แบบหยาบ)
        this._backupItem = {
          ass_id: currentId,
          asset_name: document.getElementById('sel_asset_name')?.innerText || '',
          ass_code: document.getElementById('sel_asset_code')?.innerText || '',
          asset_sn: (document.getElementById('sel_asset_sn')?.innerText || '').replace(/^SN:\s*/,''),
          asset_model: (document.getElementById('sel_asset_model')?.innerText || '').replace(/^Model:\s*/,''),
          fileUpload1_url: document.getElementById('sel_asset_img')?.getAttribute('src') || ''
        };
      }
    }

    // ✅ เคลียร์ค่าเพื่อให้เลือกใหม่
    document.getElementById('ed_asset') && (document.getElementById('ed_asset').value = '');
    document.getElementById('ed_asset_id') && (document.getElementById('ed_asset_id').value = '');
    document.getElementById('ed_asset_name_submit') && (document.getElementById('ed_asset_name_submit').value = '');
    document.getElementById('asset_search_input') && (document.getElementById('asset_search_input').value = '');

    document.getElementById('asset_selected_card')?.classList.add('hidden');
    document.getElementById('asset_search_wrapper')?.classList.remove('hidden');
    this.applyThumb('');

    // ✅ โชว์ปุ่มย้อนกลับ
    this.updateRestoreUI();

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
  class="fixed top-0 right-0 h-[100dvh]
         w-full md:w-[95vw] lg:w-[90vw] xl:w-[85vw] 2xl:w-[80vw]
         max-w-[1600px] z-[9999]
         translate-x-full transition-transform duration-300 ease-out
         bg-[#F8FAFC] border-l border-slate-200 shadow-2xl
         flex flex-col font-['Kanit']">

  <!-- HEADER (เอาปุ่ม Save ออกแล้ว) -->
  <div class="px-6 py-3 bg-white border-b border-slate-200 flex items-center justify-between shadow-sm">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-sky-600 to-sky-800 text-white flex items-center justify-center shadow-md">
        <i data-lucide="monitor-cog" class="w-5 h-5"></i>
      </div>
      <div>
       <h2 id="ud_title" class="text-sm font-bold text-slate-800 leading-tight">จัดการงานซ่อมบำรุง (Maintenance Console)</h2>
        <div class="flex items-center gap-2 mt-0.5">
          <span class="text-[11px] font-medium text-slate-500" id="ud_subtitle">ID: Waiting...</span>
          <span class="w-1 h-1 rounded-full bg-slate-300"></span>
          <span class="text-[10px] font-bold text-sky-600 bg-sky-50 px-2 py-0.5 rounded-full border border-sky-100">
            Status: Active
          </span>
        </div>
      </div>
    </div>

    <button type="button" onClick="closeDrawer()"
      class="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400
             hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition">
      <i data-lucide="x" class="w-5 h-5"></i>
    </button>
  </div>

  <!-- BODY (สกอลล์เดียวทั้ง Drawer) -->
  <div id="ud_body_scroll" class="flex-1 min-h-0 overflow-y-auto bg-[#F8FAFC]">
  <div class="grid grid-cols-1 lg:grid-cols-12 min-h-full lg:gap-0">

    <!-- LEFT: REQUEST DETAILS (มี padding เฉพาะฝั่งซ้าย) -->
    <div class="lg:col-span-6 px-6 py-6">
      <div class="max-w-4xl mx-auto space-y-5">

        <div class="flex items-center gap-2 pb-2 border-b border-slate-200/60">
          <i data-lucide="file-text" class="w-4 h-4 text-slate-400"></i>
          <span class="text-xs font-black text-slate-500 uppercase tracking-wider">
            รายละเอียดใบงาน (Request Details)
          </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-1">
          <!-- Requester -->
          <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col justify-between">
            <div >
              <label class="text-[10px] font-bold text-slate-400 uppercase">ผู้แจ้ง (Requester)</label>
              <div class="flex items-center gap-3 mt-2">
                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500">
                  <i data-lucide="user" class="w-5 h-5"></i>
                </div>
                <div class="flex-1">
                  <input type="text" id="ed_name"
                    class="w-full text-sm font-bold text-slate-700 bg-transparent border-none p-0 focus:ring-0 placeholder-slate-300"
                    placeholder="ระบุชื่อผู้แจ้ง">
                  <input type="text" id="ed_phone"
                    class="w-full text-xs text-slate-400 bg-transparent border-none p-0 focus:ring-0 placeholder-slate-300"
                    placeholder="เบอร์โทรศัพท์">
                </div>
              </div>
            </div>

            <div class="mt-3 pt-3 border-t border-slate-50 flex gap-5">
              <div class="flex-1">
                <label class="text-[10px] font-bold text-slate-400">วันที่แจ้ง</label>
                <input id="ed_report_date" type="date"
                  class="block w-full text-xs font-semibold text-slate-600 bg-transparent border-none p-0 focus:ring-0">
              </div>
              <div class="flex-1">
                <label class="text-[10px] font-bold text-slate-400">เวลา</label>
                <input id="ed_report_time" type="time"
                  class="block w-full text-xs font-semibold text-slate-600 bg-transparent border-none p-0 focus:ring-0">
              </div>
            </div>
          </div>

          <!-- Urgency + Problem -->
          <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex flex-col">
            <div class="flex items-center justify-between mb-2">
              <label class="text-[10px] font-bold text-slate-400 uppercase">ความเร่งด่วน & อาการ</label>
              <select id="ed_urgency"
                class="text-[10px] font-bold bg-slate-50 border border-slate-200 rounded-md px-2 py-1 text-slate-600 focus:border-sky-500 outline-none">
                <option value="low">🟢 ต่ำ</option>
                <option value="medium">🟡 ปานกลาง</option>
                <option value="high">🟠 สูง</option>
                <option value="critical">🔴 เร่งด่วนมาก</option>
              </select>
            </div>
            <textarea id="ed_problem" rows="6"
  class="w-full resize-none text-xs font-bold text-slate-1000 bg-slate-50 rounded-lg border-0 p-3 focus:ring-1 focus:ring-sky-200 placeholder-slate-300"
  placeholder="รายละเอียดอาการเสีย..."></textarea>
          </div>
        </div>

        <!-- Location + Asset -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-visible">
          <div class="bg-slate-50/50 px-4 py-2 border-b border-slate-100 flex items-center justify-between">
            <span class="text-xs font-bold text-slate-700">📍 สถานที่และทรัพย์สิน</span>
            <button type="button"
              class="text-[10px] text-sky-600 font-bold hover:underline"
              onclick="LocationSelector.openModal('edit','building')">
              แก้ไขตำแหน่ง
            </button>
          </div>

          <div class="p-4 space-y-4">
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
                    <div >
  
               <div class="space-y-1">

  <!-- Title -->
  <div class="flex items-end justify-between">
    <div>
      <label class="flex items-center gap-2 text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
		  <span>ทรัพย์สิน/เครื่องจักร (Asset)</span>
		  <span class="text-[11px] text-slate-400 font-semibold normal-case tracking-normal">
			ค้นหาจากชื่อ, รหัสครุภัณฑ์, Serial Number หรือ Model
		  </span>
		</label>
    </div>
 
  </div>

  <!-- Hidden inputs -->
  <input type="hidden" id="ed_asset" name="ed_asset" value="">
  <input type="hidden" id="ed_asset_id" name="ed_asset_id" value="">
  <input type="hidden" id="ed_asset_name_submit" name="asset_name" value="">

  <!-- Search Box -->
  <div id="asset_search_wrapper" class="relative">

    <!-- Input -->
    <div class="relative">
      <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
      </div>

      <input
        type="text"
        id="asset_search_input"
        autocomplete="off"
        onkeyup="AssetSelector.search(this.value)"
        placeholder="พิมพ์เพื่อค้นหาอุปกรณ์ (ชื่อ / รหัส / S/N / Model)..."
        class="w-full pl-10 pr-24 py-3 rounded-2xl border border-slate-200 bg-slate-50
               text-sm font-semibold text-slate-700 placeholder:text-slate-400 placeholder:font-medium
               focus:bg-white focus:border-sky-400 focus:ring-4 focus:ring-sky-50 outline-none transition-all
               shadow-sm"
      />

      <!-- Loading -->
      <div id="asset_loading" class="hidden absolute inset-y-0 right-3 flex items-center">
        <svg class="animate-spin h-4 w-4 text-sky-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
      </div>

      <!-- Right Actions (UX Pro) -->
      <div class="absolute inset-y-0 right-2 flex items-center gap-1">
        <button
          type="button"
          onclick="document.getElementById('asset_search_input').value=''; AssetSelector.search('');"
          class="h-9 px-2.5 rounded-xl border border-slate-200 bg-white text-slate-500
                 hover:bg-slate-50 hover:text-slate-700 transition text-[11px] font-black"
          title="ล้างคำค้นหา">
          ล้าง
        </button>

        <button
          id="asset_restore_btn"
          type="button"
          onclick="AssetSelector.restoreBackup()"
          class="hidden h-9 px-2.5 rounded-xl border border-sky-200 bg-sky-50 text-sky-700
                 hover:bg-sky-100 transition text-[11px] font-black"
          title="กลับไปทรัพย์สินเดิม">
          ↩ เดิม
        </button>
      </div>
    </div>

    <!-- Hint bar (show only when restore available) -->
    <div id="asset_restore_hint"
      class="hidden mt-2 flex items-center gap-2 rounded-2xl border border-sky-100 bg-sky-50 px-3 py-2">
      <i data-lucide="info" class="w-4 h-4 text-sky-600"></i>
      <div class="text-[11px] font-semibold text-slate-600">
        มีทรัพย์สินเดิมให้ย้อนกลับได้ กดปุ่ม <span class="font-black text-sky-700">↩ เดิม</span> ด้านขวา
      </div>
    </div>

    <!-- Dropdown -->
    <div id="asset_dropdown"
      class="hidden absolute top-full left-0 right-0 mt-2 bg-white rounded-2xl border border-slate-100
             shadow-xl max-h-72 overflow-y-auto divide-y divide-slate-50 z-50">
    </div>
  </div>

  <!-- Selected Card -->
  <div id="asset_selected_card"
    class="hidden relative overflow-hidden rounded-2xl border border-slate-200 bg-white
           shadow-[0_10px_28px_-14px_rgba(2,132,199,0.35)] transition hover:border-sky-300">

    <div class="p-4 flex items-start gap-3">
      <!-- Thumb -->
      <div class="w-14 h-14 rounded-2xl bg-slate-50 border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center relative">
        <img id="sel_asset_img" src="" class="w-full h-full object-cover hidden">
        <i id="sel_asset_icon" data-lucide="cpu" class="w-6 h-6 text-slate-400"></i>
      </div>

      <!-- Info -->
      <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-center gap-2 mb-1.5">
          <span id="sel_asset_code"
            class="text-[10px] font-black bg-sky-50 text-sky-700 border border-sky-100 px-2 py-1 rounded-lg uppercase tracking-wider">
            CODE
          </span>

          <span id="sel_asset_sn" class="text-[10px] text-slate-400 font-mono truncate">
            SN: -
          </span>

          <span id="sel_asset_model" class="text-[10px] text-slate-400 font-mono truncate">
            Model: -
          </span>
        </div>

        <h4 id="sel_asset_name" class="text-[15px] font-black text-slate-900 leading-snug truncate">
          Asset Name
        </h4>

       <!-- <div class="mt-2 flex items-center gap-2 text-[11px] font-semibold text-slate-500">
          <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600"></i>
          เลือกทรัพย์สินแล้ว พร้อมบันทึก
        </div>-->
      </div>

      <!-- Actions -->
      <div class="flex flex-col items-end gap-2">
        <button type="button"
          onClick="AssetSelector.clear()"
          class="h-10 w-10 rounded-2xl border border-slate-200 bg-white text-slate-500
                 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition flex items-center justify-center"
          title="เปลี่ยนอุปกรณ์">
          <i data-lucide="arrow-left-right" class="w-4 h-4"></i>
        </button>

        <!--<span class="text-[10px] font-black text-emerald-700 bg-emerald-50 border border-emerald-100 px-2 py-1 rounded-full">
          Selected
        </span>-->
      </div>
    </div>
 
  </div>

</div>

            </div>
 
        
          </div>
        </div>

        <!-- Images -->
        <div class="up-card">
          <div class="up-head">
            <div class="up-title">
              <i data-lucide="images" class="w-4 h-4 text-sky-700"></i>
              รูปภาพแจ้งซ่อม (รองรับ: JPG / PNG · แนบได้สูงสุด 5 รูป)
            </div>
            <div class="up-count"><span id="ed_img_count">0/5</span></div>
          </div>

          <div id="ed_dropzone" class="up-zone">
            <div class="up-left">
              <div class="cam"><i data-lucide="camera" class="w-4 h-4"></i></div>
              <div class="txt">ADD PHOTO<br><span class="font-semibold">ลากมาวางได้</span></div>
            </div>
            <div class="up-mid" id="ed_mid">
              <div id="ed_hint" class="up-hint">ลากรูปมาวางที่นี่ หรือคลิก ADD PHOTO</div>
              <div id="ed_preview" class="up-thumbs"></div>
              <p id="ed_img_err" class="hidden up-err">รวมรูปได้สูงสุด 5 รูปเท่านั้น</p>
            </div>
          </div>

          <input id="ed_images" type="file" class="hidden" multiple accept="image/*">

            <p id="ed_img_err" class="hidden mt-2 text-[10px] font-bold text-rose-600">รวมรูปได้สูงสุด 5 รูปเท่านั้น</p>
			<div class="up-help"></div>
          </div>

          
        </div>

      </div> 

    <!-- RIGHT: ACTIONS (แนบติด + เต็มแผง + ติดขอบแบบรูป) -->
    <div class="lg:col-span-6 bg-white border-l border-slate-200">
      <!-- ทำหัว sticky ได้ (เหมือน enterprise) -->
      <div class="px-5 py-3 border-b border-slate-100 bg-slate-50 lg:sticky lg:top-0 z-10">
        <h3 class="text-xs font-black text-slate-700 uppercase tracking-wide flex items-center gap-2">
          <i data-lucide="bolt" class="w-3.5 h-3.5 text-yellow-500"></i> การดำเนินการ (Actions)
        </h3>
      </div>

      <div class="p-5 space-y-6">
<!-- Helper: อธิบายขั้นตอนการทำงาน -->
<div id="actions-helper"
     class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50/70 px-6 py-4 flex gap-3 items-start">
  <div class="mt-1 flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100">
    <i data-lucide="workflow" class="w-4 h-4 text-emerald-600"></i>
  </div>
  <div class="space-y-1">
    <p class="text-sm font-semibold text-slate-800">
      ขั้นตอนการดำเนินการใบงาน
    </p>
    <ul class="text-xs text-slate-600 space-y-1 list-disc list-inside">
      <li><span class="font-semibold text-emerald-700">สเตปที่ 1:</span> กรอกวันที่ เวลา และผู้รับผิดชอบฝั่งรับงานให้ครบ</li>
      <li><span class="font-semibold text-emerald-700">สเตปที่ 2:</span> ระบบจะเปิดส่วน <span class="font-semibold">“ปิดงาน / รายงานผล”</span> ให้โดยอัตโนมัติ</li>
      <li>ตรวจสอบข้อมูลให้เรียบร้อย จากนั้นกด <span class="font-semibold">“บันทึกข้อมูล (Save)”</span> ด้านล่างขวา</li>
    </ul>
  </div>
</div>
              <!-- STEP 1: ACCEPT -->
              <div id="step-accept" class="relative pl-4 border-l-2 border-slate-200  border-sky-400 transition-colors">
                <span class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-sky-100 border-2 border-white shadow-sm
                             flex items-center justify-center text-[10px] font-bold text-sky-600
                             bg-sky-500  text-white transition-colors">1</span>

                <h4 class="text-xs font-bold text-slate-800 mb-3 flex items-center gap-2">
                  รับงาน / จ่ายงาน
                  <span class="text-[9px] font-normal text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">Assign</span>
                </h4>

                <div class="space-y-3">
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="block text-[10px] font-bold text-slate-400 mb-0.5">วันที่เข้าทำ</label>
                      <input id="ac_date" type="date"
                        class="w-full text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2
                               focus:border-sky-500 focus:ring-2 focus:ring-sky-100 outline-none">
                    </div>
                    <div>
                      <label class="block text-[10px] font-bold text-slate-400 mb-0.5">เวลาเข้าทำ</label>
                      <input id="ac_time" type="time"
                        class="w-full text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2
                               focus:border-sky-500 focus:ring-2 focus:ring-sky-100 outline-none">
                    </div>
                  </div>

                  <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-0.5">เลือกช่าง / ผู้รับผิดชอบ</label>
                    <select id="ac_tech" name="responsible_user_ids[]" multiple
					  class="w-full text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2
							 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 outline-none">
					</select>
                  </div>
 

                  
                </div>
              </div>


              <!-- STEP 2: CLOSE -->
             <div id="step-close"
     class="relative pl-4 border-l-2 border-slate-200  border-emerald-400 transition-colors">
  <span
    class="absolute -left-[9px] top-0 w-4 h-4 rounded-full bg-emerald-100 border-2 border-white shadow-sm
           flex items-center justify-center text-[10px] font-bold 
            bg-emerald-500 text-white transition-colors">
    2
  </span>

                <h4 class="text-xs font-bold text-slate-800 mb-3 flex items-center gap-2">
                  ปิดงาน / รายงานผล
                  <span class="text-[9px] font-normal text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">Close</span>
                </h4>

                <div class="space-y-3">
                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="block text-[10px] font-bold text-slate-400 mb-0.5">วันที่เสร็จ</label>
                      <input id="cl_date" type="date"
                        class="w-full text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2
                               focus:border-rose-500 focus:ring-2 focus:ring-rose-100 outline-none">
                    </div>
                    <div>
                      <label class="block text-[10px] font-bold text-slate-400 mb-0.5">เวลาเสร็จ</label>
                      <input id="cl_time" type="time"
                        class="w-full text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2
                               focus:border-rose-500 focus:ring-2 focus:ring-rose-100 outline-none">
                    </div>
                  </div>

                  <div class="grid grid-cols-2 gap-2">
                    <div>
                      <label class="block text-[10px] font-bold text-slate-400 mb-0.5">ชนิดของการบริการ</label>
                      <select id="cl_service_type"
                        class="w-full text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2
                               focus:border-rose-500 focus:ring-2 focus:ring-rose-100 outline-none">
                        <option value="">-- เลือก --</option> 
                      </select>
                    </div>
                    <div>
                      <label class="block text-[10px] font-bold text-slate-400 mb-0.5">ประเภทงาน</label>
                      <select id="cl_job_type"
                        class="w-full text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2
                               focus:border-rose-500 focus:ring-2 focus:ring-rose-100 outline-none">
                        <option value="">-- เลือก --</option> 
                      </select>
                    </div>
                  </div>

                  <div>
                    <label class="block text-[10px] font-bold text-slate-400 mb-0.5">บันทึกผลการดำเนินงาน</label>
                    <textarea id="cl_note" rows="4"
                      class="w-full resize-none text-xs text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2
                             focus:border-rose-500 focus:ring-2 focus:ring-rose-100 outline-none placeholder-slate-300"
                      placeholder="เช่น เปลี่ยนอะไหล่..., แก้ไขแล้วทดสอบใช้งานได้ปกติ, ข้อเสนอแนะ..."></textarea>
                  </div> 
				  

                  <div class="up-card">
                    <div class="up-head">
                      <div class="up-title">
                        <i data-lucide="images" class="w-4 h-4 text-sky-700"></i>
                        รูปปิดงาน (รองรับ: JPG / PNG · แนบได้สูงสุด 5 รูป)
                      </div>
                      <div class="up-count"><span id="cl_img_count">0/5</span></div>
                    </div>

                    <div id="cl_dropzone" class="up-zone">
                      <div class="up-left">
                        <div class="cam"><i data-lucide="camera" class="w-4 h-4"></i></div>
                        <div class="txt">ADD PHOTO<br><span class="font-semibold">ลากมาวางได้</span></div>
                      </div>
                      <div class="up-mid" id="cl_mid">
                        <div id="cl_hint" class="up-hint">ลากรูปมาวางที่นี่ หรือคลิก ADD PHOTO</div>
                        <div id="cl_preview" class="up-thumbs"></div>

                        <p  class="hidden up-err">เลือกได้สูงสุด 5 รูปเท่านั้น</p>
                      </div>
                    </div>

                    <input id="cl_images" type="file" class="hidden" multiple accept="image/*">

                      <p id="cl_img_err" class="hidden mt-2 text-[10px] font-bold text-rose-600">เลือกได้สูงสุด 5 รูปเท่านั้น</p>
                    </div>

                    <div class="up-help"></div>
                  </div>
                  <div class="bg-white rounded-xl border border-slate-200 p-3">
                    <div class="flex items-center justify-between">
                      <div class="text-[10px] font-black text-slate-500 flex items-center gap-2">
                        <i data-lucide="package-plus" class="w-4 h-4"></i> อะไหล่/วัสดุสิ้นเปลือง
                      </div>
                      <button type="button" onClick="openPartsModal()"
                        class="text-[10px] font-black text-sky-700 hover:underline flex items-center gap-1">
                        <i data-lucide="plus-circle" class="w-4 h-4"></i> เพิ่มรายการ
                      </button>
                    </div>
                    <div id="parts_wrap" class="mt-3 space-y-2"></div> 
					<div class="mt-3 flex items-center justify-end text-[11px] font-extrabold text-slate-700">
					  รวมค่าวัสดุ/อะไหล่:
					  <span id="parts_total" class="ml-2 text-emerald-700 text-sm">0.00</span>
					  <span class="ml-1 text-[10px] text-slate-400">บาท</span>
					</div> 
					<p class="mt-2 text-[10px] text-slate-400">
					  * เพิ่มได้หลายรายการ (เลือกจากสต๊อกและกำหนดจำนวนที่ใช้)
					</p>
                  </div>

                </div>
              </div>

              <!-- STEP 3: LOG -->
               

            </div><!-- /actions content -->
          </div>
        </div>

      </div>
    </div>
  </div>

  
<!-- =========================
     ✅ Parts / Consumables Picker Modal
     ========================= -->
<div id="parts_modal" class="fixed inset-0 z-[21000] hidden">
  <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[1px]" data-parts-close></div>

  <div class="relative mx-auto mt-6 w-[96%] max-w-6xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
    <!-- header -->
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
      <div>
        <div class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
          <i data-lucide="package-search" class="w-4 h-4 text-sky-700"></i>
          อะไหล่/วัสดุสิ้นเปลือง
        </div>
        <div class="text-[11px] text-slate-500 mt-1">เลือกคลัง แล้วค้นหาเพื่อเพิ่มรายการ</div>
      </div>
      <button type="button" class="h-9 w-9 rounded-xl border border-slate-200 hover:bg-slate-50 flex items-center justify-center" data-parts-close>
        <i data-lucide="x" class="w-4 h-4 text-slate-500"></i>
      </button>
    </div>

    <!-- controls -->
    <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
      <div class="grid grid-cols-12 gap-3 items-center">
        <div class="col-span-12 md:col-span-3">
          <label class="block text-[10px] font-black text-slate-500 mb-1">คลังสินค้า</label>
          <select id="parts_wh" class="w-full h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700">
            <option value="">-- เลือกคลัง --</option>
            <option value="plumbing">คลังประปา</option>
            <option value="electric">คลังไฟฟ้า</option>
            <option value="general">คลังทั่วไป</option>
          </select>
        </div>

        <div class="col-span-12 md:col-span-9">
          <label class="block text-[10px] font-black text-slate-500 mb-1">ค้นหา</label>
          <div class="relative">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input id="parts_q" type="text" placeholder="ค้นหารหัส/ชื่อสินค้า..."
              class="w-full h-10 rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-sky-200 outline-none">
          </div>
        </div>
      </div>
    </div>

    <!-- list -->
    <div class="max-h-[55vh] overflow-auto">
      <div class="grid grid-cols-12 px-5 py-2 text-[11px] font-extrabold text-slate-500 border-b border-slate-100 bg-white sticky top-0 z-10">
        <div class="col-span-4">รายการสินค้า</div>
        <div class="col-span-2">คลังสินค้า</div>
        <div class="col-span-3">รายละเอียด</div>
        <div class="col-span-1 text-right">ราคา</div>
        <div class="col-span-2 text-right">สต็อก</div>
      </div>

      <div id="parts_list" class="divide-y divide-slate-100"></div>

      <div id="parts_empty" class="hidden px-5 py-12 text-center text-[12px] text-slate-400 font-semibold">
        ไม่พบรายการ
      </div>
    </div>

    <!-- footer -->
    <div class="px-5 py-4 border-t border-slate-100 bg-white flex items-center justify-between">
      <div class="text-[11px] font-bold text-slate-500">
        เลือกแล้ว: <span id="parts_selected_count" class="text-sky-700 font-extrabold">0</span> รายการ
      </div>
      <div class="flex items-center gap-2">
        <button type="button" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50" data-parts-close>
          ยกเลิก
        </button>
        <button type="button" id="parts_confirm"
          class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold disabled:opacity-40 disabled:cursor-not-allowed">
          เพิ่มรายการ
        </button>
      </div>
    </div>
  </div>
</div>

<script>
/* =========================
   ✅ Parts Picker (frontend)
   - สามารถเปลี่ยนโหลดข้อมูลจาก API ได้ภายหลัง
   ========================= */
(function(){
  const modal   = document.getElementById('parts_modal');
  const listEl  = document.getElementById('parts_list');
  const emptyEl = document.getElementById('parts_empty');
  const whEl    = document.getElementById('parts_wh');
  const qEl     = document.getElementById('parts_q');
  const cntEl   = document.getElementById('parts_selected_count');
  const okBtn   = document.getElementById('parts_confirm');

  if(!modal || !listEl || !emptyEl || !whEl || !qEl || !cntEl || !okBtn) return;

  // ต้องมี AG_ID จากหน้า (เช่น const AG_ID = <?= (int)$ag_id ?>;)
  if(typeof AG_ID === 'undefined'){
    console.error('AG_ID is not defined');
    return;
  }

  const state = {
    items: [],
    selected: new Map(), // id -> item
  };

  function openPartsModal(){
	  const jobType = (document.getElementById('cl_job_type')?.value || '').trim();
	
	  if (!jobType) {
		Swal.fire({
		  icon: 'warning',
		  title: 'กรุณาเลือกประเภทงานก่อน',
		  text: 'ต้องเลือก "ประเภทงาน" ก่อนเพิ่มอะไหล่/วัสดุสิ้นเปลือง'
		});
		return;
	  }
	
	  modal.classList.remove('hidden');
	  state.selected.clear();
	  cntEl.textContent = '0';
	  okBtn.disabled = true;
	
	  loadWhOptions().then(loadItems);
	
	  window.lucide?.createIcons?.();
	  setTimeout(()=> qEl.focus(), 50);
	}
  window.openPartsModal = openPartsModal;

  function closePartsModal(){
    modal.classList.add('hidden');
  }

  modal.querySelectorAll('[data-parts-close]').forEach(el=>{
    el.addEventListener('click', closePartsModal);
  });

  // เรียกครั้งแรก (ให้ dropdown มีค่า)
  loadWhOptions();

  async function loadWhOptions(){
    // reset option
    whEl.innerHTML = `<option value="">-- เลือกคลัง --</option>`;

    try{
      const params = new URLSearchParams({
        action: 'get_wh_list',
        ag_id: String(AG_ID)
      });

      const res = await fetch(`handle_stock_maintenance.php?${params.toString()}`, {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
      });

      const j = await res.json();
      if(!res.ok || !j.success){
        console.error('loadWhOptions error:', j);
        return;
      }

      (j.data || []).forEach(row => {
        const opt = document.createElement('option');
        opt.value = String(row.id);         // ✅ wh_id (tb_wh_stock.id)
        opt.textContent = row.wh_name || ''; // ✅ ชื่อคลัง
        whEl.appendChild(opt);
      });

    }catch(err){
      console.error(err);
    }
  }

function normalizeItem(r){
  return {
    ps_id: Number(r.ps_id || 0),
    pd_id: Number(r.pd_id || 0),
    wh_id: Number(r.wh_id || 0),

    name: String(r.pd_details_head || ''),
    code: String(r.pd_gen_code || ''),
    detail: String(r.pd_details || ''),
    wh: String(r.wh_name || ''),
    wh_name: String(r.wh_name || ''),
    price: Number(r.pd_price || 0),

    stock: Number(r.pd_qty || 0),
    stockMin: Number(r.pd_qty_min || 0),
    unit: String(r.pd_unit || ''),

    brand: String(r.pd_brand || ''),

    model: String(r.pd_model || '')
  };
}

  async function loadItems(){
	  const wh_id    = (whEl.value || '').trim();
	  const q        = (qEl.value || '').trim();
	  const job_type = (document.getElementById('cl_job_type')?.value || '').trim();
	
	  if (!job_type) {
		state.items = [];
		render();
		return;
	  }
	
	  try{
		const params = new URLSearchParams({
		  action: 'get_all',
		  ag_id: String(AG_ID),
		  job_type: job_type
		});
	
		if (wh_id) params.set('wh_id', wh_id);
		if (q)     params.set('q', q);
	
		const res = await fetch(`handle_stock_maintenance.php?${params.toString()}`, {
		  method: 'GET',
		  headers: { 'Accept': 'application/json' }
		});
	
		const j = await res.json();
		const raw = (j && j.success && Array.isArray(j.data)) ? j.data : [];
		state.items = raw.map(normalizeItem).filter(x => x.ps_id > 0);
		render();
	
	  }catch(err){
		console.error(err);
		state.items = [];
		render();
	  }
	}

function stockColor(it){
  const stock = Number(it.stock || 0);
  const min   = Number(it.stockMin || 0);

  if (stock <= 0) return 'bg-rose-500';
  if (min > 0 && stock <= min) return 'bg-amber-500';
  return 'bg-emerald-500';
}

  function render(){
    listEl.innerHTML = '';
    emptyEl.classList.toggle('hidden', state.items.length > 0);

    state.items.forEach(it=>{
      const checked = state.selected.has(it.ps_id);
      const max = Number(it.stockMax || 0);
      const stock = Number(it.stock || 0);
      const pct = (max > 0) ? Math.round((stock / max) * 100) : 0;

      const row = document.createElement('div');
      row.className = "grid grid-cols-12 px-5 py-3 cursor-pointer items-center " + (checked ? "bg-sky-50 ring-1 ring-sky-200" : "hover:bg-slate-50");
      row.innerHTML = `
        <div class="col-span-4">
          <div class="flex items-start gap-3">
            <input type="checkbox"
              class="ag-input-field-input ag-checkbox-input mt-1"
              ${checked ? 'checked' : ''} />
            <div>
              <div class="text-[12px] font-extrabold text-slate-800 leading-snug">${escapeHtml(it.name || '')}</div>
              <div class="mt-1 flex items-center gap-2">
                <span class="text-[10px] font-black text-slate-600 bg-slate-100 px-2 py-0.5 rounded">${escapeHtml(it.code || '')}</span>
                ${checked ? `<span class="text-[10px] font-black text-sky-700 bg-sky-50 border border-sky-200 px-2 py-0.5 rounded">เลือกแล้ว</span>` : ``}
                ${it.tier ? `<span class="text-[10px] font-black text-purple-700 bg-purple-50 px-2 py-0.5 rounded">${escapeHtml(it.tier)}</span>` : ``}
              </div>
            </div>
          </div>
        </div>

        <div class="col-span-2 text-[11px] font-bold text-slate-700">${escapeHtml(it.wh || '')}</div>

        <div class="col-span-3 text-[11px] font-semibold text-slate-600">${escapeHtml(it.detail || '')}  ${it.model ? `<div class="text-[10px] text-slate-500 mt-0.5">รุ่น: ${escapeHtml(it.model)}</div>` : ``}</div>

        <div class="col-span-1 text-right text-[12px] font-extrabold text-slate-700">${Number(it.price || 0).toLocaleString()}</div>

        <div class="col-span-2 text-right">
          <div class="text-[11px] font-extrabold ${max > 0 && stock <= (max*0.2) ? 'text-rose-600':'text-slate-700'}">
            ${stock} / ${max} <span class="text-[10px] font-bold text-slate-400">${escapeHtml(it.unit || '')}</span>
          </div>
          <div class="mt-1 h-1.5 w-full bg-slate-200 rounded-full overflow-hidden">
            <div class="h-full ${stockColor(it)}" style="width:${Math.min(100, Math.max(0, pct))}%"></div>
          </div>
        </div>
      `;

    row.addEventListener('click', ()=>{
		  const key = it.ps_id; // ✅ unique ต่อแถวสต๊อก
		
		  if(state.selected.has(key)) state.selected.delete(key);
		  else state.selected.set(key, it);
		
		  cntEl.textContent = String(state.selected.size);
		  okBtn.disabled = state.selected.size === 0;
		  render();
		});

      listEl.appendChild(row);
    });
  }

  whEl.addEventListener('change', loadItems);
  qEl.addEventListener('input', loadItems);
  
  document.getElementById('cl_job_type')?.addEventListener('change', () => {
	  if (!modal.classList.contains('hidden')) {
		loadItems();
	  }
	});
  

	okBtn.addEventListener('click', ()=>{
	  if(state.selected.size === 0) return;
	
	  state.selected.forEach((item)=>{
		addPartRow(item, '1');   // ✅ item มี wh_id อยู่แล้ว จาก normalizeItem()
	  });
	
	  closePartsModal();
	  state.selected.clear();
	  cntEl.textContent = '0';
	  okBtn.disabled = true;
	  
	  
	});
	

})();

function getSelectedMultiValues(id) {
  const $el = $('#' + id);
  if (!$el.length) return [];

  const val = $el.val();

  if (Array.isArray(val)) {
    return val.map(v => String(v).trim()).filter(Boolean);
  }

  return val ? [String(val).trim()] : [];
}

function setMultiSelected(id, values) {
  const $el = $('#' + id);
  if (!$el.length) return;

  const selected = (values || [])
    .map(v => String(v).trim())
    .filter(Boolean);

  $el.find('option').prop('selected', false);

  selected.forEach(v => {
    $el.find('option').filter(function () {
      return String(this.value) === v;
    }).prop('selected', true);
  });

  $el.val(selected).trigger('change');
  setTimeout(syncResponsibleCheckboxes, 0);
}

function syncResponsibleCheckboxes() {
  const selected = new Set(getSelectedMultiValues('ac_tech'));

  document.querySelectorAll('.select2-results__option .rr-check').forEach(cb => {
    cb.checked = selected.has(String(cb.dataset.value));
  });
}

function formatResponsibleOption(option) {
  if (!option.id) return option.text;

  const value = String(option.id);
  const selected = new Set(getSelectedMultiValues('ac_tech'));
  const checked = selected.has(value) ? 'checked' : '';

  return $(`
    <div class="rr-option" style="display:flex; align-items:center; gap:8px;">
      <input
        type="checkbox"
        class="rr-check"
        data-value="${escapeHtml(value)}"
        ${checked}
        tabindex="-1"
        style="pointer-events:none; width:16px; height:16px;"
      />
      <span>${escapeHtml(option.text)}</span>
    </div>
  `);
}

function initResponsibleSelect2() {
  const $el = $('#ac_tech');
  if (!$el.length) return;

  if ($el.data('select2')) {
    $el.select2('destroy');
  }

  $el.select2({
    placeholder: 'เลือกช่าง / ผู้รับผิดชอบ',
    allowClear: true,
    closeOnSelect: false,
    width: '100%',
    dropdownParent: $('#ud_drawer').length ? $('#ud_drawer') : $(document.body),
    templateResult: formatResponsibleOption,
    templateSelection: function (option) {
      return option.text;
    },
    escapeMarkup: function (markup) {
      return markup;
    }
  });

  // กัน event ซ้ำ
  $el.off('.responsible');

  $el.on(
    'change.responsible select2:select.responsible select2:unselect.responsible select2:open.responsible',
    function () {
      setTimeout(syncResponsibleCheckboxes, 0);
      checkStep1Completed();
    }
  );
}

// ✅ กัน refresh เฉพาะตอนแก้ใบงานจริง ๆ
function isDrawerOpen() {
  const drawer = document.getElementById('ud_drawer');
  return drawer && !drawer.classList.contains('translate-x-full');
}

function isPartsModalOpen() {
  const modal = document.getElementById('parts_modal');
  return modal && !modal.classList.contains('hidden');
}

function isPopupOpen() {
  return !!document.querySelector('.swal2-container, .select2-container--open');
}

// ✅ เช็คเฉพาะ input ใน Drawer / Modal เท่านั้น
// ไม่เอาช่องค้นหาหน้าตารางมาบล็อก Auto Refresh
function isUserEditingRepairForm() {
  if (isDrawerOpen()) return true;
  if (isPartsModalOpen()) return true;
  if (isPopupOpen()) return true;

  return false;
}

// ✅ Auto Refresh รายการแจ้งซ่อม
let autoRefreshTimer = null;

function startAutoRefreshRepairList() {
  if (autoRefreshTimer) {
    clearInterval(autoRefreshTimer);
  }

  autoRefreshTimer = setInterval(() => {
    if (!gridApi) return;

    // ถ้าเปิด Drawer / Modal / Popup อยู่ ห้ามรีเฟรช
    if (isUserEditingRepairForm()) {
      return;
    }

    // ✅ บังคับโหลดข้อมูลใหม่จริง
    gridApi.refreshServerSide({ purge: true });

  }, 120000); // ทุก 30 วินาที
}

</script>




  <!-- FOOTER (ปุ่ม Save ย้ายมาด้านล่างแล้ว + อยู่ตลอด) -->
  <div class="px-6 py-3 bg-white border-t border-slate-200 flex items-center justify-between">
    <button type="button" onClick="closeDrawer()"
      class="px-4 py-2 rounded-lg border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50 transition">
      ปิด
    </button>

    <button type="button" onClick="saveDrawer()"
      class="group flex items-center gap-2 px-5 py-2 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold transition-all
             shadow-lg shadow-sky-200 hover:shadow-sky-300 active:scale-95">
      <i data-lucide="save" class="w-4 h-4 group-hover:animate-pulse"></i>
      บันทึกข้อมูล (Save)
    </button>
  </div>
</aside>

<!-- Evaluation modal: ต้องอยู่นอก #ud_drawer -->
<div id="repair_eval_modal"
     class="fixed inset-0 z-[22000] hidden">

  <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-[2px]"
       onclick="RepairEvaluationCenter.close()"></div>

  <div class="relative mx-auto mt-[3vh] w-[96%] max-w-6xl h-[92vh]
              bg-[#F8FAFC] rounded-3xl shadow-2xl border border-slate-200
              overflow-hidden flex flex-col font-['Kanit']">

    <!-- Header -->
    <div class="px-6 py-4 bg-white border-b border-slate-200
                flex items-center justify-between">

      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br
                    from-sky-600 to-sky-800 text-white
                    flex items-center justify-center shadow-md">
          <i data-lucide="mail-check" class="w-5 h-5"></i>
        </div>

        <div>
          <h2 class="text-base font-black text-slate-900">
            ตรวจสอบและส่งแบบประเมิน
          </h2>

          <p id="repair_eval_subtitle"
             class="text-[11px] text-slate-500 mt-0.5">
            กำลังโหลดข้อมูล...
          </p>
        </div>
      </div>

      <button type="button"
              onclick="RepairEvaluationCenter.close()"
              class="w-10 h-10 rounded-xl border border-slate-200
                     text-slate-400 hover:bg-rose-50
                     hover:text-rose-600 hover:border-rose-200
                     flex items-center justify-center">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>

    <!-- Body -->
    <div class="flex-1 min-h-0 overflow-y-auto p-5 space-y-4">

      <div id="repair_eval_document"
           class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
      </div>

      <div id="repair_eval_notice"></div>

      <div id="repair_eval_summary"
           class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

        <!-- รายชื่อผู้รับ -->
        <div class="lg:col-span-7 bg-white rounded-2xl
                    border border-slate-200 overflow-hidden">

          <div class="px-4 py-3 bg-slate-50 border-b border-slate-200
                      flex items-center justify-between gap-3">

            <div>
              <div class="text-xs font-black text-slate-800">
                รายชื่อผู้รับอีเมล
              </div>

              <div class="text-[10px] text-slate-400">
                เลือกผู้รับที่ต้องการส่งหรือส่งซ้ำ
              </div>
            </div>

            <label class="flex items-center gap-2 text-[11px]
                          font-bold text-slate-600 cursor-pointer">
              <input id="repair_eval_select_all"
                     type="checkbox"
                     class="ag-checkbox-input"
                     onchange="RepairEvaluationCenter.toggleAll(this.checked)">
              เลือกทั้งหมด
            </label>

          </div>

          <div id="repair_eval_recipients"
               class="divide-y divide-slate-100 max-h-[50vh] overflow-y-auto">
          </div>
        </div>

        <!-- Mail Logs -->
        <div class="lg:col-span-5 bg-white rounded-2xl
                    border border-slate-200 overflow-hidden">

          <div class="px-4 py-3 bg-slate-50 border-b border-slate-200
                      flex items-center justify-between">

            <div>
              <div class="text-xs font-black text-slate-800">
                Mail Logs
              </div>

              <div class="text-[10px] text-slate-400">
                ประวัติการส่งล่าสุด
              </div>
            </div>

            <button type="button"
                    onclick="RepairEvaluationCenter.load()"
                    class="w-8 h-8 rounded-lg border border-slate-200
                           bg-white hover:bg-slate-50 text-sky-700
                           flex items-center justify-center">
              <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
            </button>
          </div>

          <div id="repair_eval_logs"
               class="divide-y divide-slate-100 max-h-[50vh] overflow-y-auto">
          </div>
        </div>

      </div>
    </div>

    <!-- Footer -->
    <div class="px-5 py-4 bg-white border-t border-slate-200
                flex flex-wrap items-center justify-between gap-3">

      <button type="button"
              onclick="RepairEvaluationCenter.close()"
              class="px-4 py-2 rounded-xl border border-slate-200
                     text-slate-600 text-xs font-bold hover:bg-slate-50">
        ปิด
      </button>

      <div class="flex flex-wrap items-center gap-2">

        <button type="button"
                onclick="RepairEvaluationCenter.load()"
                class="px-4 py-2 rounded-xl border border-sky-200
                       bg-sky-50 text-sky-700 text-xs font-bold
                       hover:bg-sky-100 flex items-center gap-2">
          <i data-lucide="refresh-cw" class="w-4 h-4"></i>
          ตรวจสอบสถานะ
        </button>

        <button type="button"
                onclick="RepairEvaluationCenter.selectFailed()"
                class="px-4 py-2 rounded-xl border border-amber-200
                       bg-amber-50 text-amber-700 text-xs font-bold
                       hover:bg-amber-100">
          เลือกที่ยังไม่ส่ง/ล้มเหลว
        </button>

        <button id="repair_eval_send_btn"
                type="button"
                onclick="RepairEvaluationCenter.sendSelected()"
                class="px-5 py-2 rounded-xl bg-gradient-to-r
                       from-[#006B9F] to-[#04ADFF]
                       text-white text-xs font-bold shadow-md
                       disabled:opacity-40 disabled:cursor-not-allowed
                       flex items-center gap-2">
          <i data-lucide="send" class="w-4 h-4"></i>
          ส่งให้ผู้ที่เลือก
        </button>

      </div>
    </div>

  </div>
</div>


<!-- =========================================================
จุดที่ 3: วาง JavaScript นี้หลัง Modal หรือก่อน </body>
========================================================= -->

<script>
window.RepairEvaluationCenter = {
  row: null,
  data: null,
  selected: new Set(),

  open(row) {
    this.row = row || null;
    this.data = null;
    this.selected.clear();

    document
      .getElementById('repair_eval_modal')
      ?.classList.remove('hidden');

    document.getElementById('repair_eval_subtitle').textContent =
      `เลขที่ใบงาน: ${row?.rp_format || '-'}`;

    this.showLoading();
    this.load();

    safeIcons();
  },

  close() {
    document
      .getElementById('repair_eval_modal')
      ?.classList.add('hidden');

    this.row = null;
    this.data = null;
    this.selected.clear();
  },

  showLoading() {
    document.getElementById('repair_eval_document').innerHTML = `
      <div class="animate-pulse space-y-2">
        <div class="h-4 bg-slate-200 rounded w-1/3"></div>
        <div class="h-3 bg-slate-100 rounded w-2/3"></div>
      </div>
    `;

    document.getElementById('repair_eval_notice').innerHTML = '';
    document.getElementById('repair_eval_summary').innerHTML = '';

    document.getElementById('repair_eval_recipients').innerHTML = `
      <div class="py-12 text-center text-xs text-slate-400">
        กำลังตรวจสอบสถานะ...
      </div>
    `;

    document.getElementById('repair_eval_logs').innerHTML = '';
  },

  async load() {
    const repairId = this.row?.id || this.row?.rp_id;

    if (!repairId) {
      Swal.fire('ผิดพลาด', 'ไม่พบรหัสใบงาน', 'error');
      return;
    }

    try {
      const url = new URL(
        'repair_evaluation_api.php',
        window.location.href
      );

      url.searchParams.set('action', 'status');
      url.searchParams.set('id', repairId);

      const response = await fetch(url.toString(), {
        method: 'GET',
        cache: 'no-store',
        credentials: 'same-origin'
      });

      const text = await response.text();

      let json;

      try {
        json = JSON.parse(text);
      } catch (error) {
        console.error(text);
        throw new Error(
          'API ตอบกลับไม่ใช่ JSON กรุณาตรวจ PHP Error Log'
        );
      }

      if (!response.ok || !json?.success) {
        throw new Error(
          json?.message || 'ไม่สามารถโหลดสถานะได้'
        );
      }

      this.data = json.data || {};
      this.autoSelect();
      this.render();
	  if (
		  normalizeStatus(this.data?.repair?.status) === 'feedback' ||
		  Number(this.data?.repair?.has_feedback || 0) === 1 ||
		  this.data?.evaluation?.status === 'completed'
		) {
		  refreshGrid();
		}

    } catch (error) {
      console.error(error);

      document.getElementById('repair_eval_recipients').innerHTML = `
        <div class="py-12 px-5 text-center">
          <div class="text-sm font-bold text-rose-600">
            ไม่สามารถโหลดข้อมูลได้
          </div>
          <div class="text-xs text-slate-400 mt-2">
            ${escapeHtml(error.message || '')}
          </div>
        </div>
      `;
    }
  },

  autoSelect() {
    this.selected.clear();

    if (!this.data?.can_send) {
      return;
    }

    const isNewRound =
      this.data?.send_mode === 'new_round';

    (this.data?.recipients || []).forEach(item => {
      if (
        item.email_valid &&
        (
          isNewRound ||
          ['not_sent', 'failed'].includes(item.status)
        )
      ) {
        this.selected.add(String(item.notify_id));
      }
    });
  },

  badge(status) {
    const map = {
      not_sent: {
        text: 'ยังไม่ส่ง',
        cls: 'bg-slate-100 text-slate-600 border-slate-200'
      },
      pending: {
        text: 'กำลังส่ง',
        cls: 'bg-amber-50 text-amber-700 border-amber-200'
      },
      sent: {
        text: 'ส่งแล้ว',
        cls: 'bg-sky-50 text-sky-700 border-sky-200'
      },
      opened: {
        text: 'เปิดลิงก์แล้ว',
        cls: 'bg-violet-50 text-violet-700 border-violet-200'
      },
      failed: {
        text: 'ส่งไม่สำเร็จ',
        cls: 'bg-rose-50 text-rose-700 border-rose-200'
      },
      evaluated: {
        text: 'ประเมินแล้ว',
        cls: 'bg-emerald-50 text-emerald-700 border-emerald-200'
      },
      invalid: {
        text: 'อีเมลไม่ถูกต้อง',
        cls: 'bg-orange-50 text-orange-700 border-orange-200'
      }
    };

    return map[status] || map.not_sent;
  },

  render() {
    const repair = this.data?.repair || {};
    const evaluation = this.data?.evaluation || {};
    const summary = this.data?.summary || {};
    const recipients = this.data?.recipients || [];
    const logs = this.data?.logs || [];

    const completed =
	  evaluation.status === 'completed' ||
	  Number(repair.has_feedback || 0) === 1 ||
	  normalizeStatus(repair.status) === 'feedback';

    const isNewRound =
      this.data?.send_mode === 'new_round';

    document.getElementById('repair_eval_subtitle').textContent =
      `เลขที่ใบงาน: ${repair.rp_format || '-'}`;

    document.getElementById('repair_eval_document').innerHTML = `
      <div class="flex flex-col lg:flex-row lg:items-start
                  justify-between gap-4">

        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-base font-black text-sky-700">
              ${escapeHtml(repair.rp_format || '-')}
            </span>

            <span class="px-2.5 py-1 rounded-full text-[10px]
                         font-black border
                         ${completed
                           ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                           : 'bg-amber-50 text-amber-700 border-amber-200'}">
              ${completed
				  ? 'ประเมินเรียบร้อยแล้ว · ส่งรอบใหม่ได้'
				  : 'รอการประเมิน'}
            </span>
          </div>

          <div class="text-xs text-slate-500 mt-2">
            ผู้แจ้ง:
            <span class="font-bold text-slate-700">
              ${escapeHtml(repair.name || '-')}
            </span>
          </div>

          <div class="text-xs text-slate-500 mt-1">
            ปัญหา:
            <span class="font-semibold text-slate-700">
              ${escapeHtml(repair.problem_detail || '-')}
            </span>
          </div>

          <div class="text-xs text-slate-500 mt-1">
            สถานะใบงาน:
            <span class="font-bold text-slate-700">
              ${escapeHtml(repair.status || '-')}
            </span>
          </div>
        </div>

        ${completed ? `
          <div class="rounded-2xl bg-emerald-50 border
                      border-emerald-200 px-4 py-3 shrink-0">
            <div class="text-[10px] font-bold text-emerald-600">
              ผลการประเมินรอบที่
              ${Number(evaluation.round_no || 1)}
            </div>
            <div class="text-sm font-black text-emerald-800 mt-1">
              ${escapeHtml(evaluation.score_label || '-')}
            </div>
            <div class="text-[10px] text-emerald-600 mt-1">
              ${escapeHtml(evaluation.submitted_at_text || '')}
            </div>
          </div>
        ` : ''}
      </div>
    `;

    document.getElementById('repair_eval_notice').innerHTML =
      this.data?.can_send
        ? ''
        : `
          <div class="rounded-2xl border border-amber-200
                      bg-amber-50 px-4 py-3
                      flex items-start gap-3">
            <i data-lucide="triangle-alert"
               class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"></i>
            <div>
              <div class="text-xs font-black text-amber-800">
                ยังไม่สามารถส่งแบบประเมินได้
              </div>
              <div class="text-[11px] text-amber-700 mt-1">
                ${escapeHtml(
                  this.data?.can_send_reason ||
                  'กรุณาตรวจสอบสถานะใบงาน'
                )}
              </div>
            </div>
          </div>
        `;

    const cards = [
      {
        label: 'ผู้รับทั้งหมด',
        value: summary.total || 0,
        cls: 'text-slate-800',
        icon: 'users'
      },
      {
        label: 'ส่งแล้ว',
        value: summary.sent || 0,
        cls: 'text-sky-700',
        icon: 'send'
      },
      {
        label: 'เปิดลิงก์แล้ว',
        value: summary.opened || 0,
        cls: 'text-violet-700',
        icon: 'mouse-pointer-click'
      },
      {
        label: 'ส่งไม่สำเร็จ',
        value: summary.failed || 0,
        cls: 'text-rose-700',
        icon: 'circle-alert'
      }
    ];

    document.getElementById('repair_eval_summary').innerHTML =
      cards.map(card => `
        <div class="bg-white rounded-2xl border border-slate-200
                    px-4 py-3 shadow-sm flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-slate-50
                      flex items-center justify-center">
            <i data-lucide="${card.icon}"
               class="w-4 h-4 ${card.cls}"></i>
          </div>
          <div>
            <div class="text-[10px] font-bold text-slate-400">
              ${card.label}
            </div>
            <div class="text-lg font-black ${card.cls}">
              ${card.value}
            </div>
          </div>
        </div>
      `).join('');

    document.getElementById('repair_eval_recipients').innerHTML =
      recipients.length
        ? recipients.map(item => {
            const key = String(item.notify_id);
            const badge = this.badge(item.status);
            const checked = this.selected.has(key);

            const disabled =
              !item.email_valid ||
              !this.data?.can_send;

            return `
              <div class="px-4 py-3 flex items-start gap-3
                          ${checked ? 'bg-sky-50/60' : 'bg-white'}">

                <input type="checkbox"
                       class="ag-checkbox-input mt-1"
                       ${checked ? 'checked' : ''}
                       ${disabled ? 'disabled' : ''}
                       onchange="RepairEvaluationCenter.toggle(
                         '${escapeHtml(key)}',
                         this.checked
                       )">

                <div class="flex-1 min-w-0">

                  <div class="flex flex-wrap items-center
                              justify-between gap-2">

                    <div class="font-black text-[12px]
                                text-slate-800 truncate">
                      ${escapeHtml(item.name || '-')}
                    </div>

                    <span class="px-2 py-0.5 rounded-full border
                                 text-[9px] font-black ${badge.cls}">
                      ${badge.text}
                    </span>
                  </div>

                  <div class="text-[10px] text-slate-500 mt-1 truncate">
                    ${escapeHtml(item.position || '-')}
                    ·
                    ${escapeHtml(item.email || '-')}
                  </div>

                  <div class="text-[10px] text-slate-400 mt-1">
                    ${item.last_sent_at_text
                      ? `ส่งล่าสุด ${escapeHtml(item.last_sent_at_text)}`
                      : 'ยังไม่มีประวัติการส่ง'}
                  </div>

                  ${item.last_error ? `
                    <div class="text-[10px] text-rose-600
                                bg-rose-50 border border-rose-100
                                rounded-lg px-2 py-1 mt-2">
                      ${escapeHtml(item.last_error)}
                    </div>
                  ` : ''}
                </div>

                ${!disabled ? `
                  <button type="button"
                          onclick="RepairEvaluationCenter.sendOne(
                            '${escapeHtml(key)}'
                          )"
                          class="h-8 px-3 rounded-lg border
                                 border-sky-200 bg-white text-sky-700
                                 text-[10px] font-black
                                 hover:bg-sky-50 shrink-0">
                    ${item.status === 'not_sent' ? 'ส่ง' : 'ส่งซ้ำ'}
                  </button>
                ` : ''}
              </div>
            `;
          }).join('')
        : `
          <div class="py-12 text-center text-xs text-slate-400">
            ไม่พบผู้รับอีเมลของหน่วยงานนี้
          </div>
        `;

    document.getElementById('repair_eval_logs').innerHTML =
      logs.length
        ? logs.map(log => {
            const badge = this.badge(
              log.effective_status || log.send_status
            );

            return `
              <div class="px-4 py-3">
                <div class="flex items-start justify-between gap-3">

                  <div class="min-w-0">
                    <div class="text-[10px] font-bold text-slate-400">
                      ${escapeHtml(log.created_at_text || '-')}
                    </div>

                    <div class="text-[11px] font-black
                                text-slate-700 mt-1 truncate">
                      ${escapeHtml(log.recipient_name || '-')}
                    </div>

                    <div class="text-[10px] text-slate-500 truncate">
                      ${escapeHtml(log.recipient_email || '-')}
                    </div>

                    <div class="text-[9px] text-slate-400 mt-1">
                      รอบที่ ${Number(log.round_no || 1)}
                      · ครั้งที่ ${Number(log.attempt_no || 1)}
                    </div>
                  </div>

                  <span class="px-2 py-0.5 rounded-full border
                               text-[9px] font-black ${badge.cls}">
                    ${badge.text}
                  </span>
                </div>

                ${log.error_message ? `
                  <div class="mt-2 text-[10px] text-rose-600
                              bg-rose-50 border border-rose-100
                              rounded-lg px-2 py-1">
                    ${escapeHtml(log.error_message)}
                  </div>
                ` : ''}
              </div>
            `;
          }).join('')
        : `
          <div class="py-12 text-center text-xs text-slate-400">
            ยังไม่มีประวัติการส่งอีเมล
          </div>
        `;

    const sendButton =
      document.getElementById('repair_eval_send_btn');

    if (sendButton) {
      sendButton.disabled =
        !this.data?.can_send ||
        this.selected.size === 0;

      sendButton.innerHTML = isNewRound
        ? `
            <i data-lucide="rotate-cw" class="w-4 h-4"></i>
            ส่งแบบประเมินรอบใหม่
          `
        : `
            <i data-lucide="send" class="w-4 h-4"></i>
            ส่งให้ผู้ที่เลือก
          `;
    }

    const available = recipients.filter(item =>
      item.email_valid &&
      this.data?.can_send
    );

    document.getElementById('repair_eval_select_all').checked =
      available.length > 0 &&
      available.every(item =>
        this.selected.has(String(item.notify_id))
      );

    safeIcons();
  },

  toggle(id, checked) {
    if (checked) {
      this.selected.add(String(id));
    } else {
      this.selected.delete(String(id));
    }

    this.render();
  },

  toggleAll(checked) {
    this.selected.clear();

    if (checked && this.data?.can_send) {
      (this.data?.recipients || []).forEach(item => {
        if (item.email_valid) {
          this.selected.add(String(item.notify_id));
        }
      });
    }

    this.render();
  },

  selectFailed() {
    this.selected.clear();

    if (!this.data?.can_send) {
      this.render();
      return;
    }

    const isNewRound =
      this.data?.send_mode === 'new_round';

    (this.data?.recipients || []).forEach(item => {
      if (
        item.email_valid &&
        (
          isNewRound ||
          ['not_sent', 'failed'].includes(item.status)
        )
      ) {
        this.selected.add(String(item.notify_id));
      }
    });

    this.render();

    if (!this.selected.size) {
      Swal.fire(
        'ไม่มีรายการ',
        this.data?.send_mode === 'new_round'
          ? 'ไม่พบผู้รับอีเมลที่ใช้งานได้'
          : 'ไม่พบผู้รับที่ยังไม่ส่งหรือส่งล้มเหลว',
        'info'
      );
    }
  },

  async sendOne(notifyId) {
    this.selected.clear();
    this.selected.add(String(notifyId));
    await this.sendSelected();
  },

  async sendSelected() {
    const repairId = this.row?.id || this.row?.rp_id;
    const ids = [...this.selected];

    if (!repairId || !ids.length) {
      return;
    }

    const isNewRound =
      this.data?.send_mode === 'new_round';

    const confirm = await Swal.fire({
      icon: 'question',
      title: isNewRound
        ? 'ยืนยันส่งแบบประเมินรอบใหม่?'
        : 'ยืนยันส่งแบบประเมิน?',
      html: `
        <div class="text-sm text-slate-600">
          ${isNewRound
            ? 'ระบบจะสร้างรอบการประเมินใหม่และส่งลิงก์ใหม่ให้'
            : 'ระบบจะส่งอีเมลให้'}
          ผู้รับที่เลือก <b>${ids.length}</b> คน
          ${isNewRound
            ? '<div class="mt-2 text-xs text-amber-600">ผลประเมินเดิมยังคงเก็บไว้และเปิดดูได้</div>'
            : ''}
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: isNewRound
        ? 'สร้างรอบใหม่และส่ง'
        : 'ยืนยันส่งอีเมล',
      cancelButtonText: 'ยกเลิก',
      reverseButtons: true
    });

    if (!confirm.isConfirmed) {
      return;
    }

    try {
      Swal.fire({
        title: 'กำลังส่งอีเมล...',
        text: 'โปรดรอสักครู่',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => Swal.showLoading()
      });

      const fd = new FormData();
      fd.append('id', repairId);

      ids.forEach(id => {
        fd.append('notify_ids[]', id);
      });

      const url = new URL(
        'repair_evaluation_api.php',
        window.location.href
      );

      url.searchParams.set('action', 'send');

      const response = await fetch(url.toString(), {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      });

      const text = await response.text();

      let json;

      try {
        json = JSON.parse(text);
      } catch (error) {
        console.error(text);
        throw new Error(
          'API ตอบกลับไม่ใช่ JSON กรุณาตรวจ PHP Error Log'
        );
      }

      if (!response.ok || !json?.success) {
        throw new Error(
          json?.message || 'ไม่สามารถส่งอีเมลได้'
        );
      }

      const result = json.data || {};
      const failedCount =
        Number(result.failed_count || 0);

      await Swal.fire({
        icon: failedCount > 0 ? 'warning' : 'success',
        title: failedCount > 0
          ? 'ส่งอีเมลสำเร็จบางส่วน'
          : 'ส่งอีเมลเรียบร้อย',
        html: `
          <div class="text-sm">
            <div class="mb-2 font-bold text-sky-700">
              รอบการประเมินที่ ${Number(result.round_no || 1)}
            </div>
            ส่งสำเร็จ
            <b class="text-emerald-600">
              ${Number(result.sent_count || 0)}
            </b>
            คน<br>

            ส่งไม่สำเร็จ
            <b class="text-rose-600">
              ${failedCount}
            </b>
            คน
          </div>
        `
      });

      await this.load();
      refreshGrid();

    } catch (error) {
      console.error(error);

      Swal.fire({
        icon: 'error',
        title: 'ส่งอีเมลไม่สำเร็จ',
        text: error.message || 'กรุณาลองใหม่'
      });
    }
  }
};
</script>


</body>
</html>