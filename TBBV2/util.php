<?php

function get_x_from_array($array,$return_num) {
	$return_array=array();
	if (count($array)<=0){
		return $return_array;
	}
	$i=0;
	foreach ($array as $item){
		if ($i>=$return_num){
			return $return_array;
		}
		$i++;
		array_push($return_array, $item);
	}
	return $return_array;
}

function is_item_in_array($array,$id) {
	for ($i=0; $i<=(count($array)-1); $i++)
	{	
		if ($array[$i] == $id) {
			return true;
		}
	}
	return false;
}
function is_item_in_array_str($array,$str) {
	for ($i=0; $i<=(count($array)-1); $i++)
	{
		if (strtolower($array[$i]) == strtolower($str)) {
			return true;
		}
	}
	return false;
}

# check to see if the block is marked as one to block for spam
function is_user_spam($user_id) {
	$files = glob(BLOCKS_L1.$user_id."¬*");
	if (count($files)>1){
		log_it("ERROR","TOO MANY FILES FOUND is_user_spam ".$files);
		return false;
	}
	foreach ($files as $file){
		if (file_exists($file)) {
			$file_handle = fopen($file, "r");
			$line = fgets($file_handle);
			fclose($file_handle);
			if (preg_match("/spam/i", $line, $tmp)){
				return true;
			}
		} else {
			log_it("ERROR","FILE DOES NOT EXIST is_user_spam ".$file);
		}
	}
	return false;
}

function is_removed_block($screen_name)
{
	$results=get_users_in_dir(REM_BLOCKS_DIR);
	return is_item_in_array_str($results[1],$screen_name);
}
function is_user($screen_name)
{
	$results=get_users_in_dir(USERS_DIR);
	return is_item_in_array_str($results[1],$screen_name);
}
/* ADMIN Users -- 3 levels 
 * 1. Blockers can add to block lists only
 * 2. Admins can create Blockers
 * 3. Super Admin can create Admins and Blockers
 * What determines their level is are they in the relevant directory?
*/
function is_blocker($screen_name)
{
	$results=get_users_in_dir(BLOCKERS_DIR);
	return is_item_in_array_str($results[1],$screen_name);
}
function is_admin($screen_name)
{
	$results=get_users_in_dir(ADMINS_DIR);
	return is_item_in_array_str($results[1],$screen_name);
}
function is_super_admin($screen_name)
{
	$results=get_users_in_dir(SUPER_ADMINS_DIR);
	return is_item_in_array_str($results[1],$screen_name);
}
# Also keep is_authorised user for functions all three levels can do like adding blocks
function is_authorised_user($screen_name)
{
	if (is_blocker($screen_name)){
		return true;
	} else if (is_admin($screen_name)){
		return true;
	} else if (is_super_admin($screen_name)){
		return true;
	} 
}

/* Remove the array passed to the function and return the remainder */
function subtract_array($blockees_arr,$remove_arr){
	# remove the items in the remove array from the blockees array
	# Any removed from blockees are returned as a separate array
	if (count($remove_arr)==0){
		return array($blockees_arr,$remove_arr);
	}
	$new_blockees = array_diff($blockees_arr, $remove_arr);
	$removed_arr=array_diff($blockees_arr, $new_blockees);
	
	return array($new_blockees,$removed_arr);
}

/* Read the users in the dir - return string ids and numerical ids */
function get_users_in_dir($dir)
{
	$numerical_users = array();
	$string_users = array();
	# Get files in order, newest first
	$files=listdir_by_date($dir);
	/* This is the correct way to loop over the directory. */
	foreach ($files as $file){
		if (preg_match("/(\S+)\¬(\S+)/i", $file, $result)) {
			# Build arrays
			array_push($numerical_users, chop($result[1]));
			array_push($string_users, chop($result[2]));
		}
	}

	return array($numerical_users,$string_users);
}

