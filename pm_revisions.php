<?php
// pm_revisions.php — แท็บ "ประวัติการแก้ไข PM" (Super Admin) ถูก include จาก pm.php
//  มอนิเตอร์ใบงานที่ถูกยกเลิกผลบันทึก (รอทำใหม่ / ทำใหม่แล้ว) + รีเซ็ตใบงานที่บันทึกแล้วให้ทำใหม่
//  API: handle_pm_work_cancel.php (logs / search / info / cancel)
@session_start();
include_once "config_ctrl/checksession.php";
if (($_SESSION['sess_user_level_es'] ?? '') !== 'super_admin') return;
?>
<style>
    #tab-revisions .rv-chip { height: 32px; padding: 0 12px; border-radius: 999px; border: 1px solid #e2e8f0; font-size: 12px; font-weight: 600; color: #475569; background: #fff; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px; }
    #tab-revisions .rv-chip.on { background: #0f172a; border-color: #0f172a; color: #fff; }
    #tab-revisions .rv-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; white-space: nowrap; border: 1px solid; }
</style>
<div id="tab-revisions" class="tab-content hidden">
    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-800 flex items-center gap-2"><i data-lucide="history" class="w-5 h-5 text-amber-500"></i> ประวัติการแก้ไข PM <span class="text-[11px] font-semibold text-red-600 bg-red-50 border border-red-100 rounded-full px-2 py-0.5">Super Admin</span></h3>
                <p class="text-[12px] text-slate-500 mt-0.5">ใบงานที่ถูกยกเลิกผลบันทึกแล้วส่งกลับให้ทำใหม่ — ติดตามว่าทำใหม่แล้วหรือยัง ดูผลเดิมก่อนยกเลิก และรีเซ็ตใบงานอื่นให้ทำใหม่ได้จากที่นี่</p>
            </div>
            <button id="rv-reset-btn" type="button" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 shrink-0"><i data-lucide="rotate-ccw" class="w-4 h-4"></i> รีเซ็ตงานให้ทำใหม่</button>
        </div>

        <div class="flex flex-col lg:flex-row gap-2 lg:items-center mb-3">
            <div class="flex flex-wrap gap-1.5" id="rv-status">
                <button type="button" class="rv-chip on" data-st="">ทั้งหมด <b id="rv-n-total">0</b></button>
                <button type="button" class="rv-chip" data-st="redo"><span class="w-2 h-2 rounded-full bg-amber-500"></span>รอทำใหม่ <b id="rv-n-redo">0</b></button>
                <button type="button" class="rv-chip" data-st="done"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>ทำใหม่แล้ว <b id="rv-n-done">0</b></button>
            </div>
            <div class="relative lg:ml-auto lg:w-80">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input id="rv-q" type="search" placeholder="ค้นหาเลขที่เอกสาร / เครื่อง / เหตุผล / ผู้ยกเลิก" class="w-full border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
            </div>
        </div>

        <!-- สูงเต็มพื้นที่ที่เหลือของจอ (pmFillRest) — รายการเยอะเลื่อนในกล่องนี้ · ไม่มีรายการ: ข้อความกลางกล่อง -->
        <div id="rv-wrap" class="overflow-auto border border-slate-200 rounded-lg flex flex-col" style="min-height: 160px">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600 text-[12px] sticky top-0 z-10">
                    <tr>
                        <th class="p-2 text-left whitespace-nowrap">ยกเลิกเมื่อ</th>
                        <th class="p-2 text-left whitespace-nowrap">เลขที่เอกสาร</th>
                        <th class="p-2 text-left">เครื่อง / เช็คชีต</th>
                        <th class="p-2 text-left whitespace-nowrap">วันกำหนด</th>
                        <th class="p-2 text-left">เหตุผล</th>
                        <th class="p-2 text-left whitespace-nowrap">สถานะปัจจุบัน</th>
                        <th class="p-2 text-right whitespace-nowrap"></th>
                    </tr>
                </thead>
                <tbody id="rv-body"><tr><td colspan="7" class="p-6 text-center text-slate-400">กำลังโหลด...</td></tr></tbody>
            </table>
            <div id="rv-empty" class="hidden flex-1 flex-col items-center justify-center text-center text-slate-400 text-sm p-6">
                <i data-lucide="history" class="w-10 h-10 text-slate-300 mb-3"></i><span id="rv-empty-text"></span>
            </div>
        </div>
    </div>
