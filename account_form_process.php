<?php

require_once('header.php');
require_once('PasswordHash.php');


$userid=$_SESSION['user_data']['id'];
$error='';

#########################################
// authorization -- login required

if (!isset($_SESSION['authorized']) || $_SESSION['authorized'] != $userid) {
	$_SESSION['notification']='<p>You must sign in to your account with your password to access account settings.</p>';
	header("location: ./logon.php");
	die('line 12');
}// end if (!isset($_SESSION['authorized']) || $_SESSION['authorized'] != 1)


###################################################
// update password

if (isset($_POST['password']) && !empty($_POST['password'])) {

	$password=$_POST['password'];
	if (strlen($password)>72) {$error.='<p>Password may not exceed 72 characters.</p>';}

	if (empty($error)) {
		$hasher = new PasswordHash(8, false);
		$hashedpassword = $hasher->HashPassword($password);
		$query="UPDATE wD_Users SET password=?,PasswordTemp=0 WHERE id=?";
		$result=$DBi->query("$query",array($hashedpassword,$userid));
		if ($result){$_SESSION['notification']='<p>Your password has been updated.</p>';}
		else {$_SESSION['notification']='<p>Password update failed.</p>';}
	}// end if (empty($error))

}// end if (isset($_POST['password']))

###################################################
// update comment

if (isset($_POST['comment'])) {

	$comment=$_POST['comment'];
	$query="UPDATE wD_Users SET comment=? WHERE id=?";
	$result=$DBi->query("$query",array($comment,$userid));
	if ($result){$_SESSION['notification']='<p>Your comment has been updated.</p>';}
	else {$_SESSION['notification']='<p>Comment update failed.</p>';}

}// end if (isset($_POST['comment']))


###################################################
// change email

if (isset($_POST['emailToken']) && isset($_POST['emailchange'])) {
	$error='';
	$newemail=trim(strtolower($_POST['emailchange']));
	$emailToken=$_POST['emailToken'];
	$userid=$_SESSION['user_data']['id'];

	if ($emailToken != hash('md5', $_SESSION['user_data']['SecurityKey'].$_SESSION['user_data']['email'])) {$error .= '<p>The email token is not valid.</p>';}
	if(!filter_var($newemail, FILTER_VALIDATE_EMAIL)) {	$error .='<p>Please enter a valid email address.</p>';}

	if (empty($error)) {
		$query="UPDATE wD_Users SET email=AES_ENCRYPT(?,?), password=0 WHERE id=?";
		$result=$DBi->query("$query",array($newemail,$aes_encrypt_key,$userid));
		if ($result) {
			$_SESSION['notification']='Your account email address has been changed. You will have to reset your password using the new email address.';
			$_SESSION['authorized']=0;
			header("Location: ./logon.php");
			die('line 61');
		}
		else {
			$error='Update failed.';
		}
	}//end if (empty($error))



}// end if (isset($_POST['emailToken'])

if (isset($_POST['emailchangerequest'])) {
	$email=$_SESSION['user_data']['email'];
	$username=$_SESSION['user_data']['username'];
	require_once('objects/mailer.php');
	$Mailer = new Mailer();
	$emailToken = hash('md5', $_SESSION['user_data']['SecurityKey'].$_SESSION['user_data']['email']);
	$url = 'http://'.$_SERVER['SERVER_NAME'].'/account.php?emailToken='.$emailToken;
	$subject='BitDip email change request';
	$body='<p>Hello '.$_SESSION['user_data']['username'].'</p><p>We have received a request to change your account email address. To complete the request, log in to your account and visit this url:</p><p><a href="'.$url.'">'.$url.'</a></p>';
	$Mailer->Send(array($email=>$username),$subject,$body);
	$_SESSION['notification']='<p>Check your current email for a confirmation link.</p><p> <b>NOTICE: After you change your email address, you will have to reset your password using the new email address.</b></p>';
}// end if (isset($_POST['emailchangerequest']))




if (!empty($error)) {$_SESSION['notification']=$error;}

header("Location: ./account.php");
?>