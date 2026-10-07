<?php 
@session_start();
include "config_ctrl/checksession.php"; 
?>

<style>
    /* ตั้งค่าความสูงเริ่มต้นสำหรับ Desktop */
    #tab-feedback {
        height: calc(100vh - 180px);
        min-height: 500px;
    }
    
    .swal2-container {
        z-index: 10000 !important; 
    }
    
    /* ซ่อน Scrollbar นอกเฉพาะบน Desktop (จอใหญ่) */
    @media (min-width: 769px) {
        body { overflow: hidden; }
    }

    /* การแสดงผลบน Mobile (จอเล็ก) */
    @media (max-width: 768px) {
        body { overflow-y: auto !important; }
        
        #tab-feedback {
            height: auto !important;
            min-height: calc(100vh - 80px);
        }
        
        .feedback-content-wrapper {
            height: 75vh !important;
            min-height: 550px;
            padding: 1rem !important;
        }

        /* Sidebar */
        .feedback-sidebar {
            position: fixed;
            left: -100%;
            top: 0;
            bottom: 0;
            width: 280px;
            z-index: 100;
            transition: 0.3s;
            background: white;
            box-shadow: 10px 0 15px -3px rgba(0, 0, 0, 0.1);
        }
        .feedback-sidebar.active { left: 0; }
        .feedback-sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 90;
        }
        .feedback-sidebar-overlay.active { display: block; }
    }
</style>

<div id="feedback-sidebar-overlay" class="feedback-sidebar-overlay" onclick="toggleFeedbackSidebar()"></div>

