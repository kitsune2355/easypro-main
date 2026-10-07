<?php 
//pm_worksheet.php
@session_start();
include "config_ctrl/checksession.php"; 

$plan_id = isset($_GET['plan_id']) ? $_GET['plan_id'] : '';
$mode = isset($_GET['mode']) ? $_GET['mode'] : '';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบงานบำรุงรักษาเชิงป้องกัน (PM Worksheet)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#006b9f',
                        secondary: '#004a6f',
                    },
                }
            }
        }
    </script>
    <link rel="icon" type="image/png" href="../es/logo - easypro2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="js/img-carousel.js"></script>
    
    <style>
        body { 
            font-family: 'Kanit', sans-serif; 
            background-color: #f1f5f9; 
        }
        
        .custom-file-upload {
            @apply border border-dashed border-slate-300 inline-block p-2 cursor-pointer rounded-lg bg-slate-50 text-center w-full transition-all hover:border-primary hover:bg-blue-50;
        }

        details > summary { list-style: none; }
        details > summary::-webkit-details-marker { display: none; }
        
        input[type="radio"]:checked + span.radio-label {
            @apply font-bold;
        }
        
        /* สไตล์สำหรับ Canvas ลายเซ็น */
        .signature-canvas {
            width: 100%;
            height: 150px;
            border-radius: 0.5rem;
            cursor: crosshair;
        }

        /* ซ่อน radio จริงๆ แล้วใช้ custom style สำหรับการประเมิน */
        .eval-radio:checked + label {
            @apply bg-primary text-white border-primary;
        }
		.field-error {
			border-color: #ef4444 !important;
			box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12) !important;
		}
		
		.item-error-summary {
			border: 1px solid #fecaca !important;
			background: #fef2f2 !important;
		}
		
		.item-error-panel {
			border-color: #fca5a5 !important;
		}
		.pm-swal-enterprise {
			border-radius: 22px !important;
			padding: 1.1rem !important;
			box-shadow:
				0 10px 25px rgba(15, 23, 42, 0.10),
				0 4px 10px rgba(15, 23, 42, 0.06) !important;
		}
		
		.pm-swal-title {
			font-family: 'Kanit', sans-serif !important;
			font-size: 1.35rem !important;
			font-weight: 700 !important;
			color: #0f172a !important;
			padding-bottom: 0.35rem !important;
		}
		
		.pm-swal-html {
			margin-top: 0.25rem !important;
			margin-bottom: 0.5rem !important;
		}
		
		.swal2-styled.swal2-confirm {
			border-radius: 12px !important;
			font-weight: 600 !important;
			padding: 0.72rem 1.4rem !important;
			box-shadow: 0 4px 10px rgba(0, 107, 159, 0.18) !important;
		}
		.pm-validation-scroll::-webkit-scrollbar {
			width: 8px;
		}
		
		.pm-validation-scroll::-webkit-scrollbar-track {
			background: #f1f5f9;
			border-radius: 999px;
		}
		
		.pm-validation-scroll::-webkit-scrollbar-thumb {
			background: #cbd5e1;
			border-radius: 999px;
		}
		
		.pm-validation-scroll::-webkit-scrollbar-thumb:hover {
			background: #94a3b8;
		}
    </style>
