<?php

/**
 * @package Base
 */

require_once('header.php');

require_once(l_r('gamesearch/search.php'));
require_once(l_r('pager/pagergame.php'));
require_once(l_r('objects/game.php'));
require_once(l_r('gamepanel/game.php'));

if (isset($_GET['userID']) && is_numeric($_GET['userID'])) {
	$profileuserid = $_GET['userID'];
	}
else {$profileuserid=false;}


if ( !$profileuserid )
{
	libHTML::starthtml('Search for user');

	print libHTML::pageTitle(l_t('Search for user'),l_t('Search for a user using all or part of the username.'));

	if( isset($_SESSION['searchReturn']) && !empty($_SESSION['searchReturn'])) {
		print '<p class="notice">'.$_SESSION['searchReturn'].'</p>';
	}


	print '<form action="profile_form_process.php" method="post">';
	print '<ul class="formlist">';
		print '<li class="formlisttitle">Username:</li>';
		print '<li class="formlistfield">';
			print '<input type="text" name="searchusername" value="" size="30">';
		print '</li>';
		print '<li class="formlistdesc">';
		print '	Enter all or part of a user name.';
		print '</li>';
	print '</ul>';
	print '<div class="hr"></div>';
	print '<p class="notice">';
		print '<input type="submit" class="form-submit" value="Search">';
	print '</p>';
	print '</form>';
	print '</div>';

}
else {
	$query="SELECT ";
	$query.="wD_Users.id, ";
	$query.="wD_Users.username, ";
	$query.="wD_Users.comment, ";
	$query.="wD_Users.type, ";
	$query.="wD_Users.JoinedDateTime, ";
	$query.="wD_Users.LastVisitDateTime, ";
	$query.="wD_Users.source ";
	$query.="FROM wD_Users WHERE id=?";
	$UserProfile=$DBi->fetch_row("$query",false,array($profileuserid));


	// get source
	$sourceid=$UserProfile['source'];
	$query="SELECT id, username FROM wD_Users WHERE id=?";
	$BitDipSponsor=$DBi->fetch_row("$query",false,array($sourceid));

	// get guild
	$query="SELECT ";
	$query.="bd_guild_membership.Source, ";
	$query.="bd_guilds.Name, ";
	$query.="bd_guilds.LogoFilename ";
	$query.="FROM bd_guild_membership LEFT JOIN bd_guilds ON bd_guild_membership.GuildID=bd_guilds.GuildID ";
	$query.="WHERE bd_guild_membership.Status='active' AND bd_guild_membership.UserID=?";
	$GuildData=$DBi->fetch_row("$query",false,array($profileuserid));




}// end else



libHTML::starthtml();

print '<div class="content">';

if( isset($searchReturn) )
	print '<p class="notice">'.$searchReturn.'</p>';

