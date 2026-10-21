<?php
//pm_import.php — แท็บนำเข้าแผน PM จาก Excel ถูก include จาก pm.php (ผู้ใช้ทุกคนที่เข้าเมนูวางแผน PM ได้)
@session_start();
include_once "config_ctrl/checksession.php";
?>
<style>
    #imp-plan-hot .htInvalid { background-color: #fee2e2 !important; }
    #imp-plan-hot .imp-input { background-color: #f0f9ff; }
    #imp-plan-hot .imp-disabled { background-color: #f1f5f9 !important; color: #94a3b8; }
    /* ช่องสัปดาห์ (เหมือนตารางใน Template) — ใช้ !important เพราะธีม Handsontable บังคับสีพื้นของเซลล์อ่านอย่างเดียว (.htDimmed) */
    #imp-plan-hot td.imp-week { padding: 0; text-align: center; font-size: 11px; font-weight: 600; color: #0369a1; }
    #imp-plan-hot td.imp-week.imp-mark { background-color: #e0f2fe !important; }
    #imp-plan-hot td.imp-week.imp-start { background-color: #0ea5e9 !important; color: #fff; }
    #imp-plan-hot td.imp-month-end { border-right: 2px solid #cbd5e1; }
</style>
<div id="tab-import" class="tab-content hidden">
    <!-- ขั้นที่ 1: อ่านไฟล์ + สร้างเช็คชีต -->
    <div id="imp-step1" class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-800">นำเข้าแผน PM จาก Excel</h3>
                <p class="text-[12px] text-slate-500">ขั้นที่ 1: อ่านไฟล์ Yearly PM Schedule แล้วสร้างเช็คชีตให้อัตโนมัติ
                    <span id="imp-contract" class="font-medium text-sky-600"></span></p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button id="imp-defaults-btn" type="button" title="จุดตรวจที่ใส่ให้เช็คชีตใหม่ที่สร้างจากการนำเข้า (แยกตามหน่วยงาน)" class="border border-slate-300 text-slate-700 hover:bg-slate-50 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors">
                    <i data-lucide="list-checks" class="w-4 h-4"></i> ตั้งค่าจุดตรวจเริ่มต้น
                </button>
                <button id="imp-template-btn" class="border border-emerald-600 text-emerald-700 hover:bg-emerald-50 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors shadow-sm">
                    <i data-lucide="file-down" class="w-4 h-4"></i> <span class="sm:hidden">Template</span><span class="hidden sm:inline">ดาวน์โหลดไฟล์ Template สำหรับ Import</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-[13px] font-medium text-slate-700 mb-1.5">ไฟล์ Excel (.xlsx)</label>
                <input type="file" id="imp-file" accept=".xlsx,.xls"
                       class="w-full border border-slate-300 rounded-lg py-2 px-3 text-sm file:mr-3 file:py-1 file:px-3 file:border-0 file:rounded file:bg-sky-50 file:text-sky-700">
            </div>
            <div>
                <label class="block text-[13px] font-medium text-slate-700 mb-1.5">ชีต</label>
                <select id="imp-sheet" disabled class="w-full border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none"></select>
            </div>
            <div>
                <label class="block text-[13px] font-medium text-slate-700 mb-1.5">ปีของตารางใน Excel (ค.ศ.)</label>
                <input type="number" id="imp-year" min="2000" max="2100"
                       class="w-full border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
            </div>
        </div>

        <div id="imp-preview" class="hidden mt-5">
            <div id="imp-stats" class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-5"></div>

            <h4 class="text-sm font-semibold text-slate-700 mb-1">เช็คชีต</h4>
            <p class="text-[12px] text-slate-500 mb-2">
                เช็คชีตใหม่ใช้<span id="imp-default-desc">จุดตรวจเริ่มต้นของหน่วยงาน</span> — กด <b>แก้ไขจุดตรวจ</b> เพื่อเพิ่ม/แก้ไขเฉพาะเช็คชีตก่อนสร้าง (แก้ไขภายหลังได้ที่แท็บสร้างเช็คชีต) ·
                ระบบกำหนดเลขที่เอกสาร (CS-ปีเดือน-ลำดับ), Rev. 00, วันที่มีผล = วันที่นำเข้า และเวลาที่ใช้ 60 นาที ให้อัตโนมัติ ·
                แนบภาพจุดตรวจสอบได้สูงสุด 5 รูป และคำแนะนำในการทำงานสูงสุด 5 ไฟล์ ไม่เกิน 5MB/ไฟล์
            </p>
            <div class="overflow-auto border border-slate-200 rounded-lg mb-5" style="max-height: 420px;">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600 sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-2 text-left">เช็คชีต</th>
                            <th class="px-3 py-2 text-left">ประเภทเครื่องจักร</th>
                            <th class="px-3 py-2 text-left w-20">เครื่อง</th>
                            <th class="px-3 py-2 text-left w-28">สถานะ</th>
                            <th class="px-3 py-2 text-left min-w-[200px]">จุดตรวจ</th>
                            <th class="px-3 py-2 text-left min-w-[220px]">ภาพจุดตรวจสอบ</th>
                            <th class="px-3 py-2 text-left min-w-[220px]">คำแนะนำในการทำงาน</th>
                        </tr>
                    </thead>
                    <tbody id="imp-sheet-body"></tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center gap-2 mb-2">
                <h4 class="text-sm font-semibold text-slate-700 mr-2">รายการเครื่องจักรในไฟล์</h4>
                <button data-filter="" class="imp-filter px-3 py-1 rounded-full text-xs border border-slate-300">ทั้งหมด</button>
                <button data-filter="ok" class="imp-filter px-3 py-1 rounded-full text-xs border border-emerald-300 text-emerald-700">พร้อมนำเข้า</button>
                <button data-filter="not_found" class="imp-filter px-3 py-1 rounded-full text-xs border border-rose-300 text-rose-700">ไม่พบในระบบ</button>
                <button data-filter="other" class="imp-filter px-3 py-1 rounded-full text-xs border border-amber-300 text-amber-700">อื่น ๆ</button>
            </div>
            <div id="imp-row-grid" class="ag-theme-alpine" style="height: 400px; width: 100%;"></div>

            <div class="flex justify-end mt-4">
                <button id="imp-create-btn" class="btn-gradient px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                    <i data-lucide="file-plus-2" class="w-4 h-4"></i> สร้างเช็คชีต และไปกำหนดแผน PM
                </button>
            </div>
        </div>
    </div>

    <!-- ขั้นที่ 2: กรอกแผน PM (ลากคัดลอกได้) -->
    <div id="imp-step2" class="hidden bg-white p-5 rounded-xl shadow-sm border border-slate-200">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-3 gap-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-800">ขั้นที่ 2: กำหนดแผน PM</h3>
                <p class="text-[12px] text-slate-500">
                    <b>ความถี่</b>และ<b>วันที่เริ่ม</b>ตั้งค่าเริ่มต้นจาก Excel แล้ว (ระยะห่างในตาราง 4 สัปดาห์ = 30 วัน, เริ่มวันจันทร์ของสัปดาห์แรกที่มีกำหนด) กรุณาตรวจสอบ
                    และกรอกแจ้งเตือนล่วงหน้า · ลากมุมขวาล่างของเซลล์เพื่อคัดลอก · กรองคอลัมน์ได้จากเมนูหัวคอลัมน์<br>
                    ระบุวัน: วันในสัปดาห์ เช่น <b>จ., พฤ.</b> หรือวันที่ในเดือน เช่น <b>1, 15</b> (เฉพาะความถี่ที่ต้องระบุวัน) ·
                    บันทึกเฉพาะแถวที่กรอก ความถี่ + แจ้งเตือน + วันที่เริ่ม ครบ แถวที่เหลือยังอยู่ในตาราง
                </p>
            </div>
            <!-- มือถือ: ปุ่มเหลือแค่ไอคอน ตัวเลขอยู่ซ้าย ปุ่มชิดขวา -->
            <div class="flex items-center gap-2 w-full sm:w-auto shrink-0 whitespace-nowrap">
                <span id="imp-plan-count" class="text-[12px] text-slate-500 mr-auto sm:mr-0"></span>
                <button id="imp-fs-btn" type="button" title="ขยายเต็มจอ" aria-label="ขยายเต็มจอ" class="text-sky-600 hover:text-sky-800 bg-sky-50 px-2.5 sm:px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-1.5 transition-colors border border-sky-200">
                    <i data-lucide="maximize" class="w-4 h-4"></i> <span class="hidden sm:inline">ขยายเต็มจอ</span>
                </button>
                <button id="imp-save-btn" title="บันทึกแผน PM" aria-label="บันทึกแผน PM" class="btn-gradient px-2.5 sm:px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> <span class="hidden sm:inline">บันทึกแผน PM</span>
                </button>
            </div>
        </div>
        <div id="imp-plan-hot" style="height: 560px; width: 100%; overflow: hidden;"></div>
    </div>

    <?php if (($_SESSION['sess_user_level_es'] ?? '') === 'super_admin') { ?>
    <!-- Super Admin: ลบแผน PM ที่นำเข้าผิด (ทีละรอบการสร้าง) — handle_pm_bulk_delete.php -->
    <div id="bd-card" class="bg-white p-5 rounded-xl shadow-sm border border-red-200 mt-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-800 flex items-center gap-2"><i data-lucide="trash-2" class="w-5 h-5 text-red-500"></i> ลบแผน PM ที่นำเข้าผิด <span class="text-[11px] font-semibold text-red-600 bg-red-50 border border-red-100 rounded-full px-2 py-0.5">Super Admin</span></h3>
                <p class="text-[12px] text-slate-500 mt-0.5">หน่วยงาน <b id="bd-agency"></b> · การนำเข้า 1 ครั้งมักมี 2 แถว: <b class="text-violet-700">สร้างเช็คชีต</b> (ขั้นที่ 1) และ <b class="text-sky-700">บันทึกแผน PM</b> (ขั้นที่ 2) — ติ๊กแถวที่นำเข้าผิด แล้วกด ตรวจสอบก่อนลบ (ระบบสรุปให้ดูก่อน ยังไม่ลบ)</p>
            </div>
            <button id="bd-reload" type="button" class="border border-slate-300 text-slate-700 hover:bg-slate-50 px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-2"><i data-lucide="refresh-cw" class="w-4 h-4"></i> โหลดใหม่</button>
        </div>
        <div class="text-[12px] text-slate-600 bg-amber-50 border border-amber-100 rounded-lg p-3 mb-3 leading-relaxed">
            <div class="font-semibold text-slate-700 mb-1">เมื่อกดลบ ระบบจะ:</div>
            <ul class="list-disc pl-5 space-y-0.5">
                <li>ลบงาน PM ของแผนในแถวที่เลือก <b>ตามประเภทเครื่องจักรและแบบการลบที่เลือก</b> (ขั้นที่ 2 ด้านล่าง) — แผนที่ไม่เหลืองานเลยจะถูกลบ</li>
                <li>ถ้าลบเฉพาะ <b class="text-red-600">งานที่ยังไม่ทำ</b> — งานที่ทำแล้วเก็บไว้ครบ และแผนนั้นจะถูกปิดใช้งาน</li>
                <li>ถ้าลบ <b class="text-red-600">งานที่ทำแล้ว</b> — ลบใบงาน ผลตรวจ รูป ลายเซ็น ผลประเมินด้วย และ<b>คืนสต็อกอะไหล่</b>ที่ใบงานตัดไป</li>
                <li><b class="text-amber-700">ไม่ลบเช็คชีต</b> — เช็คชีตที่ไม่มีแผนใช้แล้วจะถูกล้างประเภทเครื่องจักร (จุดตรวจยังอยู่ นำกลับมาใช้ได้ที่แท็บสร้างเช็คชีต)</li>
            </ul>
            <div class="mt-1 text-red-600 font-medium">ลบแล้วกู้คืนไม่ได้</div>
        </div>
        <div class="overflow-auto border border-slate-200 rounded-lg" style="max-height: 420px">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600 text-[12px] sticky top-0 z-10">
                    <tr>
                        <th class="p-2 w-10 text-center"><input type="checkbox" id="bd-all" class="w-4 h-4 accent-red-600" title="เลือกทั้งหมด"></th>
                        <th class="p-2 text-left whitespace-nowrap">วันเวลา / รอบนี้ทำอะไร</th>
                        <th class="p-2 text-left">ผู้สร้าง</th>
                        <th class="p-2 text-left">เช็คชีต (ตัวอย่าง)</th>
                        <th class="p-2 text-right whitespace-nowrap">แผน PM</th>
                        <th class="p-2 text-right whitespace-nowrap">งานยังไม่ทำ</th>
                        <th class="p-2 text-right whitespace-nowrap">งานทำแล้ว</th>
                        <th class="p-2 text-right whitespace-nowrap">เช็คชีตที่สร้าง</th>
                    </tr>
                </thead>
                <tbody id="bd-body"><tr><td colspan="8" class="p-6 text-center text-slate-400">กำลังโหลด...</td></tr></tbody>
            </table>
        </div>
        <div id="bd-step2" class="hidden mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <h4 class="text-sm font-semibold text-slate-700">ขั้นที่ 2 · ประเภทเครื่องจักร</h4>
                    <label class="text-[12px] text-slate-500 flex items-center gap-1.5 cursor-pointer"><input type="checkbox" id="bd-type-all" class="w-4 h-4 accent-red-600" checked> ทุกประเภท</label>
                </div>
                <div id="bd-types" class="border border-slate-200 rounded-lg divide-y divide-slate-100 overflow-auto" style="max-height: 260px"></div>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-slate-700 mb-1.5">ลบงานแบบไหน</h4>
                <div class="space-y-2" id="bd-modes">
                    <label class="bd-mode flex gap-3 p-3 rounded-lg border-2 cursor-pointer"><input type="radio" name="bd-mode" value="pending" class="mt-0.5 accent-red-600" checked>
                        <span><span class="block text-sm font-semibold text-slate-800">เฉพาะงานที่ยังไม่ทำ <span class="text-[11px] font-medium text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded">แนะนำ</span></span>
                        <span class="block text-xs text-slate-500">งานที่ทำแล้วเก็บไว้ครบ แผนที่มีงานทำแล้วจะถูกปิดใช้งาน</span></span></label>
                    <label class="bd-mode flex gap-3 p-3 rounded-lg border-2 cursor-pointer"><input type="radio" name="bd-mode" value="done" class="mt-0.5 accent-red-600">
                        <span><span class="block text-sm font-semibold text-slate-800">เฉพาะงานที่ทำแล้ว</span>
                        <span class="block text-xs text-slate-500">ลบใบงาน ผลตรวจ รูป ลายเซ็น ผลประเมิน และคืนสต็อกอะไหล่ — งานที่ยังไม่ทำคงไว้</span></span></label>
                    <label class="bd-mode flex gap-3 p-3 rounded-lg border-2 cursor-pointer"><input type="radio" name="bd-mode" value="all" class="mt-0.5 accent-red-600">
                        <span><span class="block text-sm font-semibold text-red-700">ทั้งหมด (ยังไม่ทำ + ทำแล้ว)</span>
                        <span class="block text-xs text-slate-500">ลบแผนและงานทั้งหมดของประเภทที่เลือก รวมใบงานที่ทำแล้ว และคืนสต็อกอะไหล่</span></span></label>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2 mt-3">
            <span id="bd-selinfo" class="text-[12px] text-slate-500">ยังไม่ได้เลือก</span>
            <button id="bd-preview" type="button" disabled class="bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2"><i data-lucide="search-check" class="w-4 h-4"></i> ตรวจสอบก่อนลบ</button>
        </div>
    </div>
    <?php } ?>
