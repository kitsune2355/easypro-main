<?php
@session_start();
include "config_ctrl/connect.php";
include "config_ctrl/checksession.php";
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การแจ้งเตือนทั้งหมด</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { prompt: ['Prompt', 'sans-serif'] },
                    colors: {
                        'easy-primary': '#006b9f',
                        'easy-secondary': ' #004a6f ',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f3f4f6; }
        .material-symbols-rounded { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
    </style>
</head>
<body class="p-4 h-screen flex flex-col">

    <div class="max-w-8xl mx-auto w-full bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-full overflow-hidden">
        
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-gray-50 shrink-0">
            <div class="flex items-center gap-3">
                <div class="bg-blue-100 text-blue-600 p-2 rounded-lg">
                    <span class="material-symbols-rounded text-[24px]">notifications</span>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">การแจ้งเตือนทั้งหมด</h2>
                    <p class="text-sm text-gray-500 mt-0.5">รายการแจ้งเตือนระบบของคุณ</p>
                </div>
            </div>
            
            <button id="mark-all-read-btn" class="flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:text-blue-600 transition-all shadow-sm">
                <span class="material-symbols-rounded text-[18px]">done_all</span>
                อ่านทั้งหมด
            </button>
        </div>

        <div id="full-notification-list" class="flex-1 overflow-y-auto p-2 bg-white">
            <div id="loading-indicator" class="flex flex-col justify-center items-center h-32 gap-3">
                <div class="animate-spin rounded-full h-8 w-8 border-4 border-easy-primary border-t-transparent"></div>
                <span class="text-gray-500 text-sm">กำลังโหลดข้อมูล...</span>
            </div>
        </div>
        
        <div class="p-4 border-t border-gray-100 bg-gray-50 shrink-0 text-center">
            <div id="append-loading" class="hidden text-sm text-gray-500">
                <div class="animate-spin rounded-full h-4 w-4 border-2 border-easy-primary border-t-transparent inline-block align-middle mr-2"></div>
                กำลังโหลดข้อมูลเพิ่มเติม...
            </div>
            <p id="no-more-text" class="hidden text-sm text-gray-400">ไม่มีการแจ้งเตือนเพิ่มเติม</p>
        </div>

    </div>

    <script>
        let currentPage = 1;
        let isLoading = false;
        let hasMore = true;
        let lastDateGroup = '';

        async function fetchAllNotifications(isAppend = false) {
            if (isLoading) return;
            if (isAppend && !hasMore) return;

            isLoading = true;
            const listContainer = document.getElementById('full-notification-list');
            const loadingIndicator = document.getElementById('loading-indicator');
            const appendLoading = document.getElementById('append-loading');
            const noMoreText = document.getElementById('no-more-text');

            if (!isAppend) {
                currentPage = 1;
                lastDateGroup = ''; // รีเซ็ตกลุ่มวันที่เมื่อเริ่มโหลดใหม่
                listContainer.innerHTML = '';
                listContainer.appendChild(loadingIndicator);
                loadingIndicator.classList.remove('hidden');
                appendLoading.classList.add('hidden');
                noMoreText.classList.add('hidden');
            } else {
                appendLoading.classList.remove('hidden');
            }

            try {
                const response = await fetch(`notification_api.php?action=get&page=${currentPage}`);
                const json = await response.json();
                
                if (!isAppend) {
                    loadingIndicator.classList.add('hidden');
                }

                if (json.status === 'success') {
                    const data = json.data;
                    
                    if (data.length === 0 && !isAppend) {
                        listContainer.innerHTML = `
                            <div class="flex flex-col items-center justify-center h-48 text-gray-400">
                                <span class="material-symbols-rounded text-5xl mb-2 opacity-50">notifications_off</span>
                                <p>ไม่มีการแจ้งเตือน</p>
                            </div>
                        `;
                        return;
                    }

                    data.forEach(item => {
                        // 🌟 แทรกหัวข้อจัดกลุ่ม หากกลุ่มวันที่ของข้อมูลไม่ตรงกับรายการก่อนหน้า
                        if (item.date_group !== lastDateGroup) {
                            const groupHeader = document.createElement('div');
                            groupHeader.className = 'px-3 py-1.5 mt-4 mb-2 text-sm font-bold text-gray-700 bg-gray-100 rounded-lg inline-block';
                            groupHeader.innerText = item.date_group;
                            listContainer.appendChild(groupHeader);
                            lastDateGroup = item.date_group; // อัปเดตกลุ่มล่าสุด
                        }

                        // โค้ดสร้าง notifItem เดิม
                        const notifItem = document.createElement('div');
                        notifItem.className = `group flex flex-col md:flex-row md:items-center justify-between p-4 mb-2 rounded-xl border transition-all cursor-pointer ${item.isRead ? 'bg-white border-gray-100 hover:border-gray-200' : 'bg-blue-50/50 border-blue-100 shadow-sm'}`;
                        
                        notifItem.innerHTML = `
                            <div class="flex items-start gap-4">
                                <div class="mt-1 flex-shrink-0">
                                    ${!item.isRead ? 
                                        '<span class="flex h-3 w-3 relative"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span><span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span></span>' 
                                        : 
                                        '<span class="h-3 w-3 rounded-full bg-gray-300 block"></span>'
                                    }
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-800 group-hover:text-easy-primary transition-colors">${item.title}</h4>
                                    <p class="text-sm text-gray-500 mt-1 whitespace-pre-line">${item.detail}</p>
                                </div>
                            </div>
                            <div class="mt-3 md:mt-0 md:ml-4 flex-shrink-0 text-left md:text-right">
                                <span class="text-xs font-medium text-gray-400 bg-gray-50 px-2.5 py-1 rounded-md border border-gray-100">${item.time}</span>
                            </div>
                        `;

                        notifItem.addEventListener('click', async () => {
                            if (!item.isRead) {
                                await markAsRead(item.id);
                            }
                            
                            if (window.parent && window.parent.navigate) {
                                switch(item.type) {
                                    case 'repair_request':
                                    case 'repair_completed':
                                        sessionStorage.setItem('_edit_id', item.related_id);
                                        window.parent.navigate('maintenance_info.php');
                                        break;
                                    case 'meter_exceeded':
                                        if (item.url && item.url !== '#') {
                                            window.parent.navigate(item.url); 
                                        } else {
                                            window.parent.navigate('meter_info.php');
                                        }
                                        break;
                                    case 'low_stock':
                                            window.parent.navigate('stock.php');
                                        break;
                                    case 'pm_advance_alert':
                                    case 'pm_today_alert':
                                        window.parent.navigate('pm.php'); 
                                        break;
                                    default:
                                        window.parent.navigate('dashboard.php');
                                        break;
                                }
                            }
                        });

                        listContainer.appendChild(notifItem);
                    });

                    if (data.length < 10) {
                        hasMore = false;
                        appendLoading.classList.add('hidden');
                        if (currentPage > 1 || data.length > 0) {
                            noMoreText.classList.remove('hidden');
                        }
                    } else {
                        currentPage++;
                        appendLoading.classList.add('hidden');
                        noMoreText.classList.add('hidden');
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                if (!isAppend) {
                    loadingIndicator.classList.add('hidden');
                    listContainer.innerHTML = '<div class="p-8 text-center text-red-500">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
                }
            } finally {
                isLoading = false;
                appendLoading.classList.add('hidden');
            }
        }

        async function markAsRead(id) {
            try {
                await fetch('notification_api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'read', notification_id: id })
                });
                
                fetchAllNotifications(false);
                
                if (window.parent && typeof window.parent.fetchNotifications === 'function') {
                    window.parent.fetchNotifications(false);
                }
            } catch (error) {
                console.error('Error marking as read:', error);
            }
        }

        // ดักจับ Event Scroll ของลิสต์รายการแจ้งเตือนแทนการกดปุ่ม
        const listContainer = document.getElementById('full-notification-list');
        listContainer.addEventListener('scroll', () => {
            // เมื่อเลื่อนลงมาจนเกือบถึงขอบล่างสุด (ห่างจากล่างสุด 30px) ให้ดึงข้อมูลเพิ่มทันที
            if (listContainer.scrollTop + listContainer.clientHeight >= listContainer.scrollHeight - 30) {
                fetchAllNotifications(true);
            }
        });

        document.getElementById('mark-all-read-btn').addEventListener('click', async () => {
            if(confirm('ยืนยันการทำเครื่องหมายว่าอ่านทั้งหมด?')) {
                await markAsRead('all');
            }
        });

        // Initial Load
        document.addEventListener('DOMContentLoaded', () => {
            fetchAllNotifications(false);
        });
    </script>
</body>
</html>