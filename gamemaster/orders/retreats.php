<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * Converts & sanitizes orders to moves for processing, then performs the actions based on
 * the results of the processed moves, and also creates new moves
 *
 * @package GameMaster
 * @subpackage Orders
 */
class processOrderRetreats extends processOrder
{
	/**
	 * Wipe all the incomplete orders.
	 */
	public function completeAll()
	{
		global $DB, $Game;

		$DB->sql_put("UPDATE wD_Orders
			SET type='Disband'
			WHERE gameID = ".$Game->id." AND type='Retreat' AND toTerrID IS NULL");
	}

	/**
	 * Convert orders to moves to be adjudicated
	 */
	public function toMoves()
	{
		global $DB, $Game;

		// Insert all the needed info into the moves table, stripping off the coasts data, which the adjudicator doesn't deal with
		$DB->sql_put("INSERT INTO wD_Moves
				( 	gameID, orderID, countryID, moveType, unitID, toTerrID )
			SELECT 	gameID, id, 	countryID, type, 		unitID, ".$Game->Variant->deCoastSelect('toTerrID')." as toTerrID
			FROM wD_Orders WHERE gameID = ".$Game->id);
	}

	/**
	 * Create Retreats orders for the current game
	 */
	public function create()
	{
		global $DB, $Game;

		// An order is needed for every unit which is retreating
		$DB->sql_put("INSERT INTO wD_Orders
				( gameID, countryID, type, unitID )
			SELECT t.gameID, u.countryID, 'Retreat', u.id
			FROM wD_Units u INNER JOIN wD_TerrStatus t ON ( t.retreatingUnitID = u.id )
			WHERE t.gameID = ".$Game->id);
	}

	/**
	 * Apply the adjudicated moves; delete/create units as decided
	 */
	public function apply()
	{
		global $DB, $Game;

		/*
		 * Delete units which couldn't disband
		 */
		$DB->sql_put(
				"DELETE FROM u
				USING wD_Units AS u
				INNER JOIN wD_Moves m ON ( m.unitID = u.id AND m.gameID=".$GLOBALS['GAMEID']." )
				WHERE m.moveType = 'Disband' OR m.success='No'");

		/*
		 * Update the units table for units which could retreat
		 */
		$DB->sql_put(
			"UPDATE wD_Units u
				INNER JOIN wD_Orders o ON ( o.unitID = u.id )
				INNER JOIN wD_Moves m ON ( m.orderID = o.id AND m.gameID=".$GLOBALS['GAMEID']." )
			SET u.terrID = o.toTerrID
			WHERE o.type = 'Retreat' AND m.success = 'Yes' AND o.gameID = ".$Game->id);

	}
}

?>