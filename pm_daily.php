<?php
//pm_daily.php — แท็บ "งานที่ต้องทำ" (โหมดหน้างาน): ช่างดูงาน PM วันนี้/7 วัน + งานค้าง แล้วเปิดใบงานได้ทันที (handle_pm_daily.php summary/items)
//  รองรับงานจำนวนมาก: โหลดเฉพาะตัวเลขสรุปของกลุ่มก่อน แล้วโหลดเครื่องทีละ 30 รายการเมื่อเปิดกลุ่ม
?>
<style>
    .pmd-seg { display: inline-flex; background: #f1f5f9; border-radius: .7rem; padding: 3px; }
    .pmd-seg button { padding: .4rem .85rem; border-radius: .55rem; font-size: 13px; font-weight: 600; color: #64748b; white-space: nowrap; }
    .pmd-seg button.on { background: #fff; color: #006B9F; box-shadow: 0 1px 2px rgba(15,23,42,.08); }
    .pmd-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .8rem; border-radius: 9999px; font-size: 12.5px; font-weight: 600; border: 1px solid transparent; cursor: pointer; white-space: nowrap; }
    .pmd-chip.is-on { box-shadow: 0 0 0 2px currentColor inset; }
    .pmd-sel { border: 1px solid #e2e8f0; border-radius: .5rem; padding: .45rem .6rem; font-size: 13px; background: #fff; outline: none; max-width: 100%; }
    .pmd-group > summary { list-style: none; cursor: pointer; }
    .pmd-group > summary::-webkit-details-marker { display: none; }
    .pmd-group[open] .pmd-caret { transform: rotate(90deg); }
    .pmd-caret { transition: transform .15s; }
    .pmd-bar { height: 6px; border-radius: 9999px; background: #e2e8f0; overflow: hidden; }
    .pmd-bar > i { display: block; height: 100%; background: #10b981; }
    .pmd-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .75rem; align-items: center; padding: .7rem 1rem; border-top: 1px solid #f1f5f9; }
    .pmd-row:hover { background: #f8fafc; }
    .pmd-row.is-done .pmd-name { color: #64748b; }
    .pmd-name { font-weight: 600; color: #1e293b; font-size: 14px; }
    .pmd-sub { font-size: 12px; color: #64748b; }
    .pmd-badge { display: inline-flex; align-items: center; gap: .25rem; padding: .1rem .5rem; border-radius: 9999px; font-size: 11px; font-weight: 600; white-space: nowrap; }
    @media (max-width: 639.98px) {
        .pmd-row { grid-template-columns: minmax(0, 1fr); }
        .pmd-row .pmd-act button { width: 100%; justify-content: center; padding-top: .6rem; padding-bottom: .6rem; }
    }
    /* จอคอม (lg+): แบ่งซ้าย-ขวา — รายการกลุ่มทางซ้าย, เครื่องในกลุ่มที่เลือกทางขวา (เลื่อนแยกกัน) */
    .pmd-split { display: grid; grid-template-columns: minmax(260px, 340px) minmax(0, 1fr); gap: .75rem; min-height: 420px; }
    .pmd-split > div { min-height: 0; overflow-y: auto; background: #fff; border: 1px solid #e2e8f0; border-radius: .75rem; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
    .pmd-side-item { display: block; width: 100%; text-align: left; padding: .7rem .9rem; border-bottom: 1px solid #f1f5f9; border-left: 3px solid transparent; }
    .pmd-side-item:hover { background: #f8fafc; }
    .pmd-side-item.on { background: #f0f9ff; border-left-color: #006B9F; }
    .pmd-side-item.on .pmd-side-name { color: #006B9F; }
    .pmd-side-name { font-weight: 600; font-size: 13.5px; color: #1e293b; }
    .pmd-detail .pmd-group { border: 0; box-shadow: none; border-radius: 0; overflow: visible; }
    .pmd-detail .pmd-group > summary { position: sticky; top: 0; z-index: 2; background: #fff; border-bottom: 1px solid #e2e8f0; cursor: default; }
    .pmd-detail .pmd-group > summary:hover { background: #fff; }
    .pmd-detail .pmd-caret { display: none; }
    .pmd-detail .pmd-row:first-of-type { border-top: 0; }
    /* มือถือ: แนวคิดเดียวกับจอคอม — รายการกลุ่ม => แตะแล้วเครื่องในกลุ่มเปิดเป็นหน้าเต็มจอ (ปุ่มกลับด้านบน, บันทึกพร้อมกันติดล่าง) */
    @keyframes pmdIn { from { transform: translateX(24px); opacity: 0; } to { transform: none; opacity: 1; } }
    @media (max-width: 1023.98px) {
        .pmd-split { display: block; height: auto !important; min-height: 0; }
        .pmd-split > #pmd-side { overflow: visible; }
        .pmd-side-item { padding: .85rem 1rem; }
        .pmd-side-item:last-child { border-bottom: 0; }
        .pmd-side-item.on { background: #fff; border-left-color: transparent; }
        .pmd-side-item.on .pmd-side-name { color: #1e293b; }
        .pmd-side-item:active { background: #f1f5f9; }
        #pmd-detail { display: none; position: fixed; inset: 0; z-index: 900; border: 0; border-radius: 0; box-shadow: none; background: #f8fafc; overflow-y: auto; -webkit-overflow-scrolling: touch; }
        .pmd-split.is-detail #pmd-detail { display: flex; flex-direction: column; animation: pmdIn .18s ease-out; }
        .pmd-detail .pmd-group { background: #fff; flex: 1 0 auto; }
        .pmd-detail .pmd-group > summary { display: none; }
        /* ปุ่มบันทึกพร้อมกันติดล่าง: ปุ่มเดียว = เต็มความกว้าง, หลายวัน = เลื่อนซ้าย-ขวาในแถวเดียว */
        .pmd-batchbar { position: sticky; bottom: 0; z-index: 2; background: #fff; border-top: 1px solid #e2e8f0; box-shadow: 0 -4px 12px rgba(15,23,42,.06);
                        padding: .7rem 1rem calc(.7rem + env(safe-area-inset-bottom)); display: flex; gap: .5rem; overflow-x: auto; scrollbar-width: none; }
        .pmd-batchbar::-webkit-scrollbar { display: none; }
        .pmd-batchbar button { flex: 0 0 auto; justify-content: center; padding: .7rem 1rem; font-size: 13.5px; border-radius: .75rem; }
        .pmd-batchbar button:only-child { flex: 1 1 auto; }
        #pmd-chips { flex-wrap: nowrap; overflow-x: auto; scrollbar-width: none; margin: 0 -.75rem; padding: 0 .75rem; }
        #pmd-chips::-webkit-scrollbar { display: none; }
        #tab-daily:not(.adv-open) .pmd-adv { display: none !important; }
    }
</style>

<div id="tab-daily" class="tab-content hidden space-y-3">
    <div class="bg-white p-3 sm:p-4 rounded-xl shadow-sm border border-slate-200 space-y-3">
        <!-- แถว 1: ช่วงวัน + เลื่อนวัน -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- มือถือ: ช่วงวัน / รวมงานค้าง อยู่ในตัวกรองเพิ่มเติม (แสดงเป็นแถวถัดไป) -->
            <div class="pmd-adv order-last basis-full h-0 lg:hidden" aria-hidden="true"></div>
            <div class="pmd-seg pmd-adv order-last lg:order-none" id="pmd-range">
                <button type="button" data-days="1">รายวัน</button>
                <button type="button" data-days="7">7 วัน</button>
            </div>
            <div class="flex items-center gap-1 flex-1 min-w-0 lg:flex-none">
                <button type="button" id="pmd-prev" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50" title="ก่อนหน้า" aria-label="ก่อนหน้า"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                <input type="date" id="pmd-date" class="flex-1 min-w-0 lg:flex-none border border-slate-200 rounded-lg py-1.5 px-2 text-sm outline-none focus:ring-2 focus:ring-sky-500/30">
                <button type="button" id="pmd-next" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50" title="ถัดไป" aria-label="ถัดไป"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                <button type="button" id="pmd-today" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-sm font-medium">วันนี้</button>
            </div>
            <div id="pmd-date-label" class="hidden lg:block text-sm font-semibold text-slate-700"></div>
            <label class="pm-switch pmd-adv order-last lg:order-none lg:ml-auto text-slate-600" title="แสดงงานที่ยังไม่ได้ทำจากวันก่อนหน้า">
                <input type="checkbox" id="pmd-overdue"><i></i> รวมงานค้าง
            </label>
            <div class="flex items-center gap-1 lg:gap-2">
                <!-- มือถือ: ตัวกรองเพิ่มเติม (ช่วงวัน / รวมงานค้าง / จัดกลุ่ม / ประเภทเครื่อง) พับไว้ -->
                <button type="button" id="pmd-adv-btn" class="lg:hidden relative p-2 rounded-lg border border-slate-200 hover:bg-slate-50" title="ตัวกรองเพิ่มเติม" aria-label="ตัวกรองเพิ่มเติม" aria-expanded="false">
                    <i data-lucide="sliders-horizontal" class="w-4 h-4"></i><span id="pmd-adv-dot" class="hidden absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-amber-500 ring-2 ring-white"></span>
                </button>
                <button type="button" id="pmd-refresh" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50" title="โหลดใหม่" aria-label="โหลดใหม่"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button>
            </div>
        </div>

        <!-- แถว 2: สถานะ -->
        <div id="pmd-chips" class="flex flex-wrap gap-2"></div>

        <!-- แถว 3: ประเภทเครื่อง / ค้นหา -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="pmd-adv"><select id="pmd-type" class="pmd-sel" aria-label="ประเภทเครื่อง"><option value="">ทุกประเภทเครื่อง</option></select></span>
            <div class="relative flex-1 min-w-[200px] basis-full lg:basis-auto">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="search" id="pmd-q" placeholder="ค้นหาเครื่อง / รหัส / สถานที่ / เช็คชีต" class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500/30">
            </div>
        </div>
    </div>

    <div id="pmd-list" class="space-y-2"></div>
</div>

<script>
(function () {
    const $ = id => document.getElementById(id);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const ymd = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const addDays = (s, n) => { const d = new Date(s + 'T00:00:00'); d.setDate(d.getDate() + n); return ymd(d); };
    const thDate = s => new Date(s + 'T00:00:00').toLocaleDateString('th-TH', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    const thShort = s => new Date(s + 'T00:00:00').toLocaleDateString('th-TH', { weekday: 'short', day: 'numeric', month: 'short' });
    const fmt = n => Number(n || 0).toLocaleString();

    const PAGE = 30;            // จำนวนเครื่องที่โหลดต่อครั้งในแต่ละกลุ่ม

    // ค่าที่จำไว้ในเครื่อง
    const pref = (() => { try { return JSON.parse(localStorage.getItem('pmdPref')) || {}; } catch (e) { return {}; } })();
    const savePref = () => { try { localStorage.setItem('pmdPref', JSON.stringify(pref)); } catch (e) { /* โหมดส่วนตัว: ไม่จำค่า */ } };

    let curDate = '', curTo = '', today = ymd(new Date()), filter = 'todo';
    let days = pref.days === 7 ? 7 : 1;
    let summary = null;         // { counts, groups, types } จาก action=summary
    let openKeys = new Set();   // กลุ่มที่เปิดอยู่ (มีได้กลุ่มเดียว = selKey)
    let items = {};             // group key => { rows, loading, error, done(โหลดครบแล้ว) }
    let reqSeq = 0;             // กันผลลัพธ์เก่ามาทับผลลัพธ์ใหม่ (เปลี่ยนตัวกรองเร็ว ๆ)
    // เปิดได้ทีละกลุ่ม (selKey) — จอคอม: แบ่งซ้าย-ขวา, มือถือ: รายการกลุ่ม แตะแล้วเข้าไปดูเครื่อง
    const WIDE = window.matchMedia('(min-width: 1024px)');
    let selKey = null;
    // จัดกลุ่มตามเช็คชีตเสมอ (ทุกขนาดจอ) — ปุ่มบันทึกพร้อมกันใช้ได้ทุกกลุ่ม
    const grpVal = () => 'checksheet';
    function pickSel() {
        const gs = summary.groups;
        if (!gs.some(g => g.key === selKey)) selKey = WIDE.matches && gs.length ? gs[0].key : null;   // มือถือ: เริ่มที่รายการกลุ่ม
        openKeys = new Set(selKey != null ? [selKey] : []);
    }
    // ความสูงของส่วนแบ่ง = พื้นที่ที่เหลือของหน้าจอ (แต่ละฝั่งเลื่อนเอง)
    function fitSplit() {
        const el = $('pmd-split'), main = el && el.closest('main');
        if (!main) return;
        if (!WIDE.matches) { el.style.height = ''; return; }
        const top = el.getBoundingClientRect().top - main.getBoundingClientRect().top + main.scrollTop;
        el.style.height = Math.max(420, main.clientHeight - top - 12) + 'px';
    }
    window.addEventListener('resize', fitSplit);

    // สถานะของงาน: done = ทำแล้ว, overdue = เลยกำหนดยังไม่ทำ, pending = รอดำเนินการ
    const stateOf = r => r.status === 1 ? 'done' : (r.event_date < today ? 'overdue' : 'pending');
    const STATE = {
        done:    { label: 'ทำแล้ว',     cls: 'bg-emerald-50 text-emerald-700', icon: 'check-circle-2' },
        pending: { label: 'รอดำเนินการ', cls: 'bg-sky-50 text-sky-700',         icon: 'clock' },
        overdue: { label: 'เลยกำหนด',    cls: 'bg-red-50 text-red-700',         icon: 'alert-triangle' },
    };
    const GROUP_ICON = { checksheet: 'file-check-2', location: 'map-pin', machine_type: 'cpu', event_date: 'calendar' };
    const EMPTY_LABEL = { checksheet: '(ไม่มีชื่อเช็คชีต)', location: '(ไม่ระบุสถานที่)', machine_type: '(ไม่ระบุประเภท)', event_date: '' };

    let refreshOnFocus = false;  // เปิดหน้าต่างใบงานแล้ว => กลับมาที่หน้านี้ให้โหลดสถานะใหม่
    function openWin(url) {
        refreshOnFocus = true;
        if (typeof openPmFullWindow === 'function') openPmFullWindow(url); else window.open(url, '_blank');
    }

    // พารามิเตอร์ร่วมของ summary/items
    function params(extra) {
        return new URLSearchParams(Object.assign({
            date: curDate, date_to: curTo, overdue: $('pmd-overdue').checked ? 1 : 0,
            q: $('pmd-q').value.trim(), type: $('pmd-type').value, group: grpVal(), filter,
        }, extra || {})).toString();
    }

    // โหลดตัวเลขสรุป + รายการกลุ่ม (เบา: ไม่มีรายการเครื่อง) — keepOpen = คงกลุ่มที่เปิดไว้ (กลับมาจากใบงาน)
    async function load(keepOpen) {
        const seq = ++reqSeq;
        curDate = $('pmd-date').value || ymd(new Date());
        curTo = addDays(curDate, days - 1);
        $('pmd-date-label').textContent = days === 1 ? thDate(curDate) : `${thShort(curDate)} – ${thShort(curTo)}`;
        document.querySelectorAll('#pmd-range button').forEach(b => b.classList.toggle('on', +b.dataset.days === days));
        items = {};
        if (!summary || !keepOpen) $('pmd-list').innerHTML = `<div class="bg-white rounded-xl border border-slate-200 py-12 text-center text-slate-400 text-sm">กำลังโหลด...</div>`;
        try {
            const res = await fetch('handle_pm_daily.php?action=summary&' + params());
            const data = await res.json();
            if (seq !== reqSeq) return;
            if (!data.success) throw new Error(data.error || 'โหลดข้อมูลไม่สำเร็จ');
            summary = data;
            today = data.today || today;
        } catch (e) {
            if (seq !== reqSeq) return;
            summary = null;
            const m = mainEl(); if (m) m.style.overflow = '';   // หน้าเต็มจอบนมือถือถูกล้างไปด้วย
            $('pmd-chips').innerHTML = '';
            $('pmd-list').innerHTML = `<div class="bg-white rounded-xl border border-red-200 py-10 text-center text-red-600 text-sm">${esc(e.message)}</div>`;
            return;
        }
        fillTypes();
        syncAdvDot();
        pickSel();
        render();
    }

    // โหลดเครื่องในกลุ่มทีละหน้า
    async function loadItems(key, more) {
        const it = items[key] || (items[key] = { rows: [], loading: false, error: '', done: false });
        if (it.loading || (more && it.done)) return;
        it.loading = true; it.error = '';
        if (!more) it.rows = [];
        renderGroup(key);
        const seq = reqSeq;
        try {
            const res = await fetch('handle_pm_daily.php?action=items&' + params({ key, offset: it.rows.length, limit: PAGE }));
            const data = await res.json();
            if (seq !== reqSeq || items[key] !== it) return;
            if (!data.success) throw new Error(data.error || 'โหลดรายการไม่สำเร็จ');
            it.rows = it.rows.concat(data.data);
            it.done = data.data.length < PAGE;
        } catch (e) {
            if (seq !== reqSeq || items[key] !== it) return;
            it.error = e.message;
        }
        it.loading = false;
        renderGroup(key);
    }

    function fillTypes() {
        const sel = $('pmd-type'), cur = sel.value;
        const types = summary.types || [];
        sel.innerHTML = `<option value="">ทุกประเภทเครื่อง</option>` + types.map(t => `<option ${t === cur ? 'selected' : ''}>${esc(t)}</option>`).join('');
        if (cur && !types.includes(cur)) sel.insertAdjacentHTML('beforeend', `<option selected>${esc(cur)}</option>`);
    }

    function render() {
        const c = summary.counts;
        const chip = (key, label, cls) => `<button type="button" data-f="${key}" class="pmd-chip ${cls} ${filter === key ? 'is-on' : ''}">${label} <b>${fmt(c[key])}</b></button>`;
        $('pmd-chips').innerHTML = chip('todo', 'ต้องทำ', 'bg-amber-50 text-amber-700')
            + (c.overdue || filter === 'overdue' ? chip('overdue', 'เลยกำหนด', STATE.overdue.cls) : '')
            + chip('done', 'ทำแล้ว', STATE.done.cls)
            + chip('all', 'ทั้งหมด', 'bg-slate-100 text-slate-700');

        if (!summary.groups.length) {
            const searching = $('pmd-q').value.trim() || $('pmd-type').value;
            const msg = !c.all && !searching ? 'ไม่มีงาน PM ในช่วงนี้' : (filter === 'todo' && !searching ? 'ไม่มีงานค้าง ทำครบแล้ว' : 'ไม่พบรายการตามเงื่อนไข');
            $('pmd-list').innerHTML = `<div class="bg-white rounded-xl border border-slate-200 py-14 text-center text-slate-400 text-sm">
                <i data-lucide="calendar-check" class="w-9 h-9 mx-auto mb-2"></i>${msg}</div>`;
            lucide.createIcons({ root: $('pmd-list') });
            return;
        }
        const more500 = summary.groups.length >= 500 ? `<div class="text-center text-xs text-slate-400 py-2">แสดง 500 กลุ่มแรก — ค้นหาหรือกรองเพื่อดูกลุ่มอื่น</div>` : '';
        $('pmd-list').innerHTML = `<div id="pmd-split" class="pmd-split">
            <div id="pmd-side">${summary.groups.map(sideHtml).join('')}${more500}</div>
            <div id="pmd-detail" class="pmd-detail"></div></div>`;
        renderDetail();
        lucide.createIcons({ root: $('pmd-side') });
        fitSplit();
    }

    const labelOf = g => { const grp = grpVal(); return grp === 'event_date' ? thDate(g.key) : (g.label || EMPTY_LABEL[grp]); };
    const pctOf = g => g.total ? Math.round(g.done * 100 / g.total) : 0;

    // ปุ่มบันทึกพร้อมกัน — เครื่องที่ใช้เช็คชีตเดียวกันในวันเดียวกัน (g.batch จาก summary)
    //  outline = แบบขอบ (การ์ดในรายการยาว ๆ ไม่ลายตา)
    const batchBtns = (g, outline) => (g.batch || []).map(b =>
        `<button type="button" data-batch="${esc(g.key)}" data-date="${b.date}" class="px-2.5 py-1.5 rounded-lg ${outline ? 'border border-sky-200 bg-white text-sky-700 active:bg-sky-50' : 'bg-sky-600 hover:bg-sky-700 text-white'} text-xs font-semibold inline-flex items-center gap-1.5 whitespace-nowrap">
            <i data-lucide="layers" class="w-3.5 h-3.5"></i> บันทึกพร้อมกัน ${b.n} เครื่อง${days > 1 || b.date !== curDate ? ' · ' + thShort(b.date) : ''}</button>`).join('');

    // รายการเช็คชีต (จอคอม: ฝั่งซ้าย) — มือถือมีปุ่มบันทึกพร้อมกันบนการ์ดด้วย (div แทน button เพราะมีปุ่มซ้อนข้างใน)
    function sideHtml(g) {
        const batch = batchBtns(g, true);
        return `<div role="button" tabindex="0" data-sel="${esc(g.key)}" class="pmd-side-item cursor-pointer ${g.key === selKey ? 'on' : ''}">
            <div class="flex items-start gap-2">
                <i data-lucide="${GROUP_ICON[grpVal()]}" class="w-4 h-4 mt-0.5 flex-none text-[#006B9F]"></i>
                <span class="pmd-side-name flex-1 min-w-0 break-words">${esc(labelOf(g))}</span>
                <span class="text-xs text-slate-500 flex-none">${fmt(g.n)} เครื่อง</span>
                <i data-lucide="chevron-right" class="w-4 h-4 flex-none text-slate-300 lg:hidden"></i>
            </div>
            <div class="flex items-center gap-2 mt-1.5 pl-6">
                <span class="pmd-bar flex-1"><i style="width:${pctOf(g)}%"></i></span>
                <span class="text-[11px] text-slate-500 flex-none">${fmt(g.done)}/${fmt(g.total)}</span>
                ${g.overdue ? `<span class="pmd-badge ${STATE.overdue.cls} flex-none">เลยกำหนด ${fmt(g.overdue)}</span>` : ''}
            </div>
            ${batch ? `<div class="lg:hidden flex gap-2 mt-2.5 pl-6 overflow-x-auto" style="scrollbar-width:none">${batch}</div>` : ''}
        </div>`;
    }
    // กลุ่มที่เลือก (จอคอม: ฝั่งขวา, มือถือ: หน้าถัดไปพร้อมปุ่มกลับ) — ใช้ renderGroup เดิม
    const GROUP_NAME = { checksheet: 'เช็คชีต', location: 'สถานที่', machine_type: 'ประเภทเครื่อง', event_date: 'วันที่' };
    function renderDetail() {
        const box = $('pmd-detail');
        if (!box) return;
        $('pmd-split').classList.toggle('is-detail', selKey != null);
        if (selKey == null) { box.innerHTML = ''; lockMain(); return; }
        const g = summary.groups.find(x => x.key === selKey);
        // มือถือ: หัวหน้าเต็มจอ (ปุ่มกลับ + ชื่อกลุ่ม) — จอคอมใช้หัวกลุ่มเดิม (summary)
        box.innerHTML = `<div class="lg:hidden sticky top-0 z-[3] bg-white border-b border-slate-200 flex items-center gap-1 pl-1 pr-3 py-1.5 shadow-sm">
                <button type="button" data-back class="w-11 h-11 flex-none rounded-full flex items-center justify-center text-slate-600 active:bg-slate-100" title="กลับไป${GROUP_NAME[grpVal()]}ทั้งหมด" aria-label="กลับ"><i data-lucide="arrow-left" class="w-5 h-5"></i></button>
                <div class="min-w-0 flex-1">
                    <div class="font-semibold text-slate-800 text-[15px] leading-tight truncate">${esc(labelOf(g))}</div>
                    <div class="text-xs text-slate-500 mt-0.5 flex flex-wrap items-center gap-x-2">
                        <span>${fmt(g.n)} เครื่อง</span>
                        ${g.overdue ? `<span class="text-red-600 font-semibold">เลยกำหนด ${fmt(g.overdue)}</span>` : ''}
                        <span>ทำแล้ว ${fmt(g.done)}/${fmt(g.total)}</span>
                    </div>
                </div>
            </div>
            <details class="pmd-group" data-key="${esc(selKey)}"></details>`;
        lucide.createIcons({ root: box });
        box.scrollTop = 0;
        lockMain();
        if (!items[selKey]) loadItems(selKey); else renderGroup(selKey);
    }
    const mainEl = () => $('tab-daily').closest('main');
    // มือถือ: หน้าเต็มจอเปิดอยู่ => หน้ารายการด้านหลังไม่เลื่อนตาม (กลับมาแล้วอยู่ที่เดิม)
    function lockMain() { const m = mainEl(); if (m) m.style.overflow = !WIDE.matches && selKey != null && $('pmd-split') ? 'hidden' : ''; }
    function selectGroup(key) {
        selKey = key; openKeys = new Set([key]);
        document.querySelectorAll('#pmd-side [data-sel]').forEach(b => b.classList.toggle('on', b.dataset.sel === key));
        renderDetail();
    }
    function backToList() {
        selKey = null; openKeys = new Set();
        document.querySelectorAll('#pmd-side [data-sel]').forEach(b => b.classList.remove('on'));
        renderDetail();
        lockMain();
    }

    function renderGroup(key) {
        const el = [...document.querySelectorAll('#pmd-list .pmd-group')].find(d => d.dataset.key === key);
        const g = summary && summary.groups.find(x => x.key === key);
        if (!el || !g) return;
        const grp = grpVal();
        const wide = WIDE.matches;   // จอคอม: ปุ่มบันทึกพร้อมกันอยู่ขวาสุดของหัวกลุ่ม, มือถือ: ใต้หัวกลุ่ม
        const label = labelOf(g);
        const open = openKeys.has(key);
        const it = items[key];

        const batch = batchBtns(g);

        let body = '';
        if (open) {
            body = (it ? it.rows.map(rowHtml).join('') : '')
                + (it && it.loading ? `<div class="py-4 text-center text-sm text-slate-400 border-t border-slate-100">กำลังโหลด...</div>` : '')
                + (it && it.error ? `<div class="py-4 text-center text-sm text-red-600 border-t border-slate-100">${esc(it.error)} <button type="button" data-retry="${esc(key)}" class="underline ml-1">ลองใหม่</button></div>` : '')
                + (it && !it.loading && !it.error && !it.done && it.rows.length < g.n
                    ? `<button type="button" data-more="${esc(key)}" class="w-full py-2.5 text-sm text-sky-700 hover:bg-sky-50 border-t border-slate-100">แสดงเพิ่มอีก ${Math.min(PAGE, g.n - it.rows.length)} จาก ${fmt(g.n - it.rows.length)} เครื่องที่เหลือ</button>` : '');
        }

        el.open = open;
        el.innerHTML = `<summary class="flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3 hover:bg-slate-50">
                <i data-lucide="chevron-right" class="pmd-caret w-4 h-4 text-slate-400"></i>
                <i data-lucide="${GROUP_ICON[grp]}" class="w-4 h-4 text-[#006B9F]"></i>
                <span class="font-semibold text-slate-800 text-sm min-w-0 break-words">${esc(label)}</span>
                <span class="pmd-badge bg-slate-100 text-slate-600">${fmt(g.n)} เครื่อง</span>
                ${g.overdue ? `<span class="pmd-badge ${STATE.overdue.cls}">เลยกำหนด ${fmt(g.overdue)}</span>` : ''}
                ${batch && open && wide ? `<span class="flex-1 min-w-[16rem] flex flex-wrap justify-end gap-2">${batch}</span>` : ''}
            </summary>
            ${body}
            ${batch && open && !wide ? `<div class="pmd-batchbar">${batch}</div>` : ''}`;
        lucide.createIcons({ root: el });
    }

    function rowHtml(r) {
        const st = stateOf(r), s = STATE[st];
        let btn;
        // ปุ่มแบบขอบ (ไม่ทึบ) ให้รายการยาว ๆ ดูไม่ลายตา
        if (st !== 'done') {
            btn = `<button type="button" data-open="${r.id}" class="px-3 py-2 rounded-lg border border-sky-200 text-sky-700 hover:bg-sky-600 hover:text-white hover:border-sky-600 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors"><i data-lucide="external-link" class="w-3.5 h-3.5"></i> เปิดใบงาน</button>`;
        } else if (r.has_record) {
            btn = `<button type="button" data-history="${r.id}" class="px-3 py-2 rounded-lg border border-emerald-200 text-emerald-700 hover:bg-emerald-600 hover:text-white hover:border-emerald-600 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors"><i data-lucide="search" class="w-3.5 h-3.5"></i> ดูประวัติ</button>`;
        } else {
            // status = 1 แต่ไม่มีใบบันทึกผลในระบบ (เช่น แก้สถานะในฐานข้อมูลโดยตรง)
            btn = `<span class="text-xs text-slate-400" title="งานนี้ถูกตั้งสถานะเป็นทำแล้ว แต่ไม่มีใบบันทึกผลในระบบ">ไม่มีใบบันทึก</span>`;
        }
        const g = grpVal();
        const meta = [
            r.machine_code && `<span class="font-mono">${esc(r.machine_code)}</span>`,
            g !== 'machine_type' && r.machine_type && esc(r.machine_type),
            g !== 'location' && r.location && `<span class="inline-flex items-center gap-1"><i data-lucide="map-pin" class="w-3 h-3"></i>${esc(r.location)}</span>`,
            g !== 'checksheet' && r.checksheet && `<span class="inline-flex items-center gap-1"><i data-lucide="file-check-2" class="w-3 h-3"></i>${esc(r.checksheet)}</span>`,
        ].filter(Boolean).join('<span class="text-slate-300">·</span>');
        // ป้ายสถานะเดียวต่อแถว: เลยกำหนด (พร้อมวันกำหนด) / ทำแล้ว; รอดำเนินการไม่ต้องมีป้าย (เป็นค่าปกติ)
        const showDate = days > 1 && g !== 'event_date';
        let badge = '';
        if (st === 'overdue') badge = `<span class="pmd-badge ${s.cls}"><i data-lucide="${s.icon}" class="w-3 h-3"></i>เลยกำหนด · ${thShort(r.event_date)}</span>`;
        else if (st === 'done') badge = `<span class="pmd-badge ${s.cls}"><i data-lucide="${s.icon}" class="w-3 h-3"></i>ทำแล้ว${r.inspector ? ' · ' + esc(r.inspector) : ''}</span>`;
        else if (showDate) badge = `<span class="pmd-badge bg-slate-100 text-slate-600">${thShort(r.event_date)}</span>`;
        return `<div class="pmd-row ${st === 'done' ? 'is-done' : ''}">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="pmd-name">${esc(r.machine_name || '-')}</span>
                    ${badge}
                </div>
                <div class="pmd-sub flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-0.5">${meta}</div>
            </div>
            <div class="pmd-act shrink-0">${btn}</div>
        </div>`;
    }

    // ---------- เหตุการณ์ ----------
    $('pmd-overdue').checked = pref.overdue !== false;
    $('pmd-date').value = today;

    $('pmd-range').addEventListener('click', e => {
        const b = e.target.closest('[data-days]');
        if (!b) return;
        days = +b.dataset.days; pref.days = days; savePref();
        load();
    });
    $('pmd-date').addEventListener('change', () => load());
    $('pmd-prev').addEventListener('click', () => { $('pmd-date').value = addDays($('pmd-date').value || today, -days); load(); });
    $('pmd-next').addEventListener('click', () => { $('pmd-date').value = addDays($('pmd-date').value || today, days); load(); });
    $('pmd-today').addEventListener('click', () => { $('pmd-date').value = ymd(new Date()); load(); });
    $('pmd-refresh').addEventListener('click', () => load(true));
    $('pmd-overdue').addEventListener('change', () => { pref.overdue = $('pmd-overdue').checked; savePref(); load(); });
    $('pmd-type').addEventListener('change', () => load());
    let qTimer = null;
    $('pmd-q').addEventListener('input', () => { clearTimeout(qTimer); qTimer = setTimeout(() => load(), 350); });
    $('pmd-adv-btn').addEventListener('click', () => {
        const open = $('tab-daily').classList.toggle('adv-open');
        $('pmd-adv-btn').setAttribute('aria-expanded', open);
        $('pmd-adv-btn').classList.toggle('bg-sky-50', open);
    });
    function syncAdvDot() {
        const custom = days !== 1 || !$('pmd-overdue').checked || !!$('pmd-type').value;
        $('pmd-adv-dot').classList.toggle('hidden', !custom);
    }
    $('pmd-chips').addEventListener('click', e => {
        const b = e.target.closest('[data-f]');
        if (b) { filter = b.dataset.f; load(); }
    });

    $('pmd-list').addEventListener('click', e => {
        const sel = e.target.closest('[data-sel]');
        if (sel && !e.target.closest('[data-batch]')) { selectGroup(sel.dataset.sel); return; }
        if (e.target.closest('[data-back]')) { backToList(); return; }
        // หัวกลุ่มที่เลือกเปิดค้างไว้ (ปุ่มในหัวกลุ่มทำงานต่อด้านล่าง)
        if (e.target.closest('summary')) { e.preventDefault(); if (!e.target.closest('button')) return; }
        const op = e.target.closest('[data-open]'), hi = e.target.closest('[data-history]'), ba = e.target.closest('[data-batch]');
        const more = e.target.closest('[data-more]'), retry = e.target.closest('[data-retry]');
        // ใบงาน / ประวัติ: เปิดเป็น dialog บนหน้านี้ (ปิดแล้วโหลดสถานะใหม่ผ่าน pm:batch-closed)
        if (op) window.openPmWorksheetDialog ? window.openPmWorksheetDialog(op.dataset.open, false) : openWin('pm_worksheet.php?plan_id=' + encodeURIComponent(op.dataset.open));
        else if (hi) window.openPmWorksheetDialog ? window.openPmWorksheetDialog(hi.dataset.history, true) : openWin('pm_worksheet.php?plan_id=' + encodeURIComponent(hi.dataset.history) + '&mode=history');
        else if (ba) window.openPmBatchDialog('pm_worksheet_batch.php?checksheet_id=' + encodeURIComponent(ba.dataset.batch) + '&date=' + encodeURIComponent(ba.dataset.date));
        else if (more) loadItems(more.dataset.more, true);
        else if (retry) loadItems(retry.dataset.retry);
    });

    $('pmd-list').addEventListener('keydown', e => {
        const sel = e.target.closest && e.target.closest('[data-sel]');
        if (sel && e.target === sel && (e.key === 'Enter' || e.key === ' ')) { e.preventDefault(); selectGroup(sel.dataset.sel); }
    });

    // กลับมาจากหน้าต่างใบงาน => โหลดสถานะใหม่ (เฉพาะตอนแท็บนี้เปิดอยู่) โดยคงกลุ่มที่เปิดไว้
    window.addEventListener('focus', () => {
        if (!refreshOnFocus || !summary || $('tab-daily').classList.contains('hidden')) return;
        refreshOnFocus = false;
        load(true);
    });

    // ปิด dialog บันทึกพร้อมกัน => โหลดสถานะใหม่ (เฉพาะตอนแท็บนี้เปิดอยู่)
    window.addEventListener('pm:batch-closed', () => {
        if (summary && !$('tab-daily').classList.contains('hidden')) load(true);
    });

    // ย่อ/ขยายหน้าต่างข้ามขนาดจอคอม <-> มือถือ => สลับรูปแบบ
    WIDE.addEventListener('change', () => {
        if (!summary) return;
        pickSel();
        render();
    });
    // แท็บเพิ่งแสดง (ก่อนหน้าซ่อนอยู่ ความสูงเป็น 0) => คำนวณความสูงส่วนแบ่งใหม่
    new MutationObserver(() => { if (!$('tab-daily').classList.contains('hidden')) fitSplit(); })
        .observe($('tab-daily'), { attributes: true, attributeFilter: ['class'] });

    // switchTab('daily', opts) — เปิดจากหน้าภาพรวม: กรองสถานะที่ต้องการ
    window.initDailyTab = function (opts) {
        if (opts && opts.filter) { filter = opts.filter; if (opts.filter === 'overdue') $('pmd-overdue').checked = true; }
        load(!!summary && !(opts && opts.filter));
    };
})();
</script>
