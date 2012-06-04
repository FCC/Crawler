<?php
/* Exports database as CSV
 *
 * NB: If you are crawling a large website (> 65,000 URLs) Excel cannot open the file, however Access can
 *
 * @package Crawler
*/
 
/**
 * Include necessary files
 */
include('config.php');
include('includes/functions.php');
include('includes/mysql_functions.php');

/**
 * Set the content headers
 */
header('Content-type: application/CSV');
header("Content-Disposition: attachment; filename=export.csv");
	
/**
 * SQL query to generate our results
 */
$pages = mysql_query("SELECT url, title, clicks, http_code, crawl_tag, size, type, modified, (SELECT count(*) FROM links WHERE `to` = urls.ID) as incoming, (SELECT count(*) FROM links WHERE `to` = urls.ID) as outgoing from urls");

/**
 * Count the number of pages in our dataset
 */
$count = mysql_num_rows($pages);

/**
 * Get array of fields by parsing keys of first row array
 *
 * NOTE TO SELF: There is a better way to do this
 */
$fields = array_keys(mysql_fetch_assoc($pages));

/**
 * Print the header row and a new line charecter
 */
foreach ($fields as $field) {
	echo "$field\t";
}
echo "\n";

/**
 * When we looped through to grab the field names, we moevd the internal pointer.
 * Reset internal pointer so our loop includes the first row
 */
mysql_data_seek($pages,0);


/**
 * Loop through the rows (pages)
 */
for ($i=0; $i < $count; $i++) {

	/**
	 * Fetch the row as an associative array
	 */
	$page = mysql_fetch_assoc($pages);
	
	/**
	 * Loop through each field within the row
	 */
	foreach ($page as $key=>$field) {
	
		/**
		 * If it the 'size', or 'modified' field, make it human readible, otherwise just output
		 */
		switch($key) {
			case 'size':
				echo file_size($field);
			break;
			case 'modified':
				if (!is_null($field)) echo date('Y-m-d H:i:s',$field);
			break;
			default:
				echo $field;
			break;
		} //End switch
		echo "\t"; 
	} //End Field
	echo "\n";
} //End Row

?>