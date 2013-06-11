<?php
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

# Get users...$users[0] are the numerical ids and $users[1] are the string ids
$users=get_users_in_dir(USERS_DIR);

for ($i=0; $i<=(count($users[0])-1); $i++)
{
	$userstarttime=start_clock();
	
	$current_user=$users[1][$i];
	$current_user_id=$users[0][$i];
	$auth=get_user_auth($users[0][$i],$users[1][$i]);
	#Get block level from user @author jbilling
	$block_level=chop($auth[2]);
	# User being processed
	log_it("INFO","PROCESSING BLOCKS FOR: ".$current_user_id."¬".$current_user." --> Level ".$block_level);
	$myblockees = array();
	# Always Level 1
	$blockees=get_users_in_dir(BLOCKS_L1);
	$tmp=array_merge($blockees[0],$myblockees);
	$myblockees=$tmp;
	# Store L1 blockees for checking later - checking for SPAM Bots
	$L1_blockees=$blockees[0];
	# Get blockees...$blockees[0] are the numerical ids and $blockees[1] are the string ids
	if ($block_level==2 || $block_level==3) {
		$blockees=get_users_in_dir(BLOCKS_L2);
		$tmp=array_merge($myblockees,$blockees[0]);
		$myblockees=$tmp;
	}
	if ($block_level==3) {
		$blockees=get_users_in_dir(BLOCKS_L3);
		$tmp=array_merge($myblockees,$blockees[0]);
		$myblockees=$tmp;
	}

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
			
			# Take list of blocked users from list to block
			$return = subtract_array($myblockees,$blocked_users);
			# don't care about the ones already blocked
			$myblockees = $return[0];
			
			# Take list of followed users from list to block and get difference
			$return = subtract_array($myblockees,$friends);
			$myblockees = $return[0];
			$friends_not_blocked=$return[1];
			
			# No point carrying on if nothing to block
			if (count($myblockees)>0) {
				#count number of api calls allowed in 15 min period is 15.. 
				$api_calls=15;
			
				# Need to only block the number of users that we have API calls left
				$x_users_to_block=get_x_from_array($myblockees,$api_calls);
				# Now block the users
				for ($x=0; $x<=(count($x_users_to_block)-1); $x++)
				{
					# First check the user is valid, no point blocking if they have gone
					if (is_valid_user_byid($x_users_to_block[$x],$connection)!=""){
						$is_spam=false;
						if (is_item_in_array($L1_blockees,$x_users_to_block[$x])){
							# Could be one to block for spam
							$is_spam=is_user_spam($x_users_to_block[$x]);
						}
						if ($is_spam){
							$connection->post('users/report_spam', array('user_id' => $x_users_to_block[$x]));
							log_it("INFO","SPAM BLOCKED USER : ".$x_users_to_block[$x]." FOR USER ".$current_user);
						} else {
							$connection->post('blocks/create', array('user_id' => $x_users_to_block[$x]));
							log_it("INFO","BLOCKED USER : ".$x_users_to_block[$x]." FOR USER ".$current_user);
						}
					} else {
						$blockee_screen_name=get_screen_name($x_users_to_block[$x],$connection);
						log_it("ERROR","BLOCKEE NOT VALID : ".$x_users_to_block[$x]."¬".$blockee_screen_name." TRIED TO BLOCK FOR USER ".$current_user);
					}
				}
			}
		}
	}
	stop_clock($userstarttime,"BLOCKEM FOR USER ".$current_user);
}
delete_lock();
stop_clock($starttime,"BLOCKEM TOTAL");
