<script src="ajax/ajax_framework.js"> </script>
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
<?php  
define("SB_M1","datauser_1");
include "head_user.php";  
$member_id 			= $_GET['member_id'];
$status		 		= $_GET['status'];
$img_icon		 	= $_GET['img_icon'];

$sql="SELECT *		
	  FROM tb_member 
	  WHERE member_id = '".$member_id."'
	  LIMIT 0 , 1
	  "; 
	  
$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
$num_rows =mysqli_num_rows($query);
if($num_rows>=1){
	$rs=mysqli_fetch_array($query);
	$readonly1 = ' readonly';
	$text = 'แก้ไขข้อมูลติวเตอร์';
}else{
	$text = 'สมัครติวเตอร์';
}

?>
<style>
.container23 {
	display: flex;
	align-items: center;
	justify-content: center;
	min-height: 100vh;
}
</style>
<div class="container2">
  <link rel="stylesheet" href="css2/main.min.css">
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header"> 
      <div class="container"> 
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0"> <strong><?php echo $text; ?></strong></h1>
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    <iframe id="iFm_save_target" name="iFm_save_target" src="" style="width:0px;height:0px;border:0"></iframe>
    <form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target">
      <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_3" />
      <input name="status" type="hidden" id="status" value="<?php echo $status ?>" />
      <input type="hidden" name="member_id" value="<?php echo $rs['member_id'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อผู้ใช้งาน" > 
      <!-- Main content --> 
	  <?php if($img_icon==''){?>
      <div class="container">
	  <?php } ?>
      <div class="row">
      <?php if($rs['member_id']==''){?>
      <div class="col-md-12">
        <div class="card-header" style="background-color:#17a2b8; color:#FFFFFF">
          <h5 class="card-title m-0"  style="padding:5px;"><strong>ข้อมูลเข้าใช้งานระบบ</strong></h5>
        </div>
        <div class="callout callout-info"  >
          <div class="form-group">
            <label for="exampleInputBorder">อีเมล์ <code >*</code></label>
            <input type="text" name="member_email" value="<?php echo $rs['member_email'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="อีเมล์" required>
          </div>
          <div class="form-group">
            <label for="exampleInputBorder">ชื่อผู้ใช้งาน <code>*</code></label>
            <input type="text" name="member_username" value="<?php echo $rs['member_username'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อผู้ใช้งาน" required>
          </div>
          <div class="form-group">
            <label for="exampleInputBorder">รหัสผ่าน <code>*</code></label>
            <input type="text" name="member_password" value="<?php echo $rs['member_password'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="รหัสผ่าน" required>
          </div>
          <div class="form-group">
            <label for="exampleInputBorder">ยืนยันรหัสผ่าน <code >*</code></label>
            <input type="text" name="member_username_confirm" value="<?php echo $rs['member_username_confirm'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ยืนยันรหัสผ่าน" required>
          </div>
        </div>
      </div>
      <?php } ?>
      <div class="col-md-12">
        <div class="card-header" style="background-color:#17a2b8; color:#FFFFFF">
          <h5 class="card-title m-0" style="padding:5px;"><strong>ข้อมูลส่วนตัว</strong></h5>
        </div>
        <div class="callout callout-info"  >
          <div class="form-group">
            <label for="exampleInputBorder">คำนำหน้า <code>*</code></label>
            <select name="member_prefix" class="form-control " data-dropdown-css-class="select2-danger" style="width: 100%;" required>
              <option >เลือก</option>
              <option value="1" <?php if($rs['member_prefix']=='1'){?> selected="selected" <?php } ?>>นาย</option>
              <option value="2" <?php if($rs['member_prefix']=='2'){?> selected="selected" <?php } ?>>นางสาว</option>
              <option value="3" <?php if($rs['member_prefix']=='3'){?> selected="selected" <?php } ?>>นาง</option>
              <option value="4" <?php if($rs['member_prefix']=='4'){?> selected="selected" <?php } ?>>ด.ช.</option>
              <option value="5" <?php if($rs['member_prefix']=='5'){?> selected="selected" <?php } ?>>ด.ญ.</option>
            </select>
          </div>
          <div class="form-group">
            <label for="exampleInputBorder">ชื่อจริง <code >*</code></label>
            <input type="text" name="member_first_name" value="<?php echo $rs['member_first_name'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อจริง" required>
          </div>
          <div class="form-group">
            <label for="exampleInputBorder">นามสกุล <code >*</code></label>
            <input type="text" name="member_last_name" value="<?php echo $rs['member_last_name'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="นามสกุล" required>
          </div>
          <div class="form-group">
            <label for="exampleInputBorder">ชื่อเล่น <code >*</code></label>
            <input type="text" name="member_nick_name" value="<?php echo $rs['member_nick_name'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อเล่น" required>
          </div>
          <div class="form-group">
            <label for="exampleInputBorder">วัน-เดือน-ปี เกิด <code >*</code></label>
            <div class="row">
              <div class="col-md-4">
                <div class="row">
                  <div class="col-sm-12" style="margin-top:5px;">
                    <select name="member_birthday_day" id="member_birthday_day" class="form-control" required >
                      <option value="" >เลือกวัน</option>
                      <?php
                                            for($i=01;$i<=31;$i++) { 
                                              ?>
                      <option value="<?php echo  $i?>" <?php if($i==$rs['member_birthday_day']) echo 'selected';?>  ><?php echo $i?></option>
                      <?php
                                            }?>
                    </select>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="row">
                  <div class="col-sm-12" style="margin-top:5px;">
                    <select name="member_birthday_month" id="member_birthday_month" class="form-control " required >
                      <option value="" > เลือกเดือน</option>
                      <?php
					  		  $thai_full_month_arr=array(
													"0"=>"",
													"1"=>"มกราคม",
													"2"=>"กุมภาพันธ์",
													"3"=>"มีนาคม",
													"4"=>"เมษายน",
													"5"=>"พฤษภาคม",
													"6"=>"มิถุนายน",	
													"7"=>"กรกฏาคม",
													"8"=>"สิงหาคม",
													"9"=>"กันยายน",
													"10"=>"ตุลาคม",
													"11"=>"พฤศจิกายน",
													"12"=>"ธันวาคม"
												);
							  if($rsm_birthday_month==""){
							  $rsm_birthday_month = date('n');
							  }else{
							  $month_period_search = $month;
							  }
							  echo $month_period_search;
							  for($i=01;$i<=12;$i++) { 
							  ?>
                      <option value="<?php echo $i?>" <?php if($i==$rs['member_birthday_month']) echo 'selected'; ?>  ><?php echo $thai_full_month_arr[$i]?></option>
                      <?php
							  }?>
                    </select>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="row">
                  <div class="col-sm-12" style="margin-top:5px;">
                    <select name="member_birthday_year" id="member_birthday_year"  class="form-control "  required >
                      <option value="" > เลือกปี</option>
                      <?php
                                            for($i=(date('Y'));$i>(date('Y')-90);$i--) {
                                              ?>
                      <option value="<?php echo  $i?>" <?php if($i==$rs['member_birthday_year']) echo 'selected';?>  ><?php echo $i+543?>/<?php echo $i?></option>
                      <?php
                                            }?>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="row">
              <div class="col-sm-6">
                <!-- text input -->
                <div class="form-group">
                  <label>เบอร์โทร <code >*</code></label>
                  <input type="text" name="member_phone" value="<?php echo $rs['member_phone'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="เบอร์โทร" required>
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>&nbsp;</label>
                  <select name="member_phone_network" id="member_phone_network"  class="form-control "  required >
                    <option value="">กรุณาเลือกเครือข่ายเบอร์โทร</option>
                    <option value="1" <?php if($rs['member_phone_network']=='1'){?> selected="selected" <?php } ?>>AIS</option>
                    <option value="2" <?php if($rs['member_phone_network']=='2'){?> selected="selected" <?php } ?>>DTAC</option>
                    <option value="3" <?php if($rs['member_phone_network']=='3'){?> selected="selected" <?php } ?>>TRUE</option>
                    <option value="4" <?php if($rs['member_phone_network']=='4'){?> selected="selected" <?php } ?>d>My Cat</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="row">
              <div class="col-sm-6">
                <!-- text input -->
                <div class="form-group">
                  <label>เบอร์โทรสำรอง <code ></code></label>
                  <input type="text" name="member_phone2" value="<?php echo $rs['member_phone2'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="เบอร์โทรสำรอง" >
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label>&nbsp;</label>
                  <select name="member_phone_network2" id="member_phone_network2"  class="form-control "  >
                    <option value="">กรุณาเลือกเครือข่ายเบอร์โทรสำรอง</option>
                    <option value="1" <?php if($rs['member_phone_network2']=='1'){?> selected="selected" <?php } ?>>AIS</option>
                    <option value="2" <?php if($rs['member_phone_network2']=='2'){?> selected="selected" <?php } ?>>DTAC</option>
                    <option value="3" <?php if($rs['member_phone_network2']=='3'){?> selected="selected" <?php } ?>>TRUE</option>
                    <option value="4" <?php if($rs['member_phone_network2']=='4'){?> selected="selected" <?php } ?>d>My Cat</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label for="exampleInputBorder">Facebook </label>
            <div class="row">
              <div class="col-md-2">
                <div class="row">
                  <div class="col-3" style="margin-top:5px; text-align:right"> https://www.facebook.com/ </div>
                </div>
              </div>
              <div class="col-md-5">
                <div class="row">
                  <div class="col-8" style="margin-left:25px;">
                    <input type="text" name="member_facebook" value="<?php echo $rs['member_facebook'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ระบุชื่อ Facebook" >
                  </div>
                </div>
              </div>
            </div>
            คลิกดูวิธีการใส่ชื่อ Facebook ที่นี่
            <div class="form-group"><br>
              <label for="exampleInputBorder">ไอดีไลน์ <code >*</code></label>
              <input type="text" name="member_line" value="<?php echo $rs['member_line'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ไอดีไลน์" required>
            </div>
            <div class="form-group">
              <label for="exampleInputBorder">เลขที่บัตรประชาชน <code >*</code></label>
              <input type="text" name="member_id_card" value="<?php echo $rs['member_id_card'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="เลขที่บัตรประชาชน" data-inputmask="'mask': ['9-9999-99999-99-9']" data-mask  inputmode="text" required>
            </div>
            <div class="form-group"><br>
              <label for="exampleInputBorder">แนบรูปภาพบัตรประชาชน <code >*</code></label>
              <div class="custom-file col-md-5">
                <input name="file10" type="file" class="custom-file-input" id="customFile" <?php if($rs['member_id_card_file']==''){?>required<?php } ?>>
                <label class="custom-file-label" for="customFile">Choose file</label>
              </div>
            </div>
            <?php
						   if($rs['member_id_card_file']!="") {
						   	    $arr = explode(".",$rs['member_id_card_file']);
							    if((strtoupper($arr[1])=="JPG")
							   	  ||(strtoupper($arr[1])=="GIF")
							   	  ||(strtoupper($arr[1])=="PNG")
							   	  ||(strtoupper($arr[1])=="TIF")
							   	  ||(strtoupper($arr[1])=="JPEG")) {
								  echo '<a target="_blank" href="'.$rs['member_id_card_file'].'"><img src="'.$rs['member_id_card_file'].'"  width="50%"></a>';
								}else{
								
								  echo '<a target="_blank" href="'.$rs['member_id_card_file'].'"><li class="fas fa-paperclip"></li>ไฟล์</a>';
							  	}
						   }
						   ?>
          </div>
        </div>
      </div>
      <div class="col-md-12">
        <div class="card-header" style="background-color:#17a2b8; color:#FFFFFF">
          <h5 class="card-title m-0"  style="padding:5px;"><strong>ที่อยู่ปัจจุบัน</strong></h5>
        </div>
        <div class="callout callout-info"  >
          <div class="form-group">
            <label for="exampleInputBorder">ที่อยู่ <code>*</code></label>
            <input type="text" name="member_address" value="<?php echo $rs['member_address'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ที่อยู่" required>
          </div>
          <div class="form-group">
            <label for="exampleInputBorder">จังหวัด <code >*</code></label>
            <input type="text" name="cus_addr06" value="<?php echo $rs['cus_addr06'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ที่อยู่" required>
            <!--<select name="cus_addr06" id="cus_addr06" onchange="ListAmphur(this.value,'cus_addr05','cus_addr04')" class="form-control select2 ">
					<option value=""> เลือกจังหวัด </option>
				 
					 <?php
						$sql="SELECT *
						  FROM  tb_addr
						  WHERE Type = '1' 
						  ORDER BY Name ASC
						  ";
						$query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
						$num_rows =mysqli_num_rows($query);
						if ($num_rows>=1){
							$tmpScript = "";
							while($rstmp=mysqli_fetch_array($query)) { ?>
							<option value="<?php echo $rstmp['Code']?>" 
							<?php if($rs['cn_prov']==$rstmp['Code']) { 
									echo "selected"; 
									$tmpScript = '
									<script language="javascript">
										ListAmphur(\''.$rstmp['Code'].'\',\'cus_addr05\',\'cus_addr04\');
									</script>
									';
									if(($rs['cn_county']!="")&&($rs['cn_county']!="0")) {
										$tmpScript.= '
										<script language="javascript">
											function fn_select() {
												var cus_addr05 = document.getElementById ( "cus_addr05" );
												cus_addr05.value = \''.$rs['cn_county'].'\';  
											}
											setTimeout("ListDistrict(\''.$rs['cn_county'].'\',\'cus_addr04\')",500);
											setTimeout("fn_select()",700);
										</script>
										';
									}
									if(($cus_addr04!="")&&($cus_addr04!="0")) {
										$tmpScript.= '
										<script language="javascript">
											function fn_select2() {
												var cus_addr04 = document.getElementById ( "cus_addr04" );
												cus_addr04.value = \''.$cus_addr04.'\';  
											}
											setTimeout("fn_select2()",1000);
										</script>
										';
									}
								} ?> ><?php echo $rstmp['Name']?></option>
							<?php
							}
						}
						?> 
					</select>-->
          </div>
          <div class="form-group">
            <label for="exampleInputBorder">อำเภอ/เขต <code >*</code></label>
            <input type="text" name="cus_addr05" value="<?php echo $rs['cus_addr05'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="อำเภอ/เขต" required>
            <!-- <select name="cus_addr05" id="cus_addr05" onChange="ListDistrict(this.value,'cus_addr04')" class="form-control"><option value="">กรุณาเลือกจังหวัด</option></select>-->
          </div>
          <div class="form-group">
            <label for="exampleInputBorder">ตำบล/แขวง <code >*</code></label>
            <input type="text" name="cus_addr04" value="<?php echo $rs['cus_addr04'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ตำบล/แขวง" required>
            <!--
                  <select name="cus_addr04" id="cus_addr04"   class="form-control" ><option value="">กรุณาเลือกอำเภอ/เขต</option></select>-->
          </div>
        </div>
      </div>
      <div class="col-md-12">
        <div class="card-header" style="background-color:#17a2b8; color:#FFFFFF">
          <h5 class="card-title m-0"  style="padding:5px;"><strong>ประวัติการศึกษา</strong></h5>
        </div>
        <div class="callout callout-info"  >
          <div class="fc fc-unthemed fc-ltr">
            <div class="fc-view-container" style="">
              <div class="b-table">
                <div class="table-wrapper has-mobile-cards" >
                  <button type="button" class="btn btn-block btn-warning  btn-sm" style="text-align:left;"><strong>มัธยมปลาย</strong></button>
                  <br />
                  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label">โรงเรียน<code >*</code></label>
                    <div class="col-sm-10">
                      <input type="text" name="member_shool" class="form-control" id="inputEmail3" value="<?php echo $rs['member_shool'] ?>" placeholder="โรงเรียน">
                    </div>
                  </div>
                  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label">แผนการเรียน<code >*</code></label>
                    <div class="col-sm-10">
                      <input type="text" name="member_shool_plan" class="form-control" id="inputEmail3" value="<?php echo $rs['member_shool_plan'] ?>" placeholder="แผนการเรียน">
                    </div>
                  </div>
                  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label">GPA<code >*</code></label>
                    <div class="col-sm-10">
                      <input type="text" name="member_shool_GPA" class="form-control" id="inputEmail3" value="<?php echo $rs['member_shool_GPA'] ?>" placeholder="GPA">
                    </div>
                  </div>
                  <button type="button" class="btn btn-block btn-warning  btn-sm" style="text-align:left;"><strong>ระดับการศึกษาปัจจุบัน</strong></button>
                  <br />
                  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label" style="font-size:14px">กำลังศึกษาหรือจบระดับ <code >*</code></label>
                    <div class="col-sm-10">
                      <select name="member_shool_ent" class="form-control">
                        <option value="">กรุณาเลือก</option>
                        <option value="1" <?php if($rs['member_shool_ent']=='1'){?> selected="selected" <?php } ?>>ปริญญาตรี</option>
                        <option value="2" <?php if($rs['member_shool_ent']=='2'){?> selected="selected" <?php } ?>>ปริญญาโท</option>
                        <option value="3" <?php if($rs['member_shool_ent']=='3'){?> selected="selected" <?php } ?>>ปริญญาเอก</option>
                      </select>
                    </div>
                  </div>
                  <?php  
			 $degree_title = array("","ระดับปริญญาตรี", "ระดับปริญญาโท", "ระดับปริญญาเอก");
			 $block_title = array("","<blockquote>", "<blockquote class=\"quote-secondary\">", "<blockquote class=\"quote-Warning\">");
			   for($i=1; $i<=3; $i++){
			  	 $sql_ed="SELECT *
						FROM `tb_education` As TbWs 
						WHERE member_id = '".$member_id."'
					  AND degree =  '" . $i . "'
						ORDER BY edu_id ASC  
					  ";
				  $query_ed = mysqli_query($connect, $sql_ed) or die(mysqli_error($connect));
				  $rs_ed = mysqli_fetch_array($query_ed);
			   ?>
                  <?php echo $block_title[$i] ?>
                  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label" style="color:#FF0000"><?php echo $degree_title[$i]?><code >*</code></label>
                    <input name="degree<?php echo $i ?>" type="hidden"  value="<?php echo $i ?>" placeholder="GPA">
                    <div class="col-sm-2">
                      <div class="custom-control custom-radio">
                        <input class="custom-control-input custom-control-input-danger" type="radio" id="customRadio<?php echo $i ?>_1" name="check_schloo<?php echo $i ?>" value="1" <?php if($rs_ed['check_schloo']=='1'){?> checked="checked" <?php } ?>>
                        <label for="customRadio<?php echo $i ?>_1" class="custom-control-label">จบการศึกษาแล้ว</label>
                      </div>
                    </div>
                    <div class="col-sm-2">
                      <div class="custom-control custom-radio">
                        <input class="custom-control-input custom-control-input-danger" type="radio" id="customRadio<?php echo $i ?>_2" name="check_schloo<?php echo $i ?>" value="2" <?php if($rs_ed['check_schloo']=='2'){?> checked="checked" <?php } ?>>
                        <label for="customRadio<?php echo $i ?>_2" class="custom-control-label">กำลังศึกษาอยู่</label>
                      </div>
                    </div>
                  </div>
                  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label" style="font-size:14px">ชั้นปี</label>
                    <div class="col-sm-4">
                      <select name="note<?php echo $i ?>" class="form-control">
                        <option value="">กรุณาเลือกชั้นปี</option>
                        <option value="1" <?php if($rs_ed['note']=='1'){?> selected="selected" <?php } ?>>ปีที่ 1</option>
                        <option value="2" <?php if($rs_ed['note']=='2'){?> selected="selected" <?php } ?>>ปีที่ 2</option>
                        <option value="3" <?php if($rs_ed['note']=='3'){?> selected="selected" <?php } ?>>ปีที่ 3</option>
                        <option value="4" <?php if($rs_ed['note']=='4'){?> selected="selected" <?php } ?>>ปีที่ 4</option>
                        <option value="5" <?php if($rs_ed['note']=='5'){?> selected="selected" <?php } ?>>ปีที่ 5</option>
                        <option value="6" <?php if($rs_ed['note']=='6'){?> selected="selected" <?php } ?>>ปีที่ 6</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label" style="font-size:14px">สถาบัน</label>
                    <div class="col-sm-4">
                      <input type="text" name="university<?php echo $i ?>" class="form-control" id="inputEmail3" value="<?php echo $rs_ed['university'] ?>" placeholder="โรงเรียน">
                    </div>
                  </div>
                  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label" style="font-size:14px">คณะ</label>
                    <div class="col-sm-4">
                      <input type="text" name="faculty<?php echo $i ?>" class="form-control" id="inputEmail3" value="<?php echo $rs_ed['faculty'] ?>" placeholder="คณะ">
                    </div>
                  </div>
                  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label" style="font-size:14px">สาขา/วิชาเอก</label>
                    <div class="col-sm-4">
                      <input type="text" name="section<?php echo $i ?>" class="form-control" id="inputEmail3" value="<?php echo $rs_ed['section'] ?>" placeholder="สาขา/วิชาเอก">
                    </div>
                  </div>
                  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label" style="font-size:14px">gpa</label>
                    <div class="col-sm-4">
                      <input type="text" name="gpa<?php echo $i ?>" class="form-control" id="inputEmail3" value="<?php echo $rs_ed['gpa'] ?>" placeholder="gpa">
                    </div>
                  </div>
                  </blockquote>
                  <?php } ?>
                  <!--<table class=" table table-bordered table-hover " id="mytable_1" style=" border-collapse:collapse; font-size: 12px; border-radius:3px; ">
					<thead> 
						<tr style="background-color: yellowgreen;">      
							<th width="118" align="center" >ระดับ</th>  
							<th width="112" align="center" >สถาบัน</th>  
							<th width="115" align="center" >สาขาวิชา</th>   
							<th width="132" align="center" >วิชาเอก</th> 
							<th width="201" align="center" >GPA</th> 
							<th width="152" align="center" >เกียรติประวัติ</th>   
							<th width="180" align="center" ><span>-</span></th> 
							<th width="67" align="center" >&nbsp;</th>   
						</tr>
					</thead>
					<tbody>
					 <?php
					$rsmp_title = array("","นาย", "นาง", "นางสาว");
					$rsmp_position  = array("","หัวหน้าแม่บ้าน", "แม่บ้าน", "สแปร์", "ล้างจาน (สจ๊วต)");
					if(strlen($search_month)==1) $search_month = "0".$search_month; 
					$sql="SELECT *
						FROM `tb_education` As TbWs 
						WHERE member_id = '".$member_id."'
						ORDER BY edu_id ASC
						";   
					//echo $sql;
					$query=mysqli_query($connect,$sql) or die(mysqli_error($connect));
					$num_rows = mysqli_num_rows($query); 
					$query=mysqli_query($connect,$sql) or die(mysqli_error($connect));
					$num_rows = mysqli_num_rows($query); 
								if($num_rows>=1){ 
									$x=0;
									while($rs_sb=mysqli_fetch_array($query)){   
										$x++; 
										?> 	   
										<tr>
										 
										<td data-label="ระดับ" width="112" style=" font-size:12px;">
										  <select name="degree[]" class="form-control" id="">
											<option value="1" <?php if($rs_sb['degree']=='1'){?> selected="selected" <?php } ?>>ปวช.</option>
											<option value="2" <?php if($rs_sb['degree']=='2'){?> selected="selected" <?php } ?>>ปวส.</option>
											<option value="3" <?php if($rs_sb['degree']=='3'){?> selected="selected" <?php } ?>>ปริญญาตรี</option>
											<option value="4" <?php if($rs_sb['degree']=='4'){?> selected="selected" <?php } ?>>ปริญญาโท.</option>
											<option value="5" <?php if($rs_sb['degree']=='5'){?> selected="selected" <?php } ?>>ปริญญาเอก</option>
										</select>
										</td>
										<td data-label="สถาบัน" width="115"  > 
										  <input type="text" id="university[]" name="university[]" class="form-control" value="<?php echo $rs_sb['university']; ?>" style="width: 100%;">
										</td> 
										<td width="132"   data-label="สาขาวิชา"> 
										  <input type="text" id="faculty[]" name="faculty[]" class="form-control" value="<?php echo $rs_sb['faculty']; ?>" style="width: 100%;">
									  	</td>   
										<td width="201"  data-label="วิชาเอก"> 
										  <input type="text" id="section[]" name="section[]" class="form-control" value="<?php echo $rs_sb['section']; ?>" style="width: 100%;">								
									    </td>   
										<td width="152" style="text-align:right" data-label="GPA"> 
										  <input type="text" id="gpa[]" name="gpa[]" class="form-control" value="<?php echo $rs_sb['gpa']; ?>" style="width: 100%;">	 	
										</td>   
										<td width="180"  data-label="เกียรติประวัติ"> 
										 
										  <input type="text" id="note[]" name="note[]" class="form-control" value="<?php echo $rs_sb['note']; ?>" style="width: 100%;">										
										</td>     
		                                <td data-label="" width="67"  class="" style=" text-align:left; ">
										<select name="edu_status[]" class="form-control" id="">
												<option value="1" <?php if($rs_sb['edu_status']=='1'){?> selected="selected" <?php } ?>>ศึกษาอยู่</option>
												<option value="2" <?php if($rs_sb['edu_status']=='1'){?> selected="selected" <?php } ?>>จบแล้ว</option>
										</select>
										</td>	
		                                <td data-label="" width="100" style=" text-align:left; "> 
										<button onclick="fn_add_item('<?php echo $x ?>')" type="button" class="btn  btn-secondary btn-sm">+</button>
										<button onclick="fn_del_row_item('<?php echo $x ?>')" type="button" class="btn  btn-warning btn-sm">-</button> 
										</td>	 
									</tr> 
									<?php     
									$i++;    
										}  
									}
									?>
									<tr> 
										<td data-label="ระดับ" width="112" >
										  <select name="degree[]" class="form-control" id="">
											<option value="1" >ปวช.</option>
											<option value="2">ปวส.</option>
											<option value="3">ปริญญาตรี</option>
											<option value="4">ปริญญาโท.</option>
											<option value="5">ปริญญาเอก</option>
										</select>
										</td>
										<td data-label="สถาบัน" width="115"  > 
										  <input type="text" id="university[]" name="university[]" class="form-control" value="<?php echo $rs['university']; ?>" style="width: 100%;">
										</td> 
										<td width="132"   data-label="สาขาวิชา"> 
										  <input type="text" id="faculty[]" name="faculty[]" class="form-control" value="<?php echo $rs['faculty']; ?>" style="width: 100%;">
									  	</td>   
										<td width="201"  data-label="วิชาเอก"> 
										  <input type="text" id="section[]" name="section[]" class="form-control" value="<?php echo $rs['section']; ?>" style="width: 100%;">								
									    </td>   
										<td width="152" style="text-align:right" data-label="GPA"> 
										  <input type="text" id="gpa[]" name="gpa[]" class="form-control" value="<?php echo $rs['gpa']; ?>" style="width: 100%;">	 	
										</td>   
										<td width="180"  data-label="เกียรติประวัติ"> 
										 
										  <input type="text" id="note[]" name="note[]" class="form-control" value="<?php echo $rs['note']; ?>" style="width: 100%;">										
										</td>     
		                                <td data-label="" width="67"  class="" style=" text-align:left; ">
										<select name="edu_status[]" class="form-control" id="">
												<option value="1">ศึกษาอยู่</option>
												<option value="2">จบแล้ว</option>
										</select>
										</td>	
		                                <td data-label="" width="100"  class="" style=" text-align:left; "> 
										<button onclick="fn_add_item('<?php echo $x+1 ?>')" type="button" class="btn  btn-secondary btn-sm">+</button>
										</td>	 
									</tr> 
	</tbody>
	</table>   -->
                </div>
              </div>
            </div>
          </div>
        </div>  
        </div> 
      <div class="col-md-12">
        <div class="card-header" style="background-color:#17a2b8; color:#FFFFFF">
          <h5 class="card-title m-0"  style="padding:5px;"><strong>งานสอนที่ต้องการสอนในปัจจุบัน</strong></h5>
        </div>
        <div class="callout callout-info"  >
          <div class="fc fc-unthemed fc-ltr">
            <div class="fc-view-container" style="">
              <div class="b-table">
                <div class="table-wrapper has-mobile-cards" > 
                  <br />
                  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label" style="font-size:12px">
					<div class="icheck-primary d-inline" >
                        <input name="member_teach_check_1" type="checkbox" id="checkboxPrimary1" value="1" <?php if($rs['member_teach_check_1']=='1'){?> checked="checked" <?php } ?>>
                        <label for="checkboxPrimary1">
                        </label>
                      </div>
					  อนุบาล<code ></code></label>
                    <div class="col-sm-10">
                      <input type="text" name="member_teach_1" class="form-control" id="inputEmail3" value="<?php echo $rs['member_teach_1'] ?>" placeholder="เช่น คณิตศาสตร์, ภาษาอังกฤษ">
                    </div> 
                  </div> 
				  
                  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label" style="font-size:12px">
					<div class="icheck-primary d-inline">
                        <input name="member_teach_check_2" type="checkbox" id="checkboxPrimary2" value="1"  <?php if($rs['member_teach_check_2']=='1'){?> checked="checked" <?php } ?>>
                        <label for="checkboxPrimary2">
                        </label>
                      </div>
					  ประถม<code ></code></label>
                    <div class="col-sm-10">
                      <input type="text" name="member_teach_2" class="form-control" id="inputEmail3" value="<?php echo $rs['member_teach_2'] ?>" placeholder="เช่น คณิตศาสตร์, ศิลปะ">
                    </div> 
                  </div> 
				  
				  
                  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label" style="font-size:12px">
					<div class="icheck-primary d-inline">
                        <input name="member_teach_check_3" type="checkbox" id="checkboxPrimary3" value="1"  <?php if($rs['member_teach_check_3']=='1'){?> checked="checked" <?php } ?>>
                        <label for="checkboxPrimary3">
                        </label>
                      </div>
					  มัธยม<code ></code></label>
                    <div class="col-sm-10">
                      <input type="text" name="member_teach_3" class="form-control" id="inputEmail3" value="<?php echo $rs['member_teach_3'] ?>" placeholder="เเช่น อังกฤษ คณิต ฟิสิกส์ เคมี ชีวะ GAT PAT">
                    </div> 
                  </div> 
				  
				  
                  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label" style="font-size:12px">
					<div class="icheck-primary d-inline">
                        <input name="member_teach_check_4" type="checkbox" id="checkboxPrimary4" value="1"  <?php if($rs['member_teach_check_4']=='1'){?> checked="checked" <?php } ?>>
                        <label for="checkboxPrimary4">
                        </label>
                      </div>
					    ติวสอบเข้ามหาลัย<code ></code></label>
                    <div class="col-sm-10">
                      <input type="text" name="member_teach_4" class="form-control" id="inputEmail3" value="<?php echo $rs['member_teach_4'] ?>" placeholder="เช่น GAT PAT ONET SAT CUTEP">
                    </div> 
                  </div> 
				  
				  
                  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label" style="font-size:10px">
					<div class="icheck-primary d-inline">
                        <input name="member_teach_check_5" type="checkbox" id="checkboxPrimary5" value="1"  <?php if($rs['member_teach_check_5']=='1'){?> checked="checked" <?php } ?>>
                        <label for="checkboxPrimary5">
                        </label>
                      </div>
					 <strong> มหาวิทยาลัย / บุคคลทั่วไป</strong><code ></code></label>
                    <div class="col-sm-10">
                      <input type="text" name="member_teach_5" class="form-control" id="inputEmail3" value="<?php echo $rs['member_teach_5'] ?>" placeholder="เช่น สถิติ บัญชี calculus">
                    </div> 
                  </div> 
				  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label" style="font-size:14px">
					 
					    สถานที่สะดวกสอน <code >*</code></label>
                    <div class="col-sm-10"> 
					  <textarea class="form-control"  name="member_add_teach"  rows="5" placeholder="เช่น สยาม, ใกล้ BTS/MRT, อนุเสาวรีย์, ย่านเขตตลิ่งชัน, แถวม.เกษตร" required><?php echo $rs['member_add_teach'] ?></textarea>
                    </div> 
                  </div> 
                </div>
              </div>
            </div>
          </div>
        </div> 
        </div> 
		
		<div class="col-md-12">
        <div class="card-header" style="background-color:#17a2b8; color:#FFFFFF">
          <h5 class="card-title m-0"  style="padding:5px;"><strong>ประสบการณ์การสอน</strong></h5>
        </div>
        <div class="callout callout-info"  >
          <div class="fc fc-unthemed fc-ltr">
            <div class="fc-view-container" style="">
              <div class="b-table">
                <div class="table-wrapper has-mobile-cards" >  
                   <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-2 col-form-label" style="color:#FF0000">ประสบการณ์การสอน<code ></code></label> 
                    <div class="col-sm-2">
                      <div class="custom-control custom-radio">
                        <input class="custom-control-input custom-control-input-danger" type="radio" id="customRadio55_1" name="member_experience_1" value="1" <?php if($rs['member_experience_1']=='1'){?> checked="checked" <?php } ?> required>
                        <label for="customRadio55_1" class="custom-control-label">ยังไม่มีประสบการณ์</label>
                      </div>
                    </div>
                    <div class="col-sm-2">
                      <div class="custom-control custom-radio">
                        <input class="custom-control-input custom-control-input-danger" type="radio" id="customRadio55_2" name="member_experience_1" value="2" <?php if($rs['member_experience_1']=='2'){?> checked="checked" <?php } ?> required>
                        <label for="customRadio55_2" class="custom-control-label">เคยสอนมาแล้ว</label>
                      </div>
                    </div>
					
                    <div class="col-sm-3">  
					<div class="w3-button">  
					<select name="member_experience_2" id="member_experience_2" class="form-control" >
						<option value="-1"<?php if($rs['member_experience_2']=='-1'){?> selected="selected" <?php } ?>>กรุณาเลือกจำนวนปีที่เคยสอน</option>
						<option value="0" <?php if($rs['member_experience_2']=='0'){?> selected="selected" <?php } ?>>ไม่ถึง 1 ปี</option>
						<option value="1" <?php if($rs['member_experience_2']=='1'){?> selected="selected" <?php } ?>>1 ปี</option>
						<option value="2" <?php if($rs['member_experience_2']=='2'){?> selected="selected" <?php } ?>>2 ปี</option>
						<option value="3" <?php if($rs['member_experience_2']=='3'){?> selected="selected" <?php } ?>>3 ปี</option>
						<option value="4" <?php if($rs['member_experience_2']=='4'){?> selected="selected" <?php } ?>>4 ปี</option>
						<option value="5" <?php if($rs['member_experience_2']=='5'){?> selected="selected" <?php } ?>>5 ปี</option>
						<option value="6" <?php if($rs['member_experience_2']=='6'){?> selected="selected" <?php } ?>>6 ปี</option>
						<option value="7" <?php if($rs['member_experience_2']=='7'){?> selected="selected" <?php } ?>>7 ปี</option>
						<option value="8" <?php if($rs['member_experience_2']=='8'){?> selected="selected" <?php } ?>>8 ปี</option>
						<option value="9" <?php if($rs['member_experience_2']=='9'){?> selected="selected" <?php } ?>>9 ปี</option>
						<option value="10" <?php if($rs['member_experience_2']=='10'){?> selected="selected" <?php } ?>>10 ปี</option>
						<option value="11" <?php if($rs['member_experience_2']=='11'){?> selected="selected" <?php } ?>>มากกว่า 10 ปี</option>
                  </select></div>
                  </div>
                  </div>
				  
				  
				  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-3 col-form-label" > 
					    เคยสอนวิชาและระดับชั้น <code ></code></label>
                    <div class="col-sm-9"> 
					 <input type="text" name="member_experience_3" class="form-control" id="inputEmail3" value="<?php echo $rs['member_experience_3'] ?>" placeholder="เช่น สอนวิทยาศาสตร์ ป.4-5">
                    </div> 
                  </div>
				  
				  
				  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-3 col-form-label" >
					 
					    เคยติวเข้า ม.1/ม.4/มหาวิทยาลัย </label>
                    <div class="col-sm-9">  
					 <input type="text" name="member_experience_4" class="form-control" id="inputEmail3" value="<?php echo $rs['member_experience_4'] ?>" placeholder="เช่น ม.1 สาธิตจุฬาฯ">
                    </div> 
                  </div>
				  
				  
				  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-3 col-form-label" >
					 
					    ประสบการณ์สอน </label>
                    <div class="col-sm-9"> 
					  <input type="text" name="member_experience_5" class="form-control" id="inputEmail3" value="<?php echo $rs['member_experience_5'] ?>" placeholder="โรงเรียน/กวดวิชา/วิทยากร">
                    </div> 
                  </div>
				  
				  
				  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-3 col-form-label" >
					 
					    เรียนจบ/เคยทำงาน/ทำงานอยู่ </label>
                    <div class="col-sm-9"> 
					    <input type="text" name="member_experience_6" class="form-control" id="inputEmail3" value="<?php echo $rs['member_experience_6'] ?>" placeholder="อาชีพ/ลักษณะงาน บริษัท/องค์กร งาน Freelance">
                    </div> 
                  </div>
				  
				  
				  <div class="form-group row">
                    <label for="inputEmail3" class="col-sm-5 col-form-label" style="color:#FF0000" >
					 
					   ** เพิ่มเติม
ประสบการณ์สอนเยอะ จะพิจารณาเป็นพิเศษ **  </label> 
					  <textarea class="form-control" name="member_add_teach2" rows="10" placeholder="เช่น เคยสอนสอนระดับ อนุบาล/ติวสอบเข้า ม.1/สอนคณิต ป.5 ติว GAT PAT ฯลฯ (**ถ้าเขียนประสบการณ์สอนเยอะ จะพิจารณาเป็นพิเศษ)" required><?php echo $rs['member_add_teach2'] ?></textarea>
                    </div> 
                  </div>
                </div>
              </div>
            </div>
          </div> 
		
		<div class="row">  
          <div class="col-md-12 col-lg-6 col-md-12 col-xs-12">
            <!-- Widget: user widget style 1 -->
            <div class="card card-widget widget-user" >
              <!-- Add the bg color to the header using any of the bg-* classes -->
              <div class="widget-user-header bg-info"><br />
                <h3 class="widget-user-username">ข้อตกลง</h3>
              </div>
              <div class="widget-user-image">
                <img class="img-circle elevation-2" src="privacy-policy.png" width="128" height="128" alt="User Avatar"> 
              </div>
               <div class="card-footer p-0">
                <ul class="nav flex-column">
                  <li class="nav-item"> 
                     &nbsp;
					 <br /><br />

 <textarea name="textarea" readonly="" class="form-control"  style="font-size:16px !important; min-height:250px;width:100%;  ">   
 1) สนใจงานสอนไหน แจ้งรหัสงานที่สนใจ พร้อมประวัติ เบอร์โทร ประสบการณ์สอน มาก่อน หากได้งานสอน ช่วยส่ง บัตรนิสิต, บัตรประชาชน, รูปปัจจุบัน เฟส ไลน์ ไอดี รายละเอียดครั้งแรกที่รับงานครบตามที่แจ้ง
