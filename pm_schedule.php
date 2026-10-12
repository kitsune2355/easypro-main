<?php
//pm_schedule.php — แท็บตารางแผน PM: ดูแผน PM ที่บันทึกแล้ว + กำหนดการ (อ่านอย่างเดียว) ถูก include จาก pm.php (ผู้ใช้ทุกคนที่เข้าเมนูวางแผน PM ได้)
// มุมมอง: รายปี (เดือนละ 4 ช่อง แบบ Template Excel) / รายเดือน (ช่องละ 1 วัน) / รายสัปดาห์ (จ.–อา. ช่องละ 1 วัน)
// ค้นหา/กรอง/แบ่งหน้าที่ server (handle_pm_schedule.php?action=get_grid) — หน่วยงานใหญ่ไม่ต้องโหลดทุกแผนพร้อมกัน
@session_start();
include_once "config_ctrl/checksession.php";
?>
<style>
    /* ช่องกำหนดการ: จุด/ตัวเลขเล็ก ๆ แทนสีพื้นเต็มช่อง (ลดความลายตา)
       วงกลมโปร่ง = รอดำเนินการ / จุดเขียว = ทำแล้ว / จุดแดง = เลยกำหนด / ตัวเลข = มีหลายครั้งในช่องนั้น */
    #sch-hot td { vertical-align: middle; }
    #sch-hot td.sch-wk { padding: 0; text-align: center; }
    #sch-hot td.sch-weekend { background-color: #f8fafc !important; }
    #sch-hot td.sch-month-end { border-right: 1px solid #cbd5e1 !important; }
    #sch-hot td.sch-now { background-color: #fff5f6 !important; box-shadow: inset 1px 0 0 #fda4af, inset -1px 0 0 #fda4af; }
    #sch-hot td.sch-grp { background-color: #f1f5f9 !important; }
    #sch-hot td.sch-pc { font-size: 12.5px; color: #475569; padding: 0 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    #sch-hot td.sch-mc { padding: 0 10px; }
    .sch-l1 { font-size: 13px; font-weight: 500; color: #1e293b; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sch-l2 { font-size: 11.5px; color: #64748b; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sch-g { display: flex; align-items: center; gap: .375rem; min-width: 0; padding: 0 2px; }
    .sch-g-t { flex: 1 1 auto; min-width: 0; font-size: 12.5px; font-weight: 600; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sch-g-n, .sch-g-od { flex: none; font-size: 11px; font-weight: 600; line-height: 1; padding: 3px 6px; border-radius: 9999px; }
    .sch-g-n { background: #e2e8f0; color: #475569; }
    .sch-g-od { background: #fee2e2; color: #dc2626; }
    .sch-dot { display: inline-block; width: 8px; height: 8px; border-radius: 9999px; vertical-align: middle; }
    .sch-dot-p { box-shadow: inset 0 0 0 1.5px #0ea5e9; }
    .sch-dot-d { background: #10b981; }
    .sch-dot-o { background: #ef4444; }
    .sch-mk { display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; padding: 0 4px; border-radius: 9999px; font-size: 10.5px; font-weight: 600; line-height: 1; vertical-align: middle; }
    .sch-mk-p { background: #e0f2fe; color: #0369a1; }
    .sch-mk-d { background: #d1fae5; color: #047857; }
    .sch-mk-o { background: #fee2e2; color: #dc2626; }
    #sch-hot th .sch-h-day { display: block; line-height: 1.15; font-weight: 600; }
    #sch-hot th .sch-h-dow { display: block; line-height: 1.1; font-size: 10px; font-weight: 500; opacity: .75; }
    .sch-legend { display: inline-flex; align-items: center; gap: .375rem; font-size: 12px; color: #64748b; white-space: nowrap; }
    .sch-legend-now { display: inline-block; width: 12px; height: 14px; background: #fff5f6; box-shadow: inset 1px 0 0 #fda4af, inset -1px 0 0 #fda4af; }
    .sch-view-btn { padding: .3rem .7rem; border-radius: .5rem; font-size: 13px; font-weight: 500; color: #475569; white-space: nowrap; }
    .sch-view-btn:hover { color: #0f172a; }
    .sch-view-btn.is-active { background: #fff; color: #006B9F; font-weight: 600; box-shadow: 0 1px 2px rgba(15, 23, 42, .12); }
    .sch-input { background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem; padding: .4rem .6rem; font-size: 13px; color: #334155; outline: none; }
    .sch-input:focus { border-color: #38bdf8; box-shadow: 0 0 0 3px rgba(14, 165, 233, .15); }
    .sch-qbtn { display: inline-flex; align-items: center; gap: .375rem; padding: .4rem .6rem; border-radius: .5rem; border: 1px solid #e2e8f0; background: #fff; font-size: 13px; color: #475569; white-space: nowrap; }
    .sch-qbtn:hover { background: #f8fafc; color: #0f172a; }
    .sch-qbtn:disabled { opacity: .45; cursor: default; background: #fff; }

    /* มือถือ/แท็บเล็ต: เลื่อนทั้งหน้า ตารางสูงตามจอ */
    #sch-hot { height: max(360px, calc(100vh - 360px)); }
    /* จอคอม (lg ขึ้นไป): การ์ดสูงพอดีพื้นที่ของแท็บ ตารางกินพื้นที่ที่เหลือในการ์ด */
    @media (min-width: 1024px) {
        #tab-schedule:not(.hidden) { height: 100%; display: flex; flex-direction: column; }
        #sch-card { flex: 1 1 auto; min-height: 480px; display: flex; flex-direction: column; }
        #sch-grid-wrap { flex: 1 1 0; min-height: 0; }
        #sch-hot { height: 100%; }
    }
    /* เต็มจอ: การ์ดเต็มหน้าจอจริง (Fullscreen API) หรือเต็มหน้าเว็บ (เครื่องที่ไม่รองรับ เช่น iPhone) */
    #sch-card.sch-fs { position: fixed; inset: 0; z-index: 1000; border-radius: 0; overflow: hidden; display: flex; flex-direction: column; }
    #sch-card.sch-fs #sch-grid-wrap { flex: 1 1 0; min-height: 0; }
    #sch-card.sch-fs #sch-hot { height: 100%; }
</style>
<div id="tab-schedule" class="tab-content hidden">
    <div id="sch-card" class="bg-white p-4 sm:p-5 rounded-xl shadow-sm border border-slate-200">
        <!-- หัวเรื่อง -->
        <div class="flex items-start gap-3">
            <div class="min-w-0 flex-1">
                <h3 class="text-lg font-semibold text-slate-800 leading-tight">ตารางแผน PM</h3>
                <p id="sch-subtitle" class="text-xs text-slate-500 mt-0.5">แผน PM ที่บันทึกแล้ว และกำหนดการของช่วงเวลาที่เลือก (ดูอย่างเดียว)</p>
            </div>
            <div class="flex items-center gap-1.5 flex-none">
                <button id="sch-help-btn" type="button" title="คำอธิบาย" aria-expanded="false" class="p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <i data-lucide="circle-help" class="w-4 h-4"></i>
                </button>
                <button id="sch-export-btn" type="button" title="Export Excel (ตามตัวกรองที่เลือก)" class="sch-qbtn">
                    <i data-lucide="file-down" class="w-4 h-4 text-emerald-600"></i> <span class="hidden sm:inline">Export Excel</span>
                </button>
                <button id="sch-fs-btn" type="button" title="ขยายเต็มจอ" class="sch-qbtn">
                    <i data-lucide="maximize" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- มุมมอง + ช่วงเวลา -->
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <div id="sch-view" role="tablist" aria-label="มุมมอง" class="flex items-center gap-0.5 p-0.5 rounded-lg bg-slate-100">
                <button type="button" class="sch-view-btn is-active" data-view="year" role="tab" aria-selected="true">รายปี</button>
                <button type="button" class="sch-view-btn" data-view="month" role="tab">รายเดือน</button>
                <button type="button" class="sch-view-btn" data-view="week" role="tab">รายสัปดาห์</button>
            </div>
            <div class="flex items-center gap-1">
                <select id="sch-year" aria-label="ปี" class="sch-input"></select>
                <div id="sch-period" class="hidden flex items-center gap-1">
                    <button id="sch-prev" type="button" title="ก่อนหน้า" class="sch-qbtn !px-1.5"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                    <span id="sch-period-label" class="min-w-[8.5rem] text-center text-sm font-semibold text-slate-700 whitespace-nowrap"></span>
                    <button id="sch-next" type="button" title="ถัดไป" class="sch-qbtn !px-1.5"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                    <button id="sch-today" type="button" class="sch-qbtn">วันนี้</button>
                </div>
                <button id="sch-reload-btn" type="button" title="โหลดข้อมูลใหม่" class="p-2 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
            </div>
            <!-- คำอธิบายสัญลักษณ์ (มือถืออยู่ในกล่องคำอธิบาย) -->
            <div class="hidden md:flex flex-wrap items-center gap-x-3.5 gap-y-1 ml-auto">
                <span class="sch-legend"><span class="sch-dot sch-dot-p"></span>รอดำเนินการ</span>
                <span class="sch-legend"><span class="sch-dot sch-dot-d"></span>ทำแล้ว</span>
                <span class="sch-legend"><span class="sch-dot sch-dot-o"></span>เลยกำหนด</span>
                <span class="sch-legend"><span class="sch-mk sch-mk-p">3</span>จำนวนครั้ง</span>
                <span class="sch-legend"><span class="sch-legend-now"></span><span class="sch-now-label">สัปดาห์นี้</span></span>
            </div>
        </div>

        <!-- ค้นหา + ตัวกรอง -->
        <div class="mt-2.5 flex flex-wrap items-center gap-2">
            <div class="relative w-full sm:w-64">
                <i data-lucide="search" class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                <input id="sch-q" type="search" autocomplete="off" placeholder="ค้นหาเครื่อง / รหัส / เช็คชีต / ที่ตั้ง" class="sch-input w-full !pl-8">
            </div>
            <div class="flex w-full sm:w-auto gap-2">
                <select id="sch-cs" aria-label="เช็คชีต" class="sch-input flex-1 min-w-0 sm:flex-none sm:w-56"><option value="">ทุกเช็คชีต</option></select>
                <select id="sch-st" aria-label="สถานะ" class="sch-input flex-1 min-w-0 sm:flex-none sm:w-48"></select>
            </div>
            <div class="relative">
                <button id="sch-cols-btn" type="button" aria-expanded="false" title="แสดงคอลัมน์" class="sch-qbtn"><i data-lucide="columns-3" class="w-4 h-4"></i><span class="hidden sm:inline">คอลัมน์</span></button>
                <div id="sch-cols" class="hidden absolute left-0 top-full mt-1 z-[300] w-56 bg-white border border-slate-200 rounded-xl shadow-lg p-2"></div>
            </div>
            <span id="sch-count" class="text-xs text-slate-500 whitespace-nowrap"></span>
            <button id="sch-od-chip" type="button" class="hidden text-xs font-medium text-red-600 bg-red-50 border border-red-200 hover:bg-red-100 rounded-full px-2.5 py-1 whitespace-nowrap"></button>
        </div>

        <div id="sch-help" class="hidden mt-2.5 rounded-lg bg-slate-50 border border-slate-200 p-3 text-xs text-slate-600">
            <div class="md:hidden flex flex-wrap gap-x-4 gap-y-1 mb-2.5">
                <span class="sch-legend"><span class="sch-dot sch-dot-p"></span>รอดำเนินการ</span>
                <span class="sch-legend"><span class="sch-dot sch-dot-d"></span>ทำแล้ว</span>
                <span class="sch-legend"><span class="sch-dot sch-dot-o"></span>เลยกำหนด</span>
                <span class="sch-legend"><span class="sch-mk sch-mk-p">3</span>จำนวนครั้ง</span>
                <span class="sch-legend"><span class="sch-legend-now"></span><span class="sch-now-label">สัปดาห์นี้</span></span>
            </div>
            <ul class="list-disc pl-4 space-y-1">
                <li><b>รายปี</b> — เดือนละ 4 ช่อง (วันที่ 1-7, 8-14, 15-21, 22 ถึงสิ้นเดือน) ตัวเลขในช่อง = จำนวนครั้งในช่องนั้น สีบอกสถานะ (แดง = มีงานเลยกำหนด) ชี้ที่ช่องเพื่อดูรายละเอียด</li>
                <li><b>รายเดือน / รายสัปดาห์</b> — ช่องละ 1 วัน ช่องสีเทาอ่อน = เสาร์-อาทิตย์ ใช้ ‹ › เพื่อเลื่อนช่วงเวลา หรือกด <b>วันนี้</b></li>
                <li>แถวสีเทา = หัวกลุ่มเช็คชีต (ตัวเลข = จำนวนแผนในกลุ่ม / สีแดง = แผนที่มีงานเลยกำหนด) — กรองสถานะนับตามช่วงเวลาที่ดูอยู่</li>
                <li>คอลัมน์ แจ้งเตือนล่วงหน้า / ระบุวัน / เริ่มแผน / ที่ตั้ง เปิดได้ที่ปุ่ม <b>คอลัมน์</b></li>
                <li>ตารางนี้ใช้ดูอย่างเดียว — แก้ไขแผนได้ที่แท็บ <b>จัดการแผน PM</b> · Export Excel ส่งออกทุกแผนตามมุมมองและตัวกรองที่เลือก (ไม่ใช่แค่หน้านี้)</li>
            </ul>
        </div>

        <div id="sch-grid-wrap" class="relative mt-3">
            <div id="sch-hot" class="w-full overflow-hidden"></div>
            <div id="sch-empty" class="hidden py-16 text-center text-sm text-slate-400"></div>
            <div id="sch-error" class="hidden py-12 text-center text-sm text-red-600">
                <div id="sch-error-msg"></div>
                <button id="sch-retry" type="button" class="sch-qbtn mt-3 mx-auto"><i data-lucide="refresh-cw" class="w-4 h-4"></i>ลองใหม่</button>
            </div>
            <div id="sch-loading" class="absolute inset-0 z-20 flex items-center justify-center bg-white/70 text-sm text-slate-500">
                <span class="inline-flex items-center gap-2"><i data-lucide="loader-circle" class="w-4 h-4 animate-spin"></i>กำลังโหลด...</span>
            </div>
        </div>

        <!-- แบ่งหน้า -->
        <div id="sch-pager" class="hidden mt-2.5 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
            <span id="sch-range" class="whitespace-nowrap"></span>
            <div class="flex items-center gap-1.5">
                <select id="sch-size" aria-label="จำนวนต่อหน้า" class="sch-input !py-1 !text-xs">
                    <option value="50">50 / หน้า</option><option value="100">100 / หน้า</option><option value="200">200 / หน้า</option>
                </select>
                <button id="sch-pg-prev" type="button" title="หน้าก่อนหน้า" class="sch-qbtn !px-1.5 !py-1"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                <span id="sch-pg-info" class="min-w-[4.5rem] text-center font-medium text-slate-600 whitespace-nowrap"></span>
                <button id="sch-pg-next" type="button" title="หน้าถัดไป" class="sch-qbtn !px-1.5 !py-1"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
            </div>
        </div>
    </div>
</div>

<script>
pmLazy('schedule', function() {   // โหลดเมื่อเปิดแท็บครั้งแรก (ดู pmLazy ใน pm.php)
    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const TH_MONTHS = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    const TH_MON = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    const TH_DOW = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    // คอลัมน์ท้ายตาราง (เลือกแสดงได้ที่ปุ่ม "คอลัมน์") w = [จอคอม, มือถือ]
    const PLAN_COLS = [
        { key: 'f', head: 'ความถี่', w: [150, 120], on: true },
        { key: 'nx', head: 'รอบถัดไป', w: [96, 88], on: true, center: true },
        { key: 'a', head: 'แจ้งเตือนล่วงหน้า', w: [150, 120] },
        { key: 'dy', head: 'ระบุวัน', w: [110, 90] },
        { key: 'sd', head: 'เริ่มแผน', w: [96, 88], center: true },
        { key: 'loc', head: 'ที่ตั้ง', w: [190, 140] }
    ];
    // ตัวกรองสถานะ (นับตามช่วงเวลาที่ดู) — ค่าเดียวกับ st ใน handle_pm_schedule.php
    const STATUS = [['', 'ทุกสถานะ', 'all'], ['overdue', 'มีงานเลยกำหนด'], ['pending', 'มีงานรอดำเนินการ'], ['done', 'ทำครบแล้ว'], ['has', 'มีงานในช่วงนี้'], ['none', 'ไม่มีงานในช่วงนี้']];
    const COLS_KEY = 'pmSchCols', SIZE_KEY = 'pmSchSize';

    const $ = id => document.getElementById(id);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const isNarrow = () => window.innerWidth < 640;
    const pad = n => String(n).padStart(2, '0');
    const ymd = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const parseYmd = s => { const [y, m, d] = String(s).split('-').map(Number); return new Date(y, m - 1, d); };
    const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
    const mondayOf = d => addDays(d, -((d.getDay() + 6) % 7));
    const thDate = d => `${d.getDate()} ${TH_MON[d.getMonth()]} ${d.getFullYear() + 543}`;
    const thShort = s => { if (!/^\d{4}-\d{2}-\d{2}$/.test(s || '')) return s || ''; const d = parseYmd(s); return `${d.getDate()} ${TH_MON[d.getMonth()]} ${String(d.getFullYear() + 543).slice(2)}`; };
    const lsGet = (k, def) => { try { const v = JSON.parse(localStorage.getItem(k)); return v ?? def; } catch (e) { return def; } };
    const lsSet = (k, v) => { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* โหมดส่วนตัว: ไม่จำค่า */ } };

    // เต็มจอทั้งเอกสาร => หน้าต่างแจ้งเตือน (z-index 1060) แสดงเหนือการ์ดเต็มจอ (1000) ได้ตามปกติ
    const fsElement = () => document.fullscreenElement || document.webkitFullscreenElement || null;
    const alertBox = opts => Swal.fire(opts);
    const loadingBox = (title, html = '') => { alertBox({ title, html, allowOutsideClick: false, showConfirmButton: false }); Swal.showLoading(); };

    // ---------- สถานะ ----------
    let opts = null;           // ตัวเลือกจาก get_grid&opts=1: freqs, alerts, contract, agency, checksheets
    let res = null;            // ผลล่าสุดของ get_grid (หน้าปัจจุบัน)
    let hot = null;
    let loading = false;
    let seq = 0;               // กันผลเก่าทับผลใหม่ (พิมพ์ค้นหาเร็ว ๆ)
    let narrowLayout = null;
    let view = 'year';         // year | month | week
    let cursor = null;         // วันแรกของเดือน (รายเดือน) / วันจันทร์ (รายสัปดาห์)
    let q = '', st = '', offset = 0;
    let limit = [50, 100, 200].includes(+lsGet(SIZE_KEY, 50)) ? +lsGet(SIZE_KEY, 50) : 50;
    let colsOn = lsGet(COLS_KEY, null);
    if (!Array.isArray(colsOn)) colsOn = PLAN_COLS.filter(c => c.on).map(c => c.key);
    let periods = [];          // คอลัมน์กำหนดการของมุมมองปัจจุบัน
    let rowMeta = [];          // ต่อแถวในตาราง: {g, n, od} = หัวกลุ่มเช็คชีต / {r, cells} = แผน

    $('sch-size').value = String(limit);

    // ช่องสัปดาห์ของวันในมุมมองรายปี (เหมือน bucket ใน handle_pm_schedule.php)
    function weekIndexOf(s) {
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s || '');
        return m ? (+m[2] - 1) * 4 + Math.min(4, Math.ceil(+m[3] / 7)) - 1 : -1;
    }
    // "ช่อง:จำนวน:ทำแล้ว:เลยกำหนด,..." => {ช่อง: [n, done, overdue]}
    function parseCells(c) {
        const out = {};
        if (c) c.split(',').forEach(s => { const [k, n, d, o] = s.split(':').map(Number); out[k] = [n, d, o]; });
        return out;
    }
    const cellStatus = w => (w[2] ? 'o' : (w[1] === w[0] ? 'd' : 'p'));
    const freqText = v => (opts && opts.freqs[v]) || '';
    const alertText = v => (opts && opts.alerts[v]) || '';
    const planVal = (r, key) => ({ f: freqText(r.f), a: alertText(r.a), nx: r.nx || '', sd: r.sd || '', dy: r.dy || '', loc: r.loc || '' })[key];

    // ช่วงวันที่ของมุมมองรายเดือน/รายสัปดาห์
    function periodRange() {
        if (view === 'month') return [cursor, new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0)];
        return [cursor, addDays(cursor, 6)];
    }

    // ---------- โหลดข้อมูล ----------
    function params(extra) {
        const p = { action: 'get_grid', offset, limit };
        if (q) p.q = q;
        if (st) p.st = st;
        if ($('sch-cs').value) p.cs = $('sch-cs').value;
        if (view === 'year') { p.mode = 'year'; p.year = $('sch-year').value || new Date().getFullYear(); }
        else { const [f, t] = periodRange(); p.mode = 'day'; p.from = ymd(f); p.to = ymd(t); }
        if (!opts) p.opts = 1;
        return Object.assign(p, extra || {});
    }

    function setLoading(on) {
        loading = on;
        $('sch-loading').classList.toggle('hidden', !on);
    }

    async function load() {
        const my = ++seq;
        setLoading(true);
        $('sch-error').classList.add('hidden');
        try {
            const r = await axios.get('handle_pm_schedule.php', { params: params() });
            if (my !== seq) return;
            if (!r.data.success) throw new Error(r.data.error);
            const d = r.data.data;
            if (d.opts && applyOpts(d.opts)) return load();   // ปีนี้อยู่นอกช่วงสัญญา => เปลี่ยนปีแล้วโหลดใหม่
            if (d.total && d.offset >= d.total) { offset = 0; return load(); }
            res = d;
            renderTable();
        } catch (e) {
            if (my !== seq) return;
            if (hot) { hot.destroy(); hot = null; }
            $('sch-hot').classList.add('hidden');
            $('sch-empty').classList.add('hidden');
            $('sch-pager').classList.add('hidden');
            $('sch-error-msg').textContent = 'โหลดแผน PM ไม่สำเร็จ: ' + (e.response ? e.response.status + ' ' : '') + e.message;
            $('sch-error').classList.remove('hidden');
        } finally {
            if (my === seq) setLoading(false);
        }
    }

    // ตัวเลือกปี (ตามช่วงสัญญา) + เช็คชีต — คืนค่า true ถ้าต้องเปลี่ยนปีที่เลือก
    function applyOpts(o) {
        opts = o;
        const nowY = new Date().getFullYear();
        const y1 = parseInt((o.contract.start || '').slice(0, 4), 10) || nowY - 1;
        const y2 = parseInt((o.contract.end || '').slice(0, 4), 10) || nowY + 1;
        const years = [];
        for (let y = y1; y <= y2; y++) years.push(y);
        const cur = +$('sch-year').value || nowY;
        $('sch-year').innerHTML = years.map(y => `<option value="${y}">พ.ศ. ${y + 543}</option>`).join('');
        $('sch-year').value = years.includes(cur) ? cur : years[0];
        $('sch-cs').innerHTML = '<option value="">ทุกเช็คชีต</option>' +
            o.checksheets.map(([name, n]) => `<option value="=${esc(name)}">${esc(name || 'ไม่ระบุเช็คชีต')} (${n.toLocaleString()})</option>`).join('');
        return view === 'year' && !years.includes(cur);
    }

    // ---------- คอลัมน์กำหนดการตามมุมมอง ----------
    // {key, k (ช่อง), group, label (HTML หัวคอลัมน์), text (ข้อความใน Excel), cls, weekend, now}
    function buildPeriods() {
        const today = res.today;
        if (res.mode === 'year') {
            const year = +res.from.slice(0, 4);
            const nowIdx = String(year) === today.slice(0, 4) ? weekIndexOf(today) : -1;
            return Array.from({ length: 48 }, (_, k) => ({
                key: 'p' + k, k, group: `${MONTHS[Math.floor(k / 4)]} ${year}`, label: String(k + 1), text: k + 1,
                cls: k % 4 === 3 ? ' sch-month-end' : '', now: k === nowIdx
            }));
        }
        const from = parseYmd(res.from), to = parseYmd(res.to), month = to - from > 7 * 864e5;
        const out = [];
        for (let d = from, k = 0; d <= to; d = addDays(d, 1), k++) {
            const dow = d.getDay(), weekend = dow === 0 || dow === 6;
            out.push({
                key: 'p' + k, k, group: `${TH_MONTHS[d.getMonth()]} ${d.getFullYear() + 543}`,
                label: month
                    ? `<span class="sch-h-day">${d.getDate()}</span><span class="sch-h-dow">${TH_DOW[dow]}</span>`
                    : `<span class="sch-h-day">${TH_DOW[dow]} ${d.getDate()}</span>`,
                text: month ? `${d.getDate()} ${TH_DOW[dow]}` : `${TH_DOW[dow]} ${d.getDate()} ${TH_MON[d.getMonth()]}`,
                cls: (weekend ? ' sch-weekend' : '') + (month && dow === 0 ? ' sch-month-end' : ''),
                weekend, now: ymd(d) === today
            });
        }
        return out;
    }

    function updateHeaderText() {
        const sub = { year: `กำหนดการรายปี พ.ศ. ${(+$('sch-year').value || new Date().getFullYear()) + 543} (เดือนละ 4 ช่อง) จัดกลุ่มตามเช็คชีต`,
                      month: 'กำหนดการรายเดือน — ช่องละ 1 วัน จัดกลุ่มตามเช็คชีต', week: 'กำหนดการรายสัปดาห์ (จันทร์–อาทิตย์) — ช่องละ 1 วัน' }[view];
        $('sch-subtitle').textContent = sub + ' · ดูอย่างเดียว';
        document.querySelectorAll('.sch-now-label').forEach(el => el.textContent = view === 'year' ? 'สัปดาห์นี้' : 'วันนี้');
        if (view !== 'year') {
            const [from, to] = periodRange();
            $('sch-period-label').textContent = view === 'month'
                ? `${TH_MONTHS[from.getMonth()]} ${from.getFullYear() + 543}`
                : (from.getMonth() === to.getMonth() ? `${from.getDate()}–${to.getDate()} ${TH_MON[to.getMonth()]} ${to.getFullYear() + 543}` : `${thDate(from)} – ${thDate(to)}`);
        }
    }

    // ---------- แถบเครื่องมือ: ตัวเลขสถานะ / จำนวนผลลัพธ์ / แบ่งหน้า ----------
    function updateToolbar() {
        const c = res.counts;
        $('sch-st').innerHTML = STATUS.map(([v, label, key]) => `<option value="${v}">${label} (${(c[key || v] || 0).toLocaleString()})</option>`).join('');
        $('sch-st').value = st;
        $('sch-count').textContent = `${res.total.toLocaleString()} แผน`;
        const chip = $('sch-od-chip');
        chip.textContent = `เลยกำหนด ${c.overdue.toLocaleString()} แผน`;
        chip.title = 'แสดงเฉพาะแผนที่มีงานเลยกำหนดในช่วงนี้';
        chip.classList.toggle('hidden', !c.overdue || st === 'overdue');

        const pages = Math.max(1, Math.ceil(res.total / limit)), page = Math.floor(res.offset / limit) + 1;
        $('sch-pager').classList.toggle('hidden', !res.total);
        $('sch-range').textContent = res.total ? `แสดง ${(res.offset + 1).toLocaleString()}–${Math.min(res.offset + limit, res.total).toLocaleString()} จาก ${res.total.toLocaleString()} แผน` : '';
        $('sch-pg-info').textContent = `หน้า ${page} / ${pages}`;
        $('sch-pg-prev').disabled = page <= 1;
        $('sch-pg-next').disabled = page >= pages;
    }

    // ---------- ตาราง ----------
    function nameRenderer(instance, td, row) {
        const m = rowMeta[row];
        if (m.r) {
            td.className = 'sch-mc';
            td.title = [m.r.mc, m.r.code, m.r.loc, m.r.cs].filter(Boolean).join(' · ');
            td.innerHTML = `<div class="sch-l1">${esc(m.r.mc || m.r.code || '-')}</div><div class="sch-l2">${esc([m.r.code, m.r.loc].filter(Boolean).join(' · ') || '-')}</div>`;
        } else {
            td.className = 'sch-grp';
            td.title = `${m.g || 'ไม่ระบุเช็คชีต'} — ${m.n.toLocaleString()} แผน` + (m.od ? ` (มีงานเลยกำหนด ${m.od.toLocaleString()} แผน)` : '');
            td.innerHTML = `<div class="sch-g"><span class="sch-g-t">${esc(m.g || 'ไม่ระบุเช็คชีต')}</span><span class="sch-g-n">${m.n.toLocaleString()}</span>`
                + (m.od ? `<span class="sch-g-od">${m.od.toLocaleString()}</span>` : '') + '</div>';
        }
        return td;
    }

    function cellRenderer(instance, td, row, col) {
        const m = rowMeta[row];
        const p = periods[col - 1];
        let cls = 'sch-wk' + p.cls + (p.now ? ' sch-now' : '');
        td.title = '';
        td.innerHTML = '';
        if (!m.r) { td.className = cls + ' sch-grp'; return td; }
        td.className = cls;
        const w = m.cells[p.k];
        if (w) {
            const s = cellStatus(w);
            td.innerHTML = w[0] > 1 ? `<span class="sch-mk sch-mk-${s}">${w[0]}</span>` : `<span class="sch-dot sch-dot-${s}"></span>`;
            td.title = `${w[0]} ครั้ง` + (w[1] ? ` · ทำแล้ว ${w[1]}` : '') + (w[2] ? ` · เลยกำหนด ${w[2]}` : '') + (w[0] - w[1] - w[2] > 0 ? ` · รอดำเนินการ ${w[0] - w[1] - w[2]}` : '');
        }
        return td;
    }

    function planRenderer(instance, td, row, col, prop, value) {
        const m = rowMeta[row];
        const c = PLAN_COLS.find(x => x.key === prop);
        td.className = 'sch-pc' + (c && c.center ? ' htCenter' : '') + (m.r ? '' : ' sch-grp');
        td.textContent = m.r ? (prop === 'nx' || prop === 'sd' ? thShort(value) : (value || '')) : '';
        td.title = m.r && value ? String(value) : '';
        return td;
    }

    function renderTable() {
        periods = buildPeriods();
        updateHeaderText();
        updateToolbar();

        const data = [];
        rowMeta = [];
        let prev = null;
        res.rows.forEach(r => {
            if (r.cs !== prev) {   // หัวกลุ่มเช็คชีต (เรียงตามชื่อเช็คชีตจาก server)
                prev = r.cs;
                const g = res.groups[r.cs] || [0, 0];
                rowMeta.push({ g: r.cs, n: g[0], od: g[1] });
                data.push({ m: r.cs || 'ไม่ระบุเช็คชีต' });
            }
            const cells = parseCells(r.c);
            const row = { m: `${r.mc} ${r.code}` };
            periods.forEach(p => { const w = cells[p.k]; row[p.key] = w ? w[0] : ''; });
            PLAN_COLS.forEach(c => row[c.key] = planVal(r, c.key));
            rowMeta.push({ r, cells });
            data.push(row);
        });

        const empty = !res.rows.length;
        const filtered = q || st || $('sch-cs').value;
        $('sch-empty').textContent = filtered ? 'ไม่พบแผน PM ตามเงื่อนไขที่เลือก' : (res.counts.all ? 'ไม่มีงาน PM ในช่วงเวลานี้' : 'ยังไม่มีแผน PM ในหน่วยงานนี้');
        $('sch-empty').classList.toggle('hidden', !empty);
        $('sch-hot').classList.toggle('hidden', empty);
        if (hot) { hot.destroy(); hot = null; }
        if (empty) return;

        narrowLayout = isNarrow();
        const ni = narrowLayout ? 1 : 0;
        const plan = PLAN_COLS.filter(c => colsOn.includes(c.key));
        const nP = periods.length;
        // ความกว้างช่องกำหนดการ: รายปี 34 / รายเดือน 36 / รายสัปดาห์ 92 (จอแคบเล็กลง)
        const pw = { year: [34, 32], month: [36, 32], week: [92, 64] }[view][ni];
        const colWidths = [narrowLayout ? 150 : 250, ...Array(nP).fill(pw), ...plan.map(c => c.w[ni])];

        // หัวคอลัมน์ 2 แถว: แถวบนรวมช่องที่อยู่กลุ่มเดียวกัน (เดือน)
        const groups = [];
        periods.forEach(p => {
            const g = groups[groups.length - 1];
            if (g && g.label === p.group) g.colspan++;
            else groups.push({ label: p.group, colspan: 1 });
        });
        const top = ['เครื่องจักร / เช็คชีต', ...groups.map(g => g.colspan > 1 ? g : g.label)];
        if (plan.length) top.push(plan.length > 1 ? { label: 'แผน PM', colspan: plan.length } : 'แผน PM');
        const nowCol = periods.findIndex(p => p.now);

        hot = new Handsontable($('sch-hot'), {
            data,
            colorScheme: 'light',
            readOnly: true,                // ดูอย่างเดียว — แก้แผนที่แท็บจัดการแผน PM
            nestedHeaders: [top, ['', ...periods.map(p => p.label), ...plan.map(c => c.head)]],
            columns: [
                { data: 'm', renderer: nameRenderer },
                ...periods.map(p => ({ data: p.key, renderer: cellRenderer })),
                ...plan.map(c => ({ data: c.key, renderer: planRenderer }))
            ],
            rowHeaders: false,
            rowHeights: row => (rowMeta[row] && rowMeta[row].r ? 44 : 30),
            autoRowSize: false,
            width: '100%',
            height: '100%',
            stretchH: 'none',
            colWidths,
            // ช่องกว้างคงที่: กันพื้นที่เลื่อนไม่ตรงกับที่วาดจริง
            modifyColWidth: (width, col) => colWidths[col] ?? width,
            fixedColumnsStart: 1,
            fillHandle: false,
            contextMenu: ['copy'],
            licenseKey: 'non-commercial-and-evaluation',
            afterGetColHeader: function(col, TH) {
                const p = col >= 1 && col <= nP ? periods[col - 1] : null;
                const isPlan = col > nP;
                TH.style.backgroundColor = p && p.now ? '#ffe4e6' : (p && p.weekend ? '#eef2f6' : '#f8fafc');
                TH.style.color = p && p.now ? '#be123c' : (isPlan ? '#475569' : '#334155');
                if (p) TH.style.fontSize = narrowLayout ? '11px' : '12px';
            }
        });
        // เลื่อนให้เห็นช่องปัจจุบัน (เว้นไว้ 2 ช่องก่อนหน้า) — รายสัปดาห์มีแค่ 7 วัน ไม่ต้องเลื่อน
        if (view !== 'week' && nowCol > 2) setTimeout(() => {
            const holder = $('sch-hot').querySelector('.ht_master .wtHolder');
            if (!holder) return;
            let x = 0;
            for (let c = 1; c < 1 + nowCol - 2; c++) x += colWidths[c];
            holder.scrollLeft = x;
        }, 80);
        lucide.createIcons();
    }

    // ---------- เลือกคอลัมน์ ----------
    function renderColsMenu() {
        $('sch-cols').innerHTML = '<div class="px-2 pt-1 pb-1.5 text-[11px] font-semibold text-slate-400">แสดงคอลัมน์</div>' + PLAN_COLS.map(c =>
            `<label class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 text-sm text-slate-700 cursor-pointer select-none">
                <input type="checkbox" data-col="${c.key}" ${colsOn.includes(c.key) ? 'checked' : ''} class="w-4 h-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">${c.head}</label>`).join('');
    }
    renderColsMenu();
    $('sch-cols-btn').addEventListener('click', e => {
        e.stopPropagation();
        const open = $('sch-cols').classList.toggle('hidden') === false;
        $('sch-cols-btn').setAttribute('aria-expanded', String(open));
    });
    $('sch-cols').addEventListener('click', e => e.stopPropagation());
    document.addEventListener('click', () => { $('sch-cols').classList.add('hidden'); $('sch-cols-btn').setAttribute('aria-expanded', 'false'); });
    $('sch-cols').addEventListener('change', e => {
        const key = e.target.dataset.col;
        if (!key) return;
        colsOn = e.target.checked ? [...new Set([...colsOn, key])] : colsOn.filter(k => k !== key);
        lsSet(COLS_KEY, colsOn);
        if (res) renderTable();   // ไม่ต้องโหลดใหม่ ข้อมูลมีครบแล้ว
    });

    // ---------- ค้นหา / กรอง / แบ่งหน้า ----------
    const reload = () => { offset = 0; load(); };
    let qTimer = null;
    $('sch-q').addEventListener('input', () => {
        clearTimeout(qTimer);
        qTimer = setTimeout(() => {
            const v = $('sch-q').value.trim();
            if (v === q) return;
            q = v;
            reload();
        }, 300);
    });
    $('sch-cs').addEventListener('change', reload);
    $('sch-st').addEventListener('change', () => { st = $('sch-st').value; reload(); });
    $('sch-od-chip').addEventListener('click', () => { st = 'overdue'; reload(); });
    $('sch-size').addEventListener('change', () => { limit = +$('sch-size').value; lsSet(SIZE_KEY, limit); reload(); });
    $('sch-pg-prev').addEventListener('click', () => { offset = Math.max(0, offset - limit); load(); });
    $('sch-pg-next').addEventListener('click', () => { if (res && offset + limit < res.total) { offset += limit; load(); } });
    $('sch-retry').addEventListener('click', load);

    // ---------- เปลี่ยนมุมมอง / เลื่อนช่วงเวลา ----------
    function setView(v) {
        if (v === view) return;
        if (v !== 'year') {
            // เริ่มที่วันนี้ ถ้าปีที่เลือกอยู่คือปีนี้ ไม่งั้นเริ่มต้นปีที่เลือก
            const base = cursor || (String($('sch-year').value) === String(new Date().getFullYear()) ? new Date() : new Date(+$('sch-year').value, 0, 1));
            cursor = v === 'month' ? new Date(base.getFullYear(), base.getMonth(), 1) : mondayOf(base);
            if (view === 'year' && st === '') st = 'has';   // รายเดือน/รายสัปดาห์: เริ่มที่เฉพาะแผนที่มีงานในช่วงนี้
        } else {
            if (cursor) {
                const y = String(cursor.getFullYear());
                if ([...$('sch-year').options].some(o => o.value === y)) $('sch-year').value = y;
            }
            if (st === 'has') st = '';
        }
        view = v;
        document.querySelectorAll('.sch-view-btn').forEach(b => {
            const on = b.dataset.view === v;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-selected', String(on));
        });
        $('sch-year').classList.toggle('hidden', v !== 'year');
        $('sch-period').classList.toggle('hidden', v === 'year');
        updateHeaderText();
        reload();
    }

    function shiftPeriod(dir) {
        cursor = view === 'month' ? new Date(cursor.getFullYear(), cursor.getMonth() + dir, 1) : addDays(cursor, 7 * dir);
        updateHeaderText();
        reload();
    }

    document.querySelectorAll('.sch-view-btn').forEach(b => b.addEventListener('click', () => setView(b.dataset.view)));
    $('sch-prev').addEventListener('click', () => shiftPeriod(-1));
    $('sch-next').addEventListener('click', () => shiftPeriod(1));
    $('sch-today').addEventListener('click', () => {
        const now = new Date();
        cursor = view === 'month' ? new Date(now.getFullYear(), now.getMonth(), 1) : mondayOf(now);
        updateHeaderText();
        reload();
    });
    $('sch-year').addEventListener('change', reload);
    $('sch-reload-btn').addEventListener('click', load);
    $('sch-help-btn').addEventListener('click', () => {
        const open = $('sch-help').classList.toggle('hidden') === false;
        $('sch-help-btn').setAttribute('aria-expanded', String(open));
        if (hot) setTimeout(() => hot.refreshDimensions(), 50);
    });

    // ---------- Export Excel: ทุกแผนตามมุมมองและตัวกรองที่เลือก (Name / CODE / Location + ช่องกำหนดการ + ข้อมูลแผน PM) ----------
    // รายปีเป็นรูปแบบเดียวกับ Template นำเข้า / โหลดทุกแถวตอนกดส่งออกเท่านั้น (limit=0)
    // ใช้ ExcelJS (ใส่สีหัวคอลัมน์/สีสถานะได้) — โหลดไว้แล้วใน pm.php พร้อม FileSaver (saveAs)
    const XL_PLAN = [['f', 'ความถี่'], ['a', 'แจ้งเตือนล่วงหน้า'], ['dy', 'ระบุวัน'], ['nx', 'รอบถัดไป'], ['sd', 'เริ่มแผน']];
    const XL = {
        title:   { font: 'FF0F172A' },
        info:    { font: 'FF64748B' },
        base:    { fill: 'FF006B9F', font: 'FFFFFFFF' },                       // Name / CODE / Location
        groupA:  { fill: 'FFE0F2FE', font: 'FF075985' },                       // เดือน (สลับสี)
        groupB:  { fill: 'FFBAE6FD', font: 'FF075985' },
        weekend: { fill: 'FFE2E8F0', font: 'FF475569' },
        now:     { fill: 'FFFFE4E6', font: 'FFBE123C' },                       // สัปดาห์นี้ / วันนี้
        plan:    { fill: 'FFDCFCE7', font: 'FF166534' },                       // ข้อมูลแผน PM
        border:  'FFCBD5E1',
        cell: { p: ['FFE0F2FE', 'FF0369A1'], d: ['FFDCFCE7', 'FF15803D'], o: ['FFFEE2E2', 'FFDC2626'] }
    };
    const xlFill = argb => ({ type: 'pattern', pattern: 'solid', fgColor: { argb } });
    const xlBorder = () => { const b = { style: 'thin', color: { argb: XL.border } }; return { top: b, left: b, bottom: b, right: b }; };

    $('sch-export-btn').addEventListener('click', async () => {
        if (!res || !res.total) return alertBox({ icon: 'info', title: 'แจ้งเตือน', text: 'ไม่มีแผน PM ให้ส่งออก' });
        if (typeof ExcelJS === 'undefined' || typeof saveAs === 'undefined') {
            return alertBox({ icon: 'error', title: 'ข้อผิดพลาด', text: 'ไม่พบไลบรารี ExcelJS กรุณาโหลดหน้าใหม่' });
        }
        loadingBox('กำลังเตรียมไฟล์ Excel...', `${res.total.toLocaleString()} แผน`);
        let all;
        try {
            const r = await axios.get('handle_pm_schedule.php', { params: params({ offset: 0, limit: 0 }) });
            if (!r.data.success) throw new Error(r.data.error);
            all = r.data.data;
        } catch (e) {
            return alertBox({ icon: 'error', title: 'ข้อผิดพลาด', text: 'โหลดข้อมูลส่งออกไม่สำเร็จ: ' + e.message });
        }

        const nP = periods.length;
        const firstPlan = 4 + nP;                 // คอลัมน์แรกของข้อมูลแผน PM (นับจาก 1)
        const W = 3 + nP + XL_PLAN.length;
        const year = all.from.slice(0, 4);
        const from = parseYmd(all.from), to = parseYmd(all.to);
        const title = view === 'year'
            ? `YEARLY PREVENTIVE MAINTENANCE SCHEDULE ${opts.agency ? opts.agency + ' ' : ''}FOR JANUARY ${year} - DECEMBER ${year}`
            : `ตารางแผน PM ${opts.agency ? opts.agency + ' ' : ''}${view === 'month' ? 'รายเดือน' : 'รายสัปดาห์'} ${thDate(from)} - ${thDate(to)}`;
        const sheetName = view === 'year' ? `PM ${year}` : (view === 'month' ? `PM ${all.from.slice(0, 7)}` : `PM ${all.from}`);
        const fileName = view === 'year' ? `PM_Schedule_${year}.xlsx` : `PM_Schedule_${view === 'month' ? all.from.slice(0, 7) : 'week_' + all.from}.xlsx`;
        const filterNote = [q && `ค้นหา "${q}"`, $('sch-cs').value && `เช็คชีต ${$('sch-cs').value.slice(1) || '-'}`,
                            st && STATUS.find(s => s[0] === st)[1]].filter(Boolean).join(' · ');

        const wb = new ExcelJS.Workbook();
        const ws = wb.addWorksheet(sheetName, { views: [{ state: 'frozen', xSplit: 2, ySplit: 4 }] });
        ws.columns = [{ width: 28 }, { width: 16 }, { width: 28 }, ...Array(nP).fill({ width: { year: 4.5, month: 5, week: 12 }[view] }),
                      { width: 22 }, { width: 22 }, { width: 14 }, { width: 12 }, { width: 12 }];

        // แถว 1-2: ชื่อรายงาน + ข้อมูล ณ วันที่
        ws.mergeCells(1, 1, 1, W);
        ws.mergeCells(2, 1, 2, W);
        Object.assign(ws.getCell(1, 1), { value: title, font: { bold: true, size: 14, color: { argb: XL.title.font } } });
        Object.assign(ws.getCell(2, 1), { value: `ข้อมูล ณ วันที่ ${all.today} · แผน PM ${all.rows.length.toLocaleString()} รายการ${filterNote ? ' · ตัวกรอง: ' + filterNote : ''}`, font: { size: 10, color: { argb: XL.info.font } } });
        ws.getRow(1).height = 22;

        // แถว 3-4: หัวคอลัมน์ (มีสี) — value === undefined: ใส่แค่สี (ช่องล่างของช่องที่รวมกัน)
        const head = (r, c, value, tone) => {
            const cell = ws.getCell(r, c);
            if (value !== undefined) cell.value = value;
            cell.fill = xlFill(tone.fill);
            cell.font = { bold: true, size: 10, color: { argb: tone.font } };
            cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
            cell.border = xlBorder();
        };
        ['Name', 'CODE', 'Location'].forEach((t, i) => { ws.mergeCells(3, i + 1, 4, i + 1); head(3, i + 1, t, XL.base); head(4, i + 1, undefined, XL.base); });
        let g = 0, gStart = 0;
        periods.forEach((p, i) => {
            const groupTone = g % 2 ? XL.groupB : XL.groupA;
            const first = i === 0 || periods[i - 1].group !== p.group;
            if (first) gStart = i;
            head(3, 4 + i, first ? (view === 'year' ? MONTHS[Math.floor(i / 4)] : p.group) : undefined, groupTone);
            head(4, 4 + i, p.text, p.now ? XL.now : (p.weekend ? XL.weekend : groupTone));
            const last = i === nP - 1 || periods[i + 1].group !== p.group;
            if (last) { if (i > gStart) ws.mergeCells(3, 4 + gStart, 3, 4 + i); g++; }
        });
        XL_PLAN.forEach(([, t], i) => { ws.mergeCells(3, firstPlan + i, 4, firstPlan + i); head(3, firstPlan + i, t, XL.plan); head(4, firstPlan + i, undefined, XL.plan); });
        ws.getRow(3).height = 20;
        ws.getRow(4).height = view === 'year' ? 18 : 30;

        // ข้อมูล: สีช่องกำหนดการตามสถานะเหมือนบนหน้าจอ
        all.rows.forEach((r, i) => {
            const row = ws.getRow(5 + i);
            const cells = parseCells(r.c);
            const put = (c, v, wrap) => {
                const cell = row.getCell(c);
                cell.value = v ?? '';
                cell.border = xlBorder();
                cell.font = { size: 10 };
                cell.alignment = { vertical: 'middle', wrapText: wrap };
                return cell;
            };
            put(1, r.cs || '-', true);
            put(2, r.code, true);
            put(3, r.loc || r.mc, true);
            periods.forEach((p, j) => {
                const w = cells[p.k];
                const s = w ? cellStatus(w) : '';
                const cell = put(4 + j, w ? (w[0] > 1 ? w[0] : { p: '●', d: '✓', o: '!' }[s]) : '', false);
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                if (s) { cell.fill = xlFill(XL.cell[s][0]); cell.font = { size: 10, bold: true, color: { argb: XL.cell[s][1] } }; }
                else if (p.weekend) cell.fill = xlFill('FFF8FAFC');
            });
            XL_PLAN.forEach(([k], j) => put(firstPlan + j, planVal(r, k), true));
        });

        // หมายเหตุท้ายตาราง
        const note = 5 + all.rows.length + 1;
        ws.mergeCells(note, 2, note, W);
        Object.assign(ws.getCell(note, 1), { value: 'หมายเหตุ', font: { bold: true, size: 10 } });
        Object.assign(ws.getCell(note, 2), {
            value: (view === 'year'
                ? '● = รอดำเนินการ   ✓ = ทำแล้ว   ! = เลยกำหนด   ตัวเลข = จำนวนครั้งในสัปดาห์นั้น (เดือนละ 4 สัปดาห์: วันที่ 1-7, 8-14, 15-21, 22 ขึ้นไป)'
                : '● = รอดำเนินการ   ✓ = ทำแล้ว   ! = เลยกำหนด   ตัวเลข = จำนวนครั้งในวันนั้น') + ' · สีพื้น: ฟ้า = รอดำเนินการ, เขียว = ทำแล้ว, แดง = มีงานเลยกำหนด',
            font: { size: 10, color: { argb: XL.info.font } }
        });

        const buf = await wb.xlsx.writeBuffer();
        Swal.close();
        saveAs(new Blob([buf], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }), fileName);
    });

    // ---------- เต็มจอ ----------
    // การ์ดคลุมทั้งหน้า (.sch-fs) + ขอเต็มจอให้ "ทั้งเอกสาร" (ทะลุ iframe ของ main.php ที่มี allowfullscreen)
    // เครื่องที่ไม่รองรับ (เช่น Safari บน iPhone) => การ์ดคลุมพื้นที่หน้าเว็บอย่างเดียว
    function setFsButton(on) {
        $('sch-fs-btn').innerHTML = `<i data-lucide="${on ? 'minimize' : 'maximize'}" class="w-4 h-4"></i>`;
        $('sch-fs-btn').title = on ? 'ย่อหน้าจอ' : 'ขยายเต็มจอ';
        lucide.createIcons();
        if (hot) setTimeout(() => hot.refreshDimensions(), 80);
    }

    function setCardFullscreen(on) {
        $('sch-card').classList.toggle('sch-fs', on);
        setFsButton(on);
    }

    $('sch-fs-btn').addEventListener('click', async () => {
        if ($('sch-card').classList.contains('sch-fs')) {
            setCardFullscreen(false);
            if (fsElement()) (document.exitFullscreen || document.webkitExitFullscreen).call(document);
            return;
        }
        setCardFullscreen(true);
        const el = document.documentElement;
        const req = el.requestFullscreen || el.webkitRequestFullscreen;
        if (req && (document.fullscreenEnabled || document.webkitFullscreenEnabled)) {
            try { await req.call(el); } catch (e) { /* ไม่อนุญาต => การ์ดคลุมหน้าเว็บอย่างเดียว */ }
        }
    });

    // กด ESC ออกจากเต็มจอของเบราว์เซอร์ => ย่อการ์ดกลับด้วย
    const onFsChange = () => {
        if (fsElement()) setFsButton(true);
        else if ($('sch-card').classList.contains('sch-fs')) setCardFullscreen(false);
    };
    document.addEventListener('fullscreenchange', onFsChange);
    document.addEventListener('webkitfullscreenchange', onFsChange);

    // หมุนจอ/เปลี่ยนขนาดข้ามจุดตัดมือถือ => สร้างตารางใหม่ด้วยความกว้างคอลัมน์ที่เหมาะสม
    let resizeTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if ($('tab-schedule').classList.contains('hidden')) return;
            if (hot && narrowLayout !== isNarrow()) renderTable();
            else if (hot) hot.refreshDimensions();
        }, 200);
    });

    // เปิดแท็บซ้ำ (เรียกจาก switchTab ใน pm.php): ไม่โหลดใหม่ — กดปุ่มโหลดใหม่/เปลี่ยนปีเอง
    // ตารางที่สร้างตอนแท็บซ่อนอยู่ต้อง refresh ขนาด
    window.initScheduleTab = function() {
        if (hot) setTimeout(() => hot.refreshDimensions(), 50);
        else if (!res && !loading) load();
    };

    updateHeaderText();
    load();
});
</script>
