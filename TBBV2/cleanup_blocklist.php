<?php
require_once('/var/www/html/sign_up/twitteroauth/twitteroauth.php');
require_once('/var/www/html/sign_up/config.php');
require_once('/var/www/apbb/util.php');
/**
 * Get blockees from /var/www/apbb/blocks/level_1/2/3
 *     Licensed to the Apache Software Foundation (ASF) under one
       or more contributor license agreements.  See the NOTICE file
       distributed with this work for additional information
       regarding copyright ownership.  The ASF licenses this file
       to you under the Apache License, Version 2.0 (the
       "License"); you may not use this file except in compliance
       with the License.  You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0

       Unless required by applicable law or agreed to in writing,
       software distributed under the License is distributed on an
       "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
       KIND, either express or implied.  See the License for the
       specific language governing permissions and limitations
       under the License.
 * Scan and if any names or ids change then reset the block list
 */
#Start timer
$starttime=start_clock();
# Locking to make sure this script only runs once and nothing else runs at the same time
if (check_lock()){
	log_it("FATAL","PICKUP MORPHS NOT RAN: BLOCKEM STILL RUNNING! MAY NEED TO REMOVE LOCK!");
	exit;
}

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, BB_ACCESSTOKEN, BB_ACCESSTOKENSECRET);

# Big call out to @secular_steve (Now @stevewcoa) for his great work in demonstrating this bug
# A user can morph their account so the numerical id appears to change -->
# 587589987¬secular_steve
# 1158787213¬secular_steve
# Whereas the original is now oool0n not secular_steve
# Without this code no one would get off the blocklist but the screen_name and ids would not match
# So just loop over the block list and where the user id does not match the screen name 
# -> delete and rewrite so it does!

