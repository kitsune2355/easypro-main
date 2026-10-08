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

    <script src="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.js"></script>
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
        
        async function fetchMetersData(page = 1) {
            preserveCurrentState();

            currentPage = page;
            const searchVal = document.getElementById('search-input').value.trim();
            const offset = (currentPage - 1) * pageSize;

            try {
                const response = await axios.get(`handle_meters.php?action=get_all&ag_id=${AG_ID}&search=${encodeURIComponent(searchVal)}&limit=${pageSize}&offset=${offset}`);
                
                if (response.data && response.data.success) {
                    let serverData = response.data.data.map(item => {
                        return {
                            ...item,
                            selected: false
                        };
                    });

                    serverData = serverData.map(row => {
                        return cachedDirtyRows.has(row.id) ? cachedDirtyRows.get(row.id) : row;
                    });
                    
                    allData = [...cachedNewRows, ...serverData];
                    
                    const totalRows = response.data.total || 0;
                    renderServerPagination(totalRows, offset); 
                    
                    hot.loadData(JSON.parse(JSON.stringify(allData)));
                    updateCounter();
                }
            } catch (error) {
                console.error(error);
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
                btn.onclick = () => { fetchMetersData(targetPage); };
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
                const headers = ['ประเภทมิเตอร์', 'ชื่อมิเตอร์', 'อาคาร/สาขา', 'ชั้น', 'ห้อง', 'ค่าสูงสุดหน้าปัด', 'กำหนด % ที่รับได้', 'สถานะ'];
                return headers[col - 1];
            },
            // 2. เพิ่มคอลัมน์ selected ที่ index 0
            columns: [
                { data: 'selected', type: 'checkbox', className: 'htCenter htMiddle', width: 40 },
                { data: 'mt_type', type: 'dropdown', source: ['มิเตอร์น้ำ', 'มิเตอร์ไฟ', 'มิเตอร์TOU', 'มิเตอร์แก๊ส'], renderer: customCellRenderer, width: 120 },
                { data: 'mt_name', type: 'text', renderer: customCellRenderer, width: 200 },
                { 
                    data: 'area_name', 
                    type: 'dropdown', 
                    source: function(query, process) {
                        process(areaNames);
                    },
                    renderer: customCellRenderer, 
                    width: 200 
                },
                { 
                    data: 'ac_name', 
                    type: 'dropdown', 
                    source: function(query, process) {
                        // อ้างอิงแถวปัจจุบันเพื่อดูว่าเลือกอาคารอะไรไปแล้ว
                        const rowData = this.instance.getSourceDataAtRow(this.row);
                        const area = rowData.area_name;
                        if (!area) return process([]);
                        
                        // หาอาคารที่ตรงกันเพื่อดึงชั้น (Floors)
                        const areaObj = buildingData.find(b => b.name === area);
                        if (areaObj && areaObj.floors) {
                            process(areaObj.floors.map(f => f.name));
                        } else {
                            process([]);
                        }
                    },
                    renderer: customCellRenderer, 
                    width: 100 
                },
                { 
                    data: 'ar_name', 
                    type: 'dropdown', 
                    source: function(query, process) {
                        const rowData = this.instance.getSourceDataAtRow(this.row);
                        const area = rowData.area_name;
                        const floor = rowData.ac_name;
                        if (!area || !floor) return process([]);
                        
                        // หาห้อง (Rooms) จากอาคารและชั้นที่เลือก
                        const areaObj = buildingData.find(b => b.name === area);
                        if (areaObj && areaObj.floors) {
                            const floorObj = areaObj.floors.find(f => f.name === floor);
                            if (floorObj && floorObj.rooms) {
                                process(floorObj.rooms.map(r => r.name));
                            } else {
                                process([]);
                            }
                        } else {
                            process([]);
                        }
                    },
                    renderer: customCellRenderer, 
                    width: 100 
                },
                { 
                    data: 'max_val', 
                    title: 'ค่าสูงสุดหน้าปัด (Max)', 
                    type: 'numeric',
                    renderer: customCellRenderer, 
                    numericFormat: { pattern: '0,0' }
                },
                { data: 'limit_percent', type: 'numeric', numericFormat: { pattern: '0.00' }, width: 120, className: 'htRight htMiddle',renderer: customCellRenderer },
                { data: 'status', type: 'checkbox', checkedTemplate: '0', uncheckedTemplate: '1', renderer: statusCheckboxRenderer, className: 'htCenter htMiddle', width: 100 }
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
                    
                    // Clear ค่าเก่าใน Modal ก่อนเปิด (เพิ่มการเช็ค null)
                    const inAreaId = document.getElementById('in_area_id');
                    if (inAreaId) inAreaId.value = '';
                    
                    const inAcId = document.getElementById('in_ac_id');
                    if (inAcId) inAcId.value = '';
                    
                    const inArId = document.getElementById('in_ar_id');
                    if (inArId) inArId.value = '';
                    
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
                    
                    if (prop === 'location' && !changedPropsInThisBatch.includes('ac_name')) {
                        hot.setDataAtRowProp(row, 'ac_name', '');
                        hot.setDataAtRowProp(row, 'ar_name', '');
                    } else if (prop === 'ac_name' && !changedPropsInThisBatch.includes('ar_name')) {
                        hot.setDataAtRowProp(row, 'ar_name', '');
                    }
                    // ----------------------------------------
                    
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
                    target['area_name'] = bName || '';
                    target['ac_name'] = fName || '';
                    target['ar_name'] = rName || '';
                }
            }
        }

        // --- Interactions ---
        document.getElementById('search-input').addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { fetchMetersData(1); }, 500);
        });

        document.getElementById('page-size').addEventListener('change', (e) => {
            pageSize = parseInt(e.target.value); fetchMetersData(1); 
        });

        document.getElementById('btn-add-row').addEventListener('click', () => {
            preserveCurrentState(); 
            const count = parseInt(document.getElementById('row-count-input').value) || 1;
            
            for(let i = 0; i < count; i++) {
                const newObj = {
                    id: tempIdCounter--, 
                    mt_type: "มิเตอร์ไฟ", 
                    mt_name: "", 
                    area_id: null, area_name: "",
                    ac_id: null, ac_name: "",
                    ar_id: null, ar_name: "",
                    full_location: "",
                    status: "0"
                };
                cachedNewRows.unshift(newObj); 
                newRowIds.add(newObj.id);
            }
            fetchMetersData(currentPage); 
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
                            
                            // ยิง API ไปที่หน้าเซฟ เพื่อส่งคำสั่งลบทันที (ส่ง data เป็น array ว่างๆ เพราะไม่ได้บันทึกอะไรใหม่)
                            const response = await axios.post('handle_meters.php?action=save', { 
                                data: [], 
                                deleted_ids: idsToDeleteFromDB,
                                ag_id: AG_ID, 
                                user_id: USER_ID
                            });

                            if (response.data && response.data.success) {
                                Swal.fire({ title: 'ลบข้อมูลสำเร็จ!', icon: 'success', timer: 1500 });
                                
                                // รีเฟรชดึงข้อมูลตารางใหม่ เพื่อให้ตรงกับ DB จริงๆ
                                await fetchMetersData(currentPage);
                                updateDeleteButtonVisibility(); // ซ่อนปุ่มลบ
                                return; // จบการทำงาน ไม่ต้องทำโค้ดด้านล่างต่อ เพราะตารางรีเฟรชแล้ว
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

            // ฟังก์ชันสำหรับแปลงชื่อสถานที่ (Name) เป็นรหัส (ID) จาก buildingData
            const mapLocationIds = (rowData) => {
                let area_id = null, ac_id = null, ar_id = null;
                
                // เช็คว่ามีตัวแปร buildingData และโหลดมาแล้ว
                if (rowData.area_name && typeof buildingData !== 'undefined' && buildingData.length > 0) {
                    // 1. หาอาคาร (Area)
                    const areaObj = buildingData.find(b => b.name === rowData.area_name);
                    if (areaObj) {
                        area_id = areaObj.id || areaObj.area_id || null; 
                        
                        // 2. หาชั้น (Ac)
                        if (rowData.ac_name && areaObj.floors) {
                            const floorObj = areaObj.floors.find(f => f.name === rowData.ac_name);
                            if (floorObj) {
                                ac_id = floorObj.id || floorObj.ac_id || null;
                                
                                // 3. หาห้อง (Ar)
                                if (rowData.ar_name && floorObj.rooms) {
                                    const roomObj = floorObj.rooms.find(r => r.name === rowData.ar_name);
                                    if (roomObj) {
                                        ar_id = roomObj.id || roomObj.ar_id || null;
                                    }
                                }
                            }
                        }
                    }
                }
                
                // นำ ID ที่หาได้ไปอัปเดตใส่กลับใน Payload
                rowData.area_id = area_id;
                rowData.ac_id = ac_id;
                rowData.ar_id = ar_id;
                
                return rowData;
            };
            // -------------------------------------------------------------

            cachedNewRows.forEach(row => {
                // เช็คว่าชื่อมิเตอร์ว่างไหม
                const isRowEmpty = (!row.mt_name || String(row.mt_name).trim() === '');
                
                if (isRowEmpty) {
                    newRowIds.delete(row.id);
                } else {
                    validNewRows.push(row);
                    let rowData = { ...row };
                    rowData.id = 0; 
                    
                    rowData = mapLocationIds(rowData); 
                    
                    dataToSave.push(rowData);
                }
            });

            cachedNewRows = validNewRows;

            cachedDirtyRows.forEach((row, id) => {
                let rowData = { ...row };
                if (rowData.max_val !== undefined) {
                    // แปลงเป็นตัวเลขและเอาลูกน้ำออก (ถ้ามี)
                    rowData.max_val = Number(String(rowData.max_val).replace(/,/g, '')) || 1000000;
                }
                rowData = mapLocationIds(rowData); 
                
                dataToSave.push(rowData);
            });

            if (dataToSave.length === 0) {
                updateCounter();
                fetchMetersData(currentPage); 
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
                    const response = await axios.post('handle_meters.php?action=save', { 
                        data: dataToSave, 
                        deleted_ids: deletedIds,
                        ag_id: AG_ID, 
                        user_id: USER_ID
                    });

                    if (response.data && response.data.success) {
                        Swal.fire({ title: 'บันทึกสำเร็จ!', icon: 'success', timer: 1500 });
                        
                        dirtyCells.clear(); 
                        newRowIds.clear(); 
                        deletedIds = []; 
                        cachedNewRows = [];
                        cachedDirtyRows.clear();
                        updateCounter();
                        
                        fetchMetersData(currentPage); 
                    } else {
                        Swal.fire('ข้อผิดพลาด', response.data.error || 'ไม่สามารถบันทึกได้', 'error');
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
                const response = await axios.get(`handle_meters.php?action=get_all&ag_id=${AG_ID}&search=${encodeURIComponent(searchVal)}&limit=999999&offset=0`);
                
                if (response.data && response.data.success && response.data.data.length > 0) {
                    const serverData = response.data.data;
                    const exportData = serverData.map(item => {
                        let locParts = [];
                        if (item.area_name) locParts.push(item.area_name);
                        if (item.ac_name) locParts.push(item.ac_name);
                        if (item.ar_name) locParts.push(item.ar_name);
                        let full_location = locParts.length > 0 ? locParts.join(' ') : '';

                        return {
                            'ประเภทมิเตอร์': item.mt_type || '',
                            'ชื่อมิเตอร์': item.mt_name || '',
                            'สถานที่': full_location,
                            'สถานะ': String(item.status) === '0' ? 'Active' : 'Inactive'
                        };
                    });

                    const worksheet = XLSX.utils.json_to_sheet(exportData);
                    const workbook = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(workbook, worksheet, "MetersData");
                    
                    const date = new Date();
                    const dateString = `${date.getFullYear()}${(date.getMonth()+1).toString().padStart(2,'0')}${date.getDate().toString().padStart(2,'0')}`;
                    const fileName = `Meters_Export_${dateString}.xlsx`;
                    
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
            await fetchBuildingData();
            await fetchMetersData();
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