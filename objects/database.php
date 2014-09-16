<?php

defined('IN_CODE') or die('This script can not be run by itself.');

// TODO: Use MySQLi instead of the PHP4 mysql API used here

/**
 * A MySQL DB interaction object.
 *
 * @package Base
 */
class Database {

	public static function affected() {
		return mysql_affected_rows();
	}

	/**
	 * The MySQL resource object used to identify the database connection
	 */
	private $link;

	/**
	 * @var int The number of times sql_put() has been called
	 */
	public $putqueries=0;

	/**
	 * @var int The number of times sql_tabl() has been called
	 */
	public $getqueries=0;

	/**
	 * This is like implode with a wrapper around it, because using implode for this function again
	 * and again got messy
	 *
	 * @param string $before Text before packed array
	 * @param array $array The array to pack
	 * @param string $after Text after packed array
	 * @param string $joiner Text to join the array pieces
	 *
	 * @return string Packed array
	 */
	public static function packArray($before, array $array, $after, $joiner)
	{
		if ( ! count($array) )
		{
			return '';
		}
		else
		{
			return $before . implode($after.$joiner.$before, $array) . $after;
		}
	}

	/**
	 * Initialize the database connection
	 */
	public function __construct()
	{
		$this->link = mysql_connect(Config::$database_socket,
				Config::$database_username, Config::$database_password);

		if( ! $this->link )
			trigger_error(l_t("Couldn't connect to the MySQL server, if this problem persists please inform the admin."));

		if( ! mysql_select_db(Config::$database_name, $this->link) )
			trigger_error(l_t("Connected to the MySQL server, but couldn't access the specified database. ".
						"If this problem persists please inform the admin."));

			/*
			 * Using InnoDB's default transaction isolation level (REPEATABLE-READ) a snapshot is taken when you
			 * first read.
			 * Any other changes made by other transactions aren't read, except for LOCK IN SHARE MODE, which will
			 * always get the latest data from the database.
			 *
			 * The amazing thing is, locking FOR UPDATE /does not/ get the latest data from the database, despite
			 * it being a "tougher" lock than LOCK IN SHARE MODE (read/write-lock instead of just read-lock).
			 *
			 * So what used to happen is when joining a game and members were locked FOR UPDATE, and a player would
			 * join based on the false assumption that the game and member rows must be the latest rows.
			 *
			 * SELECT whatever			|
			 * 							| SELECT whatever
			 * LOCK game etc FOR UPDATE	|
			 * CHECK player can join	|
			 * INSERT player into game	| LOCK game etc FOR UPDATE [waiting...]
			 * COMMIT					|
			 * 							| CHECK player can join (using same info as when SELECT whatever happened!!)
			 * 							| INSERT player into game
			 * 							| COMMIT
			 *
			 * A player has joined the game /twice/ despite read/write locking..
			 *
			 *
			 * Because of this we use READ COMMITTED, which ensures that not only LOCK IN SHARE MODE gets the latest
			 * committed data, but /all selects/ get the latest data (having the latest data is a pretty useful
			 * transaction-mode)
			 */
		$this->sql_put("SET AUTOCOMMIT=0, NAMES utf8, time_zone = '+0:00'");
		$this->sql_put("SET TRANSACTION ISOLATION LEVEL READ COMMITTED");
	}

	/**
	 * Close the database connection
	 */
	public function __destruct()
	{
		if ( ! mysql_close($this->link) )
		{
			// This function may be called after/before the other objects are around.
			die(l_t("Could not successfully close connection to database."));
		}
	}

	/**
	 * Sanitize incoming strings, leaving newlines. Suitable for messages.
	 * Replace newlines with <br />, and allow <br /> through the filter.
	 *
	 * @param string $text The string to be escaped
	 *
	 * @return string The sanitized string
	 */
	public function msg_escape($text, $htmlAllowed=false)
	{
		// str_replace is binary safe, nl2br isn't
		$text = str_replace("\r\n",'<br />',$text);
		$text = str_replace("\n",'<br />',$text);
		$text = str_replace("\r",'<br />',$text);

		$text = $this->escape($text, $htmlAllowed);

		$text = str_replace('&lt;br /&gt;', '<br />', $text);

		return $text;
	}

	/**
	 * Un-escape something escaped
	 *
	 * @param string $text The text to un-escape
	 *
	 * @return string Dangerous text
	 */
	public function un_msg_escape($text)
	{
		// str_replace is binary safe, nl2br isn't
		$text = str_replace("\r\n",'<br />',$text);
		$text = str_replace("\n",'<br />',$text);
		$text = str_replace("\r",'<br />',$text);

		$text = $this->escape($text);

		$text = str_replace('&lt;br /&gt;', '<br />', $text);

		return $text;
	}

