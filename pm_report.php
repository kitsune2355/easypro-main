<?php
// แท็บ "รายงาน PM" (โหมดจัดการแผน) — ตัวช่วยแอดมินออกรายงานส่งมอบ PM ให้หน่วยงาน
//  ① ตัวกรอง → ② เลือกรูปแบบรายงาน (PM History / สรุป / รายเครื่อง) → ③ ส่งออก (พิมพ์ / Excel)
//  ข้อมูลเยอะ: แบ่งพิมพ์เป็นหลายไฟล์ เลือกไฟล์ด้วยตาราง 1..N พร้อมติ๊กว่าไฟล์ไหนพิมพ์แล้ว
?>
<style>
    #tab-report .pmr-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
    #tab-report .pmr-label { display: block; font-size: 11px; font-weight: 600; color: #64748b; margin-bottom: 6px; }
    #tab-report .pmr-input { width: 100%; height: 38px; border: 1px solid #e2e8f0; border-radius: .6rem; padding: 0 10px; font-size: 13px; background: #fff; color: #0f172a; }
    #tab-report .pmr-input:focus { outline: none; border-color: var(--color-secondary); box-shadow: 0 0 0 3px rgba(4,173,255,.15); }
    #tab-report .pmr-chip { height: 30px; padding: 0 11px; border-radius: 999px; border: 1px solid #e2e8f0; font-size: 12px; font-weight: 600; color: #475569; background: #fff; white-space: nowrap; }
    #tab-report .pmr-chip.on { background: var(--color-primary); border-color: var(--color-primary); color: #fff; }
    #tab-report .pmr-step { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #334155; margin: 2px 2px 8px; }
    #tab-report .pmr-step b { width: 22px; height: 22px; border-radius: 999px; background: var(--color-primary); color: #fff; font-size: 12px; display: grid; place-items: center; }
    #tab-report .pmr-kind { text-align: left; border: 1.5px solid #e2e8f0; border-radius: 1rem; background: #fff; padding: 14px; display: flex; gap: 12px; align-items: flex-start; transition: .15s; position: relative; }
    #tab-report .pmr-kind:hover { border-color: #93c5fd; }
    #tab-report .pmr-kind.on { border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(0,107,159,.12); background: #f8fcff; }
    #tab-report .pmr-kind .ic { width: 40px; height: 40px; border-radius: .8rem; display: grid; place-items: center; flex: none; }
    #tab-report .pmr-kind .t { font-weight: 700; color: #0f172a; font-size: 14px; }
    #tab-report .pmr-kind .d { font-size: 12px; color: #64748b; margin-top: 2px; line-height: 1.45; }
    #tab-report .pmr-kind .m { font-size: 12px; font-weight: 600; color: var(--color-primary); margin-top: 6px; }
    #tab-report .pmr-kind .ck { position: absolute; top: 10px; right: 10px; width: 20px; height: 20px; border-radius: 999px; border: 1.5px solid #cbd5e1; display: grid; place-items: center; color: #fff; }
    #tab-report .pmr-kind.on .ck { background: var(--color-primary); border-color: var(--color-primary); }
    #tab-report .pmr-check { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; padding: 7px 8px; border-radius: .5rem; cursor: pointer; }
    #tab-report .pmr-check:hover { background: #f8fafc; }
    #tab-report .pmr-check input { width: 16px; height: 16px; accent-color: var(--color-primary); flex: none; }
    #tab-report .pmr-btn { height: 38px; padding: 0 14px; border-radius: .6rem; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 6px; white-space: nowrap; }
    #tab-report .pmr-btn:disabled { opacity: .45; cursor: not-allowed; }
    #tab-report .pmr-btn-pri { background: var(--color-primary); color: #fff; }
    #tab-report .pmr-btn-pri:hover:not(:disabled) { filter: brightness(1.1); }
    #tab-report .pmr-btn-xl { background: #fff; color: #047857; border: 1px solid #a7f3d0; }
    #tab-report .pmr-btn-xl:hover:not(:disabled) { background: #ecfdf5; }
    #tab-report .pmr-btn-ghost { background: #fff; color: #334155; border: 1px solid #e2e8f0; }
    #tab-report .pmr-btn-ghost:hover:not(:disabled) { background: #f8fafc; }
    #tab-report .pmr-table { width: 100%; font-size: 12.5px; border-collapse: separate; border-spacing: 0; }
    #tab-report .pmr-table th { position: sticky; top: 0; z-index: 1; background: #f8fafc; color: #64748b; font-weight: 600; font-size: 11px; text-align: left; padding: 8px; border-bottom: 1px solid #e2e8f0; }
    #tab-report .pmr-table td { padding: 7px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    #tab-report .pmr-table tr:hover td { background: #f8fafc; }
    #tab-report .pmr-bar { height: 6px; border-radius: 6px; background: #e2e8f0; overflow: hidden; }
    #tab-report .pmr-bar > span { display: block; height: 100%; background: #10b981; }
    #tab-report .pmr-files { display: grid; grid-template-columns: repeat(auto-fill, minmax(34px, 1fr)); gap: 5px; max-height: 172px; overflow-y: auto; padding: 2px; }
    #tab-report .pmr-file { height: 30px; border-radius: .45rem; border: 1px solid #e2e8f0; background: #fff; font-size: 11.5px; font-weight: 600; color: #475569; }
    #tab-report .pmr-file:hover { border-color: var(--color-secondary); }
    #tab-report .pmr-file.done { background: #d1fae5; border-color: #6ee7b7; color: #047857; }
    #tab-report .pmr-file.cur { outline: 2px solid var(--color-primary); outline-offset: 1px; border-color: var(--color-primary); }
    #tab-report .pmr-skel { background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 37%, #f1f5f9 63%); background-size: 400% 100%; animation: pmrSk 1.2s ease infinite; border-radius: .5rem; }
    /* จอคอม: แท็บพอดีความสูงจอ — หน้าไม่เลื่อน ตาราง/กล่องส่งออกเลื่อนภายในตัวเอง */
    @media (min-width: 1024px) {
        #tab-report:not(.hidden) { display: flex; flex-direction: column; height: 100%; min-height: 480px; }
        #tab-report > * { flex: none; }
        #tab-report > #pmr-sec2 { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
        #pmr-sec2 > .pmr-step { flex: none; }
        #pmr-main { flex: 1 1 auto; min-height: 0; grid-template-rows: minmax(0, 1fr); }
        #pmr-left { display: flex; flex-direction: column; min-height: 0; overflow: hidden; }
        #pmr-left > [data-panel]:not(.hidden) { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
        #pmr-left > [data-panel] > * { flex: none; }
        #pmr-left > [data-panel] > .pmr-tw { flex: 1 1 auto; min-height: 120px; max-height: none; }
        #pmr-aside { overflow-y: auto; min-height: 0; }
        #tab-report .pmr-files { max-height: 104px; }
        #tab-report .pmr-kind { padding: 11px 14px; }
    }
    /* จอคอมที่เตี้ย (เช่น 1366x768, 1280x720): ย่อส่วนหัวให้ตารางมีพื้นที่มากขึ้น */
    @media (min-width: 1024px) and (max-height: 880px) {
        #tab-report.space-y-4 > :not([hidden]) ~ :not([hidden]) { margin-top: .6rem; }
        #pmr-filter { padding: .6rem .9rem; }
        #pmr-filter .pmr-sub, #tab-report .pmr-kind .d { display: none !important; }
        #pmr-filter > div:first-child { margin-bottom: .4rem; }
        #tab-report .pmr-kind { padding: 8px 12px; align-items: center; }
        #tab-report .pmr-kind .ic { width: 32px; height: 32px; }
        #tab-report .pmr-kind .m { margin-top: 1px; }
        #tab-report .pmr-step { margin: 0 2px 5px; }
        #pmr-left, #pmr-aside { padding: .75rem .9rem; }
        #pmr-aside.space-y-4 > * + * { margin-top: .75rem; }
        #pmr-secs .pmr-check { padding-top: 4px; padding-bottom: 4px; }
    }
    @keyframes pmrSk { 0% { background-position: 100% 50%; } 100% { background-position: 0 50%; } }
