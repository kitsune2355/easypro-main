<?php 
@session_start();
include "config_ctrl/checksession.php"; 
//pm_dashboard.php
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<style>
    /* ตั้งค่าความสูงเริ่มต้นสำหรับ Desktop */
    #tab-dashboard {
        height: calc(100vh - 180px);
        min-height: 500px;
    }
    
    /* ให้ Container ของ Calendar กินพื้นที่ 1fr ได้อย่างสมบูรณ์ */
    #pm-calendar {
        height: 100% !important;
        min-height: 400px;
    }
    
    .fc { height: 100% !important; }
    .fc-scroller { overflow-y: auto !important; }

    /* ===== Chart.js-inspired look: เส้นบาง สีนุ่มนวล ตัวอักษรสะอาด มุมโค้งมน ===== */

    /* พื้นฐานตัวอักษรและสีเส้นกริด ให้ดูเบา สะอาด เหมือน Chart.js */
    .fc, .fc .fc-toolbar-title, .fc th, .fc td {
        font-family: inherit;
    }
    .fc-theme-standard td,
    .fc-theme-standard th,
    .fc-theme-standard .fc-scrollgrid {
        border-color: #eef1f5 !important;
    }
    .fc-theme-standard .fc-scrollgrid {
        border-width: 1px !important;
        border-radius: 12px;
        overflow: hidden;
    }

    /* Toolbar: ทำให้เล็กกะทัดรัด เบาสบายตา, ปุ่มโค้งมน ไม่มีเงาแข็งกระด้าง */
    .fc .fc-header-toolbar {
        margin-bottom: 0.75em !important;
        gap: 8px;
    }
    .fc .fc-toolbar-title {
        font-size: 0.95rem !important;
        font-weight: 600;
        color: #1e293b;
        letter-spacing: -0.01em;
    }
    .fc .fc-toolbar-chunk {
        display: flex;
        align-items: center;
    }
    .fc .fc-button {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        color: #475569 !important;
        box-shadow: none !important;
        border-radius: 6px !important;
        font-weight: 500;
        font-size: 0.78rem !important;
        text-transform: none !important;
        padding: 0.28em 0.65em !important;
        line-height: 1.4 !important;
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
    .fc .fc-icon {
        font-size: 0.95em;
    }
    .fc .fc-button:hover {
        background: #f1f5f9 !important;
        border-color: #cbd5e1 !important;
        color: #0f172a !important;
    }
    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
        background: #0284c7 !important;
        border-color: #0284c7 !important;
        color: #ffffff !important;
        box-shadow: none !important;
    }
    .fc .fc-button:focus,
    .fc .fc-button-primary:focus {
        box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
    }
    .fc .fc-button-group .fc-button {
        border-radius: 0 !important;
    }
    .fc .fc-button-group .fc-button:first-child { border-top-left-radius: 8px !important; border-bottom-left-radius: 8px !important; }
    .fc .fc-button-group .fc-button:last-child { border-top-right-radius: 8px !important; border-bottom-right-radius: 8px !important; }

    /* หัวคอลัมน์วัน (จ. อ. พ. ...) ให้ดูเบาบางแบบ axis label ของกราฟ */
    .fc-theme-standard th {
        background: #fafbfc;
    }
    .fc .fc-col-header-cell-cushion {
        color: #64748b;
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding: 10px 4px;
        text-decoration: none;
    }

    /* ตัวเลขวันที่ */
    .fc .fc-daygrid-day-number {
        color: #334155;
        font-size: 0.8rem;
        font-weight: 500;
        padding: 6px 8px;
        text-decoration: none;
    }
    .fc .fc-daygrid-day-frame {
        transition: background 0.15s ease;
    }
    .fc .fc-daygrid-day:hover .fc-daygrid-day-frame {
        background: #f8fafc;
    }

    /* วันนี้: ไฮไลต์นุ่มๆ แบบสีพื้นหลังของกราฟ ไม่ใช้สีเหลืองแข็งแบบดีฟอลต์ */
    .fc .fc-day-today {
        background: rgba(2, 132, 199, 0.06) !important;
    }
    .fc .fc-day-today .fc-daygrid-day-number {
        color: #0284c7;
        font-weight: 700;
    }

    /* Event: ทำให้เป็นแคปซูลโค้งมน มีระยะห่าง คล้าย data point/legend ของ Chart.js */
    .fc-event {
        cursor: pointer !important;
        border-radius: 6px !important;
        border-width: 0 !important;
        padding: 2px 6px !important;
        margin: 1px 3px !important;
        font-size: 0.72rem !important;
        font-weight: 500;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        transition: transform 0.12s ease, box-shadow 0.12s ease;
    }
    .fc-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(15, 23, 42, 0.12);
    }
    .fc-daygrid-event-dot {
        display: none;
    }
    .fc-h-event .fc-event-title,
    .fc-h-event .fc-event-time {
        color: #ffffff;
    }

    /* วันหยุด background event ให้จางนุ่มแบบพื้นหลังกริดของกราฟ */
    .fc-bg-event {
        opacity: 0.5 !important;
    }

    /* List view (กำหนดการ) ให้ดูเป็นรายการสะอาดแบบ tooltip/legend list */
    .fc .fc-list {
        border-color: #eef1f5 !important;
        border-radius: 12px;
        overflow: hidden;
    }
    .fc .fc-list-day-cushion {
        background: #fafbfc !important;
    }
    .fc .fc-list-event:hover td {
        background: #f8fafc !important;
    }
    .fc-list-event-dot {
        border-width: 5px !important;
    }

    .fc-theme-standard .fc-popover {
        z-index: 9999 !important;
        border-radius: 12px !important;
        border-color: #e2e8f0 !important;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12) !important;
        overflow: hidden;
    }
    .fc-theme-standard .fc-popover-header {
        background: #fafbfc !important;
        padding: 8px 10px !important;
    }
    .fc-theme-standard .fc-popover .fc-popover-body {
        max-height: 250px !important;
        overflow-y: auto !important;  
        padding: 6px !important;
    }

    .swal2-container {
        z-index: 10000 !important; 
    }
    
    /* ซ่อน Scrollbar นอกเฉพาะบน Desktop (จอใหญ่) */
    @media (min-width: 769px) {
        body { overflow: hidden; }
    }

    /* การแสดงผลบน Mobile (จอเล็ก) */
    @media (max-width: 768px) {
        body { overflow-y: auto !important; /* สำคัญ: ต้องให้มือถือ Scroll ลงได้ */ }
        
        #tab-dashboard {
            height: auto !important;
            min-height: calc(100vh - 80px);
        }
        
        /* กำหนดความสูงของกล่องปฏิทินบนมือถือให้ชัดเจน เพื่อไม่ให้โดนบีบ */
        .calendar-wrapper {
            height: 75vh !important;
            min-height: 550px;
            padding: 1rem !important; /* ลด padding ลงนิดหน่อยเพิ่มพื้นที่ */
        }

        /* จัดระเบียบ Header ของ FullCalendar ไม่ให้เละเมื่อจอแคบ */
        .fc .fc-toolbar {
            flex-direction: column;
            gap: 8px;
        }
        .fc .fc-toolbar-title {
            font-size: 1rem !important;
        }

        /* Sidebar สไตล์เดิมของคุณ */
        .filter-sidebar {
            position: fixed;
            left: -100%;
            top: 0;
            bottom: 0;
            width: 280px;
            z-index: 100;
            transition: 0.3s;
            background: white;
            box-shadow: 10px 0 15px -3px rgba(0, 0, 0, 0.1);
        }
        .filter-sidebar.active { left: 0; }
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 90;
        }
        .sidebar-overlay.active { display: block; }
    }
