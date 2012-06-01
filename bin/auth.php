<?php

global $userlist;

session_start();

if ($_SESSION['authorized'] == false)
{
	list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));

	addlog("AUTH: connection attempt with " .$_SERVER['PHP_AUTH_USER'] ."/" .$_SERVER['PHP_AUTH_PW']);

	// checkup login and password
	if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
	{
		if($_SERVER['PHP_AUTH_PW'] == sqlgetuserinfo(password, $_SERVER['PHP_AUTH_USER']))
		{
			$_SESSION['authorized'] = true;
			addlog("AUTH: user [" .$user ."] successfully identified!");
			addstreaminglog("iStreamDev user [" .$_SERVER['PHP_AUTH_USER'] ."] successfully identified!");
		}
	}

	// login
	if (!$_SESSION['authorized'])
	{
		addlog("AUTH: identification failed: " .$_SERVER['PHP_AUTH_USER'] ."/" .$_SERVER['PHP_AUTH_PW']);
		addstreaminglog("iStreamDev identification failed: " .$_SERVER['PHP_AUTH_USER'] ."/" .$_SERVER['PHP_AUTH_PW']);

		header('WWW-Authenticate: Basic Realm="Login please"');
		header('HTTP/1.0 401 Unauthorized');
		echo "Incorrect user/password";
		exit;
	}
}

?>
