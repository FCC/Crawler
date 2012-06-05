<?php
/**
 * 
 * Crawler Configuration File
 *
 */
 
 /**
  * MySQL Connection Settings
  */
 $mysql_server = 'localhost';
 $mysql_user = 'root';
 $mysql_pass = 'galgren'; 
 $mysql_db = 'fcc_crawler';

/**
 * Local Timezone
 */
 $timezone = 'America/New_York';

/**
 * Script Timeout (seconds)
 */
 $timeout = 380;
 
/**
 *
 * Domains to crawl separated by commas.  Crawler will verify that a link resides in one of the domains listed before adding it to the queue.
 *
 * Example:  "www.fcc.gov, broadband.com"
 *
 */
$domains = "www.fcc.gov"; 

/**
 * 
 * Patterns separated by a commas that if found should be excluded from crawl and link count.
 *
 * Example: "/fontsize=/i"
 *
 */
$excluded_array = array("/fontsize=/", "/contrast=/","/page=[2-9]+/","/page=1[0-9]+/",
	"/related-rss/","/document/","/print\/node/","/gov\/related\//","/gov\/reports\//",
	"/gov\/Bureaus/","/gov\/events\/[a-zA-Z0-9]+/","/blog\/[0-9]+/","/ecfs\/comment\//","/fcc-bin\/bye/");

/**
 *
 * Tag or set name for a given crawl to allow multuple crawls in same database
 *
 * Example:  "mainsite"
 *
 */
$crawl_tag = "mainsite"; 

 /**
  * Settings to save html of page into database
  */
$store_local = True; // Set to False to not store


/**
 * No Need to Edit below here
 */

 
 /**
 * Check to ensure settings are not defaults
 */

if ($mysql_server == ''|$mysql_user == ''|$mysql_pass==''|$mysql_db=='') die('You must enter MySQL information in config.php before continuing');
 
if ($domains == '') die('You must enter one or more domains in config.php before continuing');
 
/**
 * Initiate database connection
 */
$db=mysql_connect ($mysql_server, $mysql_user, $mysql_pass) or die ('I cannot connect to the database because: ' . mysql_error());

/**
 * Select DB
 */
mysql_select_db ($mysql_db);

/**
 * Set the timezone to properly note timestamps
 */
date_default_timezone_set($timezone);

/**
 * Set script timeout
 */
set_time_limit($timeout);

/* 
================== VERIFY AND CREATE TABLES IF NECESSARY ================== 
*/

$tables = array('urls','links');
$create_mysql=0;
foreach ($tables as $table) {
	if(!mysql_num_rows( mysql_query("SHOW TABLES LIKE '".$table."'"))) $create_mysql = 1;
}

if ($create_mysql) {
	$file = "create-tables.sql";
	$fh = fopen($file, 'r+');
	$contents = fread($fh, filesize($file));
	$cont = preg_split("/;/", $contents);
	foreach($cont as $query) $result = mysql_query($query);
}
?>