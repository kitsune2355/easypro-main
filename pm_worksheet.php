<?php 
//pm_worksheet.php
@session_start();
include "config_ctrl/checksession.php"; 

$plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : '';
$mode = isset($_GET['mode']) ? $_GET['mode'] : '';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบงานบำรุงรักษาเชิงป้องกัน (PM Worksheet)</title>
    <script>
        // ขนาดตัวอักษรทั้งโปรแกรม (เมนู ตั้งค่าการแสดงผล) — หน้านี้เปิดเป็นหน้าต่างแยก จึงขยายเองด้วย zoom
        //  เปิดเป็น dialog (iframe ในหน้าวางแผน PM) => กรอบนอกถูกขยายตามขนาดตัวอักษรอยู่แล้ว ไม่ต้องขยายซ้ำ
        (function () { if (window.parent !== window) return; let z = 1; try { z = parseFloat(localStorage.getItem('easypro_font_scale')) || 1; } catch (e) {} if (z !== 1) document.documentElement.style.zoom = Math.min(1.5, Math.max(0.8, z)); })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#006b9f',
                        secondary: '#004a6f',
                    },
                }
            }
        }
    </script>
    <link rel="icon" type="image/png" href="../es/logo - easypro2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script src="js/pm_approve_sign.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="js/img-carousel.js"></script>
    
    <style>
        body { 
            font-family: 'Kanit', sans-serif; 
            background-color: #f1f5f9; 
        }
        
        /* หมายเหตุ: @apply ใช้ไม่ได้กับ Tailwind CDN ใน <style> ปกติ จึงเขียนเป็น CSS ตรง */
        .custom-file-upload {
            display: flex; width: 100%; padding: .5rem; cursor: pointer; text-align: center;
            border: 2px dashed #cbd5e1; border-radius: .75rem; background: #f8fafc; transition: all .2s;
        }
        .custom-file-upload:hover { border-color: #006b9f; background: #eff6ff; }

        /* ===== ใบงาน PM: แถบขั้นตอน (stepper) ===== */
        .ws-step-btn { width: 100%; height: 100%; display: flex; align-items: center; gap: .625rem; padding: .5rem .75rem; border-radius: .75rem; border: 1px solid #e2e8f0; background: #fff; color: #475569; text-align: left; transition: all .15s; }
        .ws-step-btn:hover { border-color: #94a3b8; }
        .ws-step-num { flex-shrink: 0; width: 1.75rem; height: 1.75rem; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; font-size: .8125rem; font-weight: 700; background: #f1f5f9; color: #64748b; }
        .ws-step-title { display: block; font-size: .875rem; font-weight: 600; line-height: 1.25; }
        .ws-step-sub { display: block; font-size: .6875rem; color: #94a3b8; line-height: 1.3; margin-top: .125rem; }
        .ws-step-btn.is-active { background: #006b9f; border-color: #006b9f; color: #fff; box-shadow: 0 4px 10px rgba(0, 107, 159, .2); }
        .ws-step-btn.is-active .ws-step-num { background: rgba(255, 255, 255, .2); color: #fff; }
        .ws-step-btn.is-active .ws-step-sub { color: rgba(255, 255, 255, .8); }
        .ws-step-btn.is-done:not(.is-active) .ws-step-num { background: #dcfce7; color: #15803d; }
        @media (max-width: 639.98px) {
            .ws-step-btn { flex-direction: column; gap: .25rem; padding: .5rem .25rem; text-align: center; }
            .ws-step-title { font-size: .75rem; }
            .ws-step-sub { display: none; }
        }

        /* ===== รายการจุดตรวจสอบ: ตาราง (md ขึ้นไป) / การ์ด (มือถือ) + แผงรายละเอียดของข้อที่เลือก ===== */
        .ws-sheet-wrap { overflow-x: auto; }
        .ws-sheet { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .875rem; }
        .ws-sheet th, .ws-sheet td { background: #fff; border-bottom: 1px solid #e2e8f0; padding: .625rem .75rem; vertical-align: top; text-align: left; }
        .ws-sheet th + th, .ws-sheet td + td { border-left: 1px solid #f1f5f9; }
        .ws-sheet thead th { background: #f8fafc; color: #64748b; font-size: .75rem; font-weight: 600; white-space: nowrap; }
        .ws-sheet .ws-col-no { width: 3rem; text-align: center; color: #64748b; font-weight: 600; box-shadow: inset 4px 0 0 #cbd5e1; }
        .ws-sheet .ws-col-point { min-width: 12rem; }
        .ws-sheet .ws-col-status { width: 15rem; min-width: 13rem; }
        .ws-sheet .ws-col-value { width: 11rem; min-width: 9rem; }
        .ws-sheet .ws-col-photo { width: 7rem; min-width: 6.5rem; }
        .ws-sheet tbody tr.ws-item { cursor: pointer; }
        @media not all {   /* hover ของตารางเดิม: ไม่ใช้แล้ว */
            .ws-sheet tbody tr.ws-item:hover td { background: #f8fafc; }
        }
        .ws-sheet td:focus-within { outline: 2px solid #006b9f; outline-offset: -2px; }
        /* สถานะของแต่ละข้อ: แถบสีที่คอลัมน์ลำดับ + พื้นแดงเมื่อผิดปกติ, แถวที่เลือกเป็นพื้นฟ้า */
        .ws-item[data-state="Pass"] .ws-col-no { box-shadow: inset 4px 0 0 #16a34a; color: #15803d; }
        .ws-item[data-state="Fail"] .ws-col-no { box-shadow: inset 4px 0 0 #dc2626; color: #b91c1c; }
        .ws-item[data-state="N/A"]  .ws-col-no { box-shadow: inset 4px 0 0 #64748b; }
        .ws-sheet tbody tr.ws-item[data-state="Fail"] td { background: #fef2f2; }
        .ws-sheet tbody tr.ws-item.is-selected td { background: #eff6ff; }
        .ws-sheet tbody tr.ws-item.is-selected[data-state="Fail"] td { background: #fee2e2; }
        .ws-choice { min-height: 2.25rem; }
        .ws-photo { min-height: 3.75rem; padding: .25rem; }
        .ws-hide { display: none !important; }
        .ws-mobile-no, .ws-chip { display: none !important; }
        .ws-more-btn { display: none; }

        /* แผงรายละเอียด: จอ lg ขึ้นไปอยู่ด้านขวาของตาราง, จอเล็กกว่าเป็นแผ่นเลื่อนขึ้นจากด้านล่าง */
        @media not all {   /* แผงรายละเอียดด้านขวา: ไม่ใช้แล้ว (แสดงเป็นแผ่นเลื่อนขึ้นทุกขนาดจอ) */
            .ws-body { display: grid; grid-template-columns: minmax(0, 1fr) 21rem; }
            .ws-detail { position: sticky; top: var(--ws-detail-top, 9rem); align-self: start; max-height: calc(100vh - var(--ws-detail-top, 9rem) - 5.5rem); overflow-y: auto; border-left: 1px solid #e2e8f0; background: #fff; border-radius: 0 0 .75rem 0; }
            .ws-detail-head, .ws-backdrop { display: none !important; }
        }
        @media all {
            .ws-detail { position: fixed; left: 0; right: 0; bottom: 0; z-index: 60; max-height: 82vh; overflow-y: auto; background: #fff; border-radius: 1rem 1rem 0 0; box-shadow: 0 -10px 30px rgba(15, 23, 42, .18); transform: translateY(105%); visibility: hidden; transition: transform .25s ease, visibility .25s; }
            .ws-detail.is-open { transform: translateY(0); visibility: visible; }
            .ws-backdrop { position: fixed; inset: 0; z-index: 55; background: rgba(15, 23, 42, .45); opacity: 0; pointer-events: none; transition: opacity .2s; }
            .ws-backdrop.is-open { opacity: 1; pointer-events: auto; }
            .ws-more-btn { display: inline-flex; align-items: center; gap: .375rem; margin-top: .5rem; font-size: .75rem; font-weight: 500; color: #006b9f; }
        }
        @media (min-width: 768px) {
            .ws-detail { left: 50%; right: auto; width: 36rem; max-width: 100%; transform: translate(-50%, 105%); }
            .ws-detail.is-open { transform: translate(-50%, 0); }
        }

        /* แต่ละข้อเป็นการ์ด ทุกขนาดจอ — แบบเดียวกับหน้าบันทึกพร้อมกัน (pm_worksheet_batch.php) */
        @media all {
            .ws-sheet-wrap { overflow: visible; padding: .75rem; background: #f8fafc; border-radius: 0 0 .75rem .75rem; }
            .ws-sheet, .ws-sheet tbody { display: block; }
            .ws-sheet thead, .ws-sheet tr.ws-item > td.ws-col-no, .ws-sheet tr.ws-item > td.ws-empty { display: none; }
            .ws-sheet tbody tr.ws-item { display: flex; flex-direction: column; margin-bottom: .75rem; border: 1px solid #e2e8f0; border-left: 4px solid #cbd5e1; border-radius: .75rem; overflow: hidden; background: #fff; box-shadow: 0 1px 2px rgba(15, 23, 42, .05); cursor: default; }
            .ws-sheet tbody tr.ws-item[data-state="Pass"] { border-left-color: #16a34a; }
            .ws-sheet tbody tr.ws-item[data-state="Fail"] { border-left-color: #dc2626; }
            .ws-sheet tbody tr.ws-item[data-state="N/A"]  { border-left-color: #64748b; }
            .ws-sheet tr.ws-item > td { display: block; width: auto; min-width: 0; border: 0; border-top: 1px solid #f1f5f9; padding: .625rem .875rem; box-shadow: none; }
            .ws-sheet tbody tr.ws-item.is-selected > td { background: #fff; }
            .ws-sheet tbody tr.ws-item[data-state="Fail"] > td { background: #fef2f2; }
            .ws-sheet tr.ws-item > td.ws-col-point { border-top: 0; background: #f8fafc; }
            .ws-sheet td[data-label]::before { content: attr(data-label); display: block; font-size: .6875rem; font-weight: 600; color: #64748b; margin-bottom: .375rem; }
            .ws-sheet td:focus-within { outline: none; }
            .ws-mobile-no { display: inline-flex !important; align-items: center; justify-content: center; flex-shrink: 0; width: 1.75rem; height: 1.75rem; border-radius: 9999px; background: rgba(0, 107, 159, .1); color: #006b9f; font-weight: 700; font-size: .8125rem; }
            .ws-chip { display: inline-flex !important; }
            .ws-choice { min-height: 2.75rem; }
            .ws-photo { min-height: 4.5rem; }
        }

        /* ===== หน้าตาแบบเดียวกับหน้าบันทึกพร้อมกัน (pm_worksheet_batch.php) ===== */
        body { background: #f8fafc; }
        .ws-topbar { padding-top: env(safe-area-inset-top, 0px); }
        .ws-topbtn { width: 2.25rem; height: 2.25rem; border-radius: .75rem; background: rgba(255, 255, 255, .15); display: inline-flex; align-items: center; justify-content: center; flex: none; }
        .ws-topbtn:hover { background: rgba(255, 255, 255, .25); }
        .ws-pbar { height: 6px; border-radius: 99px; background: rgba(255, 255, 255, .25); overflow: hidden; }
        .ws-pbar > span { display: block; height: 100%; background: #fff; border-radius: 99px; transition: width .3s; }
        .ws-blk { background: #fff; border: 1px solid #e2e8f0; border-radius: .75rem; padding: .65rem .75rem; }
        .ws-chip2 { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .55rem; border-radius: 9999px; border: 1px solid; font-size: 11.5px; font-weight: 600; }
        /* อะไหล่ที่ต้องเตรียม + ไฟล์แนบ ต่อกันในการ์ดเครื่อง: เส้นคั่นเส้นเดียว */
        #spares-container:not(.hidden) + #checksheet-files-section { border-top: 0; padding-top: 0; margin-top: .5rem; }
        .ws-info-lbl { display: block; font-size: .6875rem; color: #64748b; font-weight: 500; margin-bottom: .125rem; }
        /* กล่องของแต่ละส่วน: มุมมน ไม่มีเงา ระยะห่างแบบหน้าบันทึกพร้อมกัน */
        #pmForm .ws-step > div.bg-white { border-radius: 1rem; box-shadow: none; margin-bottom: .75rem; }
        #pmForm .ws-step > div.bg-white > div:first-child h2 { font-size: .9375rem; }
        #pmForm .ws-step > div.bg-white > div:first-child { padding: .75rem 1rem; }
        .ws-sheet-wrap { padding: .75rem; }
        /* การ์ดจุดตรวจแบบกะทัดรัด (เหมือนหน้าบันทึกพร้อมกัน): หัวบรรทัดเดียว · ผลการตรวจ + รูป แถวเดียวกัน · ค่าที่วัดขึ้นบรรทัดใหม่บนจอเล็ก */
        .ws-sheet-wrap { padding: .5rem; }
        .ws-sheet tbody tr.ws-item { flex-direction: row; flex-wrap: wrap; align-items: stretch; gap: .5rem; padding: 0 .625rem .5rem; margin-bottom: .5rem; border-left-width: 3px; border-radius: .625rem; box-shadow: none; }
        .ws-sheet tr.ws-item > td { padding: 0; border: 0; background: transparent !important; }
        .ws-sheet tbody tr.ws-item[data-state="Fail"] { background: #fef2f2; }
        .ws-sheet tr.ws-item > td.ws-col-point { flex: 0 0 100%; padding: .45rem 0 0; background: transparent; }
        .ws-sheet tr.ws-item > td.ws-col-status { flex: 1 1 0; min-width: 0; }
        .ws-sheet tr.ws-item > td.ws-col-value { flex: 1 1 100%; order: 3; }
        .ws-sheet tr.ws-item > td.ws-col-photo { flex: 0 0 4.25rem; }
        .ws-sheet td.ws-col-status[data-label]::before, .ws-sheet td.ws-col-photo[data-label]::before { display: none; }
        .ws-sheet td.ws-col-value[data-label]::before { margin-bottom: .2rem; }
        .ws-sheet .ws-status-box, .ws-sheet .ws-status-box > .flex { height: 100%; }
        .ws-sheet .ws-status-box > .flex { gap: .25rem; }
        .ws-mobile-no { width: 1.375rem !important; height: 1.375rem !important; font-size: .6875rem !important; }
        .ws-sheet .ws-choice { min-height: 2.25rem; border-width: 1.5px; font-size: 13px; padding: 0 .375rem; gap: .3rem; }
        .ws-sheet .ws-choice i { font-size: 11px; }
        .ws-sheet .ws-photo { min-height: 2.25rem; height: 100%; padding: .125rem; border-width: 1.5px; border-radius: .5rem; }
        .ws-sheet .ws-photo .fa-camera { font-size: .875rem; }
        .ws-sheet .ws-photo [id^="upload_ui_"] > span { font-size: 10px; }
        .ws-sheet tr.ws-item img[id^="img_preview_"] { height: 2.5rem; }
        .ws-sheet .ws-value-input { padding: .3rem .5rem; height: 2.25rem; }
        /* ผลการตรวจ | ค่าที่วัด | รูป อยู่ระนาบเดียวกัน: ชื่อค่า/มาตรฐานย้ายไปเป็นป้ายในหัวการ์ดแล้ว */
        .ws-sheet td.ws-col-value[data-label]::before, .ws-sheet td.ws-col-value > div.text-xs { display: none; }
        .ws-sheet td.ws-col-value > .flex { height: 100%; }
        .ws-sheet [id^="photo_label_"] { display: none; }
        .ws-sheet .ws-photo [id^="upload_ui_"] { flex-direction: row !important; gap: .25rem; line-height: 1; }
        .ws-sheet .ws-photo [id^="upload_ui_"] > span { margin-top: 0 !important; }
        .ws-sheet .ws-photo { min-height: 0 !important; }
        .ws-sheet tr.ws-item img[id^="img_preview_"] { height: 1.95rem !important; }
        .ws-more-btn { display: inline-flex !important; flex: none; width: 1.75rem; height: 1.75rem; margin: 0 !important; border-radius: 9999px; align-items: center; justify-content: center; color: #94a3b8 !important; }
        .ws-more-btn:hover { color: #006b9f !important; background: #f1f5f9; }
        .ws-chip { font-size: 10px !important; padding: 0 .4rem !important; }
        @media (min-width: 640px) {
            .ws-sheet tr.ws-item > td.ws-col-value { flex: 0 0 10rem; order: 0; }
            .ws-sheet tr.ws-item > td.ws-col-photo { flex: 0 0 5rem; }
        }
        /* แถบปุ่มล่าง */
        .ws-foot { position: fixed; left: 0; right: 0; bottom: 0; z-index: 40; background: #fff; border-top: 1px solid #e2e8f0; padding: .6rem .75rem; padding-bottom: calc(.6rem + env(safe-area-inset-bottom, 0px)); }
        .ws-btn { border-radius: .75rem; padding: .6rem .9rem; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; justify-content: center; gap: .5rem; transition: background .15s; }
        .ws-btn:disabled { opacity: .5; cursor: not-allowed; }
        /* แผ่นลงชื่อและบันทึก (โหมดบันทึกเท่านั้น) */
        .ws-edit-only { display: none !important; }
        body.ws-edit .ws-edit-only { display: flex !important; }
        body.ws-edit .ws-signsheet { position: fixed; left: 0; right: 0; bottom: 0; z-index: 60; max-width: 56rem; margin: 0 auto; max-height: 92vh; overflow-y: auto; background: #f8fafc; border-radius: 1.25rem 1.25rem 0 0; padding: 1rem; padding-bottom: 0; box-shadow: 0 -10px 30px rgba(15, 23, 42, .2); transform: translateY(105%); visibility: hidden; transition: transform .22s ease, visibility .22s; }
        body.ws-edit .ws-signsheet.open { transform: none; visibility: visible; }
        .ws-sign-foot { position: sticky; bottom: 0; gap: .5rem; background: #f8fafc; padding: .6rem 0 calc(.75rem + env(safe-area-inset-bottom, 0px)); margin-top: .25rem; }
        .ws-scrim { position: fixed; inset: 0; z-index: 55; background: rgba(15, 23, 42, .5); opacity: 0; pointer-events: none; transition: opacity .2s; }
        body.ws-edit .ws-scrim.open { opacity: 1; pointer-events: auto; }
        body.ws-sheet-open { overflow: hidden; }
        @media (prefers-reduced-motion: reduce) { .ws-signsheet, .ws-scrim, .ws-pbar > span { transition: none !important; } }

        details > summary { list-style: none; }
        details > summary::-webkit-details-marker { display: none; }
        
        input[type="radio"]:checked + span.radio-label {
            font-weight: 700;
        }
        
        /* สไตล์สำหรับ Canvas ลายเซ็น */
        .signature-canvas {
            width: 100%;
            height: 150px;
            border-radius: 0.5rem;
            cursor: crosshair;
        }

        /* ซ่อน radio จริงๆ แล้วใช้ custom style สำหรับการประเมิน */
        .eval-radio:checked + label {
            @apply bg-primary text-white border-primary;
        }
		.field-error {
			border-color: #ef4444 !important;
			box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12) !important;
		}
		
		.item-error-summary {
			border: 1px solid #fecaca !important;
			background: #fef2f2 !important;
		}
		
		.item-error-panel {
			border-color: #fca5a5 !important;
		}
		.pm-swal-enterprise {
			border-radius: 22px !important;
			padding: 1.1rem !important;
			box-shadow:
				0 10px 25px rgba(15, 23, 42, 0.10),
				0 4px 10px rgba(15, 23, 42, 0.06) !important;
		}
		
		.pm-swal-title {
			font-family: 'Kanit', sans-serif !important;
			font-size: 1.35rem !important;
			font-weight: 700 !important;
			color: #0f172a !important;
			padding-bottom: 0.35rem !important;
		}
		
		.pm-swal-html {
			margin-top: 0.25rem !important;
			margin-bottom: 0.5rem !important;
		}
		
		.swal2-styled.swal2-confirm {
			border-radius: 12px !important;
			font-weight: 600 !important;
			padding: 0.72rem 1.4rem !important;
			box-shadow: 0 4px 10px rgba(0, 107, 159, 0.18) !important;
		}
		.pm-validation-scroll::-webkit-scrollbar {
			width: 8px;
		}
		
		.pm-validation-scroll::-webkit-scrollbar-track {
			background: #f1f5f9;
			border-radius: 999px;
		}
		
		.pm-validation-scroll::-webkit-scrollbar-thumb {
			background: #cbd5e1;
			border-radius: 999px;
		}
		
		.pm-validation-scroll::-webkit-scrollbar-thumb:hover {
			background: #94a3b8;
		}
        /* สถานะ รอตรวจรับ: เขียวอ่อน ขอบเส้นปะ (ทำแล้ว = เขียว ขอบทึบ) */
        .pm-review { background: #f0fdf4; color: #047857; border: 1px dashed #059669; }
    </style>
</head>
<body class="text-slate-700 pb-28">

    <!-- หัวหน้า: แบบเดียวกับหน้าบันทึกพร้อมกัน (pm_worksheet_batch.php) — ชื่อเช็คชีต · เครื่อง/วันที่ · ความคืบหน้า -->
    <nav class="ws-topbar bg-primary text-white sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-4 pt-3 pb-2.5">
            <div class="flex items-center gap-2">
                <!-- เปิดจากรายการเครื่อง/ป๊อปอัปเลือกในปฏิทิน => กลับไปหน้านั้น -->
                <button type="button" id="ws-back" onclick="wsBack()" class="ws-topbtn hidden !w-auto px-2.5 gap-1.5 text-sm font-semibold"><i class="fa-solid fa-chevron-left"></i><span id="ws-back-label" class="hidden sm:inline">กลับ</span></button>
                <div class="min-w-0 flex-1">
                    <h1 class="text-base sm:text-lg font-semibold leading-tight truncate"><span>บันทึกผลการบำรุงรักษา (PM Worksheet)</span></h1>
                    <p id="ws-nav-sub" class="text-xs opacity-90 truncate">กำลังโหลด...</p>
                </div>
                <button type="button" onclick="openPmEventMatrixReport()" title="รายงาน PM ตามรอบงาน" class="ws-topbtn"><i class="fa-solid fa-table-cells-large"></i></button>
                <button type="button" onclick="openPmMonthlyReport()" title="รายงาน PM รายเดือน" class="ws-topbtn"><i class="fa-solid fa-table"></i></button>
                <?php if (!empty($mode)): ?>
                <button type="button" onclick="openPmPrintPage()" title="พิมพ์ใบงาน PM" class="ws-topbtn"><i class="fa-solid fa-print"></i></button>
                <button type="button" id="ws-cancel-rec" onclick="wsCancelRecord()" title="ยกเลิกผลบันทึก แล้วส่งกลับให้ทำใหม่" class="ws-topbtn hidden !w-auto px-2.5 gap-1.5 text-sm font-semibold"><i class="fa-solid fa-rotate-left"></i><span class="hidden sm:inline">ยกเลิกผล</span></button>
                <?php endif; ?>
                <button type="button" onclick="wsClose()" title="ปิด" class="ws-topbtn"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div class="ws-pbar mt-2"><span id="ws-nav-bar" style="width:0%"></span></div>
            <div class="flex items-center justify-between mt-1.5 text-[11px] opacity-90">
                <span id="ws-nav-mode"><?php echo !empty($mode) ? 'ประวัติที่บันทึกแล้ว' : 'ใบงานรายเครื่อง'; ?></span>
                <span class="tabular-nums" id="ws-nav-count"></span>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-3 sm:px-4 mt-3 space-y-3" id="app-container" style="display: none;">
        <!-- เคยถูกยกเลิกผลบันทึก (ส่งกลับให้ทำใหม่) — wsRenderCancelInfo() -->
        <div id="ws-cancel-note" class="hidden rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900"></div>
        
        <form id="pmForm" onSubmit="event.preventDefault();">
            <input type="hidden" id="plan_id" name="plan_id" value="<?php echo htmlspecialchars($plan_id); ?>">

            <!-- แถบขั้นตอนเดิม: ไม่แสดงแล้ว (หน้าเป็นแบบเลื่อนหน้าเดียวเหมือนหน้าบันทึกพร้อมกัน) — คง id ไว้ให้โค้ดเดิมอ้างอิงได้ -->
            <div id="ws-stepper" class="hidden">
                <button type="button" class="ws-step-btn" data-ws-step-go="2"><span class="ws-step-num">2</span><span id="ws-step-sub-2"></span></button>
            </div>

            <!-- ===== ขั้นที่ 1: ข้อมูลงาน — การ์ดเครื่องแบบเดียวกับหน้าบันทึกพร้อมกัน ===== -->
            <section class="ws-step" data-ws-step="1">
            <div class="ws-blk">
                <!-- รายละเอียดงานจากปฏิทิน (สถานะ เลยกำหนด เลื่อน) — เติมโดย wsLoadJobDetail() -->
                <div id="ws-job-chips" class="flex flex-wrap items-center gap-1.5 mb-2 empty:hidden"></div>
                <div id="ws-job-machine" class="text-lg font-semibold text-slate-800 leading-tight break-words">-</div>
                <div class="text-sm text-slate-600 mt-0.5" id="ws-job-sub"></div>
                <div class="text-xs text-slate-400 mt-0.5">เลขที่เอกสาร <span id="ws-job-doc">-</span> · กำหนดการ <span id="ws-job-date">-</span></div>
                <div id="ws-job-tiles" class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-3 empty:hidden"></div>
                <div id="ws-job-extra" class="empty:hidden"></div>
                <!-- อะไหล่ที่ต้องเตรียม + ไฟล์แนบประกอบเช็คชีต: อยู่ในการ์ดเครื่อง — เติมโดย renderSpares() / renderFiles() -->
                <div id="spares-container" class="hidden mt-3 pt-2.5 border-t border-slate-100">
                    <div class="ws-info-lbl"><i class="fa-solid fa-screwdriver-wrench mr-1"></i>อะไหล่ที่ต้องเตรียม</div>
                    <div id="spares-list" class="flex flex-wrap gap-1.5"></div>
                </div>
                <div id="checksheet-files-section" class="hidden mt-3 pt-2.5 border-t border-slate-100 space-y-2">
                    <div id="checksheet-images-block" class="hidden">
                        <div class="ws-info-lbl"><i class="fa-regular fa-image mr-1"></i>ภาพจุดตรวจสอบ</div>
                        <div id="checksheet-images-list" class="flex flex-wrap gap-1.5"></div>
                    </div>
                    <div id="checksheet-docs-block" class="hidden">
                        <div class="ws-info-lbl"><i class="fa-solid fa-paperclip mr-1"></i>คำแนะนำในการทำงาน</div>
                        <div id="checksheet-docs-list" class="flex flex-wrap gap-1.5"></div>
                    </div>
                </div>
                <!-- ข้อมูลหัวใบงาน: ไม่แสดง (ใช้เติมการ์ดเครื่อง/หัวหน้า และโหมดประวัติ) — คง id ไว้ -->
                <div class="hidden" aria-hidden="true">
                    <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-x-3 gap-y-2 text-[13px] rounded-lg bg-slate-50 border border-slate-100 p-2.5">
                        <div><label class="ws-info-lbl">เลขที่เอกสาร</label><div id="info-doc-no" class="font-semibold text-slate-900">-</div></div>
                        <div><label class="ws-info-lbl">Revision No</label><div id="info-rev-no" class="font-semibold text-slate-900">-</div></div>
                        <div class="col-span-2"><label class="ws-info-lbl">ชื่อเช็คชีต</label><div id="info-checksheet-name" class="font-semibold text-slate-900">-</div></div>
                        <div class="col-span-2"><label class="ws-info-lbl">ชื่อเครื่องจักร</label><div id="info-machine-name" class="font-semibold text-primary">-</div></div>
                        <div><label class="ws-info-lbl">ซีเรียล (Serial Number)</label><div id="info-machine-sn" class="font-semibold text-slate-900">-</div></div>
                        <div><label class="ws-info-lbl">ประเภทเครื่องจักร</label><div id="info-machine-type" class="font-semibold text-slate-900">-</div></div>
                        <div class="col-span-2"><label class="ws-info-lbl">สถานที่ตั้งเครื่องจักร</label><div id="info-location" class="font-semibold text-slate-900">-</div></div>
                        <div><label class="ws-info-lbl">วันที่ตามกำหนดการ</label><div id="info-plan-date" class="font-semibold text-amber-700 flex items-center gap-1.5"><i class="fa-solid fa-calendar-day"></i><span>-</span></div></div>
                        <div><label class="ws-info-lbl">วันที่ดำเนินการจริง</label><div id="info-actual-date" class="font-semibold text-green-700 flex items-center gap-1.5"><i class="fa-solid fa-calendar-check"></i><span><?php echo date('Y-m-d'); ?></span></div></div>
                    </div>
                </div>
            </div>


            </section>

            <!-- ===== ขั้นที่ 2: ตรวจสอบ ===== -->
            <section class="ws-step" data-ws-step="2">
            <!-- ไม่ใช้ overflow-hidden เพื่อให้แถบความคืบหน้า (sticky) ติดด้านบนขณะเลื่อน -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-6">
                <div class="bg-slate-50 border-b border-slate-200 px-4 sm:px-6 py-4 flex items-center gap-2.5 rounded-t-xl">
                    <i class="fa-solid fa-list-check text-primary text-lg"></i>
                    <h2 class="text-base sm:text-lg font-semibold text-slate-800">รายการจุดตรวจสอบ</h2>
                </div>
                <div id="items-list" class="rounded-b-xl"></div>
            </div>
            </section>

            <!-- ===== ขั้นที่ 3: อะไหล่/วัสดุที่ใช้จริง ===== -->
            <section class="ws-step" data-ws-step="3">
            <!-- *** รายการอะไหล่ที่ใช้จริง (Actual Spares Used) *** -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
                <div class="bg-emerald-50 border-b border-emerald-200 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid fa-check-double text-emerald-700 text-lg"></i>
                        <h2 class="text-lg font-semibold text-emerald-900">รายการอะไหล่/วัสดุที่ใช้จริง</h2>
                    </div>
                </div>
                <div class="p-6">
                    <?php include "stock_function.php"; ?>
                </div>
            </div>
            </section>

            <!-- ===== ขั้นที่ 4: สรุป & ลงชื่อ ===== -->
            <section class="ws-step" data-ws-step="4">
            <div id="ws-scrim" class="ws-scrim" onclick="wsCloseSign()"></div>
            <!-- โหมดบันทึก: เป็นแผ่นเลื่อนขึ้นจากด้านล่าง (เหมือน "ส่งที่ครบแล้ว" ของหน้าบันทึกพร้อมกัน) · โหมดประวัติ: แสดงในหน้าตามปกติ -->
            <div id="ws-signsheet" class="ws-signsheet" role="dialog" aria-label="ลงชื่อและบันทึก">
                <div class="ws-edit-only flex items-center justify-between mb-3">
                    <h3 class="font-semibold text-slate-800 text-lg">ลงชื่อและบันทึก</h3>
                    <button type="button" onclick="wsCloseSign()" class="w-10 h-10 rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-50" title="กลับไปตรวจ"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div id="ws-summary" class="bg-white rounded-xl border border-slate-200 mb-3"></div>
            <div class="bg-white rounded-xl border border-slate-200 p-4 sm:p-5 mb-3">
                <h3 class="text-base font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100"><i class="fa-solid fa-user-gear text-primary mr-2"></i>ส่วนของผู้ปฏิบัติงาน / ผู้ตรวจสอบ</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1.5">
                                <i class="fa-solid fa-user-check text-slate-500"></i>
                                <span>ชื่อผู้ตรวจสอบ <span class="text-red-500">*</span></span>
                            </label>
                            <div class="relative custom-select-container">
                                <div id="inspector_display" class="w-full border border-slate-300 rounded-lg px-3 py-2 pr-8 bg-white cursor-pointer flex flex-wrap gap-1 min-h-[42px] items-center shadow-sm" onClick="toggleDropdown('inspector_dropdown')">
                                    <span class="text-slate-400 text-sm">คลิกเพื่อเลือกชื่อ...</span>
                                </div>
                                
                                <button type="button" id="inspector_clear_btn" onClick="clearSelection('inspector', event)" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-500 focus:outline-none transition-colors">
                                    <i class="fa-solid fa-circle-xmark"></i>
                                </button>

                                <div id="inspector_dropdown" class="hidden absolute z-[9999] w-full mt-1 bg-white border border-slate-300 rounded-lg shadow-lg max-h-60 overflow-y-auto p-2">
                                    </div>
                                <input type="hidden" id="inspector_name" name="inspector_name" required>
                                <input type="hidden" id="inspector_users_json" name="inspector_users_json">
                            </div>
                        </div>
                        <!-- วันที่ทำ PM: ตามการตั้งค่าหน่วยงาน (วันนี้ / เลือกได้ภายในช่วงสัญญา/แผน) -->
                        <div id="ws-date-wrap" class="hidden">
                            <label for="actual_date" class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1.5">
                                <i class="fa-regular fa-calendar-check text-slate-500"></i>
                                <span>วันที่ทำ PM <span id="ws-date-req" class="text-red-500 hidden">*</span></span>
                            </label>
                            <div id="ws-date-today" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-slate-50 text-sm text-slate-700"></div>
                            <input type="date" id="actual_date" class="hidden w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none text-sm shadow-sm bg-white">
                            <p id="ws-date-hint" class="text-xs text-slate-500 mt-1.5"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1.5">
                                <i class="fa-solid fa-comment-dots text-slate-500"></i>
                                <span>หมายเหตุ / ข้อเสนอแนะเพิ่มเติม</span>
                            </label>
                            <textarea id="remarks" name="remarks" rows="3" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none text-sm transition" placeholder="ระบุรายละเอียดเพิ่มเติม (ถ้ามี)"></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-signature text-slate-500"></i>
                            <span>ลายมือชื่อผู้ตรวจสอบ <span id="ws-sign-req" class="text-red-500">*</span><span id="ws-sign-opt" class="hidden text-xs font-normal text-slate-400">(ไม่บังคับ)</span></span>
                        </label>
                        <div class="border border-slate-300 rounded-lg overflow-hidden bg-white shadow-sm relative group">
                            <canvas id="inspector-signature-pad" class="signature-canvas touch-none"></canvas>
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-40 group-hover:opacity-10 transition-opacity" id="inspector-signature-placeholder">
                                <span class="text-slate-400 font-medium"><i class="fa-solid fa-pen mr-1"></i> เซ็นชื่อที่นี่</span>
                            </div>
                            <div class="absolute top-2 right-2">
                                <button type="button" id="clear-inspector-signature" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-md text-xs font-medium border border-slate-200 transition-colors shadow-sm" title="ล้างลายเซ็น">
                                    <i class="fa-solid fa-eraser"></i> ล้าง
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                <div class="ws-edit-only ws-sign-foot">
                    <button type="button" onclick="wsCloseSign()" class="ws-btn border border-slate-200 bg-white text-slate-700"><i class="fa-solid fa-chevron-left"></i>กลับไปตรวจ</button>
                    <button type="button" id="btn_save_work" onClick="saveWorkRecord()" class="ws-btn flex-1 bg-emerald-600 hover:bg-emerald-700 text-white"><i class="fa-solid fa-floppy-disk"></i>บันทึกผลการปฏิบัติงาน</button>
                </div>
            </div>

            <div id="evaluation_section" class="hidden mt-3 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="bg-blue-50 border-b border-blue-100 px-6 py-4 flex flex-col md:flex-row items-start md:items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid fa-star-half-stroke text-primary text-lg"></i>
                        <h2 class="text-lg font-semibold text-slate-800">การประเมินผลการปฏิบัติงาน <span class="text-sm font-normal text-slate-500 ml-1">(สำหรับผู้ประเมิน)</span></h2>
                    </div>
                    <div class="flex justify-end items-center gap-3 p-2 bg-white rounded-lg border border-slate-200 shadow-sm">
                        <span class="text-base font-medium text-slate-700">คะแนนประเมินเฉลี่ย :</span>
                        <span id="display_average_score" class="text-2xl font-bold text-primary">0.00</span>
                        <span class="text-sm text-slate-500">/ 5.00</span>
                        <input type="hidden" id="eval_average_score" name="eval_average_score" value="0">
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="bg-slate-50/50 rounded-xl border border-slate-200 p-5 mb-6">
                        <p class="text-sm text-slate-500 mb-4"><i class="fa-solid fa-circle-info mr-1"></i> เกณฑ์การประเมิน: 5 = ดีมาก, 4 = ดี, 3 = ปานกลาง, 2 = พอใช้, 1 = ควรปรับปรุง</p>
                        
                        <div id="eval-topics-container" class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-4">
                            <div class="col-span-1 lg:col-span-2 text-center p-4 text-slate-500">
                                <i class="fas fa-spinner fa-spin mr-2"></i> กำลังโหลดข้อมูลการประเมิน...
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1.5">
                                    <i class="fa-solid fa-comment-dots text-slate-500"></i>
                                    <span>ข้อเสนอแนะจากการประเมิน</span>
                                </label>
                                <textarea id="eval_comments" name="eval_comments" rows="2" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none text-sm transition" placeholder="ระบุข้อเสนอแนะเพิ่มเติมเพื่อการปรับปรุง..."></textarea>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1.5">
                                        <i class="fa-solid fa-user-tie text-slate-500"></i>
                                        <span>ชื่อผู้ประเมิน <span class="text-red-500">*</span></span>
                                    </label>
                                    <div class="relative custom-select-container">
                                        <div id="evaluator_display" class="w-full border border-slate-300 rounded-lg px-3 py-2 pr-8 bg-white cursor-pointer flex flex-wrap gap-1 min-h-[42px] items-center shadow-sm" onClick="toggleDropdown('evaluator_dropdown')">
                                            <span class="text-slate-400 text-sm">คลิกเพื่อเลือกชื่อ...</span>
                                        </div>

                                        <button type="button" id="evaluator_clear_btn" onClick="clearSelection('evaluator', event)" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-500 focus:outline-none transition-colors">
                                            <i class="fa-solid fa-circle-xmark"></i>
                                        </button>

                                        <div id="evaluator_dropdown" class="hidden absolute z-[9999] bottom-full mb-1 w-full bg-white border border-slate-300 rounded-lg shadow-lg max-h-60 overflow-y-auto p-2">
                                            </div>
                                        <input type="hidden" id="evaluator_name" name="evaluator_name" required>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1.5">
                                        <i class="fa-solid fa-calendar-check text-slate-500"></i>
                                        <span>วันที่ประเมินผล <span class="text-red-500">*</span></span>
                                    </label>
                                    <input type="date" id="eval_date" name="eval_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none text-sm transition">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1.5">
                                <i class="fa-solid fa-signature text-slate-500"></i>
                                <span>ลายมือชื่อผู้ประเมิน <span class="text-red-500">*</span></span>
                            </label>
                            <div class="border border-slate-300 rounded-lg overflow-hidden bg-white shadow-sm relative group">
                                <canvas id="evaluator-signature-pad" class="signature-canvas touch-none"></canvas>
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-40 group-hover:opacity-10 transition-opacity" id="evaluator-signature-placeholder">
                                    <span class="text-slate-400 font-medium"><i class="fa-solid fa-pen mr-1"></i> เซ็นชื่อผู้ประเมินที่นี่</span>
                                </div>
                                <div class="absolute top-2 right-2">
                                    <button type="button" id="clear-evaluator-signature" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-md text-xs font-medium border border-slate-200 transition-colors shadow-sm" title="ล้างลายเซ็น">
                                        <i class="fa-solid fa-eraser"></i> ล้าง
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </section>

            <!-- ===== แถบปุ่มด้านล่าง (ติดขอบล่างจอ) — แบบเดียวกับหน้าบันทึกพร้อมกัน ===== -->
            <div id="ws-actionbar" class="ws-foot">
                <div class="max-w-4xl mx-auto flex items-center gap-2">
                    <div id="ws-bar-status" class="flex-1 min-w-0 text-sm text-slate-500 leading-tight"></div>
                    <button type="button" id="ws-open-sign" onclick="wsOpenSign()" class="ws-btn flex-[2] bg-primary hover:bg-primary/90 text-white"><i class="fa-solid fa-signature"></i>ลงชื่อ &amp; บันทึก</button>
                    <div id="ws-save-actions" class="flex gap-2">
                        <button type="button" id="btn_save_eval" onClick="saveEvaluation()" class="hidden ws-btn bg-green-600 hover:bg-green-700 text-white"><i class="fa-solid fa-star"></i>บันทึกการประเมิน</button>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <div id="loading" class="fixed inset-0 bg-white/90 backdrop-blur-sm z-50 flex flex-col items-center justify-center transition-all duration-300">
        <i class="fa-solid fa-circle-notch fa-spin text-5xl text-primary mb-4"></i>
        <p class="text-slate-600 font-medium text-lg">กำลังโหลดข้อมูล...</p>
    </div>

    <script>
        window.AG_ID = '<?php echo isset($sess_user_agency_es) ? $sess_user_agency_es : ""; ?>';
        window.USER_ID = '<?php echo isset($sess_user_id) ? $sess_user_id : ""; ?>';

        const urlParams = new URLSearchParams(window.location.search);
		const planId = urlParams.get('plan_id') || document.getElementById('plan_id').value;
		
		const pageMode = urlParams.get('mode') || '<?php echo htmlspecialchars($mode, ENT_QUOTES, 'UTF-8'); ?>';
		const isHistoryMode = pageMode === 'history';

		// เปิดได้ 2 แบบ: dialog ในหน้าวางแผน PM (iframe) หรือหน้าต่างแยก
		const WS_EMBED = window.parent !== window;
		let wsSaving = false;
		window.pmBatchBusy = () => wsSaving;   // dialog ถามก่อนปิด: กำลังบันทึกอยู่ห้ามปิด
		// force = ปิดโดยไม่ถาม (หลังบันทึกสำเร็จ)
		function wsClose(force, goBack) {
			if (WS_EMBED && window.parent.closePmDialog) window.parent.closePmDialog(force === true, goBack === true);
			else {
				// เปิดเป็นหน้าเต็ม (เช่น สแกน QR เครื่องจักร) — ปิดแท็บไม่ได้ => กลับหน้าก่อนหน้า
				if (force !== true && window.pmDirty && window.pmDirty() && !confirm('ข้อมูลที่กรอกไว้ยังไม่ได้บันทึก ถ้าออกจะหายไป ต้องการออกหรือไม่?')) return;
				window.close();
				setTimeout(() => { if (!window.closed) { if (history.length > 1) history.back(); else location.href = 'main.php#pm.php'; } }, 300);
			}
		}
		// กลับไปหน้าก่อนหน้า (ถามก่อนถ้ามีข้อมูลที่ยังไม่บันทึก — ถามที่หน้าวางแผน PM)
		function wsBack() { wsClose(false, true); }
		(function () {
			let back = null;
			try { back = WS_EMBED && window.parent.pmDialogBack ? window.parent.pmDialogBack() : null; } catch (e) {}
			if (!back) return;
			const b = document.getElementById('ws-back');
			b.classList.remove('hidden'); b.title = back.label;
			document.getElementById('ws-back-label').textContent = back.label;
		})();
		// มีข้อมูลที่กรอกแต่ยังไม่บันทึก => dialog ถามก่อนปิด (คลิกพื้นหลัง / ✕ / Esc / ย้อนกลับ)
		window.pmDirty = () => {
			if (isHistoryMode) return false;
			if (document.querySelector('#items-list input[type="radio"]:checked')) return true;
			if ([...document.querySelectorAll('#items-list .ws-value-input')].some(i => i.value.trim())) return true;
			if ([...document.querySelectorAll('input[id^="photo_base64_"]')].some(i => i.value)) return true;
			if (document.querySelectorAll('#parts_wrap > div').length) return true;
			return !!(document.getElementById('remarks')?.value || '').trim();
		};

		// รายละเอียดงานจากปฏิทิน (เดิมเป็นป๊อปอัปแยกก่อนเปิดใบงาน): สถานะ เลยกำหนด ความถี่ เวลาโดยประมาณ ทำครั้งล่าสุด ประวัติเลื่อน ผลการทำงาน
		async function wsLoadJobDetail() {
			let r;
			try { r = await (await fetch(`handle_pm_dashboard.php?action=get_event_detail&id=${encodeURIComponent(planId)}`)).json(); } catch (e) { return; }
			if (!r || !r.success) return;
			const d = r.data, ev = d.event || {};
			const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
			const fmt = s => { if (!s) return '-'; const x = new Date(String(s).slice(0, 10) + 'T00:00:00'); return isNaN(x) ? s : x.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' }); };
			const chip = (cls, icon, text) => `<span class="ws-chip2 ${cls}"><i class="${icon}"></i>${text}</span>`;
			const done = String(ev.status) === '1';
			const chips = [done ? chip('bg-emerald-50 text-emerald-700 border-emerald-200', 'fa-solid fa-circle-check', 'ดำเนินการแล้ว')
				: chip('bg-sky-50 text-sky-700 border-sky-200', 'fa-solid fa-clock', 'รอดำเนินการ')];
			if (!done && ev.event_date && d.today) {
				const diff = Math.round((new Date(ev.event_date + 'T00:00:00') - new Date(d.today + 'T00:00:00')) / 86400000);
				chips.push(diff < 0 ? chip('bg-red-50 text-red-700 border-red-200', 'fa-solid fa-triangle-exclamation', `เลยกำหนด ${-diff} วัน`)
					: diff === 0 ? chip('bg-amber-50 text-amber-700 border-amber-200', 'fa-solid fa-bolt', 'ครบกำหนดวันนี้')
					: chip('bg-slate-50 text-slate-600 border-slate-200', 'fa-regular fa-clock', `อีก ${diff} วัน`));
			}
			if ((d.postpones || []).length) chips.push(chip('bg-violet-50 text-violet-700 border-violet-200', 'fa-solid fa-clock-rotate-left', `เลื่อนแล้ว ${d.postpones.length} ครั้ง`));
			document.getElementById('ws-job-chips').innerHTML = chips.join('');

			const tile = (icon, color, label, value) => `<div class="flex items-center gap-2 p-2 rounded-xl bg-slate-50 border border-slate-100 min-w-0">
				<span class="w-7 h-7 shrink-0 rounded-lg bg-white shadow-sm flex items-center justify-center ${color}"><i class="${icon} text-xs"></i></span>
				<span class="min-w-0"><span class="block text-[10.5px] text-slate-400 leading-tight">${label}</span><span class="block text-[13px] font-semibold text-slate-700 truncate">${value}</span></span></div>`;
			document.getElementById('ws-job-tiles').innerHTML =
				tile('fa-solid fa-calendar-day', 'text-sky-600', 'วันที่กำหนด', fmt(ev.event_date))
				+ tile('fa-solid fa-rotate-right', 'text-indigo-500', 'ความถี่', esc(ev.freq_desc || '-'))
				+ tile('fa-solid fa-stopwatch', 'text-amber-500', 'เวลาโดยประมาณ', ev.estimated_time ? `~${esc(ev.estimated_time)} นาที` : '-')
				+ tile('fa-solid fa-check-double', 'text-emerald-600', 'ทำครั้งล่าสุด', d.last_done ? fmt(d.last_done) : 'ยังไม่เคยทำ');

			const pp = (d.postpones || [])[0], w = d.work;
			document.getElementById('ws-job-extra').innerHTML = (pp ? `<div class="mt-2 p-2.5 rounded-xl bg-violet-50 border border-violet-100 text-[13px] text-violet-800">
					<i class="fa-solid fa-clock-rotate-left mr-1"></i> เลื่อนจาก <b>${fmt(pp.old_date)}</b> เป็น <b>${fmt(pp.new_date)}</b>${pp.reason ? `<div class="text-[12px] text-violet-600 mt-0.5">เหตุผล: ${esc(pp.reason)}</div>` : ''}</div>` : '')
				+ (w ? `<div class="mt-2 p-2.5 rounded-xl bg-emerald-50 border border-emerald-100 text-[13px] text-emerald-900 space-y-0.5">
					<div><i class="fa-solid fa-calendar-check mr-1"></i> ทำเมื่อ <b>${fmt(w.actual_date || ev.completed_at)}</b></div>
					${(w.inspectors || []).length ? `<div><i class="fa-solid fa-user-check mr-1"></i> ผู้ตรวจ: ${w.inspectors.map(esc).join(', ')}</div>` : ''}
					<div><i class="fa-solid fa-star mr-1"></i> ${w.evaluation ? `ประเมินแล้ว คะแนนเฉลี่ย <b>${esc(w.evaluation.average_score)}</b>${w.evaluation.evaluator_name ? ` โดย ${esc(w.evaluation.evaluator_name)}` : ''}` : 'ยังไม่ได้ประเมิน'}</div></div>` : '');
		}
        let inspectorPad, evaluatorPad;
        let totalEvalTopics = 0;
        let usersData = [];
		let worksheetItems = [];
		window.latestValidationErrors = [];
		
		
        async function loadUsers() {
            try {
                const response = await axios.get(`handle_pm_worksheet.php?action=get_users&ag_id=${AG_ID}`);
                if (response.data.success) {
                    const allUsers = response.data.data;

                    // ----------------------------------------------------
                    // กรองสำหรับผู้ตรวจสอบ: ต้องการ user_department 3 และ 4
                    // (เนื่องจาก Backend Query ดึงมาเฉพาะ 3 กับ 4 ให้แล้ว จึงใช้ทั้งหมดได้เลย)
                    const inspectorUsers = allUsers; 

                    // กรองสำหรับผู้ประเมิน: ต้องการเฉพาะ user_department 4 เท่านั้น
                    const evaluatorUsers = allUsers.filter(user => user.user_department == 4 || user.user_department == '4');
                    // ----------------------------------------------------

                    // ส่ง Array ที่แยกกันไปสร้าง Checkbox ในแต่ละ Dropdown
                    renderCheckboxes('inspector_dropdown', 'inspector', inspectorUsers);
                    renderCheckboxes('evaluator_dropdown', 'evaluator', evaluatorUsers);
                }
            } catch (error) {
                console.error('Error fetching users:', error);
            }
        }
		
		function formatThaiDate(dateString) {
			if (!dateString) return '-';
		
			const date = new Date(dateString);
			if (isNaN(date.getTime())) return dateString || '-';
		
			return date.toLocaleDateString('th-TH', {
				year: 'numeric',
				month: 'long',
				day: 'numeric'
			});
		}

        // 2. ฟังก์ชันสร้าง Checkbox เข้าไปใน Dropdown
        // เก็บทั้งชื่อสำหรับแสดงผล และ user_id สำหรับบันทึกลงตารางลูก
        function renderCheckboxes(containerId, type, usersList) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
            
            if (usersList.length === 0) {
                container.innerHTML = '<div class="p-2 text-sm text-slate-400 text-center">ไม่พบรายชื่อ</div>';
                return;
            }

            // ช่องค้นหาชื่อ (ติดด้านบนขณะเลื่อนรายชื่อ)
            container.innerHTML = `
                <div class="sticky -top-2 -mx-2 -mt-2 px-2 pt-2 pb-1.5 bg-white z-[1]">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                        <input type="search" class="ws-user-q w-full pl-7 pr-2 py-1.5 border border-slate-200 rounded-md text-sm outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary" placeholder="ค้นหาชื่อ..." autocomplete="off">
                    </div>
                </div>
                <div class="ws-user-empty hidden p-2 text-sm text-slate-400 text-center">ไม่พบชื่อที่ค้นหา</div>`;
            container.querySelector('.ws-user-q').addEventListener('input', e => {
                const q = e.target.value.trim().toLowerCase();
                let shown = 0;
                container.querySelectorAll('.ws-user-opt').forEach(el => {
                    const hit = !q || el.dataset.q.includes(q);
                    el.classList.toggle('hidden', !hit);
                    if (hit) shown++;
                });
                container.querySelector('.ws-user-empty').classList.toggle('hidden', shown > 0);
            });

            usersList.forEach(user => {
                const userId = String(user.user_id || '');       // เช่น i-2, i-3
                const userPkId = String(user.id || '');          // primary key ใน tb_user เช่น 8, 9
                const name = `${user.user_name || ''} ${user.user_fname || ''}`.trim();
                const safeName = String(name).replace(/"/g, '&quot;');
                const safeUserId = String(userId).replace(/"/g, '&quot;');
                const safeUserPkId = String(userPkId).replace(/"/g, '&quot;');
                
                const html = `
                    <label class="ws-user-opt flex items-center gap-2 p-2 hover:bg-slate-50 rounded cursor-pointer" data-q="${safeName.toLowerCase()}">
                        <input
                            type="checkbox"
                            value="${safeUserId}"
                            data-user-id="${safeUserId}"
                            data-user-pk-id="${safeUserPkId}"
                            data-user-name="${safeName}"
                            class="${type}-checkbox w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary"
                            onchange="updateSelection('${type}')"
                        >
                        <span class="text-sm text-slate-700">${name}</span>
                    </label>
                `;
                container.insertAdjacentHTML('beforeend', html);
            });
        }

        // 3. ฟังก์ชันเปิด/ปิด Dropdown
        function toggleDropdown(dropdownId) {
            // ปิด dropdown อื่นๆ ก่อนเปิดอันใหม่
            document.getElementById('inspector_dropdown').classList.add('hidden');
            document.getElementById('evaluator_dropdown').classList.add('hidden');
            
            // เปิดตัวที่เลือก
            const dd = document.getElementById(dropdownId);
            dd.classList.remove('hidden');
            // จอที่มีเมาส์: โฟกัสช่องค้นหาเลย (มือถือไม่โฟกัส ไม่ให้คีย์บอร์ดเด้งบังรายชื่อ)
            if (window.matchMedia('(pointer: fine)').matches) dd.querySelector('.ws-user-q')?.focus();
        }

        // ตั้งค่าการบันทึกผลของหน่วยงาน (handle_pm_worksheet.php?action=get_data → record_setting)
        let wsRecordSetting = { sign_required: 1, date_mode: 'today', window: null };
        const wsShortDate = d => new Date(d + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
        function applyRecordSetting(s) {
            if (!s) return;
            wsRecordSetting = s;
            const signReq = !!+s.sign_required;
            document.getElementById('ws-sign-req')?.classList.toggle('hidden', !signReq);
            document.getElementById('ws-sign-opt')?.classList.toggle('hidden', signReq);

            const wrap = document.getElementById('ws-date-wrap'), inp = document.getElementById('actual_date');
            const txt = document.getElementById('ws-date-today'), hint = document.getElementById('ws-date-hint');
            if (!wrap || !inp) return;
            wrap.classList.remove('hidden');
            const w = s.window;
            const pick = s.date_mode === 'pick' && w;
            inp.classList.toggle('hidden', !pick);
            txt.classList.toggle('hidden', !!pick);
            document.getElementById('ws-date-req')?.classList.toggle('hidden', !pick);
            if (!pick) {
                txt.textContent = formatThaiDate(s.today) + ' (วันที่บันทึก)';
                hint.textContent = '';
                return;
            }
            inp.min = w.min; inp.max = w.max;
            inp.value = s.today < w.min ? w.min : (s.today > w.max ? w.max : s.today);
            const range = w.early ? 'ยังไม่ถึงวันเริ่มสัญญา — บันทึกเป็นวันนี้'
                : (w.min === w.max ? `เลือกได้เฉพาะ ${wsShortDate(w.min)}` : `เลือกได้ ${wsShortDate(w.min)} – ${wsShortDate(w.max)} (ช่วงสัญญา)`);
            const upd = () => { hint.innerHTML = (inp.value ? `<b class="text-slate-700">${formatThaiDate(inp.value)}</b> · ` : '') + range; };
            inp.oninput = () => { inp.classList.remove('field-error'); upd(); };
            upd();
        }

        function clearSelection(type, event) {
            // ป้องกันไม่ให้การคลิกปุ่มไปทำให้ Dropdown เปิด (Stop Event Bubbling)
            if (event) {
                event.stopPropagation();
            }
            
            // เอาติ๊กถูกออกจาก Checkbox ทั้งหมดของกลุ่มนั้น
            const checkboxes = document.querySelectorAll(`.${type}-checkbox`);
            checkboxes.forEach(cb => cb.checked = false);
            
            // เรียกใช้ฟังก์ชันอัปเดตหน้าจอ
            updateSelection(type);
        }

        // 4. ฟังก์ชันอัปเดตหน้าจอเมื่อมีการติ๊ก Checkbox
        // type = inspector หรือ evaluator
        // สำหรับ inspector จะเติม inspector_name และ inspector_users_json
        function updateSelection(type) {
            const checkboxes = document.querySelectorAll(`.${type}-checkbox:checked`);

            const selectedUsers = Array.from(checkboxes).map(cb => ({
                user_id: cb.dataset.userId || cb.value,
                user_pk_id: cb.dataset.userPkId || '',
                name: cb.dataset.userName || cb.value
            }));

            const selectedNames = selectedUsers.map(user => user.name);
            
            const display = document.getElementById(`${type}_display`);
            const hiddenNameInput = document.getElementById(`${type}_name`);
            const hiddenUsersJsonInput = document.getElementById(`${type}_users_json`);
            const clearBtn = document.getElementById(`${type}_clear_btn`);
            
            if (selectedNames.length > 0) {
                display.innerHTML = selectedNames.map(name => 
                    `<span class="bg-blue-100 text-blue-800 text-xs px-2.5 py-1 rounded-full border border-blue-200">${name}</span>`
                ).join('');
                
                if (clearBtn) clearBtn.classList.remove('hidden');
            } else {
                display.innerHTML = `<span class="text-slate-400 text-sm">คลิกเพื่อเลือกชื่อ...</span>`;
                
                if (clearBtn) clearBtn.classList.add('hidden');
            }
            
            if (hiddenNameInput) {
                hiddenNameInput.value = selectedNames.join(', ');
            }

            if (hiddenUsersJsonInput) {
                hiddenUsersJsonInput.value = JSON.stringify(selectedUsers);
            }
        }

        async function loadEvalTopics() {
            try {
                const response = await axios.get(`handle_pm_feedback.php?action=get_eval_topics&ag_id=${AG_ID}`);
                
                if (response.data.success) {
                    const topics = response.data.data;
                    const container = document.getElementById('eval-topics-container');
                    let html = '';
                    
                    // +++ เก็บจำนวนหัวข้อทั้งหมดเพื่อใช้เป็นตัวหาร +++
                    totalEvalTopics = topics.length; 
                    
                    if (topics.length > 0) {
                        topics.forEach((topic, index) => {
                            // ... (โค้ดสร้าง HTML เหมือนในคำตอบข้อที่แล้ว) ...
                            let radiosHtml = '';
                            for(let i=1; i<=5; i++) {
                                radiosHtml += `
                                <div class="relative">
                                    <input type="radio" name="${topic.topic_title}" value="${i}" id="${topic.topic_title}_${i}" class="peer hidden eval-radio-input" required>
                                    <label for="${topic.topic_title}_${i}" class="w-8 h-8 flex items-center justify-center rounded-md border border-slate-200 text-sm font-medium text-slate-500 cursor-pointer transition-all hover:bg-slate-100 peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary shadow-sm">
                                        ${i}
                                    </label>
                                </div>
                                `;
                            }

                            html += `
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 bg-white rounded-lg border border-slate-100 shadow-sm hover:border-blue-200 transition-colors">
                                <label class="text-sm font-medium text-slate-700 w-full sm:w-1/2 flex items-start gap-2">
                                    <span class="text-slate-400 font-normal">${index + 1}.</span> ${topic.topic_title}
                                </label>
                                <div class="flex gap-1.5 w-full sm:w-auto justify-end">
                                    ${radiosHtml}
                                </div>
                            </div>
                            `;
                        });
                    } else {
                        html = `<div class="col-span-1 lg:col-span-2 p-4 text-center text-red-500">ไม่พบข้อมูลหัวข้อการประเมินในระบบ</div>`;
                    }
                    
                    container.innerHTML = html;
                    
                    // +++ เรียกฟังก์ชันผูก Event ให้ Radio ทุกตัว +++
                    attachCalculateScoreEvent(); 
                    
                }
            } catch (error) {
                // ... โค้ดจัดการ Error ...
            }
        }

        // ==========================================
        // ฟังก์ชันใหม่สำหรับการคำนวณคะแนน
        // ==========================================

        function attachCalculateScoreEvent() {
            // ดึง Radio ทั้งหมดที่มีคลาส eval-radio-input
            const radios = document.querySelectorAll('.eval-radio-input');
            
            // ผูก Event 'change' เพื่อให้คำนวณใหม่ทุกครั้งที่ผู้ใช้คลิกเปลี่ยนตัวเลือก
            radios.forEach(radio => {
                radio.addEventListener('change', calculateAverageScore);
            });
        }

        function calculateAverageScore() {
            // หา Radio ทั้งหมดที่ถูกคลิกเลือกแล้ว (checked)
            const selectedRadios = document.querySelectorAll('.eval-radio-input:checked');
            
            if (selectedRadios.length === 0 || totalEvalTopics === 0) {
                updateScoreUI(0);
                return;
            }

            // รวมคะแนนจากข้อที่เลือก
            let sum = 0;
            selectedRadios.forEach(radio => {
                sum += parseFloat(radio.value);
            });

            // คำนวณค่าเฉลี่ย (รวมคะแนน หารด้วย จำนวนหัวข้อทั้งหมด)
            let average = sum / totalEvalTopics; 
            
            updateScoreUI(average);
        }

        function updateScoreUI(score) {
            // แปลงให้เป็นทศนิยม 2 ตำแหน่ง
            const formattedScore = score.toFixed(2);
            
            // แสดงผลบนหน้าเว็บ
            document.getElementById('display_average_score').innerText = formattedScore;
            
            // ใส่ค่าลงใน Input ซ่อนเตรียมส่งไป Backend
            document.getElementById('eval_average_score').value = formattedScore;
        }

        // ฟังก์ชันคำนวณคะแนนเฉลี่ยอัตโนมัติ
        function initEvaluationCalculator() {
            const evalInputs = document.querySelectorAll('.eval-radio-input');
            const displayScore = document.getElementById('average-score-display');
            const hiddenScore = document.getElementById('eval_average_score');
            
            evalInputs.forEach(input => {
                input.addEventListener('change', () => {
                    let total = 0;
                    let count = 0;
                    
                    // หาเฉพาะ input ที่ถูก checked
                    document.querySelectorAll('.eval-radio-input:checked').forEach(checkedInput => {
                        total += parseInt(checkedInput.value);
                        count++;
                    });
                    
                    if(count > 0) {
                        const avg = (total / count).toFixed(2);
                        displayScore.textContent = avg;
                        hiddenScore.value = avg;
                    }
                });
            });
        }

        // ฟังก์ชันตั้งค่า Signature Pads ทั้ง 2 ตัว
        function initSignaturePads() {
            // 1. Inspector Pad
            const canvasInsp = document.getElementById('inspector-signature-pad');
            const placeholderInsp = document.getElementById('inspector-signature-placeholder');
            
            // 2. Evaluator Pad
            const canvasEval = document.getElementById('evaluator-signature-pad');
            const placeholderEval = document.getElementById('evaluator-signature-placeholder');
            
            function resizeCanvas(canvas) {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
            }

            function resizeAll() {
                resizeCanvas(canvasInsp);
                if (inspectorPad) inspectorPad.clear();
                
                resizeCanvas(canvasEval);
                if (evaluatorPad) evaluatorPad.clear();
            }

            window.addEventListener("resize", resizeAll);
            resizeCanvas(canvasInsp);
            resizeCanvas(canvasEval);

            // Init Inspector
            inspectorPad = new SignaturePad(canvasInsp, { penColor: "rgb(15, 23, 42)", backgroundColor: "rgba(255, 255, 255, 0)" });
            inspectorPad.addEventListener("beginStroke", () => placeholderInsp.style.display = 'none');
            document.getElementById('clear-inspector-signature').addEventListener('click', () => {
                inspectorPad.clear(); placeholderInsp.style.display = 'flex';
            });

            // Init Evaluator
            evaluatorPad = new SignaturePad(canvasEval, { penColor: "rgb(15, 23, 42)", backgroundColor: "rgba(255, 255, 255, 0)" });
            evaluatorPad.addEventListener("beginStroke", () => placeholderEval.style.display = 'none');
            document.getElementById('clear-evaluator-signature').addEventListener('click', () => {
                evaluatorPad.clear(); placeholderEval.style.display = 'flex';
            });
        } 

        // ===== ยกเลิกผลบันทึก แล้วส่งกลับให้ทำใหม่ (Admin / Super Admin) — handle_pm_work_cancel.php =====
        function wsThDateTime(s) {
            const d = new Date(String(s || '').replace(' ', 'T'));
            return isNaN(d) ? (s || '-') : d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: 'numeric' }) + ' ' + d.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) + ' น.';
        }
        function wsEsc(v) { return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]); }
        function wsRenderCancelInfo(data) {
            const logs = data.cancel_logs || [];
            const can = !!(isHistoryMode && data.can_cancel);
            const btn = document.getElementById('ws-cancel-rec');
            if (btn) { btn.classList.toggle('hidden', !can); btn.classList.toggle('inline-flex', can); }
            const note = document.getElementById('ws-cancel-note');
            if (!logs.length) { note.classList.add('hidden'); return; }
            const last = logs[0], rev = logs.length;
            // ประวัติ: เลขที่เอกสาร + ฉบับแก้ไข
            const docEl = document.getElementById('info-doc-no');
            if (isHistoryMode && docEl && docEl.textContent && docEl.textContent !== '-') docEl.textContent += ' (แก้ไขครั้งที่ ' + rev + ')';
            const who = l => wsEsc(l.created_by_name || l.created_by || '-');
            const hist = logs.map(l => '<li>ครั้งที่ ' + l.revision + ' · ' + wsEsc(wsThDateTime(l.created_at)) + ' · โดย ' + who(l) + ' — ' + wsEsc(l.reason) + '</li>').join('');
            note.innerHTML = isHistoryMode
                ? '<div class="font-semibold"><i class="fa-solid fa-rotate-left mr-1"></i> ใบงานฉบับแก้ไขครั้งที่ ' + rev + '</div>'
                  + '<div class="text-xs mt-1">ผลบันทึกเดิมถูกยกเลิกและบันทึกใหม่แล้ว (ใช้เลขที่เอกสารเดิม)</div>'
                  + '<ul class="text-xs mt-1.5 list-disc pl-5 space-y-0.5">' + hist + '</ul>'
                : '<div class="font-semibold"><i class="fa-solid fa-triangle-exclamation mr-1"></i> งานนี้ถูกยกเลิกผลบันทึกเดิม — กรุณาตรวจและบันทึกใหม่</div>'
                  + '<div class="text-xs mt-1">เหตุผล: <b>' + wsEsc(last.reason) + '</b> · โดย ' + who(last) + ' เมื่อ ' + wsEsc(wsThDateTime(last.created_at)) + '</div>'
                  + '<div class="text-xs mt-0.5">บันทึกใหม่จะใช้เลขที่เอกสารเดิม <b>' + wsEsc(last.doc_no || '-') + '</b> (แก้ไขครั้งที่ ' + rev + ')</div>';
            note.classList.remove('hidden');
        }
        async function wsCancelRecord() {
            let info;
            try { info = (await axios.get('handle_pm_work_cancel.php?action=info&event_id=' + encodeURIComponent(planId))).data; }
            catch (e) { info = { success: false, error: 'เชื่อมต่อไม่สำเร็จ' }; }
            if (!info.success) { Swal.fire('ยกเลิกไม่ได้', info.error, 'error'); return; }
            const spares = (info.spares || []).map(x => '<li>' + wsEsc(x.name || 'อะไหล่') + ' × ' + x.qty + '</li>').join('');
            const r = await Swal.fire({
                title: 'ยกเลิกผลบันทึก PM?',
                html: '<div class="text-left text-sm space-y-2">'
                    + '<div>เลขที่เอกสาร <b>' + wsEsc(info.doc_no) + '</b> · ทำเมื่อ ' + wsEsc(formatThaiDate(info.actual_date)) + '</div>'
                    + '<ul class="list-disc pl-5 space-y-1 text-slate-600">'
                    + '<li>ลบผลตรวจ รูป และลายเซ็นของใบงานนี้ แล้ว<b>ตั้งงานกลับเป็น "ยังไม่ทำ"</b> ในวันเดิม ให้ช่างตรวจและบันทึกใหม่</li>'
                    + '<li>บันทึกใหม่จะใช้<b>เลขที่เอกสารเดิม</b> และระบุเป็นฉบับแก้ไขครั้งที่ ' + ((info.revision || 0) + 1) + '</li>'
                    + (spares ? '<li>คืนสต็อกอะไหล่ที่ใบงานนี้ตัดไป:<ul class="list-[circle] pl-5">' + spares + '</ul></li>' : '')
                    + (info.evaluated ? '<li class="text-red-600"><b>ใบงานนี้ประเมินแล้ว — ผลประเมินจะถูกลบด้วย</b> ต้องประเมินใหม่หลังบันทึกใหม่</li>' : '')
                    + '</ul><label class="block font-medium text-slate-700 pt-1">เหตุผลที่ยกเลิก <span class="text-red-500">*</span></label></div>',
                input: 'textarea', inputPlaceholder: 'เช่น เลือกผลปกติผิด หน้างานจริงพบไฟแสดงสถานะไม่ติด',
                inputAttributes: { maxlength: 500 },
                icon: 'warning', showCancelButton: true, confirmButtonText: 'ยกเลิกผลบันทึก', cancelButtonText: 'ปิด',
                confirmButtonColor: '#dc2626', focusCancel: true, width: '34rem',
                preConfirm: v => { if ((v || '').trim().length < 5) { Swal.showValidationMessage('กรุณาระบุเหตุผลอย่างน้อย 5 ตัวอักษร'); return false; } return v.trim(); }
            });
            if (!r.isConfirmed) return;
            Swal.fire({ title: 'กำลังยกเลิก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            const fd = new FormData(); fd.append('event_id', planId); fd.append('reason', r.value);
            let d;
            try { d = (await axios.post('handle_pm_work_cancel.php?action=cancel', fd)).data; } catch (e) { d = { success: false, error: 'เชื่อมต่อไม่สำเร็จ' }; }
            if (!d.success) { Swal.fire('ยกเลิกไม่สำเร็จ', d.error, 'error'); return; }
            try { const host = WS_EMBED ? window.parent : window.opener; host && host.refreshCalendarEvents && host.refreshCalendarEvents(); } catch (e) {}
            const go = await Swal.fire({ icon: 'success', title: 'ยกเลิกผลบันทึกแล้ว',
                html: 'งานกลับเป็น "ยังไม่ทำ" แล้ว' + (d.spares_returned ? ' · คืนสต็อกอะไหล่ ' + d.spares_returned + ' รายการ' : '')
                    + '<br>บันทึกใหม่จะใช้เลขที่เอกสาร <b>' + wsEsc(d.doc_no) + '</b> (แก้ไขครั้งที่ ' + d.revision + ')',
                showCancelButton: true, confirmButtonText: 'เปิดใบงานเพื่อบันทึกใหม่', cancelButtonText: 'ปิด', confirmButtonColor: '#006b9f' });
            if (go.isConfirmed) { const u = new URL(window.location.href); u.searchParams.delete('mode'); window.location.replace(u.toString()); }
            else wsClose(true);
        }

        async function fetchData() {
			try {
				const apiAction = isHistoryMode ? 'get_history' : 'get_data';
		
				const response = await axios.get(
					`handle_pm_worksheet.php?action=${apiAction}&plan_id=${encodeURIComponent(planId)}&ag_id=${encodeURIComponent(AG_ID)}`
				);
		
				const data = response.data;
		
				if (data.success) {
					if (isHistoryMode) {
						renderHistoryMode(data);
						wsRenderCancelInfo(data);
					} else {
						renderInfo(data.info);
						wsRenderCancelInfo(data);
						applyRecordSetting(data.record_setting);
						renderSpares(data.spares);
		
						worksheetItems = Array.isArray(data.items) ? data.items : [];
						renderItems(worksheetItems);
						applyBatchPrefill();

						renderFiles(data.files || []);
					}
		
					const loadingIndicator = document.getElementById('loading');
					loadingIndicator.classList.add('opacity-0', 'pointer-events-none');
					setTimeout(() => { loadingIndicator.style.display = 'none'; }, 300);
		
					document.getElementById('app-container').style.display = 'block';
					wsInitSteps();
					wsLoadJobDetail();
					window.dispatchEvent(new Event('resize'));
		
				} else {
					Swal.fire({
						icon: 'error',
						title: 'ผิดพลาด',
						text: data.error || 'ไม่สามารถโหลดข้อมูลได้'
					});
				}
		
			} catch (error) {
				console.error(error);
				Swal.fire({
					icon: 'error',
					title: 'ผิดพลาด',
					text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'
				});
			}
		}
		
		function renderFiles(files) {
			const section = document.getElementById('checksheet-files-section');
			const imagesBlock = document.getElementById('checksheet-images-block');
			const docsBlock = document.getElementById('checksheet-docs-block');
			const imagesList = document.getElementById('checksheet-images-list');
			const docsList = document.getElementById('checksheet-docs-list');
		
			if (!section || !imagesBlock || !docsBlock || !imagesList || !docsList) return;
		
			imagesList.innerHTML = '';
			docsList.innerHTML = '';
		
			if (!files || files.length === 0) {
				section.classList.add('hidden');
				imagesBlock.classList.add('hidden');
				docsBlock.classList.add('hidden');
				return;
			}
		
			const imageFiles = files.filter(f => String(f.file_type).toLowerCase() === 'image');
			const docFiles = files.filter(f => String(f.file_type).toLowerCase() !== 'image');
		
			if (imageFiles.length > 0) {
				imagesBlock.classList.remove('hidden');
		
				const imageUrls = imageFiles.map(file => file.file_path);
				const imageUrlsJson = JSON.stringify(imageUrls).replace(/"/g, '&quot;');
		
				imagesList.innerHTML = imageFiles.map((file, index) => `
					<div class="group block w-14 cursor-pointer" title="${file.file_name}">
						<div class="w-14 h-14 rounded-lg overflow-hidden border border-slate-200 bg-slate-50 group-hover:border-primary transition"
							 onclick='ImageCarousel.open(${imageUrlsJson}, ${index})'>
							<img src="${file.file_path}" alt="${file.file_name}"
								 class="w-full h-full object-cover">
						</div>
					</div>
				`).join('');
			} else {
				imagesBlock.classList.add('hidden');
			}
		
			if (docFiles.length > 0) {
				docsBlock.classList.remove('hidden');
		
				docsList.innerHTML = docFiles.map(file => `
					<a href="${file.file_path}" target="_blank"
					   class="inline-flex items-center gap-1.5 max-w-full px-2.5 py-1 rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 hover:border-primary transition" title="${file.file_name}">
						<i class="fa-regular fa-file-lines text-emerald-600 text-xs"></i>
						<span class="truncate max-w-[118px] sm:max-w-[200px] text-xs font-medium">${file.file_name}</span>
						<i class="fa-solid fa-arrow-up-right-from-square text-slate-400 text-[10px]"></i>
					</a>
				`).join('');
			} else {
				docsBlock.classList.add('hidden');
			}
		
			section.classList.remove('hidden');
		}

        // --- ส่วนของ Render ข้อมูลเหมือนเดิม 100% ---
        function renderInfo(info) {
			document.getElementById('info-doc-no').textContent = info.doc_no || '-';
			document.getElementById('info-rev-no').textContent = info.rev_no || '-';
			document.getElementById('info-checksheet-name').textContent = info.checksheet_name || '-';
		
			const machineDisplay = `${info.machine_code ? '[' + info.machine_code + '] ' : ''}${info.machine_name || '-'}`;
			document.getElementById('info-machine-name').textContent = machineDisplay;
		
			document.getElementById('info-machine-sn').textContent = info.machine_sn || '-';
			document.getElementById('info-machine-type').textContent = info.machine_type || '-';
			document.getElementById('info-location').textContent = info.location || '-';
		
			document.getElementById('info-plan-date').querySelector('span').textContent = formatThaiDate(info.plan_date);
		
			const actualDateEl = document.getElementById('info-actual-date')?.querySelector('span');
			if (actualDateEl) {
				actualDateEl.textContent = formatThaiDate(actualDateEl.textContent);
			}
		}

        function renderSpares(spares) {
            if (!spares || spares.length === 0) return;
            const container = document.getElementById('spares-container');
            const tbody = document.getElementById('spares-list');
            container.classList.remove('hidden');
            tbody.innerHTML = spares.map(sp => wsSpareChip(`${sp.part_name || '-'}${sp.part_no ? ' (' + sp.part_no + ')' : ''}`, sp.quantity || sp.qty)).join('');
        }
        // อะไหล่ที่ต้องเตรียม: ป้ายเล็ก "ชื่อ ×จำนวน" ในการ์ดเครื่อง
        function wsSpareChip(name, qty) {
            const q = parseFloat(qty);
            return `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-50 border border-amber-200 text-xs text-slate-700">${wsAttr(name)}${isNaN(q) ? '' : ` <b class="text-amber-700">×${q}</b>`}</span>`;
        }

        // ---------- ขั้นตอนของใบงาน (stepper) ----------
        // หน้าแบ่งเป็น 4 ขั้น (section.ws-step[data-ws-step]) แสดงทีละขั้น ไปขั้นไหนก็ได้ ตรวจความครบตอนกดบันทึก
        let wsStep = 1;
        const WS_STEP_COUNT = 4;

        // หน้าเป็นแบบเลื่อนหน้าเดียว (เหมือนหน้าบันทึกพร้อมกัน): ข้อมูลงาน → จุดตรวจสอบ → อะไหล่ → [ลงชื่อ & บันทึก] เปิดแผ่นด้านล่าง
        //  wsGoStep/wsShowStepOf ยังใช้ได้เหมือนเดิม (validation พาไปยังช่องที่ขาด): ขั้น 4 = เปิดแผ่นลงชื่อ, ขั้นอื่น = ปิดแผ่นแล้วเลื่อนไปที่ส่วนนั้น
        function wsInitSteps() {
            const txt = id => (document.getElementById(id)?.textContent || '').trim() || '-';
            const machine = txt('info-machine-name');
            document.getElementById('ws-job-machine').textContent = machine;
            document.getElementById('ws-job-sub').textContent = [txt('info-machine-sn') !== '-' ? 'S/N ' + txt('info-machine-sn') : '', txt('info-location') !== '-' ? txt('info-location') : ''].filter(Boolean).join(' · ');
            document.getElementById('ws-job-doc').textContent = txt('info-doc-no');
            document.getElementById('ws-job-date').textContent = txt('info-plan-date');
            // หัวหน้า: ชื่อเช็คชีต + เครื่อง · วันที่
            const title = document.querySelector('nav h1 span');
            if (title && !isHistoryMode && txt('info-checksheet-name') !== '-') title.textContent = txt('info-checksheet-name');
            document.getElementById('ws-nav-sub').textContent = [machine, txt('info-plan-date')].filter(v => v && v !== '-').join(' · ');
            document.body.classList.toggle('ws-edit', !isHistoryMode);
            document.getElementById('ws-open-sign')?.classList.toggle('hidden', isHistoryMode);
            window.addEventListener('resize', wsLayoutSticky);
            wsGoStep(1, false);
            wsRefreshSummary();
        }

        function wsGoStep(n, scroll = true) {
            n = Math.min(Math.max(n, 1), WS_STEP_COUNT);
            wsStep = n;
            wsCloseDetail();
            if (n === 4 && !isHistoryMode) { wsOpenSign(); return; }
            wsCloseSign();
            wsLayoutSticky();
            if (scroll) document.querySelector(`.ws-step[data-ws-step="${n}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // พาไปยังส่วนที่มี element นี้ (ใช้ตอนพาไปยังช่องที่กรอกไม่ครบ)
        function wsShowStepOf(el) {
            const panel = el?.closest?.('.ws-step');
            if (!panel) return;
            if (+panel.dataset.wsStep === 4 && !isHistoryMode) wsOpenSign(); else wsCloseSign();
        }

        // แผ่นลงชื่อและบันทึก (โหมดบันทึก)
        function wsOpenSign() {
            if (isHistoryMode) return;
            wsRenderSummary();
            document.getElementById('ws-signsheet')?.classList.add('open');
            document.getElementById('ws-scrim')?.classList.add('open');
            document.body.classList.add('ws-sheet-open');
            setTimeout(wsFixSignatureCanvas, 260);   // canvas ขนาดถูกต้องหลังแผ่นเลื่อนขึ้นเสร็จ
        }
        function wsCloseSign() {
            document.getElementById('ws-signsheet')?.classList.remove('open');
            document.getElementById('ws-scrim')?.classList.remove('open');
            document.body.classList.remove('ws-sheet-open');
        }
        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape' || Swal.isVisible()) return;
            if (document.getElementById('ws-signsheet')?.classList.contains('open')) wsCloseSign();
            else if (document.getElementById('ws-detail')?.classList.contains('is-open')) wsCloseDetail();
            else if (WS_EMBED) wsClose();   // dialog ในหน้าวางแผน PM
        });

        // canvas ลายเซ็นถูกสร้างตอนขั้นที่ 4 ยังซ่อนอยู่ (กว้าง 0) จึงต้องปรับขนาดเมื่อแสดงครั้งแรก
        function wsFixSignatureCanvas() {
            const c = document.getElementById('inspector-signature-pad');
            if (!c || !c.offsetWidth) return;
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            if (c.width !== Math.round(c.offsetWidth * ratio)) window.dispatchEvent(new Event('resize'));
        }

        // ตำแหน่งของส่วนที่ติดด้านบน: nav > แถบขั้นตอน > แถบเครื่องมือของตาราง > แผงรายละเอียด
        function wsLayoutSticky() {
            const navH = document.querySelector('nav.sticky')?.offsetHeight || 0;
            const stepper = document.getElementById('ws-stepper');
            if (!stepper) return;
            stepper.style.top = navH + 'px';
            const top2 = navH + stepper.offsetHeight;
            const toolbar = document.getElementById('ws-toolbar');
            if (toolbar) toolbar.style.top = top2 + 'px';
            document.getElementById('ws-detail')?.style.setProperty('--ws-detail-top', (top2 + (toolbar?.offsetHeight || 0) + 12) + 'px');
            document.documentElement.style.scrollPaddingTop = (top2 + (toolbar?.offsetHeight || 0) + 16) + 'px';
        }

        // ขั้นที่ 4: สรุปผลการตรวจ (กดรายการเพื่อกลับไปแก้ข้อนั้น)
        function wsRenderSummary() {
            const box = document.getElementById('ws-summary');
            if (!box) return;
            const rows = [...document.querySelectorAll('#items-list tr.ws-item')];
            const pending = rows.filter(r => !r.dataset.state);
            const fails = rows.filter(r => r.dataset.state === 'Fail');
            const count = s => rows.filter(r => r.dataset.state === s).length;
            const stat = (label, val, cls) => `
                <div class="rounded-xl border p-3 ${cls}">
                    <div class="text-xs font-medium opacity-80">${label}</div>
                    <div class="text-2xl font-bold leading-tight mt-0.5">${val}</div>
                </div>`;
            const list = rs => rs.map(r => {
                const d = wsDetail[r.dataset.itemId] || {};
                return `<button type="button" onclick="wsJumpToItem('${r.dataset.itemId}')"
                            class="w-full text-left flex items-start gap-2 px-2.5 py-1.5 rounded-lg hover:bg-white/80 transition">
                            <span class="font-semibold">${d.no || ''}.</span><span class="flex-1">${d.title || '-'}</span>
                            <i class="fa-solid fa-chevron-right text-[10px] mt-1.5 opacity-60"></i>
                        </button>`;
            }).join('');

            box.innerHTML = `
                <div class="bg-slate-50 border-b border-slate-200 px-5 py-4 flex items-center gap-2.5 rounded-t-xl">
                    <i class="fa-solid fa-clipboard-list text-primary text-lg"></i>
                    <h2 class="text-base sm:text-lg font-semibold text-slate-800">สรุปผลการตรวจ</h2>
                </div>
                <div class="p-4 sm:p-5 space-y-4">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        ${stat('ตรวจแล้ว', `${rows.length - pending.length}<span class="text-base font-medium opacity-70"> / ${rows.length}</span>`, 'bg-white border-slate-200 text-slate-700')}
                        ${stat('ปกติ', count('Pass'), 'bg-green-50 border-green-200 text-green-700')}
                        ${stat('ผิดปกติ', count('Fail'), 'bg-red-50 border-red-200 text-red-700')}
                        ${stat('N/A', count('N/A'), 'bg-slate-50 border-slate-200 text-slate-600')}
                    </div>
                    ${pending.length ? `
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-amber-900">
                            <div class="text-sm font-semibold mb-1"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i>ยังไม่ได้ตรวจ ${pending.length} ข้อ</div>
                            <div class="text-sm">${list(pending)}</div>
                        </div>` : ''}
                    ${fails.length ? `
                        <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-red-800">
                            <div class="text-sm font-semibold mb-1"><i class="fa-solid fa-circle-xmark mr-1.5"></i>รายการที่ผิดปกติ ${fails.length} ข้อ</div>
                            <div class="text-sm">${list(fails)}</div>
                        </div>` : ''}
                    ${rows.length && !pending.length && !fails.length ? `
                        <div class="rounded-xl border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-800">
                            <i class="fa-solid fa-circle-check mr-1.5"></i>ตรวจครบทุกข้อ ไม่พบความผิดปกติ
                        </div>` : ''}
                </div>`;
        }

        function wsJumpToItem(itemId) {
            wsGoStep(2, false);
            wsShowRow(itemId);
            wsSelect(itemId, { scroll: true });
        }

        // ---------- รายการจุดตรวจสอบ : ตาราง + แผงรายละเอียด ----------
        // ตารางมีเฉพาะคอลัมน์ที่ต้องกรอก ส่วนมาตรฐาน/วิธีตรวจ/ข้อปฏิบัติ แสดงในแผงรายละเอียดของข้อที่เลือก
        // หมายเหตุ: name/id ที่ validation และการบันทึกใช้ (status_, val_, item_id[], photo_*, req_star_, data-item-id,
        // .ws-status-box รอบตัวเลือกผล) ต้องคงไว้เหมือนเดิม
        const WS_STATE = {
            '':    { text: 'ยังไม่ตรวจ', cls: 'bg-slate-100 text-slate-500 border-slate-200', icon: 'fa-regular fa-circle' },
            'Pass':{ text: 'ปกติ',       cls: 'bg-green-100 text-green-700 border-green-200', icon: 'fa-solid fa-circle-check' },
            'Fail':{ text: 'ผิดปกติ',    cls: 'bg-red-100 text-red-700 border-red-200',       icon: 'fa-solid fa-circle-xmark' },
            'N/A': { text: 'N/A',        cls: 'bg-slate-200 text-slate-700 border-slate-300', icon: 'fa-solid fa-ban' }
        };
        const WS_CHIP_CLS = 'ws-chip flex-shrink-0 items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium border';
        let wsFilter = 'all';
        let wsSearch = '';
        let wsDetail = {};      // itemId -> ข้อมูลที่แสดงในแผงรายละเอียด
        let wsSelected = null;

        const wsAttr = s => String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');

        // โครงตาราง + แผงรายละเอียด (ใช้ร่วมกันทั้งโหมดบันทึกและโหมดประวัติ)
        function wsSheetHtml(bodyHtml, footHtml = '') {
            return `
                <div class="ws-body">
                    <div class="ws-sheet-wrap min-w-0">
                        <table class="ws-sheet">
                            <thead>
                                <tr>
                                    <th class="ws-col-no">#</th>
                                    <th class="ws-col-point">จุดตรวจสอบ</th>
                                    <th class="ws-col-status">ผลการตรวจ</th>
                                    <th class="ws-col-value">ค่าที่วัดได้จริง</th>
                                    <th class="ws-col-photo">รูปภาพหลักฐาน</th>
                                </tr>
                            </thead>
                            <tbody>${bodyHtml}</tbody>
                        </table>
                        ${footHtml}
                    </div>
                    <aside id="ws-detail" class="ws-detail" aria-label="รายละเอียดจุดตรวจสอบ">
                        <div class="ws-detail-head sticky top-0 bg-white z-10 flex items-center justify-between px-4 pt-3 pb-2 border-b border-slate-100">
                            <span class="text-sm font-semibold text-slate-700">รายละเอียดจุดตรวจสอบ</span>
                            <button type="button" onclick="wsCloseDetail()" class="w-8 h-8 rounded-full hover:bg-slate-100 text-slate-500" title="ปิด"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div id="ws-detail-body"></div>
                    </aside>
                </div>
                <div id="ws-detail-backdrop" class="ws-backdrop" onclick="wsCloseDetail()"></div>`;
        }

        // คอลัมน์ลำดับ + จุดตรวจสอบ (บนมือถือคือหัวการ์ด)
        function wsPointCells(index, item, badges, chipHtml) {
            return `
                <td class="ws-col-no">${index + 1}</td>
                <td class="ws-col-point">
                    <div class="flex items-center gap-2">
                        <span class="ws-mobile-no">${index + 1}</span>
                        <div class="flex-1 min-w-0 flex flex-wrap items-center gap-x-1.5 gap-y-0.5">
                            <span class="text-sm font-semibold text-slate-800 leading-snug break-words">${item.check_point || '-'}</span>${badges}
                        </div>
                        ${chipHtml}
                        <button type="button" class="ws-more-btn" title="ดูมาตรฐาน / วิธีตรวจ"><i class="fa-solid fa-book-open"></i></button>
                    </div>
                </td>`;
        }

        const wsIllustration = url => url
            ? `<a href="${url}" target="_blank" class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 text-primary hover:bg-blue-100 rounded-md text-xs font-medium transition-colors border border-blue-100"><i class="fa-regular fa-image"></i> ดูรูปประกอบมาตรฐาน</a>`
            : '';

        // เก็บข้อมูลของข้อไว้แสดงในแผงรายละเอียด
        function wsSetDetail(itemId, no, item, badges, illustrationUrl, action) {
            wsDetail[itemId] = {
                no, badges,
                title: item.check_point || '-',
                standard: item.standard_text,
                illustration: wsIllustration(illustrationUrl),
                expected: hasMeasurementConfig(item)
                    ? `${item.measurement_name ? item.measurement_name + ' : ' : ''}<b class="text-primary">${item.expected_value}</b> ${item.unit || ''}` : '',
                method: item.method_text,
                action
            };
        }

        function wsRenderDetail(itemId) {
            const d = wsDetail[itemId];
            const body = document.getElementById('ws-detail-body');
            if (!d || !body) return;
            const total = Object.keys(wsDetail).length;
            const section = (icon, label, html, cls = 'bg-white border-slate-200', labelCls = 'text-slate-500') => `
                <div class="rounded-xl border p-3.5 ${cls}">
                    <div class="text-[11px] font-bold uppercase tracking-wider mb-1 ${labelCls}"><i class="${icon} mr-1"></i>${label}</div>
                    <div class="leading-relaxed break-words">${html}</div>
                </div>`;
            body.innerHTML = `
                <div class="px-4 pt-4 pb-3">
                    <div class="text-xs text-slate-500 mb-1">ข้อ ${d.no} จาก ${total}</div>
                    <div class="font-semibold text-slate-800 leading-snug">${d.title}</div>
                    ${d.badges ? `<div class="flex flex-wrap gap-1 mt-2">${d.badges}</div>` : ''}
                </div>
                <div class="px-4 pb-4 space-y-3 text-sm text-slate-700">
                    ${section('fa-solid fa-book-open', 'มาตรฐานการตรวจสอบ', (d.standard || '-') + d.illustration)}
                    ${d.expected ? section('fa-solid fa-ruler', 'ค่ามาตรฐาน', d.expected, 'bg-blue-50/60 border-blue-100', 'text-primary') : ''}
                    ${section('fa-solid fa-wrench', 'วิธีตรวจสอบ / เครื่องมือ', d.method || '-')}
                    ${section('fa-solid fa-triangle-exclamation', 'เมื่อพบความผิดปกติ', d.action || '-', 'bg-red-50 border-red-100 text-red-700', 'text-red-600')}
                </div>
                <div class="sticky bottom-0 bg-white border-t border-slate-100 p-3 flex gap-2">
                    <button type="button" onclick="wsSelectStep(-1)" ${d.no <= 1 ? 'disabled' : ''}
                            class="flex-1 px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-700 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed"><i class="fa-solid fa-chevron-left mr-1"></i>ข้อก่อนหน้า</button>
                    <button type="button" onclick="wsSelectStep(1)" ${d.no >= total ? 'disabled' : ''}
                            class="flex-1 px-3 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:bg-primary/90 disabled:opacity-40 disabled:cursor-not-allowed">ข้อถัดไป<i class="fa-solid fa-chevron-right ml-1"></i></button>
                </div>`;
        }

        function wsSelect(itemId, { open = false, scroll = false } = {}) {
            wsSelected = String(itemId);
            document.querySelectorAll('#items-list tr.ws-item').forEach(r => r.classList.toggle('is-selected', r.dataset.itemId === wsSelected));
            wsRenderDetail(wsSelected);
            if (open) wsOpenDetail();
            if (scroll) document.querySelector(`#items-list tr.ws-item[data-item-id="${wsSelected}"]`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // ข้อก่อนหน้า/ถัดไป (ข้ามแถวที่ถูกกรองออก)
        function wsSelectStep(delta) {
            const rows = [...document.querySelectorAll('#items-list tr.ws-item:not(.ws-hide)')];
            const next = rows[rows.findIndex(r => r.dataset.itemId === wsSelected) + delta];
            if (next) wsSelect(next.dataset.itemId, { scroll: true });
        }

        function wsOpenDetail() {
            document.getElementById('ws-detail')?.classList.add('is-open');
            document.getElementById('ws-detail-backdrop')?.classList.add('is-open');
        }

        function wsCloseDetail() {
            document.getElementById('ws-detail')?.classList.remove('is-open');
            document.getElementById('ws-detail-backdrop')?.classList.remove('is-open');
        }

        // คลิก/โฟกัสแถว = เลือกข้อนั้น, ปุ่ม "ดูมาตรฐาน" (จอเล็ก) = เปิดแผงรายละเอียด
        function wsBindSheet(container) {
            if (container.dataset.wsBound) return;
            container.dataset.wsBound = '1';
            container.addEventListener('click', e => {
                const row = e.target.closest('tr.ws-item');
                if (!row) return;
                wsSelect(row.dataset.itemId, { open: !!e.target.closest('.ws-more-btn') });
            });
            container.addEventListener('focusin', e => {
                const row = e.target.closest('tr.ws-item');
                if (row && row.dataset.itemId !== wsSelected) wsSelect(row.dataset.itemId);
            });
            container.addEventListener('keydown', wsSheetKeys);
        }

        function renderItems(items) {
            const container = document.getElementById('items-list');
            wsDetail = {};

            // ปุ่มเลือกผล: radio ซ่อน (peer) + ปุ่มแบบ segmented เปลี่ยนสีชัดเจนเมื่อเลือก
            const choice = (item, photoReqId, value, label, icon, onCls) => `
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="status_${item.id}" value="${value}" required
                           onchange="handleStatusChange(${item.id}, '${photoReqId}')" class="peer sr-only">
                    <span class="ws-choice radio-label flex items-center justify-center gap-1.5 px-2 rounded-lg border-2 text-xs sm:text-sm font-medium transition whitespace-nowrap
                                 bg-white text-slate-600 border-slate-200 hover:border-slate-300 peer-focus-visible:ring-2 peer-focus-visible:ring-primary ${onCls}">
                        <i class="${icon}"></i> ${label}
                    </span>
                </label>`;

            let rows = '';
            items.forEach((item, index) => {
                // เงื่อนไข Photo Required (1 = บังคับ, 2 = ไม่บังคับ, 3 = บังคับเฉพาะเมื่อไม่ผ่าน)
                const photoReqId = String(item.photo_required_id);
                const isPhotoRequiredAlways = (photoReqId === '1');

                let requirePhotoBadge = '';
                if (photoReqId === '1') {
                    requirePhotoBadge = '<span class="text-[10px] bg-red-50 text-red-600 px-2 py-0.5 rounded-full font-semibold border border-red-200 whitespace-nowrap"><i class="fa-solid fa-camera mr-1"></i>บังคับถ่ายรูป</span>';
                } else if (photoReqId === '3') {
                    requirePhotoBadge = '<span class="text-[10px] bg-amber-50 text-amber-600 px-2 py-0.5 rounded-full font-semibold border border-amber-200 whitespace-nowrap"><i class="fa-solid fa-camera mr-1"></i>ถ่ายรูปเมื่อผิดปกติ</span>';
                }
                const needMeasure = hasMeasurementConfig(item);
                // ค่าที่ต้องวัด + มาตรฐาน อยู่ในหัวการ์ด (แถวกรอกค่าจะได้สูงเท่าปุ่มผลการตรวจ)
                const measureBadge = needMeasure
                    ? `<span class="text-[10px] bg-blue-50 text-primary px-2 py-0.5 rounded-full font-semibold border border-blue-200 whitespace-nowrap"><i class="fa-solid fa-ruler mr-1"></i>${wsAttr(item.measurement_name || 'ต้องวัดค่า')} · มาตรฐาน ${wsAttr(item.expected_value)} ${wsAttr(item.unit || '')}</span>` : '';
                const badges = requirePhotoBadge + measureBadge;
                wsSetDetail(item.id, index + 1, item, badges, item.reference_image,
                    item.action_text || item.action_if_abnormal || item.action_abnormal);

                const isNA = String(item.check_type_id) === '2';
                const statusCell = `
                    <td class="ws-col-status" data-label="ผลการตรวจ *">
                        <div class="ws-status-box rounded-lg">
                            <input type="hidden" name="item_id[]" value="${item.id}">
                            <div class="flex gap-1.5">
                                ${choice(item, photoReqId, 'Pass', 'ปกติ', 'fa-solid fa-check', 'peer-checked:bg-green-600 peer-checked:border-green-600 peer-checked:text-white')}
                                ${choice(item, photoReqId, 'Fail', 'ผิดปกติ', 'fa-solid fa-xmark', 'peer-checked:bg-red-600 peer-checked:border-red-600 peer-checked:text-white')}
                                ${isNA ? choice(item, photoReqId, 'N/A', 'N/A', 'fa-solid fa-ban', 'peer-checked:bg-slate-600 peer-checked:border-slate-600 peer-checked:text-white') : ''}
                            </div>
                        </div>
                    </td>`;

                const valueCell = needMeasure ? `
                    <td class="ws-col-value" data-label="${wsAttr(item.measurement_name || 'ค่าที่วัดได้จริง')}">
                        <div class="flex items-stretch gap-1.5">
                            <input type="text" inputmode="decimal" name="val_${item.id}" oninput="wsUpdateItem(${item.id})"
                                   class="ws-value-input w-full min-w-0 px-2.5 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none font-semibold text-slate-800"
                                   placeholder="กรอกค่า">
                            ${item.unit ? `<span class="flex items-center text-xs text-slate-600 font-semibold bg-slate-50 px-2 rounded-lg border border-slate-200 whitespace-nowrap">${item.unit}</span>` : ''}
                        </div>
                        <div class="text-xs text-slate-500 mt-1">มาตรฐาน <b class="text-primary">${item.expected_value}</b> ${item.unit || ''}</div>
                    </td>` : `
                    <td class="ws-col-value ws-empty" data-label="ค่าที่วัดได้จริง">
                        <span class="text-slate-300">–</span>
                        <input type="hidden" name="val_${item.id}" value="-">
                    </td>`;

                const reqStar = isPhotoRequiredAlways
                    ? `<span class="text-red-500" id="req_star_${item.id}">*</span>`
                    : `<span class="text-xs font-normal text-slate-500 ml-1" id="req_star_${item.id}">(ถ้ามี)</span>`;
                const photoCell = `
                    <td class="ws-col-photo" data-label="รูปภาพหลักฐาน">
                        <label class="custom-file-upload ws-photo group relative overflow-hidden items-center justify-center" id="photo_container_${item.id}">
                            <input type="file" id="photo_input_${item.id}" accept="image/*" class="hidden" onchange="previewImage(this, ${item.id})" ${isPhotoRequiredAlways ? 'required' : ''}>
                            <div id="upload_ui_${item.id}" class="flex flex-col items-center justify-center w-full">
                                <i class="fa-solid fa-camera text-slate-300 text-lg"></i>
                                <span class="text-[11px] text-slate-500 font-medium leading-tight mt-0.5"><span id="photo_label_${item.id}">เพิ่มรูป</span> ${reqStar}</span>
                            </div>
                            <div id="preview_ui_${item.id}" class="hidden w-full relative">
                                <img id="img_preview_${item.id}" src="" class="w-full h-40 md:h-14 object-cover rounded" />
                                <span class="absolute bottom-1 right-1 text-white text-[10px] bg-black/60 px-1.5 py-0.5 rounded" title="เปลี่ยนรูป"><i class="fa-solid fa-pen"></i></span>
                            </div>
                        </label>
                        <input type="hidden" id="photo_base64_${item.id}" name="photo_${item.id}">
                    </td>`;

                const chip = `<span id="ws-state-${item.id}" class="${WS_CHIP_CLS} ${WS_STATE[''].cls}"><i class="${WS_STATE[''].icon}"></i>${WS_STATE[''].text}</span>`;

                rows += `
                    <tr class="ws-item" data-item-id="${item.id}" data-state="" data-search="${wsAttr(String(item.check_point || '').toLowerCase())}">
                        ${wsPointCells(index, item, badges, chip)}
                        ${statusCell}
                        ${valueCell}
                        ${photoCell}
                    </tr>`;
            });

            const toolbar = `
                <div id="ws-toolbar" class="sticky top-0 z-20 px-4 sm:px-5 py-3 bg-white/95 backdrop-blur border-b border-slate-200">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2.5">
                        <div class="text-sm text-slate-700 whitespace-nowrap">ตรวจแล้ว <b id="ws-done">0</b> / ${items.length} ข้อ
                            <span id="ws-fail-wrap" class="hidden ml-1 text-red-600">· ผิดปกติ <b id="ws-fail">0</b></span></div>
                        <div class="flex-1 min-w-[120px] h-2 bg-slate-100 rounded-full overflow-hidden">
                            <div id="ws-progress" class="h-full bg-primary rounded-full transition-all duration-300" style="width:0%"></div>
                        </div>
                        <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full lg:w-auto">
                            <div class="relative flex-1 sm:flex-none sm:w-56 min-w-[160px]">
                                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                                <input type="search" id="ws-search" placeholder="ค้นหาจุดตรวจสอบ"
                                       class="w-full pl-8 pr-3 py-1.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none">
                            </div>
                            <div class="flex gap-1.5 overflow-x-auto">
                                <button type="button" data-ws-filter="all"     class="ws-filter-btn px-3 py-1 rounded-full text-xs border whitespace-nowrap">ทั้งหมด</button>
                                <button type="button" data-ws-filter="pending" class="ws-filter-btn px-3 py-1 rounded-full text-xs border whitespace-nowrap">ยังไม่ตรวจ</button>
                                <button type="button" data-ws-filter="Fail"    class="ws-filter-btn px-3 py-1 rounded-full text-xs border whitespace-nowrap">ผิดปกติ</button>
                            </div>
                        </div>
                    </div>
                </div>`;

            container.innerHTML = toolbar + wsSheetHtml(rows,
                `<div id="ws-empty" class="hidden p-8 text-center text-sm text-slate-400">ไม่พบรายการที่ตรงกับเงื่อนไข</div>`);

            container.querySelectorAll('.ws-filter-btn').forEach(btn => btn.addEventListener('click', () => {
                wsFilter = btn.dataset.wsFilter;
                wsApplyFilter();
            }));
            document.getElementById('ws-search').addEventListener('input', e => {
                wsSearch = e.target.value.trim().toLowerCase();
                wsApplyFilter();
            });
            wsBindSheet(container);
            wsRefreshSummary();
            if (items.length) wsSelect(items[0].id);
            wsLayoutSticky();
        }

        // ช่องกรอกค่า: Enter / ↓ ไปแถวถัดไป, ↑ ไปแถวก่อนหน้า (แบบ Excel)
        function wsSheetKeys(e) {
            if (!e.target.classList.contains('ws-value-input')) return;
            if (!['Enter', 'ArrowDown', 'ArrowUp'].includes(e.key)) return;
            e.preventDefault();
            const inputs = [...document.querySelectorAll('#items-list tr.ws-item:not(.ws-hide) .ws-value-input')];
            const next = inputs[inputs.indexOf(e.target) + (e.key === 'ArrowUp' ? -1 : 1)];
            if (next) { next.focus(); next.select(); }
        }

        // อัปเดตสถานะของข้อ (สีแถว + ชิปบนการ์ดมือถือ) และภาพรวม
        function wsUpdateItem(itemId) {
            const row = document.querySelector(`#items-list .ws-item[data-item-id="${itemId}"]`);
            if (!row) return;
            const checked = document.querySelector(`input[name="status_${itemId}"]:checked`);
            const state = checked ? checked.value : '';
            row.dataset.state = state;
            const s = WS_STATE[state] || WS_STATE[''];
            const chip = document.getElementById(`ws-state-${itemId}`);
            if (chip) {
                chip.className = `${WS_CHIP_CLS} ${s.cls}`;
                chip.innerHTML = `<i class="${s.icon}"></i>${s.text}`;
            }
            wsRefreshSummary();
        }

        function wsRefreshSummary() {
            const all = document.querySelectorAll('#items-list tr.ws-item');
            if (!all.length || !document.getElementById('ws-done')) return;
            const done = [...all].filter(r => r.dataset.state).length;
            const fail = [...all].filter(r => r.dataset.state === 'Fail').length;
            document.getElementById('ws-done').textContent = done;
            document.getElementById('ws-fail').textContent = fail;
            document.getElementById('ws-fail-wrap').classList.toggle('hidden', fail === 0);
            document.getElementById('ws-progress').style.width = `${Math.round(done / all.length * 100)}%`;
            // หัวหน้า + แถบล่าง (แบบหน้าบันทึกพร้อมกัน)
            const navBar = document.getElementById('ws-nav-bar');
            if (navBar) navBar.style.width = `${Math.round(done / all.length * 100)}%`;
            const navCount = document.getElementById('ws-nav-count');
            if (navCount) navCount.textContent = `ตรวจแล้ว ${done} / ${all.length} ข้อ`;
            const barStatus = document.getElementById('ws-bar-status');
            if (barStatus) barStatus.innerHTML = `ตรวจแล้ว <b class="text-slate-800">${done}</b>/${all.length}${fail ? ` · <span class="text-red-600">ผิดปกติ ${fail}</span>` : ''}`;
            // ตัวเลขบนแถบขั้นตอน
            const sub = document.getElementById('ws-step-sub-2');
            if (sub) sub.textContent = `ตรวจแล้ว ${done}/${all.length} ข้อ`;
            const stepBtn = document.querySelector('[data-ws-step-go="2"]');
            if (stepBtn) {
                stepBtn.classList.toggle('is-done', done === all.length);
                stepBtn.querySelector('.ws-step-num').innerHTML = done === all.length ? '<i class="fa-solid fa-check"></i>' : '2';
            }
            wsApplyFilter();
        }

        function wsApplyFilter() {
            let visible = 0;
            document.querySelectorAll('#items-list tr.ws-item').forEach(r => {
                const st = r.dataset.state;
                const okState = wsFilter === 'all' || (wsFilter === 'pending' ? !st : st === wsFilter);
                const okSearch = !wsSearch || (r.dataset.search || '').includes(wsSearch);
                const show = okState && okSearch;
                r.classList.toggle('ws-hide', !show);
                if (show) visible++;
            });
            document.getElementById('ws-empty')?.classList.toggle('hidden', visible > 0);
            document.querySelectorAll('#items-list .ws-filter-btn').forEach(b => {
                const on = b.dataset.wsFilter === wsFilter;
                b.classList.toggle('bg-primary', on);
                b.classList.toggle('text-white', on);
                b.classList.toggle('border-primary', on);
                b.classList.toggle('border-slate-300', !on);
                b.classList.toggle('text-slate-600', !on);
            });
        }

        function handleStatusChange(itemId, photoRequiredId) {
            wsUpdateItem(itemId);
            if (photoRequiredId === '3') {
                const statusInputs = document.getElementsByName(`status_${itemId}`);
                const photoInput = document.getElementById(`photo_input_${itemId}`);
                const photoLabel = document.getElementById(`photo_label_${itemId}`);
                const reqStar = document.getElementById(`req_star_${itemId}`);
                
                let isFailChecked = false;
                for (let radio of statusInputs) {
                    if (radio.checked && radio.value === 'Fail') {
                        isFailChecked = true;
                        break;
                    }
                }

                // ถ้าเลือก 'ผิดปกติ' ให้บังคับถ่ายรูป
                if (isFailChecked) {
                    photoInput.setAttribute('required', 'required');
                    reqStar.className = "text-red-500";
                    reqStar.innerText = "*";
                    if(!photoInput.files || photoInput.files.length === 0) {
                    photoLabel.textContent = 'เพิ่มรูป';
                    photoLabel.classList.add('text-red-500');
                    }
                } else {
                    // ถ้าเลือกผ่าน หรือ ไม่เกี่ยวข้อง เอาบังคับออก
                    photoInput.removeAttribute('required');
                    reqStar.className = "text-xs font-normal text-slate-500 ml-1";
                    reqStar.innerText = "(ถ้ามี)";
                    if(!photoInput.files || photoInput.files.length === 0) {
                    photoLabel.textContent = 'เพิ่มรูป';
                    photoLabel.classList.remove('text-red-500');
                    const container = document.getElementById(`photo_container_${itemId}`);
                    container.classList.remove('border-red-400', 'bg-red-50');
                    }
                }
            }
        }
		
		function renderHistoryMode(data) {
			const record = data.record || {};
			const items = data.items || [];
			const actualSpares = data.actual_spares || [];
			const sparesSnapshot = data.spares_snapshot || [];
			const filesSnapshot = data.files_snapshot || [];
			const inspectors = data.inspectors || [];
		
			// เปลี่ยนหัวข้อหน้า
			const pageTitle = document.querySelector('nav h1 span');
			if (pageTitle) {
				pageTitle.textContent = 'ประวัติการบำรุงรักษา (PM History)';
			}
		
			// แสดงข้อมูลหัวใบงานจาก snapshot
			renderInfo({
				doc_no: record.doc_no,
				rev_no: record.rev_no,
				checksheet_name: record.checksheet_name,
				machine_code: record.machine_code,
				machine_name: record.machine_name,
				machine_sn: record.machine_sn,
				machine_type: record.machine_type,
				location: record.location,
				plan_date: record.plan_date
			});
		
			const actualDateEl = document.getElementById('info-actual-date')?.querySelector('span');
			if (actualDateEl) {
				actualDateEl.textContent = formatThaiDate(record.actual_date);
			}
		
			// แสดงไฟล์แนบ snapshot
			renderFiles(filesSnapshot);
		
			// แสดงอะไหล่ที่ต้องเตรียม snapshot
			renderHistoryPreparedSpares(sparesSnapshot);
		
			// ซ่อนส่วนอะไหล่/วัสดุที่ใช้จริงแบบ input
			hideActualSpareInputSection();
		
			// แสดงอะไหล่/วัสดุที่ใช้จริงแบบอ่านอย่างเดียว
			renderHistoryActualSpares(actualSpares);
		
			// แสดงรายการผลตรวจแบบอ่านอย่างเดียว
			renderHistoryItems(items);
		
			// แสดงผู้ตรวจสอบ
			const inspectorNames = inspectors.length > 0
				? inspectors.map(i => i.inspector_name_snapshot).filter(Boolean).join(', ')
				: (record.inspector_name || '-');
		
			const inspectorDisplay = document.getElementById('inspector_display');
			if (inspectorDisplay) {
				inspectorDisplay.innerHTML = `<span class="bg-emerald-100 text-emerald-800 text-xs px-2.5 py-1 rounded-full border border-emerald-200">${inspectorNames}</span>`;
				inspectorDisplay.onclick = null;
				inspectorDisplay.classList.add('pointer-events-none', 'bg-slate-50');
			}
		
			const inspectorInput = document.getElementById('inspector_name');
			if (inspectorInput) {
				inspectorInput.value = inspectorNames;
			}
		
			// แสดงหมายเหตุ
			const remarksEl = document.getElementById('remarks');
			if (remarksEl) {
				remarksEl.value = record.remarks || '';
				remarksEl.readOnly = true;
				remarksEl.classList.add('bg-slate-50');
			}
		
			// แสดงลายเซ็นแทน canvas
			renderHistorySignature(record.inspector_signature_path);

			// สถานะตรวจรับโดยหัวหน้าช่าง (หน่วยงานที่เปิดขั้นตรวจรับ) แล้วต่อด้วยผลการประเมิน
			renderApprovalBox();
			renderEvaluationResult();
		
			// ซ่อนปุ่มและส่วนประเมิน
			document.getElementById('btn_save_work')?.classList.add('hidden');
			document.getElementById('btn_save_eval')?.classList.add('hidden');
			document.getElementById('evaluation_section')?.classList.add('hidden');
		
			// ซ่อนปุ่มล้าง dropdown
			document.getElementById('inspector_clear_btn')?.classList.add('hidden');
			document.getElementById('evaluator_clear_btn')?.classList.add('hidden');
		
			// ปิด input ทั้งหมดไม่ให้แก้ไข
			lockHistoryInputs();
		}
		
		// ===== ตรวจรับโดยหัวหน้าช่าง (โหมดประวัติ) — handle_pm_approve.php =====
		//  รอตรวจรับ: ผู้ตรวจรับกด ตรวจรับ / ส่งกลับแก้ไข ได้ในใบงาน · ตรวจรับแล้ว: ผู้ตรวจรับ เวลา หมายเหตุ · หน่วยงานไม่ได้เปิด: ไม่แสดง
		async function renderApprovalBox() {
			const anchor = document.getElementById('evaluation_section');
			if (!anchor || document.getElementById('ws-approve-box')) return;
			const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
			let r;
			try { r = await (await fetch('handle_pm_approve.php?action=get_one&event_id=' + encodeURIComponent(planId))).json(); } catch (e) { r = { success: false }; }
			if (!r.success || r.approval_status === null || r.approval_status === undefined) return;
			const box = document.createElement('div');
			box.id = 'ws-approve-box';
			const ev = document.getElementById('ws-eval-result');
			(ev || anchor).insertAdjacentElement('beforebegin', box);
			const head = '<h2 class="text-base font-semibold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-user-check text-emerald-600"></i> การตรวจรับงาน</h2>';
			const last = (r.history || []).find(h => h.action === 'approve');
			if (r.approval_status === 1) {
				box.className = 'mt-3 bg-white rounded-xl shadow-sm border border-emerald-200 p-4 sm:p-5';
				box.innerHTML = `<div class="flex flex-wrap items-center justify-between gap-3">${head}<span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold"><i class="fa-solid fa-check mr-1"></i>ตรวจรับแล้ว</span></div>
					<div class="mt-2 flex flex-wrap items-end justify-between gap-3 text-sm">
						<div><div class="text-slate-500">ผู้ตรวจรับ</div><div class="font-semibold text-slate-800">${esc(last ? last.approver_name : '-')}</div><div class="text-xs text-slate-500">ตรวจรับเมื่อ ${esc(wsThDateTime(r.approved_at))}</div></div>
						${last && last.signature_path ? `<img src="${esc(last.signature_path)}" alt="ลายมือชื่อผู้ตรวจรับ" class="h-16 max-w-[220px] object-contain border border-slate-200 rounded-lg bg-white p-1">` : ''}
					</div>
					${last && last.comment ? `<div class="mt-2 text-sm bg-slate-50 rounded-lg px-3 py-2"><span class="text-slate-500">หมายเหตุ:</span> ${esc(last.comment)}</div>` : ''}`;
				return;
			}
			box.className = 'mt-3 bg-emerald-50/40 rounded-xl shadow-sm border border-dashed border-emerald-500 p-4 sm:p-5';
			box.innerHTML = `<div class="flex flex-wrap items-center justify-between gap-3">${head}
					<span class="px-2.5 py-1 rounded-full pm-review text-xs font-semibold"><i class="fa-solid fa-hourglass-half mr-1"></i>รอหัวหน้าช่างตรวจรับ</span></div>
				<div class="mt-1.5 text-xs text-emerald-800">งานนี้บันทึกผลแล้ว จะนับว่าเสร็จเมื่อผู้ตรวจรับกดตรวจรับ</div>
				${r.can_approve ? `<div class="mt-3 flex flex-wrap gap-2 justify-end">
					<button type="button" data-ws-ap="return" class="px-4 py-2 rounded-lg border border-red-200 bg-white text-red-600 hover:bg-red-50 text-sm font-semibold inline-flex items-center gap-1.5"><i class="fa-solid fa-rotate-left"></i> ส่งกลับแก้ไข</button>
					<button type="button" data-ws-ap="approve" class="px-4 py-2 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-semibold inline-flex items-center gap-1.5"><i class="fa-solid fa-check"></i> ตรวจรับ</button></div>` : ''}`;
			box.addEventListener('click', async e => {
				const b = e.target.closest('[data-ws-ap]');
				if (!b) return;
				const isReturn = b.dataset.wsAp === 'return';
				let isConfirmed = true, value;
				if (!isReturn) { value = await window.pmApproveSign({ title: 'ตรวจรับใบงานนี้' }); isConfirmed = !!value; }
				else ({ isConfirmed, value } = await Swal.fire({
					title: 'ส่งกลับแก้ไข', icon: 'warning',
					html: '<div class="text-xs text-left text-slate-500">ผลบันทึกเดิม (ผลตรวจ รูป ลายเซ็น) จะถูกยกเลิก อะไหล่คืนเข้าสต็อก · ช่างเห็นเหตุผลและบันทึกใหม่ด้วยเลขที่เอกสารเดิม</div>',
					input: 'textarea', inputPlaceholder: 'สิ่งที่ต้องแก้ไข (อย่างน้อย 5 ตัวอักษร)',
					inputValidator: v => (v || '').trim().length < 5 ? 'กรุณาระบุสิ่งที่ต้องแก้ไขอย่างน้อย 5 ตัวอักษร' : undefined,
					showCancelButton: true, confirmButtonText: 'ส่งกลับ', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#dc2626', reverseButtons: true,
				}));
				if (!isConfirmed) return;
				Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
				const body = isReturn ? { event_id: +planId, reason: value.trim() } : { event_ids: [+planId], comment: value.comment, signature: value.signature };
				let res;
				try { res = await (await fetch('handle_pm_approve.php?action=' + (isReturn ? 'return' : 'approve'), { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })).json(); }
				catch (er) { res = { success: false, error: er.message }; }
				const err = !res.success ? res.error : (!isReturn && res.results && res.results[0] && !res.results[0].success ? res.results[0].error : '');
				if (err) { Swal.fire(isReturn ? 'ส่งกลับไม่สำเร็จ' : 'ตรวจรับไม่สำเร็จ', err, 'error'); return; }
				if (!isReturn) { Swal.fire({ icon: 'success', title: 'ตรวจรับแล้ว', timer: 1200, showConfirmButton: false }); box.remove(); renderApprovalBox(); return; }
				// ส่งกลับ => ใบงานนี้ถูกยกเลิกแล้ว
				Swal.fire({ icon: 'success', title: 'ส่งกลับให้ช่างแก้ไขแล้ว', text: (res.doc_no || '') + ' (แก้ไขครั้งที่ ' + res.revision + ')' });
				box.className = 'mt-3 bg-red-50 rounded-xl border border-red-200 p-4 sm:p-5 text-sm text-red-700';
				box.innerHTML = '<i class="fa-solid fa-rotate-left mr-1"></i> ส่งกลับแก้ไขแล้ว — ผลบันทึกนี้ถูกยกเลิก ปิดหน้าต่างนี้ได้';
			});
		}

		// ===== ผลการประเมินผลงาน PM (โหมดประวัติ) =====
		async function renderEvaluationResult() {
			const anchor = document.getElementById('evaluation_section');
			if (!anchor || document.getElementById('ws-eval-result')) return;
			const box = document.createElement('div');
			box.id = 'ws-eval-result';
			box.className = 'mt-3 bg-white rounded-xl shadow-sm border border-gray-100 p-4 sm:p-5';
			box.innerHTML = '<div class="text-sm text-slate-400"><i class="fa-solid fa-circle-notch fa-spin mr-1"></i> กำลังโหลดผลการประเมิน...</div>';
			anchor.insertAdjacentElement('beforebegin', box);
			const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
			let r;
			try { r = await (await fetch('handle_pm_evaluate.php?action=get_one&event_id=' + encodeURIComponent(planId))).json(); } catch (e) { r = { success: false }; }
			if (!r.success) { box.remove(); return; }
			const d = r.data;
			const head = '<h2 class="text-base font-semibold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-star-half-stroke text-primary"></i> ผลการประเมิน</h2>';
			if (+d.evaluation_status !== 1 || !d.id) {
				box.innerHTML = `<div class="flex flex-wrap items-center justify-between gap-3">${head}
					<div class="flex items-center gap-2"><span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold">ยังไม่ได้ประเมิน</span>
					${r.can_evaluate && !new URLSearchParams(location.search).has('noeval') ? `<a href="pm_evaluate_batch.php?ids=${encodeURIComponent(planId)}&back=${encodeURIComponent(location.pathname.split('/').pop() + location.search)}" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold inline-flex items-center gap-1.5"><i class="fa-solid fa-star"></i> ประเมินใบงานนี้</a>` : ''}</div></div>`;
				return;
			}
			const dt = d.eval_date ? new Date(d.eval_date + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
			box.innerHTML = `<div class="flex flex-wrap items-center justify-between gap-3">${head}
					<div class="text-sm">คะแนนเฉลี่ย <b class="text-2xl text-primary">${Number(d.average_score).toFixed(2)}</b> <span class="text-slate-400">/ 5.00</span></div></div>
				<div class="mt-3 grid sm:grid-cols-2 gap-x-6 gap-y-1.5">
					${(d.topics || []).map((x, i) => `<div class="flex items-center justify-between gap-3 text-sm py-1 border-b border-slate-100"><span class="text-slate-600"><span class="text-slate-400 mr-1">${i + 1}.</span>${esc(x.title)}</span><b class="flex-none w-7 h-7 rounded-md bg-primary/10 text-primary flex items-center justify-center">${+x.score}</b></div>`).join('')}
				</div>
				${d.eval_comments ? `<div class="mt-3 text-sm bg-slate-50 rounded-lg px-3 py-2"><span class="text-slate-500">ข้อเสนอแนะ:</span> ${esc(d.eval_comments)}</div>` : ''}
				<div class="mt-3 flex flex-wrap items-end justify-between gap-3 text-sm">
					<div><div class="text-slate-500">ผู้ประเมิน</div><div class="font-semibold text-slate-800">${esc(d.evaluator_name || '-')}</div><div class="text-xs text-slate-500">ประเมินเมื่อ ${dt}</div></div>
					${d.evaluator_signature_path ? `<img src="${esc(d.evaluator_signature_path)}" alt="ลายเซ็นผู้ประเมิน" class="h-16 max-w-[220px] object-contain border border-slate-200 rounded-lg bg-white p-1">` : ''}
				</div>`;
		}
		function hideActualSpareInputSection() {
			const headings = Array.from(document.querySelectorAll('h2'));
		
			headings.forEach(h => {
				if (h.textContent.includes('รายการอะไหล่/วัสดุที่ใช้จริง')) {
					const section = h.closest('.bg-white');
					if (section) {
						section.classList.add('hidden');
					}
				}
			});
		}
		
		// โหมดประวัติ: ใช้ตาราง + แผงรายละเอียดแบบเดียวกับโหมดบันทึก แต่แสดงผลอย่างเดียว
		function renderHistoryItems(items) {
			const container = document.getElementById('items-list');
			wsDetail = {};

			if (!items || items.length === 0) {
				container.innerHTML = `
					<div class="p-8 text-center text-slate-400">
						ไม่พบรายการผลการตรวจสอบ
					</div>
				`;
				return;
			}

			let rows = '';
			items.forEach((item, index) => {
				const st = WS_STATE[item.result_status];
				const statusLabel = st ? st.text : (item.result_status || '-');
				const statusCls = st ? st.cls : WS_STATE[''].cls;
				const statusIcon = st ? st.icon : WS_STATE[''].icon;
				const needMeasure = hasMeasurementConfig(item);
				wsSetDetail(item.checksheet_item_id, index + 1, item, '', item.illustration_path, item.action_abnormal);

				const photoUrl = item.result_photo_path ? String(item.result_photo_path).trim() : '';
				const photoHtml = photoUrl
					? `
						<div
							class="group relative w-full h-40 md:h-14 rounded-lg overflow-hidden border border-slate-200 bg-slate-50 cursor-pointer hover:border-primary transition"
							onclick='ImageCarousel.open(${JSON.stringify([photoUrl])}, "รูปหลักฐาน")'
							title="คลิกเพื่อดูรูปใหญ่"
						>
							<img
								src="${photoUrl}"
								alt="รูปหลักฐาน"
								class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
								onerror="this.parentElement.innerHTML='<div class=&quot;w-full h-full flex items-center justify-center text-xs text-red-500 bg-red-50&quot;>โหลดรูปไม่ได้</div>';"
							>
							<span class="absolute bottom-1 right-1 text-white text-[10px] bg-black/60 px-1.5 py-0.5 rounded"><i class="fa-solid fa-magnifying-glass-plus"></i></span>
						</div>
					  `
					: `<span class="text-slate-400 text-xs">ไม่มีรูป</span>`;

				rows += `
					<tr class="ws-item" data-item-id="${item.checksheet_item_id}" data-state="${wsAttr(item.result_status || '')}">
						${wsPointCells(index, item, '', `<span class="${WS_CHIP_CLS} ${statusCls}"><i class="${statusIcon}"></i>${statusLabel}</span>`)}
						<td class="ws-col-status" data-label="ผลการตรวจ">
							<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full border text-xs font-bold ${statusCls}"><i class="${statusIcon}"></i>${statusLabel}</span>
						</td>
						<td class="ws-col-value ${needMeasure || (item.actual_value && item.actual_value !== '-') ? '' : 'ws-empty'}" data-label="ค่าที่วัดได้จริง">
							<b class="text-slate-800">${item.actual_value || '-'}</b> <span class="text-xs text-slate-500">${item.unit || ''}</span>
							${needMeasure ? `<div class="text-xs text-slate-500 mt-1">มาตรฐาน <b class="text-primary">${item.expected_value}</b> ${item.unit || ''}</div>` : ''}
						</td>
						<td class="ws-col-photo" data-label="รูปภาพหลักฐาน">${photoHtml}</td>
					</tr>
				`;
			});

			container.innerHTML = wsSheetHtml(rows);
			wsBindSheet(container);
			wsSelect(items[0].checksheet_item_id);
			const sub = document.getElementById('ws-step-sub-2');
			if (sub) sub.textContent = `${items.length} ข้อ`;
		}
		
		function renderHistoryActualSpares(spares) {
			if (!spares || spares.length === 0) return;
		
			const container = document.createElement('div');
			container.className = 'bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6';
		
			let rows = '';
		
			spares.forEach((sp, index) => {
				rows += `
					<tr class="hover:bg-amber-50 transition-colors">
						<td class="py-3 px-6 text-center text-slate-500 font-medium">${index + 1}</td>
						<td class="py-3 px-6">
							<div class="font-semibold text-slate-900">${sp.rpd_details_head || '-'}</div>
							<div class="text-xs text-slate-500">${sp.pd_gen_code || '-'}</div>
						</td>
						<td class="py-3 px-6 text-center font-bold text-amber-700">${sp.rpd_qty || '-'}</td>
						<td class="py-3 px-6 text-center">${sp.pd_unit || '-'}</td>
						<td class="py-3 px-6 text-right">${Number(sp.rpd_price || 0).toLocaleString('th-TH')}</td>
						<td class="py-3 px-6 text-right font-bold">${Number(sp.rpd_sum_money || 0).toLocaleString('th-TH')}</td>
					</tr>
				`;
			});
		
			container.innerHTML = `
				<div class="bg-amber-50 border-b border-amber-200 px-6 py-4 flex items-center gap-2.5">
					<i class="fa-solid fa-screwdriver-wrench text-amber-700 text-lg"></i>
					<h2 class="text-lg font-semibold text-amber-900">อะไหล่/วัสดุที่ใช้จริง</h2>
				</div>
				<div class="p-0 overflow-x-auto">
					<table class="w-full text-sm">
						<thead class="bg-amber-100/50 text-amber-900 border-b border-amber-200">
							<tr>
								<th class="py-3 px-6 text-center w-16">ลำดับ</th>
								<th class="py-3 px-6 text-left">รายการ</th>
								<th class="py-3 px-6 text-center">จำนวน</th>
								<th class="py-3 px-6 text-center">หน่วย</th>
								<th class="py-3 px-6 text-right">ราคา</th>
								<th class="py-3 px-6 text-right">รวม</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-amber-100">${rows}</tbody>
					</table>
				</div>
			`;
		
			// โหมดประวัติ: แสดงอะไหล่ที่ใช้จริงในขั้นที่ 3
			document.querySelector('.ws-step[data-ws-step="3"]')?.appendChild(container);
		}
		
		function renderHistoryPreparedSpares(spares) {
			if (!spares || spares.length === 0) {
				return;
			}
		
			const container = document.getElementById('spares-container');
			const tbody = document.getElementById('spares-list');
		
			if (!container || !tbody) return;
		
			container.classList.remove('hidden');
			tbody.innerHTML = spares.map(sp => wsSpareChip(sp.part_name || '-', sp.quantity)).join('');
		}
		
		function renderHistorySignature(signaturePath) {
			const canvas = document.getElementById('inspector-signature-pad');
			if (!canvas) return;
		
			const wrap = canvas.closest('.border');
			if (!wrap) return;
		
			wrap.innerHTML = '';
		
			if (signaturePath) {
				wrap.innerHTML = `
					<div class="min-h-[150px] flex items-center justify-center bg-white">
						<img src="${signaturePath}" class="max-h-32 object-contain">
					</div>
				`;
			} else {
				wrap.innerHTML = `
					<div class="min-h-[150px] flex items-center justify-center bg-slate-50 text-slate-400">
						ไม่มีลายเซ็น
					</div>
				`;
			}
		}
		
		function lockHistoryInputs() {
				document.querySelectorAll('input, textarea, select, button').forEach(el => {
				const id = el.id || '';
		
				// ไม่ล็อกปุ่มยกเลิก/ปิดหน้าต่าง
				if (el.textContent && el.textContent.includes('ยกเลิก')) {
					return;
				}
		
				if (id === 'btn_save_work' || id === 'btn_save_eval') {
					return;
				}
		
				if (el.tagName === 'BUTTON') {
					if (!el.onclick || String(el.onclick).includes('window.close')) {
						return;
					}
				}
		
				if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT') {
					el.readOnly = true;
					el.disabled = true;
					el.classList.add('bg-slate-50');
				}
			});
		}
		
        function previewImage(input, itemId) {
            const file = input.files[0];
            const base64Input = document.getElementById(`photo_base64_${itemId}`);
            const container = document.getElementById(`photo_container_${itemId}`);
            const uploadUI = document.getElementById(`upload_ui_${itemId}`);
            const previewUI = document.getElementById(`preview_ui_${itemId}`);
            const imgPreview = document.getElementById(`img_preview_${itemId}`);
            const label = document.getElementById(`photo_label_${itemId}`);

            if (file) {
                // เปลี่ยนสีขอบเป็นสี primary
                container.classList.add('border-primary', 'bg-blue-50/10');
                container.classList.remove('p-2'); // เอา padding ออกเพื่อให้รูปภาพชิดขอบสวยงาม
                
                const reader = new FileReader();
                reader.onload = function(e) { 
                    // เซ็ตค่า Base64 และนำไปแสดงที่ <img>
                    const cleanBase64 = String(e.target.result || '')
						.replace(/[\u0000-\u001F\u007F-\u009F]/g, '');
					
					base64Input.value = cleanBase64;
					imgPreview.src = cleanBase64;
                    
                    // สลับ UI: ซ่อนกล่องข้อความอัปโหลด -> แสดงรูปพรีวิว
                    uploadUI.classList.add('hidden');
                    previewUI.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                // กรณีผู้ใช้กด Cancle ไม่อัปโหลดรูปภาพ (เคลียร์ค่าทิ้ง)
                const isPhotoRequired = input.hasAttribute('required');
                label.textContent = 'เพิ่มรูป';
                label.classList.remove('text-primary', 'font-bold');
                
                container.classList.remove('border-primary', 'bg-blue-50/10');
                container.classList.add('p-2');
                
                base64Input.value = '';
                imgPreview.src = '';
                
                // สลับ UI คืนค่าเดิม: แสดงกล่องข้อความอัปโหลด -> ซ่อนรูปพรีวิว
                uploadUI.classList.remove('hidden');
                previewUI.classList.add('hidden');
            }
        }
		
		function clearValidationUI() {
			document.querySelectorAll('.field-error').forEach(el => el.classList.remove('field-error'));
			document.querySelectorAll('.item-error-summary').forEach(el => el.classList.remove('item-error-summary'));
			document.querySelectorAll('.item-error-panel').forEach(el => el.classList.remove('item-error-panel'));
		}
		
		function markElementError(element) {
			if (element) element.classList.add('field-error');
		}
		
		// แถวของข้อในตารางจุดตรวจสอบ: เอาออกจากตัวกรองเพื่อให้มองเห็น
		function wsShowRow(itemId) {
			const row = document.querySelector(`#items-list .ws-item[data-item-id="${itemId}"]`);
			if (row) row.classList.remove('ws-hide');
			return row;
		}
		
		function markItemError(itemId) {
			const row = wsShowRow(itemId);
			if (!row) return;
		
			const pointCell = row.querySelector('.ws-col-point');
			const panel = row.querySelector('.ws-status-box');
		
			if (pointCell) pointCell.classList.add('item-error-summary');
			if (panel) panel.classList.add('item-error-panel');
		}

		
		function scrollToFirstError(errors) {
			if (!errors || errors.length === 0) return;
		
			const first = errors[0];
		
			if (first.type === 'item') {
				const row = wsShowRow(first.itemId);
				wsShowStepOf(row);
				if (row) {
					row.scrollIntoView({ behavior: 'smooth', block: 'center' });
				}
				return;
			}
		
			if (first.element) {
				wsShowStepOf(first.element);
				first.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		}
		
		function openAndScrollToElement(element) {
			if (!element) return;
		
			element.closest('.ws-item')?.classList.remove('ws-hide');
			wsShowStepOf(element);
		
			setTimeout(() => {
				element.scrollIntoView({
					behavior: 'smooth',
					block: 'center'
				});
		
				try {
					element.focus({ preventScroll: true });
				} catch (e) {}
			}, 180);
		}
		
		function goToValidationTarget(errorIndex) {
			if (!window.latestValidationErrors || !window.latestValidationErrors[errorIndex]) return;
		
			const err = window.latestValidationErrors[errorIndex];
		
			Swal.close();
		
			setTimeout(() => {
				if (err.type === 'header') {
					if (err.fieldKey === 'inspector_name') {
						const el = document.getElementById('inspector_display');
						openAndScrollToElement(el);
						setTimeout(() => {
							toggleDropdown('inspector_dropdown');
						}, 250);
						return;
					}
		
					if (err.fieldKey === 'inspector_signature') {
						const el = document.getElementById('inspector-signature-pad')?.closest('.border');
						openAndScrollToElement(el);
						return;
					}

					if (err.fieldKey === 'actual_date') {
						const el = document.getElementById('actual_date');
						openAndScrollToElement(el);
						setTimeout(() => el?.focus(), 300);
						return;
					}
				}
		
				if (err.type === 'item') {
					const itemId = err.itemId;
					const details = wsShowRow(itemId);
		
					let target = null;
		
					if (err.fieldKey === 'status') {
						target = document.querySelector(`input[name="status_${itemId}"]`)?.closest('.ws-status-box');
					} else if (err.fieldKey === 'value') {
						target = document.querySelector(`[name="val_${itemId}"]`);
					} else if (err.fieldKey === 'photo') {
						target = document.getElementById(`photo_container_${itemId}`);
					} else {
						target = details;
					}
		
					openAndScrollToElement(target || details);
				}
			}, 120);
		}
		
		window.goToValidationTarget = goToValidationTarget;
		
		// เปิดจากหน้าบันทึกพร้อมกันหลายเครื่อง (pm_worksheet_batch.php) => นำผลที่กรอกไว้ในหน้านั้นมาใส่ให้ ไม่ต้องกรอกซ้ำ
		//  ผลการตรวจ / ค่าที่วัด / รูปหลักฐาน / อะไหล่ที่ใช้จริง — ผู้ใช้ตรวจทานและแก้ได้ก่อนบันทึก
		function applyBatchPrefill() {
			let d = null;
			try { d = window.opener && window.opener.pmBatchPrefill && window.opener.pmBatchPrefill(planId); } catch (e) { d = null; }
			if (!d) return;
			let n = 0;
			worksheetItems.forEach(item => {
				const id = item.id;
				const st = d.status && d.status[id];
				const radio = st ? document.querySelector(`input[name="status_${id}"][value="${st}"]`) : null;
				if (radio) { radio.checked = true; handleStatusChange(id, String(item.photo_required_id)); n++; }
				const v = d.values && d.values[id];
				const valEl = document.querySelector(`input[name="val_${id}"]:not([type="hidden"])`);
				if (valEl && v != null && String(v).trim() !== '') { valEl.value = v; n++; }
				const ph = d.photos && d.photos[id];
				if (ph) { setPhotoFromDataUrl(id, ph); n++; }
				wsUpdateItem(id);
			});
			(d.parts || []).forEach(p => {
				if (typeof addPartRow !== 'function' || !(+p.rpd_qty > 0)) return;
				addPartRow({ pd_id: p.rpd_product_id, ps_id: p.ps_id, wh_id: p.wh_id, wh_name: p.wh_name, model: p.pd_model, code: p.pd_gen_code,
					name: p.rpd_details_head, detail: p.rpd_details, brand: p.rpd_brand, price: p.rpd_price, unit: p.pd_unit }, String(p.rpd_qty));
				n++;
			});
			if (typeof updatePartsTotal === 'function') updatePartsTotal();
			if (n) Swal.fire({ toast: true, position: 'top', icon: 'info', title: 'นำข้อมูลที่กรอกไว้ในหน้าบันทึกพร้อมกันมาใส่แล้ว — ตรวจทานก่อนบันทึก', showConfirmButton: false, timer: 3500 });
		}
		// ใส่รูปจาก data URL (แบบเดียวกับเลือกไฟล์ใน previewImage)
		function setPhotoFromDataUrl(itemId, url) {
			const base64Input = document.getElementById(`photo_base64_${itemId}`);
			if (!base64Input) return;
			base64Input.value = url;
			document.getElementById(`img_preview_${itemId}`).src = url;
			document.getElementById(`upload_ui_${itemId}`).classList.add('hidden');
			document.getElementById(`preview_ui_${itemId}`).classList.remove('hidden');
			const container = document.getElementById(`photo_container_${itemId}`);
			container.classList.add('border-primary', 'bg-blue-50/10');
			container.classList.remove('p-2', 'border-red-400', 'bg-red-50');
		}

		function getItemFormData(itemId) {
			const statusEl = document.querySelector(`input[name="status_${itemId}"]:checked`);
			const valueEl = document.querySelector(`[name="val_${itemId}"]`);
			const photoBase64El = document.getElementById(`photo_base64_${itemId}`);
			const photoInputEl = document.getElementById(`photo_input_${itemId}`);
		
			return {
				status: statusEl ? String(statusEl.value).trim() : '',
				value: valueEl ? String(valueEl.value).trim() : '',
				photoBase64: photoBase64El ? String(photoBase64El.value).trim() : '',
				hasPhotoFile: !!(photoInputEl && photoInputEl.files && photoInputEl.files.length > 0),
				statusEl,
				valueEl,
				photoInputEl
			};
		}
		
		function hasMeasurementConfig(item) {
			return item.expected_value !== null &&
				   item.expected_value !== undefined &&
				   String(item.expected_value).trim() !== '' &&
				   String(item.expected_value).trim() !== '-';
		}
		
		function isNumericRequiredValue(value) {
			return value !== '' && !isNaN(value);
		}
		
		function validateInspectorSection() {
			const errors = [];
	
			const inspectorInput = document.getElementById('inspector_name');
			const inspectorUsersJsonInput = document.getElementById('inspector_users_json');
			const inspectorDisplay = document.getElementById('inspector_display');
			const remarksEl = document.getElementById('remarks');
	
			const inspectorName = inspectorInput ? inspectorInput.value.trim() : '';
			const inspectorUsersJson = inspectorUsersJsonInput ? inspectorUsersJsonInput.value.trim() : '';
			const remarks = remarksEl ? remarksEl.value.trim() : '';
	
			if (!inspectorName) {
				markElementError(inspectorDisplay);
				errors.push({
					type: 'header',
					fieldKey: 'inspector_name',
					message: 'ยังไม่ได้เลือกชื่อผู้ตรวจสอบ',
					element: inspectorDisplay
				});
			}

			if (!inspectorUsersJson || inspectorUsersJson === '[]') {
				markElementError(inspectorDisplay);
				errors.push({
					type: 'header',
					fieldKey: 'inspector_users_json',
					message: 'ไม่พบ user_id ของผู้ตรวจสอบ กรุณาเลือกชื่อใหม่อีกครั้ง',
					element: inspectorDisplay
				});
			}
	
			// วันที่ทำ PM (โหมดเลือกวันที่): ต้องอยู่ในช่วงสัญญา/แผนของหน่วยงาน
			const dateInp = document.getElementById('actual_date');
			const dw = wsRecordSetting.window;
			let actualDate = '';
			if (wsRecordSetting.date_mode === 'pick' && dw && dateInp) {
				actualDate = dateInp.value;
				if (!actualDate || actualDate < dw.min || actualDate > dw.max) {
					markElementError(dateInp);
					errors.push({
						type: 'header',
						fieldKey: 'actual_date',
						message: `วันที่ทำ PM ต้องอยู่ระหว่าง ${wsShortDate(dw.min)} – ${wsShortDate(dw.max)}`,
						element: dateInp
					});
				}
			}

			// ลายมือชื่อ: บังคับตามการตั้งค่าหน่วยงาน
			if (+wsRecordSetting.sign_required && (!inspectorPad || inspectorPad.isEmpty())) {
				const signCanvasWrap = document.getElementById('inspector-signature-pad')?.closest('.border');
				markElementError(signCanvasWrap);
				errors.push({
					type: 'header',
					fieldKey: 'inspector_signature',
					message: 'ยังไม่ได้ลงลายมือชื่อผู้ตรวจสอบ',
					element: signCanvasWrap
				});
			}
	
			return {
				valid: errors.length === 0,
				errors,
				data: {
					inspector_name: inspectorName,
					inspector_users_json: inspectorUsersJson,
					remarks,
					actual_date: actualDate,
					inspector_signature: (inspectorPad && !inspectorPad.isEmpty())
						? inspectorPad.toDataURL('image/png')
						: ''
				}
			};
		}
		
		function validateWorksheetItems(items) {
			const errors = [];
			const normalizedItems = [];
		
			items.forEach((item, index) => {
				const rowNo = index + 1;
				const itemId = item.id;
				const formData = getItemFormData(itemId);
		
				const status = formData.status;
				const value = formData.value;
				const hasPhoto = formData.photoBase64 !== '' || formData.hasPhotoFile;
				const photoRequiredId = String(item.photo_required_id || '');
				const needMeasurement = hasMeasurementConfig(item);
		
				let hasErrorInThisItem = false;
		
				if (!status) {
					hasErrorInThisItem = true;
					const statusBox = document.querySelector(`input[name="status_${itemId}"]`)?.closest('.ws-status-box');
					markElementError(statusBox);
					errors.push({
						type: 'item',
						itemId,
						fieldKey: 'status',
						message: `ข้อ ${rowNo} "${item.check_point || '-'}" : ยังไม่ได้ระบุผลการตรวจ`,
						element: statusBox
					});
				}
		
				//if (needMeasurement && value === '') {
//					hasErrorInThisItem = true;
//					markElementError(formData.valueEl);
//					errors.push({
//						type: 'item',
//						itemId,
//						fieldKey: 'value',
//						message: `ข้อ ${rowNo} "${item.check_point || '-'}" : ต้องกรอกค่าที่วัดได้จริง ตามค่ามาตรฐาน ${item.expected_value || '-'}`,
//						element: formData.valueEl
//					});
//				}
		
				if (needMeasurement && value !== '' && !isNumericRequiredValue(value)) {
					hasErrorInThisItem = true;
					markElementError(formData.valueEl);
					errors.push({
						type: 'item',
						itemId,
						fieldKey: 'value',
						message: `ข้อ ${rowNo} "${item.check_point || '-'}" : ค่า ${item.measurement_name} ต้องเป็นตัวเลข`,
						element: formData.valueEl
					});
				}
		
				if (photoRequiredId === '1' && !hasPhoto) {
					hasErrorInThisItem = true;
					const photoWrap = document.getElementById(`photo_container_${itemId}`);
					markElementError(photoWrap);
					errors.push({
						type: 'item',
						itemId,
						fieldKey: 'photo',
						message: `ข้อ ${rowNo} "${item.check_point || '-'}" : ระบบกำหนดให้แนบรูปภาพหลักฐาน`,
						element: photoWrap
					});
				}
		
				if (photoRequiredId === '3' && status === 'Fail' && !hasPhoto) {
					hasErrorInThisItem = true;
					const photoWrap = document.getElementById(`photo_container_${itemId}`);
					markElementError(photoWrap);
					errors.push({
						type: 'item',
						itemId,
						fieldKey: 'photo',
						message: `ข้อ ${rowNo} "${item.check_point || '-'}" : เมื่อผลเป็น Fail ระบบกำหนดให้แนบรูปภาพหลักฐาน`,
						element: photoWrap
					});
				}
		
				if (String(item.check_type_id) === '1' && status && !['Pass', 'Fail'].includes(status)) {
					hasErrorInThisItem = true;
					errors.push({
						type: 'item',
						itemId,
						fieldKey: 'status',
						message: `ข้อ ${rowNo} "${item.check_point || '-'}" : ค่าผลการตรวจไม่ถูกต้อง`,
						element: null
					});
				}
		
				if (String(item.check_type_id) === '2' && status && !['Pass', 'Fail', 'N/A'].includes(status)) {
					hasErrorInThisItem = true;
					errors.push({
						type: 'item',
						itemId,
						fieldKey: 'status',
						message: `ข้อ ${rowNo} "${item.check_point || '-'}" : ค่าผลการตรวจไม่ถูกต้อง`,
						element: null
					});
				}
		
				if (hasErrorInThisItem) {
					markItemError(itemId);
				}
		
				const cleanPhotoBase64 = (formData.photoBase64 || '')
					.replace(/[\u0000-\u001F\u007F-\u009F]/g, '');
				
				normalizedItems.push({
					item_id: itemId,
					status: status || '',
					value: value !== '' ? value : '-',
					photo_required_id: photoRequiredId,
					check_type_id: String(item.check_type_id || ''),
					measurement_name: item.measurement_name || '',
					has_photo: cleanPhotoBase64 !== '' ? 1 : 0
				});
			});
		
			return {
				valid: errors.length === 0,
				errors,
				items: normalizedItems
			};
		}
		
		function buildEnterpriseValidationHtml(errors) {
			window.latestValidationErrors = errors;
		
			const headerErrors = errors.filter(err => err.type === 'header');
			const itemErrors = errors.filter(err => err.type === 'item');
		
			const renderSection = (title, icon, items, tone = 'warning', startIndex = 0) => {
				if (!items.length) return '';
		
				const toneMap = {
					warning: {
						bg: '#fff7ed',
						border: '#fdba74',
						title: '#9a3412',
						text: '#7c2d12',
						badgeBg: '#ffedd5',
						badgeText: '#9a3412'
					},
					neutral: {
						bg: '#f8fafc',
						border: '#cbd5e1',
						title: '#334155',
						text: '#475569',
						badgeBg: '#e2e8f0',
						badgeText: '#334155'
					}
				};
		
				const c = toneMap[tone];
		
				return `
					<div style="
						margin-top:12px;
						border:1px solid ${c.border};
						background:${c.bg};
						border-radius:14px;
						padding:14px 16px;
						text-align:left;
					">
						<div style="
							display:flex;
							align-items:center;
							justify-content:space-between;
							gap:12px;
							margin-bottom:10px;
						">
							<div style="
								display:flex;
								align-items:center;
								gap:8px;
								font-weight:700;
								color:${c.title};
								font-size:14px;
							">
								<span>${icon}</span>
								<span>${title}</span>
							</div>
							<span style="
								display:inline-flex;
								align-items:center;
								justify-content:center;
								min-width:28px;
								height:28px;
								padding:0 10px;
								border-radius:999px;
								background:${c.badgeBg};
								color:${c.badgeText};
								font-size:12px;
								font-weight:700;
							">${items.length}</span>
						</div>
		
						<div style="display:flex; flex-direction:column; gap:8px;">
							${items.map((err, index) => {
								const realIndex = startIndex + index;
								return `
									<button
										type="button"
										onclick="goToValidationTarget(${realIndex})"
										style="
											width:100%;
											background:#ffffff;
											border:1px solid rgba(148,163,184,0.18);
											border-radius:10px;
											padding:10px 12px;
											display:flex;
											gap:10px;
											align-items:flex-start;
											text-align:left;
											cursor:pointer;
											transition:all .18s ease;
										"
										onmouseover="this.style.borderColor='#fb923c'; this.style.transform='translateY(-1px)'"
										onmouseout="this.style.borderColor='rgba(148,163,184,0.18)'; this.style.transform='translateY(0)'"
									>
										<div style="
											flex:0 0 auto;
											width:22px;
											height:22px;
											border-radius:999px;
											background:${c.badgeBg};
											color:${c.badgeText};
											display:flex;
											align-items:center;
											justify-content:center;
											font-size:11px;
											font-weight:700;
											margin-top:1px;
										">${realIndex + 1}</div>
		
										<div style="flex:1;">
											<div style="
												color:${c.text};
												font-size:13px;
												line-height:1.55;
												margin-bottom:4px;
											">
												${err.message}
											</div>
											<div style="
												font-size:11px;
												color:#64748b;
												font-weight:600;
											">
												คลิกเพื่อไปยังตำแหน่งที่ต้องแก้ไข
											</div>
										</div>
									</button>
								`;
							}).join('')}
						</div>
					</div>
				`;
			};
		
			return `
				<div style="text-align:left;">
					<div style="
						border:1px solid #fde68a;
						background:linear-gradient(180deg,#fffdf5 0%, #fffaf0 100%);
						border-radius:16px;
						padding:16px;
						margin-bottom:12px;
					">
						<div style="
							display:flex;
							align-items:flex-start;
							gap:12px;
						">
							<div style="
								width:42px;
								height:42px;
								border-radius:12px;
								background:#fff7ed;
								border:1px solid #fdba74;
								display:flex;
								align-items:center;
								justify-content:center;
								font-size:20px;
							">⚠️</div>
		
							<div>
								<div style="
									font-size:16px;
									font-weight:700;
									color:#92400e;
									margin-bottom:4px;
								">
									ข้อมูลยังไม่พร้อมสำหรับการบันทึก
								</div>
								<div style="
									font-size:13px;
									color:#78716c;
									line-height:1.6;
								">
									พบรายการที่ต้องตรวจสอบทั้งหมด
									<strong style="color:#92400e;"> ${errors.length} จุด</strong>
									กรุณาคลิกที่รายการเพื่อไปยังตำแหน่งที่ต้องแก้ไข
								</div>
							</div>
						</div>
					</div>
		
					<div style="
						max-height: 420px;
						overflow-y: auto;
						padding-right: 6px;
					">
						${renderSection('ข้อมูลผู้ตรวจสอบ', '👤', headerErrors, 'neutral', 0)}
						${renderSection('รายการจุดตรวจสอบ', '🧾', itemErrors, 'warning', headerErrors.length)}
					</div>
				</div>
			`;
		}
		
		function collectActualSpares() {
			const rows = document.querySelectorAll('#parts_wrap > div');
			const parts = [];
		
			rows.forEach(row => {
				const qtyInput = row.querySelector('input[data-role="qty"]');
				const rpdQty = qtyInput ? String(qtyInput.value || '').trim() : '';
		
				if (rpdQty !== '' && parseFloat(rpdQty) > 0) {
					const qty = parseFloat(rpdQty || '0');
					const price = parseFloat(row.dataset.price || '0');
		
					parts.push({
						rpd_product_id: row.dataset.productId || '',
						ps_id: row.dataset.stockId || '',
						wh_id: row.dataset.whId || '',
		
						pd_gen_code: row.dataset.genCode || '',
						rpd_details_head: row.dataset.detailsHead || '',
						rpd_details: row.dataset.details || '',
						rpd_brand: row.dataset.brand || '',
		
						rpd_qty: qty,
						rpd_price: price,
						pd_unit: row.dataset.unit || '',
						rpd_sum_money: qty * price,
		
						wh_name: row.dataset.whName || '',
						pd_model: row.dataset.model || ''
					});
				}
			});
		
			return parts;
		}

        // ฟังก์ชันบันทึกส่วนแรก (Inspector)
       async function saveWorkRecord() {
			try {
				clearValidationUI();
		
				if (!Array.isArray(worksheetItems) || worksheetItems.length === 0) {
					await Swal.fire({
						icon: 'warning',
						title: 'ไม่พบรายการตรวจสอบ',
						text: 'ยังไม่มีข้อมูลรายการจุดตรวจสอบสำหรับบันทึก',
						confirmButtonColor: '#006b9f'
					});
					return;
				}
		
				const inspectorCheck = validateInspectorSection();
				const itemsCheck = validateWorksheetItems(worksheetItems);
		
				const allErrors = [
					...inspectorCheck.errors,
					...itemsCheck.errors
				];
		
				if (allErrors.length > 0) {
					scrollToFirstError(allErrors);
		
					await Swal.fire({
						icon: 'warning',
						title: 'ตรวจพบข้อมูลไม่ครบ',
						html: buildEnterpriseValidationHtml(allErrors),
						width: 760,
						confirmButtonText: 'ไปแก้ไขข้อมูล',
						confirmButtonColor: '#006b9f',
						customClass: {
							popup: 'pm-swal-enterprise',
							title: 'pm-swal-title',
							htmlContainer: 'pm-swal-html'
						}
					});
					return;
				}
		
				Swal.fire({
					title: 'กำลังบันทึกข้อมูล',
					html: `
						<div style="text-align:center; color:#475569; font-size:13px;">
							กรุณารอสักครู่ ระบบกำลังตรวจสอบและบันทึกผลการปฏิบัติงาน
						</div>
					`,
					allowOutsideClick: false,
					allowEscapeKey: false,
					showConfirmButton: false,
					didOpen: () => {
						Swal.showLoading();
					},
					customClass: {
						popup: 'pm-swal-enterprise',
						title: 'pm-swal-title',
						htmlContainer: 'pm-swal-html'
					}
				});
		
				const formBody = new FormData();
				const parts = collectActualSpares();
				const itemsPayload = JSON.stringify(itemsCheck.items || []);
				const partsPayload = JSON.stringify(parts || []);
				const cleanInspectorSignature = String(inspectorCheck.data.inspector_signature || '')
					.replace(/[\u0000-\u001F\u007F-\u009F]/g, '');
				
				formBody.append('action', 'save');
				formBody.append('plan_id', planId);
				formBody.append('inspector_name', inspectorCheck.data.inspector_name);
				formBody.append('inspector_users_json', inspectorCheck.data.inspector_users_json);
				formBody.append('remarks', inspectorCheck.data.remarks);
				formBody.append('inspector_signature', cleanInspectorSignature);
				formBody.append('actual_date', inspectorCheck.data.actual_date || '');
				formBody.append('items', itemsPayload);
				formBody.append('parts', partsPayload);
				
				itemsCheck.items.forEach(item => {
					const photoEl = document.getElementById(`photo_base64_${item.item_id}`);
					const photoBase64 = photoEl ? String(photoEl.value || '').trim() : '';
					if (photoBase64 !== '') {
						const cleanPhotoBase64 = photoBase64.replace(/[\u0000-\u001F\u007F-\u009F]/g, '');
						formBody.append(`photo_${item.item_id}`, cleanPhotoBase64);
					}
				});
				
				wsSaving = true;   // dialog ในหน้าวางแผน PM จะไม่ให้ปิดระหว่างนี้
				const response = await axios.post('handle_pm_worksheet.php', formBody).finally(() => { wsSaving = false; });
				Swal.close();

				if (response.data.success) {
					// การประเมินทำที่แท็บ "ประเมิน PM" ในหน้าวางแผน PM (หรือปุ่มในใบงานโหมดประวัติ)
					document.getElementById('btn_save_work').classList.add('hidden');
					wsCloseSign();
					document.getElementById('ws-open-sign')?.classList.add('hidden');
		
					await Swal.fire({
						icon: 'success',
						title: 'บันทึกผลสำเร็จ',
						html: `
							<div style="text-align:left;">
								<div style="
									border:1px solid #bbf7d0;
									background:linear-gradient(180deg,#f0fdf4 0%, #ecfdf5 100%);
									border-radius:16px;
									padding:16px;
								">
									<div style="display:flex; gap:12px; align-items:flex-start;">
										<div style="
											width:42px;
											height:42px;
											border-radius:12px;
											background:#dcfce7;
											border:1px solid #86efac;
											display:flex;
											align-items:center;
											justify-content:center;
											font-size:20px;
										">✅</div>
					
										<div>
											<div style="
												font-size:15px;
												font-weight:700;
												color:#166534;
												margin-bottom:4px;
											">
												บันทึกผลการปฏิบัติงานเรียบร้อยแล้ว
											</div>
											<div style="
												font-size:13px;
												color:#3f3f46;
												line-height:1.6;
											">
												ระบบบันทึกข้อมูลสำเร็จแล้ว
											</div>
										</div>
									</div>
								</div>
							</div>
						`,
						width: 640,
						timer: 1200,
						showConfirmButton: false,
						allowOutsideClick: false,
						allowEscapeKey: false,
						customClass: {
							popup: 'pm-swal-enterprise',
							title: 'pm-swal-title',
							htmlContainer: 'pm-swal-html'
						}
					});
					
					// เปิดเป็น dialog ในหน้าวางแผน PM => ปิด dialog (หน้าหลักโหลดปฏิทิน/งานที่ต้องทำใหม่เอง)
					if (WS_EMBED) { wsClose(true, true); return; }

					// พยายามปิดหน้าต่าง
					window.open('', '_self');
					window.close();

					// ถ้าปิดไม่ได้ ให้กลับหน้าก่อนหน้า หรือ redirect ไปหน้ารายการ
					setTimeout(() => {
						if (!window.closed) {
							if (window.history.length > 1) {
								window.history.back();
							} else {
								window.location.href = 'pm_plan.php'; // แก้เป็นหน้ารายการที่ต้องการ
							}
						}
					}, 500);

				} else {
					await Swal.fire({
						icon: 'error',
						title: 'เกิดข้อผิดพลาด',
						text: response.data.error || 'ไม่สามารถบันทึกข้อมูลได้',
						confirmButtonColor: '#dc2626'
					});
				}
		
			} catch (error) {
				console.error('Error saving work record:', error);
				Swal.close();
		
				const serverMessage =
					error?.response?.data?.error ||
					error?.response?.data?.message ||
					error?.message ||
					'ไม่สามารถตรวจสอบหรือบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง';

				await Swal.fire({
					icon: 'error',
					title: 'เกิดข้อผิดพลาด',
					text: serverMessage,
					confirmButtonColor: '#dc2626'
				});
			}
		}

        // ฟังก์ชันบันทึกส่วนที่สอง (Evaluator)
        async function saveEvaluation() {
            
        }

        document.addEventListener('click', function(event) {
            if (!event.target.closest('.custom-select-container')) {
                document.getElementById('inspector_dropdown')?.classList.add('hidden');
                document.getElementById('evaluator_dropdown')?.classList.add('hidden');
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
			initSignaturePads();
		
			if (!isHistoryMode) {
				initEvaluationCalculator();
				loadEvalTopics();
				loadUsers();
			}
		
			if (planId) {
				fetchData();
			} else {
				Swal.fire({
					icon: 'error',
					title: 'ข้อผิดพลาด',
					text: 'ไม่พบรหัสแผนงาน (Plan ID)'
				});
			}
		});
		
		function openPmPrintPage() {
			const params = new URLSearchParams(window.location.search);
			const planId = params.get('plan_id');
			const workRecordId = params.get('work_record_id');
		
			if (!planId && !workRecordId) {
				Swal.fire('ผิดพลาด', 'ไม่พบรหัสแผนงานหรือรหัสประวัติ PM', 'error');
				return;
			}
		
			let url = 'pm_worksheet_print.php?';
		
			if (workRecordId) {
				url += 'work_record_id=' + encodeURIComponent(workRecordId);
			} else {
				url += 'plan_id=' + encodeURIComponent(planId);
			}
		
			window.open(
				url,
				'_blank',
				'width=' + screen.availWidth +
				',height=' + screen.availHeight +
				',left=0,top=0,scrollbars=yes,resizable=yes'
			);
		}
		
		function openPmEventMatrixReport() {
			const params = new URLSearchParams(window.location.search);
			const planId = params.get('plan_id');
			const workRecordId = params.get('work_record_id');
		
			if (!planId && !workRecordId) {
				Swal.fire('ผิดพลาด', 'ไม่พบรหัสแผนงานหรือรหัสประวัติ PM', 'error');
				return;
			}
		
			let url = 'pm_monthly_event_matrix_report.php?';
		
			if (workRecordId) {
				url += 'cols=21&show_pending=1&rows=18&work_record_id=' + encodeURIComponent(workRecordId);
			} else {
				url += 'cols=21&show_pending=1&rows=18&plan_id=' + encodeURIComponent(planId);
				
			}
		
			window.open(
				url,
				'_blank',
				'width=' + screen.availWidth +
				',height=' + screen.availHeight +
				',left=0,top=0,scrollbars=yes,resizable=yes'
			);
		}
		
		function openPmMonthlyReport() {
			const params = new URLSearchParams(window.location.search);
			const planId = params.get('plan_id');
			const workRecordId = params.get('work_record_id');
		
			if (!planId && !workRecordId) {
				Swal.fire('ผิดพลาด', 'ไม่พบรหัสแผนงานหรือรหัสประวัติ PM', 'error');
				return;
			}
		
			let url = 'pm_monthly_report.php?';
		
			if (workRecordId) {
				url += 'work_record_id=' + encodeURIComponent(workRecordId);
			} else {
				url += 'plan_id=' + encodeURIComponent(planId);
			}
		
			window.open(
				url,
				'_blank',
				'width=' + screen.availWidth +
				',height=' + screen.availHeight +
				',left=0,top=0,scrollbars=yes,resizable=yes'
			);
		}

    </script>
	
	<div id="image-carousel-modal" class="fixed inset-0 z-[99999] hidden">
    <div id="image-carousel-backdrop" class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>

    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative w-full max-w-5xl bg-white rounded-[28px] shadow-2xl overflow-hidden">
            <button id="image-carousel-close"
                class="absolute top-4 right-4 z-20 w-10 h-10 rounded-full bg-white/90 hover:bg-white text-slate-500 hover:text-slate-700 shadow-md flex items-center justify-center transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <div class="bg-black flex items-center justify-center min-h-[320px] md:min-h-[520px]">
                <img id="image-carousel-image" src="" alt="preview" class="max-w-full max-h-[75vh] object-contain select-none">
            </div>

            <div class="absolute left-0 right-0 bottom-0 p-4 pointer-events-none">
                <div class="flex items-center justify-center">
                    <div class="pointer-events-auto flex items-center gap-2 bg-slate-900/90 text-white rounded-full px-3 py-2 shadow-xl">
                        <button id="image-carousel-prev"
                            class="w-10 h-10 rounded-full hover:bg-white/10 flex items-center justify-center transition">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <div class="w-px h-6 bg-white/15"></div>

                        <button id="image-carousel-zoom-out"
                            class="w-10 h-10 rounded-full hover:bg-white/10 flex items-center justify-center transition">
                            <i class="fa-solid fa-minus"></i>
                        </button>

                        <button id="image-carousel-zoom-in"
                            class="w-10 h-10 rounded-full hover:bg-white/10 flex items-center justify-center transition">
                            <i class="fa-solid fa-plus"></i>
                        </button>

                        <div class="min-w-[70px] text-center text-sm font-semibold">
                            <span id="image-carousel-counter">1 / 1</span>
                        </div>

                        <button id="image-carousel-reset"
                            class="w-10 h-10 rounded-full hover:bg-white/10 flex items-center justify-center transition">
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>

                        <div class="w-px h-6 bg-white/15"></div>

                        <button id="image-carousel-next"
                            class="w-10 h-10 rounded-full hover:bg-white/10 flex items-center justify-center transition">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="image-carousel-dots" class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-2"></div>
        </div>
    </div>
</div>
</body>
</html>