</style>

<div id="tab-report" class="tab-content hidden space-y-4">

    <!-- ตัวกรอง -->
    <div id="pmr-filter" class="pmr-card p-3 sm:p-4">
        <div class="flex flex-wrap items-start justify-between gap-2 mb-3">
            <div>
                <h3 class="text-base font-semibold text-slate-800 flex items-center gap-2"><i data-lucide="file-bar-chart" class="w-5 h-5 text-[var(--color-primary)]"></i> รายงานส่งมอบ PM</h3>
                <p class="pmr-sub text-xs text-slate-500 mt-0.5">เลือกช่วงข้อมูล → เลือกรูปแบบรายงาน → พิมพ์ / Save PDF หรือ Excel เพื่อส่งให้หน่วยงาน</p>
            </div>
            <p id="pmr-agency" class="text-xs text-slate-500 text-right"></p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)_auto] gap-3 items-end">
            <div>
                <span class="pmr-label">ช่วงข้อมูล (ตามวันที่ในแผน PM)</span>
                <div class="flex flex-wrap items-center gap-1.5">
                    <div id="pmr-presets" class="flex flex-wrap gap-1.5">
                        <button type="button" class="pmr-chip" data-p="this_month">เดือนนี้</button>
                        <button type="button" class="pmr-chip" data-p="last_month">เดือนก่อน</button>
                        <button type="button" class="pmr-chip" data-p="quarter">ไตรมาสนี้</button>
                        <button type="button" class="pmr-chip" data-p="year">ปีนี้</button>
                    </div>
                    <div class="flex items-center gap-1.5 flex-1 min-w-[250px]">
                        <input type="date" id="pmr-from" class="pmr-input" aria-label="วันที่เริ่มต้น">
                        <span class="text-slate-400">–</span>
                        <input type="date" id="pmr-to" class="pmr-input" aria-label="วันที่สิ้นสุด">
                    </div>
                </div>
            </div>
            <div><label class="pmr-label" for="pmr-type">ประเภทเครื่อง</label><select id="pmr-type" class="pmr-input"><option value="">ทุกประเภท</option></select></div>
            <div><label class="pmr-label" for="pmr-cs">เช็คชีต</label><select id="pmr-cs" class="pmr-input"><option value="">ทุกเช็คชีต</option></select></div>
            <button type="button" id="pmr-load" class="pmr-btn pmr-btn-pri"><i data-lucide="refresh-cw" class="w-4 h-4"></i> แสดงข้อมูล</button>
        </div>
    </div>


    <!-- เลือกรูปแบบรายงาน -->
    <div>
        <div class="pmr-step"><b>1</b> เลือกรูปแบบรายงาน</div>
        <div id="pmr-kinds" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <button type="button" class="pmr-kind" data-kind="history">
                <span class="ic bg-indigo-50 text-indigo-600"><i data-lucide="history" class="w-5 h-5"></i></span>
                <span class="min-w-0 pr-5"><span class="t block">PM History</span><span class="d block">ประวัติการบำรุงรักษา 1 แถวต่อใบงาน (A4 แนวนอน)</span><span class="m block" data-meta="history"></span></span>
                <span class="ck"><i data-lucide="check" class="w-3 h-3"></i></span>
            </button>
            <button type="button" class="pmr-kind" data-kind="summary">
                <span class="ic bg-sky-50 text-sky-600"><i data-lucide="file-text" class="w-5 h-5"></i></span>
                <span class="min-w-0 pr-5"><span class="t block">รายงานสรุป</span><span class="d block">ภาพรวม % ความสำเร็จ จุดไม่ผ่าน อะไหล่ ผลประเมิน พร้อมช่องลงนาม</span><span class="m block" data-meta="summary"></span></span>
                <span class="ck"><i data-lucide="check" class="w-3 h-3"></i></span>
            </button>
            <button type="button" class="pmr-kind" data-kind="machine">
                <span class="ic bg-emerald-50 text-emerald-600"><i data-lucide="cpu" class="w-5 h-5"></i></span>
                <span class="min-w-0 pr-5"><span class="t block">รายงานรายเครื่อง</span><span class="d block">หน้าข้อมูลเครื่อง และ/หรือ ใบงาน PM — เลือกได้</span><span class="m block" data-meta="machine"></span></span>
                <span class="ck"><i data-lucide="check" class="w-3 h-3"></i></span>
            </button>
        </div>
    </div>

    <!-- รายละเอียด + ส่งออก -->
    <div id="pmr-sec2">
        <div class="pmr-step"><b>2</b> <span id="pmr-step2">ตรวจสอบข้อมูลและส่งออก</span></div>
        <div id="pmr-main" class="pmr-card overflow-hidden grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div id="pmr-left" class="p-4 min-w-0">
                <!-- PM History -->
                <div data-panel="history">
                    <div class="flex items-center justify-between mb-2"><span class="text-sm font-semibold text-slate-700">ตัวอย่างข้อมูล</span><span id="pmr-hcount" class="text-xs text-slate-500"></span></div>
                    <div class="pmr-tw border border-slate-200 rounded-xl overflow-auto">
                        <table class="pmr-table">
                            <thead><tr><th class="w-24">ทำเสร็จ</th><th class="w-40">เลขที่ใบงาน</th><th>เครื่อง / เช็คชีท</th><th class="w-24">ผลตรวจ</th><th class="w-16 text-center">ล่าช้า</th></tr></thead>
                            <tbody id="pmr-hbody"></tbody>
                        </table>
                    </div>
                </div>
                <!-- สรุป -->
                <div data-panel="summary" class="hidden">
                    <span class="text-sm font-semibold text-slate-700">หัวข้อในเอกสาร</span>
                    <div id="pmr-secs" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-x-2 mt-1 mb-3">
                        <label class="pmr-check"><input type="checkbox" value="overview" checked> ภาพรวม % ความสำเร็จ</label>
                        <label class="pmr-check"><input type="checkbox" value="groups" checked> แยกตามประเภทเครื่อง / เช็คชีต</label>
                        <label class="pmr-check"><input type="checkbox" value="machines" checked> ตารางสรุปรายเครื่อง <span class="pmr-n text-slate-400" data-n="machines"></span></label>
                        <label class="pmr-check"><input type="checkbox" value="fails" checked> จุดตรวจที่ไม่ผ่าน <span class="pmr-n text-slate-400" data-n="fails"></span></label>
                        <label class="pmr-check"><input type="checkbox" value="spares" checked> อะไหล่ที่ใช้ <span class="pmr-n text-slate-400" data-n="spares"></span></label>
                        <label class="pmr-check"><input type="checkbox" value="evals" checked> ผลประเมิน PM <span class="pmr-n text-slate-400" data-n="evaluations"></span></label>
                        <label class="pmr-check"><input type="checkbox" value="postpones" checked> ประวัติเลื่อนแผน <span class="pmr-n text-slate-400" data-n="postpones"></span></label>
                        <label class="pmr-check"><input type="checkbox" value="sign" checked> ช่องลงนามส่งมอบ</label>
                    </div>
                    <div class="flex items-center justify-between mb-2"><span class="text-sm font-semibold text-slate-700">ความคืบหน้าตามประเภทเครื่อง</span><span id="pmr-gcount" class="text-xs text-slate-500"></span></div>
                    <div class="pmr-tw border border-slate-200 rounded-xl overflow-auto max-h-[300px]">
                        <table class="pmr-table">
                            <thead><tr><th>ประเภทเครื่อง</th><th class="w-20 text-right">เครื่อง</th><th class="w-28">ทำแล้ว / แผน</th><th class="w-36">ความสำเร็จ</th><th class="w-16 text-right">ค้าง</th></tr></thead>
                            <tbody id="pmr-gbody"></tbody>
                        </table>
                    </div>
                </div>
                <!-- รายเครื่อง -->
                <div data-panel="machine" class="hidden">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mb-2">
                        <span class="text-xs font-semibold text-slate-500">เนื้อหาที่พิมพ์:</span>
                        <label class="pmr-check !px-0"><input type="checkbox" id="pmr-info" checked> หน้าข้อมูลเครื่อง</label>
                        <label class="pmr-check !px-0"><input type="checkbox" id="pmr-attach" checked> ใบงาน PM</label>
                        <label class="pmr-check !px-0"><input type="checkbox" id="pmr-photos"> รูปผลตรวจ</label>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <div class="relative flex-1">
                            <i data-lucide="search" class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input id="pmr-msearch" type="text" class="pmr-input !pl-8" placeholder="ค้นหารหัส / ชื่อเครื่อง / ตำแหน่ง">
                        </div>
                        <button type="button" id="pmr-mall" class="pmr-btn pmr-btn-ghost" title="เลือก/ไม่เลือก ทั้งหมดที่แสดง"><i data-lucide="check-check" class="w-4 h-4"></i><span class="hidden sm:inline">เลือก/ไม่เลือกทั้งหมด</span></button>
                    </div>
                    <div class="pmr-tw border border-slate-200 rounded-xl overflow-auto max-h-[420px]">
                        <table class="pmr-table">
                            <thead><tr><th class="w-8"></th><th>เครื่อง</th><th class="w-32">ประเภท</th><th class="w-28">ทำแล้ว / แผน</th><th class="w-14 text-center">ใบงาน</th></tr></thead>
                            <tbody id="pmr-mbody"></tbody>
                        </table>
                    </div>
                    <p id="pmr-msel" class="text-xs text-slate-500 mt-2"></p>
                </div>
            </div>

            <!-- ส่งออก -->
            <aside id="pmr-aside" class="border-t lg:border-t-0 lg:border-l border-slate-200 bg-slate-50/70 p-4 space-y-4">
                <div>
                    <div class="text-sm font-semibold text-slate-700 mb-1">สิ่งที่จะได้</div>
                    <div id="pmr-outinfo" class="text-sm text-slate-600 leading-relaxed"></div>
                </div>
                <div>
                    <div class="text-sm font-semibold text-slate-700 mb-2 flex items-center gap-1.5"><i data-lucide="printer" class="w-4 h-4"></i> พิมพ์ / Save PDF</div>
                    <div id="pmr-print"></div>
                </div>
                <div class="border-t border-slate-200 pt-4">
                    <button type="button" id="pmr-excel" class="pmr-btn pmr-btn-xl w-full"><i data-lucide="file-spreadsheet" class="w-4 h-4"></i> <span>ดาวน์โหลด Excel</span></button>
                </div>
            </aside>
        </div>
    </div>
