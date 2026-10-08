<?php 
@session_start();
include "config_ctrl/checksession.php"; 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการผู้ใช้งาน</title>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.1/qrcode.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
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
        .cell-dirty { background-color: #fffbeb !important; color: #92400e !important; }
        .cell-new { background-color: #f0fdf4 !important; color: #166534 !important; }
        .cell-error { background-color: #fee2e2 !important; color: #b91c1c !important; }
        .status-active { color: #0369a1; font-weight: 600; }
        .status-inactive { color: #64748b; font-weight: 600; }
        .btn-delete-row { color: #ef4444; cursor: pointer; transition: transform 0.2s; }
        .btn-delete-row:hover { transform: scale(1.2); color: #b91c1c; }

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

    <div class="flex-1 flex flex-col min-h-0 overflow-hidden bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center text-sm gap-2">
            <div class="flex items-center gap-4">
                <button id="btn-delete-selected" class="hidden text-red-500 hover:text-red-700 font-semibold text-xs bg-red-50 px-2 py-1 rounded border border-red-100">
                    <i class="fa-solid fa-trash-can mr-1"></i> ลบรายการที่เลือก
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
        <span class="flex items-center gap-2 text-center sm:text-left"><i class="fa-solid fa-info-circle"></i> ดับเบิลคลิกที่เซลล์เพื่อแก้ไขข้อมูล หรือเลื่อนซ้ายขวาบนตาราง</span>
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
        let departmentData = [];
        let departmentNames = [];
        let cachedNewRows = []; 
        let cachedDirtyRows = new Map(); 
        let errorRows = new Set();

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
        async function fetchDepartments() {
            try {
                const response = await axios.get('handle_user.php?action=get_department');
                if (response.data && response.data.success) {
                    departmentData = response.data.data;
                    departmentNames = departmentData.map(dep => dep.dep_name);
                }
            } catch (error) {
                console.error('Error fetching departments:', error);
            }
        }

        async function fetchUsers(page = 1) {
            // บันทึกการแก้ไขลง Cache ทันทีโดยไม่ต้องแจ้งเตือน
            preserveCurrentState();

            currentPage = page;
            const searchVal = document.getElementById('search-input').value.trim();
            const offset = (currentPage - 1) * pageSize;

            try {
                const response = await axios.get(`handle_user.php?action=get_all&ag_id=${AG_ID}&search=${encodeURIComponent(searchVal)}&limit=${pageSize}&offset=${offset}`);
                
                if (response.data && response.data.success) {
                    let serverData = response.data.data.map(item => ({
                        ...item,
                        selected: false
                    }));

                    // 1. นำข้อมูลที่แก้ไขไปแล้ว (แต่ยังไม่บันทึก) มาทับข้อมูลจาก Server
                    serverData = serverData.map(row => {
                        return cachedDirtyRows.has(row.id) ? cachedDirtyRows.get(row.id) : row;
                    });
                    
                    // 2. เอาแถวใหม่แปะไว้บนสุดเสมอ ไม่ว่าจะอยู่หน้าไหน
                    allData = [...cachedNewRows, ...serverData];
                    
                    const totalRows = response.data.total || 0;
                    renderServerPagination(totalRows, offset); 
                    
                    hot.loadData(JSON.parse(JSON.stringify(allData)));
                    updateCounter();
                }
            } catch (error) {
                console.error(error);
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
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
                btn.onclick = () => { fetchUsers(targetPage); };
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

        async function checkDuplicateUserId(value, oldValue, dbId, visualRow) {
            // ถ้าค่าว่าง ให้ลบสถานะ error
            if (!value || String(value).trim() === '') {
                if (errorRows.has(dbId)) {
                    errorRows.delete(dbId);
                    hot.render();
                }
                return;
            }

            // ตรวจสอบใน Cache
            const duplicateInLocal = cachedNewRows.some(r => r.user_id === value && r.id !== dbId) ||
                                     Array.from(cachedDirtyRows.values()).some(r => r.user_id === value && r.id !== dbId) ||
                                     allData.some(row => row.user_id === value && row.id !== dbId);
                                     
            if (duplicateInLocal) { 
                showDuplicateAlert(visualRow, dbId, oldValue); 
                return; 
            }

            const checkId = dbId > 0 ? dbId : 0;
            try {
                const response = await axios.get(`handle_user.php?action=check_duplicate&value=${encodeURIComponent(value)}&db_id=${checkId}`);
                if (response.data && response.data.success && response.data.is_duplicate) {
                    showDuplicateAlert(visualRow, dbId, oldValue);
                } else {
                    // *** เพิ่มตรงนี้: ถ้าข้อมูลถูกแก้ไขจนไม่ซ้ำแล้ว ให้ลบสีแดงออก ***
                    if (errorRows.has(dbId)) {
                        errorRows.delete(dbId);
                        hot.render();
                    }
                }
            } catch (error) { console.error("Error checking duplicate:", error); }
        }

        function showDuplicateAlert(row, dbId, oldValue) {
            errorRows.add(dbId);
            hot.render(); 

            Swal.fire({
                title: 'รหัสผู้ใช้ซ้ำ!', 
                text: `ไม่สามารถใช้ชื่อผู้ใช้นี้ได้ 
                        เนื่องจากมีผู้ใช้งานแล้ว กรุณาเลือกชื่อผู้ใช้อื่น`,
                icon: 'error', 
                confirmButtonColor: '#d33',
                confirmButtonText: 'ตกลง'
            });
        }

        // --- Renderers ---
        function customCellRenderer(instance, td, row, col, prop, value, cellProperties) {
            Handsontable.renderers.TextRenderer.apply(this, arguments);
            
            // เพิ่ม 'cell-error' เข้าไปในรายการที่ต้องลบออกก่อนเริ่มวาดเซลล์ใหม่
            td.classList.remove('cell-dirty', 'cell-new', 'status-active', 'status-inactive', 'cell-error');
            
            const rowData = instance.getSourceDataAtRow(row);
            if (!rowData) return td;

            // ตรวจสอบสถานะการตกแต่งเซลล์ตามลำดับความสำคัญ (Error > New > Dirty)
            if (errorRows.has(rowData.id) && prop === 'user_id') {
                // ถ้ามี error และเป็นคอลัมน์ user_id จะแสดงสีแดงแค่ช่องเดียว
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

        function dropdownRenderer(instance, td, row, col, prop, value, cellProperties) {
            customCellRenderer.apply(this, arguments);
            const wrapper = document.createElement('div');
            wrapper.className = 'flex items-center justify-between w-full h-full pr-1';
            wrapper.innerHTML = `<span class="truncate">${value || ""}</span><i class="fa-solid fa-chevron-down text-[9px] text-slate-300 ml-2"></i>`;
            td.innerHTML = ''; td.appendChild(wrapper);
            return td;
        }

        function statusCheckboxRenderer(instance, td, row, col, prop, value, cellProperties) {
            Handsontable.renderers.CheckboxRenderer.apply(this, arguments);
            td.classList.remove('cell-dirty', 'cell-new', 'status-active', 'status-inactive');
            const rowData = instance.getSourceDataAtRow(row);
            if (!rowData) return td;

            if (newRowIds.has(rowData.id)) { td.classList.add('cell-new'); } 
            else if (dirtyCells.has(rowData.id) && dirtyCells.get(rowData.id).has(prop)) { td.classList.add('cell-dirty'); }

            td.style.display = 'flex'; td.style.alignItems = 'center'; td.style.justifyContent = 'center'; td.style.gap = '8px';
            const label = document.createElement('span');
            label.className = value === 'Active' ? 'status-active text-[11px]' : 'status-inactive text-[11px]';
            label.innerText = value;
            const input = td.querySelector('input');
            if (input) input.className = 'w-3.5 h-3.5 cursor-pointer';
            td.appendChild(label);
            return td;
        }

        function actionRenderer(instance, td, row, col, prop, value, cellProperties) {
            Handsontable.renderers.TextRenderer.apply(this, arguments);
            td.classList.remove('cell-dirty', 'cell-new', 'status-active', 'status-inactive');
            td.innerHTML = '';
            const rowData = instance.getSourceDataAtRow(row);

            if (rowData && rowData.id > 0) {
                const btn = document.createElement('button');
                btn.className = 'text-slate-400 rounded hover:bg-sky-50 hover:text-sky-600 flex items-center justify-center mx-auto';
                btn.title = 'เปลี่ยนรหัสผ่าน';
                btn.innerHTML = '<i class="fa-solid fa-key text-[11px]"></i>';
                btn.onmousedown = (e) => e.stopPropagation(); 
                btn.onclick = (e) => { e.stopPropagation(); openChangePasswordDialog(rowData.id, rowData.user_id); };
                td.appendChild(btn);
            } else {
                td.innerText = '-'; td.classList.add('text-slate-300', 'text-xs', 'htCenter', 'htMiddle');
            }
            return td;
        }

        function qrRenderer(instance, td, row, col, prop, value, cellProperties) {
            Handsontable.renderers.TextRenderer.apply(this, arguments);
            
            // ลบคลาสตกแต่งพื้นฐานออกก่อนเพื่อไม่ให้สีเพี้ยน
            td.classList.remove('cell-dirty', 'cell-new', 'status-active', 'status-inactive', 'cell-error');
            td.innerHTML = '';
            
            const rowData = instance.getSourceDataAtRow(row);

            // ตรวจสอบว่ามีข้อมูลและมี Username (user_id) กรอกไว้หรือไม่
            if (rowData && rowData.user_id && rowData.user_id.trim() !== "") {
                const btn = document.createElement('button');
                btn.className = 'text-slate-400 rounded hover:bg-sky-50 hover:text-sky-600 flex items-center justify-center mx-auto';
                btn.title = 'สแกน QR Code เพื่อเข้าสู่ระบบ';
                btn.innerHTML = '<i class="fa-solid fa-qrcode text-[14px]"></i>';
                
                btn.onmousedown = (e) => e.stopPropagation(); // ป้องกันการ focus เซลล์ใน Handsontable
                btn.onclick = (e) => { 
                    e.stopPropagation(); 
                    // ดึงค่า Username และเข้ารหัส URL ให้ปลอดภัย
                    const username = encodeURIComponent(rowData.user_id);
                    const targetUrl = `https://happylandgroup.biz/es/login_qr.php?u=${username}`;
                    
                    // เปิดลิงก์ในแท็บใหม่
                    window.open(targetUrl, '_blank'); 
                };
                td.appendChild(btn);
            } else {
                // กรณีเป็นแถวใหม่หรือยังไม่ได้กรอก Username ให้แสดงขีด (-)
                td.innerText = '-'; 
                td.classList.add('text-slate-300', 'text-xs', 'htCenter', 'htMiddle');
            }
            return td;
        }

        // --- Handsontable Init ---
        const hot = new Handsontable(container, {
            data: [], 
            colHeaders: ['Username', 'ชื่อ', 'นามสกุล', 'ระดับผู้ใช้', 'ตำแหน่ง', 'เบอร์โทรศัพท์', 'สถานะ', 'QR Login', 'จัดการ'],
            columns: [
                { data: 'user_id', type: 'text', renderer: customCellRenderer, className: 'font-bold text-[--color-primary]' },
                { data: 'user_name', type: 'text', renderer: customCellRenderer },
                { data: 'user_fname', type: 'text', renderer: customCellRenderer },
                { data: 'user_level', type: 'dropdown', source: ['admin', 'employer'], renderer: dropdownRenderer },
                { data: 'dep_name', type: 'dropdown', source: [], renderer: dropdownRenderer }, 
                { data: 'user_tel', type: 'text', renderer: customCellRenderer },
                { data: 'status', type: 'checkbox', checkedTemplate: 'Active', uncheckedTemplate: 'Inactive', renderer: statusCheckboxRenderer, className: 'htCenter htMiddle' },
                { data: 'qr_action', renderer: qrRenderer, readOnly: true, width: 60, className: 'htCenter htMiddle' },
                { data: 'action', renderer: actionRenderer, readOnly: true, width: 60, className: 'htCenter htMiddle' }
            ],
            stretchH: 'all', 
            rowHeaders: function(index) {
                return (currentPage - 1) * pageSize + index + 1;
            },
            height: '100%',
            licenseKey: 'non-commercial-and-evaluation', 
            viewportRowRenderingOffset: 20,
            width: '100%',
            preventOverflow: 'horizontal',

            afterChange: function (changes, source) {
                if (source === 'loadData' || source === 'ObserveChanges.change') return;

                changes.forEach(([row, prop, oldValue, newValue]) => {
                    if (oldValue === newValue) return;

                    const rowData = hot.getSourceDataAtRow(row);
                    const target = allData.find(d => d.id === rowData.id);
                    if(!target) return;
                    
                    target[prop] = newValue;

                    if (prop === 'user_id') {
                        if (newValue && String(newValue).trim() !== "") {
                            // ถ้ามีการพิมพ์ข้อความ ให้ไปเช็คซ้ำ
                            checkDuplicateUserId(newValue, oldValue, rowData.id, row);
                        } else {
                            // *** ถ้าผู้ใช้ลบข้อความทิ้งจนว่างเปล่า ให้เคลียร์สีแดงออกด้วย ***
                            if (errorRows.has(rowData.id)) {
                                errorRows.delete(rowData.id);
                                hot.render();
                            }
                        }
                    }

                    if (!newRowIds.has(target.id)) {
                        if (!dirtyCells.has(target.id)) dirtyCells.set(target.id, new Set());
                        dirtyCells.get(target.id).add(prop);
                    }
                });
                
                hot.render();
                updateCounter();
            }
        });

        function updateCounter() {
            // นับเฉพาะแถวที่ไม่ได้ว่างเปล่าจริงๆ (สำหรับ newRows)
            let validNewRowsCount = 0;
            cachedNewRows.forEach(r => {
                if ((r.user_id && r.user_id.trim() !== '') || (r.user_name && r.user_name.trim() !== '')) {
                    validNewRowsCount++;
                }
            });

            // หา row ใหม่ที่กำลังแสดงผลบนหน้าจอด้วยเผื่อยังไม่ได้ลง cache
            allData.forEach(r => {
                if (newRowIds.has(r.id) && !cachedNewRows.some(cr => cr.id === r.id)) {
                     if ((r.user_id && r.user_id.trim() !== '') || (r.user_name && r.user_name.trim() !== '')) validNewRowsCount++;
                }
            });

            const total = dirtyCells.size + newRowIds.size + deletedIds.length;
            if (total > 0) { counterEl.classList.remove('hidden'); countNumEl.innerText = total; } 
            else counterEl.classList.add('hidden');
        }

        // --- Interactions ---
        document.getElementById('search-input').addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { fetchUsers(1); }, 500);
        });

        document.getElementById('page-size').addEventListener('change', (e) => {
            pageSize = parseInt(e.target.value); fetchUsers(1); 
        });

        document.getElementById('btn-add-row').addEventListener('click', () => {
            preserveCurrentState(); 
            const count = parseInt(document.getElementById('row-count-input').value) || 1;
            
            for(let i = 0; i < count; i++) {
                const newObj = {
                    selected: false, 
                    id: tempIdCounter--, 
                    user_id: "", 
                    user_name: "", 
                    user_fname: "", 
                    user_level: "employer", 
                    dep_name: "", 
                    user_tel: "", 
                    status: "Active"
                };
                cachedNewRows.unshift(newObj); // ดันแถวใหม่ไปบนสุดของ Cache
                newRowIds.add(newObj.id);
            }
            
            fetchUsers(currentPage); // โหลดตารางใหม่เพื่อให้เห็นแถวที่ถูกเพิ่มทันที
        });

        document.getElementById('btn-save').addEventListener('click', async () => {
            // 1. ตรวจสอบหา cell-error ก่อนเริ่มบันทึก
            const hotInstance = hot; // เปลี่ยน hot เป็นชื่อตัวแปรตารางของคุณ
            const rowCount = hotInstance.countRows();
            const colCount = hotInstance.countCols();
            let hasError = false;

            for (let r = 0; r < rowCount; r++) {
                for (let c = 0; c < colCount; c++) {
                    const meta = hotInstance.getCellMeta(r, c);
                    if (meta.className && meta.className.includes('cell-error')) {
                        hasError = true;
                        break;
                    }
                }
                if (hasError) break;
            }

            if (hasError) {
                Swal.fire({
                    icon: 'error',
                    title: 'พบข้อผิดพลาด!',
                    text: 'กรุณาแก้ไขข้อมูลในช่องที่มีพื้นหลังสีแดงก่อนทำการบันทึก',
                    confirmButtonColor: '#d33'
                });
                return; // หยุดการทำงานทันที ไม่ให้บันทึก
            }
            
            preserveCurrentState(); // ดึงสถานะจอปัจจุบันลง Cache ก่อน

            const dataToSave = [];
            let validNewRows = []; // เก็บเฉพาะแถวใหม่ที่มีการกรอกข้อมูล

            // 1. จัดการแถวใหม่ และ กรองแถวว่างออก (เป้าหมายที่ 2)
            cachedNewRows.forEach(row => {
                // เช็คว่าว่างไหม (สมมติว่าถ้าไม่กรอก Username ถือว่าว่าง)
                const isRowEmpty = (!row.user_id || String(row.user_id).trim() === '') && 
                                   (!row.user_name || String(row.user_name).trim() === '');
                
                if (isRowEmpty) {
                    // ถ้าว่าง ให้ลบออกจาก Set ของการติดตามการเปลี่ยนแปลง
                    newRowIds.delete(row.id);
                } else {
                    validNewRows.push(row);
                    let rowData = { ...row };
                    rowData.id = 0; 
                    const matchedDep = departmentData.find(d => d.dep_name === row.dep_name);
                    rowData.user_department = matchedDep ? matchedDep.dep_id : null;
                    dataToSave.push(rowData);
                }
            });

            // อัปเดต Cache แถวใหม่ให้เหลือแค่แถวที่มีข้อมูล
            cachedNewRows = validNewRows;

            // 2. จัดการแถวเดิมที่ถูกแก้ไข
            cachedDirtyRows.forEach((row, id) => {
                let rowData = { ...row };
                const matchedDep = departmentData.find(d => d.dep_name === row.dep_name);
                rowData.user_department = matchedDep ? matchedDep.dep_id : null;
                dataToSave.push(rowData);
            });

            // ตรวจสอบว่าหลังจากกรองแล้ว ยังมีข้อมูลให้เซฟอยู่ไหม
            if (dataToSave.length === 0) {
                updateCounter();
                fetchUsers(currentPage); // รีเฟรชจอเพื่อเคลียร์แถวว่างทิ้งไป
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
                    const response = await axios.post('handle_user.php?action=save', { 
                        data: dataToSave, ag_id: AG_ID, user_sin: USER_ID
                    });

                    if (response.data && response.data.success) {
                        Swal.fire({ title: 'บันทึกสำเร็จ!', icon: 'success', timer: 1500 });
                        
                        // เคลียร์ Cache และ State ทุกอย่างหลังบันทึกเสร็จ
                        dirtyCells.clear(); 
                        newRowIds.clear(); 
                        deletedIds = []; 
                        cachedNewRows = [];
                        cachedDirtyRows.clear();
                        updateCounter();
                        
                        fetchUsers(currentPage); 
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
                Swal.fire({ title: 'กำลังเตรียมข้อมูล Export...', text: 'กรุณารอสักครู่ (อาจใช้เวลาสร้าง QR Code)', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                const searchVal = document.getElementById('search-input').value.trim();
                // เรียก API ของ user
                const response = await axios.get(`handle_user.php?action=get_all&ag_id=${AG_ID}&search=${encodeURIComponent(searchVal)}&limit=999999&offset=0`);
                
                if (response.data && response.data.success && response.data.data.length > 0) {
                    
                    // 1. สร้าง Workbook และ Worksheet ด้วย ExcelJS
                    const workbook = new ExcelJS.Workbook();
                    const worksheet = workbook.addWorksheet('Users');

                    // 2. กำหนดหัวคอลัมน์และความกว้าง
                    worksheet.columns = [
                        { header: 'Username', key: 'user_id', width: 20 },
                        { header: 'ชื่อ', key: 'user_name', width: 20 },
                        { header: 'นามสกุล', key: 'user_fname', width: 20 },
                        { header: 'ระดับผู้ใช้', key: 'user_level', width: 15 },
                        { header: 'แผนก/ระดับ', key: 'dep_name', width: 20 },
                        { header: 'เบอร์โทรศัพท์', key: 'user_tel', width: 15 },
                        { header: 'สถานะ', key: 'status', width: 15 },
                        { header: 'QR Login', key: 'qr_login', width: 15 } // คอลัมน์สำหรับใส่รูป
                    ];

                    // 3. วนลูปข้อมูลเพื่อเพิ่มแถวและรูปภาพ
                    const users = response.data.data;
                    for (let i = 0; i < users.length; i++) {
                        const rowData = users[i];
                        
                        // เพิ่มข้อมูลลงในแถว
                        const row = worksheet.addRow({
                            user_id: rowData.user_id || '',
                            user_name: rowData.user_name || '',
                            user_fname: rowData.user_fname || '',
                            user_level: rowData.user_level || '',
                            dep_name: rowData.dep_name || '',
                            user_tel: rowData.user_tel || '',
                            status: rowData.status || 'Inactive'
                        });

                        // ขยายความสูงของแถวเพื่อให้พอดีกับรูป QR Code
                        row.height = 80; 

                        // ตรวจสอบว่ามี user_id ถึงจะสร้าง QR Code
                        if (rowData.user_id) {
                            const qrUrl = `https://happylandgroup.biz/es/login_qr.php?u=${encodeURIComponent(rowData.user_id)}`;
                            
                            // สร้างรูป QR Code ในรูปแบบ Base64 (ปรับ margin และขนาดภาพได้)
                            const qrBase64 = await QRCode.toDataURL(qrUrl, { margin: 1, width: 100 });
                            
                            // นำรูปเพิ่มเข้า Workbook
                            const imageId = workbook.addImage({
                                base64: qrBase64,
                                extension: 'png',
                            });

                            // วางรูปภาพลงในเซลล์ของ Worksheet
                            worksheet.addImage(imageId, {
                                tl: { col: 7, row: row.number - 1 + 0.1 }, // col 7 คือคอลัมน์ที่ 8 (index เริ่มที่ 0), +0.1 คือ offset ขอบบนเล็กน้อย
                                ext: { width: 75, height: 75 } // ขนาดรูป QR ใน Excel
                            });
                        }
                    }

                    // จัดรูปแบบตัวหนาให้กับหัวตาราง (แถวที่ 1)
                    worksheet.getRow(1).font = { bold: true };
                    worksheet.getRow(1).alignment = { vertical: 'middle', horizontal: 'center' };

                    // 4. สร้างไฟล์และดาวน์โหลด
                    const buffer = await workbook.xlsx.writeBuffer();
                    const date = new Date();
                    const dateString = `${date.getFullYear()}${(date.getMonth()+1).toString().padStart(2,'0')}${date.getDate().toString().padStart(2,'0')}`;
                    
                    // ใช้ FileSaver.js บันทึกไฟล์
                    saveAs(new Blob([buffer]), `Users_Export_${dateString}.xlsx`);
                    
                    Swal.close();
                } else {
                    Swal.fire('แจ้งเตือน', 'ไม่มีข้อมูลสำหรับ Export', 'warning');
                }
            } catch (error) {
                console.error("Export Error: ", error);
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลสำหรับ Export ได้', 'error');
            }
        });

        window.openChangePasswordDialog = async function(id, user_id) {
            const { value: formValues } = await Swal.fire({
                title: 'เปลี่ยนรหัสผ่าน',
                html: `
                    <div class="text-sm text-slate-500 mb-4 font-medium">รหัสผู้ใช้: <span class="text-sky-600">${user_id || '-'}</span></div>
                    <div class="flex flex-col gap-3 text-left">
                        <div>
                            <label class="text-xs text-slate-500 font-semibold mb-1 block">รหัสผ่านใหม่</label>
                            <div class="relative">
                                <input id="swal-pass1" type="password" class="w-full pl-3 pr-10 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500" placeholder="กรอกรหัสผ่านใหม่">
                                <button type="button" onclick="togglePasswordVisibility('swal-pass1', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-sky-600">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs text-slate-500 font-semibold mb-1 block">ยืนยันรหัสผ่านใหม่</label>
                            <div class="relative">
                                <input id="swal-pass2" type="password" class="w-full pl-3 pr-10 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500" placeholder="ยืนยันรหัสผ่านใหม่อีกครั้ง">
                                <button type="button" onclick="togglePasswordVisibility('swal-pass2', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-sky-600">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `,
                focusConfirm: false, showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-save mr-1"></i> บันทึกรหัสผ่าน',
                cancelButtonText: 'ยกเลิก', confirmButtonColor: '#006B9F',
                customClass: { popup: 'rounded-xl', title: 'text-lg font-bold text-slate-700' },
                preConfirm: () => {
                    const pass1 = document.getElementById('swal-pass1').value;
                    const pass2 = document.getElementById('swal-pass2').value;
                    if (!pass1 || !pass2) { Swal.showValidationMessage('กรุณากรอกรหัสผ่านให้ครบทั้งสองช่อง'); return false; }
                    if (pass1 !== pass2) { Swal.showValidationMessage('รหัสผ่านไม่ตรงกัน'); return false; }
                    if (pass1.length < 4) { Swal.showValidationMessage('รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร'); return false; }
                    return pass1;
                }
            });

            if (formValues) {
                try {
                    Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                    const response = await axios.post('handle_user.php?action=change_password', { id: id, password: formValues });
                    if (response.data && response.data.success) {
                        Swal.fire({ title: 'สำเร็จ!', text: 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว', icon: 'success', timer: 2000, showConfirmButton: false });
                    } else {
                        Swal.fire('ข้อผิดพลาด', response.data.error || 'ไม่สามารถเปลี่ยนรหัสผ่านได้', 'error');
                    }
                } catch (error) { Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error'); }
            }
        };

        window.togglePasswordVisibility = function(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text'; icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password'; icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye');
            }
        };

        async function initApp() {
            await fetchDepartments();
            hot.updateSettings({
                columns: hot.getSettings().columns.map(col => {
                    if (col.data === 'dep_name') { return { ...col, source: departmentNames }; }
                    return col;
                })
            });
            await fetchUsers();
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
                        // ==========================================
                        // กรณี: ยกเลิกการแก้ไข (ทิ้งข้อมูล เคลียร์ค่าตาราง)
                        // ==========================================
                        () => {
                            // 1. เคลียร์ตัวเลข "รอการบันทึก" ให้เป็น 0
                            const countNum = document.getElementById('count-num');
                            if (countNum) countNum.innerText = '0';
                            
                            // 2. รีเฟรชหน้าต่างปัจจุบันเพื่อล้างค่าตารางทั้งหมด (ทำงานได้กับทุกหน้า)
                            window.location.reload();
                            
                            // 3. อนุญาตให้เปลี่ยนหน้าต่างต่อไปได้ (ถ้าการ reload ไม่ตัดการทำงานไปก่อน)
                            window.bypassUnsavedCheck = true; 
                            doc.removeEventListener('click', clickHandler, true);
                            originalTarget.click();
                            
                            setTimeout(() => {
                                doc.addEventListener('click', clickHandler, true);
                                window.bypassUnsavedCheck = false;
                            }, 500);
                        },
                        // ==========================================
                        // กรณี: ต้องการบันทึกข้อมูล
                        // ==========================================
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