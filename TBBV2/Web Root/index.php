<?php
/**
 * @file
 * User has successfully authenticated with Twitter. Access tokens saved to session and to filesystem as a user.
 */

/* Check domain is www.theblockbot.com -> if not forward to it */
if ($_SERVER['HTTP_HOST']!="www.theblockbot.com") {
	header( 'Location: http://www.theblockbot.com/sign_up/' ) ;
}

/* Load required lib files. */
session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');

/* If access tokens are not available redirect to connect page. */
if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
    header('Location: ./clearsessions.php');
}

/* Get block level from session: Default of 2 in case of error */
$block_level=$_SESSION['level'];
if ($block_level<1 || $block_level>3){
	$block_level=2;
}

/* Get user access tokens out of the session. */
$access_token = $_SESSION['access_token'];

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

/* If method is set change API call made. Test is called by default. */
$credentials = $connection->get('account/verify_credentials');

$user_id = $credentials->id;
$screen_name = strtolower($credentials->screen_name);

if ($user_id>0){
	// Message to user...
	$content='<hr><h3>Well done <b>'.$screen_name.'</b>! You have successfully authorised The Block Bot to block <b>Level '.$block_level.'</b> blockees</h3>';
	$content=$content.'<button dojoType="dijit.form.Button" type="button">CLICK HERE: To see who you are blocking<script type="dojo/method" event="onClick" args="evt">window.open(\'/sign_up/show_blocks.php\',\'_self\');</script></button><br>';
	$content=$content.'<button dojoType="dijit.form.Button" type="button">CLICK HERE: To change your blocking level<script type="dojo/method" event="onClick" args="evt">window.open(\'/sign_up/clearsessions.php\',\'_self\');</script></button>';
	
	# User file
	$my_user_file = USERS_DIR.$user_id.'¬'.$screen_name;
	# Check user is not in block list! Only care if they are in L2 or L3, L1 ppl can use the list
	$my_file_block_level_1 = BLOCKS_L1.$user_id.'¬'.$screen_name;
	$my_file_block_level_2 = BLOCKS_L2.$user_id.'¬'.$screen_name;
	# L1 blocked users can sign up... L2 and L3 cannot.
	if (file_exists($my_file_block_level_1)) {
			create_user_entry($my_file_block_level_1,$access_token['oauth_token'],$access_token['oauth_token_secret'],$block_level);
	} else if (file_exists($my_file_block_level_2)) {
			create_user_entry($my_file_block_level_2,$access_token['oauth_token'],$access_token['oauth_token_secret'],$block_level);
	} else {
		// Now file is set write it out
		create_user_entry($my_user_file,$access_token['oauth_token'],$access_token['oauth_token_secret'],$block_level);
	}
} else {
	$content='Sorry something went wrong, try <a href="clearsessions.php">clearing your session</a> and trying again<br><br>';
}
/* Visibility of freeze peach and text, if blank shown */
$visibility="";
$title="Atheism+ Block Bot Sign-Up Page";
/* Include HTML to display on the page */
include('html.inc');

function create_user_entry($file,$acc_token,$acc_token_secret,$level) {
	$handle = fopen($file, 'w') or die('Cannot open file:  '.$file);
	$data = $acc_token.','.$acc_token_secret.','.$level;
	fwrite($handle, $data);
	fclose($handle);
}