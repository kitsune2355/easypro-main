<?
	
	/**** Class Database ****/
	Class MY_SQL
	{
		
		var $db_host;
		var $db_user;
		var $db_pass;
		var $db_name;
		var $db_connect;
		 
		 
		function MY_SQL()
		{
		$this->db_host = _host;
		$this->db_user = _db_user;
		$this->db_pass = _db_pass;
		$this->db_name = _db_name;
		}
		
		//ฟังก์ชั่นติดต่อฐานข้อมูล
		function fncConnectDB()
		{
		$this->db_connect  = @mysql_connect($this->db_host,$this->db_user,$this->db_pass) or die(mysql_error());
		}
		
		//ฟังก์ชั่นเลือกฐานข้อมูล
		function fncSelectDB()
		{
		@mysql_select_db($this->db_name) or die(mysql_error());
		}
		
		function fncNextID($strTable)
		{
			$q = mysql_query("show table status from $this->db_name like '$strTable'") or die(mysql_error()."<br/>Can't get next ID!<br/>show table status from $this->db_name like '$strTable' ");
			return mysql_result($q, 0, 'Auto_increment');
		}

		function fncLastID()
		{
			return mysql_insert_id();
		}

		/**** function insert record ****/
		function fncInsert($strTable,$strFieldAndValue)
		{
				
				  $strSQL ="INSERT INTO ".$strTable;
				  $f = "("; $val = " VALUES(";
				  for($i < 0 ; $i < count($strFieldAndValue);$i++){
					  $f .= key($strFieldAndValue);
					  if($i != (count($strFieldAndValue)-1))
					  $f .= ",";
					  $val .= "'".$strFieldAndValue[key($strFieldAndValue)]."'";
					  if($i != (count($strFieldAndValue) - 1))
					  $val .= ",";
				  next($strFieldAndValue);
				  }
				  $f .= ")";
				  $val .= ")";
				  $strSQL  .= $f.$val;
				
				return @mysql_query($strSQL) or die(mysql_error());
		}
		
				/**** function insert record ****/
		function fncInsertLastID($strTable,$strFieldAndValue)
		{
				
				  $strSQL ="INSERT INTO ".$strTable;
				  $f = "("; $val = " VALUES(";
				  for($i < 0 ; $i < count($strFieldAndValue);$i++){
					  $f .= key($strFieldAndValue);
					  if($i != (count($strFieldAndValue)-1))
					  $f .= ",";
					  $val .= "'".$strFieldAndValue[key($strFieldAndValue)]."'";
					  if($i != (count($strFieldAndValue) - 1))
					  $val .= ",";
				  next($strFieldAndValue);
				  }
				  $f .= ")";
				  $val .= ")";
				  $strSQL  .= $f.$val;
				
				return array(mysql_query($strSQL) or die(mysql_error()), mysql_insert_id());
				
		}
		
	    /**** function insert with select record ****/
		function fncInsertSelect($strTable,$strField,$OrgField,$OrgTable,$OrgCond)
		{
				
				$strSQL ="INSERT INTO ".$strTable." (".$strField.") ";
				$strSQL.="SELECT ".$OrgField." FROM ".$OrgTable." WHERE ".$OrgCond;
				return @mysql_query($strSQL) or die(mysql_error()."<br/>:".$strSQL);

		}

		/**** function select record ****/
		function fncSelect($strField,$strTable,$strCondition)
		{
				
				$strSQL = "SELECT $strField FROM $strTable $strCondition";
				$rs = @mysql_query($strSQL) or die(mysql_error()."<br/> : $strSQL");
				while($row = @mysql_fetch_array($rs))
				{
				$array[] = $row;
				}
				return $array;
				
		}
		
		
		/**** function select record ****/
		function fncSelectAssoc($strField,$strTable,$strCondition)
		{
			
				$strSQL = "SELECT $strField FROM $strTable $strCondition";
				$rs = @mysql_query($strSQL) or die(mysql_error()." : $strSQL");
				return @mysql_fetch_assoc($rs);
		
		}
		
		/**** function select record ****/
		function fncListTables($strSQL)
		{
				$rs = @mysql_query($strSQL) or die(mysql_error()." : $strSQL");
				while($row = @mysql_fetch_row($rs))
				{
					$array[] = $row;
				}
				return $array;
		}

		/**** function update record (argument) ****/
		function fncUpdate($strTable,$strValue,$key)
		{
				//$strSQL = "UPDATE $strTable SET $strCommand WHERE $strCondition ";
				//return @mysql_query($strSQL);
				
				$strSQL = "UPDATE ".$strTable." SET ";
				$w  = "";
				for($i < 0 ; $i < count($strValue);$i++)
				{
				$strSQL .= key($strValue)." = '".$strValue[key($strValue)]."'";
				if($i != (count($strValue)-1))
				$strSQL .= ", ";
				
				if($i == $key[$i])
				{
				$w .= key($strValue)." = '".$strValue[key($strValue)]."'";
				if($i != (count($key)-1))
				 $w .= " AND ";
				}  
				next($strValue);
				}
				$strSQL .= " WHERE ".$w;
				
				return @mysql_query($strSQL) or die (mysql_error()." : $strSQL");
				
		}
		
		/**** function update record (multiple row) ****/
		function fncUpdateSet($strTable,$strCondition)
		{
				$strSQL = "UPDATE $strTable SET $strCondition ";
				return mysql_query($strSQL);

		}

		/**** function delete record ****/
		function fncDelete($strTable,$strCondition)
		{
				$strSQL = "DELETE FROM $strTable WHERE $strCondition ";
				return @mysql_query($strSQL);
		}

		/*** end class auto disconnect ***/
		function __destruct() {
				return @mysql_close($this->objConnect);
	    }
		
		
		//ฟังก์ชั่นที่ทำให้ MySQL แสดงอักขระเป็น tis620
		function set_char_tis620()
		{
		$cs1 = "SET character_set_results=tis620";
		$cs2 = "SET character_set_client = tis620";
		$cs3 = "SET character_set_connection = tis620";
		@mysql_query($cs1) or die('Error query: ' . mysql_error());
		@mysql_query($cs2) or die('Error query: ' . mysql_error());
		@mysql_query($cs3) or die('Error query: ' . mysql_error());
		}
		 
		//ฟังก์ชั่นที่ทำให้ MySQL แสดงอักขระเป็น utf8
		function set_char_utf8()
		{
		$cs1 = "SET character_set_results=utf8";
		$cs2 = "SET character_set_client = utf8";
		$cs3 = "SET character_set_connection = utf8";
		@mysql_query($cs1) or die('Error query: ' . mysql_error());
		@mysql_query($cs2) or die('Error query: ' . mysql_error());
		@mysql_query($cs3) or die('Error query: ' . mysql_error());
		}
		
		 function fncClose()
		 {
		  @mysql_close($this->db_connect);
		 }
	}			

?>