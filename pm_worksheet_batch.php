<?php
//pm_worksheet_batch.php — บันทึกใบงาน PM พร้อมกันหลายเครื่อง (เช็คชีตเดียวกัน วันเดียวกัน)
//  ออกแบบสำหรับงานหลายร้อยเครื่อง: รายการบรรทัดเดียว + ตรวจทีละเครื่องเต็มจอ + สแกน QR + ร่างอัตโนมัติ + ส่งเป็นชุด
@session_start();
include "config_ctrl/checksession.php";

$checksheet_id = isset($_GET['checksheet_id']) ? intval($_GET['checksheet_id']) : 0;
$date = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']) ? $_GET['date'] : '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>บันทึกใบงาน PM พร้อมกันหลายเครื่อง</title>
    <script>
        // ขนาดตัวอักษรทั้งโปรแกรม (เมนู ตั้งค่าการแสดงผล) — หน้านี้เปิดเป็นหน้าต่างแยก จึงขยายเองด้วย zoom
        //  เปิดเป็น dialog (iframe ใน pm.php) => กรอบนอกถูกขยายตามขนาดตัวอักษรอยู่แล้ว ไม่ต้องขยายซ้ำ
        (function () { if (window.parent !== window) return; let z = 1; try { z = parseFloat(localStorage.getItem('easypro_font_scale')) || 1; } catch (e) {} if (z !== 1) document.documentElement.style.zoom = Math.min(1.5, Math.max(0.8, z)); })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script src="js/search-select.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --brand: #006b9f; }
        html, body { height: 100%; }
        body { font-family: 'Kanit', sans-serif; background: #f1f5f9; }
        .num { font-variant-numeric: tabular-nums; }
        /* ตรึงเต็มกรอบที่มองเห็นจริง (ไม่ใช้ 100dvh — คลาดเคลื่อนเมื่อหน้าถูก zoom) => แถบปุ่มล่างติดขอบล่างจอทุกอุปกรณ์ */
        .app { position: fixed; inset: 0; display: flex; flex-direction: column; max-width: 56rem; margin: 0 auto; background: #f8fafc; overflow: hidden; }
        @media (min-width: 900px) { .app { border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; } }
        .topbar { background: var(--brand); color: #fff; padding: .75rem 1rem .6rem; padding-top: calc(.75rem + env(safe-area-inset-top, 0px)); flex: none; }
        .pbar { height: 6px; border-radius: 99px; background: rgba(255,255,255,.25); overflow: hidden; }
        .pbar > span { display: block; height: 100%; background: #fff; border-radius: 99px; transition: width .3s; }
        .draft::before { content: ""; display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: #86efac; margin-right: 6px; }
        .chips { display: flex; gap: .4rem; overflow-x: auto; scrollbar-width: none; }
        .chip { flex: none; border: 1px solid #e2e8f0; background: #fff; border-radius: 99px; padding: .3rem .75rem; font-size: 13px; display: inline-flex; gap: .4rem; align-items: center; color: #334155; }
        .chip.on { background: #0f172a; color: #fff; border-color: #0f172a; }
        .chip .dot { width: 7px; height: 7px; border-radius: 50%; }
        .mrow { display: grid; grid-template-columns: 10px minmax(0,1fr) auto; gap: .75rem; align-items: center; width: 100%; text-align: left; padding: .7rem 1rem; border-bottom: 1px solid #e2e8f0; background: #fff; }
        .mrow:hover { background: #f8fafc; }
        .mrow .sd { width: 10px; height: 10px; border-radius: 50%; background: #cbd5e1; }
        .mrow[data-s="done"] .sd { background: #16a34a; } .mrow[data-s="fail"] .sd { background: #dc2626; }
        .mrow[data-s="sent"] .sd { background: #94a3b8; } .mrow[data-s="error"] .sd { background: #f59e0b; } .mrow[data-s="part"] .sd { background: #f59e0b; }
        .tag { font-size: 11px; padding: .1rem .55rem; border-radius: 99px; white-space: nowrap; font-weight: 600; }
        .tag.todo { color: #64748b; } .tag.part { background: #fef3c7; color: #b45309; }
        .tag.done { background: #dcfce7; color: #15803d; } .tag.fail { background: #fee2e2; color: #b91c1c; }
        .tag.sent { background: #f1f5f9; color: #64748b; } .tag.error { background: #fef3c7; color: #b45309; }
        .btn { border-radius: .75rem; padding: .6rem .9rem; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; justify-content: center; gap: .5rem; }
        .btn:disabled { opacity: .45; cursor: not-allowed; }
        .foot { flex: none; background: #fff; border-top: 1px solid #e2e8f0; padding: .6rem .75rem; padding-bottom: calc(.6rem + env(safe-area-inset-bottom, 0px)); display: flex; gap: .5rem; }

        /* ตรวจทีละเครื่อง (เต็มจอ) */
        .focus { position: absolute; inset: 0; background: #f8fafc; display: flex; flex-direction: column; z-index: 20; transform: translateY(100%); transition: transform .22s ease; }
        .focus.open { transform: none; }
        .nav-btn { width: 2.25rem; height: 2.25rem; border-radius: .75rem; border: 1px solid #e2e8f0; background: #fff; display: grid; place-items: center; color: #334155; flex: none; }
        .nav-btn:disabled { opacity: .35; }
        .nav-btn.sm { width: 2.25rem; height: 2.25rem; border: 0; background: transparent; }
        .txt-btn { height: 2.25rem; padding: 0 .65rem; border-radius: .75rem; border: 1px solid #e2e8f0; background: #fff; display: inline-flex; align-items: center; gap: .35rem; font-size: 14px; font-weight: 600; color: #334155; flex: none; }
        .txt-btn:hover, .nav-btn:hover:not(:disabled) { background: #f1f5f9; }
        .qty { display: inline-flex; align-items: center; border: 1px solid #e2e8f0; border-radius: .6rem; overflow: hidden; flex: none; }
        .qty button { width: 2rem; height: 2.1rem; color: #475569; font-weight: 700; }
        .qty input { width: 3rem; height: 2.1rem; text-align: center; font-weight: 600; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; }
        .blk { background: #fff; border: 1px solid #e2e8f0; border-radius: .75rem; padding: .65rem .75rem; }
        /* ===== จุดตรวจสอบ: การ์ดแบบเดียวกับใบงาน PM (pm_worksheet.php บนมือถือ) ===== */
        .wsc { border: 1px solid #e2e8f0; border-left: 3px solid #cbd5e1; border-radius: .625rem; overflow: hidden; background: #fff; }
        .wsc + .wsc { margin-top: .5rem; }
        .wsc[data-state="Pass"] { border-left-color: #16a34a; }
        .wsc[data-state="Fail"] { border-left-color: #dc2626; }
        .wsc[data-state="N/A"]  { border-left-color: #64748b; }
        /* หัวการ์ดบรรทัดเดียว: ลำดับ · ชื่อจุดตรวจ · ป้าย · ปุ่มมาตรฐาน */
        .wsc-head { padding: .45rem .625rem; display: flex; align-items: center; gap: .5rem; }
        .wsc-no { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 1.375rem; height: 1.375rem; border-radius: 9999px; background: rgba(0, 107, 159, .1); color: #006b9f; font-weight: 700; font-size: .6875rem; }
        .wsc-badge { font-size: 10px; padding: 0 .4rem; border-radius: 9999px; font-weight: 600; border: 1px solid; white-space: nowrap; line-height: 1.5; }
        .wsc-more { flex: none; width: 1.75rem; height: 1.75rem; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; color: #94a3b8; }
        .wsc-more:hover { color: #006b9f; background: #f1f5f9; }
        /* ผลการตรวจ + รูป อยู่แถวเดียวกันทุกขนาดจอ, ค่าที่วัดขึ้นบรรทัดใหม่บนจอเล็ก */
        .wsc-row { display: flex; flex-wrap: wrap; align-items: stretch; gap: .5rem; padding: 0 .625rem .5rem; }
        .wsc-sec { flex: 1 1 0; min-width: 0; }
        .wsc-sec.v { flex: 1 1 100%; order: 3; }
        .wsc-sec.p { flex: 0 0 4.25rem; }
        .wsc[data-state="Fail"] { background: #fef2f2; }
        .wsc-lbl { display: block; font-size: .6875rem; font-weight: 600; color: #64748b; margin-bottom: .2rem; }
        @media (min-width: 640px) {
            .wsc-sec.v { flex: 0 0 10rem; order: 0; }
            .wsc-sec.p { flex: 0 0 5rem; }
        }
        .ws-choice { flex: 1 1 0; min-height: 2.25rem; display: flex; align-items: center; justify-content: center; gap: .3rem; padding: 0 .375rem; border-radius: .5rem; border: 1.5px solid #e2e8f0; background: #fff; color: #475569; font-size: 13px; font-weight: 500; white-space: nowrap; transition: background .15s, border-color .15s; }
        .ws-choice i { font-size: 11px; }
        .ws-choice:hover { border-color: #cbd5e1; }
        .ws-choice.on-pass { background: #16a34a; border-color: #16a34a; color: #fff; font-weight: 600; }
        .ws-choice.on-fail { background: #dc2626; border-color: #dc2626; color: #fff; font-weight: 600; }
        .ws-choice.on-na   { background: #475569; border-color: #475569; color: #fff; font-weight: 600; }
        .ws-value { width: 100%; min-width: 0; height: 2.25rem; padding: .3rem .5rem; border: 1px solid #cbd5e1; border-radius: .5rem; font-weight: 600; color: #1e293b; outline: none; font-size: 15px; }
        .ws-value:focus { border-color: #006b9f; box-shadow: 0 0 0 2px rgba(0, 107, 159, .25); }
        .ws-value.miss { border-color: #ef4444; background: #fef2f2; }
        .ws-unit { display: flex; align-items: center; font-size: .75rem; color: #475569; font-weight: 600; background: #f8fafc; padding: 0 .5rem; border-radius: .5rem; border: 1px solid #e2e8f0; white-space: nowrap; }
        .ws-photo { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; min-height: 2.25rem; padding: .125rem; cursor: pointer; text-align: center; border: 1.5px dashed #cbd5e1; border-radius: .5rem; background: #f8fafc; overflow: hidden; position: relative; transition: all .2s; }
        .ws-photo:hover { border-color: #006b9f; background: #eff6ff; }
        .ws-photo.has { border-style: solid; border-color: #006b9f; padding: 0; }
        .ws-photo.miss { border-color: #ef4444; background: #fef2f2; }
        .ws-photo img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .ws-sec-title { font-size: .75rem; font-weight: 600; color: #64748b; margin: .125rem .125rem .375rem; }
        .bigok { background: #16a34a; color: #fff; padding: .7rem 1rem; font-size: 15px; }
        .bigbad { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: .7rem .75rem; font-size: 14px; }

        /* แผ่นจากด้านล่าง */
        .scrim { position: absolute; inset: 0; background: rgba(15,23,42,.5); z-index: 30; opacity: 0; pointer-events: none; transition: opacity .2s; }
        .scrim.open { opacity: 1; pointer-events: auto; }
        .sheet { position: absolute; left: 0; right: 0; bottom: 0; z-index: 31; background: #fff; border-radius: 1.25rem 1.25rem 0 0; padding: 1rem; padding-bottom: calc(1rem + env(safe-area-inset-bottom, 0px)); max-height: 90%; overflow-y: auto; transform: translateY(105%); transition: transform .22s ease; }
        .sheet.open { transform: none; }
        #qrReader { width: 100%; border-radius: 1rem; overflow: hidden; background: #0b1722; min-height: 12rem; }
        #sig { touch-action: none; }
        .toast { position: absolute; left: 50%; bottom: 5.5rem; transform: translate(-50%, 1rem); background: #0f172a; color: #fff; padding: .6rem 1rem; border-radius: .8rem; font-size: 14px; z-index: 40; opacity: 0; pointer-events: none; transition: .2s; max-width: 90%; text-align: center; }
        .toast.show { opacity: 1; transform: translate(-50%, 0); }
        @media (prefers-reduced-motion: reduce) { .focus, .sheet, .pbar > span { transition: none; } }
    </style>
</head>
<body class="text-slate-700">
<div class="app" id="app">

    <header class="topbar">
        <div class="flex items-center gap-3">
            <div class="min-w-0 flex-1">
                <h1 id="hdrTitle" class="text-base sm:text-lg font-semibold leading-tight truncate">บันทึกพร้อมกันหลายเครื่อง</h1>
                <p id="hdrSub" class="text-xs opacity-90 truncate">กำลังโหลด...</p>
            </div>
            <button type="button" onclick="closeMe()" class="w-9 h-9 rounded-xl bg-white/15 flex items-center justify-center flex-none" title="ปิด"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="pbar mt-2"><span id="topBar" style="width:0%"></span></div>
        <div class="flex items-center justify-between mt-1.5 text-[11px] opacity-90">
            <span class="draft" id="draftTxt">ร่างจะถูกเก็บในเครื่องนี้อัตโนมัติ</span>
            <span class="num" id="topCount"></span>
        </div>
    </header>

    <div class="bg-white border-b border-slate-200 px-3 py-2.5 space-y-2 flex-none">
        <div class="chips" id="chips" role="tablist" aria-label="กรองสถานะ"></div>
        <div class="flex gap-2">
            <div class="relative flex-1 min-w-0">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input id="q" type="search" placeholder="ค้นหารหัส / ชื่อเครื่อง / สถานที่" class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-sky-500/30">
            </div>
            <button type="button" id="btnScan" class="px-3 rounded-xl bg-sky-50 text-[#006B9F] font-semibold text-sm inline-flex items-center gap-1.5 flex-none"><i class="fa-solid fa-qrcode"></i>สแกน</button>
        </div>
        <div id="bulkBox" class="hidden text-xs text-slate-500 flex items-center gap-2">
            <span class="flex-1">เช็คชีตนี้ไม่มีค่า/รูปที่ต้องกรอกรายเครื่อง</span>
            <button type="button" id="btnBulk" class="px-2.5 py-1.5 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 font-semibold"></button>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto" id="list" role="list"></div>

    <footer class="foot">
        <button type="button" id="btnSend" class="btn border border-slate-200 bg-white text-slate-700"><i class="fa-solid fa-paper-plane"></i><span id="sendTxt">ส่งที่ครบแล้ว</span></button>
        <button type="button" id="btnStart" class="btn flex-1 bg-[#006B9F] hover:bg-[#005a87] text-white">ตรวจต่อ</button>
    </footer>

    <!-- ตรวจทีละเครื่อง -->
    <section class="focus" id="focus" aria-label="ตรวจทีละเครื่อง">
        <div class="bg-white border-b border-slate-200 px-3 py-2.5 flex-none" style="padding-top:calc(.6rem + env(safe-area-inset-top, 0px))">
            <div class="flex items-center gap-2">
                <button class="txt-btn" id="fClose" title="กลับไปหน้ารายการเครื่อง"><i class="fa-solid fa-arrow-left"></i>รายการ</button>
                <div class="flex-1 flex items-center justify-center gap-1 min-w-0">
                    <button class="nav-btn sm" id="fPrev" title="เครื่องก่อนหน้า"><i class="fa-solid fa-chevron-left"></i></button>
                    <div class="text-center text-sm text-slate-500 num whitespace-nowrap px-1" id="fCount"></div>
                    <button class="nav-btn sm" id="fNext" title="เครื่องถัดไป"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
                <button class="txt-btn" id="fScan" title="สแกน QR ที่ตัวเครื่อง"><i class="fa-solid fa-qrcode"></i>สแกน</button>
            </div>
            <div class="h-1.5 rounded-full bg-slate-200 overflow-hidden mt-2"><span id="fBar" class="block h-full bg-[#006B9F]" style="width:0%"></span></div>
        </div>
        <div class="flex-1 overflow-y-auto p-2.5 space-y-2" id="fBody"></div>
        <div class="foot" id="fFoot"></div>
    </section>

    <div class="scrim" id="scrim"></div>
    <section class="sheet" id="sheet"></section>
    <div class="toast" id="toast" role="status"></div>
</div>

<script>
const CHECKSHEET_ID = <?php echo json_encode($checksheet_id); ?>;
const DATE = <?php echo json_encode($date); ?>;
const AG_ID = <?php echo json_encode(isset($sess_user_agency_es) ? (string)$sess_user_agency_es : ''); ?>;
const LIST_MAX = 150;   // แสดงรายการไม่เกินนี้ (ค้นหา/สแกนเพื่อไปเครื่องอื่น)

let ITEMS = [], EVENTS = [], SPARES = [], users = [], sigPad = null, sigPath = '';
let common = {};          // item_id => 'Pass' | 'N/A' (ผลเริ่มต้นของทุกเครื่อง)
let per = {};             // event_id => { values, photos, status:{item_id:'Pass'|'Fail'|'N/A'}, parts:[อะไหล่ที่ใช้จริง], ok }
const sent = new Map();   // event_id => { ok, msg } ผลการส่งในรอบนี้

const $ = id => document.getElementById(id);
// เปิดได้ 2 แบบ: dialog (iframe ใน pm.php) หรือหน้าต่างแยก
const EMBED = window.parent !== window;
function closeMe() { if (EMBED && window.parent.closePmBatchDialog) window.parent.closePmBatchDialog(); else window.close(); }
function notifyHost() {   // บันทึกแล้ว => ให้ปฏิทินของหน้าหลักโหลดใหม่
    const host = EMBED ? window.parent : window.opener;
    try { host && host.refreshCalendarEvents && host.refreshCalendarEvents(); } catch (e) {}
}
window.pmBatchBusy = () => sending;   // dialog ถามก่อนปิด: กำลังส่งอยู่ห้ามปิด
const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
const icons = () => {};   // ไอคอน Font Awesome ไม่ต้องสร้างเอง
const isMeas = it => { const v = String(it.expected_value ?? '').trim(); return v !== '' && v !== '-'; };
const needPhotoAlways = it => +it.photo_required_id === 1;
const canNA = it => +it.check_type_id === 2;   // ข้อที่เช็คชีตอนุญาตให้เลือก "ไม่เกี่ยวข้อง"
const thDate = d => { const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('th-TH', { day: 'numeric', month: 'long', year: 'numeric' }); };
const pe = id => { const p = (per[id] = per[id] || { values: {}, photos: {}, status: {}, ok: false }); p.parts = p.parts || []; return p; };
const statusOf = (id, it) => pe(id).status[it.id] || common[it.id];
const photoNeeded = (id, it) => needPhotoAlways(it) || (+it.photo_required_id === 3 && statusOf(id, it) === 'Fail');
const failCount = id => ITEMS.filter(it => statusOf(id, it) === 'Fail').length;
const evOf = id => EVENTS.find(e => +e.id === id);
// ช่องที่ต้องกรอกทุกเครื่อง (ค่าที่วัด / รูปบังคับ) — ถ้าไม่มี กด "ปกติทั้งหมด" ได้ทีเดียว
const perMachineItems = () => ITEMS.filter(it => isMeas(it) || needPhotoAlways(it));

function missing(id) {
    const p = pe(id), out = [];
    ITEMS.forEach((it, i) => {
        if (isMeas(it) && !String(p.values[it.id] || '').trim()) out.push(`ข้อ ${i + 1} ${it.measurement_name || 'ค่าที่วัด'}`);
        if (photoNeeded(id, it) && !p.photos[it.id]) out.push(`รูปข้อ ${i + 1}`);
    });
    return out;
}
// สถานะในรายการ: sent ส่งแล้ว / error ส่งไม่สำเร็จ / done ครบรอส่ง / fail ผิดปกติรอส่ง / part กรอกค้าง / todo ยังไม่ตรวจ
function status(id) {
    const s = sent.get(id);
    if (s && s.ok) return 'sent';
    const p = per[id];
    if (!p) return 'todo';
    const ready = p.ok && !missing(id).length;
    if (s && !s.ok && ready) return 'error';
    if (ready) return failCount(id) ? 'fail' : 'done';
    const touched = Object.keys(p.values).length || Object.keys(p.photos).length || Object.keys(p.status).length || (p.parts || []).length;
    return touched ? 'part' : 'todo';
}

// ---------- ร่างอัตโนมัติ (IndexedDB: เก็บรูปได้มากกว่า localStorage) ----------
const DRAFT_KEY = `pmBatch:${AG_ID}:${CHECKSHEET_ID}:${DATE}`;
let dbp = null;
function db() {
    if (!dbp) dbp = new Promise((res, rej) => {
        const r = indexedDB.open('easypro_pm_batch', 1);
        r.onupgradeneeded = () => r.result.createObjectStore('drafts');
        r.onsuccess = () => res(r.result); r.onerror = () => rej(r.error);
    });
    return dbp;
}
async function draftGet() { try { const d = await db(); return await new Promise(res => { const q = d.transaction('drafts').objectStore('drafts').get(DRAFT_KEY); q.onsuccess = () => res(q.result || null); q.onerror = () => res(null); }); } catch (e) { return null; } }
async function draftPut(v) { try { const d = await db(); d.transaction('drafts', 'readwrite').objectStore('drafts').put(v, DRAFT_KEY); return true; } catch (e) { return false; } }
let saveTm = null;
function saveDraft() {
    clearTimeout(saveTm);
    saveTm = setTimeout(async () => {
        const keep = {};
        Object.entries(per).forEach(([id, p]) => { if (!(sent.get(+id) || {}).ok) keep[id] = p; });   // ส่งแล้วไม่ต้องเก็บ
        const ok = await draftPut({ per: keep, sigPath, at: new Date().toISOString() });
        // สรุปเล็ก ๆ ให้ปฏิทินนับ "รอส่ง" ได้โดยไม่ต้องอ่านร่างทั้งก้อน (มีรูป)
        try { const d = await db(); d.transaction('drafts', 'readwrite').objectStore('drafts').put({ ready: readyIds() }, DRAFT_KEY.replace('pmBatch:', 'pmBatchSum:')); } catch (e) {}
        $('draftTxt').textContent = ok ? `บันทึกร่างในเครื่องแล้ว · ${new Date().toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' })}` : 'เก็บร่างไม่ได้ (เบราว์เซอร์ไม่อนุญาต) — กรุณาส่งเป็นระยะ';
    }, 400);
}

async function load() {
    if (!CHECKSHEET_ID || !DATE) { $('hdrSub').textContent = 'ข้อมูลไม่ครบ'; return; }
    const r = await (await fetch(`handle_pm_worksheet.php?action=get_batch_data&checksheet_id=${CHECKSHEET_ID}&date=${DATE}`)).json();
    if (!r.success) { Swal.fire('โหลดข้อมูลไม่สำเร็จ', r.error || '', 'error'); return; }
    ITEMS = r.items; EVENTS = r.events; SPARES = r.spares || [];
    ITEMS.forEach(it => common[it.id] = 'Pass');
    $('hdrTitle').textContent = r.checksheet.name;
    $('hdrSub').textContent = `${thDate(DATE)} · ${EVENTS.length} เครื่องที่ยังไม่ได้ทำ`;
    document.title = `บันทึกพร้อมกัน — ${r.checksheet.name}`;
    // กู้ร่าง: เฉพาะเครื่องที่ยังไม่ได้ทำ (เครื่องที่บันทึกไปแล้วจะไม่อยู่ใน EVENTS)
    const d = await draftGet();
    if (d) {
        const live = new Set(EVENTS.map(e => String(e.id)));
        Object.entries(d.per || {}).forEach(([id, p]) => { if (live.has(id)) per[id] = p; });
        sigPath = d.sigPath || '';
        // ร่างเก่าที่ยังไม่มีสรุปให้ปฏิทิน (รอส่ง) => สร้างเลย
        try { const x = await db(); x.transaction('drafts', 'readwrite').objectStore('drafts').put({ ready: readyIds() }, DRAFT_KEY.replace('pmBatch:', 'pmBatchSum:')); } catch (e) {}
        if (Object.keys(per).length) $('draftTxt').textContent = `กู้ร่างจากเครื่องนี้ · ${new Date(d.at).toLocaleString('th-TH', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })}`;
    }
    renderList(); loadUsers();
}

// ---------- เปิดใบงานเต็มเป็นหน้าต่าง popup — ปิดแล้วเช็คว่าเครื่องไหนบันทึกไปแล้ว ----------
function openWorksheet(id) {
    const w = Math.min(screen.availWidth, 1280), h = screen.availHeight;
    const win = window.open(`pm_worksheet.php?plan_id=${id}`, 'pmws_' + id,
        `width=${w},height=${h},left=${Math.round((screen.availWidth - w) / 2)},top=0,resizable=yes,scrollbars=yes,toolbar=no,menubar=no,location=no,status=no`);
    if (!win) { Swal.fire({ icon: 'warning', title: 'ไม่สามารถเปิดหน้าต่างใหม่ได้', text: 'กรุณาอนุญาต Popup ของเว็บไซต์นี้ก่อนใช้งาน', confirmButtonColor: '#006b9f' }); return; }
    win.focus();
    const t = setInterval(() => { if (win.closed) { clearInterval(t); syncSaved(); } }, 800);
}
// ใบงานเต็มที่เปิดจากหน้านี้ เรียกเพื่อนำข้อมูลที่กรอกไว้ไปใส่ (ผลตรวจ ค่าที่วัด รูป อะไหล่) — ไม่ต้องกรอกซ้ำ
//  เครื่องที่ตรวจครบแล้ว (ok) => ส่งผลครบทุกข้อ (ข้อที่ไม่ได้แตะ = ปกติ), ยังไม่ครบ => ส่งเฉพาะข้อที่เลือกไว้
window.pmBatchPrefill = function (planId) {
    const p = per[+planId];
    if (!p) return null;
    const status = {};
    ITEMS.forEach(it => { const v = p.ok ? statusOf(+planId, it) : p.status[it.id]; if (v) status[it.id] = v; });
    return JSON.parse(JSON.stringify({ status, values: p.values || {}, photos: p.photos || {}, parts: p.parts || [] }));
};
async function syncSaved() {
    let r;
    try { r = await (await fetch(`handle_pm_worksheet.php?action=get_batch_data&checksheet_id=${CHECKSHEET_ID}&date=${DATE}`)).json(); } catch (e) { return; }
    if (!r || !r.success) return;
    const live = new Set(r.events.map(e => +e.id));
    let n = 0;
    EVENTS.forEach(e => { const id = +e.id; if (!live.has(id) && !(sent.get(id) || {}).ok) { sent.set(id, { ok: true, msg: '' }); delete per[id]; n++; } });
    if (!n) return;
    saveDraft(); renderList();
    notifyHost();
    toast(`บันทึกจากใบงานเต็มแล้ว ${n} เครื่อง`);
    if ($('focus').classList.contains('open') && (sent.get(cur) || {}).ok) nextTodo();
}

// ---------- รายการ ----------
let filter = 'todo';
const CHIP = [['todo', 'ยังไม่ตรวจ', '#cbd5e1'], ['part', 'กรอกค้าง', '#f59e0b'], ['fail', 'ผิดปกติ', '#dc2626'], ['done', 'ครบ รอส่ง', '#16a34a'], ['sent', 'ส่งแล้ว', '#94a3b8'], ['all', 'ทั้งหมด', '']];
const TAG = { todo: 'ยังไม่ตรวจ', part: 'กรอกค้าง', done: 'ครบ รอส่ง', sent: 'ส่งแล้ว', error: 'ส่งไม่สำเร็จ' };
function counts() {
    const c = { all: EVENTS.length, todo: 0, part: 0, done: 0, fail: 0, sent: 0, error: 0 };
    EVENTS.forEach(e => c[status(+e.id)]++);
    return c;
}
const readyIds = () => EVENTS.map(e => +e.id).filter(id => ['done', 'fail', 'error'].includes(status(id)));
function visible() {
    const k = $('q').value.trim().toLowerCase();
    return EVENTS.filter(e => {
        const s = status(+e.id);
        const f = filter === 'all' || s === filter || (filter === 'done' && s === 'error') || (filter === 'todo' && s === 'part');
        return f && (!k || `${e.machine_code} ${e.machine_name} ${e.location}`.toLowerCase().includes(k));
    });
}
function renderList() {
    const c = counts(), total = EVENTS.length || 1;
    const finished = c.done + c.fail + c.sent + c.error;
    $('topBar').style.width = (finished * 100 / total) + '%';
    $('topCount').textContent = `ตรวจแล้ว ${finished} / ${EVENTS.length}`;
    $('chips').innerHTML = CHIP.filter(([k]) => k !== 'part' || c.part).map(([k, t, col]) => {
        const n = k === 'done' ? c.done + c.error : k === 'todo' ? c.todo + c.part : c[k];
        return `<button type="button" class="chip ${filter === k ? 'on' : ''}" data-f="${k}" role="tab">${col ? `<span class="dot" style="background:${col}"></span>` : ''}${t} <b class="num">${n}</b></button>`;
    }).join('');
    const rows = visible();
    $('list').innerHTML = rows.slice(0, LIST_MAX).map(e => {
        const id = +e.id, s = status(id), st = sent.get(id);
        const np = s !== 'sent' && per[id] && (per[id].parts || []).length;
        const ptag = np ? `<span class="tag" style="background:#ecfeff;color:#0e7490" title="ใช้อะไหล่">อะไหล่ ${np}</span>` : '';
        const tag = s === 'fail' ? `<span class="tag fail">ผิดปกติ ${failCount(id)} ข้อ</span>` : `<span class="tag ${s}">${TAG[s]}</span>`;
        return `<button type="button" class="mrow" data-s="${s}" data-id="${id}" role="listitem"><span class="sd"></span>
            <span class="min-w-0"><div class="text-sm font-semibold text-slate-800 truncate">${esc(e.machine_code || '-')} <span class="font-normal text-slate-500">${esc(e.machine_name || '')}</span></div>
            <div class="text-xs text-slate-400 truncate">${esc(e.location || '')}${st && !st.ok ? ` · <span class="text-amber-700">${esc(st.msg)}</span>` : ''}</div></span><span class="flex items-center gap-1">${ptag}${tag}</span></button>`;
    }).join('') + (rows.length > LIST_MAX ? `<div class="p-4 text-center text-xs text-slate-400">แสดง ${LIST_MAX} จาก ${rows.length} เครื่อง — ค้นหาหรือสแกน QR เพื่อไปเครื่องอื่น</div>`
        : rows.length ? '' : `<div class="py-12 text-center text-sm text-slate-400">${EVENTS.length ? 'ไม่มีเครื่องในกลุ่มนี้' : 'ไม่มีงานที่ยังไม่ได้ทำของเช็คชีตนี้ในวันนี้'}</div>`);
    const ready = readyIds().length;
    $('sendTxt').textContent = ready ? `ส่งที่ครบแล้ว (${ready})` : 'ส่งที่ครบแล้ว';
    $('btnSend').disabled = !ready;
    const left = c.todo + c.part;
    $('btnStart').textContent = left ? `ตรวจต่อ (เหลือ ${left})` : 'ตรวจครบแล้ว';
    $('btnStart').disabled = !left;
    // เช็คชีตที่ไม่มีช่องบังคับรายเครื่อง: ตั้งปกติทั้งหมดได้ทีเดียว (เหมือนบันทึกพร้อมกันแบบเดิม)
    const bulk = !perMachineItems().length && left > 0;
    $('bulkBox').classList.toggle('hidden', !bulk);
    $('bulkBox').classList.toggle('flex', bulk);
    if (bulk) $('btnBulk').textContent = `ตั้งเป็นปกติทั้งหมด (${left} เครื่อง)`;
    icons();
}
$('chips').addEventListener('click', e => { const b = e.target.closest('[data-f]'); if (b) { filter = b.dataset.f; renderList(); } });
$('q').addEventListener('input', () => { filter = 'all'; renderList(); });
$('list').addEventListener('click', e => { const b = e.target.closest('.mrow'); if (b) openFocus(+b.dataset.id); });
$('btnStart').addEventListener('click', () => { const e = EVENTS.find(x => ['todo', 'part'].includes(status(+x.id))); if (e) openFocus(+e.id); });
$('btnBulk').addEventListener('click', () => {
    const ids = EVENTS.map(e => +e.id).filter(id => ['todo', 'part'].includes(status(id)) && !failCount(id));
    ids.forEach(id => { pe(id).ok = true; });
    saveDraft(); renderList(); toast(`ตั้งเป็นปกติ ${ids.length} เครื่อง — กด "ส่งที่ครบแล้ว" เพื่อบันทึก`);
});

// ---------- ตรวจทีละเครื่อง ----------
let cur = null, showItems = false, tried = false;
function openFocus(id) {
    // ปุ่มย้อนกลับของมือถือ/เบราว์เซอร์ = กลับไปรายการ (ไม่ออกจากหน้า)
    if (!$('focus').classList.contains('open')) try { history.pushState({ pmFocus: 1 }, ''); } catch (e) {}
    cur = id; tried = false;
    const must = perMachineItems();
    showItems = ITEMS.some(it => !must.includes(it) && pe(id).status[it.id]);   // จุดตรวจอื่นมีข้อที่ไม่ปกติ => เปิดรายการไว้
    renderFocus(); $('focus').classList.add('open');
    $('fBody').scrollTop = 0;
}
function hideFocus() { $('focus').classList.remove('open'); renderList(); }
function closeFocus() { if (history.state && history.state.pmFocus) history.back(); else hideFocus(); }
window.addEventListener('popstate', () => {
    if (sending) return;
    const inFocus = $('focus').classList.contains('open');
    if ($('sheet').classList.contains('open')) { closeSheet(); if (inFocus) try { history.pushState({ pmFocus: 1 }, ''); } catch (e) {} return; }   // ย้อนกลับ = ปิดแผ่นก่อน
    if (inFocus) hideFocus();
});
document.addEventListener('keydown', e => {
    if (e.key !== 'Escape' || sending) return;
    if ($('sheet').classList.contains('open')) closeSheet(); else if ($('focus').classList.contains('open')) closeFocus(); else if (EMBED) closeMe();
});
function go(d) {
    const i = EVENTS.findIndex(e => +e.id === cur) + d;
    if (i >= 0 && i < EVENTS.length) openFocus(+EVENTS[i].id);
}
function nextTodo() {
    const i0 = EVENTS.findIndex(e => +e.id === cur);
    const order = EVENTS.slice(i0 + 1).concat(EVENTS.slice(0, i0));
    const n = order.find(e => ['todo', 'part'].includes(status(+e.id)));
    if (n) openFocus(+n.id); else { closeFocus(); toast('ตรวจครบทุกเครื่องแล้ว — กด "ส่งที่ครบแล้ว"'); }
}
// ===== จุดตรวจสอบ: การ์ดแบบเดียวกับใบงาน PM — หัวการ์ด (ลำดับ ชื่อ ป้าย ดูมาตรฐาน) | ผลการตรวจ | ค่าที่วัดได้จริง | รูปภาพหลักฐาน =====
const BADGE = {
    photo: '<span class="wsc-badge bg-red-50 text-red-600 border-red-200"><i class="fa-solid fa-camera mr-1"></i>บังคับถ่ายรูป</span>',
    photoFail: '<span class="wsc-badge bg-amber-50 text-amber-600 border-amber-200"><i class="fa-solid fa-camera mr-1"></i>ถ่ายรูปเมื่อผิดปกติ</span>',
    // ค่าที่ต้องวัด + มาตรฐาน อยู่ในหัวการ์ด (แถวกรอกค่าสูงเท่าปุ่มผลการตรวจ)
    meas: it => `<span class="wsc-badge bg-blue-50 text-[#006B9F] border-blue-200"><i class="fa-solid fa-ruler mr-1"></i>${esc(it.measurement_name || 'ต้องวัดค่า')} · มาตรฐาน ${esc(it.expected_value)} ${esc(it.unit || '')}</span>`
};
function photoBox(it) {
    const src = pe(cur).photos[it.id], need = photoNeeded(cur, it);
    return `<label class="ws-photo ${src ? 'has' : ''} ${tried && need && !src ? 'miss' : ''}" title="${src ? 'เปลี่ยนรูป' : 'เพิ่มรูป'}">
        ${src ? `<img src="${src}" alt=""><span class="absolute bottom-0.5 right-0.5 text-white text-[9px] bg-black/60 px-1 rounded"><i class="fa-solid fa-pen"></i></span>`
              : `<span class="flex flex-col items-center leading-none"><i class="fa-solid fa-camera text-slate-400 text-sm"></i><span class="text-[10px] mt-0.5 ${need ? 'text-red-500 font-semibold' : 'text-slate-400'}">${need ? 'ต้องถ่าย*' : 'ถ้ามี'}</span></span>`}
        <input type="file" accept="image/*" capture="environment" class="hidden" data-photo="${it.id}"></label>`;
}
function valueBox(it) {
    const v = pe(cur).values[it.id] || '';
    return `<div class="flex items-stretch gap-1.5">
            <input type="text" inputmode="decimal" enterkeyhint="next" data-val="${it.id}" value="${esc(v)}" placeholder="กรอกค่า" class="ws-value num ${tried && !String(v).trim() ? 'miss' : ''}">
            ${it.unit ? `<span class="ws-unit">${esc(it.unit)}</span>` : ''}
        </div>`;
}
function wsCard(it) {
    const k = ITEMS.indexOf(it), v = statusOf(cur, it), f = v === 'Fail';
    const pr = +it.photo_required_id;
    const badges = (pr === 1 ? BADGE.photo : pr === 3 ? BADGE.photoFail : '') + (isMeas(it) ? BADGE.meas(it) : '');
    const sec = (icon, label, html, cls = 'bg-white border-slate-200', lcls = 'text-slate-500') => html ? `<div class="rounded-xl border p-3 ${cls}">
            <div class="text-[11px] font-bold uppercase tracking-wider mb-1 ${lcls}"><i class="${icon} mr-1"></i>${label}</div><div class="leading-relaxed break-words">${html}</div></div>` : '';
    const info = sec('fa-solid fa-book-open', 'มาตรฐานการตรวจสอบ', esc(it.standard_text || '') + (it.reference_image ? ` <a href="${esc(it.reference_image)}" target="_blank" class="text-[#006B9F] underline">ดูรูปประกอบ</a>` : ''))
        + (isMeas(it) ? sec('fa-solid fa-ruler', 'ค่ามาตรฐาน', `${it.measurement_name ? esc(it.measurement_name) + ' : ' : ''}<b class="text-[#006B9F]">${esc(it.expected_value)}</b> ${esc(it.unit || '')}`, 'bg-blue-50/60 border-blue-100', 'text-[#006B9F]') : '')
        + sec('fa-solid fa-wrench', 'วิธีตรวจสอบ / เครื่องมือ', esc(it.method_text || ''))
        + sec('fa-solid fa-triangle-exclamation', 'เมื่อพบความผิดปกติ', esc(it.action_text || ''), 'bg-red-50 border-red-100 text-red-700', 'text-red-600');
    const choice = (st, label, icon, on) => `<button type="button" data-st="${st}" class="ws-choice ${v === st ? on : ''}"><i class="fa-solid ${icon}"></i>${label}</button>`;
    const showPhoto = needPhotoAlways(it) || f || pe(cur).photos[it.id];
    return `<div class="wsc" data-state="${v}">
        <div class="wsc-head">
            <span class="wsc-no">${k + 1}</span>
            <div class="flex-1 min-w-0 flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
                <span class="text-sm font-semibold text-slate-800 leading-snug break-words">${esc(it.check_point || '-')}</span>${badges}
            </div>
            ${info ? `<button type="button" data-info class="wsc-more" title="ดูมาตรฐาน / วิธีตรวจ"><i class="fa-solid fa-book-open text-xs"></i></button>` : ''}
        </div>
        ${info ? `<div class="item-info hidden px-2.5 pb-2 space-y-1.5 text-[13px] text-slate-700">${info}</div>` : ''}
        <div class="wsc-row">
            <div class="wsc-sec">
                <div class="flex gap-1 h-full" data-item="${it.id}">
                    ${choice('Pass', 'ปกติ', 'fa-check', 'on-pass')}
                    ${choice('Fail', 'ผิดปกติ', 'fa-xmark', 'on-fail')}
                    ${canNA(it) ? choice('N/A', 'N/A', 'fa-ban', 'on-na') : ''}
                </div>
            </div>
            ${isMeas(it) ? `<div class="wsc-sec v">${valueBox(it)}</div>` : ''}
            ${showPhoto ? `<div class="wsc-sec p">${photoBox(it)}</div>` : ''}
        </div>
        ${f && it.action_text ? `<div class="px-2.5 pb-2 -mt-1 text-xs text-red-700"><b>เมื่อผิดปกติ:</b> ${esc(it.action_text)}</div>` : ''}
    </div>`;
}
function renderFocus() {
    const e = evOf(cur), p = pe(cur), s = status(cur), nf = failCount(cur);
    const i = EVENTS.findIndex(x => +x.id === cur), c = counts();
    $('fCount').innerHTML = `เครื่อง <b class="text-slate-800 text-base">${i + 1}</b> / ${EVENTS.length}`;
    $('fBar').style.width = ((c.done + c.fail + c.sent + c.error) * 100 / (EVENTS.length || 1)) + '%';
    $('fPrev').disabled = i <= 0; $('fNext').disabled = i >= EVENTS.length - 1;
    const pill = s === 'fail' ? `<span class="tag fail">ผิดปกติ ${nf} ข้อ</span>` : `<span class="tag ${s}">${TAG[s] || ''}</span>`;
    // ข้อที่ต้องกรอกทุกเครื่อง (ค่าที่วัด / รูปบังคับ) แสดงเสมอ — จุดตรวจอื่นแสดงเมื่อกด "มีข้อผิดปกติ"
    const must = perMachineItems(), rest = ITEMS.filter(it => !must.includes(it));
    const miss = tried ? missing(cur) : [];
    $('fBody').innerHTML = `
        <div class="blk">
            <div class="flex items-start gap-2">
                <div class="min-w-0 flex-1">
                    <div class="text-lg font-semibold text-slate-800 leading-tight">${esc(e.machine_code || '-')}</div>
                    <div class="text-sm text-slate-600">${esc(e.machine_name || '')}${e.machine_sn ? ` · S/N ${esc(e.machine_sn)}` : ''}</div>
                    <div class="text-xs text-slate-400 mt-0.5">${esc(e.location || '')}</div>
                </div>${pill}
            </div>
            <button type="button" data-ws class="inline-flex items-center gap-1.5 text-xs text-[#006B9F] font-semibold mt-2 hover:underline"><i class="fa-solid fa-up-right-from-square"></i>เปิดใบงานเต็มของเครื่องนี้ (นำข้อมูลที่กรอกไว้ไปด้วย)</button>
        </div>
        ${must.length ? `<div><div class="ws-sec-title">กรอกทุกเครื่อง</div>${must.map(wsCard).join('')}</div>` : ''}
        ${showItems && rest.length ? `<div><div class="ws-sec-title">${must.length ? `จุดตรวจอื่น ${rest.length} ข้อ` : `จุดตรวจ ${ITEMS.length} ข้อ`} — แตะเฉพาะข้อที่ไม่ปกติ ข้ออื่นนับเป็นปกติ</div>${rest.map(wsCard).join('')}</div>` : ''}
        ${partsBlock()}
        ${miss.length ? `<div class="text-sm text-red-600 px-1">ยังขาด: ${miss.map(esc).join(' · ')}</div>` : ''}`;
    const mustSet = must.some(it => p.status[it.id]);
    $('fFoot').innerHTML = showItems && rest.length
        ? `<button type="button" class="btn border border-slate-200 bg-white text-slate-700" data-act="allok">${must.length ? 'ข้ออื่นปกติ' : 'ปกติทั้งหมด'}</button>
           <button type="button" class="btn flex-1 bg-[#006B9F] text-white" data-act="save">บันทึกแล้วไปถัดไป <i class="fa-solid fa-chevron-right"></i></button>`
        : `<div class="flex-1 flex gap-2">
            <button type="button" class="btn bigok flex-[2] order-2" data-act="ok"><i class="fa-solid fa-check"></i>${!mustSet ? 'ปกติทุกข้อ → ถัดไป' : rest.length ? 'ข้ออื่นปกติ → ถัดไป' : 'บันทึก → ถัดไป'}</button>
            ${rest.length ? `<button type="button" class="btn bigbad flex-1 order-1" data-act="bad"><i class="fa-solid fa-triangle-exclamation"></i>${must.length ? 'ข้ออื่นผิดปกติ' : 'ผิดปกติ'}${rest.some(canNA) ? '/N/A' : ''}</button>` : ''}</div>`;
}
// ปุ่ม "ปกติ" ใหญ่ = ตั้งจุดตรวจอื่นเป็นปกติ (ข้อในกล่อง "กรอกทุกเครื่อง" เลือกผลเองแล้ว ไม่แตะ)
function clearRest() {
    const p = pe(cur), must = perMachineItems();
    ITEMS.forEach(it => { if (must.includes(it)) return; delete p.status[it.id]; if (!needPhotoAlways(it)) delete p.photos[it.id]; });
}
function finish() {
    tried = true;
    const m = missing(cur);
    if (m.length) { renderFocus(); toast('กรอกให้ครบก่อนไปเครื่องถัดไป'); return; }
    const p = pe(cur); p.parts = p.parts.filter(x => x.rpd_qty > 0);
    p.ok = true; sent.delete(cur); saveDraft();
    const e = evOf(cur), nf = failCount(cur);
    toast(`${e.machine_code || ''} ${nf ? `ผิดปกติ ${nf} ข้อ` : 'ปกติ'} — เก็บแล้ว`);
    nextTodo();
}
$('fBody').addEventListener('input', e => {
    if (e.target.matches('[data-qv]')) { setQty(+e.target.closest('[data-pk]').dataset.pk, e.target.value); return; }
    if (!e.target.matches('[data-val]')) return;
    pe(cur).values[e.target.dataset.val] = e.target.value.trim();
    e.target.classList.remove('miss'); saveDraft();
});
$('fBody').addEventListener('keydown', e => {
    if (e.key !== 'Enter' || !e.target.matches('[data-val]')) return;
    e.preventDefault();
    const all = [...$('fBody').querySelectorAll('[data-val]')], n = all[all.indexOf(e.target) + 1];
    if (n) n.focus(); else e.target.blur();
});
$('fBody').addEventListener('change', async e => {
    if (!e.target.matches('[data-photo]')) return;
    const f = e.target.files[0]; if (!f) return;
    const url = await shrink(f);
    if (url) { pe(cur).photos[e.target.dataset.photo] = url; saveDraft(); renderFocus(); }
});
$('fBody').addEventListener('click', e => {
    if (e.target.closest('[data-ws]')) { openWorksheet(cur); return; }
    if (e.target.closest('[data-addpart]')) { openPicker(); return; }
    const plan = e.target.closest('[data-plan]'); if (plan) { openPicker(plan.dataset.plan); return; }
    const q = e.target.closest('[data-q]');
    if (q) { const k = +q.closest('[data-pk]').dataset.pk; setQty(k, pe(cur).parts[k].rpd_qty + +q.dataset.q); renderFocus(); return; }
    const del = e.target.closest('[data-delpart]');
    if (del) { pe(cur).parts.splice(+del.dataset.delpart, 1); pe(cur).ok = false; saveDraft(); renderFocus(); return; }
    const info = e.target.closest('[data-info]');
    if (info) { info.closest('.wsc').querySelector('.item-info')?.classList.toggle('hidden'); return; }
    const b = e.target.closest('[data-st]'); if (!b) return;
    const item = b.closest('[data-item]').dataset.item, p = pe(cur);
    if (b.dataset.st === common[item]) delete p.status[item]; else p.status[item] = b.dataset.st;   // เท่ากับผลเริ่มต้น => ไม่ต้องเก็บแยก
    if (b.dataset.st !== 'Fail') { const it = ITEMS.find(x => String(x.id) === item); if (it && !needPhotoAlways(it) && +it.photo_required_id === 3) delete p.photos[item]; }
    p.ok = false; saveDraft(); renderFocus();
});
$('fFoot').addEventListener('click', e => {
    const a = e.target.closest('[data-act]')?.dataset.act;
    if (a === 'ok') { clearRest(); finish(); }
    else if (a === 'bad') { showItems = true; renderFocus(); setTimeout(() => $('fBody').scrollTo({ top: $('fBody').scrollHeight, behavior: 'smooth' }), 50); }
    else if (a === 'allok') { clearRest(); showItems = false; saveDraft(); renderFocus(); }
    else if (a === 'save') finish();
});
$('fClose').addEventListener('click', closeFocus);
$('fPrev').addEventListener('click', () => go(-1));
$('fNext').addEventListener('click', () => go(1));
// ปัดซ้าย/ขวา = เครื่องถัดไป/ก่อนหน้า
let sx = null, sy = null;
$('fBody').addEventListener('touchstart', e => { sx = e.touches[0].clientX; sy = e.touches[0].clientY; }, { passive: true });
$('fBody').addEventListener('touchend', e => {
    if (sx === null || e.target.closest('input, [data-item]')) { sx = null; return; }
    const dx = e.changedTouches[0].clientX - sx, dy = e.changedTouches[0].clientY - sy;
    if (Math.abs(dx) > 80 && Math.abs(dx) > Math.abs(dy) * 1.5) go(dx < 0 ? 1 : -1);
    sx = null;
});

// ---------- อะไหล่/วัสดุที่ใช้จริง (รายเครื่อง — ตัดสต็อกตอนส่ง เหมือนใบงานทีละใบ) ----------
const money = n => (+n || 0).toLocaleString('th-TH', { maximumFractionDigits: 2 });
function partsBlock() {
    const parts = pe(cur).parts;
    const plan = SPARES.length ? `<div class="text-xs text-slate-500 mt-1">ตามแผน: ${SPARES.map(s => `<button type="button" data-plan="${esc(s.part_name)}" class="underline decoration-dotted hover:text-[#006B9F]">${esc(s.part_name)} ×${esc(+s.quantity)}</button>`).join(', ')}</div>` : '';
    if (!parts.length) return `<div class="blk flex items-center gap-3">
        <div class="flex-1 min-w-0"><div class="text-sm font-medium text-slate-700">อะไหล่/วัสดุที่ใช้</div>${plan || '<div class="text-xs text-slate-400">ไม่มี — เพิ่มเมื่อเครื่องนี้ใช้อะไหล่</div>'}</div>
        <button type="button" data-addpart class="txt-btn text-[#006B9F]"><i class="fa-solid fa-plus"></i>เพิ่มอะไหล่</button></div>`;
    return `<div class="blk">
        <div class="flex items-center gap-2 mb-1"><span class="flex-1 text-xs font-semibold text-slate-500">อะไหล่/วัสดุที่ใช้ (ตัดสต็อกเมื่อส่ง)</span>
            <button type="button" data-addpart class="text-xs font-semibold text-[#006B9F] inline-flex items-center gap-1"><i class="fa-solid fa-plus"></i>เพิ่ม</button></div>
        ${parts.map((p, k) => `<div class="flex items-center gap-2 py-2 border-t border-slate-100 first:border-0">
            <div class="flex-1 min-w-0"><div class="text-sm font-medium text-slate-800 truncate">${esc(p.rpd_details_head)}</div>
                <div class="text-xs text-slate-400 truncate">${esc(p.pd_gen_code)}${p.wh_name ? ` · ${esc(p.wh_name)}` : ''}</div></div>
            <span class="qty" data-pk="${k}"><button type="button" data-q="-1">−</button><input type="text" inputmode="decimal" data-qv value="${esc(p.rpd_qty)}"><button type="button" data-q="1">+</button></span>
            <span class="text-xs text-slate-500 w-10">${esc(p.pd_unit)}</span>
            <button type="button" data-delpart="${k}" class="text-slate-400 hover:text-red-600" title="ลบ"><i class="fa-solid fa-trash-can"></i></button>
        </div>`).join('')}${plan}</div>`;
}
function setQty(k, v) {
    const p = pe(cur).parts[k]; if (!p) return;
    p.rpd_qty = Math.max(0, Math.round((+v || 0) * 100) / 100);
    p.rpd_sum_money = p.rpd_qty * p.rpd_price;
    pe(cur).ok = false; saveDraft();
}
let whList = null, pickT = null;
async function openPicker(q = '') {
    openSheet(`<div class="flex items-center justify-between mb-2"><h3 class="font-semibold text-slate-800">เลือกอะไหล่/วัสดุ</h3>
            <button type="button" class="nav-btn" data-close><i class="fa-solid fa-xmark"></i></button></div>
        <div class="flex gap-2"><select id="pkWh" class="w-32 flex-none px-2 py-2 border border-slate-200 rounded-xl text-sm bg-white"><option value="">ทุกคลัง</option></select>
            <input id="pkQ" type="search" value="${esc(q)}" placeholder="ค้นหาชื่อ / รหัส / รุ่น" class="flex-1 min-w-0 px-3 py-2 border border-slate-200 rounded-xl text-sm"></div>
        <div id="pkList" class="mt-2 divide-y divide-slate-100 min-h-[10rem]"></div>`);
    $('sheet').querySelector('[data-close]').onclick = closeSheet;
    if (!whList) { try { const r = await (await fetch(`handle_stock_maintenance_function.php?action=get_wh_list&ag_id=${encodeURIComponent(AG_ID)}`)).json(); whList = r.success ? r.data : []; } catch (e) { whList = []; } }
    $('pkWh').insertAdjacentHTML('beforeend', whList.map(w => `<option value="${esc(w.id)}">${esc(w.wh_name)}</option>`).join(''));
    const run = () => { clearTimeout(pickT); pickT = setTimeout(searchParts, 250); };
    $('pkQ').oninput = run; $('pkWh').onchange = run;
    searchParts(); setTimeout(() => $('pkQ')?.focus(), 250);
}
async function searchParts() {
    const q = $('pkQ').value.trim(), wh = $('pkWh').value;
    if (!q && !wh) { $('pkList').innerHTML = '<div class="py-8 text-center text-sm text-slate-400">พิมพ์ชื่อหรือรหัสอะไหล่ หรือเลือกคลัง</div>'; return; }
    $('pkList').innerHTML = '<div class="py-8 text-center text-sm text-slate-400">กำลังค้นหา...</div>';
    const params = new URLSearchParams({ action: 'get_all', ag_id: AG_ID }); if (q) params.set('q', q); if (wh) params.set('wh_id', wh);
    let rows = [];
    try { const r = await (await fetch('handle_stock_maintenance_function.php?' + params)).json(); rows = (r.success ? r.data : []).filter(x => +x.ps_id > 0); } catch (e) {}
    if (!$('pkList') || $('pkQ').value.trim() !== q) return;   // แผ่นถูกปิด / พิมพ์ต่อแล้ว
    window.__pk = rows.slice(0, 50);
    $('pkList').innerHTML = window.__pk.map((x, i) => `<button type="button" data-pick="${i}" class="w-full text-left py-2.5 flex items-center gap-3 hover:bg-slate-50">
        <div class="flex-1 min-w-0"><div class="text-sm font-medium text-slate-800 truncate">${esc(x.pd_details_head)}</div>
            <div class="text-xs text-slate-400 truncate">${esc(x.pd_gen_code)}${x.pd_model ? ` · ${esc(x.pd_model)}` : ''} · ${esc(x.wh_name)}</div></div>
        <div class="text-right flex-none"><div class="text-sm font-semibold ${+x.pd_qty > 0 ? 'text-emerald-700' : 'text-red-600'} num">${money(x.pd_qty)}</div><div class="text-[11px] text-slate-400">${esc(x.pd_unit)}</div></div>
    </button>`).join('') + (rows.length > 50 ? `<div class="py-3 text-center text-xs text-slate-400">แสดง 50 จาก ${rows.length} — พิมพ์ให้เจาะจงขึ้น</div>` : rows.length ? '' : '<div class="py-8 text-center text-sm text-slate-400">ไม่พบอะไหล่</div>');
}
$('sheet').addEventListener('click', e => {
    const b = e.target.closest('[data-pick]'); if (!b) return;
    const x = window.__pk[+b.dataset.pick], parts = pe(cur).parts;
    const same = parts.find(p => +p.rpd_product_id === +x.pd_id && +p.wh_id === +x.wh_id);
    if (same) { same.rpd_qty += 1; same.rpd_sum_money = same.rpd_qty * same.rpd_price; }
    else parts.push({ rpd_product_id: +x.pd_id, ps_id: +x.ps_id, wh_id: +x.wh_id, wh_name: x.wh_name || '', pd_gen_code: x.pd_gen_code || '', rpd_details_head: x.pd_details_head || '',
        rpd_details: x.pd_details || '', rpd_brand: x.pd_brand || '', pd_model: x.pd_model || '', rpd_qty: 1, rpd_price: +x.pd_price || 0, pd_unit: x.pd_unit || '', rpd_sum_money: +x.pd_price || 0 });
    pe(cur).ok = false; saveDraft(); closeSheet(); renderFocus();
    toast(`เพิ่ม ${x.pd_details_head} แล้ว — ปรับจำนวนได้`);
});

// ย่อรูปก่อนเก็บ/ส่ง (ด้านยาวไม่เกิน 1280px, JPEG)
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

// ---------- แผ่นด้านล่าง ----------
function openSheet(html) { $('sheet').innerHTML = html; $('sheet').classList.add('open'); $('scrim').classList.add('open'); icons(); }
function closeSheet() { stopScan(); $('sheet').classList.remove('open'); $('scrim').classList.remove('open'); }
$('scrim').addEventListener('click', () => { if (!sending) closeSheet(); });

// ---------- สแกน QR ที่ตัวเครื่อง ----------
let qr = null;
function stopScan() { if (qr) { const q = qr; qr = null; q.stop().catch(() => {}).finally(() => { try { q.clear(); } catch (e) {} }); } }
function findByCode(text) {
    let code = String(text || '').trim();
    try { const u = new URL(code); code = u.searchParams.get('asset_id') || code; } catch (e) { /* ไม่ใช่ URL => ใช้ข้อความตรง ๆ */ }
    const k = code.toUpperCase();
    return EVENTS.find(e => String(e.machine_code || '').toUpperCase() === k);
}
function onScanned(text) {
    const e = findByCode(text);
    if (!e) { toast('เครื่องนี้ไม่อยู่ในงานของเช็คชีตนี้วันนี้'); return false; }
    closeSheet(); openFocus(+e.id); toast(`สแกนพบ ${e.machine_code}`);
    return true;
}
function loadQrLib() {
    if (window.Html5Qrcode) return Promise.resolve();
    return new Promise((res, rej) => { const s = document.createElement('script'); s.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js'; s.onload = res; s.onerror = rej; document.head.appendChild(s); });
}
async function scan() {
    openSheet(`<div class="flex items-center justify-between mb-2"><h3 class="font-semibold text-slate-800">สแกน QR ที่ตัวเครื่อง</h3>
            <button type="button" class="nav-btn" data-close><i class="fa-solid fa-xmark"></i></button></div>
        <div id="qrReader"></div>
        <div id="qrMsg" class="text-xs text-slate-500 mt-2">กำลังเปิดกล้อง...</div>
        <div class="flex gap-2 mt-3"><input id="qrCode" placeholder="หรือพิมพ์รหัสเครื่อง" class="flex-1 min-w-0 px-3 py-2 border border-slate-200 rounded-xl text-sm">
            <button type="button" id="qrGo" class="px-4 rounded-xl bg-[#006B9F] text-white text-sm font-semibold">เปิด</button></div>`);
    $('sheet').querySelector('[data-close]').onclick = closeSheet;
    $('qrGo').onclick = () => onScanned($('qrCode').value);
    $('qrCode').onkeydown = e => { if (e.key === 'Enter') onScanned($('qrCode').value); };
    try {
        await loadQrLib();
        qr = new Html5Qrcode('qrReader');
        await qr.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 220, height: 220 } }, t => { if (onScanned(t)) stopScan(); }, () => {});
        $('qrMsg').textContent = 'เล็ง QR ที่ติดบนเครื่องให้อยู่ในกรอบ';
    } catch (err) {
        qr = null;
        $('qrMsg').textContent = 'เปิดกล้องไม่ได้ (ต้องอนุญาตกล้อง และใช้ผ่าน https) — พิมพ์รหัสเครื่องด้านล่างแทนได้';
    }
}
$('btnScan').addEventListener('click', scan);
$('fScan').addEventListener('click', scan);

// ---------- ส่งที่ครบแล้ว (เป็นชุด) ----------
async function loadUsers() {
    try {
        const r = await (await fetch(`handle_pm_worksheet.php?action=get_users&ag_id=${encodeURIComponent(AG_ID)}`)).json();
        users = r.success ? r.data : [];
    } catch (e) { users = []; }
}
let picked = new Set(), remarks = '', sending = false;
$('btnSend').addEventListener('click', () => {
    const ids = readyIds();
    if (!ids.length) return;
    const nf = ids.filter(id => failCount(id) > 0);
    const npart = ids.filter(id => pe(id).parts.length).length;
    openSheet(`<div class="flex items-center justify-between mb-1"><h3 class="font-semibold text-slate-800 text-lg">ส่ง ${ids.length} เครื่องที่ครบ</h3>
            <button type="button" class="nav-btn" data-close><i class="fa-solid fa-xmark"></i></button></div>
        <p class="text-sm text-slate-500">ระบบสร้างใบงานแยกรายเครื่อง ${nf.length ? `· <b class="text-red-600">ผิดปกติ ${nf.length} เครื่อง</b>: ${nf.slice(0, 8).map(id => esc(evOf(id)?.machine_code || id)).join(', ')}${nf.length > 8 ? ' …' : ''}` : '· ทุกเครื่องปกติ'}${npart ? ` · <b class="text-cyan-700">ใช้อะไหล่ ${npart} เครื่อง</b> (ตัดสต็อกเมื่อส่ง)` : ''}</p>
        <label class="block text-xs font-semibold text-slate-500 mt-3 mb-1">ผู้ตรวจสอบ (เลือกได้หลายคน)</label>
        <div id="uList" class="max-h-40 overflow-y-auto border border-slate-200 rounded-xl p-1">${users.length ? users.map(u => {
            const name = `${u.user_name || ''} ${u.user_fname || ''}`.trim();
            return `<label class="flex items-center gap-2 p-2 hover:bg-slate-50 rounded-lg cursor-pointer text-sm"><input type="checkbox" class="w-4 h-4" data-uid="${esc(u.user_id)}" data-upk="${esc(u.id)}" data-name="${esc(name)}" ${picked.has(String(u.user_id)) ? 'checked' : ''}>${esc(name)}</label>`;
        }).join('') : '<div class="p-3 text-sm text-slate-400 text-center">ไม่พบรายชื่อ</div>'}</div>
        <label class="block text-xs font-semibold text-slate-500 mt-3 mb-1">หมายเหตุ (ใส่ให้ทุกเครื่องในชุดนี้)</label>
        <textarea id="rmk" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm" placeholder="ถ้ามี">${esc(remarks)}</textarea>
        <div class="flex items-center justify-between mt-3 mb-1"><span class="text-xs font-semibold text-slate-500">ลายมือชื่อผู้ตรวจสอบ</span>
            ${sigPath ? '<button type="button" id="sigNew" class="text-xs text-[#006B9F] font-semibold">เซ็นใหม่</button>' : '<button type="button" id="sigClear" class="text-xs text-slate-500">ล้าง</button>'}</div>
        <div id="sigBox">${sigPath ? '<div class="p-3 rounded-xl bg-emerald-50 text-emerald-700 text-sm">ใช้ลายเซ็นที่เซ็นไว้แล้ววันนี้</div>' : '<div class="border-2 border-dashed border-slate-200 rounded-xl bg-slate-50"><canvas id="sig" class="w-full h-36 block"></canvas></div>'}</div>
        <div id="sendProg" class="hidden mt-3"><div class="text-sm text-slate-500 mb-1"><span id="pgTxt">0 / ${ids.length}</span></div><div class="h-2.5 bg-slate-100 rounded-full overflow-hidden"><div id="pgBar" class="h-full bg-emerald-500 transition-all" style="width:0%"></div></div></div>
        <button type="button" id="sendGo" class="btn w-full mt-4 bg-emerald-600 hover:bg-emerald-700 text-white"><i class="fa-solid fa-paper-plane"></i>ส่ง ${ids.length} เครื่อง</button>
        <p class="text-xs text-slate-400 mt-2 text-center">เครื่องที่ยังไม่ตรวจเก็บเป็นร่างต่อ ส่งรอบถัดไปได้</p>`);
    $('sheet').querySelector('[data-close]').onclick = closeSheet;
    const initPad = () => {
        const c = $('sig'); if (!c) return;
        const r = window.devicePixelRatio || 1;
        c.width = c.offsetWidth * r; c.height = c.offsetHeight * r; c.getContext('2d').scale(r, r);
        sigPad = new SignaturePad(c, { penColor: 'rgb(15, 23, 42)', backgroundColor: 'rgba(255,255,255,0)' });
    };
    setTimeout(initPad, 250);   // รอแผ่นเลื่อนขึ้นก่อน (ขนาด canvas ถูกต้อง)
    $('sigClear') && ($('sigClear').onclick = () => sigPad && sigPad.clear());
    $('sigNew') && ($('sigNew').onclick = () => { sigPath = ''; saveDraft(); $('sigBox').innerHTML = '<div class="border-2 border-dashed border-slate-200 rounded-xl bg-slate-50"><canvas id="sig" class="w-full h-36 block"></canvas></div>'; $('sigNew').remove(); initPad(); });
    $('sendGo').onclick = () => sendBatch(ids);
});
async function sendBatch(ids) {
    const us = [...document.querySelectorAll('#uList input:checked')].map(c => ({ user_id: c.dataset.uid, user_pk_id: c.dataset.upk, name: c.dataset.name }));
    picked = new Set(us.map(u => String(u.user_id)));
    remarks = $('rmk').value.trim();
    const errs = [];
    if (!us.length) errs.push('เลือกชื่อผู้ตรวจสอบ');
    if (!sigPath && (!sigPad || sigPad.isEmpty())) errs.push('ลงลายมือชื่อผู้ตรวจสอบ');
    if (errs.length) { toast(errs.join(' · ')); return; }

    sending = true;
    $('sendGo').disabled = true; $('sendProg').classList.remove('hidden');
    const items = ITEMS.map(it => ({ item_id: +it.id, status: common[it.id], value: '' }));
    const hasPhoto = ids.some(id => Object.keys(pe(id).photos).length);
    const chunk = hasPhoto ? 10 : 40;   // มีรูป => ส่งทีละน้อย ไม่ให้ request ใหญ่เกิน
    let okN = 0, failN = 0;
    for (let i = 0; i < ids.length; i += chunk) {
        const part = ids.slice(i, i + chunk);
        const fd = new FormData();
        fd.append('action', 'save_batch');
        fd.append('checksheet_id', CHECKSHEET_ID);
        fd.append('event_ids', JSON.stringify(part));
        fd.append('inspector_name', us.map(u => u.name).join(', '));
        fd.append('inspector_users_json', JSON.stringify(us));
        fd.append('remarks', remarks);
        fd.append('items', JSON.stringify(items));
        if (sigPath) fd.append('signature_path', sigPath); else fd.append('inspector_signature', sigPad.toDataURL('image/png'));
        const pe_ = {};
        part.forEach(id => {
            const p = pe(id);
            pe_[id] = { values: p.values, status: p.status, parts: p.parts };   // status = ผลรายเครื่อง (ทับผลเริ่มต้น) · parts = อะไหล่ที่ใช้จริง (ตัดสต็อก)
            Object.entries(p.photos).forEach(([iid, url]) => fd.append(`photo_${id}_${iid}`, url));
        });
        fd.append('per_event', JSON.stringify(pe_));
        try {
            const r = await (await fetch('handle_pm_worksheet.php', { method: 'POST', body: fd })).json();
            if (!r.success) throw new Error(r.error || 'บันทึกไม่สำเร็จ');
            sigPath = r.signature_path || sigPath;
            r.results.forEach(x => {
                // บันทึกไปแล้วจากใบงานเต็ม (เซิร์ฟเวอร์กันบันทึกซ้ำ) => นับเป็นส่งแล้ว ไม่ใช่ส่งไม่สำเร็จ
                const ok = x.success || /ถูกบันทึกผลการปฏิบัติงานแล้ว/.test(x.error || '');
                sent.set(+x.event_id, { ok, msg: ok ? '' : (x.error || '') });
                if (ok) { okN++; delete per[x.event_id]; } else failN++;
            });
        } catch (err) {
            part.forEach(id => sent.set(id, { ok: false, msg: err.message })); failN += part.length;
            if (!sigPath) break;   // ชุดแรกไม่ผ่าน (เช่น ข้อมูลผู้ตรวจไม่ถูกต้อง) => หยุด
        }
        const n = Math.min(i + chunk, ids.length);
        $('pgTxt').textContent = `${n} / ${ids.length}`; $('pgBar').style.width = (n * 100 / ids.length) + '%';
        saveDraft();
    }
    sending = false;
    closeSheet(); renderList();
    notifyHost();
    Swal.fire({
        icon: failN ? 'warning' : 'success',
        title: failN ? `ส่งสำเร็จ ${okN} เครื่อง ไม่สำเร็จ ${failN} เครื่อง` : `ส่งสำเร็จ ${okN} เครื่อง`,
        html: failN ? '<div class="text-sm">เครื่องที่ไม่สำเร็จขึ้นสถานะ <b>ส่งไม่สำเร็จ</b> ในรายการ (พร้อมเหตุผล) — แก้แล้วส่งใหม่ หรือเปิดใบงานทีละเครื่อง</div>' : 'ใบงานของเครื่องเหล่านี้ถูกบันทึกเรียบร้อย',
        confirmButtonColor: '#006b9f'
    });
}

let tt;
function toast(t) { $('toast').textContent = t; $('toast').classList.add('show'); clearTimeout(tt); tt = setTimeout(() => $('toast').classList.remove('show'), 2200); }
// กันปิดหน้าโดยไม่ตั้งใจระหว่างส่ง
window.addEventListener('beforeunload', e => { if (sending) { e.preventDefault(); e.returnValue = ''; } });

load();
icons();
</script>
</body>
</html>
