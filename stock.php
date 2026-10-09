<?php 
@session_start();
include "config_ctrl/checksession.php"; 
?>
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
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
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

        /* ===== มือถือ (< 768px): การ์ดแทนตาราง — ตารางย้ายไปนอกจอ (ยังโหลดข้อมูล/แบ่งหน้า/เลือกแถวได้ตามปกติ) ===== */
        #mStock { display: none; }
        @media (max-width: 767.98px) {
            #mainGrid { position: absolute !important; left: -12000px; top: 0; width: 1000px !important; height: 900px !important; flex: none !important; }
            #mStock { display: flex; }
        }
        .ms-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 1px 2px rgba(15,23,42,.05); }
        .ms-card.is-selected { border-color: #0284c7; box-shadow: 0 0 0 2px rgba(2,132,199,.2); }
        .ms-act { width: 40px; height: 40px; border-radius: .75rem; display: inline-flex; align-items: center; justify-content: center; flex: none; }

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
        .ag-header { border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important; }
        .ag-root-wrapper { border: none !important; background: transparent !important; }
        
        .drawer-transition { transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .drawer-hidden { transform: translateX(100%); }
        
        /* สำหรับเมนูคลังสินค้าบน Desktop (Sidebar) */
        #warehouse-menu-list {
            max-height: calc(100vh - 500px);
            overflow-y: auto;
        }

        /* สำหรับเมนูคลังสินค้าบน Mobile (Dropdown) */
        #mobile-wh-list {
            max-height: 30vh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .sidebar-item-active { 
            background-color: #f0f9ff !important; 
            color: var(--color-primary) !important;
        }

        #selection-bar {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .selection-bar-visible { transform: translate(-50%, 0); opacity: 1; }
        .selection-bar-hidden { display: none !important; }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        @media (max-width: 1023px) {
            #main-sidebar { display: none; }
        }
        /* แก้ไขปัญหามือถือแนวนอน: ปลดล็อคความสูงเมื่อหน้าจอเตี้ยกว่า 500px */
        @media (max-height: 500px) and (max-width: 932px) {
            body {
                height: auto !important;
                overflow: auto !important; /* ยอมให้หน้าเว็บ Scroll แนวตั้งได้ */
            }
            
            main {
                overflow: visible !important;
                height: auto !important;
            }

            /* กำหนดความสูงขั้นต่ำให้ตาราง เพื่อไม่ให้บีบจนหายไป */
            #mainGrid {
                height: 400px !important; 
                min-height: 400px;
            }
        }
        
        .label-xs { font-size: 10px; font-weight: bold; color: #94a3b8; text-transform: uppercase; display: block; margin-bottom: 4px; }
        .input-base { width: 100%; padding: 10px 16px; border: 1px solid #e2e8f0; border-radius: 12px; outline: none; transition: all 0.2s; }
        .input-base:focus { ring: 2px solid rgba(14, 165, 233, 0.2); border-color: #38bdf8; }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <header class="shrink-0 flex flex-col md:flex-row justify-between items-center px-3 py-4 border-b border-slate-200 bg-white/80 backdrop-blur-md">
        <div class="flex items-center gap-3 w-full md:w-auto mb-4 md:mb-0">
            <div class="p-2 md:p-2.5 bg-primary rounded-xl shadow-lg shadow-sky-100 text-white">
                <i data-lucide="package" class="w-5 h-5 md:w-6 md:h-6 text-white"></i>
            </div>
            <div class="flex flex-col">
                <h1 class="text-base md:text-lg font-display font-bold leading-none">Stock System</h1>
                <p class="text-xs md:text-sm text-slate-500">ระบบจัดการสต็อก</p>
            </div>
        </div>

        <div class="w-full md:w-auto flex gap-2">
            <button onClick="DrawerManager.open('stock')" class="flex-1 md:pl-6 pr-4 py-2.5 btn-gradient rounded-xl flex items-center justify-center gap-2 text-sm font-bold shadow-md shadow-sky-100">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span class="hidden md:inline">เพิ่มสินค้าใหม่</span>
                <span class="md:hidden">เพิ่ม</span>
            </button>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden relative">
        <aside id="main-sidebar" class="hidden lg:flex w-72 border-r border-slate-200 flex-col bg-white">
            <div id="sidebar-search-container" class="p-3 space-y-2">
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="sidebar-search" onKeyUp="AppController.filterWHMenu()" placeholder="ค้นหาชื่อคลัง..." 
                        class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-secondary/50 outline-none transition-all">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto pb-4">
                <nav class="space-y-1" id="main-nav">
                    <div id="sidebar-stock-group">
                        <div class="px-4 mb-2">
                            <p class="text-[10px] font-bold text-slate-400 uppercase px-2">เมนูหลัก</p>
                        </div>
                        <button onClick="AppController.selectMenu('all')" id="menu-all" class="menu-item w-full flex items-center gap-3 px-6 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all border-r-4 border-transparent sidebar-item-active">
                            <i data-lucide="layout-grid" class="w-4 h-4"></i>
                            แสดงรายการสินค้าทั้งหมด
                        </button>
                        
                        <div class="px-4 mt-6 mb-2 flex justify-between items-center">
                            <p class="text-[10px] font-bold text-slate-400 uppercase px-2">คลังสินค้า / ตึก</p>
                            <button onClick="DrawerManager.open('wh')" class="p-1 hover:bg-slate-100 rounded text-slate-400 hover:text-[#006B9F]" title="เพิ่มคลัง/ตึก ใหม่"><i data-lucide="plus" class="w-3.5 h-3.5"></i></button>
                        </div>
                        <div id="warehouse-menu-list"></div>
                    </div>

                    <div id="sidebar-report-group">
                        <div class="px-4 mt-6 mb-2">
                            <p class="text-[10px] font-bold text-slate-400 uppercase px-2">รายงาน & ประวัติ</p>
                        </div>
                        <button onClick="AppController.selectMenu('report-po')" id="menu-report-po" class="menu-item w-full flex items-center gap-3 px-6 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all border-r-4 border-transparent">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            รายงานรับเข้า (PO)
                        </button>
                        <button onClick="AppController.selectMenu('report-adjust')" id="menu-report-adjust" class="menu-item w-full flex items-center gap-3 px-6 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all border-r-4 border-transparent">
                            <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                            รายงานปรับยอด (AD)
                        </button>
                    </div>
                </nav>
            </div>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-white border-b border-slate-200 px-4 py-4 flex flex-col md:flex-row justify-between md:items-center shrink-0 gap-4">
                <div class="flex items-center gap-2 group relative">
                    <div class="flex items-center gap-2">
                        <div class="lg:hidden flex items-center gap-1 bg-slate-100 px-3 py-1.5 rounded-xl cursor-pointer hover:bg-slate-200 transition-colors" onClick="AppController.toggleMobileDropdown()">
                            <h2 id="current-view-title" class="text-lg font-bold text-slate-800">สินค้าทั้งหมด</h2>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-slate-500"></i>
                        </div>
                        <h2 id="desktop-view-title" class="hidden lg:block text-xl font-bold text-slate-800 uppercase tracking-tight">สินค้าทั้งหมด</h2>
                        <span id="items-count-badge" class="px-2 py-0.5 bg-slate-100 text-slate-500 text-[10px] font-bold rounded-full">0</span>
                    </div>

                    <div id="mobile-dropdown" class="hidden absolute top-full left-0 mt-2 w-64 bg-white border border-slate-200 rounded-2xl shadow-2xl z-50 py-2">
                        <div id="mobile-stock-group">
                            <div class="p-4 border-b border-slate-100">
                                <div class="relative">
                                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                                    <input type="text" 
                                        id="mobile-sidebar-search" 
                                        oninput="AppController.filterWHMenuMobile()" 
                                        placeholder="ค้นหาคลังสินค้า..." 
                                        class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary/20">
                                </div>
                            </div>
                            <div class="h-px bg-slate-100 my-1"></div>
                            <button onClick="DrawerManager.open('wh'); AppController.toggleMobileDropdown();" class="w-full text-left px-4 py-3 text-sm font-bold text-primary hover:bg-blue-50 flex items-center gap-3">
                                <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มคลังใหม่
                            </button>
                        </div>
                            <div id="mobile-wh-list"></div>

                        <div id="mobile-report-group">
                            <button onClick="AppController.selectMenu('report-po'); AppController.toggleMobileDropdown();" class="w-full text-left px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-3">
                                <i data-lucide="file-text" class="w-4 h-4"></i> รายงานรับเข้า (PO)
                            </button>
                            <button onClick="AppController.selectMenu('report-adjust'); AppController.toggleMobileDropdown();" class="w-full text-left px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-3">
                                <i data-lucide="clipboard-check" class="w-4 h-4"></i> รายงานปรับยอด (AD)
                            </button>
                        </div>
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

                    <!-- มือถือ: การ์ดของแถวในหน้าปัจจุบัน + ปุ่มเปลี่ยนหน้า -->
                    <div id="mStock" class="flex-1 min-h-0 flex-col">
                        <div id="msCards" class="flex-1 min-h-0 overflow-y-auto p-3 flex flex-col gap-2.5 bg-slate-50/60"></div>
                        <div class="flex-none flex items-center justify-between gap-2 px-3 py-2 border-t border-slate-100 bg-white text-[13px] text-slate-600">
                            <button type="button" id="msPrev" class="ms-act border border-slate-200 bg-white disabled:opacity-40" aria-label="หน้าก่อน"><i data-lucide="chevron-left" class="w-5 h-5"></i></button>
                            <span id="msPage" class="text-center">-</span>
                            <button type="button" id="msNext" class="ms-act border border-slate-200 bg-white disabled:opacity-40" aria-label="หน้าถัดไป"><i data-lucide="chevron-right" class="w-5 h-5"></i></button>
                        </div>
                    </div>

                    <div id="selection-bar" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[3] bg-slate-900/95 backdrop-blur-md text-white px-4 py-3 md:px-6 rounded-2xl md:rounded-full shadow-2xl flex flex-col md:flex-row items-center gap-3 md:gap-6 selection-bar-hidden w-[90%] md:w-auto transition-all duration-300">
                        <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-start border-b border-slate-700 pb-2 md:border-none md:pb-0">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center font-bold text-sm shadow-lg shadow-primary/20" id="selected-count">0</div>
                                <div class="flex flex-col">
                                    <span class="text-[10px] text-slate-400 leading-tight uppercase tracking-wider">รายการสินค้า</span>
                                    <span class="text-xs font-bold leading-tight">เลือกแล้ว</span>
                                </div>
                            </div>
                            <button onClick="AppController.clearSelection()" class="md:hidden text-xs font-bold text-slate-400 px-2 py-1">
                                ยกเลิก
                            </button>
                        </div>
                        
                        <div class="hidden md:block h-6 w-px bg-slate-700"></div>
                        
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <button onClick="DrawerManager.open('receive')" class="flex-1 md:flex-none px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-all active:scale-95">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                รับเข้า
                            </button>
                            <button onClick="DrawerManager.open('adjust')" class="flex-1 md:flex-none px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold flex items-center justify-center gap-2 transition-all active:scale-95">
                                <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                                ปรับยอด
                            </button>
                            <button onClick="AppController.clearSelection()" class="hidden md:block px-3 py-2 hover:bg-white/10 rounded-xl text-xs font-bold transition-colors">
                                ยกเลิก
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="drawerOverlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden z-[3] transition-opacity duration-300 opacity-0" onClick="DrawerManager.close()"></div>
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
            <button onClick="DrawerManager.close()" class="w-10 h-10 rounded-full border border-slate-100 flex items-center justify-center text-slate-400 hover:rotate-90 hover:bg-slate-50 transition-all duration-300">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div id="drawerBody" class="flex-1 overflow-y-auto p-6 bg-slate-50/30"></div>
        <div class="flex-none bg-white border-t px-6 py-4 flex items-center justify-end gap-3">
            <button id="btnSubmit" onClick="DrawerManager.submit()" class="btn-gradient px-8 py-2.5 rounded-xl font-bold shadow-lg shadow-sky-100 flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span>บันทึกข้อมูล</span>
            </button>
        </div>
    </div>

    <!-- ===== History Modal ===== -->
    <div id="historyModal" class="fixed inset-0 z-[10] hidden">
        <div id="historyOverlay" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm opacity-0 transition-opacity duration-300" onclick="HistoryManager.close()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-3 md:p-6 pointer-events-none">
            <div id="historyPanel" class="bg-white w-full max-w-4xl h-full md:h-[85vh] rounded-2xl shadow-2xl flex flex-col overflow-hidden pointer-events-auto opacity-0 scale-95 transition-all duration-300">
                <!-- Header -->
                <div class="shrink-0 px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-white">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-secondary/10 text-secondary flex items-center justify-center shrink-0">
                            <i data-lucide="history" class="w-5 h-5"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 id="history-title" class="text-base font-display font-bold text-slate-800 truncate">ประวัติสินค้า</h3>
                            <p id="history-subtitle" class="text-[11px] text-slate-400 truncate">การเคลื่อนไหวสต๊อก รับเข้า / เบิกใช้ / ปรับยอด</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button onclick="HistoryManager.exportExcel()" title="ส่งออก Excel" class="px-3 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-xs font-bold flex items-center gap-1.5 transition-all active:scale-95">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span class="hidden sm:inline">Excel</span>
                        </button>
                        <button onclick="HistoryManager.close()" class="w-9 h-9 rounded-full border border-slate-100 flex items-center justify-center text-slate-400 hover:rotate-90 hover:bg-slate-50 transition-all duration-300">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- Filter bar -->
                <div class="shrink-0 px-5 py-3 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-end gap-3">
                    <div class="flex-1 grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">ตั้งแต่วันที่</label>
                            <input type="date" id="history-start-date" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-secondary/20 bg-white">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">ถึงวันที่</label>
                            <input type="date" id="history-end-date" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-secondary/20 bg-white">
                        </div>
                    </div>
                    <div class="flex items-end gap-2">
                        <button onclick="HistoryManager.applyFilter()" class="px-4 py-2 btn-gradient rounded-xl text-sm font-bold flex items-center gap-1.5">
                            <i data-lucide="filter" class="w-4 h-4"></i> กรอง
                        </button>
                        <button onclick="HistoryManager.clearFilter()" class="px-3 py-2 bg-white border border-slate-200 text-slate-500 rounded-xl text-sm font-bold hover:bg-slate-50">
                            ล้าง
                        </button>
                        <button id="history-sort-btn" onclick="HistoryManager.toggleSort()" title="สลับการเรียงตามวันที่" class="px-3 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-bold hover:bg-slate-50 flex items-center gap-1.5">
                            <span id="history-sort-icon" class="flex items-center"><i data-lucide="arrow-down-wide-narrow" class="w-4 h-4"></i></span>
                            <span id="history-sort-label" class="hidden sm:inline">ล่าสุดก่อน</span>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div id="history-body" class="flex-1 overflow-y-auto px-5 py-4 bg-slate-50/30">
                    <!-- rows injected here -->
                </div>

                <!-- Footer / Pagination -->
                <div class="shrink-0 px-5 py-3 border-t border-slate-100 bg-white flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center gap-2 text-[12px] text-slate-500">
                        <span>แสดง</span>
                        <select id="history-page-size" onchange="HistoryManager.changePageSize()" class="px-2 py-1 border border-slate-200 rounded-lg text-sm bg-white outline-none">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span id="history-range" class="font-medium text-slate-600"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button id="history-prev" onclick="HistoryManager.prevPage()" class="w-9 h-9 rounded-lg border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-left" class="w-4 h-4"></i>
                        </button>
                        <span id="history-page-info" class="text-[12px] font-bold text-slate-600 min-w-[70px] text-center">- / -</span>
                        <button id="history-next" onclick="HistoryManager.nextPage()" class="w-9 h-9 rounded-lg border border-slate-200 flex items-center justify-center text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed">
                            <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // --- DATA & STATE ---
        const State = {
            currentMode: 'stock',
            selectedMenuId: 'all',
            categories: [],
            items: [],
            warehouses: [],
            reports: {
                po: [],
                adjust: []
            }
        };

        const API_WH_STOCK = 'handle_wh_stock.php';
        const API_PRODUCT = 'handle_stock.php';
        const API_REPAIR_SYSTEM = 'handle_repair_system.php';
        const AG_ID = '<?php echo $sess_user_agency_es ?>';
        const USER_ID = '<?php echo $sess_user_id; ?>';
        
        let gridApi;

        const StockAPI = {
            getAllWarehousesAPI: async () => {
                try {
                    const response = await axios.get(`${API_WH_STOCK}?action=get_all&ag_id=${AG_ID}`);
                    if (response.data.success) {
                        State.warehouses = response.data.data;
                        AppController.renderWHMenu();
                    }
                } catch (error) {
                    console.error('Error fetching warehouses:', error);
                }
            },

            addWarehouseAPI: async (wh_name) => {
                try {
                    const response = await axios.post(`${API_WH_STOCK}?action=add`, {
                        ag_id: AG_ID,
                        wh_name: wh_name,
                        user_id: USER_ID
                    });
                    // สำคัญ: ต้อง return ข้อมูลกลับไป เพื่อให้ฟังก์ชัน submit รู้ผลลัพธ์
                    return response.data; 
                } catch (error) {
                    return { success: false, message: 'เกิดข้อผิดพลาดในการเชื่อมต่อ' };
                }
            },

            updateWarehouseAPI: async (id, wh_name) => {
                try {
                    const response = await axios.post(`${API_WH_STOCK}?action=update`, {
                        id: id,
                        ag_id: AG_ID,
                        wh_name: wh_name,
                        user_id: USER_ID
                    });
                    // สำคัญ: ต้อง return ข้อมูลกลับไป
                    return response.data;
                } catch (error) {
                    return { success: false, message: 'เกิดข้อผิดพลาดในการเชื่อมต่อ' };
                }
            },

            deleteWarehouseAPI: async (id) => {
                try {
                    const response = await axios.post(`${API_WH_STOCK}?action=delete`, { id: id });
                    if (response.data.success) {
                        Swal.fire('ลบสำเร็จ', response.data.message, 'success');
                        StockAPI.getAllWarehousesAPI();
                    }
                } catch (error) {
                    Swal.fire('ผิดพลาด', 'ลบข้อมูลไม่สำเร็จ', 'error');
                }
            },
            getAllCategoriesAPI: async () => {
				try {
					const response = await axios.get(`${API_REPAIR_SYSTEM}?action=get_all&ag_id=${AG_ID}`);
					if (response.data.success) {
						State.categories = response.data.data;
					}
				} catch (error) {
					console.error('Error fetching categories:', error);
				}
			},
            getAllProductsAPI: async (whId = null, search = '', startRow = 0, endRow = 10, sortModel = [], filterModel = {}) => {
                try {
                    let url = `handle_stock.php?action=get_all&ag_id=${AG_ID}`;
                    if (whId) url += `&wh_id=${whId}`;
                    if (search) url += `&search=${encodeURIComponent(search)}`;
                    
                    // เพิ่ม Pagination และ Metadata
                    url += `&startRow=${startRow}`;
                    url += `&endRow=${endRow}`;
                    url += `&sortModel=${JSON.stringify(sortModel)}`;
                    url += `&filterModel=${JSON.stringify(filterModel)}`;

                    const response = await axios.get(url);
                    State.items = response.data.data;
                    return response.data; // คืนค่า data object ทั้งหมด
                } catch (error) {
                    console.error('Error:', error);
                    return { success: false, data: [] };
                }
            },

            // ฟังก์ชันตรวจสอบและสร้างรหัสอัตโนมัติจาก Server
            checkInfoAndGenCode: async (rps_id, name) => {
                try {
                    const response = await axios.get(`${API_PRODUCT}?action=check_info&rps_id=${rps_id}&name=${name}&ag_id=${AG_ID}`);
                    return response.data;
                } catch (error) {
                    console.error(error);
                    return { exists: false, code: '' };
                }
            },

            // ฟังก์ชันบันทึกสินค้า (Insert / Update)
            saveProductAPI: async (productData) => {
                try {
                    const action = productData.pd_id ? 'update' : 'save_product';
                    const payload = {
                        ...productData,
                        ag_id: AG_ID,
                        user_id: USER_ID
                    };
                    
                    const response = await axios.post(`${API_PRODUCT}?action=${action}`, payload);
                    return response.data;
                } catch (error) {
                    console.error('Save Error:', error);
                    return { success: false, message: error.message };
                }
            },

            async updateStatusAPI(psId, newStatus) {
                try {
                    const response = await axios.post('handle_stock.php?action=update_status', {
                        ps_id: psId,
                        ps_status: newStatus 
                    });
                    return response.data;
                } catch (error) {
                    console.error('Update status error:', error);
                    return { success: false, message: 'การเชื่อมต่อเซิร์ฟเวอร์ผิดพลาด' };
                }
            },

            getReportAPI: async (type, limit, offset, search = '') => {
                try {
                    const response = await axios.get(`${API_PRODUCT}?action=get_report`, {
                        params: { 
                            type: type, 
                            ag_id: AG_ID,
                            limit: limit,
                            offset: offset,
                            search: search
                        }
                    });
                    return response.data;
                } catch (error) {
                    console.error('Error fetching report:', error);
                    return { success: false, data: [], total: 0 };
                }
            },
        };


        // --- GRID CONFIGURATIONS ---
        const GridConfig = {
            // โหมดสต็อกสินค้า
            getStockCols: () => [
                { headerName: 'รหัสสินค้า', field: 'pd_gen_code', hide: true },
                { headerName: 'ชื่อสินค้า', field: 'pd_details_head', hide: true },
                { 
                    headerName: 'รายการสินค้า', field: 'items', 
                    checkboxSelection: true, headerCheckboxSelection: true,
                    flex: 1, minWidth: 250,
                    getQuickFilterText: params => {
                        return `${params.data.pd_details_head} ${params.data.pd_gen_code}`;
                    },
                    cellRenderer: p => {
                        return `
                            <div class="flex flex-col py-2 leading-tight">
                                <span class="font-bold text-slate-800">${p.data.pd_details_head}</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <span class="text-[9px] px-1.5 py-0.5 bg-slate-100 text-slate-500 font-bold rounded uppercase">${p.data.pd_gen_code}</span>
                                    ${p.data.pd_model ? `<span class="text-[9px] px-1.5 py-0.5 bg-purple-50 text-purple-600 font-bold rounded">${p.data.pd_model}</span>` : ''}
                                </div>
                            </div>`;
                    }
                },
                {
                    headerName: 'คลังสินค้า',
                    field: 'wh_name',
                    width: 150,
                    filter: 'agSetColumnFilter',
                    filterParams: {
                        values: async (params) => {
                            try {
                                const response = await axios.get(`${API_WH_STOCK}?action=get_all&ag_id=${AG_ID}`);
                                if (response.data.success) {
                                    const names = response.data.data.map(wh => wh.wh_name);
                                    params.success(names);
                                }
                            } catch (error) {
                                console.error('Error loading filter values', error);
                                params.success([]);
                            }
                        },
                        refreshValuesOnOpen: true 
                    },
                    cellClass: 'flex items-center font-bold text-primary',
                    valueFormatter: params => params.data ? params.data.wh_name : ''
                },
                { 
                    headerName: 'ประเภท', 
                    field: 'rps_name', 
                    filter: 'agSetColumnFilter',
                    filterParams: {
                        values: async (params) => {
                            try {
                                const response = await axios.get(`${API_REPAIR_SYSTEM}?action=get_all&ag_id=${AG_ID}`);
                                if (response.data.success) {
                                    const names = response.data.data.map(item => item.rps_name);
                                    params.success(names);
                                }
                            } catch (error) {
                                console.error('Error loading filter values', error);
                                params.success([]);
                            }
                        },
                        refreshValuesOnOpen: true 
                    },
                 },
                { headerName: 'รายละเอียด', field: 'pd_details', flex: 1, minWidth: 200, cellClass: 'flex items-center' },
                { 
                    headerName: 'ราคา (บาท)', field: 'pd_price', width: 150,
                    cellClass: 'flex items-center justify-end text-slate-600',
                    valueFormatter: p => p.value ? parseFloat(p.value).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0.00'
                },
                { headerName: 'หน่วย', field: 'pd_unit', hide: true },
                { headerName: 'คงเหลือ', field: 'pd_qty', hide: true },
                { headerName: 'จำนวนสินค้าทั้งหมด', field: 'pd_qty_all', hide: true },
                { headerName: 'จุดสั่งซื้อขั้นต่ำ', field: 'pd_qty_min', hide: true },
                { 
                    headerName: 'สต็อก', 
                    width: 160,
                    cellRenderer: p => {
                        const stock = parseInt(p.data.pd_qty) || 0; // คงเหลือ
                        const total = parseInt(p.data.pd_qty_all) || 0; // เป้าหมาย
                        const min = parseInt(p.data.pd_qty_min) || 0; // จุดสั่งซื้อขั้นต่ำ
                        
                        const isLow = stock <= min;
                        const percent = total > 0 ? Math.min((stock / total) * 100, 100) : 0;
                        
                        return `
                            <div class="w-full flex flex-col justify-center h-full">
                                <div class="flex justify-between items-end mb-1">
                                    <span class="text-[11px] ${isLow ? 'text-rose-600 font-bold' : 'text-slate-700 font-bold'}">
                                        ${stock.toLocaleString()} / ${total.toLocaleString()}
                                    </span>
                                    <span class="text-[8px] text-slate-400 font-bold uppercase">${p.data.pd_unit || ''}</span>
                                </div>
                                <div class="w-full bg-slate-100 h-1 rounded-full overflow-hidden">
                                    <div class="h-full ${isLow ? 'bg-rose-500' : 'bg-emerald-500'}" style="width: ${percent}%"></div>
                                </div>
                            </div>`;
                    }
                },
                {
                    headerName: "สถานะ",
                    field: "ps_status",
                    width: 140,
                    pinned: 'right',
                    cellClass: 'flex items-center justify-center',
                    cellRenderer: params => {
                        const isActive = params.value === '0';
                        const isChecked = isActive ? 'checked' : '';
                        const statusText = isActive ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
                        const badgeClass = isActive ? 'text-emerald-600' : 'text-slate-400';

                        if (!params.data || !params.data.ps_id) {
                            return ''; 
                        }

                        return `
                            <div class="flex items-center gap-3 py-1">
                                <label class="relative inline-flex items-center cursor-pointer group">
                                    <input id="f-status" type="checkbox" ${isChecked} class="sr-only peer" 
                                        onchange="updateStockStatus(${params.data.ps_id}, this.checked)">
                                    
                                    <div class="w-8 h-4 bg-slate-200 rounded-full peer 
                                        peer-checked:bg-emerald-500/30 
                                        transition-all duration-300"></div>
                                    
                                    <div class="absolute left-[2px] top-[2px] w-3 h-3 bg-white border border-slate-300 rounded-full 
                                        transition-all duration-300 
                                        peer-checked:translate-x-4 peer-checked:border-emerald-500 peer-checked:bg-emerald-500"></div>
                                </label>
                                
                                <span class="text-[10px] ${badgeClass}">
                                    ${statusText}
                                </span>
                            </div>
                        `;
                    }
                },
                {
                    headerName: '',
                    width: 100,
                    pinned: 'right',
                    cellRenderer: p => {
                        const esc = s => String(s == null ? '' : s).replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                        const hName = esc(p.data.pd_details_head);
                        const hCode = esc(p.data.pd_gen_code);
                        return `
                        <div class="flex items-center justify-center h-full gap-1">
                            <button onclick="HistoryManager.open(${p.data.pd_id}, ${p.data.wh_id}, '${hName}', '${hCode}')" title="ประวัติสินค้า" class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-secondary transition-all flex items-center justify-center">
                                <i data-lucide="history" class="w-4 h-4"></i>
                            </button>
                            <button onclick="DrawerManager.open('stock', ${p.data.pd_id}, ${p.data.wh_id})" title="แก้ไข" class="w-8 h-8 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-primary transition-all flex items-center justify-center">
                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                            </button>
                        </div>`;
                    }
                }
            ],
            // โหมดรายงาน PO
            getReportPoCols: () => [
                { 
                    headerName: 'เลขที่เอกสาร', 
                    field: 'doc_no', 
                    width: 140, 
                    pinned: 'left',
                    cellRenderer: params => `
                        <div class="flex flex-col justify-center h-full leading-tight">
                            <span class="text-sky-600 font-bold text-[13px] tracking-tight">${params.value}</span>
                            <span class="text-[9px] text-slate-400 uppercase font-semibold">Document No.</span>
                        </div>
                    `
                },
                { 
                    headerName: 'วันที่รับเข้า', 
                    field: 'trans_date', 
                    width: 130,
                    pinned: 'left',
                    cellRenderer: params => `
                        <div class="flex flex-col justify-center h-full">
                            <span class="text-slate-600 text-[12px]">${params.value ? new Date(params.value).toLocaleDateString('th-TH', {day:'2-digit', month:'short', year:'2-digit'}) : '-'}</span>
                        </div>
                    `
                },
                { 
                    headerName: 'รายการสินค้า', 
                    field: 'items', 
                    flex: 2,
                    minWidth: 300,
                    autoHeight: true, 
                    getQuickFilterText: params => {
                        const items = params.value || [];
                        return items.map(item => `${item.pd_details_head} ${item.pd_gen_code}`).join(' ');
                    },
                    cellRenderer: params => {
                        const items = params.data.items || [];
                        if (items.length === 0) return '<div class="py-2 text-slate-400 italic text-[11px] px-4 text-center">ไม่มีรายการ</div>';

                        const rowId = 'po-sm-' + params.node.id;
                        const itemCount = items.length;
                        const searchInput = document.getElementById('grid-search');
                        const isSearching = searchInput && searchInput.value.trim() !== "";
                        const accordionClass = isSearching ? "" : "hidden";
                        const arrowClass = isSearching ? "rotate-180" : "";
                        
                        const itemsHtml = items.map(item => `
                            <div class="flex items-center justify-between px-3 border-b border-slate-50 last:border-0 hover:bg-sky-50 transition-colors text-[11px]">
                                <div class="flex flex-1 gap-2 ">
                                    <span class="font-medium text-slate-700 truncate">${item.pd_details_head}</span>
                                    <span class="text-[9px] text-purple-600">${item.pd_gen_code}</span>
                                    <span class="text-[9px] text-primary">${item.wh_name}</span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0 font-bold">
                                    <span class="text-emerald-600">+${Number(item.qty_change).toLocaleString()}</span>
                                    <span class="text-slate-400 text-[9px] font-normal">${item.pd_unit || ''}</span>
                                </div>
                            </div>
                        `).join('');

                        return `
                            <div class="py-1 w-full px-1">
                                <div class="border border-slate-200 rounded-lg overflow-hidden bg-white shadow-sm transition-all hover:border-sky-300">
                                    <button type="button" 
                                        onclick="const el = document.getElementById('${rowId}'); el.classList.toggle('hidden'); this.querySelector('.arrow').classList.toggle('rotate-180');"
                                        class="w-full flex items-center justify-between p-1.5 bg-slate-50/50 hover:bg-sky-50/50 transition-colors">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 bg-sky-600 rounded flex items-center justify-center shadow-sm">
                                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                            </div>
                                            <span class="text-[11px] font-bold text-slate-700 uppercase">Inventory In (${itemCount})</span>
                                        </div>
                                        <svg class="arrow ${arrowClass} w-3.5 h-3.5 text-slate-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <div id="${rowId}" class="${accordionClass} border-t border-slate-100 bg-white max-h-64 overflow-y-auto">
                                        <div class="flex items-center gap-3 px-3 bg-slate-50 border-b border-slate-100 text-[9px] font-bold text-slate-400 tracking-wider">
                                            <div class="flex-1">รายละเอียดสินค้า</div>
                                            <div class="w-20 text-right">จำนวน</div>
                                        </div>
                                        
                                        ${itemsHtml}
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                },
                { 
                    headerName: 'หมายเหตุ', 
                    field: 'details', 
                    flex: 0.8, 
                    pinned: 'right',
                    cellRenderer: params => `
                        <div class="flex items-center h-full text-[11px] gap-3">
                            <p class="text-slate-500 italic truncate mb-1" title="${params.value || ''}">${params.value || 'ไม่มีหมายเหตุ'}</p>
                        </div>
                    `
                },
                { 
                    headerName: 'ผู้ทำรายการ', 
                    field: 'user_id', 
                    width: 140,
                    pinned: 'right',
                    cellRenderer: params => `
                        <div class="flex items-center h-full text-[11px] gap-3">
                            <div class="flex items-center gap-1.5">
                                <div class="w-4 h-4 rounded-full bg-slate-100 flex items-center justify-center text-[8px] font-bold text-slate-500 border">
                                    ${params.data.user_id ? params.data.user_id.charAt(0).toUpperCase() : '?'}
                                </div>
                                <span class="text-[9px] font-bold text-slate-600">${params.data.user_id || 'N/A'}</span>
                            </div>
                        </div>
                    `
                }
            ],
            // โหมดรายงาน Adjust
            getReportAdjustCols: () => [
                { 
                    headerName: 'เลขที่เอกสาร', 
                    field: 'doc_no', 
                    width: 140, 
                    pinned: 'left',
                    cellRenderer: params => `
                        <div class="flex flex-col justify-center h-full leading-tight">
                            <span class="text-amber-600 font-bold text-[13px] tracking-tight">${params.value}</span>
                            <span class="text-[9px] text-slate-400 uppercase font-semibold">Document No.</span>
                        </div>
                    `
                },
                { 
                    headerName: 'วันที่ตรวจสอบยอด', 
                    field: 'trans_date', 
                    width: 130,
                    pinned: 'left',
                    cellRenderer: params => `
                        <div class="flex flex-col justify-center h-full leading-tight">
                            <span class="text-slate-700 text-[12px]">${params.value ? new Date(params.value).toLocaleDateString('th-TH', {day:'2-digit', month:'short', year:'2-digit'}) : '-'}</span>
                        </div>
                    `
                },
                { 
                    headerName: 'รายการสินค้าที่ปรับยอด', 
                    field: 'items', 
                    flex: 2, 
                    minWidth: 350, 
                    autoHeight: true, 
                    cellRenderer: params => {
                        const items = params.data.items || [];
                        if (items.length === 0) return '<div class="py-2 text-slate-400 italic text-[11px] px-4 text-center">ไม่มีรายการ</div>';

                        const rowId = 'adj-v3-' + params.node.id;
                        const itemCount = items.length;
                        const searchInput = document.getElementById('grid-search');
                        const isSearching = searchInput && searchInput.value.trim() !== "";
                        const accordionClass = isSearching ? "" : "hidden";
                        const arrowClass = isSearching ? "rotate-180" : "";
                        
                        // ส่วนของรายการสินค้าแต่ละบรรทัด
                        const itemsHtml = items.map(item => {
                            const diff = Number(item.qty_change);
                            const oldQty = Number(item.qty_balance) - diff;
                            const newQty = Number(item.qty_balance);
                            const isPos = diff > 0;

                            return `
                                <div class="flex items-center gap-3 py-2 px-3 border-b border-slate-50 last:border-0 hover:bg-slate-50/50 transition-colors text-[11px]">
                                    <div class="flex-1 flex items-center gap-2 min-w-0 leading-tight">
                                        <div class="font-semibold text-slate-700 truncate">${item.pd_details_head}</div>
                                        <span class="text-[9px] text-purple-600">${item.pd_gen_code}</span>
                                        <span class="text-[9px] text-primary">${item.wh_name}</span>
                                    </div>

                                    <div class="flex items-center bg-slate-100 rounded-md px-2 gap-2 shrink-0 border border-slate-200/50">
                                        <div class="w-10 text-center text-slate-500 font-mono">${oldQty.toLocaleString()}</div>
                                        <i data-lucide="move-right" class="w-3 h-3 text-slate-500"></i>
                                        <div class="w-10 text-center text-slate-900 font-bold font-mono">${newQty.toLocaleString()}</div>
                                    </div>

                                    <div class="w-20 text-right shrink-0">
                                        <span class="font-black ${isPos ? 'text-emerald-600' : 'text-rose-500'}">
                                            ${isPos ? '+' : ''}${diff.toLocaleString()}
                                        </span>
                                        <span class="text-[9px] text-slate-400 ml-0.5">${item.pd_unit || 'ชิ้น'}</span>
                                    </div>
                                </div>
                            `;
                        }).join('');

                        return `
                            <div class="py-1.5 w-full px-1">
                                <div class="border border-slate-200 rounded-lg overflow-hidden bg-white shadow-sm transition-all hover:border-amber-300">
                                    <button type="button" 
                                        onclick="const el = document.getElementById('${rowId}'); el.classList.toggle('hidden'); this.querySelector('.arrow').classList.toggle('rotate-180');" 
                                        class="w-full flex items-center justify-between p-2 bg-slate-50/80 hover:bg-amber-50/30 transition-colors">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 bg-amber-500 rounded flex items-center justify-center shadow-sm shrink-0">
                                                <i data-lucide="edit-3" class="w-3 h-3 text-white"></i>
                                            </div>
                                            <span class="text-[11px] font-bold text-slate-700 uppercase leading-none">Adjustment Details (${itemCount})</span>
                                        </div>
                                        <i data-lucide="chevron-down" class="arrow ${arrowClass} w-4 h-4 text-slate-400 transition-transform duration-200"></i>
                                    </button>

                                    <div id="${rowId}" class="${accordionClass} border-t border-slate-100 bg-white max-h-64 overflow-y-auto">
                                        <div class="flex items-center gap-3 px-3 bg-slate-50 border-b border-slate-100 text-[9px] font-bold text-slate-400 tracking-wider">
                                            <div class="flex-1">รายละเอียดสินค้า</div>
                                            <div class="flex items-center gap-2 shrink-0 px-2">
                                                <div class="w-10 text-center">ยอดเก่า</div>
                                                <div class="w-3"></div>
                                                <div class="w-10 text-center text-amber-600">ยอดใหม่</div>
                                            </div>
                                            <div class="w-20 text-right">ส่วนต่าง</div>
                                        </div>
                                        
                                        ${itemsHtml}
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                },
                { 
                    headerName: 'หมายเหตุ', 
                    field: 'details', 
                    flex: 0.8, 
                    pinned: 'right',
                    cellRenderer: params => `
                        <div class="flex items-center h-full text-[11px] gap-3">
                            <p class="text-slate-500 italic truncate mb-1" title="${params.value || ''}">${params.value || 'ไม่มีหมายเหตุ'}</p>
                        </div>
                    `
                },
                { 
                    headerName: 'ผู้ทำรายการ', 
                    field: 'user_id', 
                    width: 140,
                    pinned: 'right',
                    cellRenderer: params => `
                        <div class="flex items-center h-full text-[11px] gap-3">
                            <div class="flex items-center gap-1.5">
                                <div class="w-4 h-4 rounded-full bg-slate-100 flex items-center justify-center text-[8px] font-bold text-slate-500 border">
                                    ${params.data.user_id ? params.data.user_id.charAt(0).toUpperCase() : '?'}
                                </div>
                                <span class="text-[9px] font-bold text-slate-600">${params.data.user_id || 'N/A'}</span>
                            </div>
                        </div>
                    `
                }
            ]
        };

        window.updateStockStatus = async (psId, isChecked) => {
            const newStatus = isChecked ? '0' : '1';
            const result = await StockAPI.updateStatusAPI(psId, newStatus);

            if (result.success) {
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 1500,
                    timerProgressBar: true
                });
                
                Toast.fire({
                    icon: 'success',
                    title: result.message || 'อัปเดตสถานะสำเร็จ'
                });
            } else {
                // กรณีเกิดข้อผิดพลาด
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: result.message
                });
                AppController.selectMenu(State.selectedMenuId);
            }
        };

        const exportToExcel = () => {
            if (!gridApi) {
                Swal.fire('แจ้งเตือน', 'ไม่พบข้อมูลตารางสำหรับการ Export', 'warning');
                return;
            }

            let fileName = 'export.xlsx';
            let sheetName = 'Sheet1';
            let exportColumns = []; // ตัวแปรเก็บรายการคอลัมน์ที่จะ Export

            if (State.selectedMenuId === 'report-po') {
                // --- คอลัมน์สำหรับตาราง รายงานรับเข้า (PO) ---
                fileName = `รายงานรับเข้า_PO_${new Date().toISOString().split('T')[0]}.xlsx`;
                sheetName = 'PO_Report';
                exportColumns = ['doc_no', 'trans_date', 'items', 'remark', 'user_id', 'details'];

            } else if (State.selectedMenuId === 'report-adjust') {
                // --- คอลัมน์สำหรับตาราง รายงานปรับยอด (AD) ---
                fileName = `รายงานปรับยอด_AD_${new Date().toISOString().split('T')[0]}.xlsx`;
                sheetName = 'Adjust_Report';
                exportColumns = ['doc_no', 'adj_date', 'items', 'remark', 'user_id', 'details'];

            } else {
                // --- คอลัมน์สำหรับตาราง สต็อกสินค้า (Stock) ---
                const viewTitle = document.getElementById('desktop-view-title')?.innerText || 'ทั้งหมด';
                fileName = `สต็อกสินค้า_${viewTitle}_${new Date().toISOString().split('T')[0]}.xlsx`;
                sheetName = 'Stock_Data';
                
                // ตรงนี้จะดึง pd_gen_code และ pd_details_head ที่เราซ่อนไว้ออกมาลง Excel
                exportColumns = [
                    'pd_gen_code',     // รหัสสินค้า
                    'pd_details_head', // ชื่อสินค้า
                    'wh_name',         // คลังสินค้า
                    'pd_details',      // รายละเอียด
                    'pd_price',        // ราคา
                    'pd_unit',         // หน่วย
                    'pd_qty_all',       // ยอดเบิกสะสม
                    'pd_qty_min',      // ขั้นต่ำ
                    'pd_qty',          // ยอดคงเหลือ
                ];
            }

            const params = {
                fileName: fileName,
                sheetName: sheetName,
                columnKeys: exportColumns, // บังคับดึงเฉพาะคอลัมน์ที่ระบุ (รวมถึงคอลัมน์ที่ hide ไว้ด้วย)
                allColumns: false, 
                
                // จัดการ Header (เปลี่ยนชื่อหัวคอลัมน์ใน Excel ได้ตามต้องการ)
                processHeaderCallback: (params) => {
                    const colDef = params.column.getColDef();
                    if (colDef.field === 'pd_price') return 'ราคา (บาท)';
                    if (colDef.field === 'pd_qty') return 'จำนวนคงเหลือ';
                    return colDef.headerName || colDef.field;
                },

                // จัดการ Format ข้อมูลข้างใน Cell
                processCellCallback: (params) => {
                    const colId = params.column.getColId();
                    const value = params.value;

                    // 🌟 ส่วนแก้ไขการส่งออกรายการ items ของ PO และ AD 🌟
                    if (colId === 'items' && Array.isArray(value)) {
                        // วนลูปเพื่อจัดการแต่ละสินค้าในบิล
                        return value.map((item, index) => {
                            // ดึงข้อมูลพื้นฐานที่มีเหมือนกันทั้ง PO และ AD
                            const code = item.pd_gen_code || item.pd_code || ''; 
                            const name = item.pd_details_head || item.pd_name || 'ไม่ระบุชื่อสินค้า'; 
                            const unit = item.pd_unit || item.unit || ''; 

                            let formattedItem = `${index + 1}. `; 
                            if (code) formattedItem += `[${code}] `;
                            
                            // 🌟 แยกการทำงานระหว่างรายงาน AD และ PO 🌟
                            if (State.selectedMenuId === 'report-adjust') {
                                
                                // 💡 หมายเหตุ: คุณอาจต้องปรับชื่อตัวแปร old_qty และ new_qty ให้ตรงกับ field ในฐานข้อมูล/API ของคุณ
                                const oldQty = item.old_qty !== undefined ? item.old_qty : (item.qty_old || 0); // ยอดเก่า
                                const newQty = item.new_qty !== undefined ? item.new_qty : (item.qty_new || 0); // ยอดใหม่
                                const diffQty = item.adj_qty || item.qty_change || 0; // ส่วนต่าง
                                
                                // (ทางเลือก) ทำให้ส่วนต่างที่เป็นบวกมีเครื่องหมาย + นำหน้าเพื่อให้ดูง่ายขึ้น
                                const diffDisplay = diffQty > 0 ? `+${diffQty}` : diffQty;

                                // ตัวอย่างผลลัพธ์: "1. [CODE-001] ชื่อสินค้า (ยอดเก่า: 10 -> ยอดใหม่: 15 | ส่วนต่าง: +5 ชิ้น)"
                                formattedItem += `${name} (ยอดเก่า: ${oldQty} -> ยอดใหม่: ${newQty} | ส่วนต่าง: ${diffDisplay} ${unit})`;

                            } else {
                                // กรณีเป็นรายงานรับเข้า (PO) ปกติ
                                const qty = item.qty || item.qty_change || 0; // จำนวนรับเข้า
                                formattedItem += `${name} (จำนวน: ${qty} ${unit})`;
                            }

                            return formattedItem.replace(/[\r\n]+/g, ' ').trim();
                        }).join('\n'); // สั่งให้ขึ้นบรรทัดใหม่ใน Cell ของ Excel
                    }

                    // จัดการรูปแบบวันที่ (โค้ดเดิม)
                    if ((colId === 'trans_date' || colId === 'adj_date') && value) {
                        return new Date(value).toLocaleDateString('th-TH', { 
                            year: 'numeric', month: 'short', day: 'numeric' 
                        });
                    }

                    // จัดการตัวเลขราคา (โค้ดเดิม)
                    if (colId === 'pd_price' && value) {
                        return parseFloat(value).toFixed(2);
                    }

                    return (value !== null && value !== undefined) ? value : '';
                }
            };

            Swal.fire({
                title: 'กำลังเตรียมไฟล์ Excel...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                gridApi.exportDataAsExcel(params);
                Swal.close();
            } catch (error) {
                console.error("Export Error: ", error);
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถสร้างไฟล์ Excel ได้', 'error');
            }
        };

        // --- CONTROLLER ---
        const AppController = {
            init: async () => {
                try {
                    await StockAPI.getAllCategoriesAPI();
                    await StockAPI.getAllWarehousesAPI();

                    AppController.initGrid();
                    AppController.renderWHMenu();
                    AppController.selectMenu('all');
                    
                    lucide.createIcons();

                    // 1. อ่านค่าพารามิเตอร์จาก URL
const urlParams = new URLSearchParams(window.location.search);
const targetPdId = urlParams.get('pd_id');
const targetWhId = urlParams.get('wh_id');

if (targetPdId) {
    // 2. หน่วงเวลาเล็กน้อยเพื่อให้ Grid โหลดข้อมูลและเรนเดอร์ UI ให้เสร็จก่อน
    setTimeout(() => {
        let targetNode = null;
        let targetData = null;

        // 3. วนลูปหา Row ใน ag-Grid ที่มี pd_id และ wh_id ตรงกับที่เราต้องการ
        // (หมายเหตุ: เปลี่ยน gridOptions.api เป็นชื่อตัวแปร api grid ของคุณ เช่น State.gridApi)
        if (gridOptions && gridOptions.api) {
            gridOptions.api.forEachNode((node) => {
                // เช็คว่าถ้ามีการส่ง wh_id มา ต้องตรงทั้งคู่ แต่ถ้าไม่มี เอาแค่ pd_id
                const matchPd = node.data.pd_id == targetPdId;
                const matchWh = targetWhId ? (node.data.wh_id == targetWhId) : true;
                
                if (matchPd && matchWh) {
                    targetNode = node;
                    targetData = node.data;
                }
            });
        }

        if (targetNode && targetData) {
            // --- ทำการโฟกัสและไฮไลท์สี ---
            
            // ก. เลื่อนหน้าจอ (Scroll) ของตารางไปหาแถวนั้นให้อยู่ตรงกลางจอ
            gridOptions.api.ensureIndexVisible(targetNode.rowIndex, 'middle');
            
            // ข. ทำการ Select แถวนั้น (ขึ้นแถบสีไฮไลท์ค้างไว้)
            targetNode.setSelected(true);
            
            // ค. ทำให้เซลล์ในแถวนั้น "กระพริบสี" (Flash) เพื่อดึงดูดสายตาทันที
            gridOptions.api.flashCells({ rowNodes: [targetNode] });

            // ง. สั่งเปิด Drawer ทันที (แก้ชื่อฟังก์ชันให้ตรงกับของคุณ)
            DrawerManager.openEditDrawer(targetData); 

            // จ. ลบพารามิเตอร์ออกจาก URL เพื่อไม่ให้ทำงานซ้ำเวลาผู้ใช้กดรีเฟรชหน้า (F5)
            const newUrl = window.location.pathname; 
            window.history.replaceState({}, document.title, newUrl);
        } else {
            console.warn(`ไม่พบข้อมูล pd_id: ${targetPdId}, wh_id: ${targetWhId} ในตารางนี้`);
        }
    }, 800); // ตั้งเวลาดีเลย์ 0.8 วิ (ปรับเพิ่มลดได้ตามความเร็วในการโหลดข้อมูลของระบบ)
}
                    
                } catch (error) {
                    console.error("Initialization failed:", error);
                }
                
                // Click outside mobile menu
                window.addEventListener('click', (e) => {
                    const dd = document.getElementById('mobile-dropdown');
                    if (!e.target.closest('.group') && dd && !dd.classList.contains('hidden')) {
                        dd.classList.add('hidden');
                    }
                });
            },

            initGrid: () => {
                const gridDiv = document.querySelector('#mainGrid');
                setTimeout(() => MobileStock.attach(), 0);   // มือถือ: การ์ดจากแถวของตาราง
                gridApi = agGrid.createGrid(gridDiv, {
                    rowModelType: 'serverSide',
                    columnDefs: GridConfig.getStockCols(),
                    rowHeight: 60,
                    headerHeight: 45,
                    rowSelection: 'multiple',
                    suppressRowClickSelection: true,
                    pagination: true,
                    serverSideOnlyRefreshStrategy: 'root',
                    paginationPageSize: 10,
                    paginationPageSizeSelector: [10, 20, 50, 100],
                    getRowId: (params) => {
                        if (params.data && params.data.ps_id) {
                            return String(params.data.ps_id);
                        }
                        return 'temp-' + Math.random().toString(36).substring(7);
                    },
                    onGridReady: (params) => {
                        const datasource = AppController.getServerSideDatasource(); 
                        params.api.setGridOption('serverSideDatasource', datasource);
                        lucide.createIcons();
                    },
                    onPaginationChanged: (params) => {
                        if (params.newPageSize) {
                            setTimeout(() => {
                                const newSize = params.api.paginationGetPageSize 
                                                ? params.api.paginationGetPageSize() 
                                                : params.api.getGridOption('paginationPageSize');
                                params.api.setGridOption('cacheBlockSize', newSize);
                                const datasource = AppController.getServerSideDatasource();
                                params.api.setGridOption('serverSideDatasource', datasource);
                            }, 0);
                        }
                        lucide.createIcons();
                    },
                    onModelUpdated: () => {
                        setTimeout(() => {
                            lucide.createIcons();
                        }, 0);
                    },
                    getRowClass: params => {
                        if (params.data && params.data.isActive === false) {
                            return 'opacity-50 grayscale-[0.5]';
                        }
                    },
                    getContextMenuItems: (params) => {
                        const result = [
                            'copy',
                            'copyWithHeaders',
                            'paste',
                            'separator',
                            {
                                name: 'Excel Export',
                                icon: '<i data-lucide="file-spreadsheet" style="width: 16px; height: 16px;"></i>',
                                action: () => {
                                    exportToExcel();
                                }
                            }
                        ];
                        
                        // สั่งให้ Lucide สร้าง Icon ในเมนู (ใช้ setTimeout เพื่อรอให้ Menu Render เสร็จ)
                        setTimeout(() => lucide.createIcons(), 50);
                        
                        return result;
                    },
                    onSelectionChanged: AppController.onSelectionChanged
                });
            },

            getServerSideDatasource: () => {
                return {
                    getRows: async (requestParams) => {
                        const { startRow, endRow, sortModel, filterModel } = requestParams.request;
                        const search = document.getElementById('grid-search')?.value || '';
                        
                        // 1. Ensure menuId is handled as a string
                        const menuId = State.selectedMenuId;
                        const menuIdStr = menuId ? String(menuId) : ''; 

                        try {
                            let response;
                            const limit = endRow - startRow;

                            // 2. Use the string version for the check
                            if (menuIdStr.startsWith('report-')) {
                                const reportType = menuIdStr === 'report-po' ? 'IN' : 'ADJUST';
                                response = await StockAPI.getReportAPI(reportType, limit, startRow, search);
                            } else {
                                // Use the original menuId for the API logic if it needs the raw number
                                const wh_id = (menuIdStr !== 'all' && menuIdStr !== '') ? menuId : null;
                                response = await StockAPI.getAllProductsAPI(wh_id, search, startRow, endRow, sortModel, filterModel);
                            }

                            if (response.success) {
                                requestParams.success({
                                    rowData: response.data,
                                    rowCount: response.total
                                });
                                if(document.getElementById('items-count-badge')) {
                                    document.getElementById('items-count-badge').innerText = response.total;
                                }
                            } else {
                                requestParams.fail();
                            }
                        } catch (e) {
                            console.error("SSRM Error:", e);
                            requestParams.fail();
                        }
                    }
                };
            },

            toggleItemStatus: async (id, isChecked) => {
                const newStatus = isChecked ? '0' : '1'; 

                try {
                    const result = await StockAPI.updateStatusAPI(id, newStatus);

                    if (result.success) {
                        const itemIndex = State.items.findIndex(item => item.pd_id == id);
                        if (itemIndex !== -1) {
                            State.items[itemIndex].pd_status = newStatus;
                        }

                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true
                        });
                        
                        Toast.fire({
                            icon: 'success',
                            title: `สถานะ: ${isChecked ? 'ใช้งาน' : 'ระงับ'}`
                        });
                    } else {
                        throw new Error(result.message);
                    }
                } catch (error) {
                    if(gridApi) gridApi.refreshCells(); 
                    Swal.fire('Error', 'ไม่สามารถปรับสถานะได้: ' + error.message, 'error');
                }
            },

            renderWHMenu: (filterText = '') => {
                const desktopList = document.getElementById('warehouse-menu-list');
                const mobileList = document.getElementById('mobile-wh-list');
                const mobileReportGroup = document.getElementById('mobile-report-group');
                const searchLower = filterText.toLowerCase();
                const filteredWarehouses = State.warehouses.filter(wh => 
                    wh.wh_name.toLowerCase().includes(searchLower)
                );

                // --- ส่วนที่ 1: จัดการปุ่ม "ทั้งหมด" (Mobile) ---
                const isAllActive = State.selectedMenuId === 'all';
                const allActiveMobileClass = isAllActive ? 'bg-blue-50 text-primary font-bold' : 'text-slate-700 font-semibold';
                
                let mobileHtml = `
                    <button onclick="AppController.selectMenu('all'); AppController.toggleMobileDropdown();" 
                        class="mobile-menu-item w-full text-left px-4 py-3 text-sm flex items-center gap-3 border-b border-slate-50 transition-colors ${allActiveMobileClass}">
                        <i data-lucide="layout-grid" class="w-4 h-4"></i> ทั้งหมด
                    </button>
                `;

                // --- ส่วนที่ 2: จัดการรายการคลังสินค้า (ใช้ filteredWarehouses แทน State.warehouses) ---
                const generateHtml = (isMobile) => filteredWarehouses.map(wh => {
                    const count = wh.total_items || 0;
                    const isActive = State.selectedMenuId == wh.id;

                    if (isMobile) {
                        return `
                        <div class="flex items-center border-b border-slate-100 ${isActive ? 'bg-blue-50/50' : ''}">
                            <button onclick="AppController.selectMenu(${wh.id}); AppController.toggleMobileDropdown();" 
                                    class="flex-1 text-left px-4 py-4 text-sm ${isActive ? 'text-primary font-bold' : 'text-slate-600 font-medium'} flex items-center justify-between transition-colors">
                                <span class="truncate pr-2 font-semibold text-[15px]">${wh.wh_name}</span>
                            </button>
                            <div class="relative px-3 flex items-center h-full">
                                <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full shrink-0">${count}</span>
                                <button onclick="AppController.toggleWhActionMenu(event, ${wh.id}, true)" class="p-2 text-slate-400 hover:text-primary">
                                    <i data-lucide="more-vertical" class="w-5 h-5"></i>
                                </button>
                                <div id="wh-action-mb-${wh.id}" class="hidden absolute right-2 top-12 w-40 bg-white border border-slate-200 rounded-xl shadow-2xl z-[9]">
                                    <div class="py-1">
                                        <button onclick="DrawerManager.open('wh', ${wh.id}); AppController.toggleMobileDropdown();" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-slate-600 active:bg-slate-50 border-b border-slate-50">
                                            <i data-lucide="edit-3" class="w-4 h-4 text-blue-500"></i> แก้ไข
                                        </button>
                                        <button onclick="AppController.deleteWarehouse(${wh.id}, '${wh.wh_name}')" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-rose-500 active:bg-rose-50">
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
                                    <span class="truncate">${wh.wh_name}</span>
                                </button>
                                
                                <div class="flex items-center gap-1 pr-2">
                                    <span class="text-[10px] px-2 py-0.5 bg-slate-200/50 text-slate-500 rounded-lg font-bold">${count}</span>
                                    <button onclick="AppController.toggleWhActionMenu(event, ${wh.id}, false)" class="p-1.5 text-slate-400 hover:text-primary transition-colors">
                                        <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                    </button>
                                    
                                    <div id="wh-action-${wh.id}" class="hidden fixed mt-1 w-36 bg-white border border-slate-200 rounded-xl shadow-2xl z-[3] overflow-hidden">
                                        <div class="flex flex-col">
                                            <button onclick="DrawerManager.open('wh', ${wh.id})" class="w-full flex items-center gap-3 px-4 py-3 text-[13px] font-bold text-slate-600 hover:bg-slate-50 hover:text-blue-600 border-b border-slate-50 transition-all">
                                                <i data-lucide="edit-3" class="w-4 h-4 text-blue-500"></i> แก้ไข
                                            </button>
                                            <button onclick="AppController.deleteWarehouse(${wh.id}, '${wh.wh_name}')" class="w-full flex items-center gap-3 px-4 py-3 text-[13px] font-bold text-rose-500 hover:bg-rose-50 transition-all">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i> ลบออก
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>`;
                    }
                }).join('');

                // --- ส่วนที่ 3: จัดการกลุ่มรายงาน ---
                const isPoActive = State.selectedMenuId === 'report-po';
                const isAdjActive = State.selectedMenuId === 'report-adjust';

                mobileReportGroup.innerHTML = `
                    <button onclick="AppController.selectMenu('report-po'); AppController.toggleMobileDropdown();" 
                        class="w-full text-left px-4 py-3 text-sm flex items-center gap-3 transition-colors ${isPoActive ? 'bg-blue-50 text-primary font-bold' : 'text-slate-700 font-semibold'}">
                        <i data-lucide="file-text" class="w-4 h-4"></i> รายงานรับเข้า (PO)
                    </button>
                    <button onclick="AppController.selectMenu('report-adjust'); AppController.toggleMobileDropdown();" 
                        class="w-full text-left px-4 py-3 text-sm flex items-center gap-3 transition-colors ${isAdjActive ? 'bg-blue-50 text-primary font-bold' : 'text-slate-700 font-semibold'}">
                        <i data-lucide="clipboard-check" class="w-4 h-4"></i> รายงานปรับยอด (AD)
                    </button>
                `;

                // อัปเดต HTML ลงในรายการ
                mobileList.innerHTML = mobileHtml + generateHtml(true);
                desktopList.innerHTML = generateHtml(false);
                
                lucide.createIcons();
            },

            // ใส่ไว้ใน AppController
            toggleWhActionMenu: (event, whId, isMobile) => {
                event.stopPropagation();
                
                document.querySelectorAll('[id^="wh-action-"]').forEach(el => el.classList.add('hidden'));

                const menuId = isMobile ? `wh-action-mb-${whId}` : `wh-action-${whId}`;
                const menu = document.getElementById(menuId);
                
                if (menu) {
                    menu.classList.toggle('hidden');
                    
                    if (!menu.classList.contains('hidden')) {
                        menu.style.position = 'fixed';
                        
                        // คำนวณตำแหน่งปุ่มที่คลิก
                        const rect = event.currentTarget.getBoundingClientRect();
                        
                        if (isMobile) {
                            menu.style.top = `${rect.bottom + 5}px`;
                            menu.style.left = `${rect.left - 120}px`;
                        } else {
                            menu.style.top = `${rect.bottom - 100}px`;
                            menu.style.left = `${rect.right - 5}px`;
                        }

                        // เพิ่ม Event ปิดเมนูเมื่อคลิกที่อื่น
                        const closeHandler = (e) => {
                            if (!menu.contains(e.target)) {
                                menu.classList.add('hidden');
                                document.removeEventListener('click', closeHandler);
                            }
                        };
                        setTimeout(() => document.addEventListener('click', closeHandler), 10);
                    }
                }
            },

            deleteWarehouse: async (id, name) => {
                const result = await Swal.fire({
                    title: 'ยืนยันการลบ?',
                    text: `คุณต้องการลบคลัง "${name}" ใช่หรือไม่?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'ยืนยันการลบ',
                    cancelButtonText: 'ยกเลิก'
                });

                if (result.isConfirmed) {
                    await StockAPI.deleteWarehouseAPI(id);
                }
            },

            selectMenu: async (id, searchText = '') => {
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
                    const reportType = isPo ? 'IN' : 'ADJUST'; // กำหนด type ไว้ใช้ใน datasource

                    if (isPo) {
                        document.getElementById('menu-report-po').classList.add('sidebar-item-active');
                        titleText = 'รายงานการรับเข้า (PO)';
                        newCols = GridConfig.getReportPoCols();
                    } else {
                        document.getElementById('menu-report-adjust').classList.add('sidebar-item-active');
                        titleText = 'รายงานการปรับยอดสต็อก';
                        newCols = GridConfig.getReportAdjustCols();
                    }

                    if (gridApi) {
                        // 1. เตรียม Config ก่อนเปลี่ยน Model
                        const currentModel = gridApi.getGridOption('rowModelType');
                        
                        // ตั้งค่า Column และ UI ทั่วไป
                        gridApi.setGridOption('rowHeight', 32);
                        gridApi.setGridOption('headerHeight', 35);
                        gridApi.setGridOption('columnDefs', newCols);

                        // 2. ตรวจสอบว่าต้องเปลี่ยน Model หรือไม่ (ลดการกระตุกและการยิง API ซ้ำ)
                        if (currentModel !== 'serverSide') {
                            gridApi.setGridOption('rowModelType', 'serverSide');
                        }

                        const datasource = {
                            getRows: async (params) => {
                                // ป้องกันการเรียก API ถ้าไม่มี request params
                                if (!params.request) return;

                                const { startRow, endRow } = params.request;
                                const search = document.getElementById('grid-search').value || '';
                                
                                try {
                                    // คำนวณ limit/offset จาก startRow และ endRow
                                    const limit = endRow - startRow;
                                    const offset = startRow;
                                    
                                    const response = await StockAPI.getReportAPI(reportType, limit, offset, search);
                                    
                                    if (response.success) {
                                        // อัปเดตยอดรวม (แนะนำให้ทำเฉพาะตอนโหลดหน้าแรก startRow === 0)
                                        if (startRow === 0) {
                                            const badge = document.getElementById('items-count-badge');
                                            if(badge) badge.innerText = response.total.toLocaleString();
                                        }

                                        params.success({
                                            rowData: response.data,
                                            rowCount: response.total
                                        });
                                    } else {
                                        params.fail();
                                    }
                                } catch (err) {
                                    console.error(err);
                                    params.fail();
                                }
                            }
                        };

                        gridApi.setGridOption('serverSideDatasource', datasource);
                    }
                } else {
                    const activeBtnId = (id === 'all') ? 'menu-all' : `menu-${id}`;
                    document.getElementById(activeBtnId)?.classList.add('sidebar-item-active');

                    titleText = (id === 'all') ? 'สินค้าทั้งหมด' : 
                        (State.warehouses.find(w => w.id == id)?.wh_name || 'สินค้าในคลัง');
                    
                    newCols = GridConfig.getStockCols();
                    gridApi.setGridOption('rowModelType', 'serverSide');
                    gridApi.setGridOption('columnDefs', newCols);
                    gridApi.setGridOption('rowData', undefined); 

                    const datasource = AppController.getServerSideDatasource();
                    gridApi.setGridOption('serverSideDatasource', datasource);
                    gridApi.setGridOption('rowHeight', 60);
                    gridApi.setGridOption('headerHeight', 45);
                }

                // อัปเดต Grid
                if (gridApi) {
                    gridApi.setGridOption('columnDefs', newCols);
                    gridApi.setGridOption('rowData', rowData);
                    gridApi.setGridOption('groupDefaultExpanded', groupExpanded);
                    gridApi.setGridOption('quickFilterText', '');
                }

                document.getElementById('current-view-title').innerText = titleText;
                document.getElementById('desktop-view-title').innerText = titleText;
                document.getElementById('items-count-badge').innerText = rowData.length;
                setTimeout(() => lucide.createIcons(), 50);
            },

            getSelectedItemsFull: async () => {
                if (!gridApi) return [];

                const selectionState = gridApi.getServerSideSelectionState();
                const totalRowsCount = gridApi.getDisplayedRowCount(); // จำนวนแถวทั้งหมดที่ Server บอกว่ามี

                // กรณีที่ 1: เลือกปกติ (ไม่ได้กด Select All)
                if (!selectionState.selectAll) {
                    return gridApi.getSelectedRows();
                }

                // กรณีที่ 2: กด Select All (ต้องเช็คว่าใน Cache มีข้อมูลครบตาม totalRowsCount หรือยัง)
                let allLoadedRows = [];
                gridApi.forEachNode(node => { if (node.data) allLoadedRows.push(node.data); });

                if (allLoadedRows.length < totalRowsCount) {
                    Swal.fire({
                        title: 'กำลังดึงข้อมูลส่วนที่เหลือ...',
                        text: `กรุณารอสักครู่ (${allLoadedRows.length} / ${totalRowsCount})`,
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });

                    // ดึงค่า cacheBlockSize ให้รองรับทุกเวอร์ชันของ ag-Grid
                    let blockSize = 100;
                    if (typeof gridApi.getGridOption === 'function') {
                        blockSize = gridApi.getGridOption('cacheBlockSize') || 100;
                    } else if (typeof gridApi.getGridOptions === 'function') {
                        blockSize = gridApi.getGridOptions().cacheBlockSize || 100;
                    }

                    // วนลูปสั่งให้ Grid โหลด Block ที่แหว่งไปจนครบ
                    for (let i = 0; i < totalRowsCount; i += blockSize) {
                        gridApi.ensureIndexVisible(i);
                        // รอให้ Network ดึงข้อมูลจาก PHP แป๊บหนึ่ง (300ms)
                        await new Promise(resolve => setTimeout(resolve, 300)); 
                    }

                    // เมื่อโหลดเสร็จแล้ว ดึงข้อมูลจาก Cache ที่เต็มแล้วออกมาอีกรอบ
                    allLoadedRows = [];
                    gridApi.forEachNode(node => { if (node.data) allLoadedRows.push(node.data); });
                    
                    Swal.close();
                }

                // กรองรายการที่ถูก "ติ๊กออก" (Deselected) ออกจากเซตข้อมูลทั้งหมด
                const excludedIds = (selectionState.toggledNodes || []).map(id => String(id));
                const filteredItems = allLoadedRows.filter(item => {
                    const currentId = String(item.ps_id);
                    return !excludedIds.includes(currentId);
                });

                return filteredItems;
            },

           onSelectionChanged: () => {
                if (!gridApi) return;

                // 1. ดึงสถานะการเลือกจาก Server-side Selection API
                const selectionState = gridApi.getServerSideSelectionState();
                const selectionBar = document.getElementById('selection-bar');
                const selectedCountEl = document.getElementById('selected-count');
                
                let count = 0;

                // 2. คำนวณจำนวนที่เลือกจริง
                if (selectionState.selectAll) {
                    // กรณี "เลือกทั้งหมด": เอาจำนวนแถวทั้งหมดที่ Grid แสดงผลอยู่ ลบด้วยแถวที่ถูก 'ติ๊กออก' (toggledNodes)
                    const totalRows = gridApi.getDisplayedRowCount(); 
                    count = Math.max(0, totalRows - (selectionState.toggledNodes ? selectionState.toggledNodes.length : 0));
                } else {
                    // กรณี "เลือกบางรายการ": นับจากแถวที่ถูก 'ติ๊กเลือก' (toggledNodes)
                    count = selectionState.toggledNodes ? selectionState.toggledNodes.length : 0;
                }

                // 3. ควบคุมการแสดงผล UI (Selection Bar)
                if (count > 0) {
                    // อัปเดตตัวเลข
                    if (selectedCountEl) {
                        selectedCountEl.innerText = count.toLocaleString();
                    }
                    
                    // แสดงแถบเครื่องมือ (เลื่อนขึ้น)
                    if (selectionBar) {
                        selectionBar.classList.remove('selection-bar-hidden');
                        selectionBar.classList.add('selection-bar-visible');
                    }
                } else {
                    // ซ่อนแถบเครื่องมือ (เลื่อนลง)
                    if (selectionBar) {
                        selectionBar.classList.add('selection-bar-hidden');
                        selectionBar.classList.remove('selection-bar-visible');
                    }
                }

                // 4. เก็บข้อมูลที่เลือกไว้ใน State เพื่อให้ปุ่ม "รับเข้า" หรือ "ปรับยอด" นำไปใช้ต่อได้
                // สำหรับ Server-side เราควรเก็บเฉพาะ ID หรือใช้ gridApi.getSelectedRows() หากต้องการข้อมูลตัววัตถุ
                State.selectedItems = gridApi.getSelectedRows();
            },
            
            clearSelection: () => gridApi.deselectAll(),
            
            toggleMobileDropdown: () => {
                const el = document.getElementById('mobile-dropdown');
                const isHidden = el.classList.contains('hidden');
                
                if (isHidden) {
                    const mbSearch = document.getElementById('mobile-sidebar-search');
                    if(mbSearch) mbSearch.value = ''; 
                    AppController.renderWHMenu('');
                }
                
                el.classList.toggle('hidden');
            },
            
            onFilterChanged: () => {
                const searchValue = document.getElementById('grid-search').value;
                if (gridApi) {
                    gridApi.refreshServerSide();
                    gridApi.setGridOption('quickFilterText', searchValue);
                }
            },
            filterWHMenuMobile: () => {
                const val = document.getElementById('mobile-sidebar-search').value;
                AppController.renderWHMenu(val);
            },

            filterWHMenu: () => {
                const val = document.getElementById('sidebar-search').value.toLowerCase();
                AppController.renderWHMenu(val);
            }
        };

        const getSelectedData = () => {
            if (!gridApi) return [];
            let selectedNodes = gridApi.getSelectedNodes();
            return selectedNodes.map(node => node.data).filter(data => data !== undefined);
        };

        // --- DRAWER & FORM MANAGER ---
        const DrawerManager = {
            mode: null,
            editingId: null,
            whId: null,
            searchTimer: null,
            
            open: async (mode, id = null, specificWhId = null) => {
                DrawerManager.mode = mode;
                DrawerManager.editingId = id;
                DrawerManager.whId = parseInt(specificWhId);
                
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

                let currentWhIds = []; // เก็บค่า wh_ids ที่ดึงจาก DB
                let productData = null;
                let whIds = [];
                if (specificWhId) {
                    whIds = [parseInt(specificWhId)];
                } else if (State.selectedMenuId !== 'all') {
                    whIds = [parseInt(State.selectedMenuId)];
                }

                if (mode === 'stock' && id) {
                    try {
                        const response = await axios.get(`handle_stock.php?action=get_product_detail&pd_id=${id}`);
                        if (response.data.success) {
                            productData = response.data.data;
                            currentWhIds = productData.wh_ids.map(val => parseInt(val));
                        }
                    } catch (error) {
                        console.error("Fetch Detail Error:", error);
                    }
                }
                // Render Content
                if (mode === 'stock') {
                    DrawerManager.renderStockForm(id, els, productData, currentWhIds, whIds);
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

            toggleWhSelection: (whId, whName) => {
                const inputEl = document.getElementById('f-wh-values');
                let selectedIds = [];
                try { selectedIds = JSON.parse(inputEl.value || '[]'); } catch(e) { selectedIds = []; }

                if (selectedIds.includes(whId)) { selectedIds = selectedIds.filter(id => id !== whId); } 
                else { selectedIds.push(whId); }

                inputEl.value = JSON.stringify(selectedIds);
                DrawerManager.refreshWhTags();
            },

            toggleWhDropdown: (event) => {
                event.stopPropagation(); 
                const menu = document.getElementById('wh-dropdown');
                const container = document.getElementById('multi-wh-container');
                if (!menu) return;
                menu.classList.toggle('hidden');

               
                if (!menu.classList.contains('hidden')) {
                    const closeHandler = (e) => {
                        if (!menu.contains(e.target) && !container.contains(e.target)) {
                            menu.classList.add('hidden');
                            document.removeEventListener('click', closeHandler);
                        }
                    };
                    setTimeout(() => document.addEventListener('click', closeHandler), 10);
                }
            },

            refreshWhTags: () => {
                const inputEl = document.getElementById('f-wh-values');
                const container = document.getElementById('multi-wh-container');
                const placeholder = document.getElementById('wh-placeholder');
                if (!inputEl || !container) return;

                let selectedIds = [];
                try { selectedIds = JSON.parse(inputEl.value || '[]').map(id => Number(id)); } catch (e) { selectedIds = []; }

                const oldTags = container.querySelectorAll('.wh-tag');
                oldTags.forEach(t => t.remove());

                if (selectedIds.length > 0) {
                    placeholder?.classList.add('hidden');
                    selectedIds.forEach(id => {
                        const wh = State.warehouses.find(w => Number(w.id) === id);
                        if (wh) {
                            const tag = document.createElement('div');
                            tag.className = 'wh-tag bg-primary/10 text-primary text-[11px] font-bold px-2 py-1 rounded-lg flex items-center gap-1 border border-primary/20 animate-in fade-in zoom-in duration-200';
                            tag.innerHTML = `${wh.wh_name} <i data-lucide="x" class="w-3 h-3 cursor-pointer hover:text-rose-500" onclick="event.stopPropagation(); DrawerManager.toggleWhSelection(${wh.id})"></i>`;
                            container.appendChild(tag);
                        }
                    });
                } else { placeholder?.classList.remove('hidden'); }

                const allItems = document.querySelectorAll('#wh-dropdown [onclick^="DrawerManager.toggleWhSelection"]');
                allItems.forEach(item => {
                    const match = item.getAttribute('onclick').match(/\d+/);
                    if (match) {
                        const id = Number(match[0]);
                        const isChecked = selectedIds.includes(id);
                        const checkbox = item.querySelector('.wh-checkbox');
                        const icon = item.querySelector('i');
                        const label = item.querySelector('span');
                        if (isChecked) {
                            checkbox?.classList.add('bg-primary', 'border-primary'); icon?.classList.remove('hidden'); label?.classList.add('text-primary', 'font-bold');
                        } else {
                            checkbox?.classList.remove('bg-primary', 'border-primary'); icon?.classList.add('hidden'); label?.classList.remove('text-primary', 'font-bold');
                        }
                    }
                });
                if (typeof lucide !== 'undefined') lucide.createIcons();
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
            autoGenCode: async () => {
                const catEl = document.getElementById('item_category');
                const nameEl = document.getElementById('f-name'); // ตัวแปรชื่อสินค้า
                const codeEl = document.getElementById('item_code'); // ตัวแปรหรัสสินค้า
                const statusEl = document.getElementById('code-gen-status');
                
                if (catEl && nameEl && codeEl && catEl.value && nameEl.value.trim() !== "") {
                    if (statusEl) statusEl.innerText = 'กำลังตรวจสอบ...';
                    
                    try {
                        const result = await StockAPI.checkInfoAndGenCode(catEl.value, nameEl.value);
                        
                        if(result && result.code) {
                            codeEl.value = result.code;
                            if (statusEl) {
                                statusEl.innerText = result.exists ? 'พบสินค้าเดิม (ใช้รหัสเดิม)' : 'สร้างรหัสใหม่อัตโนมัติ';
                                statusEl.className = result.exists ? 'text-[10px] text-amber-600' : 'text-[10px] text-emerald-600';
                            }
                            
                            if(result.exists && result.pd_id && !DrawerManager.editingId) {
                                Swal.fire({
                                    title: 'พบข้อมูลสินค้าเดิม',
                                    text: 'มีสินค้าชื่อนี้ในระบบแล้ว ระบบจะล้างค่าเพื่อให้ระบุใหม่',
                                    icon: 'info',
                                    confirmButtonText: 'ตกลง'
                                }).then((res) => {
                                    // แก้ไข ID ให้ตรงกับตัวแปรด้านบน
                                    if (nameEl) nameEl.value = '';
                                    if (catEl) catEl.value = '';
                                    if (codeEl) codeEl.value = ''; 
                                    if (statusEl) statusEl.innerText = '';
                                });
                            }
                        }
                    } catch (error) {
                        console.error("autoGenCode error:", error);
                    }
                }
            },

            renderStockForm: (id, els, product = null, currentWhIds = [], whIds) => {
                els.title.innerText = id ? 'แก้ไขข้อมูลสินค้า' : 'เพิ่มสินค้าใหม่';
                els.sub.innerText = 'จัดการรายละเอียดสินค้าและกำหนดจำนวนสต็อก';
                
                const item = (id && State.items) 
                    ? (State.items.find(i => i.pd_id == id) || {}) : { 
                    pd_details_head: '', pd_gen_code: '', pd_rps_id: '', wh_id: '', 
                    pd_qty: 0, pd_qty_all: 0, pd_qty_min: 5, pd_unit: 'ชิ้น', pd_price: 0, pd_details: '', pd_model: '' 
                };

                const currentItem = State.items.find(i => i.pd_id == id && i.wh_id == whIds);
                let curr_qty = currentItem ? currentItem.pd_qty : 0;
                let curr_qty_all = currentItem ? currentItem.pd_qty_all : 0;
                let curr_qty_min = currentItem ? currentItem.pd_qty_min : 0;
    
                const isEdit = !!id;
                const posIntAttrs = `type="number" min="0" oninput="this.value = Math.abs(Math.floor(this.value))"`;

                // --- ส่วนที่ปรับปรุง: แสดงชื่อคลังที่เลือกแก้ไข ---
                let stockInfoText = 'ตัวเลขเหล่านี้จะถูกนำไปใช้กับคลังสินค้าที่เลือกด้านบน';
                if (isEdit && currentWhIds.length > 0) {
                    // ดึงชื่อคลังทั้งหมดที่อยู่ใน currentWhIds ออกมา
                    const selectedNames = whIds
                        .map(id => {
                            const wh = State.warehouses.find(w => w.id == id);
                            return wh ? wh.wh_name : null;
                        })
                        .filter(name => name !== null); // กรองเอาเฉพาะที่มีชื่อ

                    if (selectedNames.length > 0) {
                        // ถ้ามีคลังเดียวแสดงชื่อคลังนั้นเลย ถ้ามีหลายคลังให้เชื่อมด้วยเครื่องหมายจุลภาค (,)
                        const namesString = selectedNames.join(', ');
                        stockInfoText = `ข้อมูลนี้จะบันทึกให้กับ: <span class="font-bold text-primary">${namesString}</span>`;
                    }
                }

                els.body.innerHTML = `
                    <div class="space-y-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                                <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                                    <i data-lucide="package" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-800">ข้อมูลสินค้าทั่วไป</h3>
                                    <p class="text-[10px] text-slate-400">การแก้ไขข้อมูลส่วนนี้จะมีผลกับข้อมูลสินค้าในทุกคลัง</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-2">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                                    <div class="col-span-2">
                                        <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">ชื่อสินค้า <span class="text-rose-500">*</span></label>
                                        <input type="text" id="f-name" value="${item.pd_details_head || ''}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20">
                                    </div>
                                    <div class="md:col-span-1">
                                        <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">ประเภทงาน <span class="text-rose-500">*</span></label>
                                        <select id="item_category" 
                                            onmousedown="if(!document.getElementById('f-name').value.trim()){ 
                                                Swal.fire('คำเตือน', 'กรุณาระบุชื่อสินค้าก่อนเลือกประเภท', 'warning');
                                                return false; 
                                            }"
                                            onchange="DrawerManager.autoGenCode()" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl bg-white">
                                            <option value="">เลือกประเภท</option>
                                            ${State.categories.map(cat => `<option value="${cat.rps_id}" ${item.pd_rps_id == cat.rps_id ? 'selected' : ''}>${cat.rps_name}</option>`).join('')}
                                        </select>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">รหัสสินค้า</label>
                                        <input type="text" id="item_code" readonly value="${item.pd_gen_code || ''}" class="w-full px-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl text-slate-500">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">รุ่น / ชนิด</label>
                                        <input type="text" id="f-model" value="${item.pd_model || ''}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">ราคา/หน่วย</label>
                                        <input type="number" step="0.01" min="0" id="f-price" value="${item.pd_price}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">หน่วย</label>
                                        <input type="text" id="f-unit" value="${item.pd_unit || 'ชิ้น'}" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl">
                                    </div>
                                </div>

                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">จัดเก็บที่คลัง <span class="text-rose-500">*</span></label>
                                    <div class="relative">
                                        <div id="multi-wh-container" class="min-h-[40px] w-full px-2 py-1.5 border border-slate-200 rounded-xl bg-white flex flex-wrap gap-1.5 cursor-pointer" onclick="DrawerManager.toggleWhDropdown(event)">
                                            <div id="wh-placeholder" class="text-slate-400 text-sm py-1 px-2 ${currentWhIds.length > 0 ? 'hidden' : ''}">+ เลือกคลังสินค้า</div>
                                        </div>
                                        <div id="wh-dropdown" class="hidden absolute z-50 w-full mt-1 bg-white border border-slate-200 shadow-xl rounded-xl max-h-48 overflow-y-auto p-1">
                                            ${State.warehouses.length > 0 ? State.warehouses.map(w => {
                                                const isChecked = currentWhIds.includes(parseInt(w.id));
                                                return `
                                                <div class="flex items-center gap-2 px-3 py-2 hover:bg-slate-50 rounded-lg cursor-pointer" onclick="DrawerManager.toggleWhSelection(${w.id}, '${w.wh_name}')">
                                                    <div class="wh-checkbox w-4 h-4 border-2 rounded flex items-center justify-center ${isChecked ? 'bg-primary border-primary' : 'border-slate-300'}">
                                                        <i data-lucide="check" class="w-3 h-3 text-white ${isChecked ? '' : 'hidden'}"></i>
                                                    </div>
                                                    <span class="text-sm">${w.wh_name}</span>
                                                </div>`;
                                            }).join('') : `
                                                <div class="flex flex-col items-center justify-center py-4 text-slate-400 gap-2 cursor-default">
                                                    <i data-lucide="alert-circle" class="w-5 h-5 opacity-50"></i>
                                                    <span class="text-xs">กรุณาเพิ่มคลังสินค้าก่อน</span>
                                                </div>
                                            `}
                                        </div>
                                    </div>
                                    <input type="hidden" id="f-wh-values" value='${JSON.stringify(currentWhIds)}'>
                                </div>

                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">รายละเอียดสินค้า</label>
                                    <textarea id="f-description" rows="2" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-xl">${item.pd_details || ''}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-xl border border-dashed border-slate-300">
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-8 h-8 rounded-full bg-white border border-slate-200 text-slate-600 flex items-center justify-center shadow-sm">
                                    <i data-lucide="layers" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-800">ตั้งค่าจำนวนสต็อก</h3>
                                    <p class="text-[10px] text-amber-600 mt-0.5">${stockInfoText}</p>
                                </div>
                            </div>

                            ${isEdit ? `
                                <div class="mb-4 flex items-start gap-2 text-[11px] text-amber-700 bg-amber-50 border border-amber-100 p-2.5 rounded-lg">
                                    <i data-lucide="info" class="w-4 h-4 mt-0.5 shrink-0 text-amber-500"></i>
                                    <span>ในโหมดแก้ไข <b>"คงเหลือ"</b> จะแสดงตามจริงในระบบ หากต้องการเพิ่ม/ลดสต็อก กรุณาใช้เมนู <b>"รับเข้า"</b> หรือ <b>"ปรับยอด"</b> เพื่อให้มีประวัติการทำรายการ</span>
                                </div>
                            ` : ''}

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <div class="bg-white p-2 rounded-lg border border-slate-200 shadow-sm">
                                    <label class="text-[10px] font-bold text-blue-500 uppercase block mb-1 text-center">คงเหลือ (Qty)</label>
                                    <input ${posIntAttrs} id="f-stock" value="${curr_qty}" ${isEdit ? 'disabled' : ''} 
                                        class="w-full text-center py-1 text-lg font-bold text-slate-700 outline-none ${isEdit ? 'bg-transparent cursor-not-allowed opacity-50' : 'bg-transparent focus:text-primary'}">
                                </div>
                                <div class="bg-white p-2 rounded-lg border border-slate-200 shadow-sm">
                                    <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1 text-center">สูงสุด (Max)</label>
                                    <input ${posIntAttrs} id="f-total" value="${curr_qty_all}" 
                                        class="w-full text-center py-1 text-lg font-bold text-slate-700 bg-transparent outline-none focus:text-primary">
                                </div>
                                <div class="bg-white p-2 rounded-lg border border-red-100 shadow-sm">
                                    <label class="text-[10px] font-bold text-rose-500 uppercase block mb-1 text-center">ขั้นต่ำ (Min)</label>
                                    <input ${posIntAttrs} id="f-min" value="${curr_qty_min}" 
                                        class="w-full text-center py-1 text-lg font-bold text-rose-600 bg-transparent outline-none focus:ring-0">
                                </div>
                            </div>
                        </div>
                    </div>`;
                
                // เรียกใช้งานไอคอน Lucide ใหม่หลังการ render
                lucide.createIcons();
            },

            renderReceiveForm: async (els) => { 
                const selected = await AppController.getSelectedItemsFull();
                els.title.innerText = 'ฟอร์มรับสินค้าเข้า';
                els.sub.innerText = `รายการที่เลือก ${selected.length} รายการ`;

                const posIntAttrs = `type="number" min="0" oninput="this.value = Math.abs(Math.floor(this.value))"`;

                els.body.innerHTML = `
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label-xs">เลขที่ใบรับเข้า</label>
                                <input type="text" id="r-po" class="input-base bg-slate-50 font-bold text-blue-600" placeholder="Loading..." readonly>
                            </div>
                            <div>
                                <label class="label-xs">วันที่</label>
                                <input type="date" id="r-date" value="${new Date().toISOString().split('T')[0]}" class="input-base">
                            </div>
                        </div>
                        <div class="space-y-2">
                            ${selected.map(item => `
                                <div class="p-3 bg-white border border-slate-100 rounded-xl flex justify-between items-center">
                                    <div class="truncate pr-2">
                                        <div class="text-xs font-bold text-slate-700 leading-tight">
                                            ${item.pd_details_head}
                                        </div>
                                        <span class="text-[9px] px-1.5 py-0.5 bg-primary/10 text-primary font-bold rounded">${item.wh_name}</span>
                                    </div>
                                    <div class="flex flex-col items-end shrink-0">
                                        <div class="flex items-center gap-2">
                                            <input ${posIntAttrs} data-id="${item.ps_id}" class="receive-input w-20 px-2 py-2 text-center bg-emerald-50 border border-emerald-100 rounded-xl outline-none font-bold text-emerald-600" placeholder="+0">
                                            <span class="text-[10px] font-bold text-slate-400 w-8">${item.pd_unit || 'หน่วย'}</span>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">หมายเหตุ</label>
                            <textarea id="r-remark" rows="2" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl outline-none" placeholder="ระบุหมายเหตุการรับเข้า"></textarea>
                        </div>
                    </div>`;
                
                lucide.createIcons();

                // 3. เรียก API ดึงเลข PO มาแสดงผล
                try {
                    const response = await axios.get(`handle_stock.php?action=get_next_po&type=IN&ag_id=${AG_ID}`);
                    if (response.data.success) {
                        document.getElementById('r-po').value = response.data.next_po;
                    }
                } catch (error) {
                    console.error("Error fetching PO:", error);
                    document.getElementById('r-po').placeholder = "ระบุเลขที่เอกสาร";
                }
            },
            
            renderAdjustForm: async (els) => {
                const selected = await AppController.getSelectedItemsFull();
                const posIntAttrs = `type="number" min="0" oninput="this.value = Math.abs(Math.floor(this.value))"`;
                els.title.innerText = 'ปรับยอดสต็อก';
                els.sub.innerText = `ตรวจสอบยอดจริง ${selected.length} รายการ`;
                els.body.innerHTML = `
                    <div class="space-y-4">
                        <div class="p-3 bg-amber-50 border border-amber-100 rounded-xl text-[11px] text-amber-700">ระบุจำนวนที่นับได้จริง ระบบจะปรับยอดให้อัตโนมัติ</div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label-xs">เลขที่ใบปรับยอด</label>
                                <input type="text" id="r-ad" class="input-base bg-slate-50 font-bold text-blue-600" placeholder="Loading..." readonly>
                            </div>
                            <div>
                                <label class="label-xs">วันที่ตรวจสอบยอด</label>
                                <input type="date" id="adj-date" 
                                    value="${new Date().toISOString().split('T')[0]}" 
                                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-amber-500/20 font-medium text-slate-700">
                            </div>
                        </div>
                        <div class="space-y-2">
                            ${selected.map(item => `
                                <div class="p-3 bg-white border border-slate-100 rounded-xl flex justify-between items-center">
                                    <div>
                                        <div class="text-xs font-bold text-slate-700 leading-tight">
                                            ${item.pd_details_head}
                                        </div>
                                        <span class="text-[9px] px-1.5 py-0.5 bg-primary/10 text-primary font-bold rounded">${item.wh_name}</span>
                                    </div>
                                    <div class="flex flex-col items-end shrink-0">
                                        <div class="flex items-center gap-2">
                                            <input ${posIntAttrs} data-id="${item.ps_id}" class="adjust-input w-20 px-2 py-2 text-center bg-amber-50 border border-amber-100 rounded-xl outline-none font-bold text-amber-600 focus:ring-2 focus:ring-amber-500/20" placeholder="${item.pd_qty}">
                                            <span class="text-[10px] font-bold text-slate-400 w-8">${item.pd_unit || 'หน่วย'}</span>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">หมายเหตุ</label>
                            <textarea id="adj-remark" rows="2" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl outline-none focus:ring-2 focus:ring-primary/20" placeholder="ระบุเหตุผลการปรับปรุงยอด (เช่น สินค้าชำรุด/ของหาย)"></textarea>
                        </div>
                    </div>`;
                lucide.createIcons();

                try {
                    const response = await axios.get(`handle_stock.php?action=get_next_po&type=ADJUST&ag_id=${AG_ID}`);
                    if (response.data.success) {
                        document.getElementById('r-ad').value = response.data.next_po;
                    }
                } catch (error) {
                    console.error("Error fetching PO:", error);
                    document.getElementById('r-ad').placeholder = "ระบุเลขที่เอกสาร";
                }
            },

            renderWHForm: (els) => {
                const id = DrawerManager.editingId;
                const wh = id ? State.warehouses.find(w => w.id == id) : null;

                els.title.innerText = id ? 'แก้ไขคลังสินค้า' : 'เพิ่มคลังสินค้าใหม่';
                els.sub.innerText = id ? `แก้ไขข้อมูล: ${wh ? wh.wh_name : 'ไม่พบชื่อ'}` : 'กรุณาระบุชื่อสถานที่จัดเก็บสินค้า';
                
                els.body.innerHTML = `
                    <div class="space-y-4">
                        <input type="hidden" id="editingId" value="${id || ''}">
                        
                        <div class="space-y-2">
                            <label class="text-[10px] font-bold text-slate-400 uppercase block ml-1">ชื่อคลังสินค้า / ตึก</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                    <i data-lucide="building-2" class="w-4 h-4"></i>
                                </div>
                                <input type="text" id="wh_name" 
                                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 outline-none transition-all"
                                    placeholder="เช่น คลังสินค้า A, อาคาร 1"
                                    value="${wh ? wh.wh_name : ''}">
                            </div>
                        </div>
                    </div>
                `;
                
                if (window.lucide) lucide.createIcons();
            },

            submit: async () => {
                const { mode, editingId, whId } = DrawerManager;
                let success = false;

                if (mode === 'stock') {
                    const id = document.getElementById('editingId')?.value;
                    const name = document.getElementById('f-name')?.value.trim();
                    const category = document.getElementById('item_category')?.value;
                    const whValuesStr = document.getElementById('f-wh-values')?.value || '[]';
                    const currentWhIds = JSON.parse(whValuesStr);
                    const statusEl = document.getElementById('f-status');
                    const statusValue = (statusEl && statusEl.type === 'checkbox') 
                                        ? (statusEl.checked ? '0' : '1') 
                                        : (statusEl?.value || '0');

                    if (!name) {
                        Swal.fire('ข้อมูลไม่ครบ', 'กรุณาระบุชื่อสินค้า', 'warning');
                        document.getElementById('f-name').focus();
                        return;
                    }

                    if (!category) {
                        Swal.fire('ข้อมูลไม่ครบ', 'กรุณาเลือกประเภทงาน', 'warning');
                        document.getElementById('item_category').focus();
                        return;
                    }

                    // ตรวจสอบว่าเลือกคลังสินค้าอย่างน้อย 1 แห่งหรือไม่
                    if (currentWhIds.length === 0) {
                        Swal.fire('ข้อมูลไม่ครบ', 'กรุณาเลือกคลังสินค้าจัดเก็บอย่างน้อย 1 แห่ง', 'warning');
                        // เน้นความสนใจไปที่ตัวเลือกคลัง
                        document.getElementById('multi-wh-container').classList.add('border-rose-500', 'ring-2', 'ring-rose-200');
                        setTimeout(() => {
                            document.getElementById('multi-wh-container').classList.remove('border-rose-500', 'ring-2', 'ring-rose-200');
                        }, 3000);
                        return;
                    }

                    const productData = {
                        pd_id: editingId,
                        pd_rps_id: document.getElementById('item_category').value,
                        pd_gen_code: document.getElementById('item_code').value,
                        pd_details_head: document.getElementById('f-name').value,
                        pd_details: document.getElementById('f-description').value,
                        pd_brand: '',
                        pd_model: document.getElementById('f-model').value,
                        pd_price: document.getElementById('f-price').value,
                        pd_unit: document.getElementById('f-unit').value,
                        ag_id: AG_ID,
                        // ส่งข้อมูลสต็อกและคลัง
                        wh_ids: currentWhIds,
                        wh_id: whId,
                        qty: document.getElementById('f-stock').value,
                        qty_all: document.getElementById('f-total').value,
                        qty_min: document.getElementById('f-min').value,
                        user_id: USER_ID,
                        ps_status: statusValue
                    };

                    const result = await StockAPI.saveProductAPI(productData);
                    
                    if (result.success) {
                        success = true;
                        Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', showConfirmButton: false, timer: 1000 });
                        await StockAPI.getAllProductsAPI();
                        DrawerManager.close();
                    } else {
                        Swal.fire('ผิดพลาด', result.message || 'บันทึกไม่สำเร็จ', 'error');
                    }

                } else if (mode === 'receive') {
					const remark = document.getElementById('r-remark').value;
					const docNo = document.getElementById('r-po').value;
					const date = document.getElementById('r-date').value;
				
					const receiveInputs = document.querySelectorAll('.receive-input');
					const itemsToReceive = [];
				
					receiveInputs.forEach((input, index) => {
						const qty = parseInt(input.value, 10) || 0;
						const psId = parseInt(input.dataset.id, 10);
				
						console.log(`receive row ${index + 1}`, {
							rawValue: input.value,
							qty,
							dataId: input.dataset.id,
							psId
						});
				
						if (qty > 0 && Number.isInteger(psId) && psId > 0) {
							itemsToReceive.push({
								ps_id: psId,
								qty: qty
							});
						}
					});
				
					console.log('itemsToReceive =', itemsToReceive);
				
					if (itemsToReceive.length === 0) {
						Swal.fire(
							'แจ้งเตือน',
							'กรุณาระบุจำนวนสินค้าที่ต้องการรับเข้าอย่างน้อย 1 รายการ',
							'warning'
						);
						return;
					}
				
					const payload = {
						items: itemsToReceive,
						doc_no: docNo,
						date: date,
						remark: remark,
						ag_id: AG_ID,
						user_id: USER_ID
					};
				
					console.log('stock_receive payload =', payload);
				
					try {
						const response = await axios.post(
							'handle_stock.php?action=stock_receive',
							payload,
							{
								headers: {
									'Content-Type': 'application/json'
								}
							}
						);
				
						console.log('stock_receive response =', response.data);
				
						if (response.data.success) {
							success = true;
							await StockAPI.getAllProductsAPI();
						} else {
							Swal.fire('Error', response.data.message || 'บันทึกรับเข้าไม่สำเร็จ', 'error');
						}
					} catch (err) {
						console.error('stock_receive error =', err);
						Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
					}
				} else if (mode === 'adjust') {
                    const remark = document.getElementById('adj-remark').value;
                    const adjDate = document.getElementById('adj-date').value;
                    
                    const itemsToAdjust = [];
                    document.querySelectorAll('.adjust-input').forEach(input => {
                        const newQty = input.value;
                        // ส่งเฉพาะรายการที่มีการกรอกตัวเลข
                        if(newQty !== '') {
                            itemsToAdjust.push({
                                ps_id: input.dataset.id,
                                qty: parseInt(newQty)
                            });
                        }
                    });

                    if (itemsToAdjust.length === 0) {
                        Swal.fire('แจ้งเตือน', 'กรุณาระบุยอดสินค้าจริงอย่างน้อย 1 รายการ', 'warning');
                        return;
                    }

                    try {
                        const response = await axios.post(`handle_stock.php?action=stock_adjust`, {
                            items: itemsToAdjust,
                            date: adjDate,
                            remark: remark,
                            ag_id: AG_ID,
                            user_id: USER_ID
                        });

                        if (response.data.success) {
                            success = true;
                            await StockAPI.getAllProductsAPI(); // โหลดข้อมูลสต็อกใหม่ทันที
                        } else {
                            Swal.fire('Error', response.data.message, 'error');
                        }
                    } catch (err) {
                        console.error(err);
                        Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                    }

                } else if (mode === 'wh') {
                    const nameEl = document.getElementById('wh_name');
                    const idEl = document.getElementById('editingId');
                    
                    if (!nameEl) return;

                    const name = nameEl.value.trim();
                    const editingId = idEl ? idEl.value : null;

                    if (!name) {
                        Swal.fire('คำเตือน', 'กรุณาระบุชื่อคลัง/ตึก', 'warning');
                        return;
                    }

                    // สร้างตัวแปรมารับค่าผลลัพธ์จาก API
                    let result; 
                    if (editingId && editingId !== "") {
                        result = await StockAPI.updateWarehouseAPI(editingId, name);
                    } else {
                        result = await StockAPI.addWarehouseAPI(name);
                    }

                    // ตรวจสอบค่าที่ส่งกลับมาจาก PHP
                    if (result && result.success) {
                        success = true; 
                    } else {
                        Swal.fire('แจ้งเตือน', result?.message || 'ไม่สามารถบันทึกได้', 'warning');
                        return; 
                    }
                }

                if(success) {
                    Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', showConfirmButton: false, timer: 1000 });
                    await StockAPI.getAllWarehousesAPI();
                    AppController.renderWHMenu();
                    AppController.selectMenu(State.selectedMenuId); // Refresh Grid
                    DrawerManager.close();
                } else {
                    Swal.fire('Warning', 'เกิดข้อผิดพลาด', 'warning');
                }
            }
        };

        // --- PRODUCT HISTORY MANAGER ---
        const HistoryManager = {
            pdId: null,
            whId: null,
            page: 1,
            pageSize: 20,
            total: 0,
            startDate: '',
            endDate: '',
            sortDir: 'desc', // desc = ล่าสุดก่อน, asc = เก่าสุดก่อน

            open: (pdId, whId, name = '', code = '') => {
                HistoryManager.pdId = pdId;
                HistoryManager.whId = whId;
                HistoryManager.page = 1;
                HistoryManager.startDate = '';
                HistoryManager.endDate = '';
                HistoryManager.sortDir = 'desc';
                HistoryManager.updateSortUI();

                const startEl = document.getElementById('history-start-date');
                const endEl = document.getElementById('history-end-date');
                if (startEl) startEl.value = '';
                if (endEl) endEl.value = '';

                // หาชื่อคลังจาก tb_wh_stock (โหลดไว้แล้วใน State.warehouses โดย id = wh_id)
                const wh = State.warehouses.find(w => w.id == whId);
                const whName = wh ? wh.wh_name : `คลัง #${whId}`;

                document.getElementById('history-title').innerText = name || 'ประวัติสินค้า';
                const subParts = [];
                if (code) subParts.push(`รหัส ${code}`);
                subParts.push(`คลัง: ${whName}`);
                document.getElementById('history-subtitle').innerText = subParts.join(' · ');

                const modal = document.getElementById('historyModal');
                const overlay = document.getElementById('historyOverlay');
                const panel = document.getElementById('historyPanel');
                modal.classList.remove('hidden');
                requestAnimationFrame(() => {
                    overlay.classList.add('opacity-100');
                    panel.classList.remove('opacity-0', 'scale-95');
                });

                HistoryManager.load();
                lucide.createIcons();
            },

            close: () => {
                const modal = document.getElementById('historyModal');
                const overlay = document.getElementById('historyOverlay');
                const panel = document.getElementById('historyPanel');
                overlay.classList.remove('opacity-100');
                panel.classList.add('opacity-0', 'scale-95');
                setTimeout(() => modal.classList.add('hidden'), 300);
            },

            applyFilter: () => {
                HistoryManager.startDate = document.getElementById('history-start-date').value || '';
                HistoryManager.endDate = document.getElementById('history-end-date').value || '';
                if (HistoryManager.startDate && HistoryManager.endDate && HistoryManager.startDate > HistoryManager.endDate) {
                    Swal.fire('ช่วงวันที่ไม่ถูกต้อง', 'วันที่เริ่มต้องไม่มากกว่าวันที่สิ้นสุด', 'warning');
                    return;
                }
                HistoryManager.page = 1;
                HistoryManager.load();
            },

            clearFilter: () => {
                document.getElementById('history-start-date').value = '';
                document.getElementById('history-end-date').value = '';
                HistoryManager.startDate = '';
                HistoryManager.endDate = '';
                HistoryManager.page = 1;
                HistoryManager.load();
            },

            toggleSort: () => {
                HistoryManager.sortDir = HistoryManager.sortDir === 'desc' ? 'asc' : 'desc';
                HistoryManager.page = 1;
                HistoryManager.updateSortUI();
                HistoryManager.load();
            },

            updateSortUI: () => {
                const isDesc = HistoryManager.sortDir === 'desc';
                const labelEl = document.getElementById('history-sort-label');
                const iconEl = document.getElementById('history-sort-icon');
                if (labelEl) labelEl.innerText = isDesc ? 'ล่าสุดก่อน' : 'เก่าสุดก่อน';
                if (iconEl) {
                    iconEl.innerHTML = `<i data-lucide="${isDesc ? 'arrow-down-wide-narrow' : 'arrow-up-narrow-wide'}" class="w-4 h-4"></i>`;
                    lucide.createIcons();
                }
            },

            changePageSize: () => {
                HistoryManager.pageSize = parseInt(document.getElementById('history-page-size').value, 10) || 20;
                HistoryManager.page = 1;
                HistoryManager.load();
            },

            prevPage: () => {
                if (HistoryManager.page > 1) { HistoryManager.page--; HistoryManager.load(); }
            },

            nextPage: () => {
                const maxPage = Math.max(1, Math.ceil(HistoryManager.total / HistoryManager.pageSize));
                if (HistoryManager.page < maxPage) { HistoryManager.page++; HistoryManager.load(); }
            },

            load: async () => {
                const body = document.getElementById('history-body');
                body.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-40 text-slate-400 gap-2">
                        <svg class="animate-spin w-6 h-6 text-secondary" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span class="text-xs">กำลังโหลดข้อมูล...</span>
                    </div>`;

                try {
                    const params = {
                        pd_id: HistoryManager.pdId,
                        wh_id: HistoryManager.whId,
                        page: HistoryManager.page,
                        limit: HistoryManager.pageSize,
                        sort_by: 'transaction_datetime',
                        sort_dir: HistoryManager.sortDir
                    };
                    if (HistoryManager.startDate) params.start_date = HistoryManager.startDate;
                    if (HistoryManager.endDate) params.end_date = HistoryManager.endDate;

                    const response = await axios.get('handle_stock_history.php', { params });
                    if (response.data.success) {
                        HistoryManager.total = response.data.total || 0;
                        HistoryManager.render(response.data.data || []);
                    } else {
                        body.innerHTML = `<div class="text-center py-10 text-rose-500 text-sm">${response.data.message || 'ไม่สามารถโหลดประวัติได้'}</div>`;
                    }
                } catch (error) {
                    console.error('History load error:', error);
                    body.innerHTML = `<div class="text-center py-10 text-rose-500 text-sm">เกิดข้อผิดพลาดในการเชื่อมต่อ</div>`;
                }
                HistoryManager.updatePager();
            },

            render: (list) => {
                const body = document.getElementById('history-body');
                const rows = list || [];

                if (!rows.length) {
                    body.innerHTML = `
                        <div class="flex flex-col items-center justify-center h-48 text-slate-300 gap-3">
                            <i data-lucide="inbox" class="w-10 h-10"></i>
                            <span class="text-sm text-slate-400">ไม่พบประวัติการเคลื่อนไหวในช่วงที่เลือก</span>
                        </div>`;
                    lucide.createIcons();
                    return;
                }

                const typeStyle = {
                    'IN':     { icon: 'download',   color: 'emerald', label: 'รับเข้า' },
                    'OUT':    { icon: 'upload',      color: 'rose',    label: 'เบิกออก' },
                    'ADJUST': { icon: 'rotate-cw',   color: 'amber',   label: 'ปรับยอด' }
                };

                const fmtDate = (dt) => {
                    if (!dt) return '-';
                    const d = new Date(dt.replace(' ', 'T'));
                    if (isNaN(d)) return dt;
                    return d.toLocaleString('th-TH', { day: '2-digit', month: 'short', year: '2-digit', hour: '2-digit', minute: '2-digit' });
                };
                const num = (v) => Number(v || 0).toLocaleString();

                const rowsHtml = rows.map(r => {
                    const st = typeStyle[r.transaction_type] || { icon: 'circle', color: 'slate', label: r.transaction_type };
                    const qtyIn = Number(r.qty_in || 0);
                    const qtyOut = Number(r.qty_out || 0);
                    const change = Number(r.qty_change || 0);
                    const changeHtml = change > 0
                        ? `<span class="text-emerald-600 font-bold">+${num(qtyIn)}</span>`
                        : `<span class="text-rose-500 font-bold">-${num(qtyOut)}</span>`;

                    const balanceHtml = (r.qty_balance !== null && r.qty_balance !== undefined && r.qty_balance !== '')
                        ? `<span class="text-slate-700 font-bold font-mono">${num(r.qty_balance)}</span>`
                        : `<span class="text-slate-300">-</span>`;

                    const locHtml = r.location_name
                        ? `<span class="inline-flex items-center gap-1 text-[10px] text-slate-500"><i data-lucide="map-pin" class="w-3 h-3"></i>${r.location_name}</span>` : '';
                    const problemHtml = r.problem_detail
                        ? `<div class="text-[11px] text-slate-500 mt-0.5 italic truncate">🛠️ ${r.problem_detail}</div>` : '';
                    const detailsHtml = r.details
                        ? `<div class="text-[11px] text-slate-400 mt-0.5 truncate">${r.details}</div>` : '';

                    return `
                        <div class="flex gap-3 py-3 border-b border-slate-100 last:border-0">
                            <div class="shrink-0 flex flex-col items-center pt-0.5">
                                <div class="w-8 h-8 rounded-full bg-${st.color}-50 text-${st.color}-600 flex items-center justify-center">
                                    <i data-lucide="${st.icon}" class="w-4 h-4"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-${st.color}-50 text-${st.color}-600 uppercase shrink-0">${st.label}</span>
                                        <span class="text-xs font-bold text-slate-700 truncate">${r.transaction_name || ''}</span>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-sm">${changeHtml} <span class="text-[10px] text-slate-400">${r.unit_name || ''}</span></div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between gap-2 mt-1">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                            <span class="text-[11px] text-slate-500">${fmtDate(r.transaction_datetime)}</span>
                                            ${r.doc_no ? `<span class="text-[10px] font-bold text-secondary">${r.doc_no}</span>` : ''}
                                            ${locHtml}
                                        </div>
                                        ${problemHtml}
                                        ${detailsHtml}
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-[9px] text-slate-400 uppercase">คงเหลือ</div>
                                        ${balanceHtml}
                                    </div>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-1">โดย ${r.user_id || 'ระบบ'}</div>
                            </div>
                        </div>`;
                }).join('');

                body.innerHTML = `<div class="bg-white rounded-xl border border-slate-100 px-4">${rowsHtml}</div>`;
                lucide.createIcons();
            },

            exportExcel: async () => {
                if (!HistoryManager.pdId) return;

                Swal.fire({
                    title: 'กำลังเตรียมไฟล์ Excel...',
                    text: 'กำลังรวบรวมข้อมูลทุกหน้า',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                try {
                    // ดึงข้อมูลทั้งหมดตามตัวกรอง (วนทีละ batch เพราะ endpoint จำกัด limit 500)
                    const batch = 500;
                    let offset = 0;
                    let total = Infinity;
                    let all = [];

                    while (offset < total) {
                        const params = {
                            pd_id: HistoryManager.pdId,
                            wh_id: HistoryManager.whId,
                            limit: batch,
                            offset: offset,
                            sort_by: 'transaction_datetime',
                            sort_dir: HistoryManager.sortDir
                        };
                        if (HistoryManager.startDate) params.start_date = HistoryManager.startDate;
                        if (HistoryManager.endDate) params.end_date = HistoryManager.endDate;

                        const res = await axios.get('handle_stock_history.php', { params });
                        if (!res.data.success) throw new Error(res.data.message || 'โหลดข้อมูลไม่สำเร็จ');

                        total = res.data.total || 0;
                        const rows = res.data.data || [];
                        all = all.concat(rows);
                        if (rows.length < batch) break;
                        offset += batch;
                    }

                    if (all.length === 0) {
                        Swal.fire('ไม่มีข้อมูล', 'ไม่พบประวัติสำหรับส่งออกในช่วงที่เลือก', 'info');
                        return;
                    }

                    HistoryManager.buildExcel(all);
                    Swal.close();
                } catch (error) {
                    console.error('Export error:', error);
                    Swal.fire('ผิดพลาด', 'ไม่สามารถสร้างไฟล์ Excel ได้', 'error');
                }
            },

            buildExcel: (rows) => {
                const esc = (v) => String(v == null ? '' : v)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                const fmtDate = (dt) => {
                    if (!dt) return '';
                    const d = new Date(String(dt).replace(' ', 'T'));
                    if (isNaN(d)) return dt;
                    return d.toLocaleString('th-TH', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                };
                // บังคับให้ Excel มองรหัส/เอกสารเป็นข้อความ (กันตัดเลข 0 / แปลงเป็น scientific)
                const textCell = (v) => `<td style="mso-number-format:'\\@'">${esc(v)}</td>`;
                const numCell = (v) => `<td style="mso-number-format:'0';">${Number(v || 0)}</td>`;

                const headers = ['วันที่/เวลา', 'ประเภท', 'เลขที่เอกสาร', 'รหัสสินค้า', 'ชื่อสินค้า',
                    'รับเข้า', 'เบิกออก', 'คงเหลือ', 'หน่วย', 'สถานที่', 'ปัญหา/งานซ่อม', 'หมายเหตุ', 'ผู้ทำรายการ'];

                const headHtml = headers.map(h =>
                    `<th style="background:#006B9F;color:#fff;border:1px solid #cbd5e1;padding:6px;font-weight:bold;">${h}</th>`).join('');

                const bodyHtml = rows.map(r => {
                    const balance = (r.qty_balance !== null && r.qty_balance !== undefined && r.qty_balance !== '')
                        ? `<td style="mso-number-format:'0';">${Number(r.qty_balance)}</td>` : '<td></td>';
                    return `<tr>
                        ${textCell(fmtDate(r.transaction_datetime))}
                        <td>${esc(r.transaction_name)}</td>
                        ${textCell(r.doc_no)}
                        ${textCell(r.product_code)}
                        <td>${esc(r.product_name)}</td>
                        ${numCell(r.qty_in)}
                        ${numCell(r.qty_out)}
                        ${balance}
                        <td>${esc(r.unit_name)}</td>
                        <td>${esc(r.location_name)}</td>
                        <td>${esc(r.problem_detail)}</td>
                        <td>${esc(r.details)}</td>
                        <td>${esc(r.user_id)}</td>
                    </tr>`;
                }).join('');

                const title = document.getElementById('history-title')?.innerText || 'ประวัติสินค้า';
                const html = `<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                    <head><meta charset="UTF-8">
                    <!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>
                    <x:Name>History</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
                    </x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->
                    </head><body>
                    <table border="1" style="border-collapse:collapse;font-family:'Kanit',sans-serif;font-size:12px;">
                        <thead><tr>${headHtml}</tr></thead>
                        <tbody>${bodyHtml}</tbody>
                    </table></body></html>`;

                const blob = new Blob(['\uFEFF' + html], { type: 'application/vnd.ms-excel;charset=utf-8' });
                const now = new Date();
                const dateStr = `${now.getFullYear()}${String(now.getMonth() + 1).padStart(2, '0')}${String(now.getDate()).padStart(2, '0')}`;
                const safeTitle = String(title).replace(/[\\/:*?"<>|]/g, '_').trim();
                const fileName = `ประวัติสินค้า_${safeTitle}_${dateStr}.xls`;

                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                setTimeout(() => URL.revokeObjectURL(link.href), 1000);
            },

            updatePager: () => {
                const size = HistoryManager.pageSize;
                const total = HistoryManager.total;
                const page = HistoryManager.page;
                const maxPage = Math.max(1, Math.ceil(total / size));
                const from = total === 0 ? 0 : (page - 1) * size + 1;
                const to = Math.min(page * size, total);

                document.getElementById('history-range').innerText = total === 0 ? 'ไม่มีรายการ' : `${from}-${to} จาก ${total}`;
                document.getElementById('history-page-info').innerText = `${page} / ${maxPage}`;
                document.getElementById('history-prev').disabled = page <= 1;
                document.getElementById('history-next').disabled = page >= maxPage;
            }
        };
        window.HistoryManager = HistoryManager;

        window.addEventListener('load', AppController.init);
        window.addEventListener('load', () => {
    const params = new URLSearchParams(window.location.hash.split('?')[1]);
    const pd_id = params.get('pd_id');
    const wh_id = params.get('wh_id');

    if (pd_id) {
        setTimeout(() => {
            // 1. ถ้ามี wh_id ให้สั่งเปลี่ยนคลังก่อน
            if (wh_id && typeof AppController !== 'undefined') {
                AppController.selectMenu(wh_id);
            }

            // 2. เปิด Modal สินค้า (สมมติว่ามีฟังก์ชันเปิด)
            DrawerManager.open('stock_detail'); 

            // 3. (ถ้าจำเป็น) สั่งให้ระบบค้นหา/Focus สินค้านั้นในตาราง
            // เช่น gridApi.setQuickFilter(pd_id);
            console.log("Focusing product:", pd_id, "at warehouse:", wh_id);
        }, 500);
    }
});

        /* =========================================================
           มือถือ: การ์ดจากแถวในหน้าปัจจุบันของตาราง (สต็อก / รายงานรับเข้า / รายงานปรับยอด)
           แตะวงกลมเพื่อเลือกสินค้า (ใช้แถบ รับเข้า/ปรับยอด เดิม) / ปุ่มเรียกฟังก์ชันเดียวกับตาราง
           ========================================================= */
        const MobileStock = (() => {
            const isMobile = () => window.innerWidth < 768;
            const $ = id => document.getElementById(id);
            const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const thDate = v => v ? new Date(v).toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' }) : '-';
            let nodes = [];

            function stockCard(d, i, node) {
                const stock = parseInt(d.pd_qty) || 0, total = parseInt(d.pd_qty_all) || 0, min = parseInt(d.pd_qty_min) || 0;
                const low = stock <= min;
                const pct = total > 0 ? Math.min(stock / total * 100, 100) : 0;
                const active = d.ps_status === '0';
                const sel = node.isSelected();
                return `
                    <article class="ms-card p-3 ${sel ? 'is-selected' : ''}">
                        <div class="flex items-start gap-3">
                            <button type="button" data-act="select" data-i="${i}" class="flex-none mt-0.5 w-6 h-6 rounded-full border-2 ${sel ? 'bg-sky-600 border-sky-600 text-white' : 'border-slate-300 bg-white'} flex items-center justify-center" aria-label="เลือกสินค้า">${sel ? '<i data-lucide="check" class="w-3.5 h-3.5"></i>' : ''}</button>
                            <div class="min-w-0 flex-1">
                                <div class="text-[14px] font-bold text-slate-800 leading-snug">${esc(d.pd_details_head)}</div>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    <span class="text-[10px] px-1.5 py-0.5 bg-slate-100 text-slate-500 font-bold rounded">${esc(d.pd_gen_code)}</span>
                                    ${d.pd_model ? `<span class="text-[10px] px-1.5 py-0.5 bg-purple-50 text-purple-600 font-bold rounded">${esc(d.pd_model)}</span>` : ''}
                                    ${d.rps_name ? `<span class="text-[10px] px-1.5 py-0.5 bg-sky-50 text-sky-700 rounded">${esc(d.rps_name)}</span>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="mt-2.5 grid grid-cols-2 gap-2 text-[12px]">
                            <div class="rounded-xl bg-slate-50 px-2.5 py-2">
                                <div class="text-slate-400 text-[11px]">คงเหลือ / ทั้งหมด</div>
                                <div class="font-bold ${low ? 'text-rose-600' : 'text-slate-800'}">${stock.toLocaleString()} / ${total.toLocaleString()} <span class="font-normal text-slate-400">${esc(d.pd_unit || '')}</span></div>
                                <div class="mt-1 h-1 rounded-full bg-slate-200 overflow-hidden"><div class="h-full ${low ? 'bg-rose-500' : 'bg-emerald-500'}" style="width:${pct}%"></div></div>
                            </div>
                            <div class="rounded-xl bg-slate-50 px-2.5 py-2">
                                <div class="text-slate-400 text-[11px]">คลัง · ราคา</div>
                                <div class="font-semibold text-slate-700 truncate">${esc(d.wh_name || '-')}</div>
                                <div class="text-slate-600">${d.pd_price ? parseFloat(d.pd_price).toLocaleString('th-TH', { minimumFractionDigits: 2 }) : '0.00'} ฿</div>
                            </div>
                        </div>
                        ${low ? '<div class="mt-2 text-[11.5px] font-semibold text-rose-600 inline-flex items-center gap-1"><i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i>ต่ำกว่าจุดสั่งซื้อขั้นต่ำ (' + min.toLocaleString() + ')</div>' : ''}
                        <div class="mt-2.5 pt-2.5 border-t border-slate-100 flex items-center gap-2">
                            ${d.ps_id ? `<label class="flex-1 inline-flex items-center gap-2 cursor-pointer select-none">
                                <input type="checkbox" class="sr-only peer" ${active ? 'checked' : ''} data-act="status" data-i="${i}">
                                <span class="relative w-10 h-5 rounded-full bg-slate-200 peer-checked:bg-emerald-500 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:w-4 after:h-4 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-5"></span>
                                <span class="text-[12.5px] ${active ? 'text-emerald-700 font-semibold' : 'text-slate-400'}">${active ? 'เปิดใช้งาน' : 'ปิดใช้งาน'}</span>
                            </label>` : '<span class="flex-1"></span>'}
                            <button type="button" data-act="history" data-i="${i}" class="ms-act border border-slate-200 bg-white text-slate-500" aria-label="ประวัติสินค้า" title="ประวัติสินค้า"><i data-lucide="history" class="w-4 h-4"></i></button>
                            <button type="button" data-act="edit" data-i="${i}" class="ms-act bg-[#006b9f] text-white" aria-label="แก้ไข" title="แก้ไข"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                        </div>
                    </article>`;
            }

            function reportCard(d, isPo) {
                const items = d.items || [];
                const rows = items.map(it => {
                    const diff = Number(it.qty_change) || 0;
                    const sign = diff > 0 ? '+' : '';
                    const tone = isPo || diff > 0 ? 'text-emerald-600' : (diff < 0 ? 'text-rose-600' : 'text-slate-500');
                    return `<div class="flex items-start justify-between gap-2 py-1.5 border-b border-slate-100 last:border-0">
                        <div class="min-w-0"><div class="text-[12.5px] font-medium text-slate-700 truncate">${esc(it.pd_details_head)}</div>
                            <div class="text-[10.5px] text-slate-400">${esc(it.pd_gen_code)} · ${esc(it.wh_name || '')}</div></div>
                        <div class="flex-none text-[13px] font-bold ${tone}">${sign}${diff.toLocaleString()} <span class="text-[10px] font-normal text-slate-400">${esc(it.pd_unit || '')}</span></div>
                    </div>`;
                }).join('');
                return `
                    <article class="ms-card p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div><div class="text-[14px] font-bold text-sky-700">${esc(d.doc_no || '-')}</div>
                                <div class="text-[11.5px] text-slate-500">${thDate(d.trans_date)} · ${esc(d.user_id || '-')}</div></div>
                            <span class="flex-none rounded-full px-2 py-0.5 text-[11px] font-bold ${isPo ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'}">${isPo ? 'รับเข้า' : 'ปรับยอด'} ${items.length} รายการ</span>
                        </div>
                        ${d.details ? `<div class="mt-1.5 text-[12.5px] text-slate-600">${esc(d.details)}</div>` : ''}
                        <div class="mt-2 rounded-xl border border-slate-100 px-2.5">${rows || '<div class="py-2 text-[12px] text-slate-400">ไม่มีรายการ</div>'}</div>
                    </article>`;
            }

            function render() {
                if (!isMobile() || !gridApi) return;
                const size = gridApi.paginationGetPageSize();
                const page = gridApi.paginationGetCurrentPage();
                const pages = gridApi.paginationGetTotalPages();
                const totalRows = gridApi.paginationGetRowCount();
                const menu = State.selectedMenuId;
                const isReport = menu === 'report-po' || menu === 'report-adjust';
                nodes = [];
                for (let i = page * size; i < Math.min((page + 1) * size, totalRows); i++) {
                    const n = gridApi.getDisplayedRowAtIndex(i);
                    if (n && n.data) nodes.push(n);
                }
                $('msCards').innerHTML = nodes.length
                    ? nodes.map((n, i) => isReport ? reportCard(n.data, menu === 'report-po') : stockCard(n.data, i, n)).join('')
                    : `<div class="py-12 flex flex-col items-center gap-2 text-slate-400 text-[13px]"><i data-lucide="${totalRows ? 'loader-2' : 'inbox'}" class="w-8 h-8 ${totalRows ? 'animate-spin' : ''}"></i>${totalRows ? 'กำลังโหลด...' : 'ไม่พบรายการ'}</div>`;
                $('msPage').innerHTML = totalRows ? `หน้า <b>${page + 1}</b> / ${Math.max(pages, 1)} · ${totalRows.toLocaleString()} รายการ` : '-';
                $('msPrev').disabled = page <= 0;
                $('msNext').disabled = page >= pages - 1;
                lucide.createIcons();
            }

            function onClick(e) {
                const el = e.target.closest('[data-act]');
                if (!el || el.dataset.act === 'status') return;
                const n = nodes[+el.dataset.i];
                if (!n) return;
                const d = n.data;
                if (el.dataset.act === 'select') n.setSelected(!n.isSelected());
                else if (el.dataset.act === 'history') HistoryManager.open(d.pd_id, d.wh_id, d.pd_details_head || '', d.pd_gen_code || '');
                else if (el.dataset.act === 'edit') DrawerManager.open('stock', d.pd_id, d.wh_id);
            }

            let timer = null;
            const later = () => { clearTimeout(timer); timer = setTimeout(render, 30); };
            return {
                attach() {
                    if (!gridApi) return;
                    ['modelUpdated', 'paginationChanged', 'selectionChanged', 'rowDataUpdated'].forEach(ev => gridApi.addEventListener(ev, later));
                    $('msCards').addEventListener('click', onClick);
                    $('msCards').addEventListener('change', e => {
                        const el = e.target.closest('[data-act="status"]');
                        const n = el && nodes[+el.dataset.i];
                        if (n && n.data.ps_id) updateStockStatus(n.data.ps_id, el.checked);
                    });
                    $('msPrev').addEventListener('click', () => { gridApi.paginationGoToPreviousPage(); $('msCards').scrollTop = 0; });
                    $('msNext').addEventListener('click', () => { gridApi.paginationGoToNextPage(); $('msCards').scrollTop = 0; });
                    window.addEventListener('resize', later);
                    later();
                }
            };
        })();
    </script>
    
</body>
</html>