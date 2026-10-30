/**
 * Search Select — ทำให้ <select> ทุกตัวในหน้าค้นหาได้ (พิมพ์กรองตัวเลือก)
 *
 * select ตัวจริงยังอยู่และเป็นที่เก็บค่าเหมือนเดิม (ซ่อนไว้) โค้ดเดิมที่อ่าน/ตั้ง .value, เติม <option>,
 * ฟัง change / onchange จึงทำงานเหมือนเดิมทั้งหมด — ตัวนี้แค่เพิ่มปุ่มแสดงค่า + กล่องค้นหาตอนกด
 *
 * ใช้: <script src="js/search-select.js"></script> แล้วทุก select (รวมที่สร้างทีหลัง) จะถูกแปลงเอง
 * ไม่แปลง: select multiple / size > 1 / ที่ใช้ Choices.js / ใน Handsontable / ใส่ data-no-search
 */
(function () {
    if (window.SearchSelect) return;

    const css = `
        .ss-native { position: absolute !important; opacity: 0 !important; pointer-events: none !important; width: 1px !important; height: 1px !important; margin: 0 !important; padding: 0 !important; border: 0 !important; }
        .ss-btn { display: inline-flex; align-items: center; gap: .4rem; text-align: left; cursor: pointer; background-color: #fff; min-width: 0; font: inherit; }
        .ss-btn[disabled] { cursor: not-allowed; opacity: .6; background-color: #f8fafc; }
        .ss-btn .ss-text { flex: 1 1 auto; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ss-btn .ss-text.ss-ph { color: #94a3b8; }
        .ss-btn .ss-caret { flex: none; width: 14px; height: 14px; color: #94a3b8; transition: transform .15s; }
        .ss-btn.ss-open .ss-caret { transform: rotate(180deg); }
        .ss-btn.ss-invalid { box-shadow: 0 0 0 2px #fca5a5 !important; border-color: #ef4444 !important; }
        .ss-pop { position: fixed; z-index: 2147482000; background: #fff; border: 1px solid #e2e8f0; border-radius: .75rem; box-shadow: 0 12px 32px -8px rgba(15,23,42,.28);
                  display: flex; flex-direction: column; overflow: hidden; font-family: inherit; font-size: 14px; color: #334155; }
        .ss-pop .ss-q { margin: .5rem; padding: .5rem .7rem .5rem 2rem; border: 1px solid #e2e8f0; border-radius: .6rem; outline: none; font: inherit; font-size: 14px;
                        background: #f8fafc url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round'%3E%3Ccircle cx='11' cy='11' r='7'/%3E%3Cpath d='m20 20-3.5-3.5'/%3E%3C/svg%3E") no-repeat .6rem center; }
        .ss-pop .ss-q:focus { border-color: #38bdf8; background-color: #fff; box-shadow: 0 0 0 3px rgba(14,165,233,.15); }
        .ss-pop .ss-list { overflow-y: auto; padding: 0 .35rem .4rem; overscroll-behavior: contain; }
        .ss-pop .ss-opt { padding: .45rem .6rem; border-radius: .5rem; cursor: pointer; display: flex; align-items: center; gap: .5rem; line-height: 1.35; }
        .ss-pop .ss-opt.ss-act { background: #f0f9ff; }
        .ss-pop .ss-opt.ss-sel { color: #006B9F; font-weight: 600; }
        .ss-pop .ss-opt.ss-sel::after { content: '✓'; margin-left: auto; padding-left: .5rem; }
        .ss-pop .ss-opt.ss-dis { color: #cbd5e1; cursor: not-allowed; }
        .ss-pop .ss-grp { padding: .5rem .6rem .25rem; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .03em; }
        .ss-pop .ss-empty { padding: .9rem .6rem; color: #94a3b8; text-align: center; font-size: 13px; }
    `;
    const caret = '<svg class="ss-caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';
    const proto = HTMLSelectElement.prototype;
    const valueDesc = Object.getOwnPropertyDescriptor(proto, 'value');
    const indexDesc = Object.getOwnPropertyDescriptor(proto, 'selectedIndex');
    const enhanced = new Set();
    let pop = null, cur = null, active = -1, items = [];

    function injectCss() {
        if (document.getElementById('ss-css')) return;
        const st = document.createElement('style');
        st.id = 'ss-css'; st.textContent = css;
        document.head.appendChild(st);
    }

    const skip = sel =>
        sel.dataset.ss !== undefined || sel.multiple || sel.size > 1 || sel.hasAttribute('data-no-search') ||
        sel.classList.contains('choices__input') || sel.closest('.choices') || sel.closest('.handsontable') || sel.closest('.ss-pop');

    function labelOf(sel) {
        const o = sel.options[sel.selectedIndex];
        return o ? { text: o.text.trim(), ph: o.value === '' } : { text: '', ph: true };
    }

    function refresh(sel) {
        const btn = sel._ssBtn;
        if (!btn) return;
        const { text, ph } = labelOf(sel);
        const t = btn.firstElementChild;
        if (t.textContent !== (text || ' ')) t.textContent = text || ' ';
        t.classList.toggle('ss-ph', ph);
        btn.title = text;
        btn.disabled = sel.disabled;
        // ซ่อน/แสดงตาม select ตัวจริง (class hidden หรือ style display ที่โค้ดเดิมตั้ง)
        btn.classList.toggle('hidden', sel.classList.contains('hidden'));
        btn.style.display = sel.style.display === 'none' ? 'none' : '';
        if (sel.checkValidity()) btn.classList.remove('ss-invalid');
    }

    function enhance(sel) {
        if (skip(sel)) return;
        sel.dataset.ss = '1';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = (sel.className || '').replace(/\bhidden\b/g, '') + ' ss-btn';
        if (sel.getAttribute('style')) btn.setAttribute('style', sel.getAttribute('style'));
        const lbl = sel.getAttribute('aria-label') || (sel.id && document.querySelector(`label[for="${CSS.escape(sel.id)}"]`)?.textContent.trim());
        if (lbl) btn.setAttribute('aria-label', lbl);
        btn.setAttribute('aria-haspopup', 'listbox');
        // select ที่ทำลูกศรเอง (appearance-none + ไอคอน) ไม่ต้องใส่ลูกศรซ้ำ
        btn.innerHTML = '<span class="ss-text"></span>' + (sel.classList.contains('appearance-none') ? '' : caret);
        sel.insertAdjacentElement('afterend', btn);
        sel.classList.add('ss-native');
        sel.tabIndex = -1;
        sel._ssBtn = btn;

        // โค้ดเดิมตั้ง .value / .selectedIndex ตรง ๆ => อัปเดตปุ่มทันที
        Object.defineProperty(sel, 'value', { configurable: true, get() { return valueDesc.get.call(this); }, set(v) { valueDesc.set.call(this, v); refresh(this); } });
        Object.defineProperty(sel, 'selectedIndex', { configurable: true, get() { return indexDesc.get.call(this); }, set(v) { indexDesc.set.call(this, v); refresh(this); } });
        sel.addEventListener('change', () => refresh(sel));
        sel.addEventListener('invalid', () => btn.classList.add('ss-invalid'));
        new MutationObserver(() => refresh(sel)).observe(sel, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'class', 'style', 'selected'] });

        btn.addEventListener('click', e => { e.preventDefault(); e.stopPropagation(); cur === sel ? close() : open(sel); });
        btn.addEventListener('keydown', e => {
            if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key) || (e.key.length === 1 && !e.ctrlKey && !e.metaKey)) {
                e.preventDefault();
                open(sel, e.key.length === 1 && e.key !== ' ' ? e.key : '');
            }
        });
        enhanced.add(sel);
        refresh(sel);
    }

    function build(q) {
        const sel = cur, list = pop.querySelector('.ss-list');
        const nq = q.trim().toLowerCase();
        items = []; active = -1;
        let html = '', lastGroup = null;
        [...sel.options].forEach((o, i) => {
            if (o.hidden || o.style.display === 'none') return;
            if (nq && !o.text.toLowerCase().includes(nq)) return;
            const g = o.parentElement && o.parentElement.tagName === 'OPTGROUP' ? o.parentElement.label : null;
            if (g && g !== lastGroup) { html += `<div class="ss-grp">${esc(g)}</div>`; lastGroup = g; }
            const isSel = i === sel.selectedIndex;
            if (isSel && !o.disabled) active = items.length;
            html += `<div class="ss-opt${isSel ? ' ss-sel' : ''}${o.disabled ? ' ss-dis' : ''}" role="option" data-i="${i}" data-k="${items.length}">${esc(o.text) || '&nbsp;'}</div>`;
            items.push({ i, dis: o.disabled });
        });
        list.innerHTML = html || '<div class="ss-empty">ไม่พบรายการที่ค้นหา</div>';
        if (active < 0 && nq) active = items.findIndex(x => !x.dis);
        mark(true);
    }

    function mark(scroll) {
        pop.querySelectorAll('.ss-opt').forEach(el => el.classList.toggle('ss-act', +el.dataset.k === active));
        const el = pop.querySelector(`.ss-opt[data-k="${active}"]`);
        if (el && scroll) el.scrollIntoView({ block: 'nearest' });
    }

    // ตำแหน่งกล่อง: ใช้พื้นที่ที่มองเห็นจริง (visualViewport) — มือถือเปิดแป้นพิมพ์แล้วจอเหลือครึ่งเดียว กล่องต้องไม่ไปอยู่ใต้แป้นพิมพ์
    function place() {
        if (!cur || !pop) return;
        const r = cur._ssBtn.getBoundingClientRect();
        const vv = window.visualViewport;
        const vTop = vv ? vv.offsetTop : 0, vBottom = vv ? vv.offsetTop + vv.height : innerHeight;
        const vw = innerWidth, vh = innerHeight;
        const w = Math.min(Math.max(r.width, 240), vw - 16);
        const below = vBottom - r.bottom - 8, above = r.top - vTop - 8;
        const up = below < 260 && above > below;
        const maxH = Math.max(120, Math.min(340, (up ? above : below) - 4));
        pop.style.width = w + 'px';
        pop.style.left = Math.max(8, Math.min(r.left, vw - w - 8)) + 'px';
        pop.style.maxHeight = maxH + 'px';
        if (up) { pop.style.top = ''; pop.style.bottom = (vh - r.top + 4) + 'px'; }
        else { pop.style.bottom = ''; pop.style.top = (r.bottom + 4) + 'px'; }
    }
    // เลื่อนหน้า / ขนาดจอเปลี่ยน => ย้ายกล่องตามปุ่ม (ไม่ปิด) — ปิดเฉพาะเมื่อปุ่มเลื่อนพ้นจอ
    //  มือถือ: แตะแล้ว focus ช่องค้นหา => แป้นพิมพ์ขึ้น (resize ความสูง) + เบราว์เซอร์เลื่อนหน้าให้เห็นช่องพิมพ์ (scroll)
    //  ถ้าปิดตามเหตุการณ์เหล่านี้ กล่องจะปิดทันทีพร้อมแป้นพิมพ์
    function follow() {
        if (!cur || !pop) return;
        const r = cur._ssBtn.getBoundingClientRect();
        if (r.bottom < 0 || r.top > innerHeight || (!r.width && !r.height)) { close(); return; }
        place();
    }

    function open(sel, typed) {
        if (sel.disabled) return;
        close();
        cur = sel;
        pop = document.createElement('div');
        pop.className = 'ss-pop';
        pop.setAttribute('role', 'listbox');
        pop.innerHTML = '<input type="search" class="ss-q" placeholder="พิมพ์เพื่อค้นหา..." autocomplete="off"><div class="ss-list"></div>';
        // อยู่ใน SweetAlert => วางกล่องไว้ใน container ของ Swal (ไม่งั้นคลิกในกล่อง = คลิกนอกป๊อปอัป แล้ว Swal ปิด)
        (sel.closest('.swal2-container') || document.body).appendChild(pop);
        sel._ssBtn.classList.add('ss-open');
        const q = pop.querySelector('.ss-q');
        q.value = typed || '';
        build(q.value);
        place();
        q.focus({ preventScroll: true });
        q.addEventListener('input', () => build(q.value));
        q.addEventListener('keydown', e => {
            const n = items.length;
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!n) return;
                let k = active;
                for (let s = 0; s < n; s++) { k = (k + (e.key === 'ArrowDown' ? 1 : -1) + n) % n; if (!items[k].dis) break; }
                active = k; mark(true);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (active >= 0 && items[active] && !items[active].dis) choose(items[active].i);
            } else if (e.key === 'Escape') {
                e.preventDefault(); e.stopPropagation();
                const b = cur._ssBtn; close(); b.focus();
            } else if (e.key === 'Tab') close();
        });
        pop.addEventListener('mousedown', e => { if (!e.target.closest('.ss-q')) e.preventDefault(); });
        pop.addEventListener('click', e => {
            const el = e.target.closest('.ss-opt');
            if (el && !el.classList.contains('ss-dis')) choose(+el.dataset.i);
        });
        pop.addEventListener('mousemove', e => {
            const el = e.target.closest('.ss-opt');
            if (el && +el.dataset.k !== active) { active = +el.dataset.k; mark(false); }
        });
    }

    function choose(i) {
        const sel = cur;
        const changed = indexDesc.get.call(sel) !== i;
        indexDesc.set.call(sel, i);
        refresh(sel);
        const b = sel._ssBtn;
        close();
        b.focus({ preventScroll: true });
        if (changed) {
            sel.dispatchEvent(new Event('input', { bubbles: true }));
            sel.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function close() {
        if (pop) { pop.remove(); pop = null; }
        if (cur && cur._ssBtn) cur._ssBtn.classList.remove('ss-open');
        cur = null;
    }

    function esc(s) { return String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]); }

    function scan(root) {
        if (!root || !root.querySelectorAll) return;
        if (root.tagName === 'SELECT') enhance(root);
        root.querySelectorAll('select').forEach(enhance);
    }

    function init() {
        injectCss();
        scan(document.body);
        // select ที่สร้างทีหลัง (ป๊อปอัป ตารางที่วาดใหม่ ขั้นตอนในฟอร์ม)
        new MutationObserver(ms => ms.forEach(m => m.addedNodes.forEach(nd => nd.nodeType === 1 && scan(nd)))).observe(document.body, { childList: true, subtree: true });
        // กันกรณีโค้ดเดิมเปลี่ยนค่าแบบที่ไม่มีเหตุการณ์ (เช่น form.reset() / option.selected = true)
        setInterval(() => enhanced.forEach(sel => { if (!sel.isConnected) { enhanced.delete(sel); return; } refresh(sel); }), 600);
        document.addEventListener('mousedown', e => { if (pop && !pop.contains(e.target) && !(cur && cur._ssBtn.contains(e.target))) close(); }, true);
        document.addEventListener('scroll', e => { if (pop && !pop.contains(e.target)) follow(); }, true);
        // ความกว้างเปลี่ยน (หมุนจอ / ย่อหน้าต่าง) => ปิด · ความสูงอย่างเดียว (แป้นพิมพ์มือถือ) => ย้ายตำแหน่ง
        let lastW = innerWidth;
        window.addEventListener('resize', () => { if (innerWidth !== lastW) { lastW = innerWidth; close(); } else follow(); });
        if (window.visualViewport) { visualViewport.addEventListener('resize', follow); visualViewport.addEventListener('scroll', follow); }
    }

    window.SearchSelect = { refresh: sel => refresh(sel), enhance, close };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
