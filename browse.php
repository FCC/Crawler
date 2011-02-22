<html>
<head>
<style>
.label {font-weight:bold;}
</style>
</head>
<body>
<?php
include('config.php');
include('includes/functions.php');
include('includes/mysql_functions.php');

if (isset($_GET['pageID'])) $pageID = $_GET['pageID'];
else $pageID = 1;

$page = get_page($pageID); 
?>

<h2><?php echo $page['title']; ?></h2>
<ul>
	<li><span class='label'>URL: </span>
	<?php echo $page['url']; ?></li>
	<li>
		<span class='label'># Incomming Links: </span>
		<?php echo count_links($pageID,"to") ?>
	</li>
	<li>
		<span class='label'># Outgoing Links: </span>
		<?php echo count_links($pageID,"from")  ?>
	</li>
	<?php unset($page['title']); unset($page['ID']); unset($page['URL']); unset($page['crawled']); ?>
	<?php foreach ($page as $field=>$value) { ?>
		<li><span class='label'><?php echo $field; ?>:</span><?php 
		switch($field) {
			case 'size':
				echo file_size($value);
			break;
			case 'modified':
				if ($value != '') echo date('Y-m-d H:i:s',$value);
			break;
			default:
				echo $value;
			break;
		}
	}
	?>
			
	
</ul>
<hr>
<?php
$links = get_links($pageID);
if (sizeof($links) > 0) { ?>
<ul>
	<?php foreach ($links as $linkID => $link) { ?>
		<li>
			<a href='?pageID=<?php echo $linkID; ?>'><?php echo $link['title']; ?></a> 
			<?php unset($link['title']); unset($link['ID']); unset($link['URL']); unset($link['crawled']); ?>
			<ul>
			<?php foreach ($link as $field=>$value) { ?>
				<li><span class='label'><?php echo $field; ?>:</span><?php 
				switch($field) {
					case 'size':
						echo file_size($value);
					break;
					case 'modified':
						if (strlen($value)>0) echo date('Y-m-d H:i:s',$value);
					break;
					default:
						echo $value;
					break;
				}
				?></li>
			<?php } ?>
			</ul>
		</li>
	<?php } ?>
</ul>
<?php } else { ?>
<p>No Links on this page</p>
<?php } ?>	
