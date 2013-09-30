<?php

 /*     Licensed to the Apache Software Foundation (ASF) under one
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
 */
require_once('/var/www/html/sign_up/twitteroauth/twitteroauth.php');
require_once('/var/www/html/sign_up/config.php');
require_once('/var/www/apbb/util.php');

/**
 * Get blockees from /var/www/the_block_bot/blocks/
 *  .... Block a maximum of x at a time and only those not blocked and not followed ....
 */
#Start timer
$starttime=start_clock();
# Locking to make sure this script only runs once and nothing else runs at the same time
if (check_lock()){
	log_it("FATAL","BLOCKEM NOT RAN: BLOCKEM STILL RUNNING! MAY NEED TO REMOVE LOCK!");
	exit;
}
create_lock();

/* Create a TwitterOauth object with consumer/user tokens. */
$connection_BB = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, BB_ACCESSTOKEN, BB_ACCESSTOKENSECRET);

# Build blocklist and initialise block associative array to limit number of blocks applied per account...
$myblockees = array();
# Always Level 1
$blockees=get_users_in_dir(BLOCKS_L1);
$myblockees=$blockees[0];
# Store L1 blockees
$L1_blockees=$blockees[0];

# Get blockees...L2
$blockees=get_users_in_dir(BLOCKS_L2);
$tmp=array_merge($myblockees,$blockees[0]);
$myblockees=$tmp;
# Store L2 blockees
$L2_blockees=$blockees[0];

$blockees=get_users_in_dir(BLOCKS_L3);
$tmp=array_merge($myblockees,$blockees[0]);
$myblockees=$tmp;
# Store L3 blockees
$L3_blockees=$blockees[0];

# Associative array to hold the blocks
$my_limited_blockees = array();
# Got all blocks, build an array with a max blocks to be applied entry per block.
# This is a counter to make sure no more than MAX_BLOCKS are applied to an account in 15 minutes.
foreach ($myblockees as $blockee){
	$my_limited_blockees[$blockee] = MAX_BLOCKS;
}

# Get users...$users[0] are the numerical ids and $users[1] are the string ids
$users=get_users_in_dir(USERS_DIR);

