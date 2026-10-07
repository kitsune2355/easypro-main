<?php
@session_start();
include "config_ctrl/checksession.php";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าอีเมลแจ้งเตือน</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Kanit', 'sans-serif'] },
                    colors: { primary: '#006b9f', primaryDark: '#004a6f' }
                }
            }
        }
    </script>

    <style>
        body { font-family: 'Kanit', sans-serif; background-color: #f8fafc; margin: 0; padding: 0; overflow: hidden; }
        .ht_master .wtHolder { scrollbar-width: thin; }
        .handsontable { font-family: 'Kanit', sans-serif; font-size: 13px; }
        .handsontable th { background-color: #f1f5f9 !important; color: #334155 !important; font-weight: 500 !important; border-color: #e2e8f0; vertical-align: middle; }
        .handsontable td { border-color: #e2e8f0; vertical-align: middle; }
        .htCheckboxRendererInput { cursor: pointer; }
        .cell-dirty { background-color: #fffbeb !important; color: #92400e !important; }
        .cell-new { background-color: #f0fdf4 !important; color: #166534 !important; }
    </style>
</head>
<body class="h-screen flex flex-col p-2 md:p-4">

    <header class="flex flex-col md:flex-row items-start md:items-center justify-between shrink-0 z-20 pb-4 gap-4">
        <div class="w-full md:w-auto flex items-center bg-white border border-slate-200 rounded-lg overflow-hidden px-3 h-10 focus-within:ring-2 focus-within:ring-sky-100 transition-all shadow-sm">
            <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
            <input type="text" id="search-input" placeholder="ค้นหา ชื่อ, ตำแหน่ง, แผนก, อีเมล..." class="w-full md:w-72 h-full ml-2 text-sm text-slate-600 focus:outline-none placeholder:text-slate-300">
        </div>
        
        <div class="w-full md:w-auto flex flex-wrap items-center gap-2 md:gap-3">
            <div id="change-counter" class="hidden items-center px-3 py-1 bg-amber-50 text-amber-700 text-[11px] font-bold rounded-full border border-amber-200 shadow-sm">
                <i data-lucide="alert-circle" class="w-3.5 h-3.5 mr-1"></i> รอการบันทึก: <span id="count-num" class="ml-1">0</span>
            </div>
            
            <div class="flex items-center bg-white border border-slate-200 rounded-lg overflow-hidden focus-within:ring-2 focus-within:ring-sky-100 transition-all h-10 shadow-sm">
                <input type="number" id="row-count-input" value="1" min="1" max="50" class="w-12 h-full text-center text-sm font-semibold text-slate-600 focus:outline-none">
                <button id="btn-add-row" class="h-full px-3 bg-slate-50 border-l border-slate-200 text-slate-600 text-sm font-medium hover:bg-sky-50 hover:text-primary transition-all whitespace-nowrap flex items-center gap-1">
                    <i data-lucide="plus" class="w-3.5 h-3.5 text-primary"></i> <span class="hidden sm:inline">เพิ่มแถว</span>
                </button>
            </div>

            <button id="btn-export-excel" class="h-10 px-3 md:px-4 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 transition-all shadow-sm flex items-center gap-2 whitespace-nowrap">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> <span class="hidden sm:inline">Export</span>
            </button>

            <button id="btn-save-email" class="h-10 px-4 bg-primary text-white rounded-lg text-sm font-medium hover:bg-primaryDark transition-all shadow-sm flex items-center gap-2 whitespace-nowrap">
                <i data-lucide="save" class="w-4 h-4"></i> <span>บันทึกข้อมูล</span>
            </button>
        </div>
    </header>

    <div class="flex-1 flex flex-col min-h-0 overflow-hidden bg-white rounded-xl shadow-sm border border-slate-200">
        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-100 flex justify-between items-center text-sm gap-2 shrink-0">
            <div class="text-slate-500 text-xs font-medium">
                รายการที่ <span id="start-range" class="text-slate-800">0</span> - <span id="end-range" class="text-slate-800">0</span> จากทั้งหมด <span id="total-rows" class="text-slate-800">0</span>
            </div>
        </div>

        <div id="hot-container" class="w-full flex-1 overflow-hidden z-0 relative"></div>
        
        <div class="px-4 py-3 bg-white border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500 font-medium">แสดงหน้าละ</span>
                <select id="page-size" class="text-xs border border-slate-200 rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-sky-100 text-slate-700">
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div id="pagination-nav" class="flex flex-wrap justify-center gap-1"></div>
        </div>
    </div>

    <div class="mt-3 flex flex-col sm:flex-row justify-between items-center text-[11px] text-slate-400 font-medium px-1 uppercase tracking-wider gap-2 shrink-0">
        <span class="flex items-center gap-1.5 text-center sm:text-left">
            <i data-lucide="info" class="w-3.5 h-3.5"></i> ดับเบิลคลิกที่เซลล์เพื่อแก้ไข หรือติ๊กถูกเพื่อเปิด/ปิดสถานะ
        </span>
        <div class="flex gap-4">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-[#fffbeb] border border-[#fde68a] rounded-sm"></span> มีการแก้ไข</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-[#f0fdf4] border border-[#bbf7d0] rounded-sm"></span> รายการใหม่</span>
        </div>
    </div>

    <script>
        if (window.lucide) lucide.createIcons();

        // State Management
        let allData = [];        
        let filteredData = [];   
        let currentPage = 1;
        let pageSize = 20;
        let searchTimeout = null;
        let tempIdCounter = -1;  

        let dirtyCells = new Map(); 
        let newRowIds = new Set();  
        let deletedRowIds = [];     // เก็บ ID ที่ถูกลบเพื่อส่งให้ Backend

        const AG_ID = '<?php echo isset($sess_user_agency_es) ? $sess_user_agency_es : ""; ?>';
        const USER_ID = '<?php echo isset($sess_user_id) ? $sess_user_id : ""; ?>';

        const container = document.getElementById('hot-container');

        const hot = new Handsontable(container, {
            data: [],
            colHeaders: ['ID (ซ่อน)', 'ชื่อ - นามสกุล', 'ตำแหน่ง', 'แผนก', 'ฝ่าย', 'อีเมล', 'เบอร์โทรติดต่อ', 'สถานะเปิด/ปิดการใช้งาน'],
            columns: [
                { data: 'id', readOnly: true },
                { data: 'name', type: 'text' },
                { data: 'position', type: 'text' },
                { data: 'department', type: 'text' },
                { data: 'division', type: 'text' },
                { data: 'email', type: 'text' },
                { data: 'phone', type: 'text' },
                { 
                    data: 'status', 
                    type: 'checkbox',          
                    className: 'htCenter htMiddle', 
                    checkedTemplate: 'ใช้งาน',   
                    uncheckedTemplate: 'ระงับ'  
                }
            ],
            hiddenColumns: { columns: [0], indicators: false },
            width: '100%',
            height: '100%',
            rowHeaders: true,
            colWidths: [0, 200, 150, 120, 120, 200, 120, 100],
            stretchH: 'last',
            contextMenu: ['remove_row', 'undo', 'redo'],
            licenseKey: 'non-commercial-and-evaluation',
            
            cells: function (row, col) {
                var cellProperties = {};
                cellProperties.renderer = function(instance, td, r, c, prop, value, cellProps) {
                    if (prop === 'status') {
                        Handsontable.renderers.CheckboxRenderer.apply(this, arguments);
                    } else {
                        Handsontable.renderers.TextRenderer.apply(this, arguments);
                    }
                    
                    const rowData = instance.getSourceDataAtRow(r);
                    if (rowData) {
                        if (newRowIds.has(rowData.id)) {
                            td.classList.add('cell-new');    
                        } else if (dirtyCells.has(rowData.id)) {
                            td.classList.add('cell-dirty');  
                        }
                    }
                };
                return cellProperties;
            },

            afterChange: function (changes, source) {
                if (source === 'loadData' || !changes) return;
                
                let isChanged = false;
                changes.forEach(([rowIdx, prop, oldVal, newVal]) => {
                    if (oldVal !== newVal) {
                        const rowData = hot.getSourceDataAtRow(rowIdx);
                        if (rowData) {
                            if (!newRowIds.has(rowData.id)) {
                                dirtyCells.set(rowData.id, true);
                            }
                            isChanged = true;
                        }
                    }
                });

                if (isChanged) {
                    hot.render(); 
                    updateCounter(); 
                }
            },

            // ดักจับเหตุการณ์ตอนลบแถว
            beforeRemoveRow: function(index, amount, physicalRows) {
                physicalRows.forEach(rowIdx => {
                    const rowData = hot.getSourceDataAtRow(rowIdx);
                    if (rowData) {
                        if (rowData.id > 0) {
                            // เป็นข้อมูลใน Database -> ต้องส่งไปลบ
                            deletedRowIds.push(rowData.id);
                        } else {
                            // เป็นข้อมูลใหม่ที่ยังไม่ได้บันทึก -> ล้างออกจาก Set
                            newRowIds.delete(rowData.id);
                        }
                        dirtyCells.delete(rowData.id);
                        
                        // เอาออกจาก allData ด้วย
                        const idx = allData.findIndex(item => item.id === rowData.id);
                        if (idx > -1) allData.splice(idx, 1);
                    }
                });
            },
            afterRemoveRow: function() {
                updateCounter();
                updateTable(); // รีเฟรชตารางจัดเรียงหน้าใหม่
            }
        });

        // ----------------------------------------------------
        // API Data Binding 
        // ----------------------------------------------------
        async function loadDataFromAPI() {
            try {
                // สมมติว่ามี AG_ID (ถ้าไม่มีจะไม่โหลดข้อมูล)
                if(!AG_ID) return;

                const res = await fetch(`handle_email_notify_eval.php?action=get&ag_id=${AG_ID}`);
                const json = await res.json();
                
                if (json.success) {
                    allData = json.data || [];
                    updateTable();
                }
            } catch (error) {
                console.error('โหลดข้อมูลไม่สำเร็จ:', error);
            }
        }

        function updateTable() {
            const searchVal = document.getElementById('search-input').value.trim().toLowerCase();
            
            filteredData = allData.filter(item => {
                if (!searchVal) return true;
                return (item.name && item.name.toLowerCase().includes(searchVal)) || 
                       (item.position && item.position.toLowerCase().includes(searchVal)) ||
                       (item.department && item.department.toLowerCase().includes(searchVal)) ||
                       (item.division && item.division.toLowerCase().includes(searchVal)) ||
                       (item.email && item.email.toLowerCase().includes(searchVal)) ||
                       (item.phone && item.phone.toLowerCase().includes(searchVal));
            });

            const totalRows = filteredData.length;
            const totalPages = Math.ceil(totalRows / pageSize) || 1;
            
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            const offset = (currentPage - 1) * pageSize;
            const paginatedData = filteredData.slice(offset, offset + pageSize);

            document.getElementById('total-rows').innerText = totalRows;
            document.getElementById('start-range').innerText = totalRows ? offset + 1 : 0;
            document.getElementById('end-range').innerText = Math.min(offset + pageSize, totalRows);

            renderPaginationNav(totalPages);
            hot.loadData(paginatedData);
        }

        function renderPaginationNav(totalPages) {
            const nav = document.getElementById('pagination-nav');
            nav.innerHTML = '';
            
            const createBtn = (content, targetPage, active = false, disabled = false) => {
                const btn = document.createElement('button');
                btn.className = `px-3 py-1.5 border border-slate-200 text-xs font-medium rounded-md transition-colors ${active ? 'bg-primary text-white border-primary' : 'bg-white text-slate-600 hover:bg-slate-50'}`;
                if (disabled) {
                    btn.className = 'px-3 py-1.5 border border-slate-200 text-xs font-medium rounded-md transition-colors bg-slate-50 text-slate-300 cursor-not-allowed';
                    btn.disabled = true;
                }
                btn.innerHTML = content;
                if (!disabled) {
                    btn.onclick = () => { currentPage = targetPage; updateTable(); };
                }
                return btn;
            };

            nav.appendChild(createBtn('<i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>', currentPage - 1, false, currentPage === 1));
            
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);
            
            if (startPage > 1) {
                nav.appendChild(createBtn('1', 1));
                if (startPage > 2) nav.insertAdjacentHTML('beforeend', '<span class="px-1 text-slate-400 text-xs">...</span>');
            }
            
            for (let i = startPage; i <= endPage; i++) {
                nav.appendChild(createBtn(i.toString(), i, i === currentPage));
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) nav.insertAdjacentHTML('beforeend', '<span class="px-1 text-slate-400 text-xs">...</span>');
                nav.appendChild(createBtn(totalPages.toString(), totalPages));
            }
            
            nav.appendChild(createBtn('<i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>', currentPage + 1, false, currentPage === totalPages || totalPages === 0));
            if (window.lucide) lucide.createIcons();
        }

        function updateCounter() {
            const count = newRowIds.size + dirtyCells.size + deletedRowIds.length;
            const counterEl = document.getElementById('change-counter');
            
            if (count > 0) {
                counterEl.classList.remove('hidden');
                counterEl.classList.add('flex');
                document.getElementById('count-num').innerText = count;
            } else {
                counterEl.classList.add('hidden');
                counterEl.classList.remove('flex');
            }
        }

        // ----------------------------------------------------
        // Events Listener
        // ----------------------------------------------------
        
        document.getElementById('search-input').addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentPage = 1;
                updateTable();
            }, 300);
        });

        document.getElementById('page-size').addEventListener('change', (e) => {
            pageSize = parseInt(e.target.value);
            currentPage = 1;
            updateTable();
        });

        document.getElementById('btn-add-row').addEventListener('click', () => {
            const count = parseInt(document.getElementById('row-count-input').value) || 1;
            
            for(let i=0; i<count; i++) {
                tempIdCounter--;
                const newRow = { 
                    id: tempIdCounter, 
                    name: '', 
                    position: '', 
                    department: '', 
                    division: '', 
                    email: '', 
                    phone: '', 
                    status: 'ใช้งาน' 
                };
                allData.unshift(newRow); 
                newRowIds.add(tempIdCounter);
            }
            
            document.getElementById('search-input').value = ''; 
            currentPage = 1; 
            updateTable();
            updateCounter();
        });

        // ----------------------------------------------------
        // Save Data (ส่งไปยัง API)
        // ----------------------------------------------------
        document.getElementById('btn-save-email').addEventListener('click', async () => {
            const count = newRowIds.size + dirtyCells.size + deletedRowIds.length;
            if (count === 0) {
                Swal.fire('ไม่มีการเปลี่ยนแปลง', 'ข้อมูลเป็นปัจจุบันแล้ว', 'info');
                return;
            }

            // กรองส่งเฉพาะแถวที่มีการเพิ่มใหม่ หรือ มีการแก้ไขข้อมูล
            const dataToSave = allData.filter(row => newRowIds.has(row.id) || dirtyCells.has(row.id));

            try {
                Swal.fire({
                    title: 'กำลังบันทึก...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const res = await fetch('handle_email_notify_eval.php?action=save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        ag_id: AG_ID,
                        user_id: USER_ID,
                        data: dataToSave,
                        deleted_ids: deletedRowIds // ส่งรายการ ID ที่ต้องลบ
                    })
                });

                const result = await res.json();

                if (result.success) {
                    // เคลียร์สถานะการแก้ไขทั้งหมด
                    newRowIds.clear();
                    dirtyCells.clear();
                    deletedRowIds = [];
                    updateCounter();

                    // โหลดข้อมูลจากฐานข้อมูลใหม่เพื่อรับ ID ที่แท้จริง
                    await loadDataFromAPI();

                    Swal.close();
                    Swal.fire({ 
                        icon: 'success', 
                        title: 'บันทึกสำเร็จ', 
                        timer: 1500, 
                        showConfirmButton: false,
                        customClass: { popup: 'rounded-2xl' }
                    });
                } else {
                    throw new Error(result.error || 'เกิดข้อผิดพลาดจากเซิร์ฟเวอร์');
                }

            } catch (error) {
                Swal.close();
                Swal.fire('เกิดข้อผิดพลาด', error.message, 'error');
            }
        });

        document.getElementById('btn-export-excel').addEventListener('click', async () => {
            if (filteredData.length === 0) {
                Swal.fire('ไม่มีข้อมูล', 'ไม่มีข้อมูลสำหรับ Export', 'warning');
                return;
            }

            const workbook = new ExcelJS.Workbook();
            const sheet = workbook.addWorksheet('Email Notify');
            
            sheet.columns = [
                { header: 'ชื่อ - นามสกุล', key: 'name', width: 25 },
                { header: 'ตำแหน่ง', key: 'position', width: 20 },
                { header: 'แผนก', key: 'department', width: 20 },
                { header: 'ฝ่าย', key: 'division', width: 20 },
                { header: 'อีเมล', key: 'email', width: 30 },
                { header: 'เบอร์โทรติดต่อ', key: 'phone', width: 15 },
                { header: 'สถานะเปิด/ปิดการใช้งาน', key: 'status', width: 15 }
            ];
            
            sheet.addRows(filteredData);
            
            const buffer = await workbook.xlsx.writeBuffer();
            saveAs(new Blob([buffer]), 'Email_Notify_Evaluation.xlsx');
        });

        // สั่งให้โหลดข้อมูลตั้งแต่เปิดหน้า
        loadDataFromAPI();

    </script>
</body>
</html>