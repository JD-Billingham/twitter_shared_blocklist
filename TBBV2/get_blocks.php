<?php
/**
* @file
* Reads the_block_bot mentions and any adds to block list or authorised users are processed
* 
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
*/
require_once('/var/www/html/sign_up/twitteroauth/twitteroauth.php');
require_once('/var/www/html/sign_up/config.php');
require_once('/var/www/apbb/util.php');
#Start timer
$starttime=start_clock();
# Exit if script is already running... Possible if lots of blocks to process
if (check_lock()){
	log_it("FATAL","GET BLOCKS NOT RAN: BLOCKEM STILL RUNNING! MAY NEED TO REMOVE LOCK!");
	exit;
}

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, BB_ACCESSTOKEN, BB_ACCESSTOKENSECRET);

# Make sure tweets got are after the last one retreived
$last_tweet_id=0;
if (file_exists(ROOT_DIR."/last_getblocks.json")) {
	$handle = fopen(ROOT_DIR."/last_getblocks.json", 'r') or die('nodate,FATAL,Cannot open file: '.ROOT_DIR."/last_getblocks.json");
	$line = fgets($handle);
	$obj = json_decode($line);
	fclose($handle);
	$last_tweet_id = $obj->{'last_tweet_id'}; // 12345
}

# Needs to be under 180 as is_valid_user calls a rate limited API call: 30 seems to work well
if ($last_tweet_id>0){
	$timeline = $connection->get("statuses/mentions_timeline",array('count' => 100, "since_id" => "$last_tweet_id"));
} else {
	$timeline = $connection->get("statuses/mentions_timeline",array('count' => 100));
}
#print_r($timeline);

# Need to make sure no more than x are added in one interval
$api_count=0;
$max=20; # Low number but will really piss people off if it tweets more than this in one go!
#No actual rate limit for tweets -> apart from 1000 in day
# The is_valid_user is 180 in a 15 min period.

