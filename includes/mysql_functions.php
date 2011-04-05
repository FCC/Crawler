<?php
/**
 * Custom library of bare-bones MYSQL query and minipulation functions to peform basic SELECT, INSERT, and UPDATE queries.
 *
 * Library provides a VERY lightweight wrapper to to sanitize data and run simple MySQL queries.  Accepts associative arrays for both
 * query and data, and return associative arrays with results.
 *
 * Functions assume a MySQL connection has been established, and that a database has been selected, e.g. 
 * <code>
 * $db=mysql_connect (MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD) or die ('I cannot connect to the database because: ' . mysql_error());
 * mysql_select_db (MYSQL_DATABASE);
 * </code>
 *
 * Changes: 1.1 Added option bool to mysql_array to toggle assoc., 1.2 switch mysql_real_escape_string for addslashes
 *
 * @author Benjamin J. Balter <ben@balter.com>
 * @package mysql_functions
 * @version 1.1
 *
 */

/**
 * Function to generate a multi-dimensional associative array from a MySQL resource.
 * 
 * Example Usage, given the table below
 * 
 *			Players 
 *			--------------------
 *			ID		Name	Position
 *			--------------------
 *			1		Kevin	1B
 *			2		Tom		LF
 *			3		Sally	SS
 *			
 * The Command "mysql_array(mysql_select('players'))"
 *
 * Would return the following array 
 * <code>
 * 	array(
 *		[1] => array(
 *				['ID'] => 1,
 *				['Name'] => "Kevin",
 *				['Position'] => '1B'
 *			),
 *		[2] => array(
 *				['ID'] => 2,
 *				['Name'] => "Tom",
 *				['Position'] => 'LF'
 *			),
 *			
 *		[3] => array(
 *				['ID'] => 3,
 *				['Name'] => "Sally",
 *				['Position'] => 'SS'
 *		)
 *	)
 *
 * @param resource $result MySQL resource object (either output of mysql_query($sql) or mysql_select('table',$query))
 * @param bool $assoc makes Associate array optional (added 1.1)
 * @return array Multi-dimensional Associative array keyed to first field in table, returns empty array if no results
 *		
 */
function mysql_array($result,$assoc = TRUE) {
	
	//Start with a null results set
	$results = array();
	
	if ($assoc) {
		//Grab the first fieldname to key the array with
		$first = mysql_field_name($result,0);
		
		//Loop through each row and build an assoc. array
		while ($row = mysql_fetch_assoc($result)) $results += array($row[$first] => $row);
	} else {
		//Loop through each row and build a array
		while ($row = mysql_fetch_assoc($result)) $results[] = $row;
	}
			
	//Strip slashes and return
	return stripslashes_deep($results);
}

/**
 * Returns an array of a single MySQL result row.
 *
 * Similar to mysql_fetch_assoc exccept it strips slashes, returns an empty array (rather than an error) if resource is bad or no results are found
 * 
 * @param resource $result MySQL resource object (either output of mysql_query($sql) or mysql_select('table',$query))
 * @return array Associative array of row, returns empty array if no results
 * @package mysql_functions
 */
function mysql_row_array($result) {

	// Verify we have a valid MySQL resource, otherwise return an empty array
	if (!$result) return array();
	
	// Veryify there are results to the query, otherwise return an empty array
	if (mysql_num_rows($result) ==0) return array();
	
	//Strip slashes and return the result of mysql_fetch_assoc.
	return stripslashes_deep(mysql_fetch_assoc($result));
}

/**
 * Generates SQL query, sanitizes data, and inserts row into database.
 *
 * Example Usage
 * <code>
 * $data = array(	'Name'=>'Joan',
 *					'Position'=>'2B'
 *				);
 *	mysql_insert('players',$data);
 * </code>
 *
 * @param string $table Name of table to operate on
 * @param array $data Associative array of data fields and values
 * @returns int|bool ID of inserted row if valid, false if invalid
 * @package mysql_functions
 */

function mysql_insert($table, $data) {

	//Build query
	$sql = "INSERT INTO `$table` (";
	foreach ($data as $field => $value) $sql .= "`$field`, ";
	$sql = substr($sql,0,strlen($sql)-2) . ") VALUES (";
	foreach ($data as $field => $value) $sql .= "'" .  mysql_real_escape_string($value) . "', ";
	
	//Remove last comma
	$sql = substr($sql,0,strlen($sql)-2) . ")";
	
	// Run query and return either ID or false (error)
	if (mysql_query($sql)) return mysql_insert_id();
	return false;
}