	/**
	 * Sanitize incoming strings, filtering out all HTML. Suitable for all
	 * data.
	 *
	 * @param string $text The string to be escaped
	 *
	 * @return string The sanitized string
	 */
	public function escape($text, $htmlAllowed=false)
	{
		$text = (string) $text;
		$text = mysql_real_escape_string($text, $this->link);
		if ( !$htmlAllowed )
			$text = htmlentities( $text , ENT_NOQUOTES, 'UTF-8');
		return $text;
	}

	/**
	 * Query the database and return a MySQL table resource.
	 *
	 * @param string $sql A safe, pre-sanitized SQL query
	 *
	 * @return resource A MySQL table resource
	 */
	public function sql_tabl($sql)
	{
		global $User;

		$this->getqueries++;

		if( Config::$debug )
			$timeStart=microtime(true);

		if ( ! ( $resource = mysql_query($sql, $this->link) ) )
		{
			trigger_error(mysql_error($this->link));
		}

		if( Config::$debug )
			$this->profiler($timeStart, $sql);

		return $resource;
	}

	private $totalQueryTime=0.0;
	private $badQueries=array();
	private $slowestQuery=0.0;
	private function profiler($timeStart, $sql)
	{
		$timeTaken=microtime(true)-$timeStart;
		if($timeTaken>$this->slowestQuery)
			$this->slowestQuery = $timeTaken;

		$this->totalQueryTime += $timeTaken;

		$currentAv = ($this->totalQueryTime/($this->getqueries+$this->putqueries));

		if ( $timeTaken > 2*$currentAv )
		{
			$this->badQueries[] = array($timeTaken, $sql);
		}
	}

	public function profilerPrint()
	{
		$buf = '';

		$stats = array(
			'Total query time'=>$this->totalQueryTime,
			'Average query time'=>($this->totalQueryTime/($this->getqueries+$this->putqueries)),
			'Slowest query'=>$this->slowestQuery
		);

		$buf .= '<table>';
		foreach($stats as $name=>$val)
			$buf .= '<tr><td>'.l_t($name).':</td><td>'.$val.' sec</td></tr>';
		$buf .= '</table>';

		$buf .= '<p><strong>'.l_t('Bad queries:').'</strong></p>';
		$buf .= '<table>';
		foreach($this->badQueries as $pair)
			$buf .= '<tr><td style="width:5%">'.$pair[0].' sec</td><td>'.$pair[1].'</td></tr>';
		$buf .= '</table>';

		return $buf;
	}

	/**
	 * Take the next numbered MySQL row from a table resource, or return false
	 * if no rows remain $row[0]
	 *
	 * @param resource $tabl A MySQL table resource
	 *
	 * @return array|bool A numbered row containing one row from the table, or false if there are no rows remaining.
	 */
	public function tabl_row($tabl)
	{
		if ( $tabl == false )
		{
			return false;
		}
		else
		{
			$row = mysql_fetch_row($tabl);

			if ( ! $row )
			{
				mysql_free_result($tabl);
				return false;
			}
			else
			{
				return $row;
			}
		}
	}

	/**
	 * Take the next named MySQL row from a table resource, or return false
	 * if no rows remain $row['foo']
	 *
	 * @param resource $tabl A MySQL table resource
	 *
	 * @return array|bool A named row containing one row from the table, or false if there are no rows remaining.
	 */
	public function tabl_hash($tabl)
	{
		if ( $tabl == false )
		{
			return false;
		}
		else
		{
			$row = mysql_fetch_assoc($tabl);
			if ( ! $row )
			{
				mysql_free_result($tabl);
				return false;
			}
			else
			{
				return $row;
			}
		}
	}

	/**
	 * Run a SQL query and return a single numbered row $row[0]
	 *
	 * @param string $sql The SQL query
	 *
	 * @return array|bool A numbered row, or false if no rows were returned
	 */
	public function sql_row($sql)
	{
		$tabl = $this->sql_tabl($sql);
		$row = $this->tabl_row($tabl);

		// Free the table resource from memory, if it hasn't already been freed by tabl_row
		if ( $row ) mysql_free_result($tabl);

		return $row;
	}

	/**
	 * Run a SQL query and return a single named row $row['foo']
	 *
	 * @param string $sql The SQL query
	 *
	 * @return array|bool A named row, or false if no rows were returned
	 */
	public function sql_hash($sql)
	{
		$tabl = $this->sql_tabl($sql);
		$row = $this->tabl_hash($tabl);

		// Free the table resource from memory, if it hasn't already been freed by tabl_row
		if ( $row ) mysql_free_result($tabl);

		return $row;
	}

