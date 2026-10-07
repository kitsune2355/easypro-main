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
       ✅ Side Drawer (Right)
       ========================= */
    .rr-overlay{
      position: fixed; inset: 0;
      background: rgba(2,6,23,.45);
      opacity: 0; pointer-events: none;
      transition: opacity .18s ease;
      z-index: 60;
    }
    .rr-overlay.show{ opacity:1; pointer-events:auto; }

    .rr-drawer{
      position: fixed;
      top: 0; right: 0;
      width: min(560px, 96vw);
      height: 100vh;
      background: #fff;
      transform: translateX(110%);
      transition: transform .22s ease;
      z-index: 70;
      box-shadow: -20px 0 40px rgba(2,6,23,.18);
      display:flex; flex-direction: column;
    }
    .rr-drawer.show{ transform: translateX(0); }

    .rr-drawer-head{
      padding: 14px 16px;
      border-bottom: 1px solid #e2e8f0;
      display:flex; align-items:center; justify-content: space-between;
      background: linear-gradient(180deg, #ffffff, #fbfdff);
    }
    .rr-chip{
      display:inline-flex; align-items:center; gap:6px;
      padding: 4px 10px;
      border-radius: 999px;
      border: 1px solid #e2e8f0;
      background: #f8fafc;
      font-size: 12px;
      font-weight: 800;
      color:#0f172a;
    }

    .rr-tabs{
      display:flex;
      gap:6px;
      padding: 10px 12px;
      border-bottom: 1px solid #e2e8f0;
      background:#fff;
    }
    .rr-tab{
      flex:1;
      text-align:center;
      padding: 10px 10px;
      border-radius: 14px;
      border: 1px solid #e2e8f0;
      background: #f8fafc;
      font-size: 13px;
      font-weight: 900;
      color:#334155;
      cursor:pointer;
      transition: all .15s;
      user-select:none;
    }
    .rr-tab:hover{ transform: translateY(-1px); background:#ffffff; }
    .rr-tab.active{
      border-color: rgba(14,165,233,.35);
      box-shadow: 0 8px 18px rgba(2,8,23,.08);
      background: linear-gradient(90deg, rgba(0,107,159,.10), rgba(4,173,255,.10));
      color:#0b3a52;
    }

    .rr-drawer-body{
      padding: 14px 14px 120px;
      overflow:auto;
      flex:1;
    }
    .rr-section{
      border: 1px solid #e2e8f0;
      border-radius: 18px;
      background: #fff;
      padding: 12px;
      margin-bottom: 12px;
    }
    .rr-title{
      font-weight: 900;
      color:#0f172a;
      font-size: 14px;
      display:flex;
      align-items:center;
      gap:8px;
      margin-bottom: 8px;
    }
    .rr-sub{ font-size: 12px; color:#64748b; font-weight:700; }

    .rr-input{
      width:100%;
      background:#fff;
      border:1px solid #e2e8f0;
      border-radius: 14px;
      padding: 10px 12px;
      font-size: 13px;
      outline:none;
      transition: all .12s;
    }
    .rr-input:focus{
      border-color: rgba(14,165,233,.55);
      box-shadow: 0 0 0 4px rgba(14,165,233,.12);
    }

    .btn-sm{
      display:inline-flex; align-items:center; gap:8px;
      padding: 9px 12px;
      border-radius: 14px;
      font-size: 12px;
      font-weight: 900;
      border:1px solid #e2e8f0;
      background: #fff;
      color:#0f172a;
      cursor:pointer;
      transition: all .12s;
    }
    .btn-sm:hover{ transform: translateY(-1px); box-shadow: 0 10px 18px rgba(2,8,23,.08); }

    .rr-footer{
      position: absolute;
      left:0; right:0; bottom:0;
      padding: 12px 12px;
      border-top: 1px solid #e2e8f0;
      background: rgba(255,255,255,.92);
      backdrop-filter: blur(10px);
      display:flex;
      gap:10px;
    }
    .btn-primary{
      flex:1;
      border:none;
      padding: 12px 14px;
      border-radius: 16px;
      font-weight: 900;
      color:#fff;
      background: linear-gradient(90deg, #006B9F, #04ADFF);
      cursor:pointer;
      transition: all .15s;
    }
    .btn-primary:hover{ transform: translateY(-1px); box-shadow:0 12px 22px rgba(0,107,159,.28); }
    .btn-danger{
      width: 46px;
      border:1px solid #e2e8f0;
      background:#fff;
      border-radius: 16px;
      cursor:pointer;
    }

    .rr-hidden{ display:none; }

    /* table parts */
    .rr-parts-row{
      display:grid;
      grid-template-columns: 1.2fr .6fr .7fr auto;
      gap:8px;
      align-items:center;
      padding: 8px;
      border:1px dashed #e2e8f0;
      border-radius: 14px;
      background:#f8fafc;
    }
    .rr-mini{
      font-size: 12px;
      font-weight: 900;
      color:#0f172a;
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

  <!-- ✅ Overlay + Drawer -->
  <div id="rrOverlay" class="rr-overlay" onclick="closeDrawer()"></div>

  <aside id="rrDrawer" class="rr-drawer">
    <div class="rr-drawer-head">
      <div class="flex flex-col">
        <div class="flex items-center gap-2">
          <div class="rr-chip"><i data-lucide="hash" class="w-4 h-4"></i><span id="dwCaseId">-</span></div>
          <div class="rr-chip"><i data-lucide="activity" class="w-4 h-4"></i><span id="dwStatus">-</span></div>
        </div>
        <div class="mt-2">
          <div class="text-[14px] font-black text-slate-900 leading-tight" id="dwTitle">รายละเอียดเคส</div>
          <div class="rr-sub" id="dwSub">ผู้แจ้ง / สถานที่ / วันที่</div>
        </div>
      </div>

      <button class="btn-danger" onclick="closeDrawer()" title="ปิด">
        <div class="w-[46px] h-[42px] flex items-center justify-center">
          <i data-lucide="x" class="w-5 h-5 text-slate-600"></i>
        </div>
      </button>
    </div>

    <!-- Tabs: แก้ไขเคส / รับงาน / ปิดงาน -->
    <div class="rr-tabs">
      <div class="rr-tab active" data-tab="edit" onclick="drawerTab('edit')">แก้ไขเคส</div>
      <div class="rr-tab" data-tab="accept" onclick="drawerTab('accept')">รับงาน</div>
      <div class="rr-tab" data-tab="close" onclick="drawerTab('close')">ปิดงาน</div>
    </div>

    <div class="rr-drawer-body">

      <!-- TAB: Edit -->
      <div id="tab-edit" class="drawer-pane">
        <div class="rr-section">
          <div class="rr-title"><i data-lucide="file-pen-line" class="w-4 h-4"></i>แก้ไขข้อมูลแจ้งซ่อม</div>
          <label class="rr-sub">อาการเสีย/ปัญหา</label>
          <textarea id="edit_problem" class="rr-input mt-2" rows="4" placeholder="รายละเอียดปัญหา..."></textarea>

          <div class="grid grid-cols-2 gap-2 mt-3">
            <div>
              <label class="rr-sub">อาคาร</label>
              <input id="edit_building" class="rr-input mt-2" placeholder="อาคาร...">
            </div>
            <div>
              <label class="rr-sub">ห้อง</label>
              <input id="edit_room" class="rr-input mt-2" placeholder="ห้อง...">
            </div>
          </div>
        </div>

        <div class="rr-section">
          <div class="rr-title"><i data-lucide="image" class="w-4 h-4"></i>รูปประกอบ</div>
          <div class="rr-sub mb-2">ตัวอย่าง: คุณค่อยผูกกับ upload จริงทีหลัง</div>
          <input type="file" multiple accept="image/*" class="rr-input">
        </div>
      </div>

      <!-- TAB: Accept -->
      <div id="tab-accept" class="drawer-pane rr-hidden">
        <div class="rr-section">
          <div class="rr-title"><i data-lucide="user-check" class="w-4 h-4"></i>รับงาน / มอบหมาย</div>

          <div class="grid grid-cols-2 gap-2">
            <div>
              <label class="rr-sub">ช่างผู้รับผิดชอบ</label>
              <input id="acc_tech" class="rr-input mt-2" placeholder="เช่น ช่างส่วนกลาง">
            </div>
            <div>
              <label class="rr-sub">ความเร่งด่วน</label>
              <select id="acc_priority" class="rr-input mt-2">
                <option value="low">ต่ำ</option>
                <option value="mid" selected>ปานกลาง</option>
                <option value="high">สูง</option>
                <option value="critical">เร่งด่วนมาก</option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2 mt-3">
            <div>
              <label class="rr-sub">วันที่เข้าดำเนินการ</label>
              <input type="date" id="acc_date" class="rr-input mt-2">
            </div>
            <div>
              <label class="rr-sub">เวลาเข้าดำเนินการ</label>
              <input type="time" id="acc_time" class="rr-input mt-2">
            </div>
          </div>

          <div class="mt-3">
            <label class="rr-sub">หมายเหตุรับงาน</label>
            <textarea id="acc_note" class="rr-input mt-2" rows="3" placeholder="หมายเหตุ..."></textarea>
          </div>
        </div>
      </div>

      <!-- TAB: Close (✅ รวมอะไหล่ไว้ในปิดงาน) -->
      <div id="tab-close" class="drawer-pane rr-hidden">

        <div class="rr-section">
          <div class="rr-title"><i data-lucide="clipboard-check" class="w-4 h-4"></i>สรุปการปิดงาน</div>
          <label class="rr-sub">สรุปผล/วิธีแก้ไข</label>
          <textarea id="close_summary" class="rr-input mt-2" rows="4" placeholder="สรุปการซ่อม..."></textarea>

          <div class="grid grid-cols-2 gap-2 mt-3">
            <div>
              <label class="rr-sub">วันที่ปิดงาน</label>
              <input type="date" id="close_date" class="rr-input mt-2">
            </div>
            <div>
              <label class="rr-sub">เวลาปิดงาน</label>
              <input type="time" id="close_time" class="rr-input mt-2">
            </div>
          </div>
        </div>

        <div class="rr-section">
          <div class="rr-title w-full justify-between">
            <div class="flex items-center gap-2">
              <i data-lucide="package" class="w-4 h-4"></i>อะไหล่ที่ใช้
            </div>
            <button class="btn-sm" onclick="addPartRow()">
              <i data-lucide="plus" class="w-4 h-4"></i>เพิ่มอะไหล่
            </button>
          </div>

          <div id="partsWrap" class="space-y-2">
            <!-- rows -->
          </div>

          <div class="mt-3 flex items-center justify-between">
            <div class="rr-sub">รวมค่าอะไหล่</div>
            <div class="rr-mini"><span id="partsTotal">0.00</span> บาท</div>
          </div>
        </div>

        <div class="rr-section">
          <div class="rr-title"><i data-lucide="camera" class="w-4 h-4"></i>รูปหลังซ่อม</div>
          <input type="file" multiple accept="image/*" class="rr-input">
        </div>

        <div class="rr-section">
          <div class="rr-title"><i data-lucide="coins" class="w-4 h-4"></i>ค่าใช้จ่ายอื่นๆ</div>
          <input type="number" id="extra_cost" class="rr-input" placeholder="0.00">
        </div>
      </div>

    </div>

    <!-- Footer action -->
    <div class="rr-footer">
      <button class="btn-primary" onclick="saveDrawer()">บันทึก</button>
      <button class="btn-danger" onclick="closeDrawer()" title="ปิด">
        <div class="w-[46px] h-[46px] flex items-center justify-center">
          <i data-lucide="x" class="w-5 h-5 text-slate-600"></i>
        </div>
      </button>
    </div>
  </aside>

<script>
  let gridApi;
  const ag_id = <?php echo $sess_user_agency_es ?>;

  const escapeHtml = (s) => (s ?? '').toString()
    .replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
    .replaceAll('"','&quot;').replaceAll("'","&#039;");

  // =========================
  // ✅ AG Grid (ใช้ datasource เดิมคุณได้เลย)
  // =========================
  const columnDefs = [
    { headerName:"วันที่/เวลา", field:"report_date", width:160, pinned:"left",
      cellRenderer:(p)=> {
        const d = p.data?.report_date || '';
        const t = p.data?.report_time || '';
        return `<div class="leading-tight py-2">
          <div class="font-bold text-slate-900">${escapeHtml(d)}</div>
          <div class="text-[11px] text-slate-400 font-semibold">${escapeHtml(t)}</div>
        </div>`;
      }
    },
    { headerName:"ผู้แจ้ง", field:"name", width:200 },
    { headerName:"อาการ/ปัญหา", field:"problem_detail", flex:1 },
    { headerName:"สถานะ", field:"status", width:140 },
    {
      headerName:"จัดการ", width:110, pinned:"right", sortable:false, filter:false,
      cellRenderer:(p)=>{
        const id = p.data?.id;
        setTimeout(() => lucide.createIcons(), 0);
        return `<div class="flex items-center gap-1 h-full py-2">
          <button onclick="openDrawer(${id})" class="p-2 hover:bg-sky-50 text-sky-600 rounded-xl transition-colors" title="จัดการเคส">
            <i data-lucide="panel-right-open" class="w-4 h-4"></i>
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
    onRowDataUpdated: () => lucide.createIcons(),
    onGridReady: (params) => { gridApi = params.api; loadGrid(); }
  };

  function loadGrid(){
    // ✅ ตัวอย่าง datasource (คุณใช้ของเดิมได้)
    const datasource = {
      getRows: async (params) => {
        // NOTE: ตรงนี้คุณใช้ handle_repair_requests.php ของเดิมได้เลย
        // ผมทำ mock ให้เห็น UI ก่อน
        const rows = [];
        for(let i=params.request.startRow; i<params.request.endRow; i++){
          rows.push({
            id: i+1,
            report_date: '2026-01-05',
            report_time: '15:34',
            name: 'ทดสอบ-'+(i+1),
            problem_detail: 'ปัญหาทดสอบ #' + (i+1),
            status: (i%3===0?'pending':(i%3===1?'inprogress':'completed')),
            building_name: 'อาคาร A',
            room_name: '101'
          });
        }
        params.success({ rowData: rows, rowCount: 100049 });
        document.getElementById('row-count').innerText = '100049';
        setTimeout(()=>lucide.createIcons(), 10);
      }
    };
    gridApi.setGridOption('serverSideDatasource', datasource);
  }

  function onSearchChanged(){ if (gridApi) gridApi.refreshServerSide({ purge:true }); }
  function refreshGrid(){ if (gridApi) gridApi.refreshServerSide({ purge:true }); }

  // =========================
  // ✅ Drawer logic
  // =========================
  let currentCaseId = null;

  function openDrawer(id){
    currentCaseId = id;

    // TODO: ดึงข้อมูลจริงจาก API แล้ว set ลง input
    document.getElementById('dwCaseId').textContent = id;
    document.getElementById('dwStatus').textContent = 'รอดำเนินการ';
    document.getElementById('dwTitle').textContent = 'เคส #' + id;
    document.getElementById('dwSub').textContent = 'ผู้แจ้ง: ทดสอบ | อาคาร A | ห้อง 101';

    // reset parts
    document.getElementById('partsWrap').innerHTML = '';
    updatePartsTotal();

    // show
    document.getElementById('rrOverlay').classList.add('show');
    document.getElementById('rrDrawer').classList.add('show');

    // default tab
    drawerTab('edit');
    setTimeout(()=>lucide.createIcons(), 10);
  }

  function closeDrawer(){
    document.getElementById('rrOverlay').classList.remove('show');
    document.getElementById('rrDrawer').classList.remove('show');
  }

  function drawerTab(tab){
    // tabs active
    document.querySelectorAll('.rr-tab').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.drawer-pane').forEach(el => el.classList.add('rr-hidden'));

    const t = document.querySelector(`.rr-tab[data-tab="${tab}"]`);
    if(t) t.classList.add('active');

    const pane = document.getElementById('tab-' + tab);
    if(pane) pane.classList.remove('rr-hidden');

    setTimeout(()=>lucide.createIcons(), 10);
  }

  // =========================
  // ✅ Parts inside "ปิดงาน"
  // =========================
  function addPartRow(){
    const wrap = document.getElementById('partsWrap');
    const rid = 'p_' + Math.random().toString(16).slice(2);

    const row = document.createElement('div');
    row.className = 'rr-parts-row';
    row.id = rid;
    row.innerHTML = `
      <input class="rr-input" placeholder="ชื่ออะไหล่ / รหัส" oninput="updatePartsTotal()">
      <input class="rr-input" type="number" min="1" value="1" oninput="updatePartsTotal()">
      <input class="rr-input" type="number" min="0" step="0.01" value="0" oninput="updatePartsTotal()">
      <button class="btn-sm" onclick="removePartRow('${rid}')">
        <i data-lucide="trash-2" class="w-4 h-4"></i>
      </button>
    `;
    wrap.appendChild(row);
    updatePartsTotal();
    setTimeout(()=>lucide.createIcons(), 10);
  }

  function removePartRow(rid){
    const el = document.getElementById(rid);
    if(el) el.remove();
    updatePartsTotal();
  }

  function updatePartsTotal(){
    const wrap = document.getElementById('partsWrap');
    const rows = wrap.querySelectorAll('.rr-parts-row');
    let total = 0;

    rows.forEach(r=>{
      const inputs = r.querySelectorAll('input');
      const qty = parseFloat(inputs[1]?.value || '0');
      const price = parseFloat(inputs[2]?.value || '0');
      total += (qty * price);
    });

    document.getElementById('partsTotal').textContent = total.toFixed(2);
  }

  // =========================
  // ✅ Save (ตัวอย่าง)
  // =========================
  function saveDrawer(){
    // เก็บข้อมูลจากแท็บไหนก็ได้
    const payload = {
      id: currentCaseId,
      edit: {
        problem: document.getElementById('edit_problem').value,
        building: document.getElementById('edit_building').value,
        room: document.getElementById('edit_room').value,
      },
      accept: {
        tech: document.getElementById('acc_tech').value,
        priority: document.getElementById('acc_priority').value,
        date: document.getElementById('acc_date').value,
        time: document.getElementById('acc_time').value,
        note: document.getElementById('acc_note').value,
      },
      close: {
        summary: document.getElementById('close_summary').value,
        close_date: document.getElementById('close_date').value,
        close_time: document.getElementById('close_time').value,
        extra_cost: document.getElementById('extra_cost').value,
        parts_total: document.getElementById('partsTotal').textContent,
        parts: collectParts()
      }
    };

    // TODO: ส่งไป API จริง (handle_repair_requests.php?action=save_drawer)
    console.log('SAVE PAYLOAD', payload);

    Swal.fire({
      icon:'success',
      title:'บันทึก (ตัวอย่าง)',
      html:`<div class="text-left text-sm">ดู payload ใน console ได้เลยครับ</div>`,
      confirmButtonText:'โอเค'
    });
  }

  function collectParts(){
    const wrap = document.getElementById('partsWrap');
    const rows = wrap.querySelectorAll('.rr-parts-row');
    const parts = [];

    rows.forEach(r=>{
      const inputs = r.querySelectorAll('input');
      parts.push({
        name: inputs[0]?.value || '',
        qty: parseFloat(inputs[1]?.value || '0'),
        price: parseFloat(inputs[2]?.value || '0')
      });
    });
    return parts;
  }

  window.onload = () => {
    const gridDiv = document.querySelector('#myGrid');
    gridApi = agGrid.createGrid(gridDiv, gridOptions);
    lucide.createIcons();
  };
</script>

</body>
</html>
