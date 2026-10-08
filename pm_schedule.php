<?php
//pm_schedule.php — แท็บตารางแผน PM รายปี: ดูแผน PM ที่บันทึกแล้ว + กำหนดการรายสัปดาห์ (อ่านอย่างเดียว, เฉพาะผู้ดูแลระบบ) ถูก include จาก pm.php
@session_start();
include_once "config_ctrl/checksession.php";
if (!in_array($sess_user_level, array('admin', 'super_admin'), true)) return;
?>
<style>
    /* ช่องสัปดาห์: ใช้ !important เพราะธีม Handsontable บังคับสีพื้นของ .htDimmed ด้วย !important
       ● รอดำเนินการ / ✓ ทำแล้ว / ! เลยกำหนด (ตัวเลข = มีหลายครั้งในสัปดาห์) */
    #sch-hot td.sch-week { padding: 0; text-align: center; font-size: 11px; font-weight: 600; }
    #sch-hot td.sch-pending { background-color: #e0f2fe !important; color: #0369a1; }
    #sch-hot td.sch-done { background-color: #dcfce7 !important; color: #15803d; }
    #sch-hot td.sch-overdue { background-color: #fef3c7 !important; color: #b45309; }
    #sch-hot td.sch-month-end { border-right: 2px solid #cbd5e1; }
    #sch-hot td.sch-now { box-shadow: inset 2px 0 0 #f43f5e, inset -2px 0 0 #f43f5e; }
    #sch-hot td.sch-plan { color: #334155 !important; }
    .sch-legend { display: inline-flex; align-items: center; gap: .375rem; font-size: 12px; color: #475569; white-space: nowrap; }
    .sch-legend i { display: inline-flex; align-items: center; justify-content: center; width: 1.25rem; height: 1.25rem; border-radius: .25rem; font-style: normal; font-size: 11px; font-weight: 600; }
    .sch-chip { display: inline-flex; align-items: center; gap: .25rem; padding: .25rem .625rem; border-radius: 9999px; font-size: 12px; white-space: nowrap; border: 1px solid; }

    /* มือถือ/แท็บเล็ต: เลื่อนทั้งหน้า ตารางสูงตามจอ */
    #sch-hot { height: max(380px, calc(100vh - 290px)); }
    /* จอคอม (lg ขึ้นไป): การ์ดสูงพอดีพื้นที่ของแท็บ (ไม่ต้องเลื่อนทั้งหน้า) ตารางกินพื้นที่ที่เหลือในการ์ด
       จอเตี้ยกว่า min-height ของการ์ด => เลื่อนหน้าได้ */
    @media (min-width: 1024px) {
        #tab-schedule:not(.hidden) { height: 100%; display: flex; flex-direction: column; }
        #sch-card { flex: 1 1 auto; min-height: 480px; display: flex; flex-direction: column; }
        #sch-hot { flex: 1 1 0; height: auto; min-height: 0; }
    }
    /* เต็มจอ: การ์ดเต็มหน้าจอจริง (Fullscreen API) หรือเต็มหน้าเว็บ (เครื่องที่ไม่รองรับ เช่น iPhone) */
    #sch-card.sch-fs { position: fixed; inset: 0; z-index: 1000; border-radius: 0; overflow: hidden; display: flex; flex-direction: column; }
    #sch-card.sch-fs #sch-hot { flex: 1 1 auto; height: auto; min-height: 0; }
</style>
<div id="tab-schedule" class="tab-content hidden">
    <div id="sch-card" class="bg-white p-4 sm:p-5 rounded-xl shadow-sm border border-slate-200">
        <!-- หัวเรื่อง -->
        <div class="min-w-0">
            <h3 class="text-lg font-semibold text-slate-800 leading-tight">ตารางแผน PM รายปี</h3>
            <p class="text-[12px] text-slate-500 mt-0.5">แผน PM ที่บันทึกแล้ว และกำหนดการรายสัปดาห์ของปีที่เลือก (ดูอย่างเดียว)</p>
        </div>

        <!-- แถบเครื่องมือ -->
        <div class="mt-3 flex flex-wrap items-center gap-2 p-2 bg-slate-50 border border-slate-200 rounded-xl">
            <div class="flex items-center gap-1">
                <select id="sch-year" aria-label="ปี" class="bg-white border border-slate-300 rounded-lg py-1.5 px-2.5 text-sm focus:ring-2 focus:ring-sky-500 outline-none"></select>
                <button id="sch-reload-btn" type="button" title="โหลดข้อมูลใหม่" class="p-1.5 sm:p-2 rounded-lg text-slate-600 hover:bg-white hover:text-slate-800 border border-transparent hover:border-slate-200">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </button>
            </div>
            <!-- มือถือ: ตัวเลขสรุปลงไปแถวล่าง ปุ่มอยู่แถวเดียวกับปี -->
            <div id="sch-summary" class="order-last sm:order-none w-full sm:w-auto flex flex-wrap items-center gap-1.5"></div>
            <div class="flex items-center gap-1.5 sm:gap-2 ml-auto">
                <button id="sch-help-btn" type="button" title="คำอธิบาย" aria-expanded="false" class="p-1.5 sm:p-2 rounded-lg text-slate-600 hover:bg-white border border-transparent hover:border-slate-200">
                    <i data-lucide="circle-help" class="w-4 h-4"></i>
                </button>
                <button id="sch-export-btn" type="button" title="Export Excel" class="bg-white border border-emerald-600 text-emerald-700 hover:bg-emerald-50 px-2 sm:px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-1.5 transition-colors">
                    <i data-lucide="file-down" class="w-4 h-4"></i> <span class="hidden sm:inline">Export Excel</span>
                </button>
                <button id="sch-fs-btn" type="button" title="ขยายเต็มจอ" class="bg-white text-sky-700 hover:bg-sky-50 px-2 sm:px-3 py-1.5 rounded-lg text-sm font-medium flex items-center gap-1.5 transition-colors border border-sky-200">
                    <i data-lucide="maximize" class="w-4 h-4"></i> <span class="hidden sm:inline">ขยายเต็มจอ</span>
                </button>
            </div>
        </div>

        <!-- คำอธิบายสี (มือถืออยู่ในกล่องคำอธิบาย) + คำอธิบาย (พับเก็บ) -->
        <div class="mt-2.5 hidden sm:flex flex-wrap gap-x-4 gap-y-1">
            <span class="sch-legend"><i style="background:#e0f2fe;color:#0369a1">●</i>รอดำเนินการ</span>
            <span class="sch-legend"><i style="background:#dcfce7;color:#15803d">✓</i>ทำแล้ว</span>
            <span class="sch-legend"><i style="background:#fef3c7;color:#b45309">!</i>เลยกำหนด</span>
            <span class="sch-legend"><i style="box-shadow:inset 2px 0 0 #f43f5e,inset -2px 0 0 #f43f5e"></i>สัปดาห์นี้</span>
        </div>
        <div id="sch-help" class="hidden mt-2.5 rounded-lg bg-sky-50 border border-sky-100 p-3 text-[12px] text-slate-600">
            <div class="sm:hidden flex flex-wrap gap-x-4 gap-y-1 mb-2.5">
                <span class="sch-legend"><i style="background:#e0f2fe;color:#0369a1">●</i>รอดำเนินการ</span>
                <span class="sch-legend"><i style="background:#dcfce7;color:#15803d">✓</i>ทำแล้ว</span>
                <span class="sch-legend"><i style="background:#fef3c7;color:#b45309">!</i>เลยกำหนด</span>
                <span class="sch-legend"><i style="box-shadow:inset 2px 0 0 #f43f5e,inset -2px 0 0 #f43f5e"></i>สัปดาห์นี้</span>
            </div>
            <ul class="list-disc pl-4 space-y-1">
                <li>ตัวเลขในช่องสัปดาห์ = จำนวนครั้งในสัปดาห์นั้น (เดือนละ 4 สัปดาห์: วันที่ 1-7, 8-14, 15-21, 22 ขึ้นไป)</li>
                <li>ตารางนี้ใช้ดูอย่างเดียว — แก้ไขแผนได้ที่แท็บ <b>จัดการแผน PM</b></li>
                <li>Export Excel ส่งออกตามลำดับและตัวกรองที่แสดงอยู่ในตาราง</li>
            </ul>
        </div>

        <div id="sch-hot" class="mt-3 w-full overflow-hidden"></div>
        <div id="sch-empty" class="hidden py-16 text-center text-sm text-slate-400">ยังไม่มีแผน PM ในหน่วยงานนี้</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const PLAN_FIRST_COL = 3 + 48;

    let freqOptions = [];
    let alertOptions = [];
    let contract = { start: '', end: '' };
    let agencyName = '';
    let hot = null;
    let overduePlans = 0;
    let today = '';
    let shownYear = null;
    let ready = false;
    let narrowLayout = null;    // จอแคบ (มือถือ) ใช้ความกว้างคอลัมน์ต่างกัน

    const $ = id => document.getElementById(id);
    const freqByValue = v => freqOptions.find(o => String(o.value) === String(v));
    const alertByValue = v => alertOptions.find(a => String(a.value) === String(v));
    const isNarrow = () => window.innerWidth < 640;

    // เต็มจอทั้งเอกสาร => หน้าต่างแจ้งเตือน (z-index 1060) แสดงเหนือการ์ดเต็มจอ (1000) ได้ตามปกติ
    const fsElement = () => document.fullscreenElement || document.webkitFullscreenElement || null;
    const alertBox = opts => Swal.fire(opts);
    // เรียก showLoading ทันที (ไม่ใช้ didOpen: ถ้า Swal.close() ถูกเรียกก่อน popup เปิดเสร็จ didOpen จะสร้าง popup เปล่าค้างไว้)
    const loadingBox = (title, html = '') => { alertBox({ title, html, allowOutsideClick: false, showConfirmButton: false }); Swal.showLoading(); };

    // ช่องสัปดาห์ของวันนี้ (เหมือน weekIndex ใน handle_pm_schedule.php)
    function weekIndexOf(ymd) {
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(ymd || '');
        return m ? (+m[2] - 1) * 4 + Math.min(4, Math.ceil(+m[3] / 7)) - 1 : -1;
    }

    // ---------- โหลดข้อมูล ----------
    async function loadOptions() {
        const res = await axios.get('handle_pm_import.php?action=get_options');
        if (!res.data.success) throw new Error(res.data.error);
        freqOptions = res.data.data.freqs;
        alertOptions = res.data.data.alerts;
        contract = res.data.data.contract;
        agencyName = res.data.data.agency || '';

        // ปีตามช่วงสัญญา (ไม่มีสัญญา => ปีนี้ ±1)
        const nowY = new Date().getFullYear();
        const y1 = parseInt((contract.start || '').slice(0, 4), 10) || nowY - 1;
        const y2 = parseInt((contract.end || '').slice(0, 4), 10) || nowY + 1;
        const years = [];
        for (let y = y1; y <= y2; y++) years.push(y);
        $('sch-year').innerHTML = years.map(y => `<option value="${y}">พ.ศ. ${y + 543}</option>`).join('');
        $('sch-year').value = years.includes(nowY) ? nowY : years[0];
    }

    let lastRows = [];
    async function loadSchedule() {
        const year = parseInt($('sch-year').value, 10);
        loadingBox('กำลังโหลดแผน PM...');
        try {
            const res = await axios.get('handle_pm_schedule.php', { params: { action: 'get_schedule', year } });
            if (!res.data.success) throw new Error(res.data.error);
            Swal.close();
            today = res.data.data.today;
            shownYear = year;
            lastRows = res.data.data.rows;
            renderTable(lastRows, year);
        } catch (e) {
            alertBox({ icon: 'error', title: 'ข้อผิดพลาด', text: 'โหลดแผน PM ไม่สำเร็จ: ' + e.message });
        }
    }

    // ---------- ตาราง ----------
    function weekCell(w) {
        if (!w || !w.n) return { text: '', cls: '' };
        const text = w.n > 1 ? String(w.n) : (w.overdue ? '!' : (w.done === w.n ? '✓' : '●'));
        const cls = w.overdue ? 'sch-overdue' : (w.done === w.n ? 'sch-done' : 'sch-pending');
        return { text, cls };
    }

    function renderTable(rows, year) {
        const weekCls = {};   // plan_id => {k: class}
        overduePlans = 0;
        const data = rows.map(r => {
            const f = freqByValue(r.freq);
            const a = alertByValue(r.alert);
            const row = {
                plan_id: r.plan_id, checksheet: r.checksheet || '-', code: r.code, location: r.location || r.machine,
                freq: f ? f.desc : '', alert: a ? a.desc : '', days: (r.days || []).join(', '),
                next_date: r.next_date || '', start_date: r.start_date || ''
            };
            weekCls[r.plan_id] = {};
            let overdue = false;
            for (let k = 0; k < 48; k++) {
                const w = (r.weeks || {})[k];
                const c = weekCell(w);
                row['w' + k] = c.text;
                weekCls[r.plan_id][k] = c.cls;
                if (w && w.overdue) overdue = true;
            }
            if (overdue) overduePlans++;
            return row;
        });

        $('sch-empty').classList.toggle('hidden', data.length > 0);
        $('sch-hot').classList.toggle('hidden', data.length === 0);
        if (hot) { hot.destroy(); hot = null; }
        if (!data.length) { updateCount(); return; }

        narrowLayout = isNarrow();
        const nowIdx = String(year) === today.slice(0, 4) ? weekIndexOf(today) : -1;
        const weekCols = Array.from({ length: 48 }, (_, k) => ({ data: 'w' + k }));
        // จอแคบ: ตรึงแค่คอลัมน์ Name และใช้คอลัมน์แคบลง
        const colWidths = narrowLayout
            ? [130, 90, 120, ...Array(48).fill(40), 140, 140, 90, 125, 90]
            : [180, 110, 150, ...Array(48).fill(45), 160, 170, 110, 135, 100];

        hot = new Handsontable($('sch-hot'), {
            data: data,
            colorScheme: 'light',          // ไม่ใช้โหมดมืดตามเครื่อง (ทั้งหน้าเป็นธีมสว่าง)
            readOnly: true,                // ดูอย่างเดียว — แก้แผนที่แท็บจัดการแผน PM
            nestedHeaders: [
                ['Name', 'CODE', 'Location', ...MONTHS.map(m => ({ label: `${m} ${year}`, colspan: 4 })), { label: 'แผน PM', colspan: 4 }, ''],
                ['', '', '', ...Array.from({ length: 48 }, (_, k) => String(k + 1)),
                 'ความถี่', 'แจ้งเตือนล่วงหน้า', 'ระบุวัน', 'รอบถัดไป', 'เริ่มแผน']
            ],
            columns: [
                { data: 'checksheet' },
                { data: 'code' },
                { data: 'location' },
                ...weekCols,
                { data: 'freq', className: 'sch-plan' },
                { data: 'alert', className: 'sch-plan' },
                { data: 'days', className: 'sch-plan' },
                { data: 'next_date', className: 'sch-plan htCenter' },
                { data: 'start_date', className: 'htCenter text-slate-500' }
            ],
            cells: function(row, col, prop) {
                if (typeof prop !== 'string' || !/^w\d+$/.test(prop)) return {};
                // ใช้ this.instance (ตอนวาดครั้งแรกตัวแปร hot ยังไม่ถูกกำหนด)
                const planId = this.instance.getDataAtRowProp(row, 'plan_id');
                const k = +prop.slice(1);
                let cls = 'sch-week ' + ((weekCls[planId] || {})[k] || '');
                if (k % 4 === 3) cls += ' sch-month-end';
                if (k === nowIdx) cls += ' sch-now';
                return { className: cls };
            },
            rowHeaders: true,
            width: '100%',
            height: '100%',
            stretchH: 'none',
            colWidths: colWidths,
            fixedColumnsStart: narrowLayout ? 1 : 2,
            // ช่องสัปดาห์กว้างคงที่: ถ้าให้ Handsontable คำนวณเอง (ตามหัวคอลัมน์/ปุ่มเมนูกรอง) ความกว้างที่ใช้คำนวณพื้นที่เลื่อน
            // ไม่ตรงกับที่วาดจริง => เลื่อนสุดขวาแล้วมีพื้นที่ว่าง
            modifyColWidth: (width, col) => (col >= 3 && col < PLAN_FIRST_COL ? colWidths[col] : width),
            fillHandle: false,
            filters: true,
            dropdownMenu: ['filter_by_condition', 'filter_by_value', 'filter_action_bar'],
            columnSorting: true,
            contextMenu: ['copy'],
            licenseKey: 'non-commercial-and-evaluation',
            afterGetColHeader: function(col, TH) {
                const plan = col >= PLAN_FIRST_COL && col < PLAN_FIRST_COL + 4;
                const now = nowIdx >= 0 && col === 3 + nowIdx;
                TH.style.backgroundColor = now ? '#ffe4e6' : (plan ? '#f0f9ff' : '#f8fafc');
                TH.style.color = now ? '#be123c' : (plan ? '#0284c7' : '#334155');
                // ช่องสัปดาห์แคบ ไม่ต้องมีปุ่มกรอง
                if (col >= 3 && col < PLAN_FIRST_COL) TH.querySelector('.changeType')?.remove();
            }
        });
        // เลื่อนให้เห็นสัปดาห์นี้ (ปีปัจจุบัน) โดยเว้นไว้ 2 สัปดาห์ก่อนหน้า — คำนวณจากความกว้างคอลัมน์ที่ไม่ได้ตรึง
        if (nowIdx > 2) setTimeout(() => {
            const holder = $('sch-hot').querySelector('.ht_master .wtHolder');
            if (!holder) return;
            let x = 0;
            for (let c = narrowLayout ? 1 : 2; c < 3 + nowIdx - 2; c++) x += colWidths[c];
            holder.scrollLeft = x;
        }, 80);
        updateCount();
        lucide.createIcons();
    }

    function updateCount() {
        const total = hot ? hot.getSourceData().length : 0;
        const chip = (text, cls) => `<span class="sch-chip ${cls}">${text}</span>`;
        $('sch-summary').innerHTML =
            chip(`แผน PM <b>${total.toLocaleString()}</b>`, 'bg-white border-slate-200 text-slate-600') +
            (overduePlans ? chip(`เลยกำหนด <b>${overduePlans.toLocaleString()}</b> แผน`, 'bg-amber-50 border-amber-200 text-amber-700') : '');
    }

    // ---------- Export Excel: รูปแบบเดียวกับ Template นำเข้า (Name / CODE / Location + เดือนละ 4 สัปดาห์) ต่อด้วยข้อมูลแผน PM ----------
    // ส่งออกตามลำดับและตัวกรองที่แสดงอยู่ในตาราง (hot.getData() = ข้อมูลตามลำดับคอลัมน์ที่แสดง)
    $('sch-export-btn').addEventListener('click', () => {
        if (!hot || !hot.countRows()) return alertBox({ icon: 'info', title: 'แจ้งเตือน', text: 'ไม่มีแผน PM ให้ส่งออก' });

        const year = shownYear;
        const PLAN_HEAD = ['ความถี่', 'แจ้งเตือนล่วงหน้า', 'ระบุวัน', 'รอบถัดไป', 'เริ่มแผน'];
        const W = PLAN_FIRST_COL + PLAN_HEAD.length;
        const blank = n => Array(n).fill('');
        const rows = hot.getData();
        const aoa = [
            [`YEARLY PREVENTIVE MAINTENANCE SCHEDULE ${agencyName ? agencyName + ' ' : ''}FOR JANUARY ${year} - DECEMBER ${year}`, ...blank(W - 1)],
            [`ข้อมูล ณ วันที่ ${today} · แผน PM ${rows.length.toLocaleString()} รายการ`, ...blank(W - 1)],
            ['Name', 'CODE', 'Location', ...MONTHS.flatMap(m => [m, '', '', '']), ...PLAN_HEAD],
            ['', '', '', ...Array.from({ length: 48 }, (_, k) => k + 1), ...blank(PLAN_HEAD.length)],
            ...rows
        ];
        const footer = aoa.length + 1;
        aoa.push(blank(W));
        aoa.push(['หมายเหตุ', '● = รอดำเนินการ   ✓ = ทำแล้ว   ! = เลยกำหนด   ตัวเลข = จำนวนครั้งในสัปดาห์นั้น (เดือนละ 4 สัปดาห์: วันที่ 1-7, 8-14, 15-21, 22 ขึ้นไป)', ...blank(W - 2)]);

        const ws = XLSX.utils.aoa_to_sheet(aoa);
        const R = (r1, c1, r2, c2) => ({ s: { r: r1, c: c1 }, e: { r: r2, c: c2 } });
        ws['!merges'] = [
            R(0, 0, 0, W - 1), R(1, 0, 1, W - 1),
            R(2, 0, 3, 0), R(2, 1, 3, 1), R(2, 2, 3, 2),
            ...MONTHS.map((_, i) => R(2, 3 + i * 4, 2, 6 + i * 4)),
            ...PLAN_HEAD.map((_, i) => R(2, PLAN_FIRST_COL + i, 3, PLAN_FIRST_COL + i)),
            R(footer, 1, footer, W - 1)
        ];
        ws['!cols'] = [{ wch: 28 }, { wch: 16 }, { wch: 28 }, ...Array(48).fill({ wch: 3.5 }),
                       { wch: 22 }, { wch: 22 }, { wch: 14 }, { wch: 12 }, { wch: 12 }];

        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, `PM ${year}`);
        XLSX.writeFile(wb, `PM_Schedule_${year}.xlsx`);
    });

    $('sch-year').addEventListener('change', loadSchedule);
    $('sch-reload-btn').addEventListener('click', loadSchedule);
    $('sch-help-btn').addEventListener('click', () => {
        const open = $('sch-help').classList.toggle('hidden') === false;
        $('sch-help-btn').setAttribute('aria-expanded', String(open));
        if (hot) setTimeout(() => hot.refreshDimensions(), 50);
    });

    // ---------- เต็มจอ ----------
    // การ์ดคลุมทั้งหน้า (.sch-fs) + ขอเต็มจอให้ "ทั้งเอกสาร" (ทะลุ iframe ของ main.php ที่มี allowfullscreen)
    // ไม่ขอเต็มจอเฉพาะการ์ด: Handsontable วางเมนูกรองไว้ใน <body> ซึ่งจะมองไม่เห็นถ้าเต็มจอแค่การ์ด
    // เครื่องที่ไม่รองรับ (เช่น Safari บน iPhone) => การ์ดคลุมพื้นที่หน้าเว็บอย่างเดียว
    function setFsButton(on) {
        $('sch-fs-btn').innerHTML = on
            ? '<i data-lucide="minimize" class="w-4 h-4"></i> <span class="hidden sm:inline">ย่อหน้าจอ</span>'
            : '<i data-lucide="maximize" class="w-4 h-4"></i> <span class="hidden sm:inline">ขยายเต็มจอ</span>';
        $('sch-fs-btn').title = on ? 'ย่อหน้าจอ' : 'ขยายเต็มจอ';
        lucide.createIcons();
        if (hot) setTimeout(() => hot.refreshDimensions(), 80);
    }

    function setCardFullscreen(on) {
        $('sch-card').classList.toggle('sch-fs', on);
        setFsButton(on);
    }

    $('sch-fs-btn').addEventListener('click', async () => {
        if ($('sch-card').classList.contains('sch-fs')) {
            setCardFullscreen(false);
            if (fsElement()) (document.exitFullscreen || document.webkitExitFullscreen).call(document);
            return;
        }
        setCardFullscreen(true);
        const el = document.documentElement;
        const req = el.requestFullscreen || el.webkitRequestFullscreen;
        if (req && (document.fullscreenEnabled || document.webkitFullscreenEnabled)) {
            try { await req.call(el); } catch (e) { /* ไม่อนุญาต => การ์ดคลุมหน้าเว็บอย่างเดียว */ }
        }
    });

    // กด ESC ออกจากเต็มจอของเบราว์เซอร์ => ย่อการ์ดกลับด้วย
    const onFsChange = () => {
        if (fsElement()) setFsButton(true);
        else if ($('sch-card').classList.contains('sch-fs')) setCardFullscreen(false);
    };
    document.addEventListener('fullscreenchange', onFsChange);
    document.addEventListener('webkitfullscreenchange', onFsChange);

    // หมุนจอ/เปลี่ยนขนาดข้ามจุดตัดมือถือ => สร้างตารางใหม่ด้วยความกว้างคอลัมน์ที่เหมาะสม
    let resizeTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (hot && narrowLayout !== isNarrow()) renderTable(lastRows, shownYear);
            else if (hot) hot.refreshDimensions();
        }, 200);
    });

    // โหลดครั้งแรกเมื่อเปิดแท็บ (เรียกจาก switchTab ใน pm.php) — ตารางที่สร้างตอนแท็บซ่อนอยู่ต้อง refresh ขนาด
    window.initScheduleTab = async function() {
        if (!ready) {
            ready = true;
            try {
                await loadOptions();
            } catch (e) {
                ready = false;
                return alertBox({ icon: 'error', title: 'ข้อผิดพลาด', text: 'โหลดตัวเลือกไม่สำเร็จ: ' + e.message });
            }
            return loadSchedule();
        }
        if (hot) setTimeout(() => hot.refreshDimensions(), 50);
    };
});
</script>
