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

if ( $Misc->Panic )
{
	libHTML::notice(l_t('Registration disabled'),
		l_t("Registration has been temporarily disabled while we take care of an ".
		"unexpected problem. Please try again later, sorry for the inconvenience."));
}


libHTML::starthtml();


if (isset($_SESSION['notification']) && !empty($_SESSION['notification'])) {
	print '<div class="content">';
	print '<p class="notice">'.$_SESSION['notification'].'</p>';
	print '</div>';
	$_SESSION['notification']='';
}// end if (isset($_SESION['notification']) && !empty($_SESSION['notification']))


print libHTML::pageTitle(l_t('Register a BitDip account'),l_t('<strong>Validate your e-mail address</strong> -&gt; Enter your account settings -&gt; Play BitDip!'));

print '<h2>Registration</h2>';
print '<form method="post" action="register_form_process.php">';


if (isset($_SESSION['page'])) {$page=$_SESSION['page'];} else {$page = 'firstValidationForm';}
if (isset($_GET['emailToken'])) {$page = 'firstUserForm';}


if (isset($_SESSION['InviteCode'])) {$invitecode=$_SESSION['InviteCode'];} else {$invitecode='';}
if (isset($_SESSION['Username'])) {$username=$_SESSION['Username'];} else {$username='';}
if (isset($_SESSION['emailValidate'])) {$emailValidate=$_SESSION['emailValidate'];} else {$emailValidate='';}
if (isset($_GET['emailToken'])) {$token=$_GET['emailToken'];} else {$token='';}

switch($page)
{
	case 'firstValidationForm':
		print '<h2>Welcome to BitDip!</h2>';
		print '<p>In order to join BitDip you must have a valid invitation code from a current member. If you don\'t know an existing member, try finding one at <a href="http://webdiplomacy.net">webDiplomacy.net</a> or contact jimbursch at bitdip.net.</p>';

	case 'validationForm':
		print '<ul class="formlist">';
		print '<li class="formlisttitle">Invitation Code</li>';
		print '<li class="formlistfield">';
		print '<input type="text" name="InviteCode" value="'.$invitecode.'" />';
		print '</li>';
		print '<li class="formlistdesc">Enter your invitation code.</li>';

		print '<li class="formlisttitle">Username</li>';
		print '<li class="formlistfield">';
		print '<input type="text" name="Username" value="'.$username.'" />';
		print '</li>';
		print '<li class="formlistdesc">Enter your BitDip username.</li>';


		print '<li class="formlisttitle">E-mail address</li>';
		print '<li class="formlistfield">';
		print '<input type="text" name="emailValidate" size="50" value="'.$emailValidate.'" />';
		print '</li>';
		print '<li class="formlistdesc">Your email address will never be shared with anyone.</li>';
		print '</ul>';

		print '<div class="hr"></div>';

		print '<p class="notice">';
		print '<input type="submit" class="form-submit" value="Validate me">';
		print '</p>';
		print '</form>';

	break;

	case 'emailSent':

		print '<h3>E-mail Validation</h3>';
		print 'An e-mail has been sent to the address you provided (<strong>'.$emailValidate.'</strong>) with a link that you can click on to confirm that it\'s your real e-mail address, and then you\'re ready to go!</p>';
		print '<p>The e-mail may take a couple of minutes to arrive; if it doesn\'t appear check your spam inbox.</p>';
		print '<p>If you have problems e-mail this server\'s admin at '.Config::$adminEMail.'</p>';

	break;


	case 'firstUserForm':

		print '<h3>E-mail address confirmed!</h3>';

		print '<p>Alright; you\'re a human with an e-mail address!</p>';
		print '<p>Enter a password and anything you would like to state in your profile.</p>';

		print '<form method="post"><ul class="formlist">';
		print '<input type="hidden" name="emailToken" value="'.$_GET['emailToken'].'" />';
		print '<li class="formlisttitle">Password:</li>';
		print '<li class="formlistfield">';
		print '<input type="password" name="password" maxlength=72>';
		print '</li>';
		print '<li class="formlistdesc">Your BitDip password.</li>';
		print '<li class="formlisttitle">Comment:</li>';
		print '<li class="formlistfield">';
		print '<TEXTAREA NAME="comment" ROWS="3" COLS="50"></textarea>';
		print '</li>';
		print '<li class="formlistdesc">A comment you would like to make in your profile.</li>';
		print '<div class="hr"></div>';

		print '<p class="notice">';
		print '<input type="submit" class="form-submit" value="Register">';
		print '</p>';
		print '</form>';
		break;
}

print '</div>';
libHTML::footer();

?>