<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EasyPro User Manual</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap"
      rel="stylesheet"
    />

    <style>
        :root {
            --ep-navy: #074f72;
            --ep-navy-dark: #043b57;
            --ep-blue: #0783bd;
            --ep-blue-strong: #066f9f;
            --ep-blue-soft: #eaf5fb;
            --ep-page: #f0f4f8;
            --ep-panel: #ffffff;
            --ep-border: #dbe6ed;
            --ep-border-strong: #c9d9e3;
            --ep-text: #1c2b36;
            --ep-muted: #667785;
            --ep-success: #18845b;
        }

        * { box-sizing: border-box; }
        html, body { height: 100%; }

        body {
            margin: 0;
            font-family: "Prompt", "Kanit", "Noto Sans Thai", sans-serif;
            color: var(--ep-text);
            background: var(--ep-page);
            -webkit-font-smoothing: antialiased;
        }

        button, input { font: inherit; }

        /* Make Lucide icons drop-in replacements for the old font icons:
           scale with font-size, inherit text color, align to the baseline. */
        [data-lucide],
        .lucide {
            width: 1em;
            height: 1em;
            display: inline-block;
            vertical-align: -0.125em;
            stroke-width: 2;
            flex: 0 0 auto;
        }

        .app-shell {
            height: 100vh;
            overflow: hidden;
            background: var(--ep-page);
        }

        .topbar {
            background: #fff;
            color: var(--ep-text);
            border-bottom: 1px solid var(--ep-border);
        }

        .brand-mark {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            color: #fff;
            background: #006b9f;
        }

        .topbar-icon-btn {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            color: #748591;
            border: 1px solid var(--ep-border);
            background: #fff;
            transition: 0.2s ease;
        }

        .topbar-icon-btn:hover {
            color: var(--ep-blue-strong);
            background: #f3f8fb;
        }

        .workspace { flex: 1 1 auto; min-height: 0; }

        .sidebar {
            width: 300px;
            background: #fff;
            border-right: 1px solid var(--ep-border);
        }

        .sidebar-scroll::-webkit-scrollbar,
        .main-scroll::-webkit-scrollbar,
        .thumb-scroll::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb,
        .main-scroll::-webkit-scrollbar-thumb,
        .thumb-scroll::-webkit-scrollbar-thumb {
            background: #c9d8e1;
            border-radius: 999px;
        }

        .panel {
            background: var(--ep-panel);
            border: 1px solid var(--ep-border);
            border-radius: 10px;
        }

        .page-heading-card {
            background: #fff;
        }

        .field-shell {
            border: 1px solid var(--ep-border);
            background: #f7fafc;
            transition: 0.2s ease;
        }

        .field-shell:focus-within {
            background: #fff;
            border-color: #77bdda;
            box-shadow: 0 0 0 3px rgba(7,131,189,0.11);
        }

        .guide-group-title {
            color: #71818d;
            letter-spacing: 0.055em;
        }

        .guide-item {
            border: 1px solid transparent;
            border-left-width: 3px;
            transition: background-color 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
        }

        .guide-item:hover {
            background: #f6fafc;
            border-color: #e2ebf0;
            transform: translateX(2px);
        }

        .guide-active {
            background: var(--ep-blue-soft);
            border-color: #cfe8f4;
            border-left-color: var(--ep-blue);
            box-shadow: inset 0 0 0 1px rgba(7,131,189,0.03);
        }

        .guide-active .guide-title {
            color: #05658f;
            font-weight: 600;
        }

        .guide-active .guide-icon-wrap {
            color: #fff;
            background: var(--ep-blue);
            border-color: var(--ep-blue);
        }

        .guide-icon-wrap {
            width: 27px;
            height: 27px;
            border-radius: 7px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            color: #758692;
            background: #fff;
            border: 1px solid #d9e4ea;
            transition: 0.18s ease;
        }

        .progress-track {
            height: 6px;
            border-radius: 999px;
            background: #dfeaf0;
            overflow: hidden;
        }

        .progress-value {
            height: 100%;
            width: 0;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--ep-blue), #43a8cf);
            transition: width 0.3s ease;
        }

        .soft-badge {
            color: #05658f;
            background: var(--ep-blue-soft);
            border: 1px solid #cee8f4;
        }

        .btn-primary, .btn-secondary, .btn-success {
            min-height: 36px;
            border-radius: 8px;
            transition: 0.2s ease;
        }

        .btn-primary {
            color: #fff;
            background: var(--ep-blue-strong);
            border: 1px solid var(--ep-blue-strong);
        }

        .btn-primary:hover {
            background: #055f89;
            border-color: #055f89;
        }

        .btn-secondary {
            color: #40515d;
            background: #fff;
            border: 1px solid var(--ep-border-strong);
        }

        .btn-secondary:hover {
            color: #056f9d;
            background: #f8fbfd;
            border-color: #9fc9dd;
        }

        .btn-success {
            color: #fff;
            background: var(--ep-success);
            border: 1px solid var(--ep-success);
        }

        .btn-primary:disabled,
        .btn-secondary:disabled,
        .btn-success:disabled {
            cursor: not-allowed;
            opacity: 0.48;
            transform: none;
            box-shadow: none;
        }

        .content-prose p { margin-bottom: 0.85rem; }
        .content-prose ul {
            margin-top: 0.5rem;
            padding-left: 1.35rem;
            list-style: disc;
        }
        .content-prose li + li { margin-top: 0.35rem; }

        .mobile-overlay {
            background: rgba(3,29,43,0.58);
            backdrop-filter: blur(3px);
        }

        .slide-stage {
            background: #f7fafc;
        }

        .slide-canvas {
            background: #fff;
            border: 1px solid #dbe7ee;
        }

        .thumb-item {
            border: 1px solid #dbe6ed;
            background: #fff;
            transition: 0.18s ease;
        }

        .thumb-item:hover {
            border-color: #9ec8dc;
            background: #f9fcfe;
        }

        .thumb-item.active {
            border-color: var(--ep-blue);
            background: #eef8fd;
            box-shadow: 0 0 0 2px rgba(7,131,189,0.12);
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            padding: 0.35rem 0.7rem;
            font-size: 11px;
            font-weight: 600;
        }

        .status-ready {
            background: #eaf8f2;
            color: #16805a;
        }

        .status-doc {
            background: #edf6fb;
            color: #056f9d;
        }

        @media (max-width: 767px) {
            .sidebar { width: min(86vw, 320px); }
        }
    </style>
