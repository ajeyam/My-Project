<?php

	session_start();
	include 'config.php';
	$config = new Config();
	
	$_SESSION['error'] = array();

	// Gets username,password and Filter user input for protecting Cross Site Scripting(XSS) Attack
  $username = $config->filter_input($_GET['username']);
  $submit = $_GET['submit'];

	$ip = $_SESSION[login_info]['ip_address'];
	$login_time = $_SESSION[login_info]['login_time'];

	// Encrypts password using salt and sha256
  $salt = "A7fds7f6sd6d6fd77fd";
  $submit = $submit.$salt;
  $submit = hash("sha256",$submit);

	// Connects to database (replace $dbuser and $dbpass with your own database username and password)
  $dbuser = "dgbr";
  $dbpass = "ilovecows";
  $db = "SSID";
  $connect = oci_connect($dbuser, $dbpass, $db);

	//redirect
	$location = null;

	if(isset($username, $submit) && !empty($username) && !empty($submit)){

		if (!$connect) {
			echo "An error occurred connecting to the database";
			exit;
		}

		// checks if user with a certain username and password exist
		$sql = "SELECT * FROM users WHERE username = '$username' AND submit = '$submit'";

		$stmt = oci_parse($connect, $sql);

		if(!$stmt)  {
			echo "An error occurred in parsing the sql string.\n";
			exit;
		}

		try {
			@oci_execute($stmt);
		} catch(Exception $e){
			echo $e->getMessage();
		}
		

		// username and password match, the username is stored and the user is logged in
		if (@oci_fetch_array($stmt)) {

      //Unlocks the account if the lockout period has finished
			if(date("Y-m-d H:i:s", time()) >= $_SESSION['unlock_time']){
				unset($_SESSION['lockdown' + $username]);
				unset($_SESSION['unlock_time']);
			}

      //If account is still locked
			if($_SESSION['lockdown' + $username]){

				$_SESSION['error'][] = "Your Account is currently blocked";
				$location = 'login.php?login=false';

				$to = strtotime($_SESSION['unlock_time']);
				$from = strtotime(date("Y-m-d H:i:s", time()));
				$diff = round(abs($to - $from) / 60,2). " minute(s)";

				$_SESSION['error'][] = "Please try again in $diff";

			} else {
				$_SESSION['userError'] = "";
				$_SESSION['loggedin'] = $username;
				$location = 'monitoringPage.php';
			}

		}
		else {
      //User inputed the incorrect username or password
			$_SESSION['error'][] = "Username or Password is incorrect, please try again";

			// Check if ip already does exist;
			$query = "SELECT * from LoginAttempts where IP = '$ip'";
			$stmts = oci_parse($connect, $query);
			oci_execute($stmts);
			$row = oci_fetch_array($stmts);

			$login_attempt = $row['LOGINATTEMPT'] + 1;

			$lock_time = $row[LOGINTIME];
			$unlock_time = date("Y-m-d H:i:s", strtotime('+10 minutes', strtotime($lock_time)));

			$to = strtotime($unlock_time);
			$from = strtotime(date("Y-m-d H:i:s", time()));
			$diff = round(abs($to - $from) / 60,2). " minute(s)";

			// check login attempt if exist
			if($login_attempt >= 3){

				// if current time greater than unlock time , allow attempt
				if(date("Y-m-d H:i:s", time()) >= $unlock_time){

					unset($_SESSION['lockdown' + $username]);
					unset($_SESSION['unlock_time']);

					// reset login attempt
					$login_attempt = 1;
					$sql = "update LoginAttempts set LOGINTIME = '$login_time', LOGINATTEMPT = '$login_attempt' where IP = '$ip' AND username = '$username'";
					$stmt = oci_parse($connect, $sql);

					$result = @oci_execute($stmt);

				} else {

					$_SESSION['lockdown' + $username] = true;
					$_SESSION['unlock_time'] = $unlock_time;

					//if current less than 10 minutes of lockage, lock attempt
					$_SESSION['error'][] = "Account Locked, please try again in $diff";
				}

			} else {

				//if ip exists, update record
				if($row){

				$sql = "update LoginAttempts set USERNAME = '$username', LOGINTIME = '$login_time', LOGINATTEMPT = '$login_attempt' where IP = '$ip'";
				$stmt = oci_parse($connect, $sql);

				$result = oci_execute($stmt);

				} else {

					// create record
					$login_attempt = 1;

					$sql = "insert into LoginAttempts (USERNAME, IP, LOGINTIME, LOGINATTEMPT) VALUES ('$username', '$ip', '$login_time', '$login_attempt')";
					$stmt = oci_parse($connect, $sql);

					$result = @oci_execute($stmt);
				}

			}

			$_SESSION['loggedin'] = "";
			$location = 'login.php?login=false';


		}

	} else {
		$_SESSION['error'][] = 'Username or Password cannot be blank';
		$location = 'login.php?login=false';
	}


	oci_close($connect);
	header('Location: ' . $location);
	exit;
