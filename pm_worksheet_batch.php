<?php
//pm_worksheet_batch.php — บันทึกใบงาน PM พร้อมกันหลายเครื่อง (เช็คชีตเดียวกัน วันเดียวกัน)
@session_start();
include "config_ctrl/checksession.php";

$checksheet_id = isset($_GET['checksheet_id']) ? intval($_GET['checksheet_id']) : 0;
$date = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']) ? $_GET['date'] : '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกใบงาน PM พร้อมกันหลายเครื่อง</title>
    <script>
        // ขนาดตัวอักษรทั้งโปรแกรม (เมนู ตั้งค่าการแสดงผล) — หน้านี้เปิดเป็นหน้าต่างแยก จึงขยายเองด้วย zoom
        (function () { let z = 1; try { z = parseFloat(localStorage.getItem('easypro_font_scale')) || 1; } catch (e) {} if (z !== 1) document.documentElement.style.zoom = Math.min(1.5, Math.max(0.8, z)); })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Kanit', sans-serif; background: #f1f5f9; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
        .seg button { padding: .4rem .9rem; border-radius: .6rem; font-size: 13px; font-weight: 600; color: #64748b; }
        .seg button.on-pass { background: #10b981; color: #fff; }
        .seg button.on-na { background: #64748b; color: #fff; }
        .row-off { opacity: .55; }
        .row-off .req { display: none; }
        .row-err { border-color: #fca5a5 !important; background: #fff7f7; }
        .row-ok { border-color: #86efac !important; background: #f0fdf4; }
        .ph-btn { width: 3rem; height: 3rem; border-radius: .7rem; border: 1.5px dashed #cbd5e1; display: inline-flex; align-items: center; justify-content: center; color: #94a3b8; overflow: hidden; position: relative; flex: none; }
        .ph-btn.has { border-style: solid; border-color: #10b981; }
        .ph-btn img { width: 100%; height: 100%; object-fit: cover; }
        .ph-btn.miss { border-color: #ef4444; color: #ef4444; }
        .val-in.miss { border-color: #ef4444 !important; }
        #sig { touch-action: none; }
    </style>
</head>
<body class="text-slate-700 min-h-screen">

<header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-slate-200">
    <div class="max-w-5xl mx-auto px-4 py-3 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-[#006B9F] text-white flex items-center justify-center flex-none"><i data-lucide="list-checks" class="w-5 h-5"></i></div>
        <div class="min-w-0 flex-1">
            <h1 class="text-base sm:text-lg font-bold text-slate-800 leading-tight">บันทึกพร้อมกันหลายเครื่อง</h1>
            <p id="hdrSub" class="text-xs text-slate-500 truncate">กำลังโหลด...</p>
        </div>
        <button type="button" onclick="window.close()" class="w-10 h-10 rounded-full border border-slate-200 text-slate-500 hover:bg-slate-50 flex items-center justify-center flex-none" title="ปิด"><i data-lucide="x" class="w-5 h-5"></i></button>
    </div>
</header>

<main class="max-w-5xl mx-auto px-3 sm:px-4 py-4 space-y-4 pb-32">

    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex gap-3">
        <i data-lucide="info" class="w-5 h-5 flex-none mt-0.5 text-amber-500"></i>
        <div>
            <b>ใช้สำหรับเครื่องที่ผลตรวจปกติเหมือนกัน</b> — ระบบจะสร้างใบงานแยกให้ทุกเครื่องที่เลือก (เลขเอกสาร / ประวัติ / รายงาน เป็นรายเครื่องเหมือนเดิม)<br>
            <span class="text-amber-700">เครื่องที่<b>ผิดปกติ</b>หรือมีการ<b>ใช้อะไหล่จริง</b> ให้เอาเครื่องหมาย ✓ ออก แล้วกด <b>เปิดใบงาน</b> เพื่อบันทึกทีละเครื่อง</span>
        </div>
    </div>

    <!-- 1. ผลการตรวจ -->
    <section class="card p-4 sm:p-5">
        <h2 class="font-bold text-slate-800 flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-[#006B9F] text-white text-xs flex items-center justify-center">1</span> ผลการตรวจ <span class="text-xs font-normal text-slate-500">(ใช้กับทุกเครื่องที่เลือก)</span></h2>
        <div id="itemsBox" class="mt-3 divide-y divide-slate-100"></div>
    </section>

    <!-- 2. เครื่อง -->
    <section class="card p-4 sm:p-5">
        <div class="flex flex-wrap items-center gap-2 justify-between">
            <h2 class="font-bold text-slate-800 flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-[#006B9F] text-white text-xs flex items-center justify-center">2</span> เครื่องที่จะบันทึก <span id="selCount" class="text-sm font-semibold text-[#006B9F]"></span></h2>
            <div class="flex gap-1.5">
                <button type="button" id="btnAll" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold hover:bg-slate-50">เลือกทั้งหมด</button>
                <button type="button" id="btnNone" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold hover:bg-slate-50">ไม่เลือก</button>
            </div>
        </div>
        <div id="reqHint" class="hidden mt-2 text-xs text-slate-500"></div>
        <div class="relative mt-3">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input id="q" type="search" placeholder="ค้นหารหัส / ชื่อเครื่อง / สถานที่..." class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-sky-500/30">
        </div>
        <div id="machines" class="mt-3 space-y-2"></div>
        <div id="moreBox" class="hidden mt-3 text-center"><button type="button" id="btnMore" class="px-4 py-2 rounded-lg border border-slate-200 text-sm font-semibold hover:bg-slate-50"></button></div>
    </section>

    <!-- 3. ผู้ตรวจ / ลายเซ็น -->
    <section class="card p-4 sm:p-5">
        <h2 class="font-bold text-slate-800 flex items-center gap-2"><span class="w-6 h-6 rounded-full bg-[#006B9F] text-white text-xs flex items-center justify-center">3</span> ผู้ตรวจสอบ</h2>
        <label class="block text-xs font-semibold text-slate-500 mt-3 mb-1">ชื่อผู้ตรวจสอบ (เลือกได้หลายคน)</label>
        <div class="relative">
            <button type="button" id="inspBtn" class="w-full min-h-[44px] text-left px-3 py-2 border border-slate-200 rounded-xl bg-white flex flex-wrap gap-1.5 items-center"><span class="text-slate-400 text-sm">คลิกเพื่อเลือกชื่อ...</span></button>
            <div id="inspList" class="hidden absolute z-20 mt-1 w-full max-h-64 overflow-y-auto bg-white border border-slate-200 rounded-xl shadow-xl p-1"></div>
        </div>
        <label class="block text-xs font-semibold text-slate-500 mt-3 mb-1">หมายเหตุ (ใส่ให้ทุกเครื่อง)</label>
        <textarea id="remarks" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-sky-500/30" placeholder="ถ้ามี"></textarea>
        <div class="flex items-center justify-between mt-3 mb-1">
            <label class="text-xs font-semibold text-slate-500">ลายมือชื่อผู้ตรวจสอบ (เซ็นครั้งเดียว ใช้กับทุกใบ)</label>
            <button type="button" id="sigClear" class="text-xs text-slate-500 hover:text-red-500 inline-flex items-center gap-1"><i data-lucide="eraser" class="w-3.5 h-3.5"></i>ล้าง</button>
        </div>
        <div class="border-2 border-dashed border-slate-200 rounded-xl bg-slate-50"><canvas id="sig" class="w-full h-40 block"></canvas></div>
    </section>
</main>

<!-- แถบบันทึก ยึดขอบล่าง -->
<div class="fixed bottom-0 inset-x-0 z-30 bg-white/95 backdrop-blur border-t border-slate-200 shadow-[0_-6px_16px_-10px_rgba(15,23,42,.25)]" style="padding-bottom:env(safe-area-inset-bottom)">
    <div class="max-w-5xl mx-auto px-4 py-3 flex items-center gap-3">
        <div id="footInfo" class="flex-1 min-w-0 text-sm text-slate-500"></div>
        <button type="button" id="btnSave" class="px-5 sm:px-8 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm shadow-lg inline-flex items-center gap-2 disabled:opacity-40"><i data-lucide="save" class="w-4 h-4"></i><span id="btnSaveTxt">บันทึก</span></button>
    </div>
</div>

<script>
const CHECKSHEET_ID = <?php echo json_encode($checksheet_id); ?>;
const DATE = <?php echo json_encode($date); ?>;
const AG_ID = <?php echo json_encode(isset($sess_user_agency_es) ? (string)$sess_user_agency_es : ''); ?>;
const PAGE = 100;   // แสดงรายการทีละ 100 เครื่อง (มีหลายร้อยเครื่อง)

let ITEMS = [], EVENTS = [], shown = PAGE, sigPad = null, users = [];
const common = {};          // item_id => 'Pass' | 'N/A'
const selected = new Set(); // event_id ที่จะบันทึก
const perEvent = {};        // event_id => { values:{item_id:v}, photos:{item_id:dataURL} }
const done = new Map();     // event_id => {ok, msg}

const $ = id => document.getElementById(id);
const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
const icons = () => window.lucide && lucide.createIcons();
const isMeas = it => { const v = String(it.expected_value ?? '').trim(); return v !== '' && v !== '-'; };
const needPhoto = it => +it.photo_required_id === 1;
const reqItems = () => ITEMS.filter(it => needPhoto(it) || isMeas(it));
const thDate = d => { const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('th-TH', { day: 'numeric', month: 'long', year: 'numeric' }); };
const pe = id => (perEvent[id] = perEvent[id] || { values: {}, photos: {} });

async function load() {
    if (!CHECKSHEET_ID || !DATE) { $('hdrSub').textContent = 'ข้อมูลไม่ครบ'; return; }
    const r = await (await fetch(`handle_pm_worksheet.php?action=get_batch_data&checksheet_id=${CHECKSHEET_ID}&date=${DATE}`)).json();
    if (!r.success) { Swal.fire('โหลดข้อมูลไม่สำเร็จ', r.error || '', 'error'); return; }
    ITEMS = r.items; EVENTS = r.events;
    ITEMS.forEach(it => common[it.id] = 'Pass');
    EVENTS.forEach(e => selected.add(+e.id));
    $('hdrSub').textContent = `${r.checksheet.name} · ${thDate(DATE)} · ${EVENTS.length} เครื่องที่ยังไม่ได้ทำ`;
    document.title = `บันทึกพร้อมกัน — ${r.checksheet.name}`;
    renderItems(); renderMachines(); loadUsers(); initSig();
}

function renderItems() {
    $('itemsBox').innerHTML = ITEMS.map((it, i) => `
        <div class="py-3 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-slate-800 text-sm">${i + 1}. ${esc(it.check_point)}</div>
                ${it.standard_text ? `<div class="text-xs text-slate-500 mt-0.5">เกณฑ์: ${esc(it.standard_text)}</div>` : ''}
                <div class="flex flex-wrap gap-1.5 mt-1">
                    ${needPhoto(it) ? `<span class="text-[11px] px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 font-semibold">แนบรูปรายเครื่อง</span>` : ''}
                    ${isMeas(it) ? `<span class="text-[11px] px-2 py-0.5 rounded-full bg-violet-50 text-violet-700 font-semibold">กรอกค่ารายเครื่อง: ${esc(it.measurement_name || 'ค่า')} ${it.unit ? '(' + esc(it.unit) + ')' : ''} เกณฑ์ ${esc(it.expected_value)}</span>` : ''}
                </div>
            </div>
            <div class="seg flex gap-1 p-1 bg-slate-100 rounded-xl self-start sm:self-auto" data-item="${it.id}">
                <button type="button" data-v="Pass">ปกติ</button>
                ${+it.check_type_id === 2 ? `<button type="button" data-v="N/A">ไม่เกี่ยวข้อง</button>` : ''}
            </div>
        </div>`).join('');
    syncSeg();
    const req = reqItems();
    if (req.length) {
        $('reqHint').classList.remove('hidden');
        $('reqHint').innerHTML = `<i data-lucide="camera" class="w-3.5 h-3.5 inline -mt-0.5"></i> เช็คชีตนี้ต้อง${req.some(needPhoto) ? 'แนบรูป' : ''}${req.some(needPhoto) && req.some(isMeas) ? 'และ' : ''}${req.some(isMeas) ? 'กรอกค่าที่วัดได้' : ''}ทุกเครื่อง — ทำในแต่ละแถวด้านล่าง`;
    }
    icons();
}
function syncSeg() {
    document.querySelectorAll('.seg').forEach(s => s.querySelectorAll('button').forEach(b => {
        const on = common[s.dataset.item] === b.dataset.v;
        b.classList.toggle('on-pass', on && b.dataset.v === 'Pass');
        b.classList.toggle('on-na', on && b.dataset.v === 'N/A');
    }));
}
$('itemsBox').addEventListener('click', e => {
    const b = e.target.closest('.seg button'); if (!b) return;
    common[b.closest('.seg').dataset.item] = b.dataset.v; syncSeg();
});

function filtered() {
    const q = $('q').value.trim().toLowerCase();
    return EVENTS.filter(e => !done.get(+e.id)?.ok && (!q || `${e.machine_code} ${e.machine_name} ${e.location}`.toLowerCase().includes(q)));
}
function rowHtml(e) {
    const id = +e.id, on = selected.has(id), st = done.get(id), p = pe(id);
    const req = reqItems().map(it => {
        if (needPhoto(it)) {
            const src = p.photos[it.id];
            return `<label class="ph-btn ${src ? 'has' : ''}" data-ph="${it.id}" title="รูป: ${esc(it.check_point)}">
                        ${src ? `<img src="${src}" alt="">` : `<i data-lucide="camera" class="w-5 h-5"></i>`}
                        <input type="file" accept="image/*" class="hidden" data-photo="${it.id}">
                    </label>`;
        }
        return `<input type="text" inputmode="decimal" class="val-in w-24 px-2 py-2 border border-slate-200 rounded-lg text-sm" placeholder="${esc(it.measurement_name || 'ค่า')}" data-val="${it.id}" value="${esc(p.values[it.id] || '')}">`;
    }).join('');
    return `<div class="mrow border border-slate-200 rounded-xl p-2.5 flex flex-wrap sm:flex-nowrap items-center gap-x-3 gap-y-2 ${on ? '' : 'row-off'} ${st && !st.ok ? 'row-err' : ''}" data-id="${id}">
        <input type="checkbox" class="w-5 h-5 accent-[#006B9F] flex-none" ${on ? 'checked' : ''} data-sel>
        <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold text-slate-800 truncate">${esc(e.machine_code || '-')} <span class="font-normal text-slate-500">${esc(e.machine_name || '')}</span></div>
            ${e.location ? `<div class="text-xs text-slate-400 truncate">${esc(e.location)}</div>` : ''}
            ${st && !st.ok ? `<div class="text-xs text-red-600 mt-0.5">${esc(st.msg)}</div>` : ''}
        </div>
        ${req ? `<div class="req order-last sm:order-none w-full sm:w-auto pl-8 sm:pl-0 flex items-center gap-1.5 flex-wrap sm:justify-end">${req}</div>` : ''}
        <a href="pm_worksheet.php?plan_id=${id}" target="_blank" class="flex-none text-xs text-[#006B9F] font-semibold hover:underline whitespace-nowrap" title="บันทึกเครื่องนี้ทีละใบ">เปิดใบงาน</a>
    </div>`;
}
function renderMachines() {
    const list = filtered();
    $('machines').innerHTML = list.length ? list.slice(0, shown).map(rowHtml).join('')
        : `<div class="py-10 text-center text-slate-400 text-sm">${EVENTS.length ? 'ไม่พบเครื่องตามคำค้น' : 'ไม่มีงานที่ยังไม่ได้ทำของเช็คชีตนี้ในวันนี้'}</div>`;
    const rest = list.length - shown;
    $('moreBox').classList.toggle('hidden', rest <= 0);
    $('btnMore').textContent = `แสดงเพิ่ม (${Math.min(PAGE, rest)} จากอีก ${rest} เครื่อง)`;
    updateCount(); icons();
}
function updateCount() {
    const remain = EVENTS.filter(e => !done.get(+e.id)?.ok);
    const n = remain.filter(e => selected.has(+e.id)).length;
    $('selCount').textContent = `เลือก ${n} / ${remain.length}`;
    $('btnSaveTxt').textContent = `บันทึก ${n} เครื่อง`;
    $('btnSave').disabled = n === 0;
    const okN = [...done.values()].filter(v => v.ok).length;
    $('footInfo').innerHTML = okN ? `<span class="text-emerald-600 font-semibold">บันทึกแล้ว ${okN} เครื่อง</span>` : 'ผลตรวจ <b>ปกติ</b> ทุกเครื่องที่เลือก';
}
$('q').addEventListener('input', () => { shown = PAGE; renderMachines(); });
$('btnMore').addEventListener('click', () => { shown += PAGE; renderMachines(); });
$('btnAll').addEventListener('click', () => { filtered().forEach(e => selected.add(+e.id)); renderMachines(); });
$('btnNone').addEventListener('click', () => { filtered().forEach(e => selected.delete(+e.id)); renderMachines(); });
$('machines').addEventListener('change', async e => {
    const row = e.target.closest('.mrow'); if (!row) return;
    const id = +row.dataset.id;
    if (e.target.matches('[data-sel]')) {
        e.target.checked ? selected.add(id) : selected.delete(id);
        row.classList.toggle('row-off', !e.target.checked); updateCount();
    } else if (e.target.matches('[data-photo]')) {
        const f = e.target.files[0]; if (!f) return;
        const url = await shrink(f);
        pe(id).photos[e.target.dataset.photo] = url;
        const lb = e.target.closest('.ph-btn');
        lb.classList.add('has'); lb.classList.remove('miss');
        lb.querySelector('img, svg, i')?.remove();
        lb.insertAdjacentHTML('afterbegin', `<img src="${url}" alt="">`);
    }
});
$('machines').addEventListener('input', e => {
    if (!e.target.matches('[data-val]')) return;
    pe(+e.target.closest('.mrow').dataset.id).values[e.target.dataset.val] = e.target.value.trim();
    e.target.classList.remove('miss');
});

// ย่อรูปก่อนส่ง (ด้านยาวไม่เกิน 1280px, JPEG) — หลายร้อยเครื่องจะได้ไม่หนักเกิน
function shrink(file) {
    return new Promise(res => {
        const img = new Image(), u = URL.createObjectURL(file);
        img.onload = () => {
            const k = Math.min(1, 1280 / Math.max(img.width, img.height));
            const c = document.createElement('canvas');
            c.width = Math.round(img.width * k); c.height = Math.round(img.height * k);
            c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
            URL.revokeObjectURL(u); res(c.toDataURL('image/jpeg', 0.8));
        };
        img.onerror = () => { URL.revokeObjectURL(u); res(''); };
        img.src = u;
    });
}

async function loadUsers() {
    try {
        const r = await (await fetch(`handle_pm_worksheet.php?action=get_users&ag_id=${encodeURIComponent(AG_ID)}`)).json();
        users = r.success ? r.data : [];
    } catch (e) { users = []; }
    $('inspList').innerHTML = users.length ? users.map(u => {
        const name = `${u.user_name || ''} ${u.user_fname || ''}`.trim();
        return `<label class="flex items-center gap-2 p-2 hover:bg-slate-50 rounded-lg cursor-pointer text-sm"><input type="checkbox" class="w-4 h-4" data-uid="${esc(u.user_id)}" data-upk="${esc(u.id)}" data-name="${esc(name)}">${esc(name)}</label>`;
    }).join('') : '<div class="p-3 text-sm text-slate-400 text-center">ไม่พบรายชื่อ</div>';
}
const pickedUsers = () => [...document.querySelectorAll('#inspList input:checked')].map(c => ({ user_id: c.dataset.uid, user_pk_id: c.dataset.upk, name: c.dataset.name }));
$('inspBtn').addEventListener('click', e => { e.stopPropagation(); $('inspList').classList.toggle('hidden'); });
document.addEventListener('click', e => { if (!e.target.closest('#inspList')) $('inspList').classList.add('hidden'); });
$('inspList').addEventListener('change', () => {
    const p = pickedUsers();
    $('inspBtn').innerHTML = p.length ? p.map(u => `<span class="bg-blue-100 text-blue-800 text-xs px-2.5 py-1 rounded-full">${esc(u.name)}</span>`).join('') : '<span class="text-slate-400 text-sm">คลิกเพื่อเลือกชื่อ...</span>';
    $('inspBtn').classList.remove('!border-red-400');
});

function initSig() {
    const c = $('sig');
    const fit = () => { const r = window.devicePixelRatio || 1, d = sigPad && !sigPad.isEmpty() ? sigPad.toData() : null; c.width = c.offsetWidth * r; c.height = c.offsetHeight * r; c.getContext('2d').scale(r, r); if (sigPad) { sigPad.clear(); if (d) sigPad.fromData(d); } };
    sigPad = new SignaturePad(c, { penColor: 'rgb(15, 23, 42)', backgroundColor: 'rgba(255,255,255,0)' });
    fit(); window.addEventListener('resize', fit);
    $('sigClear').addEventListener('click', () => sigPad.clear());
}

$('btnSave').addEventListener('click', save);
async function save() {
    const ids = EVENTS.map(e => +e.id).filter(id => selected.has(id) && !done.get(id)?.ok);
    const errs = [];
    if (!ids.length) errs.push('ยังไม่ได้เลือกเครื่อง');
    if (!pickedUsers().length) { errs.push('เลือกชื่อผู้ตรวจสอบ'); $('inspBtn').classList.add('!border-red-400'); }
    if (!sigPad || sigPad.isEmpty()) errs.push('ลงลายมือชื่อผู้ตรวจสอบ');
    // รูป/ค่าที่ต้องกรอกรายเครื่อง
    const missing = [];
    ids.forEach(id => reqItems().forEach(it => {
        const p = pe(id);
        if (needPhoto(it) ? !p.photos[it.id] : !String(p.values[it.id] || '').trim()) missing.push(id);
    }));
    const missSet = new Set(missing);
    if (missSet.size) {
        errs.push(`${missSet.size} เครื่องยังไม่ได้${reqItems().some(needPhoto) ? 'แนบรูป' : 'กรอกค่า'}ครบ`);
        document.querySelectorAll('.mrow').forEach(r => {
            if (!missSet.has(+r.dataset.id)) return;
            r.querySelectorAll('[data-ph]').forEach(l => { if (!pe(+r.dataset.id).photos[l.dataset.ph]) l.classList.add('miss'); });
            r.querySelectorAll('[data-val]').forEach(i => { if (!i.value.trim()) i.classList.add('miss'); });
        });
    }
    if (errs.length) return Swal.fire({ icon: 'warning', title: 'ข้อมูลยังไม่ครบ', html: errs.map(x => `• ${esc(x)}`).join('<br>'), confirmButtonColor: '#006b9f' });

    const resultsText = ITEMS.map((it, i) => `${i + 1}. ${esc(it.check_point)} — <b>${common[it.id] === 'Pass' ? 'ปกติ' : 'ไม่เกี่ยวข้อง'}</b>`).join('<br>');
    const ok = await Swal.fire({
        icon: 'question', title: `บันทึก ${ids.length} เครื่อง?`,
        html: `<div class="text-left text-sm">ระบบจะสร้างใบงานแยกให้ทั้ง ${ids.length} เครื่อง ด้วยผลตรวจ:<div class="mt-2 p-3 bg-slate-50 rounded-lg">${resultsText}</div></div>`,
        showCancelButton: true, confirmButtonText: 'บันทึก', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#059669'
    });
    if (!ok.isConfirmed) return;

    const items = ITEMS.map(it => ({ item_id: +it.id, status: common[it.id], value: '' }));
    const users = pickedUsers();
    const chunk = reqItems().some(needPhoto) ? 10 : 40;   // มีรูป => ส่งทีละน้อย ไม่ให้ request ใหญ่เกิน
    let sigPath = '', okN = 0, failN = 0;
    Swal.fire({ title: 'กำลังบันทึก...', html: `<div class="text-sm text-slate-500 mb-2"><span id="pgTxt">0 / ${ids.length}</span></div><div class="h-2.5 bg-slate-100 rounded-full overflow-hidden"><div id="pgBar" class="h-full bg-emerald-500 transition-all" style="width:0%"></div></div>`, allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false });

    for (let i = 0; i < ids.length; i += chunk) {
        const part = ids.slice(i, i + chunk);
        const fd = new FormData();
        fd.append('action', 'save_batch');
        fd.append('checksheet_id', CHECKSHEET_ID);
        fd.append('event_ids', JSON.stringify(part));
        fd.append('inspector_name', users.map(u => u.name).join(', '));
        fd.append('inspector_users_json', JSON.stringify(users));
        fd.append('remarks', $('remarks').value.trim());
        fd.append('items', JSON.stringify(items));
        if (sigPath) fd.append('signature_path', sigPath); else fd.append('inspector_signature', sigPad.toDataURL('image/png'));
        const per = {};
        part.forEach(id => {
            const p = pe(id);
            per[id] = { values: p.values };
            Object.entries(p.photos).forEach(([iid, url]) => fd.append(`photo_${id}_${iid}`, url));
        });
        fd.append('per_event', JSON.stringify(per));
        try {
            const r = await (await fetch('handle_pm_worksheet.php', { method: 'POST', body: fd })).json();
            if (!r.success) throw new Error(r.error || 'บันทึกไม่สำเร็จ');
            sigPath = r.signature_path || sigPath;
            r.results.forEach(x => { done.set(+x.event_id, { ok: x.success, msg: x.error || '' }); x.success ? okN++ : failN++; });
        } catch (err) {
            part.forEach(id => done.set(id, { ok: false, msg: err.message })); failN += part.length;
            if (!sigPath) break;   // ชุดแรกไม่ผ่าน (เช่น ข้อมูลผู้ตรวจไม่ถูกต้อง) => หยุด
        }
        const n = Math.min(i + chunk, ids.length);
        const t = document.getElementById('pgTxt'), b = document.getElementById('pgBar');
        if (t) t.textContent = `${n} / ${ids.length}`; if (b) b.style.width = (n / ids.length * 100) + '%';
    }

    // ปฏิทินในหน้าที่เปิดมา: โหลดสถานะใหม่
    try { window.opener && window.opener.refreshCalendarEvents && window.opener.refreshCalendarEvents(); } catch (e) {}
    shown = PAGE; renderMachines();
    const fails = [...done.entries()].filter(([, v]) => !v.ok);
    await Swal.fire({
        icon: failN ? 'warning' : 'success',
        title: failN ? `บันทึกสำเร็จ ${okN} เครื่อง ไม่สำเร็จ ${failN} เครื่อง` : `บันทึกสำเร็จ ${okN} เครื่อง`,
        html: failN ? `<div class="text-left text-sm max-h-60 overflow-y-auto">${fails.slice(0, 50).map(([id, v]) => { const e = EVENTS.find(x => +x.id === id); return `• <b>${esc(e?.machine_code || id)}</b> — ${esc(v.msg)}`; }).join('<br>')}</div><div class="text-xs text-slate-500 mt-2">เครื่องที่ไม่สำเร็จยังอยู่ในรายการ แก้แล้วกดบันทึกอีกครั้ง หรือเปิดใบงานทีละเครื่อง</div>` : 'ใบงานทุกเครื่องถูกบันทึกเรียบร้อย',
        confirmButtonColor: '#006b9f'
    });
}

load();
icons();
</script>
</body>
</html>
