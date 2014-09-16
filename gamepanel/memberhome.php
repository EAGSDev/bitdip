<?php


defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * This class displays the member subsection of a game panel in a homepage context.
 * Far less info shown, and in a different format.
 *
 * @package GamePanel
 */
class panelMemberHome extends panelMember
{
	/**
	 * The finalized icon, no text
	 * @return string
	 */
	function memberFinalizedFull()
	{
		return $this->memberFinalized();
	}

	/**
	 * The header bar displaying info about the joined member viewing, but all on one line
	 * @return string
	 */
	function memberHeaderBar()
	{
		return str_replace('<br />',' ', parent::memberHeaderBar());
	}

	/**
	 * A column of data in an array, with the countryID&online icon, then finalized icon, then sent-messages icon.
	 * Returned as an array so only as many columns as required are added, keeping length down.
	 * panelMembersHome makes this into a string of pure HTML.
	 *
	 * @return array array($countryID,$finalized,$sentMessages);
	 */
	function memberColumn()
	{
		global $User;

		$buf =array();
		$buf[] = '<span class="country'.$this->countryID.' '.($User->id==$this->userID?'memberYourCountry':'').
			' memberStatus'.$this->status.'">'.substr($this->country,0,3).(
				($this->online &&!$this->isNameHidden()) ? ' '.libHTML::loggedOn($this->userID) : '').'</span>';

		$buf[] = $this->memberFinalized();
		$buf[] = $this->memberSentMessages();

		return $buf;
	}

	/**
	 * The messages icon, no text
	 * @return string
	 */
	function memberMessagesFull()
	{
		if ( count($this->newMessagesFrom) )
		{
			if ( count($this->newMessagesFrom) == 1 && in_array('0',$this->newMessagesFrom) )
				return libHTML::maybeReadMessages('board.php?gameID='.$this->gameID.'#chatbox');
			else
				return libHTML::unreadMessages('board.php?gameID='.$this->gameID.'#chatbox');
		}
		else
			return '';
	}

	/**
	 * Points won
	 * @return string
	 */
	function memberBetWon()
	{
		return $this->pointsWon;
	}

	/**
	 * Units count, no SC info
	 * @return string
	 */
	function memberUnitSCCount()
	{
		if ( $this->unitNo < $this->supplyCenterNo )
			$unitStyle = "good";
		elseif ( $this->unitNo > $this->supplyCenterNo )
			$unitStyle = "bad";
		else
			$unitStyle = "neutral";

		return '
			<span class="'.$unitStyle.'">'.l_t('%s units','<em>'.$this->unitNo.'</em>').'</span></span>
				';
	}

	/**
	 * Detail on this member's game info, for the header info of the viewing member only
	 * @return string
	 */
	function memberGameDetail()
	{
		$buf = '';
		if ( $this->status != 'Playing')
			$buf .= '<span class="memberStatus"><em>'.$this->status.'</em>. </span>';
		else
		{
			if ($this->status == 'Defeated' )
				$buf .= '<span class="memberPointsCount">'.$this->memberBetWon().'</span>';
			else
				$buf .= '<span class="memberUnitCount">'.$this->memberUnitSCCount().'</span>';
		}

		return $buf;
	}
}
?>