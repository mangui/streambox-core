<?php

global $username;

session_start();

list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':' , base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
$username=$_SERVER['PHP_AUTH_USER'];

if ($_SESSION['authorized'] == false)
{
	addlog("AUTH: connection attempt with " .$_SERVER['PHP_AUTH_USER'] ."/" .$_SERVER['PHP_AUTH_PW']);

	// checkup login and password
	if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
	{
		if($_SERVER['PHP_AUTH_PW'] == sqlgetuserinfo("password", $_SERVER['PHP_AUTH_USER']))
		{
			$_SESSION['authorized'] = true;
			addlog("AUTH: user [" .$username ."] successfully identified!");
			addstreaminglog("iStreamDev user [" .$username ."] successfully identified!");

			sqlsetuserstat("last_connection", $username, date("Y/m/d H:i:s"));
			$num=sqlgetuserstat("num_connections", $username);
			$num++;
			sqlsetuserstat("num_connections", $username, $num);
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
