<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Machine Asset Management - PM</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script src="js/img-carousel.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-enterprise/dist/ag-grid-enterprise.min.js"></script>

    <style>
        :root {
            --color-bg-light: #F0F4F8;
            --color-primary: #006B9F; 
            --color-secondary: #04ADFF;
            --font-family: 'Kanit', sans-serif;
        }
        
        body {
            font-family: var(--font-family);
            background-color: var(--color-bg-light); 
            height: 100vh;
            overflow: hidden; 
        }

        .btn-gradient {
            background: linear-gradient(to right, #006B9F, #04ADFF);
            color: white;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            box-shadow: 0 10px 20px -5px rgba(0, 107, 159, 0.4);
            transform: translateY(-1px);
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* Handsontable Customization */
        .handsontable { font-family: 'Kanit', sans-serif; font-size: 14px; z-index: 0; }
        .ht_master tr th { background-color: #f8fafc; color: #334155; font-weight: 500; }
        
        /* Top Tab active state */
        .nav-item { border-bottom: 2px solid transparent; color: #64748b; transition: all 0.2s; }
        .nav-item:hover { color: #0f172a; border-color: #cbd5e1; }
        .nav-item.active { color: var(--color-primary); border-color: var(--color-primary); font-weight: 500; }
        .nav-item.active i { color: var(--color-primary); }
        
        /* Hide scrollbar for tabs */
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="flex flex-col antialiased text-slate-700 h-screen w-full">

    <nav class="flex-none px-6 py-4 flex items-center justify-between border-b border-slate-200 bg-white z-20 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="p-2 bg-[#006B9F] rounded-lg shadow-md text-white">
                <i data-lucide="notebook-pen" class="w-6 h-6"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-800 leading-tight" id="header-title">Preventive Maintenance</h2>
                <p class="text-xs text-slate-500" id="header-subtitle">ติดตามและตรวจสอบแผนการบำรุงรักษา</p>
            </div>
        </div>
    </nav>

    <div class="flex-none bg-white border-b border-slate-200 px-6 z-10 shadow-sm">
        <ul class="flex space-x-8 overflow-x-auto hide-scrollbar">
            <li>
                <button onclick="switchTab('dashboard')" class="nav-item active flex items-center gap-2 py-3.5 text-sm whitespace-nowrap outline-none">
                    <i data-lucide="calendar-days" class="w-4 h-4"></i> ปฏิทิน
                </button>
            </li>
            <li>
                <button onclick="switchTab('holiday')" class="nav-item flex items-center gap-2 py-3.5 text-sm whitespace-nowrap outline-none">
                    <i data-lucide="calendar-off" class="w-4 h-4"></i> จัดการวันหยุด
                </button>
            </li>
            <li>
                <button onclick="switchTab('checksheet')" class="nav-item flex items-center gap-2 py-3.5 text-sm whitespace-nowrap outline-none">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> สร้างเช็คชีต
                </button>
            </li>
            <li>
                <button onclick="switchTab('plan')" class="nav-item flex items-center gap-2 py-3.5 text-sm whitespace-nowrap outline-none">
                    <i data-lucide="clipboard-list" class="w-4 h-4"></i> จัดการแผน PM
                </button>
            </li>
            <li>
                <button onclick="switchTab('history')" class="nav-item flex items-center gap-2 py-3.5 text-sm whitespace-nowrap outline-none">
                    <i data-lucide="history" class="w-4 h-4"></i> ประวัติการบำรุงรักษา
                </button>
            </li>
        </ul>
    </div>

    <div class="flex-1 flex flex-col overflow-hidden bg-[var(--color-bg-light)]">
        <main class="flex-1 overflow-y-auto px-6 py-4">
            
            <div id="tab-dashboard" class="tab-content block space-y-6">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-slate-800">ปฏิทินปฏิบัติงาน PM</h3>
                    </div>
                    <div id="pm-calendar" style="height: 600px;"></div>
                </div>
            </div>

            <div id="tab-plan" class="tab-content hidden space-y-4">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800">รายการแผน PM ที่ใช้งานอยู่</h3>
                            <p class="text-[12px] text-slate-500">จัดการแผนงานและกำหนดรอบการบำรุงรักษา</p>
                        </div>
                        <button onclick="openPlanDrawer()" class="btn-gradient px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                            <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มแผน PM
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto border border-slate-200 rounded-lg">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-600 bg-slate-50 uppercase border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3">อาคาร</th>
                                    <th class="px-4 py-3">เครื่องจักร</th>
                                    <th class="px-4 py-3">ฟอร์มที่ใช้</th>
                                    <th class="px-4 py-3">รอบ PM</th>
                                    <th class="px-4 py-3">รอบถัดไป</th>
                                    <th class="px-4 py-3 text-center">สถานะ</th>
                                    <th class="px-4 py-3 text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="plan-table-body" class="divide-y divide-slate-100">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="tab-checksheet" class="tab-content hidden space-y-6">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800">สร้างแบบฟอร์มตรวจสอบ (Checksheet Builder)</h3>
                            <p class="text-sm text-slate-500">กำหนดหัวข้อและมาตรฐานการตรวจสอบเชิงเทคนิค</p>
                        </div>
                        <button class="btn-gradient px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2" onclick="saveChecksheet()">
                            <i data-lucide="save" class="w-4 h-4"></i> บันทึก Template
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อเช็คชีต</label>
                            <input type="text" id="checksheet-name" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">หมวดหมู่/แผนก</label>
                            <input type="text" id="checksheet-dept" placeholder="เช่น แผนกวิศวกรรม, HVAC" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">วันที่มีผล</label>
                            <input type="date" id="checksheet-effective-date" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">เลขที่เอกสาร</label>
                            <input type="text" id="checksheet-doc-no" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Revision No. *</label>
                            <input type="text" id="checksheet-rev-no" value="00" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">เวลาที่คาดว่าจะเสร็จ (นาที)</label>
                            <input type="number" id="checksheet-estimated-time" placeholder="60" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                        </div>
                        
                        <div class="md:col-span-2 relative z-0">
                            <div class="flex justify-between items-center mb-1">
                                <label class="block text-sm font-medium text-slate-700">รายการอะไหล่ที่ต้องใช้ (Spare Parts)</label>
                                <button type="button" onclick="addSpareRow()" class="text-xs bg-sky-50 text-sky-600 px-2 py-1 rounded border border-sky-200 hover:bg-sky-100 flex items-center gap-1">
                                    <i data-lucide="plus" class="w-3 h-3"></i> เพิ่มแถวอะไหล่
                                </button>
                            </div>
                            <div id="hot-spare-parts" class="border border-slate-200 rounded-lg overflow-hidden"></div>
                        </div>

                        <div class="md:col-span-2 relative z-0 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">ภาพจุดตรวจสอบ (สูงสุด 5 รูป)</label>
                                <input type="file" id="checksheet-images" multiple accept="image/*" 
                                    onchange="previewImages(this)"
                                    class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100">
                                <div id="image-previews" class="flex flex-wrap gap-2 mt-3"></div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">คำแนะนำในการทำงาน (สูงสุด 5 ไฟล์, ไม่เกิน 5MB/ไฟล์)</label>
                                <input type="file" id="work-instructions" multiple 
                                    onchange="handleWorkInstructionsSelect(this)"
                                    class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                                <div id="instruction-previews" class="flex flex-wrap gap-2 mt-3"></div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4 mb-3 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-slate-600">จัดการแถวเช็คชีต:</span>
                            <input type="number" id="row-add-count" value="1" min="1" class="w-16 border border-slate-300 rounded-md p-1.5 text-sm text-center focus:ring-2 focus:ring-sky-500 outline-none">
                            <button onclick="addHotRows()" class="bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 px-3 py-1.5 rounded-md text-sm transition flex items-center gap-1 shadow-sm">
                                <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มแถวท้ายตาราง
                            </button>
                        </div>
                    </div>

                    <div class="border border-slate-200 rounded-lg overflow-hidden relative z-0">
                        <div id="hot-checksheet" class="w-full"></div>
                        <input type="file" id="hot-image-input" accept="image/*" style="display:none;">
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">รายการเช็คชีตที่บันทึกไว้ (Saved Templates)</h3>
                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <div id="checksheet-grid" class="ag-theme-alpine" style="height: 400px; width: 100%;"></div>
                    </div>
                </div>
            </div>

            <div id="tab-history" class="tab-content hidden space-y-4">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex justify-between items-center mb-4 border-b pb-4">
                        <h3 class="text-lg font-semibold text-slate-800">ประวัติการบำรุงรักษาล่าสุด</h3>
                        <div class="flex gap-2">
                            <input type="text" id="history-search" oninput="renderHistory()" placeholder="ค้นหาเครื่องจักร, ชื่อผู้ทำ..." class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                            <button class="bg-slate-100 p-2 px-3 rounded-lg hover:bg-slate-200 text-sm flex gap-2 items-center"><i data-lucide="filter" class="w-4 h-4"></i> คัดกรอง</button>
                        </div>
                    </div>

                    <div class="overflow-x-auto border border-slate-200 rounded-lg">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-600 bg-slate-50 uppercase border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3">วันที่ทำ PM</th>
                                    <th class="px-4 py-3">รหัสเครื่องจักร</th>
                                    <th class="px-4 py-3">รายละเอียดการทำงาน</th>
                                    <th class="px-4 py-3">ผู้ปฏิบัติงาน</th>
                                    <th class="px-4 py-3 text-center">ผลตรวจ</th>
                                    <th class="px-4 py-3 text-center">เอกสาร</th>
                                </tr>
                            </thead>
                            <tbody id="history-table-body" class="divide-y divide-slate-100">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="tab-holiday" class="tab-content hidden space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 col-span-1">
                        <h3 class="text-base font-semibold text-slate-800 mb-3 border-b pb-2">ตั้งค่าวันหยุดสุดสัปดาห์</h3>
                        <p class="text-[12px] text-slate-500 mb-4">ระบบจะคำนวณวันหยุดเพื่อเลื่อนรอบ PM ให้อัตโนมัติ</p>
                        
                        <div class="space-y-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="weekend-sat" class="w-4 h-4 text-sky-600 rounded border-slate-300 focus:ring-sky-500" checked onchange="saveWeekendSetting()">
                                <span class="text-sm text-slate-700">วันเสาร์ (Saturday)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" id="weekend-sun" class="w-4 h-4 text-sky-600 rounded border-slate-300 focus:ring-sky-500" checked onchange="saveWeekendSetting()">
                                <span class="text-sm text-slate-700">วันอาทิตย์ (Sunday)</span>
                            </label>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 col-span-1 md:col-span-2">
                        <h3 class="text-base font-semibold text-slate-800 mb-3 border-b pb-2">เพิ่มวันหยุดพิเศษ / นักขัตฤกษ์</h3>
                        <form id="holiday-form" onsubmit="handleHolidaySubmit(event)" class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1">
                                <label class="block text-[12px] font-medium text-slate-700 mb-1">วันที่หยุด</label>
                                <input type="date" id="holiday-date" required class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                            </div>
                            <div class="flex-[2]">
                                <label class="block text-[12px] font-medium text-slate-700 mb-1">ชื่อวันหยุด / รายละเอียด</label>
                                <input type="text" id="holiday-name" placeholder="เช่น วันขึ้นปีใหม่, วันสงกรานต์" required class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full sm:w-auto btn-gradient px-4 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2">
                                    <i data-lucide="plus" class="w-4 h-4"></i> เพิ่มวันหยุด
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <h3 class="text-base font-semibold text-slate-800 mb-3">รายการวันหยุดพิเศษ (Custom Holidays)</h3>
                    <div class="overflow-x-auto border border-slate-200 rounded-lg">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-600 bg-slate-50 uppercase border-b border-slate-200">
                                <tr>
                                    <th class="px-4 py-3 w-32">วันที่</th>
                                    <th class="px-4 py-3">ชื่อวันหยุด</th>
                                    <th class="px-4 py-3 text-center w-24">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody id="holiday-table-body" class="divide-y divide-slate-100">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <div id="drawer-overlay" class="fixed inset-0 bg-slate-900/40 z-[60] hidden transition-opacity duration-300 opacity-0 backdrop-blur-sm" onclick="closePlanDrawer()"></div>
    <div id="plan-drawer" class="fixed top-0 right-0 h-full w-full max-w-md bg-white shadow-2xl z-[70] transform translate-x-full transition-transform duration-300 flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h3 class="text-lg font-bold text-slate-800" id="drawer-title">สร้างแผน PM ใหม่</h3>
            <button onclick="closePlanDrawer()" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-200 rounded-full transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6">
            <form id="plan-form" onsubmit="handlePlanSubmit(event)" class="space-y-4">
                <input type="hidden" id="plan-id" value="">
                
                <div>
                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">เลือกอาคาร</label>
                    <select id="plan-building" required class="w-full border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-shadow">
                        <option value="">-- เลือกอาคาร --</option>
                        <option value="อาคาร A">อาคาร A (สำนักงาน)</option>
                        <option value="อาคาร B">อาคาร B (โรงงานผลิต)</option>
                        <option value="อาคาร C">อาคาร C (คลังสินค้า)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">เลือกเครื่องจักร/อุปกรณ์</label>
                    <select id="plan-machine" required class="w-full border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-shadow">
                        <option value="">-- เลือกเครื่องจักร --</option>
                        <option value="AHU-01 (แอร์รวม)">AHU-01 (แอร์รวม)</option>
                        <option value="Chiller-01 (ระบบทำความเย็น)">Chiller-01 (ระบบทำความเย็น)</option>
                        <option value="Air Compressor #1">Air Compressor #1</option>
                        <option value="Generator-01">Generator-01 (เครื่องปั่นไฟ)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">เช็คชีตที่ใช้ (Checksheet)</label>
                    <select id="plan-form-select" required class="w-full border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-shadow">
                        <option value="">-- เลือกรูปแบบฟอร์ม --</option>
                        </select>
                </div>
                
                <div class="pt-4 mt-2 border-t border-slate-100">
                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">ประเภทความถี่</label>
                    <select id="plan-type" class="w-full border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none mb-4 transition-shadow">
                        <option value="Time-based">ตามระยะเวลา (Time-based)</option>
                        <option value="Usage-based">ตามชั่วโมงการทำงาน (Usage-based)</option>
                    </select>

                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">กำหนดรอบ (ความถี่)</label>
                    <div class="flex gap-2 mb-4">
                        <input type="number" id="plan-cycle-num" value="1" min="1" required class="w-24 border border-slate-300 rounded-lg py-2.5 px-3 text-sm text-center focus:ring-2 focus:ring-sky-500 outline-none transition-shadow">
                        <select id="plan-cycle-unit" class="flex-1 border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-shadow">
                            <option value="เดือน">เดือน</option>
                            <option value="วัน">วัน</option>
                            <option value="ปี">ปี</option>
                        </select>
                    </div>

                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">วันที่เริ่มแผน (Start Date)</label>
                    <input type="date" id="plan-date" required class="w-full border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-shadow mb-4">

                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">ผู้รับผิดชอบ (Assignees)</label>
                    <div class="mb-4">
                        <select id="plan-assignees" multiple required class="w-full text-sm">
                            <option value="สมชาย ช่างยนต์">สมชาย ช่างยนต์</option>
                            <option value="วิชัย การไฟฟ้า">วิชัย การไฟฟ้า</option>
                            <option value="มานะ ซ่อมบำรุง">มานะ ซ่อมบำรุง</option>
                            <option value="ทีม Outsource">ทีม Outsource</option>
                        </select>
                    </div>

                    <label class="block text-[13px] font-medium text-slate-700 mb-1.5">การจัดการเมื่อตรงวันหยุด</label>
                    <select id="plan-holiday-action" class="w-full border border-slate-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-shadow">
                        <option value="เลื่อนไป 1 วัน">เลื่อนไป 1 วันทำการถัดไป (Forward)</option>
                        <option value="ทำก่อน 1 วัน">เลื่อนมาทำก่อน 1 วันทำการ (Backward)</option>
                        <option value="ข้าม (หยุดทำ)">ข้ามไปรอบถัดไป (Skip)</option>
                        <option value="ไม่หยุดทำ (ทำตามปกติ)">ไม่หยุดทำ (ทำตามปกติ)</option>
                    </select>
                </div>
            </form>
        </div>
        
        <div class="p-5 border-t border-slate-200 bg-white flex justify-end gap-3 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            <button type="button" onclick="closePlanDrawer()" class="px-5 py-2.5 bg-slate-100 text-slate-700 rounded-lg text-sm font-medium hover:bg-slate-200 transition-colors">ยกเลิก</button>
            <button type="submit" form="plan-form" class="btn-gradient px-6 py-2.5 rounded-lg text-sm font-medium flex items-center gap-2 shadow-sm">
                <i data-lucide="save" class="w-4 h-4"></i> <span id="text-save-plan">บันทึกแผน</span>
            </button>
        </div>
    </div>

    <div id="preview-modal" class="fixed inset-0 bg-slate-900/40 z-[80] hidden flex items-center justify-center backdrop-blur-sm transition-opacity opacity-0" onclick="closePreviewModal(event)">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-all duration-300" id="preview-content">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-sky-100 text-sky-600 rounded-lg"><i data-lucide="file-search" class="w-5 h-5"></i></div>
                    <h3 class="text-lg font-bold text-slate-800">รายละเอียดแผนงาน</h3>
                </div>
                <button onclick="closePreviewModal(null, true)" class="text-slate-400 hover:text-slate-600 hover:bg-slate-200 p-1.5 rounded-full transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-y-5 gap-x-6 text-[13px]">
                    <div class="col-span-2 sm:col-span-1">
                        <p class="text-slate-500 mb-1">รหัสเครื่องจักร</p>
                        <p class="font-semibold text-slate-800 text-base" id="prev-machine">-</p>
                    </div>
                    <div class="col-span-2 sm:col-span-1">
                        <p class="text-slate-500 mb-1">อาคาร/สถานที่</p>
                        <p class="font-medium text-slate-800" id="prev-building">-</p>
                    </div>
                    <div class="col-span-2 bg-slate-50 p-3 rounded-lg border border-slate-100">
                        <p class="text-slate-500 mb-2">ฟอร์มตรวจสอบ (Checksheet)</p>
                        <div class="flex items-center gap-2">
                            <i data-lucide="file-spreadsheet" class="w-5 h-5 text-sky-500"></i>
                            <span class="font-semibold text-sky-700 text-sm" id="prev-form">-</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-slate-500 mb-1">ความถี่ (Cycle)</p>
                        <p class="font-medium text-slate-800 flex items-center gap-1"><i data-lucide="repeat" class="w-3 h-3 text-slate-400"></i> <span id="prev-cycle">-</span></p>
                    </div>
                    <div>
                        <p class="text-slate-500 mb-1">การจัดการวันหยุด</p>
                        <p class="font-medium text-slate-800 flex items-center gap-1" id="prev-holiday">-</p>
                    </div>
                    <div>
                        <p class="text-slate-500 mb-1">วันที่เริ่มแผน</p>
                        <p class="font-medium text-slate-800" id="prev-start">-</p>
                    </div>
                    <div>
                        <p class="text-slate-500 mb-1">กำหนดการรอบถัดไป</p>
                        <p class="font-semibold text-sky-600" id="prev-next">-</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-slate-500 mb-1">ผู้รับผิดชอบ</p>
                        <div class="flex flex-wrap gap-1" id="prev-assignees"></div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 flex justify-end bg-slate-50">
                <button onclick="closePreviewModal(null, true)" class="px-5 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg text-sm hover:bg-slate-100 transition-colors font-medium">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>

    <div id="cs-preview-modal" class="fixed inset-0 bg-slate-900/40 z-[80] hidden flex items-center justify-center backdrop-blur-sm transition-opacity opacity-0" onclick="closeCsPreviewModal(event)">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden transform scale-95 transition-all duration-300" id="cs-preview-content">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-emerald-100 text-emerald-600 rounded-lg"><i data-lucide="file-spreadsheet" class="w-5 h-5"></i></div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800" id="prev-cs-name">รายละเอียดเช็คชีต</h3>
                        <p class="text-xs text-slate-500" id="prev-cs-cat">-</p>
                    </div>
                </div>
                <button onclick="closeCsPreviewModal(null, true)" class="text-slate-400 hover:text-slate-600 hover:bg-slate-200 p-1.5 rounded-full transition-colors"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="p-6 overflow-y-auto flex-1 bg-white">
                <div class="overflow-x-auto border border-slate-200 rounded-lg">
                    <table class="w-full text-sm text-left whitespace-nowrap">
                        <thead class="text-xs text-slate-600 bg-slate-50 uppercase border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3">จุดที่ตรวจสอบ</th>
                                <th class="px-4 py-3">มาตรฐานการตรวจสอบ</th>
                                <th class="px-4 py-3">วิธีตรวจสอบ/เครื่องมือ</th>
                                <th class="px-4 py-3 text-center">ประเภท</th>
                                <th class="px-4 py-3 text-center">รูปภาพ</th>
                            </tr>
                        </thead>
                        <tbody id="cs-preview-table-body" class="divide-y divide-slate-100">
                            </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // 1. STATE & UTILITY FUNCTIONS
        // ============================================
        const getToday = () => new Date().toISOString().split('T')[0];
        
        const addDays = (dateStr, days) => {
            let d = new Date(dateStr);
            d.setDate(d.getDate() + days);
            return d.toISOString().split('T')[0];
        };
        
        const getLocalYMD = (dateObj) => {
            return dateObj.getFullYear() + '-' + String(dateObj.getMonth() + 1).padStart(2, '0') + '-' + String(dateObj.getDate()).padStart(2, '0');
        };

        // Mock Application State
        let appState = {
            plans: [
                { id: 1, building: 'อาคาร A', machine: 'AHU-01 (แอร์รวม)', formSelect: 'CS-AHU-Monthly', cycleNum: 1, cycleUnit: 'เดือน', startDate: getToday(), nextDate: addDays(getToday(), 5), status: 'Active', assignees: ['สมชาย ช่างยนต์', 'วิชัย การไฟฟ้า'], holidayAction: 'เลื่อนไป 1 วัน' },
                { id: 2, building: 'อาคาร B', machine: 'Chiller-01 (ระบบทำความเย็น)', formSelect: 'CS-Chiller-Yearly', cycleNum: 1, cycleUnit: 'ปี', startDate: getToday(), nextDate: addDays(getToday(), 14), status: 'Active', assignees: ['มานะ ซ่อมบำรุง'], holidayAction: 'ไม่หยุดทำ (ทำตามปกติ)' }
            ],
            history: [
                { id: 101, date: addDays(getToday(), -2), machine: 'Air Compressor #1', detail: 'ทำความสะอาดฟิลเตอร์, อัดจารบีลูกปืน', user: 'สมชาย ช่างยนต์', status: 'normal' },
                { id: 102, date: addDays(getToday(), -5), machine: 'AHU-02 (แอร์รวม)', detail: 'เช็คกระแสไฟ, ตรวจสอบสายพาน (พบรอยร้าว)', user: 'วิชัย การไฟฟ้า', status: 'warning' }
            ],
            checksheets: [
                { 
                    id: 1, 
                    name: 'CS-AHU-Monthly', 
                    docNo: 'FM-MN-001',           // เพิ่ม: เลขที่เอกสาร
                    revNo: '00',                  // เพิ่ม: Revision No.
                    effectiveDate: '2024-01-15',  // เพิ่ม: วันที่มีผล (รูปแบบ YYYY-MM-DD)
                    category: 'ระบบปรับอากาศ (HVAC)', 
                    itemCount: 12,
                    estimatedTime: 45,            // เพิ่ม: เวลาที่คาดว่าจะเสร็จ (นาที)
                    sparePartsCount: 2            // เพิ่ม: จำนวนรายการอะไหล่ที่ต้องใช้
                },
                { 
                    id: 2, 
                    name: 'CS-Chiller-Yearly', 
                    docNo: 'FM-MN-015',           // เพิ่ม: เลขที่เอกสาร
                    revNo: '02',                  // เพิ่ม: Revision No.
                    effectiveDate: '2023-11-01',  // เพิ่ม: วันที่มีผล
                    category: 'ระบบทำความเย็น', 
                    itemCount: 45,
                    estimatedTime: 180,           // เพิ่ม: เวลาที่คาดว่าจะเสร็จ (นาที)
                    sparePartsCount: 8            // เพิ่ม: จำนวนรายการอะไหล่ที่ต้องใช้
                },
                { 
                    id: 3, 
                    name: 'CS-Pump-Weekly', 
                    docNo: 'FM-MN-022',           
                    revNo: '01',                  
                    effectiveDate: '2024-02-10',  
                    category: 'ระบบปั๊มน้ำ', 
                    itemCount: 8,
                    estimatedTime: 15,           
                    sparePartsCount: 0            
                }
            ],
            holidays: [
                { id: 1, date: addDays(getToday(), 3), name: 'วันหยุดพิเศษจำลอง' }
            ],
            weekendSetting: [0, 6] // 0 = Sunday, 6 = Saturday
        };

        // Initialize Instances
        lucide.createIcons();
        let assigneesChoiceInstance = null;
        let calendar = null;
        let hot = null;
        let hotSpareParts = null;
        let checksheetImageFiles = [];
        let workInstructionFiles = [];
        let checksheetGridApi = null;

        // ============================================
        // 2. TAB NAVIGATION LOGIC
        // ============================================
        const tabTitles = {
            'dashboard': { title: 'ปฏิทิน', sub: 'ติดตามและตรวจสอบแผนการบำรุงรักษา' },
            'plan': { title: 'จัดการแผน PM', sub: 'ตั้งค่าความถี่และผูกเครื่องจักรกับเช็คชีต' },
            'checksheet': { title: 'สร้างแบบฟอร์ม', sub: 'ออกแบบหัวข้อการตรวจเช็คเชิงป้องกัน' },
            'history': { title: 'ประวัติการบำรุงรักษา', sub: 'ดูประวัติการทำงานและผลการตรวจเช็คย้อนหลัง' },
            'holiday': { title: 'จัดการวันหยุด', sub: 'ตั้งค่าวันหยุดสุดสัปดาห์และวันหยุดนักขัตฤกษ์' }
        };

        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            
            document.getElementById(`tab-${tabId}`).classList.remove('hidden');
            event.currentTarget.classList.add('active');

            // Trigger re-renders for libraries to fix layout sizing issues when hidden
            if (tabId === 'checksheet' && hot) {
                setTimeout(() => { hot.render(); hotSpareParts.render(); }, 100);
            }
            if (tabId === 'dashboard' && calendar) {
                setTimeout(() => calendar.render(), 100);
            }
        }

        // ============================================
        // 3. RENDER & UI UPDATE FUNCTIONS
        // ============================================
        function updatePlanFormSelectOptions() {
            const select = document.getElementById('plan-form-select');
            const currentValue = select.value;
            select.innerHTML = '<option value="">-- เลือกรูปแบบฟอร์ม --</option>';
            appState.checksheets.forEach(cs => {
                select.add(new Option(`${cs.name} (${cs.category})`, cs.name));
            });
            if(currentValue) select.value = currentValue;
        }

        function renderDashboard() {
            document.getElementById('stat-pm').innerText = appState.plans.length;
            
            let doneCount = appState.history.filter(h => h.status === 'normal').length;
            let overdueCount = appState.history.filter(h => h.status === 'warning').length;
            
            document.getElementById('stat-done').innerText = doneCount;
            document.getElementById('stat-overdue').innerText = overdueCount;

            renderCalendarEvents();
        }

        function renderPlans() {
            const tbody = document.getElementById('plan-table-body');
            tbody.innerHTML = '';
            
            if(appState.plans.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center py-6 text-slate-400">ไม่มีข้อมูลแผน PM ในระบบ</td></tr>`;
            } else {
                appState.plans.forEach(plan => {
                    tbody.innerHTML += `
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3">${plan.building}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">${plan.machine}</td>
                            <td class="px-4 py-3 text-slate-500">${plan.formSelect}</td>
                            <td class="px-4 py-3">ทุก ${plan.cycleNum} ${plan.cycleUnit}</td>
                            <td class="px-4 py-3 text-sky-600 font-medium">${plan.nextDate || 'ข้ามรอบ'}</td>
                            <td class="px-4 py-3 text-center"><span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs">Active</span></td>
                            <td class="px-4 py-3 text-center flex justify-center gap-1.5">
                                <button onclick="previewPlan(${plan.id})" class="text-emerald-500 hover:text-emerald-700 bg-emerald-50 p-1.5 rounded-md transition" title="ดูรายละเอียด"><i data-lucide="eye" class="w-4 h-4"></i></button>
                                <button onclick="openPlanDrawer(${plan.id})" class="text-blue-500 hover:text-blue-700 bg-blue-50 p-1.5 rounded-md transition" title="แก้ไข"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                <button onclick="deletePlan(${plan.id})" class="text-red-500 hover:text-red-700 bg-red-50 p-1.5 rounded-md transition" title="ลบ"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    `;
                });
            }
            lucide.createIcons();
            renderDashboard(); 
        }

        function renderChecksheets() {
            const gridDiv = document.getElementById('checksheet-grid');
            
            // ถ้ายังไม่เคยสร้าง Grid ให้ทำการ Setup สร้างใหม่
            if (!checksheetGridApi) {
                const gridOptions = {
                    rowData: appState.checksheets,
                    columnDefs: [
                        { 
                            headerName: "ชื่อเช็คชีต", 
                            field: "name", 
                            flex: 1,
                            minWidth: 180,
                            filter: 'agTextColumnFilter',
                            cellRenderer: function(params) {
                                return `<div class="flex items-center gap-2 pt-1">
                                            <svg class="w-4 h-4 text-sky-600 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                                                <polyline points="14 2 14 8 20 8"/>
                                                <line x1="16" y1="13" x2="8" y2="13"/>
                                                <line x1="16" y1="17" x2="8" y2="17"/>
                                                <line x1="10" y1="9" x2="8" y2="9"/>
                                            </svg> 
                                            <span class="truncate">${params.value || '-'}</span>
                                        </div>`;
                            }
                        },
                        { 
                            headerName: "เลขที่เอกสาร", 
                            field: "docNo",
                            filter: 'agTextColumnFilter'
                        },
                        { 
                            headerName: "Rev No.", 
                            field: "revNo",
                            filter: 'agTextColumnFilter',
                            cellStyle: { textAlign: 'center' }
                        },
                        { 
                            headerName: "วันที่มีผล", 
                            field: "effectiveDate", 
                            filter: 'agDateColumnFilter',
                            cellStyle: { textAlign: 'center' }
                        },
                        { 
                            headerName: "หมวดหมู่/แผนก", 
                            field: "category", 
                            filter: 'agSetColumnFilter'
                        },
                        { 
                            headerName: "หัวข้อตรวจ", 
                            field: "itemCount",
                            filter: 'agNumberColumnFilter',
                            cellRenderer: function(params) {
                                const val = params.value || 0;
                                return `<div class="flex items-center h-full justify-center">
                                            <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded-full text-xs">${val}</span>
                                        </div>`;
                            },
                            cellStyle: { textAlign: 'center' }
                        },
                        { 
                            headerName: "เวลา (นาที)", 
                            field: "estimatedTime",
                            filter: 'agNumberColumnFilter',
                            cellRenderer: function(params) {
                                const val = params.value || 0;
                                return `<div class="flex items-center h-full justify-center text-slate-700">
                                            ${val}
                                        </div>`;
                            },
                            cellStyle: { textAlign: 'center' }
                        },
                        { 
                            headerName: "รายการอะไหล่", 
                            field: "sparePartsCount",
                            filter: 'agNumberColumnFilter',
                            cellRenderer: function(params) {
                                const val = params.value || 0;
                                return `<div class="flex items-center h-full justify-center">
                                            <span class="bg-amber-50 text-amber-600 px-2 py-1 rounded-full text-xs">${val}</span>
                                        </div>`;
                            },
                            cellStyle: { textAlign: 'center' }
                        },
                        { 
                            headerName: "จัดการ", 
                            field: "id", 
                            width: 180,
                            filter: false,
                            sortable: false,
                            pinned: 'right',
                            cellRenderer: function(params) {
                                return `
                                    <div class="flex justify-center gap-1.5 pt-1.5">
                                        <button onclick="previewChecksheet(${params.value})" class="text-emerald-500 hover:text-emerald-700 bg-emerald-50 p-1.5 rounded-md transition" title="ดูรายละเอียด">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                                <circle cx="12" cy="12" r="3"/>
                                            </svg>
                                        </button>
                                        
                                        <button onclick="copyChecksheet(${params.value})" class="text-indigo-500 hover:text-indigo-700 bg-indigo-50 p-1.5 rounded-md transition" title="คัดลอก (สร้างใหม่จากข้อมูลนี้)">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
                                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                                            </svg>
                                        </button>
                                        
                                        <button onclick="editChecksheet(${params.value})" class="text-blue-500 hover:text-blue-700 bg-blue-50 p-1.5 rounded-md transition" title="แก้ไข">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                        </button>
                                        
                                        <button onclick="deleteChecksheet(${params.value})" class="text-red-500 hover:text-red-700 bg-red-50 p-1.5 rounded-md transition" title="ลบ">
                                            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"/>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                                <line x1="10" y1="11" x2="10" y2="17"/>
                                                <line x1="14" y1="11" x2="14" y2="17"/>
                                            </svg>
                                        </button>
                                    </div>
                                `;
                            },
                            cellStyle: { textAlign: 'center' }
                        }
                    ],
                    defaultColDef: {
                        sortable: true,      
                        resizable: true
                    },
                    onGridReady: function(params) {
                        setTimeout(() => lucide.createIcons(), 100);
                    },
                    onRowDataUpdated: function(params) {
                        setTimeout(() => lucide.createIcons(), 100);
                    }
                };
                
                // V31+ ใช้ agGrid.createGrid แทนการใช้ new agGrid.Grid
                // ฟังก์ชันนี้จะ return ตัว API กลับมาให้เราใช้ควบคุมตาราง
                checksheetGridApi = agGrid.createGrid(gridDiv, gridOptions);
                
            } else {
                // หากมี Grid อยู่แล้ว ให้อัปเดตข้อมูลด้วย API ตัวใหม่ (V31+)
                checksheetGridApi.setGridOption('rowData', appState.checksheets);
                setTimeout(() => lucide.createIcons(), 100);
            }
        }

        function editChecksheet(id) {
            // 1. ค้นหาข้อมูลเช็คชีตจาก State ของคุณ
            const checksheet = appState.checksheets.find(c => c.id === id);
            if (!checksheet) return;

            // 2. นำข้อมูลมาแสดงบนช่อง Input ต่างๆ ตาม ID 
            document.getElementById('checksheet-name').value = checksheet.name || '';
            document.getElementById('checksheet-dept').value = checksheet.category || '';
            
            // หากมีฟิลด์อื่นๆ ที่คุณเซฟไว้ใน appState.checksheets ก็สามารถนำมาแสดงต่อได้ เช่น:
            document.getElementById('checksheet-doc-no').value = checksheet.docNo || '';
            document.getElementById('checksheet-rev-no').value = checksheet.revNo || '00';

            // (เพิ่มเติม) หากมีการใช้ Handsontable เก็บข้อมูล row ของเช็คชีตไว้ ก็ให้โหลดลงตารางด้วย
            if (checksheet.items && hot) {
                hot.loadData(JSON.parse(JSON.stringify(checksheet.items)));
            }

            // 3. เลื่อนหน้าจอไปที่ตำแหน่งฟอร์มด้านบนสุด
            const nameInput = document.getElementById('checksheet-name');
            nameInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // 4. ให้เคอร์เซอร์กระพริบ Focus ที่ช่อง "ชื่อเช็คชีต" 
            setTimeout(() => {
                nameInput.focus();
                
                // (Optional) เพิ่มเอฟเฟกต์สีสว่างชั่วคราวให้ผู้ใช้รู้ว่า Focus ตรงนี้แล้ว
                nameInput.classList.add('ring-4', 'ring-sky-200');
                setTimeout(() => nameInput.classList.remove('ring-4', 'ring-sky-200'), 1000);
            }, 300); // ดีเลย์เล็กน้อยเพื่อให้ scroll เสร็จก่อนแล้วค่อย focus
        }

        function copyChecksheet(id) {
            // 1. ค้นหาข้อมูลต้นฉบับ
            const original = appState.checksheets.find(c => c.id === id);
            if (!original) return;

            // 2. นำข้อมูลมาเติมลงในฟอร์ม 
            // แนะนำให้เติมคำว่า " (คัดลอก)" ท้ายชื่อ เพื่อให้ผู้ใช้รู้ว่าเป็นตัวก๊อปปี้
            document.getElementById('checksheet-name').value = (original.name || '') + ' (คัดลอก)';
            document.getElementById('checksheet-dept').value = original.category || '';
            
            // (หากมีฟิลด์อื่น หรือข้อมูลใน Handsontable ก็ดึงมาแสดงด้วยเช่นกัน)

            // *** ข้อสำคัญ: หากคุณมีตัวแปรซ่อน (Hidden input) หรือตัวแปร Global ที่เก็บ ID สำหรับโหมดแก้ไข
            // ให้เคลียร์ค่า ID นั้นทิ้ง เพื่อที่พอกดปุ่ม "บันทึก" ระบบจะทำการสร้างข้อมูลบรรทัดใหม่ ไม่ใช่ไปแก้ไขบรรทัดเดิม ***
            // currentEditId = null; 

            // 3. เลื่อนหน้าจอไปที่ฟอร์มด้านบน
            const nameInput = document.getElementById('checksheet-name');
            nameInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // 4. Focus ที่ช่องชื่อ พร้อมเลือกคลุมดำข้อความไว้ให้พิมพ์เปลี่ยนชื่อได้ทันที
            setTimeout(() => {
                nameInput.focus();
                nameInput.select(); 
            }, 300);
        }

        function previewChecksheet(id) {
            const cs = appState.checksheets.find(c => c.id == id);
            if(!cs) return;

            // ใส่ชื่อและหมวดหมู่บนหัว Modal
            document.getElementById('prev-cs-name').innerText = cs.name;
            document.getElementById('prev-cs-cat').innerText = `หมวดหมู่: ${cs.category}`;

            const tbody = document.getElementById('cs-preview-table-body');
            tbody.innerHTML = '';

            // ตรวจสอบว่ามีข้อมูล data ที่บันทึกไว้หรือไม่
            if(cs.data && cs.data.length > 0) {
                cs.data.forEach(row => {
                    // ลำดับ index อ้างอิงจากคอลัมน์ใน Handsontable
                    const point = row[0] || '-';
                    const std = row[1] || '-';
                    const method = row[2] || '-';
                    const type = row[4] || '-';
                    const photo = row[6] || '-';

                    tbody.innerHTML += `
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-800">${point}</td>
                            <td class="px-4 py-3 text-slate-600">${std}</td>
                            <td class="px-4 py-3 text-slate-600">${method}</td>
                            <td class="px-4 py-3 text-center text-slate-600"><span class="bg-sky-50 text-sky-700 px-2 py-0.5 rounded text-xs border border-sky-100">${type}</span></td>
                            <td class="px-4 py-3 text-center text-slate-600">${photo}</td>
                        </tr>
                    `;
                });
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-8 text-slate-400">ไม่พบรายละเอียดหัวข้อในเช็คชีตนี้ (อาจเป็นข้อมูลตัวอย่างก่อนมีการอัปเดตระบบ)</td></tr>`;
            }

            // เปิด Modal แบบมี Animation
            const modal = document.getElementById('cs-preview-modal');
            const content = document.getElementById('cs-preview-content');
            modal.classList.remove('hidden');
            setTimeout(() => { modal.classList.remove('opacity-0'); content.classList.remove('scale-95'); }, 10);
            lucide.createIcons();
        }

        function closeCsPreviewModal(e, force = false) {
            // ปิดเมื่อกดกากบาท หรือคลิกพื้นที่ว่างข้างนอก
            if (!force && e && e.target !== e.currentTarget) return; 
            const modal = document.getElementById('cs-preview-modal');
            const content = document.getElementById('cs-preview-content');
            content.classList.add('scale-95');
            modal.classList.add('opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        function renderHistory() {
            const tbody = document.getElementById('history-table-body');
            const searchTxt = document.getElementById('history-search').value.toLowerCase();
            tbody.innerHTML = '';
            
            let filtered = appState.history;
            if (searchTxt) {
                filtered = filtered.filter(h => 
                    h.machine.toLowerCase().includes(searchTxt) || 
                    h.user.toLowerCase().includes(searchTxt) ||
                    h.detail.toLowerCase().includes(searchTxt)
                );
            }

            if(filtered.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-6 text-slate-400">ไม่พบข้อมูลประวัติการทำ PM</td></tr>`;
            } else {
                filtered.forEach(h => {
                    let badge = h.status === 'normal' 
                        ? `<span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs flex items-center justify-center gap-1 w-fit mx-auto"><i data-lucide="check" class="w-3 h-3"></i> ปกติ</span>`
                        : `<span class="bg-orange-100 text-orange-700 px-2 py-1 rounded text-xs flex items-center justify-center gap-1 w-fit mx-auto"><i data-lucide="alert-circle" class="w-3 h-3"></i> รอปรับปรุง</span>`;

                    tbody.innerHTML += `
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3">${h.date}</td>
                            <td class="px-4 py-3 font-medium text-slate-800">${h.machine}</td>
                            <td class="px-4 py-3 text-slate-500">${h.detail}</td>
                            <td class="px-4 py-3">${h.user}</td>
                            <td class="px-4 py-3 text-center">${badge}</td>
                            <td class="px-4 py-3 text-center"><button class="text-sky-600 hover:text-sky-800 bg-sky-50 p-1.5 rounded-md mx-auto flex"><i data-lucide="file-text" class="w-4 h-4"></i></button></td>
                        </tr>
                    `;
                });
            }
            lucide.createIcons();
        }

        function renderHolidays() {
            const tbody = document.getElementById('holiday-table-body');
            tbody.innerHTML = '';
            
            const sortedHolidays = [...appState.holidays].sort((a, b) => new Date(a.date) - new Date(b.date));

            if(sortedHolidays.length === 0) {
                tbody.innerHTML = `<tr><td colspan="3" class="text-center py-6 text-slate-400">ไม่มีข้อมูลวันหยุดพิเศษ</td></tr>`;
            } else {
                sortedHolidays.forEach(h => {
                    const d = new Date(h.date);
                    const formattedDate = d.toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' });
                    
                    tbody.innerHTML += `
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 font-medium text-sky-600">${formattedDate}</td>
                            <td class="px-4 py-3 text-slate-700">${h.name}</td>
                            <td class="px-4 py-3 text-center">
                                <button onclick="deleteHoliday(${h.id})" class="text-red-500 hover:text-red-700 bg-red-50 p-1.5 rounded-md transition mx-auto flex" title="ลบ"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </td>
                        </tr>
                    `;
                });
            }
            lucide.createIcons();
        }

        // ============================================
        // 4. PLAN & CALENDAR LOGIC
        // ============================================
        function calculateNextDate(startDate, num, unit, holidayAction) {
            let d = new Date(startDate);
            if(unit === 'วัน') d.setDate(d.getDate() + parseInt(num));
            else if(unit === 'เดือน') d.setMonth(d.getMonth() + parseInt(num));
            else if(unit === 'ปี') d.setFullYear(d.getFullYear() + parseInt(num));

            const isHoliday = (dateObj) => {
                const dayOfWeek = dateObj.getDay();
                if (appState.weekendSetting.includes(dayOfWeek)) return true;
                const dateStr = getLocalYMD(dateObj);
                return appState.holidays.some(h => h.date === dateStr);
            };
            
            if (isHoliday(d) && holidayAction !== 'ไม่หยุดทำ (ทำตามปกติ)') {
                if (holidayAction === 'เลื่อนไป 1 วัน') {
                    while (isHoliday(d)) d.setDate(d.getDate() + 1); 
                } else if (holidayAction === 'ทำก่อน 1 วัน') {
                    while (isHoliday(d)) d.setDate(d.getDate() - 1); 
                } else if (holidayAction === 'ข้าม (หยุดทำ)') {
                    return null; // Skip this cycle
                }
            }
            return getLocalYMD(d); 
        }

        function openPlanDrawer(id = null) {
            const drawer = document.getElementById('plan-drawer');
            const overlay = document.getElementById('drawer-overlay');
            
            if (id) {
                const plan = appState.plans.find(p => p.id == id);
                if(plan) {
                    document.getElementById('plan-id').value = plan.id;
                    document.getElementById('plan-building').value = plan.building;
                    document.getElementById('plan-machine').value = plan.machine;
                    document.getElementById('plan-form-select').value = plan.formSelect;
                    document.getElementById('plan-cycle-num').value = plan.cycleNum;
                    document.getElementById('plan-cycle-unit').value = plan.cycleUnit;
                    document.getElementById('plan-date').value = plan.startDate;
                    document.getElementById('plan-holiday-action').value = plan.holidayAction || 'เลื่อนไป 1 วัน';

                    if(assigneesChoiceInstance) {
                        assigneesChoiceInstance.removeActiveItems();
                        assigneesChoiceInstance.setChoiceByValue(plan.assignees || []);
                    }

                    document.getElementById('drawer-title').innerText = 'แก้ไขแผน PM';
                    document.getElementById('text-save-plan').innerText = 'อัปเดตข้อมูล';
                }
            } else {
                document.getElementById('plan-form').reset();
                document.getElementById('plan-id').value = '';
                document.getElementById('plan-date').value = getToday();
                if(assigneesChoiceInstance) assigneesChoiceInstance.removeActiveItems();

                document.getElementById('drawer-title').innerText = 'สร้างแผน PM ใหม่';
                document.getElementById('text-save-plan').innerText = 'บันทึกแผน';
            }

            overlay.classList.remove('hidden');
            setTimeout(() => overlay.classList.remove('opacity-0'), 10);
            drawer.classList.remove('translate-x-full');
        }

        function closePlanDrawer() {
            const drawer = document.getElementById('plan-drawer');
            const overlay = document.getElementById('drawer-overlay');
            drawer.classList.add('translate-x-full');
            overlay.classList.add('opacity-0');
            setTimeout(() => overlay.classList.add('hidden'), 300);
        }

        function handlePlanSubmit(e) {
            e.preventDefault();
            const id = document.getElementById('plan-id').value;
            const building = document.getElementById('plan-building').value;
            const machine = document.getElementById('plan-machine').value;
            const formSelect = document.getElementById('plan-form-select').value;
            const cycleNum = document.getElementById('plan-cycle-num').value;
            const cycleUnit = document.getElementById('plan-cycle-unit').value;
            const startDate = document.getElementById('plan-date').value;
            const holidayAction = document.getElementById('plan-holiday-action').value;
            
            const assignees = assigneesChoiceInstance ? assigneesChoiceInstance.getValue(true) : [];
            if(assignees.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาระบุผู้รับผิดชอบอย่างน้อย 1 คน', 'warning');
                return;
            }

            const nextDate = calculateNextDate(startDate, cycleNum, cycleUnit, holidayAction);

            if (id) {
                const index = appState.plans.findIndex(p => p.id == id);
                if (index !== -1) {
                    appState.plans[index] = { ...appState.plans[index], building, machine, formSelect, cycleNum, cycleUnit, startDate, nextDate, assignees, holidayAction };
                }
                Swal.fire({ title: 'อัปเดตสำเร็จ!', icon: 'success', confirmButtonColor: '#006B9F', timer: 1500 });
            } else {
                const newPlan = { id: Date.now(), building, machine, formSelect, cycleNum, cycleUnit, startDate, nextDate, status: 'Active', assignees, holidayAction };
                appState.plans.push(newPlan);
                Swal.fire({ title: 'บันทึกสำเร็จ!', icon: 'success', confirmButtonColor: '#006B9F', timer: 1500 });
            }
            closePlanDrawer();
            renderPlans(); 
        }

        function deletePlan(id) {
            Swal.fire({
                title: 'ยืนยันการลบแผน PM?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: 'ลบเลย',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    appState.plans = appState.plans.filter(p => p.id != id);
                    renderPlans();
                    Swal.fire({ title: 'ลบแล้ว!', icon: 'success', showConfirmButton: false, timer: 1500 });
                }
            });
        }

        function previewPlan(id) {
            const plan = appState.plans.find(p => p.id == id);
            if(!plan) return;
            
            document.getElementById('prev-machine').innerText = plan.machine;
            document.getElementById('prev-building').innerText = plan.building;
            document.getElementById('prev-form').innerText = plan.formSelect;
            document.getElementById('prev-cycle').innerText = `ทุก ${plan.cycleNum} ${plan.cycleUnit}`;
            document.getElementById('prev-start').innerText = new Date(plan.startDate).toLocaleDateString('th-TH');
            
            if(plan.nextDate) {
                document.getElementById('prev-next').innerText = new Date(plan.nextDate).toLocaleDateString('th-TH');
            } else {
                document.getElementById('prev-next').innerHTML = `<span class="text-orange-500">ข้าม (ตรงวันหยุด)</span>`;
            }

            const assigneesContainer = document.getElementById('prev-assignees');
            assigneesContainer.innerHTML = '';
            (plan.assignees || []).forEach(name => {
                assigneesContainer.innerHTML += `<span class="bg-sky-100 text-sky-700 px-2 py-0.5 rounded text-xs border border-sky-200">${name}</span>`;
            });

            document.getElementById('prev-holiday').innerHTML = `<i data-lucide="calendar-off" class="w-4 h-4 text-slate-400"></i> ${plan.holidayAction || 'ไม่ได้ตั้งค่า'}`;

            const modal = document.getElementById('preview-modal');
            const content = document.getElementById('preview-content');
            modal.classList.remove('hidden');
            setTimeout(() => { modal.classList.remove('opacity-0'); content.classList.remove('scale-95'); }, 10);
            lucide.createIcons();
        }

        function closePreviewModal(e, force = false) {
            if (!force && e && e.target !== e.currentTarget) return; 
            const modal = document.getElementById('preview-modal');
            const content = document.getElementById('preview-content');
            content.classList.add('scale-95');
            modal.classList.add('opacity-0');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        function renderCalendarEvents() {
            if(!calendar) return;
            calendar.removeAllEvents();
            appState.plans.forEach(plan => {
                if(plan.nextDate) { 
                    calendar.addEvent({
                        id: plan.id,
                        title: `PM ${plan.machine}`,
                        start: plan.nextDate, 
                        backgroundColor: '#006B9F',
                        borderColor: '#005b87'
                    });
                }
            });
        }

        // ============================================
        // 5. CHECKSHEET BUILDER LOGIC
        // ============================================
        async function previewImages(input) {
            const newFiles = Array.from(input.files);
            input.value = ''; // เคลียร์ input เพื่อให้เลือกไฟล์เดิมซ้ำได้

            const currentCount = checksheetImageFiles.length;
            const availableSlots = 5 - currentCount;

            if (availableSlots <= 0) {
                Swal.fire({ icon: 'warning', title: 'โควต้าเต็ม', text: 'คุณเพิ่มรูปภาพครบ 5 รูปแล้ว' });
                return;
            }

            let filesToAdd = newFiles;
            if (newFiles.length > availableSlots) {
                filesToAdd = newFiles.slice(0, availableSlots);
                Swal.fire({ icon: 'info', title: 'จำกัดจำนวนรูป', text: `เพิ่มได้อีก ${availableSlots} รูปเท่านั้น` });
            }

            checksheetImageFiles = checksheetImageFiles.concat(filesToAdd);
            renderImagePreviews();
        }

        async function renderImagePreviews() {
            const previewContainer = document.getElementById('image-previews');
            previewContainer.innerHTML = '';

            // เตรียม Array ของ Base64 ไว้สำหรับ Carousel
            const base64Promises = checksheetImageFiles.map(file => {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onload = (e) => resolve(e.target.result);
                    reader.readAsDataURL(file);
                });
            });

            const allBase64 = await Promise.all(base64Promises);

            checksheetImageFiles.forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'relative w-20 h-20 group rounded-lg overflow-hidden border border-slate-200 shadow-sm flex-shrink-0';
                
                // แปลง JSON string สำหรับส่งเข้าฟังก์ชัน open เหมือนใน machine_info.php
                const jsonImages = JSON.stringify(allBase64).replace(/"/g, '&quot;');

                div.innerHTML = `
                    <img src="${allBase64[index]}" 
                        class="w-full h-full object-cover cursor-pointer transition-transform group-hover:scale-105" 
                        onclick="ImageCarousel.open(${jsonImages}, ${index})">
                    
                    <button type="button" onclick="removeChecksheetImage(${index})" 
                            class="absolute top-1 right-1 bg-white/80 text-red-500 rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity shadow hover:bg-white">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                `;
                previewContainer.appendChild(div);
            });
            
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function removeChecksheetImage(index) {
            checksheetImageFiles.splice(index, 1);
            renderImagePreviews();
        }

        async function openImageCarousel(startIndex) {
            if (checksheetImageFiles.length === 0) return;
            const promises = checksheetImageFiles.map(file => {
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.onload = (e) => resolve(e.target.result);
                    reader.readAsDataURL(file);
                });
            });

            const base64Images = await Promise.all(promises);
            if (typeof ImageCarousel !== 'undefined') {
                ImageCarousel.open(base64Images, 'ตัวอย่างรูปภาพจุดตรวจสอบ'); 
            } else {
                console.error("ImageCarousel library is not loaded.");
            }
        }

        function handleWorkInstructionsSelect(input) {
            const maxFiles = 5;
            const maxSizeInBytes = 5 * 1024 * 1024; // 5MB
            
            // เอาไฟล์ที่เลือกใหม่เข้ามาตรวจสอบ
            const newFiles = Array.from(input.files);
            let limitExceededWarning = false;

            newFiles.forEach(file => {
                // 1. เช็คว่ารวมกับของเดิมแล้วเกิน 5 ไฟล์หรือไม่
                if (workInstructionFiles.length >= maxFiles) {
                    if (!limitExceededWarning) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'แจ้งเตือน',
                            text: `อัปโหลดได้สูงสุด ${maxFiles} ไฟล์เท่านั้น ระบบจะละเว้นไฟล์ที่เกิน`
                        });
                        limitExceededWarning = true;
                    }
                    return; // ข้ามการเพิ่มไฟล์นี้
                }

                // 2. เช็คขนาดไฟล์
                if (file.size > maxSizeInBytes) {
                    Swal.fire({
                        icon: 'error',
                        title: 'ขนาดไฟล์เกิน',
                        text: `ไฟล์ "${file.name}" มีขนาดใหญ่กว่า 5MB`
                    });
                    return; // ข้ามการเพิ่มไฟล์นี้
                }

                // 3. ป้องกันการเลือกไฟล์เดิมซ้ำ (ดูจากชื่อและขนาด)
                const isDuplicate = workInstructionFiles.some(f => f.name === file.name && f.size === file.size);
                if (!isDuplicate) {
                    workInstructionFiles.push(file); // เพิ่มไฟล์เข้าคลังสะสม
                }
            });

            // อัปเดตข้อมูลกลับเข้าไปที่ input file เพื่อให้เวลา Submit Form ข้อมูลไฟล์จะถูกส่งไปด้วย
            syncFileInput(input);
            
            // เรนเดอร์หน้าจอแสดงผล
            renderWorkInstructionPreviews();
        }

        // ฟังก์ชันซิงค์ไฟล์จาก Array เข้าไปใน <input type="file">
        function syncFileInput(input) {
            const dataTransfer = new DataTransfer();
            workInstructionFiles.forEach(file => {
                dataTransfer.items.add(file);
            });
            input.files = dataTransfer.files;
        }

        // ฟังก์ชันลบไฟล์ที่เลือกไว้ออก (ทีละไฟล์)
        function removeWorkInstruction(index) {
            workInstructionFiles.splice(index, 1); // เอาออกจาก array
            const input = document.getElementById('work-instructions');
            syncFileInput(input); // อัปเดต input ใหม่
            renderWorkInstructionPreviews(); // วาดหน้าจอใหม่
        }

        // ฟังก์ชันแสดงป้ายชื่อไฟล์บนหน้าจอ
        function renderWorkInstructionPreviews() {
            const previewContainer = document.getElementById('instruction-previews');
            previewContainer.innerHTML = ''; // ล้างหน้าจอเดิม

            workInstructionFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'text-[12px] bg-slate-100 text-slate-700 px-3 py-1 rounded-full border border-slate-200 flex items-center gap-1.5 shadow-sm';
                
                fileItem.innerHTML = `
                    <i data-lucide="file-text" class="w-3 h-3 text-emerald-600"></i> 
                    <span class="max-w-[150px] truncate" title="${file.name}">${file.name}</span>
                    <button type="button" onclick="removeWorkInstruction(${index})" class="ml-1 text-slate-400 hover:text-red-500 focus:outline-none" title="ลบไฟล์นี้">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                `;
                previewContainer.appendChild(fileItem);
            });

            // เรนเดอร์ไอคอน Lucide (ไฟล์เอกสาร และ เครื่องหมายกากบาท)
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function addSpareRow() {
            if (hotSpareParts) hotSpareParts.alter('insert_row_below');
        }

        function addHotRows() {
            if(!hot) return;
            const count = parseInt(document.getElementById('row-add-count').value) || 1;
            const lastRowIndex = Math.max(0, hot.countRows() - 1);
            hot.alter('insert_row_below', lastRowIndex, count);
            hot.render();
        }

        function saveChecksheet() {
            const name = document.getElementById('checksheet-name').value || 'Template ' + getToday();
            const category = document.getElementById('checksheet-dept').value || 'ทั่วไป';
            
            const rawData = hot.getData();
            const cleanData = rawData.filter(row => row.some(cell => cell !== null && cell !== ''));
            
            if(cleanData.length === 0) {
                Swal.fire('ข้อผิดพลาด', 'กรุณาระบุหัวข้อการตรวจสอบอย่างน้อย 1 รายการ', 'error');
                return;
            }

            appState.checksheets.push({ id: Date.now(), name, category, itemCount: cleanData.length, data: cleanData });
            
            Swal.fire({
                title: 'บันทึก Template สำเร็จ!',
                html: `ฟอร์มตรวจสอบ <b>${name}</b> ถูกเพิ่มลงระบบแล้ว`,
                icon: 'success',
                confirmButtonColor: '#006B9F'
            });
            
            checksheetImageFiles = [];
            renderImagePreviews();
            
            // Clean Forms
            document.getElementById('checksheet-name').value = '';
            document.getElementById('checksheet-dept').value = '';
            document.getElementById('checksheet-effective-date').value = '';
            document.getElementById('checksheet-doc-no').value = '';
            document.getElementById('checksheet-estimated-time').value = '';
            document.getElementById('image-previews').innerHTML = '';
            document.getElementById('checksheet-images').value = '';
            
            hot.loadData([['', '', '', '', 'ผ่าน / ไม่ผ่าน', '', 'ไม่บังคับ', '', '', '']]);
            hotSpareParts.loadData([['', '']]);
        }

        function deleteChecksheet(id) {
            Swal.fire({
                title: 'ยืนยันการลบฟอร์ม?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: 'ลบเลย'
            }).then((result) => {
                if(result.isConfirmed) {
                    appState.checksheets = appState.checksheets.filter(c => c.id != id);
                    renderChecksheets();
                    Swal.fire({title: 'ลบสำเร็จ', icon: 'success', showConfirmButton: false, timer: 1000});
                }
            });
        }

        // ============================================
        // 6. HOLIDAY LOGIC
        // ============================================
        function handleHolidaySubmit(e) {
            e.preventDefault();
            const date = document.getElementById('holiday-date').value;
            const name = document.getElementById('holiday-name').value;
            
            if(appState.holidays.some(h => h.date === date)) {
                Swal.fire('แจ้งเตือน', 'มีวันหยุดในวันที่ระบุอยู่แล้ว', 'warning');
                return;
            }

            appState.holidays.push({ id: Date.now(), date, name });
            Swal.fire({ title: 'เพิ่มวันหยุดสำเร็จ', icon: 'success', showConfirmButton: false, timer: 1500 });
            
            e.target.reset();
            renderHolidays();
            recalculateAllPlans();
        }

        function deleteHoliday(id) {
            appState.holidays = appState.holidays.filter(h => h.id != id);
            renderHolidays();
            recalculateAllPlans();
        }

        function saveWeekendSetting() {
            const isSat = document.getElementById('weekend-sat').checked;
            const isSun = document.getElementById('weekend-sun').checked;
            
            let newSetting = [];
            if(isSun) newSetting.push(0);
            if(isSat) newSetting.push(6);
            
            appState.weekendSetting = newSetting;
            recalculateAllPlans();
            
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'อัปเดตตั้งค่าวันหยุดแล้ว', showConfirmButton: false, timer: 2000 });
        }

        function recalculateAllPlans() {
            appState.plans = appState.plans.map(plan => {
                plan.nextDate = calculateNextDate(plan.startDate, plan.cycleNum, plan.cycleUnit, plan.holidayAction);
                return plan;
            });
            renderPlans(); 
        }

        function imageRenderer(instance, td, row, col, prop, value, cellProperties) {
            Handsontable.renderers.TextRenderer.apply(this, arguments);
            td.innerHTML = ''; // ล้างค่า Text เดิม
            td.className = 'htCenter htMiddle';

            if (value && value.startsWith('data:image')) {
                td.innerHTML = `<img src="${value}" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px; cursor: pointer; margin: auto;">`;
            } else {
                td.innerHTML = '<span style="color: #94a3b8; font-size: 10px; cursor: pointer;">➕ เพิ่มรูป</span>';
            }
            return td;
        }

        // ============================================
        // 7. INITIALIZATION (DOM Ready)
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            
            // 1. Handsontable: Spare Parts
            const spareContainer = document.getElementById('hot-spare-parts');
            hotSpareParts = new Handsontable(spareContainer, {
                data: [['', '']], 
                colHeaders: ['อะไหล่ที่ใช้', 'จำนวนอะไหล่'],
                columns: [
                    { type: 'text', placeholder: 'ระบุชื่ออะไหล่' },
                    { type: 'numeric', placeholder: '0' }
                ],
                stretchH: 'all', height: 'auto', autoWrapRow: true, licenseKey: 'non-commercial-and-evaluation'
            });
            
            // 2. Handsontable: Checksheet Builder
            const container = document.getElementById('hot-checksheet');
            hot = new Handsontable(container, {
                data: [['', '', '', '', 'ผ่าน / ไม่ผ่าน', '', 'ไม่บังคับ', '', '', '']],
                colHeaders: [
                    'จุดที่ตรวจสอบ', 'มาตรฐานการตรวจสอบ', 'วิธีตรวจสอบ/เครื่องมือ', 'ข้อปฏิบัติเมื่อผิดปกติ',
                    'ประเภทจุดตรวจสอบ', 'รูปภาพประกอบ', 'บังคับถ่ายรูป',
                    'ชื่อค่าวัด', 'หน่วยวัด', 'ค่าที่คาดหวัง'
                ],
                height: 350, width: '100%', stretchH: 'all', rowHeaders: true, contextMenu: true, manualColumnResize: true,
                licenseKey: 'non-commercial-and-evaluation',
                columns: [
                    { type: 'text' }, { type: 'text' }, { type: 'text' }, { type: 'text' },
                    { type: 'dropdown', source: ['ผ่าน / ไม่ผ่าน', 'ผ่าน / ไม่ผ่าน / ไม่เกี่ยวข้อง'] },
                    { 
                        renderer: imageRenderer,
                        readOnly: true 
                    },
                    { type: 'dropdown', source: ['บังคับ', 'ไม่บังคับ', 'บังคับเฉพาะกรณีที่ไม่ผ่าน'] },
                    { type: 'text' }, { type: 'text' }, { type: 'text' }  
                ],
                afterOnCellMouseDown: function(event, coords, TD) {
                    if (coords.col === 5 && coords.row >= 0) {
                        const fileInput = document.getElementById('hot-image-input');
                        if (!fileInput) return;

                        fileInput.value = ''; 

                        fileInput.onchange = (e) => {
                            const file = e.target.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = (readerEvent) => {
                                    this.setDataAtCell(coords.row, coords.col, readerEvent.target.result);
                                };
                                reader.readAsDataURL(file);
                            }
                        };
                        fileInput.click();
                    }
                },
                cells(row, col) {
                    const cellProperties = {};
                    if (col >= 5 && col <= 7) { 
                        cellProperties.className = 'bg-blue-50/50'; 
                    }
                    return cellProperties;
                }
            });

            // 3. FullCalendar
            var calendarEl = document.getElementById('pm-calendar');
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'th',
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listMonth' },
                themeSystem: 'standard',
                eventClick: function(info) {
                    Swal.fire({
                        title: info.event.title,
                        html: `กำหนดการตรวจสอบ: <b>${info.event.start.toLocaleDateString('th-TH')}</b><br><br>ต้องการเปิดใบงาน (Work Order) เพื่อเข้าปฏิบัติงานหรือไม่?`,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonColor: '#006B9F',
                        cancelButtonColor: '#cbd5e1',
                        confirmButtonText: 'เปิดใบงาน'
                    });
                }
            });
            calendar.render();

            // 4. Choices.js Multi-select
            const assigneeSelect = document.getElementById('plan-assignees');
            if(assigneeSelect) {
                assigneesChoiceInstance = new Choices(assigneeSelect, {
                    removeItemButton: true, searchPlaceholderValue: 'พิมพ์ค้นหา...', placeholderValue: 'เลือกผู้รับผิดชอบ',
                    noResultsText: 'ไม่พบรายชื่อ', itemSelectText: 'กดเพื่อเลือก'
                });
            }

            // 5. Initial Renders
            renderChecksheets(); 
            renderPlans(); 
            renderHistory();
            renderHolidays();
        });
    </script>
</body>
</html>