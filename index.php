<?php


/**
 * @package Base
 */
require_once('header.php');

require_once(l_r('lib/message.php'));

require_once(l_r('objects/game.php'));

require_once(l_r('gamepanel/gamehome.php'));

/*
 * A field
 *
 * add(field, index)
 * compare(field1, field2) -> 1 if aligned, 0 if not
 *
 */
libHTML::starthtml(l_t('Home'));

if( !isset($_SESSION['lastSeenHome']) || $_SESSION['lastSeenHome'] < $User->timeLastSessionEnded )
{
	$_SESSION['lastSeenHome']=$User->timeLastSessionEnded;
}

class libHome
{
	static public function getType($type=false, $limit=5)
	{
		global $DB, $User;

		$notices=array();

		$tabl=$DB->sql_tabl("SELECT *
			FROM wD_Notices WHERE toUserID=".$User->id.($type ? " AND type='".$type."'" : '')."
			ORDER BY timeSent DESC ".($limit?'LIMIT '.$limit:''));
		while($hash=$DB->tabl_hash($tabl))
		{
			$notices[] = new notice($hash);
		}

		return $notices;
	}
	public static function PMs()
	{
		$pms = self::getType('PM', 10);
		$buf = '';
		foreach($pms as $pm)
		{
			$buf .= $pm->html();
		}
		return $buf;
	}
	public static function Game()
	{
		global $User;

		$pms = self::getType('Game');

		if(!count($pms))
		{
			print '<div class="hr"></div>';
			print '<p class="notice">'.l_t('No game notices found.').'</p>';
			return;
		}

		print '<div class="hr"></div>';

		foreach($pms as $pm)
		{
			print $pm->viewedSplitter();

			print $pm->html();
		}
	}
	public static function NoticePMs()
	{
		global $User;

		try
		{
			$message=notice::sendPMs();
		}
		catch(Exception $e)
		{
			$message=$e->getMessage();
		}

		if ( $message )
			print '<p class="notice">'.$message.'</p>';

		$pms = self::getType('PM');

		if(!count($pms))
		{
			print '<div class="hr"></div>';
			print '<p class="notice">'.l_t('No private messages found; you can send them to other people on their profile page.').'</p>';
			return;
		}

		print '<div class="hr"></div>';

		foreach($pms as $pm)
		{
			print $pm->viewedSplitter();

			print $pm->html();
		}
	}
	public static function NoticeGame()
	{
		global $User;

		$pms = self::getType('Game');

		if(!count($pms))
		{
			print '<div class="hr"></div>';
			print '<p class="notice">'.l_t('No game notices found; try browsing the <a href="gamelistings.php">game listings</a>, '.
				'or <a href="gamecreate.php">create your own</a> game.').'</p>';
			return;
		}

		print '<div class="hr"></div>';

		foreach($pms as $pm)
		{
			print $pm->viewedSplitter();

			print $pm->html();
		}
	}
	public static function Notice()
	{
		global $User;

		$pms = self::getType();

		if(!count($pms))
		{
			print '<div class="hr"></div>';
			print '<p class="notice">'.l_t('No notices found.').'</p>';
			return;
		}

		print '<div class="hr"></div>';

		foreach($pms as $pm)
		{
			print $pm->viewedSplitter();

			print $pm->html();
		}
	}
	static function topUsers()
	{
		global $DB;
		$rows=array();
		$tabl = $DB->sql_tabl("SELECT id, username, points FROM wD_Users
						order BY points DESC LIMIT 10");
		$i=1;
		while(list($userID,$username,$points)=$DB->tabl_row($tabl))
		{
			$rows[] = '#'.$i.': <a href="profile.php?userID='.$userID.'">'.$username.'</a> ('.$points.libHTML::points().')';
			$i++;
		}
		return $rows;
	}
	static function statsGlobalGame()
	{
		global $Misc;
		$stats=array(
			'Starting'=>$Misc->GamesNew,
			'Joinable'=>$Misc->GamesOpen,
			'Active'=>$Misc->GamesActive,
			'Finished'=>$Misc->GamesFinished
		);

		return $stats;
	}
	static function statsGlobalUser()
	{
		global $Misc;
		$stats=array(
			'Logged on'=>$Misc->OnlinePlayers,
			'Playing'=>$Misc->ActivePlayers,
			'Registered'=>$Misc->TotalPlayers
		);

		if( $stats['Logged on'] <= 1 ) unset($stats['Logged on']);
		if( $stats['Playing'] < 25 ) unset($stats['Playing']);

		return $stats;
	}


	static public function gameNotifyBlock ()
	{
		global $User, $DB;

		$tabl=$DB->sql_tabl("SELECT g.* FROM wD_Games g
			INNER JOIN wD_Members m ON ( m.userID = ".$User->id." AND m.gameID = g.id )
			WHERE NOT g.phase = 'Finished'
			ORDER BY g.processTime ASC");
		$buf = '';

		$count=0;
		while($game=$DB->tabl_hash($tabl))
		{
			$count++;
			$Variant=libVariant::loadFromVariantID($game['variantID']);
			$Game=$Variant->panelGameHome($game);

			$buf .= '<div class="hr"></div>';
			$buf .= $Game->summary();
		}

		if($count==0)
		{
			$buf .= '<div class="hr"></div>';
			$buf .= '<div><p class="notice">'.l_t('You\'re not joined to any games!').'<br />
				'.l_t('Access the <a href="gamelistings.php?tab=">Games</a> '.
				'link above to find games you can join, or start a '.
				'<a href="gamecreate.php">New game</a> yourself.</a>').'</p></div>';
		}
		elseif ( $count == 1 && $User->points > 5 )
		{
			$buf .= '<div class="hr"></div>';
			$buf .= '<div><p class="notice">'.l_t('You can join as many games as you '.
			'have the points to join.').' </a></p></div>';
		}
		return $buf;
	}

	static function forumNew() {
		// Select by id, prints replies and new threads
		global $DB, $Misc;

		$tabl = $DB->sql_tabl("
			SELECT m.id as postID, t.id as threadID, m.type, m.timeSent, IF(t.replies IS NULL,m.replies,t.replies) as replies,
				IF(t.subject IS NULL,m.subject,t.subject) as subject,
				u.id as userID, u.username, u.points, IF(s.userID IS NULL,0,1) as online, u.type as userType,
				SUBSTRING(m.message,1,100) as message, m.latestReplySent, t.fromUserID as threadStarterUserID
			FROM wD_ForumMessages m
			INNER JOIN wD_Users u ON ( m.fromUserID = u.id )
			LEFT JOIN wD_Sessions s ON ( m.fromUserID = s.userID )
			LEFT JOIN wD_ForumMessages t ON ( m.toID = t.id AND t.type = 'ThreadStart' AND m.type = 'ThreadReply' )
			ORDER BY m.timeSent DESC
			LIMIT 50");
		$oldThreads=0;
		$threadCount=0;

		$threadIDs = array();
		$threads = array();

		while(list(
				$postID, $threadID, $type, $timeSent, $replies, $subject,
				$userID, $username, $points, $online, $userType, $message, $latestReplySent,$threadStarterUserID
			) = $DB->tabl_row($tabl))
		{
			$threadCount++;

			if( $threadID )
				$iconMessage=libHTML::forumMessage($threadID, $postID);
			else
				$iconMessage=libHTML::forumMessage($postID, $postID);

			if ( $type == 'ThreadStart' ) $threadID = $postID;

			if( !isset($threads[$threadID]) )
			{
				if(strlen($subject)>30) $subject = substr($subject,0,40).'...';
				$threadIDs[] = $threadID;
				$threads[$threadID] = array('subject'=>$subject, 'replies'=>$replies,
					'posts'=>array(),'threadStarterUserID'=>$threadStarterUserID);
			}

			$message=Message::refilterHTML($message);

			if( strlen($message) >= 50 ) $message = substr($message,0,50).'...';

			$message = '<div class="message-contents threadID'.$threadID.'" fromUserID="'.$userID.'">'.$message.'</div>';

			$threads[$threadID]['posts'][] = array(
				'iconMessage'=>$iconMessage,'userID'=>$userID, 'username'=>$username,
				'message'=>$message,'points'=>$points, 'online'=>$online, 'userType'=>$userType, 'timeSent'=>$timeSent
			);
		}

		$buf = '';
		$threadCount=0;
		foreach($threadIDs as $threadID)
		{
			$data = $threads[$threadID];

			$buf .= '<div class="hr userID'.$threads[$threadID]['threadStarterUserID'].' threadID'.$threadID.'"></div>';

			$buf .= '<div class="homeForumGroup homeForumAlt'.($threadCount%2 + 1).
				' userID'.$threads[$threadID]['threadStarterUserID'].' threadID'.$threadID.'">
				<div class="homeForumSubject homeForumTopBorder">'.libHTML::forumParticipated($threadID).' '.$data['subject'].'</div> ';

			if( count($data['posts']) < $data['replies'])
			{
				$buf .= '<div class="homeForumPost homeForumMessage homeForumPostAlt'.libHTML::alternate().' ">

				...</div>';
			}


			$data['posts'] = array_reverse($data['posts']);
			foreach($data['posts'] as $post)
			{
				$buf .= '<div class="homeForumPost homeForumPostAlt'.libHTML::alternate().' userID'.$post['userID'].'">


					<div class="homeForumPostTime">'.libTime::text($post['timeSent']).' '.$post['iconMessage'].'</div>
					<a href="profile.php?userID='.$post['userID'].'" class="light">'.$post['username'].'</a>
						'.libHTML::loggedOn($post['userID']) . ' ('.$post['points'].libHTML::points().
						User::typeIcon($post['userType']).')

					<div style="clear:both"></div>
					<div class="homeForumMessage">'.$post['message'].'</div>
					</div>';

			}

			$buf .= '<div class="homeForumLink">
					<div class="homeForumReplies">'.l_t('%s replies','<strong>'.$data['replies'].'</strong>').'</div>
					<a href="forum.php?threadID='.$threadID.'#'.$threadID.'">'.l_t('Open').'</a>
					</div>
					</div>';
		}

		if( $buf )
		{
			return $buf;
		}
		else
		{
			return '<div class="homeNoActivity">'.l_t('No forum posts found, why not '.
				'<a href="forum.php?postboxopen=1#postbox" class="light">start one</a>?');
		}
	}


	static function forumBlock()
	{
		$buf = '<div class="homeHeader">'.l_t('Forum').'</div>';

		$forumNew=libHome::forumNew();
		$buf .=  '<table><tr><td>'.implode('</td></tr><tr><td>',$forumNew).'</td></tr></table>';
		return $buf;
	}
}

if( !$User->type['User'] )
{
	print libHTML::pageTitle(l_t('Welcome to BitDip'),l_t('A game of grand strategy played for bitcoin.'));
	//print '<div class="content">';
	?>

	<p class="welcome">
	<?php
		$welcomtext='';
		$welcomtext.='<p><b>SPECIAL NOTICE: BitDip is an open source web development project in it\'s very early stages. Jim Bursch is the lead developer and he can be found on GitHub: <a href="https://github.com/jimbursch">https://github.com/jimbursch</a>.</b></p>';

		$welcomtext.='<p><b>What is Bit?</b><br />A bit is one/millionth of a <a href="https://en.wikipedia.org/wiki/Bitcoin">bitcoin</a>, which is a digital currency or money.</p>';

		$welcomtext.='<p><b>What is Dip?</b><br />Dip is a game of grand strategy forked from the open source <a href="http://webdiplomacy.net">webDiplomacy</a> project.</p>';

		$welcomtext.='<p><b>What is BitDip?</b><br />BitDip is a game of grand strategy combined with a system of finance denominated in Bitcoin.</p>';

		$welcomtext.='<p><b>How do you play the game?</b><br />The game mechanics of BitDip are essentially the same as the game mechanics of webDiplomacy using two map variants: modern Europe and the WWIV global map.</p><p>The following are rule variants from classic webDiplomacy:</p><ul><li>Players can enter a game at the beginning by purchasing neutral supply centers. Funds from the purchase of neutral supply centers go into the game account. At the end of the game, funds are divided among surviving players, per supply center held at the time of liquidation.</li><li>Game play begins when all neutral supply centers have been purchased.</li><li>Games end when all surviving players vote to liquidate the game and divide up the game funds.</li><li>A player can enter a game at any time by purchasing some or all of the supply centers of another player. This is a transaction strictly between players and does not affect the game funds.</li><li>The only way to exit a game before the end is to sell out to another player.</li><li>There are no "countries" in BitDip. There are only players who own/occupy territories with supply centers.</li><li>The buying and selling of supply centers takes place at the end of the build phase.</li><li>All supply centers may build units</li></ul>';

		$welcomtext.='<p><b>Is it possible for a game to never end?</b><br />Yes, a game does not end until all surviving players vote to liquidate. Theoretically, a game can go on forever with old players selling out and new players buying in.</p>';

		$welcomtext.='<p><b>What are BitDip guilds?</b><br />Guilds are organized groups of players who help each other play the game. All players are encouraged to join a guild for assistance, advice, support, and to protect themselves from other guilds.</p>';

		$welcomtext.='<p><b>Is meta-gaming allowed?</b><br />Meta-gaming occurs when players communicate outside a game to coordinate in-game actions. Since it is impossible to enforce a rule against meta-gaming, it is allowed and players are welcome to do their own meta-gaming (such as joining a guild).</p>';

		$welcomtext.='<p><b>Is multi-accounting allowed?</b><br />Multi-accounting (creating and playing with more than one account) is allowed for the same reason as meta-gaming. However, we don\'t encourage the practice</p>';

		$welcomtext.='<p><b>How is BitDip different from classic Diplomacy?</b><br />The classic game of <a href="https://en.wikipedia.org/wiki/Diplomacy_%28game%29">Diplomacy</a> is a board game that was invented in the 1950\'s and is currently owned by a subsidiary of Hasbro called Wizards of the Coast. Soon after the game\'s invention and commercialization, the player community developed the game into a sophisticated hobby that went far beyond the face-to-face boardgame. Diplomacy was the first game to be extensively played by mail, then by email, and now the game is played online around the world. Thanks to the dedication of the open source developers of webDiplomacy (formerly phpDiplomacy), Diplomacy the hobby (not the boardgame) can be played any time, any where, with any player who can connect to the Web.</p>';

		$welcomtext.='<p>BitDip is forked from webDiplomacy but it is not the same thing as webDiplomacy and it is certainly nothing like the boardgame.</p><p>The goal in BitDip is not to "win" a game, but rather to increase wealth by controlling more supply centers and buying/selling supply centers profitably. A player enters a game by purchasing supply centers and exits a game by selling supply centers. The only way a game ends is if all the players agree to liquidate their supply centers, which is to cash-in the supply centers at par value, which was set when the game was created. Otherwise, the only way out of a game is to either be eliminated or sell out to another player.';


		$welcomtext.='<p><b>How it works:</b></p>';
		$welcomtext.='<p>When a game is created, a par value is set for every supply center (e.g. 100 bits). To enter the game, a player has to purchase neutral supply centers at that par value. Let\'s say player Alpha enters the game by purchasing 3 supply centers. The purchase price of those centers (300 bits) goes into the game account, which sets the liquidation value of the game. At this point if Alpha wants to get out of the game and get his money back, he has to move around the board, capture all the supply centers, and liquidate the game. But rather than do that, Alpha waits for another player, Bravo, to enter the game. Bravo purchases 3 supply centers, which increases the liquidation value of the game to 600 bits. Now, if Alpha moves around the board, captures all the supply centers (including those held by Bravo), then liquidates the game, he will make 600 bits, a nice return on his 300 bit investment.</p>';
		$welcomtext.='<p>Let\'s say for the sake of this illustration there are 10 supply centers on the board (there are 34 in classic Diplomacy). If 10 players enter the game, then the liquidation value of the game will be 1000 bits at 100 bits per supply center. Let\'s say through the course of play, Alpha and Bravo succeed in eliminating the other players and they each now hold 5 supply centers. They have profitted handsomely from their skilled play. If they are both satisfied, they can agree to liquidate the board and cash out (500 bits each). However, let\'s say Alpha doesn\'t want to liquidate. He wants to keep playing and try to win more. They cannnot liquidate unless they both agree, so if Bravo wants out, he has to find another way to exit the game. He can do so by selling his supply centers to another player, and he can offer an incentive by aggreeing to sell his 5 centers below par. Let\'s say Charlie is willing to buy the 5 centers for 400 bits. Bravo gets out of the game profitably -- he spent 100 bits to get in, and made 300 more when he sold out. Now if Charlie continues to play with Alpha, and let\'s say nothing changes and they both decide to liquidate, Charlie will make 100 bits (500 at liqudation minus the 400 he paid Bravo) and Alpha makes 400 bits (500 at liquidation minus 100 he paid to enter the game).';

		print l_t($welcomtext);

	print '</p>';
	pring '</div>';


}
elseif( isset($_REQUEST['notices']) )
{
	$User->clearNotification('PrivateMessage');

	print '<div class="content"><a href="index.php" class="light">&lt; '.l_t('Back').'</a></div>';

	print '<div class="content-bare content-home-header">';
	print '<table class="homeTable"><tr>';

	notice::$noticesPage=true;

	print '<td class="homeNoticesPMs">';
	print '<div class="homeHeader">'.l_t('Private messages').'</a></div>';
	print libHome::NoticePMs();
	print '</td>';

	print '<td class="homeSplit"></td>';

	print '<td class="homeNoticesGame">';
	print '<div class="homeHeader">'.l_t('Game messages').'</a></div>';
	print libHome::NoticeGame();
	print '</td>';

	print '</tr></table>';
	print '</div>';
	print '</div>';
}
else
{
	/*
	print '<div class="content-bare content-home-header">';
	print '<div class="boardHeader">blabla</div>';
	print '</div>';
	*/
	print '<div class="content-bare content-home-header">';// content-follow-on">';

	print '<table class="homeTable"><tr>';

	print '<td class="homeMessages">';

	print '<div class="homeHeader">'.l_t('Forum').' <a href="forum.php">'.libHTML::link().'</a></div>';
	if( file_exists(libCache::dirName('forum').'/home-forum.html') )
		print file_get_contents(libCache::dirName('forum').'/home-forum.html');
	else
	{
		$buf_home_forum=libHome::forumNew();
		file_put_contents(libCache::dirName('forum').'/home-forum.html', $buf_home_forum);
		print $buf_home_forum;
	}
	print '</td>';

	print '<td class="homeSplit"></td>';

	print '<td class="homeGameNotices">';

	/*$buf = libHome::PMs();
	if(strlen($buf))
		print '<div class="homeHeader">Private messages</div>'.$buf;
	*/

	print '<div class="homeHeader">'.l_t('Notices').' <a href="index.php?notices=on">'.libHTML::link().'</a></div>';
	print libHome::Notice();
	print '</td>';

	print '<td class="homeSplit"></td>';

	print '<td class="homeGamesStats">';
	print '<div class="homeHeader">'.l_t('My games').' <a href="gamelistings.php?page=1&gamelistType=My games">'.libHTML::link().'</a></div>';
	print libHome::gameNotifyBlock();

	print '</td>
	</tr></table>';

	print '</div>';
	print '</div>';
}

libHTML::$footerIncludes[] = l_j('home.js');
libHTML::$footerScript[] = l_jf('homeGameHighlighter').'();';

$_SESSION['lastSeenHome']=time();

libHTML::footer();

?>
