<?php

require_once('header.php');


#########################################
// authorization -- login required

if (!isset($_SESSION['authorized']) || $_SESSION['authorized'] != 1) {
	$_SESSION['notification']='<p>You must sign in to your account with your password to access account settings.</p>';
	header("location: ./logon.php");
	die('line 12');
}// end if (!isset($_SESSION['authorized']) || $_SESSION['authorized'] != 1)

##############################################
// load user_data

if (isset($_SESSION['user_data']['id'])) {
$userid=$_SESSION['user_data']['id'];
$query="SELECT id,username,AES_DECRYPT(email,?) AS email,comment FROM wD_Users WHERE id=?";
$user_data=$DBi->fetch_row("$query",false,array($aes_encrypt_key,$userid));
}


libHTML::starthtml();

if (isset($_SESSION['notification']) && !empty($_SESSION['notification'])) {
	print '<div class="content">';
	print '<p class="notice">'.$_SESSION['notification'].'</p>';
	print '</div>';
	$_SESSION['notification']='';
}// end if (isset($_SESION['notification']) && !empty($_SESSION['notification']))



print libHTML::pageTitle('User account management','Alter the settings for your BitDip user account.');



print '<ul class="formlist">';

############################################################################
// invitation codes

$query='SELECT InviteCode FROM bd_invitecodes WHERE UserID=?';
$result=$DBi->fetch_rows("$query", false, array($user_data['id']));
if ($result) {
echo '<li class="formlisttitle">Invitation Codes</li>';
foreach ($result as $row) {
	echo '<div style="margin-left:50px;">'.$row['InviteCode'].'</div>';
}// end foreach
echo '<li class="formlistdesc">Give your friend one of these single-use invitation codes to join BitDip.</li>';
}// end if $result



print '<form method="post" action="./account_form_process.php">';
print '<li class="formlisttitle">E-mail address</li>';
print '<li class="formlistfield"><input type="text" name="email" size="50" value="'.$user_data['email'].'"  /></li>';
print '<li class="formlistfield"><input type="submit" class="form-submit notice" value="Change email"></li>';
print '<li class="formlistdesc">Your e-mail address; this will <strong>not</strong> be spammed or given out to anyone.</li>';
print '</form>';

print '<div class="hr"></div>';

print '<form method="post" action="./account_form_process.php">';
print '<li class="formlisttitle">Change password:</li>';
print '<li class="formlistfield">';
print '<input type="password" name="password" size="50" maxlength="72" />';
print '</li>';
print '<li class="formlistfield"><input type="submit" class="form-submit notice" value="Change password"></li>';
print '<li class="formlistdesc">Use any characters including punctuation, 72 max. Longer is better.</li>';
print '</form>';

print '<div class="hr"></div>';

print '<form method="post" action="./account_form_process.php">';
print '<li class="formlisttitle">Comment:</li>';
print '<li class="formlistfield">';
print '<TEXTAREA NAME="comment" ROWS="3" COLS="50">'.$user_data['comment'].'</textarea>';
print '</li>';
print '<li class="formlistfield"><input type="submit" class="form-submit notice" value="Update comment"></li>';
print '<li class="formlistdesc">A comment you would like to make in your profile. </li>';
print '</form>';


print '</ul>';

print '<div class="hr"></div>';



/*

######################################################
// muted threads


	$MutedUsers = array();
	foreach($User->getMuteUsers() as $muteUserID) {
		$MutedUsers[] = new User($muteUserID);
	}
	if( count($MutedUsers) > 0 ) {
		print '<li class="formlisttitle">Muted users:</li>';
		print '<li class="formlistdesc">The users which you muted, and are unable to send you messages.</li>';
		print '<li class="formlistfield"><ul>';
		foreach ($MutedUsers as $MutedUser) {
			print '<li>'.$MutedUser->username.' '.libHTML::muted("profile.php?userID=".$MutedUser->id.'&toggleMute=on&rand='.rand(0,99999).'#mute').'</li>';
		}
		print '</ul></li>';
	}

	$MutedGames = array();
	foreach($User->getMuteCountries() as $muteGamePair) {
		list($gameID, $muteCountryID) = $muteGamePair;
		if( !isset($MutedGames[$gameID])) $MutedGames[$gameID] = array();
		$MutedGames[$gameID][] = $muteCountryID;
	}
	if( count($MutedGames) > 0 ) {
		print '<li class="formlisttitle">Muted countries:</li>';
		print '<li class="formlistdesc">The countries which you muted, and are unable to send you messages.</li>';
		print '<li class="formlistfield"><ul>';
		$LoadedVariants = array();
		foreach ($MutedGames as $gameID=>$mutedCountries) {
			list($variantID) = $DB->sql_row("SELECT variantID FROM wD_Games WHERE id=".$gameID);
			if( !isset($LoadedVariants[$variantID]))
				$LoadedVariants[$variantID] = libVariant::loadFromVariantID($variantID);
			$Game = $LoadedVariants[$variantID]->Game($gameID);
			print '<li>'.$Game->name.'<ul>';
			foreach($mutedCountries as $mutedCountryID) {
				print '<li>'.$Game->Members->ByCountryID[$mutedCountryID]->country.' '.
				libHTML::muted("board.php?gameID=".$Game->id."&msgCountryID=".$mutedCountryID."&toggleMute=".$mutedCountryID."&rand=".rand(0,99999).'#chatboxanchor').'</li>';
			}
			print '</ul></li>';
		}
		print '</ul></li>';
	}

	$tablMutedThreads = $DB->sql_tabl(
		"SELECT mt.muteThreadID, f.subject, f.replies, fu.username ".
		"FROM wD_MuteThread mt ".
		"INNER JOIN wD_ForumMessages f ON f.id = mt.muteThreadID ".
		"INNER JOIN wD_Users fu ON fu.id = f.fromUserID ".
		"WHERE mt.userID = ".$User->id);
	$mutedThreads = array();
	while( $mutedThread = $DB->tabl_hash($tablMutedThreads))
		$mutedThreads[] = $mutedThread;
	unset($tablMutedThreads);

	if( count($mutedThreads) > 0 ) {
		print '<li class="formlisttitle"><a name="threadmutes"></a>Muted threads:</li>';
		print '<li class="formlistdesc">The threads which you muted.</li>';

		$unmuteThreadID=0;
		if( isset($_GET['unmuteThreadID']) ) {

			$unmuteThreadID = (int)$_GET['unmuteThreadID'];
			$User->toggleThreadMute($unmuteThreadID);

			print '<li class="formlistfield"><strong>Thread <a class="light" href="forum.php?threadID='.$unmuteThreadID.'#'.$unmuteThreadID.
				'">#'.$unmuteThreadID.'</a> unmuted.</strong></li>';
		}

		print '<li class="formlistfield"><ul>';

		foreach ($mutedThreads as $mutedThread) {
			if( $unmuteThreadID == $mutedThread['muteThreadID']) continue;
			print '<li>'.
				'<a class="light" href="forum.php?threadID='.$mutedThread['muteThreadID'].'#'.$mutedThread['muteThreadID'].'">'.
				$mutedThread['subject'].'</a> '.
				libHTML::muted('usercp.php?unmuteThreadID='.$mutedThread['muteThreadID'].'#threadmutes').'<br />'.
				$mutedThread['username'].' ('.$mutedThread['replies'].' replies)<br />'.
				'</li>';
		}
		print '</ul></li>';
	}

*/

print '</div>';
libHTML::footer();

?>
