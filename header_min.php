<?php
session_start();



if( strpos($_SERVER['PHP_SELF'], 'header.php') ) {die("You can't view this document by itself.");}
if( !defined('IN_CODE') ) {define('IN_CODE', 1);} // A flag to tell scripts they aren't being executed by themselves



require_once('../../bitdip_config.php');
require_once('global/definitions.php');



if( strlen(Config::$serverMessages['ServerOffline']) ) {die('<html><head><title>Server offline</title></head><body>'.Config::$serverMessages['ServerOffline'].'</body></html>');}

require_once('locales/layer.php');
global $Locale;
require_once('locales/'.Config::$locale.'/layer.php'); // This will set $Locale
$Locale->initialize();

require_once('objects/user.php');
require_once('objects/bitdip.php');

date_default_timezone_set('UTC');

// Create database object
require_once('objects/database.php');
$DB = new Database();
// jimbursch - the following is for the MySQLi framework
$charset='';
$errormsg='';
$DBi = new Databasei(DB_NAME, DB_HOST, DB_USER, DB_PASSWORD, $charset, DB_DEBUG, $errormsg);


// Set up the misc values object
require_once('objects/misc.php');
global $Misc;
$Misc = new Misc();


################################################################
// authentication

if (isset($_SESSION['user_data'])) {
	$userid=$_SESSION['user_data']['id'];
	$User = new User($userid);
}
else {
	$User = new User(GUESTID);
	$_SESSION['user_data']['id']=$User->id;
}



#########################################################
################################################################


?>