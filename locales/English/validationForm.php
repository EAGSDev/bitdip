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

?><h2>Registration</h2>

<form method="post" action="register.php">

	<ul class="formlist">

		<?php
		if (isset($_REQUEST['InviteCode'])) {$invitecode=$_REQUEST['InviteCode'];} else {$invitecode='';}
		if (isset($_REQUEST['Username'])) {$username=$_REQUEST['Username'];} else {$username='';}
		?>

		<li class="formlisttitle">Invitation Code</li>
		<li class="formlistfield">
		        <input type="text" name="InviteCode" value="<?php echo $invitecode; ?>" />
		</li>
		<li class="formlistdesc">
			Enter your invitation code.
		</li>

		<li class="formlisttitle">Username</li>
		<li class="formlistfield">
		        <input type="text" name="Username" value="<?php echo $username; ?>" />
		</li>
		<li class="formlistdesc">
			Enter your BitDip username.
		</li>


		<li class="formlisttitle">E-mail address</li>
		<li class="formlistfield"><input type="text" name="emailValidate" size="50" value="<?php
		        if ( isset($_REQUEST['emailValidate'] ) )
					print $_REQUEST['emailValidate'];
		        ?>"></li>
		<li class="formlistdesc">
		Your email address will never be shared with anyone.
		</li>
</ul>

<div class="hr"></div>

<p class="notice">
	<input type="submit" class="form-submit" value="Validate me">
</p>
</form>