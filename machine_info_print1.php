<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Asset Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="../es/logo - easypro2.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        :root {
            --card-width: 6.2cm;
            --card-height: 3.5cm;
            --qr-size: 2.2cm;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
        }

        .preview-area {
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 20px 20px;
        }

        /* --------------------------
           Label Card Core Styles
           -------------------------- */
        .label-card {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 10px;
            box-sizing: border-box;
            page-break-inside: avoid;
            display: flex;
            gap: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            width: var(--card-width);
            height: var(--card-height);
            transition: all 0.3s ease;
        }

        /* เมื่อเป็นแนวตั้ง (Vertical Mode) */
        .is-vertical .label-card {
            flex-direction: column; 
            align-items: center;
            text-align: center;
            padding: 12px 10px;
        }

        .qr-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: var(--qr-size);
            height: var(--qr-size);
            flex-shrink: 0;
            border: 1px solid #f1f5f9;
            padding: 4px;
            border-radius: 6px;
            background: #fff;
        }

        .info-container {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
            width: 100%;
        }

        .info-header {
            border-bottom: 1.5px solid #3b82f6;
            padding-bottom: 4px;
            margin-bottom: 6px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        /* ปรับ Header เมื่อเป็นแนวตั้ง: ให้ชื่อกับโลโก้เรียงกันคนละบรรทัด */
        .is-vertical .info-header {
            flex-direction: column; /* ขึ้นบรรทัดใหม่ */
            align-items: center;    /* จัดกึ่งกลาง */
            gap: 4px;               /* ระยะห่างระหว่างชื่อกับโลโก้ */
            border-bottom-width: 1px;
        }

        .info-title {
            font-weight: 700;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .info-detail {
            font-size: 9px;
            color: #475569;
            line-height: 1.3;
            display: flex;
            align-items: flex-start;
            gap: 4px;
        }

        .is-vertical .info-detail {
            justify-content: center;
        }

        .info-label {
            font-weight: 600;
            color: #64748b;
        }

        .logo-img {
            width: 35px;
            height: auto;
            object-fit: contain;
        }

        @media print {
            html, body { 
                background: white !important; 
                padding: 0 !important; 
                margin: 0 !important;
                height: auto !important; 
                overflow: visible !important;
                display: block !important;
                /* zoom: 60%;  */
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            main.preview-area {
                height: auto !important;
                overflow: visible !important;
                display: block !important;
                background: none !important; 
                padding: 0 !important; 
                margin: 0 !important;
            }

            .no-print { display: none !important; }
            
            .label-card {
                box-shadow: none !important;
                border: 1px dashed #cbd5e1 !important;
                margin: 0 !important;
                page-break-inside: avoid;
            }

            #labels-container {
                gap: 0 !important;
                display: flex !important;
                flex-wrap: wrap !important;
                justify-content: flex-start !important; 
                align-items: flex-start !important;
                width: 100% !important; 
            }

            @page { margin: 1cm; }
        }
    </style>
</head>
<body class="h-screen flex flex-col md:flex-row overflow-hidden">

    <aside class="no-print w-full md:w-80 bg-white border-r border-slate-200 flex flex-col shadow-xl z-10">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <h1 class="text-xl font-bold text-slate-800 flex items-center gap-3">
                <div class="p-2 bg-sky-600 rounded-lg shadow-lg">
                    <i data-lucide="qr-code" class="w-5 h-5 text-white"></i>
                </div>
                QR Code
            </h1>
            <p id="status-text" class="text-xs text-slate-500 mt-2 italic">พร้อมพิมพ์ข้อมูล...</p>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-8">
            <div class="space-y-4">
                <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="maximize" class="w-4 h-4"></i> ขนาดป้าย
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[10px] text-slate-500 ml-1">กว้าง (cm)</label>
                        <input type="number" id="input-w" step="0.1" value="6.2" oninput="applyCustomSize()" 
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[10px] text-slate-500 ml-1">สูง (cm)</label>
                        <input type="number" id="input-h" step="0.1" value="3.5" oninput="applyCustomSize()" 
                               class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between text-[10px] text-slate-500">
                        <label>ขนาด QR Code</label>
                        <span id="qr-val">2.2 cm</span>
                    </div>
                    <input type="range" id="input-qr" min="1.0" max="6.0" step="0.1" value="2.2" 
                           oninput="document.getElementById('qr-val').innerText = this.value + ' cm'; applyCustomSize();" 
                           class="w-full h-1.5 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-sky-600">
                </div>
            </div>

            <div class="space-y-3 pt-4">
                <button id="toggleLayoutBtn" onclick="toggleLayout()" 
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50 active:scale-95 shadow-sm transition-all">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                    <span id="toggleText">สลับเป็นแนวตั้ง</span>
                </button>
                
                <button onclick="window.print()" 
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-sm font-bold transition-all shadow-lg active:scale-95">
                    <i data-lucide="printer" class="w-5 h-5"></i>
                    พิมพ์
                </button>
            </div>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto preview-area p-8">
        <div id="labels-container" class="flex flex-wrap justify-center md:justify-start gap-6 print:gap-0">
            </div>
    </main>

    <script>
        lucide.createIcons();
        let isVertical = false;

        function applyCustomSize() {
            const w = document.getElementById('input-w').value;
            const h = document.getElementById('input-h').value;
            const qr = document.getElementById('input-qr').value;

            const root = document.documentElement;
            root.style.setProperty('--card-width', `${w}cm`);
            root.style.setProperty('--card-height', `${h}cm`);
            root.style.setProperty('--qr-size', `${qr}cm`);
        }

        function toggleLayout() {
            isVertical = !isVertical;
            const container = document.getElementById('labels-container');
            const btnText = document.getElementById('toggleText');
            const inputW = document.getElementById('input-w');
            const inputH = document.getElementById('input-h');

            if (isVertical) {
                container.classList.add('is-vertical');
                btnText.innerText = 'สลับเป็นแนวนอน';
                inputW.value = 4.5;
                inputH.value = 6.0; 
            } else {
                container.classList.remove('is-vertical');
                btnText.innerText = 'สลับเป็นแนวตั้ง';
                inputW.value = 6.2;
                inputH.value = 3.5;
            }
            
            applyCustomSize();
        }

        async function loadPrintData() {
            const urlParams = new URLSearchParams(window.location.search);
            const ag_id = urlParams.get('ag_id') || '';
            const search = urlParams.get('search') || '';
            let filterModel = urlParams.get('filterModel') || '';

            try {
                if (filterModel.includes('%')) {
                    filterModel = decodeURIComponent(filterModel);
                }
            } catch(e) {}

            try {
                const url = `handle_machine_info.php?action=get_all&export=true&ag_id=${ag_id}&search=${encodeURIComponent(search)}&filterModel=${encodeURIComponent(filterModel)}`;
                const response = await fetch(url);
                const result = await response.json();

                if (result && result.rows) {
                    renderLabels(result.rows);
                    document.getElementById('status-text').innerHTML = `พบ <span class="text-sky-600 font-bold">${result.rows.length}</span> รายการ`;
                }
            } catch (error) {
                document.getElementById('status-text').innerText = `Error: ไม่สามารถดึงข้อมูลได้`;
            }
        }

        function renderLabels(data) {
            const container = document.getElementById('labels-container');
            container.innerHTML = '';

            data.forEach((item, index) => {
                const card = document.createElement('div');
                card.className = 'label-card';

                const loc = [item.location, item.floor, item.room].filter(v => v && v !== '-').join(' / ');

                card.innerHTML = `
                    <div class="qr-container" id="qrcode-${index}"></div>
                    <div class="info-container">
                        <div>
                            <div class="info-header">
                                <div class="info-title uppercase">${item.machine_name || 'Machine'}</div>
                                <img src="pro.png" class="logo-img" onerror="this.style.display='none'">
                            </div>
                            <div class="info-detail"><span class="info-label text-sky-600">ID:</span> <span class="font-bold font-mono">${item.asset_id || '-'}</span></div>
                            <div class="info-detail"><span class="info-label">S/N:</span> ${item.serial || '-'}</div>
                        </div>
                        <div class="info-detail mt-auto pt-1.5 text-slate-500 items-start">
                            <i data-lucide="building" class="w-3 h-3 flex-shrink-0"></i>
                            <span class="leading-tight break-words">
                                ${item.ag_contract || 'N/A'}
                            </span>
                        </div>
                        <div class="info-detail mt-1 text-slate-500 items-start">
                            <i data-lucide="map-pin" class="w-3 h-3 flex-shrink-0"></i>
                            <span class="leading-tight break-words">
                                ${loc || 'N/A'}
                            </span>
                        </div>
                    </div>
                `;

                container.appendChild(card);

                if (item.asset_id) {
                    new QRCode(document.getElementById(`qrcode-${index}`), {
                        text: item.asset_id,
                        width: 150,
                        height: 150,
                        colorDark : "#0f172a",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.H
                    });
                }
            });
            lucide.createIcons();
        }

        window.addEventListener('DOMContentLoaded', () => {
            loadPrintData();
            applyCustomSize();
        });
    </script>
</body>
</html>