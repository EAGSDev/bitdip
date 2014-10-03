<?php

require_once('header.php');

$error='';


if (isset($_POST['searchusername']) && !empty($_POST['searchusername'])) {

	$searchterm = $_POST['searchusername'];

}// end if (isset($_POST['searchUser']) && !empty($_POST['searchUser']))




if (!empty($error)) {$_SESSION['notification']=$error;}

header("Location: ./profile.php");

?>