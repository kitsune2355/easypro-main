<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<title>ประวัติเครื่องจักร | Machine History</title>
<link rel="icon" type="image/png" href="logo - easypro2.png">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/lucide@latest"></script>
<style>
    :root {
        --color-primary: #006B9F;
        --color-secondary: #04ADFF;
    }
    body {
        font-family: 'Kanit', 'Noto Sans Thai', sans-serif;
        background-color: #F0F4F8;
    }
    .bg-gradient-brand {
        background: linear-gradient(135deg, #006B9F, #04ADFF);
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
    .custom-scroll::-webkit-scrollbar { height: 6px; width: 6px; }
    .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

    .skeleton {
        background: linear-gradient(90deg, #e2e8f0 25%, #eef2f6 37%, #e2e8f0 63%);
        background-size: 400% 100%;
        animation: skeleton-loading 1.4s ease infinite;
    }
    @keyframes skeleton-loading {
        0% { background-position: 100% 50%; }
        100% { background-position: 0 50%; }
    }

    /* แท็บ แจ้งซ่อม / แผน PM */
    .mh-tab { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 8px; border-radius: 14px; font-size: 13px; font-weight: 700; color: #64748b; transition: background .2s, color .2s; }
    .mh-tab.on { background: var(--color-primary, #0284c7); color: #fff; box-shadow: 0 4px 12px rgba(2, 132, 199, .25); }
    .mh-tab .mh-n { font-size: 11px; padding: 0 7px; border-radius: 999px; background: rgba(100, 116, 139, .12); line-height: 18px; }
    .mh-tab.on .mh-n { background: rgba(255, 255, 255, .25); }
    /* แท็บย่อยในแผน PM: งาน PM ตามรอบแผน / ประวัติแผน PM */
    .pm-sub { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 6px; border-radius: 12px; font-size: 12.5px; font-weight: 700; color: #64748b; }
    .pm-sub.on { background: #fff; color: #047857; box-shadow: 0 1px 3px rgba(15, 23, 42, .12); }
    .pm-sub .mh-n { font-size: 11px; padding: 0 7px; border-radius: 999px; background: rgba(100, 116, 139, .12); line-height: 18px; }
    .pm-sub.on .mh-n { background: #d1fae5; }
    .timeline-item { position: relative; padding-left: 28px; }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 6px; top: 4px;
        width: 12px; height: 12px;
        border-radius: 999px;
        background: #fff;
        border: 3px solid var(--color-primary);
        z-index: 2;
    }
    .timeline-item::after {
        content: '';
        position: absolute;
        left: 11px; top: 16px;
        width: 2px; bottom: -28px;
        background: #e2e8f0;
        z-index: 1;
    }
    .timeline-item:last-child::after { display: none; }

    .lightbox-img { transition: transform 0.25s ease; }
    #lightbox.hidden { display: none; }
</style>
</head>
<body class="min-h-screen antialiased text-slate-700 pb-10">

    <!-- Top Brand Bar -->
    <header class="bg-gradient-brand text-white sticky top-0 z-30 shadow-lg shadow-sky-100">
        <div class="max-w-2xl mx-auto px-4 py-4 flex items-center gap-3">
            <div class="p-2 bg-white/15 rounded-xl backdrop-blur-sm">
                <i data-lucide="scan-line" class="w-6 h-6"></i>
            </div>
            <div class="min-w-0 flex-1">
                <h1 class="text-base font-bold leading-tight truncate">ประวัติเครื่องจักร</h1>
                <p class="text-[11px] text-sky-50/90 leading-none mt-1">Machine Asset &amp; Repair History</p>
            </div>
            <a id="btn-repair-top" href="#" onclick="return false;"
               class="hidden flex-shrink-0 items-center gap-1.5 bg-white text-[var(--color-primary)] px-3.5 py-2 rounded-xl text-xs font-bold shadow-md active:scale-95 transition-all">
                <i data-lucide="wrench" class="w-4 h-4"></i>
                <span>แจ้งซ่อม</span>
            </a>
        </div>
    </header>

    <main class="max-w-2xl mx-auto px-4 pt-5 space-y-5">

        <!-- Loading State -->
        <div id="loading-state" class="space-y-5">
            <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-sm space-y-4">
                <div class="skeleton h-5 w-2/3 rounded-lg"></div>
                <div class="skeleton h-4 w-1/3 rounded-lg"></div>
                <div class="skeleton h-32 w-full rounded-2xl"></div>
            </div>
            <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-sm space-y-3">
                <div class="skeleton h-4 w-1/2 rounded-lg"></div>
                <div class="skeleton h-16 w-full rounded-2xl"></div>
                <div class="skeleton h-16 w-full rounded-2xl"></div>
            </div>
        </div>

        <!-- Error State -->
        <div id="error-state" class="hidden bg-white rounded-3xl border border-slate-200 p-8 shadow-sm text-center space-y-3">
            <div class="w-16 h-16 mx-auto rounded-full bg-rose-50 flex items-center justify-center">
                <i data-lucide="alert-triangle" class="w-8 h-8 text-rose-500"></i>
            </div>
            <h2 class="font-bold text-slate-800">ไม่พบข้อมูล</h2>
            <p id="error-message" class="text-sm text-slate-500">ไม่สามารถโหลดข้อมูลเครื่องจักรได้</p>
            <button onclick="loadData()" class="mt-2 inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-sm font-semibold text-slate-600 transition-all">
                <i data-lucide="refresh-cw" class="w-4 h-4"></i> ลองใหม่อีกครั้ง
            </button>
        </div>

        <!-- Content -->
        <div id="content" class="hidden space-y-5">

            <!-- Machine Card -->
            <section class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div id="image-area"></div>

                <div class="p-5 space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[11px] font-bold text-sky-600 uppercase tracking-wider" id="m-code">-</p>
                            <h2 class="text-lg font-bold text-slate-900 leading-snug break-words" id="m-name">-</h2>
                            <p class="text-xs text-slate-400 mt-0.5" id="m-type">-</p>
                        </div>
                        <span id="m-status" class="flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold bg-slate-50 text-slate-500 border border-slate-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            <span>-</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="bg-slate-50 rounded-2xl p-3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1"><i data-lucide="tag" class="w-3 h-3"></i>ยี่ห้อ / รุ่น</p>
                            <p class="font-semibold text-slate-700 mt-1 break-words" id="m-brand-model">-</p>
                        </div>
                        <div class="bg-slate-50 rounded-2xl p-3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1"><i data-lucide="hash" class="w-3 h-3"></i>ซีเรียลนัมเบอร์</p>
                            <p class="font-semibold text-slate-700 mt-1 break-words" id="m-serial">-</p>
                        </div>
                        <div class="bg-slate-50 rounded-2xl p-3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1"><i data-lucide="map-pin" class="w-3 h-3"></i>สถานที่ติดตั้ง</p>
                            <p class="font-semibold text-slate-700 mt-1 break-words" id="m-location">-</p>
                        </div>
                        <div class="bg-slate-50 rounded-2xl p-3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1"><i data-lucide="calendar-clock" class="w-3 h-3"></i>รับประกันถึง</p>
                            <p class="font-semibold text-slate-700 mt-1 break-words" id="m-warranty">-</p>
                        </div>
                        <div class="bg-slate-50 rounded-2xl p-3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1"><i data-lucide="building-2" class="w-3 h-3"></i>ผู้จำหน่าย / บริษัท</p>
                            <p class="font-semibold text-slate-700 mt-1 break-words" id="m-company">-</p>
                        </div>
                        <div class="bg-slate-50 rounded-2xl p-3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1"><i data-lucide="briefcase" class="w-3 h-3"></i>หน่วยงาน</p>
                            <p class="font-semibold text-slate-700 mt-1 break-words" id="m-agency">-</p>
                        </div>
                    </div>

                    <div id="m-remark-box" class="hidden bg-amber-50 border border-amber-100 rounded-2xl p-3">
                        <p class="text-[10px] font-bold text-amber-600 uppercase tracking-wider flex items-center gap-1"><i data-lucide="sticky-note" class="w-3 h-3"></i>หมายเหตุ</p>
                        <p class="text-sm text-amber-800 mt-1 break-words" id="m-remark">-</p>
                    </div>
                </div>
            </section>

            <!-- แท็บ: แจ้งซ่อม / แผน PM -->
            <div id="mh-tabs" class="grid grid-cols-2 gap-1 p-1 bg-white rounded-2xl border border-slate-200 shadow-sm sticky top-[76px] z-20" role="tablist">
                <button type="button" class="mh-tab on" data-tab="repair" role="tab"><i data-lucide="wrench" class="w-4 h-4"></i>แจ้งซ่อม <span class="mh-n" id="tab-n-repair">0</span></button>
                <button type="button" class="mh-tab" data-tab="pm" role="tab"><i data-lucide="clipboard-check" class="w-4 h-4"></i>แผน PM <span class="mh-n" id="tab-n-pm">0</span></button>
            </div>

            <div id="panel-pm" class="hidden space-y-5">
            <div class="grid grid-cols-2 gap-1 p-1 bg-slate-100 rounded-2xl" role="tablist">
                <button type="button" class="pm-sub on" data-sub="due" role="tab">งาน PM ตามรอบแผน <span class="mh-n" id="sub-n-due">0</span></button>
                <button type="button" class="pm-sub" data-sub="hist" role="tab">ประวัติแผน PM <span class="mh-n" id="sub-n-hist">0</span></button>
            </div>

            <div id="pm-sub-due" class="space-y-5">
            <!-- งาน PM ของเครื่องนี้ (เฉพาะช่าง / super admin ที่ล็อกอินหน่วยงานเดียวกัน — handle_machine_pm.php) -->
            <section id="pm-section" class="hidden bg-white rounded-3xl border border-emerald-200 shadow-sm p-5">
                <h3 class="font-bold text-slate-800 flex items-center gap-2 mb-3">
                    <i data-lucide="clipboard-check" class="w-4.5 h-4.5 text-emerald-600"></i>
                    งาน PM ตามรอบแผน
                </h3>
                <div id="pm-list" class="space-y-2"></div>
            </section>

            <!-- รอบที่ค้าง (คนทั่วไป: ดูอย่างเดียว — ช่างใช้การ์ดด้านบน) + รอบถัดไป -->
            <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 space-y-4">
                <div id="pmd-due" class="hidden"></div>
                <div id="pmh-upcoming"></div>
            </section>
            </div>

            <!-- ประวัติแผน PM (ทุกคนดูได้ — handle_machine_pm.php?action=history) -->
            <section id="pm-sub-hist" class="hidden bg-white rounded-3xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i data-lucide="history" class="w-4.5 h-4.5 text-emerald-600"></i>
                        ประวัติแผน PM
                    </h3>
                    <span id="pmh-stat" class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700"></span>
                </div>
                <div id="pmh-list" class="space-y-2"></div>
                <div class="pt-4">
                    <button id="pmh-more" onclick="loadPmHistory(true)" class="hidden w-full py-2.5 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i><span>โหลดเพิ่มเติม</span>
                    </button>
                </div>
                <div id="pmh-empty" class="hidden text-center py-8">
                    <div class="w-14 h-14 mx-auto rounded-full bg-slate-50 flex items-center justify-center mb-3"><i data-lucide="calendar-x" class="w-7 h-7 text-slate-400"></i></div>
                    <p class="text-sm font-semibold text-slate-600">ยังไม่มีรอบ PM ที่ผ่านมา</p>
                    <p class="text-xs text-slate-400 mt-1">เครื่องจักรนี้ยังไม่มีรอบ PM ที่ถึงกำหนด</p>
                </div>
            </section>
            </div>

            <!-- Repair History -->
            <section id="panel-repair" class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <i data-lucide="wrench" class="w-4.5 h-4.5 text-sky-600"></i>
                        ประวัติการแจ้งซ่อม
                    </h3>
                    <span id="history-count" class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-sky-50 text-sky-600">0 รายการ</span>
                </div>

                <div id="history-list" class="space-y-6"></div>

                <div class="pt-4">
                    <button id="btn-load-more" onclick="loadMoreHistory()"
                            class="hidden w-full py-2.5 rounded-xl text-xs font-bold text-sky-600 bg-sky-50 hover:bg-sky-100 transition-all flex items-center justify-center gap-2">
                        <i data-lucide="chevron-down" class="w-3.5 h-3.5"></i>
                        <span id="btn-load-more-label">โหลดเพิ่มเติม</span>
                    </button>
                </div>

                <div id="history-empty" class="hidden text-center py-8">
                    <div class="w-14 h-14 mx-auto rounded-full bg-emerald-50 flex items-center justify-center mb-3">
                        <i data-lucide="check-circle-2" class="w-7 h-7 text-emerald-500"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-600">ยังไม่มีประวัติการแจ้งซ่อม</p>
                    <p class="text-xs text-slate-400 mt-1">เครื่องจักรนี้ยังไม่เคยถูกแจ้งซ่อมในระบบ</p>
                </div>
            </section>
        </div>
    </main>

    <!-- Sticky Bottom Repair Bar (มือถือ) -->
    <div id="repair-bar" class="hidden fixed bottom-0 left-0 right-0 z-40 p-3 bg-gradient-to-t from-white via-white/95 to-white/0 pt-6 sm:hidden flex gap-2">
        <!-- ทำ PM (มีรอบถึงกำหนด + สิทธิ์ช่าง/super admin) -->
        <a id="btn-pm-bottom" href="#" class="hidden flex-1 items-center justify-center gap-2 py-3.5 rounded-2xl font-bold text-white bg-emerald-600 shadow-2xl shadow-emerald-200 active:scale-95 transition-all">
            <i data-lucide="clipboard-check" class="w-5 h-5"></i>
            <span>ทำ PM</span>
        </a>
        <a id="btn-repair-bottom" href="#" onclick="return false;"
           class="btn-gradient flex-1 flex items-center justify-center gap-2 py-3.5 rounded-2xl font-bold shadow-2xl shadow-sky-200 active:scale-95 transition-all">
            <i data-lucide="wrench" class="w-5 h-5"></i>
            <span>แจ้งซ่อมเครื่องจักรนี้</span>
        </a>
    </div>

    <!-- Lightbox -->
    <div id="lightbox" class="hidden fixed inset-0 bg-black/85 z-50 flex items-center justify-center p-4" onclick="closeLightbox()">
        <img id="lightbox-img" src="" class="lightbox-img max-w-full max-h-full rounded-2xl shadow-2xl">
        <button class="absolute top-4 right-4 text-white bg-white/10 hover:bg-white/20 rounded-full p-2" onclick="closeLightbox()">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>
    </div>

<script>
lucide.createIcons();

const params = new URLSearchParams(window.location.search);
const assetId = params.get('asset_id') || params.get('id') || '';
const agId = params.get('ag_id') || '';

const HISTORY_PER_PAGE = 15;
let historyPage = 1;
let historyLoadingMore = false;

const STATUS_STYLE = {
    'ปกติ':   { color: 'emerald', label: 'ปกติ' },
    'แจ้งซ่อม': { color: 'amber',   label: 'แจ้งซ่อม' },
    'ชำรุด':  { color: 'rose',    label: 'ชำรุด' },
    'สำรอง':  { color: 'sky',     label: 'สำรอง' }
};

function repairStatusStyle(status) {
    const s = (status || '').toString();
    if (/เสร็จ|สำเร็จ|complete/i.test(s)) return { color: 'emerald', icon: 'check-circle-2' };
    if (/ยกเลิก|cancel/i.test(s))          return { color: 'slate',   icon: 'x-circle' };
    if (/ดำเนินการ|progress/i.test(s))     return { color: 'sky',     icon: 'loader-circle' };
    if (/รอ|pending/i.test(s))             return { color: 'amber',   icon: 'clock' };
    return { color: 'slate', icon: 'circle-dot' };
}

function formatDate(dateStr) {
    if (!dateStr || dateStr === '0000-00-00' || dateStr === '0000-00-00 00:00:00') return '-';
    const d = new Date(dateStr.replace(' ', 'T'));
    if (isNaN(d.getTime())) return dateStr;
    
    return d.toLocaleDateString('th-TH', { day: '2-digit', month: '2-digit', year: 'numeric' }) +
           ' ' + d.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' });
}

// สร้างลิงก์ไปหน้าแจ้งซ่อม พร้อมพารามิเตอร์ให้ระบบค้นหา/เลือกเครื่องจักรนี้ให้อัตโนมัติ
function buildRepairUrl(machine) {
    const p = new URLSearchParams();
    p.set('asset_code', machine.asset_id || assetId);
    if (machine.machine_name) p.set('asset_name', machine.machine_name);
    if (agId) p.set('ag_id', agId);
    if (machine.area_id) p.set('area_id', machine.area_id);
    if (machine.ac_id)   p.set('ac_id', machine.ac_id);
    if (machine.ar_id)   p.set('ar_id', machine.ar_id);

    // main.php ใช้ query string ก่อนเครื่องหมาย # (hash route ไปหน้าแจ้งซ่อม)
    const url = new URL('main.php', window.location.href);
    url.search = p.toString();
    url.hash = 'maintenance_request.php';
    return url.toString();
}

function setupRepairButtons(machine) {
    const href = buildRepairUrl(machine);

    const top = document.getElementById('btn-repair-top');
    top.href = href;
    top.classList.remove('hidden');
    top.classList.add('flex');
    top.onclick = null;

    const bottomWrap = document.getElementById('repair-bar');
    const bottom = document.getElementById('btn-repair-bottom');
    bottom.href = href;
    bottom.onclick = null;
    bottomWrap.classList.remove('hidden');
    document.body.classList.add('pb-28');
}

// ===== แท็บ แจ้งซ่อม / แผน PM (จำแท็บใน #pm) =====
let tabChosen = location.hash === '#pm';
function showTab(name, byUser) {
    if (byUser) tabChosen = true;
    document.querySelectorAll('.mh-tab').forEach(b => b.classList.toggle('on', b.dataset.tab === name));
    document.getElementById('panel-repair').classList.toggle('hidden', name !== 'repair');
    document.getElementById('panel-pm').classList.toggle('hidden', name !== 'pm');
    history.replaceState(null, '', name === 'pm' ? '#pm' : location.pathname + location.search);
}
document.querySelectorAll('.mh-tab').forEach(b => b.addEventListener('click', () => showTab(b.dataset.tab, true)));
if (location.hash === '#pm') showTab('pm');
function showPmSub(name) {
    document.querySelectorAll('.pm-sub').forEach(b => b.classList.toggle('on', b.dataset.sub === name));
    document.getElementById('pm-sub-due').classList.toggle('hidden', name !== 'due');
    document.getElementById('pm-sub-hist').classList.toggle('hidden', name !== 'hist');
}
document.querySelectorAll('.pm-sub').forEach(b => b.addEventListener('click', () => showPmSub(b.dataset.sub)));
let pmCanDo = false;   // ช่าง / super admin (มีการ์ดทำ PM แล้ว ไม่ต้องแสดงรายการรอบค้างซ้ำ)

// ===== ประวัติแผน PM: รอบถัดไป + รอบที่ถึงกำหนดแล้ว (ใหม่สุดก่อน) =====
const PMH_STATE = {
    approved: ['ตรวจรับแล้ว', 'bg-emerald-100 text-emerald-700', 'badge-check'],
    done:     ['ทำแล้ว', 'bg-emerald-100 text-emerald-700', 'check-circle-2'],
    waiting:  ['รอตรวจรับ', 'bg-emerald-50 text-emerald-700 border border-dashed border-emerald-500', 'hourglass'],
    today:    ['ถึงกำหนดวันนี้', 'bg-sky-100 text-sky-700', 'clock'],
    missed:   ['เลยกำหนด ยังไม่ทำ', 'bg-rose-100 text-rose-700', 'alert-triangle'],
};
let pmhPage = 0, pmhLoading = false;
const thShortDate = d => d ? new Date(String(d).slice(0, 10) + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' }) : '-';
// รอบที่ถึงกำหนดแล้วยังไม่ทำ (ทุกคนเห็น) — ช่าง/super admin มีการ์ดทำ PM ด้านบนแทน
let pmDue = [];
function renderPmDue() {
    const box = document.getElementById('pmd-due');
    if (pmCanDo) { box.classList.add('hidden'); return; }
    box.classList.remove('hidden');
    box.innerHTML = '<p class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider flex items-center gap-1 mb-1.5"><i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i>รอบที่ถึงกำหนด</p>'
        + (pmDue.length ? pmDue.map(d => '<div class="flex items-center justify-between gap-2 text-sm py-1"><span class="text-slate-700 truncate">' + esc(d.checksheet || 'ใบงาน PM') + '</span>'
            + '<span class="flex-shrink-0 text-xs font-semibold ' + (d.overdue ? 'text-rose-600' : 'text-sky-700') + '">' + thShortDate(d.event_date) + (d.overdue ? ' · เลยกำหนด' : ' · วันนี้') + '</span></div>').join('')
            : '<p class="text-sm text-slate-500 flex items-center gap-2"><i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500"></i>ไม่มีรอบที่ค้าง</p>');
    lucide.createIcons();
}
async function loadPmHistory(more) {
    if (pmhLoading) return;
    pmhLoading = true;
    const page = more ? pmhPage + 1 : 1;
    let r;
    try {
        const url = new URL('handle_machine_pm.php', window.location.href);
        url.searchParams.set('action', 'history');
        url.searchParams.set('asset_code', assetId);
        if (agId) url.searchParams.set('ag_id', agId);
        url.searchParams.set('page', page);
        url.searchParams.set('per_page', HISTORY_PER_PAGE);
        r = await (await fetch(url.toString(), { credentials: 'same-origin' })).json();
    } catch (e) { r = null; }
    pmhLoading = false;
    if (!r || !r.success) return;
    pmhPage = page;
    const s = r.stats;
    document.getElementById('tab-n-pm').innerText = s.total;
    document.getElementById('pmh-stat').innerText = s.total ? 'ทำแล้ว ' + s.done + '/' + s.total + ' รอบ' : '0 รอบ';
    document.getElementById('sub-n-hist').innerText = s.total;
    if (!more) {
        document.getElementById('sub-n-due').innerText = r.due.length;
        pmDue = r.due; renderPmDue();
        const up = document.getElementById('pmh-upcoming');
        up.innerHTML = '<p class="text-[11px] font-bold text-sky-700 uppercase tracking-wider flex items-center gap-1 mb-1.5"><i data-lucide="calendar-clock" class="w-3.5 h-3.5"></i>รอบถัดไป</p>'
            + (r.upcoming.length ? '' : '<p class="text-sm text-slate-400">ไม่มีรอบถัดไปในแผน</p>')
            + r.upcoming.map(u => '<div class="flex items-center justify-between gap-2 text-sm py-0.5"><span class="text-slate-700 truncate">' + esc(u.checksheet || 'ใบงาน PM') + '</span><span class="flex-shrink-0 font-semibold text-sky-700">' + thShortDate(u.event_date) + '</span></div>').join('');
    }
    const html = r.items.map(it => {
        const st = PMH_STATE[it.state] || PMH_STATE.done;
        const rows = [];
        if (it.actual_date) rows.push('ทำเมื่อ ' + thShortDate(it.actual_date) + (it.inspector ? ' · ' + esc(it.inspector) : ''));
        if (it.approver) rows.push('ตรวจรับโดย ' + esc(it.approver) + (it.approved_at ? ' · ' + thShortDate(it.approved_at) : ''));
        const recorded = ['approved', 'done', 'waiting'].includes(it.state);
        // ดูใบงานได้เฉพาะคนที่ล็อกอิน (pm_worksheet.php ตรวจ session)
        const link = recorded && r.logged_in ? '<a href="pm_worksheet.php?plan_id=' + it.event_id + '&mode=history" class="flex-shrink-0 inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white text-[11px] font-bold text-sky-700"><i data-lucide="file-text" class="w-3.5 h-3.5"></i>ดูใบงาน</a>' : '';
        return '<div class="rounded-2xl border border-slate-100 bg-slate-50 p-3.5">'
            + '<div class="flex items-start justify-between gap-2"><div class="min-w-0">'
            + '<p class="text-xs text-slate-400 font-medium flex items-center gap-1.5 flex-wrap"><i data-lucide="calendar" class="w-3.5 h-3.5"></i>กำหนด ' + thShortDate(it.event_date) + (it.doc_no ? ' <span class="text-slate-300">•</span> <span class="font-mono">' + esc(it.doc_no) + '</span>' : '') + '</p>'
            + '<p class="font-semibold text-slate-800 text-sm mt-1 break-words">' + esc(it.checksheet || 'ใบงาน PM') + '</p></div>'
            + '<span class="flex-shrink-0 flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold ' + st[1] + '"><i data-lucide="' + st[2] + '" class="w-3 h-3"></i>' + st[0] + '</span></div>'
            + (rows.length || link ? '<div class="mt-2 flex items-end justify-between gap-2"><div class="text-xs text-slate-500 space-y-0.5 min-w-0">' + rows.map(x => '<p>' + x + '</p>').join('') + '</div>' + link + '</div>' : '')
            + '</div>';
    }).join('');
    const list = document.getElementById('pmh-list');
    if (more) list.insertAdjacentHTML('beforeend', html); else list.innerHTML = html;
    document.getElementById('pmh-empty').classList.toggle('hidden', !!(more || r.items.length));
    document.getElementById('pmh-more').classList.toggle('hidden', !r.has_more);
    lucide.createIcons();
}

// ===== ทำ PM ตามรอบแผน (ช่าง / super admin) =====
//  แสดงรอบที่ถึงกำหนด (วันนี้ + เลยกำหนด) => เปิดใบงานบันทึกผล PM · ไม่มีรอบ = บอกรอบถัดไป · คนทั่วไป/ไม่ได้ล็อกอิน = ไม่แสดง
async function loadPm() {
    let r;
    try {
        const url = new URL('handle_machine_pm.php', window.location.href);
        url.searchParams.set('asset_code', assetId);
        if (agId) url.searchParams.set('ag_id', agId);
        r = await (await fetch(url.toString(), { credentials: 'same-origin' })).json();
    } catch (e) { return; }
    const box = document.getElementById('pm-section'), list = document.getElementById('pm-list');
    if (!r || !r.success) return;
    if (!r.can) {
        if (r.reason !== 'agency') return;
        list.innerHTML = '<p class="text-sm text-slate-500">เครื่องนี้อยู่หน่วยงาน <b>' + esc(r.agency_name || '-') + '</b> — เข้าสู่ระบบหน่วยงานนี้ก่อนจึงจะทำ PM ได้</p>';
        box.classList.remove('hidden');
        return;
    }
    pmCanDo = true; renderPmDue();
    const th = d => new Date(d + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
    const wsUrl = id => 'pm_worksheet.php?plan_id=' + encodeURIComponent(id);
    if (!r.due.length) {
        list.innerHTML = '<p class="text-sm text-slate-500 flex items-center gap-2"><i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-500"></i>ไม่มีรอบ PM ที่ต้องทำตอนนี้</p>'
            + (r.next ? '<p class="text-xs text-slate-400 mt-1">รอบถัดไป ' + th(r.next.event_date) + ' · ' + esc(r.next.checksheet) + '</p>' : '');
    } else {
        list.innerHTML = r.due.map(d => '<a href="' + wsUrl(d.event_id) + '" class="flex items-center gap-3 p-3 rounded-2xl border ' + (d.overdue ? 'border-rose-200 bg-rose-50/50' : 'border-emerald-200 bg-emerald-50/50') + ' active:scale-[.99] transition-all">'
            + '<div class="min-w-0 flex-1"><p class="font-semibold text-slate-800 text-sm truncate">' + esc(d.checksheet || 'ใบงาน PM') + '</p>'
            + '<p class="text-xs mt-0.5 ' + (d.overdue ? 'text-rose-600 font-semibold' : 'text-slate-500') + '">กำหนด ' + th(d.event_date) + (d.overdue ? ' · เลยกำหนด' : ' · วันนี้') + '</p></div>'
            + '<span class="flex-shrink-0 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold"><i data-lucide="clipboard-check" class="w-4 h-4"></i>ทำ PM</span></a>').join('');
        // แถบล่าง (มือถือ): ทำ PM รอบที่ค้างนานสุดก่อน
        const b = document.getElementById('btn-pm-bottom');
        b.href = wsUrl(r.due[0].event_id);
        b.classList.remove('hidden'); b.classList.add('flex');
        if (!tabChosen) showTab('pm');   // มีรอบที่ต้องทำ => เปิดแท็บแผน PM ให้เลย
    }
    box.classList.remove('hidden');
    lucide.createIcons();
}

function esc(str) {
    if (str === null || str === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
}

function showError(msg) {
    document.getElementById('loading-state').classList.add('hidden');
    document.getElementById('content').classList.add('hidden');
    document.getElementById('error-state').classList.remove('hidden');
    document.getElementById('error-message').innerText = msg;
    lucide.createIcons();
}

function openLightbox(src) {
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox').classList.remove('hidden');
}
function closeLightbox() {
    document.getElementById('lightbox').classList.add('hidden');
}

function renderImages(machine) {
    const area = document.getElementById('image-area');
    const images = (machine.images && machine.images.length > 0)
        ? machine.images
        : [machine.image1, machine.image2, machine.image3].filter(Boolean);

    if (images.length === 0) {
        area.innerHTML = `
            <div class="w-full h-40 bg-slate-50 flex items-center justify-center border-b border-slate-100">
                <i data-lucide="image-off" class="w-10 h-10 text-slate-300"></i>
            </div>`;
        return;
    }

    const main = images[0];
    const thumbs = images.slice(1);

    area.innerHTML = `
        <div class="relative">
            <img src="${esc(main)}" onclick="openLightbox('${esc(main)}')"
                 class="w-full h-52 object-cover cursor-zoom-in"
                 onerror="this.closest('#image-area').innerHTML='<div class=&quot;w-full h-40 bg-slate-50 flex items-center justify-center border-b border-slate-100&quot;><i data-lucide=&quot;image-off&quot; class=&quot;w-10 h-10 text-slate-300&quot;></i></div>'; lucide.createIcons();">
            ${thumbs.length > 0 ? `
            <div class="absolute bottom-3 right-3 flex gap-2">
                ${thumbs.map(t => `<img src="${esc(t)}" onclick="event.stopPropagation(); openLightbox('${esc(t)}')" class="w-12 h-12 object-cover rounded-lg border-2 border-white shadow-md cursor-zoom-in" onerror="this.style.display='none'">`).join('')}
            </div>` : ''}
        </div>`;
}

function renderMachine(machine) {
    renderImages(machine);

    document.getElementById('m-code').innerText = machine.asset_id ? `รหัส: ${machine.asset_id}` : '-';
    document.getElementById('m-name').innerText = machine.machine_name || 'ไม่ระบุชื่อเครื่องจักร';
    document.getElementById('m-type').innerText = machine.type_name || 'ไม่ระบุประเภท';

    const st = STATUS_STYLE[machine.status] || { color: 'slate', label: machine.status || 'ไม่ทราบสถานะ' };
    const statusEl = document.getElementById('m-status');
    statusEl.className = `flex-shrink-0 flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-[11px] font-bold bg-${st.color}-50 text-${st.color}-600 border border-${st.color}-100`;
    statusEl.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-${st.color}-500"></span><span>${esc(st.label)}</span>`;

    const brandModel = [machine.brand, machine.model].filter(v => v && v !== '-').join(' / ');
    document.getElementById('m-brand-model').innerText = brandModel || '-';
    document.getElementById('m-serial').innerText = machine.serial || '-';

    const loc = [machine.location, machine.floor, machine.room].filter(v => v && v !== '-' && v !== 'ไม่ระบุ').join(' / ');
    document.getElementById('m-location').innerText = loc || 'ไม่ระบุ';

    document.getElementById('m-warranty').innerText = machine.warranty ? formatDate(machine.warranty).split(' ')[0] : '-';
    document.getElementById('m-company').innerText = machine.company || '-';
    document.getElementById('m-agency').innerText = machine.ag_contract || '-';

    if (machine.remark && machine.remark.trim() !== '') {
        document.getElementById('m-remark-box').classList.remove('hidden');
        document.getElementById('m-remark').innerText = machine.remark;
    }

    setupRepairButtons(machine);
}

function renderHistory(history, meta, append) {
    const list = document.getElementById('history-list');
    const empty = document.getElementById('history-empty');
    const count = document.getElementById('history-count');
    const loadMoreBtn = document.getElementById('btn-load-more');

    const total = meta && typeof meta.total === 'number' ? meta.total : history.length;
    count.innerText = `${total} รายการ`;
    document.getElementById('tab-n-repair').innerText = total;

    if (!append && (!history || history.length === 0)) {
        list.innerHTML = '';
        empty.classList.remove('hidden');
        loadMoreBtn.classList.add('hidden');
        return;
    }
    empty.classList.add('hidden');

    const cardsHtml = historyItemsHtml(history);
    if (append) {
        list.insertAdjacentHTML('beforeend', cardsHtml);
    } else {
        list.innerHTML = cardsHtml;
    }

    const hasMore = !!(meta && meta.has_more);
    loadMoreBtn.classList.toggle('hidden', !hasMore);
    document.getElementById('btn-load-more-label').innerText = 'โหลดเพิ่มเติม';
    loadMoreBtn.disabled = false;

    lucide.createIcons();
}

function historyItemsHtml(history) {
    return history.map(item => {
        const st = repairStatusStyle(item.status);
        const dateVal = item.created_at || item.report_date || item.date || '';
        const requestNo = item.rp_format || item.request_no || '';

        const optionalRows = [];
        if (item.completed_at) optionalRows.push(['เสร็จสิ้นเมื่อ', formatDate(item.completed_at)]);
        if (item.technician || item.assigned_to) optionalRows.push(['ผู้รับผิดชอบ', item.technician || item.assigned_to]);
        if (item.reported_by || item.requester) optionalRows.push(['ผู้แจ้ง', item.reported_by || item.requester]);

        return `
        <div class="timeline-item">
            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                <div class="flex items-start justify-between gap-2 flex-wrap">
                    <div class="flex items-center gap-2 text-xs text-slate-400 font-medium">
                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                        ${formatDate(dateVal)}
                        ${requestNo ? `<span class="text-slate-300">•</span><span class="font-mono">${esc(requestNo)}</span>` : ''}
                    </div>
                    <span class="flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-${st.color}-100 text-${st.color}-700">
                        <i data-lucide="${st.icon}" class="w-3 h-3"></i>
                        ${esc(item.status || 'ไม่ทราบสถานะ')}
                    </span>
                </div>

                <div class="mt-3 space-y-2">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">อาการ / ปัญหาที่แจ้ง</p>
                        <p class="text-sm text-slate-700 mt-0.5 break-words">${esc(item.problem_detail) || '-'}</p>
                    </div>
                    ${item.completed_solution ? `
                    <div class="pt-2 border-t border-slate-200/70">
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">วิธีแก้ไข / ผลการซ่อม</p>
                        <p class="text-sm text-slate-700 mt-0.5 break-words">${esc(item.completed_solution)}</p>
                    </div>` : ''}
                    ${optionalRows.length > 0 ? `
                    <div class="pt-2 border-t border-slate-200/70 grid grid-cols-2 gap-2">
                        ${optionalRows.map(([label, val]) => `
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">${esc(label)}</p>
                            <p class="text-xs text-slate-600 mt-0.5 break-words">${esc(val)}</p>
                        </div>`).join('')}
                    </div>` : ''}
                </div>
            </div>
        </div>`;
    }).join('');
}


async function loadData() {
    document.getElementById('error-state').classList.add('hidden');
    document.getElementById('content').classList.add('hidden');
    document.getElementById('loading-state').classList.remove('hidden');

    if (!assetId) {
        showError('ลิงก์ไม่ถูกต้อง ไม่พบรหัสครุภัณฑ์ (asset_id)');
        return;
    }

    historyPage = 1;

    try {
        const url = new URL('handle_machine_info.php', window.location.href);
        url.searchParams.set('action', 'get_public_history');
        url.searchParams.set('asset_id', assetId);
        url.searchParams.set('page', historyPage);
        url.searchParams.set('per_page', HISTORY_PER_PAGE);
        if (agId) url.searchParams.set('ag_id', agId);

        const res = await fetch(url.toString());
        const json = await res.json();

        if (json.success) {
            renderMachine(json.data.machine);
            loadPm();
            loadPmHistory(false);
            renderHistory(json.data.history || [], json.data.history_meta, false);
            document.getElementById('loading-state').classList.add('hidden');
            document.getElementById('content').classList.remove('hidden');
            lucide.createIcons();
        } else {
            showError(json.error || 'ไม่พบข้อมูลเครื่องจักรนี้ในระบบ');
        }
    } catch (err) {
        console.error(err);
        showError('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์ กรุณาลองใหม่อีกครั้ง');
    }
}

async function loadMoreHistory() {
    if (historyLoadingMore || !assetId) return;
    historyLoadingMore = true;

    const btn = document.getElementById('btn-load-more');
    const label = document.getElementById('btn-load-more-label');
    btn.disabled = true;
    label.innerText = 'กำลังโหลด...';

    try {
        const nextPage = historyPage + 1;
        const url = new URL('handle_machine_info.php', window.location.href);
        url.searchParams.set('action', 'get_public_history');
        url.searchParams.set('asset_id', assetId);
        url.searchParams.set('page', nextPage);
        url.searchParams.set('per_page', HISTORY_PER_PAGE);
        if (agId) url.searchParams.set('ag_id', agId);

        const res = await fetch(url.toString());
        const json = await res.json();

        if (json.success) {
            historyPage = nextPage;
            renderHistory(json.data.history || [], json.data.history_meta, true);
        }
    } catch (err) {
        console.error(err);
        label.innerText = 'โหลดเพิ่มเติม';
        btn.disabled = false;
    } finally {
        historyLoadingMore = false;
    }
}

loadData();
</script>
</body>
</html>