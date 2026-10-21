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

        /* โหลดข้อมูล: skeleton ป้าย + แถบความคืบหน้า */
        .label-skeleton { background: #fff; border: 1px solid #e2e8f0; border-radius: 4px; padding: 10px; display: flex; gap: 12px; width: var(--card-width); height: var(--card-height); box-sizing: border-box; }
        .is-vertical .label-skeleton { flex-direction: column; align-items: center; }
        .sk { background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 37%, #f1f5f9 63%); background-size: 400% 100%; animation: sk-shine 1.3s ease infinite; border-radius: 4px; }
        @keyframes sk-shine { 0% { background-position: 100% 50%; } 100% { background-position: 0 50%; } }
        .progress-track { height: 6px; background: #e2e8f0; border-radius: 999px; overflow: hidden; }
        .progress-bar { height: 100%; width: 0; background: linear-gradient(90deg, #0ea5e9, #0369a1); border-radius: 999px; transition: width .2s; }
        .progress-bar.indeterminate { width: 35%; animation: pb-move 1.1s ease-in-out infinite; }
        @keyframes pb-move { 0% { transform: translateX(-100%); } 100% { transform: translateX(300%); } }
        button:disabled { opacity: .55; cursor: not-allowed; }
        /* ป้ายที่อยู่นอกจอ เบราว์เซอร์ข้ามการวาด (หลักพันป้ายเลื่อนได้ลื่น) — ตอนพิมพ์กลับมาวาดครบ */
        @media screen {
            #labels-container .label-card { content-visibility: auto; contain-intrinsic-size: var(--card-width) var(--card-height); }
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
            position: relative;
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
            <p id="status-text" class="text-xs text-slate-500 mt-2 italic">กำลังดึงข้อมูลเครื่องจักร...</p>
            <div id="progress-wrap" class="mt-2">
                <div class="progress-track"><div id="progress-bar" class="progress-bar indeterminate"></div></div>
                <p id="progress-text" class="text-[11px] text-slate-400 mt-1"></p>
            </div>
        </div>

        <!-- เครื่องเยอะ => แบ่งพิมพ์เป็นชุด (พิมพ์ทีละหลายพันป้ายเบราว์เซอร์จะช้าหรือค้าง) -->
        <div id="batch-box" class="hidden px-6 pt-5 space-y-2">
            <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="layers" class="w-4 h-4"></i> แบ่งพิมพ์เป็นชุด
            </h2>
            <div class="grid grid-cols-[1fr_auto] gap-2">
                <select id="batch-select" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 outline-none"></select>
                <select id="batch-size" title="จำนวนป้ายต่อชุด" class="px-2 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-sky-500 outline-none">
                    <option value="200">200/ชุด</option>
                    <option value="500" selected>500/ชุด</option>
                    <option value="1000">1000/ชุด</option>
                </select>
            </div>
            <p class="text-[11px] text-slate-400 leading-relaxed">พิมพ์ทีละชุด แล้วเลือกชุดถัดไป (ป้ายขนาดปกติ ~21 ป้ายต่อหน้า A4)</p>
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
                
                <button id="print-btn" onclick="window.print()" disabled
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-sm font-bold transition-all shadow-lg active:scale-95">
                    <i data-lucide="printer" class="w-5 h-5"></i>
                    <span id="print-btn-text">กำลังเตรียมป้าย...</span>
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

            showSkeleton();
            setProgress(null, 'กำลังดึงข้อมูลจากเซิร์ฟเวอร์...');
            try {
                const url = `handle_machine_info.php?action=get_all&export=true&ag_id=${ag_id}&search=${encodeURIComponent(search)}&filterModel=${encodeURIComponent(filterModel)}`;
                const response = await fetch(url);
                const result = await response.json();

                if (result && result.rows) {
                    ALL_ROWS = result.rows;
                    document.getElementById('status-text').innerHTML = `พบ <span class="text-sky-600 font-bold">${fmt(ALL_ROWS.length)}</span> รายการ`;
                    buildBatchOptions();
                    renderCurrentBatch();
                } else {
                    throw new Error('no rows');
                }
            } catch (error) {
                document.getElementById('labels-container').innerHTML = '';
                document.getElementById('status-text').innerText = `Error: ไม่สามารถดึงข้อมูลได้`;
                setProgress(0, 'โหลดไม่สำเร็จ — ลองรีเฟรชหน้านี้อีกครั้ง');
            }
        }

        // ---------- โหลด/แบ่งชุด/ความคืบหน้า ----------
        let ALL_ROWS = [];
        let renderToken = 0;            // เปลี่ยนชุดระหว่างสร้างป้าย => ยกเลิกรอบเก่า
        const fmt = n => Number(n).toLocaleString('th-TH');
        const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        function setProgress(ratio, text) {
            const bar = document.getElementById('progress-bar');
            document.getElementById('progress-wrap').classList.toggle('hidden', ratio === 1 && !text);
            bar.classList.toggle('indeterminate', ratio === null);
            bar.style.width = ratio === null ? '' : Math.round(ratio * 100) + '%';
            document.getElementById('progress-text').textContent = text || '';
        }

        function setPrintReady(ready, text) {
            const btn = document.getElementById('print-btn');
            btn.disabled = !ready;
            document.getElementById('print-btn-text').textContent = text;
        }

        function showSkeleton(count = 12) {
            const card = `<div class="label-skeleton" aria-hidden="true">
                <div class="sk" style="width:var(--qr-size);height:var(--qr-size);flex-shrink:0"></div>
                <div style="flex:1;display:flex;flex-direction:column;gap:7px;width:100%">
                    <div class="sk" style="height:10px;width:85%"></div>
                    <div class="sk" style="height:8px;width:60%"></div>
                    <div class="sk" style="height:8px;width:50%"></div>
                    <div class="sk" style="height:8px;width:70%;margin-top:auto"></div>
                </div></div>`;
            document.getElementById('labels-container').innerHTML = card.repeat(count);
            setPrintReady(false, 'กำลังเตรียมป้าย...');
        }

        function batchSize() { return parseInt(document.getElementById('batch-size').value, 10) || 500; }

        function buildBatchOptions() {
            const size = batchSize();
            const total = ALL_ROWS.length;
            const box = document.getElementById('batch-box');
            box.classList.toggle('hidden', total <= 200);   // เครื่องไม่เยอะ ไม่ต้องแบ่งชุด
            const sel = document.getElementById('batch-select');
            const n = Math.max(1, Math.ceil(total / size));
            sel.innerHTML = Array.from({ length: n }, (_, i) => {
                const from = i * size + 1, to = Math.min(total, (i + 1) * size);
                return `<option value="${i}">ชุดที่ ${i + 1} · ป้าย ${fmt(from)}–${fmt(to)}</option>`;
            }).join('');
        }

        function renderCurrentBatch() {
            const total = ALL_ROWS.length;
            const batched = total > 200;
            const size = batched ? batchSize() : total;
            const idx = batched ? parseInt(document.getElementById('batch-select').value, 10) || 0 : 0;
            const rows = ALL_ROWS.slice(idx * size, idx * size + size);
            renderLabels(rows, idx * size);
        }

        document.getElementById('batch-select').addEventListener('change', () => { showSkeleton(); renderCurrentBatch(); });
        document.getElementById('batch-size').addEventListener('change', () => { showSkeleton(); buildBatchOptions(); renderCurrentBatch(); });

        // สร้างลิงก์ไปยังหน้าประวัติเครื่องจักร (สำหรับให้ QR Code สแกนแล้วเปิดหน้านี้)
        function buildHistoryUrl(assetId) {
            const urlParams = new URLSearchParams(window.location.search);
            const ag_id = urlParams.get('ag_id') || '';
            const url = new URL('machine_history_view.php', window.location.href);
            url.searchParams.set('asset_id', assetId);
            if (ag_id) url.searchParams.set('ag_id', ag_id);
            return url.toString();
        }

        // ไอคอนเล็กในป้าย (SVG ของ lucide ใส่ตรง ๆ — เร็วกว่าให้ lucide แปลง <i> ทีละหมื่นตัว)
        const SVG_ATTR = 'xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3 flex-shrink-0"';
        const ICONS = {
            'building': `<svg ${SVG_ATTR}><rect width="16" height="20" x="4" y="2" rx="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/></svg>`,
            'map-pin': `<svg ${SVG_ATTR}><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>`
        };
        const iconSvg = name => ICONS[name] || '';

        function labelHtml(item, i) {
            const loc = [item.location, item.floor, item.room].filter(v => v && v !== '-').join(' / ');
            return `
                <div class="qr-container" id="qrcode-${i}"></div>
                <div class="info-container">
                    <div>
                        <div class="info-header">
                            <div class="info-title uppercase">${esc(item.machine_name || 'Machine')}</div>
                            <img src="pro.png" class="logo-img" onerror="this.style.display='none'">
                        </div>
                        <div class="info-detail"><span class="info-label text-sky-600">ID:</span> <span class="font-bold font-mono">${esc(item.asset_id || '-')}</span></div>
                        <div class="info-detail"><span class="info-label">S/N:</span> ${esc(item.serial || '-')}</div>
                    </div>
                    <div class="info-detail mt-auto pt-1.5 text-slate-500 items-start">
                        ${iconSvg('building')}
                        <span class="leading-tight break-words">${esc(item.ag_contract || 'N/A')}</span>
                    </div>
                    <div class="info-detail mt-1 text-slate-500 items-start">
                        ${iconSvg('map-pin')}
                        <span class="leading-tight break-words">${esc(loc || 'N/A')}</span>
                    </div>
                </div>`;
        }

        // สร้างป้ายทีละก้อน (คืนจังหวะให้เบราว์เซอร์ระหว่างก้อน) => หน้าไม่ค้าง และแสดงความคืบหน้าได้
        // (เดิมสร้าง QR ทั้งหมดในลูปเดียว — เครื่องหลักพันหน้าจะค้างจนกว่าจะเสร็จ)
        function renderLabels(data, offset = 0) {
            const token = ++renderToken;
            const container = document.getElementById('labels-container');
            const total = data.length;
            const BUDGET = 40;   // ms ต่อรอบ แล้วคืนจังหวะให้หน้าจอ
            let i = 0, cleared = false;

            if (!total) {
                container.innerHTML = '<p class="text-slate-400 text-sm">ไม่พบเครื่องจักรตามเงื่อนไขที่เลือก</p>';
                setProgress(1, '');
                setPrintReady(false, 'ไม่มีป้ายให้พิมพ์');
                return;
            }
            setPrintReady(false, 'กำลังเตรียมป้าย...');
            setProgress(0, `กำลังสร้างป้าย 0 / ${fmt(total)}`);

            const step = () => {
                if (token !== renderToken) return;   // ผู้ใช้เปลี่ยนชุดแล้ว
                if (!cleared) { container.innerHTML = ''; cleared = true; }
                const frag = document.createDocumentFragment();
                const t0 = performance.now();
                // สร้าง QR ในการ์ดก่อนนำขึ้นหน้า (ยังไม่อยู่ในหน้า => ไม่ต้องจัดเลย์เอาต์ทุกใบ) แล้วใส่ทีเดียวต่อรอบ
                while (i < total && performance.now() - t0 < BUDGET) {
                    const item = data[i];
                    const card = document.createElement('div');
                    card.className = 'label-card';
                    card.innerHTML = labelHtml(item, offset + i);
                    frag.appendChild(card);
                    if (item.asset_id) makeQr(card.querySelector('.qr-container'), item.asset_id);
                    i++;
                }
                container.appendChild(frag);

                if (i < total) {
                    setProgress(i / total, `กำลังสร้างป้าย ${fmt(i)} / ${fmt(total)}`);
                    nextTick(step);
                } else {
                    setProgress(1, `สร้างป้ายครบ ${fmt(total)} ป้าย`);
                    setPrintReady(true, total === ALL_ROWS.length ? 'พิมพ์' : `พิมพ์ชุดนี้ (${fmt(total)} ป้าย)`);
                }
            };
            nextTick(step);
        }

        // คืนจังหวะให้เบราว์เซอร์แล้วทำต่อทันที (setTimeout ถูกหน่วงได้ถึง ~1 วินาทีเมื่องานยาว)
        const tickChannel = new MessageChannel();
        const tickQueue = [];
        tickChannel.port1.onmessage = () => { const fn = tickQueue.shift(); if (fn) fn(); };
        function nextTick(fn) { tickQueue.push(fn); tickChannel.port2.postMessage(0); }

        // QR เป็น SVG (path เดียวต่อป้าย) — ใช้ qrcodejs คำนวณตาราง QR อย่างเดียว ไม่ให้วาด canvas แล้วแปลงเป็นรูป PNG
        // (การแปลงเป็นรูปคือส่วนที่ช้าที่สุด ~30ms/ป้าย) และ SVG พิมพ์ออกมาคมชัดทุกขนาด
        let qrEngine = null;
        function qrSvg(text) {
            if (!qrEngine) {
                qrEngine = new QRCode(document.createElement('div'), { width: 1, height: 1, correctLevel: QRCode.CorrectLevel.H });
                qrEngine.makeImage = function () {};
            }
            qrEngine.makeCode(text);
            const m = qrEngine._oQRCode, n = m.getModuleCount();
            let d = '';
            for (let r = 0; r < n; r++) {
                for (let c = 0; c < n; c++) {
                    if (!m.isDark(r, c)) continue;
                    let w = 1;
                    while (c + w < n && m.isDark(r, c + w)) w++;   // รวมช่องดำที่ติดกันในแถวเป็นแท่งเดียว
                    d += `M${c} ${r}h${w}v1h-${w}z`;
                    c += w - 1;
                }
            }
            return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${n} ${n}" shape-rendering="crispEdges" style="width:100%;height:100%;display:block"><rect width="${n}" height="${n}" fill="#ffffff"/><path d="${d}" fill="#0f172a"/></svg>`;
        }

        function makeQr(el, assetId) {
            el.innerHTML = qrSvg(buildHistoryUrl(assetId));
            const badge = document.createElement('div');
            badge.className = 'qr-logo-badge';
            badge.innerHTML = `<img src="logo - easypro2.png" onerror="this.parentElement.style.display='none'">`;
            el.appendChild(badge);
        }

        window.addEventListener('DOMContentLoaded', () => {
            loadPrintData();
            applyCustomSize();
        });
    </script>
</body>
</html>