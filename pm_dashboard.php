<?php
@session_start();
include "config_ctrl/checksession.php";
//pm_dashboard.php — ปฏิทิน PM (หน้างาน)
//  ปฏิทินแสดง "กลุ่มงาน" (วัน × เช็คชีต: ทำแล้ว/ทั้งหมด) จาก handle_pm_dashboard.php?action=get_summary
//  รายการทีละเครื่องโหลดเมื่อเปิดดูวัน/กลุ่ม (get_events) — หน่วยงานที่มีงานหลายหมื่นรายการไม่ต้องโหลดทั้งหมด
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<style>
    /* ตั้งค่าความสูงเริ่มต้นสำหรับ Desktop */
    #tab-dashboard {
        height: calc(100vh - 180px);
        min-height: 500px;
    }
    #pm-calendar { height: 100% !important; min-height: 400px; }
    .fc { height: 100% !important; }
    .fc-scroller { overflow-y: auto !important; }

    /* ===== โทนเรียบ: เส้นบาง สีน้อย ตัวอักษรสะอาด ===== */
    .fc, .fc .fc-toolbar-title, .fc th, .fc td { font-family: inherit; }
    .fc-theme-standard td, .fc-theme-standard th, .fc-theme-standard .fc-scrollgrid { border-color: #eef1f5 !important; }
    .fc-theme-standard .fc-scrollgrid { border-width: 1px !important; border-radius: 12px; overflow: hidden; }

    .fc .fc-header-toolbar { margin-bottom: 0.75em !important; gap: 8px; }
    .fc .fc-toolbar-title { font-size: 0.95rem !important; font-weight: 600; color: #1e293b; }
    .fc .fc-toolbar-chunk { display: flex; align-items: center; }
    .fc .fc-button {
        background: #ffffff !important; border: 1px solid #e2e8f0 !important; color: #475569 !important; box-shadow: none !important;
        border-radius: 6px !important; font-weight: 500; font-size: 0.78rem !important; text-transform: none !important;
        padding: 0.28em 0.65em !important; line-height: 1.4 !important; transition: background .15s, color .15s, border-color .15s;
    }
    .fc .fc-icon { font-size: 0.95em; }
    .fc .fc-button:hover { background: #f1f5f9 !important; border-color: #cbd5e1 !important; color: #0f172a !important; }
    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active { background: #006B9F !important; border-color: #006B9F !important; color: #fff !important; }
    .fc .fc-button:focus, .fc .fc-button-primary:focus { box-shadow: 0 0 0 3px rgba(0, 107, 159, .15) !important; }
    .fc .fc-button-group .fc-button { border-radius: 0 !important; }
    .fc .fc-button-group .fc-button:first-child { border-top-left-radius: 8px !important; border-bottom-left-radius: 8px !important; }
    .fc .fc-button-group .fc-button:last-child { border-top-right-radius: 8px !important; border-bottom-right-radius: 8px !important; }

    .fc-theme-standard th { background: #fafbfc; }
    .fc .fc-col-header-cell-cushion { color: #64748b; font-size: .72rem; font-weight: 600; padding: 8px 4px; text-decoration: none; }
    .fc .fc-daygrid-day-number { color: #475569; font-size: .78rem; font-weight: 500; padding: 4px 8px; text-decoration: none; }
    .fc .fc-daygrid-day:hover .fc-daygrid-day-frame { background: #fafcff; }
    .fc .fc-day-today { background: rgba(0, 107, 159, .05) !important; }
    .fc .fc-day-today .fc-daygrid-day-number { color: #fff; background: #006B9F; border-radius: 9999px; margin: 3px; padding: 1px 7px; font-weight: 600; }
    .fc .fc-day-other .fc-daygrid-day-number { color: #cbd5e1; }

    /* อีเว้นท์ของเรา (กลุ่มงาน / รายเครื่อง / วันหยุด) วาดเองทั้งหมดด้วย eventContent */
    .fc .pmc-ev { background: transparent !important; border: 0 !important; box-shadow: none !important; padding: 0 !important; margin: 1px 4px !important; cursor: pointer; }
    .fc .pmc-ev:hover { transform: none; }
    .fc-daygrid-event-dot { display: none; }
    .pmc-pill { display: flex; align-items: center; gap: 6px; min-width: 0; padding: 2px 6px; border-radius: 6px; font-size: 11.5px; line-height: 1.45;
        background: #fff; border: 1px solid #e2e8f0; color: #334155; transition: background .15s, border-color .15s; }
    .pmc-pill:hover { border-color: #94a3b8; }
    .pmc-dot { width: 7px; height: 7px; border-radius: 9999px; flex: none; background: #94a3b8; }
    .pmc-name { flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pmc-sub { color: #94a3b8; font-weight: 400; }
    .pmc-cnt { flex: none; font-variant-numeric: tabular-nums; font-weight: 600; font-size: 11px; color: #64748b; }
    .pmc-overdue .pmc-pill { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
    .pmc-overdue .pmc-dot { background: #ef4444; }
    .pmc-overdue .pmc-cnt { color: #b91c1c; }
    .pmc-pending .pmc-pill { background: #f0f9ff; border-color: #bae6fd; color: #0c4a6e; }
    .pmc-pending .pmc-dot { background: #0284c7; }
    .pmc-done .pmc-pill { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
    .pmc-done .pmc-dot { background: #10b981; }
    .pmc-done .pmc-cnt { color: #059669; }
    /* รายปี: ช่องเล็ก แสดงแค่จำนวนงาน */
    .pmc-mini { display: block; text-align: center; font-size: 10.5px; font-weight: 600; border-radius: 4px; padding: 0 2px; line-height: 1.5; }
    .pmc-overdue .pmc-mini { background: #fee2e2; color: #b91c1c; }
    .pmc-pending .pmc-mini { background: #e0f2fe; color: #075985; }
    .pmc-done .pmc-mini { background: #d1fae5; color: #047857; }
    /* รอส่ง: ตรวจครบในหน้าบันทึกพร้อมกันแล้วแต่ยังไม่ส่ง (ร่างในเครื่องนี้) — พื้นเหลือง จุดสีคงตามสถานะเดิม */
    .pmc-wait .pmc-pill { background: #fffbeb; border-color: #fcd34d; color: #92400e; }
    .pmc-wait .pmc-mini { background: #fef3c7; color: #92400e; box-shadow: inset 0 0 0 1px #fcd34d; }
    .pmc-wb { flex: none; font-size: 10px; font-weight: 700; color: #b45309; background: #fef3c7; border-radius: 4px; padding: 0 4px; }
    .pmc-hol { display: flex; align-items: center; gap: 5px; min-width: 0; padding: 1px 6px; border-radius: 6px; font-size: 11px; color: #6d28d9; background: #f5f3ff; border: 1px solid #ddd6fe; }   /* วันหยุด: ม่วง (ไม่ซ้ำกับสีเลยกำหนด) */
    .pmc-hol span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fc-bg-event { opacity: 1 !important; }

    /* ปุ่ม +N (กลุ่มที่เหลือของวัน) */
    #pm-calendar .fc-daygrid-more-link { display: block; float: none !important; margin: 1px 4px 0; padding: 0 6px; border-radius: 6px;
        color: #006B9F; font-size: 11.5px; font-weight: 600; line-height: 1.6; text-decoration: none; }
    #pm-calendar .fc-daygrid-more-link:hover { background: #f0f9ff; }
    #pm-calendar .fc-daygrid-day-bottom { margin-top: 0 !important; }

    /* กำหนดการ (list) */
    .fc .fc-list { border-color: #eef1f5 !important; border-radius: 12px; overflow: hidden; }
    .fc .fc-list-day-cushion { background: #fafbfc !important; padding: 6px 12px !important; }
    .fc .fc-list-day-text, .fc .fc-list-day-side-text { color: #334155; font-weight: 600; font-size: .8rem; text-decoration: none; }
    .fc .fc-list-event:hover td { background: #f8fafc !important; }
    .fc .fc-list-event.pmc-ev .fc-list-event-graphic, .fc .fc-list-event.pmc-ev .fc-list-event-time { display: none; }
    .fc .fc-list-event.pmc-ev td { padding: 4px 10px !important; }
    .fc .fc-list-event.pmc-ev .pmc-pill { font-size: 13px; padding: 5px 10px; }

    .swal2-container { z-index: 10000 !important; }

    /* แผงตัวกรอง: ป๊อปโอเวอร์บนจอใหญ่ / ชีตล่างบนมือถือ */
    #pmc-filter-panel { position: absolute; right: 0; top: calc(100% + 8px); width: 22rem; z-index: 60; }
    #pmc-filter-backdrop { display: none; }

    /* ซ่อน Scrollbar นอกเฉพาะบน Desktop (จอใหญ่) */
    @media (min-width: 769px) {
        body { overflow: hidden; }
    }
    @media (max-width: 768px) {
        body { overflow-y: auto !important; /* สำคัญ: ต้องให้มือถือ Scroll ลงได้ */ }
        #tab-dashboard { height: auto !important; min-height: calc(100vh - 80px); }
        .pmc-calwrap { height: 72vh !important; min-height: 520px; flex: none !important; }
        .fc .fc-toolbar { flex-direction: column; gap: 8px; }
        .fc .fc-toolbar-title { font-size: 1rem !important; }
        #pmc-filter-panel { position: fixed; left: 0; right: 0; bottom: 0; top: auto; width: auto; max-height: 85vh; overflow-y: auto;
            border-radius: 1rem 1rem 0 0 !important; z-index: 1001; }
        #pmc-filter-backdrop.on { display: block; position: fixed; inset: 0; background: rgba(15, 23, 42, .4); z-index: 1000; }
    }

    /* popup รายละเอียดงาน PM */
    .pm-dt-body { margin: 1.25rem 1rem 0 !important; padding: 0 !important; }
    .pm-dt-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .2rem .6rem; border-radius: 9999px; border: 1px solid; font-size: 11.5px; font-weight: 600; white-space: nowrap; }
    .pm-dt-row { display: flex; gap: .6rem; align-items: flex-start; }
    .pm-dt-row > i { width: 1rem; margin-top: .2rem; text-align: center; flex-shrink: 0; }
</style>

<div id="tab-dashboard" class="tab-content block h-full">
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm h-full flex flex-col p-4 md:p-5 min-w-0">
        <!-- หัวแท็บ -->
        <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-slate-800">ปฏิทิน PM</h2>
                <p id="pmc-stats" class="text-xs text-slate-500 mt-0.5">กดที่กลุ่มงานหรือวันที่ เพื่อดูรายการเครื่อง เปิดใบงาน หรือบันทึกพร้อมกัน</p>
            </div>
            <div class="relative grid grid-cols-2 md:flex items-center gap-2 w-full md:w-auto">
                <button type="button" id="pmc-filter-btn" onclick="toggleSidebar()" aria-expanded="false"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-slate-800 text-sm font-medium">
                    <i data-lucide="sliders-horizontal" class="w-4 h-4"></i> ตัวกรอง
                    <span id="pmc-filter-count" class="hidden min-w-[1.25rem] h-5 px-1.5 rounded-full bg-[#006B9F] text-white text-[11px] font-semibold leading-5 text-center">0</span>
                </button>
                <button type="button" onclick="exportCalendarToExcel()" title="Export ช่วงที่แสดงเป็น Excel"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 hover:text-emerald-700 text-sm font-medium">
                    <i data-lucide="download" class="w-4 h-4"></i> Export
                </button>

                <!-- แผงตัวกรอง (รายการตัวเลือกโหลดเมื่อเปิดครั้งแรก) -->
                <div id="pmc-filter-panel" class="hidden bg-white rounded-xl border border-slate-200 shadow-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-sm font-semibold text-slate-800">ตัวกรอง</div>
                        <button type="button" onclick="toggleSidebar(false)" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100" title="ปิด">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="space-y-3">
                        <?php
                        // ช่องตัวกรอง (id เดิม: filter-machine / filter-checksheet / filter-type / filter-location)
                        $pmcFilters = [
                            'filter-machine' => ['เครื่องจักร / อุปกรณ์', 'ระบุชื่อ หรือเลือกจากรายการ...'],
                            'filter-checksheet' => ['เช็คชีต', 'ระบุชื่อ หรือเลือกจากรายการ...'],
                            'filter-type' => ['ประเภทเครื่องจักร', 'ค้นหาประเภท...'],
                            'filter-location' => ['สถานที่ตั้ง', 'ค้นหาสถานที่...'],
                        ];
                        foreach ($pmcFilters as $fid => [$flabel, $fph]) { ?>
                        <div>
                            <label for="<?= $fid ?>" class="block text-xs font-medium text-slate-500 mb-1"><?= $flabel ?></label>
                            <div class="relative">
                                <input type="text" id="<?= $fid ?>" placeholder="<?= $fph ?>" autocomplete="off"
                                    class="w-full border border-slate-200 rounded-lg pl-3 pr-10 py-2 focus:ring-sky-500 focus:border-sky-500 text-sm">
                                <button type="button" id="clear-<?= $fid ?>" onclick="clearFilterInput('<?= $fid ?>')" class="absolute right-7 top-1/2 -translate-y-1/2 text-slate-300 hover:text-red-500 hidden z-10" title="ล้าง">
                                    <i class="fas fa-times text-[11px]"></i>
                                </button>
                                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                                <ul id="dropdown-<?= $fid ?>" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-48 overflow-y-auto hidden"></ul>
                            </div>
                        </div>
                        <?php } ?>
                        <div>
                            <span class="block text-xs font-medium text-slate-500 mb-1">สถานะ</span>
                            <input type="hidden" id="filter-status" value="">
                            <div id="pmc-status-btns" class="grid grid-cols-4 gap-1 p-1 bg-slate-100 rounded-lg" role="radiogroup" aria-label="สถานะ">
                                <?php foreach (['' => ['ทั้งหมด', ''], 'overdue' => ['เลยกำหนด', 'bg-red-500'], 'pending' => ['รอทำ', 'bg-sky-500'], 'done' => ['ทำแล้ว', 'bg-emerald-500']] as $sv => [$sl, $sdot]) { ?>
                                <button type="button" role="radio" data-status="<?= $sv ?>" class="pmc-st-btn flex items-center justify-center gap-1 py-1.5 rounded-md text-xs font-medium text-slate-600 hover:text-slate-800 transition-colors">
                                    <?php if ($sdot) { ?><span class="w-1.5 h-1.5 rounded-full <?= $sdot ?>"></span><?php } ?><?= $sl ?>
                                </button>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="pt-4 mt-4 border-t border-slate-100 grid grid-cols-2 gap-2">
                        <button type="button" onclick="resetFiltersDashboard()" class="border border-slate-200 hover:bg-slate-50 text-slate-600 font-medium py-2.5 rounded-lg text-sm">ล้างค่า</button>
                        <button type="button" onclick="applyDashboardFilters()" class="btn-gradient text-white font-semibold py-2.5 rounded-lg text-sm">กรองข้อมูล</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- คำอธิบายสี (เรียบๆ บรรทัดเดียว) -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mb-3 text-[11.5px] text-slate-500">
            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span> เลยกำหนด</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-sky-600"></span> รอทำ</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> ทำครบแล้ว</span>
            <span class="inline-flex items-center gap-1.5" title="ตรวจครบในหน้าบันทึกพร้อมกันแล้ว แต่ยังไม่กดส่ง (นับจากร่างในเครื่องนี้)"><span class="w-2.5 h-2.5 rounded-sm bg-amber-100 border border-amber-300"></span> รอส่ง</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-violet-100 border border-violet-300"></span> วันหยุด</span>
            <span class="text-slate-400">ตัวเลข = ทำแล้ว / ทั้งหมด</span>
            <span id="pmc-active-filters" class="flex flex-wrap gap-1.5"></span>
        </div>

        <div id="pmc-error" class="hidden mb-3 px-3 py-2 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3"></div>

        <div class="pmc-calwrap relative flex-1 min-h-0">
            <div id="calendar-loading" class="absolute inset-0 bg-white/70 z-40 hidden items-center justify-center rounded-xl">
                <div class="flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-slate-200 shadow-sm text-sm text-slate-500">
                    <i class="fas fa-circle-notch fa-spin text-[#006B9F]"></i> กำลังโหลด...
                </div>
            </div>
            <div id="pm-calendar"></div>
        </div>
    </div>
</div>
<div id="pmc-filter-backdrop" onclick="toggleSidebar(false)"></div>

<script>
    const AG_ID = '<?php echo isset($sess_user_agency_es) ? $sess_user_agency_es : ""; ?>';
    const USER_ID = '<?php echo isset($sess_user_id) ? $sess_user_id : ""; ?>';

    const isMobile = window.innerWidth < 768;

    // สถานะของปฏิทิน: today จากเซิร์ฟเวอร์, วันหยุด (cache), สรุปกลุ่ม วัน|เช็คชีต => {t, n} ใช้กับ "บันทึกพร้อมกัน"
    const PMC = { seq: 0, today: null, holidays: null, groupIdx: {}, optionsLoaded: false };
    const pmcPad = n => String(n).padStart(2, '0');
    const pmcYmd = d => `${d.getFullYear()}-${pmcPad(d.getMonth() + 1)}-${pmcPad(d.getDate())}`;
    const pmcAddDays = (s, n) => { const d = new Date(s + 'T00:00:00'); d.setDate(d.getDate() + n); return pmcYmd(d); };
    const pmcEsc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const pmcToday = () => PMC.today || pmcYmd(new Date());
    // สถานะของกลุ่ม: ทำครบ / เลยกำหนด / รอทำ
    const pmcState = (d, t, n) => n >= t ? 'done' : (d < pmcToday() ? 'overdue' : 'pending');
    const PMC_ORD = { overdue: 1, pending: 2, done: 3 };
    const PMC_FILTERS = { 'filter-machine': 'machine', 'filter-checksheet': 'checksheet', 'filter-type': 'type', 'filter-location': 'location', 'filter-status': 'status' };
    const PMC_STATUS_TEXT = { overdue: 'เลยกำหนด', pending: 'รอทำ', done: 'ทำแล้ว' };
    // ปุ่มเลือกสถานะ (เก็บค่าใน input#filter-status)
    function pmcSyncStatusBtns() {
        const v = document.getElementById('filter-status').value;
        document.querySelectorAll('.pmc-st-btn').forEach(b => {
            const on = b.dataset.status === v;
            b.classList.toggle('bg-white', on); b.classList.toggle('shadow-sm', on); b.classList.toggle('text-slate-800', on);
            b.setAttribute('aria-checked', on ? 'true' : 'false');
        });
    }
    document.querySelectorAll('.pmc-st-btn').forEach(b => b.addEventListener('click', () => {
        document.getElementById('filter-status').value = b.dataset.status; pmcSyncStatusBtns();
    }));
    pmcSyncStatusBtns();

    function pmcFilterParams() {
        const o = {};
        Object.entries(PMC_FILTERS).forEach(([id, key]) => { const v = document.getElementById(id).value.trim(); if (v) o[key] = v; });
        return o;
    }
    async function pmcGet(params) {
        const r = await fetch('handle_pm_dashboard.php?' + new URLSearchParams(params));
        const j = await r.json().catch(() => null);
        if (!j || !j.success) throw new Error((j && j.error) || ('HTTP ' + r.status));
        if (j.today) PMC.today = j.today;
        return j;
    }

    // ===== แผงตัวกรอง =====
    // toggleSidebar(): ชื่อเดิม (เปิด/ปิดแผงตัวกรอง) — open = true/false บังคับสถานะ
    function toggleSidebar(open) {
        const panel = document.getElementById('pmc-filter-panel');
        const on = typeof open === 'boolean' ? open : panel.classList.contains('hidden');
        panel.classList.toggle('hidden', !on);
        document.getElementById('pmc-filter-backdrop').classList.toggle('on', on);
        document.getElementById('pmc-filter-btn').setAttribute('aria-expanded', on ? 'true' : 'false');
        if (on && !PMC.optionsLoaded) {   // โหลดรายการตัวเลือกเมื่อเปิดแผงครั้งแรกเท่านั้น
            PMC.optionsLoaded = true;
            loadDashboardLocations();
            loadDashboardMachineTypes();
            loadDashboardCustomSelectFilters();
        }
    }
    // คลิกนอกแผง => ปิด (จอใหญ่)
    document.addEventListener('mousedown', e => {
        const panel = document.getElementById('pmc-filter-panel');
        if (!panel || panel.classList.contains('hidden')) return;
        if (panel.contains(e.target) || e.target.closest('#pmc-filter-btn') || e.target.closest('.swal2-container')) return;
        toggleSidebar(false);
    });

    function pmcRenderFilterBadge() {
        const f = pmcFilterParams(), n = Object.keys(f).length;
        const badge = document.getElementById('pmc-filter-count');
        badge.textContent = n;
        badge.classList.toggle('hidden', !n);
        const labels = { machine: 'เครื่อง', checksheet: 'เช็คชีต', type: 'ประเภท', location: 'สถานที่', status: 'สถานะ' };
        const idOf = k => Object.keys(PMC_FILTERS).find(id => PMC_FILTERS[id] === k);
        pmcSyncStatusBtns();
        document.getElementById('pmc-active-filters').innerHTML = Object.entries(f).map(([k, v]) => `
            <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 rounded-full bg-slate-100 text-slate-600">
                ${labels[k]}: <b class="font-medium text-slate-700 max-w-[10rem] truncate">${pmcEsc(k === 'status' ? (PMC_STATUS_TEXT[v] || v) : v)}</b>
                <button type="button" class="w-4 h-4 inline-flex items-center justify-center rounded-full hover:bg-slate-200" title="ล้างตัวกรองนี้"
                    onclick="document.getElementById('${idOf(k)}').value=''; document.getElementById('clear-${idOf(k)}')?.classList.add('hidden'); applyDashboardFilters()"><i class="fas fa-times text-[9px]"></i></button>
            </span>`).join('');
    }

    // ฟังก์ชันหลักดึงข้อมูลจาก API
    async function loadDashboardCustomSelectFilters() {
        try {
            // ตรวจสอบ AG_ID (ใช้ค่าว่างหากไม่มี)
            const agIdParam = typeof AG_ID !== 'undefined' ? AG_ID : '';

            // กำหนด URL (ดึงมา 1000 รายการเพื่อให้ครอบคลุมการค้นหาในหน้า Dashboard)
            const urlMachine = `handle_machine_info.php?action=get_all&ag_id=${agIdParam}&startRow=0&endRow=1000`;
            const urlChecksheet = `handle_pm_checksheet.php?action=get_all&ag_id=${agIdParam}&startRow=0&endRow=1000`;

            // ดึง 2 API พร้อมกัน
            const [resMachine, resChecksheet] = await Promise.all([
                fetch(urlMachine).then(r => r.json()),
                fetch(urlChecksheet).then(r => r.json())
            ]);

            // --- 1. จัดเตรียมข้อมูล เครื่องจักร (อิงตาม handle_machine_info.php) ---
            let machineItems = [{ text: 'ทั้งหมด', value: '' }];
            // API เครื่องจักรส่งกลับมาในรูปแบบ { rows: [...] }
            if (resMachine.rows && Array.isArray(resMachine.rows)) {
                resMachine.rows.forEach(item => {
                    const name = item.machine_name;
                    if (name) {
                        machineItems.push({ text: name, value: name });
                    }
                });
            }

            // --- 2. จัดเตรียมข้อมูล เช็คชีต (อิงตาม handle_pm_checksheet.php) ---
            let checksheetItems = [{ text: 'ทั้งหมด', value: '' }];
            // API เช็คชีตส่งกลับมาในรูปแบบ { success: true, data: [...] }
            if (resChecksheet.success && resChecksheet.data) {
                resChecksheet.data.forEach(item => {
                    const name = item.name || item.checksheet_name;
                    if (name) {
                        checksheetItems.push({ text: name, value: name });
                    }
                });
            }

            // --- 3. ติดตั้ง Custom Select ---
            setupCustomSelect('filter-machine', 'dropdown-filter-machine', machineItems);
            setupCustomSelect('filter-checksheet', 'dropdown-filter-checksheet', checksheetItems);

        } catch (error) {
            console.error("เกิดข้อผิดพลาดในการโหลดข้อมูล Filter:", error);
        }
    }

    // ฟังก์ชันดึงข้อมูลประเภทเครื่องจักร
    async function loadDashboardMachineTypes() {
        try {
            const agId = (typeof AG_ID !== 'undefined' && AG_ID) ? AG_ID : (window.AG_ID || '');
            if (!agId) {
                console.error('ไม่พบ AG_ID สำหรับโหลดประเภทเครื่องจักร');
                setupCustomSelect('filter-type', 'dropdown-filter-type', [{ text: 'ทั้งหมด', value: '' }]);
                return;
            }
            const response = await fetch(`get_all_ass_type.php?ag_id=${encodeURIComponent(agId)}`);
            const data = await response.json();
            let items = [{ text: 'ทั้งหมด', value: '' }];
            if (data && Array.isArray(data)) {
                data.forEach(type => {
                    items.push({ text: type.name || type.TGroupName, value: type.name || type.TGroupName });
                });
            }
            setupCustomSelect('filter-type', 'dropdown-filter-type', items);
        } catch (error) {
            console.error("เกิดข้อผิดพลาดในการดึงข้อมูลประเภทเครื่องจักร:", error);
        }
    }

    // ฟังก์ชันดึงข้อมูลสถานที่ตั้ง
    async function loadDashboardLocations() {
        try {
            const response = await fetch(`get_all_building.php?ag_id=${AG_ID}`);
            const data = await response.json();
            let items = [{ text: 'ทั้งหมด', value: '' }];
            if (data && data.building && Array.isArray(data.building)) {
                data.building.forEach(building => {
                    items.push({ text: building.area_name, value: building.area_name });
                });
            }
            setupCustomSelect('filter-location', 'dropdown-filter-location', items);
        } catch (error) {
            console.error("เกิดข้อผิดพลาดในการดึงข้อมูลสถานที่:", error);
        }
    }

    function clearFilterInput(inputId) {
        const inputEl = document.getElementById(inputId);
        inputEl.value = '';
        inputEl.dispatchEvent(new Event('input'));
        inputEl.focus();
    }

    // ใช้ร่วมกับ pm_postpone.php ด้วย
    function setupCustomSelect(inputId, dropdownId, items) {
        const inputEl = document.getElementById(inputId);
        const dropdownEl = document.getElementById(dropdownId);
        const clearBtn = document.getElementById('clear-' + inputId);

        // ฟังก์ชันย่อยสำหรับซ่อน/แสดงปุ่มกากบาท
        const toggleClearBtn = () => {
            if (clearBtn) {
                if (inputEl.value.trim().length > 0) {
                    clearBtn.classList.remove('hidden');
                } else {
                    clearBtn.classList.add('hidden');
                }
            }
        };

        const renderList = (filterText = '') => {
            dropdownEl.innerHTML = '';
            const filtered = items.filter(item =>
                item.text.toLowerCase().includes(filterText.toLowerCase())
            );

            if (filtered.length === 0) {
                dropdownEl.innerHTML = `<li class="px-3 py-2 text-xs text-slate-400 italic">ไม่พบข้อมูล</li>`;
                return;
            }

            filtered.forEach(item => {
                const li = document.createElement('li');
                li.className = 'px-3 py-2 text-xs text-slate-700 cursor-pointer hover:bg-sky-50 border-b border-slate-50 last:border-none';
                li.textContent = item.text;

                li.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    inputEl.value = item.text;
                    dropdownEl.classList.add('hidden');
                    toggleClearBtn();
                });
                dropdownEl.appendChild(li);
            });
        };

        inputEl.addEventListener('focus', () => {
            renderList(inputEl.value);
            dropdownEl.classList.remove('hidden');
        });

        inputEl.addEventListener('input', (e) => {
            renderList(e.target.value);
            dropdownEl.classList.remove('hidden');
            toggleClearBtn(); // อัปเดตปุ่มตอนพิมพ์ข้อความ
        });

        inputEl.addEventListener('blur', () => {
            setTimeout(() => dropdownEl.classList.add('hidden'), 200);
        });
    }

    // ปุ่มกากบาทของช่องตัวกรองต้องทำงานก่อนโหลดรายการตัวเลือกด้วย
    Object.keys(PMC_FILTERS).forEach(id => {
        document.getElementById(id)?.addEventListener('input', e => {
            document.getElementById('clear-' + id)?.classList.toggle('hidden', !e.target.value.trim());
        });
        // Enter = กรองข้อมูล
        document.getElementById(id)?.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); applyDashboardFilters(); } });
    });

    function resetFiltersDashboard() {
        Object.keys(PMC_FILTERS).forEach(id => {
            document.getElementById(id).value = '';
            const clearBtn = document.getElementById('clear-' + id);
            if (clearBtn) clearBtn.classList.add('hidden'); // ซ่อนกากบาท
        });
        toggleSidebar(false);
        window.refreshCalendarEvents();
    }

    function applyDashboardFilters() {
        toggleSidebar(false);
        pmcRefreshView();
    }
    // โหลดช่วงที่แสดงใหม่ (ไม่ต้องโหลดวันหยุดซ้ำ)
    function pmcRefreshView() {
        if (window.calendar) window.refreshCalendarEvents(window.calendar.view.activeStart, window.calendar.view.activeEnd);
    }

    // ===== วันหยุด =====
    const PMC_DAYS = {
        'อา.': 0, 'จ.': 1, 'อ.': 2, 'พ.': 3, 'พฤ.': 4, 'ศ.': 5, 'ส.': 6,
        'Sunday': 0, 'Monday': 1, 'Tuesday': 2, 'Wednesday': 3, 'Thursday': 4, 'Friday': 5, 'Saturday': 6
    };
    async function pmcLoadHolidays() {
        if (PMC.holidays) return PMC.holidays;
        const r = await fetch(`handle_pm_holiday.php?action=get_calendar_events&ag_id=${AG_ID}`).then(r => r.json()).catch(() => null);
        PMC.holidays = (r && r.success && r.data) ? r.data : [];
        return PMC.holidays;
    }
    // ===== รอส่ง: เครื่องที่ตรวจครบในหน้าบันทึกพร้อมกันแล้ว แต่ยังไม่กดส่ง =====
    //  อ่านสรุปที่ pm_worksheet_batch.php เก็บไว้ใน IndexedDB ของเบราว์เซอร์นี้ (pmBatchSum:ag:เช็คชีต:วันที่ => { ready: [event_id] })
    //  => PMC.drafts['วันที่|เช็คชีต'] = [event_id ที่รอส่ง]
    async function pmcLoadDrafts() {
        const out = {};
        try {
            if (!window.indexedDB) return (PMC.drafts = out);
            const db = await new Promise((res, rej) => {
                const r = indexedDB.open('easypro_pm_batch', 1);
                r.onupgradeneeded = () => r.result.createObjectStore('drafts');
                r.onsuccess = () => res(r.result); r.onerror = () => rej(r.error);
            });
            const pre = `pmBatchSum:${AG_ID}:`;
            // ร่างที่บันทึกก่อนมีสรุป (ยังไม่มี pmBatchSum) => สร้างสรุปจากร่างครั้งเดียว (เครื่องที่ ok = ตรวจครบแล้ว)
            await new Promise(res => {
                const st = db.transaction('drafts').objectStore('drafts');
                const dp = `pmBatch:${AG_ID}:`;
                const qk = st.getAllKeys(IDBKeyRange.bound(pre, pre + '￿'));
                qk.onsuccess = () => {
                    const have = new Set(qk.result.map(String));
                    const qd = st.getAllKeys(IDBKeyRange.bound(dp, dp + '￿'));
                    qd.onsuccess = async () => {
                        const todo = qd.result.map(String).filter(k => !have.has(k.replace('pmBatch:', 'pmBatchSum:')));
                        for (const k of todo) {
                            const v = await new Promise(r2 => { const g = db.transaction('drafts').objectStore('drafts').get(k); g.onsuccess = () => r2(g.result); g.onerror = () => r2(null); });
                            const ready = Object.entries((v && v.per) || {}).filter(([, p]) => p && p.ok).map(([id]) => +id);
                            await new Promise(r2 => { const t = db.transaction('drafts', 'readwrite'); t.objectStore('drafts').put({ ready }, k.replace('pmBatch:', 'pmBatchSum:')); t.oncomplete = r2; t.onerror = r2; });
                        }
                        res();
                    };
                    qd.onerror = () => res();
                };
                qk.onerror = () => res();
            });
            await new Promise(res => {
                const q = db.transaction('drafts').objectStore('drafts').openCursor(IDBKeyRange.bound(pre, pre + '￿'));
                q.onsuccess = () => {
                    const c = q.result; if (!c) return res();
                    const m = String(c.key).slice(pre.length).match(/^(\d+):(\d{4}-\d{2}-\d{2})$/);
                    const ids = c.value && Array.isArray(c.value.ready) ? c.value.ready : [];
                    if (m && ids.length) out[m[2] + '|' + m[1]] = ids.map(Number);
                    c.continue();
                };
                q.onerror = () => res();
            });
            db.close();
        } catch (e) { /* ไม่มีร่าง / เบราว์เซอร์ไม่อนุญาต => ไม่แสดงรอส่ง */ }
        return (PMC.drafts = out);
    }
    // จำนวนรอส่งของกลุ่ม (ไม่เกินจำนวนที่ยังไม่ทำ — เครื่องที่บันทึกจากที่อื่นไปแล้วไม่นับ)
    const pmcWait = (d, cs, pending) => Math.min(((PMC.drafts || {})[d + '|' + cs] || []).length, pending);
    const pmcWaitDay = (d, pending) => Math.min(Object.entries(PMC.drafts || {}).reduce((s, [k, ids]) => s + (k.startsWith(d + '|') ? ids.length : 0), 0), pending);

    // ชื่อวันหยุดของวันหนึ่ง (ใช้ในป๊อปอัปรายวัน / Export)
    function pmcHolidaysOn(dateStr) {
        const dow = new Date(dateStr + 'T00:00:00').getDay();
        return (PMC.holidays || []).filter(h =>
            (h.type === 'once' && h.date === dateStr) ||
            (h.type === 'yearly' && String(h.date).slice(5) === dateStr.slice(5)) ||
            (h.type === 'daily' && PMC_DAYS[h.date] === dow)
        ).map(h => ({ name: h.name, type: h.type }));
    }
    // วันหยุดเป็นอีเว้นท์ของปฏิทิน: ครั้งเดียว/ประจำปี = แถบเล็ก, ประจำสัปดาห์ = พื้นหลังจางๆ
    function pmcHolidayEvents(start, end) {
        const out = [];
        const y0 = start.getFullYear(), y1 = end.getFullYear();
        (PMC.holidays || []).forEach(h => {
            const base = { title: h.name, allDay: true, classNames: ['pmc-ev', 'pmc-holiday'], extendedProps: { kind: 'hol', type: h.type }, ord: 0 };
            if (h.type === 'once') out.push({ ...base, id: 'hol_' + h.id, start: h.date });
            else if (h.type === 'yearly') {
                for (let y = y0; y <= y1; y++) out.push({ ...base, id: `hol_${h.id}_yr_${y}`, start: `${y}-${String(h.date).slice(5)}` });
            } else if (h.type === 'daily' && PMC_DAYS[h.date] !== undefined) {
                out.push({ id: 'hol_recurring_' + h.id, title: h.name, daysOfWeek: [PMC_DAYS[h.date]], display: 'background', backgroundColor: '#faf5ff', extendedProps: { kind: 'hol', type: h.type } });
            }
        });
        return out;
    }

    // ===== โหลดข้อมูลปฏิทินตามมุมมอง =====
    //  รายปี (และรายเดือนบนมือถือ) => สรุปรายวัน, รายเดือน/กำหนดการ => กลุ่มวัน×เช็คชีต, รายวัน => รายเครื่อง (ถ้าเกิน 300 งานใช้กลุ่มแทน)
    window.refreshCalendarEvents = async function(activeStart, activeEnd) {
        if (!window.calendar) return;
        if (!activeStart || !activeEnd) {
            // เรียกจากภายนอก (บันทึกงาน/แก้วันหยุด/กลับมาที่แท็บ) => โหลดวันหยุดใหม่ด้วย
            PMC.holidays = null;
            activeStart = window.calendar.view.activeStart;
            activeEnd = window.calendar.view.activeEnd;
        }
        const seq = ++PMC.seq;
        const viewType = window.calendar.view.type;
        const loader = document.getElementById('calendar-loading');
        const errBox = document.getElementById('pmc-error');
        loader.classList.remove('hidden'); loader.classList.add('flex');
        pmcRenderFilterBadge();

        const range = { start: pmcYmd(activeStart), end: pmcYmd(activeEnd), ...pmcFilterParams() };
        try {
            const holP = Promise.all([pmcLoadHolidays(), pmcLoadDrafts()]);
            let rows, kind;
            // รายปี และรายเดือนบนมือถือ (ช่องแคบ) => สรุปรายวัน
            const byDay = viewType === 'multiMonthYear' || (viewType === 'dayGridMonth' && window.innerWidth < 768);
            const sum = await pmcGet({ action: 'get_summary', ...range, group: byDay ? 'day' : 'cs' });
            rows = sum.data; kind = sum.group === 'day' ? 'day' : 'group';
            // รายวัน: ถ้างานไม่เกิน 300 รายการ แสดงทีละเครื่อง
            if (viewType === 'dayGridDay' && rows.length && rows.reduce((s, r) => s + r.t, 0) <= 300) {
                const r = await pmcGet({ action: 'get_events', ...range, limit: 300 });
                rows = r.data; kind = 'event';
            }
            await holP;
            if (seq !== PMC.seq) return;   // มี request ใหม่กว่าแล้ว

            const events = [], idx = {};
            let total = 0, done = 0, overdue = 0;
            rows.forEach(r => {
                if (kind === 'event') {
                    const k = r.start + '|' + r.checksheet_id;
                    const g = idx[k] = idx[k] || { t: 0, n: 0 };
                    g.t++; if (r.status == 1) g.n++;
                    const st = r.status == 1 ? 'done' : (r.start < pmcToday() ? 'overdue' : 'pending');
                    total++; if (st === 'done') done++; if (st === 'overdue') overdue++;
                    const wait = st !== 'done' && ((PMC.drafts || {})[r.start + '|' + r.checksheet_id] || []).includes(+r.id);
                    events.push({ id: 'pm_' + r.id, title: [r.checksheet, r.machine, r.location].filter(Boolean).join(' '), start: r.start, allDay: true,
                        classNames: ['pmc-ev', 'pmc-' + st].concat(wait ? ['pmc-wait'] : []), ord: PMC_ORD[st], extendedProps: { kind: 'event', wait: wait ? 1 : 0, ...r } });
                    return;
                }
                const st = pmcState(r.d, r.t, r.n);
                const w = kind === 'group' ? pmcWait(r.d, r.cs_id, r.t - r.n) : pmcWaitDay(r.d, r.t - r.n);
                total += r.t; done += r.n; if (st === 'overdue') overdue += r.t - r.n;
                if (kind === 'group') idx[r.d + '|' + r.cs_id] = { t: r.t, n: r.n };
                events.push({
                    id: kind === 'group' ? `g_${r.d}_${r.cs_id ?? 'none'}` : 'd_' + r.d,
                    title: kind === 'group' ? (r.cs || 'ไม่ระบุเช็คชีต') : `${r.t} งาน`,
                    start: r.d, allDay: true, classNames: ['pmc-ev', 'pmc-' + st].concat(w ? ['pmc-wait'] : []), ord: PMC_ORD[st],
                    extendedProps: { kind, state: st, wait: w, ...r }
                });
            });
            PMC.groupIdx = idx;
            events.push(...pmcHolidayEvents(activeStart, activeEnd));

            window.calendar.batchRendering(() => {
                window.calendar.getEventSources().forEach(src => src.remove());
                window.calendar.addEventSource(events);
            });
            errBox.classList.add('hidden');
            const nf = n => n.toLocaleString('th-TH');
            document.getElementById('pmc-stats').innerHTML = total
                ? `ช่วงที่แสดง: งาน ${nf(total)} · <span class="${overdue ? 'text-red-600 font-medium' : ''}">เลยกำหนด ${nf(overdue)}</span> · รอทำ ${nf(total - done - overdue)} · <span class="text-emerald-600">ทำแล้ว ${nf(done)}</span>`
                : 'ไม่มีงาน PM ในช่วงที่แสดง' + (Object.keys(pmcFilterParams()).length ? ' (ตามตัวกรอง)' : '');
        } catch (error) {
            if (seq !== PMC.seq) return;
            console.error("Error updating calendar:", error);
            errBox.innerHTML = `<span><i class="fas fa-exclamation-circle mr-1"></i> โหลดปฏิทินไม่สำเร็จ: ${pmcEsc(error.message)}</span>
                <button type="button" onclick="refreshCalendarEvents()" class="px-3 py-1 rounded-lg border border-red-200 bg-white text-xs font-medium hover:bg-red-50">ลองใหม่</button>`;
            errBox.classList.remove('hidden');
        } finally {
            if (seq === PMC.seq) { loader.classList.remove('flex'); loader.classList.add('hidden'); }
        }
    };

    // หน้าตาอีเว้นท์: กลุ่ม (ชื่อเช็คชีต + ทำแล้ว/ทั้งหมด), รายวันในมุมมองรายปี (จำนวน), รายเครื่อง, วันหยุด
    function pmcEventContent(arg) {
        const p = arg.event.extendedProps;
        if (arg.event.display === 'background') return { html: '' };
        if (p.kind === 'hol') return { html: `<div class="pmc-hol"><i class="fas fa-umbrella-beach text-[10px]"></i><span>${pmcEsc(arg.event.title)}</span></div>` };
        if (p.kind === 'day') {
            if (arg.view.type === 'multiMonthYear' || window.innerWidth < 768) return { html: `<span class="pmc-mini">${p.t}</span>` };
            return { html: `<div class="pmc-pill"><span class="pmc-dot"></span><span class="pmc-name">${p.t} งาน</span>${p.wait ? `<span class="pmc-wb">รอส่ง ${p.wait}</span>` : ''}<span class="pmc-cnt">${p.n}/${p.t}</span></div>` };
        }
        if (p.kind === 'event') {
            const sub = [p.checksheet, p.location].filter(Boolean).map(pmcEsc).join(' · ');
            return { html: `<div class="pmc-pill"><span class="pmc-dot"></span><span class="pmc-name"><b class="font-medium">${pmcEsc(p.machine || '-')}</b>${sub ? ` <span class="pmc-sub">· ${sub}</span>` : ''}</span>${p.status == 1 ? '<i class="fas fa-check text-[10px] text-emerald-600"></i>' : p.wait ? '<span class="pmc-wb">รอส่ง</span>' : ''}</div>` };
        }
        return { html: `<div class="pmc-pill"><span class="pmc-dot"></span><span class="pmc-name">${pmcEsc(arg.event.title)}</span>${p.wait ? `<span class="pmc-wb">รอส่ง ${p.wait}</span>` : ''}<span class="pmc-cnt">${p.n}/${p.t}</span></div>` };
    }
    function pmcEventTip(info) {
        const p = info.event.extendedProps;
        if (p.kind === 'group' || p.kind === 'day') {
            const left = p.t - p.n;
            info.el.title = `${p.kind === 'group' ? (p.cs || 'ไม่ระบุเช็คชีต') : 'งาน PM'} · ทำแล้ว ${p.n} จาก ${p.t}` + (left ? ` · ${p.state === 'overdue' ? 'เลยกำหนด' : 'รอทำ'} ${left}` : '') + (p.wait ? ` · รอส่ง ${p.wait}` : '');
        } else if (p.kind === 'hol') info.el.title = 'วันหยุด: ' + info.event.title;
        else if (p.kind === 'event') info.el.title = info.event.title;
    }

    async function exportCalendarToExcel() {
        if (!window.calendar) return;

        if (typeof ExcelJS === 'undefined') {
            Swal.fire('ข้อผิดพลาด', 'ไม่พบไลบรารี ExcelJS กรุณาตรวจสอบการติดตั้ง', 'error');
            return;
        }

        const view = window.calendar.view;
        // ดึงงานในช่วงวันที่ที่กำลังแสดงจากเซิร์ฟเวอร์ (ปฏิทินเก็บแค่ข้อมูลสรุป)
        const activeStart = view.activeStart;
        const activeEnd = view.activeEnd;
        const viewTitle = view.title.replace(/[/\\?%*:|"<>]/g, '-');

        Swal.fire({ title: 'กำลังเตรียมไฟล์ Excel...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        let res;
        try {
            res = await pmcGet({ action: 'get_events', export: 1, start: pmcYmd(activeStart), end: pmcYmd(activeEnd), ...pmcFilterParams() });
            await pmcLoadHolidays();
        } catch (e) {
            Swal.fire('ข้อผิดพลาด', 'โหลดข้อมูลสำหรับ Export ไม่สำเร็จ: ' + e.message, 'error');
            return;
        }

        // รายการ: งาน PM + วันหยุดในช่วงที่แสดง  { date, title, isHoliday, status, completed_at, location }
        const events = res.data.map(r => ({
            date: r.start, title: [r.checksheet, r.machine, r.location].filter(Boolean).join(' '),
            isHoliday: false, status: r.status, completed_at: r.completed_at, location: r.location
        }));
        for (let d = pmcYmd(activeStart), end = pmcYmd(activeEnd); d < end; d = pmcAddDays(d, 1)) {
            pmcHolidaysOn(d).forEach(h => events.push({ date: d, title: h.name, isHoliday: true }));
        }

        if (res.data.length === 0) {
            Swal.fire('ไม่พบข้อมูล', 'ไม่มีข้อมูลในมุมมองนี้ที่สามารถ Export ได้', 'warning');
            return;
        }
        if (res.total > res.data.length) {
            const ok = await Swal.fire({ icon: 'warning', title: 'ข้อมูลมากเกินไป', text: `ช่วงนี้มีงาน ${res.total.toLocaleString()} รายการ จะ Export ได้ ${res.data.length.toLocaleString()} รายการแรก (เลือกมุมมองรายเดือน หรือใช้ตัวกรองเพื่อให้ครบ)`,
                showCancelButton: true, confirmButtonText: 'Export ต่อ', cancelButtonText: 'ยกเลิก' });
            if (!ok.isConfirmed) return;
        }
        Swal.close();

        // สร้าง Workbook ใหม่
        const workbook = new ExcelJS.Workbook();
        workbook.creator = 'PM System';

        // เช็คว่าผู้ใช้กำลังดูมุมมองแบบไหน
        const isMonthView = view.type === 'dayGridMonth';

        // ==========================================
        // 1. ถ้าเป็นมุมมอง "รายเดือน" ให้วาดตารางแบบปฏิทิน
        // ==========================================
        if (isMonthView) {
            const calSheet = workbook.addWorksheet('Calendar View', { views: [{ showGridLines: false }] });
            calSheet.columns = [
                { width: 18 }, { width: 18 }, { width: 18 }, { width: 18 },
                { width: 18 }, { width: 18 }, { width: 18 }
            ];

            // --- หัวตาราง (ชื่อเดือน) ---
            const titleRow = calSheet.addRow([`แผนปฏิบัติการ PM : ${view.title}`]);
            calSheet.mergeCells('A1:G1');
            titleRow.getCell(1).font = { size: 16, bold: true, color: { argb: 'FFFFFFFF' } };
            titleRow.getCell(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF0d6efd' } };
            titleRow.getCell(1).alignment = { horizontal: 'center', vertical: 'middle' };
            titleRow.height = 35;

            // --- หัวตาราง (วันอาทิตย์-เสาร์) ---
            const days = ["อาทิตย์", "จันทร์", "อังคาร", "พุธ", "พฤหัสบดี", "ศุกร์", "เสาร์"];
            const headerRow = calSheet.addRow(days);
            headerRow.height = 25;
            headerRow.eachCell((cell, colNumber) => {
                cell.font = { bold: true, color: { argb: 'FFFFFFFF' } };
                cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: (colNumber === 1 || colNumber === 7) ? '94a3b8' : 'FF6c757d' } };
                cell.alignment = { horizontal: 'center', vertical: 'middle' };
                cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
            });

            // --- จัดกลุ่มข้อมูลตามวันที่ (วันหยุดขึ้นก่อน) ---
            let eventMap = {};
            [...events].sort((a, b) => (b.isHoliday - a.isHoliday)).forEach(e => {
                const dateStr = e.date;
                if (!eventMap[dateStr]) eventMap[dateStr] = [];
                let prefix = e.isHoliday ? '' : '• ';
                let richTextParts = [];
                if (!e.isHoliday) {
                    let isCompleted = e.status == 1;
                    if (isCompleted && e.completed_at) {
                        let compDate = moment(e.completed_at).format('DD/MM/YYYY');
                        richTextParts.push({ text: `[ทำแล้ว ${compDate}] `, font: { color: { argb: 'FF00B050' }, bold: true } });
                    } else if (isCompleted) {
                        richTextParts.push({ text: '[ทำแล้ว] ', font: { color: { argb: 'FF00B050' }, bold: true } });
                    } else {
                        richTextParts.push({ text: '[ยังไม่ทำ] ', font: { color: { argb: 'FFFF0000' }, bold: true } });
                    }
                    richTextParts.push({ text: prefix + e.title, font: { color: { argb: 'FF000000' } } });
                } else {
                    richTextParts.push({ text: prefix + e.title, font: { color: { argb: 'FF0070C0' } } });
                }
                eventMap[dateStr].push({ richTextData: richTextParts, isHoliday: e.isHoliday });
            });

            // --- สร้างช่องปฏิทิน ---
            let currentDate = window.calendar.getDate();
            let year = currentDate.getFullYear();
            let month = currentDate.getMonth();
            let firstDay = new Date(year, month, 1);
            let lastDay = new Date(year, month + 1, 0);

            let currentWeekRow = [];
            let startDayOfWeek = firstDay.getDay();

            // เติมช่องว่างก่อนวันที่ 1
            for (let i = 0; i < startDayOfWeek; i++) currentWeekRow.push("");

            for (let d = 1; d <= lastDay.getDate(); d++) {
                let dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                let cellData;
                if (eventMap[dateStr] && eventMap[dateStr].length > 0) {
                    let richTextArray = [
                        { font: { color: { argb: 'FF000000' }, bold: true }, text: `${d}\n\n` }
                    ];
                    eventMap[dateStr].forEach((ev, index) => {
                        richTextArray.push(...ev.richTextData);
                        if (index < eventMap[dateStr].length - 1) richTextArray.push({ text: '\n' });
                    });
                    cellData = { richText: richTextArray };
                } else {
                    cellData = `${d}`;
                }
                currentWeekRow.push(cellData);

                // เมื่อครบ 1 สัปดาห์ (หรือวันสุดท้ายของเดือน) ให้ขึ้นบรรทัดใหม่
                if (currentWeekRow.length === 7 || d === lastDay.getDate()) {
                    while (currentWeekRow.length < 7) currentWeekRow.push("");
                    let row = calSheet.addRow(currentWeekRow);
                    row.height = 120;
                    row.eachCell((cell, colNumber) => {
                        cell.alignment = { vertical: 'top', horizontal: 'left', wrapText: true };
                        cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
                        if (colNumber === 1 || colNumber === 7) {
                            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF8F9FA' } };
                        }
                    });
                    currentWeekRow = [];
                }
            }
        }

        // ==========================================
        // 2. สร้างหน้า Data List สำหรับทุกมุมมอง
        // ==========================================
        const listSheetName = isMonthView ? 'Data List' : `Schedule (${view.title.substring(0,20)})`.replace(/[\\/?*\[\]:]/g, '-');
        const listSheet = workbook.addWorksheet(listSheetName);

        listSheet.columns = [
            { header: 'วันที่', key: 'date', width: 15 },
            { header: 'รายการ', key: 'title', width: 40 },
            { header: 'ประเภท', key: 'type', width: 15 },
            { header: 'สถานที่', key: 'location', width: 25 },
            { header: 'สถานะ', key: 'status', width: 15 },
            { header: 'วันที่ทำเสร็จ', key: 'completed_at', width: 20 },
        ];

        listSheet.getRow(1).eachCell(cell => {
            cell.font = { bold: true, color: { argb: 'FFFFFFFF' } };
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF198754' } };
            cell.alignment = { vertical: 'middle', horizontal: 'center' };
            cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
        });

        // ใส่ข้อมูลเรียงตามวันที่
        events.sort((a, b) => a.date < b.date ? -1 : a.date > b.date ? 1 : (b.isHoliday - a.isHoliday)).forEach(e => {
            let statusLabel = '-';
            let statusColor = 'FF000000';
            let completedDateLabel = '-';

            if (!e.isHoliday) {
                if (e.status == 1) {
                    statusLabel = 'ทำแล้ว';
                    statusColor = 'FF00B050';
                    if (e.completed_at) completedDateLabel = moment(e.completed_at).format('DD/MM/YYYY HH:mm');
                } else {
                    statusLabel = 'ยังไม่ทำ';
                    statusColor = 'FFFF0000';
                }
            }

            let row = listSheet.addRow({
                date: moment(e.date).format('DD/MM/YYYY'),
                title: e.title,
                status: statusLabel,
                type: e.isHoliday ? 'วันหยุด' : 'แผน PM',
                location: e.location || '-',
                completed_at: completedDateLabel
            });

            row.getCell('status').font = { color: { argb: statusColor }, bold: true };
            row.eachCell(cell => {
                cell.alignment = { vertical: 'middle', wrapText: true };
                cell.border = { top: {style:'thin'}, left: {style:'thin'}, bottom: {style:'thin'}, right: {style:'thin'} };
            });
            if (e.isHoliday) {
                row.eachCell(cell => {
                    cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFFF3CD' } };
                });
            }
        });

        // ดาวน์โหลดไฟล์
        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        saveAs(blob, `PM_Schedule_${viewTitle}.xlsx`);
    }

    // ==========================================
    // ===== บันทึกพร้อมกันหลายเครื่อง (เช็คชีตเดียวกัน วันเดียวกัน) =====
    // ==========================================
    function pmSameDayPending(csId, dateStr) {
        if (!csId) return 0;
        const g = PMC.groupIdx[dateStr + '|' + csId];
        return g ? g.t - g.n : 0;
    }
    function openPmBatch(csId, dateStr, back) {
        window.openPmBatchDialog('pm_worksheet_batch.php?checksheet_id=' + encodeURIComponent(csId) + '&date=' + encodeURIComponent(dateStr), back ? { back } : undefined);
    }

    // กดกลุ่มงานในปฏิทิน: กลุ่มมีงานเดียว => รายละเอียดงานนั้น, หลายงาน => ป๊อปอัปของวันโดยเปิดกลุ่มนี้ไว้
    async function openPmGroup(p) {
        if (p.t > 1) { openPmDayPanel(p.d, p.cs_id ?? 'none'); return; }
        try {
            const r = await pmcGet({ action: 'get_events', start: p.d, end: pmcAddDays(p.d, 1), cs: p.cs_id ?? 'none', limit: 1, ...pmcFilterParams() });
            if (r.data[0]) window.openPmWorksheetDialog(r.data[0].id, r.data[0].status == 1);
        } catch (e) {
            Swal.fire('ข้อผิดพลาด', 'โหลดงาน PM ไม่สำเร็จ: ' + e.message, 'error');
        }
    }

    // ป๊อปอัปงานของวัน: จัดกลุ่มตามเช็คชีต + ปุ่มบันทึกพร้อมกัน (รายเครื่องโหลดเมื่อกด "รายเครื่อง")
    //  openCs = id เช็คชีตที่ให้เปิดรายการเครื่องไว้เลย
    function openPmDayPanel(dateStr, openCs) {
        const esc = pmcEsc;
        const filters = pmcFilterParams();
        const dTh = new Date(dateStr + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'long', year: 'numeric' });
        let arr = [];
        const token = openPmDayPanel.token = {};   // กันผลของป๊อปอัปก่อนหน้ามาเขียนทับ

        function body() {
            const total = arr.reduce((s, g) => s + g.t, 0);
            const pendingAll = arr.reduce((s, g) => s + g.pending, 0);
            const waitAll = arr.reduce((s, g) => s + g.wait, 0);
            const late = dateStr < pmcToday();
            const holidays = pmcHolidaysOn(dateStr);
            return `
            <div class="text-left">
                <div class="text-sm text-slate-500 mb-3">งาน PM ${total.toLocaleString()} งาน · ${late && pendingAll ? '<span class="text-red-600">เลยกำหนด</span>' : 'ยังไม่ทำ'} <b class="${late && pendingAll ? 'text-red-600' : 'text-[#006B9F]'}">${pendingAll.toLocaleString()}</b> · ทำแล้ว <b class="text-emerald-600">${(total - pendingAll).toLocaleString()}</b>${waitAll ? ` · <span class="text-amber-700">รอส่ง <b>${waitAll.toLocaleString()}</b></span>` : ''}${Object.keys(filters).length ? ' <span class="text-slate-400">(ตามตัวกรอง)</span>' : ''}</div>
                ${holidays.length ? `<div class="mb-3 flex flex-wrap gap-1.5">${holidays.map(h => `<span class="pm-dt-chip bg-violet-50 text-violet-700 border-violet-200"><i class="fas fa-umbrella-beach"></i> ${esc(h.name)}</span>`).join('')}</div>` : ''}
                ${arr.length ? `<div class="relative mb-3">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="search" data-q placeholder="ค้นหาเช็คชีต หรือ รหัส / ชื่อเครื่อง / สถานที่" autocomplete="off"
                        class="w-full border border-slate-200 rounded-lg pl-8 pr-3 py-2 text-sm focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none">
                </div>` : ''}
                <div class="space-y-2.5 max-h-[60vh] overflow-y-auto pr-1" data-scroll>
                <div data-mres class="hidden"></div>
                <div data-noresult class="hidden text-center text-slate-400 py-6 text-sm">ไม่พบเช็คชีตหรือเครื่องที่ตรงกับคำค้น</div>
                ${arr.map((g, gi) => `
                    <div class="border rounded-xl p-3 ${g.wait ? '' : 'border-slate-200'}" data-group="${gi}" data-name="${esc(String(g.name).toLowerCase())}">
                        <div class="flex items-center gap-2 flex-wrap">
                            <div class="flex-1 min-w-[10rem]">
                                <div class="font-semibold text-slate-800 text-sm flex items-center gap-2"><span class="w-2 h-2 rounded-full flex-none ${g.pending ? (late ? 'bg-red-500' : 'bg-sky-600') : 'bg-emerald-500'}"></span>${esc(g.name)}</div>
                                <div class="text-xs text-slate-500 mt-0.5">${g.t} เครื่อง · ยังไม่ทำ <b class="${g.pending ? (late ? 'text-red-600' : 'text-[#006B9F]') : ''}">${g.pending}</b>${g.wait ? ` · <span class="text-amber-700">รอส่ง <b>${g.wait}</b></span>` : ''}${g.t - g.pending ? ` · ทำแล้ว <b class="text-emerald-600">${g.t - g.pending}</b>` : ''}</div>
                            </div>
                            ${g.pending >= 2 && g.id ? `<button type="button" data-batch="${esc(g.id)}" class="px-3 py-2 rounded-lg bg-[#006B9F] hover:bg-[#005a87] text-white text-xs font-semibold inline-flex items-center gap-1.5"><i class="fas fa-layer-group"></i> บันทึกพร้อมกัน ${g.pending} เครื่อง</button>` : ''}
                            ${g.t === 1
                                ? (() => { const d = g.n === 1; return `<button type="button" data-open="${esc(g.id1)}" data-done="${d ? 1 : 0}" class="px-3 py-2 rounded-lg ${d ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-sky-600 hover:bg-sky-700'} text-white text-xs font-semibold inline-flex items-center gap-1.5"><i class="fas ${d ? 'fa-search' : 'fa-external-link-alt'}"></i> ${d ? 'ดูประวัติ' : 'เปิดใบงาน'}</button>`; })()
                                : `<button type="button" data-toggle="${gi}" class="px-3 py-2 rounded-lg border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 inline-flex items-center gap-1.5">รายเครื่อง <i class="fas fa-chevron-down text-[10px]"></i></button>`}
                        </div>
                        <div data-list="${gi}" class="hidden mt-2.5 grid gap-1.5 sm:grid-cols-2 max-h-64 overflow-y-auto"></div>
                    </div>`).join('') || '<div class="text-center text-slate-400 py-6 text-sm">ไม่มีงาน PM ในวันนี้</div>'}
                </div>
            </div>`;
        }

        // รายการเครื่องของกลุ่ม (โหลดจากเซิร์ฟเวอร์ครั้งแรกที่เปิด สูงสุด 500 เครื่อง)
        async function loadList(popup, gi) {
            const box = popup.querySelector(`[data-list="${gi}"]`);
            if (!box) return;
            box.classList.remove('hidden');
            if (box.dataset.ready) return;
            box.dataset.ready = '1';
            box.innerHTML = '<div class="text-xs text-slate-400 py-2 sm:col-span-2"><i class="fas fa-circle-notch fa-spin mr-1"></i> กำลังโหลด...</div>';
            const g = arr[gi];
            try {
                const r = await pmcGet({ action: 'get_events', start: dateStr, end: pmcAddDays(dateStr, 1), cs: g.id ?? 'none', ...filters });
                g.list = r.data;
                box.innerHTML = r.data.map((ev, i) => {
                    const d = ev.status == 1, w = !d && ((PMC.drafts || {})[dateStr + '|' + g.id] || []).includes(+ev.id);
                    return `<button type="button" data-ev="${gi}:${i}" title="${w ? 'รอส่ง (ตรวจครบในหน้าบันทึกพร้อมกันแล้ว)' : ''}" class="text-left px-2.5 py-1.5 rounded-lg border ${d ? 'border-emerald-200 bg-emerald-50/50' : w ? 'border-amber-300 bg-amber-50' : 'border-slate-200'} hover:bg-sky-50 text-xs flex items-center gap-2 min-w-0">
                        <span class="w-2 h-2 rounded-full flex-none ${d ? 'bg-emerald-500' : w ? 'bg-amber-500' : 'bg-[#006B9F]'}"></span><span class="truncate">${esc(ev.machine || ev.checksheet || '-')}</span>${ev.location ? `<span class="truncate text-slate-400 ml-auto">${esc(ev.location)}</span>` : ''}</button>`;
                }).join('') + (r.total > r.data.length ? `<div class="text-xs text-slate-400 py-1 sm:col-span-2">แสดง ${r.data.length} จาก ${r.total.toLocaleString()} เครื่อง — ใช้ตัวกรองเพื่อค้นหาเครื่อง</div>` : '');
            } catch (e) {
                box.dataset.ready = '';
                box.innerHTML = `<div class="text-xs text-red-600 py-2 sm:col-span-2">โหลดรายการไม่สำเร็จ: ${esc(e.message)}</div>`;
            }
        }

        // ค้นหา: กรองกลุ่มตามชื่อเช็คชีตทันที + ค้นเครื่อง (รหัส/ชื่อ/S/N/สถานที่) จากเซิร์ฟเวอร์
        let mres = [], qSeq = 0, qTimer = null;
        function bindSearch(popup) {
            const inp = popup.querySelector('[data-q]');
            if (!inp) return;
            inp.addEventListener('input', () => {
                const q = inp.value.trim(), ql = q.toLowerCase();
                let shown = 0;
                popup.querySelectorAll('[data-group]').forEach(el => {
                    const on = !ql || el.dataset.name.includes(ql);
                    el.classList.toggle('hidden', !on); if (on) shown++;
                });
                clearTimeout(qTimer);
                const box = popup.querySelector('[data-mres]');
                if (q.length < 2) { box.classList.add('hidden'); box.innerHTML = ''; mres = []; popup.querySelector('[data-noresult]').classList.toggle('hidden', !!shown || !ql); return; }
                box.classList.remove('hidden');
                box.innerHTML = '<div class="text-xs text-slate-400 py-1"><i class="fas fa-circle-notch fa-spin mr-1"></i> กำลังค้นหาเครื่อง...</div>';
                const seq = ++qSeq;
                qTimer = setTimeout(async () => {
                    try {
                        const r = await pmcGet({ action: 'get_events', start: dateStr, end: pmcAddDays(dateStr, 1), q, limit: 50, ...filters });
                        if (seq !== qSeq || !popup.isConnected) return;
                        mres = r.data;
                        box.innerHTML = mres.length ? `<div class="text-xs font-semibold text-slate-500 mb-1.5">เครื่องที่ตรงกับ "${esc(q)}" ${r.total.toLocaleString()} เครื่อง${r.total > mres.length ? ` (แสดง ${mres.length})` : ''}</div>
                            <div class="grid gap-1.5 sm:grid-cols-2 mb-1">${mres.map((ev, i) => { const d = ev.status == 1; return `<button type="button" data-mev="${i}" class="text-left px-2.5 py-1.5 rounded-lg border ${d ? 'border-emerald-200 bg-emerald-50/50' : 'border-slate-200'} hover:bg-sky-50 text-xs min-w-0">
                                <span class="flex items-center gap-2 min-w-0"><span class="w-2 h-2 rounded-full flex-none ${d ? 'bg-emerald-500' : 'bg-[#006B9F]'}"></span><b class="truncate">${esc(ev.machine_code || '')}</b><span class="truncate">${esc(ev.machine || '-')}</span></span>
                                <span class="block truncate text-slate-400 pl-4">${esc(ev.checksheet || '')}${ev.location ? ' · ' + esc(ev.location) : ''}</span></button>`; }).join('')}</div>`
                            : '';
                        if (!mres.length) box.classList.add('hidden');
                        popup.querySelector('[data-noresult]').classList.toggle('hidden', !!shown || !!mres.length);
                    } catch (e) {
                        if (seq === qSeq) box.innerHTML = `<div class="text-xs text-red-600 py-1">ค้นหาเครื่องไม่สำเร็จ: ${esc(e.message)}</div>`;
                    }
                }, 300);
            });
        }

        Swal.fire({
            title: `<span class="text-lg font-bold text-slate-800">งาน PM วันที่ ${dTh}</span>`,
            html: '<div class="py-8 text-sm text-slate-400"><i class="fas fa-circle-notch fa-spin mr-1"></i> กำลังโหลด...</div>',
            width: '40rem', showConfirmButton: false, showCloseButton: true,
            customClass: { popup: 'rounded-3xl border-none shadow-2xl' },
            didOpen: async (popup) => {
                popup.addEventListener('click', e => onClick(popup, e));
                try {
                    const [r] = await Promise.all([
                        pmcGet({ action: 'get_summary', group: 'cs', force: 1, start: dateStr, end: pmcAddDays(dateStr, 1), ...filters }),
                        pmcLoadHolidays(),
                        pmcLoadDrafts()
                    ]);
                    arr = r.data.map(g => ({ id: g.cs_id, name: g.cs || 'ไม่ระบุเช็คชีต', t: g.t, n: g.n, id1: g.id1, pending: g.t - g.n, wait: pmcWait(dateStr, g.cs_id, g.t - g.n) }))
                        .sort((a, b) => b.pending - a.pending || a.name.localeCompare(b.name, 'th'));
                    arr.forEach(g => { PMC.groupIdx[dateStr + '|' + g.id] = { t: g.t, n: g.n }; });
                } catch (e) {
                    const hc = Swal.getHtmlContainer();
                    if (hc) hc.innerHTML = `<div class="py-6 text-sm text-red-600">โหลดงานของวันนี้ไม่สำเร็จ: ${esc(e.message)}</div>`;
                    return;
                }
                const hc = Swal.getHtmlContainer();
                if (!hc || !popup.isConnected || openPmDayPanel.token !== token) return;
                hc.innerHTML = body();
                bindSearch(popup);
                if (openCs !== undefined) {
                    const gi = arr.findIndex(g => String(g.id ?? 'none') === String(openCs));
                    if (gi >= 0 && arr[gi].t > 1) {
                        loadList(popup, gi);
                        popup.querySelector(`[data-group="${gi}"]`)?.scrollIntoView({ block: 'nearest' });
                    }
                }
            }
        });
        function onClick(popup, e) {
            const b = e.target.closest('[data-batch]');
            if (b) { Swal.close(); openPmBatch(b.dataset.batch, dateStr, { fn: () => openPmDayPanel(dateStr, b.dataset.batch), label: 'กลับ' }); return; }
            const me = e.target.closest('[data-mev]');
            if (me) { const ev = mres[+me.dataset.mev]; if (ev) { Swal.close(); window.openPmWorksheetDialog(ev.id, ev.status == 1, { back: { fn: () => openPmDayPanel(dateStr), label: 'กลับ' } }); } return; }
            const op = e.target.closest('[data-open]');
            if (op) { Swal.close(); window.openPmWorksheetDialog(op.dataset.open, op.dataset.done === '1', { back: { fn: () => openPmDayPanel(dateStr), label: 'กลับ' } }); return; }
            const tg = e.target.closest('[data-toggle]');
            if (tg) {
                const box = popup.querySelector(`[data-list="${tg.dataset.toggle}"]`);
                if (box.dataset.ready && !box.classList.contains('hidden')) box.classList.add('hidden');
                else loadList(popup, +tg.dataset.toggle);
                return;
            }
            const one = e.target.closest('[data-ev]');
            if (one) {
                const [gi, i] = one.dataset.ev.split(':').map(Number);
                const ev = arr[gi] && arr[gi].list && arr[gi].list[i];
                if (ev) { Swal.close(); window.openPmWorksheetDialog(ev.id, ev.status == 1, { back: { fn: () => openPmDayPanel(dateStr, arr[gi].id ?? 'none'), label: 'กลับ' } }); }
            }
        }
    }

    // กดอีเว้นท์ที่ยังไม่ทำ และวันนั้นมีเครื่องอื่นในเช็คชีตเดียวกันที่ยังไม่ทำ => ถามก่อน: บันทึกพร้อมกันหลายเครื่อง / รายเครื่อง
    function openPmEventChooser(eventId, props, start) {
        const dateStr = moment(start).format('YYYY-MM-DD');
        const same = props.status == 1 ? 0 : pmSameDayPending(props.checksheet_id, dateStr);
        if (same < 2) { window.openPmWorksheetDialog(eventId, props.status == 1); return; }
        const esc = pmcEsc;
        const dTh = new Date(dateStr + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'long', year: 'numeric' });
        const card = (act, icon, color, title, desc) => `
            <button type="button" data-choose="${act}" class="w-full text-left p-4 rounded-2xl border-2 border-slate-200 hover:border-${color}-400 hover:bg-${color}-50/60 transition-all flex items-center gap-3.5 group">
                <span class="w-11 h-11 shrink-0 rounded-xl bg-${color}-100 text-${color}-600 flex items-center justify-center text-lg"><i class="${icon}"></i></span>
                <span class="flex-1 min-w-0">
                    <span class="block font-semibold text-slate-800">${title}</span>
                    <span class="block text-xs text-slate-500 mt-0.5">${desc}</span>
                </span>
                <i class="fas fa-chevron-right text-slate-300 group-hover:text-${color}-500"></i>
            </button>`;
        Swal.fire({
            html: `
                <div class="text-left">
                    <div class="text-lg font-bold text-slate-800 pr-8">${esc(props.checksheet || 'งาน PM')}</div>
                    <div class="text-sm text-slate-500 mt-0.5">วันที่ ${dTh} · มีงานเช็คชีตนี้ที่ยังไม่ทำ <b class="text-[#006B9F]">${same} เครื่อง</b></div>
                    <div class="mt-4 space-y-2.5">
                        ${card('batch', 'fas fa-layer-group', 'sky', `บันทึกพร้อมกันหลายเครื่อง (${same} เครื่อง)`, 'ผลตรวจปกติเหมือนกัน กรอกผล เลือกผู้ตรวจ และเซ็นครั้งเดียว')}
                        ${card('one', 'fas fa-file-alt', 'emerald', 'รายเครื่อง', `เปิดใบงานของ ${esc(props.machine || 'เครื่องนี้')} (รายละเอียดงานอยู่ในใบงาน)`)}
                    </div>
                </div>`,
            width: '32rem', showConfirmButton: false, showCloseButton: true,
            customClass: { popup: 'rounded-3xl border-none shadow-2xl' },
            didOpen: (popup) => {
                popup.addEventListener('click', e => {
                    const b = e.target.closest('[data-choose]');
                    if (!b) return;
                    Swal.close();
                    if (b.dataset.choose === 'batch') openPmBatch(props.checksheet_id, dateStr);
                    else window.openPmWorksheetDialog(eventId, false, { back: { fn: () => openPmEventChooser(eventId, props, start), label: 'กลับ' } });
                });
            }
        });
    }

    // รายละเอียดงาน (สถานะ เลยกำหนด ความถี่ เวลาโดยประมาณ ทำครั้งล่าสุด ประวัติเลื่อน ผลการทำงาน) แสดงในใบงาน PM แล้ว
    //  กดงานในปฏิทิน => เปิดใบงานเป็น dialog ทันที (window.openPmWorksheetDialog ใน pm.php)

    // เริ่มต้นแท็บเมื่อเปิดครั้งแรก (ดู pmLazy ใน pm.php) — สร้างปฏิทิน แล้ว datesSet จะโหลดข้อมูลเอง
    pmLazy('dashboard', function() {
        const calendarEl = document.getElementById('pm-calendar');
        if (!calendarEl) return;

        window.calendar = new FullCalendar.Calendar(calendarEl, {
            // มือถือ: เริ่มที่มุมมองกำหนดการ (รายการ) อ่านง่ายกว่าตารางเดือน
            initialView: isMobile ? 'listMonth' : 'dayGridMonth',
            dayMaxEvents: isMobile ? 2 : 3,   // แสดงไม่เกิน 3 กลุ่มต่อวัน ที่เหลือเป็น +N
            allDayText: 'ทั้งวัน',
            // กด +N: ป๊อปอัปงานของวัน (แทนกล่องรายการเดิมของปฏิทิน)
            moreLinkClick: info => { openPmDayPanel(pmcYmd(info.date)); return info.view.type; },
            moreLinkText: n => '+' + n + ' เพิ่มเติม',
            locale: 'th',
            height: '100%',
            handleWindowResize: true,
            stickyHeaderToolbar: true,
            eventOrder: 'ord,title',
            noEventsContent: 'ไม่มีงาน PM ในช่วงนี้',
            buttonText: {
                today: 'วันนี้',
                multiMonthYear: 'รายปี',
                month: 'รายเดือน',
                day: 'รายวัน',
                list: 'กำหนดการ'
            },
            headerToolbar: {
                left: isMobile ? 'prev,next' : 'prev,next today',
                center: 'title',
                right: 'multiMonthYear,dayGridMonth,dayGridDay,listMonth'
            },
            views: {
                multiMonthYear: { type: 'multiMonth', duration: { years: 1 }, dayMaxEvents: false },
                dayGridDay: { dayMaxEvents: false }   // รายวัน: แสดงทุกงาน (ไม่เกิน 300 งาน ถ้าเกินแสดงเป็นกลุ่ม)
            },
            themeSystem: 'standard',
            datesSet: info => window.refreshCalendarEvents(info.view.activeStart, info.view.activeEnd),
            eventContent: pmcEventContent,
            eventDidMount: pmcEventTip,
            eventClick: function(info) {
                const ev = info.event, p = ev.extendedProps;
                const eventId = ev.id;
                info.jsEvent.preventDefault();

                if (p.kind === 'group') openPmGroup(p);                      // กลุ่มวัน × เช็คชีต
                else if (p.kind === 'day') openPmDayPanel(p.d);              // สรุปรายวัน (รายปี)
                else if (eventId && eventId.startsWith('pm_')) {             // รายเครื่อง (รายวัน)
                    openPmEventChooser(eventId.replace('pm_', ''), p, ev.start);
                }
                else if (eventId && eventId.startsWith('hol_')) {           // วันหยุด
                    Swal.fire({
                        title: `<span class="text-xl font-bold text-slate-800">วันหยุด</span>`,
                        html: `
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-violet-50 rounded-full flex items-center justify-center mb-4 text-violet-600">
                                    <i class="fas fa-umbrella-beach text-3xl"></i>
                                </div>
                                <div class="text-lg font-bold text-slate-700 mb-1">${pmcEsc(ev.title)}</div>
                                <div class="text-sm text-slate-500">วันที่ ${moment(ev.start).format('DD/MM/YYYY')}</div>
                            </div>
                        `,
                        confirmButtonText: 'รับทราบ',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'bg-slate-800 hover:bg-slate-900 text-white px-8 py-2 rounded-xl font-semibold text-sm transition-all',
                            popup: 'rounded-3xl border-none shadow-2xl',
                            title: 'pt-8'
                        }
                    });
                }
            },
        });

        window.calendar.render();
    });

	function openPmFullWindow(url) {
		const features = [
			'width=' + screen.availWidth,
			'height=' + screen.availHeight,
			'left=0',
			'top=0',
			'resizable=yes',
			'scrollbars=yes',
			'toolbar=no',
			'menubar=no',
			'location=no',
			'status=no'
		].join(',');

		const newWindow = window.open(url, '_blank', features);

		if (newWindow) {
			newWindow.moveTo(0, 0);
			newWindow.resizeTo(screen.availWidth, screen.availHeight);
			newWindow.focus();
		} else {
			Swal.fire({
				icon: 'warning',
				title: 'ไม่สามารถเปิดหน้าต่างใหม่ได้',
				text: 'กรุณาอนุญาต Popup ของเว็บไซต์นี้ก่อนใช้งาน',
				confirmButtonColor: '#006b9f'
			});
		}
	}
</script>
