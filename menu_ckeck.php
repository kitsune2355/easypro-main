<?php 
define("SB_M1","data_5");
define("SB_M2","data_2_1");
define("SB_M3","data_2_1_1");
include "head.php"; 

?>
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h3><strong>รายการคำถาม</strong></h3>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item active"><strong>รายการคำถาม</strong></li>
              <li class="breadcrumb-item"><a href="menu_05_05_add.php">เพิ่มคำถาม<a></li>
			   
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
                <h3 class="card-title"><strong>ข้อมูลคำถาม</strong></h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                
    <iframe id="iFm_save_target" name="iFm_save_target" src="" style="width:0px;height:0px;border:0"></iframe>
    <form id="frm_update_data" name="frm_update_data" method="post" enctype="multipart/form-data" 
	action="fn_save_center.php" target="iFm_save_target">
      <input name="SubmitH" type="hidden" id="SubmitH" value="Submit_8" />
      <input name="status" type="hidden" id="status" value="" />
      <input type="text" name="lp_agency" value="<?php echo $rs_agency['ag_id'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อผู้ใช้งาน" >   
      <input type="text" name="lp_check_point" value="<?php echo $rs_checkpoint['cp_point_number'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อผู้ใช้งาน" >  
      <input type="text" id="lp_latitude" name="lp_latitude" value="<?php echo $rs['latitude'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อผู้ใช้งาน" >  
      <input type="text" id="lp_longitude" name="lp_longitude" value="<?php echo $rs['latitude'] ?>" class="form-control form-control-border" id="exampleInputBorder" placeholder="ชื่อผู้ใช้งาน" >
      <!-- Main content --> 
	        <div class="container">
	        
		
		<div class="row">  
          <div class="col-md-12 col-lg-12 col-md-12 col-xs-12">
            <!-- Widget: user widget style 1 -->
            <div class="card card-widget widget-user" >
              <!-- Add the bg color to the header using any of the bg-* classes -->
              <div class="widget-user-header bg-danger"><br />
                <h5  ><strong>
				ยินดีต้อนรับเข้าระบบบันทึกจุดตรวจ 
