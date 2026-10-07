<?php 
define("SB_M1","data_1");
define("SB_M2","data_2_1");
define("SB_M3","data_2_1_1");
include "head.php"; 
//$SubmitH = $_POST['SubmitH'];
//if($SubmitH=="update"){
//	include "config_ctrl/checksession.php";
//	include "config_ctrl/connect.php";
//	$user_id			= $_POST['user_idH'];
//	$process_id			= $_POST['process_id'];
//	$ctrl_add			= $_POST['ctrl_add'];
//	$ctrl_edit			= $_POST['ctrl_edit'];
//	$ctrl_del			= $_POST['ctrl_del'];
//	$ctrl_view			= $_POST['ctrl_view'];
//	$ctrl_print			= $_POST['ctrl_print'];
//	$ctrl_appv			= $_POST['ctrl_appv'];
//	$iCountArr = 0;
//	foreach ($process_id as &$process_id_value) {
//		$iCountArr++;
//		$sql="DELETE FROM tb_permission_process
//			  WHERE user_id = '".$user_id."'
//			  AND process_id = '".$process_id_value."'
//			";
//		include "inc_logging.php"; 
//			$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
//		if(($ctrl_add[$iCountArr]!="")
//		 ||($ctrl_edit[$iCountArr]!="")
//		 ||($ctrl_del[$iCountArr]!="")
//		 ||($ctrl_view[$iCountArr]!="")
//		 ||($ctrl_print[$iCountArr]!="")
//		 ||($ctrl_appv[$iCountArr]!="")) {
//			$sql="INSERT INTO tb_permission_process SET
//				   ctrl_add 		= '".$ctrl_add[$iCountArr]."'
//				  ,ctrl_edit 		= '".$ctrl_edit[$iCountArr]."'
//				  ,ctrl_del 		= '".$ctrl_del[$iCountArr]."'
//				  ,ctrl_view 		= '".$ctrl_view[$iCountArr]."'
//				  ,ctrl_print 		= '".$ctrl_print[$iCountArr]."'
//				  ,ctrl_appv 		= '".$ctrl_appv[$iCountArr]."'
//				  ,user_id			= '".$user_id."'
//				  ,process_id		= '".$process_id_value."'
//				"; 
//			include "inc_logging.php"; 
//			$res_m=mysqli_query($connect,$sql) or die(mysqli_error($connect));
//		}
//	}
//}

?>

