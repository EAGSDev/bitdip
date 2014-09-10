<?php
/*
    Copyright (C) 2004-2010 Kestas J. Kuliukas

	This file is part of webDiplomacy.

    webDiplomacy is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    webDiplomacy is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with webDiplomacy.  If not, see <http://www.gnu.org/licenses/>.
 */

defined('IN_CODE') or die('This script can not be run by itself.');

/**
 * An class which groups authentication functions
 *
 * @package Base
 */
class libAuth
{
	public static function resourceLimiter($name, $seconds)
	{
		global $User;

		if( !$User->type['User'] )
			libHTML::notice(
				l_t('Denied'),
				l_t("Please <a href='register.php' class='light'>register</a> or ".
					"<a href='logon.php' class='light'>log in</a> to %s.",l_t($name))
			);

		if( !isset($_SESSION['resources']) )
			$_SESSION['resources']=array();

		if( isset($_SESSION['resources'][$name]) && (time()-$_SESSION['resources'][$name]) < $seconds )
			libHTML::notice(
				l_t('Denied'),l_t("One %s per %s seconds, please wait and try again.",$name,$seconds)
			);

		$_SESSION['resources'][$name]=time();
	}

	public static function gamemasterToken_Valid($gameMasterToken)
	{
		$token = explode('_',$gameMasterToken);
		if( count($token) != 3 )
			throw new Exception(l_t('Corrupt token %s',$gameMasterToken));

		list($gameID, $time, $hash) = $token;
		if ( self::gamemasterToken_Key($gameID,$time) != $hash )
			throw new Exception(l_t('Invalid token %s',$gameMasterToken));

		if ( (time()-$time)>5*60 )
			throw new Exception(l_t('Token %s expired (%s)',$gameMasterToken,time()));
	}

	private static function gamemasterToken_Key($gameID, $time)
	{
		return md5($gameID.$time.Config::$gameMasterSecret);
	}
	public static function likeToggleToken_Key($userID, $messageID) {

		return md5('likeToggle-'.$userID.'-'.$messageID.'-'.Config::$secret);
	}
	public static function likeToggleToken($userID, $messageID) {

		return $userID.'_'.$messageID.'_'.self::likeToggleToken_Key($userID, $messageID);
	}
	public static function likeToggleToken_Valid($token) {

		$token = explode('_',$token);

		if( count($token) != 3 )
			throw new Exception(l_t('Corrupt token %s',$token));

		$userID = (int)$token[0];
		$messageID = (int)$token[1];
		$key = $token[2];

		if( $key !== self::likeToggleToken_Key($userID, $messageID))
			throw new Exception(l_t('Invalid token %s',$token));

		return true;
	}

	public static function gamemasterToken($gameID)
	{
		$time=time();
		return $gameID.'_'.$time.'_'.self::gamemasterToken_Key($gameID,$time);
	}

###########################################################################################################

	/**
	 * This function logs a user on, or returns a guest account, and if it's an admin
	 * it'll change the admin's user if required
	 *
	 * @return User An authenticated user account
	 */
	public static function auth()
	{
		if( false )
		{
			if (!strpos($_SERVER['PHP_SELF'], 'register.php')
				and !strpos($_SERVER['PHP_SELF'], 'map.php')
				and !strpos($_SERVER['PHP_SELF'], 'gamemaster.php'))
			{
				$User = new User($facebook->require_login());
				$User->logon(); //key_User does  this if not on facebook
			}
			else
			{
				$User = new User(GUESTID);
				$_SESSION['user_data'] = $User;
			}
		}
		else
		{
			if (isset($_COOKIE['wD-Key']) and $_COOKIE['wD-Key'])
				$key = $_COOKIE['wD-Key'];
			else
				$key = false;

			if ( $key ) {
				$User = self::key_User($key);
				}
			else
				$User = new User(GUESTID);
				$_SESSION['user_data'] = $User;
		}

		return $User;
	}

