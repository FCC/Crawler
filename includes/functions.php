<?php
/**
 *
 * Main crawler functions
 *
 * @package crawler
 *
 */

 /**
  * Curl and Parsing Functions
  */
 
 /**
  * cURL Function which returns HTML and page info as array
  *
  * @params string $url URL to cURL
  * @return array Associative array of results
  */
function curl_page($url) {
	$ch = curl_init($url);
	$options = array(
			CURLOPT_HEADER => false,
			CURLOPT_COOKIEJAR => 'cookie.txt',
			CURLOPT_COOKIEFILE	=> 'cookie.txt',
			CURLOPT_USERAGENT => 'Mozilla/5.0 (FCC New Media Web Crawler)',
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FILETIME => true,
			CURLOPT_TIMEOUT => 15
			);
	curl_setopt_array($ch, $options);
	$output['html'] = curl_exec($ch);
	$output['md5'] = md5($output['html']);
	$output['http_code'] = curl_getinfo($ch,CURLINFO_HTTP_CODE);
	$output['reported_size'] = curl_getinfo($ch,CURLINFO_CONTENT_LENGTH_DOWNLOAD);
	$output['actual_size'] = curl_getinfo($ch,CURLINFO_SIZE_DOWNLOAD);
	$output['type'] = curl_getinfo($ch,CURLINFO_CONTENT_TYPE);
	$output['modified'] = curl_getinfo($ch,CURLINFO_FILETIME);	
	curl_close($ch);
	return $output;
}

/**
 * Function to parse page for title tags
 *
 * @params string $data HTML of page
 * @return string|bool title of page of null if not found
 */
function parse_title($data) {
	if (preg_match('#<title>(.*)</title>#is',$data,$title)) return trim($title[1]);
	else return null;
}

/**
 * Function to parse page for links
 *
 * @params string $data HTML of target page
 * @return array Numeric array of links on page (URLs only)
 */
function parse_links($data) {
	$regexp = "<a\s[^>]*href=([\"|']??)([^\"' >]*?)\\1[^>]*>(.*)<\/a>";
  	if(preg_match_all("/$regexp/siU", $data, $matches)) return $matches[2];
  	else return array();
}

/**
 * Given a URL calculates the page's directory
 *
 * @params string $url target URL
 * @return string Directory
 */
function parse_dir($url) {
	$slash = strrpos($url,'/');
	return substr($url,0,$slash+1);
}

/**
 * Link Checking Functions
 */

/**
 * Uniformly cleans a link to avoid duplicates
 *
 * 1. Changes relative links to absolute (/bar to http://www.foo.com/bar)
 * 2. Removes anchor tags (foo.html#bar to foo.html)
 * 3. Adds trailing slash if directory (foo.com/bar to foo.com/bar/)
 * 4. Adds www if there is not a subdomain (foo.com to www.foo.com but not bar.foo.com)
 *
 * @params string $link link to clean
 * @parmas string $dir directory of parent (linking) page
 * @return strin cleaned link
 */
function clean_link($link, $dir) {
	$link = url_to_absolute($dir, $link); //make them absolute, not relative
	if (stripos($link,'#') != FALSE) $link = substr($link,0,stripos($link,'#')); //remove anchors
	if (!preg_match('#(^http://(.*)/$)|http://(.*)/(.*)\.([A-Za-z0-9]+)|http://(.*)/([^\?\#]*)(\?|\#)([^/]*)#i',$link))  $link .= '/';
	$link = preg_replace('#http://([^.]+).([a-zA-z]{3})/#i','http://www.$1.$2/',$link);
	return $link;
}


/**
 * Performs a regular expressoin to see if a given link is an image
 *
 * @params string $link target link
 * @return bool true on image, false on anything else
 */
function is_image($link) {
	if (preg_match('%\.(gif|jpe?g|png|bmp)$%i',$link)) return true;
	else return false;
}

/**
 * Checks to see that a given link is within the domain whitelist
 *
 * Note to self: this can be rewritten using a single regex command
 *
 * @params string $link target link
 * @return bool true if out of domain, false if on domain whitelist
 */
function out_of_domain($link) {
	global $domain_array;
	foreach ($domain_array as $domain) {
		if (stripos($link,trim($domain)) != FALSE) return false;
	}
	return true;
}