<script type="text/javascript" src="ajax/ajax_framework.js"></script>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h3><strong>รายการผู้ใช้งาน</strong></h3>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item active"><strong>รายการผู้ใช้งาน</strong></li>
              <li class="breadcrumb-item"><a href="menu_01_01_add.php"><strong>เพิ่มผู้ใช้งานระบบ</strong><a> 
			   
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
                <h3 class="card-title"><strong>ข้อมูลผู้ใช้งาน</strong></h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                 
				<table style="width:100%;" border="0" cellspacing="0" cellpadding="0" class="text13normal" align="center"> 
	<td align="center" >
	<?php 
	include "config_ctrl/checksession.php";
	include "config_ctrl/connect.php";
	 $sql_h="SELECT *  
			FROM tb_user 
			WHERE user_id = '".$_POST['user_id']."' 
			";   
	$query_h =mysqli_query($connect,$sql_h) or die(mysqli_error($connect));
	$num_rows_h =mysqli_num_rows($query_h);  
	$rs=mysqli_fetch_array($query_h);
	
				if($num_rows_h>=1){ 
	?>		รหัสเข้าใข้งาน <?php echo $rs['user_id']?>
	คุณ<?php echo $rs['user_name']?>
		<?php echo $rs['user_fname']?>
		<?php } ?>	</td>
  </tr>
  <tr>
	<td colspan="2">
	<table width="100%" border="0" cellspacing="0" cellpadding="0"> 
	<?
	$user_id		= $_POST['user_id'];
	$search_module	= $_POST['search_module'];
	$sql5="SELECT *
	  FROM tb_process_ms_ctrl
	  WHERE ody <> 0 
	  "; 
	$sql5.="ORDER BY ody ASC";
	$query5 =mysqli_query($connect,$sql5) or die(mysqli_error($connect));
	$num_rows5 =mysqli_num_rows($query5);
	if ($num_rows5>=1){
		$iCountProcess = 0;
		while($rs5=mysqli_fetch_array($query5)) { 
	?> 
	 <tr bgcolor="#F3F3F3">
					<td colspan="8" class="brdrfashion"><strong><?=$rs5['process_name']?></strong></td> 
				  </tr>
	<?
			$sql="SELECT *
				  FROM tb_process_ctrl
				  WHERE proces_order <> 0 
				  AND ms_process_id = '".$rs5['ms_process_id']."'
				  ";
			if($search_module!="") {
				$sql.="AND ms_process_id = '".$search_module."'";
			}
			$sql.="ORDER BY proces_order ASC";
			$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
			$num_rows =mysqli_num_rows($query);
			if ($num_rows>=1){
				while($rs=mysqli_fetch_array($query)) { 
					$iCountProcess++;
					if($rs['ctrl_add']=="0") 		{ $rs['ctrl_add'] 			= ' disabled'; }
					if($rs['ctrl_edit']=="0") 		{ $rs['ctrl_edit'] 			= ' disabled'; }
					if($rs['ctrl_del']=="0") 		{ $rs['ctrl_del'] 			= ' disabled'; }
					if($rs['ctrl_view']=="0") 		{ $rs['ctrl_view'] 			= ' disabled'; }
					if($rs['ctrl_print']=="0") 		{ $rs['ctrl_print'] 		= ' disabled'; }
					if($rs['ctrl_appv']=="0") 		{ $rs['ctrl_appv'] 			= ' disabled'; }
					unset($rs_sb);
					$sql_sb="SELECT *
							 FROM tb_permission_process
							 WHERE user_id = '".$user_id."'
							 AND process_id = '".$rs['id']."'
							";
						//	echo $sql_sb;
					$query_sb =mysqli_query($connect,$sql_sb) or die(mysqli_error($connect));
					$num_rows_sb =mysqli_num_rows($query_sb);
					if ($num_rows_sb>=1){	
						$rs_sb=mysqli_fetch_array($query_sb);
						if($rs_sb['ctrl_add']!="0") 		{ $rs_sb['ctrl_add'] 		= ' checked'; }
						if($rs_sb['ctrl_edit']!="0") 		{ $rs_sb['ctrl_edit'] 		= ' checked'; }
						if($rs_sb['ctrl_del']!="0") 		{ $rs_sb['ctrl_del'] 		= ' checked'; }
						if($rs_sb['ctrl_view']!="0") 		{ $rs_sb['ctrl_view'] 		= ' checked'; }
						if($rs_sb['ctrl_print']!="0") 		{ $rs_sb['ctrl_print'] 		= ' checked'; }
						if($rs_sb['ctrl_appv']!="0") 		{ $rs_sb['ctrl_appv'] 		= ' checked'; }
					}
				  ?>
				  <tr onmouseover="msOverListColor(this,'#CCCCCC')" onmouseout="msOutListColor(this,'')" >
				    <td class="brdrfashion">&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php// echo $iCountProcess?></td>
					<td class="brdrfashion">(<?=$rs['id']?>) <?=$rs['process_name']?></td>
					<td class="brdrfashion"><table width="100%" cellpadding="0" cellspacing="0" border="1" style=" border-color: #999999; border:hidden; font-family:THSarabunNew;" >
                      <?php
							$sql_sb="SELECT tbPer.*
									  ,tbUsr.user_name 
									 FROM tb_permission_process_approve AS tbPer 
									 LEFT JOIN tb_user AS tbUsr
									 ON tbUsr.user_id = tbPer.user_id     
									 WHERE process_id = '".$rs['id']."' 
									 ORDER BY approve_level ASC 
									 ";
									// echo $sql_sb;
							$query_sb =mysqli_query($connect,$sql_sb)  or die(mysqli_error($connect));
							$num_rows_sb =mysqli_num_rows($query_sb);
							if($num_rows_sb>=1){
								while($rs_sb=mysqli_fetch_array($query_sb)) { 
										$user_name = $rs_sb['user_name'];
										$tmpImg = '<i class="fa fa-fw fa-user"></i>'; 
									$isapce = '';
									for($x=1;$x<$rs_sb['approve_level'];$x++) {
										$isapce.= '&nbsp;';
										$isapce.= '&nbsp;';
										$isapce.= '&nbsp;';
									} 
								?>
                      <tr>
                        <td width="712" bgcolor="#E9E9E9" style="border-bottom-color: #000000; border-bottom-style: dotted;"><?php echo $isapce?> &rsaquo;<?php echo $rs_sb['approve_level']?>
                            <?=$tmpImg?>
                            <?=$user_name?>
                          &nbsp;
                            <?php if($user_name==''){?>
                          พนักงานรับทราบ
                          <?php } ?>                        </td>
                        <td width="251" bgcolor="#E9E9E9" style="border-bottom-color: #000000; border-bottom-style: dotted;"><?php echo $rs_sb['dep_name']?> </td>
                        <td width="46" bgcolor="#E9E9E9" style="border-bottom-color: #000000; border-bottom-style: dotted;"><a title="ลบข้อมูล"  href="javascript:fn_del_permis('<?php echo $rs_sb['permis_id']?>')" style="font-size:12px; font-weight:bold; padding:5px;" class="btn btn-xs btn-danger pull-right"><li class="fas fa-trash"></li> </a> &nbsp;</td>
                      </tr>
                      <?php
								}
							}else{
							?>
                      <tr>
                        <td>-&nbsp;</td>
                        <td>&nbsp;</td>
                      </tr>
                      <?php
							}
							?>
                    </table></td>
					<td class="brdrfashion"><div align="center"><a href="menu_01_01_add.php?user_id=<?php echo $rs['user_id']?>"  type="button"  data-toggle="modal" onclick="fn_show_add_new_process('actionH','<?=$rs['process_name']?>','<?=$rs['id']?>','permis_id','div_target')" data-target="#staticBackdrop" title="กำหนดสิทธิ์การใช้งาน" class="btn btn-xs  btn-dark ">กำหนดสิทธิ์การใช้งาน<a></td> 
				  </tr>
					<?
				}
			}
		}
	}
			?>
			  <input name="user_idH" type="hidden" id="user_id" value="<?php echo $user_id ?>">
	  <tr>
	    <td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td>
		<td>&nbsp;</td> 
	  </tr> 
	</table></td>
	</tr>
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
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  
 <?php include "footer.php"; ?>
 
