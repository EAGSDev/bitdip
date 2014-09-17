<?php


require_once('header_min.php');
require_once('PasswordHash.php');


require_once('objects/mailer.php');
global $Mailer;
$Mailer = new Mailer();


if ( $Misc->Panic ){die('Registration has been temporarily closed');}



if (isset($_POST['emailValidate']) && isset($_POST['InviteCode']) && isset($_POST['Username']) ) {

	$error='';

	###################################
	// check the email address

	if (!empty($_POST['emailValidate'])) {$email = strtolower(trim($_POST['emailValidate']));} else {$error .='<p>Please enter a valid email address.</p>';}

	if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$error .='<p>A first check of this e-mail is finding it invalid. Remember you need one to play, and it will not be spammed or released.</p>';
	}// end if(!filter_var($email, FILTER_VALIDATE_EMAIL))

	$query="SELECT email FROM wD_Users WHERE email=?";
	$row=$DBi->fetch_row("$query", false, array($email));
	if($row) {$error .='<p>The e-mail address '.$email.', is already in use. Please choose another.</p>';}


	#########################################
	// check the username

	if (isset($_POST['Username']) && !empty($_POST['Username'])) {$username = strtolower(trim($_POST['Username']));}
	else {$error .='<p>Please enter a Username using only lowercase letters, numbers and underscore(_).</p>';}

	// remove underscore and make sure just alphanumeric
	if (!ctype_alnum(str_replace('_', '', $username))) {$error .='<p>Please enter a Username using only lowercase letters, numbers and underscore(_).</p>';}

	// check for duplicate in use
	$query = "SELECT username FROM wD_Users WHERE username=?";
	$row = $DBi->fetch_row("$query",false,array($username));
	if ($row) {$error .='<p>That Username is not available. Please enter another.</p>';}

	##########################################
	// email and username are ok, now update or create registration record

	if (empty($error)) {
		$query="SELECT RegistrationID FROM bd_registrations WHERE AES_DECRYPT(email,?)=?";
		$row=$DBi->fetch_row("$query",false,array($aes_encrypt_key,$email));
		if ($row) {$regid=$row['RegistrationID'];}
		else {
			$query="INSERT INTO bd_registrations (email,username) VALUES (AES_ENCRYPT(?,?),?)";
			$result=$DBi->query("$query",array($email,$aes_encrypt_key,$username));
			$regid = $DBi->insert_id;
		}// end else
	}
	else {$_SESSION['notification']=$error;}


	##################################
	// check the invite code

	$invitecode=trim($_POST['InviteCode']);
	$query="SELECT UserID, InviteCode FROM bd_invitecodes WHERE InviteCode=?";
	$row=$DBi->fetch_row("$query", false, array($invitecode));
	if(!$row) {
		$query="UPDATE bd_registrations SET FailCount=FailCount+1 WHERE RegistrationID=?";
		$result=$DBi->query("$query",array($regid));
		$error .='<p>The invitation code is not valid.</p>';
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


	if (empty($error)) {
		$url = 'http://'.$_SERVER['SERVER_NAME'].'/register.php?emailToken='.$token;
		$subject='Your new BitDip account';
		$body='<p>Hello and welcome!</p><p>Thanks for validating your e-mail address; just use this link to create your new BitDip account:</p><p><a href="'.$url.'">'.$url.'</a></p>';
		$Mailer->Send(array($email=>$email),$subject,$body);
		$_SESSION['page'] = 'emailSent';
		$_SESSION['emailValidate'] = $email;
	}
	else {$_SESSION['notification']=$error;}



}// end if (isset($_POST['emailValidate']) && isset($_POST['InviteCode']) && isset($_POST['Username']) )

else if (isset($_POST['emailToken']) && isset($_POST['password']) && isset($_POST['comment'])) {

	$error='';

	$token=$_POST['emailToken'];
	$comment=$_POST['comment'];
	$password=$_POST['password'];

	$query="SELECT AES_DECRYPT(email,?) AS emailaddress,username,source FROM bd_registrations WHERE (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(CreationDateTime)) < 3600 AND Token=?";
	$row=$DBi->fetch_row("$query",false,array($aes_encrypt_key,$token));
	if ($row) {
		$email=$row['emailaddress'];
		$username=$row['username'];
		$source=$row['source'];
	}
	else {$error .='<p>A bad e-mail token was given, please try again.</p>';}

	if (strlen($password)>72) {$error.='<p>Password may not exceed 72 characters.';}


	if (empty($error)) {
		$hasher = new PasswordHash(8, false);
		$hashedpassword = $hasher->HashPassword($password);
		$query="INSERT INTO wD_Users (username,email,source,comment,password,timeJoined,timeLastSessionEnded) VALUES (?,AES_ENCRYPT(?,?),?,?,?,NOW(),NOW())";
		$result=$DBi->query("$query",array($username,$email,$aes_encrypt_key,$source,$comment,$hashedpassword));
		$newuserid = $DBi->insert_id;
		$bitdip= new BitDip();
		$newkey=$bitdip->generatesecuritykey($newuserid);
		$_SESSION['user_data']['id']=$newuserid;

		$query="DELETE FROM bd_registrations WHERE Token=?";
		$result=$DBi->query("$query",array($token));

		$bitdip= new BitDip();
		$codecount=0;
		while ($codecount < 3) {
			$newinvitecode=$bitdip->generateinvitecode();
			$query="INSERT INTO bd_invitecodes (UserID, InviteCode) VALUES (?,?)";
			$result=$DBi->query("$query",array($newuserid,$newinvitecode));
			$codecount++;
		}// end while

		//$newuserid->sendNotice('No','No',"Welcome! This area displays your notices, which let you catch up with what has happened since you were last here");
		header("Location: ./index.php");
		die('line 147');
	}// end if (empty($error))



/*

	print libHTML::pageTitle(l_t('Register a webDiplomacy account'),l_t('Validate your e-mail address -&gt; Enter your account settings -&gt; <strong>Play webDiplomacy!</strong>'));

	print "<h3>".l_t("Welcome to webDiplomacy!")."</h3>
			<p>".l_t("Welcome, %s!",$SQLVars['username'])."<br /><br />

				".l_t("You can now post in the <a href='forum.php' class='light'>forum</a>, ".
				"look for <a href='gamelistings.php' class='light'>a game to join</a>, ".
				"create a <a href='gamecreate.php' class='light'>new game</a>, ".
				"or get some <a href='help.php' class='light'>help/info</a>.")."<br /> ".
				l_t("Be sure to bookmark the <a href='index.php' class='light'>home page</a>, ".
				"which displays a summary of your games and forum activity.")."<br /><br />

				".l_t("If you don't know what Diplomacy is about yet check out the quick
				<a href='intro.php' light='class'>graphical intro to webDiplomacy</a>,
				so you can get going faster.")."
			</p>";
	print '</div>';

*/


}// end else if (isset($_POST['emailToken']))


header("Location: ./register.php");


?>