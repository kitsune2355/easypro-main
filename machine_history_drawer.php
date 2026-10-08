<div id="history-drawer-overlay" onclick="closeHistoryDrawer()" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[60] hidden opacity-0 transition-opacity duration-300"></div>

<div id="history-drawer-panel" class="fixed top-0 right-0 h-full w-full md:w-[600px] bg-slate-50/95 backdrop-blur-xl z-[70] shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col">
    
    <div class="flex-none bg-white/80 backdrop-blur-md border-b border-slate-200/60 px-5 py-4 flex items-center justify-between shadow-sm z-20">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white shadow-md shadow-emerald-500/30">
                <i data-lucide="history" class="w-5 h-5"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-slate-800 tracking-tight">ประวัติการแจ้งซ่อม</h2>
                <p id="hist-machine-name" class="text-xs text-slate-500 font-medium">กำลังโหลด...</p>
            </div>
        </div>
        <button onclick="closeHistoryDrawer()" class="w-12 h-12 rounded-full border border-slate-100 flex items-center justify-center text-slate-400 hover:rotate-90 hover:bg-slate-50 transition-all duration-300">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto custom-scroll p-5">
        
        <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-4 mb-6 flex gap-8 items-start relative overflow-hidden group">
             <div class="absolute top-0 right-0 w-24 h-24 bg-sky-50 rounded-full blur-3xl -z-10 group-hover:scale-150 transition-transform duration-700"></div>
             
             <div id="hist-machine-img" class="w-20 h-20 rounded-xl bg-slate-50 flex items-center justify-center object-cover overflow-hidden flex-none border border-slate-100 shadow-inner">
                <i data-lucide="image" class="w-8 h-8 text-slate-300"></i>
             </div>
             
             <div class="flex-1 w-full min-w-0">
                 <div class="inline-block px-2.5 py-0.5 bg-sky-100 text-sky-700 text-[11px] font-bold rounded-md mb-2">
                     <span id="hist-asset-id">-</span>
                 </div>
                 
                 <div class="grid grid-cols-2 gap-y-2 gap-x-2 text-[12px]">
                    <div>
                        <p class="text-[10px] text-slate-400 mb-0.5">ยี่ห้อ/รุ่น</p>
                        <p id="hist-brand-model" class="text-slate-700 font-semibold truncate">-</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 mb-0.5">ซีเรียลนัมเบอร์</p>
                        <p id="hist-serial" class="text-slate-700 font-semibold truncate">-</p>
                    </div>
                    
                    <div>
                        <p class="text-[10px] text-slate-400 mb-0.5">วันหมดรับประกัน</p>
                        <p id="hist-warranty" class="text-slate-700 font-semibold flex items-center gap-1">
                            <i data-lucide="shield-alert" class="w-3 h-3 text-emerald-500"></i>
                            <span>-</span>
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400 mb-0.5">บริษัทผู้ดูแล (Vendor)</p>
                        <p id="hist-vendor" class="text-slate-700 font-semibold truncate flex items-center gap-1">
                            <i data-lucide="building-2" class="w-3 h-3 text-sky-500"></i>
                            <span>-</span>
                        </p>
                    </div>

                    <div class="col-span-2 mt-0.5">
                        <p class="text-[10px] text-slate-400 mb-0.5">สถานที่ตั้ง</p>
                        <p id="hist-location" class="text-slate-700 font-semibold flex items-center gap-1.5 truncate">
                            <i data-lucide="map-pin" class="w-3 h-3 text-slate-400 flex-none"></i>
                            <span class="truncate">-</span>
                        </p>
                    </div>
                 </div>
             </div>
        </div>

        <div class="flex items-center justify-between mb-4">
            <h3 class="text-[13px] font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="activity" class="w-3.5 h-3.5 text-sky-500"></i> ไทม์ไลน์การแจ้งซ่อม
            </h3>
            <span class="bg-slate-200 text-slate-600 py-0.5 px-2.5 rounded-full text-[11px] font-bold">
                ทั้งหมด <span id="hist-count">0</span>
            </span>
        </div>

        <div class="relative border-l-2 border-slate-200 ml-2.5 md:ml-3 space-y-4 pb-4" id="history-list-container">
            </div>
    </div>
