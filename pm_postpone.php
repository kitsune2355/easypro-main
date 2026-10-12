<?php
@session_start();
include "config_ctrl/checksession.php";
$ppMonths = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
$ppYear = (int)date('Y');
?>

<div id="tab-postpone" class="tab-content hidden h-full w-full">
    <section class="bg-white rounded-xl border border-slate-200 shadow-sm h-full flex flex-col min-h-0">

        <!-- หัวแท็บ -->
        <div class="px-4 sm:px-5 pt-4 pb-3 shrink-0">
            <h2 class="text-lg font-semibold text-slate-800">เลื่อนแผน PM</h2>
            <p class="text-xs text-slate-500 mt-0.5">เลือกวันที่ใหม่ให้งานที่ยังไม่ได้ทำ (แก้ได้หลายแถว) แล้วกดบันทึกทีเดียว — ระบบเก็บประวัติการเลื่อนทุกครั้ง</p>
        </div>

        <!-- แถบเครื่องมือ: เดือน/ปี · ค้นหา · ตัวกรอง · จำนวน -->
        <div class="px-4 sm:px-5 pb-3 flex flex-wrap items-center gap-2 shrink-0">
            <div class="flex items-center gap-1.5">
                <select id="pp-month" class="pp-ctl w-[7.5rem]" aria-label="เดือน (วันที่แผนเดิม)">
                    <option value="">ทุกเดือน</option>
                    <?php foreach ($ppMonths as $i => $name) { $m = $i + 1; ?>
                        <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>><?= $name ?></option>
                    <?php } ?>
                </select>
                <select id="pp-year" class="pp-ctl w-[5.5rem]" aria-label="ปี (วันที่แผนเดิม)">
                    <option value="">ทุกปี</option>
                    <?php for ($y = $ppYear + 1; $y >= $ppYear - 5; $y--) { ?>
                        <option value="<?= $y ?>" <?= $y == $ppYear ? 'selected' : '' ?>><?= $y + 543 ?></option>
                    <?php } ?>
                </select>
                <button type="button" id="pp-this-month" class="pp-btn-quiet" title="กลับไปเดือนปัจจุบัน">เดือนนี้</button>
            </div>
            <div class="relative flex-1 min-w-[12rem] max-w-sm">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                <input type="search" id="pp-search" placeholder="ค้นหาเครื่องจักร / รหัส / เช็คชีต" autocomplete="off" class="pp-ctl w-full !pl-9">
            </div>
            <button type="button" id="pp-filter-btn" class="pp-btn-quiet" aria-expanded="false" aria-controls="pp-filter-panel">
                <i data-lucide="sliders-horizontal" class="w-4 h-4"></i> ตัวกรอง
                <span id="pp-filter-badge" class="hidden min-w-[1.25rem] h-5 px-1 rounded-full bg-[#006B9F] text-white text-[11px] leading-5 text-center">0</span>
            </button>
            <span id="pp-count" class="ml-auto text-xs text-slate-500 whitespace-nowrap"></span>
        </div>

        <!-- ตัวกรองเพิ่มเติม (โหลดตัวเลือกเมื่อเปิดครั้งแรก) -->
        <div id="pp-filter-panel" class="hidden mx-4 sm:mx-5 mb-3 p-3 rounded-lg border border-slate-200 bg-slate-50 shrink-0">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 items-end">
                <label class="text-xs text-slate-500">เช็คชีต
                    <select id="pp-f-checksheet" class="pp-ctl w-full mt-1"><option value="">ทั้งหมด</option></select>
                </label>
                <label class="text-xs text-slate-500">ประเภทเครื่องจักร
                    <select id="pp-f-type" class="pp-ctl w-full mt-1"><option value="">ทั้งหมด</option></select>
                </label>
                <label class="text-xs text-slate-500">สถานที่ตั้ง
                    <select id="pp-f-location" class="pp-ctl w-full mt-1"><option value="">ทั้งหมด</option></select>
                </label>
                <div class="flex items-center justify-between gap-2 h-9">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" id="pp-f-overdue" class="w-4 h-4 rounded border-slate-300 text-[#006B9F] focus:ring-sky-500"> เฉพาะงานเลยกำหนด
                    </label>
                    <button type="button" id="pp-f-clear" class="text-xs text-slate-500 hover:text-[#006B9F] underline-offset-2 hover:underline">ล้างตัวกรอง</button>
                </div>
            </div>
            <p id="pp-filter-msg" class="hidden text-xs text-slate-400 mt-2"></p>
        </div>

        <!-- ตาราง (จอใหญ่) / การ์ด (มือถือ) -->
        <div class="relative flex-1 min-h-0 mx-4 sm:mx-5">
            <div id="pp-hot-wrap" class="absolute inset-0 border border-slate-200 rounded-lg overflow-hidden">
                <div id="hot-container" class="w-full h-full"></div>
            </div>
            <div id="pp-cards" class="absolute inset-0 overflow-y-auto flex-col gap-2.5 pb-2"></div>
            <div id="pp-state" class="hidden absolute inset-0 z-20 flex flex-col items-center justify-center gap-2 bg-white/85 rounded-lg text-sm text-slate-500"></div>
        </div>

        <!-- แถบล่าง: แบ่งหน้า + ปุ่มบันทึก (อยู่ติดล่างเสมอ) -->
        <div class="px-4 sm:px-5 py-3 mt-3 border-t border-slate-100 flex flex-wrap items-center gap-x-4 gap-y-2 shrink-0">
            <div class="flex items-center gap-2 text-xs text-slate-500">
                <button type="button" id="pp-prev" class="pp-icon-btn" title="หน้าก่อนหน้า"><i class="fas fa-chevron-left text-[11px]"></i></button>
                <span id="pp-page-info" class="whitespace-nowrap">0 รายการ</span>
                <button type="button" id="pp-next" class="pp-icon-btn" title="หน้าถัดไป"><i class="fas fa-chevron-right text-[11px]"></i></button>
                <select id="pp-size" class="pp-ctl !h-8 !py-0 text-xs w-[6.75rem]" aria-label="แถวต่อหน้า">
                    <option value="20">20 / หน้า</option>
                    <option value="50" selected>50 / หน้า</option>
                    <option value="100">100 / หน้า</option>
                </select>
            </div>
            <div class="flex items-center gap-2 ml-auto w-full sm:w-auto">
                <button type="button" id="pp-discard" class="hidden pp-btn-quiet">ยกเลิกการแก้ไข</button>
                <button type="button" id="pp-save" disabled class="btn-gradient flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 h-10 rounded-lg text-sm font-semibold disabled:opacity-40 disabled:cursor-not-allowed disabled:shadow-none disabled:transform-none">
                    <i class="fas fa-calendar-check"></i> <span id="pp-save-label">บันทึกการเลื่อน</span>
                </button>
            </div>
        </div>
    </section>
</div>

<style>
    #tab-postpone .pp-ctl { height: 2.25rem; border: 1px solid #e2e8f0; border-radius: .5rem; background: #fff; padding: 0 .65rem; font-size: 13px; color: #334155; outline: none; }
    #tab-postpone .pp-ctl:focus { border-color: #04ADFF; box-shadow: 0 0 0 2px rgba(4,173,255,.15); }
    #tab-postpone .pp-btn-quiet { display: inline-flex; align-items: center; gap: .4rem; height: 2.25rem; padding: 0 .75rem; border: 1px solid #e2e8f0; border-radius: .5rem; background: #fff; font-size: 13px; color: #475569; white-space: nowrap; }
    #tab-postpone .pp-btn-quiet:hover { border-color: #cbd5e1; color: #0f172a; background: #f8fafc; }
    #tab-postpone .pp-btn-quiet.hidden { display: none; }
    #tab-postpone .pp-btn-quiet[aria-expanded="true"] { border-color: #006B9F; color: #006B9F; background: #f0f9ff; }
    #tab-postpone .pp-icon-btn { width: 2rem; height: 2rem; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; border-radius: .5rem; color: #475569; background: #fff; }
    #tab-postpone .pp-icon-btn:hover:not(:disabled) { color: #006B9F; border-color: #cbd5e1; }
    #tab-postpone .pp-icon-btn:disabled { opacity: .35; cursor: default; }

    /* เซลล์ตาราง: บรรทัดหลัก + บรรทัดรอง */
    #hot-container .handsontable th { background: #f8fafc; color: #64748b; font-size: 12px; font-weight: 500; text-align: left; padding-left: 8px; }
    #hot-container .handsontable td.pp-td { vertical-align: middle; padding: 0 12px; background: #fff; border-color: #f1f5f9; line-height: 1.3; }
    #hot-container .handsontable td.pp-td.pp-dirty { background: #f0f9ff; }
    #hot-container .handsontable td.pp-td.pp-bad { background: #fef2f2; }
    #hot-container .handsontable td.pp-td.pp-first.pp-dirty { box-shadow: inset 3px 0 0 #04ADFF; }
    #hot-container .handsontable td.pp-td.pp-first.pp-bad { box-shadow: inset 3px 0 0 #ef4444; }
    #tab-postpone .pp-l1 { font-size: 14px; font-weight: 500; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #tab-postpone .pp-l2 { font-size: 12px; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 2px; }
    #tab-postpone .pp-ph { font-size: 13px; color: #94a3b8; }
    #tab-postpone .pp-err { color: #dc2626 !important; }
    #tab-postpone .pp-chip { display: inline-flex; align-items: center; gap: .3rem; height: 1.6rem; padding: 0 .5rem; border-radius: 999px; font-size: 12px; color: #64748b; background: #f1f5f9; }
    #tab-postpone .pp-chip:hover { color: #006B9F; background: #e0f2fe; }
    #tab-postpone .pp-undo { width: 1.75rem; height: 1.75rem; display: inline-flex; align-items: center; justify-content: center; border-radius: .4rem; color: #94a3b8; }
    #tab-postpone .pp-undo:hover { color: #dc2626; background: #fef2f2; }

    /* มือถือ: การ์ด */
    #tab-postpone #pp-cards { display: none; }
    #tab-postpone .pp-card { background: #fff; border: 1px solid #e2e8f0; border-radius: .9rem; padding: .8rem; }
    #tab-postpone .pp-card.pp-dirty { border-color: #7dd3fc; background: #f0f9ff; }
    #tab-postpone .pp-card.pp-bad { border-color: #fca5a5; background: #fef2f2; }
    @media (max-width: 767.98px) {
        #tab-postpone #pp-hot-wrap { display: none; }
        #tab-postpone #pp-cards { display: flex; }
    }
</style>
<!-- moment.js: pm_dashboard.php ใช้ด้วย (โหลดจากไฟล์นี้) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

<script>
(function () {
    // ค่าวันสัญญาจาก session (ค่าจริงส่งมาพร้อมข้อมูลหน้าแรกอีกครั้ง)
    const PP = {
        contract: { start: '<?php echo isset($sess_ag_start_date) ? $sess_ag_start_date : ""; ?>', end: '<?php echo isset($sess_ag_end_date) ? $sess_ag_end_date : ""; ?>' },
        holidays: [],
        today: '',
        rows: [],          // แถวในหน้าปัจจุบัน — ตารางและการ์ดแก้ข้อมูลชุดเดียวกัน
        total: 0,
        page: 1,
        size: 50,
        applied: null,     // ค่าตัวกรองที่ใช้กับข้อมูลที่แสดงอยู่
        req: 0,
        optionsLoaded: false,
        hot: null,
    };
    // heightAuto: false — กัน SweetAlert ตั้ง body เป็น height:auto จนตาราง (สูง h-full) ยุบตอนเปิดกล่อง
    const ppSwal = Swal.mixin({ heightAuto: false });
    const $ = id => document.getElementById(id);
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const isMobile = () => window.matchMedia('(max-width: 767.98px)').matches;
    const fmt = d => d ? window.formatDate(d, false) : '-';
    const ymd = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const todayStr = () => PP.today || ymd(new Date());
    const dayDiff = (a, b) => Math.round((new Date(b + 'T00:00:00') - new Date(a + 'T00:00:00')) / 86400000);
    const fmtNum = n => Number(n || 0).toLocaleString('th-TH');

    // ---------- ตรวจวันที่ (ให้ตรงกับฝั่ง server) ----------
    const THAI_DAY = { 'จ.': 1, 'อ.': 2, 'พ.': 3, 'พฤ.': 4, 'ศ.': 5, 'ส.': 6, 'อา.': 7 };
    function holidayName(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        const dow = d.getDay() || 7;
        if (dow === 7) return 'วันอาทิตย์';
        for (const h of PP.holidays) {
            const hd = String(h.date || '').trim();
            if (h.type === 'once' && hd === dateStr) return h.name;
            if (h.type === 'yearly' && hd.length >= 10 && hd.slice(5, 10) === dateStr.slice(5, 10)) return h.name;
            if (h.type === 'daily' && (THAI_DAY[hd] || +hd || 0) === dow) return h.name;
        }
        return '';
    }
    // แปลงค่าที่พิมพ์/วางเป็น YYYY-MM-DD (รับ 2026-10-15, 15/10/2026, 15/10/2569)
    function normDate(v) {
        if (v == null) return null;
        const s = String(v).trim();
        if (!s) return null;
        let m = s.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
        let y, mo, d;
        if (m) { [y, mo, d] = [+m[1], +m[2], +m[3]]; }
        else if ((m = s.match(/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/))) { [d, mo, y] = [+m[1], +m[2], +m[3]]; }
        else return s;
        if (y > 2400) y -= 543;
        const dt = new Date(y, mo - 1, d);
        return dt.getMonth() === mo - 1 ? ymd(dt) : s;
    }
    function rowError(r, forSave) {
        if (!r.nd) return '';
        if (!/^\d{4}-\d{2}-\d{2}$/.test(r.nd)) return 'รูปแบบวันที่ไม่ถูกต้อง';
        const t = todayStr();
        if (r.nd < t) return 'ห้ามเลื่อนเป็นวันในอดีต';
        if (r.nd === r.d) return 'ตรงกับวันที่เดิม';
        if (PP.contract.end && r.nd > PP.contract.end) return 'เกินวันสิ้นสุดสัญญา (' + fmt(PP.contract.end) + ')';
        if (PP.contract.start && r.nd < PP.contract.start) return 'ก่อนวันเริ่มสัญญา';
        const hol = holidayName(r.nd);
        if (hol) return 'ตรงกับวันหยุด: ' + hol;
        if (r.d < t && r.freq_days < 30) return 'งานเลยกำหนดที่ถี่กว่า 30 วัน เลื่อนไม่ได้';
        if (forSave && !String(r.reason || '').trim()) return 'ต้องระบุเหตุผล';
        return '';
    }
    const dirtyRows = () => PP.rows.filter(r => r.nd);

    // ---------- ตัวกรอง ----------
    function readFilters() {
        return {
            month: $('pp-month').value, year: $('pp-year').value, q: $('pp-search').value.trim(),
            checksheet: $('pp-f-checksheet').value, type: $('pp-f-type').value, location: $('pp-f-location').value,
            overdue: $('pp-f-overdue').checked ? '1' : '',
        };
    }
    function writeFilters(f) {
        $('pp-month').value = f.month; $('pp-year').value = f.year; $('pp-search').value = f.q;
        $('pp-f-checksheet').value = f.checksheet; $('pp-f-type').value = f.type; $('pp-f-location').value = f.location;
        $('pp-f-overdue').checked = f.overdue === '1';
        updateBadge();
    }
    function updateBadge() {
        const n = ['pp-f-checksheet', 'pp-f-type', 'pp-f-location'].filter(id => $(id).value).length + ($('pp-f-overdue').checked ? 1 : 0);
        $('pp-filter-badge').textContent = n;
        $('pp-filter-badge').classList.toggle('hidden', !n);
    }
    // ถ้ามีรายการที่ยังไม่บันทึก ถามก่อนทิ้ง — ยกเลิก => คืนค่าตัวกรองเดิม
    async function confirmDiscard() {
        const n = dirtyRows().length;
        if (!n) return true;
        const r = await ppSwal.fire({
            title: 'ทิ้งการแก้ไข?', text: `มี ${n} รายการที่ยังไม่ได้บันทึก`, icon: 'warning',
            showCancelButton: true, confirmButtonText: 'ทิ้งการแก้ไข', cancelButtonText: 'กลับไปแก้ต่อ', confirmButtonColor: '#dc2626',
        });
        return r.isConfirmed;
    }
    async function applyFilters(resetPage = true) {
        if (!(await confirmDiscard())) { if (PP.applied) writeFilters(PP.applied); return; }
        if (resetPage) PP.page = 1;
        updateBadge();
        load();
    }

    // ตัวเลือกตัวกรองโหลดเมื่อเปิดแผงครั้งแรก
    async function loadFilterOptions() {
        if (PP.optionsLoaded) return;
        PP.optionsLoaded = true;
        const msg = $('pp-filter-msg');
        msg.textContent = 'กำลังโหลดตัวเลือก...';
        msg.classList.remove('hidden', 'pp-err');
        try {
            const res = await (await fetch('handle_pm_postpone.php?action=options')).json();
            if (!res.success) throw new Error(res.error || 'โหลดตัวเลือกไม่สำเร็จ');
            const fill = (id, items) => {
                const sel = $(id), cur = sel.value;
                sel.innerHTML = '<option value="">ทั้งหมด</option>' + items.map(v => `<option value="${esc(v)}">${esc(v)}</option>`).join('');
                sel.value = cur;
            };
            fill('pp-f-checksheet', res.checksheets || []);
            fill('pp-f-type', res.types || []);
            fill('pp-f-location', res.locations || []);
            msg.classList.add('hidden');
        } catch (e) {
            PP.optionsLoaded = false;
            msg.textContent = 'โหลดตัวเลือกไม่สำเร็จ: ' + e.message;
            msg.classList.add('pp-err');
        }
    }

    // ---------- โหลดข้อมูล (แบ่งหน้าฝั่ง server) ----------
    function showState(html) {
        const el = $('pp-state');
        el.innerHTML = html || '';
        el.classList.toggle('hidden', !html);
    }
    async function load() {
        const f = readFilters();
        const myReq = ++PP.req;
        const first = !PP.today;
        const params = new URLSearchParams({ action: 'list', page: PP.page, size: PP.size, ...f });
        if (first) params.set('meta', '1');
        showState('<i class="fas fa-circle-notch fa-spin text-slate-300 text-xl"></i><span>กำลังโหลด...</span>');
        try {
            const res = await (await fetch('handle_pm_postpone.php?' + params)).json();
            if (myReq !== PP.req) return;   // มีคำขอใหม่กว่าแล้ว
            if (!res.success) throw new Error(res.error || 'โหลดข้อมูลไม่สำเร็จ');
            if (res.today) {
                PP.today = res.today;
                PP.holidays = res.holidays || [];
                if (res.contract) PP.contract = res.contract;
            }
            const cols = res.cols;
            PP.rows = (res.rows || []).map(a => {
                const o = {};
                cols.forEach((c, i) => { o[c] = a[i]; });
                o.nd = null; o.reason = ''; o.err = '';
                return o;
            });
            PP.total = res.total;
            PP.applied = f;
            // หน้าเกินจำนวน (เช่นหลังบันทึกแล้วรายการลดลง) => ไปหน้าสุดท้าย
            const pages = Math.max(1, Math.ceil(PP.total / PP.size));
            if (PP.page > pages && PP.total > 0) { PP.page = pages; return load(); }
            showState(PP.rows.length ? '' : `<i class="fas fa-calendar-check text-3xl text-slate-200"></i><span>ไม่พบงานที่รอดำเนินการตามเงื่อนไขนี้</span>`);
            renderAll();
        } catch (e) {
            if (myReq !== PP.req) return;
            showState(`<i class="fas fa-triangle-exclamation text-2xl text-red-300"></i><span class="text-red-600">${esc(e.message)}</span>
                <button type="button" class="pp-btn-quiet mt-1" onclick="fetchPostponeData()">ลองอีกครั้ง</button>`);
        }
    }

    function renderAll() {
        if (PP.hot) PP.hot.loadData(PP.rows);
        renderCards();
        updateFooter();
    }
    function updateFooter() {
        const from = PP.total ? (PP.page - 1) * PP.size + 1 : 0;
        const to = Math.min(PP.total, PP.page * PP.size);
        $('pp-count').textContent = `${fmtNum(PP.total)} รายการ`;
        $('pp-page-info').textContent = PP.total ? `${fmtNum(from)}–${fmtNum(to)} จาก ${fmtNum(PP.total)}` : '0 รายการ';
        $('pp-prev').disabled = PP.page <= 1;
        $('pp-next').disabled = to >= PP.total;
        const n = dirtyRows().length;
        $('pp-save').disabled = n === 0;
        $('pp-save-label').textContent = n ? `บันทึกการเลื่อน (${n} รายการ)` : 'บันทึกการเลื่อน';
        $('pp-discard').classList.toggle('hidden', n === 0);
    }
    // แก้ค่าแถว => ตรวจใหม่ + วาดเฉพาะส่วนที่เกี่ยวข้อง
    function touchRow(i) {
        const r = PP.rows[i];
        if (!r) return;
        r.err = rowError(r, false);
        updateFooter();
    }

    // ---------- ตาราง (จอใหญ่) ----------
    function tdBase(td, r, first) {
        td.className = 'pp-td' + (first ? ' pp-first' : '') + (r && r.nd ? (r.err ? ' pp-bad' : ' pp-dirty') : '');
    }
    function cellRenderer(kind) {
        return function (instance, td, row) {
            const r = PP.rows[row];
            tdBase(td, r, kind === 'machine');
            if (!r) { td.innerHTML = ''; return td; }
            const t = todayStr();
            let h = '';
            if (kind === 'machine') {
                const sub = [r.code, r.type, r.loc].filter(Boolean).map(esc).join(' · ');
                h = `<div class="pp-l1" title="${esc(r.machine)}">${esc(r.machine || '-')}</div><div class="pp-l2" title="${sub}">${sub}</div>`;
            } else if (kind === 'cs') {
                h = `<div class="pp-l1" title="${esc(r.cs)}">${esc(r.cs || '-')}</div><div class="pp-l2">${esc(r.freq_label || '')}</div>`;
            } else if (kind === 'old') {
                const late = dayDiff(r.d, t);
                const sub = late > 0 ? `<div class="pp-l2 pp-err">เลยกำหนด ${late} วัน</div>` : late === 0 ? '<div class="pp-l2">วันนี้</div>' : `<div class="pp-l2">อีก ${-late} วัน</div>`;
                h = `<div class="pp-l1 ${r.nd ? '!text-slate-400 line-through decoration-slate-300' : ''}">${fmt(r.d)}</div>${sub}`;
            } else if (kind === 'new') {
                if (r.nd) {
                    const ok = /^\d{4}-\d{2}-\d{2}$/.test(r.nd);
                    const sub = r.err ? `<div class="pp-l2 pp-err" title="${esc(r.err)}">${esc(r.err)}</div>`
                        : `<div class="pp-l2">เลื่อน ${dayDiff(r.d, r.nd) > 0 ? '+' : ''}${dayDiff(r.d, r.nd)} วัน</div>`;
                    h = `<div class="pp-l1 ${r.err ? 'pp-err' : '!text-[#006B9F]'}"><i class="fas fa-arrow-right text-[10px] mr-1 opacity-60"></i>${ok ? fmt(r.nd) : esc(r.nd)}</div>${sub}`;
                } else {
                    h = '<span class="pp-ph"><i class="far fa-calendar mr-1.5"></i>เลือกวันที่ใหม่</span>';
                }
            } else if (kind === 'reason') {
                h = r.reason ? `<div class="pp-l1 !font-normal" title="${esc(r.reason)}">${esc(r.reason)}</div>`
                    : `<span class="pp-ph ${r.nd ? '!text-amber-600' : ''}">${r.nd ? 'ต้องระบุเหตุผล' : 'ระบุเหตุผล'}</span>`;
            } else if (kind === 'act') {
                h = `<div class="flex items-center justify-end gap-1">
                    ${r.pc > 0 ? `<button type="button" data-act="hist" class="pp-chip" title="ดูประวัติการเลื่อน"><i class="fas fa-clock-rotate-left"></i>${r.pc}</button>` : ''}
                    ${r.nd || r.reason ? '<button type="button" data-act="undo" class="pp-undo" title="ยกเลิกการแก้ไขแถวนี้"><i class="fas fa-rotate-left"></i></button>' : ''}
                </div>`;
            }
            td.innerHTML = h;
            return td;
        };
    }
    function initHot() {
        if (PP.hot || isMobile()) return;
        const t = todayStr();
        PP.hot = new Handsontable($('hot-container'), {
            data: PP.rows,
            colHeaders: ['เครื่องจักร', 'เช็คชีต · ความถี่', 'วันที่เดิม', 'วันที่ใหม่', 'เหตุผลที่เลื่อน', 'ประวัติ'],
            columns: [
                { data: 'machine', readOnly: true, renderer: cellRenderer('machine'), width: 280 },
                { data: 'cs', readOnly: true, renderer: cellRenderer('cs'), width: 230 },
                { data: 'd', readOnly: true, renderer: cellRenderer('old'), width: 130 },
                {
                    data: 'nd', editor: 'date', renderer: cellRenderer('new'), width: 180,
                    dateFormat: 'YYYY-MM-DD', defaultDate: t,
                    datePickerConfig: {
                        firstDay: 1,
                        minDate: new Date(t + 'T00:00:00'),
                        maxDate: PP.contract.end ? new Date(PP.contract.end + 'T00:00:00') : undefined,
                        disableDayFn: d => !!holidayName(ymd(d)),
                    },
                },
                { data: 'reason', type: 'text', renderer: cellRenderer('reason'), width: 220 },
                { data: 'pc', readOnly: true, renderer: cellRenderer('act'), width: 96 },
            ],
            rowHeaders: false,
            rowHeights: 54,
            autoRowSize: false,
            stretchH: 'all',
            height: '100%',
            width: '100%',
            fillHandle: { direction: 'vertical', autoInsertRow: false },
            beforeChange(changes) {
                changes.forEach(c => {
                    if (c && c[1] === 'nd') c[3] = normDate(c[3]);
                    if (c && c[1] === 'reason') c[3] = c[3] == null ? '' : String(c[3]);
                });
            },
            afterChange(changes, source) {
                if (!changes || source === 'loadData') return;
                changes.forEach(([row, prop, , val]) => {
                    if (PP.rows[row]) PP.rows[row][prop] = val;   // กันกรณีตารางไม่ได้ผูกอ้างอิงเดียวกัน
                    touchRow(row);
                });
                PP.hot.render();
            },
            afterOnCellMouseDown(e, coords) {
                if (coords.row < 0) return;
                const act = e.target.closest && e.target.closest('[data-act]');
                if (!act) return;
                const r = PP.rows[coords.row];
                if (act.dataset.act === 'hist') showPostponeHistory(r.id);
                if (act.dataset.act === 'undo') undoRow(coords.row);
            },
            licenseKey: 'non-commercial-and-evaluation',
        });
        window.hotInstance = PP.hot;   // pm.php สั่ง render() ตอนกลับมาที่แท็บ
    }
    function undoRow(i) {
        const r = PP.rows[i];
        if (!r) return;
        r.nd = null; r.reason = ''; r.err = '';
        if (PP.hot) PP.hot.render();
        renderCards();
        updateFooter();
    }

    // ---------- การ์ด (มือถือ) ----------
    function cardHtml(r, i) {
        const t = todayStr();
        const late = dayDiff(r.d, t);
        const max = PP.contract.end || '';
        const sub = [r.code, r.type, r.loc].filter(Boolean).map(esc).join(' · ');
        return `<div class="pp-card ${r.nd ? (r.err ? 'pp-bad' : 'pp-dirty') : ''}" data-row="${i}">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <div class="pp-l1 !whitespace-normal">${esc(r.machine || '-')}</div>
                    <div class="pp-l2 !whitespace-normal">${sub}</div>
                </div>
                ${r.pc > 0 ? `<button type="button" data-act="hist" class="pp-chip shrink-0" title="ดูประวัติการเลื่อน"><i class="fas fa-clock-rotate-left"></i>${r.pc}</button>` : ''}
            </div>
            <div class="pp-l2 !whitespace-normal mt-1.5"><i class="fas fa-clipboard-check text-slate-400 mr-1"></i>${esc(r.cs || '-')}${r.freq_label ? ' · ' + esc(r.freq_label) : ''}</div>
            <div class="mt-2.5 pt-2.5 border-t border-slate-100 grid grid-cols-2 gap-2 items-end">
                <div>
                    <div class="text-[11px] text-slate-400">วันที่เดิม</div>
                    <div class="text-sm ${r.nd ? 'text-slate-400 line-through' : 'text-slate-700'}">${fmt(r.d)}</div>
                    <div class="text-[11px] ${late > 0 ? 'pp-err' : 'text-slate-400'}">${late > 0 ? `เลยกำหนด ${late} วัน` : late === 0 ? 'วันนี้' : `อีก ${-late} วัน`}</div>
                </div>
                <label class="text-[11px] text-slate-400">วันที่ใหม่
                    <input type="date" data-f="nd" value="${esc(r.nd || '')}" min="${t}" ${max ? `max="${max}"` : ''} class="pp-ctl w-full mt-0.5">
                </label>
            </div>
            <input type="text" data-f="reason" value="${esc(r.reason || '')}" placeholder="${r.nd ? 'ต้องระบุเหตุผล' : 'เหตุผลที่เลื่อน'}" class="pp-ctl w-full mt-2">
            <div class="pp-card-err text-xs pp-err mt-1.5 ${r.err ? '' : 'hidden'}">${esc(r.err)}</div>
            ${r.nd || r.reason ? '<button type="button" data-act="undo" class="text-xs text-slate-500 hover:text-red-600 mt-2"><i class="fas fa-rotate-left mr-1"></i>ยกเลิกการแก้ไข</button>' : ''}
        </div>`;
    }
    function renderCards() {
        const box = $('pp-cards');
        if (!isMobile()) { if (box.childElementCount) box.innerHTML = ''; return; }
        box.innerHTML = PP.rows.map(cardHtml).join('');
    }
    function bindCards() {
        const box = $('pp-cards');
        box.addEventListener('change', e => {
            const inp = e.target.closest('[data-f]');
            if (!inp) return;
            const card = inp.closest('[data-row]'), i = +card.dataset.row, r = PP.rows[i];
            r[inp.dataset.f] = inp.dataset.f === 'nd' ? (inp.value || null) : inp.value;
            touchRow(i);
            const tmp = document.createElement('div');
            tmp.innerHTML = cardHtml(r, i);
            card.replaceWith(tmp.firstElementChild);
        });
        box.addEventListener('click', e => {
            const act = e.target.closest('[data-act]');
            if (!act) return;
            const i = +act.closest('[data-row]').dataset.row;
            if (act.dataset.act === 'hist') showPostponeHistory(PP.rows[i].id);
            if (act.dataset.act === 'undo') undoRow(i);
        });
    }

    // ---------- ประวัติการเลื่อน ----------
    async function showPostponeHistory(planId) {
        ppSwal.fire({ title: 'ประวัติการเลื่อน', html: '<div class="text-sm text-slate-500 py-4">กำลังโหลด...</div>', showConfirmButton: false });
        try {
            const res = await (await fetch(`handle_pm_postpone.php?action=get_history&plan_id=${encodeURIComponent(planId)}`)).json();
            if (!res.success) throw new Error(res.error || 'โหลดไม่สำเร็จ');
            const list = res.data || [];
            const html = list.length ? `<ol class="text-left text-sm max-h-[55vh] overflow-y-auto space-y-2">${list.map((h, i) => `
                <li class="p-3 rounded-lg border border-slate-200">
                    <div class="flex items-center justify-between text-xs text-slate-400"><span>ครั้งที่ ${i + 1}</span><span>${esc(h.created_by || 'ระบบ')} · ${esc(fmt(h.created_at))}</span></div>
                    <div class="mt-1 text-slate-700"><span class="text-slate-400 line-through">${esc(fmt(h.old_date))}</span>
                        <i class="fas fa-arrow-right text-[10px] text-slate-300 mx-1.5"></i><span class="font-medium text-[#006B9F]">${esc(fmt(h.new_date))}</span></div>
                    <div class="mt-1 text-xs text-slate-500">เหตุผล: ${esc(h.reason || '-')}</div>
                </li>`).join('')}</ol>`
                : '<div class="text-sm text-slate-500 py-4">ยังไม่มีประวัติการเลื่อน</div>';
            ppSwal.fire({ title: 'ประวัติการเลื่อน', html, confirmButtonText: 'ปิด', confirmButtonColor: '#006B9F', width: 520 });
        } catch (e) {
            ppSwal.fire('ข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลประวัติได้: ' + e.message, 'error');
        }
    }

    // ---------- บันทึก ----------
    function refreshViews() {
        if (PP.hot) PP.hot.render();
        renderCards();
        updateFooter();
    }
    async function savePostponements() {
        const dirty = dirtyRows();
        if (!dirty.length) return;
        dirty.forEach(r => { r.err = rowError(r, true); });
        const bad = dirty.filter(r => r.err);
        if (bad.length) {
            refreshViews();
            return ppSwal.fire({
                title: `มี ${bad.length} รายการที่ต้องแก้ไข`, icon: 'warning', confirmButtonColor: '#006B9F',
                html: `<ul class="text-left text-sm space-y-1">${bad.slice(0, 6).map(r => `<li><b class="font-medium">${esc(r.machine || '-')}</b> (${esc(fmt(r.d))}) — <span class="text-red-600">${esc(r.err)}</span></li>`).join('')}${bad.length > 6 ? `<li class="text-slate-400">และอีก ${bad.length - 6} รายการ</li>` : ''}</ul>`,
            });
        }
        const ok = await ppSwal.fire({
            title: 'ยืนยันการเลื่อนแผน?', text: `บันทึกการเลื่อน ${dirty.length} รายการ`, icon: 'question',
            showCancelButton: true, confirmButtonText: 'บันทึก', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#006B9F',
        });
        if (!ok.isConfirmed) return;
        const btn = $('pp-save');
        btn.disabled = true;
        try {
            const res = await (await fetch('handle_pm_postpone.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ updates: dirty.map(r => ({ plan_id: r.id, new_date: r.nd, reason: r.reason.trim() })) }),
            })).json();
            if (!res.success) {
                // server ตรวจไม่ผ่าน => แสดงข้อผิดพลาดที่แถว
                (res.errors || []).forEach(er => { const r = PP.rows.find(x => x.id == er.plan_id); if (r) r.err = er.error; });
                refreshViews();
                return ppSwal.fire('บันทึกไม่สำเร็จ', res.error || 'เกิดข้อผิดพลาด', 'error');
            }
            ppSwal.fire({ icon: 'success', title: `เลื่อนแผนแล้ว ${res.saved} รายการ`, timer: 1600, showConfirmButton: false });
            PP.rows.forEach(r => { r.nd = null; r.reason = ''; r.err = ''; });
            load();
        } catch (e) {
            updateFooter();
            ppSwal.fire('ข้อผิดพลาด', 'บันทึกไม่สำเร็จ: ' + e.message, 'error');
        }
    }

    // ---------- เริ่มต้นเมื่อเปิดแท็บครั้งแรก ----------
    // pm.php เรียก fetchPostponeData() ทุกครั้งที่กลับมาที่แท็บ — ถ้ายังมีรายการที่แก้ค้างอยู่ ไม่โหลดทับ
    window.fetchPostponeData = function () {
        if (dirtyRows().length) { refreshViews(); return; }
        load();
    };
    window.showPostponeHistory = showPostponeHistory;
    window.savePostponements = savePostponements;

    pmLazy('postpone', function () {
        if (window.lucide) lucide.createIcons();
        initHot();
        bindCards();

        ['pp-month', 'pp-year', 'pp-f-checksheet', 'pp-f-type', 'pp-f-location', 'pp-f-overdue'].forEach(id => $(id).addEventListener('change', () => applyFilters()));
        let timer;
        $('pp-search').addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => applyFilters(), 300); });
        $('pp-this-month').addEventListener('click', () => {
            const d = new Date();
            $('pp-month').value = d.getMonth() + 1;
            $('pp-year').value = d.getFullYear();
            applyFilters();
        });
        $('pp-filter-btn').addEventListener('click', () => {
            const panel = $('pp-filter-panel');
            const open = panel.classList.toggle('hidden') === false;
            $('pp-filter-btn').setAttribute('aria-expanded', open);
            if (open) loadFilterOptions();
            if (PP.hot) requestAnimationFrame(() => PP.hot.refreshDimensions());
        });
        $('pp-f-clear').addEventListener('click', () => {
            ['pp-f-checksheet', 'pp-f-type', 'pp-f-location'].forEach(id => { $(id).value = ''; });
            $('pp-f-overdue').checked = false;
            applyFilters();
        });
        $('pp-prev').addEventListener('click', async () => { if (await confirmDiscard()) { PP.page--; load(); } });
        $('pp-next').addEventListener('click', async () => { if (await confirmDiscard()) { PP.page++; load(); } });
        $('pp-size').addEventListener('change', async () => {
            if (!(await confirmDiscard())) { $('pp-size').value = PP.size; return; }
            PP.size = +$('pp-size').value;
            PP.page = 1;
            load();
        });
        $('pp-discard').addEventListener('click', async () => {
            if (!(await confirmDiscard())) return;
            PP.rows.forEach(r => { r.nd = null; r.reason = ''; r.err = ''; });
            refreshViews();
        });
        $('pp-save').addEventListener('click', savePostponements);

        // สลับจอใหญ่/มือถือ
        let wasMobile = isMobile();
        window.addEventListener('resize', () => {
            const m = isMobile();
            if (m === wasMobile) return;
            wasMobile = m;
            if (!m) { initHot(); if (PP.hot) { PP.hot.loadData(PP.rows); PP.hot.refreshDimensions(); } }
            renderCards();
        });
        window.addEventListener('beforeunload', e => { if (dirtyRows().length) { e.preventDefault(); e.returnValue = ''; } });

        load();
    });
})();
</script>
