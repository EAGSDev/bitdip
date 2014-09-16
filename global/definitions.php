<?php


/**
 * @package Base
 */

defined('IN_CODE') or die('This script can not be run by itself.');

define("VERSION", 135) ;

// Some integer values which are named for clarity.

// System user IDs
define("GUESTID",1);

// InnoDB lock modes
define("NOLOCK", '');
define("SHARE", ' LOCK IN SHARE MODE');
define("UPDATE", ' FOR UPDATE');

// The dynamic and static server links
define("DYNAMICSRV", Config::$facebookServerURL);
define("STATICSRV", Config::$facebookStaticURL);

// Allow easy renaming of the javascript and css directories, which prevents all sorts of cacheing
// problems (people complaining about bugs in old code)
define("JSDIR", 'javascript');
define("CSSDIR", 'css');

if( !defined('FACEBOOK') )
	define('FACEBOOK',false);
?>
