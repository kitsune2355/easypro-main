<?php
//pm_overview.php — แท็บ "ภาพรวม" (โหมดจัดการแผน): ตัวเลขสรุปของหน่วยงาน + ทางลัดไปเครื่องมือจัดการ (ข้อมูลจาก handle_pm_daily.php?action=overview)
?>
<div id="tab-overview" class="tab-content hidden space-y-4">
    <div id="pmo-kpis" class="grid grid-cols-2 lg:grid-cols-4 gap-3"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- เครื่องมือจัดการ -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <h3 class="font-semibold text-slate-800 mb-1">เครื่องมือจัดการแผน PM</h3>
            <p class="text-xs text-slate-500 mb-3">ลำดับการตั้งค่าแนะนำ: สร้างเช็คชีต → สร้างแผน (ทีละรายการ หรือนำเข้าจาก Excel) → ตรวจสอบในตารางแผน PM</p>
            <div id="pmo-tools" class="grid grid-cols-1 sm:grid-cols-2 gap-3"></div>
        </div>

        <!-- เช็คชีตที่มีงานค้างมากที่สุด -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
            <h3 class="font-semibold text-slate-800 mb-1">งานค้างมากที่สุด</h3>
            <p class="text-xs text-slate-500 mb-3">เช็คชีตที่มีงานเลยกำหนดยังไม่ทำ</p>
            <div id="pmo-top" class="space-y-2"></div>
        </div>
    </div>
</div>

<script>
(function () {
    const $ = id => document.getElementById(id);
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const thShort = s => new Date(s + 'T00:00:00').toLocaleDateString('th-TH', { day: 'numeric', month: 'short', year: '2-digit' });
    const fmt = n => Number(n || 0).toLocaleString();

    const TOOLS = [
        { tab: 'schedule',   icon: 'table-2',          title: 'ตารางแผน PM',         desc: 'ดูแผนทั้งหมดแบบรายปี / รายเดือน / รายสัปดาห์ และแก้ความถี่หลายรายการพร้อมกัน' },
        { tab: 'plan',       icon: 'clipboard-list',   title: 'จัดการแผน PM',        desc: 'สร้าง แก้ไข เปิด/ปิด แผน PM ทีละเครื่อง' },
        { tab: 'checksheet', icon: 'file-spreadsheet', title: 'สร้างเช็คชีต',        desc: 'สร้างและแก้ไขจุดตรวจ ไฟล์แนบ และอะไหล่ที่ต้องเตรียม' },
        { tab: 'import',     icon: 'file-up',          title: 'นำเข้าแผนจาก Excel',  desc: 'สร้างเช็คชีตและแผน PM จำนวนมากจากไฟล์ Yearly PM Schedule' },
        { tab: 'postpone',   icon: 'calendar-clock',   title: 'เลื่อนแผน PM',        desc: 'เลื่อนวันกำหนดการที่ยังไม่ได้ทำ พร้อมเก็บประวัติการเลื่อน' },
        { tab: 'holiday',    icon: 'calendar-days',    title: 'จัดการวันหยุด',       desc: 'กำหนดวันหยุดของหน่วยงาน ใช้คำนวณวันในแผน PM' },
    ];
    $('pmo-tools').innerHTML = TOOLS.map(t => `
        <button type="button" data-tab="${t.tab}" class="text-left flex gap-3 p-3 rounded-xl border border-slate-200 hover:border-sky-300 hover:bg-sky-50/50 transition-colors">
            <span class="shrink-0 w-10 h-10 rounded-lg bg-sky-50 text-[#006B9F] flex items-center justify-center"><i data-lucide="${t.icon}" class="w-5 h-5"></i></span>
            <span class="min-w-0"><span class="block font-semibold text-slate-800 text-sm">${t.title}</span>
                <span class="block text-xs text-slate-500 mt-0.5">${t.desc}</span></span>
        </button>`).join('');
    $('pmo-tools').addEventListener('click', e => {
        const b = e.target.closest('[data-tab]');
        if (b) window.switchTab(b.dataset.tab);
    });

    function kpi(label, value, sub, cls, action) {
        return `<${action ? `button type="button" data-go="${action}"` : 'div'} class="text-left bg-white rounded-xl shadow-sm border border-slate-200 p-4 ${action ? 'hover:border-sky-300 transition-colors' : ''}">
            <div class="text-xs text-slate-500">${label}</div>
            <div class="text-2xl font-bold ${cls} mt-1">${value}</div>
            <div class="text-xs text-slate-400 mt-1">${sub}</div>
        </${action ? 'button' : 'div'}>`;
    }

    async function load() {
        $('pmo-kpis').innerHTML = `<div class="col-span-full py-8 text-center text-slate-400 text-sm">กำลังโหลด...</div>`;
        try {
            const res = await fetch('handle_pm_daily.php?action=overview');
            const data = await res.json();
            if (!data.success) throw new Error(data.error || 'โหลดข้อมูลไม่สำเร็จ');
            const d = data.data;
            const monthPct = d.month_total ? Math.round(d.month_done * 100 / d.month_total) : 0;
            $('pmo-kpis').innerHTML =
                kpi('งานวันนี้', `${fmt(d.today_done)} / ${fmt(d.today_total)}`, 'ทำแล้ว / ทั้งหมด · กดเพื่อดูรายการ', 'text-slate-800', 'todo')
                + kpi('งานเลยกำหนด', fmt(d.overdue), d.overdue ? 'กดเพื่อดูรายการ' : 'ไม่มีงานค้าง', d.overdue ? 'text-red-600' : 'text-emerald-600', d.overdue ? 'overdue' : '')
                + kpi('ความคืบหน้าเดือนนี้', monthPct + '%', `${fmt(d.month_done)} จาก ${fmt(d.month_total)} งาน · 7 วันข้างหน้า ${fmt(d.next7)} งาน`, 'text-sky-700', '')
                + kpi('แผนที่ใช้งาน', fmt(d.plans), `${fmt(d.machines)} เครื่อง · ${fmt(d.checksheets)} เช็คชีต`, 'text-slate-800', '');
            $('pmo-top').innerHTML = d.top_overdue.length ? d.top_overdue.map(t => `
                <div class="flex items-center gap-3 p-2.5 rounded-lg bg-slate-50">
                    <div class="min-w-0 flex-1"><div class="text-sm font-medium text-slate-700 truncate" title="${esc(t.name)}">${esc(t.name)}</div>
                        <div class="text-xs text-slate-400">ค้างตั้งแต่ ${thShort(t.oldest)}</div></div>
                    <span class="text-sm font-bold text-red-600">${fmt(t.n)}</span>
                </div>`).join('')
                : `<div class="py-6 text-center text-sm text-emerald-600">ไม่มีงานค้าง</div>`;
        } catch (e) {
            $('pmo-kpis').innerHTML = `<div class="col-span-full py-8 text-center text-red-600 text-sm">${esc(e.message)}</div>`;
        }
        lucide.createIcons({ root: $('tab-overview') });
    }

    // KPI ที่กดได้ => ไปหน้างาน (งานที่ต้องทำ) พร้อมกรองสถานะ
    $('pmo-kpis').addEventListener('click', e => {
        const b = e.target.closest('[data-go]');
        if (b) window.switchTab('daily', { filter: b.dataset.go });
    });

    window.initOverviewTab = load;
})();
</script>
