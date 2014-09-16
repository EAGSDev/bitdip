<?php


/**
 * Generates the JSON data used to generate orders for a certain game, used by OrderInterface.
 *
 */
class jsonBoardData
{
	public static function getBoardTurnData($gameID)
	{
		return "function loadBoardTurnData() {\n".self::getUnits($gameID)."\n\n".self::getTerrStatus($gameID)."\n}\n";
	}

	protected static function getUnits($gameID)
	{
		global $DB;

		$units = array();
		$tabl=$DB->sql_tabl("SELECT id, terrID, countryID, type FROM wD_Units WHERE gameID = ".$gameID);
		while($row=$DB->tabl_hash($tabl))
		{
			$units[$row['id']] = $row;
		}

		return 'Units = $H('.json_encode($units).');';
	}
	protected static function getTerrStatus($gameID)
	{
		global $DB;

		$terrstatus=array();
		$tabl=$DB->sql_tabl("SELECT terrID as id, standoff, occupiedFromTerrID, occupyingUnitID as unitID, countryID as ownerCountryID
			FROM wD_TerrStatus WHERE gameID = ".$gameID);
		while($row=$DB->tabl_hash($tabl)) {
			$row['standoff'] = ($row['standoff']=='Yes');
			$terrstatus[] = $row;
		}

		return 'TerrStatus = '.json_encode($terrstatus).';';
	}
}

?>