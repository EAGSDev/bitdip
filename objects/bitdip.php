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


class BitDip
{

	public function generateinvitecode() {
		global $DBi;
		$result='TRUE';
		while ($result) {
			$newinvitecode = substr(hash('sha256',time().session_id().rand()), 3, 8);
			// make sure invitecode is unique
			$query="SELECT InviteCode FROM bd_invitecodes WHERE InviteCode=?";
			$result=$DBi->fetch_row("$query",false,array($newinvitecode));
		}// end while
		return $newinvitecode;
	}// end function

	public function generatesecuritykey($userid=0) {
		global $DBi;
		$result='TRUE';
		while ($result) {
			$newsecuritykey = hash('sha256',time().session_id().rand());
			// make sure $newsecuritykey is unique
			$query="SELECT SecurityKey FROM wD_Users WHERE SecurityKey=?";
			$result=$DBi->fetch_row("$query",false,array($newsecuritykey));
		}// end while
		if ($userid != 0) {
			$query="UPDATE wD_Users SET SecurityKey=? WHERE id=?";
			$result=$DBi->query("$query",array($newsecuritykey,$userid));
		}// end if ($userid != 0)
		return $newsecuritykey;
	}// end function


	function generatepassword($length=16) {
		$password = '';
		$symbols=array('!','@','#','$','%','&','+','_','+','=');
		$parts = array_merge(range(0, 9),range('a', 'z'),range('A', 'Z'),$symbols);
		$parts = array_merge(range(0, 9),range('a', 'z'),range('A', 'Z'));
		while (strlen($password) <= $length) {
			$password .= $parts[array_rand($parts)];
		}// end for
		return $password;
	}// end function

}// end class

?>