<?php

include('config.php');
include('includes/functions.php');
include('includes/mysql_functions.php');

$sql = "SELECT url, title, clicks, http_code, size, type, modified, (SELECT count(*) FROM links WHERE `to` = urls.ID) as incoming, (SELECT count(*) FROM links WHERE `to` = urls.ID) as outgoing from urls";


if ($_GET) {
	$sql .= " WHERE";
	foreach ($_GET as $field=>$value) $sql .= " `$field` = '". urldecode($value) . "'";
}

$sql .= " LIMIT 100";

$pages = mysql_query($sql);


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

?><table border='1'><?php
/**
 * Print the header row and a new line charecter
 */
 echo "\t<tr>\r\n";
foreach ($fields as $field) {
	echo "\t\t<th>$field</th>\r\n";
}
echo "\t</tr>\r\n";

/**
 * When we looped through to grab the field names, we moevd the internal pointer.
 * Reset internal pointer so our loop includes the first row
 */
mysql_data_seek($pages,0);


/**
 * Loop through the rows (pages)
 */
for ($i=0; $i < $count; $i++) {
	echo "\t<tr>\r\n";
	/**
	 * Fetch the row as an associative array
	 */
	$page = mysql_fetch_assoc($pages);
	
	/**
	 * Loop through each field within the row
	 */
	foreach ($page as $key=>$field) {
		echo "\t\t<td>";
		/**
		 * If it the 'size', or 'modified' field, make it human readible, otherwise just output
		 */
		switch($key) {
			case 'size':
				echo file_size($field);
			break;
			case 'modified':
				if ($field != '') echo date('Y-m-d H:i:s',$field);
			break;
			default:
				echo $field;
			break;
		} //End switch
		echo "</td>\r\n"; 
	} //End Field
	echo "\t</tr>\r\n";
} //End Row
?>
</table>