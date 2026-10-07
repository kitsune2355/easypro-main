<?php   
@session_start();
include "config_ctrl/connect.php";
include "config_ctrl/checksession.php";
include "head_user.php";

?>

<br>
<style>
.box{
 background-color:white;
            box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;
            height:40px;
            width:120px;
            margin:auto;
			margin-top:10px;
            border-radius:5px;
			font-weight:900; 
    display: table-cell;
    vertical-align: middle;
	text-align:center;
			
			}
</style>
<div class="container">
  <div class="row" style="height:10px;">
  
  	<?php 
$sql_sb="SELECT *
		 FROM tb_subject 
		 "; 
$query_sb =mysqli_query($connect,$sql_sb)  or die(mysqli_error($connect));
$num_rows_sb =mysqli_num_rows($query_sb);
if($num_rows_sb>=1){
	while($rs_sb=mysqli_fetch_array($query_sb)){ 
	?>
    <div class="col-sm-4 col-md-2 col-sm-2 col-6">
      <div class="info-box mb-3" style=" text-align:center; font-size:16px;">
        <div class="info-box-content"> <strong><?php echo  $rs_sb['sj_subject'] ?></strong></div>
        <!-- /.info-box-content -->
      </div>
    </div>
<?php }
}
?>
	
  </div>
</div>
</div>
<!--[if lte IE 9]><script src="//cdnjs.cloudflare.com/ajax/libs/placeholders/3.0.2/placeholders.min.js"></script><![endif]-->
</body></html><?php
include "spct.php"; 
?>
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js"></script>
<!-- AdminLTE for demo purposes -->
