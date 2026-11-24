/* เอกสารแนบเครื่องจักร (machine_info.php + machine_history_drawer.php)
   - ในฟอร์ม: เลือก/ลากไฟล์ และกดลบ จะมีผลเมื่อกด "บันทึกข้อมูล" เท่านั้น
   - ในแผงประวัติ: ดูและดาวน์โหลดอย่างเดียว */
const MachineDocs = (() => {
  const API = 'handle_machine_docs.php';
  const EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png', 'txt'];
  const MAX_FILES = 10, MAX_MB = 20;
  let saved = [];      // เอกสารในระบบ
  let removed = [];    // md_id ที่รอลบตอนบันทึก
  let pending = [];    // File ที่รออัปโหลดตอนบันทึก
  let seq = 0;
  let loadErr = '';

  const $ = id => document.getElementById(id);
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const extOf = n => { const i = n.lastIndexOf('.'); return i < 0 ? '' : n.slice(i + 1).toLowerCase(); };
  const size = b => b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB';
  const url = (id, dl) => `${API}?action=file&id=${id}${dl ? '&dl=1' : ''}`;
  const COLOR = { pdf: 'rose', doc: 'sky', docx: 'sky', xls: 'emerald', xlsx: 'emerald', ppt: 'amber', pptx: 'amber' };
  const tag = e => { const c = COLOR[e] || 'slate'; return `<span class="shrink-0 w-11 text-center text-[10px] font-extrabold uppercase rounded-lg py-1.5 bg-${c}-50 text-${c}-600 border border-${c}-100">${esc(e)}</span>`; };
  const icons = () => { if (window.lucide) lucide.createIcons(); };
  const active = () => saved.filter(d => !removed.includes(d.md_id));

  function render() {
    const box = $('doc-list'); if (!box) return;
    const btn = 'shrink-0 w-9 h-9 rounded-xl flex items-center justify-center text-slate-400';
    box.innerHTML = (loadErr ? `<p class="text-xs text-rose-600">${esc(loadErr)}</p>` : '') + active().map(d => `
      <div class="flex items-center gap-3 bg-white border border-slate-200 rounded-2xl px-3 py-2">
        ${tag(d.file_ext)}
        <a href="${url(d.md_id)}" target="_blank" rel="noopener" class="min-w-0 flex-1 hover:text-sky-700" title="${esc(d.file_name)}">
          <span class="block text-xs font-bold truncate">${esc(d.file_name)}</span>
          <span class="block text-[10px] text-slate-400">${size(d.file_size)}</span></a>
        <a href="${url(d.md_id, 1)}" class="${btn} hover:bg-sky-50 hover:text-sky-600" title="ดาวน์โหลด" aria-label="ดาวน์โหลด ${esc(d.file_name)}"><i data-lucide="download" class="w-4 h-4"></i></a>
        <button type="button" data-del="${d.md_id}" class="${btn} hover:bg-rose-50 hover:text-rose-600" title="ลบ" aria-label="ลบ ${esc(d.file_name)}"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
      </div>`).join('') + pending.map((f, i) => `
      <div class="flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-2xl px-3 py-2">
        ${tag(extOf(f.name))}
        <div class="min-w-0 flex-1" title="${esc(f.name)}">
          <span class="block text-xs font-bold text-slate-700 truncate">${esc(f.name)}</span>
          <span class="block text-[10px] text-amber-700">${size(f.size)} · รอบันทึก</span></div>
        <button type="button" data-cancel="${i}" class="${btn} hover:bg-rose-50 hover:text-rose-600" title="เอาออก" aria-label="เอา ${esc(f.name)} ออก"><i data-lucide="x" class="w-4 h-4"></i></button>
      </div>`).join('');
    $('doc-count').textContent = `${active().length + pending.length}/${MAX_FILES}`;
    const note = $('doc-note');
    note.textContent = removed.length ? `จะลบ ${removed.length} ไฟล์เมื่อกดบันทึกข้อมูล` : '';
    note.classList.toggle('hidden', !removed.length);
    icons();
  }

  function add(files) {
    const bad = [];
    Array.from(files).forEach(f => {
      if (!EXT.includes(extOf(f.name))) bad.push(`${f.name}: ชนิดไฟล์นี้แนบไม่ได้`);
      else if (f.size > MAX_MB * 1048576) bad.push(`${f.name}: ใหญ่เกิน ${MAX_MB} MB`);
      else if (!f.size) bad.push(`${f.name}: ไฟล์ว่าง`);
      else if (pending.some(p => p.name === f.name && p.size === f.size && p.lastModified === f.lastModified)) return;
      else if (active().length + pending.length >= MAX_FILES) bad.push(`${f.name}: แนบได้ไม่เกิน ${MAX_FILES} ไฟล์ต่อเครื่อง`);
      else pending.push(f);
    });
    render();
    if (bad.length) Swal.fire({ icon: 'warning', title: 'แนบไฟล์บางรายการไม่ได้', html: bad.map(esc).join('<br>') });
  }

  function reset() { seq++; saved = []; removed = []; pending = []; loadErr = ''; render(); }

  async function list(id) {
    const r = await (await fetch(`${API}?action=list&ass_id=${encodeURIComponent(id)}`)).json();
    if (!r.success) throw new Error(r.message || 'โหลดเอกสารไม่สำเร็จ');
    return r.data;
  }

  async function load(id) {
    reset();
    const my = seq;
    try { const d = await list(id); if (my === seq) saved = d; }
    catch (e) { if (my === seq) loadErr = 'โหลดรายการเอกสารไม่สำเร็จ'; }
    if (my === seq) render();
  }

  async function post(action, data) {
    const fd = new FormData();
    Object.keys(data).forEach(k => fd.append(k, data[k]));
    const res = await fetch(`${API}?action=${action}`, { method: 'POST', body: fd });
    let r; try { r = await res.json(); } catch (e) { throw new Error(res.status === 413 ? 'ไฟล์ใหญ่เกินที่เซิร์ฟเวอร์รับได้' : 'เซิร์ฟเวอร์ตอบกลับผิดพลาด'); }
    if (!r.success) throw new Error(r.message || 'ไม่สำเร็จ');
    return r;
  }

  // เรียกหลังบันทึกเครื่องสำเร็จ · คืนรายการที่ไม่สำเร็จ (รายการเหล่านั้นยังค้างไว้ให้ลองใหม่)
  async function commit(assId, onProgress) {
    const errors = [];
    for (const id of removed.slice()) {
      try { await post('delete', { md_id: id }); saved = saved.filter(d => d.md_id !== id); removed = removed.filter(x => x !== id); }
      catch (e) { const d = saved.find(x => x.md_id === id); errors.push(`ลบ ${d ? d.file_name : ''}: ${e.message}`); }
    }
    const todo = pending.slice();
    for (let i = 0; i < todo.length; i++) {
      if (onProgress) onProgress(i + 1, todo.length);
      try { const r = await post('upload', { ass_id: assId, file: todo[i] }); saved = r.data; pending = pending.filter(f => f !== todo[i]); }
      catch (e) { errors.push(`${todo[i].name}: ${e.message}`); }
    }
    render();
    return errors;
  }

  // แผงประวัติ (ดูอย่างเดียว)
  let histSeq = 0;
  async function renderHistory(assId) {
    const wrap = $('hist-docs'); if (!wrap) return;
    const my = ++histSeq;
    wrap.innerHTML = '';
    let d;
    try { d = await list(assId); } catch (e) { if (my === histSeq) wrap.innerHTML = '<p class="text-[11px] text-rose-600 mb-4">โหลดเอกสารแนบไม่สำเร็จ</p>'; return; }
    if (my !== histSeq || !d.length) return;
    wrap.innerHTML = `<h3 class="text-[13px] font-bold text-slate-800 mb-2 flex items-center gap-2"><i data-lucide="paperclip" class="w-3.5 h-3.5 text-sky-500"></i> เอกสารแนบ (${d.length})</h3>
      <div class="space-y-1.5 mb-5">${d.map(x => `<a href="${url(x.md_id)}" target="_blank" rel="noopener" class="flex items-center gap-3 bg-white border border-slate-200 rounded-2xl px-3 py-2 hover:border-sky-300" title="${esc(x.file_name)}">
        ${tag(x.file_ext)}<span class="min-w-0 flex-1 text-xs font-bold text-slate-700 truncate">${esc(x.file_name)}</span><span class="shrink-0 text-[10px] text-slate-400">${size(x.file_size)}</span></a>`).join('')}</div>`;
    icons();
  }

  document.addEventListener('DOMContentLoaded', () => {
    const zone = $('doc-drop'), input = $('doc-input');
    if (!zone) return;
    input.accept = EXT.map(e => '.' + e).join(',');
    zone.addEventListener('click', () => input.click());
    input.addEventListener('change', () => { add(input.files); input.value = ''; });
    ['dragenter', 'dragover'].forEach(n => zone.addEventListener(n, e => { e.preventDefault(); zone.classList.add('border-sky-400', 'bg-sky-50'); }));
    ['dragleave', 'drop'].forEach(n => zone.addEventListener(n, e => { e.preventDefault(); zone.classList.remove('border-sky-400', 'bg-sky-50'); }));
    zone.addEventListener('drop', e => add(e.dataTransfer.files));
    $('doc-list').addEventListener('click', e => {
      const del = e.target.closest('[data-del]'), can = e.target.closest('[data-cancel]');
      if (del) { removed.push(Number(del.dataset.del)); render(); }
      if (can) { pending.splice(Number(can.dataset.cancel), 1); render(); }
    });
    render();
  });

  return { reset, load, commit, renderHistory };
})();