if ( isset($_REQUEST['detail']) )
{
	print '<p>(<a href="profile.php?userID='.$UserProfile->id.'">'.l_t('Back').'</a>)</p>';

	switch($_REQUEST['detail'])
	{
		case 'threads':
			$dir=User::cacheDir($UserProfile->id);
			if( file_exists($dir.'/profile_threads.html') )
				print file_get_contents($dir.'/profile_threads.html');
			else
			{
				libAuth::resourceLimiter('view threads',20);

				$tabl = $DB->sql_tabl("SELECT id, subject, message, timeSent FROM wD_ForumMessages
					WHERE fromUserID = ".$UserProfile->id." AND type='ThreadStart'
					ORDER BY timeSent DESC");

				$buf = '<h4>'.l_t('Threads posted:').'</h4>
					<ul>';
				while(list($id,$subject,$message, $timeSent)=$DB->tabl_row($tabl))
				{
					$buf .= '<li><em>'.libTime::text($timeSent).'</em>:
						<a href="forum.php?threadID='.$id.'">'.$subject.'</a><br />'.
						$message.'</li>';
				}
				$buf .= '</ul>';

				file_put_contents($dir.'/profile_threads.html', $buf);
				print $buf;
			}
			break;

		case 'replies':
			$dir=User::cacheDir($UserProfile->id);
			if( file_exists($dir.'/profile_replies.html') )
				print file_get_contents($dir.'/profile_replies.html');
			else
			{
				libAuth::resourceLimiter('view replies',20);

				$tabl = $DB->sql_tabl("SELECT f.id, a.id, a.subject, f.message, f.timeSent
					FROM wD_ForumMessages f INNER JOIN wD_ForumMessages a ON ( f.toID = a.id )
					WHERE f.fromUserID = ".$UserProfile->id." AND f.type='ThreadReply'
					ORDER BY f.timeSent DESC");

				$buf = '<h4>'.l_t('Replies:').'</h4>
					<ul>';
				while(list($id,$threadID,$subject, $message, $timeSent)=$DB->tabl_row($tabl))
				{
					$buf .= '<li><em>'.libTime::text($timeSent).'</em>: <a href="forum.php?threadID='.$threadID.'#'.$id.'">Re: '.$subject.'</a><br />'.
						$message.'</li>';
				}
				$buf .= '</ul>';

				file_put_contents($dir.'/profile_replies.html', $buf);
				print $buf;
			}
			break;

		case 'civilDisorders':
			libAuth::resourceLimiter('view civil disorders',5);

			$tabl = $DB->sql_tabl("SELECT g.name, c.countryID, c.turn, c.bet, c.SCCount
				FROM wD_CivilDisorders c INNER JOIN wD_Games g ON ( c.gameID = g.id )
				WHERE c.userID = ".$UserProfile->id);

			print '<h4>'.l_t('Civil disorders:').'</h4>
				<ul>';
			while(list($name, $countryID, $turn, $bet, $SCCount)=$DB->tabl_row($tabl))
			{
				print '<li>
					'.l_t('Game:').' <strong>'.$name.'</strong>,
					'.l_t('country #:').' <strong>'.$countryID.'</strong>,
					'.l_t('turn:').' <strong>'.$turn.'</strong>,
					'.l_t('bet:').' <strong>'.$bet.'</strong>,
					'.l_t('supply centers:').' <strong>'.$SCCount.'</strong>
					</li>';
			}
			print '</ul>';
			break;

		case 'reports':
			if ( $User->type['Moderator'] )
			{
				require_once(l_r('lib/modnotes.php'));
				libModNotes::checkDeleteNote();
				libModNotes::checkInsertNote();
				print libModNotes::reportBoxHTML('User', $UserProfile->id);
				print libModNotes::reportsDisplay('User', $UserProfile->id);
			}
		break;
	}

	print '</div>';
	libHTML::footer();
}

print '<div><div class="rightHalf">';



print '<ul class="formlist">';

if ($GuildData) {
print '<li><b>Guild:</b></li>';
print '<li><img src="./images/guilds/'.$GuildData['LogoFilename'].'"><br />'.$GuildData['Name'].'</li>';
}
else {
	print '<li><b>Guild:</b> Not a guild member.</li>';
}


print '</ul></div>';


print "<h2>".$UserProfile['username'].'</h2>';

// Regular user info starts here:
print '<div class="leftHalf" style="width:50%">';



if ( $UserProfile['comment']) {	print '<p class="profileComment">"'.$UserProfile['comment'].'"</p>';}

print '<p><ul class="formlist">';

print '<li><strong>Last visited:</strong> '.$UserProfile['LastVisitDateTime'].'</li>';

print '<strong>View:</strong> <a class="light" href="profile.php?detail=threads&userID='.$UserProfile['id'].'">Threads</a>,<a class="light" href="profile.php?detail=replies&userID='.$UserProfile['id'].'">replies</a><br />';

print '</li>';


print '<li>&nbsp;</li>';
print '<li><strong>Joined:</strong> '.$UserProfile['JoinedDateTime'].'</li>';

print '<li>&nbsp;</li>';


print '</li></ul></p></div><div style="clear:both"></div></div>';


/* #####################################################

// commenting out private messaging for now.... jimbursch

if ( $User->type['User'] && $User->id != $UserProfile->id)
{
	print '<div class="hr"></div>';

	print '<a name="messagebox"></a>';

	if ( isset($_REQUEST['message']) && $_REQUEST['message'] )
	{
		if ( ! libHTML::checkTicket() )
		{
			print '<p class="notice">'.l_t('You seem to be sending the same message again, this may happen if you refresh '.
				'the page after sending a message.').'</p>';
		}
		else
		{
			$UserProfile->sendPM($User, $_REQUEST['message']);

			print '<p class="notice">'.l_t('Private message sent successfully.').'</p>';
		}
	}

	print '<div style="margin-left:20px"><ul class="formlist">';
	print '<li class="formlisttitle">'.l_t('Send private-message:').'</li>
		<li class="formlistdesc">'.l_t('Send a message to this user.').'</li>';

	print '<form action="profile.php?userID='.$UserProfile->id.'#messagebox" method="post">
		<input type="hidden" name="formTicket" value="'.libHTML::formTicket().'" />
		<textarea name="message" style="width:80%" rows="4"></textarea></li>
		<li class="formlistfield"><input type="submit" class="form-submit" value="'.l_t('Send').'" /></li>
		</form>
		</ul>
		</div>';
}

####################################################################
*/




########################################################################
/* commenting out game search for now...jimbursch

libHTML::pagebreak();

$search = new search('Profile');

$profilePager = new PagerGames('profile.php',$total);
$profilePager->addArgs('userID='.$UserProfile->id);

if ( isset($_GET['advanced']) && $User->type['User'] )
{
	print '<a name="search"></a>';
	print '<h3>'.l_t('Search %s\'s games:',$UserProfile->username).' (<a href="profile.php?page=1&amp;userID='.$UserProfile->id.'#top" class="light">'.l_t('Close').'</a>)</h3>';

	$profilePager->addArgs('advanced=on');

	$searched=false;
	if ( isset($_REQUEST['search']) )
	{

		$searched=true;
		$_SESSION['search-profile.php'] = $_REQUEST['search'];

		$search->filterInput($_SESSION['search-profile.php']);
	}
	elseif( isset($_REQUEST['page']) && isset($_SESSION['search-profile.php']) )
	{
		$searched=true;
		$search->filterInput($_SESSION['search-profile.php']);
	}

	print '<div style="margin:30px">';
	print '<form action="profile.php?userID='.$UserProfile->id.'&advanced=on#top" method="post">';
	print '<input type="hidden" name="page" value="1" />';
	$search->formHTML();
	print '</form>';
	print '<p><a href="profile.php?page=1&amp;userID='.$UserProfile->id.'#top" class="light">'.l_t('Close search').'</a></p>';
	print '</div>';

	if( $searched )
	{
		print '<div class="hr"></div>';
		print $profilePager->pagerBar('top','<h3>'.l_t('Results:').'</h3>');

		$gameCount = $search->printGamesList($profilePager);

		if( $gameCount == 0 )
		{
			print '<p class="notice">';

			if( $profilePager->currentPage > 1 )
				print l_t('No more games found for the given search parameters.');
			else
				print l_t('No games found for the given search parameters, try broadening your search.');

			print '</p>';
			print '<div class="hr"></div>';
		}
	}
}
else
{
	$searched = true;

	if(isset($_SESSION['search-profile.php']))
		unset($_SESSION['search-profile.php']);

	$leftSide = '<h3>'.l_t('%s\'s games',$UserProfile->username).' '.
			( $User->type['User'] ? '(<a href="profile.php?userID='.$UserProfile->id.'&advanced=on#search">'.l_t('Search').'</a>)' : '' ).
			'</h3>';
	print $profilePager->pagerBar('top', $leftSide);

	$gameCount = $search->printGamesList($profilePager);

	if ( $gameCount == 0 )
	{
		print '<p class="notice">';
		if( $profilePager->currentPage > 1 )
			print l_t('No more games found for this profile.');
		else
			print l_t('No games found for this user.');
		print '</p>';

		print '<div class="hr"></div>';
	}
}

if ( $searched && $gameCount > 1 )
	print $profilePager->pagerBar('bottom','<a href="#top">'.l_t('Back to top').'</a>');
else
	print '<a name="bottom"></a>';

print '</div>';

##################################################
*/

libHTML::footer();

?>
