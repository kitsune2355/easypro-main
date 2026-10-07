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
    <title>ระบบจัดการประเภทอุปกรณ์เครื่องจักร</title>
    
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
        
        async function fetchPMFeedback(page = 1) {
            preserveCurrentState();

            currentPage = page;
            const searchVal = document.getElementById('search-input').value.trim();
            const offset = (currentPage - 1) * pageSize;

            try {
                // อัปเดต Path Endpoint
                const response = await axios.get(`handle_pm_feedback.php?action=get_all&ag_id=${AG_ID}&search=${encodeURIComponent(searchVal)}&limit=${pageSize}&offset=${offset}`);
                
                if (response.data && response.data.success) {
                    let serverData = response.data.data.map(item => ({
                        ...item,
                        selected: false
                    }));

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
                if(true) {
                   console.warn("Backend not found or error, using empty state");
                }
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
                btn.onclick = () => { fetchPMFeedback(targetPage); };
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

        async function checkDuplicateJobId(value, oldValue, dbId, visualRow) {
            if (!value || String(value).trim() === '') {
                if (errorRows.has(dbId)) {
                    errorRows.delete(dbId);
                    hot.render();
                }
                return;
            }

            const duplicateInLocal = cachedNewRows.some(r => r.job_type_id === value && r.id !== dbId) ||
                                     Array.from(cachedDirtyRows.values()).some(r => r.job_type_id === value && r.id !== dbId) ||
                                     allData.some(row => row.job_type_id === value && row.id !== dbId);
                                     
            if (duplicateInLocal) { 
                showDuplicateAlert(visualRow, dbId, oldValue); 
                return; 
            }

            const checkId = dbId > 0 ? dbId : 0;
            try {
                // อัปเดต Path Endpoint
                const response = await axios.get(`handle_pm_feedback.php?action=check_duplicate&value=${encodeURIComponent(value)}&db_id=${checkId}`);
                if (response.data && response.data.success && response.data.is_duplicate) {
                    showDuplicateAlert(visualRow, dbId, oldValue);
                } else {
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
                title: 'รหัสงานซ้ำ!', 
                text: 'รหัสงานนี้มีอยู่ในระบบแล้ว กรุณาใช้รหัสอื่น',
                icon: 'error', 
                confirmButtonColor: '#d33',
                confirmButtonText: 'ตกลง'
            });
        }

        // --- Renderers ---
        function customCellRenderer(instance, td, row, col, prop, value, cellProperties) {
            Handsontable.renderers.TextRenderer.apply(this, arguments);
            
            td.classList.remove('cell-dirty', 'cell-new', 'status-active', 'status-inactive', 'cell-error');
            
            const rowData = instance.getSourceDataAtRow(row);
            if (!rowData) return td;

            if (errorRows.has(rowData.id) && prop === 'job_type_id') {
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

        // --- Handsontable Init ---
        const hot = new Handsontable(container, {
            data: [], 
            colHeaders: ['ชื่อหัวข้อการประเมิน', 'ลำดับการแสดงผล',  'สถานะ'],
            columns: [
                { data: 'topic_title', type: 'text', renderer: customCellRenderer },
                { data: 'order_no', type: 'text', renderer: customCellRenderer },
                { data: 'status', type: 'checkbox', checkedTemplate: '0', uncheckedTemplate: '1', renderer: statusCheckboxRenderer, className: 'htCenter htMiddle' }
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

            afterChange: function (changes, source) {
                if (source === 'loadData' || source === 'ObserveChanges.change') return;

                changes.forEach(([row, prop, oldValue, newValue]) => {
                    if (oldValue === newValue) return;

                    const rowData = hot.getSourceDataAtRow(row);
                    const target = allData.find(d => d.id === rowData.id);
                    if(!target) return;
                    
                    target[prop] = newValue;

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
            let validNewRowsCount = 0;
            // อัปเดต Key การเช็คข้อมูล
            cachedNewRows.forEach(r => {
                if ((r.job_type_id && r.job_type_id.trim() !== '') || (r.topic_title && r.topic_title.trim() !== '')) {
                    validNewRowsCount++;
                }
            });

            allData.forEach(r => {
                if (newRowIds.has(r.id) && !cachedNewRows.some(cr => cr.id === r.id)) {
                     if ((r.job_type_id && r.job_type_id.trim() !== '') || (r.topic_title && r.topic_title.trim() !== '')) validNewRowsCount++;
                }
            });

            const total = dirtyCells.size + newRowIds.size + deletedIds.length;
            if (total > 0) { counterEl.classList.remove('hidden'); countNumEl.innerText = total; } 
            else counterEl.classList.add('hidden');
        }

        // --- Interactions ---
        document.getElementById('search-input').addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { fetchPMFeedback(1); }, 500);
        });

        document.getElementById('page-size').addEventListener('change', (e) => {
            pageSize = parseInt(e.target.value); fetchPMFeedback(1); 
        });

        document.getElementById('btn-add-row').addEventListener('click', () => {
            preserveCurrentState(); 
            const count = parseInt(document.getElementById('row-count-input').value) || 1;
            
            for(let i = 0; i < count; i++) {
                // อัปเดต Object Key
                const newObj = {
                    id: tempIdCounter--, 
                    topic_title: "", 
                    status: "0"
                };
                cachedNewRows.unshift(newObj); 
                newRowIds.add(newObj.id);
            }
            
            fetchPMFeedback(currentPage); 
        });

        document.getElementById('btn-save').addEventListener('click', async () => {
            preserveCurrentState(); 

            const dataToSave = [];
            let validNewRows = []; 

            cachedNewRows.forEach(row => {
                // อัปเดตการเช็ค Object Key
                const isRowEmpty = (!row.topic_title || String(row.topic_title).trim() === '');
                
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
                fetchPMFeedback(currentPage); 
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
                    // อัปเดต Path Endpoint
                    const response = await axios.post('handle_pm_feedback.php?action=save', { 
                        data: dataToSave, ag_id: AG_ID, user_sin: USER_ID
                    });

                    if (response.data && response.data.success) {
                        Swal.fire({ title: 'บันทึกสำเร็จ!', icon: 'success', timer: 1500 });
                        
                        dirtyCells.clear(); 
                        newRowIds.clear(); 
                        deletedIds = []; 
                        cachedNewRows = [];
                        cachedDirtyRows.clear();
                        updateCounter();
                        
                        fetchPMFeedback(currentPage); 
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
                Swal.fire({ title: 'กำลังเตรียมข้อมูล Export...', text: 'กรุณารอสักครู่', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                const searchVal = document.getElementById('search-input').value.trim();
                const response = await axios.get(`handle_pm_feedback.php?action=get_all&ag_id=${AG_ID}&search=${encodeURIComponent(searchVal)}&limit=999999&offset=0`);
                
                if (response.data && response.data.success && response.data.data.length > 0) {
                    const exportData = response.data.data.map(row => ({
                        'ชื่อหัวข้อการประเมิน': row.topic_title || '',
                        'สถานะ': String(row.status) === '0' ? 'Active' : 'Inactive'
                    }));

                    const worksheet = XLSX.utils.json_to_sheet(exportData);
                    const workbook = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(workbook, worksheet, "PMFeedback");
                    
                    const date = new Date();
                    const dateString = `${date.getFullYear()}${(date.getMonth()+1).toString().padStart(2,'0')}${date.getDate().toString().padStart(2,'0')}`;
                    XLSX.writeFile(workbook, `PMFeedback_Export_${dateString}.xlsx`);
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
            await fetchPMFeedback();
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