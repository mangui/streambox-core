<?php

global $username;

session_start();

$remote_ip = $_SERVER['REMOTE_ADDR'];
$remote_host= gethostbyaddr($remote_ip);

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
			addlog("user [" .$username ."] successfully identified! IP/HOST=[" .$remote_ip ."/" .$remote_host ."]");
			addstreaminglog("user [" .$username ."] successfully identified! IP/HOST=[" .$remote_ip ."/" .$remote_host ."]");
		}


		// login
		if (!$_SESSION['authorized'])
		{
			addlog("Identification failed: " .$_SERVER['PHP_AUTH_USER'] ."/" .$_SERVER['PHP_AUTH_PW'] ."IP/HOST=[" .$remote_ip ."/" .$remote_host ."]");
			addstreaminglog("Identification failed: " .$_SERVER['PHP_AUTH_USER'] ."/" .$_SERVER['PHP_AUTH_PW'] ."IP/HOST=[" .$remote_ip ."/" .$remote_host ."]");

			header('WWW-Authenticate: Basic Realm="Login please"');
			header('HTTP/1.0 401 Unauthorized');
			echo "Incorrect user/password";
			exit;
		}
	}
	else
	{
		header('WWW-Authenticate: Basic Realm="Login please"');
		header('HTTP/1.0 401 Unauthorized');
	}
}

?>
