<?php

defined('IN_CODE') or die('This script can not be run by itself.');

require_once(l_r('objects/notice.php'));
require_once(l_r('objects/basic/set.php'));

/**
 * Holds information on a user for display, or to manage certain user related functions such as logging
 * on, and preventing the same data being sent twice. Also processes user registration forms.
 *
 * TODO: I think much of this isn't used, or used rarely enough that it would be better placed elsewhere
 *
 * @package Base
 */
class User {
	public static function cacheDir($id)
	{
		return libCache::dirID('users',$id);
	}

	public static function wipeCache($id, $glob='*.*')
	{
		$dir=self::cacheDir($id);
		libCache::wipeDir($dir, $glob);
		file_put_contents($dir.'/index.html', '');
	}

	public function getSilences() {
		global $DB;

		$tabl = $DB->sql_tabl("SELECT
			silence.id as silenceID,
			silence.userID as silenceUserID,
			silence.postID as silencePostID,
			silence.moderatorUserID as silenceModeratorUserID,
			silence.enabled as silenceEnabled,
			silence.startTime as silenceStartTime,
			silence.length as silenceLength,
			silence.reason as silenceReason
		FROM wD_Silences silence
		WHERE silence.userID = ".$this->id."
		ORDER BY silence.startTime DESC");

		$silences = array();
		while( $record = $DB->tabl_hash($tabl) )
			$silences[] = new Silence($record);

		return $silences;
	}

	private $ActiveSilence;

	public function isSilenced() {
		if( !$this->silenceID )
			return false;

		$ActiveSilence = new Silence($this->silenceID);

		if( $ActiveSilence->isEnabled() ) {
			$this->ActiveSilence = $ActiveSilence;
			return true;
		}
		else
			return false;
	}
	public function getActiveSilence() {

		if( !$this->isSilenced() ) return null;
		else return $this->ActiveSilence;

	}

	/**
	* Silence ID; the ID of the last silence set to this user (may be expired / disabled since)
	* @var int/null
	*/
	public $silenceID;

	/**
	 * User ID
	 * @var int
	 */
	public $id;

	/**
	 * Username
	 * @var string
	 */
	public $username;

	/**
	 * MD5 (salted) password hex encoded
	 * @var string
	 */
	public $password;

	/**
	 * E-mail address
	 * @var string
	 */
	public $email;

	/**
	 * User type; an array of user-types, each set to true for is-a-member, false for is-not-a-member
	 * @var array
	 */
	public $type;

	/**
	 * Notification flags; an array of notification flags, each set to true if notification should be done.
	 * @var array
	 */
	public $notifications;

	/**
	 * The user-profile comment
	 * @var string
	 */
	public $comment;


	/**
	 * UNIX timestamp of join-date
	 *
	 * @var int
	 */
	public $timeJoined;

	/**
	 * UNIX timestamp from the time the last session ended
	 * @var int
	 */
	public $timeLastSessionEnded;

	/**
	 * Is this user online?
	 * @var bool
	 */
	public $online;

	/**
	 * Number of available points
	 * @var int
	 */
	public $points;

	public $lastMessageIDViewed;

	/**
	 * 'No' if the player can submit mod reports, 'Yes' if they are muted
	 * @var string
	 */
	public $muteReports;

	/**
	 * Give this user a supplement of points
	 *
	 * @param $userID The user ID
	 * @param $pointsWon The number of points won, if any
	 * @param $bet The amount bet into the game
	 * @param $gameID The game ID
	 * @param $points The number of points the user has saved
	 * @return int The amount awarded back
	 */
	public static function pointsSupplement($userID, $pointsWon, $bet, $gameID, $points)
	{
		global $DB;
		//10,23,105
		// If the user is winning points, and there is a chance they are winning fewer than they bet,
		// this function is needed to make sure no-one runs out of points completely, by making sure
		// all players have at least 100 points, including active bets in active games.

		$pointsInPlay = self::pointsInPlay($userID, $gameID); // Points in 'Playing'/'Left' games except $gameID

		if ( 100 <= ($pointsInPlay + $pointsWon + $points))
			return 0; // This member is doing fine, doesn't need topping up

		$supplement = (100 - ($pointsInPlay + $pointsWon + $points)); // The maximum supplement
		//19 = 100 - (_ + 10 + 71)

		// You can't be supplemented back more than you bet in
		if( $supplement > $bet ) $supplement = $bet;

		self::pointsTransfer($userID, 'Supplement', $supplement, $gameID);

		return $supplement;
	}

	public static function pointsTransfer($userID, $transferType, $points, $gameID='NULL', $memberID='NULL')
	{
		global $DB;

		assert('$points >= 0');

		// 'Won','Bet','Cancel','Supplement'
		if($transferType == 'Won')
		{
			// Won doesn't mean they won, this could be 0, it's just the transaction type

			/*
			 * It is expected that if they won less than they bet they have already been topped up the
			 * 100-minimum-points-supplement, and are now only being paid what they won from the game.
			 * This figure doesn't include any supplements they've already received.
			 */

			$DB->sql_put("UPDATE wD_Members SET pointsWon = ".$points." WHERE userID = ".$userID." AND gameID = ".$gameID);

		}

		if ( $transferType == 'Cancel' )
			$DB->sql_put("DELETE FROM wD_PointsTransactions
				WHERE userID = ".$userID." AND gameID = ".$gameID);
		else
			$DB->sql_put("INSERT INTO wD_PointsTransactions ( userID, type, points, gameID, memberID )
				VALUES ( ".$userID.", '".$transferType."', ".$points.", ".$gameID.", ".$memberID." )");

		if ( $transferType == 'Bet' )
		{
			$DB->sql_put("UPDATE wD_Users SET points = points - ".$points." WHERE id = ".$userID);
			$DB->sql_put("UPDATE wD_Games SET pot = pot + ".$points." WHERE id = ".$gameID);
			$DB->sql_put("UPDATE wD_Members SET bet = ".$points." WHERE id = ".$memberID);
		}
		elseif ( $transferType == 'Cancel' )
		{
			$DB->sql_put("UPDATE wD_Users SET points = points + ".$points." WHERE id = ".$userID);
			$DB->sql_put("UPDATE wD_Games SET pot = IF(pot > ".$points.",(pot - ".$points."),0) WHERE id = ".$gameID);
		}
		else
			$DB->sql_put("UPDATE wD_Users SET points = points + ".$points." WHERE id = ".$userID);
	}




	/**
	 * Find the ID of the user which has the given username, or return 0 if it doesn't exist
	 * *Does not filter input!*
	 *
	 * @param $username
	 * @return int
	 */
	public static function findUsername($username)
	{
		global $DB;

		list($id) = $DB->sql_row("SELECT id FROM wD_Users WHERE username='".$username."'");

		if ( isset($id) and $id )
			return $id;
		else
			return 0;
	}


	/**
	 * Initialize a user object
	 *
	 * @param int $id User ID
	 * @param string|bool[optional] $username Look the user up based on username instead of user ID
	 */
	function __construct($id,$authentication=false)
	{

			$this->id = intval($id);
			$this->load($this->id,$authentication);

	}

	/**
	 * Load the User object class fields. It is assumed that username is already escaped.
	 *
	 * @param string|bool[optional] If the username is given it is being used instead of ID to load the User *Not filtered*
	 */
	function load($userid,$authentication)
	{
		global $DBi;
		global $aes_encrypt_key;

		$fieldlist = ' ';
		$fieldlist .= 'u.id, ';
		$fieldlist .= 'u.username, ';
		$fieldlist .= 'u.SecurityKey, ';
		$fieldlist .= 'AES_DECRYPT(u.email,?) AS email, ';
		$fieldlist .= 'u.type, ';
		$fieldlist .= 'u.SystemUser, ';
		$fieldlist .= 'u.GuestUser, ';
		$fieldlist .= 'u.ModeratorUser, ';
		$fieldlist .= 'u.AdminUser, ';
		$fieldlist .= 'u.comment, ';
		$fieldlist .= 'u.timeJoined, ';
		$fieldlist .= 'u.timeLastSessionEnded, ';
		$fieldlist .= 'u.points, ';
		$fieldlist .= 'u.lastMessageIDViewed, ';
		$fieldlist .= 'u.muteReports, ';
		$fieldlist .= 'u.silenceID, ';
		$fieldlist .= 'u.notifications, ';
		$fieldlist .= 'IF(s.userID IS NULL,0,1) as online ';

		$whereclause = "WHERE u.id=? ";

		$query = "SELECT ".$fieldlist." FROM wD_Users u	LEFT JOIN wD_Sessions s ON ( u.id = s.userID ) ".$whereclause;


		$row=$DBi->fetch_row("$query",false,array($aes_encrypt_key,$userid));

		if (!$row){// this is a guest user
			$whereclause = "WHERE u.GuestUser=1";
			$query = "SELECT ".$fieldlist." FROM wD_Users u	LEFT JOIN wD_Sessions s ON ( u.id = s.userID ) ".$whereclause;
			$row=$DBi->fetch_row("$query",false,array($aes_encrypt_key,$userid));
		}// end if if (!$row)

		foreach( $row as $name=>$value )
		{
			$this->{$name} = $value;
			if ($authentication) {
				$_SESSION['user_data'][$name]=$value;
				$query="UPDATE wD_Users SET LastVisitDateTime=NOW() WHERE id=?";
				$result=$DBi->query("$query",array($userid));
			}// end if $authentication

		}

		###############################################################
		##############################################################
		// Convert an array of types this user has into an array of true/false indexed by type
		// eventually this can be removed when type becomes a single value instead of an array -- jimbursch

		$types = array();

		$types['System'] = false;
		$types['Guest'] = false;
		$types['User'] = true;
		$types['Moderator'] = false;
		$types['Admin'] = false;



		if ($_SESSION['user_data']['AdminUser']) {$types['Admin'] = true;$types['Moderator'] = true;}
		if ($_SESSION['user_data']['GuestUser']) {$types['Guest'] = true;$types['User'] = false;}
		if ($_SESSION['user_data']['SystemUser']) {$types['System'] = true;$types['User'] = false;}

		$this->type = $types;

		$this->notifications=new setUserNotifications($this->notifications);

		$this->online = (bool) $this->online;
	}

	/**
	 * Return a profile link for this user
	 * @param bool[optional] $welcome If true this profile link is tweaked to be used as the Welcome link
	 * @return string Profile link HTML
	 */
	function profile_link($welcome = false)
	{
		$buffer = '';

		if ( $this->type['User'] )
		{
			$buffer .= '<a href="./profile.php?userID='.$this->id.'"';

			$buffer.='>'.$this->username;

			if ( !$welcome and $this->online )
				$buffer.= libHTML::loggedOn($this->id);

			$buffer.=' ('.$this->points.libHTML::points().$this->typeIcon($this->type).')</a>';
		}
		else
		{
			$buffer .= '<em>'.$this->username.'</em>';
		}

		return $buffer;
	}

	static function typeIcon($type) {
		// This must take either a list as it comes from a SQL query, or a built-in $this->type['Admin'] style array
		if( is_array($type) ) {
			$types=array();

			foreach($type as $n=>$v)
				if($v) $types[]=$n;

			$type = implode(',',$types);
		}

		$buf='';

		if( strstr($type,'Moderator') )
			$buf .= ' <img src="'.l_s('images/icons/mod.png').'" alt="'.l_t('Mod').'" title="'.l_t('Moderator/Admin').'" />';
		elseif(strstr($type,'Banned') )
			$buf .= ' <img src="'.l_s('images/icons/cross.png').'" alt="X" title="'.l_t('Banned').'" />';

		if( strstr($type,'DonatorPlatinum') )
			$buf .= libHTML::platinum();
		elseif( strstr($type,'DonatorGold') )
			$buf .= libHTML::gold();
		elseif( strstr($type,'DonatorSilver') )
			$buf .= libHTML::silver();
		elseif( strstr($type,'DonatorBronze') )
			$buf .= libHTML::bronze();

		return $buf;
	}

	function sendNotice($keep, $private, $message)
	{
		global $DB;

		$message=$DB->escape($message,true);

		notice::send($this->id, 1, 'User', $keep,$private, $message, 'GameMaster');
	}

	function sendPM(User $FromUser, $message)
	{
		$message = htmlentities( $message, ENT_NOQUOTES, 'UTF-8');
		require_once(l_r('lib/message.php'));
		$message = message::linkify($message);

		if( $this->isUserMuted($FromUser->id) )
		{
			notice::send($FromUser->id, $this->id, 'PM', 'No', 'Yes',
				l_t('Could not deliver message, user has muted you.'), l_t('To:').' '.$this->username,
				$this->id);
		}
		else
		{
			notice::send($this->id, $FromUser->id, 'PM', 'Yes', 'Yes',
				$message, $FromUser->username, $FromUser->id);

			$this->setNotification('PrivateMessage');

			notice::send($FromUser->id, $this->id, 'PM', 'No', 'Yes',
				l_t('You sent:').' <em>'.$message.'</em>', l_t('To:').' '.$this->username,
				$this->id);
		}
	}

	/**
	 * This will set a notification value in both the object and wd_users table if not already set.
	 * @param notification notification value to set, must be 'PrivateMessage', 'GameMessage', 'Unfinalized', or 'GameUpdate'.
	 **/
	function setNotification($notification)
	{
		global $DB;

		$this->notifications->$notification = true;
		if ($this->notifications->updated)
		{
			$DB->sql_put("UPDATE wD_Users SET notifications = CONCAT_WS(',',notifications,'".$notification."') WHERE id = ".$this->id);
			$this->notifications->updated = false;
		}
	}

        /**
	 * This will clear a notification value in both the object and the wd_users table if not already cleared.
	 * @param notification notification value to clear, must be 'PrivateMessage', 'GameMessage', 'Unfinalized', or 'GameUpdate'.
	 **/
	function clearNotification($notification)
	{
		global $DB;

		$this->notifications->$notification = false;
		if ($this->notifications->updated)
		{
			$DB->sql_put("UPDATE wD_Users SET notifications = REPLACE(notifications,'".$notification."','') WHERE id = ".$this->id);
			$this->notifications->updated = false;
		}
	}

	/**
	 * The time this user joined
	 * @return string Date joined
	 */
	function timeJoinedtxt()
	{
		return libTime::text($this->timeJoined);
	}

	/**
	 * Log-on, create/update a session record, and take information for user access logging for meta-gamers
	 */
	function logon()
	{
		global $DB;

		session_name('wD_Sess_User-'.$this->id);

		/*if( $this->type['User'] )
			session_cache_limiter('private_no_expire');
		else
			session_cache_limiter('public');*/

		//session_start(); moved to header.php

		// Non-users can't get banned
		if( $this->type['Guest'] ) return;

		if ( isset($_SERVER['HTTP_USER_AGENT']) )
			$userAgentHash = substr(md5($_SERVER['HTTP_USER_AGENT']),0,4);
		else
			$userAgentHash = '0000';

		if ( ! isset($_COOKIE['wD_Code']) or intval($_COOKIE['wD_Code']) == 0 or intval($_COOKIE['wD_Code']) == 1 )
		{
			// Making this larger than 2^31 makes it negative..
			$cookieCode = rand(2, 2000000000);
			setcookie('wD_Code', $cookieCode,time()+365*7*24*60*60);
		}
		else
		{
			$cookieCode = (int) $_COOKIE['wD_Code'];
		}

		if($this->type['Banned'])
			libHTML::notice(l_t('Banned'), l_t('You have been banned from this server. If you think there has been a mistake contact the moderator team at %s , and if you still aren\'t satisfied contact the admin at %s (with details of what happened).',Config::$modEMail, Config::$adminEMail));

		/*
		$bans=array();
		$tabl = $DB->sql_tabl("SELECT numberType, number, userID FROM wD_BannedNumbers
			WHERE ( number = INET_ATON('".$_SERVER['REMOTE_ADDR']."') AND numberType='IP')
				OR ( number = ".$cookieCode." AND numberType='CookieCode')
				OR ( userID=".$this->id.")");
		while(list($banType,$banNum)=$DB->tabl_row($tabl))
			$bans[$banType]=$banNum;

		if($this->type['Banned'])
		{
			//if( isset($bans['IP']) and $cookieCode!=$bans['CookieCode'] )
				//setcookie('wD_Code', $bans['CookieCode'],time()+365*7*24*60*60);

			if(!isset($bans['IP']) || ip2long($_SERVER['REMOTE_ADDR'])!=$bans['IP'])
				self::banIP(ip2long($_SERVER['REMOTE_ADDR']), $this->id);

			libHTML::notice('Banned', 'You have been banned from this server. If you think there has been
					a mistake contact '.Config::$adminEMail.' .');
		}
		elseif( isset($bans['IP']) )
		{
			self::banUser($this->id,"You share an IP with a banned user account.", $_SERVER['REMOTE_ADDR']);
			libHTML::notice('Banned', 'You have been banned from this server. If you think there has been
				a mistake contact '.Config::$adminEMail.' .');
		}*/

		$DB->sql_put("INSERT INTO wD_Sessions (userID, lastRequest, hits, ip, userAgent, cookieCode)
					VALUES (".$this->id.",CURRENT_TIMESTAMP,1, INET_ATON('".$_SERVER['REMOTE_ADDR']."'),
							UNHEX('".$userAgentHash."'), ".$cookieCode." )
					ON DUPLICATE KEY UPDATE hits=hits+1");

		$this->online = true;
	}

	public static function banIP($ip, $userID=-1)
	{
		global $DB;

		if($userID<=0) $userID="NULL";

		$DB->sql_put("INSERT IGNORE INTO wD_BannedNumbers (number,numberType,userID,hasResponded)
				VALUES (INET_ATON('".$ip."'),'IP',".$userID.",'No')");
	}

	public static function banUser($userID, $reason=null, $ip=0)
	{
		global $DB;

		if( $reason )
		{
			$reason=$DB->msg_escape($reason);
			$comment = "comment='".$reason."', ";
		}
		else
			$comment = '';

		$DB->sql_put("UPDATE wD_Users SET ".$comment." type='Banned', points=0 WHERE id = ".$userID);

		if($ip)
			self::banIP($ip, $userID);
	}

	public function rankingDetails()
	{
		global $DB, $Misc;

		$rankingDetails = array();

		list($rankingDetails['position']) = $DB->sql_row("SELECT COUNT(id)+1
			FROM wD_Users WHERE points > ".$this->points);

		list($rankingDetails['worth']) = $DB->sql_row(
			"SELECT SUM(bet) FROM wD_Members WHERE userID = ".$this->id." AND status = 'Playing'");

		$rankingDetails['worth'] += $this->points;

		$tabl = $DB->sql_tabl(
				"SELECT COUNT(id), status FROM wD_Members WHERE userID = ".$this->id." GROUP BY status"
			);

		$rankingDetails['stats'] = array();
		while ( list($number, $status) = $DB->tabl_row($tabl) )
		{
			$rankingDetails['stats'][$status] = $number;
		}

		$tabl = $DB->sql_tabl( "SELECT COUNT(m.id), m.status, SUM(m.bet) FROM wD_Members AS m
					INNER JOIN wD_Games AS g ON m.gameID = g.id
					WHERE m.userID = ".$this->id."
						AND g.phase != 'Finished'
						AND g.anon = 'Yes'
					GROUP BY status");
		$points=0;
		while ( list($number, $status, $bets) = $DB->tabl_row($tabl) )
		{
			$points += $bets;
			$rankingDetails['anon'][$status] = $number;
		}
		$rankingDetails['anon']['points'] = $points;

		list($rankingDetails['takenOver']) = $DB->sql_row(
			"SELECT COUNT(c.userID) FROM wD_CivilDisorders c
			INNER JOIN wD_Games g ON ( g.id = c.gameID )
			LEFT JOIN wD_Members m ON ( c.gameID = m.gameID and c.userID = ".$this->id." )
			WHERE c.userID = ".$this->id." AND m.userID IS NULL"
			);


		$rankingDetails['rankingPlayers'] = $Misc->RankingPlayers;

		// Prevent division by 0 when server is new
		$rankingPlayers = ( $rankingDetails['rankingPlayers'] == 0 ? 1 : $rankingDetails['rankingPlayers'] );

		// Calculate the percentile of the player. Smaller is better.
		$rankingDetails['percentile'] = ceil(100.0*$rankingDetails['position'] / $rankingPlayers);

		$rankingDetails['rank'] = 'Political puppet';

		$ratings = array('<strong>Diplomat</strong>' => 5,
						'Mastermind' => 10,
						'Pro' => 20,
						'Experienced' => 50,
						'Member' => 90,
						'Casual player' => 100 );

		foreach($ratings as $name=>$limit)
		{
			if ( $rankingDetails['percentile'] <= $limit )
			{
				$rankingDetails['rank'] = l_t($name);
				break;
			}
		}

		return $rankingDetails;
	}

	static function pointsInPlay($userID, $excludeGameID=false)
	{
		global $DB;

		list($pointsInPlay) = $DB->sql_row(
			"SELECT SUM(m.bet) FROM wD_Members m ".
				($excludeGameID?"INNER JOIN wD_Games g ON ( m.gameID = g.id ) ":'')."
			WHERE (m.userID = ".$userID.") ".
				($excludeGameID?"AND ( NOT m.gameID = ".$excludeGameID." ) ":"")."
				AND ( m.status = 'Playing' OR m.status = 'Left' )
			GROUP BY m.userID");

		if ( !isset($pointsInPlay) || !$pointsInPlay )
			return 0;
		else
			return $pointsInPlay;
	}

	public function getMuteUsers() {
		global $DB;

		static $muteUsers;
		if( isset($muteUsers) ) return $muteUsers;
		$muteUsers = array();

		$tabl = $DB->sql_tabl("SELECT muteUserID FROM wD_MuteUser WHERE userID=".$this->id);
		while(list($muteUserID) = $DB->tabl_row($tabl))
			$muteUsers[] = $muteUserID;

		return $muteUsers;
	}
	public function isUserMuted($muteUserID) {
		return in_array($muteUserID,$this->getMuteUsers());
	}
	public function toggleUserMute($muteUserID) {
		global $DB;
		$muteUserID = (int)$muteUserID;
		if( $this->isUserMuted($muteUserID) )
			$DB->sql_put("DELETE FROM wD_MuteUser WHERE userID=".$this->id." AND muteUserID=".$muteUserID);
		else
			$DB->sql_put("INSERT INTO wD_MuteUser (userID, muteUserID) VALUES (".$this->id.",".$muteUserID.")");
	}
	public function getMuteCountries($gameID=-1) {
		global $DB;
		$gameID = (int) $gameID;

		static $muteCountries;
		if( !isset($muteCountries) ) $muteCountries = array();
		if( isset($muteCountries[$gameID]) ) return $muteCountries[$gameID];

		$muteCountries[$gameID] = array();
		$tabl = $DB->sql_tabl("SELECT m.gameID, m.muteCountryID
			FROM wD_MuteCountry m INNER JOIN wD_Games g ON g.id = m.gameID
			WHERE m.userID=".$this->id.($gameID>0?" AND m.gameID=".$gameID:''));

		while(list($muteGameID,$muteCountryID) = $DB->tabl_row($tabl))
		{
			if( $gameID<0 ) // No game ID given, we are collecting all game IDs
				$muteCountries[$gameID][] = array($muteGameID, $muteCountryID);
			else // Game ID given, this is for just one game ID
				$muteCountries[$gameID][] = $muteCountryID;
		}

		return $muteCountries[$gameID];
	}
	public function getLikeMessages() {
		global $DB;

		static $likeMessages;
		if( !isset($likeMessages) ) $likeMessages = array();
		else return $likeMessages;

		$tabl = $DB->sql_tabl("SELECT likeMessageID FROM wD_LikePost WHERE userID=".$this->id);

		while(list($likeMessageID) = $DB->tabl_row($tabl))
			$likeMessages[] = $likeMessageID;

		return $likeMessages;
	}
	public function likeMessageToggleLink($messageID, $fromUserID=-1) {

		if( $this->type['User'] && $this->id != $fromUserID && !in_array($messageID, $this->getLikeMessages()))
			return '<a id="likeMessageToggleLink'.$messageID.'"
			href="#" title="'.l_t('Give a mark of approval for this post').'" class="light likeMessageToggleLink" '.
			'onclick="likeMessageToggle('.$this->id.','.$messageID.',\''.libAuth::likeToggleToken($this->id, $messageID).'\'); '.
			'return false;">'.
			'+1</a>';
		else return '';
	}
	public function getMuteThreads($refresh=false) {
		global $DB;

		static $muteThreads;
		if( $refresh || !isset($muteThreads) ) $muteThreads = array();
		else return $muteThreads;

		$tabl = $DB->sql_tabl("SELECT muteThreadID FROM wD_MuteThread WHERE userID=".$this->id);

		while(list($muteThreadID) = $DB->tabl_row($tabl))
			$muteThreads[] = $muteThreadID;

		return $muteThreads;
	}

	public function isThreadMuted($threadID) {
		return in_array($threadID,$this->getMuteThreads($threadID));
	}
	public function toggleThreadMute($threadID) {
		global $DB;

		if( $this->isThreadMuted($threadID))
			$DB->sql_put("DELETE FROM wD_MuteThread WHERE userID = ".$this->id." AND muteThreadID=".$threadID);
		else
			$DB->sql_put("INSERT INTO wD_MuteThread (userID, muteThreadID) VALUES (".$this->id.", ".$threadID.")");

		$this->getMuteThreads(true);
	}
	public function isCountryMuted($gameID, $muteCountryID) {
		return in_array($muteCountryID,$this->getMuteCountries($gameID));
	}
	public function toggleCountryMute($gameID,$muteCountryID) {
		global $DB;
		$gameID = (int)$gameID;
		$muteCountryID = (int)$muteCountryID;

		if( $this->isCountryMuted($gameID,$muteCountryID) )
			$DB->sql_put("DELETE FROM wD_MuteCountry WHERE userID=".$this->id." AND gameID=".$gameID." AND muteCountryID=".$muteCountryID);
		else
			$DB->sql_put("INSERT INTO wD_MuteCountry (userID, gameID, muteCountryID) VALUES (".$this->id.",".$gameID.",".$muteCountryID.")");

	}
}
?>
