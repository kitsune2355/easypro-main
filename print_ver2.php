<style>
@media print {
  @page {
    margin:5mm 5mm 10mm 10mm;
  }
}
</style>

<?php 
define("SB_M1","data_13"); 
@session_start();
include "config_ctrl/connect.php";
include "config_ctrl/checksession.php";

if ($_SESSION['session_time'] != "") {
    $overtime = time() - $_SESSION['session_time'];
    if ($overtime > 3600) {
        echo '<script language="javascript">
                alert("กรุณาล็อกอินเข้าสู่ระบบใหม่");
                window.location.href="config_ctrl/logout.php"
              </script>';
        exit;
    } else {
        $_SESSION['session_time'] = time();
    }
}

$status = isset($_GET['status']) ? $_GET['status'] : '';
$rp_id  = isset($_GET['rp_id'])  ? $_GET['rp_id']  : '0';

function thDate($date, $show_time = false) {
    if (!$date || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return '';
    }

    $ts = strtotime($date);
    if ($ts === false) {
        return '';
    }

     $thaiMonths = array(
        "", 
        "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
        "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
    );

    $d = date('j', $ts);
    $m = (int)date('n', $ts);
    $y = (int)date('Y', $ts) + 543;

    $result = $d . ' ' . $thaiMonths[$m] . ' ' . $y;

    if ($show_time) {
        $time = date('H:i', $ts);
        $result .= ' ' . $time . ' น.';
    }

    return $result;
}

// ----------------------
//  SQL หัวใบงาน (repair_requests)
// ----------------------
$sql = " 
  SELECT
    r.id,

    -- map field ให้ชื่อเหมือนของเดิม
    r.rp_format,
    r.report_date        AS rp_date,
    r.report_time        AS rp_time,
    r.completed_date     AS rp_date_active,
    r.completed_time     AS rp_time_compalte,
    r.name               AS rp_name,
    r.phone              AS rp_phone,
    r.problem_detail     AS rp_subject,
    r.completed_solution AS rp_subject3,

    -- ดึงชื่อจาก master แทน field เดิม
    rg.rpg_name          AS rpg_name,   -- ชนิดของการบริการ (tb_repair_group)
    rs.rps_name          AS rps_name,   -- ประเภทงาน (tb_repair_system)

    r.image_url          AS rp_file1,
    NULL                 AS rp_close_file,   -- ถ้ามีคอลัมน์รูปปิดงานจริงมาแก้ตรงนี้ทีหลัง

    r.status,
    r.ag_id,
    r.machine_id,
    r.has_feedback,
    r.received_date,
    r.received_by,
    r.process_date,
    r.process_time,
    r.completed_by,
    r.created_by,
    r.created_at,

    a.ass_id   AS asset_ass_id,
    a.ass_code AS asset_code,
    a.asset_name,
    a.asset_sn,
    a.asset_model,

    ta.area_name AS area_name,
    tac.ac_name  AS ac_name,
    tar.ar_name  AS ar_name,

    CONCAT(ua.user_name,' ',ua.user_fname) AS name1,   -- ผู้รับแจ้ง / admin
    CONCAT(ut.user_name,' ',ut.user_fname) AS name2    -- ช่างผู้ดำเนินการ
  FROM repair_requests r
  LEFT JOIN tb_user ua
         ON ua.user_id = r.received_by            -- admin
  LEFT JOIN tb_user ut
         ON ut.user_id = r.received_by           -- ✅ ช่าง ใช้ completed_by แทน received_by
  LEFT JOIN tb_ass_list a
         ON a.ass_id = r.machine_id
  LEFT JOIN tb_area ta
         ON r.building = ta.area_id
  LEFT JOIN tb_area_class tac
         ON r.floor    = tac.ac_id
  LEFT JOIN tb_area_room tar
         ON r.room     = tar.ar_id

  -- ✅ join master ชนิดบริการ
  LEFT JOIN tb_repair_group rg
         ON rg.rpg_id   = r.service_type
        -- AND rg.rpg_ag_id = r.ag_id   -- ถ้าอยากล็อกตาม agency ด้วยก็ปลดคอมเมนต์ได้

  -- ✅ join master ประเภทงาน
  LEFT JOIN tb_repair_system rs
         ON rs.rps_id   = r.job_type
        -- AND rs.rps_ag_id = r.ag_id   -- เหมือนกัน ล็อกตาม agency ได้

  WHERE r.id = '" . mysqli_real_escape_string($connect, $rp_id) . "'
  LIMIT 1
