<?php
@session_start();
include "config_ctrl/checksession.php"; 
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Repair Requests - List</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- ✅ ag-Grid Enterprise v31 -->
  <script src="https://cdn.jsdelivr.net/npm/ag-grid-enterprise@31/dist/ag-grid-enterprise.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31/styles/ag-grid.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ag-grid-community@31/styles/ag-theme-alpine.css">

  <style>
    :root{
      --color-bg-light:#F0F4F8;
      --color-primary:#006B9F;
      --color-secondary:#04ADFF;
      --ag-accent-color:#006B9F;
      --ag-header-background-color:#f8fafc;
      --ag-row-hover-color:#f1f5f9;
      --ag-selected-row-color:#e2e8f0;
      --ag-font-family:'Kanit',sans-serif;
      --ag-font-size:13px;
    }
    body{
      font-family:'Kanit',sans-serif;
      background-color:var(--color-bg-light);
      height:100vh;
      overflow:hidden;
    }
    .btn-gradient{
      background:linear-gradient(to right,#006B9F,#04ADFF);
      color:white;
      transition:all .3s ease;
    }
    .btn-gradient:hover{
      box-shadow:0 10px 20px -5px rgba(0,107,159,.4);
      transform:translateY(-1px);
    }
    .custom-scroll::-webkit-scrollbar{width:6px;height:6px;}
    .custom-scroll::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:10px;}
    .ag-theme-alpine .ag-cell{ display:flex; align-items:center; }

    .swal-carousel-container{
      position:relative;
      width:100%;
      height:350px;
      overflow:hidden;
      border-radius:1.5rem;
      background:#f8fafc;
    }
    .carousel-item{
      display:none;
      width:100%;
      height:100%;
      animation:fadeIn .25s ease;
    }
    .carousel-item.active{
      display:flex;
      align-items:center;
      justify-content:center;
    }
    .carousel-item img{
      max-width:100%;
      max-height:100%;
      object-fit:contain;
    }
    .carousel-btn{
      position:absolute;
      top:50%;
      transform:translateY(-50%);
      background:rgba(255,255,255,.85);
      color:#334155;
      padding:8px;
      border-radius:999px;
      cursor:pointer;
      z-index:20;
      box-shadow:0 4px 6px -1px rgb(0 0 0 / .1);
      transition:all .2s;
    }
    .carousel-btn:hover{
      background:#fff;
      transform:translateY(-50%) scale(1.06);
    }
    @keyframes fadeIn { from{opacity:0} to{opacity:1} }

    .line-clamp-2{
      display:-webkit-box;
      -webkit-line-clamp:2;
      -webkit-box-orient:vertical;
      overflow:hidden;
    }
	/* ✅ FIX: กัน overlay ของ context menu บังทั้งกริด */
/* ✅ FIX จริง: overlay ตอนคลิกขวาให้มองไม่เห็น แต่ยังปิดเมนูได้ */
.ag-theme-alpine .ag-popup-shield{
  display: none !important;
}

/* กัน container popup ไปทำพื้นขาวเอง */
.ag-theme-alpine .ag-popup,
.ag-theme-alpine .ag-popup-child{
  background-color: transparent !important;
}

    .ag-theme-alpine{
  --ag-border-color:#e2e8f0;
  --ag-border-radius:12px; 
}
.ag-theme-alpine{
  --ag-modal-overlay-background-color: transparent;
}

/* ตัวที่บังตารางตอนคลิกขวา */
.ag-theme-alpine .ag-popup-shield{
  background-color: transparent !important;
  opacity: 0 !important;         /* ✅ สำคัญ: ต้องเป็น 0 */
  backdrop-filter: none !important;
} /* พ่อของกริดต้องมีความสูงจริง */
main{ min-height: 0; } /* สำคัญกับ flex */
#myGrid{
  height: 100%;
  min-height: 520px;   /* กันหาย */
}
/* ✅ ทำเมนูคลิกขวาให้ทึบ ไม่โปร่ง */
.ag-theme-alpine .ag-menu,
.ag-theme-alpine .ag-menu .ag-menu-list,
.ag-theme-alpine .ag-popup .ag-menu {
  background-color: #fff !important;
  opacity: 1 !important;
  backdrop-filter: none !important;
}

