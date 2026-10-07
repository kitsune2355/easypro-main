<?php
include 'config_ctrl/connect.php';

// รับค่าจาก URL
$meter_id = $_GET['meter_id'] ?? '';
$ag_id    = $_GET['ag_id'] ?? ''; 
$month    = $_GET['month'] ?? date('n');
$year     = $_GET['year'] ?? date('Y');
$category = $_GET['category'] ?? 'wt'; // wt, et หรือ tou

// ดึงชื่อเดือนภาษาไทย
$month_th = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
$display_date = $month_th[(int)$month] . " " . ($year + 543);

// 1. ดึงข้อมูลรายละเอียดมิเตอร์ (เพิ่ม mt_max_val)
$sql_m = "SELECT 
            m.mt_name, m.mt_limit_percent, m.mt_max_val,
            a.area_name, c.ac_name, r.ar_name
          FROM tb_meter m
          LEFT JOIN tb_area a ON m.mt_rp_area_id = a.area_id
          LEFT JOIN tb_area_class c ON m.mt_rp_ac_id = c.ac_id
          LEFT JOIN tb_area_room r ON m.mt_rp_ar_id = r.ar_id
          WHERE m.mt_id = '$meter_id'";

$res_m = mysqli_query($connect, $sql_m);
$meter_data = mysqli_fetch_assoc($res_m);
$location = implode(' - ', array_filter([$meter_data['area_name'], $meter_data['ac_name'], $meter_data['ar_name']]));
$limit_percent = isset($meter_data['mt_limit_percent']) ? floatval($meter_data['mt_limit_percent']) : 20.00;

// กำหนดค่า Max ของหน้าปัดมิเตอร์ (ถ้าไม่ระบุใน DB ให้ใช้ค่า Default 1,000,000)
$mt_max_val = (!empty($meter_data['mt_max_val']) && $meter_data['mt_max_val'] > 0) 
              ? floatval($meter_data['mt_max_val']) 
              : 1000000;

