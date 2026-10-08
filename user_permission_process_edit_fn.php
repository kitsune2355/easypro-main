<table style="width:100%;" border="0" cellspacing="0" cellpadding="0" class="text13normal" align="center">
  <tr style="background-color: #000000; color:#FFFFFF; padding:25px; font-weight:bold; height:35px; font-size:16px;">
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
	  <tr style="background-color: #E9E9E9; font-weight:bold; font-size:12px;">
	    <td background="img/m_c.gif">&nbsp;</td>
		<td height="25" background="img/m_c.gif">&nbsp;เมนู</td>
		<td width="60" background="img/m_c.gif"><div align="center">เพิ่ม <input name="CheckAll" type="checkbox" id="CheckAll" value="Y" onClick="ClickCheckAll(this);"></div></td>
		<td width="60" background="img/m_c.gif"><div align="center">แก้ไข <input name="CheckAllEdit" type="checkbox" id="CheckAll" value="Y" onClick="ClickCheckAllEdit(this);"></div></td>
		<td width="60" background="img/m_c.gif"><div align="center">ลบ <input name="CheckAlldel" type="checkbox" id="CheckAll" value="Y" onClick="ClickCheckAlldel(this);"></div></td>
		<td width="60" background="img/m_c.gif"><div align="center" >ดูข้อมูล <input name="CheckAllView" type="checkbox" id="CheckAll" value="Y" onClick="ClickCheckAllView(this);"></div></td>
		<td width="60" background="img/m_c.gif"><div align="center">พิมพ์ <input name="CheckAllPrint" type="checkbox" id="CheckAll" value="Y" onClick="ClickCheckAllPrint(this);"></div></td> 
	  </tr>
	<?
	$user_id		= $_POST['user_id'];
	$search_module	= $_POST['search_module'];
	$sql5="SELECT *
	  FROM tb_process_ms_ctrl
	  WHERE ody <> 0 
	  AND status = 1
	  "; 
	$sql5.="ORDER BY ody ASC";
	$query5 =mysqli_query($connect,$sql5) or die(mysqli_error($connect));
	$num_rows5 =mysqli_num_rows($query5);
	if ($num_rows5>=1){
		$iCountProcess = 0;
		while($rs5=mysqli_fetch_array($query5)) { 
	?> 
	 <tr bgcolor="#F3F3F3">
					<td colspan="7" class="brdrfashion"><strong><?=$rs5['process_name']?></strong></td> 
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
					<td class="brdrfashion">(<?=$rs['id']?>) <?=$rs['process_name']?><input name="process_id[<?=$iCountProcess?>]" id="process_id[<?=$iCountProcess?>]" type="hidden" 
					value="<?=$rs['id']?>" /></td>
					<td class="brdrfashion"><div align="center"><input name="ctrl_add[<?=$iCountProcess?>]" type="checkbox" id="ctrl_add<?=$iCountProcess?>" 
					value="1" <?=$rs_sb['ctrl_add']?> <?=$rs['ctrl_add']?>/></div></td>
					<td class="brdrfashion"><div align="center"><input name="ctrl_edit[<?=$iCountProcess?>]" type="checkbox" id="ctrl_edit<?=$iCountProcess?>" 
					value="1" <?=$rs_sb['ctrl_edit']?> <?=$rs['ctrl_edit']?> /></div></td>
					<td class="brdrfashion"><div align="center"><input name="ctrl_del[<?=$iCountProcess?>]" type="checkbox" id="ctrl_del<?=$iCountProcess?>" 
					value="1" <?=$rs_sb['ctrl_del']?> <?=$rs['ctrl_del']?> /></div></td>
					<td class="brdrfashion"><div align="center"><input name="ctrl_view[<?=$iCountProcess?>]" type="checkbox" id="ctrl_view<?=$iCountProcess?>" 
					value="1" <?=$rs_sb['ctrl_view']?> <?=$rs['ctrl_view']?> /></div></td>
					<td class="brdrfashion"><div align="center"><input name="ctrl_print[<?=$iCountProcess?>]" type="checkbox" id="ctrl_print<?=$iCountProcess?>" 
					value="1" <?=$rs_sb['ctrl_print']?> <?=$rs['ctrl_print']?> /></div></td> 
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
	  </tr> 
	</table></td>
	</tr>
</table>