/* เผื่อเคยไปทำ .ag-popup โปร่งไว้ */
.ag-theme-alpine .ag-popup{
  background-color: transparent !important; /* คอนเทนเนอร์โปร่งได้ */
}

/* แต่เมนูต้องทึบ (สำคัญสุด) */
.ag-theme-alpine .ag-menu{
  box-shadow: 0 10px 25px rgba(0,0,0,.12) !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 12px !important;
}

  </style>
</head>

<body class="flex flex-col antialiased text-slate-700">

  <nav class="flex-none px-3 py-4 flex items-center justify-between border-b border-slate-200 bg-white z-30">
    <div class="flex items-center gap-3">
      <div class="p-2.5 bg-[--color-primary] rounded-xl shadow-lg shadow-sky-100 text-white shadow-lg shadow-sky-200">
        <i data-lucide="clipboard-list" class="w-6 h-6"></i>
      </div>
      <div>
        <h1 class="text-xl font-bold text-slate-900 leading-none">Repair Requests</h1>
        <p class="text-sm text-slate-500 mt-1">รายการแจ้งซ่อมทั้งหมด</p>
      </div>
    </div>

    <div class="flex items-center gap-2 md:gap-3">
      <button onClick="window.location.href='maintenance_request.php'"
        class="flex items-center gap-2 btn-gradient px-3 md:px-5 py-2.5 rounded-xl text-sm font-medium transition-all shadow-md">
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span class="hidden md:inline">สร้างใบแจ้งซ่อม</span>
      </button>
    </div>
  </nav>

  <main class="flex-1 flex flex-col p-4 gap-4 overflow-hidden">
    <div class="flex flex-col md:flex-row items-center justify-between gap-4 flex-none">
      <div class="relative w-full md:w-96">
        <i data-lucide="search" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
        <input type="text" id="grid-search" oninput="onFilterTextBoxChanged()"
          placeholder="ค้นหา: ชื่อผู้แจ้ง, เบอร์, อาคาร, ห้อง, Machine ID, สถานะ..."
          class="w-full bg-white border border-slate-200 rounded-xl pl-11 pr-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-sky-500 outline-none transition-all">
      </div>

      <div class="flex gap-2 text-xs font-bold text-slate-500">
        <div class="bg-white border border-slate-200 rounded-xl px-4 py-2 flex items-center gap-2">
          <span id="row-count" class="text-sky-600 font-bold">0</span> รายการทั้งหมด
        </div>
      </div>
    </div>

    <div class="flex-1 min-h-0 overflow-hidden p-1 relative">
      <div id="myGrid" class="ag-theme-alpine w-full h-full"></div>
    </div>
  </main>

