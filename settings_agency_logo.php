<?php
@session_start();
// ตั้งค่าโลโก้หน่วยงาน (บริษัทลูกค้า) — แสดงในใบพิมพ์ทุกใบ คู่กับโลโก้ PROACTIVE (ตั้งตำแหน่ง ซ้าย/ขวา และขนาดได้)
include "config_ctrl/checksession.php";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โลโก้หน่วยงาน</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --color-primary: #006B9F; --color-primary-dark: #004a6f; }
        body { font-family: 'Prompt', sans-serif; }
        .bg-primary { background-color: var(--color-primary); }
        .hover\:bg-primary-dark:hover { background-color: var(--color-primary-dark); }
        .drop { border: 2px dashed #cbd5e1; transition: .15s; }
        .drop.over { border-color: var(--color-primary); background: #f0f9ff; }
        .checker { background-color: #fff; background-image: linear-gradient(45deg, #f1f5f9 25%, transparent 25%), linear-gradient(-45deg, #f1f5f9 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #f1f5f9 75%), linear-gradient(-45deg, transparent 75%, #f1f5f9 75%); background-size: 16px 16px; background-position: 0 0, 0 8px, 8px -8px, -8px 0; }
        /* ตัวอย่างหัวใบพิมพ์ */
        .pv-head { display: flex; align-items: center; gap: 10px; border-bottom: 2px solid var(--color-primary); padding-bottom: 8px; }
        .pv-logos { display: flex; align-items: center; gap: 8px; }
        .pv-logos img { height: 34px; width: auto; max-width: 140px; object-fit: contain; flex-shrink: 0; }
        .pv-logos.ag-left #pv-logo { order: -1; }
        /* ตำแหน่ง / ขนาด */
        .pos-opt { border: 2px solid #e2e8f0; transition: .15s; }
        .pos-opt:hover { border-color: #93c5fd; }
        .pos-opt.on { border-color: var(--color-primary); background: #f0f9ff; }
        .pos-mini { display: flex; align-items: center; gap: 4px; height: 22px; }
        .pos-mini b { display: block; height: 14px; width: 30px; border-radius: 3px; background: #94a3b8; }
        .pos-mini i { display: block; height: 20px; width: 20px; border-radius: 4px; background: var(--color-primary); }
        input[type=range].sz { accent-color: var(--color-primary); }
    </style>
</head>
<body class="min-h-screen bg-slate-50 md:p-6 p-3">
    <div class="max-w-8xl mx-auto space-y-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-image text-sky-600"></i> โลโก้หน่วยงาน (ใบพิมพ์)</h1>
            <p class="text-sm text-slate-500 mt-1">โลโก้บริษัทลูกค้าจะแสดงในใบพิมพ์ทุกใบของหน่วยงานนี้ คู่กับโลโก้ PROACTIVE — เลือกได้ว่าจะวางก่อนหรือต่อจากโลโก้ PROACTIVE และปรับขนาดได้</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
            <!-- อัปโหลด -->
            <section class="lg:col-span-3 bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
                <h2 class="text-sm font-semibold text-slate-700 mb-3">โลโก้ปัจจุบัน</h2>
                <div class="checker rounded-xl border border-slate-200 h-44 flex items-center justify-center overflow-hidden">
                    <img id="logo-img" class="hidden max-h-36 max-w-[90%] object-contain" alt="โลโก้หน่วยงาน">
                    <div id="logo-empty" class="text-center text-slate-400 text-sm"><i class="fa-regular fa-image text-3xl mb-2 block"></i>ยังไม่ได้ตั้งโลโก้</div>
                </div>
                <p id="logo-meta" class="text-xs text-slate-400 mt-2"></p>

                <div id="edit-area" class="mt-4 hidden">
                    <label id="drop" class="drop rounded-xl p-5 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-slate-50">
                        <input id="file" type="file" accept="image/png,image/jpeg,image/webp,image/gif" class="hidden">
                        <i class="fa-solid fa-cloud-arrow-up text-2xl text-sky-600 mb-2"></i>
                        <span class="text-sm font-semibold text-slate-700">คลิกเพื่อเลือกไฟล์ หรือลากไฟล์มาวางที่นี่</span>
                        <span class="text-xs text-slate-400 mt-1">PNG, JPG, WEBP หรือ GIF ไม่เกิน 2 MB · แนะนำ PNG พื้นหลังโปร่งใส สูงอย่างน้อย 100 px</span>
                    </label>
                    <div class="flex flex-wrap gap-2 mt-3">
                        <button id="btn-delete" type="button" class="hidden h-10 px-4 rounded-lg border border-red-200 text-red-600 text-sm font-semibold hover:bg-red-50 flex items-center gap-2"><i class="fa-solid fa-trash-can"></i> ลบโลโก้</button>
                    </div>
                </div>
                <p id="no-perm" class="hidden mt-4 text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg p-2.5"><i class="fa-solid fa-lock mr-1"></i> เปลี่ยนโลโก้ได้เฉพาะผู้ดูแลระบบ</p>
            </section>

            <!-- ตัวอย่าง -->
            <section class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
                <h2 class="text-sm font-semibold text-slate-700 mb-3">ตัวอย่างหัวใบพิมพ์</h2>
                <div class="rounded-lg border border-slate-200 p-3 bg-white">
                    <div class="pv-head">
                        <div id="pv-logos" class="pv-logos">
                            <img src="Logo_โปร.png" alt="PROACTIVE">
                            <img id="pv-logo" class="hidden" alt="โลโก้หน่วยงาน">
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[13px] font-bold text-sky-800 truncate">รายงานผลการบำรุงรักษา (PM)</div>
                            <div class="text-[11px] text-slate-500 truncate"><?php echo htmlspecialchars((string)($_SESSION['sess_agency_name'] ?? '')); ?></div>
                        </div>
                    </div>
                    <div class="mt-3 space-y-1.5"><div class="h-2 bg-slate-100 rounded"></div><div class="h-2 bg-slate-100 rounded w-4/5"></div><div class="h-2 bg-slate-100 rounded w-3/5"></div></div>
                </div>
                <p class="text-xs text-slate-500 mt-3 leading-relaxed">ใช้กับ: ใบงานซ่อม (Service Report), ใบงาน PM, รายงาน PM (สรุป / รายเครื่อง / PM History) และสรุปการจดบันทึกมิเตอร์</p>

                <!-- ตำแหน่ง / ขนาด -->
                <div id="opt-area" class="hidden mt-4 pt-4 border-t border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700 mb-2">ตำแหน่งโลโก้</h2>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" data-pos="left" class="pos-opt rounded-lg p-2.5 text-left">
                            <span class="pos-mini"><i></i><b></b></span>
                            <span class="block text-xs font-semibold text-slate-700 mt-1.5">ก่อนโลโก้ PROACTIVE</span>
                            <span class="block text-[11px] text-slate-400">โลโก้หน่วยงานอยู่ซ้าย</span>
                        </button>
                        <button type="button" data-pos="right" class="pos-opt rounded-lg p-2.5 text-left">
                            <span class="pos-mini"><b></b><i></i></span>
                            <span class="block text-xs font-semibold text-slate-700 mt-1.5">ต่อจากโลโก้ PROACTIVE</span>
                            <span class="block text-[11px] text-slate-400">โลโก้หน่วยงานอยู่ขวา (ค่าเริ่มต้น)</span>
                        </button>
                    </div>

                    <div class="flex items-center justify-between mt-4 mb-1">
                        <h2 class="text-sm font-semibold text-slate-700">ขนาดโลโก้หน่วยงาน</h2>
                        <span class="text-sm font-semibold text-sky-700"><span id="sz-val">100</span>%</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="sz-dec" class="h-8 w-8 shrink-0 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" title="เล็กลง"><i class="fa-solid fa-minus text-xs"></i></button>
                        <input id="sz" type="range" class="sz flex-1 min-w-0" min="50" max="250" step="10" value="100" aria-label="ขนาดโลโก้หน่วยงาน">
                        <button type="button" id="sz-inc" class="h-8 w-8 shrink-0 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50" title="ใหญ่ขึ้น"><i class="fa-solid fa-plus text-xs"></i></button>
                    </div>
                    <div class="text-center mt-1"><button type="button" id="sz-reset" class="text-[11px] text-slate-400 hover:text-sky-700 hover:underline">กลับเป็นขนาดปกติ (100%)</button></div>
                    <p class="text-[11px] text-slate-400 mt-2 leading-relaxed">100% = สูงเท่าโลโก้ PROACTIVE · ในแต่ละใบพิมพ์จะย่อ/ขยายตามสัดส่วนหัวกระดาษของใบนั้น</p>

                    <div id="opt-edit" class="flex items-center justify-end gap-2 mt-3">
                        <span id="opt-dirty" class="hidden text-[11px] text-amber-600 mr-auto"><i class="fa-solid fa-circle-exclamation"></i> ยังไม่ได้บันทึก</span>
                        <button id="btn-opt-reset" type="button" class="hidden h-9 px-3 rounded-lg border border-slate-200 text-slate-600 text-sm hover:bg-slate-50">ยกเลิก</button>
                        <button id="btn-opt-save" type="button" class="h-9 px-4 rounded-lg bg-primary hover:bg-primary-dark text-white text-sm font-semibold flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed" disabled><i class="fa-solid fa-floppy-disk"></i> บันทึก</button>
                    </div>
                </div>
            </section>
        </div>
    </div>

<script>
(function () {
    const $ = (id) => document.getElementById(id);
    const fmtKB = (b) => b ? (b / 1024).toFixed(0) + ' KB' : '';
    const PV_H = 34, PV_W = 140;   // ขนาดโลโก้ในตัวอย่างที่ 100%
    let canEdit = false, saved = { pos: 'right', size: 100 }, cur = { pos: 'right', size: 100 };
    const posBtns = Array.from(document.querySelectorAll('.pos-opt'));
    const clampSize = (v) => Math.max(+$('sz').min, Math.min(+$('sz').max, Math.round(v / 10) * 10 || 100));

    // ตำแหน่ง/ขนาดในตัวอย่าง + สถานะปุ่ม
    function render() {
        const k = cur.size / 100;
        $('pv-logos').classList.toggle('ag-left', cur.pos === 'left');
        $('pv-logo').style.height = Math.round(PV_H * k) + 'px';
        $('pv-logo').style.maxWidth = Math.round(PV_W * k) + 'px';
        posBtns.forEach((b) => { const on = b.dataset.pos === cur.pos; b.classList.toggle('on', on); b.setAttribute('aria-pressed', on); });
        $('sz').value = cur.size;
        $('sz-val').textContent = cur.size;
        const dirty = cur.pos !== saved.pos || cur.size !== saved.size;
        $('opt-dirty').classList.toggle('hidden', !dirty);
        $('btn-opt-reset').classList.toggle('hidden', !dirty);
        $('btn-opt-save').disabled = !dirty;
    }
    function setCur(patch) { if (!canEdit) return; Object.assign(cur, patch); render(); }

    function show(logo) {
        const has = !!logo;
        $('logo-img').classList.toggle('hidden', !has);
        $('logo-empty').classList.toggle('hidden', has);
        $('pv-logo').classList.toggle('hidden', !has);
        $('opt-area').classList.toggle('hidden', !has);
        $('btn-delete').classList.toggle('hidden', !has);
        if (has) {
            $('logo-img').src = logo.url; $('pv-logo').src = logo.url;
            saved = { pos: logo.pos === 'left' ? 'left' : 'right', size: clampSize(logo.size || 100) };
            cur = Object.assign({}, saved);
            render();
        }
        $('logo-meta').textContent = has ? [logo.width && logo.height ? `${logo.width} × ${logo.height} px` : '', fmtKB(logo.bytes)].filter(Boolean).join(' · ') : '';
    }

    async function load() {
        const r = await (await fetch('handle_agency_logo.php?action=get')).json();
        if (!r.success) { Swal.fire('ข้อผิดพลาด', r.error || 'โหลดข้อมูลไม่สำเร็จ', 'error'); return; }
        canEdit = !!r.can_edit;
        if (r.size_min) $('sz').min = r.size_min;
        if (r.size_max) $('sz').max = r.size_max;
        ['sz', 'sz-dec', 'sz-inc', 'sz-reset'].concat(posBtns).forEach((el) => { (typeof el === 'string' ? $(el) : el).disabled = !canEdit; });
        $('opt-edit').classList.toggle('hidden', !canEdit);
        show(r.logo);
        $('edit-area').classList.toggle('hidden', !r.can_edit);
        $('no-perm').classList.toggle('hidden', !!r.can_edit);
    }

    async function upload(file) {
        if (!file) return;
        if (!/^image\/(png|jpeg|webp|gif)$/.test(file.type)) { Swal.fire('ไฟล์ไม่รองรับ', 'รองรับเฉพาะ PNG, JPG, WEBP หรือ GIF', 'warning'); return; }
        if (file.size > 2 * 1024 * 1024) { Swal.fire('ไฟล์ใหญ่เกินไป', 'ขนาดไฟล์ต้องไม่เกิน 2 MB', 'warning'); return; }
        const fd = new FormData(); fd.append('logo', file);
        Swal.fire({ title: 'กำลังอัปโหลด...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const r = await (await fetch('handle_agency_logo.php?action=upload', { method: 'POST', body: fd })).json();
            if (!r.success) throw new Error(r.error || 'อัปโหลดไม่สำเร็จ');
            show(r.logo);
            Swal.fire({ icon: 'success', title: 'บันทึกโลโก้แล้ว', text: 'ใบพิมพ์ทุกใบจะแสดงโลโก้นี้', timer: 1800, showConfirmButton: false });
        } catch (e) { Swal.fire('ข้อผิดพลาด', e.message, 'error'); }
        $('file').value = '';
    }

    $('file').addEventListener('change', (e) => upload(e.target.files[0]));
    const drop = $('drop');
    ['dragenter', 'dragover'].forEach((ev) => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.add('over'); }));
    ['dragleave', 'drop'].forEach((ev) => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.remove('over'); }));
    drop.addEventListener('drop', (e) => upload(e.dataTransfer.files[0]));

    $('btn-delete').addEventListener('click', async () => {
        const c = await Swal.fire({ title: 'ลบโลโก้หน่วยงาน?', text: 'ใบพิมพ์จะแสดงเฉพาะโลโก้ PROACTIVE', icon: 'warning', showCancelButton: true, confirmButtonText: 'ลบ', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#dc2626' });
        if (!c.isConfirmed) return;
        const r = await (await fetch('handle_agency_logo.php?action=delete', { method: 'POST' })).json();
        if (r.success) show(null); else Swal.fire('ข้อผิดพลาด', r.error || 'ลบไม่สำเร็จ', 'error');
    });

    // ตำแหน่ง / ขนาด
    posBtns.forEach((b) => b.addEventListener('click', () => setCur({ pos: b.dataset.pos })));
    $('sz').addEventListener('input', (e) => setCur({ size: clampSize(+e.target.value) }));
    $('sz-dec').addEventListener('click', () => setCur({ size: clampSize(cur.size - 10) }));
    $('sz-inc').addEventListener('click', () => setCur({ size: clampSize(cur.size + 10) }));
    $('sz-reset').addEventListener('click', () => setCur({ size: 100 }));
    $('btn-opt-reset').addEventListener('click', () => { cur = Object.assign({}, saved); render(); });
    $('btn-opt-save').addEventListener('click', async () => {
        const fd = new FormData(); fd.append('pos', cur.pos); fd.append('size', cur.size);
        $('btn-opt-save').disabled = true;
        try {
            const r = await (await fetch('handle_agency_logo.php?action=settings', { method: 'POST', body: fd })).json();
            if (!r.success) throw new Error(r.error || 'บันทึกไม่สำเร็จ');
            saved = { pos: r.pos, size: r.size }; cur = Object.assign({}, saved); render();
            Swal.fire({ icon: 'success', title: 'บันทึกแล้ว', text: 'ใบพิมพ์ทุกใบจะใช้ตำแหน่งและขนาดนี้', timer: 1600, showConfirmButton: false });
        } catch (e) { Swal.fire('ข้อผิดพลาด', e.message, 'error'); render(); }
    });

    load();
})();
</script>
</body>
</html>
