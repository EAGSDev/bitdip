<?php


defined('IN_CODE') or die('This script can not be run by itself.');

require_once(l_r('board/orders/base/territory.php'));
/**
 * Holds unit data for orders, including the territory which the unit is staying at.
 *
 * @package Base
 * @subpackage Game
 */
class Unit {
	/**
	 * Unit ID
	 *
	 * @var int
	 */
	var $id;

	/**
	 * Unit type: 'Army'/'Fleet'
	 *
	 * @var string
	 */
	var $type;

	/**
	 * Occupying territory, with coast data
	 *
	 * @var string
	 */
	var $terrID;

	/**
	 * CountryID owner
	 *
	 * @var string
	 */
	var $countryID;

	/**
	 * Game ID
	 *
	 * @var int
	 */
	var $gameID;

	/**
	 * Occupied Territory object
	 * @var Territory
	 */
	var $Territory;

	/**
	 * Initialize a unit
	 *
	 * @param int $id Unit ID
	 */
	function __construct($row)
	{
		global $DB;

		if( !is_array($row) )
			$row = $DB->sql_hash("SELECT * FROM wD_Units WHERE id = ".$row);

		foreach ( $row as $name=>$value )
			$this->{$name} = $value;
	}
}
?>
