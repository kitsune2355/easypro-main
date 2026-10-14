/* js/repair-hold.js — พักงาน / ติดตามงาน ในรายละเอียดงานซ่อม (maintenance_info.php > การดำเนินการ ขั้นที่ 2)
 *  - พักงาน / กลับมาดำเนินการ บันทึกทันที (ไม่ต้องกด "บันทึกข้อมูล") ผ่าน handle_repair_hold.php
 *  - รออะไหล่: ค้นหาอะไหล่จากทุกคลัง เลือกได้หลายรายการ — กลับมาดำเนินการแล้วอะไหล่ที่เข้าแล้วถูกเพิ่มลงส่วนปิดงาน
 *    (ตัดสต๊อกตอนกด "บันทึกข้อมูล" ตามขั้นตอนเดิม)
 *  ใช้ของหน้าเดิม: escapeHtml, safeIcons, addPartRow, updatePartsTotal, refreshGrid, Swal
 */
(function () {
  const API = 'handle_repair_hold.php';
  const REASONS = {
    parts:    { label: 'รออะไหล่',              hint: 'ไม่มีของในคลัง / สั่งซื้อแล้ว', cls: 'amber' },
    supplier: { label: 'ส่งต่อผู้รับเหมา/ซัพ',  hint: 'งานเกินความสามารถทีม',        cls: 'violet' },
    access:   { label: 'รอเข้าพื้นที่/รอผู้แจ้ง', hint: 'ร้านปิด / ผู้แจ้งไม่อยู่',      cls: 'sky' },
    other:    { label: 'อื่นๆ',                 hint: 'ต้องระบุเหตุผล',              cls: 'slate' },
  };
  const TONE = {   // พื้น/ขอบ/ตัวอักษรของการ์ดตามเหตุผล
    amber:  'border-amber-300 bg-amber-50 text-amber-800',
    violet: 'border-violet-300 bg-violet-50 text-violet-800',
    sky:    'border-sky-300 bg-sky-50 text-sky-800',
    slate:  'border-slate-300 bg-slate-50 text-slate-700',
  };
  const esc = s => (typeof escapeHtml === 'function' ? escapeHtml(s) : String(s ?? ''));
  const box = () => document.getElementById('hold_box');
  const pad = n => String(n).padStart(2, '0');
  const thDate = ymd => { if (!ymd) return '-'; const [y, m, d] = String(ymd).slice(0, 10).split('-'); return `${d}/${m}/${+y + 543}`; };
  const thDT = s => s ? `${thDate(s)} ${String(s).slice(11, 16)} น.` : '-';
  const today = () => { const d = new Date(); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`; };
  const since = s => { const ms = Date.now() - new Date(String(s).replace(' ', 'T')).getTime(); const d = Math.floor(ms / 864e5), h = Math.floor(ms % 864e5 / 36e5);
    return d > 0 ? `${d} วัน${h ? ' ' + h + ' ชม.' : ''}` : (h > 0 ? `${h} ชม.` : 'ไม่ถึง 1 ชม.'); };
  const inputCls = 'w-full text-xs font-semibold text-slate-700 bg-white border border-slate-200 rounded-lg px-3 py-2 focus:border-amber-500 focus:ring-2 focus:ring-amber-100 outline-none';

  let S = { rp: 0, status: '', active: null, history: [], form: null, q: '', results: [], loading: false };

  async function api(action, params, body) {
    const url = new URL(API, location.href); url.searchParams.set('action', action);
    Object.entries(params || {}).forEach(([k, v]) => url.searchParams.set(k, v));
    const r = await fetch(url, body ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) } : {});
    let j = null; try { j = await r.json(); } catch (e) {}
    if (!j || !j.success) throw new Error((j && j.error) || 'เชื่อมต่อไม่สำเร็จ');
    return j;
  }

  // เปิดใบงาน => โหลดสถานะพักงาน
  async function load(row) {
    S = { rp: +(row && row.id) || 0, status: String(row && row.status || '').toLowerCase(), active: null, history: [], form: null, q: '', results: [], loading: true };
    render();
    if (!S.rp) return;
    try { const j = await api('get', { rp_id: S.rp }); S.status = String(j.status || S.status).toLowerCase(); S.active = j.active; S.history = j.history || []; }
    catch (e) { S.error = e.message; }
    S.loading = false; render();
  }

  // ---------- แสดงผล ----------
  function render() {
    const el = box(); if (!el) return;
    const step = document.getElementById('step-hold');
    if (step) step.classList.toggle('is-holding', !!S.active);
    if (S.loading) { el.innerHTML = '<div class="text-[11px] text-slate-400">กำลังโหลด...</div>'; return; }
    if (S.error) { el.innerHTML = `<div class="text-[11px] text-rose-600">${esc(S.error)}</div>`; return; }
    let html = '';
    if (S.active) html = activeHtml(S.active);
    else if (S.form) html = formHtml();
    else if (S.status === 'inprogress') html = `<div class="flex flex-wrap items-center gap-2">
        <button type="button" data-hold="open" class="h-9 px-3 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold inline-flex items-center gap-1.5"><i data-lucide="pause" class="w-4 h-4"></i> พักงาน</button>
        <span class="text-[11px] text-slate-500">เช่น รออะไหล่ ส่งต่อผู้รับเหมา รอเข้าพื้นที่</span></div>`;
    else html = `<div class="text-[11px] text-slate-500">${S.status === 'pending' ? 'บันทึกการรับงานก่อน จึงพักงานได้' : 'งานนี้ไม่ได้อยู่ระหว่างดำเนินการ'}</div>`;
    if (S.history.length) html += historyHtml();
    el.innerHTML = html;
    if (typeof safeIcons === 'function') safeIcons();
  }

  function detailRows(h) {
    const d = h.detail || {}, rows = [];
    if (h.reason === 'parts') {
      rows.push(['อะไหล่', `<div class="space-y-1">${(h.parts || []).map(p => `<div><b>${esc(p.name)}</b> ×${p.qty} ${esc(p.unit || '')}
        <span class="text-slate-500">· ${esc(p.wh_name)}</span>
        ${p.arrived_at ? '<span class="ml-1 px-1.5 rounded-full bg-emerald-100 text-emerald-700 font-bold text-[10px]">✓ เข้าคลังแล้ว</span>' : '<span class="ml-1 px-1.5 rounded-full bg-white/70 text-slate-500 font-bold text-[10px]">รอของ</span>'}</div>`).join('')}</div>`]);
      if (d.pr) rows.push(['PR/PO', esc(d.pr)]);
    }
    if (h.reason === 'supplier') {
      rows.push(['ผู้รับเหมา', esc(d.company)]);
      if (d.contact) rows.push(['ผู้ติดต่อ', esc(d.contact)]);
      if (d.po) rows.push(['ใบเสนอราคา/PO', esc(d.po)]);
      if (d.cost) rows.push(['ค่าใช้จ่ายโดยประมาณ', Number(d.cost).toLocaleString() + ' บาท']);
    }
    if (h.reason === 'access') { if (d.person) rows.push(['ผู้ประสาน', esc(d.person)]); if (d.appt) rows.push(['วันนัด', thDate(d.appt)]); }
    if (h.reason === 'other') rows.push(['เหตุผล', esc(d.why)]);
    if (h.eta) rows.push(['คาดว่ากลับมาทำ', thDate(h.eta) + (!h.ended_at && h.eta < today() ? ' <span class="px-1.5 rounded-full bg-rose-100 text-rose-700 font-bold text-[10px]">เลยกำหนด</span>' : '')]);
    if (h.note) rows.push(['หมายเหตุ', esc(h.note)]);
    return rows;
  }

  function activeHtml(h) {
    const r = REASONS[h.reason] || REASONS.other;
    const parts = h.parts || [], got = parts.filter(p => p.arrived_at).length;
    return `<div class="rounded-xl border-2 ${TONE[r.cls]} p-3 space-y-2">
      <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
        <i data-lucide="pause-circle" class="w-4 h-4"></i> กำลังพักงาน · ${esc(r.label)}
        <span class="font-normal opacity-80">(${since(h.started_at)})</span>
        ${parts.length ? `<span class="px-2 rounded-full ${got === parts.length ? 'bg-emerald-100 text-emerald-700' : 'bg-white/70'} text-[10px]">${got === parts.length ? '✓ อะไหล่ครบแล้ว' : `อะไหล่เข้า ${got}/${parts.length}`}</span>` : ''}
      </div>
      <dl class="grid grid-cols-[110px_minmax(0,1fr)] gap-x-3 gap-y-1 text-[11px] text-slate-700">
        ${detailRows(h).map(([k, v]) => `<dt class="text-slate-500">${k}</dt><dd>${v}</dd>`).join('')}
        <dt class="text-slate-500">พักงานโดย</dt><dd>${esc(h.started_by)} · ${thDT(h.started_at)}</dd>
      </dl>
      <div class="flex justify-end"><button type="button" data-hold="resume" class="h-9 px-3 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold inline-flex items-center gap-1.5"><i data-lucide="play" class="w-4 h-4"></i> กลับมาดำเนินการ</button></div>
    </div>`;
  }

  function historyHtml() {
    const end = { resume: 'กลับมาดำเนินการ', close: 'ปิดงาน', cancel: 'ยกเลิก/ล้างการรับงาน' };
    return `<details class="mt-3 group"><summary class="cursor-pointer text-[11px] font-bold text-slate-500 select-none">ประวัติการพักงาน (${S.history.length} ครั้ง)</summary>
      <ul class="mt-2 space-y-2">${S.history.map(h => `<li class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] text-slate-600">
        <div class="font-bold text-slate-700">${esc((REASONS[h.reason] || REASONS.other).label)} <span class="font-normal text-slate-400">· ${thDT(h.started_at)} → ${thDT(h.ended_at)} (${esc(end[h.end_type] || '-')})</span></div>
        ${h.reason === 'parts' && (h.parts || []).length ? `<div>${h.parts.map(p => `${esc(p.name)} ×${p.qty}${p.arrived_at ? ' ✓' : ''}`).join(', ')}</div>` : ''}
        ${h.note ? `<div class="text-slate-500">${esc(h.note)}</div>` : ''}</li>`).join('')}</ul></details>`;
  }

  function formHtml() {
    const F = S.form, e = F.err || {}, v = F.v;
    const field = (key, label, input, full, req) => `<div class="${full ? 'col-span-2' : ''}">
      <label class="block text-[10px] font-bold text-slate-500 mb-0.5" for="hold_${key}">${label}${req ? ' <span class="text-rose-500">*</span>' : ''}</label>${input}
      ${e[key] ? `<p class="mt-0.5 text-[10px] font-bold text-rose-600">${esc(e[key])}</p>` : ''}</div>`;
    const inp = (key, type = 'text', ph = '') => `<input id="hold_${key}" data-k="${key}" type="${type}" value="${esc(v[key] || '')}" placeholder="${esc(ph)}" class="${inputCls} ${e[key] ? '!border-rose-400' : ''}" ${type === 'date' ? `min="${today()}"` : ''}>`;
    let extra = '';
    if (F.reason === 'parts') extra = field('parts', 'อะไหล่ที่รอ — ค้นหาจากทุกคลัง แล้วกดเพื่อเพิ่ม (หลายรายการได้)',
      `<input id="hold_psearch" type="search" autocomplete="off" placeholder="พิมพ์ชื่อ / รหัส / รุ่น เช่น หลอด, บัลลาสต์" value="${esc(S.q)}" class="${inputCls}">
       <div id="hold_presults" class="mt-1.5 space-y-1 max-h-56 overflow-y-auto">${resultsHtml()}</div>
       <div id="hold_prows" class="mt-2 space-y-1.5">${rowsHtml()}</div>`, true, true)
      + field('pr', 'เลขที่ PR/PO', inp('pr', 'text', 'ถ้ามี'), true);
    if (F.reason === 'supplier') extra = field('company', 'บริษัทผู้รับเหมา/ซัพ', inp('company', 'text', 'เช่น บจก. ...'), false, true) + field('contact', 'ผู้ติดต่อ / เบอร์', inp('contact'))
      + field('po', 'เลขที่ใบเสนอราคา/PO', inp('po')) + field('cost', 'ค่าใช้จ่ายโดยประมาณ (บาท)', inp('cost', 'number'));
    if (F.reason === 'access') extra = field('person', 'ผู้ที่ต้องประสาน', inp('person', 'text', 'เช่น เจ้าของร้าน / ผู้แจ้ง')) + field('appt', 'วันนัดเข้าพื้นที่', inp('appt', 'date'));
    if (F.reason === 'other') extra = field('why', 'เหตุผล', inp('why', 'text', 'ระบุเหตุผลที่ต้องพักงาน'), true, true);
    const etaReq = F.reason === 'parts' || F.reason === 'supplier';
    return `<div class="rounded-xl border border-amber-200 bg-amber-50/40 p-3 space-y-3">
      <div class="text-[11px] text-slate-600">เลือกเหตุผลที่ต้องพักงาน (ช่องที่ต้องกรอกเปลี่ยนตามเหตุผล)</div>
      <div class="grid grid-cols-2 gap-2">${Object.entries(REASONS).map(([k, r]) => `<button type="button" data-hold-reason="${k}"
        class="text-left rounded-xl border-2 px-3 py-2 ${F.reason === k ? 'border-amber-500 bg-amber-50' : 'border-slate-200 bg-white hover:border-slate-300'}">
        <div class="text-xs font-bold text-slate-800">${r.label}</div><div class="text-[10px] text-slate-500">${r.hint}</div></button>`).join('')}</div>
      ${F.reason ? `<div class="grid grid-cols-2 gap-2">${extra}
        ${field('eta', 'คาดว่าจะกลับมาทำ', inp('eta', 'date'), false, etaReq)}
        ${field('note', 'หมายเหตุ', `<textarea id="hold_note" data-k="note" rows="2" class="${inputCls} resize-none" placeholder="รายละเอียดเพิ่มเติม">${esc(v.note || '')}</textarea>`, true)}</div>` : ''}
      <div class="flex justify-end gap-2">
        <button type="button" data-hold="cancel" class="h-9 px-3 rounded-lg border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:bg-slate-50">ยกเลิก</button>
        <button type="button" data-hold="save" ${F.reason && !F.saving ? '' : 'disabled'} class="h-9 px-3 rounded-lg bg-amber-500 hover:bg-amber-600 disabled:opacity-40 text-white text-xs font-bold inline-flex items-center gap-1.5"><i data-lucide="pause" class="w-4 h-4"></i> ${F.saving ? 'กำลังบันทึก...' : 'ยืนยันพักงาน'}</button>
      </div></div>`;
  }

  function resultsHtml() {
    const q = S.q.trim();
    if (!q) return '<div class="text-[10px] text-slate-400">พิมพ์เพื่อค้นหา — ผลลัพธ์บอกคลังและจำนวนที่ใช้ได้ (คลังที่มีของอยู่บนสุด)</div>';
    if (S.searching) return '<div class="text-[10px] text-slate-400">กำลังค้นหา...</div>';
    if (!S.results.length) return `<div class="text-[10px] text-slate-500">ไม่พบ "${esc(q)}" ในคลังใด — ตรวจตัวสะกด หรือแจ้งผู้ดูแลสต๊อกให้เพิ่มรายการสินค้า</div>`;
    const picked = new Set(S.form.v.parts.map(p => p.ps_id));
    return S.results.map(r => { const on = picked.has(r.ps_id);
      return `<button type="button" data-hold-pick="${r.ps_id}" ${on ? 'disabled' : ''} class="w-full flex items-center justify-between gap-2 text-left rounded-lg border border-slate-200 bg-white px-3 py-1.5 hover:border-amber-400 hover:bg-amber-50 disabled:opacity-50 disabled:hover:bg-white">
        <span class="min-w-0"><span class="block text-[11px] font-bold text-slate-800 truncate">${esc(r.name)} <span class="font-normal text-slate-400">${esc(r.code || '')}</span></span>
          <span class="block text-[10px] text-slate-500 truncate">${esc(r.wh_name)}${r.model ? ' · ' + esc(r.model) : ''}</span></span>
        <span class="flex-none text-right text-[10px] font-bold ${r.avail > 0 ? 'text-emerald-600' : 'text-slate-400'}">${on ? 'เลือกแล้ว' : (r.avail > 0 ? `ใช้ได้ ${r.avail}` : 'ไม่มีของ')}${!on && r.reserved ? `<span class="block font-normal text-slate-400">จองแล้ว ${r.reserved}</span>` : ''}</span></button>`; }).join('');
  }

  function rowsHtml() {
    const rows = S.form.v.parts;
    if (!rows.length) return '';
    return `<div class="text-[10px] font-bold text-slate-500">รายการที่เลือก (${rows.length})</div>` + rows.map((p, i) => `<div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-1.5">
      <div class="min-w-0 flex-1"><div class="text-[11px] font-bold text-slate-800 truncate">${esc(p.name)}</div>
        <div class="text-[10px] text-slate-500 truncate">${esc(p.wh_name)} · ใช้ได้ ${p.avail}${p.avail >= (+p.qty || 1) ? ' <span class="text-emerald-600 font-bold">— มีของพอ เบิกได้เลย</span>' : ''}</div></div>
      <input type="number" min="1" step="1" data-hold-qty="${i}" value="${esc(p.qty)}" class="w-16 px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold text-right" aria-label="จำนวน ${esc(p.name)}">
      <button type="button" data-hold-del="${i}" class="h-8 w-8 rounded-lg border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100 inline-flex items-center justify-center" aria-label="ลบ ${esc(p.name)}"><i data-lucide="x" class="w-4 h-4"></i></button></div>`).join('');
  }

  // ---------- ค้นหาอะไหล่ ----------
  let qTimer = null, qSeq = 0;
  function search(q) {
    S.q = q; clearTimeout(qTimer);
    if (!q.trim()) { S.results = []; S.searching = false; paintResults(); return; }
    S.searching = true; paintResults();
    qTimer = setTimeout(async () => {
      const my = ++qSeq;
      try { const j = await api('search_parts', { q: q.trim() }); if (my !== qSeq) return; S.results = j.data || []; }
      catch (e) { if (my !== qSeq) return; S.results = []; }
      S.searching = false; paintResults();
    }, 250);
  }
  function paintResults() { const r = document.getElementById('hold_presults'); if (r) r.innerHTML = resultsHtml(); }
  function pick(psId) {
    const r = S.results.find(x => x.ps_id === psId); if (!r) return;
    if (!S.form.v.parts.some(p => p.ps_id === psId)) S.form.v.parts.push({ ps_id: r.ps_id, name: r.name, wh_name: r.wh_name, avail: r.avail, qty: '1' });
    S.q = ''; S.results = []; delete S.form.err.parts;
    render(); const s = document.getElementById('hold_psearch'); if (s) s.focus();
  }

  // ---------- บันทึก ----------
  async function save() {
    const F = S.form, v = F.v, err = {};
    const req = { parts: ['eta'], supplier: ['company', 'eta'], access: [], other: ['why'] }[F.reason] || [];
    req.forEach(k => { if (!String(v[k] || '').trim()) err[k] = 'กรุณากรอกช่องนี้'; });
    if (v.eta && v.eta < today()) err.eta = 'ต้องไม่ใช่วันที่ผ่านมาแล้ว';
    if (F.reason === 'parts') {
      if (!v.parts.length) err.parts = 'ค้นหาแล้วกดเลือกอะไหล่อย่างน้อย 1 รายการ';
      else if (v.parts.some(p => !(+p.qty >= 1))) err.parts = 'จำนวนต้องอย่างน้อย 1';
    }
    if (Object.keys(err).length) { F.err = err; render(); return; }
    F.saving = true; render();
    const keys = { parts: ['pr'], supplier: ['company', 'contact', 'po', 'cost'], access: ['person', 'appt'], other: ['why'] }[F.reason];
    const detail = {}; keys.forEach(k => { if (v[k]) detail[k] = v[k]; });
    try {
      const j = await api('hold', null, { rp_id: S.rp, reason: F.reason, eta: v.eta || '', note: v.note || '', detail,
        parts: F.reason === 'parts' ? v.parts.map(p => ({ ps_id: p.ps_id, qty: +p.qty })) : [] });
      S.active = j.active; S.form = null; render();
      if (typeof refreshGrid === 'function') refreshGrid();
      Swal.fire({ icon: 'success', title: 'พักงานแล้ว', text: `${REASONS[j.active.reason].label} — กด "กลับมาดำเนินการ" เมื่อพร้อมทำงานต่อ`, timer: 1800, showConfirmButton: false });
    } catch (e) { F.saving = false; render(); Swal.fire({ icon: 'error', title: 'พักงานไม่สำเร็จ', text: e.message }); }
  }

  async function resume() {
    const h = S.active;
    const got = (h.parts || []).filter(p => p.arrived_at), miss = (h.parts || []).filter(p => !p.arrived_at);
    const ok = await Swal.fire({ icon: 'question', title: 'กลับมาดำเนินการ?',
      html: h.reason === 'parts'
        ? `${got.length ? `อะไหล่ที่เข้าแล้ว <b>${got.length} รายการ</b> จะถูกเพิ่มลงในส่วน <b>ปิดงาน</b> (ตัดสต๊อกเมื่อกด <b>บันทึกข้อมูล</b>)` : 'ยังไม่มีอะไหล่เข้าคลัง'}${miss.length ? `<br><span style="color:#b45309">ยังไม่ได้รับ: ${miss.map(p => esc(p.name) + ' ×' + p.qty).join(', ')}</span>` : ''}`
        : 'สถานะงานกลับเป็น "กำลังดำเนินการ"',
      showCancelButton: true, confirmButtonText: 'กลับมาดำเนินการ', cancelButtonText: 'ยังไม่ใช่', confirmButtonColor: '#0284c7', reverseButtons: true });
    if (!ok.isConfirmed) return;
    try {
      const j = await api('resume', null, { rp_id: S.rp });
      // อะไหล่ที่เข้าแล้ว => เพิ่มลงส่วนปิดงาน (ถ้ามีรายการเดิมคลังเดียวกันอยู่แล้ว => บวกจำนวน)
      let added = 0;
      (j.arrived || []).forEach(p => {
        const ex = [...document.querySelectorAll('#parts_wrap > div')].find(r => r.dataset.productId === String(p.pd_id) && r.dataset.whId === String(p.wh_id));
        if (ex) { const q = ex.querySelector('input[data-role="qty"]'); q.value = (+q.value || 0) + p.qty; q.dispatchEvent(new Event('input')); }
        else if (typeof addPartRow === 'function') addPartRow({ ps_id: p.ps_id, pd_id: p.pd_id, wh_id: p.wh_id, wh_name: p.wh_name, name: p.name, code: p.code, detail: p.detail, brand: p.brand, model: p.model, price: p.price, unit: p.unit }, String(p.qty));
        added++;
      });
      if (added && typeof updatePartsTotal === 'function') updatePartsTotal();
      await load({ id: S.rp, status: 'inprogress' });
      if (typeof refreshGrid === 'function') refreshGrid();
      if (typeof safeIcons === 'function') safeIcons();
      Swal.fire({ icon: 'success', title: 'กลับมาดำเนินการแล้ว',
        html: added ? `เพิ่มอะไหล่ ${added} รายการลงในส่วนปิดงานแล้ว<br>กด <b>บันทึกข้อมูล</b> เพื่อเบิก/ตัดสต๊อก` : '', confirmButtonColor: '#0284c7' });
      if (added) document.getElementById('parts_wrap')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } catch (e) { Swal.fire({ icon: 'error', title: 'ไม่สำเร็จ', text: e.message }); }
  }

  // ---------- เหตุการณ์ ----------
  document.addEventListener('click', e => {
    const el = box(); if (!el || !el.contains(e.target)) return;
    const b = e.target.closest('button'); if (!b) return;
    if (b.dataset.hold === 'open') { S.form = { reason: null, v: { parts: [] }, err: {} }; S.q = ''; S.results = []; render(); }
    else if (b.dataset.hold === 'cancel') { S.form = null; render(); }
    else if (b.dataset.hold === 'save') save();
    else if (b.dataset.hold === 'resume') resume();
    else if (b.dataset.holdReason) { S.form.reason = b.dataset.holdReason; S.form.err = {}; render(); }
    else if (b.dataset.holdPick) pick(+b.dataset.holdPick);
    else if (b.dataset.holdDel !== undefined) { S.form.v.parts.splice(+b.dataset.holdDel, 1); render(); }
  });
  document.addEventListener('input', e => {
    const el = box(); if (!el || !el.contains(e.target) || !S.form) return;
    if (e.target.id === 'hold_psearch') { search(e.target.value); return; }
    if (e.target.dataset.holdQty !== undefined) { S.form.v.parts[+e.target.dataset.holdQty].qty = e.target.value; delete S.form.err.parts; return; }
    const k = e.target.dataset.k;
    if (k) {
      S.form.v[k] = e.target.value;
      if (S.form.err[k]) {   // กรอกแล้ว => ล้างข้อความเตือนของช่องนี้ทันที
        delete S.form.err[k];
        e.target.classList.remove('!border-rose-400');
        const msg = e.target.parentElement.querySelector('p.text-rose-600'); if (msg) msg.remove();
      }
    }
  });
  document.addEventListener('keydown', e => {
    if (e.target.id !== 'hold_psearch' || e.key !== 'Enter') return;
    e.preventDefault();
    const first = document.querySelector('#hold_presults [data-hold-pick]:not([disabled])');
    if (first) pick(+first.dataset.holdPick);
  });

  window.RepairHold = { load };
})();
