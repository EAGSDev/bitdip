<?php


/**
 * The header file; sanitize, initialize, get everything set up quickly
 *
 * @package Base
 */



#########################################################
// session management
session_start();

if (!isset($_SESSION['timeout_idle'])) {$_SESSION['timeout_idle'] = time() + 900;}
else {
    if ($_SESSION['timeout_idle'] < time()) {
    session_destroy();
    session_start();
	$_SESSION['timeout_idle'] = time() + 900;
    }
    else {$_SESSION['timeout_idle'] = time() + 900;}
}// end else

#######################################################

if( strpos($_SERVER['PHP_SELF'], 'header.php') )
{
	die("You can't view this document by itself.");
}

if( !defined('IN_CODE') )
	define('IN_CODE', 1); // A flag to tell scripts they aren't being executed by themselves

require_once('../../bitdip_config.php');

require_once('global/definitions.php');

if( strlen(Config::$serverMessages['ServerOffline']) )
	die('<html><head><title>Server offline</title></head>'.
		'<body>'.Config::$serverMessages['ServerOffline'].'</body></html>');


if( ini_get('request_order') !== false ) {

	// There is a request_order php.ini variable; this must be PHP 5.3.0+

	/*
	 * This variable determines whether $_COOKIE is included in $_REQUEST;
	 * if request_order contains no 'c' then $_COOKIE is not included.
	 *
	 * $_COOKIE shouldn't be included in $_REQUEST, however since webDip
	 * has historically relied on it being there this code is here
	 * temporarily, while the improper $_REQUEST references are found and
	 * switched to $_COOKIE.
	 */
	if( substr_count(strtolower(ini_get('request_order')), 'c') == 0 ) {
		/*
		 * No 'c' in request_order, so no $_COOKIE variables in $_REQUEST;
		 * $_COOKIE will need to be merged into $_REQUEST manually.
		 *
		 * The default config used to be GPC ($_GET, $_POST, $_COOKIE), so
		 * to get the standard behaviour $_COOKIE overwrites variables
		 * already in $_REQUEST.
		 */

		foreach($_COOKIE as $key=>$value)
		{
			$_REQUEST[$key] = $value;
			// array_merge could be used here, but creating a new array
			// for use as a super-global can have weird results.
		}
	}
}

/*
 * If register_globals in enabled remove globals.
 */
if (ini_get('register_globals') or get_magic_quotes_gpc())
{
	function stripslashes_deep(&$value)
	{
		if ( is_array($value) )
			return array_map('stripslashes_deep', $value);
		else
			return stripslashes($value);
	}

	$defined_vars = get_defined_vars();
	while( list($var_name, $var_value) = each($defined_vars) )
	{
		switch( $var_name )
		{
			case "_COOKIE":
			case "_POST":
			case "_GET":
			case "_REQUEST":
				if (get_magic_quotes_gpc())
				{
					// Strip slashes if magic quotes added slashes
					${$var_name} = stripslashes_deep(${$var_name});
				}
				break;
			case "_SERVER":
				break; // Don't strip slashes on _SERVER variables, slashes aren't added to these
			case "_FILES":
				break; // Don't strip slashes on _FILES (file uploads, currently only used for locale text lookup changes)
			default:
				unset( ${$var_name} ); // Remove register_globals variables
				break;
		}
	}

	unset($defined_vars);
}

// Support the legacy request variables
if ( isset($_REQUEST['gid']) ) $_REQUEST['gameID'] = $_REQUEST['gid'];
if ( isset($_REQUEST['uid']) ) $_REQUEST['userID'] = $_REQUEST['uid'];

// Reset globals
// FIXME: Resetting this means $GLOBALS['asdf'] is no longer kept in sync with global $asdf. This causes problems during construction
$GLOBALS = array();
$GLOBALS['scriptStartTime'] = microtime(true);

ini_set('memory_limit',"8M"); // 8M is the default
ini_set('max_execution_time','8');
//ini_set('session.cache_limiter','public');
ignore_user_abort(TRUE); // Carry on if the user exits before the script gets printed.
	// This shouldn't be necessary for data integrity, but either way it may save reprocess time

ob_start(); // Buffer output. libHTML::footer() flushes.

// All the standard includes.
require_once('lib/cache.php');
require_once('lib/time.php');
require_once('lib/html.php');

require_once('locales/layer.php');

global $Locale;
require_once('locales/'.Config::$locale.'/layer.php'); // This will set $Locale
$Locale->initialize();

require_once(l_r('objects/silence.php'));
require_once(l_r('objects/user.php'));
require_once(l_r('objects/game.php'));
require_once(l_r('objects/bitdip.php'));

require_once(l_r('global/error.php'));
// Set up the error handler

date_default_timezone_set('UTC');

// Create database object
require_once(l_r('objects/database.php'));
$DB = new Database();
// jimbursch - the following is for the MySQLi framework
$charset='';
$errormsg='';
$DBi = new Databasei(DB_NAME, DB_HOST, DB_USER, DB_PASSWORD, $charset, DB_DEBUG, $errormsg);


// Set up the misc values object
require_once(l_r('objects/misc.php'));
global $Misc;
$Misc = new Misc();



// Taken from the php manual to disable cacheing.
header("Last-Modified: Mon, 26 Jul 1997 05:00:00 GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);



#########################################################
################################################################
// new authentication



if (!isset($_SESSION['user_data'])) {

	$userid=GUESTID;

	if (isset($_COOKIE['security_key'])) {
		$givenkey=$_COOKIE['security_key'];
		$query="SELECT id FROM wD_Users WHERE SecurityKey=?";
		$row=$DBi->fetch_row("$query",false,array($givenkey));
		if ($row) {$userid=$row['id'];}
	}// end if (isset($_COOKIE['security_key']))

	$User = new User($userid,true);

}// end if (!isset($_SESSION['user_data']) && isset($_COOKIE['security_key']))

else {$User = new User($_SESSION['user_data']['id'],true);}



#########################################################
################################################################
// logoff

if (isset($_GET['logoff'])) {

	if (isset($_SESSION['user_data']['id'])) {$userid=$_SESSION['user_data']['id'];} else {$userid=GUESTID;}

	//set new SecurityKey
	$bitdip= new BitDip();
	$newkey=$bitdip->generatesecuritykey($userid);


	// unset cookies
	if (isset($_SERVER['HTTP_COOKIE'])) {
		$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
		foreach($cookies as $cookie) {
			$parts = explode('=', $cookie);
			$name = trim($parts[0]);
			setcookie($name, '', time()-1000);
			setcookie($name, '', time()-1000, '/');
			$servername=$_SERVER['SERVER_NAME'];
			setcookie($name, 0, time()-100, '/',"$servername",0);
		}
	}// end if (isset($_SERVER['HTTP_COOKIE']))

	// Unset all of the session variables.
	$_SESSION = array();

	// Finally, destroy the session.
	session_destroy();

	// If its desired to kill the session, also delete the session cookie.
	// Note: This will destroy the session, and not just the session data!
	if (isset($_COOKIE[session_name()])) {setcookie(session_name(), '', time()-42000, '/');}

	header("Location: ./logon.php");
	die('line 310');
}// end if (isset($_GET['logoff']))

#######################################################################################



// This gets called by libHTML::footer
function close()
{
	global $DB, $Misc;

	// This isn't put into the database destructor in case of dieing due to an error

	if ( is_object($DB) )
	{
		$Misc->write();

		if( !defined('ERROR'))
			$DB->sql_put("COMMIT");

		unset($DB);
	}

	ob_end_flush();

	die();
}


?>