# Do Blocks
foreach($timeline as $tweet){
	# So any tweet with #report or #block in it is to be saved to tweet reports for audit / sharing of tweets
	if (preg_match("/#block/i", $tweet->text, $tmp) || preg_match("/#report/i", $tweet->text, $tmp)){
		# Write it out to the directory
		if (!file_exists(ROOT_DIR."/reports/tweets/".$tweet->user->screen_name."_".$tweet->id_str.".json")) {
			$handle = fopen(ROOT_DIR."/reports/tweets/".$tweet->user->screen_name."_".$tweet->id_str.".json", 'w') or die("Cannot open file: ".ROOT_DIR."/reports/tweets/".$tweet->screen_name."_".$tweet->id_str.".json");
			fwrite($handle, json_encode($tweet));
			fclose($handle);
		}
	}
	# Now handle the block commands
	if (preg_match("/#Block/i", $tweet->text, $tmp)){
	    preg_match_all("/\+(\S+)/i", $tweet->text, &$results);
		foreach ($results[0] as $result){
			if (is_authorised_user($tweet->user->screen_name)){
				$blockee=preg_replace('/\+/', '', $result);
				$blockee = strtolower($blockee);
				# Need to check the potential blockee is not a user or someone who has been removed from the block list
				if (!is_user($blockee) && !is_removed_block($blockee)) {
					# Don't want to block authorised users... 
					if (!is_authorised_user($blockee)) {
						$user_id=is_valid_user($blockee,$connection);						
						if ($user_id>0){
							#Now need to check what level of block they are...
							$block_level=3;
							$abuse_or_spam=false;
							# Need to check to see if the blockee actually ended up in any new block list L1/2/3
							$created = false;
							$reported = false;
							# Only at Do abuse check... Direct to report for abuse if set
							if (preg_match("/#spam|#abuse/i", $tweet->text, $tmp)){
								$abuse_or_spam=true;
							}
							if (preg_match("/(#SuperSlimy|#Level1)/i", $tweet->text, $tmp)){
								$block_level=1;
								# ALWAYS create -> NO review.. These are rarely ambiguous. Will have to just remove any mistakes
								# just in case they have been promoted!
								if (file_exists(BLOCKS_L2.$user_id.'¬'.$blockee)){unlink(BLOCKS_L2.$user_id.'¬'.$blockee);}
								if (file_exists(BLOCKS_L3.$user_id.'¬'.$blockee)){unlink(BLOCKS_L3.$user_id.'¬'.$blockee);}
								if (file_exists(BLOCKS_L4.$user_id.'¬'.$blockee)){unlink(BLOCKS_L4.$user_id.'¬'.$blockee);}
								# If already there don't want to tweet about it
								if (create_file(BLOCKS_L1.$user_id.'¬'.$blockee,"")) {
									$created = true;
								} 
							} else if (preg_match("/(#PeskyPittizen|#Level2)/i", $tweet->text, $tmp) && !file_exists(BLOCKS_L1.$user_id.'¬'.$blockee)){
								$block_level=2;
								if (is_in_blocklist($user_id,$blockee)) {
									# just in case they have been promoted or demoted!
									if (file_exists(BLOCKS_L1.$user_id.'¬'.$blockee)){unlink(BLOCKS_L2.$user_id.'¬'.$blockee);}
									if (file_exists(BLOCKS_L3.$user_id.'¬'.$blockee)){unlink(BLOCKS_L3.$user_id.'¬'.$blockee);}
									if (file_exists(BLOCKS_L4.$user_id.'¬'.$blockee)){unlink(BLOCKS_L4.$user_id.'¬'.$blockee);}
									# If already there don't want to tweet about it
									if (create_file(BLOCKS_L2.$user_id.'¬'.$blockee,"")) {
										$created = true;
									}
								} else {
									# Just add to the reporting level
									create_file(BLOCKS_L4.$user_id.'¬'.$blockee,"");
									$reported = true;
								}
							} else if (!file_exists(BLOCKS_L1.$user_id.'¬'.$blockee) && !file_exists(BLOCKS_L2.$user_id.'¬'.$blockee)){
								if (is_in_blocklist($user_id,$blockee)) {
									# just in case they have been promoted or demoted!
									if (file_exists(BLOCKS_L1.$user_id.'¬'.$blockee)){unlink(BLOCKS_L2.$user_id.'¬'.$blockee);}
									if (file_exists(BLOCKS_L2.$user_id.'¬'.$blockee)){unlink(BLOCKS_L3.$user_id.'¬'.$blockee);}
									if (file_exists(BLOCKS_L4.$user_id.'¬'.$blockee)){unlink(BLOCKS_L4.$user_id.'¬'.$blockee);}
									# If already there don't want to tweet about it
									if (create_file(BLOCKS_L3.$user_id.'¬'.$blockee,"")) {
										$created = true;
									}
								} else {
									# Just add to the reporting level
									create_file(BLOCKS_L4.$user_id.'¬'.$blockee,"");
									$reported = true;
								}
							}
							if (!$created) {
								log_it("INFO","USER ALREADY IN BLOCKLIST: ".$blockee." BLOCKER:".$tweet->user->screen_name);
								if ($reported) {
									$connection->post('statuses/update', array('status' => 'Please review REPORT on https://twitter.com/'.$blockee.', requires second blocker to confirm addition #AtheismPlus https://twitter.com/the_block_bot/status/'.$tweet->id_str));
								}
							} else {
								log_it("INFO","USER ADDED TO BLOCKLIST: ".$blockee." BLOCKER:".$tweet->user->screen_name);
								if ($abuse_or_spam) {
										$connection->post('statuses/update', array('status' => 'I just added https://twitter.com/'.$blockee.' to my L'.$block_level.' blocklist #AtheismPlus https://twitter.com/the_block_bot/status/'.$tweet->id_str.' -> Report abusive user https://support.twitter.com/forms/'));
								} else {
										$connection->post('statuses/update', array('status' => 'I just added https://twitter.com/'.$blockee.' to my L'.$block_level.' blocklist #AtheismPlus https://twitter.com/the_block_bot/status/'.$tweet->id_str));
								}
								# Do no more than X or risk getting kicked off Twitter...
								$api_count++;
								usleep(0.5*1000000);
								if ($api_count>=$max){
									stop_clock($starttime,"API LIMIT EXIT: GET BLOCKS : BLOCK");
									exit;
								}
							}
						}
					} else {
						log_it("ERROR","ATTEMPT BY AUTHORISED USER: ".$tweet->user->screen_name." TO ADD AN AUTHORISED USER: ".$blockee." AS BLOCK");
					}
				}else {
					log_it("ERROR","ATTEMPT BY AUTHORISED USER: ".$tweet->user->screen_name." TO ADD A USER: ".$blockee." AS BLOCK");
				}
			} else {
				log_it("ERROR","ATTEMPT BY UNAUTHORISED USER: ".$tweet->user->screen_name." TO ADD USER AS BLOCK");
			}
		}
	}
}