	/**
	 * Let admin users log on as other users, for debugging
	 * @var User $User The admin user
	 * @return User The user being switched to
	 */
	static public function adminUserSwitch(User $User)
	{
		assert('$User->type["Admin"]');

		if ( isset($_REQUEST['auid']) )
		{
			$auid = intval($_REQUEST['auid']);
		}
		elseif ( isset($_SESSION['auid']) )
		{
			$auid = $_SESSION['auid'];
		}

		if ( isset($auid) )
		{
			if ( $User->id == $auid || $auid <= 0 )
			{
				if ( isset($_SESSION['auid']) )
					unset($_SESSION['auid']);
			}
			else
			{
				try
				{
					define('AdminUserSwitch',$User->id); // Used to display the switch-back button in libHTML::starthtml()
					$User = new User($auid);
				}
				catch( Exception $e )
				{
					libHTML::error("Bad auid given");
				}

				$_SESSION['auid'] = $auid;
			}
		}

		return $User;
	}




	############################################
	// given userid, create key

	private static function userID_Key( $userID ) {return $userID.'_'.md5(md5(Config::$secret).$userID.sha1(Config::$secret));}




	############################################
	// given key, return userid

	public static function key_UserID($key) {
		list($userID) = explode('_', $key);
		$correctKey = self::userID_Key($userID);
		if ($correctKey == $key) {return $userID;}
		else {return false;}
	}// end function





	####################################################################################
	 //Wipe the session keys
	 */
	public static function keyWipe()
	{
		// Don't change this line. Don't ask why it needs to be set to expire in a year to expire immidiately
		$success=setcookie('wD-Key', '', (time()-3600));
		libHTML::$footerScript[] = 'eraseCookie("wD-Key");';

		if ( isset($_COOKIE[session_name()]) )
		{
			libHTML::$footerScript[] = 'eraseCookie("'.session_name().'");';
			unset($_COOKIE[session_name()]);
			setcookie(session_name(), '', time()-3600);
			session_destroy();
		}

		return $success;
	}



	/*##################################################################################################3
	//Generate and set an authentication cookie
	// @param int $userID The authenticated user ID to provide a session key for
	 * @param bool $session True if the user should only log on for a session, false if the user should log on permeanently
	 */

	public static function keySet( $userID, $session, $path=false )
	{
		if( isset($_REQUEST['logoff']) )
			return;

		$key = self::userID_Key($userID);

		if ( $session )
			setcookie('wD-Key', $key );
		elseif ( $path )
			setcookie('wD-Key', $key, (time()+365*24*60*60), $path );
		else
			setcookie('wD-Key', $key, (time()+365*24*60*60));
	}

	/*###############################################################################
	 * Logon as a user with a key. Display a notice and terminate if there is
	 * a problem, otherwise return a $User object corresponding to the given
	 * key.
	 * Will also attempt to use legacy keys
	 *
	 * @param string $key The auth key (/legacy cookie)
	 * @param bool[optional] $session Should the user be logged on only for the session true/false
	 *
	 * @return User A user object
	 */
	static public function key_User( $key, $session = false )
	{
		global $DB;

		$userID = self::key_UserID($key);

		if ( ! $userID )
		{
			if( isset($_REQUEST['noRefresh']) )
			{
				// We have been sent back from the logoff script, and clearly not with a wiped key

				// Load some data that will give useful context in the trigger_error errorlog
				// which will occur below.
				if(isset($_COOKIE['wD-Key']) and $_COOKIE['wD-Key'])
					$cookieKey = $_COOKIE['wD-Key'];
				$user_agent = $_SERVER['HTTP_USER_AGENT'];
				$allCookies=print_r($_COOKIE,true);

				$success=self::keyWipe();

				// Make sure there's no refresh loop
				trigger_error(l_t("An invalid log-on cookie was given, but it seems an attempt to remove it has failed.")."<br /><br />".
					l_t("This error has been logged, please e-mail %s if the problem persists, or you can't log on.",Config::$adminEMail));
			}
			else
			{
				self::keyWipe();
				header('refresh: 3; url=logon.php?logoff=on');
				libHTML::error(l_t("An invalid log-on cookie was given, and it has been removed. ".
					"You are being redirected to the log-on page.")."<br /><br />".
					l_t("Inform an admin at %s if the problem persists, or you can't log on.",Config::$adminEMail));
			}

		}

		// This user ID is authenticated
		self::keySet($userID, $session);

		global $User;
		try
		{
			$User = new User($userID);
		}
		catch (Exception $e)
		{
			self::keyWipe();
			header('refresh: 3; url=logon.php?logoff=on');
			libHTML::error(l_t("You are using an invalid log on cookie, which has been wiped. Please try logging on again."));
		}

		$User->logon();

		return $User;
	}

}
?>
