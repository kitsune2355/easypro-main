<?php
// pm_evaluate.php — แท็บ "ประเมิน PM" ในหน้าวางแผน PM (โหมดหน้างาน)
//  ใบงานที่บันทึกผลแล้ว จัดกลุ่มตามวัน × เช็คชีต => ประเมินรวมทั้งกลุ่ม หรือรายใบ (pm_evaluate_batch.php เปิดเป็น dialog)
//  จอคอม: แบ่งซ้าย-ขวา (กลุ่ม | ใบงานในกลุ่ม), มือถือ: รายการกลุ่ม แตะแล้วเข้าไปดูใบงาน (มีปุ่มกลับ) — แบบเดียวกับแท็บงานที่ต้องทำ
?>
<div id="tab-evaluate" class="tab-content hidden">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 sm:p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-800">ประเมิน PM</h3>
                <p class="hidden sm:block text-[12px] text-slate-500">ประเมินผลงาน PM ที่บันทึกผลแล้ว — ประเมินรวมทั้งกลุ่ม (เช็คชีตเดียวกัน วันเดียวกัน) หรือรายใบ</p>
            </div>
            <div id="ev-me" class="text-xs"></div>
        </div>

        <div id="ev-warn" class="hidden mb-3 rounded-xl border px-3 py-2.5 text-sm"></div>

        <!-- ตัวกรอง -->
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <div class="grid grid-cols-3 lg:flex gap-1 p-1 bg-slate-100 rounded-xl text-sm font-semibold w-full lg:w-auto" id="ev-status">
                <button type="button" data-s="pending" class="px-3 py-1.5 rounded-lg">รอประเมิน</button>
                <button type="button" data-s="done" class="px-3 py-1.5 rounded-lg">ประเมินแล้ว</button>
                <button type="button" data-s="all" class="px-3 py-1.5 rounded-lg">ทั้งหมด</button>
            </div>
            <div class="flex items-center gap-2 w-full lg:w-auto">
                <input type="date" id="ev-from" class="h-9 px-2 border border-slate-200 rounded-lg text-sm flex-1 min-w-0 lg:flex-none" aria-label="ตั้งแต่วันที่">
                <span class="text-slate-400 text-sm">–</span>
                <input type="date" id="ev-to" class="h-9 px-2 border border-slate-200 rounded-lg text-sm flex-1 min-w-0 lg:flex-none" aria-label="ถึงวันที่">
            </div>
            <div class="flex-1 min-w-0 lg:flex-none"><select id="ev-cs" class="h-9 px-2 border border-slate-200 rounded-lg text-sm w-full lg:w-auto lg:min-w-[12rem]" aria-label="เช็คชีต"><option value="">ทุกเช็คชีต</option></select></div>
            <span id="ev-count" class="lg:ml-auto text-xs text-slate-500"></span>
        </div>

        <div id="ev-list"></div>
    </div>
</div>

