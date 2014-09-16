<?php


defined('IN_CODE') or die('This script can not be run by itself.');

require_once(l_r('gamepanel/memberhome.php'));
/**
 * This class displays the members subsection of a game panel in a homepage context.
 *
 * @package GamePanel
 */
class panelMembersHome extends panelMembers
{
	/**
	 * Load a panelMemberHome instead of a panelMember
	 */
	protected function loadMember(array $row)
	{
		return $this->Game->Variant->panelMemberHome($row);
	}

	/**
	 * Display a table with the vital members info; who is finalized, who has sent messages etc, each member
	 * takes up a short, thin column.
	 * @return string
	 */
	function membersList()
	{
		global $User;

		// $membersList[$i]=array($nameOrCountryID,$iconOne,$iconTwo,...);
		$membersList = array();

		if( $this->Game->phase == 'Pre-game')
		{
			$count=count($this->ByID);
			for($i=0;$i<$count;$i++)
				$membersList[]=array(($i+1),'<img src="'.l_s('images/icons/tick.png').'" alt=" " title="'.l_t('Player joined, spot filled').'" />');
			for($i=$count;$i<=count($this->Game->Variant->countries);$i++)
				$membersList[]=array(($i+1), '');
		}
		else
		{
			for($countryID=1; $countryID<=count($this->Game->Variant->countries); $countryID++)
			{
				$Member = $this->ByCountryID[$countryID];

				//if ( $User->id == $this->ByCountryID[$countryID]->userID )
				//	continue;
				//elseif( $Member->status != 'Playing' && $Member->status != 'Left' )
				//	continue;

				$membersList[] = $Member->memberColumn();
			}
		}

		$buf = '<table class="homeMembersTable">';
		$rowsCount=count($membersList[0]);

		$alternate = libHTML::$alternate;
		for($i=0;$i<$rowsCount;$i++)
		{
			$rowBuf='';

			$dataPresent=false;
			$remainingPlayers=count($this->ByID);
			$remainingWidth=100;
			foreach($membersList as $data)
			{
				if($data[$i]) $dataPresent=true;

				if( $remainingPlayers>1 )
					$width = floor($remainingWidth/$remainingPlayers);
				else
					$width = $remainingWidth;

				$remainingPlayers--;
				$remainingWidth -= $width;

				$rowBuf .= '<td style="width:'.$width.'%" class="barAlt'.libHTML::alternate().'">'.$data[$i].'</td>';
			}
			libHTML::alternate();
			if($dataPresent)
			{
				$buf .= '<tr>'.$rowBuf.'</tr>';
			}

			libHTML::$alternate = $alternate;
		}
		libHTML::alternate();

		$buf .= '</table>';
		return $buf;


	}
}
?>