# After scanning each tweet write the last tweet to a JSON
# Write out file for last tweet file id
if (count($timeline)>0){
	$handle = fopen(ROOT_DIR."/last_getblocks.json", 'w') or die('Cannot open file: '.ROOT_DIR."/last_getblocks.json");
	fwrite($handle, '{"last_tweet_id":"'.$timeline[0]->id_str.'"}');
	fclose($handle);
}

#Do Add Blockers
foreach($timeline as $tweet){
	if (preg_match("/#AddBlocker/i", $tweet->text, $tmp)){
		preg_match_all("/\@(\S+)/i", $tweet->text, &$results);
		foreach ($results[0] as $result){
			if (is_admin($tweet->user->screen_name) || is_super_admin($tweet->user->screen_name)){
				$auth_user=preg_replace('/@/', '', $result);
				$auth_user = strtolower($auth_user);
				#if (is_user($auth_user)) { # NO LONGER NEED TO BE A USER FIRST
					if (!is_authorised_user($auth_user)) {
						$user_id=is_valid_user($auth_user,$connection);
						if ($user_id>0){
							$my_file = BLOCKERS_DIR.$user_id.'¬'.$auth_user;
							if (file_exists($my_file)) {
								log_it("ERROR","USER ".$tweet->user->screen_name." ATTEMPED TO ADD $auth_user ALREADY IN BLOCKERS OR ADMIN LIST");
							} else {
								$handle = fopen($my_file, 'w') or die('..,ERROR,Cannot open file:  '.$my_file);
								fwrite($handle, "authorised!");
								fclose($handle);
								log_it("INFO","USER ADDED TO BLOCKERS: $auth_user");
								$connection->post('statuses/update', array('status' => 'I just added @'.$auth_user.' to my blockers list'));
								# Do no more than 20 or risk getting kicked off Twitter...
								$api_count++;
								if ($api_count>=$max){
									stop_clock($starttime,"ERROR: API LIMIT EXIT: GET BLOCKS: ADD BLOCKER");
									exit;
								}
							}
						} else {
							log_it("ERROR","ATTEMPT BY ".$tweet->user->screen_name." TO ADD INVALID USER AS BLOCKER: ".$auth_user);
						}
					}
				#}
			} else {
				log_it("ERROR","NON AUTHORISED USER ATTEMPT TO ADD BLOCKER: ".$tweet->user->screen_name);
			}
		}
	}
}

#Do REMOVE Blockers
foreach($timeline as $tweet){
	if (preg_match("/#RemoveBlocker/i", $tweet->text, $tmp)){
		preg_match_all("/\@(\S+)/i", $tweet->text, &$results);
		foreach ($results[0] as $result){
			if (is_admin($tweet->user->screen_name) || is_super_admin($tweet->user->screen_name)){
				$auth_user=preg_replace('/@/', '', $result);
				$auth_user = strtolower($auth_user);
				#if (is_user($auth_user)) { # NO LONGER NEED TO BE A USER FIRST
				if (is_authorised_user($auth_user)) {
					$user_id=is_valid_user($auth_user,$connection);
					if ($user_id>0){
						$my_file = BLOCKERS_DIR.$user_id.'¬'.$auth_user;
						if (file_exists($my_file)) {
							unlink($my_file);
							log_it("INFO","USER ".$tweet->user->screen_name." REMOVED $auth_user FROM BLOCKERS LIST");
						} else {
							log_it("ERROR","USER ".$tweet->user->screen_name." TRIED TO REMOVE NON EXISTANT USER - $auth_user FROM BLOCKERS LIST");
						}
					} else {
						log_it("ERROR","ATTEMPT BY ".$tweet->user->screen_name." TO ADD INVALID USER AS BLOCKER: ".$auth_user);
					}
				}
				#}
			 } else {
				log_it("ERROR","NON AUTHORISED USER ATTEMPT TO ADD BLOCKER: ".$tweet->user->screen_name);
			 }
			}
	}
}

