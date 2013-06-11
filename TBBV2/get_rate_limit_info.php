<?php
/**
* @file
* Utility - shows rate limit for block bot user v1 and v1.1 limits
*/
require_once('../html/sign_up/twitteroauth/twitteroauth.php');
require_once('../html/sign_up/config.php');
require_once('./util.php');
#Start timer
$starttime=start_clock();

/* Create a TwitterOauth object with consumer/user tokens. */
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, BB_ACCESSTOKEN, BB_ACCESSTOKENSECRET);

$content = $connection->get("application/rate_limit_status");
print_r($content);
echo "\n";


$url="http://api.twitter.com/1/account/rate_limit_status.xml";
$http = curl_init($url);
curl_setopt($http, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($http);

# parse return
$xml = simplexml_load_string($result);
$json = json_encode($xml);
print_r($json);
echo "\n";

stop_clock($starttime,"GET RATE LIMITS");
