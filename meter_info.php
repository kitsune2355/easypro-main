<?php 
@session_start();
include "config_ctrl/checksession.php"; 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการมิเตอร์ - Modern Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-enterprise/dist/ag-grid-enterprise.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/img-carousel.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100..900;1,100..900&family=Noto+Sans+Thai:wght@100..900&display=swap" rel="stylesheet">

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
                        glass: 'rgba(255, 255, 255, 0.6)',
                        glassBorder: 'rgba(255, 255, 255, 0.4)',
                    },
                    boxShadow: {
                        'glass': '0 8px 32px 0 rgba(0, 107, 159, 0.1)',
                    }
                }
            }
        }
    </script>
    <style>
        .sidebar-active {
            background: white;
            box-shadow: 0 4px 12px rgba(0, 107, 159, 0.08);
            border-color: rgba(0, 107, 159, 0.2);
        }

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
            --ag-font-family: 'Noto Sans Thai', sans-serif;
            --ag-border-radius: 12px;
            --ag-header-background-color: #f8fafc;
            --ag-row-hover-color: rgba(4, 173, 255, 0.05);
            --ag-selected-row-background-color: rgba(4, 173, 255, 0.08);
            border: none !important;
        }
        .ag-header { border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important; }
        .ag-root-wrapper { border: none !important; background: transparent !important; }

        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        .info-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1rem;
            transition: all 0.2s;
        }
        .info-card:hover {
            border-color: #006B9F;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        /* Sidebar Transition */
        #main-sidebar {
            transition: transform 0.3s ease-in-out;
        }
        /* แถบแท็บเลื่อนแนวนอน - ซ่อน scrollbar แต่ยังเลื่อนได้ */
        .tabs-scroll {
            scrollbar-width: none;          /* Firefox */
            -ms-overflow-style: none;       /* IE/Edge */
            scroll-behavior: smooth;
        }
        .tabs-scroll::-webkit-scrollbar {   /* Chrome/Safari */
            display: none;
            width: 0;
            height: 0;
        }
    </style>
