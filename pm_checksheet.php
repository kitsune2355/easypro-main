<?php
if (!headers_sent()) { header('Content-Type: text/html; charset=UTF-8'); }
@session_start();
include "config_ctrl/checksession.php";
//pm_checksheet.php — แท็บ "สร้างเช็คชีต": รายการเช็คชีต (ค้นหา/กรอง/แบ่งหน้าที่เซิร์ฟเวอร์) + ตัวแก้ไขแบบเต็มหน้า
//  โหลดข้อมูลเมื่อเปิดแท็บครั้งแรก (pmLazy) และสร้าง Handsontable เมื่อเปิดตัวแก้ไขครั้งแรก
?>
<style>
    #tab-checksheet .cs-cell { display: flex; flex-direction: column; justify-content: center; height: 100%; line-height: 1.35; min-width: 0; }
    #tab-checksheet .cs-cell .l1 { font-size: 13.5px; font-weight: 500; color: #1e293b; white-space: normal; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    #tab-checksheet .cs-cell .l2 { font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    #tab-checksheet .cs-act { width: 2rem; height: 2rem; border-radius: .5rem; display: inline-flex; align-items: center; justify-content: center; color: #94a3b8; transition: color .15s, background-color .15s; }
    #tab-checksheet .cs-act:hover { background: #f1f5f9; }
    #tab-checksheet .cs-act.v:hover { color: #006B9F; }
    #tab-checksheet .cs-act.e:hover { color: #0284c7; }
    #tab-checksheet .cs-act.c:hover { color: #4f46e5; }
    #tab-checksheet .cs-act.d:hover { color: #dc2626; background: #fef2f2; }
    #tab-checksheet .cs-file { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .55rem; border-radius: 9999px; border: 1px solid #e2e8f0; font-size: 12px; color: #475569; background: #fff; white-space: nowrap; }
    #tab-checksheet .cs-file:hover { border-color: #7dd3fc; color: #006B9F; background: #f0f9ff; }
    #tab-checksheet .cs-sec-no { width: 1.6rem; height: 1.6rem; border-radius: 9999px; background: #e0f2fe; color: #006B9F; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    #tab-checksheet .cs-in { width: 100%; border: 1px solid #cbd5e1; border-radius: .5rem; padding: .5rem .65rem; font-size: 14px; outline: none; background: #fff; }
    #tab-checksheet .cs-in:focus { box-shadow: 0 0 0 2px rgba(14,165,233,.35); border-color: #38bdf8; }
    #tab-checksheet .cs-in.border-red-500 { border-color: #ef4444; }
    #tab-checksheet .ag-theme-alpine { --ag-row-hover-color: #f8fafc; --ag-header-background-color: #f8fafc; --ag-borders: none; --ag-row-border-color: #f1f5f9; --ag-font-family: 'Kanit', sans-serif; }
    #tab-checksheet .ag-header-cell-text { font-size: 12px; font-weight: 500; color: #64748b; }
</style>

