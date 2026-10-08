<?php
//pm_schedule.php — แท็บตารางแผน PM รายปี: ดู/แก้แผน PM ที่บันทึกแล้ว (เฉพาะผู้ดูแลระบบ) ถูก include จาก pm.php
@session_start();
include_once "config_ctrl/checksession.php";
if (!in_array($sess_user_level, array('admin', 'super_admin'), true)) return;
?>
<style>
    #sch-hot .htInvalid { background-color: #fee2e2 !important; }
    #sch-hot .sch-input { background-color: #f0f9ff; }
    #sch-hot .sch-disabled { background-color: #f1f5f9 !important; color: #94a3b8; }
    #sch-hot td.sch-dirty { background-color: #fef9c3 !important; }
    /* ช่องสัปดาห์ (อ่านอย่างเดียว): ใช้ !important เพราะธีม Handsontable บังคับสีพื้นของ .htDimmed ด้วย !important
       ● รอดำเนินการ / ✓ ทำแล้ว / ! เลยกำหนด (ตัวเลข = มีหลายครั้งในสัปดาห์) */
    #sch-hot td.sch-week { padding: 0; text-align: center; font-size: 11px; font-weight: 600; }
    #sch-hot td.sch-pending { background-color: #e0f2fe !important; color: #0369a1; }
    #sch-hot td.sch-done { background-color: #dcfce7 !important; color: #15803d; }
    #sch-hot td.sch-overdue { background-color: #fef3c7 !important; color: #b45309; }
    #sch-hot td.sch-month-end { border-right: 2px solid #cbd5e1; }
    #sch-hot td.sch-now { box-shadow: inset 2px 0 0 #f43f5e, inset -2px 0 0 #f43f5e; }
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
        <!-- หัวเรื่อง + ปุ่มบันทึก -->
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="text-lg font-semibold text-slate-800 leading-tight">ตารางแผน PM รายปี</h3>
                <p class="text-[12px] text-slate-500 mt-0.5">แผน PM ที่บันทึกแล้ว และกำหนดการรายสัปดาห์ของปีที่เลือก</p>
            </div>
            <button id="sch-save-btn" type="button" class="btn-gradient shrink-0 px-3 sm:px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i> <span id="sch-save-label">บันทึก</span>
            </button>
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
                <button id="sch-help-btn" type="button" title="วิธีใช้" aria-expanded="false" class="p-1.5 sm:p-2 rounded-lg text-slate-600 hover:bg-white border border-transparent hover:border-slate-200">
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

        <!-- คำอธิบายสี (มือถืออยู่ในกล่องวิธีใช้) + วิธีใช้ (พับเก็บ) -->
        <div class="mt-2.5 hidden sm:flex flex-wrap gap-x-4 gap-y-1">
            <span class="sch-legend"><i style="background:#e0f2fe;color:#0369a1">●</i>รอดำเนินการ</span>
            <span class="sch-legend"><i style="background:#dcfce7;color:#15803d">✓</i>ทำแล้ว</span>
            <span class="sch-legend"><i style="background:#fef3c7;color:#b45309">!</i>เลยกำหนด</span>
            <span class="sch-legend"><i style="background:#fef9c3"></i>แก้ไขแล้ว ยังไม่บันทึก</span>
            <span class="sch-legend"><i style="box-shadow:inset 2px 0 0 #f43f5e,inset -2px 0 0 #f43f5e"></i>สัปดาห์นี้</span>
        </div>
        <div id="sch-help" class="hidden mt-2.5 rounded-lg bg-sky-50 border border-sky-100 p-3 text-[12px] text-slate-600">
            <div class="sm:hidden flex flex-wrap gap-x-4 gap-y-1 mb-2.5">
                <span class="sch-legend"><i style="background:#e0f2fe;color:#0369a1">●</i>รอดำเนินการ</span>
                <span class="sch-legend"><i style="background:#dcfce7;color:#15803d">✓</i>ทำแล้ว</span>
                <span class="sch-legend"><i style="background:#fef3c7;color:#b45309">!</i>เลยกำหนด</span>
                <span class="sch-legend"><i style="background:#fef9c3"></i>แก้ไขแล้ว ยังไม่บันทึก</span>
                <span class="sch-legend"><i style="box-shadow:inset 2px 0 0 #f43f5e,inset -2px 0 0 #f43f5e"></i>สัปดาห์นี้</span>
            </div>
            <ul class="list-disc pl-4 space-y-1">
                <li>แก้ <b>ความถี่ / แจ้งเตือนล่วงหน้า / ระบุวัน / รอบถัดไป</b> ในตาราง แล้วกด <b>บันทึกการแก้ไข</b> (บันทึกหลายแถวพร้อมกันได้)</li>
                <li>ระบบจะสร้างกำหนดการที่ยังไม่ดำเนินการใหม่ ตั้งแต่วันรอบถัดไปจนสิ้นสุดสัญญา — งานที่ทำแล้วและงานค้างก่อนหน้านั้นไม่เปลี่ยน</li>
                <li>ระบุวัน: วันในสัปดาห์ เช่น <b>จ., พฤ.</b> หรือวันที่ในเดือน เช่น <b>1, 15</b> (เฉพาะความถี่ที่ต้องระบุวัน)</li>
                <li>ลากมุมขวาล่างของเซลล์เพื่อคัดลอกลงแถวถัดไป · กรองคอลัมน์ได้จากปุ่ม ▾ บนหัวคอลัมน์</li>
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
    const DAY_OPTIONS = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.', 'อา.'];
    const WEEKDAYS = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.'];
    const EDIT_FIELDS = ['freq', 'alert', 'days', 'next_date'];
    const PLAN_FIRST_COL = 3 + 48;

    let freqOptions = [];
    let alertOptions = [];
    let contract = { start: '', end: '' };
    let agencyName = '';
    let hot = null;
    let original = {};          // plan_id => ค่าเดิมของช่องที่แก้ได้ (ใช้หาแถวที่แก้ไข)
    let overduePlans = 0;
    let today = '';
    let shownYear = null;
    let ready = false;
    let narrowLayout = null;    // จอแคบ (มือถือ) ใช้ความกว้างคอลัมน์ต่างกัน

    const $ = id => document.getElementById(id);
    const freqByDesc = desc => freqOptions.find(o => o.desc === desc);
    const freqByValue = v => freqOptions.find(o => String(o.value) === String(v));
    const alertByDesc = desc => alertOptions.find(a => a.desc === desc);
    const alertByValue = v => alertOptions.find(a => String(a.value) === String(v));
    const escHtml = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const isNarrow = () => window.innerWidth < 640;

    // เต็มจอทั้งเอกสาร => หน้าต่างแจ้งเตือน (z-index 1060) แสดงเหนือการ์ดเต็มจอ (1000) ได้ตามปกติ
    const fsElement = () => document.fullscreenElement || document.webkitFullscreenElement || null;
    const alertBox = opts => Swal.fire(opts);
    // เรียก showLoading ทันที (ไม่ใช้ didOpen: ถ้า Swal.close() ถูกเรียกก่อน popup เปิดเสร็จ didOpen จะสร้าง popup เปล่าค้างไว้)
    const loadingBox = (title, html = '') => { alertBox({ title, html, allowOutsideClick: false, showConfirmButton: false }); Swal.showLoading(); };

    function alertsForFreq(freqDesc) {
        const f = freqByDesc(freqDesc);
        const max = f ? (parseInt(f.alertbeforeforrepeatconfig) || 0) : 0;
        return alertOptions.filter(a => { const v = parseInt(a.value); return v === 0 || v === -1 || v <= max; });
    }

    function parseDays(text) {
        return String(text || '').split(/[,\s]+/).map(s => s.trim()).filter(Boolean)
            .map(s => (/^\d+$/.test(s) ? String(parseInt(s, 10)) : (DAY_OPTIONS.includes(s) ? s : (DAY_OPTIONS.includes(s + '.') ? s + '.' : s))));
    }

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

    function changedRows() {
        if (!hot) return [];
        return hot.getSourceData().filter(r => EDIT_FIELDS.some(f => String(r[f] ?? '') !== String(original[r.plan_id]?.[f] ?? '')));
    }

    let lastRows = [];
    async function loadSchedule() {
        const year = parseInt($('sch-year').value, 10);
        if (changedRows().length) {
            const ok = await alertBox({ icon: 'warning', title: 'มีการแก้ไขที่ยังไม่บันทึก', text: 'ถ้าโหลดข้อมูลใหม่ การแก้ไขจะหายไป',
                showCancelButton: true, confirmButtonText: 'โหลดใหม่', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#006B9F' });
            if (!ok.isConfirmed) { if (shownYear) $('sch-year').value = shownYear; return; }
        }
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

    function renderTable(rows, year, keepEdits) {
        const edits = keepEdits && hot ? Object.fromEntries(hot.getSourceData().map(r => [r.plan_id, r])) : null;
        if (!keepEdits) original = {};
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
            if (!keepEdits) original[r.plan_id] = Object.fromEntries(EDIT_FIELDS.map(fd => [fd, row[fd]]));
            if (edits && edits[r.plan_id]) EDIT_FIELDS.forEach(fd => { row[fd] = edits[r.plan_id][fd]; });
            return row;
        });

        $('sch-empty').classList.toggle('hidden', data.length > 0);
        $('sch-hot').classList.toggle('hidden', data.length === 0);
        if (hot) { hot.destroy(); hot = null; }
        if (!data.length) { updateCount(); return; }

        narrowLayout = isNarrow();
        const nowIdx = String(year) === today.slice(0, 4) ? weekIndexOf(today) : -1;
        const weekCols = Array.from({ length: 48 }, (_, k) => ({ data: 'w' + k, readOnly: true }));
        // จอแคบ: ตรึงแค่คอลัมน์ Name และใช้คอลัมน์แคบลง
        const colWidths = narrowLayout
            ? [130, 90, 120, ...Array(48).fill(40), 140, 140, 90, 100, 90]
            : [180, 110, 150, ...Array(48).fill(45), 160, 170, 110, 110, 100];

        hot = new Handsontable($('sch-hot'), {
            data: data,
            colorScheme: 'light',          // ไม่ใช้โหมดมืดตามเครื่อง (ทั้งหน้าเป็นธีมสว่าง)
            nestedHeaders: [
                ['Name', 'CODE', 'Location', ...MONTHS.map(m => ({ label: `${m} ${year}`, colspan: 4 })), { label: 'แผน PM (แก้ไขได้)', colspan: 4 }, ''],
                ['', '', '', ...Array.from({ length: 48 }, (_, k) => String(k + 1)),
                 'ความถี่', 'แจ้งเตือนล่วงหน้า', 'ระบุวัน', 'รอบถัดไป', 'เริ่มแผน']
            ],
            columns: [
                { data: 'checksheet', readOnly: true },
                { data: 'code', readOnly: true },
                { data: 'location', readOnly: true },
                ...weekCols,
                { data: 'freq', type: 'dropdown', source: freqOptions.map(o => o.desc), strict: true, allowInvalid: false },
                { data: 'alert', type: 'dropdown', strict: true, allowInvalid: false,
                  source: function(query, process) { process(alertsForFreq(this.instance.getDataAtRowProp(this.row, 'freq')).map(a => a.desc)); } },
                { data: 'days' },
                { data: 'next_date', type: 'date', dateFormat: 'YYYY-MM-DD', correctFormat: true, className: 'htCenter',
                  datePickerConfig: { container: document.body,
                                      minDate: contract.start ? new Date(contract.start) : null,
                                      maxDate: contract.end ? new Date(contract.end) : null } },
                { data: 'start_date', readOnly: true, className: 'htCenter text-slate-500' }
            ],
            cells: function(row, col, prop) {
                // ใช้ this.instance (ตอนวาดครั้งแรกตัวแปร hot ยังไม่ถูกกำหนด)
                const ht = this.instance;
                const planId = ht.getDataAtRowProp(row, 'plan_id');
                if (typeof prop === 'string' && /^w\d+$/.test(prop)) {
                    const k = +prop.slice(1);
                    let cls = 'sch-week ' + ((weekCls[planId] || {})[k] || '');
                    if (k % 4 === 3) cls += ' sch-month-end';
                    if (k === nowIdx) cls += ' sch-now';
                    return { className: cls };
                }
                if (!EDIT_FIELDS.includes(prop)) return {};
                const dirty = String(ht.getDataAtRowProp(row, prop) ?? '') !== String(original[planId]?.[prop] ?? '');
                const meta = { className: (prop === 'next_date' ? 'htCenter ' : '') + (dirty ? 'sch-dirty' : 'sch-input') };
                if (prop === 'days') {
                    const f = freqByDesc(ht.getDataAtRowProp(row, 'freq'));
                    const multi = f ? String(f.dropdownmultiselect ?? '') : '';
                    if (!['2', '6', '31'].includes(multi)) return { readOnly: true, className: dirty ? 'sch-dirty' : 'sch-disabled' };
                    meta.readOnly = false;
                }
                return meta;
            },
            afterChange: function(changes, source) {
                if (!changes || source === 'loadData') return;
                if (source !== 'schAuto') {
                    // เปลี่ยนความถี่ => ล้างค่าที่ใช้ไม่ได้กับความถี่ใหม่ (เหมือนตารางนำเข้า)
                    const updates = [];
                    changes.forEach(([row, prop, oldVal, newVal]) => {
                        if (prop !== 'freq' || oldVal === newVal) return;
                        const f = freqByDesc(newVal);
                        const multi = f ? String(f.dropdownmultiselect ?? '') : '';
                        if (multi === '5') updates.push([row, 'days', WEEKDAYS.join(', ')]);
                        else if (!['2', '6', '31'].includes(multi)) updates.push([row, 'days', '']);
                        const a = alertByDesc(this.getDataAtRowProp(row, 'alert'));
                        if (a && !alertsForFreq(newVal).includes(a)) updates.push([row, 'alert', '']);
                    });
                    if (updates.length) this.setDataAtRowProp(updates, null, null, 'schAuto');
                }
                updateCount();
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
            fillHandle: { direction: 'vertical', autoInsertRow: false },
            filters: true,
            dropdownMenu: ['filter_by_condition', 'filter_by_value', 'filter_action_bar'],
            columnSorting: true,
            contextMenu: ['copy', 'cut'],
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
        const changed = changedRows().length;
        const chip = (text, cls) => `<span class="sch-chip ${cls}">${text}</span>`;
        $('sch-summary').innerHTML =
            chip(`แผน PM <b>${total.toLocaleString()}</b>`, 'bg-white border-slate-200 text-slate-600') +
            (overduePlans ? chip(`เลยกำหนด <b>${overduePlans.toLocaleString()}</b> แผน`, 'bg-amber-50 border-amber-200 text-amber-700') : '') +
            (changed ? chip(`แก้ไข <b>${changed.toLocaleString()}</b>`, 'bg-yellow-50 border-yellow-300 text-yellow-800') : '');
        $('sch-save-label').innerText = changed ? `บันทึก (${changed.toLocaleString()})` : 'บันทึก';
        $('sch-save-btn').title = changed ? `บันทึกการแก้ไข ${changed.toLocaleString()} รายการ` : 'บันทึกการแก้ไข';
    }

    function markInvalid(planIds) {
        const bad = new Set(planIds.map(String));
        hot.getSourceData().forEach((r, pr) => {
            const vr = hot.toVisualRow(pr);
            if (vr === null || vr < 0) return;
            EDIT_FIELDS.forEach(c => hot.setCellMeta(vr, hot.propToCol(c), 'valid', !bad.has(String(r.plan_id))));
        });
        hot.render();
    }

    // ---------- บันทึก ----------
    $('sch-save-btn').addEventListener('click', async () => {
        const rows = changedRows();
        if (!rows.length) return alertBox({ icon: 'info', title: 'แจ้งเตือน', text: 'ยังไม่มีแผนที่แก้ไข' });

        const toSave = rows.map(r => {
            const f = freqByDesc(r.freq);
            const multi = f ? String(f.dropdownmultiselect ?? '') : '';
            return {
                plan_id: r.plan_id,
                row: `${r.code} · ${r.checksheet}`,
                freq: f ? f.value : '',
                alert: alertByDesc(r.alert)?.value ?? '',
                days: multi === '5' ? WEEKDAYS : (['2', '6', '31'].includes(multi) ? parseDays(r.days) : []),
                next_date: r.next_date
            };
        });

        const ok = await alertBox({
            title: `บันทึกการแก้ไขแผน PM ${toSave.length.toLocaleString()} รายการ?`,
            html: 'กำหนดการที่ยังไม่ดำเนินการ ตั้งแต่วัน<b>รอบถัดไป</b>จะถูกสร้างใหม่ตามค่าที่แก้ไข จนถึงวันสิ้นสุดสัญญา',
            icon: 'question', showCancelButton: true, confirmButtonText: 'บันทึก', cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#006B9F'
        });
        if (!ok.isConfirmed) return;

        loadingBox('กำลังบันทึก...', 'อาจใช้เวลาสักครู่ กรุณาอย่าปิดหน้านี้');
        try {
            const res = await axios.post('handle_pm_schedule.php?action=update_plans', { plans: toSave });
            if (!res.data.success) {
                if (res.data.errors) markInvalid(res.data.errors.map(e => toSave[e.index].plan_id));
                const list = (res.data.errors || []).slice(0, 50).map(e => `${escHtml(e.row)}: ${escHtml(e.error)}`).join('<br>');
                throw new Error(escHtml(res.data.error) + (list ? `<div class="text-left text-sm mt-2 max-h-60 overflow-y-auto">${list}</div>` : ''));
            }
            const d = res.data.data;
            await alertBox({ icon: 'success', title: 'บันทึกสำเร็จ', text: `แก้ไขแผน PM ${d.plans_updated.toLocaleString()} รายการ · สร้างกำหนดการใหม่ ${d.events_created.toLocaleString()} ครั้ง` });
            original = {};   // บันทึกแล้ว ไม่ต้องถามยืนยันก่อนโหลดใหม่
            hot.getSourceData().forEach(r => { original[r.plan_id] = Object.fromEntries(EDIT_FIELDS.map(f => [f, r[f]])); });
            loadSchedule();
        } catch (e) {
            alertBox({ icon: 'error', title: 'บันทึกไม่สำเร็จ', html: e.message + '<br>(ไม่มีข้อมูลใดถูกบันทึก)' });
        }
    });

    // ---------- Export Excel: รูปแบบเดียวกับ Template นำเข้า (Name / CODE / Location + เดือนละ 4 สัปดาห์) ต่อด้วยข้อมูลแผน PM ----------
    // ส่งออกตามลำดับและตัวกรองที่แสดงอยู่ในตาราง (hot.getData() = ข้อมูลตามลำดับคอลัมน์ที่แสดง)
    $('sch-export-btn').addEventListener('click', async () => {
        if (!hot || !hot.countRows()) return alertBox({ icon: 'info', title: 'แจ้งเตือน', text: 'ไม่มีแผน PM ให้ส่งออก' });
        if (changedRows().length) {
            const ok = await alertBox({ icon: 'warning', title: 'มีการแก้ไขที่ยังไม่บันทึก', text: 'ไฟล์จะใช้ค่าที่แสดงในตารางตอนนี้ (รวมค่าที่ยังไม่บันทึก)',
                showCancelButton: true, confirmButtonText: 'ส่งออก', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#006B9F' });
            if (!ok.isConfirmed) return;
        }

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
    // ไม่ขอเต็มจอเฉพาะการ์ด: Handsontable วางเมนูกรอง/ตัวเลือกวันที่ไว้ใน <body> ซึ่งจะมองไม่เห็นถ้าเต็มจอแค่การ์ด
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

    // หมุนจอ/เปลี่ยนขนาดข้ามจุดตัดมือถือ => สร้างตารางใหม่ด้วยความกว้างคอลัมน์ที่เหมาะสม (คงค่าที่แก้ไว้)
    let resizeTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (hot && narrowLayout !== isNarrow()) renderTable(lastRows, shownYear, true);
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