2) ตรวจสอบ วัน เวลา สถานที่ ก่อนรับงานทุกครั้ง หากตกลงรับงานแล้ว แจ้งว่า วัน เวลา สถานที่ไม่สะดวกสอน จะไม่คืนค่าแน่ะนำทุกกรณี
3) หากได้งานสอนผู้สอนต้องติดต่อผู้เรียนทันที แล้วต้องแจ้งกลับมาที่สถาบันทันที หลังจากได้คุยกับผู้เรียนแล้ว กฏของการรับงานสอนกรณีเกิดปัญหาทั่วๆไป
************************************
กรณีที่ทางเราจะไม่คืนค่าแน่ะนำ เช่น - ผู้สอนไม่สามารถสอนได้ เนื่องจากติดภารกิจหรือเหตุผลส่วนตัวไม่ว่างมาสอนอีกแล้ว
- โทรติดต่อหาผู้เรียนช้า ทำให้ผู้เรียนไปเรียนกับที่อื่น
- มีการขอขึ้นค่าสอนหรือเพิ่มราคาจากทางผู้เรียนเอง(ทางสถาบันไม่รู้เห็นด้วย)
- งานสอนที่มีเหตุเลื่อนการสอนต้องแจ้งกลับทันทีในวันที่เลื่อนสอนวันนั้น หากเลื่อนเกิน 2สัปดาห์แล้วแจ้งกลับว่ายังไม่ได้สอน จะไม่คืนค่าแน่ะนำทุกกรณี
-ผู้ติดต่อจองงานสอนต้องเป็นผู้สอนเองเท่านั้น
กรณีผู้เรียนมีการยกเลิกการเรียน หรือ สอนไปแล้วผู้เรียนไม่มั่นใจ ไม่โอเคกับความสามารถของผู้สอน - หากผู้เรียนยกเลิกงานสอนเอง (โดยเกิดปัญหาจากทางผู้เรียนเท่านั้น) และยังไม่มีการสอนเกิดขึ้น ทางสถาบันจะคืนค่าแน่ะนำให้ครบเต็มจำนวน
- ถ้ามีการสอนไปแล้ว และเกิดจากผู้เรียนยกเลิกการเรียนเอง(โดยเกิดปัญหาจากผู้เรียนเท่านั้น) ทางสถาบันจะคืนค่าแน่ะนำให้เป็นส่วนต่างให้ครบตามจำนวนค่าแน่ะนำ
*** เช่น ผู้สอนจ่ายค่าแน่ะนำมา 900บาท และผู้สอนได้สอนไปแล้ว1ครั้ง สมมติว่าได้รับเงินค่าสอนไปแล้ว 600บาท แล้วเกิดกรณีผู้เรียนยกเลิกไม่เรียน (โดยเกิดปัญหาจากผู้เรียนเอง) ทางสถาบันยินดีคืนเงินส่วนต่างให้ 300บาท - ถ้ามีการได้รับเงินค่าสอนเท่ากับหรือมากกว่าค่าแน่ะนำทางสถาบันจะไม่คืนค่าแน่ะนำ
- การคืนค่าแน่ะนำ ชื่อ-นามสกุล ที่ใช้สมัครติวเตอร์ หรือ ชื่อประวัติที่ส่งมาต้องตรงกับชื่อบัญชีธนาคาร หากไม่ตรงกันทางสถาบันไม่คืนค่าแน่ะนำให้
สำหรับงานมีปัญหา และต้องโอนค่าแน่ะนำคืน ทางสถาบันจะกำหนดวันโอน ทุกวัน พุธ หรือ อาทิตย์ นับ จากติวเตอร์โทร/ไลน์ แจ้งให้สถาบันทราบว่างานมีปัญหาและสถาบันตกลงจะคืนให้
- งานสอนที่รับไปแล้ว ถือว่ายินยอมและตกลงตามกฏระเบียบทุกข้อเลยจะ</textarea>
                  </li>
                  <li class="nav-item">
                    <a href="#" class="nav-link"> 
                   
                      <div class="icheck-danger d-inline">
                        <input type="checkbox" id="checkboxDanger2" required <?php if($rs['member_id']!=''){?> checked="checked"<?php } ?>>
                        <label for="checkboxDanger2">
                        </label>
                      </div>  
                         <span class="  badge bg-info"> ยอมรับข้อตกลง</span> 
                    </a>
                  </li> 
                </ul>
              </div>
            </div>
            <!-- /.widget-user -->
          </div>
          <!-- /.col --> 
           
          <!-- /.col -->
          <div class="col-md-12 col-lg-6 col-md-12 col-xs-12">
            <!-- Widget: user widget style 1 -->
            <div class="card card-widget widget-user">
              <!-- Add the bg color to the header using any of the bg-* classes -->
              <div class="widget-user-header bg-danger">
                <h6 class="widget-user-username"><span style="font-size:20px"><strong>การรับรองและการยินยอมให้เก็บรวบรวม<br />และเปิดเผยข้อมูลส่วนบุคคล</span></strong></h6> 
              </div>
              <div class="widget-user-image">
                <img class="img-circle elevation-2" src="7.jpg" width="128" height="128" alt="User Avatar"> 
              </div>
               <div class="card-footer p-0">
                <ul class="nav flex-column">
                  <li class="nav-item"> 
                     &nbsp;
					 <br /><br />

 <textarea name="textarea" readonly="" class="form-control"  style="font-size:16px !important; min-height:250px;width:100%;  ">    1) ข้าพเจ้าขอรับรองว่า ข้อความหรือข้อมูลที่ข้าพเจ้าได้ให้ไว้ข้างต้นทั้งหมดนี้เป็นความจริงทุกประการ หากบริษัทฯตรวจสอบพบในภายหลังว่าข้อความหรือข้อมูลส่วนหนึ่งส่วนใดเป็นเท็จหริอไม่ตรงกับความเป็นจริง ข้าพเจ้ายินยอมให้บริษัทฯเลิกจ้างได้ทันทีโดยไม่มีเงื่อนไขแต่อย่างใดทั้งสิ้น 
