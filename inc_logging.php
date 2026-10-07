<?
$SQLLog = $sql;
if($SQLLog=="") $SQLLog = $sql_sb;
if($SQLLog=="") $SQLLog = $sql_ms;
$data_log = 'Time : '.date('Y-m-d H:i:s').' By. '.$sess_user_id.PHP_EOL.'SQL: '.$SQLLog.PHP_EOL.'-----------------------------------------'.PHP_EOL;
$myFile = 'logfile/LogFile_'.date('Y-m').'.txt';
$fp = fopen($myFile, 'a');
fwrite($fp, $data_log);
//fclose($myFile);
?>