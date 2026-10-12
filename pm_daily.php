<?php
//pm_daily.php — แท็บ "งานที่ต้องทำ": ช่างดูงาน PM ของวันที่เลือก + งานค้างจากวันก่อน แล้วเปิดใบงานได้ทันที (ข้อมูลจาก handle_pm_daily.php)
?>
<style>
    .pmd-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .75rem; border-radius: 9999px; font-size: 12px; font-weight: 600; border: 1px solid transparent; cursor: pointer; white-space: nowrap; }
    .pmd-chip.is-on { box-shadow: 0 0 0 2px currentColor inset; }
    .pmd-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .75rem; align-items: center; padding: .7rem 1rem; border-top: 1px solid #f1f5f9; }
    .pmd-row:hover { background: #f8fafc; }
    .pmd-row.is-done .pmd-name { color: #64748b; }
    .pmd-name { font-weight: 600; color: #1e293b; font-size: 14px; }
    .pmd-sub { font-size: 12px; color: #64748b; }
    .pmd-badge { display: inline-flex; align-items: center; gap: .25rem; padding: .1rem .5rem; border-radius: 9999px; font-size: 11px; font-weight: 600; white-space: nowrap; }
</style>

<div id="tab-daily" class="tab-content hidden space-y-3">
    <div class="bg-white p-3 sm:p-4 rounded-xl shadow-sm border border-slate-200 space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-1">
                <button type="button" id="pmd-prev" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50" title="วันก่อนหน้า" aria-label="วันก่อนหน้า"><i data-lucide="chevron-left" class="w-4 h-4"></i></button>
                <input type="date" id="pmd-date" class="border border-slate-200 rounded-lg py-2 px-3 text-sm outline-none focus:ring-2 focus:ring-sky-500/30">
                <button type="button" id="pmd-next" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50" title="วันถัดไป" aria-label="วันถัดไป"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                <button type="button" id="pmd-today" class="px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-sm font-medium">วันนี้</button>
            </div>
            <div id="pmd-date-label" class="text-sm font-semibold text-slate-700 sm:ml-2"></div>
            <div class="flex items-center gap-3 ml-auto">
                <label class="pm-switch text-slate-600" title="แสดงงานที่ยังไม่ได้ทำจากวันก่อนหน้า">
                    <input type="checkbox" id="pmd-overdue"><i></i> รวมงานค้าง
                </label>
                <button type="button" id="pmd-refresh" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50" title="โหลดใหม่" aria-label="โหลดใหม่"><i data-lucide="refresh-cw" class="w-4 h-4"></i></button>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div id="pmd-chips" class="flex flex-wrap gap-2"></div>
            <div class="relative w-full sm:w-64 sm:ml-auto">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="search" id="pmd-q" placeholder="ค้นหาเครื่อง / รหัส / สถานที่ / เช็คชีต" class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500/30">
            </div>
        </div>
    </div>

    <div id="pmd-list" class="space-y-3"></div>
</div>

<script>
(function () {
    const $ = id => document.getElementById(id);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const ymd = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const thDate = s => new Date(s + 'T00:00:00').toLocaleDateString('th-TH', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    const thShort = s => new Date(s + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'short' });

    let rows = [], curDate = '', today = ymd(new Date()), filter = 'todo', loading = false;

    // สถานะของงาน: done = ทำแล้ว, overdue = เลยกำหนดยังไม่ทำ, pending = รอดำเนินการ
    const stateOf = r => r.status === 1 ? 'done' : (r.event_date < today ? 'overdue' : 'pending');
    const STATE = {
        done:    { label: 'ทำแล้ว',         cls: 'bg-emerald-50 text-emerald-700', icon: 'check-circle-2' },
        pending: { label: 'รอดำเนินการ',     cls: 'bg-sky-50 text-sky-700',         icon: 'clock' },
        overdue: { label: 'เลยกำหนด',        cls: 'bg-red-50 text-red-700',         icon: 'alert-triangle' },
    };

    function openWin(url) {
        if (typeof openPmFullWindow === 'function') openPmFullWindow(url); else window.open(url, '_blank');
    }

    async function load() {
        if (loading) return;
        loading = true;
        curDate = $('pmd-date').value || ymd(new Date());
        $('pmd-date-label').textContent = thDate(curDate);
        $('pmd-list').innerHTML = `<div class="bg-white rounded-xl border border-slate-200 py-12 text-center text-slate-400 text-sm">กำลังโหลด...</div>`;
        try {
            const res = await fetch(`handle_pm_daily.php?action=list&date=${encodeURIComponent(curDate)}&overdue=${$('pmd-overdue').checked ? 1 : 0}`);
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'โหลดข้อมูลไม่สำเร็จ');
            rows = data.data || [];
            today = data.today || today;
        } catch (e) {
            rows = [];
            $('pmd-list').innerHTML = `<div class="bg-white rounded-xl border border-red-200 py-10 text-center text-red-600 text-sm">${esc(e.message)}</div>`;
            loading = false;
            return;
        }
        loading = false;
        render();
    }

    function render() {
        const q = $('pmd-q').value.trim().toLowerCase();
        const matchQ = r => !q || [r.machine_name, r.machine_code, r.location, r.checksheet, r.machine_type].some(v => String(v || '').toLowerCase().includes(q));
        const base = rows.filter(matchQ);

        // ชิปสรุป (กดเพื่อกรองตามสถานะ)
        const cnt = { all: base.length, pending: 0, overdue: 0, done: 0 };
        base.forEach(r => cnt[stateOf(r)]++);
        cnt.todo = cnt.pending + cnt.overdue;
        const chip = (key, label, cls) => `<button type="button" data-f="${key}" class="pmd-chip ${cls} ${filter === key ? 'is-on' : ''}">${label} <b>${cnt[key]}</b></button>`;
        $('pmd-chips').innerHTML = chip('todo', 'ต้องทำ', 'bg-amber-50 text-amber-700')
            + (cnt.overdue || filter === 'overdue' ? chip('overdue', 'เลยกำหนด', STATE.overdue.cls) : '')
            + chip('done', 'ทำแล้ว', STATE.done.cls)
            + chip('all', 'ทั้งหมด', 'bg-slate-100 text-slate-700');

        const list = base.filter(r => filter === 'all' || (filter === 'todo' ? r.status !== 1 : stateOf(r) === filter));
        if (!list.length) {
            const msg = !rows.length ? 'ไม่มีงาน PM ในวันนี้' : (filter === 'todo' && !q ? 'ไม่มีงานค้าง ทำครบแล้ว' : 'ไม่พบรายการตามเงื่อนไข');
            $('pmd-list').innerHTML = `<div class="bg-white rounded-xl border border-slate-200 py-14 text-center text-slate-400 text-sm">
                <i data-lucide="calendar-check" class="w-9 h-9 mx-auto mb-2"></i>${msg}</div>`;
            lucide.createIcons({ root: $('pmd-list') });
            return;
        }

        // จัดกลุ่มตามเช็คชีต (เครื่องที่ใช้เช็คชีตเดียวกันในวันเดียวกัน บันทึกพร้อมกันได้)
        const groups = new Map();
        list.forEach(r => {
            if (!groups.has(r.checksheet_id)) groups.set(r.checksheet_id, { id: r.checksheet_id, name: r.checksheet || '-', items: [] });
            groups.get(r.checksheet_id).items.push(r);
        });

        $('pmd-list').innerHTML = [...groups.values()].map(g => {
            const done = g.items.filter(r => r.status === 1).length;
            const batchable = g.items.filter(r => r.status === 0 && r.event_date === curDate).length;
            return `<section class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <header class="flex flex-wrap items-center gap-2 px-4 py-3 bg-slate-50">
                    <i data-lucide="file-check-2" class="w-4 h-4 text-[#006B9F]"></i>
                    <h3 class="font-semibold text-slate-800 text-sm">${esc(g.name)}</h3>
                    <span class="text-xs text-slate-500">ทำแล้ว ${done}/${g.items.length} เครื่อง</span>
                    ${batchable > 1 ? `<button type="button" data-batch="${g.id}" class="ml-auto px-3 py-1.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-semibold inline-flex items-center gap-1.5">
                        <i data-lucide="layers" class="w-3.5 h-3.5"></i> บันทึกพร้อมกัน ${batchable} เครื่อง</button>` : ''}
                </header>
                ${g.items.map(rowHtml).join('')}
            </section>`;
        }).join('');
        lucide.createIcons({ root: $('pmd-list') });
    }

    function rowHtml(r) {
        const st = stateOf(r), s = STATE[st];
        let btn;
        if (st !== 'done') {
            btn = `<button type="button" data-open="${r.id}" class="px-3 py-2 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-semibold inline-flex items-center gap-1.5"><i data-lucide="external-link" class="w-3.5 h-3.5"></i> เปิดใบงาน</button>`;
        } else if (r.has_record) {
            btn = `<button type="button" data-history="${r.id}" class="px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold inline-flex items-center gap-1.5"><i data-lucide="search" class="w-3.5 h-3.5"></i> ดูประวัติ</button>`;
        } else {
            // status = 1 แต่ไม่มีใบบันทึกผลในระบบ (เช่น แก้สถานะในฐานข้อมูลโดยตรง)
            btn = `<span class="text-xs text-slate-400" title="งานนี้ถูกตั้งสถานะเป็นทำแล้ว แต่ไม่มีใบบันทึกผลในระบบ">ไม่มีใบบันทึก</span>`;
        }
        const meta = [
            r.machine_code && `<span class="font-mono">${esc(r.machine_code)}</span>`,
            r.machine_type && esc(r.machine_type),
            r.location && `<span class="inline-flex items-center gap-1"><i data-lucide="map-pin" class="w-3 h-3"></i>${esc(r.location)}</span>`,
        ].filter(Boolean).join('<span class="text-slate-300">·</span>');
        const extra = st === 'overdue' ? `<span class="pmd-badge bg-red-50 text-red-600">กำหนด ${thShort(r.event_date)}</span>`
            : (st === 'done' && r.inspector ? `<span class="pmd-sub">โดย ${esc(r.inspector)}</span>` : '');
        return `<div class="pmd-row ${st === 'done' ? 'is-done' : ''}">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="pmd-name">${esc(r.machine_name || '-')}</span>
                    <span class="pmd-badge ${s.cls}"><i data-lucide="${s.icon}" class="w-3 h-3"></i>${s.label}</span>
                    ${extra}
                </div>
                <div class="pmd-sub flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-0.5">${meta}</div>
            </div>
            <div class="shrink-0">${btn}</div>
        </div>`;
    }

    function shiftDay(n) {
        const d = new Date(($('pmd-date').value || ymd(new Date())) + 'T00:00:00');
        d.setDate(d.getDate() + n);
        $('pmd-date').value = ymd(d);
        load();
    }

    // "รวมงานค้าง" เปิดไว้เป็นค่าเริ่มต้น (จำค่าที่ผู้ใช้เลือกไว้ในเครื่อง)
    try { $('pmd-overdue').checked = localStorage.getItem('pmdOverdue') !== '0'; } catch (e) { $('pmd-overdue').checked = true; }
    $('pmd-date').value = today;
    $('pmd-date').addEventListener('change', load);
    $('pmd-prev').addEventListener('click', () => shiftDay(-1));
    $('pmd-next').addEventListener('click', () => shiftDay(1));
    $('pmd-today').addEventListener('click', () => { $('pmd-date').value = ymd(new Date()); load(); });
    $('pmd-refresh').addEventListener('click', load);
    $('pmd-overdue').addEventListener('change', () => {
        try { localStorage.setItem('pmdOverdue', $('pmd-overdue').checked ? '1' : '0'); } catch (e) { /* โหมดส่วนตัว: ไม่จำค่า */ }
        load();
    });
    let qTimer = null;
    $('pmd-q').addEventListener('input', () => { clearTimeout(qTimer); qTimer = setTimeout(render, 200); });
    $('pmd-chips').addEventListener('click', e => {
        const b = e.target.closest('[data-f]');
        if (b) { filter = b.dataset.f; render(); }
    });
    $('pmd-list').addEventListener('click', e => {
        const op = e.target.closest('[data-open]'), hi = e.target.closest('[data-history]'), ba = e.target.closest('[data-batch]');
        if (op) openWin('pm_worksheet.php?plan_id=' + encodeURIComponent(op.dataset.open));
        else if (hi) openWin('pm_worksheet.php?plan_id=' + encodeURIComponent(hi.dataset.history) + '&mode=history');
        else if (ba) openWin('pm_worksheet_batch.php?checksheet_id=' + encodeURIComponent(ba.dataset.batch) + '&date=' + encodeURIComponent(curDate));
    });

    // กลับมาจากหน้าต่างใบงาน => โหลดสถานะใหม่ (เฉพาะตอนแท็บนี้เปิดอยู่)
    window.addEventListener('focus', () => { if (!$('tab-daily').classList.contains('hidden')) load(); });

    window.initDailyTab = load;
})();
</script>
