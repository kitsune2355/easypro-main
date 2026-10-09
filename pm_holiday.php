<?php 
@session_start();
include "config_ctrl/checksession.php"; 
?>

<div id="tab-holiday" class="tab-content hidden grid grid-cols-1 lg:grid-cols-12 gap-4 h-auto relative">
    
    <div id="holiday-drawer-backdrop" onclick="window.toggleHolidayDrawer()" class="fixed inset-0 bg-slate-900/50 z-40 hidden lg:hidden opacity-0 transition-opacity duration-300"></div>

    <div id="holiday-drawer" class="fixed inset-y-0 left-0 z-50 w-80 max-w-[calc(100%-3rem)] bg-white p-5 shadow-2xl border-r border-slate-200 transform -translate-x-full transition-transform duration-300 flex flex-col h-full overflow-y-auto lg:relative lg:translate-x-0 lg:col-span-4 xl:col-span-3 lg:w-auto lg:max-w-none lg:shadow-sm lg:rounded-2xl lg:border lg:h-full">
        
        <div class="mb-5 shrink-0 flex justify-between items-start">
            <div>
                <h3 class="text-lg font-semibold text-slate-800">จัดการวันหยุดและเงื่อนไข</h3>
                <p class="text-sm text-slate-500 mt-1">ระบบจะตรวจสอบเงื่อนไขนี้ก่อนลงตาราง PM</p>
            </div>
            <button type="button" onclick="togglePmPanel('holiday', true)" class="hidden lg:inline-flex items-center justify-center w-8 h-8 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors" title="ซ่อนแผงจัดการวันหยุด">
                <i class="fas fa-angles-left"></i>
            </button>
            <button type="button" onclick="window.toggleHolidayDrawer()" class="lg:hidden p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="holiday-form" onsubmit="window.handleHolidaySubmit(event)" class="space-y-5 flex flex-col flex-1 justify-between">
            <input type="hidden" id="holiday-id" value="">

            <div class="grid grid-cols-1 gap-4">
                <div class="col-span-1">
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">รูปแบบการหยุด</label>
                    <select id="holiday-repeat" onchange="window.toggleHolidayInput()" class="w-full border border-slate-200 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none bg-slate-50/50">
                        <option value="once">ครั้งเดียว</option>
                        <option value="yearly">ทุกปี</option>
                        <option value="daily">ทุกๆวัน</option>
                    </select>
                </div>

                <div class="col-span-1" id="box-date-input">
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">ระบุวันที่</label>
                    <input type="date" id="holiday-date" class="w-full border border-slate-200 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none bg-slate-50/50">
                </div>

                <div class="col-span-1 hidden" id="box-day-select">
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">เลือกวันในสัปดาห์</label>
                    <select id="holiday-day-of-week" class="w-full border border-slate-200 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none bg-slate-50/50">
                        <option value="อา.">วันอาทิตย์</option>
                        <option value="จ.">วันจันทร์</option>
                        <option value="อ.">วันอังคาร</option>
                        <option value="พ.">วันพุธ</option>
                        <option value="พฤ.">วันพฤหัสบดี</option>
                        <option value="ศ.">วันศุกร์</option>
                        <option value="ส.">วันเสาร์</option>
                    </select>
                </div>

                <div class="col-span-1">
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">การจัดการเมื่อตรงวันหยุด</label>
                    <select id="holiday-handle" class="w-full border border-slate-200 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none bg-white">
                        <option value="next_working_day">เลื่อนไป 1 วันทำการถัดไป</option>
                        <option value="prev_working_day">เลื่อนมาทำก่อน 1 วันทำการ</option>
                        <option value="skip">หยุดทำ</option>
                        <option value="none">ไม่หยุดทำ (รันงานตามปกติ)</option>
                    </select>
                </div>

                <div class="col-span-1">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-500 mb-1.5 uppercase tracking-wider">ชื่อวันหยุด / รายละเอียด</label>
                        <input type="text" id="holiday-name" placeholder="เช่น วันแรงงาน, วันปิดปรับปรุง" required class="w-full border border-slate-200 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none bg-white">
                    </div>
                    
                    <div class="flex items-center justify-between mt-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="holiday-status" checked class="sr-only peer">
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-500"></div>
                            <span class="ml-2 text-sm font-medium text-slate-600">เปิดใช้งาน</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="pt-4 mt-auto flex flex-col gap-2">
                <button type="submit" id="submit-btn" class="w-full px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2 shadow-sm shadow-sky-100">
                    <i data-lucide="save" class="w-4 h-4"></i> <span id="submit-btn-text">เพิ่มรายการ</span>
                </button>
                <button type="button" id="cancel-edit-btn" onclick="window.cancelEdit()" class="hidden w-full px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition-all flex items-center justify-center gap-2">
                    <i data-lucide="x" class="w-4 h-4"></i> ยกเลิกการแก้ไข
                </button>
            </div>
        </form>
    </div>

    <div class="lg:col-span-8 xl:col-span-9 bg-white p-5 rounded-2xl shadow-sm border border-slate-200 flex flex-col min-h-[500px] lg:min-h-0 lg:h-full overflow-hidden">
        
        <div class="flex items-center justify-between mb-4 shrink-0">
            <div class="flex items-center gap-2">
                <button type="button" onclick="togglePmPanel('holiday', false)" class="pm-expand-btn items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200 text-sm font-medium" title="แสดงแผงจัดการวันหยุด">
                    <i class="fas fa-angles-right"></i> จัดการวันหยุด
                </button>
                <h3 class="text-lg font-semibold text-slate-800">รายการวันหยุดที่บันทึกไว้</h3>
            </div>
            <button type="button" onclick="window.toggleHolidayDrawer(true)" class="lg:hidden px-3 py-2 bg-sky-50 text-sky-600 hover:bg-sky-100 rounded-lg text-sm font-medium flex items-center gap-1.5 transition-colors">
                <i data-lucide="plus" class="w-4 h-4"></i> เพิ่ม/จัดการ
            </button>
        </div>
        
        <div class="border border-slate-200 rounded-lg overflow-hidden flex-1 flex flex-col min-h-0">
            <div id="holiday-grid" class="ag-theme-alpine w-full h-full flex-1"></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let gridApi;
        window.holidays = [];

        const AG_ID = '<?php echo isset($sess_user_agency_es) ? $sess_user_agency_es : ""; ?>';
        const USER_ID = '<?php echo isset($sess_user_id) ? $sess_user_id : ""; ?>';

        // 🌟 [NEW] ฟังก์ชันสำหรับเปิด-ปิด Drawer บน Mobile
        window.toggleHolidayDrawer = function(forceOpen = null) {
            const drawer = document.getElementById('holiday-drawer');
            const backdrop = document.getElementById('holiday-drawer-backdrop');
            
            const isOpen = !drawer.classList.contains('-translate-x-full');
            const shouldOpen = forceOpen !== null ? forceOpen : !isOpen;

            if (shouldOpen) {
                drawer.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
                setTimeout(() => backdrop.classList.remove('opacity-0'), 10);
            } else {
                drawer.classList.add('-translate-x-full');
                backdrop.classList.add('opacity-0');
                setTimeout(() => backdrop.classList.add('hidden'), 300);
            }
        };

        window.toggleHolidayInput = function() {
            const repeatType = document.getElementById('holiday-repeat').value;
            const boxDate = document.getElementById('box-date-input');
            const boxDay = document.getElementById('box-day-select');
            
            if (repeatType === 'daily') {
                boxDate.classList.add('hidden');
                boxDay.classList.remove('hidden');
            } else {
                boxDate.classList.remove('hidden');
                boxDay.classList.add('hidden');
            }
        };

        const columnDefs = [
            { 
                headerName: "รูปแบบ / วันที่", 
                field: "date",
                flex: 1,
                filter: 'agDateColumnFilter',
                filterParams: {
                    browserDatePicker: true,
                    suppressAndOrCondition: true,
                    filterOptions: ['inRange', 'equals'], 
                    defaultOption: 'inRange'
                },
                cellRenderer: params => {
                    if (!params.data) return `<div class="text-slate-400 text-sm animate-pulse">กำลังโหลด...</div>`;
                    const h = params.data;
                    let dateDisplay = h.type === 'daily' ? `ทุกวัน ${h.date}` : h.date;
                    return `<div class="font-medium text-sky-600">${window.formatDate(dateDisplay, false)}</div>`;
                }
            },
            { 
                headerName: "รูปแบบการหยุด", 
                field: "type",
                flex: 1.5,
                filter: 'agSetColumnFilter', 
                filterParams: {
                    values: ['once', 'yearly', 'daily'], // ข้อมูลที่จะให้เลือก
                    valueFormatter: params => {
                        const typeMapping = { 'once': 'ครั้งเดียว', 'yearly': 'ทุกปี', 'daily': 'ทุกๆวัน' };
                        return typeMapping[params.value] || params.value;
                    }
                },
                valueFormatter: params => {
                    if (!params.value) return ''; 
                    const typeMapping = { 'once': 'ครั้งเดียว', 'yearly': 'ทุกปี', 'daily': 'ทุกๆวัน' };
                    return typeMapping[params.value] || params.value;
                }
            },
            { 
                headerName: "ชื่อวันหยุด / รายละเอียด", 
                field: "name", 
                flex: 1.5,
                filter: 'agTextColumnFilter',
            },
            { 
                headerName: "การจัดการเมื่อตรงวันหยุด", 
                field: "handle",
                flex: 1,
                filter: 'agSetColumnFilter', 
                filterParams: {
                    values: ['next_working_day', 'prev_working_day', 'skip', 'none'],
                    valueFormatter: params => {
                        const mapping = {
                            'next_working_day': 'เลื่อนไปวันถัดไป',
                            'prev_working_day': 'เลื่อนมาทำก่อน',
                            'skip': 'หยุดทำ',
                            'none': 'ไม่หยุดทำ'
                        };
                        return mapping[params.value] || params.value;
                    }
                },
                valueFormatter: params => {
                    if (!params.value) return '';
                    const mapping = { 'next_working_day': 'เลื่อนไปวันถัดไป', 'prev_working_day': 'เลื่อนมาทำก่อน', 'skip': 'หยุดทำ', 'none': 'ไม่หยุดทำ' };
                    return mapping[params.value] || params.value;
                }
            },
            { 
                headerName: "สถานะ", 
                field: "status", 
                width: 120,
                cellClass: 'text-center',
                filter: 'agSetColumnFilter', 
                filterParams: {
                    values: [true, false], 
                    valueFormatter: params => params.value ? 'เปิดใช้งาน' : 'ปิดใช้งาน'
                },
                cellRenderer: params => {
                    if (!params.data || !params.data.id) {
                        return ''; 
                    }

                    const isActive = params.data.status == 1 || params.data.status === true;
                    const isChecked = isActive ? 'checked' : '';
                    const statusText = isActive ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
                    const badgeClass = isActive ? 'text-emerald-600' : 'text-slate-400';

                    return `
                        <div class="flex justify-center items-center gap-2 py-1 h-full">
                            <label class="relative inline-flex items-center cursor-pointer group">
                                <input type="checkbox" ${isChecked} class="sr-only peer" 
                                    onchange="window.toggleHolidayStatus('${params.data.id}')">
                                
                                <div class="w-8 h-4 bg-slate-200 rounded-full peer 
                                    peer-checked:bg-emerald-500/30 
                                    transition-all duration-300"></div>
                                
                                <div class="absolute left-[2px] top-[2px] w-3 h-3 bg-white border border-slate-300 rounded-full 
                                    transition-all duration-300 
                                    peer-checked:translate-x-4 peer-checked:border-emerald-500 peer-checked:bg-emerald-500"></div>
                            </label>
                            
                            <span class="text-xs font-bold ${badgeClass}">
                                ${statusText}
                            </span>
                        </div>
                    `;
                }
            },
            { 
                headerName: "จัดการ", 
                width: 130, 
                cellClass: 'text-center',
                filter: false,
                cellRenderer: params => {
                    if (!params.data) return ''; 
                    const div = document.createElement('div');
                    div.className = 'flex justify-center gap-1 mt-1';
                    div.innerHTML = `
                        <button onclick="window.editHoliday('${params.data.id}')" class="p-1.5 text-slate-400 hover:text-amber-500 transition-colors" title="แก้ไข">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                        </button>
                        <button onclick="window.deleteHoliday('${params.data.id}')" class="p-1.5 text-slate-400 hover:text-red-500 transition-colors" title="ลบ">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    `;
                    setTimeout(() => { if (window.lucide) lucide.createIcons({ root: div }); }, 0);
                    return div;
                }
            }
        ];

        const gridOptions = {
            columnDefs: columnDefs,
            rowData: [], 
            rowModelType: 'infinite',
            pagination: true,
            paginationPageSize: 10,
            cacheBlockSize: 10,
            paginationPageSizeSelector: [10, 20, 50, 100],
            defaultColDef: {
                resizable: true,
                sortable: false,
                filter: false
            },
            onPaginationChanged: (event) => {
                if (event.newPageSize) {
                    const api = event.api;
                    const newPageSize = api.paginationGetPageSize();
                    const currentBlockSize = api.getGridOption ? api.getGridOption('cacheBlockSize') : gridOptions.cacheBlockSize;

                    if (currentBlockSize !== newPageSize) {
                        if (api.setGridOption) {
                            api.setGridOption('cacheBlockSize', newPageSize);
                        } else {
                            api.updateGridOptions({ cacheBlockSize: newPageSize });
                        }
                        api.setGridOption ? api.setGridOption('datasource', holidayDataSource) : api.setDatasource(holidayDataSource);
                    }
                }
            },
            onFirstDataRendered: () => setTimeout(() => { if(window.lucide) lucide.createIcons(); }, 50),
            onRowDataUpdated: () => setTimeout(() => { if(window.lucide) lucide.createIcons(); }, 50)
        };

        const gridDiv = document.querySelector('#holiday-grid');
        gridApi = agGrid.createGrid(gridDiv, gridOptions);

        // มือถือ: การ์ดวันหยุด
        (function () {
            const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
            const TYPE = { once: 'ครั้งเดียว', yearly: 'ทุกปี', daily: 'ทุกๆวัน' };
            const HANDLE = { next_working_day: 'เลื่อนไปวันถัดไป', prev_working_day: 'เลื่อนมาทำก่อน', skip: 'หยุดทำ', none: 'ไม่หยุดทำ' };
            window.PmGridCards(gridApi, gridDiv.parentElement, {
                empty: 'ยังไม่มีวันหยุดที่บันทึกไว้',
                card: h => {
                    const on = h.status == 1 || h.status === true;
                    const date = window.formatDate(h.type === 'daily' ? `ทุกวัน ${h.date}` : h.date, false);
                    return `<div class="pm-mc ${on ? '' : 'is-off'}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-sm font-bold text-sky-600">${esc(date)}</div>
                                <div class="pm-mc-title mt-0.5">${esc(h.name || '-')}</div>
                            </div>
                            <span class="pm-mc-chip bg-slate-100 text-slate-600 shrink-0">${esc(TYPE[h.type] || h.type || '-')}</span>
                        </div>
                        <div class="mt-2 text-xs text-slate-600 flex items-center gap-1.5"><i data-lucide="calendar-clock" class="w-3.5 h-3.5 text-amber-500"></i>เมื่อตรงวันหยุด: <b class="font-semibold text-slate-700">${esc(HANDLE[h.handle] || h.handle || '-')}</b></div>
                        <div class="flex items-center justify-between gap-2 mt-2.5 pt-2.5 border-t border-slate-100">
                            <label class="pm-switch ${on ? 'text-emerald-600' : 'text-slate-400'}"><input type="checkbox" ${on ? 'checked' : ''} onchange="window.toggleHolidayStatus('${esc(h.id)}')"><i></i>${on ? 'เปิดใช้งาน' : 'ปิดใช้งาน'}</label>
                            <div class="flex gap-1.5">
                                <button type="button" class="pm-mc-btn" title="แก้ไข" onclick="window.editHoliday('${esc(h.id)}')"><i data-lucide="edit" class="w-4 h-4"></i></button>
                                <button type="button" class="pm-mc-btn !text-red-500" title="ลบ" onclick="window.deleteHoliday('${esc(h.id)}')"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                            </div>
                        </div>
                    </div>`;
                }
            });
        })();

        const holidayDataSource = {
            getRows: async (params) => {
                const limit = params.endRow - params.startRow;
                const offset = params.startRow;
                
                // ดึงข้อมูล Filter ที่ผู้ใช้พิมพ์/เลือก
                const filterModel = params.filterModel;
                
                // จัดเตรียม URL Parameters
                let queryParams = new URLSearchParams({
                    action: 'get_all',
                    ag_id: AG_ID,
                    limit: limit,
                    offset: offset
                });

                // ตรวจสอบและแนบค่า Filter ไปที่ API
                if (filterModel.date) {
                    if (filterModel.date.type === 'inRange') {
                        if (filterModel.date.dateFrom) queryParams.append('date_from', filterModel.date.dateFrom);
                        if (filterModel.date.dateTo) queryParams.append('date_to', filterModel.date.dateTo);
                    } else {
                        if (filterModel.date.dateFrom) queryParams.append('date_from', filterModel.date.dateFrom);
                    }
                }
                if (filterModel.name?.filter) queryParams.append('filter_name', filterModel.name.filter);
                if (filterModel.type?.values) queryParams.append('filter_type', filterModel.type.values.join(','));
                if (filterModel.handle?.values) queryParams.append('filter_handle', filterModel.handle.values.join(','));
                if (filterModel.status?.values) queryParams.append('filter_status', filterModel.status.values.join(','));

                try {
                    // ใช้ queryParams.toString() ในการแนบตัวแปรต่อท้าย
                    const response = await axios.get(`handle_pm_holiday.php?${queryParams.toString()}`);
                    
                    if (response.data.success) {
                        const rowsThisPage = response.data.data;
                        const lastRow = response.data.total_rows;
                        params.successCallback(rowsThisPage, lastRow);
                        setTimeout(() => { if(window.lucide) lucide.createIcons(); }, 100);
                    } else {
                        console.error("Fetch error:", response.data.error);
                        params.failCallback();
                    }
                } catch (error) {
                    console.error("API Connection error:", error);
                    params.failCallback();
                }
            }
        };

        function getHolidayFromGrid(id) {
            let holiday = null;
            gridApi.forEachNode(node => {
                if (node.data && node.data.id == id) {
                    holiday = node.data;
                }
            });
            return holiday;
        }

        window.fetchHolidays = async function() {
            try {
                const response = await axios.get(`handle_pm_holiday.php?action=get_all&ag_id=${AG_ID}`);
                if (response.data.success) {
                    window.holidays = response.data.data;
                    gridApi.setGridOption('datasource', holidayDataSource);
                    setTimeout(() => { if(window.lucide) lucide.createIcons(); }, 100);
                } else {
                    console.error("Fetch error:", response.data.error);
                }
            } catch (error) {
                console.error("API Connection error:", error);
            }
        };

        window.toggleHolidayStatus = async function(id) {
            const holiday = getHolidayFromGrid(id);
            if (!holiday) return;

            const payload = {
                id: holiday.id,
                ag_id: AG_ID,
                type: holiday.type,
                date: holiday.date,
                name: holiday.name,
                handle: holiday.handle,
                status: !holiday.status,
                user_id: USER_ID
            };

            try {
                const response = await axios.post('handle_pm_holiday.php?action=save', payload);
                if (response.data.success) {
                    window.fetchHolidays(); 
                } else {
                    Swal.fire('ข้อผิดพลาด', response.data.error, 'error');
                }
            } catch (error) {
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
            }
        };

        window.editHoliday = function(id) {
            const holiday = getHolidayFromGrid(id);
            if (!holiday) return;

            // 🌟 [NEW] สั่งเปิด Drawer ทันทีเมื่อกดแก้ไข (เผื่อกรณีผู้ใช้ใช้มือถือ)
            if (window.innerWidth < 1024) {
                window.toggleHolidayDrawer(true);
            }

            document.getElementById('holiday-id').value = holiday.id;
            document.getElementById('holiday-repeat').value = holiday.type;
            
            window.toggleHolidayInput(); 

            if (holiday.type === 'daily') {
                document.getElementById('holiday-day-of-week').value = holiday.date;
                document.getElementById('holiday-date').value = '';
            } else {
                document.getElementById('holiday-date').value = holiday.date;
            }

            document.getElementById('holiday-handle').value = holiday.handle;
            document.getElementById('holiday-name').value = holiday.name;
            document.getElementById('holiday-status').checked = holiday.status; 

            document.getElementById('submit-btn').classList.replace('bg-sky-600', 'bg-amber-500');
            document.getElementById('submit-btn').classList.replace('hover:bg-sky-700', 'hover:bg-amber-600');
            document.getElementById('submit-btn').classList.replace('shadow-sky-100', 'shadow-amber-100');
            document.getElementById('submit-btn-text').innerText = 'บันทึกการแก้ไข';
            document.getElementById('cancel-edit-btn').classList.remove('hidden');
        };

        window.cancelEdit = function() {
            document.getElementById('holiday-form').reset();
            document.getElementById('holiday-id').value = '';
            
            document.getElementById('submit-btn').classList.replace('bg-amber-500', 'bg-sky-600');
            document.getElementById('submit-btn').classList.replace('hover:bg-amber-600', 'hover:bg-sky-700');
            document.getElementById('submit-btn').classList.replace('shadow-amber-100', 'shadow-sky-100');
            document.getElementById('submit-btn-text').innerText = 'เพิ่มรายการ';
            document.getElementById('cancel-edit-btn').classList.add('hidden');
            
            window.toggleHolidayInput();
        };

        window.deleteHoliday = function(id) {
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "คุณต้องการลบวันหยุดนี้ออกจากระบบใช่หรือไม่",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#cbd5e1',
                confirmButtonText: 'ใช่, ลบเลย',
                cancelButtonText: 'ยกเลิก'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const response = await axios.post('handle_pm_holiday.php?action=delete', { id: id });
                        if (response.data.success) {
                            if(document.getElementById('holiday-id').value == id) {
                                window.cancelEdit();
                            }
                            window.fetchHolidays(); 
                            Swal.fire({
                                title: 'ลบแล้ว!',
                                text: 'ลบรายการวันหยุดสำเร็จ',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('ข้อผิดพลาด', response.data.error, 'error');
                        }
                    } catch (error) {
                        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                    }
                }
            });
        };

        window.handleHolidaySubmit = async function(e) {
            e.preventDefault();
            
            const editId = document.getElementById('holiday-id').value;
            const repeatType = document.getElementById('holiday-repeat').value;
            const handleType = document.getElementById('holiday-handle').value;
            const name = document.getElementById('holiday-name').value;
            const statusVal = document.getElementById('holiday-status').checked; 
            
            let dateVal = '';
            if (repeatType === 'daily') {
                dateVal = document.getElementById('holiday-day-of-week').value;
            } else {
                dateVal = document.getElementById('holiday-date').value;
                if(!dateVal) {
                    Swal.fire('ข้อผิดพลาด', 'กรุณาระบุวันที่', 'error');
                    return;
                }
            }

            const payload = {
                id: editId,
                ag_id: AG_ID,
                type: repeatType,
                date: dateVal,
                name: name,
                handle: handleType,
                status: statusVal,
                user_id: USER_ID
            };

            try {
                const response = await axios.post('handle_pm_holiday.php?action=save', payload);
                
                if (response.data.success) {
                    Swal.fire({
                        title: editId ? 'แก้ไขสำเร็จ' : 'บันทึกสำเร็จ',
                        text: response.data.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    window.cancelEdit(); 
                    window.fetchHolidays(); 
                    
                    // 🌟 [NEW] สั่งปิด Drawer ทันทีเมื่อบันทึกเสร็จ
                    if (window.innerWidth < 1024) {
                        window.toggleHolidayDrawer(false);
                    }

                    if(window.refreshCalendarEvents) window.refreshCalendarEvents();
                } else {
                    Swal.fire('ข้อผิดพลาด', response.data.error, 'error');
                }
            } catch (error) {
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
                console.error("API Submission Error:", error);
            }
        };

        window.fetchHolidays();
    });
</script>