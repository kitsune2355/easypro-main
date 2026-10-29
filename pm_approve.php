<?php
// pm_approve.php — แท็บ "ตรวจรับ PM" ในหน้าวางแผน PM (โหมดหน้างาน) — หัวหน้าช่างตรวจรับงานที่ช่างบันทึกผลแล้ว
//  เปิดใช้ต่อหน่วยงานที่ ตั้งค่าข้อมูล → แผน PM → การบันทึกผล PM · ผู้ตรวจรับกำหนดที่ ผู้ตรวจรับ PM (handle_pm_approve.php)
//  ใบงานจัดกลุ่มตามวัน × เช็คชีต => ตรวจรับรวมทั้งกลุ่ม หรือรายใบ / ส่งกลับแก้ไขรายใบ (ช่างบันทึกใหม่)
//  โครงหน้าแบบเดียวกับแท็บประเมิน PM (pm_evaluate.php): จอคอมแบ่งซ้าย-ขวา, มือถือแตะกลุ่มแล้วเปิดเต็มจอ
?>
<div id="tab-approve" class="tab-content hidden">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 sm:p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-800">ตรวจรับ PM</h3>
                <p class="hidden sm:block text-[12px] text-slate-500">งานที่ช่างบันทึกผลแล้วจะนับว่าเสร็จเมื่อตรวจรับ — ตรวจรับรวมทั้งกลุ่ม (เช็คชีตเดียวกัน วันเดียวกัน) หรือรายใบ · ไม่ผ่านให้ส่งกลับแก้ไข</p>
            </div>
            <div id="ap-me" class="text-xs"></div>
        </div>

        <div id="ap-warn" class="hidden mb-3 rounded-xl border px-3 py-2.5 text-sm"></div>

        <!-- ตัวกรอง: รอตรวจรับ = ทุกวันที่ (ไม่ต้องเลือกช่วง) -->
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <div class="grid grid-cols-3 lg:flex gap-1 p-1 bg-slate-100 rounded-xl text-sm font-semibold w-full lg:w-auto" id="ap-status">
                <button type="button" data-s="pending" class="px-3 py-1.5 rounded-lg">รอตรวจรับ</button>
                <button type="button" data-s="done" class="px-3 py-1.5 rounded-lg">ตรวจรับแล้ว</button>
                <button type="button" data-s="all" class="px-3 py-1.5 rounded-lg">ทั้งหมด</button>
            </div>
            <div id="ap-range" class="flex items-center gap-2 w-full lg:w-auto">
                <input type="date" id="ap-from" class="h-9 px-2 border border-slate-200 rounded-lg text-sm flex-1 min-w-0 lg:flex-none" aria-label="ตั้งแต่วันที่">
                <span class="text-slate-400 text-sm">–</span>
                <input type="date" id="ap-to" class="h-9 px-2 border border-slate-200 rounded-lg text-sm flex-1 min-w-0 lg:flex-none" aria-label="ถึงวันที่">
            </div>
            <div class="flex-1 min-w-0 lg:flex-none"><select id="ap-cs" class="h-9 px-2 border border-slate-200 rounded-lg text-sm w-full lg:w-auto lg:min-w-[12rem]" aria-label="เช็คชีต"><option value="">ทุกเช็คชีต</option></select></div>
            <span id="ap-count" class="lg:ml-auto text-xs text-slate-500"></span>
        </div>

        <div id="ap-list"></div>
    </div>
</div>

<!-- ลายมือชื่อผู้ตรวจรับ (ป๊อปอัปตรวจรับ) -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script src="js/pm_approve_sign.js"></script>

