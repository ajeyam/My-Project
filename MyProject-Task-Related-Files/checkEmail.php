<?php
session_start();
include 'config.php';
$config = new Config();

// Filter user input for protecting Cross Site Scripting(XSS) Attack
$email = $config->filter_input($_GET['email']);


?>
