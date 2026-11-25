<?php
// พิมพ์ใบขออนุมัติจัดซื้อ (PR) — stock_pr_print.php?id= (เปิดจากเมนู "ใบขอซื้อ (PR)" ใน stock.php)
//  รูปแบบเดียวกับใบพิมพ์อื่นของระบบ (รายงานแจ้งซ่อม / รายงาน PM): หัวโลโก้ + เส้นสีหลัก, กล่องข้อมูล, ตาราง, ช่องลงนาม
@session_start();
include "config_ctrl/checksession.php";
include_once "config_ctrl/connect.php";
mysqli_set_charset($connect, 'utf8mb4');
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/config_ctrl/stock_pr.php';
require_once __DIR__ . '/config_ctrl/stock_pr_approver.php';
require_once __DIR__ . '/config_ctrl/stock_perm.php';
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
// ชื่อผู้อนุมัติ: ตามที่เลือกในใบ · ใบเก่าที่ยังไม่มีช่องนี้ (NULL) = ผู้อนุมัติที่ตั้งไว้ ถ้ามีคนเดียว
$approver = trim((string)($pr['approver_name'] ?? ''));
if (($pr['approver_id'] ?? null) === null && !$cancelled) {
    $apList = spra_list($connect, $ag);
    if (count($apList) === 1) $approver = $apList[0]['name'];
}
// ในวงเล็บเป็น ชื่อ-นามสกุล
$approver = spr_full_name($connect, $approver, $pr['approver_id'] ?? '');
$reqName = spr_full_name($connect, $pr['req_name'], $pr['created_by']);
$STATE = ['approved' => ['อนุมัติ', 'st-ok'], 'pending' => ['รออนุมัติ', 'st-wait'], 'cancelled' => ['ยกเลิก', 'st-void']][spr_state($pr)];
// ลงนามบนหน้านี้ (ลายเซ็นเก็บในใบ — handle_stock_pr.php?action=sign)
$me = (string)($_SESSION['sess_user_id_es'] ?? '');
$level = (string)($_SESSION['sess_user_level_es'] ?? '');
$signRights = spr_sign_rights($connect, $ag, $pr, $me, $level);
// ชื่อที่จะลงในช่องผู้อนุมัติ: ใบของผู้อนุมัติคนนี้ = ชื่อในใบ · นอกนั้น = ผู้ที่ล็อกอิน (super_admin / ใบไม่ระบุผู้อนุมัติ)
$apMine = (string)($pr['approver_id'] ?? '') === $me;
$apSignName = $signRights['ap'] ? spr_user_name($connect, $me) : '';
$apLock = spr_has_surname($apSignName);   // บัญชีมีนามสกุลแล้ว = ใช้ชื่อในบัญชี
if (!$apLock && $apMine && $approver !== '') $apSignName = $approver;
function sign_state($slot) {
    global $pr, $signRights, $me, $level;
    $by = (string)($pr[$slot . '_signed_by'] ?? '');
    $at = (string)($pr[$slot . '_signed_at'] ?? '');
    $t = $at !== '' ? strtotime($at) : 0;
    return [
        'img' => $by !== '' ? (string)$pr[$slot . '_sign'] : '',
        'at' => $by !== '' && $t ? date('d/m/', $t) . (date('Y', $t) + 543) : '',
        'can_sign' => $by === '' && $signRights[$slot],
        'can_unsign' => $by !== '' && (int)$pr['status'] === 1 && ($level === 'super_admin' || $by === $me)
            && ($slot === 'ap' || spr_state($pr) !== 'approved'),   // อนุมัติแล้ว ลบลายเซ็นผู้ขอไม่ได้
    ];
}
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
  .bar-acts { display: flex; gap: 10px; }
  .bar .grow { flex: 1 1 auto; min-width: 0; color: var(--muted); }
  .bar .grow b { color: var(--ink); }
  .bar button { height: 36px; padding: 0 16px; border: 0; border-radius: 8px; font: inherit; font-weight: 600; cursor: pointer; }
  .btn { background: var(--brand); color: #fff; }
  .st { display: inline-block; padding: 1px 10px; border-radius: 99px; font-size: 12px; font-weight: 700; }
  .st-ok { background: #d1fae5; color: #047857; }
  .st-wait { background: #fef3c7; color: #b45309; }
  .st-void { background: #fee2e2; color: #b91c1c; }
  .btn-sign { background: #e0f2fe; color: #075985; border: 1px solid #7dd3fc !important; }
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
  .void-note { margin: -2px 0 10px; padding: 6px 10px; border: 1px solid #fecaca; border-radius: 6px; background: #fef2f2; color: #b91c1c; font-size: 12px; overflow-wrap: anywhere; }
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
  .sign .sgb { position: relative; }
  .sign .t { margin-bottom: 4px; font-weight: 700; color: var(--brand); text-align: left; }
  .sign .ln { display: flex; align-items: flex-end; justify-content: center; height: 40px; margin: 0 8px 4px; border-bottom: 1px dotted #475569; }
  .sign .ln img { display: block; max-width: 100%; max-height: 39px; object-fit: contain; }
  .sg-btn { position: absolute; top: 8px; right: 10px; height: 30px; padding: 0 12px; border: 0; border-radius: 8px; background: var(--brand); color: #fff; font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; }
  .sg-btn.sg-del { background: #fff; color: #b91c1c; border: 1px solid #fecaca; }
  .sg-btn:disabled { opacity: .5; cursor: default; }

  /* กล่องลงลายเซ็น */
  .sgd { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 16px; background: rgba(15, 23, 42, .5); }
  .sgd[hidden] { display: none; }
  .sgd-box { width: 100%; max-width: 520px; max-height: 100%; overflow: auto; padding: 18px; border-radius: 14px; background: #fff; box-shadow: 0 20px 50px rgba(15, 23, 42, .3); }
  .sgd h2 { margin: 0; font-size: 18px; color: var(--brand); }
  .sgd p { margin: 2px 0 10px; color: var(--muted); }
  .sgd p b { color: var(--ink); }
  .sgd-lb { display: block; margin-bottom: 3px; font-weight: 600; }
  .sgd input { width: 100%; height: 42px; margin-bottom: 10px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 10px; font: inherit; font-size: 16px; }
  .sgd input[readonly] { background: var(--soft); color: var(--muted); }
  .sgd canvas { display: block; width: 100%; height: 200px; border: 1.5px dashed #94a3b8; border-radius: 10px; background: #fff; touch-action: none; cursor: crosshair; }
  .sgd .err { min-height: 20px; margin-top: 6px; color: #b91c1c; font-size: 12.5px; }
  .sgd .acts { display: flex; gap: 8px; margin-top: 6px; }
  .sgd .acts button { height: 42px; padding: 0 16px; border: 0; border-radius: 10px; font: inherit; font-weight: 600; cursor: pointer; }
  .sgd .acts .sp { flex: 1; }
  .sgd .acts button:disabled { opacity: .5; cursor: default; }
  .sign .nm { min-height: 19px; }
  .sign .muted { color: var(--muted); font-size: 12px; }
  .sign .dt { margin-top: 8px; color: var(--muted); font-size: 12px; }

  .foot-screen { margin-top: 14px; padding-top: 6px; border-top: 1px solid var(--line); display: flex; justify-content: space-between; color: var(--muted); font-size: 10.5px; }
  .void { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; overflow: hidden; }
  .void span { font-size: 110px; font-weight: 700; color: rgba(220, 38, 38, .13); transform: rotate(-28deg); border: 10px solid rgba(220, 38, 38, .13); padding: 0 30px; border-radius: 18px; }

  /* มือถือ: รายการเป็นการ์ด แถบปุ่มติดล่างจอ กล่องเซ็นเต็มความกว้าง
     html.measure = ปิดชั่วคราวตอนคำนวณแถวว่าง ให้วัดจากขนาด A4 จริง (fill) — งานพิมพ์จึงเหมือนบนคอม */
  html.measure .sheet { width: 210mm; max-width: none; }
  @media screen and (max-width: 640px) {
    html:not(.measure) body { background: #f1f5f9; font-size: 14px; padding-bottom: calc(76px + env(safe-area-inset-bottom)); }
    html:not(.measure) .bar { padding: 10px 14px; }
    html:not(.measure) .bar .grow { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    html:not(.measure) .bar-acts { position: fixed; left: 0; right: 0; bottom: 0; z-index: 11; display: flex; gap: 8px; padding: 10px 12px calc(10px + env(safe-area-inset-bottom)); background: #fff; border-top: 1px solid var(--line); box-shadow: 0 -4px 14px rgba(15, 23, 42, .08); }
    html:not(.measure) .bar-acts button { flex: 1 1 0; min-width: 0; height: 48px; padding: 0 6px; border-radius: 12px; font-size: 14px; }
    html:not(.measure) .sheet { width: auto; min-height: 0; margin: 12px; padding: 16px; border-radius: 14px; overflow: hidden; }
    html:not(.measure) .rp-head { flex-wrap: wrap; gap: 8px 12px; padding-bottom: 12px; }
    html:not(.measure) .rp-head .txt { order: 3; flex-basis: 100%; }
    html:not(.measure) .docno { margin-left: auto; }
    html:not(.measure) .info { grid-template-columns: 1fr 1fr; }
    html:not(.measure) .info small { font-size: 12px; }
    html:not(.measure) table.items { display: block; font-size: 14px; }
    html:not(.measure) table.items tbody { display: block; }
    html:not(.measure) table.items thead { display: none; }
    html:not(.measure) table.items colgroup { display: none; }
    html:not(.measure) table.items tbody tr { display: grid; grid-template-columns: 24px minmax(0, 1fr) auto auto; column-gap: 6px; align-items: baseline; padding: 12px 0; border-bottom: 1px solid var(--line); }
    html:not(.measure) table.items tbody tr.fill { display: none; }
    html:not(.measure) table.items tbody tr td { padding: 0; border: 0; background: none !important; }
    html:not(.measure) table.items td:nth-child(1) { grid-area: 1 / 1; color: var(--muted); text-align: left; }
    html:not(.measure) table.items td:nth-child(2) { grid-area: 2 / 2; font-size: 12.5px; color: var(--muted); }
    html:not(.measure) table.items td:nth-child(3) { grid-area: 1 / 2; font-weight: 600; }
    html:not(.measure) table.items td:nth-child(4) { grid-area: 1 / 3; font-size: 16px; color: var(--brand); }
    html:not(.measure) table.items td:nth-child(5) { grid-area: 1 / 4; color: var(--muted); }
    html:not(.measure) table.items td:nth-child(6) { grid-area: 3 / 2 / auto / -1; font-size: 12.5px; color: var(--muted); }
    html:not(.measure) table.items td:nth-child(6):not(:empty)::before { content: 'หมายเหตุ: '; }
    html:not(.measure) table.items tbody tr.total { display: block; padding: 10px 0 0; border: 0; }
    html:not(.measure) table.items tbody tr.total td { display: block; text-align: right; color: var(--muted); }
    html:not(.measure) .sign { grid-template-columns: 1fr; }
    html:not(.measure) .sign .ln { height: 64px; }
    html:not(.measure) .sign .ln img { max-height: 62px; }
    html:not(.measure) .sg-btn { height: 36px; }
    html:not(.measure) .sg-btn[data-sign] { display: none; }
    html:not(.measure) .void span { font-size: 60px; border-width: 6px; padding: 0 18px; }
    html:not(.measure) .foot-screen { font-size: 11.5px; }
    .sgd { align-items: flex-end; padding: 0; }
    .sgd-box { max-width: none; padding: 18px 16px calc(16px + env(safe-area-inset-bottom)); border-radius: 18px 18px 0 0; }
    .sgd canvas { height: 42vh; max-height: 300px; }
    .sgd .acts button { height: 48px; border-radius: 12px; font-size: 15px; }
    .sgd .acts #sgd-ok { flex: 1; }
    .sgd .acts .sp { display: none; }
  }
  /* มือถือแนวนอน: ผืนเซ็นไม่สูงเกินจอ */
  @media screen and (max-height: 480px) { .sgd { align-items: center; } .sgd-box { max-width: 720px; padding: 10px 16px; border-radius: 14px; } .sgd canvas { height: 44vh; } }
  @media print {
    body { background: #fff; }
    .bar, .foot-screen, .sg-btn, .sgd { display: none !important; }
    .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
    .void-note, .info div, table.items tbody tr:nth-child(even) td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .void { position: fixed; }
  }
</style>
</head>
<body>
<div class="bar">
  <div class="grow"><b><?php echo h($pr['pr_no']); ?></b> <span class="st <?php echo $STATE[1]; ?>"><?php echo $STATE[0]; ?></span> <span class="bar-t">ใบขออนุมัติจัดซื้อ</span></div>
  <button type="button" class="btn-ghost" onclick="window.close()">ปิด</button>
  <div class="bar-acts">
  <?php foreach (['req' => 'เซ็นผู้ขอซื้อ', 'ap' => 'เซ็นผู้อนุมัติ'] as $k => $lb): $sg = sign_state($k); if ($sg['can_sign']): ?>
  <button type="button" class="btn-sign" data-sign="<?php echo $k; ?>"><?php echo $lb; ?></button>
  <?php endif; endforeach; ?>
  <button type="button" class="btn" onclick="window.print()">พิมพ์</button>
  </div>
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
    <div><small>ผู้ขอซื้อ/ขอจ้าง</small><b><?php echo h($reqName); ?></b></div>
    <div><small>จำนวนรายการ</small><b><?php echo count($items); ?> รายการ</b></div>
  </div>

  <?php if ($cancelled): ?>
  <div class="void-note"><b>ยกเลิกแล้ว</b><?php if (!empty($pr['cancelled_at'])): ?> เมื่อ <?php echo h(spr_thai_date(substr($pr['cancelled_at'], 0, 10)) . ' ' . substr($pr['cancelled_at'], 11, 5)); ?><?php endif; ?><?php if (trim((string)($pr['cancel_reason'] ?? '')) !== ''): ?> · เหตุผล: <?php echo h($pr['cancel_reason']); ?><?php endif; ?></div>
  <?php endif; ?>
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
    <div class="sgb">
      <div class="t">ผู้ขอซื้อ/ขอจ้าง</div>
      <?php $sg = sign_state('req'); ?>
      <div class="ln"><?php if ($sg['img'] !== ''): ?><img src="<?php echo h($sg['img']); ?>" alt="ลายเซ็นผู้ขอซื้อ/ขอจ้าง"><?php endif; ?></div>
      <div class="nm">( <?php echo h($reqName); ?> )</div>
      <div class="dt">วันที่ <?php echo $sg['at'] !== '' ? h($sg['at']) : '......../......../........'; ?></div>
      <?php if ($sg['can_sign']): ?><button type="button" class="sg-btn" data-sign="req">ลงนาม</button>
      <?php elseif ($sg['can_unsign']): ?><button type="button" class="sg-btn sg-del" data-unsign="req">ลบลายเซ็น</button><?php endif; ?>
    </div>
    <div class="sgb">
      <div class="t">ผู้อนุมัติ</div>
      <?php $sg = sign_state('ap'); ?>
      <div class="ln"><?php if ($sg['img'] !== ''): ?><img src="<?php echo h($sg['img']); ?>" alt="ลายเซ็นผู้อนุมัติ"><?php endif; ?></div>
      <div class="nm">( <?php echo $approver !== '' ? h($approver) : '................................................'; ?> )</div>
      <div class="dt">วันที่ <?php echo $sg['at'] !== '' ? h($sg['at']) : '......../......../........'; ?></div>
      <?php if ($sg['can_sign']): ?><button type="button" class="sg-btn" data-sign="ap">ลงนาม</button>
      <?php elseif ($sg['can_unsign']): ?><button type="button" class="sg-btn sg-del" data-unsign="ap">ลบลายเซ็น</button><?php endif; ?>
    </div>
  </div>

  <div class="foot-screen"><span><?php echo h($foot); ?></span><span>EasyPro</span></div>
</div>
<?php if ($signRights['req'] || $signRights['ap']): ?>
<div class="sgd" id="sgd" hidden role="dialog" aria-modal="true" aria-labelledby="sgd-t">
  <div class="sgd-box">
    <h2 id="sgd-t"></h2>
    <p><?php echo h($pr['pr_no']); ?> · <?php echo count($items); ?> รายการ</p>
    <label class="sgd-lb" for="sgd-n">ชื่อ-นามสกุล</label>
    <input type="text" id="sgd-n" maxlength="150" autocomplete="name" placeholder="ชื่อ นามสกุล">
    <canvas id="sgd-c"></canvas>
    <div class="err" id="sgd-e" role="alert"></div>
    <div class="acts">
      <button type="button" class="btn-ghost" id="sgd-clear">ล้าง</button>
      <span class="sp"></span>
      <button type="button" class="btn-ghost" id="sgd-x">ยกเลิก</button>
      <button type="button" class="btn" id="sgd-ok">บันทึกลายเซ็น</button>
    </div>
  </div>
</div>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js" async></script>
<script>
// ลงนาม / ลบลายเซ็น — บันทึกแล้วโหลดหน้าใหม่
(function () {
  var PR = <?php echo json_encode(['id' => $id, 'ver' => (string)$pr['updated_at'], 'names' => ['req' => $reqName, 'ap' => $apSignName], 'lock' => ['req' => spr_state($pr) === 'approved' && spr_has_surname($reqName), 'ap' => $apLock], 'old' => $apMine ? '' : $approver], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
  var dlg = document.getElementById('sgd'), $ = function (x) { return document.getElementById(x); }, pad = null, slot = '';
  function reload() { location.replace('stock_pr_print.php?id=' + PR.id); }
  function post(action, body, done) {
    fetch('handle_stock_pr.php?action=' + action, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
      .then(function (r) { return r.json(); })
      .then(function (r) { done(r && r.success ? '' : (r && r.error) || 'บันทึกไม่สำเร็จ'); })
      .catch(function () { done('เชื่อมต่อไม่สำเร็จ กรุณาลองใหม่'); });
  }
  // ขนาดผืนตามความหนาแน่นจอ — เปลี่ยนขนาด/หมุนจอแล้วเส้นเดิมยังอยู่
  function fit() {
    if (!pad) return;
    var c = $('sgd-c'), k = Math.max(window.devicePixelRatio || 1, 1), data = pad.toData();
    c.width = c.offsetWidth * k; c.height = c.offsetHeight * k;
    c.getContext('2d').scale(k, k);
    pad.clear();
    if (data.length) pad.fromData(data);
  }
  function close() { dlg.hidden = true; if (pad) { pad.off(); pad = null; } }
  function open(s) {
    if (!window.SignaturePad) { alert('โหลดช่องเซ็นไม่สำเร็จ กรุณาตรวจอินเทอร์เน็ตแล้วรีเฟรชหน้า'); return; }
    slot = s;
    $('sgd-t').textContent = s === 'ap' ? 'ลงนามผู้อนุมัติ' : 'ลงนามผู้ขอซื้อ/ขอจ้าง';
    $('sgd-n').value = PR.names[s] || '';
    $('sgd-n').readOnly = !!PR.lock[s];
    $('sgd-e').textContent = s === 'ap' && PR.old ? 'ชื่อผู้อนุมัติในใบจะเปลี่ยนจาก ' + PR.old + ' เป็น ' + PR.names.ap : '';
    dlg.hidden = false;
    pad = new SignaturePad($('sgd-c'), { penColor: 'rgb(15, 23, 92)', minWidth: 1, maxWidth: 2.8 });
    fit();
    if (!/\S\s+\S/.test($('sgd-n').value)) $('sgd-n').focus(); else $('sgd-ok').focus();
  }
  // ตัดขอบว่างรอบเส้น และย่อ — รูปเล็ก เห็นเส้นเต็มช่องในใบพิมพ์
  function trimmed() {
    var c = $('sgd-c'), w = c.width, h = c.height, d = c.getContext('2d').getImageData(0, 0, w, h).data;
    var x0 = w, y0 = h, x1 = -1, y1 = -1;
    for (var y = 0; y < h; y++) for (var x = 0; x < w; x++) if (d[(y * w + x) * 4 + 3] > 8) { if (x < x0) x0 = x; if (x > x1) x1 = x; if (y < y0) y0 = y; if (y > y1) y1 = y; }
    var p = 6, cw = x1 - x0 + 1 + p * 2, ch = y1 - y0 + 1 + p * 2, s = Math.min(1, 700 / cw, 260 / ch), o = document.createElement('canvas');
    o.width = Math.max(1, Math.round(cw * s)); o.height = Math.max(1, Math.round(ch * s));
    o.getContext('2d').drawImage(c, x0 - p, y0 - p, cw, ch, 0, 0, o.width, o.height);
    return o.toDataURL('image/png');
  }
  // เซ็นนิดเดียว (จุด/แตะพลาด) ไม่นับเป็นลายเซ็น
  function tooSmall() {
    var len = 0;
    pad.toData().forEach(function (g) { for (var i = 1; i < g.points.length; i++) len += Math.hypot(g.points[i].x - g.points[i - 1].x, g.points[i].y - g.points[i - 1].y); });
    return len < 30;
  }
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-sign]');
    if (b) { open(b.getAttribute('data-sign')); return; }
    b = e.target.closest('[data-unsign]');
    if (b) {
      if (!confirm(b.getAttribute('data-unsign') === 'ap' ? 'ลบลายเซ็นผู้อนุมัติ?\nใบนี้จะกลับเป็น "รออนุมัติ" และแก้ไข/ยกเลิกได้' : 'ลบลายเซ็นช่องนี้?')) return;
      b.disabled = true;
      post('unsign', { id: PR.id, slot: b.getAttribute('data-unsign') }, function (err) { if (err) { b.disabled = false; alert(err); } else reload(); });
    }
  });
  if (!dlg) return;
  $('sgd-clear').onclick = function () { pad.clear(); $('sgd-e').textContent = ''; };
  $('sgd-x').onclick = close;
  $('sgd-ok').onclick = function () {
    var name = $('sgd-n').value.trim();
    if (!/\S\s+\S/.test(name)) { $('sgd-e').textContent = 'กรุณากรอกชื่อ-นามสกุล (เว้นวรรคระหว่างชื่อกับนามสกุล)'; $('sgd-n').focus(); return; }
    if (pad.isEmpty() || tooSmall()) { $('sgd-e').textContent = 'กรุณาลงลายมือชื่อ'; return; }
    var btn = $('sgd-ok');
    btn.disabled = true;
    post('sign', { id: PR.id, slot: slot, name: name, sign: trimmed(), ver: PR.ver }, function (err) { if (err) { btn.disabled = false; $('sgd-e').textContent = err; } else reload(); });
  };
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !dlg.hidden) close(); });
  window.addEventListener('resize', function () { if (!dlg.hidden) fit(); });
})();
</script>
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
    document.documentElement.classList.add('measure');   // มือถือ: วัดด้วยขนาด A4
    try { fillRows(); } finally { document.documentElement.classList.remove('measure'); }
  }
  function fillRows() {
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
  });
  window.addEventListener('beforeprint', fill);
})();
</script>
</body>
</html>
