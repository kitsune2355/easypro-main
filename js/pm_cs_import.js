// นำเข้าจุดตรวจจาก Excel (หน้าสร้าง/แก้ไขเช็คชีต — pm_checksheet.php)
//  ไฟล์ของแต่ละหน่วยงานเรียงคอลัมน์ไม่เหมือนกัน => หาแถวหัวตาราง + จับคู่คอลัมน์จากคำในหัวตารางให้เอง
//  หน้าต่าง (เต็มหน้าต่าง): ไฟล์ (หน้าตาแบบ Excel ซูมได้) + ตารางจุดตรวจแบบเดียวกับในระบบ — วางบน-ล่าง หรือซ้าย-ขวา ลากเส้นแบ่งปรับขนาดได้
//  คอลัมน์ที่จับคู่สีเดียวกัน, คลิกหัวคอลัมน์ (A B C …) เพื่อเปลี่ยน, ชี้แถวแล้วไฮไลต์แถวคู่
//  - เซลล์รวม: เติมค่าให้ทุกช่องในช่วงที่รวม · แถวหัวข้อหมวด (มีข้อความช่องเดียว): ข้าม หรือใส่เป็นคำนำหน้าจุดตรวจ
//  - มาตรฐานที่เป็นตัวเลข เช่น "220 V", "≤ 80 °C", "4.5 - 6 bar" => ค่าที่คาดหวัง + หน่วย (เลือกได้)
//  - จำการจับคู่คอลัมน์ไว้ในเบราว์เซอร์ (ไฟล์หัวตารางแบบเดิมครั้งต่อไปจับคู่ตามที่แก้ไว้)
//  ต้องมี XLSX (SheetJS) · ใส่ผลลัพธ์ผ่าน openChecksheetImport({ apply(rows, mode) }) หรือ window.csImportApply(rows, mode) จาก pm_checksheet.php
//  ใช้ได้ทั้งหน้าสร้างเช็คชีต และหน้าต่างแก้ไขจุดตรวจรายเช็คชีตของการนำเข้าแผน PM (pm_import.php — เปิดซ้อนบน SweetAlert ซึ่งในระบบตั้ง z-index 10000)
(function () {
    // ช่องปลายทาง = คอลัมน์ตารางจุดตรวจ (ลำดับเดียวกับ Handsontable ใน pm_checksheet.php)
    const FIELDS = [
        { k: 'check_point', label: 'จุดที่ตรวจสอบ', kw: ['จุดที่ตรวจ', 'จุดตรวจ', 'รายการตรวจ', 'รายการ', 'หัวข้อ', 'ชิ้นส่วน', 'รายละเอียดการทำงาน', 'check point', 'checkpoint', 'check item', 'item', 'task', 'description', 'detail', 'inspection', 'part'] },
        { k: 'standard', label: 'มาตรฐานการตรวจสอบ', kw: ['มาตรฐาน', 'เกณฑ์', 'ค่ามาตรฐาน', 'standard', 'criteria', 'spec', 'specification', 'requirement'] },
        { k: 'method', label: 'วิธีตรวจสอบ/เครื่องมือ', kw: ['วิธี', 'เครื่องมือ', 'method', 'tool', 'how to', 'procedure'] },
        { k: 'action', label: 'ข้อปฏิบัติเมื่อผิดปกติ', kw: ['ผิดปกติ', 'แก้ไข', 'ข้อปฏิบัติ', 'การดำเนินการ', 'action', 'corrective', 'remedy', 'abnormal'] },
        { k: 'value_name', label: 'ชื่อค่าวัด', kw: ['ค่าที่วัด', 'ชื่อค่า', 'ค่าวัด', 'parameter', 'measure', 'reading'] },
        { k: 'unit', label: 'หน่วยวัด', kw: ['หน่วย', 'unit'] },
        { k: 'expected', label: 'ค่าที่คาดหวัง', kw: ['ค่าที่คาดหวัง', 'ค่าเป้าหมาย', 'expected', 'target', 'set point', 'setpoint'] },
        // หมวด / อุปกรณ์ (มักเป็นเซลล์รวมแนวตั้ง) => หมวดของจุดตรวจ (ติ๊ก "ใส่ชื่อหมวดนำหน้าจุดตรวจ" => ต่อหน้าชื่อด้วย)
        { k: 'group', label: 'หมวด / อุปกรณ์', kw: ['อุปกรณ์', 'หมวด', 'ระบบ', 'equipment', 'system', 'component', 'group', 'section'] },
    ];
    // คอลัมน์ที่ไม่ใช้ (ลำดับ ผลตรวจ หมายเหตุ วันที่ ช่องติ๊ก ฯลฯ)
    const IGNORE_KW = ['ลำดับ', 'no.', 'no', 'ที่', 'ผล', 'result', 'หมายเหตุ', 'remark', 'note', 'วันที่', 'date', 'ok', 'ng', 'ปกติ', 'ผิดปกติ', 'ไม่ปกติ', 'ผ่าน', 'ไม่ผ่าน', 'normal', 'abnormal', 'สถานะ', 'status', 'ลงชื่อ', 'signature', 'ผู้ตรวจ'];
    const CHECK_TYPES = ['ผ่าน / ไม่ผ่าน', 'ผ่าน / ไม่ผ่าน / ไม่เกี่ยวข้อง'];
    const PHOTO_REQS = ['ไม่บังคับ', 'บังคับ', 'บังคับเฉพาะกรณีที่ไม่ผ่าน'];
    const LS_KEY = 'easypro_cs_import_maps';

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const norm = s => String(s ?? '').replace(/\s+/g, ' ').trim();
    const low = s => norm(s).toLowerCase();

    let wb = null, sheetName = '', rows = [], headRow = 0, map = [], fileName = '';
    let sheetList = [], lastSheet = '';   // ชีตที่แสดงเป็นแท็บ (ไม่รวมชีตซ่อน) · lastSheet = ชีตที่เปิดอยู่ตอนบันทึกไฟล์ครั้งล่าสุด

    // ---------- อ่านชีต (เติมค่าเซลล์รวม) ----------
    // อ่านตั้งแต่ A1 เสมอ: ชีตที่ข้อมูลเริ่มแถว/คอลัมน์อื่น ลำดับแถว/คอลัมน์ต้องตรงกับเซลล์รวม (!merges) และรูปแบบจาก ExcelJS ที่นับจาก A1
    const rawRows = name => {
        const ws = wb.Sheets[name];
        if (!ws || !ws['!ref']) return [];
        const range = XLSX.utils.decode_range(ws['!ref']); range.s.r = 0; range.s.c = 0;
        return XLSX.utils.sheet_to_json(ws, { header: 1, defval: '', raw: false, blankrows: true, range }).map(r => (r || []).map(v => String(v ?? '')));
    };
    function sheetRows(name) {
        const ws = wb.Sheets[name];
        const aoa = rawRows(name);
        (ws['!merges'] || []).forEach(m => {
            const v = (aoa[m.s.r] || [])[m.s.c];
            if (v === '' || v == null) return;
            for (let r = m.s.r; r <= m.e.r; r++) {
                aoa[r] = aoa[r] || [];
                for (let c = m.s.c; c <= m.e.c; c++) if (r !== m.s.r || c !== m.s.c) aoa[r][c] = v;
            }
        });
        return aoa.map(r => (r || []).map(v => String(v ?? '')));
    }
    const clean = v => String(v ?? '');

    // ---------- หน้าตาแบบ Excel: รูปแบบเซลล์ (ความกว้าง/สูง สีพื้น ตัวอักษร เส้นขอบ รูป) อ่านด้วย ExcelJS ----------
    //  .xls / .csv หรือโหลด ExcelJS ไม่ได้ => แสดงเป็นตารางมีเส้นตารางธรรมดา
    let xwb = null, imgCache = {};
    const EXCELJS = 'https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js';
    function loadExcelJs() {
        if (window.ExcelJS) return Promise.resolve(window.ExcelJS);
        return new Promise((res, rej) => {
            const sc = document.createElement('script');
            sc.src = EXCELJS;
            sc.onload = () => res(window.ExcelJS);
            sc.onerror = () => rej(new Error('โหลด ExcelJS ไม่สำเร็จ'));
            document.head.appendChild(sc);
        });
    }
    // สี indexed ของ Excel (0–63) · 64 = สีตัวอักษรปกติ
    const IDX = ('000000 FFFFFF FF0000 00FF00 0000FF FFFF00 FF00FF 00FFFF '.repeat(2) + '800000 008000 000080 808000 800080 008080 C0C0C0 808080 9999FF 993366 FFFFCC CCFFFF 660066 FF8080 0066CC CCCCFF '
        + '000080 FF00FF FFFF00 00FFFF 800080 800000 008080 0000FF 00CCFF CCFFFF CCFFCC FFFF99 99CCFF FF99CC CC99FF FFCC99 3366FF 33CCCC 99CC00 FFCC00 FF9900 FF6600 666699 969696 003366 339966 003300 333300 993300 993366 333399 333333').split(' ');
    // สีธีม (theme 0–9 = lt1 dk1 lt2 dk2 accent1–6) จากไฟล์ — ไม่มีใช้ชุด Office
    let themeCols = [];
    function readTheme() {
        themeCols = ['FFFFFF', '000000', 'E7E6E6', '44546A', '4472C4', 'ED7D31', 'A5A5A5', 'FFC000', '5B9BD5', '70AD47'];
        try {
            const sch = new DOMParser().parseFromString(xwb._themes.theme1, 'application/xml').getElementsByTagName('a:clrScheme')[0];
            ['lt1', 'dk1', 'lt2', 'dk2', 'accent1', 'accent2', 'accent3', 'accent4', 'accent5', 'accent6'].forEach((n, i) => {
                const e = sch.getElementsByTagName('a:' + n)[0], c = e && e.firstElementChild;
                const v = c && (c.getAttribute('lastClr') || c.getAttribute('val'));
                if (v) themeCols[i] = v;
            });
        } catch (e) { /* ใช้ชุด Office */ }
    }
    function xColor(c) {
        if (!c) return '';
        let hex = c.argb ? String(c.argb).slice(-6) : c.theme != null ? themeCols[c.theme] : c.indexed != null ? IDX[c.indexed] : '';
        if (!/^[0-9a-f]{6}$/i.test(hex || '')) return '';   // ค่าจากไฟล์ใส่ลง style="" — รับเฉพาะเลขฐาน 16
        if (c.tint) hex = [0, 2, 4].map(i => { const v = parseInt(hex.substr(i, 2), 16), t = c.tint; return ('0' + Math.round(t < 0 ? v * (1 + t) : v + (255 - v) * t).toString(16)).slice(-2); }).join('');
        return '#' + hex;
    }
    const BSTYLE = { thin: '1px solid', hair: '1px solid', dotted: '1px dotted', dashed: '1px dashed', dashDot: '1px dashed', dashDotDot: '1px dashed', medium: '2px solid', mediumDashed: '2px dashed', mediumDashDot: '2px dashed', mediumDashDotDot: '2px dashed', slantDashDot: '2px dashed', thick: '3px solid', double: '3px double' };
    const bcss = b => b && BSTYLE[b.style] ? BSTYLE[b.style] + ' ' + (b.style === 'hair' ? '#9ca3af' : xColor(b.color) || '#000') : '';
    // ฟอนต์ที่เครื่องไม่มี => Tahoma (TH Sarabun ฯลฯ ตัวเล็กกว่าฟอนต์ทั่วไป ย่อขนาดลงให้ใกล้เคียง)
    const fontOk = {};
    function hasFont(name) {
        if (!(name in fontOk)) {
            const cx = document.createElement('canvas').getContext('2d'), t = 'abcMWกขคฬ0123';
            cx.font = '20px monospace'; const a = cx.measureText(t).width;
            cx.font = `20px "${name}", monospace`; fontOk[name] = cx.measureText(t).width !== a;
        }
        return fontOk[name];
    }
    function fontCss(f) {
        f = f || {};
        // ชื่อฟอนต์จากไฟล์ใส่ลง style="" — ตัดอักขระที่ปิด attribute/ประกาศได้
        let fam = String(f.name || 'Calibri').replace(/["'<>;\\{}]/g, ''), size = +f.size || 11;
        // TH SarabunIT๙ ตั้งชื่อตระกูลฟอนต์ซ้ำเป็น "TH SarabunPSK" แต่วาดเลข 0–9 เป็นเลขไทย — เครื่องที่ลงไว้ทั้งคู่ เบราว์เซอร์อาจหยิบตัว IT๙
        //  => แสดงด้วย TH Sarabun New (รูปตัวอักษร/ขนาดเดียวกัน เลขอารบิก)
        if (/^TH ?Sarabun/i.test(fam) && !/new/i.test(fam)) fam = 'TH Sarabun New';
        if (!hasFont(fam)) { if (/^TH |PSK/i.test(fam)) size *= 0.72; fam = 'Tahoma'; }
        let s = `font-family:'${fam}',Tahoma,sans-serif;font-size:${+size.toFixed(1)}pt;`;
        if (f.bold) s += 'font-weight:700;';
        if (f.italic) s += 'font-style:italic;';
        if (f.underline || f.strike) s += 'text-decoration:' + [f.underline ? 'underline' : '', f.strike ? 'line-through' : ''].join(' ') + ';';
        const c = xColor(f.color);
        if (c) s += 'color:' + c + ';';
        return s;
    }
    // สไตล์ของเซลล์ (เซลล์รวม: เส้นขวา/ล่าง เอาจากเซลล์ขอบของช่วง)
    function cellCss(ws, r, c, sp) {
        const cell = ws.getRow(r + 1).getCell(c + 1), a = cell.alignment || {}, fl = cell.fill;
        let s = fontCss(cell.font);
        if (fl && fl.type === 'pattern' && fl.pattern && fl.pattern !== 'none') { const bg = xColor(fl.fgColor); if (bg) s += 'background-color:' + bg + ';'; }
        else if (fl && fl.type === 'gradient' && fl.stops && fl.stops[0]) { const bg = xColor(fl.stops[0].color); if (bg) s += 'background-color:' + bg + ';'; }
        const v = cell.value, num = typeof v === 'number' || (v && typeof v.result === 'number');
        const h = a.horizontal && a.horizontal !== 'general' ? a.horizontal : num ? 'right' : '';
        if (h) s += 'text-align:' + ({ centerContinuous: 'center', distributed: 'justify', fill: 'left' }[h] || h) + ';';
        s += 'vertical-align:' + ({ top: 'top', middle: 'middle', center: 'middle', distributed: 'middle', justify: 'middle' }[a.vertical] || 'bottom') + ';';
        if (a.wrapText) s += 'white-space:pre-wrap;word-break:break-word;';
        if (a.indent) s += 'padding-left:' + (3 + a.indent * 9) + 'px;';
        // เส้นขอบ: ตาราง border-collapse ทำให้เส้นทับหัวคอลัมน์/หัวแถวที่ตรึงไว้ => ใช้แบบ separate แล้ววาดแต่ละเส้นครั้งเดียว
        //  ขวา = ขวาของช่องนี้ หรือซ้ายของช่องถัดไป · ล่าง = ล่างของช่องนี้ หรือบนของช่องข้างล่าง · ซ้าย/บน วาดเฉพาะคอลัมน์/แถวแรก
        const B = (rr, cc) => ws.getRow(rr + 1).getCell(cc + 1).border || {};
        const cs = sp ? sp.cs : 1, rs = sp ? sp.rs : 1;
        const side = (k, b) => { const x = bcss(b); return x ? `border-${k}:${x};` : ''; };
        return s + (c === 0 ? side('left', B(r, c).left) : '') + (r === 0 ? side('top', B(r, c).top) : '')
            + side('right', B(r, c + cs - 1).right || B(r, c + cs).left) + side('bottom', B(r + rs - 1, c).bottom || B(r + rs, c).top);
    }
    function imgUrl(id) {
        if (!imgCache[id]) {
            const m = xwb.getImage(id), type = 'image/' + (m.extension === 'jpg' ? 'jpeg' : m.extension);
            imgCache[id] = m.buffer ? URL.createObjectURL(new Blob([m.buffer], { type })) : m.base64 ? (/^data:/.test(m.base64) ? m.base64 : `data:${type};base64,${m.base64}`) : '';
        }
        return imgCache[id];
    }
    // จำนวนคอลัมน์ = ถึงคอลัมน์สุดท้ายที่มีข้อความ (ชีตที่จัดรูปแบบไว้กว้างกว่าข้อมูล)
    const width = () => rows.reduce((m, r) => { for (let c = (r || []).length - 1; c >= m; c--) if (norm(r[c])) return c + 1; return m; }, 0);

    // ---------- หาแถวหัวตาราง: แถวที่มีคำของช่องปลายทางมากสุด (30 แถวแรก) ----------
    function scoreHeader(r) {
        // นับแต่ละข้อความครั้งเดียว (เซลล์รวมเติมค่าซ้ำ) — แถวที่มีข้อความเดียว (หัวข้อหมวด / หัวเรื่อง) ไม่ใช่หัวตาราง
        const cells = [...new Set((r || []).map(c => norm(clean(c))).filter(Boolean))];
        if (cells.length < 2) return 0;
        let s = 0;
        cells.forEach(c => { const t = low(clean(c)); if (!t || t.length > 60) return; if (FIELDS.some(f => f.kw.some(k => t.includes(k)))) s += 2; else if (IGNORE_KW.includes(t)) s += 1; });
        return s;
    }
    function detectHeader() {
        let best = 0, bestS = -1;
        rows.slice(0, 30).forEach((r, i) => { const s = scoreHeader(r); if (s > bestS) { bestS = s; best = i; } });
        return best;
    }

    // ชื่อหัวคอลัมน์ (รวมหัว 2 ชั้น ถ้าแถวถัดไปเป็นหัวย่อย เช่น "ค่ามาตรฐาน" / "Min" "Max")
    function headName(c) {
        const a = clean((rows[headRow] || [])[c]), b = clean((rows[headRow + 1] || [])[c]);
        const subIsHead = scoreHeader(rows[headRow + 1]) > 0 && !(rows[headRow + 1] || []).some(v => /^\d+([.,]\d+)?$/.test(clean(v)));
        return norm(subIsHead && b && b !== a ? (a ? a + ' / ' + b : b) : a);
    }
    // หัวคอลัมน์ที่เป็นเซลล์รวมหลายคอลัมน์ (เช่น "Standards" รวม L:N) => ค่าในแต่ละแถวอาจอยู่คอลัมน์ไหนก็ได้ในช่วงนั้น
    function hdrSpan(c) {
        const ws = wb && wb.Sheets[sheetName];
        const m = ws && (ws['!merges'] || []).find(m => m.s.r <= headRow && m.e.r >= headRow && m.s.c <= c && m.e.c >= c && m.e.c > m.s.c);
        return m ? [m.s.c, m.e.c] : [c, c];
    }
    // ชนิดของคอลัมน์ (รวมคอลัมน์ที่อยู่ใต้หัวเซลล์รวมเดียวกันทางขวาของคอลัมน์ที่จับคู่)
    function fieldOf(c) {
        if (orient === 'col') return colSel[c] ? 'check_point' : '';
        if (map[c]) return map[c];
        for (let x = c - 1; x >= hdrSpan(c)[0]; x--) if (map[x]) return map[x];
        return '';
    }
    function dataStart() {
        if (orient === 'col') return headEnd + 1;
        const sub = rows[headRow + 1];
        return headRow + (scoreHeader(sub) > 0 && !(sub || []).some(v => /^\d+([.,]\d+)?$/.test(clean(v))) ? 2 : 1);
    }

    // ---------- จับคู่คอลัมน์อัตโนมัติ (แต่ละช่องปลายทางใช้ได้คอลัมน์เดียว — คำที่ตรงยาวที่สุดชนะ) ----------
    function autoMap() {
        const n = width();
        const sig = mapSig();
        const saved = loadMaps()[sig];
        if (saved && saved.length === n) return saved.slice();
        const cand = [];
        for (let c = 0; c < n; c++) {
            const t = low(headName(c));
            if (!t || IGNORE_KW.includes(t)) continue;   // หัวที่เป็นช่องผลตรวจล้วน ๆ (ปกติ / ผิดปกติ / OK …) ไม่จับคู่ แม้มีคำของช่องปลายทาง
            FIELDS.forEach(f => f.kw.forEach(k => { if (t.includes(k)) cand.push({ c, f: f.k, w: k.length + (t === k ? 5 : 0) }); }));
        }
        cand.sort((x, y) => y.w - x.w);
        const out = Array(n).fill(''), used = new Set();
        cand.forEach(x => { if (!out[x.c] && !used.has(x.f)) { out[x.c] = x.f; used.add(x.f); } });
        // ไม่มีคอลัมน์จุดตรวจ => ใช้คอลัมน์ข้อความที่ยาวที่สุดโดยเฉลี่ย (ที่ยังไม่ได้ใช้)
        if (!used.has('check_point')) {
            let best = -1, bestLen = 0;
            for (let c = 0; c < n; c++) {
                if (out[c] || IGNORE_KW.includes(low(headName(c)))) continue;
                const vals = rows.slice(dataStart(), dataStart() + 40).map(r => clean((r || [])[c])).filter(Boolean);
                const avg = vals.length ? vals.reduce((s, v) => s + v.length, 0) / vals.length : 0;
                if (avg > bestLen && !vals.every(v => /^[\d.,\s]*$/.test(v))) { bestLen = avg; best = c; }
            }
            if (best >= 0) out[best] = 'check_point';
        }
        return out;
    }
    const mapSig = () => sheetName + '|' + Array.from({ length: width() }, (_, c) => low(headName(c))).join('|');
    function loadMaps() { try { return JSON.parse(localStorage.getItem(LS_KEY)) || {}; } catch (e) { return {}; } }
    function saveMap() {
        try { const m = loadMaps(); m[mapSig()] = map; const keys = Object.keys(m); while (keys.length > 30) delete m[keys.shift()]; localStorage.setItem(LS_KEY, JSON.stringify(m)); } catch (e) { /* โหมดส่วนตัว */ }
    }

    // ---------- แปลงเป็นจุดตรวจ ----------
    // มาตรฐานที่เป็นตัวเลข: "220 V" / "≤ 80 °C" / "4.5 - 6 bar" / "ไม่เกิน 10 A" => { expected, unit }
    function splitValue(s) {
        const t = norm(s).replace(/ไม่เกิน|ไม่เกิ้น/g, '≤').replace(/ไม่น้อยกว่า|อย่างน้อย/g, '≥').replace(/<=/g, '≤').replace(/>=/g, '≥');
        const m = /^(≤|≥|<|>|=|±)?\s*(-?\d+(?:[.,]\d+)?)(?:\s*(?:-|–|~|ถึง|to)\s*(-?\d+(?:[.,]\d+)?))?\s*([A-Za-zก-๙°%µΩ/³²·.]+(?:\s?[A-Za-z°%/]+)?)?$/.exec(t);
        if (!m) return null;
        return { expected: (m[1] || '') + m[2] + (m[3] ? ' - ' + m[3] : ''), unit: (m[4] || '').trim() };
    }
    // ส่วนลงชื่อท้ายฟอร์ม (Done By / Checked by / ลายเซ็น …) => จบตารางจุดตรวจ
    const FOOTER_RE = /done\s*by|checked\s*by|verified\s*by|approved\s*by|inspected\s*by|signature|ลายเซ็น|ลายมือชื่อ|ลงชื่อ|ลงนาม|ดำเนินการโดย|ตรวจสอบโดย|ทวนสอบโดย|ผู้อนุมัติ|ผู้ตรวจสอบ\s*[.…_]{2,}/i;
    // แถวที่ผู้ใช้ลบออก (src) / ตัดตั้งแต่แถวนี้ลงไป
    let removed = new Set(), cutAt = null;
    // ประเภทจุดตรวจ / บังคับถ่ายรูป: ค่าทั้งตาราง (เลือกที่หัวคอลัมน์ตารางล่าง) + เลือกรายแถว { แถว: { type, photo } }
    let defType = CHECK_TYPES[0], defPhoto = PHOTO_REQS[0], rowOpt = {};

    // แปลงแถวข้อมูลเป็นจุดตรวจ => { items: [{ src: แถว (0-based), v: [10 ช่องของตาราง] }], kind: { แถว: 'item' | 'cont' (ต่อจากแถวบน) | 'section' | 'skip' | 'removed' | 'cut' | 'footer' }, footerAt }
    // ---------- แบบ "จุดตรวจเป็นคอลัมน์" (เช่น ใบตรวจถังดับเพลิง: แถว = เครื่อง, หัวคอลัมน์หลายชั้น = จุดตรวจ) ----------
    //  orient: 'row' = จุดตรวจเป็นแถว (ปกติ) / 'col' = หัวคอลัมน์แต่ละช่องคือ 1 จุดตรวจ · headRow..headEnd = แถวหัวตาราง (หลายชั้น)
    // rowMap = ชนิดของแถวหัวตาราง { แถว: 'check_point' | 'group' (หมวด) | 'standard' | … } — เลือกที่เลขแถว (หัวแถว)
    let orient = 'row', colSel = [], headEnd = 0, rowMap = {};
    // คอลัมน์ข้อมูลของเครื่อง/ลำดับ/หมายเหตุ — ไม่ใช่จุดตรวจ
    const ID_RE = /^(item|items|no\.?|ลำดับ|ลำดับที่|ที่|code|รหัส|รหัสเครื่อง|รหัสอุปกรณ์|asset ?(code|no\.?)|tag( ?no\.?)?|location|สถานที่|สถานที่ติดตั้ง|ตำแหน่ง|ตำแหน่งติดตั้ง|จุดติดตั้ง|ชั้น|floor|zone|โซน|remark|remarks|หมายเหตุ|วันที่|date|signature|ลงชื่อ|ผู้ตรวจ.*|brand.*|model|ยี่ห้อ.*|รุ่น.*|serial( ?no\.?)?|s\/n|sn)$/i;
    // แถวหัวตารางที่มีทั้ง "รหัส" และ "สถานที่" => น่าจะเป็นแบบจุดตรวจเป็นคอลัมน์ (คืนเลขแถว หรือ -1)
    function detectColLayout() {
        for (let i = 0; i < Math.min(rows.length, 30); i++) {
            const t = (rows[i] || []).map(v => low(v));
            if (t.some(v => /^(code|รหัส|รหัสเครื่อง|รหัสอุปกรณ์|asset ?code|tag( ?no\.?)?)$/.test(v)) && t.some(v => /^(location|สถานที่|สถานที่ติดตั้ง|ตำแหน่ง|ตำแหน่งติดตั้ง|จุดติดตั้ง)$/.test(v))) return i;
        }
        return -1;
    }
    // หัวคอลัมน์ทุกชั้น (เซลล์รวมเติมค่าแล้ว) เช่น ["รายการตรวจเช็ค", "เกจวัดน้ำยา", "Recharge"]
    function colLevels(c) {
        const out = [];
        for (let r = headRow; r <= headEnd; r++) { const v = norm(clean((rows[r] || [])[c])); if (v && v !== out[out.length - 1]) out.push(v); }
        return out;
    }
    // ตั้งค่าแบบจุดตรวจเป็นคอลัมน์จากแถวหัวตาราง h: หาความลึกของหัว + เลือกคอลัมน์จุดตรวจเริ่มต้น
    function setupCol(h) {
        orient = 'col'; headRow = h;
        const n = width(), idCols = [];
        for (let c = 0; c < n; c++) if (ID_RE.test(low((rows[h] || [])[c]))) idCols.push(c);
        // หัวหลายชั้น: แถวถัดลงมาที่ช่องรหัส/ลำดับว่าง (เซลล์รวมแนวตั้ง) แต่มีข้อความในคอลัมน์อื่น
        headEnd = h;
        for (let r = h + 1; r <= h + 4 && r < rows.length; r++) {
            const d = disp[r] || [];
            const idEmpty = idCols.length ? idCols.every(c => !norm(d[c])) : false;
            if (idEmpty && d.some((v, c) => norm(v) && !idCols.includes(c))) headEnd = r; else break;
        }
        colSel = Array.from({ length: n }, (_, c) => {
            if (idCols.includes(c)) return false;
            const lv = colLevels(c), leaf = low(lv[lv.length - 1] || '');
            return !!leaf && !ID_RE.test(leaf) && !IGNORE_KW.includes(leaf);
        });
        removed = new Set(); cutAt = null; rowOpt = {};
        autoRowMap();
        // แถวถัดจากหัวตารางที่ช่องรหัส/ลำดับ/สถานที่มีป้าย เช่น "Standard", "หน่วย" => แถวมาตรฐาน/หน่วย ของแต่ละจุดตรวจ
        const LABEL_ROWS = [[/^(standard|standards|มาตรฐาน|ค่ามาตรฐาน|เกณฑ์|criteria|spec)/i, 'standard'], [/^(unit|units|หน่วย)/i, 'unit'], [/^(method|วิธี)/i, 'method'], [/^(expected|target|ค่าที่คาดหวัง|ค่าเป้าหมาย)/i, 'expected']];
        for (let r = headEnd + 1; r <= headEnd + 3 && r < rows.length; r++) {
            const lbl = (rows[r] || []).map((v, c) => (colSel[c] ? '' : norm(clean(v)))).find(Boolean) || '';
            const hit = LABEL_ROWS.find(([re, k]) => re.test(lbl) && rowOf(k) < 0);
            if (!hit) break;
            rowMap[r] = hit[1]; headEnd = r;
        }
    }
    // ค่าเริ่มต้น: แถวล่างสุดของหัว = จุดตรวจ · แถวเหนือขึ้นไปที่มีหลายค่า (เช่น เกจวัดน้ำยา / สภาพทั่วไป) = หมวด
    //  แถวที่ทุกคอลัมน์เหมือนกัน (เช่น "รายการตรวจเช็ค") ไม่ใช้
    function autoRowMap() {
        rowMap = {};
        rowMap[headEnd] = 'check_point';
        const sel = colSel.map((on, c) => (on ? c : -1)).filter(c => c >= 0);
        for (let r = headEnd - 1; r >= headRow; r--) {
            if (new Set(sel.map(c => cellVal(r, c)).filter(Boolean)).size >= 2) { rowMap[r] = 'group'; break; }
        }
    }
    const cellVal = (r, c) => (r < 0 ? '' : norm(clean((rows[r] || [])[c])));
    const rowOf = k => { for (const r in rowMap) if (rowMap[r] === k) return +r; return -1; };
    // ชื่อจุดตรวจของคอลัมน์ c (จากแถวที่เลือกเป็นจุดตรวจ) — ติ๊ก "ใส่ชื่อหมวดนำหน้า" => หมวด - จุดตรวจ
    const colName = (c, withSec) => { const cp = cellVal(rowOf('check_point'), c), sec = cellVal(rowOf('group'), c); return cp && withSec && sec && sec !== cp ? sec + ' - ' + cp : cp; };
    function buildColItems(opt) {
        const sel = colSel.map((on, c) => (on ? c : -1)).filter(c => c >= 0);
        const items = [], kind = {}, R = k => rowOf(k);
        // จุดตรวจ 1 ข้อใช้หลายคอลัมน์ (เช่น ชื่ออังกฤษ | ชื่อไทย) — ช่องมาตรฐาน/ผลตรวจใต้แถวจุดตรวจรวมเซลล์คลุมคอลัมน์เหล่านั้น
        //  => รวมเป็นจุดตรวจเดียว ชื่อ "General clean / ทำความสะอาดทั่วไป"
        const cpR = R('check_point');
        const span = (wb.Sheets[sheetName]['!merges'] || []).filter(m => m.e.c > m.s.c && m.s.r > cpR && m.s.r <= cpR + 3);
        const groupOf = c => { const m = span.find(m => m.s.c <= c && c <= m.e.c); return m ? m.s.c : c; };
        const groups = [];
        sel.forEach(c => { const g = groupOf(c), last = groups[groups.length - 1]; if (last && last.g === g) last.cols.push(c); else groups.push({ g, cols: [c] }); });
        let prevKey = '';
        groups.forEach(({ cols }) => {
            const c = cols[0];
            const sec = cellVal(R('group'), c), cp0 = [...new Set(cols.map(x => cellVal(cpR, x)).filter(Boolean))].join(' / ');
            const key = cp0 + '|' + sec;
            if (!cp0 || key === prevKey) return;   // เซลล์รวมแนวนอน (หัวเดียวกันหลายคอลัมน์) => จุดตรวจเดียว
            prevKey = key;
            const cp = opt.section && sec && sec !== cp0 ? sec + ' - ' + cp0 : cp0;
            let standard = cellVal(R('standard'), c), unit = cellVal(R('unit'), c), expected = cellVal(R('expected'), c), valueName = cellVal(R('value_name'), c);
            if (opt.split && !expected && standard) {
                const sv = splitValue(standard);
                if (sv) { expected = sv.expected; if (!unit) unit = sv.unit; if (!valueName) valueName = cp; }
            }
            const src = 'c' + c, ro = rowOpt[src] || {};
            items.push({ src, col: c, v: [cp, standard, cellVal(R('method'), c), cellVal(R('action'), c), ro.type || opt.checkType, '', ro.photo || opt.photo, valueName, unit, expected, sec] });
        });
        for (let i = headRow; i <= headEnd; i++) kind[i] = 'head';
        return { items, kind, footerAt: null };
    }

    function buildItems(opt) {
        if (orient === 'col') return buildColItems(opt);
        const col = k => map.indexOf(k);
        // ค่าว่าง => ดูคอลัมน์ทางขวาที่อยู่ใต้หัวเซลล์รวมเดียวกัน (ไม่ข้ามไปคอลัมน์ที่จับคู่ชนิดอื่น)
        const ends = {};
        const get = (r, k) => {
            const c = col(k);
            if (c < 0) return '';
            if (!(c in ends)) ends[c] = hdrSpan(c)[1];
            for (let x = c; x <= ends[c]; x++) {
                if (x !== c && map[x]) break;
                const v = norm(clean((r || [])[x]));
                if (v && !(k === 'check_point' && /^\d+(\.\d+)*[.)]?$/.test(v))) return v;   // จุดตรวจ: ข้ามเลขลำดับ (หัว "TASKS" รวมทั้งคอลัมน์ลำดับและรายการ)
            }
            return '';
        };
        // แถว i อยู่ใต้เซลล์รวมแนวตั้งของคอลัมน์ที่จับคู่ (ไม่ใช่คอลัมน์จุดตรวจ) => แถวแรกของเซลล์รวม
        const cpc = col('check_point'), merges = (wb.Sheets[sheetName]['!merges'] || []).filter(m => m.e.r > m.s.r);
        const contStart = i => {
            const m = merges.find(m => m.s.r < i && m.e.r >= i && !(cpc >= m.s.c && cpc <= m.e.c)
                && map.some((k, c) => k && k !== 'check_point' && k !== 'group' && c >= m.s.c && c <= m.e.c));
            return m ? m.s.r : null;
        };
        const items = [], kind = {};
        let section = '', footerAt = null;
        for (let i = dataStart(); i < rows.length; i++) {
            const r = rows[i];
            const vals = (r || []).map(v => norm(clean(v))).filter(Boolean);
            if (cutAt != null && i >= cutAt) { kind[i] = 'cut'; continue; }
            if (footerAt != null) { kind[i] = 'footer'; continue; }
            if (!vals.length) continue;
            if (opt.footer && FOOTER_RE.test(vals.join(' '))) { footerAt = i; kind[i] = 'footer'; continue; }
            if (removed.has(i)) { kind[i] = 'removed'; continue; }
            let cp = get(r, 'check_point');
            // แถวหัวข้อหมวด: ทั้งแถวมีข้อความเดียว (มักเป็นเซลล์รวมทั้งแถว)
            const distinct = [...new Set(vals)];
            if (distinct.length === 1 && !/^\d+$/.test(distinct[0])) {
                section = distinct[0].replace(/^\d+(?:\.\d+)*[.)]?\s+/, '');
                kind[i] = 'section';
                continue;
            }
            if (!cp) { kind[i] = 'skip'; continue; }
            cp = cp.replace(/^\d+(?:\.\d+)*[.)]?\s+/, '');   // ตัดเลขข้อหน้าข้อความ เช่น "1.2 ตรวจ..." / "3) ตรวจ..."
            // ข้อความจุดตรวจที่ยาวต่อลงมาอีกแถว (ช่องมาตรฐาน ฯลฯ เป็นเซลล์รวมแนวตั้งคลุมทั้งสองแถว) => ต่อท้ายจุดตรวจแถวบน
            const prev = items[items.length - 1], cs = contStart(i);
            if (prev && cs != null && prev.src >= cs && kind[i - 1] !== 'section') { prev.v[0] += ' ' + cp; kind[i] = 'cont'; continue; }
            const grp = get(r, 'group'), sec = grp && grp !== cp ? grp : section;
            if (opt.section && sec) cp = sec + ' - ' + cp;
            let standard = get(r, 'standard'), unit = get(r, 'unit'), expected = get(r, 'expected'), valueName = get(r, 'value_name');
            if (opt.split && !expected && standard) {
                const sv = splitValue(standard);
                if (sv) { expected = sv.expected; if (!unit) unit = sv.unit; if (!valueName) valueName = cp; }
            }
            kind[i] = 'item';
            const ro = rowOpt[i] || {};
            items.push({ src: i, v: [cp, standard, get(r, 'method'), get(r, 'action'), ro.type || opt.checkType, '', ro.photo || opt.photo, valueName, unit, expected, sec] });
        }
        return { items, kind, footerAt };
    }

    // ---------- หน้าต่าง: บน = ตาราง Excel (ติ๊กแถวที่จะนำเข้า) / ล่าง = ตารางจุดตรวจแบบในระบบ — สีของชนิดคอลัมน์ตรงกัน · ชี้แถวแล้วไฮไลต์คู่ ----------
    const FCOLOR = { check_point: '#0284c7', standard: '#059669', method: '#7c3aed', action: '#e11d48', value_name: '#d97706', unit: '#0d9488', expected: '#ea580c', group: '#475569' };
    const SHORT = { check_point: 'จุดตรวจ', standard: 'มาตรฐาน', method: 'วิธีตรวจ', action: 'เมื่อผิดปกติ', value_name: 'ชื่อค่าวัด', unit: 'หน่วย', expected: 'ค่าที่คาดหวัง', group: 'หมวด' };
    const tint = (hex, a) => { const n = parseInt(hex.slice(1), 16); return `rgba(${n >> 16},${(n >> 8) & 255},${n & 255},${a})`; };
    let result = { items: [], kind: {} };

    // เช็คชีตที่กำลังแก้ (openOpt.target จากหน้าที่เปิด) — ผู้ใช้เปิดไฟล์ Excel ไปแก้แล้วลืมว่าจะนำเข้าลงเช็คชีตไหน
    const targetName = () => { const t = openOpt.target; return t ? t.name || 'ยังไม่ได้ตั้งชื่อ' : ''; };
    function targetHtml() {
        const t = openOpt.target;
        if (!t) return '';
        return `<div id="csi-target" class="inline-flex items-center gap-2 min-w-0 max-w-full px-3 py-1.5 rounded-lg border border-sky-200 bg-sky-50 text-sky-900" title="จุดตรวจที่นำเข้าจะใส่ลงเช็คชีตนี้">
            <i data-lucide="clipboard-pen" class="w-4 h-4 shrink-0 text-sky-600"></i>
            <span class="truncate">${esc(t.mode || 'กำลังแก้ไข')} <b>${esc(targetName())}</b>${t.doc ? ' <span class="text-sky-700">· ' + esc(t.doc) + '</span>' : ''}</span>
            ${t.count != null ? `<span class="shrink-0 px-2 py-0.5 rounded-full bg-white border border-sky-200 text-xs text-sky-700">${t.count ? `มีอยู่ ${t.count} จุด` : 'ยังไม่มีจุดตรวจ'}</span>` : ''}</div>`;
    }
    function modalHtml() {
        return `<div id="csi-modal" class="fixed inset-0 flex items-center justify-center p-2 sm:p-4" style="z-index:10050;background:rgba(15,23,42,.5)">
        <div id="csi-box" class="bg-white rounded-2xl shadow-2xl w-full max-w-[1500px] h-[94vh] flex flex-col overflow-hidden text-[14px] text-slate-700">
            <div class="flex flex-wrap items-center gap-3 px-5 py-3.5 border-b border-slate-200">
                <div class="font-semibold text-slate-800 text-base mr-1">นำเข้าจุดตรวจจาก Excel</div>
                ${targetHtml()}
                <label class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border border-slate-300 hover:bg-slate-50 cursor-pointer">
                    <i data-lucide="upload" class="w-4 h-4 text-emerald-600"></i><span id="csi-fname">เลือกไฟล์</span>
                    <input type="file" id="csi-file" accept=".xlsx,.xls,.xlsm,.csv" class="hidden"></label>
                <div class="flex-1"></div>
                <div class="inline-flex items-center rounded-lg border border-slate-200 p-0.5 text-slate-500" title="มุมมอง">
                    <button type="button" data-layout="tb" title="บน-ล่าง" class="w-8 h-8 rounded-md flex items-center justify-center hover:bg-slate-100"><i data-lucide="rows-2" class="w-4 h-4"></i></button>
                    <button type="button" data-layout="lr" title="ซ้าย-ขวา" class="w-8 h-8 rounded-md flex items-center justify-center hover:bg-slate-100"><i data-lucide="columns-2" class="w-4 h-4"></i></button>
                </div>
                <button type="button" id="csi-fs" title="เต็มจอ / ย่อ" class="w-9 h-9 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-500"><i data-lucide="maximize-2" class="w-5 h-5"></i></button>
                <button type="button" data-csi-close class="w-9 h-9 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-500"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div id="csi-err" role="alert" class="hidden mx-5 mt-3 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <i data-lucide="circle-alert" class="w-5 h-5 shrink-0 text-rose-500 mt-0.5"></i>
                <div class="flex-1 min-w-0"><b id="csi-err-title"></b><div id="csi-err-msg" class="text-rose-700 break-words"></div></div>
                <button type="button" data-err-close class="text-rose-400 hover:text-rose-700" title="ปิด"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
            <div id="csi-empty" class="flex-1 flex flex-col items-center justify-center text-center p-8 text-slate-500">
                <i data-lucide="file-spreadsheet" class="w-12 h-12 text-slate-300 mb-3"></i>
                <div class="text-base font-medium text-slate-700">เลือกไฟล์ใบตรวจเช็ค (Excel / CSV) ของหน่วยงาน</div>
                <div class="text-sm mt-1.5 max-w-lg">ฝั่งหนึ่งคือไฟล์ อีกฝั่งคือจุดตรวจที่จะใส่ลงเช็คชีต — คลิกหัวคอลัมน์ (A B C …) ของไฟล์เพื่อบอกว่าคอลัมน์นั้นคืออะไร</div>
            </div>
            <div id="csi-main" class="hidden flex-1 min-h-0 min-w-0 flex">
                <div id="csi-pa" class="min-h-0 min-w-0 flex flex-col">
                    <div class="px-4 py-2 text-sm text-slate-500 flex flex-wrap items-center gap-x-4 gap-y-1">
                        <b class="text-slate-800">ไฟล์ Excel <span class="font-normal text-slate-500">· ชีต</span> <span id="csi-sname" class="text-emerald-700"></span></b>
                        <div class="inline-flex items-center gap-1 text-xs" title="ใบตรวจที่แต่ละแถวคือเครื่อง (เช่น ถังดับเพลิง) และจุดตรวจอยู่ที่หัวคอลัมน์ ให้เลือก คอลัมน์">
                            <span>จุดตรวจเป็น</span>
                            <span class="inline-flex rounded-lg border border-slate-200 p-0.5">
                                <button type="button" data-orient="row" class="px-2.5 h-7 rounded-md hover:bg-slate-100">แถว</button>
                                <button type="button" data-orient="col" class="px-2.5 h-7 rounded-md hover:bg-slate-100">คอลัมน์</button>
                            </span>
                        </div>
                        <span class="flex items-center gap-3 text-xs"><span class="inline-flex items-center gap-1"><i class="w-1 h-3.5 rounded-sm bg-amber-400"></i>หัวตาราง</span><span class="inline-flex items-center gap-1"><i class="w-1 h-3.5 rounded-sm bg-slate-400"></i>หมวด</span><span class="inline-flex items-center gap-1"><i class="w-1 h-3.5 rounded-sm bg-emerald-500"></i>นำเข้า</span></span>
                        <div class="flex-1"></div>
                        <div class="inline-flex items-center rounded-lg border border-slate-200 text-slate-600">
                            <button type="button" data-zoom="out" title="ซูมออก (Ctrl + ล้อเมาส์)" class="w-8 h-8 flex items-center justify-center hover:bg-slate-100 rounded-l-lg"><i data-lucide="zoom-out" class="w-4 h-4"></i></button>
                            <button type="button" data-zoom="100" id="csi-zv" title="100%" class="min-w-[3.5rem] h-8 text-xs font-medium hover:bg-slate-100">100%</button>
                            <button type="button" data-zoom="in" title="ซูมเข้า (Ctrl + ล้อเมาส์)" class="w-8 h-8 flex items-center justify-center hover:bg-slate-100"><i data-lucide="zoom-in" class="w-4 h-4"></i></button>
                            <button type="button" data-zoom="fit" title="พอดีความกว้าง" class="w-8 h-8 flex items-center justify-center hover:bg-slate-100 rounded-r-lg border-l border-slate-200"><i data-lucide="move-horizontal" class="w-4 h-4"></i></button>
                        </div>
                    </div>
                    <div id="csi-left" class="flex-1 min-h-0 overflow-auto bg-white border-t border-slate-200"></div>
                    <div id="csi-tabs" class="flex items-stretch gap-0.5 overflow-x-auto bg-slate-100 border-t border-slate-300 px-2 text-[13px] shrink-0"></div>
                </div>
                <div id="csi-gutter" title="ลากเพื่อปรับขนาด · ดับเบิลคลิก = ครึ่งต่อครึ่ง"></div>
                <div id="csi-pb" class="flex-1 min-h-0 min-w-0 flex flex-col">
                    <div class="px-5 py-2.5 text-sm text-slate-500 flex flex-wrap items-center gap-x-5 gap-y-1.5">
                        <div class="flex items-center gap-2"><b class="text-slate-800">จุดตรวจที่จะใส่ลงเช็คชีต</b> <span id="csi-count" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-semibold text-xs"></span></div>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer" title="เช่น 220 V, ≤ 80 °C"><input type="checkbox" id="csi-split" checked class="w-4 h-4 accent-emerald-600"> แยกค่าตัวเลข + หน่วยจากมาตรฐาน</label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer"><input type="checkbox" id="csi-section" class="w-4 h-4 accent-emerald-600"> <span id="csi-section-lbl">ใส่ชื่อหมวดนำหน้าจุดตรวจ</span></label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer" title="Done By / Checked by / ลายเซ็น …"><input type="checkbox" id="csi-footer" checked class="w-4 h-4 accent-emerald-600"> หยุดที่ส่วนลงชื่อท้ายฟอร์ม</label>
                        <div class="flex-1"></div><span id="csi-note" class="text-xs"></span></div>
                    <div id="csi-right" class="flex-1 min-h-0 overflow-auto px-5 pb-4"></div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 px-5 py-3 border-t border-slate-200">
                <div class="flex-1"></div>
                <button type="button" data-csi-close class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700">ยกเลิก</button>
                <button type="button" id="csi-append" disabled class="px-4 py-2 rounded-lg border border-emerald-600 text-emerald-700 bg-white disabled:opacity-40">ต่อท้ายจุดตรวจเดิม</button>
                <button type="button" id="csi-replace" disabled class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium disabled:opacity-40">แทนที่จุดตรวจทั้งหมด</button>
            </div>
        </div>
        <div id="csi-menu" style="z-index:10051" class="hidden fixed bg-white border border-slate-200 rounded-xl shadow-2xl py-1.5 text-sm min-w-[15rem]"></div></div>`;
    }
    const $ = id => document.getElementById(id);
    const opts = () => ({ split: $('csi-split').checked, section: $('csi-section').checked, footer: $('csi-footer').checked, checkType: defType, photo: defPhoto });

    // ไฟล์: หน้าตาแบบ Excel — หัวคอลัมน์ A B C … (แถบสี + ชนิดที่จับคู่, คลิกเพื่อเปลี่ยน) · หัวแถว = เลขแถว + ช่องติ๊กนำเข้า + แถบสีสถานะ
    const RH_W0 = 58, RH_WC = 112;   // ความกว้างหัวแถว (โหมดคอลัมน์มีชื่อชนิดของแถว)
    let disp = [], colX = [], tableW = 0;
    function renderLeft() {
        const n = width(), ds = dataStart(), last = Math.min(rows.length, 400);
        const ws = xwb && xwb.getWorksheet(sheetName);
        const grid = !ws || !(ws.views && ws.views[0] && ws.views[0].showGridLines === false);
        // ความกว้างคอลัมน์ (หน่วยตัวอักษรของ Excel => px) · ไม่มีรูปแบบ (.xls / .csv) => กว้างตามข้อความ
        const dw = (ws && ws.properties.defaultColWidth) || 8.43, dh = (ws && ws.properties.defaultRowHeight) || 15;
        const colW = c => {
            if (ws) { const col = ws.getColumn(c + 1); return col.hidden ? 0 : Math.round((col.width || dw) * 7 + 5); }
            const len = rows.slice(0, last).reduce((m, r) => Math.max(m, clean((r || [])[c]).length), 0);
            return Math.max(64, Math.min(320, len * 7 + 12));
        };
        // เซลล์รวม: rowspan/colspan ที่เซลล์มุมซ้ายบน · ช่องที่ถูกรวมไม่ต้องวาด
        const span = {}, covered = new Set();
        (wb.Sheets[sheetName]['!merges'] || []).forEach(m => {
            if (m.s.r >= last || m.s.c >= n) return;
            span[m.s.r + ',' + m.s.c] = { rs: Math.min(m.e.r, last - 1) - m.s.r + 1, cs: Math.min(m.e.c, n - 1) - m.s.c + 1 };
            for (let r = m.s.r; r <= m.e.r; r++) for (let c = m.s.c; c <= m.e.c; c++) if (r !== m.s.r || c !== m.s.c) covered.add(r + ',' + c);
        });
        const widths = Array.from({ length: n }, (_, c) => colW(c));
        const RH_W = orient === 'col' ? RH_WC : RH_W0;
        colX = [RH_W]; widths.forEach((w, c) => colX.push(colX[c] + w));
        tableW = colX[n];
        let h = `<div id="csi-xl" class="relative w-max" style="zoom:${view.zoom}"><table class="csi-xt${grid ? ' gl' : ''}" style="width:${tableW}px">`
            + `<colgroup><col style="width:${RH_W}px">${widths.map(w => `<col style="width:${w}px">`).join('')}</colgroup><thead><tr><th class="csi-corner"></th>`;
        for (let c = 0; c < n; c++) {
            const k = orient === 'col' ? (colSel[c] ? 'check_point' : '') : map[c], hn = orient === 'col' ? colLevels(c).join(' / ') : headName(c);
            const ck = orient === 'col' ? (colSel[c] ? 'นำเข้า' : '') : (k ? SHORT[k] : '');
            h += `<th data-col="${c}" class="csi-colh" title="คอลัมน์ ${XLSX.utils.encode_col(c)}${hn ? ' · ' + esc(hn) : ''} — คลิกเพื่อเลือกชนิด"${k ? ` style="box-shadow:inset 0 3px 0 ${FCOLOR[k]};background:linear-gradient(${tint(FCOLOR[k], .1)},${tint(FCOLOR[k], .1)}),#f3f4f6"` : ''}>`
                + `<div>${XLSX.utils.encode_col(c)}</div>${ck ? `<div class="csi-ck" style="color:${FCOLOR[k]}">${ck}</div>` : ''}</th>`;
        }
        h += '</tr></thead><tbody>';
        const STRIP = { head: '#fbbf24', section: '#94a3b8', item: '#10b981', cont: '#10b981' };
        const TAG = { head: orient === 'col' ? 'แถวหัวตาราง — คลิกเพื่อเลือกชนิดของแถว' : 'แถวหัวตาราง', section: 'หัวข้อหมวด', item: 'นำเข้า', cont: 'ข้อความต่อจากจุดตรวจแถวบน', removed: 'ไม่นำเข้า', cut: 'ตัดออก', footer: 'ส่วนลงชื่อท้ายฟอร์ม', skip: 'ไม่มีจุดตรวจ' };
        for (let i = 0; i < last; i++) {
            const k = i < headRow ? 'above' : i < ds ? 'head' : (result.kind[i] || 'empty');
            const item = k === 'item' || k === 'cont', off = k === 'removed' || k === 'cut' || k === 'footer';
            const rh = ws ? Math.round((ws.getRow(i + 1).height || dh) * 4 / 3) : 0;
            const rk = orient === 'col' ? rowMap[i] : '';   // ชนิดของแถว (โหมดคอลัมน์)
            h += `<tr data-src="${i}"${off ? ' class="csi-off"' : ''}${rh ? ` style="height:${rh}px"` : ''}>`
                + `<th class="csi-rh${orient === 'col' ? ' csi-rhc' : ''}"${orient === 'col' ? ` data-rowh="${i}"` : ''} title="แถว ${i + 1}${TAG[k] ? ' · ' + TAG[k] : ''}"${STRIP[k] ? ` style="box-shadow:inset 3px 0 0 ${STRIP[k]}"` : ''}><div${rh ? ` style="max-height:${rh}px"` : ''}>`
                + `${k === 'item' || k === 'removed' ? `<input type="checkbox" data-toggle="${i}" ${item ? 'checked' : ''} title="นำเข้าแถวนี้">` : ''}${orient === 'col' ? `<span>${i + 1}</span>${rk ? `<b class="csi-rk" style="color:${FCOLOR[rk]}">${SHORT[rk]}</b>` : ''}` : `<span data-head="${i}" title="ตั้งเป็นแถวหัวตาราง">${i + 1}</span>`}</div></th>`;
            for (let c = 0; c < n; c++) {
                const key = i + ',' + c;
                if (covered.has(key)) continue;
                const sp = span[key], txt = clean((disp[i] || [])[c]);
                let st = ws ? cellCss(ws, i, c, sp) : '';
                // แถวที่นำเข้า: ระบายสีจางตามชนิดคอลัมน์ (เซลล์รวม => คอลัมน์แรกในช่วงที่จับคู่)
                if (item || (orient === 'col' && rk)) {   // แบบจุดตรวจเป็นคอลัมน์: แถวที่เลือกชนิด × คอลัมน์ที่นำเข้า
                    let f = null;
                    for (let x = c; x < c + (sp ? sp.cs : 1); x++) if (orient === 'col' ? colSel[x] && FCOLOR[rk] : fieldOf(x)) { f = orient === 'col' ? FCOLOR[rk] : FCOLOR[fieldOf(x)]; break; }
                    if (f) st += `background-image:linear-gradient(${tint(f, .14)},${tint(f, .14)});`;
                }
                // ข้อความไม่ตัดบรรทัด ล้นไปช่องขวาที่ว่างได้เหมือน Excel
                const ov = txt && !sp && !/pre-wrap/.test(st) && c + 1 < n && !clean((disp[i] || [])[c + 1]) && !covered.has(i + ',' + (c + 1));
                h += `<td${sp ? ` rowspan="${sp.rs}" colspan="${sp.cs}"` : ''}${txt ? ` data-c="${c}" data-cs="${sp ? sp.cs : 1}"` : ''}${ov ? ' class="ov"' : ''}${st ? ` style="${st}"` : ''}>${esc(txt)}</td>`;
            }
            h += '</tr>';
        }
        $('csi-left').innerHTML = h + '</tbody></table><div id="csi-imgs"></div><div id="csi-band"></div></div>'
            + (rows.length > last ? `<div class="p-2 text-center text-xs text-slate-400">แสดง ${last} จาก ${rows.length} แถว</div>` : '');
        placeImages(ws);
    }
    // รูปในชีต (โลโก้ ฯลฯ) วางตามตำแหน่งเซลล์ที่ยึดไว้ (offset หน่วย EMU: 9525 = 1px)
    function placeImages(ws) {
        const layer = $('csi-imgs');
        if (!ws || !layer) return;
        const trs = $('csi-left').querySelectorAll('.csi-xt tbody tr');
        const X = (col, off) => colX[Math.min(col, colX.length - 1)] + (off || 0) / 9525;
        const Y = (row, off) => (trs[row] ? trs[row].offsetTop + (off || 0) / 9525 : null);
        ws.getImages().forEach(im => {
            const tl = im.range.tl, br = im.range.br, ext = im.range.ext;
            const x = X(tl.nativeCol, tl.nativeColOff), y = Y(tl.nativeRow, tl.nativeRowOff);
            if (y == null) return;
            const yb = br ? Y(br.nativeRow, br.nativeRowOff) : null;
            const w = br ? X(br.nativeCol, br.nativeColOff) - x : ext && ext.width, hh = yb != null ? yb - y : ext && ext.height;
            const url = imgUrl(im.imageId);
            if (url && w > 0 && hh > 0) layer.insertAdjacentHTML('beforeend', `<img src="${esc(url)}" alt="" style="left:${x}px;top:${y}px;width:${w}px;height:${hh}px">`);
        });
    }

    // ล่าง: ตารางจุดตรวจ — คอลัมน์และหน้าตาเดียวกับตารางจุดตรวจในหน้าสร้างเช็คชีต (Handsontable)
    //  ช่องที่มาจากคอลัมน์ในไฟล์มีขีดสีใต้หัวคอลัมน์ สีเดียวกับหัวคอลัมน์ด้านบน · ✕ ไม่นำเข้า · ✂ ไม่นำเข้าตั้งแต่แถวนี้ลงไป
    const SYS_COLS = [
        { i: 10, label: 'หมวด', k: 'group', w: 130 }, { i: 0, label: 'จุดที่ตรวจสอบ', k: 'check_point', w: 190 }, { i: 1, label: 'มาตรฐานการตรวจสอบ', k: 'standard', w: 180 },
        { i: 2, label: 'วิธีตรวจสอบ/เครื่องมือ', k: 'method', w: 170 }, { i: 3, label: 'ข้อปฏิบัติเมื่อผิดปกติ', k: 'action', w: 170 },
        { i: 4, label: 'ประเภทจุดตรวจสอบ', w: 190, dd: 'type', opts: CHECK_TYPES }, { i: 5, label: 'รูปภาพประกอบ', w: 100, img: true },
        { i: 6, label: 'บังคับถ่ายรูป', w: 190, dd: 'photo', opts: PHOTO_REQS }, { i: 7, label: 'ชื่อค่าวัด', k: 'value_name', w: 110 },
        { i: 8, label: 'หน่วยวัด', k: 'unit', w: 90 }, { i: 9, label: 'ค่าที่คาดหวัง', k: 'expected', w: 110 },
    ];
    function renderRight() {
        // คอลัมน์ที่มีค่ามาจากไฟล์ (ตรง หรือแยกจากมาตรฐาน) => ขีดสีใต้หัวคอลัมน์
        const fromFile = c => c.k && (map.indexOf(c.k) >= 0 || ((c.k === 'expected' || c.k === 'unit' || c.k === 'value_name' || c.k === 'group') && result.items.some(it => it.v[c.i])));
        let h = '<table class="csi-sys border-collapse w-full text-[13px] border border-slate-200"><thead class="sticky top-0 z-[2]"><tr><th class="w-12"></th>'
            + SYS_COLS.map(c => `<th style="min-width:${c.w}px"><div class="truncate">${c.label}</div>${fromFile(c) ? `<div class="mx-auto mt-1 h-[3px] w-10 rounded-full" style="background:${FCOLOR[c.k]}"></div>` : ''}`
                + (c.dd ? `<select data-no-search data-all="${c.dd}" title="ใช้กับทุกแถว" class="csi-dd mt-1 w-full font-normal">${c.opts.map(o => `<option ${o === (c.dd === 'type' ? defType : defPhoto) ? 'selected' : ''}>${o}</option>`).join('')}</select>` : '') + '</th>').join('')
            + '<th class="w-20 sticky right-0 z-[3]"></th></tr></thead><tbody>';
        h += result.items.map((it, n) => {
            const v = it.v;
            return `<tr data-src="${it.src}" class="group">
                <td class="text-center text-slate-500 bg-slate-50" title="${orient === 'col' ? 'คอลัมน์ ' + XLSX.utils.encode_col(it.col) : 'แถว ' + (it.src + 1)} ในไฟล์">${n + 1}</td>
                ${SYS_COLS.map(c => c.img ? '<td class="text-center text-[11px] text-slate-400 bg-slate-50">+ เพิ่มรูป</td>'
                    : c.dd ? `<td><select data-no-search data-row="${c.dd}" data-src="${it.src}" class="csi-dd w-full">${c.opts.map(o => `<option ${o === v[c.i] ? 'selected' : ''}>${o}</option>`).join('')}</select></td>`
                    : c.i === 10 ? `<td class="${v[10] && v[10] === ((result.items[n - 1] || {}).v || [])[10] ? 'text-slate-300' : 'font-semibold'}">${esc(v[10] || '')}</td>`   // หมวดเดียวกับแถวบน => ตัวจาง
                    : `<td>${esc(v[c.i])}</td>`).join('')}
                <td class="text-right whitespace-nowrap sticky right-0 z-[1] !bg-white shadow-[-6px_0_8px_-6px_rgba(15,23,42,.15)]">
                    ${orient === 'col' ? '' : `<button type="button" data-cut="${it.src}" title="ไม่นำเข้าตั้งแต่แถวนี้ลงไป" class="invisible group-hover:visible w-7 h-7 rounded-md text-slate-400 hover:text-rose-600 hover:bg-rose-50 inline-flex items-center justify-center"><i data-lucide="scissors" class="w-4 h-4"></i></button>`}
                    <button type="button" data-rm="${it.src}" title="ไม่นำเข้าจุดนี้" class="w-7 h-7 rounded-md border border-slate-200 text-slate-400 hover:text-rose-600 hover:border-rose-200 hover:bg-rose-50 inline-flex items-center justify-center"><i data-lucide="x" class="w-4 h-4"></i></button></td>
            </tr>`;
        }).join('');
        $('csi-right').innerHTML = h + '</tbody></table>'
            + (result.items.length ? '' : '<div class="p-8 text-center text-slate-400">ยังไม่มีจุดตรวจ — คลิกหัวคอลัมน์ด้านบนที่เป็นรายการตรวจ แล้วเลือก "จุดที่ตรวจสอบ"</div>');
        $('csi-count').textContent = result.items.length + ' จุด';
        // แถวที่ไม่นำเข้า (ติ๊กออก / ตัดท้าย / ส่วนลงชื่อท้ายฟอร์ม) + คืนได้
        if (orient === 'col') {   // จุดตรวจมาจากหัวคอลัมน์ — แถวข้อมูล (เครื่อง/รายการ) ไม่นำเข้า
            const off = colSel.filter((on, c) => !on && colLevels(c).length).length;
            $('csi-note').innerHTML = `<span class="text-slate-500">จุดตรวจจากหัวคอลัมน์ · แถวข้อมูลด้านล่างไม่นำเข้า${off ? ` · ไม่ใช้ ${off} คอลัมน์` : ''}</span>`;
            $('csi-append').disabled = $('csi-replace').disabled = !result.items.length;
            return;
        }
        const nRm = [...removed].filter(i => result.kind[i] === 'removed').length;
        const notes = [];
        if (nRm) notes.push(`ติ๊กออก ${nRm} แถว`);
        if (cutAt != null) notes.push(`ตัดตั้งแต่แถว ${cutAt + 1}`);
        else if (result.footerAt != null) notes.push(`หยุดที่ส่วนลงชื่อ (แถว ${result.footerAt + 1})`);
        $('csi-note').innerHTML = notes.length ? `<span class="text-slate-500">${notes.join(' · ')}</span>${nRm || cutAt != null ? ' <button type="button" data-restore-all class="ml-1 text-emerald-700 hover:underline">คืนทั้งหมด</button>' : ''}` : '';
        $('csi-append').disabled = $('csi-replace').disabled = !result.items.length;
    }
    function refresh() {
        result = buildItems(opts());
        renderLeft(); renderRight();
        if (window.lucide) lucide.createIcons();
    }

    // ชี้แถวฝั่งหนึ่ง => ไฮไลต์แถว/การ์ดคู่กันอีกฝั่ง (และเลื่อนให้เห็น)
    function pair(src, from) {
        document.querySelectorAll('#csi-modal .csi-hl').forEach(e => e.classList.remove('csi-hl'));
        if (src == null) return;
        document.querySelectorAll(`#csi-modal tr[data-src="${src}"]`).forEach(e => {
            e.classList.add('csi-hl');
            const box = e.closest('#csi-left, #csi-right');
            if (box.id !== from) {
                const bt = box.getBoundingClientRect(), et = e.getBoundingClientRect();
                if (et.top < bt.top + 40 || et.bottom > bt.bottom) box.scrollTo({ top: box.scrollTop + et.top - bt.top - box.clientHeight / 3, behavior: 'smooth' });
            }
        });
    }

    // เมนูเลือกชนิดของคอลัมน์ (คลิกหัวคอลัมน์ตารางไฟล์ด้านบน)
    function openMenu(c, el) {
        const m = $('csi-menu'), rect = el.getBoundingClientRect();
        if (orient === 'col') {
            // แบบจุดตรวจเป็นคอลัมน์: ใช้ / ไม่ใช้ + ชื่อจุดตรวจที่จะได้
            const name = colName(c, $('csi-section').checked);
            m.innerHTML = `<div class="px-3.5 py-1.5 text-xs text-slate-400">คอลัมน์ ${XLSX.utils.encode_col(c)} · ${esc(colLevels(c).join(' / ')) || 'ไม่มีหัว'}</div>`
                + `<div class="px-3.5 pb-2 text-xs max-w-[22rem]">${name ? '<span class="text-slate-400">จะได้จุดตรวจ:</span> <b class="text-slate-700">' + esc(name) + '</b>' : '<span class="text-amber-600">' + (rowOf('check_point') < 0 ? 'ยังไม่ได้เลือกแถวที่เป็นจุดตรวจ — คลิกเลขแถวด้านซ้าย' : 'คอลัมน์นี้ไม่มีชื่อจุดตรวจ') + '</span>'}</div>`
                + `<button type="button" data-set="col-on" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center gap-2.5 ${colSel[c] ? 'font-semibold bg-slate-50' : ''}"><span class="w-3 h-3 rounded-full" style="background:${FCOLOR.check_point}"></span>ใช้เป็นจุดตรวจ</button>`
                + `<div class="border-t border-slate-100 my-1"></div><button type="button" data-set="col-off" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 text-slate-500 ${colSel[c] ? '' : 'font-semibold bg-slate-50'}">ไม่ใช้คอลัมน์นี้ (รหัส ที่ตั้ง หมายเหตุ …)</button>`;
            m.dataset.col = c; showCol(c); m.classList.remove('hidden');
            m.style.left = Math.min(rect.left, window.innerWidth - m.offsetWidth - 8) + 'px';
            m.style.top = Math.min(rect.bottom + 4, window.innerHeight - m.offsetHeight - 8) + 'px';
            return;
        }
        const smp = [...new Set(disp.slice(dataStart()).map(r => norm(clean((r || [])[c]))).filter(Boolean))];
        m.innerHTML = `<div class="px-3.5 py-1.5 text-xs text-slate-400">คอลัมน์ ${XLSX.utils.encode_col(c)} · ${esc(headName(c)) || 'ไม่มีหัว'}</div>`
            + `<div class="px-3.5 pb-2 text-xs max-w-[22rem]">${smp.length ? '<span class="text-slate-400">ข้อความในคอลัมน์:</span> ' + smp.slice(0, 4).map(v => `<span class="inline-block max-w-[10rem] truncate align-bottom px-1.5 rounded bg-blue-50 text-blue-700">${esc(v)}</span>`).join(' ') + (smp.length > 4 ? ` <span class="text-slate-400">+${smp.length - 4}</span>` : '') : '<span class="text-amber-600">ไม่มีข้อความในคอลัมน์นี้</span>'}</div>` +
            FIELDS.map(f => `<button type="button" data-set="${f.k}" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center gap-2.5 ${map[c] === f.k ? 'font-semibold bg-slate-50' : ''}">
                <span class="w-3 h-3 rounded-full" style="background:${FCOLOR[f.k]}"></span>${esc(f.label)}${map.indexOf(f.k) >= 0 && map[c] !== f.k ? `<span class="ml-auto text-[11px] text-slate-400">ย้ายจาก ${XLSX.utils.encode_col(map.indexOf(f.k))}</span>` : ''}</button>`).join('') +
            `<div class="border-t border-slate-100 my-1"></div><button type="button" data-set="" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 text-slate-500">ไม่ใช้คอลัมน์นี้</button>`;
        m.dataset.col = c;
        showCol(c);
        m.classList.remove('hidden');
        m.style.left = Math.min(rect.left, window.innerWidth - m.offsetWidth - 8) + 'px';
        m.style.top = Math.min(rect.bottom + 4, window.innerHeight - m.offsetHeight - 8) + 'px';
    }
    // โหมดคอลัมน์: คลิกเลขแถว => เลือกชนิดของแถว (จุดตรวจ / หมวด / มาตรฐาน …) หรือขยาย/ย่อช่วงแถวหัวตาราง
    function openRowMenu(i, el) {
        const m = $('csi-menu'), rect = el.getBoundingClientRect();
        const sel = colSel.map((on, c) => (on ? c : -1)).filter(c => c >= 0);
        const smp = [...new Set(sel.map(c => cellVal(i, c)).filter(Boolean))];
        const inHead = i >= headRow && i <= headEnd;
        const btn = (k, html, on) => `<button type="button" data-rset="${k}" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center gap-2.5 ${on ? 'font-semibold bg-slate-50' : ''}">${html}</button>`;
        m.innerHTML = `<div class="px-3.5 py-1.5 text-xs text-slate-400">แถว ${i + 1}${inHead ? ' · หัวตาราง' : ''}</div>`
            + `<div class="px-3.5 pb-2 text-xs max-w-[22rem]">${smp.length ? '<span class="text-slate-400">ข้อความในแถว (คอลัมน์ที่นำเข้า):</span> ' + smp.slice(0, 4).map(v => `<span class="inline-block max-w-[10rem] truncate align-bottom px-1.5 rounded bg-blue-50 text-blue-700">${esc(v)}</span>`).join(' ') + (smp.length > 4 ? ` <span class="text-slate-400">+${smp.length - 4}</span>` : '') : '<span class="text-amber-600">ไม่มีข้อความในคอลัมน์ที่นำเข้า</span>'}</div>`
            // ไม่มีข้อความในคอลัมน์ที่นำเข้า => ไม่มีอะไรให้ใช้เป็นจุดตรวจ/มาตรฐาน — ไม่แสดงรายการชนิด (เคยตั้งไว้ => เอาออกได้)
            + (!smp.length ? (rowMap[i] ? btn('', '<span class="text-slate-500">ไม่ใช้แถวนี้</span>', false) : '')
            : (inHead ? '' : '<div class="px-3.5 pb-1.5 text-[11px] text-slate-500">แถวนี้อยู่นอกหัวตาราง — เลือกชนิดแล้วจะรวมเป็นส่วนหัว (ไม่นับเป็นแถวข้อมูล)</div>')
            + FIELDS.map(f => { const at = rowOf(f.k); return btn(f.k, `<span class="w-3 h-3 rounded-full" style="background:${FCOLOR[f.k]}"></span>${esc(f.label)}${at >= 0 && at !== i ? `<span class="ml-auto text-[11px] text-slate-400">ย้ายจากแถว ${at + 1}</span>` : ''}`, rowMap[i] === f.k); }).join('')
            + (inHead ? btn('', '<span class="text-slate-500">ไม่ใช้แถวนี้</span>', !rowMap[i]) : ''))
            + '<div class="border-t border-slate-100 my-1"></div>'
            + btn('top', '<i data-lucide="arrow-up-to-line" class="w-4 h-4 text-slate-400"></i>ตั้งเป็นแถวบนสุดของหัวตาราง', i === headRow)
            + btn('bottom', '<i data-lucide="arrow-down-to-line" class="w-4 h-4 text-slate-400"></i>ตั้งเป็นแถวล่างสุดของหัวตาราง', i === headEnd);
        m.dataset.row = i;
        m.classList.remove('hidden');
        if (window.lucide) lucide.createIcons({ root: m });
        m.style.left = Math.min(rect.right + 4, window.innerWidth - m.offsetWidth - 8) + 'px';
        m.style.top = Math.max(8, Math.min(rect.top, window.innerHeight - m.offsetHeight - 8)) + 'px';
    }
    function setRowType(i, k) {
        if (k === 'top' || k === 'bottom') {
            if (k === 'top') { headRow = i; if (headEnd < i) headEnd = i; } else { headEnd = i; if (headRow > i) headRow = i; }
            Object.keys(rowMap).forEach(r => { if (+r < headRow || +r > headEnd) delete rowMap[r]; });
            if (rowOf('check_point') < 0) rowMap[headEnd] = rowMap[headEnd] || 'check_point';
            return;
        }
        Object.keys(rowMap).forEach(r => { if (k && rowMap[r] === k) delete rowMap[r]; });   // ชนิดหนึ่งใช้ได้แถวเดียว
        if (k) { rowMap[i] = k; if (i > headEnd) headEnd = i; if (i < headRow) headRow = i; }   // แถวนอกหัวตาราง => ขยายหัวตารางให้รวม
        else delete rowMap[i];
    }
    function showCol(c) {
        const band = $('csi-band'), xt = document.querySelector('#csi-left .csi-xt');
        document.querySelectorAll('#csi-left .csi-ct, #csi-left .csi-hov').forEach(e => e.classList.remove('csi-ct', 'csi-hov'));
        if (!band || !xt) return;
        if (c == null || !(c + 1 < colX.length)) { band.style.display = 'none'; return; }
        Object.assign(band.style, { display: 'block', left: colX[c] + 'px', width: (colX[c + 1] - colX[c]) + 'px', height: xt.offsetHeight + 'px' });
        const th = document.querySelector('#csi-left .csi-colh[data-col="' + c + '"]');
        if (th) th.classList.add('csi-hov');
        document.querySelectorAll('#csi-left td[data-c]').forEach(td => { const s0 = +td.dataset.c; if (s0 <= c && c < s0 + +td.dataset.cs) td.classList.add('csi-ct'); });
    }
    const closeMenu = () => { if ($('csi-menu')) $('csi-menu').classList.add('hidden'); showCol(null); };

    // แท็บชีตใต้ตารางไฟล์ (แบบ Excel) — ผู้ใช้มักจำไม่ได้ว่าแก้ชีตไหน: แท็บที่เลือกเป็นสีเขียว + ป้าย "เปิดล่าสุด" ที่ชีตที่เปิดอยู่ตอนบันทึกไฟล์
    function renderTabs() {
        $('csi-sname').textContent = sheetName;
        $('csi-tabs').innerHTML = '<span class="self-center text-slate-500 pr-1.5 shrink-0">ชีต</span>' + sheetList.map((n, i) =>
            `<button type="button" data-sheet="${i}" class="csi-tab${n === sheetName ? ' csi-cur' : ''}"${n === lastSheet ? ' title="ชีตที่เปิดอยู่ตอนบันทึกไฟล์ครั้งล่าสุด"' : ''}>${esc(n)}${n === lastSheet ? '<span class="csi-last">เปิดล่าสุด</span>' : ''}</button>`).join('');
        const cur = $('csi-tabs').querySelector('.csi-cur');
        if (cur) cur.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }
    function loadSheet(name) {
        sheetName = name;
        renderTabs();
        removed = new Set(); cutAt = null; rowOpt = {};
        rows = sheetRows(sheetName);
        disp = rawRows(sheetName);   // ค่าที่แสดงในหน้าตาแบบ Excel (เซลล์รวมแสดงครั้งเดียว)
        const hc = detectColLayout();   // มีหัว "รหัส" + "สถานที่" => แบบจุดตรวจเป็นคอลัมน์
        setOrient(hc >= 0 ? 'col' : 'row', hc);
        $('csi-empty').classList.add('hidden');
        $('csi-main').classList.remove('hidden');
        refresh();
    }
    // สลับแบบ จุดตรวจเป็นแถว / เป็นคอลัมน์ (ปุ่มที่แถบตารางไฟล์) · h = แถวหัวตาราง (ไม่ระบุ => หาให้)
    function setOrient(o, h) {
        if (o === 'col') {
            const hc = h != null && h >= 0 ? h : detectColLayout();
            setupCol(hc >= 0 ? hc : headRow);
        } else {
            orient = 'row';
            removed = new Set(); cutAt = null; rowOpt = {};
            headRow = detectHeader(); map = autoMap();
        }
        document.querySelectorAll('#csi-modal [data-orient]').forEach(b => b.classList.toggle('csi-on', b.dataset.orient === orient));
        ['csi-split', 'csi-footer'].forEach(id => $(id).closest('label').classList.toggle('hidden', orient === 'col'));
    }

    async function readFile(f) {
        const buf = await f.arrayBuffer();
        // ตรวจลายเซ็นไฟล์: SheetJS อ่านไฟล์เสีย/ไม่ใช่ Excel เป็นข้อความขยะได้โดยไม่แจ้ง error
        //  .xlsx/.xlsm = ZIP (PK) · .xls = OLE (D0 CF 11 E0) หรือ ZIP/HTML/XML ที่ Excel บันทึกเป็น .xls
        const sig = new Uint8Array(buf.slice(0, 8)), zip = sig[0] === 0x50 && sig[1] === 0x4B, ole = sig[0] === 0xD0 && sig[1] === 0xCF && sig[2] === 0x11 && sig[3] === 0xE0;
        const head = new TextDecoder('utf-8').decode(buf.slice(0, 64)).trimStart();   // TextDecoder ตัด BOM ให้เอง
        if (!buf.byteLength) throw new Error('ไฟล์ว่าง');
        if (/\.xls[xm]$/i.test(f.name) && !zip) throw new Error('ไฟล์นี้ไม่ใช่ไฟล์ Excel (.xlsx) หรือไฟล์เสีย — ลองเปิดใน Excel แล้วบันทึกใหม่เป็น .xlsx');
        if (/\.xls$/i.test(f.name) && !zip && !ole && !head.startsWith('<')) throw new Error('ไฟล์นี้ไม่ใช่ไฟล์ Excel (.xls) หรือไฟล์เสีย — ลองเปิดใน Excel แล้วบันทึกใหม่เป็น .xlsx');
        wb = /\.csv$/i.test(f.name) ? XLSX.read(new TextDecoder('utf-8').decode(buf), { type: 'string' }) : XLSX.read(buf, { type: 'array' });
        // รูปแบบเซลล์ (.xlsx / .xlsm) — อ่านไม่ได้ก็แสดงเป็นตารางธรรมดา
        Object.values(imgCache).forEach(u => { if (/^blob:/.test(u)) URL.revokeObjectURL(u); });
        xwb = null; imgCache = {};
        if (/\.xls[xm]$/i.test(f.name)) {
            try { const EJ = await loadExcelJs(); xwb = new EJ.Workbook(); await xwb.xlsx.load(buf.slice(0)); readTheme(); } catch (e) { xwb = null; }
        }
        // แท็บ = ชีตที่ไม่ได้ซ่อน (ซ่อนหมด => แสดงทุกชีต)
        const wbm = wb.Workbook || {}, meta = wbm.Sheets || [];
        sheetList = wb.SheetNames.filter((n, i) => !(meta[i] && meta[i].Hidden));
        if (!sheetList.length) sheetList = wb.SheetNames.slice();
        // ชีตที่เปิดอยู่ตอนบันทึกไฟล์ (activeTab ของ workbookView — ไม่ระบุ = ชีตแรก) · CSV / .xls บางแบบไม่มีข้อมูลนี้
        const wv = (wbm.WBView || [])[0];
        lastSheet = wv ? wb.SheetNames[+wv.activeTab || 0] || '' : '';
        if (!sheetList.includes(lastSheet)) lastSheet = '';
        // เปิดชีตที่เปิดอยู่ตอนบันทึกก่อน (ชีตที่เพิ่งแก้) ถ้ามีหัวตาราง — ไม่มี/เป็นชีตหมายเหตุ => ชีตที่มีหัวตารางชัดที่สุด
        let best = sheetList[0], bestS = -1;
        const score = {};
        sheetList.forEach(n => {
            const s = score[n] = Math.max(0, ...sheetRows(n).slice(0, 30).map(scoreHeader));
            if (s > bestS) { bestS = s; best = n; }
        });
        const hasData = n => sheetRows(n).some(r => r.some(v => String(v).trim()));
        loadSheet(lastSheet && (score[lastSheet] > 0 || (bestS <= 0 && hasData(lastSheet))) ? lastSheet : best);
        fileName = f.name;
        $('csi-fname').textContent = f.name;   // อ่านสำเร็จแล้วจึงเปลี่ยนชื่อไฟล์ (ไฟล์เสีย => ยังเป็นไฟล์เดิมที่แสดงอยู่)
    }

    // popup เต็มจอ (Fullscreen API — iframe ของ main.php เปิด allowfullscreen ไว้) · เปิดอัตโนมัติตอนเปิด popup, ปุ่มสลับ/Esc ย่อกลับ
    // เต็มจอ = popup เต็มหน้าต่างเบราว์เซอร์ (ไม่มีขอบ) — หน้าในกรอบ iframe ของ main.php ขอขยายกรอบทับเมนู/แถบบนด้วย (setFrameMax)
    //  ไม่ใช้ Fullscreen API: Chrome ออกจากเต็มจอทุกครั้งที่เปิดหน้าต่างเลือกไฟล์ และกลับเข้าเองไม่ได้
    let fsOn = false;
    const isFs = () => fsOn;
    function setFs(on) {
        const m = $('csi-modal'), box = $('csi-box');
        fsOn = !!on && !!m;
        try { if (window.parent !== window && window.parent.setFrameMax) window.parent.setFrameMax(fsOn); } catch (e) { /* คนละโดเมน */ }
        if (!m || !box) return;
        m.classList.toggle('p-2', !fsOn); m.classList.toggle('sm:p-4', !fsOn);
        ['rounded-2xl', 'max-w-[1500px]', 'h-[94vh]'].forEach(c => box.classList.toggle(c, !fsOn));
        box.classList.toggle('h-full', fsOn);
        $('csi-fs').innerHTML = `<i data-lucide="${fsOn ? 'minimize-2' : 'maximize-2'}" class="w-5 h-5"></i>`;
        if (window.lucide) lucide.createIcons();
    }
    // มุมมอง (จำไว้ในเบราว์เซอร์): layout tb = บน-ล่าง / lr = ซ้าย-ขวา · ratio = ขนาดฝั่งไฟล์ (%) · zoom = ซูมตารางไฟล์
    const VIEW_KEY = 'easypro_cs_import_view';
    const view = { layout: 'tb', ratio: 50, zoom: 1 };
    try { Object.assign(view, JSON.parse(localStorage.getItem(VIEW_KEY)) || {}); } catch (e) { /* โหมดส่วนตัว */ }
    const saveView = () => { try { localStorage.setItem(VIEW_KEY, JSON.stringify(view)); } catch (e) { /* โหมดส่วนตัว */ } };
    function applyLayout() {
        const lr = view.layout === 'lr';
        $('csi-main').classList.toggle('flex-col', !lr);
        $('csi-pa').style.flex = `0 0 ${view.ratio}%`;
        $('csi-gutter').className = 'csi-gut ' + (lr ? 'csi-gut-v' : 'csi-gut-h');
        document.querySelectorAll('#csi-modal [data-layout]').forEach(b => b.classList.toggle('csi-on', b.dataset.layout === view.layout));
    }
    function setZoom(z) {
        view.zoom = Math.max(0.3, Math.min(2.5, Math.round(z * 100) / 100));
        if ($('csi-xl')) $('csi-xl').style.zoom = view.zoom;
        $('csi-zv').textContent = Math.round(view.zoom * 100) + '%';
        saveView();
    }
    const ZSTEP = [0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1, 1.1, 1.25, 1.5, 1.75, 2, 2.5];
    const zoomStep = d => setZoom(d > 0 ? ZSTEP.find(z => z > view.zoom + 0.001) || 2.5 : [...ZSTEP].reverse().find(z => z < view.zoom - 0.001) || 0.3);
    function close() { const m = $('csi-modal'); if (m) { setFs(false); m.remove(); } window.removeEventListener('keydown', onKey, true); }
    function onKey(e) { if (e.key === 'Escape') { e.stopImmediatePropagation(); e.preventDefault();   // ฟังแบบ capture ที่ window => SweetAlert ที่เปิดอยู่ด้านล่างไม่ปิดตาม
        if ($('csi-menu') && !$('csi-menu').classList.contains('hidden')) closeMenu(); else close(); } }
    function apply(mode) {
        const items = buildItems(opts()).items.map(it => it.v);
        if (!items.length) return;
        if (orient === 'row') saveMap();   // จำการจับคู่คอลัมน์เฉพาะแบบจุดตรวจเป็นแถว
        // เรียกจากหน้าต่าง SweetAlert (pm_import) => ไม่แสดง Swal ซ้ำ (เปิดได้ทีละอัน จะปิดหน้าต่างแก้ไข)
        const own = openOpt.apply;
        (own || window.csImportApply)(items, mode);
        close();
        if (!own && window.Swal) Swal.fire({ icon: 'success', title: `นำเข้า ${items.length} จุดตรวจ`, text: (targetName() ? 'ลงเช็คชีต ' + targetName() + ' — ' : '') + 'ตรวจสอบในตาราง แล้วกดบันทึกเช็คชีต', timer: 1800, showConfirmButton: false });
    }

    // ข้อผิดพลาดแสดงเป็นแถบในหน้าต่างนี้ — ไม่ใช้ SweetAlert: ถูกหน้าต่างนี้บัง และถ้าเปิดซ้อนบน SweetAlert (pm_import) จะปิดหน้าต่างแก้ไขที่อยู่ด้านล่าง
    function showErr(title, msg) {
        const box = $('csi-err');
        if (!box) return;
        box.classList.toggle('hidden', !title);
        $('csi-err-title').textContent = title || '';
        $('csi-err-msg').textContent = msg || '';
    }
    let openOpt = {};
    window.openChecksheetImport = function (opt) {
        openOpt = opt || {};
        if (typeof XLSX === 'undefined') { if (window.Swal) Swal.fire('ข้อผิดพลาด', 'ไม่พบตัวอ่านไฟล์ Excel (XLSX)', 'error'); return; }
        close();
        if (!$('csi-style')) document.head.insertAdjacentHTML('beforeend', '<style id="csi-style">#csi-modal tr.csi-hl > td { background-color: #fef9c3 !important; } #csi-modal .csi-sys tr:hover td { background-color: #f8fafc; } #csi-modal details > summary::-webkit-details-marker { display: none; } #csi-modal .csi-sys th { background: #f8fafc; color: #334155; font-weight: 500; text-align: center; padding: 6px 8px; border-bottom: 1px solid #e2e8f0; border-left: 1px solid #e2e8f0; } #csi-modal .csi-dd { border: 1px solid #e2e8f0; border-radius: 6px; padding: 3px 6px; background: #fff; font-size: 13px; color: #1e293b; } #csi-modal .csi-sys td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; border-left: 1px solid #e2e8f0; vertical-align: top; color: #1e293b; }'
            // หน้าตาแบบ Excel
            + ' #csi-modal .csi-xt { border-collapse: separate; border-spacing: 0; table-layout: fixed; background: #fff; color: #000; font-family: Calibri, Tahoma, sans-serif; font-size: 11pt; }'
            + ' #csi-modal .csi-xt td { padding: 0 3px; overflow: hidden; white-space: nowrap; line-height: 1.2; vertical-align: bottom; }'
            + ' #csi-modal .csi-xt td.ov { overflow: visible; } #csi-modal .csi-xt.gl td { border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; }'
            + ' #csi-modal .csi-xt thead th { position: sticky; top: 0; z-index: 3; height: 40px; background: #f3f4f6; border-right: 1px solid #d4d4d8; border-bottom: 1px solid #d4d4d8; color: #6b7280; font: 500 12px/1.25 system-ui, sans-serif; text-align: center; padding: 0 2px; overflow: hidden; white-space: nowrap; }'
            + ' #csi-modal .csi-xt thead th.csi-colh { cursor: pointer; } #csi-modal .csi-xt thead th.csi-colh:hover { background: #e5e7eb !important; } #csi-modal .csi-ck { font-weight: 600; font-size: 11px; overflow: hidden; text-overflow: ellipsis; }'
            + ' #csi-modal .csi-xt thead th.csi-corner { left: 0; z-index: 4; }'
            + ' #csi-modal .csi-xt th.csi-rh { position: sticky; left: 0; z-index: 2; background: #f3f4f6; border-right: 1px solid #d4d4d8; border-bottom: 1px solid #d4d4d8; color: #6b7280; font: 400 11px/1.1 system-ui, sans-serif; padding: 0 4px 0 6px; vertical-align: middle; }'
            + ' #csi-modal .csi-xt th.csi-rh > div { display: flex; align-items: center; gap: 4px; overflow: hidden; } #csi-modal .csi-xt th.csi-rh input { width: 13px; height: 13px; accent-color: #059669; cursor: pointer; } #csi-modal .csi-xt th.csi-rh span { cursor: pointer; } #csi-modal .csi-xt th.csi-rh span:hover { color: #d97706; }'
            + ' #csi-modal .csi-xt th.csi-rhc { cursor: pointer; } #csi-modal .csi-xt th.csi-rhc:hover { background: #e5e7eb; } #csi-modal .csi-rk { font-size: 10px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }'
            + ' #csi-modal .csi-xt tr.csi-off > td { opacity: .3; } #csi-modal .csi-xt tr.csi-hl > th { background: #fde68a; }'
            + ' #csi-imgs img { position: absolute; z-index: 1; pointer-events: none; }'
            + ' #csi-band { display: none; position: absolute; top: 0; z-index: 1; pointer-events: none; background: rgba(37,99,235,.08); box-shadow: inset 2px 0 0 #2563eb, inset -2px 0 0 #2563eb; }'
            + ' #csi-modal .csi-xt td.csi-ct { box-shadow: inset 0 0 0 2px #2563eb; } #csi-modal .csi-xt thead th.csi-hov { background: #dbeafe !important; color: #1d4ed8; }'
            + ' #csi-modal .csi-gut { flex: 0 0 8px; background: #f1f5f9; position: relative; touch-action: none; } #csi-modal .csi-gut:hover, #csi-modal .csi-gut.drag { background: #a7f3d0; }'
            + ' #csi-modal .csi-gut::after { content: ""; position: absolute; inset: 0; margin: auto; border-radius: 2px; background: #94a3b8; }'
            + ' #csi-modal .csi-gut-h { cursor: row-resize; } #csi-modal .csi-gut-h::after { width: 40px; height: 3px; } #csi-modal .csi-gut-v { cursor: col-resize; } #csi-modal .csi-gut-v::after { width: 3px; height: 40px; }'
            + ' #csi-modal .csi-on { background: #ecfdf5; color: #047857; }'
            + ' #csi-modal .csi-tab { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; white-space: nowrap; color: #475569; border-radius: 0 0 6px 6px; } #csi-modal .csi-tab:hover { background: #e2e8f0; }'
            + ' #csi-modal .csi-tab.csi-cur { background: #fff; color: #047857; font-weight: 600; box-shadow: inset 0 -3px 0 #10b981, 0 1px 2px rgba(15,23,42,.12); }'
            + ' #csi-modal .csi-last { font-size: 10px; font-weight: 500; line-height: 1; padding: 3px 6px; border-radius: 999px; background: #fef3c7; color: #b45309; }</style>');
        document.body.insertAdjacentHTML('beforeend', modalHtml());
        setFs(true);
        applyLayout();
        $('csi-zv').textContent = Math.round(view.zoom * 100) + '%';
        if (window.lucide) lucide.createIcons();
        const m = $('csi-modal');
        m.addEventListener('click', e => {
            if (e.target.closest('[data-csi-close]') || e.target === m) { close(); return; }
            const rset = e.target.closest('#csi-menu [data-rset]');
            if (rset) { setRowType(+$('csi-menu').dataset.row, rset.dataset.rset); closeMenu(); refresh(); return; }
            const rowh = e.target.closest('[data-rowh]');
            if (rowh) { openRowMenu(+rowh.dataset.rowh, rowh); return; }
            const set = e.target.closest('#csi-menu [data-set]');
            if (set) {
                const c = +$('csi-menu').dataset.col, k = set.dataset.set;
                if (k === 'col-on' || k === 'col-off') { colSel[c] = k === 'col-on'; closeMenu(); refresh(); return; }   // เมนูหัวคอลัมน์ (โหมดคอลัมน์) // แบบจุดตรวจเป็นคอลัมน์
                if (k) map = map.map((v, i) => (v === k && i !== c ? '' : v));   // ชนิดหนึ่งใช้ได้คอลัมน์เดียว
                map[c] = k;
                closeMenu(); refresh();
                return;
            }
            if (e.target.closest('#csi-fs')) { setFs(!isFs()); return; }
            if (e.target.closest('[data-err-close]')) { showErr(''); return; }
            const ori = e.target.closest('[data-orient]');
            if (ori) { if (ori.dataset.orient !== orient && rows.length) { setOrient(ori.dataset.orient); refresh(); } return; }
            const lay = e.target.closest('[data-layout]');
            if (lay) { view.layout = lay.dataset.layout; saveView(); applyLayout(); return; }
            const zb = e.target.closest('[data-zoom]');
            if (zb) {
                const z = zb.dataset.zoom;
                if (z === 'in' || z === 'out') zoomStep(z === 'in' ? 1 : -1);
                else if (z === 'fit') { if (tableW) setZoom(($('csi-left').clientWidth - 2) / tableW); }
                else setZoom(1);
                return;
            }
            const tb = e.target.closest('[data-sheet]');
            if (tb) { const n = sheetList[+tb.dataset.sheet]; if (n != null && n !== sheetName) loadSheet(n); return; }
            const th = e.target.closest('.csi-colh');
            if (th) { openMenu(+th.dataset.col, th); return; }
            const tg = e.target.closest('[data-toggle]');
            if (tg) { const i = +tg.dataset.toggle; if (tg.checked) removed.delete(i); else removed.add(i); refresh(); return; }
            const rm = e.target.closest('[data-rm]');
            if (rm) { const v = rm.dataset.rm; if (/^c\d+$/.test(v)) colSel[+v.slice(1)] = false; else removed.add(+v); refresh(); return; }
            const cut = e.target.closest('[data-cut]');
            if (cut) { cutAt = +cut.dataset.cut; refresh(); return; }
            if (e.target.closest('[data-restore-all]')) { removed = new Set(); cutAt = null; refresh(); return; }
            const hd = e.target.closest('[data-head]');
            if (hd) { if (orient === 'col') setupCol(+hd.dataset.head); else { headRow = +hd.dataset.head; map = autoMap(); } refresh(); return; }
            if (!e.target.closest('#csi-menu')) closeMenu();
        });
        m.addEventListener('mouseover', e => {
            // ชี้หัวคอลัมน์ไฟล์ => ไฮไลต์คอลัมน์ (เมนูเปิดอยู่ => คงคอลัมน์ของเมนู)
            const menuOpen = !$('csi-menu').classList.contains('hidden');
            const ch = e.target.closest('#csi-left .csi-colh');
            const el = e.target.closest('#csi-left tr[data-src], #csi-right tr[data-src]');
            // แบบจุดตรวจเป็นคอลัมน์: ชี้แถวในตารางจุดตรวจ => ไฮไลต์คอลัมน์ต้นทางในไฟล์
            const srcCol = orient === 'col' && el && /^c\d+$/.test(el.dataset.src) ? +el.dataset.src.slice(1) : null;
            if (!menuOpen) showCol(ch ? +ch.dataset.col : srcCol);
            pair(el ? el.dataset.src : null, el ? el.closest('#csi-left, #csi-right').id : '');
        });
        window.addEventListener('keydown', onKey, true);
        $('csi-file').addEventListener('change', e => {
            const f = e.target.files[0];
            showErr('');
            if (f) readFile(f).catch(err => showErr('อ่านไฟล์ไม่ได้: ' + f.name, err && err.message ? err.message : String(err)));
            e.target.value = '';   // เลือกไฟล์เดิมซ้ำได้ (หลังแก้ไฟล์)
        });
        // Ctrl + ล้อเมาส์ = ซูมตารางไฟล์
        $('csi-left').addEventListener('wheel', e => { if (!e.ctrlKey) return; e.preventDefault(); zoomStep(e.deltaY < 0 ? 1 : -1); }, { passive: false });
        // ลากเส้นแบ่งเพื่อปรับขนาดสองฝั่ง (15–85%) · ดับเบิลคลิก = ครึ่งต่อครึ่ง
        const gut = $('csi-gutter');
        gut.addEventListener('pointerdown', e => {
            e.preventDefault();
            gut.setPointerCapture(e.pointerId); gut.classList.add('drag');
            const r = $('csi-main').getBoundingClientRect(), lr = view.layout === 'lr';
            const mv = ev => { view.ratio = Math.max(15, Math.min(85, lr ? (ev.clientX - r.left) / r.width * 100 : (ev.clientY - r.top) / r.height * 100)); $('csi-pa').style.flex = `0 0 ${view.ratio}%`; };
            const up = () => { gut.removeEventListener('pointermove', mv); gut.removeEventListener('pointerup', up); gut.classList.remove('drag'); saveView(); };
            gut.addEventListener('pointermove', mv); gut.addEventListener('pointerup', up);
        });
        gut.addEventListener('dblclick', () => { view.ratio = 50; saveView(); applyLayout(); });
        ['csi-split', 'csi-section', 'csi-footer'].forEach(id => $(id).addEventListener('change', refresh));
        // ประเภทจุดตรวจ / บังคับถ่ายรูป ในตารางล่าง: หัวคอลัมน์ = ทั้งตาราง, ในแถว = เฉพาะแถว
        m.addEventListener('change', e => {
            const all = e.target.closest('select[data-all]');
            if (all) {
                const f = all.dataset.all;
                if (f === 'type') defType = all.value; else defPhoto = all.value;
                Object.keys(rowOpt).forEach(k => { delete rowOpt[k][f]; });
                refresh(); return;
            }
            const one = e.target.closest('select[data-row]');
            if (one) { const k = one.dataset.src; rowOpt[k] = rowOpt[k] || {}; rowOpt[k][one.dataset.row] = one.value; refresh(); }
        });
        $('csi-append').addEventListener('click', () => apply('append'));
        $('csi-replace').addEventListener('click', () => apply('replace'));
    };
    // ทดสอบ / ใช้ซ้ำ
    window.csImportParse = { splitValue };
})();
