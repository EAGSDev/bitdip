<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * Return all the maps for this game. May use a lot of resources if the maps aren't
 * already cached.
 *
 * @package Board
 */

print '<h3>'.l_t('Maps').'</h3>';

for($i=$Game->turn;$i>=0;$i--)
{
	if($i<$Game->turn && ($i%2)!=0) print '<div class="hr"></div>';

	print '<h4>'.$Game->datetxt($i).'</h4>';
	print '<p style="text-align:center">
		<img src="map.php?gameID='.$Game->id.'&turn='.$i.'" title="'.l_t('Small map for this turn').'" /><br />
		'.l_t('Large map:').' <a href="map.php?gameID='.$Game->id.'&largemap=on&turn='.$i.'">
					<img src="'.l_s('images/historyicons/external.png').'" alt="'.l_t('Large map').'"
						title="'.l_t('This button will open the large map in a new window. The large map shows all the moves, and is useful when the small map isn\'t clear enough').'."
					/></a>
		</p>';
}

?>