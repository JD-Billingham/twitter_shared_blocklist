<?php
/**
* @file
* Reads the_block_bot mentions and any adds to block list or authorised users are processed
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

# Needs to be under 180 as is_valid_user calls a rate limited API call: 30 seems to work well
$timeline = $connection->get("statuses/mentions_timeline",array('count' => 30));
#print_r($timeline);

# Need to make sure no more than x are added in one interval
$api_count=0;
$max=20; # Low number but will really piss people off if it tweets more than this in one go!
#No actual rate limit for tweets -> apart from 1000 in day
# The is_valid_user is 180 in a 15 min period.

# Do Blocks
foreach($timeline as $tweet){
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
							$block_or_spam="block";
							# Need to check to see if the blockee actually ended up in any new block list L1/2/3
							$created = false;
							if (preg_match("/(#SuperSlimy|#Level1)/i", $tweet->text, $tmp)){
								$block_level=1;
								# Only at level 1, do spam check...
								if (preg_match("/#spam/i", $tweet->text, $tmp)){
									$block_or_spam="spam";
								}
								if (create_file(BLOCKS_L1.$user_id.'¬'.$blockee,$block_or_spam)) {
									$created = true;
									# just in case they have been promoted!
									unlink(BLOCKS_L2.$user_id.'¬'.$blockee);
									unlink(BLOCKS_L3.$user_id.'¬'.$blockee);
								} 
							} else if (preg_match("/(#PeskyPittizen|#Level2)/i", $tweet->text, $tmp) && !file_exists(BLOCKS_L1.$user_id.'¬'.$blockee)){
								$block_level=2;
								if (create_file(BLOCKS_L2.$user_id.'¬'.$blockee,$block_or_spam)) {
									$created = true;
									# just in case they have been promoted!
									unlink(BLOCKS_L3.$user_id.'¬'.$blockee);
								}
							} else if (!file_exists(BLOCKS_L1.$user_id.'¬'.$blockee) && !file_exists(BLOCKS_L2.$user_id.'¬'.$blockee)){
								if (create_file(BLOCKS_L3.$user_id.'¬'.$blockee,$block_or_spam)) {
									$created = true;
								}
							}
							if (!$created) {
								log_it("INFO","USER ALREADY IN BLOCKLIST: ".$blockee);
							} else {
								log_it("INFO","USER ADDED TO BLOCKLIST: ".$blockee);
								$connection->post('statuses/update', array('status' => 'I just added https://twitter.com/'.$blockee.' to my level '.$block_level.' blocklist #AtheismPlus'));
								# Do no more than X or risk getting kicked off Twitter...
								$api_count++;
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
			if (is_admin($tweet->user->screen_name)||is_super_admin($tweet->user->screen_name)){
				$blocked_user=preg_replace('/@/', '', $result);
				$blocked_user = strtolower($blocked_user);
				if (!is_removed_block($blocked_user)) {
					$user_id=is_valid_user($blocked_user,$connection);
					if ($user_id>0){
						$my_file_L1 = BLOCKS_L1.$user_id.'¬'.$blocked_user;
						$my_file_L2 = BLOCKS_L2.$user_id.'¬'.$blocked_user;
						$my_file_L3 = BLOCKS_L3.$user_id.'¬'.$blocked_user;
						if (file_exists($my_file_L1) || file_exists($my_file_L2) || file_exists($my_file_L3)) {
							unlink($my_file_L1);
							unlink($my_file_L2);
							unlink($my_file_L3);
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
stop_clock($starttime,"GET BLOCKS");
