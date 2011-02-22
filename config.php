<?php
/**
 * 
 * Crawler Configuration File
 *
 */
 
 /**
  * MySQL Connection Settings
  */
 $mysql_server = '';
 $mysql_user = '';
 $mysql_pass = ''; 
 $mysql_db = '';

/**
 * Local Timezone
 */
 $timezone = 'America/New_York';
 
/**
 *
 * Domains to crawl separated by commas.  Crawler will verify that a link resides in one of the domains listed before adding it to the queue.
 *
 * Example:  "www.fcc.gov, broadband.com"
 *
 */
$domains = ""; 

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