<script>
  let gridApi;

  // ---------- helpers ----------
  function escapeHtml(s){
    return (s ?? "").toString()
      .replace(/&/g,"&amp;")
      .replace(/</g,"&lt;")
      .replace(/>/g,"&gt;")
      .replace(/"/g,"&quot;")
      .replace(/'/g,"&#039;");
  }

  function getImagesFromRow(row){
    if(!row) return [];
    if(Array.isArray(row.images)) return row.images.filter(Boolean);
    // เผื่อ backend ส่งเป็น string concat มา
    if(typeof row.images === "string" && row.images.trim()){
      return row.images.split("||").map(s=>s.trim()).filter(Boolean);
    }
    return [];
  }

  const IMG_BASE = `${location.origin}/es/API_es/uploads/`;

  function inferYearMonth(row){
    const d = (row?.report_date || "").toString();
    if(/^\d{4}-\d{2}-\d{2}$/.test(d)){
      return { y: d.slice(0,4), m: d.slice(5,7) };
    }
    const c = (row?.created_at || "").toString();
    if(/^\d{4}-\d{2}-\d{2}/.test(c)){
      return { y: c.slice(0,4), m: c.slice(5,7) };
    }
    return null;
  }

  function normalizeImageUrl(u, row){
    if(!u) return "";
    u = String(u).trim().replace(/\\/g,'/');

    if(u.startsWith("http://") || u.startsWith("https://")) return encodeURI(u);

    // ถ้า DB เก็บเป็น "2025/12/file.jpg"
    if(/^\d{4}\/\d{2}\//.test(u)) return encodeURI(IMG_BASE + u.replace(/^\/+/, ''));

    const ym = inferYearMonth(row);
    if(ym){
      return encodeURI(`${IMG_BASE}${ym.y}/${ym.m}/${u.replace(/^\/+/, '')}`);
    }

    return encodeURI(IMG_BASE + u.replace(/^\/+/, ''));
  }

  function formatThaiDate(dateStr){
    if(!dateStr) return "-";
    const d = new Date(dateStr + "T00:00:00");
    return new Intl.DateTimeFormat("th-TH", { day:"numeric", month:"short", year:"numeric" }).format(d);
  }

  // ---------- columns ----------
  const columnDefs = [
    {
      headerName:"รูป",
      field:"images",
      width:80,
      pinned:"left",
      sortable:false,
      filter:false,
      cellRenderer:(params)=>{
        const images = getImagesFromRow(params.data).map(u => normalizeImageUrl(u, params.data)).filter(Boolean);
        const firstImg = images.length ? images[0] : null;

        if(firstImg){
          return `
            <div class="flex items-center h-full cursor-pointer group" onclick='openImageCarousel(${JSON.stringify(images)})'>
              <div class="relative">
                <img src="${firstImg}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 shadow-sm group-hover:scale-105 transition-transform">
              </div>
            </div>`;
        }

        return `
          <div class="flex items-center h-full">
            <div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-200 border-dashed flex items-center justify-center text-slate-300">
              <i data-lucide="image" class="w-4 h-4"></i>
            </div>
          </div>`;
      }
    },

    {
      headerName: "วันที่/เวลา",
      field: "report_date",
      width: 160,
      pinned: "left",
      filter: 'agDateColumnFilter',
      filterParams: {
        comparator: (filterLocalDateAtMidnight, cellValue) => {
          if (cellValue == null) return -1;
          const dateParts = String(cellValue).split("-");
          const cellDate = new Date(Number(dateParts[0]), Number(dateParts[1]) - 1, Number(dateParts[2]));
          if (filterLocalDateAtMidnight.getTime() === cellDate.getTime()) return 0;
          return (cellDate < filterLocalDateAtMidnight) ? -1 : 1;
        }
      },
      valueGetter: (p) => p.data?.report_date || "",
      cellRenderer: (p) => {
        const d = formatThaiDate(p.data?.report_date);
        const t = p.data?.report_time || "";
        return `<div class="leading-tight py-2">
          <div class="font-bold text-slate-800">${escapeHtml(d)}</div>
          <div class="text-[10px] text-slate-400 font-semibold">${escapeHtml(t)}</div>
        </div>`;
      }
    },

    {
      headerName:"Machine ID",
      field:"asset_code",
      colId:"machine_id",
      width:180,
      filter:true,
      hide:true,
      cellRenderer:(p)=>{
        const code = (p.data?.asset_code || "").toString().trim();
        const name = (p.data?.asset_name || "").toString().trim();
        if(!code && !name) return "";
        return `
          <div class="leading-tight py-1">
            <div class="font-mono text-[10px] font-bold text-sky-700 bg-sky-50 inline-block px-2 py-0.5 rounded-md border border-sky-100">
              ${escapeHtml(code || "-")}
            </div>
            <div class="text-[11px] text-slate-600 font-semibold mt-1 line-clamp-2">
              ${escapeHtml(name || "")}
            </div>
          </div>
        `;
      }
    },

    {
      headerName:"ผู้แจ้ง",
      width:220,
      filter:true,
      valueGetter:(p)=>`${p.data?.name || ""} ${p.data?.phone || ""}`,
      cellRenderer:(p)=>{
        const name = p.data?.name || "ไม่ระบุชื่อ";
        const phone = p.data?.phone || "-";
        return `<div class="leading-tight py-2">
          <div class="font-bold text-slate-800">${escapeHtml(name)}</div>
          <div class="text-[10px] text-slate-400 font-semibold">ติดต่อ ${escapeHtml(phone)}</div>
        </div>`;
      }
    },

    { headerName:"อาคาร", field:"building_name", width:150, filter:true,
      cellRenderer:(p)=>`<span class="font-bold text-slate-800">${escapeHtml(p.value || "-")}</span>` },

    { headerName:"ชั้น", field:"floor_name", width:110, filter:true,
      cellRenderer:(p)=>`<span class="text-slate-700 font-semibold">${escapeHtml(p.value || "-")}</span>` },

    { headerName:"ห้อง", field:"room_name", width:110, filter:true,
      cellRenderer:(p)=>`<span class="text-slate-700 font-semibold">${escapeHtml(p.value || "-")}</span>` },

    {
      headerName:"อาการเสีย/ปัญหา",
      field:"problem_detail",
      flex:1,
      minWidth:260,
      filter:true,
      cellRenderer:(p)=>{
        const v = (p.value || "").toString();
        return `<div class="py-2 leading-snug">
          <div class="font-semibold text-slate-800 line-clamp-2">${escapeHtml(v || "-")}</div>
        </div>`;
      }
    },

    {
      headerName:"สถานะ",
      field:"status",
      width:140,
      filter:true,
      cellRenderer:(p)=>{
        const raw = (p.value || "").toString().trim();
        const v = raw.toLowerCase();

        let color = "slate";
        if(["ใหม่","รอดำเนินการ","pending"].includes(v)) color = "amber";
        if(["กำลังดำเนินการ","in progress","doing"].includes(v)) color = "sky";
        if(["เสร็จสิ้น","completed","done"].includes(v)) color = "emerald";
        if(["ยกเลิก","cancel","canceled"].includes(v)) color = "rose";

        const label = raw || "ไม่ระบุ";
        return `<div class="flex items-center gap-1.5 px-2.5 rounded-xl text-[11px] font-bold w-fit bg-${color}-50 text-${color}-600 border border-${color}-100">
          <span class="w-1.5 h-1.5 rounded-full bg-${color}-500"></span>${escapeHtml(label)}
        </div>`;
      }
    },

    { headerName:"ผู้บันทึก", field:"created_by", width:140, filter:true },

    {
      headerName:"จัดการ",
      width:120,
      pinned:"right",
      sortable:false,
      filter:false,
      cellRenderer:(params)=>{
        if(!params.data) return "";
        const images = getImagesFromRow(params.data).map(u => normalizeImageUrl(u, params.data)).filter(Boolean);
        setTimeout(()=>lucide.createIcons(),0);

        return `
          <div class="flex items-center gap-1 h-full py-2">
            <button onclick='viewRepairDetail(${JSON.stringify(params.data)})'
              class="p-2 hover:bg-slate-50 text-slate-500 rounded-xl transition-colors" title="ดูรายละเอียด">
              <i data-lucide="file-text" class="w-4 h-4"></i>
            </button>

            <button onclick='openImageCarousel(${JSON.stringify(images)})'
              class="p-2 hover:bg-sky-50 text-sky-600 rounded-xl transition-colors" title="ดูรูป">
              <i data-lucide="images" class="w-4 h-4"></i>
            </button>
          </div>`;
      }
    }
  ];

  // ---------- SSR datasource (v31) ----------
 function createDatasource(){
  return {
    getRows: async (params) => {
      try{
        const payload = {
          action: "get_all_ssr",
          ag_id: <?php echo (int)$sess_user_agency_es; ?>,
          startRow: params.request.startRow,
          endRow: params.request.endRow,
          sortModel: params.request.sortModel || [],
          filterModel: params.request.filterModel || {},
          q: (document.getElementById("grid-search")?.value || "").trim()
        };

        const res = await fetch("handle_repair_requests_v2.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload)
        });

        const json = await res.json();

        if(!res.ok || !json || json.success !== true){
          console.error("SSR error:", json);
          params.fail();
          Swal.fire("ผิดพลาด", json?.error || "โหลดข้อมูลไม่สำเร็จ", "error");
          return;
        }

        const rows = (json.data || []).map(r=>{
          const imgs = getImagesFromRow(r);
          r.images = imgs.map(u => normalizeImageUrl(u, r)).filter(Boolean);
          return r;
        });

        params.success({ rowData: rows, rowCount: json.total || 0 });
        document.getElementById("row-count").innerText = json.total || 0;

        const hasMachine = rows.some(r => ((r.asset_code || r.machine_id || "") + "").trim() !== "");
        if(gridApi) gridApi.setColumnsVisible(["machine_id"], hasMachine);

        setTimeout(()=>lucide.createIcons(),0);
      }catch(err){
        console.error(err);
        params.fail();
        Swal.fire("ผิดพลาด", "เชื่อมต่อเซิร์ฟเวอร์ไม่ได้", "error");
      }
    }
  };
}


  // ---------- grid options ----------
// ---------- grid options ----------
const gridOptions = {
  columnDefs,
  rowModelType: 'serverSide',
  serverSideStoreType: 'partial',
  cacheBlockSize: 50,
  rowHeight: 65,

  pagination: true,
  paginationPageSize: 10,
  paginationPageSizeSelector: [10,20,50,100],

  onGridReady: (params) => {
    gridApi = params.api;

    // ✅ v31
    gridApi.setGridOption('serverSideDatasource', createDatasource());
    gridApi.refreshServerSide({ purge: true });

    setTimeout(()=>lucide.createIcons(),0);
  },

  onPaginationChanged: ()=> setTimeout(()=>lucide.createIcons(),0),
  onRowDataUpdated: ()=> setTimeout(()=>lucide.createIcons(),0),
  onViewportChanged: ()=> setTimeout(()=>lucide.createIcons(),0),
};


  window.onload = () => {
	  const gridDiv = document.querySelector("#myGrid");
	  gridApi = agGrid.createGrid(gridDiv, gridOptions); // ✅ สร้างกริด
	  setTimeout(()=>lucide.createIcons(),0);
	};
  // search debounce
  let debounce = null;
  function onFilterTextBoxChanged(){
    clearTimeout(debounce);
    debounce = setTimeout(()=>{
      if(gridApi){
        gridApi.refreshServerSide({ purge:true });
      }
    }, 300);
  }

  // -------- Image Carousel ----------
  let currentSlide = 0;

  function openImageCarousel(images){
    if(!images || images.length === 0){
      Swal.fire("ไม่มีรูป", "รายการนี้ไม่มีรูปแนบ", "info");
      return;
    }

    const urls = images.filter(Boolean);
    currentSlide = 0;

    let carouselHtml = `<div class="swal-carousel-container">`;
    urls.forEach((img, idx)=>{
      carouselHtml += `
        <div class="carousel-item ${idx===0?'active':''}" id="slide-${idx}">
          <img src="${img}" onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'500\\' height=\\'260\\'><rect width=\\'100%\\' height=\\'100%\\' fill=\\'%23f1f5f9\\'/><text x=\\'50%\\' y=\\'50%\\' text-anchor=\\'middle\\' fill=\\'%2394a3b8\\' font-size=\\'16\\'>Image not found</text></svg>'; ">
        </div>`;
    });

    if(urls.length > 1){
      carouselHtml += `
        <button onclick="changeSlide(-1, ${urls.length})" class="carousel-btn left-3"><i data-lucide="chevron-left"></i></button>
        <button onclick="changeSlide(1, ${urls.length})" class="carousel-btn right-3"><i data-lucide="chevron-right"></i></button>
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-1.5 z-20">
          ${urls.map((_,i)=>`<div id="dot-${i}" class="w-2 h-2 rounded-full ${i===0?'bg-sky-500':'bg-slate-300'} transition-all"></div>`).join('')}
        </div>
      `;
    }
    carouselHtml += `</div>`;

    Swal.fire({
      title: 'รูปประกอบการแจ้งซ่อม',
      html: carouselHtml,
      showConfirmButton: false,
      showCloseButton: true,
      width: '650px',
      customClass: { popup: 'rounded-3xl p-4' },
      didOpen: ()=>lucide.createIcons()
    });
  }

  function changeSlide(dir,total){
    document.querySelector(`#slide-${currentSlide}`).classList.remove("active");
    document.querySelector(`#dot-${currentSlide}`).classList.replace("bg-sky-500","bg-slate-300");
    currentSlide = (currentSlide + dir + total) % total;
    document.querySelector(`#slide-${currentSlide}`).classList.add("active");
    document.querySelector(`#dot-${currentSlide}`).classList.replace("bg-slate-300","bg-sky-500");
  }

  // -------- Detail Modal ----------
  function viewRepairDetail(data){
    const images = getImagesFromRow(data).map(u => normalizeImageUrl(u, data)).filter(Boolean);

    const imgBtn = images.length
      ? `<button type="button" onclick='openImageCarousel(${JSON.stringify(images)})'
          class="btn-gradient px-3 py-2 rounded-xl text-xs font-bold inline-flex items-center gap-2">
          <i data-lucide="images" class="w-4 h-4"></i> ดูรูป (${images.length})
        </button>`
      : `<div class="text-xs text-slate-400 font-semibold">ไม่มีรูปแนบ</div>`;

    const html = `
      <div class="text-left space-y-3">
        <div class="grid grid-cols-2 gap-2">
          <div class="bg-slate-50 border border-slate-100 rounded-2xl p-3">
            <div class="text-[10px] text-slate-400 font-bold uppercase">วันที่/เวลา</div>
            <div class="font-bold text-slate-800">${escapeHtml(data.report_date || "-")} ${escapeHtml(data.report_time || "")}</div>
          </div>
          <div class="bg-slate-50 border border-slate-100 rounded-2xl p-3">
            <div class="text-[10px] text-slate-400 font-bold uppercase">Machine ID</div>
            <div class="font-mono font-bold text-sky-700">${escapeHtml(data.asset_code || data.machine_id || "-")}</div>
          </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-3">
          <div class="text-[10px] text-slate-400 font-bold uppercase">ผู้แจ้ง</div>
          <div class="font-bold text-slate-800">${escapeHtml(data.name || "-")}</div>
          <div class="text-xs text-slate-500 font-semibold">${escapeHtml(data.phone || "-")}</div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-3">
          <div class="text-[10px] text-slate-400 font-bold uppercase">สถานที่</div>
          <div class="font-bold text-slate-800">${escapeHtml(data.building_name || "-")}</div>
          <div class="text-xs text-slate-500 font-semibold">${escapeHtml(data.floor_name || "-")} • ${escapeHtml(data.room_name || "-")}</div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-3">
          <div class="text-[10px] text-slate-400 font-bold uppercase">อาการเสีย/ปัญหา</div>
          <div class="text-sm font-semibold text-slate-800 whitespace-pre-wrap">${escapeHtml(data.problem_detail || "-")}</div>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div class="bg-slate-50 border border-slate-100 rounded-2xl p-3">
            <div class="text-[10px] text-slate-400 font-bold uppercase">ผู้บันทึก</div>
            <div class="font-bold text-slate-800">${escapeHtml(data.created_by || "-")}</div>
          </div>
          <div class="bg-slate-50 border border-slate-100 rounded-2xl p-3">
            <div class="text-[10px] text-slate-400 font-bold uppercase">สถานะ</div>
            <div class="font-bold text-slate-800">${escapeHtml(data.status || "-")}</div>
          </div>
        </div>

        <div class="flex items-center justify-between pt-1">
          <div class="text-xs text-slate-400 font-semibold">บันทึกเมื่อ: <span class="text-slate-700 font-bold">${escapeHtml(data.created_at || "-")}</span></div>
          ${imgBtn}
        </div>
      </div>
    `;

    Swal.fire({
      title: 'รายละเอียดใบแจ้งซ่อม',
      html,
      showCloseButton: true,
      showConfirmButton: false,
      width: '760px',
      customClass: { popup: 'rounded-3xl p-4' },
      didOpen: ()=>lucide.createIcons()
    });
  }
</script>

</body>
</html>
