<?php 
@session_start();
include "config_ctrl/checksession.php"; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* บังคับให้ปฏิทิน Handsontable (Pikaday) อยู่บนสุดเสมอ */
    .pika-single {
        z-index: 999999 !important;
        position: fixed !important; /* ใช้ fixed แทน absolute เพื่อให้แม่นยำใน iframe */
        box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
    }

    /* ป้องกันตาราง Handsontable ตัดขอบปฏิทิน */
    .handsontable .htDatepickerHolder {
        display: none;
    }

    /* ===== รายการแผน PM: ตารางโปร่ง แถวละ 2 บรรทัด ===== */
    #pmPlanGrid { --ag-borders: none; --ag-row-border-color: #f1f5f9; --ag-header-foreground-color: #64748b; --ag-cell-horizontal-padding: 16px; }
    #pmPlanGrid .ag-root-wrapper { border: 0; border-radius: 0; }
    #pmPlanGrid .ag-header-cell-text { font-size: 12px; font-weight: 600; }
    #pmPlanGrid .ag-cell { display: flex; align-items: center; line-height: 1.35; }
    .pmp-cell { display: flex; flex-direction: column; justify-content: center; min-width: 0; width: 100%; }
    .pmp-l1 { font-size: 14px; font-weight: 500; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pmp-l2 { font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
    .pmp-off .pmp-l1 { color: #94a3b8; }
    .pmp-pill { display: inline-flex; align-items: center; gap: .25rem; padding: 0 .5rem; border-radius: 9999px; font-size: 11px; font-weight: 600; line-height: 1.25rem; }
    .pmp-act { width: 2rem; height: 2rem; border-radius: .5rem; display: inline-flex; align-items: center; justify-content: center; color: #94a3b8; transition: all .15s; }
    .pmp-act:hover { background: #f1f5f9; }
    .pmp-seg { display: inline-flex; background: #f1f5f9; border-radius: .6rem; padding: 3px; gap: 2px; flex: none; }
    .pmp-seg button { padding: .3rem .7rem; border-radius: .5rem; font-size: 12.5px; font-weight: 500; color: #64748b; white-space: nowrap; }
    .pmp-seg button b { font-weight: 600; margin-left: .3rem; color: #94a3b8; }
    .pmp-seg button.on { background: #fff; color: #006B9F; box-shadow: 0 1px 2px rgba(15,23,42,.1); }
    .pmp-seg button.on b { color: #006B9F; }
    .pmp-seg button[data-st="overdue"] b { color: #ef4444; }
    /* มือถือ: การ์ดยาวตามเนื้อหา เลื่อนทั้งหน้าแทนกล่องเลื่อนเล็กๆ */
    @media (max-width: 767.98px) {
        #tab-plan#tab-plan { height: auto !important; min-height: 0 !important; }
        #tab-plan .pm-mc-list { overflow: visible; }
    }
</style>
<div id="tab-plan" class="tab-content hidden flex flex-col min-h-full">
    <div id="plan-list-view" class="bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col flex-1 min-h-0">
        <!-- หัวแท็บ -->
        <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 px-4 sm:px-5 pt-4 sm:pt-5">
            <div class="min-w-0">
                <h3 class="text-lg font-semibold text-slate-800">แผน PM</h3>
                <p class="text-xs text-slate-500">กำหนดรอบการบำรุงรักษาของแต่ละเครื่องจักร · <span id="contract-display-list"></span></p>
            </div>
            <div class="grid grid-cols-2 sm:flex items-center gap-2 shrink-0">
                <button type="button" onclick="window.generateCalendar()" title="สร้างกำหนดการล่วงหน้าจนถึงวันสิ้นสุดสัญญา" class="border border-slate-200 bg-white text-slate-600 hover:border-sky-300 hover:text-sky-700 px-3.5 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2 transition-colors">
                    <i data-lucide="calendar-sync" class="w-4 h-4"></i> คำนวณแผนใหม่
                </button>
                <button type="button" onclick="window.showWizard()" class="btn-gradient px-4 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2">
                    <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มแผน PM
                </button>
            </div>
        </div>

        <!-- แถบค้นหา + สถานะ + ตัวกรอง -->
        <div class="flex flex-col lg:flex-row lg:items-center gap-2.5 px-4 sm:px-5 pt-4 pb-3">
            <div class="relative w-full lg:w-72 shrink-0">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input id="plan-q" type="search" autocomplete="off" placeholder="ค้นหาเครื่องจักร, รหัส, S/N, เช็คชีต..." class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500/30 focus:border-sky-400">
            </div>
            <div class="flex items-center gap-2 min-w-0">
                <div class="overflow-x-auto hide-scrollbar min-w-0">
                    <div id="plan-seg" class="pmp-seg">
                        <button type="button" data-st="" class="on">ทั้งหมด<b data-n="all">-</b></button>
                        <button type="button" data-st="active">ใช้งาน<b data-n="active">-</b></button>
                        <button type="button" data-st="overdue">มีงานค้าง<b data-n="overdue">-</b></button>
                        <button type="button" data-st="off">ปิด<b data-n="off">-</b></button>
                    </div>
                </div>
                <button type="button" id="plan-filter-btn" onclick="window.togglePlanFilters()" class="shrink-0 border border-slate-200 bg-white text-slate-600 hover:text-sky-700 px-3 py-1.5 rounded-lg text-[13px] font-medium flex items-center gap-1.5">
                    <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i> <span class="hidden sm:inline">ตัวกรอง</span>
                    <span id="plan-filter-badge" class="hidden min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-sky-600 text-white text-[10px] leading-[1.1rem] text-center"></span>
                </button>
            </div>
            <div id="plan-count" class="text-xs text-slate-500 lg:ml-auto whitespace-nowrap">กำลังโหลด...</div>
        </div>
        <div id="plan-filters" class="hidden px-4 sm:px-5 pb-3">
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                <select id="plan-f-type" class="w-full sm:w-64 border border-slate-200 bg-white rounded-lg px-2.5 py-1.5 text-sm outline-none focus:ring-2 focus:ring-sky-500/30">
                    <option value="">ทุกประเภทเครื่องจักร</option>
                </select>
                <select id="plan-f-chk" class="w-full sm:w-72 border border-slate-200 bg-white rounded-lg px-2.5 py-1.5 text-sm outline-none focus:ring-2 focus:ring-sky-500/30">
                    <option value="">ทุกเช็คชีต</option>
                </select>
                <button type="button" onclick="window.clearPlanFilters()" class="text-xs text-slate-500 hover:text-red-500 px-2 py-1.5 self-start sm:self-auto">ล้างตัวกรอง</button>
            </div>
        </div>
        <div id="plan-error" class="hidden mx-4 sm:mx-5 mb-3 px-3 py-2 rounded-lg bg-red-50 border border-red-200 text-sm text-red-600 flex items-center justify-between gap-2"></div>

        <div class="flex-1 min-h-0 border-t border-slate-200 flex flex-col relative px-4 pb-4 pt-3 sm:p-0">
            <div class="flex-1 min-h-0">
                <div id="pmPlanGrid" class="ag-theme-alpine" style="height: 100%; width: 100%;"></div>
            </div>
        </div>
    </div>

    <div id="plan-wizard-view" class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 hidden flex flex-col flex-1">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-800">สร้างแผน PM ใหม่</h3>
                <p class="text-sm text-slate-500">กรุณากรอกข้อมูลตามขั้นตอนให้ครบถ้วน</p>
            </div>
            <div>
                <p class="text-[12px] mt-1">
                    <span class="text-slate-500">ระยะเวลาสัญญา: </span>
                    <span id="contract-display-wizard" class="font-medium text-sky-600"></span>
                </p>
            </div>
        </div>

        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center w-full max-w-2xl">
                <div id="stepper-1" class="flex items-center text-sky-600 relative">
                    <div class="rounded-full transition duration-500 ease-in-out h-8 w-8 py-3 border-2 border-sky-600 bg-sky-600 text-white flex items-center justify-center font-bold text-sm">1</div>
                    <div class="absolute top-0 left-0 sm:left-auto sm:-ml-10 text-left sm:text-center mt-10 w-24 sm:w-28 text-[11px] sm:text-xs font-medium uppercase text-sky-600">เลือกข้อมูลพื้นฐาน</div>
                </div>
                <div id="line-1" class="flex-auto border-t-2 transition duration-500 ease-in-out border-slate-200"></div>
                
                <div id="stepper-2" class="flex items-center text-slate-400 relative">
                    <div class="rounded-full transition duration-500 ease-in-out h-8 w-8 py-3 border-2 border-slate-200 bg-white flex items-center justify-center font-bold text-sm">2</div>
                    <div class="absolute top-0 -ml-8 sm:-ml-10 text-center mt-10 w-24 sm:w-28 text-[11px] sm:text-xs font-medium uppercase">ตั้งค่าเช็คชีต</div>
                </div>
                <div id="line-2" class="flex-auto border-t-2 transition duration-500 ease-in-out border-slate-200"></div>
                
                <div id="stepper-3" class="flex items-center text-slate-400 relative">
                    <div class="rounded-full transition duration-500 ease-in-out h-8 w-8 py-3 border-2 border-slate-200 bg-white flex items-center justify-center font-bold text-sm">3</div>
                    <div class="absolute top-0 right-0 sm:right-auto sm:-ml-10 text-right sm:text-center mt-10 w-24 sm:w-28 text-[11px] sm:text-xs font-medium uppercase">กำหนดวันเริ่มทำ</div>
                </div>
            </div>
        </div>

        <div class="mt-12 min-h-[300px]">
            
            <div id="step-content-1" class="block space-y-6 max-w-7xl mx-auto">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">ประเภทเครื่องจักร</label>
                        <select id="step1-mac-type" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                            <option value="">-- กำลังโหลดข้อมูล... --</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">รายการเช็คชีต (เลือกได้มากกว่า 1)</label>
                    <div id="step1-checksheets-container" class="grid grid-cols-1 md:grid-cols-3 gap-3 p-4 border border-slate-200 rounded-lg bg-slate-50/50 max-h-60 overflow-y-auto">
                        <div class="text-sm text-slate-500 px-2">กำลังโหลดรายการเช็คชีต...</div>
                    </div>
                </div>
            </div>

            <div id="step-content-2" class="hidden space-y-6">
                <div class="overflow-x-auto border border-slate-200 rounded-lg">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-slate-600 bg-slate-50 uppercase border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3">ชื่อเช็คชีต</th>
                                <th class="px-4 py-3">ความถี่</th>
                                <th class="px-4 py-3">การแจ้งเตือน</th>
                                <th class="px-4 py-3">ระบุวัน (ถ้ามี)</th>
                            </tr>
                        </thead>
                        <tbody id="step2-tbody"> </tbody>
                    </table>
                </div>
            </div>

            <div id="step-content-3" class="hidden space-y-4">
                <div class="flex justify-between items-end gap-3 mb-2">
                    <div>
                        <h4 class="text-sm font-medium text-slate-800">กำหนดวันเริ่มทำ PM สำหรับเครื่องจักรในกลุ่ม</h4>
                        <p class="text-xs text-slate-500">กรอกวันที่ในรูปแบบ YYYY-MM-DD หรือดับเบิ้ลคลิกเพื่อเปิดปฏิทิน</p>
                    </div>
                    <button type="button" onclick="window.toggleStep3Fullscreen()" title="ขยายเต็มจอ" aria-label="ขยายเต็มจอ" class="shrink-0 text-sky-600 hover:text-sky-800 bg-sky-50 p-2 sm:px-3 sm:py-1.5 rounded-lg text-xs font-medium flex items-center gap-1.5 transition-colors border border-sky-200">
                        <i data-lucide="maximize" class="w-4 h-4"></i> <span class="hidden sm:inline">ขยายเต็มจอ</span>
                    </button>
                </div>
                
                <div id="step3-wrapper" class="w-full bg-white relative rounded border border-slate-300 flex flex-col transition-all duration-200">
                    
                    <div id="step3-fullscreen-header" class="hidden justify-between items-center p-4 border-b border-slate-200 bg-slate-50">
                        <div>
                        <h4 class="text-sm font-medium text-slate-800">กำหนดวันเริ่มทำ PM สำหรับเครื่องจักรในกลุ่ม</h4>
                        <p class="text-xs text-slate-500">กรอกวันที่ในรูปแบบ YYYY-MM-DD หรือดับเบิ้ลคลิกเพื่อเปิดปฏิทิน</p>
                    </div>
                        <button type="button" onclick="window.toggleStep3Fullscreen()" title="ย่อหน้าจอกลับ" aria-label="ย่อหน้าจอกลับ" class="shrink-0 text-slate-600 hover:text-red-500 bg-white p-2 sm:px-4 sm:py-2 rounded-lg text-sm font-medium flex items-center gap-2 border border-slate-300 shadow-sm transition-colors">
                            <i data-lucide="minimize" class="w-4 h-4"></i> <span class="hidden sm:inline">ย่อหน้าจอกลับ</span>
                        </button>
                    </div>
                    
                    <div id="step3-hot-container" class="w-full z-0 flex-grow" style="height: 350px;"></div>
                </div>
            </div>

        </div>

        <!-- เนื้อหาสั้นกว่าจอ: ดันแถบปุ่มลงล่างสุด ทุกขั้นจึงอยู่ตำแหน่งเดียวกัน -->
        <div class="flex-1"></div>
        <!-- ปุ่มย้อนกลับ / ถัดไป / บันทึก ยึดขอบล่างของจอขณะเลื่อน -->
        <div class="sticky bottom-0 z-20 -mx-5 -mb-5 mt-8 px-5 py-3 flex justify-between items-center gap-2 bg-white/95 backdrop-blur border-t border-slate-200 rounded-b-xl shadow-[0_-6px_16px_-10px_rgba(15,23,42,.25)]">
            <button id="btn-prev" onclick="window.prevStep()" class="hidden px-5 py-2.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-600 hover:bg-slate-200 transition-colors">
                ย้อนกลับ
            </button>
            <div class="flex-grow"></div>
            <button id="btn-next" onclick="window.nextStep()" class="btn-gradient px-6 py-2.5 rounded-lg text-sm font-medium text-white transition-colors">
                ถัดไป
            </button>
            <button id="btn-submit" onclick="window.submitWizard()" class="hidden bg-emerald-500 hover:bg-emerald-600 px-6 py-2.5 rounded-lg text-sm font-medium text-white transition-colors">
                บันทึกแผน PM
            </button>
        </div>
    </div>
</div>

<script>
    // โหลดเมื่อเปิดแท็บ "จัดการแผน PM" ครั้งแรก (ดู pmLazy ใน pm.php)
    pmLazy('plan', function() {
        const AG_ID = '<?php echo isset($sess_user_agency_es) ? $sess_user_agency_es : ""; ?>';
        const USER_ID = '<?php echo isset($sess_user_id) ? $sess_user_id : ""; ?>';
        const CONTRACT_START = '<?php echo isset($sess_ag_start_date) ? $sess_ag_start_date : ""; ?>';
        const CONTRACT_END = '<?php echo isset($sess_ag_end_date) ? $sess_ag_end_date : ""; ?>';

        const contractText = `${CONTRACT_START} ถึง ${CONTRACT_END}`;
        const displayList = document.getElementById('contract-display-list');
        if(displayList) displayList.innerText = CONTRACT_START && CONTRACT_END
            ? `สัญญา ${window.formatDate(CONTRACT_START, false)} – ${window.formatDate(CONTRACT_END, false)}` : 'ยังไม่ได้กำหนดระยะเวลาสัญญา';
        const displayWizard = document.getElementById('contract-display-wizard');
        if(displayWizard) displayWizard.innerText = contractText;
        
        // ถ้าตัวแปร Global ยังไม่มี ให้สร้างขึ้นมา
        if (!window.pmPlans) {
            window.pmPlans = [];
        }

        let currentStep = 1;
        let selectedChecksheetsData = []; // เก็บข้อมูลเช็คชีตที่เลือกและตั้งค่า
        let planGridApi = null;
        let alertOptionsData = [];
        let freqOptionsData = [];

        const daysOptions = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.', 'อา.'];
        const datesOptions = Array.from({length: 31}, (_, i) => i + 1);

        window.hotStep3Instance = null;

        // โหลดตารางใหม่ (คงหน้าเดิม) — เรียกหลังบันทึก/ลบ/เปลี่ยนสถานะ
        function fetchPlans() {
            if (planGridApi) planGridApi.refreshInfiniteCache();
        }

        // ตัวเลือกความถี่/การแจ้งเตือน: โหลดครั้งเดียวเมื่อเปิดฟอร์ม (สร้าง/แก้ไข)
        let planOptionsPromise = null;
        function loadPlanOptions() {
            if (planOptionsPromise) return planOptionsPromise;
            planOptionsPromise = axios.get('handle_pm_plan.php?action=get_options').then(response => {
                if (!response.data.success) throw new Error(response.data.error || 'ไม่สามารถดึงข้อมูลตัวเลือกได้');
                alertOptionsData = response.data.data.alerts;
                freqOptionsData = response.data.data.freqs;
            }).catch(error => {
                planOptionsPromise = null; // ให้ลองใหม่ได้ครั้งหน้า
                console.error('API Error:', error);
                Swal.fire('ข้อผิดพลาด', 'โหลดตัวเลือกความถี่/การแจ้งเตือนไม่สำเร็จ', 'error');
                throw error;
            });
            return planOptionsPromise;
        }

        let machineTypesLoaded = false;
        async function loadMachineTypes() {
            if (machineTypesLoaded) return;
			const deptSelect = document.getElementById('step1-mac-type');
		
			if (!deptSelect) {
				console.error('ไม่พบ select id="step1-mac-type"');
				return;
			}
		
			try {
				const agId = AG_ID || window.AG_ID || '';

				deptSelect.innerHTML = '<option value="">กำลังโหลดข้อมูล...</option>';
		
				if (!agId) {
					deptSelect.innerHTML = '<option value="">ไม่พบข้อมูลหน่วยงาน AG_ID</option>';
					return;
				}
		
				const response = await axios.get(
					`get_all_ass_type.php?ag_id=${encodeURIComponent(agId)}`
				);
		
				const data = response.data;
		
				deptSelect.innerHTML = '<option value="">-- เลือกประเภทเครื่องจักร --</option>';
		
				if (Array.isArray(data) && data.length > 0) {
					data.forEach(item => {
						const option = document.createElement('option');
						option.value = item.id || item.GroupId;
						option.textContent = item.name || item.TGroupName;
						deptSelect.appendChild(option);
					});
					machineTypesLoaded = true;
				} else {
					deptSelect.innerHTML = '<option value="">ไม่พบข้อมูลประเภทเครื่องจักร</option>';
				}
		
			} catch (error) {
				console.error('ไม่สามารถโหลดข้อมูลประเภทเครื่องจักรได้:', error);
				deptSelect.innerHTML = '<option value="">เกิดข้อผิดพลาดในการโหลดข้อมูล</option>';
			}
		}

        // ฟังก์ชันโหลด Checksheets ลง Step 1
        window.loadChecksheetsToStep1 = async function() {
            const container = document.getElementById('step1-checksheets-container');
            container.innerHTML = '<div class="text-sm text-slate-500 px-2">กำลังโหลดรายการเช็คชีต...</div>';

            try {
                
                const response = await axios.get(`handle_pm_checksheet.php?action=get_all&ag_id=${AG_ID}`);
                const templates = response.data.data || response.data || [];
                
                container.innerHTML = '';
                
                if (templates.length === 0) {
                    container.innerHTML = '<div class="text-sm text-red-500 px-2">ไม่พบข้อมูลเช็คชีตในระบบ</div>';
                    return;
                }

                templates.forEach(t => {
                    const id = t.id || t.chk_id; 
                    const name = t.name || t.chk_name;
                    
                    container.innerHTML += `
                    <label class="flex items-center p-3 border border-slate-200 rounded-lg bg-white cursor-pointer hover:border-sky-400 transition-colors">
                        <input type="checkbox" value="${name}" data-id="${id}" class="chk-checksheet w-4 h-4 text-sky-600 bg-slate-100 border-slate-300 rounded focus:ring-sky-500">
                        <span class="ml-3 text-sm font-medium text-slate-700">${name}</span>
                    </label>
                    `;
                });
            } catch (error) {
                console.error('Error loading checksheets:', error);
                container.innerHTML = '<div class="text-sm text-red-500 px-2">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
            }
        };

        // ฟังก์ชันสลับหน้าจอระหว่าง List กับ Wizard
        window.showWizard = function() {
            document.getElementById('plan-list-view').classList.add('hidden');
            document.getElementById('plan-wizard-view').classList.remove('hidden');
            document.getElementById('plan-wizard-view').classList.add('block');
            
            // Reset Wizard
            currentStep = 1;
            const btnSubmit = document.getElementById('btn-submit');
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = 'บันทึกแผน PM';
            loadMachineTypes().then(() => { document.getElementById('step1-mac-type').value = ''; });
            loadPlanOptions().catch(() => {});
            window.loadChecksheetsToStep1();
            window.updateWizardUI();
        };

        window.hideWizard = function() {
            document.getElementById('plan-wizard-view').classList.add('hidden');
            document.getElementById('plan-wizard-view').classList.remove('block');
            document.getElementById('plan-list-view').classList.remove('hidden');
        };

        // ฟังก์ชันเปลี่ยน UI ตาม Step
        window.updateWizardUI = function() {
            // ซ่อนโชว์ Content
            document.getElementById('step-content-1').classList.toggle('hidden', currentStep !== 1);
            document.getElementById('step-content-2').classList.toggle('hidden', currentStep !== 2);
            document.getElementById('step-content-3').classList.toggle('hidden', currentStep !== 3);

            // จัดการ Stepper Header
            for (let i = 1; i <= 3; i++) {
                const stepEl = document.getElementById(`stepper-${i}`);
                const lineEl = document.getElementById(`line-${i-1}`); // มี line-1, line-2
                
                if (i < currentStep) {
                    // Passed
                    stepEl.classList.replace('text-slate-400', 'text-sky-600');
                    stepEl.classList.replace('text-sky-600', 'text-sky-600');
                    stepEl.children[0].className = "rounded-full transition duration-500 ease-in-out h-8 w-8 py-3 border-2 border-sky-600 bg-sky-600 text-white flex items-center justify-center font-bold text-sm";
                    stepEl.children[0].innerHTML = `<i data-lucide="check" class="w-4 h-4"></i>`;
                    if(lineEl) lineEl.classList.add('border-sky-600');
                } else if (i === currentStep) {
                    // Active
                    stepEl.classList.replace('text-slate-400', 'text-sky-600');
                    stepEl.children[0].className = "rounded-full transition duration-500 ease-in-out h-8 w-8 py-3 border-2 border-sky-600 bg-sky-600 text-white flex items-center justify-center font-bold text-sm";
                    stepEl.children[0].innerHTML = i;
                    if(lineEl) {
                        lineEl.classList.remove('border-sky-600');
                        lineEl.classList.add('border-slate-200');
                    }
                } else {
                    // Future
                    stepEl.classList.replace('text-sky-600', 'text-slate-400');
                    stepEl.children[0].className = "rounded-full transition duration-500 ease-in-out h-8 w-8 py-3 border-2 border-slate-200 bg-white flex items-center justify-center font-bold text-sm text-slate-500";
                    stepEl.children[0].innerHTML = i;
                    if(lineEl) {
                        lineEl.classList.remove('border-sky-600');
                        lineEl.classList.add('border-slate-200');
                    }
                }
            }
            lucide.createIcons();

            // จัดการปุ่ม
            document.getElementById('btn-prev').classList.toggle('hidden', currentStep === 0);
            if (currentStep === 3) {
                document.getElementById('btn-next').classList.add('hidden');
                document.getElementById('btn-submit').classList.remove('hidden');
            } else {
                document.getElementById('btn-next').classList.remove('hidden');
                document.getElementById('btn-submit').classList.add('hidden');
            }
        };

        // ควบคุมการเปลี่ยนหน้าและ Validate ข้อมูล
        window.nextStep = async function() {
        if (currentStep === 1) {
            const macType = document.getElementById('step1-mac-type').value;
            const checkedElements = Array.from(document.querySelectorAll('.chk-checksheet:checked'));
            
            if (!macType) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกประเภทเครื่องจักรและเครื่องจักรให้ครบถ้วน', 'warning')
                    .then(() => document.getElementById('step1-mac-type').focus());
                return;
            }
            if (checkedElements.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกเช็คชีตอย่างน้อย 1 รายการ', 'warning');
                return;
            }

            selectedChecksheetsData = checkedElements.map(cb => ({
                id: cb.getAttribute('data-id'),
                name: cb.value,
                frequency: 'ทุกวัน',
                freqValue: '', 
                alertDesc: 'ไม่ระบุ',
                alertValue: '',
                days: []
            }));

            try { await loadPlanOptions(); } catch (e) { return; }
            renderStep2();
            currentStep++;
            window.updateWizardUI();
            
        } else if (currentStep === 2) {
            // Save Step 2 state to selectedChecksheetsData
            for (let i = 0; i < selectedChecksheetsData.length; i++) {
                const freqSelect = document.getElementById(`freq-${i}`);
                const freqOpt = freqSelect.options[freqSelect.selectedIndex];
                const multiSelect = freqOpt.getAttribute('dropdownmultiselect');
                
                selectedChecksheetsData[i].frequency = freqOpt.text.trim(); 
                selectedChecksheetsData[i].freqValue = freqOpt.value; 
                
                const alertSelect = document.getElementById(`alert-${i}`);
                selectedChecksheetsData[i].alertValue = alertSelect.value;
                selectedChecksheetsData[i].alertDesc = alertSelect.options[alertSelect.selectedIndex].text.trim();
                
                const checkedItems = Array.from(document.querySelectorAll(`.chk-day-${i}:checked`)).map(cb => cb.value);
                selectedChecksheetsData[i].days = checkedItems;

                // 🌟 ฟังก์ชันจัดการแจ้งเตือนและโฟกัส
                const highlightError = (message, indexToFocus) => {
                    Swal.fire({
                        icon: 'warning',
                        title: 'ข้อมูลไม่ครบถ้วน',
                        text: message,
                        confirmButtonColor: '#0ea5e9'
                    }).then(() => {
                        // หากล่อง Days/Dates ของแถวที่มีปัญหา
                        const dayContainer = document.getElementById(`days-container-${indexToFocus}`);
                        if (dayContainer) {
                            dayContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            // ใส่ Effect กระพริบสีแดงให้จุดสังเกต
                            dayContainer.classList.add('ring-2', 'ring-red-500', 'animate-pulse');
                            setTimeout(() => {
                                dayContainer.classList.remove('ring-2', 'ring-red-500', 'animate-pulse');
                            }, 3000);
                        }
                    });
                };

                // 🌟 เงื่อนไขดักการเลือกวันที่
                // 1. กรณี dropdownmultiselect เป็น '2' (เช่น 2 ครั้งต่อสัปดาห์) ต้องเลือกให้ครบ 2 วันเป๊ะ
                if (multiSelect === '2' && checkedItems.length !== 2) { 
                    highlightError(`กรุณาเลือกวันให้ครบ 2 วัน สำหรับเช็คชีต "${selectedChecksheetsData[i].name}"`, i);
                    return; // หยุดการเปลี่ยน Step
                }
                
                // 2. กรณี dropdownmultiselect เป็น '6' (เช่น หลายวันในสัปดาห์) หรือ '31' (หลายวันในเดือน) ต้องเลือกอย่างน้อย 1 วัน
                if ((multiSelect === '6' || multiSelect === '31') && checkedItems.length === 0) { 
                    highlightError(`กรุณาระบุวันอย่างน้อย 1 วัน สำหรับเช็คชีต "${selectedChecksheetsData[i].name}"`, i);
                    return; // หยุดการเปลี่ยน Step
                }
            }
            
            // ถ้าผ่าน Loop Validation ทั้งหมด ค่อยขยับไป Step 3
            renderStep3();
            currentStep++;
            window.updateWizardUI();
        }
    };

        window.prevStep = function() {
            if (currentStep > 1) {
                currentStep--;
                window.updateWizardUI();
            } else {
                window.hideWizard();
            }
        };

        // จัดการแสดงผล Step 2
        function renderStep2() {
            const tbody = document.getElementById('step2-tbody');
            tbody.innerHTML = '';
            
            // เตรียม HTML Options จาก freqOptionsData
            const freqHtml = freqOptionsData.map(opt => 
                `<option value="${opt.value}" dropdownmultiselect="${opt.dropdownmultiselect}" alertbeforeforrepeatconfig="${opt.alertbeforeforrepeatconfig}">
                    ${opt.desc}
                </option>`
            ).join('');

            selectedChecksheetsData.forEach((cs, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-4 py-4 font-medium text-slate-800">${cs.name}</td>

                    <td class="px-4 py-4">
                        <select id="freq-${index}" data-original-val="${cs.frequency}" class="w-full border border-slate-300 rounded p-2 focus:ring-sky-500 outline-none" 
                                onchange="window.handleFreqChange(${index})">
                            ${freqHtml}
                        </select>
                    </td>
                    <td class="px-4 py-4">
                        <select id="alert-${index}" class="w-full border border-slate-300 rounded p-2 text-sm focus:ring-sky-500 outline-none bg-white">
                        </select>
                    </td>
                    <td class="px-4 py-4" id="days-container-${index}">
                        <div id="days-wrapper-${index}" class="flex flex-wrap opacity-50 pointer-events-none transition-all duration-300">
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);

                // สั่งรันฟังก์ชันครั้งแรกเพื่อให้ช่อง 'การแจ้งเตือน' สอดคล้องกับค่าเริ่มต้น
                setTimeout(() => window.handleFreqChange(index), 10);
            });
        }

        // ฟังก์ชันควบคุมตัวเลือก (การแจ้งเตือน และ กำหนดวัน)
        window.handleFreqChange = function(index, savedDays = []) {
            const freqSelect = document.getElementById(`freq-${index}`);
            if(!freqSelect || freqSelect.selectedIndex === -1) return; 
            
            const selectedOpt = freqSelect.options[freqSelect.selectedIndex];
            const multiSelect = selectedOpt.getAttribute('dropdownmultiselect'); 
            const maxAlertValue = parseInt(selectedOpt.getAttribute('alertbeforeforrepeatconfig')) || 0;

            // --- ส่วนจัดการ Alert และ Days Wrapper (โค้ดเดิมของคุณ) ---
            const alertSelect = document.getElementById(`alert-${index}`);
            const currentAlertVal = alertSelect.value;
            alertSelect.innerHTML = '';
            alertOptionsData.forEach(alert => {
                const aVal = parseInt(alert.value);
                if (aVal === 0 || aVal === -1 || aVal <= maxAlertValue) {
                    const isSelected = (alert.value == currentAlertVal) ? 'selected' : '';
                    alertSelect.innerHTML += `<option value="${alert.value}" ${isSelected}>${alert.desc}</option>`;
                }
            });

            const wrapper = document.getElementById(`days-wrapper-${index}`);
            wrapper.innerHTML = ''; 
            const savedDaysStr = savedDays.map(d => d.toString());

            if (multiSelect === '2' || multiSelect === '6' || multiSelect === '5') {
                const isWeekdayOnly = (multiSelect === '5');
                const weekdays = ['จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.'];
                if (isWeekdayOnly) { wrapper.classList.add('pointer-events-none', 'opacity-80'); } 
                else { wrapper.classList.remove('opacity-50', 'opacity-80', 'pointer-events-none'); }

                wrapper.innerHTML = daysOptions.map(day => {
                    const isChecked = (savedDaysStr.includes(day) || (isWeekdayOnly && weekdays.includes(day))) ? 'checked' : '';
                    return `
                        <label class="inline-flex items-center mr-3 mb-2 cursor-pointer">
                            <input type="checkbox" value="${day}" class="chk-day-${index} w-4 h-4 text-sky-600 rounded border-slate-300" ${isChecked}>
                            <span class="ml-1.5 text-xs text-slate-700">${day}</span>
                        </label>
                    `;
                }).join('');
            } else if (multiSelect === '31') {
                wrapper.classList.remove('opacity-50', 'pointer-events-none', 'opacity-80');
                let datesHtml = `<div class="grid grid-cols-7 gap-1 w-full max-w-[240px] p-2 bg-slate-50 border border-slate-200 rounded">`;
                datesHtml += datesOptions.map(date => {
                    const isChecked = savedDaysStr.includes(date.toString()) ? 'checked' : '';
                    return `
                        <label class="inline-flex items-center justify-center cursor-pointer">
                            <input type="checkbox" value="${date}" class="chk-day-${index} sr-only peer" ${isChecked}>
                            <div class="w-6 h-6 flex items-center justify-center rounded text-[10px] font-medium text-slate-600 bg-white border border-slate-200 peer-checked:bg-sky-500 peer-checked:text-white peer-checked:border-sky-500 transition-colors">${date}</div>
                        </label>
                    `;
                }).join('');
                datesHtml += `</div></div>`;
                wrapper.innerHTML = datesHtml;
            } else {
                wrapper.classList.add('opacity-50', 'pointer-events-none');
                wrapper.innerHTML = `<span class="text-xs text-slate-400 py-1">- ไม่ต้องระบุวัน -</span>`;
            }

            // --- ส่วนที่เพิ่มใหม่สำหรับโหมด Edit: จัดการ Disable/Enable วันที่เริ่มใหม่ ---
            if (index === 'edit') {
                const dateInput = document.getElementById('edit-next-date');
                const warningMsg = document.getElementById('edit-warning-msg');
                const originalFreq = freqSelect.getAttribute('data-original-val');

                if (freqSelect.value !== originalFreq) {
                    // มีการเปลี่ยนความถี่ -> เปิดให้แก้ไขวันที่ และแสดงคำเตือน
                    dateInput.disabled = false;
                    dateInput.classList.remove('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
                    dateInput.classList.add('bg-white', 'text-slate-900');
                    warningMsg.classList.remove('hidden');
                } else {
                    // ความถี่เหมือนเดิม -> ปิดการแก้ไข และซ่อนคำเตือน
                    dateInput.disabled = true;
                    dateInput.classList.add('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
                    dateInput.classList.remove('bg-white', 'text-slate-900');
                    warningMsg.classList.add('hidden');
                }
            }
        };

        // ฟังก์ชันเปิด/ปิด โหมด Fullscreen ด้วย API ของ Browser (ทะลุ iframe)
        window.toggleStep3Fullscreen = function() {
            // สำคัญ: เปลี่ยนเป้าหมายจาก wrapper ไปเป็น document.documentElement (แท็ก <html>)
            // เพื่อให้ Datepicker ที่อยู่ใน <body> ไม่ถูกเบราว์เซอร์ซ่อน
            const elem = document.documentElement; 
            
            if (!document.fullscreenElement && !document.webkitFullscreenElement) {
                // เข้าสู่โหมด Fullscreen
                if (elem.requestFullscreen) {
                    elem.requestFullscreen();
                } else if (elem.webkitRequestFullscreen) { /* Safari */
                    elem.webkitRequestFullscreen();
                } else if (elem.msRequestFullscreen) { /* IE11 */
                    elem.msRequestFullscreen();
                }
            } else {
                // ออกจากโหมด Fullscreen
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) { /* Safari */
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) { /* IE11 */
                    document.msExitFullscreen();
                }
            }
        };

        // ต้องสร้าง Event Listener ดักจับเมื่อหน้าจอเปลี่ยนสถานะ Fullscreen (เช่น ตอนกดปุ่ม ESC)
        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('webkitfullscreenchange', handleFullscreenChange); // สำหรับ Safari

        function handleFullscreenChange() {
            const wrapper = document.getElementById('step3-wrapper');
            const container = document.getElementById('step3-hot-container');
            const header = document.getElementById('step3-fullscreen-header');

            const isFullscreen = document.fullscreenElement || document.webkitFullscreenElement;

            if (isFullscreen) {
                // เมื่อหน้าจอ <html> เป็น Fullscreen เราจะจับตารางขยายให้เต็มจอด้วย CSS
                wrapper.classList.add('fixed', 'inset-0', 'z-[1000]', 'bg-white');
                wrapper.classList.remove('relative', 'rounded', 'border', 'border-slate-300');
                
                header.classList.remove('hidden');
                header.classList.add('flex');
                
                container.style.height = 'calc(100vh - 72px)'; 
            } else {
                // เมื่อย่อหน้าจอกลับ (หรือกดปุ่ม ESC) เอา CSS ออก
                wrapper.classList.remove('fixed', 'inset-0', 'z-[1000]', 'bg-white');
                wrapper.classList.add('relative', 'rounded', 'border', 'border-slate-300');
                
                header.classList.add('hidden');
                header.classList.remove('flex');
                
                container.style.height = '350px'; 
            }

            // ให้ Handsontable รีเฟรชขนาดตาราง
            if (window.hotStep3Instance) {
                setTimeout(() => {
                    window.hotStep3Instance.render();
                }, 100);
            }
        }

        // จัดการแสดงผล Step 3 (แบบ Handsontable)
        async function renderStep3() {
            // 1. ดึงค่าอย่างปลอดภัย (Safe Check) ป้องกัน Error null
            const macTypeEl = document.getElementById('step1-mac-type');
            const macNameEl = document.getElementById('step1-mac-name');
            
            // macType ในที่นี้คือ AG_ID ที่ได้จากการเลือก
            const macType = macTypeEl ? macTypeEl.value : '';
            const singleMacName = macNameEl ? macNameEl.value : '';
            
            const container = document.getElementById('step3-hot-container');
            if (!container) {
                console.error("ไม่พบ element id: step3-hot-container");
                return;
            }
            
            // แสดงข้อความ Loading ระหว่างรอข้อมูลจาก API
            container.innerHTML = '<div class="flex justify-center items-center h-full text-slate-500"><i class="fas fa-spinner fa-spin mr-2"></i> กำลังโหลดข้อมูลเครื่องจักร...</div>';

            let apiData = [];

            // 2. เรียก API ดึงข้อมูลเครื่องจักรตามประเภท (AG_ID)
            if (macType) {
                try {
                    const response = await fetch(`handle_pm_plan.php?action=get_machines_by_group&macType=${macType}&AG_ID=${AG_ID}`);
                    const result = await response.json();
                    
                    if (result.success && result.data.length > 0) {
                        // Map ข้อมูลจาก API ให้ตรงกับโครงสร้างที่ตารางต้องการ
                        apiData = result.data.map(item => ({
                            id: item.id,
                            qr: item.qrCode || '-',
                            eqId: item.groupName || '-', // รหัสหรือประเภทเครื่องจักร
                            location: item.location || '-',
                            name: item.name || '-'
                        }));
                    }
                } catch (error) {
                    console.error('Fetch machines error:', error);
                    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์เพื่อดึงข้อมูลเครื่องจักรได้', 'error');
                }
            }

            // ล้างข้อความ Loading 
            container.innerHTML = ''; 

            // ถ้าไม่พบข้อมูล หรือดึงข้อมูลล้มเหลว ให้ใช้ข้อมูลที่กรอกใน Step 1 มาใช้ขัดตาทัพ
            if (!apiData || apiData.length === 0) {
                apiData = [{ qr: '-', eqId: singleMacName || '-', location: '-', name: '-' }];
            }

            // 3. เตรียม Headers สำหรับตาราง
            const colHeaders = ['QR Code', 'ประเภทเครื่องจักร/อุปกรณ์', 'สถานที่ตั้งเครื่องจักร', 'ชื่อเครื่องจักร'];
            
            // เตรียม Columns Setting
            const columns = [
                { data: 'qr', readOnly: true, className: 'htMiddle' },
                { data: 'eqId', readOnly: true, className: 'htMiddle font-medium' },
                { data: 'location', readOnly: true, className: 'htMiddle' },
                { data: 'name', readOnly: true, className: 'htMiddle' }
            ];

            // วนลูปสร้างคอลัมน์วันที่ตามเช็คชีตที่เลือกมา
            selectedChecksheetsData.forEach((cs, i) => {
                colHeaders.push(`
                    <span style="font-size: 14px; font-weight:normal;">${cs.name}</span><br>
                    <span style="font-size: 10px; font-weight:normal; color:#64748b;">เริ่มทำตั้งแต่วันที่</span>`);
                columns.push({
                    data: `startDate_${i}`, // กำหนด Key ให้ตรงกับ Data
                    type: 'date',
                    dateFormat: 'YYYY-MM-DD',
                    correctFormat: true,
                    className: 'htCenter htMiddle',
                    datePickerConfig: {
                        container: document.body, 
                        minDate: new Date(CONTRACT_START),
                        maxDate: new Date(CONTRACT_END),
                        blurFieldOnSelect: false,
                        
                        // เพิ่มส่วนการจัดการตำแหน่งตรงนี้ครับ
                        onOpen: function() {
                            const pickerEl = this.el;
                            const inputEl = this._o.field;
                            const rect = inputEl.getBoundingClientRect();
                            const pickerWidth = 260; // ความกว้างโดยประมาณของปฏิทิน
                            const windowWidth = window.innerWidth;
                            
                            // คำนวณตำแหน่ง Left
                            let leftPos = rect.left;
                            
                            // ถ้าตำแหน่ง Left + ความกว้างปฏิทิน เกินขอบจอขวา
                            if (leftPos + pickerWidth > windowWidth) {
                                // ให้ขยับมาทางซ้าย โดยห่างจากขอบขวา 80px
                                leftPos = windowWidth - pickerWidth - 80;
                            }
                            
                            // กรณีถ้าขยับมาแล้วติดขอบซ้าย (จอเล็กมาก)
                            if (leftPos < 0) leftPos = 10;

                            // กำหนดค่า Style โดยตรง
                            pickerEl.style.left = leftPos + 'px';
                            
                            // จัดตำแหน่งความสูง (กรณีอยู่ล่างจอให้เด้งขึ้นบน)
                            const pickerHeight = 300; // ความสูงโดยประมาณ
                            if (rect.bottom + pickerHeight > window.innerHeight) {
                                pickerEl.style.top = (rect.top + window.scrollY - pickerHeight) + 'px';
                            } else {
                                pickerEl.style.top = (rect.bottom + window.scrollY) + 'px';
                            }
                        }
                    }
                });
            });

            // 4. เตรียม Data ให้ตรงกับคอลัมน์
            const hotData = apiData.map(item => {
                let rowData = { ...item };
                // ใส่ค่าว่างเริ่มแรกให้กับคอลัมน์วันที่
                selectedChecksheetsData.forEach((cs, i) => {
                    rowData[`startDate_${i}`] = ''; 
                });
                return rowData;
            });

            // ลบตัวเก่าทิ้งเพื่อป้องกัน Error
            if (window.hotStep3Instance) {
                window.hotStep3Instance.destroy();
            }

            // 5. วาด Handsontable
            window.hotStep3Instance = new Handsontable(container, {
                data: hotData,
                colHeaders: colHeaders,
                columns: columns,
                rowHeaders: true, 
                width: '100%',
                height: '100%',
                stretchH: 'all', 
                contextMenu: ['copy'], 
                licenseKey: 'non-commercial-and-evaluation',
                fixedColumnsLeft: 4, 
                
                afterGetColHeader: function(col, TH) {
                    TH.style.backgroundColor = '#f8fafc';
                    TH.style.color = '#334155';
                    if (col >= 4) { 
                        TH.style.backgroundColor = '#f0f9ff'; 
                        TH.style.color = '#0284c7'; 
                    }
                }
            });
        }

        window.submitWizard = async function() {
            const btnSubmit = document.getElementById('btn-submit');
            
            // 1. ป้องกันการกดซ้ำโดยการ Disable ปุ่มทันที
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> กำลังบันทึก...';

            const tableData = window.hotStep3Instance.getSourceData();
            const macType = document.getElementById('step1-mac-type').value;

            const machinesPayload = tableData.map(row => ({ ...row }));

            const payload = {
                mac_type: macType,
                ag_id: AG_ID,
                user_id: USER_ID,
                contract_start: CONTRACT_START,
                contract_end: CONTRACT_END,
                checksheets: selectedChecksheetsData.map(cs => ({
                    id: cs.id,
                    freqValue: cs.freqValue,
                    alertValue: cs.alertValue,
                    days: cs.days
                })),
                machines: machinesPayload
            };

            // แสดง Loading Overlay ของ Swal
            Swal.fire({
                title: 'กำลังบันทึกข้อมูล',
                text: 'กรุณารอสักครู่...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                const response = await axios.post('handle_pm_plan.php?action=save', payload);
                if (response.data.success) {
                    Swal.fire({
                        title: 'สำเร็จ',
                        text: 'บันทึกแผนเรียบร้อย',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.hideWizard();
                        window.reloadPlans(); // แผนใหม่อยู่บนสุด (เรียงตามวันที่สร้าง)
                        if (window.refreshCalendarEvents) window.refreshCalendarEvents();
                    });
                } else {
                    throw new Error(response.data.error || 'เกิดข้อผิดพลาด');
                }
            } catch (error) { 
                console.error(error);
                Swal.fire('ข้อผิดพลาด', error.message, 'error');
                // ถ้าพลาด ให้เปิดปุ่มกลับมาให้กดใหม่ได้
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = 'บันทึกแผน PM';
            }
        };


        /* ===================== รายการแผน PM (แบ่งหน้าฝั่งเซิร์ฟเวอร์ ครั้งละ 50 แผน) ===================== */
        const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
        const nf = n => Number(n || 0).toLocaleString('th-TH');
        const fmtD = d => window.formatDate(d, false);
        const planById = new Map();                                   // แถวที่โหลดมาแล้ว (ใช้ตอนแก้ไข/ลบ)
        const planState = { q: '', status: '', type: '', chk: '' };   // ค้นหา + ตัวกรองปัจจุบัน
        let planTotal = 0;

        function daysFromToday(d) {
            const [y, m, dd] = String(d).split('-').map(Number);
            const t = new Date(); t.setHours(0, 0, 0, 0);
            return Math.round((new Date(y, m - 1, dd) - t) / 86400000);
        }
        const machineSub = d => [d.ass_code, d.macTypeName, d.asset_sn ? 'S/N ' + d.asset_sn : ''].filter(Boolean).map(esc).join(' · ');
        const chkSub = d => [d.freqDesc || '-', d.alertDesc && d.alertDesc !== 'ไม่ระบุ' ? 'แจ้งเตือน: ' + d.alertDesc : ''].filter(Boolean).map(esc).join(' · ');

        // รอบถัดไป = กำหนดการที่ยังไม่ทำ (ตั้งแต่วันนี้) + จำนวนรอบที่ค้าง
        function nextHtml(d) {
            if (d.status != 1) return `<div class="pmp-l1 !text-slate-400">—</div><div class="pmp-l2">ปิดใช้งาน</div>`;
            const l1 = d.next_ev ? fmtD(d.next_ev) : '<span class="text-slate-400">ไม่มีรอบคงเหลือ</span>';
            let l2 = '';
            if (d.overdue_n > 0) l2 = `<span class="pmp-pill bg-red-50 text-red-600">ค้าง ${nf(d.overdue_n)} รอบ</span>`;
            else if (d.next_ev) { const n = daysFromToday(d.next_ev); l2 = n <= 0 ? 'วันนี้' : n === 1 ? 'พรุ่งนี้' : `อีก ${nf(n)} วัน`; }
            return `<div class="pmp-l1">${l1}</div>${l2 ? `<div class="pmp-l2">${l2}</div>` : ''}`;
        }
        const switchHtml = d => {
            const on = d.status == 1;
            return `<label class="pm-switch ${on ? 'text-emerald-600' : 'text-slate-400'}" title="เปิด/ปิดใช้งานแผน"><input type="checkbox" ${on ? 'checked' : ''} onchange="window.updatePlanStatus('${esc(d.id)}', this.checked)"><i></i>${on ? 'ใช้งาน' : 'ปิด'}</label>`;
        };
        const actionsHtml = (d, cls) => {
            const id = esc(d.id);
            return `<button type="button" class="${cls} hover:!text-sky-700" title="รายงานรายเดือน/รายวัน" onclick="window.open('pm_monthly_report_1.php?plan_id=${id}&show_pending=1', '_blank')"><i class="fa-regular fa-file-lines"></i></button>`
                + `<button type="button" class="${cls} hover:!text-sky-700" title="แก้ไข / รายละเอียด" onclick="window.editPlan('${id}')"><i class="fa-regular fa-pen-to-square"></i></button>`
                + `<button type="button" class="${cls} hover:!text-red-500" title="ลบแผน" onclick="window.deletePlan('${id}')"><i class="fa-regular fa-trash-can"></i></button>`;
        };

        window.updatePlanStatus = async function(id, isChecked) {
            const status = isChecked ? 1 : 0;
            try {
                const response = await axios.post('handle_pm_plan.php?action=update_status', { id: id, status: status });
                if (!response.data.success) Swal.fire('ข้อผิดพลาด', response.data.error || 'ไม่สามารถอัปเดตสถานะได้', 'error');
            } catch (error) {
                console.error('Update status error:', error);
                Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
            }
            fetchPlans(); // วาดแถว + จำนวนตามสถานะใหม่ให้ตรงกับ DB
        };

        const cellR = fn => p => p.data ? fn(p.data) : '<div class="pmp-l2">กำลังโหลด...</div>';
        const columnDefs = [
            { colId: 'machine', headerName: 'เครื่องจักร', flex: 3, minWidth: 260, sortable: true,
              cellRenderer: cellR(d => `<div class="pmp-cell ${d.status == 1 ? '' : 'pmp-off'}"><div class="pmp-l1" title="${esc(d.equipmentName)}">${esc(d.equipmentName || '-')}</div><div class="pmp-l2">${machineSub(d) || '-'}</div></div>`) },
            { colId: 'checksheet', headerName: 'เช็คชีต', flex: 3, minWidth: 240, sortable: true,
              cellRenderer: cellR(d => `<div class="pmp-cell ${d.status == 1 ? '' : 'pmp-off'}"><div class="pmp-l1" title="${esc(d.checksheetName)}">${esc(d.checksheetName || '-')}</div><div class="pmp-l2">${chkSub(d)}</div></div>`) },
            { colId: 'next', headerName: 'รอบถัดไป', width: 165, sortable: true, cellRenderer: cellR(d => `<div class="pmp-cell">${nextHtml(d)}</div>`) },
            { colId: 'status', headerName: 'สถานะ', width: 120, sortable: true, cellRenderer: cellR(switchHtml) },
            { colId: 'actions', headerName: '', width: 128, sortable: false, cellRenderer: cellR(d => `<div class="flex items-center gap-0.5">${actionsHtml(d, 'pmp-act')}</div>`) },
        ];

        function setPlanCounts(c) {
            document.querySelectorAll('#plan-seg [data-n]').forEach(b => { b.textContent = nf(c[b.dataset.n]); });
        }
        function updatePlanCount() {
            const el = document.getElementById('plan-count');
            if (!planGridApi) return;
            if (!planTotal) { el.textContent = 'ไม่พบแผน PM'; return; }
            const size = planGridApi.paginationGetPageSize(), from = planGridApi.paginationGetCurrentPage() * size;
            el.textContent = `แสดง ${nf(from + 1)}–${nf(Math.min(from + size, planTotal))} จาก ${nf(planTotal)} แผน`;
        }
        function showPlanError(msg) {
            const el = document.getElementById('plan-error');
            el.classList.toggle('hidden', !msg);
            el.innerHTML = msg ? `<span><i class="fa-solid fa-circle-exclamation mr-1.5"></i>${esc(msg)}</span><button type="button" class="text-xs font-medium underline shrink-0" onclick="window.reloadPlans()">ลองใหม่</button>` : '';
        }

        const planDatasource = {
            getRows(params) {
                const s = (params.sortModel || [])[0];
                const qp = { action: 'list', offset: params.startRow, limit: params.endRow - params.startRow,
                             q: planState.q, status: planState.status, type: planState.type, chk: planState.chk };
                if (s) { qp.sort = s.colId; qp.dir = s.sort; }
                else if (planState.status === 'overdue') { qp.sort = 'overdue'; qp.dir = 'desc'; } // งานค้างมากสุดก่อน
                axios.get('handle_pm_plan.php', { params: qp }).then(res => {
                    const r = res.data;
                    if (!r || !r.success) throw new Error((r && r.error) || 'โหลดรายการแผน PM ไม่สำเร็จ');
                    r.rows.forEach(x => planById.set(String(x.id), x));
                    window.pmPlans = r.rows;
                    planTotal = r.total;
                    setPlanCounts(r.counts);
                    showPlanError('');
                    params.successCallback(r.rows, r.total);
                    document.getElementById('plan-empty').classList.toggle('hidden', r.total > 0);
                    updatePlanCount();
                }).catch(err => {
                    console.error('Fetch plans error:', err);
                    params.failCallback();
                    document.getElementById('plan-count').textContent = '';
                    showPlanError(err.message && !/^Request failed|Network/.test(err.message) ? err.message : 'โหลดรายการแผน PM ไม่สำเร็จ กรุณาตรวจสอบการเชื่อมต่อ');
                });
            }
        };

        const gridOptions = {
            columnDefs: columnDefs,
            rowModelType: 'infinite',
            datasource: planDatasource,
            cacheBlockSize: 50,
            maxBlocksInCache: 4,
            pagination: true,
            paginationPageSize: 50,
            paginationPageSizeSelector: false,
            defaultColDef: { resizable: false, suppressHeaderMenuButton: true, sortingOrder: ['asc', 'desc', null] },
            getRowId: p => String(p.data.id),
            suppressCellFocus: true,
            rowHeight: 58,
            headerHeight: 42,
            localeText: { page: 'หน้า', of: 'จาก', to: '–', more: 'มากกว่า', loadingOoo: 'กำลังโหลด...' },
            suppressNoRowsOverlay: true,   // ใช้ #plan-empty แทน (ไม่ให้ข้อความซ้อนกัน)
            onPaginationChanged: updatePlanCount,
        };

        // เริ่มใหม่ที่หน้าแรก (เมื่อเปลี่ยนคำค้น/ตัวกรอง/หลังสร้างแผน)
        window.reloadPlans = function() {
            if (planGridApi) planGridApi.setGridOption('datasource', planDatasource);
        };
        window.renderPlans = fetchPlans; // ชื่อเดิม

        const gridDiv = document.querySelector('#pmPlanGrid');
        if (gridDiv) {
            gridDiv.insertAdjacentHTML('afterend', `<div id="plan-empty" class="hidden max-md:!hidden absolute inset-x-0 top-[42px] bottom-12 flex flex-col items-center justify-center text-center text-slate-400 text-sm pointer-events-none">
                <i data-lucide="clipboard-list" class="w-9 h-9 mb-2"></i>ไม่พบแผน PM ตามเงื่อนไข<span class="text-xs mt-1">ลองเปลี่ยนคำค้นหรือตัวกรอง หรือกด "เพิ่มแผน PM"</span></div>`);
            gridDiv.parentElement.classList.add('relative');
            planGridApi = agGrid.createGrid(gridDiv, gridOptions);

            // มือถือ: การ์ดแผน PM (ใช้ช่องค้นหาด้านบนร่วมกัน)
            window.PmGridCards(planGridApi, gridDiv.parentElement, {
                empty: 'ไม่พบแผน PM ตามเงื่อนไข',
                card: d => {
                    const on = d.status == 1;
                    return `<div class="pm-mc ${on ? '' : 'is-off'}">
                        <div class="pm-mc-title">${esc(d.equipmentName || '-')}</div>
                        <div class="pm-mc-sub mt-0.5">${machineSub(d)}</div>
                        <div class="mt-1.5 text-xs text-slate-600 flex items-start gap-1.5"><i class="fa-regular fa-clipboard mt-0.5 text-sky-600"></i><span>${esc(d.checksheetName || '-')}</span></div>
                        <div class="grid grid-cols-2 gap-1.5 mt-2">
                            <div class="pm-mc-kv"><b>รอบถัดไป</b><span>${on ? (d.next_ev ? fmtD(d.next_ev) : 'ไม่มีรอบคงเหลือ') : '—'}${on && d.overdue_n > 0 ? ` <em class="not-italic !text-red-600">· ค้าง ${nf(d.overdue_n)}</em>` : ''}</span></div>
                            <div class="pm-mc-kv"><b>ความถี่</b><span>${esc(d.freqDesc || '-')}</span></div>
                        </div>
                        <div class="flex items-center justify-between gap-2 mt-2.5 pt-2.5 border-t border-slate-100">
                            ${switchHtml(d)}
                            <div class="flex gap-1.5">${actionsHtml(d, 'pm-mc-btn')}</div>
                        </div>
                    </div>`;
                }
            });
        }

        // ---- แถบค้นหา / สถานะ / ตัวกรอง ----
        let qTimer = null;
        document.getElementById('plan-q').addEventListener('input', e => {
            clearTimeout(qTimer);
            qTimer = setTimeout(() => {
                const q = e.target.value.trim();
                if (q === planState.q) return;
                planState.q = q;
                window.reloadPlans();
            }, 300);
        });
        document.getElementById('plan-seg').addEventListener('click', e => {
            const b = e.target.closest('button[data-st]');
            if (!b || b.dataset.st === planState.status) return;
            document.querySelectorAll('#plan-seg button').forEach(x => x.classList.toggle('on', x === b));
            planState.status = b.dataset.st;
            window.reloadPlans();
        });

        let planFiltersLoaded = false;
        window.togglePlanFilters = function() {
            const box = document.getElementById('plan-filters');
            box.classList.toggle('hidden');
            if (planFiltersLoaded || box.classList.contains('hidden')) return;
            planFiltersLoaded = true;
            const fill = (sel, items, all) => {
                sel.innerHTML = `<option value="">${all}</option>` + items.map(o => `<option value="${esc(o.id)}">${esc(o.name)} (${nf(o.n)})</option>`).join('');
            };
            const selT = document.getElementById('plan-f-type'), selC = document.getElementById('plan-f-chk');
            selT.innerHTML = selC.innerHTML = '<option value="">กำลังโหลด...</option>';
            axios.get('handle_pm_plan.php?action=filters').then(res => {
                if (!res.data.success) throw new Error();
                fill(selT, res.data.data.types, 'ทุกประเภทเครื่องจักร');
                fill(selC, res.data.data.checksheets, 'ทุกเช็คชีต');
            }).catch(() => {
                planFiltersLoaded = false;
                selT.innerHTML = selC.innerHTML = '<option value="">โหลดตัวเลือกไม่สำเร็จ</option>';
            });
        };
        function onPlanFilterChange() {
            planState.type = document.getElementById('plan-f-type').value;
            planState.chk = document.getElementById('plan-f-chk').value;
            const n = (planState.type ? 1 : 0) + (planState.chk ? 1 : 0);
            const badge = document.getElementById('plan-filter-badge');
            badge.textContent = n;
            badge.classList.toggle('hidden', !n);
            window.reloadPlans();
        }
        document.getElementById('plan-f-type').addEventListener('change', onPlanFilterChange);
        document.getElementById('plan-f-chk').addEventListener('change', onPlanFilterChange);
        window.clearPlanFilters = function() {
            document.getElementById('plan-f-type').value = '';
            document.getElementById('plan-f-chk').value = '';
            onPlanFilterChange();
        };

        // --- ฟังก์ชันสำหรับลบแผน PM ---
        window.deletePlan = function(planId) {
            const p = planById.get(String(planId)) || {};
            Swal.fire({
                title: 'ลบแผน PM นี้?',
                html: `<div class="text-sm text-slate-600"><div class="font-medium text-slate-800">${esc(p.equipmentName || '')}</div><div>${esc(p.checksheetName || '')}</div>
                       <p class="mt-3 text-xs text-red-500">กำหนดการทั้งหมดของแผนนี้จะถูกลบด้วย และไม่สามารถกู้คืนได้</p></div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'ลบแผน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (!result.isConfirmed) return;
                axios.get(`handle_pm_plan.php?action=delete&id=${encodeURIComponent(planId)}`)
                    .then(res => {
                        if (!res.data.success) throw new Error(res.data.error || 'ลบแผนไม่สำเร็จ');
                        Swal.fire({ title: 'ลบสำเร็จ', icon: 'success', timer: 1200, showConfirmButton: false });
                        planById.delete(String(planId));
                        fetchPlans();
                        if (window.refreshCalendarEvents) window.refreshCalendarEvents();
                    })
                    .catch(err => Swal.fire('ข้อผิดพลาด', err.message || 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error'));
            });
        };

        // แก้ไขแผน + แสดงรายละเอียดที่ไม่ได้อยู่ในตาราง (วันเริ่ม/ผู้สร้าง/วันที่สร้าง)
        window.editPlan = async function(planId) {
            const plan = planById.get(String(planId));
            if (!plan) return;
            try { await loadPlanOptions(); } catch (e) { return; }

            // เตรียม Options สำหรับ Select
            const freqHtml = freqOptionsData.map(opt =>
                `<option value="${opt.value}" ${opt.value == plan.frequency ? 'selected' : ''}
                    dropdownmultiselect="${opt.dropdownmultiselect}"
                    alertbeforeforrepeatconfig="${opt.alertbeforeforrepeatconfig}">${esc(opt.desc)}</option>`
            ).join('');

            const alertHtml = alertOptionsData.map(opt =>
                `<option value="${opt.value}" ${opt.value == plan.alert_value ? 'selected' : ''}>${esc(opt.desc)}</option>`
            ).join('');

            const meta = (k, v) => `<div class="min-w-0"><div class="text-[11px] text-slate-400">${k}</div><div class="text-slate-700 truncate">${v}</div></div>`;
            const inputCls = 'w-full border border-slate-200 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500/30 outline-none';

            Swal.fire({
                title: 'แก้ไขแผน PM',
                width: '620px',
                html: `
                    <div class="text-left space-y-4 mt-2 text-sm">
                        <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                            <div class="font-medium text-slate-800">${esc(plan.equipmentName || '-')}</div>
                            <div class="text-xs text-slate-500 mt-0.5">${machineSub(plan)}</div>
                            <div class="text-xs text-slate-600 mt-1.5"><i class="fa-regular fa-clipboard text-sky-600 mr-1"></i>${esc(plan.checksheetName || '-')}</div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3 pt-3 border-t border-slate-200 text-xs">
                                ${meta('เริ่มทำตั้งแต่', fmtD(plan.start_date))}
                                ${meta('รอบถัดไป', plan.next_ev ? fmtD(plan.next_ev) : '-')}
                                ${meta('สร้างเมื่อ', fmtD(plan.created_at))}
                                ${meta('ผู้สร้าง', esc(plan.created_by || '-'))}
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium text-slate-700 mb-1">ความถี่</label>
                                <select id="freq-edit" data-original-val="${esc(plan.frequency)}" class="${inputCls} bg-white"
                                        onchange="window.handleFreqChange('edit')">
                                    ${freqHtml}
                                </select>
                            </div>
                            <div>
                                <label class="block font-medium text-slate-700 mb-1">การแจ้งเตือนล่วงหน้า</label>
                                <select id="alert-edit" class="${inputCls} bg-white">
                                    ${alertHtml}
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 mb-1">ระบุวัน (ถ้ามี)</label>
                            <div id="days-wrapper-edit" class="flex flex-wrap gap-2 mt-1 min-h-[40px] p-2 border border-dashed border-slate-200 rounded-lg"></div>
                        </div>

                        <div>
                            <label class="block font-medium text-slate-700 mb-1">เริ่มต้นตั้งแต่วันที่ <span class="text-red-500">*</span></label>
                            <input type="date" id="edit-next-date" value="${esc(plan.next_date)}"
                                class="${inputCls} bg-slate-100 text-slate-400 cursor-not-allowed" disabled>
                            <p id="edit-warning-msg" class="text-[11px] text-red-500 mt-1 hidden">
                                * ระบบจะลบแผนงานที่ยังไม่เสร็จและวางแผนใหม่ กรุณาเลือกวันที่เริ่มใหม่
                            </p>
                        </div>
                    </div>
                `,
                didOpen: () => {
                    window.handleFreqChange('edit', plan.days_config || []);
                },
                showCancelButton: true,
                confirmButtonText: 'บันทึกการแก้ไข',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#006B9F',
                preConfirm: () => {
                    const nextDate = document.getElementById('edit-next-date').value;
                    const freq = document.getElementById('freq-edit').value;
                    const alert = document.getElementById('alert-edit').value;
                    const checkedDays = Array.from(document.querySelectorAll(`.chk-day-edit:checked`)).map(cb => cb.value);

                    if (!nextDate) {
                        Swal.showValidationMessage('กรุณาระบุวันที่ในรอบถัดไป');
                        return false;
                    }

                    // ตรวจสอบเงื่อนไขสัญญา
                    if (nextDate < CONTRACT_START || nextDate > CONTRACT_END) {
                        Swal.showValidationMessage(`วันที่ต้องอยู่ระหว่าง ${CONTRACT_START} ถึง ${CONTRACT_END}`);
                        return false;
                    }

                    return {
                        id: planId,
                        ag_id: AG_ID,
                        nextDate: nextDate,
                        contract_end: CONTRACT_END,
                        freq: freq,
                        alert: alert,
                        days: checkedDays
                    };
                }
            }).then((result) => {
                if (!result.isConfirmed) return;
                axios.post('handle_pm_plan.php?action=update', result.value)
                    .then(res => {
                        if (!res.data.success) throw new Error(res.data.error || 'บันทึกไม่สำเร็จ');
                        Swal.fire({ title: 'สำเร็จ!', icon: 'success', timer: 1500, showConfirmButton: false });
                        fetchPlans();
                        if (window.refreshCalendarEvents) window.refreshCalendarEvents();
                    })
                    .catch(err => Swal.fire('ข้อผิดพลาด', err.message || 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error'));
            });
        };

        // ฟังก์ชันคำนวณปฏิทินใหม่ (กรณีต่อสัญญา)
        window.generateCalendar = function() {
            Swal.fire({
                title: 'ยืนยันการคำนวณแผนใหม่?',
                text: `ระบบจะสร้างกำหนดการทำ PM ล่วงหน้าไปจนถึงสิ้นสุดสัญญา (${CONTRACT_END})`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#006B9F',
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: 'ใช่, คำนวณใหม่',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'กำลังประมวลผล...',
                        text: 'กรุณารอสักครู่ ระบบกำลังสร้างแผน PM',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    axios.post(`handle_pm_plan.php?action=regenerate_calendar`, {
                        ag_id: AG_ID,
                        contract_end: CONTRACT_END
                    }).then(res => {
                        if (res.data.success) {
                            Swal.fire({ title: 'สำเร็จ!', text: res.data.message, icon: 'success', timer: 2000, showConfirmButton: false });
                            fetchPlans(); // โหลดตารางใหม่
                            if (window.refreshCalendarEvents) window.refreshCalendarEvents();
                        } else {
                            Swal.fire('เกิดข้อผิดพลาด', res.data.error, 'error');
                        }
                    }).catch(err => {
                        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                    });
                }
            });
        };

        if (window.lucide) lucide.createIcons({ root: document.getElementById('tab-plan') });
    });
</script>
