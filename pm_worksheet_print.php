<?php
@session_start();
include "config_ctrl/checksession.php";
include "config_ctrl/connect.php";

date_default_timezone_set('Asia/Bangkok');

$plan_id = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0;
$work_record_id = isset($_GET['work_record_id']) ? intval($_GET['work_record_id']) : 0;

$ag_id = isset($sess_user_agency_es) ? trim((string)$sess_user_agency_es) : '';

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function thaiDate($date) {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '-';
    }

    $ts = strtotime($date);
    if (!$ts) {
        return $date;
    }

    $months = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];

    $d = date('j', $ts);
    $m = $months[(int)date('n', $ts)];
    $y = date('Y', $ts) + 543;

    return $d . ' ' . $m . ' ' . $y;
}

function recordMark($status) {
    $status = trim((string)$status);
    if ($status === 'Pass') return '/';
    if ($status === 'Fail') return 'X';
    if ($status === 'N/A') return '-';
    return $status !== '' ? $status : '-';
}

function statusClass($status) {
    if ($status === 'Fail') return 'text-[#c00000] font-bold';
    if ($status === 'Pass') return 'text-black font-bold';
    return '';
}

function valueText($value, $unit = '') {
    $value = trim((string)$value);
    $unit = trim((string)$unit);
    if ($value === '' || $value === '-') {
        return '';
    }
    return $value . ($unit !== '' ? ' ' . $unit : '');
}

if ($plan_id <= 0 && $work_record_id <= 0) {
    die('ไม่พบรหัสแผนงานหรือรหัสประวัติ PM');
}

$where = "1=1";

if ($work_record_id > 0) {
    $where .= " AND pm_work_records.id = " . $work_record_id;
} else {
    $where .= " AND pm_work_records.plan_event_id = " . $plan_id;
}

if ($ag_id !== '') {
    $ag_id_db = mysqli_real_escape_string($connect, $ag_id);
    $where .= " AND pm_work_records.ag_id = '$ag_id_db'";
}

// ปรับ SQL ให้ Join ตาราง pm_plans และ pm_freq_options
// ใช้เฉพาะชื่อฟิลด์ที่ต้องการเพื่อไม่ให้ id ชนกัน
$sql_record = "SELECT pm_work_records.*, tb_agency.ag_contract, tb_agency.ag_located, pm_freq_options.description AS freq_description 
               FROM pm_work_records 
               LEFT JOIN tb_agency ON pm_work_records.ag_id = tb_agency.ag_id 
               LEFT JOIN pm_plans ON pm_work_records.checksheet_id = pm_plans.checksheet_id
               LEFT JOIN pm_freq_options ON pm_plans.frequency = pm_freq_options.freq_value
               WHERE $where LIMIT 1";
$res_record = mysqli_query($connect, $sql_record);

if (!$res_record) {
    die('SQL Error Record: ' . mysqli_error($connect));
}

$record = mysqli_fetch_assoc($res_record);

if (!$record) {
    die('ไม่พบประวัติ PM ที่บันทึกแล้ว');
}

$real_work_record_id = intval($record['id']);
$real_plan_event_id = intval($record['plan_event_id']);

$items = [];
$sql_items = "SELECT * FROM pm_work_record_items WHERE work_record_id = $real_work_record_id ORDER BY sort_order ASC, id ASC";
$res_items = mysqli_query($connect, $sql_items);
if ($res_items) {
    while ($row = mysqli_fetch_assoc($res_items)) {
        $items[] = $row;
    }
}

$inspectors = [];
$sql_inspectors = "SELECT * FROM pm_work_record_inspectors WHERE work_record_id = $real_work_record_id ORDER BY id ASC";
$res_inspectors = mysqli_query($connect, $sql_inspectors);
if ($res_inspectors) {
    while ($row = mysqli_fetch_assoc($res_inspectors)) {
        $inspectors[] = $row;
    }
}