</div>

<script>
pmLazy('import', function() {   // โหลดเมื่อเปิดแท็บครั้งแรก (ดู pmLazy ใน pm.php)
    const STATUS_TEXT = { ok: 'พร้อมนำเข้า', not_found: 'ไม่พบในระบบ', no_schedule: 'ไม่มีกำหนดการ', duplicate: 'แถวซ้ำ' };
    const DAY_OPTIONS = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.', 'อา.'];
    const WEEKDAYS = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.'];

    let workbook = null;
    let parsedRows = [];
    let freqOptions = [];
    let alertOptions = [];
    let contract = { start: '', end: '' };
    let defaultAlert = '';
    let agencyName = '';
    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const LEGEND = 'D = Day W= Week M = Month Q = Quarter H = Half Year Y = Year R = Reading * =Multiple check Work Order Load';
    let sheetList = [];
    let attachState = {};   // sheet key => { images: File[], docs: File[] }
    let itemState = {};     // sheet key => [จุดตรวจ] ของเช็คชีตใหม่ (ส่งไปตอน create_sheets)
    let rowGridApi = null;
    const MAX_IMAGES = 5, MAX_DOCS = 5, MAX_DOC_BYTES = 5 * 1024 * 1024;
    let hot = null;

    const $ = id => document.getElementById(id);
    const freqByDesc = desc => freqOptions.find(o => o.desc === desc);
    const alertByDesc = desc => alertOptions.find(a => a.desc === desc);

    async function loadOptions() {
        try {
            const res = await axios.get('handle_pm_import.php?action=get_options');
            if (!res.data.success) throw new Error(res.data.error);
            freqOptions = res.data.data.freqs;
            alertOptions = res.data.data.alerts;
            contract = res.data.data.contract;
            defaultAlert = res.data.data.default_alert || '';
            agencyName = res.data.data.agency || '';
            setAgencyDefaults(res.data.data.default_items, res.data.data.default_items_custom);
            if (contract.start || contract.end) $('imp-contract').innerText = `· ระยะเวลาสัญญา: ${contract.start} ถึง ${contract.end}`;
        } catch (e) {
            Swal.fire('ข้อผิดพลาด', 'โหลดตัวเลือกไม่สำเร็จ: ' + e.message, 'error');
        }
    }

    // ---------- Template (รูปแบบเดียวกับไฟล์ Yearly PM Schedule) ----------
    $('imp-template-btn').addEventListener('click', async () => {
        const year = parseInt($('imp-year').value, 10) || parseInt((contract.start || '').slice(0, 4), 10) || new Date().getFullYear();
        let machines = [];
        try {
            const res = await axios.get('handle_pm_import.php?action=machines');
            if (res.data.success) machines = res.data.data;
        } catch (e) { /* ไม่มีรายการเครื่องก็ยังดาวน์โหลด Template เปล่าได้ */ }

        const W = 3 + 48;                 // Name, CODE, Location + 48 สัปดาห์
        const blank = n => Array(n).fill('');
        const aoa = [
            [`YEARLY PREVENTIVE MAINTENANCE SCHEDULE ${agencyName ? agencyName + ' ' : ''}FOR JANUARY ${year} - DECEMBER ${year}`, ...blank(W - 1)],
            ['ส่วนงานวิศวกรรม บริษัท โปรแอ็คทีฟ แมเนจเม้นท์ จำกัด', ...blank(W - 1)],
            ['Name', 'CODE', 'Location', ...MONTHS.flatMap(m => [m, '', '', ''])],
            ['', '', '', ...Array.from({ length: 48 }, (_, k) => k + 1)]
        ];
        // ใส่เครื่องจักรของหน่วยงานไว้ให้ (Name = ประเภทเครื่องจักร, CODE = เลขครุภัณฑ์) — กรอกตัวอักษรในช่องสัปดาห์
        machines.forEach(m => aoa.push([m.TGroupName || m.asset_name || '', m.ass_code, (m.location || '').trim(), ...blank(48)]));
        const footer = aoa.length;
        aoa.push(['หมายเหตุ', ...blank(19), LEGEND, ...blank(W - 21)]);
        aoa.push(['ผู้จัดเตรียม..', ...blank(19), 'วันที่', ...blank(W - 21)]);
        aoa.push(['ผู้อนุมัติ', ...blank(19), 'วันที่', ...blank(W - 21)]);

        const ws = XLSX.utils.aoa_to_sheet(aoa);
        const R = (r1, c1, r2, c2) => ({ s: { r: r1, c: c1 }, e: { r: r2, c: c2 } });
        ws['!merges'] = [
            R(0, 0, 0, W - 1), R(1, 0, 1, W - 1),
            ...MONTHS.map((_, i) => R(2, 3 + i * 4, 2, 6 + i * 4)),
            ...[0, 1, 2].flatMap(i => [R(footer + i, 0, footer + i, 19), R(footer + i, 20, footer + i, 31)])
        ];
        ws['!cols'] = [{ wch: 28 }, { wch: 16 }, { wch: 28 }, ...Array(48).fill({ wch: 3.5 })];

        const guide = XLSX.utils.aoa_to_sheet([
            ['วิธีกรอก Template นำเข้าแผน PM'],
            [''],
            ['1. Name', 'ชื่อเช็คชีต (เครื่องที่มี Name เดียวกันในประเภทเดียวกันจะใช้เช็คชีตเดียวกัน)'],
            ['2. CODE', 'เลขครุภัณฑ์ ต้องตรงกับในระบบ (ใส่รายการเครื่องของหน่วยงานไว้ให้แล้ว)'],
            ['3. Location', 'ตำแหน่งที่ตั้ง'],
            ['4. ช่องสัปดาห์ 1-48', 'เดือนละ 4 สัปดาห์ ใส่ตัวอักษรในสัปดาห์ที่ต้องทำ PM'],
            ['', LEGEND],
            [''],
            ['ระบบจะตั้งค่าเริ่มต้นให้', ''],
            ['- วันที่เริ่ม', 'วันจันทร์ของสัปดาห์แรกที่มีกำหนด (สัปดาห์ที่ k = วันจันทร์ที่ k ของเดือน)'],
            ['- ความถี่', 'ตามระยะห่างระหว่างช่อง (4 สัปดาห์ = 30 วัน เช่น ห่าง 8 ช่อง = ทุก 60 วัน)'],
            ['- แจ้งเตือนล่วงหน้า', '8 โมงเช้าของวันนั้น'],
            ['', 'ตรวจสอบและแก้ไขได้ในหน้าตรวจสอบก่อนบันทึก'],
            [''],
            ['ห้ามแก้ไข', 'หัวตาราง Name / CODE / Location และชื่อเดือน (ระบบใช้หาตำแหน่งคอลัมน์)']
        ]);
        guide['!cols'] = [{ wch: 24 }, { wch: 90 }];

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, `PM ${year}`);
        XLSX.utils.book_append_sheet(wb, guide, 'วิธีกรอก');
        XLSX.writeFile(wb, `Template_Import_PM_${year}.xlsx`);
    });

    // ---------- ขั้นที่ 1: อ่าน Excel ----------
    $('imp-file').addEventListener('change', async (ev) => {
        const file = ev.target.files[0];
        if (!file) return;
        try {
            workbook = XLSX.read(await file.arrayBuffer(), { type: 'array' });
        } catch (e) {
            Swal.fire('ข้อผิดพลาด', 'อ่านไฟล์ Excel ไม่ได้', 'error');
            return;
        }
        const sel = $('imp-sheet');
        sel.innerHTML = workbook.SheetNames.map(n => `<option value="${n}">${n}</option>`).join('');
        sel.disabled = false;
        const first = workbook.SheetNames.find(n => findHeader(sheetRows(n)) >= 0);
        if (first) sel.value = first;
        parseSheet();
    });
    $('imp-sheet').addEventListener('change', parseSheet);

    function sheetRows(name) {
        return XLSX.utils.sheet_to_json(workbook.Sheets[name], { header: 1, defval: '', raw: false });
    }

    function findHeader(rows) {
        return rows.findIndex(r => r.some(c => /^name$/i.test(String(c).trim())) && r.some(c => /^code$/i.test(String(c).trim())));
    }

    // อ่านแถวเครื่องจักร: Name / CODE (เลขครุภัณฑ์) / Location และตัวอักษรความถี่ในช่องสัปดาห์
    function parseSheet() {
        $('imp-preview').classList.add('hidden');
        const rows = sheetRows($('imp-sheet').value);
        const h = findHeader(rows);
        if (h < 0) {
            Swal.fire('รูปแบบไฟล์ไม่ถูกต้อง', 'ไม่พบหัวตารางที่มีคอลัมน์ Name และ CODE ในชีตนี้', 'warning');
            return;
        }
        const head = rows[h].map(c => String(c).trim().toLowerCase());
        const nameCol = head.indexOf('name');
        const codeCol = head.indexOf('code');
        const locCol = head.indexOf('location');
        let weekCol = head.findIndex(c => /^jan/.test(c));
        if (weekCol < 0) weekCol = Math.max(nameCol, codeCol, locCol) + 1;

        // ปีจากหัวเรื่อง เช่น "... JANUARY 2026 - DECEMBER 2026"
        const titleYear = rows.slice(0, h).flat().join(' ').match(/\b(20\d{2})\b/);
        $('imp-year').value = titleYear ? titleYear[1] : new Date().getFullYear();

        parsedRows = [];
        for (let i = h + 1; i < rows.length; i++) {
            const r = rows[i];
            const code = String(r[codeCol] ?? '').trim();
            if (!code) continue;
            const cells = {};   // ช่องสัปดาห์ 0-47 (เดือนละ 4 ช่อง) => ตัวอักษร
            for (let k = 0; k < 48; k++) {
                const v = String(r[weekCol + k] ?? '').trim().toUpperCase();
                if (/^[A-Z*]$/.test(v)) cells[k] = v;
            }
            parsedRows.push({
                row: i + 1,
                name: String(r[nameCol] ?? '').trim(),
                code: code,
                location: locCol >= 0 ? String(r[locCol] ?? '').trim() : '',
                cells: cells
            });
        }
        if (!parsedRows.length) return Swal.fire('แจ้งเตือน', 'ไม่พบข้อมูลเครื่องจักรในชีตนี้', 'warning');
        runPreview();
    }

    function statTile(label, value, color) {
        return `<div class="rounded-lg border border-slate-200 p-3">
            <div class="text-[12px] text-slate-500">${label}</div>
            <div class="text-xl font-semibold ${color}">${Number(value).toLocaleString()}</div></div>`;
    }

    const gridBase = { pagination: true, paginationPageSize: 50, paginationPageSizeSelector: [20, 50, 100, 500],
                       defaultColDef: { flex: 1, minWidth: 100, sortable: true, resizable: true, filter: true },
                       rowHeight: 40, headerHeight: 44 };

    async function runPreview() {
        Swal.fire({ title: 'กำลังตรวจสอบข้อมูล...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const res = await axios.post('handle_pm_import.php?action=preview', { rows: parsedRows });
            if (!res.data.success) throw new Error(res.data.error);
            Swal.close();
            renderPreview(res.data.data);
        } catch (e) {
            Swal.fire('ข้อผิดพลาด', e.message, 'error');
        }
    }

    function renderPreview(data) {
        const s = data.stats;
        $('imp-stats').innerHTML =
            statTile('เครื่องในไฟล์', s.rows, 'text-slate-800') +
            statTile('พร้อมนำเข้า', s.matched, 'text-emerald-600') +
            statTile('ไม่พบในระบบ', s.not_found, 'text-rose-600') +
            statTile('เช็คชีตใหม่', s.sheets_new, 'text-sky-700') +
            statTile('ใช้เช็คชีตเดิม', s.sheets_reused, 'text-slate-600');
        $('imp-create-btn').disabled = s.matched === 0;
        $('imp-create-btn').classList.toggle('opacity-50', s.matched === 0);

        renderSheetTable(data.sheets);

        const rowCols = [
            { headerName: 'แถว', field: 'row', maxWidth: 90 },
            { headerName: 'Name', field: 'name' },
            { headerName: 'เลขครุภัณฑ์', field: 'code' },
            { headerName: 'Location', field: 'location' },
            { headerName: 'เครื่องในระบบ', field: 'machine' },
            { headerName: 'เช็คชีต', field: 'sheets', flex: 2, valueFormatter: p => (p.value || []).join(', ') },
            { headerName: 'สถานะ', field: 'status', maxWidth: 150, valueFormatter: p => STATUS_TEXT[p.value] || p.value,
              cellClass: p => p.value === 'ok' ? 'text-emerald-600' : (p.value === 'not_found' ? 'text-rose-600' : 'text-amber-600') },
            { headerName: 'หมายเหตุ', field: 'message', flex: 2 }
        ];

        window.impRows = data.rows;
        if (rowGridApi) rowGridApi.setGridOption('rowData', data.rows);
        else rowGridApi = agGrid.createGrid($('imp-row-grid'), { ...gridBase, columnDefs: rowCols, rowData: data.rows });

        $('imp-preview').classList.remove('hidden');
        lucide.createIcons();
    }

    // ---------- ตารางเช็คชีต + ไฟล์แนบ ----------
    const escHtml = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function renderSheetTable(sheets) {
        sheetList = sheets;
        // เก็บไฟล์ที่เลือกไว้แล้วของเช็คชีตที่ยังอยู่
        const keep = {};
        const keepItems = {};
        sheets.forEach(s => {
            if (s.existing_id) return;
            keep[s.key] = attachState[s.key] || { images: [], docs: [] };
            keepItems[s.key] = itemState[s.key] || defaultItems();
        });
        attachState = keep;
        itemState = keepItems;

        $('imp-sheet-body').innerHTML = sheets.map((s, i) => {
            const isNew = !s.existing_id;
            const attachCell = (kind, accept, btnClass, label) => isNew ? `
                <label class="inline-flex items-center gap-1 px-2 py-1 rounded border text-xs cursor-pointer ${btnClass}">
                    <i data-lucide="paperclip" class="w-3 h-3"></i> ${label}
                    <input type="file" multiple ${accept} class="hidden imp-attach" data-idx="${i}" data-kind="${kind}">
                </label>
                <div id="imp-${kind}-${i}" class="flex flex-wrap gap-1 mt-1"></div>`
                : `<span class="text-[11px] text-slate-400">แนบได้ที่แท็บสร้างเช็คชีต</span>`;
            return `<tr class="border-t border-slate-100 align-top">
                <td class="px-3 py-2 font-medium text-slate-800">${escHtml(s.name)}
                    <div class="text-[11px] font-normal text-slate-400">ใน Excel: ${escHtml((s.letters || []).join(', '))}</div></td>
                <td class="px-3 py-2">${escHtml(s.group_name)}</td>
                <td class="px-3 py-2">${s.machines}</td>
                <td class="px-3 py-2 ${isNew ? 'text-sky-700 font-medium' : 'text-slate-500'}">${isNew ? 'สร้างใหม่' : 'ใช้เช็คชีตเดิม'}</td>
                <td class="px-3 py-2" id="imp-items-${i}">${isNew ? itemsCell(i) : `<span class="text-[11px] text-slate-400">ใช้จุดตรวจของเช็คชีตเดิม</span>`}</td>
                <td class="px-3 py-2">${attachCell('images', 'accept="image/*"', 'border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100', 'เลือกรูป')}</td>
                <td class="px-3 py-2">${attachCell('docs', '', 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100', 'เลือกไฟล์')}</td>
            </tr>`;
        }).join('');
        sheets.forEach((s, i) => { if (!s.existing_id) { renderChips(i, 'images'); renderChips(i, 'docs'); } });
    }

    function renderChips(i, kind) {
        const box = $(`imp-${kind}-${i}`);
        if (!box) return;
        const files = attachState[sheetList[i].key][kind];
        box.innerHTML = files.map((f, fi) => `
            <span class="inline-flex items-center gap-1 max-w-[200px] px-2 py-0.5 rounded-full bg-slate-100 text-[11px] text-slate-700">
                <span class="truncate" title="${escHtml(f.name)}">${escHtml(f.name)}</span>
                <button type="button" class="imp-attach-del text-rose-500 hover:text-rose-700" data-idx="${i}" data-kind="${kind}" data-fi="${fi}">&times;</button>
            </span>`).join('') +
            (files.length ? `<span class="text-[11px] text-slate-400">${files.length}/${kind === 'images' ? MAX_IMAGES : MAX_DOCS}</span>` : '');
        lucide.createIcons();
    }

    // ---------- จุดตรวจของเช็คชีตใหม่ ----------
    const CHECK_TYPES = [{ id: 1, label: 'ผ่าน / ไม่ผ่าน' }, { id: 2, label: 'ผ่าน / ไม่ผ่าน / ไม่เกี่ยวข้อง' }];
    const PHOTO_REQS = [{ id: 2, label: 'ไม่บังคับ' }, { id: 1, label: 'บังคับ' }, { id: 3, label: 'บังคับเฉพาะกรณีที่ไม่ผ่าน' }];
    // คอลัมน์ในหน้าต่างแก้ไข (ลำดับเดียวกับการวางจาก Excel)
    const ITEM_COLS = [
        { f: 'check_point', label: 'จุดตรวจ', req: true, w: 180 },
        { f: 'standard_text', label: 'มาตรฐาน', w: 160 },
        { f: 'method_text', label: 'วิธีตรวจ', w: 160 },
        { f: 'action_abnormal', label: 'การแก้ไขเมื่อผิดปกติ', w: 160 },
        { f: 'check_type_id', label: 'ผลตรวจ', opts: CHECK_TYPES, w: 170 },
        { f: 'photo_required_id', label: 'ถ่ายรูป', opts: PHOTO_REQS, w: 170 },
        { f: 'value_name', label: 'ค่าที่วัด', w: 110 },
        { f: 'unit', label: 'หน่วย', w: 80 },
        { f: 'expected_value', label: 'ค่ามาตรฐาน', w: 100 },
    ];
    const blankItem = () => ({ check_point: '', standard_text: '', method_text: '', action_abnormal: '', check_type_id: 1, photo_required_id: 2, value_name: '', unit: '', expected_value: '' });

    // จุดตรวจเริ่มต้นของหน่วยงาน (ตั้งค่าได้ที่ปุ่ม "ตั้งค่าจุดตรวจเริ่มต้น") — โหลดมากับ get_options
    let agencyDefaults = [{ ...blankItem(), check_point: 'ตรวจสอบอุปกรณ์', standard_text: 'ตรวจสอบอุปกรณ์', method_text: 'ตรวจสอบอุปกรณ์' }];
    let agencyDefaultsCustom = false;
    const defaultItems = () => agencyDefaults.map(it => ({ ...it }));
    function setAgencyDefaults(items, custom) {
        if (Array.isArray(items) && items.length) agencyDefaults = items.map(it => ({ ...blankItem(), ...it }));
        agencyDefaultsCustom = !!custom;
        const names = agencyDefaults.map(it => it.check_point);
        $('imp-default-desc').innerHTML = `จุดตรวจเริ่มต้น${agencyDefaultsCustom ? 'ของหน่วยงาน' : 'ของระบบ'} ${names.length} จุด`
            + ` (${escHtml(names.slice(0, 3).join(', '))}${names.length > 3 ? ' ...' : ''})`;
    }

    function itemsCell(i) {
        const items = itemState[sheetList[i].key] || [];
        const names = items.slice(0, 3).map(it => `<li class="truncate max-w-[220px]" title="${escHtml(it.check_point)}">${escHtml(it.check_point)}</li>`).join('');
        return `<ul class="text-[12px] text-slate-600 list-disc pl-4">${names}${items.length > 3 ? `<li class="list-none -ml-4 text-slate-400">และอีก ${items.length - 3} จุด</li>` : ''}</ul>
            <button type="button" class="imp-items-edit mt-1 inline-flex items-center gap-1 px-2 py-1 rounded border border-violet-200 bg-violet-50 text-violet-700 hover:bg-violet-100 text-xs" data-idx="${i}">
                <i data-lucide="list-plus" class="w-3 h-3"></i> แก้ไขจุดตรวจ (${items.length})</button>`;
    }

    function refreshItemsCells() {
        sheetList.forEach((s, i) => { const td = $(`imp-items-${i}`); if (td && !s.existing_id) td.innerHTML = itemsCell(i); });
        lucide.createIcons();
    }

    function itemRowHtml(it, r) {
        return `<tr data-r="${r}" class="border-t border-slate-100 align-top">
            <td class="px-1 py-1 text-center text-slate-400 text-xs pt-2.5">${r + 1}</td>
            ${ITEM_COLS.map(c => `<td class="px-1 py-1">${c.opts
                ? `<select data-f="${c.f}" class="w-full border border-slate-300 rounded px-1.5 py-1.5 text-[13px]" style="min-width:${c.w}px">${c.opts.map(o => `<option value="${o.id}" ${+it[c.f] === o.id ? 'selected' : ''}>${o.label}</option>`).join('')}</select>`
                : `<input data-f="${c.f}" value="${escHtml(it[c.f])}" class="w-full border border-slate-300 rounded px-2 py-1.5 text-[13px]" style="min-width:${c.w}px">`}</td>`).join('')}
            <td class="px-1 py-1"><button type="button" class="imp-it-del p-1.5 text-rose-500 hover:bg-rose-50 rounded" title="ลบจุดตรวจ">&times;</button></td>
        </tr>`;
    }

    // อ่านค่าจากตารางในหน้าต่างแก้ไข
    function readItemRows(tbody) {
        return [...tbody.querySelectorAll('tr')].map(tr => {
            const it = {};
            tr.querySelectorAll('[data-f]').forEach(el => { it[el.dataset.f] = el.tagName === 'SELECT' ? +el.value : el.value.trim(); });
            return it;
        });
    }

    /**
     * หน้าต่างแก้ไขจุดตรวจ (ใช้ทั้งแก้ไขรายเช็คชีต และตั้งค่าจุดตรวจเริ่มต้นของหน่วยงาน)
     * opts: { title, rows, note, footerHtml, confirmText, denyText } => { items, all, denied } | null (ยกเลิก)
     */
    async function openItemEditor(opts) {
        let rows = (opts.rows || []).map(it => ({ ...it }));
        if (!rows.length) rows = [blankItem()];

        const res = await Swal.fire({
            title: opts.title,
            width: '95vw',
            html: `<div class="text-left">
                ${opts.note || ''}
                <p class="text-[12px] text-slate-500 mb-2">วางข้อมูลจาก Excel ได้ (คัดลอกหลายแถว/หลายคอลัมน์ ตามลำดับคอลัมน์ด้านล่าง แล้ววางในช่องเริ่มต้น) ·
                    กรอก <b>ค่ามาตรฐาน</b> เมื่อต้องการให้ช่างกรอกค่าที่วัดได้</p>
                <div class="overflow-auto border border-slate-200 rounded-lg" style="max-height:55vh">
                    <table class="w-full text-sm"><thead class="bg-slate-50 text-slate-600 text-[12px] sticky top-0 z-10"><tr>
                        <th class="px-1 py-2 w-8">#</th>
                        ${ITEM_COLS.map(c => `<th class="px-1 py-2 text-left font-medium whitespace-nowrap">${c.label}${c.req ? ' <span class="text-rose-500">*</span>' : ''}</th>`).join('')}
                        <th class="w-8"></th></tr></thead>
                    <tbody id="imp-it-body">${rows.map(itemRowHtml).join('')}</tbody></table>
                </div>
                <div class="flex flex-wrap items-center gap-3 mt-3">
                    <button type="button" id="imp-it-add" class="px-3 py-1.5 rounded-lg border border-sky-300 text-sky-700 hover:bg-sky-50 text-sm">+ เพิ่มจุดตรวจ</button>
                    ${opts.footerHtml || ''}
                </div></div>`,
            showCancelButton: true, confirmButtonText: opts.confirmText || 'บันทึกจุดตรวจ', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#006B9F',
            showDenyButton: !!opts.denyText, denyButtonText: opts.denyText || '', denyButtonColor: '#64748b',
            didOpen: () => {
                const body = $('imp-it-body');
                const renumber = () => body.querySelectorAll('tr').forEach((tr, r) => { tr.dataset.r = r; tr.cells[0].textContent = r + 1; });
                $('imp-it-add').addEventListener('click', () => {
                    body.insertAdjacentHTML('beforeend', itemRowHtml(blankItem(), body.rows.length));
                    body.lastElementChild.querySelector('input').focus();
                });
                body.addEventListener('click', e => {
                    const del = e.target.closest('.imp-it-del');
                    if (!del) return;
                    del.closest('tr').remove();
                    if (!body.rows.length) body.insertAdjacentHTML('beforeend', itemRowHtml(blankItem(), 0));
                    renumber();
                });
                // วางหลายแถว/หลายคอลัมน์จาก Excel: กระจายลงตาราง เริ่มจากช่องที่วาง
                body.addEventListener('paste', e => {
                    const el = e.target.closest('[data-f]');
                    const text = (e.clipboardData || window.clipboardData).getData('text');
                    if (!el || !/[\t\n]/.test(text.trim())) return;
                    e.preventDefault();
                    const lines = text.replace(/\r/g, '').replace(/\n+$/, '').split('\n').map(l => l.split('\t'));
                    const r0 = +el.closest('tr').dataset.r, c0 = ITEM_COLS.findIndex(c => c.f === el.dataset.f);
                    lines.forEach((cells, dr) => {
                        while (body.rows.length <= r0 + dr) body.insertAdjacentHTML('beforeend', itemRowHtml(blankItem(), body.rows.length));
                        const tr = body.rows[r0 + dr];
                        cells.forEach((v, dc) => {
                            const col = ITEM_COLS[c0 + dc];
                            if (!col) return;
                            const target = tr.querySelector(`[data-f="${col.f}"]`);
                            v = v.trim();
                            if (col.opts) {
                                const o = col.opts.find(o => o.label === v || String(o.id) === v);
                                if (o) target.value = o.id;
                            } else target.value = v;
                        });
                    });
                });
            },
            preConfirm: () => {
                const all = readItemRows($('imp-it-body'));
                const items = all.filter(it => ITEM_COLS.some(c => !c.opts && it[c.f] !== ''));
                const errs = [];
                items.forEach(it => {
                    const n = all.indexOf(it) + 1;
                    if (!it.check_point) errs.push(`แถว ${n}: กรุณากรอกจุดตรวจ`);
                    if (it.expected_value && it.expected_value !== '-' && !it.value_name) errs.push(`แถว ${n}: มีค่ามาตรฐาน กรุณากรอกชื่อค่าที่วัด`);
                });
                if (!items.length) errs.push('ต้องมีจุดตรวจอย่างน้อย 1 จุด');
                if (errs.length) { Swal.showValidationMessage(errs.slice(0, 5).join('<br>')); return false; }
                return { items, all: !!$('imp-it-all')?.checked };
            }
        });
        if (res.isDenied) return { denied: true };
        return res.isConfirmed ? res.value : null;
    }

    async function editSheetItems(i) {
        const sheet = sheetList[i];
        const newCount = sheetList.filter(s => !s.existing_id).length;
        const res = await openItemEditor({
            title: 'จุดตรวจ: ' + escHtml(sheet.name),
            rows: itemState[sheet.key] || [],
            footerHtml: newCount > 1 ? `<label class="ml-auto inline-flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" id="imp-it-all"> ใช้จุดตรวจชุดนี้กับเช็คชีตใหม่ทั้งหมด (${newCount} ชุด)</label>` : '',
        });
        if (!res) return;
        const { items, all } = res;
        sheetList.forEach(s => {
            if (!s.existing_id && (all || s.key === sheet.key)) itemState[s.key] = items.map(it => ({ ...it }));
        });
        refreshItemsCells();
    }

    $('imp-sheet-body').addEventListener('click', (ev) => {
        const btn = ev.target.closest('.imp-items-edit');
        if (btn) editSheetItems(+btn.dataset.idx);
    });

    // ตั้งค่าจุดตรวจเริ่มต้นของหน่วยงาน (บันทึกที่เซิร์ฟเวอร์ ใช้กับการนำเข้าครั้งต่อ ๆ ไป)
    $('imp-defaults-btn').addEventListener('click', async () => {
        const res = await openItemEditor({
            title: 'ตั้งค่าจุดตรวจเริ่มต้น' + (agencyName ? ' · ' + escHtml(agencyName) : ''),
            rows: defaultItems(),
            note: `<div class="mb-3 p-3 rounded-lg bg-sky-50 border border-sky-100 text-[13px] text-sky-900">
                จุดตรวจชุดนี้จะใส่ให้ <b>เช็คชีตใหม่ทุกชุดที่สร้างจากการนำเข้า Excel</b> ของหน่วยงานนี้ (แก้ไขรายเช็คชีตได้อีกครั้งก่อนสร้าง)
                — ${agencyDefaultsCustom ? 'ตอนนี้ใช้ค่าที่หน่วยงานตั้งไว้' : 'ตอนนี้ใช้ค่าเริ่มต้นของระบบ'}</div>`,
            confirmText: 'บันทึกเป็นค่าเริ่มต้น',
            denyText: agencyDefaultsCustom ? 'คืนค่าเริ่มต้นของระบบ' : '',
        });
        if (!res) return;
        try {
            const r = await axios.post('handle_pm_import.php?action=save_defaults', res.denied ? { reset: true } : { items: res.items });
            if (!r.data.success) throw new Error(r.data.error);
            setAgencyDefaults(r.data.data.default_items, r.data.data.default_items_custom);
        } catch (e) {
            Swal.fire('ข้อผิดพลาด', 'บันทึกจุดตรวจเริ่มต้นไม่สำเร็จ: ' + e.message, 'error');
            return;
        }
        // เช็คชีตใหม่ในไฟล์ที่เปิดอยู่ซึ่งยังใช้ค่าเริ่มต้นเดิม => ถามว่าจะใช้ชุดใหม่ไหม
        const pending = sheetList.filter(s => !s.existing_id);
        if (pending.length) {
            const ask = await Swal.fire({ icon: 'success', title: 'บันทึกจุดตรวจเริ่มต้นแล้ว',
                html: `ใช้จุดตรวจชุดใหม่กับเช็คชีตใหม่ ${pending.length} ชุดในไฟล์ที่เปิดอยู่ด้วยไหม?<br><span class="text-xs text-slate-500">จุดตรวจที่แก้ไขรายเช็คชีตไว้จะถูกแทนที่</span>`,
                showCancelButton: true, confirmButtonText: 'ใช้กับไฟล์นี้ด้วย', cancelButtonText: 'ไม่ต้อง', confirmButtonColor: '#006B9F' });
            if (ask.isConfirmed) {
                pending.forEach(s => { itemState[s.key] = defaultItems(); });
                refreshItemsCells();
            }
        } else {
            Swal.fire({ icon: 'success', title: 'บันทึกจุดตรวจเริ่มต้นแล้ว', timer: 1400, showConfirmButton: false });
        }
    });

    $('imp-sheet-body').addEventListener('change', (ev) => {
        const input = ev.target.closest('.imp-attach');
        if (!input) return;
        const i = +input.dataset.idx, kind = input.dataset.kind;
        const list = attachState[sheetList[i].key][kind];
        const max = kind === 'images' ? MAX_IMAGES : MAX_DOCS;
        const problems = [];
        Array.from(input.files).forEach(f => {
            if (list.some(x => x.name === f.name && x.size === f.size)) return;
            if (list.length >= max) { problems.push(`เกิน ${max} ${kind === 'images' ? 'รูป' : 'ไฟล์'}: ${f.name}`); return; }
            if (kind === 'images' && !f.type.startsWith('image/')) { problems.push(`ไม่ใช่รูปภาพ: ${f.name}`); return; }
            if (kind === 'docs' && f.size > MAX_DOC_BYTES) { problems.push(`ใหญ่กว่า 5MB: ${f.name}`); return; }
            list.push(f);
        });
        input.value = '';
        renderChips(i, kind);
        if (problems.length) Swal.fire({ icon: 'warning', title: 'บางไฟล์ไม่ถูกเพิ่ม', html: problems.map(escHtml).join('<br>') });
    });

    $('imp-sheet-body').addEventListener('click', (ev) => {
        const btn = ev.target.closest('.imp-attach-del');
        if (!btn) return;
        const i = +btn.dataset.idx, kind = btn.dataset.kind;
        attachState[sheetList[i].key][kind].splice(+btn.dataset.fi, 1);
        renderChips(i, kind);
    });

    document.querySelectorAll('.imp-filter').forEach(btn => btn.addEventListener('click', () => {
        if (!rowGridApi) return;
        const f = btn.dataset.filter;
        const rows = (window.impRows || []).filter(r => !f || (f === 'other' ? !['ok', 'not_found'].includes(r.status) : r.status === f));
        rowGridApi.setGridOption('rowData', rows);
    }));

    $('imp-create-btn').addEventListener('click', async () => {
        const ok = await Swal.fire({
            title: 'สร้างเช็คชีต?',
            html: 'ระบบจะสร้างเช็คชีตที่ยังไม่มี แล้วแสดงตารางให้กำหนดแผน PM<br>เครื่องที่ไม่พบในระบบจะถูกข้าม',
            icon: 'question', showCancelButton: true, confirmButtonText: 'สร้างเช็คชีต', cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#006B9F'
        });
        if (!ok.isConfirmed) return;

        Swal.fire({ title: 'กำลังสร้างเช็คชีต...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const res = await axios.post('handle_pm_import.php?action=create_sheets', { rows: parsedRows, year: parseInt($('imp-year').value, 10), items: itemState });
            if (!res.data.success) throw new Error(res.data.error);
            const d = res.data.data;

            // อัปโหลดไฟล์แนบทีละเช็คชีต (กันเกินขีดจำกัดจำนวน/ขนาดไฟล์ต่อ request ของเซิร์ฟเวอร์)
            const uploads = Object.entries(attachState).filter(([key, a]) => d.created_ids[key] && (a.images.length || a.docs.length));
            const failed = [];
            for (let n = 0; n < uploads.length; n++) {
                const [key, a] = uploads[n];
                Swal.update({ title: 'กำลังอัปโหลดไฟล์แนบ...', html: `${n + 1} / ${uploads.length}` });
                Swal.showLoading();
                const fd = new FormData();
                fd.append('checksheet_id', d.created_ids[key]);
                a.images.forEach(f => fd.append('images[]', f));
                a.docs.forEach(f => fd.append('docs[]', f));
                try {
                    const up = await axios.post('handle_pm_import.php?action=upload_attachments', fd);
                    if (!up.data.success) throw new Error(up.data.error);
                } catch (e) {
                    failed.push(`${sheetList.find(s => s.key === key)?.name || key}: ${e.message}`);
                }
            }

            const msg = [`สร้างเช็คชีตใหม่ ${d.sheets_created} ชุด`];
            if (uploads.length) msg.push(`แนบไฟล์ ${uploads.length - failed.length} / ${uploads.length} เช็คชีต`);
            if (d.sheets_reused) msg.push(`ใช้เช็คชีตเดิม ${d.sheets_reused} ชุด`);
            if (d.plans_skipped) msg.push(`ข้าม ${d.plans_skipped} รายการที่มีแผนอยู่แล้ว`);
            if (failed.length) {
                msg.push(`<div class="text-left text-sm mt-2 text-rose-600">แนบไฟล์ไม่สำเร็จ (แนบเพิ่มได้ที่แท็บสร้างเช็คชีต):<br>${failed.map(escHtml).join('<br>')}</div>`);
            }
            await Swal.fire({ icon: failed.length ? 'warning' : 'success', title: 'สร้างเช็คชีตเรียบร้อย', html: msg.join('<br>') });
            attachState = {};
            itemState = {};
            renderPlanTable(d.plan_rows);
        } catch (e) {
            Swal.fire('ข้อผิดพลาด', e.message, 'error');
        }
    });

    // ---------- ขั้นที่ 2: ตารางกรอกแผน ----------
    function rowMulti(row) {
        const f = freqByDesc(hot.getDataAtRowProp(row, 'freq'));
        return f ? String(f.dropdownmultiselect ?? '') : '';
    }

    function alertsForFreq(freqDesc) {
        const f = freqByDesc(freqDesc);
        const max = f ? (parseInt(f.alertbeforeforrepeatconfig) || 0) : 0;
        return alertOptions.filter(a => { const v = parseInt(a.value); return v === 0 || v === -1 || v <= max; });
    }

    function parseDays(text) {
        return String(text || '').split(/[,\s]+/).map(s => s.trim()).filter(Boolean)
            .map(s => (/^\d+$/.test(s) ? String(parseInt(s, 10)) : (DAY_OPTIONS.includes(s) ? s : (DAY_OPTIONS.includes(s + '.') ? s + '.' : s))));
    }

    // ตรวจสอบแถว — คืนข้อความผิดพลาด หรือ '' ถ้าถูกต้อง
    function validateRow(r) {
        const f = freqByDesc(r.freq);
        if (!f) return 'กรุณาเลือกความถี่';
        const a = alertByDesc(r.alert);
        if (!a) return 'กรุณาเลือกการแจ้งเตือน';
        if (!alertsForFreq(r.freq).includes(a)) return 'การแจ้งเตือนนี้ใช้กับความถี่ที่เลือกไม่ได้';
        if (!/^\d{4}-\d{2}-\d{2}$/.test(r.start_date || '')) return 'กรุณาระบุวันที่เริ่ม (YYYY-MM-DD)';
        if ((contract.start && r.start_date < contract.start) || (contract.end && r.start_date > contract.end)) return 'วันที่เริ่มอยู่นอกช่วงสัญญา';
        const multi = String(f.dropdownmultiselect ?? '');
        const days = parseDays(r.days);
        if (multi === '2' && days.length !== 2) return 'ต้องระบุวันให้ครบ 2 วัน';
        if ((multi === '6' || multi === '31') && !days.length) return 'ต้องระบุวันอย่างน้อย 1 วัน';
        if (['2', '6'].includes(multi) && days.some(d => !DAY_OPTIONS.includes(d))) return 'วันในสัปดาห์ต้องเป็น จ. อ. พ. พฤ. ศ. ส. อา.';
        if (multi === '31' && days.some(d => !/^\d+$/.test(d) || +d < 1 || +d > 31)) return 'วันที่ในเดือนต้องเป็น 1-31';
        return '';
    }

    function renderPlanTable(planRows) {
        $('imp-step2').classList.remove('hidden');
        // ความถี่และวันที่เริ่มตั้งค่าเริ่มต้นจาก Excel แล้ว (ตรวจสอบ/แก้ไขได้) — แจ้งเตือนและระบุวันให้แอดมินกรอก
        // แจ้งเตือนล่วงหน้า: ค่าเริ่มต้น 8 โมงเช้าของวันนั้น ทุกเครื่องจักร
        const defAlert = alertOptions.find(a => String(a.value) === defaultAlert);
        const data = planRows.map(r => {
            const row = { ...r, freq: r.freq || '', alert: defAlert ? defAlert.desc : '', days: '', start_date: r.start_date || '' };
            for (let k = 0; k < 48; k++) row['w' + k] = (r.cells || {})[k] || '';
            return row;
        });
        const year = parseInt($('imp-year').value, 10);

        // ตำแหน่งช่องสัปดาห์ของวันที่เริ่ม (วันจันทร์ที่ k ของเดือน => สัปดาห์ k) เพื่อไฮไลต์
        const startWeekIdx = (ymd) => {
            const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(ymd || '');
            if (!m || +m[1] !== year) return -1;
            return (+m[2] - 1) * 4 + Math.min(4, Math.ceil(+m[3] / 7)) - 1;
        };

        const dateCol = {
            data: 'start_date', type: 'date', dateFormat: 'YYYY-MM-DD', correctFormat: true, className: 'htCenter imp-input',
            // ไม่กำหนด container: ให้ปฏิทินอยู่ในกล่องของ Handsontable ซึ่งจะเลื่อนไม่ให้ล้นขอบจอให้เอง
            datePickerConfig: {
                minDate: contract.start ? new Date(contract.start) : null,
                maxDate: contract.end ? new Date(contract.end) : null
            }
        };

        if (hot) hot.destroy();
        // หัวตารางเหมือน Template: Name / CODE / Location + เดือนละ 4 สัปดาห์ แล้วต่อด้วยข้อมูลแผน PM
        const weekCols = Array.from({ length: 48 }, (_, k) => ({
            data: 'w' + k, readOnly: true, className: 'imp-week' + (k % 4 === 3 ? ' imp-month-end' : '')
        }));
        const PLAN_FIRST_COL = 3 + 48;

        hot = new Handsontable($('imp-plan-hot'), {
            data: data,
            colorScheme: 'light',   // ไม่ใช้โหมดมืดตามเครื่อง (ทั้งหน้าเป็นธีมสว่าง)
            nestedHeaders: [
                ['Name', 'CODE', 'Location', ...MONTHS.map(m => ({ label: m, colspan: 4 })), { label: 'แผน PM (ตรวจสอบ / แก้ไข)', colspan: 5 }],
                ['', '', '', ...Array.from({ length: 48 }, (_, k) => String(k + 1)),
                 'ความถี่', 'แจ้งเตือนล่วงหน้า', 'ระบุวัน', 'วันที่เริ่ม', 'หมายเหตุจาก Excel']
            ],
            columns: [
                { data: 'checksheet', readOnly: true },
                { data: 'code', readOnly: true },
                { data: 'location', readOnly: true },
                ...weekCols,
                { data: 'freq', type: 'dropdown', source: freqOptions.map(o => o.desc), strict: true, allowInvalid: false, className: 'imp-input' },
                { data: 'alert', type: 'dropdown', strict: true, allowInvalid: false, className: 'imp-input',
                  source: function(query, process) { process(alertsForFreq(this.instance.getDataAtRowProp(this.row, 'freq')).map(a => a.desc)); } },
                { data: 'days' },
                dateCol,
                { data: 'note', readOnly: true, className: 'text-amber-600' }
            ],
            cells: function(row, col, prop) {
                if (!hot) return {};
                if (typeof prop === 'string' && /^w\d+$/.test(prop)) {
                    const k = +prop.slice(1);
                    let cls = 'imp-week' + (k % 4 === 3 ? ' imp-month-end' : '');
                    if (k === startWeekIdx(hot.getDataAtRowProp(row, 'start_date'))) cls += ' imp-start';
                    else if (hot.getDataAtRowProp(row, prop)) cls += ' imp-mark';
                    return { className: cls };
                }
                if (prop !== 'days') return {};
                const multi = rowMulti(row);
                if (multi === '5') return { readOnly: true, className: 'imp-disabled' };
                return ['2', '6', '31'].includes(multi) ? { readOnly: false, className: 'imp-input' } : { readOnly: true, className: 'imp-disabled' };
            },
            afterChange: function(changes, source) {
                if (!changes || source === 'loadData' || source === 'impAuto') return;
                const updates = [];
                changes.forEach(([row, prop, oldVal, newVal]) => {
                    if (prop !== 'freq' || oldVal === newVal) return;
                    // เปลี่ยนความถี่ => ล้างค่าที่ใช้ไม่ได้กับความถี่ใหม่
                    const f = freqByDesc(newVal);
                    const multi = f ? String(f.dropdownmultiselect ?? '') : '';
                    if (multi === '5') updates.push([row, 'days', WEEKDAYS.join(', ')]);
                    else if (!['2', '6', '31'].includes(multi)) updates.push([row, 'days', '']);
                    const a = alertByDesc(this.getDataAtRowProp(row, 'alert'));
                    if (a && !alertsForFreq(newVal).includes(a)) updates.push([row, 'alert', '']);
                });
                if (updates.length) this.setDataAtRowProp(updates, null, null, 'impAuto');
            },
            rowHeaders: true,
            width: '100%',
            height: '100%',
            stretchH: 'none',
            colWidths: [180, 110, 150, ...Array(48).fill(45), 160, 170, 110, 110, 280],
            fixedColumnsStart: 2,
            // ช่องสัปดาห์กว้างคงที่: ถ้าให้ Handsontable คำนวณเอง ความกว้างที่ใช้คำนวณพื้นที่เลื่อนไม่ตรงกับที่วาดจริง => เลื่อนสุดขวาแล้วมีพื้นที่ว่าง
            modifyColWidth: (width, col) => (col >= 3 && col < PLAN_FIRST_COL ? 45 : width),
            fillHandle: { direction: 'vertical', autoInsertRow: false },
            filters: true,
            dropdownMenu: ['filter_by_condition', 'filter_by_value', 'filter_action_bar'],
            columnSorting: true,
            contextMenu: ['copy', 'cut'],
            licenseKey: 'non-commercial-and-evaluation',
            afterGetColHeader: function(col, TH) {
                const plan = col >= PLAN_FIRST_COL && col < PLAN_FIRST_COL + 4;
                TH.style.backgroundColor = plan ? '#f0f9ff' : '#f8fafc';
                TH.style.color = plan ? '#0284c7' : '#334155';
            }
        });
        updateCount();
        hot.addHook('afterChange', updateCount);
        $('imp-step2').scrollIntoView({ behavior: 'smooth' });
        lucide.createIcons();
    }

    // ---------- ย่อ/ขยายเต็มจอ (ขั้นที่ 2) — เหมือน toggleStep3Fullscreen ใน pm_plan.php ----------
    const HOT_HEIGHT = '560px';
    let impFullscreen = false;

    function applyFullscreen(on) {
        impFullscreen = on;
        const card = $('imp-step2');
        const box = $('imp-plan-hot');
        card.classList.toggle('fixed', on);
        card.classList.toggle('inset-0', on);
        card.classList.toggle('z-[1000]', on);
        card.classList.toggle('rounded-xl', !on);
        card.classList.toggle('overflow-hidden', on);
        $('imp-fs-btn').innerHTML = on
            ? '<i data-lucide="minimize" class="w-4 h-4"></i> <span class="hidden sm:inline">ย่อหน้าจอกลับ</span>'
            : '<i data-lucide="maximize" class="w-4 h-4"></i> <span class="hidden sm:inline">ขยายเต็มจอ</span>';
        $('imp-fs-btn').title = on ? 'ย่อหน้าจอกลับ' : 'ขยายเต็มจอ';
        lucide.createIcons();
        // ความสูงตาราง = พื้นที่ที่เหลือใต้หัวการ์ด
        box.style.height = on ? `${Math.max(300, window.innerHeight - box.getBoundingClientRect().top - 20)}px` : HOT_HEIGHT;
        if (hot) setTimeout(() => hot.refreshDimensions(), 50);
    }

    $('imp-fs-btn').addEventListener('click', () => {
        const el = document.documentElement;
        const isDocFs = document.fullscreenElement || document.webkitFullscreenElement;
        if (!impFullscreen) {
            applyFullscreen(true);
            // ขยายทั้งหน้าต่างเบราว์เซอร์ (ทะลุ iframe) ถ้ารองรับ — ถ้าไม่รองรับก็ยังเต็มพื้นที่หน้าเว็บ
            const req = el.requestFullscreen || el.webkitRequestFullscreen;
            if (req && !isDocFs) Promise.resolve(req.call(el)).catch(() => {}).then(() => applyFullscreen(true));
        } else {
            applyFullscreen(false);
            const exit = document.exitFullscreen || document.webkitExitFullscreen;
            if (isDocFs && exit) exit.call(document);
        }
    });

    // กด ESC ออกจากโหมดเต็มจอของเบราว์เซอร์ => ย่อการ์ดกลับด้วย
    const onFsChange = () => {
        if (impFullscreen && !(document.fullscreenElement || document.webkitFullscreenElement)) applyFullscreen(false);
    };
    document.addEventListener('fullscreenchange', onFsChange);
    document.addEventListener('webkitfullscreenchange', onFsChange);
    window.addEventListener('resize', () => { if (impFullscreen) applyFullscreen(true); });

    // แถวที่กรอกครบ: ความถี่ + แจ้งเตือน + วันที่เริ่ม (ระบุวันตรวจใน validateRow)
    const isComplete = r => !!(r.freq && r.alert && r.start_date);

    function updateCount() {
        if (!hot) return;
        const rows = hot.getSourceData();
        const ready = rows.filter(isComplete).length;
        $('imp-plan-count').innerText = `พร้อมบันทึก ${ready.toLocaleString()} / ${rows.length.toLocaleString()} รายการ`;
    }

    function markInvalid(physicalRows) {
        const bad = new Set(physicalRows);
        const cols = ['freq', 'alert', 'days', 'start_date'];
        hot.getSourceData().forEach((_, pr) => {
            const vr = hot.toVisualRow(pr);
            if (vr === null || vr < 0) return;
            cols.forEach(c => hot.setCellMeta(vr, hot.propToCol(c), 'valid', !bad.has(pr)));
        });
        hot.render();
    }

    $('imp-save-btn').addEventListener('click', async () => {
        if (!hot) return;
        const source = hot.getSourceData();
        const toSave = [];
        const errors = [];
        source.forEach((r, pr) => {
            if (!isComplete(r)) return; // ยังกรอกไม่ครบ => ยังไม่บันทึก (คงอยู่ในตาราง)
            const err = validateRow(r);
            if (err) { errors.push({ pr, text: `${r.code} · ${r.checksheet}: ${err}` }); return; }
            const f = freqByDesc(r.freq);
            const multi = String(f.dropdownmultiselect ?? '');
            toSave.push({
                pr,
                row: r.code,
                machine_id: r.machine_id,
                checksheet_id: r.checksheet_id,
                freq: f.value,
                alert: alertByDesc(r.alert).value,
                days: multi === '5' ? WEEKDAYS : (['2', '6', '31'].includes(multi) ? parseDays(r.days) : []),
                start_date: r.start_date
            });
        });

        markInvalid(errors.map(e => e.pr));
        if (errors.length) {
            return Swal.fire({ icon: 'warning', title: `ข้อมูลไม่ครบถ้วน ${errors.length} แถว`,
                html: `<div class="text-left text-sm max-h-60 overflow-y-auto">${errors.slice(0, 50).map(e => e.text).join('<br>')}${errors.length > 50 ? '<br>...' : ''}</div>` });
        }
        if (!toSave.length) return Swal.fire('แจ้งเตือน', 'ยังไม่มีแถวที่กรอก ความถี่ + แจ้งเตือน + วันที่เริ่ม ครบ', 'warning');

        const ok = await Swal.fire({
            title: `บันทึกแผน PM ${toSave.length.toLocaleString()} รายการ?`,
            html: 'ระบบจะสร้างกำหนดการตามความถี่ตั้งแต่วันที่เริ่มจนถึงวันสิ้นสุดสัญญา',
            icon: 'question', showCancelButton: true, confirmButtonText: 'บันทึก', cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#006B9F'
        });
        if (!ok.isConfirmed) return;

        Swal.fire({ title: 'กำลังบันทึก...', html: 'อาจใช้เวลาสักครู่ กรุณาอย่าปิดหน้านี้', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const res = await axios.post('handle_pm_import.php?action=save_plans', { plans: toSave });
            if (!res.data.success) {
                if (res.data.errors) markInvalid(res.data.errors.map(e => toSave[e.index].pr));
                const list = (res.data.errors || []).slice(0, 50).map(e => `${e.row}: ${e.error}`).join('<br>');
                throw new Error(res.data.error + (list ? `<div class="text-left text-sm mt-2 max-h-60 overflow-y-auto">${list}</div>` : ''));
            }
            const d = res.data.data;
            // เอาแถวที่บันทึกแล้วออกจากตาราง
            const saved = new Set(toSave.map(t => t.pr));
            const remaining = source.filter((_, pr) => !saved.has(pr));
            hot.loadData(remaining);
            updateCount();
            await Swal.fire('บันทึกสำเร็จ', `สร้างแผน PM ${d.plans_created.toLocaleString()} แผน · กำหนดการ ${d.events_created.toLocaleString()} ครั้ง`, 'success');
            if (!remaining.length) {
                if (impFullscreen) $('imp-fs-btn').click();
                $('imp-step2').classList.add('hidden');
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'บันทึกไม่สำเร็จ', html: e.message + '<br>(ไม่มีข้อมูลใดถูกบันทึก)' });
        }
    });

    loadOptions();
});
</script>
<?php if (($_SESSION['sess_user_level_es'] ?? '') === 'super_admin') { ?>
<script>
// Super Admin: ลบแผน PM ที่นำเข้าผิด — ขั้นที่ 1 เลือกรอบ · ขั้นที่ 2 เลือกประเภทเครื่องจักร + แบบการลบ
pmLazy('import', function () {
    const $ = id => document.getElementById(id);
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fmt = n => Number(n || 0).toLocaleString('th-TH');
    const thDT = s => { const d = new Date(String(s).replace(' ', 'T')); return isNaN(d) ? s : d.toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' }) + ' ' + d.toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }) + ' น.'; };
    const MODE_TEXT = { pending: 'เฉพาะงานที่ยังไม่ทำ', done: 'เฉพาะงานที่ทำแล้ว', all: 'ทั้งหมด (ยังไม่ทำ + ทำแล้ว)' };
    let BATCHES = [], TYPES = [], typeSeq = 0;

    const selectedKeys = () => [...document.querySelectorAll('.bd-chk:checked')].map(c => c.value);
    const selectedTypes = () => [...document.querySelectorAll('.bd-type:checked')].map(c => c.value);
    const mode = () => (document.querySelector('input[name="bd-mode"]:checked') || {}).value || 'pending';

    // สรุปตามที่เลือก (ประมาณจากจำนวนของประเภทที่เลือก)
    function updateInfo() {
        const keys = selectedKeys();
        if (!keys.length) { $('bd-selinfo').innerHTML = 'ยังไม่ได้เลือก — ติ๊กแถวที่นำเข้าผิด'; $('bd-preview').disabled = true; return; }
        const tsel = selectedTypes();
        const pick = TYPES.filter(t => tsel.includes(t.id));
        const sum = f => pick.reduce((a, t) => a + (t[f] || 0), 0);
        const m = mode();
        const del = (m !== 'done' ? sum('pending') : 0) + (m !== 'pending' ? sum('done') : 0);
        const keep = (m === 'done' ? sum('pending') : 0) + (m === 'pending' ? sum('done') : 0);
        $('bd-selinfo').innerHTML = TYPES.length
            ? `เลือก <b>${keys.length}</b> แถว · ${pick.length}/${TYPES.length} ประเภท · <b>${MODE_TEXT[m]}</b> → จะลบงาน <b class="text-red-600">${fmt(del)}</b> รายการ${keep ? ` · คงไว้ <b class="text-emerald-600">${fmt(keep)}</b>` : ''}`
            : `เลือก <b>${keys.length}</b> แถว · กำลังโหลดประเภทเครื่องจักร...`;
        $('bd-preview').disabled = !pick.length;
        document.querySelectorAll('.bd-mode').forEach(l => {
            const on = l.querySelector('input').checked, red = l.querySelector('input').value !== 'pending';
            l.classList.toggle('border-red-400', on && red); l.classList.toggle('bg-red-50/50', on && red);
            l.classList.toggle('border-sky-400', on && !red); l.classList.toggle('bg-sky-50/50', on && !red);
            l.classList.toggle('border-slate-200', !on);
        });
    }

    async function loadTypes() {
        const keys = selectedKeys();
        $('bd-step2').classList.toggle('hidden', !keys.length);
        TYPES = [];
        if (!keys.length) { updateInfo(); return; }
        const seq = ++typeSeq;
        $('bd-types').innerHTML = '<div class="p-4 text-center text-slate-400 text-sm">กำลังโหลด...</div>';
        updateInfo();
        const r = await post('types', {}, true).catch(() => ({ success: false, error: 'เชื่อมต่อไม่สำเร็จ' }));
        if (seq !== typeSeq) return;
        if (!r.success) { $('bd-types').innerHTML = `<div class="p-4 text-center text-red-500 text-sm">${esc(r.error)}</div>`; return; }
        TYPES = r.types;
        $('bd-types').innerHTML = TYPES.map(t => `
            <label class="flex items-center gap-2.5 px-3 py-2 hover:bg-slate-50 cursor-pointer text-sm">
                <input type="checkbox" class="bd-type w-4 h-4 accent-red-600" value="${esc(t.id)}" checked>
                <span class="flex-1 min-w-0 truncate ${t.id === 'none' ? 'text-slate-400 italic' : 'text-slate-700'}">${esc(t.name)}</span>
                <span class="text-[11px] text-slate-500 whitespace-nowrap">${t.plans ? `แผน ${fmt(t.plans)} · ` : ''}<span class="text-red-600">ยังไม่ทำ ${fmt(t.pending)}</span> · <span class="text-emerald-600">ทำแล้ว ${fmt(t.done)}</span>${t.sheets ? ` · เช็คชีต ${fmt(t.sheets)}` : ''}</span>
            </label>`).join('') || '<div class="p-4 text-center text-slate-400 text-sm">ไม่มีข้อมูลในแถวที่เลือก</div>';
        document.querySelectorAll('.bd-type').forEach(c => c.addEventListener('change', () => {
            const all = document.querySelectorAll('.bd-type'), on = document.querySelectorAll('.bd-type:checked');
            $('bd-type-all').checked = all.length === on.length; $('bd-type-all').indeterminate = on.length > 0 && on.length < all.length;
            updateInfo();
        }));
        $('bd-type-all').checked = true; $('bd-type-all').indeterminate = false;
        updateInfo();
    }

    function onRowsChanged() {
        $('bd-all').checked = selectedKeys().length > 0 && selectedKeys().length === BATCHES.length;
        loadTypes();
    }

    async function load() {
        $('bd-body').innerHTML = '<tr><td colspan="8" class="p-6 text-center text-slate-400">กำลังโหลด...</td></tr>';
        try {
            const r = await (await fetch('handle_pm_bulk_delete.php?action=batches')).json();
            if (!r.success) throw new Error(r.error);
            BATCHES = r.batches; $('bd-agency').textContent = r.agency || '';
            $('bd-body').innerHTML = BATCHES.length ? BATCHES.map(b => `
                <tr class="border-t border-slate-100 hover:bg-red-50/40">
                    <td class="p-2 text-center"><input type="checkbox" class="bd-chk w-4 h-4 accent-red-600" value="${esc(b.key)}"></td>
                    <td class="p-2 whitespace-nowrap">${esc(thDT(b.at))}<div class="flex gap-1 mt-0.5">${b.plans ? '<span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-sky-50 text-sky-700 border border-sky-100">บันทึกแผน PM</span>' : ''}${b.sheets_created ? '<span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-violet-50 text-violet-700 border border-violet-100">สร้างเช็คชีต</span>' : ''}</div></td>
                    <td class="p-2">${esc(b.by || '-')}</td>
                    <td class="p-2 text-[12px] text-slate-500 max-w-[320px]">${b.sample.map(esc).join(', ') || '-'}${(b.sheets_used || b.sheets_created) > b.sample.length ? ' …' : ''}</td>
                    <td class="p-2 text-right">${fmt(b.plans)}</td>
                    <td class="p-2 text-right font-semibold ${b.pending ? 'text-red-600' : 'text-slate-400'}">${fmt(b.pending)}</td>
                    <td class="p-2 text-right ${b.done ? 'text-emerald-600 font-semibold' : 'text-slate-400'}">${fmt(b.done)}</td>
                    <td class="p-2 text-right">${fmt(b.sheets_created)}</td>
                </tr>`).join('') : '<tr><td colspan="8" class="p-6 text-center text-slate-400">ไม่มีแผน PM หรือเช็คชีตในหน่วยงานนี้</td></tr>';
            document.querySelectorAll('.bd-chk').forEach(c => c.addEventListener('change', onRowsChanged));
            onRowsChanged();
        } catch (e) {
            $('bd-body').innerHTML = `<tr><td colspan="8" class="p-6 text-center text-red-500">${esc(e.message || 'โหลดไม่สำเร็จ')}</td></tr>`;
        }
    }

    // keysOnly = ส่งเฉพาะรอบ (โหลดประเภท)
    function post(action, extra, keysOnly) {
        const fd = new FormData();
        selectedKeys().forEach(k => fd.append('keys[]', k));
        if (!keysOnly) {
            // เลือกครบทุกประเภท => ไม่ส่ง types (= ทุกประเภท รวมที่เพิ่มเข้ามาภายหลัง)
            const all = document.querySelectorAll('.bd-type').length, sel = selectedTypes();
            if (sel.length !== all) sel.forEach(t => fd.append('types[]', t));
            fd.append('mode', mode());
        }
        Object.entries(extra || {}).forEach(([k, v]) => fd.append(k, v));
        return fetch('handle_pm_bulk_delete.php?action=' + action, { method: 'POST', body: fd }).then(r => r.json());
    }

    $('bd-all').addEventListener('change', e => { document.querySelectorAll('.bd-chk').forEach(c => c.checked = e.target.checked); onRowsChanged(); });
    $('bd-type-all').addEventListener('change', e => { document.querySelectorAll('.bd-type').forEach(c => c.checked = e.target.checked); e.target.indeterminate = false; updateInfo(); });
    document.querySelectorAll('input[name="bd-mode"]').forEach(r => r.addEventListener('change', updateInfo));
    $('bd-reload').addEventListener('click', load);

    $('bd-preview').addEventListener('click', async () => {
        Swal.fire({ title: 'กำลังตรวจสอบ...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const r = await post('preview').catch(() => ({ success: false, error: 'เชื่อมต่อไม่สำเร็จ' }));
        if (!r.success) { Swal.fire('ข้อผิดพลาด', r.error, 'error'); return; }
        const s = r.summary;
        if (!s.pending_del && !s.done_del && !s.delete_plans && !s.deactivate_plans && !s.clear_sheets) { Swal.fire('ไม่มีรายการที่ลบได้', 'ตามประเภทและแบบการลบที่เลือก ไม่มีงานที่ต้องลบ', 'info'); return; }
        const row = (icon, text) => `<li class="flex gap-2 items-start"><span>${icon}</span><span>${text}</span></li>`;
        const types = selectedTypes().length === document.querySelectorAll('.bd-type').length ? 'ทุกประเภท' : TYPES.filter(t => selectedTypes().includes(t.id)).map(t => esc(t.name)).join(', ');
        const html = `<div class="text-left text-[13px] text-slate-500 mb-3">${MODE_TEXT[s.mode]} · ประเภท: ${types}</div>
        <ul class="text-left text-[14px] space-y-2">
            ${s.pending_del ? row('🗑️', `ลบงานที่ยังไม่ทำ <b class="text-red-600">${fmt(s.pending_del)}</b> รายการ`) : ''}
            ${s.done_del ? row('🗑️', `ลบงานที่ทำแล้ว <b class="text-red-600">${fmt(s.done_del)}</b> รายการ — ใบงาน ${fmt(s.records_del)} ใบ (ผลตรวจ รูป ลายเซ็น ผลประเมิน)`) : ''}
            ${s.spare_items ? row('📦', `คืนสต็อกอะไหล่ ${fmt(s.spare_items)} รายการ (รวม ${fmt(s.spare_qty)} หน่วย) ที่ใบงานเหล่านี้ตัดไป`) : ''}
            ${s.delete_plans ? row('🗑️', `ลบแผน PM <b class="text-red-600">${fmt(s.delete_plans)}</b> แผน (ไม่เหลืองานแล้ว)`) : ''}
            ${s.deactivate_plans ? row('⏸️', `ปิดใช้งาน ${fmt(s.deactivate_plans)} แผน (ยังมีงานที่ทำแล้ว ${fmt(s.done_keep)} รายการ เก็บไว้)`) : ''}
            ${s.clear_sheets ? row('🏷️', `ล้างประเภทเครื่องจักรของเช็คชีต <b class="text-amber-600">${fmt(s.clear_sheets)}</b> ชุด (ไม่ลบเช็คชีต จุดตรวจยังอยู่)`) : ''}
        </ul>
        ${s.done_del ? '<div class="mt-3 text-left text-[13px] text-red-700 bg-red-50 border border-red-100 rounded-lg p-2.5"><b>คำเตือน:</b> งานที่ทำแล้วจะหายจากประวัติและรายงาน PM ทั้งหมด</div>' : ''}
        <p class="text-[13px] text-slate-500 mt-4">ลบแล้วกู้คืนไม่ได้ — พิมพ์คำว่า <b class="text-red-600">ลบ</b> เพื่อยืนยัน</p>`;
        const c = await Swal.fire({
            title: 'ยืนยันการลบแผน PM', html, icon: 'warning', input: 'text', inputPlaceholder: 'พิมพ์ ลบ', width: '36rem',
            showCancelButton: true, confirmButtonText: 'ลบถาวร', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#dc2626', focusCancel: true,
            preConfirm: v => { if ((v || '').trim() !== 'ลบ') { Swal.showValidationMessage('พิมพ์คำว่า ลบ ให้ถูกต้อง'); return false; } return true; }
        });
        if (!c.isConfirmed) return;
        Swal.fire({ title: 'กำลังลบ...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const d = await post('delete', { confirm: 'ลบ' }).catch(() => ({ success: false, error: 'เชื่อมต่อไม่สำเร็จ' }));
        if (!d.success) { Swal.fire('ลบไม่สำเร็จ', d.error, 'error'); return; }
        const t = d.summary;
        await Swal.fire({ icon: 'success', title: 'ลบเรียบร้อย', html: [
            t.pending_del ? `ลบงานที่ยังไม่ทำ ${fmt(t.pending_del)} รายการ` : '',
            t.done_del ? `ลบงานที่ทำแล้ว ${fmt(t.done_del)} รายการ` : '',
            t.spare_items ? `คืนสต็อกอะไหล่ ${fmt(t.spare_items)} รายการ` : '',
            t.delete_plans ? `ลบแผน ${fmt(t.delete_plans)} แผน` : '',
            t.deactivate_plans ? `ปิดใช้งาน ${fmt(t.deactivate_plans)} แผน` : '',
            t.clear_sheets ? `ล้างประเภทเครื่องจักรของเช็คชีต ${fmt(t.clear_sheets)} ชุด` : ''
        ].filter(Boolean).join('<br>') });
        load();
    });

    load();
});
</script>
<?php } ?>