/* Read the blocks in the dir(s) depending on level - return string ids and numerical ids */
function get_blocks_by_level($level)
{
	if ($level==3){
		$result=get_users_in_dir(BLOCKS_L3);
		return array($result[0],$result[1]);
	}
	if ($level==2){
		$result=get_users_in_dir(BLOCKS_L2);
		return array($result[0],$result[1]);
	}
	if ($level==1){
		$result=get_users_in_dir(BLOCKS_L1);
		return array($result[0],$result[1]);
	}
	# return empty array if given dodgy input..
	$numerical_users = array();
	$string_users = array();
	return array($numerical_users,$string_users);
}

/* Read the files in the dir - return in order - newest first
 * Makes sure newest additions are blocked first and newest users are serviced first
 */
function listdir_by_date($path){
	$dir = opendir($path);
	$list = array();
	while($file = readdir($dir)){
		if ($file != '.' and $file != '..'){
			// add the filename, to be sure not to
			// overwrite a array key
			$ctime = filectime($path."/".$file) . ',' . $file;
			$list[$ctime] = $file;
		}
	}
	closedir($dir);
	krsort($list);
	return $list;
}

/* User Auth is token,secret,level so this returns an array of three items
*/
function get_user_auth($num_id,$str_id)
{
	$auth = array();
	$my_user_file=USERS_DIR.$num_id."¬".$str_id;
	if (file_exists($my_user_file)) {
		$file_handle = fopen($my_user_file, "r");
		$line = fgets($file_handle);
		$auth = preg_split("/,/", $line,-1,PREG_SPLIT_NO_EMPTY);
		fclose($file_handle);
	}
	return $auth;
}

function is_valid_user($screen_name,$connection)
{
    $user_info = $connection->get('users/show',array('screen_name' => $screen_name));
    if(!isset($user_info->id)){
    	#echo "ERROR: USER NOT VALID: ".$screen_name."\n";
        return 0;
    } else {
        return $user_info->id;
    }
}
function is_valid_user_byid($user_id,$connection)
{
	$user_info = $connection->get('users/show',array('user_id' => $user_id));
	if(!isset($user_info->id)){
		#echo "ERROR: USER NOT VALID: ".$screen_name."\n";
		return "";
	} else {
		return $user_info->screen_name;
	}
}
function get_screen_name($user_id,$connection)
{
    $user_info = $connection->get('users/show',array('user_id' => $user_id));
    if(!isset($user_info->screen_name)){
    	#echo "ERROR: USER NOT VALID: ".$user_id."\n";
        return false;
    } else {
        return $user_info->screen_name;
    }
}

# Timer
function stop_clock($starttime,$script){
	$mtime = microtime();
	$mtime = explode(" ",$mtime);
	$mtime = $mtime[1] + $mtime[0];
	$endtime = $mtime;
	$totaltime = ($endtime - $starttime);
	if (strlen($script)>0){
		log_it("TIMING",$script." - TOOK: ".$totaltime." seconds");
	}
	return $totaltime;
}
function start_clock(){
	$mtime = microtime();
	$mtime = explode(" ",$mtime);
	$mtime = $mtime[1] + $mtime[0];
	$starttime = $mtime;
	return $starttime;
}

#CHeck lock - return true if file exists
function check_lock(){
	$my_lock_file=ROOT_DIR."lock";
	if (file_exists($my_lock_file)) {
		return true;
	}
	return false;
}
function create_lock(){
	$my_lock_file=ROOT_DIR."lock";
	$handle = fopen($my_lock_file, 'w') or die('...,ERROR,Cannot open file:  '.$my_file);
	date_default_timezone_set("GMT");
	fwrite($handle, "locked! ".date("D M j G:i:s"));
	fclose($handle);
}
function delete_lock(){
	$my_lock_file=ROOT_DIR."lock";
	unlink($my_lock_file);
}
# Function to log errors and messages
function log_it($type,$message) {
	date_default_timezone_set("GMT");
	# make sure all lines are date stamped date(DATE_RFC822);
	echo date("D M j G:i:s").",".$type.",".$message."\n";
}
# function to create file in a given directory with a given content
# Used in get_blocks to create block file with "block" or "spam" in it...
function create_file($file,$content) {
	# return false if the file already exists
	if (file_exists($file)) {
		return false;
	} 
	$handle = fopen($file, 'w') or die('..,ERROR,Cannot open file:  '.$my_file);
	fwrite($handle, $content);
	fclose($handle);
	return true;
}