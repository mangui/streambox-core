<?php

include ('bin/debug.php');

global $userlist;

session_start();

if ($_SESSION['authorized'] == false)
{
	list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));

	exec("echo " .$_SERVER['PHP_AUTH_USER'] ." >/tmp/yopla");

	// checkup login and password
	if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
	{
		foreach ($userlist as $user => $pass)
		{
			if (($user == $_SERVER['PHP_AUTH_USER']) && ($pass == $_SERVER['PHP_AUTH_PW']))
			{
				$_SESSION['authorized'] = true;
				addlog("User [" .$user ."] successfully identified!");
				addstreaminglog("iStreamDev user [" .$user ."] successfully identified!");
			}
		}
	}

	// login
	if (!$_SESSION['authorized'])
	{
		addlog("Identification failed: " .$_SERVER['PHP_AUTH_USER'] ."/" .$_SERVER['PHP_AUTH_PW']);
		addstreaminglog("iStreamDev identification failed: " .$_SERVER['PHP_AUTH_USER'] ."/" .$_SERVER['PHP_AUTH_PW']);

		header('WWW-Authenticate: Basic Realm="Login please"');
		header('HTTP/1.0 401 Unauthorized');
		echo "Incorrect user/password";
		exit;
	}
}

?>
