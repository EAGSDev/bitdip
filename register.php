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

/**
 * @package Base
 * @subpackage Forms
 */

require_once('header.php');

require_once(l_r('objects/mailer.php'));
global $Mailer;
$Mailer = new Mailer();

if ( $Misc->Panic )
{
	libHTML::notice(l_t('Registration disabled'),
		l_t("Registration has been temporarily disabled while we take care of an ".
		"unexpected problem. Please try again later, sorry for the inconvenience."));
}

// The user must be guest to register a new account
if( $User->type['User'] )
{
	libHTML::error(l_t("You're attempting to create a ".
		"new user account when you already have one. Please use ".
		"your existing user account."));
}

libHTML::starthtml();

$page = 'firstValidationForm';

if (isset($_REQUEST['emailValidate']) && isset($_REQUEST['InviteCode']) && isset($_REQUEST['Username']) )
{
	try
	{




		// check the email address
		$email = strtolower(trim($_REQUEST['emailValidate']));

		if(!filter_var($email, FILTER_VALIDATE_EMAIL))
			throw new Exception(l_t("A first check of this e-mail is finding it invalid. Remember you need one to play, and it will not be spammed or released."));

		$query="SELECT email FROM wD_Users WHERE email=?";
		$row=$DBi->fetch_row("$query", false, array($email));
		if($row)
		throw new Exception(l_t("The e-mail address '%s', is already in use. Please choose another.",$email));



		// check the username
		if (isset($_REQUEST['Username']) && !empty($_REQUEST['Username'])) {
			$username = strtolower(trim($_REQUEST['Username']));
		}
		else {
			throw new Exception(l_t("Please enter a Username using only lowercase letters, numbers and underscore(_)."));
		}
		if (!ctype_alnum(str_replace('_', '', $username))) {
			throw new Exception(l_t("Please enter a Username using only lowercase letters, numbers and underscore(_)."));
		}
		$query = "SELECT username FROM wD_Users WHERE username=?";
		$row = $DBi->fetch_row("$query",false,array($username));
		if ($row) {
			throw new Exception(l_t("That Username is not available. Please enter another."));
		}



		// email and username are ok, now create registration record
		$query="SELECT RegistrationID FROM bd_registrations WHERE AES_DECRYPT(email,'$aes_encrypt_key')=?";
		$row=$DBi->fetch_row("$query",false,array($email));
		if ($row) {$regid=$row['RegistrationID'];}
		else {
			$query="INSERT INTO bd_registrations (email) VALUES (AES_ENCRYPT(?,'$aes_encrypt_key'))";
			$result=$DBi->query("$query",array($email));
			$regid = $DBi->insert_id;
		}// end else



		// check the invite code
		$invitecode=trim($_REQUEST['InviteCode']);
		$query="SELECT UserID, InviteCode FROM bd_invitecodes WHERE InviteCode=?";
		$row=$DBi->fetch_row("$query", false, array($invitecode));
		if(!$row) {
			$query="UPDATE bd_registrations SET FailCount=FailCount+1";
			$result=$DBi->query("$query",array());
			throw new Exception(l_t('The invitation code is not valid.'));
		}
		else {
			$source=$row['UserID'];
			$token=md5($email.$username.$invitecode.$regid.session_id());
			$query="UPDATE bd_registrations SET username=?, InviteCode=?, Source=?, Token=? WHERE RegistrationID=?";
			$result=$DBi->query("$query",array($username,$invitecode,$source,$token,$regid));
			// generate new invite code for source
			$bitdip= new BitDip();
			$newinvitecode=$bitdip->generateinvitecode();
			$query="UPDATE bd_invitecodes SET InviteCode=? WHERE InviteCode=?";
			$result=$DBi->query("$query",array($newinvitecode,$invitecode));
		}


		$url = 'http://'.$_SERVER['SERVER_NAME'].'/register.php?emailToken='.$token;
		$subject='Your new BitDip account';
		$body='<p>Hello and welcome!</p><p>Thanks for validating your e-mail address; just use this link to create your new BitDip account:</p><p><a href="'.$url.'">'.$url.'</a></p>';
		$Mailer->Send(array($email=>$email),$subject,$body);

		$page = 'emailSent';
	}
	catch(Exception $e)
	{
		print '<div class="content">';
		print '<p class="notice">'.$e->getMessage().'</p>';
		print '</div>';

		$page = 'validationForm';
	}
}
elseif ( isset($_REQUEST['emailToken']) )
{
	try
	{

		$token=$_REQUEST['emailToken'];
		$query="SELECT AES_DECRYPT(email,'$aes_encrypt_key') AS emailaddress,username,source FROM bd_registrations WHERE (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(CreationDateTime)) < 3600 AND Token=?";
		$row=$DBi->fetch_row("$query",false,array($token));
		if ($row) {
			$email=$row['emailaddress'];
			$username=$row['username'];
			$source=$row['source'];

		} else {
			throw new Exception(l_t("A bad e-mail token was given, please try again"));
		}


		$page = 'userForm';

		// The user's e-mail is authenticated; he's not a robot and he has a real e-mail address
		// Let him through to the form, or process his form if he has one
		if ( isset($_REQUEST['userForm']) )
		{
			$_REQUEST['userForm']['email'] = $email;
			$_REQUEST['userForm']['username'] = $username;
			$_REQUEST['userForm']['source'] = $source;

			// If the form is accepted the script will end within here.
			// If it isn't accepted they will be shown back to the userForm page
			require_once(l_r('register/processUserForm.php'));
		}
		else
		{
			$_REQUEST['userForm']=array('email' => $email,'username' => $username, 'source' =>  $source);



			$page = 'firstUserForm';
		}
	}
	catch( Exception $e)
	{
		print '<div class="content">';
		print '<p class="notice">'.$e->getMessage().'</p>';
		print '</div>';

		$page = 'emailTokenFailed';
	}
}

