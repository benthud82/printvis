<?php
Function _ftpupload($ftpfilename,$ftpwhse)
{
	//* Transfer file to FTP server *//
	$serverarray = array(2=>"10.1.112.199",3=>"10.1.22.212",6=>"10.1.17.208",7=>"10.1.18.194",9=>"10.1.16.206",11=>"10.10.200.209");
	$server = $serverarray[$ftpwhse];
	//$server = "10.1.16.206";
	$ftp_user_name = "anonymous";
	$ftp_user_pass = "anonymous@hsi.com";
	$dest = "$ftpfilename";
	$source = "./exports/$ftpfilename";
	$connection = ftp_connect($server);
	$login = ftp_login($connection, $ftp_user_name, $ftp_user_pass);
	if (!$connection || !$login) { die('Connection attempt failed!'); }
	echo "<br /><br />Uploading $ftpfilename for Whse $ftpwhse<br /><br />";
	$upload = ftp_put($connection, $dest, $source, FTP_ASCII);
	if (!$upload) { echo 'FTP upload failed!'; } else { echo'FTP Succeeded!';}
	print_r(error_get_last());
	ftp_close($connection); 
}

include '../connections/conn_printvis.php';
$whsearray = array(2, 3, 6, 7, 9);
$ftpdatetime = date('Ymd_His');

foreach ($whsearray as $whsesel)
{
	$sql_looselines_taskpred = $conn1->prepare("SELECT LPAD(batchtime_cart, 5, '0') AS batchnum, 
		CASE
			WHEN CAST(batchtime_time_totaltime AS UNSIGNED) > 999 THEN '00999'
			ELSE LPAD(CAST(batchtime_time_totaltime AS UNSIGNED), 5, '0')
			END AS MAXTIME
	FROM printvis.looselines_batchtime WHERE (batchtime_whse = $whsesel)");
	//FROM printvis.looselines_batchtime WHERE (batchtime_whse = $whsesel) AND (batchtime_exported = 0)");
	
	$sql_looselines_taskpred->execute();
	$numrows = $sql_looselines_taskpred->rowCount();
	if ($numrows > 0)
	{
		$filename = "picktimes_whse".$whsesel."_".$ftpdatetime.".gol";
		$fp = fopen("./exports/$filename", "w"); //open for write
		$data = "";
		$updatearray = array();
		foreach($sql_looselines_taskpred as $picktimerow)
		{
			$data .= $picktimerow['batchnum'].$picktimerow['MAXTIME']."\r\n";
			$updatearray[] = $picktimerow['batchnum'];
		}
		fwrite ($fp, $data);
		fclose ($fp);
		$updatewhere = implode(',', $updatearray);
		//$updateflag = $conn1->prepare("UPDATE printvis.looselines_batchtime SET batchtime_exported = 1 WHERE batchtime_cart IN($updatewhere)");
		//$updateflag->execute();
		$sendftp = _ftpupload($filename,$whsesel);
	}
}
?>
