TO USE:

1. Edit config.PHP with appropriate database and domain information
2. (for now) in phpMyAdmin insert the seed URL into the urls table.
	* URL should be www.
	* URL should have a trailing slash
	* (for now) May also want to set clicks to '0' to avoid problems 
3. Open crawler.php
4. (optional) open stats.php to watch progress

TIPS:
	Changes to php.ini
		1. Increase memory limit (1GB)
		2. Remove execution time limit
	Changes to mysql.ini
		* Increased max query size (to avoid "mysql went away" error)

Additional documentation (source code) in (/source)