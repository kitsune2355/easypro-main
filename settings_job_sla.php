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
    <title>ตั้งค่า SLA (รับงาน-ปิดงาน)</title>
    
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

        .handsontable td, .handsontable th { font-size: 12px !important; }
        
        .handsontable .htCore td {
            vertical-align: middle;
            padding: 0 6px !important; 
            white-space: nowrap !important;
        }

        .handsontable th.colHeader { font-weight: 500; color: #334155; }
        .handsontable th { background-color: #f1f5f9 !important; font-weight: 600 !important; color: #334155 !important; }
        
        .checker-header { margin: 0; cursor: pointer; width: 14px; height: 14px; }

        .handsontable .wtHolder::-webkit-scrollbar { height: 4px; width: 4px; }
        .handsontable .wtHolder::-webkit-scrollbar-track { background: #f8fafc; border-radius: 2px; }
        .handsontable .wtHolder::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }
        .handsontable .wtHolder::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .cell-dirty { background-color: #fffbeb !important; color: #92400e !important; }
        .cell-new { background-color: #f0fdf4 !important; color: #166534 !important; }
        
        .page-link {
            padding: 4px 10px; border: 1px solid #e2e8f0; background: white; color: #64748b;
            border-radius: 6px; cursor: pointer; transition: all 0.2s; font-size: 12px; font-weight: 500;
        }
        .page-link:hover { background: #f1f5f9; color: var(--color-primary); }
        .page-link.active { background: var(--color-primary); color: white; border-color: var(--color-primary); }
        .page-link:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .sla-editor-container {
            position: absolute; z-index: 1000; display: none; background: #ffffff;
            border: 1px solid var(--color-primary); padding: 6px 8px; border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15); align-items: center; gap: 8px;
        }
    </style>
</head>
<body class="h-screen flex flex-col overflow-hidden md:p-6 p-2 bg-slate-50">

    <header class="flex flex-col md:flex-row items-start md:items-center justify-between shrink-0 z-20 pb-4 gap-4">
        <div class="w-full md:w-auto flex items-center bg-white border border-slate-200 rounded-lg overflow-hidden px-3 h-9 focus-within:ring-2 focus-within:ring-sky-100 transition-all shadow-sm">
            <i class="fa-solid fa-search text-slate-400 text-sm"></i>
            <input type="text" id="search-input" placeholder="ค้นหาข้อมูล..." class="w-full md:w-80 h-full ml-2 text-xs text-slate-600 focus:outline-none placeholder:text-slate-300">
        </div>
        
        <div class="w-full md:w-auto flex flex-wrap items-center gap-2 md:gap-3">
            <div id="change-counter" class="hidden px-3 py-1 bg-amber-50 text-amber-700 text-[11px] font-bold rounded-full border border-amber-200 shadow-sm">
                <i class="fa-solid fa-circle-exclamation mr-1"></i> รอการบันทึก: <span id="count-num">0</span>
            </div>
            
            <div class="hidden items-center bg-white border border-slate-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-sky-100 transition-all h-9 shadow-sm">
                <input type="number" id="row-count-input" value="1" min="1" max="50" class="w-12 h-full text-center text-xs font-semibold text-slate-600 focus:outline-none">
                <button id="btn-add-row" class="h-full px-3 bg-slate-50 border-l border-slate-200 text-slate-600 text-xs font-medium hover:bg-sky-50 hover:text-sky-600 transition-all whitespace-nowrap">
                    <i class="fa-solid fa-plus text-sky-500 mr-1"></i> <span class="hidden sm:inline">เพิ่มแถว</span>
                </button>
            </div>

            <button id="btn-export-excel" class="h-9 px-3 bg-green-600 text-white rounded-lg text-xs font-semibold hover:bg-green-700 transition-all shadow-md flex items-center gap-2 whitespace-nowrap">
                <i class="fa-solid fa-file-excel"></i> <span class="hidden sm:inline">Export</span>
            </button>

            <button id="btn-save" class="h-9 px-4 bg-primary text-white rounded-lg text-xs font-semibold hover:bg-primary-dark transition-all shadow-md flex items-center gap-2 whitespace-nowrap flex-1 md:flex-none justify-center">
                <i class="fa-solid fa-save"></i> <span>บันทึกข้อมูล</span>
            </button>
        </div>
    </header>

    <div class="flex-1 flex flex-col min-h-0 overflow-hidden bg-white rounded-xl shadow-sm border border-slate-200 relative">
        <div class="px-4 py-2 bg-slate-50 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center text-xs gap-2">
            <div class="flex items-center gap-4">
                <button id="btn-delete-selected" class="hidden text-red-500 hover:text-red-700 font-semibold text-[11px] bg-red-50 px-2 py-1 rounded border border-red-100">
                    <i class="fa-solid fa-trash-can mr-1"></i> ลบรายการที่เลือก (<span id="selected-count">0</span>)
                </button>
            </div>
            <div class="text-slate-500 font-medium">
                รายการที่ <span id="start-range" class="text-slate-800">0</span> - <span id="end-range" class="text-slate-800">0</span> จากทั้งหมด <span id="total-rows" class="text-slate-800">0</span>
            </div>
        </div>

        <div id="hot-display" class="w-full flex-1 overflow-hidden z-0"></div>
        
        <div class="px-4 py-2 bg-white border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-start">
                <span class="text-[11px] text-slate-500 font-medium">แสดงหน้าละ</span>
                <select id="page-size" class="text-[11px] border border-slate-200 rounded-md p-1 focus:outline-none focus:ring-2 focus:ring-sky-100">
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div id="pagination-nav" class="flex flex-wrap justify-center gap-1 w-full sm:w-auto"></div>
        </div>
    </div>

    <div class="m-3 flex flex-col sm:flex-row justify-between items-center text-[10px] text-slate-400 font-medium px-1 uppercase tracking-wider gap-2">
        <span class="flex items-center gap-2 text-center sm:text-left"><i class="fa-solid fa-info-circle"></i> ดับเบิลคลิกที่เซลล์เพื่อแก้ไขข้อมูล SLA</span>
        <div class="flex gap-4 sm:gap-6">
            <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 bg-amber-100 border border-amber-200 rounded-sm"></span> มีการแก้ไข</span>
            <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 bg-green-100 border border-green-200 rounded-sm"></span> รายการใหม่</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.js"></script>
    <script src="js/handsontable-light.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

    <script>
        let allData = []; 
        let currentPage = 1;
        let searchTimeout = null;
        let dirtyCells = new Map(); 
        let newRowIds = new Set();  
        let deletedIds = [];        
        let tempIdCounter = -1; 
        let cachedNewRows = []; 
        let cachedDirtyRows = new Map(); 

        const AG_ID = '<?php echo isset($sess_user_agency_es) ? $sess_user_agency_es : "DEMO_AG_ID"; ?>';
        const USER_ID = '<?php echo isset($sess_user_id) ? $sess_user_id : "DEMO_USER_ID"; ?>';

        const container = document.getElementById('hot-display');
        const counterEl = document.getElementById('change-counter');
        const countNumEl = document.getElementById('count-num');

        // ฟังก์ชันอ่านข้อความหลายๆ หน่วยมารวมเป็นชั่วโมง
        function calculateHours(valStr) {
            if (!valStr) return 0;
            let totalHours = 0;
            const str = String(valStr).toLowerCase();
            const regex = /([\d.]+)\s*([a-z]+)/g;
            let match;
            
            while ((match = regex.exec(str)) !== null) {
                const num = parseFloat(match[1]);
                const unit = match[2];

                if (unit.startsWith('d')) { totalHours += num * 24; } 
                else if (unit.startsWith('h')) { totalHours += num; } 
                else if (unit.startsWith('m')) { totalHours += parseFloat((num / 60).toFixed(2)); }
            }
            return totalHours;
        }

        // Custom Editor
        class SLAEditor extends Handsontable.editors.BaseEditor {
            init() {
                this.container = document.createElement('div');
                this.container.className = 'sla-editor-container flex items-center';

                const createInputGroup = (placeholder, labelText) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'flex items-center gap-1';
                    const input = document.createElement('input');
                    input.type = 'number'; input.min = '0'; input.placeholder = placeholder;
                    input.className = 'handsontableInput border border-slate-300 rounded px-1.5 py-1 text-xs w-32 h-8 focus:outline-none focus:border-sky-500 text-center';
                    const label = document.createElement('span');
                    label.className = 'text-xs text-slate-500 font-medium'; label.innerText = labelText;
                    wrapper.appendChild(input); wrapper.appendChild(label);
                    return { wrapper, input };
                };

                const dayGroup = createInputGroup('0', 'วัน');
                const hrGroup = createInputGroup('0', 'ชม.');
                const minGroup = createInputGroup('0', 'นาที');

                this.inputD = dayGroup.input; this.inputH = hrGroup.input; this.inputM = minGroup.input;

                this.btnSave = document.createElement('button');
                this.btnSave.innerHTML = '<i class="fa-solid fa-check"></i>';
                this.btnSave.className = 'bg-primary text-white w-7 h-7 rounded text-xs hover:bg-primary-dark flex items-center justify-center transition-colors ml-1';
                this.btnSave.onclick = (e) => { e.preventDefault(); this.finishEditing(); };

                this.container.appendChild(dayGroup.wrapper);
                this.container.appendChild(hrGroup.wrapper);
                this.container.appendChild(minGroup.wrapper);
                this.container.appendChild(this.btnSave);

                this.container.addEventListener('mousedown', (e) => e.stopPropagation());
                document.body.appendChild(this.container);
            }

            getValue() {
                let parts = [];
                const d = parseInt(this.inputD.value) || 0; const h = parseInt(this.inputH.value) || 0; const m = parseInt(this.inputM.value) || 0;
                if (d > 0) parts.push(`${d} days`); if (h > 0) parts.push(`${h} hrs`); if (m > 0) parts.push(`${m} mins`);
                return parts.join(' ');
            }

            setValue(value) {
                this.inputD.value = ''; this.inputH.value = ''; this.inputM.value = '';
                if (value) {
                    const str = String(value).toLowerCase();
                    const regex = /([\d.]+)\s*([a-z]+)/g;
                    let match;
                    while ((match = regex.exec(str)) !== null) {
                        const num = parseInt(match[1]); const unit = match[2];
                        if (unit.startsWith('d')) this.inputD.value = num;
                        else if (unit.startsWith('h')) this.inputH.value = num;
                        else if (unit.startsWith('m')) this.inputM.value = num;
                    }
                }
            }

            open() {
                const td = this.TD; const rect = td.getBoundingClientRect();
                this.container.style.top = (rect.top + window.scrollY + rect.height + 2) + 'px';
                this.container.style.left = (rect.left + window.scrollX) + 'px';
                this.container.style.display = 'flex';
            }
            close() { this.container.style.display = 'none'; }
            focus() { this.inputD.focus(); }
        }

        Handsontable.editors.registerEditor('SLAEditor', SLAEditor);

        // Renderers
        function customCellRenderer(instance, td, row, col, prop, value, cellProperties) {
            if (prop === 'sla_step1' || prop === 'sla_step2') { Handsontable.renderers.HtmlRenderer.apply(this, arguments); } 
            else { Handsontable.renderers.TextRenderer.apply(this, arguments); }
            
            td.classList.remove('cell-dirty', 'cell-new');
            const rowData = instance.getSourceDataAtRow(row);
            if (!rowData) return td;
            
            if (newRowIds.has(rowData.id)) { td.classList.add('cell-new'); } 
            else if (dirtyCells.has(rowData.id) && dirtyCells.get(rowData.id).has(prop)) { td.classList.add('cell-dirty'); }

            if (prop === 'sla_step1' || prop === 'sla_step2') {
                if (value && value.trim() !== '') {
                    const hrs = calculateHours(value);
                    td.innerHTML = `<div class="flex justify-between items-center w-full px-1"><span class="text-slate-700 font-medium">${value}</span><span class="text-[10px] text-emerald-600 font-semibold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-100" title="คิดเป็นชั่วโมง"><i class="fa-regular fa-clock"></i> ${hrs} hrs</span></div>`;
                } else {
                    td.innerHTML = '';
                }
                td.style.verticalAlign = 'middle';
            }
            return td;
        }

        // Handsontable Init
        const hot = new Handsontable(container, {
            data: [], 
            rowHeights: 32, autoRowSize: false, wordWrap: false, 
            colHeaders: function(col) {
                // if (col === 0) {
                //     const allChecked = allData.length > 0 && allData.every(row => row.selected === true || row.selected === 'true');
                //     return `<input type="checkbox" class="checker-header cursor-pointer w-3.5 h-3.5" ${allChecked ? 'checked' : ''}>`;
                // }
                const headers = ['รหัสสินค้าสต๊อก', 'ประเภทงาน', 'SLA: ระยะเวลาตอบรับ (แจ้งเรื่อง - รับงาน)', 'SLA: ระยะเวลาแก้ไข (รับงาน - ปิดงาน)'];
                // return headers[col - 1];
                return headers[col];
            },
            columns: [
                // { data: 'selected', type: 'checkbox', className: 'htCenter htMiddle', width: 40 },
                { data: 'rps_code', type: 'text', readOnly: true, className: 'htCenter htMiddle font-bold text-slate-700 bg-slate-50', width: 80 },
                { data: 'rps_name', type: 'text', readOnly: true, className: 'htMiddle font-semibold text-slate-700 bg-slate-50', width: 220 },
                { data: 'sla_step1', type: 'text', editor: 'SLAEditor', renderer: customCellRenderer, width: 320 },
                { data: 'sla_step2', type: 'text', editor: 'SLAEditor', renderer: customCellRenderer, width: 320 }
            ],
            stretchH: 'all', 
            rowHeaders: function(index) { 
                const pageSize = parseInt(document.getElementById('page-size').value) || 20;
                return (currentPage - 1) * pageSize + index + 1; 
            },
            height: '100%', licenseKey: 'non-commercial-and-evaluation', 
            viewportRowRenderingOffset: 9999, width: '100%', preventOverflow: 'horizontal',

            afterChange: function (changes, source) {
                if (source === 'loadData' || source === 'ObserveChanges.change') return;
                let selectionChanged = false;

                changes.forEach(([row, prop, oldValue, newValue]) => {
                    if (oldValue === newValue) return;
                    const rowData = hot.getSourceDataAtRow(row);
                    const target = allData.find(d => d.id === rowData.id);
                    if(!target) return;
                    
                    target[prop] = newValue;
                    if (!newRowIds.has(target.id) && prop !== 'selected') {
                        if (!dirtyCells.has(target.id)) dirtyCells.set(target.id, new Set());
                        dirtyCells.get(target.id).add(prop);
                    }
                    if (prop === 'selected') selectionChanged = true;
                });

                hot.render(); updateCounter();
                if (selectionChanged) updateDeleteButtonVisibility();
            }
        });

        container.addEventListener('click', (e) => {
            if (e.target.classList.contains('checker-header')) {
                const isChecked = e.target.checked;
                const changes = [];
                for (let i = 0; i < hot.countRows(); i++) { changes.push([i, 'selected', isChecked]); }
                if (changes.length > 0) hot.setDataAtRowProp(changes);
                allData.forEach(row => { row.selected = isChecked; });
                updateDeleteButtonVisibility();
            }
        });

        // --------------------------------------------------------
        // ระบบจัดการข้อมูล (เชื่อมต่อ API)
        // --------------------------------------------------------
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

        function updateCounter() {
            let validNewRowsCount = 0;
            cachedNewRows.forEach(r => { if (r.sla_step1 && r.sla_step1.trim() !== '') validNewRowsCount++; });
            allData.forEach(r => {
                if (newRowIds.has(r.id) && !cachedNewRows.some(cr => cr.id === r.id)) {
                     if (r.sla_step1 && r.sla_step1.trim() !== '') validNewRowsCount++;
                }
            });
            const total = dirtyCells.size + newRowIds.size + deletedIds.length;
            if (total > 0) { counterEl.classList.remove('hidden'); countNumEl.innerText = total; } 
            else counterEl.classList.add('hidden');
        }

        function updateDeleteButtonVisibility() {
            const selectedRows = allData.filter(row => row.selected === true || row.selected === 'true');
            const btnDelete = document.getElementById('btn-delete-selected');
            const countSpan = document.getElementById('selected-count'); 
            if (selectedRows.length > 0) { btnDelete.classList.remove('hidden'); if (countSpan) countSpan.innerText = selectedRows.length; } 
            else { btnDelete.classList.add('hidden'); if (countSpan) countSpan.innerText = '0'; }
        }

        function renderPagination(totalItems, pageSize) {
            const totalPages = Math.ceil(totalItems / pageSize);
            const nav = document.getElementById('pagination-nav');
            nav.innerHTML = '';
            
            if (totalPages <= 1) return;

            const createBtn = (text, page, disabled = false, active = false) => {
                const btn = document.createElement('button');
                btn.innerHTML = text; btn.className = `page-link ${active ? 'active' : ''}`;
                btn.disabled = disabled;
                if (!disabled && !active) btn.onclick = () => fetchSLAData(page);
                return btn;
            };

            nav.appendChild(createBtn('<i class="fa-solid fa-chevron-left"></i>', currentPage - 1, currentPage === 1));

            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, startPage + 4);
            if (endPage - startPage < 4) startPage = Math.max(1, endPage - 4);

            if (startPage > 1) { nav.appendChild(createBtn(1, 1)); if (startPage > 2) { const dots = document.createElement('span'); dots.className="px-2 text-slate-400"; dots.innerText="..."; nav.appendChild(dots); } }
            
            for (let i = startPage; i <= endPage; i++) { nav.appendChild(createBtn(i, i, false, i === currentPage)); }
            
            if (endPage < totalPages) { if (endPage < totalPages - 1) { const dots = document.createElement('span'); dots.className="px-2 text-slate-400"; dots.innerText="..."; nav.appendChild(dots); } nav.appendChild(createBtn(totalPages, totalPages)); }
            
            nav.appendChild(createBtn('<i class="fa-solid fa-chevron-right"></i>', currentPage + 1, currentPage === totalPages));
        }

        // ดึงข้อมูลผ่าน API
        async function fetchSLAData(page = 1) {
            preserveCurrentState();
            currentPage = page;
            const limit = parseInt(document.getElementById('page-size').value) || 20;
            const offset = (currentPage - 1) * limit;
            const searchValue = document.getElementById('search-input').value.trim();

            try {
                const response = await axios.get('handle_job_sla.php', {
                    params: {
                        action: 'get_all',
                        ag_id: AG_ID,
                        search: searchValue,
                        limit: limit,
                        offset: offset
                    }
                });

                if (response.data.success) {
                    let serverData = response.data.data;
                    const totalRows = response.data.total || 0;

                    // ⭐ เพิ่มตรงนี้: ถ้าไม่มีข้อมูลใดๆ จากฐานข้อมูลเลย ให้สร้างแถวว่างๆ ขึ้นมา 1 แถวอัตโนมัติ
                    if (serverData.length === 0 && cachedNewRows.length === 0) {
                        const newObj = { id: tempIdCounter--, sla_step1: "", sla_step2: "" };
                        cachedNewRows.unshift(newObj); 
                        newRowIds.add(newObj.id);
                    }

                    serverData = serverData.map(item => ({...item, selected: false}));
                    serverData = serverData.map(row => cachedDirtyRows.has(row.id) ? cachedDirtyRows.get(row.id) : row);
                    
                    allData = [...cachedNewRows, ...serverData];
                    document.getElementById('total-rows').innerText = totalRows;
                    
                    let startRow = totalRows === 0 ? 0 : offset + 1;
                    let endRow = offset + serverData.length;
                    document.getElementById('start-range').innerText = startRow;
                    document.getElementById('end-range').innerText = endRow;
                    
                    hot.loadData(JSON.parse(JSON.stringify(allData)));
                    updateCounter();
                    updateDeleteButtonVisibility();
                    renderPagination(totalRows, limit);
                } else {
                    console.error("Fetch API Error: ", response.data.error);
                }
            } catch (error) { 
                console.error("Axios request failed: ", error); 
            }
        }

        // Event: Search Input (พร้อม Debounce กันหน่วง)
        document.getElementById('search-input').addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetchSLAData(1); 
            }, 500); 
        });

        // Event: เลือกแสดงจำนวนหน้า
        document.getElementById('page-size').addEventListener('change', () => {
            fetchSLAData(1);
        });

        document.getElementById('btn-add-row').addEventListener('click', () => {
            preserveCurrentState(); 
            const count = parseInt(document.getElementById('row-count-input').value) || 1;
            for(let i = 0; i < count; i++) {
                const newObj = { id: tempIdCounter--, sla_step1: "", sla_step2: "" };
                cachedNewRows.unshift(newObj); newRowIds.add(newObj.id);
            }
            fetchSLAData(currentPage); 
        });

        document.getElementById('btn-delete-selected').addEventListener('click', () => {
            const rowsToDelete = allData.filter(row => row.selected === true || row.selected === 'true');
            if (rowsToDelete.length === 0) return;

            Swal.fire({
                title: 'ยืนยันการลบ?', text: `คุณต้องการลบ ${rowsToDelete.length} รายการที่เลือกใช่หรือไม่?`,
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'ใช่, ลบ', cancelButtonText: 'ยกเลิก'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const realIdsToDelete = rowsToDelete.filter(r => r.id > 0).map(r => r.id);

                    if (realIdsToDelete.length > 0) {
                        try {
                            const res = await axios.post('handle_job_sla.php?action=delete', {
                                ag_id: AG_ID,
                                ids: realIdsToDelete
                            });
                            
                            if(!res.data.success) {
                                Swal.fire('เกิดข้อผิดพลาด', res.data.error, 'error');
                                return;
                            }
                        } catch (e) {
                            Swal.fire('เกิดข้อผิดพลาด', e.message, 'error');
                            return;
                        }
                    }

                    // ลบในฝั่งหน้าบ้าน
                    rowsToDelete.forEach(row => {
                        if (newRowIds.has(row.id)) { newRowIds.delete(row.id); cachedNewRows = cachedNewRows.filter(r => r.id !== row.id); } 
                        else { dirtyCells.delete(row.id); cachedDirtyRows.delete(row.id); }
                    });
                    
                    allData = allData.filter(row => !(row.selected === true || row.selected === 'true'));
                    hot.loadData(JSON.parse(JSON.stringify(allData)));
                    updateCounter(); updateDeleteButtonVisibility(); 
                    
                    // หลังจากลบเสร็จ ถ้าข้อมูลหายเกลี้ยง ฟังก์ชันดึงข้อมูลรอบใหม่ก็จะไปสร้างแถวว่างๆ ให้ 1 แถวอัตโนมัติเช่นกัน
                    fetchSLAData(currentPage);
                }
            });
        });

        document.getElementById('btn-save').addEventListener('click', async () => {
            preserveCurrentState(); 
            const dataToSave = [];
            let validNewRows = []; 

            cachedNewRows.forEach(row => {
                if ((!row.sla_step1 || String(row.sla_step1).trim() === '') && (!row.sla_step2 || String(row.sla_step2).trim() === '')) {
                    newRowIds.delete(row.id);
                } else { 
                    row.sla_step1_hours = calculateHours(row.sla_step1);
                    row.sla_step2_hours = calculateHours(row.sla_step2);
                    validNewRows.push(row); dataToSave.push({ ...row, id: 0 }); 
                }
            });
            cachedNewRows = validNewRows;

            cachedDirtyRows.forEach(row => {
                row.sla_step1_hours = calculateHours(row.sla_step1);
                row.sla_step2_hours = calculateHours(row.sla_step2);
                dataToSave.push({ ...row })
            });

            if (dataToSave.length === 0) { fetchSLAData(currentPage); Swal.fire({ title: 'ไม่มีข้อมูลที่ต้องบันทึก', icon: 'info' }); return; }

            try {
                // เรียก API บันทึกข้อมูล
                const response = await axios.post('handle_job_sla.php?action=save', {
                    ag_id: AG_ID,
                    user_id: USER_ID,
                    items: dataToSave
                });

                if (response.data.success) {
                    Swal.fire({ title: 'บันทึกสำเร็จ', icon: 'success', timer: 1500 });
                    dirtyCells.clear(); newRowIds.clear(); deletedIds = []; 
                    cachedNewRows = []; cachedDirtyRows.clear(); updateCounter();
                    fetchSLAData(currentPage); 
                } else {
                    Swal.fire({ title: 'เกิดข้อผิดพลาด', text: response.data.error, icon: 'error' });
                }
            } catch (e) {
                Swal.fire({ title: 'เกิดข้อผิดพลาด', text: e.message, icon: 'error' });
            }
        });

        function initApp() { fetchSLAData(); lucide.createIcons(); }
        initApp();
        window.addEventListener('resize', () => { clearTimeout(window.resizeTimer); window.resizeTimer = setTimeout(() => { hot.render(); }, 200); });
    </script>
</body>
</html>