<iframe id="iFm_save_target_3" name="iFm_save_target_3" src="" style="width:0px;height:0px;border:0"></iframe>
	<form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target_3">
	
		 <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_19" />
		 <input name="Action" type="hidden" id="Action" value="add" />  
		 <input name="process_id" type="hidden" id="user_process" value="add" /> 
<div class="modal fade" id="staticBackdrop" style="width:100%;"  data-backdrop="static" tabindex="-1" role="dialog" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog" role="document" >
    <div class="modal-content" style="width:700px; margin-left:-200px;">
      <div class="modal-header" >
        <h5 class="modal-title" id="staticBackdropLabel"><strong>กำหนดอนุมัติ</strong> </h5>
        <button type="button"  class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
		 <div id="user_titel" align="center" style="font-weight:bold; font-size:18px"></div>
		 <hr>
	    <table style="margin-top:-10px;"  id="example22"  width="100%">
        <thead>
          <tr style="font-size:16px; border-bottom: solid #CCCCCC 2px; ">
            <th width="80%" class="text-center"><span style="font-size:14px;">ชื่อ</span></th>
            <th width="15%" class="text-center"><span style="font-size:14px;">ลำดับ</span></th>
            <th width="5%" class="text-center"><span style="font-size:14px;"></span></th>  
          </tr> 
        </thead>
        <tbody>
		</tbody>
		</table> 
	    <script language="javascript">
							 function fn_add_item() { 
									var user_id 	=	document.getElementById('user_id2').value;
									var approve_level 	=	document.getElementById('approve_level2').value;  
									var user_id_arr =   user_id.split("|");
									if(user_id=="") { return; } 
									if(approve_level=="") { return; } 
									//var tmp1 = un_id.split("/");
					
									var tmp_row=document.getElementById('example22').rows.length;
									var x=document.getElementById('example22').insertRow((tmp_row*1));
									var c1=x.insertCell(0);
									var c2=x.insertCell(1);
									var c3=x.insertCell(2); 
									var rdChk = '';
									tmp_row = ((tmp_row*1)-1);
									if(((tmp_row*1)-1)=="1") { rdChk = ' checked'; }
									for (var i=0;i<=((tmp_row*1)+1); i++) {
									c1.innerHTML='<td  ><div style="text-align: left;font-size:16px; border-bottom: solid #CCCCCC 1px; ">'+user_id_arr[1]+'</div></td><input type="hidden" name="user_id[]" id="user_id[]" value="'+user_id_arr[0]+'"/>';
									c2.innerHTML='<td ><div style="text-align:center;  border-bottom: solid #CCCCCC 1px;">'+approve_level+'</div><input style="width:100%;" class="form-control" type="hidden" name="approve_level[]" id="approve_level[]" value="'+approve_level+'" /></td>';   
									c3.innerHTML='<td align="center" style="font-size:16px; border-bottom: solid #CCCCCC 2px; ">&nbsp;&nbsp;&nbsp;<a href="javascript:fn_del_row_item('+i+')" style=" margin-top:2px;" class="btn btn-xs btn-danger ">ลบ</a></td>';
									}
									var table 	= document.getElementById('example22');
									var rows 	= table.getElementsByTagName("tr")[((tmp_row*1)+1)];  
								}
								function fn_del_row_item(del_row) {
									var table = document.getElementById("example22");
									var rows  = table.getElementsByTagName("tr")[del_row];
									rows.style.display = 'none'; 
									var user_id_arr  = document.getElementsByName("user_id[]"); 
									user_id_arr[(del_row-1)].value=''; 
								}  
							</script>
	  <table style="margin-top:30px; border: #CCCCCC"  border="1" width="100%">
          <tr>
            <td width="45%"><select name="user_id2" id="user_id2" class="form-control select2"   >
                <option value="">- เลือกชื่อ -</option>
                <?php
				$sql="SELECT *
				  FROM  tb_user  
				  "; 
				$query =mysqli_query($connect,$sql)  or die(mysqli_error($connect));
				$num_rows =mysqli_num_rows($query);
				if ($num_rows>=1){ 
					while($rstmp=mysqli_fetch_array($query)) { ?>
<option value="<?php echo $rstmp['user_id'] ?>|<?php echo $rstmp['user_name'].' '.$rstmp['user_fname'] ?>"><?php echo $rstmp['user_name'].' '.$rstmp['user_fname'] ?></option>
<?php 
					}
				}
				?>
              </select>
            </td>  
            <td width="45%"><select name="approve_level2" id="approve_level2" class="form-control select2"   style=" font-size:14px; font-weight:normal; height:50px; padding-top:0px;"  >
                <option value="1">1</option> 
                <option value="2">2</option> 
                <option value="3">3</option> 
                <option value="4">4</option> 
                <option value="5">5</option> 
                <option value="6">6</option> 
                <option value="7">7</option> 
                <option value="8">8</option> 
                <option value="9">9</option> 
                <option value="10">10</option> 
              </select>
            </td>  
            <td width="10%"><a onClick="fn_add_item()" class="btn btn-success" style="width:3em; padding:10px;">
              <li class="fa fa-plus"></li>
              </a> </a></td>
          </tr> 
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button><input type="submit" class="btn btn-primary" id="submit" name="submit" value="บันทึกข้อมูล">
      </div>
    </div>
  </div>
