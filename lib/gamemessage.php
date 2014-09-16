<?php

defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * Send a message to a member of a countryID in a game, from another member. Used by GameMaster in processGame, and
 * Chatbox
 *
 * @package Base
 * @subpackage Game
 */
class libGameMessage
{
	/**
	 * Send a game message. Messages are sanitized
	 *
	 * @param string $toCountryID The countryID being sent to. 'Global' sends to all.
	 * @param string $fromCountryID The county being sent from. 'GameMaster' can also be used.
	 * @param string|array $message The message(s) to be sent (Can be an array of messages for)
	 * @param int[optional] $gameID The game ID to use. If not given the current global Game is sent to.
	 */
	static public function send($toCountryID, $fromCountryID, $message, $gameID=-1)
	{
		global $DB, $Game;

		if ( ! is_object($Game) )
		{
			$Variant=libVariant::loadFromGameID($gameID);
			$Game = $Variant->Game($gameID);
		}

		$message = $DB->msg_escape($message);

		if ( !is_numeric($toCountryID) )
			$toCountryID=0;

		if ( !is_numeric($fromCountryID) )
		{
			$message = '<strong>'.$fromCountryID.':</strong> '.$message;
			$fromCountryID=0;
		}

		if( 65000 < strlen($message) )
		{
			throw new Exception(l_t("Message too long"));
		}

		$DB->sql_put("INSERT INTO wD_GameMessages
					(gameID, toCountryID, fromCountryID, turn, message, timeSent)
					VALUES(".$Game->id.",
						".$toCountryID.",
						".$fromCountryID.",
						".$Game->turn.",
						'".$message."',
						".time().")");

		if ($toCountryID != $fromCountryID)
		{
			libGameMessage::notify($toCountryID, $fromCountryID);
		}
	}

	/**
	 * Notify a countryID that you sent them a message, uses the global Game
	 *
	 * @param string $toCountryID The countryID sent to, can be 'Global'
	 * @param string $fromCountryID The countryID sent from
	 * @param Game $Game The game being referred to
	 */
	private static function notify($toCountryID, $fromCountryID)
	{
		global $DB, $Game;

		$DB->sql_put("COMMIT"); // Prevent deadlocks

		if ( $toCountryID == 0 )
		{
			$DB->sql_put("UPDATE wD_Members
						SET newMessagesFrom = IF( (newMessagesFrom+0) = 0,
												'0',
												CONCAT_WS(',',newMessagesFrom,'0') )
						WHERE gameID = ".$Game->id." AND NOT countryID=".$fromCountryID);
		}
		else
		{
			$DB->sql_put("UPDATE wD_Members
						SET newMessagesFrom = IF( (newMessagesFrom+0) = 0,
												'".$fromCountryID."',
												CONCAT_WS(',',newMessagesFrom,'".$fromCountryID."') )
						WHERE gameID = ".$Game->id." AND countryID=".$toCountryID);
		}
	}
}
?>