<div id="tab-feedback" class="tab-content block h-full">
    <div class="grid grid-cols-1 md:grid-cols-[384px_1fr] gap-4 h-full">
        
        <!-- Sidebar ตัวกรอง -->
        <aside id="feedback-sidebar" class="feedback-sidebar grid grid-rows-[auto_1fr_auto] bg-white p-5 md:rounded-xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="grid grid-cols-[1fr_auto] items-center mb-4">
                <h3 class="text-base font-bold text-slate-800 grid grid-flow-col auto-cols-max items-center gap-2">
                    <i class="fas fa-filter text-sky-600"></i> ตัวกรอง Feedback
                </h3>
                <button class="md:hidden text-slate-400" onclick="toggleFeedbackSidebar()">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="space-y-4 overflow-y-auto pr-1">
                <!-- Filter: เครื่องจักร -->
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">เครื่องจักร / อุปกรณ์</label>
                    <div class="relative">
                        <input type="text" id="feedback-filter-machine" placeholder="ระบุชื่อ หรือเลือกจากรายการ..." autocomplete="off"
                            class="w-full border-slate-200 rounded-lg pl-3 pr-10 py-1.5 focus:ring-sky-500 focus:border-sky-500 shadow-sm text-xs cursor-pointer">
                        
                        <button type="button" id="clear-feedback-filter-machine" onclick="clearFeedbackFilterInput('feedback-filter-machine')" class="absolute right-6 text-slate-300 hover:text-red-500 hidden transition-colors z-10">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                        
                        <i class="fas fa-chevron-down absolute right-2.5 top-2.5 text-slate-400 text-[10px] pointer-events-none"></i>
                        <ul id="dropdown-feedback-machine" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-48 overflow-y-auto hidden">
                        </ul>
                    </div>
                </div>

                <!-- Filter: เช็คชีต -->
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">เช็คชีต</label>
                    <div class="relative">
                        <input type="text" id="feedback-filter-checksheet" placeholder="ระบุชื่อ หรือเลือกจากรายการ..." autocomplete="off"
                            class="w-full border-slate-200 rounded-lg pl-3 pr-10 py-1.5 focus:ring-sky-500 focus:border-sky-500 shadow-sm text-xs cursor-pointer">
                        
                        <button type="button" id="clear-feedback-filter-checksheet" onclick="clearFeedbackFilterInput('feedback-filter-checksheet')" class="absolute right-6 text-slate-300 hover:text-red-500 hidden transition-colors z-10">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                        
                        <i class="fas fa-chevron-down absolute right-2.5 top-2.5 text-slate-400 text-[10px] pointer-events-none"></i>
                        <ul id="dropdown-feedback-checksheet" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-48 overflow-y-auto hidden">
                        </ul>
                    </div>
                </div>

                <!-- Filter: ประเภทเครื่องจักร -->
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">ประเภทเครื่องจักร</label>
                    <div class="relative">
                        <input type="text" id="feedback-filter-type" placeholder="ค้นหาประเภท..." autocomplete="off"
                            class="w-full border-slate-200 rounded-lg pl-3 pr-10 py-1.5 focus:ring-sky-500 focus:border-sky-500 shadow-sm text-xs cursor-pointer">
                        
                        <button type="button" id="clear-feedback-filter-type" onclick="clearFeedbackFilterInput('feedback-filter-type')" class="absolute right-6 text-slate-300 hover:text-red-500 hidden transition-colors z-10">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>
                        
                        <i class="fas fa-chevron-down absolute right-2.5 top-2.5 text-slate-400 text-[10px] pointer-events-none"></i>
                        <ul id="dropdown-feedback-type" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-48 overflow-y-auto hidden">
                        </ul>
                    </div>
                </div>

                <!-- Filter: สถานที่ตั้ง -->
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">สถานที่ตั้ง</label>
                    <div class="relative">
                        <input type="text" id="feedback-filter-location" placeholder="ค้นหาสถานที่..." autocomplete="off"
                            class="w-full border-slate-200 rounded-lg pl-3 pr-10 py-1.5 focus:ring-sky-500 focus:border-sky-500 shadow-sm text-xs cursor-pointer">
                        
                        <button type="button" id="clear-feedback-filter-location" onclick="clearFeedbackFilterInput('feedback-filter-location')" class="absolute right-6 text-slate-300 hover:text-red-500 hidden transition-colors z-10">
                            <i class="fas fa-times text-[10px]"></i>
                        </button>

                        <i class="fas fa-chevron-down absolute right-2.5 top-2.5 text-slate-400 text-[10px] pointer-events-none"></i>
                        <ul id="dropdown-feedback-location" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-md shadow-lg max-h-48 overflow-y-auto hidden">
                        </ul>
                    </div>
                </div>
            </div>

            <!-- ปุ่ม Action ตัวกรอง -->
            <div class="pt-4 mt-4 border-t border-slate-100 grid grid-cols-2 gap-2">
                <button onclick="resetFeedbackFilters()" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-medium py-3 rounded-lg transition duration-200 text-xs">
                    ล้างค่า
                </button>
                <button onclick="applyFeedbackFilters()" class="bg-sky-600 hover:bg-sky-700 text-white font-semibold py-3 rounded-lg transition duration-200 shadow-md text-xs">
                    กรองข้อมูล
                </button>
            </div>
        </aside>

        <!-- พื้นที่แสดงผล Feedback -->
        <div class="feedback-content-wrapper bg-white p-5 rounded-xl shadow-sm border border-slate-200 relative flex flex-col h-full overflow-hidden">
            <div class="grid grid-cols-[1fr_auto] items-center mb-4">
                <div class="grid grid-flow-col auto-cols-max items-center gap-3">
                    <button onclick="toggleFeedbackSidebar()" class="md:hidden p-2 bg-sky-50 text-sky-600 rounded-lg">
                        <i class="fas fa-filter"></i>
                    </button>
                    <h3 class="text-lg font-semibold text-slate-800">รายการ Feedback</h3>
                </div>
            </div>

            <!-- พื้นที่สำหรับนำข้อมูล Feedback มาแสดงผล (แทนที่ปฏิทิน) -->
            <div id="feedback-data-container" class="flex-1 overflow-y-auto bg-slate-50 rounded-lg border border-slate-100 p-4">
                <div class="flex items-center justify-center h-full text-slate-400 text-sm">
                    <!-- แสดงเนื้อหา หรือ Loading state ที่นี่ -->
                    <p><i class="fas fa-info-circle mr-2"></i>เลือกตัวกรองเพื่อดูข้อมูล Feedback</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let isFeedbackFiltersLoaded = false;

    // ฟังก์ชันเปิด-ปิด Sidebar
    function toggleFeedbackSidebar() {
        document.getElementById('feedback-sidebar').classList.toggle('active');
        document.getElementById('feedback-sidebar-overlay').classList.toggle('active');
    }

    // ฟังก์ชันหลักดึงข้อมูลจาก API สำหรับ Select
    async function loadFeedbackCustomSelectFilters() {
        if (isFeedbackFiltersLoaded) return;
        try {
            // ใช้ AG_ID ที่ถูกประกาศไว้แล้วจากหน้า Dashboard ได้เลย
            const agIdParam = typeof AG_ID !== 'undefined' ? AG_ID : '';
            
            const urlMachine = `handle_machine_info.php?action=get_all&ag_id=${agIdParam}&startRow=0&endRow=1000`;
            const urlChecksheet = `handle_pm_checksheet.php?action=get_all&ag_id=${agIdParam}&startRow=0&endRow=1000`;

            const [resMachine, resChecksheet] = await Promise.all([
                fetch(urlMachine).then(r => r.json()),
                fetch(urlChecksheet).then(r => r.json())
            ]);

            // 1. ข้อมูลเครื่องจักร
            let machineItems = [{ text: 'ทั้งหมด', value: '' }];
            if (resMachine.rows && Array.isArray(resMachine.rows)) {
                resMachine.rows.forEach(item => {
                    const name = item.machine_name; 
                    if (name) {
                        machineItems.push({ text: name, value: name });
                    }
                });
            }

            // 2. ข้อมูลเช็คชีต
            let checksheetItems = [{ text: 'ทั้งหมด', value: '' }];
            if (resChecksheet.success && resChecksheet.data) {
                resChecksheet.data.forEach(item => {
                    const name = item.name || item.checksheet_name;
                    if (name) {
                        checksheetItems.push({ text: name, value: name });
                    }
                });
            }

            // ติดตั้ง Custom Select
            setupFeedbackCustomSelect('feedback-filter-machine', 'dropdown-feedback-machine', machineItems);
            setupFeedbackCustomSelect('feedback-filter-checksheet', 'dropdown-feedback-checksheet', checksheetItems);

        } catch (error) {
            console.error("เกิดข้อผิดพลาดในการโหลดข้อมูล Filter:", error);
        }
    }

    // ฟังก์ชันดึงข้อมูลประเภทเครื่องจักร
    async function loadFeedbackMachineTypes() {
        try {
            const response = await fetch('get_all_ass_type.php');
            const data = await response.json();

            let items = [{ text: 'ทั้งหมด', value: '' }];

            if (data && Array.isArray(data)) {
                data.forEach(type => {
                    items.push({ text: type.name, value: type.name });
                });
            }

            setupFeedbackCustomSelect('feedback-filter-type', 'dropdown-feedback-type', items);

        } catch (error) {
            console.error("เกิดข้อผิดพลาดในการดึงข้อมูลประเภทเครื่องจักร:", error);
        }
    }

    // ฟังก์ชันดึงข้อมูลสถานที่ตั้ง
    async function loadFeedbackLocations() {
        try {
            // ใช้ AG_ID ที่ถูกประกาศไว้แล้ว
            const agIdParam = typeof AG_ID !== 'undefined' ? AG_ID : '';
            const response = await fetch(`get_all_building.php?ag_id=${agIdParam}`);
            const data = await response.json();

            let items = [{ text: 'ทั้งหมด', value: '' }];

            if (data && data.building && Array.isArray(data.building)) {
                data.building.forEach(building => {
                    items.push({ 
                        text: building.area_name, 
                        value: building.area_name 
                    });
                });
            }

            setupFeedbackCustomSelect('feedback-filter-location', 'dropdown-feedback-location', items);

        } catch (error) {
            console.error("เกิดข้อผิดพลาดในการดึงข้อมูลสถานที่:", error);
        }
    }

    // ฟังก์ชันล้างค่าใน Input ตัวกรอง
    function clearFeedbackFilterInput(inputId) {
        const inputEl = document.getElementById(inputId);
        inputEl.value = '';
        inputEl.dispatchEvent(new Event('input'));
        inputEl.focus(); 
    }

    // ฟังก์ชันจัดการ Custom Select (Dropdown)
    function setupFeedbackCustomSelect(inputId, dropdownId, items) {
        const inputEl = document.getElementById(inputId);
        const dropdownEl = document.getElementById(dropdownId);
        const clearBtn = document.getElementById('clear-' + inputId);
        
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
            toggleClearBtn(); 
        });

        inputEl.addEventListener('blur', () => {
            setTimeout(() => dropdownEl.classList.add('hidden'), 200);
        });
    }

    // ฟังก์ชันเคลียร์ตัวกรองทั้งหมด
    function resetFeedbackFilters() {
        const filters = ['feedback-filter-machine', 'feedback-filter-type', 'feedback-filter-location', 'feedback-filter-checksheet'];
        
        filters.forEach(id => {
            document.getElementById(id).value = '';
            const clearBtn = document.getElementById('clear-' + id);
            if (clearBtn) clearBtn.classList.add('hidden');
        });
        
        fetchFeedbackData(); 
        if (typeof isMobile !== 'undefined' && isMobile || window.innerWidth < 768) toggleFeedbackSidebar();
    }

    // ฟังก์ชันเมื่อกด "กรองข้อมูล"
    function applyFeedbackFilters() {
        fetchFeedbackData(); 
        if (typeof isMobile !== 'undefined' && isMobile || window.innerWidth < 768) toggleFeedbackSidebar();
    }

    // ฟังก์ชันจำลองการโหลดข้อมูล Feedback (สามารถนำไปเขียนเชื่อม API ต่อได้)
    function fetchFeedbackData() {
        const filterMachine = document.getElementById('feedback-filter-machine').value;
        const filterCheck = document.getElementById('feedback-filter-checksheet').value;
        const filterType = document.getElementById('feedback-filter-type').value;
        const filterLoc = document.getElementById('feedback-filter-location').value;

        console.log("Fetching feedback data with filters:", {
            machine: filterMachine,
            checksheet: filterCheck,
            type: filterType,
            location: filterLoc
        });

        const container = document.getElementById('feedback-data-container');
        container.innerHTML = `<div class="flex items-center justify-center h-full text-slate-400 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i>กำลังโหลดข้อมูล Feedback...</div>`;
    }

    window.initFeedbackTab = function() {
        if (!isFeedbackFiltersLoaded) {
            if (typeof loadFeedbackLocations === 'function') loadFeedbackLocations();
            loadFeedbackMachineTypes();
            loadFeedbackCustomSelectFilters();
            
            // เพิ่มบรรทัดนี้เพื่อให้โหลด API แค่ครั้งแรกครั้งเดียว
            isFeedbackFiltersLoaded = true; 
        }
        
        fetchFeedbackData();
    };
</script>