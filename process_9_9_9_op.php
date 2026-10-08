<?php 
define("SB_M1","data_6");
define("SB_M2","Setting_channel_1");
define("SB_M3","data_2_1_1");
include("config_ctrl/connect.php");
include("head.php");  
$tmp_process = '9'; 
?>
<script src="ajax/ajax_framework.js"> </script>
<script language="javascript">
function fn_show1 () {
   	$('#modal-default').modal('show'); 
 }
function fn_alert () {
   	$('#modal-success').modal('show'); 
 }
function fn_alert1(alert_msg,alert_class) {
 	document.getElementById('bts_save_state1').innerHTML = alert_msg;
 	$('#modal-'+alert_class+'').modal('show'); 
}
function fn_alert2(alert_msg,alert_class) {
 	document.getElementById('bts_save_state2').innerHTML = alert_msg;
 	$('#modal-'+alert_class+'').modal('show'); 
}
function fn_alert3(alert_msg,alert_class) {
 	document.getElementById('bts_save_state3').innerHTML = alert_msg;
 	$('#modal-'+alert_class+'').modal('show'); 
}
function fn_alert4(alert_msg,alert_class) {
 	document.getElementById('bts_save_state1').innerHTML = alert_msg;
 	$('#modal-'+alert_class+'').modal('show'); 
	
}
function fn_click(){ 
	fn_show();	
} 
//function fn_list(fd_cus_id){
//	document.getElementById('div_pop_acc_list').style.display='block';
//	fn_show_list(fd_cus_id);	
//}
//function fn_show_list(fd_cus_id){ 
//	var result 	=	document.getElementById('trn_cus_name').value; 
//	var URL 	= 	"process_<?php echo $process ?>_fn_customer.php";
//	var data 	= 	'result='+result+
//			   	  	'&fd_cus_id='+fd_cus_id; 
//		ajaxLoad('post', URL, data, 'data_show_list','');		 
//		setTimeout("fn_delete_cus()", 1000);   
//}
function fn_delete_cus(){ 
	var result 	=	document.getElementById('trn_cus_name').value;
	console.log(result);  	
	if(result==""){
		document.getElementById('trn_cus_id').value='';  
		setTimeout("fn_list_Process10()", 500);  
	}
}

function fn_show_name(Name) { 
 	document.getElementById('trn_cus_name').value=Name;  
	document.getElementById('div_pop_acc_list').style.display='none';

}
function fn_list_Cusid(Cusid) { 
 	document.getElementById('trn_cus_id').value=Cusid;  
	setTimeout("fn_list_Process10()", 500);  
}  
function fn_list_Process10(){
	//var trn_cus_id 		=	document.getElementById('trn_cus_id').value; 
//	var trn_type 		=	document.getElementById('trn_type').value; 
//	var trn_date_to 	=	document.getElementById('trn_date_to').value; 
//	var trn_date_form 	=	document.getElementById('trn_date_form').value; 
//	var URL 			= 	"process_<?php echo $process ?>_list.php";
//	var data 	 = 'trn_cus_id='+trn_cus_id+
//				   '&trn_type='+trn_type+
//				   '&trn_date_to='+trn_date_to+
//				   '&trn_date_form='+trn_date_form+
//				   '&process=<?php echo $process ?>';  
//		ajaxLoad('post', URL, data, 'div_calendar_details','');	 
 
	var URL 			= 	"process_9_9_9_op_list.php";
	var data 	 		= 	'';   
		ajaxLoad('post', URL, data, 'div_calendar_details','');	 
} 

