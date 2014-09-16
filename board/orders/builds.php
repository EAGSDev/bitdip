<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * Loads this players options for their Unit Placing phase orders. Lets users choose orders, and then
 * is used to check the selection for validity.
 *
 * @package Board
 * @subpackage Orders
 */
class userOrderBuilds extends userOrder
{
	public function __construct($orderID, $gameID, $countryID)
	{
		parent::__construct($orderID, $gameID, $countryID);
	}

	protected function updaterequirements()
	{
		if( $this->type == 'Wait')
			$this->requirements=array('type');
		else
			$this->requirements=array('type','toTerrID');
	}

	protected function typeCheck()
	{
		// People could alter the type here (change destory to build) but it'd get caught in the adjudicator
		switch( $this->type )
		{
			case 'Build Army':
			case 'Build Fleet':
			case 'Wait':
				return true;
			case 'Destroy':
				return true;
			default:
				return false;
		}
	}

	protected function toTerrIDCheck()
	{
		$this->toTerrID=(int)$this->toTerrID;

		if( $this->type == 'Build Army' )
		{
			/*
			 * Creating an army at which territory
			 *
			 * Unoccupied supply centers owned by our country, which the specified unit type
			 * can be built in. If a parent coast is found return Child entries.
			 */
			return $this->sqlCheck(
				"SELECT t.id
				FROM wD_TerrStatus ts
				INNER JOIN wD_Territories t
					ON ( t.id = ts.terrID )
				WHERE ts.gameID = ".$this->gameID."
					AND ts.countryID = ".$this->countryID."
					AND t.countryID = ".$this->countryID."
					AND ts.occupyingUnitID IS NULL
					AND t.supply = 'Yes' AND NOT t.type='Sea'
					AND NOT t.coast = 'Child'
					AND t.id=".$this->toTerrID."
					AND t.mapID=".MAPID."
				LIMIT 1");
		}
		elseif( $this->type == 'Build Fleet' )
		{
			return $this->sqlCheck(
				"SELECT IF(t.coast='Parent', coast.id, t.id) as terrID
				FROM wD_TerrStatus ts
				INNER JOIN wD_Territories t ON ( t.id = ts.terrID )
				LEFT JOIN wD_Territories coast ON ( coast.mapID=t.mapID AND coast.coastParentID = t.id AND NOT t.id = coast.id )
				WHERE ts.gameID = ".$this->gameID."
					AND ts.countryID = ".$this->countryID."
					AND t.countryID = ".$this->countryID."
					AND ts.occupyingUnitID IS NULL
					AND t.supply = 'Yes'
					AND t.type = 'Coast'
					AND ( t.coast='No' OR ( t.coast='Parent' AND NOT coast.id IS NULL ) )
					AND (
						(t.coast='Parent' AND coast.id=".$this->toTerrID.")
						OR t.id=".$this->toTerrID."
					)
					AND t.mapID=".MAPID."
				LIMIT 1");
		}
		elseif ( $this->type == 'Destroy' )
		{
			/*
			 * Destroying a unit at which territory
			 */
			return $this->sqlCheck(
				"SELECT terrID
				FROM wD_TerrStatus
				WHERE gameID = ".$this->gameID."
					AND occupyingUnitID IS NOT NULL
					AND countryID = ".$this->countryID."
					AND terrID = ".$this->toTerrID."
				LIMIT 1"
			);
		}
		else
		{
			throw new Exception(l_t("Checking the territory when not required."));
		}
	}
}

?>