2) ข้าพเจ้าขอให้ความยินยอมให้บริษัทฯ เก็บรวบรวม  ใช้หรือเปิดเผยข้อมูลส่วนบุคคลของข้าพเจ้า ทั้งในส่วนข้อมูลส่วนบุคคลทั่วไปและข้อมูลส่วนบุคคลอ่อนไหว ทั้งนี้ เพื่อให้เป็นไปตามข้อกำหนดของ พรบ.คุ้มครองข้อมูลส่วนบุคคล โดยมีวัตถุประสงค์เพื่อเป็นข้อมูลประกอบในการสมัครงานและพิจารณาในการจ้างงานของข้าพเจ้าตามหลักเกณฑ์และเงื่อนไขที่บริษัทฯ กำหนดเท่านั้น<br><br>
ข้าพเจ้าได้อ่านข้อความข้างต้นแล้วเห็นว่าถูกต้องตรงตามเจตนารมณ์ของข้าพเจ้า</textarea>
                  </li>
                  <li class="nav-item">
                    <a href="#" class="nav-link"> 
                   
                      <div class="icheck-danger d-inline">
                        <input type="checkbox" id="checkboxDanger4" required <?php if($rs['member_id']!=''){?> checked="checked"<?php } ?>>
                        <label for="checkboxDanger4">
                        </label>
                      </div>  
                         <span class="  badge bg-danger"> ยอมรับข้อตกลง</span> 
                    </a>
                  </li> 
                </ul>
              </div> 
            <!-- /.widget-user -->
          </div>
          <!-- /.col -->
           
          <!-- /.col -->
        </div>
      <?php if($rs['member_id']!=''){?>
      <select name="member_status" class="custom-select form-control-border" id="exampleSelectBorder">
        <option value="0" <?php if($rs['member_status']=='0'){ ?>selected="selected" <?php } ?>>เลือกสถานะติวเตอร์</option>
        <option value="2" <?php if($rs['member_status']=='2'){?>selected="selected" <?php } ?>>อนุมัติ</option>
        <option value="1" <?php if($rs['member_status']=='1'){ ?>selected="selected" <?php } ?>>ไม่อนุมัติ</option>
      </select>
	  <?php 
	  		$text1 = 'ยืนยันแก้ไขการสมัคร';
	  
	  }else{  
		  $text1 = 'ยืนยันการสมัคร';
	  }?>
	  
	  
      <div style=" text-align:center">
        <button type="submit" class="btn btn-success " style="text-align:left; margin-top:10px; margin-left:10px;"> <span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span> <?php echo $text1;?> </button>
      </div>
    </form>
    <!-- /.row -->
	  <?php if($img_icon==''){?>
  </div>
  <?php } ?>
  <!-- /.container-fluid -->
