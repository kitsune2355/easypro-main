<div id="tab-history" class="tab-content hidden space-y-4">
    <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200">
        <div class="flex justify-between items-center mb-4 border-b pb-4">
            <h3 class="text-lg font-semibold text-slate-800">ประวัติการบำรุงรักษาล่าสุด</h3>
            <div class="flex gap-2">
                <input type="text" id="history-search" placeholder="ค้นหาประวัติ..." class="border border-slate-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                <button class="bg-slate-100 p-2 px-3 rounded-lg hover:bg-slate-200 text-sm flex gap-2 items-center">
                    <i data-lucide="filter" class="w-4 h-4"></i> คัดกรอง
                </button>
            </div>
        </div>

        <div class="border border-slate-200 rounded-lg overflow-hidden">
            <div id="historyGrid" class="ag-theme-alpine w-full" style="height: 500px;"></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // ข้อมูลจำลอง (Mock Data)
        if (!window.pmHistory || window.pmHistory.length === 0) {
            window.pmHistory = [
                { id: '1', date: '15/03/2026', machine: 'AHU-01 (แอร์รวม)', detail: 'เปลี่ยนฟิลเตอร์, ตรวจสอบมอเตอร์และสายพาน', worker: 'สมชาย ช่างยนต์', result: 'ผ่าน', doc: 'PM-2603-001' },
                { id: '2', date: '14/03/2026', machine: 'Chiller-01 (ระบบทำความเย็น)', detail: 'เช็คน้ำยาทำความเย็น, ทำความสะอาดคอยล์', worker: 'วิชัย การไฟฟ้า', result: 'ผ่าน (มีข้อสังเกต)', doc: 'PM-2603-002' },
                { id: '3', date: '10/03/2026', machine: 'Air Compressor #1', detail: 'ถ่ายน้ำมันเครื่อง, เปลี่ยนไส้กรองอากาศ', worker: 'ทีม Outsource', result: 'ไม่ผ่าน (รอซ่อม)', doc: 'PM-2603-003' }
            ];
        }

        // 1. สร้าง Custom Cell Renderer สำหรับ "ผลตรวจ" (วาด Badge สี)
        const resultCellRenderer = (params) => {
            if (!params.value) return '';
            let badgeClass = 'bg-emerald-100 text-emerald-700';
            
            if (params.value.includes('ไม่ผ่าน')) {
                badgeClass = 'bg-red-100 text-red-700';
            } else if (params.value.includes('สังเกต')) {
                badgeClass = 'bg-amber-100 text-amber-700';
            }
            
            return `<div class="flex items-center justify-center h-full">
                        <span class="px-2 py-1 rounded-md text-xs font-medium leading-none ${badgeClass}">${params.value}</span>
                    </div>`;
        };

        // 2. สร้าง Custom Cell Renderer สำหรับ "ปุ่มดูเอกสาร"
        const docCellRenderer = (params) => {
            if (!params.value) return '';
            // ใช้ SVG ตรงๆ แทน data-lucide เพื่อป้องกันปัญหา Icon หายเมื่อ ag-Grid ทำการ Scroll (DOM Virtualization)
            const fileIconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>`;
            
            return `<div class="flex items-center justify-center h-full">
                        <button onclick="console.log('Open Doc: ${params.value}')" class="text-sky-600 hover:text-sky-800 text-xs px-2 py-1 flex items-center justify-center gap-1 bg-sky-50 rounded border border-sky-200 hover:bg-sky-100 transition-colors" title="ดูเอกสาร">
                            ${fileIconSvg} ${params.value}
                        </button>
                    </div>`;
        };

        // 3. กำหนดค่าคอลัมน์ให้ ag-Grid (Column Definitions)
        const gridOptions = {
            columnDefs: [
                { headerName: "วันที่ทำ PM", field: "date", width: 120, valueFormatter: params => window.formatDate(params.value) },
                { headerName: "รหัสเครื่องจักร", field: "machine", flex: 1, minWidth: 150 },
                { headerName: "รายละเอียดการทำงาน", field: "detail", flex: 1.5, minWidth: 200 },
                { headerName: "ผู้ปฏิบัติงาน", field: "worker", width: 150 },
                { 
                    headerName: "ผลตรวจ", 
                    field: "result", 
                    width: 140, 
                    cellRenderer: resultCellRenderer 
                },
                { 
                    headerName: "เอกสาร", 
                    field: "doc", 
                    width: 150, 
                    cellRenderer: docCellRenderer 
                }
            ],
            rowData: window.pmHistory,
            // ตั้งค่าพื้นฐานให้ทุกคอลัมน์ จัดเรียงได้ ย่อขยายได้
            defaultColDef: {
                sortable: true,
                resizable: true,
                filter: true,
                wrapText: true,
                autoHeight: true
            },
            rowHeight: 48, // ปรับความสูงของแถวให้ดูโปร่งขึ้น
            pagination: true, // เปิดใช้งานแบ่งหน้า
            paginationPageSize: 10,
        };

        // 4. สั่งสร้าง ag-Grid ลงใน HTML
        const gridDiv = document.querySelector('#historyGrid');
        const gridApi = agGrid.createGrid(gridDiv, gridOptions);

        // 5. เชื่อมต่อช่องค้นหา (Search) เข้ากับ ag-Grid Quick Filter
        const searchInput = document.getElementById('history-search');
        searchInput.addEventListener('input', function() {
            gridApi.setGridOption('quickFilterText', this.value);
        });

        // รีเฟรช Icon ของ Lucide สำหรับส่วนอื่นๆ ของหน้า (เช่นปุ่มคัดกรอง)
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>