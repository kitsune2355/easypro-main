<?php 
define("SB_M1","data_13"); 
@session_start();
include "config_ctrl/connect.php";
include "config_ctrl/checksession.php";
if($_SESSION['session_time']!=""){
	$overtime=time()-$_SESSION['session_time'];
	if($overtime>3600){
	 echo' <script language="javascript">
	 		alert("กรุณาล็อกอินเข้าสู่ระบบใหม่");
				window.location.href="config_ctrl/logout.php"
			</script>';
	}else{
	$_SESSION['session_time']=time();
	}
} 
$status 	= $_GET['status'];
$rp_id		= $_GET['rp_id']; 
 
	$sql="SELECT *		
		  FROM tb_repair
		  WHERE rp_id = '".$rp_id."'
		  LIMIT 0 , 1
		  "; 
		 // echo $sql;
	$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
	$num_rows =mysqli_num_rows($query);
	if($num_rows>=1){
		$rs=mysqli_fetch_array($query);
		$readonly1 = ' readonly';
		$text = 'รายละเอียดแจ้งซ่อม';
	}else{
		$text = 'รายละเอียดแจ้งซ่อม';
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
-->
</style>
<head>
  <meta charset="utf-8">
  	<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Untitled Document</title>
</head>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<body class="thsarabunnew24b" style="font-size:7px;">

<center style="font-size:7px;">

  <table width="100%" style="border-collapse:collapse; font-size:7px;"border="1">

    <tr>
      <td style="background-color:#666666; color:#FFFFFF;"><div align="center" class="style4">SERVICES REQUEST </div></td> 
    </tr>
    <tr>
      <td style="background-color: #E6E6E6;"><div align="center" class="style6">ใบแจ้งซ้อม / ใบแจ้งรับบริการ </div></td>
    </tr>
</table>
  <table width="100%" style="border-collapse:collapse;font-size:7px; margin-top:5px;" border="1">
    <tr>
      <td width="17%"><span class="style5">PROJECT TITLE : </span></td>
      <td width="40%"><?php echo $rs['rp_name']?></td>
      <td width="11%"><span class="style5">เลขที่ใบบริการ</span></td>
      <td width="32%"><?php echo $rs['rp_name']?></td>
    </tr>
    <tr>
      <td><span class="style5">ADDRESS : </span></td>
      <td><?php echo $rs['rp_name']?></td>
      <td><span class="style5">ผู้ยื่นคำร้อง</span></td>
      <td><?php echo $rs['rp_name']?></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td>&nbsp;</td>
      <td><span class="style5">ผู้รับคำร้อง</span></td>
      <td><?php echo $rs['rp_name']?></td>
    </tr>
  </table>
  
  <table width="100%" style="border-collapse:collapse;font-size:7px;margin-top:5px;"border="1">
    <tr class="lin">
      <td style="background-color:#666666; color:#FFFFFF;"><div align="center" class="style4">ส่วนที่ 1 สำหรับผู้ยื่นคำร้อง </div></td>
    </tr>
    <tr>
     <td style="background-color: #E6E6E6;"><div align="center" class="style6">รายละเอี่ยดลูกค้า</div></td>
    </tr>
  </table>
  
  <table width="100%" style="border-collapse:collapse;font-size:7px;margin-top:5px;"border="1">
    <tr>
      <td colspan="2"><span class="style5">ชื้อผู้แจ้ง</span></td>
      <td colspan="2" valign="top"><?php echo $rs['rp_name']?></td>
      <td width="9%"><span class="style5">วันที่</span></td>
      <td width="11%"><?php echo $rs['rp_date']?></td>
      <td colspan="2"><div align="center" class="style5">เอกสารอ้างอิง</div></td>
    </tr>
    <tr>
      <td colspan="2"><span class="style5">อาคาร / สถานที่ / แผนก </span></td>
      <td colspan="2" valign="top">&nbsp;</td>
      <td><span class="style5">เวลา</span></td>
      <td><?php echo $rs['rp_time']?></td>
      <td width="11%"><span class="style5">ไม่มี</span></td>
      <td width="33%"><?php echo $rs['rp_name']?></td>
    </tr>
    <tr>
      <td width="5%"><span class="style5">ชั้น</span></td>
      <td width="12%"><?php echo $rs['rp_ac_id']; ?></td>
      <td width="5%"><span class="style5">ห้อง</span></td>
      <td width="14%"><?php echo $rs['rp_ac_id']; ?></td>
      <td><span class="style5">เบอร์โทรศัพท์</span></td>
      <td><?php echo $rs['rp_phone']?></td>
      <td><span class="style5">มี (ระบุ) </span></td>
      <td><?php echo $rs['rp_name']?></td>
    </tr>
  </table>
  
  <table width="100%" style="border-collapse:collapse;font-size:7px;margin-top:5px;"border="1">
    <tr>
      <td><div align="center" class="style5">รายละเอียดของงาน</div></td>
    </tr>
    <tr>
      <td width="33%">&nbsp;</td>
    </tr>
  </table>
  
  <table width="100%" style="border-collapse:collapse;font-size:7px;margin-top:5px;"border="1">
    <tr>
      <td colspan="2" style="background-color:#666666; color:#FFFFFF;"><div align="center" class="style4">ส่วนที่ 2 สำหรับผู้รับคำร้อง </div></td>
    </tr>
    <tr>
      <td width="50%" style="background-color:#E6E6E6;"><div align="center" class="style6">ประเภทงาน</div></td>
      <td width="50%" style="background-color:#E6E6E6;"><div align="center" class="style6">ระบบ</div></td> 
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td>&nbsp;</td> 
    </tr>
    <tr>
      <td  colspan="2" style="background-color: #E6E6E6;"><div align="center" class="style6">ปัญหาที่พบ</div></td>
    </tr>
    <tr>
      <td  colspan="2">&nbsp;</td>
    </tr>
  </table>
  
  <table width="100%" style="border-collapse:collapse;font-size:7px;margin-top:5px;"border="1">
    <tr>
      <td style="background-color: #E6E6E6;"><div align="center" class="style6">รายละเอียดการให้บริการแนวทางการป้องกันและแก้ไข</div></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>
  </table>
  
  <table width="100%" style="border-collapse:collapse;font-size:7px;margin-top:5px;"border="1">
    <tr>
      <td colspan="7" style="background-color:#666666; color:#FFFFFF;"><div align="center" class="style4">อะไหล่ / วัสดุสิ้นเปลื่อง </div></td>
    </tr>
    <tr style="background-color: #E6E6E6;">
      <td width="11%"><div align="center" class="style4">ลำดับ</div></td>
      <td width="24%"><div align="center" class="style6">ชื่ออะไหล่ / วัสดุสิ้นเปลื่อง </div></td>
      <td width="12%"><div align="center" class="style6">รายละเอียด</div></td>
      <td width="19%"><div align="center" class="style6">ยี่ห้อ / รุ่น / ชนิด </div></td>
      <td width="9%"><div align="center" class="style6">จำนวน</div></td>
      <td width="16%"><div align="center" class="style6">ราคา / หน่วย </div></td>
      <td width="9%"><div align="center" class="style6">รวมเงิน</div></td>
    </tr>
	<?php for($x=0; $x<=5; $x++){?>
    <tr>
      <td>&nbsp;</td>
      <td><?php echo $rstmp['rpd_details_head']?></td>
      <td><?php echo $rstmp['rpd_details']?></td>
      <td><?php echo $rstmp['rpd_brand']?></td>
      <td><?php echo $rstmp['rpd_qty']?></td>
      <td><?php echo $rstmp['rpd_price']?></td>
      <td><?php echo $rstmp['rpd_sum_money']?></td>
    </tr>
	<?php } ?>
  </table>
  
  <table width="100%" style="border-collapse:collapse;font-size:7px;margin-top:5px;"border="1">
    <tr style="background-color: #E6E6E6;">
      <td><div align="center" class="style6"> ผลการปฎิบัติงาน</div></td>
      <td ><div align="center" class="style6">สาเหตุที่ไม่สามารถนำเนินการแล้วเสร็จ</div></td>
    </tr>
    <tr>
      <td width="13%">  <span class="style5">
        <input class="inputtr" type="checkbox"   name="cb" id="checkbox4" value="4" <?php if($rs['cb2']=='4'){?>checked="checked"<?php } ?>  required="required"/> 
      แล้วเสร็จ</span></td>
      <td width="25%" rowspan="2">&nbsp;</td>
    </tr>
    <tr>
      <td> <span class="style5">
        <input class="inputtr" type="checkbox"   name="cb" id="checkbox4" value="4" <?php if($rs['cb2']=='4'){?>checked="checked"<?php } ?>  required="required"/> 
      ต้องเข้าดำเนินการต่อ</span></td>
    </tr>
  </table>
  
  <table width="100%" style="border-collapse:collapse;font-size:7px;margin-top:5px;"border="1">
    <tr>
     <td style="background-color:#666666; color:#FFFFFF;" colspan="6"><div align="center" class="style4">ส่วนที่ 3 </div></td>
    </tr>
    <tr style="background-color: #E6E6E6;">
      <td colspan="5"><div align="center" class="style6">การประเมินผลการให้บริการ โดยลูกค้า </div></td>
      <td width="28%"><div align="center" class="style6">ข้อแนะนำเพื่อปรับปรุงการให้บริการ</div></td>
    </tr>
    <tr>
      <td width="25%"><span class="style5">1. ความรวดเร็วในการให้บริการ </span></td>
      <td width="10%"><div align="right" class="style5">
	  <style>
         .inputtr {
            width: 10px;
            height: 10px;
        }
    </style>
        <input class="inputtr" type="checkbox"   name="cb" id="checkbox4" value="4" <?php if($rs['cb']=='4'){?>checked="checked"<?php } ?>  required="required"/>
         <label class="labelTr" for="checkbox4">พอใจมาก</label>
</div></td>
      <td width="11%"><div align="right" class="style5"><label class="labelTr" for="checkbox5">
        <input class="inputtr" type="checkbox" name="cb" id="checkbox5" value="4" <?php if($rs['cb']=='3'){?>checked="checked"<?php } ?>  required="required"/>
พอใจ</label>
</div></td>
      <td width="10%"><div align="right" class="style5">
        <input class="inputtr" type="checkbox" name="cb" id="checkbox9" value="4" <?php if($rs['cb']=='2'){?>checked="checked"<?php } ?>  required="required"/>
      ปานกลาง</div></td>
      <td width="16%"><div align="right" class="style5">
        <input class="inputtr" type="checkbox" name="cb" id="checkbox13" value="4" <?php if($rs['cb']=='1'){?>checked="checked"<?php } ?>  required="required"/>
      ต่ำกว่าความคาดหวัง</div></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td><span class="style5">2. ความสุภาพของพนักงานให้บริการ </span></td>
      <td width="10%"><div align="right" class="style5">
        <input class="inputtr" type="checkbox" name="cb" id="checkbox" value="4" <?php if($rs['cb']=='4'){?>checked="checked"<?php } ?>  required="required"/>
        <label class="labelTr" for="checkbox">พอใจมาก</label>
        </div>
      </div></td>
      <td width="11%"><div align="right" class="style5">
        <input class="inputtr" type="checkbox" name="cb" id="checkbox6" value="4" <?php if($rs['cb']=='3'){?>checked="checked"<?php } ?>  required="required"/>
พอใจ</div></td>
      <td width="10%"><div align="right" class="style5">
        <input class="inputtr" type="checkbox" name="cb" id="checkbox10" value="4" <?php if($rs['cb']=='2'){?>checked="checked"<?php } ?>  required="required"/>
ปานกลาง</div></td>
      <td width="16%"><div align="right" class="style5">
        <input class="inputtr" type="checkbox" name="cb" id="checkbox14" value="4" <?php if($rs['cb']=='1'){?>checked="checked"<?php } ?>  required="required"/>
ต่ำกว่าความคาดหวัง</div></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td><span class="style5">3. การอธิบายข้อมูลเบื้องต้นให้ลูกค้าทราบ </span></td>
      <td width="10%"><div align="right" class="style5">
        <input class="inputtr" type="checkbox" name="cb" id="checkbox2" value="4" <?php if($rs['cb']=='4'){?>checked="checked"<?php } ?>  required="required"/>
        <label class="labelTr" for="checkbox2">พอใจมาก</label>
        </div>
      </div></td>
      <td width="11%"><div align="right" class="style5">
        <input class="inputtr" type="checkbox" name="cb" id="checkbox7" value="4" <?php if($rs['cb']=='3'){?>checked="checked"<?php } ?>  required="required"/>
      พอใจ</div></td>
      <td width="10%"><div align="right" class="style5">
        <input class="inputtr" type="checkbox" name="cb" id="checkbox11" value="4" <?php if($rs['cb']=='2'){?>checked="checked"<?php } ?>  required="required"/>
ปานกลาง</div></td>
      <td width="16%"><div align="right" class="style5">
        <input class="inputtr" type="checkbox" name="cb" id="checkbox15" value="4" <?php if($rs['cb']=='1'){?>checked="checked"<?php } ?>  required="required"/>
ต่ำกว่าความคาดหวัง</div></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td><span class="style5">4. การเสนอความช่วยเหลือต่างๆ</span></td>
      <td width="10%"><div align="right" class="style5">
        <input class="inputtr" type="checkbox" name="cb" id="checkbox3" value="4" <?php if($rs['cb']=='4'){?>checked="checked"<?php } ?>  required="required"/>
        <label class="labelTr" for="checkbox3">พอใจมาก</label>
        </div>
      </div></td>
      <td width="11%"><div align="right" class="style5">
        <input class="inputtr" type="checkbox" name="cb" id="checkbox8" value="4" <?php if($rs['cb']=='3'){?>checked="checked"<?php } ?>  required="required"/>
พอใจ</div></td>
      <td width="10%"><div align="right" class="style5">
        <input class="inputtr" type="checkbox" name="cb" id="checkbox12" value="4" <?php if($rs['cb']=='2'){?>checked="checked"<?php } ?>  required="required"/>
ปานกลาง</div></td>
      <td width="16%"><div align="right" class="style5">
        <input class="inputtr" type="checkbox" name="cb" id="checkbox16" value="4" <?php if($rs['cb']=='1'){?>checked="checked"<?php } ?>  required="required"/>
ต่ำกว่าความคาดหวัง</div></td>
      <td>&nbsp;</td>
    </tr>
  </table>
  
  <table width="100%" style="border-collapse:collapse;font-size:7px;margin-top:5px;"border="1">
    <tr style="background-color:#666666; color:#FFFFFF;"> 
      <td colspan="2"><div align="center" class="style4">Record By </div></td>
      <td colspan="2"><div align="center" class="style4">Approved By Site Engineer / Supervisur </div></td>
      <td colspan="2"><div align="center" class="style4">Approved By Custunler </div></td>
    </tr>
    <tr>
      <td width="8%"><span class="style5">Signatuer : </span></td>
      <td width="21%">&nbsp;</td>
      <td width="9%"><span class="style5">Signatuer : </span></td>
      <td width="30%">&nbsp;</td>
      <td width="8%"><span class="style5">Signatuer : </span></td>
      <td width="24%">&nbsp;</td>
    </tr>
    <tr>
      <td colspan="2">&nbsp;</td>
      <td colspan="2">&nbsp;</td>
      <td colspan="2">&nbsp;</td>
    </tr>
    <tr>
      <td><span class="style5">Date : </span></td>
      <td>&nbsp;</td>
      <td><span class="style5">Date : </span></td>
      <td>&nbsp;</td>
      <td><span class="style5">Date : </span></td>
      <td>&nbsp;</td>
    </tr>
    <tr>
      <td colspan="2">&nbsp;</td>
      <td colspan="2">&nbsp;</td>
      <td colspan="2">&nbsp;</td>
    </tr>
  </table>
  <p>&nbsp;</p>
</center>
</body>

<script> 
	
window.print();

</script>