/**
 * Checks to see if a given link is in fact a mailto: link
 *
 * @params string $link Link to check
 * @return bool true on mailto:, false on everything else
 */
function is_mailto($link) {
	if (stripos($link,'mailto:')===FALSE) return false;
	else return true;
}

/*
 * Data storage and retrieval functions
 */

/**
 * Adds a URL to the URLs table upon discovery in a link
 *
 * @params string $link URL to add
 * @params int $clicks number of clicks from initial page
 * @return bool true on sucess, false on fail
 */
function add_url($link,$clicks) {
	return mysql_insert('urls',array('url'=>urldecode($link),'clicks'=>$clicks));
}

/**
 * Adds a link to the links table
 *
 * @params int $form ID of linking page
 * @params int $to ID of target page
 * @return int|bool LinkID on sucess, false on fail
 */
function add_link($from,$to) {
	if ($from == $to) return false;
	if (mysql_exists('links',array('from'=>$from,'to'=>$to))) return false;
	else return mysql_insert('links',array('from'=>$from,'to'=>$to));
}

/**
 * Grab all links on a given page, optionally for a specific depth
 *
 * @params int $pageID pageID
 * @params int $click optionally the number of clicks from the homepage to restrict results
 * @return array Multidimensional array keyed by target pageID with page data
 */
function get_links($pageID,$click = '') {
	$links = mysql_array(mysql_select('links',array('from'=>$pageID)),FALSE);
	foreach ($links as $link) $output[$link['to']] = get_page($link['to']);
	return $output;
}

/**
 * Shorthand MySQL function to count links in or out of a given page
 *
 * @params int $pageID subject page
 * @params string $direction Direction to retrieve (either "to" or "from")
 * @return int Number of links
 */
function count_links($pageID,$direction) {
	$result = mysql_select('links',array($direction=>$pageID));
	return mysql_num_rows($result);
}

/**
 * Shorthand MySQL function to get a particular page's row
 *
 * @params int $pageID target page
 * @return array Associative array of page data
 */
function get_page($pageID) {
	return mysql_row_array(mysql_select('urls',array('ID'=>$pageID)));
}


/**
 * Shorthand MySQL function to to get the first 100 uncrawled URLs 
 *
 * @return array Associative array of uncrawled URLs & page data
 */
function uncrawled_urls() {
	return mysql_array(mysql_query("SELECT * FROM `urls` WHERE `crawled` = '0' LIMIT 100"));
}

/**
 * Checks to see if a given URL is already in the pages table
 *
 * @params string $link URL to check
 * @return bool true if URL exists, false if not found
 */
function have_url($url) {
	$url = mysql_row_array(mysql_select('urls',array('url'=>urldecode($url))));
	if (sizeof($url)==0) return false;
	else return $url['ID'];
}

/* Depreciated (I think)

function count_slashes($url) {
	if (strlen($url)<7) return 0;
	return substr_count($url,'/',7);
}

function get_slashes($url) {
	if (preg_match_all('#/#',$url,$matches,PREG_OFFSET_CAPTURE,7)) return $matches[0];
	else return array();
}
*/

/**
 * Converts a relative URL (/bar) to an absolute URL (http://www.foo.com/bar)
 *
 * Inspired from code available at http://nadeausoftware.com/node/79, 
 * Code distributed under OSI BSD (http://www.opensource.org/licenses/bsd-license.php)
 * 
 * @params string $baseUrl Directory of linking page
 * @params string $relativeURL URL to convert to absolute
 * @return string Absolute URL
 */
