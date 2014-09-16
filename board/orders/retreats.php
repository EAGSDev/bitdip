<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * Loads this players options for their Retreats phase orders. Lets users choose orders, and then
 * is used to check the selection for validity.
 *
 * @package Board
 * @subpackage Orders
 */
class userOrderRetreats extends userOrder
{
	public function __construct($orderID, $gameID, $countryID)
	{
		parent::__construct($orderID, $gameID, $countryID);
	}

	protected function updaterequirements()
	{
		if( $this->type == 'Retreat' )
			$this->requirements=array('type','toTerrID');
		else
			$this->requirements=array('type');
	}

	protected function typeCheck()
	{
		switch($this->type) {
			case 'Retreat':
			case 'Disband':
				return true;
			default:
				return false;
		}
	}

	protected function toTerrIDCheck()
	{
		$this->toTerrID=(int)$this->toTerrID;

		return $this->sqlCheck(
			"SELECT
				linkBorder.toTerrID
			FROM wD_Units retreatingUnit
			INNER JOIN wD_TerrStatus retreatingFrom
				ON ( retreatingFrom.retreatingUnitID = retreatingUnit.id )
			INNER JOIN wD_CoastalBorders linkBorder
				ON (
					linkBorder.mapID = ".MAPID." AND
					linkBorder.fromTerrID = retreatingUnit.terrID
					AND (
						retreatingFrom.occupiedFromTerrID IS NULL
						OR NOT ".libVariant::$Variant->deCoastCompare('retreatingFrom.occupiedFromTerrID','linkBorder.toTerrID')."
					)
				)
			LEFT JOIN wD_TerrStatus retreatingTo
				ON (
					".libVariant::$Variant->deCoastCompare('retreatingTo.terrID','linkBorder.toTerrID')."
					AND retreatingFrom.gameID = retreatingTo.gameID
				)
			WHERE retreatingUnit.id = ".$this->Unit->id."
				AND linkBorder.".strtolower($this->Unit->type)."sPass = 'Yes'
				AND retreatingTo.occupyingUnitID IS NULL
				AND ( retreatingTo.standoff IS NULL OR retreatingTo.standoff = 'No' )
				AND linkBorder.toTerrID = ".$this->toTerrID."
			LIMIT 1"
		);
	}
}

?>