#Need to loop for each level level1/2/3 and process separately
foreach (array("level_1/","level_2/","level_3/") as $level_dir) {
	# Get blockees...$blockees[0] are the numerical ids and $blockees[1] are the string ids
	$blockees=get_users_in_dir(BLOCKS.$level_dir);
	$my_user_id_blockees=$blockees[0];
	$my_screen_name_blockees=$blockees[1];
	
	# Now need to loop through and get user data for all of them.. Do they match or have they secular steve'd?
	log_it("INFO","Number of blocks: ".count($my_user_id_blockees));
	# Get array of Twitter user objects from the array of ids
	$users=get_users_from_ids($my_user_id_blockees,$connection);
	
	# Loop through em all and check if any in the list are not there at all.
	$twitter_ids = array();
	$twitter_screen_names = array();
	foreach ($users as $user) {
		$screen_name=strtolower($user->screen_name);
		$user_id=$user->id;
		$my_file = BLOCKS.$level_dir.$user_id.'¬'.$screen_name;
		if (file_exists($my_file)) {
			# Excellent... Now check it for duplicates and delete them
			$files = glob(BLOCKS.$level_dir.$user_id."¬*");
			foreach ($files as $file){
				if ($file != $my_file){
					log_it("ERROR","FOUND DUPLICATE FAKE: ".$file);
					unlink($file);
				}
			}
		} else {
			log_it("ERROR","FOUND MORPH! ".$level_dir.$user_id.'¬'.$screen_name);
			$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
			fwrite($handle, "blocked!");
			fclose($handle);
			log_it("INFO","NAUGHTY MORPH ADDED TO BLOCKLIST: ".$level_dir.$screen_name);
		}
		array_push($twitter_ids, $user_id);
		array_push($twitter_screen_names, $screen_name);
	}
	
	# REGET Get blockees.. Now the duplicates are gone.. 
	$blockees=get_users_in_dir(BLOCKS.$level_dir);
	$my_user_id_blockees=$blockees[0];
	$my_screen_name_blockees=$blockees[1];
	
	$user_ids_diff=array_diff($my_user_id_blockees,$twitter_ids);
	$screen_name_diff=array_diff($my_screen_name_blockees,$twitter_screen_names);
	
	#Check any ids for the right screen name 
	foreach ($user_ids_diff as $user_id) {
		$user=$connection->get("users/show",array('user_id' => $user_id));
	
		if (isset($user->screen_name)){
			$screen_name=strtolower($user->screen_name);
			$user_id=$user->id;
			# check to see if file exists ->
			$my_file = BLOCKS.$level_dir.$user_id.'¬'.$screen_name;
			if (file_exists($my_file)) {
				# Excellent... Do nothing!
			} else {
				log_it("ERROR","FOUND MORPH FROM HANGING IDS! ".$level_dir.$user_id.'¬'.$screen_name);
				$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
				fwrite($handle, "blocked!");
				fclose($handle);
				log_it("INFO","NAUGHTY MORPH ADDED TO BLOCKLIST FROM HANGING IDS: ".$level_dir.$screen_name);
			}
		} else {
			$files = glob(BLOCKS.$level_dir.$user_id."¬*");
			foreach ($files as $file) {
				log_it("ERROR","MOVED TO DEAD BLOCKS $file : USER ID IS AVAILABLE");
				$filename = substr(strrchr($file, "/"), 1);
				if (copy($file,DEAD_BLOCKS.$level_dir.$filename)) {
					unlink($file);
				}
			}
		}
	}
	
	#Check any screen names for the right id
	foreach ($screen_name_diff as $screen_name) {
		$user=$connection->get("users/show",array('screen_name' => $screen_name));
		if (isset($user->screen_name)){
			$screen_name=strtolower($user->screen_name);
			$user_id=$user->id;
			# check to see if file exists ->
			$my_file = BLOCKS.$level_dir.$user_id.'¬'.$screen_name;
			if (file_exists($my_file)) {
				# Excellent... Do nothing!
			} else {
				log_it("ERROR","FOUND MORPH FROM HANGING SCREEN NAME! ".$level_dir.$user_id.'¬'.$screen_name);
				$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
				fwrite($handle, "blocked!");
				fclose($handle);
				log_it("INFO","NAUGHTY MORPH ADDED TO BLOCKLIST FROM HANGING SCREEN NAME!: ".$level_dir.$screen_name);
			}
		} else {
			$files = glob(BLOCKS.$level_dir."*¬".$screen_name);
			foreach ($files as $file) {
				log_it("ERROR","MOVED TO DEAD BLOCKS $file : SCREEN NAME IS AVAILABLE");
				$filename = substr(strrchr($file, "/"), 1);
				if (copy($file,DEAD_BLOCKS.$level_dir.$filename)) {
					unlink($file);
				}
			}
		}
	}


	#Now scan the dead_blocks list and check all those little fellas out. Some may have resurrected!
	$blockees=get_users_in_dir(DEAD_BLOCKS.$level_dir);
	$my_user_id_blockees=$blockees[0];
	$my_screen_name_blockees=$blockees[1];
	
	# Now need to loop through and get user data for all of them.. Do they match or have they secular steve'd?
	log_it("INFO","Number of dead blocks: ".$level_dir." : ".count($my_user_id_blockees));
	
	if (count($my_user_id_blockees)>0){
		# Get array of Twitter user objects from the array of ids
		$users=get_users_from_ids($my_user_id_blockees,$connection);
		
		# Loop over any there and just create in blocks - delete the equivalent one from dead_blocks
		# Keep in mind it may have morphed!
		foreach ($users as $user) {
			$screen_name=strtolower($user->screen_name);
			$user_id=$user->id;
			$files = glob(DEAD_BLOCKS.$level_dir.$user_id."¬*");
			foreach ($files as $file){
				unlink($file);
				$my_file = BLOCKS.$level_dir.$user_id."¬".$screen_name;
				$handle = fopen($my_file, 'w') or die('Cannot open file:  '.$my_file);
				fwrite($handle, "blocked!");
				fclose($handle);
				log_it("ERROR","FOUND ZOMBIE IN DEAD BLOCKS! RE-ADDED TO BLOCKS: ".$file);
			}
		}
	}
}
stop_clock($starttime,"PICKUP MORPHS");

# return twitter user objects from an array of ids.
# Calls users/lookup in blocks of 90 to reduce calls
function get_users_from_ids($my_user_id_blockees,$connection){
	$num_per_call = 90;
	# Used to count how many have been added and what array index
	$count=0;
	$index=0;
	# Arrays of ids in comma separated list.
	$call_array_ids = array();
	$call_array_ids_tmp = array();
	for ($i=0; $i<=(count($my_user_id_blockees)-1); $i++)
	{
		# Do in blocks of x
		if ($count>=$num_per_call) {
		$count=0;
		$call_array_ids[$index]= implode(",", $call_array_ids_tmp);
		$call_array_ids_tmp = array();
		$index++;
	}
	array_push($call_array_ids_tmp, $my_user_id_blockees[$i]);
	$count++;
	}
	$call_array_ids[$index]= implode(",", $call_array_ids_tmp);
	
	$users=array();
	# Loop over all and get the corresponding user objects
	for ($i=0; $i<=(count($call_array_ids)-1); $i++)
	{
		$tmp_users=$connection->get("users/lookup",array('user_id' => $call_array_ids[$i]));
		if (!isset($tmp_users->errors)) {
			$users=array_merge($users,$tmp_users);
		}
	}
	return $users;
}