</div>
<!-- /.content -->
</div> 
</div>
<!-- /.content-wrapper -->
<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
  <!-- Control sidebar content goes here -->
</aside>
<!-- /.control-sidebar -->
<!-- Main Footer -->
<a id="back-to-top" href="#" class="btn btn-primary back-to-top" role="button" aria-label="Scroll to top">
      <i class="fas fa-chevron-up"></i>
    </a>
<?php 
include "footer_user.php";  ?>
</div>
<!-- ./wrapper -->
<!-- REQUIRED SCRIPTS -->
</body></html><script>
function fn_approve_member(member_id) { 
	 window.location.href="menu_01.php?member_id"+member_id+"&SubmitH=Submit_5";
}
function fn_add_item_1(tmp_row_new) {
	var tmp_row=document.getElementById('example222').rows.length;
	var tem_v=document.getElementsByName('p5d_pt_id[]');
	var tem_v_total=document.getElementsByName('p5d_qty[]');
	 
	if(tem_v[tmp_row-2].value==""||tem_v_total[tmp_row-2].value=="") { return; }
	var x=document.getElementById('example222').insertRow((tmp_row*1));
	var c1=x.insertCell(0);
	var c2=x.insertCell(1);
	var c3=x.insertCell(2);
	if(((tmp_row*1)-1)=="1") { rdChk = ' checked'; }
	for (var i=0;i<=((tmp_row*1)+1); i++) {
		c1.innerHTML='<td align="center"><select id="p5d_pt_id[]" name="p5d_pt_id[]" onchange="fn_add_item_1()" class="form-control select2" style="width:100%; text-align:center;" ><option value="">--&nbsp;เลือกตำแหน่ง&nbsp;--</option><option value=1>ผู้จัดการนิติ</option><option value=2>ผู้จัดการอาคาร</option><option value=3>ผู้จัดการหมู่บ้าน</option><option value=4>ช่างเทคนิค</option><option value=5>เจ้าหน้าที่ธุรการ</option><option value=6>หัวหน้าช่างเทคนิค</option><option value=7>ผู้จัดการนิติ</option><option value=8>IT Support</option><option value=9>วิศวกร</option><option value=10>เจ้าหน้าที่ความปลอดภัย</option><option value=11>วิศวกร</option><option value=12>เจ้าหน้าที่บัญชี</option><option value=13>เจ้าหน้าที่ประสานงานอาคาร และบริการลูกค้า</option></select></td>';
		c2.innerHTML='<td><input  onkeypress="return handleEnter(this, event)" type="text" name="p5d_qty[]" id="p5d_qty[]" placeholder="จำนวน" class="form-control" style="width:100%; height:34px; text-align:center;"  onkeyup="fn_add_item_1();"/></td>';
		c3.innerHTML='<td width="87"  align="center"><a href="javascript:fn_del_row_item_1('+(i-1)+')" style="width:100%; height:100%;" class="btn btn-danger pull-right">ลบ</a></td>'; 
	}
	var table 	= document.getElementById('example222');
	var rows 	= table.getElementsByTagName("tr")[((tmp_row*1)-1)];
	var v_total_2H = document.getElementById("v_total_2H_1_1").value;
	v_total_2H = (v_total_2H*1) + 1;
	document.getElementById("v_total_2H_1_1").value = v_total_2H;
}

