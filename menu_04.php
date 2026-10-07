<?php 
define("SB_M1","data_4");
define("SB_M2","data_2_1");
define("SB_M3","data_2_1_1");
include "head.php"; 
$ag_id = $_GET['ag_id'];
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js"></script>
<script type="text/javascript" src="src/jquery.qrcode.js"></script>
<script type="text/javascript" src="src/qrcode.js"></script>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h3><strong>รายการบัตร/จุดตรวจ</strong></h3>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item active"><strong>รายการบัตร-จุดตรวจ</strong></li>
			   
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            
          <div class="card">
              <div class="card-header">
                <h3 class="card-title"><strong>ข้อมูลหน่วยงาน</strong></h3>
              </div>
          <table id="example" style="font-size:10px; font-weight:bold" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>ชื่อหน่วยงาน</th>
                    <th>ชื่องาน</th>
                    <th>รายละเอียดหน่วยงาน</th> 
                    <th>พื้นที่ปฏิบัติงาน</th> 
                  </tr>
                  </thead>
                  <tbody>
				  <?php
				  $sql_h="SELECT *  
					 	FROM tb_agency  
            WHERE ag_id =  '".$ag_id."'
					  	";    
				$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
				$num_rows_h =mysqli_num_rows($query_h);
				$mPage = $_GET['mPageH'];
				$page_size=10;
				$Total_Page=ceil($num_rows_h/$page_size);
				if($mPage=="") {
					$page=1;
					$start_rec=0;
				}else{
					$page=$mPage;
					$start_rec=$page_size*($page-1);
					if($start_rec<=0) $start_rec=0;
				}
				$sql.=" 
					  LIMIT $start_rec,$page_size ";  
				$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
				$num_rows_h =mysqli_num_rows($query_h);
				$tmpbody='
				<div class="btn-group">
						';
						for($i=1;$i<=$Total_Page;$i++) {
							if($i==$page){
								$tmpbody.="<a class=\"btn btn-default\" href=\"\">$i</a>";
							}else{
								if($i==1||$i==$Total_Page){
									$tmpbody.="<a class=\"btn btn-success\" href=\"javascript:fn_pageSubmit('$i')\">$i</a>"; }
								elseif(($i<=$page+3)&&($i>=$page-3)) {
									$tmpbody.="<a class=\"btn btn-success\" href=\"javascript:fn_pageSubmit('$i')\">$i</a>"; }
								elseif(($i<=$page+6)&&($i>=$page-6)) {
									$tmpbody.=""; }
							}
						}
						$tmpbody.='
				</div>
				'; 
				if($num_rows_h>=1){   
					$iCountPg = $start_rec+1;
          
					while($rs2=mysqli_fetch_array($query_h)) {  
          $c =  $rs2['ag_contract'];
				  ?>
                  <tr onmouseover="msOverListColor(this,'#CCCCCC')" onmouseout="msOutListColor(this,'')" >
                    <td>
						<?php echo $rs2['ag_contract']?>
					</td>
          <td>
						<?php echo $rs2['ag_job']?>
					</td>
          <td>
					<?php echo $rs2['ag_details']?>
					</td> 
          <td>
					<?php echo $rs2['ag_area']?>
					</td>  
                  </tr>
			   <?php }
			   
			   }?>
                  </tbody>
                  <tfoot>
                  <tr>
                    <th>ชื่อหน่วยงาน</th>
                    <th>ชื่องาน</th>
                    <th>รายละเอียดหน่วยงาน</th> 
                    <th>พื้นที่ปฏิบัติงาน</th> 
                  </tr>
                  </tfoot>
                </table>  </div>  
				
 
         <!-- Main content -->
	<iframe id="iFm_save_target" name="iFm_save_target" src="" style="width:0px;height:0px;border:0"></iframe>
	<form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target">
	<input name="SubmitH" type="hidden" id="SubmitH" value="Submit_4" />  
	<input name="status" type="hidden" id="status" value="<?php echo $status ?>" /> 
	<input name="cp_id" type="hidden" id="cp_id" value="<?php echo $rs['cp_id'] ?>" /> 

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title"><strong>เพิ่มข้อมูลบัตร/จุดตรวจ</strong></h3>
              </div>
              <!-- /.card-header --> 
              <div class="row">
              <div class="card-body">  
              <div class="row">
                    <div class="col-sm-6">
                    <div class="form-group">
                  <label for="exampleInputBorder">ชื่อหน่วยงาน<code>*</code></label> 
                  <input readonly  type="text" name="cp_ag_id" class="form-control form-control-border" value="<?php echo $c ?>" id="exampleInputBorder" placeholder="ข้อมูลหน่วยงาน" <?php if($status=='1'){?>  <?php } ?> required>
         
                  <input  type="hidden" name="cp_ag_id" class="form-control form-control-border" value="<?php echo $ag_id?>" id="exampleInputBorder" placeholder="ข้อมูลหน่วยงาน" <?php if($status=='1'){?>  <?php } ?> required>
         </div> 
         </div> 
         </div> 
		 
              <div class="row">
		 			 <div class="col-sm-6">
				  <div class="form-group">
                  <label for="exampleSelectBorder">ชนิดบัตร <code>*</code></label>
                  <select name="cp_card_type" id="cp_card_type" class="custom-select form-control-border icon-menu" id="exampleSelectBorder" required>
                    <option value="">เลือกชนิดบัตร</option>
                    <option value="1" <?php if($rs['cp_card_type']=='1'){?> selected="selected"<?php } ?>>บัตรพนักงาน</strong></option>
                    <option value="0" <?php if($rs['cp_card_type']=='0'){?> selected="selected"<?php } ?>>จุดตรวจ</option>
                  </select>
                </div>
                  </div>
		 			 <div class="col-sm-6">
				  <div class="form-group">
                  <label for="exampleSelectBorder">หมายเลขจุด <code>*</code></label>
                  <select name="cp_point_number" id="cp_point_number" class="custom-select form-control-border icon-menu" id="exampleSelectBorder" required>
                    <option value="">เลือกหมายเลขจุด</option>
                    <option value="0" <?php if($rs['cp_point_number']=='1'){?> selected="selected"<?php } ?>>กะเช้า</strong></option>
                    <option value="1" <?php if($rs['cp_point_number']=='1'){?> selected="selected"<?php } ?>>กะดึก</strong></option>
                    <option value="2" <?php if($rs['cp_point_number']=='1'){?> selected="selected"<?php } ?>>1</strong></option>
                    <option value="3" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>2</option>
                    <option value="4" <?php if($rs['cp_point_number']=='1'){?> selected="selected"<?php } ?>>3</strong></option>
                    <option value="5" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>4</option>
                    <option value="6" <?php if($rs['cp_point_number']=='1'){?> selected="selected"<?php } ?>>5</strong></option>
                    <option value="7" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>6</option>
                    <option value="8" <?php if($rs['cp_point_number']=='1'){?> selected="selected"<?php } ?>>7</strong></option>
                    <option value="9" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>8</option>
                    <option value="10" <?php if($rs['cp_point_number']=='1'){?> selected="selected"<?php } ?>>9</strong></option>
                    <option value="11" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>10</option>
                    <option value="12" <?php if($rs['cp_point_number']=='1'){?> selected="selected"<?php } ?>>11</strong></option>
                    <option value="13" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>12</option>
                    <option value="14" <?php if($rs['cp_point_number']=='1'){?> selected="selected"<?php } ?>>13</strong></option>
                    <option value="15" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>14</option>
                    <option value="16" <?php if($rs['cp_point_number']=='1'){?> selected="selected"<?php } ?>>15</strong></option>
                    <option value="17" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>16</option>
                    <option value="18" <?php if($rs['cp_point_number']=='1'){?> selected="selected"<?php } ?>>17</strong></option>
                    <option value="19" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>18</option>
                    <option value="20" <?php if($rs['cp_point_number']=='1'){?> selected="selected"<?php } ?>>19</strong></option>
                    <option value="21" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>20</option>
                    <option value="22" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>21</option>
                    <option value="23" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>22</option>
                    <option value="24" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>23</option>
                    <option value="25" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>24</option>
                    <option value="26" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>25</option>
                    <option value="27" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>26</option>
                    <option value="28" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>27</option>
                    <option value="29" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>28</option>
                    <option value="30" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>29</option> 
                    <option value="31" <?php if($rs['cp_point_number']=='0'){?> selected="selected"<?php } ?>>30</option> 
                  </select>
                </div>
                  </div>
                  </div>  
                 
         <div class="row">
		 			 <div class="col-sm-12">
                <div class="form-group">
                  <label for="exampleInputBorder">ชื่อจุด<code>* ไม่ต้องใส่คำว่า "จุด" นำหน้า * </code></label>
                  <input  type="text" name="cp_point_name" class="form-control form-control-border" value="<?php echo $rs['cp_point_name']?>" id="exampleInputBorder" placeholder="ชื่อจุด" <?php if($status=='1'){?>  <?php } ?> required>
         </div> 

                  </div> 
                  </div> 
                  
         <div class="row">
                    <div class="col-sm-3">
				  <div class="form-group">
                  <label for="exampleSelectBorder">คำถามที่ 1<code>*</code></label>
                  <select  name="cp_nameanswer1" id="cp_nameanswer1" class="form-control select2 " style="width: 100%;" >
                
                  <option value=""><strong>เลือก</strong></option>
                  <?php
                    $sql="SELECT *
                    FROM  tb_checkproblem
                    group by cpb_groupanswer
                    "; 
                  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
                  $num_rows =mysqli_num_rows($query);
                  if ($num_rows>=1){ 
                    while($rstmp=mysqli_fetch_array($query)) {
                      ?>
                      <option style="background-color:#CCCCCC" disabled="disabled"><strong><?php echo $rstmp['cpb_groupanswer'] ?></strong></option>
                      <?
                        $sql3="SELECT *
                          FROM  tb_checkproblem 
                          WHERE cpb_groupanswer = '".$rstmp['cpb_groupanswer']."' 
                          "; 
                          echo $sql3;
                        $query3 =mysqli_query($connect,$sql3)  or die(mysqli_error($connect));
                        $num_rows3 =mysqli_num_rows($query3);
                        if ($num_rows3>=1){ 
                          while($rstmp3=mysqli_fetch_array($query3)) { ?>
                        <option value="<?php echo $rstmp3['cpb_nameanswer'] ?>" <?php if($rstmp3['cpb_nameanswer']==$rs['job_status']){?> selected="selected"<?php } ?>>&nbsp;&nbsp;-<?php echo $rstmp3['cpb_nameanswer'] ?></option>
                    <?php 
                        }
                      }
                    }
                  }
                      ?>
                  </select>
                  
                </div></div>

                  <div class="col-sm-2">
				  <div class="form-group">
                  <label for="exampleSelectBorder"></code></label>
                  <select name="cp_answerlevel1" id="cp_answerlevel1" class="custom-select form-control-border icon-menu" >
                    <option value="">เลือกระดับความสำคัญ</option>
                    <option value="1" <?php if($rs['cp_answerlevel1']=='1'){?> selected="selected"<?php } ?>>มาก</option>
                    <option value="2" <?php if($rs['cp_answerlevel1']=='2'){?> selected="selected"<?php } ?>>ปานกลาง</option>
                    <option value="3" <?php if($rs['cp_answerlevel1']=='3'){?> selected="selected"<?php } ?>>น้อย</option>
                  </select>
                </div>
                  </div>
                  <div class="col-sm-1">
                  </div>
                    

                    <div class="col-sm-3">
				  <div class="form-group">
                  <label for="exampleSelectBorder">คำถามที่ 6<code>*</code></label>
                  <select name="cp_nameanswer6" id="cp_nameanswer6" class="form-control select2 " style="width: 100%;" >
                
                  <option value=""><strong>เลือก</strong></option>
                  <?php
                    $sql="SELECT *
                    FROM  tb_checkproblem
                    group by cpb_groupanswer
                    "; 
                  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
                  $num_rows =mysqli_num_rows($query);
                  if ($num_rows>=1){ 
                    while($rstmp=mysqli_fetch_array($query)) {
                      ?>
                      <option style="background-color:#CCCCCC" disabled="disabled"><strong><?php echo $rstmp['cpb_groupanswer'] ?></strong></option>
                      <?
                        $sql3="SELECT *
                          FROM  tb_checkproblem 
                          WHERE cpb_groupanswer = '".$rstmp['cpb_groupanswer']."' 
                          "; 
                          echo $sql3;
                        $query3 =mysqli_query($connect,$sql3)  or die(mysqli_error($connect));
                        $num_rows3 =mysqli_num_rows($query3);
                        if ($num_rows3>=1){ 
                          while($rstmp3=mysqli_fetch_array($query3)) { ?>
                        <option value="<?php echo $rstmp3['cpb_nameanswer'] ?>" <?php if($rstmp3['cpb_nameanswer']==$rs['job_status']){?> selected="selected"<?php } ?>>&nbsp;&nbsp;-<?php echo $rstmp3['cpb_nameanswer'] ?></option>
                    <?php 
                        }
                      }
                    }
                  }
                      ?>
                  </select>
                  
                  
                </div>
                  </div>
                  <div class="col-sm-2">
				  <div class="form-group">
                  <label for="exampleSelectBorder"></code></label>
                  <select name="cp_answerlevel6" id="cp_answerlevel6" class="custom-select form-control-border icon-menu" id="exampleSelectBorder" >
                    <option value="">เลือกระดับความสำคัญ</option>
                    <option value="1" <?php if($rs['cp_answerlevel6']=='1'){?> selected="selected"<?php } ?>>มาก</strong></option>
                    <option value="2" <?php if($rs['cp_answerlevel6']=='2'){?> selected="selected"<?php } ?>>ปานกลาง</option>
                    <option value="3" <?php if($rs['cp_answerlevel6']=='3'){?> selected="selected"<?php } ?>>น้อย</option>
                  </select>
                </div>
                </div></div>



                <div class="row">
                    <div class="col-sm-3">
				  <div class="form-group">
                  <label for="exampleSelectBorder">คำถามที่ 2<code>*</code></label>
                  <select name="cp_nameanswer2" id="cp_nameanswer2" class="form-control select2 " style="width: 100%;" >
                
                  <option value=""><strong>เลือก</strong></option>
                  <?php
                    $sql="SELECT *
                    FROM  tb_checkproblem
                    group by cpb_groupanswer
                    "; 
                  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
                  $num_rows =mysqli_num_rows($query);
                  if ($num_rows>=1){ 
                    while($rstmp=mysqli_fetch_array($query)) {
                      ?>
                      <option style="background-color:#CCCCCC" disabled="disabled"><strong><?php echo $rstmp['cpb_groupanswer'] ?></strong></option>
                      <?
                        $sql3="SELECT *
                          FROM  tb_checkproblem 
                          WHERE cpb_groupanswer = '".$rstmp['cpb_groupanswer']."' 
                          "; 
                          echo $sql3;
                        $query3 =mysqli_query($connect,$sql3)  or die(mysqli_error($connect));
                        $num_rows3 =mysqli_num_rows($query3);
                        if ($num_rows3>=1){ 
                          while($rstmp3=mysqli_fetch_array($query3)) { ?>
                        <option value="<?php echo $rstmp3['cpb_nameanswer'] ?>" <?php if($rstmp3['cpb_nameanswer']==$rs['job_status']){?> selected="selected"<?php } ?>>&nbsp;&nbsp;-<?php echo $rstmp3['cpb_nameanswer'] ?></option>
                    <?php 
                        }
                      }
                    }
                  }
                      ?>
                  </select>
                  
                  
                </div>
                  </div>
                  <div class="col-sm-2">
				  <div class="form-group">
                  <label for="exampleSelectBorder"></code></label>
                  <select name="cp_answerlevel2" id="cp_answerlevel2" class="custom-select form-control-border icon-menu" id="exampleSelectBorder" >
                    <option value="">เลือกระดับความสำคัญ</option>
                    <option value="1" <?php if($rs['cp_answerlevel2']=='1'){?> selected="selected"<?php } ?>>มาก</strong></option>
                    <option value="2" <?php if($rs['cp_answerlevel2']=='2'){?> selected="selected"<?php } ?>>ปานกลาง</option>
                    <option value="3" <?php if($rs['cp_answerlevel2']=='3'){?> selected="selected"<?php } ?>>น้อย</option>
                  </select>
                </div>
                  </div>

                  <div class="col-sm-1">
                  </div>
                    


                    <div class="col-sm-3">
				  <div class="form-group">
                  <label for="exampleSelectBorder">คำถามที่ 7<code>*</code></label>
                  <select name="cp_nameanswer7" id="cp_nameanswer7" class="form-control select2 " style="width: 100%;" data-select2-id="9" tabindex="-1" aria-hidden="true">
               
                  <option value=""><strong>เลือก</strong></option>
                  <?php
                    $sql="SELECT *
                    FROM  tb_checkproblem
                    group by cpb_groupanswer
                    "; 
                  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
                  $num_rows =mysqli_num_rows($query);
                  if ($num_rows>=1){ 
                    while($rstmp=mysqli_fetch_array($query)) {
                      ?>
                      <option style="background-color:#CCCCCC" disabled="disabled"><strong><?php echo $rstmp['cpb_groupanswer'] ?></strong></option>
                      <?
                        $sql3="SELECT *
                          FROM  tb_checkproblem 
                          WHERE cpb_groupanswer = '".$rstmp['cpb_groupanswer']."' 
                          "; 
                          echo $sql3;
                        $query3 =mysqli_query($connect,$sql3)  or die(mysqli_error($connect));
                        $num_rows3 =mysqli_num_rows($query3);
                        if ($num_rows3>=1){ 
                          while($rstmp3=mysqli_fetch_array($query3)) { ?>
                        <option value="<?php echo $rstmp3['cpb_nameanswer'] ?>" <?php if($rstmp3['cpb_nameanswer']==$rs['job_status']){?> selected="selected"<?php } ?>>&nbsp;&nbsp;-<?php echo $rstmp3['cpb_nameanswer'] ?></option>
                    <?php 
                        }
                      }
                    }
                  }
                      ?>
                  </select>
                  
                  
                </div>
                  </div>  
                  <div class="col-sm-2">
                    
				  <div class="form-group">
                  <label for="exampleSelectBorder"></code></label>
                  <select name="cp_answerlevel7" id="cp_answerlevel7" class="custom-select form-control-border icon-menu"  >
                    <option value="">เลือกระดับความสำคัญ</option>
                    <option value="1" <?php if($rs['cp_answerlevel7']=='1'){?> selected="selected"<?php } ?>>มาก</strong></option>
                    <option value="2" <?php if($rs['cp_answerlevel7']=='2'){?> selected="selected"<?php } ?>>ปานกลาง</option>
                    <option value="3" <?php if($rs['cp_answerlevel7']=='3'){?> selected="selected"<?php } ?>>น้อย</option>
                  </select>
                </div>
                  </div>
                  </div> 

                  <div class="row">
                    <div class="col-sm-3">
				  <div class="form-group">
                  <label for="exampleSelectBorder">คำถามที่ 3<code>*</code></label>
                  <select name="cp_nameanswer3" id="cp_nameanswer3" class="form-control select2 " style="width: 100%;" >
                
                  <option value=""><strong>เลือก</strong></option>
                  <?php
                    $sql="SELECT *
                    FROM  tb_checkproblem
                    group by cpb_groupanswer
                    "; 
                  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
                  $num_rows =mysqli_num_rows($query);
                  if ($num_rows>=1){ 
                    while($rstmp=mysqli_fetch_array($query)) {
                      ?>
                      <option style="background-color:#CCCCCC" disabled="disabled"><strong><?php echo $rstmp['cpb_groupanswer'] ?></strong></option>
                      <?
                        $sql3="SELECT *
                          FROM  tb_checkproblem 
                          WHERE cpb_groupanswer = '".$rstmp['cpb_groupanswer']."' 
                          "; 
                          echo $sql3;
                        $query3 =mysqli_query($connect,$sql3)  or die(mysqli_error($connect));
                        $num_rows3 =mysqli_num_rows($query3);
                        if ($num_rows3>=1){ 
                          while($rstmp3=mysqli_fetch_array($query3)) { ?>
                        <option value="<?php echo $rstmp3['cpb_nameanswer'] ?>" <?php if($rstmp3['cpb_nameanswer']==$rs['job_status']){?> selected="selected"<?php } ?>>&nbsp;&nbsp;-<?php echo $rstmp3['cpb_nameanswer'] ?></option>
                    <?php 
                        }
                      }
                    }
                  }
                      ?>
                  </select>
                  
                  
                </div>
                  </div>
                  <div class="col-sm-2">
				  <div class="form-group">
                  <label for="exampleSelectBorder"></code></label>
                  <select name="cp_answerlevel3" id="cp_answerlevel3" class="custom-select form-control-border icon-menu" id="exampleSelectBorder" >
                    <option value="">เลือกระดับความสำคัญ</option>
                    <option value="1" <?php if($rs['cp_answerlevel3']=='1'){?> selected="selected"<?php } ?>>มาก</strong></option>
                    <option value="2" <?php if($rs['cp_answerlevel3']=='2'){?> selected="selected"<?php } ?>>ปานกลาง</option>
                    <option value="3" <?php if($rs['cp_answerlevel3']=='3'){?> selected="selected"<?php } ?>>น้อย</option>
                  </select>
                </div>
                  </div>



                  <div class="col-sm-1">
                  </div>
                    

                  

                    <div class="col-sm-3">
				  <div class="form-group">
                  <label for="exampleSelectBorder">คำถามที่ 8<code>*</code></label>
                  <select name="cp_nameanswer8" id="cp_nameanswer8" class="form-control select2 " style="width: 100%;" data-select2-id="9" tabindex="-1" aria-hidden="true">
               
                  <option value=""><strong>เลือก</strong></option>
                  <?php
                    $sql="SELECT *
                    FROM  tb_checkproblem
                    group by cpb_groupanswer
                    "; 
                  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
                  $num_rows =mysqli_num_rows($query);
                  if ($num_rows>=1){ 
                    while($rstmp=mysqli_fetch_array($query)) {
                      ?>
                      <option style="background-color:#CCCCCC" disabled="disabled"><strong><?php echo $rstmp['cpb_groupanswer'] ?></strong></option>
                      <?
                        $sql3="SELECT *
                          FROM  tb_checkproblem 
                          WHERE cpb_groupanswer = '".$rstmp['cpb_groupanswer']."' 
                          "; 
                          echo $sql3;
                        $query3 =mysqli_query($connect,$sql3)  or die(mysqli_error($connect));
                        $num_rows3 =mysqli_num_rows($query3);
                        if ($num_rows3>=1){ 
                          while($rstmp3=mysqli_fetch_array($query3)) { ?>
                        <option value="<?php echo $rstmp3['cpb_nameanswer'] ?>" <?php if($rstmp3['cpb_nameanswer']==$rs['job_status']){?> selected="selected"<?php } ?>>&nbsp;&nbsp;-<?php echo $rstmp3['cpb_nameanswer'] ?></option>
                    <?php 
                        }
                      }
                    }
                  }
                      ?>
                  </select>
                  
                  
                </div>
                  </div>  
                  <div class="col-sm-2">
                    
				  <div class="form-group">
                  <label for="exampleSelectBorder"></code></label>
                  <select name="cp_answerlevel8" id="cp_answerlevel8" class="custom-select form-control-border icon-menu"  >
                    <option value="">เลือกระดับความสำคัญ</option>
                    <option value="1" <?php if($rs['cp_answerlevel8']=='1'){?> selected="selected"<?php } ?>>มาก</strong></option>
                    <option value="2" <?php if($rs['cp_answerlevel8']=='2'){?> selected="selected"<?php } ?>>ปานกลาง</option>
                    <option value="3" <?php if($rs['cp_answerlevel8']=='3'){?> selected="selected"<?php } ?>>น้อย</option>
                  </select>
                </div>
                  </div>
                  </div> 

                  <div class="row">
                    <div class="col-sm-3">
				  <div class="form-group">
                  <label for="exampleSelectBorder">คำถามที่ 4<code>*</code></label>
                  <select name="cp_nameanswer4" id="cp_nameanswer4" class="form-control select2 " style="width: 100%;" >
                
                  <option value=""><strong>เลือก</strong></option>
                  <?php
                    $sql="SELECT *
                    FROM  tb_checkproblem
                    group by cpb_groupanswer
                    "; 
                  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
                  $num_rows =mysqli_num_rows($query);
                  if ($num_rows>=1){ 
                    while($rstmp=mysqli_fetch_array($query)) {
                      ?>
                      <option style="background-color:#CCCCCC" disabled="disabled"><strong><?php echo $rstmp['cpb_groupanswer'] ?></strong></option>
                      <?
                        $sql3="SELECT *
                          FROM  tb_checkproblem 
                          WHERE cpb_groupanswer = '".$rstmp['cpb_groupanswer']."' 
                          "; 
                          echo $sql3;
                        $query3 =mysqli_query($connect,$sql3)  or die(mysqli_error($connect));
                        $num_rows3 =mysqli_num_rows($query3);
                        if ($num_rows3>=1){ 
                          while($rstmp3=mysqli_fetch_array($query3)) { ?>
                        <option value="<?php echo $rstmp3['cpb_nameanswer'] ?>" <?php if($rstmp3['cpb_nameanswer']==$rs['job_status']){?> selected="selected"<?php } ?>>&nbsp;&nbsp;-<?php echo $rstmp3['cpb_nameanswer'] ?></option>
                    <?php 
                        }
                      }
                    }
                  }
                      ?>
                  </select>
                  
                  
                </div>
                  </div>
                  <div class="col-sm-2">
				  <div class="form-group">
                  <label for="exampleSelectBorder"></code></label>
                  <select name="cp_answerlevel4" id="cp_answerlevel4" class="custom-select form-control-border icon-menu" id="exampleSelectBorder" >
                    <option value="">เลือกระดับความสำคัญ</option>
                    <option value="1" <?php if($rs['cp_answerlevel4']=='1'){?> selected="selected"<?php } ?>>มาก</strong></option>
                    <option value="2" <?php if($rs['cp_answerlevel4']=='2'){?> selected="selected"<?php } ?>>ปานกลาง</option>
                    <option value="3" <?php if($rs['cp_answerlevel4']=='3'){?> selected="selected"<?php } ?>>น้อย</option>
                  </select>
                </div>
                  </div>

                  <div class="col-sm-1">
                  </div>
                    

                  

                  

                    <div class="col-sm-3">
				  <div class="form-group">
                  <label for="exampleSelectBorder">คำถามที่ 9<code>*</code></label>
                  <select name="cp_nameanswer9" id="cp_nameanswer9" class="form-control select2 " style="width: 100%;" data-select2-id="9" tabindex="-1" aria-hidden="true">
               
                  <option value=""><strong>เลือก</strong></option>
                  <?php
                    $sql="SELECT *
                    FROM  tb_checkproblem
                    group by cpb_groupanswer
                    "; 
                  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
                  $num_rows =mysqli_num_rows($query);
                  if ($num_rows>=1){ 
                    while($rstmp=mysqli_fetch_array($query)) {
                      ?>
                      <option style="background-color:#CCCCCC" disabled="disabled"><strong><?php echo $rstmp['cpb_groupanswer'] ?></strong></option>
                      <?
                        $sql3="SELECT *
                          FROM  tb_checkproblem 
                          WHERE cpb_groupanswer = '".$rstmp['cpb_groupanswer']."' 
                          "; 
                          echo $sql3;
                        $query3 =mysqli_query($connect,$sql3)  or die(mysqli_error($connect));
                        $num_rows3 =mysqli_num_rows($query3);
                        if ($num_rows3>=1){ 
                          while($rstmp3=mysqli_fetch_array($query3)) { ?>
                        <option value="<?php echo $rstmp3['cpb_nameanswer'] ?>" <?php if($rstmp3['cpb_nameanswer']==$rs['job_status']){?> selected="selected"<?php } ?>>&nbsp;&nbsp;-<?php echo $rstmp3['cpb_nameanswer'] ?></option>
                    <?php 
                        }
                      }
                    }
                  }
                      ?>
                  </select>
                  
                  
                </div>
                  </div>  
                  <div class="col-sm-2">
                    
				  <div class="form-group">
                  <label for="exampleSelectBorder"></code></label>
                  <select name="cp_answerlevel9" id="cp_answerlevel9" class="custom-select form-control-border icon-menu"  >
                    <option value="">เลือกระดับความสำคัญ</option>
                    <option value="1" <?php if($rs['cp_answerlevel9']=='1'){?> selected="selected"<?php } ?>>มาก</strong></option>
                    <option value="2" <?php if($rs['cp_answerlevel9']=='2'){?> selected="selected"<?php } ?>>ปานกลาง</option>
                    <option value="3" <?php if($rs['cp_answerlevel9']=='3'){?> selected="selected"<?php } ?>>น้อย</option>
                  </select>
                </div>
                  </div>
                  </div> 

                  <div class="row">
                    <div class="col-sm-3">
				  <div class="form-group">
                  <label for="exampleSelectBorder">คำถามที่ 5<code>*</code></label>
                  <select name="cp_nameanswer5" id="cp_nameanswer5" class="form-control select2 " style="width: 100%;" >
                
                  <option value=""><strong>เลือก</strong></option>
                  <?php
                    $sql="SELECT *
                    FROM  tb_checkproblem
                    group by cpb_groupanswer
                    "; 
                  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
                  $num_rows =mysqli_num_rows($query);
                  if ($num_rows>=1){ 
                    while($rstmp=mysqli_fetch_array($query)) {
                      ?>
                      <option style="background-color:#CCCCCC" disabled="disabled"><strong><?php echo $rstmp['cpb_groupanswer'] ?></strong></option>
                      <?
                        $sql3="SELECT *
                          FROM  tb_checkproblem 
                          WHERE cpb_groupanswer = '".$rstmp['cpb_groupanswer']."' 
                          "; 
                          echo $sql3;
                        $query3 =mysqli_query($connect,$sql3)  or die(mysqli_error($connect));
                        $num_rows3 =mysqli_num_rows($query3);
                        if ($num_rows3>=1){ 
                          while($rstmp3=mysqli_fetch_array($query3)) { ?>
                        <option value="<?php echo $rstmp3['cpb_nameanswer'] ?>" <?php if($rstmp3['cpb_nameanswer']==$rs['job_status']){?> selected="selected"<?php } ?>>&nbsp;&nbsp;-<?php echo $rstmp3['cpb_nameanswer'] ?></option>
                    <?php 
                        }
                      }
                    }
                  }
                      ?>
                  </select>
                  
                  
                </div>
                  </div>
                  <div class="col-sm-2">
				  <div class="form-group">
                  <label for="exampleSelectBorder"></code></label>
                  <select name="cp_answerlevel5" id="cp_answerlevel5" class="custom-select form-control-border icon-menu" id="exampleSelectBorder" >
                    <option value="">เลือกระดับความสำคัญ</option>
                    <option value="1" <?php if($rs['cp_answerlevel5']=='1'){?> selected="selected"<?php } ?>>มาก</strong></option>
                    <option value="2" <?php if($rs['cp_answerlevel5']=='2'){?> selected="selected"<?php } ?>>ปานกลาง</option>
                    <option value="3" <?php if($rs['cp_answerlevel5']=='3'){?> selected="selected"<?php } ?>>น้อย</option>
                  </select>
                </div>
                  </div>
                  
                  <div class="col-sm-1">
                  </div>
                    

                    <div class="col-sm-3">
				  <div class="form-group">
                  <label for="exampleSelectBorder">คำถามที่ 10<code>*</code></label>
                  
                  <select name="cp_nameanswer10" id="cp_nameanswer10" class="form-control select2 " style="width: 100%;" data-select2-id="9" tabindex="-1" aria-hidden="true">
               
                  <option value=""><strong>เลือก</strong></option>
                  <?php
                    $sql="SELECT *
                    FROM  tb_checkproblem
                    group by cpb_groupanswer
                    "; 
                  $query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
                  $num_rows =mysqli_num_rows($query);
                  if ($num_rows>=1){ 
                    while($rstmp=mysqli_fetch_array($query)) {
                      ?>
                      <option style="background-color:#CCCCCC" disabled="disabled"><strong><?php echo $rstmp['cpb_groupanswer'] ?></strong></option>
                      <?
                        $sql3="SELECT *
                          FROM  tb_checkproblem 
                          WHERE cpb_groupanswer = '".$rstmp['cpb_groupanswer']."' 
                          "; 
                          echo $sql3;
                        $query3 =mysqli_query($connect,$sql3)  or die(mysqli_error($connect));
                        $num_rows3 =mysqli_num_rows($query3);
                        if ($num_rows3>=1){ 
                          while($rstmp3=mysqli_fetch_array($query3)) { ?>
                        <option value="<?php echo $rstmp3['cpb_nameanswer'] ?>" <?php if($rstmp3['cpb_nameanswer']==$rs['job_status']){?> selected="selected"<?php } ?>>&nbsp;&nbsp;-<?php echo $rstmp3['cpb_nameanswer'] ?></option>
                    <?php 
                        }
                      }
                    }
                  }
                      ?>
                  </select>
                  
                  
                </div>
                  </div>  
                  <div class="col-sm-2">
                    
				  <div class="form-group">
                  <label for="exampleSelectBorder"></code></label>
                  <select name="cp_answerlevel10" id="cp_answerlevel10" class="custom-select form-control-border icon-menu"  >
                    <option value="">เลือกระดับความสำคัญ</option>
                    <option value="1" <?php if($rs['cp_answerlevel10']=='1'){?> selected="selected"<?php } ?>>มาก</strong></option>
                    <option value="2" <?php if($rs['cp_answerlevel10']=='2'){?> selected="selected"<?php } ?>>ปานกลาง</option>
                    <option value="3" <?php if($rs['cp_answerlevel10']=='3'){?> selected="selected"<?php } ?>>น้อย</option>
                  </select>
                </div>
                  </div>
                  </div> 

                  

				  <div style="float:left">
				  <button type="submit" class="btn btn-success " style="text-align:left; margin-top:10px; margin-left:10px;">
						<span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span> บันทึกข้อมูล </button>  
				   <!--<button onclick="fn_cancel_doc()"  class="btn btn-warning"><span class="glyphicon glyphicon-save"></span> ยกเลิก </button>  -->
					</div></div>
				  
				  
				  </div></form>
      <!-- /.container-fluid -->
    </section>
            <div class="card">
              <div class="card-header">
                <h3 class="card-title"><strong>ข้อมูลบัตร/จุดตรวจ</strong></h3>
              </div>
              <!-- /.card-header -->
              
              

              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>หมายเลขจุด</th>
                    <th>ชื่อจุด</th>
                    <th>ชนิดบัตร</th> 
                    <th>QR code</th>
                    <th>ดำเนินการ</th>
                  </tr>
                  </thead>
                  <tbody>
				  <?php
				  $sql_h="SELECT *  
					 	FROM tb_checkpoint  
            WHERE cp_ag_id =  '".$ag_id."'
					  	";    
             // echo $sql_h;
				$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
				$num_rows_h =mysqli_num_rows($query_h);
				$mPage = $_GET['mPageH'];
				$page_size=10;
				$Total_Page=ceil($num_rows_h/$page_size);
				if($mPage=="") {
					$page=1;
					$start_rec=0;
				}else{
					$page=$mPage;
					$start_rec=$page_size*($page-1);
					if($start_rec<=0) $start_rec=0;
				}
				$sql.=" 
					  LIMIT $start_rec,$page_size ";  
				$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
				$num_rows_h =mysqli_num_rows($query_h);
				$tmpbody='
				<div class="btn-group">
						';
						for($i=1;$i<=$Total_Page;$i++) {
							if($i==$page){
								$tmpbody.="<a class=\"btn btn-default\" href=\"\">$i</a>";
							}else{
								if($i==1||$i==$Total_Page){
									$tmpbody.="<a class=\"btn btn-success\" href=\"javascript:fn_pageSubmit('$i')\">$i</a>"; }
								elseif(($i<=$page+3)&&($i>=$page-3)) {
									$tmpbody.="<a class=\"btn btn-success\" href=\"javascript:fn_pageSubmit('$i')\">$i</a>"; }
								elseif(($i<=$page+6)&&($i>=$page-6)) {
									$tmpbody.=""; }
							}
						}
						$tmpbody.='
				</div>
				';
				if($num_rows_h>=1){   
					$iCountPg = 1;
					while($rs=mysqli_fetch_array($query_h)) {  
            ?>
           
				 
                  <tr onmouseover="msOverListColor(this,'#CCCCCC')" onmouseout="msOutListColor(this,'')" >
                    <td onclick="fn_gotourl('menu_04_04_add.php?cp_id=<?php echo $rs['cp_id']?>&status=1&img_icon=glyphicon-inbox')">
						 <?php if($rs['cp_point_number']=='0'){?> กะเช้า<?php }elseif($rs['cp_point_number']=='1'){ ?>กะดึก<?php }else{ ?> <?php echo ($rs['cp_point_number']-1)?>  <?php } ?>
					</td>
                    <td onclick="fn_gotourl('menu_04_04_add.php?cp_id=<?php echo $rs['cp_id']?>&status=1&img_icon=glyphicon-inbox')">
						<?php echo $rs['cp_point_name']?>  <br />
						<br />คำถาม<br />

						<?php echo $rs['cp_nameanswer1']?>  <br />

						<?php echo $rs['cp_nameanswer2']?>  <br />

						<?php echo $rs['cp_nameanswer3']?>  
						
					</td>
                    <td onclick="fn_gotourl('menu_04_04_add.php?cp_id=<?php echo $rs['cp_id']?>&status=1&img_icon=glyphicon-inbox')">
					 <?php if($rs['cp_card_type']=='1'){?> บัตรพนักงาน<?php }else{ ?>จุดตรวจ<?php } ?>
					</td> 
                    <td onclick="fn_gotourl('menu_04_04_add.php?cp_id=<?php echo $rs['cp_id']?>&status=1&img_icon=glyphicon-inbox')">
					<div id="qrcodeCanvas<?php echo $iCountPg ?>"></div>
					</td> 
                 
                    <td> 
					<a href="menu_04_04_add.php?cp_id=<?php echo $rs['cp_id']?>&status=1"  role="button" data-toggle="tooltip" data-placement="top" title="ดูข้อมูล" class="btn btn-xs  btn-info "><i class="fa fa-fw fa-cogs"></i></i>แก้ไขข้อมูลบัตร/จุดตรวจ<a> 
					<a target="_blank" onclick="fn_delete('<?php echo $rs['cp_id']?>')" role="button" data-toggle="tooltip" data-placement="top" title="ดูข้อมูล" class="btn btn-xs  btn-danger "><li class="fas fa-trash"></li><a><br><br>
					<a href="print_qr.php?cp_id=<?php echo $rs['cp_id']?>&cp_ag_id=<?php echo $rs['cp_ag_id']?>&cp_point_number=<?php echo $rs['cp_point_number']?>&status=1"  role="button" data-toggle="tooltip" data-placement="top" title="ดูข้อมูล" class="btn btn-xs  btn-info "><i class="fa fa-fw fa-print"></i></i>ปริ้น Qrcode<a> 
					</td> 
                  </tr>
                  <script>
	//jQuery('#qrcode').qrcode("this plugin is great"); 
	jQuery('#qrcodeCanvas<?php echo $iCountPg ?>').qrcode({
		text	: "https://develophpg.net/timeatt/check.php?cp_ag_id=<?php echo $rs['cp_ag_id']?>&cp_point_number=<?php echo $rs['cp_point_number']?>" 
	});	
</script>
			   <?php 
			   $iCountPg++;
			   	}
			   
			   }?>
                  </tbody>
                  <tfoot>
                  <tr>
                    <th>หมายเลขจุด</th>
                    <th>ชื่อจุด</th>
                    <th>ชนิดบัตร</th> 
                    <th>QR code</th>
                    <th>ดำเนินการ</th>
                  </tr>
                  </tfoot>
                </table>
              </div>
              <!-- /.card-body --> 
            </div>
            <!-- /.card --> 
          </div>
          <!-- /.col -->
          
        </div>
        <!-- /.row -->
        
      </div>
      
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  
 <?php include "footer.php"; ?>
 <!-- DataTables  & Plugins -->
 <script src="plugins/select2/js/select2.full.min.js"></script>