function fn_list_pd(value){
	document.getElementById('div_pop_acc_list_pd').style.display='block';
	fn_show_list_pd(value);	
}
function fn_show_list_pd(value){
	var result =document.getElementById('trn_x123_3').value; 
	var URL = "process_11_fn_product.php";
	var data = 'result='+result;
		ajaxLoad('post', URL, data, 'data_show_list_pd','');		
}
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
function fnhid() {
	document.getElementById('div_pop_acc_list').style.display='none'; 
	//document.getElementById('div_pop_acc_list_pd').style.display='none'; 
}
function fn_show() {
	var result	 =	document.getElementById('result').value; 
	var URL 	 = "fn_show_list_detail_process.php";
	var data 	 = 'result='+result+
				   '&title=ใบส่งของ'+
				   '&Submit=Submit_11'+
				   '&process=<?php echo $process ?>';  
		ajaxLoad('post', URL, data, 'data_result','');	 
}
function fn_tem(id) {
	var TmpId = id; 
	var URL  = "process_<?php echo $process ?>_tem.php";
	var data = 'TmpId='+TmpId+
			   '&process=<?php echo $process ?>'; 
	ajaxLoad('post', URL, data, 'data_tmp_result','');		
} 
function fn_Dle(user_id) {
	$('#modal-danger').modal('show'); 
}
function fn_del() {
	document.frm_update_data_2.Action.value="Del";
	document.frm_update_data_2.submit();
}  
function fn_hide_m(){ 
    $('#modal-default1').modal('hide');   
	setTimeout(fn_alert1('บันทึกข้อมูลเรียบร้อยแล้ว','success-alert'), 1000);  
 	setTimeout('window.parent.location.href="process_9_9_9.php"', 1500);
}   
function fn_hide_m1(){ 
    $('#modal-default1-update').modal('hide');   
	setTimeout(fn_alert1('บันทึกข้อมูลเรียบร้อยแล้ว','success-alert'), 1000);  
 	setTimeout('window.parent.location.href="process_9_9_9.php"', 1500);
}  
function fn_call_tb9_sum_1_1() {
   var iCountTotal    	   = document.getElementById("v_total_2H_1").value;
 //  var v_qty_arr		   = document.getElementsByName("v_qty[]");
   var v_total_arr		   = document.getElementsByName("v_total_1[]");
   
   
   if(iCountTotal=="") { return; }
   var sum_total = 0;
   var sum_price_cost = 0;
   var sum_totalH   = 0;
   for(i=0;i<=(iCountTotal*1);i++){
		var v_total = v_total_arr[i].value;
		v_total    	= v_total.replace(/[$,]+/g,'');  
		sum_total   = (v_total*1);
		sum_totalH = sum_totalH + sum_total;
   }  
   document.getElementById("v_total_2_1").value = set_num_format(sum_totalH);
   //document.getElementById("div_tb5_sum_price_cost").innerHTML = set_num_format(sum_price_cost);
   
}

<?php
//	$sql="SELECT *
//		 FROM tb_agency  
//		 WHERE ag_status = 1
//		 AND ag_id NOT IN (
//			 			 SELECT ag_id
//						 FROM tb_list_care_agency_detail 
//						 ) 
//		  ";
//	$query =mysqli_query($connect,$sql) or die(mysqli_error($connect));
//	$num_rows =mysqli_num_rows($query);
//	if($num_rows>=1){
//		while($rs_sb=mysqli_fetch_array($query)) { 
//			$tem.="<option value=".$rs_sb['ag_id'].">".$rs_sb['ag_name']."</option>";
//		} 
//	}
?>
function fn_add_item_1(tmp_row_new) {
	var tmp_row=document.getElementById('example2228').rows.length;
	var tem_v=document.getElementsByName('class_name[]'); 
	  
	if(tem_v[tmp_row-2].value=="") { return; }
	var x=document.getElementById('example2228').insertRow((tmp_row*1));
	var c1=x.insertCell(0); 
	var c3=x.insertCell(1);
	if(((tmp_row*1)-1)=="1") { rdChk = ' checked'; }
	for (var i=0;i<=((tmp_row*1)+1); i++) {
		c1.innerHTML='<td><input type="text" style="width:100%" id="class_name[]" name="class_name[]" value=""  onkeyup="fn_add_item_1()"></td>'; 
		c3.innerHTML='<td width="87"  align="center"><a href="javascript:fn_del_row_item_1('+(i-1)+')" style="width:100%; height:100%;" class="btn btn-danger pull-right">ลบ</a></td>'; 
	}
	var table 	= document.getElementById('example2228');
	var rows 	= table.getElementsByTagName("tr")[((tmp_row*1)-1)];
	//var v_total_2H = document.getElementById("v_total_2H_1").value;
	//v_total_2H = (v_total_2H*1) + 1;
	//document.getElementById("v_total_2H_1").value = v_total_2H;
}

function fn_del_row_item_1(del_row) { 
	var table 			   = document.getElementById("example2228"); 
	var tem_v			   = document.getElementsByName('class_name[]'); 
	 
	console.log(tem_v[(del_row-1)].value);
	if(tem_v[(del_row-1)].value=="") { return; } 
	var rows  = table.getElementsByTagName("tr")[del_row]; 
	rows.style.display = 'none';
	tem_v[(del_row-1)].value='';
	//setTimeout("fn_call_tb9_sum_1()", 100);   
} 