</strong></h5>
              </div>
              <div class="widget-user-image"  > 
                <img class="elevation-2 img-circle" src="logo1.png"    > 
              </div>
               <div class="card-footer p-0">
                <ul class="nav flex-column">
                  <li class="nav-item"> <br>

				  <blockquote style="text-align:center">
                  <p><strong>หน่วยงาน <?php echo $rs_agency['ag_job']; ?></strong></p> 
				  รอบวันที่ 10/5/66 เวลา 09.00 - 12.00 น.
                </blockquote> 
                  </li>
				  </ul>
				  </div>
				  </h2>  
			
				
				<?php 
				for($i=0; $i<3; $i++){
				if($rs_checkpoint['cp_nameanswer'.$i]!=''){?>
				 
				<style>
				.wrapper<?php echo $i ?>{
  display: inline-flex;
  background: #F9F9F9;
  height: 40px;
  width: 250px;
  align-items: center;
  justify-content: space-evenly;
  border-radius: 5px;
  padding: 2px 2px; 
  box-shadow: 5px 5px 30px rgba(0,0,0,0.2);
}
.wrapper<?php echo $i ?> .option<?php echo $i ?>{
  background: #fff;
  height: 100%;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-evenly;
  margin: 0 10px;
  border-radius: 5px;
  cursor: pointer;
  padding: 0 10px;
  border: 2px solid lightgrey;
  transition: all 0.3s ease;
}
.wrapper<?php echo $i ?> .option<?php echo $i ?> .dot<?php echo $i ?>{
  height: 20px;
  width: 20px;
  background: #d9d9d9;
  border-radius: 50%;
  position: relative;
}
.wrapper<?php echo $i ?> .option-1<?php echo $i ?> .dot<?php echo $i ?>::before{
  position: absolute;
  content: "";
  top: 4px;
  left: 4px;
  width: 12px;
  height: 12px;
  background: #00CC00;
  border-radius: 50%;
  opacity: 0;
  transform: scale(1.5);
  transition: all 0.3s ease;
}
.wrapper<?php echo $i ?> .option-2<?php echo $i ?> .dot<?php echo $i ?>::before{
  position: absolute;
  content: "";
  top: 4px;
  left: 4px;
  width: 12px;
  height: 12px;
  background: #FF3300;
  border-radius: 50%;
  opacity: 0;
  transform: scale(1.5);
  transition: all 0.3s ease;
}
input[type="radio"]{
  display: none;
}
#option-1<?php echo $i ?>:checked:checked ~ .option-1<?php echo $i ?>{
  border-color: #006600;
  background: #009900;
}
#option-2<?php echo $i ?>:checked:checked ~ .option-2<?php echo $i ?>{
  border-color: #CC0000;
  background: #FF0000;
}
#option-1<?php echo $i ?>:checked:checked ~ .option-1<?php echo $i ?> .dot,
#option-2<?php echo $i ?>:checked:checked ~ .option-2<?php echo $i ?> .dot{
background-color:#33FF00;
}
#option-1<?php echo $i ?>:checked:checked ~ .option-1<?php echo $i ?> .dot<?php echo $i ?>::before,
#option-2<?php echo $i ?>:checked:checked ~ .option-2<?php echo $i ?> .dot<?php echo $i ?>::before{
  opacity: 1;
  transform: scale(1);
}
.wrapper<?php echo $i ?> .option<?php echo $i ?> span{
  font-size: 15px;
  color: #808080;
}
#option-1<?php echo $i ?>:checked:checked ~ .option-1<?php echo $i ?> span,
#option-2<?php echo $i ?>:checked:checked ~ .option-2<?php echo $i ?> span{
  color: #fff;
}
				</style>
                	  <div class="card-header ui-sortable-handle dark-mode" style="margin-top:1px;" >
					  	
		<input type="text" name="lp_question1[<?php echo $i ?>]" value="<?php echo $rs_checkpoint['cp_nameanswer'.$i];?>" id="sizeWeight<?php echo $i ?>" checked="checked" />
                <h3 class="card-title">
                  <i class="fas fa-chart-pie mr-1"></i> <?php echo $rs_checkpoint['cp_nameanswer'.$i];?>
                </h3>
                <div class="card-tools">
                  <ul class="nav nav-pills ml-auto" style="margin-top:10px;">
				  <div class="wrapper<?php echo $i ?>">
 <input type="radio" name="lp_answer[<?php echo $i ?>]" id="option-1<?php echo $i ?>" checked>
 <input type="radio" name="lp_answer[<?php echo $i ?>]" id="option-2<?php echo $i ?>">
   <label for="option-1<?php echo $i ?>" class="option<?php echo $i ?> option-1<?php echo $i ?>">
     <div class="dot<?php echo $i ?>"></div>
      <span>ปกติ</span>
      </label>
   <label for="option-2<?php echo $i ?>" class="option<?php echo $i ?> option-2<?php echo $i ?>">
     <div class="dot<?php echo $i ?>"></div>
      <span>ไม่ปกติ</span>
   </label>
</div>
                  </ul>
                </div>
              </div>
				<?php } } ?>
                <!-- /.d-flex -->
                <!-- CSS -->
    <style>
    #my_camera{
        width: 80%;
        height: 100%;
        border: 1px solid black;
    }
	</style>

	<!-- -->
	 <div class="card-header ui-sortable-handle dark-mode" style="margin-top:1px;" >
                <h3 class="card-title">
                   
                </h3> 
                  <ul class="nav nav-pills ml-auto">
                    <li class="nav-item"> 
					<div id="camera1_box" style="display: block;">
	<input type=button value="กดเปิดกล้องเพื่อถ่ายรูปบุคคลยืนยันการตรวจ" id="camera" class="btn  btn-warning btn-sm" style="color:#FFFFFF" onClick="configure()">
	</div>
                    </li>
                    <li class="nav-item" style="margin-left:10px;">
					<div id="camera2_box" style="display:none;">
	<input type=button value="ถ่ายรูป" style="color:#FFFFFF"  id="camera2" class="btn  btn-warning btn-sm"   onClick="take_snapshot()">  
	</div>
				  </label> 
                    </li>
                  </ul>
                </div> 
			  <div align="center">
	<div id="my_camera"  style="display:none; "></div>
	<div id="camera3"  style="display:none; ">
    <div id="results"></div>
	<input type="text" id="results2" name="lp_picture" value=""    class="btn  btn-warning btn-sm"  >  <br>
รูปถ่าย
	</div>
	</div>
	<button onClick="getLocation()">Try It</button>

<p id="demo"></p>


 

<script>
function myMap() {
var mapProp= {
  center:new google.maps.LatLng(51.508742,-0.120850),
  zoom:5,
};
var map = new google.maps.Map(document.getElementById("googleMap"),mapProp);
}
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_KEY&callback=myMap"></script>



<script>
var x = document.getElementById("demo");
getLocation();
function getLocation() {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(showPosition);
  } else { 
    x.innerHTML = "Geolocation is not supported by this browser.";
  }
}

