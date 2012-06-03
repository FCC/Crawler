<?php
/**
 * 
 * Main crawler function.  Can be run manually or via cron.
 *
 * Append "?debug=true" to URL for verbose output, but performance will degrade over time (as browser buffer fills)
 *
 */


/**
 * Call necesary include files
 */
include('config.php');
include('includes/functions.php');
include('includes/mysql_functions.php');

/**
 * Parse domain list into an array
 */
$domain_array = explode(',',$domains);
?>
<html>
	<body>
	<?php
	echo "<p>STARTED: <b>" . date('Y-m-d H:i:s') . "</b></p>";
	echo "<p>Domains: <b>$domains</b></p>";
	echo "<p>crawl_tag: <b>$crawl_tag</b></p>";
	echo "<p>database: <b>$mysql_db</b></p>";
	echo "<p><b>Crawling...</b></p>";
	
	/*
	 * Grab list of uncrawled URLs, repeat while there are still URLs to crawl
	 */
	while ($urls = uncrawled_urls()) {

		/**
		 * Loop through the array of uncrawled URLs
		 */
		foreach ($urls as $id=>$url_data) {

			/**
			 * If we're in debug mode, indicate that we are begining to crawl a new URL
			 */
			if (isset($_GET['debug']))
				echo "<p style='font-weight:bold'>Starting to crawl " . urldecode($url_data['url']) . "</p><ul>";
			
			/** 
			 * If this is a seed URL, set clicks to zero, 
			 * otherwise, increment our internal click counter one beyond the parent's clicks
			 */
			if (!isset($url_data['clicks'])) $clicks = 0;
			else $clicks = $url_data['clicks'] + 1;
			
			/**
			 * Curl the page, returning data as an array
			 */
			$page_data = curl_page($url_data['url']);
			
			/**
			 * Calculate the directory of the current page, used to parse relative URLs
			 */
			$dir = parse_dir($url_data['url']);
			
			/**
			 * Parse the title of the current page
			 */
			$title = parse_title($page_data['html']);
			
			/**
			 * Parse the HTML for links, store in an array
			 */
			$links = parse_links($page_data['html']);

			/**
			 * Loop through the array of links
			 */
			foreach ($links as $key => &$link) {
				/**
				 * Uniformly clean the link so we don't have duplicates (absolute, no anchors, add www., etc.)
				 */
				$link = clean_link($link, $dir);
				
				/**
				 * If the link is to an image, do not add it
				 */
				if (is_image($link)) continue;
				
				/**
				 * Verify that the link target is within our array of domains
				 */
				if (out_of_domain($link)) continue;
				
				/**
				 * Verify that the link is not a mailto: link
				 */ 
				if (is_mailto($link)) continue;
				
				/**
				 * Check to see if the URL is already in the table, if so, grab its ID number
				 */
				$to = have_url($link);
				
				/**
				 * If the link is not in the table, add it
				 */
				if (!$to) {
					/**
					 * Output that we're adding a URL if we're in verbose mode
					 */
					if (isset($_GET['debug']))
						echo "<li>Adding url " . urldecode($link) . " to list</li>";
						
					/**
					 * Add URL to table, grab link ID #
					 */
					$to = add_url($link,$clicks,$crawl_tag);
				}
				
				/**
				 * If debug mode, indicate that we're adding a link
				 */
				if (isset($_GET['debug']))
					echo "<li>Adding link from here to " . urldecode($link) . "</li>";
					
				/**
				 * Add the link to the links table
				 */
				add_link($id,$to);
			}
			
			/**
			 * If the server did not report a size (in which case cURL returns '-1'), 
			 * use the size of the cURL as the file size, otherwise, trust the server
			 */
			if ($page_data['reported_size'] != -1) $size = $page_data['reported_size'];
			else $size = $page_data['actual_size'];
		
			/**
			 * If the server returned a modifed header, trust it, otherwise (return of '-1' from cURL) NULL the string.
			 */
			if ($page_data['modified'] != -1) $modified = $page_data['modified'];
			else $modified = NULL;
			
			/**
			 * Format the Data array
			 */ 
			$data = array(	'crawled'=>1,
							'title'=>$title,
							'http_code' => $page_data['http_code'],
							'size' => $size,
							'type' => $page_data['type'],
							'modified' => $modified, 
							'md5' => $page_data['md5'],
							'crawl_tag' => $crawl_tag,
							'html' => NULL
							);

			/** 
			 * If config is set to store local version of file, store it
			 */
			if($store_local) {

				// Split text/html; charset=UTF-8
				$type_info = explode("; ", $page_data['type']);

				// Only store 'text/html' files
				// TO DO enable range of file types to save 
				if($type_info[0] == 'text/html' ) {
					$data['html'] = $page_data['html'];
				}
			}


			/**
			 *  Store data
			 */
			mysql_update('urls',$data,array('ID'=>$id));

			
			/**
			 * If in debug mode, close the <ul> we opened above
			 */
			if (isset($_GET['debug']))
				echo "</ul>";
				
		} //End foreach URL
		
	} //End While uncrawled URLs
	
	/**
	 * If we're done, let the user know the good news
	 */
	if (sizeof($urls) == 0)	echo "<p>No URLs to crawl!</p>";
	echo "<p>FINISHED: " . date('Y-m-d H:i:s') . "</p>"; 
	?>
	</body>
<html>