$actual_spares = [];
$sql_actual_spares = "SELECT * FROM pm_work_record_actual_spares WHERE work_record_id = $real_work_record_id ORDER BY id ASC";
$res_actual_spares = mysqli_query($connect, $sql_actual_spares);
if ($res_actual_spares) {
    while ($row = mysqli_fetch_assoc($res_actual_spares)) {
        $actual_spares[] = $row;
    }
}

$prepared_spares = [];
$sql_prepared_spares = "SELECT * FROM pm_work_record_spares_snapshot WHERE work_record_id = $real_work_record_id ORDER BY id ASC";
$res_prepared_spares = mysqli_query($connect, $sql_prepared_spares);
if ($res_prepared_spares) {
    while ($row = mysqli_fetch_assoc($res_prepared_spares)) {
        $prepared_spares[] = $row;
    }
}

$inspector_names = [];
foreach ($inspectors as $inspector) {
    if (!empty($inspector['inspector_name_snapshot'])) {
        $inspector_names[] = $inspector['inspector_name_snapshot'];
    }
}
if (empty($inspector_names) && !empty($record['inspector_name'])) {
    $inspector_names[] = $record['inspector_name'];
}
$inspector_name_text = !empty($inspector_names) ? implode(', ', $inspector_names) : '-';

$total_rows_target = 24;
$current_rows = count($items);
$empty_rows = max(0, $total_rows_target - $current_rows);

$doc_no = $record['doc_no'] ?? '-';
$checksheet_name = $record['checksheet_name'] ?? '-';
$machine_name = $record['machine_name'] ?? '-';
$machine_code = $record['machine_code'] ?? '-';
$machine_sn = $record['machine_sn'] ?? '-';
$machine_type = $record['machine_type'] ?? '-';
$location = $record['location'] ?? '-';
$ag_contract = $record['ag_contract'] ?? '-';
$ag_located = $record['ag_located'] ?? '-';
$freq_description = $record['freq_description'] ?? '-'; 
$plan_date = thaiDate($record['plan_date'] ?? '');
$actual_date = thaiDate($record['actual_date'] ?? '');
$remarks = $record['remarks'] ?? '-';
$signature_path = $record['inspector_signature_path'] ?? '';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>PM Print - <?php echo h($doc_no); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style type="text/tailwindcss">
        @layer base {
            body { 
                font-family: Arial, "TH Sarabun New", Tahoma, sans-serif; 
            }
            table { 
                @apply w-full border-collapse table-fixed; 
            }
            td, th { 
                @apply border border-black px-1 py-[2px] align-middle text-[8px] leading-[1.25]; 
            }
        }
        @media print {
            @page {
                size: A4 portrait;
                margin: 6mm;
            }
            body {
                @apply bg-white;
            }
        }
    </style>
</head>

<body class="m-0 bg-[#e5e7eb] text-black">

<div class="sticky top-0 z-50 bg-[#0f172a] text-white px-4 py-2.5 flex justify-between items-center font-sans print:hidden">
    <div class="text-[14px] font-bold">พิมพ์ใบงาน PM</div>
    <div class="flex gap-2">
        <button type="button" class="border-0 rounded-md px-3.5 py-2 cursor-pointer text-[13px] bg-[#0284c7] text-white hover:bg-[#0369a1] transition" onclick="window.print()">พิมพ์ / Save PDF</button>
        <button type="button" class="border-0 rounded-md px-3.5 py-2 cursor-pointer text-[13px] bg-[#334155] text-white hover:bg-[#1e293b] transition" onclick="window.close()">ปิดหน้าต่าง</button>
    </div>
</div>

