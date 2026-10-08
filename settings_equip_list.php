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
    <link href="https://cdn.jsdelivr.net/npm/handsontable@17.0.1/dist/handsontable.full.min.css" rel="stylesheet">
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
        .handsontable, 
        .handsontable .colHeader, 
        .handsontable .rowHeader, 
        .handsontable .htCore,
        .handsontable .htDropdownMenuTable {
            font-family: 'Prompt', sans-serif !important;
        }

        /* ปรับแต่งส่วน Header ของ Handsontable ให้ดูเข้ากับธีม */
        .handsontable th.colHeader {
            font-weight: 500; /* ปรับน้ำหนักฟอนต์ตามต้องการ (300, 400, 500, 600, 700) */
            color: #334155;   /* สี Slate-700 ให้เข้ากับ Tailwind ของคุณ */
            font-size: 13px;
        }
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

    <script src="https://cdn.jsdelivr.net/npm/handsontable@17.0.1/dist/handsontable.full.min.js"></script>
    <script src="js/handsontable-light.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

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
        let buildingData = [];
        let areaNames = [];
        let assetTypes = [];
        
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
        async function fetchAssetTypes() {
            try {
                const response = await axios.get('get_all_ass_type.php');
                if (response.data) {
                    assetTypeData = response.data; 
                    assetTypes = response.data.map(item => item.name);
                }
            } catch (error) {
                console.error("Error fetching asset types:", error);
            }
        }

        async function fetchBuildingData() {
            try {
                const response = await axios.get(`get_building_all.php?ag_id=${AG_ID}`);
                if (response.data) {
                    // 1. ลองดึงข้อมูลออกมาก่อน (เผื่อ API ส่งมาในรูปแบบ { data: [...] } หรือส่งมาตรงๆ)
                    let rawData = response.data.data ? response.data.data : response.data;
                    
                    // 2. ถ้าข้อมูลเป็น Object ให้แปลงเป็น Array โดยดึงมาเฉพาะ Values
                    if (!Array.isArray(rawData)) {
                        // เผื่อ API ส่งมาเป็น { ok: false, error: "..." }
                        if (rawData.error) {
                            console.error("API Error:", rawData.error);
                            return;
                        }
                        rawData = Object.values(rawData);
                    }

                    // 3. กำหนดค่าให้ buildingData (ตอนนี้เป็น Array แน่นอนแล้ว)
                    buildingData = rawData;
                    
                    // 4. ดึงชื่ออาคารทั้งหมด (ทีนี้ .map จะทำงานได้ปกติ)
                    areaNames = buildingData.map(b => b.name);
                }
            } catch (error) {
                console.error("Error fetching building data:", error);
            }
        }
        
        async function fetchMachineData(page = 1) {
            preserveCurrentState();

            currentPage = page;
            const searchVal = document.getElementById('search-input').value.trim();
            
            const startRow = (currentPage - 1) * pageSize;
            const endRow = startRow + parseInt(pageSize);

            try {
                const response = await axios.get(`handle_machine_info.php?action=get_all&ag_id=${AG_ID}&search=${encodeURIComponent(searchVal)}&startRow=${startRow}&endRow=${endRow}`);
                
                if (response.data && response.data.rows) { 
                    let serverData = response.data.rows.map(item => {
                        return {
                            ...item,
                            selected: false
                        };
                    });

                    serverData = serverData.map(row => {
                        return cachedDirtyRows.has(row.id) ? cachedDirtyRows.get(row.id) : row;
                    });
                    
                    allData = [...cachedNewRows, ...serverData];
                    
                    // 4. เปลี่ยนมารับค่าจำนวนแถวทั้งหมดจาก .lastRow
                    const totalRows = response.data.lastRow || 0; 
                    
                    // ส่งค่า startRow ไปวาด Pagination กลับ
                    renderServerPagination(totalRows, startRow); 
                    
                    hot.loadData(JSON.parse(JSON.stringify(allData)));
                    updateCounter();
                }
            } catch (error) {
                console.error('Error fetching data:', error);
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
                btn.onclick = () => { fetchMachineData(targetPage); };
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

        // --- Renderers ---
        function customCellRenderer(instance, td, row, col, prop, value, cellProperties) {
            Handsontable.renderers.TextRenderer.apply(this, arguments);
            
            td.classList.remove('cell-dirty', 'cell-new', 'status-active', 'status-inactive', 'cell-error');
            
            const rowData = instance.getSourceDataAtRow(row);
            if (!rowData) return td;

            // ลบ class สีแดงออกทันทีถ้ามีข้อมูล (ป้องกันสีแดงค้างจากการ Validate ไม่ทัน)
            if (value && td.classList.contains('htInvalid')) {
                td.classList.remove('htInvalid');
            }

            if (errorRows.has(rowData.id) && prop === 'mt_name') {
                td.classList.add('cell-error');
            } else if (newRowIds.has(rowData.id)) { 
                td.classList.add('cell-new'); 
            } else if (dirtyCells.has(rowData.id) && dirtyCells.get(rowData.id).has(prop)) { 
                td.classList.add('cell-dirty'); 
            }

            if (prop === 'status') {
                if (value === 'Active') td.classList.add('status-active');
                if (value === 'Inactive') td.classList.add('status-inactive');
                td.style.textAlign = 'center';
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
            
            const isActive = (String(value) === '0');
            
            const label = document.createElement('span');
            label.className = isActive ? 'status-active text-[11px]' : 'status-inactive text-[11px]';
            label.innerText = isActive ? 'Active' : 'Inactive';
            
            const input = td.querySelector('input');
            if (input) input.className = 'w-3.5 h-3.5 cursor-pointer';

            Array.from(td.childNodes).forEach(node => {
                if (node.nodeType === Node.TEXT_NODE) {
                    node.remove();
                }
            });

            td.appendChild(label);
            return td;
        }

        function customStatusRenderer(instance, td, row, col, prop, value, cellProperties) {
            Handsontable.renderers.TextRenderer.apply(this, arguments);

            switch (value) {
                case 'ปกติ':
                    td.innerHTML = '<div class="text-emerald-600 font-medium">🟢 ปกติ (Active)</div>';
                    break;
                case 'แจ้งซ่อม':
                    td.innerHTML = '<div class="text-amber-600 font-medium">🟡 แจ้งซ่อม (Maintenance)</div>';
                    break;
                case 'ชำรุด':
                    td.innerHTML = '<div class="text-rose-600 font-medium">🔴 ชำรุด (Broken)</div>';
                    break;
                case 'สำรอง':
                    td.innerHTML = '<div class="text-sky-600 font-medium">🔵 สำรอง (Spare)</div>';
                    break;
                default:
                    // กรณีเป็นค่าว่าง หรือ 'ไม่ทราบสถานะ'
                    td.innerHTML = '<div class="text-slate-500 font-medium">⚪ ไม่ทราบสถานะ (Unknown)</div>';
                    break;
            }

            const rowData = instance.getSourceDataAtRow(row);
            if (rowData) {
                if (typeof newRowIds !== 'undefined' && newRowIds.has(rowData.id)) {
                    td.classList.add('cell-new');
                } else if (typeof dirtyCells !== 'undefined' && dirtyCells.has(`${rowData.id}-${prop}`)) {
                    td.classList.add('cell-dirty');
                } else {
                    td.classList.remove('cell-dirty', 'cell-new', 'status-active', 'status-inactive');
                }
            }
        }

        // ใช้ Event Delegation ดักจับการคลิกที่ Checkbox บน Header โดยตรง
        container.addEventListener('click', (e) => {
            if (e.target.classList.contains('checker-header')) {
                const isChecked = e.target.checked;
                
                // 1. สร้าง Array ของการเปลี่ยนแปลง (row, prop, value)
                // การใช้ batch จะช่วยให้ performance ดีกว่าการสั่ง render ทีละแถว
                const changes = [];
                const rowCount = hot.countRows();
                
                for (let i = 0; i < rowCount; i++) {
                    changes.push([i, 'selected', isChecked]);
                }

                // 2. ใช้ setDataAtRowProp แบบส่งอาเรย์ก้อนเดียว เพื่ออัปเดต Data และ UI พร้อมกัน
                if (changes.length > 0) {
                    hot.setDataAtRowProp(changes);
                }
                
                // 3. อัปเดตค่าใน Array หลัก (allData) ให้ตรงกันด้วย
                allData.forEach(row => {
                    row.selected = isChecked;
                });

                updateDeleteButtonVisibility();
            }
        });
        

        // --- Handsontable Init ---
        const hot = new Handsontable(container, {
            data: [], 
            // 1. เปลี่ยน colHeaders ให้รับเป็นฟังก์ชัน เพื่อแทรก Checkbox Select All ไปที่คอลัมน์แรก
            colHeaders: function(col) {
                if (col === 0) {
                    const allChecked = allData.length > 0 && allData.every(row => row.selected === true || row.selected === 'true');
                    return `<input type="checkbox" class="checker-header cursor-pointer w-3.5 h-3.5" ${allChecked ? 'checked' : ''}>`;
                }
                const headers = [
                    'รหัสครุภัณฑ์',
                    'ประเภทเครื่องจักร',
                    'ชื่อเครื่องจักร',
                    'ซีเรียล',
                    'ยี่ห้อ',
                    'รุ่น',
                    'วันหมดรับประกัน',
                    'บริษัทที่ดูแลอุปกรณ์',
                    'อาคาร',
                    'ชั้น',
                    'ห้อง',
                    'หมายเหตุ',
                    'สถานะ'
                ];
                return headers[col - 1];
            },
            // 2. เพิ่มคอลัมน์ selected ที่ index 0
            columns: [
                { data: 'selected', type: 'checkbox', className: 'htCenter htMiddle', width: 40 },
                { data: 'asset_id', type: 'text', renderer: customCellRenderer },
                { 
                    data: 'type_name', 
                    type: 'dropdown', 
                    source: function(query, process) {
                        process(assetTypes); 
                    },
                    renderer: customCellRenderer 
                },
                { data: 'machine_name', type: 'text', renderer: customCellRenderer },
                { data: 'serial', type: 'text', renderer: customCellRenderer },
                { data: 'brand', type: 'text', renderer: customCellRenderer },
                { data: 'model', type: 'text', renderer: customCellRenderer },
                { data: 'warranty', type: 'date', dateFormat: 'YYYY-MM-DD', renderer: customCellRenderer },
                { data: 'company', type: 'text', renderer: customCellRenderer },
                { 
                    data: 'location', 
                    type: 'dropdown', 
                    allowInvalid: true, // 👈 ป้องกันสีแดงค้างตอนยังไม่ได้เลือกชั้น
                    source: function(query, process) {
                        process(areaNames);
                    },
                    renderer: customCellRenderer, 
                    width: 200 
                },
                { 
                    data: 'floor', 
                    type: 'dropdown', 
                    allowInvalid: true,
                    source: function(query, process) {
                        const area = this.instance.getDataAtRowProp(this.row, 'location');
                        if (!area) return process([]);
                        
                        const areaObj = buildingData.find(b => String(b.name).trim() === String(area).trim());
                        
                        if (areaObj && areaObj.floors && areaObj.floors.length > 0) {
                            process(areaObj.floors.map(f => f.name));
                        } else {
                            process([]);
                        }
                    },
                    renderer: customCellRenderer, 
                    width: 120 
                },
                { 
                    data: 'room', 
                    type: 'dropdown', 
                    allowInvalid: true,
                    source: function(query, process) {
                        const area = this.instance.getDataAtRowProp(this.row, 'location');
                        const floor = this.instance.getDataAtRowProp(this.row, 'floor');
                        if (!area || !floor) return process([]);

                        const areaObj = buildingData.find(b => String(b.name).trim() === String(area).trim());
                        
                        if (areaObj && areaObj.floors) {
                            const floorObj = areaObj.floors.find(f => String(f.name).trim() === String(floor).trim());
                        
                            if (floorObj && floorObj.rooms && floorObj.rooms.length > 0) {
                                process(floorObj.rooms.map(r => r.name));
                                return;
                            }
                        }
                        process([]);
                    },
                    renderer: customCellRenderer, 
                    width: 150 
                },
                { 
                    data: 'remark', 
                    type: 'text', 
                    renderer: customCellRenderer, 
                    width: 300 
                },
                { 
                    data: 'status', 
                    type: 'dropdown',
                    source: ['ปกติ', 'แจ้งซ่อม', 'ชำรุด', 'สำรอง', 'ไม่ทราบสถานะ'], 
                    renderer: customStatusRenderer, width: 200 
                }
            ],
            stretchH: 'all', 
            rowHeaders: function(index) {
                return (currentPage - 1) * pageSize + index + 1;
            },
            height: '100%',
            licenseKey: 'non-commercial-and-evaluation', 
            viewportRowRenderingOffset: 9999,
            width: '100%',
            preventOverflow: 'horizontal',

            afterOnCellMouseDown: function (event, coords, td) {
                if (coords.row < 0) return;
                
                // จัดการเฉพาะส่วนการเลือกสถานที่ (คอลัมน์ที่ 3)
                if (coords.col === 3) {
                    activeEditRowIndex = coords.row;
                    const bNode = document.querySelector('#disp_building span.min-w-0');
                    if (bNode) bNode.innerText = '';
                    
                    const fNode = document.getElementById('disp_floor');
                    if (fNode) fNode.innerText = '';
                    
                    const rNode = document.getElementById('disp_room');
                    if (rNode) rNode.innerHTML = '';
                    
                    // ตรวจสอบว่ามี LocationSelector object ให้เรียกใช้
                    if (typeof LocationSelector !== 'undefined') {
                        LocationSelector.openModal();
                    }
                } else {
                    activeEditRowIndex = null;
                }
            },
            afterChange: function (changes, source) {
                if (source === 'loadData' || source === 'ObserveChanges.change') return;
    
                let selectionChanged = false;
                
                // ดึงรายชื่อคอลัมน์ทั้งหมดที่มีการเปลี่ยนแปลงในรอบนี้ (เพื่อเช็คว่าเป็นการ Copy/Paste หลายคอลัมน์หรือไม่)
                const changedPropsInThisBatch = changes.map(change => change[1]);

                changes.forEach(([row, prop, oldValue, newValue]) => {
                    if (oldValue === newValue) return;
                    
                    // --- แก้ไขการเคลียร์ค่า Dropdown ย่อย ---
                    // จะเคลียร์ค่าทิ้งก็ต่อเมื่อ ไม่ได้เป็นการ Paste ค่า 'floor' หรือ 'room' มาพร้อมกันในครั้งนี้
                    if (prop === 'location' && !changedPropsInThisBatch.includes('floor')) {
                        hot.setDataAtRowProp(row, 'floor', '');
                        hot.setDataAtRowProp(row, 'room', '');
                    } else if (prop === 'floor' && !changedPropsInThisBatch.includes('room')) {
                        hot.setDataAtRowProp(row, 'room', '');
                    }
                    
                    const rowData = hot.getSourceDataAtRow(row);
                    const target = allData.find(d => d.id === rowData.id);
                    if(!target) return;
                    
                    target[prop] = newValue;
                    
                    // โค้ดเดิมสำหรับการบันทึก state Dirty Cell...
                    if (!newRowIds.has(target.id) && prop !== 'selected') {
                        if (!dirtyCells.has(target.id)) dirtyCells.set(target.id, new Set());
                        dirtyCells.get(target.id).add(prop);
                    }
                    if (prop === 'selected') selectionChanged = true;
                });

                hot.render();
                updateCounter();
                if (selectionChanged) updateDeleteButtonVisibility();
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
                    target['location'] = bName || '';
                    target['floor'] = fName || '';
                    target['room'] = rName || '';
                }
            }
        }

        function highlightErrorCells(failedAssetIds) {
            // ล้างคลาส error เก่าออกก่อน (ถ้ามี)
            document.querySelectorAll('.cell-error').forEach(el => el.classList.remove('cell-error'));

            failedAssetIds.forEach(assetId => {
                if (!assetId) return;
                
                // สมมติว่าคุณมี input ที่มีแอตทริบิวต์ data-asset-id="M-123" หรือ id="asset_M-123"
                // ให้ปรับ Selector ให้ตรงกับ HTML ของคุณ
                const inputEl = document.querySelector(`input[data-asset-id="${assetId}"]`); 
                if (inputEl) {
                    inputEl.classList.add('cell-error');
                    // หรือใส่ที่ td แม่ของมัน
                    // inputEl.closest('td').classList.add('cell-error');
                }
            });
        }

        // --- Interactions ---
        document.getElementById('search-input').addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { fetchMachineData(1); }, 500);
        });

        document.getElementById('page-size').addEventListener('change', (e) => {
            pageSize = parseInt(e.target.value); fetchMachineData(1); 
        });

        document.getElementById('btn-add-row').addEventListener('click', () => {
            preserveCurrentState();
            const count = parseInt(document.getElementById('row-count-input').value) || 1;
            
            for(let i = 0; i < count; i++) {
                const newObj = {
                    id: tempIdCounter--,
                    selected: false,
                    image1: "",
                    asset_id: "",
                    type_name: "",
                    machine_name: "",
                    serial: "",
                    brand: "",
                    model: "",
                    warranty: "",
                    company: "",
                    location: "",
                    floor: "",
                    room: "",
                    remark: "",
                    status: "ไม่ทราบสถานะ", 
                    area_id: null,
                    ac_id: null,
                    ar_id: null
                };
                cachedNewRows.unshift(newObj);
                newRowIds.add(newObj.id);
            }
            fetchMachineData(currentPage);
        });

        document.getElementById('btn-delete-selected').addEventListener('click', () => {
            // ดึงข้อมูลแถวที่ถูกติ๊กเลือก
            const rowsToDelete = allData.filter(row => row.selected === true || row.selected === 'true');
            
            if (rowsToDelete.length === 0) return;

            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: `คุณต้องการลบ ${rowsToDelete.length} รายการที่เลือกใช่หรือไม่? (ข้อมูลจะถูกลบออกจากระบบทันที)`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: 'ใช่, ลบทันที',
                cancelButtonText: 'ยกเลิก'
            }).then(async (result) => { // เพิ่ม async ตรงนี้เพื่อใช้ await ตอนเรียก API
                if (result.isConfirmed) {
                    
                    let idsToDeleteFromDB = []; // เก็บ ID เฉพาะรายการที่มีในฐานข้อมูลแล้ว

                    rowsToDelete.forEach(row => {
                        if (newRowIds.has(row.id)) {
                            // ถ้าเป็นแถวที่เพิ่งกด "เพิ่มแถว" (ยังไม่เคยลง DB) ให้ลบออกจาก Cache ไปเลยทันที
                            newRowIds.delete(row.id);
                            cachedNewRows = cachedNewRows.filter(r => r.id !== row.id);
                        } else {
                            // ถ้าเป็นแถวที่มีในฐานข้อมูลอยู่แล้ว ให้แยก ID ไว้ไปยิงลบ
                            if (!idsToDeleteFromDB.includes(row.id)) {
                                idsToDeleteFromDB.push(row.id);
                            }
                            dirtyCells.delete(row.id); 
                            cachedDirtyRows.delete(row.id);
                        }
                    });

                    // ตรวจสอบว่ามีข้อมูลที่ต้องลบออกจากฐานข้อมูลหรือไม่
                    if (idsToDeleteFromDB.length > 0) {
                        try {
                            Swal.fire({ title: 'กำลังลบข้อมูล...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                            
                            // --- ส่วนที่แก้ไข: ส่งข้อมูลด้วย FormData และเปลี่ยน action เป็น delete ---
                            const formData = new FormData();
                            // ส่ง ID เป็น Array String เพื่อให้ PHP แปลงง่ายๆ
                            formData.append('deleted_ids', JSON.stringify(idsToDeleteFromDB)); 
                            
                            const response = await axios.post('handle_machine_info.php?action=delete', formData, {
                                headers: { 'Content-Type': 'multipart/form-data' }
                            });
                            // ----------------------------------------------------------------

                            if (response.data && response.data.success) {
                                Swal.fire({ title: 'ลบข้อมูลสำเร็จ!', icon: 'success', timer: 1500 });
                                
                                // รีเฟรชดึงข้อมูลตารางใหม่ เพื่อให้ตรงกับ DB จริงๆ
                                await fetchMachineData(currentPage);
                                updateDeleteButtonVisibility(); // ซ่อนปุ่มลบ
                                return; 
                            } else {
                                Swal.fire('ข้อผิดพลาด', response.data.error || 'ไม่สามารถลบข้อมูลได้', 'error');
                                return;
                            }
                        } catch (error) {
                            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                            return;
                        }
                    }

                    // ในกรณีที่เลือกเฉพาะแถวใหม่ที่เกิดจากการกด "เพิ่มแถว" เท่านั้น (ไม่ต้องยิง API)
                    allData = allData.filter(row => !(row.selected === true || row.selected === 'true'));
                    
                    hot.loadData(JSON.parse(JSON.stringify(allData)));
                    updateCounter();
                    updateDeleteButtonVisibility(); // ซ่อนปุ่มลบ
                    
                    Swal.fire({ title: 'ลบรายการสำเร็จ!', icon: 'success', timer: 1000, showConfirmButton: false });
                }
            });
        });

        document.getElementById('btn-save').addEventListener('click', async () => {
            preserveCurrentState(); 

            const dataToSave = [];
            let validNewRows = []; 

            cachedNewRows.forEach(row => {
                // แก้ไข: เปลี่ยนจาก row.mt_name เป็น row.machine_name ให้ตรงกับฟิลด์ข้อมูลจริง
                const isRowEmpty = (!row.machine_name || String(row.machine_name).trim() === '');
                
                if (isRowEmpty) {
                    newRowIds.delete(row.id);
                } else {
                    validNewRows.push(row);
                    let rowData = { ...row };
                    rowData.id = 0; 
                    dataToSave.push(rowData);
                }
            });

            cachedNewRows = validNewRows;

            cachedDirtyRows.forEach((row, id) => {
                let rowData = { ...row };
                dataToSave.push(rowData);
            });

            if (dataToSave.length === 0) {
                updateCounter();
                fetchMachineData(currentPage); 
                Swal.fire({ title: 'ไม่มีข้อมูลที่ต้องบันทึก', text: 'ระบบได้ล้างแถวที่ว่างเปล่าออกแล้ว', icon: 'info' });
                return;
            }

            const result = await Swal.fire({
                title: 'ยืนยันการบันทึก?', text: `พบข้อมูลที่ต้องการบันทึก ${dataToSave.length} รายการ`,
                icon: 'question', showCancelButton: true, confirmButtonColor: '#006B9F', confirmButtonText: 'บันทึก'
            });

            if(result.isConfirmed) {
                try {
                    Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    
                    let successCount = 0;
                    let errorCount = 0;

                    // ปรับปรุง: ใช้ Promise ในการเตรียม Request ทั้งหมด
                    const savePromises = dataToSave.map(row => {
                    const formData = new FormData();
                    if (row.id > 0) formData.append('id', row.id);

                    // แมปประเภทเครื่องจักร
                    const matchedType = assetTypeData.find(item => item.name === row.type_name);
                    const typeId = matchedType ? (matchedType.id || matchedType.GroupId) : '';
                    
                    // --- เพิ่มส่วนนี้: แมปชื่อสถานที่ ชั้น ห้อง กลับเป็น ID จากตัวแปร buildingData ---
                    let finalAreaId = row.area_id || '';
                    let finalAcId = row.ac_id || '';
                    let finalArId = row.ar_id || '';

                    // ถ้ามีการเลือก Location แต่ไม่มี ID (เช่น เปลี่ยนค่าจากหน้าจอ)
                    if (row.location && buildingData) {
                        const matchedArea = buildingData.find(b => b.name === row.location);
                        if (matchedArea) {
                            finalAreaId = matchedArea.id || matchedArea.area_id; // เปลี่ยนชื่อ field ID ให้ตรงกับ JSON ของคุณ

                            if (row.floor && matchedArea.floors) {
                                const matchedFloor = matchedArea.floors.find(f => f.name === row.floor);
                                if (matchedFloor) {
                                    finalAcId = matchedFloor.id || matchedFloor.ac_id;

                                    if (row.room && matchedFloor.rooms) {
                                        const matchedRoom = matchedFloor.rooms.find(r => r.name === row.room);
                                        if (matchedRoom) {
                                            finalArId = matchedRoom.id || matchedRoom.ar_id;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    // -------------------------------------------------------------------------

                    formData.append('asset_id', row.asset_id || '');
                    formData.append('machine_name', row.machine_name || '');
                    formData.append('machine_type', typeId);
                    formData.append('serial', row.serial || '');
                    formData.append('model', row.model || '');
                    formData.append('warranty', row.warranty || '');
                    formData.append('vendor', row.company || '');
                    formData.append('remark', row.remark || '');
                    formData.append('status', row.status || 'ไม่ทราบสถานะ');
                    formData.append('ag_id', typeof AG_ID !== 'undefined' ? AG_ID : '');
                    formData.append('user_id', typeof USER_ID !== 'undefined' ? USER_ID : '');

                    // แก้ไข: เช็คว่าถ้ามีค่าจริงๆ ถึงจะ append (ป้องกันการส่งคำว่า "undefined" หรือค่าว่างไปบันทึก)
                    if (row.brand) formData.append('brand', row.brand);
                    if (finalAreaId && finalAreaId !== 'undefined') formData.append('area_id', finalAreaId);
                    if (finalAcId && finalAcId !== 'undefined') formData.append('ac_id', finalAcId);
                    if (finalArId && finalArId !== 'undefined') formData.append('ar_id', finalArId);

                    return axios.post('handle_machine_info.php?action=save', formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    });
                });

                    // ยิง API พร้อมกันเพื่อความรวดเร็วด้วย Promise.allSettled
                    const results = await Promise.allSettled(savePromises);

                    // เช็คผลลัพธ์ของแต่ละ Request
                    let errorMessages = [];
                    let failedAssetIds = [];

                    results.forEach((res, index) => {
                        const currentRow = dataToSave[index];

                        if (res.status === 'fulfilled' && res.value.data) {
                            if (res.value.data.success) {
                                successCount++;
                            } else {
                                errorCount++;
                                if (res.value.data.error) {
                                    errorMessages.push(res.value.data.error);
                                    // เก็บ asset_id หรือ id ของแถวที่มีปัญหา
                                    failedAssetIds.push(currentRow.asset_id); 
                                }
                            }
                        } else {
                            errorCount++;
                            errorMessages.push(`ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้ (รหัส: ${currentRow.asset_id || 'ไม่ทราบ'})`);
                            failedAssetIds.push(currentRow.asset_id);
                        }
                    });

                    if (errorCount === 0) {
                        Swal.fire({ title: 'บันทึกสำเร็จ!', icon: 'success', timer: 1500 });
                        dirtyCells.clear();
                        newRowIds.clear();
                        deletedIds = [];
                        cachedNewRows = [];
                        cachedDirtyRows.clear();
                        updateCounter();
                        fetchMachineData(currentPage);
                    } else {
                        let errorHtml = `<b>บันทึกสำเร็จ ${successCount} รายการ</b><br><b style="color:red;">ล้มเหลว ${errorCount} รายการ</b><br><br>`;
                        
                        if (errorMessages.length > 0) {
                            const uniqueErrors = [...new Set(errorMessages)];
                            
                            // 💡 เพิ่ม max-height และ overflow-y: auto เพื่อให้มี Scrollbar ถ้าข้อมูลยาวเกิน
                            errorHtml += `
                            <div style="text-align: left; font-size: 0.9em; background: #fee2e2; padding: 10px; border-radius: 5px; max-height: 100px; overflow-y: auto; border: 1px solid #fca5a5;">`;
                            
                            uniqueErrors.forEach(err => {
                                errorHtml += `<div style="margin-bottom: 8px; border-bottom: 1px solid #fecaca; padding-bottom: 4px;">❌ ${err}</div>`;
                            });
                            errorHtml += `</div>`;
                        }

                        // 💡 ฟังก์ชันสำหรับไฮไลท์ Cell สีแดงบน Grid
                        highlightErrorCells(failedAssetIds);

                        Swal.fire({ 
                            title: 'พบข้อผิดพลาดบางรายการ', 
                            html: errorHtml, 
                            icon: 'warning' 
                        });
                        fetchMachineData(currentPage); 
                    }
                } catch (error) {
                    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                }
            }
        });

        document.getElementById('btn-export-excel').addEventListener('click', async () => {
            try {
                // 1. แสดง Loading เพื่อแจ้งให้ผู้ใช้ทราบว่ากำลังดึงข้อมูล
                Swal.fire({
                    title: 'กำลังเตรียมข้อมูล Export...',
                    text: 'กรุณารอสักครู่',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const searchVal = document.getElementById('search-input').value.trim();
                const response = await axios.get(`handle_machine_info.php?action=get_all&ag_id=${AG_ID}&search=${encodeURIComponent(searchVal)}&export=true`);
                
                // รองรับทั้งกรณีที่ API ส่งกลับมาเป็น .data หรือ .rows
                const serverData = response.data.data || response.data.rows;

                if (serverData && serverData.length > 0) {
                    const exportData = serverData.map(item => {
                        let locParts = [];
                        if (item.location) locParts.push(item.location);
                        if (item.floor) locParts.push(item.floor);
                        if (item.room) locParts.push(item.room);
                        let full_location = locParts.length > 0 ? locParts.join(' ') : '';

                        // แมปปิ้งฟิลด์ใหม่ให้ตรงกับระบบจัดการเครื่องจักร
                        return {
                            'รหัสครุภัณฑ์': item.asset_id || '',
                            'ประเภทเครื่องจักร': item.type_name || '',
                            'ชื่อเครื่องจักร': item.machine_name || '',
                            'ซีเรียล': item.serial || '',
                            'ยี่ห้อ': item.brand || '',
                            'รุ่น': item.model || '',
                            'วันหมดรับประกัน': item.warranty || '',
                            'บริษัทที่ดูแลอุปกรณ์': item.company || '',
                            'สถานที่ (อาคาร/ชั้น/ห้อง)': full_location,
                            'หมายเหตุ': item.remark || '',
                            'สถานะ': item.status || 'ไม่ทราบสถานะ'
                        };
                    });

                    const worksheet = XLSX.utils.json_to_sheet(exportData);
                    const workbook = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(workbook, worksheet, "MachineData"); // เปลี่ยนชื่อ Sheet
                    
                    const date = new Date();
                    const dateString = `${date.getFullYear()}${(date.getMonth()+1).toString().padStart(2,'0')}${date.getDate().toString().padStart(2,'0')}`;
                    const fileName = `Machine_Export_${dateString}.xlsx`; // เปลี่ยนชื่อไฟล์เริ่มต้น
                    
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
            await fetchAssetTypes();
            await fetchBuildingData();
            await fetchMachineData();
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