function url_to_absolute( $baseUrl, $relativeUrl ) {
    // If relative URL has a scheme, clean path and return.
    $r = split_url( $relativeUrl );
    if ( $r === FALSE )
        return FALSE;
    if ( !empty( $r['scheme'] ) )
    {
        if ( !empty( $r['path'] ) && $r['path'][0] == '/' )
            $r['path'] = url_remove_dot_segments( $r['path'] );
        return join_url( $r );
    }
 
    // Make sure the base URL is absolute.
    $b = split_url( $baseUrl );
    if ( $b === FALSE || empty( $b['scheme'] ) || empty( $b['host'] ) )
        return FALSE;
    $r['scheme'] = $b['scheme'];
 
    // If relative URL has an authority, clean path and return.
    if ( isset( $r['host'] ) )
    {
        if ( !empty( $r['path'] ) )
            $r['path'] = url_remove_dot_segments( $r['path'] );
        return join_url( $r );
    }
    unset( $r['port'] );
    unset( $r['user'] );
    unset( $r['pass'] );
 
    // Copy base authority.
    $r['host'] = $b['host'];
    if ( isset( $b['port'] ) ) $r['port'] = $b['port'];
    if ( isset( $b['user'] ) ) $r['user'] = $b['user'];
    if ( isset( $b['pass'] ) ) $r['pass'] = $b['pass'];
 
    // If relative URL has no path, use base path
    if ( empty( $r['path'] ) )
    {
        if ( !empty( $b['path'] ) )
            $r['path'] = $b['path'];
        if ( !isset( $r['query'] ) && isset( $b['query'] ) )
            $r['query'] = $b['query'];
        return join_url( $r );
    }
 
    // If relative URL path doesn't start with /, merge with base path
    if ( $r['path'][0] != '/' )
    {
        $base = mb_strrchr( $b['path'], '/', TRUE, 'UTF-8' );
        if ( $base === FALSE ) $base = '';
        $r['path'] = $base . '/' . $r['path'];
    }
    $r['path'] = url_remove_dot_segments( $r['path'] );
    return join_url( $r );
}

/**
 * Required function of URL to absolute
 *
 * Inspired from code available at http://nadeausoftware.com/node/79, 
 * Code distributed under OSI BSD (http://www.opensource.org/licenses/bsd-license.php)
 * 
 */
function url_remove_dot_segments( $path ) {
    // multi-byte character explode
    $inSegs  = preg_split( '!/!u', $path );
    $outSegs = array( );
    foreach ( $inSegs as $seg )
    {
        if ( $seg == '' || $seg == '.')
            continue;
        if ( $seg == '..' )
            array_pop( $outSegs );
        else
            array_push( $outSegs, $seg );
    }
    $outPath = implode( '/', $outSegs );
    if ( $path[0] == '/' )
        $outPath = '/' . $outPath;
    // compare last multi-byte character against '/'
    if ( $outPath != '/' &&
        (mb_strlen($path)-1) == mb_strrpos( $path, '/', 'UTF-8' ) )
        $outPath .= '/';
    return $outPath;
}

/**
 * Required function of URL to absolute
 *
 * Inspired from code available at http://nadeausoftware.com/node/79, 
 * Code distributed under OSI BSD (http://www.opensource.org/licenses/bsd-license.php)
 * 
 */
function split_url( $url, $decode=TRUE )
{
    $xunressub     = 'a-zA-Z\d\-._~\!$&\'()*+,;=';
    $xpchar        = $xunressub . ':@%';

    $xscheme       = '([a-zA-Z][a-zA-Z\d+-.]*)';

    $xuserinfo     = '((['  . $xunressub . '%]*)' .
                     '(:([' . $xunressub . ':%]*))?)';

    $xipv4         = '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';

    $xipv6         = '(\[([a-fA-F\d.:]+)\])';

    $xhost_name    = '([a-zA-Z\d-.%]+)';

    $xhost         = '(' . $xhost_name . '|' . $xipv4 . '|' . $xipv6 . ')';
    $xport         = '(\d*)';
    $xauthority    = '((' . $xuserinfo . '@)?' . $xhost .
                     '?(:' . $xport . ')?)';

    $xslash_seg    = '(/[' . $xpchar . ']*)';
    $xpath_authabs = '((//' . $xauthority . ')((/[' . $xpchar . ']*)*))';
    $xpath_rel     = '([' . $xpchar . ']+' . $xslash_seg . '*)';
    $xpath_abs     = '(/(' . $xpath_rel . ')?)';
    $xapath        = '(' . $xpath_authabs . '|' . $xpath_abs .
                     '|' . $xpath_rel . ')';

    $xqueryfrag    = '([' . $xpchar . '/?' . ']*)';

    $xurl          = '^(' . $xscheme . ':)?' .  $xapath . '?' .
                     '(\?' . $xqueryfrag . ')?(#' . $xqueryfrag . ')?$';
 
 
    // Split the URL into components.
    if ( !preg_match( '!' . $xurl . '!', $url, $m ) )
        return FALSE;
 
    if ( !empty($m[2]) )        $parts['scheme']  = strtolower($m[2]);
 
    if ( !empty($m[7]) ) {
        if ( isset( $m[9] ) )   $parts['user']    = $m[9];
        else            $parts['user']    = '';
    }
    if ( !empty($m[10]) )       $parts['pass']    = $m[11];
 
    if ( !empty($m[13]) )       $h=$parts['host'] = $m[13];
    else if ( !empty($m[14]) )  $parts['host']    = $m[14];
    else if ( !empty($m[16]) )  $parts['host']    = $m[16];
    else if ( !empty( $m[5] ) ) $parts['host']    = '';
    if ( !empty($m[17]) )       $parts['port']    = $m[18];
 
    if ( !empty($m[19]) )       $parts['path']    = $m[19];
    else if ( !empty($m[21]) )  $parts['path']    = $m[21];
    else if ( !empty($m[25]) )  $parts['path']    = $m[25];
 
    if ( !empty($m[27]) )       $parts['query']   = $m[28];
    if ( !empty($m[29]) )       $parts['fragment']= $m[30];
 
    if ( !$decode )
        return $parts;
    if ( !empty($parts['user']) )
        $parts['user']     = rawurldecode( $parts['user'] );
    if ( !empty($parts['pass']) )
        $parts['pass']     = rawurldecode( $parts['pass'] );
    if ( !empty($parts['path']) )
        $parts['path']     = rawurldecode( $parts['path'] );
    if ( isset($h) )
        $parts['host']     = rawurldecode( $parts['host'] );
    if ( !empty($parts['query']) )
        $parts['query']    = rawurldecode( $parts['query'] );
    if ( !empty($parts['fragment']) )
        $parts['fragment'] = rawurldecode( $parts['fragment'] );
    return $parts;
}

