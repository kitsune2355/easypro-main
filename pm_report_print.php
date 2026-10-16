<?php
// หน้าพิมพ์รายงานส่งมอบ PM (เปิดจากแท็บ "รายงาน PM")
//  kind=summary  รายงานสรุป — sec = overview,groups,machines,fails,spares,evals,postpones,sign
//  kind=machine  รายงานรายเครื่อง — off/lim = แบ่งชุดเครื่อง · info=1 หน้าข้อมูลเครื่อง · attach=1 ใบงาน PM · photos=1 รูปผลตรวจ
//                (ไม่ส่ง info มา = แสดงหน้าข้อมูลเครื่อง เหมือนเดิม)
@session_start();
include "config_ctrl/checksession.php";
include "config_ctrl/connect.php";
date_default_timezone_set('Asia/Bangkok');
mysqli_set_charset($connect, 'utf8mb4');
ini_set('memory_limit', '1024M');
set_time_limit(600);
require_once __DIR__ . '/config_ctrl/pm_report_data.php';
require_once __DIR__ . '/pm_worksheet_sheet.php';

$ag = isset($sess_user_agency_es) ? trim((string)$sess_user_agency_es) : '';
if ($ag === '') die('ไม่พบหน่วยงาน กรุณาเข้าสู่ระบบใหม่');
if (!pmr_can_manage($connect)) die('ไม่มีสิทธิ์ใช้งานรายงาน PM');

$kind   = ($_REQUEST['kind'] ?? 'summary') === 'machine' ? 'machine' : 'summary';
$f      = pmr_filters($_REQUEST);
$secs   = array_filter(explode(',', (string)($_REQUEST['sec'] ?? 'overview,groups,machines,fails,spares,evals,postpones,sign')));
$sec    = function ($k) use ($secs) { return in_array($k, $secs, true); };
$attach = !empty($_REQUEST['attach']);
$info   = !isset($_REQUEST['info']) || !empty($_REQUEST['info']);
if ($kind === 'machine' && !$info && !$attach) $info = true;   // ต้องมีอย่างน้อย 1 อย่าง
$photos = !empty($_REQUEST['photos']);
$off    = max(0, (int)($_REQUEST['off'] ?? 0));
$lim    = max(0, (int)($_REQUEST['lim'] ?? 0));

$R = pmr_build($connect, $ag, $f);
$K = $R['kpi'];
$agency = $R['agency'];
$machines = $R['machines'];
$totalMachines = count($machines);
if ($kind === 'machine' && $lim > 0) $machines = array_slice($machines, $off, $lim);

$n = function ($v, $d = 0) { return number_format((float)$v, $d); };
$pct = function ($v) { return rtrim(rtrim(number_format((float)$v, 1), '0'), '.') . '%'; };
$byMachine = function ($rows) {
    $o = [];
    foreach ($rows as $r) $o[$r['machine_id']][] = $r;
    return $o;
};
$failsBy = $byMachine($R['fails']);
$sparesBy = $byMachine($R['spares']);

// ใบงานที่ต้องแนบ (ดึงทีละเครื่อง เพื่อไม่ให้หน่วยความจำพุ่ง)
function pmr_work_record($connect, $wrId, $freq) {
    $st = $connect->prepare("SELECT w.*, a.ag_contract, a.ag_located FROM pm_work_records w LEFT JOIN tb_agency a ON a.ag_id = w.ag_id WHERE w.id = ?");
    $st->bind_param('i', $wrId);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();
    $st->close();
    if ($r) $r['freq_description'] = $freq;
    return $r;
}

$title = $kind === 'summary' ? 'รายงานสรุปผลการบำรุงรักษาเชิงป้องกัน (PM)' : 'รายงานผลการบำรุงรักษาเชิงป้องกัน (PM) รายเครื่อง';
$batchText = ($kind === 'machine' && $lim > 0)
    ? 'เครื่องลำดับที่ ' . ($off + 1) . '–' . min($off + $lim, $totalMachines) . ' จาก ' . $totalMachines . ' เครื่อง'
    : '';
$stateCls = ['done' => 'st-done', 'late' => 'st-late', 'overdue' => 'st-over', 'upcoming' => 'st-up'];