</div>

</form>
<div id="div_target"></div>
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
	
	function fn_del_permis(rsmp_id) { 
		Swal.fire({
		  title: "ต้องการลบข้อมูลใช่หรือไม่", 
		  icon: "warning",
		  showCancelButton: true,
		  cancelButtonText: "ไม่",      
		  confirmButtonColor: "#3085d6",
		  cancelButtonColor: "#d33",
		  confirmButtonText: "ใช่"
		}).then((result) => {
		  if (result.isConfirmed) {
		//	window.location.href="fn_save_center.php?user_id="+rsmp_id+"&SubmitH=Submit_1_1";
			
	alert(rsmp_id);
			var URL = "fn_save_center.php" ;
			var data = 'user_id='+rsmp_id+'&SubmitH=Submit_19_1';
			ajaxLoad('POST', URL, data, div_target,'');
			
			Swal.fire({
			  title: "ลบข้อมูล!",
			  text: "ข้อมูลของคุณถูกลบแล้ว.",
			  icon: "success",
		  showConfirmButton: false,
		  
		  timer: 2000 
			}).then(function() {
  
     location.assign("menu_13.php") 
 });
			
			
		  }
		});
	}
	
function fn_alet() {
 
		Swal.fire({
		  position: "top-end",
		  icon: "success",
		  title: "บันทึกข้อมูลเรียบร้อย",
		  showConfirmButton: false,
		  
		  timer: 2000 
		  
		}).then(function() {
  
     location.assign("menu_13.php") 
 });

		} 
		
	function fn_delete2(rsmp_id) { 
		if (confirm('ต้องการลบข้อมูลใช่หรือไม่')) {
			window.location.href="fn_save_center.php?user_id="+rsmp_id+"&SubmitH=Submit_1_1";
		}
	}
	function fn_show_add_new_process(actionH,user_id,process_id,permis_id,div_target) { 
document.getElementById('user_process').value=process_id; 
document.getElementById('user_titel').innerHTML=user_id; 
		
	}
	
	function ClickCheckAll(vol)
	{ 
		var i=1;
		for(i=1;i<=1000;i++)
		{
			if(vol.checked == true)
			{
				if(document.getElementById("ctrl_add"+i).disabled == false){
				eval("document.frm_update_data.ctrl_add"+i+".checked=true");
				}
			}
			else
			{
				eval("document.frm_update_data.ctrl_add"+i+".checked=false");
			}
		}
	}
	
	function ClickCheckAllEdit(vol)
	{ 
		var i=1;
		for(i=1;i<=1000;i++)
		{
			if(vol.checked == true)
			{
				if(document.getElementById("ctrl_edit"+i).disabled == false){
				eval("document.frm_update_data.ctrl_edit"+i+".checked=true");
				}
			}
			else
			{
				eval("document.frm_update_data.ctrl_edit"+i+".checked=false");
			}
		}
	}
	
	function ClickCheckAlldel(vol)
	{ 
		var i=1;
		for(i=1;i<=1000;i++)
		{
			if(vol.checked == true)
			{
				if(document.getElementById("ctrl_del"+i).disabled == false){
				eval("document.frm_update_data.ctrl_del"+i+".checked=true");
				}
			}
			else
			{
				eval("document.frm_update_data.ctrl_del"+i+".checked=false");
			}
		}
	}
	
	function ClickCheckAllView(vol)
	{ 
		var i=1;
		for(i=1;i<=1000;i++)
		{
			if(vol.checked == true)
			{
				if(document.getElementById("ctrl_view"+i).disabled == false){
				eval("document.frm_update_data.ctrl_view"+i+".checked=true");
				}
			}
			else
			{
				eval("document.frm_update_data.ctrl_view"+i+".checked=false");
			}
		}
	}
	
	function ClickCheckAllPrint(vol)
	{ 
		var i=1;
		for(i=1;i<=1000;i++)
		{
			if(vol.checked == true)
			{
				if(document.getElementById("ctrl_print"+i).disabled == false){
				eval("document.frm_update_data.ctrl_print"+i+".checked=true");
				}
			}
			else
			{
				eval("document.frm_update_data.ctrl_print"+i+".checked=false");
			}
		}
	}
	
	function fn_update_process() {
		document.frm_update_data.SubmitH.value="Submit_19";
	//	document.frm_update_data.submit();
	}
	
$(document).ready(function() {
	

    $(".btnFetch").click(function() {
		 
      // disable button
//	  document.getElementById("frm_update_data").submit();
      $(this).prop("disabled", true);
      // add spinner to button
      $(this).html(
        `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> กำลังบันทึกข้อมูล...`
      );
    });
});  
</script>