</head>
<body class="text-slate-700 pb-20">

    <nav class="bg-primary text-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <h1 class="text-xl font-semibold flex items-center gap-2 truncate">
                <i class="fa-solid fa-clipboard-check flex-shrink-0"></i> 
                <span class="truncate">บันทึกผลการบำรุงรักษา (PM Worksheet)</span>
            </h1>
			<button type="button"
					onclick="openPmEventMatrixReport()"
					class="bg-white text-primary px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-100 transition flex items-center gap-2">
				<i class="fa-solid fa-table-cells-large"></i>
				<span>รายงาน PM ตามรอบงาน</span>
			</button>
			<button type="button"
					onclick="openPmMonthlyReport()"
					class="bg-white text-primary px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-100 transition flex items-center gap-2">
				<i class="fa-solid fa-table"></i>
				<span>รายงาน PM รายเดือน</span>
			</button>
			
		<?php if (!empty($mode)): ?>
		<button type="button"
				onclick="openPmPrintPage()"
				class="bg-white text-primary px-4 py-2 rounded-lg text-sm font-semibold hover:bg-slate-100 transition flex items-center gap-2">
			<i class="fa-solid fa-print"></i>
			<span>พิมพ์ใบงาน PM</span>
		</button>
        <?php endif; ?>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6 space-y-6" id="app-container" style="display: none;">
        
        <form id="pmForm" onSubmit="event.preventDefault();">
            <input type="hidden" id="plan_id" name="plan_id" value="<?php echo htmlspecialchars($plan_id); ?>">

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
                <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-info text-primary text-lg"></i>
                    <h2 class="text-lg font-semibold text-slate-800">ข้อมูลการปฏิบัติงาน</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-5">
                    <div><label class="block text-xs text-slate-500 font-medium uppercase tracking-wider mb-1">เลขที่เอกสาร</label><div id="info-doc-no" class="font-semibold text-slate-900">-</div></div>
                    <div><label class="block text-xs text-slate-500 font-medium uppercase tracking-wider mb-1">Revision No</label><div id="info-rev-no" class="font-semibold text-slate-900">-</div></div>
                    <div class="lg:col-span-2"><label class="block text-xs text-slate-500 font-medium uppercase tracking-wider mb-1">ชื่อเช็คชีต</label><div id="info-checksheet-name" class="font-semibold text-slate-900">-</div></div>
                    <div class="lg:col-span-2"><label class="block text-xs text-slate-500 font-medium uppercase tracking-wider mb-1">ชื่อเครื่องจักร</label><div id="info-machine-name" class="font-semibold text-primary text-base">-</div></div>
					<div><label class="block text-xs text-slate-500 font-medium uppercase tracking-wider mb-1">ซีเรียล (Serial Number)</label><div id="info-machine-sn" class="font-semibold text-slate-900">-</div></div>
                    <div><label class="block text-xs text-slate-500 font-medium uppercase tracking-wider mb-1">ประเภทเครื่องจักร</label><div id="info-machine-type" class="font-semibold text-slate-900">-</div></div>
                    <div><label class="block text-xs text-slate-500 font-medium uppercase tracking-wider mb-1">สถานที่ตั้งเครื่องจักร</label><div id="info-location" class="font-semibold text-slate-900">-</div></div>
                    <div><label class="block text-xs text-slate-500 font-medium uppercase tracking-wider mb-1">วันที่ตามกำหนดการ</label><div id="info-plan-date" class="font-semibold text-amber-700 flex items-center gap-1.5"><i class="fa-solid fa-calendar-day"></i><span>-</span></div></div>
                    <div><label class="block text-xs text-slate-500 font-medium uppercase tracking-wider mb-1">วันที่ดำเนินการจริง</label><div id="info-actual-date" class="font-semibold text-green-700 flex items-center gap-1.5"><i class="fa-solid fa-calendar-check"></i><span><?php echo date('Y-m-d'); ?></span></div></div>
                </div>
            </div>
			
			<div id="checksheet-files-section" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6 hidden">
				<div class="bg-slate-50 border-b border-slate-200 px-6 py-4 flex items-center gap-2.5">
					<i class="fa-solid fa-paperclip text-primary text-lg"></i>
					<h2 class="text-lg font-semibold text-slate-800">ไฟล์แนบประกอบเช็คชีต</h2>
				</div>
			
				<div class="p-6 space-y-8">
					<!-- รูปภาพ -->
					<div id="checksheet-images-block" class="hidden">
						<h3 class="text-base font-semibold text-slate-800 mb-3">ภาพจุดตรวจสอบ</h3>
						<div id="checksheet-images-list" class="flex flex-wrap gap-3"></div>
					</div>
			
					<!-- เอกสาร -->
					<div id="checksheet-docs-block" class="hidden">
						<h3 class="text-base font-semibold text-slate-800 mb-3">คำแนะนำในการทำงาน</h3>
						<div id="checksheet-docs-list" class="flex flex-wrap gap-3"></div>
					</div>
				</div>
			</div>
			
            <div id="spares-container" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6 hidden">
                <div class="bg-amber-50 border-b border-amber-200 px-6 py-4 flex items-center gap-2.5">
                    <i class="fa-solid fa-screwdriver-wrench text-amber-700 text-lg"></i>
                    <h2 class="text-lg font-semibold text-amber-900">รายการอะไหล่ที่ต้องเตรียม</h2>
                </div>
                <div class="p-0 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-amber-100/50 text-amber-900 border-b border-amber-200">
                            <tr>
                                <th class="py-3 px-6 text-center w-16">ลำดับ</th>
                                <th class="py-3 px-6 text-left">ชื่ออะไหล่ / รหัสอะไหล่</th>
                                <th class="py-3 px-6 text-center w-32">จำนวนที่ใช้</th>
                            </tr>
                        </thead>
                        <tbody id="spares-list" class="divide-y divide-amber-100"></tbody>
                    </table>
                </div>
            </div>

            <!-- *** รายการอะไหล่ที่ใช้จริง (Actual Spares Used) *** -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
                <div class="bg-emerald-50 border-b border-emerald-200 px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid fa-check-double text-emerald-700 text-lg"></i>
                        <h2 class="text-lg font-semibold text-emerald-900">รายการอะไหล่/วัสดุที่ใช้จริง</h2>
                    </div>
                </div>
                <div class="p-6">
                    <?php include "stock_function.php"; ?>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
                <div class="bg-slate-50 border-b border-slate-200 px-6 py-4 flex items-center gap-2.5">
                    <i class="fa-solid fa-list-check text-primary text-lg"></i>
                    <h2 class="text-lg font-semibold text-slate-800">รายการจุดตรวจสอบ <span class="text-sm font-normal text-slate-500 ml-2">(คลิกเพื่อกางรายละเอียด)</span></h2>
                </div>
                <div id="items-list" class="p-4 sm:p-6 bg-slate-50/50"></div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
                <h3 class="text-base font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100"><i class="fa-solid fa-user-gear text-primary mr-2"></i>ส่วนของผู้ปฏิบัติงาน / ผู้ตรวจสอบ</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-6">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1.5">
                                <i class="fa-solid fa-user-check text-slate-500"></i>
                                <span>ชื่อผู้ตรวจสอบ <span class="text-red-500">*</span></span>
                            </label>
                            <div class="relative custom-select-container">
                                <div id="inspector_display" class="w-full border border-slate-300 rounded-lg px-3 py-2 pr-8 bg-white cursor-pointer flex flex-wrap gap-1 min-h-[42px] items-center shadow-sm" onClick="toggleDropdown('inspector_dropdown')">
                                    <span class="text-slate-400 text-sm">คลิกเพื่อเลือกชื่อ...</span>
                                </div>
                                
                                <button type="button" id="inspector_clear_btn" onClick="clearSelection('inspector', event)" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-500 focus:outline-none transition-colors">
                                    <i class="fa-solid fa-circle-xmark"></i>
                                </button>

                                <div id="inspector_dropdown" class="hidden absolute z-[9999] w-full mt-1 bg-white border border-slate-300 rounded-lg shadow-lg max-h-60 overflow-y-auto p-2">
                                    </div>
                                <input type="hidden" id="inspector_name" name="inspector_name" required>
                                <input type="hidden" id="inspector_users_json" name="inspector_users_json">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1.5">
                                <i class="fa-solid fa-comment-dots text-slate-500"></i>
                                <span>หมายเหตุ / ข้อเสนอแนะเพิ่มเติม</span>
                            </label>
                            <textarea id="remarks" name="remarks" rows="3" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none text-sm transition" placeholder="ระบุรายละเอียดเพิ่มเติม (ถ้ามี)"></textarea>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1.5">
                            <i class="fa-solid fa-signature text-slate-500"></i>
                            <span>ลายมือชื่อผู้ตรวจสอบ <span class="text-red-500">*</span></span>
                        </label>
                        <div class="border border-slate-300 rounded-lg overflow-hidden bg-white shadow-sm relative group">
                            <canvas id="inspector-signature-pad" class="signature-canvas touch-none"></canvas>
                            <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-40 group-hover:opacity-10 transition-opacity" id="inspector-signature-placeholder">
                                <span class="text-slate-400 font-medium"><i class="fa-solid fa-pen mr-1"></i> เซ็นชื่อที่นี่</span>
                            </div>
                            <div class="absolute top-2 right-2">
                                <button type="button" id="clear-inspector-signature" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-md text-xs font-medium border border-slate-200 transition-colors shadow-sm" title="ล้างลายเซ็น">
                                    <i class="fa-solid fa-eraser"></i> ล้าง
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="evaluation_section" class="hidden mt-8 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="bg-blue-50 border-b border-blue-100 px-6 py-4 flex flex-col md:flex-row items-start md:items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <i class="fa-solid fa-star-half-stroke text-primary text-lg"></i>
                        <h2 class="text-lg font-semibold text-slate-800">การประเมินผลการปฏิบัติงาน <span class="text-sm font-normal text-slate-500 ml-1">(สำหรับผู้ประเมิน)</span></h2>
                    </div>
                    <div class="flex justify-end items-center gap-3 p-2 bg-white rounded-lg border border-slate-200 shadow-sm">
                        <span class="text-base font-medium text-slate-700">คะแนนประเมินเฉลี่ย :</span>
                        <span id="display_average_score" class="text-2xl font-bold text-primary">0.00</span>
                        <span class="text-sm text-slate-500">/ 5.00</span>
                        <input type="hidden" id="eval_average_score" name="eval_average_score" value="0">
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="bg-slate-50/50 rounded-xl border border-slate-200 p-5 mb-6">
                        <p class="text-sm text-slate-500 mb-4"><i class="fa-solid fa-circle-info mr-1"></i> เกณฑ์การประเมิน: 5 = ดีมาก, 4 = ดี, 3 = ปานกลาง, 2 = พอใช้, 1 = ควรปรับปรุง</p>
                        
                        <div id="eval-topics-container" class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-4">
                            <div class="col-span-1 lg:col-span-2 text-center p-4 text-slate-500">
                                <i class="fas fa-spinner fa-spin mr-2"></i> กำลังโหลดข้อมูลการประเมิน...
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1.5">
                                    <i class="fa-solid fa-comment-dots text-slate-500"></i>
                                    <span>ข้อเสนอแนะจากการประเมิน</span>
                                </label>
                                <textarea id="eval_comments" name="eval_comments" rows="2" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none text-sm transition" placeholder="ระบุข้อเสนอแนะเพิ่มเติมเพื่อการปรับปรุง..."></textarea>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1.5">
                                        <i class="fa-solid fa-user-tie text-slate-500"></i>
                                        <span>ชื่อผู้ประเมิน <span class="text-red-500">*</span></span>
                                    </label>
                                    <div class="relative custom-select-container">
                                        <div id="evaluator_display" class="w-full border border-slate-300 rounded-lg px-3 py-2 pr-8 bg-white cursor-pointer flex flex-wrap gap-1 min-h-[42px] items-center shadow-sm" onClick="toggleDropdown('evaluator_dropdown')">
                                            <span class="text-slate-400 text-sm">คลิกเพื่อเลือกชื่อ...</span>
                                        </div>

                                        <button type="button" id="evaluator_clear_btn" onClick="clearSelection('evaluator', event)" class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-500 focus:outline-none transition-colors">
                                            <i class="fa-solid fa-circle-xmark"></i>
                                        </button>

                                        <div id="evaluator_dropdown" class="hidden absolute z-[9999] bottom-full mb-1 w-full bg-white border border-slate-300 rounded-lg shadow-lg max-h-60 overflow-y-auto p-2">
                                            </div>
                                        <input type="hidden" id="evaluator_name" name="evaluator_name" required>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1.5">
                                        <i class="fa-solid fa-calendar-check text-slate-500"></i>
                                        <span>วันที่ประเมินผล <span class="text-red-500">*</span></span>
                                    </label>
                                    <input type="date" id="eval_date" name="eval_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none text-sm transition">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2 flex items-center gap-1.5">
                                <i class="fa-solid fa-signature text-slate-500"></i>
                                <span>ลายมือชื่อผู้ประเมิน <span class="text-red-500">*</span></span>
                            </label>
                            <div class="border border-slate-300 rounded-lg overflow-hidden bg-white shadow-sm relative group">
                                <canvas id="evaluator-signature-pad" class="signature-canvas touch-none"></canvas>
                                <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-40 group-hover:opacity-10 transition-opacity" id="evaluator-signature-placeholder">
                                    <span class="text-slate-400 font-medium"><i class="fa-solid fa-pen mr-1"></i> เซ็นชื่อผู้ประเมินที่นี่</span>
                                </div>
                                <div class="absolute top-2 right-2">
                                    <button type="button" id="clear-evaluator-signature" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-md text-xs font-medium border border-slate-200 transition-colors shadow-sm" title="ล้างลายเซ็น">
                                        <i class="fa-solid fa-eraser"></i> ล้าง
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 flex flex-col sm:flex-row justify-end gap-3 border-t border-slate-100 pt-6">
                <button type="button" onClick="window.close()" class="w-full sm:w-auto px-6 py-2.5 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-200 font-medium transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-xmark"></i>
                    <span>ยกเลิก</span>
                </button>
                <button type="button" id="btn_save_work" onClick="saveWorkRecord()" class="w-full sm:w-auto px-8 py-2.5 bg-primary text-white rounded-lg hover:bg-primary/90 font-semibold shadow transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>บันทึกผลการปฏิบัติงาน</span>
                </button>
                <button type="button" id="btn_save_eval" onClick="saveEvaluation()" class="hidden w-full sm:w-auto px-8 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-star"></i>
                    <span>บันทึกการประเมิน</span>
                </button>
            </div>

        </form>
    </div>

    <div id="loading" class="fixed inset-0 bg-white/90 backdrop-blur-sm z-50 flex flex-col items-center justify-center transition-all duration-300">
        <i class="fa-solid fa-circle-notch fa-spin text-5xl text-primary mb-4"></i>
        <p class="text-slate-600 font-medium text-lg">กำลังโหลดข้อมูล...</p>
    </div>

    <script>
        window.AG_ID = '<?php echo isset($sess_user_agency_es) ? $sess_user_agency_es : ""; ?>';
        window.USER_ID = '<?php echo isset($sess_user_id) ? $sess_user_id : ""; ?>';

        const urlParams = new URLSearchParams(window.location.search);
		const planId = urlParams.get('plan_id') || document.getElementById('plan_id').value;
		
		const pageMode = urlParams.get('mode') || '<?php echo htmlspecialchars($mode, ENT_QUOTES, 'UTF-8'); ?>';
		const isHistoryMode = pageMode === 'history';
        let inspectorPad, evaluatorPad;
        let totalEvalTopics = 0;
        let usersData = [];
		let worksheetItems = [];
		window.latestValidationErrors = [];
		
		
        async function loadUsers() {
            try {
                const response = await axios.get(`handle_pm_worksheet.php?action=get_users&ag_id=${AG_ID}`);
                if (response.data.success) {
                    const allUsers = response.data.data;

                    // ----------------------------------------------------
                    // กรองสำหรับผู้ตรวจสอบ: ต้องการ user_department 3 และ 4
                    // (เนื่องจาก Backend Query ดึงมาเฉพาะ 3 กับ 4 ให้แล้ว จึงใช้ทั้งหมดได้เลย)
                    const inspectorUsers = allUsers; 

                    // กรองสำหรับผู้ประเมิน: ต้องการเฉพาะ user_department 4 เท่านั้น
                    const evaluatorUsers = allUsers.filter(user => user.user_department == 4 || user.user_department == '4');
                    // ----------------------------------------------------

                    // ส่ง Array ที่แยกกันไปสร้าง Checkbox ในแต่ละ Dropdown
                    renderCheckboxes('inspector_dropdown', 'inspector', inspectorUsers);
                    renderCheckboxes('evaluator_dropdown', 'evaluator', evaluatorUsers);
                }
            } catch (error) {
                console.error('Error fetching users:', error);
            }
        }
		
		function formatThaiDate(dateString) {
			if (!dateString) return '-';
		
			const date = new Date(dateString);
			if (isNaN(date.getTime())) return dateString || '-';
		
			return date.toLocaleDateString('th-TH', {
				year: 'numeric',
				month: 'long',
				day: 'numeric'
			});
		}

        // 2. ฟังก์ชันสร้าง Checkbox เข้าไปใน Dropdown
        // เก็บทั้งชื่อสำหรับแสดงผล และ user_id สำหรับบันทึกลงตารางลูก
        function renderCheckboxes(containerId, type, usersList) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
            
            if (usersList.length === 0) {
                container.innerHTML = '<div class="p-2 text-sm text-slate-400 text-center">ไม่พบรายชื่อ</div>';
                return;
            }
            
            usersList.forEach(user => {
                const userId = String(user.user_id || '');       // เช่น i-2, i-3
                const userPkId = String(user.id || '');          // primary key ใน tb_user เช่น 8, 9
                const name = `${user.user_name || ''} ${user.user_fname || ''}`.trim();
                const safeName = String(name).replace(/"/g, '&quot;');
                const safeUserId = String(userId).replace(/"/g, '&quot;');
                const safeUserPkId = String(userPkId).replace(/"/g, '&quot;');
                
                const html = `
                    <label class="flex items-center gap-2 p-2 hover:bg-slate-50 rounded cursor-pointer">
                        <input 
                            type="checkbox"
                            value="${safeUserId}"
                            data-user-id="${safeUserId}"
                            data-user-pk-id="${safeUserPkId}"
                            data-user-name="${safeName}"
                            class="${type}-checkbox w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary"
                            onchange="updateSelection('${type}')"
                        >
                        <span class="text-sm text-slate-700">${name}</span>
                    </label>
                `;
                container.insertAdjacentHTML('beforeend', html);
            });
        }

        // 3. ฟังก์ชันเปิด/ปิด Dropdown
        function toggleDropdown(dropdownId) {
            // ปิด dropdown อื่นๆ ก่อนเปิดอันใหม่
            document.getElementById('inspector_dropdown').classList.add('hidden');
            document.getElementById('evaluator_dropdown').classList.add('hidden');
            
            // เปิดตัวที่เลือก
            document.getElementById(dropdownId).classList.remove('hidden');
        }

        function clearSelection(type, event) {
            // ป้องกันไม่ให้การคลิกปุ่มไปทำให้ Dropdown เปิด (Stop Event Bubbling)
            if (event) {
                event.stopPropagation();
            }
            
            // เอาติ๊กถูกออกจาก Checkbox ทั้งหมดของกลุ่มนั้น
            const checkboxes = document.querySelectorAll(`.${type}-checkbox`);
            checkboxes.forEach(cb => cb.checked = false);
            
            // เรียกใช้ฟังก์ชันอัปเดตหน้าจอ
            updateSelection(type);
        }

        // 4. ฟังก์ชันอัปเดตหน้าจอเมื่อมีการติ๊ก Checkbox
        // type = inspector หรือ evaluator
        // สำหรับ inspector จะเติม inspector_name และ inspector_users_json
        function updateSelection(type) {
            const checkboxes = document.querySelectorAll(`.${type}-checkbox:checked`);

            const selectedUsers = Array.from(checkboxes).map(cb => ({
                user_id: cb.dataset.userId || cb.value,
                user_pk_id: cb.dataset.userPkId || '',
                name: cb.dataset.userName || cb.value
            }));

            const selectedNames = selectedUsers.map(user => user.name);
            
            const display = document.getElementById(`${type}_display`);
            const hiddenNameInput = document.getElementById(`${type}_name`);
            const hiddenUsersJsonInput = document.getElementById(`${type}_users_json`);
            const clearBtn = document.getElementById(`${type}_clear_btn`);
            
            if (selectedNames.length > 0) {
                display.innerHTML = selectedNames.map(name => 
                    `<span class="bg-blue-100 text-blue-800 text-xs px-2.5 py-1 rounded-full border border-blue-200">${name}</span>`
                ).join('');
                
                if (clearBtn) clearBtn.classList.remove('hidden');
            } else {
                display.innerHTML = `<span class="text-slate-400 text-sm">คลิกเพื่อเลือกชื่อ...</span>`;
                
                if (clearBtn) clearBtn.classList.add('hidden');
            }
            
            if (hiddenNameInput) {
                hiddenNameInput.value = selectedNames.join(', ');
            }

            if (hiddenUsersJsonInput) {
                hiddenUsersJsonInput.value = JSON.stringify(selectedUsers);
            }
        }

        async function loadEvalTopics() {
            try {
                const response = await axios.get(`handle_pm_feedback.php?action=get_eval_topics&ag_id=${AG_ID}`);
                
                if (response.data.success) {
                    const topics = response.data.data;
                    const container = document.getElementById('eval-topics-container');
                    let html = '';
                    
                    // +++ เก็บจำนวนหัวข้อทั้งหมดเพื่อใช้เป็นตัวหาร +++
                    totalEvalTopics = topics.length; 
                    
                    if (topics.length > 0) {
                        topics.forEach((topic, index) => {
                            // ... (โค้ดสร้าง HTML เหมือนในคำตอบข้อที่แล้ว) ...
                            let radiosHtml = '';
                            for(let i=1; i<=5; i++) {
                                radiosHtml += `
                                <div class="relative">
                                    <input type="radio" name="${topic.topic_title}" value="${i}" id="${topic.topic_title}_${i}" class="peer hidden eval-radio-input" required>
                                    <label for="${topic.topic_title}_${i}" class="w-8 h-8 flex items-center justify-center rounded-md border border-slate-200 text-sm font-medium text-slate-500 cursor-pointer transition-all hover:bg-slate-100 peer-checked:bg-primary peer-checked:text-white peer-checked:border-primary shadow-sm">
                                        ${i}
                                    </label>
                                </div>
                                `;
                            }

                            html += `
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 bg-white rounded-lg border border-slate-100 shadow-sm hover:border-blue-200 transition-colors">
                                <label class="text-sm font-medium text-slate-700 w-full sm:w-1/2 flex items-start gap-2">
                                    <span class="text-slate-400 font-normal">${index + 1}.</span> ${topic.topic_title}
                                </label>
                                <div class="flex gap-1.5 w-full sm:w-auto justify-end">
                                    ${radiosHtml}
                                </div>
                            </div>
                            `;
                        });
                    } else {
                        html = `<div class="col-span-1 lg:col-span-2 p-4 text-center text-red-500">ไม่พบข้อมูลหัวข้อการประเมินในระบบ</div>`;
                    }
                    
                    container.innerHTML = html;
                    
                    // +++ เรียกฟังก์ชันผูก Event ให้ Radio ทุกตัว +++
                    attachCalculateScoreEvent(); 
                    
                }
            } catch (error) {
                // ... โค้ดจัดการ Error ...
            }
        }

        // ==========================================
        // ฟังก์ชันใหม่สำหรับการคำนวณคะแนน
        // ==========================================

        function attachCalculateScoreEvent() {
            // ดึง Radio ทั้งหมดที่มีคลาส eval-radio-input
            const radios = document.querySelectorAll('.eval-radio-input');
            
            // ผูก Event 'change' เพื่อให้คำนวณใหม่ทุกครั้งที่ผู้ใช้คลิกเปลี่ยนตัวเลือก
            radios.forEach(radio => {
                radio.addEventListener('change', calculateAverageScore);
            });
        }

        function calculateAverageScore() {
            // หา Radio ทั้งหมดที่ถูกคลิกเลือกแล้ว (checked)
            const selectedRadios = document.querySelectorAll('.eval-radio-input:checked');
            
            if (selectedRadios.length === 0 || totalEvalTopics === 0) {
                updateScoreUI(0);
                return;
            }

            // รวมคะแนนจากข้อที่เลือก
            let sum = 0;
            selectedRadios.forEach(radio => {
                sum += parseFloat(radio.value);
            });

            // คำนวณค่าเฉลี่ย (รวมคะแนน หารด้วย จำนวนหัวข้อทั้งหมด)
            let average = sum / totalEvalTopics; 
            
            updateScoreUI(average);
        }

        function updateScoreUI(score) {
            // แปลงให้เป็นทศนิยม 2 ตำแหน่ง
            const formattedScore = score.toFixed(2);
            
            // แสดงผลบนหน้าเว็บ
            document.getElementById('display_average_score').innerText = formattedScore;
            
            // ใส่ค่าลงใน Input ซ่อนเตรียมส่งไป Backend
            document.getElementById('eval_average_score').value = formattedScore;
        }

        // ฟังก์ชันคำนวณคะแนนเฉลี่ยอัตโนมัติ
        function initEvaluationCalculator() {
            const evalInputs = document.querySelectorAll('.eval-radio-input');
            const displayScore = document.getElementById('average-score-display');
            const hiddenScore = document.getElementById('eval_average_score');
            
            evalInputs.forEach(input => {
                input.addEventListener('change', () => {
                    let total = 0;
                    let count = 0;
                    
                    // หาเฉพาะ input ที่ถูก checked
                    document.querySelectorAll('.eval-radio-input:checked').forEach(checkedInput => {
                        total += parseInt(checkedInput.value);
                        count++;
                    });
                    
                    if(count > 0) {
                        const avg = (total / count).toFixed(2);
                        displayScore.textContent = avg;
                        hiddenScore.value = avg;
                    }
                });
            });
        }

        // ฟังก์ชันตั้งค่า Signature Pads ทั้ง 2 ตัว
        function initSignaturePads() {
            // 1. Inspector Pad
            const canvasInsp = document.getElementById('inspector-signature-pad');
            const placeholderInsp = document.getElementById('inspector-signature-placeholder');
            
            // 2. Evaluator Pad
            const canvasEval = document.getElementById('evaluator-signature-pad');
            const placeholderEval = document.getElementById('evaluator-signature-placeholder');
            
            function resizeCanvas(canvas) {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);
            }

            function resizeAll() {
                resizeCanvas(canvasInsp);
                if (inspectorPad) inspectorPad.clear();
                
                resizeCanvas(canvasEval);
                if (evaluatorPad) evaluatorPad.clear();
            }

            window.addEventListener("resize", resizeAll);
            resizeCanvas(canvasInsp);
            resizeCanvas(canvasEval);

            // Init Inspector
            inspectorPad = new SignaturePad(canvasInsp, { penColor: "rgb(15, 23, 42)", backgroundColor: "rgba(255, 255, 255, 0)" });
            inspectorPad.addEventListener("beginStroke", () => placeholderInsp.style.display = 'none');
            document.getElementById('clear-inspector-signature').addEventListener('click', () => {
                inspectorPad.clear(); placeholderInsp.style.display = 'flex';
            });

            // Init Evaluator
            evaluatorPad = new SignaturePad(canvasEval, { penColor: "rgb(15, 23, 42)", backgroundColor: "rgba(255, 255, 255, 0)" });
            evaluatorPad.addEventListener("beginStroke", () => placeholderEval.style.display = 'none');
            document.getElementById('clear-evaluator-signature').addEventListener('click', () => {
                evaluatorPad.clear(); placeholderEval.style.display = 'flex';
            });
        } 

        async function fetchData() {
			try {
				const apiAction = isHistoryMode ? 'get_history' : 'get_data';
		
				const response = await axios.get(
					`handle_pm_worksheet.php?action=${apiAction}&plan_id=${encodeURIComponent(planId)}&ag_id=${encodeURIComponent(AG_ID)}`
				);
		
				const data = response.data;
		
				if (data.success) {
					if (isHistoryMode) {
						renderHistoryMode(data);
					} else {
						renderInfo(data.info);
						renderSpares(data.spares);
		
						worksheetItems = Array.isArray(data.items) ? data.items : [];
						renderItems(worksheetItems);
		
						renderFiles(data.files || []);
					}
		
					const loadingIndicator = document.getElementById('loading');
					loadingIndicator.classList.add('opacity-0', 'pointer-events-none');
					setTimeout(() => { loadingIndicator.style.display = 'none'; }, 300);
		
					document.getElementById('app-container').style.display = 'block';
					window.dispatchEvent(new Event('resize'));
		
				} else {
					Swal.fire({
						icon: 'error',
						title: 'ผิดพลาด',
						text: data.error || 'ไม่สามารถโหลดข้อมูลได้'
					});
				}
		
			} catch (error) {
				console.error(error);
				Swal.fire({
					icon: 'error',
					title: 'ผิดพลาด',
					text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'
				});
			}
		}
		
		function renderFiles(files) {
			const section = document.getElementById('checksheet-files-section');
			const imagesBlock = document.getElementById('checksheet-images-block');
			const docsBlock = document.getElementById('checksheet-docs-block');
			const imagesList = document.getElementById('checksheet-images-list');
			const docsList = document.getElementById('checksheet-docs-list');
		
			if (!section || !imagesBlock || !docsBlock || !imagesList || !docsList) return;
		
			imagesList.innerHTML = '';
			docsList.innerHTML = '';
		
			if (!files || files.length === 0) {
				section.classList.add('hidden');
				imagesBlock.classList.add('hidden');
				docsBlock.classList.add('hidden');
				return;
			}
		
			const imageFiles = files.filter(f => String(f.file_type).toLowerCase() === 'image');
			const docFiles = files.filter(f => String(f.file_type).toLowerCase() !== 'image');
		
			if (imageFiles.length > 0) {
				imagesBlock.classList.remove('hidden');
		
				const imageUrls = imageFiles.map(file => file.file_path);
				const imageUrlsJson = JSON.stringify(imageUrls).replace(/"/g, '&quot;');
		
				imagesList.innerHTML = imageFiles.map((file, index) => `
					<div class="group block w-[88px] cursor-pointer">
						<div class="w-[88px] h-[88px] rounded-xl overflow-hidden border border-slate-200 bg-slate-50 shadow-sm group-hover:shadow-md group-hover:border-primary transition"
							 onclick='ImageCarousel.open(${imageUrlsJson}, ${index})'>
							<img src="${file.file_path}" alt="${file.file_name}"
								 class="w-full h-full object-cover">
						</div>
					</div>
				`).join('');
			} else {
				imagesBlock.classList.add('hidden');
			}
		
			if (docFiles.length > 0) {
				docsBlock.classList.remove('hidden');
		
				docsList.innerHTML = docFiles.map(file => `
					<a href="${file.file_path}" target="_blank"
					   class="inline-flex items-center gap-2 max-w-full px-4 py-2 rounded-full border border-slate-200 bg-slate-100 text-slate-700 hover:bg-slate-200 transition shadow-sm">
						<i class="fa-regular fa-file-lines text-emerald-600"></i>
						<span class="truncate max-w-[220px] text-sm font-medium">${file.file_name}</span>
						<i class="fa-solid fa-arrow-up-right-from-square text-slate-400 text-xs"></i>
					</a>
				`).join('');
			} else {
				docsBlock.classList.add('hidden');
			}
		
			section.classList.remove('hidden');
		}

        // --- ส่วนของ Render ข้อมูลเหมือนเดิม 100% ---
        function renderInfo(info) {
			document.getElementById('info-doc-no').textContent = info.doc_no || '-';
			document.getElementById('info-rev-no').textContent = info.rev_no || '-';
			document.getElementById('info-checksheet-name').textContent = info.checksheet_name || '-';
		
			const machineDisplay = `${info.machine_code ? '[' + info.machine_code + '] ' : ''}${info.machine_name || '-'}`;
			document.getElementById('info-machine-name').textContent = machineDisplay;
		
			document.getElementById('info-machine-sn').textContent = info.machine_sn || '-';
			document.getElementById('info-machine-type').textContent = info.machine_type || '-';
			document.getElementById('info-location').textContent = info.location || '-';
		
			document.getElementById('info-plan-date').querySelector('span').textContent = formatThaiDate(info.plan_date);
		
			const actualDateEl = document.getElementById('info-actual-date')?.querySelector('span');
			if (actualDateEl) {
				actualDateEl.textContent = formatThaiDate(actualDateEl.textContent);
			}
		}

        function renderSpares(spares) {
            if (!spares || spares.length === 0) return;
            const container = document.getElementById('spares-container');
            const tbody = document.getElementById('spares-list');
            container.classList.remove('hidden');
            let html = '';
            spares.forEach((sp, index) => {
                const spareDisplayName = `${sp.part_name || '-'} ${sp.part_no ? '('+sp.part_no+')' : ''}`;
                html += `
                    <tr class="hover:bg-amber-50 transition-colors">
                        <td class="py-3 px-6 text-center text-slate-500 font-medium">${index + 1}</td>
                        <td class="py-3 px-6 font-medium text-slate-900">${spareDisplayName}</td>
                        <td class="py-3 px-6 text-center font-bold text-amber-700 text-base">${sp.quantity || sp.qty || '-'}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        function renderItems(items) {
            const container = document.getElementById('items-list');
            let html = '';

            items.forEach((item, index) => {
                // เงื่อนไข Photo Required (1 = บังคับ, 2 = ไม่บังคับ, 3 = บังคับเฉพาะเมื่อไม่ผ่าน)
                const photoReqId = String(item.photo_required_id);
                const isPhotoRequiredAlways = (photoReqId === '1');
                
                // ฟังก์ชันสร้าง Badge สำหรับเงื่อนไขรูปถ่าย
                let requirePhotoBadge = '';
                let photoLabelText = 'คลิกเพื่ออัปโหลดรูปภาพ';
                
                if (photoReqId === '1') {
                    requirePhotoBadge = '<span class="text-[10px] bg-red-100 text-red-600 px-2 py-0.5 rounded-full font-semibold border border-red-200 flex-shrink-0 whitespace-nowrap"><i class="fa-solid fa-camera mr-1"></i> บังคับถ่ายรูป</span>';
                    photoLabelText = 'คลิกเพื่อถ่ายรูป (บังคับ)';
                } else if (photoReqId === '3') {
                    requirePhotoBadge = '<span class="text-[10px] bg-amber-100 text-amber-600 px-2 py-0.5 rounded-full font-semibold border border-amber-200 flex-shrink-0 whitespace-nowrap"><i class="fa-solid fa-camera mr-1"></i> ถ่ายรูปเมื่อผิดปกติ</span>';
                    photoLabelText = 'คลิกเพื่อถ่ายรูป (เมื่อผิดปกติ)';
                } else {
                    photoLabelText = 'คลิกเพื่ออัปโหลดรูปภาพ (ไม่บังคับ)';
                }
                
                const illustrationHtml = item.reference_image 
                    ? `<div class="mt-2.5"><a href="${item.reference_image}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-primary hover:bg-blue-100 rounded-md text-xs font-medium transition-colors border border-blue-100"><i class="fa-regular fa-image"></i> ดูรูปประกอบมาตรฐาน</a></div>` 
                    : '';

                const isOpen = index === 0 ? 'open' : '';

                // 1. จัดการตัวเลือก Radio ตาม check_type_id
                let radioOptionsHtml = '';
                if (String(item.check_type_id) === '1') {
                    radioOptionsHtml = `
                        <div class="flex gap-3">
                            <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer bg-green-50 text-green-700 px-4 py-3 rounded-lg border border-green-200 hover:bg-green-100 transition group shadow-sm">
                                <input type="radio" name="status_${item.id}" value="Pass" required onchange="handleStatusChange(${item.id}, '${photoReqId}')" class="w-5 h-5 text-green-600 focus:ring-green-500 border-green-300">
                                <span class="font-medium text-sm radio-label">ปกติ (Pass)</span>
                            </label>
                            <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer bg-red-50 text-red-700 px-4 py-3 rounded-lg border border-red-200 hover:bg-red-100 transition group shadow-sm">
                                <input type="radio" name="status_${item.id}" value="Fail" required onchange="handleStatusChange(${item.id}, '${photoReqId}')" class="w-5 h-5 text-red-600 focus:ring-red-500 border-red-300">
                                <span class="font-medium text-sm radio-label">ผิดปกติ (Fail)</span>
                            </label>
                        </div>
                    `;
                } else if (String(item.check_type_id) === '2') {
                    radioOptionsHtml = `
                        <div class="flex flex-col sm:flex-row gap-3">
                            <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer bg-green-50 text-green-700 px-4 py-3 rounded-lg border border-green-200 hover:bg-green-100 transition group shadow-sm">
                                <input type="radio" name="status_${item.id}" value="Pass" required onchange="handleStatusChange(${item.id}, '${photoReqId}')" class="w-5 h-5 text-green-600 focus:ring-green-500 border-green-300">
                                <span class="font-medium text-sm radio-label">ปกติ (Pass)</span>
                            </label>
                            <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer bg-red-50 text-red-700 px-4 py-3 rounded-lg border border-red-200 hover:bg-red-100 transition group shadow-sm">
                                <input type="radio" name="status_${item.id}" value="Fail" required onchange="handleStatusChange(${item.id}, '${photoReqId}')" class="w-5 h-5 text-red-600 focus:ring-red-500 border-red-300">
                                <span class="font-medium text-sm radio-label">ผิดปกติ (Fail)</span>
                            </label>
                            <label class="flex-1 flex items-center justify-center gap-2 cursor-pointer bg-slate-50 text-slate-700 px-4 py-3 rounded-lg border border-slate-200 hover:bg-slate-100 transition group shadow-sm">
                                <input type="radio" name="status_${item.id}" value="N/A" required onchange="handleStatusChange(${item.id}, '${photoReqId}')" class="w-5 h-5 text-slate-600 focus:ring-slate-500 border-slate-300">
                                <span class="font-medium text-sm radio-label">ไม่เกี่ยวข้อง (N/A)</span>
                            </label>
                        </div>
                    `;
                }

                // 2. จัดการกล่องกรอกข้อมูล หากมี measurement_name (ซ่อนหากว่าง)
                let measurementInputHtml = '';
                if (item.measurement_name && item.measurement_name.trim() !== "") {
                    measurementInputHtml = `
                        <div class="mt-4">
                            <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block mb-1.5">ระบุค่า ${item.measurement_name}</span>
                            <div class="flex items-center gap-2">
                                <input type="text" name="val_${item.id}" class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none font-semibold shadow-sm text-slate-800" placeholder="ระบุค่าวัดที่ได้จริง...">
                                ${item.unit ? `<span class="text-sm text-slate-600 font-semibold bg-slate-100 px-4 py-3 rounded-lg border border-slate-200 whitespace-nowrap">${item.unit}</span>` : ''}
                            </div>
                        </div>
                    `;
                } else {
                    measurementInputHtml = `<input type="hidden" name="val_${item.id}" value="-">`;
                }

                html += `
                    <details class="group bg-white rounded-xl border border-slate-200 mb-4 overflow-hidden shadow-sm hover:shadow transition-shadow"
         data-item-id="${item.id}" ${isOpen}>
                        <summary class="flex justify-between items-center font-medium cursor-pointer list-none p-4 sm:p-5 bg-white hover:bg-slate-50 transition-colors">
                            <div class="flex items-center gap-3.5 pr-4 flex-grow">
                                <span class="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-bold text-sm border border-primary/20">${index + 1}</span>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-1.5 sm:gap-3">
                                    <span class="text-slate-800 font-semibold text-base leading-snug">${item.check_point}</span>
                                    ${requirePhotoBadge}
                                </div>
                            </div>
                            <span class="flex-shrink-0 transition-transform duration-300 group-open:-rotate-180 bg-slate-100 rounded-full p-1.5 border border-slate-200 text-slate-500 group-hover:bg-primary group-hover:text-white group-hover:border-primary">
                                <i class="fa-solid fa-chevron-down w-4 h-4 flex items-center justify-center text-sm"></i>
                            </span>
                        </summary>
                        
                        <div class="p-5 border-t border-slate-100 bg-slate-50/30">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div class="bg-white p-5 rounded-xl border border-slate-200 h-full shadow-sm">
                                        <h4 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2 pb-2 border-b border-slate-100"><i class="fa-solid fa-book-open text-primary"></i> ข้อมูลอ้างอิงสำหรับการตรวจสอบ</h4>
                                        <div class="mb-4">
                                            <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block mb-1.5">มาตรฐานการตรวจสอบ</span>
                                            <div class="text-sm text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-100 leading-relaxed">${item.standard_text || '-'}</div>
                                            ${illustrationHtml}
                                        </div>
                                        <div class="mb-4">
                                            <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block mb-1.5">วิธีตรวจสอบ / เครื่องมือที่ใช้</span>
                                            <div class="text-sm text-slate-700 leading-relaxed"><i class="fa-solid fa-wrench text-slate-400 mr-1.5 text-xs"></i> ${item.method_text || '-'}</div>
                                        </div>
                                        <div>
                                            <span class="text-[11px] text-red-500 font-bold uppercase tracking-wider block mb-1.5">ข้อปฏิบัติเมื่อพบความผิดปกติ</span>
                                            <div class="text-sm text-red-700 bg-red-50 p-3 rounded-lg border border-red-100 leading-relaxed"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i> ${item.action_text || item.action_if_abnormal || item.action_abnormal || '-'}</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4 flex flex-col">
                                    <div class="bg-blue-50/50 p-5 rounded-xl border border-blue-100 flex-grow shadow-sm">
                                        <h4 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2 pb-2 border-b border-blue-200/50"><i class="fa-solid fa-pen-to-square text-primary"></i> บันทึกผลการตรวจสอบ</h4>
                                        
                                        ${hasMeasurementConfig(item) ? `
                                        <div class="mb-5 bg-white p-4 rounded-lg border border-slate-200 shadow-sm">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                                
                                                <div class="flex-1">
                                                    <span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block mb-1">ค่าวัด / ค่าที่ต้องการ</span>
                                                    <div class="font-semibold text-slate-800 text-sm mb-1">
														${item.measurement_name || 'ค่าที่ต้องตรวจวัด'}
													</div>
                                                    <div class="flex items-baseline gap-1.5">
                                                        <span class="text-xs text-slate-500">ค่ามาตรฐาน:</span>
                                                        <span class="text-primary font-bold text-base">${item.expected_value || '-'}</span>
                                                        <span class="text-xs text-slate-500 font-medium">${item.unit || ''}</span>
                                                    </div>
                                                </div>

                                                <div class="flex-1 w-full sm:w-auto">
                                                    <span class="text-[11px] text-primary font-bold uppercase tracking-wider block mb-1">ระบุค่าที่วัดได้จริง</span>
                                                    <div class="flex items-center gap-2">
                                                        <input type="text" name="val_${item.id}" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary outline-none font-semibold shadow-sm text-slate-800 text-sm" placeholder="ระบุค่าที่วัดได้จริง...">
                                                        ${item.unit ? `<span class="text-sm text-slate-600 font-semibold bg-slate-50 px-3 py-2 rounded-lg border border-slate-200 whitespace-nowrap">${item.unit}</span>` : ''}
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                        ` : `<input type="hidden" name="val_${item.id}" value="-">`}

                                        <div class="mb-5">
                                            <span class="text-sm font-bold text-slate-800 block mb-2.5">ระบุผลการประเมิน <span class="text-red-500">*</span></span>
                                            <input type="hidden" name="item_id[]" value="${item.id}">
                                            
                                            ${radioOptionsHtml}

                                        </div>

                                        <div>
                                            <span class="text-sm font-bold text-slate-800 block mb-2.5">รูปภาพหลักฐาน ${isPhotoRequiredAlways ? '<span class="text-red-500" id="req_star_'+item.id+'">*</span>' : '<span class="text-xs font-normal text-slate-500 ml-1" id="req_star_'+item.id+'">(ถ้ามี)</span>'}</span>
                                            
                                            <label class="custom-file-upload group bg-white shadow-sm relative overflow-hidden min-h-[100px] flex items-center justify-center p-2" id="photo_container_${item.id}">
                                                <input type="file" id="photo_input_${item.id}" accept="image/*" class="hidden" onchange="previewImage(this, ${item.id})" ${isPhotoRequiredAlways ? 'required' : ''}>
                                                
                                                <div id="upload_ui_${item.id}" class="py-3 flex flex-col items-center justify-center w-full">
                                                    <i class="fa-solid fa-cloud-arrow-up text-slate-300 mb-2 block text-3xl "></i>
                                                    <span class="text-sm text-slate-500 block truncate px-2 font-medium transition-colors" id="photo_label_${item.id}">
                                                        ${photoLabelText}
                                                    </span>
                                                </div>

                                                <div id="preview_ui_${item.id}" class="hidden w-full relative group/preview">
                                                    <img id="img_preview_${item.id}" src="" class="max-h-48 mx-auto object-contain rounded" />
                                                    
                                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover/preview:opacity-100 transition-opacity flex items-center justify-center rounded cursor-pointer">
                                                        <span class="text-white text-sm font-medium bg-black/60 px-3 py-1.5 rounded-lg backdrop-blur-sm shadow-sm">
                                                            <i class="fa-solid fa-pen mr-1"></i> คลิกเพื่อเปลี่ยนรูป
                                                        </span>
                                                    </div>
                                                </div>
                                            </label>
                                            
                                            <input type="hidden" id="photo_base64_${item.id}" name="photo_${item.id}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </details>
                `;
            });

            container.innerHTML = html;
        }

        function handleStatusChange(itemId, photoRequiredId) {
            if (photoRequiredId === '3') {
                const statusInputs = document.getElementsByName(`status_${itemId}`);
                const photoInput = document.getElementById(`photo_input_${itemId}`);
                const photoLabel = document.getElementById(`photo_label_${itemId}`);
                const reqStar = document.getElementById(`req_star_${itemId}`);
                
                let isFailChecked = false;
                for (let radio of statusInputs) {
                    if (radio.checked && radio.value === 'Fail') {
                        isFailChecked = true;
                        break;
                    }
                }

                // ถ้าเลือก 'ผิดปกติ' ให้บังคับถ่ายรูป
                if (isFailChecked) {
                    photoInput.setAttribute('required', 'required');
                    reqStar.className = "text-red-500";
                    reqStar.innerText = "*";
                    if(!photoInput.files || photoInput.files.length === 0) {
                    photoLabel.textContent = 'คลิกเพื่อถ่ายรูป (บังคับเมื่อผิดปกติ)';
                    photoLabel.classList.add('text-red-500');
                    }
                } else {
                    // ถ้าเลือกผ่าน หรือ ไม่เกี่ยวข้อง เอาบังคับออก
                    photoInput.removeAttribute('required');
                    reqStar.className = "text-xs font-normal text-slate-500 ml-1";
                    reqStar.innerText = "(ถ้ามี)";
                    if(!photoInput.files || photoInput.files.length === 0) {
                    photoLabel.textContent = 'คลิกเพื่ออัปโหลดรูปภาพ (ไม่บังคับ)';
                    photoLabel.classList.remove('text-red-500');
                    const container = document.getElementById(`photo_container_${itemId}`);
                    container.classList.remove('border-red-400', 'bg-red-50');
                    }
                }
            }
        }
		
		function renderHistoryMode(data) {
			const record = data.record || {};
			const items = data.items || [];
			const actualSpares = data.actual_spares || [];
			const sparesSnapshot = data.spares_snapshot || [];
			const filesSnapshot = data.files_snapshot || [];
			const inspectors = data.inspectors || [];
		
			// เปลี่ยนหัวข้อหน้า
			const pageTitle = document.querySelector('nav h1 span');
			if (pageTitle) {
				pageTitle.textContent = 'ประวัติการบำรุงรักษา (PM History)';
			}
		
			// แสดงข้อมูลหัวใบงานจาก snapshot
			renderInfo({
				doc_no: record.doc_no,
				rev_no: record.rev_no,
				checksheet_name: record.checksheet_name,
				machine_code: record.machine_code,
				machine_name: record.machine_name,
				machine_sn: record.machine_sn,
				machine_type: record.machine_type,
				location: record.location,
				plan_date: record.plan_date
			});
		
			const actualDateEl = document.getElementById('info-actual-date')?.querySelector('span');
			if (actualDateEl) {
				actualDateEl.textContent = formatThaiDate(record.actual_date);
			}
		
			// แสดงไฟล์แนบ snapshot
			renderFiles(filesSnapshot);
		
			// แสดงอะไหล่ที่ต้องเตรียม snapshot
			renderHistoryPreparedSpares(sparesSnapshot);
		
			// ซ่อนส่วนอะไหล่/วัสดุที่ใช้จริงแบบ input
			hideActualSpareInputSection();
		
			// แสดงอะไหล่/วัสดุที่ใช้จริงแบบอ่านอย่างเดียว
			renderHistoryActualSpares(actualSpares);
		
			// แสดงรายการผลตรวจแบบอ่านอย่างเดียว
			renderHistoryItems(items);
		
			// แสดงผู้ตรวจสอบ
			const inspectorNames = inspectors.length > 0
				? inspectors.map(i => i.inspector_name_snapshot).filter(Boolean).join(', ')
				: (record.inspector_name || '-');
		
			const inspectorDisplay = document.getElementById('inspector_display');
			if (inspectorDisplay) {
				inspectorDisplay.innerHTML = `<span class="bg-emerald-100 text-emerald-800 text-xs px-2.5 py-1 rounded-full border border-emerald-200">${inspectorNames}</span>`;
				inspectorDisplay.onclick = null;
				inspectorDisplay.classList.add('pointer-events-none', 'bg-slate-50');
			}
		
			const inspectorInput = document.getElementById('inspector_name');
			if (inspectorInput) {
				inspectorInput.value = inspectorNames;
			}
		
			// แสดงหมายเหตุ
			const remarksEl = document.getElementById('remarks');
			if (remarksEl) {
				remarksEl.value = record.remarks || '';
				remarksEl.readOnly = true;
				remarksEl.classList.add('bg-slate-50');
			}
		
			// แสดงลายเซ็นแทน canvas
			renderHistorySignature(record.inspector_signature_path);
		
			// ซ่อนปุ่มและส่วนประเมิน
			document.getElementById('btn_save_work')?.classList.add('hidden');
			document.getElementById('btn_save_eval')?.classList.add('hidden');
			document.getElementById('evaluation_section')?.classList.add('hidden');
		
			// ซ่อนปุ่มล้าง dropdown
			document.getElementById('inspector_clear_btn')?.classList.add('hidden');
			document.getElementById('evaluator_clear_btn')?.classList.add('hidden');
		
			// ปิด input ทั้งหมดไม่ให้แก้ไข
			lockHistoryInputs();
		}
		
		function hideActualSpareInputSection() {
			const headings = Array.from(document.querySelectorAll('h2'));
		
			headings.forEach(h => {
				if (h.textContent.includes('รายการอะไหล่/วัสดุที่ใช้จริง')) {
					const section = h.closest('.bg-white');
					if (section) {
						section.classList.add('hidden');
					}
				}
			});
		}
		
		function renderHistoryItems(items) {
			const container = document.getElementById('items-list');
			let html = '';
		
			if (!items || items.length === 0) {
				container.innerHTML = `
					<div class="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-400">
						ไม่พบรายการผลการตรวจสอบ
					</div>
				`;
				return;
			}
		
			items.forEach((item, index) => {
				const statusClass = item.result_status === 'Pass'
					? 'bg-emerald-100 text-emerald-700 border-emerald-200'
					: item.result_status === 'Fail'
						? 'bg-red-100 text-red-700 border-red-200'
						: 'bg-slate-100 text-slate-700 border-slate-200';
		
				const photoUrl = item.result_photo_path ? String(item.result_photo_path).trim() : '';

				const photoHtml = photoUrl
					? `
						<div class="inline-block">
							<div 
								class="group relative w-32 h-24 rounded-xl overflow-hidden border border-slate-200 bg-slate-50 shadow-sm cursor-pointer hover:shadow-md hover:border-primary transition"
								onclick='ImageCarousel.open(${JSON.stringify([photoUrl])}, "รูปหลักฐาน")'
								title="คลิกเพื่อดูรูปใหญ่"
							>
								<img 
									src="${photoUrl}" 
									alt="รูปหลักฐาน"
									class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
									onerror="this.parentElement.innerHTML='<div class=&quot;w-full h-full flex items-center justify-center text-xs text-red-500 bg-red-50&quot;>โหลดรูปไม่ได้</div>';"
								>
				
								<div class="absolute inset-0 bg-black/0 group-hover:bg-black/35 transition flex items-center justify-center">
									<span class="opacity-0 group-hover:opacity-100 transition bg-white/90 text-slate-700 text-xs font-semibold px-3 py-1.5 rounded-full shadow">
										<i class="fa-solid fa-magnifying-glass-plus mr-1"></i> ดูรูปใหญ่
									</span>
								</div>
							</div>
						</div>
					  `
					: `<span class="text-slate-400 text-sm">ไม่มีรูปหลักฐาน</span>`;
		
				const referenceImageHtml = item.illustration_path
					? `
						<a href="${item.illustration_path}" target="_blank"
						   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-primary hover:bg-blue-100 rounded-md text-xs font-medium transition-colors border border-blue-100 mt-2">
							<i class="fa-regular fa-image"></i>
							ดูรูปประกอบมาตรฐาน
						</a>
					  `
					: '';
		
				html += `
					<details class="group bg-white rounded-xl border border-slate-200 mb-4 overflow-hidden shadow-sm" data-item-id="${item.checksheet_item_id}" ${index === 0 ? 'open' : ''}>
						<summary class="flex justify-between items-center font-medium cursor-pointer list-none p-4 sm:p-5 bg-white hover:bg-slate-50 transition-colors">
							<div class="flex items-center gap-3.5 pr-4 flex-grow">
								<span class="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 font-bold text-sm border border-emerald-200">
									${index + 1}
								</span>
		
								<div class="flex flex-col sm:flex-row sm:items-center gap-2">
									<span class="text-slate-800 font-semibold text-base leading-snug">${item.check_point || '-'}</span>
									<span class="inline-flex px-3 py-1 rounded-full border text-xs font-bold ${statusClass}">
										${item.result_status || '-'}
									</span>
								</div>
							</div>
		
							<span class="flex-shrink-0 transition-transform duration-300 group-open:-rotate-180 bg-slate-100 rounded-full p-1.5 border border-slate-200 text-slate-500">
								<i class="fa-solid fa-chevron-down w-4 h-4 flex items-center justify-center text-sm"></i>
							</span>
						</summary>
		
						<div class="p-5 border-t border-slate-100 bg-slate-50/30">
							<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
		
								<div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
									<h4 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2 pb-2 border-b border-slate-100">
										<i class="fa-solid fa-book-open text-emerald-700"></i>
										ข้อมูลอ้างอิง
									</h4>
		
									<div class="mb-4">
										<span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block mb-1.5">มาตรฐานการตรวจสอบ</span>
										<div class="text-sm text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-100 leading-relaxed">
											${item.standard_text || '-'}
										</div>
										${referenceImageHtml}
									</div>
		
									<div class="mb-4">
										<span class="text-[11px] text-slate-500 font-bold uppercase tracking-wider block mb-1.5">วิธีตรวจสอบ</span>
										<div class="text-sm text-slate-700 leading-relaxed">
											<i class="fa-solid fa-wrench text-slate-400 mr-1.5 text-xs"></i>
											${item.method_text || '-'}
										</div>
									</div>
		
									<div>
										<span class="text-[11px] text-red-500 font-bold uppercase tracking-wider block mb-1.5">ข้อปฏิบัติเมื่อพบความผิดปกติ</span>
										<div class="text-sm text-red-700 bg-red-50 p-3 rounded-lg border border-red-100 leading-relaxed">
											<i class="fa-solid fa-triangle-exclamation mr-1.5"></i>
											${item.action_abnormal || '-'}
										</div>
									</div>
								</div>
		
								<div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
									<h4 class="text-sm font-bold text-slate-800 mb-4 flex items-center gap-2 pb-2 border-b border-slate-100">
										<i class="fa-solid fa-clipboard-check text-emerald-700"></i>
										ผลการตรวจที่บันทึกไว้
									</h4>
		
									<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
										<div class="bg-slate-50 rounded-xl border border-slate-100 p-4">
											<div class="text-[11px] text-slate-500 font-bold mb-1">ผลการตรวจ</div>
											<div>
												<span class="inline-flex px-3 py-1 rounded-full border text-xs font-bold ${statusClass}">
													${item.result_status || '-'}
												</span>
											</div>
										</div>
		
										<div class="bg-slate-50 rounded-xl border border-slate-100 p-4">
											<div class="text-[11px] text-slate-500 font-bold mb-1">ค่าที่วัดได้จริง</div>
											<div class="font-bold text-slate-800">
												${item.actual_value || '-'} ${item.unit || ''}
											</div>
											${item.expected_value ? `<div class="text-xs text-emerald-700 mt-1">ค่ามาตรฐาน: ${item.expected_value} ${item.unit || ''}</div>` : ''}
										</div>
									</div>
		
									<div>
										<div class="text-[11px] text-slate-500 font-bold mb-2">รูปภาพหลักฐาน</div>
										${photoHtml}
									</div>
								</div>
		
							</div>
						</div>
					</details>
				`;
			});
		
			container.innerHTML = html;
		}
		
		function renderHistoryActualSpares(spares) {
			if (!spares || spares.length === 0) return;
		
			const container = document.createElement('div');
			container.className = 'bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6';
		
			let rows = '';
		
			spares.forEach((sp, index) => {
				rows += `
					<tr class="hover:bg-amber-50 transition-colors">
						<td class="py-3 px-6 text-center text-slate-500 font-medium">${index + 1}</td>
						<td class="py-3 px-6">
							<div class="font-semibold text-slate-900">${sp.rpd_details_head || '-'}</div>
							<div class="text-xs text-slate-500">${sp.pd_gen_code || '-'}</div>
						</td>
						<td class="py-3 px-6 text-center font-bold text-amber-700">${sp.rpd_qty || '-'}</td>
						<td class="py-3 px-6 text-center">${sp.pd_unit || '-'}</td>
						<td class="py-3 px-6 text-right">${Number(sp.rpd_price || 0).toLocaleString('th-TH')}</td>
						<td class="py-3 px-6 text-right font-bold">${Number(sp.rpd_sum_money || 0).toLocaleString('th-TH')}</td>
					</tr>
				`;
			});
		
			container.innerHTML = `
				<div class="bg-amber-50 border-b border-amber-200 px-6 py-4 flex items-center gap-2.5">
					<i class="fa-solid fa-screwdriver-wrench text-amber-700 text-lg"></i>
					<h2 class="text-lg font-semibold text-amber-900">อะไหล่/วัสดุที่ใช้จริง</h2>
				</div>
				<div class="p-0 overflow-x-auto">
					<table class="w-full text-sm">
						<thead class="bg-amber-100/50 text-amber-900 border-b border-amber-200">
							<tr>
								<th class="py-3 px-6 text-center w-16">ลำดับ</th>
								<th class="py-3 px-6 text-left">รายการ</th>
								<th class="py-3 px-6 text-center">จำนวน</th>
								<th class="py-3 px-6 text-center">หน่วย</th>
								<th class="py-3 px-6 text-right">ราคา</th>
								<th class="py-3 px-6 text-right">รวม</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-amber-100">${rows}</tbody>
					</table>
				</div>
			`;
		
			const itemsSection = document.getElementById('items-list')?.closest('.bg-white');
		
			if (itemsSection) {
				itemsSection.insertAdjacentElement('beforebegin', container);
			}
		}
		
		function renderHistoryPreparedSpares(spares) {
			if (!spares || spares.length === 0) {
				return;
			}
		
			const container = document.getElementById('spares-container');
			const tbody = document.getElementById('spares-list');
		
			if (!container || !tbody) return;
		
			container.classList.remove('hidden');
		
			let html = '';
		
			spares.forEach((sp, index) => {
				html += `
					<tr class="hover:bg-amber-50 transition-colors">
						<td class="py-3 px-6 text-center text-slate-500 font-medium">${index + 1}</td>
						<td class="py-3 px-6 font-medium text-slate-900">${sp.part_name || '-'}</td>
						<td class="py-3 px-6 text-center font-bold text-amber-700 text-base">${sp.quantity || '-'}</td>
					</tr>
				`;
			});
		
			tbody.innerHTML = html;
		}
		
		function renderHistorySignature(signaturePath) {
			const canvas = document.getElementById('inspector-signature-pad');
			if (!canvas) return;
		
			const wrap = canvas.closest('.border');
			if (!wrap) return;
		
			wrap.innerHTML = '';
		
			if (signaturePath) {
				wrap.innerHTML = `
					<div class="min-h-[150px] flex items-center justify-center bg-white">
						<img src="${signaturePath}" class="max-h-32 object-contain">
					</div>
				`;
			} else {
				wrap.innerHTML = `
					<div class="min-h-[150px] flex items-center justify-center bg-slate-50 text-slate-400">
						ไม่มีลายเซ็น
					</div>
				`;
			}
		}
		
		function lockHistoryInputs() {
				document.querySelectorAll('input, textarea, select, button').forEach(el => {
				const id = el.id || '';
		
				// ไม่ล็อกปุ่มยกเลิก/ปิดหน้าต่าง
				if (el.textContent && el.textContent.includes('ยกเลิก')) {
					return;
				}
		
				if (id === 'btn_save_work' || id === 'btn_save_eval') {
					return;
				}
		
				if (el.tagName === 'BUTTON') {
					if (!el.onclick || String(el.onclick).includes('window.close')) {
						return;
					}
				}
		
				if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT') {
					el.readOnly = true;
					el.disabled = true;
					el.classList.add('bg-slate-50');
				}
			});
		}
		
        function previewImage(input, itemId) {
            const file = input.files[0];
            const base64Input = document.getElementById(`photo_base64_${itemId}`);
            const container = document.getElementById(`photo_container_${itemId}`);
            const uploadUI = document.getElementById(`upload_ui_${itemId}`);
            const previewUI = document.getElementById(`preview_ui_${itemId}`);
            const imgPreview = document.getElementById(`img_preview_${itemId}`);
            const label = document.getElementById(`photo_label_${itemId}`);

            if (file) {
                // เปลี่ยนสีขอบเป็นสี primary
                container.classList.add('border-primary', 'bg-blue-50/10');
                container.classList.remove('p-2'); // เอา padding ออกเพื่อให้รูปภาพชิดขอบสวยงาม
                
                const reader = new FileReader();
                reader.onload = function(e) { 
                    // เซ็ตค่า Base64 และนำไปแสดงที่ <img>
                    const cleanBase64 = String(e.target.result || '')
						.replace(/[\u0000-\u001F\u007F-\u009F]/g, '');
					
					base64Input.value = cleanBase64;
					imgPreview.src = cleanBase64;
                    
                    // สลับ UI: ซ่อนกล่องข้อความอัปโหลด -> แสดงรูปพรีวิว
                    uploadUI.classList.add('hidden');
                    previewUI.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                // กรณีผู้ใช้กด Cancle ไม่อัปโหลดรูปภาพ (เคลียร์ค่าทิ้ง)
                const isPhotoRequired = input.hasAttribute('required');
                label.textContent = isPhotoRequired ? 'คลิกเพื่อถ่ายรูป (บังคับ)' : 'คลิกเพื่ออัปโหลดรูปภาพ';
                label.classList.remove('text-primary', 'font-bold');
                
                container.classList.remove('border-primary', 'bg-blue-50/10');
                container.classList.add('p-2');
                
                base64Input.value = '';
                imgPreview.src = '';
                
                // สลับ UI คืนค่าเดิม: แสดงกล่องข้อความอัปโหลด -> ซ่อนรูปพรีวิว
                uploadUI.classList.remove('hidden');
                previewUI.classList.add('hidden');
            }
        }
		
		function clearValidationUI() {
			document.querySelectorAll('.field-error').forEach(el => el.classList.remove('field-error'));
			document.querySelectorAll('.item-error-summary').forEach(el => el.classList.remove('item-error-summary'));
			document.querySelectorAll('.item-error-panel').forEach(el => el.classList.remove('item-error-panel'));
		}
		
		function markElementError(element) {
			if (element) element.classList.add('field-error');
		}
		
		function markItemError(itemId) {
			const details = document.querySelector(`details[data-item-id="${itemId}"]`);
			if (!details) return;
		
			details.open = true;
		
			const summary = details.querySelector('summary');
			const panel = details.querySelector('.bg-blue-50\\/50, .bg-blue-50');
		
			if (summary) summary.classList.add('item-error-summary');
			if (panel) panel.classList.add('item-error-panel');
		}
		
		function scrollToFirstError(errors) {
			if (!errors || errors.length === 0) return;
		
			const first = errors[0];
		
			if (first.type === 'item') {
				const details = document.querySelector(`details[data-item-id="${first.itemId}"]`);
				if (details) {
					details.open = true;
					details.scrollIntoView({ behavior: 'smooth', block: 'center' });
				}
				return;
			}
		
			if (first.element) {
				first.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		}
		
		function openAndScrollToElement(element) {
			if (!element) return;
		
			const details = element.closest('details');
			if (details) {
				details.open = true;
			}
		
			setTimeout(() => {
				element.scrollIntoView({
					behavior: 'smooth',
					block: 'center'
				});
		
				try {
					element.focus({ preventScroll: true });
				} catch (e) {}
			}, 180);
		}
		
		function goToValidationTarget(errorIndex) {
			if (!window.latestValidationErrors || !window.latestValidationErrors[errorIndex]) return;
		
			const err = window.latestValidationErrors[errorIndex];
		
			Swal.close();
		
			setTimeout(() => {
				if (err.type === 'header') {
					if (err.fieldKey === 'inspector_name') {
						const el = document.getElementById('inspector_display');
						openAndScrollToElement(el);
						setTimeout(() => {
							toggleDropdown('inspector_dropdown');
						}, 250);
						return;
					}
		
					if (err.fieldKey === 'inspector_signature') {
						const el = document.getElementById('inspector-signature-pad')?.closest('.border');
						openAndScrollToElement(el);
						return;
					}
				}
		
				if (err.type === 'item') {
					const itemId = err.itemId;
					const details = document.querySelector(`details[data-item-id="${itemId}"]`);
					if (details) details.open = true;
		
					let target = null;
		
					if (err.fieldKey === 'status') {
						target = document.querySelector(`input[name="status_${itemId}"]`)?.closest('.mb-5');
					} else if (err.fieldKey === 'value') {
						target = document.querySelector(`[name="val_${itemId}"]`);
					} else if (err.fieldKey === 'photo') {
						target = document.getElementById(`photo_container_${itemId}`);
					} else {
						target = details;
					}
		
					openAndScrollToElement(target || details);
				}
			}, 120);
		}
		
		window.goToValidationTarget = goToValidationTarget;
		
		function getItemFormData(itemId) {
			const statusEl = document.querySelector(`input[name="status_${itemId}"]:checked`);
			const valueEl = document.querySelector(`[name="val_${itemId}"]`);
			const photoBase64El = document.getElementById(`photo_base64_${itemId}`);
			const photoInputEl = document.getElementById(`photo_input_${itemId}`);
		
			return {
				status: statusEl ? String(statusEl.value).trim() : '',
				value: valueEl ? String(valueEl.value).trim() : '',
				photoBase64: photoBase64El ? String(photoBase64El.value).trim() : '',
				hasPhotoFile: !!(photoInputEl && photoInputEl.files && photoInputEl.files.length > 0),
				statusEl,
				valueEl,
				photoInputEl
			};
		}
		
		function hasMeasurementConfig(item) {
			return item.expected_value !== null &&
				   item.expected_value !== undefined &&
				   String(item.expected_value).trim() !== '' &&
				   String(item.expected_value).trim() !== '-';
		}
		
		function isNumericRequiredValue(value) {
			return value !== '' && !isNaN(value);
		}
		
		function validateInspectorSection() {
			const errors = [];
	
			const inspectorInput = document.getElementById('inspector_name');
			const inspectorUsersJsonInput = document.getElementById('inspector_users_json');
			const inspectorDisplay = document.getElementById('inspector_display');
			const remarksEl = document.getElementById('remarks');
	
			const inspectorName = inspectorInput ? inspectorInput.value.trim() : '';
			const inspectorUsersJson = inspectorUsersJsonInput ? inspectorUsersJsonInput.value.trim() : '';
			const remarks = remarksEl ? remarksEl.value.trim() : '';
	
			if (!inspectorName) {
				markElementError(inspectorDisplay);
				errors.push({
					type: 'header',
					fieldKey: 'inspector_name',
					message: 'ยังไม่ได้เลือกชื่อผู้ตรวจสอบ',
					element: inspectorDisplay
				});
			}

			if (!inspectorUsersJson || inspectorUsersJson === '[]') {
				markElementError(inspectorDisplay);
				errors.push({
					type: 'header',
					fieldKey: 'inspector_users_json',
					message: 'ไม่พบ user_id ของผู้ตรวจสอบ กรุณาเลือกชื่อใหม่อีกครั้ง',
					element: inspectorDisplay
				});
			}
	
			if (!inspectorPad || inspectorPad.isEmpty()) {
				const signCanvasWrap = document.getElementById('inspector-signature-pad')?.closest('.border');
				markElementError(signCanvasWrap);
				errors.push({
					type: 'header',
					fieldKey: 'inspector_signature',
					message: 'ยังไม่ได้ลงลายมือชื่อผู้ตรวจสอบ',
					element: signCanvasWrap
				});
			}
	
			return {
				valid: errors.length === 0,
				errors,
				data: {
					inspector_name: inspectorName,
					inspector_users_json: inspectorUsersJson,
					remarks,
					inspector_signature: (inspectorPad && !inspectorPad.isEmpty())
						? inspectorPad.toDataURL('image/png')
						: ''
				}
			};
		}
		
		function validateWorksheetItems(items) {
			const errors = [];
			const normalizedItems = [];
		
			items.forEach((item, index) => {
				const rowNo = index + 1;
				const itemId = item.id;
				const formData = getItemFormData(itemId);
		
				const status = formData.status;
				const value = formData.value;
				const hasPhoto = formData.photoBase64 !== '' || formData.hasPhotoFile;
				const photoRequiredId = String(item.photo_required_id || '');
				const needMeasurement = hasMeasurementConfig(item);
		
				let hasErrorInThisItem = false;
		
				if (!status) {
					hasErrorInThisItem = true;
					const statusBox = document.querySelector(`input[name="status_${itemId}"]`)?.closest('.mb-5');
					markElementError(statusBox);
					errors.push({
						type: 'item',
						itemId,
						fieldKey: 'status',
						message: `ข้อ ${rowNo} "${item.check_point || '-'}" : ยังไม่ได้ระบุผลการตรวจ`,
						element: statusBox
					});
				}
		
				//if (needMeasurement && value === '') {
//					hasErrorInThisItem = true;
//					markElementError(formData.valueEl);
//					errors.push({
//						type: 'item',
//						itemId,
//						fieldKey: 'value',
//						message: `ข้อ ${rowNo} "${item.check_point || '-'}" : ต้องกรอกค่าที่วัดได้จริง ตามค่ามาตรฐาน ${item.expected_value || '-'}`,
//						element: formData.valueEl
//					});
//				}
		
				if (needMeasurement && value !== '' && !isNumericRequiredValue(value)) {
					hasErrorInThisItem = true;
					markElementError(formData.valueEl);
					errors.push({
						type: 'item',
						itemId,
						fieldKey: 'value',
						message: `ข้อ ${rowNo} "${item.check_point || '-'}" : ค่า ${item.measurement_name} ต้องเป็นตัวเลข`,
						element: formData.valueEl
					});
				}
		
				if (photoRequiredId === '1' && !hasPhoto) {
					hasErrorInThisItem = true;
					const photoWrap = document.getElementById(`photo_container_${itemId}`);
					markElementError(photoWrap);
					errors.push({
						type: 'item',
						itemId,
						fieldKey: 'photo',
						message: `ข้อ ${rowNo} "${item.check_point || '-'}" : ระบบกำหนดให้แนบรูปภาพหลักฐาน`,
						element: photoWrap
					});
				}
		
				if (photoRequiredId === '3' && status === 'Fail' && !hasPhoto) {
					hasErrorInThisItem = true;
					const photoWrap = document.getElementById(`photo_container_${itemId}`);
					markElementError(photoWrap);
					errors.push({
						type: 'item',
						itemId,
						fieldKey: 'photo',
						message: `ข้อ ${rowNo} "${item.check_point || '-'}" : เมื่อผลเป็น Fail ระบบกำหนดให้แนบรูปภาพหลักฐาน`,
						element: photoWrap
					});
				}
		
				if (String(item.check_type_id) === '1' && status && !['Pass', 'Fail'].includes(status)) {
					hasErrorInThisItem = true;
					errors.push({
						type: 'item',
						itemId,
						fieldKey: 'status',
						message: `ข้อ ${rowNo} "${item.check_point || '-'}" : ค่าผลการตรวจไม่ถูกต้อง`,
						element: null
					});
				}
		
				if (String(item.check_type_id) === '2' && status && !['Pass', 'Fail', 'N/A'].includes(status)) {
					hasErrorInThisItem = true;
					errors.push({
						type: 'item',
						itemId,
						fieldKey: 'status',
						message: `ข้อ ${rowNo} "${item.check_point || '-'}" : ค่าผลการตรวจไม่ถูกต้อง`,
						element: null
					});
				}
		
				if (hasErrorInThisItem) {
					markItemError(itemId);
				}
		
				const cleanPhotoBase64 = (formData.photoBase64 || '')
					.replace(/[\u0000-\u001F\u007F-\u009F]/g, '');
				
				normalizedItems.push({
					item_id: itemId,
					status: status || '',
					value: value !== '' ? value : '-',
					photo_required_id: photoRequiredId,
					check_type_id: String(item.check_type_id || ''),
					measurement_name: item.measurement_name || '',
					has_photo: cleanPhotoBase64 !== '' ? 1 : 0
				});
			});
		
			return {
				valid: errors.length === 0,
				errors,
				items: normalizedItems
			};
		}
		
		function buildEnterpriseValidationHtml(errors) {
			window.latestValidationErrors = errors;
		
			const headerErrors = errors.filter(err => err.type === 'header');
			const itemErrors = errors.filter(err => err.type === 'item');
		
			const renderSection = (title, icon, items, tone = 'warning', startIndex = 0) => {
				if (!items.length) return '';
		
				const toneMap = {
					warning: {
						bg: '#fff7ed',
						border: '#fdba74',
						title: '#9a3412',
						text: '#7c2d12',
						badgeBg: '#ffedd5',
						badgeText: '#9a3412'
					},
					neutral: {
						bg: '#f8fafc',
						border: '#cbd5e1',
						title: '#334155',
						text: '#475569',
						badgeBg: '#e2e8f0',
						badgeText: '#334155'
					}
				};
		
				const c = toneMap[tone];
		
				return `
					<div style="
						margin-top:12px;
						border:1px solid ${c.border};
						background:${c.bg};
						border-radius:14px;
						padding:14px 16px;
						text-align:left;
					">
						<div style="
							display:flex;
							align-items:center;
							justify-content:space-between;
							gap:12px;
							margin-bottom:10px;
						">
							<div style="
								display:flex;
								align-items:center;
								gap:8px;
								font-weight:700;
								color:${c.title};
								font-size:14px;
							">
								<span>${icon}</span>
								<span>${title}</span>
							</div>
							<span style="
								display:inline-flex;
								align-items:center;
								justify-content:center;
								min-width:28px;
								height:28px;
								padding:0 10px;
								border-radius:999px;
								background:${c.badgeBg};
								color:${c.badgeText};
								font-size:12px;
								font-weight:700;
							">${items.length}</span>
						</div>
		
						<div style="display:flex; flex-direction:column; gap:8px;">
							${items.map((err, index) => {
								const realIndex = startIndex + index;
								return `
									<button
										type="button"
										onclick="goToValidationTarget(${realIndex})"
										style="
											width:100%;
											background:#ffffff;
											border:1px solid rgba(148,163,184,0.18);
											border-radius:10px;
											padding:10px 12px;
											display:flex;
											gap:10px;
											align-items:flex-start;
											text-align:left;
											cursor:pointer;
											transition:all .18s ease;
										"
										onmouseover="this.style.borderColor='#fb923c'; this.style.transform='translateY(-1px)'"
										onmouseout="this.style.borderColor='rgba(148,163,184,0.18)'; this.style.transform='translateY(0)'"
									>
										<div style="
											flex:0 0 auto;
											width:22px;
											height:22px;
											border-radius:999px;
											background:${c.badgeBg};
											color:${c.badgeText};
											display:flex;
											align-items:center;
											justify-content:center;
											font-size:11px;
											font-weight:700;
											margin-top:1px;
										">${realIndex + 1}</div>
		
										<div style="flex:1;">
											<div style="
												color:${c.text};
												font-size:13px;
												line-height:1.55;
												margin-bottom:4px;
											">
												${err.message}
											</div>
											<div style="
												font-size:11px;
												color:#64748b;
												font-weight:600;
											">
												คลิกเพื่อไปยังตำแหน่งที่ต้องแก้ไข
											</div>
										</div>
									</button>
								`;
							}).join('')}
						</div>
					</div>
				`;
			};
		
			return `
				<div style="text-align:left;">
					<div style="
						border:1px solid #fde68a;
						background:linear-gradient(180deg,#fffdf5 0%, #fffaf0 100%);
						border-radius:16px;
						padding:16px;
						margin-bottom:12px;
					">
						<div style="
							display:flex;
							align-items:flex-start;
							gap:12px;
						">
							<div style="
								width:42px;
								height:42px;
								border-radius:12px;
								background:#fff7ed;
								border:1px solid #fdba74;
								display:flex;
								align-items:center;
								justify-content:center;
								font-size:20px;
							">⚠️</div>
		
							<div>
								<div style="
									font-size:16px;
									font-weight:700;
									color:#92400e;
									margin-bottom:4px;
								">
									ข้อมูลยังไม่พร้อมสำหรับการบันทึก
								</div>
								<div style="
									font-size:13px;
									color:#78716c;
									line-height:1.6;
								">
									พบรายการที่ต้องตรวจสอบทั้งหมด
									<strong style="color:#92400e;"> ${errors.length} จุด</strong>
									กรุณาคลิกที่รายการเพื่อไปยังตำแหน่งที่ต้องแก้ไข
								</div>
							</div>
						</div>
					</div>
		
					<div style="
						max-height: 420px;
						overflow-y: auto;
						padding-right: 6px;
					">
						${renderSection('ข้อมูลผู้ตรวจสอบ', '👤', headerErrors, 'neutral', 0)}
						${renderSection('รายการจุดตรวจสอบ', '🧾', itemErrors, 'warning', headerErrors.length)}
					</div>
				</div>
			`;
		}
		
		function collectActualSpares() {
			const rows = document.querySelectorAll('#parts_wrap > div');
			const parts = [];
		
			rows.forEach(row => {
				const qtyInput = row.querySelector('input[data-role="qty"]');
				const rpdQty = qtyInput ? String(qtyInput.value || '').trim() : '';
		
				if (rpdQty !== '' && parseFloat(rpdQty) > 0) {
					const qty = parseFloat(rpdQty || '0');
					const price = parseFloat(row.dataset.price || '0');
		
					parts.push({
						rpd_product_id: row.dataset.productId || '',
						ps_id: row.dataset.stockId || '',
						wh_id: row.dataset.whId || '',
		
						pd_gen_code: row.dataset.genCode || '',
						rpd_details_head: row.dataset.detailsHead || '',
						rpd_details: row.dataset.details || '',
						rpd_brand: row.dataset.brand || '',
		
						rpd_qty: qty,
						rpd_price: price,
						pd_unit: row.dataset.unit || '',
						rpd_sum_money: qty * price,
		
						wh_name: row.dataset.whName || '',
						pd_model: row.dataset.model || ''
					});
				}
			});
		
			return parts;
		}

        // ฟังก์ชันบันทึกส่วนแรก (Inspector)
       async function saveWorkRecord() {
			try {
				clearValidationUI();
		
				if (!Array.isArray(worksheetItems) || worksheetItems.length === 0) {
					await Swal.fire({
						icon: 'warning',
						title: 'ไม่พบรายการตรวจสอบ',
						text: 'ยังไม่มีข้อมูลรายการจุดตรวจสอบสำหรับบันทึก',
						confirmButtonColor: '#006b9f'
					});
					return;
				}
		
				const inspectorCheck = validateInspectorSection();
				const itemsCheck = validateWorksheetItems(worksheetItems);
		
				const allErrors = [
					...inspectorCheck.errors,
					...itemsCheck.errors
				];
		
				if (allErrors.length > 0) {
					scrollToFirstError(allErrors);
		
					await Swal.fire({
						icon: 'warning',
						title: 'ตรวจพบข้อมูลไม่ครบ',
						html: buildEnterpriseValidationHtml(allErrors),
						width: 760,
						confirmButtonText: 'ไปแก้ไขข้อมูล',
						confirmButtonColor: '#006b9f',
						customClass: {
							popup: 'pm-swal-enterprise',
							title: 'pm-swal-title',
							htmlContainer: 'pm-swal-html'
						}
					});
					return;
				}
		
				Swal.fire({
					title: 'กำลังบันทึกข้อมูล',
					html: `
						<div style="text-align:center; color:#475569; font-size:13px;">
							กรุณารอสักครู่ ระบบกำลังตรวจสอบและบันทึกผลการปฏิบัติงาน
						</div>
					`,
					allowOutsideClick: false,
					allowEscapeKey: false,
					showConfirmButton: false,
					didOpen: () => {
						Swal.showLoading();
					},
					customClass: {
						popup: 'pm-swal-enterprise',
						title: 'pm-swal-title',
						htmlContainer: 'pm-swal-html'
					}
				});
		
				const formBody = new FormData();
				const parts = collectActualSpares();
				const itemsPayload = JSON.stringify(itemsCheck.items || []);
				const partsPayload = JSON.stringify(parts || []);
				const cleanInspectorSignature = String(inspectorCheck.data.inspector_signature || '')
					.replace(/[\u0000-\u001F\u007F-\u009F]/g, '');
				
				formBody.append('action', 'save');
				formBody.append('plan_id', planId);
				formBody.append('inspector_name', inspectorCheck.data.inspector_name);
				formBody.append('inspector_users_json', inspectorCheck.data.inspector_users_json);
				formBody.append('remarks', inspectorCheck.data.remarks);
				formBody.append('inspector_signature', cleanInspectorSignature);
				formBody.append('items', itemsPayload);
				formBody.append('parts', partsPayload);
				
				itemsCheck.items.forEach(item => {
					const photoEl = document.getElementById(`photo_base64_${item.item_id}`);
					const photoBase64 = photoEl ? String(photoEl.value || '').trim() : '';
					if (photoBase64 !== '') {
						const cleanPhotoBase64 = photoBase64.replace(/[\u0000-\u001F\u007F-\u009F]/g, '');
						formBody.append(`photo_${item.item_id}`, cleanPhotoBase64);
					}
				});
				
				const response = await axios.post('handle_pm_worksheet.php', formBody);
				Swal.close();
		
				if (response.data.success) {
					document.getElementById('evaluation_section').classList.remove('hidden');
					document.getElementById('btn_save_eval').classList.remove('hidden');
					document.getElementById('btn_save_work').classList.add('hidden');
		
					await Swal.fire({
						icon: 'success',
						title: 'บันทึกผลสำเร็จ',
						html: `
							<div style="text-align:left;">
								<div style="
									border:1px solid #bbf7d0;
									background:linear-gradient(180deg,#f0fdf4 0%, #ecfdf5 100%);
									border-radius:16px;
									padding:16px;
								">
									<div style="display:flex; gap:12px; align-items:flex-start;">
										<div style="
											width:42px;
											height:42px;
											border-radius:12px;
											background:#dcfce7;
											border:1px solid #86efac;
											display:flex;
											align-items:center;
											justify-content:center;
											font-size:20px;
										">✅</div>
					
										<div>
											<div style="
												font-size:15px;
												font-weight:700;
												color:#166534;
												margin-bottom:4px;
											">
												บันทึกผลการปฏิบัติงานเรียบร้อยแล้ว
											</div>
											<div style="
												font-size:13px;
												color:#3f3f46;
												line-height:1.6;
											">
												ระบบบันทึกข้อมูลสำเร็จแล้ว
											</div>
										</div>
									</div>
								</div>
							</div>
						`,
						width: 640,
						timer: 1200,
						showConfirmButton: false,
						allowOutsideClick: false,
						allowEscapeKey: false,
						customClass: {
							popup: 'pm-swal-enterprise',
							title: 'pm-swal-title',
							htmlContainer: 'pm-swal-html'
						}
					});
					
					// พยายามปิดหน้าต่าง
					window.open('', '_self');
					window.close();
					
					// ถ้าปิดไม่ได้ ให้กลับหน้าก่อนหน้า หรือ redirect ไปหน้ารายการ
					setTimeout(() => {
						if (!window.closed) {
							if (window.history.length > 1) {
								window.history.back();
							} else {
								window.location.href = 'pm_plan.php'; // แก้เป็นหน้ารายการที่ต้องการ
							}
						}
					}, 500);

				} else {
					await Swal.fire({
						icon: 'error',
						title: 'เกิดข้อผิดพลาด',
						text: response.data.error || 'ไม่สามารถบันทึกข้อมูลได้',
						confirmButtonColor: '#dc2626'
					});
				}
		
			} catch (error) {
				console.error('Error saving work record:', error);
				Swal.close();
		
				const serverMessage =
					error?.response?.data?.error ||
					error?.response?.data?.message ||
					error?.message ||
					'ไม่สามารถตรวจสอบหรือบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง';

				await Swal.fire({
					icon: 'error',
					title: 'เกิดข้อผิดพลาด',
					text: serverMessage,
					confirmButtonColor: '#dc2626'
				});
			}
		}

        // ฟังก์ชันบันทึกส่วนที่สอง (Evaluator)
        async function saveEvaluation() {
            
        }

        document.addEventListener('click', function(event) {
            if (!event.target.closest('.custom-select-container')) {
                document.getElementById('inspector_dropdown')?.classList.add('hidden');
                document.getElementById('evaluator_dropdown')?.classList.add('hidden');
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
			initSignaturePads();
		
			if (!isHistoryMode) {
				initEvaluationCalculator();
				loadEvalTopics();
				loadUsers();
			}
		
			if (planId) {
				fetchData();
			} else {
				Swal.fire({
					icon: 'error',
					title: 'ข้อผิดพลาด',
					text: 'ไม่พบรหัสแผนงาน (Plan ID)'
				});
			}
		});
		
		function openPmPrintPage() {
			const params = new URLSearchParams(window.location.search);
			const planId = params.get('plan_id');
			const workRecordId = params.get('work_record_id');
		
			if (!planId && !workRecordId) {
				Swal.fire('ผิดพลาด', 'ไม่พบรหัสแผนงานหรือรหัสประวัติ PM', 'error');
				return;
			}
		
			let url = 'pm_worksheet_print.php?';
		
			if (workRecordId) {
				url += 'work_record_id=' + encodeURIComponent(workRecordId);
			} else {
				url += 'plan_id=' + encodeURIComponent(planId);
			}
		
			window.open(
				url,
				'_blank',
				'width=' + screen.availWidth +
				',height=' + screen.availHeight +
				',left=0,top=0,scrollbars=yes,resizable=yes'
			);
		}
		
		function openPmEventMatrixReport() {
			const params = new URLSearchParams(window.location.search);
			const planId = params.get('plan_id');
			const workRecordId = params.get('work_record_id');
		
			if (!planId && !workRecordId) {
				Swal.fire('ผิดพลาด', 'ไม่พบรหัสแผนงานหรือรหัสประวัติ PM', 'error');
				return;
			}
		
			let url = 'pm_monthly_event_matrix_report.php?';
		
			if (workRecordId) {
				url += 'cols=21&show_pending=1&rows=18&work_record_id=' + encodeURIComponent(workRecordId);
			} else {
				url += 'cols=21&show_pending=1&rows=18&plan_id=' + encodeURIComponent(planId);
				
			}
		
			window.open(
				url,
				'_blank',
				'width=' + screen.availWidth +
				',height=' + screen.availHeight +
				',left=0,top=0,scrollbars=yes,resizable=yes'
			);
		}
		
		function openPmMonthlyReport() {
			const params = new URLSearchParams(window.location.search);
			const planId = params.get('plan_id');
			const workRecordId = params.get('work_record_id');
		
			if (!planId && !workRecordId) {
				Swal.fire('ผิดพลาด', 'ไม่พบรหัสแผนงานหรือรหัสประวัติ PM', 'error');
				return;
			}
		
			let url = 'pm_monthly_report.php?';
		
			if (workRecordId) {
				url += 'work_record_id=' + encodeURIComponent(workRecordId);
			} else {
				url += 'plan_id=' + encodeURIComponent(planId);
			}
		
			window.open(
				url,
				'_blank',
				'width=' + screen.availWidth +
				',height=' + screen.availHeight +
				',left=0,top=0,scrollbars=yes,resizable=yes'
			);
		}

    </script>
	
	<div id="image-carousel-modal" class="fixed inset-0 z-[99999] hidden">
    <div id="image-carousel-backdrop" class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>

    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative w-full max-w-5xl bg-white rounded-[28px] shadow-2xl overflow-hidden">
            <button id="image-carousel-close"
                class="absolute top-4 right-4 z-20 w-10 h-10 rounded-full bg-white/90 hover:bg-white text-slate-500 hover:text-slate-700 shadow-md flex items-center justify-center transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <div class="bg-black flex items-center justify-center min-h-[320px] md:min-h-[520px]">
                <img id="image-carousel-image" src="" alt="preview" class="max-w-full max-h-[75vh] object-contain select-none">
            </div>

            <div class="absolute left-0 right-0 bottom-0 p-4 pointer-events-none">
                <div class="flex items-center justify-center">
                    <div class="pointer-events-auto flex items-center gap-2 bg-slate-900/90 text-white rounded-full px-3 py-2 shadow-xl">
                        <button id="image-carousel-prev"
                            class="w-10 h-10 rounded-full hover:bg-white/10 flex items-center justify-center transition">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <div class="w-px h-6 bg-white/15"></div>

                        <button id="image-carousel-zoom-out"
                            class="w-10 h-10 rounded-full hover:bg-white/10 flex items-center justify-center transition">
                            <i class="fa-solid fa-minus"></i>
                        </button>

                        <button id="image-carousel-zoom-in"
                            class="w-10 h-10 rounded-full hover:bg-white/10 flex items-center justify-center transition">
                            <i class="fa-solid fa-plus"></i>
                        </button>

                        <div class="min-w-[70px] text-center text-sm font-semibold">
                            <span id="image-carousel-counter">1 / 1</span>
                        </div>

                        <button id="image-carousel-reset"
                            class="w-10 h-10 rounded-full hover:bg-white/10 flex items-center justify-center transition">
                            <i class="fa-solid fa-rotate-right"></i>
                        </button>

                        <div class="w-px h-6 bg-white/15"></div>

                        <button id="image-carousel-next"
                            class="w-10 h-10 rounded-full hover:bg-white/10 flex items-center justify-center transition">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="image-carousel-dots" class="absolute bottom-3 left-1/2 -translate-x-1/2 flex items-center gap-2"></div>
        </div>
    </div>
</div>
</body>
</html>