<?php
//tutorial.php — คู่มือการใช้งานเบื้องต้น (เมนู "คู่มือการใช้งาน" ใน main.php)
// บทเรียนตามเมนูจริง + ภาพหน้าจอจริงพร้อมหมุดตัวเลข (manual/img + ตำแหน่งหมุดใน manual/shots.json)
// แสดงเฉพาะบทของเมนูที่ผู้ใช้มีสิทธิ์ (เงื่อนไขเดียวกับเมนูใน main.php)
// เมื่อหน้าจอของระบบเปลี่ยน ให้จับภาพใหม่ทับไฟล์เดิมใน manual/img และปรับตำแหน่งหมุด (x, y, w, h เป็น % ของภาพ) ใน shots.json
@session_start();
include_once "config_ctrl/checksession.php";
include_once "config_ctrl/connect.php";

$manualPerms = array();
$rsPerm = mysqli_query($connect, "SELECT perm_key FROM tb_agency_role_permission WHERE ag_id = " . (int)$sess_user_agency . " AND can_view = 1");
if ($rsPerm) {
    while ($rowPerm = mysqli_fetch_assoc($rsPerm)) $manualPerms[] = $rowPerm['perm_key'];
}
$manualLevel = isset($_SESSION['sess_user_level_es']) ? $_SESSION['sess_user_level_es'] : '';
$manualShots = @file_get_contents(__DIR__ . '/manual/shots.json');
$manualVer = @filemtime(__DIR__ . '/manual/shots.json');
if (!$manualVer) $manualVer = time();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>คู่มือการใช้งาน</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- ส่งออก PDF: วาดเนื้อหาเป็นภาพ (html2canvas) แล้วรวมเป็นไฟล์ (jsPDF) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --ep: #006b9f; --ep-dark: #04506f; --ep-soft: #e8f4fa; --ink: #1c2b36; --muted: #64748b; --line: #e2e8f0; }
        html, body { height: 100%; }
        body { margin: 0; font-family: "Prompt", "Noto Sans Thai", sans-serif; color: var(--ink); background: #f0f4f8; -webkit-font-smoothing: antialiased; }
        [data-lucide], .lucide { width: 1em; height: 1em; flex: none; }

        /* สารบัญ */
        .toc-item { display: flex; align-items: center; gap: .6rem; width: 100%; text-align: left; padding: .5rem .65rem; border-radius: .6rem; font-size: 13.5px; color: #334155; }
        .toc-item:hover { background: #f1f5f9; }
        .toc-item.active { background: var(--ep-soft); color: var(--ep-dark); font-weight: 600; }
        .toc-dot { width: 1.25rem; height: 1.25rem; border-radius: 999px; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; flex: none; font-size: 11px; color: #fff; }
        .toc-item.done .toc-dot { background: #10b981; border-color: #10b981; }
        .toc-item.active:not(.done) .toc-dot { border-color: var(--ep); }

        /* ภาพหน้าจอ + หมุด */
        .shot { position: relative; border-radius: .9rem; overflow: hidden; border: 1px solid var(--line); background: #fff; box-shadow: 0 10px 30px -12px rgba(15, 23, 42, .25); cursor: zoom-in; }
        .shot img { display: block; width: 100%; height: auto; }
        .shot.mobile { max-width: 340px; margin: 0 auto; border-radius: 1.5rem; border-width: 6px; border-color: #1e293b; }
        .pin { position: absolute; transform: translate(-35%, -35%); width: 26px; height: 26px; border-radius: 999px; background: #f43f5e; color: #fff; font-size: 13px; font-weight: 700;
               display: flex; align-items: center; justify-content: center; box-shadow: 0 0 0 3px #fff, 0 4px 10px rgba(0,0,0,.3); cursor: pointer; transition: transform .15s; z-index: 2; }
        .pin:hover, .pin.active { transform: translate(-35%, -35%) scale(1.25); background: #e11d48; }
        .pin-box { position: absolute; border: 3px solid #f43f5e; border-radius: .5rem; background: rgba(244, 63, 94, .08); box-shadow: 0 0 0 9999px rgba(15, 23, 42, .28); pointer-events: none; opacity: 0; transition: opacity .15s; z-index: 1; }
        .pin-box.show { opacity: 1; }
        @media (max-width: 640px) { .pin { width: 20px; height: 20px; font-size: 11px; box-shadow: 0 0 0 2px #fff, 0 2px 6px rgba(0,0,0,.3); } }

        /* ขั้นตอน */
        .step { display: flex; gap: .75rem; padding: .75rem .85rem; border-radius: .8rem; border: 1px solid transparent; transition: background .15s, border-color .15s; }
        .step:hover, .step.active { background: #fff1f2; border-color: #fecdd3; }
        .step-no { width: 26px; height: 26px; border-radius: 999px; background: #f43f5e; color: #fff; font-size: 13px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex: none; margin-top: 1px; }
        .step-no.plain { background: #e2e8f0; color: #475569; }
        .step b { font-weight: 600; color: #0f172a; }
        .lesson-body p { line-height: 1.75; }
        .kbd { display: inline-flex; align-items: center; gap: .25rem; padding: 0 .4rem; border-radius: .35rem; border: 1px solid #cbd5e1; background: #fff; font-size: 12.5px; font-weight: 600; color: #0f172a; white-space: nowrap; }

        /* ภาพขยาย */
        /* ภาพขยาย: ภาพด้านบน + กล่องคำอธิบายของหมุดที่เลือกด้านล่าง */
        #zoom { position: fixed; inset: 0; z-index: 50; background: rgba(15, 23, 42, .88); display: none; flex-direction: column; align-items: center; justify-content: center; gap: .75rem; padding: 1rem; }
        #zoom.open { display: flex; }
        #zoom .shot { cursor: default; width: auto; flex: none; }
        #zoom .shot img { max-height: calc(100vh - 12rem); width: auto; max-width: calc(100vw - 2rem); }
        #zoom .shot.mobile img { max-height: calc(100vh - 13rem); }
        /* กล่องคำอธิบาย: สูงคงที่ (ข้อความยาวเลื่อนในกล่อง) => ภาพไม่ขยับเวลาเปลี่ยนจุด */
        #zoomCap { width: 100%; max-width: 48rem; flex: none; height: 5.75rem; }
        #zoomCapBody { height: 100%; }
        #zoomCapBody .cap-text { height: 100%; overflow-y: auto; display: flex; flex-direction: column; }
        #zoomCapBody .cap-text > div { margin: auto 0; }   /* สั้น = อยู่กลางแนวตั้ง, ยาว = เริ่มบนสุดและเลื่อนได้ */
        @media (max-width: 640px) {
            #zoomCap { height: 7.5rem; }
            #zoom .shot img, #zoom .shot.mobile img { max-height: calc(100vh - 14rem); }
        }
        #zoomCap .cap-text b { color: #0f172a; font-weight: 600; }

        /* พื้นที่เนื้อหากว้าง: ภาพซ้าย (ค้างไว้ขณะเลื่อนอ่านขั้นตอน) ขั้นตอนขวา => เห็นหมุดบนภาพตลอด */
        .wide .part-grid { display: grid; grid-template-columns: minmax(0, 3fr) minmax(0, 2fr); gap: 1.5rem; align-items: start; }
        .wide .part-grid > .shot { position: sticky; top: 1rem; }
        .wide .part-grid > ol { margin-top: 0; }
        .fade-in { animation: fade .25s ease; }
        @keyframes fade { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

        /* ===== ส่งออก PDF: เนื้อหาถูกวาดใน #printArea (นอกจอ กว้างคงที่ = ความกว้างหน้า A4) แล้วแปลงเป็นภาพ ===== */
        #printArea { position: absolute; left: -12000px; top: 0; width: 760px; padding-bottom: 28px; background: #fff; color: #1c2b36; font-size: 15px; }
        /* html2canvas จัดกึ่งกลางแนวตั้งด้วย flex ได้ไม่ตรง => ตัวเลขในวงกลมใช้ line-height แทน */
        #printArea .pin, #printArea .step-no { display: block !important; text-align: center; padding: 0; }
        #printArea .pin, #printArea .step-no { font-family: Arial, Helvetica, sans-serif; }   /* ตัวเลขล้วน: ฟอนต์ Prompt ทำให้ html2canvas วางตัวเลขต่ำกว่ากึ่งกลาง */
        #printArea .pin { line-height: 22px; }
        #printArea .step-no { line-height: 22px; flex: none; }
        #printArea:empty { display: none; }
        #printArea .pp-part { margin-top: 22px; }
        #printArea .pp-fig { margin: 10px 0; }
        #printArea .pp-fig .shot { box-shadow: none; cursor: default; }
        #printArea .pp-fig.pp-mobile .shot { max-width: 240px; }
        #printArea .pp-fig .pin { width: 22px; height: 22px; font-size: 12px; box-shadow: 0 0 0 2px #fff; transform: translate(-35%, -35%); }
        #printArea .pp-fig .pin-box { display: none; }
        #printArea .pp-steps .step-no { width: 22px; height: 22px; font-size: 12px; }
        #printArea details > summary { list-style: none; }
        #printArea details > summary svg { display: none; }
        #printArea .pp-ref { color: #0369a1; font-size: 13px; font-weight: 500; }
        #pdfModal { position: fixed; inset: 0; z-index: 60; background: rgba(15, 23, 42, .55); display: none; align-items: center; justify-content: center; padding: 1rem; }
        #pdfModal.open { display: flex; }        .scroll-thin::-webkit-scrollbar { width: 6px; } .scroll-thin::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9px; }
    </style>
</head>
<body class="flex flex-col overflow-hidden">

<!-- หัวหน้า -->
<header class="flex-none bg-white border-b border-slate-200 px-4 sm:px-6 py-3 flex items-center gap-3">
    <button id="tocBtn" type="button" class="lg:hidden p-2 -ml-1 rounded-lg text-slate-600 hover:bg-slate-100" title="สารบัญ"><i data-lucide="list" class="w-5 h-5"></i></button>
    <div class="w-10 h-10 rounded-xl bg-[#006b9f] text-white flex items-center justify-center shadow flex-none"><i data-lucide="book-open-check" class="w-5 h-5"></i></div>
    <div class="min-w-0 flex-1">
        <h1 class="text-base sm:text-lg font-bold leading-tight truncate">คู่มือการใช้งาน</h1>
        <p class="text-[11px] sm:text-xs text-slate-500 truncate">เรียนรู้ทีละบท พร้อมภาพหน้าจอจริงของระบบ</p>
    </div>
    <button type="button" id="pdfBtn" title="ดาวน์โหลดคู่มือทั้งหมดเป็น PDF" class="flex-none inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm font-medium text-slate-700 hover:bg-emerald-50 hover:border-emerald-300">
        <i data-lucide="file-down" class="w-4 h-4 text-emerald-700"></i><span class="hidden md:inline">ดาวน์โหลด PDF</span>
    </button>
    <a href="#faq" id="faqBtn" title="คำถามที่พบบ่อย" class="flex-none inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-2 text-sm font-medium text-slate-700 hover:bg-sky-50 hover:border-sky-300">
        <i data-lucide="circle-help" class="w-4 h-4 text-sky-700"></i><span class="hidden md:inline">คำถามที่พบบ่อย</span>
    </a>
    <div class="hidden sm:flex items-center gap-3 flex-none">
        <div class="text-right">
            <div class="text-[11px] text-slate-500">อ่านแล้ว</div>
            <div class="text-sm font-semibold"><span id="progText">0/0</span> บท</div>
        </div>
        <div class="w-32 h-2 rounded-full bg-slate-100 overflow-hidden"><div id="progBar" class="h-full bg-emerald-500 transition-all" style="width:0"></div></div>
    </div>
</header>

<div class="flex-1 min-h-0 flex relative">
    <!-- สารบัญ -->
    <div id="tocBackdrop" class="lg:hidden fixed inset-0 z-30 bg-slate-900/40 hidden"></div>
    <aside id="toc" class="fixed lg:static inset-y-0 left-0 z-40 w-[290px] max-w-[85vw] bg-white border-r border-slate-200 flex flex-col -translate-x-full lg:translate-x-0 transition-transform">
        <div class="p-3 border-b border-slate-100">
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input id="search" type="search" placeholder="ค้นหาในคู่มือ เช่น ปิดงาน, QR, มิเตอร์" class="w-full rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-3 py-2 text-sm outline-none focus:ring-2 focus:ring-sky-500 focus:bg-white">
            </div>
            <div class="sm:hidden mt-2.5 flex items-center gap-2 text-xs text-slate-500">
                อ่านแล้ว <b id="progTextM" class="text-slate-700">0/0</b>
                <div class="flex-1 h-1.5 rounded-full bg-slate-100 overflow-hidden"><div id="progBarM" class="h-full bg-emerald-500" style="width:0"></div></div>
            </div>
        </div>
        <nav id="tocList" class="flex-1 overflow-y-auto scroll-thin p-2"></nav>
    </aside>

    <!-- เนื้อหา -->
    <main id="content" class="flex-1 min-w-0 overflow-y-auto scroll-thin"></main>
</div>

<div id="zoom" role="dialog" aria-label="ภาพขยาย"></div>

<!-- ส่งออก PDF: กล่องแนะนำ + พื้นที่สำหรับพิมพ์ (แสดงเฉพาะตอนพิมพ์) -->
<div id="pdfModal" role="dialog" aria-label="ดาวน์โหลด PDF">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl p-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center flex-none"><i data-lucide="file-down" class="w-5 h-5"></i></div>
            <div class="min-w-0">
                <h2 class="font-bold text-slate-800">ดาวน์โหลดคู่มือเป็น PDF</h2>
                <p id="pdfInfo" class="text-[13px] text-slate-500">รวมทุกบทในไฟล์เดียว</p>
            </div>
        </div>
        <p class="mt-4 text-[14px] text-slate-600">ระบบจะสร้างไฟล์ PDF แล้วดาวน์โหลดลงเครื่องให้อัตโนมัติ (ใช้เวลาประมาณครึ่งนาที)</p>
        <div id="pdfProgress" class="hidden mt-4">
            <div class="flex items-center justify-between text-[13px] text-slate-600 mb-1.5"><span id="pdfStep">กำลังเตรียม...</span><span id="pdfPct">0%</span></div>
            <div class="h-2 rounded-full bg-slate-100 overflow-hidden"><div id="pdfBar" class="h-full bg-emerald-500 transition-all" style="width:0"></div></div>
        </div>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" id="pdfCancel" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100">ยกเลิก</button>
            <button type="button" id="pdfGo" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-sm font-semibold disabled:opacity-60">
                <i data-lucide="download" class="w-4 h-4"></i><span>ดาวน์โหลด PDF</span>
            </button>
        </div>
    </div>
</div>
<div id="printArea"></div>

<script>
const PERMS = <?php echo json_encode($manualPerms); ?>;
const LEVEL = <?php echo json_encode($manualLevel); ?>;
const SHOTS = <?php echo $manualShots ? $manualShots : '{}'; ?>;
const VER = '<?php echo $manualVer; ?>';
const ADMIN = ['admin', 'super_admin'];
const SUPER = ['super_admin'];   // หมวดผู้ดูแลระบบ (ตั้งค่าข้อมูล) เห็นเฉพาะ super admin

// ============================================================
// เนื้อหาคู่มือ: module > lesson > parts (ภาพ + ขั้นตอน) + blocks เพิ่มเติม
// step.m = เลขหมุดบนภาพ (ลำดับใน manual/shots.json) ไม่มี m = ขั้นตอนที่ไม่มีหมุด
// ============================================================
const MODULES = [
{
    id: 'start', title: 'เริ่มต้นใช้งาน', icon: 'rocket',
    lessons: [
    {
        id: 'main', title: 'รู้จักหน้าจอหลัก', perm: null,
        intro: 'หน้าจอของระบบแบ่งเป็น 2 ส่วน คือ <b>เมนูด้านซ้าย</b> สำหรับเลือกงาน และ <b>พื้นที่ทำงานด้านขวา</b> ที่จะเปลี่ยนไปตามเมนูที่เลือก',
        parts: [{ shot: 'main', steps: [
            { m: 1, t: '<b>เมนูหลัก</b> — กดเพื่อเปิดหน้าการทำงาน เมนูที่เห็นจะขึ้นกับสิทธิ์ของแต่ละคน ถ้าไม่เห็นเมนูที่ต้องใช้ ให้ติดต่อผู้ดูแลระบบ (ทุกคนมีเมนู <b>ตั้งค่าการแสดงผล</b> และ <b>คู่มือการใช้งาน</b>)' },
            { m: 2, t: '<b>ย่อ / ขยายเมนู</b> — ย่อเมนูให้เหลือแต่ไอคอน เพื่อให้พื้นที่ทำงานกว้างขึ้น บนมือถือให้กดปุ่ม <span class="kbd">☰</span> มุมซ้ายบนเพื่อเปิดเมนู' },
            { m: 3, t: '<b>หน่วยงาน</b> — ชื่อหน่วยงาน/โครงการที่กำลังใช้งาน ข้อมูลทุกหน้าจะเป็นของหน่วยงานนี้' },
            { m: 4, t: '<b>ชื่อผู้ใช้</b> — ชื่อบัญชีที่เข้าสู่ระบบอยู่' },
            { m: 5, t: '<b>การแจ้งเตือน</b> — ตัวเลขสีแดงคือรายการที่ยังไม่อ่าน (เช่น งาน PM ที่ถึงกำหนด) กดเพื่อดูรายการล่าสุด' },
            { m: 6, t: '<b>ออกจากระบบ</b> — กดทุกครั้งเมื่อใช้เครื่องร่วมกับผู้อื่น' }
        ] }],
        tips: ['ระบบใช้งานได้ทั้งคอมพิวเตอร์ แท็บเล็ต และมือถือ หน้าจอจะปรับตามขนาดให้อัตโนมัติ']
    },
    {
        id: 'qr_login', title: 'เข้าสู่ระบบด้วย QR Code (สแกนด้วยมือถือ)', perm: null,
        intro: 'แจ้งซ่อมได้ทันทีโดยไม่ต้องจำชื่อผู้ใช้และรหัสผ่าน — สแกน QR Code ที่ติดไว้ตามจุดต่างๆ ด้วยกล้องมือถือ ระบบจะเข้าสู่ระบบและเปิดหน้าแจ้งซ่อมให้เลย',
        parts: [
        { title: '1. สแกน QR Code', shot: 'qr_login_card', steps: [
            { t: 'มองหาป้าย <b>ระบบแจ้งซ่อมออนไลน์</b> ที่มี QR Code ติดไว้ (ภาพตัวอย่างถูกเบลอไว้)' },
            { m: 1, t: 'เปิด <b>กล้องมือถือ</b> (หรือเครื่องสแกน QR ในแอป LINE) เล็งที่ QR Code แล้ว <b>แตะลิงก์</b> ที่ขึ้นมาบนจอ' },
            { m: 2, t: 'ระบบเข้าสู่ระบบให้ทันที <b>ไม่ต้องกรอกรหัสผ่าน</b>' }
        ] },
        { title: '2. แจ้งซ่อมได้เลย', shot: 'qr_login_phone', steps: [
            { m: 2, t: 'เปิดหน้า <b>แจ้งซ่อม</b> ให้อัตโนมัติ — กรอกตามบท <a href="#repair" class="text-sky-700 font-semibold underline">แจ้งซ่อม</a> แล้วกด <span class="kbd">ยืนยันแจ้งซ่อม</span>' },
            { m: 1, t: 'เมนู ☰ — การเข้าด้วย QR จะเห็นเฉพาะเมนู <b>แจ้งซ่อม</b>' }
        ] },
        { title: 'สร้าง QR Code สำหรับติดประกาศ (ผู้ดูแลระบบ)', roles: SUPER, shot: 'qr_login_admin', steps: [
            { t: 'ไปที่เมนู <b>ตั้งค่าข้อมูล → ตั้งค่าผู้ใช้งาน</b>' },
            { m: 1, t: 'คอลัมน์ <b>QR Login</b> — แต่ละบัญชีมี QR ของตัวเอง' },
            { m: 2, t: 'กดไอคอน QR ของบัญชีที่ใช้สำหรับแจ้งซ่อม จะเปิดหน้า QR ในแท็บใหม่ — สั่งพิมพ์ (Ctrl+P) แล้วติดไว้ตามจุดที่ต้องการ' }
        ] }],
        tips: ['QR หนึ่งอันผูกกับบัญชีเดียว ใครสแกนก็เข้าระบบในชื่อบัญชีนั้น — สร้าง QR จากบัญชีที่ตั้งไว้สำหรับแจ้งซ่อมเท่านั้น ห้ามใช้บัญชีผู้ดูแลระบบหรือบัญชีช่าง', 'ใส่ชื่อและเบอร์โทรของคุณในฟอร์มทุกครั้ง เพื่อให้ช่างติดต่อกลับได้ เพราะชื่อบัญชีเป็นชื่อกลาง']
    },
    {
        id: 'display_settings', title: 'ปรับขนาดตัวอักษร', perm: null, href: 'display_settings.php',
        intro: 'ตัวอักษรเล็กหรือใหญ่เกินไป ปรับได้ที่เมนู <b>ตั้งค่าการแสดงผล</b> — มีผลทันทีกับทุกหน้าในโปรแกรม ทั้งเมนูด้านซ้าย แถบด้านบน และใบงาน PM',
        parts: [{ shot: 'display_settings', steps: [
            { m: 1, t: 'เปิดเมนู <b>ตั้งค่าการแสดงผล</b> ที่แถบด้านซ้าย (ทุกคนใช้ได้)' },
            { m: 2, t: '<b>แถบเลื่อน</b> — ปรับได้ตั้งแต่ 80% ถึง 150% (ทีละ 5%) ระหว่างเลื่อนดูผลได้ที่กล่องตัวอย่าง <b>ปล่อยแล้วทั้งโปรแกรมขยายตามทันที</b> ไม่ต้องบันทึก — ปุ่ม <span class="kbd">ก-</span> <span class="kbd">ก+</span> ปรับทีละ 5%' },
            { m: 3, t: '<b>ขนาดที่ใช้บ่อย</b> — กดเลือกเร็ว: เล็ก 90% / ปกติ 100% / ใหญ่ 115% / ใหญ่มาก 130%' },
            { m: 5, t: '<b>ตัวอย่าง</b> — ดูความอ่านง่ายของข้อความก่อนใช้งานจริง' },
            { m: 4, t: '<span class="kbd">กลับเป็นค่าเริ่มต้น</span> — กลับไปขนาดปกติ 100%' }
        ] }],
        tips: ['ค่าที่เลือกจำไว้ใน<b>เบราว์เซอร์ของเครื่องนั้น</b> — เปิดครั้งหน้ายังเป็นขนาดเดิม แต่ถ้าใช้เครื่องอื่นต้องเลือกใหม่', 'ขนาดใหญ่จะเห็นข้อมูลต่อหน้าจอน้อยลง ตารางบางหน้าอาจต้องเลื่อนมากขึ้น']
    },
    {
        id: 'dashboard', title: 'หน้าหลัก (Dashboard)', perm: null, href: 'dashboard.php',
        intro: 'สรุปภาพรวมงานแจ้งซ่อมตามช่วงเวลาและสถานที่ที่เลือก เหมาะสำหรับดูสถานการณ์อย่างรวดเร็ว',
        parts: [{ shot: 'dashboard', steps: [
            { m: 1, t: '<b>ช่วงเวลา</b> — เลือกดูเป็นรายวัน รายเดือน หรือรายปี' },
            { m: 2, t: '<b>ช่วงวันที่</b> — กำหนดวันเริ่มต้นและวันสิ้นสุดของข้อมูล' },
            { m: 3, t: '<b>สถานที่</b> — เลือกอาคาร/สาขา หรือดูทั้งหมด' },
            { m: 4, t: 'กด <span class="kbd">ค้นหา</span> เพื่อแสดงข้อมูลตามตัวเลือก' },
            { m: 5, t: '<b>แถบเตือนงานค้าง</b> (สีส้ม) — ขึ้นเมื่อมีงานที่แจ้ง<b>ก่อนช่วงวันที่ที่เลือก</b>แต่ยังไม่ปิด เช่น แจ้ง 31 ม.ค. แต่วันนี้ดูเดือน ก.พ. บอกจำนวนและวันที่ของงานที่ค้างนานที่สุด กด <span class="kbd">ดูรายการ</span> เพื่อเปิดงานค้างทั้งหมด' },
            { m: 6, t: '<b>การ์ดสรุป</b> — งานทั้งหมด สำเร็จแล้ว และยกเลิก นับตามช่วงวันที่ที่เลือก ส่วน <b>รอดำเนินการ / กำลังดำเนินการ</b> นับงานที่ยังไม่ปิด<b>ทั้งหมด</b> ไม่ว่าจะแจ้งเมื่อไหร่ — กดการ์ดเพื่อเปิดรายการ' },
            { m: 7, t: '<b>กราฟ</b> — แนวโน้มงานและงานแยกตามอาคาร กด <span class="kbd">เต็มจอ</span> เพื่อดูกราฟขนาดใหญ่' }
        ] }],
        tips: ['ขึ้นเดือนใหม่แล้วงานเดือนก่อนที่ยังไม่ปิดจะ<b>ไม่หายไป</b> — ยังนับอยู่ในการ์ดรอดำเนินการ / กำลังดำเนินการ และแสดงในแถบเตือนสีส้มจนกว่าจะปิดงาน']
    },
    {
        id: 'notifications', title: 'การแจ้งเตือน', perm: null, href: 'notifications.php',
        intro: 'ระบบแจ้งเตือนเมื่อมีเรื่องที่ต้องดำเนินการ เช่น มีการแจ้งซ่อมใหม่ ปิดงานซ่อม มิเตอร์ใช้เกินเกณฑ์ อะไหล่ในสต็อกใกล้หมด และงาน PM ที่ถึงกำหนดวันนี้หรือแจ้งล่วงหน้า — ดูได้จาก <b>ไอคอนกระดิ่ง</b> มุมขวาบนของทุกหน้า',
        parts: [
        { title: '1. ดูการแจ้งเตือนล่าสุดจากกระดิ่ง', shot: 'notif_bell', steps: [
            { m: 1, t: '<b>ไอคอนกระดิ่ง</b> มุมขวาบน — ตัวเลขสีแดงคือจำนวนที่ยังไม่อ่าน (99+ = มากกว่า 99 รายการ) กดเพื่อเปิดรายการล่าสุด' },
            { m: 2, t: '<b>รายการแจ้งเตือน</b> — จุดสีฟ้า = ยังไม่อ่าน <b>กดที่รายการ</b> เพื่อไปหน้าที่เกี่ยวข้องทันที และระบบจะถือว่าอ่านแล้ว' },
            { m: 3, t: 'กด <b>ดูการแจ้งเตือนทั้งหมด</b> (ล่างสุดของรายการ) เพื่อเปิดหน้ารวมการแจ้งเตือน' }
        ] },
        { title: '2. หน้าการแจ้งเตือนทั้งหมด', shot: 'notifications', steps: [
            { t: 'รายการเรียงตามวัน (วันนี้ / วันที่ก่อนหน้า) เลื่อนลงเพื่อโหลดรายการเก่าเพิ่ม' },
            { t: 'กด <span class="kbd">อ่านทั้งหมด</span> เพื่อทำเครื่องหมายว่าอ่านแล้วทุกรายการ ตัวเลขบนกระดิ่งจะหายไป' }
        ] }],
        table: { title: 'กดการแจ้งเตือนแล้วไปที่ไหน', rows: [
            ['แจ้งซ่อมใหม่ / ปิดงานซ่อม', 'เมนู <b>ข้อมูลแจ้งซ่อม</b>'],
            ['แจ้งเตือน PM (วันนี้ / ล่วงหน้า)', 'เมนู <b>วางแผน PM</b>'],
            ['มิเตอร์ใช้เกินเกณฑ์', 'เมนู <b>ข้อมูลมิเตอร์</b>'],
            ['อะไหล่ใกล้หมด', 'เมนู <b>จัดการสต็อก</b>']
        ] }
    },
    ]
},
{
    id: 'repair', title: 'งานแจ้งซ่อม', icon: 'wrench',
    lessons: [
    {
        id: 'repair', title: 'แจ้งซ่อม', perm: 'repair_request', href: 'maintenance_request.php',
        intro: 'กรอกแบบฟอร์มแจ้งซ่อมเมื่อพบปัญหา ยิ่งระบุตำแหน่งและแนบรูปชัดเจน ช่างก็แก้ไขได้เร็วขึ้น ช่องที่มีเครื่องหมาย <b class="text-rose-500">*</b> ต้องกรอก',
        parts: [{ shot: 'repair', steps: [
            { m: 1, t: '<b>วัน/เวลาที่แจ้ง</b> — ระบบใส่วันเวลาปัจจุบันให้อัตโนมัติ' },
            { m: 2, t: '<b>ชื่อผู้แจ้ง</b> — ชื่อ ฝ่าย หรือสำนักงาน เพื่อให้ช่างติดต่อกลับได้' },
            { m: 3, t: '<b>เบอร์โทรศัพท์</b> — ถ้าไม่มีเบอร์ ให้ติ๊ก <b>ไม่มีเบอร์โทร</b>' },
            { m: 4, t: '<b>ตำแหน่งที่แจ้งซ่อม</b> — กดแล้วเลือก อาคาร/สาขา → ชั้น → ห้อง' },
            { m: 5, t: '<b>ค้นหาเครื่อง/ทรัพย์สิน</b> — ถ้าเป็นปัญหาของเครื่องจักร พิมพ์รหัส ชื่อ หรือห้อง หรือกด <span class="kbd">Browse</span> เพื่อเลือกจากรายการ' },
            { m: 6, t: '<b>ความเร่งด่วน</b> — ต่ำ / ปานกลาง / สูง / เร่งด่วนมาก' },
            { m: 7, t: '<b>อาการเสีย/ปัญหาที่พบ</b> — อธิบายสิ่งที่เห็น เช่น "แอร์เปิดไม่ติด มีน้ำหยด"' },
            { m: 8, t: '<b>รูปภาพ</b> — แนบได้สูงสุด 5 รูป (JPG / PNG / WEBP) กด ADD PHOTO หรือลากรูปมาวาง' },
            { m: 9, t: '<b>วิดีโอ</b> — แนบได้ 1 คลิป ไม่เกิน 30 วินาที ถ้าคลิปยาวกว่านั้น ระบบจะเปิดหน้า<b>ตัดวิดีโอ</b>แบบเต็มจอ ลากที่จับสองข้างเพื่อเลือกช่วง แล้วกด <span class="kbd">ตัดและใช้วิดีโอนี้</span>' },
            { m: 10, t: 'ตรวจข้อมูลแล้วกด <span class="kbd">ยืนยันแจ้งซ่อม</span>' }
        ] }],
        tips: ['ถ่ายรูปให้เห็นทั้งจุดที่เสียและป้าย/รหัสเครื่อง จะช่วยให้ช่างเตรียมอุปกรณ์ได้ถูก', 'กด <b>ล้างข้อมูล</b> หากต้องการเริ่มกรอกใหม่']
    },
    {
        id: 'qr', title: 'แจ้งซ่อมด้วย QR Code ที่ตัวเครื่อง', perm: 'repair_request',
        intro: 'เครื่องจักรที่ติด QR Code ของระบบ สามารถสแกนด้วยกล้องมือถือเพื่อดูข้อมูลเครื่องและแจ้งซ่อมได้ทันที โดยไม่ต้องค้นหาเครื่องเอง',
        parts: [{ shot: 'qr', steps: [
            { t: 'เปิดกล้องมือถือ แล้วสแกน QR Code ที่ติดอยู่บนเครื่อง จะเปิดหน้า <b>ประวัติเครื่องจักร</b> (รหัส ชื่อ ยี่ห้อ สถานที่ติดตั้ง วันหมดประกัน)' },
            { m: 2, t: '<b>ประวัติการแจ้งซ่อม</b> — ดูก่อนว่ามีคนแจ้งปัญหานี้ไปแล้วหรือยัง' },
            { m: 1, t: 'กด <span class="kbd">แจ้งซ่อมเครื่องจักรนี้</span> ระบบจะเปิดฟอร์มแจ้งซ่อมพร้อมเลือกเครื่องและสถานที่ให้อัตโนมัติ' }
        ] }]
    },
    {
        id: 'repair_list', title: 'ติดตามรายการแจ้งซ่อม', perm: 'repair_data', href: 'maintenance_info.php',
        intro: 'ดูรายการแจ้งซ่อมทั้งหมด สถานะของแต่ละงาน และจัดการใบงาน',
        parts: [{ shot: 'repair_list', steps: [
            { m: 1, t: '<b>ค้นหา</b> — พิมพ์ชื่อผู้แจ้ง อาการ อาคาร สถานะ หรือรหัสครุภัณฑ์' },
            { m: 2, t: '<b>รูป/วิดีโอ</b> — กดเพื่อดูไฟล์ที่แนบมาแบบเต็มจอ ตัวเลขที่มุมคือจำนวนไฟล์ ไอคอน ▶ คือมีวิดีโอ (วิธีดูรูปอยู่ในเคล็ดลับด้านล่าง)' },
            { m: 3, t: '<b>สถานะ</b> — แถบสีบอกความคืบหน้าของงาน (ดูความหมายด้านล่าง)' },
            { m: 4, t: '<b>แก้ไขใบงาน</b> — เปิดรายละเอียด สำหรับรับงาน/ปิดงาน (ดูบทถัดไป)' },
            { m: 5, t: '<b>พิมพ์ใบงาน</b>' },
            { m: 6, t: '<b>แบบประเมิน</b> — ส่งหรือดูผลการประเมินความพึงพอใจ ปุ่มสีเขียวคือประเมินแล้ว' },
            { m: 7, t: '<b>ยกเลิกใบงาน</b> — มีเฉพาะงานที่ยังไม่ปิด ระบบจะถามยืนยันก่อน' },
            { m: 8, t: '<b>เปลี่ยนหน้า</b> และเลือกจำนวนแถวต่อหน้า' }
        ] }],
        table: { title: 'ความหมายของสถานะ', rows: [
            ['<span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500 mr-1.5"></span>รอดำเนินการ', 'แจ้งเข้ามาแล้ว ยังไม่มีช่างรับงาน'],
            ['<span class="inline-block w-2.5 h-2.5 rounded-full bg-sky-500 mr-1.5"></span>กำลังดำเนินการ', 'ช่างรับงานแล้ว กำลังแก้ไข'],
            ['<span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 mr-1.5"></span>เสร็จสิ้น', 'ช่างปิดงานแล้ว'],
            ['<span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 mr-1.5"></span>ประเมินแล้ว', 'ผู้แจ้งประเมินความพึงพอใจแล้ว'],
            ['<span class="inline-block w-2.5 h-2.5 rounded-full bg-rose-500 mr-1.5"></span>ยกเลิก', 'ใบงานถูกยกเลิก']
        ] },
        tips: ['กดปุ่มกรอง ▾ บนหัวคอลัมน์ เพื่อกรองเฉพาะอาคาร ชั้น หรือสถานะที่ต้องการ', 'บนมือถือ เลือกชิป <b>งานค้าง</b> ด้านบนรายการ เพื่อดูเฉพาะงานที่รอดำเนินการและกำลังดำเนินการ', '<b>ดูรูปแบบเต็มจอ</b> — เลื่อนรูป: ปัดซ้าย/ขวา ลากด้วยเมาส์ ปุ่ม ‹ › หรือปุ่มลูกศรบนคีย์บอร์ด | ซูม: ถ่างสองนิ้ว แตะสองครั้ง ดับเบิลคลิก หรือหมุนล้อเมาส์ | ปิด: ปุ่ม ✕ หรือ Esc', 'กดปุ่ม <span class="kbd">ส่งออก Excel</span> เหนือตาราง เพื่อดาวน์โหลดรายการเป็นไฟล์ Excel (บนมือถือเป็นไอคอนสีเขียว)']
    },
    {
        id: 'repair_job', title: 'รับงานและปิดงานซ่อม (สำหรับช่าง)', perm: 'repair_data', href: 'maintenance_info.php',
        intro: 'ในหน้า <b>ข้อมูลแจ้งซ่อม</b> กดปุ่ม <b>แก้ไขใบงาน</b> ของงานที่ต้องการ จะเปิดหน้ารายละเอียดด้านขวา ฝั่งซ้ายคือข้อมูลที่แจ้ง ฝั่งขวาคือส่วนที่ช่างบันทึก',
        parts: [{ shot: 'repair_job', steps: [
            { m: 1, t: '<b>เลขที่เอกสาร</b> ของใบงานที่กำลังเปิด' },
            { m: 2, t: '<b>สถานที่และทรัพย์สิน</b> — ตรวจสอบตำแหน่งและเครื่อง ถ้าไม่ถูกต้อง แก้ได้ที่ <b>แก้ไขตำแหน่ง</b> หรือปุ่มเปลี่ยนเครื่อง ⇄' },
            { m: 3, t: '<b>ขั้นที่ 1 รับงาน / จ่ายงาน</b> — ใส่วันที่และเวลาเข้าทำ แล้วเลือกช่างผู้รับผิดชอบ' },
            { m: 4, t: '<b>ขั้นที่ 2 ปิดงาน / รายงานผล</b> — ใส่วันเวลาที่เสร็จ ชนิดบริการ ประเภทงาน บันทึกผลการดำเนินงาน แนบรูปปิดงาน (สูงสุด 5 รูป) และเพิ่มอะไหล่/วัสดุที่ใช้จากสต็อก' },
            { m: 5, t: 'กด <span class="kbd">บันทึกข้อมูล (Save)</span> ทุกครั้งหลังกรอก' }
        ] }],
        tips: ['รับงานก่อนแล้วกดบันทึกได้เลย จากนั้นกลับมากรอกส่วนปิดงานเมื่อซ่อมเสร็จ', 'ถ่ายรูปหลังซ่อมเสร็จจากมุมเดียวกับรูปที่แจ้ง เพื่อให้เปรียบเทียบผลได้ง่าย']
    },
    {
        id: 'repair_eval', title: 'ส่งแบบประเมินความพึงพอใจ', perm: 'repair_data', href: 'maintenance_info.php',
        intro: 'หลังปิดงาน ส่งแบบประเมินทางอีเมลให้ผู้เกี่ยวข้องประเมินงานซ่อม — กดปุ่ม <b>ซองจดหมาย</b> ในคอลัมน์จัดการของหน้าข้อมูลแจ้งซ่อม',
        parts: [{ shot: 'repair_eval', steps: [
            { m: 1, t: '<b>ข้อมูลใบงาน</b> และสถานะการประเมิน — ส่งได้เมื่อใบงานมีสถานะ <b>เสร็จสิ้น</b> แล้วเท่านั้น' },
            { m: 2, t: '<b>รายชื่อผู้รับอีเมล</b> — เลือกผู้รับ หรือ <b>เลือกทั้งหมด</b> แต่ละคนแสดงสถานะ ยังไม่ส่ง / ส่งแล้ว / เปิดลิงก์แล้ว' },
            { m: 3, t: '<b>Mail Logs</b> — ประวัติการส่งล่าสุด' },
            { m: 4, t: 'กด <span class="kbd">ส่งให้ผู้ที่เลือก</span> — หรือ <b>เลือกที่ยังไม่ส่ง/ล้มเหลว</b> เพื่อส่งซ้ำ และ <b>ตรวจสอบสถานะ</b> เพื่ออัปเดตผล' }
        ] }],
        tips: ['ปุ่มเปลี่ยนเป็น <b>สีเขียว</b> เมื่อได้รับผลประเมินแล้ว กดเพื่อดูผลหรือส่งรอบใหม่']
    },
    {
        id: 'work_history', title: 'ประวัติงานช่าง', perm: 'work_history', href: 'work_history.php',
        intro: 'ติดตามงานของช่างแต่ละคนในรูปแบบปฏิทิน บอร์ดสถานะ และไทม์ไลน์ พร้อมตัวเลขสรุปประสิทธิภาพ',
        parts: [{ shot: 'work_history', steps: [
            { m: 1, t: '<b>มุมมอง</b> — ปฏิทินงาน / บอร์ดสถานะงาน / ไทม์ไลน์รายวัน / ไทม์ไลน์ทีม' },
            { m: 2, t: '<b>ตัวกรอง</b> — ค้นหาเลขที่งานหรือชื่อ (บนมือถือกดแถบ "ตัวกรอง" เพื่อเปิด)' },
            { m: 3, t: '<b>ช่าง</b> — เลือกดูเฉพาะช่างคนใดคนหนึ่ง หรือทั้งหมด' },
            { m: 4, t: '<b>ช่วงเวลา</b> — รายวัน / รายเดือน / รายปี และกำหนดวันเริ่มต้น-สิ้นสุดได้' },
            { m: 5, t: '<b>สถานะงาน</b> — จำนวนงานแต่ละสถานะตามตัวกรอง' },
            { m: 6, t: 'กด <span class="kbd">วันนี้</span> เพื่อกลับมาดูงานของวันนี้' }
        ] },
        { title: 'บอร์ดสถานะงาน', shot: 'wh_kanban', steps: [
            { m: 1, t: 'ใบงานแบ่งเป็น 4 คอลัมน์ตามสถานะ: <b>รอ/เตรียมการ</b> → <b>กำลังดำเนินการ</b> → <b>เสร็จสิ้น</b> และ <b>ยกเลิก</b> ตัวเลขบนหัวคอลัมน์คือจำนวนงาน' }
        ] },
        { title: 'ไทม์ไลน์ (รายวัน)', shot: 'wh_daily', steps: [
            { m: 1, t: 'แต่ละแถวคือ 1 วัน แนวนอนคือเวลา 24 ชั่วโมง แถบสีคือใบงาน (สีตามสถานะ) — เห็นว่างานเข้าช่วงเวลาไหนบ่อย' },
            { t: 'ปุ่มด้านบนขวา: ซูม − / + / รีเซ็ต, <span class="kbd">Export Excel</span> และขยายเต็มจอ' }
        ] },
        { title: 'ไทม์ไลน์ (ทีม)', shot: 'wh_team', steps: [
            { m: 1, t: 'แต่ละแถวคือช่าง 1 คน แนวนอนคือวันที่ — เห็นว่าใครมีงานซ้อน ใครว่าง แถว <b>ยังไม่มอบหมายช่าง</b> คืองานที่ยังไม่มีคนรับ' }
        ] }]
    }]
},
{
    id: 'pm', title: 'งานบำรุงรักษา (PM)', icon: 'notebook-pen',
    lessons: [
    {
        id: 'pm_calendar', title: 'ปฏิทินงาน PM', perm: 'pm_plan', href: 'pm.php', tab: 'dashboard',
        intro: 'เมนู <b>วางแผน PM</b> แท็บ <b>ปฏิทิน</b> แสดงงานบำรุงรักษาตามแผนทั้งหมด <span class="text-[#006B9F] font-semibold">สีน้ำเงิน</span> = รอดำเนินการ <span class="text-emerald-600 font-semibold">สีเขียว</span> = ทำแล้ว',
        parts: [{ shot: 'pm_calendar', steps: [
            { m: 1, t: '<b>ตัวกรองขั้นสูง</b> — เลือกเครื่องจักร เช็คชีต ประเภท หรือสถานที่ กด « เพื่อซ่อนแผงนี้' },
            { m: 2, t: 'กด <span class="kbd">กรองข้อมูล</span> เพื่อแสดงตามตัวกรอง (<b>ล้างค่า</b> เพื่อดูทั้งหมด)' },
            { m: 3, t: '<b>เลื่อนเดือน</b> ‹ › และกลับมา <b>วันนี้</b>' },
            { m: 4, t: '<b>มุมมอง</b> — รายปี / รายเดือน / รายวัน / กำหนดการ (แบบรายการ)' },
            { m: 5, t: '<b>กดที่งาน</b> เพื่อดูรายละเอียดและเปิดใบงาน (บทถัดไป) — ถ้าวันนั้นมีงานเช็คชีตเดียวกันที่ยังไม่ทำหลายเครื่อง ระบบจะถามก่อนว่าจะ <b>บันทึกพร้อมกันหลายเครื่อง</b> หรือ <b>รายเครื่อง</b>' },
            { t: 'ช่องที่มีงานมากจะแสดงปุ่ม <span class="kbd">+N</span> (กรอบสีฟ้า) — กดเพื่อดูงานทั้งวันแยกตามเช็คชีต' }
        ] }]
    },
    {
        id: 'pm_popup', title: 'ดูรายละเอียดงานก่อนเปิดใบงาน', perm: 'pm_plan', href: 'pm.php', tab: 'dashboard',
        intro: 'เมื่อกดงานในปฏิทิน (หรือเลือก <b>รายเครื่อง</b>) จะเห็นข้อมูลสรุปที่ช่างควรรู้ก่อนออกไปหน้างาน',
        parts: [{ shot: 'pm_popup', steps: [
            { m: 1, t: '<b>สถานะ</b> — รอดำเนินการ / เลยกำหนดกี่วัน / ครบกำหนดวันนี้ / เคยถูกเลื่อน' },
            { m: 2, t: '<b>ข้อมูลหลัก</b> — วันที่กำหนด ความถี่ เวลาที่ใช้โดยประมาณ และวันที่ทำครั้งล่าสุด' },
            { m: 3, t: '<b>สิ่งที่ต้องเตรียม</b> — จำนวนจุดตรวจ จุดที่ต้องถ่ายรูป และอะไหล่/วัสดุ' },
            { m: 4, t: '<b>คู่มือ / ไฟล์แนบ</b> — กดที่ชื่อไฟล์เพื่อดาวน์โหลด' },
            { m: 5, t: 'กด <span class="kbd">เปิดใบงาน</span> เพื่อบันทึกผล (งานที่ทำแล้วจะเป็นปุ่ม <b>ดูประวัติ</b>)' },
            { m: 6, t: '<span class="kbd">ย้อนกลับ</span> — กลับไปหน้าก่อนหน้า (ป๊อปอัปเลือกบันทึกพร้อมกัน/รายเครื่อง หรือรายการงานของวัน)' }
        ] }]
    },
    {
        id: 'pm_batch', title: 'บันทึกพร้อมกันหลายเครื่อง', perm: 'pm_plan', href: 'pm.php', tab: 'dashboard',
        intro: 'บางวันมีงานเช็คชีตเดียวกันหลายสิบถึงหลายร้อยเครื่อง (เช่น ไฟฉุกเฉิน) ถ้าผลตรวจ<b>ปกติ</b>เหมือนกัน บันทึกได้ในครั้งเดียว ระบบจะสร้างใบงานแยกให้ทุกเครื่อง — เลขเอกสาร ประวัติ และรายงาน เป็นรายเครื่องเหมือนบันทึกทีละใบ',
        parts: [
        { title: '1. เลือกจากงานในปฏิทิน', shot: 'pm_chooser', steps: [
            { t: 'กดที่งานในปฏิทิน — ถ้าวันนั้นมีงานเช็คชีตเดียวกันที่ยังไม่ทำมากกว่า 1 เครื่อง จะมีป๊อปอัปให้เลือก' },
            { m: 1, t: '<b>บันทึกพร้อมกันหลายเครื่อง</b> — เปิดหน้าบันทึกพร้อมกันของเช็คชีตนี้ในวันนั้น (ข้อ 3)' },
            { m: 2, t: '<b>รายเครื่อง</b> — ดูรายละเอียดและเปิดใบงานของเครื่องที่กด มีปุ่ม <b>ย้อนกลับ</b> มาที่ป๊อปอัปนี้' }
        ] },
        { title: '2. หรือดูงานทั้งวันจากปุ่ม +N', shot: 'pm_daylist', steps: [
            { t: 'ในปฏิทินรายเดือน / รายปี กดปุ่ม <span class="kbd">+N</span> ในช่องวัน เพื่อดูงานทั้งวันแยกตามเช็คชีต' },
            { m: 1, t: '<b>สรุปทั้งวัน</b> — จำนวนงานทั้งหมด ยังไม่ทำ และทำแล้ว' },
            { m: 2, t: '<span class="kbd">บันทึกพร้อมกัน N เครื่อง</span> — มีเมื่อเช็คชีตนั้นยังไม่ทำตั้งแต่ 2 เครื่องขึ้นไป' },
            { m: 3, t: '<b>รายเครื่อง</b> — กางรายชื่อเครื่อง จุดสีน้ำเงิน = ยังไม่ทำ สีเขียว = ทำแล้ว กดชื่อเพื่อดูรายละเอียด' },
            { m: 4, t: 'เช็คชีตที่มีงานเดียว กด <span class="kbd">เปิดใบงาน</span> ได้ทันที (ทำแล้วจะเป็น <b>ดูประวัติ</b>)' }
        ] },
        { title: '3. หน้าบันทึกพร้อมกัน', shot: 'pm_batch', steps: [
            { m: 1, t: '<b>ผลการตรวจ</b> — เลือกครั้งเดียว ใช้กับทุกเครื่องที่เลือก ได้เฉพาะ <b>ปกติ</b> หรือ <b>ไม่เกี่ยวข้อง</b>' },
            { m: 2, t: '<b>เครื่องที่จะบันทึก</b> — ค้นหา / เลือกทั้งหมด / ไม่เลือก เครื่องที่<b>ผิดปกติ</b>หรือมีการ<b>ใช้อะไหล่</b> ให้เอาเครื่องหมาย ✓ ออก' },
            { m: 3, t: '<b>รูป / ค่าที่วัดได้ รายเครื่อง</b> — มีเมื่อเช็คชีตบังคับถ่ายรูปหรือต้องกรอกค่า กดไอคอนกล้องเพื่อถ่ายหรือเลือกรูปของเครื่องนั้น' },
            { m: 4, t: '<span class="kbd">เปิดใบงาน</span> — บันทึกเครื่องนั้นทีละใบ (สำหรับเครื่องที่ผิดปกติ)' },
            { m: 5, t: '<b>ผู้ตรวจสอบ หมายเหตุ และลายมือชื่อ</b> — ทำครั้งเดียว ใช้กับทุกใบ' },
            { m: 6, t: 'กด <span class="kbd">บันทึก N เครื่อง</span> — ระบบบันทึกทีละชุดพร้อมแถบความคืบหน้า แล้วสรุปผลรายเครื่อง เครื่องที่ไม่สำเร็จจะยังอยู่ในรายการ แก้แล้วกดบันทึกอีกครั้งได้' }
        ] }],
        tips: ['หน้านี้ไม่บันทึกอะไหล่ที่ใช้จริง (ไม่ตัดสต็อก) — เครื่องที่ใช้อะไหล่ให้บันทึกทีละใบ', 'บันทึกเสร็จแล้ว ปฏิทินจะเปลี่ยนงานเป็นสีเขียวให้อัตโนมัติ', 'มีหลายร้อยเครื่อง หน้าแสดงทีละ 100 เครื่อง กด <b>แสดงเพิ่ม</b> ด้านล่างรายการ (การเลือกทั้งหมดรวมเครื่องที่ยังไม่แสดงด้วย)']
    },
    {
        id: 'pm_worksheet', title: 'บันทึกใบงาน PM', perm: 'pm_plan',
        intro: 'ใบงาน PM แบ่งเป็น 4 ขั้นตอน กด <b>ถัดไป</b> / <b>ย้อนกลับ</b> ที่แถบด้านล่าง หรือกดที่แถบขั้นตอนด้านบนเพื่อข้ามไปขั้นที่ต้องการ',
        parts: [
        { title: 'ขั้นที่ 1 ข้อมูลงาน', shot: 'pm_worksheet', steps: [
            { m: 1, t: '<b>แถบขั้นตอน</b> — ข้อมูลงาน → ตรวจสอบ → อะไหล่ → สรุป & ลงชื่อ' },
            { m: 2, t: '<b>ข้อมูลการปฏิบัติงาน</b> — ตรวจว่าเครื่องและสถานที่ถูกต้อง ด้านล่างมีภาพจุดตรวจและไฟล์คำแนะนำในการทำงาน' },
            { m: 3, t: 'กด <span class="kbd">ถัดไป</span> เพื่อไปขั้นตรวจสอบ' }
        ] },
        { title: 'ขั้นที่ 2 ตรวจสอบ', shot: 'pm_worksheet_check', steps: [
            { m: 1, t: '<b>รายการจุดตรวจสอบ</b> — แถบด้านบนบอกว่าตรวจแล้วกี่ข้อ กรองดูเฉพาะ <b>ยังไม่ตรวจ</b> หรือ <b>ผิดปกติ</b> ได้' },
            { m: 2, t: 'เลือกผล <span class="kbd">✓ ปกติ</span> <span class="kbd">✕ ผิดปกติ</span> หรือ <span class="kbd">N/A</span> กรอกค่าที่วัดได้ (ถ้ามี) — กดที่แถวเพื่อดูมาตรฐานและวิธีตรวจด้านขวา' },
            { m: 3, t: '<b>ป้ายถ่ายรูป</b> — <span class="text-rose-600 font-semibold">บังคับถ่ายรูป</span> ต้องแนบรูปทุกครั้ง, <span class="text-amber-600 font-semibold">ถ่ายรูปเมื่อผิดปกติ</span> ต้องแนบเมื่อเลือกผิดปกติ' }
        ] },
        { title: 'ขั้นที่ 3 อะไหล่', shot: 'pm_worksheet_spares', steps: [
            { m: 1, t: '<b>ขั้นอะไหล่</b> — ถ้าไม่ได้ใช้อะไหล่ในงานนี้ กด <b>ถัดไป</b> ข้ามได้เลย' },
            { m: 2, t: '<b>รายการอะไหล่/วัสดุที่ใช้จริง</b> — กด <b>⊕ เพิ่มรายการ</b> เลือกจากสต็อกแล้วใส่จำนวนที่ใช้ เพิ่มได้หลายรายการ ระบบรวมค่าวัสดุให้' },
            { m: 3, t: 'กด <span class="kbd">ถัดไป</span> เพื่อไปขั้นสรุป' }
        ] },
        { title: 'ขั้นที่ 4 สรุป & ลงชื่อ', shot: 'pm_worksheet_sign', steps: [
            { m: 1, t: '<b>สรุปผลการตรวจ</b> — จำนวนข้อที่ตรวจแล้ว ปกติ ผิดปกติ N/A ถ้ายังมีข้อที่ไม่ได้ตรวจ จะแสดงรายการไว้ กดที่ข้อนั้นเพื่อกลับไปตรวจ' },
            { m: 2, t: '<b>ชื่อผู้ตรวจสอบ</b> — คลิกแล้วเลือกชื่อจากรายการ' },
            { m: 3, t: '<b>หมายเหตุ / ข้อเสนอแนะ</b> (ถ้ามี)' },
            { m: 4, t: '<b>ลายมือชื่อผู้ตรวจสอบ</b> — เซ็นด้วยนิ้วหรือเมาส์ในกรอบ กด <b>ล้าง</b> เพื่อเซ็นใหม่' },
            { m: 5, t: 'กด <span class="kbd">บันทึกผลการปฏิบัติงาน</span> — ระบบตรวจว่ากรอกครบก่อนบันทึก' }
        ] }]
    },
    {
        id: 'pm_postpone', title: 'เลื่อนแผน PM', perm: 'pm_plan', href: 'pm.php', tab: 'postpone',
        intro: 'ใช้เมื่อทำงานตามวันที่กำหนดไม่ได้ เช่น เครื่องไม่พร้อม หรือพื้นที่ไม่ว่าง เลื่อนได้หลายรายการพร้อมกัน',
        parts: [{ shot: 'pm_postpone', steps: [
            { m: 1, t: '<b>ตัวกรอง</b> — เลือกเดือน/ปีของวันที่แผนเดิม แล้วกรองตามเครื่องจักร เช็คชีต ประเภท หรือสถานที่' },
            { m: 2, t: 'กด <span class="kbd">กรองข้อมูล</span>' },
            { m: 3, t: '<b>วันที่เลื่อนใหม่</b> — ดับเบิลคลิกช่องของงานที่จะเลื่อน แล้วเลือกวันใหม่' },
            { m: 4, t: '<b>เหตุผลที่เลื่อน</b> — ต้องระบุทุกรายการที่เลื่อน' },
            { m: 5, t: 'กด <span class="kbd">บันทึกการเลื่อนทั้งหมด</span> และยืนยัน' }
        ] }],
        tips: ['ดูประวัติการเลื่อนของแต่ละงานได้ที่คอลัมน์ <b>ประวัติเลื่อน</b> ทางขวาสุด']
    },
    {
        id: 'pm_plan', title: 'จัดการแผน PM', perm: 'pm_plan', href: 'pm.php', tab: 'plan',
        intro: 'แผน PM คือการผูก <b>เครื่องจักร + เช็คชีต + ความถี่</b> เข้าด้วยกัน ระบบจะสร้างกำหนดการในปฏิทินให้อัตโนมัติจนถึงวันสิ้นสุดสัญญา',
        parts: [
        { title: 'รายการแผน PM', shot: 'pm_plan', steps: [
            { m: 1, t: '<span class="kbd">คำนวณแผนใหม่</span> — สร้างกำหนดการล่วงหน้าทั้งหมดใหม่จนถึงวันสิ้นสุดสัญญา (ใช้หลังแก้วันหยุดหรือแก้แผนหลายรายการ)' },
            { m: 2, t: '<span class="kbd">เพิ่มแผน PM</span> — สร้างแผนใหม่ (ดูขั้นตอนด้านล่าง)' },
            { m: 3, t: '<b>ความถี่</b> ของแต่ละแผน เช่น ทุกวัน / ทุก 7 วัน / รายเดือน' },
            { m: 4, t: '<b>เริ่มทำ</b> — วันที่เริ่มแผน' },
            { m: 5, t: '<b>จัดการ</b> — 📄 เปิดรายงานรายเดือน/รายวัน ✎ แก้ไขแผน (ความถี่ การแจ้งเตือน ระบุวัน วันเริ่ม) 🗑 ลบแผน' }
        ] },
        { title: 'สร้างแผน PM ใหม่ — ขั้นที่ 1 เลือกข้อมูลพื้นฐาน', shot: 'pm_plan_add', steps: [
            { m: 1, t: '<b>แถบขั้นตอน</b> — เลือกข้อมูลพื้นฐาน → ตั้งค่าเช็คชีต → กำหนดวันเริ่มทำ' },
            { m: 4, t: 'เลือก <b>ประเภทเครื่องจักร</b> — ระบบแสดงเช็คชีตของประเภทนั้น' },
            { m: 5, t: 'ติ๊ก <b>เช็คชีต</b> ที่จะใช้ (เลือกได้มากกว่า 1) แล้วกด <span class="kbd">ถัดไป</span>' }
        ] },
        { title: 'ขั้นที่ 2 ตั้งค่าเช็คชีต', shot: 'pm_plan_add2', steps: [
            { m: 1, t: 'แต่ละแถวคือเช็คชีตที่เลือกไว้ในขั้นที่ 1' },
            { m: 2, t: '<b>ความถี่</b> — เช่น ทุกวัน / ทุก 7 วัน / ทุก 30 วัน / ระบุวันในสัปดาห์ / ระบุวันที่ของทุกเดือน' },
            { m: 3, t: '<b>การแจ้งเตือน</b> — เวลาที่ระบบแจ้งเตือนก่อนถึงงาน (ตัวเลือกขึ้นกับความถี่)' },
            { t: '<b>ระบุวัน</b> — ติ๊กวันที่ต้องทำ เฉพาะความถี่ที่ต้องระบุวัน (เช่น 2 ครั้งต่อสัปดาห์) ความถี่อื่นจะขึ้นว่า "ไม่ต้องระบุวัน"' },
            { m: 4, t: 'กด <span class="kbd">ถัดไป</span> (ย้อนกลับไปแก้ขั้นที่ 1 ได้)' }
        ] },
        { title: 'ขั้นที่ 3 กำหนดวันเริ่มทำ', shot: 'pm_plan_add3', steps: [
            { m: 1, t: 'แสดงเครื่องจักรทุกเครื่องของประเภทที่เลือก' },
            { m: 2, t: 'คอลัมน์สีฟ้าทางขวาคือเช็คชีตแต่ละชุด — ใส่ <b>วันเริ่มทำ</b> ของแต่ละเครื่อง (พิมพ์ YYYY-MM-DD หรือดับเบิลคลิกเพื่อเปิดปฏิทิน) เครื่องที่ไม่ใส่วันจะไม่ถูกสร้างแผน' },
            { m: 3, t: '<span class="kbd">ขยายเต็มจอ</span> เมื่อมีเครื่องจำนวนมาก' },
            { t: 'กด <span class="kbd">บันทึกแผน PM</span> (ปุ่มสีเขียวท้ายหน้า) ระบบจะสร้างกำหนดการในปฏิทินจนถึงวันสิ้นสุดสัญญา' }
        ] }],
        tips: ['ต้องมีเช็คชีตก่อนจึงจะสร้างแผนได้ — สร้างที่แท็บ <b>สร้างเช็คชีต</b>', 'ตั้งวันหยุดในแท็บ <b>จัดการวันหยุด</b> ก่อนสร้างแผน ระบบจะเลื่อนงานที่ตรงวันหยุดให้อัตโนมัติ']
    },
    {
        id: 'pm_checksheet', title: 'สร้างเช็คชีต', perm: 'pm_plan', href: 'pm.php', tab: 'checksheet',
        intro: 'เช็คชีตคือแบบฟอร์มตรวจสอบ กำหนดว่าต้องตรวจจุดไหน มาตรฐานเท่าไร และต้องถ่ายรูปหรือไม่ ใช้ซ้ำได้กับเครื่องประเภทเดียวกันทุกเครื่อง ช่องที่มี <b class="text-rose-500">*</b> ต้องกรอก',
        parts: [
        { title: 'ข้อมูลเช็คชีต', shot: 'pm_checksheet', steps: [
            { m: 1, t: '<b>ชื่อเช็คชีต</b> เช่น "ตรวจเช็คแอร์รายเดือน"' },
            { m: 2, t: '<b>ประเภทเครื่องจักร</b> ที่ใช้เช็คชีตนี้ พร้อมวันที่มีผล เลขที่เอกสาร และ Revision No.' },
            { m: 3, t: '<b>เวลาที่คาดว่าจะเสร็จ</b> (นาที) — แสดงให้ช่างเห็นก่อนออกไปหน้างาน' },
            { m: 4, t: '<b>รายการอะไหล่ที่ต้องใช้</b> — ชื่ออะไหล่และจำนวน กด <b>+ เพิ่มแถวอะไหล่</b> เพื่อเพิ่ม' },
            { m: 5, t: '<b>ภาพจุดตรวจสอบ</b> — แนบได้สูงสุด 5 รูป' },
            { m: 6, t: '<b>คำแนะนำในการทำงาน</b> — แนบคู่มือได้สูงสุด 5 ไฟล์ ไม่เกิน 5MB ต่อไฟล์ ช่างดาวน์โหลดได้จากปฏิทิน PM' },
            { m: 7, t: '<b>จัดการแถวเช็คชีต</b> — ใส่จำนวนแล้วกด <b>เพิ่มแถวท้ายตาราง</b> เพื่อเพิ่มจุดตรวจ' },
            { m: 8, t: 'กรอกครบแล้วกด <span class="kbd">บันทึก Template</span> (<b>ล้างข้อมูล</b> เพื่อเริ่มใหม่)' }
        ] },
        { title: 'จุดตรวจสอบ และเช็คชีตที่บันทึกไว้', shot: 'pm_checksheet_rows', steps: [
            { m: 1, t: 'แต่ละแถวคือ 1 จุดตรวจ — กรอก <b>จุดที่ตรวจสอบ</b> มาตรฐาน วิธีตรวจ/เครื่องมือ และข้อปฏิบัติเมื่อผิดปกติ' },
            { m: 2, t: '<b>ประเภทจุดตรวจสอบ</b> — ผ่าน / ไม่ผ่าน หรือ ผ่าน / ไม่ผ่าน / ไม่เกี่ยวข้อง (มีตัวเลือก N/A)' },
            { m: 3, t: '<b>บังคับถ่ายรูป</b> — บังคับ / ไม่บังคับ / บังคับเฉพาะกรณีที่ไม่ผ่าน' },
            { m: 4, t: 'ถ้าต้องวัดค่า ใส่ <b>ชื่อค่าวัด</b> หน่วย และ <b>ค่าที่คาดหวัง</b>' },
            { m: 5, t: '<b>รายการเช็คชีตที่บันทึกไว้</b> — ปุ่มด้านขวา: คัดลอก (ทำเช็คชีตใหม่จากของเดิม) / แก้ไข / ลบ' }
        ] }]
    },
    {
        id: 'pm_holiday', title: 'จัดการวันหยุด', perm: 'pm_plan', href: 'pm.php', tab: 'holiday',
        intro: 'กำหนดวันที่ไม่ทำ PM และเลือกว่าถ้างานตรงวันหยุดจะให้ทำอย่างไร ระบบจะตรวจเงื่อนไขนี้ก่อนลงตาราง PM',
        parts: [{ shot: 'pm_holiday', steps: [
            { m: 1, t: '<b>รูปแบบการหยุด</b> — ครั้งเดียว / ทุกปี / ทุกๆวัน (หยุดประจำสัปดาห์ เช่น ทุกวันอาทิตย์)' },
            { m: 2, t: '<b>ระบุวันที่</b> (หรือเลือกวันในสัปดาห์ ถ้าเป็นแบบทุกๆวัน)' },
            { m: 3, t: '<b>การจัดการเมื่อตรงวันหยุด</b> — เลื่อนไป 1 วันทำการถัดไป / เลื่อนมาทำก่อน 1 วันทำการ / หยุดทำ / ไม่หยุดทำ' },
            { m: 4, t: '<b>ชื่อวันหยุด</b> เช่น วันแรงงาน วันปิดปรับปรุง' },
            { m: 5, t: '<b>เปิดใช้งาน</b> — ปิดไว้ได้โดยไม่ต้องลบ' },
            { m: 6, t: 'กด <span class="kbd">เพิ่มรายการ</span>' },
            { m: 7, t: '<b>รายการวันหยุด</b> — แก้ไข ลบ หรือเปิด/ปิดด้วยสวิตช์ในตาราง (กด « เพื่อซ่อนแผงด้านซ้าย)' }
        ] }],
        tips: ['แก้วันหยุดแล้ว ให้กด <b>คำนวณแผนใหม่</b> ในแท็บจัดการแผน PM เพื่อให้กำหนดการที่สร้างไว้แล้วปรับตาม']
    },
    {
        id: 'pm_import', title: 'นำเข้าแผน PM จาก Excel', perm: 'pm_plan', href: 'pm.php', tab: 'import',
        intro: 'สร้างเช็คชีตและแผน PM ทีละมากๆ จากไฟล์ Excel ตารางแผน PM รายปี (Yearly PM Schedule)',
        parts: [{ title: 'ขั้นที่ 1 อ่านไฟล์ และสร้างเช็คชีต', shot: 'pm_import', steps: [
            { m: 1, t: '<span class="kbd">ดาวน์โหลดไฟล์ Template สำหรับ Import</span> — กรอกตามรูปแบบนี้ (Name / CODE / Location + เดือนละ 4 สัปดาห์)' },
            { m: 2, t: 'เลือก <b>ไฟล์ Excel (.xlsx)</b>' },
            { m: 3, t: 'เลือก <b>ชีต</b> ที่มีตาราง' },
            { m: 4, t: '<b>ปีของตาราง</b> (ค.ศ.) — ระบบอ่านจากไฟล์ให้ แก้ได้ถ้าไม่ถูก' },
            { m: 5, t: '<b>สรุปผลการอ่านไฟล์</b> — จำนวนเครื่องในไฟล์ พร้อมนำเข้า ไม่พบในระบบ เช็คชีตใหม่/เดิม — ตารางด้านล่างกรองตามสถานะได้ แนบภาพจุดตรวจและคำแนะนำให้เช็คชีตใหม่ได้' },
            { t: 'กด <span class="kbd">สร้างเช็คชีต และไปกำหนดแผน PM</span> (ท้ายหน้า)' }
        ] },
        { title: 'ขั้นที่ 2 กำหนดแผน PM (ภาพข้อมูลตัวอย่าง)', shot: 'pm_import_step2', steps: [
            { m: 1, t: '<b>คำแนะนำ</b> — ความถี่และวันที่เริ่มตั้งค่าเริ่มต้นจาก Excel ให้แล้ว (เช่น ห่าง 4 สัปดาห์ = ทุก 30 วัน) ตรวจสอบและกรอกการแจ้งเตือนเพิ่ม' },
            { m: 2, t: '<b>ตาราง</b> — แถวละ 1 เครื่อง + เช็คชีต ช่อง P คือสัปดาห์ที่มีกำหนดใน Excel เลื่อนไปทางขวาเพื่อกรอก <b>ความถี่ แจ้งเตือน ระบุวัน</b> (เช่น จ., พฤ. หรือ 1, 15) และ <b>วันที่เริ่ม</b> ลากมุมเซลล์เพื่อคัดลอกลงแถวถัดไปได้' },
            { m: 3, t: '<span class="kbd">ขยายเต็มจอ</span> เพื่อดูตารางได้กว้างขึ้น' },
            { m: 4, t: 'ด้านซ้ายบอกจำนวนแถวที่พร้อมบันทึก แล้วกด <span class="kbd">บันทึกแผน PM</span> — บันทึกเฉพาะแถวที่กรอกครบ แถวที่เหลือยังอยู่ในตารางให้แก้ต่อ' }
        ] }],
        blocks: [
            { icon: 'circle-alert', title: 'เครื่องที่ "ไม่พบในระบบ"', text: 'รหัสใน Excel ไม่ตรงกับทะเบียนเครื่องจักร — ลงทะเบียนเครื่องหรือแก้รหัสในไฟล์ก่อน แล้วนำเข้าใหม่' }
        ]
    },
    {
        id: 'pm_schedule', title: 'ตารางแผน PM (รายปี / รายเดือน / รายสัปดาห์)', perm: 'pm_plan', href: 'pm.php', tab: 'schedule',
        intro: 'ดูแผน PM ทุกเครื่องในตารางเดียว (ดูอย่างเดียว) เลือกได้ 3 มุมมอง: <b>รายปี</b> เห็นภาพรวมทั้งปี, <b>รายเดือน</b> และ <b>รายสัปดาห์</b> เห็นทีละวันว่างานตรงกับวันไหน',
        parts: [
        { title: 'มุมมองรายปี', shot: 'pm_schedule', steps: [
            { m: 1, t: '<b>เลือกมุมมอง</b> — รายปี / รายเดือน / รายสัปดาห์' },
            { m: 2, t: '<b>เลือกปี</b> ตามช่วงสัญญา' },
            { m: 3, t: '<b>สรุป</b> — จำนวนแผน PM และจำนวนแผนที่มีงานเลยกำหนด' },
            { m: 4, t: '<span class="kbd">Export Excel</span> — ส่งออกตามมุมมอง ลำดับ และตัวกรองที่แสดงอยู่ (รายปีเป็นรูปแบบเดียวกับ Template นำเข้า)' },
            { m: 5, t: '<span class="kbd">ขยายเต็มจอ</span> เพื่อดูตารางได้กว้างขึ้น' },
            { m: 6, t: '<b>ตาราง</b> — เดือนละ 4 ช่อง (วันที่ 1-7, 8-14, 15-21, 22 ถึงสิ้นเดือน) ● รอดำเนินการ ✓ ทำแล้ว ! เลยกำหนด <b>ตัวเลข = จำนวนครั้งในช่องนั้น</b> สีพื้นเหลือง = มีงานเลยกำหนด กรอบสีแดงคือสัปดาห์นี้' }
        ] },
        { title: 'มุมมองรายเดือน / รายสัปดาห์ (ช่องละ 1 วัน)', shot: 'pm_schedule_month', steps: [
            { m: 1, t: '<b>รายเดือน</b> แสดงวันที่ 1 ถึงสิ้นเดือน / <b>รายสัปดาห์</b> แสดงวันจันทร์ถึงอาทิตย์' },
            { m: 2, t: 'กด <span class="kbd">‹</span> <span class="kbd">›</span> เพื่อเลื่อนเดือน/สัปดาห์ หรือกด <span class="kbd">วันนี้</span> เพื่อกลับมาช่วงปัจจุบัน' },
            { m: 3, t: '<b>เฉพาะแผนที่มีงานในช่วงนี้</b> — ซ่อนแผนที่ไม่มีงานในเดือน/สัปดาห์ที่ดู เอาติ๊กออกเพื่อดูทุกแผน' },
            { m: 4, t: '<b>สรุป</b> — จำนวนแผน จำนวนงานในช่วงนี้ และแผนที่มีงานเลยกำหนด' },
            { m: 5, t: '<b>หัวคอลัมน์</b> — วันที่และวันในสัปดาห์ ช่องสีเทาอ่อน = เสาร์-อาทิตย์ กรอบสีแดงคือวันนี้' }
        ] }],
        tips: ['ต้องการแก้แผน ให้ไปที่แท็บ <b>จัดการแผน PM</b>', 'ดูรายเดือนเมื่ออยากรู้ว่าวันไหนมีงานบ้าง ดูรายปีเมื่ออยากเห็นภาพรวมทั้งปี']
    }]
},
{
    id: 'asset', title: 'ข้อมูลอาคารและทรัพย์สิน', icon: 'building-2',
    lessons: [
    {
        id: 'meter', title: 'บันทึกค่ามิเตอร์', perm: 'meter_data', href: 'meter_info.php',
        intro: 'จดค่ามิเตอร์น้ำ ไฟ TOU และแก๊ส ระบบจะคำนวณการใช้ และเตือนเมื่อใช้เกินเกณฑ์',
        parts: [{ shot: 'meter', steps: [
            { m: 1, t: '<b>ประเภทมิเตอร์</b> — มิเตอร์น้ำ / ไฟ / TOU / แก๊ส' },
            { m: 2, t: '<b>ค้นหาและเลือกมิเตอร์</b> จากรายการด้านซ้าย' },
            { m: 3, t: 'เลือกเดือน/ปีที่ต้องการดู กด <span class="kbd">ปัจจุบัน</span> เพื่อกลับมาเดือนนี้' },
            { m: 4, t: 'กด <span class="kbd">เพิ่มบันทึกมิเตอร์</span> เพื่อจดค่าใหม่ (ปุ่มเปลี่ยนชื่อตามประเภทที่เลือก)' },
            { m: 5, t: '<b>สีของแถว</b> — แดง = ใช้เกินเกณฑ์ เขียว = ประหยัดลงจากรอบที่แล้ว กด <b>ดูสูตรคำนวณ</b> เพื่อดูวิธีคิด' }
        ] },
        { title: 'เพิ่มบันทึกมิเตอร์', shot: 'meter_add', steps: [
            { m: 1, t: 'กดปุ่มเพิ่มบันทึก จะเปิดแผงด้านขวาของมิเตอร์ที่เลือกอยู่' },
            { m: 2, t: 'ใช้ค่าที่อ่านได้จาก <b>หน้าปัดมิเตอร์จริง</b> ทุกครั้ง' },
            { m: 3, t: '<b>วันที่บันทึก</b> แล้วเลือก <b>ช่วงเวลาบันทึก</b> (รอบที่ต้องจด)' },
            { t: '<b>เลขมิเตอร์ล่าสุด</b> — ตัวเลขบนหน้าปัด' },
            { t: '<b>ยืนยันมิเตอร์วนรอบ</b> — ติ๊กเฉพาะเมื่อเลขน้อยกว่ารอบก่อน เพราะหน้าปัดวิ่งครบรอบแล้ว' },
            { t: '<b>รูปภาพ</b> หน้าปัด (PNG, JPG ไม่เกิน 5MB) และหมายเหตุ' },
            { m: 5, t: 'กด <span class="kbd">บันทึกข้อมูล</span>' }
        ] }],
        tips: ['รอบเวลาที่ต้องจดและค่าที่ยอมรับได้ ตั้งโดยผู้ดูแลระบบในเมนู <b>ตั้งค่าข้อมูล → บันทึกมิเตอร์ น้ำ/ไฟ</b>']
    },
    {
        id: 'machine', title: 'ข้อมูลอุปกรณ์เครื่องจักร', perm: 'machine_data', href: 'machine_info.php',
        intro: 'ทะเบียนเครื่องจักรและอุปกรณ์ทั้งหมด พร้อม QR Code สำหรับติดที่เครื่อง',
        parts: [{ shot: 'machine', steps: [
            { m: 1, t: '<b>ค้นหา</b> — รหัสครุภัณฑ์ ชื่อ หรือซีเรียล' },
            { m: 2, t: '<span class="kbd">พิมพ์ QR Code</span> — พิมพ์สติกเกอร์ QR ของหลายเครื่องพร้อมกัน' },
            { m: 3, t: '<span class="kbd">ลงทะเบียนเครื่องจักร</span> — เพิ่มเครื่องใหม่' },
            { m: 4, t: '<b>สถานะ</b> — ปกติ / แจ้งซ่อม / ชำรุด / สำรอง' },
            { m: 5, t: '<b>QR Code</b> ของเครื่องนั้น' },
            { m: 6, t: '<b>ประวัติการแจ้งซ่อม</b> ของเครื่อง' },
            { m: 7, t: '<b>แก้ไข</b> ข้อมูลเครื่อง' }
        ] },
        { title: 'พิมพ์ QR Code ติดเครื่อง', shot: 'machine_print', steps: [
            { t: 'ค้นหา/กรองตารางเครื่องจักรก่อน แล้วกด <span class="kbd">พิมพ์ QR Code</span> — หน้าพิมพ์จะเปิดในแท็บใหม่ เฉพาะเครื่องที่แสดงอยู่ในตาราง' },
            { m: 1, t: '<b>ขนาดป้าย</b> — กว้าง × สูง (cm) ให้ตรงกับสติกเกอร์ที่ใช้' },
            { m: 2, t: '<b>ขนาด QR Code</b> — เลื่อนเพื่อปรับ (ตัวเลขด้านขวาคือขนาดจริง)' },
            { m: 3, t: '<span class="kbd">สลับเป็นแนวตั้ง</span> / แนวนอน' },
            { m: 4, t: '<b>ตัวอย่างป้าย</b> — ชื่อ รหัส S/N หน่วยงาน และสถานที่ แล้วกด <span class="kbd">พิมพ์</span>' }
        ] },
        { title: 'ลงทะเบียนเครื่องจักร', shot: 'machine_add', steps: [
            { m: 2, t: '<b>รหัสครุภัณฑ์</b> — ระบบสร้าง QR Code จากรหัสนี้ให้ทันที (ดูตัวอย่างด้านซ้าย)' },
            { m: 3, t: '<b>ประเภทเครื่องจักร</b> แล้วกรอกชื่อ ซีเรียล ยี่ห้อ รุ่น บริษัทที่ดูแล' },
            { m: 4, t: '<b>วันหมดรับประกัน</b> และ <b>ตำแหน่งติดตั้ง</b> (อาคาร → ชั้น → ห้อง)' },
            { m: 5, t: '<b>สถานะการใช้งาน</b> — ปกติ / แจ้งซ่อม / ชำรุด / สำรอง' },
            { m: 1, t: '<b>รูปภาพเครื่องจักร</b> สูงสุด 3 รูป แล้วกด <span class="kbd">บันทึกข้อมูล</span>' }
        ] }]
    },
    {
        id: 'stock', title: 'จัดการสต็อก', perm: 'stock_manage', href: 'stock.php',
        intro: 'จัดการรายการอะไหล่และวัสดุในแต่ละคลัง อะไหล่ที่ช่างใช้ตอนปิดงานจะเลือกจากสต็อกนี้',
        parts: [{ shot: 'stock', steps: [
            { m: 1, t: '<b>แสดงรายการสินค้าทั้งหมด</b> จากทุกคลัง' },
            { m: 2, t: '<b>คลังสินค้า / ตึก</b> — เลือกคลังเพื่อดูเฉพาะสินค้าในคลังนั้น (ตัวเลข = จำนวนรายการ)' },
            { m: 3, t: '<b>รายงาน</b> — รายงานรับเข้า (PO) และรายงานปรับยอด (AD)' },
            { m: 4, t: '<b>ค้นหา</b> รหัสหรือชื่อสินค้า' },
            { m: 5, t: '<span class="kbd">เพิ่มสินค้าใหม่</span>' }
        ] }],
        tips: ['ปุ่มสวิตช์ในคอลัมน์สถานะใช้เปิด/ปิดการใช้งานสินค้า ไอคอนนาฬิกาคือประวัติการเคลื่อนไหว (รับเข้า / เบิกใช้ / ปรับยอด)']
    },
    {
        id: 'stock_move', title: 'รับเข้าและปรับยอดสต็อก', perm: 'stock_manage', href: 'stock.php',
        intro: 'เพิ่มหรือแก้จำนวนคงเหลือผ่าน <b>รับเข้า</b> หรือ <b>ปรับยอด</b> เสมอ เพื่อให้มีประวัติตรวจสอบย้อนหลังได้ (การแก้ข้อมูลสินค้าจะไม่เปลี่ยนจำนวนคงเหลือ)',
        parts: [
        { title: 'เลือกสินค้า', shot: 'stock_select', steps: [
            { m: 1, t: 'ติ๊ก <b>วงกลมหน้าสินค้า</b> ที่ต้องการ เลือกได้หลายรายการ' },
            { m: 2, t: '<span class="kbd">รับเข้า</span> — เมื่อมีของเข้าคลัง (จำนวนเพิ่มขึ้น)' },
            { m: 3, t: '<span class="kbd">ปรับยอด</span> — เมื่อนับสต็อกแล้วจำนวนไม่ตรงกับระบบ' }
        ] },
        { title: 'ฟอร์มรับสินค้าเข้า', shot: 'stock_receive', steps: [
            { m: 1, t: '<b>เลขที่ใบรับเข้า</b> ระบบออกให้อัตโนมัติ เลือกวันที่รับ แล้วใส่ <b>จำนวน</b> ของแต่ละรายการ' },
            { m: 2, t: '<b>หมายเหตุ</b> (เช่น เลขที่ใบสั่งซื้อ) แล้วกด <span class="kbd">บันทึกข้อมูล</span>' }
        ] },
        { title: 'รายงาน', shot: 'stock_po', steps: [
            { m: 1, t: '<b>รายงานรับเข้า (PO)</b> — ประวัติใบรับเข้าทั้งหมด' },
            { m: 2, t: '<b>รายงานปรับยอด (AD)</b> — ประวัติการปรับยอด' },
            { m: 3, t: 'กดแถบ <b>รายการสินค้า</b> เพื่อดูว่าในใบนั้นมีสินค้าอะไร จำนวนเท่าไร' }
        ] }]
    }]
},
{
    id: 'admin', title: 'ผู้ดูแลระบบ', icon: 'shield-check',
    lessons: [
    {
        id: 'settings', title: 'วิธีใช้หน้าตั้งค่า และผู้ใช้งาน', roles: SUPER, href: 'settings.php',
        intro: 'เมนู <b>ตั้งค่าข้อมูล</b> แบ่งหัวข้อเป็น 6 หมวด เลือกจากเมนูด้านบน ทุกหน้าที่เป็นตารางใช้งานเหมือนกัน — บทนี้ใช้หน้า <b>ตั้งค่าผู้ใช้งาน</b> เป็นตัวอย่าง',
        parts: [{ shot: 'settings', steps: [
            { m: 1, t: '<b>เลือกหัวข้อ</b> — ตั้งค่าข้อมูลพื้นฐาน / อุปกรณ์เครื่องจักร / แจ้งซ่อม / บันทึกมิเตอร์ / อะไหล่และวัสดุ / การประเมินผล PM' },
            { m: 2, t: '<b>ค้นหา</b> ข้อมูลในตาราง' },
            { m: 3, t: '<span class="kbd">เพิ่มแถว</span> — ใส่จำนวนแถวที่ต้องการเพิ่มในช่องด้านซ้าย' },
            { m: 4, t: 'แก้ไขเสร็จแล้วกด <span class="kbd">บันทึกข้อมูล</span> — ถ้าไม่กด การแก้ไขจะไม่ถูกบันทึก' },
            { m: 5, t: '<b>ดับเบิลคลิกที่ช่อง</b> เพื่อแก้ไข ช่องสีเหลือง = มีการแก้ไข สีเขียว = รายการใหม่' }
        ] }],
        table: { title: 'ตั้งค่าผู้ใช้งาน', rows: [
            ['ระดับผู้ใช้', 'admin = ผู้ดูแลระบบ (เห็นเมนูตั้งค่าและบทเรียน/แท็บสำหรับผู้ดูแล) ระดับอื่นเป็นผู้ใช้ทั่วไป เห็นเมนูตามสิทธิ์ของหน่วยงาน'],
            ['ตำแหน่ง', 'เช่น ช่าง ผู้ว่าจ้าง เจ้าหน้าที่ธุรการ'],
            ['สถานะ', 'เอาติ๊ก Active ออกเพื่อปิดบัญชีโดยไม่ต้องลบ'],
            ['QR Login', 'QR สำหรับเข้าสู่ระบบด้วยมือถือ'],
            ['จัดการ (🔑)', 'ตั้ง/เปลี่ยนรหัสผ่าน']
        ] },
        tips: ['Export ตารางเป็น Excel ได้จากปุ่ม <b>Export</b> ทุกหน้า']
    },
    {
        id: 'settings_agency', title: 'ข้อมูลพื้นที่หน่วยงาน (อาคาร ชั้น ห้อง)', roles: SUPER, href: 'settings.php',
        intro: 'หมวด <b>ตั้งค่าข้อมูลพื้นฐาน → ข้อมูลพื้นที่หน่วยงาน</b> — อาคาร ชั้น และห้องที่ตั้งไว้ที่นี่ จะเป็นตัวเลือกตำแหน่งในหน้าแจ้งซ่อม ทะเบียนเครื่องจักร และมิเตอร์',
        parts: [{ shot: 'settings_agency', steps: [
            { m: 1, t: '<b>รายชื่ออาคาร</b> — กด ⊕ เพื่อเพิ่มอาคารใหม่ กดไอคอน Excel เพื่อ Export ข้อมูลทุกอาคาร' },
            { m: 2, t: '<b>ค้นหาอาคาร</b> แล้วกดเลือกอาคารที่จะแก้ไข' },
            { m: 3, t: '<b>ชั้น และห้อง/โซน</b> ของอาคารที่เลือก — ปิดใช้งานชั้นหรือห้องได้ด้วยช่อง Active' },
            { m: 4, t: '<span class="kbd">เพิ่มแถว</span> เพื่อเพิ่มชั้น/ห้อง' },
            { m: 5, t: 'กด <span class="kbd">บันทึกทั้งหมด</span>' }
        ] }]
    },
    {
        id: 'settings_equip', title: 'ตั้งค่าอุปกรณ์เครื่องจักร', roles: SUPER, href: 'settings.php',
        intro: 'หมวด <b>อุปกรณ์เครื่องจักร</b> — ประเภทอุปกรณ์ใช้จัดกลุ่มเครื่องและผูกกับเช็คชีต PM',
        parts: [
        { title: 'ประเภทอุปกรณ์', shot: 'settings_equip_types', steps: [
            { m: 3, t: 'เพิ่ม/แก้ชื่อประเภท เช่น เครื่องปรับอากาศ ปั๊มน้ำ แล้วกด <span class="kbd">บันทึกข้อมูล</span>' }
        ] },
        { title: 'รายการอุปกรณ์', shot: 'settings_equip_list', steps: [
            { m: 1, t: 'ค้นหาเครื่อง แก้ไขหลายเครื่องพร้อมกันแบบตาราง (รหัส ประเภท ชื่อ ซีเรียล ยี่ห้อ รุ่น วันหมดประกัน บริษัทที่ดูแล)' },
            { m: 3, t: '<span class="kbd">Export</span> เป็น Excel' },
            { m: 4, t: '<span class="kbd">บันทึกข้อมูล</span>' }
        ] }],
        tips: ['ลงทะเบียนทีละเครื่องพร้อมรูปและตำแหน่งได้ที่เมนู <b>ข้อมูลอุปกรณ์เครื่องจักร</b>']
    },
    {
        id: 'settings_repair', title: 'ตั้งค่างานแจ้งซ่อม', roles: SUPER, href: 'settings.php',
        intro: 'หมวด <b>แจ้งซ่อม</b> — ตัวเลือกที่ช่างใช้ตอนปิดงาน ระยะเวลา SLA และการแจ้งเตือนทางอีเมล',
        parts: [
        { title: 'ประเภทงาน', shot: 'settings_job_type', steps: [
            { m: 3, t: 'รหัสและชื่อประเภทงาน เช่น AIR ระบบปรับอากาศ ELC ระบบไฟฟ้า — เอาติ๊ก Active ออกเพื่อซ่อนจากตัวเลือก' }
        ] },
        { title: 'ชนิดการให้บริการ', shot: 'settings_system_types', steps: [
            { m: 3, t: 'ตัวเลือก <b>ชนิดของการบริการ</b> ที่ช่างเลือกตอนปิดงาน' }
        ] },
        { title: 'กำหนดระยะเวลา SLA', shot: 'settings_job_sla', steps: [
            { m: 1, t: '<b>ระยะเวลาตอบรับ</b> — นับจากแจ้งเรื่องถึงรับงาน' },
            { m: 2, t: '<b>ระยะเวลาแก้ไข</b> — นับจากรับงานถึงปิดงาน (พิมพ์ เช่น 1 hrs, 45 mins)' },
            { m: 3, t: '<span class="kbd">บันทึกข้อมูล</span>' }
        ] },
        { title: 'ตั้งค่าเพิ่มเติม', shot: 'settings_job_other', steps: [
            { m: 1, t: '<b>ตั้งค่าหน้าแจ้งซ่อม</b> — เช่น เปิด/ปิดหัวข้อระดับความเร่งด่วน (ปิดแล้วใช้ "ปานกลาง" เป็นค่าเริ่มต้น)' },
            { m: 2, t: '<b>อีเมลแจ้งเตือนแจ้งซ่อม</b> — รายชื่ออีเมลที่จะได้รับแจ้งเมื่อมีการแจ้งซ่อมใหม่' },
            { m: 3, t: '<b>อีเมลแจ้งเตือนประเมินงานซ่อม</b> — รายชื่อผู้รับแบบประเมิน' },
            { m: 4, t: '<span class="kbd">บันทึกการตั้งค่า</span>' }
        ] }]
    },
    {
        id: 'settings_meter', title: 'ตั้งค่ามิเตอร์', roles: SUPER, href: 'settings.php',
        intro: 'หมวด <b>บันทึกมิเตอร์ น้ำ/ไฟ</b>',
        parts: [
        { title: 'รายการมิเตอร์', shot: 'settings_meters', steps: [
            { m: 3, t: 'เพิ่มมิเตอร์: ประเภท (น้ำ / ไฟ / TOU / แก๊ส) ชื่อ ตำแหน่ง <b>ค่าสูงสุดหน้าปัด</b> และ <b>% ที่รับได้</b> — ใช้เตือนเมื่อใช้เกินเกณฑ์' },
            { m: 4, t: '<span class="kbd">บันทึกข้อมูล</span>' }
        ] },
        { title: 'ช่วงเวลาที่บันทึก', shot: 'settings_meters_time', steps: [
            { m: 3, t: 'รอบเวลาที่ต้องจดมิเตอร์ เช่น "รอบที่ 1 เวลา 08:00 น." — แสดงเป็นตัวเลือกตอนเพิ่มบันทึก' }
        ] }]
    },
    {
        id: 'settings_other', title: 'อะไหล่และหัวข้อประเมิน PM', roles: SUPER, href: 'settings.php',
        intro: 'หมวด <b>อะไหล่และวัสดุ</b> และ <b>การประเมินผล PM</b>',
        parts: [
        { title: 'รายการอะไหล่และวัสดุ', shot: 'settings_spares', steps: [
            { m: 3, t: 'แก้ไขรายการสินค้าแบบตาราง: รหัส ประเภทงาน ชื่อ รุ่น ราคา/หน่วย หน่วย และคลังที่จัดเก็บ' },
            { m: 4, t: '<span class="kbd">บันทึกข้อมูล</span> — จำนวนคงเหลือให้ใช้เมนู <b>จัดการสต็อก</b> (รับเข้า / ปรับยอด)' }
        ] },
        { title: 'รายการหัวข้อประเมิน', shot: 'settings_pm_feedback', steps: [
            { m: 3, t: 'หัวข้อที่ใช้ประเมินผลการปฏิบัติงาน PM และ <b>ลำดับการแสดงผล</b> — เอาติ๊ก Active ออกเพื่อไม่ใช้หัวข้อนั้น' }
        ] }]
    }]
}];

// คำถามที่พบบ่อย: [หมวด, คำถาม, คำตอบ, id บทที่เกี่ยวข้อง (แสดงลิงก์เฉพาะบทที่ผู้ใช้มีสิทธิ์)]
const FAQ = [
    ['ทั่วไป', 'ไม่เห็นเมนูที่ต้องใช้', 'เมนูแสดงตามสิทธิ์ของบัญชี ติดต่อผู้ดูแลระบบเพื่อขอสิทธิ์', 'main'],
    ['ทั่วไป', 'ลืมรหัสผ่าน / ต้องการเปลี่ยนรหัสผ่าน', 'ติดต่อผู้ดูแลระบบให้ตั้งรหัสผ่านใหม่ (ปุ่มรูปกุญแจในหน้าตั้งค่าผู้ใช้งาน)', 'settings'],
    ['ทั่วไป', 'บนมือถือหาเมนูไม่เจอ', 'กดปุ่ม ☰ มุมซ้ายบนของจอ', 'main'],
    ['ทั่วไป', 'ตัวอักษรเล็ก (หรือใหญ่) เกินไป', 'เลือกขนาดตัวอักษรที่เมนู "ตั้งค่าการแสดงผล" มีผลทันทีกับทุกหน้า และจำไว้ในเครื่องนั้น', 'display_settings'],
    ['ทั่วไป', 'ส่งออกข้อมูลในตารางเป็น Excel ทำอย่างไร', 'กดปุ่ม "ส่งออก Excel" สีเขียวเหนือตาราง (หน้าข้อมูลแจ้งซ่อม ข้อมูลเครื่องจักร มิเตอร์ และสต๊อก) หรือคลิกขวาที่ตารางแล้วเลือก Excel Export — ใช้บนคอมพิวเตอร์/แท็บเล็ต', 'repair_list'],
    ['ทั่วไป', 'ข้อมูลไม่อัปเดต', 'กดปุ่มรีเฟรช/โหลดใหม่ของหน้านั้น หรือเลือกเมนูเดิมอีกครั้ง', null],
    ['ทั่วไป', 'ตัวเลขสีแดงบนกระดิ่งคืออะไร', 'จำนวนการแจ้งเตือนที่ยังไม่อ่าน กดกระดิ่งเพื่อดู หรือกด "อ่านทั้งหมด" ในหน้าการแจ้งเตือน', 'notifications'],
    ['ทั่วไป', 'ใช้บนมือถือได้ไหม', 'ได้ทุกเมนู หน้าจอปรับตามขนาดอัตโนมัติ และเข้าสู่ระบบด้วยการสแกน QR Code ได้', 'qr_login'],
    ['แจ้งซ่อม', 'ดูรูปที่แนบให้ใหญ่ / ซูม / เลื่อนรูปอย่างไร', 'กดที่รูปในรายการ จะเปิดเต็มจอ — ปัดซ้าย/ขวาหรือลากด้วยเมาส์เพื่อเปลี่ยนรูป ถ่างสองนิ้ว แตะสองครั้ง หรือหมุนล้อเมาส์เพื่อซูม กด ✕ หรือ Esc เพื่อปิด', 'repair_list'],
    ['แจ้งซ่อม', 'แนบรูปหรือวิดีโอไม่ได้', 'รูปแนบได้สูงสุด 5 รูป (JPG / PNG / WEBP) วิดีโอ 1 คลิป ไม่เกิน 30 วินาที — คลิปยาวให้ตัดช่วงก่อนส่ง', 'repair'],
    ['แจ้งซ่อม', 'แจ้งซ่อมแล้ว จะรู้ได้อย่างไรว่าช่างรับงานแล้ว', 'ดูสถานะในเมนูข้อมูลแจ้งซ่อม: รอดำเนินการ → กำลังดำเนินการ (ช่างรับงานแล้ว) → เสร็จสิ้น', 'repair_list'],
    ['แจ้งซ่อม', 'ขึ้นเดือนใหม่แล้ว งานเดือนก่อนที่ยังไม่เสร็จหายไปไหม', 'ไม่หาย — การ์ดรอดำเนินการ / กำลังดำเนินการในหน้าหลักนับงานค้างทั้งหมด และมีแถบเตือนสีส้มบอกงานที่ค้างจากก่อนช่วงที่เลือก กด "ดูรายการ" เพื่อเปิดทั้งหมด (บนมือถือเลือกชิป "งานค้าง")', 'dashboard'],
    ['แจ้งซ่อม', 'แจ้งซ่อมผิด ต้องการยกเลิก', 'กดปุ่ม ✕ ยกเลิกใบงาน ในเมนูข้อมูลแจ้งซ่อม (ยกเลิกได้เฉพาะงานที่ยังไม่ปิด)', 'repair_list'],
    ['แจ้งซ่อม', 'ส่งแบบประเมินไม่ได้', 'ต้องปิดงานให้เป็นสถานะเสร็จสิ้นก่อน จึงจะส่งแบบประเมินได้', 'repair_eval'],
    ['แจ้งซ่อม', 'สแกน QR ที่ตัวเครื่องแล้วไปหน้าไหน', 'เปิดหน้าประวัติเครื่องจักร ดูข้อมูลเครื่องและประวัติซ่อม แล้วกด "แจ้งซ่อมเครื่องจักรนี้" ได้เลย', 'qr'],
    ['งาน PM', 'งาน PM ไม่ขึ้นในปฏิทิน', 'ตรวจตัวกรองด้านซ้าย (กด "ล้างค่า") และเดือนที่ดูอยู่ — ถ้ายังไม่ขึ้น แผนอาจยังไม่ได้ใส่วันเริ่มทำ', 'pm_calendar'],
    ['งาน PM', 'ทำงาน PM ตามวันที่กำหนดไม่ได้', 'เลื่อนงานได้ที่แท็บเลื่อนแผน PM โดยต้องระบุเหตุผลทุกครั้ง', 'pm_postpone'],
    ['งาน PM', 'งาน PM ตรงกับวันหยุด', 'ตั้งวันหยุดและวิธีจัดการ (เลื่อนไป / เลื่อนมาก่อน / หยุดทำ) ที่แท็บจัดการวันหยุด แล้วกด "คำนวณแผนใหม่"', 'pm_holiday'],
    ['งาน PM', 'ตัวเลขในช่องของตารางแผน PM รายปีคืออะไร', 'จำนวนครั้งของงานในช่องนั้น (รายปีแบ่งเดือนละ 4 ช่อง) — ดูทีละวันได้ที่มุมมองรายเดือนหรือรายสัปดาห์', 'pm_schedule'],
    ['งาน PM', 'งานเช็คชีตเดียวกันมีหลายร้อยใบ ต้องกดบันทึกทีละใบไหม', 'ไม่ต้อง — กดที่งานในปฏิทินแล้วเลือก "บันทึกพร้อมกันหลายเครื่อง" (หรือกด +N ในช่องวัน) กรอกผลตรวจ ผู้ตรวจ และเซ็นครั้งเดียว ระบบสร้างใบงานแยกให้ทุกเครื่อง', 'pm_batch'],
    ['งาน PM', 'บันทึกพร้อมกันแล้วบางเครื่องไม่สำเร็จ', 'ดูสาเหตุในสรุปผล (เช่น ยังไม่แนบรูปที่บังคับ) เครื่องที่ไม่สำเร็จยังอยู่ในรายการ แก้แล้วกดบันทึกอีกครั้ง — ถ้าทุกเครื่องขึ้นว่าไม่พบเลขที่เอกสาร ให้ผู้ดูแลใส่เลขที่เอกสารของเช็คชีตก่อน', 'pm_batch'],
    ['งาน PM', 'บันทึกใบงาน PM ไม่ผ่าน', 'ดูขั้นสรุปว่ายังมีข้อที่ไม่ได้ตรวจหรือไม่ จุดที่บังคับถ่ายรูปต้องแนบรูป และต้องเลือกชื่อผู้ตรวจสอบพร้อมเซ็นชื่อ', 'pm_worksheet'],
    ['มิเตอร์และสต็อก', 'เลขมิเตอร์ครั้งนี้น้อยกว่าครั้งก่อน', 'ถ้าหน้าปัดวิ่งครบรอบแล้ว ให้ติ๊ก "ยืนยันมิเตอร์วนรอบ" ตอนบันทึก', 'meter'],
    ['มิเตอร์และสต็อก', 'จำนวนอะไหล่ในระบบไม่ตรงกับของจริง', 'ใช้ปุ่ม "ปรับยอด" ในเมนูจัดการสต็อก เพื่อให้มีประวัติการแก้ไข', 'stock_move'],
    ['คู่มือนี้', 'สถานะ "อ่านแล้ว" หายไป', 'สถานะอ่านแล้วเก็บไว้ในเบราว์เซอร์ของเครื่องนั้น เปลี่ยนเครื่อง/เบราว์เซอร์ หรือล้างข้อมูลเบราว์เซอร์จะเริ่มนับใหม่', null]
];

// ============================================================
// สิทธิ์ / ความคืบหน้า
// ============================================================
const allowed = l => l.roles ? l.roles.includes(LEVEL) : (l.perm === null || PERMS.includes(l.perm));
const modules = MODULES.map(m => ({ ...m, lessons: m.lessons.filter(allowed) })).filter(m => m.lessons.length);
const flat = modules.flatMap(m => m.lessons.map(l => ({ ...l, module: m })));
const byId = Object.fromEntries(flat.map(l => [l.id, l]));

const DONE_KEY = 'easypro_manual_done';
let done = new Set();
try { done = new Set(JSON.parse(localStorage.getItem(DONE_KEY) || '[]')); } catch (e) {}
const saveDone = () => { try { localStorage.setItem(DONE_KEY, JSON.stringify([...done])); } catch (e) {} };

const $ = id => document.getElementById(id);
const stripTags = s => String(s || '').replace(/<[^>]+>/g, ' ');
const icons = () => window.lucide && lucide.createIcons();

function updateProgress() {
    const n = flat.filter(l => done.has(l.id)).length;
    const pct = flat.length ? Math.round(n / flat.length * 100) : 0;
    ['progText', 'progTextM'].forEach(id => $(id).textContent = `${n}/${flat.length}`);
    ['progBar', 'progBarM'].forEach(id => $(id).style.width = pct + '%');
}

// ============================================================
// คำถามที่พบบ่อย
// ============================================================
const faqMatch = (f, q) => !q || (f[1] + ' ' + f[2]).toLowerCase().includes(q);
function faqListHtml(q, open) {
    const list = FAQ.filter(f => faqMatch(f, q));
    if (!list.length) return `<div class="rounded-xl border border-slate-200 bg-white px-4 py-8 text-center text-sm text-slate-400">ไม่พบคำถามที่ตรงกับ "${q.replace(/[<>&"]/g, '')}"</div>`;
    return `<div class="rounded-xl border border-slate-200 bg-white divide-y divide-slate-100 overflow-hidden">
        ${[...new Set(list.map(f => f[0]))].map(g => `<div class="px-4 pt-3 pb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400 bg-slate-50/70">${g}</div>`
            + list.filter(f => f[0] === g).map(([, fq, a, go]) => `<details class="group px-4 py-3" ${open ? 'open' : ''}><summary class="cursor-pointer list-none flex items-center justify-between gap-3 text-[14px] font-medium text-slate-800">${fq}<i data-lucide="chevron-down" class="w-4 h-4 flex-none text-slate-400 group-open:rotate-180 transition-transform"></i></summary><p class="mt-2 text-[14px] text-slate-600">${a}</p>${go && byId[go] ? `<button type="button" data-nav="${go}" class="mt-2 inline-flex items-center gap-1 text-[13px] font-medium text-sky-700 hover:underline"><i data-lucide="book-open" class="w-3.5 h-3.5"></i>ดูบท: ${byId[go].title}</button>` : ''}</details>`).join('')).join('')}
    </div>`;
}

function renderFaq() {
    const q = $('search').value.trim().toLowerCase();
    $('content').innerHTML = `
    <div class="max-w-5xl mx-auto px-4 sm:px-8 py-6 sm:py-8 fade-in">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-sky-50 text-[#006b9f] flex items-center justify-center flex-none"><i data-lucide="circle-help" class="w-5 h-5"></i></div>
            <div class="min-w-0">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900">คำถามที่พบบ่อย</h2>
                <p class="text-[13px] text-slate-500">${q ? `ผลการค้นหา "${q.replace(/[<>&"]/g, '')}" — <button type="button" id="faqClear" class="text-sky-700 font-medium hover:underline">ดูทั้งหมด</button>` : 'กดที่คำถามเพื่อดูคำตอบ หรือพิมพ์ในช่องค้นหาด้านซ้ายเพื่อหาคำถาม'}</p>
            </div>
        </div>
        <div class="mt-5">${faqListHtml(q, !!q)}</div>
        <p class="mt-6 text-[13px] text-slate-500">ไม่พบคำตอบที่ต้องการ? ติดต่อผู้ดูแลระบบของหน่วยงาน</p>
    </div>`;
    icons();
}

// ============================================================
// สารบัญ + ค้นหา
// ============================================================
let currentId = null;
function renderToc() {
    const q = $('search').value.trim().toLowerCase();
    const match = l => !q || [l.title, l.intro, l.module.title, ...(l.parts || []).filter(p => !p.roles || p.roles.includes(LEVEL)).flatMap(p => p.steps.map(s => s.t)), ...(l.tips || []), ...(l.blocks || []).map(b => b.title + ' ' + b.text)]
        .some(t => stripTags(t).toLowerCase().includes(q));
    const faqHits = FAQ.filter(f => faqMatch(f, q)).length;
    let html = `<button type="button" class="toc-item ${currentId === null ? 'active' : ''}" data-go=""><i data-lucide="home" class="w-4 h-4 text-slate-500"></i>เริ่มต้นที่นี่</button>`;
    if (!q || faqHits) html += `<button type="button" class="toc-item ${currentId === 'faq' ? 'active' : ''}" data-go="faq"><i data-lucide="circle-help" class="w-4 h-4 text-sky-700"></i><span class="flex-1">คำถามที่พบบ่อย</span>${q ? `<span class="rounded-full bg-sky-100 text-sky-700 text-[11px] font-semibold px-1.5">${faqHits}</span>` : ''}</button>`;
    let any = !!(q && faqHits);
    modules.forEach(m => {
        const ls = flat.filter(l => l.module.id === m.id && match(l));
        if (!ls.length) return;
        any = true;
        html += `<div class="mt-3 mb-1 px-2 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400"><i data-lucide="${m.icon}" class="w-3.5 h-3.5"></i>${m.title}</div>`;
        html += ls.map(l => `<button type="button" class="toc-item ${l.id === currentId ? 'active' : ''} ${done.has(l.id) ? 'done' : ''}" data-go="${l.id}">
            <span class="toc-dot">${done.has(l.id) ? '✓' : ''}</span><span class="min-w-0">${l.title}</span></button>`).join('');
    });
    if (!any) html += `<div class="px-3 py-6 text-center text-sm text-slate-400">ไม่พบหัวข้อที่ตรงกับ "${q.replace(/[<>&"]/g, '')}"</div>`;
    $('tocList').innerHTML = html;
    icons();
}
$('tocList').addEventListener('click', e => {
    const b = e.target.closest('[data-go]');
    if (!b) return;
    location.hash = b.dataset.go ? '#' + b.dataset.go : '#';
    closeToc();
});
$('search').addEventListener('input', () => { renderToc(); if (currentId === 'faq') renderFaq(); });

// สารบัญบนมือถือ (แผงเลื่อนจากซ้าย)
const openToc = () => { $('toc').classList.remove('-translate-x-full'); $('tocBackdrop').classList.remove('hidden'); };
const closeToc = () => { if (innerWidth < 1024) { $('toc').classList.add('-translate-x-full'); $('tocBackdrop').classList.add('hidden'); } };
$('tocBtn').addEventListener('click', openToc);
$('tocBackdrop').addEventListener('click', closeToc);

// ============================================================
// ภาพหน้าจอ + หมุด
// ============================================================
function shotHtml(key, partIdx) {
    const s = SHOTS[key];
    if (!s) return '';
    const pins = (s.markers || []).map((m, i) => m ? `
        <div class="pin-box" data-box="${partIdx}-${i + 1}" style="left:${m.x}%;top:${m.y}%;width:${m.w}%;height:${m.h}%"></div>
        <button type="button" class="pin" data-pin="${partIdx}-${i + 1}" style="left:${Math.min(97, Math.max(1.5, m.x))}%;top:${Math.min(97, Math.max(1.5, m.y))}%">${i + 1}</button>` : '').join('');
    return `<div class="shot ${s.mobile ? 'mobile' : ''}" data-shot="${key}" data-part="${partIdx}">
        <img src="${s.src}?v=${VER}" alt="ภาพหน้าจอ" loading="lazy" width="${s.w}" height="${s.h}">${pins}</div>`;
}

function setActive(root, key, on) {
    root.querySelectorAll(`[data-pin="${key}"], [data-step="${key}"]`).forEach(el => el.classList.toggle('active', on));
    root.querySelectorAll(`[data-box="${key}"]`).forEach(el => el.classList.toggle('show', on));
}

// ============================================================
// หน้าบทเรียน
// ============================================================
function goButton(l) {
    if (!l.href) return '';
    return `<button type="button" data-open="${l.id}" class="inline-flex items-center gap-2 rounded-lg bg-[#006b9f] hover:bg-[#04506f] text-white px-4 py-2 text-sm font-medium shadow-sm">
        <i data-lucide="external-link" class="w-4 h-4"></i> ไปที่หน้านี้</button>`;
}

function renderLesson(l) {
    const idx = flat.indexOf(l);
    const prev = flat[idx - 1], next = flat[idx + 1];
    const adminChip = l.roles ? `<span class="inline-flex items-center gap-1 rounded-full bg-violet-50 text-violet-700 border border-violet-200 px-2 py-0.5 text-[11px] font-semibold"><i data-lucide="shield-check" class="w-3 h-3"></i>${l.roles.includes('admin') ? 'สำหรับผู้ดูแลระบบ' : 'สำหรับ Super Admin'}</span>` : '';

    const parts = (l.parts || []).filter(p => !p.roles || p.roles.includes(LEVEL)).map((p, pi) => `
        <section class="mt-6">
            ${p.title ? `<h3 class="text-base font-semibold text-slate-800 mb-3">${p.title}</h3>` : ''}
            <div class="${SHOTS[p.shot]?.mobile ? 'grid md:grid-cols-[minmax(0,360px)_1fr] gap-6 items-start' : 'part-grid'}">
                ${shotHtml(p.shot, pi)}
                <ol class="${SHOTS[p.shot]?.mobile ? '' : 'mt-4'} grid gap-1">
                    ${p.steps.map(s => `<li class="step" ${s.m ? `data-step="${pi}-${s.m}" tabindex="0"` : ''}>
                        <span class="step-no ${s.m ? '' : 'plain'}">${s.m || '•'}</span><div class="text-[14px] leading-relaxed text-slate-600">${s.t}</div></li>`).join('')}
                </ol>
            </div>
        </section>`).join('');

    const blocks = (l.blocks || []).map(b => `
        <div class="flex gap-3 p-4 rounded-xl bg-white border border-slate-200">
            <div class="w-9 h-9 rounded-lg bg-sky-50 text-sky-700 flex items-center justify-center flex-none"><i data-lucide="${b.icon}" class="w-4 h-4"></i></div>
            <div><div class="font-semibold text-slate-800 text-[14px]">${b.title}</div><div class="text-[14px] text-slate-600 leading-relaxed mt-0.5">${b.text}</div></div>
        </div>`).join('');

    const table = l.table ? `
        <section class="mt-6">
            <h3 class="text-base font-semibold text-slate-800 mb-2">${l.table.title}</h3>
            <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
                ${l.table.rows.map(r => `<div class="flex gap-4 px-4 py-2.5 border-t first:border-t-0 border-slate-100 text-[14px]"><div class="w-40 flex-none font-medium text-slate-800 flex items-center">${r[0]}</div><div class="text-slate-600">${r[1]}</div></div>`).join('')}
            </div>
        </section>` : '';

    const tips = l.tips ? `
        <div class="mt-6 rounded-xl bg-amber-50 border border-amber-200 p-4">
            <div class="flex items-center gap-2 text-amber-800 font-semibold text-sm mb-1.5"><i data-lucide="lightbulb" class="w-4 h-4"></i> เคล็ดลับ</div>
            <ul class="list-disc pl-5 space-y-1 text-[14px] text-amber-900/90">${l.tips.map(t => `<li>${t}</li>`).join('')}</ul>
        </div>` : '';

    // แถบล่าง (ค้างที่ขอบล่างของจอ): บทก่อนหน้า | อ่านแล้ว | บทถัดไป — จอเล็กเหลือแค่ไอคอน
    const isDone = done.has(l.id);
    const navBtn = (x, dir) => x ? `<button type="button" data-nav="${x.id}" title="${dir === 'prev' ? 'บทก่อนหน้า' : 'บทถัดไป'}: ${x.title}"
        class="group flex items-center gap-2 min-w-0 h-10 sm:h-auto rounded-xl border border-slate-200 bg-white hover:border-sky-300 hover:bg-sky-50 px-2.5 sm:px-3.5 sm:py-2 ${dir === 'prev' ? 'justify-self-start' : 'justify-self-end flex-row-reverse'}">
        <i data-lucide="${dir === 'prev' ? 'chevron-left' : 'chevron-right'}" class="w-5 h-5 text-slate-500 group-hover:text-sky-700"></i>
        <span class="hidden sm:block min-w-0 ${dir === 'prev' ? 'text-left' : 'text-right'}">
            <span class="block text-[11px] text-slate-400">${dir === 'prev' ? 'บทก่อนหน้า' : 'บทถัดไป'}</span>
            <span class="block text-sm font-semibold text-slate-700 truncate max-w-[16rem]">${x.title}</span>
        </span></button>` : '<span></span>';

    $('content').innerHTML = `
    <div class="min-h-full flex flex-col">
    <article class="flex-1 w-full max-w-5xl mx-auto px-4 sm:px-8 py-6 sm:py-8 fade-in lesson-body">
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1"><i data-lucide="${l.module.icon}" class="w-3.5 h-3.5"></i>${l.module.title}</span>
            <span>·</span><span>บทที่ ${idx + 1} จาก ${flat.length}</span>${adminChip}
        </div>
        <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900">${l.title}</h2>
            ${goButton(l)}
        </div>
        <p class="mt-2 text-[15px] text-slate-600">${l.intro}</p>
        ${parts}
        ${blocks ? `<div class="mt-6 grid gap-3 md:grid-cols-2">${blocks}</div>` : ''}
        ${table}
        ${tips}
    </article>
    <div class="sticky bottom-0 z-10 border-t border-slate-200 bg-white/95 backdrop-blur shadow-[0_-6px_16px_-10px_rgba(15,23,42,.25)]">
        <div class="max-w-5xl mx-auto px-3 sm:px-8 py-2 sm:py-2.5 grid grid-cols-[1fr_auto_1fr] items-center gap-2">
            ${navBtn(prev, 'prev')}
            <button type="button" id="doneBtn" title="${isDone ? 'อ่านบทนี้แล้ว (กดเพื่อยกเลิก)' : 'ทำเครื่องหมายว่าอ่านแล้ว'}" aria-pressed="${isDone}"
                class="inline-flex items-center justify-center gap-2 h-10 min-w-10 rounded-full px-2.5 sm:px-4 text-sm font-medium border ${isDone ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50'}">
                <i data-lucide="${isDone ? 'circle-check' : 'circle'}" class="w-5 h-5"></i><span class="hidden sm:inline">${isDone ? 'อ่านบทนี้แล้ว' : 'ทำเครื่องหมายว่าอ่านแล้ว'}</span>
            </button>
            ${navBtn(next, 'next')}
        </div>
    </div>
    </div>`;
    icons();
}

function renderHome() {
    const card = m => {
        const ls = flat.filter(l => l.module.id === m.id);
        const n = ls.filter(l => done.has(l.id)).length;
        return `<button type="button" data-nav="${(ls.find(l => !done.has(l.id)) || ls[0]).id}" class="flex flex-col text-left rounded-2xl border border-slate-200 bg-white p-4 hover:border-sky-300 hover:shadow-md transition">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-sky-50 text-[#006b9f] flex items-center justify-center flex-none"><i data-lucide="${m.icon}" class="w-5 h-5"></i></div>
                <div class="min-w-0"><div class="font-semibold text-slate-800">${m.title}</div><div class="text-xs text-slate-500">${ls.length} บท · อ่านแล้ว ${n}</div></div>
            </div>
            <ul class="mt-3 space-y-1 text-[13px] text-slate-600">${ls.map(l => `<li class="flex items-center gap-1.5"><i data-lucide="${done.has(l.id) ? 'circle-check' : 'circle'}" class="w-3.5 h-3.5 ${done.has(l.id) ? 'text-emerald-500' : 'text-slate-300'}"></i>${l.title}</li>`).join('')}</ul>
        </button>`;
    };
    const first = flat.find(l => !done.has(l.id)) || flat[0];
    $('content').innerHTML = `
    <div class="max-w-5xl mx-auto px-4 sm:px-8 py-6 sm:py-8 fade-in">
        <div class="rounded-2xl bg-gradient-to-br from-[#006b9f] to-[#04506f] text-white p-6 sm:p-8 shadow-lg relative overflow-hidden">
            <i data-lucide="book-open-check" class="absolute -right-6 -bottom-6 w-40 h-40 text-white/10"></i>
            <h2 class="text-xl sm:text-2xl font-bold">ยินดีต้อนรับสู่คู่มือการใช้งาน</h2>
            <p class="mt-2 text-white/85 text-[15px] max-w-2xl">แต่ละบทอธิบายหน้าจอจริงของระบบ ตัวเลข <span class="inline-flex w-5 h-5 rounded-full bg-rose-500 text-white text-xs font-bold items-center justify-center align-middle">1</span> บนภาพตรงกับขั้นตอนด้านล่าง ชี้หรือแตะที่ขั้นตอนเพื่อดูตำแหน่งบนภาพ กดที่ภาพเพื่อขยาย</p>
            ${first ? `<button type="button" data-nav="${first.id}" class="mt-5 inline-flex items-center gap-2 rounded-lg bg-white text-[#04506f] px-4 py-2 text-sm font-semibold shadow hover:bg-sky-50">
                <i data-lucide="play" class="w-4 h-4"></i> ${done.size ? 'อ่านต่อ: ' + first.title : 'เริ่มบทแรก'}</button>` : ''}
        </div>
        <h3 class="mt-8 mb-3 font-semibold text-slate-800">หัวข้อทั้งหมด</h3>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">${modules.map(card).join('')}</div>
        <h3 class="mt-8 mb-3 font-semibold text-slate-800">คำถามที่พบบ่อย</h3>
        ${faqListHtml('', false)}
    </div>`;
    icons();
}

// ============================================================
// เหตุการณ์ในเนื้อหา (ใช้ event delegation)
// ============================================================
const content = $('content');
content.addEventListener('mouseover', e => { const s = e.target.closest('[data-step]'); if (s) setActive(content, s.dataset.step, true); });
content.addEventListener('mouseout', e => { const s = e.target.closest('[data-step]'); if (s) setActive(content, s.dataset.step, false); });
content.addEventListener('focusin', e => { const s = e.target.closest('[data-step]'); if (s) setActive(content, s.dataset.step, true); });
content.addEventListener('focusout', e => { const s = e.target.closest('[data-step]'); if (s) setActive(content, s.dataset.step, false); });
content.addEventListener('click', e => {
    if (e.target.closest('#faqClear')) { $('search').value = ''; renderToc(); renderFaq(); return; }
    const nav = e.target.closest('[data-nav]');
    if (nav) {
        if (currentId && byId[currentId]) { done.add(currentId); saveDone(); }   // ไปบทถัดไป = อ่านบทนี้แล้ว
        location.hash = '#' + nav.dataset.nav; return;
    }
    const pin = e.target.closest('[data-pin]');
    if (pin) {
        // แตะหมุด => เลื่อนไปที่ขั้นตอนนั้นและไฮไลต์
        const step = content.querySelector(`[data-step="${pin.dataset.pin}"]`);
        content.querySelectorAll('.step.active, .pin.active').forEach(el => el.classList.remove('active'));
        content.querySelectorAll('.pin-box.show').forEach(el => el.classList.remove('show'));
        setActive(content, pin.dataset.pin, true);
        step?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }
    const step = e.target.closest('[data-step]');
    if (step) {
        content.querySelectorAll('.pin-box.show').forEach(el => el.classList.remove('show'));
        content.querySelectorAll('.step.active, .pin.active').forEach(el => el.classList.remove('active'));
        setActive(content, step.dataset.step, true);
        return;
    }
    const shot = e.target.closest('.shot');
    if (shot) { openZoom(shot); return; }
    if (e.target.closest('#doneBtn')) {
        done.has(currentId) ? done.delete(currentId) : done.add(currentId);
        saveDone(); route(); return;
    }
    const open = e.target.closest('[data-open]');
    if (open) openPage(byId[open.dataset.open]);
});

// ไปที่หน้าจริงในระบบ (หน้านี้อยู่ใน iframe ของ main.php)
function openPage(l) {
    try { if (l.tab) localStorage.setItem('activePMTab', l.tab); } catch (e) {}
    if (window.parent && window.parent !== window && typeof window.parent.navigate === 'function') window.parent.navigate(l.href);
    else location.href = l.href;
}

// ภาพขยาย: แตะตัวเลขบนภาพ => แสดงคำอธิบายของขั้นตอนนั้นใต้ภาพ เลื่อนทีละจุดด้วยปุ่ม ‹ › หรือปุ่มลูกศรบนคีย์บอร์ด
let zoomSteps = [];   // [{ m, html }] เรียงตามเลขหมุด
let zoomIdx = -1;
// เปิดใน main.php: ขอให้ขยาย iframe เต็มหน้าจอ ภาพขยายจะทับ navbar/sidebar
function frameMax(on) { try { if (window.parent !== window && typeof parent.setFrameMax === 'function') parent.setFrameMax(on); } catch (e) { /* ต่าง origin */ } }
function closeZoom() { $('zoom').classList.remove('open'); frameMax(false); }
function openZoom(shot) {
    const z = $('zoom');
    const l = byId[currentId];
    const part = l ? (l.parts || []).filter(p => !p.roles || p.roles.includes(LEVEL))[+shot.dataset.part] : null;
    const byM = {};
    (part ? part.steps : []).forEach(st => { if (st.m) (byM[st.m] = byM[st.m] || []).push(st.t); });
    zoomSteps = Object.keys(byM).map(Number).sort((a, b) => a - b).map(m => ({ m, html: byM[m].join('<br>') }));
    zoomIdx = -1;
    z.innerHTML = `<button type="button" data-close class="absolute top-3 right-3 w-10 h-10 rounded-full bg-white/15 hover:bg-white/25 text-white flex items-center justify-center" title="ปิด (Esc)"><i data-lucide="x" class="w-5 h-5"></i></button>`
        + shotHtml(shot.dataset.shot, 'z')
        + (zoomSteps.length ? `<div id="zoomCap" class="bg-white rounded-2xl shadow-xl p-2 sm:p-3 flex items-center gap-2 sm:gap-3">
            <button type="button" data-zstep="-1" class="w-10 h-10 flex-none rounded-full border border-slate-200 hover:bg-slate-50 text-slate-600 flex items-center justify-center" title="จุดก่อนหน้า (←)"><i data-lucide="chevron-left" class="w-5 h-5"></i></button>
            <div id="zoomCapBody" class="flex-1 min-w-0 flex items-center gap-2.5 py-0.5"></div>
            <button type="button" data-zstep="1" class="w-10 h-10 flex-none rounded-full border border-slate-200 hover:bg-slate-50 text-slate-600 flex items-center justify-center" title="จุดถัดไป (→)"><i data-lucide="chevron-right" class="w-5 h-5"></i></button>
        </div>` : '');
    z.classList.add('open');
    frameMax(true);
    showZoomStep(-1);
    icons();
}
function showZoomStep(i) {
    const z = $('zoom');
    zoomIdx = i;
    const st = zoomSteps[i];
    z.querySelectorAll('.pin').forEach(p => p.classList.toggle('active', !!st && p.dataset.pin === 'z-' + st.m));
    z.querySelectorAll('.pin-box').forEach(b => b.classList.toggle('show', !!st && b.dataset.box === 'z-' + st.m));
    const body = $('zoomCapBody');
    if (!body) return;
    body.innerHTML = st
        ? `<span class="step-no">${st.m}</span><div class="cap-text flex-1 min-w-0 text-left text-[14px] leading-relaxed text-slate-600"><div>${st.html}</div></div><span class="ml-auto pl-2 text-[11px] text-slate-400 whitespace-nowrap">${i + 1}/${zoomSteps.length}</span>`
        : `<div class="self-center text-[14px] text-slate-500"><i data-lucide="mouse-pointer-click" class="w-4 h-4 inline -mt-0.5 mr-1 text-rose-500"></i>แตะตัวเลขบนภาพ หรือกด ‹ › เพื่อดูคำอธิบายทีละจุด</div>`;
    icons();
}
$('zoom').addEventListener('click', e => {
    const pin = e.target.closest('[data-pin]');
    if (pin) {
        const m = +pin.dataset.pin.split('-')[1];
        const i = zoomSteps.findIndex(s => s.m === m);
        if (i >= 0) showZoomStep(i);
        else {   // หมุดที่ไม่มีคำอธิบาย: ไฮไลต์อย่างเดียว
            $('zoom').querySelectorAll('.pin-box').forEach(b => b.classList.toggle('show', b.dataset.box === pin.dataset.pin));
        }
        return;
    }
    const nav = e.target.closest('[data-zstep]');
    if (nav) { stepZoom(+nav.dataset.zstep); return; }
    if (e.target === $('zoom') || e.target.closest('[data-close]')) closeZoom();
});
function stepZoom(d) {
    if (!zoomSteps.length) return;
    showZoomStep(zoomIdx < 0 ? (d > 0 ? 0 : zoomSteps.length - 1) : (zoomIdx + d + zoomSteps.length) % zoomSteps.length);
}
document.addEventListener('keydown', e => {
    if (!$('zoom').classList.contains('open')) return;
    if (e.key === 'Escape') closeZoom();
    else if (e.key === 'ArrowRight') stepZoom(1);
    else if (e.key === 'ArrowLeft') stepZoom(-1);
});

// ============================================================
// เส้นทาง (#lesson-id)
// ============================================================
function route() {
    const id = decodeURIComponent(location.hash.slice(1));
    const l = byId[id];
    currentId = l ? l.id : (id === 'faq' ? 'faq' : null);
    if (l) renderLesson(l); else if (id === 'faq') renderFaq(); else renderHome();
    renderToc();
    updateProgress();
    content.scrollTop = 0;
}
// ============================================================
// ส่งออก PDF: รวมทุกบทที่ผู้ใช้มีสิทธิ์ + คำถามที่พบบ่อย ในไฟล์เดียว (หน้าต่างพิมพ์ของเบราว์เซอร์ => บันทึกเป็น PDF)
// ============================================================
function printLessonHtml(l, no) {
    const parts = (l.parts || []).filter(p => !p.roles || p.roles.includes(LEVEL)).map((p, pi) => `
        <section class="pp-part">
            ${p.title ? `<h3 class="text-[13pt] font-semibold text-slate-800">${p.title}</h3>` : ''}
            <div class="pp-fig ${SHOTS[p.shot]?.mobile ? 'pp-mobile' : ''}">${shotHtml(p.shot, 'pp-' + l.id + '-' + pi).replace('loading="lazy"', 'loading="eager"')}</div>
            <ol class="pp-steps space-y-1.5">
                ${p.steps.map(st => `<li class="flex gap-2.5"><span class="step-no ${st.m ? '' : 'plain'}">${st.m || '•'}</span><div class="leading-relaxed text-slate-700">${st.t}</div></li>`).join('')}
            </ol>
        </section>`).join('');
    const blocks = (l.blocks || []).map(b => `<div class="pp-keep mt-3 rounded-lg border border-slate-200 p-3"><div class="font-semibold">${b.title}</div><div class="text-slate-600 mt-0.5">${b.text}</div></div>`).join('');
    const table = l.table ? `<div class="pp-keep mt-5"><h3 class="text-[12pt] font-semibold mb-1.5">${l.table.title}</h3>
        <table class="w-full border-collapse text-[10.5pt]">${l.table.rows.map(r => `<tr><td class="border border-slate-200 px-2 py-1.5 w-[45mm] font-medium">${r[0]}</td><td class="border border-slate-200 px-2 py-1.5 text-slate-600">${r[1]}</td></tr>`).join('')}</table></div>` : '';
    const tips = l.tips ? `<div class="pp-keep mt-5 rounded-lg bg-amber-50 border border-amber-200 p-3"><div class="font-semibold text-amber-800 mb-1">เคล็ดลับ</div>
        <ul class="list-disc pl-5 space-y-0.5 text-amber-900">${l.tips.map(t => `<li>${t}</li>`).join('')}</ul></div>` : '';
    return `<article class="pp-page">
        <div class="text-[10pt] text-slate-500">${l.module.title} · บทที่ ${no}</div>
        <h2 class="text-[18pt] font-bold text-slate-900 mt-1">${no}. ${l.title}</h2>
        <p class="mt-2 text-slate-600 leading-relaxed">${l.intro}</p>
        ${parts}${blocks}${table}${tips}
    </article>`;
}

function coverHtml(pageOf) {
    const dateTh = new Date().toLocaleDateString('th-TH', { day: 'numeric', month: 'long', year: 'numeric' });
    const toc = modules.map(m => `<div class="mt-2.5"><div class="font-semibold text-slate-800">${m.title}</div>
        <ol class="mt-0.5">${flat.filter(l => l.module.id === m.id).map(l => `<li class="flex gap-2 text-slate-600"><span class="w-7 text-right">${flat.indexOf(l) + 1}.</span><span class="flex-1">${l.title}</span><span class="w-10 text-right text-slate-400">${pageOf ? pageOf[l.id] : ''}</span></li>`).join('')}</ol></div>`).join('');
    return `<section>
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl bg-[#006b9f] text-white flex items-center justify-center"><i data-lucide="book-open-check" class="w-6 h-6"></i></div>
            <div><div class="text-[28px] font-bold text-slate-900 leading-tight">คู่มือการใช้งาน EasyPro</div>
            <div class="text-slate-500">ระบบแจ้งซ่อม บำรุงรักษา (PM) และข้อมูลอาคาร</div></div>
        </div>
        <div class="mt-2 text-[13px] text-slate-400">จัดทำเมื่อ ${dateTh} · ${flat.length} บท</div>
        <h2 class="mt-6 text-[19px] font-bold text-slate-800 flex"><span class="flex-1">สารบัญ</span><span class="text-[12px] font-normal text-slate-400 self-end">หน้า</span></h2>
        ${toc}
        <div class="mt-3"><div class="font-semibold text-slate-800">ภาคผนวก</div><div class="flex text-slate-600 pl-9"><span class="flex-1">คำถามที่พบบ่อย</span><span class="w-10 text-right text-slate-400">${pageOf ? pageOf.faq : ''}</span></div></div>
    </section>`;
}
const faqSectionHtml = () => `<section><h2 class="text-[24px] font-bold text-slate-900">คำถามที่พบบ่อย</h2><div class="mt-4">${faqListHtml('', true)}</div></section>`;

// วาด HTML หนึ่งส่วนลง #printArea => {canvas, cuts (จุดที่ตัดหน้าได้, หน่วย px ของ canvas)}
async function renderSection(html) {
    const root = $('printArea');
    root.innerHTML = html;
    root.querySelectorAll('img').forEach(im => im.loading = 'eager');
    // ลิงก์ "ดูบท" กดไม่ได้ใน PDF => เปลี่ยนเป็นเลขบทที่อ้างอิง
    root.querySelectorAll('[data-nav]').forEach(b => {
        const l = byId[b.dataset.nav];
        const ref = document.createElement('div');
        ref.className = 'pp-ref mt-1';
        ref.textContent = l ? `→ ดูบทที่ ${flat.indexOf(l) + 1}: ${l.title}` : '';
        b.replaceWith(ref);
    });
    // ตัวเลขในวงกลม (หมุด/ขั้นตอน) => SVG: html2canvas วางตัวอักษรในวงกลมได้ไม่ตรงกึ่งกลาง
    root.querySelectorAll('.pin, .step-no').forEach(el => {
        const t = el.textContent.trim(), plain = el.classList.contains('plain'), pin = el.classList.contains('pin');
        const fill = plain ? '#e2e8f0' : '#f43f5e', ink = plain ? '#475569' : '#ffffff';
        el.innerHTML = `<svg width="22" height="22" viewBox="0 0 22 22" style="display:block"><circle cx="11" cy="11" r="${pin ? 10 : 11}" fill="${fill}" ${pin ? 'stroke="#ffffff" stroke-width="2"' : ''}/><text x="11" y="15.2" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="12" font-weight="700" fill="${ink}">${t}</text></svg>`;
        el.style.background = 'transparent'; el.style.boxShadow = 'none';
    });
    icons();
    await Promise.all([...root.querySelectorAll('img')].map(im => im.complete ? 0 : new Promise(r => { im.onload = im.onerror = r; })));
    if (document.fonts && document.fonts.ready) await document.fonts.ready;
    const scale = 1.5;
    const top = root.getBoundingClientRect().top;
    // จุดตัดหน้า: ใต้บล็อก (ภาพ/ขั้นตอน/กล่อง/รายการ) หรือเหนือหัวข้อ (ไม่ให้หัวข้อค้างท้ายหน้าโดยไม่มีเนื้อหา)
    const cuts = [
        ...[...root.querySelectorAll('p, .pp-fig, .pp-steps > li, .pp-keep, details, ol > li, table tr')].map(el => el.getBoundingClientRect().bottom - top + 6),
        ...[...root.querySelectorAll('h2, h3, .pp-part, section:not(.pp-part) > div')].map(el => el.getBoundingClientRect().top - top - 4)
    ].filter(v => v > 0).map(v => Math.round(v * scale));
    const canvas = await html2canvas(root, { scale, backgroundColor: '#ffffff', useCORS: true, logging: false, windowWidth: 1280 });
    return { canvas, cuts };
}

const PDF = { w: 210, h: 297, mx: 12, mt: 12, mb: 16 };   // A4 (มม.)
// แบ่ง canvas เป็นหน้า ๆ — ตัดที่ขอบบล็อกที่ใกล้ขอบล่างที่สุด (ไม่ตัดกลางภาพ/ขั้นตอน)
function slicePages({ canvas, cuts }) {
    const contentW = PDF.w - PDF.mx * 2, contentH = PDF.h - PDF.mt - PDF.mb;
    const pageH = Math.floor(canvas.width * contentH / contentW);
    const out = [];
    for (let y = 0; y < canvas.height - 2;) {
        let end = Math.min(canvas.height, y + pageH);
        if (end < canvas.height) {
            const best = cuts.filter(c => c > y + pageH * 0.35 && c <= end).sort((a, b) => b - a)[0];
            if (best) end = best;
        }
        out.push([y, end]);
        y = end;
    }
    return out;
}
function addSlices(pdf, canvas, slices, first) {
    const contentW = PDF.w - PDF.mx * 2;
    slices.forEach(([y0, y1], i) => {
        if (!(first && i === 0)) pdf.addPage();
        const c = document.createElement('canvas');
        c.width = canvas.width; c.height = y1 - y0;
        const ctx = c.getContext('2d');
        ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, c.width, c.height);
        ctx.drawImage(canvas, 0, y0, canvas.width, y1 - y0, 0, 0, canvas.width, y1 - y0);
        pdf.addImage(c.toDataURL('image/jpeg', 0.82), 'JPEG', PDF.mx, PDF.mt, contentW, (y1 - y0) * contentW / canvas.width, undefined, 'FAST');
    });
}

let pdfBusy = false, pdfCancelled = false;
async function exportPdf() {
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF({ unit: 'mm', format: 'a4', compress: true });
    const total = flat.length + 2;
    const progress = (n, text) => {
        const pct = Math.round(n / total * 100);
        $('pdfStep').textContent = text; $('pdfPct').textContent = pct + '%'; $('pdfBar').style.width = pct + '%';
    };
    // ปกวาดทีหลัง (ต้องรู้เลขหน้าของแต่ละบทก่อน) — วัดจำนวนหน้าของปกไว้ก่อน
    progress(0, 'กำลังเตรียมหน้าปก...');
    const coverPages = slicePages(await renderSection(coverHtml(null))).length;
    const pageOf = {};
    let page = 0, first = true;
    for (let i = 0; i < flat.length; i++) {
        if (pdfCancelled) return false;
        progress(i + 1, `บทที่ ${i + 1}/${flat.length}: ${flat[i].title}`);
        const r = await renderSection(printLessonHtml(flat[i], i + 1));
        const sl = slicePages(r);
        pageOf[flat[i].id] = coverPages + page + 1;
        addSlices(pdf, r.canvas, sl, first); first = false;
        page += sl.length;
    }
    if (pdfCancelled) return false;
    progress(flat.length + 1, 'คำถามที่พบบ่อย');
    const rf = await renderSection(faqSectionHtml());
    const sf = slicePages(rf);
    pageOf.faq = coverPages + page + 1;
    addSlices(pdf, rf.canvas, sf, false);
    // หน้าปก (พร้อมเลขหน้าในสารบัญ) ย้ายไปไว้หน้าแรก
    progress(total, 'หน้าปกและสารบัญ');
    // ถ้าปกจริง (มีเลขหน้า) ยาวไม่เท่าที่วัดไว้ => เลื่อนเลขหน้าในสารบัญแล้ววาดใหม่
    let rc, sc, coverNow = coverPages;
    for (let tries = 0; tries < 3; tries++) {
        rc = await renderSection(coverHtml(pageOf));
        sc = slicePages(rc);
        if (sc.length === coverNow) break;
        const diff = sc.length - coverNow;
        Object.keys(pageOf).forEach(k => { pageOf[k] += diff; });
        coverNow = sc.length;
    }
    addSlices(pdf, rc.canvas, sc, false);
    const n = pdf.getNumberOfPages();
    for (let k = 0; k < sc.length; k++) pdf.movePage(n - sc.length + 1 + k, 1 + k);
    // เลขหน้าท้ายกระดาษ
    for (let p = 1; p <= n; p++) {
        pdf.setPage(p);
        pdf.setFontSize(9); pdf.setTextColor(148, 163, 184);
        pdf.text(`${p} / ${n}`, PDF.w / 2, PDF.h - 7, { align: 'center' });
    }
    $('printArea').innerHTML = '';
    pdf.save(`คู่มือการใช้งาน EasyPro ${new Date().toISOString().slice(0, 10)}.pdf`);
    return true;
}

const pdfModal = $('pdfModal');
const closePdf = () => { if (pdfBusy) pdfCancelled = true; pdfModal.classList.remove('open'); };
$('pdfBtn').addEventListener('click', () => {
    $('pdfInfo').textContent = `รวม ${flat.length} บท พร้อมภาพประกอบ และคำถามที่พบบ่อย ในไฟล์เดียว`;
    $('pdfProgress').classList.add('hidden');
    pdfModal.classList.add('open'); icons();
});
$('pdfCancel').addEventListener('click', closePdf);
pdfModal.addEventListener('click', e => { if (e.target === pdfModal && !pdfBusy) closePdf(); });
$('pdfGo').addEventListener('click', async () => {
    if (pdfBusy) return;
    const btn = $('pdfGo');
    pdfBusy = true; pdfCancelled = false;
    btn.disabled = true; btn.querySelector('span').textContent = 'กำลังสร้าง PDF...';
    $('pdfProgress').classList.remove('hidden');
    try {
        const ok = await exportPdf();
        if (ok) { $('pdfStep').textContent = 'ดาวน์โหลดเรียบร้อย'; setTimeout(() => pdfModal.classList.remove('open'), 900); }
    } catch (e) {
        console.error(e);
        $('pdfStep').textContent = 'สร้าง PDF ไม่สำเร็จ: ' + e.message;
    } finally {
        $('printArea').innerHTML = '';
        pdfBusy = false;
        btn.disabled = false; btn.querySelector('span').textContent = 'ดาวน์โหลด PDF';
    }
});

window.addEventListener('hashchange', route);
new ResizeObserver(() => content.classList.toggle('wide', content.clientWidth >= 700)).observe(content);
route();
</script>
</body>
</html>
