<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SLA Timeline Dashboard (ภาษาไทย)</title>
    
    <!-- ตั้งค่า Tailwind & Custom Colors -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            400: 'var(--color-secondary)',
                            500: 'var(--color-primary)',
                            600: 'var(--color-primary-dark)',
                        }
                    },
                    fontFamily: {
                        sans: ['Prompt', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- FullCalendar Scheduler (Timeline View) -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar-scheduler@6.1.11/index.global.min.js"></script>
    <!-- FullCalendar Locales -->
    <script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.11/locales-all.global.min.js"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap');
        
        :root {
            --color-bg: #f8fafc;
            --color-primary: #006B9F;
            --color-primary-dark: #004a6f;
            --color-secondary: #04ADFF;
            --glass-bg: rgba(255, 255, 255, 0.9);
            --glass-border: rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            
            --fc-border-color: #e2e8f0;
            --fc-today-bg-color: #f0f9ff;
            --fc-page-bg-color: #ffffff;
            --fc-neutral-bg-color: #f1f5f9;
        }

        body {
            font-family: 'Prompt', sans-serif;
            background-color: var(--color-bg);
            color: #334155;
        }

        /* Glassmorphism Classes */
        .glass-panel {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        /* FullCalendar Customizations */
        .fc {
            border: none !important;
            font-size: 0.875rem;
        }
        
        .fc-theme-standard td, .fc-theme-standard th {
            border-color: var(--fc-border-color) !important;
        }

        .fc-resource-area-header {
            background: #f8fafc !important;
            font-weight: 600 !important;
            color: #475569 !important;
        }

        .fc-timeline-slot-label {
            color: #64748b !important;
            font-weight: 500 !important;
        }

        .fc .fc-datagrid-cell-cushion {
            padding: 8px 12px !important;
            width: 100%;
            display: flex;
            align-items: flex-start;
        }

        /* ปุ่ม Expander [+] [-] ย่อขนาดลง */
        .fc-datagrid-expander {
            color: #64748b;
            margin-right: 8px;
            margin-top: 10px;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            background: #f1f5f9;
            transition: all 0.2s ease;
        }
        
        .fc-datagrid-expander:hover {
            background: #e2e8f0;
            color: var(--color-primary);
        }

        .fc-datagrid-expander.fc-datagrid-expander-placeholder {
            background: transparent !important;
        }

        /* Event Styling */
        .sla-event {
            border: none !important;
            border-radius: 6px;
            box-shadow: 0 2px 4px -1px rgb(0 0 0 / 0.1);
            transition: all 0.2s ease;
            cursor: pointer;
            padding: 4px 8px;
            display: flex;
            align-items: center;
        }
        
        .sla-event:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
            box-shadow: 0 4px 8px -1px rgb(0 0 0 / 0.15);
            z-index: 10;
        }

        .focused-event {
            box-shadow: 0 0 0 4px rgba(4, 173, 255, 0.4) !important;
            transform: scale(1.02);
            z-index: 50 !important;
            filter: brightness(1.2);
        }

        .fc-event-title {
            font-family: 'Prompt', sans-serif;
            font-weight: 500;
        }

        .sla-response { background: linear-gradient(135deg, var(--color-secondary) 0%, var(--color-primary) 100%) !important; }
        .sla-resolution { background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important; }

        ::-webkit-scrollbar { width: 6px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .fc-header-toolbar { display: none !important; }
    </style>
</head>

<!-- ตั้งค่าให้หน้าเว็บสูง 100% เสมอ และห้ามมี Scroll bar (overflow-hidden) -->
<body class="antialiased h-[100dvh] overflow-hidden flex flex-col">

    <div class="p-2 md:p-4 lg:p-6 transition-all flex-1 flex flex-col min-h-0 w-full" id="dashboard-container">
        <div class="max-w-[1600px] mx-auto w-full h-full flex flex-col min-h-0">
            
            <!-- Header & Filters Toolbar (Compact Space-Between Layout) shrink-0 ป้องกันไม่ให้โดนบีบ -->
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 mb-4 relative z-20 shrink-0">
                
                <!-- Left: Title Area -->
                <div class="flex items-center gap-3 shrink-0">
                    <div class="p-2 bg-brand-500 rounded-lg text-white shadow-sm">
                        <i data-lucide="bar-chart-2" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h1 class="text-lg md:text-xl font-bold tracking-tight text-slate-800 leading-tight">แดชบอร์ดติดตามคิวงาน (SLA)</h1>
                        <p class="text-[12px] md:text-[13px] text-slate-500">ดูตารางการทำงานและระยะเวลาดำเนินการ</p>
                    </div>
                </div>

                <!-- Right: Compact Filter Tools (Icon Based) -->
                <div class="glass-panel p-1.5 rounded-xl flex flex-wrap sm:flex-nowrap items-center gap-1.5 w-full xl:w-auto border border-slate-200/80 shadow-sm shrink-0">
                    
                    <!-- Search -->
                    <div class="relative flex-1 sm:flex-none sm:w-48">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                            <i data-lucide="search" class="w-4 h-4 text-slate-400"></i>
                        </div>
                        <input type="text" id="searchInput" placeholder="ค้นหาชื่อ, รหัส..." class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-500 text-[13px] outline-none transition-all">
                    </div>
                    
                    <!-- Select Job Type -->
                    <select id="jobTypeFilter" class="w-[calc(50%-0.2rem)] sm:w-[110px] pl-2 pr-6 py-1.5 bg-white border border-slate-200 rounded-lg text-[13px] focus:ring-2 focus:ring-brand-500 outline-none text-slate-700 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%2364748b%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E')] bg-no-repeat bg-[position:right_0.4rem_center] bg-[size:1em]">
                        <option value="">ประเภทงาน</option>
                        <option value="Incident">Incident</option>
                        <option value="Request">Request</option>
                        <option value="PM">PM</option>
                    </select>

                    <!-- Select Service Type -->
                    <select id="serviceTypeFilter" class="w-[calc(50%-0.2rem)] sm:w-[110px] pl-2 pr-6 py-1.5 bg-white border border-slate-200 rounded-lg text-[13px] focus:ring-2 focus:ring-brand-500 outline-none text-slate-700 appearance-none bg-[url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%2214%22%20height%3D%2214%22%20viewBox%3D%220%200%2024%2024%22%20fill%3D%22none%22%20stroke%3D%22%2364748b%22%20stroke-width%3D%222%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%3E%3Cpolyline%20points%3D%226%209%2012%2015%2018%209%22%3E%3C%2Fpolyline%3E%3C%2Fsvg%3E')] bg-no-repeat bg-[position:right_0.4rem_center] bg-[size:1em]">
                        <option value="">ชนิดบริการ</option>
                        <option value="Network">Network</option>
                        <option value="Hardware">Hardware</option>
                        <option value="Software">Software</option>
                        <option value="Account">Account</option>
                    </select>

                    <div class="h-6 w-px bg-slate-200 mx-0.5 hidden sm:block"></div>

                    <!-- Action Buttons (Icons Only) -->
                    <div class="flex gap-1.5 ml-auto sm:ml-0 mt-1 sm:mt-0 w-full sm:w-auto justify-end">
                        <button onclick="applyFilter()" class="p-1.5 px-3 sm:px-1.5 bg-brand-500 hover:bg-brand-600 text-white rounded-lg flex items-center justify-center shadow-sm transition-colors" title="คัดกรอง">
                            <i data-lucide="filter" class="w-4 h-4"></i>
                        </button>
                        <button onclick="clearFilter()" class="p-1.5 px-3 sm:px-1.5 bg-white hover:bg-slate-50 text-slate-600 border border-slate-200 rounded-lg flex items-center justify-center shadow-sm transition-colors" title="ล้างค่า">
                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Calendar Container : flex-1 ช่วยให้ขยายเต็มพื้นที่ที่เหลือ min-h-0 ป้องกันการทะลุจอ -->
            <div id="calendar-container" class="bg-white rounded-2xl shadow-xl shadow-slate-200/40 border border-slate-200 overflow-hidden flex flex-col flex-1 min-h-0">
                
                <!-- Calendar Toolbar -->
                <div class="px-3 md:px-5 py-2.5 md:py-3 border-b border-slate-100 bg-slate-50/50 flex flex-col gap-3 shrink-0">
                    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3">
                        
                        <!-- ฝั่งซ้าย: Title & Legend -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3 md:gap-5">
                            <h2 id="calendar-title" class="text-base md:text-lg font-bold text-slate-800 whitespace-nowrap min-w-[140px]">กำลังโหลด...</h2>
                            
                            <div class="flex flex-wrap gap-1.5">
                                <div class="flex items-center gap-1.5 bg-white px-2 py-1 rounded-md border border-slate-200 shadow-sm">
                                    <span class="w-2.5 h-2.5 rounded-full bg-gradient-to-br from-[#04ADFF] to-[#006B9F] shadow-sm"></span>
                                    <span class="text-[11px] md:text-xs font-medium text-slate-600">SLA 1 : ระยะเวลาตอบรับ (แจ้งเรื่อง - รับงาน)</span>
                                </div>
                                <div class="flex items-center gap-1.5 bg-white px-2 py-1 rounded-md border border-slate-200 shadow-sm">
                                    <span class="w-2.5 h-2.5 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-sm"></span>
                                    <span class="text-[11px] md:text-xs font-medium text-slate-600">SLA 2 : ระยะเวลาแก้ไข (รับงาน - ปิดงาน)</span>
                                </div>
                            </div>
                        </div>

                        <!-- ฝั่งขวา: Navigation Controls & Fullscreen -->
                        <div class="flex flex-col sm:flex-row items-center gap-2 w-full xl:w-auto shrink-0">
                            <!-- ปุ่ม วัน/เดือน/ปี และ เลื่อนวันที่ -->
                            <div class="flex items-center bg-white p-1 rounded-xl border border-slate-200 shadow-sm gap-1 w-full sm:w-auto overflow-x-auto hide-scrollbar">
                                <button onclick="changeView('viewDay')" class="view-btn px-3 md:px-4 py-1.5 text-[13px] font-semibold rounded-lg transition-all bg-brand-500 text-white shadow hover:bg-brand-600 flex-1 sm:flex-none text-center whitespace-nowrap" id="btn-day">วัน</button>
                                <button onclick="changeView('viewMonth')" class="view-btn px-3 md:px-4 py-1.5 text-[13px] font-semibold rounded-lg transition-all text-slate-600 hover:bg-slate-50 flex-1 sm:flex-none text-center whitespace-nowrap" id="btn-month">เดือน</button>
                                <button onclick="changeView('viewYear')" class="view-btn px-3 md:px-4 py-1.5 text-[13px] font-semibold rounded-lg transition-all text-slate-600 hover:bg-slate-50 flex-1 sm:flex-none text-center whitespace-nowrap" id="btn-year">ปี</button>
                                
                                <div class="h-4 w-px bg-slate-200 mx-1 hidden sm:block"></div>
                                
                                <div class="flex items-center gap-1 shrink-0">
                                    <button onclick="goPrev()" class="p-1 hover:bg-slate-50 rounded-lg text-slate-600 transition-colors" title="ก่อนหน้า"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                                    <button onclick="goToday()" class="px-2 md:px-3 py-1 text-[13px] font-medium hover:bg-slate-50 rounded-lg text-slate-600 transition-colors whitespace-nowrap">วันนี้</button>
                                    <button onclick="goNext()" class="p-1 hover:bg-slate-50 rounded-lg text-slate-600 transition-colors" title="ถัดไป"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                                </div>
                            </div>
                            
                            <!-- ปุ่ม Fullscreen -->
                            <button onclick="toggleFullScreen()" class="p-2 bg-white hover:bg-slate-50 border border-slate-200 rounded-xl text-slate-600 transition-colors shadow-sm focus:outline-none shrink-0 hidden sm:block" title="ย่อ/ขยายเต็มจอเฉพาะตาราง" id="btn-fullscreen">
                                <i data-lucide="maximize" class="w-4 h-4" id="icon-fullscreen"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- กล่องปฏิทิน ใช้ flex-1 เพื่อให้ขยาย 100% -->
                <div id="calendar" class="p-0 flex-1 min-h-0 bg-white"></div>
            </div>
        </div>
    </div>

    <!-- Event Detail Popup (Modal) -->
    <div id="eventModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity opacity-0" id="modalBackdrop" onclick="closeModal()"></div>
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div id="modalPanel" class="relative bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-lg w-full scale-95 opacity-0">
                <div class="bg-brand-500 px-5 py-4 flex items-center justify-between">
                    <h3 class="text-lg leading-6 font-bold text-white flex items-center gap-2">
                        <i data-lucide="info" class="w-5 h-5"></i> รายละเอียดคิวงาน
                    </h3>
                    <button onclick="closeModal()" class="text-white/80 hover:text-white bg-white/10 hover:bg-white/20 p-1.5 rounded-lg transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
                
                <div class="px-6 py-5">
                    <div class="mb-5">
                        <p class="text-sm font-medium text-slate-500 mb-1">ชื่อพนักงานที่รับผิดชอบ</p>
                        <div class="flex items-center gap-2 text-slate-800 font-semibold" id="modalEmpName">
                            <i data-lucide="user" class="w-4 h-4 text-brand-500"></i> <span>-</span>
                        </div>
                    </div>
                    
                    <div class="mb-5">
                        <p class="text-sm font-medium text-slate-500 mb-1">รหัสงาน / รายละเอียด</p>
                        <p class="text-lg font-bold text-slate-800" id="modalJobTitle">-</p>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-100 mb-5 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-slate-500 font-medium flex items-center gap-1.5 mb-1">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i> เวลาเริ่มต้น
                            </p>
                            <p class="text-sm font-semibold text-slate-700" id="modalStartTime">-</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 font-medium flex items-center gap-1.5 mb-1">
                                <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> เวลาสิ้นสุด
                            </p>
                            <p class="text-sm font-semibold text-slate-700" id="modalEndTime">-</p>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-slate-500 mb-2">สถานะงาน (SLA Status)</p>
                        <div id="modalStatusWrapper">
                            <span id="modalStatusBadge" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold">
                                -
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse rounded-b-2xl border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-5 py-2 bg-brand-500 text-base font-medium text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:w-auto sm:text-sm transition-colors">
                        ปิดหน้าต่าง
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    <script>
        let calendar;

        const todayObj = new Date();
        const t_date = todayObj.toISOString().split('T')[0];
        
        // ข้อมูลจำลอง (Mock Data)
        const allResources = [
            { id: 'emp_1', title: 'นายสมชาย รักงานดี', role: 'Senior Engineer', expanded: true },
            { id: 'job_1_1', parentId: 'emp_1', title: 'INC-2604-001', jobType: 'Incident', serviceType: 'Network', desc: 'อินเทอร์เน็ตใช้งานไม่ได้' },
            { id: 'job_1_2', parentId: 'emp_1', title: 'REQ-2604-002', jobType: 'Request', serviceType: 'Software', desc: 'ขอติดตั้งโปรแกรมเฉพาะทาง' },
            
            { id: 'emp_2', title: 'น.ส.วิภาวี สดใส', role: 'Technical Support', expanded: true },
            { id: 'job_2_1', parentId: 'emp_2', title: 'PM-2604-003', jobType: 'PM', serviceType: 'Hardware', desc: 'ตรวจเช็ค Server ประจำเดือน' },
            { id: 'job_2_2', parentId: 'emp_2', title: 'INC-2604-004', jobType: 'Incident', serviceType: 'Hardware', desc: 'ปริ้นเตอร์กระดาษติด' },

            { id: 'emp_3', title: 'นายธนพล มุ่งมั่น', role: 'Field Technician', expanded: false },
            { id: 'job_3_1', parentId: 'emp_3', title: 'REQ-2604-005', jobType: 'Request', serviceType: 'Account', desc: 'ขอรหัสผ่านใหม่ (Reset Password)' }
        ];

        const allEvents = [
            { id: 'e1', resourceId: 'job_1_1', start: `${t_date}T08:30:00`, end: `${t_date}T09:00:00`, title: 'SLA1 (รับงาน)', className: 'sla-event sla-response' },
            { id: 'e2', resourceId: 'job_1_1', start: `${t_date}T09:00:00`, end: `${t_date}T11:30:00`, title: 'SLA2 (ปิดงาน)', className: 'sla-event sla-resolution' },
            { id: 'e3', resourceId: 'job_1_2', start: `${t_date}T13:00:00`, end: `${t_date}T13:15:00`, title: 'SLA1 (รับงาน)', className: 'sla-event sla-response' },

            { id: 'e5', resourceId: 'job_2_1', start: `${t_date}T09:00:00`, end: `${t_date}T16:30:00`, title: 'PM ตามแผน', className: 'sla-event sla-resolution' },
            { id: 'e6', resourceId: 'job_2_2', start: `${t_date}T10:30:00`, end: `${t_date}T10:45:00`, title: 'SLA1 (รับงาน)', className: 'sla-event sla-response' },
            { id: 'e7', resourceId: 'job_2_2', start: `${t_date}T10:45:00`, end: `${t_date}T12:00:00`, title: 'SLA2 (ซ่อมเสร็จ)', className: 'sla-event sla-resolution' },

            { id: 'e8', resourceId: 'job_3_1', start: `${t_date}T14:00:00`, end: `${t_date}T14:10:00`, title: 'SLA1 (รับงาน)', className: 'sla-event sla-response' },
            { id: 'e9', resourceId: 'job_3_1', start: `${t_date}T14:10:00`, end: `${t_date}T14:30:00`, title: 'SLA2 (ดำเนินการ)', className: 'sla-event sla-resolution' }
        ];

        // ปรับขนาดคอลัมน์ฝั่งซ้ายให้กระชับลง (จาก 360px เหลือ 280px บนจอคอม)
        function getResourceAreaWidth() {
            return window.innerWidth < 768 ? '200px' : '280px';
        }

        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            const calendarEl = document.getElementById('calendar');

            calendar = new FullCalendar.Calendar(calendarEl, {
                schedulerLicenseKey: 'CC-Attribution-NonCommercial-NoDerivatives',
                locale: 'th',
                initialView: 'viewDay',
                initialDate: t_date,
                nowIndicator: true,
                height: '100%', 
                slotMinWidth: 80,
                scrollTime: '08:00:00',
                
                resourceAreaWidth: getResourceAreaWidth(),
                windowResize: function(arg) {
                    calendar.setOption('resourceAreaWidth', getResourceAreaWidth());
                },

                resourceAreaColumns: [
                    {
                        headerContent: 'รายชื่อพนักงาน / รายละเอียดงาน',
                        cellContent: function(arg) {
                            let el = document.createElement('div');
                            el.className = 'w-full min-w-0';
                            
                            const isParent = !arg.resource.getParent();

                            if (isParent) {
                                // LAYER 1: พนักงาน
                                el.innerHTML = `
                                    <div class="flex items-center gap-2 py-0.5 pr-1 group cursor-pointer">
                                        <div class="relative w-8 h-8 md:w-9 md:h-9 rounded-full bg-brand-50 border border-brand-100 text-brand-600 flex items-center justify-center shrink-0 shadow-sm group-hover:bg-brand-100 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                        </div>
                                        <div class="flex flex-col min-w-0 flex-1">
                                            <span class="text-[13px] md:text-[14px] font-bold text-slate-800 leading-tight truncate">${arg.resource.title}</span>
                                            <span class="text-[10px] md:text-[11px] text-slate-500 mt-0.5 bg-slate-100 px-1.5 py-0.5 rounded inline-block w-max">${arg.resource.extendedProps.role || 'พนักงาน'}</span>
                                        </div>
                                    </div>
                                `;
                            } else {
                                // LAYER 2: งาน
                                let typeColor = 'text-brand-600 bg-brand-50 ring-1 ring-inset ring-brand-200';
                                let iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>';
                                
                                if(arg.resource.extendedProps.jobType === 'Incident') {
                                    typeColor = 'text-rose-600 bg-rose-50 ring-1 ring-inset ring-rose-200';
                                    iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
                                } else if(arg.resource.extendedProps.jobType === 'PM') {
                                    typeColor = 'text-emerald-600 bg-emerald-50 ring-1 ring-inset ring-emerald-200';
                                    iconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>';
                                }

                                el.innerHTML = `
                                    <div class="relative pl-3 md:pl-4 py-1 pr-1 md:pr-2 group">
                                        <div class="absolute top-0 bottom-0 left-[6px] md:left-[8px] w-[2px] bg-slate-200 group-hover:bg-brand-300 transition-colors"></div>
                                        <div class="absolute top-1/2 left-[6px] md:left-[8px] w-[8px] md:w-[12px] h-[2px] bg-slate-200 group-hover:bg-brand-300 transition-colors"></div>
                                        <div class="absolute top-1/2 left-[5px] md:left-[7px] w-1 h-1 rounded-full bg-slate-300 group-hover:bg-brand-400 transform -translate-y-1/2 transition-colors"></div>
                                        
                                        <div onclick="focusJob('${arg.resource.id}')" class="ml-1 md:ml-1.5 bg-white border border-slate-200/80 rounded-xl p-2 shadow-sm hover:shadow hover:border-brand-400 transition-all cursor-pointer relative z-10 hover:-translate-y-[1px]">
                                            <div class="flex items-start justify-between gap-1 mb-1">
                                                <div class="flex items-center gap-1.5 text-slate-500 group-hover:text-brand-600 transition-colors min-w-0">
                                                    <div class="p-1 rounded-md bg-slate-50 shrink-0 group-hover:bg-brand-50 hidden md:block">
                                                        ${iconSvg}
                                                    </div>
                                                    <span class="text-[12px] md:text-[13px] font-bold text-slate-800 group-hover:text-brand-600 truncate">${arg.resource.title}</span>
                                                </div>
                                                <span class="text-[9px] px-1.5 py-0.5 rounded-full font-medium shrink-0 ${typeColor} hidden sm:inline-block">${arg.resource.extendedProps.jobType}</span>
                                            </div>
                                            
                                            <div class="text-[11px] text-slate-500 line-clamp-1 mb-1" title="${arg.resource.extendedProps.desc}">
                                                ${arg.resource.extendedProps.desc}
                                            </div>
                                            
                                            <div class="flex items-center mt-auto">
                                                <div class="inline-flex items-center gap-1 text-[9.5px] text-slate-500 bg-slate-50 px-1.5 py-0.5 rounded-md border border-slate-100">
                                                    <i data-lucide="server" class="w-3 h-3"></i>
                                                    ${arg.resource.extendedProps.serviceType}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }
                            return { domNodes: [el] };
                        }
                    }
                ],

                views: {
                    viewDay: { 
                        type: 'resourceTimelineDay',
                        slotDuration: '01:00',
                        slotLabelFormat: { hour: '2-digit', minute: '2-digit', omitZeroMinute: false, hour12: false } 
                    },
                    viewMonth: { 
                        type: 'resourceTimelineMonth', 
                        slotLabelFormat: [{ weekday: 'short', day: 'numeric' }] 
                    },
                    viewYear: { 
                        type: 'resourceTimelineYear',
                        slotDuration: { months: 1 }, 
                        slotLabelFormat: [{ month: 'short' }] 
                    }
                },

                resources: allResources,
                events: allEvents,

                datesSet: function(info) {
                    document.getElementById('calendar-title').innerText = info.view.title;
                },
                
                eventDidMount: function(info) {
                    const resources = info.event.getResources();
                    if (resources && resources.length > 0) {
                        info.el.setAttribute('data-resource-id', resources[0].id);
                    }
                },

                eventClick: function(info) {
                    openModal(info.event);
                }
            });

            calendar.render();
            setTimeout(() => { lucide.createIcons(); }, 500);
        });

        // --- ฟังก์ชันย่อ/ขยายเต็มจอ (Fullscreen เฉพาะตาราง) ---
        function toggleFullScreen() {
            const container = document.getElementById('calendar-container'); 
            const icon = document.getElementById('icon-fullscreen');
            
            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                if (container.requestFullscreen) {
                    container.requestFullscreen();
                } else if (container.webkitRequestFullscreen) {
                    container.webkitRequestFullscreen();
                } else if (container.msRequestFullscreen) {
                    container.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        }

        // ดัก Event อัปเดตไอคอนเมื่อ Fullscreen เปลี่ยนแปลง
        function handleFullscreenChange() {
            const icon = document.getElementById('icon-fullscreen');
            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                icon.setAttribute('data-lucide', 'maximize');
            } else {
                icon.setAttribute('data-lucide', 'minimize');
            }
            setTimeout(() => { lucide.createIcons(); }, 50);
        }

        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('webkitfullscreenchange', handleFullscreenChange);

        // --- ระบบจัดการ Modal Popup ---
        function openModal(event) {
            const resources = event.getResources();
            let employeeName = '-';
            let jobDesc = '-';
            
            if (resources.length > 0) {
                const jobResource = resources[0];
                jobDesc = jobResource.extendedProps.desc || '-';
                const parent = jobResource.getParent();
                if (parent) {
                    employeeName = parent.title;
                }
            }

            document.getElementById('modalEmpName').innerHTML = `<i data-lucide="user" class="w-4 h-4 text-brand-500"></i> <span>${employeeName}</span>`;
            document.getElementById('modalJobTitle').innerText = `${event.title} : ${jobDesc}`;
            
            const startStr = event.start.toLocaleTimeString('th-TH', {hour: '2-digit', minute:'2-digit'});
            document.getElementById('modalStartTime').innerText = `${startStr} น.`;
            
            const endStr = event.end ? event.end.toLocaleTimeString('th-TH', {hour: '2-digit', minute:'2-digit'}) + ' น.' : 'กำลังดำเนินการ...';
            document.getElementById('modalEndTime').innerText = endStr;

            const badge = document.getElementById('modalStatusBadge');
            if (event.classNames.includes('sla-response')) {
                badge.className = "inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-50 text-brand-600 border border-brand-200";
                badge.innerHTML = '<i data-lucide="alert-circle" class="w-4 h-4 mr-1.5"></i> SLA 1 : ระยะเวลาตอบรับ (แจ้งเรื่อง - รับงาน)';
            } else if (event.classNames.includes('sla-resolution')) {
                badge.className = "inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-emerald-50 text-emerald-600 border border-emerald-200";
                badge.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4 mr-1.5"></i> SLA 2 : ระยะเวลาแก้ไข (รับงาน - ปิดงาน)';
            }

            const modal = document.getElementById('eventModal');
            const backdrop = document.getElementById('modalBackdrop');
            const panel = document.getElementById('modalPanel');
            
            modal.classList.remove('hidden');
            
            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                backdrop.classList.add('opacity-100');
                panel.classList.remove('opacity-0', 'scale-95');
                panel.classList.add('opacity-100', 'scale-100');
            });

            lucide.createIcons();
        }

        function closeModal() {
            const backdrop = document.getElementById('modalBackdrop');
            const panel = document.getElementById('modalPanel');
            
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
            
            panel.classList.remove('opacity-100', 'scale-100');
            panel.classList.add('opacity-0', 'scale-95');
            
            setTimeout(() => {
                document.getElementById('eventModal').classList.add('hidden');
            }, 300);
        }

        function focusJob(resourceId) {
            const events = calendar.getEvents().filter(e => e.getResources().some(r => r.id === resourceId));
            if (events.length === 0) return;
            
            events.sort((a, b) => a.start - b.start);
            const targetEvent = events[0];

            calendar.gotoDate(targetEvent.start);

            const attemptFocus = (retries = 0) => {
                const eventEl = document.querySelector(`.fc-timeline-event[data-resource-id="${resourceId}"]`);
                if (eventEl) {
                    eventEl.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'center' });
                    eventEl.classList.add('focused-event');
                    setTimeout(() => eventEl.classList.remove('focused-event'), 2500);
                } else if (retries < 15) {
                    setTimeout(() => attemptFocus(retries + 1), 100);
                }
            };
            
            attemptFocus();
        }

        function applyFilter() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const jobType = document.getElementById('jobTypeFilter').value;
            const serviceType = document.getElementById('serviceTypeFilter').value;

            let filteredResources = [];
            let parentIdsToKeep = new Set();

            allResources.forEach(res => {
                if (res.parentId) { 
                    let isMatch = true;
                    let parentTitle = allResources.find(p => p.id === res.parentId).title.toLowerCase();
                    
                    if (searchText && !(res.title.toLowerCase().includes(searchText) || parentTitle.includes(searchText) || res.desc.toLowerCase().includes(searchText))) {
                        isMatch = false;
                    }
                    if (jobType && res.jobType !== jobType) isMatch = false;
                    if (serviceType && res.serviceType !== serviceType) isMatch = false;

                    if (isMatch) {
                        filteredResources.push(res);
                        parentIdsToKeep.add(res.parentId); 
                    }
                }
            });

            allResources.forEach(res => {
                if (!res.parentId && parentIdsToKeep.has(res.id)) {
                    filteredResources.unshift({ ...res, expanded: true }); 
                }
            });

            if (!searchText && !jobType && !serviceType) {
                calendar.setOption('resources', allResources);
            } else {
                calendar.setOption('resources', filteredResources);
            }
        }

        function clearFilter() {
            document.getElementById('searchInput').value = '';
            document.getElementById('jobTypeFilter').value = '';
            document.getElementById('serviceTypeFilter').value = '';
            applyFilter();
        }

        function changeView(viewName) {
            calendar.changeView(viewName);
            document.querySelectorAll('.view-btn').forEach(btn => {
                btn.classList.remove('bg-brand-500', 'text-white', 'shadow-md', 'hover:bg-brand-600');
                btn.classList.add('text-slate-600', 'hover:bg-slate-100');
            });
            const btnId = {
                'viewDay': 'btn-day', 'viewMonth': 'btn-month', 'viewYear': 'btn-year'
            }[viewName];
            const btn = document.getElementById(btnId);
            if (btn) {
                btn.classList.add('bg-brand-500', 'text-white', 'shadow-md', 'hover:bg-brand-600');
                btn.classList.remove('text-slate-600', 'hover:bg-slate-100');
            }
        }

        function goPrev() { calendar.prev(); }
        function goNext() { calendar.next(); }
        function goToday() { calendar.today(); }

        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') applyFilter();
        });
    </script>
</body>
</html>