";
 

$query    = mysqli_query($connect, $sql) or die(mysqli_error($connect));
$num_rows = mysqli_num_rows($query);

if ($num_rows >= 1) {
    $rs        = mysqli_fetch_array($query);
    $readonly1 = ' readonly';
    $text      = 'รายละเอียดแจ้งซ่อม';
} else {
    $text      = 'รายละเอียดแจ้งซ่อม';
}
?> 

<link rel="stylesheet" href="fonts/thsarabunnew.css" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js"></script>
<script type="text/javascript" src="src/jquery.qrcode.js"></script>
<script type="text/javascript" src="src/qrcode.js"></script>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<style type="text/css">
<!--
.style4 {font-size: 12px}
.style5 {font-size: 10px}
.style6 {font-size: 11px; }
.style7 {font-size: 9px}
-->
</style>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- ✅ ตั้งชื่อเอกสารจาก rp_format -->
  <title>Service Report #<?php echo htmlspecialchars($rs['rp_format'], ENT_QUOTES, 'UTF-8'); ?></title>

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
</head>
<body class="thsarabunnew24b" style="font-size:7px;">

<img src="Logo_โปร.png" alt="ระบบ EasyPro" width="147" height="35">
<div align="right" style="margin-top: -15px;">SM - SR - 001</div>
<center style="font-size:7px;">
  <table width="100%" style="border-collapse:collapse; font-size:7px;" border="1">
    <tr>
      <td style="background-color:#666666; color:#FFFFFF;"><div align="center" class="style4">Service Report ( รายงานการบริการ ) </div></td> 
    </tr>
    <tr></tr>
  </table>

  <table width="100%" style="border-collapse:collapse;font-size:7px; margin-top:5px;" border="1">
    <tr>
      <td width="23%"><span class="style7">Service No. </span></td>
      <td width="25%" rowspan="2"><span class="style7">:<?php echo $rs['rp_format']?></span></td>
      <td width="24%"><span class="style7">Admin : ผู้รับแจ้ง </span></td>
      <td width="28%"><span class="style7">:<?php echo $rs['name2']?></span></td>
    </tr>
    <tr>
      <td><span class="style7">เลขที่ใบบริการ </span></td>
      <td><span class="style7">ผู้ดำเนินการ/ช่าง</span></td>
      <td><span class="style7">:<?php echo $rs['name2']?></span></td>
    </tr>
    <tr>
      <td><p class="style7">Date </p></td>
      <td rowspan="2"><span class="style7">:<?php echo thDate($rs['rp_date'])?></span></td>
      <td><p class="style7">Time Attended </p></td>
      <td rowspan="2"><span class="style7">:<?php echo $rs['rp_time']?></span></td>
    </tr>
    <tr>
      <td><span class="style7">วันที่แจ้ง </span></td>
      <td><span class="style7">เริ่มตั้งแต่เวลา </span></td>
    </tr>
    <tr>
      <td><p class="style7">Date Attended </p></td>
      <td rowspan="2"><span class="style7">:<?php echo thDate($rs['rp_date_active']) ?></span></td>
      <td><p class="style7">Time Finished </p></td>
      <td rowspan="2"><span class="style7">:<?php echo $rs['rp_time_compalte']?></span></td>
    </tr>
    <tr>
      <td><span class="style7">วันที่ซ่อม </span></td>
      <td><span class="style7">สิ้นสุดเมื่อเวลา </span></td>
    </tr>
  </table>
  
  <table width="100%" style="border-collapse:collapse;font-size:7px;margin-top:5px;" border="1">
    <tr class="lin">
      <td style="background-color:#666666; color:#FFFFFF;"><div align="center" class="style4">Customer ( รายละเอียดลูกค้า/ผู้แจ้ง ) </div></td>
    </tr>
    <tr></tr>
  </table>
  
  <table width="100%" style="border-collapse:collapse;font-size:7px;margin-top:5px;" border="1">
    <tr>
      <td width="15%"><span class="style7">Customer Name  </span></td>
      <td width="39%" rowspan="2"><span class="style7">:<?php echo $rs['rp_name']?></span></td>
      <td width="19%"><span class="style7">Department</span></td>
      <td width="27%" rowspan="2"><span class="style7">:</span><span class="style7"><?php echo $rs['area_name']; ?></span></td>
    </tr>
    <tr>
      <td><span class="style7">นามลูกค้า</span></td>
      <td><span class="style7"> หน่วยงาน </span></td>
    </tr>
    <tr>
      <td><span class="style7">Address </span></td>
      <td rowspan="2"><span class="style7">:<?php echo $rs['area_name']; ?> <?php echo $rs['ac_name']; ?></span> <span class="style7"><?php echo $rs['ar_name']; ?></span></td>
      <td colspan="2" rowspan="4" valign="top"><span class="style7"><strong>รายละเอียดของปัญหา</strong> : <br />
      <?php echo $rs['rp_subject']; ?></span></td>
    </tr>
    <tr>
      <td><span class="style7">ที่อยู่/แผนก/ฝ่าย/ชั้น/ห้อง</span></td>
    </tr>
    <tr>
      <td><span class="style7">Telephone No.  </span></td>
      <td rowspan="2"><span class="style7">:<?php echo $rs['rp_phone']?></span></td>
    </tr>
    <tr>
      <td><span class="style7"> โทรศัพท์ </span></td>
    </tr>
  </table>
  
  <table width="100%" style="border-collapse:collapse;font-size:7px;margin-top:5px;" border="1">
    <tr style="background-color:#666666; color:#FFFFFF;">
      <td><div align="center" class="style6">Type of Searvice <br />ชนิดของการบริการ </div></td>
      <td><div align="center" class="style6">Type of Worked <br />ประเภทงาน </div></td>
      <td><div align="center" class="style6">Name of Technician <br />ชื่อ - นามสกุล ช่างบริการ </div></td>
      <td><div align="center" class="style6">Remark  <br />หมายเหตุ </div></td>
    </tr>
    <tr>
      <td width="26%" rowspan="5" align="left"><div style="font-size:14px;"><?php echo $rs['rpg_name']; ?></div></td>
      <td width="28%" rowspan="5" align="left"><div style="font-size:14px;"><?php echo $rs['rps_name']; ?></div></td>
      <td width="31%"><div align="left" class="style5"><?php echo $rs['name2']?></div></td>
      <td width="15%"><div align="right" class="style5">&nbsp;</div></td>
    </tr>
    <tr>
      <td width="31%"><div align="right" class="style5">&nbsp;</div></td>
      <td width="15%"><div align="right" class="style5"></div></td>
    </tr>
    <tr>
      <td width="31%"><div align="right" class="style7">&nbsp;</div></td>
      <td width="15%"><div align="right" class="style5"></div></td>
    </tr>
    <tr>
      <td width="31%"><div align="right" class="style5">&nbsp;</div></td>
      <td width="15%"><div align="right" class="style5"></div></td>
    </tr>
    <tr>
      <td><div align="right" class="style5">&nbsp;</div></td>
      <td><div align="right" class="style5"></div></td>
    </tr>
  </table>

  <table width="100%" style="border-collapse:collapse;font-size:7px;margin-top:5px;" border="1">
    <tr>
      <td style="background-color:#666666; color:#FFFFFF;"><div align="center" class="style4"><span class="style6">Job Description ( รายละเอียดการปฏิบัติงาน ) </span></div></td>
    </tr>
    <tr>
      <td height="69" valign="top" style="font-size:12px"><?php echo $rs['rp_subject3']?></td>
    </tr>
  </table>

  <table width="100%" border="1" style="border-collapse:collapse;font-size:7px;margin-top:5px;">
    <tr style="background-color:#666666; color:#FFFFFF;">
      <td colspan="7"><div align="center" class="style6">Spare Pari &amp; Consumable / อะไหล่และวัสดุสิ้นเปลี้อง</div></td>
      <td colspan="2" rowspan="2"><div align="center"><span class="style7">Conclusion / สรุปผลงาน ประเมินผลงานการทำงาน </span></div></td>
    </tr>
    <tr style="background-color:#666666; font-size:8px; text-align:center; color:#FFFFFF;">
      <td width="9%">Item<br />รายการ</td>
      <td width="10%">Part No.<br />เบอร์อะไหล่</td>
      <td width="25%">Description<br />รายละเอียด</td>
      <td width="11%">Model / Type<br />รุ่น / ชนิด</td>
      <td width="7%">Qty<br />จำนวน</td>
      <td width="7%">Unit Price<br />ราคา / หน่วย</td>
      <td width="8%">Total<br />รวมเงิน</td>
    </tr>
    <tr>
      <td colspan="7" rowspan="5" valign="top">
        <table border="1" style="border-collapse:collapse;font-size:7px;" id="example22" width="100%">
          <tbody>
          <?php
            // ----------------------
            //  SQL รายการอะไหล่ (tb_repair_detail + tb_wh_stock)
            // ----------------------
         $sql2 = "
				  SELECT
					rpd_id,
					rpd_rp_id,
					rpd_product_id,
					wh_id,
					pd_gen_code,
					wh_name,
					rpd_details_head,
					rpd_details,
					rpd_brand,
					rpd_qty,
					rpd_price,
					pd_unit,
					rpd_sum_money 
				  FROM tb_repair_detail
				  LEFT JOIN tb_wh_stock AS ws
					ON ws.id = wh_id
				  WHERE rpd_rp_id = '" . mysqli_real_escape_string($connect, $rp_id) . "'
				  ORDER BY rpd_id ASC
				";

            $query2    = mysqli_query($connect, $sql2) or die(mysqli_error($connect));
            $num_rows2 = mysqli_num_rows($query2);
            $sumtotal  = 0;

            if ($num_rows2 >= 1) {
              $i = 0;
              while ($rstmp = mysqli_fetch_array($query2)) {
                $i++;
          ?>
            <tr>
              <th width="9%" class="text-center"><?php echo $i; ?></th>
              <th width="10%" class="text-center">
                <strong><?php echo $rstmp['rpd_details_head']; ?>
                <input style="width:100%;" class="form-control" type="hidden"  name="rpd_details_head[]" id="rpd_details_head[]" value="<?php echo $rstmp['rpd_details_head']?>"/>
                <input style="width:100%;" class="form-control" type="hidden"  name="rpd_details_head_va_id2[]" id="rpd_details_head_va_id2[]" value="<?php echo $rstmp['rpd_details_head_va_id']?>"/></strong>
              </th>
              <th width="25%"><?php echo $rstmp['rpd_details']?></th>
              <th width="11%"><?php echo $rstmp['rpd_brand']?></th>
              <th width="7%"><?php echo $rstmp['rpd_qty']?></th>
              <th width="7%"><?php echo $rstmp['rpd_price']?></th>
              <th width="8%" style="text-align:right;"><?php echo $rstmp['rpd_sum_money']?></th>
            </tr>
          <?php 
                $sumtotal += $rstmp['rpd_sum_money'];
              }
            }

            $remaining = 5 - $num_rows2;
            $start_no  = $num_rows2 + 1;
          ?>

          <?php for ($t = 0; $t < $remaining; $t++) { ?>
            <tr>
              <th width="9%" class="text-center"><?php echo $start_no + $t; ?></th>
              <th width="10%" class="text-center">
                <strong><?php echo isset($rstmp['rpd_details_head']) ? $rstmp['rpd_details_head'] : ''; ?>
                <input style="width:100%;" class="form-control" type="hidden"  name="rpd_details_head[]" id="rpd_details_head[]" value="<?php echo isset($rstmp['rpd_details_head']) ? $rstmp['rpd_details_head'] : ''; ?>"/>
                <input style="width:100%;" class="form-control" type="hidden"  name="rpd_details_head_va_id2[]" id="rpd_details_head_va_id2[]" value="<?php echo isset($rstmp['rpd_details_head_va_id']) ? $rstmp['rpd_details_head_va_id'] : ''; ?>"/></strong>
              </th>
              <th width="25%"><?php echo isset($rstmp['rpd_details']) ? $rstmp['rpd_details'] : ''; ?></th>
              <th width="11%"><?php echo isset($rstmp['rpd_brand']) ? $rstmp['rpd_brand'] : ''; ?></th>
              <th width="7%"><?php echo isset($rstmp['rpd_qty']) ? $rstmp['rpd_qty'] : ''; ?></th>
              <th width="7%"><?php echo isset($rstmp['rpd_price']) ? $rstmp['rpd_price'] : ''; ?></th>
              <th width="8%" style="text-align:right;"><?php echo isset($rstmp['rpd_sum_money']) ? $rstmp['rpd_sum_money'] : ''; ?></th>
            </tr>
          <?php } ?>
          </tbody>
        </table>
      </td>

      <td width="11%">
        <span class="style7">
          <style>
            .inputtr {
              width: 10px;
              height: 10px;
            }
          </style>
          <input class="inputtr" type="checkbox" name="cb22" id="cb2" value="4" <?php if($rs['cb']=='3'){?>checked="checked"<?php } ?> required="required"/> ดีมาก
        </span>
      </td>
      <td width="12%" rowspan="2">
        <div align="center"><span class="style7">
          <input class="inputtr" type="checkbox" name="cb243" id="cb242" value="4" <?php if($rs['cb']=='3'){?>checked="checked"<?php } ?> required="required"/> Completed  เสร็จสมบูรณ์
        </span></div>
      </td>
    </tr>
    <tr>
      <td width="11%"><span class="style7">
        <input class="inputtr" type="checkbox" name="cb23" id="cb22" value="4" <?php if($rs['cb']=='3'){?>checked="checked"<?php } ?> required="required"/> ดี
      </span></td>
    </tr>
    <tr>
      <td width="11%"><span class="style7">
        <input class="inputtr" type="checkbox" name="cb24" id="cb23" value="4" <?php if($rs['cb']=='3'){?>checked="checked"<?php } ?> required="required"/> พอใช้
      </span></td>
      <td width="12%" rowspan="2">
        <div align="center"><span class="style7">
          <input class="inputtr" type="checkbox" name="cb244" id="cb243" value="4" <?php if($rs['cb']=='3'){?>checked="checked"<?php } ?> required="required"/> To Follow Up  ต้องดำเนินการต่อ
        </span></div>
      </td>
    </tr>
    <tr>
      <td width="11%"><span class="style7">
        <input class="inputtr" type="checkbox" name="cb242" id="cb24" value="4" <?php if($rs['cb']=='3'){?>checked="checked"<?php } ?> required="required"/> ปรับปรุง
      </span></td>
    </tr>
    <tr>
      <td colspan="2" rowspan="2"><span class="style7">Date : </span></td>
    </tr>
    <tr>
      <td colspan="4"><div align="center" class="style7">Grand Total </div></td>
      <td><?php echo isset($rstmp['rpd_qty']) ? $rstmp['rpd_qty'] : ''; ?></td>
      <td colspan="2" style="text-align:right;"><strong><?php echo $sumtotal; ?></strong></td>
    </tr>
  </table>

  <table width="100%" style="border-collapse:collapse;font-size:7px;margin-top:5px;" border="1">
    <tr style="background-color:#666666; color:#FFFFFF;"> 
      <td colspan="2"><div align="center" class="style4">Customer Approval ( ลูกค้าลงนามอนุมัติ ) </div></td>
      <td colspan="2"><div align="center" class="style4">Proactive Mannagemnt ( ผู้ให้บริการ ) </div></td>
    </tr>
    <tr>
      <td width="21%" height="34"><span class="style7">Client Signture / ลายเซ็นลูกค้า </span></td>
      <td width="29%"><span class="style7"></span></td>
      <td width="29%"><span class="style7">Technician Signture / ลายเซ็นผู้ให้บริการ </span></td>
      <td width="21%"><span class="style7"></span></td>
    </tr>
    <tr>
      <td><span class="style7">Print Name / ซื่อตัวบรรจง </span></td>
      <td><span class="style7"></span></td>
      <td><span class="style7">Print Name / ซื่อตัวบรรจง </span></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td><span class="style7">Date / วันที่ </span></td>
      <td><span class="style7"></span></td>
      <td><span class="style7">Date / วันที่ </span></td>
      <td><span class="style7"></span></td>
    </tr>
    <tr>
      <td colspan="2" rowspan="3"><span class="style7"></span></td>
      <td><span class="style7">Supervisor / Manager / ลายเซ็นผู้ตรวจสอบ </span></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td><span class="style7">Print Name / ชื่อตัวบรรจง </span></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td><span class="style7">Date / วันที่ </span></td>
      <td>&nbsp;</td>
    </tr>
  </table>
  <p align="right" class="style5">Rer : 00 : 26/04/2024 </p>