<style>
    #ap-status button.on { background: #fff; color: #047857; box-shadow: 0 1px 2px rgba(15,23,42,.12); }
    #ap-status button:not(.on) { color: #64748b; }
    .ap-split { display: grid; grid-template-columns: minmax(260px, 340px) minmax(0, 1fr); border: 1px solid #e2e8f0; border-radius: .75rem; overflow: hidden; min-height: 420px; }
    .ap-split > div { min-height: 0; overflow-y: auto; }
    #ap-side { border-right: 1px solid #e2e8f0; background: #fbfcfd; }
    .ap-side-item { display: block; width: 100%; text-align: left; padding: .65rem .9rem; border-bottom: 1px solid #f1f5f9; border-left: 3px solid transparent; }
    .ap-side-item:hover { background: #f1f5f9; }
    .ap-side-item.on { background: #ecfdf5; border-left-color: #047857; }
    .ap-side-item.on .ap-side-name { color: #047857; }
    .ap-side-name { font-weight: 600; font-size: 13.5px; color: #1e293b; }
    .ap-row { display: grid; grid-template-columns: minmax(0, 1fr) 8.5rem auto; gap: .75rem; align-items: center; padding: .65rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
    .ap-row:hover { background: #f8fafc; }
    .ap-side-item .ap-chev { display: none; }
    @media (max-width: 1023.98px) {
        .ap-split { display: block; height: auto !important; min-height: 0; overflow: visible; }
        .ap-split > #ap-side { overflow: visible; }
        #ap-side { border-right: 0; background: #fff; border-radius: .75rem; }
        .ap-side-item { padding: .85rem 1rem; }
        .ap-side-item:last-child { border-bottom: 0; }
        .ap-side-item.on { background: #fff; border-left-color: transparent; }
        .ap-side-item.on .ap-side-name { color: #1e293b; }
        .ap-side-item:active { background: #f1f5f9; }
        .ap-side-item .ap-chev { display: inline-block; }
        #ap-detail { display: none; position: fixed; inset: 0; z-index: 900; background: #f8fafc; overflow-y: auto; -webkit-overflow-scrolling: touch; }
        .ap-split.is-detail #ap-detail { display: flex; flex-direction: column; }
        #ap-detail [data-items] { background: #fff; flex: 1 0 auto; }
        .ap-bottombar { position: sticky; bottom: 0; z-index: 2; background: #fff; border-top: 1px solid #e2e8f0; box-shadow: 0 -4px 12px rgba(15,23,42,.06);
                        padding: .7rem 1rem calc(.7rem + env(safe-area-inset-bottom)); }
        .ap-bottombar button { width: 100%; justify-content: center; padding: .75rem 1rem; border-radius: .75rem; }
        .ap-row { grid-template-columns: minmax(0, 1fr) auto; gap: .5rem .75rem; }
        .ap-row > :nth-child(3) { grid-column: 1 / -1; }
        .ap-row > :nth-child(3) button { flex: 1; justify-content: center; padding-top: .55rem; padding-bottom: .55rem; }
        .ap-row-head { display: none; }
    }
</style>

<script>
(function () {
    const $ = id => document.getElementById(id);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const thDate = d => { const x = new Date(String(d).slice(0, 10) + 'T00:00:00'); return isNaN(x) ? (d || '-') : x.toLocaleDateString('th-TH', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }); };
    const thDateTime = s => { const d = new Date(String(s || '').replace(' ', 'T')); return isNaN(d) ? (s || '-') : d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' }) + ' ' + d.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }); };
    const ymd = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    const API = 'handle_pm_approve.php';
    let ME = null, groups = [], status = 'pending', loading = 0;
    const WIDE = window.matchMedia('(min-width: 1024px)');
    let selKey = null;
    const keyOf = g => g.d + '|' + g.cs_id;
    const mainEl = () => $('tab-approve').closest('main');
    const postJson = async (action, body) => (await fetch(API + '?action=' + action, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })).json();

    // ความสูงส่วนรายการ = พื้นที่ที่เหลือของหน้าจอ (จอคอม: ส่วนแบ่งซ้าย-ขวา · ไม่มีรายการ: กล่องว่างพอดีจอทุกขนาด)
    function fitSplit() {
        const el = $('ap-split') || $('ap-empty'), main = el && el.closest('main');
        if (!main) return;
        const empty = el.id === 'ap-empty';
        if (!WIDE.matches && !empty) { el.style.height = ''; return; }
        const top = el.getBoundingClientRect().top - main.getBoundingClientRect().top + main.scrollTop;
        el.style.height = Math.max(empty ? 220 : 420, main.clientHeight - top - (empty ? 42 : 32)) + 'px';   // กล่องว่าง: ไม่ให้เกิดแถบเลื่อน (ระยะขอบล่างของการ์ด + main)
    }
    window.addEventListener('resize', fitSplit);

    // ตัวเลขบนแท็บ = งานรอตรวจรับทั้งหมดของหน่วยงาน
    function setBadge(n) {
        const b = $('pm-badge-approve');
        if (!b) return;
        b.textContent = n > 99 ? '99+' : n;
        b.classList.toggle('hidden', !n);
    }

    async function loadMe() {
        try { ME = await (await fetch(API + '?action=me')).json(); } catch (e) { ME = null; }
        if (!ME || !ME.success) ME = { is_approver: false, required: 0, approver_count: 0, pending: 0, ready: false };
        $('ap-me').innerHTML = ME.is_approver
            ? `<span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 font-semibold"><i class="fas fa-user-check mr-1"></i>ผู้ตรวจรับ: ${esc(ME.name)}</span>` : '';
        const w = $('ap-warn');
        if (!+ME.required && ME.pending) { w.className = 'mb-3 rounded-xl border px-3 py-2.5 text-sm border-slate-200 bg-slate-50 text-slate-600'; w.innerHTML = '<i class="fas fa-circle-info mr-1"></i> หน่วยงานปิดขั้นตรวจรับแล้ว งานใหม่จะไม่ต้องตรวจรับ — เหลือเฉพาะใบที่รอตรวจรับค้างอยู่'; }
        else w.className = 'hidden';
        setBadge(ME.pending || 0);
    }

    async function load() {
        const my = ++loading;
        if (!$('ap-split')) $('ap-list').innerHTML = '<div class="py-10 text-center text-slate-400 text-sm"><i class="fas fa-circle-notch fa-spin mr-1"></i> กำลังโหลด...</div>';
        $('ap-range').classList.toggle('hidden', status === 'pending');
        $('ap-range').classList.toggle('flex', status !== 'pending');
        const qs = new URLSearchParams({ action: 'groups', status, from: $('ap-from').value, to: $('ap-to').value, cs: $('ap-cs').value });
        let r;
        try { r = await (await fetch(API + '?' + qs)).json(); } catch (e) { r = { success: false, error: e.message }; }
        if (my !== loading) return;
        if (!r.success) { selKey = null; lockMain(); $('ap-list').innerHTML = `<div class="py-6 text-center text-red-600 text-sm">${esc(r.error || 'โหลดไม่สำเร็จ')}</div>`; return; }
        const sel = $('ap-cs'), cur = sel.value;
        if (sel.options.length <= 1 || sel.dataset.n != r.checksheets.length) {
            sel.innerHTML = '<option value="">ทุกเช็คชีต</option>' + r.checksheets.map(c => `<option value="${esc(c.id)}">${esc(c.name)}</option>`).join('');
            sel.value = cur; sel.dataset.n = r.checksheets.length;
        }
        groups = r.data;
        setBadge(r.pending || 0);
        const pend = groups.reduce((s, g) => s + (g.t - g.ok), 0);
        $('ap-count').textContent = `${groups.length} กลุ่ม · รอตรวจรับ ${pend.toLocaleString()} ใบ` + (status === 'pending' ? ' (ทุกวันที่)' : '');
        render();
    }

    function render() {
        if (!groups.length) {
            selKey = null; lockMain();
            $('ap-list').innerHTML = `<div id="ap-empty" class="flex flex-col items-center justify-center text-center text-slate-400 text-sm border border-dashed border-slate-200 rounded-xl"><i class="fas fa-clipboard-check text-4xl text-slate-300 mb-3"></i>${status === 'pending' ? 'ไม่มีงานที่รอตรวจรับ' : 'ไม่พบรายการ'}</div>`;
            fitSplit();
            return;
        }
        if (!groups.some(g => keyOf(g) === selKey)) selKey = WIDE.matches ? keyOf(groups[0]) : null;
        $('ap-list').innerHTML = `<div id="ap-split" class="ap-split">
            <div id="ap-side">${groups.map((g, i) => {
                const pending = g.t - g.ok;
                return `<button type="button" data-sel="${i}" class="ap-side-item ${keyOf(g) === selKey ? 'on' : ''}">
                    <div class="flex items-start gap-2">
                        <span class="w-2 h-2 mt-1.5 rounded-full flex-none ${pending ? 'bg-emerald-100 border border-dashed border-emerald-600' : 'bg-emerald-500'}"></span>
                        <span class="ap-side-name flex-1 min-w-0 break-words">${esc(g.cs || 'ไม่ระบุเช็คชีต')}</span>
                        <span class="text-xs text-slate-500 flex-none">${g.t} ใบ</span>
                        <i class="ap-chev fas fa-chevron-right text-[11px] text-slate-300 mt-1 flex-none"></i>
                    </div>
                    <div class="flex items-center gap-2 mt-1 pl-4 text-xs lg:pr-0 pr-5">
                        <span class="text-slate-500 flex-1">${thDate(g.d)}</span>
                        ${pending ? `<b class="text-emerald-700">รอ ${pending}</b>` : `<b class="text-emerald-600"><i class="fas fa-check"></i> ครบ</b>`}
                    </div>
                </button>`;
            }).join('')}</div>
            <div id="ap-detail"></div></div>`;
        renderDetail();
        fitSplit();
    }

    function renderDetail() {
        const box = $('ap-detail'), i = groups.findIndex(g => keyOf(g) === selKey);
        if (!box) return;
        $('ap-split').classList.toggle('is-detail', i >= 0);
        if (i < 0) { box.innerHTML = ''; lockMain(); return; }
        const g = groups[i], pending = g.t - g.ok;
        const apBtn = () => pending ? `<button type="button" data-ap-group="${i}" class="px-4 py-2 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-semibold inline-flex items-center gap-1.5"><i class="fas fa-check-double"></i> ตรวจรับ${pending > 1 ? 'ทั้งกลุ่ม ' + pending + ' ใบ' : ''}</button>` : '';
        const sub = `${thDate(g.d)} · ${g.t} ใบ${pending ? ` · <b class="text-emerald-700">รอตรวจรับ ${pending}</b>` : ''}${g.ok ? ` · <b class="text-emerald-600">ตรวจรับแล้ว ${g.ok}</b>` : ''}`;
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
                ${apBtn()}
            </div>
            <div class="ap-row ap-row-head !py-2 bg-slate-50 text-[11px] font-semibold text-slate-500 uppercase"><span>เครื่อง / ใบงาน</span><span>สถานะ</span><span></span></div>
            <div data-items="${i}"></div>
            ${pending ? `<div class="ap-bottombar lg:hidden">${apBtn()}</div>` : ''}`;
        box.scrollTop = 0;
        lockMain();
        loadItems(i);
    }

    function lockMain() { const m = mainEl(); if (m) m.style.overflow = !WIDE.matches && selKey != null && $('ap-split')?.classList.contains('is-detail') ? 'hidden' : ''; }
    function selectGroup(i) {
        selKey = keyOf(groups[i]);
        document.querySelectorAll('#ap-side [data-sel]').forEach(b => b.classList.toggle('on', +b.dataset.sel === i));
        renderDetail();
    }
    function backToList() {
        selKey = null;
        document.querySelectorAll('#ap-side [data-sel]').forEach(b => b.classList.remove('on'));
        renderDetail();
    }

    let itemsCache = {};
    async function loadItems(i) {
        const box = document.querySelector(`#ap-detail [data-items="${i}"]`);
        if (!box) return;
        box.innerHTML = '<div class="py-6 text-center text-sm text-slate-400"><i class="fas fa-circle-notch fa-spin mr-1"></i> กำลังโหลด...</div>';
        const g = groups[i];
        let r;
        try { r = await (await fetch(`${API}?action=items&date=${g.d}&cs=${encodeURIComponent(g.cs_id)}`)).json(); } catch (e) { r = { success: false, error: e.message }; }
        if (!box.isConnected) return;
        if (!r.success) { box.innerHTML = `<div class="py-6 text-center text-sm text-red-600">${esc(r.error || 'โหลดไม่สำเร็จ')}</div>`; return; }
        itemsCache = {};
        r.data.forEach(x => { itemsCache[x.id] = x; });
        box.innerHTML = r.data.map(x => {
            const ok = +x.approval_status === 1;
            return `<div class="ap-row">
                <div class="min-w-0">
                    <div class="font-semibold text-slate-800 truncate">${esc(x.machine_code || '-')} <span class="font-normal text-slate-600">${esc(x.machine_name || '')}</span></div>
                    <div class="text-xs text-slate-500 truncate">${esc(x.doc_no || '')} · ผู้ตรวจ ${esc(x.inspector_name || '-')} · ทำ ${esc(thDate(x.actual_date))}${x.location ? ' · ' + esc(x.location) : ''}</div>
                    ${ok && x.approve_comment ? `<div class="text-xs text-slate-500 truncate">หมายเหตุ: ${esc(x.approve_comment)}</div>` : ''}
                </div>
                <div>${ok ? `<span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 font-semibold text-xs whitespace-nowrap" title="${esc(x.approver_name || '')} · ${esc(thDateTime(x.approved_at))}"><i class="fas fa-check"></i> ตรวจรับแล้ว</span>
                            <div class="text-[11px] text-slate-500 mt-0.5 truncate">${esc(x.approver_name || '')}</div>
                            ${x.approve_signature ? `<img src="${esc(x.approve_signature)}" alt="ลายมือชื่อผู้ตรวจรับ" class="h-7 max-w-[7.5rem] object-contain mt-0.5">` : ''}`
                          : '<span class="px-2 py-0.5 rounded-full pm-review font-semibold text-xs whitespace-nowrap">รอตรวจรับ</span>'}</div>
                <div class="flex items-center gap-1.5 justify-end">
                    <button type="button" data-ws="${x.id}" class="px-3 py-1.5 rounded-lg border border-slate-200 text-[#006B9F] text-xs font-semibold hover:bg-sky-50 inline-flex items-center gap-1.5"><i class="fas fa-file-lines"></i> ดูใบงาน</button>
                    ${!ok ? `<button type="button" data-return="${x.id}" class="px-3 py-1.5 rounded-lg border border-red-200 text-red-600 text-xs font-semibold hover:bg-red-50 inline-flex items-center gap-1.5"><i class="fas fa-rotate-left"></i> ส่งกลับ</button>
                             <button type="button" data-ap-one="${x.id}" class="px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold inline-flex items-center gap-1.5"><i class="fas fa-check"></i> ตรวจรับ</button>` : ''}
                </div>
            </div>`;
        }).join('') || '<div class="py-10 text-center text-sm text-slate-400">ไม่มีใบงาน</div>';
    }

    // ตรวจรับ ids (ส่งเป็นชุดละ 100) — ลายมือชื่อบังคับ หมายเหตุไม่บังคับ · ชุดแรกส่งรูปลายเซ็น ชุดต่อไปใช้ไฟล์เดิม (signature_path)
    async function approve(ids, title) {
        const value = await window.pmApproveSign({ title, text: `ยืนยันว่าตรวจงาน${ids.length > 1 ? 'ทั้ง ' + ids.length + ' ใบ' : ''}แล้ว ผลถูกต้อง — นับเป็นงานที่ทำเสร็จ` });
        if (!value) return;
        let sigPath = '';
        Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const fails = [];
        let ok = 0;
        for (let k = 0; k < ids.length; k += 100) {
            let r;
            const body = { event_ids: ids.slice(k, k + 100), comment: value.comment };
            if (sigPath) body.signature_path = sigPath; else body.signature = value.signature;
            try { r = await postJson('approve', body); } catch (e) { r = { success: false, error: e.message }; }
            if (!r.success) { fails.push(r.error || 'บันทึกไม่สำเร็จ'); break; }
            sigPath = r.signature_path || sigPath;
            r.results.forEach(x => x.success ? ok++ : fails.push(x.error));
        }
        if (fails.length) Swal.fire({ icon: ok ? 'warning' : 'error', title: ok ? `ตรวจรับแล้ว ${ok} ใบ` : 'ตรวจรับไม่สำเร็จ', html: [...new Set(fails)].map(esc).join('<br>') });
        else Swal.fire({ icon: 'success', title: `ตรวจรับแล้ว ${ok} ใบ`, timer: 1400, showConfirmButton: false });
        await load();
        if (window.pmRefreshBadges) window.pmRefreshBadges();   // งานที่ตรวจรับแล้วไปรอประเมิน
    }

    async function returnOne(id) {
        const x = itemsCache[id] || {};
        const { isConfirmed, value } = await Swal.fire({
            title: 'ส่งกลับแก้ไข', icon: 'warning',
            html: `<div class="text-sm text-left text-slate-600 space-y-1">
                    <div><b>${esc(x.machine_code || '')}</b> ${esc(x.machine_name || '')} · ${esc(x.doc_no || '')}</div>
                    <ul class="list-disc pl-5 text-xs text-slate-500">
                        <li>ผลบันทึกเดิม (ผลตรวจ รูป ลายเซ็น) จะถูกยกเลิก อะไหล่ที่ตัดไปคืนเข้าสต็อก</li>
                        <li>งานกลับเป็น "ยังไม่ทำ" ช่างเห็นเหตุผลตอนเปิดใบงาน และบันทึกใหม่ด้วยเลขที่เอกสารเดิม</li>
                    </ul></div>`,
            input: 'textarea', inputPlaceholder: 'สิ่งที่ต้องแก้ไข (อย่างน้อย 5 ตัวอักษร)', inputAttributes: { maxlength: 500 },
            inputValidator: v => (v || '').trim().length < 5 ? 'กรุณาระบุสิ่งที่ต้องแก้ไขอย่างน้อย 5 ตัวอักษร' : undefined,
            showCancelButton: true, confirmButtonText: '<i class="fas fa-rotate-left"></i> ส่งกลับ', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#dc2626', reverseButtons: true,
        });
        if (!isConfirmed) return;
        Swal.fire({ title: 'กำลังส่งกลับ...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        let r;
        try { r = await postJson('return', { event_id: +id, reason: value.trim() }); } catch (e) { r = { success: false, error: e.message }; }
        if (!r.success) { Swal.fire('ส่งกลับไม่สำเร็จ', r.error || '', 'error'); return; }
        Swal.fire({ icon: 'success', title: 'ส่งกลับให้ช่างแก้ไขแล้ว', text: `${r.doc_no || ''} (แก้ไขครั้งที่ ${r.revision})`, timer: 1800, showConfirmButton: false });
        await load();
        if (window.pmRefreshBadges) window.pmRefreshBadges();   // งานที่ส่งกลับไปอยู่ในงานที่ต้องทำ
    }

    document.addEventListener('click', async e => {
        if (!e.target.closest('#tab-approve')) return;
        const sl = e.target.closest('#ap-side [data-sel]');
        if (sl) { selectGroup(+sl.dataset.sel); return; }
        if (e.target.closest('#ap-detail [data-back]')) { backToList(); return; }
        const st = e.target.closest('#ap-status [data-s]');
        if (st) { status = st.dataset.s; syncStatus(); load(); return; }
        const ag = e.target.closest('[data-ap-group]');
        if (ag) {
            const g = groups[+ag.dataset.apGroup];
            let r;
            try { r = await (await fetch(`${API}?action=items&pending=1&date=${g.d}&cs=${encodeURIComponent(g.cs_id)}`)).json(); } catch (er) { r = { success: false, error: er.message }; }
            if (!r.success) { Swal.fire('โหลดไม่สำเร็จ', r.error || '', 'error'); return; }
            if (!r.data.length) { load(); return; }
            approve(r.data.map(x => +x.id), `ตรวจรับ ${g.cs || ''} (${r.data.length} ใบ)`);
            return;
        }
        const ao = e.target.closest('[data-ap-one]');
        if (ao) { approve([+ao.dataset.apOne], 'ตรวจรับใบงานนี้'); return; }
        const rt = e.target.closest('[data-return]');
        if (rt) { returnOne(rt.dataset.return); return; }
        const ws = e.target.closest('[data-ws]');
        if (ws) { window.openPmWorksheetDialog(ws.dataset.ws, true); return; }
    });
    const syncStatus = () => document.querySelectorAll('#ap-status [data-s]').forEach(b => b.classList.toggle('on', b.dataset.s === status));
    ['ap-from', 'ap-to', 'ap-cs'].forEach(id => $(id).addEventListener('change', load));
    // ปิดใบงาน (อาจตรวจรับ/ส่งกลับจากในใบงาน) => โหลดใหม่
    window.addEventListener('pm:batch-closed', () => { if (!$('tab-approve').classList.contains('hidden')) load(); });

    WIDE.addEventListener('change', () => { if (ME) render(); });
    new MutationObserver(() => { if (!$('tab-approve').classList.contains('hidden')) fitSplit(); })
        .observe($('tab-approve'), { attributes: true, attributeFilter: ['class'] });

    window.reloadApproveTab = load;
    pmLazy('approve', async () => {
        const now = new Date();
        $('ap-from').value = ymd(new Date(now.getFullYear(), now.getMonth() - 1, 1));
        $('ap-to').value = ymd(now);
        syncStatus();
        await loadMe();
        load();
    });
})();
</script>