</div>

<script>
(function () {
    const API = 'handle_pm_report.php';
    const $ = (id) => document.getElementById(id);
    const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const fmt = (n, d = 0) => Number(n || 0).toLocaleString('th-TH', { minimumFractionDigits: d, maximumFractionDigits: d });
    const ymd = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const TH_M = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    const thDate = (s) => { if (!s) return '-'; const [y, m, d] = s.split('-').map(Number); return `${d} ${TH_M[m - 1]} ${y + 543}`; };
    const icons = () => { if (window.lucide) lucide.createIcons(); };

    // kind: รูปแบบที่เลือก · size: จำนวนต่อไฟล์ของแต่ละแบบ · file/done: ไฟล์ที่เลือก / ไฟล์ที่พิมพ์แล้ว (นับใหม่เมื่อข้อมูลเปลี่ยน)
    const S = { data: null, sel: new Set(), q: '', optBusy: false, kind: 'history',
                size: { history: 500, machine: 30 }, file: 0, done: new Set() };
    try { const k = localStorage.getItem('pmrKind'); if (['history', 'summary', 'machine'].includes(k)) S.kind = k; } catch (e) {}

    function filters() {
        return { from: $('pmr-from').value, to: $('pmr-to').value, type: $('pmr-type').value, cs: $('pmr-cs').value };
    }
    function rangeOf(p) {
        const t = new Date(), y = t.getFullYear(), m = t.getMonth();
        if (p === 'this_month') return [new Date(y, m, 1), new Date(y, m + 1, 0)];
        if (p === 'last_month') return [new Date(y, m - 1, 1), new Date(y, m, 0)];
        if (p === 'quarter') { const q = Math.floor(m / 3) * 3; return [new Date(y, q, 1), new Date(y, q + 3, 0)]; }
        return [new Date(y, 0, 1), new Date(y, 11, 31)];
    }
    function setPreset(p) { const [a, b] = rangeOf(p); $('pmr-from').value = ymd(a); $('pmr-to').value = ymd(b); markPreset(); }
    function markPreset() {
        const f = $('pmr-from').value, t = $('pmr-to').value;
        document.querySelectorAll('#pmr-presets .pmr-chip').forEach((b) => { const [x, y] = rangeOf(b.dataset.p); b.classList.toggle('on', ymd(x) === f && ymd(y) === t); });
    }

    async function loadOptions() {
        try {
            const r = await (await fetch(`${API}?action=options`)).json();
            if (!r.success) return;
            $('pmr-type').innerHTML = '<option value="">ทุกประเภท</option>' + r.types.map((t) => `<option value="${esc(t.id)}">${esc(t.name)}</option>`).join('');
            $('pmr-cs').innerHTML = '<option value="">ทุกเช็คชีต</option>' + r.checksheets.map((c) => `<option value="${esc(c.id)}">${esc(c.name)}${c.doc_no ? ' (' + esc(c.doc_no) + ')' : ''}</option>`).join('');
            S.optBusy = true;   // อัปเดตปุ่ม search-select โดยไม่โหลดข้อมูลซ้ำ
            [$('pmr-type'), $('pmr-cs')].forEach((s) => s.dispatchEvent(new Event('change')));
            S.optBusy = false;
        } catch (e) { console.error(e); }
    }

    function skeleton() {
        ['pmr-hbody', 'pmr-gbody', 'pmr-mbody'].forEach((id) => { $(id).innerHTML = '<tr><td colspan="5"><div class="pmr-skel h-32 my-2"></div></td></tr>'; });
        $('pmr-print').innerHTML = '<div class="pmr-skel h-24"></div>';
        $('pmr-outinfo').innerHTML = '<div class="pmr-skel h-10"></div>';
    }

    async function load() {
        const f = filters();
        if (!f.from || !f.to) return;
        skeleton();
        $('pmr-load').disabled = true;
        try {
            const r = await (await fetch(`${API}?action=preview&${new URLSearchParams(f)}`)).json();
            if (!r.success) throw new Error(r.error || 'โหลดข้อมูลไม่สำเร็จ');
            S.data = r;
            S.sel = new Set(r.machines.map((m) => m.id));
            resetFiles();
            render();
        } catch (e) {
            $('pmr-outinfo').innerHTML = `<div class="text-sm text-rose-600 bg-rose-50 border border-rose-100 rounded-xl p-3">${esc(e.message)}</div>`;
            $('pmr-print').innerHTML = '';
        } finally {
            $('pmr-load').disabled = false;
        }
    }

    function render() {
        const r = S.data;
        $('pmr-agency').innerHTML = `<span class="font-semibold text-slate-700">${esc(r.agency.ag_contract || '-')}</span><br>${thDate(r.filters.from)} – ${thDate(r.filters.to)}`;
        document.querySelector('[data-meta="history"]').textContent = `${fmt(r.history_count)} ใบงาน`;
        document.querySelector('[data-meta="summary"]').textContent = `${fmt(r.machines.length)} เครื่อง · ~${fmt(summaryPages())} หน้า`;
        document.querySelector('[data-meta="machine"]').textContent = `${fmt(r.machines.length)} เครื่อง`;
        document.querySelectorAll('#pmr-secs .pmr-n').forEach((s) => { s.textContent = `(${fmt(s.dataset.n === 'machines' ? r.machines.length : r.counts[s.dataset.n])})`; });
        renderHistory();
        renderGroups();
        renderMachines();
        applyKind();
    }

    // ---------- รูปแบบรายงาน ----------
    function applyKind() {
        document.querySelectorAll('#pmr-kinds .pmr-kind').forEach((b) => b.classList.toggle('on', b.dataset.kind === S.kind));
        document.querySelectorAll('#tab-report [data-panel]').forEach((p) => p.classList.toggle('hidden', p.dataset.panel !== S.kind));
        $('pmr-step2').textContent = { history: 'ตรวจสอบข้อมูล PM History และส่งออก', summary: 'เลือกหัวข้อรายงานสรุปและส่งออก', machine: 'เลือกเครื่องและส่งออกรายงานรายเครื่อง' }[S.kind];
        $('pmr-excel').querySelector('span').textContent = S.kind === 'history' ? 'ดาวน์โหลด Excel (PM History)' : 'ดาวน์โหลด Excel (ข้อมูลทั้งหมด 8 ชีต)';
        renderOutput();
    }

    function renderHistory() {
        const r = S.data, n = r.history_count;
        $('pmr-hbody').innerHTML = n ? r.history_sample.map((h) => `<tr>
            <td class="text-xs"><div class="font-semibold">${esc(h.done_time || '-')}</div><div class="text-slate-400">${thDate(h.done_date)}</div></td>
            <td class="text-xs">${esc(h.doc)}<div class="${h.late ? 'text-amber-600' : 'text-emerald-600'}">${h.late ? 'ล่าช้ากว่าแผน' : 'ตามแผน'}</div></td>
            <td class="text-xs"><div class="font-semibold text-slate-700">${esc(h.code || '-')} <span class="font-normal">${esc(h.machine)}</span></div><div class="text-slate-400 truncate max-w-[340px]">${esc(h.checksheet || '')}</div></td>
            <td class="text-xs">ผ่าน ${h.pass}${h.fail ? ` · <span class="text-rose-600 font-semibold">ไม่ผ่าน ${h.fail}</span>` : ''}<div class="text-slate-400">${h.pass_pct !== null ? h.pass_pct + ' %' : '-'}</div></td>
            <td class="text-xs text-center ${h.delay ? 'text-amber-600 font-semibold' : ''}">${h.delay} วัน</td></tr>`).join('')
            : '<tr><td colspan="5" class="text-center text-slate-400 py-8">ยังไม่มีใบงาน PM ที่บันทึกแล้วในช่วงนี้</td></tr>';
        $('pmr-hcount').textContent = n > r.history_sample.length ? `แสดง ${r.history_sample.length} แถวแรก จาก ${fmt(n)} ใบงาน` : (n ? `${fmt(n)} ใบงาน` : '');
    }

    function renderGroups() {
        const g = S.data.by_type;
        $('pmr-gcount').textContent = g.length ? `${fmt(g.length)} ประเภท` : '';
        $('pmr-gbody').innerHTML = g.length ? g.map((x) => `<tr>
            <td class="text-xs font-semibold text-slate-700">${esc(x.name)}</td><td class="text-xs text-right">${fmt(x.machines)}</td>
            <td class="text-xs">${fmt(x.done)} / ${fmt(x.planned)}</td>
            <td><div class="flex items-center gap-2"><div class="pmr-bar flex-1"><span style="width:${x.rate}%"></span></div><span class="text-xs w-10 text-right">${x.rate}%</span></div></td>
            <td class="text-xs text-right ${x.overdue ? 'text-rose-600 font-semibold' : 'text-slate-400'}">${fmt(x.overdue)}</td></tr>`).join('')
            : '<tr><td colspan="5" class="text-center text-slate-400 py-8">ไม่มีแผน PM ในช่วงนี้</td></tr>';
    }

    function shownMachines() {
        const q = S.q.trim().toLowerCase();
        return S.data.machines.filter((m) => !q || `${m.code} ${m.name} ${m.location} ${m.type}`.toLowerCase().includes(q));
    }
    function renderMachines() {
        const list = shownMachines();
        $('pmr-mbody').innerHTML = list.length ? list.map((m) => {
            const rate = m.planned ? Math.round(m.done * 100 / m.planned) : 0;
            return `<tr>
              <td><input type="checkbox" class="w-4 h-4 accent-[var(--color-primary)]" data-mid="${esc(m.id)}" ${S.sel.has(m.id) ? 'checked' : ''}></td>
              <td><div class="font-semibold text-slate-800">${esc(m.code || '-')} <span class="font-normal">${esc(m.name)}</span></div><div class="text-[11px] text-slate-400 truncate max-w-[340px]">${esc(m.location || '')}</div></td>
              <td class="text-xs text-slate-600">${esc(m.type || '-')}</td>
              <td><div class="text-xs">${m.done}/${m.planned}${m.overdue ? ` <span class="text-rose-600">· ค้าง ${m.overdue}</span>` : ''}</div><div class="pmr-bar mt-1 w-20"><span style="width:${rate}%"></span></div></td>
              <td class="text-center text-xs">${m.wrs}</td></tr>`;
        }).join('') : `<tr><td colspan="5" class="text-center text-slate-400 py-8">${S.data.machines.length ? 'ไม่พบเครื่องที่ค้นหา' : 'ไม่มีแผน PM ในช่วงนี้'}</td></tr>`;
        updateMachineSel();
    }
    function selectedMachines() { return S.data ? S.data.machines.filter((m) => S.sel.has(m.id)) : []; }
    function machinePages(ms) {
        // หน้าข้อมูลเครื่อง 1 หน้า/เครื่อง + ใบงาน 1 หน้า/ฉบับ + หน้ารูปผลตรวจ (ประมาณ 4 รูป/หน้า)
        const info = $('pmr-info').checked, attach = $('pmr-attach').checked, photos = attach && $('pmr-photos').checked;
        return ms.reduce((s, m) => s + (info ? 1 : 0) + (attach ? m.wrs + (photos ? Math.ceil(m.photos / 4) : 0) : 0), 0);
    }
    function summaryPages() { return 2 + Math.ceil((S.data ? S.data.machines.length : 0) / 22); }
    function updateMachineSel() {
        const ms = selectedMachines();
        $('pmr-msel').textContent = S.data ? `เลือก ${fmt(ms.length)} จาก ${fmt(S.data.machines.length)} เครื่อง` : '';
    }

    // ---------- ส่งออก: แบ่งไฟล์ ----------
    function resetFiles() { S.file = 0; S.done = new Set(); }
    function units() {   // รายการที่จะพิมพ์ตามรูปแบบ (ใบงาน / เครื่อง)
        if (S.kind === 'history') return { n: S.data.history_count, unit: 'ใบงาน', perPage: 14 };
        if (S.kind === 'machine') return { n: selectedMachines().length, unit: 'เครื่อง' };
        return { n: 1, unit: '' };
    }
    function filesInfo() {
        const u = units(), size = S.size[S.kind] || 0;
        const per = size > 0 ? size : Math.max(1, u.n);
        const count = u.n ? Math.ceil(u.n / per) : 0;
        return { ...u, size, per, count };
    }
    function fileRange(i, info) {
        const off = i * info.per, lim = Math.min(info.per, info.n - off);
        let pages, label = `${info.unit} ${fmt(off + 1)}–${fmt(off + lim)}`;
        if (S.kind === 'history') pages = Math.ceil(lim / info.perPage);
        else {
            const part = selectedMachines().slice(off, off + lim);
            pages = machinePages(part);
            if (part.length) label += ` · ${part[0].code || part[0].name} → ${part[part.length - 1].code || part[part.length - 1].name}`;
        }
        return { off, lim, pages, label };
    }

    function renderOutput() {
        if (!S.data) return;
        const r = S.data;
        // สรุปสิ่งที่จะได้
        if (S.kind === 'history') {
            $('pmr-outinfo').innerHTML = r.history_count ? `PM History <b>${fmt(r.history_count)}</b> ใบงาน<br><span class="text-xs text-slate-500">A4 แนวนอน ประมาณ ${fmt(Math.ceil(r.history_count / 14))} หน้า</span>` : '<span class="text-slate-400">ไม่มีใบงานที่บันทึกแล้วในช่วงนี้</span>';
        } else if (S.kind === 'summary') {
            const n = document.querySelectorAll('#pmr-secs input:checked').length;
            $('pmr-outinfo').innerHTML = `รายงานสรุป <b>${n}</b> หัวข้อ · ${fmt(r.machines.length)} เครื่อง<br><span class="text-xs text-slate-500">A4 แนวตั้ง ประมาณ ${fmt(summaryPages())} หน้า</span>`;
        } else {
            const ms = selectedMachines();
            $('pmr-outinfo').innerHTML = ms.length ? `<b>${fmt(ms.length)}</b> เครื่อง: ${[$('pmr-info').checked ? 'หน้าข้อมูลเครื่อง' : '', $('pmr-attach').checked ? `ใบงาน PM ${fmt(ms.reduce((s, m) => s + m.wrs, 0))} ฉบับ` : '', $('pmr-attach').checked && $('pmr-photos').checked ? 'รูปผลตรวจ' : ''].filter(Boolean).join(' + ')}<br><span class="text-xs text-slate-500">A4 แนวตั้ง ประมาณ ${fmt(machinePages(ms))} หน้า</span>` : '<span class="text-slate-400">ยังไม่ได้เลือกเครื่อง</span>';
        }
        const hasData = S.kind === 'history' ? r.history_count > 0 : (S.kind === 'machine' ? selectedMachines().length > 0 : r.machines.length > 0);
        $('pmr-excel').disabled = S.kind === 'history' ? !r.history_count : !r.machines.length;

        if (!hasData) { $('pmr-print').innerHTML = '<p class="text-sm text-slate-400">ไม่มีข้อมูลให้พิมพ์</p>'; return; }
        if (S.kind === 'summary') {
            $('pmr-print').innerHTML = `<button type="button" class="pmr-btn pmr-btn-pri w-full" data-act="print-summary"><i data-lucide="printer" class="w-4 h-4"></i> เปิดหน้าพิมพ์รายงานสรุป</button>`;
            icons(); return;
        }
        const info = filesInfo();
        if (S.file >= info.count) S.file = 0;
        const sizes = S.kind === 'history' ? [200, 500, 1000] : [10, 20, 30, 50, 100];
        const sizeSel = `<label class="flex items-center justify-between gap-2 text-xs text-slate-600 mb-2.5">แบ่งไฟล์ละ
            <select class="pmr-input !h-8 !w-auto !text-xs" data-act="size">${sizes.map((v) => `<option value="${v}" ${v === info.size ? 'selected' : ''}>${fmt(v)} ${info.unit}</option>`).join('')}<option value="0" ${info.size === 0 ? 'selected' : ''}>ไม่แบ่ง (ไฟล์เดียว)</option></select></label>`;
        const cur = fileRange(S.file, info);
        if (info.count <= 1) {
            $('pmr-print').innerHTML = sizeSel + `<button type="button" class="pmr-btn pmr-btn-pri w-full" data-act="print" data-i="0"><i data-lucide="printer" class="w-4 h-4"></i> เปิดหน้าพิมพ์ (~${fmt(cur.pages)} หน้า)</button>
                ${S.done.has(0) ? '<p class="text-xs text-emerald-600 mt-2 flex items-center gap-1"><i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> เปิดพิมพ์แล้ว</p>' : ''}`;
            icons(); return;
        }
        const pct = Math.round(S.done.size * 100 / info.count);
        $('pmr-print').innerHTML = sizeSel + `
            <div class="rounded-xl bg-white border border-slate-200 p-3">
                <div class="flex items-baseline justify-between gap-2">
                    <div class="text-sm font-semibold text-slate-800">ไฟล์ที่ ${S.file + 1} <span class="font-normal text-slate-400">/ ${fmt(info.count)}</span></div>
                    ${S.done.has(S.file) ? '<span class="text-xs text-emerald-600 font-semibold">พิมพ์แล้ว</span>' : ''}
                </div>
                <div class="text-xs text-slate-500 mt-0.5 truncate" title="${esc(cur.label)}">${esc(cur.label)} · ~${fmt(cur.pages)} หน้า</div>
                <div class="flex gap-2 mt-2.5">
                    <button type="button" class="pmr-btn pmr-btn-ghost !px-2.5" data-act="prev" ${S.file === 0 ? 'disabled' : ''} title="ไฟล์ก่อนหน้า"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                    <button type="button" class="pmr-btn pmr-btn-pri flex-1" data-act="print" data-i="${S.file}"><i data-lucide="printer" class="w-4 h-4"></i> พิมพ์ไฟล์ที่ ${S.file + 1}</button>
                    <button type="button" class="pmr-btn pmr-btn-ghost !px-2.5" data-act="next" ${S.file >= info.count - 1 ? 'disabled' : ''} title="ไฟล์ถัดไป"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                </div>
                <div class="flex justify-between text-[11px] text-slate-500 mt-2.5 mb-1"><span>ความคืบหน้า</span><span>พิมพ์แล้ว ${fmt(S.done.size)} / ${fmt(info.count)} ไฟล์</span></div>
                <div class="pmr-bar"><span style="width:${pct}%"></span></div>
            </div>
            <div class="flex items-center justify-between text-xs text-slate-500 mt-3 mb-1.5"><span>เลือกไฟล์ (${fmt(info.count)} ไฟล์)</span><span><span class="inline-block w-2.5 h-2.5 rounded-sm bg-emerald-200 border border-emerald-300 align-middle"></span> พิมพ์แล้ว</span></div>
            <div class="pmr-files">${Array.from({ length: info.count }, (_, i) => `<button type="button" class="pmr-file ${S.done.has(i) ? 'done' : ''} ${i === S.file ? 'cur' : ''}" data-act="pick" data-i="${i}" title="ไฟล์ที่ ${i + 1}">${i + 1}</button>`).join('')}</div>
            <p class="text-[11px] text-amber-700 mt-2 flex gap-1.5"><i data-lucide="layers" class="w-3.5 h-3.5 shrink-0 mt-px"></i><span>ข้อมูลเยอะ แบ่งเป็น ${fmt(info.count)} ไฟล์ (ไฟล์ละ ${fmt(info.per)} ${info.unit}) ให้เปิดและพิมพ์ได้เร็ว — กดพิมพ์ทีละไฟล์จนครบ</span></p>`;
        icons();
    }

    // เปิดหน้าพิมพ์ด้วย POST (รายการเครื่องอาจยาวเกิน URL)
    function openPrint(params, action = 'pm_report_print.php') {
        const form = document.createElement('form');
        form.method = 'POST'; form.action = action; form.target = '_blank';
        Object.entries(params).forEach(([k, v]) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = k; i.value = v; form.appendChild(i); });
        document.body.appendChild(form); form.submit(); form.remove();
    }
    function printFile(i) {
        const info = filesInfo(), c = fileRange(i, info);
        const lim = info.count > 1 || info.size > 0 ? c.lim : 0;
        if (S.kind === 'history') {
            openPrint({ ...filters(), off: c.off, lim }, 'pm_report_history_print.php');
        } else {
            const ms = selectedMachines(), all = ms.length === S.data.machines.length;
            openPrint({ kind: 'machine', ...filters(), m: all ? '' : ms.map((m) => m.id).join(','), off: c.off, lim,
                info: $('pmr-info').checked ? 1 : 0, attach: $('pmr-attach').checked ? 1 : '', photos: $('pmr-attach').checked && $('pmr-photos').checked ? 1 : '' });
        }
        S.done.add(i);
        // ไปไฟล์ถัดไปที่ยังไม่พิมพ์
        for (let j = 1; j <= info.count; j++) { const k = (i + j) % info.count; if (!S.done.has(k)) { S.file = k; break; } }
        renderOutput();
    }
    function printSummary() {
        const sec = [...document.querySelectorAll('#pmr-secs input:checked')].map((x) => x.value).join(',');
        if (!sec) { Swal.fire({ icon: 'info', title: 'เลือกหัวข้ออย่างน้อย 1 หัวข้อ' }); return; }
        openPrint({ kind: 'summary', ...filters(), sec });
    }

    // ---------- Excel ----------
    function loadExcelJS() {
        if (window.ExcelJS) return Promise.resolve();
        return new Promise((ok, no) => { const s = document.createElement('script'); s.src = 'https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js'; s.onload = ok; s.onerror = () => no(new Error('โหลด ExcelJS ไม่สำเร็จ')); document.head.appendChild(s); });
    }
    function addSheet(wb, name, cols, rows) {
        const ws = wb.addWorksheet(name, { views: [{ state: 'frozen', ySplit: 1 }] });
        ws.columns = cols.map((c) => ({ header: c[0], key: c[1], width: c[2] || 14, style: c[3] ? { numFmt: c[3] } : {} }));
        rows.forEach((r) => ws.addRow(r));
        const h = ws.getRow(1);
        h.font = { bold: true, color: { argb: 'FFFFFFFF' } }; h.alignment = { vertical: 'middle', wrapText: true }; h.height = 22;
        h.eachCell((c) => { c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF006B9F' } }; });
        if (rows.length) ws.autoFilter = { from: { row: 1, column: 1 }, to: { row: 1, column: cols.length } };
        return ws;
    }
    async function exportExcel() {
        const btn = $('pmr-excel'); btn.disabled = true;
        const old = btn.innerHTML; btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> กำลังสร้างไฟล์...'; if (window.lucide) lucide.createIcons();
        try {
            await loadExcelJS();
            const r = await (await fetch(`${API}?action=data&${new URLSearchParams(filters())}`)).json();
            if (!r.success) throw new Error(r.error || 'โหลดข้อมูลไม่สำเร็จ');
            const k = r.kpi, wb = new ExcelJS.Workbook();
            const sum = wb.addWorksheet('สรุป');
            sum.columns = [{ width: 34 }, { width: 18 }, { width: 12 }, { width: 12 }, { width: 12 }, { width: 12 }, { width: 12 }];
            sum.addRow(['รายงานสรุปผลการบำรุงรักษาเชิงป้องกัน (PM)']).font = { bold: true, size: 14 };
            sum.addRow([`หน่วยงาน: ${r.agency.ag_contract || ''}`]);
            sum.addRow([`ช่วงข้อมูล: ${thDate(r.filters.from)} – ${thDate(r.filters.to)}`]);
            sum.addRow([]);
            [['แผน PM ทั้งหมด (รอบ)', k.planned], ['จำนวนเครื่อง', k.machines], ['ดำเนินการแล้ว', k.done], ['% ความสำเร็จ', k.rate / 100], ['ทำตรงตามแผน', k.ontime], ['ทำแล้วล่าช้า', k.late],
             ['ค้าง / เกินกำหนด', k.overdue], ['ยังไม่ถึงกำหนด', k.upcoming], ['จุดตรวจผ่าน', k.pass], ['จุดตรวจไม่ผ่าน', k.fail], ['N/A', k.na], ['% จุดตรวจผ่าน', k.pass_rate / 100],
             ['คะแนนประเมินเฉลี่ย', k.score_avg ?? '-'], ['จำนวนครั้งเลื่อนแผน', k.postponed], ['มูลค่าอะไหล่ (บาท)', k.spare_value]].forEach(([l, v]) => {
                const row = sum.addRow([l, v]); row.getCell(1).font = { bold: true };
                if (/^%/.test(l)) row.getCell(2).numFmt = '0.0%'; else if (/บาท/.test(l)) row.getCell(2).numFmt = '#,##0.00';
            });
            [['สรุปตามประเภทเครื่อง', r.by_type], ['สรุปตามเช็คชีต', r.by_checksheet]].forEach(([t, rows]) => {
                sum.addRow([]);
                const hr = sum.addRow([t, 'เครื่อง', 'แผน', 'ทำแล้ว', 'ค้าง', '% สำเร็จ', 'ไม่ผ่าน']);
                hr.font = { bold: true, color: { argb: 'FFFFFFFF' } }; hr.eachCell((c) => { c.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF006B9F' } }; });
                rows.forEach((g) => { const row = sum.addRow([g.name, g.machines, g.planned, g.done, g.overdue, g.rate / 100, g.fail]); row.getCell(6).numFmt = '0.0%'; });
            });
            addSheet(wb, 'รายเครื่อง', [['รหัสเครื่อง', 'code', 14], ['ชื่อเครื่อง', 'name', 32], ['ประเภท', 'type', 18], ['ตำแหน่ง', 'location', 40], ['รุ่น', 'model', 16], ['S/N', 'sn', 16], ['เช็คชีต', 'cs', 34],
                ['แผน', 'planned', 8], ['ทำแล้ว', 'done', 8], ['ค้าง', 'overdue', 8], ['% สำเร็จ', 'rate', 10, '0.0%'], ['ครั้งก่อนหน้า', 'prev', 14], ['ทำล่าสุด', 'last', 14], ['จุดไม่ผ่าน', 'fail', 10]],
                r.machines.map((m) => ({ ...m, cs: (m.checksheets || []).join(', '), rate: m.planned ? m.done / m.planned : 0, prev: m.prev || '', last: m.last || '' })));
            addSheet(wb, 'รายการ PM', [['รหัสเครื่อง', 'code', 14], ['ชื่อเครื่อง', 'name', 30], ['ประเภท', 'type', 16], ['ตำแหน่ง', 'location', 36], ['เช็คชีต', 'checksheet', 32], ['เลขเอกสาร', 'doc_no', 16], ['ความถี่', 'freq', 14],
                ['วันที่ตามแผน', 'plan_date', 12], ['วันที่ทำจริง', 'actual_date', 12], ['สถานะ', 'state', 16], ['ผู้ตรวจ', 'inspector', 22], ['ผ่าน', 'pass', 7], ['ไม่ผ่าน', 'fail', 8], ['N/A', 'na', 6], ['คะแนนประเมิน', 'score', 12], ['หมายเหตุ', 'remarks', 36]], r.events);
            addSheet(wb, 'จุดไม่ผ่าน', [['วันที่', 'date', 12], ['รหัสเครื่อง', 'code', 14], ['ชื่อเครื่อง', 'name', 30], ['จุดตรวจ', 'check_point', 40], ['มาตรฐาน', 'standard', 14], ['ค่าที่วัด', 'actual', 12], ['หมายเหตุ', 'remarks', 40]], r.fails);
            addSheet(wb, 'สรุปอะไหล่', [['รหัสสินค้า', 'item_code', 16], ['รายการ', 'item', 40], ['ครั้ง', 'times', 8], ['จำนวน', 'qty', 10, '#,##0.00'], ['หน่วย', 'unit', 10], ['มูลค่า', 'total', 14, '#,##0.00']], r.spare_summary);
            addSheet(wb, 'อะไหล่ (รายครั้ง)', [['วันที่', 'date', 12], ['รหัสเครื่อง', 'code', 14], ['เครื่อง', 'machine', 30], ['รหัสสินค้า', 'item_code', 16], ['รายการ', 'item', 36], ['จำนวน', 'qty', 10, '#,##0.00'], ['หน่วย', 'unit', 10], ['ราคา', 'price', 12, '#,##0.00'], ['มูลค่า', 'total', 14, '#,##0.00']], r.spares);
            addSheet(wb, 'ผลประเมิน', [['วันที่ประเมิน', 'date', 12], ['วันที่ทำ PM', 'pm_date', 12], ['รหัสเครื่อง', 'code', 14], ['ชื่อเครื่อง', 'name', 30], ['ผู้ประเมิน', 'evaluator', 22], ['คะแนน', 'score', 10, '0.00'], ['ความคิดเห็น', 'comments', 44]], r.evaluations);
            addSheet(wb, 'เลื่อนแผน', [['รหัสเครื่อง', 'code', 14], ['ชื่อเครื่อง', 'name', 30], ['วันเดิม', 'old_date', 12], ['วันใหม่', 'new_date', 12], ['เหตุผล', 'reason', 40], ['โดย', 'by', 18]], r.postpones);
            const buf = await wb.xlsx.writeBuffer();
            const a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob([buf], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }));
            a.download = `รายงานPM_${(r.agency.ag_contract || '').replace(/[\\/:*?"<>|]/g, '')}_${r.filters.from}_${r.filters.to}.xlsx`;
            document.body.appendChild(a); a.click(); a.remove();
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Export ไม่สำเร็จ', text: e.message });
        } finally {
            btn.innerHTML = old; btn.disabled = false; if (window.lucide) lucide.createIcons();
        }
    }


    async function exportHistoryExcel() {
        const btn = $('pmr-excel'); btn.disabled = true;
        const old = btn.innerHTML; btn.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> กำลังสร้างไฟล์...'; if (window.lucide) lucide.createIcons();
        try {
            await loadExcelJS();
            const r = await (await fetch(`${API}?action=history&${new URLSearchParams(filters())}`)).json();
            if (!r.success) throw new Error(r.error || 'โหลดข้อมูลไม่สำเร็จ');
            const wb = new ExcelJS.Workbook();
            const dmy = (d) => (d ? d.split('-').reverse().join('/') : '');
            const rows = r.rows.map((h, i) => ({ ...h, no: i + 1, plan: dmy(h.plan_date), done: `${dmy(h.done_date)} ${h.done_time}`.trim(),
                status: h.late ? `ล่าช้า ${h.delay} วัน` : 'ตามแผน',
                result: `ผ่าน ${h.pass}${h.fail ? ` / ไม่ผ่าน ${h.fail}` : ''}${h.na ? ` / N/A ${h.na}` : ''}`,
                pct: h.pass_pct !== null ? h.pass_pct / 100 : null }));
            const ws = addSheet(wb, 'PM History', [['#', 'no', 6], ['เลขที่ใบงาน', 'doc', 22], ['วันที่ตามแผน', 'plan', 12], ['วันที่เข้าทำจริง', 'done', 17], ['สถานะ', 'status', 13],
                ['รหัสเครื่อง', 'code', 14], ['ชื่อเครื่อง', 'name', 30], ['ประเภท', 'type', 18], ['S/N', 'serial', 16], ['ตำแหน่งที่ตั้ง', 'location', 34],
                ['เช็คชีต', 'checksheet', 34], ['ความถี่', 'freq', 18], ['ผู้ดำเนินการ', 'inspector', 22], ['ผลการตรวจ', 'result', 22], ['% ผ่าน', 'pct', 9, '0%'],
                ['อะไหล่ (บาท)', 'spare', 13, '#,##0.00'], ['ผลการปฏิบัติงาน / ข้อเสนอแนะ', 'remarks', 40]], rows);
            ws.views = [{ state: 'frozen', xSplit: 2, ySplit: 1 }];
            ws.eachRow((row, i) => { if (i > 1) row.alignment = { vertical: 'top', wrapText: true }; });
            const buf = await wb.xlsx.writeBuffer();
            const a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob([buf], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }));
            a.download = `PM History_${(r.agency.ag_contract || '').replace(/[\\/:*?"<>|]/g, '')}_${r.filters.from}_${r.filters.to}.xlsx`;
            document.body.appendChild(a); a.click(); a.remove();
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Export ไม่สำเร็จ', text: e.message });
        } finally {
            btn.innerHTML = old; btn.disabled = false; if (window.lucide) lucide.createIcons();
        }
    }



    pmLazy('report', function () {
        setPreset('last_month');
        loadOptions();
        load();
        $('pmr-presets').addEventListener('click', (e) => { const b = e.target.closest('[data-p]'); if (b) { setPreset(b.dataset.p); load(); } });
        ['pmr-from', 'pmr-to'].forEach((id) => $(id).addEventListener('change', markPreset));
        $('pmr-load').addEventListener('click', load);
        ['pmr-type', 'pmr-cs'].forEach((id) => $(id).addEventListener('change', () => { if (!S.optBusy && S.data) load(); }));
        $('pmr-kinds').addEventListener('click', (e) => {
            const b = e.target.closest('[data-kind]'); if (!b) return;
            S.kind = b.dataset.kind; S.file = 0; S.done = new Set();
            try { localStorage.setItem('pmrKind', S.kind); } catch (err) {}
            if (S.data) applyKind(); else document.querySelectorAll('#pmr-kinds .pmr-kind').forEach((x) => x.classList.toggle('on', x === b));
        });
        $('pmr-msearch').addEventListener('input', (e) => { S.q = e.target.value; renderMachines(); });
        $('pmr-mbody').addEventListener('change', (e) => { const c = e.target.closest('[data-mid]'); if (!c) return; c.checked ? S.sel.add(c.dataset.mid) : S.sel.delete(c.dataset.mid); updateMachineSel(); resetFiles(); renderOutput(); });
        $('pmr-mall').addEventListener('click', () => { const list = shownMachines(); const allOn = list.every((m) => S.sel.has(m.id)); list.forEach((m) => allOn ? S.sel.delete(m.id) : S.sel.add(m.id)); renderMachines(); resetFiles(); renderOutput(); });
        // ต้องเลือกอย่างน้อย 1 อย่าง (หน้าข้อมูลเครื่อง หรือ ใบงาน PM) · รูปผลตรวจแนบได้เมื่อเลือกใบงาน
        const syncMachineOpts = (e) => {
            if (!$('pmr-info').checked && !$('pmr-attach').checked) (e && e.target.id === 'pmr-info' ? $('pmr-attach') : $('pmr-info')).checked = true;
            $('pmr-photos').disabled = !$('pmr-attach').checked;
            $('pmr-photos').closest('label').classList.toggle('opacity-50', !$('pmr-attach').checked);
            resetFiles(); renderOutput();
        };
        ['pmr-info', 'pmr-attach'].forEach((id) => $(id).addEventListener('change', syncMachineOpts));
        $('pmr-photos').addEventListener('change', renderOutput);
        $('pmr-secs').addEventListener('change', renderOutput);
        $('pmr-print').addEventListener('click', (e) => {
            const b = e.target.closest('[data-act]'); if (!b || b.disabled) return;
            const act = b.dataset.act, info = filesInfo();
            if (act === 'print') printFile(Number(b.dataset.i));
            else if (act === 'print-summary') printSummary();
            else if (act === 'pick') { S.file = Number(b.dataset.i); renderOutput(); }
            else if (act === 'prev') { S.file = Math.max(0, S.file - 1); renderOutput(); }
            else if (act === 'next') { S.file = Math.min(info.count - 1, S.file + 1); renderOutput(); }
        });
        $('pmr-print').addEventListener('change', (e) => { if (e.target.dataset.act === 'size') { S.size[S.kind] = Number(e.target.value); resetFiles(); renderOutput(); } });
        $('pmr-excel').addEventListener('click', () => (S.kind === 'history' ? exportHistoryExcel() : exportExcel()));
        icons();
    });
})();
</script>
