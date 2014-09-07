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
 * An object which reads/writes global named integers in the misc table. Used to
 * cache often used stats, to track the database version compared to the code
 * version, and set dynamic configuration flags (such as whether the server is in
 * panic mode)
 *
 * @package Base
 */
class BitDip
{

	public function generateinvitecode() {
		global $DBi;
		$result='TRUE';
		while ($result) {
			$newinvitecode = substr(hash('sha256',time().session_id()), 3, 16);
			$query="SELECT InviteCode FROM bd_invitecodes WHERE InviteCode=?";
			$result=$db->fetch_row("$query",false,array($newinvitecode));
		}// end while
		return $newinvitecode;
	}// end function

}// end class

?>