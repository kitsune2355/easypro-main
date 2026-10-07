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
      .timeline-row {
        display: grid;
        grid-template-columns: 56px 1fr;
        gap: 8px;
      }

      .timetable-shell {
        overflow: auto;
        max-height: calc(100vh - 260px);
        min-height: 320px;
      }
      .timetable {
        min-width: 1120px;
        border-collapse: separate;
        border-spacing: 0;
      }
      .timetable th,
      .timetable td {
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
      }
      .timetable th:first-child,
      .timetable td:first-child {
        border-left: 1px solid #e2e8f0;
      }
      .timetable thead th {
        border-top: 1px solid #e2e8f0;
        position: sticky;
        top: 0;
        z-index: 15;
      }
      .timetable td {
        height: 72px;
        vertical-align: top;
        background: #fff;
      }
      .timetable .sticky-day {
        position: sticky;
        left: 0;
        z-index: 12;
        background: #f8fafc;
      }
      .timetable thead th.sticky-day {
        z-index: 20;
      }

      .schedule-half-cell {
        width: 60px;
        min-width: 60px;
      }

      /*
       * ปิดเส้นแนวตั้งของทุกเซลล์ในส่วนตารางงานก่อน
       * แล้วเปิดกลับมาเฉพาะจุดสิ้นสุดของแต่ละชั่วโมง
       */
      .timetable tbody tr > td {
        border-left: 0 !important;
        border-right: 0 !important;
        box-shadow: none !important;
        outline: 0 !important;
      }

      .timetable tbody tr > td.sticky-day {
        border-left: 1px solid #e2e8f0 !important;
        border-right: 1px solid #e2e8f0 !important;
      }

      .timetable tbody tr > td.schedule-hour-end {
        border-right: 1px solid #e2e8f0 !important;
      }

      .timetable tbody tr > td.schedule-time-cell {
        background-color: #ffffff !important;
        background-image: none !important;
      }

      .schedule-card {
        transition: all 0.18s ease;
      }
      .schedule-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
      }

      .team-weekly-shell {
        overflow: auto;
        max-height: calc(100vh - 260px);
        min-height: 320px;
      }
      .team-weekly-table {
        min-width: 1180px;
        border-collapse: separate;
        border-spacing: 0;
      }
      .team-weekly-table th,
      .team-weekly-table td {
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
      }
      .team-weekly-table th:first-child,
      .team-weekly-table td:first-child {
        border-left: 1px solid #e2e8f0;
      }
      .team-weekly-table thead th {
        border-top: 1px solid #e2e8f0;
        position: sticky;
        top: 0;
        z-index: 15;
      }
      .team-weekly-table td {
        vertical-align: top;
        background: #fff;
        min-height: 100px;
      }
      .team-weekly-table .sticky-tech {
        position: sticky;
        left: 0;
        z-index: 14;
        background: #f8fafc;
      }
      .team-weekly-table tbody .sticky-tech {
        background: #ffffff;
      }
      .team-weekly-table thead th.sticky-tech {
        z-index: 20;
      }

      .tech-row-card {
        transition: all 0.18s ease;
      }
      .tech-row-card:hover {
        transform: translateY(-1px);
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
      .fullscreen-panel:fullscreen .timetable-shell,
      .fullscreen-panel:fullscreen .team-weekly-shell {
        max-height: calc(100vh - 170px);
      }
      /*
       * การ์ด "จำนวนงานรายช่าง" ตอนปกติกราฟสูงคงที่ 200px แต่พอกด
       * ขยายเต็มจอ (native Fullscreen) การ์ดขยายเป็น 100vh ทว่ากล่องกราฟ
       * ยังล็อกสูง 200px เดิมอยู่ เลยเหลือพื้นที่ว่างด้านล่างเยอะ
       * บังคับให้กล่องกราฟยืดเต็มพื้นที่ที่เหลือแทน
       */
      #techJobsCard.fullscreen-panel:fullscreen {
        display: flex;
        flex-direction: column;
      }
      #techJobsCard.fullscreen-panel:fullscreen #techJobsChartWrap {
        flex: 1;
        height: auto;
        min-height: 0;
      }
    </style>
  </head>
  <body class="font-prompt text-slate-800 text-sm">
    <div
      class="flex flex-col antialiased text-slate-700 h-screen w-full relative overflow-hidden"
    >
      <header
        class="w-full bg-white shadow-sm flex flex-col flex-none z-50"
      >
        <nav
          class="px-6 py-4 flex items-center justify-between border-b border-slate-200"
        >
          <div class="flex items-center gap-4">
            <div class="p-2 bg-[#006B9F] rounded-lg shadow-md text-white">
              <i data-lucide="hard-hat" class="w-6 h-6"></i>
            </div>
            <div>
              <h2
                class="text-lg font-bold text-slate-800 leading-tight"
                id="header-title"
              >
                Technician Work History
              </h2>
              <p class="text-xs text-slate-500 mt-1" id="header-subtitle">
                ติดตามและตรวจสอบประวัติงานช่าง
              </p>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              id="btnToday"
              class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50"
            >
              วันนี้
            </button>
            <button
              id="btnExport"
              class="rounded-xl bg-[var(--color-primary)] px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:brightness-105"
            >
              Export Excel
            </button>
          </div>
        </nav>

        <div class="border-b border-slate-200 px-6">
          <div class="flex overflow-x-auto hide-scrollbar">
            <button
              type="button"
              data-tab="calendar"
              class="tab-btn text-sm active flex items-center gap-1.5 px-3 py-3.5 whitespace-nowrap outline-none"
            >
              <i data-lucide="calendar" class="w-4 h-4"></i> ปฏิทินงาน
            </button>
            <button
              type="button"
              data-tab="kanban"
              class="tab-btn text-sm flex items-center gap-1.5 px-3 py-3.5 whitespace-nowrap outline-none"
            >
              <i data-lucide="kanban-square" class="w-4 h-4"></i> บอร์ดสถานะงาน
            </button>
            <button
              type="button"
              data-tab="schedule"
              class="tab-btn text-sm flex items-center gap-1.5 px-3 py-3.5 whitespace-nowrap outline-none"
            >
              <i data-lucide="list" class="w-4 h-4"></i> ตารางงานรายสัปดาห์
            </button>
            <button
              type="button"
              data-tab="team"
              class="tab-btn text-sm flex items-center gap-1.5 px-3 py-3.5 whitespace-nowrap outline-none"
            >
              <i data-lucide="users" class="w-4 h-4"></i>
              ตารางงานรายสัปดาห์แบบทีม
            </button>
          </div>
        </div>
      </header>

      <main class="relative z-10 px-4 pb-4 flex-1 min-h-0 overflow-y-auto scrollbar-thin">
        <section class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-4">
          <aside
            class="md:col-span-3 lg:col-span-2 space-y-4 md:sticky md:top-4 h-fit"
          >
            <div class="relative w-full" id="filterWrapper">
  
              <button
                id="filterToggleBtn"
                class="md:hidden w-full flex items-center justify-between bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 mb-2"
              >
                <div class="flex items-center gap-2">
                  <i data-lucide="filter" class="w-4 h-4 text-slate-500"></i>
                  <span>ตัวกรองข้อมูล</span>
                </div>
                <i data-lucide="chevron-down" id="filterChevron" class="w-4 h-4 text-slate-500 transition-transform duration-200"></i>
              </button>

              <div
                id="filterDropdown"
                class="hidden md:block absolute md:relative z-20 w-full left-0 mt-1 md:mt-0"
              >
                <div class="bg-white rounded-xl p-4 shadow-card border border-slate-100">
                  <h2 class="hidden md:block font-semibold text-base text-slate-950">
                    ตัวกรอง
                  </h2>
                  
                  <div class="mt-0 md:mt-3 space-y-3">
                    <div>
                      <label class="block text-xs font-medium text-slate-600 mb-1">ค้นหา</label>
                      <input
                        id="jobSearchInput"
                        type="text"
                        placeholder="ค้นหาเลขที่งาน / ชื่อผู้แจ้ง / รายละเอียด..."
                        class="w-full rounded-lg border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs outline-none focus:ring-2 focus:ring-indigo-500"
                      />
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-slate-600 mb-1">เลือกช่าง</label>
                      <select
                        id="technicianSelect"
                        class="w-full rounded-lg border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs outline-none focus:ring-2 focus:ring-indigo-500"
                      ></select>
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-slate-600 mb-1">มุมมอง</label>
                      <div class="grid grid-cols-3 gap-1.5 bg-slate-100 p-1 rounded-lg">
                        <button class="view-btn rounded-md text-xs font-medium" data-view="day">วัน</button>
                        <button class="view-btn rounded-md text-xs font-medium" data-view="month">เดือน</button>
                        <button class="view-btn rounded-md text-xs font-medium" data-view="year">ปี</button>
                      </div>
                    </div>
                    <div>
                      <label id="dateLabel" class="block text-xs font-medium text-slate-600 mb-1">เลือกเดือน</label>
                      <input
                        id="datePicker"
                        type="month"
                        class="w-full rounded-lg border-slate-200 bg-slate-50 px-2.5 py-1.5 text-xs outline-none focus:ring-2 focus:ring-indigo-500"
                      />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                      <button
                        id="btnPrev"
                        class="rounded-lg py-1.5 bg-slate-100 text-slate-700 text-xs font-medium hover:bg-slate-200"
                      >
                        ก่อนหน้า
                      </button>
                      <button
                        id="btnNext"
                        class="rounded-lg py-1.5 bg-slate-100 text-slate-700 text-xs font-medium hover:bg-slate-200"
                      >
                        ถัดไป
                      </button>
                    </div>
                    <button
                      id="btnReset"
                      class="w-full rounded-lg py-1.5 bg-indigo-50 text-indigo-700 text-xs font-semibold hover:bg-indigo-100"
                    >
                      ล้างตัวกรอง
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div
              class="bg-white rounded-xl p-4 shadow-card border border-slate-100"
            >
              <h2 class="font-semibold text-base text-slate-950">สถานะงาน</h2>
              <div class="mt-3 space-y-2 text-xs" id="statusLegend"></div>
            </div>
          </aside>

          <section class="md:col-span-9 lg:col-span-10">
            <div id="tab-calendar" class="tab-panel space-y-3">
              <div class="grid grid-cols-3 md:grid-cols-5 gap-2.5" id="kpiCards"></div>

              <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
                <div
                  class="lg:col-span-2 bg-white rounded-xl shadow-card border border-slate-100 overflow-hidden"
                >
                  <div
                    class="p-3 border-b border-slate-100 flex flex-col xs:flex-row xs:items-center xs:justify-between gap-2"
                  >
                    <div class="flex justify-between items-center gap-2">
                      <div>
                        <h2
                          id="calendarTitle"
                          class="text-base font-bold text-slate-950"
                        >
                          ปฏิทินงาน
                        </h2>
                        <p
                          id="calendarSubtitle"
                          class="text-[11px] text-slate-500 mt-0.5"
                        >
                          รายการงานของช่างในช่วงเวลาที่เลือก
                        </p>
                      </div>
                      <span
                        id="jobCountBadge"
                        class="px-2 py-1 rounded-full bg-slate-100 text-slate-700 font-medium"
                        >0 งาน</span
                      >
                    </div>
                  </div>

                  <div id="monthCalendarWrap" class="p-2.5">
                    <div
                      class="calendar-grid text-center text-[10px] xs:text-[11px] font-semibold text-slate-500 border-b border-slate-100 pb-1.5"
                    >
                      <div>อา.</div>
                      <div>จ.</div>
                      <div>อ.</div>
                      <div>พ.</div>
                      <div>พฤ.</div>
                      <div>ศ.</div>
                      <div>ส.</div>
                    </div>
                    <div id="monthCalendar" class="calendar-grid mt-1"></div>
                  </div>

                  <div id="dayTimelineWrap" class="hidden p-3">
                    <div id="dayTimeline" class="space-y-1.5"></div>
                  </div>

                  <div id="yearCalendarWrap" class="hidden p-3">
                    <div
                      id="yearGrid"
                      class="grid grid-cols-1 xs:grid-cols-2 lg:grid-cols-3 gap-2.5"
                    ></div>
                  </div>
                </div>

                <div class="space-y-3">
                  <div
                    class="bg-white rounded-xl shadow-card border border-slate-100 p-3"
                  >
                    <div class="flex items-start justify-between gap-2">
                      <div>
                        <h2 class="text-base font-bold text-slate-950">
                          ประสิทธิภาพ
                        </h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">
                          สรุปรายการตามข้อมูลที่กรอง
                        </p>
                      </div>
                      <span
                        id="scoreBadge"
                        class="px-2 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-semibold"
                        >0%</span
                      >
                    </div>
                    <div class="mt-3 h-40">
                      <canvas id="performanceChart"></canvas>
                    </div>
                  </div>

                  <div 
                    id="techJobsCard" 
                    class="fullscreen-panel bg-white rounded-xl shadow-card border border-slate-100 p-3 relative group/card flex flex-col"
                  >
                    <div class="flex items-center justify-between gap-2 mb-3">
                      <h2 class="text-base font-bold text-slate-950">
                        จำนวนงานรายช่าง
                      </h2>
                      
                      <button 
                        id="btnFullscreenTechJobs" 
                        type="button"
                        class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg"
                        title="ขยายเต็มจอ"
                      >
                        <i data-lucide="maximize-2" class="w-4 h-4"></i>
                      </button>
                    </div>

                    <div id="techJobsChartWrap" class="h-[200px]">
                      <canvas id="techJobsChart"></canvas>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div id="tab-kanban" class="tab-panel hidden">
              <div
                class="bg-white rounded-xl shadow-card border border-slate-100 overflow-hidden h-full flex flex-col"
              >
                <div
                  class="p-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"
                >
                  <div>
                    <h2
                      id="kanbanTitle"
                      class="text-base font-bold text-slate-950"
                    >
                      บอร์ดสถานะงาน (Kanban)
                    </h2>
                    <p
                      id="kanbanSubtitle"
                      class="text-[11px] text-slate-500 mt-0.5"
                    >
                      จัดกลุ่มงานตามสถานะในช่วงเวลาและช่างที่เลือก
                    </p>
                  </div>
                </div>

                <div class="p-4 flex-1 bg-slate-50/50">
                  <div
                    id="kanbanContainer"
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 h-full"
                  >
                    <!-- Columns will be injected by JavaScript -->
                  </div>
                </div>
              </div>
            </div>

            <div id="tab-schedule" class="tab-panel hidden">
              <div
                id="workSchedulePanel"
                class="fullscreen-panel bg-white rounded-xl shadow-card border border-slate-100 overflow-clip"
              >
                <div
                  class="p-3 border-b border-slate-100 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-2"
                >
                  <div>
                    <div class="flex items-center gap-2 mt-1.5">
                      <button
                        id="btnPrevWeekSchedule"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                      >
                        <i data-lucide="chevron-left" class="w-5 h-5"></i>
                      </button>
                      <h2
                        id="workScheduleTitle"
                        class="text-base xs:text-lg font-bold text-slate-950"
                      >
                        ตารางงานรายสัปดาห์ของช่าง
                      </h2>
                      <button
                        id="btnNextWeekSchedule"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                      >
                        <i data-lucide="chevron-right" class="w-5 h-5"></i>
                      </button>
                    </div>
                    <p
                      id="workScheduleSubtitle"
                      class="text-[11px] text-slate-500 mt-1"
                    >
                      เห็นภาพรวมว่าวันไหน ช่วงเวลาไหน ช่างทำงานอะไร
                      คลิกงานเพื่อดูรายละเอียด
                    </p>
                  </div>
                  <div class="flex flex-wrap gap-1.5">
                    <button
                      id="btnFullscreenSchedule"
                      type="button"
                      title="แสดงเต็มจอ"
                      aria-label="แสดงตารางงานเต็มจอ"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm hover:bg-slate-50 hover:text-slate-800"
                    >
                      <i data-lucide="maximize-2" class="h-4 w-4"></i>
                    </button>
                    <button
                      id="btnPrintSchedule"
                      type="button"
                      class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-slate-600 text-[11px] font-semibold shadow-sm hover:bg-slate-50 inline-flex items-center gap-1"
                    >
                      <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                      พิมพ์ตารางงาน
                    </button>
                    <button
                      id="btnExportSchedule"
                      class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white text-[11px] font-semibold shadow-sm hover:bg-indigo-700"
                    >
                      Export ตารางงาน
                    </button>
                  </div>
                </div>

                <div class="p-3 flex flex-col xl:flex-row xl:items-start gap-3">
                  <div
                    class="timetable-shell scrollbar-thin rounded-xl border border-slate-100 overflow-auto flex-1 min-w-0"
                  >
                    <table class="timetable w-full text-[11px]">
                      <thead id="workScheduleHead"></thead>
                      <tbody id="workScheduleBody"></tbody>
                    </table>
                  </div>
                  <div
                    id="workScheduleSummary"
                    class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-1 gap-2 xl:w-48 xl:shrink-0 xl:sticky xl:top-3 xl:self-start"
                  ></div>
                </div>
              </div>
            </div>

            <div id="tab-team" class="tab-panel hidden">
              <div
                id="teamWeeklyPanel"
                class="fullscreen-panel bg-white rounded-xl shadow-card border border-slate-100 overflow-clip"
              >
                <div
                  class="p-3 border-b border-slate-100 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-2"
                >
                  <div>
                    <div class="flex items-center gap-2 mt-1.5">
                      <button
                        id="btnPrevWeekTeam"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                      >
                        <i data-lucide="chevron-left" class="w-5 h-5"></i>
                      </button>
                      <h2
                        id="teamWeeklyTitle"
                        class="text-base xs:text-lg font-bold text-slate-950"
                      >
                        ตารางงานรายสัปดาห์ แยกตามช่าง
                      </h2>
                      <button
                        id="btnNextWeekTeam"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors"
                      >
                        <i data-lucide="chevron-right" class="w-5 h-5"></i>
                      </button>
                    </div>
                    <p
                      id="teamWeeklySubtitle"
                      class="text-[11px] text-slate-500 mt-1"
                    >สำหรับดูภาพรวมทั้งทีมและงานซ้อนในแต่ละวัน
                    </p>
                  </div>
                  <div class="flex flex-wrap gap-1.5">
                    <button
                      id="btnFullscreenTeamWeekly"
                      type="button"
                      title="แสดงเต็มจอ"
                      aria-label="แสดงตารางทีมเต็มจอ"
                      class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm hover:bg-slate-50 hover:text-slate-800"
                    >
                      <i data-lucide="maximize-2" class="h-4 w-4"></i>
                    </button>
                    <button
                      id="btnPrintTeamWeekly"
                      type="button"
                      class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-slate-600 text-[11px] font-semibold shadow-sm hover:bg-slate-50 inline-flex items-center gap-1"
                    >
                      <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                      พิมพ์ตารางทีม
                    </button>
                    <button
                      id="btnExportTeamWeekly"
                      class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white text-[11px] font-semibold shadow-sm hover:bg-emerald-700"
                    >
                      Export ตารางทีม
                    </button>
                  </div>
                </div>

                <div class="p-3 flex flex-col xl:flex-row xl:items-start gap-3">
                  <div
                    class="team-weekly-shell scrollbar-thin rounded-xl border border-slate-100 overflow-auto flex-1 min-w-0"
                  >
                    <table class="team-weekly-table w-full text-[11px]">
                      <thead id="teamWeeklyHead"></thead>
                      <tbody id="teamWeeklyBody"></tbody>
                    </table>
                  </div>
                  <div
                    id="teamWeeklySummary"
                    class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-1 gap-2 xl:w-48 xl:shrink-0 xl:sticky xl:top-3 xl:self-start"
                  ></div>
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
          // สลับคลาส hidden
          filterDropdown.classList.toggle("hidden");
          
          // หมุนไอคอนลูกศรเมื่อเปิด/ปิด
          if (filterDropdown.classList.contains("hidden")) {
            filterChevron.classList.remove("rotate-180");
          } else {
            filterChevron.classList.add("rotate-180");
          }
        });

        // (ตัวเลือกเสริม) ซ่อน Dropdown เมื่อคลิกพื้นที่อื่นบนหน้าจอในโหมดมือถือ
        document.addEventListener("click", (event) => {
          const isClickInside = filterToggleBtn.contains(event.target) || filterDropdown.contains(event.target);
          // ตรวจสอบว่าหน้าจอเล็ก (มือถือ) และคลิกข้างนอก
          if (!isClickInside && window.innerWidth < 768 && !filterDropdown.classList.contains("hidden")) {
            filterDropdown.classList.add("hidden");
            filterChevron.classList.remove("rotate-180");
          }
        });
      }

      lucide.createIcons();

      function createHourlyWorkSlots() {
        return Array.from({ length: 24 }, (_, index) => {
          const startHour = String(index).padStart(2, "0");
          const endHour = String(index + 1).padStart(2, "0");
          return {
            no: index + 1,
            start: `${startHour}:00`,
            end: `${endHour}:00`,
            label: `${startHour}.00-${endHour}.00`,
          };
        });
      }

      const mockData = {
        technicians: [
          {
            id: "all",
            name: "ช่างทั้งหมด",
            role: "รวมทุกทีม",
            avatar: "https://placehold.co/120x120/e0e7ff/3730a3?text=ALL",
          },
          {
            id: "unassigned",
            name: "ยังไม่มอบหมายช่าง",
            role: "งานที่ยังไม่ได้มอบหมายช่าง (เช่น รอดำเนินการ/ยกเลิก)",
            avatar: "https://placehold.co/120x120/fef3c7/b45309?text=%3F",
          },
          // ช่างที่เหลือจะถูกดึงมาจาก API (handle_work_history.php?action=get_technicians)
          // ผ่านฟังก์ชัน loadDataFromApi() ด้านล่าง แล้ว push เข้ามาต่อจากนี้
        ],
        status: {
          pending: {
            label: "รอดำเนินการ",
            color: "amber",
            className: "bg-amber-50 text-amber-700 border-amber-200",
          },
          in_progress: {
            label: "กำลังดำเนินการ",
            color: "sky",
            className: "bg-sky-50 text-sky-700 border-sky-200",
          },
          completed: {
            label: "เสร็จสิ้น",
            color: "emerald",
            className: "bg-emerald-50 text-emerald-700 border-emerald-200",
          },
          overdue: {
            label: "ยกเลิก",
            color: "rose",
            className: "bg-rose-50 text-rose-700 border-rose-200",
          },
        },

        workSlots: createHourlyWorkSlots(),
        workSchedule: [],
        jobs: [],
      };

      // ใช้วันที่-เวลาปัจจุบันจริงเป็นค่าอ้างอิง "วันนี้" ของทั้งแอป
      // (เดิม hardcode ไว้เป็น "2026-07-08T09:00:00" ทำให้ปุ่ม "วันนี้" /
      // "รีเซ็ต" พาไปวันที่ 8 ก.ค. ตลอด ไม่ใช่วันที่ปัจจุบันจริง)
      const TODAY = new Date();
      const byId = (id) => document.getElementById(id);

      const state = {
        technicianId: "all",
        view: "month",
        currentDate: new Date(TODAY),
        activeTab: "calendar",
      };

      const els = {
        technicianSelect: byId("technicianSelect"),
        jobSearchInput: byId("jobSearchInput"),
        datePicker: byId("datePicker"),
        dateLabel: byId("dateLabel"),
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
        workScheduleTitle: byId("workScheduleTitle"),
        workScheduleSubtitle: byId("workScheduleSubtitle"),
        workSchedulePanel: byId("workSchedulePanel"),
        workScheduleSummary: byId("workScheduleSummary"),
        workScheduleHead: byId("workScheduleHead"),
        workScheduleBody: byId("workScheduleBody"),
        teamWeeklyTitle: byId("teamWeeklyTitle"),
        teamWeeklySubtitle: byId("teamWeeklySubtitle"),
        teamWeeklyPanel: byId("teamWeeklyPanel"),
        teamWeeklySummary: byId("teamWeeklySummary"),
        teamWeeklyHead: byId("teamWeeklyHead"),
        teamWeeklyBody: byId("teamWeeklyBody"),
        kanbanContainer: byId("kanbanContainer"),
        kanbanTitle: byId("kanbanTitle"),
        kanbanSubtitle: byId("kanbanSubtitle"),
      };

      let chart;
      let techChart;

      const fmtDate = (dateStr, opts = {}) =>
        new Intl.DateTimeFormat("th-TH", {
          dateStyle: "medium",
          timeStyle: opts.time ? "short" : undefined,
        }).format(new Date(dateStr));
      const fmtTime = (dateStr) =>
        new Intl.DateTimeFormat("th-TH", {
          hour: "2-digit",
          minute: "2-digit",
        }).format(new Date(dateStr));
      const pad = (n) => String(n).padStart(2, "0");
      const toYMD = (d) =>
        `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
      const monthKey = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}`;
      const yearKey = (d) => String(d.getFullYear());
      const UNASSIGNED_TECH = {
        id: "unassigned",
        name: "ยังไม่มอบหมายช่าง",
        role: "รอมอบหมายช่าง",
        avatar: "https://placehold.co/120x120/fef3c7/b45309?text=%3F",
      };
      const getTech = (id) => {
        if (!id) return UNASSIGNED_TECH;
        return (
          mockData.technicians.find((t) => t.id === id) || UNASSIGNED_TECH
        );
      };
      const getJobTech = (job) => getTech(job.technicianId);

      // ป้ายชื่อย่อแทนรูป avatar: ข้อความไทยแสดงผลตรง ๆ ด้วยฟอนต์ของหน้าเว็บ
      // (ต่างจาก <img> ที่พึ่ง placehold.co ซึ่งไม่รองรับฟอนต์ภาษาไทย)
      const avatarTones = [
        "bg-indigo-100 text-indigo-700",
        "bg-emerald-100 text-emerald-700",
        "bg-amber-100 text-amber-700",
        "bg-rose-100 text-rose-700",
        "bg-sky-100 text-sky-700",
        "bg-fuchsia-100 text-fuchsia-700",
      ];
      function getInitials(name) {
        const trimmed = (name || "").trim();
        return trimmed ? trimmed.slice(0, 2) : "?";
      }
      function getAvatarTone(seed) {
        const key = String(seed || "");
        let hash = 0;
        for (let i = 0; i < key.length; i++)
          hash = (hash * 31 + key.charCodeAt(i)) >>> 0;
        return avatarTones[hash % avatarTones.length];
      }
      function renderAvatarLabel(tech, sizeClasses) {
        return `<div class="${sizeClasses} ${getAvatarTone(tech.id || tech.name)} flex items-center justify-center font-bold shrink-0" title="${tech.name}">${getInitials(tech.name)}</div>`;
      }
      const summaryTones = {
        blue: {
          card: "border-blue-100 bg-blue-50/60",
          icon: "bg-blue-600 text-white",
          value: "text-blue-950",
        },
        emerald: {
          card: "border-emerald-100 bg-emerald-50/60",
          icon: "bg-emerald-600 text-white",
          value: "text-emerald-950",
        },
        amber: {
          card: "border-amber-100 bg-amber-50/70",
          icon: "bg-amber-500 text-white",
          value: "text-amber-950",
        },
        rose: {
          card: "border-rose-100 bg-rose-50/70",
          icon: "bg-rose-500 text-white",
          value: "text-rose-950",
        },
      };

      function renderSummaryCards(items) {
        return items
          .map((item) => {
            const tone = summaryTones[item.tone] || summaryTones.blue;
            return `<div class="group relative overflow-hidden rounded-lg border ${tone.card} p-3 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-card">
    <div class="absolute inset-x-0 top-0 h-1 ${tone.icon}"></div>
    <div class="flex items-start justify-between gap-3 pt-1">
      <div class="min-w-0">
        <div class="text-[11px] font-medium text-slate-500">${item.label}</div>
        <div class="mt-1 text-2xl font-bold tracking-normal ${tone.value}">${item.value}</div>
        <div class="mt-0.5 text-[11px] text-slate-500">${item.hint}</div>
      </div>
      <div class="shrink-0 rounded-lg ${tone.icon} p-2 shadow-sm">
        <i data-lucide="${item.icon}" class="h-4 w-4"></i>
      </div>
    </div>
  </div>`;
          })
          .join("");
      }

      // SweetAlert2 ปกติจะแนบ modal ไว้ที่ <body> แต่ตอนอยู่ในโหมด native Fullscreen
      // (requestFullscreen บน workSchedulePanel/teamWeeklyPanel) เบราว์เซอร์จะ "ซ่อน"
      // ทุกอย่างที่อยู่นอก element ที่ fullscreen อยู่ ทำให้ modal ที่แนบไว้ที่ body
      // มองไม่เห็น/กดไม่ได้ (เหมือนเปิดดูรายละเอียดไม่ได้) แก้โดยให้ modal แนบเข้าไปใน
      // element ที่กำลัง fullscreen อยู่แทน (ถ้ามี) ผ่านออปชัน target ของ Swal
      function fireSwal(options) {
        const fsEl = document.fullscreenElement;
        return Swal["fire"](fsEl ? { target: fsEl, ...options } : options);
      }

      const fullscreenTargets = [
        {
          buttonId: "btnFullscreenSchedule",
          targetId: "workSchedulePanel",
          name: "ตารางงาน",
        },
        {
          buttonId: "btnFullscreenTeamWeekly",
          targetId: "teamWeeklyPanel",
          name: "ตารางทีม",
        },
        {
          buttonId: "btnFullscreenTechJobs", 
          targetId: "techJobsCard",          
          name: "จำนวนงานรายช่าง",
        }

      ];

      function isFullscreenTarget(target) {
        return document.fullscreenElement === target;
      }

      async function toggleFullscreen(target) {
        if (!target?.requestFullscreen) {
          fireSwal({
            icon: "info",
            title: "ไม่รองรับโหมดเต็มจอ",
            text: "เบราว์เซอร์นี้ไม่รองรับ Fullscreen API",
            confirmButtonText: "ตกลง",
          });
          return;
        }

        if (isFullscreenTarget(target)) {
          await document.exitFullscreen();
          return;
        }

        if (document.fullscreenElement) {
          await document.exitFullscreen();
        }
        await target.requestFullscreen();
      }

      function updateFullscreenButtons() {
        fullscreenTargets.forEach(({ buttonId, targetId, name }) => {
          const button = byId(buttonId);
          const target = byId(targetId);
          if (!button || !target) return;

          const active = isFullscreenTarget(target);
          button.title = active ? "ออกจากเต็มจอ" : "แสดงเต็มจอ";
          button.setAttribute(
            "aria-label",
            active ? `ออกจากโหมดเต็มจอ${name}` : `แสดง${name}เต็มจอ`,
          );
          button.innerHTML = `<i data-lucide="${active ? "minimize-2" : "maximize-2"}" class="h-4 w-4"></i>`;
        });
        lucide.createIcons();
      }

      function bindFullscreenTables() {
        fullscreenTargets.forEach(({ buttonId, targetId }) => {
          byId(buttonId)?.addEventListener("click", () =>
            toggleFullscreen(byId(targetId)),
          );
        });
        document.addEventListener("fullscreenchange", updateFullscreenButtons);
        // Chart.js อาศัย ResizeObserver จับขนาด container เพื่อ resize เอง แต่บาง
        // เบราว์เซอร์ resize ช้ากว่าจังหวะที่ CSS :fullscreen เปลี่ยน layout เสร็จ
        // จึงสั่ง resize() ซ้ำเองทันทีหลัง fullscreenchange กันกราฟค้างขนาดเดิม
        document.addEventListener("fullscreenchange", () => {
          if (techChart) techChart.resize();
          if (chart) chart.resize();
        });
        updateFullscreenButtons();
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

        // Re-render chart if switching to calendar to fix canvas resize issue
        if (tab === "calendar" && chart) {
          chart.resize();
          if (techChart) techChart.resize();
        }
      }

      // ==================== เชื่อมต่อ API จริง (handle_work_history.php) ====================
      // TODO: แก้ URL และ ag_id ให้ตรงกับระบบจริง (ag_id มักมาจาก session/login)
      const API_URL = "handle_work_history.php";
      const AG_ID = <?php echo $sess_user_agency_es ?>;

      const apiState = {
        search: "",
      };

      // คำนวณช่วงวันที่แจ้งซ่อม/วันที่ช่างเข้าดำเนินการ ตามมุมมองปฏิทินที่เลือกอยู่
      // (day = วันเดียว, month = ทั้งเดือน, year = ทั้งปี) เพื่อส่งไปกรองที่ backend
      // แทนการไล่หน้าด้วย startRow/endRow แบบเดิม
      function getQueryDateRange() {
        const d = state.currentDate;
        if (state.view === "day") {
          const ymd = toYMD(d);
          return { startDate: ymd, endDate: ymd };
        }
        if (state.view === "month") {
          const start = new Date(d.getFullYear(), d.getMonth(), 1);
          const end = new Date(d.getFullYear(), d.getMonth() + 1, 0);
          return { startDate: toYMD(start), endDate: toYMD(end) };
        }
        // year
        const start = new Date(d.getFullYear(), 0, 1);
        const end = new Date(d.getFullYear(), 11, 31);
        return { startDate: toYMD(start), endDate: toYMD(end) };
      }

      function buildJobsQuery() {
        const { startDate, endDate } = getQueryDateRange();
        const params = new URLSearchParams({
          action: "get_jobs",
          ag_id: AG_ID,
          startDate,
          endDate,
          // กัน edge case กรณีมีงานเกินคาดในช่วงที่เลือก ยังคง cap จำนวนแถวไว้แบบกว้างๆ
          startRow: "0",
          endRow: "2000",
          search: apiState.search || "",
          technicianId: "all", // กรองช่างทำฝั่ง client ต่อ (state.technicianId) เหมือนเดิม
        });
        return `${API_URL}?${params.toString()}`;
      }

      async function fetchTechniciansFromApi() {
        try {
          const res = await fetch(
            `${API_URL}?action=get_technicians&ag_id=${encodeURIComponent(AG_ID)}`,
          );
          const data = await res.json();
          if (data.success && Array.isArray(data.rows)) {
            // แถวแรก ("ช่างทั้งหมด") มีอยู่แล้วใน mockData.technicians ไม่ต้องเพิ่มซ้ำ
            const rest = data.rows.filter((t) => t.id !== "all");
            mockData.technicians.push(
              ...rest.map((t) => ({
                ...t,
                avatar:
                  t.avatar ||
                  `https://placehold.co/120x120/e0e7ff/3730a3?text=${encodeURIComponent(
                    (t.name || "?").slice(0, 2),
                  )}`,
              })),
            );
          }
        } catch (err) {
          console.error("โหลดรายชื่อช่างไม่สำเร็จ:", err);
        }
      }

      // ตาราง "งานรายสัปดาห์" / "ทีมรายสัปดาห์" 
      const pad2 = (n) => String(n).padStart(2, "0");
      const toHHMM = (d) => `${pad2(d.getHours())}:${pad2(d.getMinutes())}`;

      /*
       * งาน 1 ใบอาจกินเวลาข้ามหลายวัน (เช่น เข้างาน 13 ก.ค. 09:10
       * แต่เสร็จ 16 ก.ค. 13:06) ตารางรายสัปดาห์/รายทีมแสดงผลเป็น "ราย
       * วัน" (คอลัมน์/แถวละ 1 วัน ช่วงเวลา 00:00-24:00) ดังนั้นงานแบบนี้
       * ต้องถูกแตกเป็น "เซกเมนต์ย่อย" 1 ชิ้นต่อวันที่งานนั้นครอบคลุม
       * แทนที่จะยัดทั้งหมดไว้ที่วันแรกวันเดียว (ซึ่งจะทำให้ end ที่โชว์
       * กลายเป็นแค่ "เวลา" ของวันสุดท้าย โดยไม่มีวันที่กำกับ และทำให้
       * นับชั่วโมงรวมผิดพลาด/กลายเป็น NaN เมื่อเวลาสิ้นสุดตกกลางดึกข้ามวัน)
       *
       * - วันแรก   : เวลาเริ่มจริง -> 24:00
       * - วันคั่นกลาง: 00:00 -> 24:00 (เต็มวัน)
       * - วันสุดท้าย : 00:00 -> เวลาเสร็จจริง
       *
       * แต่ละเซกเมนต์มี isContinuation / continuesNext ไว้บอก UI ว่าเป็น
       * ส่วนต่อเนื่องของงานเดียวกัน เพื่อ render เป็นการ์ดแบบเส้นประ +
       * ป้าย "ต่อเนื่อง" แทนการ์ดงานปกติ
       */
      function buildScheduleFromJobs(jobs) {
        const MAX_SPAN_DAYS = 60; // กันลูปยาวเกินจริงถ้าข้อมูลผิดปกติ
        const segments = [];

        jobs
          // technicianId ผ่านการ normalize เป็น "unassigned" มาแล้วตอนโหลดจาก API
          // (ดู fetchJobsFromApi) จึงเหลือแค่เช็ค workStart พอ ไม่กรองงาน pending/cancel
          // ที่ยังไม่มอบหมายช่างทิ้งไปอีก
          .filter((job) => job.workStart)
          .forEach((job) => {
            const start = new Date(job.workStart);
            // ถ้ายังไม่ปิดงาน (ไม่มี workEnd) ให้กะระยะเวลาไว้ 1 ชม. เพื่อให้ยังแสดงในตารางได้
            const end = job.workEnd
              ? new Date(job.workEnd)
              : new Date(start.getTime() + 60 * 60 * 1000);

            // ข้ามงานที่มีวันที่/เวลาผิดปกติ (parse ไม่ได้ หรือจบก่อนเริ่ม)
            // เพื่อไม่ให้ toHHMM ของ Invalid Date กลายเป็น "NaN:NaN" แล้วทำให้
            // การนับชั่วโมงรวมพังทั้งตาราง
            if (isNaN(start.getTime()) || isNaN(end.getTime()) || end <= start) {
              return;
            }

            const baseSegment = {
              technicianId: job.technicianId,
              title: job.title,
              category: job.type || job.serviceGroup || "",
              status: job.status,
              location: job.location,
              detail: job.issueDetail,
              linkedJobId: job.id,
              tools: (job.stockUsed || []).map((s) => s.itemName),
              spanStart: job.workStart,
              spanEnd: job.workEnd,
            };

            const startYmd = toYMD(start);
            const endYmd = toYMD(end);

            // กรณีปกติ: เริ่ม-จบภายในวันเดียวกัน -> เหมือนพฤติกรรมเดิม
            if (startYmd === endYmd) {
              segments.push({
                ...baseSegment,
                id: `SCH_${job.id}`,
                date: startYmd,
                start: toHHMM(start),
                end: toHHMM(end),
                isContinuation: false,
                continuesNext: false,
              });
              return;
            }

            // กรณีข้ามวัน: แตกเป็นเซกเมนต์รายวัน
            const cursor = new Date(
              start.getFullYear(),
              start.getMonth(),
              start.getDate(),
            );
            let dayIndex = 0;

            while (toYMD(cursor) <= endYmd && dayIndex < MAX_SPAN_DAYS) {
              const cursorYmd = toYMD(cursor);
              const isFirstDay = dayIndex === 0;
              const isLastDay = cursorYmd === endYmd;

              segments.push({
                ...baseSegment,
                id: `SCH_${job.id}_D${dayIndex}`,
                date: cursorYmd,
                start: isFirstDay ? toHHMM(start) : "00:00",
                end: isLastDay ? toHHMM(end) : "24:00",
                isContinuation: !isFirstDay,
                continuesNext: !isLastDay,
              });

              cursor.setDate(cursor.getDate() + 1);
              dayIndex += 1;
            }
          });

        return segments;
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
            mockData.workSchedule = buildScheduleFromJobs(mockData.jobs);
          } else {
            console.error("โหลดข้อมูลงานซ่อมไม่สำเร็จ:", data.error);
          }
        } catch (err) {
          console.error("เรียก API งานซ่อมไม่สำเร็จ:", err);
        }
      }

      async function loadDataFromApi() {
        await Promise.all([fetchTechniciansFromApi(), fetchJobsFromApi()]);
      }

      // เรียกใหม่ทุกครั้งที่ช่วงวันที่ (มุมมองวัน/เดือน/ปี หรือวันที่ที่เลือก) เปลี่ยน
      async function refreshJobsAndRender() {
        await fetchJobsFromApi();
        render();
      }

      // เรียกใหม่ทุกครั้งที่ค้นหาเปลี่ยน (server-side search ผ่าน handle_work_history.php)
      let searchDebounceTimer = null;
      function onJobSearchChange(value) {
        apiState.search = value.trim();
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(async () => {
          await fetchJobsFromApi();
          render();
        }, 350);
      }
      // ==================== จบส่วนเชื่อมต่อ API ====================

      async function init() {
        await loadDataFromApi();
        renderTechnicianOptions();
        renderStatusLegend();
        bindEvents();
        bindFullscreenTables();
        initTabs();
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
            const map = {
              completed: "bg-emerald-400",
              in_progress: "bg-sky-400",
              pending: "bg-amber-400",
              overdue: "bg-rose-400",
            };
            return `<div class="flex items-center justify-between gap-2">
      <span class="inline-flex items-center gap-2"><span class="status-dot ${map[key]}"></span>${s.label}</span>
      <span class="text-slate-400" id="legend-${key}">0</span>
    </div>`;
          })
          .join("");
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
            syncDateInput();
            refreshJobsAndRender();
          }),
        );
        els.datePicker.addEventListener("change", (e) => {
          if (state.view === "day")
            state.currentDate = new Date(`${e.target.value}T09:00:00`);
          if (state.view === "month")
            state.currentDate = new Date(`${e.target.value}-01T09:00:00`);
          if (state.view === "year")
            state.currentDate = new Date(`${e.target.value}-01-01T09:00:00`);
          refreshJobsAndRender();
        });
        byId("btnToday").addEventListener("click", () => {
          state.currentDate = new Date(TODAY);
          syncDateInput();
          refreshJobsAndRender();
        });
        byId("btnReset").addEventListener("click", () => {
          state.technicianId = "all";
          state.view = "month";
          state.currentDate = new Date(TODAY);
          els.technicianSelect.value = "all";
          syncDateInput();
          refreshJobsAndRender();
        });
        byId("btnPrev").addEventListener("click", () => moveDate(-1));
        byId("btnNext").addEventListener("click", () => moveDate(1));

        byId("btnPrevWeekSchedule")?.addEventListener("click", () =>
          moveWeek(-1),
        );
        byId("btnNextWeekSchedule")?.addEventListener("click", () =>
          moveWeek(1),
        );
        byId("btnPrevWeekTeam")?.addEventListener("click", () => moveWeek(-1));
        byId("btnNextWeekTeam")?.addEventListener("click", () => moveWeek(1));

        byId("btnExport").addEventListener("click", exportExcel);
        byId("btnExportSchedule").addEventListener(
          "click",
          exportScheduleExcel,
        );
        byId("btnExportTeamWeekly").addEventListener(
          "click",
          exportTeamWeeklyExcel,
        );
        byId("btnPrintSchedule")?.addEventListener(
          "click",
          printWorkSchedule,
        );
        byId("btnPrintTeamWeekly")?.addEventListener(
          "click",
          printTeamWeeklySchedule,
        );
      }

      function syncDateInput() {
        document.querySelectorAll(".view-btn").forEach((btn) => {
          btn.className = `view-btn rounded-xl py-1 text-sm font-medium ${btn.dataset.view === state.view ? "bg-white text-indigo-700 shadow-sm" : "text-slate-500 hover:text-slate-700"}`;
        });
        if (state.view === "day") {
          els.datePicker.type = "date";
          els.datePicker.value = toYMD(state.currentDate);
          els.dateLabel.textContent = "เลือกวัน";
        } else if (state.view === "month") {
          els.datePicker.type = "month";
          els.datePicker.value = monthKey(state.currentDate);
          els.dateLabel.textContent = "เลือกเดือน";
        } else {
          els.datePicker.type = "number";
          els.datePicker.min = "2020";
          els.datePicker.max = "2035";
          els.datePicker.value = yearKey(state.currentDate);
          els.dateLabel.textContent = "เลือกปี";
        }
      }

      function moveDate(direction) {
        const d = new Date(state.currentDate);
        if (state.view === "day") d.setDate(d.getDate() + direction);
        if (state.view === "month") d.setMonth(d.getMonth() + direction);
        if (state.view === "year") d.setFullYear(d.getFullYear() + direction);
        state.currentDate = d;
        syncDateInput();
        refreshJobsAndRender();
      }

      function moveWeek(direction) {
        if (state.view === "day") return;

        const d = new Date(state.currentDate);
        d.setDate(d.getDate() + direction * 7);

        if (state.view === "month") {
          const startOfMonth = new Date(
            state.currentDate.getFullYear(),
            state.currentDate.getMonth(),
            1,
          );
          const endOfMonth = new Date(
            state.currentDate.getFullYear(),
            state.currentDate.getMonth() + 1,
            0,
          );

          if (d < startOfMonth || d > endOfMonth) {
            return;
          }
        }

        state.currentDate = d;
        syncDateInput();
        render();
      }

      function getFilteredJobs() {
        return mockData.jobs
          .filter((job) => {
            const workStart = new Date(job.workStart);
            // ถ้างานยังไม่เสร็จ (ไม่มี workEnd) ให้ใช้วันเริ่มเป็นวันจบไปก่อน
            const workEnd = job.workEnd ? new Date(job.workEnd) : workStart;

            const matchTech =
              state.technicianId === "all" ||
              job.technicianId === state.technicianId;
              
            let matchDate = true;
            
            // ตรวจสอบการทับซ้อนของช่วงเวลาทำงาน กับ ปฏิทินที่ผู้ใช้กำลังดูอยู่
            if (state.view === "day") {
              const currentYMD = toYMD(state.currentDate);
              matchDate = toYMD(workStart) <= currentYMD && toYMD(workEnd) >= currentYMD;
            } else if (state.view === "month") {
              const vYear = state.currentDate.getFullYear();
              const vMonth = state.currentDate.getMonth();
              const vStart = new Date(vYear, vMonth, 1);
              const vEnd = new Date(vYear, vMonth + 1, 0, 23, 59, 59);
              matchDate = workStart <= vEnd && workEnd >= vStart;
            } else if (state.view === "year") {
              const vYear = state.currentDate.getFullYear();
              const vStart = new Date(vYear, 0, 1);
              const vEnd = new Date(vYear, 11, 31, 23, 59, 59);
              matchDate = workStart <= vEnd && workEnd >= vStart;
            }
            
            return matchTech && matchDate;
          })
          .sort((a, b) => new Date(a.workStart) - new Date(b.workStart));
      }

      function getJobsByTechOnly() {
        return mockData.jobs.filter(
          (job) =>
            state.technicianId === "all" ||
            job.technicianId === state.technicianId,
        );
      }

      function render() {
        syncDateInput();
        const jobs = getFilteredJobs();
        updateHeaders(jobs);
        renderKPIs(jobs);
        renderCalendar(jobs);
        renderTechJobsChart(jobs);
        renderChart(jobs);
        updateLegends(jobs);
        renderWorkSchedule();
        renderTeamWeeklySchedule();
        renderKanban(jobs);
      }

      function updateHeaders(jobs) {
        const tech = getTech(state.technicianId);
        const titleDate =
          state.view === "day"
            ? fmtDate(state.currentDate)
            : state.view === "month"
              ? new Intl.DateTimeFormat("th-TH", {
                  month: "long",
                  year: "numeric",
                }).format(state.currentDate)
              : new Intl.DateTimeFormat("th-TH", { year: "numeric" }).format(
                  state.currentDate,
                );

        els.calendarTitle.textContent = `ปฏิทินงาน: ${titleDate}`;
        els.calendarSubtitle.textContent = `${tech.name} • ${tech.role}`;
        els.jobCountBadge.textContent = `${jobs.length} งาน`;

        els.kanbanTitle.textContent = `บอร์ดสถานะงาน: ${titleDate}`;
        els.kanbanSubtitle.textContent = `${tech.name} • แสดงใบแจ้งซ่อมตามสถานะในปัจจุบัน`;
      }

      function calcStats(jobs) {
        const total = jobs.length;
        const completed = jobs.filter((j) => j.status === "completed").length;
        const overdue = jobs.filter((j) => j.status === "overdue").length;
        const pending = jobs.filter((j) => j.status === "pending").length;
        const inProgress = jobs.filter(
          (j) => j.status === "in_progress",
        ).length;
        const totalHours = jobs.reduce((sum, j) => {
          // 1. เช็คว่ามีข้อมูลทั้งเวลาเริ่มและเวลาสิ้นสุด
          if (!j.workEnd || !j.workStart) return sum;
          
          const start = new Date(j.workStart);
          const end = new Date(j.workEnd);
          
          // 2. เช็คว่า Date ที่ได้ถูกต้อง (ไม่เป็น Invalid Date)
          if (isNaN(start.getTime()) || isNaN(end.getTime())) return sum;
          
          // 3. (ทางเลือก) เช็คว่าเวลาสิ้นสุดต้องไม่น้อยกว่าเวลาเริ่ม
          if (end < start) return sum; 
          
          return sum + (end - start) / 36e5; // แปลง milliseconds เป็นชั่วโมง
        }, 0);
        const avgHours = total ? totalHours / total : 0;
        const onTime = jobs.filter(
          (j) =>
            new Date(j.workEnd) <= new Date(j.dueDate) &&
            j.status === "completed",
        ).length;
        const efficiency = total
          ? Math.round(
              ((completed * 0.55 +
                onTime * 0.35 +
                Math.max(0, total - overdue) * 0.1) /
                total) *
                100,
            )
          : 0;
        const avgRating = completed
          ? jobs.filter((j) => j.rating).reduce((s, j) => s + j.rating, 0) /
            completed
          : 0;
        return {
          total,
          completed,
          overdue,
          pending,
          inProgress,
          totalHours,
          avgHours,
          onTime,
          efficiency,
          avgRating,
        };
      }

      function renderKPIs(jobs) {
        const s = calcStats(jobs);
        const cards = [
          {
            label: "งานทั้งหมด",
            value: s.total,
            icon: "briefcase",
            hint: "รายการที่กรองอยู่",
            color: "from-indigo-500 to-blue-500",
            iconColor: "text-indigo-500",
          },
          {
            label: "เสร็จสิ้น",
            value: s.completed,
            icon: "check-circle-2",
            hint: "ปิดงานเรียบร้อย",
            color: "from-emerald-500 to-teal-500",
            iconColor: "text-emerald-500",
          },
          {
            label: "ตรงเวลา",
            value: `${s.onTime}/${s.total}`,
            icon: "clock",
            hint: "เสร็จก่อนกำหนด",
            color: "from-sky-500 to-cyan-500",
            iconColor: "text-sky-500",
          },
          {
            label: "ชม.ทำงาน",
            value: s.totalHours.toFixed(1),
            icon: "timer",
            hint: `เฉลี่ย ${s.avgHours.toFixed(1)} ชม./งาน`,
            color: "from-amber-500 to-orange-500",
            iconColor: "text-amber-500",
          },
          {
            label: "คะแนนเฉลี่ย",
            value: s.avgRating ? s.avgRating.toFixed(1) : "-",
            icon: "star",
            hint: "จากผู้แจ้งซ่อม",
            color: "from-fuchsia-500 to-pink-500",
            iconColor: "text-fuchsia-500",
          },
        ];

        els.kpiCards.innerHTML = cards
          .map(
            (c) => `
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
  `,
          )
          .join("");

        els.scoreBadge.textContent = `${s.efficiency}%`;

        if (window.lucide) {
          lucide.createIcons();
        }
      }

      function renderMonthEventPill(job) {
        const s = mockData.status[job.status] || { className: 'bg-slate-100 text-slate-800' }; 
        
        return `<button onclick="openJobDetail('${job.id}')" 
          class="w-full text-left border ${s.className} rounded-xl px-2 py-1.5 mb-1 text-[11px] xs:text-xs leading-tight truncate hover:opacity-80 transition-opacity">
          <span class="font-semibold">${fmtTime(job.workStart)}</span> ${job.title}
        </button>`;
      }

      function renderCalendar(jobs) {
        els.monthCalendarWrap.classList.toggle(
          "hidden",
          state.view !== "month",
        );
        els.dayTimelineWrap.classList.toggle("hidden", state.view !== "day");
        els.yearCalendarWrap.classList.toggle("hidden", state.view !== "year");
        if (state.view === "month") renderMonthCalendar(jobs);
        if (state.view === "day") renderDayTimeline(jobs);
        if (state.view === "year") renderYearGrid(jobs);
      }

      function jobBadge(job, compact = false) {
        const s = mockData.status[job.status];
        const tech = getJobTech(job);
        return `<button onclick="openJobDetail('${job.id}')" class="event-pill w-full text-left rounded-xl border ${s.className} px-2 py-1.5 mb-1 text-[11px] xs:text-xs leading-tight">
    <div class="font-semibold truncate">${fmtTime(job.workStart)} ${job.title}</div>
    ${compact ? "" : `<div class="opacity-80 truncate">${tech.name}</div>`}
  </button>`;
      }

      function renderMonthCalendar(jobs) {
        const year = state.currentDate.getFullYear();
        const month = state.currentDate.getMonth();
        const first = new Date(year, month, 1);
        const startDay = first.getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const cells = [];
        
        // เติมช่องว่างก่อนวันที่ 1
        for (let i = 0; i < startDay; i++) {
          cells.push(`<div class="calendar-day border-b border-r border-slate-100 bg-slate-50/60"></div>`);
        }
        
        // สร้างตารางแต่ละวัน
        for (let day = 1; day <= daysInMonth; day++) {
          const d = new Date(year, month, day);
          const currentYMD = toYMD(d);
          
          // กรองเอาเฉพาะงานที่ "วันที่แจ้งซ่อม" ตรงกับ currentYMD เท่านั้น
          const dayJobs = jobs.filter((j) => toYMD(new Date(j.workStart)) === currentYMD);

          // เรียงลำดับตามเวลาแจ้งซ่อม
          dayJobs.sort((a, b) => new Date(a.workStart) - new Date(b.workStart));

          const isToday = currentYMD === toYMD(new Date()); 

          cells.push(`
            <div class="calendar-day border-b border-r border-slate-100 p-2 bg-white hover:bg-slate-50/70 transition overflow-hidden">
              <div class="flex items-center justify-between mb-1.5">
                <span class="${isToday ? "bg-indigo-600 text-white" : "text-slate-500"} h-7 w-7 rounded-full inline-flex items-center justify-center text-sm font-semibold">${day}</span>
                ${dayJobs.length ? `<span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">${dayJobs.length}</span>` : ""}
              </div>
              <div class="flex flex-col gap-0.5 max-h-[86px] overflow-visible">
                ${dayJobs
                  .slice(0, 2)
                  .map((j) => renderMonthEventPill(j))
                  .join("")}
                ${dayJobs.length > 2 ? `<button onclick="openDayJobs('${currentYMD}')" class="text-[11px] text-indigo-600 font-medium mt-0.5 w-full text-left truncate hover:underline">+ อีก ${dayJobs.length - 2} งาน</button>` : ""}
              </div>
            </div>
          `);
        }
        
        // เติมช่องว่างท้ายเดือนให้ครบแถว
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
          const hourJobs = jobs.filter(
            (j) => new Date(j.workStart).getHours() === h,
          );
          
          return `<div class="timeline-row">
      <div class="text-sm font-semibold text-slate-400 pt-3">${pad(h)}:00</div>
      <div class="min-h-[64px] rounded-2xl bg-slate-50 border border-slate-100 p-2">
        ${hourJobs.length ? hourJobs.map((j) => `<div class="mb-2 last:mb-0">${jobBadge(j)}</div>`).join("") : '<span class="text-xs text-slate-300">ว่าง</span>'}
      </div>
    </div>`;
        })
        .join("");
    }

      function renderYearGrid(jobs) {
        const months = Array.from(
          { length: 12 },
          (_, m) => new Date(state.currentDate.getFullYear(), m, 1),
        );
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
            const monthName = new Intl.DateTimeFormat("th-TH", {
              month: "long",
            }).format(mDate);
            const topTypes = Object.entries(
              mJobs.reduce((acc, j) => {
                acc[j.type] = (acc[j.type] || 0) + 1;
                return acc;
              }, {}),
            )
              .sort((a, b) => b[1] - a[1])
              .slice(0, 2);
            return `<button onclick="switchToMonth(${mDate.getMonth()})" class="text-left rounded-[24px] bg-slate-50 hover:bg-white border border-slate-100 hover:shadow-card p-5 transition">
      <div class="flex items-center justify-between gap-2">
        <h3 class="font-bold text-slate-950">${monthName}</h3>
        <span class="px-2.5 py-1 rounded-full bg-white text-slate-500 text-xs font-medium">${mJobs.length} งาน</span>
      </div>
      <div class="mt-4 grid grid-cols-3 gap-2 text-center">
        <div class="rounded-2xl bg-white p-2"><div class="text-lg font-bold text-emerald-600">${stats.completed}</div><div class="text-[11px] text-slate-400">เสร็จ</div></div>
        <div class="rounded-2xl bg-white p-2"><div class="text-lg font-bold text-rose-600">${stats.overdue}</div><div class="text-[11px] text-slate-400">เกิน</div></div>
        <div class="rounded-2xl bg-white p-2"><div class="text-lg font-bold text-indigo-600">${stats.efficiency}</div><div class="text-[11px] text-slate-400">คะแนน</div></div>
      </div>
      <div class="mt-3 text-xs text-slate-500 min-h-5">${topTypes.length ? "งานหลัก: " + topTypes.map((t) => `${t[0]} ${t[1]}`).join(", ") : "ไม่มีงาน"}</div>
    </button>`;
          })
          .join("");
      }

      function renderKanban(jobs) {
        const columns = [
          {
            id: "pending",
            title: "รอ/เตรียมการ",
            color: "amber",
            bgHeader: "bg-amber-100/50",
            border: "border-amber-200",
            text: "text-amber-800",
          },
          {
            id: "in_progress",
            title: "กำลังดำเนินการ",
            color: "sky",
            bgHeader: "bg-sky-100/50",
            border: "border-sky-200",
            text: "text-sky-800",
          },
          {
            id: "completed",
            title: "เสร็จสิ้น",
            color: "emerald",
            bgHeader: "bg-emerald-100/50",
            border: "border-emerald-200",
            text: "text-emerald-800",
          },
          {
            id: "overdue",
            title: "ยกเลิก",
            color: "rose",
            bgHeader: "bg-rose-100/50",
            border: "border-rose-200",
            text: "text-rose-800",
          },
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
                    ${
                      colJobs.length > 0
                        ? colJobs.map((job) => createKanbanCard(job)).join("")
                        : `<div class="p-4 text-center border-2 border-dashed border-slate-200 rounded-lg text-slate-400 text-xs">ไม่มีงานในสถานะนี้</div>`
                    }
                </div>
            </div>
        `;
        });

        els.kanbanContainer.innerHTML = html;

        // Re-initialize Lucide icons for the newly injected HTML
        if (window.lucide) {
          lucide.createIcons();
        }
      }

      function createKanbanCard(job) {
        const tech = getJobTech(job);
        // Determine priority color
        let priorityText = "";
        let priorityColor = "text-slate-500 bg-slate-100";
        if (job.priority === "critical")
          priorityText = "เร่งด่วน";
          priorityColor = "text-amber-600 bg-amber-50 border border-amber-100";
        if (job.priority === "high")
          priorityText = "สูง";
          priorityColor = "text-rose-600 bg-rose-50 border border-rose-100";
        if (job.priority === "medium")
          priorityText = "ปานกลาง";
          priorityColor = "text-amber-600 bg-amber-50 border border-amber-100";
        if (job.priority === "low")
          priorityText = "น้อย";
          priorityColor = "text-amber-600 bg-amber-50 border border-amber-100";

        return `
        <button onclick="openJobDetail('${job.id}')" class="w-full text-left bg-white p-3 rounded-lg border border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all cursor-pointer group">
            <div class="flex justify-between items-start mb-1.5 gap-2">
                <span class="text-[10px] font-medium text-slate-400 group-hover:text-indigo-500 transition-colors">${job.id}</span>
                <span class="text-[9px] px-1.5 py-0.5 rounded-md font-medium ${priorityColor}">${priorityText}</span>
            </div>
            
            <h4 class="text-[13px] font-semibold text-slate-800 leading-tight mb-1.5 line-clamp-2">${job.title}</h4>
            
            <div class="flex flex-col gap-1 mb-2.5">
                <p class="text-[11px] text-slate-500 truncate flex items-center gap-1">
                    <i data-lucide="map-pin" class="w-3 h-3 shrink-0"></i> ${job.location}
                </p>
                <p class="text-[11px] text-slate-500 truncate flex items-center gap-1">
                    <i data-lucide="clock" class="w-3 h-3 shrink-0"></i> ${fmtDate(job.workStart, { time: true })}
                </p>
            </div>
            
            <div class="flex items-center gap-2 pt-2.5 border-t border-slate-100">
                ${renderAvatarLabel(tech, "w-5 h-5 rounded-md object-cover border border-slate-200 text-xs")}
                <div class="text-[11px] font-medium text-slate-600 truncate flex-1">${tech.name}</div>
            </div>
        </button>
    `;
      }

      function renderTechJobsChart(jobs) {
        const ctx = byId("techJobsChart").getContext("2d");

        const techCounts = {};
        const techsToDisplay = state.technicianId === "all"
          ? mockData.technicians.filter((t) => t.id !== "all")
          : mockData.technicians.filter((t) => t.id === state.technicianId);

        techsToDisplay.forEach((tech) => {
          techCounts[tech.name] = 0;
        });

        jobs.forEach((job) => {
          const tech = mockData.technicians.find(
            (t) => t.id === job.technicianId,
          );
          if (tech && techCounts[tech.name] !== undefined) {
            techCounts[tech.name] += 1;
          }
        });

        if (techChart) techChart.destroy();

        techChart = new Chart(ctx, {
          type: "bar",
          plugins: [ChartDataLabels],
          data: {
            labels: Object.keys(techCounts),
            datasets: [
              {
                label: "จำนวนงาน",
                data: Object.values(techCounts),
                backgroundColor: "#006B9F",
                borderRadius: 4,
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
              padding: { top: 18 },
            },
            scales: {
              y: { beginAtZero: true, ticks: { precision: 0 } },
            },
            plugins: {
              legend: { display: false },
              datalabels: {
                anchor: "end",
                align: "top",
                offset: 2,
                color: "#004a6f",
                font: { family: "Prompt", weight: "600", size: 11 },
                formatter: (value) => (value > 0 ? value : ""),
              },
            },
          },
        });
      }

      function renderChart(jobs) {
        const grouped = jobs.reduce((acc, j) => {
          acc[j.status] = (acc[j.status] || 0) + 1;
          return acc;
        }, {});
        const labels = [
          "เสร็จสิ้น",
          "กำลังดำเนินการ",
          "รอดำเนินการ",
          "ยกเลิก",
        ];
        const data = [
          grouped.completed || 0,
          grouped.in_progress || 0,
          grouped.pending || 0,
          grouped.overdue || 0,
        ];
        const ctx = byId("performanceChart");
        if (chart) chart.destroy();
        chart = new Chart(ctx, {
          type: "doughnut",
          plugins: [ChartDataLabels],
          data: {
            labels,
            datasets: [
              {
                data,
                borderWidth: 0,
                hoverOffset: 8,
                backgroundColor: ["#10b981", "#0ea5e9", "#f59e0b", "#f43f5e"],
              },
            ],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: "bottom",
                labels: {
                  font: { family: "Prompt" },
                  usePointStyle: true,
                  padding: 18,
                },
              },
              tooltip: {
                titleFont: { family: "Prompt" },
                bodyFont: { family: "Prompt" },
              },
              datalabels: {
                color: "#fff",
                font: { family: "Prompt", weight: "600", size: 12 },
                formatter: (value) => (value > 0 ? value : ""),
              },
            },
            cutout: "68%",
          },
        });
      }

      function updateLegends(jobs) {
        const grouped = jobs.reduce((acc, j) => {
          acc[j.status] = (acc[j.status] || 0) + 1;
          return acc;
        }, {});
        Object.keys(mockData.status).forEach((k) => {
          const el = byId(`legend-${k}`);
          if (el) el.textContent = grouped[k] || 0;
        });
      }

      function timeToMinutes(t) {
        const [h, m] = t.split(":").map(Number);
        return h * 60 + m;
      }

      function durationHours(item) {
        return Math.max(
          0,
          (timeToMinutes(item.end) - timeToMinutes(item.start)) / 60,
        );
      }

      function totalDurationHours(items) {
        return items.reduce((sum, item) => sum + durationHours(item), 0);
      }

      function combineDateTime(date, time) {
        return `${date}T${time}:00`;
      }

      /*
       * แปลงระยะเวลา (มิลลิวินาที) เป็นข้อความอ่านง่าย โดยแตกเป็น
       * "วัน ชม. นาที" แทนการโชว์ชั่วโมงเป็นเลขยาวๆ (เช่น 687 ชม. 3 นาที
       * -> อ่านยาก) ให้กลายเป็น "28 วัน 15 ชม. 3 นาที" อ่านง่ายกว่ามาก
       * ส่วนที่มีค่าเป็น 0 จะถูกซ่อนไป (เช่น งานที่จบภายในวันเดียวจะไม่มี
       * "0 วัน" โผล่มา)
       */
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

      function fmtShortDate(date) {
        return new Intl.DateTimeFormat("th-TH", {
          day: "2-digit",
          month: "short",
        }).format(date);
      }

      function renderDayLabelCell(day, stickyClass) {
        return `<td class="${stickyClass} p-3 align-middle text-center bg-slate-50 font-semibold text-slate-700">
      <div>${day.name}</div><div class="text-[11px] font-normal text-slate-400">${fmtShortDate(day.date)}</div>
    </td>`;
      }

      function renderEmptyScheduleCell(className = "p-2 bg-white") {
        return `<td class="${className}"><div class="h-full min-h-[72px] rounded-2xl bg-slate-50/60"></div></td>`;
      }

      function renderSummary(target, summary) {
        target.innerHTML = renderSummaryCards(summary);
        lucide.createIcons();
      }

      function getEntriesByTech(entries, technicianId) {
        return entries.filter((item) => item.technicianId === technicianId);
      }

      function getEntriesByTechAndDay(entries, technicianId, ymd) {
        return entries.filter(
          (item) => item.technicianId === technicianId && item.date === ymd,
        );
      }

      function countBy(items, key) {
        return items.reduce((acc, item) => {
          const value = item[key];
          acc[value] = (acc[value] || 0) + 1;
          return acc;
        }, {});
      }

      function renderStatusBadges(statusCounts, className = "text-[10px]") {
        return Object.entries(statusCounts)
          .map(([status, count]) => {
            const s = mockData.status[status];
            return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border ${className} ${getScheduleStatusClass(status)}">${s?.label || status} ${count}</span>`;
          })
          .join("");
      }

      function getWeekDays(baseDate) {
        const d = new Date(baseDate);
        const day = d.getDay();
        const diffToMonday = day === 0 ? -6 : 1 - day;
        const monday = new Date(d);
        monday.setDate(d.getDate() + diffToMonday);
        const names = [
          "จันทร์",
          "อังคาร",
          "พุธ",
          "พฤหัสฯ",
          "ศุกร์",
          "เสาร์",
          "อาทิตย์",
        ];
        return names.map((name, i) => {
          const date = new Date(monday);
          date.setDate(monday.getDate() + i);
          return { name, date, ymd: toYMD(date) };
        });
      }

      function getScheduleEntriesForWeek() {
        const days = getWeekDays(state.currentDate);
        const set = new Set(days.map((d) => d.ymd));
        return mockData.workSchedule
          .filter((item) => set.has(item.date))
          .filter(
            (item) =>
              state.technicianId === "all" ||
              item.technicianId === state.technicianId,
          )
          .sort((a, b) =>
            combineDateTime(a.date, a.start).localeCompare(
              combineDateTime(b.date, b.start),
            ),
          );
      }

      function getScheduleSlotRange(entry, slots) {
        const start = timeToMinutes(entry.start);
        const end = timeToMinutes(entry.end);
        let first = slots.findIndex((slot) => timeToMinutes(slot.end) > start);
        let last = slots.length - 1;
        for (let i = slots.length - 1; i >= 0; i--) {
          if (timeToMinutes(slots[i].start) < end) {
            last = i;
            break;
          }
        }
        if (first < 0) first = 0;
        if (last < first) last = first;
        return { startIndex: first, span: last - first + 1 };
      }

      function getScheduleStatusClass(status) {
        return (
          mockData.status[status]?.className ||
          "bg-slate-50 text-slate-700 border-slate-200"
        );
      }

      let currentScheduleGroups = {};

      function makeScheduleGroupId(item) {
        const jobId = String(item.linkedJobId || item.id || "")
          .replace(/[^a-zA-Z0-9_-]/g, "_");

        return `GRP_${jobId}_${item.technicianId}_${item.date}`;
      }

      function groupScheduleEntries(items) {
        const grouped = {};
        items.forEach((item) => {
          const id = makeScheduleGroupId(item);
          if (!grouped[id]) {
            grouped[id] = {
              id,
              technicianId: item.technicianId,
              date: item.date,
              start: item.start,
              end: item.end,
              items: [],
            };
          }
          grouped[id].items.push(item);
        });
        return Object.values(grouped).sort((a, b) =>
          `${a.date} ${a.start}`.localeCompare(`${b.date} ${b.start}`),
        );
      }

      function renderScheduleGroupCard(group, mini = false) {
        currentScheduleGroups[group.id] = group;
        if (group.items.length === 1)
          return renderScheduleCard(group.items[0], mini);

        const tech = getTech(group.technicianId);
        const statusCounts = countBy(group.items, "status");

        const statusBadges = renderStatusBadges(
          statusCounts,
          "text-[9px] font-medium",
        );

        return `<button onclick="openScheduleGroupDetail('${group.id}')" class="schedule-card w-full text-left rounded-lg border border-indigo-200 bg-indigo-50 p-1.5 hover:bg-indigo-100">
    <div class="flex items-start justify-between gap-1.5">
      <div class="min-w-0">
        <div class="font-bold text-indigo-900 text-[11px] leading-tight">งานซ้อน ${group.items.length} รายการ</div>
        <div class="mt-0.5 text-[10px] text-indigo-700 truncate">${group.start}-${group.end} • ${tech.name}</div>
        ${
          mini
            ? ""
            : `<div class="mt-0.5 text-[10px] text-indigo-600 truncate">${group.items
                .slice(0, 2)
                .map((i) => i.title)
                .join(" / ")}${group.items.length > 2 ? " ..." : ""}</div>`
        }
        <div class="mt-1 flex flex-wrap gap-1">${statusBadges}</div>
      </div>
      <span class="shrink-0 text-[9px] px-1.5 py-0.5 rounded-full bg-white/80 text-indigo-700">คลิกดู</span>
    </div>
  </button>`;
      }


      function renderScheduleContinuationCard(group, mini = false) {
        currentScheduleGroups[group.id] = group;

        const item = group.items[0];
        if (!item) return "";

        const tech = getTech(group.technicianId);
        const target =
          group.items.length > 1
            ? `openScheduleGroupDetail('${group.id}')`
            : item.linkedJobId
              ? `openJobDetail('${item.linkedJobId}')`
              : `openScheduleDetail('${item.id}')`;

        return `
          <button
            onclick="${target}"
            class="schedule-card w-full text-left rounded-lg border border-dashed
                   ${getScheduleStatusClass(item.status)} p-1.5 opacity-90"
          >
            <div class="flex items-start justify-between gap-1.5">
              <div class="min-w-0 flex-1">
                <div class="text-[9px] font-bold opacity-75 truncate">
                  ต่อเนื่อง • ${item.linkedJobId || item.id}
                </div>

                <div class="font-semibold text-[11px] leading-tight
                            ${mini ? "line-clamp-2" : ""}">
                  ${item.title}
                </div>

                <div class="mt-0.5 text-[10px] font-medium opacity-80">
                  ต่อเนื่องถึง ${group.end}
                </div>

                <div class="mt-0.5 text-[10px] opacity-75 truncate">
                  ${item.location || "ไม่ระบุสถานที่"}
                </div>

                <div class="mt-0.5 text-[10px] opacity-75 truncate">
                  ${tech.name}
                </div>
              </div>

              <span class="shrink-0 text-[9px] px-1.5 py-0.5
                           rounded-full bg-white/70">
                ต่อเนื่อง
              </span>
            </div>
          </button>
        `;
      }

      function renderScheduleCard(item, mini = false) {
      const tech = getTech(item.technicianId);

      const target = item.linkedJobId
        ? `openJobDetail('${item.linkedJobId}')`
        : `openScheduleDetail('${item.id}')`;

      const continuationClass = item.isContinuation
        ? "border-dashed opacity-90"
        : "";

      return `
        <button
          onclick="${target}"
          class="schedule-card w-full text-left rounded-lg border
                ${getScheduleStatusClass(item.status)} ${continuationClass} p-1.5"
        >
          <div class="flex items-start justify-between gap-1.5">
            <div class="min-w-0 flex-1">

              <div class="text-[9px] font-bold opacity-70 truncate">
                ${item.isContinuation ? "ต่อเนื่อง • " : ""}${item.linkedJobId || item.id}
              </div>

              <div class="font-semibold text-[11px] leading-tight
                          ${mini ? "line-clamp-2" : ""}">
                ${item.title}
              </div>

              <div class="mt-0.5 text-[10px] opacity-80">
                ${item.start}-${item.end}${item.continuesNext ? " → ต่อวันถัดไป" : ""}
              </div>

              <div class="mt-0.5 text-[10px] opacity-75 truncate">
                ${item.location || "ไม่ระบุสถานที่"}
              </div>

              <div class="mt-0.5 text-[10px] opacity-75 truncate">
                ${tech.name}
              </div>
            </div>

            ${
              item.linkedJobId
                ? `<span class="shrink-0 text-[9px] px-1.5 py-0.5
                            rounded-full bg-white/70">
                    ใบงาน
                  </span>`
                : ""
            }
          </div>
        </button>
      `;
    }

      /*
       * แบ่งตารางออกเป็นช่องละ 30 นาที
       * หัวตารางยังแสดงเป็นรายชั่วโมง โดยแต่ละชั่วโมง colspan 2 ช่อง
       */
      function createHalfHourWorkSlots() {
        return Array.from({ length: 48 }, (_, index) => {
          const startMinutes = index * 30;
          const endMinutes = startMinutes + 30;

          const startHour = Math.floor(startMinutes / 60);
          const startMinute = startMinutes % 60;
          const endHour = Math.floor(endMinutes / 60);
          const endMinute = endMinutes % 60;

          return {
            index,
            start: `${pad2(startHour)}:${pad2(startMinute)}`,
            end: `${pad2(endHour)}:${pad2(endMinute)}`,
          };
        });
      }

      /*
       * แปลงเวลาเป็นตำแหน่งช่อง 30 นาที
       * เวลาเริ่มใช้การปัดใกล้ที่สุด เพื่อไม่ให้ตารางแตกเป็นหลายแถวเกินไป
       * เวลาสิ้นสุดปัดขึ้น เพื่อให้การ์ดครอบคลุมช่วงงาน
       */
      function getHalfHourScheduleRange(entry) {
        const totalSlots = 48;
        const startMinutes = timeToMinutes(entry.start);
        const endMinutes = timeToMinutes(entry.end);

        let startIndex = Math.round(startMinutes / 30);
        let endIndex = Math.ceil(endMinutes / 30);

        startIndex = Math.max(0, Math.min(totalSlots - 1, startIndex));
        endIndex = Math.max(startIndex + 1, Math.min(totalSlots, endIndex));

        return {
          startIndex,
          endIndex,
          span: endIndex - startIndex,
        };
      }

      /*
       * จัดงานลงแถว (lane) โดยงานที่กินช่องเวลาเดียวกันจะถูกย้ายลงแถวถัดไป
       * จึงสามารถใช้ colspan ได้โดยไม่ทำให้งานอื่นถูกข้ามหรือหาย
       */
      function buildScheduleLanes(groups) {
        const prepared = groups
          .map((group) => ({
            ...group,
            range: getHalfHourScheduleRange(group),
          }))
          .sort((a, b) => {
            if (a.range.startIndex !== b.range.startIndex) {
              return a.range.startIndex - b.range.startIndex;
            }
            return a.range.endIndex - b.range.endIndex;
          });

        const lanes = [];

        prepared.forEach((group) => {
          let targetLane = lanes.find(
            (lane) => group.range.startIndex >= lane.endIndex,
          );

          if (!targetLane) {
            targetLane = {
              endIndex: 0,
              groups: [],
            };
            lanes.push(targetLane);
          }

          targetLane.groups.push(group);
          targetLane.endIndex = group.range.endIndex;
        });

        return lanes.length
          ? lanes
          : [
              {
                endIndex: 0,
                groups: [],
              },
            ];
      }

      function renderHalfHourEmptyCells(count, startIndex = 0) {
        return Array.from(
          { length: count },
          (_, offset) => {
            const slotIndex = startIndex + offset;
            const isHourEnd = (slotIndex + 1) % 2 === 0;

            return `
              <td
                class="schedule-half-cell schedule-time-cell
                       ${isHourEnd ? "schedule-hour-end" : ""}
                       p-0 bg-white"
              >
                <div class="min-h-[70px]"></div>
              </td>
            `;
          },
        ).join("");
      }

      function renderScheduleSpanCard(group, mini = false) {
        currentScheduleGroups[group.id] = group;

        const item = group.items[0];
        if (!item) return "";

        const tech = getTech(group.technicianId);
        const target =
          group.items.length > 1
            ? `openScheduleGroupDetail('${group.id}')`
            : item.linkedJobId
              ? `openJobDetail('${item.linkedJobId}')`
              : `openScheduleDetail('${item.id}')`;

        // งานที่ข้ามวัน: แถบวันนี้เป็นแค่ "ส่วนหนึ่ง" ของงานทั้งใบ
        // ทูลทิปเลยโชว์ช่วงเวลาเต็ม (จากวันเริ่ม ถึงวันจบจริง) แทนที่จะโชว์
        // แค่เวลาของวันนี้ ซึ่งทำให้เข้าใจผิดว่างานจบภายในวันเดียว
        const fullRangeLabel =
          item.spanStart && item.spanEnd
            ? `${fmtDate(item.spanStart, { time: true })} — ${fmtDate(item.spanEnd, { time: true })}`
            : `${item.start}-${item.end}`;

        const tooltip = `${item.linkedJobId || item.id} • ${item.title} • ${fullRangeLabel}`;

        const continuationClass = item.isContinuation
          ? "border-dashed opacity-90"
          : "";

        return `
          <button
            onclick="${target}"
            title="${tooltip}"
            class="schedule-card w-full h-full min-h-[62px] text-left rounded-lg border
                   ${getScheduleStatusClass(item.status)}
                   ${continuationClass}
                   px-2 py-1.5 overflow-hidden"
          >
            <div class="flex items-start justify-between gap-1">
              <div class="min-w-0 flex-1">
                <div class="text-[9px] font-bold opacity-70 truncate">
                  ${item.isContinuation ? "ต่อเนื่อง • " : ""}${item.linkedJobId || item.id}
                </div>

                <div class="font-semibold text-[11px] leading-tight ${
                  mini ? "truncate" : "line-clamp-2"
                }">
                  ${item.title}
                </div>

                <div class="mt-0.5 text-[10px] font-medium opacity-80 whitespace-nowrap">
                  ${item.start}-${item.end}${item.continuesNext ? " → ต่อวันถัดไป" : ""}
                </div>

                ${
                  mini
                    ? ""
                    : `<div class="mt-0.5 text-[10px] opacity-70 truncate">
                        ${item.location || "ไม่ระบุสถานที่"} • ${tech.name}
                       </div>`
                }
              </div>

              ${
                item.linkedJobId
                  ? `<span class="shrink-0 text-[8px] px-1 py-0.5 rounded-full bg-white/70">
                       ใบงาน
                     </span>`
                  : ""
              }
            </div>
          </button>
        `;
      }

      function renderWorkSchedule() {
        const days = getWeekDays(state.currentDate);
        const entries = getScheduleEntriesForWeek();
        const tech = getTech(state.technicianId);
        const weekStart = days[0].date;
        const weekEnd = days[6].date;
        const hours = totalDurationHours(entries);
        const cancelled = entries.filter((i) => i.status === "overdue").length;
        const completed = entries.filter(
          (i) => i.status === "completed",
        ).length;
        const uniqueDays = new Set(entries.map((i) => i.date)).size;

        els.workScheduleTitle.textContent =
          `ตารางงานรายสัปดาห์: ${fmtDate(weekStart)} - ${fmtDate(weekEnd)}`;

        els.workScheduleSubtitle.textContent =
          `${tech.name} • แสดงตามช่วงเวลาเหมือนตาราง คลิกงานเพื่อดูรายละเอียด`;

        const summary = [
          {
            label: "งานในตาราง",
            value: entries.length,
            hint: "รายการ",
            icon: "clipboard-list",
            tone: "blue",
          },
          {
            label: "ชั่วโมงรวม",
            value: hours.toFixed(1),
            hint: "ชม.",
            icon: "clock-3",
            tone: "emerald",
          },
          {
            label: "วันทำงาน",
            value: uniqueDays,
            hint: "วัน/สัปดาห์",
            icon: "calendar-days",
            tone: "amber",
          },
          {
            label: "งานที่ยกเลิก",
            value: cancelled,
            hint: `เสร็จแล้ว ${completed}`,
            icon: "ban",
            tone: "rose",
          },
        ];

        renderSummary(els.workScheduleSummary, summary);

        const hourlySlots = createHourlyWorkSlots();
        const halfHourSlots = createHalfHourWorkSlots();
        const totalHalfHourSlots = halfHourSlots.length;
        const miniCard = state.technicianId === "all";

        /*
         * 1 ชั่วโมง = 2 ช่องย่อย ช่องละ 30 นาที
         */
        els.workScheduleHead.innerHTML = `
          <tr>
            <th
              class="sticky-day w-[120px] p-3 text-center bg-slate-100
                     text-slate-600 font-semibold rounded-tl-3xl"
            >
              วัน
            </th>

            ${hourlySlots
              .map(
                (slot) => `
                  <th
                    colspan="2"
                    class="min-w-[120px] p-3 text-center bg-slate-100
                           text-slate-600 font-semibold"
                  >
                    <div class="text-[11px] font-normal">${slot.label}</div>
                  </th>
                `,
              )
              .join("")}
          </tr>
        `;

        currentScheduleGroups = {};

        const bodyRows = [];

        days.forEach((day) => {
          const groups = groupScheduleEntries(
            entries.filter((item) => item.date === day.ymd),
          );

          const lanes = buildScheduleLanes(groups);
          const rowSpan = lanes.length;

          lanes.forEach((lane, laneIndex) => {
            let html = "";

            if (laneIndex === 0) {
              html += `
                <td
                  rowspan="${rowSpan}"
                  class="sticky-day p-3 align-middle text-center bg-slate-50
                         font-semibold text-slate-700"
                >
                  <div>${day.name}</div>
                  <div class="text-[11px] font-normal text-slate-400">
                    ${fmtShortDate(day.date)}
                  </div>

                  ${
                    groups.length
                      ? `<div class="mt-1 text-[10px] font-normal text-slate-400">
                           ${groups.length} งาน
                         </div>`
                      : ""
                  }
                </td>
              `;
            }

            if (!lane.groups.length) {
              html += renderHalfHourEmptyCells(totalHalfHourSlots, 0);
              bodyRows.push(`<tr>${html}</tr>`);
              return;
            }

            let cursor = 0;

            lane.groups.forEach((group) => {
              const gap = group.range.startIndex - cursor;

              if (gap > 0) {
                html += renderHalfHourEmptyCells(gap, cursor);
              }

              const endsAtHour = group.range.endIndex % 2 === 0;

              html += `
                <td
                  colspan="${group.range.span}"
                  class="schedule-time-cell
                         ${endsAtHour ? "schedule-hour-end" : ""}
                         p-1 bg-white align-top"
                >
                  ${renderScheduleSpanCard(group, miniCard)}
                </td>
              `;

              cursor = group.range.endIndex;
            });

            if (cursor < totalHalfHourSlots) {
              html += renderHalfHourEmptyCells(
                totalHalfHourSlots - cursor,
                cursor,
              );
            }

            bodyRows.push(`<tr>${html}</tr>`);
          });
        });

        els.workScheduleBody.innerHTML = bodyRows.join("");
      }

      function getWeekTechnicians() {
        // ตัด "all" (pseudo-technician รวมทุกคน) ออกเสมอ ส่วน "ยังไม่มอบหมาย
        // ช่าง" (unassigned) ให้แสดงเป็นแถวสุดท้ายของตาราง เพื่อให้เห็นงาน
        // ที่ยังไม่มีช่างรับผิดชอบด้วย ไม่ตกหล่นไปจากตารางทีมรายสัปดาห์
        const techs = mockData.technicians.filter(
          (t) => t.id !== "all" && t.id !== "unassigned",
        );
        const unassignedTech = mockData.technicians.find(
          (t) => t.id === "unassigned",
        );

        if (state.technicianId === "all") {
          return unassignedTech ? [...techs, unassignedTech] : techs;
        }
        if (state.technicianId === "unassigned") {
          return unassignedTech ? [unassignedTech] : [];
        }
        return techs.filter((t) => t.id === state.technicianId);
      }

      function getAllScheduleEntriesForWeek() {
        const days = getWeekDays(state.currentDate);
        const set = new Set(days.map((d) => d.ymd));
        const techSet = new Set(getWeekTechnicians().map((t) => t.id));
        return mockData.workSchedule
          .filter(
            (item) => set.has(item.date) && techSet.has(item.technicianId),
          )
          .sort((a, b) =>
            `${a.technicianId} ${a.date} ${a.start}`.localeCompare(
              `${b.technicianId} ${b.date} ${b.start}`,
            ),
          );
      }

      function renderTeamDayCell(tech, day, entries) {
        const items = getEntriesByTechAndDay(entries, tech.id, day.ymd);
        if (!items.length) {
          return `<td class="p-3 min-w-[150px] bg-white"><div class="min-h-[126px] rounded-2xl bg-slate-50/70 border border-dashed border-slate-200 flex items-center justify-center text-xs text-slate-300">ว่าง</div></td>`;
        }
        const groups = groupScheduleEntries(items);
        const hours = totalDurationHours(items);
        return `<td class="p-3 min-w-[150px] bg-white">
    <div class="mb-2 flex items-center justify-between gap-2">
      <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-[11px] font-medium">${items.length} งาน</span>
      <span class="text-[11px] text-slate-400">${hours.toFixed(1)} ชม.</span>
    </div>
    <div class="space-y-2">${groups.map((group) => renderScheduleGroupCard(group, true)).join("")}</div>
  </td>`;
      }

      function renderTeamWeeklySchedule() {
        const days = getWeekDays(state.currentDate);
        const weekStart = days[0].date;
        const weekEnd = days[6].date;
        const technicians = getWeekTechnicians();
        const realTechCount = technicians.filter(
          (t) => t.id !== "unassigned",
        ).length;
        const entries = getAllScheduleEntriesForWeek();
        const hours = totalDurationHours(entries);
        const completed = entries.filter(
          (i) => i.status === "completed",
        ).length;
        const cancelled = entries.filter((i) => i.status === "overdue").length;

        els.teamWeeklyTitle.textContent = `ตารางทีมรายสัปดาห์: ${fmtDate(weekStart)} - ${fmtDate(weekEnd)}`;
        els.teamWeeklySubtitle.textContent =
          state.technicianId === "all"
            ? "แสดงทุกช่างในทีม ชื่อช่างอยู่ด้านซ้าย และแต่ละช่องแสดงงานของวันนั้นตามช่วงเวลา"
            : `${getTech(state.technicianId).name} • โหมดเจาะลึกเฉพาะช่างที่เลือก โดยยังใช้รูปแบบชื่อช่างอยู่ด้านซ้าย`;

        const summary = [
          {
            label: "ช่างที่แสดง",
            value: realTechCount,
            hint: "คน",
            icon: "users",
            tone: "blue",
          },
          {
            label: "งานรวมทีม",
            value: entries.length,
            hint: "รายการ",
            icon: "clipboard-list",
            tone: "emerald",
          },
          {
            label: "ชั่วโมงรวม",
            value: hours.toFixed(1),
            hint: "ชม.",
            icon: "clock-3",
            tone: "amber",
          },
          {
            label: "งานที่ยกเลิก",
            value: cancelled,
            hint: `เสร็จแล้ว ${completed}`,
            icon: "ban",
            tone: "rose",
          },
        ];
        renderSummary(els.teamWeeklySummary, summary);

        els.teamWeeklyHead.innerHTML = `<tr>
    <th class="sticky-tech w-[310px] p-4 text-left bg-slate-100 text-slate-600 font-semibold rounded-tl-3xl">ชื่อช่าง</th>
    ${days
      .map(
        (
          day,
        ) => `<th class="min-w-[150px] p-4 text-center bg-slate-100 text-slate-600 font-semibold">
      <div>${day.name}</div>
      <div class="text-[11px] font-normal text-slate-400">${fmtShortDate(day.date)}</div>
    </th>`,
      )
      .join("")}
  </tr>`;

        if (!technicians.length) {
          els.teamWeeklyBody.innerHTML = `<tr><td colspan="8" class="p-8 text-center text-slate-400">ไม่พบข้อมูลช่าง</td></tr>`;
          return;
        }

        els.teamWeeklyBody.innerHTML = technicians
          .map((tech, index) => {
            const techEntries = getEntriesByTech(entries, tech.id);
            const techHours = totalDurationHours(techEntries);
            const statusCounts = countBy(techEntries, "status");
            const statusDots = renderStatusBadges(statusCounts);
            const isUnassigned = tech.id === "unassigned";
            // คั่นแถว "ยังไม่มอบหมายช่าง" ด้วยเส้นหนา + ระยะห่างเพิ่ม
            // เมื่อวางต่อท้ายรายชื่อช่างจริง ให้เห็นชัดว่าเป็นกลุ่มแยกต่างหาก
            const dividerClass =
              isUnassigned && index > 0 ? "border-t-4 border-t-amber-200" : "";
            return `<tr class="${dividerClass}">
      <td class="sticky-tech p-3 align-top bg-white min-w-[250px]">
        <div class="tech-row-card rounded-3xl border ${isUnassigned ? "border-amber-200 bg-amber-50/40" : "border-slate-100 bg-white"} p-3 shadow-sm">
          <div class="flex items-center gap-3">
            ${renderAvatarLabel(tech, "h-11 w-11 rounded-2xl text-sm")}
            <div class="min-w-0">
              <div class="font-bold text-slate-950 leading-snug break-words">${tech.name}</div>
              <div class="text-xs text-slate-400 leading-snug break-words">${tech.role}</div>
            </div>
          </div>
          <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
            <div class="rounded-2xl bg-slate-50 p-2"><span class="text-slate-400">งาน</span><div class="font-semibold text-slate-900">${techEntries.length}</div></div>
            <div class="rounded-2xl bg-slate-50 p-2"><span class="text-slate-400">ชม.</span><div class="font-semibold text-slate-900">${techHours.toFixed(1)}</div></div>
          </div>
          <div class="mt-2 flex flex-wrap gap-1">${statusDots || '<span class="text-xs text-slate-300">ยังไม่มีงาน</span>'}</div>
        </div>
      </td>
      ${days.map((day) => renderTeamDayCell(tech, day, entries)).join("")}
    </tr>`;
          })
          .join("");
      }

      function switchToMonth(month) {
        state.view = "month";
        state.currentDate = new Date(state.currentDate.getFullYear(), month, 1);
        render();
      }

      function openDayJobs(ymd) {
        const jobs = getFilteredJobs().filter(
          (j) => toYMD(new Date(j.workStart)) === ymd,
        );
        
        fireSwal({
          title: `ตารางงานวันที่ ${fmtDate(`${ymd}T00:00:00`)}`,
          // 👇 เพิ่มคลาส: max-h-[60vh] overflow-y-auto overscroll-contain pr-1.5
          html: `<div class="text-left space-y-2.5 p-1 max-h-[60vh] overflow-y-auto overflow-x-hidden pr-1.5 overscroll-contain">${jobs
            .map((j) => `
              <button onclick="Swal.close(); setTimeout(()=>openJobDetail('${j.id}', '${ymd}'),120)" 
                      class="group w-full flex flex-col p-3.5 bg-white border border-slate-200 rounded-2xl shadow-[0_2px_8px_-4px_rgba(0,0,0,0.1)] hover:shadow-md hover:border-blue-400 transition-all duration-300 text-left relative overflow-hidden">
                
                <div class="flex justify-between items-start w-full mb-3 gap-2">
                  <div class="flex-1 overflow-hidden">
                    <h4 class="text-sm font-semibold text-slate-800 truncate group-hover:text-blue-600 transition-colors">${j.title}</h4>
                    <div class="flex items-center text-xs text-slate-500 mt-1.5 font-medium">
                      <i data-lucide="clock" class="w-3.5 h-3.5 mr-1 text-slate-400"></i>
                      ${fmtTime(j.workStart)} - ${fmtTime(j.workEnd)}
                    </div>
                  </div>
                  <span class="shrink-0 px-2.5 py-1 rounded-lg text-[10px] font-bold border shadow-sm ${mockData.status[j.status].className}">
                    ${mockData.status[j.status].label}
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
            htmlContainer: '!overflow-hidden' // 👇 บังคับไม่ให้ Modal สร้าง Scroll ซ้อนกัน
          },
          didOpen: () => lucide.createIcons(),
        });
      }

      function openScheduleGroupDetail(groupId) {
        const group = currentScheduleGroups[groupId];
        if (!group) return;
        const tech = getTech(group.technicianId);
        const badges = renderStatusBadges(
          countBy(group.items, "status"),
          "text-[11px] font-medium",
        );

        fireSwal({
          width: 680,
          title: `<div class="text-left"><div class="text-xs text-slate-400 font-normal leading-tight">${fmtDate(combineDateTime(group.date, group.start))} ${group.start}-${group.end}</div><div class="text-base mt-0.5">งานซ้อน ${group.items.length} รายการ</div></div>`,
          html: `<div class="text-left space-y-3">
      
      <div class="rounded-xl bg-indigo-50 border border-indigo-100 p-3">
        <div class="text-sm font-semibold text-indigo-950">${tech.name}</div>
        <div class="text-[11px] text-indigo-700">${tech.role}</div>
        <div class="mt-2 flex flex-wrap gap-1.5">${badges}</div>
      </div>
      
      <div class="max-h-[360px] overflow-y-auto pr-1 space-y-2">
        ${group.items
          .map((item, idx) => {
            const status = mockData.status[item.status];
            const target = item.linkedJobId
              ? `openJobDetail('${item.linkedJobId}')`
              : `openScheduleDetail('${item.id}')`;
            return `<button onclick="${target}" class="w-full text-left rounded-xl border border-slate-100 bg-white p-3 hover:bg-slate-50 transition-colors">
            <div class="flex items-start justify-between gap-2">
              <div>
                <div class="text-[11px] text-slate-400">#${idx + 1} • ${item.id} • ${item.category}</div>
                <div class="mt-0.5 text-sm font-semibold text-slate-950">${item.title}</div>
                <div class="mt-0.5 text-[11px] text-slate-500">${item.location}</div>
              </div>
              <span class="shrink-0 inline-flex px-2.5 py-0.5 rounded-full border text-[11px] font-medium ${getScheduleStatusClass(item.status)}">${status?.label || item.status}</span>
            </div>
            <p class="mt-2 text-xs text-slate-600 line-clamp-2">${item.detail}</p>
            <div class="mt-2 flex flex-wrap gap-1.5">${(item.tools || []).map((tool) => `<span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[11px]">${tool}</span>`).join("")}</div>
          </button>`;
          })
          .join("")}
      </div>
      
    </div>`,
          showCloseButton: true,
          showConfirmButton: false,
        });
      }

      function openScheduleDetail(scheduleId) {
        const item = mockData.workSchedule.find((i) => i.id === scheduleId);
        if (!item) return;
        const tech = getTech(item.technicianId);
        const status = mockData.status[item.status];

        fireSwal({
          title: `<div class="text-left"><div class="text-xs text-slate-400 font-normal leading-tight">${item.id}</div><div class="text-base mt-0.5">${item.title}</div></div>`,
          html: `<div class="text-left space-y-3">
      
      <div class="flex items-center justify-between gap-2 rounded-xl bg-slate-50 p-3">
        <div>
          <div class="text-sm font-semibold text-slate-950">${tech.name}</div>
          <div class="text-[11px] text-slate-500">${tech.role}</div>
        </div>
        <span class="inline-flex px-2.5 py-1 rounded-full border text-[11px] font-medium ${getScheduleStatusClass(item.status)}">${status?.label || item.status}</span>
      </div>
      
      <div class="grid grid-cols-1 xs:grid-cols-2 gap-2 text-xs">
        <div class="rounded-xl border border-slate-100 p-2.5"><div class="text-[11px] text-slate-400 mb-0.5">วัน/เวลา</div><div class="font-medium">${
          item.spanStart && item.spanEnd
            ? `${fmtDate(item.spanStart, { time: true })} — ${fmtDate(item.spanEnd, { time: true })}`
            : `${fmtDate(combineDateTime(item.date, item.start))} ${item.start}-${item.end}`
        }${item.isContinuation || item.continuesNext ? `<div class="mt-0.5 text-[11px] font-normal text-slate-400">งานนี้กินเวลาข้ามวัน • วันนี้แสดงช่วง ${item.start}-${item.end}</div>` : ""}</div></div>
        <div class="rounded-xl border border-slate-100 p-2.5"><div class="text-[11px] text-slate-400 mb-0.5">ประเภทงาน</div><div class="font-medium">${item.category}</div></div>
        <div class="rounded-xl border border-slate-100 p-2.5 xs:col-span-2"><div class="text-[11px] text-slate-400 mb-0.5">สถานที่</div><div class="font-medium">${item.location}</div></div>
      </div>
      
      <div class="rounded-xl bg-white border border-slate-100 p-3">
        <h4 class="font-semibold text-sm text-slate-950">รายละเอียดงาน</h4>
        <p class="mt-1 text-xs text-slate-600">${item.detail}</p>
      </div>
      
      <div class="rounded-xl bg-indigo-50 border border-indigo-100 p-3">
        <h4 class="font-semibold text-sm text-indigo-900">อุปกรณ์/เครื่องมือที่เกี่ยวข้อง</h4>
        <div class="mt-1.5 flex flex-wrap gap-1.5">${(item.tools || []).map((tool) => `<span class="px-2.5 py-0.5 rounded-full bg-white text-indigo-700 text-[11px] font-medium shadow-sm">${tool}</span>`).join("") || '<span class="text-xs text-indigo-700">ไม่ระบุ</span>'}</div>
      </div>
      
    </div>`,
          width: 600,
          showCloseButton: true,
          showConfirmButton: false,
        });
      }

      // Carousel รูปภาพแบบเบา ๆ ไม่พึ่ง library ภายนอก ใช้กับ SweetAlert2 modal
      // เก็บ state (รายการรูป + index ปัจจุบัน) แยกตาม carouselId ไว้ที่ window
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
          return `<div>
      <h4 class="font-semibold text-sm text-slate-950 mb-1.5">${title}</h4>
      <div class="rounded-lg bg-slate-50 border border-dashed border-slate-200 p-4 text-xs text-slate-400 text-center">ไม่มีรูปภาพ</div>
    </div>`;
        }
        if (imgs.length === 1) {
          return `<div>
      <h4 class="font-semibold text-sm text-slate-950 mb-1.5">${title}</h4>
      <img src="${imgs[0]}" class="rounded-lg border border-slate-100 w-full h-48 object-cover" alt="${title}">
    </div>`;
        }
        // มีหลายรูป -> แสดงเป็น carousel เลื่อนดูทีละรูปพร้อมตัวนับ
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
        const status = mockData.status[job.status];

        // ไม่ได้ใช้ใน HTML ด้านล่าง สามารถลบออกหรือเก็บไว้ก็ได้
        const startDateText = job.workStart ? new Intl.DateTimeFormat('th-TH', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(job.workStart)) : '-';
        const endDateText = job.workEnd ? new Intl.DateTimeFormat('th-TH', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(job.workEnd)) : 'ยังไม่ระบุ';

        // --- เพิ่มส่วนคำนวณระยะเวลาการทำงาน ---
        let durationText = "-";
        if (job.workStart && job.workEnd) {
          const start = new Date(job.workStart);
          const end = new Date(job.workEnd);
          const diffMs = end - start;

          durationText = formatDurationText(diffMs);
        } else if (job.workDuration) {
          // รองรับกรณีที่คำนวณมาจากฝั่ง Backend (PHP) แล้วส่งมาในชื่อ workDuration
          durationText = job.workDuration;
        }
        // ------------------------------------

        const stockHtml = job.stockUsed.length
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
            <div class="rounded-xl border border-slate-100 p-2.5">
              <div class="text-[11px] text-slate-400 mb-0.5">วันที่แจ้งซ่อม</div>
              <div class="font-medium">${fmtDate(job.reportDate, { time: true })}</div>
            </div>
            <div class="rounded-xl border border-slate-100 p-2.5">
              <div class="text-[11px] text-slate-400 mb-0.5">ชื่อผู้แจ้ง / เบอร์โทร</div>
              <div class="font-medium">${job.reporter} • ${job.phone}</div>
            </div>
            <div class="rounded-xl border border-slate-100 p-2.5">
              <div class="text-[11px] text-slate-400 mb-0.5">สถานที่</div>
              <div class="font-medium">${job.location}</div>
            </div>
          </div>

          <div class="rounded-xl bg-white border border-slate-100 p-3">
            <h4 class="font-semibold text-sm text-slate-950">รายละเอียด/สาเหตุที่แจ้งซ่อม</h4>
            <p class="mt-1 text-xs text-slate-600">${job.issueDetail}</p>
          </div>

          ${renderImageSection("รูปภาพที่แจ้งซ่อม", job.beforeImages, `${jobIdSafe}_before`)}
        </div>

        <div class="space-y-3">
          
          <div class="flex flex-col xs:flex-row gap-2 xs:items-center xs:justify-between rounded-xl bg-slate-50 p-2.5">
            <div class="flex items-center gap-2.5">
              ${renderAvatarLabel(tech, "h-11 w-11 rounded-2xl text-sm")}
              <div>
                <div class="text-sm font-semibold text-slate-950 leading-tight">${tech.name}</div>
                <div class="text-[11px] text-slate-500 mt-0.5">${tech.role}</div>
              </div>
            </div>
            <span class="inline-flex w-fit px-2.5 py-1 rounded-full border text-[11px] font-medium ${status.className}">${status.label}</span>
          </div>

          <div class="rounded-xl border border-slate-100 p-2.5 text-xs flex justify-between items-center bg-white shadow-sm">
            <div>
              <div class="text-[11px] text-slate-400 mb-0.5">วันที่ช่างเข้าดำเนินการ</div>
              <div class="font-medium text-slate-700 mb-2">${fmtDate(job.workStart, { time: true })}</div>
              
              <div class="text-[11px] text-slate-400 mb-0.5">วันที่ดำเนินการสำเร็จ</div>
              <div class="font-medium text-slate-700">
                ${job.workEnd ? fmtDate(job.workEnd, { time: true }) : '-'}
              </div>
            </div>
            <div class="text-right pl-3 border-l border-slate-100">
              <div class="text-[11px] text-slate-400 mb-0.5">ใช้เวลาทำงาน</div>
              <div class="font-semibold text-indigo-600">${durationText}</div>
            </div>
          </div>

          <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-3">
            <h4 class="font-semibold text-sm text-emerald-900">วิธีที่ช่างแก้ไขปัญหา</h4>
            <p class="mt-1 text-xs text-emerald-800">${job.solution}</p>
          </div>

          ${renderImageSection("รูปภาพหลังแก้ไข", job.afterImages, `${jobIdSafe}_after`)}

          <div>
            <h4 class="font-semibold text-sm text-slate-950 mb-1.5">ข้อมูลอุปกรณ์ที่นำไปใช้จริง</h4>
            ${stockHtml}
          </div>
        </div>

      </div>`,
          width: 1000,
          showCloseButton: true,
          showConfirmButton: false,
          didOpen: () => lucide.createIcons(),
        });
      }

      function exportExcel() {
        const jobs = getFilteredJobs();
        if (!jobs.length) {
          fireSwal({
            icon: "info",
            title: "ไม่มีข้อมูลสำหรับ Export",
            text: "กรุณาเปลี่ยนช่วงเวลาหรือเลือกช่างใหม่",
            confirmButtonText: "ตกลง",
          });
          return;
        }

        const rows = jobs.flatMap((job) => {
          const base = {
            รหัสงาน: job.id,
            ชื่อช่าง: getJobTech(job).name,
            ประเภทงาน: job.type,
            สถานะ: mockData.status[job.status].label,
            ความสำคัญ: job.priority,
            วันที่แจ้งซ่อม: fmtDate(job.reportDate, { time: true }),
            วันที่เข้าดำเนินการ: fmtDate(job.workStart, { time: true }),
            "วันที่เสร็จ/สิ้นสุด": fmtDate(job.workEnd, { time: true }),
            ผู้แจ้ง: job.reporter,
            เบอร์โทร: job.phone,
            สถานที่: job.location,
            สาเหตุที่แจ้งซ่อม: job.issueDetail,
            วิธีแก้ไข: job.solution,
            คะแนน: job.rating || "-",
          };
          if (!job.stockUsed.length)
            return [
              { ...base, รหัสสต็อก: "-", อุปกรณ์ที่ใช้: "-", จำนวน: "-" },
            ];
          return job.stockUsed.map((item) => ({
            ...base,
            รหัสสต็อก: item.itemCode,
            อุปกรณ์ที่ใช้: item.itemName,
            จำนวน: `${item.qty} ${item.unit}`,
          }));
        });

        const ws = XLSX.utils.json_to_sheet(rows);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Technician Jobs");
        XLSX.writeFile(wb, `technician_jobs_${state.view}_${Date.now()}.xlsx`);
        fireSwal({
          icon: "success",
          title: "Export สำเร็จ",
          text: `ส่งออกข้อมูล ${jobs.length} งานเรียบร้อยแล้ว`,
          confirmButtonText: "ตกลง",
        });
      }

      function exportScheduleExcel() {
        const entries = getScheduleEntriesForWeek();
        if (!entries.length) {
          fireSwal({
            icon: "info",
            title: "ไม่มีตารางงานสำหรับ Export",
            text: "กรุณาเปลี่ยนช่วงวันที่หรือเลือกช่างใหม่",
            confirmButtonText: "ตกลง",
          });
          return;
        }
        const rows = entries.map((item) => ({
          รหัสตารางงาน: item.id,
          วันที่: fmtDate(combineDateTime(item.date, item.start)),
          เวลาเริ่ม: item.start,
          เวลาสิ้นสุด: item.end,
          ชื่อช่าง: getTech(item.technicianId).name,
          หัวข้องาน: item.title,
          ประเภท: item.category,
          สถานะ: mockData.status[item.status]?.label || item.status,
          สถานที่: item.location,
          รายละเอียด: item.detail,
          เลขที่ใบแจ้งซ่อม: item.linkedJobId || "-",
          "อุปกรณ์/เครื่องมือ": (item.tools || []).join(", "),
        }));
        const ws = XLSX.utils.json_to_sheet(rows);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Technician Schedule");
        XLSX.writeFile(wb, `technician_schedule_week_${Date.now()}.xlsx`);
        fireSwal({
          icon: "success",
          title: "Export ตารางงานสำเร็จ",
          text: `ส่งออกข้อมูล ${entries.length} รายการเรียบร้อยแล้ว`,
          confirmButtonText: "ตกลง",
        });
      }

      function exportTeamWeeklyExcel() {
        const entries = getAllScheduleEntriesForWeek();
        if (!entries.length) {
          fireSwal({
            icon: "info",
            title: "ไม่มีตารางทีมสำหรับ Export",
            text: "กรุณาเปลี่ยนสัปดาห์หรือเลือกช่างใหม่",
            confirmButtonText: "ตกลง",
          });
          return;
        }
        const rows = entries.map((item) => ({
          ชื่อช่าง: getTech(item.technicianId).name,
          วันที่: fmtDate(combineDateTime(item.date, item.start)),
          วัน: new Intl.DateTimeFormat("th-TH", { weekday: "long" }).format(
            new Date(combineDateTime(item.date, item.start)),
          ),
          เวลาเริ่ม: item.start,
          เวลาสิ้นสุด: item.end,
          หัวข้องาน: item.title,
          ประเภท: item.category,
          สถานะ: mockData.status[item.status]?.label || item.status,
          สถานที่: item.location,
          รายละเอียด: item.detail,
          เลขที่ใบแจ้งซ่อม: item.linkedJobId || "-",
          "อุปกรณ์/เครื่องมือ": (item.tools || []).join(", "),
        }));
        const ws = XLSX.utils.json_to_sheet(rows);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Team Weekly Schedule");
        XLSX.writeFile(wb, `team_weekly_schedule_${Date.now()}.xlsx`);
        fireSwal({
          icon: "success",
          title: "Export ตารางทีมสำเร็จ",
          text: `ส่งออกข้อมูล ${entries.length} รายการเรียบร้อยแล้ว`,
          confirmButtonText: "ตกลง",
        });
      }

      /*
       * ===== หน้าพิมพ์ตารางงาน (โหมด "agenda รายวัน") =====
       * เดิม openPrintWindow() จะ clone ตารางกริดรายชั่วโมง (48 คอลัมน์ย่อยครึ่งชม.)
       * ไปพิมพ์ตรง ๆ ทำให้ตัวหนังสือเล็กจิ๋วและล้นหลายหน้ากระดาษอ่านไม่ออก
       * จึงเปลี่ยนมาสร้างเอกสารพิมพ์ขึ้นใหม่จากข้อมูลจริง โดยจัดเป็นรายการ
       * งานต่อวัน (agenda) อ่านง่าย ไม่ตัดแถวกลางคัน และพิมพ์ได้พอดีหน้า A4
       */

      function escapeHtml(str) {
        return String(str ?? "").replace(
          /[&<>"']/g,
          (c) =>
            ({
              "&": "&amp;",
              "<": "&lt;",
              ">": "&gt;",
              '"': "&quot;",
              "'": "&#39;",
            })[c],
        );
      }

      function statusMeta(status) {
        return (
          mockData.status[status] || {
            label: status || "ไม่ระบุสถานะ",
            className: "bg-slate-50 text-slate-700 border-slate-200",
          }
        );
      }

      function buildStatusLegendHTML() {
        return Object.entries(mockData.status)
          .map(
            ([key, s]) => `
              <span class="legend-item">
                <span class="legend-dot status-${key}"></span>${escapeHtml(s.label)}
              </span>`,
          )
          .join("");
      }

      function buildPrintAgendaHTML(days, entries, { showTech = false } = {}) {
        const byDay = {};
        days.forEach((day) => (byDay[day.ymd] = []));
        entries.forEach((item) => {
          if (byDay[item.date]) byDay[item.date].push(item);
        });

        const colCount = showTech ? 5 : 4;

        return days
          .map((day) => {
            const items = (byDay[day.ymd] || []).slice().sort((a, b) =>
              a.start.localeCompare(b.start),
            );
            const hours = totalDurationHours(items);

            const rows = items.length
              ? items
                  .map((item) => {
                    const status = statusMeta(item.status);
                    const tech = getTech(item.technicianId);
                    return `
                      <tr>
                        <td class="col-time">${item.start}&ndash;${item.end}</td>
                        <td class="col-job">
                          <div class="job-id">${escapeHtml(item.linkedJobId || item.id)}</div>
                          <div class="job-title">${escapeHtml(item.title || "-")}</div>
                          ${item.category ? `<div class="job-cat">${escapeHtml(item.category)}</div>` : ""}
                        </td>
                        ${showTech ? `<td class="col-tech">${escapeHtml(tech.name)}</td>` : ""}
                        <td class="col-loc">${escapeHtml(item.location || "-")}</td>
                        <td class="col-status">
                          <span class="status-pill status-${escapeHtml(item.status || "")}">${escapeHtml(status.label)}</span>
                        </td>
                      </tr>`;
                  })
                  .join("")
              : `<tr><td class="empty-row" colspan="${colCount}">ไม่มีงานในวันนี้</td></tr>`;

            return `
              <section class="day-block">
                <div class="day-header">
                  <div class="day-name">${escapeHtml(day.name)} <span class="day-date">${escapeHtml(fmtShortDate(day.date))}</span></div>
                  <div class="day-meta">${items.length} งาน &bull; ${hours.toFixed(1)} ชม.</div>
                </div>
                <table class="agenda-table">
                  <thead>
                    <tr>
                      <th class="col-time">เวลา</th>
                      <th class="col-job">รายละเอียดงาน</th>
                      ${showTech ? `<th class="col-tech">ช่าง</th>` : ""}
                      <th class="col-loc">สถานที่</th>
                      <th class="col-status">สถานะ</th>
                    </tr>
                  </thead>
                  <tbody>${rows}</tbody>
                </table>
              </section>`;
          })
          .join("");
      }

      function openPrintWindow(title, subtitle, contentHtml) {
        const printWindow = window.open("", "_blank", "width=1200,height=800");
        if (!printWindow) {
          fireSwal({
            icon: "error",
            title: "ไม่สามารถเปิดหน้าต่างพิมพ์ได้",
            text: "กรุณาอนุญาต pop-up ของเบราว์เซอร์แล้วลองใหม่อีกครั้ง",
            confirmButtonText: "ตกลง",
          });
          return;
        }

        const legendHtml = buildStatusLegendHTML();
        const generatedAt = new Intl.DateTimeFormat("th-TH", {
          dateStyle: "long",
          timeStyle: "short",
        }).format(new Date());

        printWindow.document.write(`<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8" />