function pmr_header($agency, $title, $f, $sub = '') { ?>
    <header class="rp-head">
        <img src="Logo_โปร.png" alt="" class="rp-logo">
        <div class="rp-head-main">
            <div class="rp-title"><?php echo pmr_h($title); ?></div>
            <div class="rp-agency"><?php echo pmr_h($agency['ag_contract']); ?><?php if ($agency['ag_job']): ?> <span class="muted">(<?php echo pmr_h($agency['ag_job']); ?>)</span><?php endif; ?></div>
            <?php if ($agency['ag_located']): ?><div class="muted"><?php echo pmr_h($agency['ag_located']); ?></div><?php endif; ?>
        </div>
        <div class="rp-head-side">
            <div><b>ช่วงข้อมูล</b> <?php echo pmr_h(pmr_period_text($f)); ?></div>
            <?php if ($sub): ?><div><?php echo pmr_h($sub); ?></div><?php endif; ?>
            <div class="muted">พิมพ์เมื่อ <?php echo pmr_h(pmr_thai_date(date('Y-m-d'))) . ' ' . date('H:i'); ?> น.</div>
        </div>
    </header>
<?php }

function pmr_signs($labels) { ?>
    <div class="rp-signs">
        <?php foreach ($labels as $l): ?>
            <div class="rp-sign">
                <div class="rp-sign-line"></div>
                <div>(.......................................................)</div>
                <div class="b"><?php echo pmr_h($l); ?></div>
                <div class="muted">วันที่ ........./........./.............</div>
            </div>
        <?php endforeach; ?>
    </div>
<?php }
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo pmr_h($title . ' - ' . $agency['ag_contract']); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<?php if ($attach): ?>
<script src="https://cdn.tailwindcss.com"></script>
<style type="text/tailwindcss">
    /* ใบงานที่แนบ: สไตล์เดียวกับ pm_worksheet_print.php (เฉพาะภายใน .ws) */
    .ws { font-family: Arial, "TH Sarabun New", Tahoma, sans-serif; }
    .ws table { @apply w-full border-collapse table-fixed; }
    .ws td, .ws th { @apply border border-black px-1 py-[2px] align-middle text-[8px] leading-[1.25]; }
