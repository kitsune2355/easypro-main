<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการสต็อก - Modern Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-enterprise/dist/ag-grid-enterprise.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Noto Sans Thai', 'sans-serif'],
                        display: ['Kanit', 'sans-serif'],
                    },
                    colors: {
                        primary: '#006B9F',
                        secondary: '#04ADFF',
                        darkBlue: '#004a6f',
                    },
                    boxShadow: {
                        'glass': '0 8px 32px 0 rgba(0, 107, 159, 0.1)',
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --color-primary: #006B9F; 
            --color-secondary: #04ADFF;
            --ag-accent-color: #006B9F;
            --ag-header-background-color: #f8fafc;
            --ag-row-hover-color: #f1f5f9;
            --ag-selected-row-color: #e2e8f0;
            --ag-font-family: 'Kanit', sans-serif;
            --ag-font-size: 13px;
        }
        body { font-family: 'Kanit', sans-serif; background-color: #F8FAFC; }

        .btn-gradient {
            background: linear-gradient(135deg, #006B9F, #04ADFF);
            color: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-gradient:hover {
            box-shadow: 0 8px 20px -5px rgba(0, 107, 159, 0.4);
            transform: translateY(-2px);
        }
        
        .ag-theme-alpine {
            --ag-border-radius: 12px;
            --ag-font-size: 13px;
            --ag-grid-size: 3px;
            border: none !important;
        }
        .ag-group-expanded, .ag-group-contracted {
            transform: scale(0.8);
        }
        .ag-root-wrapper { border: 1px solid #e2e8f0 !important; }
        
        .drawer-transition { transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .drawer-hidden { transform: translateX(100%); }
        
        .sidebar-item-active { 
            background-color: #f0f9ff !important; 
            color: var(--color-primary) !important;
        }

        #selection-bar {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .selection-bar-visible { transform: translate(-50%, 0); opacity: 1; }
        .selection-bar-hidden { transform: translate(-50%, 100px); opacity: 0; }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        @media (max-width: 1023px) {
            #main-sidebar { display: none; }
        }
        
        .label-xs { font-size: 10px; font-weight: bold; color: #94a3b8; text-transform: uppercase; display: block; margin-bottom: 4px; }
        .input-base { width: 100%; padding: 10px 16px; border: 1px solid #e2e8f0; border-radius: 12px; outline: none; transition: all 0.2s; }
        .input-base:focus { ring: 2px solid rgba(14, 165, 233, 0.2); border-color: #38bdf8; }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <header class="shrink-0 flex flex-col md:flex-row justify-between items-center px-3 py-4 border-b border-slate-200 bg-white/80 backdrop-blur-md sticky top-0 z-1">
        <div class="flex items-center gap-3 w-full md:w-auto mb-4 md:mb-0">
            <div class="p-2 md:p-2.5 bg-primary rounded-xl shadow-lg shadow-sky-100 text-white transition-transform hover:scale-105">
                <i data-lucide="package" class="w-5 h-5 md:w-6 md:h-6 text-white"></i>
            </div>
            <div class="flex flex-col">
                <h1 class="text-base md:text-lg font-display font-bold text-darkBlue leading-none">Stock System</h1>
                <p class="text-xs md:text-sm text-slate-500">ระบบจัดการสต็อก</p>
            </div>
        </div>

        <div class="w-full md:w-auto flex gap-2">
            <button onclick="DrawerManager.open('stock')" class="flex-1 md:pl-6 pr-4 py-2.5 btn-gradient rounded-xl flex items-center justify-center gap-2 text-sm font-bold shadow-md shadow-sky-100">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span class="hidden md:inline">เพิ่มสินค้าใหม่</span>
                <span class="md:hidden">เพิ่ม</span>
            </button>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden relative">
        <aside id="main-sidebar" class="hidden lg:flex w-72 border-r border-slate-200 flex-col bg-white">
            <div class="px-4 pt-4 pb-2">
                <button id="header-toggle-btn" onclick="AppController.toggleMainMode()" 
                    class="w-full flex items-center justify-center gap-2 px-3 py-2 bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-xl text-xs font-bold transition-all border border-slate-100 shadow-sm group">
                    <span id="header-toggle-text" class="truncate">ดูรายงานและประวัติ</span>
                </button>
            </div>

            <div id="sidebar-search-container" class="p-3 space-y-2">
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="sidebar-search" onkeyup="AppController.filterWHMenu()" placeholder="ค้นหาชื่อคลัง..." 
                        class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-secondary/50 outline-none transition-all">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto pb-4">
                <nav class="space-y-1" id="main-nav">
                    <div id="sidebar-stock-group">
                        <div class="px-4 mb-2">
                            <p class="text-[10px] font-bold text-slate-400 uppercase px-2">เมนูหลัก</p>
                        </div>
                        <button onclick="AppController.selectMenu('all')" id="menu-all" class="menu-item w-full flex items-center gap-3 px-6 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all border-r-4 border-transparent sidebar-item-active">
                            <i data-lucide="layout-grid" class="w-4 h-4"></i>
                            แสดงรายการสินค้าทั้งหมด
                        </button>
                        
                        <div class="px-4 mt-6 mb-2 flex justify-between items-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase px-2">คลังสินค้า / ตึก</p>
                            <button onclick="DrawerManager.open('wh')" class="p-1 hover:bg-slate-100 rounded text-slate-400 hover:text-[#006B9F]" title="เพิ่มคลัง/ตึก ใหม่"><i data-lucide="plus" class="w-3.5 h-3.5"></i></button>
                        </div>
                        <div id="warehouse-menu-list"></div>
                    </div>

                    <div id="sidebar-report-group" class="hidden">
                        <div class="px-4 mb-2">
                            <p class="text-[10px] font-bold text-slate-400 uppercase px-2">รายงาน & ประวัติ</p>
                        </div>
                        <button onclick="AppController.selectMenu('report-po')" id="menu-report-po" class="menu-item w-full flex items-center gap-3 px-6 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all border-r-4 border-transparent">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            รายงานรับเข้า (PO)
                        </button>
                        <button onclick="AppController.selectMenu('report-adjust')" id="menu-report-adjust" class="menu-item w-full flex items-center gap-3 px-6 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all border-r-4 border-transparent">
                            <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                            รายงานปรับยอด
                        </button>
                    </div>
                </nav>
            </div>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white border-b border-slate-200 px-4 py-4 flex flex-col md:flex-row justify-between md:items-center shrink-0 gap-4">
                <div class="flex items-center gap-2 group relative">
                    <div class="flex items-center gap-2">
                        <div class="lg:hidden flex items-center gap-1 bg-slate-100 px-3 py-1.5 rounded-xl cursor-pointer hover:bg-slate-200 transition-colors" onclick="AppController.toggleMobileDropdown()">
                            <h2 id="current-view-title" class="text-lg font-bold text-slate-800">สินค้าทั้งหมด</h2>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-slate-500"></i>
                        </div>
                        <h2 id="desktop-view-title" class="hidden lg:block text-xl font-bold text-slate-800 uppercase tracking-tight">สินค้าทั้งหมด</h2>
                        <span id="items-count-badge" class="px-2 py-0.5 bg-slate-100 text-slate-500 text-[10px] font-bold rounded-full">0</span>
                    </div>

                    <div id="mobile-wh-dropdown" class="hidden absolute top-full left-0 mt-2 w-64 bg-white border border-slate-200 rounded-2xl shadow-2xl z-50 overflow-hidden py-2">
                        <div id="mobile-stock-group">
                            <div class="h-px bg-slate-100 my-1"></div>
                            <div id="mobile-wh-list"></div>
                            <div class="h-px bg-slate-100 my-1"></div>
                            <button onclick="DrawerManager.open('wh'); AppController.toggleMobileDropdown();" class="w-full text-left px-4 py-3 text-sm font-bold text-primary hover:bg-blue-50 flex items-center gap-3">
                                <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มคลังใหม่
                            </button>
                        </div>

                        <div id="mobile-report-group" class="hidden">
                            <button onclick="AppController.selectMenu('report-po'); AppController.toggleMobileDropdown();" class="w-full text-left px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-3">
                                <i data-lucide="file-text" class="w-4 h-4"></i> รายงานรับเข้า (PO)
                            </button>
                            <button onclick="AppController.selectMenu('report-adjust'); AppController.toggleMobileDropdown();" class="w-full text-left px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-3">
                                <i data-lucide="clipboard-check" class="w-4 h-4"></i> รายงานปรับยอด
                            </button>
                        </div>

                        <div class="h-px bg-slate-100 my-1"></div>
                        <button onclick="AppController.toggleMainMode(); AppController.toggleMobileDropdown();" class="w-full text-left px-4 py-3 text-xs font-bold text-amber-600 hover:bg-amber-50 flex items-center gap-3">
                            <i data-lucide="shuffle" class="w-4 h-4"></i> 
                            <span id="mobile-toggle-text">สลับไปดูรายงาน</span>
                        </button>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-hidden flex flex-col p-4 bg-slate-50/30">
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm flex flex-col h-full relative">
                    <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row justify-between gap-4">
                        <div class="relative w-full max-w-sm">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                            <input type="text" id="grid-search" placeholder="ค้นหารหัส หรือ ชื่อสินค้า..." oninput="AppController.onFilterChanged()"
                                class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-sky-500/20 transition-all text-sm font-medium">
                        </div>
                    </div>

                    <div id="mainGrid" class="ag-theme-alpine flex-1 w-full font-medium"></div>

                    <div id="selection-bar" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[3] bg-slate-900/95 backdrop-blur-md text-white px-4 py-3 md:px-6 rounded-2xl md:rounded-full shadow-2xl flex flex-col md:flex-row items-center gap-3 md:gap-6 selection-bar-hidden w-[90%] md:w-auto transition-all duration-300">
                        <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-start border-b border-slate-700 pb-2 md:border-none md:pb-0">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center font-bold text-sm shadow-lg shadow-primary/20" id="selected-count">0</div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] text-slate-400 leading-tight uppercase tracking-wider">รายการสินค้า</span>
                                    <span class="text-xs font-bold leading-tight">เลือกแล้ว</span>
                                </div>
                            </div>
                            <button onclick="AppController.clearSelection()" class="md:hidden text-xs font-bold text-slate-400 px-2 py-1">
                                ยกเลิก
                            </button>
                        </div>
                        
                        <div class="hidden md:block h-6 w-px bg-slate-700"></div>
                        
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <button onclick="DrawerManager.open('receive')" class="flex-1 md:flex-none px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-all active:scale-95">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                รับเข้า
                            </button>
                            <button onclick="DrawerManager.open('adjust')" class="flex-1 md:flex-none px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-all active:scale-95">
                                <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                                ปรับยอด
                            </button>
                            <button onclick="AppController.clearSelection()" class="hidden md:block px-3 py-2 hover:bg-white/10 rounded-xl text-xs font-bold transition-colors">
                                ยกเลิก
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="drawerOverlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden z-[3] transition-opacity duration-300 opacity-0" onclick="DrawerManager.close()"></div>
    <div id="sideDrawer" class="fixed top-0 right-0 h-full w-full md:w-[500px] bg-white z-[5] drawer-transition drawer-hidden shadow-2xl flex flex-col">
        <div class="p-4 border-b flex justify-between items-center bg-white sticky top-0">
            <div class="flex items-center gap-3">
                <div id="drawerIconBox" class="w-10 h-10 rounded-full bg-primary flex items-center justify-center text-white shadow-lg">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 id="drawerTitle" class="text-base font-display font-bold text-slate-800">หัวข้อ</h3>
                    <p id="drawerSubtitle" class="text-[10px] text-slate-400 uppercase tracking-wider mt-0.5">รายละเอียด</p>
                </div>
            </div>
            <button onclick="DrawerManager.close()" class="w-10 h-10 rounded-full border border-slate-100 flex items-center justify-center text-slate-400 hover:rotate-90 hover:bg-slate-50 transition-all duration-300">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div id="drawerBody" class="flex-1 overflow-y-auto p-6 bg-slate-50/30"></div>
        <div class="flex-none bg-white border-t px-6 py-4 flex items-center justify-end gap-3">
            <button id="btnSubmit" onclick="DrawerManager.submit()" class="btn-gradient px-8 py-2.5 rounded-xl font-bold shadow-lg shadow-sky-100 flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span>บันทึกข้อมูล</span>
            </button>
        </div>
    </div>

    <script src="js/location-selector.js"></script>
    <script>
        // --- DATA & STATE ---
        const State = {
            currentMode: 'stock', // stock | report
            selectedMenuId: 'all',
            categories: ['วัสดุสิ้นเปลือง', 'เครื่องมือช่าง', 'อะไหล่ประปา', 'อุปกรณ์ไฟฟ้า', 'อื่นๆ'],
            items: [
                { id: 1, name: 'ก๊อกน้ำทองเหลือง 1/2"', model: 'Premium-X', description: 'ทนทาน ไม่เป็นสนิม', code: 'PLB-001', whId: 1, whName: 'คลังประปา', stock: 15, total: 20, unit: 'ตัว', min: 5, price: 120, isActive: true },
                { id: 2, name: 'หลอดไฟ LED 18W Philips', model: 'Premium-X', description: 'ทนทาน ไม่เป็นสนิม', code: 'ELE-042', whId: 2, whName: 'คลังไฟฟ้า', stock: 3, total: 50, unit: 'หลอด', min: 10, price: 85, isActive: true },
                { id: 3, name: 'เทปพันเกลียว 10ม.', model: 'Premium-X', description: 'ทนทาน ไม่เป็นสนิม', code: 'MIS-010', whId: 1, whName: 'คลังประปา', stock: 50, total: 100, unit: 'ม้วน', min: 20, price: 15, isActive: true },
                { id: 4, name: 'สายไฟ THW 1x1.5', model: 'Premium-X', description: 'ทนทาน ไม่เป็นสนิม', code: 'ELE-105', whId: 2, whName: 'คลังไฟฟ้า', stock: 80, total: 200, unit: 'เมตร', min: 50, price: 12, isActive: true }
            ],
            warehouses: [
                { id: 1, name: 'คลังประปา', location: 'ตึก A ชั้น 1' },
                { id: 2, name: 'คลังไฟฟ้า', location: 'ตึก A ชั้น 2' },
                { id: 3, name: 'คลังแอร์', location: 'ตึก C หลังตึก' }
            ],
            reports: {
                po: [
                    // PO ใบที่ 1 มี 2 รายการ
                    { id: 101, date: '2024-05-20', poNo: 'PO-2024-001', name: 'ก๊อกน้ำทองเหลือง', qty: 50, unit: 'ตัว', user: 'Admin' },
                    { id: 103, date: '2024-05-20', poNo: 'PO-2024-001', name: 'เทปพันเกลียว', qty: 20, unit: 'ม้วน', user: 'Admin' },
                    
                    // PO ใบที่ 2
                    { id: 102, date: '2024-05-21', poNo: 'PO-2024-002', name: 'หลอดไฟ LED', qty: 100, unit: 'หลอด', user: 'Staff A' }
                ],
                adjust: [
                    { id: 201, date: '2024-05-22', name: 'สายไฟ THW', oldQty: 80, newQty: 75, diff: -5, remark: 'ชำรุดจากการขนย้าย', user: 'Admin' }
                ]
            }
        };

        let gridApi;

        // --- GRID CONFIGURATIONS ---
        const GridConfig = {
            // โหมดสต็อกสินค้า
            getStockCols: () => [
                { 
                    headerName: 'รายการสินค้า', field: 'name', 
                    checkboxSelection: true, headerCheckboxSelection: true,
                    flex: 1, minWidth: 250, filter: true,
                    cellRenderer: p => {
                        return `
                            <div class="flex flex-col py-2 leading-tight">
                                <span class="font-bold text-slate-800">${p.value}</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <span class="text-[9px] px-1.5 py-0.5 bg-slate-100 text-slate-500 font-bold rounded uppercase">${p.data.code}</span>
                                    ${p.data.model ? `<span class="text-[9px] px-1.5 py-0.5 bg-purple-50 text-purple-600 font-bold rounded">${p.data.model}</span>` : ''}
                                </div>
                            </div>`;
                    }
                },
                { 
                    headerName: 'คลังสินค้า', 
                    field: 'whName', 
                    width: 150,
                    filter: true,
                    cellClass: 'flex items-center',
                    valueFormatter: p => p.value
                },
                { headerName: 'รายละเอียด', field: 'description', flex: 1, minWidth: 200, cellClass: 'flex items-center' },
                { 
                    headerName: 'ราคา (บาท)', field: 'price', width: 150,
                    cellClass: 'flex items-center text-right font-bold text-slate-600',
                    valueFormatter: p => p.value ? p.value.toLocaleString() : '0'
                },
                { 
                    headerName: 'สต็อก', width: 140,
                    cellRenderer: p => {
                        const isLow = p.data.stock <= p.data.min;
                        const percent = Math.min((p.data.stock / p.data.total) * 100, 100);
                        return `
                            <div class="w-full flex flex-col justify-center h-full">
                                <div class="flex justify-between items-end mb-1">
                                    <span class="text-[11px] ${isLow ? 'text-rose-600 font-bold' : 'text-slate-700 font-bold'}">${p.data.stock} / ${p.data.total}</span>
                                    <span class="text-[8px] text-slate-400 font-bold uppercase">${p.data.unit}</span>
                                </div>
                                <div class="w-full bg-slate-100 h-1 rounded-full overflow-hidden">
                                    <div class="h-full ${isLow ? 'bg-rose-500' : 'bg-emerald-500'}" style="width: ${percent}%"></div>
                                </div>
                            </div>`;
                    }
                },
                { 
                    headerName: 'สถานะ', 
                    field: 'isActive', 
                    width: 100,
                    cellClass: 'flex items-center justify-center',
                    cellRenderer: p => {
                        const id = p.data.id;
                        const checked = p.value ? 'checked' : '';
                        return `
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" ${checked} onchange="AppController.toggleItemStatus(${id}, this.checked)">
                                <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                            </label>
                        `;
                    }
                },
                {
                    headerName: '', width: 50, pinned: 'right',
                    cellRenderer: p => `
                        <div class="flex items-center justify-center h-full">
                            <button onclick="DrawerManager.open('stock', ${p.data.id})" class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-primary transition-all flex items-center justify-center">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                        </div>`
                }
            ],
            // โหมดรายงาน PO
            getReportPoCols: () => [
                { 
                    headerName: 'เลขที่ PO / วันที่',
                    rowGroup: true,
                    hide: true,
                    valueGetter: params => {
                        if (!params.data) return '';
                        return `${params.data.poNo} | ${params.data.date}`;
                    }
                },
                { headerName: 'รายการสินค้า', field: 'name', flex: 1 },
                { 
                    headerName: 'จำนวน', field: 'qty', width: 200, 
                    aggFunc: 'sum', // รวมผลรวมจำนวนสินค้าใน PO นั้นๆ
                    cellClass: 'text-center font-bold text-emerald-600',
                    valueFormatter: p => p.value ? `+${p.value.toLocaleString()}` : ''
                },
                { headerName: 'หน่วย', field: 'unit', width: 120 },
                { headerName: 'หมายเหตุ', field: 'remark', flex: 1, cellClass: 'text-slate-500 italic' },
                { headerName: 'ผู้รับ', field: 'user', width: 120 }
            ],
            // โหมดรายงาน Adjust
            getReportAdjustCols: () => [
                { headerName: 'วันที่', field: 'date', width: 120 },
                { headerName: 'รายการสินค้า', field: 'name', flex: 1 },
                { headerName: 'ยอดเก่า', field: 'oldQty', width: 120, cellClass: 'text-center text-slate-400' },
                { headerName: 'ยอดใหม่', field: 'newQty', width: 120, cellClass: 'text-center font-bold text-slate-800' },
                { 
                    headerName: 'ส่วนต่าง', field: 'diff', width: 100,
                    cellStyle: p => ({ color: p.value > 0 ? '#10b981' : '#f43f5e', fontWeight: 'bold', textAlign: 'center' }),
                    valueFormatter: p => p.value > 0 ? `+${p.value}` : p.value
                },
                { headerName: 'หมายเหตุ', field: 'remark', flex: 1, cellClass: 'text-slate-500 italic' }
            ]
        };

        // --- CONTROLLER ---
        const AppController = {
            init: () => {
                AppController.renderWHMenu();
                AppController.initGrid();
                lucide.createIcons();
                
                // Click outside mobile menu
                window.addEventListener('click', (e) => {
                    const dd = document.getElementById('mobile-wh-dropdown');
                    if (!e.target.closest('.group') && dd && !dd.classList.contains('hidden')) {
                        dd.classList.add('hidden');
                    }
                });
            },

            initGrid: () => {
                const gridDiv = document.querySelector('#mainGrid');
                gridApi = agGrid.createGrid(gridDiv, {
                    columnDefs: GridConfig.getStockCols(),
                    rowData: State.items,
                    rowHeight: 60,
                    headerHeight: 45,
                    rowSelection: 'multiple',
                    suppressRowClickSelection: true,
                    pagination: true,
                    paginationPageSize: 10,
                    paginationPageSizeSelector: [10, 20, 50, 100],
                    onGridReady: () => {
                        AppController.updateDashboard();
                        lucide.createIcons();
                    },
                    getRowClass: params => {
                        if (params.data && params.data.isActive === false) {
                            return 'opacity-50 grayscale-[0.5]';
                        }
                    },
                    onSelectionChanged: AppController.onSelectionChanged
                });
            },

            toggleItemStatus: (id, isActive) => {
                const itemIndex = State.items.findIndex(item => item.id === id);
                if (itemIndex !== -1) {
                    State.items[itemIndex].isActive = isActive;
                    
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    });
                    
                    Toast.fire({
                        icon: 'info',
                        title: `ปรับสถานะเป็น: ${isActive ? 'เปิดใช้งาน' : 'ปิดการใช้งาน'}`
                    });
                }
            },

            renderWHMenu: () => {
                const desktopList = document.getElementById('warehouse-menu-list');
                const mobileList = document.getElementById('mobile-wh-list');
                const mobileReportGroup = document.getElementById('mobile-report-group');
                
                // --- ส่วนที่ 1: จัดการปุ่ม "ทั้งหมด" (Mobile) ---
                const isAllActive = State.selectedMenuId === 'all';
                const allActiveMobileClass = isAllActive ? 'bg-blue-50 text-primary font-bold' : 'text-slate-700 font-semibold';
                
                let mobileHtml = `
                    <button onclick="AppController.selectMenu('all'); AppController.toggleMobileDropdown();" 
                        class="mobile-menu-item w-full text-left px-4 py-3 text-sm flex items-center gap-3 border-b border-slate-50 transition-colors ${allActiveMobileClass}">
                        <i data-lucide="layout-grid" class="w-4 h-4"></i> ทั้งหมด
                    </button>
                `;

                // --- ส่วนที่ 2: จัดการรายการคลังสินค้า (Mobile & Desktop) ---
                const generateHtml = (isMobile) => State.warehouses.map(wh => {
                    const count = State.items.filter(i => i.whId == wh.id).length;
                    const isActive = State.selectedMenuId == wh.id;

                    if (isMobile) {
                        return `
                        <div class="flex items-center border-b border-slate-100 ${isActive ? 'bg-blue-50/50' : ''}">
                            <button onclick="AppController.selectMenu(${wh.id}); AppController.toggleMobileDropdown();" 
                                    class="flex-1 text-left px-4 py-4 text-sm ${isActive ? 'text-primary font-bold' : 'text-slate-600 font-medium'} flex items-center justify-between transition-colors">
                                <span class="truncate pr-2 font-semibold text-[15px]">${wh.name}</span>
                            </button>
                            
                            <div class="relative px-3 flex items-center h-full">
                                <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full shrink-0">${count}</span>

                                <button onclick="AppController.toggleWhActionMenu(event, ${wh.id}, true)" class="p-2 text-slate-400 hover:text-primary">
                                    <i data-lucide="more-vertical" class="w-5 h-5"></i>
                                </button>
                                
                                <div id="wh-action-mb-${wh.id}" class="hidden absolute right-2 top-12 w-40 bg-white border border-slate-200 rounded-xl shadow-2xl z-[9999]">
                                    <div class="py-1">
                                        <button onclick="DrawerManager.open('wh', ${wh.id}); AppController.toggleMobileDropdown();" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-slate-600 active:bg-slate-50 border-b border-slate-50">
                                            <i data-lucide="edit-3" class="w-4 h-4 text-blue-500"></i> แก้ไข
                                        </button>
                                        <button onclick="AppController.deleteWarehouse(${wh.id}, '${wh.name}')" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-rose-500 active:bg-rose-50">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i> ลบออก
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    } else {
                        const activeClass = isActive ? 'bg-blue-50 text-primary border-primary' : 'border-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700';
                        return `
                        <div class="group flex items-center w-full border-l-4 transition-all ${activeClass} relative">
                            <button onclick="AppController.selectMenu(${wh.id})" class="flex-1 flex items-center gap-3 pl-5 py-3 text-[13.5px] font-bold overflow-hidden uppercase tracking-wide">
                                <i data-lucide="building-2" class="w-4 h-4 shrink-0 opacity-70"></i>
                                <span class="truncate">${wh.name}</span>
                            </button>
                            
                            <div class="flex items-center gap-1 pr-2 relative">
                                <span class="text-[10px] px-2 py-0.5 bg-slate-200/50 text-slate-500 rounded-lg font-bold">${count}</span>
                                
                                <button onclick="AppController.toggleWhActionMenu(event, ${wh.id}, false)" class="group-hover:flex p-1.5 text-slate-400 hover:text-primary">
                                    <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                </button>
                                
                                <div id="wh-action-${wh.id}" class="hidden absolute right-0 top-10 w-32 bg-white border border-slate-200 rounded-xl shadow-xl z-[3]">
                                    <div class="py-1">
                                        <button onclick="DrawerManager.open('wh', ${wh.id})" class="w-full flex items-center gap-2 px-3 py-2 text-[12px] font-bold text-slate-600 hover:bg-slate-50 border-b border-slate-50">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5 text-blue-500"></i> แก้ไข
                                        </button>
                                        <button onclick="AppController.deleteWarehouse(${wh.id}, '${wh.name}')" class="w-full flex items-center gap-2 px-3 py-2 text-[12px] font-bold text-rose-500 hover:bg-rose-50">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> ลบออก
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    }
                }).join('');

                // --- ส่วนที่ 3: จัดการกลุ่มรายงาน (Mobile Report Group) ---
                const isPoActive = State.selectedMenuId === 'report-po';
                const isAdjActive = State.selectedMenuId === 'report-adjust';

                mobileReportGroup.innerHTML = `
                    <button onclick="AppController.selectMenu('report-po'); AppController.toggleMobileDropdown();" 
                        class="w-full text-left px-4 py-3 text-sm flex items-center gap-3 transition-colors ${isPoActive ? 'bg-blue-50 text-primary font-bold' : 'text-slate-700 font-semibold'}">
                        <i data-lucide="file-text" class="w-4 h-4"></i> รายงานรับเข้า (PO)
                    </button>
                    <button onclick="AppController.selectMenu('report-adjust'); AppController.toggleMobileDropdown();" 
                        class="w-full text-left px-4 py-3 text-sm flex items-center gap-3 transition-colors ${isAdjActive ? 'bg-blue-50 text-primary font-bold' : 'text-slate-700 font-semibold'}">
                        <i data-lucide="clipboard-check" class="w-4 h-4"></i> รายงานปรับยอด
                    </button>
                `;

                // อัปเดต HTML ลงในรายการ
                mobileList.innerHTML = mobileHtml + generateHtml(true);
                desktopList.innerHTML = generateHtml(false);
                
                lucide.createIcons();
            },

            // ใส่ไว้ใน AppController
            toggleWhActionMenu: (event, id, isMobile) => {
                event.stopPropagation();
                
                const targetId = isMobile ? `wh-action-mb-${id}` : `wh-action-${id}`;
                const targetMenu = document.getElementById(targetId);
                
                if (!targetMenu) return;
                document.querySelectorAll('[id^="wh-action-"]').forEach(el => {
                    if (el.id !== targetId) el.classList.add('hidden');
                });

                const isOpening = targetMenu.classList.toggle('hidden');

                if (!isOpening) {
                    lucide.createIcons();
                    
                    const close = (e) => {
                        if (!targetMenu.contains(e.target)) {
                            targetMenu.classList.add('hidden');
                            document.removeEventListener('click', close);
                        }
                    };
                    setTimeout(() => document.addEventListener('click', close), 10);
                }
            },

            deleteWarehouse: (id, name) => {
                const itemsInWh = State.items.filter(i => i.whId == id).length;
                
                if (itemsInWh > 0) {
                    Swal.fire({
                        title: 'ไม่สามารถลบได้',
                        text: `คลัง "${name}" ยังมีสินค้าอยู่ ${itemsInWh} รายการ`,
                        icon: 'warning',
                        confirmButtonColor: '#006B9F'
                    });
                    return;
                }

                Swal.fire({
                    title: 'ยืนยันการลบ?',
                    text: `คุณกำลังจะลบคลัง "${name}"`,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonText: 'ยืนยันการลบ',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#ef4444',
                }).then((result) => {
                    if (result.isConfirmed) {
                        State.warehouses = State.warehouses.filter(w => w.id !== id);
                        if (State.selectedMenuId == id) State.selectedMenuId = 'all';
                        
                        AppController.renderWHMenu();
                        AppController.selectMenu(State.selectedMenuId);
                        Swal.fire({ icon: 'success', title: 'ลบสำเร็จ', timer: 800, showConfirmButton: false });
                    }
                });
            },

            selectMenu: (id) => {
                State.selectedMenuId = id;
                AppController.renderWHMenu();
                document.querySelectorAll('.menu-item').forEach(b => b.classList.remove('sidebar-item-active'));

                let titleText = '';
                let newCols = [];
                let rowData = [];
                let autoGroupDef = null; 
                let groupExpanded = 0;

                // เตรียมข้อมูลสินค้าแบบ Flattened (ตามโค้ดเดิมของคุณ)
                const flattenedItems = [];
                State.items.forEach(item => {
                    if (Array.isArray(item.whId)) {
                        item.whId.forEach(whId => {
                            const wh = State.warehouses.find(w => w.id == whId);
                            flattenedItems.push({ ...item, displayWhId: whId, whName: wh ? wh.name : 'Unknown' });
                        });
                    } else {
                        flattenedItems.push({ ...item, displayWhId: item.whId });
                    }
                });

                if (gridApi) gridApi.deselectAll();

                // --- จัดการเงื่อนไขแยกตาม ID ---
                if (id === 'report-po' || id === 'report-adjust') {
                    const isPo = id === 'report-po';
                    if (isPo) {
                        document.getElementById('menu-report-po').classList.add('sidebar-item-active');
                        titleText = 'รายงานการรับเข้า (PO)';
                        newCols = GridConfig.getReportPoCols();
                        rowData = State.reports.po;
                    } else {
                        document.getElementById('menu-report-adjust').classList.add('sidebar-item-active');
                        titleText = 'รายงานการปรับยอดสต็อก';
                        newCols = GridConfig.getReportAdjustCols();
                        rowData = State.reports.adjust;
                    }

                    // ตั้งค่า Group สำหรับรายงาน (ใช้ได้ทั้ง PO และ Adjust ถ้ามีการตั้ง rowGroup ใน Config)
                    autoGroupDef = {
                        headerName: 'ข้อมูลอ้างอิง',
                        minWidth: 250,
                        cellRendererParams: {
                            suppressCount: true,
                            innerRenderer: params => {
                                const value = params.value;
                                if (value && value.includes('|')) {
                                    const [ref, dt] = value.split('|');
                                    return `<span class="text-blue-600 font-semibold">${ref.trim()}</span> 
                                            <span class="text-gray-500 ml-2">${dt.trim()}</span>`;
                                }
                                return value;
                            }
                        }
                    };
                    groupExpanded = -1;

                    // --- ปรับให้เล็กและกระชับเฉพาะโหมดรายงาน ---
                    if (gridApi) {
                        gridApi.setGridOption('rowHeight', 32); 
                        gridApi.setGridOption('headerHeight', 35);
                        gridApi.setGridOption('groupDisplayType', 'singleColumn');
                    }

                } else {
                    // --- โหมดสต็อกสินค้า (ขนาดปกติ) ---
                    if (id === 'all') {
                        document.getElementById('menu-all').classList.add('sidebar-item-active');
                        titleText = 'สินค้าทั้งหมด';
                        rowData = flattenedItems;
                    } else {
                        const menuBtn = document.getElementById(`menu-wh-${id}`);
                        if (menuBtn) menuBtn.classList.add('sidebar-item-active');
                        const wh = State.warehouses.find(w => w.id == id);
                        titleText = wh ? wh.name : 'Unknown';
                        rowData = flattenedItems.filter(i => i.displayWhId == id);
                    }
                    newCols = GridConfig.getStockCols();

                    if (gridApi) {
                        gridApi.setGridOption('rowHeight', 60); // กลับเป็นขนาดใหญ่
                        gridApi.setGridOption('headerHeight', 45); // กลับเป็นขนาดใหญ่
                        gridApi.setGridOption('groupDisplayType', null); // ปิดการแสดงผล Group แบบรายงาน
                    }
                }

                // อัปเดต Grid
                if (gridApi) {
                    gridApi.setGridOption('columnDefs', newCols);
                    gridApi.setGridOption('rowData', rowData);
                    gridApi.setGridOption('autoGroupColumnDef', autoGroupDef);
                    gridApi.setGridOption('groupDefaultExpanded', groupExpanded);
                    gridApi.setGridOption('quickFilterText', '');
                }

                document.getElementById('current-view-title').innerText = titleText;
                document.getElementById('desktop-view-title').innerText = titleText;
                document.getElementById('items-count-badge').innerText = rowData.length;
                setTimeout(() => lucide.createIcons(), 50);
            },

            toggleMainMode: () => {
                const isStock = State.currentMode === 'stock';
                State.currentMode = isStock ? 'report' : 'stock';
                
                const els = {
                    btn: document.getElementById('header-toggle-btn'),
                    text: document.getElementById('header-toggle-text'),
                    mobileText: document.getElementById('mobile-toggle-text'),
                    stockGroups: [document.getElementById('sidebar-stock-group'), document.getElementById('mobile-stock-group')],
                    reportGroups: [document.getElementById('sidebar-report-group'), document.getElementById('mobile-report-group')],
                    search: document.getElementById('sidebar-search-container')
                };

                if (State.currentMode === 'report') {
                    els.text.innerText = 'กลับหน้าสต็อกหลัก';
                    els.mobileText.innerText = 'กลับหน้าสต็อกหลัก';
                    els.btn.className = els.btn.className.replace('bg-slate-50', 'bg-amber-50 text-amber-700 border-amber-100');
                    els.stockGroups.forEach(e => e.classList.add('hidden'));
                    els.reportGroups.forEach(e => e.classList.remove('hidden'));
                    els.search.classList.add('hidden');
                    AppController.selectMenu('report-po');
                } else {
                    els.text.innerText = 'ดูรายงานและประวัติ';
                    els.mobileText.innerText = 'สลับไปดูรายงาน';
                    els.btn.className = els.btn.className.replace('bg-amber-50 text-amber-700 border-amber-100', 'bg-slate-50');
                    els.reportGroups.forEach(e => e.classList.add('hidden'));
                    els.stockGroups.forEach(e => e.classList.remove('hidden'));
                    els.search.classList.remove('hidden');
                    AppController.selectMenu('all');
                }
            },

            updateDashboard: () => {
                // Logic อัปเดตตัวเลขอื่นๆ ถ้ามี
            },

            onSelectionChanged: () => {
                const count = gridApi.getSelectedRows().length;
                document.getElementById('selected-count').innerText = count;
                const bar = document.getElementById('selection-bar');
                if (count > 0) {
                    bar.classList.remove('selection-bar-hidden');
                    bar.classList.add('selection-bar-visible');
                } else {
                    bar.classList.remove('selection-bar-visible');
                    bar.classList.add('selection-bar-hidden');
                }
            },
            
            clearSelection: () => gridApi.deselectAll(),
            
            toggleMobileDropdown: () => document.getElementById('mobile-wh-dropdown').classList.toggle('hidden'),
            
            onFilterChanged: () => gridApi.setGridOption('quickFilterText', document.getElementById('grid-search').value),

            filterWHMenu: () => {
                const val = document.getElementById('sidebar-search').value.toLowerCase();
                // Logic ซ่อน/แสดง เมนูคลังสินค้าตาม Search
                // (เพื่อให้โค้ดกระชับ ส่วนนี้สามารถปรับใช้ renderWHMenu โดยส่ง filter ไปได้)
                AppController.renderWHMenu(); // แบบง่าย: re-render (ใน production ควรซ่อน element)
            }
        };

        // --- DRAWER & FORM MANAGER ---
        const DrawerManager = {
            mode: null,
            editingId: null,
            searchTimer: null,
            
            handleQuickSearch: function(query) {
                const resultsContainer = document.getElementById('search-results');
                if (!query || query.length < 2) {
                    resultsContainer.innerHTML = '';
                    resultsContainer.classList.add('hidden');
                    return;
                }

                // ล้าง Timer เก่า (Debounce 300ms)
                clearTimeout(this.searchTimer);
                this.searchTimer = setTimeout(() => {
                    // ค้นหาทั้งจาก Code และ Name
                    const matches = State.items.filter(item => 
                        item.code.toLowerCase().includes(query.toLowerCase()) || 
                        item.name.toLowerCase().includes(query.toLowerCase())
                    ).slice(0, 5); // แสดงแค่ 5 รายการแรก

                    if (matches.length > 0) {
                        resultsContainer.innerHTML = matches.map(item => `
                            <div onclick="DrawerManager.selectSearchResult('${item.code}')" 
                                class="px-4 py-3 hover:bg-slate-50 cursor-pointer border-b border-slate-100 last:border-0 flex justify-between items-center">
                                <div>
                                    <div class="text-sm font-bold text-slate-700">${item.name}</div>
                                    <div class="text-xs text-slate-400">${item.code}</div>
                                </div>
                                <div class="text-xs font-medium text-primary bg-primary/5 px-2 py-1 rounded">
                                    ฿${item.price}
                                </div>
                            </div>
                        `).join('');
                        resultsContainer.classList.remove('hidden');
                    } else {
                        resultsContainer.innerHTML = '<div class="p-4 text-sm text-slate-400 text-center">ไม่พบสินค้าที่ตรงกัน</div>';
                        resultsContainer.classList.remove('hidden');
                    }
                }, 300);
            },

            // เมื่อคลิกเลือกจากรายการ
            selectSearchResult: function(code) {
                document.getElementById('f-code').value = code;
                document.getElementById('search-results').classList.add('hidden');
                this.searchProduct(code); // เรียกฟังก์ชันเดิมเพื่อเติมข้อมูลลงฟอร์ม
            },
            open: (mode, id = null) => {
                DrawerManager.mode = mode;
                DrawerManager.editingId = id;
                
                const els = {
                    overlay: document.getElementById('drawerOverlay'),
                    drawer: document.getElementById('sideDrawer'),
                    body: document.getElementById('drawerBody'),
                    title: document.getElementById('drawerTitle'),
                    sub: document.getElementById('drawerSubtitle'),
                    icon: document.getElementById('drawerIconBox')
                };

                els.overlay.classList.remove('hidden');
                setTimeout(() => els.overlay.classList.add('opacity-100'), 10);
                els.drawer.classList.remove('drawer-hidden');

                // Set Icon Color
                const colors = { receive: 'bg-emerald-500', adjust: 'bg-amber-500', stock: 'bg-primary', wh: 'bg-primary' };
                els.icon.className = `w-10 h-10 rounded-full flex items-center justify-center text-white shadow-lg ${colors[mode] || 'bg-primary'}`;

                // Render Content
                if (mode === 'stock') {
                    DrawerManager.renderStockForm(id, els);
                    DrawerManager.refreshWhTags();
                }
                else if (mode === 'receive') DrawerManager.renderReceiveForm(els);
                else if (mode === 'adjust') DrawerManager.renderAdjustForm(els);
                else if (mode === 'wh') DrawerManager.renderWHForm(els);

                lucide.createIcons();
            },

            close: () => {
                const overlay = document.getElementById('drawerOverlay');
                overlay.classList.remove('opacity-100');
                document.getElementById('sideDrawer').classList.add('drawer-hidden');
                setTimeout(() => overlay.classList.add('hidden'), 300);
            },

            // เพิ่มต่อท้ายภายใน DrawerManager Object
            toggleWhSelection: (id, name) => {
                const input = document.getElementById('f-wh-values');
                let selectedIds = JSON.parse(input.value);
                
                if (selectedIds.includes(id)) {
                    selectedIds = selectedIds.filter(item => item !== id);
                } else {
                    selectedIds.push(id);
                }
                
                input.value = JSON.stringify(selectedIds);
                DrawerManager.refreshWhTags();
                // Re-render dropdown เพื่ออัปเดตเครื่องหมายถูก (Optional: เพื่อความลื่นไหล)
                DrawerManager.renderWhDropdownContent(); 
            },

            refreshWhTags: () => {
                const container = document.getElementById('multi-wh-container');
                const placeholder = document.getElementById('wh-placeholder');
                const selectedIds = JSON.parse(document.getElementById('f-wh-values').value);
                
                // ล้าง Tags เก่า (ยกเว้น placeholder)
                const existingTags = container.querySelectorAll('.wh-tag');
                existingTags.forEach(t => t.remove());

                if (selectedIds.length === 0) {
                    placeholder.classList.remove('hidden');
                } else {
                    placeholder.classList.add('hidden');
                    selectedIds.forEach(id => {
                        const wh = State.warehouses.find(w => w.id == id);
                        if (wh) {
                            const tag = document.createElement('div');
                            tag.className = 'wh-tag flex items-center gap-1 bg-slate-100 text-slate-700 px-2 py-1 rounded-lg text-xs font-bold border border-slate-200 animate-in fade-in zoom-in duration-200';
                            tag.innerHTML = `${wh.name} <i data-lucide="x" class="w-3 h-3 cursor-pointer hover:text-rose-500" onclick="event.stopPropagation(); DrawerManager.toggleWhSelection(${wh.id})"></i>`;
                            container.appendChild(tag);
                        }
                    });
                    lucide.createIcons();
                }
            },

            renderWhDropdownContent: () => {
                const selectedIds = JSON.parse(document.getElementById('f-wh-values').value);
                const dropdown = document.getElementById('wh-dropdown');
                dropdown.innerHTML = State.warehouses.map(w => {
                    const isChecked = selectedIds.includes(w.id);
                    return `
                    <div class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 rounded-lg cursor-pointer transition-colors" onclick="DrawerManager.toggleWhSelection(${w.id}, '${w.name}')">
                        <div class="w-4 h-4 border-2 rounded flex items-center justify-center transition-all ${isChecked ? 'bg-primary border-primary' : 'border-slate-300'}">
                            <i data-lucide="check" class="w-3 h-3 text-white ${isChecked ? '' : 'hidden'}"></i>
                        </div>
                        <span class="text-sm ${isChecked ? 'text-primary font-bold' : 'text-slate-600'}">${w.name}</span>
                    </div>`;
                }).join('');
                lucide.createIcons();
            },

            searchProduct: function(code) {
                const foundItem = State.items.find(item => item.code === code);
                if (foundItem) {
                    // เติมข้อมูลลงฟอร์ม
                    document.getElementById('f-name').value = foundItem.name || '';
                    document.getElementById('f-category').value = foundItem.category || '';
                    document.getElementById('f-price').value = foundItem.price || 0;
                    document.getElementById('f-unit').value = foundItem.unit || 'ชิ้น';
                    document.getElementById('f-model').value = foundItem.model || '';
                    document.getElementById('f-description').value = foundItem.description || '';
                    document.getElementById('f-total').value = foundItem.total || 0;
                    document.getElementById('f-min').value = foundItem.min || 0;

                    // จัดการ Multi-Warehouse
                    let whSelected = Array.isArray(foundItem.whId) ? foundItem.whId : [foundItem.whId];
                    const whValuesInput = document.getElementById('f-wh-values');
                    if (whValuesInput) {
                        whValuesInput.value = JSON.stringify(whSelected);
                        this.refreshWhTags();
                        this.renderWhDropdownContent();
                    }

                    // แจ้งเตือนแบบ Toast (มุมขวาบน)
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true
                    });
                    Toast.fire({
                        icon: 'success',
                        title: `ดึงข้อมูล: ${foundItem.name}`
                    });
                }
            },

            renderStockForm: (id, els) => {
                els.title.innerText = id ? 'แก้ไขข้อมูลสินค้า' : 'เพิ่มสินค้าใหม่';
                els.sub.innerText = 'ระบุรายละเอียดพัสดุและคลังจัดเก็บ';
                const item = id ? State.items.find(i => i.id === id) : { 
                    name: '', code: '', category: '', whId: (State.selectedMenuId === 'all' ? 1 : State.selectedMenuId), 
                    stock: 0, total: 0, min: 5, unit: 'ชิ้น', price: 0 
                };
                // ตรวจสอบว่า whId เดิมเป็นค่าเดี่ยวหรือ Array เพื่อความยืดหยุ่น
                const currentWhIds = Array.isArray(item.whId) ? item.whId : (item.whId ? [item.whId] : []);
                const isEdit = !!id;

                const posIntAttrs = `type="number" min="0" oninput="this.value = Math.abs(Math.floor(this.value))"`;

                els.body.innerHTML = `
                    <div class="space-y-5">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="relative group">
                                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">รหัสสินค้า</label>
                                <div class="relative">
                                    <input type="text" id="f-code" value="${item.code || ''}" 
                                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-primary/10 focus:border-primary transition-all" 
                                        placeholder="รหัสสินค้า..."
                                        oninput="DrawerManager.handleQuickSearch(this.value)"
                                        autocomplete="off">
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                                        <i data-lucide="search" class="w-4 h-4"></i>
                                    </div>
                                </div>
                                
                                <div id="search-results" class="absolute z-[60] left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl overflow-hidden hidden">
                                    </div>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">ประเภทสินค้า</label>
                                <select id="f-category" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20 bg-white cursor-pointer">
                                    <option value="">-- เลือกประเภท --</option>
                                    ${State.categories.map(cat => `
                                        <option value="${cat}" ${item.category === cat ? 'selected' : ''}>${cat}</option>
                                    `).join('')}
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1">
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">จัดเก็บที่คลัง (เลือกได้หลายแห่ง)</label>
                                <div class="relative group">
                                    <div id="multi-wh-container" class="min-h-[42px] w-full px-2 py-1.5 border border-slate-200 rounded-xl bg-white flex flex-wrap gap-1.5 cursor-pointer hover:border-primary transition-all focus-within:ring-2 focus-within:ring-primary/20 focus-within:border-primary" onclick="document.getElementById('wh-dropdown').classList.toggle('hidden')">
                                        <div id="wh-placeholder" class="text-slate-400 text-sm py-1 px-2 ${Array.isArray(item.whId) && item.whId.length > 0 ? 'hidden' : ''}">เลือกคลังสินค้า...</div>
                                    </div>
                                    
                                    <div id="wh-dropdown" class="hidden absolute z-50 w-full mt-1 bg-white border border-slate-200 shadow-xl rounded-xl max-h-48 overflow-y-auto p-1">
                                        ${State.warehouses.map(w => {
                                            const isChecked = Array.isArray(item.whId) ? item.whId.includes(w.id) : (item.whId == w.id);
                                            return `
                                            <div class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 rounded-lg cursor-pointer transition-colors" onclick="DrawerManager.toggleWhSelection(${w.id}, '${w.name}')">
                                                <div class="wh-checkbox w-4 h-4 border-2 rounded flex items-center justify-center transition-all ${isChecked ? 'bg-primary border-primary' : 'border-slate-300'}">
                                                    <i data-lucide="check" class="w-3 h-3 text-white ${isChecked ? '' : 'hidden'}"></i>
                                                </div>
                                                <span class="text-sm ${isChecked ? 'text-primary font-bold' : 'text-slate-600'}">${w.name}</span>
                                            </div>`;
                                        }).join('')}
                                    </div>
                                </div>
                                <input type="hidden" id="f-wh-values" value='${JSON.stringify(Array.isArray(item.whId) ? item.whId : (item.whId ? [item.whId] : []))}'>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">ชื่อสินค้า</label>
                                <input type="text" id="f-name" value="${item.name}" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20" placeholder="ชื่อสินค้า">
                            </div>
                            <div class="col-span-1">
                                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">รุ่น / ชนิด</label>
                                <input type="text" id="f-model" value="${item.model || ''}" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20" placeholder="รุ่น / ชนิด">
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">รายละเอียดสินค้า</label>
                            <textarea id="f-description" rows="2" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20" placeholder="ระบุรายละเอียดเพิ่มเติม...">${item.description || ''}</textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                             <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">ราคา</label>
                                <input ${posIntAttrs} id="f-price" value="${item.price}" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl font-bold text-primary">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">หน่วย</label>
                                <input type="text" id="f-unit" value="${item.unit}" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl">
                            </div>
                        </div>
                        <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-[10px] font-bold text-blue-500 uppercase block mb-1">คงเหลือ</label>
                                <input ${posIntAttrs} id="f-stock" value="${item.stock}" ${isEdit ? 'disabled' : ''} class="w-full px-4 py-2.5 border border-slate-200 rounded-xl font-bold ${isEdit ? 'bg-slate-100' : 'bg-white'}">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-blue-500 uppercase block mb-1">เป้าหมาย</label>
                                <input ${posIntAttrs} id="f-total" value="${item.total}" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl font-bold">
                            </div>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-rose-500 uppercase block mb-1">จุดสั่งซื้อขั้นต่ำ</label>
                            <input ${posIntAttrs} id="f-min" value="${item.min}" class="w-full px-4 py-2.5 border border-rose-200 rounded-xl font-bold text-rose-600 bg-rose-50/20">
                        </div>
                    </div>`;
            },

            renderReceiveForm: (els) => {
                const selected = gridApi.getSelectedRows();
                els.title.innerText = 'ฟอร์มรับสินค้าเข้า';
                els.sub.innerText = `รายการที่เลือก ${selected.length} รายการ`;
                els.body.innerHTML = `
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="label-xs">เลขที่ใบรับเข้า</label><input type="text" id="r-po" class="input-base"></div>
                            <div><label class="label-xs">วันที่</label><input type="date" id="r-date" value="${new Date().toISOString().split('T')[0]}" class="input-base"></div>
                        </div>
                        <div class="space-y-2">
                            ${selected.map(item => `
                                <div class="p-3 bg-white border border-slate-100 rounded-xl flex justify-between items-center">
                                    <div class="truncate pr-2">
                                        <div class="text-xs font-bold text-slate-700">${item.name}</div>
                                        <span class="text-[10px] text-slate-400">${item.code}</span>
                                        <span class="text-[9px] px-1.5 py-0.5 bg-blue-50 text-blue-600 font-bold rounded">${item.whName}</span>
                                    </div>
                                    <div class="flex flex-col items-end shrink-0">
                                        <div class="flex items-center gap-2">
                                            <input type="number" data-id="${item.id}" class="receive-input w-20 px-2 py-2 text-center bg-emerald-50 border border-emerald-100 rounded-xl outline-none font-bold text-emerald-600 focus:ring-2 focus:ring-emerald-500/20" placeholder="+0">
                                            <span class="text-[10px] font-bold text-slate-400 w-8">${item.unit}</span>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">หมายเหตุ</label>
                            <textarea id="r-remark" rows="2" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20" placeholder="หมายเหตุเพิ่มเติม"></textarea>
                        </div>
                    </div>`;
            },
            
            renderAdjustForm: (els) => {
                const selected = gridApi.getSelectedRows();
                els.title.innerText = 'ปรับยอดสต็อก';
                els.sub.innerText = `ตรวจสอบยอดจริง ${selected.length} รายการ`;
                els.body.innerHTML = `
                    <div class="space-y-4">
                        <div class="p-3 bg-amber-50 border border-amber-100 rounded-xl text-[11px] text-amber-700">ระบุจำนวนที่นับได้จริง ระบบจะปรับยอดให้อัตโนมัติ</div>
                        <div class="mb-4">
                            <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">วันที่ตรวจสอบยอด</label>
                            <div class="relative">
                                <i data-lucide="calendar" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                <input type="date" id="adj-date" 
                                    value="${new Date().toISOString().split('T')[0]}" 
                                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-amber-500/20 font-medium text-slate-700">
                            </div>
                        </div>
                        ${selected.map(item => `
                            <div class="p-3 bg-white border border-slate-100 rounded-xl flex justify-between items-center">
                                <div>
                                    <div class="text-xs font-bold text-slate-700">${item.name}</div>
                                    <div class="text-[10px] text-slate-400">ปัจจุบัน: ${item.stock} ${item.unit}</div>
                                </div>
                                <input type="number" data-id="${item.id}" class="adjust-input w-20 px-2 py-1.5 bg-amber-50 border border-amber-100 rounded text-center font-bold text-amber-700" placeholder="${item.stock}">
                            </div>
                        `).join('')}
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">หมายเหตุ</label>
                            <textarea id="adj-remark" rows="2" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20" placeholder="หมายเหตุเพิ่มเติม"></textarea>
                        </div>
                    </div>`;
            },

            renderWHForm: (els) => {
                const id = DrawerManager.editingId;
                const wh = id ? State.warehouses.find(w => w.id == id) : { name: '', location: '' };
                
                els.title.innerText = id ? 'แก้ไขคลังสินค้า' : 'เพิ่มคลังใหม่';
                els.sub.innerText = id ? `แก้ไขข้อมูล ${wh.name}` : 'สร้างสถานที่จัดเก็บ';
                
                els.body.innerHTML = `
                    <div class="space-y-4">
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">ชื่อคลังสินค้า/ตึก</label>
                            <input type="text" id="w-name" value="${wh.name}" placeholder="เช่น คลังไฟฟ้าตึก A" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20">
                        </div>

                        <div class="space-y-4">
                            <div id="loc_trigger" onClick="LocationSelector.openModal()" class="cursor-pointer bg-white border border-slate-200 p-4 rounded-3xl hover:border-sky-400 transition-all">
                                <div id="loc_placeholder" class="${wh.location ? 'hidden' : ''}">
                                    <div class="flex items-center gap-3">
                                        <div class="w-11 h-11 rounded-2xl bg-sky-100 flex items-center justify-center text-sky-600 shrink-0">
                                            <i data-lucide="map-pin" class="w-5 h-5"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-extrabold text-slate-700">ระบุตำแหน่งที่ตั้ง</p>
                                            <p class="text-[10px] text-slate-400 font-semibold">กดเพื่อเลือก อาคาร / สาขา → ชั้น → ห้อง</p>
                                        </div>
                                        <div class="text-slate-300">
                                            <i data-lucide="chevron-right" class="w-5 h-5"></i>
                                        </div>
                                    </div>
                                </div>

                                <div id="loc_selected" class="${wh.location ? '' : 'hidden'}">
                                    <div class="flex flex-col gap-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1.5">
                                                <i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400"></i> Location Info
                                            </span>
                                            <span class="text-[10px] font-extrabold text-emerald-600 bg-emerald-50 border border-emerald-100 rounded-full px-2.5 py-1">Selected</span>
                                        </div>
                                        <span id="disp_building" class="text-sm font-extrabold text-slate-800">${wh.location || ''}</span>
                                    </div>
                                </div>

                                <input type="hidden" name="building" id="in_building" value="${wh.location || ''}">
                                <input type="hidden" name="floor" id="in_floor">
                                <input type="hidden" name="room" id="in_room">
                            </div>
                        </div>
                    </div>
                `;
            },

            submit: () => {
                const { mode, editingId } = DrawerManager;
                let success = false;
                const selectedWhIds = JSON.parse(document.getElementById('f-wh-values').value);
                const selectedWhNames = selectedWhIds.map(id => State.warehouses.find(w => w.id == id)?.name).filter(n => n);

                if (mode === 'stock') {
                    const name = document.getElementById('f-name').value;
                    if(!name) return Swal.fire('Error', 'กรุณาระบุชื่อสินค้า', 'error');

                    const payload = {
                        id: editingId || Date.now(),
                        name,
                        code: document.getElementById('f-code').value,
                        category: document.getElementById('f-category').value,
                        whId: selectedWhIds,
                        whName: selectedWhNames.join(', '),
                        price: parseFloat(document.getElementById('f-price').value) || 0,
                        stock: parseInt(document.getElementById('f-stock').value) || 0,
                        total: parseInt(document.getElementById('f-total').value) || 0,
                        min: parseInt(document.getElementById('f-min').value) || 0,
                        unit: document.getElementById('f-unit').value,
                        model: document.getElementById('f-model').value, 
                        description: document.getElementById('f-description').value
                    };

                    if(editingId) {
                        const idx = State.items.findIndex(i => i.id === editingId);
                        State.items[idx] = { ...State.items[idx], ...payload };
                    } else {
                        State.items.push(payload);
                    }
                    success = true;

                } else if (mode === 'receive') {
                    const remark = document.getElementById('r-remark').value;
                    document.querySelectorAll('.receive-input').forEach(input => {
                        const qty = parseInt(input.value) || 0;
                        if(qty > 0) {
                            const item = State.items.find(i => i.id == input.dataset.id);
                            if(item) {
                                item.stock += qty;
                                State.reports.po.push({
                                    id: Date.now(), 
                                    date: document.getElementById('r-date').value,
                                    poNo: document.getElementById('r-po').value || '-',
                                    name: item.name, 
                                    qty, 
                                    unit: item.unit, 
                                    user: 'Admin',
                                    remark: remark
                                });
                                success = true;
                            }
                        }
                    });
                } else if (mode === 'adjust') {
                    const remark = document.getElementById('adj-remark').value;
                    const adjDate = document.getElementById('adj-date').value;
                    
                    document.querySelectorAll('.adjust-input').forEach(input => {
                        const newQty = input.value;
                        if(newQty !== '') {
                            const item = State.items.find(i => i.id == input.dataset.id);
                            if(item) {
                                const oldQty = item.stock;
                                const finalNewQty = parseInt(newQty);
                                item.stock = finalNewQty;
                                
                                State.reports.adjust.push({
                                    id: Date.now() + Math.random(), 
                                    date: adjDate,
                                    name: item.name, 
                                    oldQty: oldQty, 
                                    newQty: finalNewQty,
                                    diff: finalNewQty - oldQty, 
                                    remark: remark,
                                    user: 'Admin'
                                });
                                success = true;
                            }
                        }
                    });
                } else if (mode === 'wh') {
                    const name = document.getElementById('w-name').value;
                    const location = document.getElementById('in_building').value; // หรือฟิลด์ที่คุณใช้เก็บค่า location
                    
                    if(name) {
                        if(editingId) {
                            // โหมดแก้ไข
                            const idx = State.warehouses.findIndex(w => w.id == editingId);
                            if(idx !== -1) {
                                State.warehouses[idx].name = name;
                                State.warehouses[idx].location = location;
                                
                                // อัปเดตชื่อคลังในรายการสินค้าด้วย (ถ้ามีการเปลี่ยนชื่อ)
                                State.items.forEach(item => {
                                    if(item.whId == editingId) item.whName = name;
                                });
                            }
                        } else {
                            // โหมดเพิ่มใหม่
                            State.warehouses.push({ id: Date.now(), name, location: location });
                        }
                        success = true;
                    }
                }

                if(success) {
                    Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', showConfirmButton: false, timer: 1000 });
                    AppController.renderWHMenu();
                    AppController.selectMenu(State.selectedMenuId); // Refresh Grid
                    DrawerManager.close();
                } else {
                    Swal.fire('Warning', 'ไม่มีการเปลี่ยนแปลงข้อมูล', 'warning');
                }
            }
        };

        window.addEventListener('load', AppController.init);

    </script>
    
</body>
</html>