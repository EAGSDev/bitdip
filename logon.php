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

libHTML::starthtml();

if (isset($_SESSION['notification']) && !empty($_SESSION['notification'])) {
	print '<div class="content">';
	print '<p class="notice">'.$_SESSION['notification'].'</p>';
	print '</div>';
	$_SESSION['notification']='';
}// end if (isset($_SESION['notification']) && !empty($_SESSION['notification']))


###########################################
// forgot password

if( isset($_GET['forgotPassword'])) {

	print libHTML::pageTitle('Reset your password','Resetting passwords using your e-mail account, in-case you forgot your password.');

	print '<p>Enter your username OR your email address here and you will receice a temporary password by email.</p>';
	print '<form action="./logon_form_process.php" method="post">';
	print '<ul class="formlist">';
	print '<li class="formlisttitle">Username or Email Address</li>';
	print '<li class="formlistfield"><input type="text" tabindex="1" maxlength=30 size=15 name="forgotUsername"></li>';
	print '<li class="formlistdesc">The BitDip username or email address of the account which you can\'t log in to.</li>';
	print '<li><input type="submit" class="form-submit" value="Reset Password"></li>';
	print '</ul>';
	print '</form>';


}// end if( isset($_GET['forgotPassword']))


##############################################
// user logon

else {
	print libHTML::pageTitle('Log on','Enter your BitDip account username and password to log into your account.');
	print '
		<form action="./logon_form_process.php" method="post">

		<ul class="formlist">

		<li class="formlisttitle">'.'Username'.'</li>
		<li class="formlistfield"><input type="text" tabindex="1" maxlength=30 size=30 name="loginuser"></li>
		<li class="formlistdesc">Your BitDip username -- if you don\'t have one please <a href="register.php" class="light">register</a></li>

		<li class="formlisttitle">Password</li>
		<li class="formlistfield"><input type="password" tabindex="2" maxlength=72 size=30 name="loginpass"></li>
		<li class="formlistdesc">Your BitDip password</li>

		<li><input type="submit" class="form-submit" value="Log on"></li>
		</ul>
		</form>
		<p><a href="logon.php?forgotPassword=1" class="light">Reset password?</a></p>';
}



print '</div>';
libHTML::footer();
?>
