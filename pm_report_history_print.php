<?php
// หน้าพิมพ์ PM History — 1 แถวต่อใบงาน (A4 แนวนอน) เปิดจากแท็บ "รายงาน PM"
//  from, to, type, cs, m = ตัวกรอง · off/lim = แบ่งไฟล์ตามจำนวนใบงาน
@session_start();
include "config_ctrl/checksession.php";
include "config_ctrl/connect.php";
date_default_timezone_set('Asia/Bangkok');
mysqli_set_charset($connect, 'utf8mb4');
ini_set('memory_limit', '1024M');
set_time_limit(600);
require_once __DIR__ . '/config_ctrl/pm_report_data.php';
require_once __DIR__ . '/config_ctrl/agency_logo.php';

$ag = isset($sess_user_agency_es) ? trim((string)$sess_user_agency_es) : '';
if ($ag === '') die('ไม่พบหน่วยงาน กรุณาเข้าสู่ระบบใหม่');
if (!pmr_can_manage($connect)) die('ไม่มีสิทธิ์ใช้งานรายงาน PM');

$f = pmr_filters($_REQUEST);
$off = max(0, (int)($_REQUEST['off'] ?? 0));
$lim = max(0, (int)($_REQUEST['lim'] ?? 0));
$agency = pmr_agency($connect, $ag);
$all = pmr_history($connect, $ag, $f);
$total = count($all);
$rows = $lim > 0 ? array_slice($all, $off, $lim) : $all;
$part = ($lim > 0 && $lim < $total) ? 'ใบงานลำดับที่ ' . number_format($off + 1) . '–' . number_format(min($off + $lim, $total)) . ' จาก ' . number_format($total) : '';