</style>

<div id="sidebar-overlay" class="sidebar-overlay" onclick="toggleSidebar()"></div>

<div id="tab-dashboard" class="tab-content block h-full">
    <div class="grid grid-cols-1 md:grid-cols-[384px_1fr] gap-4 h-full">
        
        <aside id="filter-sidebar" class="filter-sidebar grid grid-rows-[auto_1fr_auto] bg-white p-5 md:rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="grid grid-cols-[1fr_auto] items-center mb-4">
                <h3 class="text-base font-bold text-slate-800 grid grid-flow-col auto-cols-max items-center gap-2">
                    <i class="fas fa-filter text-sky-600"></i> ตัวกรองขั้นสูง
                </h3>
                <div class="flex items-center">
                    <button type="button" class="hidden md:inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" onclick="togglePmPanel('dashboard', true)" title="ซ่อนแผงตัวกรอง">
                        <i class="fas fa-angles-left"></i>
                    </button>
                    <button class="md:hidden text-slate-400" onclick="toggleSidebar()">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            </div>

            <div class="space-y-4 overflow-y-auto pr-1">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">เครื่องจักร / อุปกรณ์</label>
                    <div class="relative">
                        <input type="text" id="filter-machine" placeholder="ระบุชื่อ หรือเลือกจากรายการ..." autocomplete="off"
                            class="w-full border-slate-200 rounded-lg pl-3 pr-10 py-1.5 focus:ring-sky-500 focus:border-sky-500 shadow-sm text-xs cursor-pointer">
                        
                        <button type="button" id="clear-filter-machine" onclick="clearFilterInput('filter-machine')" class="absolute right-6 text-slate-300 hover:text-red-500 hidden transition-colors z-10">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                        
                        <i class="fas fa-chevron-down absolute right-2.5 top-2.5 text-slate-400 text-[10px] pointer-events-none"></i>
                        <ul id="dropdown-filter-machine" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-48 overflow-y-auto hidden">
                        </ul>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">เช็คชีต</label>
                    <div class="relative">
                        <input type="text" id="filter-checksheet" placeholder="ระบุชื่อ หรือเลือกจากรายการ..." autocomplete="off"
                            class="w-full border-slate-200 rounded-lg pl-3 pr-10 py-1.5 focus:ring-sky-500 focus:border-sky-500 shadow-sm text-xs cursor-pointer">
                        
                        <button type="button" id="clear-filter-checksheet" onclick="clearFilterInput('filter-checksheet')" class="absolute right-6 text-slate-300 hover:text-red-500 hidden transition-colors z-10">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                        
                        <i class="fas fa-chevron-down absolute right-2.5 top-2.5 text-slate-400 text-[10px] pointer-events-none"></i>
                        <ul id="dropdown-filter-checksheet" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-48 overflow-y-auto hidden">
                        </ul>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">ประเภทเครื่องจักร</label>
                    <div class="relative">
                        <input type="text" id="filter-type" placeholder="ค้นหาประเภท..." autocomplete="off"
                            class="w-full border-slate-200 rounded-lg pl-3 pr-10 py-1.5 focus:ring-sky-500 focus:border-sky-500 shadow-sm text-xs cursor-pointer">
                        
                        <button type="button" id="clear-filter-type" onclick="clearFilterInput('filter-type')" class="absolute right-6 text-slate-300 hover:text-red-500 hidden transition-colors z-10">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                        
                        <i class="fas fa-chevron-down absolute right-2.5 top-2.5 text-slate-400 text-[10px] pointer-events-none"></i>
                        <ul id="dropdown-filter-type" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-48 overflow-y-auto hidden">
                        </ul>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">สถานที่ตั้ง</label>
                    <div class="relative">
                        <input type="text" id="filter-location" placeholder="ค้นหาสถานที่..." autocomplete="off"
                            class="w-full border-slate-200 rounded-lg pl-3 pr-10 py-1.5 focus:ring-sky-500 focus:border-sky-500 shadow-sm text-xs cursor-pointer">
                        
                        <button type="button" id="clear-filter-location" onclick="clearFilterInput('filter-location')" class="absolute right-6 text-slate-300 hover:text-red-500 hidden transition-colors z-10">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>

                        <i class="fas fa-chevron-down absolute right-2.5 top-2.5 text-slate-400 text-[10px] pointer-events-none"></i>
                        <ul id="dropdown-filter-location" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-48 overflow-y-auto hidden">
                        </ul>
                    </div>
                </div>
            </div>

            <div class="pt-4 mt-4 border-t border-slate-100 grid grid-cols-2 gap-2">
                <button onclick="resetFiltersDashboard()" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium py-3 rounded-lg transition duration-200 text-xs">
                    ล้างค่า
                </button>
                <button onclick="applyDashboardFilters()" class="bg-sky-600 hover:bg-sky-700 text-white font-semibold py-3 rounded-lg transition duration-200 shadow-md text-xs">
                    กรองข้อมูล
                </button>
            </div>
        </aside>

        <div class="calendar-wrapper bg-white p-5 rounded-xl shadow-sm border border-slate-200 relative grid grid-rows-[auto_1fr] h-full">
            <div class="grid grid-cols-[1fr_auto] items-center mb-4">
                <div class="grid grid-flow-col auto-cols-max items-center gap-3">
                    <button onclick="toggleSidebar()" class="md:hidden p-2 bg-sky-50 text-sky-600 rounded-lg">
                        <i class="fas fa-filter"></i>
                    </button>
                    <button type="button" onclick="togglePmPanel('dashboard', false)" class="pm-expand-btn items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 text-sm font-medium" title="แสดงแผงตัวกรอง">
                        <i class="fas fa-angles-right"></i> ตัวกรอง
                    </button>
                    <h3 class="text-lg font-semibold text-slate-800">ปฏิทินปฏิบัติงาน PM</h3>
                </div>
                
                <div class="hidden lg:grid grid-flow-col auto-cols-max items-center gap-4 text-[10px] font-bold uppercase tracking-wider">
                    <span class="grid grid-cols-[auto_1fr] items-center gap-1.5 text-slate-600">
                        <span class="w-2.5 h-2.5 bg-sky-600 rounded-full shadow-sm shadow-sky-100"></span> 
                        แผนงาน PM
                    </span>

                    <span class="grid grid-cols-[auto_1fr] items-center gap-1.5 text-slate-600">
                        <span class="w-2.5 h-2.5 bg-[#0d9f00] rounded-full shadow-sm shadow-sky-100"></span> 
                        แผนงาน PM (ทำแล้ว)
                    </span>

                    <span class="grid grid-cols-[auto_1fr] items-center gap-1.5 text-slate-600">
                        <span class="w-2.5 h-2.5 bg-[#0ea5e9] rounded-full shadow-sm"></span> 
                        วันหยุดพิเศษ
                    </span>

                    <span class="grid grid-cols-[auto_1fr] items-center gap-1.5 text-slate-600">
                        <span class="w-2.5 h-2.5 bg-[#E11D48] rounded-full shadow-sm shadow-rose-100"></span> 
                        วันหยุดประจำปี
                    </span>

                    <span class="grid grid-cols-[auto_1fr] items-center gap-1.5 text-slate-600">
                        <span class="w-2.5 h-2.5 bg-[#94a3b8] rounded-full shadow-sm shadow-slate-100"></span> 
                        หยุดประจำสัปดาห์
                    </span>

                    <button onclick="exportCalendarToExcel()" class="ml-2 grid grid-flow-col auto-cols-max items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white py-1.5 px-3 rounded-lg transition shadow-md shadow-emerald-100">
                        <i class="fas fa-file-excel"></i> Export
                    </button>
                </div>
            </div>

            <div id="calendar-loading" class="absolute inset-0 bg-white/95 z-50 hidden flex-col p-4">
                <div class="flex flex-col h-full w-full animate-pulse">
                    
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex space-x-1">
                            <div class="w-8 h-8 bg-slate-200 rounded-md"></div>
                            <div class="w-8 h-8 bg-slate-200 rounded-md"></div>
                            <div class="w-16 h-8 bg-slate-200 rounded-md ml-2"></div>
                        </div>
                        <div class="w-48 h-8 bg-slate-200 rounded-md"></div>
                        <div class="flex">
                            <div class="w-16 h-8 bg-slate-200 rounded-l-md border-r border-white"></div>
                            <div class="w-16 h-8 bg-slate-200 border-r border-white"></div>
                            <div class="w-16 h-8 bg-slate-200 rounded-r-md"></div>
                        </div>
                    </div>

                    <div class="flex-1 bg-slate-200 border border-slate-200 grid grid-cols-7 gap-px rounded-sm overflow-hidden">
                        
                        <div class="bg-white py-2 flex justify-center items-center"><div class="w-8 h-4 bg-slate-200 rounded"></div></div>
                        <div class="bg-white py-2 flex justify-center items-center"><div class="w-8 h-4 bg-slate-200 rounded"></div></div>
                        <div class="bg-white py-2 flex justify-center items-center"><div class="w-8 h-4 bg-slate-200 rounded"></div></div>
                        <div class="bg-white py-2 flex justify-center items-center"><div class="w-8 h-4 bg-slate-200 rounded"></div></div>
                        <div class="bg-white py-2 flex justify-center items-center"><div class="w-8 h-4 bg-slate-200 rounded"></div></div>
                        <div class="bg-white py-2 flex justify-center items-center"><div class="w-8 h-4 bg-slate-200 rounded"></div></div>
                        <div class="bg-white py-2 flex justify-center items-center"><div class="w-8 h-4 bg-slate-200 rounded"></div></div>

                        <div class="bg-white p-1 flex flex-col min-h-[80px]">
                            <div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div> </div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]">
                            <div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div>
                            <div class="w-full h-5 bg-sky-100 rounded mb-1"></div> </div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]">
                            <div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div>
                            <div class="w-full h-5 bg-sky-100 rounded mb-1"></div>
                            <div class="w-3/4 h-5 bg-slate-100 rounded"></div>
                        </div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]">
                            <div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div>
                        </div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]">
                            <div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div>
                            <div class="w-full h-5 bg-slate-100 rounded mb-1"></div>
                        </div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]">
                            <div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div>
                        </div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]">
                            <div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div>
                        </div>

                        <div class="bg-white p-1 flex flex-col min-h-[80px]">
                            <div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div>
                            <div class="w-full h-5 bg-slate-100 rounded"></div>
                        </div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]">
                            <div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div>
                            <div class="w-full h-5 bg-sky-100 rounded mb-1"></div>
                        </div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]">
                            <div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div>
                            <div class="w-full h-5 bg-slate-100 rounded"></div>
                        </div>

                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div><div class="w-full h-5 bg-slate-100 rounded mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div><div class="w-1/2 h-5 bg-slate-100 rounded mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div><div class="w-full h-5 bg-sky-100 rounded mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>
                        <div class="bg-white p-1 flex flex-col min-h-[80px]"><div class="w-4 h-4 bg-slate-200 rounded self-end mb-1"></div></div>

                    </div>
                </div>
            </div>
            
            <div id="pm-calendar"></div>
        </div>
    </div>
