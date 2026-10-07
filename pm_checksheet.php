<?php
if (!headers_sent()) { header('Content-Type: text/html; charset=UTF-8'); } 
@session_start();
include "config_ctrl/checksession.php"; 
?>

<div id="tab-checksheet" class="tab-content hidden space-y-6">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800">สร้างแบบฟอร์มตรวจสอบ</h3>
                            <p class="text-sm text-slate-500">กำหนดหัวข้อและมาตรฐานการตรวจสอบเชิงเทคนิค</p> 
                        </div>
                        <div class="flex flex-row items-center gap-2">
                            <button class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition-colors" onclick="resetChecksheetForm()">
                                <i data-lucide="refresh-cw" class="w-4 h-4"></i> ล้างข้อมูล
                            </button>
                            <button class="btn-gradient px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2" onclick="saveChecksheet()">
                                <i data-lucide="save" class="w-4 h-4"></i> บันทึก Template
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">ชื่อเช็คชีต <span class="text-red-500">*</span></label>
                            <input type="text" id="checksheet-name" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                            <input type="hidden" id="checksheet-id" value="">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">ประเภทเครื่องจักร <span class="text-red-500">*</span></label>
                            <select id="checksheet-dept" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none bg-white">
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">วันที่มีผล <span class="text-red-500">*</span></label>
                            <input type="date" id="checksheet-effective-date" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">เลขที่เอกสาร<span class="text-red-500">*</span></label>
                            <input type="text" id="checksheet-doc-no" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Revision No. <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="checksheet-rev-no" value="00" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">เวลาที่คาดว่าจะเสร็จ (นาที) <span class="text-red-500">*</span></label>
                            <input type="number" id="checksheet-estimated-time" placeholder="60" class="w-full border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                        </div>
                        
                        <div class="md:col-span-2 relative z-0">
                            <div class="flex justify-between items-center mb-1">
                                <label class="block text-sm font-medium text-slate-700">รายการอะไหล่ที่ต้องใช้</label>
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
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">รายการเช็คชีตที่บันทึกไว้ </h3>
                    <div class="border border-slate-200 rounded-lg overflow-hidden">
                        <div id="checksheet-grid" class="ag-theme-alpine" style="height: 520px; width: 100%;"></div>
                    </div>
                </div>
            </div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let checksheetImageFiles = [];
        let workInstructionFiles = [];

        const AG_ID = '<?php echo isset($sess_user_agency_es) ? $sess_user_agency_es : ""; ?>';
        const USER_ID = '<?php echo isset($sess_user_id) ? $sess_user_id : ""; ?>';

        async function loadChecksheets() {
            try {
                // เรียก API ดึงข้อมูลทั้งหมด
                const response = await axios.get(`handle_pm_checksheet.php?action=get_all&ag_id=${AG_ID}`);
                
                if (response.data.success) {
                    const rowData = response.data.data;
                    
                    // อัปเดตข้อมูลลงใน AG Grid
                    if (window.checksheetGridApi) {
                        // ตรวจสอบเวอร์ชันของ AG Grid (รองรับทั้งเวอร์ชันใหม่และเก่า)
                        if (typeof window.checksheetGridApi.setGridOption === 'function') {
                            window.checksheetGridApi.setGridOption('rowData', rowData);
                        } else {
                            window.checksheetGridApi.setRowData(rowData);
                        }
                    }
                } else {
                    console.error('ไม่สามารถโหลดข้อมูลเช็คชีตได้:', response.data.error);
                }
            } catch (error) {
                console.error('เกิดข้อผิดพลาดในการโหลดข้อมูล:', error);
            }
        }

        async function loadMachineTypes() {
			const deptSelect = document.getElementById('checksheet-dept');
		
			try {
				const agId = (typeof AG_ID !== 'undefined' && AG_ID)
					? AG_ID
					: (window.AG_ID || '');
		
				deptSelect.innerHTML = '<option value="">กำลังโหลดข้อมูล...</option>';
		
				if (!agId) {
					deptSelect.innerHTML = '<option value="">ไม่พบข้อมูลหน่วยงาน</option>';
					return;
				}
		
				const response = await axios.get(`get_all_ass_type.php?ag_id=${encodeURIComponent(agId)}`);
				const data = response.data;
		
				deptSelect.innerHTML = '<option value="">-- เลือกประเภทเครื่องจักร --</option>';
		
				if (Array.isArray(data)) {
					data.forEach(item => {
						const option = document.createElement('option');
						option.value = item.id || item.ass_type_id || item.GroupId;
						option.textContent = item.name || item.ass_type_name || item.TGroupName;
						deptSelect.appendChild(option);
					});
				} else {
					deptSelect.innerHTML = '<option value="">ไม่พบข้อมูลประเภทเครื่องจักร</option>';
				}
		
			} catch (error) {
				console.error('ไม่สามารถโหลดข้อมูลประเภทเครื่องจักรได้:', error);
				deptSelect.innerHTML = '<option value="">เกิดข้อผิดพลาดในการโหลดข้อมูล</option>';
			}
		}

        // --- Helper Function: สำหรับดึงข้อมูลและนำไปใส่ใน Form ---
        async function fetchAndPopulateForm(id, isCopy = false) {
            try {
                Swal.fire({ 
                    title: 'กำลังโหลดข้อมูล...', 
                    allowOutsideClick: false, 
                    returnFocus: false,
                    didOpen: () => Swal.showLoading() 
                });
                const response = await axios.get(`handle_pm_checksheet.php?action=get_by_id&id=${id}&ag_id=${AG_ID}`);
                
                if (response.data.success) {
                    const data = response.data.data;
                    
                    // 1. ใส่ข้อมูลฟอร์มหลัก
                    document.getElementById('checksheet-name').value = data.main.name + (isCopy ? ' (Copy)' : '');
                    document.getElementById('checksheet-dept').value = data.main.ass_type_id;
                    document.getElementById('checksheet-effective-date').value = data.main.effective_date;
                    document.getElementById('checksheet-doc-no').value = data.main.doc_no;
                    document.getElementById('checksheet-rev-no').value = data.main.rev_no;
                    document.getElementById('checksheet-estimated-time').value = data.main.estimated_time;

                    let idInput = document.getElementById('checksheet-id');
                    if (idInput) idInput.value = isCopy ? '' : data.main.id;

                    // 2. เตรียมข้อมูลตารางอะไหล่ (แต่ยังไม่ load เข้าตาราง)
                    let spareData = data.spares.map(s => [s.part_name, s.quantity]);
                    if (spareData.length === 0) spareData = [['', '']];

                    // 3. เตรียมข้อมูลตารางจุดตรวจสอบ
                    const typeReverseMap = { 1: 'ผ่าน / ไม่ผ่าน', 2: 'ผ่าน / ไม่ผ่าน / ไม่เกี่ยวข้อง' };
                    const reqReverseMap = { 1: 'บังคับ', 2: 'ไม่บังคับ', 3: 'บังคับเฉพาะกรณีที่ไม่ผ่าน' };

                    let itemData = data.items.map(item => {
                         let imageVal = item.illustration_path ? item.illustration_path : ''; 
                         
                         return [
                            item.check_point,
                            item.standard_text,
                            item.method_text,
                            item.action_abnormal,
                            typeReverseMap[item.check_type_id] || 'ผ่าน / ไม่ผ่าน',
                            imageVal, // ส่ง path ไปให้ renderer
                            reqReverseMap[item.photo_required_id] || 'ไม่บังคับ',
                            item.value_name,
                            item.unit,
                            item.expected_value
                        ];
                    });
                    if (itemData.length === 0) itemData = [['', '', '', '', 'ผ่าน / ไม่ผ่าน', '', 'ไม่บังคับ', '', '', '']];

                    checksheetImageFiles = [];
                    if (data.images && data.images.length > 0) {
                        data.images.forEach(img => {
                            checksheetImageFiles.push({
                                isFromServer: true,
                                url: img.file_path,
                                id: img.id,
                                name: img.file_name
                            });
                        });
                    }
                    window.renderImagePreviews();

                    workInstructionFiles = [];
                    if (data.instructions && data.instructions.length > 0) {
                         data.instructions.forEach(inst => {
                            workInstructionFiles.push({
                                isFromServer: true,
                                url: inst.file_path,
                                id: inst.id,
                                name: inst.file_name
                            });
                        });
                    }
                    window.renderWorkInstructionPreviews();

                    Swal.close();
                    
                    // 4. สลับ Tab ไปหน้าแก้ไขก่อน (เพื่อให้ Container ไม่ถูกซ่อน)
                    const tabButton = document.querySelector('[onclick*="tab-checksheet"]');
                    if (tabButton) {
                        tabButton.click(); 
                    } else {
                        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
                        const targetTab = document.getElementById('tab-checksheet');
                        if (targetTab) targetTab.classList.remove('hidden');
                    }
                    
                    // 5. โหลดข้อมูลเข้า Handsontable และสั่ง Render หลังจากแสดง Tab แล้ว
                    setTimeout(() => {
                        if (window.hotSpareParts) {
                            window.hotSpareParts.loadData(spareData);
                            window.hotSpareParts.render();
                        }
                        if (window.hotChecksheet) {
                            window.hotChecksheet.loadData(itemData);
                            window.hotChecksheet.render();
                        }

                        // const nameInput = document.getElementById('checksheet-name');
                        // if (nameInput) {
                        //     nameInput.focus(); // ให้ Cursor ไปอยู่ที่ช่องชื่อ
                        //     nameInput.scrollIntoView({ behavior: 'smooth', block: 'center' }); // เลื่อนหน้าจอมาที่ช่องนี้แบบนุ่มนวล
                        // }

                    }, 50);
                    
                } else {
                    Swal.fire('ผิดพลาด', response.data.error, 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        }
        
        // --- ส่วนของ Handsontable สำหรับอะไหล่ (Spare Parts) ---
        const hotSpareContainer = document.getElementById('hot-spare-parts');
        if (hotSpareContainer) {
            window.hotSpareParts = new Handsontable(hotSpareContainer, {
                data: [['', '']], 
                colHeaders: ['อะไหล่ที่ใช้', 'จำนวนอะไหล่'],
                rowHeaders: true, 
                contextMenu: true,
                columns: [
                    { type: 'text', placeholder: 'ระบุชื่ออะไหล่' },
                    { type: 'numeric', placeholder: '0' }
                ],
                stretchH: 'all', 
                height: 300, 
                autoWrapRow: true, 
                licenseKey: 'non-commercial-and-evaluation'
            });
        }

        // --- ฟังก์ชันสำหรับเรนเดอร์รูปภาพในตาราง (จากโค้ดเดิม) ---
        function imageRenderer(instance, td, row, col, prop, value, cellProperties) {
            Handsontable.renderers.TextRenderer.apply(this, arguments);
            td.innerHTML = ''; 
            td.className = 'htCenter htMiddle';

            if (value && (value.startsWith('data:image') || value.startsWith('upload') || value.match(/\.(jpeg|jpg|gif|png)$/i) != null)) {
                let imgSrc = value;
                td.innerHTML = `<img src="${imgSrc}" style="width: 30px; height: 30px; object-fit: cover; border-radius: 4px; cursor: pointer; margin: auto;">`;
            } else {
                td.innerHTML = '<span style="color: #94a3b8; font-size: 10px; cursor: pointer;">➕ เพิ่มรูป</span>';
            }
            return td;
        }

        loadMachineTypes();
        loadChecksheets();

        // --- ส่วนของ Handsontable สำหรับเช็คชีตหลัก ---
        const hotChecksheetContainer = document.getElementById('hot-checksheet');
        if (hotChecksheetContainer) {
            window.hotChecksheet = new Handsontable(hotChecksheetContainer, {
                data: [['', '', '', '', 'ผ่าน / ไม่ผ่าน', '', 'ไม่บังคับ', '', '', '']],
                colHeaders: [
                    'จุดที่ตรวจสอบ', 'มาตรฐานการตรวจสอบ', 'วิธีตรวจสอบ/เครื่องมือ', 'ข้อปฏิบัติเมื่อผิดปกติ',
                    'ประเภทจุดตรวจสอบ', 'รูปภาพประกอบ', 'บังคับถ่ายรูป',
                    'ชื่อค่าวัด', 'หน่วยวัด', 'ค่าที่คาดหวัง'
                ],
                height: 400, 
                width: '100%', 
                stretchH: 'all', 
                rowHeaders: true, 
                contextMenu: true, 
                manualColumnResize: true,
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
                                    // ----------------------------------------------------
                                    // โค้ดใหม่: สร้าง Image Object เพื่อบีบอัดสัดส่วนก่อนลงตาราง
                                    // ----------------------------------------------------
                                    const img = new Image();
                                    img.onload = () => {
                                        const canvas = document.createElement('canvas');
                                        const MAX_WIDTH = 800; // กำหนดความกว้างสูงสุด (ปรับได้ตามต้องการ)
                                        const MAX_HEIGHT = 800;
                                        let width = img.width;
                                        let height = img.height;

                                        // คำนวณสัดส่วนของรูปภาพให้คงเดิม
                                        if (width > height) {
                                            if (width > MAX_WIDTH) {
                                                height *= MAX_WIDTH / width;
                                                width = MAX_WIDTH;
                                            }
                                        } else {
                                            if (height > MAX_HEIGHT) {
                                                width *= MAX_HEIGHT / height;
                                                height = MAX_HEIGHT;
                                            }
                                        }

                                        canvas.width = width;
                                        canvas.height = height;
                                        const ctx = canvas.getContext('2d');
                                        ctx.drawImage(img, 0, 0, width, height);

                                        // แปลงกลับเป็น Base64 ในรูปแบบ JPEG และลดคุณภาพเหลือ 70% (0.7)
                                        const compressedBase64 = canvas.toDataURL('image/jpeg', 0.7);
                                        
                                        // นำ Base64 ขนาดจิ๋วไปใส่ใน Cell ของตาราง
                                        this.setDataAtCell(coords.row, coords.col, compressedBase64);
                                    };
                                    img.src = readerEvent.target.result;
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
        }

        const checksheetDatasource = {
            getRows: (params) => {
                // ดึงพารามิเตอร์จาก AG Grid
                const { startRow, endRow, filterModel } = params;

                // เรียก API โดยส่งข้อมูลการแบ่งหน้าและฟิลเตอร์ไปด้วย
                axios.get('handle_pm_checksheet.php', {
                    params: {
                        action: 'get_all',
                        startRow: startRow,
                        endRow: endRow,
                        filterModel: JSON.stringify(filterModel),
                        ag_id: AG_ID
                    }
                })
                .then(response => {
                    if (response.data.success) {
                        // แจ้งข้อมูลให้ Grid ทราบ (ข้อมูลแถว, จำนวนแถวทั้งหมด)
                        params.successCallback(response.data.data, response.data.totalCount);
                        
                        // หลังจากข้อมูลเรนเดอร์ ให้รีเฟรชไอคอน Lucide
                        setTimeout(() => { if(typeof lucide !== 'undefined') lucide.createIcons(); }, 100);
                    } else {
                        params.failCallback();
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    params.failCallback();
                });
            }
        };

        // --- ส่วนของ AG Grid สำหรับแสดงรายการเช็คชีตที่บันทึกไว้ ---
        const gridDiv = document.querySelector('#checksheet-grid');
        if (gridDiv) {
            window.checksheetGridOptions = {
                // คอนฟิกหลักสำหรับ Server-side Pagination
                rowModelType: 'infinite',
                pagination: true,
                paginationPageSize: 10,
                paginationPageSizeSelector: [10, 20, 50, 100],
                cacheBlockSize: 10, // ขนาดการโหลดข้อมูลต่อหนึ่งครั้ง

                columnDefs: [
                    { 
                        headerName: "ชื่อเช็คชีต", field: "name", flex: 1, minWidth: 200,
                        filter: 'agTextColumnFilter', filterParams: { debounceMs: 500 } // หน่วงเวลาพิมพ์ก่อนค้นหา
                    },
                    { headerName: "เลขที่เอกสาร", field: "docNo", filter: 'agTextColumnFilter' },
                    { headerName: "Rev No.", field: "revNo", filter: 'agTextColumnFilter', cellStyle: { textAlign: 'center' } },
                    { headerName: "วันที่มีผล", field: "effectiveDate", filter: 'agDateColumnFilter', cellStyle: { textAlign: 'center' }, valueFormatter: params => window.formatDate(params.value, false) },
                    { headerName: "ประเภทเครื่องจักร", field: "category", filter: 'agTextColumnFilter' },
                    { 
                            headerName: "หัวข้อตรวจ", 
                            field: "itemCount",
                            filter: 'agNumberColumnFilter',
                            cellRenderer: function(params) {
                                const val = params.value || 0;
                                return `<div class="flex items-center h-full justify-center">
                                            <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded-full text-xs">${val} รายการ</span>
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
                                            <span class="bg-amber-50 text-amber-600 px-2 py-1 rounded-full text-xs">${val} รายการ</span>
                                        </div>`;
                            },
                            cellStyle: { textAlign: 'center' }
                        },
                    { 
                        headerName: "จัดการ", field: "id", width: 180, pinned: 'right', sortable: false, filter: false,
                        cellRenderer: (params) => {
                            return `
                                <div class="flex justify-center gap-1.5 pt-1.5">
                                    <button onclick="copyChecksheet(${params.value})" class="text-indigo-500 bg-indigo-50 p-1.5 rounded-md"><i data-lucide="copy" class="w-4 h-4"></i></button>
                                    <button onclick="editChecksheet(${params.value})" class="text-blue-500 bg-blue-50 p-1.5 rounded-md"><i data-lucide="edit-3" class="w-4 h-4"></i></button>
                                    <button onclick="deleteChecksheet(${params.value})" class="text-red-500 bg-red-50 p-1.5 rounded-md"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </div>`;
                        }
                    }
                ],
                defaultColDef: { sortable: true, resizable: true, filter: true },
                onPaginationChanged: (params) => {
                    if (params.newPageSize) {
                        const currentPerPage = params.api.paginationGetPageSize();
                        params.api.setGridOption('cacheBlockSize', currentPerPage);
                        params.api.refreshInfiniteCache();
                    }
                },
                onGridReady: (params) => {
                    window.checksheetGridApi = params.api;
                    // ผูก Datasource เข้ากับ Grid เมื่อพร้อม
                    params.api.setGridOption('datasource', checksheetDatasource);
                }
            };
            agGrid.createGrid(gridDiv, window.checksheetGridOptions);
        }

        // ฟังก์ชันรีเฟรชข้อมูล (เช่น หลังลบหรือแก้ไข)
        window.loadChecksheets = function() {
            if (window.checksheetGridApi) {
                // สั่งให้ Grid โหลดข้อมูลใหม่จาก Server
                window.checksheetGridApi.refreshInfiniteCache();
            }
        };

        // --- ฟังก์ชันการทำงานต่างๆ ---
        
        // เพิ่มแถวในตารางอะไหล่
        window.addSpareRow = function() {
            if (window.hotSpareParts) {
                window.hotSpareParts.alter('insert_row_below', window.hotSpareParts.countRows());
            }
        };

        // เพิ่มแถวในตารางเช็คชีต
        window.addHotRows = function() {
            if (window.hotChecksheet) {
                const count = parseInt(document.getElementById('row-add-count').value) || 1;
                for (let i = 0; i < count; i++) {
                    window.hotChecksheet.alter('insert_row_below', window.hotChecksheet.countRows());
                }
            }
        };

        // --- ฟังก์ชันจัดการรูปภาพจุดตรวจสอบ (Image Previews) ---
        window.previewImages = async function(input) {
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
            window.renderImagePreviews();
        };

        window.renderImagePreviews = async function() {
            const previewContainer = document.getElementById('image-previews');
            if(!previewContainer) return;
            previewContainer.innerHTML = '';

            // 1. ดึง Source ของรูปภาพทั้งหมดให้เสร็จเรียบร้อยก่อน
            const allImagesPromises = checksheetImageFiles.map(async (file) => {
                if (file.isFromServer) {
                    return file.url;
                } else {
                    return new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onload = (e) => resolve(e.target.result);
                        reader.readAsDataURL(file);
                    });
                }
            });

            // รอให้แปลง Base64 ครบทุกรูป (ถ้ามี)
            const allImagesData = await Promise.all(allImagesPromises);

            // ตอนนี้ allImagesData มีรูปครบ 100% แล้ว ค่อยแปลงเป็น JSON String ครั้งเดียว
            const jsonImages = JSON.stringify(allImagesData).replace(/"/g, '&quot;');

            // 2. วนลูปเพื่อสร้าง HTML Element
            allImagesData.forEach((imgSrc, index) => {
                const div = document.createElement('div');
                div.className = 'relative w-20 h-20 group rounded-lg overflow-hidden border border-slate-200 shadow-sm flex-shrink-0';
                
                div.innerHTML = `
                    <img src="${imgSrc}" 
                        class="w-full h-full object-cover cursor-pointer transition-transform group-hover:scale-105" 
                        onclick='if(typeof ImageCarousel !== "undefined") ImageCarousel.open(${jsonImages}, ${index})'>
                    
                    <button type="button" onclick="window.removeChecksheetImage(${index})" 
                            class="absolute top-1 right-1 bg-white/80 text-red-500 rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity shadow hover:bg-white">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                `;
                previewContainer.appendChild(div);
            });
            
            if (typeof lucide !== 'undefined') lucide.createIcons();
        };

        window.removeChecksheetImage = function(index) {
            checksheetImageFiles.splice(index, 1);
            window.renderImagePreviews();
        };


        // --- ฟังก์ชันจัดการไฟล์คู่มือการทำงาน (Work Instructions) ---
        window.handleWorkInstructionsSelect = function(input) {
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
            window.syncFileInput(input);
            
            // เรนเดอร์หน้าจอแสดงผล
            window.renderWorkInstructionPreviews();
        };

        // ฟังก์ชันซิงค์ไฟล์จาก Array เข้าไปใน <input type="file">
        window.syncFileInput = function(input) {
            if (!input) return;
            
            const dataTransfer = new DataTransfer();
            
            workInstructionFiles.forEach(file => {
                if (file instanceof File) {
                    dataTransfer.items.add(file);
                }
            });
            
            input.files = dataTransfer.files;
        };

        window.resetChecksheetForm = function() {
            Swal.fire({
                title: 'ยืนยันการล้างข้อมูล?',
                text: "ข้อมูลที่กรอกไว้ทั้งหมดจะถูกล้างออก",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ใช่, ล้างข้อมูล',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    // 1. ล้างค่าใน Input หลัก
                    document.getElementById('checksheet-name').value = '';
                    document.getElementById('checksheet-id').value = '';
                    document.getElementById('checksheet-dept').value = '';
                    document.getElementById('checksheet-effective-date').value = '';
                    document.getElementById('checksheet-doc-no').value = '';
                    document.getElementById('checksheet-rev-no').value = '00';
                    document.getElementById('checksheet-estimated-time').value = '';

                    // 2. ล้างข้อมูลในตาราง Handsontable
                    if (window.hotSpareParts) {
                        window.hotSpareParts.loadData([['', '']]);
                    }
                    if (window.hotChecksheet) {
                        window.hotChecksheet.loadData([['', '', '', '', 'ผ่าน / ไม่ผ่าน', '', 'ไม่บังคับ', '', '', '']]);
                    }

                    // 3. ล้างไฟล์รูปภาพและคู่มือ
                    checksheetImageFiles = [];
                    workInstructionFiles = [];
                    if (window.renderImagePreviews) window.renderImagePreviews();
                    if (window.renderWorkInstructionPreviews) window.renderWorkInstructionPreviews();
                    
                    // ล้างค่าใน input file
                    const imgInput = document.getElementById('checksheet-images');
                    const workInput = document.getElementById('work-instructions');
                    if(imgInput) imgInput.value = '';
                    if(workInput) workInput.value = '';

                    Swal.fire('ล้างข้อมูลเรียบร้อย', '', 'success');
                }
            });
        };

        // ฟังก์ชันลบไฟล์ที่เลือกไว้ออก (ทีละไฟล์)
        window.removeWorkInstruction = function(index) {
            workInstructionFiles.splice(index, 1); // เอาออกจาก array
            const input = document.getElementById('work-instructions');
            if(input) window.syncFileInput(input); // อัปเดต input ใหม่
            window.renderWorkInstructionPreviews(); // วาดหน้าจอใหม่
        };

        // ฟังก์ชันแสดงป้ายชื่อไฟล์บนหน้าจอ
        window.renderWorkInstructionPreviews = function() {
            const previewContainer = document.getElementById('instruction-previews');
            if(!previewContainer) return;
            previewContainer.innerHTML = ''; // ล้างหน้าจอเดิม

            workInstructionFiles.forEach((file, index) => {
                const fileItem = document.createElement('div');
                fileItem.className = 'text-[12px] bg-slate-100 text-slate-700 px-3 py-1 rounded-full border border-slate-200 flex items-center gap-1.5 shadow-sm';
                
                fileItem.innerHTML = `
                    <i data-lucide="file-text" class="w-3 h-3 text-emerald-600"></i> 
                    <span class="max-w-[150px] truncate" title="${file.name}">${file.name}</span>
                    <button type="button" onclick="window.removeWorkInstruction(${index})" class="ml-1 text-slate-400 hover:text-red-500 focus:outline-none" title="ลบไฟล์นี้">
                        <i data-lucide="x" class="w-3 h-3"></i>
                    </button>
                `;
                previewContainer.appendChild(fileItem);
            });

            // เรนเดอร์ไอคอน Lucide (ไฟล์เอกสาร และ เครื่องหมายกากบาท)
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        };

        // บันทึกเช็คชีต
        window.saveChecksheet = async function() {
            // 1. เคลียร์ Error เดิมที่เคยแสดงผลออกก่อน (กรณีผู้ใช้กดบันทึกซ้ำ)
            document.querySelectorAll('.error-text').forEach(e => e.remove());
            document.querySelectorAll('.error-input').forEach(e => {
                e.classList.remove('border-red-500', 'focus:ring-red-500', 'error-input');
                e.classList.add('border-slate-300', 'focus:ring-sky-500');
            });

            // 2. กำหนดรายการฟิลด์ที่บังคับกรอก
            const requiredFields = [
                { id: 'checksheet-name', name: 'ชื่อเช็คชีต' },
                { id: 'checksheet-dept', name: 'ประเภทเครื่องจักร' },
                { id: 'checksheet-effective-date', name: 'วันที่มีผล' },
                { id: 'checksheet-doc-no', name: 'เลขที่เอกสาร' },
                { id: 'checksheet-rev-no', name: 'Revision No.' },
                { id: 'checksheet-estimated-time', name: 'เวลาที่คาดว่าจะเสร็จ' }
            ];

            let hasError = false;
            let firstErrorInput = null;

            // 3. วนลูปตรวจสอบข้อมูลทีละช่อง
            requiredFields.forEach(field => {
                const inputEl = document.getElementById(field.id);
                if (inputEl && !inputEl.value.trim()) {
                    hasError = true;
                    
                    // เปลี่ยนสีกรอบเป็นสีแดง (ใช้คลาสของ Tailwind)
                    inputEl.classList.remove('border-slate-300', 'focus:ring-sky-500');
                    inputEl.classList.add('border-red-500', 'focus:ring-red-500', 'error-input');
                    
                    // สร้างข้อความแจ้งเตือนสีแดงแทรกไว้ใต้ Input นั้นๆ
                    const errorMsg = document.createElement('div');
                    errorMsg.className = 'text-red-500 text-xs mt-1 error-text';
                    errorMsg.innerText = `กรุณาระบุ ${field.name}`;
                    inputEl.parentNode.appendChild(errorMsg);

                    // เก็บ Input แรกที่ไม่ได้กรอกไว้ เพื่อทำการ Focus
                    if (!firstErrorInput) firstErrorInput = inputEl;
                }
            });

            // 4. ถ้ามี Error ให้ Focus ไปที่ช่องแรกสุดที่ไม่ได้กรอก แล้วหยุดการทำงานทันที
            if (hasError) {
                firstErrorInput.focus();
                return;
            }

            // ==========================================
            // ถ้ากรอกข้อมูลครบถ้วนแล้ว ให้ดึงข้อมูลเตรียมบันทึก
            // ==========================================
            const name = document.getElementById('checksheet-name').value.trim();
            const dept = document.getElementById('checksheet-dept').value;
            const effectiveDate = document.getElementById('checksheet-effective-date').value;
            const docNo = document.getElementById('checksheet-doc-no').value.trim();
            const revNo = document.getElementById('checksheet-rev-no').value.trim();
            const estimatedTime = document.getElementById('checksheet-estimated-time').value;

            // เตรียม Data ให้เป็น FormData
            const formData = new FormData();
            formData.append('action', 'save');
            const checksheetId = document.getElementById('checksheet-id').value;
            if (checksheetId) {
                formData.append('id', checksheetId);
            }
            formData.append('name', name);
            formData.append('ass_type_id', dept);
            formData.append('effective_date', effectiveDate);
            formData.append('doc_no', docNo);
            formData.append('rev_no', revNo);
            formData.append('estimated_time', estimatedTime);
            formData.append('ag_id', AG_ID);
            formData.append('user_id', USER_ID);

            // ดึงข้อมูลจาก Handsontable มาเป็น JSON String
            if (window.hotSpareParts) {
                formData.append('spare_parts', JSON.stringify(window.hotSpareParts.getData()));
            }
            if (window.hotChecksheet) {
                formData.append('checksheet_items', JSON.stringify(window.hotChecksheet.getData()));
            }

            // แนบไฟล์รูปภาพจุดตรวจสอบ
            checksheetImageFiles.forEach((file) => {
                if (!file.isFromServer) {
                    formData.append('checksheet_images[]', file); // ไฟล์ใหม่
                } else {
                    formData.append('existing_files[]', file.id); // แจ้ง Backend ว่าไฟล์นี้ยังเก็บไว้
                }
            });

            // แนบไฟล์คู่มือ
            workInstructionFiles.forEach((file) => {
                if (!file.isFromServer) {
                    formData.append('work_instructions[]', file); // ไฟล์ใหม่
                } else {
                    formData.append('existing_files[]', file.id); // แจ้ง Backend ว่าไฟล์นี้ยังเก็บไว้
                }
            });

            try {
                Swal.fire({ title: 'กำลังบันทึกข้อมูล...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                
                // ส่งข้อมูลไปที่ API
                const response = await axios.post('handle_pm_checksheet.php', formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });

                if (response.data.success) {
                    Swal.fire('บันทึกสำเร็จ', 'ข้อมูลเช็คชีตถูกบันทึกแล้ว', 'success');
                    
                    // รีเฟรชตาราง AG Grid 
                    window.loadChecksheets();
                    
                    // เคลียร์ฟอร์ม
                    document.getElementById('checksheet-id').value = '';
                    document.getElementById('checksheet-name').value = '';
                    document.getElementById('checksheet-dept').value = '';
                    document.getElementById('checksheet-effective-date').value = '';
                    document.getElementById('checksheet-doc-no').value = '';
                    document.getElementById('checksheet-rev-no').value = '00';
                    document.getElementById('checksheet-estimated-time').value = '';
                    
                    checksheetImageFiles = [];
                    workInstructionFiles = [];
                    window.renderImagePreviews();
                    window.renderWorkInstructionPreviews();
                    if(window.hotChecksheet) window.hotChecksheet.loadData([['', '', '', '', 'ผ่าน / ไม่ผ่าน', '', 'ไม่บังคับ', '', '', '']]);
                    if(window.hotSpareParts) window.hotSpareParts.loadData([['', '']]);
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', response.data.error || 'ไม่สามารถบันทึกได้', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        };

        window.editChecksheet = function(id) {
            fetchAndPopulateForm(id, false);
        };

        // 2. Copy (คัดลอก)
        window.copyChecksheet = function(id) {
            fetchAndPopulateForm(id, true);
        };

        // 3. Preview (ดูข้อมูล)
        window.previewChecksheet = async function(id) {
            try {
                Swal.fire({ 
                    title: 'กำลังโหลดข้อมูล...', 
                    allowOutsideClick: false, 
                    didOpen: () => Swal.showLoading() 
                });

                const res = await axios.get(`handle_pm_checksheet.php?action=get_by_id&id=${id}`);
                
                if (res.data.success) {
                    const data = res.data.data;
                    const main = data.main;
                    const items = data.items;
                    const spares = data.spares;
                    const images = data.images;
                    const instructions = data.instructions;

                    const typeMap = { 1: 'ผ่าน / ไม่ผ่าน', 2: 'ผ่าน / ไม่ผ่าน / ไม่เกี่ยวข้อง' };
                    const photoMap = { 1: 'บังคับ', 2: 'ไม่บังคับ', 3: 'บังคับเฉพาะกรณีที่ไม่ผ่าน' };

                    // สร้าง HTML สำหรับ SweetAlert
                    let html = `
                        <div class="text-left text-sm max-h-[75vh] overflow-y-auto pr-2 custom-scrollbar">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 border-b border-slate-200 pb-4 mb-5">
                                <div class="md:col-span-2">
                                    <p class="text-slate-400 text-[10px] uppercase tracking-wider font-bold mb-1">Checksheet Name</p>
                                    <p class="font-bold text-slate-800 text-lg leading-tight">${main.name}</p>
                                </div>
                                <div>
                                    <p class="text-slate-400 text-[10px] uppercase tracking-wider font-bold mb-1">Doc No. / Revision</p>
                                    <p class="font-bold text-slate-700">${main.doc_no || '-'} <span class="text-slate-400 font-normal ml-1">Rev: ${main.rev_no}</span></p>
                                </div>
                                <div>
                                    <p class="text-slate-400 text-[10px] uppercase tracking-wider font-bold mb-1">Effective Date</p>
                                    <p class="font-semibold text-slate-700">${main.effective_date || '-'}</p>
                                </div>
                                <div>
                                    <p class="text-slate-400 text-[10px] uppercase tracking-wider font-bold mb-1">Est. Time</p>
                                    <p class="font-semibold text-slate-700">${main.estimated_time} นาที</p>
                                </div>
                                <div>
                                    <p class="text-slate-400 text-[10px] uppercase tracking-wider font-bold mb-1">Check Points</p>
                                    <p class="font-bold text-sky-600">${items.length} จุดตรวจสอบ</p>
                                </div>
                            </div>
                    `;

                    // ส่วนที่ 1: รายการอะไหล่ (ถ้ามี)
                    if (spares && spares.length > 0) {
                        html += `
                            <div class="mb-6">
                                <h4 class="font-bold text-slate-800 mb-2 flex items-center gap-2 text-sm border-l-4 border-amber-500 pl-2">
                                    รายการอะไหล่ที่ต้องใช้
                                </h4>
                                <div class="border border-slate-200 rounded-lg overflow-hidden">
                                    <table class="w-full text-xs border-collapse">
                                        <thead>
                                            <tr class="bg-slate-50 text-slate-600 border-b border-slate-200">
                                                <th class="p-2 text-left">ชื่ออะไหล่</th>
                                                <th class="p-2 text-center w-24">จำนวน</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${spares.map(s => `
                                                <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50">
                                                    <td class="p-2 text-slate-700">${s.part_name}</td>
                                                    <td class="p-2 text-center font-bold text-slate-900">${s.quantity}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        `;
                    }

                    // ส่วนที่ 2: ตารางจุดตรวจสอบหลัก
                    html += `
                        <div class="mb-6">
                            <h4 class="font-bold text-slate-800 mb-2 flex items-center gap-2 text-sm border-l-4 border-sky-500 pl-2">
                                รายละเอียดจุดตรวจสอบ (Checklist Items)
                            </h4>
                            <div class="overflow-x-auto border border-slate-200 rounded-lg shadow-sm">
                                <table class="w-full text-[11px] border-collapse min-w-[950px]">
                                    <thead class="sticky top-0 z-10">
                                        <tr class="bg-slate-800 text-white font-medium">
                                            <th class="p-2.5 text-center w-10 border-r border-slate-700">#</th>
                                            <th class="p-2.5 text-left w-64 border-r border-slate-700">จุดตรวจสอบ / มาตรฐาน</th>
                                            <th class="p-2.5 text-left border-r border-slate-700">วิธีตรวจสอบ & ข้อปฏิบัติ</th>
                                            <th class="p-2.5 text-center w-28 border-r border-slate-700">ประเภท / รูป</th>
                                            <th class="p-2.5 text-center w-20 border-r border-slate-700">ภาพประกอบ</th>
                                            <th class="p-2.5 text-left w-40">ค่าวัด / ค่าที่คาดหวัง</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-200">
                                        ${items.length === 0 ? '<tr><td colspan="6" class="p-10 text-center text-slate-400">ยังไม่มีข้อมูลจุดตรวจสอบ</td></tr>' : 
                                            items.map((item, idx) => `
                                            <tr class="hover:bg-slate-50 transition">
                                                <td class="p-2.5 text-center text-slate-400 border-r border-slate-100 font-medium">${idx + 1}</td>
                                                <td class="p-2.5 border-r border-slate-100">
                                                    <div class="font-bold text-slate-900 mb-1 leading-tight">${item.check_point}</div>
                                                    <div class="text-slate-500">${item.standard_text}</div>
                                                </td>
                                                <td class="p-2.5 border-r border-slate-100">
                                                    <div class="text-slate-700 italic"><span class="text-slate-400 font-bold not-italic">Method:</span> ${item.method_text}</div>
                                                    ${item.action_abnormal ? `<div class="mt-2 p-1.5 bg-red-50 text-red-600 rounded border border-red-100"><span class="font-bold">🚨 ข้อปฏิบัติเมื่อผิดปกติ:</span> ${item.action_abnormal}</div>` : ''}
                                                </td>
                                                <td class="p-2.5 text-center border-r border-slate-100">
                                                    <div class="mb-1"><span class="px-1.5 py-0.5 bg-sky-50 text-sky-700 border border-sky-200 rounded text-[9px] font-bold">${typeMap[item.check_type_id]}</span></div>
                                                    <div class="text-[9px] text-slate-400">${photoMap[item.photo_required_id]}</div>
                                                </td>
                                                <td class="p-2.5 text-center border-r border-slate-100">
                                                    ${item.illustration_path ? `
                                                        <img src="${item.illustration_path}" class="w-10 h-10 object-cover rounded shadow-sm border border-white cursor-pointer hover:scale-110 transition mx-auto" 
                                                             onclick="window.open('${item.illustration_path}', '_blank')">
                                                    ` : '<span class="text-slate-300">-</span>'}
                                                </td>
                                                <td class="p-2.5 bg-slate-50/30">
                                                    ${item.value_name ? `
                                                        <div class="font-bold text-slate-700">${item.value_name}</div>
                                                        <div class="text-slate-500 font-mono text-[10px]">${item.expected_value} ${item.unit}</div>
                                                    ` : '<span class="text-slate-300">-</span>'}
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;

                    // ส่วนที่ 3: ไฟล์แนบทั่วไป (ถ้ามี)
                    if ((images && images.length > 0) || (instructions && instructions.length > 0)) {
                        html += `
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6 border-t border-slate-200 pt-5">
                                ${images.length > 0 ? `
                                    <div>
                                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">ภาพประกอบรวม</p>
                                        <div class="flex flex-wrap gap-2">
                                            ${images.map(img => `
                                                <img src="${img.file_path}" class="w-16 h-16 object-cover rounded-lg border border-slate-200 cursor-pointer shadow-sm" onclick="window.open('${img.file_path}')">
                                            `).join('')}
                                        </div>
                                    </div>
                                ` : ''}
                                ${instructions.length > 0 ? `
                                    <div>
                                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">ไฟล์อ้างอิง / WI</p>
                                        <div class="space-y-1.5">
                                            ${instructions.map(inst => `
                                                <a href="${inst.file_path}" target="_blank" class="flex items-center gap-2 p-2 bg-emerald-50 rounded-lg border border-emerald-100 hover:bg-emerald-100 transition text-emerald-700 no-underline">
                                                    <i data-lucide="file-text" class="w-4 h-4 text-emerald-600"></i>
                                                    <span class="text-xs font-medium truncate">${inst.file_name}</span>
                                                </a>
                                            `).join('')}
                                        </div>
                                    </div>
                                ` : ''}
                            </div>
                        `;
                    }

                    html += `</div>`; 

                    Swal.fire({
                        title: null,
                        html: html,
                        width: '90%',
                        maxWidth: '1100px',
                        showCloseButton: true,
                        confirmButtonText: 'ปิดหน้าต่าง',
                        confirmButtonColor: '#475569',
                        padding: '1.25rem',
                        didOpen: () => {
                            if (typeof lucide !== 'undefined') lucide.createIcons();
                        }
                    });
                } else {
                    Swal.fire('ผิดพลาด', res.data.error, 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        };

        // 4. Delete (ลบ)
        window.deleteChecksheet = function(id) {
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "ข้อมูล, จุดตรวจสอบ และไฟล์ที่เกี่ยวข้องจะถูกลบทั้งหมดและไม่สามารถกู้คืนได้!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: 'ลบข้อมูล',
                cancelButtonText: 'ยกเลิก'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        Swal.fire({ title: 'กำลังลบ...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                        
                        const formData = new FormData();
                        formData.append('action', 'delete');
                        formData.append('id', id);

                        const response = await axios.post('handle_pm_checksheet.php', formData);
                        
                        if (response.data.success) {
                            Swal.fire('ลบสำเร็จ!', 'ข้อมูลถูกลบออกจากระบบแล้ว', 'success');
                            window.loadChecksheets();
                        } else {
                            Swal.fire('ผิดพลาด', response.data.error, 'error');
                        }
                    } catch (error) {
                        Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                    }
                }
            });
        };
    });
</script>