// นำเข้าข้อมูลหน้าตั้งค่าจาก Excel + Template (ใช้ร่วมกันทุกหน้าใน settings.php)
//  หน้าใช้: <script src="js/settings_import.js"></script> แล้ว SettingsImport.attach({ entity: 'machines', anchor: '#btn-export-excel', onDone })
//  ต้องมี XLSX (SheetJS) และ SweetAlert ในหน้า · ข้อมูลช่อง/ค่าที่ใช้ได้/การตรวจ/การบันทึก อยู่ที่ handle_settings_import.php (entity)
//  ขั้นตอน: เลือกไฟล์ => จับคู่คอลัมน์ (เดาจากหัวตาราง, คลิกหัวคอลัมน์เพื่อเปลี่ยน) => เซิร์ฟเวอร์ตรวจทุกแถว (ใหม่ / มีอยู่แล้ว / ผิดพลาด)
//  => เลือก อัปเดตข้อมูลเดิม หรือ ข้าม => บันทึกทั้งชุด (มีแถวผิดพลาดที่เลือกนำเข้า = ไม่บันทึกเลย)
(function () {
    if (window.SettingsImport) return;
    const API = 'handle_settings_import.php';
    const LS_MAP = 'easypro_settings_import_maps';
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const norm = s => String(s ?? '').replace(/\s+/g, ' ').trim();
    const low = s => norm(s).toLowerCase().replace(/\s*\*\s*$/, '');   // หัวคอลัมน์ใน Template มี * ท้ายช่องบังคับ
    const $ = id => document.getElementById(id);
    const COLORS = ['#0284c7', '#059669', '#7c3aed', '#e11d48', '#d97706', '#0d9488', '#ea580c', '#475569', '#2563eb', '#16a34a', '#9333ea', '#db2777', '#ca8a04', '#0891b2', '#64748b'];

    let S = null;   // สถานะของหน้าต่างที่เปิดอยู่

    async function api(entity, action, body) {
        const url = `${API}?entity=${encodeURIComponent(entity)}&action=${action}`;
        const res = body ? await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) }) : await fetch(url);
        const txt = await res.text();
        let d;
        try { d = JSON.parse(txt); } catch (e) { throw new Error('เซิร์ฟเวอร์ตอบกลับไม่ถูกต้อง: ' + txt.replace(/<[^>]*>/g, ' ').slice(0, 200)); }
        if (!d.success) throw new Error(d.error || 'ทำรายการไม่สำเร็จ');
        return d;
    }

    // ---------- Template / ข้อมูลปัจจุบันในรูปแบบนำเข้า ----------
    //  ชีต 1 = หัวคอลัมน์ (* = บังคับ) + แถวตัวอย่าง หรือข้อมูลปัจจุบัน · ชีต "ค่าที่ใช้ได้" = รายการของช่องแบบเลือก · ชีต "คำอธิบาย"
    async function downloadTemplate(cfg, withData) {
        Swal.fire({ title: withData ? 'กำลังเตรียมข้อมูล...' : 'กำลังสร้าง Template...', allowOutsideClick: false, didOpen: () => Swal.showLoading(), customClass: { container: 'sxi-swal' } });
        try {
            const d = await api(cfg.entity, withData ? 'export' : 'schema');
            const fields = d.fields;
            const head = fields.map(f => f.label + (f.required ? ' *' : ''));
            const body = withData ? d.rows.map(r => fields.map(f => r[f.k] ?? '')) : [fields.map(f => f.example ?? '')];
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet([head].concat(body));
            ws['!cols'] = fields.map(f => ({ wch: Math.max(12, Math.min(40, (f.label.length + 4) * 1.6)) }));
            XLSX.utils.book_append_sheet(wb, ws, (d.sheet || 'Data').slice(0, 31));
            // ค่าที่ใช้ได้ของช่องแบบเลือก (คอลัมน์ละช่อง)
            const lk = fields.filter(f => (d.lookups || {})[f.k] && d.lookups[f.k].length);
            if (lk.length) {
                const n = Math.max(...lk.map(f => d.lookups[f.k].length));
                const aoa = [lk.map(f => f.label)];
                for (let i = 0; i < n; i++) aoa.push(lk.map(f => d.lookups[f.k][i] ?? ''));
                const ls = XLSX.utils.aoa_to_sheet(aoa);
                ls['!cols'] = lk.map(() => ({ wch: 30 }));
                XLSX.utils.book_append_sheet(wb, ls, 'ค่าที่ใช้ได้');
            }
            const help = [['ช่อง', 'บังคับ', 'คำอธิบาย']].concat(fields.map(f => [f.label, f.required ? 'ใช่' : '', f.hint || '']));
            help.push([], ['ช่องที่มี * ต้องกรอก · ' + (d.keyHint || 'แถวที่รหัสซ้ำกับข้อมูลในระบบ เลือกได้ตอนนำเข้าว่าจะอัปเดตหรือข้าม')]);
            const hs = XLSX.utils.aoa_to_sheet(help);
            hs['!cols'] = [{ wch: 28 }, { wch: 8 }, { wch: 80 }];
            XLSX.utils.book_append_sheet(wb, hs, 'คำอธิบาย');
            const ymd = new Date().toISOString().slice(0, 10).replace(/-/g, '');
            XLSX.writeFile(wb, `${d.file || cfg.entity}_${withData ? 'ข้อมูล_' + ymd : 'Template'}.xlsx`);
            Swal.close();
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'ข้อผิดพลาด', text: e.message, customClass: { container: 'sxi-swal' } });
        }
    }

    // ---------- หน้าต่างนำเข้า ----------
    function modalHtml(title) {
        // เต็มจอเสมอ (หน้าในกรอบ iframe ขยายกรอบทับเมนูด้วย setFrameMax ตอนเปิด)
        return `<div id="sxi-modal" class="fixed inset-0 flex" style="z-index:10050;background:rgba(15,23,42,.5)">
        <div class="bg-white w-full h-full flex flex-col overflow-hidden text-[14px] text-slate-700">
            <div class="flex flex-wrap items-center gap-3 px-5 py-3.5 border-b border-slate-200">
                <div class="font-semibold text-slate-800 text-base mr-1">นำเข้า${esc(title)}จาก Excel</div>
                <label class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg border border-slate-300 hover:bg-slate-50 cursor-pointer">
                    <i class="fa-solid fa-file-arrow-up text-emerald-600"></i><span id="sxi-fname">เลือกไฟล์</span>
                    <input type="file" id="sxi-file" accept=".xlsx,.xls,.xlsm,.csv" class="hidden"></label>
                <select id="sxi-sheet" data-no-search class="hidden border border-slate-300 rounded-lg py-2 px-2.5" title="ชีต"></select>
                <div class="flex-1"></div>
                <button type="button" data-tpl="0" class="px-3 py-2 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600"><i class="fa-solid fa-file-excel text-emerald-600 mr-1.5"></i>Template</button>
                <button type="button" data-sxi-close title="ปิด (Esc)" class="w-9 h-9 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-500"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <div id="sxi-err" role="alert" class="hidden mx-5 mt-3 flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <i class="fa-solid fa-circle-exclamation text-rose-500 mt-0.5"></i><div class="flex-1 min-w-0" id="sxi-err-msg"></div>
                <button type="button" data-err-close class="text-rose-400 hover:text-rose-700"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div id="sxi-empty" class="flex-1 flex flex-col items-center justify-center text-center p-8 text-slate-500">
                <i class="fa-solid fa-file-excel text-5xl text-slate-300 mb-3"></i>
                <div class="text-base font-medium text-slate-700">เลือกไฟล์ Excel ที่มีข้อมูล${esc(title)}</div>
                <div class="text-sm mt-1.5 max-w-lg">ใช้ไฟล์จากปุ่ม <b>Template</b> (มีหัวคอลัมน์ ตัวอย่าง และค่าที่ใช้ได้) หรือไฟล์ของหน่วยงานเองก็ได้ — คลิกหัวคอลัมน์ (A B C …) เพื่อบอกว่าคอลัมน์นั้นคือช่องไหน</div>
                <div class="mt-4 flex gap-2"><button type="button" data-tpl="0" class="px-4 py-2 rounded-lg border border-emerald-600 text-emerald-700 hover:bg-emerald-50">ดาวน์โหลด Template</button>
                    <button type="button" data-tpl="1" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50">ข้อมูลปัจจุบัน (แก้แล้วนำเข้ากลับ)</button></div>
            </div>
            <div id="sxi-main" class="hidden flex-1 min-h-0 flex flex-col">
                <div class="px-4 py-2 text-sm text-slate-500 flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-slate-100">
                    <b class="text-slate-800">ไฟล์</b><span>คลิกหัวคอลัมน์เพื่อเลือกว่าเป็นช่องไหน · ติ๊กออกเพื่อไม่นำเข้าแถวนั้น · คลิกเลขแถวเพื่อตั้งเป็นแถวหัวตาราง</span>
                    <div class="flex-1"></div><span id="sxi-req" class="text-xs"></span>
                </div>
                <div id="sxi-file-grid" class="overflow-auto border-b-4 border-slate-100" style="flex:0 0 42%"></div>
                <div class="px-4 py-2 text-sm flex flex-wrap items-center gap-x-4 gap-y-1.5">
                    <b class="text-slate-800">ผลการตรวจ</b>
                    <span id="sxi-sum" class="flex flex-wrap gap-1.5"></span>
                    <div class="flex-1"></div>
                    <span class="text-slate-500">รายการที่มีอยู่แล้วในระบบ:</span>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer"><input type="radio" name="sxi-mode" value="update" checked class="accent-emerald-600"> อัปเดตข้อมูลเดิม</label>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer"><input type="radio" name="sxi-mode" value="skip" class="accent-emerald-600"> ข้าม</label>
                </div>
                <div id="sxi-check" class="flex-1 min-h-0 overflow-auto px-4 pb-3"></div>
            </div>
            <div class="flex flex-wrap items-center gap-2 px-5 py-3 border-t border-slate-200">
                <span id="sxi-foot" class="text-sm text-slate-500"></span>
                <div class="flex-1"></div>
                <button type="button" data-sxi-close class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-700">ยกเลิก</button>
                <button type="button" id="sxi-commit" disabled class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium disabled:opacity-40"><i class="fa-solid fa-file-import mr-1.5"></i>นำเข้า</button>
            </div>
        </div>
        <div id="sxi-menu" style="z-index:10051" class="hidden fixed bg-white border border-slate-200 rounded-xl shadow-2xl py-1.5 text-sm min-w-[16rem] max-h-[70vh] overflow-auto"></div></div>`;
    }
    const CSS = `#sxi-modal .sxi-g { border-collapse: separate; border-spacing: 0; font-size: 13px; }
        #sxi-modal .sxi-g th, #sxi-modal .sxi-g td { border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; padding: 4px 8px; white-space: nowrap; max-width: 260px; overflow: hidden; text-overflow: ellipsis; }
        #sxi-modal .sxi-g thead th { position: sticky; top: 0; z-index: 2; background: #f3f4f6; color: #6b7280; font-weight: 500; text-align: center; cursor: pointer; min-width: 90px; }
        #sxi-modal .sxi-g thead th:hover { background: #e5e7eb; }
        #sxi-modal .sxi-g th.sxi-rh { position: sticky; left: 0; z-index: 1; background: #f3f4f6; color: #94a3b8; font-weight: 400; text-align: left; cursor: pointer; min-width: 64px; }
        #sxi-modal .sxi-g thead th.sxi-rh { z-index: 3; cursor: default; }
        #sxi-modal .sxi-g tr.sxi-head td { background: #fffbeb; font-weight: 600; color: #334155; }
        #sxi-modal .sxi-g tr.sxi-off td { opacity: .35; text-decoration: line-through; }
        #sxi-modal .sxi-c th { position: sticky; top: 0; z-index: 2; background: #f8fafc; color: #334155; font-weight: 500; text-align: left; }
        #sxi-modal .sxi-c td.sxi-bad { background: #fef2f2; color: #b91c1c; }
        #sxi-modal .sxi-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .1rem .55rem; border-radius: 999px; font-size: 12px; font-weight: 600; border: 1px solid; white-space: nowrap; }`;

    // ขยายกรอบ iframe ทุกชั้นให้เต็มหน้าต่าง: ชั้นที่มี setFrameMax (main.php) ใช้ของหน้านั้น
    //  ชั้นอื่น (เช่น settings_job_other.php ที่ฝังหน้าอีเมลไว้) ตั้งกรอบเป็น fixed ทับทั้งหน้าชั่วคราว
    function frameMax(on) {
        let w = window;
        try {
            while (w.parent !== w) {
                const p = w.parent;
                if (p.setFrameMax) p.setFrameMax(on);
                else {
                    const fe = w.frameElement;
                    if (fe) {
                        if (on) { if (fe.dataset.sxiStyle === undefined) fe.dataset.sxiStyle = fe.getAttribute('style') || ''; fe.style.cssText += ';position:fixed;inset:0;width:100%;height:100%;z-index:2147483000'; }
                        else if (fe.dataset.sxiStyle !== undefined) { fe.setAttribute('style', fe.dataset.sxiStyle); delete fe.dataset.sxiStyle; }
                    }
                }
                w = p;
            }
        } catch (e) { /* คนละโดเมน */ }
    }

    function close() {
        const m = $('sxi-modal');
        if (m) { frameMax(false); m.remove(); }
        window.removeEventListener('keydown', onKey, true);
        S = null;
    }
    function onKey(e) { if (e.key === 'Escape' && S && !(window.Swal && Swal.isVisible())) { e.stopImmediatePropagation(); if (!$('sxi-menu').classList.contains('hidden')) $('sxi-menu').classList.add('hidden'); else close(); } }
    function showErr(msg) { const b = $('sxi-err'); if (!b) return; b.classList.toggle('hidden', !msg); $('sxi-err-msg').textContent = msg || ''; }

    // ---------- อ่านไฟล์ ----------
    function rawRows(name) {
        const ws = S.wb.Sheets[name];
        if (!ws || !ws['!ref']) return [];
        const range = XLSX.utils.decode_range(ws['!ref']); range.s.r = 0; range.s.c = 0;
        // เซลล์วันที่ของ Excel: SheetJS แสดงตามรูปแบบในไฟล์ (มักเป็น m/d/yy) => ใช้ ปี-เดือน-วัน กันวัน/เดือนสลับ
        Object.keys(ws).forEach(a => {
            const c = ws[a];
            if (a[0] !== '!' && c && c.t === 'n' && c.z && XLSX.SSF.is_date(c.z)) { try { c.w = XLSX.SSF.format('yyyy-mm-dd', c.v); } catch (e) { /* รูปแบบแปลก ใช้ค่าเดิม */ } }
        });
        const aoa = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '', raw: false, dateNF: 'yyyy-mm-dd', blankrows: true, range }).map(r => (r || []).map(v => String(v ?? '')));
        // เซลล์รวม => เติมค่าทุกช่อง (หัวตารางหลายคอลัมน์)
        (ws['!merges'] || []).forEach(m => {
            const v = (aoa[m.s.r] || [])[m.s.c];
            if (v === '' || v == null) return;
            for (let r = m.s.r; r <= m.e.r; r++) { aoa[r] = aoa[r] || []; for (let c = m.s.c; c <= m.e.c; c++) aoa[r][c] = v; }
        });
        return aoa;
    }
    const width = () => S.rows.reduce((m, r) => { for (let c = r.length - 1; c >= m; c--) if (norm(r[c])) return c + 1; return m; }, 0);
    // ชื่อหัวคอลัมน์ตรงกับช่องไหน: ตรงชื่อช่อง (Template) > คำค้นของช่อง
    function matchField(h) {
        const t = low(h);
        if (!t) return null;
        const exact = S.fields.find(f => low(f.label) === t);
        if (exact) return exact.k;
        let best = null, bw = 0;
        S.fields.forEach(f => (f.kw || []).forEach(k => { k = k.toLowerCase(); if (t.includes(k) && k.length > bw) { bw = k.length; best = f.k; } }));
        return best;
    }
    function detectHeader() {
        let best = 0, bs = -1;
        S.rows.slice(0, 30).forEach((r, i) => { const s = new Set(r.map(matchField).filter(Boolean)).size; if (s > bs) { bs = s; best = i; } });
        return best;
    }
    function autoMap() {
        const n = width(), sig = S.cfg.entity + '|' + S.sheet + '|' + Array.from({ length: n }, (_, c) => low((S.rows[S.head] || [])[c])).join('|');
        S.sig = sig;
        try { const saved = (JSON.parse(localStorage.getItem(LS_MAP)) || {})[sig]; if (saved && saved.length === n) return saved.slice(); } catch (e) { /* โหมดส่วนตัว */ }
        const used = new Set();
        return Array.from({ length: n }, (_, c) => { const k = matchField((S.rows[S.head] || [])[c]); if (!k || used.has(k)) return ''; used.add(k); return k; });
    }
    function saveMap() {
        try { const m = JSON.parse(localStorage.getItem(LS_MAP)) || {}; m[S.sig] = S.map; const ks = Object.keys(m); while (ks.length > 40) delete m[ks.shift()]; localStorage.setItem(LS_MAP, JSON.stringify(m)); } catch (e) { /* โหมดส่วนตัว */ }
    }

    async function readFile(f) {
        const buf = await f.arrayBuffer();
        const sig = new Uint8Array(buf.slice(0, 4)), zip = sig[0] === 0x50 && sig[1] === 0x4B, ole = sig[0] === 0xD0 && sig[1] === 0xCF;
        if (!buf.byteLength) throw new Error('ไฟล์ว่าง');
        if (/\.xls[xm]$/i.test(f.name) && !zip) throw new Error('ไฟล์นี้ไม่ใช่ไฟล์ Excel (.xlsx) หรือไฟล์เสีย — ลองเปิดใน Excel แล้วบันทึกใหม่เป็น .xlsx');
        if (/\.xls$/i.test(f.name) && !zip && !ole && !new TextDecoder('utf-8').decode(buf.slice(0, 64)).trimStart().startsWith('<')) throw new Error('ไฟล์นี้ไม่ใช่ไฟล์ Excel (.xls) หรือไฟล์เสีย');
        S.wb = /\.csv$/i.test(f.name) ? XLSX.read(new TextDecoder('utf-8').decode(buf), { type: 'string' }) : XLSX.read(buf, { type: 'array', cellNF: true });   // cellNF = เก็บรูปแบบตัวเลข (.z) ไว้แยกเซลล์วันที่
        // ชีตที่หัวตารางตรงกับช่องมากสุด (ไม่ใช่ชีต "ค่าที่ใช้ได้"/"คำอธิบาย" ของ Template)
        let best = S.wb.SheetNames[0], bs = -1;
        S.wb.SheetNames.forEach(n => {
            if (/^(ค่าที่ใช้ได้|คำอธิบาย)$/.test(n)) return;
            S.sheet = n; S.rows = rawRows(n);
            const s = Math.max(0, ...S.rows.slice(0, 30).map(r => new Set(r.map(matchField).filter(Boolean)).size));
            if (s > bs) { bs = s; best = n; }
        });
        const sel = $('sxi-sheet');
        sel.innerHTML = S.wb.SheetNames.map(n => `<option value="${esc(n)}" ${n === best ? 'selected' : ''}>${esc(n)}</option>`).join('');
        sel.classList.toggle('hidden', S.wb.SheetNames.length < 2);
        loadSheet();
        $('sxi-fname').textContent = f.name;
    }
    function loadSheet() {
        S.sheet = $('sxi-sheet').value;
        S.rows = rawRows(S.sheet);
        S.head = detectHeader();
        S.off = new Set();
        S.map = autoMap();
        $('sxi-empty').classList.add('hidden');
        $('sxi-main').classList.remove('hidden');
        render(); check();
    }

    // แถวข้อมูล (หลังหัวตาราง, ไม่ว่าง, ไม่ได้ติ๊กออก) => { src, values: { field: text } }
    function dataRows() {
        const out = [];
        for (let i = S.head + 1; i < S.rows.length; i++) {
            const r = S.rows[i] || [];
            if (!r.some(v => norm(v))) continue;
            const values = {};
            S.map.forEach((k, c) => { if (k) values[k] = norm(r[c]); });
            if (!Object.values(values).some(Boolean)) continue;
            out.push({ src: i, off: S.off.has(i), values });
        }
        return out;
    }

    // ---------- แสดงผล ----------
    const colorOf = k => COLORS[S.fields.findIndex(f => f.k === k) % COLORS.length];
    function render() {
        const n = width(), last = Math.min(S.rows.length, 300);
        let h = '<table class="sxi-g"><thead><tr><th class="sxi-rh"></th>';
        for (let c = 0; c < n; c++) {
            const k = S.map[c], f = S.fields.find(x => x.k === k);
            h += `<th data-col="${c}" title="คลิกเพื่อเลือกว่าคอลัมน์นี้คือช่องไหน"${k ? ` style="box-shadow:inset 0 3px 0 ${colorOf(k)}"` : ''}><div>${XLSX.utils.encode_col(c)}</div><div class="text-[11px] font-semibold" style="color:${k ? colorOf(k) : '#cbd5e1'}">${f ? esc(f.label) : 'ไม่ใช้'}</div></th>`;
        }
        h += '</tr></thead><tbody>';
        for (let i = 0; i < last; i++) {
            const r = S.rows[i] || [], isHead = i === S.head, data = i > S.head && r.some(v => norm(v));
            h += `<tr data-src="${i}" class="${isHead ? 'sxi-head' : ''}${S.off.has(i) ? ' sxi-off' : ''}"><th class="sxi-rh" data-head="${i}" title="คลิกเพื่อตั้งเป็นแถวหัวตาราง">${data ? `<input type="checkbox" data-toggle="${i}" ${S.off.has(i) ? '' : 'checked'} class="mr-1.5 accent-emerald-600 align-middle">` : '<span class="inline-block w-[13px] mr-1.5"></span>'}${i + 1}${isHead ? ' <span class="text-amber-600 text-[10px]">หัว</span>' : ''}</th>`;
            for (let c = 0; c < n; c++) h += `<td title="${esc(r[c])}">${esc(r[c])}</td>`;
            h += '</tr>';
        }
        $('sxi-file-grid').innerHTML = h + '</tbody></table>' + (S.rows.length > last ? `<div class="p-2 text-xs text-slate-400">แสดง ${last} จาก ${S.rows.length} แถว (นำเข้าทุกแถว)</div>` : '');
        const missing = S.fields.filter(f => f.required && !S.map.includes(f.k));
        $('sxi-req').innerHTML = missing.length ? `<span class="text-rose-600">ยังไม่ได้เลือกคอลัมน์ที่บังคับ: ${missing.map(f => esc(f.label)).join(', ')}</span>` : '<span class="text-emerald-600">เลือกคอลัมน์ที่บังคับครบแล้ว</span>';
    }

    const STATUS = {
        new: ['ใหม่', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
        update: ['มีอยู่แล้ว (อัปเดต)', 'bg-sky-50 text-sky-700 border-sky-200'],
        same: ['เหมือนเดิม', 'bg-slate-50 text-slate-500 border-slate-200'],
        skip: ['มีอยู่แล้ว (ข้าม)', 'bg-slate-50 text-slate-500 border-slate-200'],
        error: ['ผิดพลาด', 'bg-rose-50 text-rose-700 border-rose-200'],
        off: ['ไม่นำเข้า', 'bg-slate-50 text-slate-400 border-slate-200'],
    };
    let checkSeq = 0, checkTimer = null;
    // ให้เซิร์ฟเวอร์ตรวจทุกแถว (ไม่บันทึก) — หน่วงเล็กน้อยระหว่างเปลี่ยนการจับคู่
    function check() {
        clearTimeout(checkTimer);
        checkTimer = setTimeout(async () => {
            const seq = ++checkSeq, rows = dataRows();
            S.result = null;
            $('sxi-check').innerHTML = '<div class="p-6 text-center text-slate-400"><i class="fa-solid fa-circle-notch fa-spin mr-1.5"></i>กำลังตรวจข้อมูล...</div>';
            $('sxi-commit').disabled = true;
            try {
                const d = await api(S.cfg.entity, 'preview', { rows: rows.filter(r => !r.off).map(r => r.values), mode: mode() });
                if (seq !== checkSeq || !S) return;
                S.result = { rows, res: d.rows };
                renderCheck();
            } catch (e) {
                if (seq !== checkSeq || !S) return;
                $('sxi-check').innerHTML = `<div class="p-6 text-center text-rose-600">${esc(e.message)}</div>`;
            }
        }, 250);
    }
    const mode = () => (document.querySelector('input[name="sxi-mode"]:checked') || {}).value || 'update';
    function renderCheck() {
        const { rows, res } = S.result, m = mode();
        let k = 0;
        const list = rows.map(r => { if (r.off) return { r, st: 'off', errors: [] }; const x = res[k++] || {}; let st = x.status; if (st === 'update' && m === 'skip') st = 'skip'; return { r, st, errors: x.errors || [], bad: x.bad || [] }; });
        const cnt = {};
        list.forEach(x => { cnt[x.st] = (cnt[x.st] || 0) + 1; });
        $('sxi-sum').innerHTML = ['new', 'update', 'skip', 'same', 'error', 'off'].filter(s => cnt[s]).map(s => `<span class="sxi-chip ${STATUS[s][1]}">${STATUS[s][0]} ${cnt[s]}</span>`).join('') || '<span class="text-slate-400">ไม่มีข้อมูลในไฟล์</span>';
        const shown = S.fields.filter(f => S.map.includes(f.k));
        $('sxi-check').innerHTML = `<table class="sxi-g sxi-c w-full"><thead><tr><th class="w-14">แถว</th><th class="w-36">สถานะ</th>${shown.map(f => `<th>${esc(f.label)}${f.required ? ' <span class="text-rose-500">*</span>' : ''}</th>`).join('')}<th>หมายเหตุ</th></tr></thead><tbody>`
            + list.map(x => `<tr data-src="${x.r.src}"><td class="text-slate-400">${x.r.src + 1}</td><td><span class="sxi-chip ${STATUS[x.st][1]}">${STATUS[x.st][0]}</span></td>`
                + shown.map(f => `<td class="${(x.bad || []).includes(f.k) ? 'sxi-bad' : ''}" title="${esc(x.r.values[f.k])}">${esc(x.r.values[f.k])}</td>`).join('')
                + `<td class="text-rose-600" title="${esc(x.errors.join(' · '))}">${esc(x.errors.join(' · '))}</td></tr>`).join('')
            + '</tbody></table>';
        const errors = cnt.error || 0, todo = (cnt.new || 0) + (m === 'update' ? (cnt.update || 0) : 0);
        $('sxi-foot').innerHTML = errors ? `<span class="text-rose-600">มี ${errors} แถวผิดพลาด — แก้ในไฟล์แล้วเลือกไฟล์ใหม่ หรือติ๊กแถวนั้นออก</span>` : todo ? `จะเพิ่ม ${cnt.new || 0} · อัปเดต ${m === 'update' ? (cnt.update || 0) : 0} รายการ` : 'ไม่มีรายการที่ต้องบันทึก';
        $('sxi-commit').disabled = !!errors || !todo;
    }

    // เมนูเลือกช่องของคอลัมน์ (พร้อมตัวอย่างข้อความในคอลัมน์)
    function openMenu(c, el) {
        const m = $('sxi-menu'), rect = el.getBoundingClientRect();
        const smp = [...new Set(S.rows.slice(S.head + 1).map(r => norm((r || [])[c])).filter(Boolean))];
        m.innerHTML = `<div class="px-3.5 py-1.5 text-xs text-slate-400">คอลัมน์ ${XLSX.utils.encode_col(c)} · ${esc(norm((S.rows[S.head] || [])[c])) || 'ไม่มีหัว'}</div>`
            + `<div class="px-3.5 pb-2 text-xs max-w-[22rem]">${smp.length ? '<span class="text-slate-400">ข้อความในคอลัมน์:</span> ' + smp.slice(0, 4).map(v => `<span class="inline-block max-w-[10rem] truncate align-bottom px-1.5 rounded bg-blue-50 text-blue-700">${esc(v)}</span>`).join(' ') : '<span class="text-amber-600">ไม่มีข้อความในคอลัมน์นี้</span>'}</div>`
            + S.fields.map(f => { const at = S.map.indexOf(f.k); return `<button type="button" data-set="${f.k}" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 flex items-center gap-2.5 ${S.map[c] === f.k ? 'font-semibold bg-slate-50' : ''}"><span class="w-3 h-3 rounded-full flex-none" style="background:${colorOf(f.k)}"></span>${esc(f.label)}${f.required ? ' <span class="text-rose-500">*</span>' : ''}${at >= 0 && at !== c ? `<span class="ml-auto text-[11px] text-slate-400">ย้ายจาก ${XLSX.utils.encode_col(at)}</span>` : ''}</button>`; }).join('')
            + '<div class="border-t border-slate-100 my-1"></div><button type="button" data-set="" class="w-full text-left px-3.5 py-2 hover:bg-slate-50 text-slate-500">ไม่ใช้คอลัมน์นี้</button>';
        m.dataset.col = c;
        m.classList.remove('hidden');
        m.style.left = Math.min(rect.left, innerWidth - m.offsetWidth - 8) + 'px';
        m.style.top = Math.max(8, Math.min(rect.bottom + 4, innerHeight - m.offsetHeight - 8)) + 'px';
    }

    async function commit() {
        const rows = dataRows().filter(r => !r.off).map(r => r.values), m = mode(), cfg = S.cfg;
        const c = await Swal.fire({ title: 'นำเข้าข้อมูล?', html: $('sxi-foot').innerHTML, icon: 'question', showCancelButton: true, confirmButtonText: 'นำเข้า', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#059669', customClass: { container: 'sxi-swal' } });
        if (!c.isConfirmed) return;
        if (!S) return;   // หน้าต่างนำเข้าถูกปิดระหว่างยืนยัน
        saveMap();
        Swal.fire({ title: 'กำลังนำเข้า...', allowOutsideClick: false, allowEscapeKey: false, didOpen: () => Swal.showLoading(), customClass: { container: 'sxi-swal' } });
        try {
            const d = await api(cfg.entity, 'commit', { rows, mode: m });
            close();
            await Swal.fire({ icon: 'success', title: 'นำเข้าเรียบร้อย', html: `เพิ่ม <b>${d.inserted}</b> · อัปเดต <b>${d.updated}</b> · ข้าม <b>${d.skipped}</b> รายการ` });
            if (cfg.onDone) cfg.onDone(d); else location.reload();
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'นำเข้าไม่สำเร็จ', text: e.message, customClass: { container: 'sxi-swal' } });
        }
    }

    async function open(cfg) {
        close();
        if (typeof XLSX === 'undefined') { Swal.fire('ข้อผิดพลาด', 'ไม่พบตัวอ่านไฟล์ Excel (XLSX)', 'error'); return; }
        let schema;
        try { schema = await api(cfg.entity, 'schema'); } catch (e) { Swal.fire('ข้อผิดพลาด', e.message, 'error'); return; }
        if (!$('sxi-css')) document.head.insertAdjacentHTML('beforeend', `<style id="sxi-css">${CSS} .sxi-swal { z-index: 10100 !important; }</style>`);
        document.body.insertAdjacentHTML('beforeend', modalHtml(schema.title || cfg.title || ''));
        S = { cfg, fields: schema.fields, rows: [], map: [], off: new Set(), head: 0 };
        frameMax(true);
        const m = $('sxi-modal');
        window.addEventListener('keydown', onKey, true);
        m.addEventListener('click', e => {
            if (e.target.closest('[data-sxi-close]') || e.target === m) { close(); return; }
            if (e.target.closest('[data-err-close]')) { showErr(''); return; }
            const tpl = e.target.closest('[data-tpl]');
            if (tpl) { downloadTemplate(cfg, tpl.dataset.tpl === '1'); return; }
            const set = e.target.closest('#sxi-menu [data-set]');
            if (set) {
                const c = +$('sxi-menu').dataset.col, k = set.dataset.set;
                if (k) S.map = S.map.map(v => (v === k ? '' : v));   // ช่องหนึ่งใช้ได้คอลัมน์เดียว
                S.map[c] = k;
                $('sxi-menu').classList.add('hidden'); render(); check(); return;
            }
            const th = e.target.closest('#sxi-file-grid thead th[data-col]');
            if (th) { openMenu(+th.dataset.col, th); return; }
            const tg = e.target.closest('[data-toggle]');
            if (tg) { const i = +tg.dataset.toggle; if (tg.checked) S.off.delete(i); else S.off.add(i); render(); check(); return; }
            const hd = e.target.closest('[data-head]');
            if (hd && !e.target.closest('input')) { S.head = +hd.dataset.head; S.off = new Set(); S.map = autoMap(); render(); check(); return; }
            if (e.target.id === 'sxi-commit' || e.target.closest('#sxi-commit')) { commit(); return; }
            if (!e.target.closest('#sxi-menu')) $('sxi-menu').classList.add('hidden');
        });
        m.addEventListener('change', e => { if (e.target.name === 'sxi-mode' && S.result) renderCheck(); });
        $('sxi-file').addEventListener('change', e => {
            const f = e.target.files[0];
            showErr('');
            if (f) readFile(f).catch(err => showErr('อ่านไฟล์ไม่ได้: ' + f.name + ' — ' + (err && err.message ? err.message : err)));
            e.target.value = '';
        });
        $('sxi-sheet').addEventListener('change', loadSheet);
    }

    // ปุ่ม "นำเข้า Excel" + "Template" ข้างปุ่มส่งออกของหน้า
    function attach(cfg) {
        // ไอคอนในปุ่ม/หน้าต่างใช้ Font Awesome — หน้าที่ใช้ไอคอนชุดอื่น (เช่น หน้าอีเมลแจ้งเตือนใช้ lucide) โหลดเพิ่มให้
        if (!document.querySelector('link[href*="font-awesome"]')) {
            const fa = document.createElement('link');
            fa.rel = 'stylesheet'; fa.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
            document.head.appendChild(fa);
        }
        const anchor = typeof cfg.anchor === 'string' ? document.querySelector(cfg.anchor) : cfg.anchor;
        if (!anchor) return;
        // cfg.btnClass = ปุ่มไอคอนเล็กตามสไตล์ของหน้า (เช่น หัวรายชื่ออาคาร)
        const cls = 'h-10 px-3 md:px-4 rounded-lg text-sm font-semibold transition-all shadow-sm flex items-center gap-2 whitespace-nowrap';
        const imp = document.createElement('button');
        imp.type = 'button'; imp.className = cfg.btnClass || (cls + ' bg-white border border-emerald-600 text-emerald-700 hover:bg-emerald-50');
        imp.innerHTML = cfg.btnClass ? '<i class="fa-solid fa-file-import text-emerald-700"></i>' : '<i class="fa-solid fa-file-import"></i> <span class="hidden sm:inline">นำเข้า Excel</span>';
        imp.title = 'นำเข้าข้อมูลจากไฟล์ Excel';
        imp.addEventListener('click', () => open(cfg));
        const tpl = document.createElement('button');
        tpl.type = 'button'; tpl.className = cfg.btnClass || (cls + ' bg-white border border-slate-200 text-slate-600 hover:bg-slate-50');
        tpl.innerHTML = cfg.btnClass ? '<i class="fa-solid fa-table-list text-slate-500"></i>' : '<i class="fa-solid fa-file-excel text-emerald-600"></i> <span class="hidden sm:inline">Template</span>';
        tpl.title = 'ดาวน์โหลดไฟล์ Template สำหรับนำเข้า';
        tpl.addEventListener('click', async () => {
            const r = await Swal.fire({ title: 'ดาวน์โหลด Template', text: 'ไฟล์ Excel สำหรับนำเข้า — มีหัวคอลัมน์ ค่าที่ใช้ได้ และคำอธิบายช่อง', showDenyButton: true, showCancelButton: true,
                confirmButtonText: 'Template ว่าง', denyButtonText: 'พร้อมข้อมูลปัจจุบัน', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#059669', denyButtonColor: '#0369a1' });
            if (r.isConfirmed) downloadTemplate(cfg, false); else if (r.isDenied) downloadTemplate(cfg, true);
        });
        anchor.insertAdjacentElement('beforebegin', tpl);
        anchor.insertAdjacentElement('beforebegin', imp);
    }

    window.SettingsImport = { attach, open, downloadTemplate };
})();
