<?php	
	// $myfile = fopen("AttFile/newfile225.txt", "w") or die("Unable to open file!");
//$txt = "John Doe\n";
//fwrite($myfile, $txt);
//$txt = "Jane Doe\n";
//fwrite($myfile, $txt);
//fclose($myfile);
$dataB = $_GET['P']; 
$txt = $dataB;
$handle = fopen($txt, "r");
if ($handle) {

    while (!feof($handle)) {
        $line = fgets($handle);
       
    }
 echo  '<img   class="elevation-2 img-circle" src="'.$line.'" >';
    fclose($handle);
} else {
    echo "Error opening file.\n";   
}
fclose($handle)
?>
