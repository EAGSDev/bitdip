<?php


defined('IN_CODE') or die('This script can not be run by itself.');

if ( $Misc->Panic )
	libHTML::error('Cannot update in a panic.');

/*
 * This code may be needed for big updates
 *
 * if ( !$Misc->Maintenance )
{
	$Misc->Maintenance = 1;
	$Misc->write();
	libHTML::error('Cannot update unless in maintenance mode; maintenance mode set, wait a minute for clients to
		finish and run again.');
}

ini_set('memory_limit',"20M"); // 8M is the default
ini_set('max_execution_time','120');

$DB->get_lock('install',0); // Make sure only one person performs the update

$Misc->read(); // Check we haven't updated while waiting for the lock

if( $Misc->Version == VERSION )
	libHTML::notice('Complete','Update complete');
*/

if ( $Misc->Version == 98 || $Misc->Version == 99 )
{
	$Misc->Version = 100;
	$Misc->write();
	libHTML::notice('Updated','Updated version number, please refresh.');
}
elseif ( $Misc->Version == 104 || $Misc->Version == 130 )
{
	$Misc->Version = 131;
	$Misc->write();
	libHTML::notice('Updated','Updated version number, please refresh.');
}
else
{
	unset($DB); // Prevent libHTML from trying to do anything fancy if the database is out of sync with the code
	libHTML::error(
			"Database version ".($Misc->Version/100)." and code
			version ".(VERSION/100)." don't match, and no
			auto-update script is available for this version.
			Please wait while the admin runs update.sql"
		);
}

print '</div>';
libHTML::footer();

?>