/**
 * Required function of URL to absolute
 *
 * Inspired from code available at http://nadeausoftware.com/node/79, 
 * Code distributed under OSI BSD (http://www.opensource.org/licenses/bsd-license.php)
 * 
 */
function join_url( $parts, $encode=TRUE )
{
    if ( $encode )
    {
        if ( isset( $parts['user'] ) )
            $parts['user']     = rawurlencode( $parts['user'] );
        if ( isset( $parts['pass'] ) )
            $parts['pass']     = rawurlencode( $parts['pass'] );
        if ( isset( $parts['host'] ) &&
            !preg_match( '!^(\[[\da-f.:]+\]])|([\da-f.:]+)$!ui', $parts['host'] ) )
            $parts['host']     = rawurlencode( $parts['host'] );
        if ( !empty( $parts['path'] ) )
            $parts['path']     = preg_replace( '!%2F!ui', '/',
                rawurlencode( $parts['path'] ) );
        if ( isset( $parts['query'] ) )
            $parts['query']    = rawurlencode( $parts['query'] );
        if ( isset( $parts['fragment'] ) )
            $parts['fragment'] = rawurlencode( $parts['fragment'] );
    }
 
    $url = '';
    if ( !empty( $parts['scheme'] ) )
        $url .= $parts['scheme'] . ':';
    if ( isset( $parts['host'] ) )
    {
        $url .= '//';
        if ( isset( $parts['user'] ) )
        {
            $url .= $parts['user'];
            if ( isset( $parts['pass'] ) )
                $url .= ':' . $parts['pass'];
            $url .= '@';
        }
        if ( preg_match( '!^[\da-f]*:[\da-f.:]+$!ui', $parts['host'] ) )
            $url .= '[' . $parts['host'] . ']'; // IPv6
        else
            $url .= $parts['host'];             // IPv4 or name
        if ( isset( $parts['port'] ) )
            $url .= ':' . $parts['port'];
        if ( !empty( $parts['path'] ) && $parts['path'][0] != '/' )
            $url .= '/';
    }
    if ( !empty( $parts['path'] ) )
        $url .= $parts['path'];
    if ( isset( $parts['query'] ) )
        $url .= '?' . $parts['query'];
    if ( isset( $parts['fragment'] ) )
        $url .= '#' . $parts['fragment'];
    return $url;
}

/**
 * Returns filesize in human readable terms
 *
 * Inspired by code available at http://stackoverflow.com/questions/1222245/calculating-script-memory-usages-in-php
 * Code distributed under CC-Wiki License (http://creativecommons.org/licenses/by-sa/2.5/) 
 *
 * @params int $size filesize in bytes
 */
    function file_size($size)  {
		$filesizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
		return $size ? round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $filesizename[$i] : '0 Bytes';
    }

?>