switch($page)
{
	case 'firstValidationForm':
	case 'validationForm':
		print libHTML::pageTitle(l_t('Register a BitDip account'),l_t('<strong>Validate your e-mail address</strong> -&gt; Enter your account settings -&gt; Play BitDip!'));
		break;

	case 'emailSent':
	case 'emailTokenFailed':
	case 'firstUserForm':
	case 'userForm':
		print libHTML::pageTitle(l_t('Register a BitDip account'),l_t('Validate your e-mail address -&gt; <strong>Enter your account settings</strong> -&gt; Play BitDip!'));
}

switch($page)
{
	case 'firstValidationForm':

		print '<h2>'.l_t('Welcome to Bitcoin Diplomacy!').'</h2>';
		print '<p>'.l_t('In order to join BitDip you must have a valid invitation code from a current member. If you don\'t know an existing member, try finding one at <a href="http://webdiplomacy.net">webDiplomacy.net</a> or contact jimbursch at bitdip.net.').'</p>';

	case 'validationForm':

		require_once(l_r('locales/English/validationForm.php'));

		break;

	case 'emailSent':

		print '<h3>'.l_t('E-mail Validation').'</h3>';
		print l_t("An e-mail has been sent to the address you provided (<strong>%s</strong>) ".
			"with a link that you can click on to confirm that it's your real e-mail address, and then you're ".
			"ready to go!",htmlentities($_REQUEST['emailValidate']))."</p>";

		print "<p>".l_t("The e-mail may take a couple of minutes to arrive; if it doesn't appear check your spam inbox.")."</p>";

		print '<p>'.l_t('If you have problems e-mail this server\'s admin at %s',Config::$adminEMail).'</p>';

		break;

	case 'emailTokenFailed':
		print '<p>'.l_t('The e-mail token you provided was not accepted; please go back to the e-mail you were sent and '.
			'check that you visited the exact URL given.').'</p>';
		print '<p>'.l_t('If the e-mail did not arrive check your spam box. If you are sure you haven\'t received it and that '.
			'you have waited long enough for it try going through the registration process from the start.').'<br /><br />

			'.l_t('If it still fails e-mail this server\'s admin at %s',Config::$adminEMail).'</p>';
		break;

	case 'firstUserForm':

		print '<h3>'.l_t('E-mail address confirmed!').'</h3>';

		print "<p>".l_t("Alright; you're a human with an e-mail address!</p>
			<p>Enter the username and password you want, and any of the optional details/settings, into the screen below to
			complete the registration process.")."</p>";

	case 'userForm':
		print '<form method="post"><ul class="formlist">';
		print '<input type="hidden" name="emailToken" value="'.$_REQUEST['emailToken'].'" />';

		require_once(l_r('locales/English/userRegister.php'));
		require_once(l_r('locales/English/user.php'));

		break;
}

print '</div>';
libHTML::footer();

?>