	/**
	 * Run a data insertion SQL query, halting webDip if there is an error
	 *
	 * @param string $sql The data insertion SQL query
	 *
	 * @returns int The ID of the last inserted row, which may be irrelevant if an INSERT/UPDATE query weren't performed
	 */
	public function sql_put($sql)
	{
		global $User;

		$this->putqueries++;

		if( Config::$debug )
			$timeStart=microtime(true);

		if(! mysql_query($sql, $this->link) )
		{
			trigger_error(mysql_error($this->link));
		}

		if( Config::$debug )
			$this->profiler($timeStart, $sql);
	}

	public function last_affected()
	{
		return mysql_affected_rows($this->link);
	}

	public function last_inserted()
	{
		return mysql_insert_id($this->link);
	}

	/**
	 * Get a MySQL named lock, will stop the script if the lock cannot be obtained
	 *
	 * @param string $name The name of the lock
	 * @param int[optional] $wait The time to wait before giving up, default is 8 seconds
	 */
	public function get_lock($name, $wait=8)
	{
		list($success) = $this->sql_row("SELECT GET_LOCK('".$name."', ".$wait.")");

		if ( $success != 1 )
		{
			libHTML::error(l_t("A database lock (%s) is required to complete this page safely, but it could not be ".
				"acquired (it's being used by someone else). This usually means the server is running slowly, and ".
				"taking unusually long to complete tasks.",$name)."<br /><br />".
				l_t("Please wait a few moments and try again. Sorry for the inconvenience."));
		}
	}

}

##########################################################################################
##########################################################################################
##########################################################################################
##########################################################################################





/**
 * MySQL Database Framework for MySQLi
 *
 * Version: 1.0.1 (June 11, 2012)
 * Author: Sunny Singh (http://sunnyis.me)
 * Project Page: http://github.com/sunnysingh/database
 */

class Databasei {

 public $connection, $connection_error, $connection_error_code, $server_info, $client_info, $host_info, $insert_id, $affected_rows;
 public $query_count_all = 0, $query_count_success = 0, $query_count_error = 0;
 private $debug, $stmt, $result, $filters = array();

 // Sets up database connection
 public function __construct($name, $server, $username, $password, $charset = "utf8", $debug = true, $errormsg = "Database connection failed.") {
  if ($this->debug) { mysqli_report(MYSQLI_REPORT_ERROR); }
  else { mysqli_report(MYSQLI_REPORT_OFF); }
  $this->connection = @new mysqli($server, $username, $password, $name);
  $this->connection_error = $this->connection->connect_error;
  $this->connection_error_code = $this->connection->connect_errno;
  $this->debug = $debug;
  if (!$this->connection_error_code) {
   $this->connection->set_charset($charset);
   if ($charset == "utf8") { $this->connection->query("SET NAMES utf8"); }
   $this->server_info = $this->connection->server_info;
   $this->client_info = $this->connection->client_info;
   $this->host_info = $this->connection->host_info;
  }
  else if ($this->connection_error_code && $errormsg !== false) {
   error_log("MySQL database error:  ".$this->connection_error." for error code ".$this->connection_error_code);
   trigger_error("MySQL database error:  ".$this->connection_error." for error code ".$this->connection_error_code);
   if ($this->debug == 'true') { die("Database Connection Error ".$this->connection_error_code.": ".$this->connection_error); } else { die($errormsg); }
  }
 }

 // Automatically close database connection
 public function __destruct() {
  if (!$this->connection_error_code) { $this->connection->close(); }
 }

  // Filters

 public function add_filter($type, $filter) {
  if (is_callable($filter)) { $this->filters[$type] = $filter; }
  else { $this->filters[$type] = false; }
 }

public function filter_exists($type) {
$return = (isset($this->filters[$type]) ? $this->filters[$type] : false);
return $return;
}

 private function apply_filter($type, $args = array()) {
  $call = call_user_func($this->filters[$type], $args);
  return $call[0];
 }

  // Queries

