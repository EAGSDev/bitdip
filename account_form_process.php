<?php

require_once('header_min.php');
require_once('PasswordHash.php');


$userid=$_SESSION['user_data']['id'];
$error='';

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



if (!empty($error)) {$_SESSION['notification']=$error;}

header("Location: ./account.php");
?>