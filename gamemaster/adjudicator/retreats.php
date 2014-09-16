<?php


defined('IN_CODE') or die('This script can not be run by itself.');
/**
 * @package GameMaster
 * @subpackage Adjudicator
 */

class adjudicatorRetreats {
	/**
	 * Adjudicate retreats orders: Fail retreats to the same place, everything else is fine
	 */
	function adjudicate()
	{
		global $DB;

		/*
		 * - Fail any situations where more than one unit is retreating to one place
		 * - Anything remaining is successful
		 */

		/*
		 * There's not much adjudication to do, when the orders are generated from a fixed list
		 */

		{
			// Units retreating to the same place as one or more other units fail
			$tabl = $DB->sql_tabl(
				"SELECT toTerrID, COUNT(toTerrID) as countToTerrID
				FROM wD_Moves
				WHERE moveType = 'Retreat' AND gameID = ".$GLOBALS['GAMEID']."
				GROUP BY toTerrID
				HAVING countToTerrID > 1");
			$blockedRetreats = array();
			while(list($toTerrID) = $DB->tabl_row($tabl))
			{
				/*
				 * This has to be done in two queries because it is modifying its own table
				 */
				$blockedRetreats[] = $toTerrID;
			}

			if ( count($blockedRetreats) )
			{
				$DB->sql_put(
					"UPDATE wD_Moves
					SET success = 'No'
					WHERE toTerrID IN ( '".implode("','", $blockedRetreats)."' ) AND gameID = ".$GLOBALS['GAMEID']);
			}

			unset($blockedRetreats);
		}

		// Only valid retreat orders are left (Disbanding units are always successful)
		$DB->sql_put("UPDATE wD_Moves SET success = 'Yes' WHERE NOT success = 'No' AND gameID = ".$GLOBALS['GAMEID']);
		$DB->sql_put("UPDATE wD_Moves SET dislodged = 'No' WHERE gameID = ".$GLOBALS['GAMEID']);
	}
}

?>