</head>
<body class="h-screen flex flex-col overflow-y-auto lg:overflow-hidden">

    <header class="shrink-0 flex flex-col sm:flex-row justify-between items-center px-3 py-4 border-b border-slate-200 bg-white/80 backdrop-blur-md sticky top-0 z-40">
        <div class="flex items-center gap-3 w-full sm:w-auto mb-4 sm:mb-0">
            <div class="p-2 sm:p-2.5 bg-primary rounded-xl shadow-lg shadow-sky-100 text-white">
                <i data-lucide="gauge" class="w-5 h-5 sm:w-6 sm:h-6 text-white"></i>
            </div>
            <div class="flex flex-col">
                <h1 class="text-lg font-display font-bold leading-none">
                    Meter Management System
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">ระบบจัดการมิเตอร์</p>
            </div>
        </div>

        <div class="w-full sm:w-auto">
            <button onclick="openDrawer()" class="w-full sm:w-auto btn-gradient flex items-center justify-center gap-2 px-3 md:px-5 py-2.5 rounded-xl text-sm font-display font-bold shadow-lg active:scale-95 transition-all">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span id="btn-add-text">เพิ่มบันทึก</span>
            </button>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden relative">
        <aside id="main-sidebar" class="fixed lg:static inset-y-0 left-0 w-64 md:w-72 border-r border-slate-200 flex flex-col bg-white z-40 -translate-x-full lg:translate-x-0 h-full overflow-hidden">
            <div class="px-3 py-4 space-y-2 shrink-0">
                <div class="relative flex items-center bg-slate-200/50 p-1 rounded-xl">
                    <nav id="nav-tabs-container" class="grid grid-cols-2 flex-1 gap-1"></nav>
                    <div id="more-tabs-wrapper" class="relative hidden">
                        <button onclick="toggleMoreTabs()" id="more-tabs-btn" class="px-2 py-1.5 text-[11px] font-bold text-slate-500 hover:text-primary flex items-center gap-1">
                            <span id="more-count">+0</span>
                        </button>
                        <div id="more-tabs-dropdown" class="hidden absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-xl z-50 p-1"></div>
                    </div>
                </div>
                
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="search-input" onkeyup="filterMeters()" placeholder="ค้นหาชื่อมิเตอร์..." class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-secondary/50 outline-none transition-all">
                </div>
            </div>

            <div id="meter-list" class="flex-1 overflow-y-auto px-3 space-y-1 pb-6 min-h-0">
                <div class="p-4 text-center text-slate-400 text-sm">กำลังโหลดข้อมูล...</div>
            </div>
        </aside>

        <main class="flex-1 min-w-0 flex flex-col overflow-y-auto lg:overflow-hidden p-4 space-y-4">
            
            <div class="space-y-4 shrink-0">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-center">
                    <div class="relative group w-full">
                        <div onclick="toggleMobileDropdown()" class="flex items-center gap-4 cursor-pointer lg:cursor-default rounded-2xl bg-white lg:bg-transparent transition-colors w-full">
                            <div id="status-indicator" class="w-12 h-12 md:w-14 md:h-14 rounded-2xl flex items-center justify-center shadow-inner shrink-0 bg-slate-50">
                                <i id="header-icon" data-lucide="box" class="w-6 h-6 md:w-7 md:h-7"></i>
                            </div>

                            <div class="flex flex-col flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h2 id="current-meter-name" class="text-xl md:text-2xl font-display font-bold text-slate-800 leading-tight truncate">
                                        โปรดเลือกมิเตอร์
                                    </h2>
                                    
                                    <i data-lucide="chevron-down" class="w-5 h-5 text-slate-400 lg:hidden transition-transform shrink-0 mr-2" id="dropdown-chevron"></i>
                                </div>
                                
                                <div class="flex items-center gap-2 text-slate-500 text-xs md:text-sm mt-0.5 truncate">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-primary shrink-0"></i>
                                    <span id="current-meter-location">--</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-x-1 text-slate-500 text-xs mt-0.5">
                                    <span>ค่าที่ยอมรับได้ </span><span id="meter-limit-percent" class="text-sky-700 font-medium">0.0</span><span>%</span>
                                    <p>ค่าสูงสุดหน้าปัด</p><span id="current-meter-max" class="text-sky-700 font-medium">0.0</span><span>หน่วย</span>
                                </div>
                            </div>
                        </div>

                        <div id="mobile-meter-dropdown" class="hidden absolute top-full left-0 mt-2 w-full min-w-[280px] bg-white border border-slate-200 rounded-2xl shadow-2xl z-[100] max-h-[60vh] overflow-hidden flex flex-col lg:hidden">
                            <div class="p-3 border-b bg-slate-50/50 space-y-3">
                                <div class="relative">
                                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="text" id="search-input-mobile" onkeyup="filterMetersMobile()" placeholder="ค้นหามิเตอร์..." class="w-full pl-10 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm outline-none focus:ring-2 focus:ring-primary/20">
                                </div>

                                <div class="relative flex items-center bg-slate-200/50 p-1 rounded-xl">
                                    <nav id="nav-tabs-dropdown-container" class="grid grid-cols-2 flex-1 gap-1"></nav>
                                    <div id="more-tabs-wrapper-mobile" class="relative hidden">
                                        <button onclick="toggleMoreTabsMobile()" id="more-tabs-btn-mobile" class="px-2 py-1.5 text-[11px] font-bold text-slate-500 flex items-center gap-1">
                                            <span id="more-count-mobile">+0</span>
                                        </button>
                                        <div id="more-tabs-dropdown-mobile" class="hidden absolute right-0 top-full mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-2xl z-[110] p-1">
                                            </div>
                                    </div>
                                </div>
                            </div>

                            <div id="meter-list-mobile" class="overflow-y-auto p-2 space-y-1">
                                </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-12 gap-1 mb-4">
                        <div class="col-span-4">
                            <select id="filter-month" onchange="fetchMeterData(currentCat)" class="w-full bg-white border border-slate-200 rounded-lg text-xs p-2 outline-none focus:ring-1 focus:ring-primary">
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
                            <select id="filter-year" onchange="fetchMeterData(currentCat)" class="w-full bg-white border border-slate-200 rounded-lg text-xs p-2 outline-none focus:ring-1 focus:ring-primary">
                                <?php
                                $currentYear = date('Y');
                                for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                    $selected = ($y == $currentYear) ? 'selected' : '';
                                    echo "<option value='$y' $selected>".($y + 543)."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-span-3">
                            <button onclick="resetToCurrentDate()" class="w-full h-full flex items-center justify-center gap-1 bg-secondary/20 text-primary hover:bg-secondary/30 rounded-lg text-xs font-bold transition-all shadow-sm">
                                <i data-lucide="calendar-days" class="w-4 h-4"></i>
                                ปัจจุบัน
                            </button>
                        </div>
                        <div class="col-span-1">
                            <button onclick="printData()" id="selected-meter-id" class="w-full h-full flex items-center justify-center gap-1 bg-black hover:bg-black/80 text-white rounded-lg text-xs font-bold transition-all shadow-sm">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    
                </div>
            </div>

            <div class="flex-1 overflow-hidden min-h-[420px] lg:min-h-[300px]">
                <div class="bg-white h-full w-full rounded-2xl border border-slate-200 overflow-hidden shadow-sm flex flex-col">
                    <div id="myGrid" class="ag-theme-alpine flex-1 w-full"></div>
                </div>
            </div>

            <div class="mt-6 shrink-0 lg:shrink bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-3">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="flex items-center gap-2 px-2 py-1 rounded-lg bg-red-50 border border-red-100">
                                <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                                <span class="text-xs font-bold text-red-700">ใช้พลังงานเกินเกณฑ์ที่รับได้</span>
                            </div>
                            
                            <div class="flex items-center gap-2 px-2 py-1 rounded-lg bg-green-50 border border-green-100">
                                <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                <span class="text-xs font-bold text-green-700">ประหยัดพลังงานลงจากรอบที่แล้ว</span>
                            </div>

                            <div class="flex items-center gap-2 px-2 py-1 rounded-lg bg-slate-50 border border-slate-100">
                                <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                                <span class="text-xs font-bold text-slate-600">ปกติ อยู่ในเกณฑ์ที่ยอมรับได้</span>
                            </div>
                        </div>

                        <button onclick="toggleFormula()" class="flex items-center gap-1.5 text-xs font-medium text-blue-600 hover:text-blue-700 transition-colors bg-blue-50 px-2 py-1 rounded-lg border border-blue-100">
                            <i data-lucide="info" class="w-3.5 h-3.5"></i>
                            <span>ดูสูตรคำนวณ</span>
                        </button>
                    </div>

                    <div id="formula-container" class="hidden mt-3 p-3 bg-slate-50 rounded-lg border border-slate-200 border-dashed animate-in fade-in slide-in-from-top-2 duration-300">
                        <div class="flex items-center gap-2 text-slate-500 text-xs mb-1">
                            <i data-lucide="calculator" class="w-3.5 h-3.5"></i>
                            <span class="font-semibold uppercase tracking-wider">สูตรการคำนวณ</span>
                        </div>
                        <code class="text-sky-600 font-bold text-xs block ml-5">
                            % ที่ยอมรับได้ = ((Units ปัจจุบัน - Units รอบก่อนหน้า) ÷ Units รอบก่อนหน้า) × 100
                        </code>
                    </div>
                </div>
                
        </main>

        <div id="drawer-overlay" onclick="closeDrawer()" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[50] hidden opacity-0 transition-opacity duration-300"></div>
        <div id="meter-drawer" class="fixed top-0 right-0 h-full w-full sm:w-[500px] lg:w-[600px] bg-white shadow-2xl z-[60] translate-x-full transition-transform duration-300 flex flex-col">
            <div class="p-4 border-b flex justify-between items-center bg-white sticky top-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-[--color-primary] flex items-center justify-center text-white shadow-lg shadow-sky-100">
                        <i data-lucide="plus" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 id="drawer-title" class="text-lg font-display font-bold text-slate-800">เพิ่มบันทึก</h3>
                        <p class="text-xs text-slate-500 mt-0.5">ระบุค่าที่อ่านได้จากมิเตอร์จริง</p>
                    </div>
                </div>
                <button onclick="closeDrawer()" class="w-12 h-12 rounded-full border border-slate-100 flex items-center justify-center text-slate-400 hover:rotate-90 hover:bg-slate-50 transition-all duration-300">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                <div class="grid grid-cols-1 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-600">วันที่บันทึก</label>
                        <input type="date" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 outline-none">
                    </div>
                </div>

                <div id="drawer-dynamic-fields" class="space-y-4">
                    </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-bold text-slate-600">หมายเหตุ</label>
                    <textarea rows="3" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 outline-none" placeholder="ข้อมูลเพิ่มเติม..."></textarea>
                </div>
            </div>

            <div class="flex-none bg-white border-t px-6 py-4 flex items-center justify-end gap-3">
                <button onclick="saveData()" class="btn-gradient px-4 py-2 rounded-2xl font-bold shadow-xl shadow-sky-100 transition-all transform active:scale-95 flex items-center gap-2">
                    <i data-lucide="check" class="w-5 h-5"></i>
                    <span>บันทึกข้อมูล</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        const refreshIcons = () => lucide.createIcons();
        
        // --- Configuration ---
        const urlParams = new URLSearchParams(window.location.search);
        const AG_ID = <?php echo $sess_user_agency_es ?>;
        const USER_ID = '<?php echo $sess_user_id; ?>';
        
        let gridApi = null;
        let currentCat = 'wt';
        let currentMeterId = null;
        let currentMeterList = [];
        let allRounds = [];
        let isPhotoDeleted = false;
        let editingRecordId = null;

        const METER_CONFIGS = {
            wt: {
                name: 'มิเตอร์น้ำ',
                icon: 'droplets',
                colorClass: 'sky',
                bg: 'bg-sky-600',
                mobileText: 'text-sky-600',
                border: 'border-sky-200',
                unit: 'm³',
                label: 'เพิ่มบันทึกมิเตอร์น้ำ',
                type: 'simple'
            },
            et: {
                name: 'มิเตอร์ไฟ',
                icon: 'zap',
                colorClass: 'amber',
                bg: 'bg-amber-500',
                mobileText: 'text-amber-500',
                border: 'border-amber-200',
                unit: 'kW/h',
                label: 'เพิ่มบันทึกมิเตอร์ไฟ',
                type: 'simple'
            },
            tou: {
                name: 'มิเตอร์ TOU',
                icon: 'clock-3',
                colorClass: 'purple',
                bg: 'bg-purple-500',
                mobileText: 'text-purple-500',
                border: 'border-purple-200',
                unit: 'kW/h',
                label: 'เพิ่มบันทึกมิเตอร์ TOU',
                type: 'complex' // ประเภทที่มีหลายฟิลด์
            },
            gt: {
                name: 'มิเตอร์แก๊ส',
                icon: 'flame',
                colorClass: 'orange',
                bg: 'bg-orange-500',
                mobileText: 'text-orange-500',
                border: 'border-orange-200',
                unit: 'm³',
                label: 'เพิ่มบันทึกมิเตอร์แก๊ส',
                type: 'simple'
            }
        };

        function toggleFormula() {
            const container = document.getElementById('formula-container');
            if (container.classList.contains('hidden')) {
                container.classList.remove('hidden');
                // รีเฟรชไอคอนหลังจากแสดงผล (ถ้าจำเป็น)
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } else {
                container.classList.add('hidden');
            }
        }

        async function exportToExcelWithImages() {
            const workbook = new ExcelJS.Workbook();
            const worksheet = workbook.addWorksheet('Meter Data');
            const config = METER_CONFIGS[currentCat];

            // 1. กำหนดหัวตาราง (Headers) ตาม Column Definitions ที่มีอยู่
            let columns = [];

            if (currentCat === 'tou') {
                columns = [
                    { header: 'วันที่', key: 'date', width: 15 },
                    { header: 'เวลา', key: 'time', width: 10 },
                    { header: 'On Peak (011)', key: 'on_peak', width: 15 },
                    { header: 'Off Peak (012)', key: 'off_peak', width: 15 },
                    { header: 'Holiday (013)', key: 'holiday', width: 15 },
                    { header: 'รวม (010)', key: 'total_val', width: 15 },
                    { header: 'รวม (Units)', key: 'total_unit', width: 15 },
                    { header: 'On Peak (kW/h)', key: 'on_peak_demand', width: 15 },
                    { header: 'Off Peak (kW/h)', key: 'off_peak_demand', width: 15 },
                    { header: 'Holiday (kW/h)', key: 'holiday_demand', width: 15 },
                    { header: '% ที่ยอมรับได้', key: 'percent_diff', width: 15 },
                    { header: 'ผู้บันทึก', key: 'recorder', width: 20 },
                    { header: 'หมายเหตุ', key: 'note', width: 25 }
                ];
            } else {
                // สำหรับมิเตอร์น้ำ (wt) และไฟทั่วไป (et)
                columns = [
                    { header: 'วันที่', key: 'date', width: 15 },
                    { header: 'เวลา', key: 'time', width: 10 },
                    { header: 'เลขมิเตอร์', key: 'curr', width: 15 },
                    { header: 'ปริมาณการใช้', key: 'usage', width: 15 },
                    { header: '% ที่ยอมรับได้', key: 'percent_diff', width: 15 },
                    { header: 'ผู้บันทึก', key: 'recorder', width: 20 },
                    { header: 'หมายเหตุ', key: 'note', width: 25 }
                ];
            }

            worksheet.columns = columns;

            // จัดรูปแบบหัวตาราง
            worksheet.getRow(1).font = { bold: true };
            worksheet.getRow(1).alignment = { vertical: 'middle', horizontal: 'center' };

            // ดึงข้อมูลจาก ag-Grid
            const rowData = [];
            gridApi.forEachNodeAfterFilterAndSort((node) => {
                rowData.push(node.data);
            });

            Swal.fire({
                title: 'กำลังเตรียมไฟล์...',
                text: 'กรุณารอสักครู่ ระบบกำลังประมวลผลรูปภาพ',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            for (let i = 0; i < rowData.length; i++) {
                const data = rowData[i];
                const rowIndex = i + 2; // เริ่มที่แถว 2 เพราะแถว 1 คือ Header
                const row = worksheet.getRow(rowIndex);
                row.height = 80; // กำหนดความสูงแถวเพื่อให้แสดงรูปได้ชัดเจน

                // ใส่ข้อมูล Text แบบไดนามิกตามคอลัมน์ที่ตั้งไว้
                columns.forEach(col => {
                    // ข้าม percent_diff ไว้ก่อน เพราะมีการกำหนดสีและสไตล์พิเศษด้านล่าง
                    if (col.key === 'percent_diff') return;

                    // จัดการกรณีเป็น usage (เพื่อให้มีทศนิยม 2 ตำแหน่งเหมือนโค้ดเดิม)
                    if (col.key === 'usage') {
                        row.getCell(col.key).value = data.usage ? parseFloat(data.usage).toFixed(2) : '0.00';
                    } else {
                        // ใส่ข้อมูลตาม key ทั่วไป ถ้าไม่มีค่าให้ใส่ '-'
                        row.getCell(col.key).value = data[col.key] !== undefined && data[col.key] !== null && data[col.key] !== '' ? data[col.key] : '-';
                    }
                });

                // จัดการ Custom Style ของ percent_diff
                const percentCell = row.getCell('percent_diff');
                const pVal = data.percent_diff;

                if (pVal === undefined || pVal === null || pVal === '') {
                    percentCell.value = '-';
                } else {
                    percentCell.value = pVal > 0 ? `+${pVal}%` : `${pVal}%`;
                    
                    if (data.is_exceeded) {
                        percentCell.font = { color: { argb: 'FFB91C1C' }, bold: true };
                        percentCell.fill = {
                            type: 'pattern',
                            pattern: 'solid',
                            fgColor: { argb: 'FFFEE2E2' }
                        };
                    } else if (pVal < 0) {
                        percentCell.font = { color: { argb: 'FF166534' }, bold: true };
                    }
                }

                // จัดการรูปภาพ
                if (data.images && data.images.length > 0) {
                    try {
                        const imgUrl = data.images[0];
                        const response = await fetch(imgUrl);
                        const blob = await response.blob();
                        const arrayBuffer = await blob.arrayBuffer();

                        const imageId = workbook.addImage({
                            buffer: arrayBuffer,
                            extension: 'jpeg',
                        });

                        worksheet.addImage(imageId, {
                            // กำหนดตำแหน่งให้อยู่คอลัมน์ซ้ายสุด (สามารถเปลี่ยน col ให้ตรงกับที่ต้องการได้)
                            tl: { col: 0, row: rowIndex - 1 }, 
                            ext: { width: 100, height: 100 },
                            editAs: 'oneCell'
                        });
                    } catch (error) {
                        console.error('Error adding image to excel:', error);
                    }
                }
                
                row.alignment = { vertical: 'middle', horizontal: 'center' };
            }

            // ดาวน์โหลดไฟล์
            const buffer = await workbook.xlsx.writeBuffer();
            saveAs(new Blob([buffer]), `Meter_Report_${config?.name || currentCat}_${new Date().getTime()}.xlsx`);
            
            Swal.close();
        }

        const colDefs = {
            common: [
                { 
                    field: "images", 
                    headerName: "รูป",
                    width: 80, 
                    sortable: false,
                    cellRenderer: params => {
                        const images = params.value || [];
                        const firstImg = images.length > 0 ? images[0] : null;
                        if (firstImg) {
                            return `<div class="flex items-center h-full cursor-pointer group" onclick='ImageCarousel.open(${JSON.stringify(images)})'>
                                        <div class="relative">
                                            <img src="${firstImg}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 shadow-sm group-hover:scale-105 transition-transform">
                                        </div>
                                    </div>`;
                        }
                        return `<div class="flex items-center h-full"><div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-200 border-dashed flex items-center justify-center text-slate-300"><i data-lucide="image" class="w-4 h-4"></i></div></div>`;
                    }
                },
                { field: "date", headerName: "วันที่",  width: 130},
                { field: "time", headerName: "เวลา",  width: 100 },
                { field: "curr", headerName: "เลขมิเตอร์", type: 'numericColumn', cellStyle: { fontWeight: 'bold', color: '#006B9F' } },
                { 
                    field: "usage", 
                    headerName: "รวม (m³)", 
                    type: 'numericColumn',
                    valueFormatter: p => p.value ? parseFloat(p.value).toFixed(2) : '0.00',
                    aggFunc: 'sum',
                    cellStyle: params => {
                        const baseStyle = { fontWeight: 'bold' };
                        // ข้ามการทำสีถ้าเป็นแถวจัดกลุ่ม (Group) หรือไม่มีข้อมูล
                        if (!params.data || params.node.group) return baseStyle;
                        
                        // 🔴 ถ้าเกิน -> สีแดง
                        if (params.data.is_exceeded) {
                            return { ...baseStyle, color: '#b91c1c' }; 
                        } 
                        // 🟢 ถ้าติดลบ (< 0) -> สีเขียว
                        else if (params.data.percent_diff !== null && params.data.percent_diff < 0) {
                            return { ...baseStyle, color: '#166534' }; 
                        }
                        // ⚫ ปกติ 0 ถึง 20% -> สีดำ/ปกติ
                        return baseStyle; 
                    }
                },
                { 
                    field: "percent_diff", 
                    headerName: "% ที่ยอมรับได้", 
                    width: 120, 
                    type: 'numericColumn',
                    valueFormatter: params => {
                        if (params.value === undefined || params.value === null) return '-';
                        // ใส่เครื่องหมาย + ด้านหน้าถ้าค่ามากกว่า 0
                        return params.value > 0 ? `+${params.value}%` : `${params.value}%`;
                    },
                    cellStyle: params => {
                        if (!params.data || params.node.group || params.value === null) return null;
                        
                        // 🔴 เกิน-> สีแดง
                        if (params.data.is_exceeded) {
                            return { color: '#b91c1c', backgroundColor: '#fee2e2', fontWeight: 'bold' }; 
                        } 
                        // 🟢 ติดลบ -> สีเขียว
                        else if (params.data.percent_diff < 0) {
                            return { color: '#166534', fontWeight: 'bold' }; 
                        }
                        return null;
                    }
                },
                { field: "recorder", headerName: "ผู้บันทึก", flex: 1 },
                { field: "note", headerName: "หมายเหตุ", flex: 1 },
                { 
                    headerName: "จัดการ", 
                    width: 110, 
                    pinned: 'right',
                    cellRenderer: params => {
                        if (!params.data) return '';
                        const rowData = JSON.stringify(params.data).replace(/"/g, '&quot;'); 
                        setTimeout(() => lucide.createIcons(), 0); 
                        
                        return `
                            <div class="flex items-center gap-1 h-full py-2">
                                <button onclick='openDrawer(${rowData})' class="p-2 hover:bg-white hover:border border-slate/50 text-slate-400 rounded-xl transition-colors">
                                    <i data-lucide="pen" class="w-4 h-4"></i>
                                </button>
                            </div>`;
                    }
                }
            ],
            tou: [
                { field: "date", headerName: "วันที่" },
                { field: "time", headerName: "เวลา" },
                { 
                    headerName: "พลังงานไฟฟ้า",
                    children: [
                        { 
                            headerName: "Total 010 (kW/h)",
                            children: [
                                { field: "total_val", headerName: "010", width: 100 },
                                { 
                                    field: "total_unit", 
                                    headerName: "(Units)", 
                                    width: 100,
                                    type: 'numericColumn',
                                    cellStyle: params => {
                                        const baseStyle = { fontWeight: 'bold' };
                                        if (!params.data || params.node.group) return baseStyle;

                                        // 🔴 ถ้าเกิน -> สีแดง
                                        if (params.data.is_exceeded) {
                                            return { ...baseStyle, color: '#b91c1c' }; 
                                        } 
                                        // 🟢 ถ้าติดลบ (< 0) -> สีเขียว
                                        else if (params.data.percent_diff !== null && params.data.percent_diff < 0) {
                                            return { ...baseStyle, color: '#166534' }; 
                                        }
                                        return baseStyle;
                                    }
                                },
                                { 
                                    field: "percent_diff", 
                                    headerName: "% ที่ยอมรับได้", 
                                    width: 120, 
                                    type: 'numericColumn',
                                    valueFormatter: params => {
                                        if (params.value === undefined || params.value === null) return '-';
                                        return params.value > 0 ? `+${params.value}%` : `${params.value}%`;
                                    },
                                    cellStyle: params => {
                                        if (!params.data || params.node.group || params.value === null) return null;
                                        
                                        // 🔴 เกิน 20% -> สีแดง
                                        if (params.data.is_exceeded) {
                                            return { color: '#b91c1c', backgroundColor: '#fee2e2', fontWeight: 'bold' }; 
                                        } 
                                        // 🟢 ติดลบ -> สีเขียว
                                        else if (params.data.percent_diff < 0) {
                                            return { color: '#166534', fontWeight: 'bold' }; 
                                        }
                                        return null;
                                    }
                                }
                            ]
                        },
                        { 
                            headerName: "On Peak 011 (kW/h)",
                            children: [
                                { field: "on_val", headerName: "011", width: 100 },
                                { field: "on_unit", headerName: "(Units)", width: 90 }
                            ]
                        },
                        { 
                            headerName: "Off Peak 012 (kW/h)",
                            children: [
                                { field: "off_val", headerName: "012", width: 100 },
                                { field: "off_unit", headerName: "(Units)", width: 90 }
                            ]
                        }
                    ]
                },
                { 
                    headerName: "ค่าความต้องการไฟฟ้าสูงสุด",
                    children: [
                        { field: "on_peak_demand", headerName: "On Peak 031 (kW/h)", width: 200 },
                        { field: "off_peak_demand", headerName: "Off Peak 032 (kW/h)", width: 200 }
                    ]
                },
                { 
                    headerName: "ค่าความต้องการกำลังฟ้ารีแอ็คทีฟ",
                    flex: 1,
                    children: [
                        { field: "on_reactive", headerName: "On Peak 071 (kW/h)", width: 200 },
                        { field: "off_reactive", headerName: "Off Peak 072 (kW/h)", width: 200 }
                    ]
                },
                { field: "recorder", headerName: "ผู้บันทึก", flex: 1 },
                { field: "note", headerName: "หมายเหตุ", flex: 1 },
                { 
                    field: "images", 
                    headerName: "รูป",
                    width: 80, 
                    sortable: false,
                    cellRenderer: params => {
                        const images = params.value || [];
                        const firstImg = images.length > 0 ? images[0] : null;
                        if (firstImg) {
                            return `<div class="flex items-center h-full cursor-pointer group" onclick='ImageCarousel.open(${JSON.stringify(images)})'>
                                        <div class="relative">
                                            <img src="${firstImg}" class="w-10 h-10 rounded-lg object-cover border border-slate-200 shadow-sm group-hover:scale-105 transition-transform">
                                        </div>
                                    </div>`;
                        }
                        return `<div class="flex items-center h-full"><div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-200 border-dashed flex items-center justify-center text-slate-300"><i data-lucide="image" class="w-4 h-4"></i></div></div>`;
                    }
                },
                { 
                    headerName: "จัดการ", 
                    width: 110, 
                    pinned: 'right',
                    cellRenderer: params => {
                        if (!params.data) return '';
                        const rowData = JSON.stringify(params.data).replace(/"/g, '&quot;'); 
                        setTimeout(() => lucide.createIcons(), 0); 
                        
                        return `
                            <div class="flex items-center gap-1 h-full py-2">
                                <button onclick='openDrawer(${rowData})' class="p-2 hover:bg-white hover:border-secondary text-slate-400 rounded-xl transition-colors">
                                    <i data-lucide="pen" class="w-4 h-4"></i>
                                </button>
                            </div>`;
                    }
                }
            ]
        };

        // --- Functions ---

        async function fetchRounds() {
            try {
                const response = await fetch(`get_all_meter_round.php?ag_id=${AG_ID}`);
                const data = await response.json();
                if (Array.isArray(data)) {
                    allRounds = data;
                }
            } catch (error) {
                console.error("Error fetching rounds:", error);
            }
        }

        async function fetchMeterData(type) {
            const listContainer = document.getElementById('meter-list');
            const month = document.getElementById('filter-month').value;
            const year = document.getElementById('filter-year').value;

            listContainer.innerHTML = '<div class="p-8 text-center"><i data-lucide="loader-2" class="w-8 h-8 animate-spin mx-auto text-primary mb-2"></i><p class="text-slate-400 text-sm">กำลังโหลดข้อมูล...</p></div>';
            refreshIcons();
            
            updateGrid(type, []);

            try {
                const response = await fetch(`handle_meter_info.php?action=get_all&tab=${type}&ag_id=${AG_ID}&month=${month}&year=${year}`);
                const data = await response.json();
                
                currentMeterList = Array.isArray(data) ? data : [];
                
                renderSidebar();
                
                if (currentMeterList.length > 0) {
                    const exists = currentMeterList.find(m => m.id === currentMeterId);
                    if (!exists) {
                        currentMeterId = currentMeterList[0].id;
                    }
                    renderSidebar(); 
                    const selected = currentMeterList.find(m => m.id === currentMeterId);
                    selectMeter(selected);
                } else {
                    currentMeterId = null;
                    renderSidebar();
                    resetInfoHeaders();
                }

            } catch (error) {
                console.error("Error fetching meters:", error);
                listContainer.innerHTML = '<div class="p-4 text-center text-red-400 text-sm">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
            }
        }

        function renderDynamicTabs() {
            const settings = [
                { 
                    id: 'nav-tabs-container', 
                    moreWrapper: 'more-tabs-wrapper', 
                    moreDropdown: 'more-tabs-dropdown', 
                    moreCount: 'more-count',
                    moreBtn: 'more-tabs-btn'
                },
                { 
                    id: 'nav-tabs-dropdown-container', 
                    moreWrapper: 'more-tabs-wrapper-mobile', 
                    moreDropdown: 'more-tabs-dropdown-mobile', 
                    moreCount: 'more-count-mobile',
                    moreBtn: 'more-tabs-btn-mobile'
                }
            ];

            const entries = Object.entries(METER_CONFIGS);

            settings.forEach(set => {
                const container = document.getElementById(set.id);
                if (!container) return;

                // แสดงแท็บทั้งหมดในแถวเดียว (เลื่อนแนวนอนได้)
                container.innerHTML = entries.map(([key, config]) => createTabHtml(key, config)).join('');

                // ซ่อนปุ่ม "เพิ่มเติม" เดิม เพราะเปลี่ยนมาใช้การเลื่อนแทน
                const moreWrapper = document.getElementById(set.moreWrapper);
                if (moreWrapper) moreWrapper.classList.add('hidden');

                // เลื่อนให้แท็บที่กำลังเลือกอยู่มาอยู่ในมุมมอง
                const activeBtn = container.querySelector(`button[onclick="switchCategory('${currentCat}')"]`);
                if (activeBtn) activeBtn.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            });
            refreshIcons();
        }

        // ฟังก์ชันเปิด/ปิด สำหรับจุดต่างๆ
        function toggleMoreTabsMobile() {
            document.getElementById('more-tabs-dropdown-mobile').classList.toggle('hidden');
        }

        function closeAllMoreTabs() {
            document.getElementById('more-tabs-dropdown')?.classList.add('hidden');
            document.getElementById('more-tabs-dropdown-mobile')?.classList.add('hidden');
        }

        // ดักจับเมื่อหน้าเว็บหลักสูญเสียโฟกัส (เช่น คลิกเข้าไปใน iframe)
        window.addEventListener('blur', function() {
            // 1. ปิด Dropdown เมนูของแท็บในโหมดมือถือ
            const moreTabsMobile = document.getElementById('more-tabs-dropdown-mobile');
            if (moreTabsMobile && !moreTabsMobile.classList.contains('hidden')) {
                moreTabsMobile.classList.add('hidden');
            }

            // 2. ปิด Dropdown เมนูของแท็บในโหมดเดสก์ท็อป (เผื่อไว้ด้วย)
            const moreTabsDesktop = document.getElementById('more-tabs-dropdown');
            if (moreTabsDesktop && !moreTabsDesktop.classList.contains('hidden')) {
                moreTabsDesktop.classList.add('hidden');
            }
            
            // 3. ปิด Dropdown รายชื่อมิเตอร์โหมดมือถือ (Mobile Meter Dropdown) เผื่อว่าเปิดค้างอยู่
            const mobileMeterDropdown = document.getElementById('mobile-meter-dropdown');
            if (mobileMeterDropdown && !mobileMeterDropdown.classList.contains('hidden')) {
                mobileMeterDropdown.classList.add('hidden');
                
                // หมุนลูกศรกลับ
                const chevron = document.getElementById('dropdown-chevron');
                if(chevron) chevron.style.transform = 'rotate(0deg)';
            }
        });

        // Helper สร้าง HTML ปุ่ม Tab
        function createTabHtml(key, config) {
            const isActive = key === currentCat;
            const activeClass = `bg-white shadow-sm border ${config.border} font-bold ${config.mobileText}`;
            const normalClass = `text-slate-500 border-transparent`;

            return `
                <button onclick="switchCategory('${key}')"
                    class="mobile-tab-btn w-full px-2 py-1.5 rounded-lg text-[11px] font-display flex items-center justify-center gap-1 transition-all whitespace-nowrap ${isActive ? activeClass : normalClass}">
                    <i data-lucide="${config.icon}" class="w-3.5 h-3.5 shrink-0"></i>
                    <span class="truncate">${config.name}</span>
                </button>
            `;
        }

        // ฟังก์ชันเปิด/ปิด Dropdown "เพิ่มเติม"
        function toggleMoreTabs() {
            const dropdown = document.getElementById('more-tabs-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // ปิด Dropdown เมื่อคลิกที่อื่น
        document.addEventListener('click', (e) => {
            const btn = document.getElementById('more-tabs-btn');
            const dropdown = document.getElementById('more-tabs-dropdown');
            if (btn && !btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        function switchCategory(cat) {
            currentCat = cat;
            const config = METER_CONFIGS[cat] || METER_CONFIGS['wt'];
            
            const btnText = document.getElementById('btn-add-text');
            if (btnText) btnText.innerText = config.label;

            renderDynamicTabs();
            fetchMeterData(cat);
        }

        function toggleMobileDropdown() {
            if (window.innerWidth >= 1024) return;
            const dropdown = document.getElementById('mobile-meter-dropdown');
            const chevron = document.getElementById('dropdown-chevron');
            const isOpen = !dropdown.classList.contains('hidden');
            
            if (isOpen) {
                dropdown.classList.add('hidden');
                chevron.style.transform = 'rotate(0deg)';
            } else {
                dropdown.classList.remove('hidden');
                chevron.style.transform = 'rotate(180deg)';
                document.getElementById('search-input-mobile').focus();
            }
        }

        function renderSidebar() {
            const listDesktop = document.getElementById('meter-list');
            const listMobile = document.getElementById('meter-list-mobile');
            
            const searchVal = document.getElementById('search-input').value.toLowerCase();
            const searchValMobile = document.getElementById('search-input-mobile').value.toLowerCase();

            // ล้างข้อมูลเก่า
            if(listDesktop) listDesktop.innerHTML = '';
            if(listMobile) listMobile.innerHTML = '';

            const filteredList = currentMeterList.filter(m => 
                m.name.toLowerCase().includes(searchVal) || 
                (m.location && m.location.toLowerCase().includes(searchVal))
            );
            
            const filteredListMobile = currentMeterList.filter(m => 
                m.name.toLowerCase().includes(searchValMobile) || 
                (m.location && m.location.toLowerCase().includes(searchValMobile))
            );

            // สร้าง Function ช่วยสร้าง Element รายการ
            const createItem = (m, isMobile) => {
                const div = document.createElement('div');
                div.className = `p-3 rounded-xl cursor-pointer transition-all border border-transparent hover:bg-secondary/5 hover:border-secondary ${currentMeterId === m.id ? 'border-primary' : ''}`;
                div.onclick = () => {
                    selectMeter(m);
                    if(isMobile) toggleMobileDropdown();
                };

                const iconMap = { wt: 'droplets', et: 'zap', tou: 'clock-3', gt: 'flame' };
                const colorMap = { wt: 'text-blue-500 bg-blue-50', et: 'text-amber-500 bg-amber-50', tou: 'text-purple-500 bg-purple-50', gt: 'text-orange-500 bg-orange-50' };

                div.innerHTML = `
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white flex items-center justify-center shrink-0 shadow-sm border border-slate-100 ${colorMap[currentCat]}">
                            <i data-lucide="${iconMap[currentCat]}" class="w-4 h-4"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-700 truncate">${m.name}</p>
                            <p class="text-[10px] text-slate-400 truncate">${m.location || '-'}</p>
                        </div>
                    </div>
                `;
                return div;
            };

            filteredList.forEach(m => listDesktop?.appendChild(createItem(m, false)));
            filteredListMobile.forEach(m => listMobile?.appendChild(createItem(m, true)));
            
            refreshIcons();
        }

        function filterMetersMobile() {
            renderSidebar();
        }

        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('mobile-meter-dropdown');
            const trigger = document.getElementById('status-indicator').parentElement;
            if (!trigger.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
                const chevron = document.getElementById('dropdown-chevron');
                if(chevron) chevron.style.transform = 'rotate(0deg)';
            }
        });
        
        function filterMeters() {
            renderSidebar();
            
            const searchVal = document.getElementById('search-input').value.toLowerCase();
            const firstMatch = currentMeterList.find(m => m.name.toLowerCase().includes(searchVal));
            if (firstMatch) {
                selectMeter(firstMatch);
            }
        }

        function resetToCurrentDate() {
            const now = new Date();
            const currentMonth = now.getMonth() + 1;
            const currentYear = now.getFullYear();

            document.getElementById('filter-month').value = currentMonth;
            document.getElementById('filter-year').value = currentYear;

            fetchMeterData(currentCat);
        }

        function resetInfoHeaders() {
            document.getElementById('current-meter-name').innerText = "ไม่พบข้อมูลมิเตอร์";
            document.getElementById('current-meter-location').innerText = "--";
            document.getElementById('current-meter-max').innerText = "0.00";
            document.getElementById('meter-limit-percent').innerText = "0.00";
            updateGrid(currentCat, []);
        }

        function selectMeter(m) {
            currentMeterId = m.id;
            document.querySelectorAll('.meter-item').forEach(el => el.classList.remove('sidebar-active', 'border-primary/60'));
            const sideItem = document.getElementById(`m-${m.id}`);
            if(sideItem) sideItem.classList.add('sidebar-active', 'border-primary/60');

            // Summary Info
            document.getElementById('current-meter-name').innerText = m.name;
            document.getElementById('current-meter-location').innerText = m.location || '-';
            document.getElementById('current-meter-max').innerText = m.mt_max_val.toLocaleString() || '-';
            document.getElementById('meter-limit-percent').innerText = m.limit_percent || '-';
            
            const iconMap = { wt: 'droplets', et: 'zap', tou: 'clock-3', gt: 'flame' };
            const colorMap = { wt: 'bg-sky-50 text-sky-600', et: 'bg-amber-50 text-amber-600', tou: 'bg-purple-50 text-purple-600', gt: 'bg-orange-50 text-orange-600' };
            
            const indicator = document.getElementById('status-indicator');
            indicator.className = `w-14 h-14 rounded-2xl flex items-center justify-center shadow-inner shadow-lg ${colorMap[currentCat]}`;
            
            const icon = document.getElementById('header-icon');
            icon.setAttribute('data-lucide', iconMap[currentCat]);
            
            updateGrid(currentCat, m.history || []);
            renderSidebar();
            refreshIcons();
        }

        function updateGrid(cat, historyData) {
            const flatData = [];
            if (Array.isArray(historyData)) {
                historyData.forEach(day => {
                    if (day.round && Array.isArray(day.round)) {
                        day.round.forEach(r => {
                            flatData.push({
                                date: day.date,
                                ...r
                            });
                        });
                    }
                });
            }

            const defs = cat === 'tou' ? colDefs.tou : colDefs.common;
            
            if (gridApi) {
                gridApi.setGridOption('columnDefs', defs);
                gridApi.setGridOption('rowData', flatData);
            } else {
                const gridOptions = {
                    columnDefs: defs,
                    rowData: flatData,
                    defaultColDef: {
                        minWidth: 100,
                        sortable: true,
                        suppressHeaderMenuButton: true
                    },
                    onGridReady: (params) => {
                        refreshIcons();
                    },
                    onRowDataUpdated: () => refreshIcons(),
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
                                    exportToExcelWithImages();
                                }
                            }
                        ];
                        
                        // สั่งให้ Lucide สร้าง Icon ในเมนู (ใช้ setTimeout เพื่อรอให้ Menu Render เสร็จ)
                        setTimeout(() => lucide.createIcons(), 50);
                        
                        return result;
                    },
                };

                gridApi = agGrid.createGrid(document.querySelector('#myGrid'), gridOptions);
            }
        }

        function openDrawer(data = null) {
            isPhotoDeleted = false;
            editingRecordId = data ? data.id : null;
            const drawer = document.getElementById('meter-drawer');
            const overlay = document.getElementById('drawer-overlay');
            const fieldsContainer = document.getElementById('drawer-dynamic-fields');
            const drawerTitle = document.getElementById('drawer-title');
            
            // 1. Title
            const actionText = data ? 'แก้ไขบันทึก' : 'เพิ่มบันทึก';
            const titles = {
                wt: `${actionText}มิเตอร์น้ำ`,
                et: `${actionText}มิเตอร์ไฟ`,
                tou: `${actionText}มิเตอร์ TOU`,
                gt: `${actionText}มิเตอร์แก๊ส`
            };
            drawerTitle.innerText = titles[currentCat] || actionText;

            const dateInput = document.querySelector('#meter-drawer input[type="date"]');
            const noteInput = document.querySelector('#meter-drawer textarea');

            if (data) {
                // Convert DD/MM/YYYY to YYYY-MM-DD
                const dateParts = data.date.split('/');
                if(dateParts.length === 3) dateInput.value = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
                noteInput.value = data.note || "";
            } else {
                dateInput.value = new Date().toISOString().split('T')[0];
                noteInput.value = "";
            }

            // 2. Dynamic Fields
            let periodField = `
                <div class="space-y-2">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">ช่วงเวลาบันทึก</label>
                    <div class="grid grid-cols-2 gap-3">
            `;

            if (allRounds.length > 0) {
                allRounds.forEach((round, index) => {
                    // ตรวจสอบว่าเป็นโหมดแก้ไขหรือไม่ เพื่อเลือก Radio ให้ถูกตัว
                    const isChecked = data ? (data.round_id == round.round_id) : (index === 0);
                    
                    periodField += `
                        <label class="relative flex flex-col items-center justify-center p-3 border border-slate-200 rounded-2xl cursor-pointer hover:bg-slate-50 has-[:checked]:bg-primary/5 has-[:checked]:border-primary has-[:checked]:ring-2 has-[:checked]:ring-primary/20 transition-all group">
                            <input type="radio" name="round_id" value="${round.round_id}" class="hidden" ${isChecked ? 'checked' : ''}>
                            <span class="text-sm font-bold text-slate-700 group-has-[:checked]:text-primary">${round.round_name}</span>
                            <span class="text-xs text-slate-400 group-has-[:checked]:text-primary/70">${round.round_time.substring(0, 5)} น.</span>
                            <div class="absolute -top-2 -right-2 w-6 h-6 bg-primary text-white rounded-full flex items-center justify-center opacity-0 group-has-[:checked]:opacity-100 transition-opacity shadow-lg">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            </div>
                        </label>
                    `;
                });
            } else {
                periodField += `<p class="col-span-2 text-xs text-red-400">ไม่พบข้อมูลรอบการบันทึก</p>`;
            }
            periodField += `</div></div>`;

            // --- Part 2: Meter Values ---
            let meterField = '';
            if (currentCat === 'tou') {
                meterField = `
                    <div class="space-y-4">
                        <div class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100">
                            <h4 class="text-[11px] font-bold text-blue-600 uppercase mb-3 flex items-center gap-2">
                                <i data-lucide="zap" class="w-3.5 h-3.5"></i> พลังงานไฟฟ้า
                            </h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-[11px] text-slate-500 mb-1 block">Total 010 (kW/h)</label>
                                    <input type="number" step="0.01" value="${data?.total_val || "0.00"}" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[11px] text-slate-500 mb-1 block">On Peak 011 (kW/h)</label>
                                        <input type="number" step="0.01" value="${data?.on_val || "0.00"}" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500/20">
                                    </div>
                                    <div>
                                        <label class="text-[11px] text-slate-500 mb-1 block">Off Peak 012 (kW/h)</label>
                                        <input type="number" step="0.01" value="${data?.off_val || "0.00"}" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500/20">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-amber-50/50 p-4 rounded-2xl border border-amber-100">
                            <h4 class="text-[11px] font-bold text-amber-600 uppercase mb-3 flex items-center gap-2">
                                <i data-lucide="trending-up" class="w-3.5 h-3.5"></i> ค่าความต้องการไฟฟ้าสูงสุด
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[11px] text-slate-500 mb-1 block">On Peak 031 (kW/h)</label>
                                    <input type="number" step="0.01" value="${data?.on_peak_demand || "0.00"}" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-amber-500/20">
                                </div>
                                <div>
                                    <label class="text-[11px] text-slate-500 mb-1 block">Off Peak 032 (kW/h)</label>
                                    <input type="number" step="0.01" value="${data?.off_peak_demand || "0.00"}" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-amber-500/20">
                                </div>
                            </div>
                        </div>

                        <div class="bg-purple-50/50 p-4 rounded-2xl border border-purple-100">
                            <h4 class="text-[11px] font-bold text-purple-600 uppercase mb-3 flex items-center gap-2">
                                <i data-lucide="activity" class="w-3.5 h-3.5"></i> ค่าความต้องการกำลังฟ้ารีแอ็คทีฟ
                            </h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[11px] text-slate-500 mb-1 block">On Peak 071 (kW/h)</label>
                                    <input type="number" step="0.01" value="${data?.on_reactive || "0.00"}" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500/20">
                                </div>
                                <div>
                                    <label class="text-[11px] text-slate-500 mb-1 block">Off Peak 072 (kW/h)</label>
                                    <input type="number" step="0.01" value="${data?.off_reactive || "0.00"}" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500/20">
                                </div>
                            </div>
                        </div>

                        <div class="bg-orange-50/50 p-4 rounded-2xl border border-orange-100">
                            <div class="flex items-start">
                                <div class="flex items-center h-5 mt-0.5">
                                    <input id="is_rollover" name="is_rollover" type="checkbox" value="1" 
                                        ${data?.is_rollover == 1 ? 'checked' : ''} 
                                        class="w-4 h-4 text-orange-600 bg-white border-orange-300 rounded focus:ring-orange-500 cursor-pointer">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="is_rollover" class="text-[11px] font-bold text-orange-600 uppercase flex items-center gap-2 cursor-pointer">
                                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> ยืนยันมิเตอร์วนรอบ (Meter Rollover)
                                    </label>
                                    <p class="text-orange-600/80 text-[11px] mt-1 leading-relaxed">
                                        * ติ๊กเฉพาะกรณีที่เลขมิเตอร์ปัจจุบันน้อยกว่าเลขรอบก่อนหน้า เนื่องจากหน้าปัดมิเตอร์วิ่งครบรอบแล้วเท่านั้น
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                `;
            } else {
                meterField = `
                    <div class="space-y-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider ml-1">เลขมิเตอร์ล่าสุด</label>
                        <div class="relative">
                            <input type="number" step="0.01" value="${data?.curr || ''}" class="w-full pl-4 pr-12 py-3 bg-slate-50 border border-slate-200 rounded-xl text-lg font-bold text-primary focus:ring-2 focus:ring-primary/20 outline-none placeholder:text-slate-300" placeholder="0.00">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-bold text-slate-400">${METER_CONFIGS[currentCat]?.unit || 'kW/h'}</span>
                        </div>
                    </div>
                    <div class="bg-orange-50/50 p-4 rounded-2xl border border-orange-100">
                            <div class="flex items-start">
                                <div class="flex items-center h-5 mt-0.5">
                                    <input id="is_rollover" name="is_rollover" type="checkbox" value="1" ${data?.is_rollover == '1' ? 'checked' : ''} class="w-4 h-4 text-orange-600 bg-white border-orange-300 rounded focus:ring-orange-500 cursor-pointer">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="is_rollover" class="text-[11px] font-bold text-orange-600 uppercase flex items-center gap-2 cursor-pointer">
                                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> ยืนยันมิเตอร์วนรอบ (Meter Rollover)
                                    </label>
                                    <p class="text-orange-600/80 text-[11px] mt-1 leading-relaxed">
                                        * ติ๊กเฉพาะกรณีที่เลขมิเตอร์ปัจจุบันน้อยกว่าเลขรอบก่อนหน้า เนื่องจากหน้าปัดมิเตอร์วิ่งครบรอบแล้วเท่านั้น
                                    </p>
                                </div>
                            </div>
                        </div>
                `;
            }

            // --- Part 3: Photo Upload ---
            const photoField = `
                <div class="space-y-2">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">รูปภาพ</label>
                    <div class="relative group">
                        <input type="file" id="meter_photo" accept="image/*" class="hidden" onchange="previewImage(this)">
                        
                        <div id="image-container" class="relative flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50 hover:bg-slate-100 hover:border-primary/50 transition-all overflow-hidden group">
                            
                            <label for="meter_photo" id="upload-placeholder" class="flex flex-col items-center cursor-pointer w-full h-full justify-center">
                                <div class="p-3 bg-white rounded-full shadow-sm mb-2 group-hover:scale-110 transition-transform">
                                    <i data-lucide="camera" class="w-6 h-6 text-slate-400"></i>
                                </div>
                                <span class="text-xs text-slate-500 font-medium">กดเพื่อถ่ายภาพ หรือ อัปโหลด</span>
                                <span class="text-xs text-slate-400 mt-1">PNG, JPG ไม่เกิน 5MB</span>
                            </label>

                            <img id="image-preview" 
                                onclick="previewFullImage()" 
                                class="hidden w-full h-full object-cover cursor-zoom-in hover:opacity-90 transition-opacity" 
                                title="คลิกเพื่อดูรูปขนาดใหญ่">
                        </div>

                        <button id="remove-img-btn" onclick="clearImage(event)" class="hidden absolute top-2 right-2 w-8 h-8 bg-red-500 text-white rounded-full shadow-lg flex items-center justify-center hover:bg-red-600 transition-colors z-10">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                        
                        <label for="meter_photo" id="change-img-btn" class="hidden absolute bottom-2 right-2 px-3 py-1.5 bg-white/90 backdrop-blur border border-slate-200 text-slate-600 rounded-lg text-[10px] font-bold shadow-sm cursor-pointer hover:bg-white transition-all z-10">
                            เปลี่ยนรูป
                        </label>
                    </div>
                </div>
            `;

            fieldsContainer.innerHTML = periodField + meterField + photoField;

            // --- แสดงรูปภาพเดิมหากอยู่ในโหมดแก้ไข ---
            if (data && data.images && data.images.length > 0) {
                const preview = document.getElementById('image-preview');
                const placeholder = document.getElementById('upload-placeholder');
                const removeBtn = document.getElementById('remove-img-btn');
                
                // นำรูปภาพแรกจาก array มาแสดง (เพราะในตารางเก็บเป็น array)
                preview.src = data.images[0]; 
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
                removeBtn.classList.remove('hidden');
            }

            drawer.classList.remove('translate-x-full');
            overlay.classList.remove('hidden');
            setTimeout(() => {
                overlay.classList.add('opacity-100');
                refreshIcons();
            }, 10);
        }

        function closeDrawer() {
            const drawer = document.getElementById('meter-drawer');
            const overlay = document.getElementById('drawer-overlay');
            drawer.classList.add('translate-x-full');
            overlay.classList.remove('opacity-100');
            setTimeout(() => overlay.classList.add('hidden'), 300);
        }

        function previewFullImage() {
            const preview = document.getElementById('image-preview');
            if (preview && preview.src && !preview.classList.contains('hidden')) {
                ImageCarousel.open([preview.src]);
            }
        }

        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            const placeholder = document.getElementById('upload-placeholder');
            const removeBtn = document.getElementById('remove-img-btn');
            const changeBtn = document.getElementById('change-img-btn');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                    removeBtn.classList.remove('hidden');
                    if(changeBtn) changeBtn.classList.remove('hidden');
                    isPhotoDeleted = false;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function clearImage(event) {
            if(event) event.preventDefault(); 
            const input = document.getElementById('meter_photo');
            const preview = document.getElementById('image-preview');
            const placeholder = document.getElementById('upload-placeholder');
            const removeBtn = document.getElementById('remove-img-btn');
            const changeBtn = document.getElementById('change-img-btn');

            input.value = '';
            preview.src = '';
            preview.classList.add('hidden');
            placeholder.classList.remove('hidden');
            removeBtn.classList.add('hidden');
            if(changeBtn) changeBtn.classList.add('hidden');
            isPhotoDeleted = true;
        }

        function printData() {
            const month = document.getElementById('filter-month').value;
            const year = document.getElementById('filter-year').value;
            const category = currentCat;

            if (!currentMeterId) {
                alert("กรุณาเลือกมิเตอร์ที่ต้องการพิมพ์");
                return;
            }

            const url = `meter_info_print.php?meter_id=${currentMeterId}&month=${month}&year=${year}&category=${category}`;
            const windowFeatures = "width=800,height=600,scrollbars=yes,resizable=yes";
            window.open(url, '_blank', windowFeatures);
        }

        async function saveData() {
            const drawer = document.getElementById('meter-drawer');
            const dateInput = drawer.querySelector('input[type="date"]');
            const roundRadio = drawer.querySelector('input[name="round_id"]:checked');
            const note = drawer.querySelector('textarea');
            const fileInput = document.getElementById('meter_photo');

            // --- 1. Validation: วันที่ ---
            if (!dateInput.value) {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'กรุณาระบุวันที่', 
                    text: 'โปรดเลือกวันที่บันทึกข้อมูล' 
                }).then(() => dateInput.focus()); 
                return;
            }

            // --- 2. Validation: รอบการบันทึก ---
            if (!roundRadio) {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'กรุณาเลือกช่วงเวลา', 
                    text: 'โปรดเลือกช่วงเวลาที่บันทึกมิเตอร์' 
                });
                return;
            }

            // --- 3. Validation: เลขมิเตอร์ (แยกตามประเภท) ---
            if (METER_CONFIGS[currentCat].type === 'simple') {
                const valInput = drawer.querySelector('input[type="number"]');
                if (!valInput || valInput.value.trim() === "") {
                    Swal.fire({ 
                        icon: 'warning', 
                        title: 'กรุณากรอกเลขมิเตอร์', 
                        text: `โปรดระบุค่า ${METER_CONFIGS[currentCat].name}` 
                    }).then(() => valInput.focus()); 
                    return;
                }
            } else if (currentCat === 'tou') {
                const touInputs = drawer.querySelectorAll('#drawer-dynamic-fields input[type="number"]');
                for (let input of touInputs) {
                    if (input.value.trim() === "") {
                        const labelText = input.previousElementSibling ? input.previousElementSibling.innerText : "ข้อมูลให้ครบถ้วน";
                        await Swal.fire({ 
                            icon: 'warning', 
                            title: 'ข้อมูลไม่ครบถ้วน', 
                            text: `กรุณากรอกช่อง: ${labelText}` 
                        });
                        input.focus(); 
                        return; 
                    }
                }
            }

            // --- 4. ตรวจสอบข้อมูลซ้ำ (Logic เดิม) ---
            if (editingRecordId === null) { 
                const checkData = new FormData();
                checkData.append('meter_id', currentMeterId);
                checkData.append('date', dateInput.value);
                checkData.append('round_id', roundRadio.value);
                checkData.append('type', currentCat.toLowerCase());

                try {
                    const checkRes = await fetch('handle_meter_info.php?action=check_duplicate', {
                        method: 'POST',
                        body: checkData
                    });
                    const checkResult = await checkRes.json();

                    if (checkResult.exists) {
                        const confirm = await Swal.fire({
                            title: 'พบข้อมูลซ้ำในระบบ!',
                            text: `วันที่ ${dateInput.value} รอบนี้มีบันทึกอยู่แล้ว ต้องการบันทึกทับหรือไม่?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'บันทึกทับ',
                            cancelButtonText: 'ยกเลิก'
                        });
                        if (!confirm.isConfirmed) return;
                    }
                } catch (error) {
                    console.error("Duplicate check error:", error);
                }
            }

            // --- 5. ดำเนินการส่งข้อมูล ---
            Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            const formData = new FormData();
            formData.append('ag_id', AG_ID);
            formData.append('mt_id', currentMeterId);
            formData.append('record_id', editingRecordId);
            
            // หากเป็นการแก้ไข (Edit Mode) ให้ส่ง flag แจ้งหลังบ้าน
            if (editingRecordId !== null) {
                formData.append('mode', 'edit');
            }
            
            formData.append('tab', currentCat.toLowerCase());
            formData.append('round_id', roundRadio.value);
            formData.append('date', dateInput.value);
            formData.append('note', note.value);
            formData.append('user_ins', USER_ID);

            if (METER_CONFIGS[currentCat].type === 'simple') {
                const valInput = drawer.querySelector('input[type="number"]');
                formData.append('curr', valInput.value);
            } else {
                const touInputs = drawer.querySelectorAll('#drawer-dynamic-fields input[type="number"]');
                const fields = ['total_val', 'on_val', 'off_val', 'on_peak_demand', 'off_peak_demand', 'on_reactive', 'off_reactive'];
                touInputs.forEach((input, index) => {
                    if(fields[index]) {
                        let value = input.value.trim() === "" ? "0" : input.value;
                        formData.append(fields[index], value);
                    }
                });
            }

            // ตรวจสอบและดึงค่า Checkbox is_rollover ตรงนี้ (หลังจากมี formData แล้ว)
            const isRolloverCheckbox = document.getElementById('is_rollover');
            if (isRolloverCheckbox) {
                formData.append('is_rollover', isRolloverCheckbox.checked ? '1' : '0'); 
            }

            if (fileInput && fileInput.files[0]) {
                formData.append('meter_photo', fileInput.files[0]);
            } else if (isPhotoDeleted) {
                formData.append('meter_photo', ""); 
            }

            try {
                const response = await fetch(`handle_meter_info.php?action=save`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: result.message, timer: 1500, showConfirmButton: false });
                    closeDrawer();
                    fetchMeterData(currentCat);
                } else {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: result.message });
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'ล้มเหลว', text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้' });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderDynamicTabs();
            refreshIcons();
            fetchRounds();
            
            setTimeout(() => {
                let params = new URLSearchParams(window.location.search);
                try {
                    if (window.parent && window.parent.location.hash.includes('?')) {
                        let hashString = window.parent.location.hash.split('?')[1];
                        params = new URLSearchParams(hashString);
                    }
                } catch(e) {}

                let notiTab = params.get('tab') || 'wt';
                let notiMonth = params.get('month');
                let notiYear = params.get('year');

                // ค้นหา Dropdown อัตโนมัติ
                let monthSelect = document.getElementById('month') || document.querySelector('select[id*="month"]') || document.querySelector('select[name*="month"]');
                let yearSelect = document.getElementById('year') || document.querySelector('select[id*="year"]') || document.querySelector('select[name*="year"]');

                // 1. แค่เปลี่ยนค่าใน Dropdown เฉยๆ (ไม่ต้องสั่ง dispatchEvent แล้ว เพื่อลดการยิง API ซ้ำซ้อน)
                if (monthSelect && notiMonth) {
                    let paddedMonth = notiMonth.padStart(2, '0');
                    if (Array.from(monthSelect.options).some(opt => opt.value === paddedMonth)) {
                        monthSelect.value = paddedMonth;
                    } else {
                        monthSelect.value = parseInt(notiMonth);
                    }
                }

                if (yearSelect && notiYear) {
                    yearSelect.value = notiYear;
                }

                // 2. รวบยอดสั่งยิง API เพียง 1 ครั้งที่ตรงนี้
                // ใช้ switchCategory เป็นตัวจัดการหลัก ซึ่งปกติมันจะไปอ่านค่า .value ล่าสุดใน Dropdown มายิง API ให้อยู่แล้ว
                switchCategory(notiTab);

            }, 150); // ลดเวลาหน่วงลงมาให้เร็วขึ้น
        });
    </script>
</body>
</html>