function showPosition(position) {
  x.innerHTML = "Latitude: " + position.coords.latitude + 
  "<br>Longitude: " + position.coords.longitude;
  document.getElementById("lp_latitude").value=position.coords.latitude;
  document.getElementById("lp_longitude").value=position.coords.longitude;
}
</script>
	<!-- Script -->
 <script src="http://code.jquery.com/jquery-latest.js"></script>
 <script type="text/javascript">
   <?php 
		 for($i=1; $i<4; $i++){
	?> 
   function readURL<?php echo $i ?>(input) {
	 console.log(input.files[0]);
	 if (input.files && input.files[0]) {
	   var reader = new FileReader();

	   reader.onload = function(e) {
		 $('#blah<?php echo $i ?>').attr('src', e.target.result);
		 $('#blah1<?php echo $i ?>').attr('value', e.target.result);
	   }
		document.getElementById('img<?php echo $i ?>').style.display='block';
	   reader.readAsDataURL(input.files[0]);
	 }
   }
   
   <?php } ?>
 </script>   
  <div class="card-header ui-sortable-handle dark-mode" style="margin-top:1px;" >
                เพิ่มรูปภาพเหตุการณ์ปัจุบัน
                  <ul class="nav nav-pills ml-auto">
                    <div class="custom-file"> 
                      <label class="custom-file-label" for="customFile">รูปที่ 1</label>
                      <input type="file" class="custom-file-input" name="file1" id="customFile" onChange="readURL1(this);" />
                    </div>
                    <div id="img1" style="display:none;"> 
                		<img id="blah1"   src="#" alt="" />
                      <input type="hidden" id="blah11"  name="lp_attachment100" value="">
                    </div>
                    <div class="custom-file">
                      <input type="file" class="custom-file-input" name="file2" onChange="readURL2(this);" id="customFile">
                      <label class="custom-file-label" for="customFile">รูปที่ 2</label>
                    </div>
                    <div id="img2" style="display:none;"> 
                		<img id="blah2"   src="#" alt="" /> 
                      <input type="hidden" id="blah12"  name="lp_attachment200" value="">
                    </div>
					<div class="custom-file">
                      <input type="file" name="file3" class="custom-file-input" onChange="readURL3(this);" id="customFile">
                      <label class="custom-file-label" for="customFile">รูปที่ 3</label>
                    </div>
                    <div id="img3" style="display:none;"> 
                		<img id="blah3"   src="#" alt="" /> 
                      <input type="hidden" id="blah13"  name="lp_attachment300" value="">
                    </div>
                  </ul> 
              </div> 
  <div class="card-header ui-sortable-handle dark-mode" style="margin-top:1px;" >
                <h3 class="card-title">
                  <i class="fas fa-chart-pie mr-1"></i> บันทึกเพิ่มเติม
                </h3> 
                <br>
                  <ul class="nav nav-pills ml-auto">
                  <textarea class="form-control" rows="3" name="lp_more_details" placeholder="กรอกข้อมูลที่ต้องการบันทึกเหตุการณ์เพิ่มเติม"></textarea>
                  </ul> 
              </div>
	<script type="text/javascript" src="camera/webcamjs/webcam.min.js"></script>

	<!-- Code to handle taking the snapshot and displaying it locally -->
	<script language="JavaScript">
	 

		function take_snapshot() {
			
	document.getElementById('my_camera').style.display='block';
	document.getElementById('camera1_box').style.display='block';
	document.getElementById('camera2_box').style.display='none';
	document.getElementById('camera3').style.display='block';
			// play sound effect
			//shutter.play();

			// take snapshot and get image data
			Webcam.snap( function(data_uri) {
				// display results in page
				document.getElementById('results2').value = data_uri;
				document.getElementById('results').innerHTML = 
					'<img id="imageprev" src="'+data_uri+'"/>';
			} );

		//	Webcam.reset();
		}

		function saveSnap(){
			// Get base64 value from <img id='imageprev'> source
			var base64image =  document.getElementById("imageprev").src;

			 Webcam.upload( base64image, 'upload.php', function(code, text) {
				 console.log('Save successfully');
				 //console.log(text);
            });

		}
	</script>
                  <div style=" text-align:center">
					          <button type="submit" class="btn btn-success " style="text-align:left; margin-top:10px; margin-left:10px;"> <span class="glyphicon glyphicon-floppy-saved" aria-hidden="true"></span> ยืนยัน การตรวจจุด</button>
							  </div>
</div> 
 
 
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
 <!-- DataTables  & Plugins -->
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