// 2. เตรียม Query สำหรับดึงประวัติการจดบันทึกของเดือนที่เลือก
if ($category === 'wt' || $category === 'et') {
    $table_name = ($category === 'wt') ? 'tb_toum_detail_wt' : 'tb_toum_detail_et';
    $prefix = ($category === 'wt') ? 'toumdt_wt' : 'toumdt_et';
    
    $sql_h = "SELECT 
                DATE(td.{$prefix}_time) as log_date,
                td.{$prefix}_mt as curr_val,
                td.{$prefix}_rm as note,
                u.user_name as recorder,
                mr.round_time as time
              FROM {$table_name} td
              LEFT JOIN tb_meter_round mr ON td.{$prefix}_round_id = mr.round_id
              LEFT JOIN tb_user u ON td.{$prefix}_user_ins = u.user_id
              WHERE td.{$prefix}_toum_id = '$meter_id'
              AND MONTH(td.{$prefix}_time) = '$month' 
              AND YEAR(td.{$prefix}_time) = '$year'
              ORDER BY td.{$prefix}_time ASC, td.{$prefix}_round_id ASC";
} elseif ($category === 'tou') {
    $sql_h = "SELECT 
                DATE(td.toudt_time) as log_date,
                mr.round_time as time,
                u.user_name as recorder,
                td.toudt_010 as total_val,
                td.toudt_011 as on_val,
                td.toudt_012 as off_val,
                td.toudt_031 as on_peak_demand,
                td.toudt_032 as off_peak_demand,
                td.toudt_071 as on_peak_reactive,
                td.toudt_072 as off_peak_reactive,
                td.toudt_note as note
              FROM tb_tou_detail td
              LEFT JOIN tb_meter_round mr ON td.toudt_round_id = mr.round_id
              LEFT JOIN tb_user u ON td.toudt_user_ins = u.user_id
              WHERE td.toudt_tou_id = '$meter_id'
              AND MONTH(td.toudt_time) = '$month' 
              AND YEAR(td.toudt_time) = '$year'
              ORDER BY td.toudt_time ASC, td.toudt_round_id ASC";
}
$res_h = mysqli_query($connect, $sql_h);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Report - <?php echo $meter_data['mt_name']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; font-size: 14px; padding: 20px; zoom:50%; }
        .report-header { display: flex; align-items: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }
        .logo { width: 100px; height: auto; }
        .title-text h2 { margin: 0; font-size: 18px; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: center; }
        th { background-color: #f2f2f2; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>

    <div class="report-header">
        <img src="../es/Logo_โปร.png" class="logo" onerror="this.style.display='none'">
        <div class="title-text" style="flex-grow:1;">
            <h2>สรุปการจดบันทึกมิเตอร์<?php echo ($category == 'wt' ? 'น้ำประปา' : 'ไฟฟ้า'); ?></h2>
            <h2>ประจำเดือน <?php echo $display_date; ?></h2>
        </div>
    </div>

    <div style="margin-bottom: 10px;">
        <strong>ชื่อมิเตอร์:</strong> <?php echo $meter_data['mt_name']; ?> | 
        <strong>สถานที่:</strong> <?php echo $location; ?> | 
        <strong style="color: #006B9F;">ค่า % ที่ยอมรับได้:</strong> <?php echo $limit_percent; ?>% |
        <strong>Max Meter:</strong> <?php echo number_format($mt_max_val); ?>
    </div>

    <table>
        <thead>
            <?php if($category !== 'tou'): ?>
                <tr>
                    <th>วันที่</th>
                    <th>เวลา</th>
                    <th>เลขหน่วยที่จดได้</th>
                    <th>หน่วยที่ใช้</th>
                    <th>% Diff</th> 
                    <th>ผู้จดบันทึก</th>
                    <th>หมายเหตุ</th> 
                </tr>
            <?php else: ?>
                <tr>
                    <th rowspan="2">วันที่</th>
                    <th rowspan="2">เวลา</th>
                    <th colspan="3">Total 010</th>
                    <th colspan="2">On Peak 011</th>
                    <th colspan="2">Off Peak 012</th>
                    <th colspan="2">ค่าความต้องการไฟฟ้าสูงสุด</th>
                    <th colspan="2">ค่าความต้องการกำลังฟ้ารีแอ็คทีฟ</th>
                    <th rowspan="2">ผู้จดบันทึก</th>
                    <th rowspan="2">หมายเหตุ</th> </tr>
                <tr>
                    <th>010</th><th>หน่วยที่ใช้</th><th>% Diff</th>
                    <th>011</th><th>หน่วยที่ใช้</th>
                    <th>012</th><th>หน่วยที่ใช้</th>
                    <th>On Peak 031</th><th>Off Peak 032</th>
                    <th>On Peak 071</th><th>Off Peak 072</th>
                </tr>
            <?php endif; ?>
        </thead>
        <tbody>
            <?php 
            // --- 🔍 ขั้นตอนสำคัญ: ดึงค่าสุดท้ายของเดือนก่อนหน้ามาเป็นค่าตั้งต้น ---
            $prev_val = 0; 
            $prev_usage = null;
            $prev_on = 0; 
            $prev_off = 0;
            $prev_total_unit = null;

            $first_day_of_month = "$year-$month-01 00:00:00";

            if ($category === 'wt' || $category === 'et') {
                $sql_prev = "SELECT {$prefix}_mt as val 
                             FROM {$table_name} 
                             WHERE {$prefix}_toum_id = '$meter_id' 
                             AND {$prefix}_time < '$first_day_of_month'
                             ORDER BY {$prefix}_time DESC, {$prefix}_round_id DESC LIMIT 1";
                $res_prev = mysqli_query($connect, $sql_prev);
                if ($res_prev && $row_prev = mysqli_fetch_assoc($res_prev)) {
                    $prev_val = floatval($row_prev['val']);
                }
            } elseif ($category === 'tou') {
                $sql_prev_tou = "SELECT toudt_011, toudt_012 
                                 FROM tb_tou_detail 
                                 WHERE toudt_tou_id = '$meter_id' 
                                 AND toudt_time < '$first_day_of_month'
                                 ORDER BY toudt_time DESC, toudt_round_id DESC LIMIT 1";
                $res_prev_tou = mysqli_query($connect, $sql_prev_tou);
                if ($res_prev_tou && $row_prev_tou = mysqli_fetch_assoc($res_prev_tou)) {
                    $prev_on = floatval($row_prev_tou['toudt_011']);
                    $prev_off = floatval($row_prev_tou['toudt_012']);
                }
            }

            // --- 2. วนลูปแสดงข้อมูล ---
            while($row = mysqli_fetch_assoc($res_h)): 
                $date_fm = date('d/m/Y', strtotime($row['log_date']));
                $time_fm = substr($row['time'], 0, 5);
            ?>
                <tr>
                    <?php if($category !== 'tou'): 
                        $curr_val = floatval($row['curr_val']);
                        
                        // 🌟 คำนวณ Usage แบบรองรับ Rollover
                        if ($curr_val < $prev_val) {
                            // กรณีมิเตอร์วนรอบ (ค่าใหม่น้อยกว่าค่าเก่า)
                            $usage = ($mt_max_val - $prev_val) + $curr_val;
                        } else {
                            // กรณีปกติ (รวมถึงกรณีค่าเท่าเดิม 12450 - 12450 = 0)
                            $usage = $curr_val - $prev_val;
                        }

                        // คำนวณ % Diff
                        $percent_diff = null;
                        $color = '';
                        if ($prev_usage !== null && $prev_usage > 0) {
                            $percent_diff = (($usage - $prev_usage) / $prev_usage) * 100;
                            if ($percent_diff > $limit_percent) $color = 'color: red; font-weight: bold;';
                            else if ($percent_diff < 0) $color = 'color: green; font-weight: bold;';
                        }
                    ?>
                        <td><?php echo $date_fm; ?></td>
                        <td><?php echo $time_fm; ?></td>
                        <td><?php echo number_format($curr_val, 2); ?></td>
                        <td><?php echo number_format($usage, 2); ?></td>
                        <td style="<?php echo $color; ?>">
                            <?php echo $percent_diff !== null ? number_format($percent_diff, 2) . '%' : '0%'; ?>
                        </td>
                        <?php 
                            $prev_val = $curr_val; 
                            $prev_usage = $usage; 
                        ?>
                    <?php else: 
                        $c_tot = floatval($row['total_val']);
                        $c_on  = floatval($row['on_val']);
                        $c_off = floatval($row['off_val']);
                        
                        // 🌟 คำนวณ On/Off แบบรองรับ Rollover
                        $u_on = ($c_on < $prev_on) ? (($mt_max_val - $prev_on) + $c_on) : ($c_on - $prev_on);
                        $u_off = ($c_off < $prev_off) ? (($mt_max_val - $prev_off) + $c_off) : ($c_off - $prev_off);
                        $u_tot = $u_on + $u_off;

                        // คำนวณ % Diff สำหรับ TOU
                        $percent_diff = null;
                        $color = '';
                        if ($prev_total_unit !== null && $prev_total_unit > 0) {
                            $percent_diff = (($u_tot - $prev_total_unit) / $prev_total_unit) * 100;
                            if ($percent_diff > $limit_percent) $color = 'color: red; font-weight: bold;';
                            else if ($percent_diff < 0) $color = 'color: green; font-weight: bold;';
                        }
                    ?>
                        <td><?php echo $date_fm; ?></td>
                        <td><?php echo $time_fm; ?></td>
                        <td><?php echo number_format($c_tot, 2); ?></td>
                        <td><?php echo number_format($u_tot, 2); ?></td>
                        <td style="<?php echo $color; ?>">
                            <?php echo $percent_diff !== null ? number_format($percent_diff, 2) . '%' : '0%'; ?>
                        </td>
                        <td><?php echo number_format($c_on, 2); ?></td><td><?php echo number_format($u_on, 2); ?></td>
                        <td><?php echo number_format($c_off, 2); ?></td><td><?php echo number_format($u_off, 2); ?></td>

                        <td><?php echo number_format($row['on_peak_demand'], 2); ?></td>
                        <td><?php echo number_format($row['off_peak_demand'], 2); ?></td>
                        
                        <td><?php echo number_format($row['on_peak_reactive'], 2); ?></td>
                        <td><?php echo number_format($row['off_peak_reactive'], 2); ?></td>
                        <?php 
                            $prev_on = $c_on; 
                            $prev_off = $c_off; 
                            $prev_total_unit = $u_tot;
                        ?>
                    <?php endif; ?>
                    
                    <td><?php echo $row['recorder']; ?></td>
                    <td><?php echo $row['note']; ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <table border="1" width="100%" style="font-size:12px; margin-top:20px; border-collapse:collapse;"> 
        <tbody>
            <tr style="background-color: #f2f2f2;">
                <td colspan="2" style="padding-left: 10px;"><strong>Approved By Site Engineer / Supervisor</strong></td>
                <td colspan="2" style="padding-left: 10px;"><strong>Approved By Customer</strong></td>
                <td colspan="2" style="padding-left: 10px;"><strong>Symbol for Shift & Day</strong></td>
            </tr>
            <tr>
                <td width="80" align="left">&nbsp;Signature :&nbsp;</td>
                <td width="300">&nbsp;</td>
                <td width="80" align="left">&nbsp;Signature :&nbsp;</td>
                <td width="300">&nbsp;</td>
                <td align="left" style="padding-left: 5px;">&nbsp;M = Morning : 07:00 - 16:00&nbsp;</td>
                <td align="left" style="padding-left: 5px;">&nbsp;D = Day : 08:00 - 17:00&nbsp;</td>
            </tr>
            <tr>
                <td align="left">&nbsp;Date :&nbsp;</td>
                <td>&nbsp;</td>
                <td align="left">&nbsp;Date :&nbsp;</td>
                <td>&nbsp;</td>
                <td align="left" style="padding-left: 5px;">&nbsp;A = Afternoon : 14:00 - 23:00&nbsp;</td>
                <td align="left" style="padding-left: 5px;">&nbsp;N = Night : 23:00 - 08:00&nbsp;</td>
            </tr>
        </tbody>
    </table>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>