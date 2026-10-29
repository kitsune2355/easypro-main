<?php
// pm_evaluate_batch.php — ประเมินผลงาน PM ทีละใบหรือรวมหลายใบ (?ids=1,2,3 หรือ ?cs=<checksheet>&date=YYYY-MM-DD)
@session_start();
include "config_ctrl/checksession.php";
include "config_ctrl/connect.php";
// เฉพาะ super_admin และผู้ประเมิน PM ของหน่วยงาน
$pmCanEvaluate = (($_SESSION['sess_user_level_es'] ?? '') === 'super_admin');
if (!$pmCanEvaluate) {
    try {
        $stEv = $connect->prepare("SELECT 1 FROM pm_evaluators WHERE ag_id = ? AND user_id = ? LIMIT 1");
        $agEv = (int)($_SESSION['sess_user_agency'] ?? 0);
        $uidEv = (string)($_SESSION['sess_user_id_es'] ?? '');
        $stEv->bind_param('is', $agEv, $uidEv);
        $stEv->execute();
        $pmCanEvaluate = (bool)$stEv->get_result()->fetch_row();
        $stEv->close();
    } catch (Throwable $e) { $pmCanEvaluate = false; }   // ยังไม่มีตาราง = ยังไม่ได้ตั้งผู้ประเมิน
}
if (!$pmCanEvaluate) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>ประเมินผลงาน PM</title>'
        . '<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;600&display=swap" rel="stylesheet"></head>'
        . '<body style="margin:0;font-family:Kanit,sans-serif;background:#f1f5f9;display:grid;place-items:center;min-height:100vh;padding:16px">'
        . '<div style="max-width:420px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;text-align:center">'
        . '<div style="font-size:40px">🔒</div><h2 style="margin:8px 0 4px;color:#0f172a">ไม่มีสิทธิ์ประเมินผลงาน PM</h2>'
        . '<p style="color:#64748b;font-size:14px;line-height:1.6">ใช้ได้เฉพาะ super admin และผู้ประเมิน PM ของหน่วยงาน<br>กำหนดผู้ประเมินได้ที่ ตั้งค่าข้อมูล → ผู้ประเมิน PM</p>'
        . '</div></body></html>';
    exit;
}
$ids = preg_replace('/[^0-9,]/', '', $_GET['ids'] ?? '');
$cs = preg_replace('/[^0-9a-zA-Z_-]/', '', $_GET['cs'] ?? '');
$date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'] ?? '') ? $_GET['date'] : '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประเมินผลงาน PM</title>
    <script>
        // ขนาดตัวอักษรทั้งโปรแกรม (เมนู ตั้งค่าการแสดงผล)
        (function () { let z = 1; try { z = parseFloat(localStorage.getItem('easypro_font_scale')) || 1; } catch (e) {} if (z !== 1) document.documentElement.style.zoom = Math.min(1.5, Math.max(0.8, z)); })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <style>
        body { font-family: 'Kanit', sans-serif; background: #f1f5f9; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; }
        .sc { width: 2.4rem; height: 2.4rem; border-radius: .6rem; border: 1px solid #e2e8f0; font-weight: 600; color: #64748b; background: #fff; }
        .sc.on { background: #006B9F; border-color: #006B9F; color: #fff; }
        .sc.miss { border-color: #fca5a5; }
        .row-off { opacity: .5; }
        #sig { touch-action: none; }
    </style>
</head>
<body class="text-slate-700 min-h-screen">

<header class="sticky top-0 z-30 bg-[#006B9F] text-white">
    <div class="max-w-6xl mx-auto px-3 sm:px-4 py-3 flex items-center gap-3">
        <button type="button" id="btn-back" onclick="closeMe(true)" class="hidden h-9 px-2.5 rounded-xl bg-white/15 hover:bg-white/25 text-sm font-semibold flex items-center gap-1.5 flex-none"><i class="fa-solid fa-chevron-left"></i><span id="back-label" class="hidden sm:inline">กลับ</span></button>
        <div class="min-w-0 flex-1">
            <h1 class="text-base sm:text-lg font-semibold leading-tight truncate"><i class="fa-solid fa-star-half-stroke mr-1.5"></i>ประเมินผลงาน PM</h1>
            <p id="sub" class="text-xs opacity-90 truncate">กำลังโหลด...</p>
        </div>
        <button type="button" onclick="closeMe(false)" title="ปิด" class="w-9 h-9 rounded-xl bg-white/15 hover:bg-white/25 flex items-center justify-center flex-none"><i class="fa-solid fa-xmark text-lg"></i></button>
    </div>
</header>

<main class="max-w-6xl mx-auto px-3 sm:px-4 py-4 space-y-4 pb-28">
    <div id="not-eval" class="hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 flex gap-3">
        <i class="fa-solid fa-lock mt-0.5"></i>
        <div><b>คุณยังไม่ได้เป็นผู้ประเมิน PM</b> — ดูได้อย่างเดียว ผู้ดูแลระบบกำหนดผู้ประเมินได้ที่ ตั้งค่าข้อมูล → แผน PM → ผู้ประเมิน PM</div>
    </div>

    <!-- 1. ใบงาน -->
    <section class="card p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-bold text-slate-800 flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-[#006B9F] text-white text-xs flex items-center justify-center">1</span> ใบงานที่จะประเมิน <span id="sel-count" class="text-sm font-semibold text-[#006B9F]"></span></h2>
            <div id="sel-btns" class="flex gap-1.5">
                <button type="button" id="btn-all" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold hover:bg-slate-50">เลือกทั้งหมด</button>
                <button type="button" id="btn-none" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold hover:bg-slate-50">ไม่เลือก</button>
            </div>
        </div>
        <div id="items" class="mt-3 space-y-1.5 max-h-[40vh] overflow-y-auto"></div>
    </section>

    <!-- 2. คะแนน -->
    <section class="card p-4 sm:p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-bold text-slate-800 flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-[#006B9F] text-white text-xs flex items-center justify-center">2</span> คะแนนประเมิน <span class="text-xs font-normal text-slate-500">(ใช้กับทุกใบที่เลือก)</span></h2>
            <div class="text-sm">เฉลี่ย <b id="avg" class="text-2xl text-[#006B9F]">0.00</b> <span class="text-slate-400">/ 5.00</span></div>
        </div>
        <p class="text-xs text-slate-500 mt-1">5 = ดีมาก · 4 = ดี · 3 = ปานกลาง · 2 = พอใช้ · 1 = ควรปรับปรุง
            <span class="ml-2">ให้ทุกข้อ: <button type="button" data-all="5" class="text-[#006B9F] font-semibold hover:underline">5</button> · <button type="button" data-all="4" class="text-[#006B9F] font-semibold hover:underline">4</button> · <button type="button" data-all="3" class="text-[#006B9F] font-semibold hover:underline">3</button></span></p>
        <div id="topics" class="mt-3 divide-y divide-slate-100"></div>
    </section>

    <!-- 3. ผู้ประเมิน -->
    <section class="card p-4 sm:p-5">
        <h2 class="font-bold text-slate-800 flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-[#006B9F] text-white text-xs flex items-center justify-center">3</span> ผู้ประเมิน</h2>
        <div class="mt-3 text-sm">ชื่อผู้ประเมิน: <b id="me-name">-</b> <span class="text-xs text-slate-400">(ผู้ที่ล็อกอิน)</span></div>
        <label class="block text-xs font-semibold text-slate-500 mt-3 mb-1">ข้อเสนอแนะ (ใส่ให้ทุกใบ)</label>
        <textarea id="comments" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-sky-500/30" placeholder="ถ้ามี"></textarea>
        <div class="flex items-center justify-between mt-3 mb-1">
            <label class="text-xs font-semibold text-slate-500">ลายมือชื่อผู้ประเมิน (เซ็นครั้งเดียว ใช้กับทุกใบ)</label>
            <button type="button" id="sig-clear" class="text-xs text-slate-500 hover:text-red-500"><i class="fa-solid fa-eraser mr-1"></i>ล้าง</button>
        </div>
        <div class="border-2 border-dashed border-slate-200 rounded-xl bg-slate-50"><canvas id="sig" class="w-full h-36 block"></canvas></div>
    </section>
</main>

<div class="fixed bottom-0 inset-x-0 z-30 bg-white/95 backdrop-blur border-t border-slate-200 shadow-[0_-6px_16px_-10px_rgba(15,23,42,.25)]" style="padding-bottom:env(safe-area-inset-bottom)">
    <div class="max-w-6xl mx-auto px-3 sm:px-4 py-3 flex items-center gap-3">
        <div id="foot" class="flex-1 min-w-0 text-sm text-slate-500"></div>
        <button type="button" id="btn-save" class="px-5 sm:px-8 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-lg inline-flex items-center gap-2 disabled:opacity-40"><i class="fa-solid fa-star"></i><span id="save-txt">บันทึกการประเมิน</span></button>
    </div>
</div>

<script>
const Q = { ids: <?php echo json_encode($ids); ?>, cs: <?php echo json_encode($cs); ?>, date: <?php echo json_encode($date); ?> };
const EMBED = window.parent !== window;
const $ = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
const thDate = d => { const x = new Date(String(d).slice(0, 10) + 'T00:00:00'); return isNaN(x) ? (d || '-') : x.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' }); };
let ME = null, ITEMS = [], TOPICS = [], sigPad = null, saving = false;
const selected = new Set(), scores = {}, done = new Map();

// ปิดหน้า: dialog ในหน้าวางแผน PM หรือหน้าต่างแยก — goBack = กลับไปหน้าก่อนหน้า (ถ้ามี)
// เปิดมาจากใบงาน (กด "ประเมินใบงานนี้") => กลับนั้นในกรอบเดิม
const WS_BACK = (() => { const b = new URLSearchParams(location.search).get('back') || ''; return /^pm_worksheet\.php\?/.test(b) ? b : ''; })();
async function backToWorksheet(force) {
    if (saving) return;
    if (!force && window.pmDirty() && !(await Swal.fire({ icon: 'warning', title: 'กลับ?', text: 'คะแนนที่กรอกไว้ยังไม่ได้บันทึก ถ้ากลับจะหายไป',
        showCancelButton: true, confirmButtonText: 'กลับเลย', cancelButtonText: 'ประเมินต่อ', confirmButtonColor: '#dc2626', reverseButtons: true })).isConfirmed) return;
    location.replace(WS_BACK);
}
function closeMe(goBack) {
    if (WS_BACK && goBack) return backToWorksheet(goBack === 'force');
    if (EMBED && window.parent.closePmDialog) window.parent.closePmDialog(goBack === 'force' ? true : false, goBack === true || goBack === 'force');
    else window.close();
}
window.pmBatchBusy = () => saving;
window.pmDirty = () => !saving && (Object.keys(scores).length > 0 || (sigPad && !sigPad.isEmpty())) && ![...done.values()].some(v => v.ok);
(function () {   // ปุ่มกลับ เมื่อเปิดจากรายการ
    let back = WS_BACK ? { label: 'กลับ' } : null;
    if (!back) try { back = EMBED && window.parent.pmDialogBack ? window.parent.pmDialogBack() : null; } catch (e) {}
    if (!back) return;
    $('btn-back').classList.remove('hidden'); $('btn-back').title = back.label; $('back-label').textContent = back.label;
})();

async function load() {
    const qs = Q.ids ? `ids=${Q.ids}` : `cs=${encodeURIComponent(Q.cs)}&date=${Q.date}`;
    const [me, it] = await Promise.all([
        fetch('handle_pm_evaluate.php?action=me').then(r => r.json()),
        fetch(`handle_pm_evaluate.php?action=items&pending=1&${qs}`).then(r => r.json())
    ]);
    if (!me.success || !it.success) { $('sub').textContent = (me.error || it.error || 'โหลดข้อมูลไม่สำเร็จ'); return; }
    ME = me; TOPICS = me.topics; ITEMS = it.data;
    ITEMS.forEach(x => selected.add(+x.id));
    $('me-name').textContent = me.name;
    $('not-eval').classList.toggle('hidden', me.is_evaluator);
    const first = ITEMS[0];
    $('sub').textContent = first ? `${first.checksheet_name || ''} · ${thDate(first.event_date)} · ${ITEMS.length} ใบที่รอประเมิน` : 'ไม่มีใบงานที่รอประเมิน';
    renderItems(); renderTopics(); initSig(); update();
}

function renderItems() {
    const left = ITEMS.filter(x => !done.get(+x.id)?.ok);
    $('sel-btns').classList.toggle('hidden', left.length <= 1);
    $('items').innerHTML = left.length ? left.map(x => {
        const on = selected.has(+x.id), st = done.get(+x.id);
        return `<label class="flex items-center gap-3 p-2.5 border ${st && !st.ok ? 'border-red-300 bg-red-50' : 'border-slate-200'} rounded-xl ${on ? '' : 'row-off'} cursor-pointer">
            <input type="checkbox" class="w-5 h-5 accent-[#006B9F] flex-none" data-id="${x.id}" ${on ? 'checked' : ''} ${left.length <= 1 ? 'disabled' : ''}>
            <span class="flex-1 min-w-0">
                <span class="block text-sm font-semibold text-slate-800 truncate">${esc(x.machine_code || '-')} <span class="font-normal text-slate-500">${esc(x.machine_name || '')}</span></span>
                <span class="block text-xs text-slate-500 truncate">${esc(x.doc_no || '')} · ทำเมื่อ ${thDate(x.actual_date)} · ผู้ตรวจ ${esc(x.inspector_name || '-')}</span>
                ${st && !st.ok ? `<span class="block text-xs text-red-600">${esc(st.msg)}</span>` : ''}
            </span>
            <button type="button" data-view="${x.id}" title="ดูรายละเอียดใบงาน" class="flex-none h-9 px-3 rounded-lg border border-slate-200 text-[#006B9F] text-xs font-semibold hover:bg-sky-50 inline-flex items-center gap-1.5"><i class="fa-solid fa-file-lines"></i><span class="hidden sm:inline">ดูใบงาน</span></button>
        </label>`;
    }).join('') : `<div class="py-8 text-center text-slate-400 text-sm">${ITEMS.length ? 'ประเมินครบแล้ว' : 'ไม่มีใบงานที่รอประเมิน'}</div>`;
}
// ดูใบงาน: เปิดใบงานโหมดประวัติซ้อนบนหน้านี้ (ไม่ใช้ dialog ของหน้าหลัก เพื่อไม่ให้คะแนนที่กรอกไว้หาย)
$('items').addEventListener('click', e => {
    const b = e.target.closest('[data-view]');
    if (!b) return;
    e.preventDefault(); e.stopPropagation();   // อยู่ใน label: ไม่ให้ไปติ๊ก/ยกเลิกเลือกใบงาน
    openView(b.dataset.view);
});
function openView(id) {
    closeView();
    const v = document.createElement('div');
    v.id = 'ws-view';
    v.className = 'fixed inset-0 z-[60] bg-slate-900/50 flex items-center justify-center sm:p-3';
    v.innerHTML = '<div class="w-full h-full sm:max-w-4xl sm:rounded-2xl overflow-hidden bg-white shadow-2xl"><iframe class="w-full h-full border-0 block"></iframe></div>';
    v.querySelector('iframe').src = 'pm_worksheet.php?plan_id=' + encodeURIComponent(id) + '&mode=history&noeval=1&embed=1';
    v.addEventListener('click', e => { if (e.target === v) closeView(); });
    document.body.appendChild(v);
    document.body.style.overflow = 'hidden';
}
function closeView() { const v = $('ws-view'); if (v) { v.remove(); document.body.style.overflow = ''; } }
// ใบงานที่ซ้อนอยู่เรียกฟังก์ชันเหล่านี้ของหน้าที่เปิดมัน (ปุ่ม กลับ / ✕ / Esc)
window.pmDialogBack = () => ($('ws-view') ? { label: 'กลับไปหน้าประเมิน' } : null);
window.closePmDialog = () => closeView();
document.addEventListener('keydown', e => { if (e.key === 'Escape' && $('ws-view') && !Swal.isVisible()) closeView(); });

$('items').addEventListener('change', e => { const id = +e.target.dataset.id; if (!id) return; e.target.checked ? selected.add(id) : selected.delete(id); e.target.closest('label').classList.toggle('row-off', !e.target.checked); update(); });
$('btn-all').onclick = () => { ITEMS.forEach(x => { if (!done.get(+x.id)?.ok) selected.add(+x.id); }); renderItems(); update(); };
$('btn-none').onclick = () => { selected.clear(); renderItems(); update(); };

function renderTopics() {
    $('topics').innerHTML = TOPICS.length ? TOPICS.map((t, i) => `
        <div class="py-2.5 flex flex-col sm:flex-row sm:items-center gap-2 justify-between" data-t="${i}">
            <div class="text-sm text-slate-700"><span class="text-slate-400 mr-1">${i + 1}.</span>${esc(t)}</div>
            <div class="flex gap-1.5">${[1, 2, 3, 4, 5].map(v => `<button type="button" class="sc ${scores[t] === v ? 'on' : ''}" data-v="${v}">${v}</button>`).join('')}</div>
        </div>`).join('') : '<div class="py-6 text-center text-red-600 text-sm">ยังไม่มีหัวข้อประเมิน — เพิ่มได้ที่ ตั้งค่าข้อมูล → แผน PM → รายการหัวข้อประเมิน</div>';
}
$('topics').addEventListener('click', e => {
    const b = e.target.closest('.sc'); if (!b) return;
    scores[TOPICS[+b.closest('[data-t]').dataset.t]] = +b.dataset.v;
    renderTopics(); update();
});
document.querySelectorAll('[data-all]').forEach(b => b.onclick = () => { TOPICS.forEach(t => scores[t] = +b.dataset.all); renderTopics(); update(); });

function update() {
    const vals = TOPICS.map(t => scores[t]).filter(Boolean);
    $('avg').textContent = vals.length ? (vals.reduce((a, b) => a + b, 0) / vals.length).toFixed(2) : '0.00';
    const ids = ITEMS.map(x => +x.id).filter(id => selected.has(id) && !done.get(id)?.ok);
    $('sel-count').textContent = ITEMS.length > 1 ? `เลือก ${ids.length} / ${ITEMS.filter(x => !done.get(+x.id)?.ok).length}` : '';
    $('save-txt').textContent = ids.length > 1 ? `บันทึกการประเมิน ${ids.length} ใบ` : 'บันทึกการประเมิน';
    $('btn-save').disabled = !ids.length || !ME || !ME.is_evaluator;
    const okN = [...done.values()].filter(v => v.ok).length;
    $('foot').innerHTML = okN ? `<span class="text-emerald-600 font-semibold">ประเมินแล้ว ${okN} ใบ</span>` : `คะแนนครบ <b>${vals.length}/${TOPICS.length}</b> หัวข้อ`;
}

function initSig() {
    const c = $('sig');
    const fit = () => { const r = window.devicePixelRatio || 1, d = sigPad && !sigPad.isEmpty() ? sigPad.toData() : null; c.width = c.offsetWidth * r; c.height = c.offsetHeight * r; c.getContext('2d').scale(r, r); if (sigPad) { sigPad.clear(); if (d) sigPad.fromData(d); } };
    sigPad = new SignaturePad(c, { penColor: 'rgb(15, 23, 42)', backgroundColor: 'rgba(255,255,255,0)' });
    fit(); window.addEventListener('resize', fit);
    $('sig-clear').onclick = () => sigPad.clear();
}

$('btn-save').onclick = async () => {
    const ids = ITEMS.map(x => +x.id).filter(id => selected.has(id) && !done.get(id)?.ok);
    const errs = [];
    if (!ids.length) errs.push('ยังไม่ได้เลือกใบงาน');
    const miss = TOPICS.filter(t => !scores[t]);
    if (miss.length) errs.push(`ให้คะแนนอีก ${miss.length} หัวข้อ`);
    if (!sigPad || sigPad.isEmpty()) errs.push('ลงลายมือชื่อผู้ประเมิน');
    if (errs.length) {
        document.querySelectorAll('[data-t]').forEach(r => { if (!scores[TOPICS[+r.dataset.t]]) r.querySelectorAll('.sc').forEach(b => b.classList.add('miss')); });
        return Swal.fire({ icon: 'warning', title: 'ข้อมูลยังไม่ครบ', html: errs.map(x => '• ' + esc(x)).join('<br>'), confirmButtonColor: '#006b9f' });
    }
    if (ids.length > 1) {
        const ok = await Swal.fire({ icon: 'question', title: `บันทึกการประเมิน ${ids.length} ใบ?`, text: `คะแนนเฉลี่ย ${$('avg').textContent} — ระบบจะบันทึกผลประเมินแยกให้ทุกใบ`, showCancelButton: true, confirmButtonText: 'บันทึก', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#059669' });
        if (!ok.isConfirmed) return;
    }
    saving = true;
    Swal.fire({ title: 'กำลังบันทึก...', html: `<div class="text-sm text-slate-500 mb-2" id="pg">0 / ${ids.length}</div><div class="h-2.5 bg-slate-100 rounded-full overflow-hidden"><div id="pgb" class="h-full bg-emerald-500" style="width:0%"></div></div>`, allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false });
    let sigPath = '', okN = 0, failN = 0;
    for (let i = 0; i < ids.length; i += 50) {
        const part = ids.slice(i, i + 50);
        const body = { event_ids: part, scores, comments: $('comments').value.trim() };
        if (sigPath) body.signature_path = sigPath; else body.signature = sigPad.toDataURL('image/png');
        try {
            const txt = await (await fetch('handle_pm_evaluate.php?action=save', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })).text();
            let r;
            // ผลไม่ใช่ JSON (เซิร์ฟเวอร์ขัดข้อง) => แสดงข้อความจากเซิร์ฟเวอร์ ให้รู้สาเหตุ
            try { r = JSON.parse(txt); } catch (pe) { throw new Error('เซิร์ฟเวอร์ตอบกลับไม่ถูกต้อง: ' + txt.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 300)); }
            if (!r.success) throw new Error(r.error || 'บันทึกไม่สำเร็จ');
            sigPath = r.signature_path || sigPath;
            r.results.forEach(x => { done.set(+x.event_id, { ok: x.success, msg: x.error || '' }); x.success ? okN++ : failN++; });
        } catch (e) {
            part.forEach(id => done.set(id, { ok: false, msg: e.message })); failN += part.length;
            if (!sigPath) break;
        }
        const n = Math.min(i + 50, ids.length);
        const pg = document.getElementById('pg'), pgb = document.getElementById('pgb');
        if (pg) pg.textContent = `${n} / ${ids.length}`; if (pgb) pgb.style.width = (n / ids.length * 100) + '%';
    }
    saving = false;
    renderItems(); update();
    await Swal.fire({ icon: failN ? 'warning' : 'success', title: failN ? `ประเมินสำเร็จ ${okN} ใบ ไม่สำเร็จ ${failN} ใบ` : `บันทึกการประเมินสำเร็จ${okN > 1 ? ' ' + okN + ' ใบ' : ''}`,
        text: failN ? [...done.values()].find(v => !v.ok)?.msg || '' : '', confirmButtonColor: '#006b9f', timer: failN ? undefined : 1500, showConfirmButton: !!failN });
    if (!failN) closeMe('force');   // สำเร็จทั้งหมด => ปิด (กลับไปรายการถ้าเปิดมาจากรายการ)
};

load();
</script>
</body>
</html>
