<?php
@session_start();
// ตั้งค่าผู้ตรวจรับ PM (หัวหน้าช่าง) — ผู้ใช้ที่ติ๊ก "ผู้ตรวจรับ" ตรวจรับ / ส่งกลับแก้ไขงาน PM ได้ (แท็บ "ตรวจรับ PM" ในหน้าวางแผน PM)
//  ใช้เมื่อเปิด "ต้องให้หัวหน้าช่างตรวจรับงาน" ที่ settings_pm_record.php · หน้าตาเดียวกับ settings_pm_evaluators.php
include "config_ctrl/checksession.php";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าผู้ตรวจรับ PM</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/handsontable@17.0.1/dist/handsontable.full.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --color-bg: #f8fafc;
            --color-primary: #006B9F;
            --color-primary-dark: #004a6f;
        }

        body { font-family: 'Prompt', sans-serif; }
        .bg-primary { background-color: var(--color-primary); }
        .bg-primary-dark { background-color: var(--color-primary-dark); }

        /* Handsontable Overrides (เหมือนหน้าตั้งค่าอื่น) */
        .handsontable,
        .handsontable .colHeader,
        .handsontable .rowHeader,
        .handsontable .htCore,
        .handsontable .htDropdownMenuTable {
            font-family: 'Prompt', sans-serif !important;
        }
        .handsontable th.colHeader { font-weight: 500; color: #334155; font-size: 13px; }
        .handsontable th { background-color: #f1f5f9 !important; font-weight: 600 !important; color: #334155 !important; }

        /* Cell States */
        .cell-dirty { background-color: #fffbeb !important; color: #92400e !important; }
        .cell-readonly { color: #475569 !important; }

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
            <input type="text" id="search-input" placeholder="ค้นหาชื่อ / Username / ตำแหน่ง..." class="w-full md:w-80 h-full ml-2 text-sm text-slate-600 focus:outline-none placeholder:text-slate-300">
        </div>

        <div class="w-full md:w-auto flex flex-wrap items-center gap-2 md:gap-3">
            <div id="change-counter" class="hidden px-3 py-1 bg-amber-50 text-amber-700 text-[11px] font-bold rounded-full border border-amber-200 shadow-sm">
                <i class="fa-solid fa-circle-exclamation mr-1"></i> รอการบันทึก: <span id="count-num">0</span>
            </div>

            <label class="h-10 px-3 md:px-4 bg-white border border-slate-200 rounded-lg text-sm font-medium text-slate-600 flex items-center gap-2 cursor-pointer shadow-sm whitespace-nowrap hover:bg-sky-50 transition-all">
                <input type="checkbox" id="only-approvers" class="w-3.5 h-3.5"> <span class="hidden sm:inline">เฉพาะผู้ตรวจรับ</span><i class="fa-solid fa-filter sm:hidden text-sky-500"></i>
            </label>

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
            <div class="text-slate-500 text-xs font-medium">
                <i class="fa-solid fa-user-check text-emerald-600 mr-1"></i> ผู้ตรวจรับ <span id="approver-count" class="text-slate-800 font-semibold">0</span> คน
            </div>
            <div class="text-slate-500 text-xs font-medium">
                รายการที่ <span id="start-range" class="text-slate-800">0</span> - <span id="end-range" class="text-slate-800">0</span> จากทั้งหมด <span id="total-rows" class="text-slate-800">0</span>
            </div>
        </div>

        <div id="hot-display" class="w-full flex-1 overflow-hidden z-0"></div>

        <div class="px-4 py-3 bg-white border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-start">
                <span class="text-xs text-slate-500 font-medium">แสดงหน้าละ</span>
                <select id="page-size" data-no-search class="text-xs border border-slate-200 rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-sky-100">
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
            <div id="pagination-nav" class="flex flex-wrap justify-center gap-1 w-full sm:w-auto"></div>
        </div>
    </div>

    <div class="m-4 flex flex-col sm:flex-row justify-between items-center text-[11px] text-slate-400 font-medium px-1 uppercase tracking-wider gap-2">
        <span class="flex items-center gap-2 text-center sm:text-left normal-case"><i class="fa-solid fa-info-circle"></i> ติ๊กช่อง "ผู้ตรวจรับ" แล้วกดบันทึก (Super Admin ตรวจรับได้เสมอ ไม่ต้องติ๊ก) · ใช้เมื่อเปิด "ต้องให้หัวหน้าช่างตรวจรับงาน" ที่ การบันทึกผล PM · ตรวจรับได้ที่ วางแผน PM → หน้างาน → ตรวจรับ PM</span>
        <div class="flex gap-4 sm:gap-6">
            <span class="flex items-center gap-2"><span class="w-3 h-3 bg-amber-100 border border-amber-200 rounded-sm"></span> มีการแก้ไข</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/handsontable@17.0.1/dist/handsontable.full.min.js"></script>
    <script src="js/handsontable-light.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>

    <script>
        // --- State: ผู้ใช้ทั้งหมดของหน่วยงาน (โหลดครั้งเดียว ค้นหา/แบ่งหน้าในหน้าเว็บ) ---
        let users = [];               // [{ user_id, name, user_position, user_level, is_approver }]
        let saved = new Map();        // user_id => ค่าเดิมจากเซิร์ฟเวอร์ (true/false)
        let currentPage = 1;
        let pageSize = 20;
        let viewRows = [];            // แถวของหน้าปัจจุบัน

        const LEVEL = { super_admin: 'Super Admin', admin: 'Admin', employer: 'Employer', general: 'General' };
        const container = document.getElementById('hot-display');
        const counterEl = document.getElementById('change-counter');
        const countNumEl = document.getElementById('count-num');
        const paginationNav = document.getElementById('pagination-nav');

        const dirtyIds = () => users.filter(u => saved.get(u.user_id) !== !!u.is_approver).map(u => u.user_id);

        function filtered() {
            const q = document.getElementById('search-input').value.trim().toLowerCase();
            const only = document.getElementById('only-approvers').checked;
            return users.filter(u => (!only || u.is_approver) &&
                (!q || `${u.user_id} ${u.name} ${u.user_position || ''} ${LEVEL[u.user_level] || u.user_level || ''}`.toLowerCase().includes(q)));
        }

        // --- API ---
        async function fetchUsers() {
            try {
                const r = await (await fetch('handle_pm_approve.php?action=setting_list')).json();
                if (!r.success) { Swal.fire('ข้อผิดพลาด', r.error || 'โหลดข้อมูลไม่สำเร็จ', 'error'); return; }
                users = r.data.map(u => ({ ...u, is_approver: !!u.is_approver }));
                saved = new Map(users.map(u => [u.user_id, u.is_approver]));
                render();
            } catch (e) {
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        }

        function render() {
            const list = filtered();
            const totalPages = Math.ceil(list.length / pageSize) || 1;
            if (currentPage > totalPages) currentPage = totalPages;
            const offset = (currentPage - 1) * pageSize;
            viewRows = list.slice(offset, offset + pageSize);
            hot.loadData(viewRows.map(u => ({ user_id: u.user_id, name: u.name || '', user_position: u.user_position || '', level: LEVEL[u.user_level] || u.user_level || '', is_approver: u.is_approver ? '0' : '1' })));
            renderPagination(list.length, offset, totalPages);
            document.getElementById('approver-count').innerText = users.filter(u => u.is_approver).length;
            updateCounter();
        }

        function renderPagination(totalRows, offset, totalPages) {
            document.getElementById('total-rows').innerText = totalRows;
            document.getElementById('start-range').innerText = totalRows ? offset + 1 : 0;
            document.getElementById('end-range').innerText = Math.min(offset + pageSize, totalRows);

            paginationNav.innerHTML = '';
            const createBtn = (content, targetPage, active = false, disabled = false) => {
                const btn = document.createElement('button');
                btn.className = `page-link ${active ? 'active' : ''}`;
                btn.innerHTML = content; btn.disabled = disabled;
                btn.onclick = () => { currentPage = targetPage; render(); };
                return btn;
            };
            paginationNav.appendChild(createBtn('<i class="fa-solid fa-chevron-left"></i>', currentPage - 1, false, currentPage === 1));
            const startPage = Math.max(1, currentPage - 2), endPage = Math.min(totalPages, currentPage + 2);
            if (startPage > 1) paginationNav.appendChild(createBtn('1', 1));
            if (startPage > 2) { const s = document.createElement('span'); s.className = 'px-2 py-1 text-slate-400'; s.innerText = '...'; paginationNav.appendChild(s); }
            for (let i = startPage; i <= endPage; i++) paginationNav.appendChild(createBtn(i, i, i === currentPage));
            if (endPage < totalPages - 1) { const s = document.createElement('span'); s.className = 'px-2 py-1 text-slate-400'; s.innerText = '...'; paginationNav.appendChild(s); }
            if (endPage < totalPages) paginationNav.appendChild(createBtn(totalPages, totalPages));
            paginationNav.appendChild(createBtn('<i class="fa-solid fa-chevron-right"></i>', currentPage + 1, false, currentPage === totalPages));
        }

        function updateCounter() {
            const n = dirtyIds().length;
            if (n > 0) { counterEl.classList.remove('hidden'); countNumEl.innerText = n; }
            else { counterEl.classList.add('hidden'); countNumEl.innerText = '0'; }
        }

        // --- Renderers ---
        function textRenderer(instance, td, row, col, prop, value) {
            Handsontable.renderers.TextRenderer.apply(this, arguments);
            td.classList.add('cell-readonly');
            if (prop === 'user_id') td.style.fontWeight = '600';
            return td;
        }

        function approverRenderer(instance, td, row, col, prop, value) {
            Handsontable.renderers.CheckboxRenderer.apply(this, arguments);
            td.classList.remove('cell-dirty', 'status-active', 'status-inactive');
            const u = viewRows[row];
            if (u && saved.get(u.user_id) !== u.is_approver) td.classList.add('cell-dirty');
            td.style.display = 'flex';
            td.style.alignItems = 'center';
            td.style.justifyContent = 'center';
            td.style.gap = '8px';
            const on = String(value) === '0';
            const label = document.createElement('span');
            label.className = on ? 'status-active text-[11px]' : 'status-inactive text-[11px]';
            label.innerText = on ? 'ผู้ตรวจรับ' : '-';
            const input = td.querySelector('input');
            if (input) input.className = 'w-3.5 h-3.5 cursor-pointer';
            Array.from(td.childNodes).forEach(node => { if (node.nodeType === Node.TEXT_NODE) node.remove(); });
            td.appendChild(label);
            return td;
        }

        // --- Handsontable Init ---
        const hot = new Handsontable(container, {
            data: [],
            colHeaders: ['Username', 'ชื่อ - นามสกุล', 'ตำแหน่ง', 'ระดับผู้ใช้', 'ผู้ตรวจรับ'],
            columns: [
                { data: 'user_id', readOnly: true, renderer: textRenderer },
                { data: 'name', readOnly: true, renderer: textRenderer },
                { data: 'user_position', readOnly: true, renderer: textRenderer },
                { data: 'level', readOnly: true, renderer: textRenderer },
                { data: 'is_approver', type: 'checkbox', checkedTemplate: '0', uncheckedTemplate: '1', renderer: approverRenderer, className: 'htCenter htMiddle' }
            ],
            colWidths: [140, 240, 180, 120, 120],
            stretchH: 'all',
            rowHeaders: index => (currentPage - 1) * pageSize + index + 1,
            height: '100%',
            width: '100%',
            licenseKey: 'non-commercial-and-evaluation',
            viewportRowRenderingOffset: 9999,
            preventOverflow: 'horizontal',

            afterChange(changes, source) {
                if (source === 'loadData' || !changes) return;
                changes.forEach(([row, prop, oldValue, newValue]) => {
                    if (prop !== 'is_approver' || oldValue === newValue) return;
                    const u = viewRows[row];
                    if (u) u.is_approver = String(newValue) === '0';
                });
                document.getElementById('approver-count').innerText = users.filter(u => u.is_approver).length;
                hot.render();
                updateCounter();
            }
        });

        // --- Interactions ---
        let searchTimeout = null;
        document.getElementById('search-input').addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { currentPage = 1; render(); }, 300);
        });
        document.getElementById('only-approvers').addEventListener('change', () => { currentPage = 1; render(); });
        document.getElementById('page-size').addEventListener('change', e => { pageSize = parseInt(e.target.value); currentPage = 1; render(); });

        document.getElementById('btn-save').addEventListener('click', async () => {
            const changed = dirtyIds();
            if (!changed.length) {
                Swal.fire({ title: 'ไม่มีข้อมูลที่ต้องบันทึก', icon: 'info', confirmButtonColor: '#006B9F' });
                return;
            }
            const result = await Swal.fire({
                title: 'ยืนยันการบันทึก?', text: `พบข้อมูลที่ต้องการบันทึก ${changed.length} รายการ`,
                icon: 'question', showCancelButton: true, confirmButtonColor: '#006B9F', confirmButtonText: 'บันทึก', cancelButtonText: 'ยกเลิก'
            });
            if (!result.isConfirmed) return;
            try {
                Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                const r = await (await fetch('handle_pm_approve.php?action=setting_save', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_ids: users.filter(u => u.is_approver).map(u => u.user_id) })
                })).json();
                if (r.success) {
                    saved = new Map(users.map(u => [u.user_id, u.is_approver]));
                    render();
                    Swal.fire({ title: 'บันทึกสำเร็จ!', icon: 'success', timer: 1500, showConfirmButton: false });
                } else {
                    Swal.fire('ข้อผิดพลาด', r.error || 'ไม่สามารถบันทึกได้', 'error');
                }
            } catch (e) {
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        });

        document.getElementById('btn-export-excel').addEventListener('click', () => {
            const list = filtered();
            if (!list.length) { Swal.fire('แจ้งเตือน', 'ไม่มีข้อมูลสำหรับ Export', 'warning'); return; }
            const ws = XLSX.utils.json_to_sheet(list.map(u => ({
                'Username': u.user_id, 'ชื่อ - นามสกุล': u.name || '', 'ตำแหน่ง': u.user_position || '',
                'ระดับผู้ใช้': LEVEL[u.user_level] || u.user_level || '', 'ผู้ตรวจรับ': u.is_approver ? 'ใช่' : '-'
            })));
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'PM_Evaluators');
            const d = new Date();
            XLSX.writeFile(wb, `PM_Evaluators_${d.getFullYear()}${String(d.getMonth() + 1).padStart(2, '0')}${String(d.getDate()).padStart(2, '0')}.xlsx`);
        });

        fetchUsers();
        window.addEventListener('resize', () => { clearTimeout(window.resizeTimer); window.resizeTimer = setTimeout(() => hot.render(), 200); });
    </script>
    <!-- ถามก่อนเปลี่ยนเมนูเมื่อยังไม่บันทึก (เหมือนหน้าตั้งค่าอื่น) -->
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
