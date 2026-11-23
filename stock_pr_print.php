<?php
// พิมพ์ใบขออนุมัติจัดซื้อ (PR) — stock_pr_print.php?id= (เปิดจากเมนู "ใบขอซื้อ (PR)" ใน stock.php)
//  รูปแบบเดียวกับใบพิมพ์อื่นของระบบ (รายงานแจ้งซ่อม / รายงาน PM): หัวโลโก้ + เส้นสีหลัก, กล่องข้อมูล, ตาราง, ช่องลงนาม
@session_start();
include "config_ctrl/checksession.php";
include_once "config_ctrl/connect.php";
mysqli_set_charset($connect, 'utf8mb4');
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/config_ctrl/stock_pr.php';
require_once __DIR__ . '/config_ctrl/agency_logo.php';

$ag = (int)($_SESSION['sess_user_agency'] ?? 0);
$id = (int)($_GET['id'] ?? 0);
spr_ensure($connect);
$st = mysqli_prepare($connect, "SELECT * FROM tb_stock_pr WHERE pr_id = ? AND ag_id = ? LIMIT 1");
mysqli_stmt_bind_param($st, 'ii', $id, $ag);
mysqli_stmt_execute($st);
$pr = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);
if (!$pr) { http_response_code(404); echo '<meta charset="utf-8"><p style="font-family:sans-serif;padding:24px">ไม่พบใบขอซื้อ หรือไม่ใช่ของหน่วยงานที่เลือกอยู่</p>'; exit; }

$items = [];
$st = mysqli_prepare($connect, "SELECT line_no, pd_code, pd_name, pd_unit, qty, note FROM tb_stock_pr_item WHERE pr_id = ? ORDER BY line_no");
mysqli_stmt_bind_param($st, 'i', $id);
mysqli_stmt_execute($st);
$rs = mysqli_stmt_get_result($st);
while ($r = mysqli_fetch_assoc($rs)) $items[] = $r;
mysqli_stmt_close($st);