<div id="tab-checksheet" class="tab-content hidden">

    <!-- ===== รายการเช็คชีต ===== -->
    <div id="cs-list-view" class="space-y-3">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="min-w-0">
                <h3 class="text-lg font-semibold text-slate-800">เช็คชีต</h3>
                <p class="text-xs text-slate-500">แบบฟอร์มจุดตรวจที่ใช้กับแผน PM — สร้าง แก้ไข หรือคัดลอกจากของเดิม</p>
            </div>
            <button type="button" onclick="openChecksheetEditor()" class="btn-gradient w-full sm:w-auto justify-center px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 shrink-0">
                <i data-lucide="plus" class="w-4 h-4"></i> สร้างเช็คชีต
            </button>
        </div>

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 p-3 border-b border-slate-100">
                <div class="relative flex-1 sm:max-w-sm">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="search" id="cs-search" placeholder="ค้นหาชื่อเช็คชีต หรือเลขที่เอกสาร" class="w-full pl-9 pr-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500/30">
                </div>
                <select id="cs-filter-type" class="sm:w-56 border border-slate-200 rounded-lg py-2 px-3 text-sm bg-white outline-none focus:ring-2 focus:ring-sky-500/30">
                    <option value="">ทุกประเภทเครื่องจักร</option>
                </select>
                <span id="cs-count" class="text-xs text-slate-500 sm:ml-auto whitespace-nowrap"></span>
            </div>
            <div id="cs-list-error" class="hidden mx-3 mt-3 px-3 py-2 rounded-lg bg-red-50 border border-red-100 text-sm text-red-600 flex items-center justify-between gap-2">
                <span>โหลดรายการเช็คชีตไม่สำเร็จ</span>
                <button type="button" onclick="loadChecksheets()" class="text-xs font-medium underline">ลองใหม่</button>
            </div>
            <div class="p-2 sm:p-0">
                <div><div id="checksheet-grid" class="ag-theme-alpine" style="height: max(380px, calc(100vh - 360px)); width: 100%;"></div></div>
            </div>
        </div>
    </div>

    <!-- ===== ตัวแก้ไขเช็คชีต (แสดงแทนรายการ) ===== -->
    <div id="cs-editor-view" class="hidden space-y-3 pb-2">
        <!-- แถบหัวตัวแก้ไข ติดด้านบนขณะเลื่อน (พื้นสีหน้าเว็บบังเนื้อหาที่เลื่อนผ่านช่อง padding ของ main) -->
        <div class="sticky -top-3 sm:-top-4 z-30 -mt-3 sm:-mt-4 pt-3 sm:pt-4 bg-[#F0F4F8]">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-3 py-2.5 sm:px-4 flex items-center gap-2 sm:gap-3">
            <button type="button" onclick="closeChecksheetEditor()" title="กลับไปรายการ" aria-label="กลับไปรายการ" class="w-9 h-9 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 flex items-center justify-center shrink-0">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </button>
            <div class="min-w-0 flex-1">
                <h3 id="cs-editor-title" class="text-base sm:text-lg font-semibold text-slate-800 leading-tight truncate">สร้างเช็คชีต</h3>
                <p id="cs-editor-sub" class="text-xs text-slate-500 truncate">กรอกข้อมูลทั่วไป แล้วเพิ่มจุดตรวจ</p>
            </div>
            <button type="button" id="cs-btn-reset" onclick="resetChecksheetForm()" title="ล้างข้อมูล" aria-label="ล้างข้อมูล" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50">
                <i data-lucide="eraser" class="w-4 h-4"></i> ล้างข้อมูล
            </button>
            <button type="button" onclick="closeChecksheetEditor()" class="hidden sm:inline-flex px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50">ยกเลิก</button>
            <button type="button" onclick="saveChecksheet()" title="บันทึก" class="btn-gradient px-3 sm:px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 shrink-0">
                <i data-lucide="save" class="w-4 h-4"></i> บันทึก
            </button>
        </div>
        </div>

        <div id="cs-editor-loading" class="hidden bg-white rounded-xl border border-slate-200 shadow-sm py-16 text-center text-sm text-slate-400">กำลังโหลด...</div>

        <div id="cs-editor-body" class="space-y-3">
            <input type="hidden" id="checksheet-id" value="">

            <!-- 1. ข้อมูลทั่วไป -->
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
                <div class="flex items-center gap-2 mb-4">
                    <span class="cs-sec-no">1</span>
                    <h4 class="font-semibold text-slate-800">ข้อมูลทั่วไป</h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div class="md:col-span-4">
                        <label for="checksheet-name" class="block text-sm text-slate-600 mb-1">ชื่อเช็คชีต <span class="text-red-500">*</span></label>
                        <input type="text" id="checksheet-name" class="cs-in" placeholder="เช่น PM ปั๊มน้ำ รายเดือน">
                    </div>
                    <div class="md:col-span-2">
                        <label for="checksheet-dept" class="block text-sm text-slate-600 mb-1">ประเภทเครื่องจักร <span class="text-red-500">*</span></label>
                        <select id="checksheet-dept" class="cs-in"><option value="">กำลังโหลด...</option></select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="checksheet-doc-no" class="block text-sm text-slate-600 mb-1">เลขที่เอกสาร <span class="text-red-500">*</span></label>
                        <input type="text" id="checksheet-doc-no" class="cs-in">
                    </div>
                    <div>
                        <label for="checksheet-rev-no" class="block text-sm text-slate-600 mb-1">Revision No. <span class="text-red-500">*</span></label>
                        <input type="text" id="checksheet-rev-no" value="00" class="cs-in">
                    </div>
                    <div class="md:col-span-2">
                        <label for="checksheet-effective-date" class="block text-sm text-slate-600 mb-1">วันที่มีผล <span class="text-red-500">*</span></label>
                        <input type="date" id="checksheet-effective-date" class="cs-in">
                    </div>
                    <div>
                        <label for="checksheet-estimated-time" class="block text-sm text-slate-600 mb-1">เวลาที่ใช้ (นาที) <span class="text-red-500">*</span></label>
                        <input type="number" id="checksheet-estimated-time" min="0" placeholder="60" class="cs-in">
                    </div>
                </div>
            </section>

            <!-- 2. จุดตรวจ -->
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2">
                        <span class="cs-sec-no">2</span>
                        <h4 class="font-semibold text-slate-800">จุดตรวจ</h4>
                        <span id="cs-item-count" class="text-xs text-slate-500"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="number" id="row-add-count" value="1" min="1" aria-label="จำนวนแถวที่จะเพิ่ม" class="w-16 border border-slate-200 rounded-lg py-1.5 px-2 text-sm text-center outline-none focus:ring-2 focus:ring-sky-500/30">
                        <button type="button" onclick="addHotRows()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:bg-slate-50">
                            <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มแถว
                        </button>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mb-2">คลิกช่อง "รูปภาพประกอบ" เพื่อเลือกรูป · คลิกขวาที่ตารางเพื่อแทรก/ลบแถว</p>
                <div class="border border-slate-200 rounded-lg overflow-hidden relative z-0">
                    <div id="hot-checksheet" class="w-full"></div>
                    <input type="file" id="hot-image-input" accept="image/*" style="display:none;">
                </div>
            </section>

            <!-- 3. อะไหล่ -->
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <div class="flex items-center gap-2">
                        <span class="cs-sec-no">3</span>
                        <h4 class="font-semibold text-slate-800">อะไหล่ที่ต้องใช้</h4>
                        <span class="text-xs text-slate-400">(ไม่บังคับ)</span>
                    </div>
                    <button type="button" onclick="addSpareRow()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:bg-slate-50">
                        <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มแถว
                    </button>
                </div>
                <div class="max-w-2xl border border-slate-200 rounded-lg overflow-hidden relative z-0">
                    <div id="hot-spare-parts"></div>
                </div>
            </section>

            <!-- 4. ไฟล์แนบ -->
            <section class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 sm:p-5">
                <div class="flex items-center gap-2 mb-4">
                    <span class="cs-sec-no">4</span>
                    <h4 class="font-semibold text-slate-800">ไฟล์แนบ</h4>
                    <span class="text-xs text-slate-400">(ไม่บังคับ)</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-sm text-slate-600">ภาพจุดตรวจสอบ <span id="cs-img-count" class="text-xs text-slate-400">0/5</span></span>
                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer">
                                <i data-lucide="image-plus" class="w-4 h-4"></i> เลือกรูป
                                <input type="file" id="checksheet-images" multiple accept="image/*" onchange="previewImages(this)" class="hidden">
                            </label>
                        </div>
                        <div id="image-previews" class="flex flex-wrap gap-2 min-h-[5rem] p-2 rounded-lg border border-dashed border-slate-200"></div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-sm text-slate-600">คำแนะนำในการทำงาน / WI <span class="text-xs text-slate-400">สูงสุด 5 ไฟล์ · ≤5MB</span></span>
                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer">
                                <i data-lucide="paperclip" class="w-4 h-4"></i> เลือกไฟล์
                                <input type="file" id="work-instructions" multiple onchange="handleWorkInstructionsSelect(this)" class="hidden">
                            </label>
                        </div>
                        <div id="instruction-previews" class="flex flex-wrap gap-2 min-h-[5rem] p-2 rounded-lg border border-dashed border-slate-200"></div>
                    </div>
                </div>
            </section>

            <!-- ปุ่มท้ายฟอร์ม -->
            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button type="button" onclick="closeChecksheetEditor()" class="w-full sm:w-auto px-4 py-2.5 rounded-lg border border-slate-200 bg-white text-sm text-slate-600 hover:bg-slate-50">ยกเลิก</button>
                <button type="button" onclick="saveChecksheet()" class="btn-gradient w-full sm:w-auto justify-center px-5 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> บันทึกเช็คชีต
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const AG_ID = '<?php echo isset($sess_user_agency_es) ? $sess_user_agency_es : ""; ?>';
    const USER_ID = '<?php echo isset($sess_user_id) ? $sess_user_id : ""; ?>';
    const $ = id => document.getElementById(id);
    const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const fmtD = v => v ? window.formatDate(v, false) : '-';

    const EMPTY_ITEM = () => ['', '', '', '', 'ผ่าน / ไม่ผ่าน', '', 'ไม่บังคับ', '', '', ''];
    const EMPTY_SPARE = () => ['', ''];
    const TYPE_REV = { 1: 'ผ่าน / ไม่ผ่าน', 2: 'ผ่าน / ไม่ผ่าน / ไม่เกี่ยวข้อง' };
    const REQ_REV = { 1: 'บังคับ', 2: 'ไม่บังคับ', 3: 'บังคับเฉพาะกรณีที่ไม่ผ่าน' };

    let checksheetImageFiles = [];
    let workInstructionFiles = [];
    let csDirty = false;          // มีการแก้ไขที่ยังไม่บันทึก
    let typesReady = null;        // Promise โหลดประเภทเครื่องจักร (ครั้งเดียวต่อการเปิดหน้า)
    let gridApi = null;
    const listState = { q: '', typeId: '' };
    const rowCache = {};          // id => แถวที่โหลดล่าสุด (ใช้ตอนยืนยันการลบ)

    // ไอคอน lucide เป็น SVG string (ใช้ในเซลล์ตารางที่ถูกวาดใหม่บ่อย ไม่ต้องสแกนทั้งหน้า)
    const icoCache = {};
    function ico(name, cls = 'w-4 h-4') {
        const k = name + '|' + cls;
        if (icoCache[k]) return icoCache[k];
        let html = `<i data-lucide="${name}" class="${cls}"></i>`;
        try {
            const node = lucide.icons[name.replace(/(^|-)(\w)/g, (m, d, c) => c.toUpperCase())];
            const el = lucide.createElement(node);
            el.setAttribute('class', cls);
            html = el.outerHTML;
        } catch (e) { /* ใช้ <i data-lucide> แทน */ }
        return (icoCache[k] = html);
    }

    // ---------- ประเภทเครื่องจักร (ใช้ทั้งตัวกรองและฟอร์ม) ----------
    function loadMachineTypes() {
        if (typesReady) return typesReady;
        const deptSelect = $('checksheet-dept');
        const filterSel = $('cs-filter-type');
        typesReady = (async () => {
            if (!AG_ID) { deptSelect.innerHTML = '<option value="">ไม่พบข้อมูลหน่วยงาน</option>'; return; }
            try {
                const res = await axios.get(`get_all_ass_type.php?ag_id=${encodeURIComponent(AG_ID)}`);
                const data = Array.isArray(res.data) ? res.data : [];
                const opts = data.map(it => `<option value="${esc(it.id || it.ass_type_id || it.GroupId)}">${esc(it.name || it.ass_type_name || it.TGroupName)}</option>`).join('');
                deptSelect.innerHTML = '<option value="">-- เลือกประเภทเครื่องจักร --</option>' + opts;
                filterSel.innerHTML = '<option value="">ทุกประเภทเครื่องจักร</option>' + opts;
                if (!data.length) deptSelect.innerHTML = '<option value="">ไม่พบข้อมูลประเภทเครื่องจักร</option>';
            } catch (error) {
                console.error('ไม่สามารถโหลดข้อมูลประเภทเครื่องจักรได้:', error);
                deptSelect.innerHTML = '<option value="">เกิดข้อผิดพลาดในการโหลดข้อมูล</option>';
                typesReady = null; // เปิดตัวแก้ไขครั้งหน้าจะลองใหม่
            }
        })();
        return typesReady;
    }

    // ---------- รายการเช็คชีต (AG Grid infinite + handle_pm_checksheet.php?action=list) ----------
    const datasource = {
        getRows: (params) => {
            $('cs-list-error').classList.add('hidden');
            axios.get('handle_pm_checksheet.php', {
                params: {
                    action: 'list',
                    startRow: params.startRow,
                    endRow: params.endRow,
                    q: listState.q,
                    type_id: listState.typeId,
                    sortModel: JSON.stringify(params.sortModel || [])
                }
            }).then(res => {
                if (!res.data || !res.data.success) throw new Error(res.data?.error || 'โหลดไม่สำเร็จ');
                const rows = res.data.data, total = res.data.totalCount;
                rows.forEach(r => { rowCache[r.id] = r; });
                params.successCallback(rows, total);
                $('cs-count').textContent = `${Number(total).toLocaleString()} รายการ`;
                if (total === 0) {
                    const filtered = listState.q || listState.typeId;
                    gridApi.setGridOption('overlayNoRowsTemplate', filtered
                        ? '<span class="text-sm text-slate-400">ไม่พบเช็คชีตที่ตรงกับการค้นหา</span>'
                        : '<span class="text-sm text-slate-400">ยังไม่มีเช็คชีต — กด "สร้างเช็คชีต" เพื่อเริ่ม</span>');
                    gridApi.showNoRowsOverlay();
                } else gridApi.hideOverlay();
            }).catch(err => {
                console.error('โหลดรายการเช็คชีตไม่สำเร็จ', err);
                $('cs-list-error').classList.remove('hidden');
                $('cs-count').textContent = '';
                params.failCallback();
            });
        }
    };

    const pending = '<div class="cs-cell"><div class="h-3 w-2/3 bg-slate-100 rounded"></div></div>';
    const columnDefs = [
        {
            headerName: 'เช็คชีต', field: 'name', flex: 2, minWidth: 260, sortable: true,
            cellRenderer: p => !p.data ? pending : `<div class="cs-cell">
                <button type="button" class="l1 text-left hover:text-[#006B9F]" title="ดูรายละเอียด" onclick="previewChecksheet(${Number(p.data.id)})">${esc(p.data.name || '-')}</button>
                <span class="l2">${esc(p.data.docNo || 'ไม่มีเลขที่เอกสาร')} · Rev. ${esc(p.data.revNo || '-')}</span></div>`
        },
        {
            headerName: 'ประเภทเครื่องจักร', field: 'category', flex: 1, minWidth: 150, sortable: true,
            cellRenderer: p => !p.data ? '' : `<div class="cs-cell"><span class="l2 !text-[13px] ${p.data.category ? '!text-slate-700' : '!text-slate-400'}">${esc(p.data.category || 'ไม่ระบุ')}</span></div>`
        },
        {
            headerName: 'จุดตรวจ', field: 'itemCount', width: 190, sortable: true,
            cellRenderer: p => !p.data ? '' : `<div class="cs-cell"><span class="l1">${p.data.itemCount} จุด</span>
                <span class="l2">${p.data.estimatedTime || 0} นาที${p.data.sparePartsCount ? ` · อะไหล่ ${p.data.sparePartsCount}` : ''}</span></div>`
        },
        {
            // ไฟล์แนบ: กดเพื่อดูรูปจุดตรวจ / เอกสาร WI (โหลดรายชื่อไฟล์เมื่อกด)
            headerName: 'ไฟล์แนบ', field: 'fileCount', width: 200, sortable: false,
            cellRenderer: p => !p.data ? '' : `<div class="cs-cell">${p.data.fileCount
                ? `<span class="flex gap-1.5">
                    ${p.data.imageCount ? `<button type="button" class="cs-file" onclick="showChecksheetFiles(${Number(p.data.id)}, 'images')" title="ดูภาพจุดตรวจสอบ">${ico('image', 'w-3.5 h-3.5')} รูป ${p.data.imageCount}</button>` : ''}
                    ${p.data.docCount ? `<button type="button" class="cs-file" onclick="showChecksheetFiles(${Number(p.data.id)}, 'docs')" title="ดูคำแนะนำในการทำงาน">${ico('file-text', 'w-3.5 h-3.5')} เอกสาร ${p.data.docCount}</button>` : ''}
                  </span>`
                : '<span class="l2">ไม่มี</span>'}</div>`
        },
        {
            headerName: 'ใช้ในแผน', field: 'planCount', width: 110, sortable: false,
            cellRenderer: p => !p.data ? '' : `<div class="cs-cell">${p.data.planCount
                ? `<span class="l1">${p.data.planCount.toLocaleString()} แผน</span>`
                : '<span class="l2">ยังไม่ใช้</span>'}</div>`
        },
        {
            headerName: 'วันที่มีผล', field: 'effectiveDate', width: 160, sortable: true,
            cellRenderer: p => !p.data ? '' : `<div class="cs-cell"><span class="l1">${fmtD(p.data.effectiveDate)}</span>
                <span class="l2">แก้ไข ${fmtD(p.data.updatedAt)}</span></div>`
        },
        {
            headerName: '', field: 'id', width: 160, sortable: false, cellClass: 'flex items-center justify-end',
            cellRenderer: p => !p.data ? '' : `<div class="flex items-center justify-end gap-0.5 h-full">
                <button type="button" class="cs-act v" title="ดูรายละเอียด" onclick="previewChecksheet(${Number(p.data.id)})">${ico('eye')}</button>
                <button type="button" class="cs-act e" title="แก้ไข" onclick="editChecksheet(${Number(p.data.id)})">${ico('pencil')}</button>
                <button type="button" class="cs-act c" title="คัดลอกเป็นเช็คชีตใหม่" onclick="copyChecksheet(${Number(p.data.id)})">${ico('copy')}</button>
                <button type="button" class="cs-act d" title="ลบ" onclick="deleteChecksheet(${Number(p.data.id)})">${ico('trash-2')}</button></div>`
        }
    ];

    function initList() {
        const gridDiv = $('checksheet-grid');
        gridApi = agGrid.createGrid(gridDiv, {
            rowModelType: 'infinite',
            datasource,
            pagination: true,
            paginationPageSize: 50,
            paginationPageSizeSelector: false,
            cacheBlockSize: 50,
            maxBlocksInCache: 4,
            rowHeight: 56,
            headerHeight: 40,
            columnDefs,
            defaultColDef: { resizable: true, sortable: false, filter: false, suppressHeaderMenuButton: true },
            suppressCellFocus: true,
            overlayNoRowsTemplate: '<span id="cs-empty" class="text-sm text-slate-400">ยังไม่มีเช็คชีต</span>',
            overlayLoadingTemplate: '<span class="text-sm text-slate-400">กำลังโหลด...</span>'
        });
        window.checksheetGridApi = gridApi;
        window.checksheetGridOptions = { api: gridApi };

        // มือถือ: การ์ดเช็คชีต (ใช้ช่องค้นหา/ตัวกรองด้านบนร่วมกับเดสก์ท็อป)
        window.PmGridCards(gridApi, gridDiv.parentElement, {
            empty: 'ไม่พบเช็คชีต',
            card: d => `<div class="pm-mc">
                <button type="button" class="pm-mc-title text-left" onclick="previewChecksheet(${Number(d.id)})">${esc(d.name || '-')}</button>
                <div class="pm-mc-sub mt-0.5">${esc(d.docNo || '-')} · Rev. ${esc(d.revNo || '-')} · ${esc(d.category || 'ไม่ระบุประเภท')}</div>
                <div class="pm-mc-sub mt-1">${d.itemCount} จุดตรวจ · ${d.estimatedTime || 0} นาที · ${d.planCount ? d.planCount + ' แผน' : 'ยังไม่ใช้ในแผน'}</div>
                <div class="flex items-center justify-between gap-2 mt-2.5 pt-2.5 border-t border-slate-100">
                    <span class="text-xs text-slate-400">มีผล ${fmtD(d.effectiveDate)}</span>
                    <span class="flex gap-1.5">
                        ${d.fileCount ? `<button type="button" class="pm-mc-btn !w-auto px-2.5 gap-1 text-xs" title="ไฟล์แนบ" onclick="showChecksheetFiles(${Number(d.id)}, '${d.imageCount ? 'images' : 'docs'}')"><i data-lucide="paperclip" class="w-4 h-4"></i>${d.fileCount}</button>` : ''}
                        <button type="button" class="pm-mc-btn" title="แก้ไข" onclick="editChecksheet(${Number(d.id)})"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                        <button type="button" class="pm-mc-btn" title="คัดลอก" onclick="copyChecksheet(${Number(d.id)})"><i data-lucide="copy" class="w-4 h-4"></i></button>
                        <button type="button" class="pm-mc-btn !text-red-500" title="ลบ" onclick="deleteChecksheet(${Number(d.id)})"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                    </span>
                </div>
            </div>`
        });

        let tm = null;
        $('cs-search').addEventListener('input', e => {
            clearTimeout(tm);
            tm = setTimeout(() => {
                const q = e.target.value.trim();
                if (q === listState.q) return;
                listState.q = q;
                reloadFromFirstPage();
            }, 300);
        });
        $('cs-filter-type').addEventListener('change', e => {
            listState.typeId = e.target.value;
            reloadFromFirstPage();
        });
    }

    // ตั้ง datasource ใหม่ => กลับหน้าแรกและล้าง cache
    function reloadFromFirstPage() {
        if (!gridApi) return;
        $('cs-count').textContent = 'กำลังโหลด...';
        gridApi.setGridOption('datasource', datasource);
    }

    // รีเฟรชข้อมูล (หลังบันทึก/ลบ) — คงหน้าปัจจุบัน
    window.loadChecksheets = function () {
        if (gridApi) gridApi.refreshInfiniteCache();
    };

    // ---------- Handsontable (สร้างเมื่อเปิดตัวแก้ไขครั้งแรก) ----------
    function imageRenderer(instance, td, row, col, prop, value) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);
        td.innerHTML = '';
        td.className = 'htCenter htMiddle';
        if (value && (value.startsWith('data:image') || value.startsWith('upload') || /\.(jpeg|jpg|gif|png|webp)$/i.test(value))) {
            const img = document.createElement('img');
            img.src = value;
            img.style.cssText = 'width:30px;height:30px;object-fit:cover;border-radius:4px;cursor:pointer;margin:auto;';
            td.appendChild(img);
        } else {
            td.innerHTML = '<span style="color:#94a3b8;font-size:11px;cursor:pointer;">+ เพิ่มรูป</span>';
        }
        return td;
    }

    // ย่อรูปให้ไม่เกิน 800px แล้วเก็บเป็น Base64 JPEG 70% ในเซลล์
    function pickCellImage(hot, row, col) {
        const fileInput = $('hot-image-input');
        if (!fileInput) return;
        fileInput.value = '';
        fileInput.onchange = (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                const img = new Image();
                img.onload = () => {
                    const MAX = 800;
                    let w = img.width, h = img.height;
                    if (w > h && w > MAX) { h *= MAX / w; w = MAX; }
                    else if (h >= w && h > MAX) { w *= MAX / h; h = MAX; }
                    const canvas = document.createElement('canvas');
                    canvas.width = w; canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                    hot.setDataAtCell(row, col, canvas.toDataURL('image/jpeg', 0.7));
                };
                img.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        };
        fileInput.click();
    }

    function updateItemCount() {
        if (!window.hotChecksheet) return;
        const n = window.hotChecksheet.getData().filter(r => r[0] && String(r[0]).trim()).length;
        $('cs-item-count').textContent = n ? `${n} จุด` : '';
    }

    // ความสูงตารางตามจำนวนแถว (ไม่เหลือพื้นที่ว่างมาก) แต่ไม่เกิน max — เผื่อที่ให้ dropdown ด้วย min
    function fitHot(hot, min, max) {
        if (!hot) return;
        const h = Math.max(min, Math.min(max, 32 + hot.countRows() * 30 + 20));
        if (hot.getSettings().height !== h) hot.updateSettings({ height: h });
    }
    const fitAll = () => { fitHot(window.hotChecksheet, 220, 460); fitHot(window.hotSpareParts, 110, 260); };

    function ensureHot() {
        if (window.hotSpareParts && window.hotChecksheet) return;
        const markDirty = (changes, source) => { if (source !== 'loadData') csDirty = true; };
        const refit = () => setTimeout(fitAll, 0);

        window.hotSpareParts = new Handsontable($('hot-spare-parts'), {
            data: [EMPTY_SPARE()],
            colHeaders: ['อะไหล่ที่ใช้', 'จำนวน'],
            rowHeaders: true,
            contextMenu: true,
            columns: [
                { type: 'text', placeholder: 'ระบุชื่ออะไหล่' },
                { type: 'numeric', placeholder: '0' }
            ],
            colWidths: i => i === 1 ? 90 : Math.max(150, ($('hot-spare-parts').clientWidth || 420) - 150),
            stretchH: 'all',
            height: 110,
            autoWrapRow: true,
            afterChange: markDirty,
            afterLoadData: refit, afterCreateRow: refit, afterRemoveRow: () => { csDirty = true; refit(); },
            licenseKey: 'non-commercial-and-evaluation'
        });

        window.hotChecksheet = new Handsontable($('hot-checksheet'), {
            data: [EMPTY_ITEM()],
            colHeaders: [
                'จุดที่ตรวจสอบ', 'มาตรฐานการตรวจสอบ', 'วิธีตรวจสอบ/เครื่องมือ', 'ข้อปฏิบัติเมื่อผิดปกติ',
                'ประเภทจุดตรวจสอบ', 'รูปภาพประกอบ', 'บังคับถ่ายรูป',
                'ชื่อค่าวัด', 'หน่วยวัด', 'ค่าที่คาดหวัง'
            ],
            colWidths: [190, 180, 170, 170, 190, 100, 190, 110, 90, 110],
            height: 220,
            width: '100%',
            stretchH: 'all',
            rowHeaders: true,
            contextMenu: true,
            manualColumnResize: true,
            licenseKey: 'non-commercial-and-evaluation',
            columns: [
                { type: 'text' }, { type: 'text' }, { type: 'text' }, { type: 'text' },
                { type: 'dropdown', source: ['ผ่าน / ไม่ผ่าน', 'ผ่าน / ไม่ผ่าน / ไม่เกี่ยวข้อง'] },
                { renderer: imageRenderer, readOnly: true },
                { type: 'dropdown', source: ['บังคับ', 'ไม่บังคับ', 'บังคับเฉพาะกรณีที่ไม่ผ่าน'] },
                { type: 'text' }, { type: 'text' }, { type: 'text' }
            ],
            afterOnCellMouseDown: function (event, coords) {
                if (coords.col === 5 && coords.row >= 0) pickCellImage(this, coords.row, coords.col);
            },
            afterChange: (changes, source) => { markDirty(changes, source); updateItemCount(); },
            afterLoadData: refit, afterCreateRow: refit,
            afterRemoveRow: () => { csDirty = true; updateItemCount(); refit(); }
        });
    }

    // ---------- เปิด/ปิดตัวแก้ไข ----------
    function showEditor(mode, sub) {
        if ($('tab-checksheet').classList.contains('hidden')) window.switchTab('checksheet');
        $('cs-list-view').classList.add('hidden');
        $('cs-editor-view').classList.remove('hidden');
        $('cs-editor-title').textContent = { create: 'สร้างเช็คชีต', edit: 'แก้ไขเช็คชีต', copy: 'คัดลอกเช็คชีต' }[mode];
        $('cs-editor-sub').textContent = sub || 'กรอกข้อมูลทั่วไป แล้วเพิ่มจุดตรวจ';
        $('cs-btn-reset').classList.toggle('sm:inline-flex', mode === 'create');
        clearErrors();
        ensureHot();
        loadMachineTypes();
        document.querySelector('#tab-checksheet').closest('main')?.scrollTo({ top: 0 });
        if (window.lucide) lucide.createIcons({ root: $('cs-editor-view') });
    }

    function setEditorLoading(on) {
        $('cs-editor-loading').classList.toggle('hidden', !on);
        $('cs-editor-body').classList.toggle('hidden', on);
    }

    function clearForm() {
        $('checksheet-id').value = '';
        $('checksheet-name').value = '';
        $('checksheet-dept').value = '';
        $('checksheet-effective-date').value = '';
        $('checksheet-doc-no').value = '';
        $('checksheet-rev-no').value = '00';
        $('checksheet-estimated-time').value = '';
        $('row-add-count').value = 1;
        if (window.hotSpareParts) window.hotSpareParts.loadData([EMPTY_SPARE()]);
        if (window.hotChecksheet) window.hotChecksheet.loadData([EMPTY_ITEM()]);
        checksheetImageFiles = [];
        workInstructionFiles = [];
        $('checksheet-images').value = '';
        $('work-instructions').value = '';
        window.renderImagePreviews();
        window.renderWorkInstructionPreviews();
        updateItemCount();
        clearErrors();
        csDirty = false;
    }

    window.openChecksheetEditor = function () {
        showEditor('create');
        setEditorLoading(false);
        clearForm();
        $('checksheet-name').focus();
    };

    window.closeChecksheetEditor = async function (force) {
        if (!force && csDirty) {
            const r = await Swal.fire({
                title: 'ออกโดยไม่บันทึก?', text: 'การแก้ไขที่ยังไม่บันทึกจะหายไป', icon: 'warning',
                showCancelButton: true, confirmButtonText: 'ออก', cancelButtonText: 'แก้ไขต่อ',
                confirmButtonColor: '#ef4444', reverseButtons: true
            });
            if (!r.isConfirmed) return;
        }
        csDirty = false;
        $('cs-editor-view').classList.add('hidden');
        $('cs-list-view').classList.remove('hidden');
    };

    // ดึงข้อมูลเช็คชีตแล้วใส่ในฟอร์ม (แก้ไข หรือ คัดลอก)
    async function fetchAndPopulateForm(id, isCopy = false) {
        const row = rowCache[id];
        showEditor(isCopy ? 'copy' : 'edit', row ? `${row.name}${row.docNo ? ' · ' + row.docNo : ''}` : '');
        setEditorLoading(true);
        clearForm();
        try {
            const [response] = await Promise.all([
                axios.get(`handle_pm_checksheet.php?action=get_by_id&id=${encodeURIComponent(id)}&ag_id=${AG_ID}`),
                loadMachineTypes()
            ]);
            if (!response.data.success) throw new Error(response.data.error || 'ไม่พบข้อมูล');
            const data = response.data.data;

            $('checksheet-name').value = data.main.name + (isCopy ? ' (Copy)' : '');
            $('checksheet-dept').value = data.main.ass_type_id ?? '';
            $('checksheet-effective-date').value = data.main.effective_date || '';
            $('checksheet-doc-no').value = data.main.doc_no || '';
            $('checksheet-rev-no').value = data.main.rev_no || '';
            $('checksheet-estimated-time').value = data.main.estimated_time ?? '';
            $('checksheet-id').value = isCopy ? '' : data.main.id;
            if (!isCopy) $('cs-editor-sub').textContent = `${data.main.name}${data.main.doc_no ? ' · ' + data.main.doc_no : ''}`;
            else $('cs-editor-sub').textContent = `สร้างเช็คชีตใหม่จาก "${data.main.name}" — แก้ไขแล้วกดบันทึก`;

            let spareData = data.spares.map(s => [s.part_name, s.quantity]);
            if (!spareData.length) spareData = [EMPTY_SPARE()];
            let itemData = data.items.map(item => [
                item.check_point, item.standard_text, item.method_text, item.action_abnormal,
                TYPE_REV[item.check_type_id] || 'ผ่าน / ไม่ผ่าน',
                item.illustration_path || '',
                REQ_REV[item.photo_required_id] || 'ไม่บังคับ',
                item.value_name, item.unit, item.expected_value
            ]);
            if (!itemData.length) itemData = [EMPTY_ITEM()];

            checksheetImageFiles = (data.images || []).map(img => ({ isFromServer: true, url: img.file_path, id: img.id, name: img.file_name }));
            workInstructionFiles = (data.instructions || []).map(inst => ({ isFromServer: true, url: inst.file_path, id: inst.id, name: inst.file_name }));

            setEditorLoading(false);
            // แสดงฟอร์มก่อนแล้วค่อยโหลดเข้า Handsontable (ต้องมองเห็น container ถึงคำนวณขนาดถูก)
            window.hotSpareParts.loadData(spareData);
            window.hotChecksheet.loadData(itemData);
            window.hotSpareParts.render();
            window.hotChecksheet.render();
            window.renderImagePreviews();
            window.renderWorkInstructionPreviews();
            updateItemCount();
            csDirty = isCopy; // คัดลอก = ยังไม่ได้บันทึก
        } catch (error) {
            console.error(error);
            $('cs-editor-loading').innerHTML = `<div class="text-red-600 mb-3">โหลดข้อมูลเช็คชีตไม่สำเร็จ${error.message ? ': ' + esc(error.message) : ''}</div>
                <button type="button" onclick="closeChecksheetEditor(true)" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">กลับไปรายการ</button>`;
            return;
        }
    }

    window.editChecksheet = function (id) { $('cs-editor-loading').innerHTML = 'กำลังโหลด...'; fetchAndPopulateForm(id, false); };
    window.copyChecksheet = function (id) { $('cs-editor-loading').innerHTML = 'กำลังโหลด...'; fetchAndPopulateForm(id, true); };

    // ---------- แถวตาราง ----------
    window.addSpareRow = function () {
        if (window.hotSpareParts) window.hotSpareParts.alter('insert_row_below', window.hotSpareParts.countRows());
    };

    window.addHotRows = function () {
        if (!window.hotChecksheet) return;
        const count = Math.min(100, parseInt($('row-add-count').value) || 1);
        for (let i = 0; i < count; i++) {
            window.hotChecksheet.alter('insert_row_below', window.hotChecksheet.countRows());
        }
    };

    // ---------- รูปภาพจุดตรวจสอบ ----------
    window.previewImages = function (input) {
        const newFiles = Array.from(input.files);
        input.value = ''; // เลือกไฟล์เดิมซ้ำได้
        const availableSlots = 5 - checksheetImageFiles.length;
        if (availableSlots <= 0) {
            Swal.fire({ icon: 'warning', title: 'โควต้าเต็ม', text: 'คุณเพิ่มรูปภาพครบ 5 รูปแล้ว' });
            return;
        }
        let filesToAdd = newFiles;
        if (newFiles.length > availableSlots) {
            filesToAdd = newFiles.slice(0, availableSlots);
            Swal.fire({ icon: 'info', title: 'จำกัดจำนวนรูป', text: `เพิ่มได้อีก ${availableSlots} รูปเท่านั้น` });
        }
        if (filesToAdd.length) csDirty = true;
        checksheetImageFiles = checksheetImageFiles.concat(filesToAdd);
        window.renderImagePreviews();
    };

    window.renderImagePreviews = async function () {
        const box = $('image-previews');
        if (!box) return;
        $('cs-img-count').textContent = `${checksheetImageFiles.length}/5`;
        const srcs = await Promise.all(checksheetImageFiles.map(file => file.isFromServer ? file.url : new Promise(resolve => {
            const reader = new FileReader();
            reader.onload = e => resolve(e.target.result);
            reader.readAsDataURL(file);
        })));
        box.innerHTML = '';
        if (!srcs.length) {
            box.innerHTML = '<span class="w-full self-center text-center text-xs text-slate-400">ยังไม่มีรูป</span>';
            return;
        }
        srcs.forEach((src, index) => {
            const div = document.createElement('div');
            div.className = 'relative w-20 h-20 group rounded-lg overflow-hidden border border-slate-200 flex-shrink-0';
            div.innerHTML = `<img class="w-full h-full object-cover cursor-pointer" alt="">
                <button type="button" title="ลบรูปนี้" class="absolute top-1 right-1 bg-white/90 text-red-500 rounded-full p-1 shadow sm:opacity-0 group-hover:opacity-100 transition-opacity">${ico('x', 'w-3 h-3')}</button>`;
            const img = div.querySelector('img');
            img.src = src;
            img.onclick = () => { if (typeof ImageCarousel !== 'undefined') ImageCarousel.open(srcs, index); };
            div.querySelector('button').onclick = () => window.removeChecksheetImage(index);
            box.appendChild(div);
        });
    };

    window.removeChecksheetImage = function (index) {
        checksheetImageFiles.splice(index, 1);
        csDirty = true;
        window.renderImagePreviews();
    };

    // ---------- ไฟล์คำแนะนำการทำงาน (WI) ----------
    window.handleWorkInstructionsSelect = function (input) {
        const maxFiles = 5;
        const maxSizeInBytes = 5 * 1024 * 1024; // 5MB
        let limitExceededWarning = false;
        Array.from(input.files).forEach(file => {
            if (workInstructionFiles.length >= maxFiles) {
                if (!limitExceededWarning) {
                    Swal.fire({ icon: 'warning', title: 'แจ้งเตือน', text: `อัปโหลดได้สูงสุด ${maxFiles} ไฟล์เท่านั้น ระบบจะละเว้นไฟล์ที่เกิน` });
                    limitExceededWarning = true;
                }
                return;
            }
            if (file.size > maxSizeInBytes) {
                Swal.fire({ icon: 'error', title: 'ขนาดไฟล์เกิน', text: `ไฟล์ "${file.name}" มีขนาดใหญ่กว่า 5MB` });
                return;
            }
            // ไม่เพิ่มไฟล์เดิมซ้ำ (ดูจากชื่อและขนาด)
            if (!workInstructionFiles.some(f => f.name === file.name && f.size === file.size)) {
                workInstructionFiles.push(file);
                csDirty = true;
            }
        });
        window.syncFileInput(input);
        window.renderWorkInstructionPreviews();
    };

    // ซิงค์ไฟล์ใหม่จาก Array กลับเข้า <input type="file">
    window.syncFileInput = function (input) {
        if (!input) return;
        const dt = new DataTransfer();
        workInstructionFiles.forEach(file => { if (file instanceof File) dt.items.add(file); });
        input.files = dt.files;
    };

    window.removeWorkInstruction = function (index) {
        workInstructionFiles.splice(index, 1);
        csDirty = true;
        window.syncFileInput($('work-instructions'));
        window.renderWorkInstructionPreviews();
    };

    window.renderWorkInstructionPreviews = function () {
        const box = $('instruction-previews');
        if (!box) return;
        if (!workInstructionFiles.length) {
            box.innerHTML = '<span class="w-full self-center text-center text-xs text-slate-400">ยังไม่มีไฟล์</span>';
            return;
        }
        box.innerHTML = workInstructionFiles.map((file, index) => `
            <div class="text-xs bg-slate-50 text-slate-700 pl-2.5 pr-1.5 py-1 rounded-full border border-slate-200 flex items-center gap-1.5 max-w-full">
                ${ico('file-text', 'w-3.5 h-3.5 text-slate-500 shrink-0')}
                ${file.isFromServer
                    ? `<a href="${esc(file.url)}" target="_blank" rel="noopener" class="max-w-[180px] truncate hover:text-[#006B9F]" title="${esc(file.name)}">${esc(file.name)}</a>`
                    : `<span class="max-w-[180px] truncate" title="${esc(file.name)}">${esc(file.name)}</span>`}
                <button type="button" onclick="removeWorkInstruction(${index})" class="text-slate-400 hover:text-red-500 p-0.5" title="ลบไฟล์นี้">${ico('x', 'w-3 h-3')}</button>
            </div>`).join('');
    };

    window.resetChecksheetForm = function () {
        Swal.fire({
            title: 'ยืนยันการล้างข้อมูล?',
            text: 'ข้อมูลที่กรอกไว้ทั้งหมดจะถูกล้างออก',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'ใช่, ล้างข้อมูล',
            cancelButtonText: 'ยกเลิก'
        }).then(result => {
            if (result.isConfirmed) clearForm();
        });
    };

    // ---------- ตรวจสอบและบันทึก ----------
    function clearErrors() {
        document.querySelectorAll('#cs-editor-view .error-text').forEach(e => e.remove());
        document.querySelectorAll('#cs-editor-view .error-input').forEach(e => e.classList.remove('border-red-500', 'error-input'));
    }

    window.saveChecksheet = async function () {
        clearErrors();
        const requiredFields = [
            { id: 'checksheet-name', name: 'ชื่อเช็คชีต' },
            { id: 'checksheet-dept', name: 'ประเภทเครื่องจักร' },
            { id: 'checksheet-doc-no', name: 'เลขที่เอกสาร' },
            { id: 'checksheet-rev-no', name: 'Revision No.' },
            { id: 'checksheet-effective-date', name: 'วันที่มีผล' },
            { id: 'checksheet-estimated-time', name: 'เวลาที่คาดว่าจะเสร็จ' }
        ];
        let firstErrorInput = null;
        requiredFields.forEach(field => {
            const inputEl = $(field.id);
            if (inputEl && !inputEl.value.trim()) {
                inputEl.classList.add('border-red-500', 'error-input');
                const errorMsg = document.createElement('div');
                errorMsg.className = 'text-red-500 text-xs mt-1 error-text';
                errorMsg.innerText = `กรุณาระบุ ${field.name}`;
                inputEl.parentNode.appendChild(errorMsg);
                if (!firstErrorInput) firstErrorInput = inputEl;
            }
        });
        if (firstErrorInput) {
            firstErrorInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstErrorInput.focus({ preventScroll: true });
            return;
        }

        const formData = new FormData();
        formData.append('action', 'save');
        const checksheetId = $('checksheet-id').value;
        if (checksheetId) formData.append('id', checksheetId);
        formData.append('name', $('checksheet-name').value.trim());
        formData.append('ass_type_id', $('checksheet-dept').value);
        formData.append('effective_date', $('checksheet-effective-date').value);
        formData.append('doc_no', $('checksheet-doc-no').value.trim());
        formData.append('rev_no', $('checksheet-rev-no').value.trim());
        formData.append('estimated_time', $('checksheet-estimated-time').value);
        formData.append('ag_id', AG_ID);
        formData.append('user_id', USER_ID);
        if (window.hotSpareParts) formData.append('spare_parts', JSON.stringify(window.hotSpareParts.getData()));
        if (window.hotChecksheet) formData.append('checksheet_items', JSON.stringify(window.hotChecksheet.getData()));

        // ไฟล์ใหม่แนบไปด้วย / ไฟล์เดิมส่ง id กลับไปเพื่อบอกว่ายังเก็บไว้
        checksheetImageFiles.forEach(file => {
            if (!file.isFromServer) formData.append('checksheet_images[]', file);
            else formData.append('existing_files[]', file.id);
        });
        workInstructionFiles.forEach(file => {
            if (!file.isFromServer) formData.append('work_instructions[]', file);
            else formData.append('existing_files[]', file.id);
        });

        try {
            Swal.fire({ title: 'กำลังบันทึกข้อมูล...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            const response = await axios.post('handle_pm_checksheet.php', formData, { headers: { 'Content-Type': 'multipart/form-data' } });
            if (response.data.success) {
                Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', text: 'ข้อมูลเช็คชีตถูกบันทึกแล้ว', timer: 1500, showConfirmButton: false });
                csDirty = false;
                window.closeChecksheetEditor(true);
                // สร้างใหม่ => กลับหน้าแรก (เรียงตามแก้ไขล่าสุด) / แก้ไข => คงหน้าเดิม
                if (checksheetId) window.loadChecksheets(); else reloadFromFirstPage();
            } else {
                Swal.fire('เกิดข้อผิดพลาด', response.data.error || 'ไม่สามารถบันทึกได้', 'error');
            }
        } catch (error) {
            console.error(error);
            Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        }
    };

    // ---------- ดูรายละเอียด ----------
    // ดูไฟล์แนบของเช็คชีต (รูปจุดตรวจ / เอกสาร WI) — focus = 'images' | 'docs' ให้ส่วนนั้นขึ้นก่อน
    window.showChecksheetFiles = async function (id, focus) {
        Swal.fire({ title: 'กำลังโหลดไฟล์...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        let data;
        try {
            const res = await axios.get(`handle_pm_checksheet.php?action=get_by_id&id=${encodeURIComponent(id)}`);
            if (!res.data.success) throw new Error(res.data.error || 'โหลดไฟล์ไม่สำเร็จ');
            data = res.data.data;
        } catch (e) {
            Swal.fire('ผิดพลาด', e.message || 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            return;
        }
        const { main, images, instructions } = data;
        const docIcon = name => {
            const ext = String(name || '').split('.').pop().toLowerCase();
            if (ext === 'pdf') return ico('file-text', 'w-5 h-5 text-red-500 shrink-0');
            if (/^xls[xm]?$|^csv$/.test(ext)) return ico('file-spreadsheet', 'w-5 h-5 text-emerald-600 shrink-0');
            if (/^docx?$/.test(ext)) return ico('file-text', 'w-5 h-5 text-sky-600 shrink-0');
            return ico('file', 'w-5 h-5 text-slate-500 shrink-0');
        };
        const imgHtml = `<div><div class="text-xs font-medium text-slate-500 mb-2">ภาพจุดตรวจสอบ (${images.length})</div>
            ${images.length ? `<div class="grid grid-cols-2 sm:grid-cols-3 gap-2">${images.map(img => `
                <a href="${esc(img.file_path)}" target="_blank" rel="noopener" class="block group" title="${esc(img.file_name || '')}">
                    <img src="${esc(img.file_path)}" loading="lazy" class="w-full aspect-square object-cover rounded-lg border border-slate-200 group-hover:border-sky-400">
                    <div class="text-[11px] text-slate-500 truncate mt-1">${esc(img.file_name || '')}</div></a>`).join('')}</div>`
                : '<div class="text-sm text-slate-400 py-4">ไม่มีรูป</div>'}</div>`;
        const docHtml = `<div><div class="text-xs font-medium text-slate-500 mb-2">คำแนะนำในการทำงาน / WI (${instructions.length})</div>
            ${instructions.length ? `<div class="space-y-1.5">${instructions.map(f => `
                <a href="${esc(f.file_path)}" target="_blank" rel="noopener" class="flex items-center gap-2.5 px-3 py-2 rounded-lg border border-slate-200 hover:bg-sky-50 hover:border-sky-300 text-slate-700">
                    ${docIcon(f.file_name)}<span class="text-sm truncate flex-1">${esc(f.file_name || f.file_path)}</span>${ico('external-link', 'w-3.5 h-3.5 text-slate-400 shrink-0')}</a>`).join('')}</div>`
                : '<div class="text-sm text-slate-400 py-4">ไม่มีเอกสาร</div>'}</div>`;
        Swal.fire({
            title: `<div class="text-base font-semibold text-slate-800 text-left">ไฟล์แนบ: ${esc(main.name)}</div>`,
            html: `<div class="text-left space-y-5 max-h-[65vh] overflow-y-auto pr-1">${focus === 'docs' ? docHtml + imgHtml : imgHtml + docHtml}</div>`,
            width: 'min(720px, 95vw)', showCloseButton: true, confirmButtonText: 'ปิด', confirmButtonColor: '#64748b'
        });
    };

    window.previewChecksheet = async function (id) {
        try {
            Swal.fire({ title: 'กำลังโหลดข้อมูล...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            const res = await axios.get(`handle_pm_checksheet.php?action=get_by_id&id=${encodeURIComponent(id)}`);
            if (!res.data.success) { Swal.fire('ผิดพลาด', res.data.error, 'error'); return; }
            const { main, items, spares, images, instructions } = res.data.data;
            const cat = rowCache[id]?.category;

            let html = `<div class="text-left text-sm max-h-[75vh] overflow-y-auto pr-1">
                <div class="border-b border-slate-200 pb-4 mb-4">
                    <div class="text-lg font-semibold text-slate-800 leading-tight">${esc(main.name)}</div>
                    <div class="text-xs text-slate-500 mt-1">${esc(main.doc_no || '-')} · Rev. ${esc(main.rev_no || '-')}${cat ? ' · ' + esc(cat) : ''}</div>
                    <div class="flex flex-wrap gap-x-6 gap-y-1 mt-3 text-xs text-slate-600">
                        <span>วันที่มีผล <b class="font-medium text-slate-800">${fmtD(main.effective_date)}</b></span>
                        <span>เวลาที่ใช้ <b class="font-medium text-slate-800">${esc(main.estimated_time || 0)} นาที</b></span>
                        <span>จุดตรวจ <b class="font-medium text-slate-800">${items.length} จุด</b></span>
                    </div>
                </div>
                <div class="mb-5">
                    <div class="font-medium text-slate-800 mb-2">จุดตรวจ</div>
                    <div class="overflow-x-auto border border-slate-200 rounded-lg">
                        <table class="w-full text-xs border-collapse min-w-[760px]">
                            <thead><tr class="bg-slate-50 text-slate-500 border-b border-slate-200">
                                <th class="p-2 text-center w-8">#</th>
                                <th class="p-2 text-left">จุดตรวจ / มาตรฐาน</th>
                                <th class="p-2 text-left">วิธีตรวจ / เมื่อผิดปกติ</th>
                                <th class="p-2 text-left w-32">ประเภท / รูปถ่าย</th>
                                <th class="p-2 text-center w-16">ภาพ</th>
                                <th class="p-2 text-left w-32">ค่าวัด</th>
                            </tr></thead>
                            <tbody class="divide-y divide-slate-100">
                            ${items.length === 0 ? '<tr><td colspan="6" class="p-8 text-center text-slate-400">ยังไม่มีจุดตรวจ</td></tr>' : items.map((it, i) => `
                                <tr class="align-top">
                                    <td class="p-2 text-center text-slate-400">${i + 1}</td>
                                    <td class="p-2"><div class="font-medium text-slate-800">${esc(it.check_point)}</div><div class="text-slate-500">${esc(it.standard_text)}</div></td>
                                    <td class="p-2"><div class="text-slate-700">${esc(it.method_text) || '-'}</div>${it.action_abnormal ? `<div class="mt-1 text-red-600">เมื่อผิดปกติ: ${esc(it.action_abnormal)}</div>` : ''}</td>
                                    <td class="p-2 text-slate-600"><div>${TYPE_REV[it.check_type_id] || '-'}</div><div class="text-slate-400">${REQ_REV[it.photo_required_id] || '-'}</div></td>
                                    <td class="p-2 text-center">${it.illustration_path ? `<a href="${esc(it.illustration_path)}" target="_blank" rel="noopener"><img src="${esc(it.illustration_path)}" class="w-10 h-10 object-cover rounded border border-slate-200 mx-auto"></a>` : '<span class="text-slate-300">-</span>'}</td>
                                    <td class="p-2">${it.value_name ? `<div class="text-slate-700">${esc(it.value_name)}</div><div class="text-slate-500">${esc(it.expected_value)} ${esc(it.unit)}</div>` : '<span class="text-slate-300">-</span>'}</td>
                                </tr>`).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>`;
            if (spares.length) {
                html += `<div class="mb-5"><div class="font-medium text-slate-800 mb-2">อะไหล่ที่ต้องใช้</div>
                    <div class="border border-slate-200 rounded-lg divide-y divide-slate-100">
                    ${spares.map(s => `<div class="flex justify-between gap-3 px-3 py-2 text-xs"><span class="text-slate-700">${esc(s.part_name)}</span><span class="font-medium text-slate-800">${esc(Number(s.quantity))}</span></div>`).join('')}
                    </div></div>`;
            }
            if (images.length || instructions.length) {
                html += `<div class="grid grid-cols-1 md:grid-cols-2 gap-5 border-t border-slate-200 pt-4">
                    ${images.length ? `<div><div class="text-xs text-slate-500 mb-2">ภาพจุดตรวจสอบ</div><div class="flex flex-wrap gap-2">
                        ${images.map(img => `<a href="${esc(img.file_path)}" target="_blank" rel="noopener"><img src="${esc(img.file_path)}" class="w-16 h-16 object-cover rounded-lg border border-slate-200"></a>`).join('')}
                    </div></div>` : ''}
                    ${instructions.length ? `<div><div class="text-xs text-slate-500 mb-2">คำแนะนำในการทำงาน / WI</div><div class="space-y-1.5">
                        ${instructions.map(inst => `<a href="${esc(inst.file_path)}" target="_blank" rel="noopener" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700">${ico('file-text', 'w-4 h-4 text-slate-500 shrink-0')}<span class="text-xs truncate">${esc(inst.file_name)}</span></a>`).join('')}
                    </div></div>` : ''}
                </div>`;
            }
            html += '</div>';

            Swal.fire({
                html, width: 'min(1100px, 95vw)', showCloseButton: true, padding: '1.25rem',
                showDenyButton: true, denyButtonText: 'แก้ไข', denyButtonColor: '#006B9F',
                confirmButtonText: 'ปิด', confirmButtonColor: '#64748b'
            }).then(r => { if (r.isDenied) window.editChecksheet(id); });
        } catch (error) {
            console.error(error);
            Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        }
    };

    // ---------- ลบ ----------
    window.deleteChecksheet = function (id) {
        const row = rowCache[id];
        const used = row && row.planCount
            ? `<div class="mt-3 px-3 py-2 rounded-lg bg-red-50 text-red-600 text-sm">เช็คชีตนี้ถูกใช้ใน ${row.planCount.toLocaleString()} แผน PM — แผนเหล่านั้นจะไม่มีเช็คชีตให้ตรวจ</div>` : '';
        Swal.fire({
            title: 'ยืนยันการลบ?',
            html: `${row ? `<div class="font-medium text-slate-800">${esc(row.name)}</div>` : ''}
                <div class="text-sm text-slate-500 mt-1">ข้อมูล จุดตรวจ และไฟล์ที่เกี่ยวข้องจะถูกลบทั้งหมดและไม่สามารถกู้คืนได้</div>${used}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'ลบข้อมูล',
            cancelButtonText: 'ยกเลิก'
        }).then(async (result) => {
            if (!result.isConfirmed) return;
            try {
                Swal.fire({ title: 'กำลังลบ...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                const response = await axios.post('handle_pm_checksheet.php', formData);
                if (response.data.success) {
                    delete rowCache[id];
                    Swal.fire({ icon: 'success', title: 'ลบสำเร็จ', timer: 1300, showConfirmButton: false });
                    window.loadChecksheets();
                } else {
                    Swal.fire('ผิดพลาด', response.data.error, 'error');
                }
            } catch (error) {
                Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        });
    };

    // ---------- เริ่มต้นเมื่อเปิดแท็บครั้งแรก ----------
    pmLazy('checksheet', function () {
        // ตัวแก้ไข: ทุกช่องที่พิมพ์ => มีการแก้ไขที่ยังไม่บันทึก
        $('cs-editor-view').addEventListener('input', e => { if (e.target.closest('#cs-editor-body section')) csDirty = true; });
        loadMachineTypes();
        initList();
        window.renderImagePreviews();
        window.renderWorkInstructionPreviews();
    });
})();
</script>
