<?php 
@session_start();
include "config_ctrl/checksession.php";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- ECharts CDN -->
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap');
        :root {
            --color-bg: #f8fafc;
            --color-primary: #006B9F; /* Sky 500 */
            --color-primary-dark: #004a6f; /* Sky 600 */
            --color-secondary: #04ADFF; /* Indigo 500 */
            --glass-bg: rgba(255, 255, 255, 0.9);
            --glass-border: rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }
        
        body { 
            font-family: 'Prompt', sans-serif; 
            background-color: var(--color-bg);
            color: #1e293b;
            background-image: 
                radial-gradient(at 0% 0%, rgba(14, 165, 233, 0.1) 0px, transparent 50%), 
                radial-gradient(at 100% 0%, rgba(99, 102, 241, 0.1) 0px, transparent 50%), 
                radial-gradient(at 100% 100%, rgba(14, 165, 233, 0.05) 0px, transparent 50%);
            background-attachment: fixed;
        }

        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            border-radius: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .glass-panel:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
        }

        .kpi-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border: 1px solid #f1f5f9;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }
        
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .kpi-icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 1rem;
            font-size: 1.5rem;
        }

        .chart-container {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid #f1f5f9;
        }

        .custom-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
            color: white;
            box-shadow: 0 4px 6px -1px rgba(14, 165, 233, 0.3);
            transition: all 0.2s;
        }
        .btn-primary:hover {
            filter: brightness(110%);
            box-shadow: 0 10px 15px -3px rgba(14, 165, 233, 0.4);
        }

        .tab-pill {
            padding: 0.5rem 1.5rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
            color: #64748b;
        }
        .tab-pill.active {
            background-color: var(--color-primary);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(14, 165, 233, 0.3);
        }

        /* ECharts Containers */
        .echart-box { width: 100%; height: 350px; }
        .echart-box-lg { width: 100%; height: 450px; }
        .echart-box-sm { width: 100%; height: 300px; }

        /* Skeleton Loading */
        .skeleton-loader {
            position: absolute; inset: 0;
            background: #f8fafc; z-index: 10;
            display: flex; flex-direction: column;
            padding: 1.5rem; border-radius: 1rem;
        }
        .shimmer {
            animation: shimmer 2s infinite linear;
            background: linear-gradient(to right, #f1f5f9 0%, #e2e8f0 20%, #f1f5f9 40%, #f1f5f9 100%);
            background-size: 1000px 100%;
        }
        @keyframes shimmer { 0% { background-position: -1000px 0; } 100% { background-position: 1000px 0; } }
        .hidden { display: none !important; }
    </style>
</head>
<body class="antialiased min-h-screen">
    
    <!-- Top Navigation / Header -->
    <div class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-gray-100 shadow-sm px-3 py-2 mb-8">
        <div class="w-full mx-auto flex flex-col xl:flex-row xl:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="bg-[--color-primary] p-2.5 rounded-xl text-white shadow-lg shadow-sky-200">
                    <i data-lucide="layout-dashboard" class="w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Maintenance Dashboard</h1>
                    <p class="text-sm text-slate-500">ระบบบริหารจัดการงานซ่อมบำรุงและพลังงาน</p>
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-3 bg-slate-50 p-2 rounded-2xl border border-slate-100">
                <div class="px-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">ช่วงเวลา</label>
                    <select id="filterType" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 block w-32 p-2 custom-select outline-none">
                        <option value="daily">รายวัน</option>
                        <option value="monthly">รายเดือน</option>
                        <option value="yearly">รายปี</option>
                    </select>
                </div>

                <div id="filterInputs" class="flex items-center gap-2">
                    <!-- Dynamic Inputs -->
                </div>

                <div class="px-2 border-l border-slate-200 pl-4 ml-2">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">สถานที่</label>
                    <select id="buildingFilter" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 block w-48 p-2 custom-select outline-none">
                        <option value="">ทั้งหมด</option>
                    </select>
                </div>

                <button id="searchBtn" class="btn-primary px-5 py-2 rounded-xl flex items-center gap-2 font-semibold ml-2 h-[38px]">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <span>ค้นหา</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-full mx-auto px-6 pb-12">
        
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Total Jobs -->
            <div class="kpi-card group">
                <div id="loader-stat-1" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">งานทั้งหมด</p>
                        <h3 id="stat-total" class="text-4xl font-black text-slate-800 tracking-tight">0</h3>
                        <p class="text-xs text-slate-400 mt-2">รายการแจ้งซ่อม</p>
                    </div>
                    <div class="kpi-icon-wrapper bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                        <i data-lucide="folder-open" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
            
            <!-- Pending -->
            <div class="kpi-card group">
                <div id="loader-stat-2" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">รอดำเนินการ</p>
                        <h3 id="stat-pending" class="text-4xl font-black text-amber-500 tracking-tight">0</h3>
                        <p class="text-xs text-slate-400 mt-2">รายการค้าง</p>
                    </div>
                    <div class="kpi-icon-wrapper bg-amber-50 text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition-colors duration-300">
                        <i data-lucide="clock" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>

            <!-- In Progress -->
            <div class="kpi-card group">
                <div id="loader-stat-3" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">กำลังดำเนินการ</p>
                        <h3 id="stat-doing" class="text-4xl font-black text-sky-500 tracking-tight">0</h3>
                        <p class="text-xs text-slate-400 mt-2">กำลังซ่อม</p>
                    </div>
                    <div class="kpi-icon-wrapper bg-sky-50 text-sky-500 group-hover:bg-sky-500 group-hover:text-white transition-colors duration-300">
                        <i data-lucide="hammer" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>

            <!-- Completed -->
            <div class="kpi-card group">
                <div id="loader-stat-4" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">สำเร็จแล้ว</p>
                        <h3 id="stat-completed" class="text-4xl font-black text-emerald-500 tracking-tight">0</h3>
                        <p class="text-xs text-slate-400 mt-2">ปิดงานแล้ว</p>
                    </div>
                    <div class="kpi-icon-wrapper bg-emerald-50 text-emerald-500 group-hover:bg-emerald-500 group-hover:text-white transition-colors duration-300">
                        <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <div class="chart-container relative">
                <div id="loader-job-chart" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h4 class="font-bold text-lg text-slate-800">ปริมาณงานตามช่วงเวลา</h4>
                        <p class="text-xs text-slate-400">แนวโน้มการแจ้งซ่อม</p>
                    </div>
                </div>
                <div id="monthlyTotalChart" class="echart-box"></div>
            </div>
            
            <div class="chart-container relative">
                <div id="loader-building-chart" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h4 class="font-bold text-lg text-slate-800">แยกตามอาคาร</h4>
                    </div>
                </div>
                <div id="buildingChart" class="echart-box"></div>
            </div>
        </div>

        <!-- Secondary Charts Row -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <div class="chart-container relative">
                <div id="loader-service-chart" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <h4 class="font-bold text-lg text-slate-800 mb-4">ชนิดของการบริการ</h4>
                <div id="serviceTypeChart" class="echart-box"></div>
            </div>
            <div class="chart-container relative">
                <div id="loader-category-chart" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <h4 class="font-bold text-lg text-slate-800 mb-4">ประเภทงาน</h4>
                <div id="jobCategoryChart" class="echart-box"></div>
            </div>
        </div>

        <!-- Energy Section -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="bg-yellow-100 p-2 rounded-lg text-yellow-600">
                        <i data-lucide="zap" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-800">Energy Monitoring</h2>
                        <p class="text-xs text-slate-500">ติดตามการใช้พลังงานไฟฟ้าและน้ำประปา</p>
                    </div>
                </div>
                
                <div class="flex items-center gap-3 bg-white p-1 rounded-full border border-slate-200 shadow-sm">
                    <div onClick="switchEnergyTab('wt')" id="tab-btn-wt" class="tab-pill active">มิเตอร์น้ำ</div>
                    <div onClick="switchEnergyTab('et')" id="tab-btn-et" class="tab-pill">มิเตอร์ไฟ</div>
                    <div onClick="switchEnergyTab('tou')" id="tab-btn-tou" class="tab-pill">มิเตอร์ TOU</div>
                </div>
            </div>
            
            <div class="p-4 relative min-h-[500px]">
                <div id="loader-energy-chart" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                
                <!-- Filter for TOU -->
                <div id="touFilterContainer" class="hidden mb-4 flex justify-end">
                    <select id="touMeterFilter" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-2 focus:ring-sky-500 p-2 custom-select">
                        <option value="">มิเตอร์ TOU ทั้งหมด</option>
                    </select>
                </div>

                <!-- Chart Containers -->
                <div id="container-wt" class="block"><div id="wtChart" class="echart-box-lg"></div></div>
                <div id="container-et" class="hidden"><div id="etChart" class="echart-box-lg"></div></div>
                <div id="container-tou" class="hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2">
                            <div id="touChart" class="echart-box-lg"></div>
                        </div>
                        <div class="rounded-xl p-4 border border-slate-100 flex flex-col justify-center">
                            <h5 class="text-center font-bold text-slate-500 mb-4 uppercase text-xs tracking-wider">สัดส่วนการใช้พลังงาน</h5>
                            <div id="touDonutChart" class="echart-box-sm"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    lucide.createIcons();
    
    // Config & State
    let currentStart = moment().startOf('month');
    let currentEnd = moment().endOf('month');
    let activeEnergyTab = 'wt';
    let isBuildingPopulated = false; 
    let isMeterFilterPopulated = false;
    let ag_id = <?php echo $sess_user_agency_es ?>;

    const formatNum = (num) => new Intl.NumberFormat().format(num);

    // ECharts Theme
    const chartColors = ['#0ea5e9', '#6366f1', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6', '#06b6d4', '#ec4899'];
    
    // CSV Export Helper
    function exportChartToCSV(chartInstance, fileName) {
        try {
            const option = chartInstance.getOption();
            let csvContent = ""; // ลบ data:text/csv ออกจากตรงนี้
            let header = [];
            let dataRows = [];
            
            // ส่วนการเตรียมข้อมูล (เหมือนเดิม)
            let xAxisData = [];
            if (option.xAxis && option.xAxis[0] && option.xAxis[0].data) {
                xAxisData = option.xAxis[0].data;
                header.push("Date/Category");
            } else if (option.yAxis && option.yAxis[0] && option.yAxis[0].type === 'category' && option.yAxis[0].data) {
                xAxisData = option.yAxis[0].data;
                header.push("Category");
            }

            if(option.series) {
                option.series.forEach(s => {
                    header.push(s.name || "Value");
                    if(s.type === 'pie' || s.type === 'donut') {
                        if(header.length === 2 && header[0] === 'Date/Category') header = ["Name", "Value"]; 
                        s.data.forEach((d, i) => {
                            if(!dataRows[i]) dataRows[i] = [];
                            dataRows[i][0] = d.name;
                            dataRows[i][1] = d.value;
                        });
                    } else {
                        s.data.forEach((d, i) => {
                            if(!dataRows[i]) dataRows[i] = [xAxisData[i] || ""];
                            let val = (typeof d === 'object' && d !== null) ? (d.value || d.y || d) : d;
                            dataRows[i].push(val);
                        });
                    }
                });
            }

            // สร้างเนื้อหา CSV
            csvContent += header.join(",") + "\r\n";
            dataRows.forEach(row => { csvContent += row.join(",") + "\r\n"; });

            // --- ส่วนที่แก้ไขเพื่อให้รองรับภาษาไทย ---
            // ใช้ Blob ร่วมกับ \ufeff (UTF-8 BOM)
            const blob = new Blob(["\ufeff" + csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            
            const link = document.createElement("a");
            link.setAttribute("href", url);
            link.setAttribute("download", fileName + "_" + moment().format('YYYYMMDD') + ".csv");
            document.body.appendChild(link);
            link.click();
            
            // ล้างหน่วยความจำ
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            
        } catch(e) { 
            console.error(e);
            alert("Export Failed"); 
        }
    }

    // Chart Base Option
    const getBaseOption = (titleForExport) => ({
        textStyle: { fontFamily: 'Prompt' },
        color: chartColors,
        backgroundColor: 'transparent',
        tooltip: {
            trigger: 'axis',
            backgroundColor: 'rgba(255, 255, 255, 0.95)',
            borderColor: '#e2e8f0',
            borderWidth: 1,
            textStyle: { color: '#334155' },
            shadowBlur: 10,
            shadowColor: 'rgba(0,0,0,0.05)',
            padding: 12
        },
        grid: { left: '3%', right: '4%', bottom: '12%', containLabel: true },
        toolbox: {
            show: true,
            feature: {
                dataZoom: { yAxisIndex: false, title: { zoom: 'ซูม', back: 'รีเซ็ต' } },
                dataView: { readOnly: true, title: 'ข้อมูล', lang: ['Data View', 'Close', 'Refresh'] },
                myExportCSV: {
                    show: true, title: 'Export CSV',
                    icon: 'path://M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M15.8,20H14L12,16.6L10,20H8.2L11.1,15.5L8.2,11H10L12,14.4L14,11H15.8L12.9,15.5L15.8,20M13,9V3.5L18.5,9H13Z',
                    onclick: (opt, instance) => exportChartToCSV(instance, titleForExport || 'chart')
                },
                saveAsImage: { show: true, title: 'บันทึกภาพ', pixelRatio: 2 }
            },
            iconStyle: { borderColor: '#94a3b8' },
            right: 10, top: 0
        }
    });

    // Init Charts
    const charts = {
        job: echarts.init(document.querySelector("#monthlyTotalChart")),
        building: echarts.init(document.querySelector("#buildingChart")),
        service: echarts.init(document.querySelector("#serviceTypeChart")),
        category: echarts.init(document.querySelector("#jobCategoryChart")),
        wt: echarts.init(document.querySelector("#wtChart")),
        et: echarts.init(document.querySelector("#etChart")),
        tou: echarts.init(document.querySelector("#touChart")),
        touDonut: echarts.init(document.querySelector("#touDonutChart"))
    };

    window.addEventListener('resize', () => Object.values(charts).forEach(c => c.resize()));

    window.switchEnergyTab = function(type) {
        activeEnergyTab = type;
        $('.tab-pill').removeClass('active');
        $(`#tab-btn-${type}`).addClass('active');
        
        $('#container-wt, #container-et, #container-tou').addClass('hidden').removeClass('block');
        $(`#container-${type}`).removeClass('hidden').addClass('block');
        
        if (type === 'tou') $('#touFilterContainer').removeClass('hidden');
        else { $('#touFilterContainer').addClass('hidden'); $('#touMeterFilter').val(''); }

        setTimeout(() => {
            if(type === 'wt') charts.wt.resize();
            if(type === 'et') charts.et.resize();
            if(type === 'tou') { charts.tou.resize(); charts.touDonut.resize(); }
        }, 50);

        loadDashboardData(currentStart, currentEnd);
    }

    function toggleLoaders(show) {
        show ? $('.skeleton-loader').removeClass('hidden') : $('.skeleton-loader').addClass('hidden');
    }

    function populateBuildingFilter() {
        if (isBuildingPopulated) return;
        $.ajax({
            url: 'get_all_building.php',
            type: 'GET', data: { ag_id: ag_id }, dataType: 'json',
            success: function(response) {
                if (response.building && response.building.length > 0) {
                    const $b = $('#buildingFilter');
                    $b.find('option:not(:first)').remove(); 
                    response.building.forEach(b => $b.append(`<option value="${b.area_id}">${b.area_name}</option>`));
                    isBuildingPopulated = true;
                }
            }
        });
    }

    function populateMeterFilters(data) {
        if (!data || data.length === 0 || isMeterFilterPopulated) return;
        const meterMap = new Map();
        data.forEach(day => { 
            if (day.meter?.tou) day.meter.tou.forEach(m => meterMap.set(m.mt_id, m.text)); 
        });
        if (meterMap.size > 0) {
            const $t = $('#touMeterFilter');
            meterMap.forEach((n, i) => $t.append(`<option value="${i}">${n}</option>`));
            isMeterFilterPopulated = true;
        }
    }

    $('#filterType').on('change', function() {
        const type = $(this).val();
        let html = '';
        const style = 'bg-white border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 block p-2 outline-none';
        if (type === 'daily') {
            html = `<input type="date" id="dateStart" class="${style}" value="${moment().subtract(7, 'days').format('YYYY-MM-DD')}">
                    <span class="text-slate-400">-</span>
                    <input type="date" id="dateEnd" class="${style}" value="${moment().format('YYYY-MM-DD')}">`;
        } else if (type === 'monthly') {
            html = `<input type="month" id="monthStart" class="${style}" value="${moment().format('YYYY-MM')}">
                    <span class="text-slate-400">-</span>
                    <input type="month" id="monthEnd" class="${style}" value="${moment().format('YYYY-MM')}">`;
        } else if (type === 'yearly') {
            const curY = new Date().getFullYear();
            let opts = ''; for(let i = curY; i >= curY - 5; i--) opts += `<option value="${i}">${i}</option>`;
            html = `<select id="yearStart" class="${style}">${opts}</select>
                    <span class="text-slate-400">-</span>
                    <select id="yearEnd" class="${style}">${opts}</select>`;
        }
        $('#filterInputs').html(html);
    }).trigger('change');

    function loadDashboardData(s, e) {
        toggleLoaders(true);
        Object.values(charts).forEach(c => c.showLoading({ color: '#0ea5e9', maskColor: 'rgba(255, 255, 255, 0.6)' }));

        let params = { 
            startDate: s.format('YYYY-MM-DD'), 
            endDate: e.format('YYYY-MM-DD'), 
            ag_id: ag_id 
        };
        const selectedAreaId = $('#buildingFilter').val();
        if (selectedAreaId) params.area_id = selectedAreaId;
        const selectedMeterId = $('#touMeterFilter').val();
        if (activeEnergyTab === 'tou' && selectedMeterId) { params.mt_type = 'tou'; params.mt_id = selectedMeterId; }

        $.ajax({
            url: 'get_all_dashboard.php',
            type: 'GET', data: params, dataType: 'json',
            success: function(response) {
                Object.values(charts).forEach(c => c.hideLoading());
                if (response.error) { toggleLoaders(false); return; }
                const data = response.data || [];
                populateMeterFilters(data);

                // Update KPIs
                $('#stat-total').text(formatNum(data.reduce((s, i) => s + i.repairs, 0)));
                $('#stat-completed').text(formatNum(data.reduce((s, i) => s + i.completed, 0)));
                $('#stat-pending').text(formatNum(data.reduce((s, i) => s + i.pending, 0)));
                $('#stat-doing').text(formatNum(data.reduce((s, i) => s + (i.inprogress || 0), 0)));

                // Update Charts
                const jobDates = data.map(i => moment(i.date).format('DD MMM'));
                const jobValues = data.map(i => i.repairs);
                
                charts.job.setOption({
                    ...getBaseOption('Job_Stats'),
                    xAxis: { type: 'category', boundaryGap: false, data: jobDates, axisLine: { lineStyle: { color: '#cbd5e1' } } },
                    yAxis: { type: 'value', splitLine: { lineStyle: { type: 'dashed', color: '#f1f5f9' } } },
                    dataZoom: [{ type: 'inside' }, { type: 'slider', bottom: 10, height: 20 }],
                   series: [{
    						name: 'งานแจ้งซ่อม',
							type: 'line',
							smooth: true,
							areaStyle: {
								color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
									{offset: 0, color: 'rgba(14, 165, 233, 0.4)'},
									{offset: 1, color: 'rgba(14, 165, 233, 0.05)'}
								])
							},
							itemStyle: { color: '#0ea5e9' },
							label: {
								show: true,
								position: 'top',
								formatter: '{c}'
							},
							data: jobValues
						}]
                });

                const areaSum = {}, svcSum = {}, typeSum = {};
                const wtSeriesMap = {}, etSeriesMap = {}, touDailyData = [];
                const allDates = [];

                data.forEach(day => {
                    const dFmt = moment(day.date).format('DD/MM/YY');
                    allDates.push(dFmt);
                    if(day.area) day.area.forEach(a => areaSum[a.text] = (areaSum[a.text] || 0) + a.value);
                    if(day.service) day.service.forEach(s => svcSum[s.text] = (svcSum[s.text] || 0) + s.value);
                    if(day.type) day.type.forEach(t => typeSum[t.text] = (typeSum[t.text] || 0) + t.value);

                    if (day.meter?.wt) day.meter.wt.forEach(m => {
                        if (!wtSeriesMap[m.text]) wtSeriesMap[m.text] = [];
                        wtSeriesMap[m.text].push(m.unitDay);
                    });
                    if (day.meter?.et) day.meter.et.forEach(m => {
                        if (!etSeriesMap[m.text]) etSeriesMap[m.text] = [];
                        etSeriesMap[m.text].push(m.unitDay);
                    });
                    
                    let tT = 0, tOn = 0, tOff = 0;
                    if (day.meter?.tou) day.meter.tou.forEach(m => { tT += (m.total_unit || 0); tOn += (m.on_peak_unit || 0); tOff += (m.off_peak_unit || 0); });
                    touDailyData.push({ date: dFmt, total: tT, on: tOn, off: tOff });
                });

                charts.building.setOption({
                    ...getBaseOption('Building_Stats'),
                    grid: { left: '3%', right: '10%', bottom: '5%', containLabel: true },
                    xAxis: { type: 'value', splitLine: { lineStyle: { type: 'dashed' } } },
                    yAxis: { type: 'category', data: Object.keys(areaSum), axisLine: { show: false }, axisTick: { show: false } },
                    series: [{
							name: 'จำนวนงาน',
							type: 'bar',
							data: Object.values(areaSum),
							itemStyle: {
								borderRadius: [0, 4, 4, 0],
								color: new echarts.graphic.LinearGradient(1, 0, 0, 0, [
									{offset: 0, color: '#0ea5e9'},
									{offset: 1, color: '#3b82f6'}
								])
							},
							label: {
								show: true,
								position: 'right',
								formatter: '{c}',
								fontWeight: 'bold'
							}
						}]
                });

                charts.service.setOption({
					...getBaseOption('Service_Stats'),
					tooltip: { trigger: 'item' },
					legend: { bottom: 0 },
					series: [{
						name: 'บริการ',
						type: 'pie',
						radius: ['40%', '60%'],
						center: ['50%', '45%'],
						itemStyle: { borderColor: '#fff', borderWidth: 1 },
						labelLine: { show: true },
						label: {
							show: true,
							formatter: '{b}: {c} ({d}%)'
						},
						data: Object.keys(svcSum).map(k => ({ value: svcSum[k], name: k }))
					}]
				});

                charts.category.setOption({
					...getBaseOption('Category_Stats'),
					tooltip: { trigger: 'item' },
					legend: { bottom: 0 },
					series: [{
						name: 'หมวดหมู่',
						type: 'pie',
						radius: '60%',
						center: ['50%', '45%'],
						itemStyle: { borderColor: '#fff', borderWidth: 1 },
						labelLine: { show: true },
						label: {
							show: true,
							formatter: '{b}: {c} ({d}%)'
						},
						data: Object.keys(typeSum).map(k => ({ value: typeSum[k], name: k }))
					}]
				});

                const updateLineChart = (chart, seriesMap, title) => {
                    const series = Object.keys(seriesMap).map(n => ({
						name: n,
						type: 'line',
						smooth: true,
						symbol: 'circle',
						symbolSize: 8,
						lineStyle: { width: 2 },
						label: {
							show: true,
							position: 'top',
							formatter: '{c}'
						},
						data: seriesMap[n]
					}));
                    chart.setOption({
                        ...getBaseOption(title),
                        title: series.length === 0 ? { text: 'ไม่พบข้อมูล', left: 'center', top: 'center', textStyle: { color: '#94a3b8' } } : {},
                        xAxis: { type: 'category', boundaryGap: false, data: allDates, axisLine: { lineStyle: { color: '#cbd5e1' } } },
                        yAxis: { type: 'value', splitLine: { lineStyle: { type: 'dashed', color: '#f1f5f9' } } },
                        dataZoom: [{ type: 'inside' }, { type: 'slider', bottom: 10, height: 20 }],
                        series: series
                    }, true);
                };

                updateLineChart(charts.wt, wtSeriesMap, 'Water_Usage');
                updateLineChart(charts.et, etSeriesMap, 'Electric_Usage');

                charts.tou.setOption({
                    ...getBaseOption('TOU_Stats'),
                    legend: { top: 0 },
                    xAxis: { type: 'category', data: touDailyData.map(d => d.date) },
                    yAxis: { type: 'value', splitLine: { lineStyle: { type: 'dashed' } } },
                    dataZoom: [{ type: 'inside' }, { type: 'slider', bottom: 10, height: 20 }],
                    series: [
							{
								name: 'Total',
								type: 'bar',
								itemStyle: { color: '#3b82f6', borderRadius: [4, 4, 0, 0] },
								label: {
									show: true,
									position: 'top',
									formatter: '{c}'
								},
								data: touDailyData.map(d => d.total)
							},
							{
								name: 'On Peak',
								type: 'bar',
								itemStyle: { color: '#f97316', borderRadius: [4, 4, 0, 0] },
								label: {
									show: true,
									position: 'top',
									formatter: '{c}'
								},
								data: touDailyData.map(d => d.on)
							},
							{
								name: 'Off Peak',
								type: 'bar',
								itemStyle: { color: '#10b981', borderRadius: [4, 4, 0, 0] },
								label: {
									show: true,
									position: 'top',
									formatter: '{c}'
								},
								data: touDailyData.map(d => d.off)
							}
						]
                }, true);

                let touSummary = [];
                if (response.summary) {
                    const on = parseFloat(response.summary.sum_tou_on_peak || 0);
                    const off = parseFloat(response.summary.sum_tou_off_peak || 0);
                    if (on > 0 || off > 0) touSummary = [{ value: on, name: 'On Peak' }, { value: off, name: 'Off Peak' }];
                }
                charts.touDonut.setOption({
					...getBaseOption('TOU_Pie'),
					color: ['#f97316', '#10b981'],
					legend: { bottom: 0 },
					title: touSummary.length === 0
						? { text: 'No Data', left: 'center', top: 'center', textStyle: { fontSize: 12, color: '#ccc' } }
						: {},
					series: [{
						name: 'TOU',
						type: 'pie',
						radius: ['50%', '70%'],
						center: ['50%', '45%'],
						itemStyle: { borderRadius: 5, borderColor: '#fff', borderWidth: 2 },
						labelLine: {
							show: true
						},
						label: {
							show: true,
							formatter: '{b}: {c} ({d}%)'
						},
						data: touSummary
					}]
				}, true);

                setTimeout(() => toggleLoaders(false), 300);
            },
            error: () => { Object.values(charts).forEach(c => c.hideLoading()); toggleLoaders(false); }
        });
    }

    $('#searchBtn').on('click', () => {
        const type = $('#filterType').val();
        let s, e;
        if (type === 'daily') { s = moment($('#dateStart').val()); e = moment($('#dateEnd').val()); }
        else if (type === 'monthly') { s = moment($('#monthStart').val(), 'YYYY-MM').startOf('month'); e = moment($('#monthEnd').val(), 'YYYY-MM').endOf('month'); }
        else if (type === 'yearly') { s = moment($('#yearStart').val(), 'YYYY').startOf('year'); e = moment($('#yearEnd').val(), 'YYYY').endOf('year'); }
        if(s.isValid() && e.isValid()) { currentStart = s; currentEnd = e; loadDashboardData(s, e); }
    });

    $('#buildingFilter, #touMeterFilter').on('change', () => loadDashboardData(currentStart, currentEnd));

    $(document).ready(() => {
        populateBuildingFilter();
        loadDashboardData(currentStart, currentEnd);
    });
    </script>
</body>
</html>