function fn_add_item_2(tmp_row_new) {
	var tmp_row=document.getElementById('example2229').rows.length;
	var tem_v=document.getElementsByName('room_name[]'); 
	  
	if(tem_v[tmp_row-2].value=="") { return; }
	var x=document.getElementById('example2229').insertRow((tmp_row*1));
	var c1=x.insertCell(0); 
	var c3=x.insertCell(1);
	if(((tmp_row*1)-1)=="1") { rdChk = ' checked'; }
	for (var i=0;i<=((tmp_row*1)+1); i++) {
		c1.innerHTML='<td><input type="text" style="width:100%" id="room_name[]" name="room_name[]" value=""  onkeyup="fn_add_item_2()"></td>'; 
		c3.innerHTML='<td width="87"  align="center"><a href="javascript:fn_del_row_item_2('+(i-1)+')" style="width:100%; height:100%;" class="btn btn-danger pull-right">ลบ</a></td>'; 
	}
	var table 	= document.getElementById('example2229');
	var rows 	= table.getElementsByTagName("tr")[((tmp_row*1)-1)];
	//var v_total_2H = document.getElementById("v_total_2H_1").value;
	//v_total_2H = (v_total_2H*1) + 1;
	//document.getElementById("v_total_2H_1").value = v_total_2H;
}

function fn_del_row_item_2(del_row) { 
	var table 			   = document.getElementById("example2229"); 
	var tem_v			   = document.getElementsByName('room_name[]'); 
	 
	console.log(tem_v[(del_row-1)].value);
	if(tem_v[(del_row-1)].value=="") { return; } 
	var rows  = table.getElementsByTagName("tr")[del_row]; 
	rows.style.display = 'none';
	tem_v[(del_row-1)].value='';
	//setTimeout("fn_call_tb9_sum_1()", 100);   
} 

</script> 
  <div class="content-wrapper"> 
	<section class="content"> 
    <div class="row">
      <div class="col-md-12">
        <div class="box box-primary">
		  <div class="fc-center" align="center"><h4><?php echo $eng_full_month_arr[$search_month*1]?> <?php echo $search_year;?> รายการตารางเดิน <!--บัญชีรายรับรายจ่าย --> 
		  </div> 
          <div class="box-body no-padding" id="div_calendar_details">
            
          </div>
          <!-- /.box-body -->
        </div>
        <!-- /. box -->
      </div>
    </div>
	</section>
  </div>
  <!-- /.content-wrapper --> 
<?php  include("footer.php"); ?>

<script> 
function fn_select_qut () {
   $('#modal-default1').modal('show'); 
}
function fn_select_qut_1 (process_id, process_degree) {
   $('#modal-default1-update_1').modal('show'); 
	var URL 	 = "process_9_9_9_op_list_edit.php";
	var data 	 = 'process_id='+process_id+
				   '&process_degree='+process_degree;  
		ajaxLoad('post', URL, data, 'data_result_2','');	 
}
function fn_select_qut_2 (ag_id,ag_name,class_id,class_name) {
   $('#modal-default1-update').modal('show');   
	var URL 	 = "process_9_9_9_op_list_edit2.php";
	var data 	 = 'ag_id='+ag_id+
				   '&ag_name='+ag_name+
				   '&class_id='+class_id+
				   '&class_name='+class_name;  
		ajaxLoad('post', URL, data, 'data_result_1','');	 
}
function fn_select_qut_3 (ag_id,ag_name,class_id,class_name) {
   $('#modal-default1-update2').modal('show');   
	var URL 	 = "process_9_9_9_op_list_edit3.php";
	var data 	 = 'ag_id='+ag_id+
				   '&ag_name='+ag_name+
				   '&class_id='+class_id+
				   '&class_name='+class_name;  
		ajaxLoad('post', URL, data, 'data_result_3','');	 
}

function fn_del_permis(permis_id,room_agc_id) {
	if(confirm("คุณต้องการลบรายการที่เลือกนี้ใช่หรือไม่")){
		var URL = "fn_save_center.php" ;
		var data = 'SubmitH=Submit_15&room_agc_id='+room_agc_id+'&permis_id='+permis_id;
		ajaxLoad('post', URL, data, 'div_update_fn','');
		//var Dist = setTimeout(fn_alert4('ลบข้อมูลเรียบร้อยแล้ว','success-alert'), 1000); 
		var Dist = setTimeout("fn_list_Process10()", 1000);
		//var Dist = setTimeout("fn_hide_update2()", 1500);
	}
} 
function fn_close(permis_id) {
	if(confirm("คุณต้องการลบรายการที่เลือกนี้ใช่หรือไม่")){
		var URL = "fn_save_center.php" ;
		var data = 'SubmitH=Submit_15&permis_id='+permis_id;
		ajaxLoad('post', URL, data, 'div_update_fn','');
		//var Dist = setTimeout(fn_alert4('ลบข้อมูลเรียบร้อยแล้ว','success-alert'), 1000); 
		var Dist = setTimeout("fn_list_Process10()", 1000);
		var Dist = setTimeout("fn_hide_update2()", 1500);
	}
} 

