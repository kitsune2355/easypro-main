<?php
// หน้าพิมพ์รายงานแจ้งซ่อม — เปิดจากปุ่ม "พิมพ์รายงาน" ในหน้ารายการแจ้งซ่อม (maintenance_info.php)
//  ใช้ตัวกรอง/คำค้น/การเรียงเดียวกับตาราง: GET search, filterModel, sortModel ส่งต่อให้ handle_repair_requests.php?action=get_all
//  หน่วยงานมาจาก session เท่านั้น
@session_start();
include "config_ctrl/checksession.php";
include_once "config_ctrl/connect.php";
require_once __DIR__ . '/config_ctrl/agency_logo.php';

$ag_id = (int)($sess_user_agency_es ?? 0);
$agency = ['name' => '', 'code' => ''];
if ($ag_id > 0) {
    $agRes = mysqli_query($connect, "SELECT ag_contract, ag_job FROM tb_agency WHERE ag_id = $ag_id LIMIT 1");
    if ($agRes && ($agRow = mysqli_fetch_assoc($agRes))) $agency = ['name' => (string)$agRow['ag_contract'], 'code' => (string)$agRow['ag_job']];
}
$query = [
    'search' => (string)($_GET['search'] ?? ''),
    'filterModel' => (string)($_GET['filterModel'] ?? '{}'),
    'sortModel' => (string)($_GET['sortModel'] ?? ''),
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>รายงานแจ้งซ่อม - <?php echo htmlspecialchars($agency['name'], ENT_QUOTES, 'UTF-8'); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  :root { --brand: #006b9f; --line: #d5dce4; --muted: #64748b; --ink: #1e293b; --soft: #f1f6f9; }
  * { box-sizing: border-box; }
  body { margin: 0; background: #e2e8f0; color: var(--ink); font-family: 'Sarabun', Tahoma, sans-serif; font-size: 13px; line-height: 1.45; }
  button, select { font: inherit; }

  /* แถบเครื่องมือ (ไม่พิมพ์) */
  .bar { position: sticky; top: 0; z-index: 10; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 10px 16px; background: #fff; border-bottom: 1px solid var(--line); }
  .bar .grow { flex: 1 1 auto; min-width: 140px; color: var(--muted); }
  .bar label { display: inline-flex; align-items: center; gap: 6px; }
  .bar select { height: 36px; padding: 0 8px; border: 1px solid var(--line); border-radius: 8px; background: #fff; }
  .btn { margin-left: auto; height: 36px; padding: 0 16px; border: 0; border-radius: 8px; background: var(--brand); color: #fff; font-weight: 600; cursor: pointer; }
  .btn:disabled { background: #94a3b8; cursor: wait; }

  /* แผ่นกระดาษบนจอ */
  .sheet { width: 210mm; max-width: 100%; margin: 16px auto; padding: 10mm; background: #fff; box-shadow: 0 1px 6px rgba(15, 23, 42, .15); }

  .rp-head { display: flex; align-items: center; gap: 12px; padding-bottom: 8px; border-bottom: 2px solid var(--brand); }
  .rp-head .logo { height: 40px; width: auto; max-width: 110px; object-fit: contain; }
  .rp-head .logo-ag { height: 40px; width: auto; max-width: 130px; object-fit: contain; }
  .rp-head h1 { margin: 0; font-size: 19px; color: var(--brand); }
  .rp-head .sub { color: var(--muted); font-size: 12px; }
  .rp-head .txt { flex: 1; min-width: 0; }

  /* หน้าแรก: รายงานสรุปผล */
  .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin: 12px 0; }
  .stat { padding: 8px 10px; background: var(--soft); border-left: 3px solid var(--brand); border-radius: 4px; }
  .stat b { display: block; font-size: 20px; line-height: 1.2; }
  .stat span { color: var(--muted); font-size: 11.5px; }
  .stat em { color: var(--muted); font-size: 11.5px; font-style: normal; font-weight: 400; }
  .sum-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px 12px; align-items: start; }
  .sum { break-inside: avoid; page-break-inside: avoid; }
  .sum h2 { margin: 0 0 3px; padding-bottom: 2px; font-size: 14px; color: var(--brand); border-bottom: 1px solid var(--brand); }
  .sum table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 12.5px; }
  .sum td { padding: 3px; border-bottom: 1px solid #edf1f5; vertical-align: middle; overflow-wrap: anywhere; }
  .sum .n { width: 34px; text-align: right; font-weight: 600; }
  .sum .p { width: 34px; text-align: right; color: var(--muted); }
  .sum .b { width: 26%; }
  .sum-bar { height: 8px; border-radius: 2px; background: #e2e8f0; overflow: hidden; }
  .sum-bar i { display: block; height: 100%; background: var(--brand); }
  .sum-note { margin-top: 2px; color: var(--muted); font-size: 10.5px; }
  .sum-empty { padding: 4px 3px; color: var(--muted); font-size: 11.5px; }
  #list.has-jobs { break-before: page; page-break-before: always; }

  .job { margin-bottom: 10px; border: 1px solid var(--line); border-radius: 6px; overflow: hidden; break-inside: avoid; page-break-inside: avoid; }
  .job-h { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 5px 10px; background: var(--brand); color: #fff; }
  .job-h .no { font-size: 14px; font-weight: 700; }
  .job-h .right { margin-left: auto; font-size: 12px; }
  .chip { padding: 0 8px; border-radius: 99px; background: #fff; font-size: 11.5px; font-weight: 600; }
  .st-pending { color: #b45309; } .st-inprogress { color: #1d4ed8; } .st-completed, .st-feedback { color: #047857; } .st-cancel { color: #475569; }
  .urgent { color: #fecaca; font-weight: 700; }

  .job-b { padding: 7px 10px 9px; }
  .grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 3px 12px; }
  .f { min-width: 0; overflow-wrap: anywhere; }
  .f small { display: block; color: var(--muted); font-size: 10.5px; line-height: 1.2; }
  .f.wide { grid-column: 1 / -1; }
  .text { margin-top: 5px; white-space: pre-line; overflow-wrap: anywhere; }
  .text small { display: block; color: var(--muted); font-size: 10.5px; line-height: 1.2; }

  .parts { width: 100%; margin-top: 6px; border-collapse: collapse; font-size: 11.5px; }
  .parts th, .parts td { padding: 2px 6px; border: 1px solid var(--line); text-align: left; overflow-wrap: anywhere; }
  .parts th { background: var(--soft); font-weight: 600; }
  .parts .num { text-align: right; white-space: nowrap; }

  .side { margin-top: 7px; min-width: 0; }
  .side-t { margin-bottom: 3px; font-size: 11.5px; font-weight: 700; }
  .side-t.before { color: #b45309; } .side-t.after { color: #047857; }
  /* ขนาดรูป: --photo-w ตั้งจาก JS ตามตัวเลือก เล็ก / กลาง / ใหญ่ · กรอบรูป 4:3 รูปคงสัดส่วนเดิม */
  .photos { display: grid; gap: 5px; grid-template-columns: repeat(auto-fill, minmax(min(var(--photo-w, 60mm), 100%), var(--photo-w, 60mm))); }
  .ph { position: relative; width: 100%; aspect-ratio: 4 / 3; display: flex; align-items: center; justify-content: center; border: 1px solid var(--line); border-radius: 4px; background: #f8fafc; overflow: hidden; break-inside: avoid; page-break-inside: avoid; }
  .ph img { position: absolute; top: 0; left: 0; display: block; width: 100%; height: 100%; object-fit: contain; }
  .ph.bad { color: #dc2626; font-size: 11px; }
  .photo-none .side { display: none; }
  .note { margin-top: 5px; color: var(--muted); font-size: 11px; }

  /* แนวนอน: ข้อมูล 4 คอลัมน์ · รูปก่อน/หลังซ่อมอยู่ข้างกัน */
  .ori-l .grid { grid-template-columns: repeat(4, 1fr); }
  .ori-l .stats { grid-template-columns: repeat(6, 1fr); }
  .ori-l .sum-grid { grid-template-columns: repeat(3, 1fr); }
  .ori-l .sides { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; align-items: start; }
  @media screen and (min-width: 641px) { .ori-l .sheet { width: 297mm; } }

  /* รูปแบบตาราง: งานละ 1 แถว รูปก่อน/หลังซ่อมอยู่ข้างกัน · ความกว้างคอลัมน์ตามแนวกระดาษและขนาดรูป */
  .rt-wrap { width: 100%; }
  .rt { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 11.5px; line-height: 1.35; }
  .photo-none .rt { --c1: 14%; --c2: 22%; --c3: 46%; --c4: 18%; }
  .rt col.c1 { width: var(--c1); } .rt col.c2 { width: var(--c2); } .rt col.c3 { width: var(--c3); }
  .rt col.c4 { width: var(--c4); } .rt col.c5 { width: var(--c5); } .rt col.c6 { width: var(--c6); }
  .photo-none .rt .c5, .photo-none .rt .c6 { display: none; }
  .rt thead { display: table-header-group; }
  .rt th { padding: 5px 6px; background: var(--brand); color: #fff; border: 1px solid var(--brand); font-weight: 600; text-align: left; }
  .rt td { padding: 5px 6px; border: 1px solid var(--line); vertical-align: top; overflow-wrap: anywhere; }
  .rt tr { break-inside: avoid; page-break-inside: avoid; }
  .rt .no { display: block; font-size: 12.5px; font-weight: 700; color: var(--brand); }
  .rt .chip { display: inline-block; margin: 2px 0; padding: 0 6px; border: 1px solid currentColor; border-radius: 6px; line-height: 1.35; }
  .rt .it { margin-top: 3px; white-space: pre-line; }
  .rt .it:first-child { margin-top: 0; }
  .rt .it small { display: block; color: var(--muted); font-size: 10px; line-height: 1.2; }
  .rt .crit { color: #dc2626; font-weight: 700; }
  .rt .ph-col { padding: 3px; }
  .rt .ph.rtph { margin-bottom: 3px; }
  .rt .rtph:last-child { margin-bottom: 0; }
  .rt .no-ph { padding: 8px 0; color: var(--muted); font-size: 11px; text-align: center; }

  .msg { padding: 40px 10px; text-align: center; color: var(--muted); }
  .msg.err { color: #dc2626; }

  @media (max-width: 640px) {
    .sheet { margin: 0; padding: 12px; box-shadow: none; }
    .rt-wrap { overflow-x: auto; }
    .rt { min-width: 760px; }
    .stats, .ori-l .stats { grid-template-columns: repeat(2, 1fr); }
    .grid, .ori-l .grid { grid-template-columns: 1fr 1fr; }
    .sum-grid, .ori-l .sum-grid, .ori-l .sides { grid-template-columns: 1fr; }
  }
  @media print {
    body { background: #fff; }
    .bar { display: none; }
    .sheet { width: auto; margin: 0; padding: 0; box-shadow: none; }
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
<style id="pageStyle">@page { size: A4 landscape; margin: 10mm; @bottom-right { content: "หน้า " counter(page) " / " counter(pages); font-family: Sarabun, Tahoma, sans-serif; font-size: 10px; color: #64748b; } }</style>
</head>
<body class="photo-on ori-l">
  <div class="bar">
    <label>รูปแบบ
      <select id="reportFormat" aria-label="รูปแบบรายละเอียดงาน">
        <option value="table" selected>ตาราง</option>
        <option value="card">กล่อง</option>
      </select>
    </label>
    <label>แนวกระดาษ
      <select id="orientation" aria-label="แนวกระดาษ">
        <option value="l" selected>แนวนอน</option>
        <option value="p">แนวตั้ง</option>
      </select>
    </label>
    <label>รูปประกอบ
      <select id="photoSize" aria-label="ขนาดรูปประกอบ">
        <option value="s">เล็ก</option>
        <option value="m" selected>กลาง</option>
        <option value="l">ใหญ่</option>
        <option value="none">ไม่แสดงรูป</option>
      </select>
    </label>
    <span id="barInfo" class="grow"></span>
    <button type="button" id="btnPrint" class="btn" disabled>กำลังเตรียม...</button>
  </div>

  <div class="sheet">
    <div class="rp-head">
      <img src="../es/Logo_โปร.png" class="logo" alt="" onerror="this.style.display='none'">
      <?php echo agl_img($connect, $ag_id, 'class="logo-ag"'); ?>
      <div class="txt">
        <h1>รายงานสรุปผลงานแจ้งซ่อม</h1>
        <div class="sub" id="rpSub"><?php echo htmlspecialchars($agency['name'], ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
    </div>
    <div id="summary"></div>
    <div id="list"><div class="msg">กำลังโหลดข้อมูล...</div></div>
  </div>

<script>
(function () {
  const AG_ID = <?php echo $ag_id; ?>;
  const AGENCY = <?php echo json_encode($agency, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?>;
  const QUERY = <?php echo json_encode($query, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?>;
  // ค้างมาแล้วกี่วัน (นับจากวันที่แจ้งถึงวันนี้)
  const ageDays = (r) => { const m = ymd(r.report_date); return m ? Math.max(0, Math.floor((Date.now() - new Date(+m[1], +m[2] - 1, +m[3]).getTime()) / 86400000)) : null; };
  const STATUS = { pending: 'รอดำเนินการ', inprogress: 'กำลังดำเนินการ', completed: 'เสร็จสิ้น', feedback: 'ประเมินแล้ว', cancel: 'ยกเลิก', canceled: 'ยกเลิก', cancelled: 'ยกเลิก' };
  const URGENCY = { low: 'ต่ำ', medium: 'ปานกลาง', high: 'สูง', critical: 'เร่งด่วนมาก' };
  const MONTHS = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
  const $ = (id) => document.getElementById(id);
  const esc = (v) => String(v == null ? '' : v).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const num = (v, d) => Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: d || 0, maximumFractionDigits: d || 0 });

  const ymd = (s) => { const m = String(s || '').match(/^(\d{4})-(\d{2})-(\d{2})/); return m && m[1] !== '0000' && m[2] !== '00' ? m : null; };
  const thDate = (s) => { const m = ymd(s); return m ? `${+m[3]} ${MONTHS[+m[2]]} ${+m[1] + 543}` : ''; };
  const hhmm = (s) => { const m = String(s || '').match(/^(\d{2}):(\d{2})/); return m ? `${m[1]}:${m[2]} น.` : ''; };
  const when = (d, t) => { const a = thDate(d); return a ? (hhmm(t) ? `${a} ${hhmm(t)}` : a) : '-'; };
  const stamp = (d, t) => { const m = ymd(d), h = String(t || '').match(/^(\d{2}):(\d{2})/); return m && h ? Date.UTC(+m[1], +m[2] - 1, +m[3], +h[1], +h[2]) : null; };

  // ชั่วโมงตั้งแต่แจ้งจนซ่อมเสร็จ (รวมช่วงพักงาน) · เวลาไม่ครบ = null
  const hoursToFinish = (r) => {
    const a = stamp(r.report_date, r.report_time), b = stamp(r.completed_date, r.completed_time);
    return a === null || b === null || b < a ? null : (b - a) / 3600000;
  };
  const durationText = (h) => {
    if (h === null) return '-';
    if (h < 24) return `${num(h, 1)} ชม.`;
    const day = Math.floor(h / 24), rest = Math.round(h - day * 24);
    return rest ? `${day} วัน ${rest} ชม.` : `${day} วัน`;
  };
  const partsTotal = (r) => (r.parts || []).reduce((s, p) => s + (Number(p.rpd_sum_money) || 0), 0);
  const isDone = (r) => r.status === 'completed' || r.status === 'feedback';
  const isCanceled = (r) => ['cancel', 'canceled', 'cancelled'].includes(r.status);

  const field = (label, value, cls) => `<div class="f ${cls || ''}"><small>${label}</small>${esc(value || '-')}</div>`;
  const photoBlock = (title, cls, urls) => !urls.length ? '' : `
    <div class="side"><div class="side-t ${cls}">${title} (${urls.length} รูป)</div>
      <div class="photos">${urls.map((u) => `<div class="ph"><img src="${esc(u)}" alt="${title}"></div>`).join('')}</div>
    </div>`;

  function holdText(r) {
    if (!r.hold || r.status !== 'inprogress') return '';
    return [r.hold.label || '', thDate(r.hold.since) ? 'ตั้งแต่ ' + thDate(r.hold.since) : '', thDate(r.hold.eta) ? 'กำหนดกลับมาทำ ' + thDate(r.hold.eta) : '',
      r.hold.parts ? `อะไหล่มาแล้ว ${r.hold.arrived}/${r.hold.parts} รายการ` : ''].filter(Boolean).join(' · ');
  }

  function jobHtml(r) {
    const stCls = ['cancel', 'canceled', 'cancelled'].includes(r.status) ? 'st-cancel' : 'st-' + esc(r.status);
    const urgency = URGENCY[r.urgency] || 'ไม่ระบุ';
    const place = [r.building_name, r.floor_name, r.room_name].filter(Boolean).join(' / ');
    const tech = String(r.responsible_names || r.tech_name || r.completed_by || '').replace(/\n/g, ', ');
    const equip = [r.asset_code, r.asset_name].filter(Boolean).join(' ');
    const hold = holdText(r) ? 'พักงาน: ' + holdText(r) : '';
    const age = r.status === 'inprogress' || r.status === 'pending' ? ageDays(r) : null;
    const parts = r.parts || [];
    const partsHtml = !parts.length ? '' : `<table class="parts"><thead><tr><th>อะไหล่</th><th class="num">จำนวน</th><th class="num">รวม (บาท)</th></tr></thead><tbody>
      ${parts.map((p) => `<tr><td>${esc([p.pd_gen_code, p.rpd_details_head].filter(Boolean).join(' '))}</td><td class="num">${esc(p.rpd_qty)} ${esc(p.pd_unit)}</td><td class="num">${num(p.rpd_sum_money, 2)}</td></tr>`).join('')}
      </tbody></table>`;
    return `<section class="job">
      <div class="job-h"><span class="no">${esc(r.rp_format || '-')}</span><span class="chip ${stCls}">${esc(STATUS[r.status] || r.status || '')}</span>
        <span class="right ${r.urgency === 'critical' ? 'urgent' : ''}">ความเร่งด่วน: ${esc(urgency)}</span></div>
      <div class="job-b">
        <div class="grid">
          ${field('แจ้งเมื่อ', when(r.report_date, r.report_time))}
          ${field('เริ่มซ่อม', when(r.process_date, r.process_time))}
          ${field('ซ่อมเสร็จ', when(r.completed_date, r.completed_time))}
          ${field('สถานที่', place)}
          ${field('ประเภทงาน', [r.service_type_name, r.job_type_name].filter(Boolean).join(' / '))}
          ${field('แจ้งถึงเสร็จ', durationText(hoursToFinish(r)))}
          ${field('ผู้แจ้ง', [r.name, r.phone].filter(Boolean).join(' โทร '))}
          ${field('ช่างผู้ดำเนินงาน', tech)}
          ${age !== null ? field('ค้างมาแล้ว', num(age) + ' วัน') : ''}
          ${field('อุปกรณ์', equip)}
        </div>
        <div class="text"><small>ปัญหา</small>${esc(r.problem_detail || '-')}</div>
        ${r.completed_solution ? `<div class="text"><small>การแก้ไข</small>${esc(r.completed_solution)}</div>` : ''}
        ${partsHtml}
        ${hold ? `<div class="note">${esc(hold)}</div>` : ''}
        <div class="sides">
          ${photoBlock('รูปก่อนซ่อม', 'before', (r.images || []).filter(Boolean))}
          ${photoBlock('รูปหลังซ่อม', 'after', (r.completed_images || []).filter(Boolean))}
        </div>
        ${r.video_url ? '<div class="note">งานนี้มีวิดีโอประกอบ ดูได้ในระบบ</div>' : ''}
      </div>
    </section>`;
  }

  const item = (label, value) => value ? `<div class="it"><small>${label}</small>${esc(value)}</div>` : '';
  const photoCell = (urls, title) => `<td class="ph-col ${title === 'รูปก่อนซ่อม' ? 'c5' : 'c6'}">${urls.length
    ? urls.map((u) => `<div class="ph rtph"><img src="${esc(u)}" alt="${title}"></div>`).join('')
    : '<div class="no-ph">ไม่มีรูป</div>'}</td>`;

  function tableRow(r) {
    const stCls = ['cancel', 'canceled', 'cancelled'].includes(r.status) ? 'st-cancel' : 'st-' + esc(r.status);
    const place = [r.building_name, r.floor_name, r.room_name].filter(Boolean).join(' / ');
    const tech = String(r.responsible_names || r.tech_name || r.completed_by || '').replace(/\n/g, '\n');
    const hold = holdText(r);
    const parts = (r.parts || []).map((p) => `${[p.pd_gen_code, p.rpd_details_head].filter(Boolean).join(' ')} ${p.rpd_qty || ''} ${p.pd_unit || ''}`.trim()).join('\n');
    const h = hoursToFinish(r), cost = partsTotal(r);
    return `<tr>
      <td class="c1"><span class="no">${esc(r.rp_format || '-')}</span><span class="chip ${stCls}">${esc(STATUS[r.status] || r.status || '')}</span>
        <div class="it ${r.urgency === 'critical' ? 'crit' : ''}"><small>ความเร่งด่วน</small>${esc(URGENCY[r.urgency] || 'ไม่ระบุ')}</div>
        ${item('แจ้งเมื่อ', when(r.report_date, r.report_time))}
        ${thDate(r.process_date) ? item('เริ่มซ่อม', when(r.process_date, r.process_time)) : ''}
        ${thDate(r.completed_date) ? item('ซ่อมเสร็จ', when(r.completed_date, r.completed_time)) : ''}</td>
      <td class="c2">${item('สถานที่', place || '-')}${item('ประเภทงาน', [r.service_type_name, r.job_type_name].filter(Boolean).join(' / '))}${item('ผู้แจ้ง', [r.name, r.phone].filter(Boolean).join(' โทร '))}${item('อุปกรณ์', [r.asset_code, r.asset_name].filter(Boolean).join(' '))}</td>
      <td class="c3">${item('ปัญหา', r.problem_detail || '-')}${item('การแก้ไข', r.completed_solution)}${item('อะไหล่', parts)}${item('พักงาน', hold)}${r.video_url ? '<div class="it note">มีวิดีโอประกอบ ดูได้ในระบบ</div>' : ''}</td>
      <td class="c4">${item('ช่าง', tech || '-')}${(r.status === 'inprogress' || r.status === 'pending') && ageDays(r) !== null ? item('ค้างมาแล้ว', num(ageDays(r)) + ' วัน') : ''}${h !== null ? item('แจ้งถึงเสร็จ', durationText(h)) : ''}${cost ? item('ค่าอะไหล่ (บาท)', num(cost, 2)) : ''}</td>
      ${photoCell((r.images || []).filter(Boolean), 'รูปก่อนซ่อม')}
      ${photoCell((r.completed_images || []).filter(Boolean), 'รูปหลังซ่อม')}
    </tr>`;
  }
  const tableHtml = (rows) => `<div class="rt-wrap"><table class="rt">
    <colgroup><col class="c1"><col class="c2"><col class="c3"><col class="c4"><col class="c5"><col class="c6"></colgroup>
    <thead><tr><th class="c1">เลขที่ / เวลา</th><th class="c2">สถานที่ / ผู้แจ้ง</th><th class="c3">ปัญหา / การแก้ไข</th><th class="c4">ผู้ดำเนินงาน</th><th class="c5">รูปก่อนซ่อม</th><th class="c6">รูปหลังซ่อม</th></tr></thead>
    <tbody>${rows.map(tableRow).join('')}</tbody></table></div>`;

  // นับตามกลุ่ม เรียงมากไปน้อย · เกิน 8 กลุ่มรวมเป็น "อื่น ๆ" ให้หน้าสรุปพอดี 1 หน้า
  const MAX_GROUP = 8;
  function groupRows(values) {
    const m = new Map();
    values.forEach((v) => { const k = String(v || '').trim() || 'ไม่ระบุ'; m.set(k, (m.get(k) || 0) + 1); });
    const list = [...m.entries()].sort((x, y) => y[1] - x[1]);
    if (list.length <= MAX_GROUP) return list;
    const rest = list.slice(MAX_GROUP - 1);
    return list.slice(0, MAX_GROUP - 1).concat([[`อื่น ๆ (${num(rest.length)} กลุ่ม)`, rest.reduce((t, x) => t + x[1], 0)]]);
  }
  function sumTable(title, list, total, note) {
    const max = Math.max(1, ...list.map((x) => x[1]));
    const body = list.length ? `<table><tbody>${list.map((x) => `<tr><td>${esc(x[0])}</td><td class="b"><div class="sum-bar"><i style="width:${Math.round(x[1] * 100 / max)}%"></i></div></td><td class="n">${num(x[1])}</td><td class="p">${total ? Math.round(x[1] * 100 / total) + '%' : ''}</td></tr>`).join('')}</tbody></table>` : '<div class="sum-empty">ไม่มีข้อมูล</div>';
    return `<section class="sum"><h2>${title}</h2>${body}${note ? `<div class="sum-note">${note}</div>` : ''}</section>`;
  }

  function renderSummary(rows) {
    const total = rows.length, done = rows.filter(isDone).length, canceled = rows.filter(isCanceled).length;
    const pct = (n) => total ? ` <em>(${Math.round(n * 100 / total)}%)</em>` : '';
    const hours = rows.map(hoursToFinish).filter((h) => h !== null);
    const avg = hours.length ? hours.reduce((a, b) => a + b, 0) / hours.length : null;
    const stats = [[num(total), 'งานทั้งหมด'], [num(done) + pct(done), 'เสร็จสิ้น'], [num(total - done - canceled) + pct(total - done - canceled), 'ยังไม่เสร็จ'],
      [num(canceled) + pct(canceled), 'ยกเลิก'], [esc(durationText(avg)), `เวลาเฉลี่ย แจ้งถึงเสร็จ (${num(hours.length)} งาน)`], [num(rows.reduce((t, r) => t + partsTotal(r), 0), 2), 'ค่าอะไหล่รวม (บาท)']];
    const techs = rows.flatMap((r) => String(r.responsible_names || r.tech_name || '').split('\n').map((x) => x.trim()).filter(Boolean));
    $('summary').innerHTML = `<div class="stats">${stats.map((i) => `<div class="stat"><b>${i[0]}</b><span>${i[1]}</span></div>`).join('')}</div>
      <div class="sum-grid">
        ${sumTable('สถานะ', groupRows(rows.map((r) => STATUS[r.status] || r.status)), total)}
        ${sumTable('ความเร่งด่วน', groupRows(rows.map((r) => URGENCY[r.urgency] || 'ไม่ระบุ')), total)}
        ${sumTable('ชนิดบริการ', groupRows(rows.map((r) => r.service_type_name)), total)}
        ${sumTable('ประเภทงาน', groupRows(rows.map((r) => r.job_type_name)), total)}
        ${sumTable('อาคาร', groupRows(rows.map((r) => r.building_name)), total)}
        ${sumTable('ช่างผู้ดำเนินงาน', groupRows(techs), total, 'งานที่มีช่างหลายคนนับให้ทุกคน')}
      </div>`;
  }

  function periodText(rows) {
    const ds = rows.map((r) => String(r.report_date || '').slice(0, 10)).filter((d) => ymd(d)).sort();
    if (!ds.length) return '';
    return ds[0] === ds[ds.length - 1] ? thDate(ds[0]) : `${thDate(ds[0])} – ${thDate(ds[ds.length - 1])}`;
  }

  // รอรูปโหลดครบก่อนเปิดปุ่มพิมพ์ — รูปที่โหลดไม่ได้แสดงข้อความแทน
  let waitRun = 0;
  function waitImages() {
    const run = ++waitRun;   // สลับรูปแบบระหว่างโหลด: รอบเก่าไม่ต้องอัปเดตปุ่ม
    const imgs = [...document.querySelectorAll('.ph img')];
    let left = imgs.length, failed = 0;
    const btn = $('btnPrint');
    btn.disabled = true; btn.textContent = 'กำลังเตรียม...';
    const update = () => {
      if (run !== waitRun) return;
      $('barInfo').textContent = left ? `กำลังโหลดรูป เหลือ ${num(left)} / ${num(imgs.length)} รูป` : (failed ? `${num(rowsCount)} รายการ · รูปโหลดไม่ได้ ${num(failed)} รูป` : `${num(rowsCount)} รายการ`);
      if (!left) { const b = $('btnPrint'); b.disabled = false; b.textContent = 'พิมพ์รายงาน'; }
    };
    imgs.forEach((img) => {
      const finish = (bad) => {
        if (bad) { failed++; const box = img.parentElement; box.classList.add('bad'); box.textContent = 'โหลดรูปไม่ได้'; }
        left--; update();
      };
      if (img.complete && img.naturalWidth) finish(false);
      else { img.addEventListener('load', () => finish(false), { once: true }); img.addEventListener('error', () => finish(true), { once: true }); }
    });
    update();
  }

  let rowsCount = 0, allRows = [];
  function renderList() {
    $('list').classList.add('has-jobs');
    $('list').innerHTML = $('reportFormat').value === 'table' ? tableHtml(allRows) : allRows.map(jobHtml).join('');
    waitImages();
  }
  $('btnPrint').addEventListener('click', () => window.print());
  // ความกว้างรูป (มม.) ของ เล็ก / กลาง / ใหญ่ ตามรูปแบบ + แนวกระดาษ
  const PHOTO_SIZES = {
    'table-l': { s: 35, m: 55, l: 75 },
    'table-p': { s: 22, m: 34, l: 46 },
    'card-l': { s: 45, m: 65, l: 130 },
    'card-p': { s: 45, m: 60, l: 90 },
  };
  const PAGE_WIDTH = { l: 277, p: 190 };   // ความกว้างพื้นที่พิมพ์ A4 (มม.) หลังหักขอบกระดาษ
  const TEXT_COLS = [0.2, 0.25, 0.37, 0.18];   // สัดส่วนคอลัมน์ข้อความในตาราง
  const applyLayout = () => {
    const ori = $('orientation').value, size = $('photoSize').value, hidden = size === 'none';
    const cfg = PHOTO_SIZES[$('reportFormat').value + '-' + ori];
    const w = hidden ? cfg.m : cfg[size];
    document.body.className = `photo-${hidden ? 'none' : 'on'} ori-${ori}`;
    // คอลัมน์รูปกว้างเท่ารูป + ขอบช่อง 2 มม. ที่เหลือแบ่งให้คอลัมน์ข้อความ
    const photoPct = (w + 2) * 100 / PAGE_WIDTH[ori], textPct = 100 - photoPct * 2, st = document.body.style;
    st.setProperty('--photo-w', w + 'mm');
    TEXT_COLS.forEach((c, i) => st.setProperty('--c' + (i + 1), (textPct * c).toFixed(2) + '%'));
    st.setProperty('--c5', photoPct.toFixed(2) + '%');
    st.setProperty('--c6', photoPct.toFixed(2) + '%');
    // เลขหน้ามุมขวาล่างของกระดาษ
    $('pageStyle').textContent = `@page { size: A4 ${ori === 'l' ? 'landscape' : 'portrait'}; margin: 10mm; @bottom-right { content: "หน้า " counter(page) " / " counter(pages); font-family: Sarabun, Tahoma, sans-serif; font-size: 10px; color: #64748b; } }`;
  };
  $('photoSize').addEventListener('change', applyLayout);
  $('orientation').addEventListener('change', applyLayout);
  $('reportFormat').addEventListener('change', applyLayout);
  applyLayout();
  $('reportFormat').addEventListener('change', () => { if (allRows.length) renderList(); });

  (async () => {
    try {
      const url = new URL('handle_repair_requests.php', location.href);
      url.searchParams.set('action', 'get_all');
      url.searchParams.set('ag_id', AG_ID);
      url.searchParams.set('startRow', 0);
      url.searchParams.set('endRow', 100000);
      url.searchParams.set('search', QUERY.search);
      url.searchParams.set('filterModel', QUERY.filterModel || '{}');
      if (QUERY.sortModel) url.searchParams.set('sortModel', QUERY.sortModel);
      const res = await (await fetch(url.toString(), { cache: 'no-store' })).json();
      if (!res || !Array.isArray(res.rows)) throw new Error((res && (res.message || res.error)) || 'โหลดข้อมูลไม่สำเร็จ');
      const rows = res.rows;
      rowsCount = rows.length;
      const issued = new Date().toLocaleString('th-TH', { dateStyle: 'medium', timeStyle: 'short' });
      $('rpSub').textContent = [[AGENCY.name, AGENCY.code].filter(Boolean).join(' · '), periodText(rows) ? 'วันที่แจ้ง ' + periodText(rows) : '', 'ออกรายงาน ' + issued].filter(Boolean).join('  |  ');
      if (!rows.length) {
        $('list').innerHTML = '<div class="msg">ไม่มีข้อมูลตามตัวกรองที่เลือก</div>';
        $('barInfo').textContent = '0 รายการ';
        return;
      }
      renderSummary(rows);
      allRows = rows;
      renderList();
    } catch (e) {
      console.error(e);
      $('list').innerHTML = `<div class="msg err">${esc(e.message || 'โหลดข้อมูลไม่สำเร็จ')}</div>`;
      $('btnPrint').textContent = 'พิมพ์ไม่ได้';
    }
  })();
})();
</script>
</body>
</html>