</style>
<?php endif; ?>
<style>
    :root { --ink: #0f172a; --muted: #64748b; --line: #cbd5e1; --head: #e0f2fe; --brand: #006B9F; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #e5e7eb; color: var(--ink); font-family: "Sarabun", Tahoma, sans-serif; font-size: 11px; }
    .bar { position: sticky; top: 0; z-index: 50; background: #0f172a; color: #fff; padding: 10px 16px; display: flex; gap: 12px; align-items: center; justify-content: space-between; font-family: Tahoma, sans-serif; }
    .bar b { font-size: 14px; }
    .bar .info { font-size: 12px; color: #cbd5e1; }
    .bar button { border: 0; border-radius: 6px; padding: 8px 14px; cursor: pointer; font-size: 13px; color: #fff; background: #0284c7; }
    .bar button.sec { background: #334155; }
    .wrap { padding: 16px; }
    .page { width: 210mm; min-height: 297mm; margin: 0 auto 16px; background: #fff; padding: 12mm 12mm 14mm; box-shadow: 0 1px 4px rgba(0,0,0,.15); }
    .muted { color: var(--muted); }
    .b { font-weight: 700; }
    .rp-head { display: flex; gap: 12px; align-items: flex-start; border-bottom: 2px solid var(--brand); padding-bottom: 8px; margin-bottom: 10px; }
    .rp-logo { width: 90px; height: 34px; object-fit: contain; }
    .rp-head-main { flex: 1; }
    .rp-title { font-size: 15px; font-weight: 700; color: var(--brand); }
    .rp-agency { font-size: 13px; font-weight: 700; margin-top: 2px; }
    .rp-head-side { text-align: right; font-size: 10.5px; line-height: 1.6; }
    h2 { font-size: 12.5px; margin: 14px 0 6px; padding-left: 7px; border-left: 4px solid var(--brand); break-after: avoid; }
    h2 .muted { font-weight: 400; font-size: 10.5px; }
    table.t { width: 100%; border-collapse: collapse; font-size: 10px; }
    table.t th, table.t td { border: 1px solid var(--line); padding: 3px 5px; vertical-align: top; }
    table.t th { background: var(--head); font-weight: 700; text-align: center; }
    table.t thead { display: table-header-group; }
    table.t tr { break-inside: avoid; }
    table.t tfoot td { background: #f8fafc; font-weight: 700; }
    .c { text-align: center; } .r { text-align: right; } .nw { white-space: nowrap; }
    .kpis { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; }
    .kpi { border: 1px solid var(--line); border-radius: 6px; padding: 6px 8px; }
    .kpi .v { font-size: 17px; font-weight: 700; line-height: 1.2; }
    .kpi .l { font-size: 9.5px; color: var(--muted); }
    .bar-pct { height: 9px; background: #e2e8f0; border-radius: 9px; overflow: hidden; margin-top: 8px; display: flex; }
    .bar-pct span { display: block; height: 100%; }
    .legend { display: flex; gap: 14px; font-size: 9.5px; margin-top: 4px; color: var(--muted); }
    .legend i { display: inline-block; width: 9px; height: 9px; border-radius: 2px; margin-right: 4px; vertical-align: -1px; }
    .rate { display: inline-block; min-width: 42px; }
    .mini { display: inline-block; height: 6px; background: #e2e8f0; border-radius: 6px; width: 50px; vertical-align: middle; overflow: hidden; margin-left: 4px; }
    .mini span { display: block; height: 100%; background: #10b981; }
    .st-done { color: #047857; font-weight: 700; } .st-late { color: #b45309; font-weight: 700; }
    .st-over { color: #b91c1c; font-weight: 700; } .st-up { color: #475569; }
    .fail { color: #b91c1c; font-weight: 700; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; border: 1px solid var(--line); border-radius: 6px; overflow: hidden; }
    .info-grid div { padding: 4px 8px; border-bottom: 1px solid #eef2f7; }
    .info-grid div span { color: var(--muted); display: inline-block; min-width: 82px; }
    .m-title { font-size: 14px; font-weight: 700; margin: 2px 0 6px; }
    .rp-signs { display: flex; gap: 14px; margin-top: 28px; break-inside: avoid; }
    .rp-sign { flex: 1; text-align: center; font-size: 10.5px; line-height: 1.7; }
    .rp-sign-line { height: 34px; }
    .empty { color: var(--muted); font-style: italic; padding: 6px 0; }
    .ws-wrap { margin: 0 auto 16px; width: 210mm; }
    /* ---------- หน้าจอ: แสดงเป็นแผ่น A4 (แนวตั้ง) เหมือนตอนพิมพ์ — จอที่แคบกว่า A4 ปรับเต็มความกว้าง ---------- */
    @media screen {
        .page { border-radius: 2px; }
        .ws-wrap { max-width: 100%; overflow-x: auto; }
    }
    @media screen and (max-width: 860px) {
        .page { width: 100%; min-height: 0; padding: 16px 14px 20px; }
        .ws-wrap { width: 100%; }
        .kpis { grid-template-columns: repeat(3, 1fr); }
    }
    @media screen and (max-width: 640px) {
        .bar { flex-wrap: wrap; padding: 8px 12px; } .bar b { font-size: 13px; } .bar .info { font-size: 11px; }
        .bar button { padding: 7px 10px; font-size: 12px; }
        .wrap { padding: 8px; }
        .page { padding: 14px 12px 18px; border-radius: 10px; margin-bottom: 10px; }
        .rp-head { flex-wrap: wrap; } .rp-head-side { text-align: left; width: 100%; }
        .rp-title { font-size: 15px; }
        .kpis { grid-template-columns: repeat(2, 1fr); }
        .info-grid { grid-template-columns: 1fr; }
        .tw { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .tw table.t { min-width: 620px; }
        .rp-signs { flex-direction: column; gap: 4px; margin-top: 18px; }
    }
    @page { size: A4 portrait; margin: 8mm; @bottom-right { content: "หน้า " counter(page) " / " counter(pages); font-size: 8px; color: #64748b; } }
    @media print {
        body { background: #fff; }
        .bar { display: none; }
        .wrap { padding: 0; }
        .page { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
        .ws-wrap { width: auto; margin: 0; }
        /* แต่ละเครื่อง และใบงาน/หน้ารูปผลตรวจที่แนบ เริ่มแผ่นใหม่เสมอ — ไม่ปนกับเครื่องอื่น */
        .page, .ws-wrap > div { break-before: page; break-after: auto !important; }
        .wrap > .page:first-child, .wrap > .ws-wrap:first-child > div:first-child { break-before: auto; }
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
</head>
<body>
<div class="bar">
    <div><b><?php echo pmr_h($title); ?></b>
        <div class="info"><?php echo pmr_h($agency['ag_contract'] . ' · ' . pmr_period_text($f) . ($batchText ? ' · ' . $batchText : '')); ?></div></div>
    <div style="display:flex;gap:8px">
        <button type="button" onclick="window.print()">พิมพ์ / Save PDF</button>
        <button type="button" class="sec" onclick="window.close()">ปิดหน้าต่าง</button>
    </div>
</div>
<div class="wrap">

<?php if ($kind === 'summary'): ?>
<section class="page">
    <?php pmr_header($agency, $title, $f); ?>

    <?php if ($sec('overview')): ?>
    <h2>1. ภาพรวมการดำเนินงาน</h2>
    <div class="kpis">
        <div class="kpi"><div class="v"><?php echo $n($K['planned']); ?></div><div class="l">แผน PM ทั้งหมด (รอบ) · <?php echo $n($K['machines']); ?> เครื่อง</div></div>
        <div class="kpi"><div class="v" style="color:#047857"><?php echo $n($K['done']); ?></div><div class="l">ดำเนินการแล้ว · <?php echo $pct($K['rate']); ?></div></div>
        <div class="kpi"><div class="v" style="color:#b91c1c"><?php echo $n($K['overdue']); ?></div><div class="l">ค้าง / เกินกำหนด</div></div>
        <div class="kpi"><div class="v"><?php echo $n($K['upcoming']); ?></div><div class="l">ยังไม่ถึงกำหนด</div></div>
        <div class="kpi"><div class="v"><?php echo $pct($K['ontime_rate']); ?></div><div class="l">ทำตรงตามแผน (<?php echo $n($K['ontime']); ?>/<?php echo $n($K['done']); ?>)</div></div>
        <div class="kpi"><div class="v"><?php echo $pct($K['pass_rate']); ?></div><div class="l">จุดตรวจผ่าน (<?php echo $n($K['pass']); ?>/<?php echo $n($K['pass'] + $K['fail']); ?> จุด)</div></div>
        <div class="kpi"><div class="v" style="color:<?php echo $K['fail'] ? '#b91c1c' : 'inherit'; ?>"><?php echo $n($K['fail']); ?></div><div class="l">จุดตรวจไม่ผ่าน</div></div>
        <div class="kpi"><div class="v"><?php echo $K['score_avg'] !== null ? $n($K['score_avg'], 2) : '-'; ?></div><div class="l">คะแนนประเมินเฉลี่ย (<?php echo $n($K['evals']); ?> ใบ)</div></div>
        <div class="kpi"><div class="v"><?php echo $n($K['postponed']); ?></div><div class="l">ครั้งที่เลื่อนแผน</div></div>
        <div class="kpi"><div class="v"><?php echo $n($K['spare_value'], 2); ?></div><div class="l">มูลค่าอะไหล่ที่ใช้ (บาท)</div></div>
    </div>
    <?php $tot = max(1, $K['planned']); ?>
    <div class="bar-pct">
        <span style="width:<?php echo $K['ontime'] * 100 / $tot; ?>%;background:#10b981"></span>
        <span style="width:<?php echo $K['late'] * 100 / $tot; ?>%;background:#f59e0b"></span>
        <span style="width:<?php echo $K['overdue'] * 100 / $tot; ?>%;background:#ef4444"></span>
        <span style="width:<?php echo $K['upcoming'] * 100 / $tot; ?>%;background:#94a3b8"></span>
    </div>
    <div class="legend"><span><i style="background:#10b981"></i>ทำแล้วตามแผน <?php echo $n($K['ontime']); ?></span><span><i style="background:#f59e0b"></i>ทำแล้วล่าช้า <?php echo $n($K['late']); ?></span><span><i style="background:#ef4444"></i>ค้าง/เกินกำหนด <?php echo $n($K['overdue']); ?></span><span><i style="background:#94a3b8"></i>ยังไม่ถึงกำหนด <?php echo $n($K['upcoming']); ?></span></div>
    <?php endif; ?>

    <?php if ($sec('groups')): ?>
    <?php foreach ([['2. สรุปตามประเภทเครื่อง', 'ประเภทเครื่อง', $R['by_type']], ['3. สรุปตามเช็คชีต', 'เช็คชีต', $R['by_checksheet']]] as $G): ?>
    <h2><?php echo pmr_h($G[0]); ?></h2>
    <div class="tw"><table class="t">
        <thead><tr><th style="width:5%">#</th><th><?php echo pmr_h($G[1]); ?></th><th style="width:9%">เครื่อง</th><th style="width:9%">แผน</th><th style="width:9%">ทำแล้ว</th><th style="width:9%">ค้าง</th><th style="width:16%">ความสำเร็จ</th><th style="width:9%">จุดไม่ผ่าน</th></tr></thead>
        <tbody>
        <?php foreach ($G[2] as $i => $g): ?>
            <tr><td class="c"><?php echo $i + 1; ?></td><td><?php echo pmr_h($g['name']); ?></td><td class="c"><?php echo $n($g['machines']); ?></td><td class="c"><?php echo $n($g['planned']); ?></td><td class="c"><?php echo $n($g['done']); ?></td><td class="c <?php echo $g['overdue'] ? 'fail' : ''; ?>"><?php echo $n($g['overdue']); ?></td>
                <td class="nw"><span class="rate"><?php echo $pct($g['rate']); ?></span><span class="mini"><span style="width:<?php echo $g['rate']; ?>%"></span></span></td><td class="c <?php echo $g['fail'] ? 'fail' : ''; ?>"><?php echo $n($g['fail']); ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$G[2]): ?><tr><td colspan="8" class="c empty">ไม่มีข้อมูล</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($sec('machines')): ?>
    <h2>4. สรุปรายเครื่อง <span class="muted">(<?php echo $n(count($R['machines'])); ?> เครื่อง)</span></h2>
    <div class="tw"><table class="t">
        <thead><tr><th style="width:4%">#</th><th style="width:11%">รหัสเครื่อง</th><th>ชื่อเครื่อง / ตำแหน่ง</th><th style="width:11%">ประเภท</th><th style="width:5%">แผน</th><th style="width:6%">ทำแล้ว</th><th style="width:5%">ค้าง</th><th style="width:7%">สำเร็จ</th><th style="width:10%">ครั้งก่อนหน้า</th><th style="width:10%">ทำล่าสุด</th><th style="width:6%">ไม่ผ่าน</th></tr></thead>
        <tbody>
        <?php foreach ($R['machines'] as $i => $m): $rate = $m['planned'] ? $m['done'] * 100 / $m['planned'] : 0; ?>
            <tr><td class="c"><?php echo $i + 1; ?></td><td class="nw"><?php echo pmr_h($m['code'] ?: '-'); ?></td><td><?php echo pmr_h($m['name']); ?><?php if ($m['location']): ?><div class="muted"><?php echo pmr_h($m['location']); ?></div><?php endif; ?></td><td><?php echo pmr_h($m['type']); ?></td>
                <td class="c"><?php echo $m['planned']; ?></td><td class="c"><?php echo $m['done']; ?></td><td class="c <?php echo $m['overdue'] ? 'fail' : ''; ?>"><?php echo $m['overdue']; ?></td><td class="c"><?php echo $pct($rate); ?></td><td class="c nw"><?php echo pmr_h(pmr_thai_date($m['prev'])); ?></td><td class="c nw"><?php echo pmr_h(pmr_thai_date($m['last'])); ?></td><td class="c <?php echo $m['fail'] ? 'fail' : ''; ?>"><?php echo $m['fail']; ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$R['machines']): ?><tr><td colspan="11" class="c empty">ไม่มีแผน PM ในช่วงนี้</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    <?php endif; ?>

    <?php if ($sec('fails')): ?>
    <h2>5. รายการจุดตรวจที่ไม่ผ่าน <span class="muted">(<?php echo $n(count($R['fails'])); ?> รายการ)</span></h2>
    <?php if ($R['fails']): ?>
    <div class="tw"><table class="t">
        <thead><tr><th style="width:4%">#</th><th style="width:11%">วันที่</th><th style="width:22%">เครื่อง</th><th>จุดตรวจ</th><th style="width:11%">มาตรฐาน</th><th style="width:10%">ค่าที่วัด</th><th style="width:20%">หมายเหตุ/ข้อเสนอแนะ</th></tr></thead>
        <tbody>
        <?php foreach ($R['fails'] as $i => $x): ?>
            <tr><td class="c"><?php echo $i + 1; ?></td><td class="c nw"><?php echo pmr_h(pmr_thai_date($x['date'])); ?></td><td><b><?php echo pmr_h($x['code']); ?></b> <?php echo pmr_h($x['name']); ?></td><td><?php echo pmr_h($x['check_point']); ?></td><td class="c"><?php echo pmr_h($x['standard'] ?: '-'); ?></td><td class="c fail"><?php echo pmr_h($x['actual'] ?: '-'); ?></td><td><?php echo pmr_h($x['remarks'] ?: '-'); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php else: ?><div class="empty">ไม่พบจุดตรวจที่ไม่ผ่านในช่วงนี้</div><?php endif; ?>
    <?php endif; ?>

    <?php if ($sec('spares')): ?>
    <h2>6. สรุปอะไหล่ / วัสดุที่ใช้ <span class="muted">(<?php echo $n(count($R['spares'])); ?> รายการเบิก)</span></h2>
    <?php if ($R['spare_summary']): ?>
    <div class="tw"><table class="t">
        <thead><tr><th style="width:4%">#</th><th style="width:15%">รหัสสินค้า</th><th>รายการ</th><th style="width:9%">ครั้ง</th><th style="width:10%">จำนวน</th><th style="width:9%">หน่วย</th><th style="width:14%">มูลค่า (บาท)</th></tr></thead>
        <tbody>
        <?php foreach ($R['spare_summary'] as $i => $x): ?>
            <tr><td class="c"><?php echo $i + 1; ?></td><td class="nw"><?php echo pmr_h($x['item_code'] ?: '-'); ?></td><td><?php echo pmr_h($x['item']); ?></td><td class="c"><?php echo $x['times']; ?></td><td class="r"><?php echo $n($x['qty'], 2); ?></td><td class="c"><?php echo pmr_h($x['unit']); ?></td><td class="r"><?php echo $n($x['total'], 2); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="6" class="r">รวมมูลค่า</td><td class="r"><?php echo $n($K['spare_value'], 2); ?></td></tr></tfoot>
    </table></div>
    <?php else: ?><div class="empty">ไม่มีการเบิกใช้อะไหล่ในช่วงนี้</div><?php endif; ?>
    <?php endif; ?>

    <?php if ($sec('evals')): ?>
    <h2>7. ผลการประเมินงาน PM <span class="muted">(<?php echo $n(count($R['evaluations'])); ?> ใบ<?php echo $K['score_avg'] !== null ? ' · เฉลี่ย ' . $n($K['score_avg'], 2) : ''; ?>)</span></h2>
    <?php if ($R['evaluations']): ?>
    <div class="tw"><table class="t">
        <thead><tr><th style="width:4%">#</th><th style="width:11%">วันที่ประเมิน</th><th style="width:24%">เครื่อง</th><th style="width:15%">ผู้ประเมิน</th><th style="width:8%">คะแนน</th><th>ความคิดเห็น</th></tr></thead>
        <tbody>
        <?php foreach ($R['evaluations'] as $i => $x): ?>
            <tr><td class="c"><?php echo $i + 1; ?></td><td class="c nw"><?php echo pmr_h(pmr_thai_date($x['date'])); ?></td><td><b><?php echo pmr_h($x['code']); ?></b> <?php echo pmr_h($x['name']); ?></td><td><?php echo pmr_h($x['evaluator']); ?></td><td class="c b"><?php echo $n($x['score'], 2); ?></td><td><?php echo pmr_h($x['comments'] ?: '-'); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php else: ?><div class="empty">ยังไม่มีผลประเมินในช่วงนี้</div><?php endif; ?>
    <?php endif; ?>

    <?php if ($sec('postpones') && $R['postpones']): ?>
    <h2>8. ประวัติการเลื่อนแผน <span class="muted">(<?php echo $n(count($R['postpones'])); ?> ครั้ง)</span></h2>
    <div class="tw"><table class="t">
        <thead><tr><th style="width:4%">#</th><th style="width:26%">เครื่อง</th><th style="width:13%">วันเดิม</th><th style="width:13%">วันใหม่</th><th>เหตุผล</th></tr></thead>
        <tbody>
        <?php foreach ($R['postpones'] as $i => $x): ?>
            <tr><td class="c"><?php echo $i + 1; ?></td><td><b><?php echo pmr_h($x['code']); ?></b> <?php echo pmr_h($x['name']); ?></td><td class="c"><?php echo pmr_h(pmr_thai_date($x['old_date'])); ?></td><td class="c"><?php echo pmr_h(pmr_thai_date($x['new_date'])); ?></td><td><?php echo pmr_h($x['reason'] ?: '-'); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
    <?php endif; ?>

    <?php if ($sec('sign')) pmr_signs(['ผู้จัดทำรายงาน', 'ผู้ตรวจสอบ (หัวหน้างาน/วิศวกร)', 'ผู้รับมอบ (ตัวแทนหน่วยงาน)']); ?>
</section>

<?php else: /* ---------------- รายเครื่อง ---------------- */ ?>
<?php if (!$machines): ?>
<section class="page"><?php pmr_header($agency, $title, $f); ?><div class="empty">ไม่มีแผน PM ในช่วงนี้</div></section>
<?php endif; ?>
<?php $printed = 0; foreach ($machines as $mi => $m): $rate = $m['planned'] ? $m['done'] * 100 / $m['planned'] : 0; ?>
<?php if ($info): $printed++; ?>
<section class="page">
    <?php pmr_header($agency, $title, $f, 'เครื่องที่ ' . ($off + $mi + 1) . ' / ' . $totalMachines); ?>
    <div class="m-title"><?php echo pmr_h(($m['code'] ? $m['code'] . ' · ' : '') . $m['name']); ?></div>
    <div class="info-grid">
        <div><span>ประเภท</span><?php echo pmr_h($m['type'] ?: '-'); ?></div>
        <div><span>ตำแหน่ง</span><?php echo pmr_h($m['location'] ?: '-'); ?></div>
        <div><span>รุ่น / S/N</span><?php echo pmr_h(trim(($m['model'] ?: '-') . ' / ' . ($m['sn'] ?: '-'))); ?></div>
        <div><span>เช็คชีต</span><?php echo pmr_h(implode(', ', $m['checksheets']) ?: '-'); ?></div>
        <div><span>แผน / ทำแล้ว</span><?php echo $m['planned']; ?> / <?php echo $m['done']; ?> รอบ (<?php echo $pct($rate); ?>)<?php if ($m['overdue']): ?> · <span class="fail" style="min-width:0">ค้าง <?php echo $m['overdue']; ?></span><?php endif; ?></div>
        <div><span>จุดไม่ผ่าน</span><span class="<?php echo $m['fail'] ? 'fail' : ''; ?>" style="min-width:0;color:<?php echo $m['fail'] ? '' : 'inherit'; ?>"><?php echo $m['fail']; ?></span> · <span style="min-width:0">ครั้งก่อนหน้า</span> <?php echo pmr_h(pmr_thai_date($m['prev'])); ?> · <span style="min-width:0">ทำล่าสุด</span> <?php echo pmr_h(pmr_thai_date($m['last'])); ?></div>
    </div>

    <h2>ประวัติการทำ PM ในช่วงนี้</h2>
    <div class="tw"><table class="t">
        <thead><tr><th style="width:4%">#</th><th style="width:11%">วันที่ตามแผน</th><th style="width:11%">วันที่ทำจริง</th><th style="width:14%">สถานะ</th><th>เช็คชีต / ผู้ตรวจ</th><th style="width:13%">ผลตรวจ<br><span class="muted">ผ่าน/ไม่ผ่าน/NA</span></th><th style="width:8%">ประเมิน</th></tr></thead>
        <tbody>
        <?php foreach ($m['events'] as $i => $e): ?>
            <tr><td class="c"><?php echo $i + 1; ?></td><td class="c nw"><?php echo pmr_h(pmr_thai_date($e['event_date'])); ?></td><td class="c nw"><?php echo pmr_h(pmr_thai_date($e['actual_date'])); ?></td>
                <td class="c <?php echo $stateCls[$e['state']] ?? ''; ?>"><?php echo pmr_h(pmr_state_label($e['state'])); ?></td>
                <td><?php echo pmr_h($e['cs_name']); ?><?php if ($e['inspector_name']): ?><div class="muted"><?php echo pmr_h($e['inspector_name']); ?></div><?php endif; ?><?php if ($e['remarks']): ?><div class="muted">หมายเหตุ: <?php echo pmr_h($e['remarks']); ?></div><?php endif; ?></td>
                <td class="c"><?php echo $e['wr_id'] ? $e['pass'] . ' / <span class="' . ($e['fail'] ? 'fail' : '') . '">' . $e['fail'] . '</span> / ' . $e['na'] : '-'; ?></td>
                <td class="c"><?php echo $e['average_score'] !== null ? $n($e['average_score'], 2) : '-'; ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>

    <?php $mf = $failsBy[$m['id']] ?? []; if ($mf): ?>
    <h2>จุดตรวจที่ไม่ผ่าน</h2>
    <div class="tw"><table class="t">
        <thead><tr><th style="width:12%">วันที่</th><th>จุดตรวจ</th><th style="width:14%">มาตรฐาน</th><th style="width:12%">ค่าที่วัด</th><th style="width:26%">หมายเหตุ</th></tr></thead>
        <tbody><?php foreach ($mf as $x): ?><tr><td class="c nw"><?php echo pmr_h(pmr_thai_date($x['date'])); ?></td><td><?php echo pmr_h($x['check_point']); ?></td><td class="c"><?php echo pmr_h($x['standard'] ?: '-'); ?></td><td class="c fail"><?php echo pmr_h($x['actual'] ?: '-'); ?></td><td><?php echo pmr_h($x['remarks'] ?: '-'); ?></td></tr><?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>

    <?php $ms = $sparesBy[$m['id']] ?? []; if ($ms): $msum = 0; ?>
    <h2>อะไหล่ / วัสดุที่ใช้</h2>
    <div class="tw"><table class="t">
        <thead><tr><th style="width:12%">วันที่</th><th style="width:15%">รหัส</th><th>รายการ</th><th style="width:10%">จำนวน</th><th style="width:9%">หน่วย</th><th style="width:13%">มูลค่า</th></tr></thead>
        <tbody><?php foreach ($ms as $x): $msum += $x['total']; ?><tr><td class="c nw"><?php echo pmr_h(pmr_thai_date($x['date'])); ?></td><td class="nw"><?php echo pmr_h($x['item_code']); ?></td><td><?php echo pmr_h($x['item']); ?></td><td class="r"><?php echo $n($x['qty'], 2); ?></td><td class="c"><?php echo pmr_h($x['unit']); ?></td><td class="r"><?php echo $n($x['total'], 2); ?></td></tr><?php endforeach; ?></tbody>
        <tfoot><tr><td colspan="5" class="r">รวม</td><td class="r"><?php echo $n($msum, 2); ?></td></tr></tfoot>
    </table></div>
    <?php endif; ?>

    <?php pmr_signs(['ผู้ดำเนินการ / ช่าง', 'ผู้ตรวจสอบ', 'ผู้รับมอบ (หน่วยงาน)']); ?>
</section>
<?php endif; ?>
<?php
    if ($attach) {
        foreach ($m['events'] as $e) {
            if (!$e['wr_id']) continue;
            $rec = pmr_work_record($connect, (int)$e['wr_id'], $e['freq']);
            if (!$rec) continue;
            echo '<div class="ws ws-wrap">';
            pm_render_worksheet_sheet($connect, $rec, $photos);
            echo '</div>';
            $printed++;
        }
        @ob_flush(); @flush();
    }
?>
<?php endforeach; ?>
<?php if ($machines && !$printed): ?>
<section class="page"><?php pmr_header($agency, $title, $f, $batchText); ?><div class="empty">เครื่องที่เลือกยังไม่มีใบงาน PM ที่บันทึกผลในช่วงนี้</div></section>
<?php endif; ?>
<?php endif; ?>
</div>
<script>
    if (new URLSearchParams(location.search).get('auto') === '1') window.addEventListener('load', () => setTimeout(() => window.print(), 600));
</script>
</body>
</html>
