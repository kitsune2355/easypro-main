<?php 
@session_start();
include "config_ctrl/checksession.php"; 
?>
<!DOCTYPE html>
<html lang="th">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ประวัติงานช่าง | Technician Work History</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="css/style.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: { prompt: ["Prompt", "sans-serif"] },
            boxShadow: {
              soft: "0 16px 45px rgba(15, 23, 42, 0.10)",
              card: "0 8px 28px rgba(15, 23, 42, 0.08)",
            },
          },
        },
      };
    </script>
    <style>
      :root {
        --color-bg-light: #f0f4f8;
        --color-main-surface: rgba(255, 255, 255, 0.95);
        --color-border-glass: rgba(0, 0, 0, 0.1);
        --color-primary: #006b9f;
        --color-secondary: #04adff;
        --dark-blue: #004a6f;
        --color-text-dark: #333333;
        --color-placeholder: #777777;
        --shadow-glass: 0 8px 32px 0 rgba(0, 0, 0, 0.15);
      }

      * {
        font-family: "Prompt", "Kanit", "Noto Sans Thai", sans-serif;
      }
      body {
        background: var(--color-bg-light);
        color: var(--color-text-dark);
      }
      .glass-card {
        background: var(--color-main-surface) !important;
        border-color: var(--color-border-glass) !important;
        box-shadow: var(--shadow-glass) !important;
        backdrop-filter: blur(10px);
      }
      .hero-gradient {
        background: linear-gradient(
          135deg,
          var(--color-primary),
          var(--color-secondary),
          var(--dark-blue)
        );
      }
      .text-theme-primary {
        color: var(--color-primary) !important;
      }
      .border-theme {
        border-color: var(--color-border-glass) !important;
      }
      .bg-slate-950,
      .bg-indigo-600,
      .bg-indigo-500 {
        background-color: var(--color-primary) !important;
      }
      .text-indigo-700,
      .text-indigo-600,
      .text-indigo-500 {
        color: var(--color-primary) !important;
      }
      .border-indigo-200 {
        border-color: rgba(4, 173, 255, 0.35) !important;
      }

      .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
      }
      .calendar-day {
        min-height: 90px;
      }
      @media (max-width: 768px) {
        .calendar-day {
          min-height: 70px;
        }
      }

      .event-pill {
        transition: all 0.18s ease;
      }
      .event-pill:hover {
        transform: translateY(-1px);
        filter: brightness(0.98);
      }
      .scrollbar-thin::-webkit-scrollbar {
        width: 6px;
        height: 6px;
      }
      .scrollbar-thin::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
      }
      .status-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        display: inline-block;
      }
      .swal2-popup {
        font-family: "Prompt", sans-serif !important;
        border-radius: 16px !important;
      }

      .tab-btn {
        transition: all 0.2s ease;
        border-bottom: 2px solid transparent;
        color: #64748b;
      }
      .tab-btn.active {
        background: transparent;
        color: var(--color-primary);
        border-color: var(--color-primary);
        font-weight: 600;
      }
      .tab-btn:not(.active):hover {
        background: #eef6fb;
        color: var(--color-primary);
      }
      .hide-scrollbar::-webkit-scrollbar {
        display: none;
      }
      .hide-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
      }

      /* Custom Scrollbar */
      ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
      }
      ::-webkit-scrollbar-track {
        background: transparent;
      }
      ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
      }
      ::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
      }

      .kanban-col {
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 240px);
        min-height: 500px;
      }
      .kanban-cards-container {
        flex: 1;
        overflow-y: auto;
        padding-right: 4px;
      }

      .fullscreen-panel:fullscreen {
        width: 100vw;
        height: 100vh;
        overflow: auto;
        background: #ffffff;
        border-radius: 0;
      }

      #techJobsCard.fullscreen-panel:fullscreen {
        display: flex;
        flex-direction: column;
      }
      #techJobsCard.fullscreen-panel:fullscreen #techJobsChartWrap {
        flex: 1;
        height: auto;
        min-height: 0;
      }
      
      /* Timeline CSS (Notion Style) */
      .timeline-container {
        display: flex;
        flex-direction: column;
        overflow: auto;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        background: #fff;
        max-height: calc(100vh - 240px);
        min-height: 400px;
      }
      .fullscreen-panel:fullscreen .timeline-container {
        max-height: calc(100vh - 90px);
        min-height: 100%;
      }
      .timeline-container::-webkit-scrollbar {
        width: 8px;
        height: 8px;
      }
      .timeline-container::-webkit-scrollbar-track {
        background: transparent;
        border-radius: 8px;
      }
      .timeline-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 8px;
      }
      .timeline-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
      }
      
      .timeline-header {
        display: flex;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        position: sticky;
        top: 0;
        z-index: 20;
        width: max-content; min-width: 100%;
      }
      .timeline-body {
        display: flex;
        flex-direction: column;
        width: max-content; min-width: 100%;
      }
      .timeline-row {
        display: flex;
        border-bottom: 1px solid #f1f5f9;
        min-height: 60px;
        width: max-content; min-width: 100%;
      }
      .timeline-row:last-child {
        border-bottom: none;
      }
      .timeline-sidebar {
        width: 200px;
        min-width: 200px;
        padding: 0.75rem;
        border-right: 1px solid #e2e8f0;
        background: #fff;
        position: sticky;
        left: 0;
        z-index: 10;
        display: flex;
        align-items: center;
        font-size: 0.75rem;
        font-weight: 600;
        color: #334155;
      }
      .timeline-header .timeline-sidebar {
        background: #f8fafc;
        z-index: 30;
      }
      .timeline-content {
        flex: 1;
        position: relative;
        min-width: 800px; 
        background-image: linear-gradient(to right, #f1f5f9 1px, transparent 1px);
        background-size: calc(100% / 24) 100%; 
      }
      .timeline-bar {
        position: absolute;
        height: 28px;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 0.65rem;
        color: #fff;
        display: flex;
        align-items: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: transform 0.15s;
        border: 1px solid #ffffff;
      }
      .timeline-bar:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 5 !important;
      }
      .timeline-header-cell {
        flex: 1;
        text-align: center;
        padding: 0.5rem;
        font-size: 0.7rem;
        font-weight: 500;
        color: #64748b;
        border-right: 1px solid #f1f5f9;
        background: #f8fafc;
      }
      .timeline-header-cell:last-child {
        border-right: none;
      }
      .timeline-header-content {
        flex: 1;
        display: flex;
        min-width: 800px;
        background: #f8fafc;
      }

      /* Zoom controls */
      .timeline-zoom-controls {
        display: flex;
        align-items: center;
        gap: 2px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.65rem;
        padding: 2px;
      }
      .timeline-zoom-btn {
        width: 26px;
        height: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: #475569;
        transition: all 0.15s ease;
        cursor: pointer;
      }
      .timeline-zoom-btn:hover:not(:disabled) {
        background: #fff;
        color: var(--color-primary);
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.12);
      }
      .timeline-zoom-btn:disabled {
        opacity: 0.35;
        cursor: not-allowed;
      }
      .timeline-zoom-label {
        font-size: 10.5px;
        font-weight: 600;
        color: #475569;
        min-width: 38px;
        text-align: center;
        user-select: none;
      }
    </style>
  </head>
  <body class="font-prompt text-slate-800 text-sm">
    <div
      class="flex flex-col antialiased text-slate-700 h-screen w-full relative overflow-hidden"
    >
      <header class="w-full bg-white shadow-sm flex flex-col flex-none z-50">
        <nav class="px-6 py-4 flex items-center justify-between border-b border-slate-200">
          <div class="flex items-center gap-4">
            <div class="p-2 bg-[#006B9F] rounded-lg shadow-md text-white">
              <i data-lucide="hard-hat" class="w-6 h-6"></i>
            </div>
            <div>
              <h2 class="text-lg font-bold text-slate-800 leading-tight" id="header-title">
                Technician Work History
              </h2>
              <p class="text-xs text-slate-500 mt-1" id="header-subtitle">
                ติดตามและตรวจสอบประวัติงานช่าง
              </p>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <button id="btnToday" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">
              วันนี้
            </button>
          </div>
        </nav>

        <div class="border-b border-slate-200 px-6">
          <div class="flex overflow-x-auto hide-scrollbar">
            <button type="button" data-tab="calendar" class="tab-btn text-sm active flex items-center gap-1.5 px-3 py-3.5 whitespace-nowrap outline-none">
              <i data-lucide="calendar" class="w-4 h-4"></i> ปฏิทินงาน
            </button>
            <button type="button" data-tab="kanban" class="tab-btn text-sm flex items-center gap-1.5 px-3 py-3.5 whitespace-nowrap outline-none">
              <i data-lucide="kanban-square" class="w-4 h-4"></i> บอร์ดสถานะงาน
            </button>
            <button type="button" data-tab="timeline_daily" class="tab-btn text-sm flex items-center gap-1.5 px-3 py-3.5 whitespace-nowrap outline-none">
              <i data-lucide="clock" class="w-4 h-4"></i> ไทม์ไลน์ (รายวัน)
            </button>
            <button type="button" data-tab="timeline_team" class="tab-btn text-sm flex items-center gap-1.5 px-3 py-3.5 whitespace-nowrap outline-none">
              <i data-lucide="users" class="w-4 h-4"></i> ไทม์ไลน์ (ทีม)
            </button>
          </div>
        </div>
      </header>

      <main class="relative z-10 px-4 pb-4 flex-1 min-h-0 overflow-y-auto scrollbar-thin">
        <section class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-4">
          <aside class="md:col-span-3 lg:col-span-2 space-y-4 md:sticky md:top-4 h-fit">
            <div class="relative w-full" id="filterWrapper">
              <button id="filterToggleBtn" class="md:hidden w-full flex items-center justify-between bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 mb-2">
                <div class="flex items-center gap-2">
                  <i data-lucide="filter" class="w-4 h-4 text-slate-500"></i>
                  <span>ตัวกรองข้อมูล</span>
                </div>
                <i data-lucide="chevron-down" id="filterChevron" class="w-4 h-4 text-slate-500 transition-transform duration-200"></i>
              </button>

              <div id="filterDropdown" class="hidden md:block absolute md:relative z-20 w-full left-0 mt-1 md:mt-0">
                <div class="bg-white rounded-xl p-4 shadow-card border border-slate-100">
                  <h2 class="hidden md:block font-semibold text-base text-slate-950">ตัวกรอง</h2>
                  
                  <div class="mt-0 md:mt-3 space-y-3">
                    <div>
                      <label class="block text-xs font-medium text-slate-600 mb-1">ค้นหา</label>
                      <input id="jobSearchInput" type="text" placeholder="ค้นหาเลขที่งาน / ชื่อ..." class="w-full rounded-lg border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-slate-600 mb-1">เลือกช่าง</label>
                      <select id="technicianSelect" class="w-full rounded-lg border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs outline-none focus:ring-2 focus:ring-indigo-500"></select>
                    </div>
                    
                    <div>
                      <label class="block text-xs font-medium text-slate-600 mb-1">เลือกช่วงเวลา</label>
                      <div class="grid grid-cols-3 gap-1.5 bg-slate-100 p-1 rounded-lg mb-2">
                        <button class="view-btn rounded-md text-xs font-medium" data-view="day">รายวัน</button>
                        <button class="view-btn rounded-md text-xs font-medium" data-view="month">รายเดือน</button>
                        <button class="view-btn rounded-md text-xs font-medium" data-view="year">รายปี</button>
                      </div>
                      <div class="grid grid-cols-2 gap-2">
                        <div>
                          <label class="block text-[10px] text-slate-500 mb-0.5">เริ่มต้น</label>
                          <input id="startDatePicker" type="date" class="w-full rounded-lg border-slate-200 bg-slate-50 px-2 py-1.5 text-xs outline-none focus:ring-2 focus:ring-indigo-500" />
                        </div>
                        <div>
                          <label class="block text-[10px] text-slate-500 mb-0.5">สิ้นสุด</label>
                          <input id="endDatePicker" type="date" class="w-full rounded-lg border-slate-200 bg-slate-50 px-2 py-1.5 text-xs outline-none focus:ring-2 focus:ring-indigo-500" />
                        </div>
                      </div>
                    </div>

                    <button id="btnReset" class="w-full rounded-lg py-1.5 bg-indigo-50 text-indigo-700 text-xs font-semibold hover:bg-indigo-100">
                      ล้างตัวกรอง
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-xl p-4 shadow-card border border-slate-100">
              <h2 class="font-semibold text-base text-slate-950">สถานะงาน</h2>
              <div class="mt-3 space-y-2 text-xs" id="statusLegend"></div>
            </div>
          </aside>

          <section class="md:col-span-9 lg:col-span-10">
            <!-- ปฏิทินงาน -->
            <div id="tab-calendar" class="tab-panel space-y-3">
              <div class="grid grid-cols-3 md:grid-cols-5 gap-2.5" id="kpiCards"></div>

              <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
                <div class="lg:col-span-2 bg-white rounded-xl shadow-card border border-slate-100 overflow-hidden">
                  <div class="p-3 border-b border-slate-100 flex flex-col xs:flex-row xs:items-center xs:justify-between gap-2">
                    <div class="flex justify-between items-center gap-2">
                      <div>
                        <h2 id="calendarTitle" class="text-base font-bold text-slate-950">ปฏิทินงาน</h2>
                        <p id="calendarSubtitle" class="text-[11px] text-slate-500 mt-0.5">รายการงานของช่างในช่วงเวลาที่เลือก</p>
                      </div>
                      <span id="jobCountBadge" class="px-2 py-1 rounded-full bg-slate-100 text-slate-700 font-medium">0 งาน</span>
                    </div>
                  </div>
                  <div id="monthCalendarWrap" class="p-2.5">
                    <div class="calendar-grid text-center text-[10px] xs:text-[11px] font-semibold text-slate-500 border-b border-slate-100 pb-1.5">
                      <div>อา.</div><div>จ.</div><div>อ.</div><div>พ.</div><div>พฤ.</div><div>ศ.</div><div>ส.</div>
                    </div>
                    <div id="monthCalendar" class="calendar-grid mt-1"></div>
                  </div>
                  <div id="dayTimelineWrap" class="hidden p-3">
                    <div id="dayTimeline" class="space-y-1.5"></div>
                  </div>
                  <div id="yearCalendarWrap" class="hidden p-3">
                    <div id="yearGrid" class="grid grid-cols-1 xs:grid-cols-2 lg:grid-cols-3 gap-2.5"></div>
                  </div>
                </div>

                <div class="space-y-3">
                  <div class="bg-white rounded-xl shadow-card border border-slate-100 p-3">
                    <div class="flex items-start justify-between gap-2">
                      <div>
                        <h2 class="text-base font-bold text-slate-950">ประสิทธิภาพ</h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">สรุปรายการตามข้อมูลที่กรอง</p>
                      </div>
                      <span id="scoreBadge" class="px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-semibold">0%</span>
                    </div>
                    <div class="mt-3 h-40"><canvas id="performanceChart"></canvas></div>
                  </div>

                  <div id="techJobsCard" class="fullscreen-panel bg-white rounded-xl shadow-card border border-slate-100 p-3 relative group/card flex flex-col">
                    <div class="flex items-center justify-between gap-2 mb-3">
                      <h2 class="text-base font-bold text-slate-950">จำนวนงานรายช่าง</h2>
                      <button id="btnFullscreenTechJobs" type="button" class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg" title="ขยายเต็มจอ">
                        <i data-lucide="maximize-2" class="w-4 h-4"></i>
                      </button>
                    </div>
                    <div id="techJobsChartWrap" class="h-[200px]"><canvas id="techJobsChart"></canvas></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- บอร์ดสถานะงาน (Kanban) -->
            <div id="tab-kanban" class="tab-panel hidden">
              <div class="bg-white rounded-xl shadow-card border border-slate-100 overflow-hidden h-full flex flex-col">
                <div class="p-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                  <div>
                    <h2 id="kanbanTitle" class="text-base font-bold text-slate-950">บอร์ดสถานะงาน (Kanban)</h2>
                    <p id="kanbanSubtitle" class="text-[11px] text-slate-500 mt-0.5">จัดกลุ่มงานตามสถานะในช่วงเวลาและช่างที่เลือก</p>
                  </div>
                </div>
                <div class="p-4 flex-1 bg-slate-50/50">
                  <div id="kanbanContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 h-full"></div>
                </div>
              </div>
            </div>

            <!-- ไทม์ไลน์ (รายวัน) แท็บใหม่ -->
            <div id="tab-timeline_daily" class="tab-panel hidden">
              <div id="timelineDailyPanel" class="fullscreen-panel bg-white rounded-xl shadow-card border border-slate-100 overflow-clip flex flex-col">
                <div class="p-3 border-b border-slate-100 flex items-center justify-between gap-2 flex-wrap">
                  <div>
                    <h2 class="text-base xs:text-lg font-bold text-slate-950">ไทม์ไลน์งานรายวัน (Timeline)</h2>
                    <p class="text-[11px] text-slate-500 mt-1">แสดงงานในแต่ละวันตามช่วงเวลา 24 ชั่วโมง</p>
                  </div>
                  <div class="flex items-center gap-2">
                    <div class="timeline-zoom-controls" title="กด Ctrl/⌘ + เลื่อนล้อเมาส์เพื่อซูมได้เช่นกัน">
                      <button id="btnZoomOutDaily" type="button" title="ซูมออก" class="timeline-zoom-btn">
                        <i data-lucide="zoom-out" class="h-3.5 w-3.5"></i>
                      </button>
                      <span id="zoomLabelDaily" class="timeline-zoom-label">100%</span>
                      <button id="btnZoomInDaily" type="button" title="ซูมเข้า" class="timeline-zoom-btn">
                        <i data-lucide="zoom-in" class="h-3.5 w-3.5"></i>
                      </button>
                      <button id="btnZoomResetDaily" type="button" title="รีเซ็ตซูม" class="timeline-zoom-btn">
                        <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                      </button>
                    </div>
                    <button id="btnExportTimelineDaily" type="button" title="Export Excel" class="inline-flex h-8 items-center gap-1.5 px-3 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 text-xs font-semibold shadow-sm hover:bg-emerald-100">
                      <i data-lucide="file-spreadsheet" class="h-4 w-4"></i> Export Excel
                    </button>
                    <button id="btnFullscreenTimelineDaily" type="button" title="แสดงเต็มจอ" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm hover:bg-slate-50 hover:text-slate-800">
                      <i data-lucide="maximize-2" class="h-4 w-4"></i>
                    </button>
                  </div>
                </div>
                <div class="p-3 flex-1 flex flex-col">
                  <div id="timelineDailyContainer" class="timeline-container w-full"></div>
                </div>
              </div>
            </div>

            <!-- ไทม์ไลน์ (รายทีม) แท็บใหม่ -->
            <div id="tab-timeline_team" class="tab-panel hidden">
              <div id="timelineTeamPanel" class="fullscreen-panel bg-white rounded-xl shadow-card border border-slate-100 overflow-clip flex flex-col">
                <div class="p-3 border-b border-slate-100 flex items-center justify-between gap-2 flex-wrap">
                  <div>
                    <h2 class="text-base xs:text-lg font-bold text-slate-950">ไทม์ไลน์ทีม (Team Timeline)</h2>
                    <p class="text-[11px] text-slate-500 mt-1">แสดงงานแยกตามช่างในช่วงเวลาที่เลือก</p>
                  </div>
                  <div class="flex items-center gap-2">
                    <div class="timeline-zoom-controls" title="กด Ctrl/⌘ + เลื่อนล้อเมาส์เพื่อซูมได้เช่นกัน">
                      <button id="btnZoomOutTeam" type="button" title="ซูมออก" class="timeline-zoom-btn">
                        <i data-lucide="zoom-out" class="h-3.5 w-3.5"></i>
                      </button>
                      <span id="zoomLabelTeam" class="timeline-zoom-label">100%</span>
                      <button id="btnZoomInTeam" type="button" title="ซูมเข้า" class="timeline-zoom-btn">
                        <i data-lucide="zoom-in" class="h-3.5 w-3.5"></i>
                      </button>
                      <button id="btnZoomResetTeam" type="button" title="รีเซ็ตซูม" class="timeline-zoom-btn">
                        <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                      </button>
                    </div>
                    <button id="btnExportTimelineTeam" type="button" title="Export Excel" class="inline-flex h-8 items-center gap-1.5 px-3 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 text-xs font-semibold shadow-sm hover:bg-emerald-100">
                      <i data-lucide="file-spreadsheet" class="h-4 w-4"></i> Export Excel
                    </button>
                    <button id="btnFullscreenTimelineTeam" type="button" title="แสดงเต็มจอ" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm hover:bg-slate-50 hover:text-slate-800">
                      <i data-lucide="maximize-2" class="h-4 w-4"></i>
                    </button>
                  </div>
                </div>
                <div class="p-3 flex-1 flex flex-col">
                  <div id="timelineTeamContainer" class="timeline-container w-full"></div>
                </div>
              </div>
            </div>

          </section>
        </section>
      </main>
    </div>

    <script>
      // ฟังก์ชันสำหรับจัดการเปิด/ปิด ตัวกรองในมือถือ
      const filterToggleBtn = document.getElementById("filterToggleBtn");
      const filterDropdown = document.getElementById("filterDropdown");
      const filterChevron = document.getElementById("filterChevron");

      if (filterToggleBtn && filterDropdown) {
        filterToggleBtn.addEventListener("click", () => {
          filterDropdown.classList.toggle("hidden");
          if (filterDropdown.classList.contains("hidden")) {
            filterChevron.classList.remove("rotate-180");
          } else {
            filterChevron.classList.add("rotate-180");
          }
        });

        document.addEventListener("click", (event) => {
          const isClickInside = filterToggleBtn.contains(event.target) || filterDropdown.contains(event.target);
          if (!isClickInside && window.innerWidth < 768 && !filterDropdown.classList.contains("hidden")) {
            filterDropdown.classList.add("hidden");
            filterChevron.classList.remove("rotate-180");
          }
        });
      }

      lucide.createIcons();

      const mockData = {
        technicians: [
          { id: "all", name: "ช่างทั้งหมด", role: "รวมทุกทีม", avatar: "https://placehold.co/120x120/e0e7ff/3730a3?text=ALL" },
          { id: "unassigned", name: "ยังไม่มอบหมายช่าง", role: "งานที่ยังไม่ได้มอบหมายช่าง", avatar: "https://placehold.co/120x120/fef3c7/b45309?text=%3F" },
        ],
        status: {
          pending: { label: "รอดำเนินการ", color: "amber", className: "bg-amber-50 text-amber-700 border-amber-200" },
          in_progress: { label: "กำลังดำเนินการ", color: "sky", className: "bg-sky-50 text-sky-700 border-sky-200" },
          completed: { label: "เสร็จสิ้น", color: "emerald", className: "bg-emerald-50 text-emerald-700 border-emerald-200" },
          cencel: { label: "ยกเลิก", color: "rose", className: "bg-rose-50 text-rose-700 border-rose-200" },
        },
        jobs: [],
      };

      const TODAY = new Date();
      const byId = (id) => document.getElementById(id);

      const state = {
        technicianId: "all",
        view: "month", // 'day', 'month', 'year'
        startDate: new Date(TODAY.getFullYear(), TODAY.getMonth(), 1),
        endDate: new Date(TODAY.getFullYear(), TODAY.getMonth() + 1, 0),
        activeTab: "calendar",
        zoomDailyIdx: 2, // index into ZOOM_STEPS -> 1x (100%)
        zoomTeamIdx: 2,
      };

      // ระดับซูมของไทม์ไลน์ (0.5x - 4x)
      const ZOOM_STEPS = [0.5, 0.75, 1, 1.5, 2, 3, 4];
      const BASE_HOUR_WIDTH = 55; // px ต่อ 1 ชั่วโมง ที่ซูม 100% (ไทม์ไลน์รายวัน)
      const BASE_DAY_WIDTH = 90; // px ต่อ 1 วัน ที่ซูม 100% (ไทม์ไลน์ทีม)

      const els = {
        technicianSelect: byId("technicianSelect"),
        jobSearchInput: byId("jobSearchInput"),
        startDatePicker: byId("startDatePicker"),
        endDatePicker: byId("endDatePicker"),
        calendarTitle: byId("calendarTitle"),
        calendarSubtitle: byId("calendarSubtitle"),
        jobCountBadge: byId("jobCountBadge"),
        monthCalendarWrap: byId("monthCalendarWrap"),
        monthCalendar: byId("monthCalendar"),
        dayTimelineWrap: byId("dayTimelineWrap"),
        dayTimeline: byId("dayTimeline"),
        yearCalendarWrap: byId("yearCalendarWrap"),
        yearGrid: byId("yearGrid"),
        kpiCards: byId("kpiCards"),
        statusLegend: byId("statusLegend"),
        scoreBadge: byId("scoreBadge"),
        kanbanContainer: byId("kanbanContainer"),
        kanbanTitle: byId("kanbanTitle"),
        kanbanSubtitle: byId("kanbanSubtitle"),
        timelineDailyContainer: byId("timelineDailyContainer"),
        timelineTeamContainer: byId("timelineTeamContainer"),
        zoomLabelDaily: byId("zoomLabelDaily"),
        zoomLabelTeam: byId("zoomLabelTeam"),
        btnZoomInDaily: byId("btnZoomInDaily"),
        btnZoomOutDaily: byId("btnZoomOutDaily"),
        btnZoomResetDaily: byId("btnZoomResetDaily"),
        btnZoomInTeam: byId("btnZoomInTeam"),
        btnZoomOutTeam: byId("btnZoomOutTeam"),
        btnZoomResetTeam: byId("btnZoomResetTeam"),
      };

      let chart;
      let techChart;

      const fmtDate = (dateStr, opts = {}) =>
        new Intl.DateTimeFormat("th-TH", {
          dateStyle: "medium",
          timeStyle: opts.time ? "short" : undefined,
        }).format(new Date(dateStr));
      
      const fmtShortDate = (date) =>
        new Intl.DateTimeFormat("th-TH", { day: "2-digit", month: "short" }).format(date);
        
      const fmtTime = (dateStr) =>
        new Intl.DateTimeFormat("th-TH", { hour: "2-digit", minute: "2-digit" }).format(new Date(dateStr));
      
      const pad = (n) => String(n).padStart(2, "0");
      const toYMD = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

      const UNASSIGNED_TECH = {
        id: "unassigned",
        name: "ยังไม่มอบหมายช่าง",
        role: "รอมอบหมายช่าง",
        avatar: "https://placehold.co/120x120/fef3c7/b45309?text=%3F",
      };
      
      const getTech = (id) => {
        if (!id) return UNASSIGNED_TECH;
        return mockData.technicians.find((t) => t.id === id) || UNASSIGNED_TECH;
      };
      const getJobTech = (job) => getTech(job.technicianId);

      const avatarTones = [
        "bg-indigo-100 text-indigo-700", "bg-emerald-100 text-emerald-700",
        "bg-amber-100 text-amber-700", "bg-rose-100 text-rose-700",
        "bg-sky-100 text-sky-700", "bg-fuchsia-100 text-fuchsia-700",
      ];
      
      function getInitials(name) {
        const trimmed = (name || "").trim();
        return trimmed ? trimmed.slice(0, 2) : "?";
      }
      
      function getAvatarTone(seed) {
        const key = String(seed || "");
        let hash = 0;
        for (let i = 0; i < key.length; i++) hash = (hash * 31 + key.charCodeAt(i)) >>> 0;
        return avatarTones[hash % avatarTones.length];
      }
      
      function renderAvatarLabel(tech, sizeClasses) {
        return `<div class="${sizeClasses} ${getAvatarTone(tech.id || tech.name)} flex items-center justify-center font-bold shrink-0" title="${tech.name}">${getInitials(tech.name)}</div>`;
      }

      function fireSwal(options) {
        const fsEl = document.fullscreenElement;
        return Swal["fire"](fsEl ? { target: fsEl, ...options } : options);
      }

      const fullscreenTargets = [
        { buttonId: "btnFullscreenTimelineDaily", targetId: "timelineDailyPanel", name: "ไทม์ไลน์รายวัน" },
        { buttonId: "btnFullscreenTimelineTeam", targetId: "timelineTeamPanel", name: "ไทม์ไลน์ทีม" },
        { buttonId: "btnFullscreenTechJobs", targetId: "techJobsCard", name: "จำนวนงานรายช่าง" }
      ];

      function isFullscreenTarget(target) { return document.fullscreenElement === target; }

      async function toggleFullscreen(target) {
        if (!target?.requestFullscreen) return;
        if (isFullscreenTarget(target)) {
          await document.exitFullscreen();
        } else {
          if (document.fullscreenElement) await document.exitFullscreen();
          await target.requestFullscreen();
        }
      }

      function updateFullscreenButtons() {
        fullscreenTargets.forEach(({ buttonId, targetId, name }) => {
          const button = byId(buttonId);
          const target = byId(targetId);
          if (!button || !target) return;
          const active = isFullscreenTarget(target);
          button.title = active ? "ออกจากเต็มจอ" : "แสดงเต็มจอ";
          button.innerHTML = `<i data-lucide="${active ? "minimize-2" : "maximize-2"}" class="h-4 w-4"></i>`;
        });
        lucide.createIcons();
      }

      function bindFullscreenTables() {
        fullscreenTargets.forEach(({ buttonId, targetId }) => {
          byId(buttonId)?.addEventListener("click", () => toggleFullscreen(byId(targetId)));
        });
        document.addEventListener("fullscreenchange", () => {
          updateFullscreenButtons();
          if (techChart) techChart.resize();
          if (chart) chart.resize();
        });
      }

      function initTabs() {
        document.querySelectorAll(".tab-btn").forEach((btn) => {
          btn.addEventListener("click", () => setActiveTab(btn.dataset.tab));
        });
        setActiveTab(state.activeTab);
      }

      function setActiveTab(tab) {
        state.activeTab = tab;
        document.querySelectorAll(".tab-btn").forEach((btn) => {
          btn.classList.toggle("active", btn.dataset.tab === tab);
        });
        document.querySelectorAll(".tab-panel").forEach((panel) => {
          panel.classList.toggle("hidden", panel.id !== `tab-${tab}`);
        });
        if (tab === "calendar" && chart) {
          chart.resize();
          if (techChart) techChart.resize();
        }
      }

      // ==================== API ====================
      const API_URL = "handle_work_history.php";
      const AG_ID = <?php echo isset($sess_user_agency_es) ? $sess_user_agency_es : "1"; ?>; // จำลอง AG_ID ถ้าไม่มีค่า
      const apiState = { search: "" };

      function getQueryDateRange() {
        return { startDate: toYMD(state.startDate), endDate: toYMD(state.endDate) };
      }

      function buildJobsQuery() {
        const { startDate, endDate } = getQueryDateRange();
        const params = new URLSearchParams({
          action: "get_jobs", ag_id: AG_ID, startDate, endDate,
          search: apiState.search || "", technicianId: "all",
        });
        return `${API_URL}?${params.toString()}`;
      }

      async function fetchTechniciansFromApi() {
        try {
          const res = await fetch(`${API_URL}?action=get_technicians&ag_id=${encodeURIComponent(AG_ID)}`);
          const data = await res.json();
          if (data.success && Array.isArray(data.rows)) {
            const rest = data.rows.filter((t) => t.id !== "all");
            mockData.technicians = [
              mockData.technicians[0],
              mockData.technicians[1],
              ...rest.map((t) => ({
                ...t,
                avatar: t.avatar || `https://placehold.co/120x120/e0e7ff/3730a3?text=${encodeURIComponent((t.name || "?").slice(0, 2))}`,
              }))
            ];
          }
        } catch (err) { console.error(err); }
      }

      async function fetchJobsFromApi() {
        try {
          const res = await fetch(buildJobsQuery());
          const data = await res.json();
          if (data.success && Array.isArray(data.rows)) {
            mockData.jobs = data.rows.map((job) => ({
              ...job,
              technicianId: job.technicianId || "unassigned",
            }));
          }
        } catch (err) { console.error(err); }
      }

      async function loadDataFromApi() {
        await Promise.all([fetchTechniciansFromApi(), fetchJobsFromApi()]);
      }

      async function refreshJobsAndRender() {
        await fetchJobsFromApi();
        render();
      }

      let searchDebounceTimer = null;
      function onJobSearchChange(value) {
        apiState.search = value.trim();
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(async () => {
          await fetchJobsFromApi();
          render();
        }, 350);
      }

      async function init() {
        await loadDataFromApi();
        renderTechnicianOptions();
        renderStatusLegend();
        bindEvents();
        bindFullscreenTables();
        bindExportButtons();
        initTabs();
        initZoomControls();
        syncDateInput();
        render();
      }

      function renderTechnicianOptions() {
        els.technicianSelect.innerHTML = mockData.technicians
          .map((t) => `<option value="${t.id}">${t.name}</option>`)
          .join("");
        els.technicianSelect.value = state.technicianId;
      }

      function renderStatusLegend() {
        const rows = Object.entries(mockData.status)
          .map(([key, s]) => {
            const map = { completed: "bg-emerald-400", in_progress: "bg-sky-400", pending: "bg-amber-400", cencel: "bg-rose-400" };
            return `<div class="flex items-center justify-between gap-2">
              <span class="inline-flex items-center gap-2"><span class="status-dot ${map[key]}"></span>${s.label}</span>
              <span class="text-slate-400" id="legend-${key}">0</span>
            </div>`;
          }).join("");
        els.statusLegend.innerHTML = rows;
      }

      function bindEvents() {
        els.technicianSelect.addEventListener("change", (e) => {
          state.technicianId = e.target.value;
          render();
        });
        
        if (els.jobSearchInput) {
          els.jobSearchInput.addEventListener("input", (e) => {
            onJobSearchChange(e.target.value);
          });
        }
        
        document.querySelectorAll(".view-btn").forEach((btn) =>
          btn.addEventListener("click", () => {
            state.view = btn.dataset.view;
            const d = new Date(TODAY);
            if(state.view === "day") {
                state.startDate = new Date(d);
                state.endDate = new Date(d);
            } else if (state.view === "month") {
                state.startDate = new Date(d.getFullYear(), d.getMonth(), 1);
                state.endDate = new Date(d.getFullYear(), d.getMonth() + 1, 0);
            } else if (state.view === "year") {
                state.startDate = new Date(d.getFullYear(), 0, 1);
                state.endDate = new Date(d.getFullYear(), 11, 31);
            }
            syncDateInput();
            refreshJobsAndRender();
          })
        );
        
        els.startDatePicker.addEventListener("change", (e) => {
          state.startDate = new Date(e.target.value);
          if(state.startDate > state.endDate) {
              state.endDate = new Date(state.startDate);
          }
          syncDateInput();
          refreshJobsAndRender();
        });
        
        els.endDatePicker.addEventListener("change", (e) => {
          state.endDate = new Date(e.target.value);
          if(state.endDate < state.startDate) {
              state.startDate = new Date(state.endDate);
          }
          syncDateInput();
          refreshJobsAndRender();
        });

        byId("btnToday").addEventListener("click", () => {
          state.view = "day";
          state.startDate = new Date(TODAY);
          state.endDate = new Date(TODAY);
          syncDateInput();
          refreshJobsAndRender();
        });

        byId("btnReset").addEventListener("click", () => {
          state.technicianId = "all";
          state.view = "month";
          state.startDate = new Date(TODAY.getFullYear(), TODAY.getMonth(), 1);
          state.endDate = new Date(TODAY.getFullYear(), TODAY.getMonth() + 1, 0);
          
          if (els.jobSearchInput) {
            els.jobSearchInput.value = "";
          }
          apiState.search = ""; 

          els.technicianSelect.value = "all";
          syncDateInput();
          refreshJobsAndRender();
        });
      }

      function syncDateInput() {
        document.querySelectorAll(".view-btn").forEach((btn) => {
          btn.className = `view-btn rounded-xl py-1 text-sm font-medium ${btn.dataset.view === state.view ? "bg-white text-indigo-700 shadow-sm" : "text-slate-500 hover:text-slate-700"}`;
        });
        els.startDatePicker.value = toYMD(state.startDate);
        els.endDatePicker.value = toYMD(state.endDate);
      }

      function getFilteredJobs() {
        return mockData.jobs
          .filter((job) => {
            const workStart = new Date(job.workStart);
            let workEnd = job.workEnd ? new Date(job.workEnd) : workStart;
            // กันเคส workEnd เป็นสตริงวันที่ไม่สมบูรณ์ (เช่น "2026-07-16T" ไม่มีเวลา)
            // ซึ่ง JS parse เป็น Invalid Date แล้วจะทำให้เงื่อนไขเทียบวันที่ผิดพลาด
            // และงานนั้นถูกกรองทิ้งไปเงียบๆ โดยไม่มี error ให้เห็น
            if (isNaN(workEnd.getTime())) workEnd = workStart;
            const matchTech = state.technicianId === "all" || job.technicianId === state.technicianId;
            
            // ให้งานที่ทับซ้อนช่วงที่เลือกแสดงผล
            const vStart = new Date(state.startDate);
            vStart.setHours(0,0,0,0);
            const vEnd = new Date(state.endDate);
            vEnd.setHours(23,59,59,999);
            
            const matchDate = workStart <= vEnd && workEnd >= vStart;
            return matchTech && matchDate;
          })
          .sort((a, b) => new Date(a.workStart) - new Date(b.workStart));
      }

      function render() {
        const jobs = getFilteredJobs();
        updateHeaders(jobs);
        renderKPIs(jobs);
        renderCalendar(jobs); // Calendar remains working on state.startDate
        renderTechJobsChart(jobs);
        renderChart(jobs);
        updateLegends(jobs);
        renderKanban(jobs);
        
        renderTimelineDaily(jobs);
        renderTimelineTeam(jobs);
      }

      function updateHeaders(jobs) {
        const tech = getTech(state.technicianId);
        let titleDate = "";
        if(toYMD(state.startDate) === toYMD(state.endDate)) {
            titleDate = fmtDate(state.startDate);
        } else {
            titleDate = `${fmtShortDate(state.startDate)} - ${fmtShortDate(state.endDate)}`;
        }

        els.calendarTitle.textContent = `ปฏิทินงาน: ${titleDate}`;
        els.calendarSubtitle.textContent = `${tech.name} • ${tech.role}`;
        els.jobCountBadge.textContent = `${jobs.length} งาน`;

        els.kanbanTitle.textContent = `บอร์ดสถานะงาน: ${titleDate}`;
        els.kanbanSubtitle.textContent = `${tech.name} • แสดงใบแจ้งซ่อมตามสถานะในปัจจุบัน`;
      }

      // --- Timeline (Notion Style) Logic ---
      
      const statusColors = {
        pending: "bg-amber-500",
        in_progress: "bg-sky-500",
        completed: "bg-emerald-500",
        cencel: "bg-rose-500"
      };

      function calculateTimelineLanes(jobs, startBound, endBound, calcLeftPct, calcWidthPct, totalPx) {
        const sorted = [...jobs].filter(j => j.workStart).sort((a, b) => new Date(a.workStart) - new Date(b.workStart));
        const lanes = []; // เก็บตำแหน่ง % ฝั่งขวาสุดของแต่ละเลน (เพื่อกันภาพทับกัน)
        
        // กำหนดความกว้างขั้นต่ำประมาณ 160px เพื่อให้เห็นรหัสงานและชื่องานครบถ้วน
        const MIN_PX = 160; 
        const minWidthPct = totalPx ? (MIN_PX / totalPx) * 100 : 0;
        
        sorted.forEach(job => {
            let start = new Date(job.workStart);
            let end = job.workEnd ? new Date(job.workEnd) : new Date(start.getTime() + 60*60*1000);
            
            if (start < startBound) start = new Date(startBound);
            if (end > endBound) end = new Date(endBound);
            if (end <= start) return;

            const left = calcLeftPct(start);
            const width = calcWidthPct(start, end);
            
            // ความกว้างที่จะแสดงผลจริง (ป้องกันไม่ให้สั้นกว่าค่าขั้นต่ำ)
            const renderWidth = Math.max(minWidthPct, width);
            const rightPct = left + renderWidth; // จุดที่แท่งงานนี้จะไปสิ้นสุดบนหน้าจอ

            let placed = false;
            for (let i = 0; i < lanes.length; i++) {
                // เช็คว่าพื้นที่สายตาในเลนนี้ ว่างพอสำหรับตำแหน่งเริ่มต้นของงานนี้หรือไม่
                if (lanes[i] <= left) {
                    lanes[i] = rightPct;
                    job.lane = i;
                    placed = true;
                    break;
                }
            }
            if (!placed) {
                lanes.push(rightPct);
                job.lane = lanes.length - 1;
            }
            job.renderLeft = Math.max(0, left);
            job.renderWidth = renderWidth;
        });
        
        return { processedJobs: sorted, totalLanes: lanes.length };
    }

      function getDatesInRange(start, end) {
        const dates = [];
        let curr = new Date(start);
        curr.setHours(0,0,0,0);
        let endD = new Date(end);
        endD.setHours(0,0,0,0);
        
        while(curr <= endD) {
            dates.push(new Date(curr));
            curr.setDate(curr.getDate() + 1);
        }
        return dates;
      }

      function renderTimelineDaily(jobs) {
        const dates = getDatesInRange(state.startDate, state.endDate);
        const zoom = ZOOM_STEPS[state.zoomDailyIdx];
        const totalPx = Math.round(BASE_HOUR_WIDTH * zoom * 24);

        let headerCellsHtml = "";
        for(let i=0; i<24; i++) {
            headerCellsHtml += `<div class="timeline-header-cell">${pad(i)}:00</div>`;
        }
        let headerHtml = `<div class="timeline-sidebar">วันที่</div><div class="timeline-header-content" style="min-width:${totalPx}px;">${headerCellsHtml}</div>`;
        
        let bodyHtml = "";
        
        dates.forEach(day => {
            const dayYMD = toYMD(day);
            const startOfDay = new Date(day);
            startOfDay.setHours(0,0,0,0);
            const endOfDay = new Date(day);
            endOfDay.setHours(23,59,59,999);
            
            const dayJobs = jobs.filter(j => {
                const s = new Date(j.workStart);
                const e = j.workEnd ? new Date(j.workEnd) : new Date(s.getTime() + 60*60*1000);
                return s <= endOfDay && e >= startOfDay;
            });

            const calcLeft = (t) => ((t.getHours() * 60 + t.getMinutes()) / 1440) * 100;
            const calcWidth = (s, e) => ((e - s) / 60000) / 1440 * 100;

            const { processedJobs, totalLanes } = calculateTimelineLanes(dayJobs, startOfDay, endOfDay, calcLeft, calcWidth);
            
            // 40px per lane + 20px padding
            const rowHeight = totalLanes === 0 ? 60 : Math.max(60, (totalLanes * 34) + 16); 
            
            let barsHtml = "";
            processedJobs.forEach(job => {
                if(job.renderWidth === undefined) return;
                const bgColor = statusColors[job.status] || "bg-slate-500";
                const topPos = 8 + (job.lane * 34);
                barsHtml += `
                    <div class="timeline-bar ${bgColor}" 
                         style="left: ${job.renderLeft}%; width: ${Math.max(1, job.renderWidth)}%; top: ${topPos}px;"
                         onclick="openJobDetail('${job.id}')"
                         title="${job.id} ${job.title} | ${fmtTime(job.workStart)} - ${job.workEnd ? fmtTime(job.workEnd) : 'N/A'}">
                        ${job.id} ${job.title}
                    </div>
                `;
            });

            bodyHtml += `
                <div class="timeline-row" style="height: ${rowHeight}px;">
                    <div class="timeline-sidebar flex-col !items-start justify-center gap-0.5">
                        <span class="text-[13px]">${fmtShortDate(day)}</span>
                        <span class="text-[10px] text-slate-400 font-normal">${dayJobs.length} งาน</span>
                    </div>
                    <div class="timeline-content" style="min-width:${totalPx}px; background-size: calc(100% / 24) 100%;">
                        ${barsHtml}
                    </div>
                </div>
            `;
        });

        els.timelineDailyContainer.innerHTML = `
            <div class="timeline-header">${headerHtml}</div>
            <div class="timeline-body">${bodyHtml}</div>
        `;
        updateZoomUI("daily");
      }

      function renderTimelineTeam(jobs) {
        const dates = getDatesInRange(state.startDate, state.endDate);
        const totalDays = dates.length;
        const zoom = ZOOM_STEPS[state.zoomTeamIdx];
        const totalPx = Math.round(BASE_DAY_WIDTH * zoom * totalDays);

        let headerCellsHtml = "";
        dates.forEach(day => {
            headerCellsHtml += `<div class="timeline-header-cell border-l border-slate-200">${fmtShortDate(day)}</div>`;
        });
        let headerHtml = `<div class="timeline-sidebar">ชื่อช่าง</div><div class="timeline-header-content" style="min-width:${totalPx}px;">${headerCellsHtml}</div>`;

        const startBound = new Date(state.startDate);
        startBound.setHours(0,0,0,0);
        const endBound = new Date(state.endDate);
        endBound.setHours(23,59,59,999);
        const totalMs = endBound - startBound;

        // Filter valid technicians to show
        const techsToShow = mockData.technicians.filter(t => t.id !== 'all' && (state.technicianId === 'all' || t.id === state.technicianId));
        
        let bodyHtml = "";
        
        techsToShow.forEach(tech => {
            const techJobs = jobs.filter(j => j.technicianId === tech.id);
            
            const calcLeft = (t) => ((t - startBound) / totalMs) * 100;
            const calcWidth = (s, e) => ((e - s) / totalMs) * 100;

            const { processedJobs, totalLanes } = calculateTimelineLanes(techJobs, startBound, endBound, calcLeft, calcWidth);
            
            const rowHeight = totalLanes === 0 ? 70 : Math.max(70, (totalLanes * 34) + 20);
            
            let barsHtml = "";
            processedJobs.forEach(job => {
                if(job.renderWidth === undefined) return;
                const bgColor = statusColors[job.status] || "bg-slate-500";
                const topPos = 10 + (job.lane * 34);
                barsHtml += `
                    <div class="timeline-bar ${bgColor}" 
                         style="left: ${job.renderLeft}%; width: ${Math.max(0.5, job.renderWidth)}%; top: ${topPos}px;"
                         onclick="openJobDetail('${job.id}')"
                         title="${job.id} ${job.title} | ${fmtDate(job.workStart, {time:true})}">
                        ${job.id} ${job.title}
                    </div>
                `;
            });

            bodyHtml += `
                <div class="timeline-row" style="height: ${rowHeight}px;">
                    <div class="timeline-sidebar gap-2">
                        ${renderAvatarLabel(tech, "h-8 w-8 rounded-lg text-xs")}
                        <div class="min-w-0 flex-1">
                            <div class="text-[11px] leading-tight truncate">${tech.name}</div>
                            <div class="text-[9px] text-slate-400 font-normal mt-0.5">${techJobs.length} งาน</div>
                        </div>
                    </div>
                    <div class="timeline-content" style="min-width:${totalPx}px; background-size: calc(100% / ${totalDays}) 100%;">
                        ${barsHtml}
                    </div>
                </div>
            `;
        });

        if (techsToShow.length === 0) {
            bodyHtml = `<div class="p-8 text-center text-slate-400 text-sm">ไม่พบข้อมูลช่าง</div>`;
        }

        els.timelineTeamContainer.innerHTML = `
            <div class="timeline-header">${headerHtml}</div>
            <div class="timeline-body">${bodyHtml}</div>
        `;
        updateZoomUI("team");
      }

      // --- Export Excel (ไทม์ไลน์ + รายการข้อมูล) ---

      function getJobDurationText(job) {
        if (job.workStart && job.workEnd) {
          return formatDurationText(new Date(job.workEnd) - new Date(job.workStart));
        }
        return job.workDuration || "-";
      }

      function getJobListSheetRows(jobs) {
        const header = [
          "เลขที่งาน", "ชื่องาน", "ช่างผู้รับผิดชอบ", "บทบาท", "สถานะ",
          "วันที่แจ้งซ่อม", "ผู้แจ้ง", "เบอร์โทร", "สถานที่",
          "วันที่เข้าดำเนินการ", "เวลาเข้าดำเนินการ",
          "วันที่เสร็จสิ้น", "เวลาเสร็จสิ้น", "ระยะเวลาทำงาน",
          "รายละเอียด/สาเหตุที่แจ้งซ่อม", "วิธีที่ช่างแก้ไขปัญหา",
        ];
        const rows = jobs.map((job) => {
          const tech = getJobTech(job);
          const status = mockData.status[job.status]?.label || job.status || "-";
          return [
            job.id || "-",
            job.title || "-",
            tech.name,
            tech.role,
            status,
            job.reportDate ? fmtDate(job.reportDate, { time: true }) : "-",
            job.reporter || "-",
            job.phone || "-",
            job.location || "-",
            job.workStart ? fmtDate(job.workStart) : "-",
            job.workStart ? fmtTime(job.workStart) : "-",
            job.workEnd ? fmtDate(job.workEnd) : "-",
            job.workEnd ? fmtTime(job.workEnd) : "-",
            getJobDurationText(job),
            job.issueDetail || "-",
            job.solution || "-",
          ];
        });
        return [header, ...rows];
      }

      // ชุดสีสำหรับระบายเซลล์ตามรหัสงาน (สีเดียวกันเสมอสำหรับรหัสงานเดียวกัน)
      const JOB_COLOR_PALETTE = [
        { bg: "FFDCEFFB", font: "FF004A6F" },
        { bg: "FFDCFCE7", font: "FF14532D" },
        { bg: "FFFEF3C7", font: "FF78350F" },
        { bg: "FFFCE7F3", font: "FF831843" },
        { bg: "FFEDE9FE", font: "FF4C1D95" },
        { bg: "FFFFE4E6", font: "FF881337" },
        { bg: "FFE0E7FF", font: "FF312E81" },
        { bg: "FFD1FAE5", font: "FF065F46" },
        { bg: "FFFFEDD5", font: "FF7C2D12" },
        { bg: "FFCCFBF1", font: "FF134E4A" },
        { bg: "FFFAE8FF", font: "FF701A75" },
        { bg: "FFE2E8F0", font: "FF1E293B" },
      ];
      const jobColorCache = {};
      function getJobColor(jobId) {
        const key = String(jobId || "-");
        if (jobColorCache[key]) return jobColorCache[key];
        let hash = 0;
        for (let i = 0; i < key.length; i++) hash = (hash * 31 + key.charCodeAt(i)) >>> 0;
        const color = JOB_COLOR_PALETTE[hash % JOB_COLOR_PALETTE.length];
        jobColorCache[key] = color;
        return color;
      }

      const THIN_BORDER = { style: "thin", color: { argb: "FFD9DEE4" } };
      const CELL_BORDER = { top: THIN_BORDER, left: THIN_BORDER, bottom: THIN_BORDER, right: THIN_BORDER };

      function styleHeaderRow(row) {
        row.eachCell((cell) => {
          cell.font = { name: "Tahoma", bold: true, size: 10, color: { argb: "FFFFFFFF" } };
          cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FF006B9F" } };
          cell.alignment = { vertical: "middle", horizontal: "center", wrapText: true };
          cell.border = CELL_BORDER;
        });
        row.height = 22;
      }

      // จัดสรร "เลน" (lane) ให้กับงานที่เวลาทับกัน (index-based) แบบเดียวกับอัลกอริทึม
      // calculateTimelineLanes ที่หน้าเว็บใช้แสดงผลไทม์ไลน์ ป้องกันไม่ให้งานคาบเกี่ยวกันถูกยัดรวมในช่องเดียว
      function assignLanesByIndex(items) {
        items.sort((a, b) => a.startIdx - b.startIdx || a.endIdx - b.endIdx);
        const laneEnd = [];
        items.forEach((it) => {
          let lane = laneEnd.findIndex((endIdx) => endIdx < it.startIdx);
          if (lane === -1) { lane = laneEnd.length; laneEnd.push(it.endIdx); }
          else laneEnd[lane] = it.endIdx;
          it.lane = lane;
        });
        return Math.max(1, laneEnd.length);
      }

      // แผ่นไทม์ไลน์รายวัน: แต่ละวันจะถูกแบ่งเป็นหลาย "เลน" ย่อย (แถวซ้อนใต้กัน) เพื่อไม่ให้งานที่
      // เวลาทับกันในชั่วโมงเดียวกันปนกันอยู่ในช่องเดียว — เหมือนแท่งงานหลายแถวบนหน้าเว็บ
      function getDailyTimelineBlocks(jobs) {
        const dates = getDatesInRange(state.startDate, state.endDate);
        return dates.map((day) => {
          const startOfDay = new Date(day); startOfDay.setHours(0, 0, 0, 0);
          const endOfDay = new Date(day); endOfDay.setHours(23, 59, 59, 999);

          const items = jobs
            .filter((j) => {
              const s = new Date(j.workStart);
              const e = j.workEnd ? new Date(j.workEnd) : new Date(s.getTime() + 60 * 60 * 1000);
              return s <= endOfDay && e >= startOfDay;
            })
            .map((j) => {
              let s = new Date(j.workStart);
              let e = j.workEnd ? new Date(j.workEnd) : new Date(s.getTime() + 60 * 60 * 1000);
              if (s < startOfDay) s = new Date(startOfDay);
              if (e > endOfDay) e = new Date(endOfDay);
              let startIdx = s.getHours();
              let endIdx = e.getHours();
              if (e.getMinutes() === 0 && e.getSeconds() === 0 && endIdx > startIdx) endIdx -= 1;
              endIdx = Math.min(23, Math.max(startIdx, endIdx));
              return { id: j.id, title: j.title, startIdx, endIdx };
            });

          const laneCount = assignLanesByIndex(items);
          const laneRows = Array.from({ length: laneCount }, () => Array.from({ length: 24 }, () => null));
          items.forEach((it) => {
            for (let h = it.startIdx; h <= it.endIdx; h++) laneRows[it.lane][h] = { id: it.id, title: it.title };
          });

          return { label: fmtShortDate(day), laneRows };
        });
      }

      // แผ่นไทม์ไลน์ทีม: แต่ละช่างแบ่งเป็นหลาย "เลน" ย่อยตามงานที่ช่วงวันที่ทับกัน
      function getTeamTimelineBlocks(jobs) {
        const dates = getDatesInRange(state.startDate, state.endDate);
        const rangeStart = dates[0];
        const dayMs = 24 * 60 * 60 * 1000;
        const dateIndex = (d) => {
          const dd = new Date(d); dd.setHours(0, 0, 0, 0);
          return Math.round((dd - rangeStart) / dayMs);
        };
        const clamp = (v) => Math.min(dates.length - 1, Math.max(0, v));

        const techsToShow = mockData.technicians.filter(
          (t) => t.id !== "all" && (state.technicianId === "all" || t.id === state.technicianId)
        );

        const rows = techsToShow.map((tech) => {
          const items = jobs
            .filter((j) => j.technicianId === tech.id)
            .map((j) => {
              const s = new Date(j.workStart);
              const e = j.workEnd ? new Date(j.workEnd) : new Date(s.getTime() + 60 * 60 * 1000);
              const startIdx = clamp(dateIndex(s));
              const endIdx = Math.max(startIdx, clamp(dateIndex(e)));
              return { id: j.id, title: j.title, startIdx, endIdx };
            });

          const laneCount = assignLanesByIndex(items);
          const laneRows = Array.from({ length: laneCount }, () => Array.from({ length: dates.length }, () => null));
          items.forEach((it) => {
            for (let d = it.startIdx; d <= it.endIdx; d++) laneRows[it.lane][d] = { id: it.id, title: it.title };
          });

          return { label: tech.name, laneRows };
        });

        return { dates, rows };
      }

      // เติมข้อมูลลงแถวเลนเดียว (แต่ละช่องมีได้อย่างมาก 1 งาน) พร้อมระบายสีตามรหัสงาน
      // และ merge เซลล์ที่เป็นรหัสงานเดียวกันติดต่อกัน
      function fillLaneRow(ws, row, laneCells, colOffset) {
        const rowNum = row.number;
        for (let i = 0; i < laneCells.length; i++) {
          const col = colOffset + i;
          const cellData = laneCells[i];
          const cell = row.getCell(col);
          cell.alignment = { vertical: "middle", horizontal: "center", wrapText: true };
          cell.border = CELL_BORDER;

          if (!cellData) { cell.value = ""; continue; }

          let endIdx = i;
          while (endIdx + 1 < laneCells.length && laneCells[endIdx + 1] && laneCells[endIdx + 1].id === cellData.id) endIdx++;
          const color = getJobColor(cellData.id);
          const startCol = colOffset + i;
          const endCol = colOffset + endIdx;
          for (let c = startCol; c <= endCol; c++) {
            const cc = row.getCell(c);
            cc.fill = { type: "pattern", pattern: "solid", fgColor: { argb: color.bg } };
            cc.font = { name: "Tahoma", size: 9, bold: true, color: { argb: color.font } };
            cc.alignment = { vertical: "middle", horizontal: "center", wrapText: true };
            cc.border = CELL_BORDER;
          }
          row.getCell(startCol).value = `${cellData.id}\n${cellData.title}`;
          if (endCol > startCol) ws.mergeCells(rowNum, startCol, rowNum, endCol);
          i = endIdx;
        }
      }

      // เพิ่มบล็อกแถว (label + เลนย่อยหลายแถว) ลงชีต แล้ว merge ช่องป้ายกำกับตามแนวตั้งให้ครอบทุกเลน
      function addLaneBlock(ws, label, laneRows) {
        const firstRowNum = ws.rowCount + 1;
        laneRows.forEach((laneCells) => {
          const row = ws.addRow([""]);
          fillLaneRow(ws, row, laneCells, 2);
          row.height = 30;
        });
        const lastRowNum = ws.rowCount;
        const labelCell = ws.getCell(firstRowNum, 1);
        labelCell.value = label;
        labelCell.font = { name: "Tahoma", bold: true, size: 10 };
        labelCell.alignment = { vertical: "middle", horizontal: "left", wrapText: true };
        for (let r = firstRowNum; r <= lastRowNum; r++) ws.getCell(r, 1).border = CELL_BORDER;
        if (lastRowNum > firstRowNum) ws.mergeCells(firstRowNum, 1, lastRowNum, 1);
      }

      function addDailyTimelineSheet(wb, jobs) {
        const ws = wb.addWorksheet("ไทม์ไลน์รายวัน", { views: [{ state: "frozen", xSplit: 1, ySplit: 1 }] });
        const header = ["วันที่", ...Array.from({ length: 24 }, (_, i) => `${pad(i)}:00`)];
        styleHeaderRow(ws.addRow(header));
        getDailyTimelineBlocks(jobs).forEach(({ label, laneRows }) => addLaneBlock(ws, label, laneRows));
        ws.getColumn(1).width = 14;
        for (let c = 2; c <= 25; c++) ws.getColumn(c).width = 15;
      }

      function addTeamTimelineSheet(wb, jobs) {
        const ws = wb.addWorksheet("ไทม์ไลน์ทีม", { views: [{ state: "frozen", xSplit: 1, ySplit: 1 }] });
        const { dates, rows } = getTeamTimelineBlocks(jobs);
        const header = ["ช่าง", ...dates.map((d) => fmtShortDate(d))];
        styleHeaderRow(ws.addRow(header));
        rows.forEach(({ label, laneRows }) => addLaneBlock(ws, label, laneRows));
        ws.getColumn(1).width = 20;
        for (let c = 2; c <= dates.length + 1; c++) ws.getColumn(c).width = 18;
      }

      function addJobListSheet(wb, jobs) {
        const ws = wb.addWorksheet("รายการข้อมูล");
        const [header, ...rows] = getJobListSheetRows(jobs);
        styleHeaderRow(ws.addRow(header));
        rows.forEach((r) => {
          const row = ws.addRow(r);
          row.eachCell((cell) => {
            cell.border = CELL_BORDER;
            cell.font = { name: "Tahoma", size: 9 };
            cell.alignment = { vertical: "middle", wrapText: true };
          });
        });
        ws.columns.forEach((col, i) => { col.width = i === 0 ? 14 : 22; });
      }

      async function downloadWorkbook(wb, filenamePrefix) {
        const buffer = await wb.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: "application/octet-stream" });
        const now = new Date();
        const stamp = `${now.getFullYear()}${pad(now.getMonth() + 1)}${pad(now.getDate())}_${pad(now.getHours())}${pad(now.getMinutes())}`;
        const url = URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = `${filenamePrefix}_${stamp}.xlsx`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      }

      async function exportTimelineDaily() {
        const jobs = getFilteredJobs();
        if (!jobs.length) { Swal.fire({ icon: "info", title: "ไม่มีข้อมูลสำหรับส่งออก", confirmButtonColor: "#006B9F" }); return; }
        const wb = new ExcelJS.Workbook();
        addDailyTimelineSheet(wb, jobs);
        addJobListSheet(wb, jobs);
        await downloadWorkbook(wb, "ไทม์ไลน์รายวัน");
      }

      async function exportTimelineTeam() {
        const jobs = getFilteredJobs();
        if (!jobs.length) { Swal.fire({ icon: "info", title: "ไม่มีข้อมูลสำหรับส่งออก", confirmButtonColor: "#006B9F" }); return; }
        const wb = new ExcelJS.Workbook();
        addTeamTimelineSheet(wb, jobs);
        addJobListSheet(wb, jobs);
        await downloadWorkbook(wb, "ไทม์ไลน์ทีม");
      }

      function bindExportButtons() {
        byId("btnExportTimelineDaily")?.addEventListener("click", exportTimelineDaily);
        byId("btnExportTimelineTeam")?.addEventListener("click", exportTimelineTeam);
      }

      // --- Zoom controls (ซูมเข้า/ออก ไทม์ไลน์) ---

      function updateZoomUI(type) {
        const idx = type === "daily" ? state.zoomDailyIdx : state.zoomTeamIdx;
        const label = type === "daily" ? els.zoomLabelDaily : els.zoomLabelTeam;
        const btnIn = type === "daily" ? els.btnZoomInDaily : els.btnZoomInTeam;
        const btnOut = type === "daily" ? els.btnZoomOutDaily : els.btnZoomOutTeam;
        if (label) label.textContent = `${Math.round(ZOOM_STEPS[idx] * 100)}%`;
        if (btnIn) btnIn.disabled = idx >= ZOOM_STEPS.length - 1;
        if (btnOut) btnOut.disabled = idx <= 0;
      }

      function setZoom(type, newIdx, container, anchorClientX) {
        const clampedIdx = Math.max(0, Math.min(ZOOM_STEPS.length - 1, newIdx));
        const key = type === "daily" ? "zoomDailyIdx" : "zoomTeamIdx";
        if (state[key] === clampedIdx) return;

        // จำตำแหน่งสัดส่วนการเลื่อนสกอลล์ไว้ เพื่อให้จุดที่มองอยู่คงที่ตอนซูม
        let ratio = 0;
        if (container) {
          const rect = container.getBoundingClientRect();
          const anchorX = (anchorClientX ?? rect.left + rect.width / 2) - rect.left + container.scrollLeft;
          ratio = container.scrollWidth > 0 ? anchorX / container.scrollWidth : 0;
        }

        state[key] = clampedIdx;
        const jobs = getFilteredJobs();
        if (type === "daily") renderTimelineDaily(jobs); else renderTimelineTeam(jobs);

        if (container) {
          requestAnimationFrame(() => {
            const newAnchorX = ratio * container.scrollWidth;
            container.scrollLeft = newAnchorX - (anchorClientX !== undefined ? (anchorClientX - container.getBoundingClientRect().left) : container.clientWidth / 2);
          });
        }
      }

      function changeZoom(type, direction, container, anchorClientX) {
        const key = type === "daily" ? "zoomDailyIdx" : "zoomTeamIdx";
        setZoom(type, state[key] + direction, container, anchorClientX);
      }

      function resetZoom(type, container) {
        setZoom(type, ZOOM_STEPS.indexOf(1), container);
      }

      function initZoomControls() {
        const configs = [
          { type: "daily", inBtn: els.btnZoomInDaily, outBtn: els.btnZoomOutDaily, resetBtn: els.btnZoomResetDaily, container: els.timelineDailyContainer },
          { type: "team", inBtn: els.btnZoomInTeam, outBtn: els.btnZoomOutTeam, resetBtn: els.btnZoomResetTeam, container: els.timelineTeamContainer },
        ];
        configs.forEach(({ type, inBtn, outBtn, resetBtn, container }) => {
          if (inBtn) inBtn.addEventListener("click", () => changeZoom(type, 1, container));
          if (outBtn) outBtn.addEventListener("click", () => changeZoom(type, -1, container));
          if (resetBtn) resetBtn.addEventListener("click", () => resetZoom(type, container));
          if (container) {
            // รองรับ Ctrl/⌘ + เลื่อนล้อเมาส์เพื่อซูมเข้า-ออก
            container.addEventListener(
              "wheel",
              (e) => {
                if (!(e.ctrlKey || e.metaKey)) return;
                e.preventDefault();
                changeZoom(type, e.deltaY < 0 ? 1 : -1, container, e.clientX);
              },
              { passive: false }
            );
          }
        });
      }

      // --- End Timeline Logic ---

      function calcStats(jobs) {
        const total = jobs.length;
        const completed = jobs.filter((j) => j.status === "completed").length;
        const cencel = jobs.filter((j) => j.status === "cencel").length;
        const pending = jobs.filter((j) => j.status === "pending").length;
        const inProgress = jobs.filter((j) => j.status === "in_progress").length;
        const totalHours = jobs.reduce((sum, j) => {
          if (!j.workEnd || !j.workStart) return sum;
          const start = new Date(j.workStart);
          const end = new Date(j.workEnd);
          if (isNaN(start.getTime()) || isNaN(end.getTime()) || end < start) return sum;
          return sum + (end - start) / 36e5;
        }, 0);
        const avgHours = total ? totalHours / total : 0;
        const onTime = completed; // Simplified for demo
        const efficiency = total ? Math.round(((completed * 0.55 + onTime * 0.35 + Math.max(0, total - cencel) * 0.1) / total) * 100) : 0;
        const avgRating = 0;
        return { total, completed, cencel, pending, inProgress, totalHours, avgHours, onTime, efficiency, avgRating };
      }

      function renderKPIs(jobs) {
        const s = calcStats(jobs);
        const cards = [
          { label: "งานทั้งหมด", value: s.total, icon: "briefcase", hint: "รายการที่กรองอยู่", color: "from-indigo-500 to-blue-500", iconColor: "text-indigo-500" },
          { label: "เสร็จสิ้น", value: s.completed, icon: "check-circle-2", hint: "ปิดงานเรียบร้อย", color: "from-emerald-500 to-teal-500", iconColor: "text-emerald-500" },
          { label: "ตรงเวลา", value: `${s.onTime}/${s.total}`, icon: "clock", hint: "เสร็จก่อนกำหนด", color: "from-sky-500 to-cyan-500", iconColor: "text-sky-500" },
          { label: "ชม.ทำงาน", value: s.totalHours.toFixed(1), icon: "timer", hint: `เฉลี่ย ${s.avgHours.toFixed(1)} ชม./งาน`, color: "from-amber-500 to-orange-500", iconColor: "text-amber-500" },
          { label: "คะแนนเฉลี่ย", value: s.avgRating ? s.avgRating.toFixed(1) : "-", icon: "star", hint: "จากผู้แจ้งซ่อม", color: "from-fuchsia-500 to-pink-500", iconColor: "text-fuchsia-500" },
        ];

        els.kpiCards.innerHTML = cards.map((c) => `
          <div class="bg-white rounded-xl p-3 shadow-card border border-slate-100 overflow-hidden relative">
            <div class="absolute -right-4 -top-4 h-12 w-12 bg-gradient-to-br ${c.color} opacity-10 rounded-full"></div>
            <div class="flex items-start justify-between relative">
              <div>
                <p class="text-[11px] font-medium text-slate-500">${c.label}</p>
                <h3 class="mt-0.5 text-lg xs:text-xl font-bold text-slate-950 leading-none">${c.value}</h3>
                <p class="mt-1 text-[10px] text-slate-400">${c.hint}</p>
              </div>
              <div class="p-1.5 rounded-lg bg-slate-50 border border-slate-100">
                 <i data-lucide="${c.icon}" class="w-4 h-4 ${c.iconColor}"></i>
              </div>
            </div>
          </div>
        `).join("");

        els.scoreBadge.textContent = `${s.efficiency}%`;
        if (window.lucide) lucide.createIcons();
      }

      // ======================================================================
      // กลับมาใช้ปฏิทินแบบ Original 100% (Day, Month, Year View)
      // ======================================================================

      function renderMonthEventPill(job) {
        const s = mockData.status[job.status] || { className: 'bg-slate-100 text-slate-800' }; 
        return `<button onclick="openJobDetail('${job.id}')" class="w-full text-left border ${s.className} rounded-xl px-2 py-1.5 mb-1 text-[11px] xs:text-xs leading-tight truncate hover:opacity-80 transition-opacity"><span class="font-semibold">${fmtTime(job.workStart)}</span> ${job.title}</button>`;
      }

      function jobBadge(job, compact = false) {
        const s = mockData.status[job.status] || { className: 'bg-slate-100 text-slate-800' };
        const tech = getJobTech(job);
        return `<button onclick="openJobDetail('${job.id}')" class="event-pill w-full text-left rounded-xl border ${s.className} px-2 py-1.5 mb-1 text-[11px] xs:text-xs leading-tight">
          <div class="font-semibold truncate">${fmtTime(job.workStart)} ${job.title}</div>
          ${compact ? "" : `<div class="opacity-80 truncate">${tech.name}</div>`}
        </button>`;
      }

      function renderCalendar(jobs) {
        els.monthCalendarWrap.classList.toggle("hidden", state.view !== "month");
        els.dayTimelineWrap.classList.toggle("hidden", state.view !== "day");
        els.yearCalendarWrap.classList.toggle("hidden", state.view !== "year");
        
        if (state.view === "month") renderMonthCalendar(jobs);
        if (state.view === "day") renderDayTimeline(jobs);
        if (state.view === "year") renderYearGrid(jobs);
      }

      function renderMonthCalendar(jobs) {
        const year = state.startDate.getFullYear();
        const month = state.startDate.getMonth();
        const first = new Date(year, month, 1);
        const startDay = first.getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const cells = [];
        
        for (let i = 0; i < startDay; i++) {
          cells.push(`<div class="calendar-day border-b border-r border-slate-100 bg-slate-50/60"></div>`);
        }
        
        for (let day = 1; day <= daysInMonth; day++) {
          const d = new Date(year, month, day);
          const currentYMD = toYMD(d);
          const dayJobs = jobs.filter((j) => toYMD(new Date(j.workStart)) === currentYMD);
          dayJobs.sort((a, b) => new Date(a.workStart) - new Date(b.workStart));
          const isToday = currentYMD === toYMD(new Date()); 
          cells.push(`
            <div class="calendar-day border-b border-r border-slate-100 p-2 bg-white hover:bg-slate-50/70 transition overflow-hidden">
              <div class="flex items-center justify-between mb-1.5">
                <span class="${isToday ? "bg-indigo-600 text-white" : "text-slate-500"} h-7 w-7 rounded-full inline-flex items-center justify-center text-sm font-semibold">${day}</span>
                ${dayJobs.length ? `<span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">${dayJobs.length}</span>` : ""}
              </div>
              <div class="flex flex-col gap-0.5 max-h-[86px] overflow-visible">
                ${dayJobs.slice(0, 2).map((j) => renderMonthEventPill(j)).join("")}
                ${dayJobs.length > 2 ? `<button onclick="openDayJobs('${currentYMD}')" class="text-[11px] text-indigo-600 font-medium mt-0.5 w-full text-left truncate hover:underline">+ อีก ${dayJobs.length - 2} งาน</button>` : ""}
              </div>
            </div>
          `);
        }
        
        const totalCells = Math.ceil((startDay + daysInMonth) / 7) * 7;
        for (let i = startDay + daysInMonth; i < totalCells; i++) {
          cells.push(`<div class="calendar-day border-b border-r border-slate-100 bg-slate-50/60"></div>`);
        }
        els.monthCalendar.innerHTML = cells.join("");
      }

      function renderDayTimeline(jobs) {
        const hours = Array.from({ length: 24 }, (_, i) => i);
        els.dayTimeline.innerHTML = hours
          .map((h) => {
            const hourJobs = jobs.filter((j) => new Date(j.workStart).getHours() === h);
            return `<div class="flex items-start gap-3 mb-2.5">
        <div class="w-10 shrink-0 pt-3.5 text-[13px] font-bold text-slate-400">${pad(h)}:00</div>
        <div class="flex-1 min-h-[54px] rounded-2xl bg-slate-50 border border-slate-100 p-3.5">
          ${
            hourJobs.length
              ? hourJobs.map((j) => `<div class="mb-1.5 last:mb-0">${jobBadge(j, true)}</div>`).join("")
              : '<span class="text-[13px] font-medium text-slate-300 block">ว่าง</span>'
          }
        </div>
      </div>`;
          })
          .join("");
      }

      function renderYearGrid(jobs) {
        const months = Array.from({ length: 12 }, (_, m) => new Date(state.startDate.getFullYear(), m, 1));
        els.yearGrid.innerHTML = months
          .map((mDate) => {
            const mJobs = jobs.filter((j) => {
              const jobStart = new Date(j.workStart);
              const jobEnd = j.workEnd ? new Date(j.workEnd) : jobStart;
              const monthStart = new Date(mDate.getFullYear(), mDate.getMonth(), 1);
              const monthEnd = new Date(mDate.getFullYear(), mDate.getMonth() + 1, 0, 23, 59, 59);
              return jobStart <= monthEnd && jobEnd >= monthStart;
            });
            const stats = calcStats(mJobs);
            const monthName = new Intl.DateTimeFormat("th-TH", { month: "long" }).format(mDate);
            const topTypes = Object.entries(mJobs.reduce((acc, j) => { acc[j.type] = (acc[j.type] || 0) + 1; return acc; }, {})).sort((a, b) => b[1] - a[1]).slice(0, 2);
            
            return `<button onclick="switchToMonth(${mDate.getMonth()})" class="text-left rounded-[24px] bg-slate-50 hover:bg-white border border-slate-100 hover:shadow-card p-5 transition">
      <div class="flex items-center justify-between gap-2">
        <h3 class="font-bold text-slate-950">${monthName}</h3>
        <span class="px-2.5 py-1 rounded-full bg-white text-slate-500 text-xs font-medium">${mJobs.length} งาน</span>
      </div>
      <div class="mt-4 grid grid-cols-3 gap-2 text-center">
        <div class="rounded-2xl bg-white p-2"><div class="text-lg font-bold text-emerald-600">${stats.completed}</div><div class="text-[11px] text-slate-400">เสร็จ</div></div>
        <div class="rounded-2xl bg-white p-2"><div class="text-lg font-bold text-rose-600">${stats.cencel}</div><div class="text-[11px] text-slate-400">เกิน</div></div>
        <div class="rounded-2xl bg-white p-2"><div class="text-lg font-bold text-indigo-600">${stats.efficiency}</div><div class="text-[11px] text-slate-400">คะแนน</div></div>
      </div>
      <div class="mt-3 text-xs text-slate-500 min-h-5">${topTypes.length ? "งานหลัก: " + topTypes.map((t) => t[0] + " " + t[1]).join(", ") : "ไม่มีงาน"}</div>
    </button>`;
          }).join("");
      }

      function switchToMonth(month) {
        state.view = "month";
        state.startDate = new Date(state.startDate.getFullYear(), month, 1);
        state.endDate = new Date(state.startDate.getFullYear(), month + 1, 0);
        syncDateInput();
        render();
      }

      function openDayJobs(ymd) {
        const jobs = getFilteredJobs().filter((j) => toYMD(new Date(j.workStart)) === ymd);
        fireSwal({
          title: `ตารางงานวันที่ ${fmtDate(ymd + "T00:00:00")}`,
          html: `<div class="text-left space-y-2.5 p-1 max-h-[60vh] overflow-y-auto overflow-x-hidden pr-1.5 overscroll-contain">${jobs
            .map((j) => `
              <button onclick="Swal.close(); setTimeout(()=>openJobDetail('${j.id}', '${ymd}'),120)" 
                      class="group w-full flex flex-col p-3.5 bg-white border border-slate-200 rounded-2xl shadow-[0_2px_8px_-4px_rgba(0,0,0,0.1)] hover:shadow-md hover:border-blue-400 transition-all duration-300 text-left relative overflow-hidden">
                <div class="flex justify-between items-start w-full mb-3 gap-2">
                  <div class="flex-1 overflow-hidden">
                    <h4 class="text-sm font-semibold text-slate-800 truncate group-hover:text-blue-600 transition-colors">${j.title}</h4>
                    <div class="flex items-center text-xs text-slate-500 mt-1.5 font-medium">
                      <i data-lucide="clock" class="w-3.5 h-3.5 mr-1 text-slate-400"></i>
                      ${fmtTime(j.workStart)} - ${j.workEnd ? fmtTime(j.workEnd) : ''}
                    </div>
                  </div>
                  <span class="shrink-0 px-2.5 py-1 rounded-lg text-[10px] font-bold border shadow-sm ${mockData.status[j.status]?.className || ''}">
                    ${mockData.status[j.status]?.label || j.status}
                  </span>
                </div>
                <div class="flex justify-between items-center w-full pt-2.5 border-t border-slate-100">
                  <div class="text-xs font-semibold text-slate-700 flex items-center">
                    <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-blue-100 to-blue-50 text-blue-600 flex items-center justify-center mr-2 text-[10px] border border-blue-100">
                      ${getJobTech(j).name.charAt(0)}
                    </div>
                    ${getJobTech(j).name}
                  </div>
                  <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 group-hover:text-blue-500 group-hover:translate-x-1 transition-all duration-300"></i>
                </div>
              </button>
            `).join("")}</div>`,
          width: 680,
          showCloseButton: true,
          showConfirmButton: false,
          customClass: {
            popup: 'rounded-[2rem] p-4',
            title: 'text-xl font-bold text-slate-800 pb-2',
            closeButton: 'focus:outline-none hover:bg-slate-100 rounded-full transition-colors',
            htmlContainer: '!overflow-hidden'
          },
          didOpen: () => lucide.createIcons(),
        });
      }

      function renderKanban(jobs) {
        const columns = [
          { id: "pending", title: "รอ/เตรียมการ", color: "amber", bgHeader: "bg-amber-100/50", border: "border-amber-200", text: "text-amber-800" },
          { id: "in_progress", title: "กำลังดำเนินการ", color: "sky", bgHeader: "bg-sky-100/50", border: "border-sky-200", text: "text-sky-800" },
          { id: "completed", title: "เสร็จสิ้น", color: "emerald", bgHeader: "bg-emerald-100/50", border: "border-emerald-200", text: "text-emerald-800" },
          { id: "cencel", title: "ยกเลิก", color: "rose", bgHeader: "bg-rose-100/50", border: "border-rose-200", text: "text-rose-800" },
        ];
        let html = "";
        columns.forEach((col) => {
          const colJobs = jobs.filter((j) => j.status === col.id);
          html += `
            <div class="kanban-col bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden flex flex-col">
                <div class="p-3 border-b ${col.border} ${col.bgHeader} flex items-center justify-between sticky top-0 z-10">
                    <h3 class="font-bold text-sm ${col.text}">${col.title}</h3>
                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-white text-xs font-bold shadow-sm ${col.text}">${colJobs.length}</span>
                </div>
                <div class="kanban-cards-container p-2 space-y-2 bg-slate-50/50 scrollbar-thin">
                    ${colJobs.length > 0 ? colJobs.map((job) => createKanbanCard(job)).join("") : `<div class="p-4 text-center border-2 border-dashed border-slate-200 rounded-lg text-slate-400 text-xs">ไม่มีงานในสถานะนี้</div>`}
                </div>
            </div>
        `;
        });
        els.kanbanContainer.innerHTML = html;
        if (window.lucide) lucide.createIcons();
      }

      function createKanbanCard(job) {
        const tech = getJobTech(job);
        let priorityText = "", priorityColor = "text-slate-500 bg-slate-100";
        if (job.priority === "critical") { priorityText = "เร่งด่วน"; priorityColor = "text-amber-600 bg-amber-50 border border-amber-100"; }
        return `
        <button onclick="openJobDetail('${job.id}')" class="w-full text-left bg-white p-3 rounded-lg border border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all cursor-pointer group">
            <div class="flex justify-between items-start mb-1.5 gap-2">
                <span class="text-[10px] font-medium text-slate-400 group-hover:text-indigo-500 transition-colors">${job.id}</span>
                <span class="text-[9px] px-1.5 py-0.5 rounded-md font-medium ${priorityColor}">${priorityText || job.priority}</span>
            </div>
            <h4 class="text-[13px] font-semibold text-slate-800 leading-tight mb-1.5 line-clamp-2">${job.title}</h4>
            <div class="flex flex-col gap-1 mb-2.5">
                <p class="text-[11px] text-slate-500 truncate flex items-center gap-1"><i data-lucide="map-pin" class="w-3 h-3 shrink-0"></i> ${job.location}</p>
                <p class="text-[11px] text-slate-500 truncate flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3 shrink-0"></i> ${fmtDate(job.workStart, { time: true })}</p>
            </div>
            <div class="flex items-center gap-2 pt-2.5 border-t border-slate-100">
                ${renderAvatarLabel(tech, "w-5 h-5 rounded-md object-cover border border-slate-200 text-xs")}
                <div class="text-[11px] font-medium text-slate-600 truncate flex-1">${tech.name}</div>
            </div>
        </button>`;
      }

      function renderTechJobsChart(jobs) {
        const ctx = byId("techJobsChart").getContext("2d");
        const techCounts = {};
        const techsToDisplay = state.technicianId === "all" ? mockData.technicians.filter((t) => t.id !== "all") : mockData.technicians.filter((t) => t.id === state.technicianId);
        techsToDisplay.forEach((tech) => { techCounts[tech.name] = 0; });
        jobs.forEach((job) => {
          const tech = mockData.technicians.find((t) => t.id === job.technicianId);
          if (tech && techCounts[tech.name] !== undefined) techCounts[tech.name] += 1;
        });

        if (techChart) techChart.destroy();
        techChart = new Chart(ctx, {
          type: "bar", plugins: [ChartDataLabels],
          data: { labels: Object.keys(techCounts), datasets: [{ label: "จำนวนงาน", data: Object.values(techCounts), backgroundColor: "#006B9F", borderRadius: 4 }] },
          options: {
            responsive: true, maintainAspectRatio: false, layout: { padding: { top: 18 } },
            scales: { 
              x: {
                ticks: {
                  autoSkip: false,    // บังคับไม่ให้ Chart.js ซ่อนชื่อ
                  maxRotation: 45,    // เอียงข้อความ 45 องศา
                  minRotation: 45,    // บังคับให้เอียง 45 องศาเป็นค่าเริ่มต้นเลย
                  font: {
                    family: "Prompt",
                    size: 8         // ปรับขนาดฟอนต์ให้เล็กลงเล็กน้อยเพื่อประหยัดพื้นที่
                  }
                }
              },
              y: { beginAtZero: true, ticks: { precision: 0 } } 
            },
            plugins: { legend: { display: false }, datalabels: { anchor: "end", align: "top", offset: 2, color: "#004a6f", font: { family: "Prompt", weight: "600", size: 11 }, formatter: (value) => (value > 0 ? value : "") } }
          }
        });
      }

      function renderChart(jobs) {
        const grouped = jobs.reduce((acc, j) => { acc[j.status] = (acc[j.status] || 0) + 1; return acc; }, {});
        const ctx = byId("performanceChart");
        if (chart) chart.destroy();
        chart = new Chart(ctx, {
          type: "doughnut", plugins: [ChartDataLabels],
          data: {
            labels: ["เสร็จสิ้น", "กำลังดำเนินการ", "รอดำเนินการ", "ยกเลิก"],
            datasets: [{ data: [grouped.completed || 0, grouped.in_progress || 0, grouped.pending || 0, grouped.cencel || 0], borderWidth: 0, hoverOffset: 8, backgroundColor: ["#10b981", "#0ea5e9", "#f59e0b", "#f43f5e"] }]
          },
          options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom", labels: { font: { family: "Prompt" }, usePointStyle: true, padding: 18 } }, datalabels: { color: "#fff", font: { family: "Prompt", weight: "600", size: 12 }, formatter: (value) => (value > 0 ? value : "") } }, cutout: "68%" }
        });
      }

      function updateLegends(jobs) {
        const grouped = jobs.reduce((acc, j) => { acc[j.status] = (acc[j.status] || 0) + 1; return acc; }, {});
        Object.keys(mockData.status).forEach((k) => { const el = byId(`legend-${k}`); if (el) el.textContent = grouped[k] || 0; });
      }

      function formatDurationText(diffMs) {
        if (!(diffMs > 0)) return "-";
        const totalMinutes = Math.floor(diffMs / 60000);
        const days = Math.floor(totalMinutes / (60 * 24));
        const hours = Math.floor((totalMinutes % (60 * 24)) / 60);
        const mins = totalMinutes % 60;
        const parts = [];
        if (days > 0) parts.push(`${days} วัน`);
        if (hours > 0) parts.push(`${hours} ชม.`);
        if (mins > 0) parts.push(`${mins} นาที`);
        return parts.length ? parts.join(" ") : "น้อยกว่า 1 นาที";
      }

      window.__carouselData = window.__carouselData || {};
      function moveCarouselImage(carouselId, direction) {
        const data = window.__carouselData[carouselId];
        const wrap = document.getElementById(carouselId);
        if (!data || !wrap) return;
        const total = data.imgs.length;
        data.index = (data.index + direction + total) % total;
        const imgEl = wrap.querySelector(".carousel-img");
        const counterEl = wrap.querySelector(".carousel-counter");
        if (imgEl) imgEl.src = data.imgs[data.index];
        if (counterEl) counterEl.textContent = `${data.index + 1} / ${total}`;
      }
      window.moveCarouselImage = moveCarouselImage;

      function renderImageSection(title, imgs, sectionKey) {
        if (!imgs || imgs.length === 0) {
          return `<div><h4 class="font-semibold text-sm text-slate-950 mb-1.5">${title}</h4><div class="rounded-lg bg-slate-50 border border-dashed border-slate-200 p-4 text-xs text-slate-400 text-center">ไม่มีรูปภาพ</div></div>`;
        }
        if (imgs.length === 1) {
          return `<div><h4 class="font-semibold text-sm text-slate-950 mb-1.5">${title}</h4><img src="${imgs[0]}" class="rounded-lg border border-slate-100 w-full h-48 object-cover" alt="${title}"></div>`;
        }
        const carouselId = `carousel_${sectionKey}`;
        window.__carouselData[carouselId] = { imgs, index: 0 };
        return `<div>
      <h4 class="font-semibold text-sm text-slate-950 mb-1.5">${title}</h4>
      <div id="${carouselId}" class="relative rounded-lg overflow-hidden border border-slate-100 bg-slate-50">
        <img src="${imgs[0]}" class="carousel-img w-full h-48 object-cover" alt="${title}">
        <button type="button" onclick="moveCarouselImage('${carouselId}', -1)" class="absolute left-1.5 top-1/2 -translate-y-1/2 h-7 w-7 rounded-full bg-black/50 text-white text-sm flex items-center justify-center hover:bg-black/70">‹</button>
        <button type="button" onclick="moveCarouselImage('${carouselId}', 1)" class="absolute right-1.5 top-1/2 -translate-y-1/2 h-7 w-7 rounded-full bg-black/50 text-white text-sm flex items-center justify-center hover:bg-black/70">›</button>
        <div class="carousel-counter absolute bottom-1.5 right-1.5 text-[10px] bg-black/60 text-white px-1.5 py-0.5 rounded-full">1 / ${imgs.length}</div>
      </div>
    </div>`;
      }

      function openJobDetail(jobId, fromDayYmd) {
        const job = mockData.jobs.find((j) => j.id === jobId);
        if (!job) return;
        const tech = getJobTech(job);
        const status = mockData.status[job.status] || { label: job.status, className: '' };
        
        let durationText = "-";
        if (job.workStart && job.workEnd) {
          const diffMs = new Date(job.workEnd) - new Date(job.workStart);
          durationText = formatDurationText(diffMs);
        } else if (job.workDuration) {
          durationText = job.workDuration;
        }

        const stockHtml = (job.stockUsed && job.stockUsed.length)
          ? `<div class="max-h-48 overflow-y-auto overflow-x-auto rounded-lg border border-slate-100"><table class="w-full text-xs border-collapse">
        <thead class="sticky top-0"><tr class="bg-slate-50 text-slate-500"><th class="text-left px-2 py-1.5 rounded-l-lg">รหัส</th><th class="text-left px-2 py-1.5">อุปกรณ์</th><th class="text-right px-2 py-1.5 rounded-r-lg">จำนวน</th></tr></thead>
        <tbody>${job.stockUsed.map((i) => `<tr class="border-b border-slate-100"><td class="px-2 py-1.5">${i.itemCode}</td><td class="px-2 py-1.5">${i.itemName}</td><td class="px-2 py-1.5 text-right">${i.qty} ${i.unit}</td></tr>`).join("")}</tbody>
      </table></div>`
          : '<div class="rounded-lg bg-slate-50 p-2.5 text-xs text-slate-500">ยังไม่มีการเบิกใช้อุปกรณ์จากสต็อก</div>';

        const jobIdSafe = String(job.id).replace(/[^a-zA-Z0-9_-]/g, "_");

        const backButtonHtml = fromDayYmd
          ? `<button onclick="Swal.close(); setTimeout(()=>openDayJobs('${fromDayYmd}'),120)" class="shrink-0 h-7 w-7 rounded-full hover:bg-slate-100 flex items-center justify-center text-slate-500 hover:text-slate-800 transition-colors" title="กลับไปยังรายการงานวันนี้">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
      </button>`
          : "";

        fireSwal({
          title: `<div class="text-left flex items-center gap-1.5">${backButtonHtml}<div><div class="text-xs text-slate-400 font-normal leading-tight">${job.id}</div><div class="text-base mt-0.5">${job.title}</div></div></div>`,
          html: `
      <div class="text-left grid grid-cols-1 sm:grid-cols-2 gap-5"> 
        <div class="space-y-3">
          <div class="grid grid-cols-1 gap-2 text-xs">
            <div class="rounded-xl border border-slate-100 p-2.5"><div class="text-[11px] text-slate-400 mb-0.5">วันที่แจ้งซ่อม</div><div class="font-medium">${fmtDate(job.reportDate, { time: true })}</div></div>
            <div class="rounded-xl border border-slate-100 p-2.5"><div class="text-[11px] text-slate-400 mb-0.5">ชื่อผู้แจ้ง / เบอร์โทร</div><div class="font-medium">${job.reporter} • ${job.phone}</div></div>
            <div class="rounded-xl border border-slate-100 p-2.5"><div class="text-[11px] text-slate-400 mb-0.5">สถานที่</div><div class="font-medium">${job.location}</div></div>
          </div>
          <div class="rounded-xl bg-white border border-slate-100 p-3"><h4 class="font-semibold text-sm text-slate-950">รายละเอียด/สาเหตุที่แจ้งซ่อม</h4><p class="mt-1 text-xs text-slate-600">${job.issueDetail}</p></div>
          ${renderImageSection("รูปภาพที่แจ้งซ่อม", job.beforeImages, `${jobIdSafe}_before`)}
        </div>
        <div class="space-y-3">
          <div class="flex flex-col xs:flex-row gap-2 xs:items-center xs:justify-between rounded-xl bg-slate-50 p-2.5">
            <div class="flex items-center gap-2.5">${renderAvatarLabel(tech, "h-11 w-11 rounded-2xl text-sm")}<div><div class="text-sm font-semibold text-slate-950 leading-tight">${tech.name}</div><div class="text-[11px] text-slate-500 mt-0.5">${tech.role}</div></div></div>
            <span class="inline-flex w-fit px-2.5 py-1 rounded-full border text-[11px] font-medium ${status.className}">${status.label}</span>
          </div>
          <div class="rounded-xl border border-slate-100 p-2.5 text-xs flex justify-between items-center bg-white shadow-sm">
            <div>
              <div class="text-[11px] text-slate-400 mb-0.5">วันที่ช่างเข้าดำเนินการ</div><div class="font-medium text-slate-700 mb-2">${fmtDate(job.workStart, { time: true })}</div>
              <div class="text-[11px] text-slate-400 mb-0.5">วันที่ดำเนินการสำเร็จ</div><div class="font-medium text-slate-700">${job.workEnd ? fmtDate(job.workEnd, { time: true }) : '-'}</div>
            </div>
            <div class="text-right pl-3 border-l border-slate-100"><div class="text-[11px] text-slate-400 mb-0.5">ใช้เวลาทำงาน</div><div class="font-semibold text-indigo-600">${durationText}</div></div>
          </div>
          <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-3"><h4 class="font-semibold text-sm text-emerald-900">วิธีที่ช่างแก้ไขปัญหา</h4><p class="mt-1 text-xs text-emerald-800">${job.solution ? job.solution : '-'}</p></div>
          ${renderImageSection("รูปภาพหลังแก้ไข", job.afterImages, `${jobIdSafe}_after`)}
          <div><h4 class="font-semibold text-sm text-slate-950 mb-1.5">ข้อมูลอุปกรณ์ที่นำไปใช้จริง</h4>${stockHtml}</div>
        </div>
      </div>`,
          width: 1000,
          showCloseButton: true,
          showConfirmButton: false,
          didOpen: () => lucide.createIcons(),
        });
      }
      
      window.openJobDetail = openJobDetail;
      window.switchToMonth = switchToMonth;
      window.openDayJobs = openDayJobs;
      init();
    </script>
  </body>
</html>