</div>

<script>
    const AG_ID = '<?php echo isset($sess_user_agency_es) ? $sess_user_agency_es : ""; ?>';
    const USER_ID = '<?php echo isset($sess_user_id) ? $sess_user_id : ""; ?>';

    const isMobile = window.innerWidth < 768;

    function toggleSidebar() {
        document.getElementById('filter-sidebar').classList.toggle('active');
        document.getElementById('sidebar-overlay').classList.toggle('active');
    }

    // ฟังก์ชันหลักดึงข้อมูลจาก API
    async function loadDashboardCustomSelectFilters() {
        try {
            // ตรวจสอบ AG_ID (ใช้ค่าว่างหากไม่มี)
            const agIdParam = typeof AG_ID !== 'undefined' ? AG_ID : '';
            
            // กำหนด URL (ดึงมา 1000 รายการเพื่อให้ครอบคลุมการค้นหาในหน้า Dashboard)
            const urlMachine = `handle_machine_info.php?action=get_all&ag_id=${agIdParam}&startRow=0&endRow=1000`;
            const urlChecksheet = `handle_pm_checksheet.php?action=get_all&ag_id=${agIdParam}&startRow=0&endRow=1000`;

            // ดึง 2 API พร้อมกัน
            const [resMachine, resChecksheet] = await Promise.all([
                fetch(urlMachine).then(r => r.json()),
                fetch(urlChecksheet).then(r => r.json())
            ]);

            // --- 1. จัดเตรียมข้อมูล เครื่องจักร (อิงตาม handle_machine_info.php) ---
            let machineItems = [{ text: 'ทั้งหมด', value: '' }];
            // API เครื่องจักรส่งกลับมาในรูปแบบ { rows: [...] }
            if (resMachine.rows && Array.isArray(resMachine.rows)) {
                resMachine.rows.forEach(item => {
                    // ใช้ฟิลด์ machine_name ตามที่ระบุใน PHP
                    const name = item.machine_name; 
                    if (name) {
                        machineItems.push({ text: name, value: name });
                    }
                });
            }

            // --- 2. จัดเตรียมข้อมูล เช็คชีต (อิงตาม handle_pm_checksheet.php) ---
            let checksheetItems = [{ text: 'ทั้งหมด', value: '' }];
            // API เช็คชีตส่งกลับมาในรูปแบบ { success: true, data: [...] }
            if (resChecksheet.success && resChecksheet.data) {
                resChecksheet.data.forEach(item => {
                    const name = item.name || item.checksheet_name;
                    if (name) {
                        checksheetItems.push({ text: name, value: name });
                    }
                });
            }

            // --- 3. ติดตั้ง Custom Select (เรียกใช้ฟังก์ชัน setupCustomSelect เดิม) ---
            setupCustomSelect('filter-machine', 'dropdown-filter-machine', machineItems);
            setupCustomSelect('filter-checksheet', 'dropdown-filter-checksheet', checksheetItems);

        } catch (error) {
            console.error("เกิดข้อผิดพลาดในการโหลดข้อมูล Filter:", error);
        }
    }

    // ฟังก์ชันดึงข้อมูลประเภทเครื่องจักร
   async function loadDashboardMachineTypes() {
		try {
			const agId = (typeof AG_ID !== 'undefined' && AG_ID)
				? AG_ID
				: (window.AG_ID || '');
	
			if (!agId) {
				console.error('ไม่พบ AG_ID สำหรับโหลดประเภทเครื่องจักร');
				setupCustomSelect('filter-type', 'dropdown-filter-type', [
					{ text: 'ทั้งหมด', value: '' }
				]);
				return;
			}
	
			const response = await fetch(
				`get_all_ass_type.php?ag_id=${encodeURIComponent(agId)}`
			);
	
			const data = await response.json();
	
			let items = [{ text: 'ทั้งหมด', value: '' }];
	
			if (data && Array.isArray(data)) {
				data.forEach(type => {
					items.push({
						text: type.name || type.TGroupName,
						value: type.name || type.TGroupName
					});
				});
			}
	
			setupCustomSelect('filter-type', 'dropdown-filter-type', items);
	
		} catch (error) {
			console.error("เกิดข้อผิดพลาดในการดึงข้อมูลประเภทเครื่องจักร:", error);
		}
	}

    // ฟังก์ชันดึงข้อมูลสถานที่ตั้ง
    async function loadDashboardLocations() {
        try {
            const response = await fetch(`get_all_building.php?ag_id=${AG_ID}`);
            const data = await response.json();

            let items = [{ text: 'ทั้งหมด', value: '' }];

            if (data && data.building && Array.isArray(data.building)) {
                data.building.forEach(building => {
                    items.push({ 
                        text: building.area_name, 
                        value: building.area_name 
                    });
                });
            }

            setupCustomSelect('filter-location', 'dropdown-filter-location', items);

        } catch (error) {
            console.error("เกิดข้อผิดพลาดในการดึงข้อมูลสถานที่:", error);
        }
    }

    function clearFilterInput(inputId) {
        const inputEl = document.getElementById(inputId);
        inputEl.value = '';
        inputEl.dispatchEvent(new Event('input'));
        inputEl.focus(); 
    }

    function setupCustomSelect(inputId, dropdownId, items) {
        const inputEl = document.getElementById(inputId);
        const dropdownEl = document.getElementById(dropdownId);
        const clearBtn = document.getElementById('clear-' + inputId);
        
        // ฟังก์ชันย่อยสำหรับซ่อน/แสดงปุ่มกากบาท
        const toggleClearBtn = () => {
            if (clearBtn) {
                if (inputEl.value.trim().length > 0) {
                    clearBtn.classList.remove('hidden');
                } else {
                    clearBtn.classList.add('hidden');
                }
            }
        };

        const renderList = (filterText = '') => {
            dropdownEl.innerHTML = '';
            const filtered = items.filter(item => 
                item.text.toLowerCase().includes(filterText.toLowerCase())
            );

            if (filtered.length === 0) {
                dropdownEl.innerHTML = `<li class="px-3 py-2 text-xs text-slate-400 italic">ไม่พบข้อมูล</li>`;
                return;
            }

            filtered.forEach(item => {
                const li = document.createElement('li');
                li.className = 'px-3 py-2 text-xs text-slate-700 cursor-pointer hover:bg-sky-50 border-b border-slate-50 last:border-none';
                li.textContent = item.text;
                
                li.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    inputEl.value = item.text; 
                    dropdownEl.classList.add('hidden');
                    toggleClearBtn();
                });
                dropdownEl.appendChild(li);
            });
        };

        inputEl.addEventListener('focus', () => {
            renderList(inputEl.value);
            dropdownEl.classList.remove('hidden');
        });

        inputEl.addEventListener('input', (e) => {
            renderList(e.target.value);
            dropdownEl.classList.remove('hidden');
            toggleClearBtn(); // อัปเดตปุ่มตอนพิมพ์ข้อความ
        });

        inputEl.addEventListener('blur', () => {
            setTimeout(() => dropdownEl.classList.add('hidden'), 200);
        });
    }


    function resetFiltersDashboard() {
        const filters = ['filter-machine', 'filter-type', 'filter-location', 'filter-checksheet'];
        
        filters.forEach(id => {
            document.getElementById(id).value = '';
            const clearBtn = document.getElementById('clear-' + id);
            if (clearBtn) clearBtn.classList.add('hidden'); // ซ่อนกากบาท
        });
        
        window.refreshCalendarEvents(); 
        if (window.innerWidth < 768) toggleSidebar();
    }

    function applyDashboardFilters() {
        window.refreshCalendarEvents();
        if (window.innerWidth < 768) toggleSidebar();
    }

    async function exportCalendarToExcel() {
        if (!window.calendar) return;

        if (typeof ExcelJS === 'undefined') {
            Swal.fire('ข้อผิดพลาด', 'ไม่พบไลบรารี ExcelJS กรุณาตรวจสอบการติดตั้ง', 'error');
            return;
        }

        const view = window.calendar.view;
        // ดึงข้อมูลทั้งหมด และกรอง "เฉพาะข้อมูลที่อยู่ในช่วงวันที่กำลังแสดงผลบนหน้าจอ" เท่านั้น
        const activeStart = view.activeStart;
        const activeEnd = view.activeEnd;
        const events = window.calendar.getEvents().filter(e => {
            return e.start >= activeStart && e.start < activeEnd;
        });

        const viewTitle = view.title.replace(/[/\\?%*:|"<>]/g, '-');

        if (events.length === 0) {
            Swal.fire('ไม่พบข้อมูล', 'ไม่มีข้อมูลในมุมมองนี้ที่สามารถ Export ได้', 'warning');
            return;
        }

        // สร้าง Workbook ใหม่
        const workbook = new ExcelJS.Workbook();
        workbook.creator = 'PM System';

        // เช็คว่าผู้ใช้กำลังดูมุมมองแบบไหน
        const isMonthView = view.type === 'dayGridMonth';

        // ==========================================
        // 1. ถ้าเป็นมุมมอง "รายเดือน" ให้วาดตารางแบบปฏิทิน
        // ==========================================
        if (isMonthView) {
            const calSheet = workbook.addWorksheet('Calendar View', { views: [{ showGridLines: false }] });
            
            // กำหนดความกว้างของคอลัมน์ (อาทิตย์ - เสาร์)
            calSheet.columns = [
                { width: 18 }, { width: 18 }, { width: 18 }, { width: 18 },
                { width: 18 }, { width: 18 }, { width: 18 }
            ];

            // --- หัวตาราง (ชื่อเดือน) ---
            const titleRow = calSheet.addRow([`แผนปฏิบัติการ PM : ${view.title}`]);
            calSheet.mergeCells('A1:G1');
            titleRow.getCell(1).font = { size: 16, bold: true, color: { argb: 'FFFFFFFF' } };
            titleRow.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0d6efd' } }; // สีน้ำเงิน
            titleRow.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            titleRow.height = 35;

            // --- หัวตาราง (วันอาทิตย์-เสาร์) ---
            const days = ["อาทิตย์", "จันทร์", "อังคาร", "พุธ", "พฤหัสบดี", "ศุกร์", "เสาร์"];
            const headerRow = calSheet.addRow(days);
            headerRow.height = 25;
            headerRow.eachCell((cell, colNumber) => {
                cell.font = { bold: true, color: { argb: 'FFFFFFFF' } };
                // วันอาทิตย์และวันเสาร์สีเทาอ่อน, วันปกติสีเทาเข้ม
                cell.fill = { 
                    type: 'pattern', 
                    pattern: 'solid', 
                    fgColor: { argb: (colNumber === 1 || colNumber === 7) ? '94a3b8' : 'FF6c757d' } 
                };
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
            });

            // --- จัดกลุ่มข้อมูลตามวันที่ ---
            let eventMap = {};
            events.forEach(e => {
                if (e.start) {
                    let dateStr = moment(e.start).format('YYYY-MM-DD');
                    if (!eventMap[dateStr]) eventMap[dateStr] = [];
                    
                    let isHoliday = e.id.startsWith('hol_');
                    let prefix = isHoliday ? '' : '• ';
                    
                    let richTextParts = [];
                    
                    if (!isHoliday) {
                        let isCompleted = (e.extendedProps?.status == 1 || e.extendedProps?.status == '1');
                        
                        // 1. จัดการข้อความสถานะและวันที่ทำเสร็จ
                        if (isCompleted && e.extendedProps?.completed_at) {
                            // ถ้าทำแล้ว และมีวันที่ ให้ดึงวันที่มาฟอร์แมต
                            let compDate = moment(e.extendedProps.completed_at).format('DD/MM/YYYY');
                            // สังเกตการใช้เครื่องหมาย ` (Backtick) เพื่อให้ ${compDate} ทำงาน
                            richTextParts.push({ text: `[ทำแล้ว ${compDate}] `, font: { color: { argb: 'FF00B050' }, bold: true } });
                        } else if (isCompleted) {
                            // เผื่อกรณีที่สถานะเป็น "ทำแล้ว" แต่เผลอไม่มีข้อมูลวันที่ในระบบ
                            richTextParts.push({ text: '[ทำแล้ว] ', font: { color: { argb: 'FF00B050' }, bold: true } });
                        } else {
                            // ถ้ายังไม่ทำ ไม่ต้องมีตัวแปรวันที่
                            richTextParts.push({ text: '[ยังไม่ทำ] ', font: { color: { argb: 'FFFF0000' }, bold: true } });
                        }
                        
                        // 2. ประกาศตัวแปร itemTitle ก่อนเรียกใช้
                        let itemTitle = prefix + e.title;
                        richTextParts.push({ text: itemTitle, font: { color: { argb: 'FF000000' } } });
                        
                    } else {
                        richTextParts.push({ text: prefix + e.title, font: { color: { argb: 'FF0070C0' } } });
                    }
                    
                    eventMap[dateStr].push({ 
                        richTextData: richTextParts, 
                        isHoliday: isHoliday 
                    });
                }
            });

            // --- สร้างช่องปฏิทิน ---
            let currentDate = window.calendar.getDate();
            let year = currentDate.getFullYear();
            let month = currentDate.getMonth();
            let firstDay = new Date(year, month, 1);
            let lastDay = new Date(year, month + 1, 0);

            let currentWeekRow = [];
            let startDayOfWeek = firstDay.getDay();

            // เติมช่องว่างก่อนวันที่ 1
            for (let i = 0; i < startDayOfWeek; i++) currentWeekRow.push("");

            for (let d = 1; d <= lastDay.getDate(); d++) {
                let dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                
                // เตรียมข้อมูลที่จะใส่ในช่อง
                let cellData;
                
                if (eventMap[dateStr] && eventMap[dateStr].length > 0) {
                    // สร้าง array สำหรับเก็บข้อความแบบ Rich Text
                    let richTextArray = [
                        { font: { color: { argb: 'FF000000' }, bold: true }, text: `${d}\n\n` } // เลขวันที่สีดำตัวหนา
                    ];

                    // วนลูปใส่รายการงานของวันนั้นๆ (ส่วนที่แก้ไขให้ดึงข้อมูลสีมาใช้)
                    eventMap[dateStr].forEach((ev, index) => {
                        // กระจายชุดข้อความและสีที่เตรียมไว้มาใส่ในเซลล์
                        richTextArray.push(...ev.richTextData);
                        
                        // เพิ่มเว้นบรรทัด (\n) ถ้าไม่ใช่รายการสุดท้าย
                        if (index < eventMap[dateStr].length - 1) {
                            richTextArray.push({ text: '\n' });
                        }
                    });

                    cellData = { richText: richTextArray };
                } else {
                    // ถ้าไม่มี Event ใส่เป็นตัวเลขธรรมดา
                    cellData = `${d}`; 
                }
                
                currentWeekRow.push(cellData);

                // เมื่อครบ 1 สัปดาห์ (หรือวันสุดท้ายของเดือน) ให้ขึ้นบรรทัดใหม่
                if (currentWeekRow.length === 7 || d === lastDay.getDate()) {
                    while (currentWeekRow.length < 7) currentWeekRow.push(""); // เติมช่องว่างสัปดาห์สุดท้ายให้เต็ม
                    
                    let row = calSheet.addRow(currentWeekRow);
                    row.height = 120; // ตั้งความสูงให้เหมือนช่องปฏิทิน

                    row.eachCell((cell, colNumber) => {
                        cell.alignment = { vertical: 'top', horizontal: 'left', wrapText: true };
                        cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
                        
                        // ทำสีพื้นหลังช่องวันอาทิตย์และวันเสาร์ ให้เป็นสีเทาอ่อน
                        if (colNumber === 1 || colNumber === 7) {
                            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF8F9FA' } };
                        }
                    });
                    currentWeekRow = [];
                }
            }
        }

        // ==========================================
        // 2. สร้างหน้า Data List สำหรับทุกมุมมอง (เป็นข้อมูลดิบจัดรูปแบบสวยงาม)
        // ==========================================
        const listSheetName = isMonthView ? 'Data List' : `Schedule (${view.title.substring(0,20)})`;
        const listSheet = workbook.addWorksheet(listSheetName);
        
        listSheet.columns = [
            { header: 'วันที่', key: 'date', width: 15 },
            { header: 'รายการ', key: 'title', width: 40 },
            { header: 'ประเภท', key: 'type', width: 15 },
            { header: 'สถานที่', key: 'location', width: 25 },
            { header: 'สถานะ', key: 'status', width: 15 },
            { header: 'วันที่ทำเสร็จ', key: 'completed_at', width: 20 },
        ];

        // ตกแต่งหัวตารางหน้า List
        listSheet.getRow(1).eachCell(cell => {
            cell.font = { bold: true, color: { argb: 'FFFFFFFF' } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF198754' } }; // สีเขียว
            cell.alignment = { vertical: 'middle', horizontal: 'center' };
            cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
        });

        // ใส่ข้อมูลเรียงตามวันที่
        let sortedEvents = events.sort((a, b) => a.start - b.start);
        sortedEvents.forEach(e => {
            let isHoliday = e.id.startsWith('hol_');
            
            let statusLabel = '-';
            let statusColor = 'FF000000'; 
            let completedDateLabel = '-'; // <--- ค่าเริ่มต้นสำหรับคนที่ยังไม่ทำ

            if (!isHoliday) {
                if (e.extendedProps?.status == 1 || e.extendedProps?.status == '1') {
                    statusLabel = 'ทำแล้ว';
                    statusColor = 'FF00B050'; 
                    
                    // จัดฟอร์แมตวันที่ทำเสร็จ (สมมติใน DB เก็บเป็น YYYY-MM-DD HH:mm:ss)
                    if (e.extendedProps?.completed_at) {
                        completedDateLabel = moment(e.extendedProps.completed_at).format('DD/MM/YYYY HH:mm'); 
                    }
                } else {
                    statusLabel = 'ยังไม่ทำ';
                    statusColor = 'FFFF0000'; 
                }
            }

            let row = listSheet.addRow({
                date: moment(e.start).format('DD/MM/YYYY'),
                title: e.title,
                status: statusLabel, 
                type: isHoliday ? 'วันหยุด' : 'แผน PM',
                location: e.extendedProps?.location || '-',
                completed_at: completedDateLabel // <--- โยนตัวแปรวันที่ทำเสร็จลงคอลัมน์นี้
            });

            let statusCell = row.getCell('status');
            statusCell.font = { color: { argb: statusColor }, bold: true };

            row.eachCell(cell => {
                cell.alignment = { vertical: 'middle', wrapText: true };
                cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
            });

            if (isHoliday) {
                row.eachCell(cell => {
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFFF3CD' } };
                });
            }
        });

        // ==========================================
        // ดาวน์โหลดไฟล์
        // ==========================================
        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        saveAs(blob, `PM_Schedule_${viewTitle}.xlsx`);
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof loadDashboardLocations === 'function') loadDashboardLocations();
        loadDashboardMachineTypes();
        loadDashboardCustomSelectFilters();
        
        const calendarEl = document.getElementById('pm-calendar');
        
        if (calendarEl) {
            window.calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'th',
                height: '100%',
                handleWindowResize: true,
                stickyHeaderToolbar: true,
                buttonText: {
                    today: 'วันนี้',
                    multiMonthYear: 'รายปี',
                    month: 'รายเดือน',
                    day: 'รายวัน',
                    list: 'กำหนดการ'
                },
                headerToolbar: { 
                    left: isMobile ? 'prev,next' : 'prev,next today', 
                    center: 'title', 
                    right: 'multiMonthYear,dayGridMonth,dayGridDay,listMonth'
                },
                views: {
                    multiMonthYear: { type: 'multiMonth', duration: { years: 1 } }
                },
                themeSystem: 'standard',
                
                datesSet: function(info) {
                    if(window.refreshCalendarEvents) {
                        window.refreshCalendarEvents(info.view.activeStart, info.view.activeEnd);
                    }
                },
                eventClick: function(info) {
                    const eventId = info.event.id;
                    const props = info.event.extendedProps;
                    const eventDate = moment(info.event.start).format('DD/MM/YYYY');

                    // ==========================================
                    // 1. กรณีคลิกที่ "แผนงาน PM" (ID ขึ้นต้นด้วย pm_)
                    // ==========================================
                    if (eventId && eventId.startsWith('pm_')) {
                        const realId = eventId.replace('pm_', '');
                        let badgeHtml = '';
                        let confirmBtnText = '';
                        let confirmBtnClass = '';

                        if (props.status == 1 || props.status == '1') {
                            badgeHtml = `
                                <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border border-emerald-200">
                                    <i class="fas fa-check-circle mr-1"></i> ดำเนินการแล้ว
                                </span>
                            `;
                            confirmBtnText = '<i class="fas fa-search mr-2"></i> ดูประวัติ';
                            confirmBtnClass = 'bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-lg font-semibold text-sm mx-2 shadow-lg shadow-emerald-100 transition-all';
                        } else {
                            badgeHtml = `
                                <span class="bg-sky-100 text-sky-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border border-sky-200">
                                    <i class="fas fa-clock mr-1"></i> แผนงานรอดำเนินการ
                                </span>
                            `;
                            confirmBtnText = '<i class="fas fa-external-link-alt mr-2"></i> เปิดใบงาน';
                            confirmBtnClass = 'bg-sky-600 hover:bg-sky-700 text-white px-6 py-2.5 rounded-lg font-semibold text-sm mx-2 shadow-lg shadow-sky-100 transition-all';
                        }

                        Swal.fire({
                            title: `<span class="text-xl font-bold text-slate-800">รายละเอียด</span>`,
                            html: `
                                <div class="space-y-3">
                                    <div class="flex justify-center mb-5">
                                        ${badgeHtml}
                                    </div>

                                    <div class="grid grid-cols-1 gap-3 text-left">
                                        <div class="flex items-center p-3 bg-slate-50 rounded-xl border border-slate-100 transition-all hover:bg-white hover:shadow-md">
                                            <div class="w-10 h-10 bg-white rounded-lg shadow-sm flex items-center justify-center mr-3 text-indigo-500">
                                                <i class="fas fa-cogs text-lg"></i>
                                            </div>
                                            <div>
                                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">เครื่องจักร/อุปกรณ์</div>
                                                <div class="text-sm font-semibold text-slate-700">${props.machine || '-'}</div>
                                            </div>
                                        </div>

                                        <div class="flex items-center p-3 bg-slate-50 rounded-xl border border-slate-100 transition-all hover:bg-white hover:shadow-md">
                                            <div class="w-10 h-10 bg-white rounded-lg shadow-sm flex items-center justify-center mr-3 text-purple-500">
                                                <i class="fas fa-layer-group text-lg"></i>
                                            </div>
                                            <div>
                                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">ประเภท</div>
                                                <div class="text-sm font-semibold text-slate-700">${props.type || '-'}</div>
                                            </div>
                                        </div>

                                        <div class="flex items-center p-3 bg-slate-50 rounded-xl border border-slate-100 transition-all hover:bg-white hover:shadow-md">
                                            <div class="w-10 h-10 bg-white rounded-lg shadow-sm flex items-center justify-center mr-3 text-sky-500">
                                                <i class="fas fa-map-marker-alt text-lg"></i>
                                            </div>
                                            <div>
                                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">สถานที่ / ตำแหน่ง</div>
                                                <div class="text-sm font-semibold text-slate-700">${props.location || '-'}</div>
                                            </div>
                                        </div>

                                        <div class="flex items-center p-3 bg-slate-50 rounded-xl border border-slate-100 transition-all hover:bg-white hover:shadow-md">
                                            <div class="w-10 h-10 bg-white rounded-lg shadow-sm flex items-center justify-center mr-3 text-emerald-500">
                                                <i class="fas fa-clipboard-check text-lg"></i>
                                            </div>
                                            <div>
                                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">รายการเช็คชีต</div>
                                                <div class="text-sm font-semibold text-slate-700">${props.checksheet || '-'}</div>
                                            </div>
                                        </div>

                                        <div class="flex items-center p-3 bg-slate-50 rounded-xl border border-slate-100 transition-all hover:bg-white hover:shadow-md">
                                            <div class="w-10 h-10 bg-white rounded-lg shadow-sm flex items-center justify-center mr-3 text-amber-500">
                                                <i class="fas fa-calendar-day text-lg"></i>
                                            </div>
                                            <div>
                                                <div class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">วันที่กำหนดงาน</div>
                                                <div class="text-sm font-semibold text-slate-700">${eventDate}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `,
                            showCancelButton: false,
                            confirmButtonText: confirmBtnText,
                            reverseButtons: true,
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: confirmBtnClass,
                                cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-600 px-6 py-2.5 rounded-lg font-semibold text-sm mx-2 transition-all',
                                popup: 'rounded-3xl border-none shadow-2xl',
                                title: 'pt-8'
                            },
                            showClass: { popup: 'animate__animated animate__fadeInUp animate__faster' },
                            hideClass: { popup: 'animate__animated animate__fadeOutDown animate__faster' }
                        }).then((result) => {
                            if (result.isConfirmed) {
								let url = '';
							
								if (props.status == 1 || props.status == '1') {
									url = 'pm_worksheet.php?plan_id=' + encodeURIComponent(realId) + '&mode=history';
								} else {
									url = 'pm_worksheet.php?plan_id=' + encodeURIComponent(realId);
								}
							
								openPmFullWindow(url);
							}
                        });
                    }

                    // ==========================================
                    // 2. กรณีคลิกที่ "วันหยุด" (ID ขึ้นต้นด้วย hol_)
                    // ==========================================
                    else if (eventId && eventId.startsWith('hol_')) {
                        Swal.fire({
                            title: `<span class="text-xl font-bold text-slate-800">วันหยุด</span>`,
                            html: `
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-4 text-red-500">
                                        <i class="fas fa-calendar-times text-3xl"></i>
                                    </div>
                                    <div class="text-lg font-bold text-slate-700 mb-1">${info.event.title}</div>
                                    <div class="text-sm text-slate-500">วันที่ ${eventDate}</div>
                                </div>
                            `,
                            confirmButtonText: 'รับทราบ',
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: 'bg-slate-800 hover:bg-slate-900 text-white px-8 py-2 rounded-xl font-semibold text-sm transition-all',
                                popup: 'rounded-3xl border-none shadow-2xl',
                                title: 'pt-8'
                            }
                        });
                    }
                },
            });
            
            window.calendar.render();

            window.refreshCalendarEvents = async function(activeStart, activeEnd) {
                if (!window.calendar) return;

                const loader = document.getElementById('calendar-loading');
                if (loader) {
                    loader.classList.remove('hidden');
                    loader.classList.add('flex');
                }

                if (!activeStart || !activeEnd) {
                    activeStart = window.calendar.view.activeStart;
                    activeEnd = window.calendar.view.activeEnd;
                }

                const startDateStr = moment(activeStart).format('YYYY-MM-DD');
                const endDateStr = moment(activeEnd).format('YYYY-MM-DD');

                const filterMachine = document.getElementById('filter-machine').value;
                const filterCheck = document.getElementById('filter-checksheet').value;
                const filterType = document.getElementById('filter-type').value;
                const filterLoc = document.getElementById('filter-location').value;

                try {
                    const params = new URLSearchParams({
                        action: 'get_all',
                        ag_id: AG_ID,
                        machine: filterMachine,
                        checksheet: filterCheck,
                        type: filterType,
                        location: filterLoc,
                        start: startDateStr,
                        end: endDateStr
                    });

                    // 1. ดึงข้อมูล 2 API พร้อมกัน (Parallel Fetch) ลดเวลารอ
                    const [resPlan, resHoliday] = await Promise.all([
                        fetch(`handle_pm_dashboard.php?${params.toString()}`),
                        fetch(`handle_pm_holiday.php?action=get_calendar_events&ag_id=${AG_ID}`)
                    ]);

                    const planResult = await resPlan.json();
                    const holidayResult = await resHoliday.json();

                    // 2. เตรียม Array ก้อนใหญ่เพื่อเก็บ Event ทั้งหมด
                    let allEvents = []; 

                    if (planResult.success) {
                        planResult.data.forEach(item => {
                            if (item.extendedProps.status == 0) {
                                // ดันเข้า Array แทนการ addEvent ทีละตัว
                                allEvents.push({
                                    id: 'pm_' + item.id,
                                    title: item.title,
                                    start: item.start,
                                    backgroundColor: '#006B9F',
                                    borderColor: '#006B9F',
                                    extendedProps: item.extendedProps
                                });
                            } else if (item.extendedProps.status == 1) {
                                allEvents.push({
                                    id: 'pm_' + item.id,
                                    title: item.title,
                                    start: item.start,
                                    backgroundColor: '#0d9f00',
                                    borderColor: '#0d9f00',
                                    extendedProps: item.extendedProps
                                });
                            }
                        });
                    }

                    if (holidayResult.success && holidayResult.data) {
                        // ส่ง Array allEvents เข้าไปให้ฟังก์ชันวันหยุดช่วยยัดข้อมูลเพิ่ม
                        renderHolidaysToCalendar(holidayResult.data, allEvents);
                    }

                    // 3. จัดการ FullCalendar: ล้าง Source เก่าทิ้ง และนำเข้า Array ก้อนใหม่รวดเดียว (Batch Insert)
                    window.calendar.getEventSources().forEach(src => src.remove()); 
                    window.calendar.addEventSource(allEvents);

                } catch (error) {
                    console.error("Error updating calendar:", error);
                } finally {
                    if (loader) {
                        loader.classList.remove('flex');
                        loader.classList.add('hidden');
                    }
                }
            };

            // ปรับแก้ฟังก์ชันจัดการวันหยุดให้รับพารามิเตอร์ allEvents เข้ามา
            function renderHolidaysToCalendar(holidays, allEvents) {
                const daysMap = { 
                    'อา.': 0, 'จ.': 1, 'อ.': 2, 'พ.': 3, 'พฤ.': 4, 'ศ.': 5, 'ส.': 6,
                    'Sunday': 0, 'Monday': 1, 'Tuesday': 2, 'Wednesday': 3, 'Thursday': 4, 'Friday': 5, 'Saturday': 6
                };

                holidays.forEach(h => {
                    let style = {};

                    if (h.type === 'once') {
                        style = { backgroundColor: '#0ea5e9', borderColor: '#E0E7FF', textColor: '#FFF', titlePrefix: '📅 ' };
                    } else if (h.type === 'yearly') {
                        style = { backgroundColor: '#ff7982', borderColor: '#FECDD3', textColor: '#E11D48', titlePrefix: '📅 ' };
                    } else if (h.type === 'daily') {
                        style = { backgroundColor: '#eaf5ff', borderColor: '#E2E8F0', textColor: '#1b1b1b', titlePrefix: '' };
                    }

                    const commonData = {
                        title: style.titlePrefix + h.name,
                        backgroundColor: style.backgroundColor,
                        borderColor: style.borderColor,
                        textColor: style.textColor,
                        extendedProps: { type: h.type },
                        classNames: ['holiday-event-style'] 
                    };

                    // ดันเข้า Array แทนการใช้ window.calendar.addEvent
                    if (h.type === 'once') {
                        allEvents.push({ id: 'hol_' + h.id, start: h.date, allDay: true, ...commonData });
                    } 
                    else if (h.type === 'yearly') {
                        const dateParts = h.date.split('-');
                        const currentYear = new Date().getFullYear();
                        for (let i = -1; i <= 1; i++) {
                            allEvents.push({ id: `hol_${h.id}_yr_${currentYear + i}`, start: `${currentYear + i}-${dateParts[1]}-${dateParts[2]}`, allDay: true, ...commonData });
                        }
                    } 
                    else if (h.type === 'daily') {
                        const dayNum = daysMap[h.date];
                        if (dayNum !== undefined) {
                            allEvents.push({ id: 'hol_recurring_' + h.id, daysOfWeek: [dayNum], display: 'background', ...commonData });
                        }
                    }
                });
            }

            setTimeout(() => {
                if(window.refreshCalendarEvents) window.refreshCalendarEvents();
            }, 300);
        }
    });

    // --- สคริปต์คำนวณตำแหน่ง Popover fullCalendar อัตโนมัติ ---
    const observePopover = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                
                // ตรวจสอบว่า Node ที่เพิ่งถูกสร้างคือกล่อง Popover ของ FullCalendar ใช่หรือไม่
                if (node.nodeType === 1 && node.classList.contains('fc-popover')) {
                    let popover = node;
                    
                    // หากรอบปฏิทินของคุณ (อ้างอิงจาก ID ที่ครอบปฏิทินอยู่)
                    let calendarEl = document.getElementById('pm-calendar'); 
                    if (!calendarEl) return;

                    // อ่านพิกัดและขนาดของปฏิทิน และ Popover
                    let calendarRect = calendarEl.getBoundingClientRect();
                    let popoverRect = popover.getBoundingClientRect();

                    // เงื่อนไข: ถ้าขอบล่างของ Popover ทะลุหรือใกล้ทะลุขอบล่างของปฏิทิน
                    // (บวกเผื่อระยะขอบไว้สัก 10px ป้องกันการชนขอบพอดี)
                    if ((popoverRect.bottom + 10) > calendarRect.bottom) {
                        
                        // บังคับพลิกกล่องขึ้นด้านบน
                        popover.style.transform = 'translateY(-70%)';
                        
                        // ขยับชดเชยความสูงของช่องวันที่เล็กน้อย (ปรับลด/เพิ่มตัวเลข -30px นี้ได้ตามความสวยงาม)
                        popover.style.marginTop = '-30px'; 
                        popover.style.boxShadow = '0 -5px 20px rgba(0,0,0,0.15)';
                    }
                }
            });
        });
    });

    // เริ่มดักจับการสร้าง Popover ในหน้าเว็บ
    observePopover.observe(document.body, { childList: true, subtree: true });
	
	function openPmFullWindow(url) {
		const features = [
			'width=' + screen.availWidth,
			'height=' + screen.availHeight,
			'left=0',
			'top=0',
			'resizable=yes',
			'scrollbars=yes',
			'toolbar=no',
			'menubar=no',
			'location=no',
			'status=no'
		].join(',');
	
		const newWindow = window.open(url, '_blank', features);
	
		if (newWindow) {
			newWindow.moveTo(0, 0);
			newWindow.resizeTo(screen.availWidth, screen.availHeight);
			newWindow.focus();
		} else {
			Swal.fire({
				icon: 'warning',
				title: 'ไม่สามารถเปิดหน้าต่างใหม่ได้',
				text: 'กรุณาอนุญาต Popup ของเว็บไซต์นี้ก่อนใช้งาน',
				confirmButtonColor: '#006b9f'
			});
		}
	}
</script>