<?php
@session_start();
include "config_ctrl/checksession.php";
include "config_ctrl/connect.php";

date_default_timezone_set('Asia/Bangkok');

$plan_id = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0;
$work_record_id = isset($_GET['work_record_id']) ? intval($_GET['work_record_id']) : 0;

$ag_id = isset($sess_user_agency_es) ? trim((string)$sess_user_agency_es) : '';

require_once __DIR__ . '/pm_worksheet_sheet.php';


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

$doc_no = $record['doc_no'] ?? '-';
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

    <?php pm_render_worksheet_sheet($connect, $record, true); ?>

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