</head>
<body>
    <div class="app-shell flex h-screen flex-col overflow-hidden">
        <header class="topbar w-full bg-white shadow-sm flex shrink-0 flex-col flex-none z-50">
          <!-- <header class="topbar relative z-40 flex shrink-0 items-center justify-between px-4 md:px-6"> -->

            <nav class="flex-none px-6 py-4 flex items-center justify-between border-b border-slate-200 bg-white z-20 shadow-sm">
              <div class="flex items-center gap-4">
                  <div id="mobile-menu-btn" class="topbar-icon-btn md:hidden" aria-label="เปิดเมนูคู่มือ">
                    <i data-lucide="panel-right-close"></i>
                  </div>
                  <div class="brand-mark shrink-0">
                      <i data-lucide="book-open-check" class="w-6 h-6"></i>
                  </div>
                  <div>
                      <h2 class="text-lg font-bold text-slate-800 leading-tight" id="header-title">Tutorial</h2>
                      <p class="text-xs text-slate-500 mt-1" id="header-subtitle">คู่มือการใช้งานโปรแกรมแบบภาพสไลด์สำหรับองค์กร</p>
                  </div>
              </div>
          </nav>
        </header>

        <div class="workspace relative flex min-h-0 overflow-hidden">
            <aside id="sidebar" class="sidebar absolute inset-y-0 left-0 z-50 flex h-full shrink-0 -translate-x-full flex-col transition-transform duration-300 ease-out md:relative md:translate-x-0">
                <div class="border-b border-[#e1eaf0] px-4 pb-3.5 pt-2">
                    <div class="flex items-start justify-between gap-3">

                        <button id="close-sidebar-btn" class="grid h-8 w-8 place-items-center rounded-lg border border-[#dbe6ed] text-[#748591] hover:bg-[#f3f8fb] md:hidden" aria-label="ปิดเมนูคู่มือ">
                            <i data-lucide="x"></i>
                        </button>
                    </div>

                    <div class="mt-3 rounded-lg border border-[#dce8ef] bg-[#f7fafc] p-3">
                        <div class="mb-2 flex items-center justify-between text-xs">
                            <span class="font-medium text-[#52636f]">ความคืบหน้าการเปิดอ่าน</span>
                            <span id="progress-percent" class="font-semibold text-[#066f9f]">0%</span>
                        </div>
                        <div class="progress-track">
                            <div id="progress-value" class="progress-value"></div>
                        </div>
                        <p id="progress-text" class="mt-2 text-[11px] text-[#7a8994]">เปิดอ่านแล้ว 0 จาก 0 หัวข้อ</p>
                    </div>
                </div>

                <div class="border-b border-[#e5edf2] px-3 py-3">
                    <label class="field-shell flex items-center gap-2 rounded-lg px-3 py-2">
                        <i data-lucide="search" class="text-xs text-[#8a9aa5]"></i>
                        <input id="search-guide" type="search" placeholder="ค้นหาหัวข้อหรือฟีเจอร์" class="min-w-0 flex-1 bg-transparent text-sm text-[#263843] outline-none placeholder:text-[#9aa8b1]">
                    </label>
                </div>

                <div id="guide-list-container" class="sidebar-scroll flex-1 overflow-y-auto px-3 py-3"></div>
            </aside>

            <div id="sidebar-overlay" class="mobile-overlay fixed inset-0 z-40 hidden md:hidden"></div>

            <main class="main-scroll min-w-0 flex-1 overflow-y-auto">
                <div class="mx-auto w-full max-w-8xl p-3 md:p-4">
                    <div class="space-y-4">
                            <article class="panel overflow-hidden">
                                <div class="flex flex-col justify-between gap-2.5 border-b border-[#e1eaf0] px-4 py-3 sm:flex-row sm:items-center md:px-5">
                                    <div class="flex min-w-0 items-center gap-2.5">
                                        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#e8f5fb] text-[#0676a7]">
                                            <i data-lucide="image"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-semibold uppercase tracking-[0.08em] text-[#7a8994]">Slide Preview</p>
                                            <h3 id="slide-header-title" class="truncate text-sm font-semibold text-[#263843]">กำลังโหลดคู่มือ</h3>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 text-xs text-[#6f7f8a]">
                                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-[#f2f7fa] px-2.5 py-1.5"><i data-lucide="file-text" class="text-[#0783bd]"></i><span id="guide-page-count-top">0 หน้า</span></span>
                                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-[#f2f7fa] px-2.5 py-1.5"><i data-lucide="image" class="text-[#0783bd]"></i><span id="current-slide-label-top">สไลด์ 1/1</span></span>
                                    </div>
                                </div>

                                <div class="p-3 md:p-4">
                                    <div class="slide-stage rounded-xl border border-[#e2edf3] p-2.5 md:p-3">
                                        <div class="slide-canvas overflow-hidden rounded-lg">
                                            <div class="relative aspect-[16/9] w-full bg-white">
                                                <img id="slide-image" src="" alt="ภาพสไลด์" class="h-full w-full object-contain">
                                            </div>
                                        </div>

                                        <div class="mt-3 flex flex-col gap-2.5 md:flex-row md:items-center md:justify-between">
                                            <div class="min-w-0">
                                                <div class="mb-1 flex items-center gap-2 text-xs text-[#7a8994]">
                                                    <span id="current-slide-pill" class="soft-badge rounded-lg px-2 py-0.5 font-semibold">สไลด์ 1</span>
                                                    <span class="status-pill status-doc"><i data-lucide="book-open-check"></i> คู่มือภาพประกอบ</span>
                                                </div>
                                                <h3 id="slide-title" class="truncate text-base font-bold text-[#243641]">หัวข้อสไลด์</h3>
                                                <p id="slide-caption" class="mt-0.5 text-xs leading-5 text-[#63747f]">คำอธิบายสไลด์</p>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <button id="btn-prev-slide" class="btn-secondary inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold"><i data-lucide="chevron-left"></i> สไลด์ก่อนหน้า</button>
                                                <button id="btn-next-slide" class="btn-primary inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold">สไลด์ถัดไป <i data-lucide="chevron-right"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-[#e1eaf0] px-4 py-3 md:px-5">
                                    <div class="mb-2.5 flex items-center justify-between">
                                        <h4 class="text-sm font-bold text-[#263843]">รายการสไลด์ในหัวข้อนี้</h4>
                                        <span id="slide-total-inline" class="text-xs text-[#7b8b95]">0 สไลด์</span>
                                    </div>
                                    <div id="slide-thumbnails" class="thumb-scroll flex gap-2.5 overflow-x-auto pb-1"></div>
                                </div>
                            </article>

                            <article class="panel overflow-hidden">
                                <div class="border-b border-[#e2ebf0] px-4 py-4 md:px-5">
                                    <div class="mb-2.5 flex flex-wrap items-center gap-2">
                                        <span id="guide-badge" class="soft-badge rounded-lg px-2 py-0.5 text-xs font-semibold">หัวข้อที่ 1</span>
                                        <span id="guide-slide-count" class="inline-flex items-center gap-1.5 text-xs text-[#72828d]"><i data-lucide="images"></i> 0 สไลด์</span>
                                        <span class="inline-flex items-center gap-1.5 text-xs text-[#72828d]"><i data-lucide="languages"></i> ภาษาไทย</span>
                                    </div>
                                    <h2 id="guide-title" class="text-lg font-bold leading-snug text-[#1f303b] md:text-xl">หัวข้อคู่มือ</h2>
                                </div>

                                <div id="guide-description" class="content-prose px-4 py-4 text-sm leading-7 text-[#5c6d78] md:px-5">
                                    รายละเอียดของหัวข้อคู่มือจะแสดงที่นี่
                                </div>

                                <div class="flex flex-col-reverse justify-between gap-2.5 border-t border-[#e2ebf0] bg-[#fbfdfe] px-4 py-3 sm:flex-row md:px-5">
                                    <button id="btn-prev-guide" class="btn-secondary inline-flex w-full items-center justify-center gap-2 px-4 py-2 text-xs font-semibold sm:w-auto">
                                        <i data-lucide="arrow-left"></i> หัวข้อก่อนหน้า
                                    </button>
                                    <button id="btn-next-guide" class="btn-primary inline-flex w-full items-center justify-center gap-2 px-4 py-2 text-xs font-semibold sm:w-auto">
                                        หัวข้อถัดไป <i data-lucide="arrow-right"></i>
                                    </button>
                                </div>
                            </article>
                    </div>

                    <footer class="py-4 text-center text-[11px] text-[#8897a1]">
                        EasyPro User Manual · Enterprise Guide Platform
                    </footer>
                </div>
            </main>
        </div>
    </div>

    <script>
        function createSlideSvg(step, title, caption, tag) {
            const svg = `
                <svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900" viewBox="0 0 1600 900">
                    <defs>
                        <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#f8fbfd"/>
                            <stop offset="100%" stop-color="#eaf4f9"/>
                        </linearGradient>
                        <linearGradient id="accent" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%" stop-color="#074f72"/>
                            <stop offset="100%" stop-color="#0783bd"/>
                        </linearGradient>
                    </defs>
                    <rect width="1600" height="900" fill="url(#bg)"/>
                    <rect x="0" y="0" width="1600" height="94" fill="url(#accent)"/>
                    <rect x="0" y="842" width="1600" height="58" fill="#e8f2f8"/>
                    <text x="80" y="57" fill="#ffffff" font-size="34" font-family="Inter, Noto Sans Thai, sans-serif" font-weight="700">EasyPro Enterprise Manual</text>
                    <rect x="80" y="145" width="160" height="44" rx="12" fill="#e8f5fb" stroke="#c6e2ef"/>
                    <text x="160" y="174" text-anchor="middle" fill="#056f9d" font-size="22" font-family="Inter, Noto Sans Thai, sans-serif" font-weight="700">${step}</text>
                    <text x="80" y="245" fill="#1f303b" font-size="42" font-family="Inter, Noto Sans Thai, sans-serif" font-weight="700">${title}</text>
                    <text x="80" y="292" fill="#60707c" font-size="22" font-family="Inter, Noto Sans Thai, sans-serif">${caption}</text>

                    <rect x="80" y="340" width="930" height="420" rx="24" fill="#ffffff" stroke="#d6e5ee"/>
                    <rect x="80" y="340" width="930" height="66" rx="24" fill="#f2f8fb" stroke="#d6e5ee"/>
                    <circle cx="118" cy="373" r="8" fill="#d06464"/>
                    <circle cx="146" cy="373" r="8" fill="#e4b44b"/>
                    <circle cx="174" cy="373" r="8" fill="#7dbf8f"/>
                    <rect x="230" y="356" width="300" height="32" rx="10" fill="#ffffff" stroke="#d6e5ee"/>
                    <text x="255" y="377" fill="#7b8b95" font-size="16" font-family="Inter, Noto Sans Thai, sans-serif">หน้าจอระบบ / ตัวอย่างพื้นที่การทำงาน</text>

                    <rect x="110" y="440" width="220" height="270" rx="16" fill="#f7fbfd" stroke="#d7e5ec"/>
                    <rect x="355" y="440" width="300" height="64" rx="14" fill="#ecf6fb" stroke="#d0e6f1"/>
                    <rect x="355" y="525" width="590" height="42" rx="12" fill="#f5f9fb" stroke="#dce9ef"/>
                    <rect x="355" y="585" width="590" height="42" rx="12" fill="#f5f9fb" stroke="#dce9ef"/>
                    <rect x="355" y="645" width="420" height="42" rx="12" fill="#f5f9fb" stroke="#dce9ef"/>
                    <rect x="795" y="645" width="150" height="42" rx="12" fill="#0783bd"/>
                    <text x="870" y="672" text-anchor="middle" fill="#ffffff" font-size="16" font-family="Inter, Noto Sans Thai, sans-serif" font-weight="700">บันทึก</text>

                    <rect x="1055" y="215" width="445" height="545" rx="24" fill="#ffffff" stroke="#d6e5ee"/>
                    <rect x="1085" y="252" width="160" height="36" rx="10" fill="#074f72"/>
                    <text x="1165" y="276" text-anchor="middle" fill="#ffffff" font-size="18" font-family="Inter, Noto Sans Thai, sans-serif" font-weight="700">คู่มือย่อ</text>
                    <text x="1088" y="332" fill="#1f303b" font-size="22" font-family="Inter, Noto Sans Thai, sans-serif" font-weight="700">${tag}</text>
                    <text x="1088" y="376" fill="#60707c" font-size="19" font-family="Inter, Noto Sans Thai, sans-serif">1. ตรวจสอบข้อมูลในหน้าจอ</text>
                    <text x="1088" y="415" fill="#60707c" font-size="19" font-family="Inter, Noto Sans Thai, sans-serif">2. กรอกข้อมูลให้ครบถ้วน</text>
                    <text x="1088" y="454" fill="#60707c" font-size="19" font-family="Inter, Noto Sans Thai, sans-serif">3. กดปุ่มยืนยันหรือบันทึก</text>
                    <text x="1088" y="493" fill="#60707c" font-size="19" font-family="Inter, Noto Sans Thai, sans-serif">4. ตรวจสอบผลลัพธ์หรือสถานะ</text>
                    <rect x="1088" y="548" width="354" height="138" rx="18" fill="#eef8fd" stroke="#d0e7f2"/>
                    <text x="1114" y="590" fill="#056f9d" font-size="20" font-family="Inter, Noto Sans Thai, sans-serif" font-weight="700">หมายเหตุ</text>
                    <text x="1114" y="628" fill="#5f707c" font-size="18" font-family="Inter, Noto Sans Thai, sans-serif">สไลด์ตัวอย่างนี้สามารถแทนที่</text>
                    <text x="1114" y="660" fill="#5f707c" font-size="18" font-family="Inter, Noto Sans Thai, sans-serif">ด้วยภาพหน้าจอจริงของระบบได้ทันที</text>
                </svg>`;
            return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
        }

        const manualData = [
            {
                moduleId: "m1",
                moduleTitle: "เริ่มต้นใช้งานระบบ",
                guides: [
                    {
                        id: "g1-1",
                        title: "ภาพรวมหน้าจอหลักของระบบ",
                        description: `
                            <p>หัวข้อนี้อธิบายส่วนประกอบของหน้าจอหลัก เช่น เมนูหลัก แถบสถานะ พื้นที่การทำงาน และปุ่มสำคัญที่ผู้ใช้งานต้องพบเป็นประจำ</p>
                            <ul>
                                <li>รู้จักโครงสร้างของหน้าจอและเมนูหลัก</li>
                                <li>เข้าใจตำแหน่งของปุ่มใช้งานที่สำคัญ</li>
                                <li>ใช้เป็นคู่มือสำหรับพนักงานใหม่ได้ทันที</li>
                            </ul>
                        `,
                        slides: [
                            { title: "ภาพรวมหน้าจอหลัก", caption: "ทำความเข้าใจองค์ประกอบหลักของระบบ EasyPro", image: createSlideSvg("STEP 01", "ภาพรวมหน้าจอหลัก", "ทำความเข้าใจองค์ประกอบหลักของระบบ EasyPro", "รู้จักเมนูหลัก") },
                            { title: "เมนูด้านซ้าย", caption: "ดูโครงสร้างเมนูเพื่อเข้าถึงแต่ละฟังก์ชันได้รวดเร็ว", image: createSlideSvg("STEP 02", "เมนูด้านซ้าย", "ดูโครงสร้างเมนูเพื่อเข้าถึงแต่ละฟังก์ชันได้รวดเร็ว", "การนำทางในระบบ") },
                            { title: "แถบเครื่องมือด้านบน", caption: "แสดงชื่อผู้ใช้งาน การแจ้งเตือน และปุ่มลัดที่สำคัญ", image: createSlideSvg("STEP 03", "แถบเครื่องมือด้านบน", "แสดงชื่อผู้ใช้งาน การแจ้งเตือน และปุ่มลัดที่สำคัญ", "ส่วนหัวของระบบ") }
                        ]
                    },
                    {
                        id: "g1-2",
                        title: "การเข้าสู่ระบบและสิทธิ์ผู้ใช้งาน",
                        description: `
                            <p>หัวข้อนี้แสดงขั้นตอนการเข้าสู่ระบบ การเลือกหน่วยงาน และการตรวจสอบสิทธิ์ของผู้ใช้งานภายในองค์กร</p>
                            <ul>
                                <li>เข้าสู่ระบบด้วยชื่อผู้ใช้และรหัสผ่าน</li>
                                <li>ตรวจสอบบทบาทของผู้ใช้งาน</li>
                                <li>กำหนดการเข้าถึงเมนูตามสิทธิ์</li>
                            </ul>
                        `,
                        slides: [
                            { title: "หน้าจอเข้าสู่ระบบ", caption: "กรอกชื่อผู้ใช้และรหัสผ่านที่ได้รับจากผู้ดูแลระบบ", image: createSlideSvg("STEP 01", "หน้าจอเข้าสู่ระบบ", "กรอกชื่อผู้ใช้และรหัสผ่านที่ได้รับจากผู้ดูแลระบบ", "การเข้าสู่ระบบ") },
                            { title: "เลือกหน่วยงาน", caption: "หากมีหลายหน่วยงาน สามารถเลือกบริบทการทำงานได้", image: createSlideSvg("STEP 02", "เลือกหน่วยงาน", "หากมีหลายหน่วยงาน สามารถเลือกบริบทการทำงานได้", "เลือกบริบทการทำงาน") },
                            { title: "ตรวจสอบสิทธิ์การใช้งาน", caption: "สิทธิ์ของแต่ละบทบาทจะกำหนดเมนูที่มองเห็นและแก้ไขได้", image: createSlideSvg("STEP 03", "ตรวจสอบสิทธิ์การใช้งาน", "สิทธิ์ของแต่ละบทบาทจะกำหนดเมนูที่มองเห็นและแก้ไขได้", "สิทธิ์และบทบาท") }
                        ]
                    }
                ]
            },
            {
                moduleId: "m2",
                moduleTitle: "การทำงานประจำวัน",
                guides: [
                    {
                        id: "g2-1",
                        title: "การสร้างรายการงานใหม่",
                        description: `
                            <p>ใช้หัวข้อนี้เป็นคู่มือสร้างรายการงานใหม่ ตั้งแต่การกรอกข้อมูลเบื้องต้น การเลือกผู้รับผิดชอบ และการบันทึกรายการเข้าระบบ</p>
                            <ul>
                                <li>กรอกข้อมูลผู้แจ้งและรายละเอียดงาน</li>
                                <li>กำหนดประเภทงานและระดับความสำคัญ</li>
                                <li>บันทึกรายการและตรวจสอบสถานะ</li>
                            </ul>
                        `,
                        slides: [
                            { title: "เริ่มสร้างรายการงาน", caption: "กดปุ่มเพิ่มรายการใหม่จากหน้ารายการงาน", image: createSlideSvg("STEP 01", "เริ่มสร้างรายการงาน", "กดปุ่มเพิ่มรายการใหม่จากหน้ารายการงาน", "สร้างรายการใหม่") },
                            { title: "กรอกข้อมูลรายละเอียด", caption: "ระบุหัวข้องาน สถานที่ ผู้ติดต่อ และรายละเอียดที่จำเป็น", image: createSlideSvg("STEP 02", "กรอกข้อมูลรายละเอียด", "ระบุหัวข้องาน สถานที่ ผู้ติดต่อ และรายละเอียดที่จำเป็น", "กรอกข้อมูลให้ครบ") },
                            { title: "บันทึกและตรวจสอบ", caption: "เมื่อบันทึกแล้ว ระบบจะสร้างเลขอ้างอิงและสถานะงานให้อัตโนมัติ", image: createSlideSvg("STEP 03", "บันทึกและตรวจสอบ", "เมื่อบันทึกแล้ว ระบบจะสร้างเลขอ้างอิงและสถานะงานให้อัตโนมัติ", "ยืนยันการบันทึก") },
                            { title: "ติดตามสถานะงาน", caption: "สามารถค้นหาและติดตามการดำเนินงานย้อนหลังได้จากรายการงาน", image: createSlideSvg("STEP 04", "ติดตามสถานะงาน", "สามารถค้นหาและติดตามการดำเนินงานย้อนหลังได้จากรายการงาน", "ตรวจสอบผลลัพธ์") }
                        ]
                    },
                    {
                        id: "g2-2",
                        title: "การแนบไฟล์ รูปภาพ และเอกสารประกอบ",
                        description: `
                            <p>คู่มือนี้อธิบายการแนบรูปภาพ เอกสาร หรือไฟล์ประกอบการทำงาน เพื่อให้ข้อมูลครบถ้วนและตรวจสอบย้อนหลังได้ง่าย</p>
                            <ul>
                                <li>เพิ่มไฟล์แนบในฟอร์มรายการงาน</li>
                                <li>รองรับรูปภาพและเอกสารประกอบ</li>
                                <li>ช่วยให้การตรวจสอบย้อนหลังทำได้สะดวกขึ้น</li>
                            </ul>
                        `,
                        slides: [
                            { title: "เปิดส่วนแนบไฟล์", caption: "เลื่อนไปยังส่วนไฟล์แนบหรือรูปภาพภายในฟอร์ม", image: createSlideSvg("STEP 01", "เปิดส่วนแนบไฟล์", "เลื่อนไปยังส่วนไฟล์แนบหรือรูปภาพภายในฟอร์ม", "ส่วนแนบเอกสาร") },
                            { title: "เลือกไฟล์จากเครื่อง", caption: "กดปุ่มเลือกไฟล์และอัปโหลดข้อมูลที่ต้องการแนบ", image: createSlideSvg("STEP 02", "เลือกไฟล์จากเครื่อง", "กดปุ่มเลือกไฟล์และอัปโหลดข้อมูลที่ต้องการแนบ", "อัปโหลดไฟล์") },
                            { title: "ตรวจสอบไฟล์แนบ", caption: "หลังอัปโหลดแล้วควรตรวจสอบชื่อไฟล์และความถูกต้องก่อนบันทึก", image: createSlideSvg("STEP 03", "ตรวจสอบไฟล์แนบ", "หลังอัปโหลดแล้วควรตรวจสอบชื่อไฟล์และความถูกต้องก่อนบันทึก", "ตรวจสอบก่อนบันทึก") }
                        ]
                    }
                ]
            },
            {
                moduleId: "m3",
                moduleTitle: "รายงานและการติดตามผล",
                guides: [
                    {
                        id: "g3-1",
                        title: "การค้นหาและกรองข้อมูล",
                        description: `
                            <p>หัวข้อนี้ช่วยให้ผู้ใช้งานสามารถค้นหาข้อมูลย้อนหลัง กรองตามสถานะ หน่วยงาน หรือช่วงเวลา และสรุปผลได้รวดเร็ว</p>
                            <ul>
                                <li>ค้นหาจากคำสำคัญหรือเลขอ้างอิง</li>
                                <li>ใช้ตัวกรองเพื่อลดจำนวนข้อมูลที่แสดง</li>
                                <li>เหมาะกับผู้ดูแลระบบและหัวหน้างาน</li>
                            </ul>
                        `,
                        slides: [
                            { title: "ค้นหาด้วยคำสำคัญ", caption: "ใช้ช่องค้นหาเพื่อระบุเลขที่งานหรือข้อความสำคัญ", image: createSlideSvg("STEP 01", "ค้นหาด้วยคำสำคัญ", "ใช้ช่องค้นหาเพื่อระบุเลขที่งานหรือข้อความสำคัญ", "ค้นหาข้อมูล") },
                            { title: "เลือกตัวกรอง", caption: "กำหนดสถานะ วันที่ หรือหน่วยงานเพื่อกรองผลลัพธ์", image: createSlideSvg("STEP 02", "เลือกตัวกรอง", "กำหนดสถานะ วันที่ หรือหน่วยงานเพื่อกรองผลลัพธ์", "กรองผลลัพธ์") },
                            { title: "ตรวจสอบรายการที่พบ", caption: "ผลลัพธ์ที่ได้สามารถเปิดดูรายละเอียดต่อได้ทันที", image: createSlideSvg("STEP 03", "ตรวจสอบรายการที่พบ", "ผลลัพธ์ที่ได้สามารถเปิดดูรายละเอียดต่อได้ทันที", "ตรวจสอบข้อมูล") }
                        ]
                    },
                    {
                        id: "g3-2",
                        title: "การส่งออกข้อมูลและรายงาน",
                        description: `
                            <p>ใช้สำหรับสรุปผลการทำงานและส่งออกข้อมูลในรูปแบบเอกสาร เช่น PDF หรือ Excel เพื่อใช้ในงานบริหารและการประชุม</p>
                            <ul>
                                <li>ส่งออกข้อมูลเป็นรายงานหรือไฟล์ตาราง</li>
                                <li>ตรวจสอบเงื่อนไขก่อน Export</li>
                                <li>เหมาะกับงานติดตาม KPI และงานผู้บริหาร</li>
                            </ul>
                        `,
                        slides: [
                            { title: "เลือกเมนูรายงาน", caption: "เข้าสู่หน้ารายงานหรือสรุปผลจากเมนูระบบ", image: createSlideSvg("STEP 01", "เลือกเมนูรายงาน", "เข้าสู่หน้ารายงานหรือสรุปผลจากเมนูระบบ", "เริ่มต้นจากรายงาน") },
                            { title: "กำหนดช่วงข้อมูล", caption: "เลือกช่วงเวลา ประเภทงาน หรือเงื่อนไขที่ต้องการสรุป", image: createSlideSvg("STEP 02", "กำหนดช่วงข้อมูล", "เลือกช่วงเวลา ประเภทงาน หรือเงื่อนไขที่ต้องการสรุป", "ตั้งค่าก่อนส่งออก") },
                            { title: "กดส่งออกเอกสาร", caption: "Export รายงานเป็น PDF หรือ Excel ตามรูปแบบที่องค์กรใช้งาน", image: createSlideSvg("STEP 03", "กดส่งออกเอกสาร", "Export รายงานเป็น PDF หรือ Excel ตามรูปแบบที่องค์กรใช้งาน", "ส่งออกข้อมูล") }
                        ]
                    }
                ]
            }
        ];

        let currentGuideId = null;
        let currentSlideIndex = 0;
        let flatGuideList = [];
        const openedGuides = new Set();

        // Render any <i data-lucide="..."> placeholders (static or dynamically injected) into SVG icons.
        function refreshIcons() {
            if (window.lucide) {
                lucide.createIcons();
            }
        }

        // Safe DOM setters: no-op if the element was removed from the markup,
        // so trimming sections from the UI never breaks the viewer.
        function setText(id, value) {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        }

        function setHTML(id, value) {
            const el = document.getElementById(id);
            if (el) el.innerHTML = value;
        }

        function init() {
            flattenGuides();
            renderSidebar();
            setupEventListeners();
            updateGlobalSummary();

            if (flatGuideList.length > 0) {
                loadGuide(flatGuideList[0].id);
            }

            refreshIcons();
        }

        function flattenGuides() {
            flatGuideList = [];
            manualData.forEach((module) => {
                module.guides.forEach((guide) => {
                    flatGuideList.push({
                        ...guide,
                        moduleId: module.moduleId,
                        moduleTitle: module.moduleTitle,
                        displayIndex: flatGuideList.length + 1
                    });
                });
            });
        }

        function updateGlobalSummary() {
            const totalSlides = flatGuideList.reduce((sum, guide) => sum + guide.slides.length, 0);
            setText("module-count", `${manualData.length} หมวด`);
            setText("guide-count", `${flatGuideList.length} หัวข้อ`);
            setText("slide-count-global", `${totalSlides} สไลด์`);
            updateProgress();
        }

        function updateProgress() {
            const total = flatGuideList.length;
            const viewed = openedGuides.size;
            const percent = total ? Math.round((viewed / total) * 100) : 0;
            setText("progress-text", `เปิดอ่านแล้ว ${viewed} จาก ${total} หัวข้อ`);
            setText("progress-percent", `${percent}%`);
            const bar = document.getElementById("progress-value");
            if (bar) bar.style.width = `${percent}%`;
        }

        function renderSidebar(searchTerm = "") {
            const container = document.getElementById("guide-list-container");
            const keyword = searchTerm.trim().toLowerCase();
            container.innerHTML = "";

            manualData.forEach((module, moduleIndex) => {
                const filteredGuides = module.guides.filter((guide) => {
                    return guide.title.toLowerCase().includes(keyword) || module.moduleTitle.toLowerCase().includes(keyword);
                });

                if (!filteredGuides.length) return;

                const group = document.createElement("section");
                group.className = "mb-5";

                const title = document.createElement("div");
                title.className = "guide-group-title mb-2 flex items-center justify-between px-2 text-[11px] font-bold uppercase";
                title.innerHTML = `
                    <span>${String(moduleIndex + 1).padStart(2, "0")} · ${module.moduleTitle}</span>
                    <span class="rounded-md bg-[#f0f5f8] px-1.5 py-0.5 text-[10px] font-semibold text-[#87959f]">${filteredGuides.length}</span>
                `;
                group.appendChild(title);

                const list = document.createElement("div");
                list.className = "space-y-1.5";

                filteredGuides.forEach((guide) => {
                    const isActive = guide.id === currentGuideId;
                    const flatItem = flatGuideList.find((item) => item.id === guide.id);
                    const isOpened = openedGuides.has(guide.id);

                    const button = document.createElement("button");
                    button.type = "button";
                    button.className = `guide-item ${isActive ? "guide-active" : ""} w-full rounded-xl px-3 py-3 text-left`;
                    button.onclick = () => loadGuide(guide.id);
                    button.innerHTML = `
                        <div class="flex items-start gap-3">
                            <span class="guide-icon-wrap mt-0.5">
                                <i data-lucide="${isActive ? "book-open" : isOpened ? "check" : "file-text"}" class="text-[11px]"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="guide-title text-[13px] leading-5 text-[#4b5c67]">${guide.title}</p>
                                <div class="mt-1.5 flex items-center justify-between gap-2 text-[10px] text-[#8a98a2]">
                                    <span>หัวข้อที่ ${flatItem.displayIndex}</span>
                                    <span><i data-lucide="images" class="mr-1"></i>${guide.slides.length} หน้า</span>
                                </div>
                            </div>
                        </div>
                    `;
                    list.appendChild(button);
                });

                group.appendChild(list);
                container.appendChild(group);
            });

            if (!container.children.length) {
                container.innerHTML = `
                    <div class="mx-2 mt-4 rounded-xl border border-dashed border-[#cad9e2] bg-[#f8fbfd] px-4 py-8 text-center">
                        <div class="mx-auto mb-3 grid h-10 w-10 place-items-center rounded-full bg-white text-[#93a3ad] shadow-sm"><i data-lucide="search"></i></div>
                        <p class="text-sm font-medium text-[#5d6e79]">ไม่พบหัวข้อคู่มือ</p>
                        <p class="mt-1 text-xs text-[#8b99a3]">ลองค้นหาด้วยคำอื่น</p>
                    </div>
                `;
            }

            refreshIcons();
        }

        function loadGuide(guideId) {
            currentGuideId = guideId;
            currentSlideIndex = 0;
            openedGuides.add(guideId);

            const guide = flatGuideList.find((item) => item.id === guideId);
            if (!guide) return;

            const searchInput = document.getElementById("search-guide");
            renderSidebar(searchInput ? searchInput.value : "");
            updateProgress();

            setText("guide-badge", `หัวข้อที่ ${guide.displayIndex}`);
            setText("guide-title", guide.title);
            setText("slide-header-title", guide.title);
            setHTML("guide-slide-count", `<i data-lucide="images"></i> ${guide.slides.length} สไลด์`);
            setText("guide-page-count-top", `${guide.slides.length} หน้า`);
            setHTML("guide-description", guide.description);
            setText("bc-module-name", guide.moduleTitle);
            setText("bc-guide-name", guide.title);
            setText("summary-module", guide.moduleTitle);
            setText("summary-index", `${guide.displayIndex} / ${flatGuideList.length}`);
            setText("summary-slide-count", `${guide.slides.length} สไลด์`);
            setText("slide-total-inline", `${guide.slides.length} สไลด์`);

            renderSlides(guide);
            updateGuideNavigationButtons(guide.displayIndex - 1);
            refreshIcons();

            document.querySelector("main").scrollTo({ top: 0, behavior: "smooth" });
            if (window.innerWidth < 768) toggleMobileSidebar(false);
        }

        function renderSlides(guide) {
            renderSlide(guide, currentSlideIndex);
            renderThumbnails(guide);
            updateSlideNavigationButtons(guide);
        }

        function renderSlide(guide, slideIndex) {
            const slide = guide.slides[slideIndex];
            if (!slide) return;

            document.getElementById("slide-image").src = slide.image;
            document.getElementById("slide-image").alt = slide.title;
            document.getElementById("slide-title").textContent = slide.title;
            document.getElementById("slide-caption").textContent = slide.caption;
            document.getElementById("current-slide-pill").textContent = `สไลด์ ${slideIndex + 1}`;
            document.getElementById("current-slide-label-top").textContent = `สไลด์ ${slideIndex + 1}/${guide.slides.length}`;
        }

        function renderThumbnails(guide) {
            const container = document.getElementById("slide-thumbnails");
            container.innerHTML = "";

            guide.slides.forEach((slide, index) => {
                const button = document.createElement("button");
                button.type = "button";
                button.className = `thumb-item ${index === currentSlideIndex ? "active" : ""} min-w-[210px] rounded-xl p-2 text-left`;
                button.onclick = () => {
                    currentSlideIndex = index;
                    renderSlides(guide);
                };
                button.innerHTML = `
                    <div class="overflow-hidden rounded-lg border border-[#e1eaf0] bg-white">
                        <img src="${slide.image}" alt="${slide.title}" class="aspect-[16/9] w-full object-cover">
                    </div>
                    <div class="pt-2">
                        <div class="mb-1 text-[11px] font-semibold text-[#06729f]">สไลด์ ${index + 1}</div>
                        <div class="line-clamp-2 text-xs font-medium leading-5 text-[#425561]">${slide.title}</div>
                    </div>
                `;
                container.appendChild(button);
            });
        }

        function updateSlideNavigationButtons(guide) {
            const prevButton = document.getElementById("btn-prev-slide");
            const nextButton = document.getElementById("btn-next-slide");

            prevButton.disabled = currentSlideIndex <= 0;
            prevButton.onclick = currentSlideIndex > 0 ? () => {
                currentSlideIndex -= 1;
                renderSlides(guide);
            } : null;

            nextButton.disabled = false;
            nextButton.classList.remove("btn-success");
            nextButton.classList.add(currentSlideIndex >= guide.slides.length - 1 ? "btn-success" : "btn-primary");

            if (currentSlideIndex < guide.slides.length - 1) {
                nextButton.innerHTML = `สไลด์ถัดไป <i data-lucide="chevron-right"></i>`;
                nextButton.onclick = () => {
                    currentSlideIndex += 1;
                    renderSlides(guide);
                };
            } else {
                nextButton.innerHTML = `<i data-lucide="circle-check"></i> อ่านครบหัวข้อนี้`;
                nextButton.onclick = () => {
                    Swal.fire({
                        title: "อ่านครบหัวข้อนี้แล้ว",
                        text: `คุณเปิดดูคู่มือหัวข้อ “${guide.title}” ครบทุกสไลด์แล้ว`,
                        icon: "success",
                        confirmButtonText: "รับทราบ",
                        confirmButtonColor: "#066f9f",
                        customClass: { popup: "rounded-2xl" }
                    });
                };
            }

            refreshIcons();
        }

        function updateGuideNavigationButtons(index) {
            const prevButton = document.getElementById("btn-prev-guide");
            const nextButton = document.getElementById("btn-next-guide");

            prevButton.disabled = index <= 0;
            prevButton.onclick = index > 0 ? () => loadGuide(flatGuideList[index - 1].id) : null;

            nextButton.classList.remove("btn-primary", "btn-success");

            if (index < flatGuideList.length - 1) {
                nextButton.disabled = false;
                nextButton.classList.add("btn-primary");
                nextButton.innerHTML = `หัวข้อถัดไป <i data-lucide="arrow-right"></i>`;
                nextButton.onclick = () => loadGuide(flatGuideList[index + 1].id);
            } else {
                nextButton.disabled = false;
                nextButton.classList.add("btn-success");
                nextButton.innerHTML = `<i data-lucide="circle-check"></i> จบคู่มือทั้งหมด`;
                nextButton.onclick = () => {
                    Swal.fire({
                        title: "เปิดอ่านครบทุกหัวข้อแล้ว",
                        text: "คุณได้เปิดอ่านคู่มือการใช้งานระบบครบถ้วนแล้ว",
                        icon: "success",
                        confirmButtonText: "รับทราบ",
                        confirmButtonColor: "#066f9f",
                        customClass: { popup: "rounded-2xl" }
                    });
                };
            }
        }

        function setupEventListeners() {
            const searchInput = document.getElementById("search-guide");
            const mobileMenuButton = document.getElementById("mobile-menu-btn");
            const closeSidebarButton = document.getElementById("close-sidebar-btn");
            const overlay = document.getElementById("sidebar-overlay");

            searchInput.addEventListener("input", (event) => renderSidebar(event.target.value));
            mobileMenuButton.addEventListener("click", () => toggleMobileSidebar(true));
            closeSidebarButton.addEventListener("click", () => toggleMobileSidebar(false));
            overlay.addEventListener("click", () => toggleMobileSidebar(false));

            document.addEventListener("keydown", (event) => {
                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k") {
                    event.preventDefault();
                    searchInput.focus();
                }
                if (event.key === "Escape" && window.innerWidth < 768) {
                    toggleMobileSidebar(false);
                }
                if (event.key === "ArrowRight") {
                    document.getElementById("btn-next-slide")?.click();
                }
                if (event.key === "ArrowLeft") {
                    document.getElementById("btn-prev-slide")?.click();
                }
            });
        }

        function toggleMobileSidebar(show) {
            const sidebar = document.getElementById("sidebar");
            const overlay = document.getElementById("sidebar-overlay");

            if (show) {
                sidebar.classList.remove("-translate-x-full");
                overlay.classList.remove("hidden");
            } else {
                sidebar.classList.add("-translate-x-full");
                overlay.classList.add("hidden");
            }
        }

        window.addEventListener("DOMContentLoaded", init);
    </script>
</body>
</html>