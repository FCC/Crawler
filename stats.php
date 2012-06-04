<?php
/**
 * File to output the Crawler's current progress to the browser
 *
 * @package Crawler
 */
?>
<html>
<head>
<style>
body {margin:0; padding: 0;}
.form-row {clear:both; width: 100%; padding: 10 0 10 0px; float:left;}
.form-label {float:left; width: 60%; text-align: right; padding-right: 10%; font-weight: bold;}
.form-field {float:left; width: 30%; }
#percent { font-size:50pt; font-weight:bold; text-align:center;}
.column {width:33.3%; float:left;}
h2 {text-align: center; border-bottom: 1px solid #ccc;}
h1 {text-align: center;}
table {text-align:right; margin-left:auto; margin-right: auto;}
.num-clicks, .codes, .filetypes {font-weight:bold; text-align:center;}
th {border-bottom: 1px solid #ccc; font-size:10pt; padding-left: 5px; padding-right: 5px; text-align:center;}
.clear {clear:both; border-bottom: 1px solid #ccc; width:100%; padding-top:20px;}
</style>
<body>
<h1>Crawler Statistics</h1>
<?php
/**
 * Grab necessary files
 */
include('config.php');
include('includes/functions.php');
include('includes/mysql_functions.php');

/**
 * Calculate number of crawled and Uncralwed pages for configured crawl_tag
 *
 */
$data = mysql_query('SELECT crawled, COUNT(crawled) AS NumOccurrences FROM urls WHERE `crawl_tag`="'.$crawl_tag.'" GROUP BY crawled ');

/**
 * Check for case of all files crawled (no row with uncrawled group count)
 */
$num_groups = mysql_num_rows($data);

if ($num_groups < 1) {
	$uncrawled = 0;
	$crawled = 0;
	$total = $crawled+$uncrawled;
	$percent = 0;
} elseif ($num_groups < 2) {
	$uncrawled = 0;
	$crawled = mysql_result($data,0,1);
	$total = $crawled+$uncrawled;
	$percent = 100 * $crawled / $total;
} else {
	$uncrawled = mysql_result($data,0,1);
	$crawled = mysql_result($data,1,1);
	$total = $crawled+$uncrawled;
	$percent = 100 * $crawled / $total;
}

?>
<div class='clear'> </div>
<div class='column'>

	<h2>Pages Crawled</h2>

	<?php

	while ($row = mysql_fetch_array($data, MYSQL_ASSOC)) {\
		printf("Value: %s  Count: %s\r\n", $row["crawled"], $row["NumOccurrences"]);
		$num_crawled[$row["crawled"]] = $row["NumOccurrences"];
	}
	?>
	<div class='form-row'>
		<div class='form-label'>
			Crawled:
		</div>
		<div class='form-field'>
			<a href='query.php?crawled=1'><?php echo number_format($crawled); ?></a>
		</div>
	</div>
	<div class='form-row'>
		<div class='form-label'>
			Indexed, but not yet crawled:
		</div>
		<div class='form-field'>
			<a href='query.php?crawled=0'><?php echo number_format($uncrawled); ?></a>
		</div>
	</div>
	<div class='form-row'>
		<div class='form-label'>
			Total:
		</div>
		<div class='form-field'>
			<a href='query.php'><?php echo number_format($total); ?></a>
		</div>
	</div>
</div>
<div class='column'>

	<h2>Estimated Percent Complete</h2>
	<div id='percent'>
		<?php echo number_format($percent,2); ?>%
	</div>
</div>

<div class='column'>

<h2>Pages Indexed</h2>
<table>
	<tr>
		<th># of Clicks</th>
		<th>Count</th>
		<th>Cumulative Count</th>
	</tr>
<?php
$sql = 'SELECT clicks, COUNT(clicks) AS NumOccurrences FROM urls WHERE `crawl_tag`="' . $crawl_tag . '" GROUP BY clicks HAVING ( COUNT(clicks) > 0 )';
$clicks = mysql_array(mysql_query($sql));
$cumulative = 0;
foreach ($clicks as $click) { $cumulative += $click['NumOccurrences']; ?>
	<tr>
		<td class='num-clicks'><a href='query.php?clicks=<?php echo $click['clicks']; ?>'><?php echo $click['clicks']; ?></a></td>
		<td><?php echo number_format($click['NumOccurrences']); ?></td>
		<td><?php echo number_format($cumulative); ?></td>		
	</tr>
<?php } ?>
</table>
</div>
<div class='clear'> </div>

<div class='column'>
<h2>Response Codes</h2>
</html>
<?php
$sql = 'SELECT http_code, COUNT(http_code) AS NumOccurrences FROM urls WHERE `crawl_tag`="'.$crawl_tag.'" GROUP BY http_code HAVING ( COUNT(http_code) > 0 )';
$codes = mysql_array(mysql_query($sql));
?>
<table>
	<tr>
		<th>Response Code</th>
		<th>Count</th>
	</tr>
	<?php foreach ($codes as $code) { ?>
	<tr>
		<td class='codes'>
			<a href='query.php?http_code=<?php echo $code['http_code']; ?>'><?php echo $code['http_code']; ?></a>
		</td>
		<td>
			<?php echo number_format($code['NumOccurrences']); ?>
		</td>
	</tr>
	<?php } ?>
</table>
</div>


<div class='column'>
<h2>File Types</h2>
</html>
<?php
$sql = 'SELECT type, COUNT(type) AS NumOccurrences FROM urls WHERE `crawl_tag`="'.$crawl_tag.'" GROUP BY type HAVING ( COUNT(type) > 0 ) ORDER BY NumOccurrences DESC';
$types = mysql_array(mysql_query($sql));
?>
<table>
	<tr>
		<th>File Type</th>
		<th>Count</th>
	</tr>
	<?php foreach ($types as $type) { ?>
	<tr>
		<td class='fieltype'>
			<a href='query.php?http_code=<?php echo urlencode($type['type']); ?>'><?php echo $type['type']; ?></a>
		</td>
		<td>
			<?php echo number_format($type['NumOccurrences']); ?>
		</td>
	</tr>
	<?php } ?>
</table>
</div>

<div class='column'>
<h2>File Sizes</h2>
</html>
<?php
$sql = 'SELECT MAX(size) as max, AVG(size) as avg FROM urls WHERE `crawl_tag`="'.$crawl_tag.'"';
$sizes = mysql_row_array(mysql_query($sql));


?>
<div class='form-row'>
	<div class='form-label'>
		Largest File:
	</div>
	<div class='form-field'>
	<?php echo file_size($sizes['max']); ?>
	</div>
</div>
<div class='form-row'>
	<div class='form-label'>
		Average File Size:
	</div>
	<div class='form-field'>
		<?php echo file_size($sizes['avg']); ?>
	</div>
</div>
</div>

<div class='clear'> </div>
<div style='text-align:center;margin-bottom:20px;'>

	<div><b>Current Domains: </b><?php echo $domains; ?></div> 
	<div><b>Current Crawl Tag: </b><?php echo $crawl_tag; ?></div> 
	<?php 
	$sql = 'SELECT title, url FROM urls WHERE crawled = "1" AND crawl_tag = "'.$crawl_tag.'" ORDER BY ID DESC LIMIT 1';
	$last = mysql_row_array(mysql_query($sql));
	?>
	<div><b>Last Page Crawled: </b><?php echo $last['title']; ?> (<?php echo "<a href='{$last['url']}' target='_new'>{$last['url']}</a>";?>) </div> 
</div>

</body>
