<?php

require_once('header_min.php');
require_once('PasswordHash.php');

$error='';
$_SESSION['authorized']=0;



if (isset($_POST['loginuser']) AND isset($_POST['loginpass'])) {
	$username=trim(strtolower($_POST['loginuser']));
	$givenpassword=trim($_POST['loginpass']);


	if (strlen($givenpassword) > 72) {die('Invalid password.');}


	$query="SELECT id, password, FailedLoginCount, SecurityKey FROM wD_Users WHERE username=?";
	$row=$DBi->fetch_row("$query",false,array($username));
	if (!$row) {$error .= '<p>We have no record of that Username.</p>';}
	else {
		$userid=$row['id'];
		$password=$row['password'];
		$failedlogins=$row['FailedLoginCount'];
		$seckey=$row['SecurityKey'];
        $hasher = new PasswordHash(8, false);
        $check = $hasher->CheckPassword($givenpassword, $password);


        if ($check && empty($error)) { // A match was made.
        	$_SESSION['authorized']=1;
        	$_SESSION['user_data']['id'] = $userid;
        	$servername=$_SERVER['SERVER_NAME'];
        	setcookie('security_key', $seckey, time()+31536000, '/',$servername,0);
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

if (!empty($error)) {$_SESSION['notification']=$error;}

header("Location: ./logon.php");

?>