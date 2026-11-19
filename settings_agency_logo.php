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
        /* การจัดวาง */
        .pos-opt { border: 2px solid #e2e8f0; transition: .15s; }
        .pos-opt:hover:not(:disabled) { border-color: #93c5fd; }
        .pos-opt.on { border-color: var(--color-primary); background: #f0f9ff; }
        .pos-mini { display: flex; align-items: center; justify-content: center; gap: 4px; height: 26px; }
        .pos-mini b { display: block; height: 14px; width: 30px; border-radius: 3px; background: #94a3b8; }
        .pos-mini i { display: block; height: 20px; width: 20px; border-radius: 4px; background: var(--color-primary); }
        .al-mini { display: flex; justify-content: center; gap: 3px; height: 26px; }
        .al-mini i { display: block; width: 22px; height: 26px; border-radius: 4px; background: var(--color-primary); }
        .al-mini b { display: block; width: 26px; height: 11px; border-radius: 3px; background: #94a3b8; }
        .al-mini s { display: block; width: 18px; height: 6px; border-radius: 2px; background: #cbd5e1; }
        .opt-card { height: 76px; border-radius: .5rem; padding: .5rem; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .4rem; text-align: center; }
        .opt-card:disabled { cursor: not-allowed; }
        .opt-label { font-size: 12px; font-weight: 600; color: #334155; line-height: 1.25; }
        .opt-title { display: flex; align-items: center; justify-content: space-between; height: 1.5rem; margin-bottom: .5rem; }
        .opt-title h3 { font-size: .875rem; font-weight: 600; color: #334155; }
        .opt-title > span { font-size: .875rem; font-weight: 600; color: #0369a1; }
        .opt-step { height: 2rem; width: 2rem; flex-shrink: 0; border-radius: .5rem; border: 1px solid #e2e8f0; color: #475569; }
        .opt-step:hover:not(:disabled) { background: #f8fafc; }
        .opt-step:disabled { opacity: .5; cursor: not-allowed; }
        .opt-note { font-size: 11px; color: #94a3b8; margin-top: .5rem; min-height: 1rem; }
        .opt-reset:hover:not(:disabled) { color: #0369a1; text-decoration: underline; }
        .opt-block + .opt-block { border-top: 1px solid #f1f5f9; padding-top: 1.1rem; }
        input[type=range].sz { accent-color: var(--color-primary); }
        input[type=range].sz:disabled { opacity: .5; }
        #opt-area.no-logo .opt-body { opacity: .45; pointer-events: none; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 md:p-6 p-3">
    <div class="max-w-8xl mx-auto space-y-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-image text-sky-600"></i> โลโก้หน่วยงาน (ใบพิมพ์)</h1>
            <p class="text-sm text-slate-500 mt-1">โลโก้บริษัทลูกค้าจะแสดงในใบพิมพ์ทุกใบของหน่วยงานนี้ คู่กับโลโก้ PROACTIVE — เลือกได้ว่าจะวางก่อนหรือต่อจากโลโก้ PROACTIVE พร้อมปรับขนาด การจัดแนวตั้ง และระยะห่างได้</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-stretch">
            <!-- ซ้าย: โลโก้ + ตัวอย่าง -->
            <div class="flex flex-col gap-4">
                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
                    <h2 class="text-sm font-semibold text-slate-700 mb-3">โลโก้ปัจจุบัน</h2>
                    <div class="checker rounded-xl border border-slate-200 h-40 flex items-center justify-center overflow-hidden">
                        <img id="logo-img" class="hidden max-h-32 max-w-[90%] object-contain" alt="โลโก้หน่วยงาน">
                        <div id="logo-empty" class="text-center text-slate-400 text-sm"><i class="fa-regular fa-image text-3xl mb-2 block"></i>ยังไม่ได้ตั้งโลโก้</div>
                    </div>
                    <p id="logo-meta" class="text-xs text-slate-400 mt-2 min-h-[1rem]"></p>

                    <div id="edit-area" class="mt-2 hidden">
                        <div class="flex gap-2 items-stretch">
                            <label id="drop" class="drop flex-1 min-w-0 rounded-xl px-4 py-3 flex items-center gap-3 cursor-pointer hover:bg-slate-50">
                                <input id="file" type="file" accept="image/png,image/jpeg,image/webp,image/gif" class="hidden">
                                <i class="fa-solid fa-cloud-arrow-up text-2xl text-sky-600 shrink-0"></i>
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-slate-700">คลิกเพื่อเลือกไฟล์ หรือลากไฟล์มาวาง</span>
                                    <span class="block text-xs text-slate-400 mt-0.5">PNG, JPG, WEBP หรือ GIF ไม่เกิน 2 MB · แนะนำ PNG พื้นหลังโปร่งใส</span>
                                </span>
                            </label>
                            <button id="btn-delete" type="button" class="hidden shrink-0 w-20 rounded-xl border border-red-200 text-red-600 text-sm font-semibold hover:bg-red-50 flex flex-col items-center justify-center gap-1" title="ลบโลโก้"><i class="fa-solid fa-trash-can"></i><span class="text-xs">ลบโลโก้</span></button>
                        </div>
                    </div>
                    <p id="no-perm" class="hidden mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-lg p-2.5"><i class="fa-solid fa-lock mr-1"></i> เปลี่ยนโลโก้และการจัดวางได้เฉพาะผู้ดูแลระบบ</p>
                </section>

                <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5 flex-1 flex flex-col">
                    <h2 class="text-sm font-semibold text-slate-700 mb-3">ตัวอย่างหัวใบพิมพ์</h2>
                    <div class="rounded-xl border border-slate-200 p-4 bg-white flex-1 flex flex-col justify-center min-h-[9rem]">
                        <div id="pv-head" class="pv-head">
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
                    <p class="text-xs text-slate-500 mt-2 leading-relaxed">ใช้กับ: ใบงานซ่อม (Service Report), ใบงาน PM, รายงาน PM (สรุป / รายเครื่อง / PM History) และสรุปการจดบันทึกมิเตอร์</p>
                </section>
            </div>

            <!-- ขวา: การจัดวางโลโก้ -->
            <section id="opt-area" class="no-logo bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5 flex flex-col">
                <div class="flex flex-wrap items-center gap-2 pb-3 border-b border-slate-100">
                    <h2 class="text-sm font-semibold text-slate-700 flex items-center gap-2 mr-auto"><i class="fa-solid fa-sliders text-sky-600"></i> การจัดวางโลโก้</h2>
                    <div id="opt-edit" class="flex items-center gap-2">
                        <span id="opt-dirty" class="hidden text-[11px] text-amber-600"><i class="fa-solid fa-circle-exclamation"></i> ยังไม่ได้บันทึก</span>
                        <button id="btn-opt-reset" type="button" class="hidden h-9 px-3 rounded-lg border border-slate-200 text-slate-600 text-sm hover:bg-slate-50">ยกเลิก</button>
                        <button id="btn-opt-save" type="button" class="h-9 px-4 rounded-lg bg-primary hover:bg-primary-dark text-white text-sm font-semibold flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed" disabled><i class="fa-solid fa-floppy-disk"></i> บันทึก</button>
                    </div>
                </div>
                <p id="opt-empty" class="mt-3 text-xs text-slate-500 bg-slate-50 border border-slate-100 rounded-lg p-2.5"><i class="fa-solid fa-circle-info mr-1 text-sky-600"></i> อัปโหลดโลโก้ก่อน จึงจะตั้งค่าการจัดวางได้</p>

                <div class="opt-body flex-1 flex flex-col justify-between gap-5 mt-4">
                    <!-- ตำแหน่ง -->
                    <div class="opt-block">
                        <div class="opt-title"><h3>ตำแหน่งโลโก้หน่วยงาน</h3></div>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" data-pos="left" class="pos-opt opt-card"><span class="pos-mini"><i></i><b></b></span><span class="opt-label">ก่อนโลโก้ PROACTIVE</span></button>
                            <button type="button" data-pos="right" class="pos-opt opt-card"><span class="pos-mini"><b></b><i></i></span><span class="opt-label">ต่อจากโลโก้ PROACTIVE</span></button>
                        </div>
                        <p class="opt-note">ค่าเริ่มต้น: ต่อจากโลโก้ PROACTIVE (อยู่ขวา)</p>
                    </div>

                    <!-- แนวตั้ง -->
                    <div class="opt-block">
                        <div class="opt-title"><h3>การจัดแนวตั้ง</h3></div>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" data-align="top" class="al-opt pos-opt opt-card"><span class="al-mini" style="align-items:flex-start"><i></i><b></b><s></s></span><span class="opt-label">ชิดบน</span></button>
                            <button type="button" data-align="center" class="al-opt pos-opt opt-card"><span class="al-mini" style="align-items:center"><i></i><b></b><s></s></span><span class="opt-label">กึ่งกลาง</span></button>
                            <button type="button" data-align="bottom" class="al-opt pos-opt opt-card"><span class="al-mini" style="align-items:flex-end"><i></i><b></b><s></s></span><span class="opt-label">ชิดล่าง</span></button>
                        </div>
                        <p class="opt-note">จัดแนวโลโก้ทั้งสอง และข้อความหัวรายงาน</p>
                    </div>

                    <!-- ขนาด -->
                    <div class="opt-block">
                        <div class="opt-title"><h3>ขนาดโลโก้หน่วยงาน</h3><span><span id="sz-val">100</span>%</span></div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="sz-dec" class="opt-step" title="เล็กลง"><i class="fa-solid fa-minus text-xs"></i></button>
                            <input id="sz" type="range" class="sz flex-1 min-w-0" min="50" max="250" step="10" value="100" aria-label="ขนาดโลโก้หน่วยงาน">
                            <button type="button" id="sz-inc" class="opt-step" title="ใหญ่ขึ้น"><i class="fa-solid fa-plus text-xs"></i></button>
                        </div>
                        <p class="opt-note flex justify-between gap-2"><span>100% = สูงเท่าโลโก้ PROACTIVE</span><button type="button" id="sz-reset" class="opt-reset">ค่าปกติ (100%)</button></p>
                    </div>

                    <!-- ระยะห่าง -->
                    <div class="opt-block">
                        <div class="opt-title"><h3>ระยะห่างระหว่างโลโก้</h3><span><span id="gap-val">10</span> px</span></div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="gap-dec" class="opt-step" title="ชิดขึ้น"><i class="fa-solid fa-minus text-xs"></i></button>
                            <input id="gap" type="range" class="sz flex-1 min-w-0" min="0" max="60" step="2" value="10" aria-label="ระยะห่างระหว่างโลโก้">
                            <button type="button" id="gap-inc" class="opt-step" title="ห่างขึ้น"><i class="fa-solid fa-plus text-xs"></i></button>
                        </div>
                        <p class="opt-note flex justify-between gap-2"><span>0 = ชิดกัน · มากสุด 60 px</span><button type="button" id="gap-reset" class="opt-reset">ค่าปกติ (10 px)</button></p>
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
    const DEF = { pos: 'right', size: 100, align: 'center', gap: 10 };
    let canEdit = false, saved = Object.assign({}, DEF), cur = Object.assign({}, DEF);
    const posBtns = Array.from(document.querySelectorAll('.pos-opt[data-pos]'));
    const alBtns = Array.from(document.querySelectorAll('.al-opt'));
    const clampGap = (v) => Math.max(0, Math.min(+$('gap').max, Math.round(v) || 0));
    const AL_CSS = { top: 'flex-start', center: 'center', bottom: 'flex-end' };
    const clampSize = (v) => Math.max(+$('sz').min, Math.min(+$('sz').max, Math.round(v / 10) * 10 || 100));

    // ตำแหน่ง/ขนาดในตัวอย่าง + สถานะปุ่ม
    function render() {
        const k = cur.size / 100;
        $('pv-logos').classList.toggle('ag-left', cur.pos === 'left');
        $('pv-logo').style.height = Math.round(PV_H * k) + 'px';
        $('pv-logo').style.maxWidth = Math.round(PV_W * k) + 'px';
        $('pv-logos').style.alignItems = AL_CSS[cur.align];
        $('pv-head').style.alignItems = AL_CSS[cur.align];   // ข้อความหัวรายงานจัดแนวตามโลโก้
        $('pv-logos').style.gap = cur.gap + 'px';
        posBtns.forEach((b) => { const on = b.dataset.pos === cur.pos; b.classList.toggle('on', on); b.setAttribute('aria-pressed', on); });
        alBtns.forEach((b) => { const on = b.dataset.align === cur.align; b.classList.toggle('on', on); b.setAttribute('aria-pressed', on); });
        $('gap').value = cur.gap;
        $('gap-val').textContent = cur.gap;
        $('sz').value = cur.size;
        $('sz-val').textContent = cur.size;
        const dirty = Object.keys(DEF).some((k) => cur[k] !== saved[k]);
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
        $('opt-area').classList.toggle('no-logo', !has);   // ยังไม่มีโลโก้ => ตั้งค่าการจัดวางไม่ได้
        $('opt-empty').classList.toggle('hidden', has);
        $('opt-edit').classList.toggle('hidden', !has || !canEdit);
        $('btn-delete').classList.toggle('hidden', !has);
        if (has) {
            $('logo-img').src = logo.url; $('pv-logo').src = logo.url;
            saved = { pos: logo.pos === 'left' ? 'left' : 'right', size: clampSize(logo.size || 100), align: AL_CSS[logo.align] ? logo.align : 'center', gap: clampGap(logo.gap == null ? 10 : logo.gap) };
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
        if (r.gap_max) $('gap').max = r.gap_max;
        ['sz', 'sz-dec', 'sz-inc', 'sz-reset', 'gap', 'gap-dec', 'gap-inc', 'gap-reset'].concat(posBtns, alBtns).forEach((el) => { (typeof el === 'string' ? $(el) : el).disabled = !canEdit; });
        show(r.logo);
        $('edit-area').classList.toggle('hidden', !r.can_edit);
        $('no-perm').classList.toggle('hidden', !!r.can_edit);
    }

    async function upload(file) {
        if (!file || !canEdit) return;
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
    alBtns.forEach((b) => b.addEventListener('click', () => setCur({ align: b.dataset.align })));
    $('gap').addEventListener('input', (e) => setCur({ gap: clampGap(+e.target.value) }));
    $('gap-dec').addEventListener('click', () => setCur({ gap: clampGap(cur.gap - 2) }));
    $('gap-inc').addEventListener('click', () => setCur({ gap: clampGap(cur.gap + 2) }));
    $('gap-reset').addEventListener('click', () => setCur({ gap: 10 }));
    $('btn-opt-reset').addEventListener('click', () => { cur = Object.assign({}, saved); render(); });
    $('btn-opt-save').addEventListener('click', async () => {
        const fd = new FormData(); Object.keys(DEF).forEach((k) => fd.append(k, cur[k]));
        $('btn-opt-save').disabled = true;
        try {
            const r = await (await fetch('handle_agency_logo.php?action=settings', { method: 'POST', body: fd })).json();
            if (!r.success) throw new Error(r.error || 'บันทึกไม่สำเร็จ');
            saved = { pos: r.pos, size: r.size, align: r.align, gap: r.gap }; cur = Object.assign({}, saved); render();
            Swal.fire({ icon: 'success', title: 'บันทึกแล้ว', text: 'ใบพิมพ์ทุกใบจะใช้การจัดวางโลโก้นี้', timer: 1600, showConfirmButton: false });
        } catch (e) { Swal.fire('ข้อผิดพลาด', e.message, 'error'); render(); }
    });

    load();
})();
</script>
</body>
</html>
