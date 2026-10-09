<?php
//pm_schedule.php — แท็บตารางแผน PM: ดูแผน PM ที่บันทึกแล้ว + กำหนดการ (อ่านอย่างเดียว) ถูก include จาก pm.php (ผู้ใช้ทุกคนที่เข้าเมนูวางแผน PM ได้)
// มุมมอง: รายปี (เดือนละ 4 ช่อง แบบ Template Excel) / รายเดือน (ช่องละ 1 วัน) / รายสัปดาห์ (จ.–อา. ช่องละ 1 วัน)
@session_start();
include_once "config_ctrl/checksession.php";
?>
<style>
    /* ช่องกำหนดการ: ใช้ !important เพราะธีม Handsontable บังคับสีพื้นของ .htDimmed ด้วย !important
       ● รอดำเนินการ / ✓ ทำแล้ว / ! เลยกำหนด (ตัวเลข = มีหลายครั้งในช่องนั้น) */
    #sch-hot td.sch-week { padding: 0; text-align: center; font-size: 11px; font-weight: 600; }
    #sch-hot td.sch-weekend { background-color: #f8fafc !important; }
    #sch-hot td.sch-pending { background-color: #e0f2fe !important; color: #0369a1; }
    #sch-hot td.sch-done { background-color: #dcfce7 !important; color: #15803d; }
    #sch-hot td.sch-overdue { background-color: #fef3c7 !important; color: #b45309; }
    #sch-hot td.sch-month-end { border-right: 2px solid #cbd5e1; }
    #sch-hot td.sch-now { box-shadow: inset 2px 0 0 #f43f5e, inset -2px 0 0 #f43f5e; }
    #sch-hot td.sch-plan { color: #334155 !important; }
    #sch-hot th .sch-h-day { display: block; line-height: 1.15; font-weight: 600; }
    #sch-hot th .sch-h-dow { display: block; line-height: 1.1; font-size: 10px; font-weight: 500; opacity: .75; }
    .sch-legend { display: inline-flex; align-items: center; gap: .375rem; font-size: 12px; color: #475569; white-space: nowrap; }
    .sch-legend i { display: inline-flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; border-radius: .25rem; font-style: normal; font-size: 11px; font-weight: 600; }
    .sch-chip { display: inline-flex; align-items: center; gap: .25rem; padding: .25rem .625rem; border-radius: 9999px; font-size: 12px; white-space: nowrap; border: 1px solid; }
    .sch-view-btn { padding: .3rem .7rem; border-radius: .5rem; font-size: 13px; font-weight: 500; color: #475569; white-space: nowrap; }
    .sch-view-btn:hover { color: #0f172a; }
    .sch-view-btn.is-active { background: #fff; color: #0369a1; font-weight: 600; box-shadow: 0 1px 2px rgba(15, 23, 42, .12); }

    /* มือถือ/แท็บเล็ต: เลื่อนทั้งหน้า ตารางสูงตามจอ */
    #sch-hot { height: max(380px, calc(100vh - 290px)); }
    /* จอคอม (lg ขึ้นไป): การ์ดสูงพอดีพื้นที่ของแท็บ (ไม่ต้องเลื่อนทั้งหน้า) ตารางกินพื้นที่ที่เหลือในการ์ด
       จอเตี้ยกว่า min-height ของการ์ด => เลื่อนหน้าได้ */
    @media (min-width: 1024px) {
        #tab-schedule:not(.hidden) { height: 100%; display: flex; flex-direction: column; }
        #sch-card { flex: 1 1 auto; min-height: 480px; display: flex; flex-direction: column; }
        #sch-hot { flex: 1 1 0; height: auto; min-height: 0; }
    }
    /* เต็มจอ: การ์ดเต็มหน้าจอจริง (Fullscreen API) หรือเต็มหน้าเว็บ (เครื่องที่ไม่รองรับ เช่น iPhone) */
    #sch-card.sch-fs { position: fixed; inset: 0; z-index: 1000; border-radius: 0; overflow: hidden; display: flex; flex-direction: column; }
    #sch-card.sch-fs #sch-hot { flex: 1 1 auto; height: auto; min-height: 0; }
</style>
<div id="tab-schedule" class="tab-content hidden">
    <div id="sch-card" class="bg-white p-4 sm:p-5 rounded-xl shadow-sm border border-slate-200">
        <!-- หัวเรื่อง -->
        <div class="min-w-0">
            <h3 class="text-lg font-semibold text-slate-800 leading-tight">ตารางแผน PM</h3>
            <p id="sch-subtitle" class="text-[12px] text-slate-500 mt-0.5">แผน PM ที่บันทึกแล้ว และกำหนดการของช่วงเวลาที่เลือก (ดูอย่างเดียว)</p>
        </div>

        <!-- แถบเครื่องมือ -->
        <div class="mt-3 flex flex-wrap items-center gap-2 p-2 bg-slate-50 border border-slate-200 rounded-xl">
            <!-- มุมมอง -->
            <div id="sch-view" role="tablist" aria-label="มุมมอง" class="flex items-center gap-0.5 p-0.5 rounded-lg bg-slate-200/70">
                <button type="button" class="sch-view-btn is-active" data-view="year" role="tab">รายปี</button>
                <button type="button" class="sch-view-btn" data-view="month" role="tab">รายเดือน</button>
                <button type="button" class="sch-view-btn" data-view="week" role="tab">รายสัปดาห์</button>
            </div>
            <div class="flex items-center gap-1">
                <!-- รายปี: เลือกปี / รายเดือน-รายสัปดาห์: เลื่อนช่วงเวลา -->
                <select id="sch-year" aria-label="ปี" class="bg-white border border-slate-300 rounded-lg py-1.5 px-2.5 text-sm focus:ring-2 focus:ring-sky-500 outline-none"></select>
                <div id="sch-period" class="hidden flex items-center gap-1">
                    <button id="sch-prev" type="button" title="ก่อนหน้า" class="p-1.5 rounded-lg bg-white border border-slate-300 text-slate-600 hover:bg-slate-100"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                    <span id="sch-period-label" class="min-w-[9.5rem] text-center text-sm font-semibold text-slate-700 whitespace-nowrap"></span>
                    <button id="sch-next" type="button" title="ถัดไป" class="p-1.5 rounded-lg bg-white border border-slate-300 text-slate-600 hover:bg-slate-100"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                    <button id="sch-today" type="button" class="px-2.5 py-1.5 rounded-lg bg-white border border-slate-300 text-sm text-slate-600 hover:bg-slate-100">วันนี้</button>
                </div>
                <button id="sch-reload-btn" type="button" title="โหลดข้อมูลใหม่" class="p-1.5 sm:p-2 rounded-lg text-slate-600 hover:bg-white hover:text-slate-800 border border-transparent hover:border-slate-200">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
            </div>
            <label id="sch-only-wrap" class="hidden items-center gap-1.5 text-[13px] text-slate-600 cursor-pointer select-none">
                <input id="sch-only" type="checkbox" checked class="w-4 h-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500"> เฉพาะแผนที่มีงานในช่วงนี้
            </label>
            <!-- มือถือ: ตัวเลขสรุปลงไปแถวล่าง -->
            <div id="sch-summary" class="order-last w-full xl:order-none xl:w-auto flex flex-wrap items-center gap-1.5"></div>
            <div class="flex items-center gap-1.5 sm:gap-2 ml-auto">
                <button id="sch-help-btn" type="button" title="คำอธิบาย" aria-expanded="false" class="p-1.5 sm:p-2 rounded-lg text-slate-600 hover:bg-white border border-transparent hover:border-slate-200">
                    <i data-lucide="circle-help" class="w-4 h-4"></i>
                </button>
                <button id="sch-export-btn" type="button" title="Export Excel" class="bg-white border border-emerald-600 text-emerald-700 hover:bg-emerald-50 px-2 sm:px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-1.5 transition-colors">
                    <i data-lucide="file-down" class="w-4 h-4"></i> <span class="hidden sm:inline">Export Excel</span>
                </button>
                <button id="sch-fs-btn" type="button" title="ขยายเต็มจอ" class="bg-white text-sky-700 hover:bg-sky-50 px-2 sm:px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-1.5 transition-colors border border-sky-200">
                    <i data-lucide="maximize" class="w-4 h-4"></i> <span class="hidden sm:inline">ขยายเต็มจอ</span>
                </button>
            </div>
        </div>

        <!-- คำอธิบายสี (มือถืออยู่ในกล่องคำอธิบาย) + คำอธิบาย (พับเก็บ) -->
        <div class="mt-2.5 hidden sm:flex flex-wrap gap-x-4 gap-y-1">
            <span class="sch-legend"><i style="background:#e0f2fe;color:#0369a1">●</i>รอดำเนินการ</span>
            <span class="sch-legend"><i style="background:#dcfce7;color:#15803d">✓</i>ทำแล้ว</span>
            <span class="sch-legend"><i style="background:#fef3c7;color:#b45309">!</i>เลยกำหนด</span>
            <span class="sch-legend"><i style="box-shadow:inset 2px 0 0 #f43f5e,inset -2px 0 0 #f43f5e"></i><span class="sch-now-label">สัปดาห์นี้</span></span>
        </div>
        <div id="sch-help" class="hidden mt-2.5 rounded-lg bg-sky-50 border border-sky-100 p-3 text-[12px] text-slate-600">
            <div class="sm:hidden flex flex-wrap gap-x-4 gap-y-1 mb-2.5">
                <span class="sch-legend"><i style="background:#e0f2fe;color:#0369a1">●</i>รอดำเนินการ</span>
                <span class="sch-legend"><i style="background:#dcfce7;color:#15803d">✓</i>ทำแล้ว</span>
                <span class="sch-legend"><i style="background:#fef3c7;color:#b45309">!</i>เลยกำหนด</span>
                <span class="sch-legend"><i style="box-shadow:inset 2px 0 0 #f43f5e,inset -2px 0 0 #f43f5e"></i><span class="sch-now-label">สัปดาห์นี้</span></span>
            </div>
            <ul class="list-disc pl-4 space-y-1">
                <li><b>รายปี</b> — เดือนละ 4 ช่อง (วันที่ 1-7, 8-14, 15-21, 22 ถึงสิ้นเดือน) ตัวเลขในช่อง = จำนวนครั้งในช่องนั้น สีพื้นบอกสถานะ (เหลือง = มีงานเลยกำหนด)</li>
                <li><b>รายเดือน / รายสัปดาห์</b> — ช่องละ 1 วัน เห็นว่างานตรงกับวันไหน ช่องสีเทาอ่อน = เสาร์-อาทิตย์ ใช้ ‹ › เพื่อเลื่อนช่วงเวลา หรือกด <b>วันนี้</b></li>
                <li>ตารางนี้ใช้ดูอย่างเดียว — แก้ไขแผนได้ที่แท็บ <b>จัดการแผน PM</b></li>
                <li>Export Excel ส่งออกตามมุมมอง ลำดับ และตัวกรองที่แสดงอยู่ในตาราง</li>
            </ul>
        </div>

        <div id="sch-hot" class="mt-3 w-full overflow-hidden"></div>
        <div id="sch-empty" class="hidden py-16 text-center text-sm text-slate-400">ยังไม่มีแผน PM ในหน่วยงานนี้</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const TH_MONTHS = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    const TH_MON = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    const TH_DOW = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
    const PLAN_HEAD = ['ความถี่', 'แจ้งเตือนล่วงหน้า', 'ระบุวัน', 'รอบถัดไป', 'เริ่มแผน'];

    let freqOptions = [];
    let alertOptions = [];
    let contract = { start: '', end: '' };
    let agencyName = '';
    let hot = null;
    let today = '';
    let shownYear = null;      // ปีของ lastRows (ข้อมูลรายปี)
    let ready = false;
    let narrowLayout = null;   // จอแคบ (มือถือ) ใช้ความกว้างคอลัมน์ต่างกัน
    let view = 'year';         // year | month | week
    let cursor = null;         // วันแรกของเดือน (รายเดือน) / วันจันทร์ (รายสัปดาห์)
    let lastRows = [];         // แผน PM + ช่องรายปี (get_schedule)
    let dayData = {};          // plan_id => {YYYY-MM-DD: {n, done, overdue}} (get_days)
    let periods = [];          // คอลัมน์กำหนดการของมุมมองปัจจุบัน
    let summary = { plans: 0, overdue: 0, events: 0 };

    const $ = id => document.getElementById(id);
    const freqByValue = v => freqOptions.find(o => String(o.value) === String(v));
    const alertByValue = v => alertOptions.find(a => String(a.value) === String(v));
    const isNarrow = () => window.innerWidth < 640;
    const pad = n => String(n).padStart(2, '0');
    const ymd = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const parseYmd = s => { const [y, m, d] = String(s).split('-').map(Number); return new Date(y, m - 1, d); };
    const addDays = (d, n) => new Date(d.getFullYear(), d.getMonth(), d.getDate() + n);
    const mondayOf = d => addDays(d, -((d.getDay() + 6) % 7));
    const thDate = d => `${d.getDate()} ${TH_MON[d.getMonth()]} ${d.getFullYear() + 543}`;

    // เต็มจอทั้งเอกสาร => หน้าต่างแจ้งเตือน (z-index 1060) แสดงเหนือการ์ดเต็มจอ (1000) ได้ตามปกติ
    const fsElement = () => document.fullscreenElement || document.webkitFullscreenElement || null;
    const alertBox = opts => Swal.fire(opts);
    // เรียก showLoading ทันที (ไม่ใช้ didOpen: ถ้า Swal.close() ถูกเรียกก่อน popup เปิดเสร็จ didOpen จะสร้าง popup เปล่าค้างไว้)
    const loadingBox = (title, html = '') => { alertBox({ title, html, allowOutsideClick: false, showConfirmButton: false }); Swal.showLoading(); };

    // ช่องสัปดาห์ของวันนี้ในมุมมองรายปี (เหมือน weekIndex ใน handle_pm_schedule.php)
    function weekIndexOf(s) {
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s || '');
        return m ? (+m[2] - 1) * 4 + Math.min(4, Math.ceil(+m[3] / 7)) - 1 : -1;
    }

    // ---------- โหลดข้อมูล ----------
    async function loadOptions() {
        const res = await axios.get('handle_pm_import.php?action=get_options');
        if (!res.data.success) throw new Error(res.data.error);
        freqOptions = res.data.data.freqs;
        alertOptions = res.data.data.alerts;
        contract = res.data.data.contract;
        agencyName = res.data.data.agency || '';

        // ปีตามช่วงสัญญา (ไม่มีสัญญา => ปีนี้ ±1)
        const nowY = new Date().getFullYear();
        const y1 = parseInt((contract.start || '').slice(0, 4), 10) || nowY - 1;
        const y2 = parseInt((contract.end || '').slice(0, 4), 10) || nowY + 1;
        const years = [];
        for (let y = y1; y <= y2; y++) years.push(y);
        $('sch-year').innerHTML = years.map(y => `<option value="${y}">พ.ศ. ${y + 543}</option>`).join('');
        $('sch-year').value = years.includes(nowY) ? nowY : years[0];
    }

    // ช่วงวันที่ของมุมมองรายเดือน/รายสัปดาห์
    function periodRange() {
        if (view === 'month') return [cursor, new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0)];
        return [cursor, addDays(cursor, 6)];
    }

    async function loadView() {
        loadingBox('กำลังโหลดแผน PM...');
        try {
            if (view === 'year') {
                const year = parseInt($('sch-year').value, 10);
                const res = await axios.get('handle_pm_schedule.php', { params: { action: 'get_schedule', year } });
                if (!res.data.success) throw new Error(res.data.error);
                today = res.data.data.today;
                shownYear = year;
                lastRows = res.data.data.rows;
            } else {
                // รายการแผนไม่ขึ้นกับปี — ใช้ของเดิมได้ ถ้ายังไม่เคยโหลดค่อยโหลดปีของช่วงที่ดู
                if (!lastRows.length) {
                    const res = await axios.get('handle_pm_schedule.php', { params: { action: 'get_schedule', year: cursor.getFullYear() } });
                    if (!res.data.success) throw new Error(res.data.error);
                    shownYear = cursor.getFullYear();
                    lastRows = res.data.data.rows;
                }
                const [from, to] = periodRange();
                const res = await axios.get('handle_pm_schedule.php', { params: { action: 'get_days', from: ymd(from), to: ymd(to) } });
                if (!res.data.success) throw new Error(res.data.error);
                today = res.data.data.today;
                dayData = res.data.data.days || {};
            }
            Swal.close();
            renderTable();
        } catch (e) {
            alertBox({ icon: 'error', title: 'ข้อผิดพลาด', text: 'โหลดแผน PM ไม่สำเร็จ: ' + e.message });
        }
    }

    // ---------- คอลัมน์กำหนดการตามมุมมอง ----------
    // {key, group, label (HTML หัวคอลัมน์), text (ข้อความใน Excel), cls, now, get(row) => {n, done, overdue}}
    function buildPeriods() {
        if (view === 'year') {
            const year = shownYear;
            const nowIdx = String(year) === today.slice(0, 4) ? weekIndexOf(today) : -1;
            return Array.from({ length: 48 }, (_, k) => ({
                key: 'p' + k, group: `${MONTHS[Math.floor(k / 4)]} ${year}`, label: String(k + 1), text: k + 1,
                cls: k % 4 === 3 ? ' sch-month-end' : '', now: k === nowIdx, get: r => (r.weeks || {})[k]
            }));
        }
        const [from, to] = periodRange();
        const out = [];
        for (let d = from, k = 0; d <= to; d = addDays(d, 1), k++) {
            const s = ymd(d), dow = d.getDay(), weekend = dow === 0 || dow === 6;
            out.push({
                key: 'p' + k,
                group: view === 'month' ? `${TH_MONTHS[d.getMonth()]} ${d.getFullYear() + 543}` : `${TH_MONTHS[d.getMonth()]} ${d.getFullYear() + 543}`,
                label: view === 'month'
                    ? `<span class="sch-h-day">${d.getDate()}</span><span class="sch-h-dow">${TH_DOW[dow]}</span>`
                    : `<span class="sch-h-day">${TH_DOW[dow]} ${d.getDate()}</span>`,
                text: view === 'month' ? `${d.getDate()} ${TH_DOW[dow]}` : `${TH_DOW[dow]} ${d.getDate()} ${TH_MON[d.getMonth()]}`,
                cls: (weekend ? ' sch-weekend' : '') + (view === 'month' && dow === 0 ? ' sch-month-end' : ''),
                weekend, now: s === today, get: r => (dayData[r.plan_id] || {})[s]
            });
        }
        return out;
    }

    // ---------- ตาราง ----------
    function weekCell(w) {
        if (!w || !w.n) return { text: '', cls: '' };
        const text = w.n > 1 ? String(w.n) : (w.overdue ? '!' : (w.done === w.n ? '✓' : '●'));
        const cls = w.overdue ? 'sch-overdue' : (w.done === w.n ? 'sch-done' : 'sch-pending');
        return { text, cls };
    }

    function updateHeaderText() {
        const sub = { year: `แผน PM ที่บันทึกแล้ว และกำหนดการรายปี พ.ศ. ${(shownYear || 0) + 543} (เดือนละ 4 ช่อง)`,
                      month: 'กำหนดการรายเดือน — ช่องละ 1 วัน', week: 'กำหนดการรายสัปดาห์ (จันทร์–อาทิตย์) — ช่องละ 1 วัน' }[view];
        $('sch-subtitle').textContent = sub + ' (ดูอย่างเดียว)';
        document.querySelectorAll('.sch-now-label').forEach(el => el.textContent = view === 'year' ? 'สัปดาห์นี้' : 'วันนี้');
        if (view !== 'year') {
            const [from, to] = periodRange();
            $('sch-period-label').textContent = view === 'month'
                ? `${TH_MONTHS[from.getMonth()]} ${from.getFullYear() + 543}`
                : (from.getMonth() === to.getMonth() ? `${from.getDate()}–${to.getDate()} ${TH_MON[to.getMonth()]} ${to.getFullYear() + 543}` : `${thDate(from)} – ${thDate(to)}`);
        }
    }

    function renderTable() {
        periods = buildPeriods();
        updateHeaderText();
        const cellCls = {};   // plan_id => {key: class}
        summary = { plans: 0, overdue: 0, events: 0 };
        const only = view !== 'year' && $('sch-only').checked;
        const data = [];
        lastRows.forEach(r => {
            const f = freqByValue(r.freq);
            const a = alertByValue(r.alert);
            const row = {
                plan_id: r.plan_id, checksheet: r.checksheet || '-', code: r.code, location: r.location || r.machine,
                freq: f ? f.desc : '', alert: a ? a.desc : '', days: (r.days || []).join(', '),
                next_date: r.next_date || '', start_date: r.start_date || ''
            };
            cellCls[r.plan_id] = {};
            let overdue = false, n = 0;
            periods.forEach(p => {
                const w = p.get(r);
                const c = weekCell(w);
                row[p.key] = c.text;
                cellCls[r.plan_id][p.key] = c.cls;
                if (w && w.overdue) overdue = true;
                if (w) n += w.n;
            });
            if (only && !n) return;   // รายเดือน/รายสัปดาห์: ซ่อนแผนที่ไม่มีงานในช่วงนี้
            if (overdue) summary.overdue++;
            summary.events += n;
            data.push(row);
        });
        summary.plans = data.length;

        $('sch-empty').textContent = lastRows.length ? 'ไม่มีงาน PM ในช่วงเวลานี้' : 'ยังไม่มีแผน PM ในหน่วยงานนี้';
        $('sch-empty').classList.toggle('hidden', data.length > 0);
        $('sch-hot').classList.toggle('hidden', data.length === 0);
        if (hot) { hot.destroy(); hot = null; }
        if (!data.length) { updateCount(); return; }

        narrowLayout = isNarrow();
        const nP = periods.length;
        const planFirst = 3 + nP;
        // ความกว้างช่องกำหนดการ: รายปี 45 / รายเดือน 38 / รายสัปดาห์ 96 (จอแคบเล็กลง)
        const pw = { year: [45, 40], month: [38, 34], week: [96, 72] }[view][narrowLayout ? 1 : 0];
        const colWidths = narrowLayout
            ? [130, 90, 120, ...Array(nP).fill(pw), 140, 140, 90, 125, 90]
            : [180, 110, 150, ...Array(nP).fill(pw), 160, 170, 110, 135, 100];

        // หัวคอลัมน์ 2 แถว: แถวบนรวมช่องที่อยู่กลุ่มเดียวกัน (เดือน)
        const groups = [];
        periods.forEach(p => {
            const g = groups[groups.length - 1];
            if (g && g.label === p.group) g.colspan++;
            else groups.push({ label: p.group, colspan: 1 });
        });
        const nowCol = periods.findIndex(p => p.now);

        hot = new Handsontable($('sch-hot'), {
            data: data,
            colorScheme: 'light',          // ไม่ใช้โหมดมืดตามเครื่อง (ทั้งหน้าเป็นธีมสว่าง)
            readOnly: true,                // ดูอย่างเดียว — แก้แผนที่แท็บจัดการแผน PM
            nestedHeaders: [
                ['Name', 'CODE', 'Location', ...groups.map(g => g.colspan > 1 ? g : g.label), { label: 'แผน PM', colspan: 4 }, ''],
                ['', '', '', ...periods.map(p => p.label), ...PLAN_HEAD]
            ],
            columns: [
                { data: 'checksheet' },
                { data: 'code' },
                { data: 'location' },
                ...periods.map(p => ({ data: p.key })),
                { data: 'freq', className: 'sch-plan' },
                { data: 'alert', className: 'sch-plan' },
                { data: 'days', className: 'sch-plan' },
                { data: 'next_date', className: 'sch-plan htCenter' },
                { data: 'start_date', className: 'htCenter text-slate-500' }
            ],
            cells: function(row, col) {
                if (col < 3 || col >= planFirst) return {};
                // ใช้ this.instance (ตอนวาดครั้งแรกตัวแปร hot ยังไม่ถูกกำหนด)
                const p = periods[col - 3];
                const planId = this.instance.getDataAtRowProp(row, 'plan_id');
                let cls = 'sch-week ' + ((cellCls[planId] || {})[p.key] || '') + p.cls;
                if (p.now) cls += ' sch-now';
                return { className: cls };
            },
            rowHeaders: true,
            width: '100%',
            height: '100%',
            stretchH: 'none',
            colWidths: colWidths,
            fixedColumnsStart: narrowLayout ? 1 : 2,
            // ช่องกำหนดการกว้างคงที่: ถ้าให้ Handsontable คำนวณเอง (ตามหัวคอลัมน์/ปุ่มเมนูกรอง) ความกว้างที่ใช้คำนวณพื้นที่เลื่อน
            // ไม่ตรงกับที่วาดจริง => เลื่อนสุดขวาแล้วมีพื้นที่ว่าง
            modifyColWidth: (width, col) => (col >= 3 && col < planFirst ? colWidths[col] : width),
            fillHandle: false,
            filters: true,
            dropdownMenu: ['filter_by_condition', 'filter_by_value', 'filter_action_bar'],
            columnSorting: true,
            contextMenu: ['copy'],
            licenseKey: 'non-commercial-and-evaluation',
            afterGetColHeader: function(col, TH) {
                const plan = col >= planFirst && col < planFirst + 4;
                const p = col >= 3 && col < planFirst ? periods[col - 3] : null;
                const now = p && p.now;
                TH.style.backgroundColor = now ? '#ffe4e6' : (plan ? '#f0f9ff' : (p && p.weekend ? '#eef2f6' : '#f8fafc'));
                TH.style.color = now ? '#be123c' : (plan ? '#0284c7' : '#334155');
                // ช่องกำหนดการแคบ ไม่ต้องมีปุ่มกรอง
                if (p) TH.querySelector('.changeType')?.remove();
            }
        });
        // เลื่อนให้เห็นช่องปัจจุบัน (เว้นไว้ 2 ช่องก่อนหน้า) — คำนวณจากความกว้างคอลัมน์ที่ไม่ได้ตรึง
        // รายสัปดาห์มีแค่ 7 วัน ไม่ต้องเลื่อน (ให้เห็นตั้งแต่วันจันทร์)
        if (view !== 'week' && nowCol > 2) setTimeout(() => {
            const holder = $('sch-hot').querySelector('.ht_master .wtHolder');
            if (!holder) return;
            let x = 0;
            for (let c = narrowLayout ? 1 : 2; c < 3 + nowCol - 2; c++) x += colWidths[c];
            holder.scrollLeft = x;
        }, 80);
        updateCount();
        lucide.createIcons();
    }

    function updateCount() {
        const chip = (text, cls) => `<span class="sch-chip ${cls}">${text}</span>`;
        $('sch-summary').innerHTML =
            chip(`แผน PM <b>${summary.plans.toLocaleString()}</b>`, 'bg-white border-slate-200 text-slate-600') +
            (view !== 'year' ? chip(`งานในช่วงนี้ <b>${summary.events.toLocaleString()}</b> ครั้ง`, 'bg-sky-50 border-sky-200 text-sky-700') : '') +
            (summary.overdue ? chip(`เลยกำหนด <b>${summary.overdue.toLocaleString()}</b> แผน`, 'bg-amber-50 border-amber-200 text-amber-700') : '');
    }

    // ---------- เปลี่ยนมุมมอง / เลื่อนช่วงเวลา ----------
    function setView(v) {
        if (v === view) return;
        if (v !== 'year') {
            // เริ่มที่วันนี้ ถ้าปีที่เลือกอยู่คือปีนี้ ไม่งั้นเริ่มต้นปีที่เลือก
            const base = cursor || (String($('sch-year').value) === String(new Date().getFullYear()) ? new Date() : new Date(+$('sch-year').value, 0, 1));
            cursor = v === 'month' ? new Date(base.getFullYear(), base.getMonth(), 1) : mondayOf(base);
        } else if (cursor) {
            const y = String(cursor.getFullYear());
            if ([...$('sch-year').options].some(o => o.value === y)) $('sch-year').value = y;
        }
        view = v;
        document.querySelectorAll('.sch-view-btn').forEach(b => {
            const on = b.dataset.view === v;
            b.classList.toggle('is-active', on);
            b.setAttribute('aria-selected', String(on));
        });
        $('sch-year').classList.toggle('hidden', v !== 'year');
        $('sch-period').classList.toggle('hidden', v === 'year');
        $('sch-only-wrap').classList.toggle('hidden', v === 'year');
        $('sch-only-wrap').classList.toggle('flex', v !== 'year');
        loadView();
    }

    function shiftPeriod(dir) {
        cursor = view === 'month' ? new Date(cursor.getFullYear(), cursor.getMonth() + dir, 1) : addDays(cursor, 7 * dir);
        loadView();
    }

    document.querySelectorAll('.sch-view-btn').forEach(b => b.addEventListener('click', () => setView(b.dataset.view)));
    $('sch-prev').addEventListener('click', () => shiftPeriod(-1));
    $('sch-next').addEventListener('click', () => shiftPeriod(1));
    $('sch-today').addEventListener('click', () => {
        const now = new Date();
        cursor = view === 'month' ? new Date(now.getFullYear(), now.getMonth(), 1) : mondayOf(now);
        loadView();
    });
    $('sch-only').addEventListener('change', () => renderTable());

    // ---------- Export Excel: ตามมุมมองที่แสดง (Name / CODE / Location + ช่องกำหนดการ) ต่อด้วยข้อมูลแผน PM ----------
    // รายปีเป็นรูปแบบเดียวกับ Template นำเข้า / ส่งออกตามลำดับและตัวกรองที่แสดงอยู่ในตาราง (hot.getData())
    // ใช้ ExcelJS (ใส่สีหัวคอลัมน์/สีสถานะได้) — โหลดไว้แล้วใน pm.php พร้อม FileSaver (saveAs)
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
        cell: { 'sch-pending': ['FFE0F2FE', 'FF0369A1'], 'sch-done': ['FFDCFCE7', 'FF15803D'], 'sch-overdue': ['FFFEF3C7', 'FFB45309'] }
    };
    const xlFill = argb => ({ type: 'pattern', pattern: 'solid', fgColor: { argb } });
    const xlBorder = () => { const b = { style: 'thin', color: { argb: XL.border } }; return { top: b, left: b, bottom: b, right: b }; };

    $('sch-export-btn').addEventListener('click', async () => {
        if (!hot || !hot.countRows()) return alertBox({ icon: 'info', title: 'แจ้งเตือน', text: 'ไม่มีแผน PM ให้ส่งออก' });
        if (typeof ExcelJS === 'undefined' || typeof saveAs === 'undefined') {
            return alertBox({ icon: 'error', title: 'ข้อผิดพลาด', text: 'ไม่พบไลบรารี ExcelJS กรุณาโหลดหน้าใหม่' });
        }

        const nP = periods.length;
        const firstPlan = 4 + nP;                 // คอลัมน์แรกของข้อมูลแผน PM (นับจาก 1)
        const W = 3 + nP + PLAN_HEAD.length;
        const rows = hot.getData();
        const [from, to] = view === 'year' ? [] : periodRange();
        const title = view === 'year'
            ? `YEARLY PREVENTIVE MAINTENANCE SCHEDULE ${agencyName ? agencyName + ' ' : ''}FOR JANUARY ${shownYear} - DECEMBER ${shownYear}`
            : `ตารางแผน PM ${agencyName ? agencyName + ' ' : ''}${view === 'month' ? 'รายเดือน' : 'รายสัปดาห์'} ${thDate(from)} - ${thDate(to)}`;
        const sheetName = view === 'year' ? `PM ${shownYear}` : (view === 'month' ? `PM ${ymd(from).slice(0, 7)}` : `PM ${ymd(from)}`);
        const fileName = view === 'year' ? `PM_Schedule_${shownYear}.xlsx` : `PM_Schedule_${view === 'month' ? ymd(from).slice(0, 7) : 'week_' + ymd(from)}.xlsx`;

        const wb = new ExcelJS.Workbook();
        const ws = wb.addWorksheet(sheetName, { views: [{ state: 'frozen', xSplit: 2, ySplit: 4 }] });
        ws.columns = [{ width: 28 }, { width: 16 }, { width: 28 }, ...Array(nP).fill({ width: { year: 4.5, month: 5, week: 12 }[view] }),
                      { width: 22 }, { width: 22 }, { width: 14 }, { width: 12 }, { width: 12 }];

        // แถว 1-2: ชื่อรายงาน + ข้อมูล ณ วันที่
        ws.mergeCells(1, 1, 1, W);
        ws.mergeCells(2, 1, 2, W);
        Object.assign(ws.getCell(1, 1), { value: title, font: { bold: true, size: 14, color: { argb: XL.title.font } } });
        Object.assign(ws.getCell(2, 1), { value: `ข้อมูล ณ วันที่ ${today} · แผน PM ${rows.length.toLocaleString()} รายการ`, font: { size: 10, color: { argb: XL.info.font } } });
        ws.getRow(1).height = 22;

        // แถว 3-4: หัวคอลัมน์ (มีสี)
        // value === undefined: ใส่แค่สี (ช่องล่างของช่องที่รวมกัน — ถ้าใส่ค่าจะไปทับค่าของช่องบน)
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
        PLAN_HEAD.forEach((t, i) => { ws.mergeCells(3, firstPlan + i, 4, firstPlan + i); head(3, firstPlan + i, t, XL.plan); head(4, firstPlan + i, undefined, XL.plan); });
        ws.getRow(3).height = 20;
        ws.getRow(4).height = view === 'year' ? 18 : 30;

        // ข้อมูล: สีช่องกำหนดการตามสถานะเหมือนบนหน้าจอ
        rows.forEach((vals, r) => {
            const row = ws.getRow(5 + r);
            vals.forEach((v, c) => {
                const cell = row.getCell(c + 1);
                cell.value = v === null || v === undefined ? '' : v;
                cell.border = xlBorder();
                cell.font = { size: 10 };
                cell.alignment = { vertical: 'middle', wrapText: c < 3 || c >= 3 + nP };
                if (c >= 3 && c < 3 + nP) {
                    const p = periods[c - 3];
                    const cls = String(hot.getCellMeta(r, c).className || '');
                    const st = Object.keys(XL.cell).find(k => cls.includes(k));
                    cell.alignment = { horizontal: 'center', vertical: 'middle' };
                    if (st) { cell.fill = xlFill(XL.cell[st][0]); cell.font = { size: 10, bold: true, color: { argb: XL.cell[st][1] } }; }
                    else if (p.weekend) cell.fill = xlFill('FFF8FAFC');
                }
            });
        });

        // หมายเหตุท้ายตาราง
        const note = 5 + rows.length + 1;
        ws.mergeCells(note, 2, note, W);
        Object.assign(ws.getCell(note, 1), { value: 'หมายเหตุ', font: { bold: true, size: 10 } });
        Object.assign(ws.getCell(note, 2), {
            value: view === 'year'
                ? '● = รอดำเนินการ   ✓ = ทำแล้ว   ! = เลยกำหนด   ตัวเลข = จำนวนครั้งในสัปดาห์นั้น (เดือนละ 4 สัปดาห์: วันที่ 1-7, 8-14, 15-21, 22 ขึ้นไป) · สีพื้น: ฟ้า = รอดำเนินการ, เขียว = ทำแล้ว, เหลือง = มีงานเลยกำหนด'
                : '● = รอดำเนินการ   ✓ = ทำแล้ว   ! = เลยกำหนด   ตัวเลข = จำนวนครั้งในวันนั้น · สีพื้น: ฟ้า = รอดำเนินการ, เขียว = ทำแล้ว, เหลือง = มีงานเลยกำหนด',
            font: { size: 10, color: { argb: XL.info.font } }
        });

        const buf = await wb.xlsx.writeBuffer();
        saveAs(new Blob([buf], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }), fileName);
    });

    $('sch-year').addEventListener('change', loadView);
    $('sch-reload-btn').addEventListener('click', loadView);
    $('sch-help-btn').addEventListener('click', () => {
        const open = $('sch-help').classList.toggle('hidden') === false;
        $('sch-help-btn').setAttribute('aria-expanded', String(open));
        if (hot) setTimeout(() => hot.refreshDimensions(), 50);
    });

    // ---------- เต็มจอ ----------
    // การ์ดคลุมทั้งหน้า (.sch-fs) + ขอเต็มจอให้ "ทั้งเอกสาร" (ทะลุ iframe ของ main.php ที่มี allowfullscreen)
    // ไม่ขอเต็มจอเฉพาะการ์ด: Handsontable วางเมนูกรองไว้ใน <body> ซึ่งจะมองไม่เห็นถ้าเต็มจอแค่การ์ด
    // เครื่องที่ไม่รองรับ (เช่น Safari บน iPhone) => การ์ดคลุมพื้นที่หน้าเว็บอย่างเดียว
    function setFsButton(on) {
        $('sch-fs-btn').innerHTML = on
            ? '<i data-lucide="minimize" class="w-4 h-4"></i> <span class="hidden sm:inline">ย่อหน้าจอ</span>'
            : '<i data-lucide="maximize" class="w-4 h-4"></i> <span class="hidden sm:inline">ขยายเต็มจอ</span>';
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
            if (hot && narrowLayout !== isNarrow()) renderTable();
            else if (hot) hot.refreshDimensions();
        }, 200);
    });

    // โหลดครั้งแรกเมื่อเปิดแท็บ (เรียกจาก switchTab ใน pm.php) — ตารางที่สร้างตอนแท็บซ่อนอยู่ต้อง refresh ขนาด
    window.initScheduleTab = async function() {
        if (!ready) {
            ready = true;
            try {
                await loadOptions();
            } catch (e) {
                ready = false;
                return alertBox({ icon: 'error', title: 'ข้อผิดพลาด', text: 'โหลดตัวเลือกไม่สำเร็จ: ' + e.message });
            }
            return loadView();
        }
        if (hot) setTimeout(() => hot.refreshDimensions(), 50);
    };
});
</script>