<style>
    #ev-status button.on { background: #fff; color: #006B9F; box-shadow: 0 1px 2px rgba(15,23,42,.12); }
    #ev-status button:not(.on) { color: #64748b; }
    /* จอคอม (lg+): แบ่งซ้าย-ขวา — กลุ่มทางซ้าย, ใบงานในกลุ่มที่เลือกทางขวา (เลื่อนแยกกัน) */
    .ev-split { display: grid; grid-template-columns: minmax(260px, 340px) minmax(0, 1fr); border: 1px solid #e2e8f0; border-radius: .75rem; overflow: hidden; min-height: 420px; }
    .ev-split > div { min-height: 0; overflow-y: auto; }
    #ev-side { border-right: 1px solid #e2e8f0; background: #fbfcfd; }
    .ev-side-item { display: block; width: 100%; text-align: left; padding: .65rem .9rem; border-bottom: 1px solid #f1f5f9; border-left: 3px solid transparent; }
    .ev-side-item:hover { background: #f1f5f9; }
    .ev-side-item.on { background: #f0f9ff; border-left-color: #006B9F; }
    .ev-side-item.on .ev-side-name { color: #006B9F; }
    .ev-side-name { font-weight: 600; font-size: 13.5px; color: #1e293b; }
    .ev-row { display: grid; grid-template-columns: minmax(0, 1fr) 7.5rem auto; gap: .75rem; align-items: center; padding: .65rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .ev-row:hover { background: #f8fafc; }
    .ev-side-item .ev-chev { display: none; }   /* ลูกศรเฉพาะมือถือ (ใช้ lg:hidden ไม่ได้ — CSS ของ fa ทับ) */
    /* มือถือ: แนวคิดเดียวกับจอคอม — รายการกลุ่ม => แตะแล้วใบงานในกลุ่มเปิดเป็นหน้าเต็มจอ (ปุ่มกลับด้านบน, ประเมินทั้งกลุ่มติดล่าง) */
    @keyframes evIn { from { transform: translateX(24px); opacity: 0; } to { transform: none; opacity: 1; } }
    @media (max-width: 1023.98px) {
        .ev-split { display: block; height: auto !important; min-height: 0; overflow: visible; }
        .ev-split > #ev-side { overflow: visible; }
        #ev-side { border-right: 0; background: #fff; border-radius: .75rem; }
        .ev-side-item { padding: .85rem 1rem; }
        .ev-side-item:last-child { border-bottom: 0; }
        .ev-side-item.on { background: #fff; border-left-color: transparent; }
        .ev-side-item.on .ev-side-name { color: #1e293b; }
        .ev-side-item:active { background: #f1f5f9; }
        .ev-side-item .ev-chev { display: inline-block; }
        #ev-detail { display: none; position: fixed; inset: 0; z-index: 900; background: #f8fafc; overflow-y: auto; -webkit-overflow-scrolling: touch; }
        .ev-split.is-detail #ev-detail { display: flex; flex-direction: column; animation: evIn .18s ease-out; }
        #ev-detail [data-items] { background: #fff; flex: 1 0 auto; }
        .ev-bottombar { position: sticky; bottom: 0; z-index: 2; background: #fff; border-top: 1px solid #e2e8f0; box-shadow: 0 -4px 12px rgba(15,23,42,.06);
                        padding: .7rem 1rem calc(.7rem + env(safe-area-inset-bottom)); }
        .ev-bottombar button { width: 100%; justify-content: center; padding: .75rem 1rem; border-radius: .75rem; }
        .ev-row { grid-template-columns: minmax(0, 1fr) auto; gap: .5rem .75rem; }
        .ev-row > :nth-child(3) { grid-column: 1 / -1; }
        .ev-row > :nth-child(3) button { flex: 1; justify-content: center; padding-top: .55rem; padding-bottom: .55rem; }
        .ev-row-head { display: none; }
    }
</style>

<script>
(function () {
    const $ = id => document.getElementById(id);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const thDate = d => { const x = new Date(String(d).slice(0, 10) + 'T00:00:00'); return isNaN(x) ? (d || '-') : x.toLocaleDateString('th-TH', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }); };
    const ymd = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    let ME = null, groups = [], status = 'pending', loading = 0;
    // เลือกทีละกลุ่ม (selKey = วันที่|เช็คชีต) — จอคอม: เลือกกลุ่มแรกให้เลย, มือถือ: เริ่มที่รายการกลุ่ม
    const WIDE = window.matchMedia('(min-width: 1024px)');
    let selKey = null;
    const keyOf = g => g.d + '|' + g.cs_id;
    const canEvalNow = () => ME && ME.is_evaluator && ME.topics.length;
    const mainEl = () => $('tab-evaluate').closest('main');
    // จอคอม: ความสูงของส่วนแบ่ง = พื้นที่ที่เหลือของหน้าจอ (แต่ละฝั่งเลื่อนเอง)
    function fitSplit() {
        const el = $('ev-split'), main = el && el.closest('main');
        if (!main) return;
        if (!WIDE.matches) { el.style.height = ''; return; }
        const top = el.getBoundingClientRect().top - main.getBoundingClientRect().top + main.scrollTop;
        el.style.height = Math.max(420, main.clientHeight - top - 32) + 'px';   // 32 = ขอบล่างของการ์ด + ระยะห่าง
    }
    window.addEventListener('resize', fitSplit);

    async function loadMe() {
        ME = await (await fetch('handle_pm_evaluate.php?action=me')).json();
        if (!ME.success) ME = { is_evaluator: false, topics: [], evaluator_count: 0 };
        $('ev-me').innerHTML = ME.is_evaluator
            ? `<span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold"><i class="fas fa-user-check mr-1"></i>ผู้ประเมิน: ${esc(ME.name)}</span>`
            : `<span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 font-semibold"><i class="fas fa-eye mr-1"></i>ดูอย่างเดียว</span>`;
        const w = $('ev-warn');
        if (!ME.evaluator_count) { w.className = 'mb-3 rounded-xl border px-3 py-2.5 text-sm border-amber-200 bg-amber-50 text-amber-800'; w.innerHTML = '<i class="fas fa-triangle-exclamation mr-1"></i> ยังไม่ได้กำหนดผู้ประเมิน — ผู้ดูแลระบบกำหนดได้ที่ <b>ตั้งค่าข้อมูล → การประเมินผล PM → ผู้ประเมิน PM</b>'; }
        else if (!ME.topics.length) { w.className = 'mb-3 rounded-xl border px-3 py-2.5 text-sm border-amber-200 bg-amber-50 text-amber-800'; w.innerHTML = '<i class="fas fa-triangle-exclamation mr-1"></i> ยังไม่มีหัวข้อประเมิน — เพิ่มได้ที่ <b>ตั้งค่าข้อมูล → การประเมินผล PM → รายการหัวข้อประเมิน</b>'; }
        else if (!ME.is_evaluator) { w.className = 'mb-3 rounded-xl border px-3 py-2.5 text-sm border-slate-200 bg-slate-50 text-slate-600'; w.innerHTML = '<i class="fas fa-circle-info mr-1"></i> คุณไม่ได้อยู่ในรายชื่อผู้ประเมิน จึงดูสถานะการประเมินได้อย่างเดียว'; }
        else { w.className = 'hidden'; }
    }

    async function load() {
        const my = ++loading;
        // โหลดใหม่หลังปิดหน้าต่างประเมิน/ใบงาน => คงรายการเดิมไว้ระหว่างโหลด (หน้าเต็มจอบนมือถือไม่กะพริบ)
        if (!$('ev-split')) $('ev-list').innerHTML = '<div class="py-10 text-center text-slate-400 text-sm"><i class="fas fa-circle-notch fa-spin mr-1"></i> กำลังโหลด...</div>';
        const qs = new URLSearchParams({ action: 'groups', status, from: $('ev-from').value, to: $('ev-to').value, cs: $('ev-cs').value });
        let r;
        try { r = await (await fetch('handle_pm_evaluate.php?' + qs)).json(); } catch (e) { r = { success: false, error: e.message }; }
        if (my !== loading) return;
        if (!r.success) { selKey = null; lockMain(); $('ev-list').innerHTML = `<div class="py-6 text-center text-red-600 text-sm">${esc(r.error || 'โหลดไม่สำเร็จ')}</div>`; return; }
        // เช็คชีตในตัวกรอง (คงค่าที่เลือกไว้)
        const sel = $('ev-cs'), cur = sel.value;
        if (sel.options.length <= 1 || sel.dataset.n != r.checksheets.length) {
            sel.innerHTML = '<option value="">ทุกเช็คชีต</option>' + r.checksheets.map(c => `<option value="${esc(c.id)}">${esc(c.name)}</option>`).join('');
            sel.value = cur; sel.dataset.n = r.checksheets.length;
        }
        groups = r.data;
        const pend = groups.reduce((s, g) => s + (g.t - g.ev), 0);
        $('ev-count').textContent = `${groups.length} กลุ่ม · รอประเมิน ${pend.toLocaleString()} ใบ`;
        render();
    }

    function render() {
        if (!groups.length) {
            selKey = null; lockMain();
            $('ev-list').innerHTML = `<div class="py-12 text-center text-slate-400 text-sm"><i class="fas fa-clipboard-check text-3xl text-slate-300 block mb-2"></i>${status === 'pending' ? 'ไม่มีใบงานที่รอประเมินในช่วงนี้' : 'ไม่พบรายการ'}</div>`;
            return;
        }
        if (!groups.some(g => keyOf(g) === selKey)) selKey = WIDE.matches ? keyOf(groups[0]) : null;
        $('ev-list').innerHTML = `<div id="ev-split" class="ev-split">
            <div id="ev-side">${groups.map((g, i) => {
                const pending = g.t - g.ev;
                return `<button type="button" data-sel="${i}" class="ev-side-item ${keyOf(g) === selKey ? 'on' : ''}">
                    <div class="flex items-start gap-2">
                        <span class="w-2 h-2 mt-1.5 rounded-full flex-none ${pending ? 'bg-amber-500' : 'bg-emerald-500'}"></span>
                        <span class="ev-side-name flex-1 min-w-0 break-words">${esc(g.cs || 'ไม่ระบุเช็คชีต')}</span>
                        <span class="text-xs text-slate-500 flex-none">${g.t} ใบ</span>
                        <i class="ev-chev fas fa-chevron-right text-[11px] text-slate-300 mt-1 flex-none"></i>
                    </div>
                    <div class="flex items-center gap-2 mt-1 pl-4 text-xs lg:pr-0 pr-5">
                        <span class="text-slate-500 flex-1">${thDate(g.d)}</span>
                        ${pending ? `<b class="text-amber-700">รอ ${pending}</b>` : `<b class="text-emerald-600"><i class="fas fa-check"></i> ครบ</b>`}
                    </div>
                </button>`;
            }).join('')}</div>
            <div id="ev-detail"></div></div>`;
        renderDetail();
        fitSplit();
    }

    // กลุ่มที่เลือก (จอคอม: ฝั่งขวา, มือถือ: หน้าถัดไปพร้อมปุ่มกลับ) — หัว (ปุ่มประเมินทั้งกลุ่ม) + ใบงานเป็นแถว
    function renderDetail() {
        const box = $('ev-detail'), i = groups.findIndex(g => keyOf(g) === selKey);
        if (!box) return;
        $('ev-split').classList.toggle('is-detail', i >= 0);
        if (i < 0) { box.innerHTML = ''; lockMain(); return; }
        const g = groups[i], pending = g.t - g.ev, canEval = canEvalNow();
        const evBtn = cls => pending && canEval ? `<button type="button" data-eval-group="${i}" class="${cls} px-4 py-2 rounded-lg bg-[#006B9F] hover:bg-[#005a87] text-white text-sm font-semibold inline-flex items-center gap-1.5"><i class="fas fa-star"></i> ประเมิน${pending > 1 ? 'ทั้งกลุ่ม ' + pending + ' ใบ' : ''}</button>` : '';
        const sub = `${thDate(g.d)} · ${g.t} ใบ${pending ? ` · <b class="text-amber-700">รอประเมิน ${pending}</b>` : ''}${g.ev ? ` · <b class="text-emerald-600">ประเมินแล้ว ${g.ev}</b>` : ''}`;
        box.innerHTML = `
            <div class="lg:hidden sticky top-0 z-[3] bg-white border-b border-slate-200 flex items-center gap-1 pl-1 pr-3 py-1.5 shadow-sm">
                <button type="button" data-back class="w-11 h-11 flex-none rounded-full flex items-center justify-center text-slate-600 active:bg-slate-100" title="กลับไปรายการทั้งหมด" aria-label="กลับ"><i class="fas fa-arrow-left"></i></button>
                <div class="min-w-0 flex-1">
                    <div class="font-semibold text-slate-800 text-[15px] leading-tight truncate">${esc(g.cs || 'ไม่ระบุเช็คชีต')}</div>
                    <div class="text-xs text-slate-500 mt-0.5">${sub}</div>
                </div>
            </div>
            <div class="hidden lg:flex sticky top-0 z-[1] bg-white border-b border-slate-200 px-4 py-3 items-center gap-3 flex-wrap">
                <div class="flex-1 min-w-[12rem]">
                    <div class="font-semibold text-slate-800 break-words">${esc(g.cs || 'ไม่ระบุเช็คชีต')}</div>
                    <div class="text-xs text-slate-500 mt-0.5">${sub}</div>
                </div>
                ${evBtn('')}
            </div>
            <div class="ev-row ev-row-head !py-2 bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase"><span>เครื่อง / ใบงาน</span><span>ผลประเมิน</span><span></span></div>
            <div data-items="${i}"></div>
            ${evBtn('') ? `<div class="ev-bottombar lg:hidden">${evBtn('')}</div>` : ''}`;
        box.scrollTop = 0;
        lockMain();
        loadItems(i);
    }

    // มือถือ: หน้าเต็มจอเปิดอยู่ => หน้ารายการด้านหลังไม่เลื่อนตาม (กลับมาแล้วอยู่ที่เดิม)
    function lockMain() { const m = mainEl(); if (m) m.style.overflow = !WIDE.matches && selKey != null && $('ev-split')?.classList.contains('is-detail') ? 'hidden' : ''; }
    function selectGroup(i) {
        selKey = keyOf(groups[i]);
        document.querySelectorAll('#ev-side [data-sel]').forEach(b => b.classList.toggle('on', +b.dataset.sel === i));
        renderDetail();
    }
    function backToList() {
        selKey = null;
        document.querySelectorAll('#ev-side [data-sel]').forEach(b => b.classList.remove('on'));
        renderDetail();
    }

    async function loadItems(i) {
        const box = document.querySelector(`#ev-detail [data-items="${i}"]`);
        if (!box) return;
        box.innerHTML = '<div class="py-6 text-center text-sm text-slate-400"><i class="fas fa-circle-notch fa-spin mr-1"></i> กำลังโหลด...</div>';
        const g = groups[i];
        let r;
        try { r = await (await fetch(`handle_pm_evaluate.php?action=items&date=${g.d}&cs=${encodeURIComponent(g.cs_id)}`)).json(); } catch (e) { r = { success: false, error: e.message }; }
        if (!box.isConnected) return;   // เลือกกลุ่มอื่นไปแล้ว
        if (!r.success) { box.innerHTML = `<div class="py-6 text-center text-sm text-red-600">${esc(r.error || 'โหลดไม่สำเร็จ')}</div>`; return; }
        const canEval = canEvalNow();
        box.innerHTML = r.data.map(x => {
            const ev = x.evaluation_status == 1;
            return `<div class="ev-row">
                <div class="min-w-0">
                    <div class="font-semibold text-slate-800 truncate">${esc(x.machine_code || '-')} <span class="font-normal text-slate-600">${esc(x.machine_name || '')}</span></div>
                    <div class="text-xs text-slate-500 truncate">${esc(x.doc_no || '')} · ผู้ตรวจ ${esc(x.inspector_name || '-')}${x.location ? ' · ' + esc(x.location) : ''}</div>
                </div>
                <div>${ev ? `<span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-semibold text-xs whitespace-nowrap" title="${esc(x.evaluator_name || '')}">★ ${Number(x.average_score).toFixed(2)}</span>`
                          : '<span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-semibold text-xs whitespace-nowrap">รอประเมิน</span>'}</div>
                <div class="flex items-center gap-1.5 justify-end">
                    <button type="button" data-ws="${x.id}" class="px-3 py-1.5 rounded-lg border border-slate-200 text-[#006B9F] text-xs font-semibold hover:bg-sky-50 inline-flex items-center gap-1.5"><i class="fas fa-file-lines"></i> ดูใบงาน</button>
                    ${!ev && canEval ? `<button type="button" data-eval-one="${x.id}" class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold inline-flex items-center gap-1.5"><i class="fas fa-star"></i> ประเมิน</button>` : ''}
                </div>
            </div>`;
        }).join('') || '<div class="py-10 text-center text-sm text-slate-400">ไม่มีใบงาน</div>';
    }

    document.addEventListener('click', e => {
        if (!e.target.closest('#tab-evaluate')) return;
        const sl = e.target.closest('#ev-side [data-sel]');
        if (sl) { selectGroup(+sl.dataset.sel); return; }
        if (e.target.closest('#ev-detail [data-back]')) { backToList(); return; }
        const st = e.target.closest('#ev-status [data-s]');
        if (st) { status = st.dataset.s; syncStatus(); load(); return; }
        const eg = e.target.closest('[data-eval-group]');
        if (eg) { const g = groups[+eg.dataset.evalGroup]; window.openPmDialog(`pm_evaluate_batch.php?cs=${encodeURIComponent(g.cs_id)}&date=${g.d}`, 'ประเมินผลงาน PM'); return; }
        const eo = e.target.closest('[data-eval-one]');
        if (eo) { window.openPmDialog(`pm_evaluate_batch.php?ids=${eo.dataset.evalOne}`, 'ประเมินผลงาน PM'); return; }
        const ws = e.target.closest('[data-ws]');
        if (ws) { window.openPmWorksheetDialog(ws.dataset.ws, true); return; }
    });
    const syncStatus = () => document.querySelectorAll('#ev-status [data-s]').forEach(b => b.classList.toggle('on', b.dataset.s === status));
    ['ev-from', 'ev-to', 'ev-cs'].forEach(id => $(id).addEventListener('change', load));
    // ปิด dialog ประเมิน / ใบงาน => โหลดรายการใหม่ (คงกลุ่มที่เลือกไว้)
    window.addEventListener('pm:batch-closed', () => { if (!$('tab-evaluate').classList.contains('hidden')) load(); });

    // ย่อ/ขยายหน้าต่างข้ามขนาดจอคอม <-> มือถือ => สลับรูปแบบ
    WIDE.addEventListener('change', () => { if (ME) render(); });
    // แท็บเพิ่งแสดง => คำนวณความสูงส่วนแบ่งใหม่
    new MutationObserver(() => { if (!$('tab-evaluate').classList.contains('hidden')) fitSplit(); })
        .observe($('tab-evaluate'), { attributes: true, attributeFilter: ['class'] });

    window.reloadEvaluateTab = load;
    pmLazy('evaluate', async () => {
        const now = new Date();
        $('ev-from').value = ymd(new Date(now.getFullYear(), now.getMonth() - 1, 1));   // ค่าเริ่มต้น: ต้นเดือนก่อน – วันนี้
        $('ev-to').value = ymd(now);
        syncStatus();
        await loadMe();
        load();
    });
})();
</script>
