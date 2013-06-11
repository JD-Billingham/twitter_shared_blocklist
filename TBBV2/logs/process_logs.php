<?php
/**
 * @file
 * Read the logs and create a csv for the days logs in it
 */

/* Load required lib files. */
require_once('/var/www/html/sign_up/config.php');
require_once('/var/www/apbb/util.php');

#Start timer
$starttime=start_clock();

# Get all the logs in the logs directory...
$files=glob(LOGS_DIR."*_log_*");

$csv_handle = fopen(WEB_ADMIN_LOGS_DIR."logs.csv", 'w') or die('..,ERROR,Cannot open file:  '.WEB_ADMIN_LOGS_DIR."logs.csv");
fwrite($csv_handle, "log_name,date,log_level,message\n");

foreach ($files as $file) {
	$file_handle = fopen($file, "r");
	while (!feof($file_handle)) {
		$line = fgets($file_handle);
		fwrite($csv_handle, basename($file).",$line");
	}
	fclose($file_handle);	
}
fclose($csv_handle);

stop_clock($starttime,"PROCESS LOGS");