 // Used internally for all queries and externally for INSERT, UPDATE, DELETE, etc.
 public function query($query, $params = array(), $markers = null) {
  if ($this->filter_exists("query")) { $query = $this->apply_filter("query", array($query)); }
  $this->stmt = $this->connection->prepare($query);
  $this->query_count_all++;
  if ($this->stmt) {
   $this->query_count_success++;
   if (count($params)) {
    foreach ($params as $key => $param) {
     if (is_int($param) || is_bool($param)) {
      $markers .= "i";
      $param_trueval = intval($param);
      $params_bindable[] = $param_trueval;
     } else if (is_double($param)) {
      $markers .= "d";
      $param_trueval = doubleval($param);
      $params_bindable[] = $param_trueval;
     } else {
      $markers .= "s";
      $param_trueval = strval($param);
      $params_bindable[] = $param_trueval;
     }
    }
    // For some reason, creating references within the first loop breaks some queries
    foreach ($params_bindable as $key => &$param) {
     $params_bindable_withref[$key] = &$param;
    }
    array_unshift($params_bindable_withref, $markers);
    call_user_func_array(array($this->stmt, "bind_param"), $params_bindable_withref);
   }
   $execute = $this->stmt->execute();
   // An extra check to see if the query executed without errors (first check is when we first prepare the query)
   if (!$execute) {
	$debug_backtrace = debug_backtrace();
	error_log("MySQL database error:  ".$this->stmt->error." for query ".$query." in ".$debug_backtrace[1]["file"]." on line ".$debug_backtrace[1]["line"]);
	trigger_error("MySQL database error:  ".$this->stmt->error." for query ".$query." in ".$debug_backtrace[1]["file"]." on line ".$debug_backtrace[1]["line"]);
    if ($this->debug == 'true') {
     echo "MySQL database error: ".$this->stmt->error." for query <pre><code>".$query."</code></pre> in ".$debug_backtrace[1]["file"]." on line ".$debug_backtrace[1]["line"];
     exit();
	}
	return false;
   }
   if ($this->stmt->field_count) {
    $fields = $this->stmt->result_metadata()->fetch_fields();
    foreach ($fields as $key => $field) {
     $fields_names[$field->name] = &$field->name;
    }
    call_user_func_array(array($this->stmt, "bind_result"), $fields_names);
    return $fields_names;
   } else {
    // Set or update info relating to the latest query
    $this->insert_id = $this->connection->insert_id;
    $this->affected_rows = $this->connection->affected_rows;
    // Close statement
    $this->stmt->close();
    // Return affected rows if any, otherwise return true (because 0 is considered false)
    if ($this->affected_rows) { return $this->affected_rows; } else { return true; }
   }
  } else {
   $this->query_count_error++;
   $debug_backtrace = debug_backtrace();
   error_log("MySQL database error:  ".$this->connection->error." for query ".$query." in ".$debug_backtrace[1]["file"]." on line ".$debug_backtrace[1]["line"]);
   trigger_error("MySQL database error:  ".$this->connection->error." for query ".$query." in ".$debug_backtrace[1]["file"]." on line ".$debug_backtrace[1]["line"]);

  if ($this->debug == 'true') {
    echo "Database error: ".$this->connection->error." for query <pre><code>".$query."</code></pre> in ".$debug_backtrace[1]["file"]." on line ".$debug_backtrace[1]["line"];
    exit();
   }
   return false;
  }
 }

  // Data Fetching

 // Fetch a single field from a single row
 public function fetch_field($query, $params = array()) {
  $this->result = $this->query($query, $params);
  if ($this->result && count($this->stmt->fetch())) {
   $result_value = current($this->result);
   $this->stmt->free_result();
   $this->stmt->close();
   return $result_value;
  } else {
   $this->stmt->free_result();
   $this->stmt->close();
   return false;
  }
 }

 // Fetch multiple fields from a single row
 public function fetch_row($query, $object = true, $params = array()) {
  $this->result = $this->query($query, $params);
  if ($this->result && $this->stmt->store_result() && $this->stmt->num_rows) {
   $this->stmt->fetch();
   if ($object) {
    $result_object = new stdClass();
    foreach ($this->result as $key => $value) {
     $result_object->$key = $value;
    }
    $this->stmt->free_result();
    $this->stmt->close();
    return $result_object;
   } else {
    foreach ($this->result as $key => $value) {
     $result_array[$key] = $value;
    }
    $this->stmt->free_result();
    $this->stmt->close();
    return $result_array;
   }
  } else {
   $this->stmt->close();
   return false;
  }
 }

 // Fetch multiple fields from multiple rows
 public function fetch_rows($query, $object = true, $params = array()) {
  $this->result = $this->query($query, $params);
  if ($this->result && $this->stmt->store_result() && $this->stmt->num_rows) {
   if ($object) {
    while ($this->stmt->fetch()) {
     $row = new stdClass();
     foreach ($this->result as $key => $value) {
      $row->$key = $value;
     }
     $result_object[] = $row;
    }
    $this->stmt->free_result();
    $this->stmt->close();
    return $result_object;
   } else {
    while ($this->stmt->fetch()) {
     $row = array();
     foreach ($this->result as $key => $value) {
      $row[$key] = $value;
     }
     $result_array[] = $row;
    }
    $this->stmt->free_result();
    $this->stmt->close();
    return $result_array;
   }
  } else {
   //$this->stmt->close();
   return false;
  }
 }

 // Deprecated, use the parameter binding feature to escape user data
 public function escape($str) {
  return $this->connection->real_escape_string($str);
 }

}

?>
