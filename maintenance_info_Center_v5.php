<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Maintenance Execution System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8fafc;
        }
        
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

        .job-card {
            transition: all 0.2s ease-in-out;
            border-right: 4px solid transparent; /* เปลี่ยนจากซ้ายเป็นขวา */
        }
        .job-card.active {
            background-color: #f1f5f9;
            border-right-color: #006B9F;
        }
        .priority-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .issue-box {
            background: linear-gradient(to bottom right, #fff1f2, #ffffff);
            border-left: 4px solid #ef4444;
        }

        .filter-chip {
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .filter-chip.active {
            background-color: #006B9F;
            color: white;
            border-color: #006B9F;
        }

        /* Sidebar Right Logic */
        #sidebar {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @media (max-width: 768px) {
            .sidebar-closed {
                transform: translateX(100%); /* เปลี่ยนเป็น 100% เพื่อซ่อนทางขวา */
            }
            .sidebar-open {
                transform: translateX(0);
            }
        }
        
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
    </style>
</head>
<body class="h-screen flex flex-col overflow-hidden text-slate-700">

    <!-- Header Navigation -->
    <header class="h-16 glass-effect border-b border-slate-200 flex items-center justify-between px-4 md:px-6 z-40 shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-[#006B9F] rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-900/20">
                <i data-lucide="wrench" class="w-6 h-6"></i>
            </div>
            <div class="block xs:hidden">
                <h1 class="font-bold text-slate-900 leading-none text-sm md:text-base">Maintenance Hub</h1>
                <span class="text-[10px] text-slate-400 font-medium tracking-tighter uppercase">Execution & Monitoring</span>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <button onclick="toggleSidebar(true)" class="md:hidden p-2 hover:bg-slate-100 rounded-xl text-slate-600 transition-colors">
                <i data-lucide="list-filter" class="w-6 h-6"></i>
            </button>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden relative">
        
        <!-- Workspace (Now appears first or left of sidebar on desktop) -->
        <main class="flex-1 flex flex-col bg-[#f1f5f9] overflow-hidden relative order-1">
            
            <div id="welcomeView" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-[#f1f5f9] p-6 text-center">
                <div class="w-16 h-16 bg-white rounded-2xl shadow-sm border border-slate-200 flex items-center justify-center mb-4 text-slate-200">
                    <i data-lucide="file-text" class="w-8 h-8"></i>
                </div>
                <h3 class="font-bold text-slate-800">เลือกรายการงานเพื่อดำเนินการ</h3>
                <button onclick="toggleSidebar(true)" class="mt-4 md:hidden px-6 py-2 bg-[#006B9F] text-white rounded-xl text-xs font-bold shadow-lg shadow-blue-900/20 active:scale-95 transition-transform">
                    เปิดรายการงาน
                </button>
            </div>

            <div id="executionView" class="hidden flex-1 flex flex-col overflow-hidden">
                <div class="h-20 glass-effect border-b border-slate-200 px-6 md:px-8 flex items-center justify-between shrink-0">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span id="displayJobId" class="text-[10px] font-black text-[#006B9F] tracking-tighter">ID</span>
                            <span id="displayPrio" class="px-2 py-0.5 rounded text-[8px] font-black uppercase text-white">PRIO</span>
                        </div>
                        <h2 id="displayTitle" class="text-base md:text-lg font-bold text-slate-900 truncate">Title</h2>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-4 md:p-8">
                    <form id="maintenanceForm" class="max-w-6xl mx-auto">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                            
                            <!-- Left Column: Reference Info -->
                            <div class="lg:col-span-4 space-y-6">
                                <div class="issue-box rounded-2xl shadow-sm overflow-hidden p-6 border border-red-100 h-fit sticky top-0">
                                    <div class="flex items-start gap-4 mb-4">
                                        <div class="p-2 bg-red-100 rounded-lg text-red-600">
                                            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                                        </div>
                                        <h4 class="text-sm font-bold uppercase tracking-wider text-red-800">ข้อมูลปัญหา</h4>
                                    </div>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">รายละเอียดจากผู้แจ้ง</label>
                                            <p id="displayIssue" class="text-sm text-slate-700 leading-relaxed font-medium">ไม่มีข้อมูลรายละเอียด</p>
                                        </div>
                                        <div class="pt-4 border-t border-red-50">
                                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">สถานที่แจ้งซ่อม</label>
                                            <div id="displayLocation" class="text-sm text-slate-700 font-medium flex items-center gap-2">
                                                <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
                                                <span>ระบุสถานที่...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Execution Form -->
                            <div class="lg:col-span-8 space-y-6">
                                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label class="text-[11px] font-bold text-slate-400 uppercase">วันที่ดำเนินการ</label>
                                        <input type="datetime-local" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-4 focus:ring-blue-500/5 outline-none">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[11px] font-bold text-slate-400 uppercase">ช่างผู้รับผิดชอบ</label>
                                        <input type="text" value="อานนท์ แก้ไขงาน" readonly class="w-full px-4 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-sm text-slate-500 font-medium">
                                    </div>
                                </div>

                                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-6">
                                    <div class="space-y-2">
                                        <label class="text-[11px] font-bold text-slate-400 uppercase">บันทึกผลการปฏิบัติงาน</label>
                                        <textarea rows="5" required placeholder="อธิบายขั้นตอนการแก้ไข..." class="w-full px-4 py-3 border border-slate-200 rounded-xl text-sm focus:ring-4 focus:ring-blue-500/5 outline-none resize-none transition-all"></textarea>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-[11px] font-bold text-slate-400 uppercase">วัสดุ/อะไหล่ที่ใช้</label>
                                        <input type="text" placeholder="ระบุชื่ออะไหล่และจำนวน..." class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-4 focus:ring-blue-500/5 outline-none">
                                    </div>
                                </div>

                                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                                    <label class="text-[11px] font-bold text-slate-400 uppercase block mb-4">รูปภาพหลักฐานการทำงาน</label>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4" id="imagePreviewContainer">
                                        <label class="aspect-square rounded-2xl border-2 border-dashed border-slate-200 hover:border-[#006B9F] hover:bg-blue-50/50 flex flex-col items-center justify-center cursor-pointer transition-all">
                                            <i data-lucide="plus" class="text-slate-300 w-6 h-6"></i>
                                            <input type="file" multiple class="hidden" onchange="previewImages(event)">
                                        </label>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-4 pb-12">
                                    <button type="button" onclick="resetView()" class="w-full sm:w-auto px-6 py-2.5 text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors">ยกเลิก</button>
                                    <button type="submit" class="w-full sm:w-auto px-10 py-3 bg-[#006B9F] text-white rounded-xl text-xs font-bold shadow-xl shadow-blue-900/20 hover:scale-[1.02] active:scale-95 transition-all">
                                        ยืนยันปิดงาน
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <!-- Sidebar Drawer Backdrop -->
        <div id="sidebarBackdrop" onclick="toggleSidebar(false)" class="fixed inset-0 bg-slate-900/40 z-40 hidden md:hidden opacity-0 transition-opacity duration-300"></div>

        <!-- Sidebar Drawer (Now on the RIGHT) -->
        <aside id="sidebar" class="fixed inset-y-0 right-0 w-80 md:w-96 bg-white border-l border-slate-200 flex flex-col shrink-0 z-50 md:z-30 sidebar-closed md:relative md:translate-x-0 order-2">
            <div class="p-4 border-b border-slate-100 space-y-4">
                <div class="flex items-center justify-between md:hidden">
                    <h2 class="font-bold text-slate-900">รายการงานซ่อม</h2>
                    <button onclick="toggleSidebar(false)" class="p-2 text-slate-400 hover:text-slate-600 transition-colors">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <div class="relative group">
                    <input type="text" id="searchInput" onkeyup="filterTasks()" placeholder="ค้นหารหัสงานหรือชื่อ..." class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-[#006B9F] outline-none transition-all">
                    <i data-lucide="search" class="absolute left-3 top-3 text-slate-400 group-focus-within:text-[#006B9F] w-4 h-4"></i>
                </div>
                
                <div class="flex gap-2 overflow-x-auto pb-2 no-scrollbar">
                    <button id="btn-all" onclick="setFilter('all')" class="filter-chip active px-4 py-1.5 rounded-full border border-slate-200 text-[10px] font-bold uppercase tracking-tight">ทั้งหมด</button>
                    <button id="btn-urgent" onclick="setFilter('urgent')" class="filter-chip px-4 py-1.5 rounded-full border border-slate-200 text-[10px] font-bold text-red-500 uppercase tracking-tight">เร่งด่วน</button>
                    <button id="btn-high" onclick="setFilter('high')" class="filter-chip px-4 py-1.5 rounded-full border border-slate-200 text-[10px] font-bold text-orange-500 uppercase tracking-tight">สูง</button>
                    <button id="btn-medium" onclick="setFilter('medium')" class="filter-chip px-4 py-1.5 rounded-full border border-slate-200 text-[10px] font-bold text-yellow-600 uppercase tracking-tight">ปานกลาง</button>
                    <button id="btn-low" onclick="setFilter('low')" class="filter-chip px-4 py-1.5 rounded-full border border-slate-200 text-[10px] font-bold text-emerald-500 uppercase tracking-tight">ต่ำ</button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto" id="taskList">
                <?php
                $tasks = [
                    ['id' => 'MT-8842', 'title' => 'ซ่อมบำรุงปั๊มน้ำแรงดันสูง ชั้น 1', 'loc' => 'ห้องเครื่อง AHU 01', 'prio' => 'urgent', 'desc' => 'ปั๊มน้ำมีเสียงดังผิดปกติและมีน้ำรั่วซึมบริเวณข้อต่อหลัก ทำให้น้ำไหลลงพื้นห้องเครื่องเป็นจำนวนมาก'],
                    ['id' => 'MT-8845', 'title' => 'เปลี่ยนหลอดไฟทางเดินฉุกเฉิน', 'loc' => 'อาคาร B ทางหนีไฟ', 'prio' => 'high', 'desc' => 'ไฟสำรองไม่สว่างเมื่อทดสอบกดปุ่ม Test คาดว่าแบตเตอรี่เสื่อมสภาพ'],
                    ['id' => 'MT-8848', 'title' => 'ตรวจสอบระบบปรับอากาศสำนักงาน', 'loc' => 'ชั้น 4 ฝ่ายบริหาร', 'prio' => 'medium', 'desc' => 'แอร์ห้องผู้อำนวยการมีกลิ่นอับและลมไม่เย็นเท่าที่ควร'],
                    ['id' => 'MT-8850', 'title' => 'เช็คระดับน้ำมันเครื่องปั่นไฟสำรอง', 'loc' => 'ดาดฟ้า อาคาร C', 'prio' => 'low', 'desc' => 'ตรวจเช็คตามรอบบำรุงรักษารายเดือน (PM)'],
                ];

                foreach($tasks as $t):
                    $prio_color = ['urgent'=>'#ef4444', 'high'=>'#f97316', 'medium'=>'#eab308', 'low'=>'#10b981'][$t['prio']];
                ?>
                <div onclick="selectJob(<?php echo htmlspecialchars(json_encode($t)); ?>, this)" 
                     data-prio="<?php echo $t['prio']; ?>"
                     data-title="<?php echo strtolower($t['title'] . ' ' . $t['id']); ?>"
                     class="job-card p-5 border-b border-slate-50 cursor-pointer hover:bg-slate-50 relative group">
                    <div class="flex justify-between items-start mb-1">
                        <span class="text-[10px] font-bold text-[#006B9F] tracking-tighter"><?php echo $t['id']; ?></span>
                    </div>
                    <h4 class="text-sm font-semibold text-slate-800 leading-snug line-clamp-2 mb-2 group-hover:text-[#006B9F] transition-colors"><?php echo $t['title']; ?></h4>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5 text-[10px] text-slate-400">
                            <i data-lucide="map-pin" class="w-3 h-3"></i>
                            <span class="truncate max-w-[180px]"><?php echo $t['loc']; ?></span>
                        </div>
                        <div class="priority-dot shadow-sm shadow-slate-200" style="background-color: <?php echo $prio_color; ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </aside>

    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        let currentPriorityFilter = 'all';

        function toggleSidebar(open) {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            
            if (open) {
                sidebar.classList.remove('sidebar-closed');
                sidebar.classList.add('sidebar-open');
                backdrop.classList.remove('hidden');
                setTimeout(() => backdrop.classList.add('opacity-100'), 10);
            } else {
                sidebar.classList.remove('sidebar-open');
                sidebar.classList.add('sidebar-closed');
                backdrop.classList.remove('opacity-100');
                setTimeout(() => backdrop.classList.add('hidden'), 300);
            }
        }

        function setFilter(prio) {
            currentPriorityFilter = prio;
            document.querySelectorAll('.filter-chip').forEach(btn => btn.classList.remove('active'));
            const activeBtn = document.getElementById(`btn-${prio}`);
            if (activeBtn) activeBtn.classList.add('active');
            filterTasks();
        }

        function filterTasks() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.job-card');

            cards.forEach(card => {
                const cardPrio = card.getAttribute('data-prio');
                const cardContent = card.getAttribute('data-title');
                const matchesPrio = (currentPriorityFilter === 'all' || cardPrio === currentPriorityFilter);
                const matchesSearch = cardContent.includes(searchText);

                card.style.display = (matchesPrio && matchesSearch) ? 'block' : 'none';
            });
        }

        function selectJob(jobData, element) {
            document.querySelectorAll('.job-card').forEach(c => c.classList.remove('active'));
            element.classList.add('active');
            
            if (window.innerWidth < 768) toggleSidebar(false);

            document.getElementById('welcomeView').classList.add('hidden');
            const view = document.getElementById('executionView');
            view.classList.remove('hidden');

            document.getElementById('displayJobId').innerText = jobData.id;
            document.getElementById('displayTitle').innerText = jobData.title;
            document.getElementById('displayIssue').innerText = jobData.desc;
            document.getElementById('displayLocation').querySelector('span').innerText = jobData.loc;
            
            const prioBadge = document.getElementById('displayPrio');
            prioBadge.innerText = jobData.prio.toUpperCase();
            prioBadge.style.backgroundColor = {
                'urgent': '#ef4444', 'high': '#f97316', 'medium': '#eab308', 'low': '#10b981'
            }[jobData.prio];

            document.getElementById('maintenanceForm').reset();
            const container = document.getElementById('imagePreviewContainer');
            container.querySelectorAll('.preview-item').forEach(p => p.remove());
        }

        function resetView() {
            document.getElementById('welcomeView').classList.remove('hidden');
            document.getElementById('executionView').classList.add('hidden');
            document.querySelectorAll('.job-card').forEach(c => c.classList.remove('active'));
        }

        function previewImages(event) {
            const container = document.getElementById('imagePreviewContainer');
            Array.from(event.target.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.className = "preview-item aspect-square rounded-2xl overflow-hidden relative border border-slate-100 group shadow-sm";
                    div.innerHTML = `
                        <img src="${e.target.result}" class="w-full h-full object-cover">
                        <button type="button" onclick="this.parentElement.remove()" class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all">
                            <i data-lucide="x" class="text-white w-6 h-6"></i>
                        </button>
                    `;
                    container.prepend(div);
                    lucide.createIcons(); // Refresh icons for new elements
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
</body>
</html>