for ($i=0; $i<=(count($users[0])-1); $i++)
{
	$userstarttime=start_clock();

	# Get user auth. Log start...
	$current_user=$users[1][$i];
	$current_user_id=$users[0][$i];
	$auth=get_user_auth($current_user_id,$current_user);
	
	# Addition to track blocks created by the bot for a user TBB2.1
	# List of blocks applied for the user by the bot ... Not added if the user had blocked a blockee themselves already
	$bot_blocks = array();
	$bot_blocks=get_bot_blocks($current_user_id);
	
	#Get block level from user @author jbilling
	$block_level=chop($auth[2]);
	# User being processed

	# Build master list of blocks to apply
	# Always Level 1 -- depending on the block level add the others too.
	$myblockees=$L1_blockees;
	if ($block_level==2 || $block_level==3) {
		$myblockees=array_merge($myblockees,$L2_blockees);
	}
	if ($block_level==3) {
		$myblockees=array_merge($myblockees,$L3_blockees);
	}

	log_it("INFO","PROCESSING BLOCKS FOR: ".$current_user_id."¬".$current_user." --> Level ".$block_level);
	
	if ((strlen($auth[0])>10) && (strlen($auth[1])>10)) {
		# Now need to get authorisation to use this users account
		/* Create a TwitterOauth object with consumer/user tokens. */
		$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $auth[0], $auth[1]);

		$user_info = $connection->get('account/verify_credentials');
		if(!isset($user_info->id))
		{
			if (isset($user_info->errors)){
				$errors=$user_info->errors;
				$error=$errors[0]->message;
			} else {
				$error="UNKNOWN ERROR";
			}
			log_it("FATAL","ACCOUNT REVOKED BLOCKBOT? ".$current_user." -->".$error);
		} else {
			# Get list of blocked users (blocks/ids)
			$data = $connection->get("blocks/ids");
			$blocked_users = $data->ids;

			# Get list of people followed (friends/ids)
			$data = $connection->get("friends/ids");
			$friends = $data->ids;
			# Check for errors, if this fails could block friends
			if(!isset($data->ids))
			{
				if (isset($user_info->errors)){
					$errors=$user_info->errors;
					$error=$errors[0]->message;
				} else {
					$error="UNKNOWN ERROR";
				}
				$blockee_screen_name=get_screen_name($x_users_to_block[$x],$connection);
				log_it("FATAL","ERROR GETTING FRIENDS FOR USER? ".$blockee_screen_name." for ".$current_user." Error: ".$error);
				# JUMP OUT OF LOOP - do not process blocks for this user!
				break;
			}
			
			# New array for custom blockees ... Created each time
			$my_custom_blockees = array();
			
			# Take list of blocked users from list to block, don't care about the ones already blocked
			$return = subtract_array($myblockees,$blocked_users);
			$my_custom_blockees = $return[0];
			
			# remove people already blocked by the bot. Don't re-block if the user has already blocked Added for TBB2.1
			$return = subtract_array($my_custom_blockees,$bot_blocks);
			$my_custom_blockees = $return[0];
			
			# Take list of followed users from list to block and get difference
			$return = subtract_array($my_custom_blockees,$friends);
			$my_custom_blockees = $return[0];
			
			# No point carrying on if nothing to block
			if (count($my_custom_blockees)>0) {
				
				# Need to only block the number of users that we have API calls left -> 15 max
				$x_users_to_block=get_x_from_array($my_custom_blockees,15);
				
				# Now block the users
				for ($x=0; $x<=(count($x_users_to_block)-1); $x++)
				{
					# First check the user is valid, no point blocking if they have gone
					if (is_valid_user_byid($x_users_to_block[$x],$connection)!=""){
						$err = $connection->post('blocks/create', array('user_id' => $x_users_to_block[$x], "skip_status" => "1"));
						if(!isset($err->id))
						{
							if (isset($user_info->errors)){
								$errors=$user_info->errors;
								$error=$errors[0]->message;
							} else {
								$error="UNKNOWN ERROR";
							}
							$blockee_screen_name=get_screen_name($x_users_to_block[$x],$connection);
							log_it("FATAL","ERROR BLOCKING? ".$blockee_screen_name." for ".$current_user." Error: ".$error);
						} else {
							log_it("INFO","BLOCKED USER : ".$x_users_to_block[$x]." FOR USER ".$current_user);	
							# Check the block was applied successfully, if so then add to the running tally of blocks applied for the account
							array_push($bot_blocks,  $x_users_to_block[$x]);
							
							# Now decrement from the array tracking how many blocks have been applied to a particular account
							$my_limited_blockees[$x_users_to_block[$x]]--;
							# Delete from the available blockees if the maximum blocks have been applied...
							# NB ADDED TO COMPLY WITH TWITTER LIMITS ON NUMBER OF BLOCKS ALLOWED TO AVOID ACCOUNT SUSPENSIONS TBB V2.1
							if ($my_limited_blockees[$x_users_to_block[$x]]<=0){
								$L1_blockees = array_diff($L1_blockees,array($x_users_to_block[$x]));
								$L2_blockees = array_diff($L2_blockees,array($x_users_to_block[$x]));
								$L3_blockees = array_diff($L3_blockees,array($x_users_to_block[$x]));
								log_it("INFO","BLOCKEE HIT LIMIT ON BLOCKING : ".$x_users_to_block[$x]);
							}
						}
					} else {
						$blockee_screen_name=get_screen_name($x_users_to_block[$x],$connection);
						log_it("ERROR","BLOCKEE NOT VALID : ".$x_users_to_block[$x]."¬".$blockee_screen_name." TRIED TO BLOCK FOR USER ".$current_user);
					}
				}
				# Now save the list of accounts blocked for this user to the directory. One per user, by ID as screenname could change
				write_bot_blocks($current_user_id,$bot_blocks);
			}
		}
	}
	stop_clock($userstarttime,"BLOCKEM FOR USER ".$current_user);
}
delete_lock();
stop_clock($starttime,"BLOCKEM TOTAL");
