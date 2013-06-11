<?php
/**
 * @file
 * Read the blocks directory and create a CSV with all the blocks in it...
 * Used by the atheism plus ZAPPER! By @aratina
 */

/* Load required lib files. */
require_once('/var/www/html/sign_up/config.php');
require_once('/var/www/apbb/util.php');

#Start timer
$starttime=start_clock();

#Set up table...
#Get blockees...
#Get blockees by level...
$result=get_blocks_by_level(1);
$blockees_level_1=$result[1];
$blockees_level_1_ids=$result[0];
$result=get_blocks_by_level(2);
$blockees_level_2=$result[1];
$blockees_level_2_ids=$result[0];
$result=get_blocks_by_level(3);
$blockees_level_3=$result[1];
$blockees_level_3_ids=$result[0];

$handle = fopen(WEB_ROOT_DIR."/followthefollowers/blocks.csv", 'w') or die('Cannot open file: '.WEB_ROOT_DIR."/followthefollowers/blocks.csv");

$bits = array();
foreach ($blockees_level_1 as $screen_name){
	array_push ($bits, $screen_name);
	array_push ($bits, 1);
}
foreach ($blockees_level_2 as $screen_name){
	array_push ($bits, $screen_name);
	array_push ($bits, 2);
}
foreach ($blockees_level_3 as $screen_name){
	array_push ($bits, $screen_name);
	array_push ($bits, 3);
}

fwrite($handle, implode(",",$bits));
fclose($handle);
stop_clock($starttime,"CREATE SIMPLE BLOCKS CSV");