#Do Add Admins - Need to be Super Admin or in this case the Block Bot
foreach($timeline as $tweet){
	if (preg_match("/#AddAdmin/i", $tweet->text, $tmp)){
		preg_match_all("/\@(\S+)/i", $tweet->text, &$results);
		foreach ($results[0] as $result){
			if (is_super_admin($tweet->user->screen_name)){
				$auth_user=preg_replace('/@/', '', $result);
				$auth_user = strtolower($auth_user);
				#if (is_user($auth_user)) { # NO LONGER NEED TO BE A USER FIRST
				# No point adding if the person is an admin or super admin already
				if (!is_admin($auth_user) && !is_super_admin($auth_user)) {
					$user_id=is_valid_user($auth_user,$connection);
					if ($user_id>0){
						$my_file = ADMINS_DIR.$user_id.'¬'.$auth_user;
						if (file_exists($my_file)) {
							log_it("ERROR","USER ".$tweet->user->screen_name." ATTEMPED TO ADD $auth_user ALREADY IN ADMIN LIST");
						} else {
							$handle = fopen($my_file, 'w') or die('..,ERROR,Cannot open file:  '.$my_file);
							fwrite($handle, "authorised!");
							fclose($handle);
							log_it("INFO","USER ADDED TO ADMINS: $auth_user");
							$connection->post('statuses/update', array('status' => 'I just added @'.$auth_user.' to my admins list'));
							# Do no more than 20 or risk getting kicked off Twitter...
							$api_count++;
							if ($api_count>=$max){
								stop_clock($starttime,"ERROR: API LIMIT EXIT: GET BLOCKS: ADD ADMIN");
								exit;
							}
						}
					} else {
						log_it("ERROR","ATTEMPT BY ".$tweet->user->screen_name." TO ADD INVALID USER AS ADMIN: ".$auth_user);
					}
				}
				#}
			} else {
				log_it("ERROR","NON AUTHORISED USER ATTEMPT TO ADD ADMIN USER: ".$tweet->user->screen_name);
				}
		}
	}
}

#Do Block Removal --> This is a mention so the de-blockee is notified
foreach($timeline as $tweet){
	if (preg_match("/#RemoveFromBlockList/i", $tweet->text, $tmp)){
		preg_match_all("/\@(\S+)/i", $tweet->text, &$results);
		foreach ($results[0] as $result){
			if (is_authorised_user($tweet->user->screen_name)){
				$blocked_user=preg_replace('/@/', '', $result);
				$blocked_user = strtolower($blocked_user);
				if (!is_removed_block($blocked_user)) {
					$user_id=is_valid_user($blocked_user,$connection);
					if ($user_id>0){
						$my_file_L1 = BLOCKS_L1.$user_id.'¬'.$blocked_user;
						$my_file_L2 = BLOCKS_L2.$user_id.'¬'.$blocked_user;
						$my_file_L3 = BLOCKS_L3.$user_id.'¬'.$blocked_user;
						$my_file_L4 = BLOCKS_L4.$user_id.'¬'.$blocked_user;
						if (file_exists($my_file_L1) || file_exists($my_file_L2) || file_exists($my_file_L3) || file_exists($my_file_L4)) {
							if (file_exists($my_file_L1)) {unlink($my_file_L1);}
							if (file_exists($my_file_L2)) {unlink($my_file_L2);}
							if (file_exists($my_file_L3)) {unlink($my_file_L3);}
							if (file_exists($my_file_L4)) {unlink($my_file_L4);}
							# Need to create a file in removed blocks for this user so they are not re-added instantly
							create_file(REM_BLOCKS_DIR.$user_id.'¬'.$blocked_user,"removed!");
							log_it("INFO","USER REMOVED FROM BLOCK LIST: $blocked_user");
							$connection->post('statuses/update', array('status' => 'I just REMOVED @'.$blocked_user.' from my block list, you might want to as well!'));
						} else {
							log_it("ERROR","USER $blocked_user ALREADY MANUALLY REMOVED FROM BLOCKLIST");
						}
					} else {
						log_it("ERROR","ATTEMPT BY ".$tweet->user->screen_name." TO REMOVE INVALID USER FROM BLOCK LIST: ".$blocked_user);
					}
				} else {
					log_it("ERROR","USER $blocked_user ALREADY REMOVED FROM BLOCKLIST");
				}
			} else {
				log_it("ERROR","NON AUTHORISED USER ATTEMPT TO REMOVE USER FROM BLOCK LIST: ".$tweet->user->screen_name);
				}
		}
	}
}