function fn_hide_update1(){ 
    $('#modal-default1-update_1').modal('hide');   
	setTimeout(fn_alert4('บันทึกข้อมูลเรียบร้อยแล้ว','success-alert'), 1000);   
	setTimeout("fn_hide_update2()", 1500);   
} 
function fn_hide_update2(){ 
    $('#modal-success-alert').modal('hide');    
}  
function fn_hide_update3(){ 
    $('#modal-default1-update').modal('hide');   
	setTimeout(fn_alert4('บันทึกข้อมูลเรียบร้อยแล้ว','success-alert'), 1000);   
	setTimeout("fn_hide_update4()", 1500);   
} 
function fn_hide_update4(){ 
    $('#modal-success-alert').modal('hide');    
}  
function fn_hide_update5(){ 
    $('#modal-default1-update2').modal('hide');   
	setTimeout(fn_alert4('บันทึกข้อมูลเรียบร้อยแล้ว','success-alert'), 1000);   
	setTimeout("fn_hide_update6()", 1500);   
} 
function fn_hide_update6(){ 
    $('#modal-success-alert').modal('hide');    
}   
</script>

<div id="div_update_fn"></div>

  <iframe id="iFm_save_target_3" name="iFm_save_target_3" src="" style="width:800px;height:200px;border:0"></iframe>	
  <form id="frm_update_data_3" name="frm_update_data_3" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target_3">
		 <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_38" />
		 <input name="Action" type="hidden" id="Action" value="add" />
		<div class="modal fade" id="modal-default1-update2">
		  <div class="modal-dialog">
			<div class="modal-content">
			  <div class="modal-header" style="width:600px; height:40px; background-color:#0066FF; color:#FFFFFF">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				  <span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><strong>อัพเดทแอร์</strong></h4>
			  </div>
			  <div class="modal-body" style="width:600px;">  
			  <div class="row">
			   <div class="col-sm-12">
			   <div id="data_result_3"></div>
							
							</div>
						</div>
						
			  <div class="modal-footer">
				<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
				<button style="display: none;" type="submit" id="submit-button">Not Shown</button> 
				<input type="submit" class="btn btn-primary" id="submit" name="submit" value="บันทึกข้อมูล">
			  </div>
			</div>
		  </div>
		</div>
		</div>
</form>

  <iframe id="iFm_save_target_3" name="iFm_save_target_3" src="" style="width:0px;height:0px;border:0"></iframe>	
  <form id="frm_update_data_3" name="frm_update_data_3" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target_3">
		 <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_9" />
		 <input name="Action" type="hidden" id="Action" value="add" />
		<div class="modal fade" id="modal-default1-update_1">
		  <div class="modal-dialog">
			<div class="modal-content" style="width:1000px;">
			  <div class="modal-header">
              <h4 class="modal-title">เพิ่มข้อมูลตารางเวลาการเดิน</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
              </button>
            </div>
			  <div class="modal-body" style="width:1000px;">  
			  <div class="row" style="width:1000px;">
			   <div class="col-sm-12" style="width:1000px;">
			   <div id="data_result_2"></div>
							
							</div>
						</div>
						
			  <div class="modal-footer">
				<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
				<button style="display: none;" type="submit" id="submit-button">Not Shown</button> 
				<input type="submit" class="btn btn-primary" id="submit" name="submit" value="บันทึกข้อมูล">
			  </div>
			</div>
		  </div>
		</div>
		</div>
</form>
  <iframe id="iFm_save_target_2" name="iFm_save_target_2" src="" style="width:800px;height:200px;border:0"></iframe>	
  <form id="frm_update_data_2" name="frm_update_data_2" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target_2">
		 <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_10" />
		 <input name="Action" type="hidden" id="Action" value="add" />
		<div class="modal fade" id="modal-default1-update">
		  <div class="modal-dialog">
			<div class="modal-content">
			  <div class="modal-header">
              <h4 class="modal-title">เลือกจุด</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">×</span>
              </button>
            </div>
			  <div class="modal-body">  
			  <div class="row">
			   <div class="col-sm-12">
			   <div id="data_result_1"></div>
							
							</div>
						</div>
						
			  <div class="modal-footer">
				<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
				<button style="display: none;" type="submit" id="submit-button">Not Shown</button> 
				<input type="submit" class="btn btn-primary" id="submit" name="submit" value="บันทึกข้อมูล">
			  </div>
			</div>
		  </div>
		</div>
		</div>