<script src="plugins/datatables/jquery.dataTables.min.js"></script>
<script src="plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="plugins/jszip/jszip.min.js"></script>
<script src="plugins/pdfmake/pdfmake.min.js"></script>
<script src="plugins/pdfmake/vfs_fonts.js"></script>
<script src="plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="plugins/datatables-buttons/js/buttons.colVis.min.js"></script>
<script>
  $(function () {
    $("#example1").DataTable({
      "responsive": true, "lengthChange": true, "autoWidth": true,
      "buttons": ["copy",  "excel",  "print", "colvis"]
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
    $('#example2').DataTable({
      "paging": true,
      "lengthChange": false,
      "searching": false,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true,
    });
  }); 
	function msOverListColor(el,tarcolor) {
		el.bgColor = tarcolor;
		el.style.cursor = 'pointer';
	}
	function msOutListColor(el,tarcolor) {
		el.bgColor = tarcolor;
		if (typeof fn_view_approve_list === "function") {
			fn_view_approve_list('','');
		}
	}
	function msOverList(el,imgtar) {
		el.style.backgroundImage="url("+imgtar+")";
		el.style.cursor = 'pointer';
	}
	function msOutList(el,imgtar) {
		el.style.backgroundImage = "url("+imgtar+")";
	}
	function fn_gotourl(mLink) {
		window.location.href=mLink;
	}
	function fn_delete(rsmp_id) { 
		if (confirm('ต้องการลบข้อมูลใช่หรือไม่')) {
			window.location.href="fn_save_center.php?user_id="+rsmp_id+"&SubmitH=Submit_1_1";
		}
	}
</script>

