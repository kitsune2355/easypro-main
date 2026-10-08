
<?php
@session_start();
include "config_ctrl/checksession.php";
$ag_id = (int)($sess_user_agency_es ?? 0); // ✅ กันว่างแล้ว JS พัง
?>

<!-- =========================
     REQUIRED LIBRARIES
     ========================= -->
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  // ต้องกำหนดจากระบบจริง
  const AG_ID = '<?php echo isset($sess_user_agency_es) ? $sess_user_agency_es : ""; ?>';
</script>

<style>
  .ag-checkbox-input{
    -webkit-appearance:none;
    appearance:none;
    width:18px;
    height:18px;
    border-radius:9999px;
    border:2px solid #cbd5e1;
    background:#fff;
    display:inline-grid;
    place-content:center;
    cursor:pointer;
    outline:none;
  }
  .ag-checkbox-input::after{
    content:"";
    width:6px;
    height:10px;
    border-right:2px solid #fff;
    border-bottom:2px solid #fff;
    transform:rotate(45deg) scale(0);
    transition:transform 120ms ease;
  }
  .ag-checkbox-input:checked{
    background:#0ea5e9;
    border-color:#0ea5e9;
  }
  .ag-checkbox-input:checked::after{
    transform:rotate(45deg) scale(1);
  }
  .ag-checkbox-input:hover{ border-color:#94a3b8; }
  .ag-checkbox-input:focus{ box-shadow:0 0 0 3px rgba(14,165,233,.25); }
</style>

<!-- =========================
     PARTS / CONSUMABLES UI
     ========================= -->
<div class="bg-white rounded-xl border border-slate-200 p-3">
  <div class="flex items-center justify-between">
    <div class="text-[10px] font-black text-slate-500 flex items-center gap-2">
      <i data-lucide="package-plus" class="w-4 h-4"></i>
      อะไหล่/วัสดุสิ้นเปลือง
    </div>

    <button type="button" onclick="openPartsModal()"
      class="text-[10px] font-black text-sky-700 hover:underline flex items-center gap-1">
      <i data-lucide="plus-circle" class="w-4 h-4"></i>
      เพิ่มรายการ
    </button>
  </div>

 

  <div id="parts_wrap" class="mt-3 space-y-2"></div>

  <div class="mt-3 flex items-center justify-end text-[11px] font-extrabold text-slate-700">
    รวมค่าวัสดุ/อะไหล่:
    <span id="parts_total" class="ml-2 text-emerald-700 text-sm">0.00</span>
    <span class="ml-1 text-[10px] text-slate-400">บาท</span>
  </div>

  <p class="mt-2 text-[10px] text-slate-400">
    * เพิ่มได้หลายรายการ (เลือกจากสต๊อกและกำหนดจำนวนที่ใช้)
  </p>
</div>

<!-- =========================
     PARTS PICKER MODAL
     ========================= -->
<div id="parts_modal" class="fixed inset-0 z-[21000] hidden">
  <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[1px]" data-parts-close></div>

  <div class="relative mx-auto mt-6 w-[96%] max-w-6xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
      <div>
        <div class="text-sm font-extrabold text-slate-900 flex items-center gap-2">
          <i data-lucide="package-search" class="w-4 h-4 text-sky-700"></i>
          อะไหล่/วัสดุสิ้นเปลือง
        </div>
        <div class="text-[11px] text-slate-500 mt-1">เลือกคลัง แล้วค้นหาเพื่อเพิ่มรายการ</div>
      </div>

      <button type="button"
        class="h-9 w-9 rounded-xl border border-slate-200 hover:bg-slate-50 flex items-center justify-center"
        data-parts-close>
        <i data-lucide="x" class="w-4 h-4 text-slate-500"></i>
      </button>
    </div>

    <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
      <div class="grid grid-cols-12 gap-3 items-center">
        <div class="col-span-12 md:col-span-3">
          <label class="block text-[10px] font-black text-slate-500 mb-1">คลังสินค้า</label>
          <select id="parts_wh"
            class="w-full h-10 rounded-xl border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700">
            <option value="">-- เลือกคลัง --</option>
          </select>
        </div>

        <div class="col-span-12 md:col-span-9">
          <label class="block text-[10px] font-black text-slate-500 mb-1">ค้นหา</label>
          <div class="relative">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input id="parts_q" type="text" placeholder="ค้นหารหัส/ชื่อสินค้า..."
              class="w-full h-10 rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-xs font-bold text-slate-700 focus:ring-2 focus:ring-sky-200 outline-none">
          </div>
        </div>
      </div>
    </div>

    <div class="max-h-[55vh] overflow-auto">
      <div class="grid grid-cols-12 px-5 py-2 text-[11px] font-extrabold text-slate-500 border-b border-slate-100 bg-white sticky top-0 z-10">
        <div class="col-span-4">รายการสินค้า</div>
        <div class="col-span-2">คลังสินค้า</div>
        <div class="col-span-3">รายละเอียด</div>
        <div class="col-span-1 text-right">ราคา</div>
        <div class="col-span-2 text-right">สต็อก</div>
      </div>

      <div id="parts_list" class="divide-y divide-slate-100"></div>

      <div id="parts_empty" class="hidden px-5 py-12 text-center text-[12px] text-slate-400 font-semibold">
        ไม่พบรายการ
      </div>
    </div>

    <div class="px-5 py-4 border-t border-slate-100 bg-white flex items-center justify-between">
      <div class="text-[11px] font-bold text-slate-500">
        เลือกแล้ว:
        <span id="parts_selected_count" class="text-sky-700 font-extrabold">0</span> รายการ
      </div>

      <div class="flex items-center gap-2">
        <button type="button"
          class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-50"
          data-parts-close>
          ยกเลิก
        </button>

        <button type="button" id="parts_confirm"
          class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold disabled:opacity-40 disabled:cursor-not-allowed">
          เพิ่มรายการ
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function escapeHtml(str){
  return String(str ?? '').replace(/[&<>"']/g, function(m){
    return {
      '&':'&amp;',
      '<':'&lt;',
      '>':'&gt;',
      '"':'&quot;',
      "'":'&#039;'
    }[m];
  });
}

function safeIcons(){
  if (window.lucide && typeof window.lucide.createIcons === 'function') {
    window.lucide.createIcons();
  }
}

function updatePartsTotal(){
  const wrap = document.getElementById('parts_wrap');
  const totalEl = document.getElementById('parts_total');
  if(!wrap || !totalEl) return;

  let total = 0;
  wrap.querySelectorAll('#parts_wrap > div').forEach(function(row){
    const sum = parseFloat(row.dataset.sum || '0') || 0;
    total += sum;
  });

  totalEl.textContent = total.toLocaleString('th-TH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
}

function addPartRow(item, qty){
  qty = qty || '1';

  const wrap = document.getElementById('parts_wrap');
  if (!wrap || !item) return;

  const row = document.createElement('div');
  row.className = "grid grid-cols-12 gap-2 items-center";
  row.setAttribute('data-actual-spare-row', '1');

  row.dataset.productId   = String(item.pd_id || '');
  row.dataset.stockId     = String(item.ps_id || '');
  row.dataset.whId        = String(item.wh_id || '');
  row.dataset.whName      = String(item.wh_name || item.wh || '');
  row.dataset.model       = String(item.model || '');
  row.dataset.genCode     = String(item.code || '');
  row.dataset.detailsHead = String(item.name || '');
  row.dataset.details     = String(item.detail || '');
  row.dataset.brand       = String(item.brand || '');
  row.dataset.price       = String(item.price || 0);
  row.dataset.unit        = String(item.unit || '');

  // เพิ่มชุดนี้เพื่อให้ collectActualSpares() อ่านได้
  row.dataset.rpdProductId   = String(item.pd_id || '');
  row.dataset.psId           = String(item.ps_id || '');
  row.dataset.pdGenCode      = String(item.code || '');
  row.dataset.rpdDetailsHead = String(item.name || '');
  row.dataset.rpdDetails     = String(item.detail || '');
  row.dataset.rpdBrand       = String(item.brand || '');
  row.dataset.rpdPrice       = String(item.price || 0);
  row.dataset.pdUnit         = String(item.unit || '');

  row.innerHTML = `
    <div class="col-span-7">
      <div class="text-[11px] font-bold text-slate-800">
        ${escapeHtml(item.name || '')}
      </div>

      <div class="text-[10px] text-slate-500">
        <span class="font-semibold">รหัส:</span> ${escapeHtml(item.code || '')}
        ${item.brand ? ` | <span class="font-semibold">ยี่ห้อ:</span> ${escapeHtml(item.brand)}` : ``}
        ${(item.wh_name || item.wh) ? ` | <span class="font-semibold">คลัง:</span> ${escapeHtml(item.wh_name || item.wh || '')}` : ``}
      </div>

      <div class="text-[10px] text-slate-500 truncate">
        ${escapeHtml(item.detail || '')}
      </div>

      ${item.model ? `
      <div class="text-[10px] text-slate-500">
        <span class="font-semibold">รุ่น/ชนิด:</span> ${escapeHtml(item.model || '')}
      </div>` : ``}

      <div class="text-[10px] text-emerald-700 font-semibold">
        ราคา/หน่วย: ${Number(item.price || 0).toLocaleString()} (${escapeHtml(item.unit || '')})
      </div>
    </div>

    <div class="col-span-3">
      <input type="number" min="0" step="1" data-role="qty" data-actual-spare-qty
	  class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-[12px] font-semibold text-right"
	  value="${escapeHtml(qty)}" />
      <div class="mt-1 text-[10px] text-right text-slate-500">
        รวม: <span data-role="sum">0</span>
      </div>
    </div>

    <div class="col-span-2 flex justify-end">
      <button type="button"
        class="h-9 px-2 rounded-xl border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100">
        <i data-lucide="trash-2" class="w-4 h-4"></i>
      </button>
    </div>
  `;

  const qtyInput = row.querySelector('input[data-role="qty"]');
  const sumEl = row.querySelector('[data-role="sum"]');

  function recalcRow() {
    const qtyValue = parseFloat(qtyInput.value || '0') || 0;
    const price = parseFloat(row.dataset.price || '0') || 0;
    const sum = qtyValue * price;

    sumEl.textContent = sum.toLocaleString('th-TH', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });

    row.dataset.qty = String(qtyValue);
    row.dataset.sum = String(sum);

    updatePartsTotal();
  }

  qtyInput.addEventListener('input', recalcRow);

  row.querySelector('button').addEventListener('click', function(){
    row.remove();
    updatePartsTotal();
  });

  wrap.appendChild(row);
  recalcRow();
  safeIcons();
}

(function(){
  const modal   = document.getElementById('parts_modal');
  const listEl  = document.getElementById('parts_list');
  const emptyEl = document.getElementById('parts_empty');
  const whEl    = document.getElementById('parts_wh');
  const qEl     = document.getElementById('parts_q');
  const cntEl   = document.getElementById('parts_selected_count');
  const okBtn   = document.getElementById('parts_confirm');

  if(!modal || !listEl || !emptyEl || !whEl || !qEl || !cntEl || !okBtn) return;

  if(typeof AG_ID === 'undefined'){
    console.error('AG_ID is not defined');
    return;
  }

  const state = {
    items: [],
    selected: new Map()
  };

  function closePartsModal(){
    modal.classList.add('hidden');
  }

	 window.openPartsModal = function(){
	  modal.classList.remove('hidden');
	  state.selected.clear();
	  cntEl.textContent = '0';
	  okBtn.disabled = true;
	
	  loadWhOptions().then(loadItems);
	  safeIcons();
	
	  setTimeout(function(){
		qEl.focus();
	  }, 50);
	};

  modal.querySelectorAll('[data-parts-close]').forEach(function(el){
    el.addEventListener('click', closePartsModal);
  });

  async function loadWhOptions(){
    whEl.innerHTML = '<option value="">-- เลือกคลัง --</option>';

    try{
      const params = new URLSearchParams({
        action: 'get_wh_list',
        ag_id: String(AG_ID)
      });

      const res = await fetch('handle_stock_maintenance_function.php?' + params.toString(), {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
      });

      const j = await res.json();

      if(!res.ok || !j.success){
        console.error('loadWhOptions error:', j);
        return;
      }

      (j.data || []).forEach(function(row){
        const opt = document.createElement('option');
        opt.value = String(row.id);
        opt.textContent = row.wh_name || '';
        whEl.appendChild(opt);
      });

    }catch(err){
      console.error(err);
    }
  }

  function normalizeItem(r){
    return {
      ps_id: Number(r.ps_id || 0),
      pd_id: Number(r.pd_id || 0),
      wh_id: Number(r.wh_id || 0),
      name: String(r.pd_details_head || ''),
      code: String(r.pd_gen_code || ''),
      detail: String(r.pd_details || ''),
      wh: String(r.wh_name || ''),
      wh_name: String(r.wh_name || ''),
      price: Number(r.pd_price || 0),
      stock: Number(r.pd_qty || 0),
      stockMax: Number(r.pd_qty_all || 0),
      unit: String(r.pd_unit || ''),
      brand: String(r.pd_brand || ''),
      model: String(r.pd_model || '')
    };
  }

  async function loadItems(){
  const wh_id = (whEl.value || '').trim();
  const q = (qEl.value || '').trim();

  try{
    const params = new URLSearchParams({
      action: 'get_all',
      ag_id: String(AG_ID)
    });

    if (wh_id) params.set('wh_id', wh_id);
    if (q) params.set('q', q);

    const res = await fetch('handle_stock_maintenance_function.php?' + params.toString(), {
      method: 'GET',
      headers: { 'Accept': 'application/json' }
    });

    const j = await res.json();
    const raw = (j && j.success && Array.isArray(j.data)) ? j.data : [];

    state.items = raw.map(normalizeItem).filter(function(x){
      return x.ps_id > 0;
    });

    render();
  }catch(err){
    console.error(err);
    state.items = [];
    render();
  }
}

  function stockColor(it){
    const max = Number(it.stockMax || 0);
    const stock = Number(it.stock || 0);
    const pct = max > 0 ? (stock / max) : 0;

    if(pct <= 0.2) return 'bg-rose-500';
    if(pct <= 0.5) return 'bg-amber-500';
    return 'bg-emerald-500';
  }

  function render(){
    listEl.innerHTML = '';
    emptyEl.classList.toggle('hidden', state.items.length > 0);

    state.items.forEach(function(it){
      const checked = state.selected.has(it.ps_id);
      const max = Number(it.stockMax || 0);
      const stock = Number(it.stock || 0);
      const pct = (max > 0) ? Math.round((stock / max) * 100) : 0;

      const row = document.createElement('div');
      row.className = 'grid grid-cols-12 px-5 py-3 cursor-pointer items-center ' + (checked ? 'bg-sky-50 ring-1 ring-sky-200' : 'hover:bg-slate-50');

      row.innerHTML = `
        <div class="col-span-4">
          <div class="flex items-start gap-3">
            <input type="checkbox" class="ag-input-field-input ag-checkbox-input mt-1" ${checked ? 'checked' : ''} />
            <div>
              <div class="text-[12px] font-extrabold text-slate-800 leading-snug">${escapeHtml(it.name || '')}</div>
              <div class="mt-1 flex items-center gap-2">
                <span class="text-[10px] font-black text-slate-600 bg-slate-100 px-2 py-0.5 rounded">${escapeHtml(it.code || '')}</span>
                ${checked ? '<span class="text-[10px] font-black text-sky-700 bg-sky-50 border border-sky-200 px-2 py-0.5 rounded">เลือกแล้ว</span>' : ''}
              </div>
            </div>
          </div>
        </div>

        <div class="col-span-2 text-[11px] font-bold text-slate-700">${escapeHtml(it.wh || '')}</div>

        <div class="col-span-3 text-[11px] font-semibold text-slate-600">
          ${escapeHtml(it.detail || '')}
          ${it.model ? '<div class="text-[10px] text-slate-500 mt-0.5">รุ่น: ' + escapeHtml(it.model) + '</div>' : ''}
        </div>

        <div class="col-span-1 text-right text-[12px] font-extrabold text-slate-700">${Number(it.price || 0).toLocaleString()}</div>

        <div class="col-span-2 text-right">
          <div class="text-[11px] font-extrabold ${max > 0 && stock <= (max * 0.2) ? 'text-rose-600' : 'text-slate-700'}">
            ${stock} / ${max} <span class="text-[10px] font-bold text-slate-400">${escapeHtml(it.unit || '')}</span>
          </div>
          <div class="mt-1 h-1.5 w-full bg-slate-200 rounded-full overflow-hidden">
            <div class="h-full ${stockColor(it)}" style="width:${Math.min(100, Math.max(0, pct))}%"></div>
          </div>
        </div>
      `;

      row.addEventListener('click', function(){
        const key = it.ps_id;

        if(state.selected.has(key)) state.selected.delete(key);
        else state.selected.set(key, it);

        cntEl.textContent = String(state.selected.size);
        okBtn.disabled = state.selected.size === 0;
        render();
      });

      listEl.appendChild(row);
    });

    safeIcons();
  }

  whEl.addEventListener('change', loadItems);
  qEl.addEventListener('input', loadItems);

 

  okBtn.addEventListener('click', function(){
    if(state.selected.size === 0) return;

    state.selected.forEach(function(item){
      addPartRow(item, '1');
    });

    closePartsModal();
    state.selected.clear();
    cntEl.textContent = '0';
    okBtn.disabled = true;
  });

  loadWhOptions();
})();

document.addEventListener('DOMContentLoaded', function(){
  safeIcons();
});
</script>