</div>

<script>
pmLazy('revisions', function () {
    const $ = id => document.getElementById(id);
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const thD = s => { if (!s) return '-'; const d = new Date(String(s).slice(0, 10) + 'T00:00:00'); return isNaN(d) ? s : d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' }); };
    const thDT = s => { if (!s) return '-'; const d = new Date(String(s).replace(' ', 'T')); return isNaN(d) ? s : thD(s) + ' ' + d.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) + ' น.'; };
    let ROWS = [], status = '', today = '', qTimer = null;

    function stateBadge(r) {
        if (r.state === 'done') return `<span class="rv-badge bg-emerald-50 text-emerald-700 border-emerald-200"><i class="fas fa-check"></i> ทำใหม่แล้ว</span><div class="text-[11px] text-slate-500 mt-0.5">${esc(thD(r.new_actual_date))}${r.new_inspector ? ' · ' + esc(r.new_inspector) : ''}</div>`;
        if (r.state === 'redo') {
            const late = r.event_date && today && r.event_date < today;
            const days = late ? Math.round((new Date(today) - new Date(r.event_date)) / 86400000) : 0;
            return `<span class="rv-badge bg-amber-50 text-amber-700 border-amber-200"><i class="fas fa-hourglass-half"></i> รอทำใหม่</span>${late ? `<div class="text-[11px] text-red-600 mt-0.5">เลยกำหนด ${days} วัน</div>` : ''}`;
        }
        if (r.state === 'superseded') return `<span class="rv-badge bg-slate-50 text-slate-500 border-slate-200">ถูกยกเลิกซ้ำภายหลัง</span>`;
        return `<span class="rv-badge bg-slate-50 text-slate-400 border-slate-200">กำหนดการถูกลบแล้ว</span>`;
    }

    function render() {
        $('rv-body').innerHTML = ROWS.length ? ROWS.map((r, i) => `
            <tr class="border-t border-slate-100 align-top hover:bg-slate-50/60">
                <td class="p-2 whitespace-nowrap">${esc(thDT(r.created_at))}<div class="text-[11px] text-slate-500">โดย ${esc(r.created_by_name || r.created_by || '-')}</div></td>
                <td class="p-2 whitespace-nowrap font-medium text-slate-700">${esc(r.doc_no || '-')}<div class="text-[11px] text-amber-700">แก้ไขครั้งที่ ${r.revision}</div></td>
                <td class="p-2 min-w-[12rem]"><b class="text-slate-700">${esc(r.machine_code || '')}</b> ${esc(r.machine_name || '')}<div class="text-[11px] text-slate-500">${esc(r.checksheet || '')}</div></td>
                <td class="p-2 whitespace-nowrap">${esc(thD(r.event_date))}</td>
                <td class="p-2 min-w-[14rem] text-slate-700">${esc(r.reason)}</td>
                <td class="p-2">${stateBadge(r)}</td>
                <td class="p-2 text-right whitespace-nowrap">
                    <button type="button" data-old="${i}" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">ผลเดิม</button>
                    ${r.state === 'redo' || r.state === 'done' ? `<button type="button" data-open="${i}" class="px-2.5 py-1.5 rounded-lg ${r.state === 'redo' ? 'bg-sky-600 hover:bg-sky-700' : 'bg-emerald-600 hover:bg-emerald-700'} text-white text-xs font-semibold">${r.state === 'redo' ? 'เปิดใบงาน' : 'ดูใบงานใหม่'}</button>` : ''}
                </td>
            </tr>`).join('') : '';
        $('rv-empty-text').textContent = status || $('rv-q').value ? 'ไม่พบรายการตามเงื่อนไข' : 'ยังไม่มีการยกเลิกผลบันทึก PM ในหน่วยงานนี้';
        $('rv-empty').classList.toggle('hidden', !!ROWS.length); $('rv-empty').classList.toggle('flex', !ROWS.length);
    }

    async function load() {
        $('rv-empty').classList.add('hidden'); $('rv-empty').classList.remove('flex');
        $('rv-body').innerHTML = '<tr><td colspan="7" class="p-6 text-center text-slate-400">กำลังโหลด...</td></tr>';
        try {
            const r = await (await fetch('handle_pm_work_cancel.php?action=logs&' + new URLSearchParams({ q: $('rv-q').value.trim(), status }))).json();
            if (!r.success) throw new Error(r.error);
            ROWS = r.rows; today = r.today || '';
            $('rv-n-total').textContent = r.stats.total; $('rv-n-redo').textContent = r.stats.redo; $('rv-n-done').textContent = r.stats.done;
            render();
        } catch (e) {
            $('rv-body').innerHTML = `<tr><td colspan="7" class="p-6 text-center text-red-500">${esc(e.message || 'โหลดไม่สำเร็จ')}</td></tr>`;
        }
    }

    // ผลบันทึกเดิมก่อนยกเลิก (สรุปที่เก็บไว้ใน log)
    function showOld(r) {
        const s = r.snapshot || {};
        const st = { Pass: ['ปกติ', 'text-emerald-600'], Fail: ['ผิดปกติ', 'text-red-600'], 'N/A': ['N/A', 'text-slate-500'] };
        const items = (s.items || []).map((it, k) => {
            const x = st[it.result_status] || [it.result_status || '-', 'text-slate-500'];
            return `<tr class="border-t border-slate-100"><td class="py-1.5 pr-2 text-slate-400">${k + 1}</td><td class="py-1.5 pr-2">${esc(it.check_point)}</td><td class="py-1.5 pr-2 font-semibold ${x[1]} whitespace-nowrap">${esc(x[0])}</td><td class="py-1.5 whitespace-nowrap">${it.actual_value ? esc(it.actual_value) + ' ' + esc(it.unit || '') : ''}</td></tr>`;
        }).join('');
        Swal.fire({
            title: 'ผลบันทึกเดิม (ก่อนยกเลิก)', width: '40rem', confirmButtonText: 'ปิด', confirmButtonColor: '#006b9f',
            html: `<div class="text-left text-sm space-y-2">
                <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-[13px]">
                    <div><span class="text-slate-500">เลขที่เอกสาร</span> <b>${esc(r.doc_no || '-')}</b></div>
                    <div><span class="text-slate-500">ทำเมื่อ</span> ${esc(thD(s.actual_date))}</div>
                    <div class="col-span-2"><span class="text-slate-500">ผู้ตรวจ</span> ${esc((s.inspectors || []).filter(Boolean).join(', ') || '-')}</div>
                    ${s.remarks ? `<div class="col-span-2"><span class="text-slate-500">หมายเหตุ</span> ${esc(s.remarks)}</div>` : ''}
                    ${s.evaluated ? '<div class="col-span-2 text-amber-700">ใบงานเดิมประเมินแล้ว (ผลประเมินถูกลบเมื่อยกเลิก)</div>' : ''}
                    ${(s.spares || []).length ? `<div class="col-span-2"><span class="text-slate-500">อะไหล่ที่คืนสต็อก</span> ${s.spares.map(p => esc(p.name || 'อะไหล่') + ' × ' + (+p.qty)).join(', ')}</div>` : ''}
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-2 text-[13px]"><b>เหตุผลที่ยกเลิก:</b> ${esc(r.reason)} <span class="text-slate-500">· ${esc(r.created_by_name || r.created_by || '')} ${esc(thDT(r.created_at))}</span></div>
                ${items ? `<table class="w-full text-[13px]"><thead><tr class="text-[11px] text-slate-500 text-left"><th>#</th><th>จุดตรวจ</th><th>ผลเดิม</th><th>ค่าที่วัด</th></tr></thead><tbody>${items}</tbody></table>` : '<div class="text-slate-400">ไม่มีข้อมูลผลตรวจเดิม</div>'}
            </div>`
        });
    }

    // รีเซ็ตใบงานที่บันทึกแล้วให้ทำใหม่: ค้นหา => เลือก => เหตุผล => ยกเลิก (กติกาเดียวกับปุ่ม "ยกเลิกผล" ในใบงาน)
    async function resetFlow() {
        let picked = null, timer = null, rows = [];
        const pick = await Swal.fire({
            title: 'รีเซ็ตงานให้ทำใหม่', width: '42rem', showConfirmButton: false, showCloseButton: true,
            html: `<div class="text-left">
                <p class="text-[13px] text-slate-500 mb-2">ค้นหาใบงาน PM ที่บันทึกแล้ว (เลขที่เอกสาร / รหัส / ชื่อเครื่อง) แล้วเลือกใบที่ต้องการให้ทำใหม่</p>
                <input id="rv-sq" type="search" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none" placeholder="พิมพ์อย่างน้อย 2 ตัวอักษร เช่น FM-PM-EL-01-6909 หรือ EL-A12" autocomplete="off">
                <div id="rv-sres" class="mt-2 max-h-[50vh] overflow-y-auto space-y-1.5"></div>
            </div>`,
            didOpen: popup => {
                const inp = popup.querySelector('#rv-sq'), box = popup.querySelector('#rv-sres');
                inp.focus();
                inp.addEventListener('input', () => {
                    clearTimeout(timer);
                    const q = inp.value.trim();
                    if (q.length < 2) { box.innerHTML = ''; return; }
                    box.innerHTML = '<div class="text-xs text-slate-400 py-2">กำลังค้นหา...</div>';
                    timer = setTimeout(async () => {
                        const r = await (await fetch('handle_pm_work_cancel.php?action=search&q=' + encodeURIComponent(q))).json().catch(() => ({ success: false }));
                        if (inp.value.trim() !== q) return;
                        rows = r.success ? r.rows : [];
                        box.innerHTML = rows.length ? rows.map((x, i) => `
                            <button type="button" data-pick="${i}" class="w-full text-left px-3 py-2 rounded-lg border border-slate-200 hover:border-red-300 hover:bg-red-50/50 text-sm">
                                <div class="flex items-center gap-2"><b class="text-slate-800">${esc(x.doc_no)}</b>${+x.evaluation_status === 1 ? '<span class="rv-badge bg-violet-50 text-violet-700 border-violet-200">ประเมินแล้ว</span>' : ''}<span class="ml-auto text-xs text-slate-500">ทำเมื่อ ${esc(thD(x.actual_date))}</span></div>
                                <div class="text-xs text-slate-600 mt-0.5">${esc(x.machine_code || '')} ${esc(x.machine_name || '')} · ${esc(x.checksheet || '')}</div>
                            </button>`).join('') : '<div class="text-xs text-slate-400 py-2">ไม่พบใบงานที่บันทึกแล้ว</div>';
                    }, 300);
                });
                box.addEventListener('click', e => { const b = e.target.closest('[data-pick]'); if (b) { picked = rows[+b.dataset.pick]; Swal.clickConfirm(); } });
            },
            preConfirm: () => true
        });
        if (!pick.isConfirmed || !picked) return;

        const info = await (await fetch('handle_pm_work_cancel.php?action=info&event_id=' + picked.event_id)).json().catch(() => ({ success: false, error: 'เชื่อมต่อไม่สำเร็จ' }));
        if (!info.success) { Swal.fire('รีเซ็ตไม่ได้', info.error, 'error'); return; }
        const spares = (info.spares || []).map(x => '<li>' + esc(x.name || 'อะไหล่') + ' × ' + x.qty + '</li>').join('');
        const c = await Swal.fire({
            title: 'รีเซ็ตให้ทำใหม่?', icon: 'warning', width: '34rem',
            html: `<div class="text-left text-sm space-y-2">
                <div><b>${esc(picked.doc_no)}</b> · ${esc(picked.machine_code || '')} ${esc(picked.machine_name || '')}</div>
                <ul class="list-disc pl-5 space-y-1 text-slate-600">
                    <li>ลบผลตรวจ รูป และลายเซ็นเดิม แล้วตั้งงานกลับเป็น <b>"ยังไม่ทำ"</b> ในวันเดิม</li>
                    <li>บันทึกใหม่จะใช้เลขที่เอกสารเดิม (แก้ไขครั้งที่ ${(info.revision || 0) + 1})</li>
                    ${spares ? `<li>คืนสต็อกอะไหล่:<ul class="list-[circle] pl-5">${spares}</ul></li>` : ''}
                    ${info.evaluated ? '<li class="text-red-600"><b>ใบงานนี้ประเมินแล้ว — ผลประเมินจะถูกลบด้วย</b></li>' : ''}
                </ul>
                <label class="block font-medium text-slate-700 pt-1">เหตุผล <span class="text-red-500">*</span></label></div>`,
            input: 'textarea', inputPlaceholder: 'เช่น บันทึกผลปกติผิด หน้างานจริงพบความผิดปกติ', inputAttributes: { maxlength: 500 },
            showCancelButton: true, confirmButtonText: 'รีเซ็ตให้ทำใหม่', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#dc2626', focusCancel: true,
            preConfirm: v => { if ((v || '').trim().length < 5) { Swal.showValidationMessage('กรุณาระบุเหตุผลอย่างน้อย 5 ตัวอักษร'); return false; } return v.trim(); }
        });
        if (!c.isConfirmed) return;
        Swal.fire({ title: 'กำลังรีเซ็ต...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const fd = new FormData(); fd.append('event_id', picked.event_id); fd.append('reason', c.value);
        const d = await (await fetch('handle_pm_work_cancel.php?action=cancel', { method: 'POST', body: fd })).json().catch(() => ({ success: false, error: 'เชื่อมต่อไม่สำเร็จ' }));
        if (!d.success) { Swal.fire('รีเซ็ตไม่สำเร็จ', d.error, 'error'); return; }
        await Swal.fire({ icon: 'success', title: 'รีเซ็ตแล้ว', html: `งานกลับเป็น "ยังไม่ทำ" — บันทึกใหม่จะใช้เลขที่เอกสาร <b>${esc(d.doc_no)}</b> (แก้ไขครั้งที่ ${d.revision})${d.spares_returned ? `<br>คืนสต็อกอะไหล่ ${d.spares_returned} รายการ` : ''}`, confirmButtonColor: '#006b9f' });
        status = ''; document.querySelectorAll('#rv-status .rv-chip').forEach(b => b.classList.toggle('on', b.dataset.st === ''));
        load();
    }

    $('rv-status').addEventListener('click', e => {
        const b = e.target.closest('[data-st]'); if (!b) return;
        status = b.dataset.st;
        document.querySelectorAll('#rv-status .rv-chip').forEach(x => x.classList.toggle('on', x === b));
        load();
    });
    $('rv-q').addEventListener('input', () => { clearTimeout(qTimer); qTimer = setTimeout(load, 350); });
    $('rv-body').addEventListener('click', e => {
        const o = e.target.closest('[data-old]'); if (o) { showOld(ROWS[+o.dataset.old]); return; }
        const p = e.target.closest('[data-open]');
        if (p) { const r = ROWS[+p.dataset.open]; window.openPmWorksheetDialog(r.plan_event_id, r.state === 'done'); }
    });
    $('rv-reset-btn').addEventListener('click', resetFlow);
    window.addEventListener('pm:batch-closed', () => { if (!$('tab-revisions').classList.contains('hidden')) load(); });   // ปิดใบงานแล้วสถานะอาจเปลี่ยน
    pmFillRest('revisions', $('rv-wrap'), 160);   // ตารางสูงพอดีจอ แม้มีรายการน้อย/ไม่มี
    if (window.lucide) lucide.createIcons();
    load();
});
</script>
