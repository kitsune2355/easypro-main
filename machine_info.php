<?php 
@session_start();
include "config_ctrl/checksession.php"; 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Machine Asset Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:ital,wght@0,100..900;1,100..900&family=Noto+Sans+Thai:wght@100..900&display=swap" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/ag-grid-enterprise/dist/ag-grid-enterprise.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    <script src="js/img-carousel.js"></script>

    <style>
        :root {
            --color-bg-light: #F0F4F8;
            --color-primary: #006B9F; 
            --color-secondary: #04ADFF;
            --ag-accent-color: #006B9F;
            --ag-header-background-color: #f8fafc;
            --ag-row-hover-color: #f1f5f9;
            --ag-selected-row-color: #e2e8f0;
            --ag-font-family: 'Kanit', sans-serif;
            --ag-font-size: 13px;
        }
        
        body {
            font-family: 'Kanit', sans-serif;
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

        .ag-theme-alpine {
            --ag-border-color: #e2e8f0;
            --ag-border-radius: 12px;
            width: 100%;
            height: 100%;
        }

        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        #qrcode-box img, #qr-preview-main img {
            display: inline-block !important;
        }

        .qr-wrap {
            position: relative;
            display: inline-flex;
        }

        .qr-logo-badge {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 22%;
            height: 22%;
            background: #fff;
            border-radius: 999px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 0 2px #fff;
            padding: 2px;
            z-index: 2;
        }

        .qr-logo-badge img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 999px;
        }

        .ag-theme-alpine .ag-cell {
            display: flex;
            align-items: center;
        }

        /* Carousel Styles inside SweetAlert */
        .swal-carousel-container {
            position: relative;
            width: 100%;
            height: 350px;
            overflow: hidden;
            border-radius: 1.5rem;
            background: #f8fafc;
        }
        .carousel-item {
            display: none;
            width: 100%;
            height: 100%;
            animation: fadeIn 0.3s ease;
        }
        .carousel-item.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .carousel-item img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .carousel-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.8);
            backdrop-blur: sm;
            color: #334155;
            padding: 8px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 20;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            transition: all 0.2s;
        }
        .carousel-btn:hover { background: white; transform: translateY(-50%) scale(1.1); }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

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
            #myGrid {
                height: 400px !important; 
                min-height: 400px;
            }
        }
    </style>
