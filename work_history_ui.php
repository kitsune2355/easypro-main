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
        overflow-x: auto;
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

      .schedule-card {
        transition: all 0.18s ease;
      }
      .schedule-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.1);
      }

      .team-weekly-shell {
        overflow-x: auto;
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
    </style>
  </head>
  <body class="font-prompt text-slate-800 text-sm">
    <div
      class="flex flex-col antialiased text-slate-700 h-screen w-full relative"
    >
      <header
        class="sticky top-0 z-50 w-full bg-white shadow-sm flex flex-col flex-none"
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

      <main class="relative z-10 px-4 pb-4">
        <section class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-4">
          <aside
            class="md:col-span-3 lg:col-span-2 space-y-4 md:sticky md:top-36 h-fit"
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
                    <div class="flex flex-wrap gap-1.5 text-[11px]">
                      <span
                        class="px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 font-medium"
                        >คลิกงานเพื่อดูรายละเอียด</span
                      >
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
                    class="bg-white rounded-xl shadow-card border border-slate-100 p-3"
                  >
                    <h2 class="text-base font-bold text-slate-950">
                      จำนวนงานรายช่าง
                    </h2>
                    <div class="mt-3 h-[200px]">
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
                  <div class="flex flex-wrap gap-1.5 text-[11px]">
                    <span
                      class="px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 font-medium"
                      >คลิกการ์ดเพื่อดูรายละเอียด</span
                    >
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
                class="bg-white rounded-xl shadow-card border border-slate-100 overflow-hidden"
              >
                <div
                  class="p-3 border-b border-slate-100 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-2"
                >
                  <div>
                    <div
                      class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-[10px] font-semibold"
                    >
                      ตารางงานรายสัปดาห์
                    </div>
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
                      id="btnExportSchedule"
                      class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white text-[11px] font-semibold shadow-sm hover:bg-indigo-700"
                    >
                      Export ตารางงาน
                    </button>
                  </div>
                </div>

                <div class="p-3 flex flex-col xl:flex-row gap-3">
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
                    class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-1 gap-2 xl:w-48 xl:shrink-0"
                  ></div>
                </div>
              </div>
            </div>

            <div id="tab-team" class="tab-panel hidden">
              <div
                id="teamWeeklyPanel"
                class="bg-white rounded-xl shadow-card border border-slate-100 overflow-hidden"
              >
                <div
                  class="p-3 border-b border-slate-100 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-2"
                >
                  <div>
                    <div
                      class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-semibold"
                    >
                      ตารางงานรายสัปดาห์แบบทีม
                    </div>
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
                    >
                      ชื่อช่างอยู่ด้านซ้าย วันอยู่ด้านบน
                      เหมาะสำหรับดูภาพรวมทั้งทีมและงานซ้อนในแต่ละวัน
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
                      id="btnExportTeamWeekly"
                      class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white text-[11px] font-semibold shadow-sm hover:bg-emerald-700"
                    >
                      Export ตารางทีม
                    </button>
                  </div>
                </div>

                <div class="p-3 flex flex-col xl:flex-row gap-3">
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
                    class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-1 gap-2 xl:w-48 xl:shrink-0"
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
            id: "t01",
            name: "ช่างสมชาย ใจดี",
            role: "ไฟฟ้า / ระบบแสงสว่าง",
            avatar: "https://placehold.co/120x120/dbeafe/1d4ed8?text=SC",
          },
          {
            id: "t02",
            name: "ช่างอนันต์ พรชัย",
            role: "ประปา / สุขภัณฑ์",
            avatar: "https://placehold.co/120x120/dcfce7/15803d?text=AN",
          },
          {
            id: "t03",
            name: "ช่างวิทยา แก้วใส",
            role: "แอร์ / เครื่องทำความเย็น",
            avatar: "https://placehold.co/120x120/fef3c7/b45309?text=WT",
          },
        ],
        status: {
          pending: {
            label: "รออะไหล่/รอดำเนินการ",
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
        workSchedule: [
          {
            id: "SCH-2607-001",
            technicianId: "t01",
            date: "2026-07-06",
            start: "08:00",
            end: "09:00",
            title: "ตรวจเช็กไฟฟ้าอาคาร A",
            category: "PM",
            status: "completed",
            location: "อาคาร A ทุกชั้น",
            detail: "เดินตรวจแสงสว่าง ปลั๊กไฟ และตู้ควบคุมย่อยประจำสัปดาห์",
            tools: ["ไขควงวัดไฟ", "มัลติมิเตอร์"],
          },
          {
            id: "SCH-2607-002",
            technicianId: "t01",
            date: "2026-07-06",
            start: "13:00",
            end: "15:00",
            title: "สำรวจจุดติดตั้งโคมไฟ",
            category: "สำรวจงาน",
            status: "completed",
            location: "ทางเดินอาคาร C",
            detail: "สำรวจพื้นที่ก่อนเสนอเบิกโคมไฟและอุปกรณ์เดินสาย",
            tools: ["ตลับเมตร", "แบบฟอร์มสำรวจ"],
          },
          {
            id: "SCH-2607-003",
            technicianId: "t02",
            date: "2026-07-06",
            start: "09:00",
            end: "11:00",
            title: "ตรวจระบบน้ำชั้น 1-2",
            category: "PM",
            status: "completed",
            location: "อาคาร B",
            detail: "ตรวจแรงดันน้ำ วาล์ว และจุดรั่วซึมตามรอบงานประจำ",
            tools: ["ประแจ", "เทปพันเกลียว"],
          },
          {
            id: "SCH-2607-004",
            technicianId: "t03",
            date: "2026-07-06",
            start: "10:00",
            end: "12:00",
            title: "ล้างฟิลเตอร์แอร์สำนักงาน",
            category: "PM",
            status: "completed",
            location: "สำนักงานชั้น 3",
            detail: "ถอดล้างฟิลเตอร์แอร์และตรวจอุณหภูมิหลังล้าง",
            tools: ["น้ำยาล้างคอยล์", "เครื่องเป่าลม"],
          },
          {
            id: "SCH-2607-005",
            technicianId: "t02",
            date: "2026-07-07",
            start: "08:00",
            end: "10:00",
            title: "แก้ไขกลิ่นท่อระบายน้ำ",
            category: "ซ่อมทั่วไป",
            status: "completed",
            location: "ครัวกลาง",
            detail: "ตรวจท่อดักกลิ่นและทำความสะอาดท่อระบายน้ำ",
            tools: ["น้ำยาล้างท่อ", "สายทะลวงท่อ"],
          },
          {
            id: "SCH-2607-006",
            technicianId: "t03",
            date: "2026-07-07",
            start: "13:00",
            end: "16:00",
            title: "เช็กแอร์ห้องอบรม",
            category: "ตรวจเช็ก",
            status: "completed",
            location: "ห้องอบรมใหญ่",
            detail: "ตรวจเสียงและวัดอุณหภูมิเครื่องปรับอากาศ 3 เครื่อง",
            tools: ["เทอร์โมมิเตอร์", "มัลติมิเตอร์"],
          },
          {
            id: "SCH-2607-007",
            technicianId: "t01",
            date: "2026-07-08",
            start: "14:00",
            end: "15:00",
            title: "ปลั๊กไฟ Pantry ชำรุด",
            category: "ใบแจ้งซ่อม",
            status: "completed",
            linkedJobId: "JOB-2607-004",
            location: "Pantry ชั้น 3",
            detail: "งานจากใบแจ้งซ่อม JOB-2607-004",
            tools: ["เต้ารับคู่มีกราวด์"],
          },
          {
            id: "SCH-2607-008A",
            technicianId: "t01",
            date: "2026-07-08",
            start: "09:00",
            end: "10:00",
            title: "ซ่อมไฟห้องประชุมย่อย",
            category: "ใบแจ้งซ่อม",
            status: "completed",
            location: "อาคาร A / ห้องประชุมย่อย",
            detail:
              "ผู้แจ้งแจ้งว่าหลอดไฟกระพริบ 2 จุด ช่างตรวจสอบและเปลี่ยนหลอด LED เรียบร้อย",
            tools: ["หลอด LED 18W", "ไขควงวัดไฟ"],
          },
          {
            id: "SCH-2607-008B",
            technicianId: "t01",
            date: "2026-07-08",
            start: "09:00",
            end: "10:00",
            title: "ตรวจปลั๊กโต๊ะประชาสัมพันธ์",
            category: "ใบแจ้งซ่อม",
            status: "in_progress",
            location: "อาคารหน้า / ประชาสัมพันธ์",
            detail:
              "ปลั๊กไฟหลวม เสียบอุปกรณ์แล้วไฟติดดับ อยู่ระหว่างตรวจโหลดไฟ",
            tools: ["มัลติมิเตอร์", "เทปพันสายไฟ"],
          },
          {
            id: "SCH-2607-008C",
            technicianId: "t01",
            date: "2026-07-08",
            start: "09:00",
            end: "10:00",
            title: "เปลี่ยนสวิตช์ไฟหน้าห้อง HR",
            category: "ใบแจ้งซ่อม",
            status: "completed",
            location: "อาคาร B / ห้อง HR",
            detail:
              "สวิตช์กดแล้วไม่เด้งกลับ เปลี่ยนสวิตช์ใหม่และทดสอบใช้งานปกติ",
            tools: ["สวิตช์ไฟ 1 ทาง", "ไขควง"],
          },
          {
            id: "SCH-2607-008D",
            technicianId: "t01",
            date: "2026-07-08",
            start: "09:00",
            end: "10:00",
            title: "ไฟป้ายหน้าอาคารไม่ติด",
            category: "ใบแจ้งซ่อม",
            status: "pending",
            location: "หน้าอาคารสำนักงาน",
            detail: "ตรวจพบชุด Driver LEDเสีย ต้องรอเบิกอะไหล่จากสต็อกกลาง",
            tools: ["Driver LED", "บันได"],
          },
          {
            id: "SCH-2607-008E",
            technicianId: "t01",
            date: "2026-07-08",
            start: "09:00",
            end: "10:00",
            title: "ตรวจ UPS ห้อง Server",
            category: "ตรวจเช็ก",
            status: "completed",
            location: "ห้อง Server",
            detail:
              "ตรวจค่าแบตเตอรี่ UPS และทดสอบสำรองไฟเบื้องต้น ผลอยู่ในเกณฑ์ปกติ",
            tools: ["เครื่องวัดไฟ", "แบบฟอร์ม PM"],
          },
          {
            id: "SCH-2607-008F",
            technicianId: "t01",
            date: "2026-07-08",
            start: "09:00",
            end: "10:00",
            title: "แก้ไขเบรกเกอร์ย่อยทริป",
            category: "ใบแจ้งซ่อม",
            status: "overdue",
            location: "อาคาร C / ชั้น 2",
            detail:
              "เบรกเกอร์ทริปซ้ำหลังใช้งานเครื่องถ่ายเอกสาร ต้องตรวจวงจรเพิ่มและนัดปิดระบบช่วงเย็น",
            tools: ["มัลติมิเตอร์", "แคลมป์มิเตอร์"],
          },
          {
            id: "SCH-2607-008G",
            technicianId: "t02",
            date: "2026-07-08",
            start: "09:00",
            end: "10:00",
            title: "ซ่อมก๊อกน้ำรั่ว",
            category: "ใบแจ้งซ่อม",
            status: "completed",
            location: "ห้องน้ำหญิง ชั้น 1",
            detail: "เปลี่ยนยางโอริงและขันข้อต่อใหม่",
            tools: ["ประแจ", "ยางโอริง"],
          },
          {
            id: "SCH-2607-008",
            technicianId: "t01",
            date: "2026-07-08",
            start: "15:00",
            end: "16:00",
            title: "บันทึกสรุปงานและคืนอะไหล่",
            category: "เอกสาร",
            status: "completed",
            location: "ห้องช่าง",
            detail: "บันทึกผลการซ่อม อัปเดตสต็อก และคืนอุปกรณ์ที่ไม่ได้ใช้",
            tools: ["ระบบสต็อก"],
          },
          {
            id: "SCH-2607-009",
            technicianId: "t02",
            date: "2026-07-10",
            start: "09:00",
            end: "12:00",
            title: "ชักโครกกดน้ำไม่ลง",
            category: "ใบแจ้งซ่อม",
            status: "overdue",
            linkedJobId: "JOB-2607-005",
            location: "อาคาร A / ห้องน้ำชาย ชั้น 4",
            detail: "งานจากใบแจ้งซ่อม JOB-2607-005",
            tools: ["สายทะลวงท่อ", "น้ำยาแก้ท่อตัน"],
          },
          {
            id: "SCH-2607-010",
            technicianId: "t03",
            date: "2026-07-10",
            start: "13:00",
            end: "15:00",
            title: "เตรียมอะไหล่งานแอร์",
            category: "จัดเตรียม",
            status: "pending",
            location: "ห้องสต็อก",
            detail:
              "ตรวจสต็อกน้ำยาแอร์ ฟิลเตอร์ และอุปกรณ์ล้างคอยล์สำหรับงานสุดสัปดาห์",
            tools: ["ใบเบิกสต็อก"],
          },
          {
            id: "SCH-2607-011",
            technicianId: "t03",
            date: "2026-07-12",
            start: "09:00",
            end: "13:00",
            title: "ล้างแอร์ห้อง Server",
            category: "ใบแจ้งซ่อม/PM",
            status: "completed",
            linkedJobId: "JOB-2607-006",
            location: "ห้อง Server",
            detail: "งานบำรุงรักษาตามรอบ PM จาก JOB-2607-006",
            tools: ["น้ำยาล้างคอยล์", "ฟิลเตอร์แอร์"],
          },
          {
            id: "SCH-2607-012",
            technicianId: "t01",
            date: "2026-07-16",
            start: "09:00",
            end: "10:00",
            title: "ตรวจตู้ไฟ MDB",
            category: "ใบแจ้งซ่อม",
            status: "pending",
            linkedJobId: "JOB-2607-007",
            location: "ห้องไฟฟ้าหลัก",
            detail: "ตรวจจุดต่อสายและรออะไหล่เบรกเกอร์รุ่นเฉพาะ",
            tools: ["มัลติมิเตอร์", "กล้องถ่ายความร้อน"],
          },
        ],
        jobs: [
          {
            id: "JOB-2607-001",
            technicianId: "t01",
            title: "ซ่อมไฟห้องประชุมชั้น 2",
            type: "ไฟฟ้า",
            priority: "สูง",
            status: "completed",
            reportDate: "2026-07-01T09:12:00",
            workStart: "2026-07-02T09:00:00",
            workEnd: "2026-07-02T11:20:00",
            dueDate: "2026-07-02T17:00:00",
            reporter: "คุณกานดา",
            phone: "089-111-2222",
            location: "อาคาร A / ห้องประชุม 2",
            issueDetail: "หลอดไฟไม่ติด 4 จุด และสวิตช์มีอาการหลวม",
            beforeImages: [
              "https://placehold.co/520x320/111827/facc15?text=Before+Light+Issue",
            ],
            solution:
              "เปลี่ยนหลอด LED 18W จำนวน 4 หลอด ตรวจเช็กสายไฟและยึดสวิตช์ใหม่",
            afterImages: [
              "https://placehold.co/520x320/ecfdf5/059669?text=After+Fixed",
            ],
            stockUsed: [
              {
                itemCode: "STK-ELE-001",
                itemName: "หลอด LED 18W",
                qty: 4,
                unit: "หลอด",
              },
              {
                itemCode: "STK-ELE-014",
                itemName: "เทปพันสายไฟ",
                qty: 1,
                unit: "ม้วน",
              },
            ],
            rating: 5,
          },
          {
            id: "JOB-2607-002",
            technicianId: "t02",
            title: "ท่อน้ำรั่วใต้อ่างล้างมือ",
            type: "ประปา",
            priority: "กลาง",
            status: "completed",
            reportDate: "2026-07-03T08:34:00",
            workStart: "2026-07-03T13:00:00",
            workEnd: "2026-07-03T15:10:00",
            dueDate: "2026-07-04T12:00:00",
            reporter: "คุณวราภรณ์",
            phone: "081-333-4444",
            location: "อาคาร B / ห้องน้ำหญิง ชั้น 1",
            issueDetail: "มีน้ำหยดใต้อ่างล้างมือ ทำให้พื้นเปียกตลอดเวลา",
            beforeImages: [
              "https://placehold.co/520x320/f8fafc/0284c7?text=Before+Pipe+Leak",
            ],
            solution:
              "เปลี่ยนข้อต่อ PVC และซีลยางใหม่ ทดสอบแรงดันน้ำไม่พบรั่วซึม",
            afterImages: [
              "https://placehold.co/520x320/ecfeff/0891b2?text=After+Pipe+Fixed",
            ],
            stockUsed: [
              {
                itemCode: "STK-PLB-008",
                itemName: "ข้อต่อ PVC 1/2 นิ้ว",
                qty: 2,
                unit: "ชิ้น",
              },
              {
                itemCode: "STK-PLB-012",
                itemName: "ซีลยางกันรั่ว",
                qty: 2,
                unit: "ชิ้น",
              },
            ],
            rating: 4,
          },
          {
            id: "JOB-2607-003",
            technicianId: "t03",
            title: "แอร์ไม่เย็น ห้องบัญชี",
            type: "แอร์",
            priority: "สูง",
            status: "in_progress",
            reportDate: "2026-07-05T10:18:00",
            workStart: "2026-07-05T14:00:00",
            workEnd: "2026-07-05T16:30:00",
            dueDate: "2026-07-06T17:00:00",
            reporter: "คุณธนากร",
            phone: "086-555-7777",
            location: "อาคาร C / ห้องบัญชี",
            issueDetail: "แอร์เปิดได้แต่ลมไม่เย็น มีเสียงดังบริเวณคอยล์เย็น",
            beforeImages: [
              "https://placehold.co/520x320/e0f2fe/0369a1?text=Before+AC",
            ],
            solution:
              "ล้างแผ่นกรอง ตรวจน้ำยาแอร์ พบต้องเติมน้ำยาเพิ่ม รออนุมัติใช้อะไหล่",
            afterImages: [
              "https://placehold.co/520x320/f0f9ff/0284c7?text=Work+In+Progress",
            ],
            stockUsed: [
              {
                itemCode: "STK-AC-002",
                itemName: "น้ำยาล้างคอยล์",
                qty: 1,
                unit: "ขวด",
              },
            ],
            rating: 0,
          },
          {
            id: "JOB-2607-004",
            technicianId: "t01",
            title: "ปลั๊กไฟชำรุดบริเวณ Pantry",
            type: "ไฟฟ้า",
            priority: "กลาง",
            status: "completed",
            reportDate: "2026-07-08T11:06:00",
            workStart: "2026-07-08T14:00:00",
            workEnd: "2026-07-08T15:00:00",
            dueDate: "2026-07-08T18:00:00",
            reporter: "คุณปิยะ",
            phone: "082-999-1111",
            location: "Pantry ชั้น 3",
            issueDetail: "ปลั๊กไฟแตกร้าว เสียบอุปกรณ์แล้วไฟติดๆดับๆ",
            beforeImages: [
              "https://placehold.co/520x320/fff7ed/ea580c?text=Before+Socket",
            ],
            solution: "ตัดไฟ เปลี่ยนเต้ารับใหม่ ตรวจสอบโหลดไฟฟ้าใช้งานได้ปกติ",
            afterImages: [
              "https://placehold.co/520x320/f0fdf4/16a34a?text=After+Socket",
            ],
            stockUsed: [
              {
                itemCode: "STK-ELE-021",
                itemName: "เต้ารับคู่มีกราวด์",
                qty: 1,
                unit: "ชุด",
              },
            ],
            rating: 5,
          },
          {
            id: "JOB-2607-005",
            technicianId: "t02",
            title: "ชักโครกกดน้ำไม่ลง",
            type: "ประปา",
            priority: "สูง",
            status: "overdue",
            reportDate: "2026-07-10T09:40:00",
            workStart: "2026-07-11T10:00:00",
            workEnd: "2026-07-11T11:30:00",
            dueDate: "2026-07-10T18:00:00",
            reporter: "คุณสุรีย์",
            phone: "088-221-0099",
            location: "อาคาร A / ห้องน้ำชาย ชั้น 4",
            issueDetail: "ชักโครกตัน กดน้ำแล้วน้ำเอ่อล้น",
            beforeImages: [
              "https://placehold.co/520x320/fef2f2/e11d48?text=Before+Toilet",
            ],
            solution: "ใช้สายทะลวงท่อและน้ำยาแก้ท่อตัน ตรวจสอบพบมีสิ่งอุดตัน",
            afterImages: [
              "https://placehold.co/520x320/ecfdf5/059669?text=After+Toilet",
            ],
            stockUsed: [
              {
                itemCode: "STK-PLB-019",
                itemName: "น้ำยาแก้ท่อตัน",
                qty: 1,
                unit: "ขวด",
              },
            ],
            rating: 3,
          },
          {
            id: "JOB-2607-006",
            technicianId: "t03",
            title: "ล้างแอร์ประจำเดือน ห้อง Server",
            type: "PM",
            priority: "กลาง",
            status: "completed",
            reportDate: "2026-07-12T08:00:00",
            workStart: "2026-07-12T09:00:00",
            workEnd: "2026-07-12T12:30:00",
            dueDate: "2026-07-12T17:00:00",
            reporter: "IT Support",
            phone: "02-555-1212",
            location: "ห้อง Server",
            issueDetail: "งานบำรุงรักษาเชิงป้องกันตามรอบ PM",
            beforeImages: [
              "https://placehold.co/520x320/f8fafc/334155?text=Before+PM",
            ],
            solution:
              "ล้างคอยล์เย็น คอยล์ร้อน ตรวจวัดอุณหภูมิหลังทำงาน อยู่ในเกณฑ์ปกติ",
            afterImages: [
              "https://placehold.co/520x320/f0fdf4/16a34a?text=After+PM",
            ],
            stockUsed: [
              {
                itemCode: "STK-AC-002",
                itemName: "น้ำยาล้างคอยล์",
                qty: 2,
                unit: "ขวด",
              },
              {
                itemCode: "STK-AC-007",
                itemName: "ฟิลเตอร์แอร์",
                qty: 1,
                unit: "ชุด",
              },
            ],
            rating: 5,
          },
          {
            id: "JOB-2607-007",
            technicianId: "t01",
            title: "ตรวจตู้ไฟ MDB",
            type: "ไฟฟ้า",
            priority: "สูง",
            status: "pending",
            reportDate: "2026-07-15T13:20:00",
            workStart: "2026-07-16T09:00:00",
            workEnd: "2026-07-16T10:15:00",
            dueDate: "2026-07-16T18:00:00",
            reporter: "คุณภาคิน",
            phone: "087-101-2020",
            location: "ห้องไฟฟ้าหลัก",
            issueDetail: "เบรกเกอร์ย่อยมีความร้อนสูงกว่าปกติ",
            beforeImages: [
              "https://placehold.co/520x320/fffbeb/d97706?text=Before+MDB",
            ],
            solution:
              "ตรวจสอบจุดต่อสาย พบควรรอเปลี่ยนเบรกเกอร์รุ่นเฉพาะ กำลังรออะไหล่",
            afterImages: [
              "https://placehold.co/520x320/fef3c7/b45309?text=Waiting+Parts",
            ],
            stockUsed: [],
            rating: 0,
          },
          {
            id: "JOB-2606-008",
            technicianId: "t02",
            title: "ซ่อมก๊อกน้ำห้องครัว",
            type: "ประปา",
            priority: "ต่ำ",
            status: "completed",
            reportDate: "2026-06-20T10:15:00",
            workStart: "2026-06-20T13:30:00",
            workEnd: "2026-06-20T14:25:00",
            dueDate: "2026-06-21T17:00:00",
            reporter: "คุณนิดา",
            phone: "080-000-4444",
            location: "ครัวกลาง",
            issueDetail: "ก๊อกน้ำหมุนปิดไม่สนิท มีน้ำหยดตลอดเวลา",
            beforeImages: [
              "https://placehold.co/520x320/e0f2fe/0369a1?text=Before+Faucet",
            ],
            solution: "เปลี่ยนยางโอริงและหัวก๊อกใหม่",
            afterImages: [
              "https://placehold.co/520x320/ecfdf5/059669?text=After+Faucet",
            ],
            stockUsed: [
              {
                itemCode: "STK-PLB-006",
                itemName: "ยางโอริง",
                qty: 3,
                unit: "ชิ้น",
              },
              {
                itemCode: "STK-PLB-010",
                itemName: "หัวก๊อกน้ำ",
                qty: 1,
                unit: "ชุด",
              },
            ],
            rating: 4,
          },
          {
            id: "JOB-2605-009",
            technicianId: "t03",
            title: "เติมน้ำยาแอร์ ห้องประชาสัมพันธ์",
            type: "แอร์",
            priority: "กลาง",
            status: "completed",
            reportDate: "2026-05-18T09:00:00",
            workStart: "2026-05-18T10:00:00",
            workEnd: "2026-05-18T11:40:00",
            dueDate: "2026-05-19T17:00:00",
            reporter: "คุณน้ำฝน",
            phone: "085-123-4567",
            location: "อาคารหน้า / ประชาสัมพันธ์",
            issueDetail: "อุณหภูมิไม่ลด แม้ตั้งไว้ที่ 24 องศา",
            beforeImages: [
              "https://placehold.co/520x320/e0f2fe/0284c7?text=Before+Gas",
            ],
            solution:
              "ตรวจหารอยรั่วและเติมน้ำยาแอร์ วัดอุณหภูมิหลังซ่อม 23.8 องศา",
            afterImages: [
              "https://placehold.co/520x320/ecfdf5/059669?text=After+Gas",
            ],
            stockUsed: [
              {
                itemCode: "STK-AC-009",
                itemName: "น้ำยาแอร์ R32",
                qty: 1,
                unit: "กก.",
              },
            ],
            rating: 5,
          },
          {
            id: "JOB-2604-010",
            technicianId: "t01",
            title: "ติดตั้งโคมไฟทางเดิน",
            type: "ไฟฟ้า",
            priority: "ต่ำ",
            status: "completed",
            reportDate: "2026-04-09T15:20:00",
            workStart: "2026-04-10T09:00:00",
            workEnd: "2026-04-10T12:00:00",
            dueDate: "2026-04-11T17:00:00",
            reporter: "ฝ่ายอาคาร",
            phone: "02-111-2222",
            location: "ทางเดินชั้น 5",
            issueDetail: "เพิ่มแสงสว่างบริเวณทางเดินเพื่อความปลอดภัย",
            beforeImages: [
              "https://placehold.co/520x320/f8fafc/475569?text=Before+Install",
            ],
            solution: "ติดตั้งโคมไฟ LED 3 จุด เดินสายไฟเข้าท่อร้อยสายเรียบร้อย",
            afterImages: [
              "https://placehold.co/520x320/ecfdf5/059669?text=After+Install",
            ],
            stockUsed: [
              {
                itemCode: "STK-ELE-031",
                itemName: "โคมไฟ LED",
                qty: 3,
                unit: "ชุด",
              },
              {
                itemCode: "STK-ELE-041",
                itemName: "ท่อร้อยสายไฟ",
                qty: 6,
                unit: "เมตร",
              },
            ],
            rating: 4,
          },
        ],
      };

      const TODAY = "2026-07-08T09:00:00";
      const byId = (id) => document.getElementById(id);

      const state = {
        technicianId: "all",
        view: "month",
        currentDate: new Date(TODAY),
        activeTab: "calendar",
      };

      const els = {
        technicianSelect: byId("technicianSelect"),
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
      const getTech = (id) =>
        mockData.technicians.find((t) => t.id === id) ||
        mockData.technicians[0];
      const getJobTech = (job) => getTech(job.technicianId);
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
      ];

      function isFullscreenTarget(target) {
        return document.fullscreenElement === target;
      }

      async function toggleFullscreen(target) {
        if (!target?.requestFullscreen) {
          Swal.fire({
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

      function init() {
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
        document.querySelectorAll(".view-btn").forEach((btn) =>
          btn.addEventListener("click", () => {
            state.view = btn.dataset.view;
            syncDateInput();
            render();
          }),
        );
        els.datePicker.addEventListener("change", (e) => {
          if (state.view === "day")
            state.currentDate = new Date(`${e.target.value}T09:00:00`);
          if (state.view === "month")
            state.currentDate = new Date(`${e.target.value}-01T09:00:00`);
          if (state.view === "year")
            state.currentDate = new Date(`${e.target.value}-01-01T09:00:00`);
          render();
        });
        byId("btnToday").addEventListener("click", () => {
          state.currentDate = new Date(TODAY);
          syncDateInput();
          render();
        });
        byId("btnReset").addEventListener("click", () => {
          state.technicianId = "all";
          state.view = "month";
          state.currentDate = new Date(TODAY);
          els.technicianSelect.value = "all";
          syncDateInput();
          render();
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
        render();
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
            const work = new Date(job.workStart);
            const matchTech =
              state.technicianId === "all" ||
              job.technicianId === state.technicianId;
            let matchDate = true;
            if (state.view === "day")
              matchDate = toYMD(work) === toYMD(state.currentDate);
            if (state.view === "month")
              matchDate =
                work.getFullYear() === state.currentDate.getFullYear() &&
                work.getMonth() === state.currentDate.getMonth();
            if (state.view === "year")
              matchDate =
                work.getFullYear() === state.currentDate.getFullYear();
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
        const totalHours = jobs.reduce(
          (sum, j) =>
            sum + (new Date(j.workEnd) - new Date(j.workStart)) / 36e5,
          0,
        );
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
        for (let i = 0; i < startDay; i++)
          cells.push(
            `<div class="calendar-day border-b border-r border-slate-100 bg-slate-50/60"></div>`,
          );
        for (let day = 1; day <= daysInMonth; day++) {
          const d = new Date(year, month, day);
          const dayJobs = jobs.filter(
            (j) => toYMD(new Date(j.workStart)) === toYMD(d),
          );
          const isToday = toYMD(d) === "2026-07-08";
          cells.push(`<div class="calendar-day border-b border-r border-slate-100 p-2 bg-white hover:bg-slate-50/70 transition">
      <div class="flex items-center justify-between mb-2">
        <span class="${isToday ? "bg-indigo-600 text-white" : "text-slate-500"} h-7 w-7 rounded-full inline-flex items-center justify-center text-sm font-semibold">${day}</span>
        ${dayJobs.length ? `<span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">${dayJobs.length}</span>` : ""}
      </div>
      <div class="max-h-[86px] overflow-auto scrollbar-thin">
        ${dayJobs
          .slice(0, 3)
          .map((j) => jobBadge(j))
          .join("")}
        ${dayJobs.length > 3 ? `<button onclick="openDayJobs('${toYMD(d)}')" class="text-[11px] text-indigo-600 font-medium mt-1">+ ดูอีก ${dayJobs.length - 3} งาน</button>` : ""}
      </div>
    </div>`);
        }
        const totalCells = Math.ceil((startDay + daysInMonth) / 7) * 7;
        for (let i = startDay + daysInMonth; i < totalCells; i++)
          cells.push(
            `<div class="calendar-day border-b border-r border-slate-100 bg-slate-50/60"></div>`,
          );
        els.monthCalendar.innerHTML = cells.join("");
      }

      function renderDayTimeline(jobs) {
        const hours = Array.from({ length: 13 }, (_, i) => i + 8);
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
              const d = new Date(j.workStart);
              return d.getMonth() === mDate.getMonth();
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
        let priorityColor = "text-slate-500 bg-slate-100";
        if (job.priority === "สูง")
          priorityColor = "text-rose-600 bg-rose-50 border border-rose-100";
        if (job.priority === "กลาง")
          priorityColor = "text-amber-600 bg-amber-50 border border-amber-100";

        return `
        <button onclick="openJobDetail('${job.id}')" class="w-full text-left bg-white p-3 rounded-lg border border-slate-200 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all cursor-pointer group">
            <div class="flex justify-between items-start mb-1.5 gap-2">
                <span class="text-[10px] font-medium text-slate-400 group-hover:text-indigo-500 transition-colors">${job.id}</span>
                <span class="text-[9px] px-1.5 py-0.5 rounded-md font-medium ${priorityColor}">${job.priority}</span>
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
                <img src="${tech.avatar}" class="w-5 h-5 rounded-md object-cover border border-slate-200" title="${tech.name}" alt="${tech.name}">
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
            scales: {
              y: { beginAtZero: true, ticks: { precision: 0 } },
            },
            plugins: {
              legend: { display: false },
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

      function getScheduleSlotRange(entry) {
        const start = timeToMinutes(entry.start);
        const end = timeToMinutes(entry.end);
        const slots = mockData.workSlots;
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
        return `GRP_${item.technicianId}_${item.date}_${item.start.replace(":", "")}_${item.end.replace(":", "")}`;
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

      function renderScheduleCard(item, mini = false) {
        const tech = getTech(item.technicianId);
        const statusLabel = mockData.status[item.status]?.label || item.status;
        const target = item.linkedJobId
          ? `openJobDetail('${item.linkedJobId}')`
          : `openScheduleDetail('${item.id}')`;

        return `<button onclick="${target}" class="schedule-card w-full text-left rounded-lg border ${getScheduleStatusClass(item.status)} p-1.5">
    <div class="flex items-start justify-between gap-1.5">
      <div class="min-w-0">
        <div class="font-semibold text-[11px] leading-tight ${mini ? "line-clamp-2" : ""}">${item.title}</div>
        <div class="mt-0.5 text-[10px] opacity-80 truncate">${item.start}-${item.end} • ${tech.name}</div>
        ${mini ? "" : `<div class="mt-0.5 text-[10px] opacity-75 truncate">${item.location}</div>`}
      </div>
      ${item.linkedJobId ? '<span class="shrink-0 text-[9px] px-1.5 py-0.5 rounded-full bg-white/70">ใบงาน</span>' : ""}
    </div>
  </button>`;
      }

      function renderWorkSchedule() {
        const days = getWeekDays(state.currentDate);
        const entries = getScheduleEntriesForWeek();
        const tech = getTech(state.technicianId);
        const weekStart = days[0].date;
        const weekEnd = days[6].date;
        const hours = totalDurationHours(entries);
        const linked = entries.filter((i) => i.linkedJobId).length;
        const completed = entries.filter(
          (i) => i.status === "completed",
        ).length;
        const uniqueDays = new Set(entries.map((i) => i.date)).size;

        els.workScheduleTitle.textContent = `ตารางงานรายสัปดาห์: ${fmtDate(weekStart)} - ${fmtDate(weekEnd)}`;
        els.workScheduleSubtitle.textContent = `${tech.name} • แสดงตามช่วงเวลาเหมือนตาราง คลิกงานเพื่อดูรายละเอียด`;

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
            label: "ผูกใบแจ้งซ่อม",
            value: linked,
            hint: `เสร็จแล้ว ${completed}`,
            icon: "link-2",
            tone: "rose",
          },
        ];
        renderSummary(els.workScheduleSummary, summary);

        els.workScheduleHead.innerHTML = `<tr>
    <th class="sticky-day w-[120px] p-3 text-center bg-slate-100 text-slate-600 font-semibold rounded-tl-3xl">วัน</th>
    ${mockData.workSlots.map((slot) => `<th class="min-w-[120px] p-3 text-center bg-slate-100 text-slate-600 font-semibold"><div class="text-[11px] font-normal">${slot.label}</div></th>`).join("")}
  </tr>`;

        if (state.technicianId === "all") {
          els.workScheduleBody.innerHTML = days
            .map((day) => {
              const cells = mockData.workSlots
                .map((slot) => {
                  const slotStart = timeToMinutes(slot.start);
                  const slotEnd = timeToMinutes(slot.end);
                  const slotEntries = entries.filter(
                    (item) =>
                      item.date === day.ymd &&
                      timeToMinutes(item.start) < slotEnd &&
                      timeToMinutes(item.end) > slotStart,
                  );
                  const slotGroups = groupScheduleEntries(slotEntries);
                  return slotGroups.length
                    ? `<td class="p-2 min-w-[120px] bg-white"><div class="space-y-1.5">${slotGroups.map((group) => renderScheduleGroupCard(group, true)).join("")}</div></td>`
                    : renderEmptyScheduleCell("p-2 min-w-[120px] bg-white");
                })
                .join("");
              return `<tr>${renderDayLabelCell(day, "sticky-day")}${cells}</tr>`;
            })
            .join("");
          return;
        }

        currentScheduleGroups = {};
        els.workScheduleBody.innerHTML = days
          .map((day) => {
            const rowGroups = groupScheduleEntries(
              entries.filter((item) => item.date === day.ymd),
            ).map((group) => ({
              ...group,
              range: getScheduleSlotRange(group),
            }));
            let html = renderDayLabelCell(day, "sticky-day");
            let i = 0;
            while (i < mockData.workSlots.length) {
              const starts = rowGroups.filter(
                (group) => group.range.startIndex === i,
              );
              if (starts.length) {
                const span = Math.max(
                  ...starts.map((group) => group.range.span),
                );
                html += `<td colspan="${span}" class="p-2 bg-white"><div class="space-y-2">${starts.map((group) => renderScheduleGroupCard(group)).join("")}</div></td>`;
                i += span;
              } else {
                html += renderEmptyScheduleCell();
                i++;
              }
            }
            return `<tr>${html}</tr>`;
          })
          .join("");
      }

      function getWeekTechnicians() {
        const techs = mockData.technicians.filter((t) => t.id !== "all");
        if (state.technicianId === "all") return techs;
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
        const entries = getAllScheduleEntriesForWeek();
        const hours = totalDurationHours(entries);
        const completed = entries.filter(
          (i) => i.status === "completed",
        ).length;
        const overlapGroups = groupScheduleEntries(entries).filter(
          (g) => g.items.length > 1,
        ).length;

        els.teamWeeklyTitle.textContent = `ตารางทีมรายสัปดาห์: ${fmtDate(weekStart)} - ${fmtDate(weekEnd)}`;
        els.teamWeeklySubtitle.textContent =
          state.technicianId === "all"
            ? "แสดงทุกช่างในทีม ชื่อช่างอยู่ด้านซ้าย และแต่ละช่องแสดงงานของวันนั้นตามช่วงเวลา"
            : `${getTech(state.technicianId).name} • โหมดเจาะลึกเฉพาะช่างที่เลือก โดยยังใช้รูปแบบชื่อช่างอยู่ด้านซ้าย`;

        const summary = [
          {
            label: "ช่างที่แสดง",
            value: technicians.length,
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
            label: "งานซ้อนเวลา",
            value: overlapGroups,
            hint: `เสร็จแล้ว ${completed}`,
            icon: "git-merge",
            tone: "rose",
          },
        ];
        renderSummary(els.teamWeeklySummary, summary);

        els.teamWeeklyHead.innerHTML = `<tr>
    <th class="sticky-tech w-[210px] p-4 text-left bg-slate-100 text-slate-600 font-semibold rounded-tl-3xl">ชื่อช่าง</th>
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
          .map((tech) => {
            const techEntries = getEntriesByTech(entries, tech.id);
            const techHours = totalDurationHours(techEntries);
            const statusCounts = countBy(techEntries, "status");
            const statusDots = renderStatusBadges(statusCounts);
            return `<tr>
      <td class="sticky-tech p-3 align-top bg-white">
        <div class="tech-row-card rounded-3xl border border-slate-100 bg-white p-3 shadow-sm">
          <div class="flex items-center gap-3">
            <img src="${tech.avatar}" class="h-11 w-11 rounded-2xl object-cover" alt="${tech.name}">
            <div class="min-w-0">
              <div class="font-bold text-slate-950 truncate">${tech.name}</div>
              <div class="text-xs text-slate-400 truncate">${tech.role}</div>
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
        Swal.fire({
          title: `รายการงานวันที่ ${fmtDate(`${ymd}T00:00:00`)}`,
          html: `<div class="text-left space-y-2">${jobs
            .map(
              (
                j,
              ) => `<button onclick="Swal.close(); setTimeout(()=>openJobDetail('${j.id}'),120)" class="w-full rounded-2xl border border-slate-200 p-3 hover:bg-slate-50 text-left">
      <div class="font-semibold text-slate-900">${fmtTime(j.workStart)} - ${fmtTime(j.workEnd)} ${j.title}</div>
      <div class="text-sm text-slate-500">${getJobTech(j).name} • ${mockData.status[j.status].label}</div>
    </button>`,
            )
            .join("")}</div>`,
          width: 680,
          showCloseButton: true,
          showConfirmButton: false,
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

        Swal.fire({
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

        Swal.fire({
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
        <div class="rounded-xl border border-slate-100 p-2.5"><div class="text-[11px] text-slate-400 mb-0.5">วัน/เวลา</div><div class="font-medium">${fmtDate(combineDateTime(item.date, item.start))} ${item.start}-${item.end}</div></div>
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

      function openJobDetail(jobId) {
        const job = mockData.jobs.find((j) => j.id === jobId);
        if (!job) return;
        const tech = getJobTech(job);
        const status = mockData.status[job.status];

        const stockHtml = job.stockUsed.length
          ? `<div class="overflow-x-auto"><table class="w-full text-xs border-collapse">
    <thead><tr class="bg-slate-50 text-slate-500"><th class="text-left px-2 py-1.5 rounded-l-lg">รหัส</th><th class="text-left px-2 py-1.5">อุปกรณ์</th><th class="text-right px-2 py-1.5 rounded-r-lg">จำนวน</th></tr></thead>
    <tbody>${job.stockUsed.map((i) => `<tr class="border-b border-slate-100"><td class="px-2 py-1.5">${i.itemCode}</td><td class="px-2 py-1.5">${i.itemName}</td><td class="px-2 py-1.5 text-right">${i.qty} ${i.unit}</td></tr>`).join("")}</tbody>
  </table></div>`
          : '<div class="rounded-lg bg-slate-50 p-2.5 text-xs text-slate-500">ยังไม่มีการเบิกใช้อุปกรณ์จากสต็อก</div>';

        const imageHtml = (title, imgs) =>
          `<div><h4 class="font-semibold text-sm text-slate-950 mb-1.5">${title}</h4><div class="grid grid-cols-2 gap-2">${imgs.map((src) => `<img src="${src}" class="rounded-lg border border-slate-100 w-full h-28 object-cover" alt="${title}">`).join("")}</div></div>`;

        Swal.fire({
          title: `<div class="text-left"><div class="text-xs text-slate-400 font-normal leading-tight">${job.id}</div><div class="text-base mt-0.5">${job.title}</div></div>`,
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

        ${imageHtml("รูปภาพที่แจ้งซ่อม", job.beforeImages)}
      </div>

      <div class="space-y-3">
        
        <div class="flex flex-col xs:flex-row gap-2 xs:items-center xs:justify-between rounded-xl bg-slate-50 p-2.5">
          <div class="flex items-center gap-2.5">
            <img src="${tech.avatar}" class="h-9 w-9 rounded-lg object-cover" alt="${tech.name}">
            <div>
              <div class="text-sm font-semibold text-slate-950 leading-tight">${tech.name}</div>
              <div class="text-[11px] text-slate-500 mt-0.5">${tech.role}</div>
            </div>
          </div>
          <span class="inline-flex w-fit px-2.5 py-1 rounded-full border text-[11px] font-medium ${status.className}">${status.label}</span>
        </div>

        <div class="rounded-xl border border-slate-100 p-2.5 text-xs">
          <div class="text-[11px] text-slate-400 mb-0.5">วันที่ช่างเข้าดำเนินการ</div>
          <div class="font-medium">${fmtDate(job.workStart, { time: true })} - ${fmtTime(job.workEnd)}</div>
        </div>

        <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-3">
          <h4 class="font-semibold text-sm text-emerald-900">วิธีที่ช่างแก้ไขปัญหา</h4>
          <p class="mt-1 text-xs text-emerald-800">${job.solution}</p>
        </div>

        ${imageHtml("รูปภาพหลังแก้ไข", job.afterImages)}

        <div>
          <h4 class="font-semibold text-sm text-slate-950 mb-1.5">ข้อมูลอุปกรณ์ที่นำไปใช้จริง</h4>
          ${stockHtml}
        </div>
      </div>

    </div>`,
          width: 1000,
          showCloseButton: true,
          showConfirmButton: false,
        });
      }

      function exportExcel() {
        const jobs = getFilteredJobs();
        if (!jobs.length) {
          Swal.fire({
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
        Swal.fire({
          icon: "success",
          title: "Export สำเร็จ",
          text: `ส่งออกข้อมูล ${jobs.length} งานเรียบร้อยแล้ว`,
          confirmButtonText: "ตกลง",
        });
      }

      function exportScheduleExcel() {
        const entries = getScheduleEntriesForWeek();
        if (!entries.length) {
          Swal.fire({
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
        Swal.fire({
          icon: "success",
          title: "Export ตารางงานสำเร็จ",
          text: `ส่งออกข้อมูล ${entries.length} รายการเรียบร้อยแล้ว`,
          confirmButtonText: "ตกลง",
        });
      }

      function exportTeamWeeklyExcel() {
        const entries = getAllScheduleEntriesForWeek();
        if (!entries.length) {
          Swal.fire({
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
        Swal.fire({
          icon: "success",
          title: "Export ตารางทีมสำเร็จ",
          text: `ส่งออกข้อมูล ${entries.length} รายการเรียบร้อยแล้ว`,
          confirmButtonText: "ตกลง",
        });
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
