<?php
//display_settings.php — ตั้งค่าการแสดงผล (ขนาดตัวอักษรทั้งโปรแกรม) เมนูสำหรับผู้ใช้ทุกคน ถูกเปิดใน iframe ของ main.php
// ค่าถูกบันทึกในเบราว์เซอร์ของเครื่องนั้น (localStorage: easypro_font_scale) และ main.php เป็นผู้ขยายหน้าทั้งหมด
@session_start();
include_once "config_ctrl/checksession.php";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่าการแสดงผล</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { maxWidth: { '8xl': '88rem' } } } };</script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        body { margin: 0; font-family: "Prompt", "Noto Sans Thai", sans-serif; color: #1c2b36; background: #f0f4f8; -webkit-font-smoothing: antialiased; }
        /* แถบเลื่อนขนาดตัวอักษร */
        #fsRange { -webkit-appearance: none; appearance: none; width: 100%; height: 8px; border-radius: 999px; outline: none; cursor: pointer;
                   background: linear-gradient(to right, #0284c7 var(--p, 0%), #e2e8f0 var(--p, 0%)); }
        #fsRange::-webkit-slider-thumb { -webkit-appearance: none; width: 24px; height: 24px; border-radius: 999px; background: #fff; border: 3px solid #0284c7; box-shadow: 0 2px 6px rgba(15,23,42,.25); }
        #fsRange::-moz-range-thumb { width: 18px; height: 18px; border-radius: 999px; background: #fff; border: 3px solid #0284c7; box-shadow: 0 2px 6px rgba(15,23,42,.25); }
        #fsRange:focus-visible::-webkit-slider-thumb { box-shadow: 0 0 0 4px rgba(14,165,233,.3); }
        .fs-chip.is-active { background: #0284c7; color: #fff; border-color: #0284c7; }
    </style>
</head>
<body class="overflow-y-auto">
    <!-- Header (รูปแบบเดียวกับหน้าอื่น เช่น ข้อมูลแจ้งซ่อม) -->
    <nav class="flex-none px-3 py-4 flex items-center justify-between border-b border-slate-200 bg-white">
        <div class="flex items-center gap-3">
            <div class="p-2.5 bg-[#006b9f] rounded-xl shadow-lg text-white">
                <i data-lucide="type" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-900 leading-none">ตั้งค่าการแสดงผล</h1>
                <p class="text-sm text-slate-500 mt-1">ปรับขนาดตัวอักษรให้อ่านง่าย — ใช้กับทุกหน้าในโปรแกรม</p>
            </div>
        </div>
    </nav>

    <main class="p-4">
        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-slate-800 flex items-center gap-2"><i data-lucide="a-large-small" class="w-5 h-5 text-sky-700"></i>ขนาดตัวอักษร</h2>
                    <p class="mt-1 text-[13px] text-slate-500">เลื่อนเพื่อปรับ — ปล่อยแล้วมีผลทันทีกับทุกเมนู รวมถึงเมนูด้านซ้ายและใบงาน PM</p>
                </div>
                <button type="button" id="fsReset" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i> กลับเป็นค่าเริ่มต้น
                </button>
            </div>

            <div class="mt-6 flex items-center gap-3 sm:gap-4">
                <button type="button" id="fsMinus" title="เล็กลง" class="w-10 h-10 flex-none rounded-full border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 flex items-center justify-center"><span class="text-[13px] font-semibold">ก-</span></button>
                <div class="flex-1 min-w-0">
                    <input id="fsRange" type="range" min="80" max="150" step="5" value="100" aria-label="ขนาดตัวอักษร (เปอร์เซ็นต์)">
                    <div class="relative mt-1.5 h-4 text-[11px] text-slate-400"><span class="absolute left-0">80%</span><span class="absolute -translate-x-1/2" style="left:28.57%">100%</span><span class="absolute -translate-x-1/2" style="left:64.29%">125%</span><span class="absolute right-0">150%</span></div>
                </div>
                <button type="button" id="fsPlus" title="ใหญ่ขึ้น" class="w-10 h-10 flex-none rounded-full border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 flex items-center justify-center"><span class="text-[17px] font-semibold">ก+</span></button>
                <div class="w-20 flex-none text-center rounded-xl bg-sky-50 border border-sky-200 py-1.5">
                    <div id="fsValue" class="text-lg font-bold text-sky-700 leading-tight">100%</div>
                    <div id="fsName" class="text-[11px] text-sky-700/80">ปกติ</div>
                </div>
            </div>
            <div id="fsPresets" class="mt-4 flex flex-wrap items-center gap-2">
                <span class="text-[12px] text-slate-400 mr-1">ขนาดที่ใช้บ่อย:</span>
            </div>

            <div class="mt-6 rounded-xl bg-slate-50 border border-slate-200 p-4">
                <div class="text-[12px] font-semibold uppercase tracking-wide text-slate-400 mb-2">ตัวอย่าง</div>
                <p id="fsSample" class="text-[15px] text-slate-700 leading-relaxed">แจ้งซ่อม: แอร์ห้องประชุมชั้น 3 เปิดไม่ติด มีน้ำหยด — กรุณาเข้าตรวจสอบภายในวันนี้</p>
                <p id="fsSample2" class="mt-1 text-[12px] text-slate-500">ข้อความขนาดเล็ก เช่น วันที่ เวลา และสถานะ · 9 ต.ค. 2569 · 10:30 น.</p>
            </div>
        </section>

        <section class="mt-4 rounded-2xl border border-sky-100 bg-sky-50/60 p-4 text-[13px] text-slate-600 flex gap-3">
            <i data-lucide="info" class="w-5 h-5 text-sky-700 flex-none mt-0.5"></i>
            <div class="space-y-1">
                <p>การตั้งค่านี้บันทึกไว้ใน<b>เบราว์เซอร์ของเครื่องนี้</b> — ใช้เครื่องอื่นต้องตั้งค่าใหม่</p>
                <p>ขนาดใหญ่จะเห็นข้อมูลต่อหน้าจอน้อยลง ตารางบางหน้าอาจต้องเลื่อนมากขึ้น</p>
                <p>ถ้าต้องการขยายเฉพาะครั้งคราว ใช้ <b>Ctrl</b> + <b>+</b> / <b>Ctrl</b> + <b>−</b> ของเบราว์เซอร์ได้เช่นกัน</p>
            </div>
        </section>
    </main>

<script>
const KEY = 'easypro_font_scale';
const PRESETS = [{ z: 0.9, label: 'เล็ก' }, { z: 1, label: 'ปกติ' }, { z: 1.15, label: 'ใหญ่' }, { z: 1.3, label: 'ใหญ่มาก' }];
const MIN = 0.8, MAX = 1.5, STEP = 0.05;
const $ = id => document.getElementById(id);
const host = (window.parent && window.parent !== window && typeof window.parent.applyFontScale === 'function') ? window.parent : null;
const round = z => Math.round(Math.min(MAX, Math.max(MIN, z)) * 100) / 100;

function current() {
    let z = 1;
    try { z = parseFloat(localStorage.getItem(KEY)) || 1; } catch (e) {}
    return round(z);
}
const nameOf = z => z < 0.95 ? 'เล็ก' : z < 1.05 ? 'ปกติ' : z < 1.2 ? 'ใหญ่' : z < 1.35 ? 'ใหญ่มาก' : 'ใหญ่พิเศษ';

// แสดงผลบนหน้านี้ (ระหว่างเลื่อน: ตัวอย่างขยายตามทันที ทั้งโปรแกรมรอจนปล่อย — ไม่งั้นหน้ากระตุกใต้นิ้ว)
function show(z, applied) {
    const pct = Math.round(z * 100);
    const range = $('fsRange');
    range.value = pct;
    range.style.setProperty('--p', ((pct - MIN * 100) / ((MAX - MIN) * 100) * 100) + '%');
    $('fsValue').textContent = pct + '%';
    $('fsName').textContent = nameOf(z);
    // ตัวอย่าง: ขนาดเทียบกับขนาดที่ใช้อยู่ (หน้านี้ถูกขยายด้วยค่าที่ใช้อยู่แล้ว)
    const rel = z / (applied || current());
    $('fsSample').style.fontSize = (15 * rel) + 'px';
    $('fsSample2').style.fontSize = (12 * rel) + 'px';
    document.querySelectorAll('.fs-chip').forEach(c => c.classList.toggle('is-active', Math.abs(parseFloat(c.dataset.z) - z) < 0.001));
}
function apply(z) {
    z = round(z);
    // ใน main.php: ให้หน้าหลักขยายทุกหน้า / เปิดหน้านี้ตรง ๆ: บันทึกค่าไว้อย่างเดียว
    if (host) host.applyFontScale(z);
    else try { localStorage.setItem(KEY, String(z)); } catch (e) {}
    show(z, z);
}

$('fsPresets').insertAdjacentHTML('beforeend', PRESETS.map(p =>
    `<button type="button" data-z="${p.z}" class="fs-chip rounded-full border border-slate-200 bg-white hover:border-sky-300 px-3 py-1 text-[13px] text-slate-600">${p.label} ${Math.round(p.z * 100)}%</button>`).join(''));
$('fsPresets').addEventListener('click', e => { const b = e.target.closest('[data-z]'); if (b) apply(parseFloat(b.dataset.z)); });
$('fsRange').addEventListener('input', e => show(e.target.value / 100));
$('fsRange').addEventListener('change', e => apply(e.target.value / 100));
$('fsMinus').addEventListener('click', () => apply(current() - STEP));
$('fsPlus').addEventListener('click', () => apply(current() + STEP));
$('fsReset').addEventListener('click', () => apply(1));
show(current());
lucide.createIcons();
</script>
</body>
</html>
