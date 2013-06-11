<?php

/**
 * @file
 * Check if consumer token is set and if so send user to get a request token.
 */
/* Check domain is www.theblockbot.com -> if not forward to it */
if ($_SERVER['HTTP_HOST']!="www.theblockbot.com") {
	header( 'Location: http://www.theblockbot.com/sign_up/' ) ;
}

/**
 * Exit with an error message if the CONSUMER_KEY or CONSUMER_SECRET is not defined.
 */
 /* Start session and load library. */
session_start();
require_once('config.php');

if (CONSUMER_KEY === '' || CONSUMER_SECRET === '') {
  echo 'You need a consumer key and secret to test the sample code. Get one from <a href="https://twitter.com/apps">https://twitter.com/apps</a>';
  exit;
}

/* Build an image link to start the redirect process. */
$content = '
<h3> Choose your level</h3>
<form action="./redirect.php" method="post">
    <input type="radio" data-dojo-type="dijit/form/RadioButton" name="level" id="radioOne" checked value="1"/> 
    	<label for="radioOne">Level 1: Super Slimy; abusive spammers, d0x\'ers, imposters and stalkers (Just these, surely you want to!)</label> <br/>
    <input type="radio" data-dojo-type="dijit/form/RadioButton" name="level" id="radioTwo" value="2"/> 
    	<label for="radioTwo">Level 2: Pesky Pittizens; assholes, anti-feminists and attention seekers (Plus you will block Level 1)</label> <br/>
    <input type="radio" data-dojo-type="dijit/form/RadioButton" name="level" id="radioThree" value="3"/> 
    	<label for="radioThree">Level 3: Mildly Mildewy; mostly just annoying. (Plus you will block Levels 1 and 2)</label> <br/><br/>

	<b>THEN CLICK HERE >>>>&nbsp;&nbsp;</b><input type="image" src="./images/lighter.png"  name="submit" value="submit" />
</form>';
/* Visibility of freeze peach and text */
$visibility="";
$title="Atheism+ Block Bot Sign-Up Page";
/* Include HTML to display on the page. */
include('html.inc');
