<?php


defined('IN_CODE') or die('This script can not be run by itself.');

require_once(l_r('gamepanel/member.php'));
/**
 * This class displays the members subsection of a game panel.
 *
 * @package GamePanel
 */
class panelMembers extends Members
{
	/**
	 * Load a panelMember instead of a Member
	 */
	protected function loadMember(array $row)
	{
		return $this->Game->Variant->panelMember($row);
	}

	/*
	private function sortBy($sortList, $by)
	{
		$byIndex=array();
		$byList=array();
		foreach($sortList as $obj)
		{
			$objVal = (int)$obj->{$by};

			if(!isset($byIndex[$objVal]))
				$byIndex[$objVal] = array();

			$byList[$objVal]=$objVal;
			$byIndex[$objVal][$obj->id] = $obj;
		}
		sort($byList);
		$byList=array_reverse($byList);
		$sorted=array();
		foreach($byList as $objVal)
			foreach($byIndex[$objVal] as $obj)
				$sorted[] = $obj;

		return $sorted;
	}
	*/

	/**
	 * The order in which to display the various statuses of members. Which come last etc.
	 * @var array
	 */
	private static $statusOrder=array('Won','Survived','Drawn','Playing','Left','Resigned','Defeated');

	/**
	 * The list of members; just names if pregame, otherwise a full detailed table, ordered by
	 * status then relative success in-game.
	 *
	 * @return string
	 */
	function membersList()
	{
		if( $this->Game->phase == 'Pre-game')
		{
			$membersNames = array();
			foreach($this->ByUserID as $Member)
				$membersNames[] = '<span class="memberName">'.$Member->memberName().'</span>';

			return '<table><tr class="member memberAlternate1 memberPreGameList"><td>'.
				implode(', ',$membersNames).'</td></tr></table>';
		}

		libHTML::$alternate=2;
		$membersList = array();
		foreach(self::$statusOrder as $status)
		{
			foreach($this->ByStatus[$status] as $Member)
			{
				$membersList[] = '<tr class="member memberAlternate'.libHTML::alternate().'">'.
					$Member->memberBar().'</tr>';
			}
		}

		return '<table>'.implode('',$membersList).'</table>';
	}

	/**
	 * A form showing a selection of civil-disorder countries which can be taken over from
	 * @return string
	 */
	public function selectCivilDisorder()
	{
		global $User;

		$buf = "";
		if( 1==count($this->ByStatus['Left']) )
		{
			foreach($this->ByStatus['Left'] as $Member);

			$buf .= '<input type="hidden" name="countryID" value="'.$Member->countryID.'" />
				'.l_t('<label>Take over:</label> %s, for %s.',$Member->countryColored(),'<em>'.$Member->pointsValue().libHTML::points().'</em>');
		}
		else
		{
			$buf .= '<label>'.l_t('Take over:').'</label> <select name="countryID">';
			foreach($this->ByStatus['Left'] as $Member)
			{
				$pointsValue = $Member->pointsValue();

				if ( $User->points >= $pointsValue )
				{
					$buf .= '<option value="'.$Member->countryID.'" />
						'.l_t('%s, for %s',$Member->country,$pointsValue).'
						</option>';
				}
			}
			$buf .= '</select>';
		}
		return $buf;
	}

	/**
	 * The occupation bar HTML; only generate it once then store it here, as it is usually used at least twice for one game
	 * @var unknown_type
	 */
	private $occupationBarCache;

	/**
	 * The occupation bar; a bar representing each of the countries current progress as measured by the number of SCs.
	 * If called pre-game it goes from red to green as 1 to 7 players join the game.
	 *
	 * @return string
	 */
	function occupationBar()
	{
		if ( isset($this->occupationBarCache)) return $this->occupationBarCache;

		libHTML::$first=true;
		if( $this->Game->phase != 'Pre-game' )
		{
			$SCPercents = $this->SCPercents();
			$buf = '';
			foreach($SCPercents as $countryID=>$width)
				if ( $width > 0 )
					$buf .= '<td class="occupationBar'.$countryID.' '.libHTML::first().'" style="width:'.$width.'%"></td>';
		}
		else
		{
			$joinedPercent = ceil((count($this->ByID)*100.0/count($this->Game->Variant->countries)));
			$buf = '<td class="occupationBarJoined '.libHTML::first().'" style="width:'.$joinedPercent.'%"></td>';
			if ( $joinedPercent < 99.0 )
				$buf .= '<td class="occupationBarNotJoined" style="width:'.(100-$joinedPercent).'%"></td>';
		}

		$this->occupationBarCache = '<table class="occupationBarTable"><tr>
					'.$buf.'
				</tr></table>';

		return $this->occupationBarCache;
	}
}
?>