$agName = (string)($_SESSION['sess_agency_name'] ?? '');
$st = mysqli_prepare($connect, "SELECT ag_contract FROM tb_agency WHERE ag_id = ? LIMIT 1");
if ($st) {
    mysqli_stmt_bind_param($st, 'i', $ag);
    mysqli_stmt_execute($st);
    mysqli_stmt_bind_result($st, $agc);
    if (mysqli_stmt_fetch($st) && trim((string)$agc) !== '') $agName = trim((string)$agc);
    mysqli_stmt_close($st);
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function qty_txt($q) { $q = (float)$q; return $q == floor($q) ? number_format($q) : rtrim(rtrim(number_format($q, 2), '0'), '.'); }
$cancelled = (int)$pr['status'] === 0;
$printedAt = spr_thai_date(date('Y-m-d')) . ' ' . date('H:i');
$foot = $pr['pr_no'] . ' · พิมพ์เมื่อ ' . $printedAt;
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo h($pr['pr_no']); ?> ใบขออนุมัติจัดซื้อ</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  :root { --brand: #006b9f; --line: #d5dce4; --muted: #64748b; --ink: #1e293b; --soft: #f1f6f9; }
  @page { size: A4 portrait; margin: 10mm 10mm 12mm;
    @bottom-left { content: "<?php echo str_replace(['\\', '"', '<', '>'], '', $foot); ?>"; font-family: 'Sarabun', sans-serif; font-size: 9px; color: #64748b; }
    @bottom-right { content: "หน้า " counter(page) " / " counter(pages); font-family: 'Sarabun', sans-serif; font-size: 9px; color: #64748b; } }
  * { box-sizing: border-box; }
  body { margin: 0; background: #e2e8f0; color: var(--ink); font-family: 'Sarabun', Tahoma, sans-serif; font-size: 13px; line-height: 1.45; }

  /* แถบเครื่องมือ (ไม่พิมพ์) */
  .bar { position: sticky; top: 0; z-index: 10; display: flex; align-items: center; gap: 10px; padding: 10px 16px; background: #fff; border-bottom: 1px solid var(--line); }
  .bar .grow { flex: 1 1 auto; min-width: 0; color: var(--muted); }
  .bar .grow b { color: var(--ink); }
  .bar button { height: 36px; padding: 0 16px; border: 0; border-radius: 8px; font: inherit; font-weight: 600; cursor: pointer; }
  .btn { background: var(--brand); color: #fff; }
  .btn-ghost { background: #fff; color: var(--muted); border: 1px solid var(--line) !important; }

  /* แผ่นกระดาษบนจอ */
  .sheet { position: relative; width: 210mm; max-width: 100%; min-height: 297mm; margin: 16px auto; padding: 10mm; background: #fff; box-shadow: 0 1px 6px rgba(15, 23, 42, .15); }

  /* หัวเอกสาร */
  .rp-head { display: flex; align-items: center; gap: 12px; padding-bottom: 8px; border-bottom: 2px solid var(--brand); }
  .rp-head .txt { flex: 1; min-width: 0; }
  .rp-head h1 { margin: 0; font-size: 19px; line-height: 1.25; color: var(--brand); }
  .rp-head .sub { color: var(--muted); font-size: 12px; }
  .docno { flex: none; text-align: right; line-height: 1.2; }
  .docno small { display: block; color: var(--muted); font-size: 10.5px; }
  .docno b { font-size: 17px; color: var(--brand); letter-spacing: .2px; }
  .chip-void { display: inline-block; margin-top: 3px; padding: 0 8px; border-radius: 99px; background: #fee2e2; color: #b91c1c; font-size: 11px; font-weight: 700; }

  /* กล่องข้อมูล */
  .info { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin: 12px 0 10px; }
  .info div { padding: 6px 10px; background: var(--soft); border-left: 3px solid var(--brand); border-radius: 4px; min-width: 0; overflow-wrap: anywhere; }
  .info small { display: block; color: var(--muted); font-size: 10.5px; line-height: 1.2; }
  .info b { font-size: 13.5px; }
  .info em { display: block; color: var(--muted); font-size: 11px; font-style: normal; }
  .lead { margin: 0 0 6px; color: var(--muted); }

  /* ตารางรายการ */
  table.items { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 12.5px; }
  table.items th { padding: 5px 6px; background: var(--brand); color: #fff; font-weight: 600; text-align: left; border: 1px solid var(--brand); }
  table.items td { padding: 5px 6px; border: 1px solid var(--line); vertical-align: top; overflow-wrap: anywhere; }
  table.items tbody tr:nth-child(even) td { background: #f8fafc; }
  table.items tr { break-inside: avoid; page-break-inside: avoid; }
  table.items .c { text-align: center; }
  table.items .r { text-align: right; white-space: nowrap; }
  table.items .code { font-size: 11.5px; color: #334155; }
  table.items .qty { font-weight: 700; font-size: 13.5px; }
  table.items tr.total td { background: var(--soft); font-weight: 600; border-color: var(--line); }
  table.items tr.fill td { height: 26px; }
  table.items th, table.items tr.total td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

  .remark { margin-top: 10px; padding: 7px 10px; border: 1px solid var(--line); border-radius: 6px; break-inside: avoid; page-break-inside: avoid; }
  .remark small { display: block; color: var(--muted); font-size: 10.5px; }
  .remark div { white-space: pre-line; overflow-wrap: anywhere; }

  /* ช่องลงนาม */
  .sign { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px; break-inside: avoid; page-break-inside: avoid; }
  .sign > div { padding: 10px 14px 12px; border: 1px solid var(--line); border-radius: 6px; text-align: center; }
  .sign .t { margin-bottom: 26px; font-weight: 700; color: var(--brand); text-align: left; }
  .sign .ln { margin: 0 8px 4px; border-bottom: 1px dotted #475569; }
  .sign .nm { min-height: 19px; }
  .sign .muted { color: var(--muted); font-size: 12px; }
  .sign .dt { margin-top: 8px; color: var(--muted); font-size: 12px; }

  .foot-screen { margin-top: 14px; padding-top: 6px; border-top: 1px solid var(--line); display: flex; justify-content: space-between; color: var(--muted); font-size: 10.5px; }
  .void { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; overflow: hidden; }
  .void span { font-size: 110px; font-weight: 700; color: rgba(220, 38, 38, .13); transform: rotate(-28deg); border: 10px solid rgba(220, 38, 38, .13); padding: 0 30px; border-radius: 18px; }

  @media screen and (max-width: 640px) { .info { grid-template-columns: 1fr 1fr; } .sheet { padding: 5mm; } }
  @media print {
    body { background: #fff; }
    .bar, .foot-screen { display: none; }
    .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
    .info div, table.items tbody tr:nth-child(even) td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .void { position: fixed; }
  }
</style>
</head>
<body>
<div class="bar">
  <div class="grow"><b><?php echo h($pr['pr_no']); ?></b> · ใบขออนุมัติจัดซื้อ<?php echo $cancelled ? ' · <span style="color:#b91c1c">ยกเลิกแล้ว</span>' : ''; ?></div>
  <button type="button" class="btn-ghost" onclick="window.close()">ปิด</button>
  <button type="button" class="btn" onclick="window.print()">พิมพ์</button>
</div>

<div class="sheet">
  <?php if ($cancelled): ?><div class="void"><span>ยกเลิก</span></div><?php endif; ?>

  <div class="rp-head">
    <?php echo agl_logos($connect, $ag, '<img src="Logo_โปร.png" alt="PROACTIVE" style="height:40px;width:auto;max-width:110px;object-fit:contain">', 40, 130); ?>
    <div class="txt">
      <h1>ใบขออนุมัติจัดซื้อ</h1>
      <div class="sub">Purchase Request<?php echo $agName !== '' ? ' · ' . h($agName) : ''; ?></div>
    </div>
    <div class="docno">
      <small>เลขที่เอกสาร</small>
      <b><?php echo h($pr['pr_no']); ?></b>
      <?php if ($cancelled): ?><br><span class="chip-void">ยกเลิก</span><?php endif; ?>
    </div>
  </div>

  <div class="info">
    <div><small>วันที่เอกสาร</small><b><?php echo h(spr_thai_date($pr['pr_date'])); ?></b></div>
    <div><small>จัดส่งภายในวันที่</small><b><?php echo !empty($pr['deliver_date']) ? h(spr_thai_date($pr['deliver_date'])) : '-'; ?></b></div>
    <div><small>ผู้ขอซื้อ/ขอจ้าง</small><b><?php echo h($pr['req_name']); ?></b></div>
    <div><small>จำนวนรายการ</small><b><?php echo count($items); ?> รายการ</b></div>
  </div>

  <p class="lead">โปรดดำเนินการจัดซื้อพัสดุตามรายการและจำนวนต่อไปนี้<?php if (!empty($pr['deliver_date'])): ?> โดยจัดส่งภายในวันที่ <?php echo h(spr_thai_date($pr['deliver_date'])); ?><?php endif; ?></p>

  <table class="items">
    <colgroup><col style="width:9mm"><col style="width:30mm"><col><col style="width:18mm"><col style="width:16mm"><col style="width:34mm"></colgroup>
    <thead><tr><th class="c">ที่</th><th>รหัสสินค้า</th><th>รายการ</th><th class="r">จำนวน</th><th class="c">หน่วย</th><th>หมายเหตุ</th></tr></thead>
    <tbody id="pr-rows">
      <?php foreach ($items as $i => $it): ?>
      <tr>
        <td class="c"><?php echo $i + 1; ?></td>
        <td class="code"><?php echo h($it['pd_code']); ?></td>
        <td><?php echo h($it['pd_name']); ?></td>
        <td class="r qty"><?php echo h(qty_txt($it['qty'])); ?></td>
        <td class="c"><?php echo h($it['pd_unit']); ?></td>
        <td><?php echo h($it['note']); ?></td>
      </tr>
      <?php endforeach; ?>
      <tr class="total" id="pr-total"><td colspan="6">รวม <?php echo count($items); ?> รายการ</td></tr>
    </tbody>
  </table>

  <?php if (trim((string)$pr['remark']) !== ''): ?>
  <div class="remark" id="pr-remark"><small>หมายเหตุ</small><div><?php echo h($pr['remark']); ?></div></div>
  <?php endif; ?>

  <div class="sign" id="pr-sign">
    <div>
      <div class="t">ผู้ขอซื้อ/ขอจ้าง</div>
      <div class="ln"></div>
      <div class="nm">( <?php echo h($pr['req_name']); ?> )</div>
      <div class="dt">วันที่ ......../......../........</div>
    </div>
    <div>
      <div class="t">ผู้อนุมัติ</div>
      <div class="ln"></div>
      <div class="nm">( ................................................ )</div>
      <div class="dt">วันที่ ......../......../........</div>
    </div>
  </div>

  <div class="foot-screen"><span><?php echo h($foot); ?></span><span>EasyPro</span></div>
</div>
<script>
// เติมแถวว่างในตารางให้เนื้อหาเต็มหน้า A4 (ช่องลงนามอยู่ท้ายกระดาษ) — รายการเกิน 1 หน้า => เติมให้เต็มหน้าสุดท้าย
//  จำลองการแบ่งหน้าตอนพิมพ์: หัวตารางพิมพ์ซ้ำทุกหน้า · แถว / หมายเหตุ / ช่องลงนาม ไม่ถูกตัดกลางหน้า
//  ความกว้างเนื้อหาบนจอ = ตอนพิมพ์ (210mm − ขอบ 10mm×2) · ความสูงต่อหน้า = 297mm − ขอบบน 10mm − ขอบล่าง 12mm
(function () {
  var tbody = document.getElementById('pr-rows');
  var total = document.getElementById('pr-total');
  var sheet = document.querySelector('.sheet');
  var table = tbody && tbody.closest('table');
  if (!tbody || !total || !sheet || !table) return;
  function mm(v) { var d = document.createElement('div'); d.style.cssText = 'position:absolute;visibility:hidden;height:' + v + 'mm'; document.body.appendChild(d); var h = d.getBoundingClientRect().height; d.remove(); return h; }
  function boxH(el) { if (!el) return 0; var cs = getComputedStyle(el); return el.getBoundingClientRect().height + parseFloat(cs.marginTop) + parseFloat(cs.marginBottom); }
  // ตำแหน่งท้ายเนื้อหา (y) และหน้าสุดท้าย เมื่อมีแถวว่างเพิ่ม extra แถว
  function simulate(P, start, headH, rowHs, extra, fillH, tail) {
    var page = 0, y = start + headH;
    function place(h, repeatHead) {
      if (y + h > P * (page + 1)) { page++; y = P * page + (repeatHead ? headH : 0); }
      y += h;
    }
    rowHs.forEach(function (h) { place(h, true); });
    for (var i = 0; i < extra; i++) place(fillH, true);
    tail.forEach(function (t) { place(t.h, t.inTable); });
    return page;
  }
  function fill() {
    tbody.querySelectorAll('tr.fill').forEach(function (tr) { tr.remove(); });
    var P = mm(275) - mm(2);   // เผื่อ 2mm กันปัดเศษ
    var top = sheet.getBoundingClientRect().top + parseFloat(getComputedStyle(sheet).paddingTop);
    var thead = table.querySelector('thead');
    var start = thead.getBoundingClientRect().top - top;
    var headH = thead.getBoundingClientRect().height;
    var rowHs = [].map.call(tbody.querySelectorAll('tr:not(.total)'), function (tr) { return tr.getBoundingClientRect().height; });
    var tail = [{ h: total.getBoundingClientRect().height, inTable: true }];
    if (document.getElementById('pr-remark')) tail.push({ h: boxH(document.getElementById('pr-remark')), inTable: false });
    tail.push({ h: boxH(document.getElementById('pr-sign')), inTable: false });
    // ความสูงแถวว่าง
    var probe = document.createElement('tr');
    probe.className = 'fill';
    probe.innerHTML = '<td></td><td></td><td></td><td></td><td></td><td></td>';
    tbody.insertBefore(probe, total);
    var fillH = probe.getBoundingClientRect().height;
    probe.remove();
    var lastPage = simulate(P, start, headH, rowHs, 0, fillH, tail);
    var n = 0;
    while (n < 200 && simulate(P, start, headH, rowHs, n + 1, fillH, tail) === lastPage) n++;
    for (var i = 0; i < n; i++) {
      var tr = document.createElement('tr');
      tr.className = 'fill';
      tr.innerHTML = '<td></td><td></td><td></td><td></td><td></td><td></td>';
      tbody.insertBefore(tr, total);
    }
  }
  var fontsReady = document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve();
  var loaded = new Promise(function (ok) { if (document.readyState === 'complete') ok(); else window.addEventListener('load', ok); });   // โลโก้โหลดเสร็จ
  Promise.all([fontsReady, loaded]).then(function () {
    fill();
    // ?autoprint=1 (ปุ่มพิมพ์ในหน้าสต็อก): เปิดหน้าต่างสั่งพิมพ์ทันทีเมื่อจัดหน้าเสร็จ
    if (/[?&]autoprint=1(&|$)/.test(location.search)) setTimeout(function () { window.focus(); window.print(); }, 60);
  });
  window.addEventListener('beforeprint', fill);
})();
</script>
</body>
</html>
