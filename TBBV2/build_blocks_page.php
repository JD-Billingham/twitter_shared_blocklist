<?php
/**
 * @file
 * Read the blocks directory and create a HTML page with all the blocked people in it
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

/* Load required lib files. */
require_once('/var/www/html/sign_up/config.php');
require_once('/var/www/apbb/util.php');
#Start timer
$starttime=start_clock();

#Set up table...
#Get blockees...
#Get blockees by level...
$result=get_blocks_by_level(1);
$blockees_level_1=$result[1];
$result=get_blocks_by_level(2);
$blockees_level_2=$result[1];
$result=get_blocks_by_level(3);
$blockees_level_3=$result[1];
$result=get_users_in_dir(DEAD_BLOCKS."level_1/");
$blockees_dead_level_1=$result[1];
$result=get_users_in_dir(DEAD_BLOCKS."level_2/");
$blockees_dead_level_2=$result[1];
$result=get_users_in_dir(DEAD_BLOCKS."level_3/");
$blockees_dead_level_3=$result[1];
# Get Blockers
$result=get_users_in_dir(BLOCKERS_DIR);
$blockers=$result[1];
# Get Admins
$result=get_users_in_dir(ADMINS_DIR);
$admins=$result[1];
# Get Super Admins
$result=get_users_in_dir(SUPER_ADMINS_DIR);
$super_admins=$result[1];
# Get Users
$result=get_users_in_dir(USERS_DIR);
$users=$result[1];

# Sort alphabetically
sort($blockees_level_1);
sort($blockees_level_2);
sort($blockees_level_3);
sort($blockees_dead_level_1);
sort($blockees_dead_level_2);
sort($blockees_dead_level_3);
sort($blockers);
sort($admins);
sort($super_admins);
sort($users);

$content="<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\">";
$content=$content."<html><head><style type=\"text/css\">body, html { font-family:helvetica,arial,sans-serif; font-size:90%; }</style>";
$content=$content."<script src=\"http://ajax.googleapis.com/ajax/libs/dojo/1.6/dojo/dojo.xd.js\" djConfig=\"parseOnLoad: true\"></script>";
$content=$content."<script type=\"text/javascript\">dojo.require(\"dijit.form.Button\");</script><link rel=\"stylesheet\" type=\"text/css\" href=\"http://ajax.googleapis.com/ajax/libs/dojo/1.6/dijit/themes/claro/claro.css\"/></head>";
$content=$content."<body class=\" claro \">";
$content=$content."<h1>Blocks By Level</h1><hr><h3>Level 1</h3>";
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
$content=$content."<hr><h3>Level 2</h3>";
$user_num=1;
foreach ($blockees_level_2 as $user)
{
	$content=$content."<button dojoType=\"dijit.form.Button\" type=\"button\">";
	$content=$content.$user_num.". ".$user;
	$content=$content."<script type=\"dojo/method\" event=\"onClick\" args=\"evt\">";
	$content=$content."window.open('https://twitter.com/".$user."', '_blank');";
	$content=$content."</script></button>";
	$user_num++;
}
$content=$content."<hr><h3>Level 3</h3>";
$user_num=1;
foreach ($blockees_level_3 as $user)
{
	$content=$content."<button dojoType=\"dijit.form.Button\" type=\"button\">";
	$content=$content.$user_num.". ".$user;
	$content=$content."<script type=\"dojo/method\" event=\"onClick\" args=\"evt\">";
	$content=$content."window.open('https://twitter.com/".$user."', '_blank');";
	$content=$content."</script></button>";
	$user_num++;
}
$content=$content."<h1>Dead Blocks</h1><hr><h3>Level 1</h3>";
$user_num=1;
foreach ($blockees_dead_level_1 as $user)
{
	$content=$content."<button dojoType=\"dijit.form.Button\" type=\"button\">";
	$content=$content.$user_num.". ".$user;
	$content=$content."<script type=\"dojo/method\" event=\"onClick\" args=\"evt\">";
	$content=$content."window.open('https://twitter.com/".$user."', '_blank');";
	$content=$content."</script></button>";
	$user_num++;
}
$content=$content."<hr><h3>Level 2</h3>";
$user_num=1;
foreach ($blockees_dead_level_2 as $user)
{
	$content=$content."<button dojoType=\"dijit.form.Button\" type=\"button\">";
	$content=$content.$user_num.". ".$user;
	$content=$content."<script type=\"dojo/method\" event=\"onClick\" args=\"evt\">";
	$content=$content."window.open('https://twitter.com/".$user."', '_blank');";
	$content=$content."</script></button>";
	$user_num++;
}
$content=$content."<hr><h3>Level 3</h3>";
$user_num=1;
foreach ($blockees_dead_level_3 as $user)
{
	$content=$content."<button dojoType=\"dijit.form.Button\" type=\"button\">";
	$content=$content.$user_num.". ".$user;
	$content=$content."<script type=\"dojo/method\" event=\"onClick\" args=\"evt\">";
	$content=$content."window.open('https://twitter.com/".$user."', '_blank');";
	$content=$content."</script></button>";
	$user_num++;
}
$content=$content."<h1>Blockers/Admins and Super Admins</h1><hr><h3>Blockers</h3>";
$user_num=1;
foreach ($blockers as $user)
{
	$content=$content."<button dojoType=\"dijit.form.Button\" type=\"button\">";
	$content=$content.$user_num.". ".$user;
	$content=$content."<script type=\"dojo/method\" event=\"onClick\" args=\"evt\">";
	$content=$content."window.open('https://twitter.com/".$user."', '_blank');";
	$content=$content."</script></button>";
	$user_num++;
}
$content=$content."<hr><h3>Admins</h3>";
$user_num=1;
foreach ($admins as $user)
{
	$content=$content."<button dojoType=\"dijit.form.Button\" type=\"button\">";
	$content=$content.$user_num.". ".$user;
	$content=$content."<script type=\"dojo/method\" event=\"onClick\" args=\"evt\">";
	$content=$content."window.open('https://twitter.com/".$user."', '_blank');";
	$content=$content."</script></button>";
	$user_num++;
}
$content=$content."<hr><h3>Super-Admins</h3>";
$user_num=1;
foreach ($super_admins as $user)
{
	$content=$content."<button dojoType=\"dijit.form.Button\" type=\"button\">";
	$content=$content.$user_num.". ".$user;
	$content=$content."<script type=\"dojo/method\" event=\"onClick\" args=\"evt\">";
	$content=$content."window.open('https://twitter.com/".$user."', '_blank');";
	$content=$content."</script></button>";
	$user_num++;
}
$content=$content."<h1>Users</h1><hr>";
$user_num=1;
foreach ($users as $user)
{
	$content=$content."<button dojoType=\"dijit.form.Button\" type=\"button\">";
	$content=$content.$user_num.". ".$user;
	$content=$content."<script type=\"dojo/method\" event=\"onClick\" args=\"evt\">";
	$content=$content."window.open('https://twitter.com/".$user."', '_blank');";
	$content=$content."</script></button>";
	$user_num++;
}

$content=$content."<br><br><br><br><br><br></body></html>";

$handle = fopen(WEB_ADMIN_DIR."blocks_admins_users.html", 'w') or die('Cannot open file: '.WEB_ADMIN_DIR.'blocks_admins_users.html');
fwrite($handle, $content);
fclose($handle);
stop_clock($starttime,"BUILD BLOCKS PAGE");