<?php
/**
 * @file
 * User has successfully authenticated with Twitter. Access tokens saved to session and to filesystem as a user.
 * This shows the users blocks and the block list blocks -- what do they have already and what do they have that
 * the block list doesn't?
 */
/* Check domain is www.theblockbot.com -> if not forward to it */
if ($_SERVER['HTTP_HOST']!="www.theblockbot.com") {
	header( 'Location: http://www.theblockbot.com/sign_up/' ) ;
}

/* Load required lib files. */
session_start();
require_once('twitteroauth/twitteroauth.php');
require_once('config.php');
require_once('../../apbb/util.php');

/* If access tokens are not available redirect to connect page. */
if (empty($_SESSION['access_token']) || empty($_SESSION['access_token']['oauth_token']) || empty($_SESSION['access_token']['oauth_token_secret'])) {
    header('Location: ./clearsessions.php');
}
/* Get user access tokens out of the session. */
$access_token = $_SESSION['access_token'];

/* Get block level from session: Default of 2 in case of error */
$block_level=$_SESSION['level'];
if ($block_level<1 || $block_level>3){
	$block_level=2;
}

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);

/* If method is set change API call made. Test is called by default. */
$credentials = $connection->get('account/verify_credentials');
$user_id = $credentials->id;
$screen_name = $credentials->screen_name;

if (empty($_SESSION['blocks_ids'])) {
	# Get list of blocked users (blocks/ids)
	$cursor=-1;
	$blocked_users = array();
	$data = $connection->get("blocks/list");
	$my_users = $data->users;
	$cursor=$data->next_cursor_str;
	# While data in cursor... LOOP! Danger if person has large number of blocks *cough*aratina*cough*
	while ($cursor>0){
		$data = $connection->get("blocks/list", array("cursor" => "$cursor"));
		$my_users_next = $data->users;
		$cursor=$data->next_cursor_str;
		$my_users=array_merge($my_users,$my_users_next);
	}
	foreach ($my_users as $user) {
		array_push($blocked_users, strtolower($user->screen_name));
	}
	sort($blocked_users);
	$_SESSION['blocks_ids'] = $blocked_users;
} else {
	#Get from session
	$blocked_users =$_SESSION['blocks_ids'];
}

