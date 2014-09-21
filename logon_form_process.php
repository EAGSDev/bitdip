<?php

require_once('header_min.php');
require_once('PasswordHash.php');
require_once('objects/user.php');


$error='';



#################################################
// normal login

$_SESSION['authorized']=0;

if (isset($_POST['loginuser']) AND isset($_POST['loginpass'])) {
	$username=trim(strtolower($_POST['loginuser']));
	$givenpassword=trim($_POST['loginpass']);

	if (strlen($givenpassword) > 72) {die('Invalid password.');}

	$query="SELECT id, password, FailedLoginCount, SecurityKey, PasswordTemp, UNIX_TIMESTAMP(PasswordTempTimestamp) AS pwtime FROM wD_Users WHERE username=?";
	$row=$DBi->fetch_row("$query",false,array($username));
	if (!$row) {$error .= '<p>We have no record of that Username.</p>';}
	else {
		$userid=$row['id'];
		$password=$row['password'];
		$passwordtemp=$row['PasswordTemp'];
		$passwordtimestamp=$row['pwtime'];
		$failedlogins=$row['FailedLoginCount'];
		$seckey=$row['SecurityKey'];

        if ($passwordtemp==1 && time()-$passwordtimestamp > 900) {
        	$error.='<p>Your password has expired. You need to reset your password.</p>';
        }
		else {
			$hasher = new PasswordHash(8, false);
			$check = $hasher->CheckPassword($givenpassword, $password);
        }// end else


        if ($check && empty($error)) { // A match was made.
        	$_SESSION['authorized']=1;
	       	$query="UPDATE wD_Users SET FailedLoginCount=0 WHERE id=?";
        	$result=$DBi->query("$query",array($userid));
        	$servername=$_SERVER['SERVER_NAME'];
        	setcookie('security_key', $seckey, time()+31536000, '/',$servername,0);
        	$User = new User($userid);
        	header("Location: ./index.php");
        	die();
        }// end if ($check && empty($error))

        else {
        	$error.='<p>The given password is not valid.</p>';
        	$query="UPDATE wD_Users SET FailedLoginCount=FailedLoginCount+1 WHERE id=?";
        	$result=$DBi->query("$query",array($userid));
        	if ($failedlogins > 3) {
        		$query="UPDATE wD_Users SET password='0' WHERE id=?";
        		$result=$DBi->query("$query",array($userid));
        		$error.='<p>Too many attempts have been made to log in to your account. You must reset your password.</p>';
        	}// end if ($failedlogins > 3)

        }// end else

	}// end else

}// end if (isset($_POST['loginuser']) AND isset($_POST['loginpass']))

###############################################################
// forgot password

if (isset($_POST['forgotUsername']) && !empty($_POST['forgotUsername'])) {


	$userid='';
	$username='';
	$email='';

	$givenusername=trim(strtolower($_POST['forgotUsername']));

	##########################################
	// error check

	if (filter_var($givenusername, FILTER_VALIDATE_EMAIL)) {
		$email=$givenusername;
		$query="SELECT id,username FROM wD_Users WHERE AES_DECRYPT(email,?)=?";
		$row=$DBi->fetch_row("$query",false,array($aes_encrypt_key,$email));
		if (!$row) {$error.='<p>That email address ('.$email.') does not exist in our records.</p>';}
		else {
			$userid=$row['id'];
			$username=$row['username'];
		}// end else
	}// end if (filter_var($givenusername, FILTER_VALIDATE_EMAIL))


	else {
		$username=$givenusername;
		$query="SELECT id, AES_DECRYPT(email,?) AS emailaddress FROM wD_Users WHERE username=?";
		$row=$DBi->fetch_row("$query",false,array($aes_encrypt_key,$username));
		if (!$row) {$error.='<p>That username ('.$username.') does not exist in our records.</p>';}
		else {
			$userid=$row['id'];
			$email=$row['emailaddress'];
		}// end else
	}// end else


	########################################
	// set and send temporary password


	if (empty($error) && !empty($userid)) {

		$bitdip=new BitDip();
		$newpassword=$bitdip->generatepassword();
		$hasher = new PasswordHash(8, false);
		$hashedpassword = $hasher->HashPassword($newpassword);
		$query="UPDATE wD_Users SET password=?, PasswordTemp=1, PasswordTempTimestamp=NOW() WHERE id=?";
		$result=$DBi->query("$query",array($hashedpassword,$userid));



		$subject='BitDip account information';
		$body='<p>We have recieved a request to reset the password on your BitDip account. Below is your temporary password, which is good for approximately 15 minutes.</p><p><b>'.$newpassword.'</b></p>';

		require_once('./objects/mailer.php');
		$Mailer = new Mailer();
		$Mailer->Send(array($email=>$username),$subject,$body);

		$_SESSION['notification']='<p>Check your email for a new, temporary password.</p>';
	}// end if (empty($error) && !empty($userid))



}// end if (isset($_POST['forgotUsername']))


##############################################




if (!empty($error)) {$_SESSION['notification']=$error;}

header("Location: ./logon.php");

?>