#Do Block ReLevelling 
foreach($timeline as $tweet){
	if (preg_match("/#ReLevel/i", $tweet->text, $tmp)){
		preg_match_all("/\+(\S+)/i", $tweet->text, &$results);
		foreach ($results[0] as $result){
			if (is_authorised_user($tweet->user->screen_name)){
				$blocked_user=preg_replace('/\+/','', $result);
				$blocked_user = strtolower($blocked_user);
				if (!is_removed_block($blocked_user)) {
					$user_id=is_valid_user($blocked_user,$connection);
					if ($user_id>0){
						$my_file_L1 = BLOCKS_L1.$user_id.'¬'.$blocked_user;
						$my_file_L2 = BLOCKS_L2.$user_id.'¬'.$blocked_user;
						$my_file_L3 = BLOCKS_L3.$user_id.'¬'.$blocked_user;
						$my_file_L4 = BLOCKS_L4.$user_id.'¬'.$blocked_user;
						$start_level =0;
						$end_level =0;
						if (is_in_blocklist($user_id,$blocked_user)) {
							if (file_exists($my_file_L1)) {unlink($my_file_L1);$start_level=1;}
							if (file_exists($my_file_L2)) {unlink($my_file_L2);$start_level=2;}
							if (file_exists($my_file_L3)) {unlink($my_file_L3);$start_level=3;}
							if (file_exists($my_file_L4)) {unlink($my_file_L4);$start_level=4;}
							# Need to create a file in the correct level for this user - only moves down as a re-add ups them so L2/3/4
							# Level 4 by default if nothing is specified
							if (preg_match("/#Level2/i", $tweet->text, $tmp)){
								create_file(BLOCKS_L2.$user_id.'¬'.$blocked_user,"");
								$end_level = 2;
							} else if (preg_match("/#Level3/i", $tweet->text, $tmp)){
								create_file(BLOCKS_L3.$user_id.'¬'.$blocked_user,"");
								$end_level = 3;
							} else {
								create_file(BLOCKS_L4.$user_id.'¬'.$blocked_user,"");
								$end_level = 4;
							}
							log_it("INFO","USER $blocked_user MOVED FROM LEVEL $start_level TO: $end_level");
						} else {
							log_it("ERROR","USER $blocked_user ALREADY MANUALLY REMOVED FROM BLOCKLIST");
						}
					} else {
						log_it("ERROR","ATTEMPT BY ".$tweet->user->screen_name." TO MOVE LEVEL FOR : ".$blocked_user);
					}
				} else {
					log_it("ERROR","USER $blocked_user REMOVED FROM BLOCKLIST");
				}
			} else {
				log_it("ERROR","NON AUTHORISED USER ATTEMPT TO MOVE LEVEL OF USER ON BLOCK LIST: ".$tweet->user->screen_name);
				}
		}
	}
}
stop_clock($starttime,"GET BLOCKS");

# Check if blockee is anywhere in the block list 1-4
function is_in_blocklist($user_id,$blockee) {
	if (file_exists(BLOCKS_L3.$user_id.'¬'.$blockee)) {
		return true;
	} else if (file_exists(BLOCKS_L2.$user_id.'¬'.$blockee)) {
		return true;
	} else if (file_exists(BLOCKS_L1.$user_id.'¬'.$blockee)) {
		return true;
	} else if (file_exists(BLOCKS_L4.$user_id.'¬'.$blockee)) {
		return true;
	} 
	return false;
}