#Set up table...
#Get blockees by level...
$result=get_blocks_by_level(1);
$blockees_level_1=$result[1];
$result=get_blocks_by_level(2);
$blockees_level_2=$result[1];
$result=get_blocks_by_level(3);
$blockees_level_3=$result[1];
# Now compile list of all blockees
$blockees=$blockees_level_1;
if ($block_level>=2){
	$tmp=array_merge($blockees,$blockees_level_2);
	$blockees=$tmp;
}
if ($block_level==3){
	$tmp=array_merge($blockees,$blockees_level_3);
	$blockees=$tmp;
}
# Sort all the arrays for display
sort($blockees_level_1);
sort($blockees_level_2);
sort($blockees_level_3);
$user_num=1;
$result=subtract_array($blockees,$blocked_users);
$not_in_blocklist=$result[0];
$result=subtract_array($blocked_users,$blockees);
$not_in_users_blocklist=$result[0];
sort($not_in_users_blocklist);
sort($not_in_blocklist);
$content='
<button dojoType="dijit.form.Button" type="button">CLICK HERE: To change your blocking level<script type="dojo/method" event="onClick" args="evt">window.open(\'/sign_up/clearsessions.php\',\'_self\');</script></button>
<div style="height: 600px;">
    <div dojoType="dijit.layout.TabContainer" style="width: 100%;" doLayout="false">
        <div dojoType="dijit.layout.ContentPane" title="All your current blocks" data-dojo-props="selected:true">
        All the Twitter users currently in your block list. Let us know if we should add any to our list!<br><br>
<div id="userbox">';
foreach ($blocked_users as $user)
{
	$content=$content."<button dojoType=\"dijit.form.Button\" type=\"button\">";
	$content=$content.$user_num.". ".$user;
	$content=$content."<script type=\"dojo/method\" event=\"onClick\" args=\"evt\">";
	$content=$content."window.open('https://twitter.com/".$user."', '_blank');";
	$content=$content."</script></button>";
	$user_num++;
}
$more='
</div></div>
<div dojoType="dijit.layout.ContentPane" title="Users on level '.$block_level.' block list">
If you have signed up this should match your block list minus those you are following. (Note may take time to catch up as the bot blocks ~60/hour)<br><br>
<hr>LEVEL 1 BLOCKS<div id="level_1_blocks">';
$content=$content.$more;
$user_num=1;
foreach ($blockees_level_1 as $user)
{
	$content=$content."<button dojoType=\"dijit.form.Button\" type=\"button\">";
	$content=$content.$user_num.". ".$user;
	$content=$content."<script type=\"dojo/method\" event=\"onClick\" args=\"evt\">";
	$content=$content."window.open('https://twitter.com/".$user."', '_blank');";
	$content=$content."</script></button>";
	$user_num++;
}
$content=$content."</div>";
if ($block_level>=2){
	$user_num=1;
	$content=$content."<hr>LEVEL 2 BLOCKS<div id=\"level_2_blocks\">";
	foreach ($blockees_level_2 as $user)
	{
		$content=$content."<button dojoType=\"dijit.form.Button\" type=\"button\">";
		$content=$content.$user_num.". ".$user;
		$content=$content."<script type=\"dojo/method\" event=\"onClick\" args=\"evt\">";
		$content=$content."window.open('https://twitter.com/".$user."', '_blank');";
		$content=$content."</script></button>";
		$user_num++;
	}	
	$content=$content."</div>";
}
if ($block_level==3){
	$user_num=1;
	$content=$content."<hr>LEVEL 3 BLOCKS<div id=\"level_3_blocks\">";
	foreach ($blockees_level_3 as $user)
	{
	$content=$content."<button dojoType=\"dijit.form.Button\" type=\"button\">";
	$content=$content.$user_num.". ".$user;
	$content=$content."<script type=\"dojo/method\" event=\"onClick\" args=\"evt\">";
	$content=$content."window.open('https://twitter.com/".$user."', '_blank');";
	$content=$content."</script></button>";
		$user_num++;
}
	$content=$content."</div>";
}
$more ='
</div>
<div dojoType="dijit.layout.ContentPane" title="Users on level '.$block_level.' block list you are not yet blocking">
These will NOT be blocked as long as you are following them. Otherwise the bot will block in due course...(Note may take time to catch up as the bot blocks ~60/hour)<br><br>
<div id="notyetbox">';
$content=$content.$more;
$user_num=1;
foreach ($not_in_blocklist as $user)
{
	$content=$content."<button dojoType=\"dijit.form.Button\" type=\"button\">";
	$content=$content.$user_num.". ".$user;
	$content=$content."<script type=\"dojo/method\" event=\"onClick\" args=\"evt\">";
	$content=$content."window.open('https://twitter.com/".$user."', '_blank');";
	$content=$content."</script></button>";
	$user_num++;
}
$more ='
</div></div>
<div dojoType="dijit.layout.ContentPane" title="Users you are blocking but we are not.">
Yet! Let us know if they deserve addition. Any here you think should be added then tweet them to @the_block_bot<br><br>
<div id="couldbebox">';
$content=$content.$more;
$user_num=1;
foreach ($not_in_users_blocklist as $user)
{
	$content=$content."<button dojoType=\"dijit.form.Button\" type=\"button\">";
	$content=$content.$user_num.". ".$user;
	$content=$content."<script type=\"dojo/method\" event=\"onClick\" args=\"evt\">";
	$content=$content."window.open('https://twitter.com/".$user."', '_blank');";
	$content=$content."</script></button>";
	$user_num++;
}
$content=$content."</div></div></div>";
/* Visibility of freeze peach and text */
$visibility="style=\"display:none\"";
$title="Atheism+ Block Bot Show Blocks Page";
include('html.inc');