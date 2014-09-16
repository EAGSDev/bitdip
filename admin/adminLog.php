<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * This script displays the 20 most recent admin/moderator actions made with the adminActionsForms class.
 *
 * @package Admin
 */


if( !isset($_REQUEST['full']) )
	print '<a href="admincp.php?tab=Control Panel Logs&full=on">'.l_t('View all logs').'</a>';

print '<table class="credits">';

$alternate = 1;
$tabl = $DB->sql_tabl(
	"SELECT a.name, u.username, a.time, a.details, a.params
		FROM wD_AdminLog a INNER JOIN wD_Users u ON ( a.userID = u.id )
		ORDER BY a.time DESC ".(isset($_REQUEST['full'])?'':"LIMIT 20"));

while ( $row = $DB->tabl_hash($tabl) )
{
	$row['time'] = libTime::text($row['time']);

	$params = $row['params'];
	/*
	$params = @unserialize($row['params']);
	if( count($params) )
	{
		$p=array();
		foreach($params as $name=>$value) {
			if( $name=='userID' )
				$value = '<a href="profile.php?userID='.$value.'" class="light">'.$value.'</a>';
			elseif( $name=='gameID' )
				$value = '<a href="board.php?gameID='.$value.'" class="light">'.$value.'</a>';

			$p[]=$name.'='.$value;
		}
		$params='<br />Params: '.implode(', ',$p);
		unset($p);
	}
	else
		$params='';
	*/

	$alternate = ( $alternate ? 0 : 1 );

	print '<tr class="replyalternate'.($alternate+1).'">';

	print '<td class="left time">'.$row['time'].'</td>';

	print '<td class="right message">'.$row['username'].': <strong>'.$row['name'].'</strong>: '.$row['details'].$params.'</td>';
	print '</tr>';
}

print '</table>';

?>