function fn_del_row_item_1(del_row) { 
	
	var table = document.getElementById("example222");
	var tem_v=document.getElementsByName('p5d_pt_id[]');
  	var v_total_arr		   = document.getElementsByName("p5d_qty[]");
	
	v_total_arr[(del_row-1)].value='';
	console.log(v_total_arr[(del_row-1)].value);
	if(tem_v[(del_row-1)].value=="") { return; }
	
	var rows  = table.getElementsByTagName("tr")[del_row]; 
	rows.style.display = 'none';
	//setTimeout("fn_call_tb9_sum_1()", 100);   
} 
</script>
<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Select2 -->
<script src="plugins/select2/js/select2.full.min.js"></script>
<!-- Bootstrap4 Duallistbox -->
<script src="plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js"></script>
<!-- InputMask -->
<script src="plugins/moment/moment.min.js"></script>
<script src="plugins/inputmask/jquery.inputmask.min.js"></script>
<!-- date-range-picker -->
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<!-- bootstrap color picker -->
<script src="plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Bootstrap Switch -->
<script src="plugins/bootstrap-switch/js/bootstrap-switch.min.js"></script>
<!-- BS-Stepper -->
<script src="plugins/bs-stepper/js/bs-stepper.min.js"></script>
<!-- dropzonejs -->
<script src="plugins/dropzone/min/dropzone.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>
<!-- AdminLTE for demo purposes -->
<!-- Page specific script -->
<script src="plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
<script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2').select2()

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })

    //Datemask dd/mm/yyyy
    $('#datemask').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' })
    //Datemask2 mm/dd/yyyy
    $('#datemask2').inputmask('mm/dd/yyyy', { 'placeholder': 'mm/dd/yyyy' })
    //Money Euro
    $('[data-mask]').inputmask()

    //Date picker
    $('#reservationdate').datetimepicker({
        format: 'L'
    });

    //Date and time picker
    $('#reservationdatetime').datetimepicker({ icons: { time: 'far fa-clock' } });

    //Date range picker
    $('#reservation').daterangepicker()
    //Date range picker with time picker
    $('#reservationtime').daterangepicker({
      timePicker: true,
      timePickerIncrement: 30,
      locale: {
        format: 'MM/DD/YYYY hh:mm A'
      }
    })
    //Date range as a button
    $('#daterange-btn').daterangepicker(
      {
        ranges   : {
          'Today'       : [moment(), moment()],
          'Yesterday'   : [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
          'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
          'Last 30 Days': [moment().subtract(29, 'days'), moment()],
          'This Month'  : [moment().startOf('month'), moment().endOf('month')],
          'Last Month'  : [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        startDate: moment().subtract(29, 'days'),
        endDate  : moment()
      },
      function (start, end) {
        $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'))
      }
    )

    //Timepicker
    $('#timepicker').datetimepicker({
      format: 'LT'
    })

    //Bootstrap Duallistbox
    $('.duallistbox').bootstrapDualListbox()

    //Colorpicker
    $('.my-colorpicker1').colorpicker()
    //color picker with addon
    $('.my-colorpicker2').colorpicker()

    $('.my-colorpicker2').on('colorpickerChange', function(event) {
      $('.my-colorpicker2 .fa-square').css('color', event.color.toString());
    })

    $("input[data-bootstrap-switch]").each(function(){
      $(this).bootstrapSwitch('state', $(this).prop('checked'));
    })

  })
    function ListAmphur(SelectValue,nameaddr,nameaddr2)
	{
		SelectValue = SelectValue.substr(0,2);
		var URL = "get_list.php?SelectValue=" + SelectValue + "&emp=" ;
		ajaxLoad('get', URL, '', nameaddr,'');
		
		var URL = "get_list2.php?SelectValue=xx&emp=" ;
		ajaxLoad('get', URL, '', nameaddr2,'');
	}
	function ListDistrict(SelectValue,nameaddr)
	{
		SelectValue = SelectValue.substr(0,4);
		var URL = "get_list2.php?SelectValue=" + SelectValue + "&emp=" ;
		ajaxLoad('get', URL, '', nameaddr,'');
	} 
	 $(function () {
  bsCustomFileInput.init();
});
</script>