</head>
<body class="flex flex-col antialiased text-slate-700">

    <!-- Header Section -->
    <nav class="flex-none px-3 py-4 flex items-center justify-between border-b border-slate-200 bg-white z-30">
        <div class="flex items-center gap-3">
            <div class="p-2.5 bg-[--color-primary] rounded-xl shadow-lg shadow-sky-100 text-white shadow-lg shadow-sky-200">
                <i data-lucide="drill" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold text-slate-900 leading-none">Machine Management</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">ข้อมูลอุปกรณ์เครื่องจักร</p>
            </div>
        </div>

        <div class="flex items-center gap-2 md:gap-3">
            <button onClick="printFilteredMachines()" class="flex items-center gap-2 bg-black text-white border border-slate-200 hover:bg-bg-white text-slate-700 px-3 md:px-4 py-2.5 rounded-xl text-sm font-medium transition-all shadow-sm">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span class="hidden md:inline">พิมพ์ QR Code</span>
            </button>

            <button onClick="openDrawer('add')" class="flex items-center gap-2 btn-gradient px-3 md:px-5 py-2.5 rounded-xl text-sm font-medium transition-all shadow-md">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span class="hidden md:inline">ลงทะเบียนเครื่องจักร</span>
            </button>
        </div>
    </nav>

    <!-- Main Content Area -->
    <main class="flex-1 flex flex-col p-4 gap-4 overflow-hidden">
        
        <!-- Action Bar -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 flex-none">
            <div class="relative w-full md:w-96">
                <i data-lucide="search" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="grid-search" oninput="onFilterTextBoxChanged()" placeholder="ค้นหาครุภัณฑ์, ชื่อ, หรือซีเรียล..." 
                    class="w-full bg-white border border-slate-200 rounded-xl pl-11 pr-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-sky-500 outline-none transition-all">
            </div>
            <div class="flex gap-2 text-xs font-bold text-slate-500">
                <div class="bg-white border border-slate-200 rounded-xl px-4 py-2 flex items-center gap-2">
                    <span id="row-count" class="text-sky-600 font-bold">0</span> รายการทั้งหมด
                </div>
            </div>
        </div>

        <!-- Grid Container -->
        <div class="flex-1 overflow-hidden p-1 relative">
            <div id="myGrid" class="ag-theme-alpine w-full h-full"></div>
        </div>
    </main>

    <!-- Drawer Panel -->
    <div id="drawer-overlay" onClick="closeDrawer()" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 hidden opacity-0 transition-opacity duration-300"></div>
    <div id="drawer-panel" class="fixed top-0 right-0 h-full w-full md:w-[850px] bg-slate-50 z-50 shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col">
        
        <div class="flex-none bg-white border-b px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-[--color-primary] flex items-center justify-center text-white shadow-lg shadow-sky-100">
                    <i data-lucide="plus" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900">ลงทะเบียนเครื่องจักร</h2>
                    <p class="text-xs text-slate-400 font-medium">เพิ่มข้อมูลเข้าระบบเพื่อสร้าง QR Code อัตโนมัติ</p>
                </div>
            </div>
            <button onClick="closeDrawer()" class="w-12 h-12 rounded-full border border-slate-100 flex items-center justify-center text-slate-400 hover:rotate-90 hover:bg-slate-50 transition-all duration-300">
                <i data-lucide="x" class="w-6 h-6"></i>
              </button>
        </div>

        <div class="flex-1 overflow-y-auto custom-scroll p-4">
            <form id="machineForm" class="grid grid-cols-1 md:grid-cols-12 gap-4 pb-12">
                
                <!-- Left: QR & Status Card -->
                <div class="md:col-span-4 space-y-4">
                    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm flex flex-col items-center gap-4 text-center">
                        <div id="qrcode-box" class="p-4 bg-white border-2 border-slate-100 rounded-2xl shadow-inner min-h-[160px] flex items-center justify-center">
                            <div id="qr-empty" class="text-slate-200"><i data-lucide="qr-code" class="w-32 h-24"></i></div>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Live QR Preview</p>
                            <p id="qr-label" class="text-xs font-mono font-bold text-sky-600 italic">PLEASE ENTER ASSET ID</p>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">สถานะการใช้งาน</label>
                        <select id="machine_status" class="w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-semibold outline-none focus:ring-2 focus:ring-sky-500 transition-all" onChange="this.className=this.options[this.selectedIndex].className + ' w-full bg-slate-50 border-none rounded-2xl px-4 py-3 text-sm font-semibold outline-none focus:ring-2 focus:ring-sky-500 transition-all'">
                            <option value="ปกติ" class="text-emerald-600">🟢 ปกติ (Active)</option>
                            <option value="แจ้งซ่อม" class="text-amber-600">🟡 แจ้งซ่อม (Maintenance)</option>
                            <option value="ชำรุด" class="text-rose-600">🔴 ชำรุด (Broken)</option>
                            <option value="สำรอง" class="text-sky-600">🔵 สำรอง (Spare)</option>
                            <option value="" class="text-slate-500">⚪ ไม่ทราบสถานะ (Unknown)</option>
                        </select>
                    </div>

                    <div class="bg-white p-4 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">รูปภาพเครื่องจักร (สูงสุด 3 รูป)</label>
                        <div class="grid grid-cols-3 gap-2">
                            <?php for($i=1; $i<=3; $i++): ?> 
                                <div class="relative aspect-square">
                                    <div id="drop_zone_<?= $i ?> "
                                        onclick="document.getElementById('file_img_<?= $i ?>').click()"
                                        class="h-full bg-slate-50 border-2 border-dashed border-slate-200 rounded-xl flex items-center justify-center cursor-pointer hover:border-sky-300 transition-all overflow-hidden">
                                        
                                        <img id="preview_<?= $i ?>" 
                                            onclick="previewFullImage(<?= $i ?>); event.stopPropagation();" 
                                            class="hidden w-full h-full object-cover cursor-zoom-in hover:opacity-90 transition-opacity">
                                        
                                        <div id="placeholder_<?= $i ?>" class="flex flex-col items-center">
                                            <i data-lucide="image-plus" class="w-5 h-5 text-slate-300"></i>
                                        </div>
                                        <input type="file" id="file_img_<?= $i ?>" name="machine_images[]" accept="image/*" class="hidden" onChange="previewImage(this, <?= $i ?>)">
                                    </div>
                                    <button type="button" id="btn_remove_<?= $i ?>" onClick="removeImage(<?= $i ?>)"
                                            class="hidden absolute -top-1 -right-1 bg-rose-500 text-white p-1 rounded-full shadow-lg hover:bg-rose-600 transition-all z-10">
                                        <i data-lucide="x" class="w-3 h-3"></i>
                                    </button>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Form Inputs -->
                <div class="md:col-span-8 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 md:col-span-1">
                            <label class="text-[11px] font-bold text-slate-400 uppercase mb-2 block">รหัสครุภัณฑ์ <span class="text-rose-500">*</span></label>
                            <input type="text" id="asset_id" onKeyUp="updateQR()" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-3 text-sm font-mono focus:border-sky-500 outline-none transition-all" placeholder="เช่น ASSET-001">
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <label class="text-[11px] font-bold text-slate-400 uppercase mb-2 block">ประเภทเครื่องจักร <span class="text-rose-500">*</span></label>
                            <select id="machine_type_input" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-3 text-sm focus:border-sky-500 outline-none transition-all appearance-none">
                                <option value="">-- เลือกประเภท --</option>
                                </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 md:col-span-1">
                            <label class="text-[11px] font-bold text-slate-400 uppercase mb-2 block">ชื่อเครื่องจักร</label>
                            <input type="text" id="machine_name_input" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-3 text-sm focus:border-sky-500 outline-none transition-all" placeholder="ระบุชื่อเรียกอุปกรณ์">
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <label class="text-[11px] font-bold text-slate-400 uppercase mb-2 block">ซีเรียล (Serial Number)</label>
                            <input type="text" id="serial_number" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-3 text-sm focus:border-sky-500 outline-none transition-all" placeholder="S/N: xxxxxxx">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[11px] font-bold text-slate-400 uppercase mb-2 block">ยี่ห้อ (Brand)</label>
                            <input id="brand_input" type="text" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-3 text-sm focus:border-sky-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-slate-400 uppercase mb-2 block">รุ่น (Model)</label>
                            <input id="model_input" type="text" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-3 text-sm focus:border-sky-500 outline-none transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[11px] font-bold text-slate-400 uppercase mb-2 block">วันหมดรับประกัน</label>
                            <input id="warranty_date" type="date" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-3 text-sm focus:border-sky-500 outline-none transition-all">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold text-slate-400 uppercase mb-2 block">บริษัทที่ดูแลอุปกรณ์ (Vendor/Service)</label>
                            <input id="vendor_company" type="text" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-3 text-sm focus:border-sky-500 outline-none transition-all" placeholder="ชื่อบริษัทและเบอร์โทรติดต่อ">
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div id="loc_trigger" onClick="LocationSelector.openModal()" class="cursor-pointer bg-white border border-slate-200 p-4 rounded-3xl hover:border-sky-400 transition-all">
                            <div id="loc_placeholder">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-2xl bg-sky-100 flex items-center justify-center text-sky-600 shrink-0">
                                        <i data-lucide="map-pin" class="w-5 h-5"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-extrabold text-slate-700">ระบุตำแหน่งที่แจ้งซ่อม</p>
                                        <p class="text-[10px] text-slate-400 font-semibold">กดเพื่อเลือก อาคาร / สาขา → ชั้น → ห้อง</p>
                                    </div>
                                    <div class="text-slate-300">
                                        <i data-lucide="chevron-right" class="w-5 h-5"></i>
                                    </div>
                                </div>
                            </div>

                            <div id="loc_selected" class="hidden">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1.5">
                                            <i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400"></i> Building
                                        </span>
                                        <span class="text-[10px] font-extrabold text-emerald-600 bg-emerald-50 border border-emerald-100 rounded-full px-2.5 py-1 inline-flex items-center gap-1.5">
                                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Selected
                                        </span>
                                    </div>
                                    <span id="disp_building" class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
                                        <i data-lucide="map-pin" class="w-4 h-4 text-sky-600"></i>
                                        <span class="min-w-0 break-words"></span>
                                    </span>
                                    <div class="flex flex-wrap gap-2 mt-1">
                                        <span class="px-2 py-1 bg-blue-50 text-sky-700 text-[10px] font-extrabold rounded-lg border border-blue-100 inline-flex items-center gap-1.5">
                                            <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                                            <span id="disp_floor"></span>
                                        </span>
                                        <span id="disp_room" class="px-2 py-1 bg-slate-50 text-slate-600 text-[10px] font-extrabold rounded-lg border border-slate-200 inline-flex items-center gap-1.5">
                                            <i data-lucide="door-open" class="w-3.5 h-3.5"></i>
                                            ห้อง -
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="building" id="in_building" required>
                            <input type="hidden" name="floor" id="in_floor" required>
                            <input type="hidden" name="room" id="in_room" required>
                        </div>
                    </div>

                    <div>
                        <label class="text-[11px] font-bold text-slate-400 uppercase mb-2 block">หมายเหตุ</label>
                        <textarea id="remark_input" rows="3" class="w-full bg-white border border-slate-200 rounded-2xl px-5 py-3 text-sm focus:border-sky-500 outline-none transition-all" placeholder="ข้อมูลเพิ่มเติม..."></textarea>
                    </div>
                </div>
            </form>
        </div>

        <div class="flex-none bg-white border-t px-6 py-4 flex items-center justify-end gap-3">
            <button onClick="handleSave()" class="btn-gradient px-4 py-2 rounded-2xl font-bold shadow-xl shadow-sky-100 transition-all transform active:scale-95 flex items-center gap-2">
                <i data-lucide="check" class="w-5 h-5"></i>
                <span>บันทึกข้อมูล</span>
            </button>
        </div>
    </div>

    <?php include 'machine_history_drawer.php'; ?>

    <script src="js/location-selector.js"></script>
    <script>
        let gridApi;
        const AG_ID = <?php echo json_encode(isset($sess_user_agency_es) ? (string)$sess_user_agency_es : '', JSON_UNESCAPED_UNICODE); ?>;
		const USER_ID = <?php echo json_encode(isset($sess_user_id) ? (string)$sess_user_id : '', JSON_UNESCAPED_UNICODE); ?>;
		
		window.AG_ID = AG_ID;
		window.USER_ID = USER_ID;

        // ฟังก์ชันสำหรับเปิดหน้าพิมพ์ป้าย QR Code
        function printFilteredMachines() {
            // 1. ดึงข้อความที่ค้นหาจากช่องค้นหา (Search)
            const searchTerm = document.getElementById('grid-search').value;
            
            // 2. ดึงค่าการกรอง (Filter) ปัจจุบันของตาราง ag-Grid
            const filterModel = gridApi ? gridApi.getFilterModel() : {};
            const filterModelStr = JSON.stringify(filterModel);
            
            // 3. สร้าง URL ไปยังหน้าพิมพ์ พร้อมแนบตัวแปรไปกับ URL
            const url = new URL('machine_info_print.php', window.location.href);
            url.searchParams.append('ag_id', AG_ID);
            url.searchParams.append('search', searchTerm);
            url.searchParams.append('filterModel', filterModelStr);
            
            // 4. เปิดแท็บใหม่
            window.open(url.toString(), '_blank');
        }

        window.formatDate = function(dateStr, showTime = true) {
            if (!dateStr || dateStr === '0000-00-00' || dateStr === '0000-00-00 00:00:00') return '-';
            
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;

            const datePart = d.toLocaleDateString('th-TH', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });

            if (!showTime) {
                return datePart;
            }

            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');

            return `${datePart}, ${hours}:${minutes} น.`;
        };

        async function loadMachineTypes() {
			const select = document.getElementById('machine_type_input');
		
			if (!select) {
				console.error('ไม่พบ select id="machine_type_input"');
				return;
			}
		
			try {
				select.innerHTML = '<option value="">กำลังโหลดข้อมูล...</option>';
		
				if (!AG_ID) {
					select.innerHTML = '<option value="">ไม่พบข้อมูลหน่วยงาน AG_ID</option>';
					console.error('AG_ID ว่าง กรุณาตรวจสอบ $sess_user_agency_es');
					return;
				}
		
				const response = await fetch(
					`get_all_ass_type.php?ag_id=${encodeURIComponent(AG_ID)}`
				);
		
				const types = await response.json();
		
				select.innerHTML = '<option value="">-- เลือกประเภท --</option>';
		
				if (Array.isArray(types) && types.length > 0) {
					types.forEach(type => {
						const option = document.createElement('option');
						option.value = type.id || type.GroupId;
						option.textContent = type.name || type.TGroupName;
						select.appendChild(option);
					});
				} else {
					select.innerHTML = '<option value="">ไม่พบข้อมูลประเภทเครื่องจักร</option>';
				}
		
			} catch (error) {
				console.error('Error loading machine types:', error);
				select.innerHTML = '<option value="">เกิดข้อผิดพลาดในการโหลดข้อมูล</option>';
			}
		}

        const getFilterParams = (columnName) => {
			return {
				values: (params) => {
					fetch(
						`handle_machine_info.php?action=get_filter_values&column=${encodeURIComponent(columnName)}&ag_id=${encodeURIComponent(AG_ID)}`
					)
					.then(response => response.json())
					.then(data => params.success(data))
					.catch(error => {
						console.error('Filter error:', error);
						params.success([]);
					});
				},
				refreshValuesOnOpen: true,
			};
		};

        class DateRangeFilter {
            init(params) {
                this.params = params;
                this.eGui = document.createElement('div');
                this.eGui.innerHTML = `
                    <div class="space-y-1 p-3 min-w-[180px]">
                        <div class="font-semibold text-[11px] text-slate-600">ช่วงวันที่</div>
                        <input type="date" data-role="dateFrom"
                            class="ag-input-field-input w-full border border-slate-200 rounded mb-1 px-2 py-1 text-[11px]" />
                        <input type="date" data-role="dateTo"
                            class="ag-input-field-input w-full border border-slate-200 rounded px-2 py-1 text-[11px]" />
                    </div>
                `;
                
                this.dateFromInput = this.eGui.querySelector('[data-role="dateFrom"]');
                this.dateToInput = this.eGui.querySelector('[data-role="dateTo"]');

                this.dateFromInput.addEventListener('change', () => this.onFilterChanged());
                this.dateToInput.addEventListener('change', () => this.onFilterChanged());
            }

            getGui() { return this.eGui; }

            isFilterActive() {
                return this.dateFromInput.value !== '' || this.dateToInput.value !== '';
            }

            // ---------- ส่วนที่เพิ่มเข้ามาเพื่อให้ตารางฝั่งหน้าเว็บกรองข้อมูลได้ ----------
            doesFilterPass(params) {
                const value = params.data.asset_ins;
                if (!value || value === '-') return false; // ข้ามรายการที่ไม่มีวันที่

                const cellDate = new Date(value);
                cellDate.setHours(0, 0, 0, 0); // รีเซ็ตเวลาให้เป็นเที่ยงคืนเพื่อเปรียบเทียบแค่วันที่

                let passed = true;

                // ตรวจสอบวันที่เริ่มต้น
                if (this.dateFromInput.value) {
                    const fromDate = new Date(this.dateFromInput.value);
                    fromDate.setHours(0, 0, 0, 0);
                    if (cellDate < fromDate) passed = false;
                }

                // ตรวจสอบวันที่สิ้นสุด
                if (this.dateToInput.value) {
                    const toDate = new Date(this.dateToInput.value);
                    toDate.setHours(0, 0, 0, 0);
                    if (cellDate > toDate) passed = false;
                }

                return passed;
            }
            // ------------------------------------------------------------------

            getModel() {
                if (!this.isFilterActive()) return null;
                return {
                    filterType: 'dateRange',
                    dateFrom: this.dateFromInput.value,
                    dateTo: this.dateToInput.value
                };
            }

            setModel(model) {
                if (model) {
                    this.dateFromInput.value = model.dateFrom || '';
                    this.dateToInput.value = model.dateTo || '';
                } else {
                    this.dateFromInput.value = '';
                    this.dateToInput.value = '';
                }
            }

            onFilterChanged() { 
                this.params.filterChangedCallback(); 
            }
        }

        const columnDefs = [
            { 
                headerName: "รูป", 
                field: "images", 
                width: 80, 
                pinned: 'left',
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
                headerName: "รหัสครุภัณฑ์", 
                field: "asset_id", 
                width: 160, 
                pinned: 'left', 
                filter: 'agSetColumnFilter',
                filterParams: getFilterParams('asset_id'),
                cellRenderer: p => `<span class="font-mono text-[10px] font-bold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-md border border-sky-100">${p.value}</span>` 
            },
            { 
                headerName: "ประเภท", 
                field: "type_name",
                width: 200, 
                filter: 'agSetColumnFilter',
                filterParams: getFilterParams('type_name'),
                cellRenderer: params => {
                    const value = params.value || 'ไม่ระบุประเภท';
                    return `
                        <div class="flex items-center gap-2 h-full">
                            <div class="p-1.5 bg-slate-100 rounded-lg text-slate-500">
                                <i data-lucide="layers" class="w-3.5 h-3.5"></i>
                            </div>
                            <span class="font-medium text-slate-700">${value}</span>
                        </div>
                    `;
                }
            },
            { 
                headerName: "รายละเอียด", 
                field: "machine_name", 
                flex: 1,
                filter: 'agTextColumnFilter',
                cellRenderer: p => `<div class="leading-tight py-2"><div class="font-bold text-slate-800">${p.value}</div><div class="text-[10px] text-slate-400 italic">S/N: ${p.data.serial}</div></div>`
            },
            { 
                headerName: "อาคาร", 
                field: "location", 
                width: 180, 
                filter: 'agSetColumnFilter',
                filterParams: getFilterParams('location')
            },
            { 
                headerName: "ชั้น", 
                field: "floor", 
                width: 110, 
                filter: 'agSetColumnFilter',
                filterParams: getFilterParams('floor')
            },
            { 
                headerName: "ห้อง", 
                field: "room", 
                width: 110, 
                filter: 'agSetColumnFilter',
                filterParams: getFilterParams('room')
            },
            { 
                headerName: 'วันที่สร้าง', 
                field: 'asset_ins', 
                filter: DateRangeFilter,
                cellClass: 'text-sky-700 font-medium',
                valueFormatter: params => window.formatDate(params.value, false),
                sortable: true,
                width: 150
            },
            { 
                headerName: "สถานะ", 
                field: "status", 
                width: 130,
                filter: 'agSetColumnFilter',
                filterParams: getFilterParams('status'),
                cellRenderer: p => {
                    let color = 'emerald';
                    if(p.value === 'แจ้งซ่อม') color = 'amber';
                    if(p.value === 'ชำรุด') color = 'rose';
                    if(p.value === 'สำรอง') color = 'sky';
                    if(p.value === '') color = 'gray';
                    return `<div class="flex items-center gap-1.5 px-2.5 rounded-xl text-[11px] font-bold w-fit bg-${color}-50 text-${color}-600 border border-${color}-100"><span class="w-1.5 h-1.5 rounded-full bg-${color}-500"></span>${p.value || 'ไม่ทราบสถานะ'}</div>`;
                }
            },
            { 
                headerName: "จัดการ", 
                width: 150,
                pinned: 'right',
                cellRenderer: params => {
                    if (!params.data) return '';
                    
                    const assetId = params.data.asset_id || '';
                    const dbId = params.data.id; 
                    setTimeout(() => lucide.createIcons(), 0); 

                    return `
                        <div class="flex items-center gap-1 h-full py-2">
                            <button onclick="viewQuickQR('${assetId}')" class="p-2 hover:bg-sky-50 text-sky-600 rounded-xl transition-colors" title="ดู QR Code">
                                <i data-lucide="qr-code" class="w-4 h-4"></i>
                            </button>
                            <button onclick="viewHistory('${dbId}')" class="p-2 hover:bg-emerald-50 text-emerald-600 rounded-xl transition-colors" title="ประวัติการแจ้งซ่อม">
                                <i data-lucide="history" class="w-4 h-4"></i>
                            </button>
                            <button onclick="editMachine('${dbId}')" class="p-2 hover:bg-slate-50 text-slate-400 rounded-xl transition-colors" title="แก้ไข">
                                <i data-lucide="pen" class="w-4 h-4"></i>
                            </button>
                        </div>`;
                }
            }
        ];

        const gridOptions = {
            columnDefs: columnDefs,
            rowHeight: 65,
            rowModelType: 'serverSide',
            pagination: true,
            paginationPageSize: 10,
            cacheBlockSize: 10,
            paginationPageSizeSelector: [10, 20, 50, 100],
            getMainMenuItems: (params) => {
                // ดึงรายการมาตรฐาน และกรองเอา 'resetColumns' เดิมออก
                const menuItems = params.defaultItems.filter(item => item !== 'resetColumns');

                menuItems.push('separator');
                menuItems.push({
                    name: 'Reset Columns',
                    icon: '<i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>',
                    action: () => {
                        params.api.setFilterModel(null);
                        params.api.resetColumnState();
                        params.api.refreshServerSide();
                    }
                });
                setTimeout(() => lucide.createIcons(), 50);

                return menuItems;
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
                            exportToExcelWithImages();
                        }
                    }
                ];
                
                // สั่งให้ Lucide สร้าง Icon ในเมนู (ใช้ setTimeout เพื่อรอให้ Menu Render เสร็จ)
                setTimeout(() => lucide.createIcons(), 50);
                
                return result;
            },
            onRowDataUpdated: () => lucide.createIcons(),
            onViewportChanged: () => lucide.createIcons(),
            onPaginationChanged: () => lucide.createIcons(),
            onRowGroupOpened: () => lucide.createIcons(),
            onGridReady: (params) => {
                gridApi = params.api;
                loadGridData();
            },
            onPaginationChanged: (params) => {
                if (params.newPageSize) {
                    const newPageSize = gridApi.getGridOption('paginationPageSize');
                    gridApi.setGridOption('cacheBlockSize', newPageSize);
                }
            }
        };

        async function loadGridData() {
            const ag_id = AG_ID;

			if (!ag_id) {
				console.error('ไม่พบ AG_ID สำหรับโหลดข้อมูลเครื่องจักร');
				return;
			}
            
            const datasource = {
                getRows: async (params) => {
                    const searchTerm = document.getElementById('grid-search').value;
                    const requestData = params.request;
                    
                    const url = new URL('handle_machine_info.php', window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/'));
                    url.searchParams.append('action', 'get_all');
                    url.searchParams.append('ag_id', ag_id);
                    
                    // ส่งตำแหน่งแถวเริ่มต้นและแถวสุดท้าย (Ag-Grid จะคำนวณจาก paginationPageSize ให้อัตโนมัติ)
                    url.searchParams.append('startRow', requestData.startRow);
                    url.searchParams.append('endRow', requestData.endRow);
                    url.searchParams.append('search', searchTerm);

                    url.searchParams.append('filterModel', JSON.stringify(params.request.filterModel));
                    
                    // ส่ง Sort Model
                    if (requestData.sortModel && requestData.sortModel.length > 0) {
                        url.searchParams.append('sortModel', JSON.stringify(requestData.sortModel));
                    }

                    try {
                        const response = await fetch(url);
                        const result = await response.json();

                        if (result.rows) {
                            params.success({
                                rowData: result.rows,
                                rowCount: result.lastRow // จำนวนแถวทั้งหมดเพื่อให้ปุ่ม Next/Prev ทำงานถูก
                            });
                            
                            if(document.getElementById('row-count')) {
                                document.getElementById('row-count').innerText = result.lastRow;
                            }
                            setTimeout(() => lucide.createIcons(), 100);
                        } else {
                            params.fail();
                        }
                    } catch (error) {
                        console.error("Fetch error:", error);
                        params.fail();
                    }
                }
            };

            gridApi.setGridOption('serverSideDatasource', datasource);
        }

        function onFilterTextBoxChanged() {
            if (gridApi) {
                gridApi.refreshServerSide();
            }
        }

        window.onload = async () => {
            const gridDiv = document.querySelector('#myGrid');
            gridApi = agGrid.createGrid(gridDiv, gridOptions);
            
            await loadGridData();
        };

        function changeSlide(dir, total) {
            document.querySelector(`#slide-${currentSlide}`).classList.remove('active');
            document.querySelector(`#dot-${currentSlide}`).classList.replace('bg-sky-500', 'bg-slate-300');
            currentSlide = (currentSlide + dir + total) % total;
            document.querySelector(`#slide-${currentSlide}`).classList.add('active');
            document.querySelector(`#dot-${currentSlide}`).classList.replace('bg-slate-300', 'bg-sky-500');
        }

        function viewQuickQR(assetId) {
            Swal.fire({
                title: `QR Code: ${assetId}`,
                html: `<div id="qr-preview-main" class="flex justify-center p-4 bg-slate-50 rounded-2xl border border-slate-100"></div>`,
                showConfirmButton: true,
                confirmButtonText: 'ปิดหน้าต่าง',
                confirmButtonColor: '#006B9F',
                customClass: { popup: 'rounded-3xl' },
                didOpen: () => {
                    const holder = document.getElementById('qr-preview-main');
                    const wrap = document.createElement('div');
                    wrap.className = 'qr-wrap';
                    holder.appendChild(wrap);
                    new QRCode(wrap, {
                        text: buildHistoryUrl(assetId), width: 220, height: 220,
                        colorDark : "#0f172a", colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.H
                    });
                    const badge = document.createElement('div');
                    badge.className = 'qr-logo-badge';
                    badge.innerHTML = `<img src="logo - easypro2.png" onerror="this.parentElement.style.display='none'">`;
                    wrap.appendChild(badge);
                }
            });
        }

        function openDrawer(mode = 'edit') {
            if (mode === 'add') {
                document.getElementById('machineForm').reset();
                document.querySelector('#drawer-panel h2').innerText = "ลงทะเบียนเครื่องจักร";
                
                // 1. ล้างรูปพรีวิว
                for(let i=1; i<=3; i++) removeImage(i);
                
                // 2. ล้างข้อมูลสถานที่ (Location Selector)
                // ล้างค่าใน Hidden Inputs
                document.getElementById('in_building').value = "";
                document.getElementById('in_floor').value = "";
                document.getElementById('in_room').value = "";
                
                // ล้างค่าใน Dataset (ถ้ามีการใช้ดึงข้อมูลตอน Save)
                document.getElementById('in_room').dataset.id = ""; 

                // สลับ UI กลับไปหน้า "ยังไม่ได้เลือก"
                document.getElementById('loc_placeholder').classList.remove('hidden');
                document.getElementById('loc_selected').classList.add('hidden');
                
                // ล้างตัวหนังสือที่เคยแสดงผลไว้
                document.querySelector('#disp_building span').innerText = "";
                document.getElementById('disp_floor').innerText = "";
                document.getElementById('disp_room').innerHTML = '<i data-lucide="door-open" class="w-3.5 h-3.5"></i> ห้อง -';

                // 3. อัปเดต QR และ Icon
                updateQR(); 
                lucide.createIcons(); // รีเฟรช icon ในส่วนที่ล้างค่าใหม่
            }

            const overlay = document.getElementById('drawer-overlay');
            const panel = document.getElementById('drawer-panel');
            overlay.classList.remove('hidden');
            setTimeout(() => {
                overlay.classList.add('opacity-100');
                panel.classList.remove('translate-x-full');
            }, 10);
        }

        function closeDrawer() {
            const overlay = document.getElementById('drawer-overlay');
            const panel = document.getElementById('drawer-panel');
            overlay.classList.remove('opacity-100');
            panel.classList.add('translate-x-full');
            setTimeout(() => overlay.classList.add('hidden'), 300);
        }

        // สร้างลิงก์ไปยังหน้าประวัติเครื่องจักร (สำหรับให้ QR Code สแกนแล้วเปิดหน้านี้)
        function buildHistoryUrl(assetId) {
            const url = new URL('machine_history_view.php', window.location.href);
            url.searchParams.set('asset_id', assetId);
            if (typeof AG_ID !== 'undefined' && AG_ID) url.searchParams.set('ag_id', AG_ID);
            return url.toString();
        }

        function updateQR() {
            const val = document.getElementById('asset_id').value;
            const box = document.getElementById('qrcode-box');
            const label = document.getElementById('qr-label');

            if (val.trim().length > 0) {
                label.innerText = val.toUpperCase();
                label.classList.remove('italic', 'text-slate-400');
                box.innerHTML = "";
                const wrap = document.createElement('div');
                wrap.className = 'qr-wrap';
                box.appendChild(wrap);
                new QRCode(wrap, {
                    text: buildHistoryUrl(val), width: 140, height: 140,
                    colorDark : "#0f172a", colorLight : "#ffffff",
                    correctLevel : QRCode.CorrectLevel.H
                });
                const badge = document.createElement('div');
                badge.className = 'qr-logo-badge';
                badge.innerHTML = `<img src="logo - easypro2.png" onerror="this.parentElement.style.display='none'">`;
                wrap.appendChild(badge);
            } else {
                box.innerHTML = '<div id="qr-empty" class="text-slate-200"><i data-lucide="qr-code" class="w-32 h-24"></i></div>';
                label.innerText = "PLEASE ENTER ASSET ID";
                label.classList.add('italic');
                lucide.createIcons();
            }
        }

        async function exportToExcelWithImages() {
            if (!gridApi) return;

            Swal.fire({
                title: 'กำลังเตรียมไฟล์ Excel...',
                text: 'ระบบกำลังดึงข้อมูลทั้งหมดตามที่ตัวกรองเลือกไว้',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                const filterModel = gridApi.getFilterModel(); 
                const columnState = gridApi.getColumnState();
                const sortModel = columnState
                    .filter(s => s.sort != null)
                    .map(s => ({
                        colId: s.colId,
                        sort: s.sort
                    }));

                const ag_id = AG_ID;
				const searchVal = document.getElementById('grid-search')?.value || '';

                const url = new URL('handle_machine_info.php', window.location.href);
                url.searchParams.append('action', 'get_all');
                url.searchParams.append('ag_id', ag_id);
                url.searchParams.append('search', searchVal);
                url.searchParams.append('filterModel', JSON.stringify(filterModel));
                url.searchParams.append('sortModel', JSON.stringify(sortModel));
                url.searchParams.append('export', 'true');

                const response = await fetch(url);
                const result = await response.json();
                
                if (!result.rows || result.rows.length === 0) {
                    Swal.fire('แจ้งเตือน', 'ไม่พบข้อมูลที่ต้องการ Export', 'warning');
                    return;
                }

                const nodes = result.rows; 
                const workbook = new ExcelJS.Workbook();
                const worksheet = workbook.addWorksheet('Machine Assets');

                // ตั้งค่า Columns
                worksheet.columns = [
                    { header: 'QR Code', key: 'qr', width: 15 },
                    { header: 'รูปภาพ', key: 'img', width: 20 },
                    { header: 'รหัสครุภัณฑ์', key: 'asset_id', width: 20 },
                    { header: 'ประเภท', key: 'type_name', width: 20 },
                    { header: 'ชื่อเครื่องจักร', key: 'machine_name', width: 25 },
                    { header: 'ซีเรียล (S/N)', key: 'serial', width: 20 },
                    { header: 'ยี่ห้อ (Brand)', key: 'brand', width: 15 },
                    { header: 'รุ่น (Model)', key: 'model', width: 15 },
                    { header: 'อาคาร', key: 'location', width: 20 },
                    { header: 'ชั้น', key: 'floor', width: 10 },
                    { header: 'ห้อง', key: 'room', width: 15 },
                    { header: 'วันที่สร้าง', key: 'asset_ins', width: 15 },
                    { header: 'สถานะ', key: 'status', width: 15 },
                    { header: 'วันหมดประกัน', key: 'warranty', width: 15 },
                    { header: 'บริษัทที่ดูแล/Vendor', key: 'company', width: 25 },
                    { header: 'หมายเหตุ', key: 'remark', width: 30 }
                ];

                // วนลูปสร้าง Row จากข้อมูลที่ได้จาก Server
                for (let i = 0; i < nodes.length; i++) {
                    const data = nodes[i];
                    const rowIndex = i + 2; 
                    worksheet.getRow(rowIndex).height = 80;

                    worksheet.getRow(rowIndex).values = {
                        asset_id: data.asset_id,
                        type_name: data.type_name,
                        machine_name: data.machine_name,
                        serial: data.serial,
                        brand: data.brand,
                        model: data.model,
                        location: data.location,
                        floor: data.floor,
                        room: data.room,
                        status: data.status,
                        warranty: data.warranty,
                        company: data.company,
                        remark: data.remark
                    };

                    // จัดการ QR Code
                    const qrBase64 = await generateQRBase64(data.asset_id);
                    if (qrBase64) {
                        const qrId = workbook.addImage({ base64: qrBase64, extension: 'png' });
                        worksheet.addImage(qrId, {
                            tl: { col: 0.1, row: rowIndex - 0.9 },
                            ext: { width: 70, height: 70 }
                        });
                    }

                    // จัดการรูปภาพ (รูปแรก)
                    if (data.images && data.images.length > 0) {
                        const imgBase64 = await getBase64FromUrl(data.images[0]);
                        if (imgBase64) {
                            const machineImgId = workbook.addImage({ base64: imgBase64, extension: 'png' });
                            worksheet.addImage(machineImgId, {
                                tl: { col: 1.1, row: rowIndex - 0.9 },
                                ext: { width: 100, height: 70 }
                            });
                        }
                    }
                    worksheet.getRow(rowIndex).alignment = { vertical: 'middle', horizontal: 'left', wrapText: true };
                }

                // สไตล์ Header
                worksheet.getRow(1).font = { bold: true, color: { argb: 'FFFFFFFF' } };
                worksheet.getRow(1).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: '006B9F' } };
                worksheet.getRow(1).alignment = { horizontal: 'center' };

                const buffer = await workbook.xlsx.writeBuffer();
                saveAs(new Blob([buffer]), `Machine_Export_${new Date().toISOString().slice(0,10)}.xlsx`);
                
                Swal.fire('สำเร็จ', 'ดาวน์โหลดไฟล์เรียบร้อยแล้ว', 'success');

            } catch (error) {
                console.error("Export Error:", error);
                Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการดึงข้อมูลเพื่อ Export', 'error');
            }
        }

        /**
         * Utility to convert an Image URL to Base64 for ExcelJS
         */
        async function getBase64FromUrl(url) {
            try {
                const data = await fetch(url);
                const blob = await data.blob();
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.readAsDataURL(blob);
                    reader.onloadend = () => {
                        const base64data = reader.result;
                        resolve(base64data);
                    };
                });
            } catch (error) {
                console.error("Error converting image to base64", error);
                return null;
            }
        }

        /**
         * Utility to generate a QR Code Base64 string silently 
         */
        function generateQRBase64(text) {
            return new Promise((resolve) => {
                // Create a temporary hidden div to render the QR
                const tempDiv = document.createElement('div');
                new QRCode(tempDiv, {
                    text: text,
                    width: 150,
                    height: 150,
                    correctLevel: QRCode.CorrectLevel.H
                });

                // Draws the company logo onto the center of the QR canvas
                // and resolves with the flattened base64 PNG.
                const drawLogoAndResolve = (canvas) => {
                    const logo = new Image();
                    logo.onload = () => {
                        const ctx = canvas.getContext('2d');
                        const logoSize = canvas.width * 0.22;
                        const cx = canvas.width / 2;
                        const cy = canvas.height / 2;

                        // White circular backdrop so the logo stays legible
                        ctx.save();
                        ctx.beginPath();
                        ctx.arc(cx, cy, logoSize / 2 + 6, 0, Math.PI * 2);
                        ctx.fillStyle = '#ffffff';
                        ctx.fill();
                        ctx.restore();

                        ctx.drawImage(logo, cx - logoSize / 2, cy - logoSize / 2, logoSize, logoSize);
                        resolve(canvas.toDataURL("image/png"));
                    };
                    // If the logo can't be loaded, just fall back to the plain QR
                    logo.onerror = () => resolve(canvas.toDataURL("image/png"));
                    logo.src = 'logo - easypro2.png';
                };

                // Wait a small moment for the library to render the canvas/img
                setTimeout(() => {
                    const img = tempDiv.querySelector('img');
                    const canvas = tempDiv.querySelector('canvas');

                    if (canvas) {
                        drawLogoAndResolve(canvas);
                    } else if (img && img.src && img.src.startsWith('data:image')) {
                        // Some browsers render qrcodejs output as an <img> instead of <canvas>;
                        // copy it onto a canvas first so we can draw the logo on top.
                        const fallbackCanvas = document.createElement('canvas');
                        fallbackCanvas.width = 150;
                        fallbackCanvas.height = 150;
                        const srcImg = new Image();
                        srcImg.onload = () => {
                            fallbackCanvas.getContext('2d').drawImage(srcImg, 0, 0, 150, 150);
                            drawLogoAndResolve(fallbackCanvas);
                        };
                        srcImg.onerror = () => resolve(img.src);
                        srcImg.src = img.src;
                    } else {
                        resolve(null);
                    }
                }, 50);
            });
        }

        async function editMachine(id) {
            try {
                // Swal.fire({ title: 'กำลังโหลดข้อมูล...', didOpen: () => Swal.showLoading() });

                const response = await fetch(`handle_machine_info.php?action=get_by_id&id=${id}`);
                const result = await response.json();

                if (result.success) {
                    const data = result.data;
                    Swal.close();

                    window.currentEditId = id; 
                    document.querySelector('#drawer-panel h2').innerText = "แก้ไขข้อมูลเครื่องจักร";
                    document.querySelector('#drawer-panel p').innerText = "แก้ไขข้อมูลเข้าระบบเพื่อสร้าง QR Code อัตโนมัติ";
                    document.getElementById('machine_type_input').value = data.asset_type || '';
                    document.getElementById('asset_id').value = data.asset_id || '';
                    document.getElementById('machine_name_input').value = data.machine_name || '';
                    document.getElementById('serial_number').value = data.serial || '';
                    document.getElementById('brand_input').value = data.brand || '';
                    document.getElementById('model_input').value = data.model || '';
                    document.getElementById('warranty_date').value = data.warranty || '';
                    document.getElementById('vendor_company').value = data.company || '';
                    document.getElementById('remark_input').value = data.remark || '';
                    document.getElementById('machine_status').value = data.status || '';

                    // อัปเดตสถานที่
                    if (data.location && data.location !== 'ไม่ระบุ') {
                        // 1. สลับการแสดงผลจาก Placeholder เป็น Selected Card
                        document.getElementById('loc_placeholder').classList.add('hidden');
                        document.getElementById('loc_selected').classList.remove('hidden');

                        // 2. นำชื่อ (Text) ไปแสดงใน UI
                        document.querySelector('#disp_building span').innerText = data.location;
                        document.getElementById('disp_floor').innerText = data.floor;
                        document.getElementById('disp_room').innerText = (data.room && data.room !== '-') ? `ห้อง ${data.room}` : 'ไม่ระบุห้อง';

                        // 3. ใส่ ID ลงใน Hidden Input เพื่อใช้ตอนบันทึก
                        document.getElementById('in_building').value = data.area_id || '';
                        document.getElementById('in_floor').value = data.ac_id || '';
                        document.getElementById('in_room').value = data.ar_id || '';
                    } else {
                        // ถ้าไม่มีข้อมูล ให้กลับไปหน้าเลือกปกติ
                        document.getElementById('loc_placeholder').classList.remove('hidden');
                        document.getElementById('loc_selected').classList.add('hidden');
                    }

                    updateQR();

                    // จัดการรูปภาพ
                    for(let i=1; i<=3; i++) removeImage(i); 
                    for (let i = 1; i <= 3; i++) {
                        const imgUrl = data[`image${i}`];
                        if (imgUrl) {
                            const preview = document.getElementById('preview_' + i);
                            if (preview) {
                            preview.src = imgUrl;
                            preview.classList.remove('hidden');
                            document.getElementById('placeholder_' + i).classList.add('hidden');
                            document.getElementById('btn_remove_' + i).classList.remove('hidden');
                            }
                        }
                    }

                    openDrawer('edit');
                } else {
                    Swal.fire('ผิดพลาด', result.error, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
            }
        }

        function previewFullImage(index) {
            const allImages = [];
            let activeIndex = 0;

            for (let i = 1; i <= 3; i++) {
                const img = document.getElementById('preview_' + i);
                if (img && img.src && !img.classList.contains('hidden')) {
                    allImages.push(img.src);
                    if (i === index) activeIndex = allImages.length - 1;
                }
            }

            if (allImages.length > 0) {
                ImageCarousel.open(allImages);
            }
        }

        function previewImage(input, index) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const preview = document.getElementById('preview_' + index);
                    const placeholder = document.getElementById('placeholder_' + index);
                    const btnRemove = document.getElementById('btn_remove_' + index);
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                    btnRemove.classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeImage(index) {
            const input = document.getElementById('file_img_' + index);
            const preview = document.getElementById('preview_' + index);
            const placeholder = document.getElementById('placeholder_' + index);
            const btnRemove = document.getElementById('btn_remove_' + index);
            input.value = "";
            preview.src = "";
            preview.classList.add('hidden');
            placeholder.classList.remove('hidden');
            btnRemove.classList.add('hidden');
        }

        async function handleSave() {
            const assetId = document.getElementById('asset_id').value;
            if(!assetId) {
                Swal.fire('คำเตือน', 'กรุณาระบุรหัสครุภัณฑ์', 'warning');
                return;
            }

            const formData = new FormData();
            if(window.currentEditId) formData.append('id', window.currentEditId);
            
            // ข้อมูล Text
            formData.append('asset_id', assetId);
            formData.append('machine_name', document.getElementById('machine_name_input').value);
            formData.append('machine_type', document.getElementById('machine_type_input').value);
            formData.append('serial', document.getElementById('serial_number').value);
            formData.append('brand', document.getElementById('brand_input').value);
            formData.append('model', document.getElementById('model_input').value);
            formData.append('status', document.getElementById('machine_status').value);
            formData.append('warranty', document.getElementById('warranty_date').value);
            formData.append('vendor', document.getElementById('vendor_company').value);
            formData.append('remark', document.getElementById('remark_input').value);
            formData.append('ag_id', typeof AG_ID !== 'undefined' ? AG_ID : '');
            formData.append('user_id', typeof USER_ID !== 'undefined' ? USER_ID : '');

            // ข้อมูล Location (IDs)
            formData.append('area_id', document.getElementById('in_building').value || ''); 
            formData.append('ac_id', document.getElementById('in_floor').value || '');
            formData.append('ar_id', document.getElementById('in_room').value || '');
            

            // ไฟล์รูปภาพ (loop 1-3)
            for (let i = 1; i <= 3; i++) {
                const fileInput = document.getElementById(`file_img_${i}`);
                const preview = document.getElementById(`preview_${i}`);
                
                if (fileInput && fileInput.files[0]) {
                    formData.append(`file_upload_${i}`, fileInput.files[0]);
                    formData.append(`img_status_${i}`, 'upload'); 
                } 
                else if (preview.classList.contains('hidden')) {
                    formData.append(`img_status_${i}`, 'delete');
                } 
                else {
                    formData.append(`img_status_${i}`, 'keep');
                }
            }

            try {
                Swal.fire({ title: 'กำลังบันทึก...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

                const response = await fetch('handle_machine_info.php?action=save', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    Swal.fire({ icon: 'success', title: result.message, timer: 1500 });
                    closeDrawer();
                    if (typeof loadGridData === "function") loadGridData();
                    window.currentEditId = null;
                } else {
                    Swal.fire('ไม่สำเร็จ', result.error, 'error');
                }
            } catch (error) {
                Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        }

        $(document).ready(function() {
            LocationSelector.init();
            loadMachineTypes();
        });
    </script>
</body>
</html>