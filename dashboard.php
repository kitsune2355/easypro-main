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
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.3/dist/echarts.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/locale/th.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap');
        :root {
            --color-bg: #f8fafc;
            --color-primary: #006B9F;
            --color-primary-dark: #004a6f;
            --color-secondary: #04ADFF;
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
            position: relative;
        }

        .chart-container:fullscreen,
		.chart-container:-webkit-full-screen,
		.chart-container:-ms-fullscreen {
			width: 100vw !important;
			height: 100vh !important;
			background: #ffffff !important;
			color: #1e293b !important;
			border-radius: 0 !important;
			padding: 24px !important;
			display: flex !important;
			flex-direction: column !important;
			overflow: hidden !important;
		}
		
		.chart-container:fullscreen > .flex,
		.chart-container:-webkit-full-screen > .flex,
		.chart-container:-ms-fullscreen > .flex {
			flex: 0 0 auto !important;
			margin-bottom: 12px !important;
		}
		
		.chart-container:fullscreen .echart-box,
		.chart-container:fullscreen .echart-box-lg,
		.chart-container:fullscreen .echart-box-sm,
		.chart-container:-webkit-full-screen .echart-box,
		.chart-container:-webkit-full-screen .echart-box-lg,
		.chart-container:-webkit-full-screen .echart-box-sm,
		.chart-container:-ms-fullscreen .echart-box,
		.chart-container:-ms-fullscreen .echart-box-lg,
		.chart-container:-ms-fullscreen .echart-box-sm {
			width: 100% !important;
			height: auto !important;
			flex: 1 1 auto !important;
			min-height: 0 !important;
			background: #ffffff !important;
		}

        .echart-box:fullscreen,
        .echart-box-lg:fullscreen,
        .echart-box-sm:fullscreen,
        .echart-box:-webkit-full-screen,
        .echart-box-lg:-webkit-full-screen,
        .echart-box-sm:-webkit-full-screen,
        .echart-box:-ms-fullscreen,
        .echart-box-lg:-ms-fullscreen,
        .echart-box-sm:-ms-fullscreen {
            width: 100vw !important;
            height: 100vh !important;
            max-width: none !important;
            max-height: none !important;
            background: #fff !important;
            padding: 16px !important;
            box-sizing: border-box !important;
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

        .btn-chart-action {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            font-size: 0.875rem;
            font-weight: 500;
            transition: 0.2s;
        }
        .btn-chart-action:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
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

        .echart-box { width: 100%; height: 350px; }
        .echart-box-lg { width: 100%; height: 450px; }
        .echart-box-sm { width: 100%; height: 300px; }

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
                        <option value="monthly" selected>รายเดือน</option>
                        <option value="yearly">รายปี</option>
                    </select>
                </div>

                <div id="filterInputs" class="flex items-center gap-2"></div>

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

    <main class="max-w-full mx-auto px-6 pb-12">
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            <div class="kpi-card group cursor-pointer" onClick="goToMaintenanceInfo('')" title="คลิกเพื่อดูรายการ">
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
            
            <div class="kpi-card group cursor-pointer" onClick="goToMaintenanceInfo('pending')" title="คลิกเพื่อดูรายการ">
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

            <div class="kpi-card group cursor-pointer" onClick="goToMaintenanceInfo('inprogress')" title="คลิกเพื่อดูรายการ">
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

            <div class="kpi-card group cursor-pointer" onClick="goToMaintenanceInfo('completed')" title="คลิกเพื่อดูรายการ">
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

            <div class="kpi-card group cursor-pointer" onClick="goToMaintenanceInfo('cancel')" title="คลิกเพื่อดูรายการ">
                <div id="loader-stat-5" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">ยกเลิกงาน</p>
                        <h3 id="stat-cancel" class="text-4xl font-black text-rose-500 tracking-tight">0</h3>
                        <p class="text-xs text-slate-400 mt-2">รายการที่ถูกยกเลิก</p>
                    </div>
                    <div class="kpi-icon-wrapper bg-rose-50 text-rose-500 group-hover:bg-rose-500 group-hover:text-white transition-colors duration-300">
                        <i data-lucide="x-circle" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <div class="chart-container relative">
                <div id="loader-job-chart" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <div class="flex items-center justify-between mb-6 gap-3">
                    <div>
                        <h4 class="font-bold text-lg text-slate-800">ปริมาณงานตามช่วงเวลา</h4>
                        <p class="text-xs text-slate-400">แนวโน้มการแจ้งซ่อม</p>
                    </div>
                    <button type="button" onClick="toggleChartFullscreen('monthlyTotalChart')" class="btn-chart-action">
                        <i data-lucide="maximize" class="w-4 h-4"></i>
                        <span>เต็มจอ</span>
                    </button>
                </div>
                <div id="monthlyTotalChart" class="echart-box"></div>
            </div>
            
            <div class="chart-container relative">
                <div id="loader-building-chart" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <div class="flex items-center justify-between mb-6 gap-3">
                    <div>
                        <h4 class="font-bold text-lg text-slate-800">แยกตามอาคาร</h4>
                    </div>
                    <button type="button" onClick="toggleChartFullscreen('buildingChart')" class="btn-chart-action">
                        <i data-lucide="maximize" class="w-4 h-4"></i>
                        <span>เต็มจอ</span>
                    </button>
                </div>
                <div id="buildingChart" class="echart-box"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <div class="chart-container relative">
                <div id="loader-service-chart" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <div class="flex items-center justify-between mb-4 gap-3">
                    <h4 class="font-bold text-lg text-slate-800">ชนิดของการบริการ</h4>
                    <button type="button" onClick="toggleChartFullscreen('serviceTypeChart')" class="btn-chart-action">
                        <i data-lucide="maximize" class="w-4 h-4"></i>
                        <span>เต็มจอ</span>
                    </button>
                </div>
                <div id="serviceTypeChart" class="echart-box"></div>
            </div>
            <div class="chart-container relative">
                <div id="loader-category-chart" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <div class="flex items-center justify-between mb-4 gap-3">
                    <h4 class="font-bold text-lg text-slate-800">ประเภทงาน</h4>
                    <button type="button" onClick="toggleChartFullscreen('jobCategoryChart')" class="btn-chart-action">
                        <i data-lucide="maximize" class="w-4 h-4"></i>
                        <span>เต็มจอ</span>
                    </button>
                </div>
                <div id="jobCategoryChart" class="echart-box"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 mb-8">
            <div class="chart-container relative">
                <div id="loader-monthly-repair-chart" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <div class="flex items-center justify-between mb-4 gap-3">
                    <h4 class="font-bold text-lg text-slate-800">กราฟรวมแจ้งซ่อมทั้งหมดในแต่ละเดือน</h4>
                    <button type="button" onClick="toggleChartFullscreen('monthlyRepairSummaryChart')" class="btn-chart-action">
                        <i data-lucide="maximize" class="w-4 h-4"></i>
                        <span>เต็มจอ</span>
                    </button>
                </div>
                <div id="monthlyRepairSummaryChart" class="echart-box-lg"></div>
            </div>

            <div class="chart-container relative">
                <div id="loader-monthly-service-chart" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <div class="flex items-center justify-between mb-4 gap-3">
                    <h4 class="font-bold text-lg text-slate-800">กราฟรวมชนิดของการบริการ แยกแต่ละชนิดในแต่ละเดือน</h4>
                    <button type="button" onClick="toggleChartFullscreen('monthlyServiceSummaryChart')" class="btn-chart-action">
                        <i data-lucide="maximize" class="w-4 h-4"></i>
                        <span>เต็มจอ</span>
                    </button>
                </div>
                <div id="monthlyServiceSummaryChart" class="echart-box-lg"></div>
            </div>

            <div class="chart-container relative">
                <div id="loader-monthly-type-chart" class="skeleton-loader"><div class="h-full w-full shimmer rounded-lg"></div></div>
                <div class="flex items-center justify-between mb-4 gap-3">
                    <h4 class="font-bold text-lg text-slate-800">กราฟรวมประเภทงานในแต่ละเดือน แยกแต่ละประเภทงาน</h4>
                    <button type="button" onClick="toggleChartFullscreen('monthlyTypeSummaryChart')" class="btn-chart-action">
                        <i data-lucide="maximize" class="w-4 h-4"></i>
                        <span>เต็มจอ</span>
                    </button>
                </div>
                <div id="monthlyTypeSummaryChart" class="echart-box-lg"></div>
            </div>
        </div>

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
                
                <div id="touFilterContainer" class="hidden mb-4 flex justify-end">
                    <select id="touMeterFilter" class="bg-white border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-2 focus:ring-sky-500 p-2 custom-select">
                        <option value="">มิเตอร์ TOU ทั้งหมด</option>
                    </select>
                </div>

                <div id="container-wt" class="block">
                    <div class="flex justify-end mb-4">
                        <button type="button" onClick="toggleChartFullscreen('wtChart')" class="btn-chart-action">
                            <i data-lucide="maximize" class="w-4 h-4"></i>
                            <span>เต็มจอ</span>
                        </button>
                    </div>
                    <div id="wtChart" class="echart-box-lg"></div>
                </div>

                <div id="container-et" class="hidden">
                    <div class="flex justify-end mb-4">
                        <button type="button" onClick="toggleChartFullscreen('etChart')" class="btn-chart-action">
                            <i data-lucide="maximize" class="w-4 h-4"></i>
                            <span>เต็มจอ</span>
                        </button>
                    </div>
                    <div id="etChart" class="echart-box-lg"></div>
                </div>

                <div id="container-tou" class="hidden">
				
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <div class="lg:col-span-2">
                            <div class="flex justify-end mb-4">
                                <button type="button" onClick="toggleChartFullscreen('touChart')" class="btn-chart-action">
                                    <i data-lucide="maximize" class="w-4 h-4"></i>
                                    <span>เต็มจอ</span>
                                </button>
                            </div>
                            <div id="touChart" class="echart-box-lg"></div>
                        </div>
                        <div class="rounded-xl p-4 border border-slate-100 flex flex-col justify-center">
                            <div class="flex items-center justify-between gap-3 mb-4">
                                <h5 class="text-center font-bold text-slate-500 uppercase text-xs tracking-wider">สัดส่วนการใช้พลังงาน</h5>
                                <button type="button" onClick="toggleChartFullscreen('touDonutChart')" class="btn-chart-action">
                                    <i data-lucide="maximize" class="w-4 h-4"></i>
                                    <span>เต็มจอ</span>
                                </button>
                            </div>
                            <div id="touDonutChart" class="echart-box-sm"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
    lucide.createIcons();
    moment.locale('th');
	
    let currentStart = moment().startOf('month');
    let currentEnd = moment().endOf('month');
    let activeEnergyTab = 'wt';
    let isBuildingPopulated = false; 
    let isMeterFilterPopulated = false;
    let ag_id = <?php echo $sess_user_agency_es ?>;

    const formatNum = (num) => new Intl.NumberFormat().format(num);

    // ✅ กดที่การ์ด KPI แล้วไปหน้า maintenance_info พร้อม filter ช่วงวันที่ + สถานะ
    function goToMaintenanceInfo(status) {
        const type = $('#filterType').val();
        let s, e;

        if (type === 'daily') {
            s = moment($('#dateStart').val());
            e = moment($('#dateEnd').val());
        } else if (type === 'monthly') {
            s = moment($('#monthStart').val(), 'YYYY-MM').startOf('month');
            e = moment($('#monthEnd').val(), 'YYYY-MM').endOf('month');
        } else if (type === 'yearly') {
            s = moment($('#yearStart').val(), 'YYYY').startOf('year');
            e = moment($('#yearEnd').val(), 'YYYY').endOf('year');
        }

        const params = new URLSearchParams();
        
        // หากดึงค่าได้ถูกต้อง ให้ส่งไป ถ้าไม่ได้ให้ใช้ currentStart ตัวเดิมเป็น fallback
        params.set('dateFrom', (s && s.isValid()) ? s.format('YYYY-MM-DD') : currentStart.format('YYYY-MM-DD'));
        params.set('dateTo', (e && e.isValid()) ? e.format('YYYY-MM-DD') : currentEnd.format('YYYY-MM-DD'));
        
        if (status) params.set('status', status);
        window.location.href = 'maintenance_info.php?' + params.toString();
    }

    const hideZeroLabel = (params) => {
        const value = typeof params.value === 'object' && params.value !== null
            ? (params.value.value ?? 0)
            : params.value;
        return Number(value) === 0 ? '' : formatNum(value);
    };
	
    const hideZeroPieLabel = (params) => {
        return Number(params.value) === 0
            ? ''
            : `${params.name}: ${formatNum(params.value)} (${params.percent}%)`;
    };

    const chartColors = ['#0ea5e9', '#6366f1', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6', '#06b6d4', '#ec4899', '#14b8a6', '#e11d48', '#84cc16', '#f97316'];

    function exportChartToCSV(chartInstance, fileName) {
        try {
            const option = chartInstance.getOption();
            let csvContent = "";
            let header = [];
            let dataRows = [];
            
            let xAxisData = [];
            if (option.xAxis && option.xAxis[0] && option.xAxis[0].data) {
                xAxisData = option.xAxis[0].data;
                header.push("Date/Category");
            } else if (option.yAxis && option.yAxis[0] && option.yAxis[0].type === 'category' && option.yAxis[0].data) {
                xAxisData = option.yAxis[0].data;
                header.push("Category");
            }

            if (option.series) {
                option.series.forEach(s => {
                    header.push(s.name || "Value");
                    if (s.type === 'pie' || s.type === 'donut') {
                        if (header.length === 2 && header[0] === 'Date/Category') header = ["Name", "Value"]; 
                        s.data.forEach((d, i) => {
                            if (!dataRows[i]) dataRows[i] = [];
                            dataRows[i][0] = d.name;
                            dataRows[i][1] = d.value;
                        });
                    } else {
                        s.data.forEach((d, i) => {
                            if (!dataRows[i]) dataRows[i] = [xAxisData[i] || ""];
                            let val = (typeof d === 'object' && d !== null) ? (d.value || d.y || d) : d;
                            dataRows[i].push(val);
                        });
                    }
                });
            }

            csvContent += header.join(",") + "\r\n";
            dataRows.forEach(row => { csvContent += row.join(",") + "\r\n"; });

            const blob = new Blob(["\ufeff" + csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.setAttribute("href", url);
            link.setAttribute("download", fileName + "_" + moment().format('YYYYMMDD') + ".csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        } catch(e) { 
            console.error(e);
            alert("Export Failed"); 
        }
    }

    let fullscreenChartId = null;
	
	function getChartInstanceById(chartId) {
		const chartEl = document.getElementById(chartId);
		if (!chartEl) return null;
		return echarts.getInstanceByDom(chartEl);
	}
	
	function getFullscreenElement() {
		return document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement;
	}
	
	function resizeFullscreenChart() {
		if (!fullscreenChartId) return;
	
		const chartEl = document.getElementById(fullscreenChartId);
		if (!chartEl) return;
	
		const chart = echarts.getInstanceByDom(chartEl);
		if (!chart) return;
	
		const fullscreenEl = getFullscreenElement();
		if (!fullscreenEl) return;
	
		const headerEl = fullscreenEl.querySelector(':scope > .flex');
		const fullscreenStyle = window.getComputedStyle(fullscreenEl);
	
		const paddingTop = parseFloat(fullscreenStyle.paddingTop) || 0;
		const paddingBottom = parseFloat(fullscreenStyle.paddingBottom) || 0;
		const headerHeight = headerEl ? headerEl.offsetHeight : 0;
	
		const availableHeight = window.innerHeight - paddingTop - paddingBottom - headerHeight - 12;
	
		chartEl.style.width = '100%';
		chartEl.style.height = Math.max(availableHeight, 300) + 'px';
		chartEl.style.background = '#ffffff';
	
		requestAnimationFrame(() => {
			chart.resize();
			setTimeout(() => chart.resize(), 100);
			setTimeout(() => chart.resize(), 300);
		});
	}
	
	function clearFullscreenChartSize() {
		if (!fullscreenChartId) return;
	
		const chartEl = document.getElementById(fullscreenChartId);
		const chart = chartEl ? echarts.getInstanceByDom(chartEl) : null;
	
		if (chartEl) {
			chartEl.style.width = '';
			chartEl.style.height = '';
			chartEl.style.background = '';
		}
	
		if (chart) {
			setTimeout(() => chart.resize(), 100);
		}
	
		fullscreenChartId = null;
	}
	
	function toggleChartFullscreen(chartId) {
		const chartEl = document.getElementById(chartId);
		if (!chartEl) return;
	
		const container = chartEl.closest('.chart-container') || chartEl.parentElement || chartEl;
		fullscreenChartId = chartId;
	
		if (!getFullscreenElement()) {
			if (container.requestFullscreen) {
				container.requestFullscreen();
			} else if (container.webkitRequestFullscreen) {
				container.webkitRequestFullscreen();
			} else if (container.msRequestFullscreen) {
				container.msRequestFullscreen();
			}
		} else {
			if (document.exitFullscreen) {
				document.exitFullscreen();
			} else if (document.webkitExitFullscreen) {
				document.webkitExitFullscreen();
			} else if (document.msExitFullscreen) {
				document.msExitFullscreen();
			}
		}
	}
	
	function handleFullscreenChange() {
		if (getFullscreenElement()) {
			resizeFullscreenChart();
		} else {
			clearFullscreenChartSize();
		}
	}
	
	document.addEventListener('fullscreenchange', handleFullscreenChange);
	document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
	document.addEventListener('MSFullscreenChange', handleFullscreenChange);
	
	window.addEventListener('resize', function() {
		if (getFullscreenElement()) {
			resizeFullscreenChart();
		} else {
			Object.values(charts).forEach(c => c.resize());
		}
	});

    const getBaseOption = (titleForExport) => ({
        textStyle: { fontFamily: 'Prompt' },
        color: chartColors,
        backgroundColor: '#ffffff',
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
                    show: true,
                    title: 'Export CSV',
                    icon: 'path://M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M15.8,20H14L12,16.6L10,20H8.2L11.1,15.5L8.2,11H10L12,14.4L14,11H15.8L12.9,15.5L15.8,20M13,9V3.5L18.5,9H13Z',
                    onclick: (opt, instance) => exportChartToCSV(instance, titleForExport || 'chart')
                },
                saveAsImage: { show: true, title: 'บันทึกภาพ', pixelRatio: 2 }
            },
            iconStyle: { borderColor: '#94a3b8' },
            right: 10,
            top: 0
        }
    });

    const charts = {
        job: echarts.init(document.querySelector("#monthlyTotalChart")),
        building: echarts.init(document.querySelector("#buildingChart")),
        service: echarts.init(document.querySelector("#serviceTypeChart")),
        category: echarts.init(document.querySelector("#jobCategoryChart")),
        monthlyRepairSummary: echarts.init(document.querySelector("#monthlyRepairSummaryChart")),
        monthlyServiceSummary: echarts.init(document.querySelector("#monthlyServiceSummaryChart")),
        monthlyTypeSummary: echarts.init(document.querySelector("#monthlyTypeSummaryChart")),
        wt: echarts.init(document.querySelector("#wtChart")),
        et: echarts.init(document.querySelector("#etChart")),
        tou: echarts.init(document.querySelector("#touChart")),
        touDonut: echarts.init(document.querySelector("#touDonutChart"))
    };

    window.addEventListener('resize', () => Object.values(charts).forEach(c => c.resize()));

    function onlyBarLegend(series) {
        return series
            .filter(s => s.type === 'bar')
            .map(s => s.name);
    }

    window.switchEnergyTab = function(type) {
        activeEnergyTab = type;
        $('.tab-pill').removeClass('active');
        $('#tab-btn-' + type).addClass('active');
        
        $('#container-wt, #container-et, #container-tou').addClass('hidden').removeClass('block');
        $('#container-' + type).removeClass('hidden').addClass('block');
        
        if (type === 'tou') $('#touFilterContainer').removeClass('hidden');
        else { $('#touFilterContainer').addClass('hidden'); $('#touMeterFilter').val(''); }

        setTimeout(() => {
            if (type === 'wt') charts.wt.resize();
            if (type === 'et') charts.et.resize();
            if (type === 'tou') {
                charts.tou.resize();
                charts.touDonut.resize();
            }
        }, 300);

        loadDashboardData(currentStart, currentEnd);
    };

    function toggleLoaders(show) {
        show ? $('.skeleton-loader').removeClass('hidden') : $('.skeleton-loader').addClass('hidden');
    }

    function populateBuildingFilter() {
        if (isBuildingPopulated) return;
        $.ajax({
            url: 'get_all_building.php',
            type: 'GET',
            data: { ag_id: ag_id },
            dataType: 'json',
            success: function(response) {
                if (response.building && response.building.length > 0) {
                    const $b = $('#buildingFilter');
                    $b.find('option:not(:first)').remove();
                    response.building.forEach(b => $b.append('<option value="' + b.area_id + '">' + b.area_name + '</option>'));
                    isBuildingPopulated = true;
                }
            }
        });
    }

    function populateMeterFilters(data) {
        if (!data || data.length === 0 || isMeterFilterPopulated) return;
        const meterMap = new Map();
        data.forEach(day => {
            if (day.meter && day.meter.tou) {
                day.meter.tou.forEach(m => meterMap.set(m.mt_id, m.text));
            }
        });
        if (meterMap.size > 0) {
            const $t = $('#touMeterFilter');
            meterMap.forEach((n, i) => $t.append('<option value="' + i + '">' + n + '</option>'));
            isMeterFilterPopulated = true;
        }
    }

    function getMonthLabel(dateStr) {
        const m = moment(dateStr).locale('th');
        return m.format('MMM') + ' ' + String(m.year() + 543).slice(-2);
    }
	
	function createMonthlyTopLineSeries(monthLabels, barSeries, lineName) {
		const lineData = monthLabels.map((label, monthIndex) => {
			let maxVal = 0;
	
			barSeries.forEach(series => {
				const val = Number(series.data[monthIndex] || 0);
				if (val > maxVal) maxVal = val;
			});
	
			return {
				value: [monthIndex, maxVal],
				monthIndex: monthIndex,
				maxVal: maxVal
			};
		});
	
		return {
			name: lineName,
			type: 'custom',
			silent: true,
			tooltip: { show: false },
			renderItem: function(params, api) {
				const monthIndex = api.value(0);
				const maxVal = api.value(1);
	
				if (!maxVal || maxVal <= 0) return;
	
				const center = api.coord([monthIndex, maxVal]);
				const bandWidth = api.size([1, 0])[0];
				const halfWidth = bandWidth * 0.32;
	
				return {
					type: 'line',
					shape: {
						x1: center[0] - halfWidth,
						y1: center[1] - 8,
						x2: center[0] + halfWidth,
						y2: center[1] - 8
					},
					style: {
						stroke: '#2563eb',
						lineWidth: 2
					}
				};
			},
			data: lineData,
			z: 10
		};
	}
	
	function createTrendCustomSeries(monthLabels, barSeries, trendName) {
		const customData = monthLabels.map((label, monthIndex) => {
			const row = [monthIndex];
			barSeries.forEach(series => {
				row.push(Number(series.data[monthIndex] || 0));
			});
			return row;
		});
	
		const encodeY = [];
		for (var i = 0; i < barSeries.length; i++) {
			encodeY.push(i + 1);
		}
	
		return {
			type: 'custom',
			name: trendName || 'trend',
			renderItem: function (params, api) {
				var xValue = api.value(0);
				var currentSeriesIndices = api.currentSeriesIndices();
				var barLayout = api.barLayout({
					barGap: '30%',
					barCategoryGap: '20%',
					count: currentSeriesIndices.length - 1
				});
	
				var points = [];
	
				for (var i = 0; i < currentSeriesIndices.length; i++) {
					var seriesIndex = currentSeriesIndices[i];
	
					if (seriesIndex !== params.seriesIndex) {
						var point = api.coord([xValue, api.value(seriesIndex)]);
						point[0] += barLayout[i - 1].offsetCenter;
						point[1] -= 12;
						points.push(point);
					}
				}
	
				return {
					type: 'polyline',
					shape: {
						points: points
					},
					style: api.style({
						stroke: '#2563eb',
						fill: 'none',
						lineWidth: 2
					})
				};
			},
			itemStyle: {
				borderWidth: 2
			},
			encode: {
				x: 0,
				y: encodeY
			},
			data: customData,
			z: 100,
			silent: true,
			tooltip: { show: false }
		};
	}
	
    function buildMonthlySummary(data) {
		const monthMap = {};
		const serviceNames = new Set();
		const typeNames = new Set();
	
		data.forEach(day => {
			const monthKey = moment(day.date).format('YYYY-MM');
			const monthLabel = getMonthLabel(day.date);
	
			if (!monthMap[monthKey]) {
				monthMap[monthKey] = {
					label: monthLabel,
					repairs: 0,
					service: {},
					type: {}
				};
			}
	
			monthMap[monthKey].repairs += Number(day.repairs || 0);
	
			(day.service || []).forEach(s => {
				const name = s.text || '-';
				const value = Number(s.value || 0);
				serviceNames.add(name);
				monthMap[monthKey].service[name] = (monthMap[monthKey].service[name] || 0) + value;
			});
	
			(day.type || []).forEach(t => {
				const name = t.text || '-';
				const value = Number(t.value || 0);
				typeNames.add(name);
				monthMap[monthKey].type[name] = (monthMap[monthKey].type[name] || 0) + value;
			});
		});
	
		const sortedMonthKeys = Object.keys(monthMap).sort();
	
		return {
			monthLabels: sortedMonthKeys.map(k => monthMap[k].label),
			repairs: sortedMonthKeys.map(k => monthMap[k].repairs),
	
			serviceSeries: Array.from(serviceNames).map(name => ({
				name: name,
				type: 'bar',
				emphasis: { focus: 'series' },
				itemStyle: {
					opacity: 0.75
				},
				label: {
					show: true,
					position: 'top',
					formatter: hideZeroLabel
				},
				data: sortedMonthKeys.map(k => monthMap[k].service[name] || 0)
			})),
	
			typeSeries: Array.from(typeNames).map(name => ({
				name: name,
				type: 'bar',
				emphasis: { focus: 'series' },
				itemStyle: {
					opacity: 0.75
				},
				label: {
					show: true,
					position: 'top',
					formatter: hideZeroLabel
				},
				data: sortedMonthKeys.map(k => monthMap[k].type[name] || 0)
			}))
		};
	}

    $('#filterType').on('change', function() {
        const type = $(this).val();
        let html = '';
        const style = 'bg-white border border-slate-200 text-slate-700 text-sm rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 block p-2 outline-none';
        if (type === 'daily') {
            html = '<input type="date" id="dateStart" class="' + style + '" value="' + moment().subtract(7, 'days').format('YYYY-MM-DD') + '">' +
                   '<span class="text-slate-400">-</span>' +
                   '<input type="date" id="dateEnd" class="' + style + '" value="' + moment().format('YYYY-MM-DD') + '">';
        } else if (type === 'monthly') {
            html = '<input type="month" id="monthStart" class="' + style + '" value="' + moment().format('YYYY-MM') + '">' +
                   '<span class="text-slate-400">-</span>' +
                   '<input type="month" id="monthEnd" class="' + style + '" value="' + moment().format('YYYY-MM') + '">';
        } else if (type === 'yearly') {
            const curY = new Date().getFullYear();
            let opts = '';
            for(let i = curY; i >= curY - 5; i--) opts += '<option value="' + i + '">' + i + '</option>';
            html = '<select id="yearStart" class="' + style + '">' + opts + '</select>' +
                   '<span class="text-slate-400">-</span>' +
                   '<select id="yearEnd" class="' + style + '">' + opts + '</select>';
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
        if (activeEnergyTab === 'tou' && selectedMeterId) {
            params.mt_type = 'tou';
            params.mt_id = selectedMeterId;
        }

        $.ajax({
            url: 'get_all_dashboard.php',
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                Object.values(charts).forEach(c => c.hideLoading());
                if (response.error) { toggleLoaders(false); return; }

                const data = response.data || [];
                const monthlySummary = buildMonthlySummary(data);

                populateMeterFilters(data);

                $('#stat-total').text(formatNum(data.reduce((s, i) => s + Number(i.repairs || 0), 0)));
                $('#stat-completed').text(formatNum(data.reduce((s, i) => s + Number(i.completed || 0), 0)));
                $('#stat-pending').text(formatNum(data.reduce((s, i) => s + Number(i.pending || 0), 0)));
                $('#stat-doing').text(formatNum(data.reduce((s, i) => s + Number(i.inprogress || 0), 0)));
                $('#stat-cancel').text(formatNum(data.reduce((s, i) => s + Number(i.canceled || 0), 0)));

                const jobDates = data.map(i => {
                    const m = moment(i.date).locale('th');
                    const buddhistYear = m.year() + 543;
                    return m.format('DD MMM') + ' ' + buddhistYear;
                });
                const jobValues = data.map(i => Number(i.repairs || 0));

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
                            formatter: hideZeroLabel
                        },
                        data: jobValues
                    }]
                }, true);

                const areaSum = {}, svcSum = {}, typeSum = {};
                const wtSeriesMap = {}, etSeriesMap = {}, touDailyData = [];
                const allDates = [];

                data.forEach(day => {
                    const m = moment(day.date).locale('th');
                    const buddhistYearShort = String(m.year() + 543).slice(-2);
                    const dFmt = m.format('DD MMM') + ' ' + buddhistYearShort;
                    allDates.push(dFmt);

                    if(day.area) day.area.forEach(a => areaSum[a.text] = (areaSum[a.text] || 0) + Number(a.value || 0));
                    if(day.service) day.service.forEach(s => svcSum[s.text] = (svcSum[s.text] || 0) + Number(s.value || 0));
                    if(day.type) day.type.forEach(t => typeSum[t.text] = (typeSum[t.text] || 0) + Number(t.value || 0));

                    if (day.meter && day.meter.wt) {
                        day.meter.wt.forEach(m => {
                            if (!wtSeriesMap[m.text]) wtSeriesMap[m.text] = [];
                            wtSeriesMap[m.text].push(Number(m.unitDay || 0));
                        });
                    }

                    if (day.meter && day.meter.et) {
                        day.meter.et.forEach(m => {
                            if (!etSeriesMap[m.text]) etSeriesMap[m.text] = [];
                            etSeriesMap[m.text].push(Number(m.unitDay || 0));
                        });
                    }
                    
                    let tT = 0, tOn = 0, tOff = 0;
                    if (day.meter && day.meter.tou) {
                        day.meter.tou.forEach(m => {
                            tT += Number(m.total_unit || 0);
                            tOn += Number(m.on_peak_unit || 0);
                            tOff += Number(m.off_peak_unit || 0);
                        });
                    }
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
                            formatter: hideZeroLabel,
                            fontWeight: 'bold'
                        }
                    }]
                }, true);

                charts.service.setOption({
                    ...getBaseOption('Service_Stats'),
                    tooltip: { trigger: 'item' },
                    legend: {
                        type: 'plain',
                        bottom: 0,
                        left: 'center',
                        icon: 'roundRect',
                        itemWidth: 18,
                        itemHeight: 10,
                        textStyle: { fontSize: 12, color: '#475569' }
                    },
                    series: [{
                        name: 'บริการ',
                        type: 'pie',
                        radius: ['40%', '60%'],
                        center: ['50%', '42%'],
                        itemStyle: { borderColor: '#fff', borderWidth: 1 },
                        labelLine: { show: true },
                        label: {
                            show: true,
                            formatter: '{b}: {c} ({d}%)'
                        },
                        data: Object.keys(svcSum).map(k => ({ value: svcSum[k], name: k }))
                    }]
                }, true);
				
                charts.category.setOption({
                    ...getBaseOption('Category_Stats'),
                    tooltip: { trigger: 'item' },
                    legend: {
                        type: 'plain',
                        bottom: 0,
                        left: 'center',
                        icon: 'roundRect',
                        itemWidth: 18,
                        itemHeight: 10,
                        textStyle: { fontSize: 12, color: '#475569' }
                    },
                    series: [{
                        name: 'หมวดหมู่',
                        type: 'pie',
                        radius: '60%',
                        center: ['50%', '42%'],
                        itemStyle: { borderColor: '#fff', borderWidth: 1 },
                        labelLine: { show: true },
                        label: {
                            show: true,
                            formatter: '{b}: {c} ({d}%)'
                        },
                        data: Object.keys(typeSum).map(k => ({ value: typeSum[k], name: k }))
                    }]
                }, true);

                charts.monthlyRepairSummary.setOption({
                    ...getBaseOption('Monthly_Repair_Summary'),
                    tooltip: { trigger: 'axis' },
                    legend: { show: false },
                    xAxis: {
                        type: 'category',
                        data: monthlySummary.monthLabels,
                        axisLine: { lineStyle: { color: '#cbd5e1' } }
                    },
                    yAxis: {
                        type: 'value',
                        splitLine: { lineStyle: { type: 'dashed', color: '#f1f5f9' } }
                    },
                    dataZoom: [
                        { type: 'inside' },
                        { type: 'slider', bottom: 10, height: 20 }
                    ],
                    series: [{
                        name: 'รวมแจ้งซ่อม',
                        type: 'bar',
                        barMaxWidth: 50,
                        itemStyle: {
                            borderRadius: [6, 6, 0, 0],
                            color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                { offset: 0, color: '#0ea5e9' },
                                { offset: 1, color: '#2563eb' }
                            ])
                        },
                        label: {
                            show: true,
                            position: 'top',
                            formatter: hideZeroLabel
                        },
                        data: monthlySummary.repairs
                    }]
                }, true);

               const monthlyServiceTrendSeries = createTrendCustomSeries(
					monthlySummary.monthLabels,
					monthlySummary.serviceSeries,
					'trend'
				);
				
				charts.monthlyServiceSummary.setOption({
					...getBaseOption('Monthly_Service_Summary'),
					tooltip: { trigger: 'axis' },
					legend: {
						type: 'scroll',
						top: 0,
						data: ['trend'].concat(monthlySummary.serviceSeries.map(s => s.name))
					},
					grid: {
						left: '3%',
						right: '4%',
						top: '15%',
						bottom: '18%',
						containLabel: true
					},
					xAxis: {
						type: 'category',
						data: monthlySummary.monthLabels,
						axisLine: { lineStyle: { color: '#cbd5e1' } }
					},
					yAxis: {
						type: 'value',
						splitLine: { lineStyle: { type: 'dashed', color: '#f1f5f9' } }
					},
					dataZoom: [
						{ type: 'inside' },
						{ type: 'slider', bottom: 10, height: 20 }
					],
					series: [monthlyServiceTrendSeries].concat(monthlySummary.serviceSeries)
				}, true);

               const monthlyTypeTrendSeries = createTrendCustomSeries(
					monthlySummary.monthLabels,
					monthlySummary.typeSeries,
					'trend'
				);
				
				charts.monthlyTypeSummary.setOption({
					...getBaseOption('Monthly_Type_Summary'),
					tooltip: { trigger: 'axis' },
					legend: {
						type: 'scroll',
						top: 0,
						data: ['trend'].concat(monthlySummary.typeSeries.map(s => s.name))
					},
					grid: {
						left: '3%',
						right: '4%',
						top: '15%',
						bottom: '18%',
						containLabel: true
					},
					xAxis: {
						type: 'category',
						data: monthlySummary.monthLabels,
						axisLine: { lineStyle: { color: '#cbd5e1' } }
					},
					yAxis: {
						type: 'value',
						splitLine: { lineStyle: { type: 'dashed', color: '#f1f5f9' } }
					},
					dataZoom: [
						{ type: 'inside' },
						{ type: 'slider', bottom: 10, height: 20 }
					],
					series: [monthlyTypeTrendSeries].concat(monthlySummary.typeSeries)
				}, true);

                function updateLineChart(chart, seriesMap, title) {
                    const meterNames = Object.keys(seriesMap);

                    const series = meterNames.map(name => ({
                        name: name,
                        type: 'line',
                        smooth: true,
                        symbol: 'circle',
                        symbolSize: 8,
                        lineStyle: { width: 2 },
                        emphasis: { focus: 'series' },
                        label: {
                            show: true,
                            position: 'top',
                            formatter: hideZeroLabel
                        },
                        data: seriesMap[name]
                    }));

                    chart.setOption({
                        ...getBaseOption(title),
                        title: series.length === 0 ? {
                            text: 'ไม่พบข้อมูล',
                            left: 'center',
                            top: 'center',
                            textStyle: { color: '#94a3b8' }
                        } : {},
                        legend: {
                            type: 'plain',
                            bottom: 0,
                            left: 'center',
                            icon: 'roundRect',
                            itemWidth: 18,
                            itemHeight: 10,
                            textStyle: { fontSize: 12, color: '#475569' }
                        },
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
                        grid: {
                            left: '3%',
                            right: '4%',
                            top: '10%',
                            bottom: '22%',
                            containLabel: true
                        },
                        xAxis: {
                            type: 'category',
                            boundaryGap: false,
                            data: allDates,
                            axisLine: { lineStyle: { color: '#cbd5e1' } }
                        },
                        yAxis: {
                            type: 'value',
                            splitLine: {
                                lineStyle: {
                                    type: 'dashed',
                                    color: '#f1f5f9'
                                }
                            }
                        },
                        dataZoom: [
                            { type: 'inside' },
                            { type: 'slider', bottom: 55, height: 18 }
                        ],
                        series: series
                    }, true);
                }

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
                                formatter: hideZeroLabel
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
                                formatter: hideZeroLabel
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
                                formatter: hideZeroLabel
                            },
                            data: touDailyData.map(d => d.off)
                        }
                    ]
                }, true);

                let touSummary = [];
                if (response.summary) {
                    const on = parseFloat(response.summary.sum_tou_on_peak || 0);
                    const off = parseFloat(response.summary.sum_tou_off_peak || 0);
                    if (on > 0 || off > 0) {
                        touSummary = [
                            { value: on, name: 'On Peak' },
                            { value: off, name: 'Off Peak' }
                        ];
                    }
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
                        labelLine: { show: true },
                        label: {
                            show: true,
                            formatter: '{b}: {c} ({d}%)'
                        },
                        data: touSummary
                    }]
                }, true);

                setTimeout(() => {
                    toggleLoaders(false);
                    lucide.createIcons();
                }, 300);
            },
            error: function() {
                Object.values(charts).forEach(c => c.hideLoading());
                toggleLoaders(false);
            }
        });
    }

    $('#searchBtn').on('click', function() {
        const type = $('#filterType').val();
        let s, e;

        if (type === 'daily') {
            s = moment($('#dateStart').val());
            e = moment($('#dateEnd').val());
        } else if (type === 'monthly') {
            s = moment($('#monthStart').val(), 'YYYY-MM').startOf('month');
            e = moment($('#monthEnd').val(), 'YYYY-MM').endOf('month');
        } else if (type === 'yearly') {
            s = moment($('#yearStart').val(), 'YYYY').startOf('year');
            e = moment($('#yearEnd').val(), 'YYYY').endOf('year');
        }

        if (s.isValid() && e.isValid()) {
            currentStart = s;
            currentEnd = e;
            loadDashboardData(s, e);
        }
    });

    $('#buildingFilter, #touMeterFilter').on('change', function() {
        loadDashboardData(currentStart, currentEnd);
    });

    $(document).ready(function() {
        populateBuildingFilter();
        loadDashboardData(currentStart, currentEnd);
    });
    </script>
</body>
</html>