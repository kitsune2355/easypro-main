<?php 
@session_start();
include "config_ctrl/checksession.php"; 
?>

<div id="tab-postpone" class="tab-content hidden h-full w-full">
    <div class="grid grid-cols-1 md:grid-cols-[384px_1fr] gap-4 h-full relative">

        <div id="postpone-sidebar-backdrop" class="fixed inset-0 bg-slate-900/50 z-40 hidden md:hidden transition-opacity" onclick="togglePostponeSidebar()"></div>

        <aside id="postpone-filter-sidebar" class="fixed inset-y-0 left-0 z-50 w-[85vw] max-w-[384px] h-full flex flex-col bg-white p-5 shadow-2xl transition-transform duration-300 transform -translate-x-full md:relative md:translate-x-0 md:w-auto md:shadow-sm md:rounded-xl md:border md:border-slate-200">
            
            <div class="flex justify-between items-center mb-4 shrink-0">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <i class="fas fa-filter text-sky-600"></i> ตัวกรองขั้นสูง
                </h3>
                <button type="button" class="hidden md:inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors" onclick="togglePmPanel('postpone', true)" title="ซ่อนแผงตัวกรอง">
                    <i class="fas fa-angles-left"></i>
                </button>
                <button class="md:hidden text-slate-400 hover:text-slate-600 transition-colors p-1" onclick="togglePostponeSidebar()">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="space-y-4 overflow-y-auto pr-2 flex-1 custom-scrollbar">
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">เดือน / ปี (วันที่แผนเดิม)</label>
                    <div class="grid grid-cols-12 gap-2">
                        <div class="col-span-5">
                            <select id="postpone-filter-month" class="w-full bg-white border border-slate-200 rounded-lg text-xs py-1.5 px-2 outline-none focus:ring-1 focus:ring-sky-500 shadow-sm cursor-pointer">
                                <option value="">ทุกเดือน</option>
                                <?php
                                $months = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
                                foreach ($months as $i => $name) {
                                    $m = $i + 1;
                                    $selected = ($m == date('n')) ? 'selected' : '';
                                    echo "<option value='$m' $selected>$name</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-span-4">
                            <select id="postpone-filter-year" class="w-full bg-white border border-slate-200 rounded-lg text-xs py-1.5 px-2 outline-none focus:ring-1 focus:ring-sky-500 shadow-sm cursor-pointer">
                                <option value="">ทุกปี</option>
                                <?php
                                $currentYear = date('Y');
                                // +1 เผื่อดูแผนของปีถัดไป และย้อนหลัง 5 ปี
                                for ($y = $currentYear + 1; $y >= $currentYear - 5; $y--) {
                                    $selected = ($y == $currentYear) ? 'selected' : '';
                                    echo "<option value='$y' $selected>".($y + 543)."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-span-3">
                            <button type="button" onclick="resetToCurrentDate()" class="w-full h-full flex items-center justify-center gap-1 bg-sky-50 text-sky-600 hover:bg-sky-100 rounded-lg text-[10px] sm:text-xs font-bold transition-all shadow-sm border border-sky-100" title="กลับไปเดือนปัจจุบัน">
                                <i class="fas fa-calendar-day"></i> ปัจจุบัน
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">เครื่องจักร / อุปกรณ์</label>
                    <div class="relative">
                        <input type="text" id="postpone-filter-machine" placeholder="ระบุชื่อ หรือเลือกจากรายการ..." autocomplete="off"
                            class="w-full border-slate-200 rounded-lg pl-3 pr-10 py-1.5 focus:ring-sky-500 focus:border-sky-500 shadow-sm text-xs cursor-text">
                        
                        <button type="button" id="clear-postpone-filter-machine" onclick="clearFilterInput('postpone-filter-machine')" class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-300 hover:text-red-500 hidden transition-colors z-10">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                        
                        <i class="fas fa-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                        <ul id="dropdown-postpone-filter-machine" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-48 overflow-y-auto hidden">
                        </ul>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">เช็คชีต</label>
                    <div class="relative">
                        <input type="text" id="postpone-filter-checksheet" placeholder="ระบุชื่อ หรือเลือกจากรายการ..." autocomplete="off"
                            class="w-full border-slate-200 rounded-lg pl-3 pr-10 py-1.5 focus:ring-sky-500 focus:border-sky-500 shadow-sm text-xs cursor-text">
                        
                        <button type="button" id="clear-postpone-filter-checksheet" onclick="clearFilterInput('postpone-filter-checksheet')" class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-300 hover:text-red-500 hidden transition-colors z-10">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                        
                        <i class="fas fa-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                        <ul id="dropdown-postpone-filter-checksheet" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-48 overflow-y-auto hidden">
                        </ul>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">ประเภทเครื่องจักร</label>
                    <div class="relative">
                        <input type="text" id="postpone-filter-type" placeholder="ค้นหาประเภท..." autocomplete="off"
                            class="w-full border-slate-200 rounded-lg pl-3 pr-10 py-1.5 focus:ring-sky-500 focus:border-sky-500 shadow-sm text-xs cursor-text">
                        
                        <button type="button" id="clear-postpone-filter-type" onclick="clearFilterInput('postpone-filter-type')" class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-300 hover:text-red-500 hidden transition-colors z-10">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                        
                        <i class="fas fa-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                        <ul id="dropdown-postpone-filter-type" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-48 overflow-y-auto hidden">
                        </ul>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">สถานที่ตั้ง</label>
                    <div class="relative">
                        <input type="text" id="postpone-filter-location" placeholder="ค้นหาสถานที่..." autocomplete="off"
                            class="w-full border-slate-200 rounded-lg pl-3 pr-10 py-1.5 focus:ring-sky-500 focus:border-sky-500 shadow-sm text-xs cursor-text">
                        
                        <button type="button" id="clear-postpone-filter-location" onclick="clearFilterInput('postpone-filter-location')" class="absolute right-6 top-1/2 -translate-y-1/2 text-slate-300 hover:text-red-500 hidden transition-colors z-10">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>

                        <i class="fas fa-chevron-down absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px] pointer-events-none"></i>
                        <ul id="dropdown-postpone-filter-location" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-48 overflow-y-auto hidden">
                        </ul>
                    </div>
                </div>
            </div>

            <div class="pt-4 mt-2 border-t border-slate-100 grid grid-cols-2 gap-2 shrink-0">
                <button onclick="resetFiltersPostpone()" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium py-2.5 rounded-lg transition duration-200 text-xs">
                    ล้างค่า
                </button>
                <button onclick="applyPostponeFilters()" class="bg-sky-600 hover:bg-sky-700 text-white font-semibold py-2.5 rounded-lg transition duration-200 shadow-md text-xs">
                    กรองข้อมูล
                </button>
            </div>
        </aside>

        <div class="flex flex-col gap-4 overflow-hidden h-full pb-2 md:pt-0">
            <div class="calendar-wrapper bg-white p-5 rounded-xl shadow-sm border border-slate-200 flex-1 flex flex-col min-h-0">
                <div class="flex justify-between items-center mb-4 shrink-0">
                    <h2 class="text-lg font-bold text-slate-800">
                        <button type="button" class="p-2 bg-sky-50 text-sky-600 rounded-lg md:hidden hover:bg-sky-100 transition-colors" onclick="togglePostponeSidebar()">
                            <i class="fas fa-filter pointer-events-none"></i>
                        </button>
                        <button type="button" onclick="togglePmPanel('postpone', false)" class="pm-expand-btn items-center gap-1.5 px-2.5 py-1.5 mr-2 rounded-lg bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 text-sm font-medium align-middle" title="แสดงแผงตัวกรอง">
                            <i class="fas fa-angles-right"></i> ตัวกรอง
                        </button>
                        <i class="fas fa-calendar-alt text-sky-600 mr-2"></i> จัดการเลื่อนแผนงาน PM</h2>
                    <button onclick="savePostponements()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 md:px-6 rounded-lg text-xs md:text-sm font-bold shadow-md transition">
                        <i class="fas fa-save md:mr-2"></i> <span class="hidden md:inline">บันทึกการเลื่อนทั้งหมด</span>
                    </button>
                </div>
                <div class="flex-1 relative border border-slate-200 rounded-lg overflow-hidden">
                    <div id="hot-container" class="absolute inset-0 z-0"></div>
                    
                    <div id="no-data-overlay" class="absolute inset-0 z-10 hidden backdrop-blur-[2px]">
                        <div class="flex flex-col items-center justify-center h-full">
                            <i class="fas fa-folder-open text-4xl text-slate-300 mb-3"></i>
                            <span class="text-slate-500 font-medium">ไม่พบข้อมูลตามเงื่อนไขที่กำหนด</span>
                        </div>
                    </div>
                </div>
            </div>

            <div id="history-section" class="bg-slate-50 p-4 rounded-xl border border-slate-200 hidden shrink-0">
                <h3 class="text-sm font-bold text-slate-700 mb-3"><i class="fas fa-history mr-2"></i> ประวัติการเลื่อนของรายการที่เลือก</h3>
                <div id="history-list" class="space-y-2 max-h-32 overflow-y-auto text-xs custom-scrollbar">
                </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-between items-center px-4 py-3 bg-white border border-slate-200 rounded-xl shadow-sm gap-4 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 font-medium">แสดงหน้าละ:</span>
                    <select id="page-size-select" onchange="changePageSize()" class="text-xs border-slate-300 rounded-lg py-1.5 pl-3 pr-8 focus:ring-sky-500 focus:border-sky-500 shadow-sm cursor-pointer bg-slate-50 hover:bg-slate-100 transition-colors appearance-none outline-none">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50" selected>50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="text-xs text-slate-500 font-medium">
                    แสดง <span id="start-record-info" class="font-bold text-sky-600">0</span> - <span id="end-record-info" class="font-bold text-sky-600">0</span> จาก <span id="total-records" class="font-bold text-slate-800">0</span> รายการ
                </div>

                <div class="flex items-center gap-1 bg-slate-50 p-1 rounded-lg border border-slate-200">
                    <button onclick="changePage(-1)" id="prev-btn" class="flex items-center justify-center w-8 h-8 rounded-md hover:bg-white hover:shadow-sm text-slate-600 disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:shadow-none transition-all" title="ก่อนหน้า">
                        <i class="fas fa-chevron-left text-[10px]"></i>
                    </button>
                    <div class="px-3 py-1 text-xs font-bold text-slate-700 bg-white shadow-sm border border-slate-200 rounded-md min-w-[2.5rem] text-center" id="page-info">1</div>
                    <button onclick="changePage(1)" id="next-btn" class="flex items-center justify-center w-8 h-8 rounded-md hover:bg-white hover:shadow-sm text-slate-600 disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:shadow-none transition-all" title="ถัดไป">
                        <i class="fas fa-chevron-right text-[10px]"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

<script>
    const P_AG_ID = '<?php echo isset($sess_user_agency_es) ? $sess_user_agency_es : ""; ?>';
    const P_USER_ID = '<?php echo isset($sess_user_id) ? $sess_user_id : "1"; ?>'; 
    const CONTRACT_START = '<?php echo isset($sess_ag_start_date) ? $sess_ag_start_date : ""; ?>';
    const CONTRACT_END = '<?php echo isset($sess_ag_end_date) ? $sess_ag_end_date : ""; ?>';
    
    let currentPage = 1;
    let rowsPerPage = 50;

    // ประกาศเป็น window object เพื่อให้สคริปต์ใน pm.php สั่งอัปเดตขนาดได้
    window.hotInstance = null;
    let postponeTableData = [];

    function togglePostponeSidebar() {
        const sidebar = document.getElementById('postpone-filter-sidebar');
        const backdrop = document.getElementById('postpone-sidebar-backdrop');
        
        sidebar.classList.toggle('-translate-x-full');
        
        // สลับแสดง/ซ่อน backdrop
        if (backdrop) {
            backdrop.classList.toggle('hidden');
        }
    }

    function resetToCurrentDate() {
        const d = new Date();
        document.getElementById('postpone-filter-month').value = d.getMonth() + 1;
        document.getElementById('postpone-filter-year').value = d.getFullYear();
    }

    async function fetchPostponeData() {
        const startRow = (currentPage - 1) * rowsPerPage;
        const endRow = startRow + rowsPerPage;
        // 1. ดึงค่าจากตัวกรองต่างๆ ในหน้า UI
        const machine = document.getElementById('postpone-filter-machine').value;
        const checksheet = document.getElementById('postpone-filter-checksheet').value;
        const type = document.getElementById('postpone-filter-type').value;
        const location = document.getElementById('postpone-filter-location').value;
        const month = document.getElementById('postpone-filter-month').value;
        const year = document.getElementById('postpone-filter-year').value;

        // 2. สร้าง Query Parameters
        const params = new URLSearchParams({
            action: 'get_all',
            startRow: startRow,
            endRow: endRow,
            machine: machine,
            checksheet: checksheet,
            type: type,
            location: location,
            month: month,
            year: year
        });

        try {
            // 3. เรียก API พร้อมส่งค่าตัวกรองไปด้วย
            const res = await fetch(`handle_pm_postpone.php?${params.toString()}`);
            const result = await res.json();
            
            if (result.success) {
                const rawData = result.data || []; 
                
                const mappedData = rawData.map(item => ({
                    id: item.id,
                    qr_code: item.extendedProps.qr_code,
                    machine: item.extendedProps.machine,
                    postpone_count: item.postpone_count,
                    type: item.extendedProps.type,
                    location: item.extendedProps.location,
                    checksheet: item.extendedProps.checksheet,
                    frequency: item.extendedProps.frequency,
                    old_date: item.start,
                    new_date: null,
                    reason: ''
                }));
                
                // โหลดข้อมูลเข้าตาราง (ถึงเป็น array ว่าง [] ตารางก็จะเคลียร์ข้อมูลให้)
                if (window.hotInstance) {
                    window.hotInstance.loadData(mappedData);
                    window.hotInstance.render();
                }

                // ==========================================
                // เพื่อเปิด/ปิดหน้าจอ "ไม่พบข้อมูล"
                // ==========================================
                // const noDataOverlay = document.getElementById('no-data-overlay');
                // if (mappedData.length === 0) {
                //     noDataOverlay.classList.remove('hidden');
                // } else {
                //     noDataOverlay.classList.add('hidden');
                // }
                // ==========================================

                const totalRecords = parseInt(result.total, 10) || 0;
                const actualStart = totalRecords === 0 ? 0 : startRow + 1;
                const actualEnd = endRow > totalRecords ? totalRecords : endRow;

                document.getElementById('total-records').textContent = totalRecords;
                document.getElementById('start-record-info').textContent = actualStart;
                document.getElementById('end-record-info').textContent = actualEnd;
                document.getElementById('page-info').textContent = currentPage;

                document.getElementById('prev-btn').disabled = currentPage === 1;
                document.getElementById('next-btn').disabled = endRow >= totalRecords;
            }
        } catch (error) {
            console.error("Error fetching postpone data:", error);
        }
    }

    function changePageSize() {
        const select = document.getElementById('page-size-select');
        rowsPerPage = parseInt(select.value, 10);
        currentPage = 1;
        fetchPostponeData();
    }

    function changePage(step) {
        currentPage += step;
        fetchPostponeData();
    }

    // ฟังก์ชันหลักดึงข้อมูลจาก API
    async function loadCustomSelectFilters() {
        try {
            // กำหนด URL (ดึงมา 1000 รายการเพื่อให้ครอบคลุมการค้นหาในหน้า Dashboard)
            const urlMachine = `handle_machine_info.php?action=get_all&ag_id=${P_AG_ID}&startRow=0&endRow=1000`;
            const urlChecksheet = `handle_pm_checksheet.php?action=get_all&ag_id=${P_AG_ID}&startRow=0&endRow=1000`;

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
                    // ใช้ฟิลด์ machine_name ตามที่ระบุใน PHP
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

            // --- 3. ติดตั้ง Custom Select (เรียกใช้ฟังก์ชัน setupCustomSelect เดิม) ---
            setupCustomSelect('postpone-filter-machine', 'dropdown-postpone-filter-machine', machineItems);
            setupCustomSelect('postpone-filter-checksheet', 'dropdown-postpone-filter-checksheet', checksheetItems);

        } catch (error) {
            console.error("เกิดข้อผิดพลาดในการโหลดข้อมูล Filter:", error);
        }
    }

    // ฟังก์ชันดึงข้อมูลประเภทเครื่องจักร
    async function loadPostponeMachineTypes() {
        try {
            const response = await fetch('get_all_ass_type.php');
            const data = await response.json();

            // เตรียมข้อมูลเริ่มต้น
            let items = [{ text: 'ทั้งหมด', value: '' }];

            if (data && Array.isArray(data)) {
                data.forEach(type => {
                    // ใช้ type.name ตามโครงสร้างเดิมที่คุณส่งมา
                    items.push({ text: type.name, value: type.name });
                });
            }

            // ติดตั้ง Custom Select Search
            setupCustomSelect('postpone-filter-type', 'dropdown-postpone-filter-type', items);

        } catch (error) {
            console.error("เกิดข้อผิดพลาดในการดึงข้อมูลประเภทเครื่องจักร:", error);
        }
    }

    // ฟังก์ชันดึงข้อมูลสถานที่ตั้ง
    async function loadPostponeLocations() {
        try {
            const response = await fetch(`get_all_building.php?ag_id=${P_AG_ID}`);
            const data = await response.json();

            // เตรียมข้อมูลเริ่มต้น
            let items = [{ text: 'ทั้งหมด', value: '' }];

            // เช็คตามโครงสร้าง data.building ที่คุณส่งมา
            if (data && data.building && Array.isArray(data.building)) {
                data.building.forEach(building => {
                    items.push({ 
                        text: building.area_name, 
                        value: building.area_name 
                    });
                });
            }

            // ติดตั้ง Custom Select Search
            setupCustomSelect('postpone-filter-location', 'dropdown-postpone-filter-location', items);

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

    function setupCustomSelect(inputId, dropdownId, items) {
        const inputEl = document.getElementById(inputId);
        const dropdownEl = document.getElementById(dropdownId);
        const clearBtn = document.getElementById('clear-' + inputId); // ดึงปุ่มกากบาทตาม id
        
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
                    toggleClearBtn(); // อัปเดตปุ่มตอนเลือกรายการเสร็จ
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


    function resetFiltersPostpone() {
        const filters = ['postpone-filter-machine', 'postpone-filter-type', 'postpone-filter-location', 'postpone-filter-checksheet'];
        
        filters.forEach(id => {
            document.getElementById(id).value = '';
            const clearBtn = document.getElementById('clear-' + id);
            if (clearBtn) clearBtn.classList.add('hidden');
        });
        
        // รีเซ็ตเดือนและปีให้เป็น "ทั้งหมด"
        document.getElementById('postpone-filter-month').value = '';
        document.getElementById('postpone-filter-year').value = '';
        
        // หากต้องการให้ปุ่มล้างค่าเรียกโหลดข้อมูลใหม่ ให้ใช้ฟังก์ชันเก่า หรือ fetchPostponeData() ก็ได้
        currentPage = 1;
        fetchPostponeData(); 
        
        if (window.innerWidth < 768) {
            const sidebar = document.getElementById('postpone-filter-sidebar');
            if (!sidebar.classList.contains('-translate-x-full')) {
                togglePostponeSidebar();
            }
        }
    }

    function applyPostponeFilters() {
        currentPage = 1;
        fetchPostponeData();
        if (window.innerWidth < 768) togglePostponeSidebar();
    }

    document.addEventListener("DOMContentLoaded", () => {
        initHandsontable(); 
        loadPostponeLocations();
        loadPostponeMachineTypes();
        loadCustomSelectFilters();
        
        fetchPostponeData(); 
    });

    async function showPostponeHistory(planId) {
        try {
            // ยิง API ไปขอประวัติการเลื่อน
            const res = await fetch(`handle_pm_postpone.php?action=get_history&plan_id=${planId}`);
            const resData = await res.json();

            if (resData.success && resData.data.length > 0) {
                let html = '<div class="text-left text-sm max-h-[50vh] overflow-y-auto pr-2"><ul class="list-none space-y-3">';
                
                resData.data.forEach((item, index) => {
                    html += `
                        <li class="bg-slate-50 p-3 rounded border border-slate-200">
                            <div class="font-bold text-sky-700 mb-1">ครั้งที่ ${index + 1}</div>
                            <div><i class="far fa-calendar-alt text-slate-400"></i> แผนเดิม: <span class="line-through text-red-500">${item.old_date}</span> <i class="fas fa-arrow-right text-slate-400 mx-1"></i> <span class="text-green-600 font-semibold">${item.new_date}</span></div>
                            <div class="text-slate-600 mt-1"><i class="fas fa-info-circle text-slate-400"></i> เหตุผล: ${item.reason}</div>
                        </li>`;
                });
                
                html += '</ul></div>';

                Swal.fire({
                    title: 'ประวัติการเลื่อนแผนงาน',
                    html: html,
                    icon: 'info',
                    confirmButtonText: 'ปิด',
                    confirmButtonColor: '#0284c7',
                    width: '500px'
                });
            } else {
                Swal.fire('ข้อมูลว่างเปล่า', 'ไม่พบประวัติการเลื่อนแผนนี้ในระบบ', 'info');
            }
        } catch (error) {
            console.error(error);
            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลประวัติได้', 'error');
        }
    }

    function initHandsontable() {
        const container = document.getElementById('hot-container');
        const todayDate = new Date();
        todayDate.setHours(0,0,0,0);
        hotInstance = new Handsontable(container, {
            data: [],
            colHeaders: ['QR Code', 'ชื่อเครื่องจักร', 'ประเภท', 'สถานที่', 'เช็คชีต', 'ความถี่(วัน)', 'วันที่แผนเดิม', 'วันที่เลื่อนใหม่', 'เหตุผลที่เลื่อน', 'ประวัติเลื่อน'],
            columns: [
                { data: 'qr_code', readOnly: true, className: 'htCenter htMiddle font-mono' },
                { data: 'machine', readOnly: true, className: 'htMiddle' },
                { data: 'type', readOnly: true, className: 'htMiddle' },
                { data: 'location', readOnly: true, className: 'htMiddle' },
                { data: 'checksheet', readOnly: true, className: 'htMiddle' },
                { data: 'frequency', readOnly: true, className: 'htCenter htMiddle font-bold' },
                { 
                    data: 'old_date', 
                    readOnly: true, 
                    width: 150,
                    className: 'htCenter htMiddle text-rose-600',
                    renderer: function(instance, td, row, col, prop, value, cellProperties) {
                        Handsontable.renderers.TextRenderer.apply(this, arguments);
                        if (value) {
                            td.innerText = window.formatDate(value, false);
                        } else {
                            td.innerText = '-';
                        }

                        return td;
                    }
                },
                { 
                    data: 'new_date', 
                    type: 'date', 
                    dateFormat: 'YYYY-MM-DD', 
                    correctFormat: true,
                    defaultDate: moment().format('YYYY-MM-DD'),
                    datePickerConfig: {
                        firstDay: 1,
                        showWeekNumber: true,
                        minDate: todayDate, 
                        maxDate: new Date(CONTRACT_END),
                        disableDayFn: function(date) {
                            return date.getDay() === 0;
                        }
                    }
                },
                { data: 'reason', type: 'text', className: 'htMiddle bg-amber-50' },
                { 
                    data: 'postpone_count', 
                    readOnly: true, 
                    className: 'htCenter htMiddle',
                    renderer: function(instance, td, row, col, prop, value) {
                        td.innerHTML = value > 0 ? `<span class="bg-red-100 text-red-700 px-2 py-0.5 rounded-full font-bold">${value} ครั้ง</span>` : `<span class="text-slate-400">0</span>`;
                        return td;
                    }
                },
            ],
            rowHeaders: true, 
            stretchH: 'all',
            height: '100%',
            afterSelection: (row) => {
                const rowData = hotInstance.getSourceDataAtRow(row);
                if(rowData) loadPostponeHistory(rowData.id);
            },
            afterOnCellMouseDown: function(event, coords, td) {
                if (coords.row < 0) return; // ข้ามถ้าคลิกที่ Header
                const prop = this.colToProp(coords.col);
                
                // ถ้าคลิกที่คอลัมน์ postpone_count และมีจำนวนมากกว่า 0
                if (prop === 'postpone_count') {
                    const rowData = this.getSourceDataAtRow(coords.row);
                    if (rowData.postpone_count > 0) {
                        showPostponeHistory(rowData.id);
                    }
                }
            },
            licenseKey: 'non-commercial-and-evaluation'
        });
    }

    async function loadPostponeHistory(planId) {
        const section = document.getElementById('history-section');
        const list = document.getElementById('history-list');
        const res = await fetch(`handle_pm_postpone.php?plan_id=${planId}`);
        const result = await res.json();
        
        if (result.success && result.data.length > 0) {
            section.classList.remove('hidden');
            list.innerHTML = result.data.map(h => `
                <div class="flex flex-col p-2 bg-slate-50 border-l-4 border-sky-500 rounded shadow-sm">
                    <span class="font-bold text-sky-700">เลื่อนเป็น: ${h.new_date} (จาก ${h.old_date})</span>
                    <span class="text-slate-600">เหตุผล: ${h.reason}</span>
                    <small class="text-slate-400">โดย: ${h.user_name || 'System'} เมื่อ ${h.created_at}</small>
                </div>
            `).join('');
        } else {
            section.classList.add('hidden');
        }
    }

    async function savePostponements() {
        const allData = hotInstance.getSourceData();
        const today = moment().startOf('day');
        let updates = [];
        let errors = [];

        allData.forEach((row, i) => {
            if (row.new_date) {
                const newD = moment(row.new_date);
                const oldD = moment(row.old_date);
                const freq = parseInt(row.frequency);

                if (!row.reason) errors.push(`แถวที่ ${i+1}: ต้องระบุเหตุผล`);
                if (newD.isBefore(today)) errors.push(`แถวที่ ${i+1}: ห้ามเลื่อนเป็นวันในอดีต`);
                
                // กฎ: ถ้าแผนเดิมเลยกำหนด (OldDate < Today) ต้องมีความถี่ >= 30 วัน
                if (oldD.isBefore(today) && freq < 30) {
                    errors.push(`แถวที่ ${i+1}: งานเลยกำหนดที่มีความถี่น้อยกว่า 30 วัน ไม่อนุญาตให้เลื่อน`);
                }

                updates.push({
                    plan_id: row.id,
                    old_date: row.old_date,
                    new_date: row.new_date,
                    reason: row.reason,
                    user_id: P_USER_ID
                });
            }
        });

        if (errors.length > 0) {
            Swal.fire('ข้อผิดพลาด', errors.join('<br>'), 'error');
            return;
        }

        if (updates.length === 0) return Swal.fire('ข้อมูลว่าง', 'ระบุข้อมูลการเลื่อนอย่างน้อย 1 รายการ', 'info');

        const confirm = await Swal.fire({ title: 'ยืนยัน?', text: `บันทึกการเลื่อน ${updates.length} รายการ`, icon: 'warning', showCancelButton: true });
        if (confirm.isConfirmed) {
            const res = await fetch('handle_pm_postpone.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ updates })
            });
            const resData = await res.json();
            if (resData.success) {
                Swal.fire('สำเร็จ', 'บันทึกข้อมูลเรียบร้อย', 'success');
                fetchPostponeData();
                document.getElementById('history-section').classList.add('hidden');
            }
        }
    }


</script>