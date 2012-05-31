<?php

global $user, $pass;

session_start();

if (isset($_COOKIE['istreamdev']))
{
       if(sha1($pass) == $_COOKIE['istreamdev'] ) {
 		setcookie ("istream", sha1($pass), time()+60*60*24*30);
		$authorized = true;
	} else {
		$authorised = false;
	}
}

# checkup login and password
if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
{
    if (($user == $_SERVER['PHP_AUTH_USER']) && ($pass == ($_SERVER['PHP_AUTH_PW'])) )
    {
    setcookie ("istreamdev", sha1($pass), time()+60*60*24*30);
    $authorized = true;
    }
}

# login
if (!$authorized)
{
    header('WWW-Authenticate: Basic Realm="Login please"');
    header('HTTP/1.0 401 Unauthorized');
    echo "Login";
    exit;
}

?>
