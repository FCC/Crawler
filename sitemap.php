<?php header ("Content-Type:text/xml");?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php 
include('config.php');
include('includes/functions.php');
include('includes/mysql_functions.php');

function xmlentities($string) {
   return str_replace(
		array ( '&', '"', "'", '<', '>', '?' ), 
		array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;', '&apos;' ), 
		$string
	);
} 

$pages = mysql_array(mysql_query("SELECT url, modified from urls"));
foreach ($pages as $page) { ?>
   <url>
      <loc><?php echo xmlentities($page['url']); ?></loc>
	  <?php if (strlen($page['modified'])>0) { ?><lastmod><?php echo date('Y-m-d',$page['modified']); ?></lastmod><?php } ?>
   </url>
<?php } ?>
</urlset> 
