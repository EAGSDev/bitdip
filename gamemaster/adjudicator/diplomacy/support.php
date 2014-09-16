<?php

defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * A supporting unit; support hold and move have many similarities
 *
 * @package GameMaster
 * @subpackage Adjudicator
 */
abstract class adjSupport extends adjHold
{
	protected abstract function attacked();

	protected function _success()
	{
		try
		{
			if ( $this->attacked() )
				return false;
		}
		catch(adjParadoxException $p) { }

		try
		{
			if ( $this->dislodged() )
				return false;
		}
		catch(adjParadoxException $pe)
		{
			if ( isset($p) ) $p->downSizeTo($pe);
			else $p = $pe;
		}

		if ( isset($p) ) throw $p;
		else return true;
	}
}

/**
 * Support moving unit
 *
 * @package GameMaster
 * @subpackage Adjudicator
 */
class adjSupportMove extends adjSupport
{
	public $supporting;

	public function setUnits(array $units)
	{
		$this->supporting = $units[$this->supporting];

		parent::setUnits($units);
	}

	protected function attacked()
	{
		foreach($this->attackers as $attacker)
		{
			if ( isset($this->supporting->defender) )
				if ( $attacker->id == $this->supporting->defender->id )
					continue; // The unit attacking me is the unit I'm supporting against

			try
			{
				if ( $attacker->compare('attackStrength','>',0) )
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
}

/**
 * Support holding unit
 *
 * @package GameMaster
 * @subpackage Adjudicator
 */
class adjSupportHold extends adjSupport
{
	protected function attacked()
	{
		foreach($this->attackers as $attacker)
		{
			try
			{
				if ( $attacker->compare('attackStrength','>',0) )
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
}

?>