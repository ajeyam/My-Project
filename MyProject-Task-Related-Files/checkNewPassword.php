<?php
 // Filter user input for protecting Cross Site Scripting(XSS) Attack
    session_start();
	include 'config.php';
	$config = new Config();

    $result = true;

    // Gets username, old password and new password
    $submit = $_GET['old'];
    $new = $_GET['new'];
	
	// Filter user input for protecting Cross Site Scripting(XSS) Attack
    $username = $config->filter_input($_GET['user']);

    // Encrypts old password to check if it's correct
    $salt = "A7fds7f6sd6d6fd77fd";
    $submit = $submit.$salt;
    $submit = hash("sha256",$submit);

    //Encrypts new password
    $new = $new.$salt;
    $new = hash("sha256",$new);
?>