<div class="p-4 print:p-0">

    <div class="w-[210mm] min-h-[297mm] bg-white mx-auto p-[5mm] border border-black print:w-full print:min-h-0 print:m-0 print:border-0 print:p-0 print:break-after-always">

        <div class="h-[25px] text-center text-[11px] font-bold relative border border-black border-b-0 leading-[24px]">
            MAINTENANCE TASKS REPORT
            <img src="Logo_โปร.png" class="absolute right-[6px] top-[2px] w-[95px] h-[20px] object-contain" alt="PROACTIVE">
        </div>

        <div class="bg-[#bdd7ee] text-center text-[10px] font-bold h-[20px] leading-[20px] border border-black border-b-0">
            <?php echo h(strtoupper($machine_name)); ?>
        </div>

        <table>
            <tr>
                <td class="w-[16%]"><div class="text-[6.5px] uppercase">PROJECT TITLE :</div></td>
                <td class="w-[36%]"><div class="text-[8px] min-h-[12px]"><?php echo h($checksheet_name); ?></div></td>
                <td class="w-[12%]"><div class="text-[6.5px] uppercase">DATE :</div></td>
                <td class="w-[16%]"><div class="text-[8px] min-h-[12px]"><?php echo h($actual_date); ?></div></td>
                <td class="w-[10%]"><div class="text-[6.5px] uppercase">DOC NO :</div></td>
                <td class="w-[10%]"><div class="text-[8px] min-h-[12px]"><?php echo h($doc_no); ?></div></td>
            </tr>
            <tr>
                <td><div class="text-[6.5px] uppercase">ADDRESS :</div></td>
                <td><div class="text-[8px] min-h-[12px]"><?php echo 'หน่วยงาน: ' . h($ag_contract) . ', ' . h($ag_located); ?></div></td>
                <td><div class="text-[6.5px] uppercase">LOCATION :</div></td>
                <td colspan="3"><div class="text-[8px] min-h-[12px]"><?php echo h($location); ?></div></td>
            </tr>
            <tr>
                <td colspan="2" rowspan="3" class="text-[#335eea] text-center text-[10px]">
                    <?php echo h($machine_name); ?>
                </td>
                <td><div class="text-[6.5px] uppercase">EQUIPMENT CODE :</div></td>
                <td colspan="3"><div class="text-[8px] min-h-[12px]"><?php echo h($machine_code); ?></div></td>
            </tr>
            <tr>
                <td><div class="text-[6.5px] uppercase">BRAND / MODEL :</div></td>
                <td><div class="text-[8px] min-h-[12px]">-</div></td>
                <td><div class="text-[6.5px] uppercase">S/N :</div></td>
                <td><div class="text-[8px] min-h-[12px]"><?php echo h($machine_sn); ?></div></td>
            </tr>
            <tr>
                <td><div class="text-[6.5px] uppercase">PERIOD :</div></td>
                <td><div class="text-[8px] min-h-[12px]"><?php echo h($freq_description); ?></div></td>
                <td><div class="text-[6.5px] uppercase">SYSTEM :</div></td>
                <td><div class="text-[8px] min-h-[12px]"><?php echo h($machine_type); ?></div></td>
            </tr>
        </table>

        <table>
            <colgroup>
                <col class="w-[16px]">
                <col class="w-full">
                <col class="w-[92px]">
                <col class="w-[156px]">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="2" class="bg-[#bdd7ee] text-center font-bold h-[18px]">TASKS</th>
                    <th class="bg-[#bdd7ee] text-center font-bold h-[18px]">STANDARD</th>
                    <th class="bg-[#bdd7ee] text-center font-bold h-[18px]">RECORD</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-[#a6a6a6] font-bold">
                    <td class="text-center"></td>
                    <td colspan="3">MONTHLY P.M. / MAINTENANCE TASKS</td>
                </tr>

                <?php foreach ($items as $index => $item): ?>
                    <?php
                        $status = $item['result_status'] ?? '';
                        $mark = recordMark($status);
                        $class = statusClass($status);

                        $actual_value = valueText($item['actual_value'] ?? '', $item['unit'] ?? '');
                        $standard = '-';

                        if (!empty($item['expected_value'])) {
                            $standard = $item['expected_value'];
                            if (!empty($item['unit'])) {
                                $standard .= ' ' . $item['unit'];
                            }
                        } elseif (!empty($item['value_name'])) {
                            $standard = $item['value_name'];
                        }
                    ?>
                    <tr>
                        <td class="text-center h-[17px]"><?php echo $index + 1; ?></td>
                        <td class="h-[17px]"><?php echo h($item['check_point'] ?? '-'); ?></td>
                        <td class="text-center h-[17px]"><?php echo h($standard); ?></td>
                        <td class="p-0 h-[17px]">
                            <div class="grid grid-cols-2 h-full min-h-[13px] divide-x divide-black">
                                <div class="flex items-center justify-center text-[8px] min-h-[13px]">
                                    <?php echo h($actual_value); ?>
                                </div>
                                <div class="flex items-center justify-center text-[8px] min-h-[13px] <?php echo $class; ?>">
                                    <?php echo h($status); ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php for ($i = 0; $i < $empty_rows; $i++): ?>
                    <tr>
                        <td class="text-center h-[17px]">&nbsp;</td>
                        <td class="h-[17px]">&nbsp;</td>
                        <td class="text-center h-[17px]">&nbsp;</td>
                        <td class="p-0 h-[17px]">
                            <div class="grid grid-cols-2 h-full min-h-[13px] divide-x divide-black">
                                <div>&nbsp;</div>
                                <div>&nbsp;</div>
                            </div>
                        </td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <div class="border-x border-black min-h-[52px]">
            <div class="text-[7px] px-1 py-[3px] font-bold">RECOMMENDATIONS / REMARKS</div>
            <div class="text-[7px] px-1 py-[1px]"><?php echo nl2br(h($remarks)); ?></div>
        </div>

        <div class="border-x border-t border-black bg-[#d9d9d9] grid grid-cols-[70px_1fr] items-center text-[7.5px] py-[4px] px-1 leading-[1.5]">
            <div></div>
            <div>1.) Make Sure Disconnect Power Before Touching Any Electrical Parts./ ต้องมั่นใจว่าได้ตัดกระแสไฟฟ้าแล้ว</div>
            
            <div class="font-bold">SAFETY NOTE :</div>
            <div>2.) Make Sure To Show Warning Sign At Control Panel./ ต้องแน่ใจว่าได้มีการติดป้ายเตือนบริเวณตู้ควบคุมต่างๆที่ดำเนินการ</div>
            
            <div></div>
            <div>3.) Make sure that after the operation. System in the status. Work as normal./ ต้องแน่ใจว่าระบบอยู่ในสภาวะปกติ หลังจากดำเนินการข้างต้น</div>
        </div>

        <?php if (!empty($prepared_spares) || !empty($actual_spares)): ?>
            <div class="mt-[6px] break-inside-avoid">
                <table>
                    <colgroup>
                        <col class="w-[40px]">
                        <col class="w-full">
                        <col class="w-[80px]">
                        <col class="w-[80px]">
                        <col class="w-[90px]">
                        <col class="w-[90px]">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="bg-[#bdd7ee] text-center font-bold">NO.</th>
                            <th class="bg-[#bdd7ee] text-center font-bold">SPARE PART / MATERIAL</th>
                            <th class="bg-[#bdd7ee] text-center font-bold">QTY</th>
                            <th class="bg-[#bdd7ee] text-center font-bold">UNIT</th>
                            <th class="bg-[#bdd7ee] text-center font-bold">PRICE</th>
                            <th class="bg-[#bdd7ee] text-center font-bold">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $spare_no = 1; ?>
                        <?php foreach ($prepared_spares as $sp): ?>
                            <tr>
                                <td class="text-center"><?php echo $spare_no++; ?></td>
                                <td><?php echo h($sp['part_name'] ?? '-'); ?></td>
                                <td class="text-center"><?php echo h($sp['quantity'] ?? '-'); ?></td>
                                <td class="text-center">-</td>
                                <td class="text-right">-</td>
                                <td class="text-right">-</td>
                            </tr>
                        <?php endforeach; ?>
                        <?php foreach ($actual_spares as $sp): ?>
                            <tr>
                                <td class="text-center"><?php echo $spare_no++; ?></td>
                                <td>
                                    <?php echo h($sp['rpd_details_head'] ?? '-'); ?>
                                    <div class="text-[6.5px] text-[#333]"><?php echo h($sp['pd_gen_code'] ?? ''); ?></div>
                                </td>
                                <td class="text-center"><?php echo h($sp['rpd_qty'] ?? '-'); ?></td>
                                <td class="text-center"><?php echo h($sp['pd_unit'] ?? '-'); ?></td>
                                <td class="text-right"><?php echo number_format((float)($sp['rpd_price'] ?? 0), 2); ?></td>
                                <td class="text-right"><?php echo number_format((float)($sp['rpd_sum_money'] ?? 0), 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <table>
            <tr>
                <td class="h-[92px] align-top text-center text-[8px] w-1/3">
                    <div class="font-bold text-[10px] mb-1">Done By / ดำเนินการโดย</div>
                    <div class="text-[7px] mb-1">Signature/ลงนาม (Tech./ช่าง)</div>

                    <?php if (!empty($signature_path)): ?>
                        <img src="<?php echo h($signature_path); ?>" class="block max-w-[120px] max-h-[36px] object-contain mx-auto my-[2px]">
                    <?php else: ?>
                        <div class="border-b border-dotted border-black h-[14px] mx-[18px] my-[2px]"></div>
                    <?php endif; ?>

                    <div><?php echo h($inspector_name_text); ?></div>
                    <div class="mt-1">Date/วันที่ <?php echo h($actual_date); ?></div>
                </td>

                <td class="h-[92px] align-top text-center text-[8px] w-1/3">
                    <div class="font-bold text-[10px] mb-1">Checked By / ตรวจสอบโดย</div>
                    <div class="text-[7px] mb-1">Signature/ลงนาม (Engineer / Supervisor)</div>
                    <div class="border-b border-dotted border-black h-[14px] mx-[18px] my-[2px]"></div>
                    <div class="mt-[18px]">Date/วันที่ ______________________</div>
                </td>

                <td class="h-[92px] align-top text-center text-[8px] w-1/3">
                    <div class="font-bold text-[10px] mb-1">Verified By / ทวนสอบโดย</div>
                    <div class="text-[7px] mb-1">Signature/ลงนาม (B.M./ผู้จัดการอาคาร)</div>
                    <div class="border-b border-dotted border-black h-[14px] mx-[18px] my-[2px]"></div>
                    <div class="mt-1">Date/วันที่ ______________________</div>

                    <div class="bg-[#bdd7ee] border border-black mx-[20px] my-[4px] mt-[6px] p-[3px] font-normal">CUSTOMER'S ACCEPTANCE</div>
                    <div class="border-b border-dotted border-black h-[14px] mx-[18px] my-[2px]"></div>
                    <div>Date/วันที่ ______________________</div>
                </td>
            </tr>
        </table>

    </div>

    <?php
        $photo_items = array_filter($items, function($it) {
            return !empty($it['result_photo_path']);
        });
    ?>
    <?php if (!empty($photo_items)): ?>
        <div class="w-[210mm] min-h-[297mm] bg-white mt-4 mx-auto p-[8mm] border border-black print:w-full print:min-h-0 print:m-0 print:border-0 print:p-0 print:break-after-always">
            <div class="font-bold text-[13px] text-center mb-[10px]">
                RESULT PHOTO ATTACHMENTS / รูปภาพหลักฐานการตรวจสอบ
            </div>

            <div class="grid grid-cols-2 gap-[10px]">
                <?php foreach ($photo_items as $index => $item): ?>
                    <div class="border border-black p-[6px] min-h-[95mm] break-inside-avoid">
                        <div class="text-[9px] font-bold mb-[5px]">
                            <?php echo ($index + 1) . '. ' . h($item['check_point'] ?? '-'); ?>
                        </div>
                        <img src="<?php echo h($item['result_photo_path']); ?>" class="w-full max-h-[82mm] object-contain block border border-[#ccc]">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
    const AUTO_PRINT = new URLSearchParams(window.location.search).get('auto') === '1';

    if (AUTO_PRINT) {
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 500);
        });
    }
</script>

</body>
</html>