<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * A root class for adjudication; all units must defend, but only holding units
 * use this class alone, others extend this
 *
 * @package GameMaster
 * @subpackage Adjudicator
 */
class adjHold extends adjDependencyNode
{
	/**
	 * The unit ID
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The countryID name of the unit
	 *
	 * @var string
	 */
	public $countryID;

	/**
	 * The units which are potentially supporting this unit
	 *
	 * @var array
	 */
	public $supporters = array();

	/**
	 * The attackers which are attempting to dislodge this unit
	 *
	 * @var array
	 */
	public $attackers = array();

	/**
	 * @param int $id Unit ID
	 * @param int $countryID CountryID
	 */
	function __construct($id, $countryID)
	{
		$this->id = $id;
		$this->countryID = $countryID;
	}

	/**
	 * Import supporter and attacker units
	 *
	 * @param array $units
	 */
	public function setUnits(array $units)
	{
		foreach($this->supporters as &$supporter)
			$supporter = $units[$supporter];

		foreach($this->attackers as &$attacker)
			$attacker = $units[$attacker];
	}

	protected function _dislodged()
	{
		foreach($this->attackers as $attacker)
		{
			try
			{
				if( $attacker->success() )
					return true;
			}
			catch(adjParadoxException $pe)
			{
				if ( isset($p) ) $p->downSizeTo($pe);
				else $p = $pe;
			}
		}

		if ( isset($p) ) throw $p;
		else return false;
	}

	protected function _holdStrength()
	{
		/*
		 * Determine the max and min amount of hold strength that this unit has.
		 * If paradoxes are caught the smallest one is put into an array with the
		 * max and min value, and returned.
		 */

		$min = 1;
		$max = 1;
		foreach($this->supporters as $supporter)
		{
			try
			{
				if ( $supporter->success() )
				{
					$min++;
					$max++;
				}
			}
			catch(adjParadoxException $pe)
			{
				$max++;
				if ( isset($p) ) $p->downSizeTo($pe);
				else $p = $pe;
			}
		}

		// Wrap everything up and send it back to compare()
		$holdStrength = array('max'=>$max, 'min'=>$min);

		if ( isset($p) )
		{
			$holdStrength['paradox'] = $p;
		}

		return $holdStrength;
	}

	protected function _path()
	{
		// A convoy has its own path handler, for everyone else this is okay
		return true;
	}
}



?>