// สรุปของไฟล์นี้
$S = ['n' => count($rows), 'ontime' => 0, 'late' => 0, 'fail' => 0, 'failJobs' => 0, 'spare' => 0.0];
foreach ($rows as $r) {
    $r['late'] ? $S['late']++ : $S['ontime']++;
    $S['fail'] += $r['fail'];
    if ($r['fail']) $S['failJobs']++;
    $S['spare'] += $r['spare'];
}
$monthName = function ($d) {
    $m = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    return $m[(int)substr($d, 5, 2)] . ' ' . ((int)substr($d, 0, 4) + 543);
};
$monthCount = [];
foreach ($rows as $r) { $k = substr($r['plan_date'], 0, 7); $monthCount[$k] = ($monthCount[$k] ?? 0) + 1; }
$multiMonth = count($monthCount) > 1;
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PM History - <?php echo pmr_h($agency['ag_contract']); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<style>
    :root { --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --brand: #006B9F; --head: #eaf4fb; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #e5e7eb; color: var(--ink); font-family: "Sarabun", Tahoma, sans-serif; font-size: 9.5px; }
    .bar { position: sticky; top: 0; z-index: 50; background: #0f172a; color: #fff; padding: 10px 16px; display: flex; gap: 12px; align-items: center; justify-content: space-between; font-family: Tahoma, sans-serif; }
    .bar b { font-size: 14px; }
    .bar .info { font-size: 12px; color: #cbd5e1; }
    .bar button { border: 0; border-radius: 6px; padding: 8px 14px; cursor: pointer; font-size: 13px; color: #fff; background: #0284c7; }
    .bar button.sec { background: #334155; }
    .sheet { width: 297mm; margin: 16px auto; background: #fff; padding: 9mm 9mm 10mm; box-shadow: 0 1px 4px rgba(0,0,0,.15); }

    .head { display: flex; align-items: center; gap: 12px; border-bottom: 2px solid var(--brand); padding-bottom: 7px; }
    .head img { width: 92px; height: 34px; object-fit: contain; }
    .head img.ag-logo { width: auto; max-width: 120px; }
    .head .main { flex: 1; }
    .head h1 { margin: 0; font-size: 17px; color: var(--brand); line-height: 1.2; }
    .head h1 small { font-size: 11px; color: var(--muted); font-weight: 600; }
    .head .ag { font-size: 12px; font-weight: 700; margin-top: 1px; }
    .head .side { text-align: right; font-size: 10px; line-height: 1.6; color: #334155; }

    .stats { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px; margin: 8px 0 10px; }
    .stat { border: 1px solid var(--line); border-radius: 6px; padding: 5px 8px; }
    .stat .v { font-size: 15px; font-weight: 700; line-height: 1.2; }
    .stat .l { font-size: 9px; color: var(--muted); }

    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    thead { display: table-header-group; }
    th { background: var(--head); color: #0f172a; font-weight: 700; text-align: left; padding: 5px 5px; border-bottom: 1.5px solid var(--brand); font-size: 9px; line-height: 1.25; vertical-align: bottom; }
    td { padding: 5px 5px; border-bottom: 1px solid var(--line); vertical-align: top; line-height: 1.35; overflow-wrap: anywhere; }
    tbody tr:nth-child(even) td { background: #fafcfe; }
    tr { break-inside: avoid; }
    .c { text-align: center; } .r { text-align: right; } .nw { white-space: nowrap; }
    .b { font-weight: 700; } .muted { color: var(--muted); } .sm { font-size: 8.5px; }
    .tag { display: inline-block; border-radius: 4px; padding: 1px 5px; font-size: 8.5px; font-weight: 700; white-space: nowrap; }
    .tag.ok { background: #dcfce7; color: #166534; } .tag.late { background: #fef3c7; color: #92400e; }
    .fail { color: #b91c1c; font-weight: 700; }
    .pbar { height: 4px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-top: 3px; }
    .pbar span { display: block; height: 100%; background: #10b981; }
    .pbar.bad span { background: #ef4444; }
    tr.month td { background: #f1f5f9 !important; font-weight: 700; color: #334155; padding: 4px 6px; border-bottom: 1px solid #cbd5e1; }
    tfoot td { font-weight: 700; border-top: 1.5px solid var(--brand); border-bottom: 0; background: #fff; }

    .signs { display: flex; gap: 18px; margin-top: 26px; break-inside: avoid; }
    .sign { flex: 1; text-align: center; font-size: 10px; line-height: 1.7; }
    .sign .ln { height: 30px; }

    /* ---------- หน้าจอ: แสดงเป็นแผ่น A4 แนวนอนเหมือนตอนพิมพ์ — จอที่แคบกว่า A4 ปรับเต็มความกว้าง ตารางเลื่อนซ้าย-ขวาได้ ---------- */
    @media screen { .sheet { min-height: 210mm; border-radius: 2px; } }
    @media screen and (max-width: 1180px) {
        .sheet { width: auto; min-height: 0; margin: 12px; }
        .tw { overflow-x: auto; -webkit-overflow-scrolling: touch; } .tw table { min-width: 1060px; }
    }
    @media screen and (max-width: 640px) {
        .bar { flex-wrap: wrap; padding: 8px 12px; } .bar b { font-size: 13px; } .bar .info { font-size: 11px; }
        .bar button { padding: 7px 10px; font-size: 12px; }
        .sheet { margin: 8px; padding: 14px 12px 18px; border-radius: 10px; }
        .head { flex-wrap: wrap; } .head .side { text-align: left; width: 100%; } .head h1 { font-size: 16px; }
        .stats { grid-template-columns: repeat(2, 1fr); }
        .signs { flex-direction: column; gap: 4px; margin-top: 18px; }
    }
    @page { size: A4 landscape; margin: 7mm; @bottom-right { content: "หน้า " counter(page) " / " counter(pages); font-size: 8px; color: #64748b; } }
    @media print {
        body { background: #fff; }
        .bar { display: none; }
        .sheet { width: auto; margin: 0; padding: 0; box-shadow: none; }
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
</head>
<body>
<div class="bar">
    <div><b>PM History</b><div class="info"><?php echo pmr_h($agency['ag_contract'] . ' · ' . pmr_period_text($f) . ($part ? ' · ' . $part : ' · ' . number_format($total) . ' ใบงาน')); ?></div></div>
    <div style="display:flex;gap:8px">
        <button type="button" onclick="window.print()">พิมพ์ / Save PDF</button>
        <button type="button" class="sec" onclick="window.close()">ปิดหน้าต่าง</button>
    </div>
</div>
<div class="sheet">
    <header class="head">
        <img src="Logo_โปร.png" alt="">
        <?php echo agl_img($connect, $ag, 'class="ag-logo"'); ?>
        <div class="main">
            <h1>ประวัติการบำรุงรักษา (PM History) <small>· รายงานใบงาน PM ที่ดำเนินการแล้ว</small></h1>
            <div class="ag"><?php echo pmr_h($agency['ag_contract']); ?><?php if ($agency['ag_job']): ?> <span class="muted">(<?php echo pmr_h($agency['ag_job']); ?>)</span><?php endif; ?></div>
        </div>
        <div class="side">
            <b>ช่วงแผน PM</b> <?php echo pmr_h(pmr_period_text($f)); ?><br>
            <?php if ($part): ?><?php echo pmr_h($part); ?><br><?php endif; ?>
            <span class="muted">พิมพ์เมื่อ <?php echo pmr_h(pmr_thai_date(date('Y-m-d'))) . ' ' . date('H:i'); ?> น.</span>
        </div>
    </header>

    <div class="stats">
        <div class="stat"><div class="v"><?php echo number_format($S['n']); ?></div><div class="l">ใบงาน PM ที่ดำเนินการแล้ว</div></div>
        <div class="stat"><div class="v" style="color:#166534"><?php echo number_format($S['ontime']); ?></div><div class="l">ทำตามแผน</div></div>
        <div class="stat"><div class="v" style="color:#92400e"><?php echo number_format($S['late']); ?></div><div class="l">ทำล่าช้ากว่าแผน</div></div>
        <div class="stat"><div class="v" style="color:<?php echo $S['fail'] ? '#b91c1c' : 'inherit'; ?>"><?php echo number_format($S['fail']); ?></div><div class="l">จุดตรวจไม่ผ่าน (<?php echo number_format($S['failJobs']); ?> ใบงาน)</div></div>
        <div class="stat"><div class="v"><?php echo number_format($S['spare'], 2); ?></div><div class="l">ค่าอะไหล่ที่ใช้ (บาท)</div></div>
    </div>

    <div class="tw"><table>
        <colgroup>
            <col style="width:3%"><col style="width:10%"><col style="width:7.5%"><col style="width:8%"><col style="width:15%">
            <col style="width:11.5%"><col style="width:13%"><col style="width:8.5%"><col style="width:8%"><col style="width:6%"><col>
        </colgroup>
        <thead>
            <tr>
                <th class="c">#</th>
                <th>เลขที่ใบงาน<div class="muted sm">วันที่ตามแผน</div></th>
                <th>วันที่เข้าทำจริง</th>
                <th>สถานะ</th>
                <th>เครื่อง / อุปกรณ์<div class="muted sm">รหัส · ประเภท · S/N</div></th>
                <th>ตำแหน่งที่ตั้ง</th>
                <th>เช็คชีต<div class="muted sm">ความถี่</div></th>
                <th>ผู้ดำเนินการ</th>
                <th>ผลการตรวจ</th>
                <th class="r">อะไหล่ (บาท)</th>
                <th>ผลการปฏิบัติงาน / ข้อเสนอแนะ</th>
            </tr>
        </thead>
        <tbody>
        <?php $curMonth = ''; foreach ($rows as $i => $r): $mk = substr($r['plan_date'], 0, 7); ?>
            <?php if ($multiMonth && $mk !== $curMonth): $curMonth = $mk; ?>
            <tr class="month"><td colspan="11">แผนเดือน<?php echo pmr_h($monthName($r['plan_date'])); ?> · <?php echo number_format($monthCount[$mk]); ?> ใบงาน</td></tr>
            <?php endif; ?>
            <tr>
                <td class="c muted"><?php echo number_format($off + $i + 1); ?></td>
                <td><span class="b"><?php echo pmr_h($r['doc']); ?></span><div class="muted"><?php echo pmr_h(pmr_thai_date($r['plan_date'])); ?></div></td>
                <td class="nw"><?php echo pmr_h(pmr_thai_date($r['done_date'])); ?><div class="muted"><?php echo pmr_h($r['done_time'] ? $r['done_time'] . ' น.' : ''); ?></div></td>
                <td><?php if ($r['late']): ?><span class="tag late">ล่าช้า <?php echo (int)$r['delay']; ?> วัน</span><?php else: ?><span class="tag ok">ตามแผน</span><?php endif; ?></td>
                <td><span class="b"><?php echo pmr_h($r['code'] ?: '-'); ?></span> <?php echo pmr_h($r['name']); ?>
                    <div class="muted sm"><?php echo pmr_h(implode(' · ', array_filter([$r['type'], $r['serial'] ? 'S/N ' . $r['serial'] : '']))); ?></div></td>
                <td><?php echo pmr_h($r['location'] ?: '-'); ?></td>
                <td><?php echo pmr_h($r['checksheet'] ?: '-'); ?><?php if ($r['freq']): ?><div class="muted sm"><?php echo pmr_h($r['freq']); ?></div><?php endif; ?></td>
                <td><?php echo pmr_h($r['inspector'] ?: '-'); ?></td>
                <td>
                    ผ่าน <?php echo $r['pass']; ?><?php if ($r['fail']): ?> · <span class="fail">ไม่ผ่าน <?php echo $r['fail']; ?></span><?php endif; ?><?php if ($r['na']): ?> <span class="muted">· N/A <?php echo $r['na']; ?></span><?php endif; ?>
                    <?php if ($r['pass_pct'] !== null): ?><div class="pbar <?php echo $r['fail'] ? 'bad' : ''; ?>"><span style="width:<?php echo (int)$r['pass_pct']; ?>%"></span></div><div class="muted sm"><?php echo (int)$r['pass_pct']; ?>% ผ่าน</div><?php endif; ?>
                </td>
                <td class="r"><?php echo $r['spare'] > 0 ? number_format($r['spare'], 2) : '<span class="muted">-</span>'; ?></td>
                <td><?php echo $r['remarks'] ? nl2br(pmr_h($r['remarks'])) : '<span class="muted">-</span>'; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="11" class="c muted" style="padding:18px">ไม่มีใบงาน PM ที่ดำเนินการแล้วในช่วงนี้</td></tr><?php endif; ?>
        </tbody>
        <?php if ($rows): ?>
        <tfoot><tr><td colspan="9" class="r">รวม <?php echo number_format($S['n']); ?> ใบงาน · ค่าอะไหล่</td><td class="r"><?php echo number_format($S['spare'], 2); ?></td><td></td></tr></tfoot>
        <?php endif; ?>
    </table></div>

    <?php if ($rows && (!$lim || $off + $lim >= $total)): ?>
    <div class="signs">
        <?php foreach (['ผู้จัดทำรายงาน', 'ผู้ตรวจสอบ (หัวหน้างาน/วิศวกร)', 'ผู้รับมอบ (ตัวแทนหน่วยงาน)'] as $l): ?>
        <div class="sign"><div class="ln"></div>(.......................................................)<div class="b"><?php echo pmr_h($l); ?></div><div class="muted">วันที่ ........./........./.............</div></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<script>
    if (new URLSearchParams(location.search).get('auto') === '1') window.addEventListener('load', () => setTimeout(() => window.print(), 600));
</script>
</body>
</html>
