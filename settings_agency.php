<?php 
@session_start();
include "config_ctrl/checksession.php"; 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการพื้นที่ (Responsive View)</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap');
        
        :root {
            --color-bg: #f8fafc;
            --color-primary: #006B9F;
            --color-primary-dark: #004a6f;
            --color-secondary: #04ADFF;
        }

        body { font-family: 'Prompt', sans-serif; background-color: var(--color-bg); font-size: 13px; }
        
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

        /* ปรับขนาดฟอนต์ในตารางให้เล็กลง */
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
        .handsontable th { background-color: #f1f5f9 !important; font-weight: 600 !important; color: #334155 !important; padding: 0 4px !important; }
        .checker-header { margin: 0; cursor: pointer; width: 12px; height: 12px; }
        
        .floor-cell {
            background-color: #ffffff !important;
            font-weight: 600 !important;
            color: var(--color-primary) !important;
            text-align: center !important;
        }

        /* Highlighting Logic */
        td.row-new-cell { background-color: #f0fdf4 !important; color: #166534 !important; }
        td.cell-edited { background-color: #fffbeb !important; color: #92400e !important; }

        /* ปรับขนาด padding ของสถานะให้บางลง */
        .status-active { color: #0369a1; padding: 4px; font-weight: 600; }
        .status-inactive { color: #64748b; padding: 4px; font-weight: 600; }

        .building-card { transition: all 0.2s; border-left: 3px solid transparent; }
        .building-card:hover { background-color: #f1f5f9; }
        .building-card.active { 
            background-color: #e4f6ff; 
            box-shadow: 0 2px 4px -1px rgb(0 0 0 / 0.1); 
        }

        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* สไตล์สำหรับ Pagination ให้เล็กลง */
        .page-link {
            padding: 4px 10px; border: 1px solid #e2e8f0; background: white; color: #64748b;
            border-radius: 4px; cursor: pointer; transition: all 0.2s; font-size: 12px; font-weight: 500;
        }
        .page-link:hover { background: #f1f5f9; color: var(--color-primary); }
        .page-link.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }
        .page-link:disabled { opacity: 0.5; cursor: not-allowed; }

        @media (max-width: 768px) {
            /* ปรับความกว้าง sidebar บนมือถือให้เล็กลง */
            #sidebar { position: fixed; left: -100%; top: 0; bottom: 0; width: 250px; z-index: 50; transition: left 0.3s ease-in-out; }
            #sidebar.open { left: 0; }
            #overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 40; }
            #overlay.open { display: block; }
        }
    </style>
</head>
<body class="h-screen flex flex-col overflow-hidden text-[13px]">
    <div id="overlay" onclick="toggleSidebar()"></div>

    <div class="flex-1 flex overflow-hidden">
        <aside id="sidebar" class="w-60 bg-white border-r border-slate-200 flex flex-col shadow-sm shrink-0 overflow-hidden">
            <div class="px-3 py-1.5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center shrink-0">
                <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider">รายชื่ออาคาร</h3>
                <div class="flex flex-row items-center space-x-1.5">
                    <button onclick="exportAllToExcel()" title="Export ทั้งหมดทุกอาคาร" class="text-green-600 hover:bg-green-50 rounded p-1 transition-colors">
                        <i class="fa-solid fa-file-excel text-base"></i>
                    </button>
                    <button onclick="addNewBuilding()" title="เพิ่มอาคารใหม่" class="text-sky-600 hover:bg-sky-50 rounded p-1 transition-colors">
                        <i class="fa-solid fa-plus-circle text-base"></i>
                    </button>
                </div>
            </div>
            <div class="p-2 bg-white border-b border-slate-100">
                <div class="relative">
                    <i class="fa-solid fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                    <input type="text" id="building-search" onkeyup="renderSidebar()" placeholder="ค้นหาอาคาร..." class="w-full pl-7 pr-2 py-1.5 text-xs border border-slate-200 rounded-md focus:outline-none focus:ring-1 focus:ring-sky-500/50 focus:border-sky-500 transition-all">
                </div>
            </div>
            <div id="building-list" class="mt-2 space-y-0.5 overflow-y-auto h-[calc(100vh-140px)] px-1.5 custom-scrollbar"></div>
        </aside>

        <main class="flex-1 flex flex-col bg-slate-50 min-w-0">
            <div id="table-toolbar" class="px-3 md:px-4 py-2 border-b border-slate-200 bg-white/80 backdrop-blur flex flex-col lg:flex-row justify-between items-start lg:items-center gap-2 shrink-0">
                
                <div class="flex flex-col gap-1 w-full lg:w-auto">
                    <h2 id="current-building-title" class="hidden md:block text-base font-bold text-slate-800">
                        <span class="text-slate-400 font-normal">กรุณาเลือกอาคาร</span>
                    </h2>
                    
                    <div class="md:hidden flex items-center gap-1.5 w-full sm:max-w-md">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none text-[#006B9F]">
                                <i class="fa-solid fa-building text-xs"></i>
                            </div>
                            <select id="mobile-building-select" onchange="if(this.value) selectBuilding(this.value)" class="w-full appearance-none bg-white border border-slate-300 text-slate-800 text-xs font-bold rounded-lg pl-7 pr-8 py-1.5 focus:outline-none focus:ring-1 focus:ring-sky-500/50 focus:border-[#006B9F] shadow-sm truncate cursor-pointer transition-all">
                                <option value="" disabled selected>-- กรุณาเลือกอาคาร --</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 text-slate-400">
                                <i class="fa-solid fa-chevron-down text-[10px]"></i>
                            </div>
                        </div>
                        <button onclick="addNewBuilding()" class="shrink-0 w-8 h-8 flex items-center justify-center bg-sky-50 border border-sky-200 text-sky-600 rounded-lg hover:bg-sky-100 hover:text-sky-700 transition-colors shadow-sm" title="เพิ่มอาคารใหม่">
                            <i class="fa-solid fa-plus text-sm"></i>
                        </button>
                        <button onclick="exportAllToExcel()" class="shrink-0 w-8 h-8 flex items-center justify-center bg-green-50 border border-green-200 text-green-600 rounded-lg hover:bg-green-100 hover:text-green-700 transition-colors shadow-sm" title="Export ทั้งหมด">
                            <i class="fa-solid fa-file-excel text-sm"></i>
                        </button>
                    </div>
                </div>

                <div id="action-buttons" class="hidden w-full lg:w-auto flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-1.5 mt-2 lg:mt-0">
                    
                    <div class="relative w-full sm:w-64 order-first sm:order-none">
                        <i class="fa-solid fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                        <input type="text" id="room-search" onkeyup="handleRoomSearch(event)" placeholder="ค้นหาห้อง/ชั้น..." class="w-full pl-7 pr-2 py-1.5 text-xs border border-slate-200 rounded-md focus:outline-none focus:ring-1 focus:ring-sky-500/50 focus:border-sky-500 transition-all shadow-sm">
                    </div>

                    <div id="change-counter" class="hidden w-full sm:w-fit px-2 py-1 bg-amber-50 text-amber-700 text-[10px] font-bold rounded-md sm:rounded-full border border-amber-200 shadow-sm animate-pulse text-center">
                        <i class="fa-solid fa-circle-exclamation mr-1"></i> รอการบันทึก: <span id="count-num">0</span>
                    </div>
                    
                    <div class="flex items-center bg-white border border-slate-200 rounded-md overflow-hidden h-8 shadow-sm w-full sm:w-auto">
                        <input type="number" id="row-count-input" value="1" min="1" max="50" class="w-16 sm:w-10 h-full text-center text-xs font-semibold text-slate-600 focus:outline-none border-r border-slate-100 bg-slate-50/50">
                        <button onclick="addMultipleRows()" class="flex-1 sm:flex-none h-full px-2.5 bg-slate-50 sm:bg-white text-slate-600 text-xs font-medium hover:bg-sky-50 hover:text-sky-600 transition-all flex items-center justify-center gap-1">
                            <i class="fa-solid fa-plus text-sky-500 text-[10px]"></i> <span>เพิ่มแถว</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-1.5 w-full sm:w-auto">
                        <button onclick="exportToExcel()" class="flex-1 sm:flex-none h-8 px-3 bg-emerald-600 text-white rounded-md text-xs font-semibold hover:bg-emerald-700 transition-all shadow-sm flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-file-excel"></i> Export
                        </button>
                    </div>

                    <div id="header-actions" class="hidden w-full sm:w-auto">
                        <button onclick="saveAllData()" class="w-full sm:w-auto h-8 px-3 py-1.5 bg-[#006B9F] hover:bg-[#005a85] text-white text-xs font-medium rounded-md shadow-sm transition-all flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-save"></i> <span>บันทึกทั้งหมด</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex-1 p-2 md:p-3 overflow-hidden flex flex-col">
                <div id="empty-state" class="flex-1 flex flex-col items-center justify-center text-slate-400 border-2 border-dashed border-slate-200 rounded-xl bg-white m-1">
                    <i class="fa-regular fa-building text-3xl text-slate-300 mb-2"></i>
                    <p class="text-base font-semibold text-slate-500">ยังไม่มีอาคารที่ถูกเลือก</p>
                </div>
                <div id="hot-display" class="hidden w-full h-full rounded-lg overflow-hidden shadow-sm border border-slate-200 bg-white"></div>
            </div>

            <div class="mx-3 mb-1.5 flex flex-col md:flex-row justify-between items-center text-[10px] text-slate-500 font-medium px-2">
                <div class="flex gap-3 sm:gap-4 uppercase tracking-wider w-full md:w-auto">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 bg-amber-100 border border-amber-200 rounded-sm"></span> มีการแก้ไข</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 bg-green-100 border border-green-200 rounded-sm"></span> รายการใหม่</span>
                </div>
            </div>
            
            <div id="table-footer" class="flex flex-col md:flex-row justify-between items-center text-[10px] text-slate-500 font-medium px-1 gap-2">
                <div class="px-3 py-2 bg-white border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2 w-full">
                    <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-start">
                        <span class="text-[11px] text-slate-500 font-medium">แสดง</span>
                        <select id="page-size-select" onchange="changePageSize(this.value)" class="text-[11px] border border-slate-200 rounded p-1 focus:outline-none focus:ring-1 focus:ring-sky-100">
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
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.js"></script>
    <script src="js/handsontable-light.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

    <script>
        let database = {};
        let currentBuildingId = null;
        let hot = null;
        let editedCells = {}; 
        const container = document.getElementById('hot-display');

        // State สำหรับ Pagination & Search
        let currentBuildingData = []; 
        let currentPage = 1;
        let pageSize = 20;
        let searchQuery = "";

        const AG_ID = '<?php echo isset($sess_user_agency_es) ? $sess_user_agency_es : ""; ?>';
        const USER_ID = '<?php echo isset($sess_user_id) ? $sess_user_id : ""; ?>';

        function generateUID() {
            return Date.now().toString(36) + Math.random().toString(36).substr(2);
        }

        async function initApp() {
            await fetchDatabase(false); 
            initHandsontable();
            const buildingIds = Object.keys(database);
            if (buildingIds.length > 0) selectBuilding(buildingIds[0]);
        }

        async function fetchDatabase(isForTable = false) {
            try {
                let url = `handle_agency.php?action=get_all&ag_id=${AG_ID}`;
                if (isForTable && currentBuildingId) {
                    url += `&area_id=${currentBuildingId}&page=${currentPage}&limit=${pageSize}`;
                    if (searchQuery) {
                        url += `&search=${encodeURIComponent(searchQuery)}`;
                    }
                }
                const response = await fetch(url);
                const result = await response.json();

                if (result.success) {
                    // ฟังก์ชันช่วยแตกข้อมูล (Flatten) จาก ชั้น->ห้อง ให้เป็นตารางแบนๆ
                    const flattenData = (floors) => {
                        let flat = [];
                        if (!floors) return flat;
                        floors.forEach(f => {
                            if (f.rooms && f.rooms.length > 0) {
                                f.rooms.forEach(r => {
                                    flat.push({
                                        floor_id: f.floor_id,
                                        floor: f.floor,
                                        floor_status: f.floor_status,
                                        room_id: r.room_id,
                                        room_name: r.room_name,
                                        status: r.status,
                                        _uid: generateUID()
                                    });
                                });
                            } else {
                                flat.push({
                                    floor_id: f.floor_id,
                                    floor: f.floor,
                                    floor_status: f.floor_status,
                                    room_id: null,
                                    room_name: null,
                                    status: null,
                                    _uid: generateUID()
                                });
                            }
                        });
                        return flat;
                    };

                    if (!isForTable) {
                        database = result.data;
                        Object.keys(database).forEach(bId => {
                            // แตกข้อมูลและเก็บกลับเข้าไปในรูปแบบ rooms เดิมให้หน้าบ้านใช้
                            database[bId].rooms = flattenData(database[bId].floors); 
                        });
                        renderSidebar();
                    } else {
                        const buildingData = result.data[currentBuildingId];
                        currentBuildingData = buildingData ? flattenData(buildingData.floors) : [];
                        hot.loadData(currentBuildingData);
                        
                        const mergeSettings = calculateMergeCells(currentBuildingData);
                        hot.updateSettings({ mergeCells: mergeSettings.length > 0 ? mergeSettings : true });
                        
                        if (result.pagination) {
                            renderClientPagination(result.pagination.total_pages, result.pagination.total_records);
                        }
                    }
                }
            } catch (error) {
                console.error("Error fetching data:", error);
            }
        }

        function updateChangeCounter() {
            const newRowsCount = currentBuildingData.filter(row => row._status === 'new').length;
            const editedCellsCount = Object.keys(editedCells).length;

            const totalChanges = newRowsCount + editedCellsCount;
            const counterEl = document.getElementById('change-counter');
            const countNumEl = document.getElementById('count-num');
            const refreshBtn = document.getElementById('btn-refresh-group'); 
            
            // อัปเดตตัวเลขเสมอ ไม่ว่าจะเคลียร์ค่าแล้วหรือไม่ก็ตาม
            if (countNumEl) {
                countNumEl.innerText = totalChanges; 
            }

            if (totalChanges > 0) {
                counterEl.classList.remove('hidden');
                if (refreshBtn) refreshBtn.disabled = true;  
            } else {
                counterEl.classList.add('hidden');
                if (refreshBtn) refreshBtn.disabled = false; 
            }
        }

        function statusCheckboxRenderer(instance, td, row, col, prop, value, cellProperties) {
            Handsontable.renderers.CheckboxRenderer.apply(this, arguments);
            const physicalRow = instance.toPhysicalRow(row);
            const sourceData = instance.getSourceDataAtRow(physicalRow);
            
            if (sourceData && sourceData._status === 'new') {
                td.classList.add('row-new-cell');
            } else if (sourceData && editedCells[`${sourceData._uid}:${prop}`]) {
                td.classList.add('cell-edited');
            }

            td.style.display = 'table-cell';
            td.style.verticalAlign = 'middle';
            td.style.textAlign = 'center';

            const isActive = (String(value) === '0');
            const label = document.createElement('span');
            label.className = isActive ? 'status-active text-[11px]' : 'status-inactive text-[11px]';
            label.innerText = isActive ? 'Active' : 'Inactive';
            
            // ใช้ margin-left จัดช่องไฟแทนการใช้ gap ของ flex
            label.style.marginLeft = '6px';
            label.style.display = 'inline-block';
            label.style.verticalAlign = 'middle';
            
            const input = td.querySelector('input');
            if (input) {
                input.className = 'w-3 h-3 cursor-pointer';
                input.style.margin = '0';
                input.style.verticalAlign = 'middle'; // ให้อยู่กึ่งกลางระนาบเดียวกับข้อความ
            }

            // เคลียร์ Text Node ว่างและนำ label เข้าไปต่อท้าย input
            Array.from(td.childNodes).forEach(node => { if (node.nodeType === Node.TEXT_NODE) node.remove(); });
            td.appendChild(label);
            
            return td;
        }

        function initHandsontable() {
            hot = new Handsontable(container, {
                data: [],
                colHeaders: ['ชั้น', 'ชื่อห้อง / โซน', 'สถานะชั้น', 'สถานะห้อง'],
                columns: [
                    { data: 'floor', type: 'text', width: 100 },
                    { data: 'room_name', type: 'text', width: 250 },
                    { 
                        data: 'floor_status', 
                        type: 'checkbox', 
                        className: 'htCenter htMiddle', 
                        width: 50,
                        checkedTemplate: '0', 
                        uncheckedTemplate: '1',
                        renderer: statusCheckboxRenderer
                    },
                    { data: 'status', type: 'checkbox', width: 50, checkedTemplate: '0', uncheckedTemplate: '1', renderer: statusCheckboxRenderer, className: 'htCenter htMiddle' }
                ],
                cells: function(row, col) {
                    const cellPrp = {};
                    const physicalRow = this.instance.toPhysicalRow(row);
                    const sourceData = this.instance.getSourceDataAtRow(physicalRow);
                    let classes = [];

                    if (col === 0) classes.push('floor-cell', 'htMiddle');
                    if (col === 1) classes.push('htLeft', 'htMiddle', 'font-medium', 'text-slate-700');
                    if (col === 2) classes.push('htCenter', 'htMiddle');

                    if (sourceData && sourceData._status === 'new') {
                        classes.push('row-new-cell');
                    } else if (sourceData && editedCells[`${sourceData._uid}:${this.instance.colToProp(col)}`]) {
                        classes.push('cell-edited');
                    }
                    
                    cellPrp.className = classes.join(' ');
                    return cellPrp;
                },
                afterChange: (changes, source) => {
                    const userActionSources = ['edit', 'CopyPaste.paste', 'Autofill.fill', 'undo', 'redo'];
                    if (!userActionSources.includes(source)) return;

                    changes.forEach(([row, prop, oldValue, newValue]) => {
                        if (oldValue === newValue) return;
                        
                        const physicalRow = hot.toPhysicalRow(row);
                        const rowData = hot.getSourceDataAtRow(physicalRow);

                        if (rowData) {
                            // 1. อัปเดตข้อมูลใน Master Data (currentBuildingData)
                            const masterIndex = currentBuildingData.findIndex(r => r._uid === rowData._uid);
                            if (masterIndex !== -1) {
                                currentBuildingData[masterIndex][prop] = newValue;
                                if (currentBuildingData[masterIndex]._status !== 'new') {
                                    editedCells[`${rowData._uid}:${prop}`] = true;
                                }

                                // --- 🚀 LOGIC: เปลี่ยนสถานะห้องตามสถานะชั้น ---
                                if (prop === 'floor_status') {
                                    const targetFloorId = rowData.floor_id;
                                    const targetFloorName = rowData.floor; // สำรองไว้เผื่อเป็นรายการใหม่ที่ยังไม่มี ID

                                    currentBuildingData.forEach((item, idx) => {
                                        // ตรวจสอบเงื่อนไข: ชั้นเดียวกัน (เช็ค ID หรือชื่อ)
                                        const isSameFloor = targetFloorId ? (item.floor_id === targetFloorId) : (item.floor === targetFloorName);
                                        
                                        if (isSameFloor) {
                                            // เปลี่ยนสถานะห้องให้เหมือนสถานะชั้น
                                            item.status = newValue; 
                                            item.floor_status = newValue; // อัปเดตสถานะชั้นในแถวอื่นๆ ของชั้นเดียวกันด้วย

                                            // บันทึกว่ามีการแก้ไข (ยกเว้นรายการใหม่)
                                            if (item._status !== 'new') {
                                                editedCells[`${item._uid}:status`] = true;
                                                editedCells[`${item._uid}:floor_status`] = true;
                                            }
                                        }
                                    });
                                }
                            }
                        }
                    });

                    updateChangeCounter();
                    hot.render(); // สั่งวาดตารางใหม่เพื่อแสดงผลค่าที่เปลี่ยนไป
                },
                mergeCells: true, 
                rowHeaders: function(index) {
                    return (currentPage - 1) * pageSize + index + 1;
                },
                height: '100%', 
                width: '100%', 
                stretchH: 'all',
                licenseKey: 'non-commercial-and-evaluation'
            });
        }

        function selectBuilding(id) {
            if (currentBuildingId) saveCurrentTableToMemory();
            currentBuildingId = id;
            
            renderSidebar(); 
            
            const building = database[id]; 
            document.getElementById('current-building-title').innerHTML = `<i class="fa-solid fa-building text-[#006B9F] mr-1"></i> ${building.name}`;
            document.getElementById('header-actions').classList.remove('hidden');
            document.getElementById('action-buttons').classList.remove('hidden');
            document.getElementById('action-buttons').classList.add('flex');
            document.getElementById('empty-state').classList.add('hidden');
            document.getElementById('hot-display').classList.remove('hidden');
            
            // แก้ไขการโชว์ Pagination control
            document.querySelector('#pagination-nav').parentElement.classList.remove('hidden');
            document.getElementById('room-search').value = '';
            
            editedCells = {}; 
            searchQuery = "";
            currentPage = 1;
            
            currentBuildingData = building.rooms ? JSON.parse(JSON.stringify(building.rooms)) : [];
            
            applyFiltersAndPagination();
            updateChangeCounter();
        }

        function handleRoomSearch(e) {
            searchQuery = e.target.value;
            currentPage = 1; 
            applyFiltersAndPagination();
        }

        function changePageSize(size) {
            if (Object.keys(editedCells).length > 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาบันทึกข้อมูลที่แก้ไขก่อนเปลี่ยนหน้า', 'warning');
                document.getElementById('page-size-select').value = pageSize; // คืนค่า select กลับ
                return;
            }
            pageSize = parseInt(size);
            currentPage = 1;
            applyFiltersAndPagination();
        }

        function changePage(direction) {
            if (Object.keys(editedCells).length > 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาบันทึกข้อมูลที่แก้ไขก่อนเปลี่ยนหน้า', 'warning');
                return;
            }
            currentPage += direction;
            applyFiltersAndPagination();
        }

        function applyFiltersAndPagination() {
            fetchDatabase(true);
            updateChangeCounter();
        }

        function renderClientPagination(totalPages, totalItems) {
            const paginationNav = document.getElementById('pagination-nav');
            if (!paginationNav) return;
            
            paginationNav.innerHTML = '';
            
            // ฟังก์ชันตัวช่วยสำหรับสร้างปุ่ม
            const createBtn = (content, targetPage, active = false, disabled = false) => {
                const btn = document.createElement('button');
                btn.className = `page-link ${active ? 'active' : ''}`;
                btn.innerHTML = content; 
                btn.disabled = disabled;
                btn.onclick = () => { 
                    currentPage = targetPage; 
                    applyFiltersAndPagination(); 
                };
                return btn;
            };
            
            // ปุ่มย้อนกลับ
            paginationNav.appendChild(createBtn('<i class="fa-solid fa-chevron-left"></i>', currentPage - 1, false, currentPage <= 1));
            
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);
            
            if (startPage > 1) paginationNav.appendChild(createBtn('1', 1, false));
            if (startPage > 2) {
                let ellipsis = document.createElement('span');
                ellipsis.className = 'px-1 py-0.5 text-slate-400'; ellipsis.innerText = '...';
                paginationNav.appendChild(ellipsis);
            }

            for (let i = startPage; i <= endPage; i++) {
                paginationNav.appendChild(createBtn(i, i, i === currentPage));
            }

            if (endPage < totalPages - 1) {
                let ellipsis = document.createElement('span');
                ellipsis.className = 'px-1 py-0.5 text-slate-400'; ellipsis.innerText = '...';
                paginationNav.appendChild(ellipsis);
            }
            if (endPage < totalPages) paginationNav.appendChild(createBtn(totalPages, totalPages, false));

            // ปุ่มถัดไป
            paginationNav.appendChild(createBtn('<i class="fa-solid fa-chevron-right"></i>', currentPage + 1, false, currentPage >= totalPages));
            
            // เพิ่มข้อความบอกจำนวนรายการต่อท้ายแบบเนียนๆ
            let infoText = document.createElement('div');
            infoText.className = 'text-[11px] text-slate-400 font-normal ml-2 flex items-center hidden lg:flex';
            infoText.innerText = `(ทั้งหมด ${totalItems} รายการ)`;
            paginationNav.appendChild(infoText);
        }

        window.addMultipleRows = function() {
            if (!currentBuildingId) return;
            
            const count = parseInt(document.getElementById('row-count-input').value) || 1;
            
            const newRows = Array.from({length: count}, () => ({ 
                _uid: generateUID(), 
                floor: '', 
                room_name: '', 
                floor_status: '0',
                status: '0',
                _status: 'new' 
            }));
        
            currentBuildingData = [...newRows, ...currentBuildingData];
            hot.loadData(currentBuildingData);

            const mergeSettings = calculateMergeCells(currentBuildingData);
            hot.updateSettings({ mergeCells: mergeSettings.length > 0 ? mergeSettings : true });
            
            updateChangeCounter();
            container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        };

        window.saveAllData = async function() {
            // 1. นำข้อมูลที่จำเป็นมาเตรียมบันทึก
            const roomsToSave = currentBuildingData.filter(row => row.floor || row.room_name);
            const cleanRooms = roomsToSave.map(r => {
                const cleanRow = { ...r };
                delete cleanRow._status;
                delete cleanRow._uid;
                return cleanRow;
            });
            
            Swal.fire({ title: 'กำลังบันทึกข้อมูล...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

            try {
                const response = await fetch('handle_agency.php?action=save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        area_id: currentBuildingId, 
                        rooms: cleanRooms, 
                        user_id: USER_ID 
                    })
                });
                const result = await response.json();

                if (result.success) {
                    // 2. อัปเดตข้อมูลในหน่วยความจำ (database หลัก) เป็นข้อมูลใหม่ที่คลีนแล้ว
                    database[currentBuildingId].rooms = cleanRooms.map(r => ({...r, _uid: generateUID()}));
                    
                    // --- จุดที่แก้ไขเพื่อแก้ปัญหากด 2 ครั้ง ---
                    const tempId = currentBuildingId;
                    // เคลียร์ค่า ID ปัจจุบันทิ้งชั่วคราว เพื่อไม่ให้ฟังก์ชัน selectBuilding สั่ง saveCurrentTableToMemory ทับของใหม่
                    currentBuildingId = null; 
                    
                    // 3. รีเฟรชตารางใหม่ด้วยข้อมูลที่ถูกต้อง
                    selectBuilding(tempId);
                    
                    // 4. บังคับอัปเดตตัวนับและซ่อนปุ่ม
                    const counterEl = document.getElementById('change-counter');
                    const countNumEl = document.getElementById('count-num');
                    if (counterEl) counterEl.classList.add('hidden');
                    if (countNumEl) countNumEl.innerText = '0';
                    
                    Swal.fire({ title: 'บันทึกสำเร็จ', icon: 'success', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', result.error || 'ไม่สามารถบันทึกข้อมูลได้', 'error');
                }
            } catch (error) {
                Swal.fire('ข้อผิดพลาด', 'มีบางอย่างผิดปกติในการติดต่อกับเซิร์ฟเวอร์', 'error');
                console.error(error);
            }
        };

        function saveCurrentTableToMemory() {
            if (!currentBuildingId) return;
            database[currentBuildingId].rooms = currentBuildingData.filter(row => row.floor || row.room_name);
        }

        function renderSidebar() {
            const listContainer = document.getElementById('building-list');
            const mobileSelect = document.getElementById('mobile-building-select'); 
            const searchVal = document.getElementById('building-search').value.toLowerCase();
            
            listContainer.innerHTML = '';
            
            // ล้างค่าใน Mobile Select และใส่ Placeholder กลับเข้าไป
            if (mobileSelect) {
                mobileSelect.innerHTML = '<option value="" disabled selected>-- กรุณาเลือกอาคาร --</option>';
            }

            Object.keys(database).forEach(id => {
                const building = database[id];
                
                // 1. ส่วนของ Desktop Sidebar (ปรับคลาสให้เล็กลง)
                if(building.name.toLowerCase().includes(searchVal)) {
                    const isActive = (building.status == 0); 
                    const statusIcon = isActive ? 'fa-circle-check text-sky-500' : 'fa-circle-xmark text-slate-300';
                    const nameClass = isActive ? 'text-slate-700 font-medium' : 'text-slate-400 line-through';
                    const actionIcon = (building.status == 0) ? 'fa-toggle-on text-sky-500' : 'fa-toggle-off text-slate-300';
                    
                    const item = document.createElement('div');
                    item.className = `building-card p-1 rounded-lg cursor-pointer border border-transparent mb-1 ${currentBuildingId === id ? 'active' : ''} group`;
                    item.innerHTML = `
                        <div class="flex items-center justify-between w-full">
                            <div class="flex items-center gap-1.5 overflow-hidden flex-1" onclick="selectBuilding('${id}')">
                                <i class="fa-solid ${statusIcon} shrink-0 text-[10px]"></i>
                                <span class="truncate text-xs ${nameClass}">${building.name}</span>
                            </div>
                            <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button onclick="event.stopPropagation(); editBuildingName('${id}', '${building.name}')" class="p-1 text-slate-400 hover:text-sky-600">
                                    <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                                </button>
                                <button onclick="event.stopPropagation(); toggleBuildingStatus('${id}', ${building.status})" 
                                        class="p-0.5 hover:scale-110 transition-transform">
                                    <i class="fa-solid ${actionIcon} text-sm"></i>
                                </button>
                            </div>
                        </div>`;
                    listContainer.appendChild(item);

                    // 2. ส่วนของ Mobile Select
                    if (mobileSelect && building.status == 0) {
                        const option = document.createElement('option');
                        option.value = id;
                        option.text = building.name;
                        if (currentBuildingId === id) option.selected = true;
                        mobileSelect.appendChild(option);
                    }
                }
            });
        }

        function calculateMergeCells(data) {
            const mergeCells = [];
            let startRow = 0;
            
            for (let i = 1; i <= data.length; i++) {
                if (i === data.length || data[i].floor !== data[i - 1].floor) {
                    if (i - startRow > 1) {
                        // เช็คว่าต้องมีข้อมูลชื่อชั้นถึงจะยอมให้ Merge (ป้องกันการ Merge ค่าว่างตอนกดเพิ่มแถวใหม่)
                        if (data[startRow].floor !== null && String(data[startRow].floor).trim() !== '') {
                            mergeCells.push({ row: startRow, col: 0, rowspan: i - startRow, colspan: 1 });
                            mergeCells.push({ row: startRow, col: 2, rowspan: i - startRow, colspan: 1 });
                        }
                    }
                    startRow = i;
                }
            }
            return mergeCells;
        }

        window.refreshAndGroup = function() { 
            if(currentBuildingId) {
                currentBuildingData.sort((a, b) => String(a.floor || '').localeCompare(String(b.floor || ''), undefined, { numeric: true }));
                currentPage = 1;
                applyFiltersAndPagination(); 
            }
        };

        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); document.getElementById('overlay').classList.toggle('open'); }

        window.addNewBuilding = async function() {
            const { value: name } = await Swal.fire({ 
                title: 'สร้างอาคารใหม่', 
                input: 'text', 
                showCancelButton: true,
                inputValidator: (value) => { if (!value) return 'กรุณาระบุชื่ออาคาร!'; }
            });

            if (name) { 
                try {
                    const response = await fetch('handle_agency.php?action=add_building', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ area_name: name, ag_id: AG_ID, user_id: USER_ID })
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        const newId = result.area_id; 
                        database[newId] = { name: name, rooms: [] }; 
                        renderSidebar(); 
                        selectBuilding(newId);
                        Swal.fire({ title: 'เพิ่มอาคารสำเร็จ', icon: 'success', timer: 1000, showConfirmButton: false });
                    } else {
                        Swal.fire('เกิดข้อผิดพลาด', result.error || 'ไม่สามารถเพิ่มอาคารได้', 'error');
                    }
                } catch (error) { console.error(error); }
            }
        };

        window.editBuildingName = async function(id, currentName) {
            const { value: newName } = await Swal.fire({
                title: 'แก้ไขชื่ออาคาร',
                input: 'text',
                inputValue: currentName,
                showCancelButton: true,
                confirmButtonText: 'บันทึก',
                cancelButtonText: 'ยกเลิก',
                inputValidator: (value) => { if (!value) return 'กรุณาระบุชื่ออาคาร!'; }
            });
            if (newName && newName !== currentName) {
                updateBuildingAPI(id, { area_name: newName });
            }
        };

        window.toggleBuildingStatus = function(id, currentStatus) {
            const newStatus = (currentStatus == 0) ? 1 : 0;
            const actionText = (newStatus == 0) ? 'เปิดการใช้งาน' : 'ปิดการใช้งาน';
            
            Swal.fire({
                title: 'ยืนยันการเปลี่ยนสถานะ?',
                text: `คุณต้องการ ${actionText} อาคารนี้ใช่หรือไม่?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'ตกลง',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: (newStatus == 0) ? '#0ea5e9' : '#ef4444'
            }).then((result) => {
                if (result.isConfirmed) {
                    updateBuildingAPI(id, { area_status: newStatus });
                }
            });
        };

        async function updateBuildingAPI(id, data) {
            try {
                const response = await fetch('handle_agency.php?action=update_building', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ area_id: id, user_id: USER_ID, ...data })
                });
                const result = await response.json();
                if (result.success) {
                    if (data.area_name) database[id].name = data.area_name;
                    if (data.area_status !== undefined) database[id].status = data.area_status;
                    renderSidebar();
                    Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', timer: 1000, showConfirmButton: false });
                } else {
                    Swal.fire('ผิดพลาด', result.error || 'บันทึกไม่สำเร็จ', 'error');
                }
            } catch (e) {
                Swal.fire('ผิดพลาด', 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้', 'error');
            }
        }
        
        window.exportToExcel = async function() {
            if (!currentBuildingId) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกอาคารก่อนส่งออกข้อมูล', 'warning');
                return;
            }

            Swal.fire({
                title: 'กำลังเตรียมข้อมูลสำหรับ Export...',
                text: 'กรุณารอสักครู่',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                const response = await fetch(`handle_agency.php?action=get_all&ag_id=${AG_ID}&area_id=${currentBuildingId}`);
                const result = await response.json();

                if (result.success && result.data[currentBuildingId]) {
                    const building = result.data[currentBuildingId];
                    const floors = building.floors || [];
                    const exportData = [];

                    floors.forEach(f => {
                        const floorStatusText = f.floor_status == '0' ? 'Active' : 'Inactive';
                        
                        if (f.rooms && f.rooms.length > 0) {
                            f.rooms.forEach(r => {
                                exportData.push({
                                    'อาคาร': building.name,
                                    'ชั้น': f.floor,
                                    'สถานะชั้น': floorStatusText,
                                    'ชื่อห้อง / โซน': r.room_name,
                                    'สถานะห้อง': r.status == '0' ? 'Active' : 'Inactive'
                                });
                            });
                        } else {
                            exportData.push({
                                'อาคาร': building.name,
                                'ชั้น': f.floor,
                                'สถานะชั้น': floorStatusText,
                                'ชื่อห้อง / โซน': '-',
                                'สถานะห้อง': '-'
                            });
                        }
                    });

                    if (exportData.length === 0) {
                        Swal.fire('ไม่พบข้อมูล', 'อาคารนี้ยังไม่มีข้อมูลห้อง/พื้นที่', 'info');
                        return;
                    }

                    const ws = XLSX.utils.json_to_sheet(exportData);
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "รายชื่อห้องทั้งหมด");

                    // ปรับความกว้างคอลัมน์ของ Excel
                    const wscols = [
                        {wch: 30}, {wch: 10}, {wch: 15}, {wch: 35}, {wch: 15}
                    ];
                    ws['!cols'] = wscols;

                    const dateStr = new Date().toISOString().split('T')[0];
                    const fileName = `ข้อมูลพื้นที่_${building.name}_${dateStr}.xlsx`;

                    XLSX.writeFile(wb, fileName);
                    Swal.close();
                } else {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถดึงข้อมูลจากระบบได้', 'error');
                }
            } catch (error) {
                console.error("Export Error:", error);
                Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์', 'error');
            }
        };

        window.exportAllToExcel = async function() {
            Swal.fire({
                title: 'กำลังรวบรวมข้อมูลทุกอาคาร...',
                text: 'กรุณารอสักครู่ ระบบกำลังจัดทำไฟล์ Excel',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                const response = await fetch(`handle_agency.php?action=get_all&ag_id=${AG_ID}`);
                const result = await response.json();

                if (result.success && result.data) {
                    const allExportData = [];
                    
                    Object.values(result.data).forEach(building => {
                        const floors = building.floors || [];
                        
                        if (floors.length > 0) {
                            floors.forEach(f => {
                                // แปลงสถานะชั้น (ถ้าเป็น 0 = Active, นอกนั้น = Inactive)
                                const floorStatusText = f.floor_status == '0' ? 'Active' : 'Inactive';
                                
                                if (f.rooms && f.rooms.length > 0) {
                                    f.rooms.forEach(room => {
                                        allExportData.push({
                                            'ชื่ออาคาร': building.name,
                                            'ชั้น': f.floor,
                                            'สถานะชั้น': floorStatusText,
                                            'ชื่อห้อง / โซน': room.room_name,
                                            'สถานะห้อง': room.status == '0' ? 'Active' : 'Inactive'
                                        });
                                    });
                                } else {
                                    // กรณีสร้างชั้นไว้แต่ยังไม่มีห้อง
                                    allExportData.push({
                                        'ชื่ออาคาร': building.name,
                                        'ชั้น': f.floor,
                                        'สถานะชั้น': floorStatusText,
                                        'ชื่อห้อง / โซน': '-',
                                        'สถานะห้อง': '-'
                                    });
                                }
                            });
                        } else {
                            // กรณีเป็นตึกเปล่าๆ ยังไม่มีการสร้างชั้นเลย
                            allExportData.push({
                                'ชื่ออาคาร': building.name,
                                'ชั้น': '-',
                                'สถานะชั้น': '-',
                                'ชื่อห้อง / โซน': '-',
                                'สถานะห้อง': '-'
                            });
                        }
                    });

                    if (allExportData.length === 0) {
                        Swal.fire('ไม่พบข้อมูล', 'ไม่พบข้อมูลอาคารและห้องในระบบ', 'info');
                        return;
                    }

                    const ws = XLSX.utils.json_to_sheet(allExportData);
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "Master_Data_Rooms");

                    // ปรับความกว้างคอลัมน์ของ Excel ให้สวยงามพอดีกับข้อมูล
                    const wscols = [
                        {wch: 30}, // ชื่ออาคาร
                        {wch: 10}, // ชั้น
                        {wch: 15}, // สถานะชั้น
                        {wch: 35}, // ชื่อห้อง / โซน
                        {wch: 15}  // สถานะห้อง
                    ];
                    ws['!cols'] = wscols;

                    const timestamp = new Date().toISOString().substring(0, 10);
                    XLSX.writeFile(wb, `รายงานพื้นที่ทั้งหมด_${timestamp}.xlsx`);
                    
                    Swal.close();
                } else {
                    throw new Error('Data fetch unsuccessful');
                }
            } catch (error) {
                console.error("Export All Error:", error);
                Swal.fire('ผิดพลาด', 'ไม่สามารถส่งออกข้อมูลทั้งหมดได้ กรุณาลองใหม่', 'error');
            }
        };

        window.onload = initApp;
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            window.bypassUnsavedCheck = false;

            function hasUnsavedChanges() {
                if (window.bypassUnsavedCheck) return false; 
                
                // เช็คสถานะการซ่อนของป้ายแจ้งเตือนด้วย
                const counterEl = document.getElementById('change-counter');
                if (counterEl && counterEl.classList.contains('hidden')) {
                    return false; 
                }

                const countNum = document.getElementById('count-num');
                return countNum && parseInt(countNum.innerText || '0') > 0;
            }

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
                    if (res.isConfirmed) onConfirmCallback(); 
                    else if (res.isDenied) onDenyCallback(); 
                });
            }

            function interceptMenuClicks(targetWindow) {
                if (!targetWindow || !targetWindow.document) return;
                const doc = targetWindow.document;
                
                const clickHandler = function(e) {
                    const target = e.target.closest('a, button, [onclick], .nav-item');
                    const isMenuClick = target && (
                        target.closest('nav') || target.closest('.sidebar-nav') ||
                        target.closest('#menu-container') || target.closest('#desktop-menu-container') ||
                        target.closest('#mobile-menu-container') || target.closest('#main-dropdown') ||
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
                                const saveBtn = document.getElementById('btn-save') || document.querySelector('[onclick="saveAllData()"]');
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
                window.addEventListener('unload', () => doc.removeEventListener('click', clickHandler, true));
            }

            if (window.parent && window.parent !== window) {
                interceptMenuClicks(window.parent);
                if (window.parent.parent && window.parent.parent !== window.parent) {
                    interceptMenuClicks(window.parent.parent);
                }
            }

            window.addEventListener('beforeunload', function (e) {
                if (hasUnsavedChanges()) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        });
    </script>
</body>
</html>