<?php

/**
 * @file
 * A single location to store configuration for web-root app
 */	
define('CONSUMER_KEY', '8Q1l3enWwBIJu9Q5R4K4JQ');
define('CONSUMER_SECRET', 's6PZHxt3ehwT8SUNSI5ybt5AA5ySf3EQN0skbZnOa28');
define('OAUTH_CALLBACK', 'http://www.theblockbot.com/sign_up/callback.php');

/**
* The block bots authorisation tokens... Remove when distributed!
*/
define('BB_ACCESSTOKEN', '1135412292-gsho3YJAoldfAEuLsVXSNu4f9zufKgtQa2M5tBw');
define('BB_ACCESSTOKENSECRET', 'Yc0XM0qrrqP633evKePAqH1sdFHIEXdErLIxp5at1ac');

/**
 * Misc constants
 */
define('ROOT_DIR','/var/www/apbb/');
define('WEB_ROOT_DIR','/var/www/html/sign_up/');
define('USERS_DIR', ROOT_DIR.'users/');
define('BLOCKS', ROOT_DIR.'blocks/');
define('BLOCKS_ANALYSIS_DIR', ROOT_DIR.'blocks_analysis/');
define('DEAD_BLOCKS', ROOT_DIR.'dead_blocks/');
define('BLOCKS_L1', ROOT_DIR.'blocks/level_1/');
define('BLOCKS_L2', ROOT_DIR.'blocks/level_2/');
define('BLOCKS_L3', ROOT_DIR.'blocks/level_3/');
define('LOGS_DIR', ROOT_DIR.'logs/');
define('REM_BLOCKS_DIR', ROOT_DIR.'removed_blocks/');
define('WEB_ADMIN_DIR', WEB_ROOT_DIR.'admin/');
define('HASHSPAM_DIR', ROOT_DIR.'hashspam/');
define('WEB_ADMIN_LOGS_DIR', WEB_ROOT_DIR.'admin/logs/');
define('BLOCKERS_DIR', ROOT_DIR.'authorised_users/blockers/');
define('ADMINS_DIR', ROOT_DIR.'authorised_users/admins/');
define('SUPER_ADMINS_DIR', ROOT_DIR.'authorised_users/super_admins/');