</div>

<script>
    function openHistoryDrawer() {
        const overlay = document.getElementById('history-drawer-overlay');
        const panel = document.getElementById('history-drawer-panel');
        overlay.classList.remove('hidden');
        setTimeout(() => {
            overlay.classList.remove('opacity-0');
            panel.classList.remove('translate-x-full');
        }, 10);
    }

    function closeHistoryDrawer() {
        const overlay = document.getElementById('history-drawer-overlay');
        const panel = document.getElementById('history-drawer-panel');
        overlay.classList.add('opacity-0');
        panel.classList.add('translate-x-full');
        setTimeout(() => overlay.classList.add('hidden'), 300);
    }

    function toggleHistoryAccordion(index) {
        const bodyEl = document.getElementById(`hist-body-${index}`);
        const iconEl = document.getElementById(`hist-chevron-${index}`);
        
        if (bodyEl.classList.contains('hidden')) {
            bodyEl.classList.remove('hidden');
            iconEl.classList.add('rotate-180');
        } else {
            bodyEl.classList.add('hidden');
            iconEl.classList.remove('rotate-180');
        }
    }

    async function viewHistory(dbId) {
        Swal.fire({ title: 'กำลังโหลดข้อมูล...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            const response = await fetch(`handle_machine_info.php?action=get_history&id=${dbId}`);
            const result = await response.json();
            Swal.close();

            if (result.success) {
                const { machine, history } = result.data;

                // 1. ข้อมูลอุปกรณ์พื้นฐาน
                document.getElementById('hist-machine-name').innerText = machine.asset_name || 'ไม่ระบุชื่ออุปกรณ์';
                document.getElementById('hist-asset-id').innerText = machine.ass_code || '-';
                document.getElementById('hist-brand-model').innerText = `${machine.brn_id || '-'} / ${machine.asset_model || '-'}`;
                document.getElementById('hist-serial').innerText = machine.asset_sn || '-';
                
                // 2. ข้อมูลใหม่: วันหมดประกัน และ บริษัทผู้ดูแล
                // ** หมายเหตุ: กรุณาเปลี่ยน warranty_date และ vendor_name ให้ตรงกับชื่อฟิลด์จริงในฐานข้อมูล tb_ass_list ของคุณ **
                document.querySelector('#hist-warranty span').innerText = (machine.warranty_date || machine.asset_warranty) 
                    ? window.formatDate(machine.warranty_date || machine.asset_warranty, false) 
                    : 'ไม่มีข้อมูล';
                document.querySelector('#hist-vendor span').innerText = machine.vendor_name || machine.asset_vendor || 'ไม่มีข้อมูล';

                // 3. ข้อมูลสถานที่
                let locArr = [];
                if(machine.area_name) locArr.push(machine.area_name);
                if(machine.ac_name) locArr.push(machine.ac_name);
                if(machine.ar_name) locArr.push(machine.ar_name);
                document.querySelector('#hist-location span').innerText = locArr.length > 0 ? locArr.join(' > ') : 'ไม่ระบุสถานที่';

                // 4. รูปภาพ
                const imgBox = document.getElementById('hist-machine-img');
                if (machine.fileUpload1) {
                    imgBox.innerHTML = `<img src="${machine.fileUpload1}" class="w-full h-full object-cover">`;
                } else {
                    imgBox.innerHTML = `<i data-lucide="image" class="w-8 h-8 text-slate-300"></i>`;
                }

                // 5. ข้อมูลรายการแจ้งซ่อม (Timeline & Accordion)
                document.getElementById('hist-count').innerText = history.length;
                const listContainer = document.getElementById('history-list-container');
                listContainer.innerHTML = '';

                if (history.length === 0) {
                    listContainer.classList.remove('border-l-2'); 
                    listContainer.innerHTML = `
                        <div class="text-center py-10 bg-white/50 rounded-2xl border border-dashed border-slate-300 ml-[-10px] md:ml-[-12px]">
                            <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i data-lucide="inbox" class="w-6 h-6 text-slate-400"></i>
                            </div>
                            <p class="text-slate-500 text-[13px] font-medium">ยังไม่มีประวัติการแจ้งซ่อม</p>
                        </div>`;
                } else {
                    listContainer.classList.add('border-l-2');
                    let html = '';
                    
                    history.forEach((item, index) => {
                        let statusConfig = { 
                            bg: 'bg-slate-100', text: 'text-slate-600', dot: 'bg-slate-300', 
                            icon: 'clock', border: 'border-slate-200', ring: '#cbd5e140' 
                        };
                        
                        let displayStatus = item.status || 'รอดำเนินการ';

                        if (item.status === 'completed') {
                            statusConfig = { bg: 'bg-emerald-100', text: 'text-emerald-700', dot: 'bg-emerald-500', icon: 'check-circle', border: 'border-emerald-500', ring: '#10b98140' };
                            displayStatus = 'เสร็จสิ้น';
                        } else if (item.status === 'inprogress') {
                            statusConfig = { bg: 'bg-sky-100', text: 'text-sky-700', dot: 'bg-sky-500', icon: 'settings', border: 'border-sky-500', ring: '#0ea5e940' };
                            displayStatus = 'กำลังดำเนินการ';
                        } else if (item.status === 'pending') {
                            statusConfig = { bg: 'bg-amber-100', text: 'text-amber-700', dot: 'bg-amber-500', icon: 'alert-circle', border: 'border-amber-500', ring: '#f59e0b40' };
                            displayStatus = 'รอดำเนินการ';
                        } else if (item.status === 'feedback') {
                            statusConfig = { bg: 'bg-yellow-100', text: 'text-yellow-700', dot: 'bg-yellow-500', icon: 'star', border: 'border-yellow-500', ring: '#ffe60040' };
                            displayStatus = 'รอประเมิน';
                        }

                        const reqDate = window.formatDate(item.created_at || item.report_date, item.report_time) || '-';
                        const procDate = window.formatDate(item.process_date, item.process_time);
                        const compDate = window.formatDate(item.completed_date, item.completed_time);

                        const isExpanded = index === 0; 
                        const bodyHiddenClass = isExpanded ? '' : 'hidden';
                        const chevronRotateClass = isExpanded ? 'rotate-180' : '';

                        html += `
                            <div class="relative pl-5 md:pl-6 group">
                                <div class="absolute -left-[5px] top-4 w-2 h-2 rounded-full ${statusConfig.dot} ring-[3px] ring-slate-50 group-hover:scale-150 transition-transform duration-300 z-10"></div>
                                
                                <div class="bg-white rounded-xl border-y border-r border-l-4 border-slate-200/60 ${statusConfig.border} shadow-sm hover:shadow-md transition-all duration-300 group-hover:border-[color:var(--tw-ring-color)] overflow-hidden" style="--tw-ring-color: ${statusConfig.ring}">
                                    
                                    <div class="p-3.5 cursor-pointer flex justify-between items-center hover:bg-slate-50/50 transition-colors" onclick="toggleHistoryAccordion(${index})">
                                        <div class="flex-1 pr-3 min-w-0">
                                            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wide flex items-center gap-1 ${statusConfig.bg} ${statusConfig.text}">
                                                    <i data-lucide="${statusConfig.icon}" class="w-3 h-3"></i>
                                                    ${displayStatus}
                                                </span>
                                                <span class="text-[11px] text-slate-400 font-medium flex items-center gap-1">
                                                    <i data-lucide="calendar" class="w-3 h-3"></i> ${reqDate}
                                                </span>
                                            </div>
                                            <h4 class="text-[13px] font-bold text-slate-800 leading-snug truncate max-w-full">
                                                <span class="text-[10px] font-normal text-slate-400 leading-none mb-0.5">รายละเอียดปัญหา:</span> ${item.problem_detail || 'ไม่ระบุอาการเสีย'}
                                            </h4>
                                        </div>
                                        
                                        <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 transition-transform duration-300 ${chevronRotateClass} flex-none" id="hist-chevron-${index}">
                                            <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                        </div>
                                    </div>

                                    <div id="hist-body-${index}" class="${bodyHiddenClass} border-t border-slate-100 bg-white">
                                        <div class="p-3.5 pt-2">
                                            
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 bg-slate-50/50 rounded-lg p-2.5 text-[12px] border border-slate-100 mb-3 mt-1">
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-500 flex-none">
                                                        <i data-lucide="user" class="w-3 h-3"></i>
                                                    </div>
                                                    <div class="truncate">
                                                        <p class="text-[10px] text-slate-400 leading-none mb-0.5">ผู้แจ้ง</p>
                                                        <p class="font-semibold text-slate-700 truncate">${item.name || '-'}</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 flex-none">
                                                        <i data-lucide="wrench" class="w-3 h-3"></i>
                                                    </div>
                                                    <div class="truncate">
                                                        <p class="text-[10px] text-slate-400 leading-none mb-0.5">ผู้ซ่อม</p>
                                                        <p class="font-semibold text-slate-700 truncate">${item.completed_by || '-'}</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="border-t border-slate-100 pt-3">
                                                <div class="grid grid-cols-3 gap-1.5">
                                                    <div class="relative">
                                                        <div class="text-[10px] text-slate-400 mb-0.5 flex items-center gap-1 font-medium">
                                                            <i data-lucide="file-plus" class="w-2.5 h-2.5"></i> แจ้งซ่อม
                                                        </div>
                                                        <div class="text-[11px] font-semibold text-slate-700">${reqDate}</div>
                                                    </div>
                                                    
                                                    <div class="relative">
                                                        <div class="text-[10px] ${procDate ? 'text-sky-500' : 'text-slate-400'} mb-0.5 flex items-center gap-1 font-medium">
                                                            <i data-lucide="settings-2" class="w-2.5 h-2.5"></i> ดำเนินการ
                                                        </div>
                                                        <div class="text-[11px] font-semibold ${procDate ? 'text-sky-700' : 'text-slate-400'}">
                                                            ${procDate ? procDate : '<span class="text-[10px] font-normal">รอ</span>'}
                                                        </div>
                                                    </div>

                                                    <div class="relative">
                                                        <div class="text-[10px] ${compDate ? 'text-emerald-500' : 'text-slate-400'} mb-0.5 flex items-center gap-1 font-medium">
                                                            <i data-lucide="check-circle-2" class="w-2.5 h-2.5"></i> เสร็จ
                                                        </div>
                                                        <div class="text-[11px] font-semibold ${compDate ? 'text-emerald-700' : 'text-slate-400'}">
                                                            ${compDate ? compDate : '-'}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            ${item.completed_solution ? `
                                            <div class="mt-3 pt-2.5 border-t border-dashed border-slate-200">
                                                <div class="flex gap-2 bg-amber-50/50 p-2 rounded-lg">
                                                    <div class="mt-0.5 w-4 h-4 rounded-full bg-amber-100 flex items-center justify-center flex-none">
                                                        <i data-lucide="lightbulb" class="w-2.5 h-2.5 text-amber-600"></i>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <span class="text-slate-500 block text-[10px] font-bold mb-0.5 uppercase tracking-wide">วิธีแก้ไขปัญหา:</span>
                                                        <span class="font-medium text-[12px] text-slate-800 break-words">${item.completed_solution}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    listContainer.innerHTML = html;
                }

                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
                
                openHistoryDrawer();

            } else {
                Swal.fire('เกิดข้อผิดพลาด', result.error || 'ไม่สามารถดึงข้อมูลได้', 'error');
            }
        } catch (error) {
            console.error(error);
            Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
        }
    }
</script>