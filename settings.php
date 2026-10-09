<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบตั้งค่าทันสมัย</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&family=Noto+Sans+Thai:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: 'var(--color-primary)',
                        secondary: 'var(--color-secondary)',
                        darkBlue: 'var(--dark-blue)',
                        textDark: 'var(--color-text-dark)',
                        bgLight: 'var(--color-bg-light)',
                        glassBorder: 'var(--color-border-glass)',
                    },
                    fontFamily: {
                        sans: ['Noto Sans Thai', 'sans-serif'],
                        display: ['Kanit', 'sans-serif'],
                    },
                    boxShadow: {
                        glass: 'var(--shadow-glass)',
                        dropdown: '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --color-bg-light: #F0F4F8;
            --color-main-surface: rgba(255, 255, 255, 0.85);
            --color-border-glass: rgba(255, 255, 255, 0.4);
            --color-primary: #006B9F; 
            --color-secondary: #04ADFF;
            --dark-blue: #004a6f;
            --color-text-dark: #333333;
            --shadow-glass: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        }

        body {
            background-color: var(--color-bg-light);
            color: var(--color-text-dark);
            overflow: hidden;
        }

        .glass-panel {
            background: var(--color-main-surface);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--color-border-glass);
            box-shadow: var(--shadow-glass);
        }

        /* สถานะเมนู (Click to Active) */
        .main-dropdown-content {
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.2s ease-in-out;
            pointer-events: none;
        }

        .main-dropdown-content.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
        }

        .submenu-container {
            opacity: 0;
            visibility: hidden;
            transform: translateX(-10px);
            transition: all 0.2s ease-in-out;
            pointer-events: none;
        }

        .submenu-container.active {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
            pointer-events: auto;
        }

        .fade-update {
            animation: fadeIn 0.3s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .mobile-submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .mobile-submenu.open {
            max-height: 500px;
        }
    </style>
</head>
<body class="h-screen w-full flex flex-col font-sans relative">

    <div id="menu-overlay" class="fixed inset-0 z-40 hidden cursor-default"></div>

    <nav class="w-full h-16 glass-panel z-50 flex items-center justify-between px-3 py-4 shrink-0 relative bg-white/80">
        
        <div class="flex items-center gap-3 shrink-0">
            <div class="p-2 md:p-2.5 bg-primary rounded-xl shadow-lg shadow-sky-100 text-white">
                <i data-lucide="settings" size="20"></i>
            </div>
            <div class="">
                <h1 class="font-display font-semibold text-lg leading-tight">Setting System</h1>
                <p class="text-xs text-slate-500 mt-1">ตั้งค่าระบบเบื้องต้น</p>
            </div>
        </div>

        <div class="hidden md:flex items-center flex-1 h-full relative" id="desktop-menu-container">
            <div class="flex items-center text-sm font-medium">

                <div class="relative flex items-center h-16 cursor-pointer" id="dropdown-trigger">
                    <div class="flex items-center hover:bg-gray-50 px-2 py-1.5 rounded-xl transition-colors">
                        <i data-lucide="chevron-right" size="14" class="mx-2 text-gray-300"></i>
                        <span id="breadcrumb-category" class="text-gray-500 font-medium">หมวดหมู่</span>
                        <i data-lucide="chevron-right" size="14" class="mx-2 text-gray-300"></i>
                        <span id="breadcrumb-page" class="text-primary font-bold">เลือกรายการ</span>
                        <i data-lucide="chevron-down" size="14" class="ml-2 text-gray-400 transition-transform duration-300" id="main-chevron"></i>
                    </div>

                    <div id="main-dropdown" class="main-dropdown-content absolute top-[80%] left-0 w-64 bg-white rounded-2xl shadow-dropdown border border-gray-100 py-3 z-50 flex flex-col">
                        <div class="px-4 py-2 mb-2 border-b border-gray-50">
                            <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">เลือกหมวดหมู่</h3>
                        </div>
                        <div id="nested-menu-container"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 shrink-0 ml-4">
            <button id="mobile-toggle-btn" class="md:hidden p-2 text-primary hover:bg-blue-50 rounded-lg transition-colors">
                <i data-lucide="chevron-down" size="24"></i>
            </button>
        </div>
    </nav>

    <div id="mobile-menu-overlay" class="fixed top-16 left-0 w-full bg-white/95 backdrop-blur-xl border-b border-gray-200 shadow-xl z-40 transform -translate-y-[150%] transition-transform duration-500 md:hidden overflow-y-auto max-h-[85vh]">
        <div class="p-2 space-y-2" id="mobile-menu-content"></div>
    </div>

    <main class="flex-1 flex flex-col overflow-hidden relative w-full max-w-full mx-auto">
        <div class="w-full flex-1 overflow-hidden relative">
            
            <iframe id="content-frame" name="content-frame" class="w-full h-full border-0" src="about:blank"></iframe>

            <div id="placeholder-content" class="absolute inset-0 flex flex-col items-center justify-center text-center p-10 bg-white">
                <div class="w-20 h-20 bg-blue-50 rounded-3xl flex items-center justify-center mb-6 text-primary shadow-inner">
                    <i id="placeholder-icon" data-lucide="settings-2" size="40"></i>
                </div>
                <h3 class="text-xl font-display font-semibold text-darkBlue mb-2">เข้าสู่ระบบตั้งค่า</h3>
                <p class="text-gray-500 text-sm max-w-xs leading-relaxed">กรุณาเลือกหัวข้อที่ต้องการจัดการผ่านเมนูนำทางด้านบน</p>
            </div>
        </div>
    </main>

    <script>
        // --- 1. Configuration Data ---
        const menuConfig = [
            {
                category: "ตั้งค่าข้อมูลพื้นฐาน",
                id: "basic_setup",
                icon: "database",
                items: [
                    { label: "ตั้งค่าผู้ใช้งาน", href: "settings_user.php", id: "users" },
                    // { label: "กำหนดสิทธิ์ผู้ใช้งาน", href: "settings_roles.php", id: "roles" },
                    // { label: "กำหนดสิทธิ์การอนุมัติ", href: "settings_approvals.php", id: "approvals" },
                    { label: "ข้อมูลพื้นที่หน่วยงาน", href: "settings_agency.php", id: "agencys" }
                ]
            },
            {
                category: "อุปกรณ์เครื่องจักร",
                id: "equip_setup",
                icon: "drill",
                items: [
                    { label: "ประเภทอุปกรณ์", href: "settings_equip_types.php", id: "equip_types" },
                    { label: "รายการอุปกรณ์", href: "settings_equip_list.php", id: "equip_list" }
                ]
            },
            {
                category: "แจ้งซ่อม",
                id: "repair_setup",
                icon: "wrench",
                items: [
                    { label: "ประเภทงาน", href: "settings_job_type.php", id: "job_types" },
                    { label: "ชนิดการให้บริการ", href: "settings_system_types.php", id: "sys_types" },
                    { label: "กำหนดระยะเวลา SLA", href: "settings_job_sla.php", id: "job_sla" },
                    { label: "ตั้งค่าเพิ่มเติม", href: "settings_job_other.php", id: "job_other" },
                    // { label: "ประเภทสาเหตุ", href: "settings_cause_types.php", id: "cause_types" },
                    // { label: "ช่องทางการรับแจ้ง", href: "settings_channels.php", id: "channels" }
                ]
            },
            {
                category: "บันทึกมิเตอร์ น้ำ/ไฟ",
                id: "meter_setup",
                icon: "gauge",
                items: [
                    { label: "รายการมิเตอร์", href: "settings_meters.php", id: "meters" },
                    { label: "ช่วงเวลาที่บันทึก", href: "settings_meters_time.php", id: "meter_time" }
                ]
            },
            {
                category: "อะไหล่และวัสดุ",
                id: "spares_setup",
                icon: "box",
                items: [
                    { label: "รายการอะไหล่และวัสดุ", href: "settings_spares.php", id: "spares" },
                    { label: "คลังสินค้า / ตึก", href: "settings_warehouses.php", id: "warehouses" },
                ]
            },
            {
                category: "การประเมินผล PM",
                id: "pm_feedback_setup",
                icon: "star",
                items: [
                    { label: "รายการหัวข้อประเมิน", href: "settings_pm_feedback.php", id: "pm_feedback" },
                ]
            }
        ];

        // --- 2. Element References ---
        const nestedMenuContainer = document.getElementById('nested-menu-container');
        const mobileMenuContent = document.getElementById('mobile-menu-content');
        const contentFrame = document.getElementById('content-frame');
        const placeholder = document.getElementById('placeholder-content');
        const mobileToggleBtn = document.getElementById('mobile-toggle-btn');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const breadcrumbCategory = document.getElementById('breadcrumb-category');
        const breadcrumbPage = document.getElementById('breadcrumb-page');
        
        const dropdownTrigger = document.getElementById('dropdown-trigger');
        const mainDropdown = document.getElementById('main-dropdown');
        const mainChevron = document.getElementById('main-chevron');
        const menuOverlay = document.getElementById('menu-overlay');

        // --- 3. Render Functions ---

        function renderMenus() {
            // Render Desktop Nested Menu
            nestedMenuContainer.innerHTML = menuConfig.map(group => `
                <div class="category-item relative px-2" onclick="toggleSubmenu(event, '${group.id}')">
                    <button class="w-full flex items-center justify-between px-3 py-2.5 text-sm font-medium text-gray-600 hover:text-primary hover:bg-blue-50/50 rounded-xl transition-all duration-200">
                        <div class="flex items-center gap-3">
                            <i data-lucide="${group.icon}" size="16" class="text-gray-400"></i>
                            <span>${group.category}</span>
                        </div>
                        <i data-lucide="chevron-right" size="14" class="text-gray-300"></i>
                    </button>

                    <div id="submenu-${group.id}" class="submenu-container absolute top-[-10px] left-full ml-1 w-64 bg-white rounded-2xl shadow-dropdown border border-gray-100 py-3 z-50 flex flex-col">
                        <div class="px-4 py-1.5 mb-2 border-b border-gray-50">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">${group.category}</span>
                        </div>
                        ${group.items.map(item => `
                            <button onclick="handleNavigation(event, '${item.href}', '${item.label}', '${group.category}', '${item.id}')" 
                               class="nav-item w-full text-left px-4 py-2.5 text-sm text-gray-500 hover:text-primary hover:bg-blue-50/50 flex items-center gap-3 transition-colors"
                               data-id="${item.id}">
                               <span class="w-1.5 h-1.5 rounded-full bg-gray-200 nav-dot"></span>
                               ${item.label}
                            </button>
                        `).join('')}
                    </div>
                </div>
            `).join('');

            // Render Mobile Menu
            mobileMenuContent.innerHTML = menuConfig.map(group => `
                <div class="border-b border-gray-100 last:border-0 pb-2">
                    <button class="w-full flex items-center justify-between p-2 text-left font-semibold text-darkBlue hover:bg-gray-50 rounded-xl transition-colors" onclick="toggleMobileSubmenu('${group.id}')">
                        <div class="flex items-center gap-3">
                             <i data-lucide="${group.icon}" size="18" class="text-primary"></i>
                             ${group.category}
                        </div>
                        <i data-lucide="chevron-down" size="18" class="text-gray-400 transform transition-transform duration-300" id="icon-${group.id}"></i>
                    </button>
                    <div id="mobile-sub-${group.id}" class="mobile-submenu pl-6 space-y-1">
                         ${group.items.map(item => `
                            <button onclick="handleNavigation(event, '${item.href}', '${item.label}', '${group.category}', '${item.id}')" 
                               class="nav-item w-full text-left p-3 text-sm text-gray-500 hover:text-primary hover:bg-blue-50 rounded-lg flex items-center gap-3 transition-colors">
                               <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                               ${item.label}
                            </button>
                        `).join('')}
                    </div>
                </div>
            `).join('');

            lucide.createIcons();
        }

        // --- 4. Interactive Logic ---

        function closeAllMenus() {
            mainDropdown.classList.remove('active');
            mainChevron.classList.remove('rotate-180');
            document.querySelectorAll('.submenu-container').forEach(sub => sub.classList.remove('active'));
            menuOverlay.classList.add('hidden'); // ซ่อน Overlay
        }

        dropdownTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isActive = mainDropdown.classList.contains('active');
            
            if (!isActive) {
                closeAllMenus();
                mainDropdown.classList.add('active');
                mainChevron.classList.add('rotate-180');
                menuOverlay.classList.remove('hidden'); // แสดง Overlay เพื่อดักคลิกทุกที่รวมถึง iframe
            } else {
                closeAllMenus();
            }
        });

        function toggleSubmenu(event, groupId) {
            event.stopPropagation();
            const targetSub = document.getElementById(`submenu-${groupId}`);
            const isAlreadyActive = targetSub.classList.contains('active');

            document.querySelectorAll('.submenu-container').forEach(sub => {
                if (sub !== targetSub) sub.classList.remove('active');
            });

            if (!isAlreadyActive) {
                targetSub.classList.add('active');
            } else {
                targetSub.classList.remove('active');
            }
        }

        // คลิก Overlay เพื่อปิดเมนู
        menuOverlay.addEventListener('click', closeAllMenus);

        // คลิกพื้นที่สีขาวในเมนูหลัก ไม่ให้ปิด
        mainDropdown.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // --- 5. Navigation Logic ---

        function handleNavigation(event, url, label, category, itemId) {
            if(event) event.stopPropagation();

            placeholder.classList.add('hidden');

            breadcrumbCategory.classList.remove('fade-update');
            breadcrumbPage.classList.remove('fade-update');
            void breadcrumbCategory.offsetWidth; 
            
            breadcrumbCategory.textContent = category;
            breadcrumbPage.textContent = label;
            
            breadcrumbCategory.classList.add('fade-update');
            breadcrumbPage.classList.add('fade-update');

            document.querySelectorAll('.nav-dot').forEach(dot => dot.classList.replace('bg-secondary', 'bg-gray-200'));
            document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('text-primary', 'font-semibold', 'bg-blue-50'));

            document.querySelectorAll(`[data-id="${itemId}"]`).forEach(item => {
                item.classList.add('text-primary', 'font-semibold', 'bg-blue-50');
                const dot = item.querySelector('.nav-dot');
                if(dot) dot.classList.replace('bg-gray-200', 'bg-secondary');
            });

            closeAllMenus();
            toggleMobileMenu(false);

            try {
                if (window.parent && window.parent !== window) {
                    const newHash = "#settings.php#" + url;
                    window.parent.history.replaceState(null, null, newHash);
                } else {
                    window.history.replaceState(null, null, "#" + url);
                }
            } catch (e) {
                console.warn("URL Sync Error", e);
            }

            contentFrame.src = url;
        }

        function toggleMobileSubmenu(groupId) {
            const submenu = document.getElementById(`mobile-sub-${groupId}`);
            const icon = document.getElementById(`icon-${groupId}`);
            if (submenu.classList.contains('open')) {
                submenu.classList.remove('open');
                icon.classList.remove('rotate-180');
            } else {
                document.querySelectorAll('.mobile-submenu').forEach(el => el.classList.remove('open'));
                document.querySelectorAll('[id^="icon-"]').forEach(el => el.classList.remove('rotate-180'));
                submenu.classList.add('open');
                icon.classList.add('rotate-180');
            }
        }

        function toggleMobileMenu(forceState = null) {
            const isOpen = !mobileMenuOverlay.classList.contains('-translate-y-[150%]');
            const shouldOpen = forceState !== null ? forceState : !isOpen;
            if (shouldOpen) {
                mobileMenuOverlay.classList.remove('-translate-y-[150%]');
                mobileToggleBtn.innerHTML = '<i data-lucide="x" size="24"></i>';
            } else {
                mobileMenuOverlay.classList.add('-translate-y-[150%]');
                mobileToggleBtn.innerHTML = '<i data-lucide="chevron-down" size="24"></i>';
            }
            lucide.createIcons();
        }

        mobileToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleMobileMenu();
        });

        renderMenus();

        window.addEventListener('blur', function() {
            if (mainDropdown && mainDropdown.classList.contains('active')) {
                closeAllMenus();
            }
        });
        
        (function initApp() {
            let targetUrl = null;
            try {
                let hash = window.location.hash;
                if (window.parent && window.parent !== window) hash = window.parent.location.hash; 
                const hashParts = hash.split('#');
                if (hashParts.length >= 3) targetUrl = hashParts[2];
            } catch (e) {}

            let activeGroup = menuConfig[0];
            let activeItem = activeGroup.items[0];

            if (targetUrl) {
                for (const group of menuConfig) {
                    const foundItem = group.items.find(item => item.href === targetUrl);
                    if (foundItem) { activeGroup = group; activeItem = foundItem; break; }
                }
            }
            handleNavigation(null, activeItem.href, activeItem.label, activeGroup.category, activeItem.id);
        })();

    </script>
</body>
</html>