</center>
</body>

<br /><br /><br /><br />

<?php
// โหลดรูป "แจ้งซ่อม" (ก่อนซ่อม)
$beforeImages = array();
$sqlBefore = "
  SELECT path, file_name
  FROM repair_images
  WHERE repair_request_id = '" . mysqli_real_escape_string($connect, $rp_id) . "'
  ORDER BY created_at ASC
  LIMIT 5
";
$qBefore = mysqli_query($connect, $sqlBefore);
if ($qBefore) {
  while ($row = mysqli_fetch_assoc($qBefore)) {
    $beforeImages[] = $row;
  }
}

// โหลดรูป "แก้ไขแจ้งซ่อม" (หลังซ่อม)
$afterImages = array();
$sqlAfter = "
  SELECT path, file_name
  FROM repair_completed_images
  WHERE repair_request_id = '" . mysqli_real_escape_string($connect, $rp_id) . "'
  ORDER BY uploaded_at ASC
  LIMIT 5
";
$qAfter = mysqli_query($connect, $sqlAfter);
if ($qAfter) {
  while ($row = mysqli_fetch_assoc($qAfter)) {
    $afterImages[] = $row;
  }
}

// helper ให้ path ต่อกับ file_name สวย ๆ
function buildImageUrl($row) {
  $path = isset($row['path']) ? $row['path'] : '';
  $file = isset($row['file_name']) ? $row['file_name'] : '';
  return '../API_es/uploads/' . rtrim($path, '/').'/'.ltrim($file, '/');
}
?>
<style>
  .images-section {
    margin-top: 20px;
    page-break-inside: avoid;
  }
  .images-title {
    font-size: 11px;
    font-weight: bold;
    margin-bottom: 6px;
  }
  .images-grid {
    display: flex;
    flex-wrap: wrap;
    margin: -4px;
  }
  .images-grid-item {
    width: 20%; /* 5 รูปต่อแถวพอดี */
    padding: 4px;
    box-sizing: border-box;
    text-align: center;
  }
  .images-grid-item-inner {
    border: 1px solid #ddd;
    padding: 4px;
    border-radius: 3px;
  }
  .images-grid-item img {
    max-width: 100%;
    max-height: 120px;
    height: auto;
    object-fit: contain;
  }
  .images-grid-caption {
    margin-top: 3px;
    font-size: 9px;
    color: #555;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
</style><div class="row images-section">
  <!-- รูปแจ้งซ่อม (ก่อนซ่อม) -->
  <div class="col-sm-6">
    <div class="form-group">
      <div class="images-title">รูปภาพแจ้งซ่อม (Before) <span style="font-weight:normal;">สูงสุด 5 รูป</span></div>

      <?php if (!empty($beforeImages)) { ?>
        <div class="images-grid">
          <?php foreach ($beforeImages as $img) { 
            $src = buildImageUrl($img);
          ?>
            <div class="images-grid-item">
              <div class="images-grid-item-inner">
                <img src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                <div class="images-grid-caption">
                  <?php echo htmlspecialchars($img['file_name'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
              </div>
            </div>
          <?php } ?>
        </div>
      <?php } else { ?>
        <div style="font-size:9px; color:#999;">- ไม่มีรูปภาพแจ้งซ่อม -</div>
      <?php } ?>
    </div>
  </div>

  <!-- รูปแก้ไขแจ้งซ่อม (หลังซ่อม) -->
  <div class="col-sm-6">
    <div class="form-group">
      <div class="images-title">รูปภาพแก้ไขแจ้งซ่อม (After) <span style="font-weight:normal;">สูงสุด 5 รูป</span></div>

      <?php if (!empty($afterImages)) { ?>
        <div class="images-grid">
          <?php foreach ($afterImages as $img) { 
            $src = buildImageUrl($img);
          ?>
            <div class="images-grid-item">
              <div class="images-grid-item-inner">
                <img src="<?php echo htmlspecialchars($src, ENT_QUOTES, 'UTF-8'); ?>" alt="">
                <div class="images-grid-caption">
                  <?php echo htmlspecialchars($img['file_name'], ENT_QUOTES, 'UTF-8'); ?>
                </div>
              </div>
            </div>
          <?php } ?>
        </div>
      <?php } else { ?>
        <div style="font-size:9px; color:#999;">- ไม่มีรูปภาพแก้ไขแจ้งซ่อม -</div>
      <?php } ?>
    </div>
  </div>
</div>

<script>
  // auto print ตอนเปิด
  window.print();
</script>

</html>