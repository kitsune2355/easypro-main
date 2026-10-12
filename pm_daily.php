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
</style>

<div id="tab-daily" class="tab-content hidden space-y-3">
    <div class="bg-white p-3 sm:p-4 rounded-xl shadow-sm border border-slate-200 space-y-3">
        <!-- แถว 1: ช่วงวัน + เลื่อนวัน -->
        <div class="flex flex-wrap items-center gap-2">
            <div class="pmd-seg" id="pmd-range">
                <button type="button" data-days="1">รายวัน</button>
                <button type="button" data-days="7">7 วัน</button>
            </div>
            <div class="flex items-center gap-1">
                <button type="button" id="pmd-prev" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50" title="ก่อนหน้า" aria-label="ก่อนหน้า"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                <input type="date" id="pmd-date" class="border border-slate-200 rounded-lg py-1.5 px-2 text-sm outline-none focus:ring-2 focus:ring-sky-500/30">
                <button type="button" id="pmd-next" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50" title="ถัดไป" aria-label="ถัดไป"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                <button type="button" id="pmd-today" class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-sm font-medium">วันนี้</button>
            </div>
            <div id="pmd-date-label" class="text-sm font-semibold text-slate-700"></div>
            <div class="flex items-center gap-3 ml-auto">
                <label class="pm-switch text-slate-600" title="แสดงงานที่ยังไม่ได้ทำจากวันก่อนหน้า">
                    <input type="checkbox" id="pmd-overdue"><i></i> รวมงานค้าง
                </label>
                <button type="button" id="pmd-refresh" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50" title="โหลดใหม่" aria-label="โหลดใหม่"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button>
            </div>
        </div>

        <!-- แถว 2: สถานะ -->
        <div id="pmd-chips" class="flex flex-wrap gap-2"></div>

        <!-- แถว 3: จัดกลุ่ม / กรอง / ค้นหา -->
        <div class="flex flex-wrap items-center gap-2">
            <label class="flex items-center gap-1.5 text-xs text-slate-500">จัดกลุ่ม
                <select id="pmd-group" class="pmd-sel">
                    <option value="checksheet">เช็คชีต</option>
                    <option value="location">สถานที่</option>
                    <option value="machine_type">ประเภทเครื่อง</option>
                    <option value="event_date">วันที่</option>
                </select>
            </label>
            <select id="pmd-type" class="pmd-sel" aria-label="ประเภทเครื่อง"><option value="">ทุกประเภทเครื่อง</option></select>
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="search" id="pmd-q" placeholder="ค้นหาเครื่อง / รหัส / สถานที่ / เช็คชีต" class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500/30">
            </div>
            <div class="flex items-center gap-1 text-xs">
                <button type="button" id="pmd-expand" class="px-2.5 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600">ขยายทั้งหมด</button>
                <button type="button" id="pmd-collapse" class="px-2.5 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600">ย่อทั้งหมด</button>
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
    const AUTO_OPEN_MAX = 60;   // งานตามชิปที่เลือกไม่เกินนี้ => ขยายทุกกลุ่มให้เลย
    const OPEN_ALL_MAX = 15;    // "ขยายทั้งหมด" เปิดได้ไม่เกินกี่กลุ่ม (กันโหลดพร้อมกันมากเกินไป)

    // ค่าที่จำไว้ในเครื่อง
    const pref = (() => { try { return JSON.parse(localStorage.getItem('pmdPref')) || {}; } catch (e) { return {}; } })();
    const savePref = () => { try { localStorage.setItem('pmdPref', JSON.stringify(pref)); } catch (e) { /* โหมดส่วนตัว: ไม่จำค่า */ } };

    let curDate = '', curTo = '', today = ymd(new Date()), filter = 'todo';
    let days = pref.days === 7 ? 7 : 1;
    let summary = null;         // { counts, groups, types } จาก action=summary
    let openKeys = null;        // กลุ่มที่เปิดอยู่ (null = ค่าเริ่มต้นตามจำนวนงาน)
    let items = {};             // group key => { rows, loading, error, done(โหลดครบแล้ว) }
    let reqSeq = 0;             // กันผลลัพธ์เก่ามาทับผลลัพธ์ใหม่ (เปลี่ยนตัวกรองเร็ว ๆ)

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
            q: $('pmd-q').value.trim(), type: $('pmd-type').value, group: $('pmd-group').value, filter,
        }, extra || {})).toString();
    }

    // โหลดตัวเลขสรุป + รายการกลุ่ม (เบา: ไม่มีรายการเครื่อง) — keepOpen = คงกลุ่มที่เปิดไว้ (กลับมาจากใบงาน)
    async function load(keepOpen) {
        const seq = ++reqSeq;
        curDate = $('pmd-date').value || ymd(new Date());
        curTo = addDays(curDate, days - 1);
        $('pmd-date-label').textContent = days === 1 ? thDate(curDate) : `${thShort(curDate)} – ${thShort(curTo)}`;
        document.querySelectorAll('#pmd-range button').forEach(b => b.classList.toggle('on', +b.dataset.days === days));
        if (!keepOpen) openKeys = null;
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
            $('pmd-chips').innerHTML = '';
            $('pmd-list').innerHTML = `<div class="bg-white rounded-xl border border-red-200 py-10 text-center text-red-600 text-sm">${esc(e.message)}</div>`;
            return;
        }
        fillTypes();
        const groups = summary.groups;
        if (!openKeys) {
            const total = groups.reduce((s, g) => s + g.n, 0);
            openKeys = new Set(total <= AUTO_OPEN_MAX ? groups.map(g => g.key)
                : ($('pmd-q').value.trim() ? groups.slice(0, 3).map(g => g.key) : []));   // ค้นหา => เปิด 3 กลุ่มแรก
        } else {
            const exist = new Set(groups.map(g => g.key));
            openKeys = new Set([...openKeys].filter(k => exist.has(k)));
        }
        render();
        openKeys.forEach(k => loadItems(k));
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
        $('pmd-list').innerHTML = summary.groups.map(g => `<details class="pmd-group bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden" data-key="${esc(g.key)}"></details>`).join('')
            + (summary.groups.length >= 500 ? `<div class="text-center text-xs text-slate-400 py-2">แสดง 500 กลุ่มแรก — ค้นหาหรือกรองเพื่อดูกลุ่มอื่น</div>` : '');
        summary.groups.forEach(g => renderGroup(g.key));
    }

    function renderGroup(key) {
        const el = [...document.querySelectorAll('#pmd-list .pmd-group')].find(d => d.dataset.key === key);
        const g = summary && summary.groups.find(x => x.key === key);
        if (!el || !g) return;
        const grp = $('pmd-group').value;
        const label = grp === 'event_date' ? thDate(g.key) : (g.label || EMPTY_LABEL[grp]);
        const pct = g.total ? Math.round(g.done * 100 / g.total) : 0;
        const open = openKeys.has(key);
        const it = items[key];

        const batch = (g.batch || []).map(b =>
            `<button type="button" data-batch="${esc(g.key)}" data-date="${b.date}" class="px-2.5 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-semibold inline-flex items-center gap-1.5">
                <i data-lucide="layers" class="w-3.5 h-3.5"></i> บันทึกพร้อมกัน ${b.n} เครื่อง${days > 1 || b.date !== curDate ? ' · ' + thShort(b.date) : ''}</button>`).join('');

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
                <span class="flex items-center gap-2 ml-auto text-xs text-slate-500" title="ทำแล้ว ${fmt(g.done)} จาก ${fmt(g.total)}">
                    <span class="pmd-bar w-20 sm:w-28"><i style="width:${pct}%"></i></span>${fmt(g.done)}/${fmt(g.total)}
                </span>
            </summary>
            ${batch && open ? `<div class="flex flex-wrap gap-2 px-4 pb-3">${batch}</div>` : ''}
            ${body}`;
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
        const g = $('pmd-group').value;
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

    function toggleGroup(key, open) {
        if (open) openKeys.add(key); else openKeys.delete(key);
        if (open && !items[key]) loadItems(key); else renderGroup(key);
    }

    // ---------- เหตุการณ์ ----------
    $('pmd-overdue').checked = pref.overdue !== false;
    if (pref.group) $('pmd-group').value = pref.group;
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
    $('pmd-group').addEventListener('change', () => { pref.group = $('pmd-group').value; savePref(); load(); });
    $('pmd-type').addEventListener('change', () => load());
    let qTimer = null;
    $('pmd-q').addEventListener('input', () => { clearTimeout(qTimer); qTimer = setTimeout(() => load(), 350); });
    $('pmd-expand').addEventListener('click', () => {
        if (!summary) return;
        summary.groups.slice(0, OPEN_ALL_MAX).forEach(g => { if (!openKeys.has(g.key)) toggleGroup(g.key, true); });
    });
    $('pmd-collapse').addEventListener('click', () => { [...openKeys].forEach(k => toggleGroup(k, false)); });
    $('pmd-chips').addEventListener('click', e => {
        const b = e.target.closest('[data-f]');
        if (b) { filter = b.dataset.f; load(); }
    });

    $('pmd-list').addEventListener('click', e => {
        const sum = e.target.closest('summary');
        if (sum && !e.target.closest('button')) {
            // เปิด/ปิดกลุ่มเอง: โหลดรายการเมื่อเปิดครั้งแรก
            e.preventDefault();
            const key = sum.parentElement.dataset.key;
            toggleGroup(key, !openKeys.has(key));
            return;
        }
        const op = e.target.closest('[data-open]'), hi = e.target.closest('[data-history]'), ba = e.target.closest('[data-batch]');
        const more = e.target.closest('[data-more]'), retry = e.target.closest('[data-retry]');
        if (op) openWin('pm_worksheet.php?plan_id=' + encodeURIComponent(op.dataset.open));
        else if (hi) openWin('pm_worksheet.php?plan_id=' + encodeURIComponent(hi.dataset.history) + '&mode=history');
        else if (ba) openWin('pm_worksheet_batch.php?checksheet_id=' + encodeURIComponent(ba.dataset.batch) + '&date=' + encodeURIComponent(ba.dataset.date));
        else if (more) loadItems(more.dataset.more, true);
        else if (retry) loadItems(retry.dataset.retry);
    });

    // กลับมาจากหน้าต่างใบงาน => โหลดสถานะใหม่ (เฉพาะตอนแท็บนี้เปิดอยู่) โดยคงกลุ่มที่เปิดไว้
    window.addEventListener('focus', () => {
        if (!refreshOnFocus || !summary || $('tab-daily').classList.contains('hidden')) return;
        refreshOnFocus = false;
        load(true);
    });

    // switchTab('daily', opts) — เปิดจากหน้าภาพรวม: กรองสถานะที่ต้องการ
    window.initDailyTab = function (opts) {
        if (opts && opts.filter) { filter = opts.filter; if (opts.filter === 'overdue') $('pmd-overdue').checked = true; }
        load(!!summary && !(opts && opts.filter));
    };
})();
</script>
