<?php
@session_start();
// ตั้งค่าโลโก้หน่วยงาน (บริษัทลูกค้า) — แสดงในใบพิมพ์ทุกใบ ต่อจากโลโก้ PROACTIVE (ทางขวา)
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
        .pv-logos img { height: 34px; width: auto; max-width: 140px; object-fit: contain; }
        .pv-sep { width: 1px; height: 26px; background: #cbd5e1; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 md:p-6 p-3">
    <div class="max-w-8xl mx-auto space-y-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-image text-sky-600"></i> โลโก้หน่วยงาน (ใบพิมพ์)</h1>
            <p class="text-sm text-slate-500 mt-1">โลโก้บริษัทลูกค้าจะแสดงในใบพิมพ์ทุกใบของหน่วยงานนี้ <b>ต่อจากโลโก้ PROACTIVE ทางขวา</b> </p>
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
                        <div class="pv-logos">
                            <img src="Logo_โปร.png" alt="PROACTIVE">
                            <span id="pv-sep" class="pv-sep hidden"></span>
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
            </section>
        </div>
    </div>

<script>
(function () {
    const $ = (id) => document.getElementById(id);
    const fmtKB = (b) => b ? (b / 1024).toFixed(0) + ' KB' : '';

    function show(logo) {
        const has = !!logo;
        $('logo-img').classList.toggle('hidden', !has);
        $('logo-empty').classList.toggle('hidden', has);
        $('pv-logo').classList.toggle('hidden', !has);
        $('pv-sep').classList.toggle('hidden', !has);
        $('btn-delete').classList.toggle('hidden', !has);
        if (has) { $('logo-img').src = logo.url; $('pv-logo').src = logo.url; }
        $('logo-meta').textContent = has ? [logo.width && logo.height ? `${logo.width} × ${logo.height} px` : '', fmtKB(logo.bytes)].filter(Boolean).join(' · ') : '';
    }

    async function load() {
        const r = await (await fetch('handle_agency_logo.php?action=get')).json();
        if (!r.success) { Swal.fire('ข้อผิดพลาด', r.error || 'โหลดข้อมูลไม่สำเร็จ', 'error'); return; }
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

    load();
})();
</script>
</body>
</html>
