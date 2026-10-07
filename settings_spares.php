<?php 
@session_start();
// ตรวจสอบ Session ตามเดิม
include "config_ctrl/checksession.php"; 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการมิเตอร์</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap');
        
        :root {
            --color-bg: #f8fafc;
            --color-primary: #006B9F;
            --color-primary-dark: #004a6f;
        }

        body { font-family: 'Prompt', sans-serif; }
        .bg-primary { background-color: var(--color-primary); }
        .bg-primary-dark { background-color: var(--color-primary-dark); }

        /* Handsontable Overrides */
        .handsontable { font-family: 'Prompt', sans-serif; font-size: 13px; }
        .handsontable th { background-color: #f1f5f9 !important; font-weight: 600 !important; color: #334155 !important; }
        .checker-header { margin: 0; cursor: pointer; width: 14px; height: 14px; }

        /* ปรับแต่ง Scrollbar สำหรับ Handsontable */
        .handsontable .wtHolder::-webkit-scrollbar {
            height: 4px; /* ปรับความสูงของ Scrollbar แนวนอนให้เล็กลง (ค่าเริ่มต้นมักจะ 15-17px) */
            width: 4px;  /* ปรับความกว้างของ Scrollbar แนวตั้ง */
        }

        /* พื้นหลังของแถบ Scrollbar */
        .handsontable .wtHolder::-webkit-scrollbar-track {
            background: #f8fafc; /* สีพื้นหลังอ่อนๆ ให้กลืนกับ UI */
            border-radius: 2px;
        }

        /* ตัวจับ Scrollbar (Thumb) */
        .handsontable .wtHolder::-webkit-scrollbar-thumb {
            background: #cbd5e1; /* สีของตัวเลื่อน (สีเทาอ่อน) */
            border-radius: 2px;  /* ทำให้ขอบมน */
        }

        /* เมื่อเอาเมาส์ไปชี้ที่ตัวจับ Scrollbar */
        .handsontable .wtHolder::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; /* สีเข้มขึ้นเมื่อ Hover */
        }
        
        /* Cell States */
        .cell-dirty { background-color: #fffbeb !important; color: #92400e !important; }
        .cell-new { background-color: #f0fdf4 !important; color: #166534 !important; }
        .cell-error { background-color: #fee2e2 !important; color: #b91c1c !important; }
        
        /* Status Styles */
        .status-active { color: #0369a1; font-weight: 600; }
        .status-inactive { color: #64748b; font-weight: 600; }
        
        /* UI Components */
        .page-link {
            padding: 6px 12px; border: 1px solid #e2e8f0; background: white; color: #64748b;
            border-radius: 6px; cursor: pointer; transition: all 0.2s; font-size: 13px; font-weight: 500;
        }
        .page-link:hover { background: #f1f5f9; color: var(--color-primary); }
        .page-link.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }
        .page-link:disabled { opacity: 0.5; cursor: not-allowed; }
        
    </style>
</head>
<body class="h-screen flex flex-col overflow-hidden md:p-6 p-2 bg-slate-50">

    <header class="flex flex-col md:flex-row items-start md:items-center justify-between shrink-0 z-20 pb-4 gap-4">
        <div class="w-full md:w-auto flex items-center bg-white border border-slate-200 rounded-lg overflow-hidden px-3 h-10 focus-within:ring-2 focus-within:ring-sky-100 transition-all shadow-sm">
            <i class="fa-solid fa-search text-slate-400"></i>
            <input type="text" id="search-input" placeholder="ค้นหาข้อมูล..." class="w-full md:w-80 h-full ml-2 text-sm text-slate-600 focus:outline-none placeholder:text-slate-300">
        </div>
        
        <div class="w-full md:w-auto flex flex-wrap items-center gap-2 md:gap-3">
            <div id="change-counter" class="hidden px-3 py-1 bg-amber-50 text-amber-700 text-[11px] font-bold rounded-full border border-amber-200 shadow-sm">
                <i class="fa-solid fa-circle-exclamation mr-1"></i> รอการบันทึก: <span id="count-num">0</span>
            </div>
            
            <div class="flex items-center bg-white border border-slate-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-sky-100 transition-all h-10 shadow-sm">
                <input type="number" id="row-count-input" value="1" min="1" max="50" class="w-12 h-full text-center text-sm font-semibold text-slate-600 focus:outline-none">
                <button id="btn-add-row" class="h-full px-3 md:px-4 bg-slate-50 border-l border-slate-200 text-slate-600 text-sm font-medium hover:bg-sky-50 hover:text-sky-600 transition-all whitespace-nowrap">
                    <i class="fa-solid fa-plus text-sky-500 mr-1"></i> <span class="hidden sm:inline">เพิ่มแถว</span>
                </button>
            </div>

            <button id="btn-export-excel" class="h-10 px-3 md:px-4 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 transition-all shadow-md flex items-center gap-2 whitespace-nowrap">
                <i class="fa-solid fa-file-excel"></i> <span class="hidden sm:inline">Export</span>
            </button>

            <button id="btn-save" class="h-10 px-3 md:px-5 bg-primary text-white rounded-lg text-sm font-semibold hover:bg-primary-dark transition-all shadow-md flex items-center gap-2 whitespace-nowrap flex-1 md:flex-none justify-center">
                <i class="fa-solid fa-save"></i> <span>บันทึกข้อมูล</span>
            </button>
        </div>
    </header>

    <div class="flex-1 flex flex-col min-h-0 overflow-hidden bg-white rounded-xl shadow-sm border border-slate-200 relative">
        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center text-sm gap-2">
            <div class="flex items-center gap-4">
                <button id="btn-delete-selected" class="hidden text-red-500 hover:text-red-700 font-semibold text-xs bg-red-50 px-2 py-1 rounded border border-red-100">
                    <i class="fa-solid fa-trash-can mr-1"></i> ลบรายการที่เลือก (<span id="selected-count">0</span>)
                </button>
            </div>
            <div class="text-slate-500 text-xs font-medium">
                รายการที่ <span id="start-range" class="text-slate-800">0</span> - <span id="end-range" class="text-slate-800">0</span> จากทั้งหมด <span id="total-rows" class="text-slate-800">0</span>
            </div>
        </div>

        <div id="hot-display" class="w-full flex-1 overflow-hidden z-0"></div>
        
        <div class="px-4 py-3 bg-white border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-start">
                <span class="text-xs text-slate-500 font-medium">แสดงหน้าละ</span>
                <select id="page-size" class="text-xs border border-slate-200 rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-sky-100">
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="150">150</option>
                    <option value="200">200</option>
                </select>
            </div>
            <div id="pagination-nav" class="flex flex-wrap justify-center gap-1 w-full sm:w-auto"></div>
        </div>
    </div>

    <div class="m-4 flex flex-col sm:flex-row justify-between items-center text-[11px] text-slate-400 font-medium px-1 uppercase tracking-wider gap-2">
        <span class="flex items-center gap-2 text-center sm:text-left"><i class="fa-solid fa-info-circle"></i> ดับเบิลคลิกที่เซลล์เพื่อแก้ไขข้อมูล</span>
        <div class="flex gap-4 sm:gap-6">
            <span class="flex items-center gap-2"><span class="w-3 h-3 bg-amber-100 border border-amber-200 rounded-sm"></span> มีการแก้ไข</span>
            <span class="flex items-center gap-2"><span class="w-3 h-3 bg-green-100 border border-green-200 rounded-sm"></span> รายการใหม่</span>
        </div>
    </div>

    <div style="display: none;">
        <div id="loc_trigger" onClick="LocationSelector.openModal()">
            <div id="loc_placeholder">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-sky-100 flex items-center justify-center text-sky-600 shrink-0">
                        <i data-lucide="map-pin" class="w-5 h-5 fa-solid fa-map-pin"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-extrabold text-slate-700">ระบุตำแหน่งที่แจ้งซ่อม</p>
                        <p class="text-[10px] text-slate-400 font-semibold">กดเพื่อเลือก อาคาร / สาขา → ชั้น → ห้อง</p>
                    </div>
                    <div class="text-slate-300">
                        <i data-lucide="chevron-right" class="w-5 h-5 fa-solid fa-chevron-right"></i>
                    </div>
                </div>
            </div>

            <div id="loc_selected" class="hidden">
                <div class="flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1.5">
                            <i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400 fa-solid fa-building"></i> Building
                        </span>
                        <span class="text-[10px] font-extrabold text-emerald-600 bg-emerald-50 border border-emerald-100 rounded-full px-2.5 py-1 inline-flex items-center gap-1.5">
                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5 fa-solid fa-check-circle"></i> Selected
                        </span>
                    </div>
                    <span id="disp_building" class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-4 h-4 text-sky-600 fa-solid fa-map-pin"></i>
                        <span class="min-w-0 break-words"></span>
                    </span>
                    <div class="flex flex-wrap gap-2 mt-1">
                        <span class="px-2 py-1 bg-blue-50 text-sky-700 text-[10px] font-extrabold rounded-lg border border-blue-100 inline-flex items-center gap-1.5">
                            <i data-lucide="layers" class="w-3.5 h-3.5 fa-solid fa-layer-group"></i>
                            <span id="disp_floor"></span>
                        </span>
                        <span id="disp_room" class="px-2 py-1 bg-slate-50 text-slate-600 text-[10px] font-extrabold rounded-lg border border-slate-200 inline-flex items-center gap-1.5">
                            <i data-lucide="door-open" class="w-3.5 h-3.5 fa-solid fa-door-open"></i>
                            -
                        </span>
                    </div>
                </div>
            </div>

            <input type="hidden" name="building" id="in_building" required>
            <input type="hidden" name="floor" id="in_floor" required>
            <input type="hidden" name="room" id="in_room" required>
            
            <input type="hidden" name="area_id" id="in_area_id" required>
            <input type="hidden" name="ac_id" id="in_ac_id" required>
            <input type="hidden" name="ar_id" id="in_ar_id" required>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script src="js/location-selector.js"></script>

    <script>
        // --- State Management ---
        let allData = []; 
        let currentPage = 1;
        let pageSize = 20; 
        let searchTimeout = null;
        let dirtyCells = new Map(); 
        let newRowIds = new Set();  
        let deletedIds = [];        
        let tempIdCounter = -1; 
        let cachedNewRows = []; 
        let cachedDirtyRows = new Map(); 
        let errorRows = new Set();

        let repairSystemsMap = {};        
        let repairSystemsReverseMap = {};
        let repairSystemNames = [];

        let warehouseMap = {};       
        let warehouseReverseMap = {};
        let warehouseNames = [];
        
        let activeEditRowIndex = null; // เก็บ index แถวที่กำลังเลือกสถานที่

        const AG_ID = '<?php echo isset($sess_user_agency_es) ? $sess_user_agency_es : ""; ?>';
        const USER_ID = '<?php echo isset($sess_user_id) ? $sess_user_id : ""; ?>';

        const container = document.getElementById('hot-display');
        const counterEl = document.getElementById('change-counter');
        const countNumEl = document.getElementById('count-num');
        const paginationNav = document.getElementById('pagination-nav');

        // ฟังก์ชันดึงข้อมูลปัจจุบันบนหน้าจอยัดลง Cache ก่อนเปลี่ยนหน้า
        function preserveCurrentState() {
            allData.forEach(row => {
                if (newRowIds.has(row.id)) {
                    const existingIdx = cachedNewRows.findIndex(r => r.id === row.id);
                    if(existingIdx >= 0) cachedNewRows[existingIdx] = {...row};
                    else cachedNewRows.push({...row});
                } else if (dirtyCells.has(row.id)) {
                    cachedDirtyRows.set(row.id, {...row});
                }
            });
        }

        // --- API Calls ---
        async function loadRepairSystems() {
            try {
                const response = await axios.get(`handle_repair_system.php?action=get_all&ag_id=${AG_ID}`);
                if (response.data) {
                    // โครงสร้าง API อาจจะเป็น response.data ตรงๆ หรือ response.data.data
                    const dataList = response.data.data || response.data; 
                    
                    dataList.forEach(item => {
                        // *หมายเหตุ: หากชื่อคอลัมน์ในตาราง tb_repair_system ไม่ใช่ rps_name ให้เปลี่ยนให้ตรงกับฐานข้อมูลของคุณ
                        repairSystemsMap[item.rps_id] = item.rps_name; 
                        repairSystemsReverseMap[item.rps_name] = item.rps_id;
                        repairSystemNames.push(item.rps_name);
                    });
                }
            } catch (error) {
                console.error('Error fetching repair systems:', error);
            }
        }

        const loadWarehouses = async (pdId = null) => {
            try {
                let url = `handle_wh_stock.php?action=get_all_setting&ag_id=${AG_ID}`;
                
                if (pdId) {
                    url += `&pd_id=${pdId}`;
                }

                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    if (!pdId) { 
                        result.data.forEach(item => {
                            warehouseMap[item.id] = item.wh_name;
                            warehouseReverseMap[item.wh_name] = item.id;
                            if (!warehouseNames.includes(item.wh_name)) {
                                warehouseNames.push(item.wh_name);
                            }
                        });
                    }
                    return result.data; 
                } else {
                    console.error('Error loading warehouses:', result.message);
                    return [];
                }
            } catch (error) {
                console.error('Fetch error:', error);
                return [];
            }
        };
        
        async function fetchSparesData(page = 1) {
            preserveCurrentState();

            currentPage = page;
            const searchVal = document.getElementById('search-input').value.trim();
            const offset = (currentPage - 1) * pageSize;

            try {
                const response = await axios.get(`handle_spares.php?action=get_all&ag_id=${AG_ID}&search=${encodeURIComponent(searchVal)}&limit=${pageSize}&offset=${offset}`);
                
                if (response.data && response.data.success) {
                    let serverData = response.data.data.map(item => {
                        return {
                            ...item,
                            pd_rps_id: repairSystemsMap[item.pd_rps_id] || item.pd_rps_id,
                            wh_id: warehouseMap[item.wh_id] || item.wh_id,
                            selected: false
                        };
                    });

                    // ตรวจสอบข้อมูลที่แก้ไขค้างไว้ (Dirty)
                    serverData = serverData.map(row => {
                        return cachedDirtyRows.has(row.pd_id) ? cachedDirtyRows.get(row.pd_id) : row;
                    });
                    
                    // --- แก้ไขตรงนี้ ---
                    // กรอง cachedNewRows เพื่อเอาเฉพาะรายการที่ยังไม่มีใน serverData (ป้องกันการซ้ำ)
                    const filteredNewRows = cachedNewRows.filter(nr => 
                        !serverData.some(sr => sr.pd_id === nr.pd_id)
                    );
                    
                    allData = [...filteredNewRows, ...serverData];
                    // ------------------
                    
                    const totalRows = response.data.total || 0;
                    renderServerPagination(totalRows, offset); 
                    
                    hot.loadData(JSON.parse(JSON.stringify(allData)));
                    updateCounter();
                }
            } catch (error) {
                console.error('Error fetching spares data:', error);
            }
        }

        function renderServerPagination(totalRows, currentOffset) {
            const totalPages = Math.ceil(totalRows / pageSize) || 1;
            if (currentPage > totalPages) currentPage = totalPages || 1;
            
            document.getElementById('total-rows').innerText = totalRows;
            document.getElementById('start-range').innerText = totalRows ? currentOffset + 1 : 0;
            document.getElementById('end-range').innerText = Math.min(currentOffset + pageSize, totalRows);
            
            paginationNav.innerHTML = '';
            const createBtn = (content, targetPage, active = false, disabled = false) => {
                const btn = document.createElement('button');
                btn.className = `page-link ${active ? 'active' : ''}`;
                btn.innerHTML = content; btn.disabled = disabled;
                btn.onclick = () => { fetchSparesData(targetPage); };
                return btn;
            };
            
            paginationNav.appendChild(createBtn('<i class="fa-solid fa-chevron-left"></i>', currentPage - 1, false, currentPage === 1));
            
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);
            
            if (startPage > 1) paginationNav.appendChild(createBtn('1', 1, false));
            if (startPage > 2) {
                let ellipsis = document.createElement('span');
                ellipsis.className = 'px-2 py-1 text-slate-400'; ellipsis.innerText = '...';
                paginationNav.appendChild(ellipsis);
            }

            for (let i = startPage; i <= endPage; i++) {
                paginationNav.appendChild(createBtn(i, i, i === currentPage));
            }

            if (endPage < totalPages - 1) {
                let ellipsis = document.createElement('span');
                ellipsis.className = 'px-2 py-1 text-slate-400'; ellipsis.innerText = '...';
                paginationNav.appendChild(ellipsis);
            }
            if (endPage < totalPages) paginationNav.appendChild(createBtn(totalPages, totalPages, false));

            paginationNav.appendChild(createBtn('<i class="fa-solid fa-chevron-right"></i>', currentPage + 1, false, currentPage === totalPages));
        }

        // เก็บเลขที่ "จองไว้" ของแต่ละ prefix แบบ synchronous (ไม่พึ่งการอ่านค่าจากตาราง)
        // เพราะ JS เป็น single-thread การจองด้วยตัวแปรกลางนี้จะ atomic เสมอ
        // ไม่ว่า request check_info ของแถวไหนจะตอบกลับมาก่อน-หลังก็ตาม
        const reservedCodeCounters = {}; // เช่น { 'ELC-BAM': 7 }

        function reserveNextCode(prefix, apiNum, padLength) {
            const baseline = Math.max(reservedCodeCounters[prefix] ?? 0, apiNum - 1);
            const next = baseline + 1;
            reservedCodeCounters[prefix] = next;
            return prefix + '-' + String(next).padStart(padLength, '0');
        }

        async function checkProductName(value, rowVisual) {
            if (!value || value.trim() === '') return;

            try {
                // 1. เรียก API ตรวจสอบข้อมูล
                const response = await axios.get(`handle_spares.php?action=check_info&name=${encodeURIComponent(value.trim())}&ag_id=${AG_ID}`);
                
                if (response.data && response.data.exists) {
                    const info = response.data.data;

                    // 2. แจ้งเตือนผู้ใช้
                    Swal.fire({
                        title: 'พบข้อมูลสินค้าเดิม!',
                        html: `ชื่อนี้มีอยู่ในระบบแล้วภายใต้รหัส <b>${info.pd_gen_code}</b><br>ระบบจะดึงข้อมูลเดิมมาเติมให้ในตาราง`,
                        icon: 'info',
                        timer: 2500,
                        showConfirmButton: false
                    });

                    // 3. เตรียมการ Map ข้อมูลประเภทงาน (ถ้าในตารางแสดงเป็นชื่อภาษาไทย)
                    // หาก API ส่ง rps_name มาให้แล้ว ให้ใช้ค่านั้นได้เลย
                    const rpsValue = info.rps_name || (repairSystemsReverseMap[info.pd_rps_id] || info.pd_rps_id);

                    // 4. อัปเดตข้อมูลทุก Column พร้อมกันในครั้งเดียว
                    // โครงสร้าง: [row, prop, value]
                    const changes = [
                        [rowVisual, 'pd_rps_id', rpsValue],
                        [rowVisual, 'pd_model', info.pd_model],
                        [rowVisual, 'pd_price', info.pd_price],
                        [rowVisual, 'pd_unit', info.pd_unit],
                        [rowVisual, 'pd_details', info.pd_details],
                        [rowVisual, 'pd_brand', info.pd_brand],
                        [rowVisual, 'pd_gen_code', info.pd_gen_code]
                    ];

                    // ส่งคำสั่งอัปเดตพร้อมระบุ source เป็น 'apiUpdate'
                    hot.setDataAtRowProp(changes, 'apiUpdate');

                }
            } catch (error) {
                console.error("Error checking product name:", error);
            }
        }

        // --- Renderers ---
        function customCellRenderer(instance, td, row, col, prop, value, cellProperties) {
            // ให้แสดงผลตามประเภทของเซลล์ (เพื่อรักษา Format ตัวเลข/Dropdown ไว้)
            if (cellProperties.type === 'numeric') {
                Handsontable.renderers.NumericRenderer.apply(this, arguments);
            } else if (cellProperties.type === 'dropdown') {
                Handsontable.renderers.AutocompleteRenderer.apply(this, arguments);
            } else {
                Handsontable.renderers.TextRenderer.apply(this, arguments);
            }

            const rowData = instance.getSourceDataAtRow(row);
            if (!rowData) return td;

            // 1. ตรวจสอบว่าเป็น "แถวใหม่" หรือไม่ (เปลี่ยนจาก id เป็น pd_id)
            if (rowData.pd_id && newRowIds.has(rowData.pd_id)) {
                td.classList.add('cell-new');
            } 
            // 2. ตรวจสอบว่า "เซลล์นี้ถูกแก้ไข" หรือไม่
            else if (rowData.pd_id && dirtyCells.has(rowData.pd_id) && dirtyCells.get(rowData.pd_id).has(prop)) {
                td.classList.add('cell-dirty');
            }

            // 3. จัดการสีพื้นหลังให้ช่องที่ ReadOnly (เช่น รหัสสินค้า หรือ Qty ของเดิม)
            if (cellProperties.readOnly) {
                td.classList.add('bg-slate-50', 'text-slate-400');
            }

            return td;
        }

        function statusCheckboxRenderer(instance, td, row, col, prop, value, cellProperties) {
            Handsontable.renderers.CheckboxRenderer.apply(this, arguments);
            td.classList.remove('cell-dirty', 'cell-new', 'status-active', 'status-inactive');
            
            const rowData = instance.getSourceDataAtRow(row);
            if (!rowData) return td;

            if (newRowIds.has(rowData.id)) { td.classList.add('cell-new'); } 
            else if (dirtyCells.has(rowData.id) && dirtyCells.get(rowData.id).has(prop)) { td.classList.add('cell-dirty'); }

            td.style.display = 'flex'; 
            td.style.alignItems = 'center'; 
            td.style.justifyContent = 'center'; 
            td.style.gap = '8px';
            
            // แปลงค่า "0" เป็น Active
            const isActive = (String(value) === '0');
            
            const label = document.createElement('span');
            label.className = isActive ? 'status-active text-[11px]' : 'status-inactive text-[11px]';
            label.innerText = isActive ? 'Active' : 'Inactive';
            
            const input = td.querySelector('input');
            if (input) input.className = 'w-3.5 h-3.5 cursor-pointer';

            // สำคัญ: ลบ Text Node (ข้อความ #bad-value# หรือ "0") ที่ Handsontable สร้างไว้ออก
            Array.from(td.childNodes).forEach(node => {
                if (node.nodeType === Node.TEXT_NODE) {
                    node.remove();
                }
            });

            td.appendChild(label);
            return td;
        }

        // --- Handsontable Init ---
        const hot = new Handsontable(container, {
            data: allData, 
            // 1. เปลี่ยน colHeaders ให้รับเป็นฟังก์ชัน เพื่อแทรก Checkbox Select All ไปที่คอลัมน์แรก
            colHeaders: function(col) {
                if (col === 0) {
                    const allChecked = allData.length > 0 && allData.every(row => row.selected === true || row.selected === 'true');
                    return `<input type="checkbox" class="checker-header cursor-pointer w-3.5 h-3.5" ${allChecked ? 'checked' : ''}>`;
                }
                const headers = [
                    'รหัสสินค้า', 
                    'ประเภทงาน', 
                    'ชื่อสินค้า', 
                    'รุ่น / ชนิด', 
                    'ราคา/หน่วย', 
                    'หน่วย', 
                    'จัดเก็บที่คลัง', 
                    'รายละเอียดสินค้า', 
                    'คงเหลือ (Qty)', 
                    'สูงสุด (Max)', 
                    'ขั้นต่ำ (Min)'
                ];
                return headers[col - 1];
            },
            // 2. กำหนดคอลัมน์ตามฐานข้อมูลอะไหล่
            columns: [
                { data: 'selected', type: 'checkbox', className: 'htCenter htMiddle' },
                { data: 'pd_gen_code', type: 'text', readOnly: true, className: 'htCenter htMiddle bg-slate-50 text-slate-500', width: 120, renderer: customCellRenderer }, 
                { data: 'pd_rps_id', type: 'dropdown', source: repairSystemNames, renderer: customCellRenderer, width: 220 }, 
                { data: 'pd_details_head', type: 'text', width: 200, renderer: customCellRenderer }, 
                { data: 'pd_model', type: 'text', className: 'htCenter htMiddle', width: 150, renderer: customCellRenderer }, 
                { data: 'pd_price', type: 'numeric', numericFormat: { pattern: '0,0.00' }, width: 100, renderer: customCellRenderer }, 
                { data: 'pd_unit', type: 'text', width: 80, className: 'htCenter htMiddle' , renderer: customCellRenderer }, 
                { 
                    data: 'wh_name', 
                    type: 'dropdown', 
                    width: 230, 
                    source: async function(query, process) {
                        // 1. ดึงข้อมูลแถวที่กำลังแก้ไขอยู่
                        const row = this.instance.getSelected()[0][0];
                        const pdId = this.instance.getDataAtRowProp(row, 'pd_id');
                        
                        // เพิ่ม: ดึงชื่อสินค้าของแถวนี้ เพื่อเช็คว่ากำลังเพิ่มคลังให้สินค้าตัวไหน
                        const currentProductName = this.instance.getDataAtRowProp(row, 'pd_details_head'); 

                        // 2. โหลดคลังทั้งหมดมาไว้ก่อน
                        const warehouses = await loadWarehouses(pdId);
                        let availableWarehouses = warehouses.map(w => w.wh_name);

                        // 3. ดึงข้อมูลทั้งหมดในตารางมาตรวจสอบ
                        const sourceData = this.instance.getSourceData();
                        
                        // 4. หาคลังที่ถูกเลือกไปแล้วสำหรับ "สินค้าตัวเดียวกัน" ในแถวอื่นๆ
                        const usedWarehouses = [];
                        sourceData.forEach((dataRow, index) => {
                            // ถ้าไม่ใช่แถวปัจจุบัน + มีการระบุคลังแล้ว + เป็นสินค้าชื่อเดียวกัน
                            if (index !== row && dataRow.wh_name && dataRow.pd_details_head === currentProductName) {
                                usedWarehouses.push(dataRow.wh_name);
                            }
                        });

                        // 5. กรองเอาเฉพาะรายชื่อคลังที่ "ยังไม่มี" ใน usedWarehouses
                        availableWarehouses = availableWarehouses.filter(wh => !usedWarehouses.includes(wh));

                        // 6. ส่งรายชื่อที่กรองแล้วให้ Dropdown แสดงผล
                        process(availableWarehouses);
                    }, 
                    renderer: customCellRenderer 
                },
                { data: 'pd_details', type: 'text', width: 250, renderer: customCellRenderer }, 
                { data: 'pd_qty', type: 'numeric', width: 100, renderer: customCellRenderer }, 
                { data: 'pd_qty_all', type: 'numeric', width: 100, renderer: customCellRenderer }, 
                { data: 'pd_qty_min', type: 'numeric', width: 100, renderer: customCellRenderer }, 
                { data: 'pd_id', type: 'text' },
                { data: 'ps_status', type: 'checkbox', checkedTemplate: '0', uncheckedTemplate: '1', renderer: statusCheckboxRenderer, className: 'htCenter htMiddle' }
            ],
            hiddenColumns: {
                columns: [12], // ซ่อนคอลัมน์ index 12 (pd_id)
                indicators: false
            },
            // 3. ควบคุม ReadOnly ไดนามิก (เฉพาะคอลัมน์ qty)
            cells: function(row, col, prop) {
                let cellProperties = {};
                if (prop === 'pd_qty') {
                    const rowData = this.instance.getSourceDataAtRow(row);
                    
                    // เช็คว่ามี pd_id และ "ต้องไม่อยู่ในรายการแถวที่เพิ่มใหม่ (newRowIds)"
                    if (rowData && rowData.pd_id && !newRowIds.has(rowData.pd_id)) {
                        cellProperties.readOnly = true;
                        cellProperties.className = 'bg-slate-50 text-slate-400 cursor-not-allowed htRight htMiddle';
                    } else {
                        // แถวที่เพิ่งกด "เพิ่ม" ใหม่ จะยังกรอกข้อมูลได้
                        cellProperties.readOnly = false;
                        cellProperties.className = 'htRight htMiddle';
                    }
                }
                return cellProperties;
            },
            stretchH: 'all', 
            rowHeaders: function(index) {
                return (currentPage - 1) * pageSize + index + 1;
            },
            height: '100%',
            licenseKey: 'non-commercial-and-evaluation', 
            viewportRowRenderingOffset: 9999,
            width: '100%',
            preventOverflow: 'horizontal',

            afterChange: function (changes, source) {
                if (source === 'loadData' || source === 'ObserveChanges.change') return;

                let selectionChanged = false; // ตัวแปรเช็คว่ามีการติ๊ก Checkbox เลือกแถวหรือไม่

                changes.forEach(([row, prop, oldValue, newValue]) => {
                    if (oldValue === newValue) return;

                    if (prop === 'pd_details_head' && newValue) {
                        checkProductName(newValue, row);
                    }

                    const rowData = hot.getSourceDataAtRow(row);
                    const target = allData.find(d => d.pd_id === rowData.pd_id && rowData.pd_id !== undefined);
                    
                    if(!target && rowData.pd_id) return;
                    
                    if(target) {
                        target[prop] = newValue;

                        // ตรวจสอบว่าถ้าเป็นการแก้ไขข้อมูลปกติ ค่อยบันทึกว่าเซลล์นี้ถูกแก้
                        if (!newRowIds.has(target.pd_id) && prop !== 'selected') {
                            if (!dirtyCells.has(target.pd_id)) dirtyCells.set(target.pd_id, new Set());
                            dirtyCells.get(target.pd_id).add(prop);
                        }
                    }

                    if (prop === 'selected') selectionChanged = true;

                    // ==========================================
                    // ส่วนที่ 1: เพิ่มโค้ดเช็คเพื่อยิง API ขอรหัสสินค้า
                    // ==========================================
                    if (prop === 'pd_details_head' || prop === 'pd_rps_id') {
                        const pdName = rowData.pd_details_head;
                        const pdRpsName = rowData.pd_rps_id; 

                        // ---------------------------------------------------------
                        // [เพิ่มใหม่] ตรวจจับ: บังคับกรอกชื่อสินค้าก่อนเลือกประเภทงาน
                        // ---------------------------------------------------------
                        if (prop === 'pd_rps_id' && newValue) {
                            if (!pdName || pdName.trim() === '') {
                                // แจ้งเตือนด้วย SweetAlert2 (หรือใช้ alert ปกติก็ได้ครับ)
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'คำเตือน',
                                    text: 'กรุณาระบุชื่อสินค้าก่อนเลือกประเภท',
                                    confirmButtonColor: '#3085d6',
                                    confirmButtonText: 'ตกลง'
                                });

                                // ล้างค่าประเภทงานที่ผู้ใช้เพิ่งเลือกออก เพื่อให้เลือกใหม่ทีหลัง
                                hot.setDataAtCell(row, hot.propToCol('pd_rps_id'), null, 'apiUpdate');
                                return; // หยุดการทำงาน โดดออกจากฟังก์ชันทันที
                            }
                        }
                        // ---------------------------------------------------------

                        // ถ้ากรอกครบทั้ง ชื่อสินค้า และ เลือกประเภทงานแล้ว (โค้ดเดิม)
                        if (pdName && pdName.trim() !== '' && pdRpsName) {
                            
                            const allData = hot.getSourceData();
                            let existingFrontendCode = null;
                            
                            // ขั้นที่ 1: เช็คก่อนว่าในตารางมีชื่อสินค้านี้กรอกไว้แล้วในแถวอื่นไหม 
                            let isDuplicateInTable = false;
                            for (let i = 0; i < allData.length; i++) { 
                                if (i !== row && allData[i].pd_details_head === pdName) { 
                                    isDuplicateInTable = true; 
                                    break; 
                                } 
                            }

                            if (isDuplicateInTable) { 
                                // 1. แจ้งเตือนผู้ใช้งานแบบ Popup
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'พบชื่อสินค้าซ้ำ!',
                                    text: 'ชื่อสินค้านี้มีอยู่แล้วในแถวอื่นที่คุณกำลังเพิ่มข้อมูล',
                                    timer: 2500,
                                    showConfirmButton: false
                                });
                                
                                // 2. ไฮไลต์พื้นหลังคอลัมน์ "ชื่อสินค้า" เป็นสีแดง (ใช้ class cell-error)
                                const nameColIndex = hot.propToCol('pd_details_head');
                                hot.setCellMeta(row, nameColIndex, 'className', 'cell-error');
                                
                                // 3. ลบค่ารหัสสินค้าออก เพื่อป้องกันการนำไปบันทึก
                                hot.setDataAtCell(row, hot.propToCol('pd_gen_code'), '', 'apiUpdate');
                                
                                hot.render(); // รีเฟรชตารางให้สีแดงขึ้นทันที
                                
                            } else { 
                                // ลบไฮไลต์สีแดงออก (เผื่อในกรณีที่ผู้ใช้แก้ชื่อใหม่แล้วไม่ซ้ำ)
                                const nameColIndex = hot.propToCol('pd_details_head');
                                hot.removeCellMeta(row, nameColIndex, 'className');
                                hot.render();
                                // ขั้นที่ 2: ถ้าไม่เจอในตาราง ให้ยิง API
                                const rpsId = repairSystemsReverseMap[pdRpsName];
                                if (rpsId) {
                                    axios.get(`handle_spares.php?action=check_info&rps_id=${rpsId}&name=${encodeURIComponent(pdName.trim())}&ag_id=${AG_ID}`)
                                        .then(response => {
                                            if (response.data && response.data.code) {
                                                let finalCode = response.data.code;

                                                // จองเลขถัดไปแบบ synchronous กันชนกันกรณีรันหลายแถวพร้อมกัน
                                                // (แทน loop เดิมที่อ่านจาก allData ซึ่งอาจยังไม่ทันอัปเดต)
                                                if (!response.data.exists) {
                                                    // ค้นหาตำแหน่ง '-' ตัวสุดท้าย เพื่อแยก prefix และตัวเลขออกจากกันอย่างแม่นยำ
                                                    const lastDashIdx = finalCode.lastIndexOf('-');
                                                    if (lastDashIdx !== -1) {
                                                        const prefix = finalCode.substring(0, lastDashIdx);
                                                        const numStr = finalCode.substring(lastDashIdx + 1);
                                                        
                                                        const apiNum = parseInt(numStr, 10);
                                                        const padLength = numStr.length;
                                                        
                                                        if (!isNaN(apiNum)) {
                                                            // โยนเข้าฟังก์ชันจองเลข (ต่อให้ API ส่งเลขเดิมมาซ้ำ ฟังก์ชันนี้จะบวกคิวถัดไปให้เอง)
                                                            finalCode = reserveNextCode(prefix, apiNum, padLength);
                                                        }
                                                    }
                                                }

                                                // อัปเดตรหัสลงไปที่ช่อง
                                                hot.setDataAtCell(row, hot.propToCol('pd_gen_code'), finalCode, 'apiUpdate');
                                            }
                                        })
                                        .catch(err => {
                                            console.error('Error fetching product code:', err);
                                        });
                                }
                            }
                        }
                    }
                    // ==========================================
                });
                
                hot.render();
                updateCounter();
                
                if (selectionChanged && typeof updateDeleteButtonVisibility === 'function') {
                    updateDeleteButtonVisibility();
                }
            },
            
            afterOnCellMouseDown: function (event, coords, td) {
                // ดักจับการคลิกที่ Header ของคอลัมน์แรก (Select All)
                if (coords.row === -1 && coords.col === 0) {
                    const isAllChecked = allData.length > 0 && allData.every(row => row.selected === true || row.selected === 'true');
                    allData.forEach(row => row.selected = !isAllChecked);
                    hot.loadData(JSON.parse(JSON.stringify(allData)));
                    
                    if (typeof updateDeleteButtonVisibility === 'function') {
                        updateDeleteButtonVisibility();
                    }
                    return;
                }
            }
        });

        function updateCounter() {
            let validNewRowsCount = 0;
            cachedNewRows.forEach(r => {
                if (r.mt_name && r.mt_name.trim() !== '') validNewRowsCount++;
            });

            allData.forEach(r => {
                if (newRowIds.has(r.id) && !cachedNewRows.some(cr => cr.id === r.id)) {
                     if (r.mt_name && r.mt_name.trim() !== '') validNewRowsCount++;
                }
            });

            const total = dirtyCells.size + newRowIds.size + deletedIds.length;
            if (total > 0) { counterEl.classList.remove('hidden'); countNumEl.innerText = total; } 
            else counterEl.classList.add('hidden');
        }

        function updateDeleteButtonVisibility() {
            const selectedRows = allData.filter(row => row.selected === true || row.selected === 'true');
            const selectedCount = selectedRows.length;
            
            const btnDelete = document.getElementById('btn-delete-selected');
            const countSpan = document.getElementById('selected-count'); 
            
            if (selectedCount > 0) {
                btnDelete.classList.remove('hidden');
                if (countSpan) countSpan.innerText = selectedCount;
            } else {
                btnDelete.classList.add('hidden'); 
                if (countSpan) countSpan.innerText = '0';
            }
        }

        function applyLocationToTable() {
            if (activeEditRowIndex === null) return;

            // 1. ดึง "ชื่อ" จากการแสดงผลบน UI ของ Modal
            const bNode = document.querySelector('#disp_building span.min-w-0');
            const fNode = document.getElementById('disp_floor');
            const rNode = document.getElementById('disp_room');

            let bName = bNode ? bNode.innerText.trim() : '';
            let fName = fNode ? fNode.innerText.trim() : '';
            let rName = rNode ? rNode.innerText.trim() : '';
            const aId = document.getElementById('in_building').value;
            const acId = document.getElementById('in_floor').value;
            const arId = document.getElementById('in_room').value;

            // 3. เอามาประกอบรวมเป็นข้อความเดียว
            let locParts = [];
            if (bName && bName !== 'Building') locParts.push(bName);
            if (fName) locParts.push(fName);
            if (rName && rName !== '-') locParts.push(rName);
            let fullLoc = locParts.join(' ');

            // อัปเดตตารางทันทีถ้ามีข้อมูล
            if (fullLoc.trim() !== '') {
                hot.setDataAtRowProp(activeEditRowIndex, 'full_location', fullLoc);
                
                const rowData = hot.getSourceDataAtRow(activeEditRowIndex);
                const target = allData.find(d => d.id === rowData.id);
                if(target) {
                    target['full_location'] = fullLoc;
                    target['area_id'] = aId || null;
                    target['ac_id'] = acId || null;
                    target['ar_id'] = arId || null;
                    target['area_name'] = bName || '';
                    target['ac_name'] = fName || '';
                    target['ar_name'] = rName || '';
                }
            }
        }

        // --- Interactions ---
        document.getElementById('search-input').addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { fetchSparesData(1); }, 500);
        });

        document.getElementById('page-size').addEventListener('change', (e) => {
            pageSize = parseInt(e.target.value); fetchSparesData(1); 
        });

        document.getElementById('btn-add-row').addEventListener('click', addNewRow);

        function addNewRow() {
            const numRows = parseInt(document.getElementById('row-count-input').value) || 1;
            
            for (let i = 0; i < numRows; i++) {
                const tempId = tempIdCounter--;
                
                const newRow = {
                    pd_id: tempId, // เปลี่ยนจาก id เป็น pd_id
                    selected: false,
                    pd_gen_code: 'Auto Generate', // รหัสสินค้าจะถูกสร้างจากหลังบ้าน
                    pd_rps_id: 'กรุณากรอกชื่อสินค้าก่อน',   // ประเภทงาน
                    pd_details_head: '', // ชื่อสินค้า
                    pd_model: '',
                    pd_price: 0,
                    pd_unit: '',
                    wh_name: '',       // จัดเก็บที่คลัง
                    pd_details: '',
                    pd_qty: 0,
                    pd_qty_all: 0,
                    pd_qty_min: 0,
                    isNew: true,
                    ps_status: "0"
                };
                
                allData.unshift(newRow); // นำข้อมูลไปต่อที่ด้านบนสุดของตาราง
                cachedNewRows.unshift(newRow);
                newRowIds.add(tempId);
            }
            
            hot.loadData(JSON.parse(JSON.stringify(allData)));
            updateCounter();
        }

        document.getElementById('btn-delete-selected').addEventListener('click', deleteSelected);

        async function deleteSelected() {
            const selectedRows = allData.filter(row => row.selected === true || row.selected === 'true');
            
            if (selectedRows.length === 0) return;

            // เปลี่ยนจาก confirm ธรรมดาเป็น SweetAlert2
            const result = await Swal.fire({
                title: 'ยืนยันการลบข้อมูล?',
                text: `คุณต้องการลบข้อมูลอะไหล่จำนวน ${selectedRows.length} รายการใช่หรือไม่?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<i class="fa-solid fa-trash-can"></i> ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก'
            });

            if (!result.isConfirmed) {
                return;
            }

            try {
                const idsToDelete = [];
                
                selectedRows.forEach(row => {
                    if (newRowIds.has(row.pd_id)) {
                        newRowIds.delete(row.pd_id);
                        cachedNewRows = cachedNewRows.filter(r => r.pd_id !== row.pd_id);
                        allData = allData.filter(r => r.pd_id !== row.pd_id);
                    } else {
                        idsToDelete.push(row.pd_id);
                    }
                });

                if (idsToDelete.length > 0) {
                    const response = await axios.post('handle_spares.php?action=delete_product', {
                        pd_ids: idsToDelete,
                        ag_id: AG_ID
                    });

                    if (response.data.success) {
                        idsToDelete.forEach(id => {
                            dirtyCells.delete(id);
                            cachedDirtyRows.delete(id);
                        });
                        Swal.fire({
                            icon: 'success',
                            title: 'ลบข้อมูลสำเร็จ',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    } else {
                        Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการลบข้อมูล: ' + response.data.message, 'error');
                        return;
                    }
                }

                await fetchSparesData(currentPage); 
                updateDeleteButtonVisibility(); 

            } catch (error) {
                console.error('Error deleting data:', error);
                Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการลบข้อมูล', 'error');
            }
        }

        document.getElementById('btn-save').addEventListener('click', saveData);

        async function saveData() {
            // =========================================
            // เช็คว่ามี cell-error (สีแดง) ค้างอยู่หรือไม่
            // =========================================
            let hasError = false;
            for (let r = 0; r < hot.countRows(); r++) {
                for (let c = 0; c < hot.countCols(); c++) {
                    const meta = hot.getCellMeta(r, c);
                    if (meta.className && meta.className.includes('cell-error')) {
                        hasError = true;
                        break; // หยุดเช็คคอลัมน์ถัดไป
                    }
                }
                if (hasError) break; // หยุดเช็คแถวถัดไป
            }

            if (hasError) {
                Swal.fire({
                    icon: 'error',
                    title: 'ไม่สามารถบันทึกได้',
                    text: 'พบข้อมูลสินค้าซ้ำ กรุณาแก้ไขช่องที่มีพื้นหลัง "สีแดง" ให้ถูกต้องก่อนบันทึก'
                });
                return; // หยุดการทำงานของฟังก์ชันทันที ไม่ส่งไปบันทึก
            }
            const changesToSave = [];

            // 1. รวบรวมข้อมูลแถวที่เพิ่มใหม่
            newRowIds.forEach(id => {
                const rowData = allData.find(r => r.pd_id === id);
                if (rowData && rowData.pd_details_head && rowData.pd_rps_id) { 
                    changesToSave.push({ ...rowData, pd_id: null }); 
                }
            });

            // 2. รวบรวมข้อมูลที่ถูกแก้ไข
            dirtyCells.forEach((changedProps, id) => {
                if (!newRowIds.has(id)) {
                    const rowData = allData.find(r => r.pd_id === id);
                    if (rowData) {
                        changesToSave.push({
                            ...rowData,
                            pd_id: id 
                        });
                    }
                }
            });

            if (changesToSave.length === 0) {
                // เปลี่ยนแจ้งเตือนเป็น SweetAlert2
                Swal.fire({
                    icon: 'info',
                    title: 'ไม่มีข้อมูลให้บันทึก',
                    text: 'ไม่มีข้อมูลที่ถูกแก้ไขหรือเพิ่มใหม่ (กรุณาตรวจสอบว่ากรอกข้อมูลจำเป็นครบถ้วน)'
                });
                return;
            }

            try {
                let hasError = false; 

                // แสดงหน้าโหลดระหว่างกำลังส่งข้อมูล
                Swal.fire({
                    title: 'กำลังบันทึกข้อมูล...',
                    text: 'กรุณารอสักครู่',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                for (const data of changesToSave) {
                    const payload = {
                        pd_id: data.pd_id,
                        pd_gen_code: (data.pd_gen_code && data.pd_gen_code !== 'Auto Generate') ? data.pd_gen_code : null,
                        pd_rps_id: repairSystemsReverseMap[data.pd_rps_id] || data.pd_rps_id,
                        pd_details_head: data.pd_details_head,
                        pd_brand: '',
                        pd_model: data.pd_model,
                        pd_price: data.pd_price,
                        pd_unit: data.pd_unit,
                        wh_ids: data.wh_name ? [ (warehouseReverseMap[data.wh_name] || data.wh_name) ] : [],
                        pd_details: data.pd_details,
                        qty: data.pd_qty,         
                        qty_all: data.pd_qty_all, 
                        qty_min: data.pd_qty_min,
                        ag_id: AG_ID,
                        ps_status: (data.ps_status === true || data.ps_status === '1' || data.ps_status === 1) ? '1' : '0'
                    };
                    
                    const response = await axios.post('handle_spares.php?action=save_product', payload);
                    
                    if (!response.data.success) {
                        hasError = true;
                        console.error('Error in row:', response.data.message);
                    }
                } 

                if (!hasError) {
                    dirtyCells.clear();
                    newRowIds.clear();
                    cachedNewRows = [];
                    cachedDirtyRows.clear();
                    
                    // ใช้ค่า bypassUnsavedCheck = true เพื่อไม่ให้หน้าต่างเตือนเด้งตอนรีเฟรชหน้า
                    window.bypassUnsavedCheck = true; 

                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกสำเร็จ',
                        text: 'ระบบทำการบันทึกข้อมูลเรียบร้อยแล้ว',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        // เพิ่มการรีโหลดหน้าตอนส่งข้อมูลสำเร็จ
                        window.location.reload(); 
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'บันทึกสำเร็จบางส่วน',
                        text: 'เกิดข้อผิดพลาดในการบันทึกข้อมูลบางรายการ'
                    });
                }
            } catch (error) {
                console.error('Error saving data:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เชื่อมต่อล้มเหลว',
                    text: 'เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์'
                });
            }
        }

        document.getElementById('btn-export-excel').addEventListener('click', async () => {
            try {
                Swal.fire({
                    title: 'กำลังเตรียมข้อมูล Export...',
                    text: 'กรุณารอสักครู่',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const searchVal = document.getElementById('search-input').value.trim();
                const response = await axios.get(`handle_spares.php?action=get_all&ag_id=${AG_ID}&search=${encodeURIComponent(searchVal)}&limit=999999&offset=0`);
            
                if (response.data && response.data.success && response.data.data.length > 0) {
                    const serverData = response.data.data;
                    
                    const exportData = serverData.map(item => ({
                        'รหัสสินค้า': item.pd_gen_code || '',
                        'ประเภทงาน': repairSystemsMap[item.pd_rps_id] || item.pd_rps_id || '',
                        'ชื่อสินค้า': item.pd_details_head || '',
                        'รุ่น / ชนิด': item.pd_model || '',
                        'ราคา/หน่วย': item.pd_price || 0,
                        'หน่วย': item.pd_unit || '',
                        'จัดเก็บที่คลัง': item.wh_name || warehouseMap[item.wh_id] || item.wh_id || '',
                        'รายละเอียดสินค้า': item.pd_details || '',
                        'คงเหลือ (Qty)': item.pd_qty || 0,
                        'สูงสุด (Max)': item.pd_qty_all || 0,
                        'ขั้นต่ำ (Min)': item.pd_qty_min || 0
                    }));

                    const worksheet = XLSX.utils.json_to_sheet(exportData);
                    const workbook = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(workbook, worksheet, "SparesData");
                    
                    const date = new Date();
                    const dateString = `${date.getFullYear()}${(date.getMonth()+1).toString().padStart(2,'0')}${date.getDate().toString().padStart(2,'0')}`;
                    const fileName = `Spares_Export_${dateString}.xlsx`;
                    
                    XLSX.writeFile(workbook, fileName);
                    
                    Swal.close();
                } else {
                    Swal.fire('แจ้งเตือน', 'ไม่มีข้อมูลสำหรับ Export', 'warning');
                }
            } catch (error) {
                console.error("Export Error: ", error);
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลสำหรับ Export ได้', 'error');
            }
        });

        async function initApp() {
            const targetNode = document.getElementById('loc_trigger');
            if (targetNode) {
                const observer = new MutationObserver((mutationsList) => {
                    if (activeEditRowIndex !== null) {
                        // หน่วงเวลาเล็กน้อย 100ms เพื่อให้สคริปต์ Location รันเสร็จก่อนดึงค่า
                        clearTimeout(window.locDebounce);
                        window.locDebounce = setTimeout(() => {
                            applyLocationToTable();
                        }, 100);
                    }
                });
                observer.observe(targetNode, { childList: true, subtree: true, characterData: true });
            }

            await Promise.all([
                loadRepairSystems(),
                loadWarehouses()
            ]);
            fetchSparesData();
            lucide.createIcons()
        }

        initApp();
        window.addEventListener('resize', () => { 
            clearTimeout(window.resizeTimer);
            window.resizeTimer = setTimeout(() => { hot.render(); }, 200);
        });
    </script>
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        
        window.bypassUnsavedCheck = false;

        // 1. ฟังก์ชันเช็คว่ามีการแก้ไขเซลล์หรือไม่
        function hasUnsavedChanges() {
            if (window.bypassUnsavedCheck) return false; 
            
            const countNum = document.getElementById('count-num');
            return countNum && parseInt(countNum.innerText || '0') > 0;
        }

        // 2. ฟังก์ชันแสดง SweetAlert2
        function showUnsavedWarning(onDenyCallback, onConfirmCallback) {
            Swal.fire({
                title: 'มีการแก้ไขที่ยังไม่ได้บันทึก',
                text: "คุณต้องการยกเลิกการแก้ไข หรือบันทึกข้อมูลก่อนเปลี่ยนหน้า?",
                icon: 'warning',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonColor: '#006B9F',
                denyButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa-solid fa-save"></i> บันทึกข้อมูล',
                denyButtonText: '<i class="fa-solid fa-rotate-left"></i> ยกเลิกการแก้ไข',
                cancelButtonText: 'ปิดหน้าต่าง'
            }).then((res) => {
                if (res.isConfirmed) {
                    onConfirmCallback(); 
                } else if (res.isDenied) {
                    onDenyCallback(); 
                }
            });
        }

        // 3. ฟังก์ชันดักจับการคลิกเมนู
        function interceptMenuClicks(targetWindow) {
            if (!targetWindow || !targetWindow.document) return;
            
            const doc = targetWindow.document;
            
            const clickHandler = function(e) {
                const target = e.target.closest('a, button, [onclick], .nav-item');
                
                const isMenuClick = target && (
                    target.closest('nav') ||
                    target.closest('.sidebar-nav') ||
                    target.closest('#menu-container') ||
                    target.closest('#desktop-menu-container') ||
                    target.closest('#mobile-menu-container') ||
                    target.closest('#main-dropdown') ||
                    target.closest('[id*="menu"]')
                );
                
                if (isMenuClick && hasUnsavedChanges()) {
                    e.preventDefault();
                    e.stopPropagation(); 
                    
                    const originalTarget = target; 
                    
                    showUnsavedWarning(
                        () => {
                            const countNum = document.getElementById('count-num');
                            if (countNum) countNum.innerText = '0';
                            
                            window.location.reload();
                            
                            window.bypassUnsavedCheck = true; 
                            doc.removeEventListener('click', clickHandler, true);
                            originalTarget.click();
                            
                            setTimeout(() => {
                                doc.addEventListener('click', clickHandler, true);
                                window.bypassUnsavedCheck = false;
                            }, 500);
                        },
                        () => {
                            const saveBtn = document.getElementById('btn-save');
                            if (saveBtn) {
                                saveBtn.click(); 
                                
                                const countNum = document.getElementById('count-num');
                                if (countNum) countNum.innerText = '0';

                                window.bypassUnsavedCheck = true;
                                setTimeout(() => { window.bypassUnsavedCheck = false; }, 2000);
                            }
                        }
                    );
                }
            };
            
            doc.addEventListener('click', clickHandler, true);
            
            window.addEventListener('unload', () => {
                doc.removeEventListener('click', clickHandler, true);
            });
        }

        // 4. ผูกการดักจับ Event กับหน้าต่างแม่ต่างๆ
        if (window.parent && window.parent !== window) {
            interceptMenuClicks(window.parent);
            if (window.parent.parent && window.parent.parent !== window.parent) {
                interceptMenuClicks(window.parent.parent);
            }
        }

        // 5. ดักการกดรีเฟรช/ปิดแท็บบนเบราว์เซอร์
        window.addEventListener('beforeunload', function (e) {
            if (hasUnsavedChanges()) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // 6. ดักการกดปุ่มบันทึกเองที่หน้าจอ
        const mainSaveBtn = document.getElementById('btn-save');
        if (mainSaveBtn) {
            mainSaveBtn.addEventListener('click', () => {
                const countNum = document.getElementById('count-num');
                if (countNum) countNum.innerText = '0'; 
                
                window.bypassUnsavedCheck = true;
                setTimeout(() => { window.bypassUnsavedCheck = false; }, 2000);
            });
        }
    });
</script>
</body>
</html>