<title>${title}</title>
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&display=swap" rel="stylesheet" />
<style>
  * { box-sizing: border-box; }
  body {
    font-family: "Prompt", "Noto Sans Thai", sans-serif;
    margin: 22px;
    color: #1e293b;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }
  .doc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 16px;
    border-bottom: 2px solid #0f172a;
    padding-bottom: 10px;
    margin-bottom: 4px;
  }
  h1 { font-size: 19px; margin: 0 0 4px; color: #0f172a; }
  p.subtitle { font-size: 12px; color: #64748b; margin: 0; }
  .generated-at { font-size: 10px; color: #94a3b8; text-align: right; white-space: nowrap; }

  .legend {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin: 10px 0 16px;
    font-size: 10.5px;
    color: #475569;
  }
  .legend-item { display: inline-flex; align-items: center; gap: 5px; }
  .legend-dot { width: 8px; height: 8px; border-radius: 999px; display: inline-block; }
  .legend-dot.status-pending { background: #f59e0b; }
  .legend-dot.status-in_progress { background: #0ea5e9; }
  .legend-dot.status-completed { background: #10b981; }
  .legend-dot.status-overdue { background: #f43f5e; }

  .day-block { break-inside: avoid; page-break-inside: avoid; margin-bottom: 14px; }
  .day-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    background: #0f172a;
    color: #fff;
    padding: 6px 10px;
    border-radius: 6px 6px 0 0;
  }
  .day-name { font-size: 12.5px; font-weight: 600; }
  .day-date { font-size: 10.5px; font-weight: 400; opacity: 0.8; margin-left: 4px; }
  .day-meta { font-size: 10px; opacity: 0.85; white-space: nowrap; }

  table.agenda-table { border-collapse: collapse; width: 100%; font-size: 10.5px; table-layout: fixed; }
  .agenda-table th,
  .agenda-table td { border: 1px solid #e2e8f0; padding: 6px 8px; vertical-align: top; word-break: break-word; }
  .agenda-table thead th { background: #f1f5f9; text-align: left; font-weight: 600; color: #334155; font-size: 10px; }
  .agenda-table tbody tr:nth-child(even) { background: #f8fafc; }
  .agenda-table tr { break-inside: avoid; page-break-inside: avoid; }

  .col-time { width: 68px; text-align: center; font-weight: 600; white-space: nowrap; }
  .col-tech { width: 100px; }
  .col-loc { width: 130px; }
  .col-status { width: 92px; text-align: center; }
  .job-id { font-size: 9px; font-weight: 700; color: #64748b; letter-spacing: 0.02em; }
  .job-title { font-weight: 600; margin-top: 1px; }
  .job-cat { font-size: 9.5px; color: #94a3b8; margin-top: 1px; }
  .empty-row { text-align: center; color: #94a3b8; font-style: italic; padding: 10px; }

  .status-pill {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 9.5px;
    font-weight: 600;
    border: 1px solid transparent;
    white-space: nowrap;
  }
  .status-pending { background: #fffbeb; color: #b45309; border-color: #fde68a; }
  .status-in_progress { background: #f0f9ff; color: #0369a1; border-color: #bae6fd; }
  .status-completed { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
  .status-overdue { background: #fff1f2; color: #be123c; border-color: #fecdd3; }

  @media print {
    body { margin: 12mm; }
    @page { size: A4 portrait; margin: 10mm; }
  }
</style>
</head>
<body>
  <div class="doc-header">
    <div>
      <h1>${title}</h1>
      <p class="subtitle">${subtitle}</p>
    </div>
    <div class="generated-at">พิมพ์เมื่อ ${generatedAt}</div>
  </div>
  <div class="legend">${legendHtml}</div>
  ${contentHtml}
</body>
</html>`);
        printWindow.document.close();
        printWindow.onload = () => {
          printWindow.focus();
          printWindow.print();
        };
      }

      function printWorkSchedule() {
        const days = getWeekDays(state.currentDate);
        const entries = getScheduleEntriesForWeek();
        const title = els.workScheduleTitle.textContent;
        const subtitle = els.workScheduleSubtitle.textContent;
        const showTech = state.technicianId === "all";
        const contentHtml = buildPrintAgendaHTML(days, entries, { showTech });
        openPrintWindow(title, subtitle, contentHtml);
      }

      function printTeamWeeklySchedule() {
        const days = getWeekDays(state.currentDate);
        const entries = getAllScheduleEntriesForWeek();
        const title = els.teamWeeklyTitle.textContent;
        const subtitle = els.teamWeeklySubtitle.textContent;
        const contentHtml = buildPrintAgendaHTML(days, entries, {
          showTech: true,
        });
        openPrintWindow(title, subtitle, contentHtml);
      }

      window.openJobDetail = openJobDetail;
      window.openDayJobs = openDayJobs;
      window.switchToMonth = switchToMonth;
      window.openScheduleDetail = openScheduleDetail;
      window.openScheduleGroupDetail = openScheduleGroupDetail;
      window.moveWeek = moveWeek;

      init();
    </script>
  </body>
</html>