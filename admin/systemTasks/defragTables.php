<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * This file defragments the tables which may reach high ID levels. Once they go over
 * 2^32-1 PHP can't keep the ID in memory, automatically starts using floats, and the
 * imprecision causes lots of bizarre problems.
 *
 * The server must not be in use when this is run, but luckily it should never have
 * to be run except on the largest servers after many years (unless lots of DATC testing
 * is causing an artificially high number of orders etc)
 *
 * @package Admin
 */

if ( !$User->type['Admin'] )
	die(l_t('Admins only'));

ini_set('memory_limit',"12M");
ini_set('max_execution_time','60');

header('Content-Type: text/plain');

ob_end_flush();

print l_t('Defragmenting')."\n"; flush();

$tableNames = array('Moves','Orders','TerrStatus','Units');


foreach($tableNames as $tableName)
{
	print l_t('Defragmenting %s',$tableName)."\n"; flush();

	$DB->sql_put("BEGIN");

	list($max) = $DB->sql_row("SELECT MAX(id) FROM wD_".$tableName);

	$tabl = $DB->sql_tabl("SELECT id FROM wD_".$tableName." ORDER BY id ASC");

	$i=1;
	while( list($id) = $DB->tabl_row($tabl) )
	{
		if ( ( $i % 100 ) == 0 )
		{
			print "\t".$id."(/".$max.") -> ".$i."\n"; flush();
		}

		if ( $i != $id )
		{
			$DB->sql_put("UPDATE wD_".$tableName." SET id = ".$i." WHERE id = ".$id);

			if ( $tableName == 'Units' )
			{
				$DB->sql_put("UPDATE wD_TerrStatus SET occupyingUnitID = ".$i." WHERE occupyingUnitID = ".$id);

				$DB->sql_put("UPDATE wD_TerrStatus SET retreatingUnitID = ".$i." WHERE retreatingUnitID = ".$id);

				$DB->sql_put("UPDATE wD_Orders SET unitID = ".$i." WHERE unitID = ".$id);
			}
		}

		$i++;
	}

	$DB->sql_put("COMMIT");

	$DB->sql_put("ALTER TABLE wD_".$tableName." AUTO_INCREMENT = ".$i);

	print $tableName.' done: '.$max.'->'.$i."\n"; flush();
}

$DB->sql_put("COMMIT");

print l_t("Done")."\n";

?>