</form>
<iframe id="iFm_save_target" name="iFm_save_target" src="" style="width:0px;height:0px;border:0"></iframe>
	<form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target">
		 <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_14" />
		 <input name="Action" type="hidden" id="Action" value="add" />
		<div class="modal fade" id="modal-default1"  >
		  <div class="modal-dialog"  style=" width:1300px;" >
			<div class="modal-content" >
			  <div class="modal-header" style="width:1300px; height:40px; background-color:#0066FF; color:#FFFFFF">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				  <span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><strong>เพิ่มข้อมูลใหม่</strong></h4>
			  </div>
			  <div class="modal-body" style=" width:1300px;">  
			  <div class="row">
			   <div class="col-sm-12">
			   <style>
					.example2 {
						border: 1px solid black;
					}
					.example2 th {
						border: 1px solid;
						border-color: #CCCCCC;
						font-size:16px;
						height: 30px;
					}
					.example2 td {
						border: 1px solid;
						border-color: #CCCCCC;
					}
				</style>  
			<input type="hidden" id="div_voucher_list" name="div_voucher_list"  value=""> 
			<table width="100%" border="1" class="example2">
				  <tr> 
			    <td width="24%" align="right">	 
					</td>
				    <td width="26%" align="right">	
						 
					</td>
					<td width="26%" align="right"> 
					 
					</td>
				  </tr>
				</table> 
							
				</div>
						</div>
						
			  <div class="modal-footer">
				<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
				<button style="display: none;" type="submit" id="submit-button">Not Shown</button> 
				<input type="submit" class="btn btn-primary" id="submit" name="submit" value="บันทึกข้อมูล">
			  </div>
			</div>
		  </div>
		</div>
</form>
<script>
fn_list_Process10();
//fn_show(); 
function doFancyStuffEdit() { 
	var TmpValue = document.getElementById("customer_payment").value;
	if(TmpValue!=""){ 
		fn_edit();
	}else{  
    	document.getElementById("submit-button-edit").click(); 
	}
}
function doFancyStuff() {
	var TmpValue = document.getElementById("customer_img").value;
	console.log(TmpValue);
	if(TmpValue!=""){ 
		//document.getElementById("modal-default1").className = "modal";
		//document.getElementById("modal-default1").style.display='none'; 
		fn_submit();
	}else{  
    	document.getElementById("submit-button").click(); 
	}
}
function fn_submit() { 
	document.getElementById("modal-default1").className = "modal";
	document.getElementById("modal-default1").style.display='none'; 
	document.frm_update_data.submit();
	
} 
$('#rg_dateRg').datepicker({
	  language:'th',
	  todayBtn: "linked",
	  autoclose: true ,
	  orientation: 'top', 
	  format:'dd - M - yyyy' 
}).datepicker("setDate", "0");
$(function () {
    $('#example1').DataTable()
    $('#example2').DataTable({
	  'lengthMenu' : [[10, 20, 30, -1], [10, 20, 30, "All"]],
      'paging'      : true,
      'lengthChange': false,
      'searching'   : true,
      'ordering'    : false,
      'info'        : true,
      'autoWidth'   : false,
	  'oLanguage': {
                    "sEmptyTable":     "ไม่มีข้อมูลในตาราง",
					"sInfo":           "แสดง _START_ ถึง _END_ จาก _TOTAL_ แถว",
					"sInfoEmpty":      "แสดง 0 ถึง 0 จาก 0 แถว",
					"sInfoFiltered":   "(กรองข้อมูล _MAX_ ทุกแถว)",
					"sInfoPostFix":    "",
					"sInfoThousands":  ",",
					"sLengthMenu":     "แสดง _MENU_ แถว",
					"sLoadingRecords": "กำลังโหลดข้อมูล...",
					"sProcessing":     "กำลังดำเนินการ...",
					"sSearch":         "ค้นหา: ",
					"sZeroRecords":    "ไม่พบข้อมูล",
					"oPaginate": {
						"sFirst":    "หน้าแรก",
					"sPrevious": "ก่อนหน้า",
						"sNext":     "ถัดไป",
					"sLast":     "หน้าสุดท้าย"
					},
					"oAria": {
						"sSortAscending":  ": เปิดใช้งานการเรียงข้อมูลจากน้อยไปมาก",
					"sSortDescending": ": เปิดใช้งานการเรียงข้อมูลจากมากไปน้อย"
					}
            }
    })
  })
</script>