/**
 * Generates SQL query, sanitizes data, and updates row in database.
 *
 * Example Usage
 * <code>			
 * //Updates Tom's row and moves him to Right Field
 * $data = array(	'Position' => 'RF');
 * $query = array(	'Name' => 'Tom'	);
 * mysql_update('players', $data, $query);
 *	
 *	----
 *	 		
 * //Updates all players named 'Tom' or in Left Field and moves them to Right Field
 * $data = array(	'Position' => 'RF');
 * $query = array(	'Name' => 'Tom', 'Position' => 'LF' );
 * mysql_update('players', $data, $query, "OR");
 * </code>
 *
 * @param string $table Name of table to operate on
 * @param array $data Associative array of data fields and values
 * @param array $query Associative array of query fields and values
 * @param string $connector (Optional) connector for quiery ('AND' or 'OR')
 * @return bool true or false on sucess or fail
 * @package mysql_functions
 */
 
function mysql_update($table, $data, $query, $connector = "AND") {

	//Format the SQL query
	$sql = "UPDATE `$table` SET ";
	foreach ($data as $field => $value) $sql .= "`$field` = '" .  mysql_real_escape_string($value) ."', ";
	$sql = substr($sql,0,strlen($sql)-2) . " WHERE ";
	foreach ($query as $field => $value) $sql .= "`$field` = '$value' $connector ";
	
	//Remove the last connector
	$sql = substr($sql,0,strlen($sql)-(strlen($connector)+1));
	
	//return a bool with the query's result
	if (mysql_query($sql)) return true;
	return false;
}
/**
 * Builds an SQL query, sanitizes the data, removes a row from the database.
 *
 * EXAMPLE USAGE
 * <code>
 * $query = array('ID'=>'3');
 * mysql_remove('players',$query);
 * </code>
 *
 * @param string $table Name of table to operate on
 * @param array $query Associative array of query field names and values
 * @param string $connector (Optional) query connector ('AND' or 'OR')
 * @return bool true or false for sucess or fail
 * @package mysql_functions
 *					
 */
function mysql_remove($table, $query=array(), $connector = "AND") {

	//Build the SQL Query
	$sql = "DELETE FROM `$table` WHERE ";
	foreach ($query as $field => $value) $sql .= "`$field` = '" .  mysql_real_escape_string($value) . "' $connector ";
	
	//Remove the last connecter
	$sql = substr($sql,0,strlen($sql)-(strlen($connector)+1));
	
	//return a bool with the query's result
	if (mysql_query($sql)) return true;
	return false;
}

/**
 * Builds an SQL query, sanatizes data, and return a MySQL resource object with the results.
 *
 * Typically used in conjunction with mysql_array or mysql_row_array to handle simple MySQL queries
 *
 * For example, to return an entire table:
 * <code>
 * mysql_select('Players');
 * </code>
 * Or to return a select set of results:
 * <code>
 * $query = array('Name'=>'Tom');
 * mysql_select('Players',$query);
 * </code>
 *
 * @param string $table Name of table to operate on
 * @param array $query Associative array of query field names and values
 * @param string $connector (Optional) query connector ('AND' or 'OR')
 * @return object MySQL resource object with results
 * @package mysql_functions
 *
 */
function mysql_select($table, $query=array(), $connector = "AND") {
	
	//Build the SQL Query
	$sql = "SELECT * FROM `$table` ";
	
	//If there is no WHERE clause, just run the query
	if (sizeof($query)>0) { 
		$sql .= "WHERE ";
		
		//Loop through the fields/values
		foreach ($query as $field => $value) $sql .= "`$field` = '" .  mysql_real_escape_string($value) . "' $connector ";
		
		//Remove the last connector
		$sql = substr($sql,0,strlen($sql)-(strlen($connector)+1));
	}
	
	//Run the query
	$result = mysql_query($sql);
	
	//Output an errors if applicable
	if (mysql_error()) echo "<p>" . mysql_error() . ": $sql</p>";
	
	//Return the result (as a MySQL resource)
	return $result;
}

/**
 * Runs a simple mysql SELECT query and returns true or false if results are found.
 *
 * Used to verify data (such as a username or password) when the existence of the fields (rather than their value) is what is sought
 *
 * @param string $table Name of table to operate on
 * @param array $query Associative array of query field names and values
 * @param string $connector (Optional) query connector ('AND' or 'OR')
 * @return bool returns true if one or more results found, otherwise returns false
 * @package mysql_functions
 *
 */
function mysql_exists($table,$query=array(),$connector="AND") {
	$result = mysql_select($table,$query,$connector);
	if (mysql_num_rows($result)!=0) return true;
	return false;
}

/**
 * Removes slashes from multi-dimensional arrays.
 *
 * Runs stripslashes() on all values in a multi-dimensial array.  Used with mysql_array to remove slashes added by add_slashes() form mysql_insert().
 * Can also accept a standard array.
 * 
 * @param array $value Array to be sanitized, may be single or multi-dimensional
 * @return array Return array identical to one given but with slashes removed
 * @package mysql_functions
 *
 */
function stripslashes_deep($value) {
    $value = is_array($value) ?
        array_map('stripslashes_deep', $value) :
        stripslashes($value);
    return $value;
}


?>