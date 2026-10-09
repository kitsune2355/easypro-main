<?php 
@session_start();
include "config_ctrl/connect.php";
include "config_ctrl/checksession.php";
ini_set('memory_limit', '300M');
ini_set('max_execution_time', 5000);
date_default_timezone_set('Asia/Bangkok');

if (isset($_SESSION['is_qr_user']) && $_SESSION['is_qr_user'] === true) {
    header("Location: main2.php#maintenance_request.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Machine Asset Management - PM</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable@17.0.1/dist/handsontable.full.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/handsontable@17.0.1/dist/handsontable.full.min.js"></script>
    <script src="js/handsontable-light.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-enterprise/dist/ag-grid-enterprise.min.js"></script>
    <script src="js/img-carousel.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <style>
        :root {
            --color-bg-light: #F0F4F8;
            --color-primary: #006B9F; 
            --color-secondary: #04ADFF;
            --font-family: 'Kanit', sans-serif;
        }
        
        body {
            font-family: var(--font-family);
            background-color: var(--color-bg-light); 
            height: 100vh;
            overflow: hidden; 
        }

        .btn-gradient {
            background: linear-gradient(to right, #006B9F, #04ADFF);
            color: white;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            box-shadow: 0 10px 20px -5px rgba(0, 107, 159, 0.4);
            transform: translateY(-1px);
        }

        .ag-theme-alpine {
            --ag-font-family: var(--font-family);
            --ag-border-radius: 12px;
            --ag-header-background-color: #f8fafc;
            --ag-row-hover-color: rgba(4, 173, 255, 0.05);
            --ag-selected-row-background-color: rgba(4, 173, 255, 0.08);
            border: none !important;
        }
        .ag-header { border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important; }
        .ag-root-wrapper { border: none !important; background: transparent !important; }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Handsontable Customization */
        .handsontable, 
        .handsontable .colHeader, 
        .handsontable .rowHeader, 
        .handsontable .htCore,
        .handsontable .htDropdownMenuTable {
            font-family: 'Kanit', sans-serif !important;
        }

        .handsontable th.colHeader {
            font-weight: 500; 
            color: #334155;   
            font-size: 13px;
        }
        .ht_master tr th { background-color: #f8fafc; color: #334155; font-weight: 500; }
        
        /* Top Tab active state */
        .nav-item { border-bottom: 2px solid transparent; color: #64748b; transition: all 0.2s; }
        .nav-item:hover { color: #0f172a; border-color: #cbd5e1; }
        .nav-item.active { color: var(--color-primary); border-color: var(--color-primary); font-weight: 500; }
        .nav-item.active i { color: var(--color-primary); }
        
        /* Hide scrollbar for tabs */
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* แผงซ้าย (ตัวกรอง/ตัวจัดการ) พับได้บนจอใหญ่: แท็บปฏิทิน / เลื่อนแผน PM / จัดการวันหยุด (ดู togglePmPanel)
           มือถือยังใช้ลิ้นชักเดิมของแต่ละแท็บ */
        .pm-expand-btn { display: none; }
        @media (min-width: 768px) {
            #tab-dashboard.pm-collapsed > .grid, #tab-postpone.pm-collapsed > .grid { grid-template-columns: minmax(0, 1fr); }
            #tab-dashboard.pm-collapsed #filter-sidebar, #tab-postpone.pm-collapsed #postpone-filter-sidebar { display: none; }
            #tab-dashboard.pm-collapsed .pm-expand-btn, #tab-postpone.pm-collapsed .pm-expand-btn { display: inline-flex; }
        }
        @media (min-width: 1024px) {
            #tab-holiday.pm-collapsed #holiday-drawer { display: none; }
            #tab-holiday.pm-collapsed > .lg\:col-span-8 { grid-column: 1 / -1; }
            #tab-holiday.pm-collapsed .pm-expand-btn { display: inline-flex; }
        }

        #tab-holiday, #tab-holiday, #tab-plan, #tab-holiday
        {
            height: calc(100vh - 180px) !important;
            min-height: 400px;
        }
    </style>
    <style>
        /* ===== มือถือ: การ์ดแทนตาราง AG Grid (ดู PmGridCards) ===== */
        .pm-mcards { display: none; }
        @media (max-width: 767.98px) {
            /* ตารางจริงยังทำงานอยู่นอกจอ (โหลดข้อมูล/แบ่งหน้า/กรอง) การ์ดอ่านข้อมูลจากตาราง */
            .pm-grid-wrap { position: absolute !important; left: -12000px !important; top: 0; width: 1000px !important; height: 700px !important; overflow: hidden !important; }
            .pm-grid-wrap > .ag-theme-alpine { height: 700px !important; }
            .pm-mcards { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; gap: .6rem; }
            #tab-holiday { height: auto !important; min-height: 0 !important; }
            #tab-holiday > .lg\:col-span-8 { min-height: 0 !important; }
        }
        .pm-mc-list { display: flex; flex-direction: column; gap: .6rem; min-height: 0; overflow-y: auto; }
        .pm-mc { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: .8rem; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
        .pm-mc.is-off { background: #f8fafc; }
        .pm-mc.is-off .pm-mc-title { color: #94a3b8; }
        .pm-mc-title { font-weight: 700; color: #1e293b; font-size: 14px; line-height: 1.35; }
        .pm-mc-sub { font-size: 12px; color: #64748b; }
        .pm-mc-kv { background: #f8fafc; border-radius: .6rem; padding: .35rem .55rem; min-width: 0; }
        .pm-mc-kv b { display: block; font-size: 10px; font-weight: 600; color: #94a3b8; }
        .pm-mc-kv span { display: block; font-size: 12.5px; font-weight: 600; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pm-mc-btn { width: 2.25rem; height: 2.25rem; border-radius: .7rem; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; justify-content: center; color: #64748b; background: #fff; }
        .pm-mc-btn:active { background: #f1f5f9; }
        .pm-mc-chip { display: inline-flex; align-items: center; gap: .25rem; padding: .1rem .5rem; border-radius: 9999px; font-size: 11px; font-weight: 600; white-space: nowrap; }
        .pm-mc-pager { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .25rem 0; }
        .pm-mc-pager button { width: 2.5rem; height: 2.5rem; border-radius: .75rem; border: 1px solid #e2e8f0; background: #fff; color: #334155; }
        .pm-mc-pager button:disabled { opacity: .35; }
        .pm-switch { position: relative; display: inline-flex; align-items: center; gap: .4rem; cursor: pointer; font-size: 12px; font-weight: 600; }
        .pm-switch input { position: absolute; opacity: 0; width: 0; height: 0; }
        .pm-switch i { width: 2.1rem; height: 1.2rem; border-radius: 9999px; background: #e2e8f0; position: relative; transition: .2s; }
        .pm-switch i::after { content: ''; position: absolute; top: 2px; left: 2px; width: calc(1.2rem - 4px); height: calc(1.2rem - 4px); border-radius: 9999px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.2); transition: .2s; }
        .pm-switch input:checked + i { background: #10b981; }
        .pm-switch input:checked + i::after { transform: translateX(.9rem); }
    </style>
    <script>
        /* มือถือ: แสดงแถวของตาราง AG Grid หน้าปัจจุบันเป็นการ์ด (ตารางยังเป็นตัวจัดการข้อมูล/แบ่งหน้า/กรอง)
           PmGridCards(api, wrapEl, { card(data) => html, empty, search: placeholder, onSearch(text) }) */
        window.PmGridCards = function (api, wrap, opts) {
            if (!api || !wrap) return null;
            const isMobile = () => window.innerWidth < 768;
            wrap.classList.add('pm-grid-wrap');
            const box = document.createElement('div');
            box.className = 'pm-mcards';
            box.innerHTML = (opts.search ? `<div class="relative"><i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>`
                    + `<input type="search" class="pm-mc-q w-full pl-9 pr-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-sky-500/30" placeholder="${opts.search}"></div>` : '')
                + `<div class="pm-mc-list"></div>`
                + `<div class="pm-mc-pager"><button type="button" data-p="prev" aria-label="หน้าก่อนหน้า"><i data-lucide="chevron-left" class="w-4 h-4 mx-auto"></i></button>`
                + `<span class="pm-mc-info text-xs font-semibold text-slate-500 text-center"></span>`
                + `<button type="button" data-p="next" aria-label="หน้าถัดไป"><i data-lucide="chevron-right" class="w-4 h-4 mx-auto"></i></button></div>`;
            wrap.insertAdjacentElement('afterend', box);
            const list = box.querySelector('.pm-mc-list'), info = box.querySelector('.pm-mc-info');
            let retry = null, tries = 0;

            function render() {
                if (!isMobile()) return;
                const size = api.paginationGetPageSize(), page = api.paginationGetCurrentPage();
                const pages = Math.max(1, api.paginationGetTotalPages() || 1);
                const total = api.paginationGetRowCount ? api.paginationGetRowCount() : api.getDisplayedRowCount();
                const html = [];
                let pending = false;
                for (let i = page * size; i < page * size + size; i++) {
                    const node = api.getDisplayedRowAtIndex(i);
                    if (!node) break;
                    if (!node.data) { pending = true; continue; }
                    html.push(opts.card(node.data, node));
                }
                list.innerHTML = html.length ? html.join('')
                    : (pending ? `<div class="py-12 text-center text-slate-400 text-sm">กำลังโหลด...</div>`
                               : `<div class="py-12 text-center text-slate-400 text-sm"><i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2"></i>${opts.empty || 'ไม่พบข้อมูล'}</div>`);
                info.textContent = `หน้า ${Math.min(page + 1, pages)} / ${pages}` + (total ? ` · ${total} รายการ` : '');
                box.querySelector('[data-p="prev"]').disabled = page <= 0;
                box.querySelector('[data-p="next"]').disabled = page >= pages - 1;
                box.querySelector('.pm-mc-pager').style.display = pages > 1 ? '' : 'none';
                if (window.lucide) lucide.createIcons({ root: box });
                // แถวที่ยังโหลดจากเซิร์ฟเวอร์ไม่เสร็จ (infinite row model) => ลองวาดใหม่อีกครั้ง
                clearTimeout(retry);
                if (pending && tries++ < 20) retry = setTimeout(render, 300); else if (!pending) tries = 0;
            }
            box.addEventListener('click', e => {
                const b = e.target.closest('[data-p]');
                if (!b) return;
                if (b.dataset.p === 'prev') api.paginationGoToPreviousPage(); else api.paginationGoToNextPage();
                list.scrollTop = 0;
                box.scrollIntoView({ block: 'nearest' });
            });
            if (opts.search) {
                let tm = null;
                box.querySelector('.pm-mc-q').addEventListener('input', e => {
                    clearTimeout(tm);
                    tm = setTimeout(() => opts.onSearch(e.target.value.trim()), 300);
                });
            }
            ['modelUpdated', 'paginationChanged', 'rowDataUpdated', 'cellValueChanged'].forEach(ev => api.addEventListener(ev, () => { tries = 0; render(); }));
            let wasMobile = isMobile();
            window.addEventListener('resize', () => { const m = isMobile(); if (m && !wasMobile) render(); wasMobile = m; });
            setTimeout(render, 0);
            return { render };
        };
    </script>
</head>
<body class="flex flex-col antialiased text-slate-700 h-screen w-full relative">

    <nav class="flex-none px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between border-b border-slate-200 bg-white z-20 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="p-2 bg-[#006B9F] rounded-lg shadow-md text-white">
                <i data-lucide="notebook-pen" class="w-6 h-6"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-800 leading-tight" id="header-title">Preventive Maintenance</h2>
                <p class="hidden sm:block text-xs text-slate-500 mt-1" id="header-subtitle">ติดตามและตรวจสอบแผนการบำรุงรักษา</p>
            </div>
        </div>
    </nav>

    <div class="flex-none bg-white border-b border-slate-200 px-2 sm:px-6 z-10 shadow-sm relative">
        <ul id="pm-tabs" class="flex gap-5 sm:gap-8 px-2 sm:px-0 overflow-x-auto hide-scrollbar">
            <li>
                <button onclick="switchTab('dashboard')" class="nav-item flex items-center gap-2 py-3.5 text-sm whitespace-nowrap outline-none">
                    <i data-lucide="calendar" class="w-4 h-4"></i> ปฏิทิน
                </button>
            </li>
            <li>
                <button onclick="switchTab('postpone')" class="nav-item flex items-center gap-2 py-3.5 text-sm whitespace-nowrap outline-none">
                    <i data-lucide="calendar-clock" class="w-4 h-4"></i> เลื่อนแผน PM
                </button>
            </li>
            <li>
                <button onclick="switchTab('plan')" class="nav-item flex items-center gap-2 py-3.5 text-sm whitespace-nowrap outline-none">
                    <i data-lucide="clipboard-list" class="w-4 h-4"></i> จัดการแผน PM
                </button>
            </li>
            <li>
                <button onclick="switchTab('checksheet')" class="nav-item flex items-center gap-2 py-3.5 text-sm whitespace-nowrap outline-none">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> สร้างเช็คชีต
                </button>
            </li>
            <!-- <li>
                <button onclick="switchTab('history')" class="nav-item flex items-center gap-2 py-3.5 text-sm whitespace-nowrap outline-none">
                    <i data-lucide="history" class="w-4 h-4"></i> ประวัติการบำรุงรักษา
                </button>
            </li> -->
            <li>
                <button onclick="switchTab('holiday')" class="nav-item flex items-center gap-2 py-3.5 text-sm whitespace-nowrap outline-none">
                    <i data-lucide="calendar-days" class="w-4 h-4"></i> จัดการวันหยุด
                </button>
            </li>
            <!-- <li>
                <button onclick="switchTab('feedback')" class="nav-item flex items-center gap-2 py-3.5 text-sm whitespace-nowrap outline-none">
                    <i data-lucide="star" class="w-4 h-4"></i> ประเมิน
                </button>
            </li> -->
            <li>
                <button onclick="switchTab('import')" class="nav-item flex items-center gap-2 py-3.5 text-sm whitespace-nowrap outline-none">
                    <i data-lucide="file-up" class="w-4 h-4"></i> นำเข้าแผนจาก Excel
                </button>
            </li>
            <li>
                <button onclick="switchTab('schedule')" class="nav-item flex items-center gap-2 py-3.5 text-sm whitespace-nowrap outline-none">
                    <i data-lucide="table-2" class="w-4 h-4"></i> ตารางแผน PM
                </button>
            </li>
        </ul>
        <!-- มือถือ: เงาจางขอบขวา บอกว่ายังมีแท็บให้เลื่อนดู -->
        <span id="pm-tabs-fade" class="pointer-events-none absolute right-0 top-0 bottom-0 w-10 bg-gradient-to-l from-white to-transparent"></span>
    </div>

    <div class="flex-1 flex flex-col overflow-hidden bg-[var(--color-bg-light)]">
        <main class="flex-1 overflow-y-auto px-2 sm:px-4 pt-3 sm:pt-4 pb-4 relative">
            <?php include 'pm_dashboard.php'; ?>
            <?php include 'pm_postpone.php'; ?>
            <?php include 'pm_plan.php'; ?>
            <?php include 'pm_checksheet.php'; ?>
            <?php include 'pm_history.php'; ?>
            <?php include 'pm_holiday.php'; ?>
            <?php include 'pm_feedback.php'; ?>
            <?php include 'pm_import.php'; ?>
            <?php include 'pm_schedule.php'; ?>
        </main>
    </div>

    <div id="drawer-overlay" class="fixed inset-0 bg-slate-900/40 z-[60] hidden transition-opacity duration-300 opacity-0 backdrop-blur-sm" onclick="closePlanDrawer()"></div>
    
    <div id="plan-drawer" class="fixed top-0 right-0 h-full w-full max-w-md bg-white shadow-2xl z-[70] transform translate-x-full transition-transform duration-300 flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h3 class="text-lg font-bold text-slate-800" id="drawer-title">สร้างแผน PM ใหม่</h3>
            <button onclick="closePlanDrawer()" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-200 rounded-full transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6">
            <form id="plan-form" onsubmit="window.handlePlanSubmit(event)" class="space-y-4">
                <input type="hidden" id="plan-id" value="">
                
                <div>
                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">เลือกอาคาร</label>
                    <select id="plan-building" required class="w-full border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-shadow">
                        <option value="">-- เลือกอาคาร --</option>
                        <option value="อาคาร A">อาคาร A (สำนักงาน)</option>
                        <option value="อาคาร B">อาคาร B (โรงงานผลิต)</option>
                        <option value="อาคาร C">อาคาร C (คลังสินค้า)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">เลือกเครื่องจักร/อุปกรณ์</label>
                    <select id="plan-machine" required class="w-full border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-shadow">
                        <option value="">-- เลือกเครื่องจักร --</option>
                        <option value="AHU-01 (แอร์รวม)">AHU-01 (แอร์รวม)</option>
                        <option value="Chiller-01 (ระบบทำความเย็น)">Chiller-01 (ระบบทำความเย็น)</option>
                        <option value="Air Compressor #1">Air Compressor #1</option>
                        <option value="Generator-01">Generator-01 (เครื่องปั่นไฟ)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">เช็คชีตที่ใช้ (Checksheet)</label>
                    <select id="plan-form-select" required class="w-full border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-shadow">
                        <option value="">-- เลือกรูปแบบฟอร์ม --</option>
                    </select>
                </div>
                <div class="pt-4 mt-2 border-t border-slate-100">
                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">ประเภทความถี่</label>
                    <select id="plan-type" class="w-full border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none mb-4 transition-shadow">
                        <option value="Time-based">ตามระยะเวลา (Time-based)</option>
                        <option value="Usage-based">ตามชั่วโมงการทำงาน (Usage-based)</option>
                    </select>

                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">กำหนดรอบ (ความถี่)</label>
                    <div class="flex gap-2 mb-4">
                        <input type="number" id="plan-cycle-num" value="1" min="1" required class="w-24 border border-slate-300 rounded-lg py-2.5 px-3 text-sm text-center focus:ring-2 focus:ring-sky-500 outline-none transition-shadow">
                        <select id="plan-cycle-unit" class="flex-1 border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-shadow">
                            <option value="เดือน">เดือน</option>
                            <option value="วัน">วัน</option>
                            <option value="ปี">ปี</option>
                        </select>
                    </div>

                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">วันที่เริ่มแผน (Start Date)</label>
                    <input type="date" id="plan-date" required class="w-full border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-shadow mb-4">

                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">ผู้รับผิดชอบ (Assignees)</label>
                    <div class="mb-4">
                        <select id="plan-assignees" multiple required class="w-full text-sm">
                            <option value="สมชาย ช่างยนต์">สมชาย ช่างยนต์</option>
                            <option value="วิชัย การไฟฟ้า">วิชัย การไฟฟ้า</option>
                            <option value="มานะ ซ่อมบำรุง">มานะ ซ่อมบำรุง</option>
                            <option value="ทีม Outsource">ทีม Outsource</option>
                        </select>
                    </div>

                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">การจัดการเมื่อตรงวันหยุด</label>
                    <select id="plan-holiday-action" class="w-full border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-shadow">
                        <option value="เลื่อนไป 1 วัน">เลื่อนไป 1 วันทำการถัดไป</option>
                        <option value="ทำก่อน 1 วัน">เลื่อนมาทำก่อน 1 วันทำการ</option>
                        <option value="ข้าม (หยุดทำ)">ข้ามไปรอบถัดไป</option>
                        <option value="ไม่หยุดทำ (ทำตามปกติ)">ไม่หยุดทำ</option>
                    </select>
                </div>
            </form>
        </div>
        
        <div class="p-5 border-t border-slate-200 bg-white flex justify-end gap-3 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            <button type="button" onclick="closePlanDrawer()" class="px-5 py-2.5 border border-slate-300 rounded-lg text-slate-700 font-medium hover:bg-slate-50 transition-colors text-sm">ยกเลิก</button>
            <button type="submit" form="plan-form" class="btn-gradient px-5 py-2.5 rounded-lg text-white font-medium text-sm flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> บันทึกแผน
            </button>
        </div>
    </div>

    <script>
        window.checksheetTemplates = [];
        window.pmPlans = [];
        window.pmHistory = [];
        window.holidays = [];

        window.calendar = null;
        window.hotChecksheet = null;
        window.checksheetGridOptions = null;
        window.assigneesChoiceInstance = null;

        lucide.createIcons();

        window.switchTab = function(tabId) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
            document.querySelectorAll('.nav-item').forEach(btn => btn.classList.remove('active'));

            const targetTab = document.getElementById('tab-' + tabId);
            if(targetTab) targetTab.classList.remove('hidden');

            const activeBtn = document.querySelector(`button[onclick*="'${tabId}'"]`);
            if (activeBtn) {
                activeBtn.classList.add('active');
                // มือถือ: เลื่อนแถบแท็บให้เห็นแท็บที่เลือก
                const ul = document.getElementById('pm-tabs');
                if (ul) ul.scrollTo({ left: activeBtn.offsetLeft - (ul.clientWidth - activeBtn.offsetWidth) / 2, behavior: 'smooth' });
            }

            localStorage.setItem('activePMTab', tabId);

            if (tabId === 'dashboard') {
                if (window.calendar) { 
                    window.calendar.updateSize();
                    if (typeof window.refreshCalendarEvents === 'function') window.refreshCalendarEvents();
                }
            } else if (tabId === 'postpone') {
                if (typeof window.fetchPostponeData === 'function') {
                    window.fetchPostponeData(); 
                }
                
                setTimeout(() => {
                    if (window.hotInstance) {
                        window.hotInstance.render();
                    }
                }, 100);
            } else if (tabId === 'feedback') {
                // เพิ่มเงื่อนไขนี้เข้าไป
                if (typeof window.initFeedbackTab === 'function') {
                    window.initFeedbackTab();
                }
            } else if (tabId === 'schedule') {
                if (typeof window.initScheduleTab === 'function') {
                    window.initScheduleTab();
                }
            }
        }

        // ---------- แผงซ้าย (ตัวกรอง/ตัวจัดการ) พับได้บนจอใหญ่ — จำสถานะแยกแต่ละแท็บ ----------
        const PM_PANEL_KEY = 'pmPanelCollapsed';
        const pmPanelState = () => { try { return JSON.parse(localStorage.getItem(PM_PANEL_KEY)) || {}; } catch (e) { return {}; } };

        window.togglePmPanel = function(tabId, collapse) {
            const tab = document.getElementById('tab-' + tabId);
            if (!tab) return;
            const on = collapse ?? !tab.classList.contains('pm-collapsed');
            tab.classList.toggle('pm-collapsed', on);
            const state = pmPanelState();
            state[tabId] = on;
            try { localStorage.setItem(PM_PANEL_KEY, JSON.stringify(state)); } catch (e) { /* โหมดส่วนตัว: ไม่จำสถานะ */ }
            // พื้นที่เปลี่ยน => ให้ปฏิทิน/ตารางในแท็บคำนวณขนาดใหม่
            requestAnimationFrame(() => {
                window.dispatchEvent(new Event('resize'));
                if (tabId === 'dashboard' && window.calendar) window.calendar.updateSize();
            });
        };

        Object.entries(pmPanelState()).forEach(([tabId, on]) => {
            document.getElementById('tab-' + tabId)?.classList.toggle('pm-collapsed', !!on);
        });

        (function () {
            const ul = document.getElementById('pm-tabs'), fade = document.getElementById('pm-tabs-fade');
            if (!ul || !fade) return;
            const upd = () => { fade.style.opacity = ul.scrollLeft + ul.clientWidth >= ul.scrollWidth - 4 ? '0' : '1'; };
            ul.addEventListener('scroll', upd, { passive: true });
            window.addEventListener('resize', upd);
            setTimeout(upd, 0);
        })();

        document.addEventListener('DOMContentLoaded', () => {
            const savedTab = localStorage.getItem('activePMTab') || 'dashboard';
            
            // สลับไปยังแท็บที่เคยเปิดไว้
            window.switchTab(savedTab);
        });

        window.openPlanDrawer = function() {
            document.getElementById('drawer-overlay').classList.remove('hidden');
            setTimeout(() => {
                document.getElementById('drawer-overlay').classList.remove('opacity-0');
                document.getElementById('plan-drawer').classList.remove('translate-x-full');
            }, 10);
        }

        window.closePlanDrawer = function() {
            document.getElementById('plan-drawer').classList.add('translate-x-full');
            document.getElementById('drawer-overlay').classList.add('opacity-0');
            setTimeout(() => {
                document.getElementById('drawer-overlay').classList.add('hidden');
            }, 300);
            
            const form = document.getElementById('plan-form');
            if(form) form.reset();
            document.getElementById('plan-id').value = '';
            document.getElementById('drawer-title').innerText = 'สร้างแผน PM ใหม่';
            if(window.assigneesChoiceInstance) {
                window.assigneesChoiceInstance.removeActiveItems();
            }
        }

        window.formatDate = function(dateStr, showTime = true) {
            if (!dateStr || dateStr === '0000-00-00' || dateStr === '0000-00-00 00:00:00') return '-';
            
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;

            const datePart = d.toLocaleDateString('th-TH', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });

            if (!showTime) {
                return datePart;
            }

            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');

            return `${datePart}, ${hours}:${minutes} น.`;
        };
    </script>
</body>
</html>